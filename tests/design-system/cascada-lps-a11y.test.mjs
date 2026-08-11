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

test('los chips de filtro de Programacion Semanal declaran su estado', async () => {
  const js = await read('public/js/modules/programacion_semanal/hot.js');
  // renderAlertLegend arma el chip como cadena; el atributo tiene que salir de ahi.
  const legend = js.match(/function renderAlertLegend\(\)[\s\S]*?\n  \}/);
  assert.ok(legend, 'no se encontro renderAlertLegend()');
  assert.match(legend[0], /aria-pressed/, 'los chips de PS no emiten aria-pressed');

  // Y el estado tiene que seguir al filtro, no quedarse fijo en el markup inicial.
  const sync = js.match(/function syncLegendVisualState\(\)[\s\S]*?\n  \}/);
  assert.ok(sync, 'no se encontro syncLegendVisualState()');
  assert.match(sync[0], /aria-pressed/, 'syncLegendVisualState no actualiza aria-pressed');
});

test('el boton bloqueado de /profesionales es alcanzable pero no actua', async () => {
  const vista = await read('views/profesionales/profesionales.view.php');
  // aria-disabled devuelve el boton a pulsable: sin guard en el manejador, un
  // boton inerte se convierte en un boton que borra.
  if (!/aria-disabled/.test(vista)) return; // aun no migrado
  assert.match(vista, /aria-disabled=["']true["']/);
  assert.match(vista, /getAttribute\(['"]aria-disabled['"]\)|\.ariaDisabled/,
    'hay aria-disabled pero ningun guard que lo compruebe en el manejador');
});
