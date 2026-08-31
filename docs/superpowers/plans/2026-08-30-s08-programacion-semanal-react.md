---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-08-30-s08-programacion-semanal-react.md
resumen: "migrate /programacion-semanal from VIEW-39/40/41, Handsontable and global JavaScript to a native React weekly plan with server-authoritative phases, states…"
---

# S08 Programación Semanal React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Execute tasks in order and stop at the stated review gates.
> Checkbox syntax is an execution prompt only; repository progress and closure are recorded in the
> plan `## Cierre` ledger and git history, never inferred from checkbox counts.

**Goal:** migrate `/programacion-semanal` from VIEW-39/40/41, Handsontable and global JavaScript to
a native React weekly plan with server-authoritative phases, states, quantities and actions;
individual planning/qualification, manual/CNP/CNC/TNP flows, explicit reconciliation, transactional
close/CIC, reopen, CSV/XLSX, shared drawer, responsive table/cards and dark/light parity, without
changing RLS, schema or live data during verification.

**Architecture:** T01 supplies shell/session/project/week/theme/navigation; S05 canonical activity
identity; S06 the week lifecycle; S07 normalized restriction config; T02 the shared drawer. S08 adds
a scoped read/write adapter, one pure weekly state/projection/quantity layer, one effective-action
policy and narrow orchestration services behind 16 endpoints. React parses every wire contract with
Zod, keeps one normalized row store, renders a semantic table at 768+ and fully editable cards below
768. Reconciliation is preview-read plus explicit transactional apply; loading never writes. Legacy
VIEW-39/40/41 and aliases remain for pilot rollback until functional, RBAC, accessibility and
explicitly approved visual gates pass.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe, PhpSpreadsheet 5.4 and AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s08-programacion-semanal-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia` or another checkout.
- Execute only after T01, S05, S06 and S07 contracts/files are present and green. Task 14 additionally
  requires T02's shared React drawer contract. If T02 is absent, stop at checkpoint 4; do not fork or
  copy the legacy drawer into S08.
- Inspect `git status --short` and relevant diffs before every task. Preserve pre-existing/unrelated
  edits; do not clean, revert or refactor adjacent work.
- This session is documentation-only. Do not implement, commit, push, publish or deploy now. Commit
  commands below are future execution instructions and require implementation authorization.
- `/admin/` is excluded: no files, routes, styles, tests, dependencies or permissions there.
- Do not modify RLS, `ProjectScope` semantics, schema, migrations, tables, columns, indexes, triggers,
  grants, users, credentials, memberships, roles, overrides or database data. Do not execute DDL/DML
  during documentation or verification.
- Never create, alter or self-heal `auto_program_log` at request time. The canonical global schema is
  a precondition; fail with `SCHEMA_PREREQUISITE_MISSING` if it is absent.
- Never create subcontratistas/profesionales, placeholder contacts or catalog rows from S08. A missing
  reference blocks close and links to the owning module.
- PHP is authoritative for project, prefix, area, week/max/dates/phase, row/source ownership,
  restrictions, projections, sums, PAC, state, permissions, historical editing, close/reopen and
  report scope. React never sends those context fields as authority.
- Keep existing capabilities unchanged. Use `lps.programacion_semanal.ver/editar`,
  `lps.reportes.generar`, `LpsWeekEditPolicy`, `SemanalReabrirPolicy` and T02 drawer actions. Resolve
  aliases with `RbacService::normalizeRole()` only in PHP policy; never expose roles as frontend
  authority.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Components/hooks call the typed S08
  gateway. All TypeScript wire/domain types come from `z.infer`.
- Every endpoint begins with a failing PHP contract. Every Zod schema, gateway, domain function,
  hook and component begins with a failing Vitest. No implementation-before-RED.
- Mutations never retry automatically. Browser scenarios intercept every S08 mutation, report and
  T02 write before navigation; no real request may reach a write path.
- PHP mutation tests use fake stores, clock, logger, notifier, carryover synchronizer, CIC writer and
  report generator. No test connects to a live DB for writes; rollback writes are also forbidden.
- Preserve exactly five programming states, five qualification states, `ps-no-activa`, phase order,
  hard/soft thresholds, `N/A`, execution-readiness exception, TNP and severity semantics.
- Preserve numeric behavior: localized input, one decimal, normal commitment >0, normal real >=0,
  TNP real >0, split sums and `%`/budget ceilings. PHP must enforce every limit.
- Loading `/app/programacion-semanal`, context, activities, reconciliation preview/log and close
  preview must be read-only. A test must fail if they call any write adapter.
- Use only tokens from `public/css/tokens.css`; no literal colors, inline style objects,
  `!important`, Bootstrap, jQuery, Handsontable, Select2, Toastr, Font Awesome, CSS-in-JS or new grid,
  query or state dependencies.
- Dark is default/fallback; light has identical capability. Required viewports: `390×844`,
  `768×1024`, `1180×820`, `1440×900`; no horizontal page overflow.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output outside git until Task 15's gate is approved.
- Do not delete CNP/CNC/CIC routes/views/controllers/styles, `legacyCards.js`, T02 drawer or shared
  policies/services while another module consumes them.
- No real dev-door login, DB probe, week/project change, report file, reconciliation, close/reopen,
  TNP/CNP/CNC/CIC or drawer write is part of S08 verification. Playwright installs full interception
  before navigation.

## File Structure

### Create — PHP

- `src/Security/ProgramacionSemanalActionPolicy.php` — pure phase/week/effective-action resolver.
- `src/Services/ProgramacionSemanalReadStore.php` — scoped reads and reconciliation facts interface.
- `src/Services/ProgramacionSemanalWriteStore.php` — transaction/write/CIC/log interface.
- `src/Services/DatabaseProgramacionSemanalStore.php` — sole S08 SQL adapter.
- `src/Services/ProgramacionSemanalStateResolver.php` — eleven canonical states/readiness.
- `src/Services/ProgramacionSemanalProjectionService.php` — safe weekly projection DTO.
- `src/Services/ProgramacionSemanalQuantityValidator.php` — localized values and split ceilings.
- `src/Services/ProgramacionSemanalContextService.php` — context/actions/catalogs/links/CSRF.
- `src/Services/ProgramacionSemanalQueryService.php` — stable rows/counts.
- `src/Services/ProgramacionSemanalMutationService.php` — narrow planning/qualification save.
- `src/Services/ProgramacionSemanalManualService.php` — candidates/create/duplicate/deprogram.
- `src/Services/ProgramacionSemanalTnpService.php` — candidates and transactional TNP.
- `src/Services/ProgramacionSemanalReconciliationService.php` — pure preview/apply/log orchestration.
- `src/Services/ProgramacionSemanalCloseService.php` — preview/close/CIC/reopen orchestration.
- `src/Services/ProgramacionSemanalReportService.php` — scoped report wrapper.
- `src/Controllers/Api/ProgramacionSemanalApiController.php` — thin RBAC/CSRF/JSON adapter.
- `tests/fixtures/programacion_semanal_state_cases.php`.
- `tests/fixtures/programacion_semanal_contract_rows.php`.
- `tests/test_programacion_semanal_state_resolver.php`.
- `tests/test_programacion_semanal_projection_quantity.php`.
- `tests/test_programacion_semanal_action_policy.php`.
- `tests/test_programacion_semanal_context_contract.php`.
- `tests/test_programacion_semanal_activities_contract.php`.
- `tests/test_programacion_semanal_activity_contract.php`.
- `tests/test_programacion_semanal_manual_contract.php`.
- `tests/test_programacion_semanal_tnp_contract.php`.
- `tests/test_programacion_semanal_reconciliation_preview_contract.php`.
- `tests/test_programacion_semanal_reconciliation_apply_contract.php`.
- `tests/test_programacion_semanal_reconciliation_log_contract.php`.
- `tests/test_programacion_semanal_close_preview_contract.php`.
- `tests/test_programacion_semanal_close_apply_contract.php`.
- `tests/test_programacion_semanal_reopen_contract.php`.
- `tests/test_programacion_semanal_report_contract.php`.
- `tests/test_programacion_semanal_routes.php`.

### Create — React/TypeScript

- `frontend/src/lib/api/esquemas/programacion-semanal.ts` and `.test.ts` — 16 strict contracts;
  every S08 type is `z.infer`.
- `frontend/src/lib/api/programacion-semanal.ts` and `.test.ts` — exact methods/paths/JSON/CSRF.
- `frontend/src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.ts` and test.
- `frontend/src/modules/programacion-semanal/dominio/filtrarProgramacionSemanal.ts` and test.
- `frontend/src/modules/programacion-semanal/dominio/exportarProgramacionSemanalCsv.ts` and test.
- `frontend/src/modules/programacion-semanal/useProgramacionSemanal.ts` and `.test.tsx`.
- `frontend/src/modules/programacion-semanal/ProgramacionSemanalPage.tsx` and `.test.tsx`.
- `frontend/src/modules/programacion-semanal/componentes/ToolbarProgramacionSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/FiltrosProgramacionSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/LeyendaProgramacionSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/TablaProgramacionSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/TarjetasProgramacionSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/EditorActividadSemanal.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoActividadManual.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoDesprogramar.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoCnc.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoTnp.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoConciliacion.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/HistorialConciliacion.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoCierreSemana.tsx` and test.
- `frontend/src/modules/programacion-semanal/componentes/DialogoReapertura.tsx` and test.
- `frontend/src/modules/programacion-semanal/programacion-semanal.css`.
- `tests/browser/fixtures/programacion-semanal-react.mjs`.
- `tests/browser/programacion-semanal-react.spec.mjs`.
- `tests/browser/programacion-semanal-react.a11y.mjs`.
- `tests/browser/programacion-semanal-react.visual.mjs` — candidates only before approval.

### Modify during implementation

- `public/index.php` — add the 16 API routes by task; page route changes only in Task 16.
- `frontend/src/shell/rutas.tsx` and `.test.tsx` — pilot/canonical React route.
- `frontend/src/shell/NavegacionLateral.tsx` and tests only if T01 has not already replaced local role
  logic with server navigation; never add an S08 role map.
- `src/Services/RestrictionConfigResolver.php` only if S07 did not expose the normalized pure method;
  preserve keys, labels, types and thresholds.
- `src/Core/Lps/LpsService.php` only to delegate its projection method to the new pure service after
  parity tests; do not change other consumers opportunistically.
- `src/Services/ProgramChangeDetector.php` — route legacy callers through the new service only at the
  canonical cut; remove request-time DDL without changing schema.
- `src/Controllers/Api/SemanalApiController.php` — aliases delegate to services during coexistence;
  remove methods only after zero callers.
- `src/Controllers/Programacion/ProgramacionSemanalController.php` — protect view and later remove
  `index()`/exclusive loads after cut; retain cnp/cnc/cic methods until S09–S11.
- `src/Controllers/Gestion/ReportController.php` or its processor only to consume the canonical
  state resolver through the S08 wrapper.
- `src/Core/SpaRouter.php` and route contract — canonical GET/HEAD cut in Task 16 only.
- T02 shared drawer files — consume extension points only; no S08 fork.
- `docs/last-planner-programacion-semanal-estados.md` — point to canonical resolver and include all
  eleven states; never leave stale authority.
- `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — S08 closure ledger.
- `docs/design-system/manifests/programacion-semanal.json` — React sources/layouts/states/tests.
- `docs/design-system/manifests/inventory.json`, `docs/design-system/ui-groups-inventory.json`,
  `docs/design-system/unlayered-delivery-inventory.json`, `docs/design-system/exceptions.json`,
  `docs/design-system/state-token-exceptions.json`, `docs/design-system/state-tint-exceptions.json`
  only where S08 legacy entries actually disappear.
