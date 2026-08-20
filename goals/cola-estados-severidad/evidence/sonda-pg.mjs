// Sonda de Programa General: mide el color COMPUTADO del fondo de fila por
// estado declarado, y comprueba que el mapeo del literal `Estado` -> clave de
// presentacion no cae en la heuristica de respaldo.
//
// Proyecto 68 «Optimización Aeropuerto JMC», semana 10: es el unico accesible a
// una cuenta sembrada que trae los SIETE estados vivos a la vez, incluido
// «Fuera de Ventana», que es el 39,3% de la tabla en toda la base.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;
const etiqueta = process.argv[2] || 'actual';

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 }, deviceScaleFactor: 1 });
const page = await c.newPage();
// Siembra determinista: una fila por estado, con los literales EXACTOS de la
// columna `Estado` (verificados contra la base). Handsontable es virtual y solo
// renderiza lo visible, asi que medir sobre datos reales dejaba estados fuera de
// pantalla; esto los pone todos a la vez y hace la sonda reproducible.
const ESTADOS = ['Fuera de Ventana', 'Actividad Futura', 'En Curso', 'Terminada',
                 'Debe Iniciar', 'Atrasada', 'Sin Datos'];
const FILAS = ESTADOS.map((estado, i) => ({
  Consecutivo: 900 + i, Id: 900 + i, unique_id: 900 + i,
  Actividad: estado, Descripcion: estado, Titulo: 0,
  Estado: estado, Semanas_Inicio: 10, Ejecutado: 0,
  // Restricciones duras cumplidas: sin esto `getRestrictionAlertKey` mete la
  // fila en un cubo r0..r4-6 y el realce por condicion tapa el matiz del estado,
  // que es justo lo que esta sonda quiere medir por separado.
  D_y_E: 1, Materiales: 1, MdeO: 1, Equipos: 1, Predecesora: 1,
  Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'L. Marin', unidad: '%',
}));
await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":false}' }));
await page.route('**/api/general/list**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }) }));
await page.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Da Porto')}`);
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 30000 }).catch(() => {});
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await page.goto(`${BASE}/programa-general`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => document.querySelectorAll('#hot-container .ht_master tbody tr').length >= 5, null, { timeout: 60000 })
  .catch(() => console.log('AVISO: menos de 5 filas renderizadas'));
await page.waitForTimeout(1800);

const datos = await page.evaluate(() => {
  const cv = document.createElement('canvas'); cv.width = 1; cv.height = 1;
  const ctx = cv.getContext('2d', { willReadFrequently: true });
  const rgba = (v) => { if (!v || v === 'transparent') return [0,0,0,0]; ctx.globalCompositeOperation='copy'; ctx.fillStyle='#000'; ctx.fillStyle=v; ctx.fillRect(0,0,1,1); const d=ctx.getImageData(0,0,1,1).data; return [d[0],d[1],d[2],d[3]/255]; };
  const over = (f,g) => { const a=f[3]+g[3]*(1-f[3]); if(!a) return [0,0,0,0]; const m=i=>(f[i]*f[3]+g[i]*g[3]*(1-f[3]))/a; return [m(0),m(1),m(2),a]; };
  const fondo = (el) => { let acc=[0,0,0,0], n=el; while(n){ acc=over(acc, rgba(getComputedStyle(n).backgroundColor)); if(acc[3]>=0.999) break; n=n.parentElement; } return acc; };
  const hex = (c) => '#'+[0,1,2].map(i=>Math.round(c[i]).toString(16).padStart(2,'0')).join('');
  const porEstado = {};
  for (const tr of document.querySelectorAll('#hot-container .ht_master tbody tr')) {
    const tds = [...tr.querySelectorAll('td')];
    if (!tds.length) continue;
    const chip = tr.querySelector('[data-aia-hue]');
    // El literal del estado lo lleva el chip de la columna Estado Operativo.
    const etiqueta = (tr.textContent.match(/Fuera de Ventana|Actividad Futura|Debe Iniciar|En Curso|Terminada|Atrasada|Sin Datos/) || ['(sin etiqueta)'])[0];
    const clase = (tds[0].className.match(/pg-state-[\w-]+/) || ['(sin clase)'])[0];
    if (porEstado[etiqueta]) { porEstado[etiqueta].filas++; continue; }
    porEstado[etiqueta] = {
      filas: 1,
      clase,
      fondo: hex(fondo(tds[0])),
      hue: chip ? chip.getAttribute('data-aia-hue') : '(sin chip)',
      severidad: chip ? chip.getAttribute('data-aia-severity') : '-',
      urgencia: chip ? chip.getAttribute('data-aia-urgency') : '-',
    };
  }
  return porEstado;
});

console.log(`PROGRAMA GENERAL (${etiqueta}) — ${Object.keys(datos).length} estados distintos en pantalla`);
console.log('etiqueta'.padEnd(22) + 'clase de fila'.padEnd(30) + 'fondo'.padEnd(10) + 'matiz'.padEnd(12) + 'sev/urg');
for (const [k, v] of Object.entries(datos)) {
  console.log(k.padEnd(22) + String(v.clase).padEnd(30) + String(v.fondo).padEnd(10) +
              String(v.hue).padEnd(12) + `${v.severidad}/${v.urgencia}  (${v.filas} filas)`);
}
const fondos = Object.values(datos).map((v) => v.fondo);
const dup = fondos.filter((f, i) => fondos.indexOf(f) !== i);
if (dup.length) console.log('\nCOLISIONES de fondo: ' + [...new Set(dup)].join(', '));
else console.log('\nSin colisiones: un fondo distinto por estado.');
writeFileSync(`${OUT}pg-${etiqueta}.json`, JSON.stringify(datos, null, 2));
await page.screenshot({ path: `${OUT}pg-${etiqueta}-1180x820-dark.png` });
await b.close();
