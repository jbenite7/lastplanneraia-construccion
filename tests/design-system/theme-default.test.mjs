import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const THEME_ENTRYPOINTS = [
  'public/css/aia-design-system.css',
  'public/css/design-system/entrypoints/theme-overrides.css',
];

const themeBlock = (css, selector) => {
  const index = css.indexOf(selector);
  assert.notEqual(index, -1, `no se encontro el bloque ${selector}`);
  return css.slice(index, css.indexOf('}', index));
};

for (const entrypoint of THEME_ENTRYPOINTS) {
  test(`${entrypoint}: :root sirve los valores dark`, async () => {
    const css = await read(entrypoint);
    const root = themeBlock(css, ':root,');
    assert.match(root, /color-scheme:\s*dark/);
    assert.match(root, /--ds-active-bg-canvas:\s*var\(--ds-color-bg-canvas-dark\)/);
    assert.match(root, /--ds-active-text-primary:\s*var\(--ds-color-text-primary-dark\)/);
  });

  test(`${entrypoint}: :root se agrupa con el selector dark, no con linen`, async () => {
    const css = await read(entrypoint);
    const root = css.slice(css.indexOf(':root,'), css.indexOf('{', css.indexOf(':root,')));
    assert.match(root, /\[data-aia-theme="dark"\]/);
    assert.equal(/linen/.test(root), false, ':root sigue agrupado con linen');
  });
}

test('los dos entrypoints declaran bloques de tema equivalentes', async () => {
  const [aggregator, segmented] = await Promise.all(THEME_ENTRYPOINTS.map(read));
  const extract = (css) => css.slice(css.indexOf('@layer theme'), css.indexOf('@layer legacy-overrides'));
  assert.equal(extract(aggregator).trim(), extract(segmented).trim());
});
