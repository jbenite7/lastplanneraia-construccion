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

  // El fondo del chip es del design system. Si el modulo lo declara otra vez
  // gana por capa -`module` va despues de `components`- y la primitiva se
  // vuelve inerte, que es exactamente el fallo que motivo esta migracion.
  const chipBlock = css.match(/\.pi-page \.ops-state-chip \{([^}]*)\}/)?.[1] ?? '';
  assert.ok(chipBlock, 'no se encontró la regla base de .ops-state-chip');
  assert.doesNotMatch(
    chipBlock,
    /(^|[\s;])background(-color)?\s*:/,
    '.pi-page .ops-state-chip volvió a declarar `background`; eso tapa la primitiva del DS',
  );

  const perState = css.match(/\.pi-state-[\w-]+ \.ops-state-chip \{[^}]*(background|color|border-color)[^}]*\}/g) ?? [];
  assert.deepEqual(
    perState,
    [],
    `${perState.length} regla(s) vuelven a colorear el chip por nombre de estado`,
  );
});
