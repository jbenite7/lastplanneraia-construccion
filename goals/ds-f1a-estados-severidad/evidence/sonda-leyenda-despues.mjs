// Tercera sonda: la LEYENDA (Guia Operativa) es donde el usuario lee la escala.
// Mide el color computado de los ocho muestrarios y captura el modal a escala
// real (deviceScaleFactor 1, sin reescalado: la trampa de `severidad-runtime`).
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';
const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;
const FILAS = [
  { unique_id: 101, Id: 101, Titulo: 0, Actividad: 'Pilotaje eje A', Sub_Contratista: 'X', Responsable_AIA: 'L. Marin', Semanas_Inicio: -2, Ejecutado: 0, D_y_E: '100%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '50%', Modelo: 'N/A', Ruta_Critica: '1', alerta_crisis: 0, Observaciones: '' },
];
const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 }, deviceScaleFactor: 1 });
const page = await c.newPage();
await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":false}' }));
await page.route('**/programacion-intermedia/filtros', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":true,"data":{}}' }));
await page.route('**/api/pi/list**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }) }));
await page.goto(`${BASE}/dev/entrar?u=test.R`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'));
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await page.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
await page.locator('button.leyenda_colores').click();
await page.waitForFunction(() => document.querySelectorAll('#modal_leyenda_colores_body .pi-legend-modal-swatch').length >= 8, null, { timeout: 20000 });
await page.waitForTimeout(600);

const muestras = await page.evaluate(() => {
  const cv = document.createElement('canvas'); cv.width = 1; cv.height = 1;
  const ctx = cv.getContext('2d', { willReadFrequently: true });
  const hex = (v) => { ctx.globalCompositeOperation = 'copy'; ctx.fillStyle = '#000'; ctx.fillStyle = v; ctx.fillRect(0, 0, 1, 1); const d = ctx.getImageData(0, 0, 1, 1).data; return '#' + [d[0], d[1], d[2]].map((x) => x.toString(16).padStart(2, '0')).join(''); };
  return [...document.querySelectorAll('#modal_leyenda_colores_body .pi-legend-modal-swatch')].map((s) => ({
    clase: (s.className.match(/pi-state-[\w-]+/) || ['(sin estado)'])[0],
    etiqueta: (s.parentElement.querySelector('.pi-legend-quick-state strong') || {}).textContent || null,
    fondo: hex(getComputedStyle(s).backgroundColor),
  }));
});
console.log(JSON.stringify(muestras, null, 2));
const unicos = new Set(muestras.map((m) => m.fondo));
console.log(`\n${muestras.length} entradas de leyenda -> ${unicos.size} colores distintos:`, [...unicos].join(' '));
writeFileSync(`${OUT}medicion-leyenda.json`, JSON.stringify({ muestras, coloresDistintos: [...unicos] }, null, 2));
await page.locator('#modal_leyenda_colores .modal-dialog').screenshot({ path: `${OUT}leyenda-guia-operativa-1180x820-dark.png` });
await b.close();
