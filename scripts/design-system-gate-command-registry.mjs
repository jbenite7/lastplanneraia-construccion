const entries = [
  ['static', 'ds.static.v1', 'npm run test:design-system:static'],
  ['runtime', 'ds.runtime.v1', 'npm run test:design-system:runtime'],
  ['runtime-budgets', 'ds.runtime-budgets.v1', 'npm run test:runtime-budget:check'],
  ['phpstan-scoped', 'ds.phpstan-scoped.v1', 'docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G'],
  ['phpstan-global', 'ds.phpstan-global.v1', 'npm run test:design-system:phpstan'],
  ['global-table-safety', 'ds.global-table-safety.v1', 'docker compose exec app php tests/test_global_table_safety.php'],
  ['pg-roles', 'ds.pg-roles.v1', 'npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1'],
  ['pg-persistence', 'ds.pg-persistence.v1', 'npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1'],
  ['data-restoration', 'ds.data-restoration.v1', 'npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1'],
  ['accessibility-insights', 'ds.accessibility-insights.v1', 'accessibility-insights basic-automated-review'],
  ['consolidated-lab', 'ds.consolidated-lab.v1', 'local-review consolidated-lab'],
  ['consolidated-pilot', 'ds.consolidated-pilot.v1', 'local-review consolidated-pilot'],
  ['git-preservation', 'ds.git-preservation.v1', 'npm run test:design-system:preservation'],
  ['review', 'ds.review.v1', 'local-review exact-release-diff'],
  ['atomic-commit', 'ds.atomic-commit.v1', 'git diff --cached --check'],
];

export const gateCommandRegistry = new Map(entries.map(([gateId, commandId, command]) => [
  gateId,
  Object.freeze({ commandId, command }),
]));

export function canonicalGateCommand(gateId) {
  return gateCommandRegistry.get(gateId) ?? null;
}
