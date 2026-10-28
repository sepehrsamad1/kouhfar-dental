import { test, expect } from '@playwright/test';

test.describe('Task 16 public booking UI responsive regression', () => {
    for (const width of [375, 768, 1440]) {
        test(`homepage remains stable at ${width}px`, async ({ page }) => {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto('/');
            await expect(page.locator('body')).toHaveClass(/hero-ready/, { timeout: 5000 });
            await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
            await expect(page.locator('[data-book]').first()).toBeVisible();

            const stats = page.locator('.hero-stat');
            await expect(stats).toHaveCount(3);
            await expect(stats.locator('.hero-stat__icon')).toHaveCount(3);
            const statLayout = await page.locator('.hero-stat__icon').evaluateAll((nodes) => nodes.map((node) => { const rect = node.getBoundingClientRect(); return { width: rect.width, height: rect.height, top: rect.top, bottom: rect.bottom }; }));
            for (const rect of statLayout) {
                expect(rect.width).toBeGreaterThan(0);
                expect(rect.height).toBeGreaterThan(0);
                expect(rect.top).toBeGreaterThanOrEqual(0);
                expect(rect.bottom).toBeLessThanOrEqual(710);
            }

            const layout = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                document: document.documentElement.scrollWidth,
                persianLetterSpacing: getComputedStyle(document.querySelector('.hero__lede')).letterSpacing,
            }));
            expect(layout.document, `horizontal overflow at ${width}px`).toBeLessThanOrEqual(layout.viewport + 1);
            expect(layout.persianLetterSpacing).toBe('normal');

            await page.screenshot({
                path: `test-results/task16-responsive-${width}.png`,
                fullPage: true,
            });
        });
    }
});
