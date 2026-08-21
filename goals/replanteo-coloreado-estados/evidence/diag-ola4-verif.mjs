// Sonda de verificacion de la ola 4 (2026-08-20).
// Mide en pantalla lo que el usuario reporto: alto de fila, texto cortado,
// desborde horizontal y el respiro entre la barra superior y el cajon de acciones.
// No arregla nada: solo reporta el numero medido.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch();
const p = await (await b.newContext({ viewport: { width: 1180, height: 820 } })).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card = p.locator('.project-item').filter({ has: p.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u => !u.toString().includes('/proyectos'), { timeout: 45000 });
await p.evaluate(() => localStorage.setItem('aia-theme', 'dark'));

const salida = {};
for (const [ruta, nom] of [['/programa-general', 'PG'], ['/programacion-intermedia', 'PI'], ['/programacion-semanal', 'PS']]) {
  await p.goto(`${BASE}${ruta}`, { waitUntil: 'domcontentloaded' });
  await p.waitForTimeout(3000);
  salida[nom] = await p.evaluate(() => {
    const q = (s) => document.querySelector(s);
    const tb = q('.aia-toolbar');
    const nav = q('.aia-navbar, .navbar, header, .aia-page-header');
    const holder = q('#hot-container .wtHolder');
    const th = q('#hot-container .ht_master thead th');
    const td = q('#hot-container .ht_master tbody td');
    // Texto cortado: en Handsontable el texto vive en el td o en un hijo.
    // Si scrollHeight supera clientHeight hay contenido que no se ve.
    const celdas = [...document.querySelectorAll('#hot-container .ht_master tbody td')].slice(0, 400);
    const cortadas = celdas
      .map((c, i) => ({ i, sh: c.scrollHeight, ch: c.clientHeight, sw: c.scrollWidth, cw: c.clientWidth, txt: (c.innerText || '').slice(0, 40) }))
      .filter(x => x.sh > x.ch + 1);
    return {
      thAlto: th ? +th.getBoundingClientRect().height.toFixed(1) : null,
      tdAlto: td ? +td.getBoundingClientRect().height.toFixed(1) : null,
      thFont: th ? getComputedStyle(th).fontSize : null,
      tdFont: td ? getComputedStyle(td).fontSize : null,
      celdasRevisadas: celdas.length,
      celdasCortadas: cortadas.length,
      muestraCortadas: cortadas.slice(0, 5),
      desbordeX: holder ? { scrollW: holder.scrollWidth, clientW: holder.clientWidth, exceso: holder.scrollWidth - holder.clientWidth } : null,
      respiroNavToolbar: (tb && nav) ? +(tb.getBoundingClientRect().top - nav.getBoundingClientRect().bottom).toFixed(1) : null,
      marginTopToolbar: tb ? getComputedStyle(tb).marginTop : null,
      navClase: nav ? nav.className.slice(0, 40) : null
    };
  });
  console.log('\n=== ' + nom + ' ===\n' + JSON.stringify(salida[nom], null, 1));
}
await b.close();
