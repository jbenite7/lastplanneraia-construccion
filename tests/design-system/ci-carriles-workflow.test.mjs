import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { GATE_KEYS } from '../../scripts/ci-carriles.mjs';
import { parseJobSteps } from './workflow-contract-parser.mjs';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

// Devuelve las líneas de un job (de `  <id>:` hasta el siguiente job).
const jobLines = (source, jobId) => {
  const lines = source.split('\n');
  const start = lines.findIndex((line) => line === `  ${jobId}:`);
  assert.notEqual(start, -1, `falta el job ${jobId}`);
  const offset = lines.slice(start + 1).findIndex((line) => /^  [a-zA-Z0-9_-]+:$/.test(line));
  return lines.slice(start, offset === -1 ? lines.length : start + 1 + offset);
};

// Quita los comentarios de línea completa para no confundirlos con configuración.
const sinComentarios = (lines) => lines.filter((line) => !/^\s*#/.test(line));

test('toda salida que se lee de `cambios` existe y el job declara una por clave', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const validas = new Set([...GATE_KEYS, 'temas', 'carriles']);
  const usadas = [...workflow.matchAll(/needs\.cambios\.outputs\.([A-Za-z0-9_]+)/g)].map((m) => m[1]);
  assert.ok(usadas.length > 0, 'ningún job lee las salidas de cambios');
  for (const clave of usadas) {
    assert.ok(validas.has(clave), `needs.cambios.outputs.${clave} no la emite scripts/ci-carriles.mjs`);
  }

  const cuerpo = sinComentarios(jobLines(workflow, 'cambios')).join('\n');
  const bloque = cuerpo.match(/^    outputs:\n((?:      .*\n?)+)/m);
  assert.ok(bloque, 'cambios no declara outputs');
  for (const clave of [...GATE_KEYS, 'temas', 'carriles']) {
    assert.match(
      bloque[1],
      new RegExp(`^      ${clave}: \\$\\{\\{ steps\\.carriles\\.outputs\\.${clave} \\}\\}$`, 'm'),
      `cambios no mapea la salida ${clave}`,
    );
  }
});

test('la concurrencia cancela solo en pull_request y usa el SHA en push', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  assert.match(
    workflow,
    /^concurrency:\n {2}group: design-system-\$\{\{ github\.event_name == 'pull_request' && github\.ref \|\| github\.sha \}\}\n {2}cancel-in-progress: \$\{\{ github\.event_name == 'pull_request' \}\}$/m,
  );
});

test('cambios calcula el diff con historial completo y datos del evento por env', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'cambios');
  const checkout = steps.find(({ uses }) => uses?.startsWith('actions/checkout@'));
  assert.match(checkout?.uses, /^actions\/checkout@[0-9a-f]{40} # v/);
  assert.equal(checkout?.with?.['fetch-depth'], '0');
  assert.equal(checkout?.with?.['persist-credentials'], false);

  const setupNode = steps.find(({ uses }) => uses?.startsWith('actions/setup-node@'));
  assert.match(setupNode?.uses, /^actions\/setup-node@[0-9a-f]{40} # v/);

  const carriles = steps.find(({ id }) => id === 'carriles');
  assert.ok(carriles, 'falta el paso con id carriles');
  assert.equal(carriles.env?.EVENT_NAME, '${{ github.event_name }}');
  assert.equal(carriles.env?.BASE_SHA, '${{ github.event.pull_request.base.sha }}');
  assert.equal(carriles.env?.BEFORE_SHA, '${{ github.event.before }}');
  assert.equal(carriles.env?.HEAD_SHA, '${{ github.sha }}');
  assert.ok(!carriles.run.includes('${{'), 'el run no debe interpolar datos del evento');
  assert.match(carriles.run, /node scripts\/ci-carriles\.mjs >> "\$GITHUB_OUTPUT"/);

  const cuerpo = sinComentarios(jobLines(workflow, 'cambios')).join('\n');
  assert.match(cuerpo, /^    runs-on: ubuntu-latest$/m);
  assert.match(cuerpo, /^    timeout-minutes: 5$/m);
  assert.match(cuerpo, /^    permissions:\n {6}contents: read$/m);
});

test('los jobs existentes dependen de cambios y respetan su bandera', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const estatico = sinComentarios(jobLines(workflow, 'design-system-static')).join('\n');
  assert.match(estatico, /^    needs: cambios$/m);
  assert.match(estatico, /^    if: needs\.cambios\.outputs\.static == 'true'$/m);

  const runtime = sinComentarios(jobLines(workflow, 'design-system-runtime')).join('\n');
  assert.match(runtime, /^    needs: \[cambios, design-system-static\]$/m);
  const condicion = runtime.match(/^    if: (.*)$/m)?.[1];
  assert.ok(condicion, 'runtime no declara if');
  assert.match(condicion, /!cancelled\(\)/);
  assert.match(condicion, /needs\.cambios\.result == 'success'/);
  assert.match(condicion, /needs\.cambios\.outputs\.runtime == 'true'/);
  assert.match(condicion, /needs\.design-system-static\.result == 'success'/);
  assert.match(condicion, /needs\.design-system-static\.result == 'skipped'/);
  assert.doesNotMatch(condicion, /failure/);
});

