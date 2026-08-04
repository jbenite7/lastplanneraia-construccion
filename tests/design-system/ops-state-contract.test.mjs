import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// El renderer de la celda de estado necesita saber, para cada estado del modulo,
// que matiz y que nivel le corresponden. Ese mapa ya vive en el contrato, asi
// que la tabla del JS es una proyeccion suya y no una segunda fuente: si se
// separan, el chip declara un estado que el contrato no reconoce y el color deja
// de significar lo que el laboratorio documenta.
//
// La union se hace por `key` -el vocabulario con el que el modulo nombra sus
// estados- y no por etiqueta, porque las etiquetas difieren entre los dos lados:
// el JS dice 'Ejecución Pendiente' donde el contrato dice 'En Ejecución
// Pendiente', y varias cambian de mayusculas.

function parsePresentation(source) {
  const block = source.match(/var statePresentation = \{([\s\S]*?)\n {2}\};/);
  assert.ok(block, 'no se encontró `var statePresentation` en el módulo');
  const entries = [...block[1].matchAll(
    /'?([\w-]+)'?:\s*\{\s*level:\s*'([\w-]+)',\s*hue:\s*'([\w-]+)'\s*\}/g,
  )];
  return Object.fromEntries(entries.map(([, key, level, hue]) => [key, { level, hue }]));
}

test('la tabla de presentación de Intermedia proyecta el contrato', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const module = semantics.moduleMappings.find((m) => m.module === 'programacion-intermedia');
  const presentation = parsePresentation(
    await read('public/js/modules/programacion_intermedia/hot.js'),
  );

  for (const state of module.states) {
    assert.ok(state.key, `el contrato no declara \`key\` para «${state.label}»`);
    const declared = presentation[state.key];
    assert.ok(declared, `el módulo no presenta el estado \`${state.key}\` del contrato`);
    assert.deepEqual(
      declared,
      { level: state.level, hue: state.hue },
      `\`${state.key}\` («${state.label}») difiere entre el módulo y el contrato`,
    );
  }

  // `neutral` es del modulo y no del contrato: es el estado por defecto de una
  // fila sin clasificar, no un estado operativo que la leyenda ofrezca filtrar.
  const extra = Object.keys(presentation)
    .filter((key) => key !== 'neutral' && !module.states.some((s) => s.key === key));
  assert.deepEqual(extra, [], `el módulo presenta estados que el contrato no declara: ${extra}`);
});

test('la hoja del módulo no vuelve a pintar el chip por nombre de estado', async () => {
  const css = await read('public/css/programacion-intermedia.css');

  // El fondo y la forma del chip son del design system
  // (`public/css/design-system/components/ops-state-chip.css`). El modulo ya
  // no declara una copia local de `.pi-page .ops-state-chip` -ni siquiera sin
  // `background`-: la unica fuente de verdad es el componente compartido.
  // Si reaparece aqui, gana por capa -`module` va despues de `components`- y
  // la primitiva se vuelve inerte, que es exactamente el fallo que motivo
  // esta migracion.
  const chipBlock = css.match(/\.pi-page \.ops-state-chip \{([^}]*)\}/)?.[1];
  assert.equal(
    chipBlock,
    undefined,
    '.pi-page .ops-state-chip reapareció localmente; debe vivir solo en el componente compartido',
  );

  const perState = css.match(/\.pi-state-[\w-]+ \.ops-state-chip \{[^}]*(background|color|border-color)[^}]*\}/g) ?? [];
  assert.deepEqual(
    perState,
    [],
    `${perState.length} regla(s) vuelven a colorear el chip por nombre de estado`,
  );
});

test('la tabla de presentación de Semanal proyecta el contrato', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const module = semantics.moduleMappings.find((m) => m.module === 'programacion-semanal');
  const presentation = parsePresentation(
    await read('public/js/modules/programacion_semanal/hot.js'),
  );

  for (const state of module.states) {
    assert.ok(state.key, `el contrato no declara \`key\` para «${state.label}»`);
    const declared = presentation[state.key];
    assert.ok(declared, `el módulo no presenta el estado \`${state.key}\` del contrato`);
    assert.deepEqual(
      declared,
      { level: state.level, hue: state.hue },
      `\`${state.key}\` («${state.label}») difiere entre el módulo y el contrato`,
    );
  }

  // `neutral` es del modulo y no del contrato: es el estado por defecto de una
  // fila sin clasificar (`ps-no-activa`), no un estado operativo que la leyenda
  // ofrezca filtrar.
  const extra = Object.keys(presentation)
    .filter((key) => key !== 'neutral' && !module.states.some((s) => s.key === key));
  assert.deepEqual(extra, [], `el módulo presenta estados que el contrato no declara: ${extra}`);
});

