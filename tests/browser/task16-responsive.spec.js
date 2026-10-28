import { test, expect } from '@playwright/test';

test.describe('Task 16 public booking UI responsive regression', () => {
    for (const width of [375, 768, 1440]) {
        test(`homepage remains stable at ${width}px`, async ({ page }) => {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto('/');
            await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
            await expect(page.locator('[data-book]').first()).toBeVisible();

            const layout = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                document: document.documentElement.scrollWidth,
            }));
            expect(layout.document, `horizontal overflow at ${width}px`).toBeLessThanOrEqual(layout.viewport + 1);

            await page.screenshot({
                path: `test-results/task16-responsive-${width}.png`,
                fullPage: true,
            });
        });
    }
});
