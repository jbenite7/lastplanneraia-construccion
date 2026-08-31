import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { bootstrapAutenticado } from './fixtures/shell-runtime-react.mjs';

/**
 * Auditoría axe del shell React (Tarea 8, T01 §14/§19 T01-AC-10): cero hallazgos serios/críticos
 * en claro y oscuro, en los cuatro viewports del contrato. Red completamente interceptada antes
 * de navegar — nunca toca el backend real.
 *
 * A diferencia de `design-system-lab.a11y.mjs`, este spec NO usa
 * `tests/browser/support/accessibility.mjs` (gobernanza de baseline/excepciones atada a
 * `docs/design-system/homologation.json`, pensada para las familias del laboratorio de diseño):
 * el shell React todavía no es una "familia" homologada ahí, y forzar esa integración sería
 * ampliar un contrato ajeno a esta tarea. Aquí el axe scan es autocontenido: cero
 * serio/crítico, sin excepciones.
 */

const ESCENARIOS = [
  { viewport: '390×844', width: 390, height: 844 },
  { viewport: '768×1024', width: 768, height: 1024 },
  { viewport: '1180×820', width: 1180, height: 820 },
  { viewport: '1440×900', width: 1440, height: 900 },
];

async function interceptarYEntrar(page, tema) {
  await page.addInitScript((t) => {
    try { window.localStorage.setItem('aia-theme', t); } catch { /* modo privado */ }
  }, tema);
  await page.route('**/api/**', (route) => route.abort('failed'));
  await page.route('**/api/session', (route) => (
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(bootstrapAutenticado()) })
  ));
  await page.route('**/session/touch', (route) => (
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, timestamp: Date.now(), timeoutSeconds: 3600 }) })
  ));
  await page.goto('/app');
  await expect(page.getByRole('navigation')).toBeVisible();
}

function bloqueantes(results) {
  return (results.violations || []).filter((v) => v.impact === 'serious' || v.impact === 'critical');
}

for (const escenario of ESCENARIOS) {
  for (const tema of ['dark', 'light']) {
    test(`[${escenario.viewport} · ${tema}] axe serio/crítico en cero`, async ({ page }) => {
      await page.setViewportSize({ width: escenario.width, height: escenario.height });
      await interceptarYEntrar(page, tema);
      await expect(page.locator('html')).toHaveAttribute('data-aia-theme', tema);

      const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
      const hallazgos = bloqueantes(results);

      expect(hallazgos, JSON.stringify(hallazgos, null, 2)).toEqual([]);
    });
  }
}

test('axe serio/crítico en cero con el drawer móvil abierto (390×844, oscuro)', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await interceptarYEntrar(page, 'dark');

  await page.getByRole('button', { name: /abrir menú de navegación/i }).click();
  await expect(page.getByRole('navigation').locator('xpath=ancestor::aside')).toHaveAttribute('data-shell-drawer-open', 'true');

  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
  const hallazgos = bloqueantes(results);

  expect(hallazgos, JSON.stringify(hallazgos, null, 2)).toEqual([]);
});
