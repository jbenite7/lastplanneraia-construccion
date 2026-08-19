import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// Complemento estatico de tests/browser/state-tint-ladder.mjs. Aquel mide el
// valor resuelto en el navegador; este lee el texto del CSS y del contrato, que
// es donde se ven dos cosas que el pixel no puede ver: la DUPLICACION de la
// receta (una copia fiel resuelve igual que el token) y el nombre de un token
// que ya no existe (`var()` sin fallback pinta transparente y nadie se entera).
//
// LA PALETA ES NOMINAL, NO ORDINAL. Hubo una version con tres pasos por
// familia, derivados del ancla bajando croma con la luminosidad fija. Medida en
// el navegador, la separacion maxima entre dos pasos consecutivos de una misma
// familia era 1,012:1 de contraste y dE-OK 0,0168 -por debajo del umbral de
// percepcion-, o sea que los tres pasos eran un solo color. El efecto practico:
// dos entradas de leyenda que el usuario filtra por separado pintaban fondos
// bit-identicos.
//
// Por eso quedan las ocho anclas y nada mas, y la regla que se deriva de ello:
// ningun modulo puede tener dos estados que compartan matiz.

// Los siete hex que /pdc eligio y midio a mano, mas #134841 para teal. Se fijan
// aqui, en el gate que corre sin Docker: antes este archivo aceptaba cualquier
// hex (`#[0-9a-f]{6}`), asi que un ancla equivocada pasaba CI y solo la cazaba
// la suite de navegador.
const ANCHORS = {
  violet: '#33204a',
  red: '#431414',
  orange: '#452a0d',
  amber: '#3a3a0f',
  green: '#173d26',
  blue: '#17334f',
  teal: '#134841',
  neutral: '#2b2f2d',
};

const TINT_NAMES = Object.keys(ANCHORS).map((hue) => `--ds-state-tint-${hue}`);

// Toda hoja que pueda nombrar un tinte de estado. El guard anterior solo leia
// `tokens.css`, que es justo el archivo donde un consumidor NO vive: el nombre
// roto aparece en la hoja del modulo, no en la de los tokens.
const SHEETS = [
  'public/css/tokens.css',
  'public/css/styles.css',
  'public/css/programacion-intermedia.css',
  'public/css/programa-general.css',
  'public/css/programacion-semanal.css',
  'public/css/design-system/components/states-feedback.css',
  'public/css/design-system/adapters/legacy-bridge.css',
];

test('la paleta de matices declara las ocho anclas con su hex exacto', async () => {
  const tokens = await read('public/css/tokens.css');
  for (const [hue, hex] of Object.entries(ANCHORS)) {
    const declaration = tokens.match(new RegExp(`--ds-state-tint-${hue}:\\s*([^;]+);`))?.[1]?.trim();
    assert.equal(
      declaration,
      hex,
      `--ds-state-tint-${hue} deberia ser el ancla ${hex} y vale: ${declaration}`,
    );
  }
});

// La paleta se cierra por arriba y por abajo: ni falta ninguna de las ocho ni
// sobra una novena. Un `--ds-state-tint-*` nuevo es un matiz nuevo, y eso es un
// cambio de vocabulario que se decide en el contrato, no en una hoja.
test('la paleta no publica ningun tinte de estado fuera de las ocho anclas', async () => {
  const tokens = await read('public/css/tokens.css');
  const declared = [...tokens.matchAll(/^\s*(--ds-state-tint-[\w-]+)\s*:/gm)].map(([, name]) => name);
  assert.deepEqual([...declared].sort(), [...TINT_NAMES].sort());
});

// Un consumidor que se quede apuntando a un nombre retirado recibe la cadena
// vacia y pinta transparente sin que nadie se entere. Se escanean TODAS las
// hojas y se acepta unicamente la lista blanca de ocho: cualquier otra cosa que
// empiece por `--ds-state-tint-` es un nombre que no existe.
//
// No hay lookahead ni excepcion para prosa. El guard anterior llevaba un
// `(?!`)` para dejar pasar una cita en un comentario, y esa puerta valia igual
// para una reintroduccion futura. Si un comentario necesita hablar de un nombre
// retirado, lo describe en palabras; el token literal no vuelve a escribirse.
test('ninguna hoja nombra un tinte de estado que no exista', async () => {
  const allowed = new Set(TINT_NAMES);
  const offenders = [];
  for (const sheet of SHEETS) {
    const css = await read(sheet);
    for (const [, name] of css.matchAll(/(--ds-state-tint-[a-z0-9-]+)/g)) {
      if (!allowed.has(name)) offenders.push(`${sheet}: ${name}`);
    }
  }
  assert.deepEqual(
    offenders,
    [],
    `nombres de tinte que no existen en la paleta:\n  ${offenders.join('\n  ')}`,
  );
});

