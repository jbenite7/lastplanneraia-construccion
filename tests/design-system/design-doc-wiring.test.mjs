import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = new URL('../../', import.meta.url);
const read = (rel) => readFile(new URL(rel, root), 'utf8');
const present = (rel) => existsSync(fileURLToPath(new URL(rel, root)));

test('DESIGN.md sigue apuntando a la autoridad ejecutable, sin ser segunda fuente de verdad', async () => {
  const design = await read('DESIGN.md');
  for (const anchor of [
    'docs/design-system/',
    'public/css/tokens.css',
    'public/css/design-system/core.css',
    'public/css/aia-design-system.css',
    '/internal/design-system',
  ]) {
    assert.ok(design.includes(anchor), `DESIGN.md debe referenciar ${anchor}`);
  }
});

test('los archivos de contexto versionados enlazan DESIGN.md para que los asistentes lo carguen', async () => {
  // Solo estos tres están trackeados en el repo; CLAUDE.md y AGENTS.md están en
  // .gitignore (locales por máquina) y no existen en un checkout limpio de CI.
  for (const file of ['GEMINI.md', 'README.md', 'docs/design-system/README.md']) {
    const source = await read(file);
    assert.match(source, /DESIGN\.md/, `${file} debe referenciar DESIGN.md`);
  }
});

test('si los archivos de contexto locales existen, también enlazan DESIGN.md', async () => {
  for (const file of ['CLAUDE.md', 'AGENTS.md']) {
    if (!present(file)) continue; // gitignored: ausente en CI, presente en local
    const source = await read(file);
    assert.match(source, /DESIGN\.md/, `${file} (local) debe referenciar DESIGN.md`);
  }
});

test('DESIGN.md no describe linen como una superficie enviada', async () => {
  const design = await read('DESIGN.md');
  assert.doesNotMatch(design, /shipped-but-ungated/i,
    'DESIGN.md no debe marcar linen como deuda enviada: F0 lo retiro del producto');
  assert.match(design, /retirado del producto/i,
    'DESIGN.md debe registrar que linen fue retirado, no dejarlo sin mencion');
});
