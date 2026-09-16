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

  // Hasta el 2026-09-16 esta familia era la UNICA sin claro: el spec visual salia
  // antes de capturarla y no habia golden claro que aprobar (frente
  // `bloqueo-tema-claro`, 2026-09-07). El frente `states-feedback-claro` retiro ese
  // `return`, asi que el invariante se invierte y se sigue afirmando, no deduciendo:
  // la familia declara EXACTAMENTE lo que homologation.json le pide, en los dos temas.
  const familia = homologation.families.find(({ id }) => id === 'states-feedback');
  assert.deepEqual([...familia.themes].sort(), ['dark', 'light']);
  assert.deepEqual(
    [...new Set(feedbackScenarios.map(({ theme }) => theme))].sort(),
    ['dark', 'light'],
  );
  assert.equal(feedbackScenarios.length, familia.themes.length * familia.viewports.length);
  // Y el total del manifiesto se DERIVA de homologation.json para todas las familias,
  // sin excepciones: un `18` o un `2` a mano era el candado de un solo tema escondido
  // en un numero.
  const esperadoTotal = homologation.families
    .reduce((total, { themes, viewports }) => total + (themes.length * viewports.length), 0);
  assert.equal(manifest.scenarios.length, esperadoTotal);
  // La familia se compara contra su golden, recortando el ELEMENTO (excepcion de
  // `elementCaptureAllowlist`): ya no hay un `return` antes de `toHaveScreenshot`.
  assert.match(source, /await expect\(panel\)\.toHaveScreenshot\(path\.basename\(scenario\.golden\)\);\s*return;/);
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
