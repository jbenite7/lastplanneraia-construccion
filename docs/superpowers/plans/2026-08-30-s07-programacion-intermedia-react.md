---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-08-30-s07-programacion-intermedia-react.md
resumen: "migrate /programacion-intermedia from VIEW-35, Handsontable and global JavaScript to a native React look-ahead with server-authoritative restriction/state…"
---

# S07 Programación Intermedia React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Execute tasks in order and stop at the stated review gates.
> Checkbox syntax is an execution prompt only; repository progress and closure are recorded in the
> plan `## Cierre` ledger and git history, never inferred from checkbox counts.

**Goal:** migrate `/programacion-intermedia` from VIEW-35, Handsontable and global JavaScript to a
native React look-ahead with server-authoritative restriction/state rules, effective actions,
individual and shared edits, responsive table/cards, CSV/XLSX, shared drawer integration,
dark/light parity and a reversible canonical cut, without changing RLS or data during verification.

**Architecture:** T01 supplies shell/session/project/week/theme/navigation; S04 project switching;
S05 the activity adapter and initial typed drawer boundary; T02 the final shared drawer. S07 adds a
project-scoped context/list boundary, a pure restriction/state resolver, an effective action policy,
narrow row and batch services, and eight endpoints under `/api/programacion-intermedia/*`. React
parses every response with Zod, stores one normalized domain state, renders a semantic table at
768+ and fully editable cards below 768. Legacy VIEW-35/aliases remain for pilot rollback until
functional, RBAC, a11y and explicitly approved visual gates pass.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe, PhpSpreadsheet 5.4 and AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s07-programacion-intermedia-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia` or another checkout.
- Execute only after T01, S04 and S05 contracts/files are present and green. Task 12 additionally
  requires T02's shared React drawer contract. If T02 is absent, stop at checkpoint 3; do not fork or
  copy the legacy drawer into S07.
- Inspect `git status --short` and relevant diffs before every task. Preserve pre-existing/unrelated
  edits; do not clean, revert or refactor adjacent work.
- This session is documentation-only. Do not implement, commit, push, publish or deploy now. Commit
  commands below are future execution instructions and require implementation authorization.
- `/admin/` is excluded: no files, routes, styles, tests, dependencies or permissions there.
- Do not modify RLS, `ProjectScope` semantics, schema, migrations, grants, users, credentials,
  memberships, permission assignments or database data. Do not execute DDL/DML.
- PHP is authoritative for project, area, week/max/confirmation, row ownership, restriction
  configuration, readiness/state, permissions, historical editing and shared tracking. React never
  sends `db`, table prefix, `project_id`, project, area, role, user, max week or permission flags.
- Keep existing capabilities unchanged. Use `lps.programacion_intermedia.ver/editar`,
  `lps.reportes.generar` and the T02 drawer actions. Resolve aliases with
  `RbacService::normalizeRole()` only inside the server policy; never expose role as frontend
  authority.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Components/hooks call the typed S07
  gateway. Zod schemas are the only TypeScript type definitions.
- Every endpoint starts with a failing PHP contract. Every schema/gateway/domain/component starts
  with a failing Vitest. No implementation-before-RED.
- Mutations never retry automatically. Browser scenarios intercept every S07 mutation, report and
  drawer write before navigation; they never reach a real write path.
- Do not run `tests/browser/preconstruccion-full-cycle.mjs`,
  `tests/browser/full-app-flow.spec.mjs`, `tests/browser/support/operationalCycle.mjs` or any suite
  that enters `/dev/entrar`, changes week/project, saves PI, syncs catalogs, writes comments/crisis or
  generates a real report. They execute DML or alter shared session/data.
- PHP mutation tests use fake stores, notifier, synchronizer and report generator. No test connects
  to a live DB for writes; rollback writes are also forbidden DML.
- Preserve the exact eight state IDs/labels, hard/soft thresholds, `N/A` semantics, six-week count
  universe, stable severity order and observable batch tracking warning.
- Use only tokens from `public/css/tokens.css` and semantic state contracts; no literal colors,
  inline style objects, `!important`, Bootstrap, jQuery, Handsontable, TomSelect, Toastr, Font
  Awesome, CSS-in-JS or a new grid/query/state dependency.
- Dark is default/fallback; light has identical capability. Required viewports: `390×844`,
  `768×1024`, `1180×820`, `1440×900`; no horizontal page overflow.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output outside git until Task 13's gate is approved.
- No real dev-door login, DB probe, report file, catalog sync or session-changing request is part of
  S07 verification. Playwright installs full interception before navigation.

## File Structure

### Create — PHP

- `src/Security/ProgramacionIntermediaActionPolicy.php` — pure action and row-action resolver.
- `src/Services/ProgramacionIntermediaReadStore.php` — scoped read interface.
- `src/Services/ProgramacionIntermediaWriteStore.php` — scoped transaction/write interface.
- `src/Services/DatabaseProgramacionIntermediaStore.php` — sole S07 SQL adapter.
- `src/Services/ProgramacionIntermediaStateResolver.php` — readiness, aggregate and eight states.
- `src/Services/ProgramacionIntermediaContextService.php` — week/actions/config/catalogs/links/CSRF.
- `src/Services/ProgramacionIntermediaQueryService.php` — list DTO and six-week counts.
- `src/Services/ProgramacionIntermediaMutationService.php` — narrow individual save.
- `src/Services/ProgramacionIntermediaCatalogService.php` — pure read and explicit refresh seam.
- `src/Services/ProgramacionIntermediaBatchService.php` — preview/revalidate/apply orchestration.
- `src/Services/ProgramacionIntermediaNotifier.php` — notification/audit boundary.
- `src/Services/ProgramacionIntermediaReportService.php` — safe wrapper around report generator.
- `src/Controllers/Api/ProgramacionIntermediaApiController.php` — thin RBAC/CSRF/JSON adapter.
- `tests/fixtures/programacion_intermedia_state_cases.php` — shared PHP state/config fixtures.
- `tests/test_programacion_intermedia_state_resolver.php`.
- `tests/test_programacion_intermedia_action_policy.php`.
- `tests/test_programacion_intermedia_context_contract.php`.
- `tests/test_programacion_intermedia_activities_contract.php`.
- `tests/test_programacion_intermedia_save_contract.php`.
- `tests/test_programacion_intermedia_view_contract.php`.
- `tests/test_programacion_intermedia_catalogs_contract.php`.
- `tests/test_programacion_intermedia_shared_preview_contract.php`.
- `tests/test_programacion_intermedia_shared_apply_contract.php`.
- `tests/test_programacion_intermedia_report_contract.php`.
- `tests/test_programacion_intermedia_routes.php`.

### Create — React/TypeScript

- `frontend/src/lib/api/esquemas/programacion-intermedia.ts` and `.test.ts` — strict raw contracts;
  all S07 types use `z.infer`.
- `frontend/src/lib/api/programacion-intermedia.ts` and `.test.ts` — exact queries/JSON/CSRF.
- `frontend/src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.ts` and
  `.test.ts`.
- `frontend/src/modules/programacion-intermedia/dominio/filtrarProgramacionIntermedia.ts` and
  `.test.ts`.
- `frontend/src/modules/programacion-intermedia/dominio/exportarProgramacionIntermediaCsv.ts` and
  `.test.ts`.
- `frontend/src/modules/programacion-intermedia/useProgramacionIntermedia.ts` and `.test.tsx`.
- `frontend/src/modules/programacion-intermedia/ProgramacionIntermediaPage.tsx` and `.test.tsx`.
- `frontend/src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/componentes/FiltrosProgramacionIntermedia.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/componentes/LeyendaProgramacionIntermedia.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/componentes/TablaProgramacionIntermedia.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/componentes/TarjetasProgramacionIntermedia.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/componentes/EditorActividadIntermedia.tsx` and test.
- `frontend/src/modules/programacion-intermedia/componentes/EditorHabilitacion.tsx` and test.
- `frontend/src/modules/programacion-intermedia/componentes/DialogoRestriccionCompartida.tsx` and
  test.
- `frontend/src/modules/programacion-intermedia/programacion-intermedia.css`.
- `tests/browser/fixtures/programacion-intermedia-react.mjs`.
- `tests/browser/programacion-intermedia-react.spec.mjs`.
- `tests/browser/programacion-intermedia-react.a11y.mjs`.
- `tests/browser/programacion-intermedia-react.visual.mjs` — candidates only before approval.

### Modify during implementation

- `public/index.php` — eight API routes; page route changes only in Task 14.
- `frontend/src/shell/rutas.tsx` and `.test.tsx` — pilot/canonical React route.
- `frontend/src/shell/NavegacionLateral.tsx` and its tests only if T01 has not already replaced the
  local role map with server navigation; do not add another S07 permission map.
- `src/Services/RestrictionConfigResolver.php` — expose one pure normalized resolver if its current
  API cannot serve both areas; preserve all keys/thresholds.
- `src/Core/SpaRouter.php` and route contract — canonical GET/HEAD cut in Task 14 only.
- T02 shared drawer files — consume extension points only; no S07 fork.
- `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — S07 closure ledger.
- `docs/design-system/manifests/programacion-intermedia.json` — React sources/layouts/states/tests.
- `docs/design-system/manifests/inventory.json`, `docs/design-system/ui-groups-inventory.json`,
  `docs/design-system/unlayered-delivery-inventory.json`, `docs/design-system/exceptions.json`,
  `docs/design-system/state-token-exceptions.json`, `docs/design-system/state-tint-exceptions.json`
  only where S07 legacy entries actually disappear.
