import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (path) => JSON.parse(await readFile(
  new URL(`../../${path}`, import.meta.url),
  'utf8',
));
const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

// Frente 1b (D-F1b-1, D-F1b-2, D-F1b-3, 2026-08-11): bajó de 15 a 8 gates. Motivo
// escrito de cada retiro/fusión en docs/design-system/gates-cierre-frente-1b.md.
// 2026-08-14: sube a 9 con `semanal-roles-phases`, la suite de roles y fases de la semanal.
// Entra despues de `full-app-flow` y antes de `atomic-commit`, que es el orden en que corren.
const REQUIRED_GATES = [
  'static', 'runtime', 'runtime-budgets', 'phpstan-scoped', 'phpstan-global',
  'global-table-safety', 'full-app-flow', 'semanal-roles-phases', 'atomic-commit',
];

function assertExactGateIds(gates) {
  const ids = gates.map(({ id }) => id);
  assert.equal(gates.length, REQUIRED_GATES.length);
  assert.equal(new Set(ids).size, REQUIRED_GATES.length, 'gate ids must be unique');
  assert.deepEqual(ids, REQUIRED_GATES);
}

test('closeout evidence declares the exact ordered set of blocking gates', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  assert.equal(evidence.schemaVersion, 1);
  assert.match(evidence.designSystemVersion, /^\d+\.\d+\.\d+$/);
  assertExactGateIds(evidence.gates);
  for (const gate of evidence.gates) {
    assert.deepEqual(
      Object.keys(gate).sort(),
      ['blocking', 'evidence', 'id', 'kind', 'status', 'verifiedAt'],
      gate.id,
    );
    assert.match(gate.kind, /^(automatic|manual|human)$/);
    assert.match(gate.status, /^(passed|pending|blocked)$/);
    assert.equal(gate.blocking, true, gate.id);
    assert.ok(Array.isArray(gate.evidence), gate.id);
  }
});

test('malformed, reordered, duplicate and missing gate arrays fail closed', () => {
  const gates = REQUIRED_GATES.map((id) => ({ id }));
  assert.throws(() => assertExactGateIds(gates.slice(0, -1)));
  assert.throws(() => assertExactGateIds([...gates, { id: 'unexpected' }]));
  assert.throws(() => assertExactGateIds([...gates.slice(0, -1), { id: 'static' }]));
  assert.throws(() => assertExactGateIds([...gates].reverse()));
});

test('passed gates require fresh timestamps and non-historical evidence', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const generatedAt = Date.parse(`${evidence.generatedAt}T00:00:00Z`);

  for (const gate of evidence.gates) {
    if (gate.status === 'passed') {
      assert.match(gate.verifiedAt, /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
      assert.ok(Date.parse(gate.verifiedAt) >= generatedAt, gate.id);
      assert.ok(gate.evidence.length > 0, gate.id);
      assert.doesNotMatch(gate.evidence.join(' '), /superseded|historical/i);
    } else {
      assert.equal(gate.verifiedAt, null, gate.id);
    }
  }
  assert.doesNotMatch(JSON.stringify(evidence.gates), /\bPASS\b/);
});

test('full-app-flow declara un solo recibo, y su estado coincide con lo que ese recibo midio', async () => {
  // Este test exigia `status === 'passed'` a secas, y eso es fijar la mentira
  // por escrito: obligaba a declarar aprobado un gate que hoy no se puede
  // ejecutar aqui —le falta una base de datos aislada— y contribuia al mismo
  // incentivo que D-F1b-5 describe. Se sustituye por una comprobacion mas
  // fuerte: da igual si pasa o no, lo que no puede es que el indice y su
  // recibo digan cosas distintas.
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const gate = evidence.gates.find(({ id }) => id === 'full-app-flow');
  assert.ok(gate, 'full-app-flow no esta en el cierre');
  assert.equal(gate.evidence.length, 1);
  assert.equal(gate.evidence[0].commandId, 'ds.full-app-flow.v1');

  const recibo = await readJson(gate.evidence[0].artifact);
  assert.equal(recibo.gateId, 'full-app-flow');
  const esperado = recibo.exitCode === 0 ? 'passed' : 'blocked';
  assert.equal(
    gate.status,
    esperado,
    `el indice dice '${gate.status}' y su recibo midio exitCode ${recibo.exitCode}`,
  );
});

test('closeout surfaces defer transient numeric success claims to the final closer', async () => {
  const [closeout, validationLog] = await Promise.all([
    read('docs/design-system/closeout-evidence.json'),
    read('goals/design-system-nucleo-gobernanza/validation-log.md'),
  ]);
  const activeEvidence = `${closeout}\n${validationLog}`;
  assert.doesNotMatch(activeEvidence, /\b\d+\s*(?:\/|of)\s*\d+\b/i);
  assert.doesNotMatch(activeEvidence, /\bPASS\b/);
  assert.doesNotMatch(
    activeEvidence,
    /(?:completed|completaron|reported|reportó|exited|terminó|terminaron)[^.\n|]{0,48}(?:without failure|sin fallos?|zero|cero)/i,
  );
});

test('keyboard and reflow remain evidence and are not closeout gate objects', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const ids = evidence.gates.map(({ id }) => id);
  assert.equal(ids.includes('keyboard'), false);
  assert.equal(ids.includes('voiceover'), false);
  assert.equal(ids.includes('zoom-reflow'), false);
  assert.equal(ids.includes('plannotator'), false);
});

test('un gate sin resolver es honesto: sin fecha, y con un recibo que lo sostiene', async () => {
  // Este test exigia que con la version estable NO hubiera ningun gate sin
  // resolver. Es el mismo acoplamiento que D-F1b-5 retiro del contrato
  // (2026-08-11): la activacion fue un hito unico de la 1.0.0 y no depende del
  // estado de los gates de hoy, asi que atarla aqui obligaba a declarar
  // `passed` lo que no lo esta.
  //
  // Lo que si hay que exigirle a un gate sin resolver, y esto comprueba MAS que
  // la version anterior: que no conserve la fecha de una verificacion que ya no
  // sostiene, y que su recibo diga lo mismo que el indice. Un gate rojo es
  // legitimo; uno que miente sobre por que esta rojo, no.
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const unresolved = evidence.gates.filter(({ status }) => status !== 'passed');
  for (const gate of unresolved) {
    assert.equal(gate.verifiedAt, null, `${gate.id}: sin resolver pero conserva verifiedAt`);
    const recibo = await readJson(gate.evidence[0].artifact);
    assert.notEqual(
      recibo.exitCode,
      0,
      `${gate.id}: el indice lo da por no resuelto y su recibo midio exitCode 0`,
    );
  }
});
