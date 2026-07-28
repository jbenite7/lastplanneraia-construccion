import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const CSS_ROOT = fileURLToPath(new URL('../../public/css/', import.meta.url));

// Los cuatro tokens de estado se consumen en pareja: el fondo trae su texto.
// Un bloque que use solo uno de los dos queda descompensado en cuanto el par se
// invierta -fondo oscuro con texto oscuro, o al reves-, asi que cada caso tiene
// que estar emparejado o declarado como excepcion con su razon.
async function cssFiles(dir) {
  const found = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) found.push(...await cssFiles(full));
    else if (entry.name.endsWith('.css')) found.push(full);
  }
  return found;
}

// Se recorre bloque a bloque `{ ... }` porque la pareja tiene sentido dentro de
// una misma regla: un `-bg` en una regla y su `-text` en otra no garantiza que
// se apliquen al mismo elemento.
function unpairedUses(css) {
  const found = [];
  for (const [, block] of css.matchAll(/\{([^{}]*)\}/g)) {
    const bg = new Set([...block.matchAll(/background[^;:]*:\s*[^;]*--ds-color-state-(\w+)-bg/g)].map((m) => m[1]));
    const text = new Set([...block.matchAll(/(?<!background)color\s*:\s*[^;]*--ds-color-state-(\w+)-text/g)].map((m) => m[1]));
    for (const family of bg) if (!text.has(family)) found.push(`--ds-color-state-${family}-bg`);
    for (const family of text) if (!bg.has(family)) found.push(`--ds-color-state-${family}-text`);
  }
  return found;
}

test('todo uso descompensado de los tokens de estado está declarado', async () => {
  const exceptions = JSON.parse(
    await readFile(new URL('../../docs/design-system/state-token-exceptions.json', import.meta.url), 'utf8'),
  );
  const declared = new Map();
  for (const e of exceptions.exceptions) {
    assert.ok(e.reason && e.reason.length > 20, `la excepción de ${e.file} necesita una razón real`);
    declared.set(`${e.file}|${e.token}`, (declared.get(`${e.file}|${e.token}`) ?? 0) + 1);
  }

  const files = await cssFiles(CSS_ROOT);
  assert.ok(files.length > 20, `se esperaban más de 20 hojas y se encontraron ${files.length}`);

  const undeclared = [];
  for (const file of files) {
    const rel = `public/css/${relative(CSS_ROOT, file)}`;
    const counts = new Map();
    for (const token of unpairedUses(await readFile(file, 'utf8'))) {
      counts.set(token, (counts.get(token) ?? 0) + 1);
    }
    for (const [token, count] of counts) {
      const allowed = declared.get(`${rel}|${token}`) ?? 0;
      if (count > allowed) undeclared.push(`${rel}: ${token} ×${count} (declaradas ${allowed})`);
    }
  }

  assert.deepEqual(undeclared, [], `usos descompensados sin declarar:\n  ${undeclared.join('\n  ')}`);
});
