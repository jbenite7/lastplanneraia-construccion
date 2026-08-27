import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { readdir } from 'node:fs/promises';
import test from 'node:test';
import { requiredViewports as homologationViewports } from './manifest-sources.mjs';
import { parseJobSteps } from './workflow-contract-parser.mjs';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));
// Piloto y laboratorio comparten la misma matriz de viewports requeridos, y
// esa matriz se deriva de homologation.json en vez de escribirse a mano: es la
// union de los viewports que declaran las familias gobernadas. 390x844 se
// reabrio el 2026-08-07 como soportado pero no exigido (ver
// tests/design-system/mobile-viewport-scope.test.mjs); en cuanto una familia lo
// declare, esta matriz lo recoge sola.
const requiredViewports = homologationViewports();

const viewportKey = ({ width, height }) => `${width}x${height}`;

function assertDarkPilotMatrix(scenarios, expectedPerViewport, viewports) {
  assert.deepEqual([...new Set(scenarios.map(({ theme }) => theme))], ['dark']);
  assert.deepEqual(
    [...new Set(scenarios.map(({ viewport }) => viewportKey(viewport)))].sort(),
    [...viewports].sort(),
  );

  for (const viewport of viewports) {
    assert.equal(
      scenarios.filter((scenario) => viewportKey(scenario.viewport) === viewport).length,
      expectedPerViewport,
      `dark ${viewport}`,
    );
  }
}

test('visual regression contract covers the approved laboratory matrix', async () => {
  const [source, manifest, homologation] = await Promise.all([
    read('tests/browser/design-system-lab.visual.mjs'),
    readJson('docs/design-system/manifests/laboratory.json'),
    readJson('docs/design-system/homologation.json'),
  ]);
  assert.match(source, /toHaveScreenshot/);
  assert.match(source, /MANIFEST\.scenarios/);
  const families = [...new Set(manifest.scenarios.map(({ family }) => family))];
  const familyCount = families.length;
  assert.equal(familyCount, 10);
  const declaredViewports = [...new Set(
    families.flatMap(
      (family) => homologation.families.find(({ id }) => id === family).viewports,
    ),
  )];
  assert.equal(manifest.scenarios.length, familyCount * declaredViewports.length);
  assertDarkPilotMatrix(manifest.scenarios, familyCount, declaredViewports);
});

test('visual regression contract covers the Programa General pilot matrix', async () => {
  const [source, manifest] = await Promise.all([
    read('tests/browser/programa-general.visual.mjs'),
    readJson('docs/design-system/manifests/programa-general.json'),
  ]);
  assert.match(source, /toHaveScreenshot/);
  assert.match(source, /MANIFEST\.scenarios/);
  assert.equal(manifest.scenarios.length, requiredViewports.length);
  assertDarkPilotMatrix(manifest.scenarios, 1, requiredViewports);
});

