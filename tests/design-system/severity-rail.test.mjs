import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// La regla que faltaba y que este frente existe para escribir: un canal, un eje.
// Vive en el contrato y no en la prosa de una hoja, porque es lo que se rompio:
// el fondo intentaba decir identidad Y gravedad, y acabo sin decir ninguna.
test('el contrato declara que ningun canal codifica dos ejes', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  assert.ok(Array.isArray(semantics.axisRules), 'state-semantics.json no declara axisRules');
  const texto = semantics.axisRules.join(' ').toLowerCase();
  assert.match(texto, /fondo/, 'axisRules no dice que hace el fondo');
  assert.match(texto, /filete/, 'axisRules no dice que hace el filete');
  assert.match(texto, /orden/, 'axisRules no dice que hace el orden');
});

// Los cuatro niveles que este frente corrige, y los cuatro que NO toca. Se
// asiertan todos por su valor exacto y no por «es distinto de antes»: un test
// que solo comprueba el cambio pasa igual si alguien los mueve otra vez.
//
// Procedencia de cada uno en goals/bug-coloreado-severidad/respuestas-ds-f1.md:
// cuatro no estaban en disputa, `execution-blocked` lo decidio el usuario, y los
// otros tres los propuso el implementador y el usuario los confirmo.
const NIVELES_PI = {
  'blocked-overdue-critical': 'urgent',
  'blocked-overdue': 'urgent',
  'blocked-due': 'urgent',
  'alert-1-week': 'attention',
  'alert-2-3-weeks': 'attention',
  'alert-4-6-weeks': 'healthy',
  'execution-blocked': 'urgent',
  'liberated-control': 'healthy',
};

test('los ocho estados de programacion-intermedia llevan el nivel decidido', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const pi = semantics.moduleMappings.find((m) => m.module === 'programacion-intermedia');
  const real = Object.fromEntries(pi.states.map(({ key, level }) => [key, level]));
  assert.deepEqual(real, NIVELES_PI);
});

// El modulo no puede desviarse del contrato: `statePresentation` en hot.js es una
// proyeccion, no una segunda fuente.
test('statePresentation de hot.js declara los mismos niveles que el contrato', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  const bloque = js.match(/var statePresentation = \{([\s\S]*?)\};/)[1];
  const real = {};
  for (const [, key, level] of bloque.matchAll(/'?([\w-]+)'?:\s*\{\s*level:\s*'(\w+)'/g)) {
    if (key !== 'neutral') real[key] = level;
  }
  assert.deepEqual(real, NIVELES_PI);
});

// SOLO DOS NIVELES LLEVAN FILETE. Reescrito el 2026-08-19 al adoptar el contrato
// `ds-f1a-escala-estado.json`, que declara `barra: false` para `controlado` y
// define el nivel nulo -«Fuera de Ventana», «Sin Datos»- como AUSENCIA de
// gravedad, no como un grado mas.
//
// La version anterior de este archivo asertaba lo contrario: cuatro escalones,
// y una linea que prohibia expresamente que el mas bajo midiera 0. Cambia porque
// cambio el CONTRATO, no para que pase, y el numero que lo decidio se deja
// escrito: con la regla de 7+ semanas ratificada por el usuario, «Fuera de
// Ventana» pasa a ~51% de las actividades. Un filete universal marcaria mas de
// media tabla por filas que no piden nada, y una marca que llevan todos deja de
// marcar.
const CON_FILETE = ['urgent', 'attention'];
const SIN_FILETE = ['healthy', 'neutral'];

test('solo los dos niveles que piden accion declaran grosor, y bajan con la gravedad', async () => {
  const tokens = await read('public/css/tokens.css');
  const anchos = CON_FILETE.map((n) => {
    const v = tokens.match(new RegExp(`--ds-severity-rail-width-${n}:\\s*([^;]+);`))?.[1]?.trim();
    assert.ok(v, `falta --ds-severity-rail-width-${n}`);
    return parseFloat(v);
  });
  assert.equal(new Set(anchos).size, 2, `los dos escalones deben ser distintos: ${anchos}`);
  assert.deepEqual(anchos, [...anchos].sort((a, b) => b - a), `los grosores no bajan con la gravedad: ${anchos}`);
  assert.ok(anchos[1] > 0, 'un escalon que marca no puede medir 0');
});

test('los niveles sin gravedad no declaran token de filete', async () => {
  const tokens = await read('public/css/tokens.css');
  for (const n of SIN_FILETE) {
    // Cierra la puerta por el otro lado: que `healthy` no tenga token no es un
    // olvido que alguien pueda "arreglar" reponiendolo, es el contrato.
    assert.doesNotMatch(
      tokens,
      new RegExp(`--ds-severity-rail-(width|color)-${n}\\s*:`),
      `${n} no debe declarar token de filete: el contrato dice que no lleva barra`,
    );
  }
});

test('la primitiva traduce a grosor los que marcan, y a `none` los que no', async () => {
  const css = await read('public/css/design-system/components/severity-rail.css');
  for (const n of CON_FILETE) {
    const regla = css.match(new RegExp(`\\[data-aia-severity-rail="${n}"\\]\\s*\\{([^}]*)\\}`))?.[1];
    assert.ok(regla, `severity-rail.css no traduce [data-aia-severity-rail="${n}"]`);
    assert.match(regla, new RegExp(`--ds-severity-rail-width-${n}`), `${n} no usa su token de grosor`);
    assert.match(regla, new RegExp(`--ds-severity-rail-color-${n}`), `${n} no usa su token de color`);
  }
  // El silencio se DECLARA. Estas celdas conviven con superposiciones de
  // Handsontable que tambien usan `box-shadow: inset`, asi que sin una
  // declaracion explicita el «sin filete» dependeria de quien gane la cascada.
  const sinFilete = css.match(/\[data-aia-severity-rail="healthy"\][^{]*\{([^}]*)\}/)?.[1];
  assert.ok(sinFilete, 'severity-rail.css no declara el caso sin filete');
  assert.match(sinFilete, /box-shadow:\s*none/, 'el caso sin filete debe declarar `box-shadow: none`, no omitirse');
  assert.match(css, /\[data-aia-severity-rail="neutral"\]/, 'el nivel sin gravedad tambien debe estar declarado');
});

test('el catalogo publica la ficha del filete', async () => {
  const catalogo = JSON.parse(await read('docs/design-system/component-catalog.json'));
  const arr = Array.isArray(catalogo) ? catalogo : catalogo.components;
  const ficha = arr.find((c) => c.id === 'severity-rail');
  assert.ok(ficha, 'component-catalog.json no publica la ficha `severity-rail`');
  assert.equal(ficha.family, 'states-feedback');
  assert.equal(ficha.maturity, 'candidate');
});
