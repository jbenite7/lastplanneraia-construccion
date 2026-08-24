import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({ viewport: { width: 1180, height: 820 } })).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Optimización Aeropuerto JMC')}`, { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

const trigger = pagina.locator('.ht_clone_top .pi-help-trigger').first();
await trigger.waitFor({ state: 'visible', timeout: 10000 });
await trigger.click({ force: true }); // un tooltip previo de otra columna puede quedar visible al recorrer TH con teclado/mouse
await pagina.waitForTimeout(400);

const contenido = await pagina.evaluate(() => {
  const tip = document.querySelector('.pi-help-tooltip .tooltip-inner');
  return tip ? tip.textContent : null;
});
console.log('contenido tooltip (primeros 200 chars):', (contenido || '').slice(0, 200));
assert.ok(contenido, 'no aparecio el tooltip de ayuda');
assert.match(contenido, /Diseño|diseños|Materiales/i, 'el tooltip no trae contenido de restricciones');

const encabezados = await pagina.evaluate(() =>
  document.querySelectorAll('.pi-help-tooltip .tooltip-inner h6').length);
console.log('cantidad de encabezados de restriccion en el tooltip:', encabezados);
assert.ok(encabezados >= 5, 'el tooltip no trae varias restricciones concatenadas');

console.log('OK: el globo (?) educativo por restriccion vive en la cabecera de Habilitacion');
await navegador.close();
