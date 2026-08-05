#!/usr/bin/env node
// scripts/design-system-bi-utilities.mjs
//
// Gate ANTI-PODREDUMBRE del adaptador de utilidades de /bi/*.
//
// Hasta el caso 9-tailwind-cdn-bi, `views/bi/_layout.php` cargaba el Play CDN de
// Tailwind: un `<script>` que compilaba en el navegador y generaba al vuelo
// cualquier clase que alguien escribiera. Costaba cero anadir `mt-8` a una vista
// y funcionaba solo. Ese CDN se retiro —entregaba su CSS sin capa y derrotaba al
// design system entero— y las 97 utilidades que de verdad se usaban viven ahora
// en `public/css/design-system/adapters/bi-utilities.css`, congeladas.
//
// El riesgo nuevo es el silencio: si alguien escribe `mt-8`, la clase no existe
// en la hoja, no hay compilador que la genere y NO HAY ERROR. Simplemente no
// pinta. Este gate es lo que convierte ese silencio en un rojo.
//
// Falla en los dos sentidos, como el resto de gates del design system:
//   - `undeclared-bi-utility`: la superficie usa una utilidad que la hoja no
//     declara. Es la regresion que motiva el gate.
//   - `unused-bi-utility`: la hoja declara una utilidad que ya no usa nadie.
//     Una hoja que crece sin consumidores deja de significar algo.
//
// La salida por defecto ante un `undeclared` NO es anadir la regla a la hoja: es
// preguntarse si la superficie deberia usar una primitiva `aia-*`. El adaptador
// es una capa de compatibilidad para poder cerrar una violacion de capas sin
// bloquearse en migrar 252 sitios de edicion, no un destino.
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

import { parseCssStructure } from './lib/css-structure-parser.mjs';

export const SHEET = 'public/css/design-system/adapters/bi-utilities.css';
export const VIEWS_DIR = 'views/bi';
export const SCRIPT = 'public/js/modules/bi-spa.js';

