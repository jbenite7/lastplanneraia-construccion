import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, renameSync, rmSync, unlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

import {
  CARRILES,
  GATE_KEYS,
  clasificar,
  calcularSalida,
  clasificarRuta,
  formatearResumen,
  formatearSalida,
  gatesPara,
  rutasDelCambio,
} from '../../scripts/ci-carriles.mjs';

const RUTAS = {
  docs: [
    'docs/superpowers/specs/x.md',
    'goals/algo/goal.md',
    'memoria/index.md',
    'decisiones/0001.md',
    'ROADMAP.md',
    'TASKS.md',
    '.obsidian/app.json',
    './docs/a.md',
  ],
  'ds-core': [
    'docs/design-system/README.md',
    'public/css/tokens.css',
    'public/css/aia-design-system.css',
    'public/css/design-system/core.css',
    'public/js/modules/aia_ui/theme-toggle.js',
    'src/View/Components/DesignSystemHeadComponent.php',
    'src/View/Components/DesignSystemComponent.php',
    'src/View/Components/BiAccessComponent.php',
    'public/css/styles.css',
    'public/css/buttons.css',
    'public/css/access.css',
    'public/css/handsontable-module.css',
    'public/css/handsontable-header-global.css',
    'public/css/auth-react.css',
    'public/css/project-selector-react.css',
    'src/Controllers/Core/DesignSystemAssetController.php',
  ],
  'ds-lab': [
    'views/design-system/lab.view.php',
    'src/Controllers/Internal/DesignSystemLabController.php',
    'src/Security/DesignSystemLabAccessPolicy.php',
  ],
  'ds-modulo': [
    'goals/design-system-nucleo-gobernanza/x.md',
    'DESIGN.md',
    'GEMINI.md',
    'README.md',
    'AGENTS.md',
    'CLAUDE.md',
    'public/css/profesionales.css',
    'public/js/x.js',
    'views/profesionales/profesionales.view.php',
    'public/dist-css/x.css',
  ],
  apps: [
    'views/plan-compras/app.view.php',
    'pdc-app/src/main.tsx',
    'ct-app/src/x.ts',
    'public/pdc-app/x.js',
  ],
  'front-src': [
    'frontend/src/shell/rutas.tsx',
    'frontend/package.json',
    'frontend/vite.config.ts',
  ],
  'front-bundle': ['public/app/assets/index-C767lz-p.js'],
  php: [
    'docs/security/rls-runtime-boundary.md',
    '.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md',
    'src/Services/Pdc/X.php',
    'admin/src/Core/Router.php',
    'database/fixtures/x.sql',
    'composer.lock',
    'phpstan.neon',
    'phpunit.xml',
    'admin/index.php',
    'public/index.php',
    'admin/views/x.view.php',
    'admin/public/css/x.css',
    '.superpowers/sdd/otro-frente/plan.md',
  ],
  'tests-ds': ['tests/design-system/ci-carriles.test.mjs', 'tests/design-system/x.mjs'],
  'tests-php': ['tests/test_x.php', 'tests/unit/XTest.php', 'tests/unit/Sub/YTest.php'],
  todo: [
    '.github/workflows/ci.yml',
    'scripts/ci-carriles.mjs',
    'tests/browser/x.mjs',
    'tests/fixtures/x',
    'tests/scripts/x.sh',
    'tests/test_x.mjs',
    'tests/test_x.php/sub',
    'package.json',
    'docker/php/Dockerfile',
    'e2e/x.mjs',
    'carpeta-nueva/x',
  ],
};

for (const [carril, rutas] of Object.entries(RUTAS)) {
  for (const ruta of rutas) {
    test(`clasificarRuta: ${ruta} -> ${carril}`, () => {
      assert.equal(clasificarRuta(ruta), carril);
    });
  }
}

test('todos los carriles esperados existen en CARRILES', () => {
  for (const carril of Object.keys(RUTAS)) {
    assert.ok(CARRILES.includes(carril), carril);
  }
  assert.equal(CARRILES.length, 11);
  assert.equal(GATE_KEYS.length, 9);
});

test('clasificar: lista vacía corre todo', () => {
  assert.deepEqual([...clasificar([])], ['todo']);
});

