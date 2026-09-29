import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';

const root = path.resolve(import.meta.dirname, '..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');
const exists = (relativePath) => fs.existsSync(path.join(root, relativePath));

const traceability = {
  toolbar: [
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaToolbar.test.tsx',
      name: 'renderiza título, badge de semana vigente y controles principales',
    },
  ],
  states: [
    {
      file: 'frontend/src/modules/programa-general/domain/presentacionEstados.test.ts',
      name: 'retorna configuracion canónica para Atrasada',
    },
    {
      file: 'frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx',
      name: 'monta la página mostrando el título, semana y grilla de actividades',
    },
  ],
  filters: [
    {
      file: 'frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx',
      name: 'filtra actividades por texto en la barra de búsqueda',
    },
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaFilters.test.tsx',
      name: 'permite remover filtros individuales o todos a la vez',
    },
  ],
  responsive: [
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaCards.test.tsx',
      name: 'renderiza cabeceras de capítulo y tarjetas móviles con métricas',
    },
    {
      file: 'tests/browser/s05-programa-general-react.spec.mjs',
      name: 'sin desbordamiento · tema ${tema} · ${modo} columnas · riel ${riel}',
    },
  ],
  editing: [
    {
      file: 'frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx',
      name: 'guarda cambios desde el drawer y muestra notificación toast',
    },
    {
      file: 'tests/browser/s05-programa-general-react.spec.mjs',
      name: 'edita datos en el Drawer, guarda cambios y recibe notificación toast',
    },
  ],
  batch: [
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaToolbar.test.tsx',
      name: 'dispara la actualización masiva solo para quien tiene permiso de lote',
    },
    {
      file: 'frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx',
      name: 'quita el aviso de datos desactualizados tras reconciliar un lote',
    },
  ],
  cut: [
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaToolbar.test.tsx',
      name: 'dispara onDownloadCorteXlsx y maneja estado de generación activa',
    },
  ],
  drawer: [
    {
      file: 'frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx',
      name: 'abre el Drawer Contextual LPS al hacer clic en una fila de actividad',
    },
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaDrawer.test.tsx',
      name: 'cierra el drawer al presionar Escape o botón Descartar',
    },
  ],
  themes: [
    {
      file: 'tests/browser/s05-programa-general-react.spec.mjs',
      name: 'muestra el punto neutral de Terminada en señales y Estado · ${tema}',
    },
  ],
  a11y: [
    {
      file: 'tests/browser/s05-programa-general-react.spec.mjs',
      name: 'cajón que dice la verdad: SOS real, barra visible, descarte confirmado, foco y axe sin críticos',
    },
    {
      file: 'frontend/src/modules/programa-general/components/ProgramaDrawer.test.tsx',
      name: 'al abrir, el foco entra al cajón',
    },
  ],
};

// T13 autoriza retirar estas pruebas legacy después de conservar, en esta tabla, sus
// reemplazos por comportamiento. Esta lista debe seguir completa aunque los archivos se
// eliminen: así un retiro nuevo no puede entrar sin trazar su cobertura React/PHP.
const legacyAssertionRetirements = [
  { file: 'tests/browser/programa-general-legend-hue.mjs', categories: ['filters', 'themes'] },
  { file: 'tests/browser/programa-general-legend-modal-dark.mjs', categories: ['themes', 'a11y'] },
  { file: 'tests/browser/programa-general-runtime-requests.mjs', categories: ['batch', 'cut', 'drawer'] },
  { file: 'tests/browser/programa-general-state-hue.mjs', categories: ['states', 'themes'] },
  { file: 'tests/browser/programa-general.visual.mjs', categories: ['responsive', 'themes', 'a11y'] },
  { file: 'tests/browser/design-system-body-canvas-dark.mjs', categories: ['responsive', 'themes'] },
  { file: 'tests/design-system/cascada-lps-a11y.test.mjs', categories: ['responsive', 'a11y'] },
  { file: 'tests/design-system/legend-solid-contract.test.mjs', categories: ['filters', 'states'] },
  { file: 'tests/design-system/ops-state-contract.test.mjs', categories: ['states', 'batch', 'drawer'] },
  { file: 'tests/design-system/pg-severity-rail.test.mjs', categories: ['states'] },
  { file: 'tests/design-system/programa-general-runtime-requests.test.mjs', categories: ['batch', 'cut', 'drawer'] },
  { file: 'tests/design-system/state-tint-ladder.test.mjs', categories: ['states', 'themes'] },
];

