#!/usr/bin/env node
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { join } from 'node:path';
import process from 'node:process';

import { closeoutContractFailures } from './design-system-closeout-contract.mjs';

const root = process.cwd();
const required = [
  'docs/design-system/version.json',
  'docs/design-system/CHANGELOG.md',
  'docs/design-system/component-catalog.schema.json',
  'docs/design-system/component-catalog.json',
  'docs/design-system/stable-api.schema.json',
  'docs/design-system/stable-api-1.0.0.json',
  'docs/design-system/ui-groups-inventory.schema.json',
  'docs/design-system/ui-groups-inventory.json',
  'docs/design-system/state-semantics.schema.json',
  'docs/design-system/state-semantics.json',
  'docs/design-system/vendors.json',
  'docs/design-system/legacy-aliases.json',
  'docs/design-system/decisions.md',
  'docs/design-system/homologation.json',
  'docs/design-system/family-approvals.schema.json',
  'docs/design-system/family-approvals.json',
  'docs/design-system/a11y-baseline.schema.json',
  'docs/design-system/a11y-baseline.json',
  'docs/design-system/a11y-exceptions.schema.json',
  'docs/design-system/a11y-exceptions.json',
  'docs/design-system/module-manifest.schema.json',
  'docs/design-system/manifests/goal-provenance.json',
  'docs/design-system/manifests/laboratory.json',
  'docs/design-system/manifests/programa-general.json',
  'docs/design-system/manifests/programacion-intermedia.json',
  'docs/design-system/manifests/project-selector.json',
  'docs/design-system/manifests/inventory.json',
  'docs/design-system/closeout-evidence.json',
  'goals/design-system-nucleo-gobernanza/validation-log.md',
];
const failures = [];

function readJson(file) {
  try {
    return JSON.parse(readFileSync(join(root, file), 'utf8'));
  } catch (error) {
    failures.push(`${file}: ${error.message}`);
    return null;
  }
}

function enforceUnique(items, key, label) {
  const seen = new Set();
  for (const item of items || []) {
    if (seen.has(item?.[key])) failures.push(`duplicate ${label}: ${item?.[key]}`);
    seen.add(item?.[key]);
  }
}

for (const file of required) {
  if (!existsSync(join(root, file))) failures.push(`${file}: missing`);
}

const jsonFiles = required.filter((file) => file.endsWith('.json'));
const documents = new Map(jsonFiles.filter((file) => existsSync(join(root, file)))
  .map((file) => [file, readJson(file)]));

const version = documents.get('docs/design-system/version.json')?.version;
if (!/^\d+\.\d+\.\d+$/.test(version || '')) failures.push('version.json: invalid SemVer');

const catalog = documents.get('docs/design-system/component-catalog.json');
enforceUnique(catalog?.components, 'id', 'component id');
const componentMaturities = new Set(['stable', 'candidate', 'compatibility', 'deprecated']);
for (const component of catalog?.components || []) {
  if (!Object.hasOwn(component, 'maturity')) {
    failures.push(`${component.id}: missing maturity`);
  } else if (!componentMaturities.has(component.maturity)) {
    failures.push(`${component.id}: invalid maturity ${component.maturity}`);
  }
}

const stableApi = documents.get('docs/design-system/stable-api-1.0.0.json');
const closeout = documents.get('docs/design-system/closeout-evidence.json');
const versionDocument = documents.get('docs/design-system/version.json');
if (stableApi?.targetVersion !== '1.0.0') failures.push('stable API: targetVersion must be 1.0.0');
if (stableApi?.guaranteeScope !== 'stable-only') failures.push('stable API: guaranteeScope must be stable-only');
if (stableApi?.activationGate !== 'all-closeout-gates-passed') {
  failures.push('stable API: activationGate must be all-closeout-gates-passed');
}
enforceUnique(stableApi?.components, 'id', 'stable API component id');
const declaredStableApi = new Map(
  (stableApi?.components || []).map((component) => [component.id, component]),
);
const catalogStableComponents = (catalog?.components || [])
  .filter(({ maturity }) => maturity === 'stable');
