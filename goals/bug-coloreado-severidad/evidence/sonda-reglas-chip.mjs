// Segunda sonda del frente: QUE REGLA gana el fondo del chip de estado. La
// primera sonda midio el color; esta explica de donde sale, leyendo las reglas
// emparejadas por el propio motor (CDP CSS.getMatchedStylesForNode), no el texto
// de la hoja.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const FILAS = [
  { unique_id: 102, Id: 102, Titulo: 0, Actividad: 'Excavacion sotano 1', Sub_Contratista: 'Movitierra', Responsable_AIA: 'C. Rojas', Semanas_Inicio: -1, Ejecutado: 0, D_y_E: '33%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '50%', Pdto_Cons: '100%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 104, Id: 104, Titulo: 0, Actividad: 'Placa nivel 1', Sub_Contratista: 'Estructuras Andinas', Responsable_AIA: 'M. Torres', Semanas_Inicio: 1, Ejecutado: 0, D_y_E: '100%', Materiales: '33%', MdeO: '100%', Equipos: '66%', Predecesora: '100%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
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

const cdp = await c.newCDPSession(page);
await cdp.send('DOM.enable'); await cdp.send('CSS.enable');
const { root } = await cdp.send('DOM.getDocument', { depth: -1, pierce: true });
for (const estado of ['alert-1-week', 'blocked-overdue']) {
  const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `tr td.pi-state-${estado} .ops-state-chip` });
  console.log(`\n=== ${estado} (nodeId ${nodeId}) ===`);
  if (!nodeId) continue;
  const ms = await cdp.send('CSS.getMatchedStylesForNode', { nodeId });
  for (const m of ms.matchedCSSRules) {
    const props = (m.rule.style.cssProperties || []).filter((p) => p.name === 'background' || p.name === 'background-color');
    for (const p of props) {
      console.log(' layer=', JSON.stringify((m.rule.layers || []).map((l) => l.text)), '| sel=', m.rule.selectorList.text, '|', p.name, ':', p.value);
    }
  }
}
console.log('\n--- hojas cargadas ---');
console.log((await page.evaluate(() => [...document.querySelectorAll('link[rel=stylesheet]')].map((l) => l.getAttribute('href')))).join('\n'));
await b.close();
