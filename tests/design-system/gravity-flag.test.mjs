import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('public/css/design-system/components/gravity-flag.css', 'utf8');
const tokens = readFileSync('public/css/tokens.css', 'utf8');

test('la bandera mide 26px y solo existen dos niveles', () => {
  assert.match(tokens, /--ds-flag-width\s*:\s*26px/);
  assert.match(css, /\.aia-flag--urgent\b/);
  assert.match(css, /\.aia-flag--attention\b/);
  assert.doesNotMatch(css, /\.aia-flag--ready\b/, 'D5: listo NO lleva bandera');
  assert.doesNotMatch(css, /\.aia-flag--healthy\b/, 'D5: la calma NO lleva bandera');
});

test('los glifos son SVG dibujado, nunca fuente', () => {
  assert.match(css, /url\("data:image\/svg\+xml/, 'glifo como data-URI');
  assert.doesNotMatch(css, /Font Awesome|content\s*:\s*"\\/, 'D8: sin fuente de iconos en la bandera');
});

test('el color de la bandera es por nivel, no por matiz (D4)', () => {
  assert.match(tokens, /--ds-flag-urgent-bg\s*:\s*var\(--ds-state-solid-red\)/);
  assert.match(tokens, /--ds-flag-urgent-bg-light\s*:\s*var\(--ds-state-solid-red-light\)/);
  assert.match(tokens, /--ds-flag-attention-bg\s*:\s*var\(--ds-state-solid-amber\)/);
  assert.match(tokens, /--ds-flag-attention-bg-light\s*:\s*var\(--ds-state-solid-amber-light\)/);
});
