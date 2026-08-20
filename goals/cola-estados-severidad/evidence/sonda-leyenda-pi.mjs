// Sonda de la leyenda de Programacion Intermedia. Mide el color COMPUTADO de
// cada muestrario del modal «Guia Operativa», que es donde vivia un hex claro
// embebido (#fef3c7) sobre tema oscuro.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;
const etiqueta = process.argv[2] || 'actual';

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 }, deviceScaleFactor: 1 });
const page = await c.newPage();
await page.goto(`${BASE}/dev/entrar?u=test.R`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'));
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await page.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('.leyenda_colores', { timeout: 45000 });
await page.click('.leyenda_colores');
await page.waitForSelector('#modal_leyenda_colores_body .pi-legend-quick-row', { timeout: 20000 });
await page.waitForTimeout(900);

const datos = await page.evaluate(() => {
  const hex = (rgb) => {
    const m = rgb.match(/\d+/g);
    return m ? '#' + m.slice(0, 3).map((n) => Number(n).toString(16).padStart(2, '0')).join('') : rgb;
  };
  // Luminancia relativa: lo que decide si una muestra «salta» sobre tema oscuro.
  const lum = (rgb) => {
    const m = (rgb.match(/\d+/g) || [0, 0, 0]).slice(0, 3).map(Number);
    const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    return 0.2126 * f(m[0]) + 0.7152 * f(m[1]) + 0.0722 * f(m[2]);
  };
  return [...document.querySelectorAll('#modal_leyenda_colores_body .pi-legend-quick-row')].map((row) => {
    const sw = row.querySelector('.pi-legend-modal-swatch');
    const cs = sw ? getComputedStyle(sw) : null;
    return {
      estado: (row.querySelector('.pi-legend-quick-state strong') || {}).textContent || '(sin nombre)',
      clase: sw ? (sw.className.match(/pi-state-[\w-]+/) || ['(sin clase de estado)'])[0] : '-',
      fondo: cs ? hex(cs.backgroundColor) : '-',
      borde: cs ? hex(cs.borderTopColor) : '-',
      estiloBorde: cs ? cs.borderTopStyle : '-',
      luminancia: cs ? Number(lum(cs.backgroundColor).toFixed(4)) : null,
    };
  });
});

const fondoPagina = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
console.log(`LEYENDA PI (${etiqueta}) — ${datos.length} muestrarios · fondo de pagina ${fondoPagina}`);
console.log('estado'.padEnd(34) + 'clase'.padEnd(34) + 'fondo'.padEnd(10) + 'borde'.padEnd(10) + 'lum');
for (const d of datos) {
  console.log(String(d.estado).slice(0, 33).padEnd(34) + String(d.clase).slice(0, 33).padEnd(34) +
              String(d.fondo).padEnd(10) + String(d.borde).padEnd(10) + String(d.luminancia));
}
const masClaro = datos.reduce((a, d) => (d.luminancia > (a?.luminancia ?? -1) ? d : a), null);
console.log(`\nEl mas claro: «${masClaro.estado}» con luminancia ${masClaro.luminancia}`);
writeFileSync(`${OUT}leyenda-pi-${etiqueta}.json`, JSON.stringify(datos, null, 2));
await page.screenshot({ path: `${OUT}leyenda-pi-${etiqueta}-1180x820-dark.png` });
await b.close();
