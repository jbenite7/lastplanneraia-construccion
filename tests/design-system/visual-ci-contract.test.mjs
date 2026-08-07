import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { parseJobSteps } from './workflow-contract-parser.mjs';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));
// Piloto y laboratorio comparten la misma matriz de viewports requeridos.
// 390x844 se reabrio el 2026-08-07 como soportado pero no exigido (ver
// tests/design-system/mobile-viewport-scope.test.mjs); ninguna familia lo
// declara todavia, asi que esta matriz sigue siendo solo los dos requeridos.
const requiredViewports = ['1180x820', '1440x900'];
const laboratoryViewports = requiredViewports;

const viewportKey = ({ width, height }) => `${width}x${height}`;

function assertDesktopDarkPilotMatrix(scenarios, expectedPerViewport) {
  assert.deepEqual([...new Set(scenarios.map(({ theme }) => theme))], ['dark']);
  assert.deepEqual(
    [...new Set(scenarios.map(({ viewport }) => viewportKey(viewport)))].sort(),
    [...requiredViewports].sort(),
  );

  for (const viewport of requiredViewports) {
    assert.equal(
      scenarios.filter((scenario) => viewportKey(scenario.viewport) === viewport).length,
      expectedPerViewport,
      `dark ${viewport}`,
    );
  }
}

function assertDesktopDarkLaboratoryMatrix(scenarios, familyCount) {
  assert.deepEqual([...new Set(scenarios.map(({ theme }) => theme))], ['dark']);
  assert.deepEqual(
    [...new Set(scenarios.map(({ viewport }) => viewportKey(viewport)))].sort(),
    [...laboratoryViewports].sort(),
  );
  for (const viewport of laboratoryViewports) {
    assert.equal(
      scenarios.filter((scenario) => viewportKey(scenario.viewport) === viewport).length,
      familyCount,
      `dark ${viewport}`,
    );
  }
}

test('visual regression contract covers the approved laboratory matrix', async () => {
  const [source, manifest] = await Promise.all([
    read('tests/browser/design-system-lab.visual.mjs'),
    readJson('docs/design-system/manifests/laboratory.json'),
  ]);
  assert.match(source, /toHaveScreenshot/);
  assert.match(source, /MANIFEST\.scenarios/);
  const familyCount = new Set(manifest.scenarios.map(({ family }) => family)).size;
  assert.equal(familyCount, 10);
  assert.equal(manifest.scenarios.length, 20);
  assertDesktopDarkLaboratoryMatrix(manifest.scenarios, familyCount);
});

test('visual regression contract covers the Programa General pilot matrix', async () => {
  const [source, manifest] = await Promise.all([
    read('tests/browser/programa-general.visual.mjs'),
    readJson('docs/design-system/manifests/programa-general.json'),
  ]);
  assert.match(source, /toHaveScreenshot/);
  assert.match(source, /MANIFEST\.scenarios/);
  assert.equal(manifest.scenarios.length, 2);
  assertDesktopDarkPilotMatrix(manifest.scenarios, 1);
});

test('CI is reproducible, least-privileged and has no deployment path', async () => {
  const [workflow, compose, fixtureImage, fixture] = await Promise.all([
    read('.github/workflows/design-system.yml'),
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
  const workflow = await read('.github/workflows/design-system.yml');

  const preflightIndex = workflow.indexOf('node scripts/design-system-ci-preflight.mjs');
  const startIndex = workflow.indexOf('docker compose -p "$COMPOSE_PROJECT_NAME"');
  assert.ok(preflightIndex >= 0, 'isolated runtime preflight must be present');
  assert.ok(preflightIndex < startIndex, 'preflight must run before Docker starts');
  assert.match(workflow, /node tests\/test_programa_general_sprint_contract\.mjs/);
  assert.match(workflow, /php tests\/test_global_table_safety\.php/);
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
  const workflow = await read('.github/workflows/design-system.yml');
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
  assert.equal(artifact?.uses, 'actions/upload-artifact@v4');
  assert.match(artifact?.with?.path, /test-results\//);
  assert.match(artifact?.with?.path, /test-output\//);
});

test('runtime failures preserve Playwright, axe and Docker evidence', async () => {
  const [config, e2eConfig, pilot, workflow] = await Promise.all([
    read('playwright.config.mjs'),
    read('e2e/playwright.config.mjs'),
    read('tests/browser/programa-general-design-system.mjs'),
    read('.github/workflows/design-system.yml'),
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
    read('.github/workflows/design-system.yml'),
    read('tests/browser/design-system-runtime-budget.mjs'),
    readJson('docs/design-system/runtime-baseline-0.3.3.json'),
    readJson('docs/design-system/runtime-measurements/0.3.3-retrospective.json'),
    readJson('docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json'),
    readJson('docs/design-system/runtime-budget.schema.json'),
    readJson('docs/design-system/closeout-evidence.json'),
  ]);

  assert.match(packageJson.scripts['test:runtime-budget:measure'], /design-system-runtime-budget\.mjs/);
  assert.match(packageJson.scripts['test:runtime-budget:check'], /runtime-baseline-0\.3\.3\.json/);
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

  const budgetGate = closeout.gates.find(({ id }) => id === 'runtime-budgets');
  assert.equal(budgetGate?.status, 'passed');
  assert.equal(budgetGate?.blocking, true);
});
