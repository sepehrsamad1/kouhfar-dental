export const CLINIC_TIMEZONE = 'Asia/Tehran';
const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const ARABIC_INDIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

export const toPersianDigits = (value) => String(value).replace(/[0-9]/g, (d) => PERSIAN_DIGITS[Number(d)]);
export const normalizeDigits = (value) => String(value)
    .replace(/[۰-۹]/g, (d) => String(PERSIAN_DIGITS.indexOf(d)))
    .replace(/[٠-٩]/g, (d) => String(ARABIC_INDIC_DIGITS.indexOf(d)));

export function clinicDate(instant = new Date()) {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: CLINIC_TIMEZONE, year: 'numeric', month: '2-digit', day: '2-digit',
    }).formatToParts(instant);
    const get = (key) => parts.find((part) => part.type === key)?.value ?? '';
    return `${get('year')}-${get('month')}-${get('day')}`;
}

export function addClinicDays(isoDate, amount) {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day + amount, 12));
    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
}

export function formatDate(isoDate, options = {}, lang = 'fa') {
    const [year, month, day] = isoDate.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day, 12));
    return new Intl.DateTimeFormat(lang === 'en' ? 'en-GB-u-ca-persian' : 'fa-IR-u-ca-persian', {
        timeZone: CLINIC_TIMEZONE, ...options,
    }).format(date);
}

export function formatTime(instant, lang = 'fa') {
    const value = new Intl.DateTimeFormat(lang === 'en' ? 'en-GB' : 'fa-IR', {
        timeZone: CLINIC_TIMEZONE, hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).format(new Date(instant));
    return lang === 'en' ? value : toPersianDigits(value);
}

export function slotAriaLabel(instant, lang = 'fa') {
    const date = new Intl.DateTimeFormat(lang === 'en' ? 'en-GB-u-ca-persian' : 'fa-IR-u-ca-persian', {
        timeZone: CLINIC_TIMEZONE, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).format(new Date(instant));
    return lang === 'en' ? `${formatTime(instant, lang)}, ${date}` : `ساعت ${formatTime(instant, lang)}، ${date}`;
}

export function dateAriaLabel(isoDate, lang = 'fa') {
    return formatDate(isoDate, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }, lang);
}
