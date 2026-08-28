import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync('public/css/tokens.css', 'utf8');
const core = readFileSync('public/css/design-system/core.css', 'utf8');

const ESCALA = {
  '--ds-table-cell-font-size': '0\\.75rem',        // F31: dato 12px (decisión de Felipe)
  '--ds-table-header-font-size': '0\\.6875rem',    // F33: cabecera 11px
  '--ds-table-chapter-font-size': '0\\.8125rem',   // F36: capítulo 13px
  '--ds-table-cell-pad-x-edge': '1rem',            // F35: perímetro 16px
  '--ds-table-row-h-read': '2rem',                 // F34: listado 32px
  '--ds-table-row-h-touch': '1\\.75rem',           // F11: táctil 28px
  '--ds-table-row-h-projector': '2\\.25rem',       // F40: proyector 36px
  '--ds-table-cell-font-size-projector': '0\\.9375rem', // F40: letra 15px
};
for (const [token, val] of Object.entries(ESCALA)) {
  test(`${token} = ${val.replaceAll('\\\\', '')}`, () => {
    assert.match(tokens, new RegExp(`${token}\\s*:\\s*${val}`));
  });
}

test('F32: utilitaria de cifras tabulares a la derecha', () => {
  assert.match(core, /\.aia-cell-numeric\s*\{[^}]*font-variant-numeric\s*:\s*tabular-nums/s);
  assert.match(core, /\.aia-cell-numeric\s*\{[^}]*text-align\s*:\s*right/s);
});

test('F13: poda de tres líneas con line-clamp', () => {
  assert.match(core, /\.aia-cell-clamp\s*\{[^}]*-webkit-line-clamp\s*:\s*3/s);
});

test('F40: el preset proyector se activa por data-density', () => {
  assert.match(tokens, /\[data-density="projector"\]/);
});