- `docs/design-system/state-semantics.json` only to update source ownership, never state IDs, labels,
  hues or severity without a new approved decision.
- Design-system tests asserting VIEW-39/40/41 or CSS source paths: update at cut without weakening
  tokens, state, tint, severity, a11y or responsive assertions.

### Delete only after Task 16 gate

- `views/programacion-semanal/programacion_semanal.view.php`.
- `views/programacion-semanal/partials/_changeMonitorModal.php`.
- `views/programacion-semanal/partials/modal_reabrir.php`.
- `public/js/modules/programacion_semanal/hot.js`.
- `public/js/modules/programacion_semanal/stateMachine.js`.
- `public/js/modules/programacion_semanal/changeMonitor.js`.
- `public/css/programacion-semanal.css` only if no S09–S11 selector imports it; otherwise extract the
  minimum satellite compatibility first and retire only exclusive rules.
- `public/css/change-monitor.css`.
- Legacy-only weekly main-view browser tests replaced by S08; preserve cross-module scenarios by
  updating adapters, never deleting coverage for S09–S11.
- Legacy `/api/semanal/*` aliases/methods only after a zero-caller search and rollback window.

Never delete `views/programacion-semanal/CNP.view.php`, `CNC.view.php`, `CIC.view.php`,
`legacyCards.js`, CNP/CNC/CIC APIs, `views/partials/drawer_unificado.php`, shared drawer JS/CSS,
`RestrictionConfigResolver`, `LpsWeekEditPolicy`, `SemanalReabrirPolicy`, CIC tables/services,
report infrastructure or activity identity adapters while another module consumes them.

## API and Service Signatures

The implementation must preserve these exact route boundaries:

```text
GET  /api/programacion-semanal/context
GET  /api/programacion-semanal/activities
POST /api/programacion-semanal/activity
GET  /api/programacion-semanal/manual-candidates
POST /api/programacion-semanal/manual-activity
POST /api/programacion-semanal/activity/duplicate
POST /api/programacion-semanal/activity/deprogram
GET  /api/programacion-semanal/tnp-candidates
POST /api/programacion-semanal/tnp
GET  /api/programacion-semanal/reconciliation/preview
POST /api/programacion-semanal/reconciliation/apply
GET  /api/programacion-semanal/reconciliation/log
GET  /api/programacion-semanal/close/preview
POST /api/programacion-semanal/close/apply
POST /api/programacion-semanal/reopen
POST /api/programacion-semanal/report
```

```php
interface ProgramacionSemanalReadStore
{
    public function projectContext(): array;
    public function weekContext(): array;
    public function activityRows(int $week): array;
    public function activity(int $week, int $rowId): ?array;
    public function sourceRows(int $week, int $sourceActivityId): array;
    public function manualCandidates(int $week): array;
    public function tnpCandidates(int $week): array;
    public function activeSubcontractors(): array;
    public function activeProfessionals(): array;
    public function causeCatalogs(string $area): array;
    public function reconciliationFacts(int $week): array;
    public function lastReconciliationBatch(int $week): array;
    public function closeFacts(int $week): array;
}
```

