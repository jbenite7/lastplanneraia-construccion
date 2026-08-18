const entries = [
  ['static', 'ds.static.v1', 'npm run test:design-system:static'],
  ['runtime', 'ds.runtime.v1', 'npm run test:design-system:runtime'],
  ['runtime-budgets', 'ds.runtime-budgets.v1', 'npm run test:runtime-budget:check'],
  ['phpstan-scoped', 'ds.phpstan-scoped.v1', 'docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G'],
  ['phpstan-global', 'ds.phpstan-global.v1', 'npm run test:design-system:phpstan'],
  ['global-table-safety', 'ds.global-table-safety.v1', 'docker compose exec app php tests/test_global_table_safety.php'],
  // `full-app-flow` funde `pg-roles` + `pg-persistence` + `data-restoration` (D-F1b-2,
  // 2026-08-11): los tres declaraban literalmente el mismo comando y no podian dar
  // veredictos distintos entre si. Un solo gate con el nombre de lo que de verdad mide.
  ['full-app-flow', 'ds.full-app-flow.v1', 'npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1'],
  // `semanal-roles-phases` entra el 2026-08-14, y entra por un motivo concreto: ampliar el
  // fixture aislado (8a0d5e46) volvio ejecutable esa suite, pero **nada la ejecutaba**. Un gate
  // que nadie corre deja la cobertura disponible, no vigente — que es justo la diferencia que
  // este indice existe para no perder de vista. Ver
  // docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design.md.
  ['semanal-roles-phases', 'ds.semanal-roles-phases.v1', 'npx playwright test tests/browser/programacion-semanal-roles-phases.mjs --workers=1'],
  ['atomic-commit', 'ds.atomic-commit.v1', 'git diff --cached --check'],
];

export const gateCommandRegistry = new Map(entries.map(([gateId, commandId, command]) => [
  gateId,
  Object.freeze({ commandId, command }),
]));

export function canonicalGateCommand(gateId) {
  return gateCommandRegistry.get(gateId) ?? null;
}
