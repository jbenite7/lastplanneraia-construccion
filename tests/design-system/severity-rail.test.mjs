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