```php
interface ProgramacionSemanalWriteStore
{
    public function transaction(callable $operation): mixed;
    public function updateActivity(int $week, int $rowId, array $changes): array;
    public function insertManualRows(int $week, array $rows): array;
    public function duplicateAsManual(int $week, int $rowId): array;
    public function deprogram(int $week, int $rowId, array $cnp): array;
    public function deleteManual(int $week, int $rowId): void;
    public function applyTnp(int $week, int $sourceActivityId, array $tnp): array;
    public function applyReconciliation(int $week, array $operations, array $audit): array;
    public function writeReconciliationBatch(int $week, array $entries, array $audit): void;
    public function closeWeekAndGenerateCic(int $week, array $audit): array;
    public function reopenWeek(int $week, string $reason, array $audit): array;
    public function syncCarryover(int $week, int $sourceActivityId): void;
}
```

```php
final class ProgramacionSemanalStateResolver
{
    public function resolve(array $row, array $week, array $restrictionConfig): array;
}

final class ProgramacionSemanalQuantityValidator
{
    public function validateCommitment(array $sourceRows, array $candidate, array $source): array;
    public function validateActual(array $sourceRows, array $candidate, array $source): array;
    public function normalize(mixed $value, bool $allowNull): ?float;
}

final class ProgramacionSemanalActionPolicy
{
    public function resolve(array $effectivePermissions, array $week, string $canonicalRole, \DateTimeImmutable $now): array;
    public function forRow(array $actions, array $week, array $row): array;
}
```

```ts
export const obtenerContextoProgramacionSemanal: (signal?: AbortSignal) => Promise<ContextoProgramacionSemanal>;
export const listarActividadesSemanales: (signal?: AbortSignal) => Promise<ListaProgramacionSemanal>;
export const guardarActividadSemanal: (payload: GuardarActividadSemanalPayload, csrf: string) => Promise<ResultadoActividadSemanal>;
export const listarCandidatasManuales: (signal?: AbortSignal) => Promise<ListaCandidatasManuales>;
export const crearActividadManual: (payload: CrearActividadManualPayload, csrf: string) => Promise<ResultadoFilasSemanales>;
export const duplicarActividadSemanal: (rowId: number, csrf: string) => Promise<ResultadoActividadSemanal>;
export const desprogramarActividadSemanal: (payload: DesprogramarActividadPayload, csrf: string) => Promise<ResultadoDesprogramacion>;
export const listarCandidatasTnp: (signal?: AbortSignal) => Promise<ListaCandidatasTnp>;
export const registrarTnp: (payload: RegistrarTnpPayload, csrf: string) => Promise<ResultadoFilasSemanales>;
export const previsualizarConciliacionSemanal: (signal?: AbortSignal) => Promise<PreviewConciliacionSemanal>;
export const aplicarConciliacionSemanal: (snapshotHash: string, csrf: string) => Promise<ResultadoConciliacionSemanal>;
export const obtenerLogConciliacionSemanal: (signal?: AbortSignal) => Promise<LogConciliacionSemanal>;
export const previsualizarCierreSemanal: (signal?: AbortSignal) => Promise<PreviewCierreSemanal>;
export const cerrarSemana: (snapshotHash: string, csrf: string) => Promise<ResultadoCierreSemanal>;
export const reabrirSemana: (reason: string, csrf: string) => Promise<ResultadoReaperturaSemanal>;
export const descargarCorteSemanal: (csrf: string) => Promise<ReporteSemanal>;
```

## Task 1: Characterize states, projections, quantities and effective actions

**Files:**

- Create: `tests/fixtures/programacion_semanal_state_cases.php`
- Create: `tests/test_programacion_semanal_state_resolver.php`
- Create: `tests/test_programacion_semanal_projection_quantity.php`
- Create: `tests/test_programacion_semanal_action_policy.php`
- Create: `src/Services/ProgramacionSemanalStateResolver.php`
- Create: `src/Services/ProgramacionSemanalProjectionService.php`
- Create: `src/Services/ProgramacionSemanalQuantityValidator.php`
- Create: `src/Security/ProgramacionSemanalActionPolicy.php`
- Modify: `src/Services/RestrictionConfigResolver.php` only if S07's normalized method is absent

- [ ] **Step 1: Write failing pure characterization cases**

  Cover `ps-no-activa`, five programming states and five qualification states in exact priority;
  Construction/Preconstruction hard/soft restrictions, N/A and thresholds; execution >.001 with
  pending readiness; TNP; critical/noncritical; assignments and commitment. Cover projection before,
  within and after week, invalid dates, zero/positive budget. Cover localized comma/dot, precision,
  normal/TNP bounds, `%` and physical split ceilings. Cover A/D/R/DCV/OT/G/S/SG/V/C, overrides,
  current/historical, confirmed/unconfirmed and reopen clock.

- [ ] **Step 2: Run RED and record missing-class failures**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_state_resolver.php
  docker compose exec app php tests/test_programacion_semanal_projection_quantity.php
  docker compose exec app php tests/test_programacion_semanal_action_policy.php
  ```

  Expected: non-zero because the four production classes do not exist, not because a DB/session was
  opened.

- [ ] **Step 3: Implement the minimum pure domain layer**

  Make restriction config an injected normalized value. Return semantic state DTOs; never CSS class
  or authorization role. Make projection return warning on invalid dates. Make quantity validation
  sum only same project/week/source facts supplied by the store. Make the action policy consume
  effective permission booleans and canonical role only for existing historical/reopen policies.

- [ ] **Step 4: Run GREEN plus existing pure governance tests**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_state_resolver.php
  docker compose exec app php tests/test_programacion_semanal_projection_quantity.php
  docker compose exec app php tests/test_programacion_semanal_action_policy.php
  docker compose exec app php tests/test_lps_week_edit_policy.php
  docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
  ```

  Confirm all exit 0 and no SQL/write seam was constructed.

- [ ] **Step 5: Commit the pure weekly domain**

  ```bash
  git add tests/fixtures/programacion_semanal_state_cases.php tests/test_programacion_semanal_state_resolver.php tests/test_programacion_semanal_projection_quantity.php tests/test_programacion_semanal_action_policy.php src/Services/ProgramacionSemanalStateResolver.php src/Services/ProgramacionSemanalProjectionService.php src/Services/ProgramacionSemanalQuantityValidator.php src/Security/ProgramacionSemanalActionPolicy.php src/Services/RestrictionConfigResolver.php
  git commit -m "test: characterize weekly planning domain"
  ```

## Task 2: Add project-scoped context and activities contracts

**Files:**

- Create: `tests/fixtures/programacion_semanal_contract_rows.php`
- Create: `tests/test_programacion_semanal_context_contract.php`
- Create: `tests/test_programacion_semanal_activities_contract.php`
- Create: `tests/test_programacion_semanal_routes.php`
- Create: `src/Services/ProgramacionSemanalReadStore.php`
- Create: `src/Services/ProgramacionSemanalWriteStore.php`
- Create: `src/Services/DatabaseProgramacionSemanalStore.php`
- Create: `src/Services/ProgramacionSemanalContextService.php`
- Create: `src/Services/ProgramacionSemanalQueryService.php`
- Create: `src/Controllers/Api/ProgramacionSemanalApiController.php`
- Modify: `public/index.php`
- Modify: `src/Controllers/Programacion/ProgramacionSemanalController.php`

