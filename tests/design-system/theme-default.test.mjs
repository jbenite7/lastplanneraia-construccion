import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const THEME_ENTRYPOINTS = [
  'public/css/aia-design-system.css',
  'public/css/design-system/entrypoints/theme-overrides.css',
];

const DARK_SELECTOR = ':root,';
const LINEN_SELECTOR = '[data-aia-theme="linen"]';

// Mapeo completo y explicito de las 22 declaraciones del grupo :root/dark.
// Escrito a mano a partir del CSS commiteado en 7770ea2 (no derivado del
// bloque linen ni de ningun otro archivo): si un cambio futuro apunta una
// custom property al token equivocado, este mapa no cambia solo y la
// asercion debe fallar.
const EXPECTED_DARK_DECLARATIONS = {
  'color-scheme': 'dark',
  '--ds-active-bg-canvas': 'var(--ds-color-bg-canvas-dark)',
  '--ds-active-bg-page': 'var(--ds-color-bg-page-dark)',
  '--ds-active-surface': 'var(--ds-color-surface-dark)',
  '--ds-active-surface-raised': 'var(--ds-color-surface-raised-dark)',
  '--ds-active-surface-glass': 'var(--ds-color-surface-glass-dark)',
  '--ds-active-text-primary': 'var(--ds-color-text-primary-dark)',
  '--ds-active-text-secondary': 'var(--ds-color-text-secondary-dark)',
  '--ds-active-border': 'var(--ds-color-border-dark)',
  '--ds-active-focus-ring': 'var(--ds-color-focus-ring-dark)',
  '--ds-active-action-primary': 'var(--ds-color-domain-corporate-on-dark)',
  '--ds-active-action-primary-hover': 'var(--aia-green-light)',
  '--ds-active-action-text': 'var(--ds-color-text-on-domain-dark)',
  '--ds-active-domain-construction': 'var(--ds-color-domain-construction-on-dark)',
  '--ds-active-domain-construction-text': 'var(--ds-color-text-on-domain-dark)',
  '--ds-active-data-plan': 'var(--ds-color-domain-real-estate-on-dark)',
  '--ds-active-data-executed': 'var(--ds-color-domain-corporate-on-dark)',
  '--ds-active-nav-bg': 'var(--ds-nav-bg-dark)',
  '--ds-active-nav-border': 'var(--ds-nav-border-color-dark)',
  '--ds-active-nav-text': 'var(--ds-active-text-primary)',
  '--ds-active-nav-text-muted': 'var(--ds-active-text-secondary)',
  '--ds-active-nav-mark-filter': 'invert(1) brightness(1.15)',
};

