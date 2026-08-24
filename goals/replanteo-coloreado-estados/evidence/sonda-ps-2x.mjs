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

// La fase NO se puede forzar reescribiendo el HTML del servidor. Se probo, y
// parecia funcionar porque el interceptor reportaba su propia sustitucion:
// medido el 2026-08-19 con el setter de `.value` instrumentado, el UNICO
// escritor de `#Semanal_Confirmada` es `cargarDatosGeneralesPagina2.js:183`,
// que corre en el `success` del AJAX y pisa lo que pinto el PHP. La clave viaja
// en `data`, no en la raiz de la respuesta (`cargarDatosGeneralesPagina2.js:120`:
// `datosGenerales = json_info_global['data']`).
//
// Asi que la fase se fuerza donde de verdad se decide, y el route se registra
// ANTES de la primera navegacion: al entrar al proyecto la app ya aterriza en
// /programacion-semanal, y esa carga escapaba a un route registrado despues.
if (fase === 'calificacion') {
  await page.route('**/datosGeneralesPagina.php*', async (route) => {
    const res = await route.fetch();
    const body = await res.text();
    let json = null;
    try { json = JSON.parse(body); } catch { json = null; }
    if (!json || !json.data) {
      await route.fulfill({ status: res.status(), headers: res.headers(), body });
      return;
    }
    json.data.Semanal_Confirmada = 1;
    await route.fulfill({ status: res.status(), headers: res.headers(), body: JSON.stringify(json) });
  });
}

await page.goto(`${BASE}/dev/entrar?u=test.R`);
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'));
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
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

// La sonda tiene que poder ponerse ROJA. Antes solo informaba, y su unico
// control era que el interceptor confirmase su propia sustitucion de texto: el
// 2026-08-19 declaro «fase forzada a calificacion» y midio la fase equivocada
// durante toda una corrida. Se comprueba el EFECTO —que fase salio de verdad y
// que ningun par de estados comparta fondo— y se sale distinto de cero si falla.
const faseEfectiva = await page.evaluate(() =>
  document.getElementById('Semanal_Confirmada')?.value === '1' ? 'calificacion' : 'programacion');
const fallos = [];
if (faseEfectiva !== fase) {
  fallos.push(`fase pedida «${fase}» pero la pantalla esta en «${faseEfectiva}»`);
}
const prefijoEsperado = fase === 'calificacion' ? 'ps-state-cal-' : 'ps-state-prog-';
const intrusos = datos.filter((d) => !String(d.estado).startsWith(prefijoEsperado));
if (intrusos.length) {
  fallos.push(`${intrusos.length} fila(s) con estado ajeno a la fase: ${intrusos.map((d) => d.estado).join(', ')}`);
}

const unicos = new Set(datos.map((d) => d.fondo));
console.log(`FASE ${fase.toUpperCase()} — ${datos.length} filas, ${unicos.size} fondos distintos`);
console.log('estado'.padEnd(40) + 'cubo'.padEnd(24) + 'fondo'.padEnd(10) + 'rail');
for (const d of datos) console.log(String(d.estado).padEnd(40) + String(d.cubo).padEnd(24) + String(d.fondo).padEnd(10) + String(d.rail || '-'));
writeFileSync(`${OUT}medicion-ps-${fase}.json`, JSON.stringify(datos, null, 2));
await page.waitForFunction(() => {
  const html = document.documentElement;
  return html.classList.contains('aia-theme-dark') || html.getAttribute('data-aia-theme') === 'dark';
}, null, { timeout: 20000 });
await page.waitForTimeout(250);
await page.screenshot({ path: `${OUT}ps-fase-${fase}-1180x820-dark.png` });
if (datos.length && unicos.size !== datos.length) {
  fallos.push(`${datos.length} filas pero solo ${unicos.size} fondos distintos: hay colision`);
}
if (fallos.length) {
  console.log('\nSONDA EN ROJO:');
  for (const f of fallos) console.log('  - ' + f);
  process.exitCode = 1;
} else {
  console.log('\nSONDA EN VERDE: fase correcta y un fondo distinto por estado.');
}

await page.screenshot({ path: `${OUT}ps-detalle-estado-acciones-2x.png`, clip: { x: 920, y: 225, width: 250, height: 450 } });

const chipPS = page.locator('#hot-container .ops-state-chip').first();
if (await chipPS.count()) {
  await chipPS.hover();
  await page.waitForTimeout(400);
  const cajaPS = await chipPS.boundingBox();
  if (cajaPS) { await page.screenshot({ path: `${OUT}tooltip-ps-2x.png`, clip: { x: Math.max(0, cajaPS.x - 60), y: Math.max(0, cajaPS.y - 30), width: 360, height: 220 } }); }
}
await b.close();
