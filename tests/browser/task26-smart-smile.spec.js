import { test, expect } from '@playwright/test';

const syntheticPng = {
  name: 'synthetic-smile.png',
  mimeType: 'image/png',
  buffer: Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    'base64',
  ),
};

async function openSmartSmile(page) {
  await page.goto('/');
  await page.locator('#smileStart, #smilePreview').first().click();
  await expect(page.locator('#smileModal')).toBeVisible();
  await expect(page.locator('#ssaConsent')).toBeVisible();
}

test('requires explicit consent before selecting an image', async ({ page }) => {
  await openSmartSmile(page);

  await page.locator('#ssaPickImage').click();

  await expect(page.locator('#ssaStatus')).toContainText('ابتدا رضایت');
});

test('processes a synthetic image and shows safe non-diagnostic wording', async ({ page }) => {
  await openSmartSmile(page);

  await page.locator('#ssaConsent').check();
  await page.locator('#ssaFile').setInputFiles(syntheticPng);
  await page.locator('#ssaUseImage').click();

  await expect(page.locator('[data-ssa-stage="result"]')).toHaveClass(/is-now/, { timeout: 10000 });
  await expect(page.locator('#ssaResultText')).toContainText('غیرتشخیصی');
  await expect(page.locator('.ssa-disclaimer')).toContainText('تشخیص پزشکی');
});

test('provides a deletion path for the uploaded image', async ({ page }) => {
  await openSmartSmile(page);

  await page.locator('#ssaConsent').check();
  await page.locator('#ssaFile').setInputFiles(syntheticPng);
  await page.locator('#ssaUseImage').click();

  await expect(page.locator('[data-ssa-stage="result"]')).toHaveClass(/is-now/, { timeout: 10000 });
  await page.locator('#ssaDelete').click();

  await expect(page.locator('#ssaFileName')).toContainText('هیچ تصویری');
});

test('handles a status failure without exposing server errors', async ({ page }) => {
  let intercepted = false;
  await page.route('**/api/v1/smart-smile/status*', async route => {
    if (!intercepted) {
      intercepted = true;
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'internal database secret' }),
      });
      return;
    }
    await route.continue();
  });

  await openSmartSmile(page);
  await page.locator('#ssaConsent').check();
  await page.locator('#ssaFile').setInputFiles(syntheticPng);
  await page.locator('#ssaUseImage').click();

  await expect(page.locator('#ssaStatus')).toContainText('وضعیت پردازش قابل دریافت نیست', { timeout: 10000 });
  await expect(page.locator('#ssaStatus')).not.toContainText('internal database secret');
});

for (const viewport of [
  [375, 812],
  [768, 900],
  [1440, 1000],
]) {
  test(`Smart Smile responsive layout ${viewport[0]}px`, async ({ page }) => {
    await page.setViewportSize({ width: viewport[0], height: viewport[1] });
    await openSmartSmile(page);
    await expect(page.locator('.smile-modal__panel')).toBeVisible();
    await page.locator('.smile-modal__panel').screenshot({
      path: `test-results/task26-smart-smile-${viewport[0]}.png`,
      animations: 'disabled',
    });
  });
}
