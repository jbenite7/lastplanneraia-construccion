import assert from 'node:assert/strict';
import test from 'node:test';
import { readFile } from 'node:fs/promises';
import { leerRestriccion, repartirCuadritos, MAX_CUADRITOS_VISIBLES }
  from '../../public/js/design-system/readiness-cell.js';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

test('N/A no cuenta y no se lee como liberada', () => {
  const r = leerRestriccion('N/A', 1);
  assert.equal(r.esNoAplica, true);
  assert.equal(r.cumple, false);
  assert.equal(r.relleno, 0);
});

test('vacio se trata como cero, no como N/A', () => {
  const r = leerRestriccion('', 1);
  assert.equal(r.esNoAplica, false);
  assert.equal(r.cumple, false);
  assert.equal(r.relleno, 0);
});

test('el relleno refleja el porcentaje crudo, sea cual sea la escala', () => {
  assert.equal(leerRestriccion('33%', 1).relleno, 0.33);
  assert.equal(leerRestriccion('66%', 1).relleno, 0.66);
  assert.equal(leerRestriccion('50%', 0.5).relleno, 0.5);
});

test('cumple se decide contra el umbral propio, no contra el 100', () => {
  assert.equal(leerRestriccion('50%', 0.5).cumple, true);
  assert.equal(leerRestriccion('50%', 1).cumple, false);
  assert.equal(leerRestriccion('100%', 1).cumple, true);
});

test('siete caben enteras', () => {
  const { visibles, sobrantes } = repartirCuadritos([1, 2, 3, 4, 5, 6, 7]);
  assert.equal(visibles.length, 7);
  assert.equal(sobrantes, 0);
});

test('con mas de siete se muestran seis y el resto se cuenta', () => {
  const { visibles, sobrantes } = repartirCuadritos([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
  assert.equal(visibles.length, 6);
  assert.equal(sobrantes, 4);
});

test('el tope declarado es siete, no ocho', () => {
  assert.equal(MAX_CUADRITOS_VISIBLES, 7);
});

test('la primitiva del cuadrito vive en una capa y sin hex', async () => {
  const css = await read('public/css/design-system/components/readiness-squares.css');
  assert.match(css, /@layer\s+components/, 'la hoja no declara su capa');
  assert.doesNotMatch(css, /#[0-9a-fA-F]{3,8}\b/, 'la hoja trae hex literales');
});

test('el cuadrito lleva tres senales y no solo el color', async () => {
  const css = await read('public/css/design-system/components/readiness-squares.css');
  assert.match(css, /\.aia-readiness__fill/, 'falta el relleno');
  assert.match(css, /\.aia-readiness__check/, 'falta la marca de visto');
  assert.match(css, /\.aia-readiness__box--na/, 'falta el estado no aplica');
});
