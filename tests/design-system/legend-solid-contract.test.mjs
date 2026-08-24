import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// La leyenda existe para describir la tabla. Si consume otra familia de tokens
// que los chips que describe, deja de describirla: ese fue el defecto que
// reporto el usuario con captura el 2026-08-21.
const ESTADOS_PI = [
  'blocked-overdue-critical', 'blocked-overdue', 'blocked-due',
  'alert-1-week', 'alert-2-3-weeks', 'alert-4-6-weeks',
  'execution-blocked', 'liberated-control',
];

test('cada item de leyenda de PI declara un fondo de la familia solida', async () => {
  const css = await read('public/css/styles.css');
  for (const estado of ESTADOS_PI) {
    const regla = new RegExp(
      `\\.pi-legend \\.pdc-legend-item\\.${estado}\\s*\\{[^}]*\\}`, 'm');
    const bloque = css.match(regla);
    assert.ok(bloque, `no hay regla de leyenda para ${estado}`);
    assert.match(bloque[0], /--ds-state-solid-/,
      `la leyenda de ${estado} no usa la familia solida`);
    assert.doesNotMatch(bloque[0], /--ds-state-tint-/,
      `la leyenda de ${estado} sigue usando la familia de tintes`);
  }
});

// Estados de Programa General segun moduleMappings de state-semantics.json
// (module: "programa-general"). El chip de leyenda vive en `.pg-filter-chip`,
// no en `.pdc-legend-item` (PG construyo su propio selector), pero el
// contrato es el mismo: debe pintar el solido de su hue, no el tinte.
const ESTADOS_PG = [
  'actividad-futura', 'en-curso', 'terminada', 'fuera-de-ventana',
  'debe-iniciar', 'atrasada', 'sin-datos',
];

test('cada item de leyenda de PG declara un fondo de la familia solida', async () => {
  const css = await read('public/css/programa-general.css');
  for (const estado of ESTADOS_PG) {
    const regla = new RegExp(
      `\\.pg-filter-chip\\.${estado}\\s*\\{[^}]*\\}`, 'm');
    const bloque = css.match(regla);
    assert.ok(bloque, `no hay regla de leyenda para ${estado}`);
    assert.match(bloque[0], /--ds-state-solid-/,
      `la leyenda de ${estado} no usa la familia solida`);
    assert.doesNotMatch(bloque[0], /--ds-state-tint-/,
      `la leyenda de ${estado} sigue usando la familia de tintes`);
  }
});
