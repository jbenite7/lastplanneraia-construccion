import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('frontend/src/modules/programa-general/programa-general.css', 'utf8');
const sinComentarios = css.replace(/\/\*[\s\S]*?\*\//g, '');

test('programa-general.css no define tokens --ds-* (solo los consume)', () => {
  const definiciones = sinComentarios.match(/--ds-[a-z0-9-]+\s*:/gi) ?? [];
  assert.deepEqual(definiciones, []);
});

test('programa-general.css no redefine :root ni selectores de tema', () => {
  assert.doesNotMatch(sinComentarios, /(^|[ ,{}\s]):root\b/);
  assert.doesNotMatch(sinComentarios, /\[data-(aia-)?theme=/);
});

test('programa-general.css no trae colores literales', () => {
  const hex = sinComentarios.match(/#[0-9a-f]{3,8}\b/gi) ?? [];
  const rgb = sinComentarios.match(/rgba?\(\s*\d/gi) ?? [];
  assert.deepEqual([...hex, ...rgb], []);
});