for (const component of catalogStableComponents) {
  const releaseComponent = declaredStableApi.get(component.id);
  if (!releaseComponent) {
    failures.push(`stable API: missing catalog component ${component.id}`);
    continue;
  }
  if (releaseComponent.family !== component.family) {
    failures.push(`stable API ${component.id}: family mismatch`);
  }
  if (JSON.stringify(releaseComponent.api) !== JSON.stringify(component.api)) {
    failures.push(`stable API ${component.id}: API mismatch`);
  }
  if (JSON.stringify(releaseComponent.evidenceSurfaces)
    !== JSON.stringify(['laboratory', 'programa-general'])) {
    failures.push(`stable API ${component.id}: evidence must cover laboratory and programa-general`);
  }
  if (component.visualApproval?.status !== 'approved') {
    failures.push(`stable API ${component.id}: visual approval is not approved`);
  }
  if (!(component.consumers || []).some((consumer) => ['global', 'programa-general'].includes(consumer))) {
    failures.push(`stable API ${component.id}: Programa General is not a declared consumer`);
  }
}
for (const releaseComponent of stableApi?.components || []) {
  const component = (catalog?.components || []).find(({ id }) => id === releaseComponent.id);
  if (!component) failures.push(`stable API: unknown component ${releaseComponent.id}`);
  else if (component.maturity !== 'stable') {
    failures.push(`stable API ${releaseComponent.id}: catalog maturity must be stable`);
  }
}
failures.push(...closeoutContractFailures({
  root, closeout, stableApi, versionDocument,
}));

const homologation = documents.get('docs/design-system/homologation.json');
const approvals = documents.get('docs/design-system/family-approvals.json');
const governedFamilies = [
  'foundations', 'shell-navigation', 'page-structure', 'actions', 'forms-filters',
  'states-feedback', 'data-display', 'overlays', 'vendor-adapters', 'bi-primitives',
];
enforceUnique(homologation?.families, 'id', 'homologation family');
const homologatedFamilies = new Set((homologation?.families || []).map((family) => family.id));
for (const family of governedFamilies) {
  if (!homologatedFamilies.has(family)) failures.push(`homologation: missing family ${family}`);
}
for (const family of homologation?.families || []) {
  if (family.activeCandidate
    && !family.candidates?.some((candidate) => candidate.id === family.activeCandidate)) {
    failures.push(`${family.id}: active candidate ${family.activeCandidate} is not declared`);
  }
}

for (const testFile of homologation?.tests || []) {
  if (!existsSync(join(root, testFile))) {
    failures.push(`homologation: missing test ${testFile}`);
  }
}

const approvalKeys = new Set();
for (const approval of approvals?.approvals || []) {
  const key = `${approval.familyId}/${approval.candidateId}`;
  if (approvalKeys.has(key)) failures.push(`duplicate family approval: ${key}`);
  approvalKeys.add(key);
  const family = (homologation?.families || []).find((item) => item.id === approval.familyId);
  const candidate = family?.candidates?.find((item) => item.id === approval.candidateId);
  if (!candidate) failures.push(`family approval: unknown candidate ${key}`);
  if (!approval.evidence?.length) failures.push(`${key}: approval requires evidence`);
  if (approval.scope === 'desktop-dark') {
    if (JSON.stringify(approval.themes) !== JSON.stringify(['dark'])) {
      failures.push(`${key}: desktop-dark scoped approval must cover only dark`);
    }
    if (JSON.stringify(approval.viewports) !== JSON.stringify(['1180x820', '1440x900'])) {
      failures.push(`${key}: desktop-dark scoped approval must cover the canonical desktop viewports`);
    }
  } else {
    if (JSON.stringify(approval.themes) !== JSON.stringify(['linen', 'dark'])) {
      failures.push(`${key}: approval must cover linen and dark`);
    }
    if (JSON.stringify(approval.viewports) !== JSON.stringify(['390x844', '1180x820', '1440x900'])) {
      failures.push(`${key}: approval must cover all viewports`);
    }
  }
}
for (const family of homologation?.families || []) {
  for (const candidate of family.candidates || []) {
    const key = `${family.id}/${candidate.id}`;
    if (candidate.status === 'approved' && !approvalKeys.has(key)) {
      failures.push(`${key}: approved without approval evidence`);
    }
    if (candidate.status !== 'approved' && approvalKeys.has(key)) {
      failures.push(`${key}: approval recorded for non-approved candidate`);
    }
  }
}