- [ ] **Step 1: Write failing context/list/route contracts with fake reads**

  Assert view permission, scope-required, selected week only, typed dates/numbers/booleans, stripped
  text, `[]` empty, actions/catalogs/links/restrictions/legend, row actions and opaque errors. Assert
  GET/HEAD page protection and exact GET routes. Assert neither service accepts `db`, project, area,
  role or week from request input. Assert every read path rejects a write-store spy.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_context_contract.php
  docker compose exec app php tests/test_programacion_semanal_activities_contract.php
  docker compose exec app php tests/test_programacion_semanal_routes.php
  ```

  Expected: missing services/routes; zero database statements.

- [ ] **Step 3: Implement the scoped adapter and two thin endpoints**

  Resolve scope/session once; query allowlisted columns with explicit `project_id`; reuse Task 1
  resolvers; normalize catalogs separately for CNP/CNC/CP; return no exception text. Protect the
  legacy page now with weekly view permission. Do not route the page to React yet.

- [ ] **Step 4: Run GREEN and static scope checks**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_context_contract.php
  docker compose exec app php tests/test_programacion_semanal_activities_contract.php
  docker compose exec app php tests/test_programacion_semanal_routes.php
  docker compose exec app php tests/test_semanal_rbac_solo_lectura.php
  ```

  Search changed SQL for explicit scope and verify no new `SELECT *` or request `db` reads.

- [ ] **Step 5: Commit the read boundary**

  ```bash
  git add tests/fixtures/programacion_semanal_contract_rows.php tests/test_programacion_semanal_context_contract.php tests/test_programacion_semanal_activities_contract.php tests/test_programacion_semanal_routes.php src/Services/ProgramacionSemanalReadStore.php src/Services/ProgramacionSemanalWriteStore.php src/Services/DatabaseProgramacionSemanalStore.php src/Services/ProgramacionSemanalContextService.php src/Services/ProgramacionSemanalQueryService.php src/Controllers/Api/ProgramacionSemanalApiController.php public/index.php src/Controllers/Programacion/ProgramacionSemanalController.php
  git commit -m "feat: add scoped weekly read contracts"
  ```

## Task 3: Add strict Zod schemas, gateway and normalized domain

**Files:**

- Create: `frontend/src/lib/api/esquemas/programacion-semanal.ts`
- Create: `frontend/src/lib/api/esquemas/programacion-semanal.test.ts`
- Create: `frontend/src/lib/api/programacion-semanal.ts`
- Create: `frontend/src/lib/api/programacion-semanal.test.ts`
- Create: `frontend/src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.ts`
- Create: `frontend/src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.test.ts`
- Modify: `frontend/src/lib/api/cliente.ts` only for a reusable no-store/binary/error primitive

- [ ] **Step 1: Write failing raw schema/gateway/normalizer tests**

  Start with context/activities and define placeholders only as schemas when their endpoint task is
  implemented. Reject extra keys, string numbers, legacy HTML, unknown state/phase/action and mixed
  error shapes. Assert exact path/method, abort signal, no query `db`/week and one normalized identity
  per `rowId`. Assert `0` is never collapsed into `null`.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-semanal.test.ts src/lib/api/programacion-semanal.test.ts src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.test.ts
  ```

  Expected: missing modules/functions.

- [ ] **Step 3: Implement schemas and gateway through `cliente.ts`**

  Export `z.infer` types only. Parse success and error bodies before returning. Preserve server state
  DTOs instead of reclassifying. Add a reusable primitive to `cliente.ts` only if existing functions
  cannot express no-store/abort/report; do not introduce a second HTTP wrapper.

- [ ] **Step 4: Run GREEN, typecheck and fetch boundary search**

  ```bash
  npm --prefix frontend test -- --run src/lib/api/esquemas/programacion-semanal.test.ts src/lib/api/programacion-semanal.test.ts src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.test.ts
  npm --prefix frontend run typecheck
  rg -n "fetch\(" frontend/src --glob '*.ts' --glob '*.tsx'
  ```

  Expected: all tests/typecheck pass and only `frontend/src/lib/api/cliente.ts` owns fetch.

- [ ] **Step 5: Commit the typed client boundary**

  ```bash
  git add frontend/src/lib/api/cliente.ts frontend/src/lib/api/esquemas/programacion-semanal.ts frontend/src/lib/api/esquemas/programacion-semanal.test.ts frontend/src/lib/api/programacion-semanal.ts frontend/src/lib/api/programacion-semanal.test.ts frontend/src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.ts frontend/src/modules/programacion-semanal/dominio/normalizarProgramacionSemanal.test.ts
  git commit -m "feat: add typed weekly planning gateway"
  ```

## Task 4: Build the read-only responsive vertical slice

**Files:**

- Create: `frontend/src/modules/programacion-semanal/useProgramacionSemanal.ts`
- Create: `frontend/src/modules/programacion-semanal/useProgramacionSemanal.test.tsx`
- Create: `frontend/src/modules/programacion-semanal/ProgramacionSemanalPage.tsx`
- Create: `frontend/src/modules/programacion-semanal/ProgramacionSemanalPage.test.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/ToolbarProgramacionSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/TablaProgramacionSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/TarjetasProgramacionSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/programacion-semanal.css`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

- [ ] **Step 1: Write failing page/hook/layout tests**

  Cover `/app/programacion-semanal`, context then activities, loading, empty, error, read-only,
  programming/qualification, stale request abort, week/project reset, semantic table at 768+ and no
  table node below 768. Assert visible fields/state explanations and zero edit controls for viewers.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/modules/programacion-semanal/ProgramacionSemanalPage.test.tsx src/modules/programacion-semanal/componentes/TablaProgramacionSemanal.test.tsx src/modules/programacion-semanal/componentes/TarjetasProgramacionSemanal.test.tsx src/shell/rutas.test.tsx
  ```

- [ ] **Step 3: Implement the pilot slice and token-only CSS**

  Load via the gateway; render state supplied by PHP; mount one layout at a time through the shared
  viewport mechanism; keep toolbar read-only actions available. Do not add mutations, filters or
  drawer yet. No literal colors or library grid.

- [ ] **Step 4: Run GREEN, typecheck and token checks**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/shell/rutas.test.tsx
  npm --prefix frontend run typecheck
  rg -n "#[0-9A-Fa-f]{3,8}|!important|style=|Handsontable|bootstrap|jquery|select2" frontend/src/modules/programacion-semanal
  ```

  Expected: tests/typecheck pass and static search is empty.

- [ ] **Step 5: Commit the read-only slice**

  ```bash
  git add frontend/src/modules/programacion-semanal frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
  git commit -m "feat: render weekly planning in React"
  ```

## Task 5: Add filters, counts, legend, selection and CSV

**Files:**

- Create: `frontend/src/modules/programacion-semanal/dominio/filtrarProgramacionSemanal.ts`
- Create: `frontend/src/modules/programacion-semanal/dominio/filtrarProgramacionSemanal.test.ts`
- Create: `frontend/src/modules/programacion-semanal/dominio/exportarProgramacionSemanalCsv.ts`
- Create: `frontend/src/modules/programacion-semanal/dominio/exportarProgramacionSemanalCsv.test.ts`
- Create: `frontend/src/modules/programacion-semanal/componentes/FiltrosProgramacionSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/FiltrosProgramacionSemanal.test.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/LeyendaProgramacionSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/LeyendaProgramacionSemanal.test.tsx`
- Modify: page/hook/toolbar/table/cards and CSS

- [ ] **Step 1: Write failing filter/count/legend/CSV tests**

  Cover accent-insensitive search, state multi-select without modifier, severity, critical,
  assignment, origin, readiness, phase-specific commitment/qualification/CNC and reset. Assert total,
  visible and state counts; distinct real-empty/filter-empty. Assert RFC 4180, stable visible order,
  safe text, zero/null, phase columns and identical export from table/cards.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal/dominio/filtrarProgramacionSemanal.test.ts src/modules/programacion-semanal/dominio/exportarProgramacionSemanalCsv.test.ts src/modules/programacion-semanal/componentes/FiltrosProgramacionSemanal.test.tsx src/modules/programacion-semanal/componentes/LeyendaProgramacionSemanal.test.tsx
  ```

