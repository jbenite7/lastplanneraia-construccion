import assert from 'node:assert/strict';
import test from 'node:test';

import {
  CARRILES,
  GATE_KEYS,
  clasificar,
  clasificarRuta,
  gatesPara,
} from '../../scripts/ci-carriles.mjs';

const RUTAS = {
  docs: [
    'docs/superpowers/specs/x.md',
    'goals/algo/goal.md',
    'memoria/index.md',
    'decisiones/0001.md',
    'ROADMAP.md',
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
  ],
  'ds-lab': [
    'views/design-system/lab.view.php',
    'src/Controllers/Internal/DesignSystemLabController.php',
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
    'admin/views/x.view.php',
    'admin/public/css/x.css',
    '.superpowers/sdd/otro-frente/plan.md',
  ],
  todo: [
    '.github/workflows/ci.yml',
    'scripts/ci-carriles.mjs',
    'tests/test_x.php',
    'package.json',
    'docker/php/Dockerfile',
    'e2e/x.mjs',
    'carpeta-nueva/x',
    'public/index.php',
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
  assert.equal(CARRILES.length, 9);
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
    ['static', 'runtime', 'php_runtime', 'css_minify', 'e2e'],
    ['light'],
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
    ['static', 'frontend', 'runtime', 'php_runtime', 'e2e', 'pilot'],
    ['light', 'dark'],
  ],
  [['apps'], ['static', 'runtime', 'phpstan_pdc'], ['light']],
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
