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

test('attachHtEmptyState es idempotente: llamarla dos veces no duplica hooks ni paneles', async ({ page }) => {
  // No visita /programacion-semanal: monta el componente real sobre una instancia de
  // Handsontable simulada, en una pagina neutra que no dispara la auto-mutacion conocida.
  await page.goto(`${BASE_URL}/login`);
  const result = await page.evaluate(async (baseUrl) => {
    const mod = await import(`${baseUrl}/js/design-system/ht-empty-state.js`);
    const rootElement = document.createElement('div');
    document.body.appendChild(rootElement);
    const hookCounts = {};
    const fakeHot = {
      rootElement,
      countRows: () => 0,
      addHook: (name) => {
        hookCounts[name] = (hookCounts[name] || 0) + 1;
      },
    };
    mod.attachHtEmptyState(fakeHot, { titulo: 'uno', cuerpo: 'primero' });
    mod.attachHtEmptyState(fakeHot, { titulo: 'dos', cuerpo: 'segundo' });
    return {
      panelCount: rootElement.querySelectorAll('.ht-empty-state').length,
      hookCounts,
      titulo: rootElement.querySelector('.ht-empty-state__titulo').textContent,
      cuerpo: rootElement.querySelector('.ht-empty-state__cuerpo').textContent,
    };
  }, BASE_URL);

  expect(result.panelCount).toBe(1);
  expect(result.hookCounts).toEqual({
    afterLoadData: 1,
    afterChange: 1,
    afterRemoveRow: 1,
    afterCreateRow: 1,
  });
  // Los textos si se actualizan en cada llamada, aunque los hooks no se re-registren.
  expect(result.titulo).toBe('dos');
  expect(result.cuerpo).toBe('segundo');
});