- [ ] **Step 3: Implement pure filtering/export and accessible controls**

  Keep source rows immutable. Serialize filters into SPA search params where T01 permits. Use actual
  checkboxes/buttons, live count announcement and a local Blob download. Do not call an endpoint for
  CSV or mutate session.

- [ ] **Step 4: Run GREEN and full module tests**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit discoverability and export**

  ```bash
  git add frontend/src/modules/programacion-semanal
  git commit -m "feat: add weekly filters counts and csv"
  ```

## Task 6: Implement narrow activity mutation and editable table/cards

**Files:**

- Create: `tests/test_programacion_semanal_activity_contract.php`
- Create: `src/Services/ProgramacionSemanalMutationService.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/EditorActividadSemanal.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/EditorActividadSemanal.test.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoCnc.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoCnc.test.tsx`
- Modify: controller/routes/store, schemas/gateway tests, hook, table/cards/page

- [ ] **Step 1: Write failing PHP and Vitest mutation cases**

  PHP: permission/CSRF/scope/phase/row, allowlist merge, assignment, localized numbers, split ceilings,
  CNC standard/other, PAC/completion, CNC clearing, carryover, transaction rollback and full DTO.
  React: planning versus qualification fields, one row pending, draft recovery, no auto-retry,
  `WEEK_PHASE_CHANGED`, field errors, CNC dialog focus and exact equivalence table/cards.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_activity_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/EditorActividadSemanal.test.tsx src/modules/programacion-semanal/componentes/DialogoCnc.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement service, endpoint and shared editor**

  Re-read/merge row in transaction; validate server sums and catalogs; derive PAC/state; sync
  carryover only after update; return complete affected rows. React uses one editor component from
  table and card, preserves drafts on 409/422 and never sends derived fields.

- [ ] **Step 4: Run GREEN and focused policy regressions**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_activity_contract.php
  docker compose exec app php tests/test_lps_week_edit_policy.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit individual editing**

  ```bash
  git add tests/test_programacion_semanal_activity_contract.php src/Services/ProgramacionSemanalMutationService.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/DatabaseProgramacionSemanalStore.php public/index.php frontend/src/lib/api/esquemas/programacion-semanal.ts frontend/src/lib/api/esquemas/programacion-semanal.test.ts frontend/src/lib/api/programacion-semanal.ts frontend/src/lib/api/programacion-semanal.test.ts frontend/src/modules/programacion-semanal
  git commit -m "feat: edit weekly activities safely"
  ```

## Task 7: Add manual activity, duplicate and deprogram/CNP flows

**Files:**

- Create: `tests/test_programacion_semanal_manual_contract.php`
- Create: `src/Services/ProgramacionSemanalManualService.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoActividadManual.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoActividadManual.test.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoDesprogramar.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoDesprogramar.test.tsx`
- Modify: controller/routes/store, schemas/gateway, hook/toolbar/table/cards/page

- [ ] **Step 1: Write failing four-endpoint and UI cases**

  Characterize manual universe SI 1..6/base execution <=.001, stable source ID, stripped text,
  readiness reason, lazy list, required fields, split subcontractors, first-row commitment, uniqueness,
  transaction/carryover/audit. Characterize duplicate-as-manual without commitment/real. Characterize
  hard delete for manual and logical CNP for active with area category/cause. React covers search,
  selection, validation, confirmation, multiple result rows, focus and mobile actions.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_manual_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoActividadManual.test.tsx src/modules/programacion-semanal/componentes/DialogoDesprogramar.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement candidates/create/duplicate/deprogram**

  Register exact routes; derive area/week/project; never trust activity name/catalog values without
  re-reading; apply all rows in one transaction; return full rows/effect. Keep S09 as the full CNP
  surface and link to it after success when useful.

- [ ] **Step 4: Run GREEN and module regression**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_manual_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit manual/CNP operations**

  ```bash
  git add tests/test_programacion_semanal_manual_contract.php src/Services/ProgramacionSemanalManualService.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/DatabaseProgramacionSemanalStore.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: add weekly manual and deprogram flows"
  ```

## Task 8: Make TNP functional in qualification

**Files:**

- Create: `tests/test_programacion_semanal_tnp_contract.php`
- Create: `src/Services/ProgramacionSemanalTnpService.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoTnp.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoTnp.test.tsx`
- Modify: controller/routes/store, schemas/gateway, hook/toolbar/table/cards/page

- [ ] **Step 1: Write failing TNP contracts and UI tests**

  Cover confirmed qualification action for A/D/R/DCV, viewer denial, unconfirmed denial, candidate
  SI 1..12/base executed 0/not-present-or-inactive, previously programmed flag, area-specific exact CP
  categories, real >0, CP required, 255/500 limits, existing source rows all affected with commitment
  null/PAC null/Es_TNP, insert path, transaction rollback and `cal-tnp`. Assert React never hides the
  flow on mobile and reports affected count.

- [ ] **Step 2: Run RED including the legacy-lock contradiction**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_tnp_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoTnp.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

  Expected: fail until the new service treats TNP as qualification rather than calling the ordinary
  confirmed-week guard.

- [ ] **Step 3: Implement scoped TNP candidates and transactional apply**

  Do not pass through `CommitmentLockGuard::guard(..., 'tnp')`; use the Task 1 action policy plus
  confirmed-phase revalidation. Return every affected DTO and recompute state. Do not change the
  shared guard for unrelated callers.

- [ ] **Step 4: Run GREEN and phase/policy regressions**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_tnp_contract.php
  docker compose exec app php tests/test_lps_week_edit_policy.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit TNP**

  ```bash
  git add tests/test_programacion_semanal_tnp_contract.php src/Services/ProgramacionSemanalTnpService.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/DatabaseProgramacionSemanalStore.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: support weekly unplanned work"
  ```

## Task 9: Build a pure reconciliation preview

**Files:**

- Create: `tests/test_programacion_semanal_reconciliation_preview_contract.php`
- Create: `src/Services/ProgramacionSemanalReconciliationService.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoConciliacion.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoConciliacion.test.tsx`
- Modify: controller/routes/read store, schemas/gateway, hook/toolbar/page

- [ ] **Step 1: Write failing read-only preview cases**

  Cover `insert`, `refresh`, `reactivate`, `deprogram`, `insertCnp`, `removeOrphan`, `deduplicate`,
  counts, hard/soft alerts and deterministic `snapshotHash`. Assert manual, voluntary CNP/reactivate,
  positive commitment/real and execution >.001 immunity. Inject a write-store spy that must remain
  untouched. React auto-loads preview only after context/list and requires explicit user action to
  open/apply.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reconciliation_preview_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoConciliacion.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement deterministic preview with no write dependencies**

  Extract decision logic from legacy behavior into pure operation builders; include before/after and
  reason without SQL/table names. GET uses no CSRF, `no-store`, view permission and zero mutation.
  Show preview badge/alerts but never call apply automatically.

