import { expect, test } from '@playwright/test';

const viewports = [
    { name: '375', width: 375, height: 812 },
    { name: '768', width: 768, height: 900 },
    { name: '1440', width: 1440, height: 1000 },
];

async function loginAsAdmin(page) {
    await page.goto('/staff/login');
    await page.getByLabel('ایمیل').fill('browser-admin@example.invalid');
    await page.getByLabel('رمز عبور').fill('BrowserOnlyPassword!2026');
    await page.getByRole('button', { name: 'ورود', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
}

for (const viewport of viewports) {
    test(`AI usage dashboard remains usable at ${viewport.name}px`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await loginAsAdmin(page);
        await page.goto('/staff/ai');

        await expect(page.getByRole('heading', { name: 'مدیریت دستیار هوشمند' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'مصرف ماه جاری' })).toBeVisible();
        await expect(page.locator('input[name="monthly_budget_irr"]')).toBeVisible();
        await expect(page.getByText('غیرفعال‌سازی اضطراری دستیار هوشمند')).toBeVisible();

        const dimensions = await page.evaluate(() => ({
            viewport: document.documentElement.clientWidth,
            document: document.documentElement.scrollWidth,
        }));

        expect(dimensions.document, `horizontal overflow at ${viewport.width}px`).toBeLessThanOrEqual(dimensions.viewport + 1);
        await page.screenshot({
            path: `test-results/task27-ai-admin-${viewport.name}.png`,
            fullPage: true,
        });
    });
}
