import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('accessible BI figure is approved without migrating BI runtime', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'bi-primitives');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'bi-primitives');
  const shell = catalog.components.find(({ id }) => id === 'bi-shell');
  const chart = catalog.components.find(({ id }) => id === 'bi-chart');

  assert.deepEqual(family.candidates.filter(({ status }) => status === 'approved').map(({ id }) => id), ['accessible-figure']);
  assert.equal(approval.candidateId, 'accessible-figure');
  assert.equal(shell.kind, 'canonical');
  assert.equal(shell.maturity, 'candidate');
  assert.equal(shell.visualApproval.status, 'approved');
  assert.equal(chart.kind, 'canonical');
  assert.equal(chart.maturity, 'candidate');
  assert.equal(chart.visualApproval.status, 'approved');
  assert.ok(chart.api.includes('DesignSystemComponent::biFigure'));
});

test('line charts preserve a subtle grid while keeping both series visibly distinct', async () => {
  const css = await readFile('public/css/design-system/components/bi-figure.css', 'utf8');
  const specimen = await readFile('views/design-system/families/bi-primitives.php', 'utf8');
  assert.match(css, /\.aia-bi__line\s*{[^}]*stroke-width:\s*1\.25/s);
  assert.match(css, /\.aia-bi__point\s*{[^}]*stroke-width:\s*0\.75/s);
  assert.match(css, /\.aia-bi__point--plan\s*{[^}]*fill:\s*var\(--ds-active-data-plan\)/s);
  assert.match(css, /\.aia-bi__point--executed\s*{[^}]*fill:\s*var\(--ds-active-data-executed\)/s);
  assert.equal((specimen.match(/<circle class="aia-bi__point/g) || []).length, 12);
});

test('curve guidance has padding and a subtle grid', async () => {
  const css = await readFile('public/css/design-system/components/bi-figure.css', 'utf8');
  const specimen = await readFile('views/design-system/families/bi-primitives.php', 'utf8');
  assert.match(css, /\.aia-bi__guidance\s*{[^}]*padding:/s);
  assert.match(css, /\.aia-bi__grid\s*{[^}]*stroke-width:\s*0\.2[^}]*opacity:\s*0\.3/s);
  assert.ok((specimen.match(/<line class="aia-bi__grid-line"/g) || []).length >= 8);
});

test('progress uses a donut and radar is a governed variant', async () => {
  const css = await readFile('public/css/design-system/components/bi-figure.css', 'utf8');
  const specimen = await readFile('views/design-system/families/bi-primitives.php', 'utf8');
  const catalog = await readJson('component-catalog.json');
  const inventory = await readJson('ui-groups-inventory.json');
  const chart = catalog.components.find(({ id }) => id === 'bi-chart');
  assert.match(css, /\.aia-bi-gauge__meter\s*{[^}]*aspect-ratio:\s*1\s*\/\s*1/s);
  assert.match(css, /\.aia-bi-gauge__meter\s*{[^}]*border-radius:\s*50%/s);
  assert.match(css, /\.aia-bi-gauge__meter::before\s*{[^}]*radial-gradient/s);
  assert.match(specimen, /class="aia-bi-radar"[^>]*role="img"/);
  assert.equal((specimen.match(/class="aia-bi-radar__label"/g) || []).length, 5);
  assert.ok(chart.variants.includes('donut'));
  assert.ok(chart.variants.includes('radar'));
  assert.ok(inventory.groups.some(({ id }) => id === 'radar-chart'));
});

test('radar labels use the chart scale instead of the page type scale', async () => {
  const css = await readFile('public/css/design-system/components/bi-figure.css', 'utf8');
  const specimen = await readFile('views/design-system/families/bi-primitives.php', 'utf8');
  assert.match(css, /\.aia-bi-radar__label\s*{[^}]*font-size:\s*3\.5px/s);
  assert.match(css, /@layer components\s*{[\s\S]*\.aia-bi--radar\s*{[\s\S]*display:\s*grid[\s\S]*grid-template-columns:\s*minmax\(0,\s*1fr\)/s);
  assert.match(css, /\.aia-bi--radar\s*>\s*\.aia-bi-radar\s*{[\s\S]*justify-self:\s*center[\s\S]*margin-inline:\s*0/s);
  assert.match(css, /\.aia-bi__legend-mark--plan\s*{[^}]*var\(--ds-active-data-plan\)/s);
  assert.match(css, /\.aia-bi__legend-mark--executed\s*{[^}]*var\(--ds-active-data-executed\)/s);
  assert.match(specimen, /class="aia-card aia-bi aia-bi--radar"/);
  assert.match(specimen, /x="118" y="43" text-anchor="end">Restricciones/);
  assert.match(specimen, /x="116" y="108" text-anchor="end">Recursos/);
});
