import { test, expect } from '@playwright/test';

test.describe('Task 12 staff configuration responsive checks', () => {
    test('configuration page stays within the viewport at 375, 768, and 1440 pixels', async ({ page }) => {
        await page.goto('/staff/login');
        await page.locator('#email').fill('browser-admin@example.invalid');
        await page.locator('#password').fill('BrowserOnlyPassword!2026');
        await page.getByRole('button', { name: 'ورود' }).click();
        await expect(page).toHaveURL(/\/staff$/);

        for (const width of [375, 768, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto('/staff/configuration');

            await expect(page.getByRole('heading', { name: 'تنظیمات کلینیک' })).toBeVisible();
            await expect(page.getByText('خدمات قابل ارائه', { exact: false }).first()).toBeVisible();

            const layout = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                document: document.documentElement.scrollWidth,
            }));

            expect(layout.document, `horizontal overflow at ${width}px`).toBeLessThanOrEqual(layout.viewport + 1);
            await page.screenshot({
                path: `test-results/task12-configuration-${width}.png`,
                fullPage: true,
            });
        }
    });
});
