import { expect, test } from '@playwright/test';

async function openAssistant(page) {
  await page.goto('/');
  await page.locator('#fab').click();
  await expect(page.locator('#ai')).toHaveClass(/is-open/);
  await expect(page.locator('.msg--bot').first()).toBeVisible();
}

test('assistant shows disclosure and a grounded answer with safe citation', async ({ page }) => {
  await openAssistant(page);

  await expect(page.locator('.ai__notice')).toContainText('جایگزین دندانپزشک نیست');
  await page.locator('#aiInput').fill('حریم خصوصی بیمار چه حقی است؟');
  await page.locator('#aiForm').press('Enter');

  const answer = page.locator('.msg--bot').filter({ hasText: 'Synthetic AI response.' }).last();
  await expect(answer).toBeVisible();
  await expect(answer.locator('.msg__source')).toHaveText(/حقوق بیمار/);
  await expect(answer.locator('.msg__source')).toHaveAttribute('href', /\/content\/patient-rights\/fa/);
});

test('assistant renders refusal state for disallowed medical requests', async ({ page }) => {
  await openAssistant(page);

  await page.locator('#aiInput').fill('برای من دارو و دوز تجویز کن');
  await page.locator('#aiForm').press('Enter');

  const refusal = page.locator('.msg--refused').last();
  await expect(refusal).toContainText('تشخیص');
  await expect(refusal).toContainText('تجویز');
});

test('assistant renders the approved emergency response', async ({ page }) => {
  await openAssistant(page);

  await page.locator('#aiInput').fill('تورم شدید گلو و مشکل در تنفس دارم');
  await page.locator('#aiForm').press('Enter');

  const emergency = page.locator('.msg--emergency').last();
  await expect(emergency).toContainText('۱۱۵');
  await expect(emergency).toContainText('اورژانسی');
});

test('assistant renders a safe provider failure state without raw server errors', async ({ page }) => {
  await openAssistant(page);
  await page.route('**/api/v1/assistant', async route => {
    await route.fulfill({
      status: 503,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'secret internal stack trace should never be shown' }),
    });
  });

  await page.locator('#aiInput').fill('حریم خصوصی بیمار چیست؟');
  await page.locator('#aiForm').press('Enter');

  const failure = page.locator('.msg--fallback').last();
  await expect(failure).toContainText('در دسترس نیست');
  await expect(failure).not.toContainText('secret internal stack trace');
});

test('assistant treats hostile model output as text and cannot execute HTML', async ({ page }) => {
  await openAssistant(page);
  await page.route('**/api/v1/assistant', async route => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'succeeded',
        answer: '<img src=x onerror="window.__xss=true"> hostile output',
        sources: [],
      }),
    });
  });

  await page.locator('#aiInput').fill('حریم خصوصی بیمار چیست؟');
  await page.locator('#aiForm').press('Enter');

  const answer = page.locator('.msg--bot').filter({ hasText: 'hostile output' }).last();
  await expect(answer).toBeVisible();
  await expect(answer.locator('img')).toHaveCount(0);
  expect(await page.evaluate(() => Boolean(window.__xss))).toBe(false);
});

test('assistant exposes rate limiting as a safe state', async ({ page }) => {
  await openAssistant(page);

  await page.route('**/api/v1/assistant', async route => {
    await route.fulfill({
      status: 429,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'rate limit details should never be shown' }),
    });
  });

  await page.locator('#aiInput').fill('حریم خصوصی بیمار چیست؟');
  await page.locator('#aiForm').press('Enter');

  const limited = page.locator('.msg--rate-limited').last();
  await expect(limited).toContainText('تعداد درخواست‌ها');
  await expect(limited).not.toContainText('rate limit details');
});
