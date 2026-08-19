import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const FILAS = [
  { unique_id: 101, Id: 101, Titulo: 0, Actividad: 'A', Sub_Contratista: 'X', Responsable_AIA: 'L', Semanas_Inicio: -2, Ejecutado: 0, D_y_E: '100%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '50%', Modelo: 'N/A', Ruta_Critica: '1', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 104, Id: 104, Titulo: 0, Actividad: 'B', Sub_Contratista: 'Y', Responsable_AIA: 'M', Semanas_Inicio: 1, Ejecutado: 0, D_y_E: '100%', Materiales: '33%', MdeO: '100%', Equipos: '66%', Predecesora: '100%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
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
await page.waitForFunction(() => document.querySelectorAll('#hot-container .ht_master tbody tr').length >= 2, null, { timeout: 45000 });
console.log(JSON.stringify(await page.evaluate(() => {
  const out = [];
  for (const sel of ['urgent', 'attention']) {
    const td = document.querySelector(`td[data-aia-severity-rail="${sel}"]`);
    if (!td) { out.push({ sel, err: 'sin celda' }); continue; }
    const cs = getComputedStyle(td);
    out.push({
      sel,
      width: cs.getPropertyValue(`--ds-severity-rail-width-${sel}`).trim(),
      color: cs.getPropertyValue(`--ds-severity-rail-color-${sel}`).trim(),
      criticalText: cs.getPropertyValue('--ds-color-state-critical-text').trim(),
      warningText: cs.getPropertyValue('--ds-color-state-warning-text').trim(),
      boxShadow: cs.boxShadow,
    });
  }
  return out;
}), null, 1));
await b.close();
