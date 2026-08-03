import { test, expect } from '@playwright/test';

test('los KPI del control tower no muestran la unidad cruda "count"', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8091/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8091/bi/control-tower');
  await page.waitForLoadState('networkidle');
  const texto = await page.locator('body').innerText();
  expect(texto).not.toMatch(/\bcount\b/i);
});
