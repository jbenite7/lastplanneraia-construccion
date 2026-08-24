import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { parseJobSteps } from './workflow-contract-parser.mjs';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('runtime workflow exports preflight provenance before composing a unique target', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  const provenance = steps.find(({ id }) => id === 'runtime-provenance');
  const preflight = steps.find(({ name }) => name === 'Verify isolated runtime target');
  const start = steps.find(({ name }) => name === 'Start isolated runtime');
  const stop = steps.find(({ name }) => name === 'Stop isolated runtime');

  assert.match(provenance?.run, /CI_RUN_ID=run-\$\{GITHUB_RUN_ID\}-\$\{GITHUB_RUN_ATTEMPT\}/);
  assert.match(provenance?.run, /COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-\$\{CI_RUN_ID\}/);
  assert.match(provenance?.run, /CI_GIT_SHA=/);
  assert.match(provenance?.run, /CI_WORKTREE_FINGERPRINT=/);
  assert.match(provenance?.run, /CI_FIXTURE_SHA256=/);
  assert.match(provenance?.run, /GITHUB_ENV/);
  assert.equal(preflight?.run, 'node scripts/design-system-ci-preflight.mjs');
  assert.doesNotMatch(JSON.stringify(preflight?.env), /lps-aia-design-system-ci$/);
  assert.match(start?.run, /-p "\$COMPOSE_PROJECT_NAME"/);
  assert.match(stop?.run, /-p "\$COMPOSE_PROJECT_NAME"/);
});
