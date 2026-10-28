/** Safe copy only. Never render Laravel messages, exception text, or stack traces. */
export const ERROR_MESSAGES = Object.freeze({
    network: { fa: 'اتصال اینترنت برقرار نیست. لطفاً اتصال را بررسی کنید و دوباره تلاش کنید.', en: 'You appear to be offline. Check your connection and try again.' },
    timeout: { fa: 'پاسخ سامانه بیش از حد طول کشید. لطفاً دوباره تلاش کنید.', en: 'The server took too long to respond. Please try again.' },
    session_expired: { fa: 'نشست شما منقضی شده است. صفحه را تازه‌سازی کنید و دوباره تلاش کنید.', en: 'Your session expired. Refresh the page and try again.' },
    rate_limited: { fa: 'درخواست‌های زیادی ثبت شده است. لطفاً کمی بعد دوباره تلاش کنید.', en: 'Too many attempts. Please wait a little and try again.' },
    slot_unavailable: { fa: 'این ساعت دیگر خالی نیست. لطفاً ساعت دیگری انتخاب کنید.', en: 'That time is no longer available. Please choose another slot.' },
    idempotency_conflict: { fa: 'اطلاعات این درخواست تغییر کرده است. لطفاً رزرو را دوباره بررسی کنید.', en: 'The request details changed. Please review your booking again.' },
    request_in_progress: { fa: 'درخواست شما هنوز در حال پردازش است. لطفاً چند لحظه صبر کنید.', en: 'Your request is still being processed. Please wait a moment.' },
    phone_verification_not_ready: { fa: 'تأیید شماره موبایل برای رزرو آنلاین هنوز راه‌اندازی نشده است. لطفاً با کلینیک تماس بگیرید.', en: 'Mobile verification for online booking is not set up yet. Please contact the clinic.' },
    validation_failed: { fa: 'برخی اطلاعات نیاز به اصلاح دارد. لطفاً موارد مشخص‌شده را بررسی کنید.', en: 'Some details need correcting. Please review the highlighted fields.' },
    unauthenticated: { fa: 'امکان انجام این درخواست وجود ندارد. لطفاً صفحه را تازه‌سازی کنید.', en: 'This request could not be completed. Please refresh the page.' },
    forbidden: { fa: 'امکان انجام این درخواست وجود ندارد.', en: 'This request cannot be completed.' },
    not_found: { fa: 'اطلاعات درخواستی یافت نشد. لطفاً از ابتدا تلاش کنید.', en: 'We could not find that information. Please start again.' },
    conflict: { fa: 'این درخواست با وضعیت فعلی سازگار نیست. لطفاً دوباره تلاش کنید.', en: 'This request conflicts with the current state. Please try again.' },
    csrf_invalid: { fa: 'نشست شما منقضی شده است. صفحه را تازه‌سازی کنید و دوباره تلاش کنید.', en: 'Your session expired. Refresh the page and try again.' },
    internal: { fa: 'خطایی در سامانه رخ داد. لطفاً دوباره تلاش کنید.', en: 'Something went wrong on our side. Please try again.' },
    server_error: { fa: 'خطایی در سامانه رخ داد. لطفاً دوباره تلاش کنید.', en: 'Something went wrong on our side. Please try again.' },
    invalid_response: { fa: 'پاسخ نامعتبری از سامانه دریافت شد. لطفاً دوباره تلاش کنید.', en: 'We received an unexpected response. Please try again.' },
    unknown: { fa: 'خطای پیش‌بینی‌نشده‌ای رخ داد. لطفاً دوباره تلاش کنید.', en: 'An unexpected error occurred. Please try again.' },
});

export const FIELD_MESSAGES = Object.freeze({
    full_name: { fa: 'نام و نام خانوادگی را کامل وارد کنید.', en: 'Please enter your full name.' },
    mobile: { fa: 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.', en: 'Enter an 11-digit mobile number starting with 09.' },
    service_id: { fa: 'خدمت انتخاب‌شده معتبر نیست.', en: 'The selected service is not valid.' },
    start_at: { fa: 'زمان انتخاب‌شده معتبر نیست.', en: 'The selected time is not valid.' },
});

export const UI_MESSAGES = Object.freeze({
    loading_services: { fa: 'در حال دریافت خدمات قابل رزرو…', en: 'Loading bookable services…' },
    no_services: { fa: 'فعلاً خدمتی برای رزرو آنلاین در دسترس نیست.', en: 'No services are currently available for online booking.' },
    loading_slots: { fa: 'در حال دریافت ساعت‌های خالی…', en: 'Loading available times…' },
    no_slots: { fa: 'برای این تاریخ ساعت خالی پیدا نشد. تاریخ دیگری را انتخاب کنید.', en: 'No available times were found for this date. Please choose another date.' },
    retry: { fa: 'تلاش دوباره', en: 'Try again' },
    submitting: { fa: 'در حال ثبت', en: 'Submitting' },
    errors_heading: { fa: 'لطفاً موارد زیر را بررسی کنید:', en: 'Please review the following:' },
});

export function currentLanguage(documentRef = globalThis.document) {
    return (documentRef?.documentElement?.lang ?? 'fa').toLowerCase().startsWith('en') ? 'en' : 'fa';
}

export function messageFor(code, lang = currentLanguage()) {
    const normalized = String(code ?? '').toUpperCase();
    const key = ({
        SLOT_UNAVAILABLE: 'slot_unavailable', IDEMPOTENCY_CONFLICT: 'idempotency_conflict',
        REQUEST_IN_PROGRESS: 'request_in_progress', PHONE_VERIFICATION_NOT_READY: 'phone_verification_not_ready', VALIDATION_FAILED: 'validation_failed',
        UNAUTHENTICATED: 'unauthenticated', FORBIDDEN: 'forbidden', NOT_FOUND: 'not_found',
        CONFLICT: 'conflict', RATE_LIMITED: 'rate_limited', CSRF_INVALID: 'csrf_invalid',
        INTERNAL: 'internal',
    })[normalized] ?? (ERROR_MESSAGES[String(code)] ? String(code) : 'unknown');
    return ERROR_MESSAGES[key][lang] ?? ERROR_MESSAGES[key].fa;
}

export function fieldMessageFor(field, lang = currentLanguage()) {
    const key = ({ name: 'full_name', fullName: 'full_name', phone: 'mobile', startAt: 'start_at', serviceId: 'service_id' })[field] ?? field;
    return FIELD_MESSAGES[key]?.[lang] ?? FIELD_MESSAGES[key]?.fa ?? null;
}

export function uiMessage(key, lang = currentLanguage()) {
    return UI_MESSAGES[key]?.[lang] ?? UI_MESSAGES[key]?.fa ?? '';
}
