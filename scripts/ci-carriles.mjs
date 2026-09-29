// Selector puro de carriles del CI: ruta -> carril -> banderas de gate.
// Sin dependencias ni E/S: lo consumen el script de diff y el workflow.
// Regla de oro: ante la duda, `todo` (correr todos los gates).

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
  // Núcleo del design system.
  [/^public\/css\/tokens\.css$/, 'ds-core'],
  [/^public\/css\/aia-design-system\.css$/, 'ds-core'],
  [/^public\/css\/design-system\//, 'ds-core'],
  [/^public\/js\/modules\/aia_ui\//, 'ds-core'],
  [/^src\/View\/Components\//, 'ds-core'],
  // Laboratorio del design system.
  [/^views\/design-system\//, 'ds-lab'],
  [/^src\/Controllers\/Internal\/DesignSystemLabController\.php$/, 'ds-lab'],
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
  'ds-modulo': ['static', 'php_runtime', 'css_minify', 'e2e'],
  'ds-lab': ['static', 'php_runtime', 'css_minify', 'e2e', 'lab'],
  'ds-core': ['static', 'php_runtime', 'css_minify', 'e2e', 'lab', 'pilot'],
  php: ['static', 'php_runtime', 'phpstan_pdc', 'e2e'],
  'front-bundle': ['static', 'frontend', 'php_runtime', 'e2e', 'pilot'],
  apps: ['static', 'phpstan_pdc'],
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
