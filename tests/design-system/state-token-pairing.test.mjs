import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { extname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const REPO_ROOT = fileURLToPath(new URL('../../', import.meta.url));
const CSS_ROOT = join(REPO_ROOT, 'public/css');
const INVENTORY = join(REPO_ROOT, 'docs/design-system/state-token-exceptions.json');

// Fuera de las hojas el token entra por un atributo, un `style=` o una opcion de
// JS: no hay bloque donde declarar la otra mitad, asi que cualquier aparicion es
// por definicion medio par y tiene que estar inventariada igual.
const INLINE_ROOTS = ['views', 'admin/views', 'src', 'public/js'];
const INLINE_EXTS = new Set(['.php', '.js']);

// El inventario no clasifica por longitud de texto sino por tipo: `by-design`
// sobrevive la inversion tal cual, `at-risk` no -o ya esta mal hoy- y hay que
// decidirlo, y `out-of-scope-mobile` es deuda que esta sesion tiene prohibido
// tocar. Los dos ultimos exigen `revisit` para que un aplazamiento no pueda
// disfrazarse de intencion de diseno.
const KINDS = new Set(['by-design', 'at-risk', 'out-of-scope-mobile']);
const KINDS_NEEDING_REVISIT = new Set(['at-risk', 'out-of-scope-mobile']);

// Las razones reales de este inventario miden entre 130 y 280 caracteres. El
// umbral no mide calidad -ningun numero lo hace-, solo descarta el marcador de
// posicion; quien clasifica de verdad es `kind` mas la verificacion de que
// `selector` y `line` apuntan a codigo que existe.
const MIN_REASON = 80;

async function walk(dir, keep) {
  const found = [];
  let entries;
  try {
    entries = await readdir(dir, { withFileTypes: true });
  } catch {
    return found;
  }
  for (const entry of entries) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) {
      if (entry.name === 'node_modules' || entry.name === 'vendor') continue;
      found.push(...(await walk(full, keep)));
    } else if (keep(entry.name)) {
      found.push(full);
    }
  }
  return found;
}

