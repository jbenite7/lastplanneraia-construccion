import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { extname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import {
  ANY_STATE_RE,
  inlineUsesBySignature,
  resolveCssException,
  signatureKey,
  stripComments,
  unpairedUsesBySignature,
} from '../../scripts/design-system/state-token-locator.mjs';

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
// `selector` y `occurrence` apuntan a una regla que existe hoy en el CSS real.
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

function key(entry) {
  // Los usos "found" en CSS llevan `selector`; los inline (fuera de hojas)
  // no tienen bloque del que sacarlo, asi que su firma es solo file+token+occurrence.
  // Las entradas declaradas siempre traen `selector` en el JSON (documental para
  // inline), pero solo entra en la firma cuando el archivo es una hoja CSS real.
  const withSelector = entry.file.endsWith('.css');
  return signatureKey({
    file: entry.file,
    selector: withSelector ? entry.selector : undefined,
    token: entry.token,
    occurrence: entry.occurrence ?? 1,
  });
}

async function collectFound() {
  const found = [];
  for (const file of (await walk(CSS_ROOT, (name) => name.endsWith('.css'))).sort()) {
    const rel = `public/css/${relative(CSS_ROOT, file)}`;
    for (const use of unpairedUsesBySignature(await readFile(file, 'utf8'))) found.push({ file: rel, ...use });
  }
  for (const root of INLINE_ROOTS) {
    const files = await walk(join(REPO_ROOT, root), (name) => INLINE_EXTS.has(extname(name)));
    for (const file of files.sort()) {
      const rel = relative(REPO_ROOT, file);
      for (const use of inlineUsesBySignature(await readFile(file, 'utf8'))) found.push({ file: rel, ...use });
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

  const foundKeys = new Set(found.map((use) => key({ ...use })));
  // Declarar de menos deja pasar una regresion; declarar de mas -una entrada
  // fantasma sobre codigo que ya esta pareado, o que se movio- deja un hueco
  // libre donde la proxima regresion entraria en verde. Las dos fallan.
  const sinDeclarar = found.filter((use) => !declared.has(key({ ...use }))).map((use) => key({ ...use })).sort();
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
    const where = `${entry.file}#${entry.occurrence ?? 1} ${entry.token}`;
    assert.ok(typeof entry.file === 'string' && entry.file.length > 0, `${where}: falta \`file\``);
    assert.ok(
      entry.occurrence === undefined || (Number.isInteger(entry.occurrence) && entry.occurrence > 0),
      `${where}: \`occurrence\`, si esta, tiene que ser un entero positivo`,
    );
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

    assert.ok(typeof entry.selector === 'string' && entry.selector.length > 0, `${where}: falta \`selector\``);

    const source = await readOnce(entry.file);
    if (entry.file.endsWith('.css')) {
      // El ancla ya NO es un numero de linea: es la firma selector+token(+occurrence),
      // resuelta contra el CSS real de hoy. Si la regla se borro, el selector
      // cambio o el occurrence declarado no tiene tantas copias, esto falla con
      // un mensaje que dice exactamente que falta -no un pase silencioso.
      const resolved = resolveCssException(source, entry);
      assert.ok(resolved.found, `${where}: ${resolved.reason}`);
    } else {
      // Fuera de una hoja CSS no hay bloque que resolver: basta con que el
      // token literal siga apareciendo en el archivo.
      assert.ok(source.includes(entry.token), `${where}: el token ya no aparece en ${entry.file}`);
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

// --- Fragilidad resuelta (Task 12-bis): una insercion antes de las entradas
// no puede mover el ancla, porque el ancla ya no es una linea. Se construye
// una copia de programacion-semanal.css con un comentario inocuo insertado al
// principio -desplaza TODAS las lineas de las 4 entradas de ese archivo- y se
// comprueba que la resolucion de las excepciones de ese archivo no cambia.
test('insertar una linea inocua antes de las excepciones no rompe su resolucion', async () => {
  const inventory = await loadInventory();
  const targetFile = 'public/css/programacion-semanal.css';
  const entries = inventory.exceptions.filter((e) => e.file === targetFile);
  assert.ok(entries.length >= 4, 'se esperaban varias excepciones en programacion-semanal.css para este caso');

  const original = await readFile(join(REPO_ROOT, targetFile), 'utf8');
  const before = entries.map((entry) => resolveCssException(original, entry));
  assert.ok(before.every((r) => r.found), 'las excepciones deberian resolverse contra el CSS original');

  const mutated = `/* linea inocua insertada por el test de fragilidad, 2026-08-03 */\n${original}`;
  const after = entries.map((entry) => resolveCssException(mutated, entry));

  assert.deepEqual(
    after.map((r) => r.found),
    before.map((r) => r.found),
    'insertar una linea antes de las entradas no deberia cambiar si se resuelven o no',
  );
  for (let i = 0; i < entries.length; i += 1) {
    assert.equal(after[i].atom?.selector, before[i].atom?.selector, `${entries[i].selector}: el selector resuelto cambio tras la insercion`);
  }
});

// --- Guard-rail del rediseño para Task 13: borrar el bloque entero de una
// excepcion declarada tiene que fallar con un mensaje claro, no pasar en
// silencio. Se simula quitando la regla `.ps-missing-assignment` (by-design,
// sin duplicado) de una copia del CSS.
test('borrar el bloque de una excepcion declarada produce un error claro, no un pase silencioso', async () => {
  const inventory = await loadInventory();
  const targetFile = 'public/css/programacion-semanal.css';
  const entry = inventory.exceptions.find((e) => e.file === targetFile && e.selector === '.ps-missing-assignment');
  assert.ok(entry, 'se esperaba encontrar la excepcion de .ps-missing-assignment en el inventario');

  const original = await readFile(join(REPO_ROOT, targetFile), 'utf8');
  const before = resolveCssException(original, entry);
  assert.ok(before.found, 'la excepcion deberia resolverse contra el CSS original');

  // Localiza y borra el bloque `.ps-missing-assignment { ... }` completo de una
  // copia en memoria, sin tocar el archivo real.
  const startIdx = original.indexOf('.ps-missing-assignment');
  assert.ok(startIdx >= 0, 'no se encontro el selector en el archivo real');
  const braceOpen = original.indexOf('{', startIdx);
  const braceClose = original.indexOf('}', braceOpen);
  assert.ok(braceOpen >= 0 && braceClose >= 0, 'no se pudo delimitar el bloque a borrar');
  const mutated = original.slice(0, original.lastIndexOf('\n', startIdx) + 1) + original.slice(braceClose + 1);

  const after = resolveCssException(mutated, entry);
  assert.equal(after.found, false, 'borrar el bloque deberia dejar la excepcion sin regla que la respalde');
  assert.match(after.reason, /excepcion sin regla que la respalde/, 'el mensaje de error deberia decirlo explicitamente');
});
