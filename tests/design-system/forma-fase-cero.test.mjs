// tests/design-system/forma-fase-cero.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync('public/css/tokens.css', 'utf8');
const core = readFileSync('public/css/design-system/core.css', 'utf8');

test('F1/F2: existe el radio de dato y es 4px', () => {
  assert.match(tokens, /--ds-radius-data\s*:\s*0\.25rem/);
});

test('F7: tres pisos de elevación con nombre', () => {
  assert.match(tokens, /--ds-elevation-rest\s*:\s*var\(--ds-shadow-xs\)/);
  assert.match(tokens, /--ds-elevation-float\s*:\s*var\(--ds-shadow-md\)/);
  assert.match(tokens, /--ds-elevation-top\s*:\s*var\(--ds-shadow-lg\)/);
});

test('F9: el botón se hunde al presionar', () => {
  assert.match(core, /\.aia-btn:active\b[^}]*translateY\(1px\)/s);
  assert.match(core, /\.aia-btn:active\b[^}]*inset/s);
});

test('F10: el campo es pozo', () => {
  assert.match(tokens, /--ds-input-well-bg\s*:\s*var\(--ds-active-surface-raised\)/);
  assert.match(core, /\.aia-input[^{]*\{[^}]*var\(--ds-input-well-bg\)/s);
});

test('F20: anillo de foco doble — halo del fondo + marca', () => {
  assert.match(tokens, /--ds-shadow-focus\s*:\s*0 0 0 2px var\(--ds-active-bg-canvas\),\s*0 0 0 4px var\(--ds-active-focus-ring\)/);
});

test('F29: scroll teñido con tokens del tema', () => {
  assert.match(core, /scrollbar-color\s*:\s*var\(--ds-active-border-control\)\s+transparent/);
});