- `docs/design-system/state-semantics.json` only if source paths, never state IDs/hues, must point to
  the React owner.
- Design-system tests that assert VIEW-35/CSS source paths: update at cut without weakening state,
  tint, severity or a11y assertions.

### Delete only after Task 14 gate

- `views/programacion-intermedia/programacion_intermedia.view.php`.
- `src/Controllers/Programacion/ProgramacionIntermediaController.php` only after all its actions have
  zero legacy callers or have moved behind the new services.
- `public/js/modules/programacion_intermedia/hot.js`.
- `public/js/modules/programacion_intermedia/stateMachine.js`.
- `public/css/programacion-intermedia.css`.
- Legacy-only PI browser tests replaced by the React suites, including the stale hidden-HOT runtime
  contract; preserve cross-module tests by updating their route adapter.
- Legacy route aliases only after a zero-caller search and the canonical rollback window closes.

Never delete `views/partials/drawer_unificado.php`, shared drawer JS/CSS, LPS endpoints,
`RestrictionConfigResolver`, report/catalog controllers, notification types, Handsontable/TomSelect
assets or test helpers while another module consumes them.

## API and Service Signatures

The implementation must preserve these boundaries:

```text
GET  /api/programacion-intermedia/context
GET  /api/programacion-intermedia/activities
POST /api/programacion-intermedia/activity
POST /api/programacion-intermedia/view
POST /api/programacion-intermedia/shared/preview
POST /api/programacion-intermedia/shared/apply
POST /api/programacion-intermedia/catalogs/refresh
POST /api/programacion-intermedia/report
```

```php
interface ProgramacionIntermediaReadStore
{
    public function projectContext(): array;
    public function weekContext(): array;
    public function activityRows(int $week, bool $viewAll): array;
    public function activity(int $week, int $activityId): ?array;
    public function activeSubcontractors(): array;
    public function activeProfessionals(): array;
    public function sharedTrackingAvailable(): bool;
}
```

```php
interface ProgramacionIntermediaWriteStore
{
    public function transaction(callable $operation): mixed;
    public function updateActivity(int $week, int $activityId, array $changes): array;
    public function createSharedConstraint(int $week, array $restriction, string $note, string $actor): int;
    public function linkSharedConstraint(int $sharedId, int $week, int $activityId, mixed $value): void;
}
```

```php
final class ProgramacionIntermediaStateResolver
{
    public function resolve(array $row, array $restrictionConfig): array;
    public function normalizeRestriction(string $key, mixed $value, array $restrictionConfig): int|float|string;
}
```

```php
final class ProgramacionIntermediaActionPolicy
{
    public function resolve(array $effectivePermissions, array $week, string $canonicalRole): array;
    public function forRow(array $actions, array $row): array;
}
```

