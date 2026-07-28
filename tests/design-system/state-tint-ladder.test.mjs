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

// Ocho familias por tres pasos, sin huecos. La numeracion vieja iba del 1 al 6
// con saltos y hasta cuatro pasos por familia; desde que las anclas de /pdc
// mandan, cada familia publica exactamente tres.
const FAMILIES = ['violet', 'red', 'orange', 'amber', 'green', 'blue', 'teal', 'neutral'];
const LADDER_STEPS = FAMILIES.flatMap((family) => [1, 2, 3].map((step) => `--ds-state-tint-${family}-${step}`));

test('la escalera de tintes vive en la capa de tokens', async () => {
  const tokens = await read('public/css/tokens.css');
  assert.equal(LADDER_STEPS.length, 24, 'ocho familias por tres pasos');
  for (const step of LADDER_STEPS) {
    assert.match(
      tokens,
      new RegExp(`${step}:`),
      `la escalera no declara ${step} en public/css/tokens.css`,
    );
  }
  // La numeracion vieja tiene que desaparecer del CSS, no convivir: un consumidor
  // que se quedara apuntando a `--ds-state-tint-red-6` recibiria la cadena vacia
  // y pintaria transparente sin que nadie se entere.
  const retired = tokens.match(/--ds-state-tint-(?:\w+-pdc|neutral-(?:quiet|flat)|\w+-[4-9])\b(?!`)/g) ?? [];
  assert.deepEqual(retired, [], `nombres retirados que siguen en tokens.css: ${retired.join(', ')}`);
});

// El eje de la escalera es el croma con la luminosidad fija. Escrito en CSS eso
// es una sola forma -`oklch(from <ancla> l calc(c * k) h)`-, y es lo que hay que
// impedir que se sustituya por una mezcla en sRGB, que fue justo el defecto que
// agrisaba la escalera anterior.
test('los pasos 2 y 3 se derivan del ancla bajando croma en OKLCH', async () => {
  const tokens = await read('public/css/tokens.css');
  const chromatic = FAMILIES.filter((family) => family !== 'neutral');
  assert.equal(chromatic.length, 7, 'siete familias cromaticas');
  for (const family of chromatic) {
    const anchor = tokens.match(new RegExp(`--ds-state-tint-${family}-1:\\s*(#[0-9a-f]{6});`))?.[1];
    assert.ok(anchor, `--ds-state-tint-${family}-1 deberia ser el hex del ancla`);
    for (const step of [2, 3]) {
      const declaration = tokens.match(new RegExp(`--ds-state-tint-${family}-${step}:\\s*([^;]+);`))?.[1]?.trim();
      assert.ok(declaration, `tokens.css no declara --ds-state-tint-${family}-${step}`);
      assert.match(
        declaration,
        new RegExp(`^oklch\\(from var\\(--ds-state-tint-${family}-1\\) l calc\\(c \\* 0\\.\\d+\\) h\\)$`),
        `--ds-state-tint-${family}-${step} deberia derivarse del ancla bajando croma, y vale: ${declaration}`,
      );
    }
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
