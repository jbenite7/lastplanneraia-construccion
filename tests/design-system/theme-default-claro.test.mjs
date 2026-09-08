import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const boot = readFileSync('public/js/modules/aia_ui/theme-bootstrap.js', 'utf8');

test('D12: el default del producto es CLARO', () => {
  assert.match(boot, /const\s+DEFAULT_THEME\s*=\s*['"]light['"]/,
    'theme-bootstrap declara light como default');
  assert.doesNotMatch(boot, /const\s+DEFAULT_THEME\s*=\s*['"]dark['"]/);
});

test('D14: la preferencia persiste local por aparato', () => {
  assert.match(boot, /localStorage\.getItem\(\s*['"]aia-theme['"]\s*\)/);
});

const themeRuntime = readFileSync('public/js/modules/aia_ui/theme.js', 'utf8');
const labRuntime = readFileSync('public/js/modules/aia_ui/design_system_lab.js', 'utf8');

test('D12: theme.js ya no escribe data-aia-theme ni la clase de tema (el bootstrap manda)', () => {
  assert.doesNotMatch(themeRuntime, /setAttribute\(\s*["']data-aia-theme["']/,
    'theme.js vuelve a fijar el tema a pelo: pisa la decisión de theme-bootstrap.js');
  assert.doesNotMatch(themeRuntime, /aia-theme-dark/,
    'theme.js vuelve a tocar la clase de tema');
});

test('ningún runtime del producto publica window.AiaDesignSystem (global sin consumidores)', () => {
  for (const [name, source] of [['theme.js', themeRuntime], ['design_system_lab.js', labRuntime]]) {
    assert.doesNotMatch(source, /AiaDesignSystem/, `${name} publica AiaDesignSystem`);
  }
});

test('theme.js conserva el movimiento reducido', () => {
  assert.match(themeRuntime, /prefers-reduced-motion: reduce/);
  assert.match(themeRuntime, /aia-no-motion/);
});
