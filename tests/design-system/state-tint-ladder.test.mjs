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

// El eje de matiz necesita una primitiva que lo pinte, o `data-aia-hue` es un
// atributo decorativo. `.aia-chip` ya cubre el chip y esta aprobada; lo que
// faltaba era que la capa de componentes supiera traducir un matiz a su tinte.
test('la capa de componentes traduce data-aia-hue a su tinte', async () => {
  const css = await read('public/css/design-system/components/states-feedback.css');
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  for (const { id, tint } of semantics.hues) {
    const rule = css.match(new RegExp(`\\[data-aia-hue="${id}"\\][^{]*\\{([^}]*)\\}`))?.[1];
    assert.ok(rule, `states-feedback.css no traduce [data-aia-hue="${id}"]`);
    assert.match(
      rule,
      new RegExp(tint.replace(/[-]/g, '\\-')),
      `[data-aia-hue="${id}"] deberia usar ${tint}, que es el que declara el contrato`,
    );
  }
  // El selector de urgencia no se toca: es el que asierta states-feedback.test.mjs
  // y el que sostiene la regla «urgencia now siempre usa critical».
  assert.match(css, /\[data-aia-severity="high"\]\[data-aia-urgency="now"\]/);
});

// /pdc no duplicaba la formula -sus siete estados eran hex literales-, asi que
// el guard de duplicacion no lo alcanza. Lo que hay que garantizar aqui es lo
// contrario: que los alias del modulo apunten a la escalera en vez de repetir
// el valor, para que /pdc deje de ser un sistema de color paralelo. Violeta,
// naranja y azul entraron al design system desde este modulo, asi que el hex
// vive ahora en la escalera y `--pdc-*` solo lo nombra.
const PDC_BACKGROUNDS = [
  'missing',
  'critical',
  'delayed',
  'completed-late',
  'completed-ontime',
  'active',
  'not-started',
];

test('los fondos de /pdc nombran la escalera en vez de repetir el hex', async () => {
  const tokens = await read('public/css/tokens.css');
  const literal = [];
  for (const state of PDC_BACKGROUNDS) {
    const declaration = tokens.match(new RegExp(`--pdc-${state}-bg:\\s*([^;]+);`))?.[1]?.trim();
    assert.ok(declaration, `public/css/tokens.css no declara --pdc-${state}-bg`);
    if (!declaration.includes('--ds-state-tint-')) literal.push(`--pdc-${state}-bg: ${declaration}`);
  }
  assert.deepEqual(
    literal,
    [],
    `estos fondos de /pdc siguen con valor propio en vez de consumir la escalera:\n  ${literal.join('\n  ')}`,
  );
});