- [ ] **Step 4: Run GREEN and static no-write checks**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reconciliation_preview_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  rg -n "CREATE TABLE|ALTER TABLE|DROP TABLE" src/Services/ProgramacionSemanalReconciliationService.php src/Controllers/Api/ProgramacionSemanalApiController.php
  ```

  Expected: tests pass and DDL search is empty.

- [ ] **Step 5: Commit reconciliation preview**

  ```bash
  git add tests/test_programacion_semanal_reconciliation_preview_contract.php src/Services/ProgramacionSemanalReconciliationService.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/ProgramacionSemanalReadStore.php src/Services/DatabaseProgramacionSemanalStore.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: preview weekly reconciliation safely"
  ```

## Task 10: Apply reconciliation atomically and expose a pure log

**Files:**

- Create: `tests/test_programacion_semanal_reconciliation_apply_contract.php`
- Create: `tests/test_programacion_semanal_reconciliation_log_contract.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/HistorialConciliacion.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/HistorialConciliacion.test.tsx`
- Modify: reconciliation service, controller/routes/write store, schemas/gateway, dialog/hook/page
- Modify: `src/Services/ProgramChangeDetector.php` only behind legacy delegation, not deletion

- [ ] **Step 1: Write failing apply/log/idempotency cases**

  Assert edit permission, CSRF, programming phase, snapshot revalidation, stale 409, immunity
  revalidation, one transaction, carryover/flags, one batch log, rollback, no client operations,
  repeated apply idempotence, full result rows/entries and opaque errors. Log GET must return latest
  batch/empty and never call schema/write methods; missing schema returns stable prerequisite error.
  React covers confirm, pending, stale recovery, log filter/refresh and no automatic retry.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reconciliation_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_log_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoConciliacion.test.tsx src/modules/programacion-semanal/componentes/HistorialConciliacion.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement transactional apply and read-only latest batch**

  Recompute operations from facts and compare hash; apply server plan only. Record actor/timestamp
  through injected audit/clock. Remove `ensureLogTable()` from any code path used by new endpoints;
  legacy delegation may retain rollback until cut but must not be called by React. Refresh rows and
  preview after success.

- [ ] **Step 4: Run GREEN, governance and no-DDL checks**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reconciliation_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_log_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts
  rg -n "ensureLogTable|CREATE TABLE|ALTER TABLE" src/Services/ProgramacionSemanalReconciliationService.php src/Controllers/Api/ProgramacionSemanalApiController.php
  ```

- [ ] **Step 5: Commit explicit reconciliation**

  ```bash
  git add tests/test_programacion_semanal_reconciliation_apply_contract.php tests/test_programacion_semanal_reconciliation_log_contract.php src/Services/ProgramacionSemanalReconciliationService.php src/Services/ProgramacionSemanalWriteStore.php src/Services/DatabaseProgramacionSemanalStore.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/ProgramChangeDetector.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: apply and audit weekly reconciliation"
  ```

## Task 11: Preview and close the week with CIC atomically

**Files:**

- Create: `tests/test_programacion_semanal_close_preview_contract.php`
- Create: `tests/test_programacion_semanal_close_apply_contract.php`
- Create: `src/Services/ProgramacionSemanalCloseService.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoCierreSemana.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoCierreSemana.test.tsx`
- Modify: controller/routes/store, schemas/gateway, hook/toolbar/table/cards/page

- [ ] **Step 1: Write failing close preview/apply and UI cases**

  Preview: blockers per `rowId` for commitment, assignments, limits and catalog/CIC consistency;
  pure read, summary and hash. Apply: edit/CSRF/programming/hash, server clock/actor, revalidation,
  week confirm + CIC/PAC in one transaction, rollback, no client close date and no placeholder master
  inserts. React: blocker filter/focus, disabled confirm, success phase transition and retained errors.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_close_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_apply_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoCierreSemana.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement preview and all-or-nothing close/CIC**

  Inject clock and CIC writer through the write store. Validate all catalog references instead of
  self-healing. Derive close metadata in PHP, mark confirmed and generate/update CIC inside one
  transaction. Return new context/rows/CIC summary; React replaces phase and clears programming
  drafts/actions.

- [ ] **Step 4: Run GREEN and assert forbidden placeholder absence**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_close_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_apply_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  rg -n "placeholder@example\.com|INSERT IGNORE.*subcontratistas" src/Services/ProgramacionSemanalCloseService.php src/Services/DatabaseProgramacionSemanalStore.php
  ```

  Expected: tests pass and forbidden search is empty in new code.

- [ ] **Step 5: Commit close/CIC**

  ```bash
  git add tests/test_programacion_semanal_close_preview_contract.php tests/test_programacion_semanal_close_apply_contract.php src/Services/ProgramacionSemanalCloseService.php src/Services/ProgramacionSemanalWriteStore.php src/Services/DatabaseProgramacionSemanalStore.php src/Controllers/Api/ProgramacionSemanalApiController.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: close weekly commitments atomically"
  ```

## Task 12: Reopen the week through the existing policy

**Files:**

- Create: `tests/test_programacion_semanal_reopen_contract.php`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoReapertura.tsx`
- Create: `frontend/src/modules/programacion-semanal/componentes/DialogoReapertura.test.tsx`
- Modify: close service, controller/routes/store, schemas/gateway, hook/toolbar/page

- [ ] **Step 1: Write failing reopen service/contract/UI cases**

  Assert confirmed week, edit capability, CSRF, exact `SemanalReabrirPolicy` matrix/time behavior,
  20–500 normalized reason, transaction, close-date clear, actor/role/reason audit and opaque failure.
  React uses only `actions.reopenWeek`, counts characters accessibly, restores focus and transitions
  to programming from server response.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reopen_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/DialogoReapertura.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement the narrow reopen endpoint and dialog**

  Call the existing policy before the write and again with current facts inside orchestration.
  Derive role/date/current time in PHP. Reuse the close service transaction seam; do not modify role
  aliases or relax the policy.

- [ ] **Step 4: Run GREEN with existing authorization tests**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_reopen_contract.php
  docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit reopen**

  ```bash
  git add tests/test_programacion_semanal_reopen_contract.php src/Services/ProgramacionSemanalCloseService.php src/Controllers/Api/ProgramacionSemanalApiController.php src/Services/DatabaseProgramacionSemanalStore.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: reopen weekly commitments safely"
  ```

## Task 13: Generate a scoped XLSX report from canonical states

**Files:**

- Create: `tests/test_programacion_semanal_report_contract.php`
- Create: `src/Services/ProgramacionSemanalReportService.php`
- Modify: controller/routes, schemas/gateway, hook/toolbar/page
- Modify: report processor only through an injected canonical state adapter

- [ ] **Step 1: Write failing report permission/scope/state cases**

  Assert report capability independent of edit, project/week derived from scope, format allowlist,
  CSRF, fake generator only, safe filename/URL, all ten phase states plus TNP, no exception leakage
  and `REPORT_FAILED`. React covers allowed/hidden, pending, success navigation/download and retry by
  user only. CSV remains local and available to every viewer.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_report_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal/componentes/ToolbarProgramacionSemanal.test.tsx src/modules/programacion-semanal/useProgramacionSemanal.test.tsx src/lib/api/programacion-semanal.test.ts src/lib/api/esquemas/programacion-semanal.test.ts
  ```

- [ ] **Step 3: Implement the report wrapper and canonical state adapter**

  Inject the generator; never create a real file in tests. Resolve project/week/actions server-side,
  pass normalized rows/state semantics, return only a permitted storage URL/filename. Do not rewrite
  BI or CNP/CNC/CIC reports.

- [ ] **Step 4: Run GREEN and report regressions that are read-only/fake**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_report_contract.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  ```

- [ ] **Step 5: Commit scoped report**

  ```bash
  git add tests/test_programacion_semanal_report_contract.php src/Services/ProgramacionSemanalReportService.php src/Controllers/Api/ProgramacionSemanalApiController.php public/index.php frontend/src/lib/api frontend/src/modules/programacion-semanal
  git commit -m "feat: add scoped weekly commitment report"
  ```

## Task 14: Integrate T02 drawer and harden recovery states

**Files:**

- Modify: T02 shared drawer extension points and tests
- Modify: `frontend/src/modules/programacion-semanal/ProgramacionSemanalPage.tsx`
- Modify: hook/table/cards/toolbar/filter/dialog tests and CSS