```ts
export const obtenerContextoProgramacionIntermedia: (signal?: AbortSignal) => Promise<ContextoProgramacionIntermedia>;
export const listarActividadesIntermedias: (week: number, signal?: AbortSignal) => Promise<ListaProgramacionIntermedia>;
export const guardarActividadIntermedia: (payload: GuardarActividadIntermediaPayload, csrf: string) => Promise<ActividadIntermediaRaw>;
export const cambiarVistaIntermedia: (week: number, viewAll: boolean, csrf: string) => Promise<boolean>;
export const refrescarCatalogosIntermedios: (week: number, csrf: string) => Promise<CatalogosIntermediosRaw>;
export const previsualizarRestriccionCompartida: (payload: LoteIntermedioPayload, csrf: string) => Promise<PreviewLoteIntermedio>;
export const aplicarRestriccionCompartida: (payload: LoteIntermedioPayload, csrf: string) => Promise<ResultadoLoteIntermedio>;
export const descargarCorteIntermedio: (week: number, csrf: string) => Promise<ReporteIntermedio>;
```

## Task 1: Characterize restrictions, states and effective actions

**Files:**

- Create: `tests/fixtures/programacion_intermedia_state_cases.php`
- Create: `tests/test_programacion_intermedia_state_resolver.php`
- Create: `tests/test_programacion_intermedia_action_policy.php`
- Create: `src/Services/ProgramacionIntermediaStateResolver.php`
- Create: `src/Security/ProgramacionIntermediaActionPolicy.php`
- Modify: `src/Services/RestrictionConfigResolver.php` only if a pure normalized method is needed

- [ ] **Step 1: Write failing pure state/config/action cases**

  Cover Construction and Preconstruction, all eight states plus neutral, SI boundaries -1/0/1/2/3/
  4/6/7, execution 0/.5/1, critical/noncritical, hard/soft, threshold 50/100 and `N/A`. Assert soft
  restrictions never block readiness and aggregate excludes `N/A`.

  Policy matrix must cover viewer/editor/report, A/D versus R/DCV historical, confirmed/unconfirmed,
  no week, missing Responsible and effective override booleans. Assert no role/action leaves PHP.

- [ ] **Step 2: Run RED and record missing-class failures**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_state_resolver.php
  docker compose exec app php tests/test_programacion_intermedia_action_policy.php
  ```

  Expected: non-zero because resolver/policy do not exist. If a test passes against legacy globals,
  strengthen it to instantiate only the new pure classes.

- [ ] **Step 3: Implement the smallest pure resolvers**

  Reuse normalized entries from `RestrictionConfigResolver`; do not read session or DB inside either
  class. Return exact state `{id,label,severity}`, `ready`, ratio and normalized restrictions. Policy
  receives already evaluated capabilities/week context and uses canonical role only for the existing
  A/D historical exception.

- [ ] **Step 4: Run GREEN plus existing characterization**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_state_resolver.php
  docker compose exec app php tests/test_programacion_intermedia_action_policy.php
  docker compose exec app php tests/test_pi_responsable_aia_gate.php
  node --test tests/design-system/ops-state-contract.test.mjs tests/design-system/severity-rail.test.mjs
  git diff --check
  ```

  All must return RC 0. Review that no production SQL/session/data changed.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/RestrictionConfigResolver.php src/Services/ProgramacionIntermediaStateResolver.php src/Security/ProgramacionIntermediaActionPolicy.php tests/fixtures/programacion_intermedia_state_cases.php tests/test_programacion_intermedia_state_resolver.php tests/test_programacion_intermedia_action_policy.php
  git commit -m "refactor(programacion-intermedia): unifica estados y acciones"
  ```

## Task 2: Project-scoped context and activities contracts

**Files:**

- Create: `src/Services/ProgramacionIntermediaReadStore.php`
- Create: `src/Services/ProgramacionIntermediaWriteStore.php`
- Create: `src/Services/DatabaseProgramacionIntermediaStore.php`
- Create: `src/Services/ProgramacionIntermediaContextService.php`
- Create: `src/Services/ProgramacionIntermediaQueryService.php`
- Create: `src/Controllers/Api/ProgramacionIntermediaApiController.php`
- Create: `tests/test_programacion_intermedia_context_contract.php`
- Create: `tests/test_programacion_intermedia_activities_contract.php`
- Modify: `public/index.php`

- [ ] **Step 1: Write failing context/list contracts with in-memory store**

  Assert exact keys/types, `Cache-Control: no-store`, public project identity, active week, actions,
  dynamic config/catalogs/links/CSRF and no `db`, prefix, role, table or user. List cases cover
  default/viewAll, real `items=[]`, eight-state counts fixed to six weeks, PC rows and a query error
  returning the stable error envelope rather than empty data.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_context_contract.php
  docker compose exec app php tests/test_programacion_intermedia_activities_contract.php
  ```

  Expected: missing controller/services/routes. No DB constructor in test fixtures.

- [ ] **Step 3: Implement scoped read services and two GET routes**

  Register:

  ```php
  $router->get('/api/programacion-intermedia/context', [ProgramacionIntermediaApiController::class, 'context']);
  $router->get('/api/programacion-intermedia/activities', [ProgramacionIntermediaApiController::class, 'activities']);
  ```

  `DatabaseProgramacionIntermediaStore` resolves active `ProjectScope`/request context once. Every
  SQL names `project_id = ?`, uses prepared statements and selects explicit columns. `week` is only a
  stale guard. Query service computes state with Task 1 and counts a separate six-week universe.

- [ ] **Step 4: Run GREEN and scope/static checks**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_context_contract.php
  docker compose exec app php tests/test_programacion_intermedia_activities_contract.php
  docker compose exec app php tests/test_pi_shared_apply_project_scope.php
  docker compose exec app php tests/test_week_context_write_scope.php
  git diff --check
  ```

  Inspect source to prove context/list have no writes and list does not use `SELECT *`.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaReadStore.php src/Services/ProgramacionIntermediaWriteStore.php src/Services/DatabaseProgramacionIntermediaStore.php src/Services/ProgramacionIntermediaContextService.php src/Services/ProgramacionIntermediaQueryService.php src/Controllers/Api/ProgramacionIntermediaApiController.php tests/test_programacion_intermedia_context_contract.php tests/test_programacion_intermedia_activities_contract.php public/index.php
  git commit -m "feat(programacion-intermedia): agrega contexto y lista scoped"
  ```

