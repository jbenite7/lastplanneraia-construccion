#!/usr/bin/env node
// scripts/design-system-entrypoint-partition.mjs
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
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

// Adjuntos que NO son miembros de la partición porque su CSS nunca estuvo en el
// agregador: `render()` los emite como hojas hermanas de `aia-design-system.css`.
// Exigirles que sumen al agregador daría `extra-in-partition` sobre algo que es
// correcto por diseño, así que quedan fuera de `partitionFailures` y se validan
// aquí: existen en disco y son destino legítimo de un adjunto de PHP. La lista
// es cerrada a propósito — cualquier otra URL fuera de la partición sigue siendo
// `attachment-url-drift`.
export const STANDALONE_ATTACHMENTS = {
  datatables: 'public/css/design-system/vendor-datatables-legacy.css',
};

// Import propio de la partición, ausente del agregador por diseño.
const THEME_OVERRIDES_IMPORT = '/css/design-system/entrypoints/theme-overrides.css';
const IMPORT_PATTERN = /@import url\("([^"]+)"\)(?: layer\(([a-z-]+)\))?;/g;
// Misma forma que IMPORT_PATTERN, anclada a la línea completa: es la única
// forma canónica de import permitida. Cualquier otra sintaxis (comillas
// simples, sin url(), media query, etc.) es CSS válido pero invisible para
// IMPORT_PATTERN/matchAll, así que se detecta aquí por separado.
const IMPORT_LINE_PATTERN = /^@import url\("([^"]+)"\)(?: layer\(([a-z-]+)\))?;$/;

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

function collectUnparseableImports(file, css, failures) {
  for (const rawLine of css.split('\n')) {
    const line = rawLine.trim();
    if (line.includes('@import') && !IMPORT_LINE_PATTERN.test(line)) {
      failures.push(`unparseable-import: ${file}: ${line}`);
    }
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

  // Cualquier @import que no siga IMPORT_PATTERN es invisible para parseImports
  // en todos los chequeos siguientes: se detecta aparte, línea a línea, en
  // cada miembro con imports propios (theme-overrides.css no tiene imports).
  collectUnparseableImports(ENTRYPOINT_FILES.aggregator, aggregator, failures);
  collectUnparseableImports(ENTRYPOINT_FILES.core, core, failures);
  for (const [vendor, css] of Object.entries(attachments)) {
    collectUnparseableImports(ENTRYPOINT_FILES.attachments[vendor], css, failures);
  }

  const aggregatorImports = parseImports(aggregator);
  const coreImports = parseImports(core);

  // core.css es el único miembro de la partición con import propio
  // (theme-overrides.css); debe estar presente exactamente una vez y en
  // última posición, o @layer theme + legacy-overrides se pierden en
  // silencio para toda superficie migrada.
  const themeOverridesImportsInCore = coreImports.filter(({ url }) => url === THEME_OVERRIDES_IMPORT);
  const themeOverridesIsLast = coreImports.length > 0
    && coreImports[coreImports.length - 1].url === THEME_OVERRIDES_IMPORT;
  if (themeOverridesImportsInCore.length !== 1 || !themeOverridesIsLast) {
    failures.push(`theme-overrides-missing: core.css debe importar ${THEME_OVERRIDES_IMPORT} como último import`);
  }

  const partitionMembers = [
    ['core', coreImports.filter(({ url }) => url !== THEME_OVERRIDES_IMPORT)],
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

const HEAD_COMPONENT = 'src/View/Components/DesignSystemHeadComponent.php';

// PHP es la fuente de verdad de vendors core y adjuntos; el gate los parsea
// para que no exista una segunda copia que mantener sincronizada a mano.
export function phpVendorRegistry(root) {
  const php = readFileSync(join(root, HEAD_COMPONENT), 'utf8');
  const coreBlock = php.match(/const CORE_VENDORS = \[([^\]]*)\]/s)?.[1] ?? '';
  const attachmentsBlock = php.match(/const VENDOR_ATTACHMENTS = \[([^\]]*)\]/s)?.[1] ?? '';
  const coreVendors = [...coreBlock.matchAll(/'([a-z0-9-]+)'/g)].map(([, v]) => v);
  const attachments = [...attachmentsBlock.matchAll(/'([a-z0-9-]+)' => '([^']+)'/g)]
    .map(([, vendor, url]) => ({ vendor, url }));
  return { coreVendors, attachments };
}

function* phpViews(root) {
  const stack = [join(root, 'views')];
  while (stack.length) {
    const dir = stack.pop();
    for (const name of readdirSync(dir)) {
      const path = join(dir, name);
      if (statSync(path).isDirectory()) stack.push(path);
      else if (name.endsWith('.php')) {
        yield { file: path.slice(root.length).replace(/^\//, ''), content: readFileSync(path, 'utf8') };
      }
    }
  }
}

export function coherenceFailures({ root, viewsOverride = null, manifestsOverride = null }) {
  const failures = [];
  const { coreVendors, attachments } = phpVendorRegistry(root);
  if (coreVendors.length === 0 || attachments.length === 0) {
    return ['php-registry-unreadable: no se pudieron extraer CORE_VENDORS/VENDOR_ATTACHMENTS'];
  }
  const known = new Set([...coreVendors, ...attachments.map(({ vendor }) => vendor)]);

  // 1. Todo adjunto PHP apunta a un archivo de la partición (o a un standalone
  //    declarado) y existe.
  for (const [vendor, file] of Object.entries(STANDALONE_ATTACHMENTS)) {
    if (!existsSync(join(root, file))) {
      failures.push(`standalone-attachment-missing: ${vendor} → ${file}`);
    }
  }
  const partitionUrls = new Set(
    [...Object.values(ENTRYPOINT_FILES.attachments), ...Object.values(STANDALONE_ATTACHMENTS)]
      .map((file) => file.replace('public', '')),
  );
  for (const { vendor, url } of attachments) {
    if (!partitionUrls.has(url)) failures.push(`attachment-url-drift: ${vendor} → ${url}`);
  }

  // 2. Toda vista que llama renderForModule('X') tiene manifiesto X válido.
  const views = viewsOverride ?? [...phpViews(root)];
  const usedModules = new Set();
  for (const { file, content } of views) {
    for (const match of content.matchAll(/renderForModule\('([^']+)'\)/g)) {
      const moduleId = match[1];
      usedModules.add(moduleId);
      const manifestPath = join(root, 'docs/design-system/manifests', `${moduleId}.json`);
      try {
        JSON.parse(readFileSync(manifestPath, 'utf8'));
      } catch {
        failures.push(`missing-manifest: ${moduleId} (usado por ${file})`);
      }
    }
  }

  // 3. Todo vendors[] de los manifiestos usados (o inyectados) resuelve contra PHP.
  const manifests = manifestsOverride ?? [...usedModules].flatMap((moduleId) => {
    try {
      return [JSON.parse(readFileSync(join(root, 'docs/design-system/manifests', `${moduleId}.json`), 'utf8'))];
    } catch {
      return [];
    }
  });
  for (const manifest of manifests) {
    for (const vendor of manifest.vendors ?? []) {
      if (!known.has(vendor)) {
        failures.push(`unknown-vendor: ${vendor} (manifiesto ${manifest.moduleId})`);
      }
    }
  }

  return failures;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const root = process.cwd();
  const failures = [...partitionFailures({ root }), ...coherenceFailures({ root })];
  if (failures.length) {
    console.error('Design system entrypoint partition: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log('Design system entrypoint partition: PASS');
}
