import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const repositoryRoot = new URL('../..', import.meta.url);
const exists = (path) => existsSync(new URL(path, repositoryRoot));
const read = (path) => readFile(new URL(path, repositoryRoot), 'utf8');

test('el componente de navbar legacy no existe', () => {
  assert.equal(exists('src/View/Components/NavbarComponent.php'), false);
});

test('las hojas del tema legacy no existen', () => {
  assert.equal(exists('public/css/dark-mode.css'), false);
  assert.equal(exists('public/css/navbar.css'), false);
});

test('ningun entrypoint importa las hojas del tema legacy', async () => {
  for (const entrypoint of [
    'public/css/aia-design-system.css',
    'public/css/design-system/entrypoints/core.css',
  ]) {
    const css = await read(entrypoint);
    assert.equal(css.includes('dark-mode.css'), false, `${entrypoint} importa dark-mode.css`);
    assert.equal(css.includes('navbar.css'), false, `${entrypoint} importa navbar.css`);
  }
});

import { readdir } from 'node:fs/promises';

const cssFiles = async (dir) => {
  const entries = await readdir(new URL(dir, repositoryRoot), { withFileTypes: true, recursive: true });
  return entries
    .filter((entry) => entry.isFile() && entry.name.endsWith('.css'))
    .map((entry) => `${entry.parentPath ?? entry.path}/${entry.name}`);
};

// El regex busca la CLASE, no `body.dark-mode`: los selectores con una clase de
// pagina intercalada (body.ps-page.dark-mode, .pg-page.dark-mode) tambien cuentan.
test('ninguna hoja de estilo depende de la clase legacy dark-mode', async () => {
  const offenders = [];
  for (const file of await cssFiles('public/css')) {
    const css = await readFile(file, 'utf8');
    if (/\.dark-mode\b/.test(css)) offenders.push(file);
  }
  assert.deepEqual(offenders, [], `hojas con .dark-mode: ${offenders.join(', ')}`);
});

test('el runtime de tema no aplica la clase legacy dark-mode', async () => {
  for (const script of [
    'public/js/modules/aia_ui/theme.js',
    'public/js/modules/aia_ui/design_system_lab.js',
  ]) {
    const source = await read(script);
    assert.equal(/["']dark-mode["']/.test(source), false, `${script} aplica la clase dark-mode`);
  }
});
