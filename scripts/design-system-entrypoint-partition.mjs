#!/usr/bin/env node
// scripts/design-system-entrypoint-partition.mjs
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { basename, join } from 'node:path';
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
  const viewOwnedBlock = php.match(/const VIEW_OWNED_VENDORS = \[([^\]]*)\]/s)?.[1] ?? '';
  const attachmentsBlock = php.match(/const VENDOR_ATTACHMENTS = \[([^\]]*)\]/s)?.[1] ?? '';
  const coreVendors = [...coreBlock.matchAll(/'([a-z0-9-]+)'/g)].map(([, v]) => v);
  const viewOwnedVendors = [...viewOwnedBlock.matchAll(/'([a-z0-9-]+)'/g)].map(([, v]) => v);
  const attachments = [...attachmentsBlock.matchAll(/'([a-z0-9-]+)' => '([^']+)'/g)]
    .map(([, vendor, url]) => ({ vendor, url }));
  return { coreVendors, viewOwnedVendors, attachments };
}

const MANIFEST_DIR = 'docs/design-system/manifests';

function moduleManifests(root) {
  const dir = join(root, MANIFEST_DIR);
  return readdirSync(dir)
    .filter((name) => name.endsWith('.json'))
    .sort()
    .map((name) => {
      const file = `${MANIFEST_DIR}/${name}`;
      try {
        return { file, manifest: JSON.parse(readFileSync(join(dir, name), 'utf8')) };
      } catch {
        return { file, manifest: null };
      }
    });
}

/**
 * Todo vendor de TODO manifiesto de módulo debe resolver contra el registro de
 * PHP, esté o no su vista cableada a `renderForModule`.
 *
 * `coherenceFailures` solo mira los manifiestos que alguna vista usa hoy, y por
 * ese hueco entraron `toastr` (programa-general), `tom-select`
 * (programacion-intermedia, laboratory) y `adminlte` (laboratory): quedan
 * latentes hasta que alguien migra el módulo, y entonces `moduleVendors()`
 * devuelve `null` y `renderForModule()` degrada al agregador completo dejando
 * solo un `error_log`. Se recorre el directorio, no las vistas, precisamente
 * para cubrir los manifiestos aún no cableados.
 *
 * Valida además el criterio de pertenencia a `VIEW_OWNED_VENDORS`, que hasta
 * ahora era prosa en el docblock de PHP y nada más. Sin candado, mover ahí un
 * vendor que sí tiene adjunto —se comprobó con `select2`— dejaba los tres gates
 * en verde mientras `renderForModule()` dejaba de emitir su `attach-*.css`:
 * "cargar de menos" entrando por la categoría nueva. La debilidad es del diseño
 * del registro y no de esta categoría (meter `select2` en `CORE_VENDORS` da el
 * mismo verde), así que este candado cierra la puerta que puede cerrarse, no el
 * patrón entero: `CORE_VENDORS` sigue sin criterio verificable.
 */
export function manifestVendorFailures({
  root,
  manifestsOverride = null,
  viewsOverride = null,
  registryOverride = null,
  footprintsOverride = null,
}) {
  const failures = [];
  const { coreVendors, viewOwnedVendors, attachments } = registryOverride ?? phpVendorRegistry(root);
  if (coreVendors.length === 0 || attachments.length === 0) {
    return ['php-registry-unreadable: no se pudieron extraer CORE_VENDORS/VENDOR_ATTACHMENTS'];
  }
  const known = new Set([
    ...coreVendors,
    ...viewOwnedVendors,
    ...attachments.map(({ vendor }) => vendor),
  ]);

  // Un `VIEW_OWNED_VENDORS` es, por definición, un vendor cuya hoja enlaza la
  // propia vista y del que este head no emite nada. Se verifican las dos mitades:
  // que no exista adjunto alguno para él, y que alguna vista lo enlace de verdad.
  const attachmentVendors = new Set([
    ...Object.keys(ENTRYPOINT_FILES.attachments),
    ...Object.keys(STANDALONE_ATTACHMENTS),
    ...attachments.map(({ vendor }) => vendor),
  ]);
  if (viewOwnedVendors.length) {
    const footprints = footprintsOverride ?? vendorViewFootprints(root);
    const linkTags = (viewsOverride ?? [...phpViews(root)])
      .flatMap(({ content }) => content.match(/<link\b[^>]*>/g) ?? []);
    for (const vendor of viewOwnedVendors) {
      if (attachmentVendors.has(vendor)) {
        failures.push(
          `view-owned-with-attachment: ${vendor} está en VIEW_OWNED_VENDORS pero tiene adjunto; `
          + 'renderForModule() dejaría de emitir su adaptador oscuro',
        );
        continue;
      }
      const needles = footprints[vendor] ?? [];
      if (!linkTags.some((tag) => needles.some((needle) => tag.includes(needle)))) {
        failures.push(
          `view-owned-without-link: ${vendor} está en VIEW_OWNED_VENDORS pero ninguna vista `
          + 'enlaza su hoja (huellas conocidas: ' + (needles.join(', ') || 'ninguna') + ')',
        );
      }
    }
  }

  for (const { file, manifest } of manifestsOverride ?? moduleManifests(root)) {
    if (manifest === null || typeof manifest !== 'object') {
      failures.push(`manifest-unparseable: ${file}`);
      continue;
    }
    // inventory.json y goal-provenance.json no son manifiestos de módulo.
    if (typeof manifest.moduleId !== 'string') continue;
    if (!Array.isArray(manifest.vendors)) {
      failures.push(`manifest-vendors-missing: ${file}`);
      continue;
    }
    for (const vendor of manifest.vendors) {
      if (typeof vendor !== 'string' || !known.has(vendor)) {
        failures.push(`unresolvable-vendor: ${JSON.stringify(vendor)} en ${file}`);
      }
    }
  }

  return failures;
}

