#!/usr/bin/env node
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { join } from 'node:path';
import process from 'node:process';

import { closeoutContractFailures } from './design-system-closeout-contract.mjs';

const root = process.cwd();
const manifestsDir = 'docs/design-system/manifests';
const inventoryPath = `${manifestsDir}/inventory.json`;

// El inventario es la unica fuente de verdad para "que manifiestos existen":
// se lee aqui, temprano y por su cuenta, para poder derivar de el tanto la
// lista de archivos requeridos como la lista de manifiestos que el gate
// valida linea por linea mas abajo. Sin esto, un manifiesto nuevo (o uno
// retirado) podia quedar fuera del gate en silencio -- exactamente lo que
// paso con foundation-shell.json y sus rutas muertas del PDC v1.
let inventoryManifestFiles = [];
if (existsSync(join(root, inventoryPath))) {
  try {
    const inventoryDoc = JSON.parse(readFileSync(join(root, inventoryPath), 'utf8'));
    inventoryManifestFiles = (inventoryDoc.manifests || [])
      .filter((name) => !['inventory.json', 'goal-provenance.json'].includes(name));
  } catch {
    // Un inventory.json invalido se reporta mas abajo via readJson(); aqui
    // simplemente no hay manifiestos que derivar todavia.
  }
}

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
  `${manifestsDir}/goal-provenance.json`,
  inventoryPath,
  'docs/design-system/closeout-evidence.json',
  'goals/design-system-nucleo-gobernanza/validation-log.md',
  ...inventoryManifestFiles.map((file) => `${manifestsDir}/${file}`),
];
const failures = [];

// `required` se amplia con `inventoryManifestFiles` a proposito: los 15
// manifiestos del inventario ahora entran en `documents` (mas abajo), y ese
// Map es lo que recorre el chequeo generico
// `designSystemVersion must equal ${version}` al final del archivo. Son dos
// preguntas distintas:
//   - "todo manifiesto declarado se valida linea por linea" -> lo cubre
//     `manifests` (mas abajo), derivado de `inventoryManifestFiles` y leido
//     por su cuenta, sin pasar por `documents`;
//   - "todo documento del design system declara la version vigente" -> lo
//     cubre `documents`, y por eso los manifiestos del inventario estan
//     listados aqui: sacarlos les quitaria en silencio el chequeo de version.
// Los once manifiestos que quedaron en 1.0.0 tras la campana que publico
// 1.1.0 ya subieron a la version vigente (ver CHANGELOG); todos los del
// inventario pasan ahora por este chequeo.

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

// Viewports soportados: el conjunto que el sistema acepta. Requeridos: el que
// toda familia debe cubrir con evidencia. Se separaron en F1 (DS-032) para
// reabrir el ancho movil sin exigir goldens que aun no existen; DS-031 los
// habia fundido en uno solo.
const SUPPORTED_VIEWPORTS = new Set(['1180x820', '1440x900', '390x844']);
const REQUIRED_VIEWPORTS = ['1180x820', '1440x900'];

