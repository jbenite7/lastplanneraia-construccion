import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
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

// --- Tarea 5: el resumen distingue omitido de verde -------------------------------------------
const resumenRun = async () => {
  const workflow = await read('.github/workflows/ci.yml');
  const paso = buscar(parseJobSteps(workflow, 'design-system-runtime'), 'Summarize gate results');
  assert.ok(paso?.run, 'falta el run de Summarize gate results');
  return paso;
};

// Las trece filas de la tabla, en su orden: [texto de la fila, variable G_, bandera, ¿solo light?]
const FILAS = [
  ['Enforce PHPStan baseline', 'G_PHPSTAN_BASELINE', 'php_runtime', true],
  ['Enforce PHPStan level 6 on the PDC module', 'G_PHPSTAN_PDC', 'phpstan_pdc', true],
  ['Atestar grants efectivos de runtime', 'G_RUNTIME_GRANTS', 'php_runtime', true],
  ['Correr la suite PHP completa que el CI puede honrar', 'G_PHP_SUITE', 'php_runtime', true],
  ['Correr fixtures de migración con admin efímero', 'G_PHP_ADMIN_DB', 'php_runtime', true],
  ['Enforce full-app-flow gate', 'G_FULL_APP_FLOW', 'e2e', true],
  ['Enforce semanal roles and phases gate', 'G_SEMANAL_ROLES_PHASES', 'e2e', true],
  ['Measure runtime budgets', 'G_RUNTIME_BUDGET_MEASURE', 'pilot', true],
  ['Check runtime budgets against the baseline', 'G_RUNTIME_BUDGET_CHECK', 'pilot', true],
  ['Run laboratory gates', 'G_LABORATORY_GATES', 'lab', false],
  ['Run pilot lab gates (Programa General)', 'G_PILOT_LAB_GATES', 'pilot', false],
  ['Collect keyboard and reflow evidence (no bloqueante)', 'G_KEYBOARD_REFLOW_EVIDENCE', 'lab', false],
  ['Run Programa General persistence and RBAC gate', 'G_PG_PERSISTENCE_RBAC', 'e2e', true],
];
const BANDERAS = ['php_runtime', 'phpstan_pdc', 'e2e', 'pilot', 'lab'];