// Un `/* ... */` puede contener el nombre de un token o una declaracion comentada;
// se blanquea conservando los saltos de linea para que los numeros no se muevan.
function stripComments(css) {
  return css.replace(/\/\*[\s\S]*?\*\//g, (m) => m.replace(/[^\n]/g, ' '));
}

function lineCounter(source) {
  const offsets = [0];
  for (let i = 0; i < source.length; i += 1) if (source[i] === '\n') offsets.push(i + 1);
  return (index) => {
    let lo = 0;
    let hi = offsets.length - 1;
    while (lo < hi) {
      const mid = (lo + hi + 1) >> 1;
      if (offsets[mid] <= index) lo = mid;
      else hi = mid - 1;
    }
    return lo + 1;
  };
}

const BG_RE = /background[^;:]*:\s*[^;]*--ds-color-state-(\w+)-bg/g;
// `(?<![-\w])` es la parte que importa: sin ella el lookbehind solo bloquea el
// literal `backgroundcolor:`, que no es CSS, y dejan pasar `background-color:`,
// `border-color:`, `outline-color:` y `-webkit-text-fill-color:`. Aqui solo entra
// la propiedad `color` de verdad.
const TEXT_RE = /(?<![-\w])color\s*:\s*[^;]*--ds-color-state-(\w+)-text/g;
const ANY_STATE_RE = /--ds-color-state-\w+-(?:bg|text)/g;

// Se recorre bloque a bloque `{ ... }` porque la pareja tiene sentido dentro de
// una misma regla: un `-bg` en una regla y su `-text` en otra no garantiza que
// se apliquen al mismo elemento.
function unpairedUses(css) {
  const clean = stripComments(css);
  const lineAt = lineCounter(clean);
  const found = [];
  for (const match of clean.matchAll(/\{([^{}]*)\}/g)) {
    const block = match[1];
    const base = match.index + 1;
    const bg = new Map();
    const text = new Map();
    for (const m of block.matchAll(BG_RE)) if (!bg.has(m[1])) bg.set(m[1], base + m.index);
    for (const m of block.matchAll(TEXT_RE)) if (!text.has(m[1])) text.set(m[1], base + m.index);
    for (const [family, index] of bg) {
      if (!text.has(family)) found.push({ token: `--ds-color-state-${family}-bg`, line: lineAt(index) });
    }
    for (const [family, index] of text) {
      if (!bg.has(family)) found.push({ token: `--ds-color-state-${family}-text`, line: lineAt(index) });
    }
  }
  return found;
}

function inlineUses(source) {
  const clean = source;
  const lineAt = lineCounter(clean);
  return [...clean.matchAll(ANY_STATE_RE)].map((m) => ({ token: m[0], line: lineAt(m.index) }));
}

function key(entry) {
  return `${entry.file}|${entry.token}|${entry.line}`;
}

async function collectFound() {
  const found = [];
  for (const file of (await walk(CSS_ROOT, (name) => name.endsWith('.css'))).sort()) {
    const rel = `public/css/${relative(CSS_ROOT, file)}`;
    for (const use of unpairedUses(await readFile(file, 'utf8'))) found.push({ file: rel, ...use });
  }
  for (const root of INLINE_ROOTS) {
    const files = await walk(join(REPO_ROOT, root), (name) => INLINE_EXTS.has(extname(name)));
    for (const file of files.sort()) {
      const rel = relative(REPO_ROOT, file);
      for (const use of inlineUses(await readFile(file, 'utf8'))) found.push({ file: rel, ...use });
    }
  }
  return found;
}

async function loadInventory() {
  return JSON.parse(await readFile(INVENTORY, 'utf8'));
}

test('el inventario coincide exactamente con los usos descompensados del arbol', async () => {
  const inventory = await loadInventory();
  const found = await collectFound();

  const cssFiles = await walk(CSS_ROOT, (name) => name.endsWith('.css'));
  assert.ok(cssFiles.length > 20, `se esperaban mas de 20 hojas y se encontraron ${cssFiles.length}`);

  const declared = new Map();
  for (const entry of inventory.exceptions) {
    const k = key(entry);
    assert.ok(!declared.has(k), `entrada duplicada en el inventario: ${k}`);
    declared.set(k, entry);
  }

  const foundKeys = new Set(found.map(key));
  // Declarar de menos deja pasar una regresion; declarar de mas -una entrada
  // fantasma sobre codigo que ya esta pareado, o que se movio- deja un hueco
  // libre donde la proxima regresion entraria en verde. Las dos fallan.
  const sinDeclarar = found.filter((use) => !declared.has(key(use))).map(key).sort();
  const fantasma = [...declared.keys()].filter((k) => !foundKeys.has(k)).sort();

  assert.deepEqual(sinDeclarar, [], `usos descompensados sin declarar:\n  ${sinDeclarar.join('\n  ')}`);
  assert.deepEqual(fantasma, [], `entradas del inventario que ya no corresponden a ningun uso descompensado:\n  ${fantasma.join('\n  ')}`);
});

test('cada entrada del inventario esta bien formada y localizable', async () => {
  const inventory = await loadInventory();
  assert.ok(inventory.version, 'el inventario necesita `version`');
  assert.ok(Array.isArray(inventory.exceptions) && inventory.exceptions.length > 0, 'el inventario esta vacio');

  const cache = new Map();
  const readOnce = async (rel) => {
    if (!cache.has(rel)) cache.set(rel, await readFile(join(REPO_ROOT, rel), 'utf8'));
    return cache.get(rel);
  };

  for (const entry of inventory.exceptions) {
    const where = `${entry.file}:${entry.line} ${entry.token}`;
    assert.ok(typeof entry.file === 'string' && entry.file.length > 0, `${where}: falta \`file\``);
    assert.ok(Number.isInteger(entry.line) && entry.line > 0, `${where}: \`line\` tiene que ser un entero positivo`);
    assert.ok(/^--ds-color-state-\w+-(bg|text)$/.test(entry.token ?? ''), `${where}: \`token\` no es un token de estado`);
    assert.ok(KINDS.has(entry.kind), `${where}: \`kind\` tiene que ser uno de ${[...KINDS].join(', ')}`);
    assert.ok(
      typeof entry.reason === 'string' && entry.reason.length >= MIN_REASON,
      `${where}: la razon necesita al menos ${MIN_REASON} caracteres y tiene ${entry.reason?.length ?? 0}`,
    );
    if (KINDS_NEEDING_REVISIT.has(entry.kind)) {
      assert.ok(
        typeof entry.revisit === 'string' && entry.revisit.length >= MIN_REASON,
        `${where}: \`kind: ${entry.kind}\` es un aplazamiento y necesita \`revisit\` diciendo que lo desbloquea`,
      );
    }

    const source = await readOnce(entry.file);
    const lines = source.split('\n');
    assert.ok(entry.line <= lines.length, `${where}: la linea no existe en el archivo`);
    assert.ok(
      lines[entry.line - 1].includes(entry.token),
      `${where}: la linea declarada no contiene el token, dice: ${lines[entry.line - 1].trim()}`,
    );

    assert.ok(typeof entry.selector === 'string' && entry.selector.length > 0, `${where}: falta \`selector\``);
    if (entry.file.endsWith('.css')) {
      // El `selector` tiene que ser selector de verdad: si es prosa, una regla
      // entera o un multi-selector truncado, no aparece literal en la hoja.
      const flat = (text) => text.replace(/\s+/g, ' ').trim();
      assert.ok(
        flat(stripComments(source)).includes(flat(entry.selector)),
        `${where}: \`selector\` no aparece literal en la hoja: ${entry.selector}`,
      );
    }
  }
});

test('el escaner bloque a bloque ve todos los usos de token de estado', async () => {
  // `\{([^{}]*)\}` no puede entrar en una regla que contenga otra anidada. Hoy
  // `public/css` no usa nesting nativo; el dia que lo use, los usos de esa regla
  // desaparecerian del escaner en silencio. Esta comprobacion lo convierte en rojo.
  const ciegos = [];
  for (const file of (await walk(CSS_ROOT, (name) => name.endsWith('.css'))).sort()) {
    const clean = stripComments(await readFile(file, 'utf8'));
    const total = (clean.match(ANY_STATE_RE) ?? []).length;
    let visto = 0;
    for (const match of clean.matchAll(/\{([^{}]*)\}/g)) {
      visto += (match[1].match(ANY_STATE_RE) ?? []).length;
    }
    if (visto !== total) {
      ciegos.push(`public/css/${relative(CSS_ROOT, file)}: ${total - visto} usos fuera del alcance del escaner`);
    }
  }
  assert.deepEqual(ciegos, [], `el escaner deja usos sin ver (nesting nativo?):\n  ${ciegos.join('\n  ')}`);
});
