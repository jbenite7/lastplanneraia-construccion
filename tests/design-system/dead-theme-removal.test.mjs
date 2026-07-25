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
