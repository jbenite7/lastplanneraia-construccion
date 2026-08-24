import { chromium } from 'playwright';
import assert from 'node:assert/strict';

// Verificacion puntual pedida en revision de Task 7 (hallazgo Important):
// `beforeUndoStackChange` (agregado en hot.js para que las escrituras
// derivadas de `saveRow` con source 'internal-update' no ensucien la pila de
// deshacer del globo) es una opcion GLOBAL de Handsontable -aplica a la pila
// de deshacer de TODA la tabla, no solo a las props de restriccion-. Esta
// prueba confirma que la pila y el mecanismo de deshacer NATIVOS de
// Handsontable siguen intactos para una columna normal (Observaciones,
// texto libre, prop != restriccion) tras ese cambio.
//
// Nota metodologica: se ejerce `hot.undo()` directamente -la MISMA funcion
// que invoca el atajo de teclado Ctrl+Z (`gridContext.addShortcuts([{ keys:
// [['Control/Meta','z']], callback: () => { this.undo(); } }])`, ver
// node_modules/handsontable/dist/handsontable.js ~linea 92388)-, no el
// keydown sintetico. Verificado en vivo: en este entorno headless/Playwright
// el keydown de Ctrl+Z sintetico (`page.keyboard.press`, con y sin foco real
// en la celda, con y sin retardos entre down/up) NO llega al
// `ShortcutManager` de Handsontable ni ANTES de este cambio (confirmado
// contra el commit previo a la Task 7, mismo resultado) — es una limitacion
// de la herramienta de prueba en este entorno, no una regresion introducida
// aqui. `hot.undo()` es exactamente lo que ese atajo ejecuta, asi que
// probarlo directamente prueba lo mismo que pediria un Ctrl+Z real: que el
// mecanismo de deshacer para una columna normal sigue intacto.

const BASE = 'http://localhost:8081';
const navegador = await chromium.launch();
const pagina = await (await navegador.newContext({
  viewport: { width: 1180, height: 820 },
})).newPage();

const peticiones = [];
pagina.on('request', (r) => {
  if (r.method() === 'POST' && r.url().includes('/api/pi/save')) {
    peticiones.push(r.url());
  }
});

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Optimización Aeropuerto JMC')}`,
  { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await pagina.waitForSelector('.pi-habilitacion-cell', { timeout: 30000 });

// Arranque limpio y determinista para la fila 0, columna Observaciones
// (columna real de texto libre, no fundida por la Task 4).
await pagina.evaluate(() => {
  window.PIHotModule.getHotInstance().setDataAtRowProp(0, 'Observaciones', 'base', 'edit');
});
await pagina.waitForTimeout(1000);
peticiones.length = 0;

// Edicion real desde la celda: doble clic (abre el editor de texto nativo de
// Handsontable), escribir, Enter para comprometer el cambio -exactamente
// como lo haria una persona-.
const celda = pagina.locator('.handsontable tbody tr').first().locator('td').nth(9);
await celda.click();
await pagina.waitForTimeout(200);
await pagina.keyboard.type('nuevo-valor-normal');
await pagina.keyboard.press('Enter');
await pagina.waitForTimeout(1200);

const trasEditar = await pagina.evaluate(() =>
  window.PIHotModule.getHotInstance().getDataAtRowProp(0, 'Observaciones'));
assert.equal(trasEditar, 'nuevo-valor-normal', 'la edicion de una columna normal no se aplico');
assert.ok(peticiones.length >= 1, 'la edicion de una columna normal no disparo el guardado (mismo saveRow de siempre)');

const pilaAntes = await pagina.evaluate(() =>
  window.PIHotModule.getHotInstance().getPlugin('undoRedo').doneActions.map((a) => a.actionType));
assert.ok(pilaAntes.length > 0, 'la edicion no quedo registrada en la pila de deshacer');

// La MISMA funcion que invoca el atajo Ctrl+Z nativo.
await pagina.evaluate(() => window.PIHotModule.getHotInstance().undo());
await pagina.waitForTimeout(800);

const trasDeshacer = await pagina.evaluate(() =>
  window.PIHotModule.getHotInstance().getDataAtRowProp(0, 'Observaciones'));
assert.equal(trasDeshacer, 'base',
  `el deshacer nativo de Handsontable no revirtio una columna normal: quedo en "${trasDeshacer}"`);

console.log('OK: hot.undo() nativo sigue revirtiendo una columna normal (Observaciones) tras beforeUndoStackChange');
await navegador.close();
