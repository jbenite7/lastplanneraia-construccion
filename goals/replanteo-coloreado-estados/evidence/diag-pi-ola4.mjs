// Sonda de la ola 4 para Programacion Intermedia: alto de fila, desbordamiento
// horizontal (anchos columna a columna) y respiro entre la barra superior y el
// cajon de acciones, midiendo PG como referencia.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch();
const p = await (await b.newContext({ viewport: { width: 1180, height: 820 } })).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card = p.locator('.project-item').filter({ has: p.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u => !u.toString().includes('/proyectos'), { timeout: 45000 });
await p.evaluate(() => localStorage.setItem('aia-theme', 'dark'));

for (const [ruta, nom] of [['/programa-general', 'PG'], ['/programacion-intermedia', 'PI'], ['/programacion-semanal', 'PS']]) {
  await p.goto(`${BASE}${ruta}`, { waitUntil: 'domcontentloaded' });
  await p.waitForTimeout(3000);
  const d = await p.evaluate(() => {
    const q = s => document.querySelector(s);
    const cont = q('#hot-container');
    const holder = q('#hot-container .ht_master .wtHolder') || q('#hot-container .wtHolder');
    const tabla = q('#hot-container .ht_master table.htCore');
    const th = q('#hot-container .ht_master thead th');
    const tds = [...document.querySelectorAll('#hot-container .ht_master tbody tr td')];
    // celda cortada: el contenido no cabe en la caja
    const cortadas = tds.filter(td => td.scrollHeight > td.clientHeight + 1).length;
    const altos = [...new Set(tds.slice(0, 200).map(td => Math.round(td.getBoundingClientRect().height)))];
    // anchos por columna del render real
    const anchos = [...document.querySelectorAll('#hot-container .ht_master thead th')]
      .map(x => Math.round(x.getBoundingClientRect().width));
    // respiro: barra superior -> primer cajon de acciones
    const nav = q('.aia-navbar, .navbar, header');
    const tb = q('.aia-toolbar');
    const rn = nav && nav.getBoundingClientRect();
    const rt = tb && tb.getBoundingClientRect();
    const rowH = getComputedStyle(document.documentElement).getPropertyValue('--ds-table-row-h').trim();
    return {
      contW: cont ? Math.round(cont.clientWidth) : null,
      holderClientW: holder ? holder.clientWidth : null,
      holderScrollW: holder ? holder.scrollWidth : null,
      tablaW: tabla ? Math.round(tabla.getBoundingClientRect().width) : null,
      desborde: holder ? holder.scrollWidth - holder.clientWidth : null,
      nCols: anchos.length, anchos, sumaAnchos: anchos.reduce((a, x) => a + x, 0),
      thAlto: th ? Math.round(th.getBoundingClientRect().height) : null,
      tdAltos: altos, celdasCortadas: cortadas, totalCeldas: tds.length,
      respiro: (rn && rt) ? Math.round(rt.top - rn.bottom) : null,
      toolbarClass: tb ? tb.className : null,
      toolbarMarginTop: tb ? getComputedStyle(tb).marginTop : null,
      toolbarPadTop: tb ? getComputedStyle(tb).paddingTop : null,
      padreToolbar: tb && tb.parentElement ? (tb.parentElement.className + ' | mt=' + getComputedStyle(tb.parentElement).marginTop + ' pt=' + getComputedStyle(tb.parentElement).paddingTop) : null,
      tokenRowH: rowH,
    };
  });
  console.log('\n=== ' + nom + ' ===', JSON.stringify(d, null, 1));
}
await b.close();
