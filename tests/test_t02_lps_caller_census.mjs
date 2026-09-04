// tests/test_t02_lps_caller_census.mjs
//
// Censo exacto de propiedad del contexto LPS compartido (T02, Tarea 1). No es una
// verificación de comportamiento: congela QUIÉN usa hoy VIEW-28
// (views/partials/drawer_unificado.php), su script (public/js/modules/lps_drawer.js),
// las rutas de comentarios/crisis, el cliente de notificaciones y el CSS adaptador —
// como datos estructurados, no como un conteo en prosa (AGENTS.md §Task 1 del plan
// docs/superpowers/plans/2026-08-30-t02-contexto-lps-react.md).
//
// Medido contra el código el 2026-08-31 con:
//   rg -n "drawer_unificado|lps_drawer\.js|LPSContextualDrawer" views public/js src frontend tests
//   rg -n "/api/lps/(comments|crisis)" public/index.php public/js src tests e2e
//   rg -n "/api/notifications/(unread|read)" public/index.php public/js src frontend tests
//
// T02-R (Tarea 12) reutiliza este mismo archivo: sube el censo esperado de VIEW-28 de
// cuatro a cero y verifica que ya no queden. Hasta entonces el conteo de cuatro es el
// contrato correcto — no un olvido.

import assert from 'node:assert/strict';
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { test } from 'node:test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const read = (rel) => readFileSync(path.join(ROOT, rel), 'utf8');
const exists = (rel) => existsSync(path.join(ROOT, rel));

// ---------------------------------------------------------------------------
// Barrido real de código de producción (no una lista escrita a mano) — mismo
// método que tests/design-system/shell-runtime-react-caller-census.test.mjs
// (T01 Tarea 1), adaptado a tres señales de texto en vez de un nombre de archivo.
// Nunca escanea tests/ ni e2e/: no son producción y contendrían sus propias
// menciones documentales de las señales, inflando el censo con ruido que no
// representa un llamador real.
// ---------------------------------------------------------------------------

const PRODUCTION_DIRS = ['views', 'public', 'src', 'frontend/src', 'admin'];
const EXCLUDED_DIR_NAMES = new Set([
  'vendor', 'node_modules', '.git', 'dist', 'build', 'coverage',
  '__screenshots__', 'test-results', 'playwright-report',
]);
const SCAN_EXTENSIONS = new Set(['.php', '.js', '.mjs', '.cjs', '.ts', '.tsx', '.jsx', '.css']);

function listSourceFiles(dir) {
  const absolute = path.join(ROOT, dir);
  let entries;
  try {
    entries = readdirSync(absolute);
  } catch {
    return [];
  }
  const files = [];
  for (const entry of entries) {
    if (EXCLUDED_DIR_NAMES.has(entry)) continue;
    const entryRelative = path.join(dir, entry);
    const entryAbsolute = path.join(ROOT, entryRelative);
    const info = statSync(entryAbsolute);
    if (info.isDirectory()) {
      files.push(...listSourceFiles(entryRelative));
    } else if (SCAN_EXTENSIONS.has(path.extname(entry))) {
      files.push(entryRelative);
    }
  }
  return files;
}

function isCommentOnly(line) {
  const trimmed = line.trim();
  return trimmed.startsWith('//') || trimmed.startsWith('#') || trimmed.startsWith('*')
    || (trimmed.startsWith('<!--') && trimmed.endsWith('-->'))
    // Comentario de bloque de una sola línea, p. ej. el JSDoc de procedencia que usa
    // frontend/src/shared/lps/dominio/*.ts: `/** Puerto de ... (lps_drawer.js:539-542). */`.
    // Sin esta rama esas líneas cuelan como "llamador" porque empiezan con `/**`, no con
    // `*` a secas — mismo criterio que tests/design-system/shell-runtime-react-caller-census.test.mjs,
    // extendido para cubrir la sintaxis de comentario que ese censo hermano no necesitaba.
    || (trimmed.startsWith('/*') && trimmed.endsWith('*/'));
}

/**
 * Censa cada línea, en cualquier archivo de producción, que mencione `signal` y no
 * sea puramente un comentario, excluyendo los archivos en `selfFiles` (donde la señal
 * es la propia definición/nombre del archivo, no una llamada externa).
 *
 * @returns {Array<{file: string, line: number, text: string}>}
 */
function censarSenal(signal, selfFiles = []) {
  const callers = [];
  for (const dir of PRODUCTION_DIRS) {
    for (const file of listSourceFiles(dir)) {
      if (selfFiles.includes(file)) continue;
      const contents = read(file);
      contents.split('\n').forEach((line, index) => {
        if (!line.includes(signal)) return;
        if (isCommentOnly(line)) return;
        callers.push({ file, line: index + 1, text: line.trim() });
      });
    }
  }
  return callers;
}

