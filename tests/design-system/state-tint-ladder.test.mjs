import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// Complemento estatico de tests/browser/state-tint-ladder.mjs. Aquel mide el
// valor resuelto y detecta desviaciones; este lee el texto del CSS y detecta la
// duplicacion, que es lo que el valor no puede ver: una copia fiel de la
// formula resuelve exactamente igual que el token.
//
// El origen del problema era ese: /programacion-intermedia y /programa-general
// escribian la misma escalera con los mismos porcentajes en dos hojas, y siete
// de los ocho tintes de Intermedia eran el mismo hex que los de General.

const LADDER_STEPS = [
  '--ds-state-tint-red-1',
  '--ds-state-tint-red-3',
  '--ds-state-tint-red-5',
  '--ds-state-tint-red-6',
  '--ds-state-tint-amber-1',
  '--ds-state-tint-amber-2',
  '--ds-state-tint-amber-4',
  '--ds-state-tint-amber-5',
  '--ds-state-tint-green-2',
  '--ds-state-tint-green-5',
  '--ds-state-tint-green-6',
  '--ds-state-tint-teal-2',
  '--ds-state-tint-teal-5',
  '--ds-state-tint-neutral-quiet',
  '--ds-state-tint-neutral-flat',
  '--ds-state-tint-violet-pdc',
  '--ds-state-tint-red-pdc',
  '--ds-state-tint-orange-pdc',
  '--ds-state-tint-amber-pdc',
  '--ds-state-tint-green-pdc',
  '--ds-state-tint-blue-pdc',
  '--ds-state-tint-neutral-pdc',
];

test('la escalera de tintes vive en la capa de tokens', async () => {
  const tokens = await read('public/css/tokens.css');
  for (const step of LADDER_STEPS) {
    assert.match(
      tokens,
      new RegExp(`${step}:`),
      `la escalera no declara ${step} en public/css/tokens.css`,
    );
  }
});

// El tinte de estado es una mezcla del tono semantico contra la superficie
// elevada. Esa firma -`--ds-color-state-*-text` mezclado con
// `--ds-active-surface-raised`- es la que solo debe existir en la capa de
// tokens: si reaparece en una hoja de modulo, la escalera volvio a duplicarse.
// Se acota con `[^;]` y no con `[^)]`: los operandos van dentro de `var(...)`,
// asi que la firma contiene parentesis anidados y una clase que excluya `)` no
// llega nunca al segundo operando -es decir, no puede fallar nunca-. El punto y
// coma es el unico limite fiable de una declaracion.
const TINT_RECIPE = /color-mix\([^;]*--ds-color-state-\w+-text[^;]*--ds-active-surface-raised[^;]*/g;

for (const sheet of ['public/css/programacion-intermedia.css', 'public/css/programa-general.css']) {
  test(`${sheet} no reescribe la formula de la escalera`, async () => {
    const css = await read(sheet);
    const duplicated = css.match(TINT_RECIPE) ?? [];
    assert.deepEqual(
      duplicated,
      [],
      `${sheet} recalcula ${duplicated.length} tinte(s) en vez de consumir --ds-state-tint-*`,
    );
  });
}
