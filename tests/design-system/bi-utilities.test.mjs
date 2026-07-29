import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import {
  biUtilityFailures,
  declaredUtilities,
  isUtilityShaped,
  usedClassTokens,
} from '../../scripts/design-system-bi-utilities.mjs';

const root = fileURLToPath(new URL('../..', import.meta.url));

test('el arbol real no usa ninguna utilidad Tailwind sin declarar', () => {
  assert.deepEqual(biUtilityFailures({ root }), []);
});

test('la hoja declara las 98 utilidades vivas de la superficie', () => {
  // 97 son la union medida de las 8 rutas /bi/* con el CDN puesto (94
  // estaticas mas `.inline-flex`, `.px-2` y `.py-0.5`, que solo aparecen en
  // runtime). La 98 es `.space-y-1`, que el volcado NO vio porque su rama
  // (`renderRecommendedActions()` con datos) no llego a ejecutarse, y que
  // encontro este gate leyendo el codigo.
  assert.equal(declaredUtilities({ root }).size, 98);
});

test('reconoce la forma de una utilidad Tailwind y no la de una clase propia', () => {
  for (const token of ['p-4', 'mt-1', 'w-full', 'flex', 'hidden', 'grid-cols-1',
    'text-gray-600', 'bg-gray-100', 'border-l-4', 'md:grid-cols-2', 'xl:col-span-2',
    'py-0.5', 'text-[10px]', 'min-h-[24px]', 'truncate', 'uppercase', 'z-10']) {
    assert.equal(isUtilityShaped(token), true, `${token} deberia parecer utilidad`);
  }
  for (const token of ['bi-chip', 'aia-btn', 'aia-btn--secondary', 'card', 'nav-item',
    'view-section', 'loader', 'is-disabled', 'active', 'shell-week-flyout',
    'bi-control-tower-page', 'context-week-chip', 'bi-filter-drawer__close']) {
    assert.equal(isUtilityShaped(token), false, `${token} NO deberia parecer utilidad`);
  }
});

test('el gate caza una utilidad nueva en una vista de /bi/', () => {
  const failures = biUtilityFailures({
    root,
    viewsOverride: [{ file: 'views/bi/fake.php', content: '<div class="card mt-8 flex">x</div>' }],
  });
  assert.ok(
    failures.some((f) => f.includes('undeclared-bi-utility') && f.includes('mt-8')),
    `esperaba el fallo de mt-8, hubo: ${failures.join(' | ')}`,
  );
  assert.ok(!failures.some((f) => f.includes('card')), 'card es clase propia, no utilidad');
});

test('el gate caza una utilidad nueva escrita desde bi-spa.js', () => {
  const failures = biUtilityFailures({
    root,
    scriptsOverride: [{ file: 'public/js/modules/bi-spa.js', content: "el.classList.add('space-y-4');" }],
  });
  assert.ok(failures.some((f) => f.includes('undeclared-bi-utility') && f.includes('space-y-4')));
});

test('el gate ve las clases que un helper de JS devuelve dentro de un string', () => {
  // `statusBadgeClass()` ensambla `bg-gray-100 text-gray-500` como valor de
  // retorno: un escaneo de `class="..."` no lo veria, y fue justo asi como
  // tres utilidades llegaron a /bi/* sin figurar en ningun markup.
  const failures = biUtilityFailures({
    root,
    scriptsOverride: [{ file: 'public/js/modules/bi-spa.js', content: "return 'bg-amber-200 text-gray-500';" }],
  });
  assert.ok(failures.some((f) => f.includes('bg-amber-200')));
  assert.ok(!failures.some((f) => f.includes('text-gray-500')), 'text-gray-500 si esta declarada');
});

test('el gate avisa de una utilidad declarada que ya no usa nadie', () => {
  const failures = biUtilityFailures({
    root,
    viewsOverride: [{ file: 'views/bi/fake.php', content: '<div class="flex">x</div>' }],
    scriptsOverride: [{ file: 'public/js/modules/bi-spa.js', content: '' }],
  });
  assert.ok(
    failures.some((f) => f.includes('unused-bi-utility')),
    'una hoja que declara de mas tambien es podredumbre',
  );
});

test('usedClassTokens neutraliza los bloques PHP interpolados', () => {
  // `_nav.php` mete un ternario dentro del propio atributo `class`; si no se
  // neutraliza, el parser censa `?` y `:` como si fueran clases.
  const tokens = usedClassTokens({
    views: [{ file: 'views/bi/x.php', content: '<button class="nav-item <?= $a ? \'active\' : \'\' ?> px-4">x</button>' }],
    scripts: [],
  });
  assert.ok(tokens.has('nav-item'));
  assert.ok(tokens.has('px-4'));
  assert.ok(!tokens.has('?'));
  assert.ok(!tokens.has('$a'));
});
