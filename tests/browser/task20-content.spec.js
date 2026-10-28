import { expect, test } from '@playwright/test';

const viewports = [
    { name: '375', width: 375, height: 812 },
    { name: '768', width: 768, height: 900 },
    { name: '1440', width: 1440, height: 1000 },
];

for (const viewport of viewports) {
    test(`published content remains usable at ${viewport.name}px`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto('/content/browser-public-content/fa');

        await expect(page.getByRole('heading', { name: 'محتوای مرورگر' })).toBeVisible();
        await expect(page.getByText('محتوای منتشرشده برای تست مرورگر.')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
        expect(overflow).toBe(false);

        await page.screenshot({
            path: `test-results/task20-content-${viewport.name}.png`,
            fullPage: true,
        });
    });
}
