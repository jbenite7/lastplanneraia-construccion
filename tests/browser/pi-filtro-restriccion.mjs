import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

const contarFilas = () => pagina.locator('#hot-container .ht_master tbody tr').count();
const antes = await contarFilas();

await pagina.locator('.pi-habilitacion-filtro').click();
await pagina.locator('.pi-habilitacion-filtro__opcion[data-restriccion="Materiales"]').click();
await pagina.waitForTimeout(600);

const despues = await contarFilas();
assert.ok(despues < antes,
  `el filtro no redujo las filas: ${antes} antes, ${despues} despues`);

const todasPendientes = await pagina.evaluate(() =>
  [...document.querySelectorAll('.pi-habilitacion-cell')].every((celda) => {
    const caja = celda.querySelector('[data-restriccion="Materiales"]');
    return !caja || !caja.classList.contains('aia-readiness__box--met');
  }));
assert.ok(todasPendientes,
  'quedaron filas con esa restriccion ya liberada');

console.log(`OK: ${antes} -> ${despues} filas, todas con la restriccion pendiente`);
await navegador.close();