- [ ] **Step 1: Write failing drawer/context/recovery tests**

  Assert table and card open the same T02 drawer with canonical activity, `rowId`, week and
  `modulo=PS`; diagnosis includes phase/state/readiness/commitment-real/PAC/CNP-CNC-CP and authorized
  satellite links. Cover comments/replies/mentions/digest/SOS/crisis action pass-through, context
  clear on row/week/project, focus return, 401/403/409/422/500/network and preserved relevant drafts.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/shared/drawer-lps
  ```

- [ ] **Step 3: Implement the T02 adapter and unified diagnostic panel**

  Use T02 public props/actions; do not duplicate endpoints, policy or drawer UI. Replace the legacy
  operational drawer concept with a diagnostic tab. Add accessible banners, field summary, retry
  buttons and focus restoration without swallowing errors.

- [ ] **Step 4: Run GREEN across shared consumers**

  ```bash
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/shared/drawer-lps src/modules/programa-general src/modules/programacion-intermedia
  npm --prefix frontend run typecheck
  ```

  Do not proceed if S05/S07 drawer consumers regress.

- [ ] **Step 5: Commit shared drawer integration**

  ```bash
  git add frontend/src/modules/programacion-semanal frontend/src/shared/drawer-lps
  git commit -m "feat: integrate weekly contextual drawer"
  ```

## Task 15: Prove RBAC, responsive, accessibility and dark/light behavior

**Files:**

- Create: `tests/browser/fixtures/programacion-semanal-react.mjs`
- Create: `tests/browser/programacion-semanal-react.spec.mjs`
- Create: `tests/browser/programacion-semanal-react.a11y.mjs`
- Create: `tests/browser/programacion-semanal-react.visual.mjs`
- Modify: React tests/CSS only for defects exposed by scenarios

- [ ] **Step 1: Write failing fully intercepted browser scenarios**

  Install all session/context/S08/T02/report interceptions before navigation. Cover A/D/R/DCV/OT/
  G/S/SG/V/C effective action fixtures; programming/qualification; filters/counts/CSV; individual,
  manual, duplicate, CNP, CNC, TNP; reconciliation preview/apply/log; close/blockers/CIC; reopen;
  empty/errors/context switch; 390/768/1180/1440; keyboard and axe. Assert no unhandled request and
  no write request before an explicit user confirmation.

- [ ] **Step 2: Run RED browser/a11y with no real backend writes**

  ```bash
  npx playwright test tests/browser/programacion-semanal-react.spec.mjs tests/browser/programacion-semanal-react.a11y.mjs --workers=1
  ```

  Expected: fail on missing scenario behavior only. Abort immediately if an unmocked request appears.

- [ ] **Step 3: Fix only evidenced React/CSS/contract defects**

  Preserve server actions and domain semantics. Do not weaken axe, overflow, focus, interception,
  state or permission assertions. Produce dark/light candidate screenshots in test output; do not
  update snapshots/goldens.

- [ ] **Step 4: Run focused and proportional green gate**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_state_resolver.php
  docker compose exec app php tests/test_programacion_semanal_projection_quantity.php
  docker compose exec app php tests/test_programacion_semanal_action_policy.php
  docker compose exec app php tests/test_programacion_semanal_context_contract.php
  docker compose exec app php tests/test_programacion_semanal_activities_contract.php
  docker compose exec app php tests/test_programacion_semanal_activity_contract.php
  docker compose exec app php tests/test_programacion_semanal_manual_contract.php
  docker compose exec app php tests/test_programacion_semanal_tnp_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_log_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reopen_contract.php
  docker compose exec app php tests/test_programacion_semanal_report_contract.php
  docker compose exec app php tests/test_programacion_semanal_routes.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/esquemas/programacion-semanal.test.ts src/lib/api/programacion-semanal.test.ts
  npm --prefix frontend run typecheck
  npx playwright test tests/browser/programacion-semanal-react.spec.mjs tests/browser/programacion-semanal-react.a11y.mjs --workers=1
  ```

  Read every exit code separately. Visual candidates require human approval before any baseline
  command.

- [ ] **Step 5: Commit verified behavior, not visual baselines**

  ```bash
  git add tests/browser/fixtures/programacion-semanal-react.mjs tests/browser/programacion-semanal-react.spec.mjs tests/browser/programacion-semanal-react.a11y.mjs tests/browser/programacion-semanal-react.visual.mjs frontend/src/modules/programacion-semanal
  git commit -m "test: verify weekly React parity"
  ```

## Task 16: Cut the canonical route and retire only exclusive legacy pieces

**Files:**

- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: shell routes/navigation/tests
- Modify: controller/API aliases after zero callers
- Modify: `src/Services/ProgramChangeDetector.php` only as justified by callers
- Modify: weekly state doc and design-system manifests/tests
- Delete: VIEW-39/40/41, exclusive JS/CSS/tests listed above only after gates

- [ ] **Step 1: Write failing canonical route and retirement contracts**

  Assert `/programacion-semanal` GET/HEAD reaches SPA, POST/API are not captured, page requires view
  via session bootstrap/navigation, `/app/programacion-semanal` still works, satellite CNP/CNC/CIC
  routes still resolve, manifests name React owners and no canonical HTML references VIEW-39/40/41,
  HOT/change monitor or automatic write-on-load behavior.