test('la hoja de Semanal no vuelve a pintar el chip por nombre de estado', async () => {
  const css = await read('public/css/programacion-semanal.css');

  // Mismo motivo que en Intermedia: el fondo del chip es del design system, y
  // si el modulo lo declara otra vez -aunque sea `transparent`, que tambien es
  // una declaracion de `background`- la primitiva queda inerte sobre esta
  // superficie.
  const chipBlock = css.match(/\.ps-page \.ops-state-chip \{([^}]*)\}/)?.[1] ?? '';
  assert.ok(chipBlock, 'no se encontró la regla base de .ops-state-chip');
  assert.doesNotMatch(
    chipBlock,
    /(^|[\s;])background(-color)?\s*:/,
    '.ps-page .ops-state-chip volvió a declarar `background`; eso tapa la primitiva del DS',
  );

  // Decisión del usuario (2026-08-03, task 16 revisión): "Uniforme 900 en
  // todo: PS pierde su 800" — paridad literal absoluta entre PG/PI/PS, sin
  // gradación de peso por nivel. Ningún selector `.ps-alert-*` puede volver a
  // tocar `.ops-state-chip` (ni color/background -ya prohibido antes-, ni
  // peso -ahora tampoco-): el chip nace en 900 desde el componente compartido
  // y ningún nivel lo aligera.
  const perStateBlocks = [...css.matchAll(
    /([^{}]*\.ps-alert-[\w-]+[^{}]*\.ops-state-chip[^{}]*)\{([^}]*)\}/g,
  )];
  assert.deepEqual(
    perStateBlocks.map(([selector]) => selector.trim()),
    [],
    `${perStateBlocks.length} regla(s) de .ops-state-chip por nivel sobreviven; la paridad 900 uniforme decidida el 2026-08-03 exige que no quede ninguna`,
  );
});

test('la tabla de presentación de Programa General proyecta el contrato', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const module = semantics.moduleMappings.find((m) => m.module === 'programa-general');
  const presentation = parsePresentation(
    await read('public/js/modules/programa_general/hot.js'),
  );

  for (const state of module.states) {
    assert.ok(state.key, `el contrato no declara \`key\` para «${state.label}»`);
    const declared = presentation[state.key];
    assert.ok(declared, `el módulo no presenta el estado \`${state.key}\` del contrato`);
    assert.deepEqual(
      declared,
      { level: state.level, hue: state.hue },
      `\`${state.key}\` («${state.label}») difiere entre el módulo y el contrato`,
    );
  }

  const extra = Object.keys(presentation)
    .filter((key) => key !== 'neutral' && !module.states.some((s) => s.key === key));
  assert.deepEqual(extra, [], `el módulo presenta estados que el contrato no declara: ${extra}`);
});

test('la hoja de Programa General no vuelve a pintar el chip por nombre de estado', async () => {
  const css = await read('public/css/design-system/components/ops-state-chip.css');

  // El fondo del chip es del design system. Si alguien lo declara otra vez en la
  // forma del chip, la primitiva que pinta por matiz se vuelve inerte.
  const chipBlock = css.match(/\.ops-state-chip \{([^}]*)\}/)?.[1] ?? '';
  assert.ok(chipBlock, 'no se encontró la regla base de .ops-state-chip');
  assert.doesNotMatch(
    chipBlock,
    /(^|[\s;])background(-color)?\s*:/,
    '.ops-state-chip volvió a declarar `background`; eso tapa la primitiva del DS',
  );

  const modulo = await read('public/css/programa-general.css');
  const perState = modulo.match(/\.pg-state-[\w-]+ \.ops-state-chip \{[^}]*(background|color|border-color)[^}]*\}/g) ?? [];
  assert.deepEqual(
    perState,
    [],
    `${perState.length} regla(s) vuelven a colorear el chip por nombre de estado`,
  );
});
