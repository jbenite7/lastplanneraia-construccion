import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

// Las cuatro rejillas de la cascada comparten el mismo chip con el mismo papel.
// Si una sola deja de anunciarlo, guardar en esa pantalla no produce ningun
// anuncio para quien usa lector de pantalla — que es exactamente lo que pasaba
// en tres de las cuatro hasta el 2026-08-10.
const VISTAS_CASCADA = [
  'views/programa-general/programa_general.view.php',
  'views/programa-general-actualizar/programaGeneralActualizar.view.php',
  'views/programacion-intermedia/programacion_intermedia.view.php',
  'views/programacion-semanal/programacion_semanal.view.php',
];

test('el chip de guardado se anuncia en las cuatro rejillas de la cascada', async () => {
  for (const vista of VISTAS_CASCADA) {
    const html = await read(vista);
    const chip = html.match(/<span[^>]*id="save-status"[^>]*>/);
    assert.ok(chip, `${vista}: no declara #save-status`);
    assert.match(chip[0], /role="status"/, `${vista}: #save-status sin role="status"`);
  }
});
