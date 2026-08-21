// MIDE: que programa_general/hot.js aplique `data-aia-severity-rail` en el
// <tr> y en la primera celda, igual que Intermedia (applyPIRowSeverityAttr) y
// Semanal. Nacio del replanteo 2026-08-20: PG declaraba niveles en
// statePresentation desde el remapeo 8418449a pero ninguna fila los dibujaba —
// el filete era de dos modulos y el contrato dice tres. Es un guard de fuente
// (lee el JS), no de pixel: el color computado lo cubren la sonda del goal y
// los goldens.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const src = readFileSync(
  new URL('../../public/js/modules/programa_general/hot.js', import.meta.url),
  'utf8',
);

test('PG define el aplicador del filete y lo escribe/retira como atributo', () => {
  assert.match(src, /function applyPGRowSeverityAttr\s*\(/, 'falta applyPGRowSeverityAttr');
  assert.match(src, /setAttribute\('data-aia-severity-rail'/, 'no escribe el atributo');
  assert.match(src, /removeAttribute\('data-aia-severity-rail'\)/, 'no lo retira cuando no aplica');
});

test('PG deriva el nivel del filete de statePresentation, con rail ganando al nivel', () => {
  assert.match(
    src,
    /\.rail\s*\|\|/,
    'el aplicador debe preferir presentation.rail sobre presentation.level, como PI y PS',
  );
});
