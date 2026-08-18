import { evidenceReceiptFailures } from './design-system-evidence-receipt.mjs';
import { ACTIVATED_VERSION_PATTERN, activationGitFailures } from './design-system-activation-git.mjs';

// Frente 1b (D-F1b-1, D-F1b-2, D-F1b-3, 2026-08-11): la lista baja de 15 a 8 gates.
// Retirados con motivo escrito (docs/design-system/gates-cierre-frente-1b.md):
// `git-preservation` (candado de un solo uso ya disparado), `accessibility-insights`,
// `consolidated-lab`, `consolidated-pilot` y `review` (herramienta inexistente o
// juicio humano declarado como comando). Fundidos en `full-app-flow`: `pg-roles`,
// `pg-persistence` y `data-restoration` (los tres declaraban el mismo comando).
// 2026-08-14: sube a 9 con `semanal-roles-phases`. La lista bajo de 15 a 8 por gates que no
// median nada; este sube a 9 por lo contrario — una suite que si mide y que hasta hoy no corria
// en ningun sitio.
export const closeoutGateIds = [
  'static', 'runtime', 'runtime-budgets', 'phpstan-scoped', 'phpstan-global',
  'global-table-safety', 'full-app-flow', 'semanal-roles-phases', 'atomic-commit',
];

const closeoutGateKinds = {
  static: 'automatic',
  runtime: 'automatic',
  'runtime-budgets': 'automatic',
  'phpstan-scoped': 'automatic',
  'phpstan-global': 'automatic',
  'global-table-safety': 'automatic',
  'full-app-flow': 'automatic',
  'semanal-roles-phases': 'automatic',
  'atomic-commit': 'automatic',
};
const closeoutFields = [
  'designSystemVersion', 'gates', 'generatedAt', 'schemaVersion',
];
const gateFields = ['blocking', 'evidence', 'id', 'kind', 'status', 'verifiedAt'];
// Depende del fixture aislado: su `fixtureSha256` ata el recibo al dato con el que se midio,
// y `semanal-roles-phases` no existiria sin la ampliacion de ese fixture.
const fixtureGateIds = new Set([
  'runtime', 'runtime-budgets', 'global-table-safety', 'full-app-flow', 'semanal-roles-phases',
]);
const validGateStatuses = new Set(['passed', 'pending', 'blocked']);
const maximumClockSkewMs = 5 * 60 * 1000;

function sameFields(value, fields) {
  return value && typeof value === 'object' && !Array.isArray(value)
    && JSON.stringify(Object.keys(value).sort()) === JSON.stringify([...fields].sort());
}


