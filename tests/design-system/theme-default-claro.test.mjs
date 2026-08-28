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
