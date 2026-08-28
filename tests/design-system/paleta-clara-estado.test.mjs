import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('public/css/tokens.css', 'utf8');

// D7+D5 spec temas / F-tabla spec forma: valores calibrados t=0,5 (azul t=0,7).
const TINTES = {
  '--ds-state-tint-red-light': '#f6c3c3',
  '--ds-state-tint-orange-light': '#f8c9a5',
  '--ds-state-tint-amber-light': '#ffecb2',
  '--ds-state-tint-violet-light': '#dad4f5',
  '--ds-state-tint-green-light': '#c2e2d3',
  '--ds-state-tint-blue-light': '#c1d5ec',
  '--ds-state-tint-teal-light': '#c8efec',
  '--ds-state-tint-neutral-light': '#e4e4e7',
};

// Chips claros: principal del manual; ámbar/teal/azul en peldaño oscuro (D6/D7).
const CHIPS = {
  '--ds-state-solid-red-light': '#c62828',
  '--ds-state-solid-orange-light': '#b55211',
  '--ds-state-solid-amber-light': '#a16207',
  '--ds-state-solid-violet-light': '#6752bf',
  '--ds-state-solid-green-light': '#1a5633',
  '--ds-state-solid-blue-light': '#2a5a8f',
  '--ds-state-solid-teal-light': '#007a71',
  '--ds-state-solid-neutral-light': '#52525b',
};

// Textos sobre chips claros: blanco (#ffffff) en todas las variantes.
const TEXT = {
  '--ds-state-solid-red-light-text': '#ffffff',
  '--ds-state-solid-orange-light-text': '#ffffff',
  '--ds-state-solid-amber-light-text': '#ffffff',
  '--ds-state-solid-violet-light-text': '#ffffff',
  '--ds-state-solid-green-light-text': '#ffffff',
  '--ds-state-solid-blue-light-text': '#ffffff',
  '--ds-state-solid-teal-light-text': '#ffffff',
  '--ds-state-solid-neutral-light-text': '#ffffff',
};

for (const [token, hex] of Object.entries({ ...TINTES, ...CHIPS, ...TEXT })) {
  test(`${token} declara ${hex}`, () => {
    const re = new RegExp(`${token}\\s*:\\s*${hex}\\b`, 'i');
    assert.match(css, re, `${token} debe declarar ${hex} en tokens.css`);
  });
}

// Contrastes: texto blanco de chip >= 4.5 (AA); chip vs su tinte >= 3 (WCAG 1.4.11).
const lin = (c) => { const v = c / 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
  return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
};
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };

const PAREJAS = [
  ['#c62828', '#f6c3c3'], ['#b55211', '#f8c9a5'], ['#a16207', '#ffecb2'],
  ['#6752bf', '#dad4f5'], ['#1a5633', '#c2e2d3'], ['#2a5a8f', '#c1d5ec'],
  ['#007a71', '#c8efec'], ['#52525b', '#e4e4e7'],
];
for (const [chip, tinte] of PAREJAS) {
  test(`chip ${chip} alcanza 3:1 contra su tinte ${tinte}`, () => {
    assert.ok(ratio(chip, tinte) >= 3, `${ratio(chip, tinte).toFixed(2)}:1`);
  });
  test(`blanco alcanza 4.5:1 sobre chip ${chip}`, () => {
    assert.ok(ratio('#ffffff', chip) >= 4.5, `${ratio('#ffffff', chip).toFixed(2)}:1`);
  });
}
