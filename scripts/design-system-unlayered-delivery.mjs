#!/usr/bin/env node
// scripts/design-system-unlayered-delivery.mjs
//
// Gate de ENTREGAS SIN CAPA.
//
// Una hoja de estilos de autor que entra al documento sin capa gana a todas las
// capas en declaraciones normales (DS-006 invierte el orden solo para
// `!important`). El audit ya veta `css-outside-layer` DENTRO de los archivos del
// repo, pero nadie vigilaba COMO llegan las hojas al documento: un
// `<link rel="stylesheet">` crudo, un CDN o una hoja inyectada por JS derrotan al
// design system por mucho que este declare lo correcto.
//
// Este modulo cubre la superficie ESTATICA: cada `<link rel="stylesheet">`
// escrito a mano en `views/` y cada hoja que `public/js/` monta con
// `rel = 'stylesheet'`. La superficie de RUNTIME (`document.styleSheets`,
// incluidas las hojas que un vendor inyecta en un `<style>`) la cubre
// `tests/browser/design-system-unlayered-delivery.mjs`, que reutiliza
// `compareDeliveries()` de aqui contra el mismo inventario.
//
// FUERA DE ALCANCE: `admin/`. AGENTS.md excluye el panel Admin del design
// system (AdminLTE no se migra), asi que sus `<link>` crudos no son hallazgos.
// El escaneo se limita a `views/` y `public/js/` por construccion.
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

import { parseCssStructure } from './lib/css-structure-parser.mjs';

export const INVENTORY_PATH = 'docs/design-system/unlayered-delivery-inventory.json';

// La via sancionada: el componente emite el `<link>` a un entrypoint que importa
// cada vendor con `layer(vendor)` y luego su adaptador. Un `<link>` literal en
// una vista nunca pasa por aqui, y por eso se audita.
const SANCTIONED_EMITTER = 'src/View/Components/DesignSystemHeadComponent.php';

