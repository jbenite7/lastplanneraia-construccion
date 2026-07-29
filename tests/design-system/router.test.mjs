import assert from 'node:assert/strict';
import test from 'node:test';
import { routeChanges } from '../../scripts/design-system-router.mjs';

test('archivos no-UI no disparan gates de design system', () => {
  const r = routeChanges(['src/Services/SemiAutoService.php', 'README.md']);
  assert.equal(r.uiFiles.length, 0);
  assert.equal(r.commands.length, 0);
});

test('editar una superficie declarada enruta a su gate estático', () => {
  const r = routeChanges(['views/core/project_selector.view.php']);
  assert.deepEqual(r.declared, ['project-selector']);
  assert.ok(r.commands.includes('npm run test:design-system:static'));
});

test('editar tokens/core del design system exige también el gate runtime', () => {
  const r = routeChanges(['public/css/design-system/core.css']);
  assert.ok(r.commands.includes('npm run test:design-system:runtime'));
});

// `views/pdc/pdc.view.php` era el ejemplo de superficie sin declarar hasta que
// F2 le dio manifiesto (2026-07-29). Se cambia por una de las dos vistas
// deprecadas que quedan fuera del plan del goal y por tanto sin manifiesto.
test('editar una superficie UI no declarada advierte pero no bloquea', () => {
  const r = routeChanges(['views/contratos/contratos.view.php']);
  assert.deepEqual(r.undeclared, ['views/contratos/contratos.view.php']);
  assert.ok(r.warnings.some((w) => /audit-baseline/.test(w)));
  assert.ok(r.commands.includes('npm run test:design-system:static'));
});
