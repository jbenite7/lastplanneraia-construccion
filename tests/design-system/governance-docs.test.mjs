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
    /SemVer/, /manifiesto/i, /catálogo/i, /Programa General/, /Plannotator/,
    /sin push/i, /sin deploy/i, /branch protection/i,
  ]);
});

test('module migration contract prevents local primitives and broad rollout', async () => {
  const source = await read('module-migration.md');
  requiresAll(source, [
    /un módulo por sprint/i, /componentes canónicos/i, /CSS local/i,
    /dark/i, /linen/i, /390x844/, /1180x820/, /1440x900/,
    /axe/i, /persistencia/i, /restauración/i,
  ]);
});

test('sprint close contract makes evidence and approval blocking', async () => {
  const source = await read('sprint-review-close.md');
  requiresAll(source, [
    /evidencia/i, /checksum/i, /aprobación/i, /golden/i, /axe/i,
    /VoiceOver/i, /zoom 200%/i, /commit atómico/i, /staging selectivo/i,
  ]);
});

test('manual accessibility review remains explicit and blocking', async () => {
  const source = await readFile(
    new URL('../../docs/design-system/manual-accessibility-review.md', import.meta.url),
    'utf8',
  );
  requiresAll(source, [
    /Accessibility Insights/, /Teclado/, /VoiceOver/, /Zoom 200%/, /Reflow/,
    /390x844/, /1180x820/, /1440x900/, /pendiente de evidencia humana/i,
  ]);
});