test('CI is reproducible, least-privileged and has no deployment path', async () => {
  const [workflow, compose, fixtureImage, fixture] = await Promise.all([
    read('.github/workflows/ci.yml'),
    read('docker-compose.ci.yml'),
    read('database/fixtures/design-system-ci.Dockerfile'),
    read('database/fixtures/design-system-ci.sql'),
  ]);
  assert.match(workflow, /contents:\s*read/);
  assert.match(workflow, /design-system-static/);
  assert.match(workflow, /design-system-runtime/);
  assert.doesNotMatch(workflow, /pull_request_target|deploy|production/i);
  assert.match(compose, /APP_ENV:\s*testing/);
  assert.match(compose, /dockerfile:\s*database\/fixtures\/design-system-ci\.Dockerfile/);
  assert.match(fixtureImage, /COPY database\/migrations\/20260630_global_tables_contract\.sql \/docker-entrypoint-initdb\.d\/001-global-schema\.sql/);
  assert.match(fixtureImage, /COPY database\/patches\/001_create_new_tables\.sql \/docker-entrypoint-initdb\.d\/002-rbac-schema\.sql/);
  assert.match(fixtureImage, /COPY database\/fixtures\/design-system-ci\.sql \/docker-entrypoint-initdb\.d\/003-design-system-ci\.sql/);
  // Tercera de las tres listas de SQL de CI, y la unica que se conserva duplicada a proposito.
  // Las otras dos: la lista blanca de scripts/design-system-ci-preflight.mjs (el guardarrail
  // fail-closed) y la de tests/design-system/ci-preflight.test.mjs, que desde el 2026-08-24 se
  // deriva de aquella. Esta mira el Dockerfile real por su cuenta, sin consultar a la lista
  // blanca, y por eso lo caza si las dos se desalinean.
  //
  // Al sembrar `general_flags` el 2026-08-24 el gate rechazo TRES veces seguidas, una por lista.
  // Eso es la red funcionando, no friccion que haya que quitar.
  const expectedInitOrder = [
    '001-global-schema.sql',
    '002-rbac-schema.sql',
    '003-design-system-ci.sql',
    '004-semi-auto-global.sql',
    '005-semi-auto-assistant.sql',
    '006-contract-quantities.sql',
    '007-activity-sources.sql',
    '008-bi-forecast.sql',
    '009-bi-action-queue.sql',
    '010-family-catalog-base.sql',
    '011-family-catalog-feedback.sql',
    '012-family-catalog-refactor.sql',
    '013-family-patterns.sql',
    '014-contract-defaults.sql',
    '015-contractual-aliases.sql',
    '016-equipment-review.sql',
    '017-human-decisions.sql',
    '018-design-system-ci-normalize.sql',
    '019-pdc-v2-schema.sql',
    '101-bi-view.sql',
    '102-bi-view.sql',
    '103-bi-view.sql',
    // 104 retirado el 2026-08-04 con el PDC v1: database/bi/004_bi_pdc_general.sql se borro y su
    // COPY rompia el build de la imagen de CI. La vista no tiene lectores — el informe de compras
    // se alimenta del PDC v2 (src/Services/ControlTowerService.php:522).
    '105-bi-view.sql',
    '106-bi-view.sql',
    '107-bi-view.sql',
    '108-bi-view.sql',
    '109-bi-view.sql',
    '110-bi-view.sql',
    // B-9 (2026-08-07): migracion de `general_proyectos_procesos`, ver el Dockerfile.
    '120-proyectos-lineabase.sql',
    '121-general-flags.sql',
    // linea-base-contractual (2026-08-19): la 120 CREA las columnas de linea base y no las
    // rellena. Sin esta, el CI las tiene vacias, la fecha contractual sale NULL y `baseline-drift`
    // sigue rojo aunque el calculo sea correcto. Va despues de la 120 por esa dependencia.
    '122-sembrar-linea-base.sql',
    // 2026-08-27 (Task 9, Torre piloto): 3 migraciones ya en main que nunca se sembraron en el
    // fixture de CI, ver el Dockerfile y design-system-ci-preflight.mjs para el detalle de cada
    // una.
    '123-shared-constraints-gestion.sql',
    '124-avance-edicion-manual.sql',
    '125-carryover-testigo.sql',
  ];
  assert.deepEqual(
    fixtureImage.split('\n')
      .filter((line) => line.startsWith('COPY '))
      .map((line) => line.match(/\/docker-entrypoint-initdb\.d\/([^ ]+\.sql)$/)?.[1]),
    expectedInitOrder,
  );
  assert.match(fixtureImage, /design-system-ci\.sql/);
  assert.match(fixture, /test\.A/);
  assert.match(fixture, /test\.R/);
  assert.match(fixture, /test\.C/);
  assert.match(fixture, /test\.V/);
  assert.match(fixture, /\(73, 'Da Porto', 'da_porto',/);
  assert.match(fixture, /INSERT INTO `programa`\s+\(`project_id`, `unique_id`, `Consecutivo`,/);
  assert.match(fixture, /INSERT INTO `programa_consolidado`\s+\(`project_id`, `row_id`, `Consecutivo`,/);
  assert.match(fixture, /INSERT INTO `semanas_activas`\s+\(`project_id`, `Id`, `Semana`,/);
  assert.doesNotMatch(fixture, /CREATE TABLE (?:semanas_activas|programa_consolidado)/);
  assert.doesNotMatch(fixture, /@aia\.com\.co/);
});

test('CI image includes analysis tools without changing the production default', async () => {
  const [dockerfile, compose] = await Promise.all([
    read('docker/php/Dockerfile'),
    read('docker-compose.ci.yml'),
  ]);
  assert.match(dockerfile, /ARG COMPOSER_INSTALL_FLAGS=--no-dev/);
  assert.match(dockerfile, /composer install \$\{COMPOSER_INSTALL_FLAGS\}/);
  assert.match(compose, /COMPOSER_INSTALL_FLAGS:\s*""/);
});

test('runtime CI continuously enforces the Programa General persistence boundary', async () => {
  const workflow = await read('.github/workflows/ci.yml');

  const preflightIndex = workflow.indexOf('node scripts/design-system-ci-preflight.mjs');
  const startIndex = workflow.indexOf('docker compose -p "$COMPOSE_PROJECT_NAME"');
  assert.ok(preflightIndex >= 0, 'isolated runtime preflight must be present');
  assert.ok(preflightIndex < startIndex, 'preflight must run before Docker starts');
  assert.match(workflow, /node tests\/test_programa_general_sprint_contract\.mjs/);

  // La frontera de tablas globales la vigila tests/test_global_table_safety.php, y lo que hay que
  // garantizar es que el CI la EJECUTE, no que el workflow escriba su nombre.
  //
  // Hasta el 2026-08-11 esta comprobación era `assert.match(workflow, /php tests\/…\.php/)`, una
  // cadena literal. Cuando el CI pasó de listar tres pruebas a mano a invocar el runner, que
  // ejecuta 71, la cadena desapareció y este gate se puso rojo: un cambio que multiplicó por más de
  // veinte la cobertura hacía fallar al contrato que la protegía. Decisión D-CI-1 del usuario.
  //
  // Ahora se comprueba el resultado, y por eso es más estricta que antes: falla si desaparece la
  // invocación del runner, si la prueba deja de existir, si pierde su etiqueta de nivel, o si
  // alguien le pone un nivel que el CI no ejecuta.
  //
  // Esta comparación por nivel SUPONE que el runner es acumulativo: que `--nivel=http` ejecuta
  // además lo `db` y lo `puro`. Ese supuesto no se comprueba aquí sino en
  // `tests/test_php_test_runner.php` («pedir db ejecuta TAMBIEN el de nivel puro»), y hay que
  // mantenerlo: sin él, esta aserción daría verde mientras la prueba deja de correr. Se descubrió
  // el 2026-08-11 mutando el `<=` del runner por `===` — el contrato seguía en RC=0.
  const NIVELES = ['puro', 'db', 'http', 'datos-proyecto'];

  const nivelesInvocados = [...workflow.matchAll(/run-php-tests\.php --nivel=([a-z-]+)/g)]
    .map(([, nivel]) => NIVELES.indexOf(nivel))
    .filter((indice) => indice >= 0);
  assert.ok(
    nivelesInvocados.length > 0,
    'el CI debe invocar scripts/run-php-tests.php con un nivel declarado',
  );
  const nivelMaximoDelCi = Math.max(...nivelesInvocados);

  const guardiaDeTablasGlobales = await read('tests/test_global_table_safety.php');
  const nivelDeclarado = /^\s*\/\/\s*@requiere:\s*([a-z-]+)\s*$/m.exec(guardiaDeTablasGlobales);
  assert.ok(
    nivelDeclarado,
    'tests/test_global_table_safety.php debe declarar su nivel con // @requiere:',
  );
  assert.ok(
    NIVELES.indexOf(nivelDeclarado[1]) <= nivelMaximoDelCi,
    `test_global_table_safety.php declara el nivel '${nivelDeclarado[1]}', que el CI no ejecuta `
      + `(su nivel máximo es '${NIVELES[nivelMaximoDelCi]}'): la frontera de tablas globales dejaría de vigilarse`,
  );
  assert.match(
    workflow,
    /--config=e2e\/playwright\.config\.mjs\s+e2e\/tests\/workflows\/pg-interactions\.spec\.mjs\s+--workers=1/,
  );
  assert.match(
    workflow,
    /name: Run Programa General persistence and RBAC gate[\s\S]*?APP_URL:\s*http:\/\/127\.0\.0\.1:18081[\s\S]*?E2E_BASE_URL:\s*http:\/\/127\.0\.0\.1:18081[\s\S]*?E2E_PROJECT_KEYS:\s*construction[\s\S]*?E2E_REQUIRE_ISOLATED_DB:\s*['"]?1['"]?[\s\S]*?E2E_ALLOW_DB_MUTATION:\s*design-system-ci/,
  );
  assert.match(workflow, /COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-\$\{CI_RUN_ID\}/);
  assert.match(workflow, /echo "COMPOSE_FILE=docker-compose\.yml:docker-compose\.ci\.yml"/);
});

test('consumer smoke authenticates with the sanitized CI fixture', async () => {
  const smoke = await read('tests/browser/design-system-consumer-smoke.mjs');
  assert.match(smoke, /username:\s*'test\.A'/);
  assert.match(smoke, /loginAndSelectProject\(page, project, CI_ADMIN\)/);
});

test('versioned runner scripts separate static, accessibility and visual gates', async () => {
  const packageJson = JSON.parse(await read('package.json'));
  for (const script of [
    'test:design-system:static',
    'test:design-system:runtime',
    'test:a11y:lab',
    'test:visual:lab',
    'test:a11y:pilot',
    'test:visual:pilot',
    'test:reflow',
    'test:design-system:evidence',
  ]) {
    assert.ok(packageJson.scripts[script], script);
  }
  assert.match(packageJson.scripts['test:design-system:runtime'], /design-system-lab\.mjs/);
  assert.doesNotMatch(packageJson.scripts['test:design-system:runtime'], /consumer-smoke|pilot|programa-general/);
  assert.doesNotMatch(packageJson.scripts['test:design-system:runtime'], /keyboard|reflow/);
  assert.match(packageJson.scripts['test:design-system:evidence'], /design-system-lab-keyboard\.mjs/);
  assert.match(packageJson.scripts['test:design-system:evidence'], /design-system-lab-desktop-layout\.mjs/);
  assert.match(packageJson.scripts['test:reflow'], /design-system-lab-desktop-layout\.mjs/);
  const config = await read('playwright.config.mjs');
  assert.match(config, /snapshotPathTemplate/);
  assert.match(config, /animations:\s*'disabled'/);
});

test('CI tolerates keyboard and reflow evidence and uploads its failures', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  const blocking = steps.find(({ id }) => id === 'blocking-runtime');
  const evidence = steps.find(({ id }) => id === 'keyboard-reflow-evidence');
  const artifact = steps.find(({ name }) => name === 'Preserve non-blocking evidence failures');

  assert.equal(blocking?.run, 'npm run test:design-system:runtime');
  assert.notEqual(blocking?.['continue-on-error'], true);
  assert.equal(evidence?.run, 'npm run test:design-system:evidence');
  assert.equal(evidence?.['continue-on-error'], true);
  assert.equal(
    artifact?.if,
    "steps.keyboard-reflow-evidence.outcome == 'failure'",
  );
  // Anclado por SHA desde el frente 1 de deuda de CI (spec 2026-08-20): un tag
  // mutable aquí sería reintroducir la brecha que ese frente cerró.
  assert.match(artifact?.uses ?? '', /^actions\/upload-artifact@[0-9a-f]{40} # v\d+\.\d+\.\d+$/);
  assert.match(artifact?.with?.path, /test-results\//);
  assert.match(artifact?.with?.path, /test-output\//);
});

test('runtime failures preserve Playwright, axe and Docker evidence', async () => {
  const [config, e2eConfig, pilot, workflow] = await Promise.all([
    read('playwright.config.mjs'),
    read('e2e/playwright.config.mjs'),
    read('tests/browser/programa-general-design-system.mjs'),
    read('.github/workflows/ci.yml'),
  ]);

  assert.match(config, /forbidOnly:\s*Boolean\(process\.env\.CI\)/);
  assert.match(config, /outputDir:\s*['"]\.\/test-output['"]/);
  assert.match(config, /trace:\s*['"]retain-on-failure['"]/);
  assert.match(config, /screenshot:\s*['"]only-on-failure['"]/);
  assert.match(config, /video:\s*['"]retain-on-failure['"]/);
  assert.match(e2eConfig, /forbidOnly:\s*Boolean\(process\.env\.CI\)/);
  assert.match(pilot, /reportPath:\s*testInfo\.outputPath\(['"]axe-report\.json['"]\)/);
  assert.match(workflow, /name: Preserve runtime logs[\s\S]*?docker compose[\s\S]*?logs --no-color/);
  assert.match(workflow, /test-output\//);
});

test('pilot runtime budgets remain available while the canonical runtime uses the isolated laboratory budget', async () => {
  const [packageJson, workflow, collector, baseline, retrospective, recoveryManifest, schema, closeout] = await Promise.all([
    readJson('package.json'),
    read('.github/workflows/ci.yml'),
    read('tests/browser/design-system-runtime-budget.mjs'),
    readJson('docs/design-system/runtime-baseline-0.3.3.json'),
    readJson('docs/design-system/runtime-measurements/0.3.3-retrospective.json'),
    readJson('docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json'),
    readJson('docs/design-system/runtime-budget.schema.json'),
    readJson('docs/design-system/closeout-evidence.json'),
  ]);

  assert.match(packageJson.scripts['test:runtime-budget:measure'], /design-system-runtime-budget\.mjs/);
  // La generacion vigente se lee del propio script en vez de fijarse por nombre. Clavar aqui un
  // numero concreto convertia cada re-aprobacion en una edicion de esta prueba, y fue una de las
  // tres barreras que bloquearon la de 0.3.5 el 2026-08-18 (las otras dos: la lista blanca de
  // BASELINE_GENERATIONS y las rutas literales del validador de procedencia). Lo que importa
  // proteger no es que la generacion sea una en particular, sino que exista, este registrada y
  // cumpla el contrato entero, que es lo que asegura el bloque de abajo.
  const runtimeGeneration = packageJson.scripts['test:runtime-budget:check']
    .match(/runtime-baseline-(\d+\.\d+\.\d+)\.json/)?.[1];
  assert.ok(runtimeGeneration, 'test:runtime-budget:check debe apuntar a un baseline versionado');
  assert.match(packageJson.scripts['test:runtime-budget:check'], /design-system-runtime-budget\.mjs/);
  assert.match(packageJson.scripts['test:performance:lab'], /design-system-lab\.performance\.mjs/);
  assert.match(packageJson.scripts['test:design-system:runtime'], /test:performance:lab/);
  assert.doesNotMatch(packageJson.scripts['test:design-system:runtime'], /test:runtime-budget/);
  assert.match(workflow, /npm run test:design-system:runtime/);
  assert.match(workflow, /test-output\//);

  assert.match(collector, /gzipSync/);
  assert.match(collector, /adapterAssets/);
  assert.match(collector, /duplicateRequestCount/);
  assert.match(collector, /themeFlashCount/);
  assert.match(collector, /initializationMs/);
  assert.match(collector, /handsontableInteractionMs/);
  assert.match(collector, /\.htDropdownMenu:visible/);
  assert.match(collector, /interactionKind:\s*['"]column-filter-menu['"]/);
  assert.match(collector, /laboratoryAssets/);
  assert.match(collector, /sourceTreeHash/);
  assert.match(collector, /readCurrentRuntimeContext/);
  assert.match(collector, /assetInventorySha256\s*=\s*sha256\(JSON\.stringify\(assets\)\)/);
  assert.doesNotMatch(collector, /sourceTreeHash\s*=\s*sha256\(JSON\.stringify\(assets\)\)/);
  assert.match(
    collector,
    /\$schema:\s*['"]https:\/\/lastplanneraia\.com\/schemas\/design-system-runtime-budget-v1\.json['"]/,
  );
  assert.match(collector, /DS_RUNTIME_VERSION/);
  assert.match(collector, /ciRunId:\s*runtimeContext\.ciRunId/);
  assert.match(collector, /fixtureSha256:\s*runtimeContext\.fixtureSha256/);
  assert.match(collector, /sampleNumber\s*=\s*1;\s*sampleNumber\s*<=\s*3/);
  assert.match(collector, /aggregateRuntimeBudgetSamples\(reports\)/);
  assert.match(collector, /flag:\s*['"]wx['"]/);

  assert.equal(schema.$id, 'https://lastplanneraia.com/schemas/design-system-runtime-budget-v1.json');
  assert.equal(baseline.designSystemVersion, '0.3.3');
  assert.equal(baseline.status, 'approved');
  assert.equal(baseline.measurementKind, 'retrospective');
  assert.equal(baseline.approval.status, 'approved');
  assert.equal(baseline.sourceRef, null);
  assert.equal(
    baseline.recovery.manifestPath,
    'docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json',
  );
  assert.equal(
    baseline.recovery.measurementPath,
    'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
  );
  assert.equal(
    createHash('sha256')
      .update(await read(baseline.recovery.manifestPath))
      .digest('hex'),
    baseline.recovery.manifestSha256,
  );
  assert.equal(
    createHash('sha256')
      .update(await read(baseline.recovery.measurementPath))
      .digest('hex'),
    baseline.recovery.measurementSha256,
  );
  assert.equal(recoveryManifest.status, 'retrospective-incomplete');
  assert.equal(recoveryManifest.rawSamplesPreserved, false);
  assert.equal(recoveryManifest.sourceHistory.originCommitAvailable, false);
  assert.equal(recoveryManifest.sourceHistory.recoveryFromOriginGitHistory, false);
  const { initializationMs: baselineInitializationMs, ...baselineMetrics } = baseline.metrics;
  const { initializationMs: retrospectiveInitializationMs, ...retrospectiveMetrics } = retrospective.metrics;
  assert.deepEqual(baselineMetrics, retrospectiveMetrics);
  assert.equal(baselineInitializationMs, 991);
  assert.equal(retrospectiveInitializationMs, 227.9);
  assert.deepEqual(baseline.tolerances, {
    cssGzipBytes: 2048,
    jsGzipBytes: 4096,
    addedAdapterAssets: 0,
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 110,
    handsontableInteractionMs: 45,
  });
  assert.equal(retrospective.kind, 'measurement');
  assert.equal(retrospective.measurementKind, 'retrospective');
  assert.equal(retrospective.designSystemVersion, '0.3.3');
  assert.equal(retrospective.provenance.interactionKind, 'column-filter-menu');
  assert.equal(retrospective.provenance.sampling.rawSamplesPreserved, false);
  assert.equal(retrospective.provenance.sampling.historicalSummaryCount, 3);
  assert.equal(retrospective.provenance.sampling.reportedAggregation, 'median-of-three');
  assert.equal(retrospective.provenance.sampling.samples, undefined);
  assert.deepEqual(retrospective.metrics.laboratoryAssets, []);

  // El baseline VIGENTE es el 0.3.4, medido en CI (D-GAC-5(b), aprobado por el usuario el
  // 2026-08-12). El 0.3.3 de arriba se sigue comprobando entero **como historico**: no se
  // reescribe ni se borra, porque es la unica prueba de que aquel presupuesto existio.
  //
  // Lo que este bloque protege del 0.3.4 es lo mismo que el 0.3.3 tenia y por el mismo motivo:
  // que el baseline NO se pueda editar a mano. Alli la atadura era a una retrospectiva fijada por
  // sha256; aqui es a una medicion de CI fijada por sha256 **y** por el commit que la produjo,
  // que es una atadura mas fuerte, no mas floja: aquella no se podia reproducir y esta si.
  const currentBaseline = await readJson(`docs/design-system/runtime-baseline-${runtimeGeneration}.json`);
  const currentMeasurement = await readJson(`docs/design-system/runtime-measurements/${runtimeGeneration}-measurement.json`);
  const currentManifest = await readJson(`docs/design-system/runtime-measurements/${runtimeGeneration}-recovery-manifest.json`);

  assert.equal(currentBaseline.designSystemVersion, runtimeGeneration);
  assert.equal(currentBaseline.measurementKind, 'current');
  assert.equal(currentBaseline.status, 'approved');
  assert.equal(currentBaseline.approval.status, 'approved');
  assert.equal(currentBaseline.approval.approvedBy, 'user');
  assert.match(currentBaseline.sourceRef, /^[a-f0-9]{40}$/);
  assert.equal(
    currentBaseline.recovery.measurementPath,
    `docs/design-system/runtime-measurements/${runtimeGeneration}-measurement.json`,
  );
  assert.equal(
    currentBaseline.recovery.manifestPath,
    `docs/design-system/runtime-measurements/${runtimeGeneration}-recovery-manifest.json`,
  );
  assert.equal(
    createHash('sha256')
      .update(await read(currentBaseline.recovery.measurementPath))
      .digest('hex'),
    currentBaseline.recovery.measurementSha256,
  );
  assert.equal(
    createHash('sha256')
      .update(await read(currentBaseline.recovery.manifestPath))
      .digest('hex'),
    currentBaseline.recovery.manifestSha256,
  );
  // Las cifras del baseline son las medidas, sin editar. Es la aserción que impide «ajustar» un
  // numero para que el gate pase: cambiar un byte aqui rompe la igualdad con la medicion.
  assert.deepEqual(currentBaseline.metrics, currentMeasurement.metrics);
  assert.equal(currentBaseline.sourceRef, currentMeasurement.sourceRef);
  assert.equal(currentManifest.sourceHistory.sourceRef, currentMeasurement.sourceRef);
  assert.equal(currentManifest.sourceHistory.originCommitAvailable, true);
  assert.equal(currentManifest.rawSamplesPreserved, true);
  // Las dos versiones que conviven, atadas y nombradas: la del presupuesto y la del producto.
  assert.equal(currentManifest.designSystemVersion, runtimeGeneration);
  assert.equal(currentManifest.measuredDesignSystemVersion, currentMeasurement.designSystemVersion);
  // La justificacion no es decorativa: apunta a un informe de atribucion que existe de verdad y
  // que descarta la regresion. El patron exigia `atribucion-css-gzip` porque en 0.3.4 el unico
  // numero investigado era el del CSS; en 0.3.5 fueron dos —el CSS y el tiempo de interaccion de
  // Handsontable— y ese nombre habria quedado enganoso. Lo que el contrato protege es que haya
  // informe y que sea legible desde aqui, no como se llama la metrica de aquella vez.
  assert.match(currentBaseline.justification.attributionPath, /^docs\/design-system\/runtime-measurements\/.+atribucion.+\.md$/);
  assert.ok(existsSync(currentBaseline.justification.attributionPath),
    `el informe de atribucion ${currentBaseline.justification.attributionPath} debe existir`);
  assert.equal(existsSync(new URL(`../../${currentBaseline.justification.attributionPath}`, import.meta.url)), true);
  // El historico y el vigente no pueden confundirse: distinta generacion, distinto modo.
  assert.notEqual(currentBaseline.designSystemVersion, baseline.designSystemVersion);
  assert.equal(baseline.measurementKind, 'retrospective');

  // `runtime-budgets` sigue siendo bloqueante y sigue estando en la lista: eso
  // es lo que este test protege. Lo que ya NO se le exige es estar `passed`
  // (D-F1b-5, 2026-08-11): mide los artefactos que produce la corrida de
  // `runtime`, asi que su resultado depende de ella y no aporta una garantia
  // separada. Fijarlo aqui en `passed` obligaba a declararlo aprobado aunque no
  // se hubiera ejecutado, que es justo el defecto que este frente arranco.
  const budgetGate = closeout.gates.find(({ id }) => id === 'runtime-budgets');
  assert.ok(budgetGate, 'runtime-budgets debe seguir declarado en el cierre');
  assert.equal(budgetGate.blocking, true);
  assert.ok(
    ['passed', 'blocked', 'pending'].includes(budgetGate.status),
    `estado invalido: ${budgetGate.status}`,
  );
});

test('ningun carril descarta escenarios por ancho', async () => {
  // Los carriles se descubren por busqueda, no se enumeran: un carril visual o
  // de accesibilidad nuevo que filtrara por ancho pasaria inadvertido si la
  // lista estuviera escrita a mano.
  const browserDir = new URL('../browser/', import.meta.url);
  const specs = (await readdir(browserDir))
    .filter((name) => /\.(visual|a11y)\.mjs$/.test(name))
    .map((name) => `tests/browser/${name}`);
  assert.ok(specs.length >= 4, `se esperaban al menos 4 carriles, se hallaron ${specs.length}`);
  for (const spec of specs) {
    const source = await readFile(new URL(`../../${spec}`, import.meta.url), 'utf8');
    assert.equal(
      /width\s*>=\s*1180/.test(source), false,
      `${spec} sigue descartando escenarios por ancho`,
    );
  }
});

test('el carril de accesibilidad no fija el numero de escenarios a mano', async () => {
  const source = await readFile(
    new URL('../../tests/browser/design-system-lab.a11y.mjs', import.meta.url), 'utf8',
  );
  assert.equal(/toHaveLength\(\d+\)/.test(source), false);
});
