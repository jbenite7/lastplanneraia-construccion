// Sonda del tooltip de estado (ronda 4): hover sobre un chip en cada modulo,
// espera el panel visible y guarda recorte 2x. Tambien verifica Escape.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 }, deviceScaleFactor: 2 });
const page = await c.newPage();
await page.goto(`${BASE}/dev/entrar?u=test.R`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));

for (const [ruta, nombre] of [['/programa-general', 'pg'], ['/programacion-intermedia', 'pi'], ['/programacion-semanal', 'ps']]) {
  await page.goto(`${BASE}${ruta}`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#hot-container .ops-state-chip', { timeout: 45000 }).catch(() => null);
  const chip = page.locator('#hot-container .ops-state-chip').first();
  if (!(await chip.count())) { console.log(nombre, 'SIN CHIP VISIBLE (columna fuera de viewport?)'); continue; }
  await chip.scrollIntoViewIfNeeded();
  await chip.hover();
  const visible = await page.waitForFunction(() => {
    const tip = document.querySelector('#hot-container .ops-state-chip:hover .aia-state-tip');
    return tip && getComputedStyle(tip).visibility === 'visible';
  }, null, { timeout: 4000 }).then(() => true).catch(() => false);
  const caja = await chip.boundingBox();
  if (caja) {
    await page.screenshot({ path: `tooltip-${nombre}-2x.png`, clip: { x: Math.max(0, caja.x - 60), y: Math.max(0, caja.y - 30), width: 360, height: 220 } });
  }
  await page.keyboard.press('Escape');
  const cerrado = await page.evaluate(() => document.body.classList.contains('aia-tips-off'));
  console.log(nombre, 'tooltip visible:', visible, '| Escape lo oculta:', cerrado);
}
await b.close();
