/** Bounded exponential backoff with full jitter. */
export const DEFAULT_BASE_MS = 350;
export const DEFAULT_CAP_MS = 3000;

export function backoffDelay(attempt, { base = DEFAULT_BASE_MS, cap = DEFAULT_CAP_MS, random = Math.random } = {}) {
    return Math.floor(random() * Math.min(cap, base * (2 ** attempt)));
}

export function sleep(ms, signal) {
    return new Promise((resolve, reject) => {
        if (signal?.aborted) {
            reject(signal.reason ?? new DOMException('Aborted', 'AbortError'));
            return;
        }

        const timer = setTimeout(() => {
            signal?.removeEventListener('abort', onAbort);
            resolve();
        }, ms);

        function onAbort() {
            clearTimeout(timer);
            reject(signal?.reason ?? new DOMException('Aborted', 'AbortError'));
        }

        signal?.addEventListener('abort', onAbort, { once: true });
    });
}

/** @template T @param {(attempt:number)=>Promise<T>} operation @param {{retries:number, shouldRetry:(error:any, attempt:number)=>boolean, signal?:AbortSignal, base?:number, cap?:number, random?:()=>number, sleep?:(ms:number, signal?:AbortSignal)=>Promise<void>}} options */
export async function withRetry(operation, options) {
    const { retries, shouldRetry, signal, base, cap = DEFAULT_CAP_MS, random, sleep: wait = sleep } = options;

    for (let attempt = 0; ; attempt += 1) {
        try {
            return await operation(attempt);
        } catch (error) {
            if (attempt >= retries || signal?.aborted || !shouldRetry(error, attempt)) throw error;
            const delay = Math.min(cap, Math.max(backoffDelay(attempt, { base, cap, random }), error?.retryAfterMs ?? 0));
            await wait(delay, signal);
        }
    }
}