- [ ] **Step 2: Run RED and perform zero-caller inventory**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_routes.php
  rg -n "programacion_semanal\.view|_changeMonitorModal|modal_reabrir|programacion_semanal/hot\.js|stateMachine\.js|changeMonitor\.js|api/semanal/(list|save|reabrir|auto-program|auto-program-log|tnp-actividades)" public src views frontend tests docs/design-system -g '!docs/archive/**'
  ```

  Classify every hit as S08 legacy, S09–S11/shared, contract, manifest or stale doc. Do not delete
  anything with a live non-S08 caller.

- [ ] **Step 3: Make the reversible cut and update ownership docs**

  Add canonical SpaRouter entry; update sidebar; delegate/remove aliases only when zero callers;
  delete VIEW-39/40/41 and exclusive assets; preserve satellites/shared CSS or extract their minimum
  compatibility. Update the weekly state doc to the canonical resolver and design-system manifests.
  Record exact rollback commit. Do not touch data/schema/RLS.

- [ ] **Step 4: Re-run the full S08 gate after deletion**

  ```bash
  docker compose exec app php tests/test_programacion_semanal_state_resolver.php
  docker compose exec app php tests/test_programacion_semanal_projection_quantity.php
  docker compose exec app php tests/test_programacion_semanal_action_policy.php
  docker compose exec app php tests/test_programacion_semanal_context_contract.php
  docker compose exec app php tests/test_programacion_semanal_activities_contract.php
  docker compose exec app php tests/test_programacion_semanal_activity_contract.php
  docker compose exec app php tests/test_programacion_semanal_manual_contract.php
  docker compose exec app php tests/test_programacion_semanal_tnp_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reconciliation_log_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_preview_contract.php
  docker compose exec app php tests/test_programacion_semanal_close_apply_contract.php
  docker compose exec app php tests/test_programacion_semanal_reopen_contract.php
  docker compose exec app php tests/test_programacion_semanal_report_contract.php
  docker compose exec app php tests/test_programacion_semanal_routes.php
  docker compose exec app php tests/test_lps_week_edit_policy.php
  docker compose exec app php tests/test_semanal_reabrir_autorizacion.php
  docker compose exec app php tests/test_semanal_rbac_solo_lectura.php
  npm --prefix frontend test -- --run src/modules/programacion-semanal src/lib/api/esquemas/programacion-semanal.test.ts src/lib/api/programacion-semanal.test.ts src/shell/rutas.test.tsx src/shared/drawer-lps
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  npx playwright test tests/browser/programacion-semanal-react.spec.mjs tests/browser/programacion-semanal-react.a11y.mjs --workers=1
  git diff --check
  ```

  Read each exit code on its own line. Do not run legacy live-data cycles as a substitute.

- [ ] **Step 5: Commit the canonical cut and record closure**

  ```bash
  git add -A public/index.php src/Core/SpaRouter.php src/Controllers/Programacion/ProgramacionSemanalController.php src/Controllers/Api/SemanalApiController.php src/Services/ProgramChangeDetector.php views/programacion-semanal public/js/modules/programacion_semanal public/css frontend/src docs/last-planner-programacion-semanal-estados.md docs/design-system docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md tests
  git commit -m "feat: cut weekly planning over to React"
  ```

  Review staged paths before commit; unstage any S09–S11/shared/unrelated file not justified by the
  zero-caller audit. Closing the front, PR, CI, merge and publication follow repository policy and
  require the appropriate authorization; this task does not authorize deploy.

## Vertical Checkpoints

1. **Checkpoint 1 — Read core:** Tasks 1–5 green. `/app/programacion-semanal` is useful in read-only
   mode at all viewports; no writes or legacy retirement.
2. **Checkpoint 2 — Planning:** Tasks 6–7 green. Individual/manual/duplicate/CNP work through fakes
   and intercepted browser, including mobile.
3. **Checkpoint 3 — Qualification:** Task 8 green. Real/CNC and TNP are coherent in confirmed weeks.
4. **Checkpoint 4 — Governance:** Tasks 9–13 green. Preview/apply/log, close/CIC, reopen and report are
   safe and transactional; loading remains read-only.
5. **Checkpoint 5 — Shared UX:** Task 14 green with T02 and S05/S07 consumers unchanged.
6. **Checkpoint 6 — Quality:** Task 15 green; dark/light candidates await explicit visual approval.
7. **Checkpoint 7 — Cut:** Task 16 green after zero-caller audit and exclusive retirement.

Do not skip a checkpoint by implementing a later UI against a legacy endpoint. At checkpoints 1–6,
the canonical legacy route remains the rollback path.

## Traceability Matrix

| Acceptance | Implemented by | Verified by |
|---|---|---|
| S08-AC-01 | Tasks 4,16 | route contract + pilot/canonical browser |
| S08-AC-02 | Task 3 | fetch boundary search |
| S08-AC-03 | Tasks 2–13 | PHP contracts + Zod/gateway tests |
| S08-AC-04 | Tasks 2,3 | payload/scope tests |
| S08-AC-05 | Tasks 2,4 | list/page empty tests |
| S08-AC-06 | Tasks 1,2,6–12 | policy/phase mutation tests |
| S08-AC-07 | Tasks 1,2,6,13 | resolver/list/save/report tests |
| S08-AC-08 | Task 5 | filters/legend browser tests |
| S08-AC-09 | Tasks 4,6–8,15 | table/card parity at 768/390 |
| S08-AC-10 | Tasks 1,6 | quantity and mutation PHP tests |
| S08-AC-11 | Task 6 | CNC/PAC tests |
| S08-AC-12 | Task 7 | manual contract/browser |
| S08-AC-13 | Task 8 | TNP contract/browser |
| S08-AC-14 | Tasks 2,9,15 | read-store spies + no-write browser |
| S08-AC-15 | Tasks 9,10 | reconciliation tests |
| S08-AC-16 | Task 10 | log spy + DDL search |
| S08-AC-17 | Task 11 | close preview/apply tests |
| S08-AC-18 | Task 11 | fake clock/catalog blocker/search |
| S08-AC-19 | Task 12 | reopen + existing policy tests |
| S08-AC-20 | Tasks 5,13 | CSV/report permission tests |
| S08-AC-21 | Task 14 | shared drawer tests/browser |
| S08-AC-22 | Tasks 6–15 | recovery tests/browser |
| S08-AC-23 | Task 15 | axe/keyboard/viewports/candidates |
| S08-AC-24 | all | status/diff + fake/intercepted verification |
| S08-AC-25 | Task 16 | zero-caller/route/manifests/rollback audit |

## Verification Commands Explicitly Forbidden in S08

Do not run these against the shared runtime/database during implementation verification:

- `tests/browser/programacion-semanal-roles-phases.mjs`;
- `tests/browser/programacion-semanal-sprint.mjs`;
- CNP/CNC/CIC lifecycle suites that create/update weekly records;
- `tests/browser/preconstruccion-full-cycle.mjs`;
- `tests/browser/full-app-flow.spec.mjs` unless it is first proven fully intercepted/isolated;
- `tests/test_weekly_governance.php`, because it inserts, updates and deletes a synthetic live-data
  fixture despite cleaning it afterward;
- any helper entering `/dev/entrar` and then saving, reconciling, closing, reopening or changing
  project/week;
- direct calls to `/api/semanal/save`, `/api/semanal/auto-program`, report generation or T02 writes;
- SQL clients, migrations, patches, schema checks that run DDL, or rollback-based write tests;
- snapshot update commands or visual baseline writes without explicit approval.

Static reads, pure PHP contracts, Vitest and fully intercepted Playwright are the authorized default.
If an existing test's write behavior is uncertain, inspect it before running; uncertainty means do
not run it against the shared runtime.

## Self-Review Checklist for the Executor

- [ ] All work stayed in the named worktree/branch and preserved unrelated changes.
- [ ] T01/S05/S06/S07/T02 prerequisites were verified, not copied.
- [ ] All 16 routes have matching PHP contracts, strict Zod schemas and gateway tests.
- [ ] No component/hook calls `fetch`; no payload sends server context/authority.
- [ ] Context/list/preview/log/close-preview and page load perform zero writes.
- [ ] No request-time DDL or placeholder catalog self-heal remains on the React path.
- [ ] State resolver covers exactly eleven IDs and is shared by list/save/report.
- [ ] Quantity validator enforces `%`/physical split ceilings in PHP.
- [ ] TNP works only in qualification and no ordinary confirmed-week guard blocks it.
- [ ] Reconciliation applies only after preview/confirmation with CSRF/stale detection.
- [ ] Close/CIC and reopen are transactional and use server clock/actor/policies.
- [ ] Table and cards expose identical allowed fields/actions; no hidden grid exists on mobile.
- [ ] CSV works in all layouts; report permission is independent from edit.
- [ ] T02 drawer works without regressing S05/S07 and clears on context changes.
- [ ] Dark/light and all four viewports pass token, overflow, keyboard and axe checks.
- [ ] No visual baseline was changed without explicit approval.
- [ ] S09–S11 routes/views/controllers/`legacyCards.js` remain intact.
- [ ] Zero-caller evidence preceded every legacy deletion.
- [ ] Every verification exit code was read separately and recorded.
- [ ] `git diff --check` is clean; staged files contain only S08 work.
- [ ] No RLS, schema, grants, users, credentials, data, `/admin/`, deploy or publication was touched
  without separate authorization.

## Cierre

**Estado documental al 2026-08-30:** plan escrito y autorrevisado; 16 tareas, 80 pasos, 16 rutas y
25 criterios trazados. No se ejecutó ninguna tarea de implementación, prueba con escritura, DDL/DML,
commit, push, PR, publicación o deploy. El cierre de implementación se añadirá aquí con SHA, pruebas,
salidas, límites, aprobación visual y decisión de integración reales; nunca se inferirá de casillas.
