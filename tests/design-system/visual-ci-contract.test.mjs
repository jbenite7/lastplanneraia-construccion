import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));
const requiredViewports = ['390x844', '1180x820', '1440x900'];

const viewportKey = ({ width, height }) => `${width}x${height}`;

function assertCompleteThemeViewportMatrix(scenarios, expectedPerCombination) {
  assert.deepEqual(
    [...new Set(scenarios.map(({ theme }) => theme))].sort(),
    ['dark', 'linen'],
  );
  assert.deepEqual(
    [...new Set(scenarios.map(({ viewport }) => viewportKey(viewport)))].sort(),
    [...requiredViewports].sort(),
  );

  for (const theme of ['dark', 'linen']) {
    for (const viewport of requiredViewports) {
      assert.equal(
        scenarios.filter((scenario) => (
          scenario.theme === theme && viewportKey(scenario.viewport) === viewport
        )).length,
        expectedPerCombination,
        `${theme} ${viewport}`,
      );
    }
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
  assert.equal(manifest.scenarios.length, 60);
  assertCompleteThemeViewportMatrix(manifest.scenarios, familyCount);
});

test('visual regression contract covers the Programa General pilot matrix', async () => {
  const [source, manifest] = await Promise.all([
    read('tests/browser/programa-general.visual.mjs'),
    readJson('docs/design-system/manifests/programa-general.json'),
  ]);
  assert.match(source, /toHaveScreenshot/);
  assert.match(source, /MANIFEST\.scenarios/);
  assert.equal(manifest.scenarios.length, 6);
  assertCompleteThemeViewportMatrix(manifest.scenarios, 1);
});