## Task 3: Strict Zod schemas, gateway and normalized domain

**Files:**

- Create: `frontend/src/lib/api/esquemas/programacion-intermedia.ts`
- Create: `frontend/src/lib/api/esquemas/programacion-intermedia.test.ts`
- Create: `frontend/src/lib/api/programacion-intermedia.ts`
- Create: `frontend/src/lib/api/programacion-intermedia.test.ts`
- Create: `frontend/src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.ts`
- Create: `frontend/src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.test.ts`

- [ ] **Step 1: Write failing strict schema/gateway/domain tests**

  Fixtures cover empty/full context, both areas, `NA`, mixed legacy numeric inputs only at the PHP
  adapter boundary, all state IDs/actions, safe report path and error envelopes. Reject unknown state,
  ratio >1, invalid ISO date, duplicate restriction key, missing count, external URL and unexpected
  fields. Gateway asserts only allowed `week` query, JSON, CSRF header, AbortSignal and no scope.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.test.ts
  ```

  Expected: missing modules.

- [ ] **Step 3: Implement minimal schemas/gateway/normalizer**

  Use `.strict()` at object boundaries and `z.infer` exports only. Gateway calls the common client;
  normalizer maps DTOs to immutable screen data and preserves IDs/order/actions. Do not duplicate
  readiness or state classification.

- [ ] **Step 4: Run GREEN and typecheck/fetch audit**

  ```bash
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.test.ts
  npm --prefix frontend run typecheck
  rg -n "fetch\(" frontend/src --glob '!lib/api/cliente.ts'
  git diff --check
  ```

  Tests/typecheck RC 0; `rg` must produce no direct fetch caller.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts frontend/src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.ts frontend/src/modules/programacion-intermedia/dominio/normalizarProgramacionIntermedia.test.ts
  git commit -m "feat(programacion-intermedia): tipa contratos y dominio"
  ```

## Task 4: Read-only vertical slice with responsive table and cards

**Files:**

- Create: `frontend/src/modules/programacion-intermedia/useProgramacionIntermedia.ts`
- Create: `frontend/src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/ProgramacionIntermediaPage.tsx`
- Create: `frontend/src/modules/programacion-intermedia/ProgramacionIntermediaPage.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/TablaProgramacionIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/TablaProgramacionIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/TarjetasProgramacionIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/TarjetasProgramacionIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/programacion-intermedia.css`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

- [ ] **Step 1: Write failing route/hook/table/card states**

  Cover pilot route, initial/list skeletons, true empty, error/retry, read-only actions, stale public
  identity, table at desktop/tablet and cards at mobile. Assert mobile has no table node and both
  layouts expose Id/activity/weeks/execution/readiness/state/assignments/restrictions.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/ProgramacionIntermediaPage.test.tsx src/modules/programacion-intermedia/componentes/TablaProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/componentes/TarjetasProgramacionIntermedia.test.tsx src/shell/rutas.test.tsx
  ```

- [ ] **Step 3: Implement the read-only pilot slice**

  Add `/app/programacion-intermedia`. Hook loads context then list, aborts on unmount/context change
  and discards mismatched project/week. Use `matchMedia`/existing responsive primitive to render one
  layout, not CSS-hide both. CSS consumes tokens only and confines tablet scroll to the table shell.

- [ ] **Step 4: Run GREEN, typecheck and token/static checks**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/shell/rutas.test.tsx
  npm --prefix frontend run typecheck
  rg -n "#[0-9a-fA-F]{3,8}|rgb\(|hsl\(|style=|!important|Handsontable|TomSelect|Bootstrap" frontend/src/modules/programacion-intermedia
  git diff --check
  ```

  The style/vendor audit must return no product-code match.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/modules/programacion-intermedia frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
  git commit -m "feat(programacion-intermedia): agrega lectura responsive"
  ```

## Task 5: Filters, counts, severity, legend, selection and CSV

**Files:**

- Create: `frontend/src/modules/programacion-intermedia/dominio/filtrarProgramacionIntermedia.ts`
- Create: `frontend/src/modules/programacion-intermedia/dominio/filtrarProgramacionIntermedia.test.ts`
- Create: `frontend/src/modules/programacion-intermedia/dominio/exportarProgramacionIntermediaCsv.ts`
- Create: `frontend/src/modules/programacion-intermedia/dominio/exportarProgramacionIntermediaCsv.test.ts`
- Create: `frontend/src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/FiltrosProgramacionIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/FiltrosProgramacionIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/LeyendaProgramacionIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/LeyendaProgramacionIntermedia.test.tsx`
- Modify: page/hook/table/cards/CSS from Task 4

- [ ] **Step 1: Write failing domain/component cases**

  Cover accent/case-insensitive search, state multi-select without modifier, SI bands, ready,
  subcontractor, responsible/missing, pending restriction, combined filters, stable severity order,
  clear, server window counts versus visible count, selection intersection and selected-visible only.
  CSV covers BOM, CRLF, quotes, commas/newlines, dynamic PC columns and mobile without table.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia/dominio/filtrarProgramacionIntermedia.test.ts src/modules/programacion-intermedia/dominio/exportarProgramacionIntermediaCsv.test.ts src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/componentes/FiltrosProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/componentes/LeyendaProgramacionIntermedia.test.tsx
  ```

- [ ] **Step 3: Implement pure filtering/export and accessible controls**

  Keep source rows immutable; memoize filtered/sorted output. Persist filters in the route search
  params using T01 utilities, excluding viewAll. Legend groups P1/P2/P3 and soft restrictions,
  explains thresholds and restores focus. CSV exports visible normalized rows only.