/**
 * `moduleVendors()` en PHP resuelve el manifiesto como
 * `docs/design-system/manifests/{moduleId}.json`: si el nombre del archivo no
 * coincide con su `moduleId`, `renderForModule($moduleId)` no lo encuentra
 * nunca y degrada silenciosamente al agregador completo con un `error_log`.
 * El defecto queda latente hasta que alguien cablea la vista, así que se
 * recorre el directorio entero y no las vistas.
 *
 * Los archivos sin `moduleId` (inventory.json, goal-provenance.json) no son
 * manifiestos de módulo: se excluyen por ausencia del campo, nunca por lista
 * fija de nombres.
 */
export function manifestIdentityFailures({ root, manifestsOverride = null }) {
  const failures = [];

  for (const { file, manifest } of manifestsOverride ?? moduleManifests(root)) {
    if (manifest === null || typeof manifest !== 'object') continue;
    if (typeof manifest.moduleId !== 'string') continue;
    const expected = basename(file, '.json');
    if (manifest.moduleId !== expected) {
      failures.push(
        `manifest-id-mismatch: ${file} declara moduleId="${manifest.moduleId}" `
        + `(renderForModule('${manifest.moduleId}') buscaría ${MANIFEST_DIR}/${manifest.moduleId}.json)`,
      );
    }
  }

  return failures;
}

const VENDORS_CATALOG = 'docs/design-system/vendors.json';

/**
 * Huellas de CDN, a mano y en lista cerrada.
 *
 * `vendors.json` describe los assets *locales* de cada vendor; los que entran
 * por CDN no tienen ruta en disco que buscar (`adminlte` ni siquiera declara
 * `assets`). Cada fragmento de esta tabla se midió contra las URLs absolutas
 * que hoy aparecen en `views/`: son subcadenas literales, no patrones, para que
 * un falso positivo exija que alguien escriba la URL del vendor sin usarlo.
 */
export const VENDOR_CDN_FOOTPRINTS = {
  adminlte: ['admin-lte@'],
  anychart: ['cdn.anychart.com'],
  bootstrap: ['/npm/bootstrap@', 'bootstrapcdn.com/bootstrap/'],
  datatables: ['cdn.datatables.net'],
  'font-awesome': ['/libs/font-awesome/'],
  handsontable: ['/npm/handsontable@'],
  jquery: ['/libs/jquery/', 'code.jquery.com/jquery-'],
  'jquery-ui': ['code.jquery.com/ui/'],
  select2: ['/libs/select2/'],
  'tom-select': ['/npm/tom-select@'],
};

/**
 * vendor → subcadenas cuya presencia en el markup de una vista prueba que esa
 * vista carga el vendor. Se derivan de `vendors.json` (asset local
 * `public/x/y.css` → `/x/y.css`, que es como lo escriben las vistas) más
 * `VENDOR_CDN_FOOTPRINTS`.
 */
export function vendorViewFootprints(root, catalogOverride = null) {
  const catalog = catalogOverride
    ?? JSON.parse(readFileSync(join(root, VENDORS_CATALOG), 'utf8'));
  const footprints = {};
  for (const vendor of catalog.vendors ?? []) {
    const needles = new Set(VENDOR_CDN_FOOTPRINTS[vendor.id] ?? []);
    for (const asset of vendor.assets ?? []) {
      if (asset.startsWith('public/')) needles.add(`/${asset.slice('public/'.length)}`);
    }
    if (needles.size) footprints[vendor.id] = [...needles];
  }
  return footprints;
}

function viewsByModule(views) {
  const byModule = new Map();
  for (const view of views) {
    for (const [, moduleId] of view.content.matchAll(/renderForModule\('([^']+)'\)/g)) {
      if (!byModule.has(moduleId)) byModule.set(moduleId, []);
      if (!byModule.get(moduleId).includes(view)) byModule.get(moduleId).push(view);
    }
  }
  return byModule;
}