export function closeoutContractFailures(input) {
  const {
    root, closeout, stableApi, versionDocument, now = new Date(),
  } = input;
  const failures = [];
  let closeoutValid = sameFields(closeout, closeoutFields);
  if (!closeoutValid) failures.push('closeout: fields must match the closeout contract');
  const gates = Array.isArray(closeout?.gates) ? closeout.gates : [];
  const exactIds = JSON.stringify(gates.map(({ id }) => id)) === JSON.stringify(closeoutGateIds);
  if (!exactIds || gates.some(({ blocking }) => blocking !== true)) {
    failures.push('closeout: gates must be the exact ordered blocking set');
    closeoutValid = false;
  }
  const generatedAt = /^\d{4}-\d{2}-\d{2}$/.test(String(closeout?.generatedAt ?? ''))
    ? Date.parse(`${closeout.generatedAt}T00:00:00Z`) : Number.NaN;
  if (!Number.isFinite(generatedAt)) {
    failures.push('closeout: generatedAt must be a valid date');
    closeoutValid = false;
  }
  const nowMs = now instanceof Date ? now.getTime() : Number.NaN;
  // `allPassed` ya no decide la activacion (ver abajo), pero se conserva porque
  // sigue siendo el resumen de si TODOS los gates pasan hoy, y las validaciones
  // por gate lo alimentan. Quitarlo obligaria a reescribir el bucle entero.
  let allPassed = exactIds;
  for (const gate of gates) {
    const initialFailureCount = failures.length;
    if (!sameFields(gate, gateFields)) failures.push(`${gate.id}: fields must match the closeout contract`);
    if (closeoutGateKinds[gate.id] !== gate.kind) failures.push(`${gate.id}: invalid kind ${gate.kind}`);
    if (!validGateStatuses.has(gate.status)) failures.push(`${gate.id}: invalid status ${gate.status}`);
    if (!Array.isArray(gate.evidence)) failures.push(`${gate.id}: evidence must be an array`);
    if (gate.status === 'passed') {
      const verifiedAt = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/.test(String(gate.verifiedAt ?? ''))
        ? Date.parse(gate.verifiedAt) : Number.NaN;
      if (!Number.isFinite(verifiedAt) || verifiedAt < generatedAt) {
        failures.push(`${gate.id}: passed requires fresh verifiedAt and structured evidence`);
      } else if (!Number.isFinite(nowMs) || verifiedAt > nowMs + maximumClockSkewMs) {
        failures.push(`${gate.id}: verifiedAt is too far in the future`);
      }
      const surfaces = [null];
      if (!Array.isArray(gate.evidence) || gate.evidence.length !== surfaces.length) {
        failures.push(`${gate.id}: passed requires fresh verifiedAt and structured evidence`);
      } else {
        gate.evidence.forEach((receipt, index) => failures.push(...evidenceReceiptFailures(
          root,
          {
            id: gate.id,
            surface: surfaces[index],
            fixtureRequired: fixtureGateIds.has(gate.id),
          },
          receipt,
        )));
      }
    } else {
      allPassed = false;
      if (gate.verifiedAt !== null) failures.push(`${gate.id}: unresolved gate must have null verifiedAt`);
    }
    if (failures.length > initialFailureCount) closeoutValid = false;
  }
  allPassed = allPassed && closeoutValid && gates.every(({ status }) => status === 'passed');
  if (!['pending-gates', 'guaranteed'].includes(stableApi?.releaseStatus)) {
    failures.push(`stable API: invalid releaseStatus ${stableApi?.releaseStatus}`);
  }
  const stableApiActivated = stableApi?.releaseStatus === 'guaranteed';
  // La activacion del design system fue un hito UNICO, cumplido en 1.0.0 (D2 del
  // spec 2026-08-04). A partir de ahi el sistema no se "reactiva" en cada version:
  // cualquier SemVer con major >= 1 y status stable es un sistema activado.
  const activatedVersion = ACTIVATED_VERSION_PATTERN.test(versionDocument?.version ?? '');
  const versionActivated = activatedVersion
    && versionDocument?.status === 'stable';
  const versionPartiallyActivated = activatedVersion
    || versionDocument?.status === 'stable';
  const passStateRequested = gates.some(({ status }) => status === 'passed')
    || stableApiActivated || versionPartiallyActivated;
  if (passStateRequested) failures.push(...activationGitFailures(root, closeoutGateIds));
  // `allPassed` SALIO de esta identidad el 2026-08-11, y el motivo importa mas
  // que el cambio. Antes se exigia que `allPassed`, `stableApiActivated` y
  // `versionActivated` valieran los tres lo mismo. Con la version estable en
  // 1.x los dos ultimos son `true` para siempre, asi que **un solo gate
  // declarado `blocked` —es decir, un solo gate honesto— rompia la igualdad y
  // tumbaba la activacion entera**.
  //
  // Eso convertia decir la verdad en imposible: con ocho gates de los que tres
  // no pasan, la unica forma de tener la suite verde era afirmar que los ocho
  // pasaban. **Ese acoplamiento fue el incentivo que produjo quince recibos
  // `passed` sin haberse ejecutado nunca** — no fue solo descuido: era la unica
  // salida que este contrato dejaba abierta.
  //
  // Y contradecia al comentario de arriba, que declara la activacion un hito
  // UNICO cumplido en 1.0.0: el codigo comprobaba en cada corrida algo que el
  // mismo define como inmutable. Esto no relaja el contrato; hace que haga lo
  // que dice que hace.
  //
  // Lo que NO cambia, y es lo que sostiene el arreglo: un gate en `blocked`
  // sigue poniendo rojo **su propio carril**, ruidosamente, por las
  // validaciones de arriba. Si alguna vez dejan de fallar, este contrato habra
  // pasado a mentir de otra forma. No lo vuelvas a acoplar aqui.
  if (versionPartiallyActivated !== versionActivated
    || new Set([stableApiActivated, versionActivated]).size !== 1) {
    failures.push('activation: version and stable API must activate together');
  }
  return failures;
}
