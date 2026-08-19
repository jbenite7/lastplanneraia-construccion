// Confirma los compromisos de la semana por la VIA DE LA APP (el mismo endpoint
// que usa el boton «Confirmar Compromisos»), no por SQL. Es lo que pone la vista
// en fase `calificacion`.
// El estado previo se captura y se restaura aparte: ver el goal.
import { chromium } from 'playwright';
const BASE = 'http://localhost:8081';
const b = await chromium.launch(); const c = await b.newContext(); const page = await c.newPage();
await page.goto(`${BASE}/dev/entrar?u=test.A`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'));
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });
// El token sale de la propia pagina, igual que lo toma el modulo
// (`hot.js:3946`: `$('meta[name="csrf-token"]').attr('content')`).
await page.goto(`${BASE}/programacion-semanal`, { waitUntil: 'domcontentloaded' });
const csrf = await page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content || '');
console.log('csrf:', csrf ? 'obtenido' : 'NO ENCONTRADO');
const r = await page.request.post(`${BASE}/api/semanal/save?db=da_porto`, {
  form: { opcion: 'bloquear_compromisos', semana: '1', fechaCierreCompromisos: '', _csrf_token: csrf },
});
console.log('HTTP', r.status(), (await r.text()).slice(0, 200));
await b.close();
