#!/usr/bin/env node
import { createHash } from 'node:crypto';
import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

export function consumerContractFailures({ root, manifest, viewOverride = null, cssOverride = null }) {
  const failures = [];
  const read = (file) => {
    const absolute = join(root, file);
    if (!existsSync(absolute)) {
      failures.push(`${manifest.moduleId}: missing ${file}`);
      return '';
    }
    return readFileSync(absolute, 'utf8');
  };

  if (manifest.consumerContract !== 'v1') return failures;

  const sources = manifest.sources || [];
  const viewSource = sources.find((s) => s.endsWith('.view.php')) ?? 'views/core/project_selector.view.php';
  const cssSource = sources.find((s) => /project-selector\.css$|\/module\.css$/.test(s))
    ?? sources.find((s) => s.endsWith('.css')) ?? 'public/css/project-selector.css';
  const view = viewOverride ?? read(viewSource);
  const css = cssOverride ?? read(cssSource);
  const required = [
    '/css/tokens.css',
    '/css/aia-design-system.css',
  ];
  for (const asset of required) {
    if (!view.includes(asset)) failures.push(`${manifest.moduleId}: canonical asset missing ${asset}`);
  }

  for (const primitive of ['aia-shell', 'aia-card', 'aia-input', 'aia-btn', 'aia-chip', 'aia-empty', 'aia-alert']) {
    if (!view.includes(primitive)) failures.push(`${manifest.moduleId}: missing primitive ${primitive}`);
  }

  const forbiddenView = [
    [/https?:\/\//i, 'external URL/CDN'],
    [/adminlte/i, 'AdminLTE skin'],
    [/<style\b/i, 'inline style block'],
    [/\sstyle\s*=/i, 'inline style attribute'],
  ];
  for (const [pattern, label] of forbiddenView) {
    if (pattern.test(view)) failures.push(`${manifest.moduleId}: ${label} is forbidden`);
  }

  const forbiddenCss = [
    [/#(?:[0-9a-f]{3,8})\b/i, 'raw hex color'],
    [/\brgba?\s*\(/i, 'raw rgb color'],
    [/gradient\s*\(/i, 'gradient'],
    [/!important\b/i, '!important'],
  ];
  for (const [pattern, label] of forbiddenCss) {
    if (pattern.test(css)) failures.push(`${manifest.moduleId}: ${label} is forbidden`);
  }
  for (const [property, label] of [
    ['font-family', 'local font family'],
    ['font-size', 'local font size'],
    ['border-radius', 'local radius'],
    ['box-shadow', 'local shadow'],
  ]) {
    const declaration = new RegExp(`${property}\\s*:\\s*([^;]+);`, 'ig');
    for (const match of css.matchAll(declaration)) {
      if (!match[1].trim().startsWith('var(')) failures.push(`${manifest.moduleId}: ${label} is forbidden`);
    }
  }

  for (const source of manifest.sources || []) {
    if (!existsSync(join(root, source))) failures.push(`${manifest.moduleId}: missing source ${source}`);
  }
  for (const test of manifest.tests || []) {
    if (!existsSync(join(root, test))) failures.push(`${manifest.moduleId}: missing test ${test}`);
  }
  for (const evidence of manifest.evidence || []) {
    if (!existsSync(join(root, evidence))) failures.push(`${manifest.moduleId}: missing evidence ${evidence}`);
  }
  if ((manifest.exceptions || []).length > 0) {
    failures.push(`${manifest.moduleId}: consumer contract v1 has no unreviewed exceptions`);
  }

  for (const scenario of manifest.scenarios || []) {
    const golden = join(root, scenario.golden || '');
    if (!existsSync(golden)) {
      failures.push(`${manifest.moduleId}/${scenario.id}: missing golden`);
      continue;
    }
    const hash = createHash('sha256').update(readFileSync(golden)).digest('hex');
    if (hash !== scenario.sha256) failures.push(`${manifest.moduleId}/${scenario.id}: golden hash mismatch`);
  }

  return failures;
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  const root = process.cwd();
  const explicit = process.argv[2];
  const readManifest = (rel) => JSON.parse(readFileSync(join(root, 'docs/design-system/manifests', rel), 'utf8'));

  let manifests;
  if (explicit) {
    manifests = [JSON.parse(readFileSync(join(root, explicit), 'utf8'))];
  } else {
    const inventory = JSON.parse(readFileSync(join(root, 'docs/design-system/manifests/inventory.json'), 'utf8'));
    manifests = (inventory.manifests || [])
      .map(readManifest)
      .filter((m) => m.consumerContract === 'v1');
  }

  const failures = manifests.flatMap((manifest) =>
    consumerContractFailures({ root, manifest }).map((f) => `[${manifest.moduleId}] ${f}`));

  if (failures.length) {
    console.error('Design system consumer contracts: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log(`Design system consumer contracts: PASS (${manifests.length} manifiesto/s v1)`);
}
