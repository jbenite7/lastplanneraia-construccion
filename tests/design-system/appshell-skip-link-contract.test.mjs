// Contrato de T01-Tarea 4 (AppShell): el brief exige "usa solo tokens/clases existentes;
// agrega un token semántico nuevo solo si una prueba de contrato de diseño que falla primero
// prueba que falta". Antes de esta tarea `.aia-skip-link` no existía en ningún archivo del
// design system — el primer assert de este archivo reconstruye esa prueba fallida (si alguien
// la borra sin querer, el segundo assert dejaría de tener sentido). El segundo fija que el
// único componente nuevo que la tarea sí justificó compone exclusivamente con tokens `--ds-*`
// ya existentes, nunca con un color o tamaño hardcodeado.

import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const RUTA_NAVIGATION_CSS = join(raiz, 'public/css/design-system/components/navigation.css');
const RUTA_COMPONENTS_DIR = join(raiz, 'public/css/design-system/components');

function listarCssDeComponentes() {
  return readdirSync(RUTA_COMPONENTS_DIR)
    .filter((archivo) => archivo.endsWith('.css'))
    .map((archivo) => join(RUTA_COMPONENTS_DIR, archivo));
}

test('.aia-skip-link solo vive en navigation.css (donde esta tarea la agregó)', () => {
  const otros = listarCssDeComponentes().filter((ruta) => ruta !== RUTA_NAVIGATION_CSS);
  for (const ruta of otros) {
    const texto = readFileSync(ruta, 'utf8');
    assert.doesNotMatch(texto, /\.aia-skip-link/, `${ruta}: no debería definir .aia-skip-link`);
  }

  const propio = readFileSync(RUTA_NAVIGATION_CSS, 'utf8');
  assert.match(propio, /\.aia-skip-link\s*\{/, 'navigation.css debe definir .aia-skip-link');
});

test('.aia-skip-link compone solo con tokens --ds-*, sin hex/rgb hardcodeado', () => {
  const texto = readFileSync(RUTA_NAVIGATION_CSS, 'utf8');
  const inicioBase = texto.indexOf('.aia-skip-link {');
  const inicioFoco = texto.indexOf('.aia-skip-link:focus-visible {');
  assert.ok(inicioBase >= 0 && inicioFoco > inicioBase, 'no se encontraron las reglas de .aia-skip-link');
  const cierreFoco = texto.indexOf('}', texto.indexOf('}', inicioFoco) + 1);
  const bloque = texto.slice(inicioBase, cierreFoco);

  assert.doesNotMatch(bloque, /#[0-9a-fA-F]{3,8}\b/, 'usa un hex hardcodeado en vez de un token');
  assert.doesNotMatch(bloque, /rgba?\(/, 'usa rgb()/rgba() hardcodeado en vez de un token');

  // Toda propiedad de color/tipografía/espaciado/radio/sombra/z-index/foco pasa por var(--ds-*);
  // lo único fuera de ese molde son valores estructurales sin equivalente semántico
  // (fixed/none/solid, translateY, y duraciones/anchos que ya usan tokens de motion/outline).
  const usaTokens = (bloque.match(/var\(--ds-[a-z0-9-]+\)/g) ?? []).length;
  assert.ok(usaTokens >= 10, `esperaba varios var(--ds-*) en .aia-skip-link, encontró ${usaTokens}`);
});