function archivosUnicos(callers) {
  return [...new Set(callers.map((c) => c.file))].sort();
}

// ---------------------------------------------------------------------------
// Inventario estructurado de propietarios (fuente única de verdad del censo)
// ---------------------------------------------------------------------------

const VIEW28_PARTIAL = 'views/partials/drawer_unificado.php';
const LPS_DRAWER_JS = 'public/js/modules/lps_drawer.js';

/**
 * Los cuatro consumidores productivos de VIEW-28, uno por módulo. S25 es el dashboard
 * de escalamientos: no tiene malla (Handsontable), así que su adaptador vive inline en
 * la propia vista en vez de en un `hot.js` de módulo.
 */
const VIEW28_CONSUMERS = [
  {
    modulo: 'PG',
    vista: 'views/programa-general/programa_general.view.php',
    includeStatement: "include __DIR__ . '/../partials/drawer_unificado.php';",
    initSource: 'public/js/modules/programa_general/hot.js',
    initCall: "window.LPSContextualDrawer.init(hot, 'programa-general', classifyPGRow);",
    updateContextSource: LPS_DRAWER_JS,
  },
  {
    modulo: 'PI',
    vista: 'views/programacion-intermedia/programacion_intermedia.view.php',
    includeStatement: "include __DIR__ . '/../partials/drawer_unificado.php';",
    initSource: 'public/js/modules/programacion_intermedia/hot.js',
    initCall: "window.LPSContextualDrawer.init(hot, 'programacion-intermedia', getStateView);",
    updateContextSource: LPS_DRAWER_JS,
  },
  {
    modulo: 'PS',
    vista: 'views/programacion-semanal/programacion_semanal.view.php',
    includeStatement: "include __DIR__ . '/../partials/drawer_unificado.php';",
    initSource: 'public/js/modules/programacion_semanal/hot.js',
    initCall: "window.LPSContextualDrawer.init(hot, 'programacion-semanal', function(rowData) {",
    updateContextSource: LPS_DRAWER_JS,
  },
  {
    modulo: 'S25',
    vista: 'views/dashboard/escalamientos.php',
    // S25 es el único consumidor fuera del árbol propio de su módulo (vive en
    // views/dashboard/, no en views/<modulo>/), así que su include usa PROJECT_ROOT
    // en vez de __DIR__ relativo — no es una inconsistencia, es la ubicación real.
    includeStatement: "include PROJECT_ROOT . '/views/partials/drawer_unificado.php';",
    initSource: 'views/dashboard/escalamientos.php',
    initCall: "LPSContextualDrawer.init(gridlessAdapter, 'dashboard', {});",
    updateContextSource: 'views/dashboard/escalamientos.php',
    updateContextCall: "LPSContextualDrawer.updateContext(currentSelectedCardData, 'dashboard');",
  },
];

/** Registro de rutas del cajón LPS (comentarios y crisis) en public/index.php. */
const LPS_ROUTE_REGISTRATIONS = [
  { method: 'get', path: '/api/lps/comments', handler: 'comments' },
  { method: 'post', path: '/api/lps/comments', handler: 'addComment' },
  { method: 'post', path: '/api/lps/comments/add', handler: 'addComment', alias: true },
  { method: 'post', path: '/api/lps/crisis', handler: 'registerCrisis' },
  { method: 'post', path: '/api/lps/crisis/register', handler: 'registerCrisis', alias: true },
  { method: 'post', path: '/api/lps/crisis/close', handler: 'closeCrisis' },
];

/** Registro de rutas de notificaciones en public/index.php. */
const NOTIFICATION_ROUTE_REGISTRATIONS = [
  { method: 'get', path: '/api/notifications/unread', handler: 'getUnread' },
  { method: 'post', path: '/api/notifications/read', handler: 'markAsRead' },
];

/** Cliente legacy de notificaciones y su único punto de carga conocido. */
const NOTIFICATION_CLIENT = {
  file: 'public/js/components/notifications.js',
  loader: 'public/js/cargarDatosGeneralesPagina2.js',
  loaderGuard: 'if (!document.querySelector(\'script[src*="notifications.js"]\'))',
};

/** CSS exclusivo del cajón LPS, importado una sola vez por el entrypoint del design system. */
const ADAPTER_CSS = {
  file: 'public/css/design-system/adapters/lps-drawer.css',
  importedBy: [
    'public/css/aia-design-system.css',
    'public/css/design-system/entrypoints/core.css',
  ],
  importSpecifier: '/css/design-system/adapters/lps-drawer.css?v=1.1.0',
};