- [ ] **Step 4: Run GREEN and focused module suite**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia
  npm --prefix frontend run typecheck
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): agrega filtros leyenda y csv"
  ```

## Task 6: Narrow individual mutation and server-side guards

**Files:**

- Create: `src/Services/ProgramacionIntermediaMutationService.php`
- Create: `tests/test_programacion_intermedia_save_contract.php`
- Modify: `src/Services/ProgramacionIntermediaWriteStore.php`
- Modify: `src/Services/DatabaseProgramacionIntermediaStore.php`
- Modify: `src/Controllers/Api/ProgramacionIntermediaApiController.php`
- Modify: `public/index.php`
- Modify: `frontend/src/lib/api/esquemas/programacion-intermedia.ts` and tests
- Modify: `frontend/src/lib/api/programacion-intermedia.ts` and tests

- [ ] **Step 1: Write failing save contracts and gateway tests**

  Cases: assignment only without Responsible, restriction blocked without Responsible, assigning
  Responsible plus restriction, invalid/unknown restriction/value, inactive catalog value, preserved
  legacy value when unchanged, 2.000-char observation, confirmed/historical/stale/forbidden/CSRF,
  not found, merge narrowness, recalculated full DTO and transaction exception. Assert no auto retry.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_save_contract.php
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts
  ```

- [ ] **Step 3: Implement POST activity with fakeable store**

  Register `POST /api/programacion-intermedia/activity`. Parse strict JSON, verify CSRF/RBAC/policy,
  read current row, whitelist/normalize changes, validate catalogs/config, write in transaction and
  return Task 2 DTO. Do not delegate a raw `$_POST` to the legacy script. Extract legacy alert/
  recalculation behind service seams without changing behavior.

- [ ] **Step 4: Run GREEN and negative scope checks**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_save_contract.php
  docker compose exec app php tests/test_programacion_intermedia_state_resolver.php
  docker compose exec app php tests/test_pi_responsable_aia_gate.php
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaMutationService.php src/Services/ProgramacionIntermediaWriteStore.php src/Services/DatabaseProgramacionIntermediaStore.php src/Controllers/Api/ProgramacionIntermediaApiController.php public/index.php tests/test_programacion_intermedia_save_contract.php frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts
  git commit -m "feat(programacion-intermedia): guarda actividades con guardas"
  ```

## Task 7: Editable table/cards and save recovery

**Files:**

- Create: `frontend/src/modules/programacion-intermedia/componentes/EditorActividadIntermedia.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/EditorActividadIntermedia.test.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/EditorHabilitacion.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/EditorHabilitacion.test.tsx`
- Modify: hook/page/table/cards/CSS

- [ ] **Step 1: Write failing editable-layout cases**

  Cover assignment/observation/restriction changes in table and mobile card, same validation in both,
  missing Responsable explanation/unlock in one request, keyboard previous/next, cancel before save,
  per-row pending, duplicate-submit block, success replacement, 422 field error, 409 reload prompt,
  network revert+retry, confirmed transition and dirty navigation guard.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia/componentes/EditorActividadIntermedia.test.tsx src/modules/programacion-intermedia/componentes/EditorHabilitacion.test.tsx src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/componentes/TablaProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/componentes/TarjetasProgramacionIntermedia.test.tsx
  ```

- [ ] **Step 3: Implement one shared editor state machine**

  Table/cards open the same editor with normalized row/config/actions. Keep one local draft; serialize
  mutations per activity; replace only from server DTO; maintain focused/open activity after save.
  No optimistic state classification. Announce save states through a polite live region.

- [ ] **Step 4: Run GREEN, typecheck and mobile no-grid assertion**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia
  npm --prefix frontend run typecheck
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): habilita edicion responsive"
  ```

## Task 8: CSRF viewAll and explicit catalog refresh

**Files:**

- Create: `src/Services/ProgramacionIntermediaCatalogService.php`
- Create: `tests/test_programacion_intermedia_view_contract.php`
- Create: `tests/test_programacion_intermedia_catalogs_contract.php`
- Modify: controller/routes/context service
- Modify: S07 schemas/gateway tests
- Modify: toolbar/hook/page tests

- [ ] **Step 1: Write failing contracts/UI cases**

  View cases: POST only, CSRF, edit action, confirmed/stale, session key only, no DB write, list reload
  and selection reset. Catalog cases: initial context never calls sync, explicit refresh calls fake once,
  scoped returned names, independent partial failure, create links/action, forbidden/CSRF and no raw
  exception. Viewer sees disabled/absent mutating controls but retains displayed assignments.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_view_contract.php
  docker compose exec app php tests/test_programacion_intermedia_catalogs_contract.php
  npm --prefix frontend test -- --run src/lib/api/programacion-intermedia.test.ts src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx
  ```

- [ ] **Step 3: Implement two POST endpoints and UI orchestration**

  Register `/view` and `/catalogs/refresh`. View writes only `$_SESSION['pi_view_all']`. Catalog
  service wraps existing professional sync behind an injected interface, then reads both catalogs;
  no GET side effect. UI keeps ordinary Reload pure and exposes “Actualizar listas” separately.

- [ ] **Step 4: Run GREEN**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_view_contract.php
  docker compose exec app php tests/test_programacion_intermedia_catalogs_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/programacion-intermedia.test.ts
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaCatalogService.php src/Services/ProgramacionIntermediaContextService.php src/Controllers/Api/ProgramacionIntermediaApiController.php public/index.php tests/test_programacion_intermedia_view_contract.php tests/test_programacion_intermedia_catalogs_contract.php frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): protege vista y catálogos"
  ```

## Task 9: Shared batch preview contract and dialog

**Files:**

- Create: `src/Services/ProgramacionIntermediaBatchService.php`
- Create: `tests/test_programacion_intermedia_shared_preview_contract.php`
- Create: `frontend/src/modules/programacion-intermedia/componentes/DialogoRestriccionCompartida.tsx`
- Create: `frontend/src/modules/programacion-intermedia/componentes/DialogoRestriccionCompartida.test.tsx`
- Modify: controller/routes/schemas/gateway/hook/page

- [ ] **Step 1: Write failing no-write preview and dialog cases**

  Validate 2..500 unique positive IDs, at least one applied group, nonempty applied values, config
  keys/values, 1.000-char note, catalog values, found/missing, current/predicted rows, conflicts,
  missing Responsible, canApply/reasons and both areas. Fake write store must record zero calls.
  Dialog covers selected count, dynamic restrictions, assignments-only, combined unlock, blocked
  preview, focus trap/restore and dirty close confirmation.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_shared_preview_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia/componentes/DialogoRestriccionCompartida.test.tsx src/lib/api/programacion-intermedia.test.ts
  ```

