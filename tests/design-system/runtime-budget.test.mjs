import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  compareRuntimeBudget,
  validateRuntimeBudgetArtifact,
} from '../../scripts/design-system-runtime-budget.mjs';
import {
  APPROVED_BASELINE,
  currentMeasurement,
  currentSamples,
  withRuntimeEnvironment,
} from './runtime-budget-fixtures.mjs';

const compare = (baseline, measurement) => withRuntimeEnvironment(
  () => compareRuntimeBudget(baseline, measurement),
);

const CURRENT_MEASUREMENT = currentMeasurement();

test('accepts a measured report inside every explicit 0.3.3 tolerance', () => {
  assert.equal(validateRuntimeBudgetArtifact(APPROVED_BASELINE), true);
  assert.equal(validateRuntimeBudgetArtifact(CURRENT_MEASUREMENT), true);

  const result = compare(APPROVED_BASELINE, CURRENT_MEASUREMENT);
  assert.equal(result.pass, true, JSON.stringify(result.violations, null, 2));
  assert.deepEqual(result.violations, []);
});

test('accepts a measured retrospective 0.3.3 artifact without treating it as approved', () => {
  const retrospective = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-measurements/0.3.3-retrospective.json', import.meta.url),
    'utf8',
  ));

  assert.equal(validateRuntimeBudgetArtifact(retrospective), true);
  assert.throws(
    () => compare(retrospective, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('rejects a pending or unapproved baseline instead of inventing values', () => {
  const pending = {
    ...APPROVED_BASELINE,
    status: 'missing-approved-measurement',
    measurementKind: 'retrospective',
    sourceRef: null,
    sourceTreeHash: null,
    recordedAt: null,
    approval: { status: 'missing', approvedBy: null, approvalRef: null },
    metrics: null,
    tolerances: null,
    reason: 'No approved runtime measurement was recorded for 0.3.3.',
    recovery: { ...APPROVED_BASELINE.recovery },
  };

  assert.equal(validateRuntimeBudgetArtifact(pending), true);
  assert.throws(
    () => compare(pending, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('canonical 0.3.3 artifact is approved only with the user-approved caps', async () => {
  const artifact = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
    'utf8',
  ));

  assert.equal(validateRuntimeBudgetArtifact(artifact), true);
  assert.equal(artifact.status, 'approved');
  assert.equal(artifact.approval.status, 'approved');
  assert.deepEqual(artifact.tolerances, {
    cssGzipBytes: 2048,
    jsGzipBytes: 4096,
    addedAdapterAssets: 0,
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 110,
    handsontableInteractionMs: 45,
  });
  assert.equal(artifact.metrics.themeFlashCount, 1);
  assert.deepEqual(artifact.metrics.laboratoryAssets, []);
});

test('fails closed for budget growth, undeclared adapters, duplicates, flash and lab assets', () => {
  const regression = currentMeasurement({
    metrics: {
      cssGzipBytes: APPROVED_BASELINE.metrics.cssGzipBytes + APPROVED_BASELINE.tolerances.cssGzipBytes + 1,
      jsGzipBytes: APPROVED_BASELINE.metrics.jsGzipBytes + APPROVED_BASELINE.tolerances.jsGzipBytes + 1,
      adapterAssets: [
        ...APPROVED_BASELINE.metrics.adapterAssets,
        '/public/css/design-system/adapters/select2.css',
      ],
      duplicateRequestCount: APPROVED_BASELINE.metrics.duplicateRequestCount + 1,
      themeFlashCount: 1,
      initializationMs: APPROVED_BASELINE.metrics.initializationMs
        + APPROVED_BASELINE.tolerances.initializationMs + 1,
      handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
        + APPROVED_BASELINE.tolerances.handsontableInteractionMs + 1,
      laboratoryAssets: ['/public/css/design-system/lab.css'],
    },
  });

  const result = compare(APPROVED_BASELINE, regression);
  assert.equal(result.pass, false);
  assert.deepEqual(
    result.violations.map(({ metric }) => metric).sort(),
    [
      'adapterAssets',
      'cssGzipBytes',
      'duplicateRequestCount',
      'handsontableInteractionMs',
      'initializationMs',
      'jsGzipBytes',
      'laboratoryAssets',
      'themeFlashCount',
    ].sort(),
  );
});

test('allows a tiny timing overshoot on Programa General measurements', () => {
  const noisyMeasurement = currentMeasurement({
    metrics: {
      initializationMs: APPROVED_BASELINE.metrics.initializationMs
        + APPROVED_BASELINE.tolerances.initializationMs + 0.3,
      handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
        + APPROVED_BASELINE.tolerances.handsontableInteractionMs + 0.3,
    },
  });

  const result = compare(APPROVED_BASELINE, noisyMeasurement);
  assert.equal(result.pass, true, JSON.stringify(result.violations, null, 2));
  assert.deepEqual(result.violations, []);
});

test('requires zero theme flash even when the historical measurement contained one', () => {
  const currentWithFlash = currentMeasurement({ metrics: { themeFlashCount: 1 } });

  const result = compare(APPROVED_BASELINE, currentWithFlash);
  assert.equal(result.pass, false);
  assert.equal(
    result.violations.some(({ metric, maximum }) => metric === 'themeFlashCount' && maximum === 0),
    true,
  );
});

test('rejects missing, negative, non-finite or context-mismatched measurements', () => {
  assert.throws(
    () => validateRuntimeBudgetArtifact({ ...CURRENT_MEASUREMENT, route: '/pdc' }),
    /route must be \/programa-general/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({
      ...CURRENT_MEASUREMENT,
      metrics: { ...CURRENT_MEASUREMENT.metrics, initializationMs: Number.NaN },
    }),
    /initializationMs must be a finite non-negative number/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({
      ...CURRENT_MEASUREMENT,
      metrics: { ...CURRENT_MEASUREMENT.metrics, laboratoryAssets: null },
    }),
    /laboratoryAssets must be an array/,
  );
});

test('compares only equivalent route, viewport, theme and fixture measurements', () => {
  assert.throws(
    () => compare(APPROVED_BASELINE, currentMeasurement({
      context: { viewport: '1180x820' },
    })),
    /runtime budget context mismatch: viewport/,
  );
});

test('el contrato de presupuesto acepta el viewport movil', async () => {
  const source = await readFile(
    new URL('../../scripts/design-system-runtime-budget.mjs', import.meta.url), 'utf8',
  );
  assert.match(source, /SUPPORTED_VIEWPORTS = \[[^\]]*'390x844'/);
});

test('el esquema de presupuesto admite el viewport movil', async () => {
  const schema = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url), 'utf8',
  ));
  const viewport = schema["$defs"]["artifactBase"]["properties"]["viewport"];
  assert.ok(viewport, 'no se encontro el enum de viewport');
  assert.ok(viewport.enum.includes('390x844'));
});

// runtime-budget.schema.json no esta en SCHEMA_DOCUMENT_PAIRS (design-system-contracts.mjs):
// sus documentos se validan por scripts/design-system-runtime-budget*.mjs a mano, no por el
// validador de esquema generico. Esa validacion manual es el contrato ejecutable real; el
// esquema es su documentacion.
//
// La primera version de esta prueba comparaba texto (el enum del esquema contra un regex sobre
// el codigo fuente del script). Eso es fragil por dos vias: se rompe con un reformateo inocuo del
// array y no se rompe cuando deberia -- un comentario que mencionara "original" la pondria roja
// sin que nada hubiera divergido. Y solo cubria una regla de las muchas que declara el esquema.
//
// Esta version prueba comportamiento: toma un artefacto valido (measurement o baseline), le aplica
// una mutacion que rompe una regla concreta del esquema, y comprueba que el validador manual la
// rechaza. Si alguien agrega una regla al esquema sin implementarla en el script, la mutacion
// equivalente pasaria en verde con el validador real y esta prueba lo detectaria -- para las
// reglas de esta lista.
//
// Cobertura: 55 mutaciones mecanicas (44 sobre measurement, 1 sobre themeObservation.phase vía
// un artefacto "sample", 10 sobre baseline), una por combinacion (regla del esquema x lugar donde
// aparece) que se puede construir con una edicion de un solo campo. No es "55 de 55 reglas del
// esquema": el censo bruto de palabras clave (type/pattern/format/minLength/minimum/maximum/
// minItems/maxItems/uniqueItems/enum/const/additionalProperties/required) en el JSON da 358
// ocurrencias, pero la enorme mayoria son la misma regla repetida en $defs hermanos (el patron
// sha256 aparece mas de 15 veces; additionalProperties:false en casi todos los objetos). Estas 55
// mutaciones cubren cada *tipo* de regla que se puede violar sin coordinar dos ramas del oneOf a
// la vez. Quedan fuera, y por que:
//   - El discriminador del oneOf en si (kind/measurementKind/status decidiendo cual de las 4
//     ramas aplica): no es una regla violable con una mutacion, es la forma de las 4 ramas; cada
//     rama ya se ejercita completa en los fixtures de accepts/rejects de este archivo y de
//     runtime-budget-v4-contract.test.mjs.
//   - prefixItems con const por indice en rawReceipts (index debe ser 1,2,3 en orden): cubierto
//     parcialmente (mutacion de un indice fuera de rango), pero no las 3 posiciones por separado.
//   - "unevaluatedProperties" cruzando las dos ramas de un allOf a la vez (una clave valida en la
//     rama base pero prohibida por la rama especifica): el validador ya lo hace por reconstruccion
//     manual de claves permitidas (`requireOnlyKeys` con la union exacta), pero no hay aqui una
//     mutacion que ejercite ese cruce especifico.
//   - additionalProperties:true en retrospectiveMeasurement.provenance (el esquema es permisivo
//     ahi a proposito): no hay nada que mutar, es la ausencia de una regla, no una regla.
//   - $schema opcional con type:string en baseline/measurement: gap conocido y documentado en el
//     informe de cierre (docs/design-system, ver goal.md); no se prueba aqui porque no esta cerrado.
const CURRENT_SAMPLE = currentSamples()[0];

function mutate(source, path, value) {
  const clone = structuredClone(source);
  let cursor = clone;
  for (let index = 0; index < path.length - 1; index += 1) cursor = cursor[path[index]];
  cursor[path[path.length - 1]] = value;
  return clone;
}

function deleteField(source, path) {
  const clone = structuredClone(source);
  let cursor = clone;
  for (let index = 0; index < path.length - 1; index += 1) cursor = cursor[path[index]];
  delete cursor[path[path.length - 1]];
  return clone;
}

const SHA256_VALID = 'a'.repeat(64);

const MUTATIONS = [
  // artifactBase: type/const/enum/pattern sobre el propio measurement.
  ['schemaVersion debe ser 1 (const)', (a) => mutate(a, ['schemaVersion'], 2)],
  ['kind debe ser un valor del enum', (a) => mutate(a, ['kind'], 'bogus')],
  ['measurementKind debe ser current o retrospective (enum)', (a) => mutate(a, ['measurementKind'], 'original')],
  ['status debe ser measured para una medicion (const)', (a) => mutate(a, ['status'], 'sampled')],
  ['designSystemVersion debe ser SemVer (pattern)', (a) => mutate(a, ['designSystemVersion'], 'not-a-version')],
  ['route debe ser /programa-general (const)', (a) => mutate(a, ['route'], '/otra-ruta')],
  ['viewport debe ser uno de los tres soportados (enum)', (a) => mutate(a, ['viewport'], '800x600')],
  ['theme debe ser dark (enum)', (a) => mutate(a, ['theme'], 'linen')],
  ['density debe ser compact o touch (enum)', (a) => mutate(a, ['density'], 'roomy')],
  ['fixture debe ser sanitized-pilot-v1 (const)', (a) => mutate(a, ['fixture'], 'otro-fixture')],
  ['ciRunId debe cumplir el patron run-... (pattern)', (a) => mutate(a, ['ciRunId'], 'not-a-run-id')],
  ['sourceRef debe ser un SHA-1 (pattern)', (a) => mutate(a, ['sourceRef'], 'not-a-sha1')],
  ['sourceTreeHash debe ser un SHA-256 (pattern)', (a) => mutate(a, ['sourceTreeHash'], 'not-a-sha256')],
  ['fixtureSha256 debe ser un SHA-256 (pattern)', (a) => mutate(a, ['fixtureSha256'], 'not-a-sha256')],
  ['recordedAt debe ser RFC3339 UTC (format: date-time)', (a) => mutate(a, ['recordedAt'], '2026-13-40T99:99:99Z')],

  // metrics: required, minimum, patrones de array (applicationPaths), additionalProperties.
  ['metrics.cssGzipBytes es obligatorio (required)', (a) => deleteField(a, ['metrics', 'cssGzipBytes'])],
  ['metrics.cssGzipBytes debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'cssGzipBytes'], -1)],
  ['metrics.jsGzipBytes debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'jsGzipBytes'], -1)],
  ['metrics.duplicateRequestCount debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'duplicateRequestCount'], -1)],
  ['metrics.themeFlashCount debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'themeFlashCount'], -1)],
  ['metrics.initializationMs debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'initializationMs'], -1)],
  ['metrics.handsontableInteractionMs debe ser >= 0 (minimum)', (a) => mutate(a, ['metrics', 'handsontableInteractionMs'], -1)],
  ['metrics.adapterAssets entradas deben ser rutas absolutas (pattern)', (a) => mutate(a, ['metrics', 'adapterAssets'], ['no-empieza-con-slash'])],
  ['metrics.adapterAssets no admite duplicados (uniqueItems)', (a) => mutate(a, ['metrics', 'adapterAssets'], ['/x.css', '/x.css'])],
  ['metrics.laboratoryAssets entradas deben ser rutas absolutas (pattern)', (a) => mutate(a, ['metrics', 'laboratoryAssets'], ['sin-slash'])],
  ['metrics no admite propiedades extra (additionalProperties: false)', (a) => mutate(a, ['metrics', 'unexpectedMetric'], 1)],

  // provenance.assets: required, pattern, enum, minimum, additionalProperties.
  ['provenance.assets no puede estar vacio (minItems: 1)', (a) => mutate(a, ['provenance', 'assets'], [])],
  ['asset.path es obligatorio (required)', (a) => deleteField(a, ['provenance', 'assets', 0, 'path'])],
  ['asset.path no admite query strings (pattern)', (a) => mutate(a, ['provenance', 'assets', 0, 'path'], '/a.css?x=1')],
  ['asset.path no admite fragmentos (pattern)', (a) => mutate(a, ['provenance', 'assets', 0, 'path'], '/a.css#f')],
  ['asset.type debe ser css o js (enum)', (a) => mutate(a, ['provenance', 'assets', 0, 'type'], 'png')],
  ['asset.rawBytes debe ser >= 0 (minimum)', (a) => mutate(a, ['provenance', 'assets', 0, 'rawBytes'], -1)],
  ['asset.gzipBytes debe ser >= 0 (minimum)', (a) => mutate(a, ['provenance', 'assets', 0, 'gzipBytes'], -1)],
  ['asset.sha256 debe ser un SHA-256 (pattern)', (a) => mutate(a, ['provenance', 'assets', 0, 'sha256'], 'not-a-sha256')],
  ['asset no admite propiedades extra (additionalProperties: false)', (a) => mutate(a, ['provenance', 'assets', 0, 'unexpected'], 1)],
  ['provenance.assetInventorySha256 debe ser un SHA-256 (pattern)', (a) => mutate(a, ['provenance', 'assetInventorySha256'], 'not-a-sha256')],

  // sampling (currentSampling): const, minItems/maxItems, pattern, additionalProperties.
  ['sampling.rawSamplesPreserved debe ser true (const)', (a) => mutate(a, ['provenance', 'sampling', 'rawSamplesPreserved'], false)],
  ['sampling.sampleCount debe ser 3 (const)', (a) => mutate(a, ['provenance', 'sampling', 'sampleCount'], 2)],
  ['sampling.aggregation debe ser median-of-three (const)', (a) => mutate(a, ['provenance', 'sampling', 'aggregation'], 'mean')],
  ['sampling.aggregateReceiptSha256 debe ser un SHA-256 (pattern)', (a) => mutate(a, ['provenance', 'sampling', 'aggregateReceiptSha256'], 'not-a-sha256')],
  ['sampling no admite propiedades extra (additionalProperties: false)', (a) => mutate(a, ['provenance', 'sampling', 'unexpected'], 1)],
  ['rawReceipts debe tener exactamente 3 elementos (maxItems)', (a) => mutate(a, ['provenance', 'sampling', 'rawReceipts'], [
    ...structuredClone(a).provenance.sampling.rawReceipts, { index: 1, sha256: SHA256_VALID },
  ])],
  ['rawReceipts[0].sha256 debe ser un SHA-256 (pattern)', (a) => mutate(a, ['provenance', 'sampling', 'rawReceipts', 0, 'sha256'], 'not-a-sha256')],
  ['rawReceipts[0].index debe ser 1 en esa posicion (prefixItems const)', (a) => mutate(a, ['provenance', 'sampling', 'rawReceipts', 0, 'index'], 3)],
];

function assertMutationRejected(description, mutated) {
  try {
    validateRuntimeBudgetArtifact(mutated);
  } catch {
    return;
  }
  assert.fail(`el validador manual acepto una mutacion que el esquema prohibe: ${description}`);
}

test('cada regla mecanica del esquema de runtime budget tiene su contraparte en el validador manual', () => {
  for (const [description, applyMutation] of MUTATIONS) {
    assertMutationRejected(description, applyMutation(CURRENT_MEASUREMENT));
  }
});

// La mutacion de themeObservation.phase (minLength: 1) necesita un artefacto de tipo "sample" con
// provenance.themeProbe, que no forma parte del measurement agregado; se prueba por separado con
// una muestra cruda valida.
test('themeProbe.observations[].phase no admite cadena vacia (minLength: 1)', () => {
  const withEmptyPhase = mutate(CURRENT_SAMPLE, ['provenance', 'themeProbe'], {
    firstPaintTheme: 'dark',
    observations: [{ phase: '', theme: 'dark', at: 0 }],
  });
  assert.throws(() => validateRuntimeBudgetArtifact(withEmptyPhase));
});

const BASELINE_MUTATIONS = [
  ['baseline.viewport debe ser 1440x900 (const)', (b) => mutate(b, ['viewport'], '1180x820')],
  ['baseline.designSystemVersion debe ser 0.3.3 (const)', (b) => mutate(b, ['designSystemVersion'], '1.0.0')],
  ['baseline.measurementKind debe ser retrospective (const)', (b) => mutate(b, ['measurementKind'], 'current')],
  ['baseline.approval.approvedBy es obligatorio si esta aprobado (required)', (b) => deleteField(b, ['approval', 'approvedBy'])],
  ['baseline.approval.status debe ser approved o missing (enum)', (b) => mutate(b, ['approval', 'status'], 'pending')],
  ['baseline.recovery.manifestPath es constante (const)', (b) => mutate(b, ['recovery', 'manifestPath'], 'otra/ruta.json')],
  ['baseline.recovery.manifestSha256 debe ser un SHA-256 (pattern)', (b) => mutate(b, ['recovery', 'manifestSha256'], 'not-a-sha256')],
  ['baseline.tolerances.cssGzipBytes debe ser >= 0 (minimum)', (b) => mutate(b, ['tolerances', 'cssGzipBytes'], -1)],
  ['baseline.tolerances.addedAdapterAssets es obligatorio (required)', (b) => deleteField(b, ['tolerances', 'addedAdapterAssets'])],
  ['baseline no admite propiedades extra fuera de las declaradas (unevaluatedProperties: false)', (b) => mutate(b, ['unexpectedTopLevel'], 1)],
];

test('cada regla mecanica del esquema para baseline tiene su contraparte en el validador manual', () => {
  for (const [description, applyMutation] of BASELINE_MUTATIONS) {
    assertMutationRejected(description, applyMutation(APPROVED_BASELINE));
  }
});

test('el numero de mutaciones mecanicas cubiertas queda documentado, no inflado', () => {
  assert.equal(MUTATIONS.length + BASELINE_MUTATIONS.length + 1, 55);
});
