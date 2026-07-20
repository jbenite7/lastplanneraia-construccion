import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { consumerContractFailures } from '../../scripts/design-system-consumer-contract.mjs';

const root = fileURLToPath(new URL('../..', import.meta.url));
const manifest = JSON.parse(await readFile(new URL('../../docs/design-system/manifests/project-selector.json', import.meta.url), 'utf8'));

test('Project Selector consumes the canonical design system contract', () => {
  assert.deepEqual(consumerContractFailures({ root, manifest }), []);
});

test('consumer contract rejects external vendors and local visual primitives', () => {
  const view = '<link href="https://cdn.example.test/adminlte.css"><div style="color:#fff" class="aia-card aia-input aia-btn aia-chip aia-empty aia-alert aia-shell"></div>';
  const css = '.bad { color: #fff; font-size: 14px; border-radius: 4px; box-shadow: 0 2px 4px #000; }';
  const failures = consumerContractFailures({ root, manifest, viewOverride: view, cssOverride: css });
  assert.ok(failures.some((failure) => failure.includes('external URL/CDN')));
  assert.ok(failures.some((failure) => failure.includes('raw hex color')));
  assert.ok(failures.some((failure) => failure.includes('local font size')));
  assert.ok(failures.some((failure) => failure.includes('local radius')));
});
