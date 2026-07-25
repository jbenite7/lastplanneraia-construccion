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

const sourceFiles = async (dir, ...extensions) => {
  const entries = await readdir(new URL(dir, repositoryRoot), { withFileTypes: true, recursive: true });
  return entries
    .filter((entry) => entry.isFile() && extensions.some((ext) => entry.name.endsWith(ext)))
    .map((entry) => `${entry.parentPath ?? entry.path}/${entry.name}`);
};

// Quita comentarios de bloque y lineas que son integramente comentario, para
// distinguir una referencia VIVA (que produce una peticion HTTP) de una mencion
// historica en prosa.
const stripComments = (source) => source
  .replace(/\/\*[\s\S]*?\*\//g, '')
  .split('\n')
  .filter((line) => !/^\s*(\/\/|#|\*)/.test(line))
  .join('\n');

// Los tests de arriba solo comprueban que los ficheros no existan y que dos
// entrypoints CSS no los importen; no miraban el runtime. La premisa del plan F0
// ("las demas menciones de navbar.css son comentarios historicos") era falsa:
// public/js/cargarDatosGeneralesPagina2.js inyectaba un <link> a esa hoja en
// cada vista sin shell sidebar, dejando un 404 y una barra superior sin estilos
// superpuesta al encabezado en /contratos, /listado-actividades y /pdc.
// Borrar una hoja exige tambien borrar el codigo que la pide.
test('ningun codigo vivo referencia las hojas del tema legacy borradas', async () => {
  const deleted = ['navbar.css', 'dark-mode.css'];
  const offenders = [];
  const files = [
    ...(await sourceFiles('public/js', '.js')),
    ...(await sourceFiles('views', '.php')),
    ...(await sourceFiles('src', '.php')),
  ];
  for (const file of files) {
    const source = stripComments(await readFile(file, 'utf8'));
    for (const sheet of deleted) {
      if (source.includes(sheet)) offenders.push(`${file} → ${sheet}`);
    }
  }
  assert.deepEqual(offenders, [], `referencias vivas a hojas borradas: ${offenders.join(', ')}`);
});

test('ninguna vista fija el tema por su cuenta salvo plan-compras (F5)', async () => {
  for (const view of ['views/bi/_layout.php', 'views/design-system/lab.view.php']) {
    const source = await read(view);
    assert.equal(
      /setAttribute\(\s*['"]data-aia-theme['"]/.test(source), false,
      `${view} fija el tema con script inline`,
    );
    assert.equal(
      /<html[^>]*data-aia-theme/.test(source), false,
      `${view} fija el tema con atributo escrito a mano`,
    );
  }
});

test('renderLaboratory emite el bootstrap de tema', async () => {
  const source = await read('src/View/Components/DesignSystemHeadComponent.php');
  const body = source.slice(source.indexOf('function renderLaboratory'));
  assert.match(body.slice(0, 400), /theme-bootstrap\.js/);
});