const deletedLegacyTestFiles = [
  'tests/browser/programa-general-legend-hue.mjs',
  'tests/browser/programa-general-legend-modal-dark.mjs',
  'tests/browser/programa-general-runtime-requests.mjs',
  'tests/browser/programa-general-state-hue.mjs',
  'tests/browser/programa-general.visual.mjs',
  'tests/design-system/pg-severity-rail.test.mjs',
  'tests/design-system/programa-general-runtime-requests.test.mjs',
];

const sharedTestBranchRetirements = [
  { file: 'tests/browser/design-system-body-canvas-dark.mjs', categories: ['responsive', 'themes'] },
  { file: 'tests/design-system/cascada-lps-a11y.test.mjs', categories: ['responsive', 'a11y'] },
  { file: 'tests/design-system/legend-solid-contract.test.mjs', categories: ['filters', 'states'] },
  { file: 'tests/design-system/ops-state-contract.test.mjs', categories: ['states', 'batch', 'drawer'] },
  { file: 'tests/design-system/state-tint-ladder.test.mjs', categories: ['states', 'themes'] },
];

const expectedLegacyTestFiles = [
  'tests/browser/programa-general-legend-hue.mjs',
  'tests/browser/programa-general-legend-modal-dark.mjs',
  'tests/browser/programa-general-runtime-requests.mjs',
  'tests/browser/programa-general-state-hue.mjs',
  'tests/browser/programa-general.visual.mjs',
  'tests/browser/design-system-body-canvas-dark.mjs',
  'tests/design-system/cascada-lps-a11y.test.mjs',
  'tests/design-system/legend-solid-contract.test.mjs',
  'tests/design-system/ops-state-contract.test.mjs',
  'tests/design-system/pg-severity-rail.test.mjs',
  'tests/design-system/programa-general-runtime-requests.test.mjs',
  'tests/design-system/state-tint-ladder.test.mjs',
];

const legacyProductionFiles = [
  'views/programa-general/programa_general.view.php',
  'public/js/modules/programa_general/hot.js',
  'public/css/programa-general.css',
  'src/Controllers/Programacion/ProgramaGeneralController.php',
];

function listProductionSources(relativeDirectory) {
  const absoluteDirectory = path.join(root, relativeDirectory);
  let entries;
  try {
    entries = fs.readdirSync(absoluteDirectory);
  } catch {
    return [];
  }

  const excludedDirectories = new Set([
    '.git',
    'vendor',
    'node_modules',
    'dist',
    'build',
    'coverage',
    '__screenshots__',
    'test-results',
    'playwright-report',
  ]);
  const sourceExtensions = new Set(['.php', '.js', '.mjs', '.cjs', '.ts', '.tsx', '.jsx', '.css', '.html']);
  const files = [];

  for (const entry of entries) {
    if (excludedDirectories.has(entry)) continue;
    const relativePath = path.join(relativeDirectory, entry);
    const absolutePath = path.join(root, relativePath);
    const info = fs.statSync(absolutePath);
    if (info.isDirectory()) {
      files.push(...listProductionSources(relativePath));
    } else if (sourceExtensions.has(path.extname(entry))) {
      files.push(relativePath);
    }
  }

  return files;
}

function registeredRoute(indexSource, method, route, controller, handler) {
  const escapedRoute = route.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const routePattern = new RegExp(`\\$router->${method}\\(['"]${escapedRoute}['"]`);
  return indexSource.split('\n').some((line) => (
    routePattern.test(line)
    && line.includes(`${controller}::class`)
    && line.includes(`'${handler}'`)
  ));
}

