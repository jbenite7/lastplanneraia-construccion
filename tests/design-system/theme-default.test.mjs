import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const THEME_ENTRYPOINTS = [
  'public/css/aia-design-system.css',
  'public/css/design-system/entrypoints/theme-overrides.css',
];

const DARK_SELECTOR = ':root,';

// Mapeo completo y explicito de las 22 declaraciones del grupo :root/dark.
// Escrito a mano a partir del CSS commiteado en 7770ea2 (no derivado del
// bloque linen ni de ningun otro archivo): si un cambio futuro apunta una
// custom property al token equivocado, este mapa no cambia solo y la
// asercion debe fallar. Sobrevive al retiro de linen en F0/Task 7 porque
// protege exactamente lo mismo que protegia antes: el mapeo del unico
// grupo que queda.
const EXPECTED_DARK_DECLARATIONS = {
  'color-scheme': 'dark',
  '--ds-active-bg-canvas': 'var(--ds-color-bg-canvas-dark)',
  '--ds-active-bg-page': 'var(--ds-color-bg-page-dark)',
  '--ds-active-surface': 'var(--ds-color-surface-dark)',
  '--ds-active-surface-raised': 'var(--ds-color-surface-raised-dark)',
  '--ds-active-surface-glass': 'var(--ds-color-surface-glass-dark)',
  '--ds-active-text-primary': 'var(--ds-color-text-primary-dark)',
  '--ds-active-text-secondary': 'var(--ds-color-text-secondary-dark)',
  // Par de bordes de WCAG 1.4.11 (decision del usuario, 2026-07-29). El alias
  // decorativo pasa a nombrarse por su ROL (separator) en vez de por su tema, y
  // aparece el de frontera de control. `--ds-color-border-separator-dark` es un
  // alias de `--ds-color-border-dark`, o sea que el valor decorativo NO cambia:
  // lo que cambia es que ahora el sistema distingue los dos roles por nombre.
  // El de control sube a 0,4 porque a 0,22 rendia 1,90:1 y 1.4.11 pide 3:1;
  // medido en navegador a 1180x820 sobre los quince fondos oscuros reales, la
  // banda resultante es 3,15-3,40:1. Se añade a este mapa (y no se relaja la
  // comprobacion de conteo exacto) para que el guard exija tambien el token nuevo.
  '--ds-active-border': 'var(--ds-color-border-separator-dark)',
  '--ds-active-border-control': 'var(--ds-color-border-control-dark)',
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

// Cuerpo completo (con llaves balanceadas) de la regla @layer theme, usado
// para contar cuantos grupos de selectores de nivel superior declara. Tras
// F0/Task 7 solo debe quedar uno (:root/dark); un segundo grupo indicaria
// que alguien reintrodujo otro tema.
const layerThemeBody = (css, label) => {
  const marker = '@layer theme';
  const markerIndex = css.indexOf(marker);
  assert.notEqual(markerIndex, -1, `${label}: no se encontro @layer theme`);
  const openBrace = css.indexOf('{', markerIndex);
  assert.notEqual(openBrace, -1, `${label}: no se encontro la apertura de @layer theme`);
  let depth = 0;
  for (let i = openBrace; i < css.length; i += 1) {
    if (css[i] === '{') depth += 1;
    else if (css[i] === '}') {
      depth -= 1;
      if (depth === 0) return css.slice(openBrace + 1, i);
    }
  }
  throw new Error(`${label}: no se encontro el cierre de @layer theme`);
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

  // F0/Task 7 retiro el grupo linen: ya no hay dos grupos cuyo orden de
  // fuente importe, asi que "el grupo dark precede a linen" queda sin
  // objeto. La proteccion equivalente ahora es que solo sobreviva un grupo
  // de selectores dentro de @layer theme — si alguien reintrodujera un
  // segundo tema, esta asercion lo detecta.
  test(`${entrypoint}: @layer theme contiene exactamente un grupo de selectores`, async () => {
    const css = await read(entrypoint);
    const body = layerThemeBody(css, entrypoint);
    const groupCount = (body.match(/{/g) || []).length;
    assert.equal(
      groupCount,
      1,
      `${entrypoint}: @layer theme declara ${groupCount} grupos de selectores, se esperaba 1 (linen se retiro en F0/Task 7; un segundo grupo indicaria que se reintrodujo otro tema)`,
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