const visualApprovalStatuses = new Set(['approved', 'pending', 'not-required']);
for (const component of catalog?.components || []) {
  const visualApproval = component.visualApproval;
  if (!visualApproval || typeof visualApproval !== 'object') {
    failures.push(`${component.id}: missing visual approval`);
    continue;
  }
  if (!visualApprovalStatuses.has(visualApproval.status)) {
    failures.push(`${component.id}: invalid visual approval ${visualApproval.status}`);
  }
  if (visualApproval.familyId !== component.family) {
    failures.push(`${component.id}: visual approval family must equal ${component.family}`);
  }
  const family = (homologation?.families || [])
    .find((item) => item.id === visualApproval.familyId);
  const candidateExists = family?.candidates?.some(
    (candidate) => candidate.id === visualApproval.candidateId,
  );
  if (visualApproval.status === 'not-required') {
    if (visualApproval.candidateId !== null) {
      failures.push(`${component.id}: not-required visual approval must have null candidate`);
    }
  } else if (!candidateExists) {
    failures.push(`${component.id}: unknown visual approval candidate ${visualApproval.familyId}/${visualApproval.candidateId}`);
  } else if (visualApproval.status === 'approved'
    && !approvalKeys.has(`${visualApproval.familyId}/${visualApproval.candidateId}`)) {
    failures.push(`${component.id}: visual approval lacks family evidence`);
  }
}

const componentIds = new Set((catalog?.components || []).map((component) => component.id));
const uiInventory = documents.get('docs/design-system/ui-groups-inventory.json');
enforceUnique(uiInventory?.groups, 'id', 'UI group id');
for (const group of uiInventory?.groups || []) {
  if (JSON.stringify(group.themes) !== JSON.stringify(['dark', 'linen'])) {
    failures.push(`${group.id}: UI group must cover dark and linen`);
  }
  for (const componentId of group.catalogIds || []) {
    if (!componentIds.has(componentId)) failures.push(`${group.id}: unknown component ${componentId}`);
  }
}
const aliases = documents.get('docs/design-system/legacy-aliases.json');
enforceUnique(aliases?.aliases, 'legacySelector', 'legacy selector');
for (const alias of aliases?.aliases || []) {
  if (!componentIds.has(alias.catalogId)) {
    failures.push(`legacy alias ${alias.legacySelector}: unknown component ${alias.catalogId}`);
  }
}
const manifestSchema = documents.get('docs/design-system/module-manifest.schema.json');
const manifests = [
  documents.get('docs/design-system/manifests/laboratory.json'),
  documents.get('docs/design-system/manifests/programa-general.json'),
  documents.get('docs/design-system/manifests/programacion-intermedia.json'),
  documents.get('docs/design-system/manifests/project-selector.json'),
].filter(Boolean);
const programManifest = manifests.find(({ moduleId }) => moduleId === 'programa-general');
const laboratoryManifest = manifests.find(({ moduleId }) => moduleId === 'design-system-laboratory');
for (const manifest of manifests) {
  for (const field of manifestSchema?.required || []) {
    if (!Object.hasOwn(manifest, field)) {
      failures.push(`${manifest.moduleId}: missing required field ${field}`);
    }
  }
  for (const componentId of manifest.components || []) {
    if (!componentIds.has(componentId)) {
      failures.push(`${manifest.moduleId}: unknown component ${componentId}`);
    }
  }
}