/**
 * Espejo de `manifestVendorFailures`: aquélla caza el vendor declarado de MÁS
 * (está en el manifiesto y no en el registro PHP); ésta caza el declarado de
 * MENOS — la vista carga un vendor que su manifiesto calla.
 *
 * Es la dirección peligrosa. El contrato de `renderForModule()` es «siempre
 * cargar de más, nunca de menos»: un vendor que nadie declaró es un vendor que
 * nadie vigila, y el día que gane un `attach-<vendor>.css` la superficie se
 * queda sin su adaptador oscuro sin que ninguna suite lo vea. Es la regresión
 * que describe el check #6 de tests/test_design_system_head_component.php.
 *
 * ALCANCE — lo que este gate NO cubre, para que su silencio no se lea como
 * cobertura total:
 *
 * 1. Solo mira las vistas cableadas a `renderForModule('X')`. Los `sources[]`
 *    del manifiesto y los módulos aún en `render()` quedan fuera: ahí no hay
 *    head segmentado que pueda cargar de menos.
 * 2. Solo ve el markup literal de esas vistas. No sigue `include`/`require` de
 *    parciales, no evalúa PHP, y no ve los assets que inyecta JavaScript en
 *    caliente — `linksComunesHead2.js` mete select2 y sweetalert2 en varias
 *    rutas y este gate no lo sabe. Tampoco mira los `@import` de un CSS propio.
 * 3. `VENDOR_CDN_FOOTPRINTS` es una lista cerrada escrita a mano: un vendor que
 *    llegue por un CDN nuevo es invisible hasta que alguien añada su fragmento.
 * 4. Los `CORE_VENDORS` se excluyen a propósito. `renderForModule()` no emite
 *    nada por ellos —su CSS ya viaja dentro de `core.css`, incondicional—, así
 *    que declararlos de menos no puede perder ningún adaptador. Exigirlos solo
 *    añadiría ruido (hoy: `jquery` en las tres vistas de auth).
 * 5. No comprueba versiones, ni que el vendor detectado se use de verdad: basta
 *    con que la vista enlace su asset.
 */
export function manifestUnderDeclarationFailures({
  root,
  viewsOverride = null,
  footprintsOverride = null,
  manifestsOverride = null,
}) {
  const failures = [];
  const { coreVendors } = phpVendorRegistry(root);
  if (coreVendors.length === 0) {
    return ['php-registry-unreadable: no se pudieron extraer CORE_VENDORS'];
  }
  const footprints = footprintsOverride ?? vendorViewFootprints(root);
  const views = viewsOverride ?? [...phpViews(root)];
  const manifestsById = new Map(
    (manifestsOverride ?? moduleManifests(root))
      .filter(({ manifest }) => manifest && typeof manifest.moduleId === 'string')
      .map(({ manifest }) => [manifest.moduleId, manifest]),
  );

  for (const [moduleId, moduleViews] of [...viewsByModule(views)].sort()) {
    const manifest = manifestsById.get(moduleId);
    // Un manifiesto ausente o ilegible ya lo cazan coherenceFailures /
    // manifestVendorFailures; aquí no hay nada contra lo que comparar.
    if (!manifest || !Array.isArray(manifest.vendors)) continue;
    const declared = new Set(manifest.vendors);
    const detected = new Map();
    for (const { file, content } of moduleViews) {
      for (const [vendor, needles] of Object.entries(footprints)) {
        if (declared.has(vendor) || coreVendors.includes(vendor)) continue;
        const hit = needles.find((needle) => content.includes(needle));
        if (hit !== undefined && !detected.has(vendor)) detected.set(vendor, { file, hit });
      }
    }
    for (const vendor of [...detected.keys()].sort()) {
      const { file, hit } = detected.get(vendor);
      failures.push(
        `undeclared-vendor: ${vendor} lo carga ${file} pero `
        + `${MANIFEST_DIR}/${moduleId}.json no lo declara (huella "${hit}")`,
      );
    }
  }

  return failures;
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
  const { coreVendors, viewOwnedVendors, attachments } = phpVendorRegistry(root);
  if (coreVendors.length === 0 || attachments.length === 0) {
    return ['php-registry-unreadable: no se pudieron extraer CORE_VENDORS/VENDOR_ATTACHMENTS'];
  }
  const known = new Set([
    ...coreVendors,
    ...viewOwnedVendors,
    ...attachments.map(({ vendor }) => vendor),
  ]);

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
  const failures = [
    ...partitionFailures({ root }),
    ...coherenceFailures({ root }),
    ...manifestVendorFailures({ root }),
    ...manifestIdentityFailures({ root }),
    ...manifestUnderDeclarationFailures({ root }),
  ];
  if (failures.length) {
    console.error('Design system entrypoint partition: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log('Design system entrypoint partition: PASS');
}