// ---------------------------------------------------------------------------
// VIEW-28: exactamente cuatro consumidores productivos
// ---------------------------------------------------------------------------

test('VIEW-28 tiene exactamente los cuatro consumidores productivos esperados (PG/PI/PS/S25)', () => {
  assert.equal(VIEW28_CONSUMERS.length, 4, 'el censo estructurado debe listar cuatro módulos, ni más ni menos');
  assert.deepEqual(
    VIEW28_CONSUMERS.map((c) => c.modulo).sort(),
    ['PG', 'PI', 'PS', 'S25'],
  );
});

for (const consumer of VIEW28_CONSUMERS) {
  test(`${consumer.modulo}: incluye VIEW-28 (${VIEW28_PARTIAL})`, () => {
    const vista = read(consumer.vista);
    assert.ok(
      vista.includes(consumer.includeStatement),
      `esperaba en ${consumer.vista}:\n  ${consumer.includeStatement}`,
    );
  });

  test(`${consumer.modulo}: carga lps_drawer.js`, () => {
    const vista = read(consumer.vista);
    assert.match(vista, /<script src="\/js\/modules\/lps_drawer\.js\?v=20260722shell1">/);
  });

  test(`${consumer.modulo}: llama LPSContextualDrawer.init con su clave de módulo exacta`, () => {
    const source = read(consumer.initSource);
    assert.ok(
      source.includes(consumer.initCall),
      `esperaba encontrar en ${consumer.initSource}:\n  ${consumer.initCall}`,
    );
  });
}

test('S25 también llama LPSContextualDrawer.updateContext (dashboard sin malla)', () => {
  const s25 = VIEW28_CONSUMERS.find((c) => c.modulo === 'S25');
  const source = read(s25.updateContextSource);
  assert.ok(source.includes(s25.updateContextCall));
});

test('PG/PI/PS delegan updateContext al propio lps_drawer.js (llamada interna, no en el consumidor)', () => {
  const drawer = read(LPS_DRAWER_JS);
  assert.match(drawer, /LPSContextualDrawer\.updateContext\(rowData, moduleKey\);/);
});

// Conjuntos exactos esperados por señal, medidos contra el código en producción
// (views/, public/, src/, frontend/src/, admin/) el 2026-08-31. `drawer_unificado.php`
// y `lps_drawer.js` se excluyen de su propia búsqueda (`selfFiles`): la única mención
// de esas cadenas dentro de sí mismos es su propio encabezado/nombre, no una llamada.
const EXPECTED_INCLUDE_CALLERS = [
  'views/dashboard/escalamientos.php',
  'views/programa-general/programa_general.view.php',
  'views/programacion-intermedia/programacion_intermedia.view.php',
  'views/programacion-semanal/programacion_semanal.view.php',
].sort();

const EXPECTED_SCRIPT_TAG_CALLERS = [...EXPECTED_INCLUDE_CALLERS];

const EXPECTED_GLOBAL_USAGE_CALLERS = [
  'public/js/modules/programa_general/hot.js',
  'public/js/modules/programacion_intermedia/hot.js',
  'public/js/modules/programacion_semanal/hot.js',
  'views/dashboard/escalamientos.php',
].sort();

test('barrido real: exactamente los cuatro archivos esperados incluyen VIEW-28 (drawer_unificado)', () => {
  const callers = censarSenal('drawer_unificado', [VIEW28_PARTIAL]);
  assert.deepEqual(
    archivosUnicos(callers),
    EXPECTED_INCLUDE_CALLERS,
    `censo de "drawer_unificado" cambió: ${JSON.stringify(callers, null, 2)} — si es un consumidor nuevo legítimo, `
      + 'actualiza EXPECTED_INCLUDE_CALLERS/VIEW28_CONSUMERS a propósito; si T02-R ya retiró uno, súbele el conteo esperado.',
  );
});

test('barrido real: exactamente los cuatro archivos esperados cargan lps_drawer.js', () => {
  const callers = censarSenal('lps_drawer.js', [LPS_DRAWER_JS]);
  assert.deepEqual(
    archivosUnicos(callers),
    EXPECTED_SCRIPT_TAG_CALLERS,
    `censo de "lps_drawer.js" cambió: ${JSON.stringify(callers, null, 2)}`,
  );
});

test('barrido real: exactamente los cuatro archivos esperados usan LPSContextualDrawer fuera de su propia definición', () => {
  const callers = censarSenal('LPSContextualDrawer', [LPS_DRAWER_JS]);
  assert.deepEqual(
    archivosUnicos(callers),
    EXPECTED_GLOBAL_USAGE_CALLERS,
    `censo de "LPSContextualDrawer" cambió: ${JSON.stringify(callers, null, 2)}`,
  );
});

