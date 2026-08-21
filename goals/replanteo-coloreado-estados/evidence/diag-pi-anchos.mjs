// Por que PI mide 1490 dentro de 1100: comparar los anchos que pide el JS
// (hot.getColWidth) con los que pinta el DOM, y ver si el recalculo por resize
// los acerca a los floors documentados.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch();
const p = await (await b.newContext({ viewport: { width: 1180, height: 820 } })).newPage();
await p.goto(`${BASE}/dev/entrar?u=test.R`);
const card = p.locator('.project-item').filter({ has: p.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await p.waitForURL(u => !u.toString().includes('/proyectos'), { timeout: 45000 });
await p.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await p.waitForTimeout(3000);
const leer = () => p.evaluate(() => {
  const inst = (window.Handsontable && document.querySelector('#hot-container')) ? null : null;
  const th = [...document.querySelectorAll('#hot-container .ht_master thead th')].map(x => Math.round(x.getBoundingClientRect().width));
  const holder = document.querySelector('#hot-container .ht_master .wtHolder');
  return { th, suma: th.reduce((a, x) => a + x, 0), scrollW: holder && holder.scrollWidth, clientW: holder && holder.clientWidth };
});
console.log('inicial', JSON.stringify(await leer()));
await p.setViewportSize({ width: 1181, height: 820 });
await p.waitForTimeout(1200);
await p.setViewportSize({ width: 1180, height: 820 });
await p.waitForTimeout(1500);
console.log('tras resize', JSON.stringify(await leer()));
await b.close();
