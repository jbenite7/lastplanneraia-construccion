// Contrato de legibilidad de Programa General (revisión visual, 2026-09-29).
//
// Defecto que Felipe vio en el cajón: las píldoras «Pendiente» y el botón «Declarar Crisis SOS LPS»
// eran ilegibles en los dos temas. Causa: usaban `--ds-active-state-solid-*-text` (el texto pensado
// para ir SOBRE el color sólido `solid-*`) encima de fondos `--ds-active-state-tint-*` (translúcidos).
// Medido en oscuro: 1.16:1 la píldora y 1.09:1 el botón (mínimo WCAG 4.5:1).
//
// El texto sobre un tinte es `--ds-color-state-{success,warning,critical,info}-text` (cambia con el
// tema) o `--ds-active-text-primary`. Esta prueba fija ese emparejamiento sin DOM.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const CSS_PG = 'frontend/src/modules/programa-general/programa-general.css';

function reglas(ruta) {
  const texto = readFileSync(join(raiz, ruta), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
  return [...texto.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((m) => ({
    selector: m[1].trim().replace(/\s+/g, ' '),
    cuerpo: m[2],
  }));
}
const declara = (cuerpo, prop) => cuerpo.match(new RegExp(`(?:^|;|\\s)${prop}\\s*:\\s*([^;]+)`))?.[1]?.trim() ?? null;

test('ningún texto de Programa General usa el color «sobre sólido» encima de un fondo tinte', () => {
  const malas = reglas(CSS_PG)
    .filter((r) => /var\(--ds-active-state-tint-/.test(declara(r.cuerpo, 'background') ?? ''))
    .filter((r) => /var\(--ds-active-state-solid-[a-z]+-text\)/.test(declara(r.cuerpo, 'color') ?? ''))
    .map((r) => r.selector);
  assert.deepEqual(malas, [], `texto «solid-*-text» sobre fondo «tint-*» (ilegible): ${malas.join(' | ')}`);
});

test('las píldoras de recurso y el botón SOS llevan el texto de estado que corresponde a su tinte', () => {
  const lista = reglas(CSS_PG);
  const color = (selector) => {
    const regla = lista.find((r) => r.selector === selector);
    assert.ok(regla, `falta la regla ${selector}`);
    return declara(regla.cuerpo, 'color');
  };
  assert.equal(color('.pill-liberado'), 'var(--ds-color-state-success-text)');
  assert.equal(color('.pill-gestion'), 'var(--ds-color-state-warning-text)');
  assert.equal(color('.pill-bloqueado'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.btn-sos-trigger'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.pg-error'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.drawer-sos-feedback--error'), 'var(--ds-color-state-critical-text)');
});
