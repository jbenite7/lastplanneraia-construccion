/**
 * Biblia de flujos · T3 PDC — escenarios críticos.
 * Cada test() empieza por el `id` del escenario. Ver docs/flujos/README.md.
 * Solo caminos de rechazo: no escriben nada.
 */
import { test, expect } from '@playwright/test';

const PROYECTO = 'Da Porto';
test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

async function entrarComo(page, cuenta) {
  await page.goto(`/dev/entrar?u=${encodeURIComponent(cuenta)}&p=${encodeURIComponent(PROYECTO)}`);
}

async function postComoUsuario(page, url, form) {
  return page.evaluate(async ({ url, form }) => {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(form).toString(),
    });
    return { status: res.status, texto: (await res.text()).slice(0, 300) };
  }, { url, form });
}

test.describe('T3 · Guardias del Plan de Compras', () => {
  test('PDC-001 · una edición sin token CSRF se rechaza', async ({ page }) => {
    await entrarComo(page, 'test.R');
    await page.goto('/plan-compras');
    const r = await postComoUsuario(page, '/api/pdc/save', { opcion: 'modificar', Id: '1' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });

  test('PDC-003 · editar una celda sin token CSRF se rechaza', async ({ page }) => {
    await entrarComo(page, 'test.R');
    await page.goto('/plan-compras');
    const r = await postComoUsuario(page, '/api/pdc/update-cell', { id: '1', prop: 'x', value: 'y' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });

  test('PDC-001 · el Visualizador no puede modificar el plan de compras', async ({ page }) => {
    await entrarComo(page, 'test.V');
    await page.goto('/plan-compras');
    const r = await postComoUsuario(page, '/api/pdc/save', { opcion: 'modificar', Id: '1' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });
});