// El tinte de estado se derivaba mezclando el tono semantico contra la
// superficie elevada. Esa firma -`--ds-color-state-*-text` mezclado con
// `--ds-active-surface-raised`- es la que solo debe existir en la capa de
// tokens: si reaparece en una hoja de modulo, la paleta volvio a duplicarse.
// Se acota con `[^;]` y no con `[^)]`: los operandos van dentro de `var(...)`,
// asi que la firma contiene parentesis anidados y una clase que excluya `)` no
// llega nunca al segundo operando -es decir, no puede fallar nunca-. El punto y
// coma es el unico limite fiable de una declaracion.
const TINT_RECIPE = /color-mix\([^;]*--ds-color-state-\w+-text[^;]*--ds-active-surface-raised[^;]*/g;

for (const sheet of ['public/css/programacion-intermedia.css', 'public/css/programa-general.css']) {
  test(`${sheet} no reescribe la formula de la paleta`, async () => {
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
      new RegExp(`${tint.replace(/[-]/g, '\\-')}\\)`),
      `[data-aia-hue="${id}"] deberia usar ${tint}, que es el que declara el contrato`,
    );
  }
  // El selector de urgencia no se toca: es el que asierta states-feedback.test.mjs
  // y el que sostiene la regla «urgencia now siempre usa critical».
  assert.match(css, /\[data-aia-severity="high"\]\[data-aia-urgency="now"\]/);
});

// El catalogo del contrato y la paleta del CSS son la misma lista o no son
// nada: si el contrato nombra un matiz que la paleta no publica, el laboratorio
// pinta un swatch vacio y los modulos que lo declaren pintan transparente.
test('el catalogo de matices del contrato es exactamente la paleta', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  assert.deepEqual(
    semantics.hues.map(({ tint }) => tint).sort(),
    [...TINT_NAMES].sort(),
  );
});

// LA REGLA. Un matiz = un estado. Si dos estados de un mismo modulo declaran el
// mismo matiz pintan el mismo fondo, y con la paleta reducida a anclas ya no
// hay un escalon con el que separarlos.
//
// LO QUE CUENTA ES LA FASE, NO EL MODULO. Reescrito el 2026-08-19: hasta hoy
// este guard comparaba los diez estados de /programacion-semanal como si
// convivieran, y tapaba el resultado con una lista de matices tolerados
// -['amber','green','red']- que mezclaba dos cosas distintas: repeticiones
// INOCUAS entre fases y colisiones REALES dentro de una fase.
//
// Las dos mitades no conviven nunca: `stateMachine.js:58` resuelve `calificacion`
// si la semana esta confirmada y `programacion` si no, asi que en pantalla hay
// cinco estados, no diez. Que `red` lo usen «RC con restricciones» (programacion)
// y «Incumplida (RC)» (calificacion) no confunde a nadie.
//
// Las dos colisiones que SI eran reales -«Condiciones Pendientes» con «Por
// Comprometer», e «Incumplida» con «Sin Calificar», cada una dentro de su propia
// fase- se resolvieron el 2026-08-19 por decision del usuario: «Por Comprometer»
// pasa a violeta y «Sin Calificar» a gris.
//
// Asi que la excepcion no se renueva: se sustituye por el predicado correcto, que
// es mas estricto que el anterior y no necesita lista de tolerados. La fase se
// deduce del prefijo de `key` (`prog-` / `cal-`); un modulo sin prefijos de fase
// se compara entero, que es el caso de todos los demas.
const FASE_POR_PREFIJO = (key) => (/^(prog|cal)-/.exec(key) ?? [null, ''])[1];

test('ningun modulo asigna el mismo matiz a dos estados de la misma fase', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const collisions = {};
  for (const { module, states } of semantics.moduleMappings) {
    const porFase = new Map();
    for (const { hue, key } of states) {
      if (hue === undefined) continue;
      const fase = FASE_POR_PREFIJO(key ?? '');
      if (!porFase.has(fase)) porFase.set(fase, new Map());
      const seen = porFase.get(fase);
      seen.set(hue, (seen.get(hue) ?? 0) + 1);
    }
    const repeated = [...porFase.entries()]
      .flatMap(([fase, seen]) => [...seen.entries()]
        .filter(([, n]) => n > 1)
        .map(([hue]) => (fase ? `${fase}:${hue}` : hue)))
      .sort();
    if (repeated.length) collisions[module] = repeated;
  }
  assert.deepEqual(collisions, {});
});

// Los tres modulos operativos que declaran matiz estado por estado tienen que
// declararlo en TODOS: un estado sin matiz cae al token de su nivel y vuelve a
// confundirse con los otros de su nivel, que es justo lo que el eje resuelve.
for (const [module, count] of [['pdc', 7], ['programacion-intermedia', 8], ['programa-general', 7]]) {
  test(`${module} declara matiz en sus ${count} estados`, async () => {
    const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
    const states = semantics.moduleMappings.find((m) => m.module === module).states;
    assert.equal(states.length, count);
    const sinMatiz = states.filter(({ hue }) => hue === undefined).map(({ label }) => label);
    assert.deepEqual(sinMatiz, []);
  });
}

// /pdc no duplicaba la formula -sus siete estados eran hex literales-, asi que
// el guard de duplicacion no lo alcanza. Lo que hay que garantizar aqui es lo
// contrario: que los alias del modulo apunten a la paleta en vez de repetir el
// valor, para que /pdc deje de ser un sistema de color paralelo. Violeta,
// naranja y azul entraron al design system desde este modulo, asi que el hex
// vive ahora en la paleta y `--pdc-*` solo lo nombra.
const PDC_BACKGROUNDS = [
  'missing',
  'critical',
  'delayed',
  'completed-late',
  'completed-ontime',
  'active',
  'not-started',
];

test('los fondos de /pdc nombran la paleta en vez de repetir el hex', async () => {
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
    `estos fondos de /pdc siguen con valor propio en vez de consumir la paleta:\n  ${literal.join('\n  ')}`,
  );
});
