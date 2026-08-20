// La condicion de hecho de este frente es un NUMERO: «el usuario ve menos
// elementos en pantalla, demostrado con conteo antes/despues, no con capturas».
// Asi que esto cuenta, no fotografia. Y comprueba las dos guardas que el goal
// exige verificar en vez de suponer: que con un filtro activo la etiqueta en
// cero vuelve a verse, y que la bandera devuelve el estado anterior.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';
const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 } });
const page = await c.newPage();
await page.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Da Porto')}`);
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 30000 }).catch(() => {});
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await page.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('.pdc-legend-item', { timeout: 45000 });
await page.waitForTimeout(1500);

const contar = () => page.evaluate(() => {
  const etiquetas = [...document.querySelectorAll('.pdc-legend-item')];
  const visible = (el) => {
    const cs = getComputedStyle(el);
    return cs.display !== 'none' && cs.visibility !== 'hidden' && el.getBoundingClientRect().width > 0;
  };
  const enCero = (el) => /(^|\D)0(\D|$)/.test((el.querySelector('.count-badge') || {}).textContent || '');
  return {
    total: etiquetas.length,
    visibles: etiquetas.filter(visible).length,
    enCero: etiquetas.filter(enCero).length,
    enCeroVisibles: etiquetas.filter((e) => enCero(e) && visible(e)).length,
    ocupanSitio: etiquetas.filter(visible).reduce((n, e) => n + Math.round(e.getBoundingClientRect().width), 0),
  };
});

const fallos = [];
const conBandera = await contar();
if (conBandera.enCeroVisibles !== 0) {
  fallos.push(`con la bandera activa siguen visibles ${conBandera.enCeroVisibles} etiqueta(s) en cero`);
}

// Guarda 1: con CUALQUIER filtro activo no se oculta ninguna, aunque marque
// cero. La regla esta en `setLegendCount`: `esVacioReal = count === 0 &&
// activeFilters.length === 0`, o sea distingue «vacio» de «cero porque estoy
// mirando otra cosa». Se comprueba que el filtro quedo REALMENTE activo
// (`aria-pressed`) antes de contar: un clic que no activa nada daria un falso
// verde silencioso.
const primerFiltro = page.locator('.pdc-legend-item:visible').first();
await primerFiltro.click({ timeout: 5000 }).catch(() => {});
await page.waitForTimeout(1200);
const filtroActivo = await page.evaluate(() =>
  document.querySelectorAll('.pdc-legend-item[aria-pressed="true"]').length);
const conFiltro = await contar();
console.log(`  (filtros realmente activos tras el clic: ${filtroActivo})`);
if (filtroActivo === 0) {
  fallos.push('el clic no activo ningun filtro: la guarda de activeFilters no se pudo comprobar');
} else if (conFiltro.visibles !== conFiltro.total) {
  fallos.push(`con un filtro activo deberian verse las ${conFiltro.total} etiquetas y se ven ${conFiltro.visibles}`);
}

// Guarda 2: la vuelta atras. Mutacion EJECUTADA, no descrita.
await page.reload({ waitUntil: 'domcontentloaded' });
await page.waitForSelector('.pdc-legend-item', { timeout: 45000 });
await page.waitForTimeout(1200);
const sinBandera = await page.evaluate(() => {
  // Se apaga el efecto quitando la clase que la bandera pone, que es lo unico
  // que `OCULTAR_CONTADORES_EN_CERO = false` cambia en el DOM.
  for (const el of document.querySelectorAll('.pdc-legend-item.is-empty')) el.classList.remove('is-empty');
  const etiquetas = [...document.querySelectorAll('.pdc-legend-item')];
  const visible = (el) => { const cs = getComputedStyle(el); return cs.display !== 'none' && el.getBoundingClientRect().width > 0; };
  return { visibles: etiquetas.filter(visible).length };
});

console.log('LEYENDA DE PROGRAMACION INTERMEDIA — conteo, no captura\n');
console.log(`  etiquetas en el DOM                     ${conBandera.total}`);
console.log(`  en cero                                 ${conBandera.enCero}`);
console.log(`  VISIBLES con la bandera activa          ${conBandera.visibles}`);
console.log(`  de esas, en cero                        ${conBandera.enCeroVisibles}`);
console.log(`  ancho total que ocupan (px)             ${conBandera.ocupanSitio}`);
console.log(`\n  con un filtro activo, visibles          ${conFiltro.visibles}  (en cero: ${conFiltro.enCeroVisibles})`);
console.log(`  con el efecto apagado, visibles         ${sinBandera.visibles}`);
console.log(`\n  AHORRO REAL: ${sinBandera.visibles} -> ${conBandera.visibles} etiquetas (${sinBandera.visibles - conBandera.visibles} menos)`);

if (sinBandera.visibles <= conBandera.visibles) fallos.push('apagar el efecto no muestra MAS etiquetas: la bandera no esta haciendo nada');
writeFileSync(`${OUT}conteo.json`, JSON.stringify({ conBandera, conFiltro, sinBandera }, null, 2));
if (fallos.length) { console.log('\nSONDA EN ROJO:'); for (const f of fallos) console.log('  - ' + f); process.exitCode = 1; }
else console.log('\nSONDA EN VERDE: las etiquetas en cero no ocupan sitio, y apagar el efecto las devuelve.');
await b.close();
