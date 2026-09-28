import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

/**
 * Goal s05-cajon-verdad (2026-09-28), punto 5. Cada `var(--…)` del cajón de Programa General y de
 * la hoja de su módulo debe existir en las hojas que la SPA carga (`frontend/index.html`:
 * `tokens.css` y `aia-design-system.css`) o en la propia hoja del módulo. Un token inexistente no
 * falla en ningún lado: la propiedad cae a su valor inicial y, en la barra de avance real, eso la
 * volvía transparente (`--aia-corporate`, `--ds-state-danger-text`).
 */
const leer = (ruta) => readFileSync(new URL(`../../${ruta}`, import.meta.url), 'utf8');

const CAJON = 'frontend/src/modules/programa-general/components/ProgramaDrawer.tsx';
const MODULO_CSS = 'frontend/src/modules/programa-general/programa-general.css';

const definidos = new Set(
  ['public/css/tokens.css', 'public/css/aia-design-system.css', MODULO_CSS].flatMap((ruta) =>
    Array.from(leer(ruta).matchAll(/(--[a-zA-Z0-9-]+)\s*:/g), (m) => m[1]),
  ),
);

const inexistentes = (fuente) => [
  ...new Set(Array.from(fuente.matchAll(/var\((--[a-zA-Z0-9-]+)/g), (m) => m[1]).filter((t) => !definidos.has(t))),
];

for (const ruta of [CAJON, MODULO_CSS]) {
  test(`${ruta} solo usa tokens que existen`, () => {
    assert.deepEqual(inexistentes(leer(ruta)), []);
  });
}

test('el cajón de Programa General no trae colores hex de respaldo', () => {
  assert.doesNotMatch(leer(CAJON), /#[0-9a-fA-F]{3,8}\b/);
});
