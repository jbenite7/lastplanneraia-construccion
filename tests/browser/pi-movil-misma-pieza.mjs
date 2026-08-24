import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 390, height: 844 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-mobile-card', { timeout: 30000 });

await pagina.locator('.pi-mobile-card details').first().click();
await pagina.waitForTimeout(400);

const orden = await pagina.evaluate(() =>
  [...document.querySelectorAll('.pi-mobile-card .aia-readiness__box')]
    .map((b) => b.getAttribute('data-restriccion')));

assert.ok(orden.length > 0,
  'la tarjeta movil no usa la primitiva compartida: no hay ningun .aia-readiness__box');
assert.deepEqual(orden, [...orden].filter(Boolean),
  'algun cuadrito de la tarjeta no declara su restriccion');

console.log(`OK: la tarjeta usa la misma pieza, ${orden.length} cuadritos en orden`);
await navegador.close();
