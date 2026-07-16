import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('states feedback keeps a canonical spinner contract without rewriting legacy goldens', async () => {
  const [source, manifestSource] = await Promise.all([
    read('tests/browser/design-system-lab.visual.mjs'),
    read('docs/design-system/manifests/laboratory.json'),
  ]);
  const manifest = JSON.parse(manifestSource);
  const feedbackScenarios = manifest.scenarios.filter(({ family }) => family === 'states-feedback');

  assert.equal(feedbackScenarios.length, 6);
  assert.equal(manifest.scenarios.length - feedbackScenarios.length, 54);
  assert.match(source, /async function assertStatesFeedbackVisualContract/);
  assert.match(source, /scenario\.family === STATES_FEEDBACK_FAMILY/);
  assert.match(source, /contrastRatio\(contract\.foreground, contract\.background\)/);
  assert.match(source, /contract\.pageOverflowX/);
  assert.match(source, /contract\.panelOverflowX/);
  assert.match(source, /contract\.spinnerInsideStatus/);
  assert.match(source, /contract\.centerDelta/);
  assert.match(source, /toHaveScreenshot/);
  assert.doesNotMatch(source, /updateSnapshots|update-snapshots/);
});
