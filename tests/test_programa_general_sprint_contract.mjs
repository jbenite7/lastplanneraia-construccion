import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const view = fs.readFileSync(
  path.join(root, 'views/programa-general/programa_general.view.php'),
  'utf8',
);
const hot = fs.readFileSync(
  path.join(root, 'public/js/modules/programa_general/hot.js'),
  'utf8',
);
const css = fs.readFileSync(path.join(root, 'public/css/programa-general.css'), 'utf8');
// La primitiva aia-btn migró de aia-design-system.css al core compartido;
// el contrato sigue validando la fuente canónica real.
const coreCss = fs.readFileSync(path.join(root, 'public/css/design-system/core.css'), 'utf8');
const buttonsCss = fs.readFileSync(path.join(root, 'public/css/buttons.css'), 'utf8');
const bridgeCss = fs.readFileSync(path.join(root, 'public/css/design-system/adapters/legacy-bridge.css'), 'utf8');

const filterMarkup = view.match(/class="aia-chip pg-filter-chip[^"]*"[^>]*data-filter="[^"]+"[^>]*>/g) || [];

// El shell canonico admite modificadores: PG lleva `aia-shell--sidebar` desde
// da792e8 («Programa General usa el shell sidebar»). Se asierta la presencia de
// las dos clases en vez de la cadena exacta, que caducaba con cada variante
// legitima del shell y dejaba la CI en rojo sin que hubiera defecto alguno.
assert.match(
  view,
  /<body class="(?=[^"]*\baia-shell\b)(?=[^"]*\bpg-page\b)[^"]*">/,
  'PG debe usar el shell canónico y conservar el gancho pg-page',
);
assert.match(view, /<main[^>]*class="aia-page/, 'PG debe usar el canvas canónico');
assert.match(view, /class="[^"]*aia-action-group/, 'PG debe usar el grupo de acciones aprobado');
assert.match(view, /class="[^"]*aia-filter-form/, 'PG debe usar el filtro siempre visible aprobado');
assert.match(view, /id="hot-container"[^>]*class="[^"]*aia-grid-shell/, 'PG debe contener Handsontable en el shell canónico');
assert.match(view, /class="[^"]*aia-modal-surface/, 'PG debe usar la superficie canónica del diálogo');
assert.doesNotMatch(view, /handsontable\.full\.min\.css/, 'PG no debe cargar el CSS vendor fuera del entrypoint');
assert.doesNotMatch(css, /--spacing-/, 'PG solo puede consumir tokens semánticos --ds-*');
assert.doesNotMatch(css, /\.btn-pdc-modern/, 'PG no debe redefinir la primitiva de botón');
assert.doesNotMatch(css, /\.handsontable|\.ht(?:Core|_master|Dropdown|Filters)/, 'PG no debe contener overrides del vendor');
assert.doesNotMatch(css, /!important/, 'PG no debe usar important local');
assert.doesNotMatch(css, /overflow-wrap:\s*anywhere|word-break:\s*break-word/, 'PG nunca debe fragmentar palabras');
assert.match(css, /^@layer module\s*\{/, 'la CSS piloto debe quedar contenida en layer module');

assert.match(
  view,
  /\$pgCssVersion\s*=\s*@filemtime\(dirname\(__DIR__, 2\)\s*\.\s*'\/public\/css\/programa-general\.css'\)/,
  'PG debe versionar su CSS desde el archivo real',
);
assert.match(
  view,
  /programa-general\.css\?v=<\?php echo urlencode\(\(string\) \$pgCssVersion\); \?>/,
  'PG debe publicar el filemtime en la URL del CSS',
);
assert.match(
  view,
  /\$pgGeneralJsVersion\s*=\s*@filemtime\(dirname\(__DIR__, 2\)\s*\.\s*'\/public\/js\/funcionesGenerales6\.js'\)/,
  'PG debe versionar el generador compartido de modales desde el archivo real',
);
assert.match(
  view,
  /funcionesGenerales6\.js\?v=<\?php echo urlencode\(\(string\) \$pgGeneralJsVersion\); \?>/,
  'PG debe invalidar la copia legacy del modal en navegadores existentes',
);

assert.equal(filterMarkup.length, 14, 'PG debe renderizar siete filtros para cada area del proyecto');
for (const filter of filterMarkup) {
  assert.match(filter, /aria-pressed="false"/, 'cada filtro PG debe declarar su estado inicial');
}

assert.match(
  hot,
  /\.attr\('aria-pressed',\s*function\s*\(\)\s*\{[\s\S]*?activeFilters\.indexOf/,
  'PG debe sincronizar aria-pressed con activeFilters',
);

assert.match(
  hot,
  /event\.key\s*===\s*'Enter'[\s\S]*?event\.keyCode\s*===\s*32/,
  'PG debe conservar activacion por teclado con Enter y Espacio',
);
assert.match(
  hot,
  /var lastCommittedValue = input\.value;[\s\S]*?function commitMobileInput\(\)[\s\S]*?lastCommittedValue = nextValue;/,
  'la card móvil debe evitar guardados duplicados del mismo valor',
);
assert.match(
  hot,
  /input\.addEventListener\('change', commitMobileInput\);[\s\S]*?input\.addEventListener\('blur', commitMobileInput\);/,
  'la card móvil debe guardar tanto por change como al perder foco',
);
assert.match(hot, /input\.setAttribute\('aria-label'/, 'cada input móvil debe tener nombre accesible');
assert.match(hot, /select\.setAttribute\('aria-label'/, 'cada selector móvil debe tener nombre accesible');

assert.match(
  bridgeCss,
  /#pgLegend\.pdc-legend-autoscaling \.pdc-legend-item\)\s*\{[^}]*min-height:\s*var\(--ds-target-min\)/,
  'los filtros PG deben garantizar targets de 44px',
);
assert.match(
  buttonsCss,
  /\.pdc-legend-item\s*\{[^}]*transition:\s*transform var\(--ds-motion-fast\),\s*box-shadow var\(--ds-motion-fast\) !important;/s,
  'los chips no deben interpolar color o superficie durante el cambio de tema',
);
assert.match(
  buttonsCss,
  /\.pdc-legend-item\s*\{\s*display:\s*inline-flex !important;[\s\S]*?white-space:\s*normal !important;[\s\S]*?overflow-wrap:\s*normal !important;[\s\S]*?word-break:\s*normal !important;/,
  'los chips canónicos deben envolver entre palabras sin fragmentarlas al ampliar',
);
assert.match(
  coreCss,
  /\.aia-btn\s*\{[^}]*min-height:\s*var\(--ds-target-min\)/,
  'los botones PG deben heredar el target canónico de 44px',
);

console.log('PASS: contrato accesible de filtros de Programa General');