test('la matriz de temas sale de cambios con respaldo a los dos temas', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const runtime = sinComentarios(jobLines(workflow, 'design-system-runtime')).join('\n');
  assert.match(
    runtime,
    /^ {8}theme: \$\{\{ fromJSON\(needs\.cambios\.outputs\.temas \|\| '\["light","dark"\]'\) \}\}$/m,
  );
});

test('el workflow no usa las palabras vetadas ni en comentarios', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  assert.doesNotMatch(workflow, /deploy|production|pull_request_target/i);
});

// --- Tarea 4: cada gate cuelga de su bandera --------------------------------------------------
const LUZ = "matrix.theme == 'light'";
const salida = (clave) => `needs.cambios.outputs.${clave} == 'true'`;

// [id o nombre del paso, banderas que su `if` debe contener, ¿solo en la pata clara?]
const RUNTIME_POR_PASO = [
  ['Verify the comment-free CSS matches its source', ['css_minify'], true],
  ['phpstan-baseline', ['php_runtime'], true],
  ['runtime-grants', ['php_runtime'], true],
  ['php-suite', ['php_runtime'], true],
  ['php-admin-db', ['php_runtime'], true],
  ['phpstan-pdc', ['phpstan_pdc'], true],
  ['full-app-flow', ['e2e'], true],
  ['semanal-roles-phases', ['e2e'], true],
  ['pg-persistence-rbac', ['e2e'], true],
  ['runtime-budget-measure', ['pilot'], true],
  ['runtime-budget-check', ['pilot'], true],
  ['blocking-runtime', ['lab'], false],
  ['keyboard-reflow-evidence', ['lab'], false],
  ['pilot-lab-gates', ['pilot'], false],
];

const buscar = (steps, clave) => steps.find((paso) => paso.id === clave || paso.name === clave);

test('cada gate del runtime lleva su `if` con su bandera y, si no depende del tema, corre solo en la pata clara', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  for (const [clave, banderas, soloLuz] of RUNTIME_POR_PASO) {
    const paso = buscar(steps, clave);
    assert.ok(paso, `falta el paso ${clave}`);
    assert.ok(paso.if, `el paso ${clave} no declara if`);
    for (const bandera of banderas) {
      assert.ok(paso.if.includes(salida(bandera)), `${clave}: su if no lee ${bandera} (${paso.if})`);
    }
    assert.equal(paso.if.includes(LUZ), soloLuz, `${clave}: la condición de la pata clara no es la esperada (${paso.if})`);
  }
});

test('los tres pasos del frontend del job static cuelgan de la bandera frontend y el resto no lleva if', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-static');
  const frontend = ['Instalar dependencias del frontend', 'Comprobar tipos del frontend', 'Correr las pruebas del frontend'];
  for (const nombre of frontend) {
    const paso = buscar(steps, nombre);
    assert.ok(paso, `falta el paso ${nombre}`);
    assert.equal(paso.if, salida('frontend'), `${nombre}: if inesperado`);
  }
  for (const paso of steps.filter((p) => !frontend.includes(p.name))) {
    assert.equal(paso.if, undefined, `el paso ${paso.name ?? paso.uses ?? paso.run} no debería llevar if`);
  }
});

test('los recibos solo se suben si su gate corrió, y la evidencia no bloqueante conserva su condición', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  const recibos = [
    ['Preserve the full-app-flow receipt', 'full-app-flow'],
    ['Preserve the semanal roles and phases receipt', 'semanal-roles-phases'],
    ['Preserve the runtime-budgets receipt', 'runtime-budget-check'],
  ];
  for (const [nombre, gate] of recibos) {
    const paso = buscar(steps, nombre);
    assert.ok(paso, `falta el paso ${nombre}`);
    assert.equal(paso.if, `always() && steps.${gate}.outcome != 'skipped'`, `${nombre}: if inesperado`);
    assert.equal(paso.with?.['if-no-files-found'], 'error');
  }
  assert.equal(
    buscar(steps, 'Preserve non-blocking evidence failures')?.if,
    "steps.keyboard-reflow-evidence.outcome == 'failure'",
  );
  assert.equal(buscar(steps, 'blocking-runtime')?.['continue-on-error'], undefined);
});

test('los pasos de restauración y el resumen siguen corriendo siempre', async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  for (const paso of steps.filter((p) => p.name?.startsWith('Restore the worktree'))) {
    assert.equal(paso.if, 'always()', `${paso.name}: debe seguir con always()`);
  }
  assert.equal(buscar(steps, 'Summarize gate results')?.if, 'always()');
});