test('el env del resumen trae banderas por gate, el tema y los carriles, y cada fila usa la función de estado', async () => {
  const paso = await resumenRun();
  assert.equal(paso.env?.THEME, '${{ matrix.theme }}');
  assert.equal(paso.env?.CARRILES, '${{ needs.cambios.outputs.carriles }}');
  for (const bandera of BANDERAS) {
    assert.equal(
      paso.env?.[`F_${bandera.toUpperCase()}`],
      `\${{ needs.cambios.outputs.${bandera} }}`,
      `falta la bandera ${bandera} en el env del resumen`,
    );
  }
  assert.equal(paso.env?.G_RUNTIME_GRANTS, '${{ steps.runtime-grants.outcome }}');
  assert.match(paso.run, /\$G_RUNTIME_GRANTS/);
  assert.match(paso.run, /^ *estado\(\) \{$/m);
  assert.ok(!paso.run.includes('${{'), 'el run no debe interpolar expresiones');
  for (const [texto, variable, bandera, soloLuz] of FILAS) {
    const esperada = `echo "| ${texto} | $(estado "\${${variable}}" "\${F_${bandera.toUpperCase()}}"${soloLuz ? ' light' : ''}) |"`;
    assert.ok(paso.run.includes(esperada), `la fila «${texto}» no usa estado con su bandera: ${esperada}`);
  }
  assert.match(paso.run, /echo "Carriles detectados: /);
  // El veredicto solo trata `failure` como rojo.
  assert.match(paso.run, /if \[ "\$outcome" = "failure" \]; then/);
});

// Ejecuta de verdad el `run` con bash y variables simuladas; devuelve el resumen y el código de salida.
const correrResumen = async ({ tema = 'light', banderas = {}, resultados = {}, carriles = 'php' }) => {
  const { run } = await resumenRun();
  const dir = await mkdtemp(join(tmpdir(), 'resumen-ci-'));
  try {
    const salida = join(dir, 'summary.md');
    await writeFile(salida, '');
    const env = { PATH: process.env.PATH, GITHUB_STEP_SUMMARY: salida, THEME: tema, CARRILES: carriles };
    for (const b of BANDERAS) env[`F_${b.toUpperCase()}`] = String(banderas[b] ?? false);
    for (const [, variable] of FILAS) env[variable] = resultados[variable] ?? 'success';
    const r = spawnSync('bash', ['-c', run], { cwd: dir, env, encoding: 'utf8' });
    return { code: r.status, stderr: r.stderr, resumen: await readFile(salida, 'utf8') };
  } finally {
    await rm(dir, { recursive: true, force: true });
  }
};
const fila = (resumen, texto) => resumen.split('\n').find((l) => l.startsWith(`| ${texto} |`));
const TODO_OMITIDO = Object.fromEntries(FILAS.map(([, v]) => [v, 'skipped']));

test('pata light de un PR de carril php: lab y pilot quedan omitidos por carril no tocado', async () => {
  const { code, resumen } = await correrResumen({
    banderas: { php_runtime: true },
    carriles: 'php',
    resultados: { ...TODO_OMITIDO, G_PHPSTAN_BASELINE: 'success', G_RUNTIME_GRANTS: 'success', G_PHP_SUITE: 'success', G_PHP_ADMIN_DB: 'success' },
  });
  assert.equal(code, 0);
  assert.match(resumen, /Carriles detectados: php/);
  for (const texto of ['Run laboratory gates', 'Run pilot lab gates (Programa General)', 'Enforce full-app-flow gate', 'Measure runtime budgets']) {
    assert.equal(fila(resumen, texto), `| ${texto} | omitido (carril no tocado) |`);
  }
  assert.equal(fila(resumen, 'Enforce PHPStan baseline'), '| Enforce PHPStan baseline | success |');
});

test('pata dark: los gates solo light dicen «corre solo en light» y los de las dos patas siguen su bandera', async () => {
  const { code, resumen } = await correrResumen({
    tema: 'dark',
    banderas: { pilot: true, e2e: false },
    carriles: 'pilot',
    resultados: { ...TODO_OMITIDO, G_PILOT_LAB_GATES: 'success' },
  });
  assert.equal(code, 0);
  for (const [texto, , , soloLuz] of FILAS.filter(([, v]) => v !== 'G_PILOT_LAB_GATES' && v !== 'G_LABORATORY_GATES' && v !== 'G_KEYBOARD_REFLOW_EVIDENCE')) {
    assert.ok(soloLuz);
    assert.equal(fila(resumen, texto), `| ${texto} | corre solo en light |`);
  }
  assert.equal(fila(resumen, 'Run pilot lab gates (Programa General)'), '| Run pilot lab gates (Programa General) | success |');
  assert.equal(fila(resumen, 'Run laboratory gates'), '| Run laboratory gates | omitido (carril no tocado) |');
});

test('bandera en true y resultado skipped en la pata light: no corrió (paso anterior falló), sin pasar por verde', async () => {
  const { code, resumen } = await correrResumen({
    banderas: { e2e: true, lab: true },
    carriles: 'e2e,lab',
    resultados: { ...TODO_OMITIDO, G_FULL_APP_FLOW: 'success' },
  });
  assert.equal(fila(resumen, 'Enforce semanal roles and phases gate'), '| Enforce semanal roles and phases gate | no corrió (paso anterior falló) |');
  assert.equal(fila(resumen, 'Run laboratory gates'), '| Run laboratory gates | no corrió (paso anterior falló) |');
  assert.equal(fila(resumen, 'Enforce full-app-flow gate'), '| Enforce full-app-flow gate | success |');
  assert.equal(code, 0, 'un omitido no es rojo');
});

test('success se imprime como success y no como omitido', async () => {
  const { resumen } = await correrResumen({ banderas: { php_runtime: true, e2e: true, pilot: true, lab: true, phpstan_pdc: true } });
  assert.doesNotMatch(resumen, /omitido|corre solo en light|no corrió/);
  for (const [texto] of FILAS) assert.equal(fila(resumen, texto), `| ${texto} | success |`);
});

test('el veredicto sale 1 con un failure y 0 con solo omitidos', async () => {
  const rojo = await correrResumen({ resultados: { G_FULL_APP_FLOW: 'failure' } });
  assert.equal(rojo.code, 1);
  assert.equal(fila(rojo.resumen, 'Enforce full-app-flow gate'), '| Enforce full-app-flow gate | failure |');
  const omitidos = await correrResumen({ resultados: TODO_OMITIDO });
  assert.equal(omitidos.code, 0);
});

test('el presupuesto de runtime omitido dice la etiqueta de estado y no que no llegó a escribirse', async () => {
  const omitido = await correrResumen({ resultados: { ...TODO_OMITIDO } });
  assert.match(omitido.resumen, /Measure runtime budgets`: omitido \(carril no tocado\)/);
  assert.doesNotMatch(omitido.resumen, /no llegó a escribirlo/);
  const corrio = await correrResumen({ banderas: { pilot: true }, resultados: { G_RUNTIME_BUDGET_MEASURE: 'failure' } });
  assert.match(corrio.resumen, /no llegó a escribirlo/);
  const frio = await correrResumen({ resultados: { ...TODO_OMITIDO } });
  assert.match(frio.resumen, /\| runtime-budgets \| sin dato ms \|/);
});
