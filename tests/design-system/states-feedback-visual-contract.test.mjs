import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('states feedback keeps a canonical spinner contract without rewriting legacy goldens', async () => {
  const [source, manifestSource, specimen] = await Promise.all([
    read('tests/browser/design-system-lab.visual.mjs'),
    read('docs/design-system/manifests/laboratory.json'),
    read('views/design-system/families/states-feedback.php'),
  ]);
  const homologation = JSON.parse(await read('docs/design-system/homologation.json'));
  const manifest = JSON.parse(manifestSource);
  const feedbackScenarios = manifest.scenarios.filter(({ family }) => family === 'states-feedback');
  const canonicalSpinner = specimen.match(
    /data-ui-group="loading-spinner"[^>]*role="status"[^>]*aria-live="polite"/g,
  );

  assert.equal(feedbackScenarios.length, 2);
  // states-feedback sigue siendo la UNICA familia que no entra al claro: el spec
  // visual sale antes de capturarla, asi que no hay golden claro que aprobar
  // (frente `bloqueo-tema-claro`, 2026-09-07). Ese es el invariante; se afirma
  // en vez de deducirse del conteo.
  assert.deepEqual([...new Set(feedbackScenarios.map(({ theme }) => theme))], ['dark']);
  // El resto se DERIVA de homologation.json. Aqui vivia un `18` a mano que era el
  // mismo candado de un solo tema que ya se abrio en laboratory-hardening: al
  // entrar el claro paso a 36 y el numero desnudo no decia por que.
  const esperadoResto = homologation.families
    .filter(({ id }) => id !== 'states-feedback')
    .reduce((total, { themes, viewports }) => total + (themes.length * viewports.length), 0);
  assert.equal(manifest.scenarios.length - feedbackScenarios.length, esperadoResto);
  assert.equal(canonicalSpinner?.length, 1);
  assert.match(source, /async function assertStatesFeedbackVisualContract/);
  assert.match(source, /scenario\.family === STATES_FEEDBACK_FAMILY/);
  assert.match(
    source,
    /panel\.locator\('\[data-ui-group="loading-spinner"\]\[role="status"\]'\)/,
  );
  assert.doesNotMatch(source, /panel\.locator\('\[data-ui-group="loading-spinner"\]'\)/);
  assert.match(source, /contrastRatio\(contract\.foreground, contract\.background\)/);
  assert.match(source, /contract\.pageOverflowX/);
  assert.match(source, /contract\.panelOverflowX/);
  assert.match(source, /contract\.spinnerInsideStatus/);
  assert.match(source, /contract\.centerDelta/);
  assert.match(source, /toHaveScreenshot/);
  assert.doesNotMatch(source, /updateSnapshots|update-snapshots/);
});