test('no existe un quinto consumidor de VIEW-28, lps_drawer.js o LPSContextualDrawer fuera del censo', () => {
  // Unión de las tres señales, medida por barrido real (no una lista escrita a mano):
  // debe ser exactamente el conjunto de archivos ya cubiertos por VIEW28_CONSUMERS.
  const todosLosArchivos = new Set([
    ...censarSenal('drawer_unificado', [VIEW28_PARTIAL]).map((c) => c.file),
    ...censarSenal('lps_drawer.js', [LPS_DRAWER_JS]).map((c) => c.file),
    ...censarSenal('LPSContextualDrawer', [LPS_DRAWER_JS]).map((c) => c.file),
  ]);
  const esperado = new Set([
    ...VIEW28_CONSUMERS.map((c) => c.vista),
    ...VIEW28_CONSUMERS.map((c) => c.initSource),
  ]);
  assert.deepEqual(
    [...todosLosArchivos].sort(),
    [...esperado].sort(),
    'el barrido real de producción encontró un archivo fuera del censo estructurado — actualízalo a propósito, no lo silencies',
  );
});

// ---------------------------------------------------------------------------
// Rutas: registros y alias
// ---------------------------------------------------------------------------

test('todas las rutas LPS del censo están registradas en public/index.php', () => {
  const routerSource = read('public/index.php');
  for (const route of LPS_ROUTE_REGISTRATIONS) {
    const pattern = new RegExp(
      `\\$router->${route.method}\\(['"]${route.path.replace(/\//g, '\\/')}['"],\\s*\\[\\\\App\\\\Controllers\\\\Api\\\\LpsApiController::class,\\s*'${route.handler}'\\]\\)`,
    );
    assert.match(routerSource, pattern, `esperaba el registro de ${route.method.toUpperCase()} ${route.path}`);
  }
});

test('los alias de escritura (comments/add, crisis/register) apuntan al mismo handler que su ruta canónica', () => {
  const byHandler = new Map();
  for (const route of LPS_ROUTE_REGISTRATIONS) {
    if (route.method !== 'post') continue;
    if (!byHandler.has(route.handler)) byHandler.set(route.handler, []);
    byHandler.get(route.handler).push(route.path);
  }
  assert.deepEqual(byHandler.get('addComment').sort(), ['/api/lps/comments', '/api/lps/comments/add']);
  assert.deepEqual(byHandler.get('registerCrisis').sort(), ['/api/lps/crisis', '/api/lps/crisis/register']);
});

test('las rutas de notificaciones del censo están registradas en public/index.php', () => {
  const routerSource = read('public/index.php');
  for (const route of NOTIFICATION_ROUTE_REGISTRATIONS) {
    const pattern = new RegExp(
      `\\$router->${route.method}\\(['"]${route.path.replace(/\//g, '\\/')}['"],\\s*\\[\\\\App\\\\Controllers\\\\Core\\\\NotificationController::class,\\s*'${route.handler}'\\]\\)`,
    );
    assert.match(routerSource, pattern, `esperaba el registro de ${route.method.toUpperCase()} ${route.path}`);
  }
});

// ---------------------------------------------------------------------------
// Cliente de notificaciones
// ---------------------------------------------------------------------------

test('el cliente legacy de notificaciones llama exactamente las dos rutas del censo', () => {
  const client = read(NOTIFICATION_CLIENT.file);
  assert.match(client, /fetch\('\/api\/notifications\/unread'/);
  assert.match(client, /fetch\('\/api\/notifications\/read'/);
});

test('notifications.js se carga una sola vez, guardado contra doble inyección, desde el loader común', () => {
  const loader = read(NOTIFICATION_CLIENT.loader);
  assert.ok(loader.includes(NOTIFICATION_CLIENT.loaderGuard));
  assert.match(loader, /scriptNotif\.src = '\/public\/js\/components\/notifications\.js\?v=legacy4';/);
});

// ---------------------------------------------------------------------------
// CSS adaptador exclusivo
// ---------------------------------------------------------------------------

test('el CSS adaptador del cajón LPS existe y se importa desde los puntos de entrada esperados', () => {
  assert.ok(exists(ADAPTER_CSS.file));
  for (const importer of ADAPTER_CSS.importedBy) {
    const source = read(importer);
    assert.ok(
      source.includes(ADAPTER_CSS.importSpecifier),
      `esperaba que ${importer} importara ${ADAPTER_CSS.importSpecifier}`,
    );
  }
});
