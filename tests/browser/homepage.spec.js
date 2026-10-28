import { test, expect } from '@playwright/test';

test.describe('Kouhfar Dental public homepage', () => {
    test('renders the homepage correctly', async ({ page }) => {
        await page.goto('/');

        await expect(page).toHaveTitle(/کلینیک دندانپزشکی دیجیتال/);
        await expect(page.locator('html')).toHaveAttribute('lang', 'fa');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

        await expect(page.locator('h1')).toContainText('درمان دقیق،');
        await expect(page.locator('h1')).toContainText('زیبایی طبیعی');
    });

    test('booking dialog opens and can be closed', async ({ page }) => {
        await page.goto('/');

        await page.locator('[data-book]').first().click();

        await expect(page.locator('#bk')).toBeVisible();

        await page.locator('#bkClose').click();

        await expect(page.locator('#bk')).toBeHidden();
    });

    test('AI assistant opens and can be closed', async ({ page }) => {
        await page.goto('/');

        await page.locator('#fab').click();

        await expect(page.locator('#ai')).toHaveClass(/is-open/);

        await page.locator('#aiClose').click();

        await expect(page.locator('#ai')).not.toHaveClass(/is-open/);
    });

    test('video modal opens and can be closed', async ({ page }) => {
        await page.goto('/');

        await page.locator('#heroVideoButton').click();

        await expect(page.locator('#heroVideoModal')).toHaveClass(/is-open/);
        await expect(page.locator('#heroVideoModal')).toBeVisible();

        await page.locator('#heroVideoClose').click();

        await expect(page.locator('#heroVideoModal')).not.toHaveClass(/is-open/);
        await expect(page.locator('#heroVideoModal')).toBeHidden();
    });
});