- [ ] **Step 3: Implement read-only preview and dialog**

  Register `POST /api/programacion-intermedia/shared/preview`; require view + CSRF, but never call a
  write method. Normalize through Task 1 and compute predicted states. Dialog receives visible
  selection and never sends project/scope/role/config labels.

- [ ] **Step 4: Run GREEN and prove zero writes**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_shared_preview_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/programacion-intermedia.test.ts
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaBatchService.php src/Controllers/Api/ProgramacionIntermediaApiController.php public/index.php tests/test_programacion_intermedia_shared_preview_contract.php frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): agrega preview compartido"
  ```

## Task 10: Atomic shared apply, tracking, audit and notifications

**Files:**

- Create: `src/Services/ProgramacionIntermediaNotifier.php`
- Create: `tests/test_programacion_intermedia_shared_apply_contract.php`
- Modify: batch service/write store/database store/controller/routes
- Modify: schemas/gateway/dialog/hook tests

- [ ] **Step 1: Write failing atomic apply cases**

  Cover preview/apply parity, edit/confirmed/historical/stale/CSRF, missing row, missing Responsible,
  assign+restrict unlock, config/catalog drift, all-or-nothing fake transaction, recalculated DTOs,
  shared record per restriction, links per row, scoped actor, deduplicated recipients, tracking
  available/unavailable warning and exception rollback. Assert SQL details never appear.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_shared_apply_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia/componentes/DialogoRestriccionCompartida.test.tsx src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx src/lib/api/programacion-intermedia.test.ts
  ```

- [ ] **Step 3: Implement apply behind transaction and notifier seam**

  Register `/shared/apply`. Re-read/revalidate before transaction. Create no table; ask
  `sharedTrackingAvailable()`. Write rows and available tracking, commit, then dispatch audit/
  notification through injected notifier with session actor/project. Return full rows and warning.
  If notifier fails after commit, report a typed notification warning without pretending rollback.

- [ ] **Step 4: Run GREEN and legacy payload/scope characterization**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_shared_apply_contract.php
  docker compose exec app php tests/test_pi_shared_payload_smoke.php
  docker compose exec app php tests/test_pi_shared_apply_project_scope.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/programacion-intermedia.test.ts
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaNotifier.php src/Services/ProgramacionIntermediaBatchService.php src/Services/ProgramacionIntermediaWriteStore.php src/Services/DatabaseProgramacionIntermediaStore.php src/Controllers/Api/ProgramacionIntermediaApiController.php public/index.php tests/test_programacion_intermedia_shared_apply_contract.php frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): aplica lotes atomicos"
  ```

## Task 11: Scoped XLSX report download

**Files:**

- Create: `src/Services/ProgramacionIntermediaReportService.php`
- Create: `tests/test_programacion_intermedia_report_contract.php`
- Modify: controller/routes/context actions
- Modify: schemas/gateway/toolbar/hook tests

- [ ] **Step 1: Write failing report contracts/UI cases**

  Assert report permission independent from PI edit, POST/CSRF, stale week, server-derived project/
  week, injected fake generator, dynamic labels request, exact same-origin directory/suffix, filename,
  `{error}` adaptation, unsafe URL rejection, pending/double-click/error/retry. Fake must create no file.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_report_contract.php
  npm --prefix frontend test -- --run src/lib/api/programacion-intermedia.test.ts src/modules/programacion-intermedia/componentes/ToolbarProgramacionIntermedia.test.tsx src/modules/programacion-intermedia/useProgramacionIntermedia.test.tsx
  ```

- [ ] **Step 3: Implement report wrapper and client action**

  Register `/report`; adapt existing restriction report generator through injected callable/service.
  Do not pass `db` from browser. Validate returned relative path before response and use a normal
  same-origin download/navigation after Zod succeeds.

- [ ] **Step 4: Run GREEN**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_report_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/programacion-intermedia.test.ts
  git diff --check
  ```

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add src/Services/ProgramacionIntermediaReportService.php src/Controllers/Api/ProgramacionIntermediaApiController.php public/index.php tests/test_programacion_intermedia_report_contract.php frontend/src/lib/api/esquemas/programacion-intermedia.ts frontend/src/lib/api/esquemas/programacion-intermedia.test.ts frontend/src/lib/api/programacion-intermedia.ts frontend/src/lib/api/programacion-intermedia.test.ts frontend/src/modules/programacion-intermedia
  git commit -m "feat(programacion-intermedia): descarga corte scoped"
  ```

## Task 12: Integrate the shared T02 drawer with PI context

**Prerequisite:** T02 shared drawer files and contracts exist and are green. Do not start otherwise.

**Files:**

- Modify: T02 drawer context adapter/types/tests at their actual paths
- Modify: `frontend/src/modules/programacion-intermedia/ProgramacionIntermediaPage.tsx`
- Modify: `frontend/src/modules/programacion-intermedia/useProgramacionIntermedia.ts`
- Modify: table/cards and tests
- Modify: `tests/browser/fixtures/programacion-intermedia-react.mjs`

- [ ] **Step 1: Write failing PI drawer integration cases**

  Cover keyboard row/card selection, `module="PI"`, activity/week/state/restrictions/contacts,
  read-only versus write actions, comments/replies/mentions, SOS triggers, 100-char close, BI link,
  filtered-selected notice, clear on project/week change, local digest and independent drawer error.

