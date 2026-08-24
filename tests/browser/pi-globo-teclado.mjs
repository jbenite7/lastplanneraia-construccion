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

await pagina.locator('.pi-habilitacion-cell').first().focus();
await pagina.keyboard.press('Enter');

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'Enter no abrio el globo');
assert.ok(await pagina.evaluate(() =>
  !!document.activeElement.closest('.aia-readiness-popover')),
  'el foco no entro al globo');

await pagina.keyboard.press('Escape');

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 0,
  'Escape no cerro el globo');
assert.ok(await pagina.evaluate(() =>
  document.activeElement.classList.contains('pi-habilitacion-cell')),
  'el foco no volvio a la celda que abrio el globo');

console.log('OK: abre, enfoca, cierra y devuelve el foco');

await pagina.locator('.pi-habilitacion-cell').first().click();
assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'el clic no reabrio el globo');

await pagina.mouse.click(5, 5);

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 0,
  'el clic afuera no cerro el globo');
assert.ok(await pagina.evaluate(() =>
  document.activeElement.classList.contains('pi-habilitacion-cell')),
  'el foco no volvio a la celda tras el clic afuera');

console.log('OK: el clic afuera cierra el globo y devuelve el foco');
await navegador.close();
