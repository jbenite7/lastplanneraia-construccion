// Selector puro de carriles del CI: ruta -> carril -> banderas de gate.
// Sin dependencias ni E/S: lo consumen el script de diff y el workflow.
// Regla de oro: ante la duda, `todo` (correr todos los gates).
// Sin argumentos, como CLI, calcula las rutas del diff con git y escribe las
// banderas en stdout (el workflow lo redirige a $GITHUB_OUTPUT).

import { execFileSync } from 'node:child_process';
import { appendFileSync, realpathSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

export const GATE_KEYS = Object.freeze([
  'static',
  'frontend',
  'runtime',
  'php_runtime',
  'phpstan_pdc',
  'css_minify',
  'e2e',
  'lab',
  'pilot',
]);

export const CARRILES = Object.freeze([
  'docs',
  'php',
  'ds-core',
  'ds-lab',
  'ds-modulo',
  'front-src',
  'front-bundle',
  'apps',
  'tests-ds',
  'tests-php',
  'todo',
]);

// Lista ordenada: la primera regla que coincide gana. El orden importa:
// las excepciones de docs/ y goals/ van antes del comodín de documentación,
// ds-core y ds-lab antes de ds-modulo, y apps y front-* antes de php.
const REGLAS = [
  // Excepciones dentro de las carpetas de documentación.
  [/^docs\/design-system\//, 'ds-core'],
  [/^docs\/security\//, 'php'],
  [/^\.superpowers\//, 'php'],
  [/^goals\/design-system-nucleo-gobernanza\//, 'ds-modulo'],
  [/^(DESIGN|GEMINI|README|AGENTS|CLAUDE)\.md$/, 'ds-modulo'],
  // Documentación pura: no toca el producto.
  [/^(docs|goals|memoria|decisiones|\.obsidian)\//, 'docs'],
  [/^ROADMAP\.md$/, 'docs'],
  [/^TASKS\.md$/, 'docs'],
  // Núcleo del design system.
  [/^public\/css\/tokens\.css$/, 'ds-core'],
  [/^public\/css\/(styles|buttons|access|handsontable-module|handsontable-header-global|auth-react|project-selector-react)\.css$/, 'ds-core'],
  [/^src\/Controllers\/Core\/DesignSystemAssetController\.php$/, 'ds-core'],
  [/^public\/css\/aia-design-system\.css$/, 'ds-core'],
  [/^public\/css\/design-system\//, 'ds-core'],
  [/^public\/js\/modules\/aia_ui\//, 'ds-core'],
  [/^src\/View\/Components\//, 'ds-core'],
  // Laboratorio del design system.
  [/^views\/design-system\//, 'ds-lab'],
  [/^src\/Controllers\/Internal\/DesignSystemLabController\.php$/, 'ds-lab'],
  [/^src\/Security\/DesignSystemLabAccessPolicy\.php$/, 'ds-lab'],
  // Pruebas: las del design system y las de PHP corren su propio carril;
  // el resto de tests/ (browser, fixtures, scripts...) cae en `todo`.
  [/^tests\/design-system\//, 'tests-ds'],
  [/^tests\/test_[^/]*\.php$/, 'tests-php'],
  [/^tests\/unit\//, 'tests-php'],
  // Apps con bundle propio (antes que views/ y php).
  [/^views\/plan-compras\//, 'apps'],
  [/^(pdc-app|ct-app)\//, 'apps'],
  [/^public\/pdc-app\//, 'apps'],
  // Frontend React del shell.
  [/^frontend\//, 'front-src'],
  [/^public\/app\//, 'front-bundle'],
  // Módulos de producto (CSS, JS y vistas).
  [/^public\/(css|js|dist-css)\//, 'ds-modulo'],
  [/^views\//, 'ds-modulo'],
  // Backend PHP y su configuración.
  [/^public\/index\.php$/, 'php'],
  [/^(src|admin|database)\//, 'php'],
  [/^composer\.(json|lock)$/, 'php'],
  [/^phpunit\.xml$/, 'php'],
  [/^phpstan[^/]*\.neon$/, 'php'],
];

export function clasificarRuta(ruta) {
  const limpia = String(ruta).replace(/^\.\//, '');
  for (const [patron, carril] of REGLAS) {
    if (patron.test(limpia)) return carril;
  }
  return 'todo';
}

export function clasificar(rutas) {
  if (!rutas || rutas.length === 0) return new Set(['todo']);
  return new Set(rutas.map(clasificarRuta));
}

// Matriz gate x carril (spec §3). `runtime` no se declara: se deriva.
const MATRIZ = {
  docs: [],
  'front-src': ['static', 'frontend'],
  'ds-modulo': ['static', 'php_runtime', 'css_minify', 'e2e', 'lab'],
  'ds-lab': ['static', 'php_runtime', 'css_minify', 'e2e', 'lab'],
  'ds-core': ['static', 'php_runtime', 'css_minify', 'e2e', 'lab', 'pilot'],
  php: ['static', 'php_runtime', 'phpstan_pdc', 'e2e'],
  'front-bundle': ['static', 'frontend', 'php_runtime', 'e2e', 'pilot', 'lab'],
  apps: ['static', 'phpstan_pdc'],
  'tests-ds': ['static'],
  'tests-php': ['static', 'php_runtime'],
  todo: GATE_KEYS.filter((k) => k !== 'runtime'),
};

// Gates que necesitan el runtime aislado levantado.
const NECESITAN_RUNTIME = ['php_runtime', 'phpstan_pdc', 'css_minify', 'e2e', 'lab', 'pilot'];

export function gatesPara(carriles, { completo = false } = {}) {
  const gates = Object.fromEntries(GATE_KEYS.map((k) => [k, false]));
  const fuente = completo ? ['todo'] : [...carriles];
  for (const carril of fuente) {
    for (const gate of MATRIZ[carril] ?? MATRIZ.todo) gates[gate] = true;
  }
  gates.runtime = NECESITAN_RUNTIME.some((k) => gates[k]);
  const temas = gates.lab || gates.pilot ? ['light', 'dark'] : ['light'];
  return { gates, temas };
}

// ---------------------------------------------------------------------------
// CLI: rutas del diff y salida para el workflow
// ---------------------------------------------------------------------------

const SHA_VALIDO = /^[0-9a-f]{40,64}$/i;
const SHA_EN_CEROS = /^0+$/;

// git por defecto: sin shell, salida como texto. Lanza si git falla.
function gitPorDefecto(args) {
  return execFileSync('git', args, {
    encoding: 'utf8',
    maxBuffer: 64 * 1024 * 1024,
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

// Rutas que cambia el evento, o `null` si el diff no se puede calcular
// (SHA ausente, en ceros, mal formado, inexistente o git fallido).
export function rutasDelCambio(entorno = {}, git = gitPorDefecto) {
  const { EVENT_NAME: evento, BASE_SHA, BEFORE_SHA, HEAD_SHA } = entorno;
  let anterior;
  if (evento === 'pull_request') anterior = BASE_SHA;
  else if (evento === 'push') anterior = BEFORE_SHA;
  else return null;

  // Validar antes de llamar a git: además de descartar los ceros de una rama
  // nueva, impide que un valor raro se lea como opción de git.
  for (const sha of [anterior, HEAD_SHA]) {
    if (typeof sha !== 'string' || !SHA_VALIDO.test(sha) || SHA_EN_CEROS.test(sha)) return null;
  }
  try {
    const bruto = git(['diff', '--name-only', '--no-renames', '-z', anterior, HEAD_SHA, '--']);
    return String(bruto).split('\0').filter((ruta) => ruta !== '');
  } catch {
    return null;
  }
}

// Nunca lanza: cualquier duda se resuelve corriendo todo.
// Hoy solo `pull_request` se selecciona por carriles; `push` y
// `workflow_dispatch` corren completo aunque `rutasDelCambio` sepa diffear push.
export function calcularSalida(entorno = {}, git = gitPorDefecto) {
  let rutas = null;
  try {
    if (entorno.EVENT_NAME === 'pull_request') rutas = rutasDelCambio(entorno, git);
  } catch {
    rutas = null;
  }
  if (rutas === null || rutas.length === 0) {
    const { gates, temas } = gatesPara(new Set(['todo']), { completo: true });
    return { gates, temas, carriles: ['completo'] };
  }
  const conjunto = clasificar(rutas);
  const { gates, temas } = gatesPara(conjunto);
  return { gates, temas, carriles: CARRILES.filter((c) => conjunto.has(c)) };
}

export function formatearSalida({ gates, temas, carriles }) {
  const lineas = GATE_KEYS.map((k) => `${k}=${gates[k] === true}`);
  const listaTemas = temas && temas.length > 0 ? temas : ['light'];
  lineas.push(`temas=${JSON.stringify(listaTemas)}`);
  lineas.push(`carriles=${carriles.join(',')}`);
  return `${lineas.join('\n')}\n`;
}

// Resumen para la pestaña del job (variable GITHUB_STEP_SUMMARY): qué carriles
// detectó el selector y qué hará cada gate. No va a stdout.
export function formatearResumen({ gates, temas, carriles }) {
  const listaTemas = temas && temas.length > 0 ? temas : ['light'];
  const lineas = [
    '## Selector de carriles',
    '',
    `Carriles detectados: ${carriles.join(', ')}`,
    '',
    '| Gate | Estado |',
    '| --- | --- |',
    ...GATE_KEYS.map((k) => `| ${k} | ${gates[k] === true ? 'corre' : 'omitido (carril no tocado)'} |`),
    '',
    `Temas: ${listaTemas.join(', ')}`,
  ];
  return `${lineas.join('\n')}\n`;
}

function esModuloPrincipal() {
  try {
    return realpathSync(process.argv[1]) === realpathSync(fileURLToPath(import.meta.url));
  } catch {
    return false;
  }
}

if (esModuloPrincipal()) {
  const salida = calcularSalida(process.env);
  process.stdout.write(formatearSalida(salida));
  // El resumen es un extra: si no se puede escribir, la salida de los gates ya
  // salió y el CI no debe caer por eso.
  if (process.env.GITHUB_STEP_SUMMARY) {
    try {
      appendFileSync(process.env.GITHUB_STEP_SUMMARY, `\n${formatearResumen(salida)}`);
    } catch (error) {
      process.stderr.write(`[ci-carriles] no pude escribir el resumen: ${error.message}\n`);
    }
  }
  const activos = GATE_KEYS.filter((k) => salida.gates[k]);
  process.stderr.write(
    `[ci-carriles] carriles: ${salida.carriles.join(', ')} | gates: ${activos.join(', ') || 'ninguno'} | temas: ${salida.temas.join(', ')}\n`,
  );
  process.exit(0);
}
