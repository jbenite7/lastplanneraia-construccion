import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

test('la tabla declara una columna de habilitacion y ninguna de restriccion suelta', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  assert.match(js, /data:\s*'__habilitacion'/, 'no existe la columna de habilitacion');
  assert.doesNotMatch(js, /renderer:\s*'piRestrictionRenderer'/,
    'sigue habiendo columnas de restriccion sueltas');
});

test('Estado_Restricciones ya no es una columna de la tabla', async () => {
  const js = await read('public/js/modules/programacion_intermedia/hot.js');
  assert.doesNotMatch(js, /\{\s*data:\s*'Estado_Restricciones'/,
    'el % Liberacion sigue ocupando una columna; la spec lo muda al globo');
});
