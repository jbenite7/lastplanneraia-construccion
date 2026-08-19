// Sonda de Programacion Semanal. Mide el color COMPUTADO del fondo de fila y del
// filete en LAS DOS FASES, que nunca conviven: `stateMachine.js:58` resuelve
// `calificacion` si la semana esta confirmada y `programacion` si no.
//
// Las filas se siembran para disparar los cinco estados de cada fase segun
// `getStateKey` (stateMachine.js:180-240), no al azar.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;
const fase = process.argv[2] === 'calificacion' ? 'calificacion' : 'programacion';

const base = (id, extra) => Object.assign({
  Consecutivo: id, Id: id, Descripcion: 'Actividad ' + id, Ubicacion: 'Eje A',
  Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'L. Marin', Empresa: 'AIA',
  Unidad: '%', Activa: 1, Critica: 0, Compromiso: 10, Ejecutado: 0,
  Ejecutado_Real: null, Prog_Sin_Restricciones_100: 0,
  // Restricciones duras al 100 %: sin esto `hasPendingCommitConditions` devuelve
  // true para TODA fila y los cinco estados colapsan en «condiciones
  // pendientes». Lo destapo la primera corrida: tres filas distintas salieron
  // con el mismo estado, y no era el producto sino la siembra.
  D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%',
}, extra);

const FILAS = fase === 'programacion' ? [
  // sinLiberacion + ejecucion -> ejecucion-con-restricciones
  base(201, { Prog_Sin_Restricciones_100: 1, Ejecutado: 0.4 }),
  // sinLiberacion + ruta critica -> bloqueo critico
  base(202, { Prog_Sin_Restricciones_100: 1, Critica: 1 }),
  // sinLiberacion a secas -> condiciones pendientes
  base(203, { Prog_Sin_Restricciones_100: 1 }),
  // liberada pero sin compromiso -> por comprometer
  base(204, { Compromiso: 0 }),
  // liberada y con compromiso -> lista para confirmar
  base(205, {}),
] : [
  // compromiso vacio + ejecucion real -> TNP
  base(301, { Compromiso: 0, Ejecutado_Real: 5, Ejecutado: 0.5 }),
  // compromiso vacio sin ejecucion real -> sin calificar
  base(302, { Compromiso: 0, Ejecutado: 0.5 }),
  // real < compromiso + ruta critica -> incumplida critica
  base(303, { Compromiso: 10, Ejecutado_Real: 2, Critica: 1, Ejecutado: 0.5 }),
  // real < compromiso -> incumplida
  base(304, { Compromiso: 10, Ejecutado_Real: 2, Ejecutado: 0.5 }),
  // real >= compromiso -> cumplida control
  base(305, { Compromiso: 10, Ejecutado_Real: 10, Ejecutado: 0.5 }),
];

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 }, deviceScaleFactor: 1 });
const page = await c.newPage();
await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":false}' }));
await page.route('**/api/semanal/list**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }) }));

await page.goto(`${BASE}/dev/entrar?u=test.R`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'));
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
// La fase la decide un input oculto que pinta el SERVIDOR
// (`programacion_semanal.view.php:59`) y que el modulo lee al construir la
// rejilla. Ponerlo con `page.evaluate` tras `domcontentloaded` llega tarde: se
// probo y las filas seguian saliendo en fase `programacion`. Se reescribe el
// HTML en vuelo, que es determinista y no depende de ganarle una carrera al
// arranque del modulo.
if (fase === 'calificacion') {
  await page.route(/\/programacion-semanal(\?|$)/, async (route) => {
    const res = await route.fetch();
    const original = await res.text();
    const html = original.replace(/(id="Semanal_Confirmada"[^>]*value=")0(")/, '$11$2');
    console.log('interceptor: ' + (html === original ? 'NO sustituyo (patron no casa)' : 'fase forzada a calificacion'));
    await route.fulfill({ status: res.status(), headers: res.headers(), body: html });
  });
}
await page.goto(`${BASE}/programacion-semanal`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.querySelectorAll('#hot-container .ht_master tbody tr').length >= 3, null, { timeout: 45000 })
  .catch(() => console.log('AVISO: menos de 3 filas renderizadas'));
await page.waitForTimeout(1200);

const datos = await page.evaluate(() => {
  const cv = document.createElement('canvas'); cv.width = 1; cv.height = 1;
  const ctx = cv.getContext('2d', { willReadFrequently: true });
  const rgba = (v) => { if (!v || v === 'transparent') return [0,0,0,0]; ctx.globalCompositeOperation='copy'; ctx.fillStyle='#000'; ctx.fillStyle=v; ctx.fillRect(0,0,1,1); const d=ctx.getImageData(0,0,1,1).data; return [d[0],d[1],d[2],d[3]/255]; };
  const over = (f,g) => { const a=f[3]+g[3]*(1-f[3]); if(!a) return [0,0,0,0]; const m=i=>(f[i]*f[3]+g[i]*g[3]*(1-f[3]))/a; return [m(0),m(1),m(2),a]; };
  const fondo = (el) => { let acc=[0,0,0,0], n=el; while(n){ acc=over(acc, rgba(getComputedStyle(n).backgroundColor)); if(acc[3]>=0.999) break; n=n.parentElement; } return acc; };
  const hex = (c) => '#'+[0,1,2].map(i=>Math.round(c[i]).toString(16).padStart(2,'0')).join('');
  const out = [];
  for (const tr of document.querySelectorAll('#hot-container .ht_master tbody tr')) {
    const td0 = tr.querySelector('td');
    if (!td0) continue;
    const clase = (tr.className.match(/ps-state-[\w-]+/) || td0.className.match(/ps-state-[\w-]+/) || ['(sin estado)'])[0];
    const cubo = (td0.className.match(/ps-alert-[\w-]+/) || ['-'])[0];
    out.push({ estado: clase, cubo, fondo: hex(fondo(td0)), rail: td0.getAttribute('data-aia-severity-rail'), railShadow: getComputedStyle(td0).boxShadow });
  }
  return out;
});

const unicos = new Set(datos.map((d) => d.fondo));
console.log(`FASE ${fase.toUpperCase()} — ${datos.length} filas, ${unicos.size} fondos distintos`);
console.log('estado'.padEnd(40) + 'cubo'.padEnd(24) + 'fondo'.padEnd(10) + 'rail');
for (const d of datos) console.log(String(d.estado).padEnd(40) + String(d.cubo).padEnd(24) + String(d.fondo).padEnd(10) + String(d.rail || '-'));
writeFileSync(`${OUT}medicion-ps-${fase}.json`, JSON.stringify(datos, null, 2));
await page.screenshot({ path: `${OUT}ps-fase-${fase}-1180x820-dark.png` });
await b.close();
