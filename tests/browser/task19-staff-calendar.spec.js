import { expect, test } from '@playwright/test';

const viewports = [
    { name: '375', width: 375, height: 812 },
    { name: '768', width: 768, height: 900 },
    { name: '1440', width: 1440, height: 1000 },
];

async function login(page) {
    await page.goto('/staff/login');
    await page.getByLabel('ایمیل').fill('browser-admin@example.invalid');
    await page.getByLabel('رمز عبور').fill('BrowserOnlyPassword!2026');
    await page.getByRole('button', { name: 'ورود' }).click();
    await expect(page).toHaveURL(/\/staff$/);
}

for (const viewport of viewports) {
    test(`staff calendar remains usable at ${viewport.name}px`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await login(page);
        await page.goto('/staff/appointments?view=week&date=2026-10-29');

        await expect(page.getByRole('heading', { name: 'تقویم نوبت‌ها' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'فهرست دسترس‌پذیر' })).toBeVisible();
        await expect(page.getByRole('navigation', { name: 'نوع نمایش تقویم' })).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
        expect(overflow).toBe(false);

        await page.screenshot({
            path: `test-results/task19-calendar-${viewport.name}.png`,
            fullPage: true,
        });
    });
}
