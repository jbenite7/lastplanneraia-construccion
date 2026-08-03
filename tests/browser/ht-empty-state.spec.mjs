import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';

test('la malla semanal vacia explica que falta y como se crea', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/programacion-semanal`);
  await page.waitForLoadState('networkidle');
  const panel = page.locator('.ht-empty-state:visible');
  await expect(panel).toHaveCount(1);
  await expect(panel).toContainText(/actividad/i);

  const box = await panel.boundingBox();
  expect(box).not.toBeNull();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.x + box.width).toBeLessThanOrEqual(1180);
});