// Mapeo completo y explicito de las 22 declaraciones del grupo linen.
const EXPECTED_LINEN_DECLARATIONS = {
  'color-scheme': 'light',
  '--ds-active-bg-canvas': 'var(--ds-color-bg-canvas)',
  '--ds-active-bg-page': 'var(--ds-color-bg-page)',
  '--ds-active-surface': 'var(--ds-color-surface)',
  '--ds-active-surface-raised': 'var(--ds-color-surface-raised)',
  '--ds-active-surface-glass': 'var(--ds-color-surface-glass)',
  '--ds-active-text-primary': 'var(--ds-color-text-primary)',
  '--ds-active-text-secondary': 'var(--ds-color-text-secondary)',
  '--ds-active-border': 'var(--ds-color-border-default)',
  '--ds-active-focus-ring': 'var(--ds-color-focus-ring-light)',
  '--ds-active-action-primary': 'var(--ds-color-domain-corporate)',
  '--ds-active-action-primary-hover': 'var(--ds-color-brand-primary-dark)',
  '--ds-active-action-text': 'var(--ds-color-text-inverse)',
  '--ds-active-domain-construction': 'var(--ds-color-domain-construction)',
  '--ds-active-domain-construction-text': 'var(--ds-color-text-inverse)',
  '--ds-active-data-plan': 'var(--ds-color-domain-real-estate)',
  '--ds-active-data-executed': 'var(--ds-color-domain-corporate)',
  '--ds-active-nav-bg': 'var(--ds-color-surface-raised)',
  '--ds-active-nav-border': 'var(--ds-color-border-default)',
  '--ds-active-nav-text': 'var(--ds-color-text-primary)',
  '--ds-active-nav-text-muted': 'var(--ds-color-text-secondary)',
  '--ds-active-nav-mark-filter': 'none',
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

// Cuerpo entre { y } de un bloque de selector, usado para verificar que
// no sobren ni falten declaraciones frente al mapeo esperado.
const blockBody = (css, selector, label) => {
  const selectorIndex = css.indexOf(selector);
  assert.notEqual(selectorIndex, -1, `${label}: no se encontro el selector ${selector}`);
  const openBrace = css.indexOf('{', selectorIndex);
  assert.notEqual(openBrace, -1, `${label}: no se encontro la apertura del bloque ${selector}`);
  const closeBrace = css.indexOf('}', openBrace);
  assert.notEqual(closeBrace, -1, `${label}: no se encontro el cierre del bloque ${selector}`);
  return css.slice(openBrace + 1, closeBrace);
};

const assertFullMapping = (body, declarations, label) => {
  for (const [property, value] of Object.entries(declarations)) {
    const pattern = new RegExp(`${escapeRegExp(property)}:\\s*${escapeRegExp(value)}\\s*;`);
    assert.match(body, pattern, `${label}: se esperaba "${property}: ${value};"`);
  }
  const declaredCount = (body.match(/;/g) || []).length;
  assert.equal(
    declaredCount,
    Object.keys(declarations).length,
    `${label}: el bloque declara ${declaredCount} propiedades, se esperaban ${Object.keys(declarations).length}`,
  );
};

for (const entrypoint of THEME_ENTRYPOINTS) {
  test(`${entrypoint}: :root/dark mapea cada --ds-active-* a su token dark (mapeo completo)`, async () => {
    const css = await read(entrypoint);
    const body = blockBody(css, DARK_SELECTOR, entrypoint);
    assertFullMapping(body, EXPECTED_DARK_DECLARATIONS, `${entrypoint} (dark)`);
  });

  test(`${entrypoint}: :root se agrupa con el selector dark, no con linen`, async () => {
    const css = await read(entrypoint);
    const root = css.slice(css.indexOf(':root,'), css.indexOf('{', css.indexOf(':root,')));
    assert.match(root, /\[data-aia-theme="dark"\]/);
    assert.equal(/linen/.test(root), false, ':root sigue agrupado con linen');
  });

  test(`${entrypoint}: linen mapea cada --ds-active-* a su token light (mapeo completo)`, async () => {
    const css = await read(entrypoint);
    const body = blockBody(css, LINEN_SELECTOR, entrypoint);
    assertFullMapping(body, EXPECTED_LINEN_DECLARATIONS, `${entrypoint} (linen)`);
  });

  test(`${entrypoint}: el grupo dark precede al grupo linen en el orden de fuente`, async () => {
    const css = await read(entrypoint);
    const darkIndex = css.indexOf(DARK_SELECTOR);
    const linenIndex = css.indexOf(LINEN_SELECTOR);
    assert.notEqual(darkIndex, -1, `${entrypoint}: no se encontro el grupo :root/dark`);
    assert.notEqual(linenIndex, -1, `${entrypoint}: no se encontro el grupo linen`);
    // :root/dark y [data-aia-theme="linen"] tienen especificidad identica;
    // quien gane la cascada lo decide solo el orden de fuente. Si linen
    // quedara antes, "linen" dejaria de poder overridear a dark en la practica.
    assert.ok(
      darkIndex < linenIndex,
      `${entrypoint}: el grupo linen aparece antes que el grupo dark en el archivo — con especificidad identica, linen dejaria de poder actuar como override`,
    );
  });
}

test('los dos entrypoints declaran bloques de tema equivalentes', async () => {
  const [aggregator, segmented] = await Promise.all(THEME_ENTRYPOINTS.map(read));
  const extract = (css, label) => {
    const start = css.indexOf('@layer theme');
    assert.notEqual(start, -1, `${label}: no se encontro el marcador @layer theme`);
    const end = css.indexOf('@layer legacy-overrides');
    assert.notEqual(end, -1, `${label}: no se encontro el marcador @layer legacy-overrides`);
    assert.ok(end > start, `${label}: @layer legacy-overrides aparece antes que @layer theme`);
    const span = css.slice(start, end);
    assert.ok(span.trim().length > 0, `${label}: el bloque @layer theme extraido esta vacio`);
    return span;
  };
  assert.equal(
    extract(aggregator, THEME_ENTRYPOINTS[0]).trim(),
    extract(segmented, THEME_ENTRYPOINTS[1]).trim(),
  );
});
