import { test, expect } from '@playwright/test';

const SERVICE_ID = 'a3b6c70a-716b-4e1e-8c80-85cc211b7b98';
const TOMORROW = (() => {
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
    const part = (key) => Number(parts.find((item) => item.type === key)?.value);
    const d = new Date(Date.UTC(part('year'), part('month') - 1, part('day') + 1, 12));
    return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}-${String(d.getUTCDate()).padStart(2, '0')}`;
})();
const SLOT = (() => { const [y, m, d] = TOMORROW.split('-').map(Number); return new Date(Date.UTC(y, m - 1, d, 5, 30)).toISOString().replace('.000Z', 'Z'); })(); // 09:00 Asia/Tehran
const API = '**/api/v1/';

async function stubCatalog(page) {
    await page.route(`${API}services`, (route) => route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [{ id: SERVICE_ID, name_fa: 'خدمت آزمایشی', duration_minutes: 30 }] }),
    }));
    await page.route(`${API}availability/**`, (route) => route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { date: TOMORROW, timezone: 'Asia/Tehran', slots: [{ start_at: SLOT, end_at: '2026-10-29T06:00:00Z' }] } }),
    }));
}

const appointment = (status = 'pending') => ({
    status,
    start_at: SLOT,
    end_at: '2026-10-29T06:00:00Z',
    expires_at: '2026-10-29T06:30:00Z',
});

async function fillToReview(page) {
    await page.locator('[data-book]').first().click();
    await page.locator('#bkServices .bk__opt').first().click();
    await page.locator('#bkNext').click();
    await page.locator('#bkDates .bk__date').nth(1).click();
    await page.locator('#bkNext').click();
    await page.locator('#bkTimes .bk__time').first().click();
    await page.locator('#bkNext').click();
    await page.fill('#bkName', 'کاربر آزمایشی');
    await page.fill('#bkPhone', '09000000000'); // synthetic, non-routable test value
    await page.locator('#bkNext').click();
    await expect(page.locator('.bk__pane[data-step="5"]')).toHaveClass(/is-now/);
}

test('does not show success before the server confirms the appointment request', async ({ page }) => {
    await stubCatalog(page);
    let release;
    const waiting = new Promise((resolve) => { release = resolve; });
    await page.route(`${API}appointments`, async (route) => {
        await waiting;
        await route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(appointment()) });
    });
    await page.goto('/');
    await fillToReview(page);
    await page.locator('#bkNext').click();
    await expect(page.locator('.bk__pane[data-step="6"]')).not.toHaveClass(/is-now/);
    await expect(page.locator('#bkNext')).toHaveAttribute('aria-disabled', 'true');
    release();
    await expect(page.locator('.bk__pane[data-step="6"]')).toHaveClass(/is-now/);
    await expect(page.locator('#bkRef')).toHaveText('در انتظار تأیید کلینیک');
    await expect(page.locator('#bkRef')).not.toContainText(/^[0-9a-f-]{36}$/i);
});

test('slot conflict returns to available times and never renders raw server text', async ({ page }) => {
    await stubCatalog(page);
    await page.route(`${API}appointments`, (route) => route.fulfill({
        status: 409, contentType: 'application/json',
        body: JSON.stringify({ code: 'SLOT_UNAVAILABLE', message: 'SQLSTATE private database detail' }),
    }));
    await page.goto('/');
    await fillToReview(page);
    await page.locator('#bkNext').click();
    await expect(page.locator('.bk__pane[data-step="3"]')).toHaveClass(/is-now/);
    await expect(page.locator('#bkErrors')).toContainText('این ساعت دیگر خالی نیست');
    await expect(page.locator('#bkErrors')).not.toContainText('SQLSTATE');
    await expect(page.locator('.bk__pane[data-step="6"]')).not.toHaveClass(/is-now/);
});

test('ambiguous network failure retries with the identical idempotency key', async ({ page }) => {
    await stubCatalog(page);
    const keys = [];
    await page.route(`${API}appointments`, (route) => {
        keys.push(route.request().headers()['idempotency-key']);
        if (keys.length === 1) return route.abort('internetdisconnected');
        return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(appointment()) });
    });
    await page.goto('/');
    await fillToReview(page);
    await page.locator('#bkNext').click();
    await expect(page.locator('#bkRef')).toHaveText('در انتظار تأیید کلینیک');
    expect(keys.length).toBe(2);
    expect(new Set(keys).size).toBe(1);
});

test('keyboard can complete the flow and announces Persian date/time', async ({ page }) => {
    await stubCatalog(page);
    await page.route(`${API}appointments`, (route) => route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify(appointment()) }));
    await page.goto('/');
    await page.locator('[data-book]').first().focus();
    await page.keyboard.press('Enter');
    const focus = async (selector) => {
        for (let i = 0; i < 60; i += 1) {
            if (await page.locator(selector).first().evaluate((node) => node === document.activeElement)) return;
            await page.keyboard.press('Tab');
        }
        throw new Error(`Keyboard focus did not reach ${selector}`);
    };
    await focus('#bkServices .bk__opt'); await page.keyboard.press('Enter');
    await focus('#bkNext'); await page.keyboard.press('Enter');
    await focus('#bkDates .bk__date:nth-of-type(2)');
    await expect(page.locator('#bkDates .bk__date').nth(1)).toHaveAttribute('aria-label', /پنجشنبه|مهر|آبان/);
    await page.keyboard.press('Enter');
    await focus('#bkNext'); await page.keyboard.press('Enter');
    await focus('#bkTimes .bk__time');
    await expect(page.locator('#bkTimes .bk__time').first()).toHaveAttribute('aria-label', /^ساعت ۰۹:۰۰، /);
    await page.keyboard.press('Enter');
    await focus('#bkNext'); await page.keyboard.press('Enter');
    await focus('#bkName'); await page.keyboard.type('کاربر آزمایشی');
    await page.keyboard.press('Tab'); await page.keyboard.type('09000000000');
    await focus('#bkNext'); await page.keyboard.press('Enter');
    await focus('#bkNext'); await page.keyboard.press('Enter');
    await expect(page.locator('#bkRef')).toHaveText('در انتظار تأیید کلینیک');
});

test('owner-required OTP gate blocks creation until Task 15 is configured', async ({ page }) => {
    await stubCatalog(page);
    let posts = 0;
    await page.route(`${API}appointments`, (route) => {
        posts += 1;
        return route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ code: 'PHONE_VERIFICATION_NOT_READY', message: 'provider config missing' }) });
    });
    await page.goto('/');
    await fillToReview(page);
    await page.locator('#bkNext').click();
    await expect(page.locator('#bkErrors')).toContainText('تأیید شماره موبایل برای رزرو آنلاین هنوز راه‌اندازی نشده است');
    await expect(page.locator('.bk__pane[data-step="6"]')).not.toHaveClass(/is-now/);
    await expect(page.locator('body')).not.toContainText('provider config missing');
    expect(posts).toBe(1); // configuration failure is not pointlessly retried
});