const PHP_BLOCK = /<\?(?:php|=)[\s\S]*?\?>/g;
const CLASS_ATTR = /\bclass\s*=\s*(["'])([\s\S]*?)\1/g;
// `classList.add('a','b')`, `.className = 'a b'` y cualquier string literal:
// las clases que `statusBadgeClass()` y `renderProgressBadge()` ensamblan viven
// en un valor de RETORNO, no en un `class=`, y asi fue como `.inline-flex`,
// `.px-2` y `.py-0.5` llegaron a la superficie sin figurar en ningun markup.
const JS_STRING = /(['"`])((?:\\.|(?!\1)[^\\])*)\1/g;
// `document.createElement('table')` no declara una clase, declara una etiqueta.
// Sin esta excepcion, `table` (que TAMBIEN es una utilidad de Tailwind:
// `display: table`) entra al censo por construir un nodo, y el gate exige
// declarar en la hoja una regla que nadie usa.
const CREATE_ELEMENT_CALL = /createElement\(\s*$/;

/**
 * Formas de utilidad de Tailwind. Se lista lo que TIENE forma de utilidad en vez
 * de intentar excluir el vocabulario propio: `bi-*`, `aia-*`, `card`,
 * `nav-item`, `view-section`, `shell-*` y compania no se parecen a ninguna de
 * estas, y asi una familia nueva de Tailwind cae del lado seguro (se reporta)
 * en vez de colarse.
 */
const UTILITY_SHAPES = [
  /^-?[mp][trblxyse]?-/,
  /^(?:w|h|size|min-w|min-h|max-w|max-h)-/,
  /^(?:flex|grid|gap|justify|items|content|self|place|order|col|row|basis|grow|shrink|space)-/,
  /^(?:top|right|bottom|left|inset|z|float|clear)-/,
  /^(?:text|font|leading|tracking|whitespace|break|indent|align|list|decoration|underline)-/,
  /^(?:bg|border|divide|ring|outline|shadow|opacity|from|to|via|accent|fill|stroke|caret)-/,
  // El guion es obligatorio en estas familias: `object`, `delay`, `scale` y
  // compania son palabras corrientes en JS (`typeof x === 'object'`) y sin el
  // guion el gate se llenaba de falsos positivos.
  /^(?:overflow|object|aspect|columns|box|cursor|select|resize|appearance|touch|will-change|mix-blend|backdrop|duration|ease|delay|animate|translate|rotate|scale|skew|origin|pointer-events)-/,
  /^(?:rounded|transform|transition|isolate|table)(?:-|$)/,
  /^(?:sr|not-sr)-only$/,
  /^(?:flex|grid|block|inline|inline-block|inline-flex|inline-grid|hidden|contents|flow-root)$/,
  /^(?:static|fixed|absolute|relative|sticky)$/,
  /^(?:truncate|uppercase|lowercase|capitalize|normal-case|italic|not-italic|antialiased|subpixel-antialiased|visible|invisible|collapse)$/,
];

// Variantes (`md:`, `hover:`, `dark:`, `group-hover:`…). Se conserva el prefijo
// al comparar contra la hoja —`md:grid-cols-2` y `grid-cols-2` son reglas
// distintas— pero la forma se decide sobre la utilidad base.
const VARIANT = /^(?:[a-z0-9][\w-]*:)+/;

export function isUtilityShaped(token) {
  if (!token || /[^\w:[\]/.%#-]/.test(token)) return false;
  const base = token.replace(VARIANT, '');
  if (!base) return false;
  return UTILITY_SHAPES.some((shape) => shape.test(base));
}

/**
 * Los selectores de `@layer utilities`, desescapados: `.py-0\.5` -> `py-0.5`.
 *
 * Solo esa capa: el bloque `@layer base` de la hoja normaliza encabezados y
 * botones de la superficie, y `.bi-control-tower-page` no es una utilidad.
 *
 * Se admite lo que siga a la clase raiz (`.space-y-1 > :not([hidden]) ~ ...`):
 * la utilidad se llama `space-y-1` aunque su regla necesite un combinador.
 */
export function declaredUtilities({ root, sheetOverride = null }) {
  const content = sheetOverride ?? readFileSync(join(root, SHEET), 'utf8');
  const declared = new Set();
  for (const rule of parseCssStructure(content)) {
    if (rule.layer !== 'utilities') continue;
    for (const selector of rule.selector.split(',')) {
      const root_ = selector.trim().match(/^\.((?:\\.|[\w-])+)(?:[\s>+~[:]|$)/);
      if (root_) declared.add(root_[1].replace(/\\(.)/g, '$1'));
    }
  }
  return declared;
}

function* walk(dir, extension) {
  if (!existsSync(dir)) return;
  for (const name of readdirSync(dir)) {
    const path = join(dir, name);
    if (statSync(path).isDirectory()) yield* walk(path, extension);
    else if (name.endsWith(extension)) yield path;
  }
}

export function readSurface(root) {
  const views = [...walk(join(root, VIEWS_DIR), '.php')].map((path) => ({
    file: path.slice(root.length + 1),
    content: readFileSync(path, 'utf8'),
  }));
  const scriptPath = join(root, SCRIPT);
  const scripts = existsSync(scriptPath)
    ? [{ file: SCRIPT, content: readFileSync(scriptPath, 'utf8') }]
    : [];
  return { views, scripts };
}

/** @returns {Map<string, string[]>} token -> archivos donde aparece */
export function usedClassTokens({ views, scripts }) {
  const used = new Map();
  const record = (token, file) => {
    if (!token) return;
    const sites = used.get(token) ?? [];
    if (!sites.includes(file)) sites.push(file);
    used.set(token, sites);
  };

  for (const { file, content } of views) {
    // El PHP interpolado dentro del propio atributo (los ternarios de
    // `_nav.php`) se neutraliza antes de partir por espacios; si no, `?` y `:`
    // entran al censo como si fueran clases.
    const flat = content.replace(PHP_BLOCK, ' ');
    for (const match of flat.matchAll(CLASS_ATTR)) {
      for (const token of match[2].split(/\s+/)) record(token.trim(), file);
    }
  }

  for (const { file, content } of scripts) {
    for (const match of content.matchAll(JS_STRING)) {
      if (CREATE_ELEMENT_CALL.test(content.slice(Math.max(0, match.index - 20), match.index))) continue;
      // Las plantillas de `bi-spa.js` llevan las comillas del markup
      // escapadas (`class=\"w-3 h-3\"`). Si no se deshacen, la barra queda
      // pegada al token y `h-3\` deja de reconocerse: asi es como `.h-3` se
      // caia del censo aunque el JIT si la generaba.
      const value = match[2].replace(/\\(["'`])/g, ' ');
      for (const token of value.split(/[\s"'`<>=]+/)) {
        const clean = token.trim();
        if (clean && isUtilityShaped(clean)) record(clean, file);
      }
    }
    for (const match of content.matchAll(CLASS_ATTR)) {
      for (const token of match[2].split(/\s+/)) record(token.trim(), file);
    }
  }
  return used;
}

export function biUtilityFailures({
  root, sheetOverride = null, viewsOverride = null, scriptsOverride = null,
}) {
  const failures = [];
  const declared = declaredUtilities({ root, sheetOverride });
  const surface = readSurface(root);
  const views = viewsOverride ?? surface.views;
  const scripts = scriptsOverride ?? surface.scripts;
  const used = usedClassTokens({ views, scripts });

  for (const [token, sites] of [...used].sort(([a], [b]) => a.localeCompare(b))) {
    if (!isUtilityShaped(token) || declared.has(token)) continue;
    failures.push(`undeclared-bi-utility: ${token} @ ${sites.join(', ')}`);
  }
  for (const token of [...declared].sort()) {
    if (!used.has(token)) failures.push(`unused-bi-utility: ${token}`);
  }
  return failures;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const root = process.cwd();
  const failures = biUtilityFailures({ root });
  if (failures.length) {
    console.error('Design system BI utilities: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    console.error(`\nLa hoja ${SHEET} ya no describe`);
    console.error('a su superficie. Antes de anadirle una regla, comprueba si /bi/* deberia');
    console.error('usar una primitiva `aia-*`: el adaptador es compatibilidad, no un destino.');
    process.exit(1);
  }
  console.log(`Design system BI utilities: PASS (${declaredUtilities({ root }).size} utilidades declaradas y en uso)`);
}