test('clasificar: unión de carriles', () => {
  assert.deepEqual(
    [...clasificar(['docs/a.md', 'src/x.php'])].sort(),
    ['docs', 'php'],
  );
});

const activas = (r) => GATE_KEYS.filter((k) => r.gates[k]);

const TABLA = [
  [['docs'], [], ['light']],
  [['front-src'], ['static', 'frontend'], ['light']],
  [
    ['ds-modulo'],
    ['static', 'runtime', 'php_runtime', 'css_minify', 'e2e', 'lab'],
    ['light', 'dark'],
  ],
  [
    ['ds-lab'],
    ['static', 'runtime', 'php_runtime', 'css_minify', 'e2e', 'lab'],
    ['light', 'dark'],
  ],
  [
    ['ds-core'],
    ['static', 'runtime', 'php_runtime', 'css_minify', 'e2e', 'lab', 'pilot'],
    ['light', 'dark'],
  ],
  [
    ['php'],
    ['static', 'runtime', 'php_runtime', 'phpstan_pdc', 'e2e'],
    ['light'],
  ],
  [
    ['front-bundle'],
    ['static', 'frontend', 'runtime', 'php_runtime', 'e2e', 'pilot', 'lab'],
    ['light', 'dark'],
  ],
  [['apps'], ['static', 'runtime', 'phpstan_pdc'], ['light']],
  [['tests-ds'], ['static'], ['light']],
  [['tests-php'], ['static', 'runtime', 'php_runtime'], ['light']],
  [['todo'], [...GATE_KEYS], ['light', 'dark']],
];

for (const [carriles, esperadas, temas] of TABLA) {
  test(`gatesPara: {${carriles.join(',')}}`, () => {
    const r = gatesPara(new Set(carriles));
    assert.deepEqual(
      activas(r).sort(),
      [...esperadas].sort(),
    );
    assert.deepEqual([...r.temas].sort(), [...temas].sort());
    for (const k of GATE_KEYS) assert.equal(typeof r.gates[k], 'boolean');
  });
}

test('php y apps no encienden lab ni el tema oscuro', () => {
  for (const carril of ['php', 'apps', 'tests-ds', 'tests-php', 'front-src', 'docs']) {
    const r = gatesPara(new Set([carril]));
    assert.equal(r.gates.lab, false, carril);
    assert.equal(r.gates.pilot, false, carril);
    assert.deepEqual(r.temas, ['light'], carril);
  }
});

test('gatesPara: completo:true enciende las nueve', () => {
  const r = gatesPara(new Set(['docs']), { completo: true });
  assert.deepEqual(activas(r), [...GATE_KEYS]);
  assert.deepEqual([...r.temas].sort(), ['dark', 'light']);
});

test('gatesPara: unión de carriles hace OR de banderas', () => {
  const r = gatesPara(new Set(['front-src', 'apps']));
  assert.deepEqual(
    activas(r).sort(),
    ['frontend', 'phpstan_pdc', 'runtime', 'static'],
  );
});

test('invariantes: runtime es el OR de sus dependientes y dark solo con lab o pilot', () => {
  const dependientes = ['php_runtime', 'phpstan_pdc', 'css_minify', 'e2e', 'lab', 'pilot'];
  const combos = [];
  for (const a of CARRILES) {
    combos.push(new Set([a]));
    for (const b of CARRILES) combos.push(new Set([a, b]));
  }
  for (const c of combos) {
    for (const completo of [false, true]) {
      const r = gatesPara(c, { completo });
      assert.equal(r.gates.runtime, dependientes.some((k) => r.gates[k]));
      assert.equal(r.temas.includes('dark'), r.gates.lab || r.gates.pilot);
    }
  }
});

test('gatesPara: un carril desconocido cae a la fila todo', () => {
  const r = gatesPara(new Set(['carril-inventado']));
  assert.deepEqual(activas(r), [...GATE_KEYS]);
  assert.deepEqual([...r.temas].sort(), ['dark', 'light']);
});

// ---------------------------------------------------------------------------
// CLI: rutas del diff, salida y fallo hacia la corrida completa
// ---------------------------------------------------------------------------

