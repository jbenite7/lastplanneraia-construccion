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

// D-GAC-2 (2026-08-12): esto exigía `aia-chip` y `pg-filter-chip` CONTIGUAS y contaba 0 de 14
// desde 47dda844 (2026-08-04), cuando el markup adoptó `pdc-legend-item` —la clase canónica del
// chip— entre ambas. Cedió la prueba, no el markup. Se comparan tokens de clase en vez de una
// cadena literal: así el contrato sigue exigiendo las dos clases pero no el orden ni la vecindad,
// que es lo que caducaba con cada variante legítima.
const filterMarkup = [...view.matchAll(/<[^>]*class="([^"]*)"[^>]*data-filter="[^"]+"[^>]*>/g)]
  .filter(([, classAttribute]) => {
    const classes = classAttribute.trim().split(/\s+/);
    return classes.includes('aia-chip') && classes.includes('pg-filter-chip');
  })
  .map(([tag]) => tag);

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
// D-GAC-1 (2026-08-12): `!important` FUERA de toda `@layer` sigue prohibido; DENTRO se
// permite. Las reglas de estado de PG (`.is-zero`, programa-general.css:619-624) viven en
// `@layer components` precisamente porque `module.components` es una capa posterior y en
// declaraciones normales ganarían; prohibirlo sin excepción dejaba el CI en rojo desde el
// 2026-07-17 sin que hubiera defecto. Se quitan los comentarios antes de mirar, porque el
// que razona esas tres declaraciones cita la palabra fuera de la capa.
const cssOutsideLayers = (() => {
  const withoutComments = css.replace(/\/\*[\s\S]*?\*\//g, '');
  const openLayer = /@layer\b[^{;]*\{/g;
  let outside = '';
  let index = 0;
  for (;;) {
    openLayer.lastIndex = index;
    const found = openLayer.exec(withoutComments);
    if (!found) {
      outside += withoutComments.slice(index);
      return outside;
    }
    outside += withoutComments.slice(index, found.index);
    let depth = 1;
    let cursor = openLayer.lastIndex;
    while (cursor < withoutComments.length && depth > 0) {
      const char = withoutComments[cursor];
      if (char === '{') depth += 1;
      else if (char === '}') depth -= 1;
      cursor += 1;
    }
    assert.equal(depth, 0, 'la CSS de PG tiene una @layer sin cerrar: el contrato no puede leerla');
    index = cursor;
  }
})();
assert.doesNotMatch(
  cssOutsideLayers,
  /!important/,
  'PG solo puede usar !important dentro de una @layer, nunca fuera de toda capa',
);
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
// D-GAC-3 (2026-08-12): esta asercion exigia las cuatro declaraciones **con
// `!important`**, y eso es la forma, no el resultado. El objetivo declarado es
// que el chip envuelva entre palabras sin fragmentarlas, y eso lo dan los
// valores. El `!important` lo retiro a proposito `0a228a39`, que midio el
// computado de los dieciseis y repuso solo los seis que hacian trabajo: exigirlo
// aqui obligaba a deshacer una resta bien medida. Mismo defecto que `D-CI-1`.
// Que los valores **ganen** no lo puede probar una hoja de estilo: se comprueba
// en navegador, y esa medicion vive en la entrega del frente `ci-en-verde`.
const chipBlock = buttonsCss.match(/^\.pdc-legend-item\s*\{([\s\S]*?)^\}/m)?.[1] ?? '';
assert.ok(
  chipBlock.length > 0,
  'el bloque .pdc-legend-item debe existir en buttons.css',
);
for (const [prop, value] of [
  ['display', 'inline-flex'],
  ['white-space', 'normal'],
  ['overflow-wrap', 'normal'],
  ['word-break', 'normal'],
]) {
  assert.match(
    chipBlock,
    new RegExp(`(^|;|\\s)${prop}:\\s*${value}\\s*(!important)?\\s*;`, 'm'),
    `los chips canónicos deben declarar ${prop}: ${value} para envolver entre palabras sin fragmentarlas`,
  );
}
assert.match(
  coreCss,
  /\.aia-btn\s*\{[^}]*min-height:\s*var\(--ds-target-min\)/,
  'los botones PG deben heredar el target canónico de 44px',
);

console.log('PASS: contrato accesible de filtros de Programa General');
