// Diagnostico PS (ola 4): alto real de la fila, de donde sale, y el respiro
// entre la barra superior y el cajon de acciones. Se recorre desde Max_Semana
// hacia atras hasta dar con una semana con filas: sin filas no hay que medir.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch();
const ctx = await b.newContext({ viewport: { width: 1180, height: 820 } });
const p = await ctx.newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
for (const nombre of ['Preconstrucción Da Porto', 'Optimización Aeropuerto JMC', 'Da Porto']) {
  const card = p.locator('.project-item').filter({ has: p.getByRole('heading', { name: nombre, exact: true }) });
  if (await card.count()) { await card.locator('button[type="submit"], .btn-enter').first().click(); break; }
}
await p.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });
await p.goto(`${BASE}/programacion-semanal`, { waitUntil: 'domcontentloaded' });
await p.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
const max = Number(await p.locator('#Max_Semana').inputValue());
let filas = 0;
for (let s = max; s >= 1 && filas === 0; s -= 1) {
  await p.evaluate(async (semana) => {
    await fetch('/context/week', { method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ semana }) });
    window.location.href = '/programacion-semanal';
  }, s);
  await p.waitForTimeout(3500);
  filas = await p.evaluate(() => document.querySelectorAll('#hot-container .ht_master .htCore tbody tr').length);
  if (filas) console.log(`semana ${s}: ${filas} filas`);
}
console.log(JSON.stringify(await p.evaluate(() => {
  const out = {};
  const trs = [...document.querySelectorAll('#hot-container .ht_master .htCore tbody tr')].slice(0, 5);
  out.filas = trs.map((tr) => {
    const td = tr.querySelector('td'); const cs = td ? getComputedStyle(td) : null;
    return { trH: tr.getBoundingClientRect().height, tdInline: td ? (td.style.height || '-') : '-',
      pad: cs ? cs.paddingTop + '/' + cs.paddingBottom : '-', lh: cs ? cs.lineHeight : '-',
      fs: cs ? cs.fontSize : '-', ws: cs ? cs.whiteSpace : '-' };
  });
  const th = document.querySelector('#hot-container .ht_clone_top th');
  if (th) out.cabecera = { h: th.getBoundingClientRect().height, fs: getComputedStyle(th).fontSize };
  const est = document.querySelector('#hot-container td.ops-state-td');
  if (est) { const chip = est.querySelector('[class*="ops-state"]');
    out.estado = { tdH: est.getBoundingClientRect().height, minH: getComputedStyle(est).minHeight,
      chipH: chip ? chip.getBoundingClientRect().height : null }; }
  const main = document.querySelector('main.hot-full-bleed');
  const tb = document.querySelector('.aia-toolbar');
  out.respiro = { mainTop: main.getBoundingClientRect().top, mainPT: getComputedStyle(main).paddingTop,
    toolbarTop: tb.getBoundingClientRect().top,
    pagePadding: getComputedStyle(document.documentElement).getPropertyValue('--ds-page-padding').trim() };
  out.tokenRowH = getComputedStyle(document.documentElement).getPropertyValue('--ds-table-row-h').trim();
  return out;
}), null, 2));
await b.close();
