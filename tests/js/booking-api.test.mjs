import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createBookingApi, API } from '../../resources/js/booking/api-client.js';
import { messageFor, ERROR_MESSAGES, FIELD_MESSAGES } from '../../resources/js/booking/error-messages.js';
import { backoffDelay } from '../../resources/js/booking/retry.js';
import { formatTime, normalizeDigits, addClinicDays } from '../../resources/js/booking/format.js';

const serviceId = 'a3b6c70a-716b-4e1e-8c80-85cc211b7b98';
const payload = { service_id: serviceId, start_at: '2026-10-29T09:30:00Z', full_name: 'کاربر آزمایشی', mobile: '09000000000' };
const document = { querySelector: () => ({ getAttribute: () => 'csrf-token-test' }) };
const wait = async () => {};
const response = (status, body, headers = {}) => new Response(JSON.stringify(body), { status, headers: { 'content-type': 'application/json', ...headers } });
function mock(responses) {
    const calls = [];
    const fetch = async (url, init) => { calls.push({ url, init }); const next = responses.shift(); if (next instanceof Error) throw next; return typeof next === 'function' ? next() : next; };
    return { calls, fetch };
}

test('uses the implemented Laravel endpoints and typed shapes', async () => {
    const fixture = mock([
        response(200, { data: [{ id: serviceId, name_fa: 'مشاوره', duration_minutes: 30 }] }),
        response(200, { data: { date: '2026-10-29', timezone: 'Asia/Tehran', slots: [{ start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z', practitioner_id: 'private', resource_id: 'private' }] } }),
    ]);
    const api = createBookingApi({ fetch: fixture.fetch, document, sleep: wait });
    assert.equal((await api.services())[0].id, serviceId);
    const slots = await api.availability(serviceId, '2026-10-29');
    assert.deepEqual(slots, [{ start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z' }]);
    assert.equal(fixture.calls[0].url, '/api/v1/services');
    assert.equal(fixture.calls[1].url, API.availability(serviceId, '2026-10-29'));
});

test('POST sends snake_case Laravel payload, CSRF, same-origin credentials and idempotency', async () => {
    const fixture = mock([response(201, { status: 'pending', start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z', expires_at: '2026-10-29T10:30:00Z' })]);
    const result = await createBookingApi({ fetch: fixture.fetch, document, sleep: wait }).create(payload, 'booking-attempt-uuid-123456');
    assert.equal(result.status, 'pending');
    assert.equal(fixture.calls[0].url, '/api/v1/appointments');
    assert.equal(fixture.calls[0].init.credentials, 'same-origin');
    assert.equal(fixture.calls[0].init.headers['X-CSRF-TOKEN'], 'csrf-token-test');
    assert.equal(fixture.calls[0].init.headers['Idempotency-Key'], 'booking-attempt-uuid-123456');
    assert.deepEqual(JSON.parse(fixture.calls[0].init.body), payload);
});

test('same idempotency key survives ambiguous network and 503 retries', async () => {
    const fixture = mock([new TypeError('raw network detail'), response(503, { message: 'private stack' }), response(201, { status: 'pending', start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z', expires_at: '2026-10-29T10:30:00Z' })]);
    await createBookingApi({ fetch: fixture.fetch, document, sleep: wait }).create(payload, 'same-key-for-retry-12345');
    assert.equal(fixture.calls.length, 3);
    assert.deepEqual(fixture.calls.map((c) => c.init.headers['Idempotency-Key']), Array(3).fill('same-key-for-retry-12345'));
});

test('409 conflict maps to safe copy; no retry for HTTP 4xx or 500', async () => {
    for (const [status, body, code] of [
        [409, { code: 'SLOT_UNAVAILABLE', message: 'database detail' }, 'SLOT_UNAVAILABLE'],
        [409, { code: 'IDEMPOTENCY_CONFLICT', message: 'private detail' }, 'IDEMPOTENCY_CONFLICT'],
        [422, { message: 'phone raw text', errors: { mobile: ['raw'], internal_note: ['secret'] } }, 'VALIDATION_FAILED'],
        [500, { message: 'stack trace' }, 'INTERNAL'],
    ]) {
        const fixture = mock([response(status, body), response(201, { status: 'pending', start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z' })]);
        await assert.rejects(createBookingApi({ fetch: fixture.fetch, document }).create(payload, 'same-key-1234567890'), (error) => {
            assert.equal(error.code, code);
            assert.equal(error.message, code);
            assert.doesNotMatch(messageFor(error.code), /database|private|stack|raw/);
            if (status === 422) assert.deepEqual(error.fields, ['mobile']);
            return true;
        });
        assert.equal(fixture.calls.length, 1);
    }
});

test('phone verification not configured fails safely and is not retried', async () => {
    const fixture = mock([response(503, { code: 'PHONE_VERIFICATION_NOT_READY', message: 'provider secret missing' }), response(201, { status: 'pending', start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z' })]);
    await assert.rejects(createBookingApi({ fetch: fixture.fetch, document, sleep: wait }).create(payload, 'verification-gate-key-123456'), (error) => {
        assert.equal(error.code, 'PHONE_VERIFICATION_NOT_READY');
        assert.match(messageFor(error.code), /راه‌اندازی نشده/);
        assert.doesNotMatch(messageFor(error.code), /provider secret/);
        return true;
    });
    assert.equal(fixture.calls.length, 1);
});

test('success response must contain valid appointment result; no fake tracking codes', async () => {
    for (const body of [
        { status: 'saved', start_at: payload.start_at, end_at: '2026-10-29T10:00:00Z' },
    ]) {
        const fixture = mock([response(201, body)]);
        await assert.rejects(createBookingApi({ fetch: fixture.fetch, document }).create(payload, 'same-key-1234567890'), (error) => error.code === 'INVALID_RESPONSE');
    }
});
