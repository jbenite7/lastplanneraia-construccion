import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { desfasados } from '../../scripts/css-minify.mjs';

const CSS_DIR = join(fileURLToPath(new URL('../../', import.meta.url)), 'public/css');

// Este guard cubre el unico riesgo serio del minificado servido: que se sirva una
// copia VIEJA. La URL lleva `?v=<mtime del original>`, asi que al editar una hoja
// sin regenerar cambia la URL y el cuerpo no — cache envenenada, y de las que no
// se ven.
//
// En un checkout limpio no hay minificados (van en .gitignore) y no hay nada que
// comprobar: `.htaccess` sirve los originales, que es el modo degradado seguro.
// Donde si existen —una maquina de desarrollo, o CI despues de generarlos— tienen
// que corresponder exactamente a su fuente.
test('ningun CSS minificado esta desfasado respecto a su fuente', () => {
  assert.ok(existsSync(CSS_DIR), 'public/css debe existir');
  const malos = desfasados(CSS_DIR);
  assert.deepEqual(
    malos,
    [],
    'Estos minificados no corresponden a lo que su fuente produce hoy. Regenera con '
      + '`npm run css:minify`:\n  ' + malos.join('\n  '),
  );
});
