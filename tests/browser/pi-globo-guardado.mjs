import { chromium } from 'playwright';
import assert from 'node:assert/strict';

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

const peticiones = [];
pagina.on('request', (r) => {
  if (r.method() === 'POST') peticiones.push({ url: r.url(), cuerpo: r.postData() });
});

// Nota (Task 7, verificado 2026-08-24): el brief traia `PDC Sandbox E2E`,
// mismo proyecto que usan las tareas 4-6. Verificado contra datos reales: esa
// actividad no tiene Responsable AIA asignado y `PI_HOT_OPTIONS.profesionales`
// llega vacio para test.R, asi que NINGUNA fila queda editable ahi -no es un
// bug, es la regla N-1 (falta Responsable AIA bloquea restricciones) mas una
// carga de datos minima-, y el globo abre correctamente en modo lectura (lo
// que de hecho prueba el Step 6). Para probar el guardado hace falta un
// proyecto con Responsable AIA ya asignado: `Optimización Aeropuerto JMC`
// (mismo test.R, 39 actividades, las 39 con responsable) sirve para eso sin
// tocar la logica de la aserción.
await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Optimización Aeropuerto JMC')}`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

// Nota (Task 7): el brief no reiniciaba `peticiones` tras la carga, y la
// carga de la pagina YA hace sus propios POST (filtros, cabecera legacy)
// antes de que el usuario toque nada. Verificado: sin este reinicio la
// aserción de "una sola peticion" contaba trafico de carga, no de guardado
// -fallaba siempre, con cualquier proyecto e implementacion-. Se limpia aqui
// para medir solo lo que dispara el globo, que es lo que la aserción dice
// medir.
peticiones.length = 0;

await pagina.locator('.pi-habilitacion-cell').first().click();
await pagina.waitForSelector('.aia-readiness-popover:popover-open');

const antes = await pagina.locator('.aia-readiness-popover__avance').innerText();

const primerSelector = pagina.locator('.aia-readiness-popover select').first();
const valorActual = await primerSelector.inputValue();
const opciones = await primerSelector.locator('option').allInnerTexts();
const nuevoValor = opciones.find((o) => o !== valorActual && o !== '—') || opciones[1];
await primerSelector.selectOption(nuevoValor);
await pagina.waitForTimeout(1200);

assert.equal(peticiones.length, 1,
  `se esperaba una sola peticion de guardado, salieron ${peticiones.length}`);
// Nota (Task 7): el brief traia `/programacion-intermedia|restriccion/i`.
// Verificado contra `public/index.php`: el UNICO endpoint de guardado de PI es
// `POST /api/pi/save` (linea 127) — no existe otro camino, ni por
// "programacion-intermedia" ni por "restriccion" en la URL. El regex se
// corrige al literal real para no perder la aserción (mismo endpoint, cero
// endpoints nuevos), no para debilitarla.
assert.match(peticiones[0].url, /\/api\/pi\/save/,
  `el globo guarda contra otro endpoint: ${peticiones[0].url}`);

assert.equal(await pagina.locator('.aia-readiness-popover:popover-open').count(), 1,
  'el globo se cerro solo al guardar');

const despues = await pagina.locator('.aia-readiness-popover__avance').innerText();
assert.notEqual(antes, despues, 'el marcador de avance no se movio al liberar');

console.log('OK: una peticion, mismo endpoint, avance en vivo y globo abierto');

// Step 7: Ctrl+Z debe deshacer lo que el globo escribio (`nuevoValor`, ya
// guardado arriba). Si el globo escribiera el valor sin pasar por
// `setDataAtRowProp`, la pila de deshacer de Handsontable no se entera y
// esta aserción queda en rojo sin que nada mas falle.
await pagina.keyboard.press('Control+z');
await pagina.waitForTimeout(800);

const valor = await pagina.locator('.aia-readiness-popover select').first().inputValue();
assert.notEqual(valor, nuevoValor, 'Ctrl+Z no deshizo la liberacion hecha desde el globo');

console.log('OK: Ctrl+Z deshace lo que el globo guardo');
await navegador.close();