- [ ] **Step 2: Run RED using T02's focused test entrypoints**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/shared/drawer-lps
  ```

  If the T02 path differs, use its real documented path; never create `src/shared/drawer-lps` just to
  satisfy this placeholder.

- [ ] **Step 3: Add the narrow PI adapter**

  Map normalized activity to T02 context, pass effective actions/CSRF, and use T02 gateway. Keep
  `consecutivo`/escalation identity compatibility at the adapter only. S07 does not alter shared
  endpoint payloads or copy drawer components/styles.

- [ ] **Step 4: Run GREEN with S05/T02 regression**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/shared/drawer-lps src/modules/programa-general
  npm --prefix frontend run typecheck
  git diff --check
  ```

  Adjust paths to the real T02/S05 modules. All relevant suites RC 0.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/modules/programacion-intermedia frontend/src/shared/drawer-lps tests/browser/fixtures/programacion-intermedia-react.mjs
  git commit -m "feat(programacion-intermedia): integra drawer contextual"
  ```

  Stage the actual T02 path only; do not use a nonexistent placeholder.

## Task 13: Intercepted RBAC, responsive, a11y and visual approval gate

**Files:**

- Create: `tests/browser/fixtures/programacion-intermedia-react.mjs`
- Create: `tests/browser/programacion-intermedia-react.spec.mjs`
- Create: `tests/browser/programacion-intermedia-react.a11y.mjs`
- Create: `tests/browser/programacion-intermedia-react.visual.mjs`
- Modify: React module/tests only for failures proven here
- Modify: `tests/test_programacion_intermedia_routes.php`

- [ ] **Step 1: Write failing full-route intercepted scenarios**

  Install intercepts before navigation for session/context/list/every POST/drawer. Cover viewer,
  editor, denied, A/D historical, R/DCV historical denied, confirmed, Construction, PC, eight states,
  neutral future, viewAll counts, filters, selection, individual save, batch, tracking warning,
  catalogs, CSV, report, drawer, errors and project/week invalidation. Assert no unmatched network.

- [ ] **Step 2: Add a11y/responsive/theme candidate assertions**

  Validate 390/768/1180/1440 in dark/light, one layout only, no page overflow, 200 % zoom, keyboard
  flow, dialog/drawer focus, 44 px targets, reduced motion, live regions and Axe. Candidate screenshots
  show representative eight states, filters, editor and batch; do not write approved golden paths.

- [ ] **Step 3: Run focused gates and fix only evidenced S07 defects**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_routes.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts
  npm --prefix frontend run typecheck
  npx playwright test tests/browser/programacion-intermedia-react.spec.mjs tests/browser/programacion-intermedia-react.a11y.mjs tests/browser/programacion-intermedia-react.visual.mjs --workers=1
  git diff --check
  ```

  Read each RC separately. Browser log, console and network must be clean. Do not broaden into live
  operational-cycle suites.

- [ ] **Step 4: Stop for explicit visual approval before baselines/cut**

  Present dark/light candidates at required viewports and enumerate intentional differences from
  VIEW-35. Without explicit approval, do not update goldens and do not start Task 14. Functional
  implementation may remain at pilot route.

- [ ] **Step 5: Future atomic QA commit after approval**

  ```bash
  git add tests/test_programacion_intermedia_routes.php tests/browser/fixtures/programacion-intermedia-react.mjs tests/browser/programacion-intermedia-react.spec.mjs tests/browser/programacion-intermedia-react.a11y.mjs tests/browser/programacion-intermedia-react.visual.mjs frontend/src/modules/programacion-intermedia
  git commit -m "test(programacion-intermedia): cubre paridad react"
  ```

  Approved screenshot paths are staged only if Felipe explicitly authorizes their replacement.

## Task 14: Canonical SPA cut, manifests and exclusive legacy retirement

**Files:**

- Modify: `src/Core/SpaRouter.php` and route contracts
- Modify: `public/index.php`
- Modify: `frontend/src/shell/rutas.tsx` and tests
- Modify: `docs/design-system/manifests/programacion-intermedia.json`
- Modify: applicable design-system inventories/exceptions/state source assertions
- Modify: `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md`
- Delete only zero-caller exclusive legacy files listed above

- [ ] **Step 1: Capture zero-caller and shared-owner evidence before deletion**

  ```bash
  rg -n "programacion_intermedia\.view|ProgramacionIntermediaController|programacion_intermedia/hot|programacion_intermedia/stateMachine|programacion-intermedia\.css|/api/pi/|programacion-intermedia/(filtros|set-filtro|set-view-all|shared-constraints)" public src views frontend tests docs --glob '!docs/superpowers/plans/2026-08-30-s07-programacion-intermedia-react.md' --glob '!docs/superpowers/specs/2026-08-30-s07-programacion-intermedia-react-design.md'
  rg -n "drawer_unificado|lps_drawer|api/lps/comments|api/lps/crisis|RestrictionConfigResolver|reportes/restricciones|api/profesionales/list|api/subcontratistas/list" public src views frontend tests docs
  ```

  Classify every match. Keep aliases/files with any active caller. Do not weaken a shared test merely
  because its source path changes.

- [ ] **Step 2: Write failing canonical route/manifest tests**

  Assert GET/HEAD `/programacion-intermedia` serves SPA, API stays JSON, unauthorized bootstrap cannot
  load data, manifest points to React sources/layouts/tests, eight state semantics remain, and VIEW-35
  is absent only after all exclusive callers move.

- [ ] **Step 3: Perform the smallest reversible cut**

  Route canonical GET/HEAD through SPA, retain pilot alias if T01 policy requires it, update manifest/
  inventories, remove only proven-exclusive VIEW-35/controller actions/JS/CSS/tests and leave shared
  drawer/report/catalog/config/notification assets. Record rollback as reverting this cut commit.

- [ ] **Step 4: Run post-cut gate from fresh outputs**

  ```bash
  docker compose exec app php tests/test_programacion_intermedia_state_resolver.php
  docker compose exec app php tests/test_programacion_intermedia_action_policy.php
  docker compose exec app php tests/test_programacion_intermedia_context_contract.php
  docker compose exec app php tests/test_programacion_intermedia_activities_contract.php
  docker compose exec app php tests/test_programacion_intermedia_save_contract.php
  docker compose exec app php tests/test_programacion_intermedia_view_contract.php
  docker compose exec app php tests/test_programacion_intermedia_catalogs_contract.php
  docker compose exec app php tests/test_programacion_intermedia_shared_preview_contract.php
  docker compose exec app php tests/test_programacion_intermedia_shared_apply_contract.php
  docker compose exec app php tests/test_programacion_intermedia_report_contract.php
  docker compose exec app php tests/test_programacion_intermedia_routes.php
  npm --prefix frontend test -- --run src/modules/programacion-intermedia src/lib/api/esquemas/programacion-intermedia.test.ts src/lib/api/programacion-intermedia.test.ts
  npm --prefix frontend run typecheck
  node --test tests/design-system/contracts.test.mjs tests/design-system/ops-state-contract.test.mjs tests/design-system/severity-rail.test.mjs tests/design-system/state-tint-ladder.test.mjs
  npx playwright test tests/browser/programacion-intermedia-react.spec.mjs tests/browser/programacion-intermedia-react.a11y.mjs tests/browser/programacion-intermedia-react.visual.mjs --workers=1
  git diff --check
  git status --short
  ```

  Read each RC on its own line. Inspect network/screenshots, prove no real POST/report/sync occurred,
  and confirm reverting the route/cut commit restores VIEW-35 without touching shared APIs/data.