test('CI is reproducible, least-privileged and has no deployment path', async () => {
  const [workflow, compose, fixture] = await Promise.all([
    read('.github/workflows/design-system.yml'),
    read('docker-compose.ci.yml'),
    read('database/fixtures/design-system-ci.sql'),
  ]);
  assert.match(workflow, /contents:\s*read/);
  assert.match(workflow, /design-system-static/);
  assert.match(workflow, /design-system-runtime/);
  assert.doesNotMatch(workflow, /pull_request_target|deploy|production/i);
  assert.match(compose, /APP_ENV:\s*testing/);
  assert.match(compose, /20260630_global_tables_contract\.sql:\/docker-entrypoint-initdb\.d\/001-global-schema\.sql:ro/);
  assert.match(compose, /design-system-ci\.sql:\/docker-entrypoint-initdb\.d\/002-design-system-ci\.sql:ro/);
  assert.match(compose, /design-system-ci\.sql/);
  assert.match(fixture, /test\.A/);
  assert.match(fixture, /test\.R/);
  assert.match(fixture, /test\.C/);
  assert.match(fixture, /test\.V/);
  assert.match(fixture, /\(73, 'Da Porto', 'da_porto',/);
  assert.match(fixture, /INSERT INTO programa\s+\(project_id, unique_id, Consecutivo,/);
  assert.match(fixture, /INSERT INTO programa_consolidado\s+\(project_id, row_id, Consecutivo,/);
  assert.match(fixture, /INSERT INTO semanas_activas\s+\(project_id, Id, Semana,/);
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
  const startIndex = workflow.indexOf('docker compose -p lps-aia-design-system-ci');
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
    /name: Run Programa General persistence and RBAC gate[\s\S]*?COMPOSE_PROJECT_NAME:\s*lps-aia-design-system-ci[\s\S]*?COMPOSE_FILE:\s*docker-compose\.yml:docker-compose\.ci\.yml[\s\S]*?APP_URL:\s*http:\/\/127\.0\.0\.1:18081[\s\S]*?E2E_BASE_URL:\s*http:\/\/127\.0\.0\.1:18081[\s\S]*?E2E_PROJECT_KEYS:\s*construction[\s\S]*?E2E_REQUIRE_ISOLATED_DB:\s*['"]?1['"]?[\s\S]*?E2E_ALLOW_DB_MUTATION:\s*design-system-ci/,
  );
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
  ]) {
    assert.ok(packageJson.scripts[script], script);
  }
  assert.match(packageJson.scripts['test:design-system:runtime'], /design-system-consumer-smoke/);
  assert.match(packageJson.scripts['test:design-system:runtime'], /test:reflow/);
  assert.match(packageJson.scripts['test:reflow'], /design-system-reflow\.mjs/);
  const config = await read('playwright.config.mjs');
  assert.match(config, /snapshotPathTemplate/);
  assert.match(config, /animations:\s*'disabled'/);
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

test('runtime budgets are measured and compared fail-closed against 0.3.3', async () => {
  const [packageJson, workflow, collector, baseline, retrospective, schema, closeout] = await Promise.all([
    readJson('package.json'),
    read('.github/workflows/design-system.yml'),
    read('tests/browser/design-system-runtime-budget.mjs'),
    readJson('docs/design-system/runtime-baseline-0.3.3.json'),
    readJson('docs/design-system/runtime-measurements/0.3.3-retrospective.json'),
    readJson('docs/design-system/runtime-budget.schema.json'),
    readJson('docs/design-system/closeout-evidence.json'),
  ]);

  assert.match(packageJson.scripts['test:runtime-budget:measure'], /design-system-runtime-budget\.mjs/);
  assert.match(packageJson.scripts['test:runtime-budget:check'], /runtime-baseline-0\.3\.3\.json/);
  assert.match(packageJson.scripts['test:design-system:runtime'], /test:runtime-budget:measure/);
  assert.match(packageJson.scripts['test:design-system:runtime'], /test:runtime-budget:check/);
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
  assert.match(collector, /sourceTreeHash\s*=\s*sha256\(JSON\.stringify\(assets\)\)/);
  assert.doesNotMatch(collector, /sourceTreeHash\s*=\s*sha256\(JSON\.stringify\(\{\s*htmlSha256/);
  assert.match(
    collector,
    /\$schema:\s*['"]https:\/\/lastplanneraia\.com\/schemas\/design-system-runtime-budget-v1\.json['"]/,
  );
  assert.match(collector, /DS_RUNTIME_MEASUREMENT_KIND/);
  assert.match(collector, /DS_RUNTIME_VERSION/);
  assert.match(collector, /DS_RUNTIME_SOURCE_REF/);

  assert.equal(schema.$id, 'https://lastplanneraia.com/schemas/design-system-runtime-budget-v1.json');
  assert.equal(baseline.designSystemVersion, '0.3.3');
  assert.equal(baseline.status, 'missing-approved-measurement');
  assert.equal(baseline.measurementKind, 'retrospective');
  assert.equal(baseline.recovery.sourceTree, '25f2787332117ed93416ffc42e6fac8b037dce94');
  assert.equal(
    baseline.recovery.measurementPath,
    'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
  );
  assert.equal(
    baseline.recovery.measurementSha256,
    'af5acde8e4e07f44e6cf8a9f5dbb4effb83f443c31064cd7e05ae401d8033216',
  );
  assert.equal(
    createHash('sha256')
      .update(await read(baseline.recovery.measurementPath))
      .digest('hex'),
    baseline.recovery.measurementSha256,
  );
  assert.equal(baseline.metrics, null);
  assert.equal(baseline.tolerances, null);
  assert.equal(retrospective.kind, 'measurement');
  assert.equal(retrospective.measurementKind, 'retrospective');
  assert.equal(retrospective.designSystemVersion, '0.3.3');
  assert.equal(retrospective.provenance.interactionKind, 'column-filter-menu');
  assert.equal(retrospective.provenance.sampling.sampleCount, 3);
  assert.equal(retrospective.provenance.sampling.aggregation, 'median-of-three');
  assert.deepEqual(retrospective.metrics.laboratoryAssets, []);

  const budgetGate = closeout.gates.find(({ id }) => id === 'runtime-budgets');
  assert.equal(budgetGate?.status, 'blocked');
  assert.equal(budgetGate?.blocking, true);
});