const vendors = documents.get('docs/design-system/vendors.json');
enforceUnique(vendors?.vendors, 'id', 'vendor id');
const adapterMaturities = new Set([
  'stable-adapter', 'candidate-adapter', 'compatibility-skin', 'deprecated-adapter',
]);
for (const vendor of vendors?.vendors || []) {
  if (!Object.hasOwn(vendor, 'adapterMaturity')) {
    failures.push(`${vendor.id}: missing adapter maturity`);
  } else if (vendor.adapterMaturity !== null && !adapterMaturities.has(vendor.adapterMaturity)) {
    failures.push(`${vendor.id}: invalid adapter maturity ${vendor.adapterMaturity}`);
  }
  if (!['inventory', 'foundation'].includes(vendor.classification)
    && vendor.adapterMaturity === null) {
    failures.push(`${vendor.id}: adapter maturity is required for ${vendor.classification}`);
  }
  for (const asset of vendor.assets || []) {
    if (!existsSync(join(root, asset))) {
      failures.push(`${vendor.id}: missing vendor asset ${asset}`);
    }
  }
  for (const [filename, expected] of Object.entries(vendor.sha256 || {})) {
    const asset = (vendor.assets || []).find((candidate) => candidate.endsWith(`/${filename}`));
    if (!asset || !existsSync(join(root, asset))) continue;
    const actual = createHash('sha256').update(readFileSync(join(root, asset))).digest('hex');
    if (actual !== expected) failures.push(`${vendor.id}: hash mismatch ${filename}`);
  }
}
const vendorIds = new Set((vendors?.vendors || []).map((vendor) => vendor.id));
for (const manifest of manifests) {
  for (const vendorId of manifest.vendors || []) {
    if (!vendorIds.has(vendorId)) {
      failures.push(`${manifest.moduleId}: unknown vendor ${vendorId}`);
    }
  }
  for (const testFile of manifest.tests || []) {
    if (!existsSync(join(root, testFile))) {
      failures.push(`${manifest.moduleId}: missing test ${testFile}`);
    }
  }
  for (const source of manifest.sources || []) {
    if (!existsSync(join(root, source))) {
      failures.push(`${manifest.moduleId}: missing source ${source}`);
    }
  }
}

const frontController = readFileSync(join(root, 'public/index.php'), 'utf8');
for (const manifest of manifests) {
  for (const route of manifest.routes || []) {
    const registered = frontController.includes(`'${route}'`)
      || frontController.includes(`"${route}"`);
    if (!registered) failures.push(`${manifest.moduleId}: route not registered ${route}`);
  }
  const scenarioIds = new Set();
  for (const scenario of manifest.scenarios || []) {
    if (scenarioIds.has(scenario.id)) {
      failures.push(`${manifest.moduleId}: duplicate scenario ${scenario.id}`);
    }
    scenarioIds.add(scenario.id);
    if (!(manifest.routes || []).includes(scenario.route)) {
      failures.push(`${manifest.moduleId}/${scenario.id}: undeclared route ${scenario.route}`);
    }
    const expectedDensity = scenario.viewport?.width >= 1200 ? 'compact' : 'touch';
    if (scenario.density !== expectedDensity) {
      failures.push(`${manifest.moduleId}/${scenario.id}: density must be ${expectedDensity}`);
    }
    const goldenPath = join(root, scenario.golden || '');
    if (!scenario.golden || !existsSync(goldenPath)) {
      failures.push(`${manifest.moduleId}/${scenario.id}: missing golden ${scenario.golden || '(empty)'}`);
      continue;
    }
    const actual = createHash('sha256').update(readFileSync(goldenPath)).digest('hex');
    if (actual !== scenario.sha256) {
      failures.push(`${manifest.moduleId}/${scenario.id}: golden hash mismatch`);
    }
  }
}

const requiredViewportKeys = new Set(['390x844', '1180x820', '1440x900']);
const laboratoryViewportKeys = new Set(['1180x820', '1440x900']);
const scenarioKey = (scenario) => `${scenario.theme}/${scenario.viewport.width}x${scenario.viewport.height}`;
for (const familyId of governedFamilies) {
  const familyScenarios = (laboratoryManifest?.scenarios || [])
    .filter((scenario) => scenario.family === familyId);
  const keys = new Set(familyScenarios.map(scenarioKey));
  for (const viewport of laboratoryViewportKeys) {
    if (!keys.has(`dark/${viewport}`)) {
      failures.push(`design-system-laboratory: missing scenario ${familyId}/dark/${viewport}`);
    }
  }
}
const pilotScenarioKeys = new Set((programManifest?.scenarios || []).map(scenarioKey));
for (const theme of ['dark', 'linen']) {
  for (const viewport of requiredViewportKeys) {
    if (!pilotScenarioKeys.has(`${theme}/${viewport}`)) {
      failures.push(`programa-general: missing scenario ${theme}/${viewport}`);
    }
  }
}

