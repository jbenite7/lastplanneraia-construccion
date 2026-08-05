import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import {
  flat,
  parseAtoms,
  resolveCssException,
  signatureKey,
  stripComments,
} from '../../scripts/design-system/state-token-locator.mjs';

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

// El troceo en reglas (`parseAtoms`) y el blanqueo de comentarios
// (`stripComments`) se comparten con el inventario hermano de tokens de estado
// en `scripts/design-system/state-token-locator.mjs`: los dos guards anclan por
// firma sobre el mismo modelo de "atomo" selector+bloque, y tener una sola
// implementacion evita que uno de los dos derive sin que nadie lo note.

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
function pairedElsewhere(allAtoms, ownParts) {
  return allAtoms.some(
    (atom) =>
      COLOR_RE.test(atom.block) &&
      selectorParts(atom.selector).some((part) => ownParts.includes(part)),
  );
}

// Igual que el inventario hermano: el ancla NO es un numero de linea sino la
// firma selector+token(+occurrence). `occurrence` es el n-esimo par
// (selector, token) identico visto en orden de aparicion dentro del archivo, y
// ese contador solo depende del ORDEN relativo de las reglas, asi que una
// insercion en cualquier punto anterior no lo mueve. Ver
// memoria/trampas/anclas-por-linea-se-desalinean.
function unpairedUsesBySignature(css) {
  const atoms = parseAtoms(stripComments(css));
  const seen = new Map();
  const found = [];
  const nextOccurrence = (selector, token) => {
    const k = `${selector} ${token}`;
    const n = (seen.get(k) ?? 0) + 1;
    seen.set(k, n);
    return n;
  };
  for (const atom of atoms) {
    if (COLOR_RE.test(atom.block)) continue;
    const parts = selectorParts(atom.selector);
    for (const m of atom.block.matchAll(BG_WITH_TINT_RE)) {
      if (pairedElsewhere(atoms, parts)) break;
      found.push({ token: m[1], selector: atom.selector, occurrence: nextOccurrence(atom.selector, m[1]) });
    }
  }
  return found;
}

function key(entry) {
  return signatureKey({
    file: entry.file,
    selector: flat(entry.selector),
    token: entry.token,
    occurrence: entry.occurrence ?? 1,
  });
}

async function collect() {
  const found = [];
  for (const root of CSS_ROOTS) {
    for (const file of await walk(join(REPO_ROOT, root), (name) => name.endsWith('.css'))) {
      const css = await readFile(file, 'utf8');
      if (!TINT_RE.test(css)) continue;
      const rel = relative(REPO_ROOT, file);
      for (const use of unpairedUsesBySignature(css)) found.push({ file: rel, ...use });
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
    offenders.map((e) => `${e.file}#${e.occurrence} ${e.selector} -> ${e.token}`),
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
    stale.map((e) => `${e.file}#${e.occurrence ?? 1} ${e.token}`),
    [],
    'entradas del inventario que ya no corresponden a ningun uso descompensado',
  );
});

test('cada entrada del inventario esta bien formada y localizable', async () => {
  const inventory = await readInventory();
  assert.ok(inventory.version, 'el inventario necesita `version`');
  assert.ok(Array.isArray(inventory.entries) && inventory.entries.length > 0, 'el inventario esta vacio');

  for (const entry of inventory.entries) {
    const where = `${entry.file}#${entry.occurrence ?? 1} ${entry.token}`;
    assert.ok(
      entry.occurrence === undefined || (Number.isInteger(entry.occurrence) && entry.occurrence > 0),
      `${where}: \`occurrence\`, si esta, tiene que ser un entero positivo`,
    );
    assert.ok(!('line' in entry), `${where}: \`line\` es un ancla retirada; el ancla es selector+token(+occurrence)`);
    assert.ok(KINDS.has(entry.kind), `${where}: \`kind\` desconocido: ${entry.kind}`);
    assert.ok(
      typeof entry.reason === 'string' && entry.reason.length >= MIN_REASON,
      `${where}: \`reason\` de menos de ${MIN_REASON} caracteres`,
    );
    if (KINDS_NEEDING_REVISIT.has(entry.kind)) {
      assert.ok(entry.revisit, `${where}: \`kind\` ${entry.kind} exige \`revisit\``);
    }
    // El ancla se resuelve contra el CSS real de hoy, no contra la propia
    // declaracion: si la regla se borro, el selector cambio o el `occurrence`
    // declarado no tiene tantas copias, esto falla diciendo exactamente que
    // falta -en vez de pasar en silencio-.
    const css = await readFile(join(REPO_ROOT, entry.file), 'utf8');
    const resolved = resolveCssException(css, entry);
    assert.ok(resolved.found, `${where}: ${resolved.reason}`);
  }
});

// --- Fragilidad resuelta (C-8): una insercion antes de las entradas no puede
// mover el ancla, porque el ancla ya no es una linea. Se construye una copia de
// programa-general-actualizar.css con un comentario inocuo al principio -que
// desplaza TODAS las lineas de sus entradas- y se comprueba que la resolucion
// no cambia.
test('insertar una linea inocua antes de las excepciones no rompe su resolucion', async () => {
  const inventory = await readInventory();
  const targetFile = 'public/css/programa-general-actualizar.css';
  const entries = inventory.entries.filter((e) => e.file === targetFile);
  assert.ok(entries.length >= 3, 'se esperaban varias entradas en programa-general-actualizar.css para este caso');

  const original = await readFile(join(REPO_ROOT, targetFile), 'utf8');
  const before = entries.map((entry) => resolveCssException(original, entry));
  assert.ok(before.every((r) => r.found), 'las entradas deberian resolverse contra el CSS original');

  const mutated = `/* linea inocua insertada por el test de fragilidad, 2026-08-05 */\n${original}`;
  const after = entries.map((entry) => resolveCssException(mutated, entry));

  assert.deepEqual(
    after.map((r) => r.found),
    before.map((r) => r.found),
    'insertar una linea antes de las entradas no deberia cambiar si se resuelven o no',
  );
  for (let i = 0; i < entries.length; i += 1) {
    assert.equal(
      after[i].atom?.selector,
      before[i].atom?.selector,
      `${entries[i].selector}: el selector resuelto cambio tras la insercion`,
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