test('cada aserción legacy retirada tiene un reemplazo React/PHP nombrado', () => {
  assert.deepEqual(
    legacyAssertionRetirements.map(({ file }) => file).sort(),
    expectedLegacyTestFiles.sort(),
    'la tabla debe incluir todos los tests legacy enumerados por la Tarea 13',
  );

  for (const [category, replacements] of Object.entries(traceability)) {
    assert.ok(replacements.length > 0, `falta un reemplazo nombrado para ${category}`);
    for (const replacement of replacements) {
      assert.ok(exists(replacement.file), `falta el test reemplazo ${replacement.file}`);
      assert.ok(
        read(replacement.file).includes(replacement.name),
        `${replacement.file} debe conservar el caso nombrado: ${replacement.name}`,
      );
    }
  }

  for (const retirement of legacyAssertionRetirements) {
    assert.ok(retirement.categories.length > 0, `${retirement.file} no tiene trazabilidad`);
    for (const category of retirement.categories) {
      assert.ok(traceability[category]?.length, `${retirement.file}: categoría sin reemplazo ${category}`);
    }
  }

  for (const file of deletedLegacyTestFiles) {
    assert.ok(
      legacyAssertionRetirements.some(({ file: mapped }) => mapped === file),
      `${file} no puede borrarse antes de entrar en la tabla de trazabilidad`,
    );
    assert.equal(exists(file), false, `${file} debe retirarse después de trazar sus reemplazos`);
  }
});

test('las suites compartidas conservan los módulos vigentes y quitan solo la rama PG PHP', () => {
  for (const retirement of sharedTestBranchRetirements) {
    assert.ok(retirement.categories.length > 0, `${retirement.file} no tiene trazabilidad`);
    assert.ok(exists(retirement.file), `${retirement.file} comparte cobertura de otros módulos y debe conservarse`);
    const source = read(retirement.file);
    assert.doesNotMatch(
      source,
      /views\/programa-general\/programa_general\.view\.php|public\/js\/modules\/programa_general\/hot\.js|public\/css\/programa-general\.css|\/programa-general\/(?:filtros|set-filtro)/,
    );
  }
});

