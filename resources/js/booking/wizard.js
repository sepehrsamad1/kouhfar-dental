import { createBookingApi } from './api-client.js';
import { currentLanguage, fieldMessageFor, messageFor, uiMessage } from './error-messages.js';
import { addClinicDays, clinicDate, dateAriaLabel, formatDate, formatTime, normalizeDigits, slotAriaLabel, toPersianDigits } from './format.js';

const PHONE = /^09\d{9}$/;
const SAFE_KEY = () => globalThis.crypto.randomUUID();

/** @param {{toast?:{show:(message:string)=>void}, goto?:(hash:string)=>void, api?:ReturnType<typeof createBookingApi>}} [deps] */
export function initBookingWizard(deps = {}) {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const api = deps.api ?? createBookingApi();
    const lang = currentLanguage();
    const faDigits = (value) => lang === 'fa' ? toPersianDigits(value) : String(value);

    const dialog = $('#bk');
    const rail = $('#bkRail');
    const next = $('#bkNext');
    const back = $('#bkBack');
    const foot = $('#bkFoot');
    const servicesWrap = $('#bkServices');
    const datesWrap = $('#bkDates');
    const timesWrap = $('#bkTimes');
    const errors = $('#bkErrors');
    const live = $('#bkLive');
    const nameInput = $('#bkName');
    const phoneInput = $('#bkPhone');
    const STEPS = ['انتخاب خدمت', 'انتخاب تاریخ', 'انتخاب ساعت', 'اطلاعات تماس', 'تأیید رزرو'];
    const state = {
        step: 1, service: null, date: '', slot: null, services: [], submitting: false,
        attempt: null, result: null, servicesLoaded: false, servicesLoading: false,
    };
    let servicesAbort = null;
    let availabilityAbort = null;
    let pendingPreselect = '';

    STEPS.forEach((label, index) => {
        const item = document.createElement('span');
        item.className = 'bk__step';
        const number = document.createElement('i');
        number.textContent = toPersianDigits(index + 1);
        item.append(number, document.createTextNode(label));
        rail.append(item);
    });

    function announce(text) {
        if (!live) return;
        live.textContent = '';
        requestAnimationFrame(() => { live.textContent = text; });
    }

    function clearErrors() {
        if (!errors) return;
        errors.hidden = true;
        errors.replaceChildren();
    }

    function showErrors(messages, retryAction = null) {
        if (!errors) {
            deps.toast?.show(messages[0] ?? messageFor('UNKNOWN', lang));
            return;
        }
        const title = document.createElement('p');
        title.className = 'bk__errsH';
        title.textContent = uiMessage('errors_heading', lang);
        const list = document.createElement('ul');
        for (const text of messages.filter(Boolean)) {
            const item = document.createElement('li');
            item.textContent = text;
            list.append(item);
        }
        errors.replaceChildren(title, list);
        if (retryAction) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn--ghost bk__errsRetry';
            button.textContent = uiMessage('retry', lang);
            button.addEventListener('click', () => { clearErrors(); retryAction(); });
            errors.append(button);
        }
        errors.hidden = false;
        errors.focus();
        announce(messages.join(' '));
    }

    function fieldError(field, show) {
        const mapping = {
            full_name: { root: '#fName', input: nameInput, error: '#eName' },
            mobile: { root: '#fPhone', input: phoneInput, error: '#ePhone' },
        }[field];
        if (!mapping) return;
        const error = $(mapping.error);
        const text = show ? fieldMessageFor(field, lang) : '';
        $(mapping.root).classList.toggle('is-bad', Boolean(text));
        mapping.input.setAttribute('aria-invalid', String(Boolean(text)));
        if (error) error.textContent = text ?? '';
        if (error) mapping.input.setAttribute('aria-describedby', error.id);
    }

    function validName(show = false) {
        const okay = nameInput.value.trim().length >= 3;
        fieldError('full_name', !okay && (show || nameInput.value.length > 0));
        return okay;
    }

    function normalizePhone(value) {
        return normalizeDigits(value).replace(/\D/g, '');
    }

    function validPhone(show = false) {
        const okay = PHONE.test(normalizePhone(phoneInput.value));
        fieldError('mobile', !okay && (show || normalizePhone(phoneInput.value).length > 0));
        return okay;
    }

    function currentPayload() {
        return {
            service_id: state.service?.id ?? '',
            start_at: state.slot?.start_at ?? '',
            full_name: nameInput.value.trim(),
            mobile: normalizePhone(phoneInput.value),
        };
    }

    function payloadFingerprint(payload) {
        return JSON.stringify([payload.service_id, payload.start_at, payload.full_name, payload.mobile]);
    }

    function invalidateAttemptIfPayloadChanged() {
        if (state.attempt && state.attempt.fingerprint !== payloadFingerprint(currentPayload())) state.attempt = null;
    }

    function attemptFor(payload) {
        const fingerprint = payloadFingerprint(payload);
        if (!state.attempt || state.attempt.fingerprint !== fingerprint) {
            state.attempt = { key: SAFE_KEY(), fingerprint };
        }
        return state.attempt;
    }

    function selectService(service) {
        if (state.service?.id === service.id) return;
        servicesAbort?.abort();
        availabilityAbort?.abort();
        state.service = service;
        state.date = '';
        state.slot = null;
        state.attempt = null;
        renderDates();
        timesWrap.replaceChildren();
        $('#bkTimeSub').textContent = 'ساعت‌های خالی برای تاریخ انتخابی';
        sync();
    }

    function renderServices() {
        servicesWrap.replaceChildren(...state.services.map((service) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'bk__opt';
            button.dataset.serviceId = service.id;
            button.setAttribute('aria-pressed', String(state.service?.id === service.id));
            const title = document.createElement('span');
            title.className = 'bk__optT';
            title.textContent = service.name_fa;
            button.append(title);
            return button;
        }));
    }

    async function loadServices(force = false) {
        if (state.servicesLoading || (state.servicesLoaded && !force)) return;
        servicesAbort?.abort();
        const controller = (servicesAbort = new AbortController());
        state.servicesLoading = true;
        servicesWrap.setAttribute('aria-busy', 'true');
        try {
            state.services = await api.services(controller.signal);
            if (controller.signal.aborted) return;
            state.servicesLoaded = true;
            renderServices();
            if (pendingPreselect) applyPreselect(pendingPreselect);
            if (state.services.length === 0) showErrors([uiMessage('no_services', lang)], () => loadServices(true));
        } catch (error) {
            if (!controller.signal.aborted && error?.kind !== 'aborted') {
                showErrors([messageFor(error?.code, lang)], () => loadServices(true));
            }
        } finally {
            if (servicesAbort === controller) {
                state.servicesLoading = false;
                servicesWrap.removeAttribute('aria-busy');
                sync();
            }
        }
    }

    function applyPreselect(label) {
        const normalized = String(label).trim();
        const service = state.services.find((item) => item.name_fa === normalized)
            ?? state.services.find((item) => normalized.includes(item.name_fa) || item.name_fa.includes(normalized));
        pendingPreselect = '';
        if (service) {
            selectService(service);
            $$('.bk__opt', servicesWrap).forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.serviceId === service.id)));
            if (state.step === 1) goTo(2);
        }
    }

    servicesWrap.addEventListener('click', (event) => {
        const button = event.target.closest('.bk__opt');
        if (!button) return;
        const service = state.services.find((item) => item.id === button.dataset.serviceId);
        if (!service) return;
        selectService(service);
        $$('.bk__opt', servicesWrap).forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
        clearErrors();
        sync();
    });

    function renderDates() {
        datesWrap.replaceChildren();
        if (!state.service) return;
        const first = clinicDate();
        // UI range matches the approved Task 13 horizon; server remains authoritative for each slot.
        for (let offset = 0; offset <= 14; offset += 1) {
            const isoDate = addClinicDays(first, offset);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'bk__date';
            button.dataset.date = isoDate;
            button.setAttribute('aria-pressed', String(state.date === isoDate));
            button.setAttribute('aria-label', dateAriaLabel(isoDate, lang));
            for (const [className, options] of [
                ['bk__dw', { weekday: 'short' }], ['bk__dd', { day: 'numeric' }], ['bk__dm', { month: 'long' }],
            ]) {
                const span = document.createElement('span');
                span.className = className;
                span.setAttribute('aria-hidden', 'true');
                span.textContent = formatDate(isoDate, options, lang);
                button.append(span);
            }
            datesWrap.append(button);
        }
    }

    datesWrap.addEventListener('click', (event) => {
        const button = event.target.closest('.bk__date');
        if (!button || !state.service) return;
        state.date = button.dataset.date;
        state.slot = null;
        state.attempt = null;
        $$('.bk__date', datesWrap).forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
        clearErrors();
        loadAvailability();
        sync();
    });

    async function loadAvailability() {
        availabilityAbort?.abort();
        const controller = (availabilityAbort = new AbortController());
        const { service, date } = state;
        if (!service || !date) return;
        timesWrap.replaceChildren();
        timesWrap.setAttribute('aria-busy', 'true');
        $('#bkTimeSub').textContent = uiMessage('loading_slots', lang);
        try {
            const slots = await api.availability(service.id, date, controller.signal);
            if (controller.signal.aborted || state.service?.id !== service.id || state.date !== date) return;
            const visibleSlots = slots.filter((slot) => slot.start_at && new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit',
            }).format(new Date(slot.start_at)) === date);
            $('#bkTimeSub').textContent = `ساعت‌های خالی برای ${formatDate(date, { weekday: 'long', day: 'numeric', month: 'long' }, lang)}`;
            timesWrap.replaceChildren(...visibleSlots.map((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'bk__time';
                button.dataset.startAt = slot.start_at;
                button.setAttribute('aria-pressed', String(state.slot?.start_at === slot.start_at));
                button.setAttribute('aria-label', slotAriaLabel(slot.start_at, lang));
                button.textContent = formatTime(slot.start_at, lang);
                return button;
            }));
            if (state.slot && !visibleSlots.some((slot) => slot.start_at === state.slot.start_at)) state.slot = null;
            if (!visibleSlots.length) {
                $('#bkTimeSub').textContent = uiMessage('no_slots', lang);
                announce($('#bkTimeSub').textContent);
            }
        } catch (error) {
            if (!controller.signal.aborted && error?.kind !== 'aborted') {
                $('#bkTimeSub').textContent = '';
                showErrors([messageFor(error?.code, lang)], () => loadAvailability());
            }
        } finally {
            if (availabilityAbort === controller) {
                timesWrap.removeAttribute('aria-busy');
                sync();
            }
        }
    }

    timesWrap.addEventListener('click', (event) => {
        const button = event.target.closest('.bk__time');
        if (!button) return;
        state.slot = { start_at: button.dataset.startAt };
        state.attempt = null;
        $$('.bk__time', timesWrap).forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
        announce(button.getAttribute('aria-label') ?? '');
        clearErrors();
        sync();
    });

    function review(target) {
        const serviceName = state.service?.name_fa ?? '—';
        const rows = [
            ['خدمت', serviceName],
            ['تاریخ', state.slot ? formatDate(clinicDate(new Date(state.slot.start_at)), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }, lang) : '—'],
            ['ساعت', state.slot ? formatTime(state.slot.start_at, lang) : '—'],
            ['نام', nameInput.value.trim() || '—'],
            ['موبایل', faDigits(normalizePhone(phoneInput.value)) || '—'],
        ];
        target.replaceChildren(...rows.map(([key, value]) => {
            const row = document.createElement('div'); row.className = 'bk__rrow';
            const heading = document.createElement('span'); heading.className = 'bk__rk'; heading.textContent = key;
            const detail = document.createElement('span'); detail.className = 'bk__rv'; detail.textContent = value;
            row.append(heading, detail);
            return row;
        }));
    }

    function canAdvance() {
        if (state.submitting) return false;
        if (state.step === 1) return Boolean(state.service);
        if (state.step === 2) return Boolean(state.date);
        if (state.step === 3) return Boolean(state.slot);
        if (state.step === 4) return validName() && validPhone();
        return true;
    }

    function focusStep() {
        const heading = $(`.bk__pane[data-step="${state.step}"] .bk__h, .bk__pane[data-step="${state.step}"] .bk__okH`);
        if (!heading) return;
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
    }

    function sync() {
        $$('.bk__pane').forEach((pane) => pane.classList.toggle('is-now', Number(pane.dataset.step) === state.step));
        $$('.bk__step', rail).forEach((step, index) => {
            step.classList.toggle('is-now', index + 1 === state.step);
            step.classList.toggle('is-done', index + 1 < state.step);
        });
        rail.style.display = state.step === 6 ? 'none' : '';
        foot.style.display = state.step === 6 ? 'none' : '';
        back.hidden = state.step <= 1;
        if (!state.submitting) next.textContent = state.step === 5 ? 'تأیید و ثبت رزرو' : 'مرحله بعد';
        next.setAttribute('aria-disabled', String(!canAdvance()));
        if (state.step === 5) review($('#bkReview'));
    }

    function goTo(step) {
        state.step = step;
        clearErrors();
        sync();
        $('.bk__body').scrollTop = 0;
        focusStep();
    }

    function applyServerFieldErrors(fields) {
        const recognized = fields.filter((field) => fieldMessageFor(field, lang));
        if (recognized.includes('service_id')) goTo(1);
        else if (recognized.includes('start_at')) { state.slot = null; goTo(3); loadAvailability(); }
        else if (recognized.some((field) => ['full_name', 'mobile'].includes(field))) {
            goTo(4);
            if (recognized.includes('full_name')) validName(true);
            if (recognized.includes('mobile')) validPhone(true);
        }
        const messages = recognized.map((field) => fieldMessageFor(field, lang)).filter(Boolean);
        showErrors(messages.length ? messages : [messageFor('VALIDATION_FAILED', lang)]);
    }

    async function submit() {
        if (state.submitting || !state.service || !state.slot) return;
        const payload = currentPayload();
        const attempt = attemptFor(payload);
        state.submitting = true;
        clearErrors();
        next.replaceChildren();
        const spinner = document.createElement('span'); spinner.className = 'spin';
        next.append(spinner, document.createTextNode(uiMessage('submitting', lang)));
        next.setAttribute('aria-disabled', 'true');
        next.setAttribute('aria-busy', 'true');
        back.disabled = true;
        announce(uiMessage('submitting', lang));

        try {
            const result = await api.create(payload, attempt.key);
            // Only render confirmation after a real 201 with a valid pending/confirmed appointment result.
            state.result = result;
            state.attempt = null;
            $('#bkRef').textContent = result.status === 'pending' ? 'در انتظار تأیید کلینیک' : 'تأیید شده';
            review($('#bkDetails'));
            state.submitting = false;
            next.removeAttribute('aria-busy');
            back.disabled = false;
            goTo(6);
        } catch (error) {
            state.submitting = false;
            next.removeAttribute('aria-busy');
            back.disabled = false;
            if (error?.kind === 'aborted') { sync(); return; }
            const code = String(error?.code ?? 'UNKNOWN').toUpperCase();
            if (code === 'SLOT_UNAVAILABLE') {
                state.slot = null;
                goTo(3);
                loadAvailability();
                showErrors([messageFor(code, lang)]);
            } else if (code === 'VALIDATION_FAILED' && error.fields?.length) {
                applyServerFieldErrors(error.fields);
            } else if (code === 'IDEMPOTENCY_CONFLICT') {
                state.attempt = null;
                sync();
                showErrors([messageFor(code, lang)]);
            } else {
                sync();
                showErrors([messageFor(code, lang)]);
            }
        }
    }

    next.addEventListener('click', () => {
        if (state.submitting) return;
        if (!canAdvance()) {
            if (state.step === 4) {
                validName(true); validPhone(true);
                $('#bk .field.is-bad input')?.focus();
                deps.toast?.show('نام و شماره موبایل را کامل وارد کنید');
            } else if (state.step === 1 && state.services.length === 0) {
                showErrors([uiMessage('no_services', lang)], () => loadServices(true));
            } else deps.toast?.show('برای ادامه، یک گزینه انتخاب کنید');
            return;
        }
        if (state.step === 5) { submit(); return; }
        goTo(state.step + 1);
    });

    back.addEventListener('click', () => { if (state.step > 1 && !state.submitting) goTo(state.step - 1); });
    [nameInput, phoneInput].forEach((input) => input.addEventListener('input', () => {
        if (input === phoneInput) phoneInput.value = normalizePhone(phoneInput.value).slice(0, 11);
        invalidateAttemptIfPayloadChanged();
        sync();
    }));

    $('#bkShow').addEventListener('click', (event) => {
        const detail = $('#bkDetails');
        detail.hidden = !detail.hidden;
        event.currentTarget.textContent = detail.hidden ? 'مشاهده جزئیات رزرو' : 'بستن جزئیات';
    });
    $('#bkHome').addEventListener('click', () => { close(); setTimeout(() => deps.goto?.('#home'), 260); });

    function reset() {
        servicesAbort?.abort(); availabilityAbort?.abort();
        Object.assign(state, { step: 1, service: null, date: '', slot: null, submitting: false, attempt: null, result: null });
        $$('.bk__opt', servicesWrap).forEach((button) => button.setAttribute('aria-pressed', 'false'));
        datesWrap.replaceChildren(); timesWrap.replaceChildren();
        $('#bkDetails').hidden = true;
        nameInput.value = ''; phoneInput.value = '';
        fieldError('full_name', false); fieldError('mobile', false); clearErrors();
    }

    function open(preselect = '') {
        if (state.step === 6) reset();
        if (preselect) {
            if (state.servicesLoaded) applyPreselect(preselect);
            else pendingPreselect = preselect;
        }
        if (!dialog.open) dialog.showModal();
        requestAnimationFrame(() => dialog.classList.add('is-on'));
        document.body.classList.add('is-locked');
        if (!state.servicesLoaded) loadServices();
        sync();
        focusStep();
    }

    function close() {
        if (state.submitting) return;
        dialog.classList.remove('is-on');
        document.body.classList.remove('is-locked');
        setTimeout(() => { if (dialog.open) dialog.close(); }, 420);
    }

    $('#bkClose').addEventListener('click', close);
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
    sync();
    return { open, close };
}