const SHA_A = 'a'.repeat(40);
const SHA_B = 'b'.repeat(40);
const CEROS = '0'.repeat(40);
const PR = { EVENT_NAME: 'pull_request', BASE_SHA: SHA_A, HEAD_SHA: SHA_B };

const todasVerdes = (gates) => GATE_KEYS.every((k) => gates[k] === true);
const todasFalsas = (gates) => GATE_KEYS.every((k) => gates[k] === false);

test('calcularSalida: pull_request solo con documentacion no activa ningun gate', () => {
  const git = () => 'docs/a.md\0docs/b.md\0';
  const salida = calcularSalida(PR, git);
  assert.ok(todasFalsas(salida.gates));
  assert.deepEqual(salida.carriles, ['docs']);
  assert.deepEqual(salida.temas, ['light']);
});

test('rutasDelCambio: pull_request diffea BASE_SHA contra HEAD_SHA con -z y sin renombrados', () => {
  let recibidos;
  const rutas = rutasDelCambio(PR, (args) => {
    recibidos = args;
    return 'docs/a.md\0docs/b.md\0';
  });
  assert.deepEqual(rutas, ['docs/a.md', 'docs/b.md']);
  assert.ok(recibidos.includes('--name-only'));
  assert.ok(recibidos.includes('--no-renames'));
  assert.ok(recibidos.includes('-z'));
  assert.ok(recibidos.indexOf(SHA_A) < recibidos.indexOf(SHA_B));
});

test('rutasDelCambio: push diffea BEFORE_SHA contra HEAD_SHA', () => {
  let recibidos;
  const rutas = rutasDelCambio(
    { EVENT_NAME: 'push', BEFORE_SHA: SHA_A, HEAD_SHA: SHA_B },
    (args) => {
      recibidos = args;
      return 'src/x.php\0';
    },
  );
  assert.deepEqual(rutas, ['src/x.php']);
  assert.ok(recibidos.indexOf(SHA_A) < recibidos.indexOf(SHA_B));
});

test('calcularSalida: push, workflow_dispatch y evento ausente corren todo', () => {
  const git = () => 'docs/a.md\0';
  for (const EVENT_NAME of ['push', 'workflow_dispatch', undefined]) {
    const salida = calcularSalida(
      { EVENT_NAME, BEFORE_SHA: SHA_A, BASE_SHA: SHA_A, HEAD_SHA: SHA_B },
      git,
    );
    assert.ok(todasVerdes(salida.gates), `evento ${EVENT_NAME}`);
    assert.deepEqual(salida.carriles, ['completo']);
    assert.deepEqual([...salida.temas].sort(), ['dark', 'light']);
  }
});

test('rutasDelCambio: SHA en ceros, ausente o mal formado da null sin invocar git', () => {
  const git = () => assert.fail('no debio llamar a git');
  assert.equal(
    rutasDelCambio({ EVENT_NAME: 'push', BEFORE_SHA: CEROS, HEAD_SHA: SHA_B }, git),
    null,
  );
  assert.equal(rutasDelCambio({ ...PR, BASE_SHA: undefined }, git), null);
  assert.equal(rutasDelCambio({ ...PR, HEAD_SHA: '' }, git), null);
  assert.equal(rutasDelCambio({ ...PR, BASE_SHA: '--output=/tmp/x' }, git), null);
  assert.equal(rutasDelCambio({ EVENT_NAME: 'workflow_dispatch', HEAD_SHA: SHA_B }, git), null);
});

test('calcularSalida: SHA en ceros o inexistente cae a la corrida completa', () => {
  const ceros = calcularSalida({ ...PR, BASE_SHA: CEROS }, () => 'docs/a.md\0');
  assert.ok(todasVerdes(ceros.gates));
  assert.deepEqual(ceros.carriles, ['completo']);
  // Inexistente: git real, en un repositorio temporal que no conoce esos SHA.
  const { dir, limpiar } = repoTemporal();
  try {
    const gitReal = gitEn(dir);
    assert.equal(rutasDelCambio(PR, gitReal), null);
    const salida = calcularSalida(PR, gitReal);
    assert.ok(todasVerdes(salida.gates));
    assert.deepEqual(salida.carriles, ['completo']);
  } finally {
    limpiar();
  }
});