const approvalKeys = new Set();
for (const approval of approvals?.approvals || []) {
  const key = `${approval.familyId}/${approval.candidateId}`;
  if (approvalKeys.has(key)) failures.push(`duplicate family approval: ${key}`);
  approvalKeys.add(key);
  const family = (homologation?.families || []).find((item) => item.id === approval.familyId);
  const candidate = family?.candidates?.find((item) => item.id === approval.candidateId);
  if (!candidate) failures.push(`family approval: unknown candidate ${key}`);
  if (!approval.evidence?.length) failures.push(`${key}: approval requires evidence`);
  // Toda aprobacion sigue cubriendo dark unicamente; la distincion que
  // introducia `scope: desktop-dark` dejo de tener efecto. 390x844 se reabrio
  // el 2026-08-07 como viewport soportado pero no exigido (ver
  // tests/design-system/mobile-viewport-scope.test.mjs): todavia ninguna
  // familia lo declara.
  if (JSON.stringify(approval.themes) !== JSON.stringify(['dark'])) {
    failures.push(`${key}: approval must cover dark`);
  }
  for (const viewport of approval.viewports || []) {
    if (!SUPPORTED_VIEWPORTS.has(viewport)) {
      failures.push(`${key}: approval declares unsupported viewport ${viewport}`);
    }
  }
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!(approval.viewports || []).includes(viewport)) {
      failures.push(`${key}: approval must cover ${viewport}`);
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
  for (const viewport of family.viewports || []) {
    if (!SUPPORTED_VIEWPORTS.has(viewport)) {
      failures.push(`${family.id}: unsupported viewport ${viewport}`);
    }
  }
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!(family.viewports || []).includes(viewport)) {
      failures.push(`${family.id}: missing required viewport ${viewport}`);
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
  if (JSON.stringify(group.themes) !== JSON.stringify(['dark'])) {
    failures.push(`${group.id}: UI group must cover dark`);
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

// Validador de esquema DELIBERADAMENTE PARCIAL.
//
// Se aplica a los manifiestos de modulo y, desde esta revision, tambien a los
// siete pares esquema/documento del design system (SCHEMA_DOCUMENT_PAIRS, al
// final del archivo), que hasta ahora se comprobaban en su forma pero nunca
// contra su propio dato.
//
// Por que existe: hasta esta revision el gate solo leia `manifestSchema.required`
// para comprobar *presencia* de campos, y nunca aplicaba nada mas del esquema.
// Consecuencia medida: un escenario que declaraba el tema claro retirado (el
// prohibido por contrato en DS-030) con su golden nombrado en consecuencia
// pasaba el gate en verde, igual que propiedades inventadas pese al
// `additionalProperties: false` del propio esquema.
//
// Por que no es un validador de JSON Schema completo: este repositorio tiene
// tres dependencias en total y anadir un validador es una decision de producto,
// no del gate. Se implementa a mano lo minimo que cierra el agujero.
//
// LO QUE SE APLICA (y solo esto):
//   - `additionalProperties: false` -> ninguna propiedad no declarada.
//   - `enum` -> el valor debe ser uno de los declarados (asi entra `theme`,
//     `density` y `capture` desde el esquema, no reimplementados a mano).
//   - `const` -> el valor debe ser exactamente el declarado.
//   - `required` sobre subobjetos (escenarios y viewport); el `required` de
//     primer nivel se sigue comprobando aparte, mas abajo, para conservar el
//     mensaje `missing required field X` que ya cubren las pruebas.
//   - Recorrido de `properties`, `items` y `$ref` local (`#/$defs/...`) para
//     poder llegar a lo anterior.
//
// LO QUE NO SE APLICA (sigue sin comprobarse):
//   `type`, `pattern`, `minimum`, `maxItems`, `minItems`, `format`, `oneOf`,
//   `anyOf`, `allOf`, `not`, `if/then/else`, `$ref` remoto, `patternProperties`,
//   `dependentRequired` y cualquier otra palabra clave. Algunas de ellas
//   (`sha256` como hex de 64, el viewport minimo) las cubre por otro camino el
//   propio gate; otras simplemente no estan cubiertas. No leer esto como
//   "los manifiestos estan validados contra su esquema".
const SCHEMA_KEYWORDS_APPLIED = ['additionalProperties:false', 'enum', 'const', 'required', '$ref', 'items'];

// `enum` y `const` comparaban con `includes`/`!==`, es decir por IDENTIDAD.
// Para escalares da igual, pero en cuanto el valor es un array o un objeto dos
// valores de contenido identico son referencias distintas y el gate reportaba
// una violacion inexistente: `ui-groups-inventory.json` producia 87 falsos
// positivos del tipo `themes: valor ["dark"] distinto del const del esquema
// (["dark"])`. Un gate que miente asi es un gate que alguien desactiva, por eso
// la comparacion es estructural.
function deepEqual(a, b) {
  if (a === b) return true;
  if (typeof a !== 'object' || typeof b !== 'object' || a === null || b === null) return false;
  if (Array.isArray(a) !== Array.isArray(b)) return false;
  if (Array.isArray(a)) return a.length === b.length && a.every((item, i) => deepEqual(item, b[i]));
  const keys = Object.keys(a);
  return keys.length === Object.keys(b).length
    && keys.every((key) => Object.hasOwn(b, key) && deepEqual(a[key], b[key]));
}

function resolveSchemaRef(schema, rootSchema) {
  let current = schema;
  const seen = new Set();
  while (current && typeof current.$ref === 'string') {
    if (!current.$ref.startsWith('#/') || seen.has(current.$ref)) return null;
    seen.add(current.$ref);
    current = current.$ref.slice(2).split('/')
      .reduce((node, segment) => (node ? node[segment] : undefined), rootSchema);
  }
  return current;
}

function schemaPartialFailures(value, schema, rootSchema, path, isRoot = false) {
  const resolved = resolveSchemaRef(schema, rootSchema);
  if (!resolved || typeof resolved !== 'object') return [];
  const found = [];
  const where = path || '(raiz)';
  if (Array.isArray(resolved.enum) && !resolved.enum.some((option) => deepEqual(option, value))) {
    found.push(`${where}: valor ${JSON.stringify(value)} fuera del enum del esquema `
      + `(${resolved.enum.map((option) => JSON.stringify(option)).join(', ')})`);
  }
  if (Object.hasOwn(resolved, 'const') && !deepEqual(value, resolved.const)) {
    found.push(`${where}: valor ${JSON.stringify(value)} distinto del const del esquema `
      + `(${JSON.stringify(resolved.const)})`);
  }
  if (Array.isArray(value) && resolved.items) {
    value.forEach((item, index) => {
      found.push(...schemaPartialFailures(item, resolved.items, rootSchema, `${path}[${index}]`));
    });
    return found;
  }
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    const properties = resolved.properties || {};
    if (resolved.additionalProperties === false) {
      for (const key of Object.keys(value)) {
        if (!Object.hasOwn(properties, key)) {
          found.push(`${where}: propiedad no declarada en el esquema: ${key}`);
        }
      }
    }
    // El `required` de primer nivel del manifiesto se comprueba aparte (mensaje
    // `missing required field X`); aqui se cubren los subobjetos, que antes no
    // tenian ninguna comprobacion de campos obligatorios.
    if (!isRoot && Array.isArray(resolved.required)) {
      for (const key of resolved.required) {
        if (!Object.hasOwn(value, key)) {
          found.push(`${where}: falta el campo obligatorio ${key}`);
        }
      }
    }
    for (const [key, entry] of Object.entries(value)) {
      if (!Object.hasOwn(properties, key)) continue;
      found.push(...schemaPartialFailures(entry, properties[key], rootSchema, path ? `${path}.${key}` : key, false));
    }
  }
  return found;
}

const manifests = inventoryManifestFiles.map((name) => {
  const relPath = `${manifestsDir}/${name}`;
  if (!existsSync(join(root, relPath))) {
    failures.push(`${relPath}: missing`);
    return null;
  }
  const document = readJson(relPath);
  if (document) Object.defineProperty(document, '__file', { value: name, enumerable: false });
  return document;
}).filter(Boolean);
enforceUnique(manifests, 'moduleId', 'module manifest moduleId');

// `moduleId` era unico pero no estaba atado a nada: renombrar laboratory.json a
// cualquier otro nombre conservando su `moduleId` pasaba en verde, y auth.json
// podia declarar `moduleId: "no-soy-auth"` sin que nada lo notara. Se ata al
// nombre del archivo, que es la unica correspondencia que hoy cumplen los 15
// manifiestos sin excepciones.
//
// Se descarto atarlo a `inventory.modules[].moduleId` porque ese campo no
// describe el archivo: project-selector.json declara `project-selector` mientras
// el inventario lo llama `projects` (etiqueta de dominio que tambien usan
// legacy-aliases.json y operational-fixtures.json), y laboratory.json y
// foundation-shell.json no tienen entrada en `modules[]` en absoluto. Atarlo ahi
// habria exigido renombrar datos vivos o inventar una lista de excepciones.
for (const manifest of manifests) {
  const expectedModuleId = manifest.__file.replace(/\.json$/, '');
  if (manifest.moduleId !== expectedModuleId) {
    failures.push(
      `${manifest.__file}: moduleId declara "${manifest.moduleId}" pero debe ser `
      + `"${expectedModuleId}", el nombre del archivo del manifiesto`,
    );
  }
}
// Sin esta comprobacion, un manifiesto intruso con un `moduleId` duplicado
// queda invisible para `programManifest`/`laboratoryManifest` (que toman el
// primer match con `find`) pero sigue aportando escenarios a las demas
// reglas -- incluida la lista blanca de `capture: "element"` de mas abajo,
// que indexa por `moduleId/scenarioId`. Reproducido con `rogue.json`
// (moduleId: "laboratory") en la re-revision de F2a-2a.
const programManifest = manifests.find(({ moduleId }) => moduleId === 'programa-general');
const laboratoryManifest = manifests.find(({ moduleId }) => moduleId === 'laboratory');
for (const manifest of manifests) {
  for (const field of manifestSchema?.required || []) {
    if (!Object.hasOwn(manifest, field)) {
      failures.push(`${manifest.moduleId}: missing required field ${field}`);
    }
  }
  if (manifestSchema) {
    for (const failure of schemaPartialFailures(manifest, manifestSchema, manifestSchema, '', true)) {
      failures.push(`${manifest.moduleId}: ${failure}`);
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

// `capture: "element"` es una excepcion al contrato de dimensiones (mas
// abajo se explica por que no se le puede acotar el alto): un recorte a
// elemento con scroll es legitimamente mas alto que el viewport. Una
// excepcion sin lista blanca es una puerta que cualquiera puede cruzar --
// bastaria con etiquetar un PNG cualquiera como "element" para saltarse el
// chequeo de alto. Por eso los escenarios habilitados para usarla estan
// declarados aqui, como contrato explicito y revisado, no como una
// propiedad que un manifiesto se auto-asigna.
//
// La clave es compuesta, `moduleId/scenarioId`: la unicidad de ids solo se
// comprueba dentro de cada manifiesto (`scenarioIds` se re-crea por manifiesto),
// asi que indexar solo por `scenario.id` dejaba que cualquier otro modulo
// reclamara el id de un escenario autorizado y heredara la excepcion de alto.
//
// La lista blanca protegia el QUIEN pero no el QUE: los dos escenarios
// autorizados podian presentar cualquier PNG que no excediera el ancho del
// viewport. Sustituir el golden de states-feedback-dark-1180x820 por un PNG de
// 390x844 pasaba en verde. Por eso cada entrada declara ahora las dimensiones
// exactas del recorte, no solo su nombre: la excepcion deja de ser "el alto no
// se comprueba" y pasa a ser "el alto es este".
//
// Se eligieron dimensiones exactas y no un piso de alto porque un piso sigue
// admitiendo cualquier PNG alto (un 1102x4000 pasaria) y porque el numero no
// cuesta mantenerlo: cuando el recorte cambie legitimamente, el `sha256` del
// escenario cambia y hay que editar el manifiesto de todas formas; esta linea
// se actualiza en el mismo commit y entra en la misma revision. Un recorte que
// cambia de tamano sin que nadie lo mire es exactamente lo que esta lista debe
// impedir. El precio es que el gate falla si alguien regenera el golden sin
// tocar esta constante: es el fallo deseado, y el mensaje dice el numero nuevo.
const ELEMENT_CAPTURE_ALLOWLIST = new Map([
  ['laboratory/states-feedback-dark-1180x820', { width: 1102, height: 1649 }],
  ['laboratory/states-feedback-dark-1440x900', { width: 1362, height: 1577 }],
]);

// `golden` era una ruta libre desde la raiz del repositorio: un escenario podia
// apuntar a cualquier PNG del repo (incluido uno suelto en la raiz, creado a
// medida con el nombre y las dimensiones correctas) y el `sha256` solo lo ataba
// al archivo que el propio manifiesto habia elegido. Los 39 goldens declarados
// hoy viven, sin excepcion, bajo el directorio donde los deja la suite de
// navegador; ese es el unico origen legitimo y el que se exige aqui. Si en el
// futuro otra suite deja goldens en otro sitio, se anade ese prefijo aqui, con
// su justificacion, y no antes.
const GOLDEN_ROOTS = ['tests/browser/__screenshots__/'];

const goldenOwners = new Map();
const goldenContentOwners = new Map();
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
    if (!scenario.viewport?.width || !scenario.viewport?.height) {
      failures.push(`${manifest.moduleId}/${scenario.id}: scenario must declare a viewport`);
      continue;
    }
    const expectedDensity = scenario.viewport?.width >= 1200 ? 'compact' : 'touch';
    if (scenario.density !== expectedDensity) {
      failures.push(`${manifest.moduleId}/${scenario.id}: density must be ${expectedDensity}`);
    }
    // `..` se rechaza aparte: `startsWith` por si solo dejaria pasar
    // `tests/browser/__screenshots__/../../../cualquier-cosa.png`.
    if (scenario.golden
      && (scenario.golden.split('/').includes('..')
        || !GOLDEN_ROOTS.some((prefix) => scenario.golden.startsWith(prefix)))) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden ${scenario.golden} esta fuera de los `
        + `directorios de evidencia permitidos (${GOLDEN_ROOTS.join(', ')})`,
      );
      continue;
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
    const expectedSuffix =
      `-${scenario.theme}-${scenario.viewport.width}x${scenario.viewport.height}.png`;
    if (!scenario.golden.endsWith(expectedSuffix)) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden does not match theme/viewport `
        + `(espera un nombre terminado en ${expectedSuffix})`,
      );
    }
    // El golden se ata a sus pixeles reales, no solo a su nombre. Un PNG
    // guarda ancho y alto en el IHDR (bytes 16..23 del archivo), asi que se
    // leen de ahi sin dependencias. `capture` distingue los dos casos
    // legitimos: "viewport" (por defecto) es una captura de pantalla completa
    // y el PNG debe medir exactamente el viewport declarado, ancho y alto;
    // "element" es un recorte a un elemento (states-feedback mide 1102 de
    // ancho en un viewport de 1180) y solo exige no exceder el viewport.
    const header = readFileSync(goldenPath);
    if (header.length >= 24 && header.readUInt32BE(12) === 0x49484452) {
      const pngWidth = header.readUInt32BE(16);
      const pngHeight = header.readUInt32BE(20);
      const capture = scenario.capture || 'viewport';
      if (capture === 'element') {
        // Un recorte a elemento no esta acotado por el alto del viewport: es
        // una captura de un elemento que puede extenderse mas alla del pliegue
        // (los dos recortes reales de states-feedback miden 1649 y 1577 px de
        // alto sobre viewports de 820 y 900). Lo unico que un recorte legitimo
        // no puede ser es mas ancho que el viewport que declara. Justo porque
        // el alto queda sin acotar, "element" solo esta permitido para los
        // escenarios de ELEMENT_CAPTURE_ALLOWLIST: cualquier otro que declare
        // "element" falla el gate en vez de heredar la excepcion en silencio.
        const allowed = ELEMENT_CAPTURE_ALLOWLIST.get(`${manifest.moduleId}/${scenario.id}`);
        if (!allowed) {
          failures.push(
            `${manifest.moduleId}/${scenario.id}: capture "element" no esta en la lista blanca `
            + `(ELEMENT_CAPTURE_ALLOWLIST en scripts/design-system-contracts.mjs); es una excepcion `
            + `al contrato de alto y solo se habilita por revision explicita`,
          );
        } else if (pngWidth !== allowed.width || pngHeight !== allowed.height) {
          // La lista blanca no solo dice quien puede recortar a elemento: dice
          // cuanto mide ese recorte. Cambiar el PNG por otro (aunque quepa en el
          // viewport) rompe aqui.
          failures.push(
            `${manifest.moduleId}/${scenario.id}: golden mide ${pngWidth}x${pngHeight} px, `
            + `pero la lista blanca de capture "element" declara `
            + `${allowed.width}x${allowed.height} para este recorte`,
          );
        }
        if (pngWidth > scenario.viewport.width) {
          failures.push(
            `${manifest.moduleId}/${scenario.id}: golden mide ${pngWidth}x${pngHeight} px, `
            + `mas ancho que el viewport declarado ${scenario.viewport.width}x${scenario.viewport.height}`,
          );
        }
      } else if (pngWidth !== scenario.viewport.width || pngHeight !== scenario.viewport.height) {
        failures.push(
          `${manifest.moduleId}/${scenario.id}: golden mide ${pngWidth}x${pngHeight} px, `
          + `no coincide con el viewport declarado ${scenario.viewport.width}x${scenario.viewport.height}`,
        );
      }
    } else {
      failures.push(`${manifest.moduleId}/${scenario.id}: golden no es un PNG valido`);
    }
    if (goldenOwners.has(scenario.golden)) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden reused by another scenario `
        + `(${goldenOwners.get(scenario.golden)})`,
      );
    }
    goldenOwners.set(scenario.golden, `${manifest.moduleId}/${scenario.id}`);
    // Indexar solo por ruta deja pasar la copia con otro nombre: el contenido
    // es el mismo y el sha256 tambien, asi que el hash es la clave que de
    // verdad identifica un golden.
    if (goldenContentOwners.has(actual)) {
      failures.push(
        `${manifest.moduleId}/${scenario.id}: golden content reused by another scenario `
        + `(${goldenContentOwners.get(actual)})`,
      );
    }
    goldenContentOwners.set(actual, `${manifest.moduleId}/${scenario.id}`);
  }
}

const scenarioKey = (scenario) => `${scenario.theme}/${scenario.viewport.width}x${scenario.viewport.height}`;
for (const familyId of governedFamilies) {
  const familyScenarios = (laboratoryManifest?.scenarios || [])
    .filter((scenario) => scenario.family === familyId);
  const keys = new Set(familyScenarios.map(scenarioKey));
  for (const viewport of REQUIRED_VIEWPORTS) {
    if (!keys.has(`dark/${viewport}`)) {
      failures.push(`laboratory: missing scenario ${familyId}/dark/${viewport}`);
    }
  }
}
const pilotScenarioKeys = new Set((programManifest?.scenarios || []).map(scenarioKey));
for (const theme of ['dark']) {
  for (const viewport of REQUIRED_VIEWPORTS) {
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

// Cada esquema del design system con su documento. Hasta esta revision el
// validador parcial de arriba solo se aplicaba a los manifiestos de modulo: los
// otros siete esquemas se comprobaban en su FORMA (que declaren $schema, $id,
// required y additionalProperties: false) pero nunca se aplicaban a su propio
// documento. Es decir, `additionalProperties: false` estaba escrito en
// component-catalog.schema.json y una propiedad inventada en
// component-catalog.json pasaba en verde.
const SCHEMA_DOCUMENT_PAIRS = [
  ['docs/design-system/component-catalog.schema.json', 'docs/design-system/component-catalog.json'],
  ['docs/design-system/stable-api.schema.json', 'docs/design-system/stable-api-1.0.0.json'],
  ['docs/design-system/ui-groups-inventory.schema.json', 'docs/design-system/ui-groups-inventory.json'],
  ['docs/design-system/state-semantics.schema.json', 'docs/design-system/state-semantics.json'],
  ['docs/design-system/family-approvals.schema.json', 'docs/design-system/family-approvals.json'],
  ['docs/design-system/a11y-baseline.schema.json', 'docs/design-system/a11y-baseline.json'],
  ['docs/design-system/a11y-exceptions.schema.json', 'docs/design-system/a11y-exceptions.json'],
];

for (const [schemaFile, documentFile] of SCHEMA_DOCUMENT_PAIRS) {
  const schema = documents.get(schemaFile);
  const document = documents.get(documentFile);
  if (!schema || !document) continue;
  // `isRoot: false` a proposito, al reves que en los manifiestos: aqui no hay
  // otro chequeo que cubra el `required` de primer nivel, asi que lo aplica
  // este mismo recorrido.
  for (const failure of schemaPartialFailures(document, schema, schema, '', false)) {
    failures.push(`${documentFile}: ${failure}`);
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