const inventory = documents.get('docs/design-system/manifests/inventory.json');
const manifestDir = join(root, 'docs/design-system/manifests');
const actualManifests = readdirSync(manifestDir)
  .filter((file) => file.endsWith('.json')
    && !['inventory.json', 'goal-provenance.json'].includes(file));
const declaredManifests = new Set(inventory?.manifests || []);
for (const manifestFile of actualManifests) {
  if (!declaredManifests.has(manifestFile)) {
    failures.push(`inventory: missing manifest ${manifestFile}`);
  }
}

if (inventory?.provenanceManifest !== 'goal-provenance.json') {
  failures.push('inventory: provenanceManifest must be goal-provenance.json');
}

const provenance = documents.get('docs/design-system/manifests/goal-provenance.json');
const canonicalGoalSources = [
  'goals/design-system-nucleo-gobernanza/goal.md',
  'goals/design-system-nucleo-gobernanza/facts.md',
  'goals/design-system-nucleo-gobernanza/plan.md',
];
if (!/^[0-9a-f]{40}$/.test(provenance?.sourceCommit || '')) {
  failures.push('goal provenance: sourceCommit must be a full Git SHA');
}
const declaredCanonicalSources = new Set(
  (provenance?.canonicalSources || []).map((source) => source.path),
);
for (const sourcePath of canonicalGoalSources) {
  if (!declaredCanonicalSources.has(sourcePath)) {
    failures.push(`goal provenance: missing canonical source ${sourcePath}`);
  }
}
for (const source of [
  ...(provenance?.canonicalSources || []),
  ...(provenance?.historicalSources || []),
]) {
  const absolutePath = join(root, source.path || '');
  if (!source.path || !existsSync(absolutePath)) {
    failures.push(`goal provenance: missing source ${source.path || '(empty)'}`);
    continue;
  }
  const actual = createHash('sha256').update(readFileSync(absolutePath)).digest('hex');
  if (actual !== source.sha256) {
    failures.push(`goal provenance: hash mismatch ${source.path}`);
  }
}
for (const source of provenance?.historicalSources || []) {
  if (source.status !== 'superseded' || source.instructional !== false) {
    failures.push(`goal provenance: historical source ${source.path} must be superseded and non-instructional`);
  }
}

for (const [file, document] of documents) {
  if (file.endsWith('version.json') || file.includes('.schema.')) continue;
  if (document?.designSystemVersion !== version) {
    failures.push(`${file}: designSystemVersion must equal ${version}`);
  }
}

for (const file of [
  'docs/design-system/component-catalog.schema.json',
  'docs/design-system/stable-api.schema.json',
  'docs/design-system/ui-groups-inventory.schema.json',
  'docs/design-system/state-semantics.schema.json',
  'docs/design-system/family-approvals.schema.json',
  'docs/design-system/a11y-baseline.schema.json',
  'docs/design-system/a11y-exceptions.schema.json',
  'docs/design-system/module-manifest.schema.json',
]) {
  const schema = documents.get(file);
  if (schema?.$schema !== 'https://json-schema.org/draft/2020-12/schema') {
    failures.push(`${file}: unsupported $schema`);
  }
  if (!schema?.$id) failures.push(`${file}: missing $id`);
  if (!Array.isArray(schema?.required)) failures.push(`${file}: missing required`);
  if (schema?.additionalProperties !== false) {
    failures.push(`${file}: additionalProperties must be false`);
  }
}

if (failures.length > 0) {
  console.error('Design system contracts: FAIL');
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}

console.log('Design system contracts: PASS');
