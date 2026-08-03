import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const REPO_ROOT = fileURLToPath(new URL('../../', import.meta.url));
const INVENTORY = join(REPO_ROOT, 'docs/design-system/state-tint-exceptions.json');

// El canal que este guard vigila NO es el de `state-token-pairing.test.mjs`.
// Aquel empareja dos tokens hermanos -`--ds-color-state-X-bg` con su `-text`- y
// puede comprobar que la familia coincide. La escalera de matices es de SOLO
// FONDO: no existe `--ds-state-tint-X-text` y se decidio no crearlo, porque los
// modulos ya deciden la tinta con sus propios pares y un tercer sitio donde
// elegir color de letra no lo usaria nadie.
//
// Por eso aqui la regla es mas floja en el QUE y mas estricta en el DONDE: no
// se exige un token concreto, se exige que ALGUNA tinta quede declarada para el
// elemento que recibe el fondo. Un fondo de matiz sin tinta declarada deja el
// texto a lo que herede, que es exactamente el defecto que la escalera arrastra
// -y en las celdas de Handsontable lo que hereda lo decide la libreria, no el
// sistema de diseno-.

// Hojas fuente. Queda FUERA `public/pdc-app/`, que es bundle generado: un rojo
// ahi no se arregla editando ese archivo sino recompilando, asi que el guard
// apuntaria al sitio equivocado. Tampoco se recorren `views/`, `src/` ni
// `public/js`: a diferencia de los tokens de estado, el matiz no aparece en
// atributos ni en `style=` en ningun punto del arbol -medido, no supuesto, y el
// propio guard lo vuelve a comprobar mas abajo-.
const CSS_ROOTS = ['public/css', 'admin/public/css', 'pdc-app/src'];
const NON_CSS_ROOTS = ['views', 'admin/views', 'src', 'public/js'];

const TINT_RE = /--ds-state-tint-[a-z0-9-]+/;
const BG_WITH_TINT_RE = /background[^;:]*:\s*[^;]*(--ds-state-tint-[a-z0-9-]+)/g;
const COLOR_RE = /(?<![-\w])color\s*:/;

// Mismo criterio de clasificacion y mismo piso de razon que el inventario
// hermano, para que quien lea uno sepa leer el otro sin aprender un formato
// nuevo. `by-design` sobrevive tal cual; los otros dos exigen `revisit` para
// que un aplazamiento no pueda disfrazarse de intencion.
const KINDS = new Set(['by-design', 'at-risk', 'out-of-scope-mobile']);
const KINDS_NEEDING_REVISIT = new Set(['at-risk', 'out-of-scope-mobile']);
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

// Un `/* ... */` puede citar un token o llevar una declaracion comentada; se
// blanquea conservando los saltos para que los numeros de linea no se muevan.
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

// Solo casan los bloques SIN llaves dentro, o sea las reglas de verdad: un
// `@layer` o un `@media` envuelven otras reglas y no declaran nada por si
// mismos. El selector es el texto que va desde el limite de bloque anterior
// hasta la llave de apertura.
function rules(clean) {
  const out = [];
  let cursor = 0;
  for (const match of clean.matchAll(/\{([^{}]*)\}/g)) {
    const head = clean.slice(cursor, match.index);
    const selector = head.slice(Math.max(head.lastIndexOf('}'), head.lastIndexOf('{')) + 1).trim();
    out.push({ selector, body: match[1], bodyIndex: match.index + 1 });
    cursor = match.index + match[0].length;
  }
  return out;
}

function selectorParts(selector) {
  return selector
    .split(',')
    .map((part) => part.trim())
    .filter((part) => part && !part.startsWith('@'));
}

// El par vale si lo declara OTRA regla del mismo archivo cuyo selector incluye
// este mismo selector. Se acepto a proposito -`programa-general-actualizar.css`
// ya lo hace y es correcto-: exigir la misma regla mandaria al inventario un
// caso sano, y un archivo de excepciones lleno de casos sanos deja de leerse.
//
// LIMITE CONOCIDO, y por eso este guard no sustituye a la medicion: es una
// comprobacion de TEXTO. No sabe de orden de capas, de especificidad ni de
// `!important`, asi que ve un par que la cascada podria estar pisando. Lo que
// confirma que la tinta llega al pixel es medirla en el navegador.
function pairedElsewhere(allRules, ownParts) {
  return allRules.some(
    (rule) =>
      COLOR_RE.test(rule.body) &&
      selectorParts(rule.selector).some((part) => ownParts.includes(part)),
  );
}

