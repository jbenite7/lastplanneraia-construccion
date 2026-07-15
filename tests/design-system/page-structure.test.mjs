import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('the approved page header is canonical in the catalog', async () => {
  const catalog = await readJson('component-catalog.json');
  const header = catalog.components.find(({ id }) => id === 'page-header');
  assert.equal(header.kind, 'canonical');
  assert.equal(header.maturity, 'stable');
  assert.equal(header.visualApproval.status, 'approved');
  assert.ok(header.api.includes('DesignSystemComponent::pageHeader'));
  assert.ok(header.api.includes('.aia-page-header'));
});
