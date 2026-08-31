// Censo estático de llamadores reales de VIEW-26, VIEW-29 y VIEW-30 — Tarea 1 del plan
// docs/superpowers/plans/2026-08-30-t01-shell-runtime-react.md.
//
// Este test NO retira nada. Es la fotografía inicial que exige el plan ("The initial
// expected count is measured from the code, never guessed"): congela cuántos sitios de
// producción incluyen/renderizan/referencian cada vista legada hoy, para que T01-R (Tarea 11,
// en una sesión autorizada aparte) pueda comparar contra un censo nuevo y decidir si el
// consumidor real llegó a cero. Un conteo que sube o baja sin que este test se actualice a
// propósito es la señal de que alguien tocó una de las tres vistas sin pasar por el censo.
//
// Método: recorre `src/`, `public/`, `views/` y `admin/` (nunca `tests/`, que no es producción)
// buscando el nombre de archivo de cada vista. Una línea cuenta como llamador real salvo que
// sea puramente un comentario (empieza, ya recortada, con `//`, `#` o `*`) — así el `require`
// indirecto de VIEW-26 (variable `$view` construida y usada en la línea siguiente) sí cuenta,
// y las dos menciones en comentario de VIEW-30 (BiViewController.php, PlanComprasController.php)
// no inflan el número.

import assert from 'node:assert/strict';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import test from 'node:test';

const ROOT = process.cwd();
const PRODUCTION_DIRS = ['src', 'public', 'views', 'admin'];
const EXCLUDED_DIR_NAMES = new Set(['vendor', 'node_modules', '.git', 'app']);

function listPhpFiles(dir) {
  const absolute = join(ROOT, dir);
  let entries;
  try {
    entries = readdirSync(absolute);
  } catch {
    return [];
  }

  const files = [];
  for (const entry of entries) {
    if (EXCLUDED_DIR_NAMES.has(entry)) continue;
    const entryRelative = join(dir, entry);
    const entryAbsolute = join(ROOT, entryRelative);
    const info = statSync(entryAbsolute);
    if (info.isDirectory()) {
      files.push(...listPhpFiles(entryRelative));
    } else if (entry.endsWith('.php')) {
      files.push(entryRelative);
    }
  }
  return files;
}

function isCommentOnly(line) {
  const trimmed = line.trim();
  return trimmed.startsWith('//') || trimmed.startsWith('#') || trimmed.startsWith('*');
}

/**
 * Censa cada línea, en cualquier archivo PHP de producción distinto del propio archivo
 * de la vista, que mencione su nombre de archivo y no sea puramente un comentario.
 *
 * @returns {{count: number, callers: Array<{file: string, line: number, text: string}>}}
 */
function censarLlamadores(nombreArchivoVista, rutaPropiaRelativa) {
  const callers = [];
  for (const dir of PRODUCTION_DIRS) {
    for (const file of listPhpFiles(dir)) {
      if (file === rutaPropiaRelativa) continue;
      const contents = readFileSync(join(ROOT, file), 'utf8');
      const lines = contents.split('\n');
      lines.forEach((line, index) => {
        if (!line.includes(nombreArchivoVista)) return;
        if (isCommentOnly(line)) return;
        callers.push({ file, line: index + 1, text: line.trim() });
      });
    }
  }
  return { count: callers.length, callers };
}

test('VIEW-26 (views/errors/error.view.php) tiene exactamente 1 llamador real de producción hoy', () => {
  const censo = censarLlamadores('error.view.php', relative(ROOT, join(ROOT, 'views/errors/error.view.php')));
  assert.equal(
    censo.count,
    1,
    `censo cambió: ${JSON.stringify(censo.callers, null, 2)} — actualiza este test a propósito, no lo silencies`,
  );
  assert.equal(censo.callers[0].file, 'src/Core/ErrorPage.php');
});

test('VIEW-29 (views/partials/head_brand.php) tiene exactamente 20 llamadores reales de producción hoy', () => {
  const censo = censarLlamadores('head_brand.php', 'views/partials/head_brand.php');
  assert.equal(
    censo.count,
    20,
    `censo cambió: ${JSON.stringify(censo.callers, null, 2)} — actualiza este test a propósito, no lo silencies`,
  );
  // VIEW-26 es a su vez llamador de VIEW-29: retirar una no retira la otra automáticamente.
  assert.ok(censo.callers.some((c) => c.file === 'views/errors/error.view.php'));
});

test('VIEW-30 (views/partials/shell_sidebar.php) tiene exactamente 14 llamadores reales de producción hoy', () => {
  const censo = censarLlamadores('shell_sidebar.php', 'views/partials/shell_sidebar.php');
  assert.equal(
    censo.count,
    14,
    `censo cambió: ${JSON.stringify(censo.callers, null, 2)} — actualiza este test a propósito, no lo silencies`,
  );
  // Las menciones en comentario de BiViewController.php y PlanComprasController.php no son
  // llamadores reales — documentan por qué `sidebarActive` debe casar con un id del partial.
  assert.ok(!censo.callers.some((c) => c.file === 'src/Controllers/Bi/BiViewController.php'));
  assert.ok(!censo.callers.some((c) => c.file === 'src/Controllers/Gestion/PlanComprasController.php'));
});