test('el GET canónico sigue en React y ya no hay rutas PHP de la vista o sus filtros', () => {
  const spaRouter = read('src/Core/SpaRouter.php');
  const indexSource = read('public/index.php');

  assert.match(
    spaRouter,
    /RUTAS_EXACTAS_MIGRADAS[\s\S]*?['"]\/programa-general['"]|['"]\/programa-general['"][\s\S]*?RUTAS_EXACTAS_MIGRADAS/,
    'SpaRouter debe conservar /programa-general como ruta React',
  );
  assert.doesNotMatch(indexSource, /\$router->(?:get|post)\(['"]\/programa-general(?:\/[^'"]*)?['"]/, 'public/index.php no debe registrar una vista PHP ni los filtros legacy de PG');
});

test('los archivos y referencias de producción exclusivos de VIEW-34 ya no existen', () => {
  for (const file of legacyProductionFiles) {
    assert.equal(exists(file), false, `${file} solo se retira después del censo de cero referencias`);
  }

  const retiredFiles = new Set(legacyProductionFiles.map((file) => file.replaceAll(path.sep, '/')));
  const forbiddenReference = /\bVIEW-34\b|views\/programa-general\/programa_general\.view\.php|public\/js\/modules\/programa_general\/hot\.js|public\/css\/programa-general\.css|ProgramaGeneralController|\/programa-general\/filtros|\/programa-general\/set-filtro/;
  const references = [];

  for (const directory of ['public', 'src', 'views', 'frontend/src']) {
    for (const file of listProductionSources(directory)) {
      const normalizedPath = file.replaceAll(path.sep, '/');
      if (retiredFiles.has(normalizedPath)) continue;
      const lines = read(file).split('\n');
      lines.forEach((line, index) => {
        if (forbiddenReference.test(line)) references.push(`${file}:${index + 1}: ${line.trim()}`);
      });
    }
  }

  assert.deepEqual(references, [], `quedan referencias activas de producción:\n${references.join('\n')}`);
});

test('el manifiesto describe la superficie React, sin vendors ni fuentes del legacy', () => {
  const manifest = JSON.parse(read('docs/design-system/manifests/programa-general.json'));
  const requiredSources = [
    'frontend/src/modules/programa-general/ProgramaGeneralPage.tsx',
    'frontend/src/modules/programa-general/programa-general.css',
    'frontend/src/modules/programa-general/api/programaGeneralApi.ts',
    'frontend/src/modules/programa-general/components/ProgramaToolbar.tsx',
    'frontend/src/modules/programa-general/components/ProgramaTable.tsx',
    'frontend/src/modules/programa-general/components/ProgramaCards.tsx',
    'src/Controllers/Api/ProgramaGeneralContextApiController.php',
    'src/Services/ProgramaGeneralContextService.php',
  ];
  for (const source of requiredSources) {
    assert.ok(manifest.sources.includes(source), `el manifiesto debe inventariar ${source}`);
  }

  assert.ok(manifest.routes.includes('/programa-general'), 'el manifiesto debe conservar la ruta React canónica');
  assert.ok(manifest.tests.includes('frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx'));
  assert.ok(manifest.tests.includes('tests/browser/s05-programa-general-react.spec.mjs'));
  assert.ok(manifest.tests.includes('tests/browser/programa-general-design-system.mjs'));
  assert.ok(manifest.tests.includes('tests/test_programa_general_action_policy.php'));
  assert.ok(manifest.tests.includes('tests/test_programa_general_context_contract.php'));
  assert.ok(manifest.tests.includes('tests/test_programa_general_restriction_contract.php'));
  assert.ok(manifest.tests.includes('tests/test_programa_general_sprint_contract.mjs'));
  assert.ok(!manifest.sources.some((source) => legacyProductionFiles.includes(source)));

  const retiredVendors = /handsontable-adapter|handsontable|bootstrap|jquery|toastr|font-awesome|jquery-ui/i;
  assert.deepEqual(
    manifest.vendors.filter((vendor) => retiredVendors.test(vendor)),
    [],
    'el manifiesto S05 no debe declarar vendors del legado',
  );
  for (const role of ['A', 'D', 'R', 'DCV', 'OT', 'G', 'S', 'SG', 'V', 'C-denied']) {
    assert.ok(manifest.roles.includes(role), `el manifiesto debe declarar el rol ${role}`);
  }
  assert.equal(manifest.persistence.filters, 'URL query parameters');
  assert.equal(manifest.persistence.projectContext, 'session project and active week');
  assert.equal(manifest.persistence.returnMarker, 'scoped LPS return marker');
});

test('todas las rutas General, LPS y Report compartidas siguen registradas', () => {
  const indexSource = read('public/index.php');
  const retainedRoutes = [
    ['get', '/api/programa-general/context', 'ProgramaGeneralContextApiController', 'show'],
    ['get', '/api/general/list', 'GeneralApiController', 'list'],
    ['post', '/api/general/list', 'GeneralApiController', 'list'],
    ['get', '/api/general/restriction-config', 'GeneralApiController', 'restrictionConfig'],
    ['post', '/api/general/update', 'GeneralApiController', 'update'],
    ['post', '/api/general/update-batch', 'GeneralApiController', 'updateBatch'],
    ['post', '/api/general/import', 'GeneralApiController', 'importExcel'],
    ['post', '/api/general/delete-update', 'GeneralApiController', 'deleteUpdate'],
    ['get', '/api/general/codigos', 'GeneralApiController', 'getCodigos'],
    ['post', '/api/general/auto-associate', 'GeneralApiController', 'autoAssociate'],
    ['post', '/api/general/decision-log', 'GeneralApiController', 'decisionLog'],
    ['get', '/reportes/{tipo}', 'ReportController', 'generate'],
    ['post', '/reportes/{tipo}', 'ReportController', 'generate'],
    ['get', '/api/lps/comments', 'LpsApiController', 'comments'],
    ['post', '/api/lps/comments', 'LpsApiController', 'addComment'],
    ['post', '/api/lps/comments/add', 'LpsApiController', 'addComment'],
    ['post', '/api/lps/crisis', 'LpsApiController', 'registerCrisis'],
    ['post', '/api/lps/crisis/register', 'LpsApiController', 'registerCrisis'],
    ['post', '/api/lps/crisis/close', 'LpsApiController', 'closeCrisis'],
  ];

  for (const [method, route, controller, handler] of retainedRoutes) {
    assert.ok(
      registeredRoute(indexSource, method, route, controller, handler),
      `debe seguir registrada ${method.toUpperCase()} ${route} → ${controller}::${handler}`,
    );
  }
});
