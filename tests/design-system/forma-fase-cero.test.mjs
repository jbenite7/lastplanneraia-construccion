// tests/design-system/forma-fase-cero.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync('public/css/tokens.css', 'utf8');
const core = readFileSync('public/css/design-system/core.css', 'utf8');
const temaOscuro = readFileSync('public/css/design-system/entrypoints/theme-overrides.css', 'utf8');
const temaClaro = readFileSync('public/css/design-system/theme-claro.css', 'utf8');

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
  assert.match(tokens, /--ds-input-well-bg\s*:\s*var\(--ds-active-surface-well\)/);
  assert.match(core, /\.aia-input[^{]*\{[^}]*var\(--ds-input-well-bg\)/s);
});

// Este guard existe por una regresion real: el pozo apuntaba a
// `--ds-active-surface-raised`, que cumple F10 en claro y la INVIERTE en oscuro
// (ahi «raised» es mas claro que la superficie, asi que el campo se leia
// elevado). El assert de arriba, solo, no la habria atrapado: fijaba el nombre
// del token, no lo que F10 pide de el. Estos tres lo comprueban de verdad.
test('F10: el pozo tiene token propio por tema y baja en los DOS', () => {
  const hex = (nombre) => {
    const m = new RegExp(`--ds-color-surface-${nombre}\\s*:\\s*([^;]+);`).exec(tokens);
    assert.ok(m, `falta --ds-color-surface-${nombre}`);
    return m[1].trim();
  };
  // Acepta las dos formas que usa la paleta: `#rrggbb` y `rgba(r, g, b, a)`.
  // El alfa se ignora a proposito: superficie y pozo comparten alfa (0.92) y
  // fondo, asi que comparar los canales crudos conserva el orden real.
  const canal = (valor) => {
    const hexMatch = /^#([0-9a-f]{6})$/i.exec(valor);
    const n = hexMatch
      ? [0, 2, 4].map((i) => parseInt(hexMatch[1].slice(i, i + 2), 16))
      : valor.match(/[\d.]+/g).slice(0, 3).map(Number);
    return (0.2126 * n[0]) + (0.7152 * n[1]) + (0.0722 * n[2]);
  };

  // Claro: hundir es oscurecer, y #f4f4f5 ya baja respecto a #ffffff.
  assert.ok(
    canal(hex('well-light')) < canal(hex('light')),
    'el pozo claro debe ser mas oscuro que la superficie clara',
  );
  // Oscuro: hundir TAMBIEN es oscurecer. Aqui estaba el defecto.
  assert.ok(
    canal(hex('well-dark')) < canal(hex('dark')),
    'el pozo oscuro debe ser mas oscuro que la superficie oscura (no «raised»)',
  );

  // Y el alias activo se declara en los dos temas, o uno se queda sin pozo.
  assert.match(temaOscuro, /--ds-active-surface-well\s*:\s*var\(--ds-color-surface-well-dark\)/);
  assert.match(temaClaro, /--ds-active-surface-well\s*:\s*var\(--ds-color-surface-well-light\)/);
});

test('F20: anillo de foco doble — halo del fondo + marca', () => {
  assert.match(tokens, /--ds-shadow-focus\s*:\s*0 0 0 2px var\(--ds-active-bg-canvas\),\s*0 0 0 4px var\(--ds-active-focus-ring\)/);
});

test('F29: scroll teñido con tokens del tema', () => {
  assert.match(core, /scrollbar-color\s*:\s*var\(--ds-active-border-control\)\s+transparent/);
});
