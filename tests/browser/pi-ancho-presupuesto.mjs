import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
// 1180, no 1100: es el viewport desktop canonico de AGENTS.md y el que evita
// UMBRAL_CARDS=1180 (public/js/modules/aia_ui/view-switch.js), que a 1100px
// deja la app en modo tarjetas moviles y Handsontable nunca se instancia.
// La condicion de hecho de la spec (2026-08-20-habilitacion-en-una-columna-design.md,
// linea 201) pide "no desborda a 1180x820"; el 1100 de la spec solo fija el
// presupuesto aritmetico de columnas, no el viewport de navegador.
const ANCHO = 1180;

const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: ANCHO, height: 820 },
})).newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('#hot-container .handsontable', { timeout: 30000 });
await pagina.waitForTimeout(2500);

const medida = await pagina.evaluate(() => {
  const holder = document.querySelector('#hot-container .ht_master .wtHolder');
  return { scroll: holder.scrollWidth, cliente: holder.clientWidth };
});

assert.ok(medida.scroll <= medida.cliente,
  `la tabla desborda: ${medida.scroll} px de contenido en ${medida.cliente} px de caja`);

const escondidas = await pagina.evaluate(() => {
  const celdas = [...document.querySelectorAll('#hot-container td, #hot-container th')];
  return celdas.filter((c) => c.scrollWidth > c.clientWidth + 1
    || c.scrollHeight > c.clientHeight + 1).length;
});

assert.equal(escondidas, 0, `${escondidas} celdas esconden su contenido`);

console.log(`OK: ${medida.scroll} px en ${medida.cliente}, 0 celdas recortadas`);
await navegador.close();
