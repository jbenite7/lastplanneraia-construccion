import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

// Nota (Task 8, verificado 2026-08-24): el brief traia `PDC Sandbox E2E`,
// mismo proyecto usado por las tareas 4-7. Verificado contra datos reales:
// ese proyecto tiene una sola fila de PI (`Actividad: ''`), asi que no existe
// una "siguiente actividad" a la que saltar y la prueba de recorrido no puede
// pasar ahi con NINGUNA implementacion. `Optimización Aeropuerto JMC` (mismo
// test.R, 39 actividades reales) es el mismo proyecto que la Task 7 ya usaba
// para probar guardado por la misma razon de datos, documentada en
// `tests/browser/pi-globo-guardado.mjs`.
await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Optimización Aeropuerto JMC')}`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

await pagina.locator('.pi-habilitacion-cell').first().click();
await pagina.waitForSelector('.aia-readiness-popover:popover-open');

const titulo = () => pagina.locator('.aia-readiness-popover__titulo').innerText();
const primera = await titulo();

await pagina.locator('.aia-readiness-popover__siguiente').click();
await pagina.waitForTimeout(400);

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'la flecha cerro el globo en vez de saltar');
const segunda = await titulo();
assert.notEqual(primera, segunda, 'el globo no cambio de actividad');
assert.ok(segunda.trim().length > 0, 'el globo quedo en blanco tras saltar');

// El ultimo salto no debe dejar el globo vacio ni saltar a una fila de capitulo.
for (let i = 0; i < 60; i += 1) {
  await pagina.locator('.aia-readiness-popover__siguiente').click();
}
await pagina.waitForTimeout(400);
assert.ok((await titulo()).trim().length > 0,
  'al llegar al final el globo se quedo sin contenido');

console.log('OK: recorre sin cerrarse y no se vacia al final');
await navegador.close();