const PHP_BLOCK = /<\?(?:php|=)[\s\S]*?\?>/g;
const LINK_TAG = /<link\b[^>]*>/gi;
const JS_STYLESHEET_ASSIGNMENT = /\.rel\s*=\s*(['"])stylesheet\1/g;

function* walk(dir, extension) {
  if (!existsSync(dir)) return;
  for (const name of readdirSync(dir)) {
    const path = join(dir, name);
    if (statSync(path).isDirectory()) yield* walk(path, extension);
    else if (name.endsWith(extension)) yield path;
  }
}

/** `/css/x.css` y `/public/vendor/x.css` resuelven igual que en el componente PHP. */
export function resolveHref(href) {
  const url = href.split('?')[0].trim();
  if (!url) return { kind: 'unresolvable' };
  if (/^(?:https?:)?\/\//i.test(url)) return { kind: 'external', id: url };
  if (!url.startsWith('/')) return { kind: 'unresolvable', id: url };
  const file = (url.startsWith('/public/') ? url : `/public${url}`).slice(1);
  return { kind: 'repo', id: url, file };
}

/**
 * Cuenta reglas de estilo fuera de capa. Una hoja externa no puede llevar capa
 * desde el documento (`<link>` no admite `layer`), asi que cuenta como sin capa
 * salvo que el propio archivo remoto se envuelva — indecidible aqui, y por eso
 * el inventario la declara explicitamente.
 */
export function unlayeredRulesOf(root, resolved) {
  if (resolved.kind === 'external') return null;
  if (resolved.kind !== 'repo') return null;
  const absolute = join(root, resolved.file);
  if (!existsSync(absolute) || !statSync(absolute).isFile()) return null;
  return parseCssStructure(readFileSync(absolute, 'utf8')).filter((rule) => !rule.layer).length;
}

/** @returns {Array<{id, kind, unlayeredRules, sites: string[]}>} */
export function staticDeliveries({ root, viewsOverride = null, scriptsOverride = null }) {
  const found = new Map();
  // El sitio es el ARCHIVO, no el archivo:linea: una linea nueva mas arriba en
  // la vista no es una entrega nueva, y un gate que se pone rojo por eso deja
  // de leerse. Copiar el `<link>` a otra vista si cambia el conjunto.
  const record = (resolved, site, unlayeredRules, kind) => {
    const entry = found.get(resolved.id)
      ?? { id: resolved.id, kind, unlayeredRules, sites: [] };
    if (!entry.sites.includes(site)) entry.sites.push(site);
    found.set(resolved.id, entry);
  };

  const views = viewsOverride ?? [...walk(join(root, 'views'), '.php')].map((path) => ({
    file: path.slice(root.length + 1),
    content: readFileSync(path, 'utf8'),
  }));
  for (const { file, content } of views) {
    // El PHP interpolado dentro de un atributo (p. ej. `?v=<?= filemtime(...) ?>`)
    // lleva un `>` que truncaria el match del tag; se neutraliza antes de parsear.
    const flat = content.replace(PHP_BLOCK, (block) => ' '.repeat(block.length));
    for (const match of flat.matchAll(LINK_TAG)) {
      const tag = match[0];
      if (!/\brel\s*=\s*["']stylesheet["']/i.test(tag)) continue;
      const href = tag.match(/\bhref\s*=\s*["']([^"']*)["']/i)?.[1] ?? '';
      const resolved = resolveHref(href);
      if (resolved.kind === 'unresolvable') continue;
      const unlayered = unlayeredRulesOf(root, resolved);
      const site = file;
      // Externa: sin capa por construccion. Repo: solo si aporta reglas sueltas.
      if (resolved.kind === 'external') record(resolved, site, null, 'view-link-external');
      else if (unlayered > 0) record(resolved, site, unlayered, 'view-link');
    }
  }

  const scripts = scriptsOverride ?? [...walk(join(root, 'public/js'), '.js')].map((path) => ({
    file: path.slice(root.length + 1),
    content: readFileSync(path, 'utf8'),
  }));
  for (const { file, content } of scripts) {
    for (const match of content.matchAll(JS_STYLESHEET_ASSIGNMENT)) {
      // El href literal vive en la asignacion vecina del mismo bloque.
      const window = content.slice(Math.max(0, match.index - 400), match.index + 400);
      const href = window.match(/\.href\s*=\s*(['"])([^'"]+)\1/)?.[2] ?? '';
      const resolved = resolveHref(href);
      const site = file;
      if (resolved.kind === 'unresolvable') {
        record({ id: `${file}: href no literal` }, site, null, 'js-link-opaque');
        continue;
      }
      const unlayered = unlayeredRulesOf(root, resolved);
      if (resolved.kind === 'external') record(resolved, site, null, 'js-link-external');
      else if (unlayered > 0) record(resolved, site, unlayered, 'js-link');
    }
  }

  return [...found.values()].sort((a, b) => a.id.localeCompare(b.id));
}

/**
 * Contraste inventario <-> realidad. Falla en los dos sentidos: una entrega no
 * declarada es una regresion, y una declarada que ya no existe es inventario
 * podrido que hay que borrar para que el gate siga significando algo.
 */
export function compareDeliveries({ scope, observed, declared }) {
  const failures = [];
  const observedIds = new Set(observed);
  const declaredIds = new Set(declared);
  for (const id of [...observedIds].sort()) {
    if (!declaredIds.has(id)) failures.push(`undeclared-unlayered-delivery: ${scope} -> ${id}`);
  }
  for (const id of [...declaredIds].sort()) {
    if (!observedIds.has(id)) failures.push(`stale-inventory-entry: ${scope} -> ${id}`);
  }
  return failures;
}

export function readInventory(root) {
  return JSON.parse(readFileSync(join(root, INVENTORY_PATH), 'utf8'));
}

export function staticFailures({ root, inventoryOverride = null, ...overrides }) {
  const failures = [];
  if (!existsSync(join(root, SANCTIONED_EMITTER))) {
    failures.push(`missing-sanctioned-emitter: ${SANCTIONED_EMITTER}`);
  }
  const inventory = inventoryOverride ?? readInventory(root);
  const observed = staticDeliveries({ root, ...overrides });
  failures.push(...compareDeliveries({
    scope: 'static',
    observed: observed.map(({ id }) => id),
    declared: (inventory.static ?? []).map(({ sheet }) => sheet),
  }));

  // Cada entrega declarada debe seguir apareciendo en los mismos sitios: si
  // alguien copia el `<link>` crudo a una vista mas, el gate lo canta.
  const declaredSites = new Map((inventory.static ?? []).map(({ sheet, sites }) => [sheet, sites ?? []]));
  for (const entry of observed) {
    const expected = declaredSites.get(entry.id);
    if (!expected) continue;
    const added = entry.sites.filter((site) => !expected.includes(site));
    const removed = expected.filter((site) => !entry.sites.includes(site));
    for (const site of added) failures.push(`undeclared-delivery-site: ${entry.id} @ ${site}`);
    for (const site of removed) failures.push(`stale-delivery-site: ${entry.id} @ ${site}`);
  }

  return { failures, observed };
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const root = process.cwd();
  if (process.argv.includes('--report')) {
    console.log(JSON.stringify(staticDeliveries({ root }), null, 2));
  } else {
    const { failures, observed } = staticFailures({ root });
    if (failures.length) {
      console.error('Design system unlayered delivery (static): FAIL');
      failures.forEach((failure) => console.error(`- ${failure}`));
      console.error(`\nActualiza ${INVENTORY_PATH} solo si la entrega sin capa es`);
      console.error('deliberada y revisada; la salida por defecto es eliminarla.');
      process.exitCode = 1;
    } else {
      console.log(`Design system unlayered delivery (static): PASS (${observed.length} entrega/s declarada/s)`);
    }
  }
}
