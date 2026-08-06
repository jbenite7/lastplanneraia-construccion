import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (name) => readFile(
  new URL(`../../docs/design-system/contracts/${name}`, import.meta.url),
  'utf8',
);

function requiresAll(source, patterns) {
  for (const pattern of patterns) assert.match(source, pattern);
}

test('governance contract fixes the global authority and release gates', async () => {
  const source = await read('governance.md');
  requiresAll(source, [
    /SemVer/, /manifiesto/i, /catálogo/i, /Programa General/, /revisión local/i,
    /sin push/i, /sin deploy/i, /branch protection/i,
  ]);
  assert.doesNotMatch(source, /Plannotator/i);
});

test('module migration contract prevents local primitives and broad rollout', async () => {
  const source = await read('module-migration.md');
  requiresAll(source, [
    /un módulo por sprint/i, /componentes canónicos/i, /CSS local/i,
    /dark/i, /1180x820/, /1440x900/,
    /axe/i, /persistencia/i, /restauración/i,
  ]);
});

test('sprint close contract makes evidence and approval blocking', async () => {
  const source = await read('sprint-review-close.md');
  requiresAll(source, [
    /evidencia/i, /checksum/i, /aprobación/i, /golden/i, /axe/i,
    /revisión automatizada/i, /cero reglas fallidas/i, /cero instancias fallidas/i,
    /revisión local/i, /commit atómico/i, /staging selectivo/i,
  ]);
  assert.doesNotMatch(source, /Plannotator/i);
});

test('Accessibility Insights exposes the machine-readable basic automated review contract', async () => {
  const closeout = JSON.parse(await readFile(
    new URL('../../docs/design-system/closeout-evidence.json', import.meta.url),
    'utf8',
  ));
  const gate = closeout.gates.find(({ id }) => id === 'accessibility-insights');
  assert.equal(gate.kind, 'automatic');
  assert.deepEqual(closeout.accessibilityReview, {
    kind: 'basic-automated-review',
    surfaces: ['laboratory', 'pilot', 'revealed-states'],
    requiredFailedRules: 0,
    requiredFailedInstances: 0,
  });
  assert.doesNotMatch(JSON.stringify({ gate, review: closeout.accessibilityReview }), /FastPass|WCAG/i);
});

test('active closeout surfaces contain no prohibited accessibility claim', async () => {
  const paths = [
    'docs/design-system/README.md',
    'docs/design-system/manual-accessibility-review.md',
    'docs/design-system/contracts/sprint-review-close.md',
    'goals/design-system-nucleo-gobernanza/goal.md',
    'goals/design-system-nucleo-gobernanza/plan.md',
    'goals/design-system-nucleo-gobernanza/facts.md',
  ];
  const sources = await Promise.all(paths.map((path) => readFile(
    new URL(`../../${path}`, import.meta.url),
    'utf8',
  )));
  const activeContract = sources.join('\n');
  assert.doesNotMatch(activeContract, /FastPass\s+(?:passed|aprobado)/i);
  assert.doesNotMatch(activeContract, /Assessment\s+(?:passed|aprobado)/i);
  assert.doesNotMatch(
    activeContract,
    /(?:WCAG[^.\n]{0,48}(?:compliant|conforme|conformidad|cumple)|(?:compliant|conforme|conformidad|cumple)[^.\n]{0,48}WCAG)/i,
  );
});