- [ ] **Step 5: Future atomic cut commit and front closure workflow**

  Stage selectively after reviewing every deletion and manifest diff:

  ```bash
  git add src/Core/SpaRouter.php public/index.php frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx docs/design-system/manifests/programacion-intermedia.json docs/design-system/manifests/inventory.json docs/design-system/ui-groups-inventory.json docs/design-system/unlayered-delivery-inventory.json docs/design-system/exceptions.json docs/design-system/state-token-exceptions.json docs/design-system/state-tint-exceptions.json docs/design-system/state-semantics.json docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
  git add -u views/programacion-intermedia src/Controllers/Programacion/ProgramacionIntermediaController.php public/js/modules/programacion_intermedia public/css/programacion-intermedia.css tests/browser tests/design-system
  git commit -m "feat(programacion-intermedia): corta la ruta canonica a React"
  ```

  Omit unchanged/shared files from staging. Then follow the repository front-closing policy: clean
  status, fetch, inspect divergence, integrate `origin/main` into this branch, re-run the entire
  post-integration gate, record verified SHA, push branch, open PR to `main`, wait for CI green and
  merge only with authorization. Production deployment remains separately authorized.

## Vertical Checkpoints

| Checkpoint | Outcome | Required evidence | Legacy/cut state |
|---|---|---|---|
| 1 | Read-only look-ahead | state/policy/context/list/Zod/filter/responsive tests | VIEW-35 canonical |
| 2 | Individual edit | fake save contract + table/card recovery | VIEW-35 canonical |
| 3 | Shared operations | view/catalog/preview/apply/report contracts and UI | VIEW-35 canonical |
| 4 | Drawer + quality | T02 integration, RBAC/a11y/theme/visual approval | VIEW-35 canonical |
| 5 | Approved cut | zero callers, manifests, post-cut gate | React canonical |

No checkpoint executes DML. Green fake/intercepted mutations prove contract/orchestration, not live
database persistence. Any future live smoke needs separate authority and a disposable, reconciled
environment.

## Traceability Matrix

| Spec criteria | Plan tasks |
|---|---|
| AC-01..04 route/navigation/scope/invalidation | 2–4, 13–14 |
| AC-05..08 universe/empty/viewAll/counts | 2–5, 8 |
| AC-09..13 states/readiness/NA/areas | 1–4, 13 |
| AC-14..16 filters/severity/selection | 5, 13 |
| AC-17..18 table/tablet/mobile | 4, 7, 13 |
| AC-19..24 validation/save/locks/recovery | 6–7, 13 |
| AC-25..27 preview/apply/tracking | 9–10, 13 |
| AC-28 catalogs | 8, 13 |
| AC-29..31 CSV/report/legend | 5, 11, 13 |
| AC-32 drawer | 12–13 |
| AC-33..35 states/a11y/themes | 4–5, 7, 12–13 |
| AC-36..40 contracts/no-DML/cut/no-RLS | every task, especially 13–14 |

## Verification Commands Explicitly Forbidden in S07

Do not run these during plan execution because they mutate shared data/session/files or broaden scope:

```text
npx playwright test tests/browser/preconstruccion-full-cycle.mjs
npx playwright test tests/browser/full-app-flow.spec.mjs
tests/browser/support/operationalCycle.mjs or any caller of runProgramacionIntermedia against Docker
manual POST to /api/pi/save or /api/programacion-intermedia/* against the mounted application
manual POST to /reportes/restricciones or /api/programacion-intermedia/report
manual POST to /api/profesionales/list or /api/programacion-intermedia/catalogs/refresh
manual drawer comment/crisis calls
composer-wide suites whose DB write behavior has not been classified
```

If an executor believes live mutation is necessary, stop and request new authority. Rollback DML is
still DML and is not a workaround.

## Self-Review Checklist for the Executor

Before claiming S07 complete, prove from fresh output:

- correct worktree/branch and no unrelated diff included;
- all eight endpoints have PHP contracts and exact Zod schemas;
- no direct fetch outside the common client;
- no client scope/auth fields;
- page/sidebar use server authorization;
- state/config fixtures cover both areas/eight states/neutral/thresholds/NA;
- counts always use six-week universe and visibleCount is separate;
- save policy covers confirmed, historical, missing Responsible and stale context;
- payload is narrow and returned row is server-recalculated;
- table and cards have identical edits; mobile mounts no hidden table;
- batch preview makes zero writes and apply is atomic/revalidated;
- no tracking DDL; warning is observable;
- catalog GET is pure and refresh uses an explicit seam;
- CSV works independent of layout; report URL/scope/permission are safe;
- T02 drawer is shared, not copied, and works in both areas;
- dark/light at four viewports, keyboard/Axe/zoom/reduced motion/no overflow;
- visual approval predates any golden update;
- zero-caller evidence predates every legacy deletion;
- shared drawer/report/catalog/config/notification assets remain;
- focused and post-cut commands return RC 0 separately;
- no RLS/schema/grant/user/credential/admin/data change;
- no DDL/DML executed;
- closure ledger and verified SHA are recorded only after actual implementation/cut.

## Cierre

**Estado documental 2026-08-30:** plan escrito y autorrevisado; implementación no iniciada. Esta
sección sólo se actualizará con evidencia real de una sesión de ejecución autorizada. Las casillas
anteriores no se marcarán retroactivamente ni se usarán como prueba de avance.
