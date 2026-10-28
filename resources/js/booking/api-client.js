import { withRetry } from './retry.js';
import { currentLanguage } from './error-messages.js';

/** @typedef {{ id:string, name_fa:string, duration_minutes?:number }} PublicService */
/** @typedef {{ start_at:string, end_at:string, display_local?:string, timezone?:string }} PublicSlot */
/** @typedef {{ status:'pending'|'confirmed', start_at:string, end_at:string, expires_at:string }} BookingResult */
/** @typedef {{ service_id:string, start_at:string, full_name:string, mobile:string }} BookingPayload */

export const API = Object.freeze({
    services: '/api/v1/services',
    availability: (serviceId, date) => `/api/v1/availability/${encodeURIComponent(serviceId)}?date=${encodeURIComponent(date)}`,
    create: '/api/v1/appointments',
});

const RETRYABLE = new Set([502, 503, 504]);
const ALLOWED_FIELDS = new Set(['full_name', 'mobile', 'service_id', 'start_at']);
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

export class BookingApiError extends Error {
    constructor(code, { status = 0, kind = 'http', retryAfterMs, fields = [] } = {}) {
        super(String(code)); // never retains untrusted response text
        this.name = 'BookingApiError';
        this.code = String(code);
        this.status = status;
        this.kind = kind;
        this.retryAfterMs = retryAfterMs;
        this.fields = fields;
    }
}

function csrfToken(documentRef) {
    return documentRef?.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function retryAfter(value) {
    if (!value) return undefined;
    const seconds = Number(value);
    if (Number.isFinite(seconds)) return Math.max(0, seconds * 1000);
    const timestamp = Date.parse(value);
    return Number.isNaN(timestamp) ? undefined : Math.max(0, timestamp - Date.now());
}

function statusCode(status) {
    return ({ 401: 'UNAUTHENTICATED', 403: 'FORBIDDEN', 404: 'NOT_FOUND', 409: 'CONFLICT', 419: 'CSRF_INVALID', 422: 'VALIDATION_FAILED', 429: 'RATE_LIMITED' })[status]
        ?? (status >= 500 ? 'INTERNAL' : 'UNKNOWN');
}

function retryable(error, { post = false } = {}) {
    if (!(error instanceof BookingApiError) || error.kind === 'aborted' || error.code === 'PHONE_VERIFICATION_NOT_READY') return false;
    if (error.kind === 'network' || error.kind === 'timeout' || RETRYABLE.has(error.status)) return true;
    // GETs may respect a short Retry-After. POSTs deliberately do not retry any HTTP 4xx.
    return !post && error.status === 429 && (error.retryAfterMs ?? Infinity) <= 3000;
}

/** @param {{ fetch?:typeof fetch, document?:Document, sleep?:(ms:number,signal?:AbortSignal)=>Promise<void>, random?:()=>number }} [deps] */
export function createBookingApi(deps = {}) {
    const fetcher = deps.fetch ?? globalThis.fetch.bind(globalThis);
    const doc = deps.document ?? globalThis.document;

    async function request(url, { method = 'GET', body, signal, timeoutMs = 10000, idempotencyKey } = {}) {
        const timeout = new AbortController();
        const timer = setTimeout(() => timeout.abort(new DOMException('Timeout', 'TimeoutError')), timeoutMs);
        let response;
        const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-TOKEN'] = csrfToken(doc);
        }
        if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey;
        try {
            response = await fetcher(url, {
                method, credentials: 'same-origin', cache: 'no-store', headers,
                body: body === undefined ? undefined : JSON.stringify(body),
                signal: AbortSignal.any([signal, timeout.signal].filter(Boolean)),
            });
        } catch (error) {
            if (signal?.aborted) throw new BookingApiError('UNKNOWN', { kind: 'aborted' });
            if (timeout.signal.aborted) throw new BookingApiError('UNKNOWN', { kind: 'timeout' });
            throw new BookingApiError('UNKNOWN', { kind: 'network' });
        } finally {
            clearTimeout(timer);
        }

        const type = response.headers.get('content-type') ?? '';
        let json = null;
        if (type.includes('application/json')) {
            try { json = await response.json(); } catch { /* normalized below */ }
        }
        if (!response.ok) {
            const rawCode = json?.error?.code ?? json?.code;
            const code = typeof rawCode === 'string' ? rawCode.toUpperCase() : statusCode(response.status);
            const errors = json?.errors ?? json?.error?.details ?? {};
            const fields = Array.isArray(errors)
                ? errors.map((item) => item?.field).filter((field) => ALLOWED_FIELDS.has(field))
                : Object.keys(errors).filter((field) => ALLOWED_FIELDS.has(field));
            throw new BookingApiError(code, {
                status: response.status, retryAfterMs: retryAfter(response.headers.get('retry-after')),
                fields: [...new Set(fields)],
            });
        }
        if (!json || typeof json !== 'object') throw new BookingApiError('INVALID_RESPONSE', { status: response.status, kind: 'invalid' });
        return { status: response.status, json };
    }

    const get = (url, signal) => withRetry(
        () => request(url, { signal, timeoutMs: 10000 }),
        { retries: 2, shouldRetry: (error) => retryable(error), signal, sleep: deps.sleep, random: deps.random },
    );

    return {
        /** @returns {Promise<PublicService[]>} */
        async services(signal) {
            const { json } = await get(API.services, signal);
            if (!Array.isArray(json.data) || !json.data.every((service) => UUID.test(service?.id) && typeof service?.name_fa === 'string')) {
                throw new BookingApiError('INVALID_RESPONSE', { kind: 'invalid' });
            }
            return json.data;
        },
        /** @returns {Promise<PublicSlot[]>} */
        async availability(serviceId, date, signal) {
            const { json } = await get(API.availability(serviceId, date), signal);
            const slots = json.data?.slots;
            if (!Array.isArray(slots) || !slots.every((slot) => typeof slot?.start_at === 'string' && !Number.isNaN(Date.parse(slot.start_at)))) {
                throw new BookingApiError('INVALID_RESPONSE', { kind: 'invalid' });
            }
            // Discard staff/resource identifiers even if the server mistakenly includes them.
            return slots.map(({ start_at, end_at }) => ({ start_at, end_at }));
        },
        /** @param {BookingPayload} payload @param {string} idempotencyKey @param {AbortSignal} [signal] @returns {Promise<BookingResult>} */
        async create(payload, idempotencyKey, signal) {
            if (typeof idempotencyKey !== 'string' || idempotencyKey.length < 16) throw new BookingApiError('UNKNOWN');
            const { status, json } = await withRetry(
                () => request(API.create, { method: 'POST', body: payload, idempotencyKey, signal, timeoutMs: 15000 }),
                { retries: 2, shouldRetry: (error) => retryable(error, { post: true }), signal, sleep: deps.sleep, random: deps.random },
            );
            const result = json;
            if (status !== 201 || !['pending', 'confirmed'].includes(result.status)
                || Number.isNaN(Date.parse(result.start_at ?? '')) || Number.isNaN(Date.parse(result.end_at ?? ''))) {
                throw new BookingApiError('INVALID_RESPONSE', { status, kind: 'invalid' });
            }
            return result;
        },
    };
}

export { currentLanguage };
