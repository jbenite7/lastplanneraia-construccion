/**
 * Sostiene el ahorro de E4 (spec 2026-08-07-f2a-piloto-movil-programacion-design.md):
 * bajo el umbral, la grilla de Handsontable no debe instanciarse — solo las cards.
 */
import { test, expect } from '@playwright/test';
import { login } from './support/session.mjs';

const PROJECT_CANDIDATES = ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto', 'Prueba'];

async function abrir(page, ruta) {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  for (const name of PROJECT_CANDIDATES) {
    const card = page.locator('.project-item').filter({
      has: page.getByRole('heading', { name, exact: true }),
    });
    if (await card.count()) {
      await card.locator('button[type="submit"], .btn-enter').click();
      await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
      break;
    }
  }
  await page.goto(ruta);
  await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
}

for (const [modulo, ruta] of [['semanal', '/programacion-semanal'], ['intermedia', '/programacion-intermedia']]) {
  test(`${modulo}: en 390x844 no existe ni un nodo de Handsontable`, async ({ page }) => {
    await abrir(page, ruta);
    const nodos = await page.locator('#hot-container .handsontable').count();
    expect(nodos, 'Handsontable se montó bajo el umbral').toBe(0);
    const cards = await page.locator('#mobile-card-view').count();
    expect(cards, 'Sin grilla y sin cards, la vista quedaría vacía').toBeGreaterThan(0);
  });
}
