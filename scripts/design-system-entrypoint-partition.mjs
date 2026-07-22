#!/usr/bin/env node
// scripts/design-system-entrypoint-partition.mjs
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

export const ENTRYPOINT_FILES = {
  aggregator: 'public/css/aia-design-system.css',
  core: 'public/css/design-system/entrypoints/core.css',
  themeOverrides: 'public/css/design-system/entrypoints/theme-overrides.css',
  attachments: {
    'jquery-ui': 'public/css/design-system/entrypoints/attach-jquery-ui.css',
    anychart: 'public/css/design-system/entrypoints/attach-anychart.css',
    select2: 'public/css/design-system/entrypoints/attach-select2.css',
    sweetalert2: 'public/css/design-system/entrypoints/attach-sweetalert2.css',
    handsontable: 'public/css/design-system/entrypoints/attach-handsontable.css',
  },
};

// Import propio de la partición, ausente del agregador por diseño.
const THEME_OVERRIDES_IMPORT = '/css/design-system/entrypoints/theme-overrides.css';
const IMPORT_PATTERN = /@import url\("([^"]+)"\)(?: layer\(([a-z-]+)\))?;/g;

function parseImports(css) {
  return [...css.matchAll(IMPORT_PATTERN)].map(([, url, layer]) => ({
    url: url.replace(/\?v=[0-9.]+$/, ''),
    layer: layer ?? null,
  }));
}

function readOrFail(root, file, failures) {
  try {
    return readFileSync(join(root, file), 'utf8');
  } catch {
    failures.push(`missing-file: ${file}`);
    return '';
  }
}

export function partitionFailures({
  root,
  coreOverride = null,
  themeOverridesOverride = null,
  attachmentOverrides = {},
}) {
  const failures = [];
  const aggregator = readOrFail(root, ENTRYPOINT_FILES.aggregator, failures);
  const core = coreOverride ?? readOrFail(root, ENTRYPOINT_FILES.core, failures);
  const themeOverrides = themeOverridesOverride
    ?? readOrFail(root, ENTRYPOINT_FILES.themeOverrides, failures);
  const attachments = Object.fromEntries(
    Object.entries(ENTRYPOINT_FILES.attachments).map(([vendor, file]) => [
      vendor,
      attachmentOverrides[vendor] ?? readOrFail(root, file, failures),
    ]),
  );
  if (failures.length) return failures;

  const aggregatorImports = parseImports(aggregator);
  const partitionMembers = [
    ['core', parseImports(core).filter(({ url }) => url !== THEME_OVERRIDES_IMPORT)],
    ...Object.entries(attachments).map(([vendor, css]) => [vendor, parseImports(css)]),
  ];

  const seen = new Map();
  for (const [owner, imports] of partitionMembers) {
    for (const entry of imports) {
      if (seen.has(entry.url)) {
        failures.push(`duplicated-in-partition: ${entry.url} (${seen.get(entry.url)} y ${owner})`);
      }
      seen.set(entry.url, owner);
    }
  }

  const aggregatorUrls = new Set(aggregatorImports.map(({ url }) => url));
  for (const { url } of aggregatorImports) {
    if (!seen.has(url)) failures.push(`missing-from-partition: ${url}`);
  }
  for (const url of seen.keys()) {
    if (!aggregatorUrls.has(url)) failures.push(`extra-in-partition: ${url}`);
  }

  // Cada miembro conserva el orden relativo y la capa que sus imports tienen en el agregador.
  const aggregatorIndex = new Map(aggregatorImports.map(({ url }, index) => [url, index]));
  const aggregatorLayer = new Map(aggregatorImports.map(({ url, layer }) => [url, layer]));
  for (const [owner, imports] of partitionMembers) {
    let last = -1;
    for (const { url, layer } of imports) {
      const index = aggregatorIndex.get(url);
      if (index === undefined) continue;
      if (index < last) failures.push(`order-drift: ${url} en ${owner}`);
      last = index;
      if (aggregatorLayer.get(url) !== layer) {
        failures.push(`layer-drift: ${url} en ${owner} (agregador: ${aggregatorLayer.get(url)}, partición: ${layer})`);
      }
    }
  }

  // La declaración de capas de core debe ser la canónica del agregador.
  const layerDeclaration = aggregator.match(/^@layer [^;]+;/m)?.[0];
  if (!core.startsWith(layerDeclaration ?? '@layer')) {
    failures.push('layer-declaration-drift: core.css no abre con la declaración canónica de capas');
  }

  // Bloques inline del agregador == theme-overrides.css, textualmente.
  const inlineStart = aggregator.indexOf('@layer theme {');
  const inlineBlocks = inlineStart === -1 ? '' : aggregator.slice(inlineStart).trim();
  if (inlineBlocks !== themeOverrides.trim()) {
    failures.push('theme-overrides-drift: los bloques inline del agregador y theme-overrides.css difieren');
  }

  return failures;
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  const failures = partitionFailures({ root: process.cwd() });
  if (failures.length) {
    console.error('Design system entrypoint partition: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log('Design system entrypoint partition: PASS');
}