function unpairedUses(css) {
  const clean = stripComments(css);
  const lineAt = lineCounter(clean);
  const all = rules(clean);
  const found = [];
  for (const rule of all) {
    if (COLOR_RE.test(rule.body)) continue;
    const parts = selectorParts(rule.selector);
    for (const m of rule.body.matchAll(BG_WITH_TINT_RE)) {
      if (pairedElsewhere(all, parts)) break;
      found.push({
        token: m[1],
        selector: parts.join(', '),
        line: lineAt(rule.bodyIndex + m.index),
      });
    }
  }
  return found;
}

function key(entry) {
  return `${entry.file}|${entry.token}|${entry.line}`;
}

async function collect() {
  const found = [];
  for (const root of CSS_ROOTS) {
    for (const file of await walk(join(REPO_ROOT, root), (name) => name.endsWith('.css'))) {
      const css = await readFile(file, 'utf8');
      if (!TINT_RE.test(css)) continue;
      const rel = relative(REPO_ROOT, file);
      for (const use of unpairedUses(css)) found.push({ file: rel, ...use });
    }
  }
  return found;
}

async function readInventory() {
  return JSON.parse(await readFile(INVENTORY, 'utf8'));
}

test('ninguna regla pinta un fondo de matiz sin declarar la tinta', async () => {
  const found = await collect();
  const inventory = await readInventory();
  const excused = new Set(inventory.entries.map(key));
  const offenders = found.filter((entry) => !excused.has(key(entry)));
  assert.deepEqual(
    offenders.map((e) => `${e.file}:${e.line} ${e.selector} -> ${e.token}`),
    [],
    'fondo de matiz sin tinta declarada y sin inventariar',
  );
});

// El inventario no puede acumular entradas de reglas que ya se arreglaron: una
// excusa que sobrevive a su caso pasa a excusar en silencio lo que venga
// despues con esa misma clave.
test('el inventario no excusa nada que ya este emparejado', async () => {
  const found = new Set((await collect()).map(key));
  const inventory = await readInventory();
  const stale = inventory.entries.filter((entry) => !found.has(key(entry)));
  assert.deepEqual(
    stale.map((e) => `${e.file}:${e.line} ${e.token}`),
    [],
    'entradas del inventario que ya no corresponden a ningun uso descompensado',
  );
});

test('cada entrada del inventario esta bien formada y localizable', async () => {
  const inventory = await readInventory();
  for (const entry of inventory.entries) {
    const where = `${entry.file}:${entry.line}`;
    assert.ok(KINDS.has(entry.kind), `${where}: \`kind\` desconocido: ${entry.kind}`);
    assert.ok(
      typeof entry.reason === 'string' && entry.reason.length >= MIN_REASON,
      `${where}: \`reason\` de menos de ${MIN_REASON} caracteres`,
    );
    if (KINDS_NEEDING_REVISIT.has(entry.kind)) {
      assert.ok(entry.revisit, `${where}: \`kind\` ${entry.kind} exige \`revisit\``);
    }
    const css = await readFile(join(REPO_ROOT, entry.file), 'utf8');
    assert.ok(
      css.includes(entry.selector.split(',')[0].trim()),
      `${where}: \`selector\` no aparece literal en la hoja: ${entry.selector}`,
    );
  }
});

// La razon por la que este guard no recorre `views/`, `src/` ni `public/js` es
// que el matiz no viaja por ahi. Eso es una MEDICION, no una suposicion, y
// caduca sola: si algun dia alguien mete un tinte en un `style=` o en una
// opcion de JS, el alcance recortado lo dejaria pasar, asi que se comprueba.
test('el matiz no se usa fuera de las hojas de estilo', async () => {
  const strays = [];
  for (const root of NON_CSS_ROOTS) {
    for (const file of await walk(
      join(REPO_ROOT, root),
      (name) => name.endsWith('.php') || name.endsWith('.js') || name.endsWith('.mjs'),
    )) {
      const source = await readFile(file, 'utf8');
      if (TINT_RE.test(source)) strays.push(relative(REPO_ROOT, file));
    }
  }
  assert.deepEqual(
    strays,
    [],
    'el matiz aparece fuera de CSS; el alcance de este guard se queda corto y hay que ampliarlo',
  );
});
