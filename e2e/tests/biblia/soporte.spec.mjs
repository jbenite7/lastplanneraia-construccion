/**
 * Biblia de flujos · T4 soporte — escenarios críticos.
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
    return { status: res.status };
  }, { url, form });
}

test.describe('T4 · Permisos de los módulos de soporte', () => {
  test('SOP-001 · el Visualizador no puede editar subcontratistas', async ({ page }) => {
    await entrarComo(page, 'test.V');
    await page.goto('/subcontratistas');
    const r = await postComoUsuario(page, '/api/subcontratistas/save', { opcion: 'modificar', Id: '1' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });

  test('SOP-001 · el Visualizador no puede editar el control de cambios', async ({ page }) => {
    await entrarComo(page, 'test.V');
    await page.goto('/control-cambios');
    const r = await postComoUsuario(page, '/api/control-cambios/save', { opcion: 'modificar', Id: '1' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });

  test('SOP-005 · el Visualizador no puede registrar una crisis', async ({ page }) => {
    await entrarComo(page, 'test.V');
    const r = await postComoUsuario(page, '/api/lps/crisis/register', { consecutivo: '1', modulo: 'PS' });
    expect(r.status).toBeGreaterThanOrEqual(400);
  });

  test('SOP-005 · registrar crisis rechaza un módulo fuera de PG/PI/PS', async ({ page }) => {
    await entrarComo(page, 'test.R');
    const r = await page.evaluate(async () => {
      const res = await fetch('/api/lps/crisis/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ consecutivo: '1', modulo: 'SOP' }).toString(),
      });
      return { status: res.status, body: await res.json() };
    });
    // El endpoint responde 200 con respuesta:"ERROR" en vez de un código HTTP de
    // rechazo — es el patrón de todo LpsApiController, no un fallo de esta prueba.
    expect(r.status).toBe(200);
    expect(r.body.respuesta).toBe('ERROR');
  });
});