test('calcularSalida: un git que lanza no se propaga y corre todo', () => {
  const salida = calcularSalida(PR, () => {
    throw new Error('git roto');
  });
  assert.ok(todasVerdes(salida.gates));
  assert.deepEqual(salida.carriles, ['completo']);
});

test('calcularSalida: diff vacio corre todo (no hay evidencia para omitir)', () => {
  for (const vacio of ['', '\0']) {
    const salida = calcularSalida(PR, () => vacio);
    assert.ok(todasVerdes(salida.gates));
    assert.deepEqual(salida.carriles, ['completo']);
  }
});

function repoTemporal() {
  const dir = mkdtempSync(join(tmpdir(), 'ci-carriles-'));
  const git = (...args) =>
    execFileSync('git', ['-c', 'user.email=t@t', '-c', 'user.name=t', ...args], {
      cwd: dir,
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
    }).trim();
  git('init', '-q');
  git('config', 'commit.gpgsign', 'false');
  return { dir, git, limpiar: () => rmSync(dir, { recursive: true, force: true }) };
}

function gitEn(dir) {
  return (args) =>
    execFileSync('git', args, { cwd: dir, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
}

function escribir(dir, ruta, texto = 'x\n') {
  const destino = join(dir, ruta);
  mkdirSync(dirname(destino), { recursive: true });
  writeFileSync(destino, texto);
}

// Crea un repositorio con dos commits: `antes` prepara el primero y `cambio` el segundo.
function conDosCommits(antes, cambio) {
  const repo = repoTemporal();
  antes(repo.dir);
  repo.git('add', '-A');
  repo.git('commit', '-q', '-m', 'uno');
  const base = repo.git('rev-parse', 'HEAD');
  cambio(repo.dir);
  repo.git('add', '-A');
  repo.git('commit', '-q', '-m', 'dos');
  const head = repo.git('rev-parse', 'HEAD');
  return { ...repo, entorno: { EVENT_NAME: 'pull_request', BASE_SHA: base, HEAD_SHA: head } };
}

test('git real: ruta con espacios, acentos y comillas llega integra y clasifica como docs', () => {
  const ruta = 'docs/con espacio/año "raro".md';
  const repo = conDosCommits(
    (d) => escribir(d, 'README-base.txt'),
    (d) => escribir(d, ruta),
  );
  try {
    const rutas = rutasDelCambio(repo.entorno, gitEn(repo.dir));
    assert.deepEqual(rutas, [ruta]);
    assert.equal(clasificarRuta(rutas[0]), 'docs');
    const salida = calcularSalida(repo.entorno, gitEn(repo.dir));
    assert.deepEqual(salida.carriles, ['docs']);
    assert.ok(todasFalsas(salida.gates));
  } finally {
    repo.limpiar();
  }
});

test('git real: un renombrado entre carriles activa ambos (php y docs)', () => {
  const repo = conDosCommits(
    (d) => escribir(d, 'src/a.php', '<?php // a\n'),
    (d) => {
      mkdirSync(join(d, 'docs'), { recursive: true });
      renameSync(join(d, 'src/a.php'), join(d, 'docs/a.md'));
    },
  );
  try {
    const rutas = rutasDelCambio(repo.entorno, gitEn(repo.dir));
    assert.deepEqual([...rutas].sort(), ['docs/a.md', 'src/a.php']);
    const { carriles } = calcularSalida(repo.entorno, gitEn(repo.dir));
    assert.ok(carriles.includes('php'));
    assert.ok(carriles.includes('docs'));
  } finally {
    repo.limpiar();
  }
});

test('git real: un archivo borrado cuenta como ruta y da php', () => {
  const repo = conDosCommits(
    (d) => escribir(d, 'src/x.php', '<?php // x\n'),
    (d) => unlinkSync(join(d, 'src/x.php')),
  );
  try {
    assert.deepEqual(rutasDelCambio(repo.entorno, gitEn(repo.dir)), ['src/x.php']);
    const salida = calcularSalida(repo.entorno, gitEn(repo.dir));
    assert.deepEqual(salida.carriles, ['php']);
    assert.equal(salida.gates.php_runtime, true);
  } finally {
    repo.limpiar();
  }
});

test('formatearSalida: una linea por gate, temas JSON valido y carriles', () => {
  const salida = calcularSalida(PR, () => 'src/x.php\0docs/a.md\0');
  const lineas = formatearSalida(salida).trim().split('\n');
  assert.equal(lineas.length, GATE_KEYS.length + 2);
  for (const k of GATE_KEYS) {
    const linea = lineas.find((l) => l.startsWith(`${k}=`));
    assert.ok(linea, `falta ${k}`);
    assert.equal(linea, `${k}=${salida.gates[k]}`);
  }
  const temas = JSON.parse(lineas.find((l) => l.startsWith('temas=')).slice('temas='.length));
  assert.ok(Array.isArray(temas) && temas.includes('light'));
  assert.equal(lineas.find((l) => l.startsWith('carriles=')), 'carriles=docs,php');
  const completa = formatearSalida(calcularSalida({}, () => ''));
  assert.match(completa, /^carriles=completo$/m);
  assert.match(completa, /^temas=\["light","dark"\]$/m);
});

// ---------------------------------------------------------------------------
// CLI real en subproceso: stdout solo con las banderas, resumen aparte
// ---------------------------------------------------------------------------

const SCRIPT = fileURLToPath(new URL('../../scripts/ci-carriles.mjs', import.meta.url));

function correrCli(entorno) {
  return spawnSync(process.execPath, [SCRIPT], {
    env: { PATH: process.env.PATH, ...entorno },
    encoding: 'utf8',
  });
}

test('CLI real: push sale 0 e imprime una linea por cada clave, temas y carriles', () => {
  const r = correrCli({ EVENT_NAME: 'push' });
  assert.equal(r.status, 0, r.stderr);
  const lineas = r.stdout.trim().split('\n');
  assert.equal(lineas.length, GATE_KEYS.length + 2);
  for (const k of [...GATE_KEYS, 'temas', 'carriles']) {
    assert.equal(lineas.filter((l) => l.startsWith(`${k}=`)).length, 1, `falta ${k}`);
  }
  assert.equal(r.stdout, formatearSalida(calcularSalida({ EVENT_NAME: 'push' })));
});

test('CLI real: con GITHUB_STEP_SUMMARY añade la tabla al archivo y no ensucia stdout', () => {
  const dir = mkdtempSync(join(tmpdir(), 'ci-carriles-resumen-'));
  try {
    const resumen = join(dir, 'summary.md');
    writeFileSync(resumen, 'previo\n');
    const r = correrCli({ EVENT_NAME: 'push', GITHUB_STEP_SUMMARY: resumen });
    assert.equal(r.status, 0, r.stderr);
    assert.equal(r.stdout, formatearSalida(calcularSalida({ EVENT_NAME: 'push' })));
    const texto = readFileSync(resumen, 'utf8');
    assert.ok(texto.startsWith('previo\n'), 'debe añadir, no sobrescribir');
    assert.match(texto, /^## Selector de carriles$/m);
    assert.match(texto, /^Carriles detectados: completo$/m);
    for (const k of GATE_KEYS) assert.match(texto, new RegExp(`^\\| ${k} \\| corre \\|$`, 'm'));
    assert.match(texto, /^Temas: light, dark$/m);
    assert.doesNotMatch(r.stdout, /Selector de carriles/);
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
});

test('formatearResumen: distingue gate que corre de gate omitido por carril no tocado', () => {
  const salida = calcularSalida(PR, () => 'docs/a.md\0');
  const texto = formatearResumen(salida);
  assert.match(texto, /^Carriles detectados: docs$/m);
  for (const k of GATE_KEYS) {
    assert.match(texto, new RegExp(`^\\| ${k} \\| omitido \\(carril no tocado\\) \\|$`, 'm'));
  }
  assert.match(texto, /^Temas: light$/m);
});

test('CLI real: sin GITHUB_STEP_SUMMARY no falla ni escribe archivos', () => {
  const r = correrCli({ EVENT_NAME: 'push' });
  assert.equal(r.status, 0);
});
