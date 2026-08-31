# S06 Actualizar Cronograma React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Execute tasks in order and stop at the stated review gates.
> Checkbox syntax is an execution prompt only; repository progress and closure are recorded in the
> plan `## Cierre` ledger and git history, never inferred from checkbox counts.

**Goal:** migrate `/programa-general-actualizar` from VIEW-33, Handsontable and inline jQuery to a
native React surface with safe XLSX preview/confirmation, deterministic base/target context,
manual and automatic mapping, editable desktop/tablet/mobile presentation, server-side
traceability, dark/light parity and a reversible canonical cut, without changing RLS or data during
verification.

**Architecture:** T01 provides shell/session/project/theme/navigation; S04 project switching; S05
the shared General list and activity normalization primitives. S06 adds a project-scoped context
resolver and effective action policy, new mutation endpoints under
`/api/programa-general-actualizar/*`, a two-phase filesystem-backed XLSX import and dedicated row/
association services. React receives all data through Zod gateways, normalizes one domain state and
renders a semantic table at 768+ and editable cards below 768. VIEW-33 and its aliases remain for
pilot rollback; after approved functional/RBAC/a11y/visual gates, GET/HEAD becomes SPA and only
exclusive legacy pieces are retired.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe, PhpSpreadsheet 5.4 and AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s06-actualizar-cronograma-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia` or another checkout.
- Execute only after T01, S04 and S05 contracts/files are present and green. Reuse their router,
  shell bootstrap, `ErrorApi`, theme, project switching, activity raw schema, unit/ratio/date helpers
  and `/api/general/list`; do not copy those systems into S06.
- Inspect `git status --short` and relevant diffs before every task. Preserve all pre-existing or
  unrelated edits; do not clean, revert or refactor adjacent work.
- This session is documentation-only. Do not implement, commit, push, publish or deploy now. Commit
  commands below are future execution instructions and require implementation authorization.
- `/admin/` is excluded: no files, routes, styles, tests, dependencies or permissions there.
- Do not modify RLS, `ProjectScope` semantics, schema, migrations, grants, users, credentials,
  memberships, permission assignments or database data. Do not execute DDL/DML.
- PHP is authoritative for project, base/target, target kind, row ownership, permissions, matching,
  inheritance, import context and deletion. React never sends `db`, table prefix, `project_id`,
  project, user, role, area, max week, permission flag or authoritative threshold.
- Keep `lps.programa_general_actualizar.ver/editar` unchanged. Shared list keeps
  `lps.programa_general.ver`; actions derive effective permission values, not a role matrix.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Components/hooks call typed S06 gateways.
  Every consumed response is parsed by Zod before domain/component code sees it.
- Every new endpoint starts with a failing PHP contract. Every schema/gateway/domain/component starts
  with a failing Vitest. No implementation-before-RED.
- Mutations never retry automatically. Save/import/autoassociate/batch/delete browser scenarios
  intercept the network and never reach a real write path.
- Do not run `tests/test_schedule_update_draft_import.php`,
  `tests/test_preconstruction_import_global_ids.php`, `tests/browser/full-app-flow.spec.mjs` or any
  other suite marked `@requiere: db` or known to create/delete projects. They execute DML.
- Backend mutation tests use fake stores and temporary files only. Temporary XLSX/JSON files are
  removed in `finally`; no fixture is written into the repo or served directory.
- Keep `ActivityMatcherService`, its configuration table, import hierarchy/ID semantics, master/
  consolidated transaction and legacy association inheritance. Extract seams; do not port these
  algorithms to TypeScript.
- Use only tokens from `public/css/tokens.css`; no literal colors, inline style objects, `!important`,
  Bootstrap, jQuery, Handsontable, TomSelect, Toastr, Font Awesome, CSS-in-JS or a new grid/state/query
  dependency in React.
- Dark is default/fallback; light has identical capability. Required viewports: `390×844`,
  `768×1024`, `1180×820`, `1440×900`; no horizontal page overflow.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output outside git until the visual gate is approved.
- No real dev-door login, session/project/week mutation or shared database probe is part of S06.
  Playwright installs route interceptions before navigation and uses synthetic session/context data.

## File Structure

### Create — PHP

- `src/Security/ProgramaGeneralActualizarActionPolicy.php` — pure effective-action resolver.
- `src/Services/ProgramaGeneralActualizarStore.php` — narrow interface for scoped reads/writes.
- `src/Services/DatabaseProgramaGeneralActualizarStore.php` — only S06 SQL implementation; project
  from active `ProjectScope`, prefix from `ModuleRequestContext`.
- `src/Services/ProgramaGeneralActualizarContextService.php` — base/target/mode/counts/actions.
- `src/Services/ProgramaGeneralActualizarUploadValidator.php` — 10 MiB, extension/MIME/upload errors.
- `src/Services/ProgramaGeneralActualizarImportParser.php` — XLSX columns/rows/dates/IDs/hierarchy.
- `src/Services/ProgramaGeneralActualizarImportStore.php` — 15-minute single-use temp preview store.
- `src/Services/ProgramaGeneralActualizarImportService.php` — preview/revalidate/confirm orchestration.
- `src/Services/ProgramaGeneralActualizarRowService.php` — ordinary edits and single mapping/heredity.
- `src/Services/ProgramaGeneralActualizarDecisionLogService.php` — session-derived audit records.
- `src/Services/ProgramaGeneralActualizarAssociationService.php` — matcher, autoapply and batch.
- `src/Controllers/Api/ProgramaGeneralActualizarApiController.php` — thin JSON/CSRF/RBAC adapter.
- `tests/test_programa_general_actualizar_action_policy.php`.
- `tests/test_programa_general_actualizar_context_contract.php`.
- `tests/test_programa_general_actualizar_import_parser.php`.
- `tests/test_programa_general_actualizar_import_preview_contract.php`.
- `tests/test_programa_general_actualizar_import_confirm_contract.php`.
- `tests/test_programa_general_actualizar_save_contract.php`.
- `tests/test_programa_general_actualizar_association_contract.php`.
- `tests/test_programa_general_actualizar_delete_contract.php`.
- `tests/test_programa_general_actualizar_routes.php`.

### Create — React/TypeScript

- `frontend/src/lib/api/esquemas/actualizar-cronograma.ts` and `.test.ts` — strict raw contracts and
  all S06 `z.infer` types.
- `frontend/src/lib/api/actualizar-cronograma.ts` and `.test.ts` — exact queries/forms/JSON/CSRF.
- `frontend/src/modules/actualizar-cronograma/dominio/normalizarActualizacion.ts` and `.test.ts`.
- `frontend/src/modules/actualizar-cronograma/dominio/filtrarActualizacion.ts` and `.test.ts`.
- `frontend/src/modules/actualizar-cronograma/dominio/validarActualizacion.ts` and `.test.ts`.
- `frontend/src/modules/actualizar-cronograma/dominio/maquinaImportacion.ts` and `.test.ts`.
- `frontend/src/modules/actualizar-cronograma/dominio/revisionAsociaciones.ts` and `.test.ts`.
- `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts` and `.test.tsx`.
- `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.tsx` and `.test.tsx`.
- `frontend/src/modules/actualizar-cronograma/componentes/ContextoActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/ToolbarActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/FiltrosActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/TablaActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/TarjetasActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/EditorActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/DialogoImportarCronograma.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/ResumenImportacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/RevisionAsociaciones.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/DialogoEliminarBorrador.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/componentes/LeyendaActualizacion.tsx` and test.
- `frontend/src/modules/actualizar-cronograma/actualizar-cronograma.css`.
- `tests/browser/fixtures/programa-general-actualizar-react.mjs`.
- `tests/browser/programa-general-actualizar-react.spec.mjs`.
- `tests/browser/programa-general-actualizar-react.a11y.mjs`.
- `tests/browser/programa-general-actualizar-react.visual.mjs` — candidate runner; no approved golden
  until Task 13 gate.

### Modify during implementation

- `public/index.php` — seven new API routes; canonical page route only in Task 14.
- `frontend/src/shell/rutas.tsx` and `.test.tsx` — pilot/canonical React route.
- `.gitignore` — ignore `storage/programa-general-actualizar-imports/`.
- `src/Core/SpaRouter.php` and its route contract — canonical GET/HEAD cut in Task 14 only.
- `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — S06 closure ledger.
- `docs/design-system/manifests/programa-general-actualizar.json` — React sources/layouts/states/tests
  and approved scenarios at cut.
- `docs/design-system/manifests/inventory.json`, `docs/design-system/ui-groups-inventory.json`,
  `docs/design-system/unlayered-delivery-inventory.json`, `docs/design-system/exceptions.json`,
  `docs/design-system/state-token-exceptions.json`, `docs/design-system/state-tint-exceptions.json`
  only where S06 legacy entries actually disappear.
- `tests/design-system/state-tint-pairing.test.mjs` — remove the legacy CSS special case at cut.
- `src/Controllers/Api/GeneralApiController.php` — remove legacy-exclusive actions/helpers only after
  caller search; shared list/update/codes and helpers used by S05 remain.
- `public/js/design-system/save-status.js`, `public/css/profesionales.css`,
  `public/css/escalamientos.css`, `public/css/change-monitor.css` only if their active comments still
  claim a dependency on deleted S06 assets; no behavior change.

### Delete only after Task 14 gate

- `views/programa-general-actualizar/programaGeneralActualizar.view.php`.
- `src/Controllers/Programacion/ProgramaGeneralActualizarController.php`.
- `public/js/modules/programa_actualizar/hot_actualizar.js`.
- `public/js/modules/programa_actualizar/rule_engine.js`.
- `public/js/modules/programa_actualizar/decision_logger.js`.
- `public/css/programa-general-actualizar.css`.
- Legacy `tests/browser/programa-general-actualizar.visual.mjs` only when its approved React
  replacement and manifest scenario are committed together.

Never delete `public/archivosBase/actualizacionCronogramaLPS.xlsx`,
`public/js/HandsontableTomSelectEditor.js`, shared Handsontable/TomSelect assets or `/api/general/*`
methods without a zero-caller search proving exclusivity.

## API and Service Signatures

The implementation must preserve these public boundaries:

```php
interface ProgramaGeneralActualizarStore
{
    public function projectContext(): array;
    public function maxActiveWeek(): int;
    public function maxProgramWeek(): int;
    public function countRows(int $week, bool $activitiesOnly = false): int;
    public function countUnmappedRows(int $week): int;
    public function activityRows(int $week): array;
    public function activity(int $week, int $uniqueId): ?array;
    public function transaction(callable $operation): mixed;
    public function confirmImport(array $parsed, array $context, ?string $firstWeekStart): array;
    public function updateRow(int $week, int $uniqueId, array $values): array;
    public function inheritFromSource(int $baseWeek, int $targetWeek, int $sourceId, int $targetId): array;
    public function deleteDraft(int $targetWeek): int;
    public function appendDecision(array $decision): int;
}
```

```php
final class ProgramaGeneralActualizarActionPolicy
{
    public function resolve(
        bool $canEdit,
        int $baseWeek,
        int $targetWeek,
        int $maxActiveWeek,
        int $baseActivityRows,
        int $targetActivityRows,
    ): array;
}
```

```ts
export const obtenerContextoActualizacion: (signal?: AbortSignal) => Promise<ContextoActualizacion>;
export const listarSemanaActualizacion: (semana: number, signal?: AbortSignal) => Promise<FilaGeneralRaw[]>;
export const previsualizarImportacion: (archivo: File, firstWeekStart: string | null, csrf: string) => Promise<PreviewImportacion>;
export const confirmarImportacion: (importToken: string, csrf: string) => Promise<ResultadoImportacion>;
export const guardarFilaActualizacion: (payload: GuardarFilaPayload, csrf: string) => Promise<FilaActualizacionRaw>;
export const autoAsociarCronograma: (targetWeek: number, csrf: string) => Promise<ResultadoAutoAsociacion>;
export const guardarRevisiones: (payload: GuardarRevisionesPayload, csrf: string) => Promise<ResultadoRevisiones>;
export const eliminarBorradorCronograma: (targetWeek: number, csrf: string) => Promise<ResultadoEliminarBorrador>;
```

## Task 1: Context resolver, action policy and endpoint

**Files:**

- Create: `src/Security/ProgramaGeneralActualizarActionPolicy.php`
- Create: `src/Services/ProgramaGeneralActualizarStore.php`
- Create: `src/Services/DatabaseProgramaGeneralActualizarStore.php`
- Create: `src/Services/ProgramaGeneralActualizarContextService.php`
- Create: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Create: `tests/test_programa_general_actualizar_action_policy.php`
- Create: `tests/test_programa_general_actualizar_context_contract.php`
- Modify: `public/index.php`

- [ ] **Step 1: Write failing pure context/action tests**

  Cover exact resolver cases without constructing `Database`:

  ```php
  $cases = [
      ['maxActive' => 0,  'maxProgram' => 0,  'base' => 0,  'target' => 1,  'mode' => 'initial'],
      ['maxActive' => 18, 'maxProgram' => 18, 'base' => 18, 'target' => 19, 'mode' => 'next'],
      ['maxActive' => 18, 'maxProgram' => 19, 'base' => 18, 'target' => 19, 'mode' => 'draft'],
      ['maxActive' => 0,  'maxProgram' => 2,  'base' => 0,  'target' => 2,  'mode' => 'draft'],
  ];
  ```

  Assert a viewer gets all actions false; an editor gets import always, row/match only with a draft,
  autoassociate only with base/target activities, delete only target > max active. Assert service
  never changes `$_SESSION['semana']`.

  Context contract uses an in-memory fake store and asserts exact keys/types, effective thresholds,
  safe project fields, `Cache-Control: no-store`, CSRF key and no `dbPrefix`/role/user/table names.

- [ ] **Step 2: Run RED and record the expected missing-class failure**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_action_policy.php
  docker compose exec app php tests/test_programa_general_actualizar_context_contract.php
  ```

  Expected: both non-zero because policy/service/controller do not exist. If either passes before
  implementation, strengthen the assertion rather than accepting a false RED.

- [ ] **Step 3: Implement the smallest project-scoped context boundary**

  Implement resolver formula exactly from S06-R1. `DatabaseProgramaGeneralActualizarStore` resolves
  `ModuleRequestContext` and current `ProjectScope` once, and every SQL includes `project_id = ?`.
  It accepts no prefix/project argument from controller.

  Controller method:

  ```php
  public function context(): void
  {
      $this->requireAuth();
      $this->authorizePermission('lps.programa_general_actualizar.ver');
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store');

      $canEdit = (new RbacService($this->db))->can('lps.programa_general_actualizar.editar');
      $data = $this->contextService->resolve($canEdit);
      $data['csrf'] = ['actualizarCronograma' => CsrfTokenManager::generate('programa_general_actualizar_save')];
      echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  }
  ```

  Register only `GET /api/programa-general-actualizar/context`; do not touch the page route.

- [ ] **Step 4: Run GREEN plus static scope guard**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_action_policy.php
  docker compose exec app php tests/test_programa_general_actualizar_context_contract.php
  docker compose exec app php tests/test_global_table_safety.php
  ```

  Expected: focused tests pass; safety test passes without mutation. Inspect output for zero SQL
  lacking project predicates. Do not run a live context request.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add public/index.php src/Security/ProgramaGeneralActualizarActionPolicy.php src/Services/ProgramaGeneralActualizarStore.php src/Services/DatabaseProgramaGeneralActualizarStore.php src/Services/ProgramaGeneralActualizarContextService.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php tests/test_programa_general_actualizar_action_policy.php tests/test_programa_general_actualizar_context_contract.php
  git commit -m "feat(actualizar-cronograma): define contexto autorizado del borrador"
  ```

## Task 2: Strict Zod contracts and S06 HTTP gateway

**Files:**

- Create: `frontend/src/lib/api/esquemas/actualizar-cronograma.ts`
- Create: `frontend/src/lib/api/esquemas/actualizar-cronograma.test.ts`
- Create: `frontend/src/lib/api/actualizar-cronograma.ts`
- Create: `frontend/src/lib/api/actualizar-cronograma.test.ts`
- Read/verify: `frontend/src/lib/api/cliente.ts`
- Reuse: `frontend/src/lib/api/esquemas/programa-general.ts` from S05

- [ ] **Step 1: Write failing schema and request-shape tests**

  Context schema must be `.strict()` at envelope/data/project/schedule/actions/csrf/matching/links.
  Define strict schemas for target row, preview, confirm, save, autoassociate, review batch, delete
  and normalized error. Export every TS type via `z.infer<typeof schema>` only.

  Gateway tests spy the S05 client transport and assert:

  ```ts
  expect(request.url).toBe('/api/general/list?semana=19');
  expect(request.url).not.toMatch(/db=|project_id=/);
  expect(preview.body).toBeInstanceOf(FormData);
  expect(preview.headers).not.toHaveProperty('Content-Type');
  expect(save.body).toEqual({ targetWeek: 19, uniqueId: 8401, changes: { sourceUniqueId: 6120 } });
  ```

  Add negative fixtures: extra context key, threshold string, malformed date, confidence >1,
  `decision:'accept'` without source and response containing a client-authored project prefix.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- src/lib/api/esquemas/actualizar-cronograma.test.ts src/lib/api/actualizar-cronograma.test.ts
  ```

  Expected: missing modules. Confirm no test imports an interface duplicated from Zod.

- [ ] **Step 3: Implement schemas and gateway on the common client**

  Use S05 `peticionJson`, `peticionFormulario`/generic request and `AbortSignal`. The gateway builds
  `FormData` itself, appends `firstWeekStart` only when non-null, passes CSRF through the client's
  header option and performs no retry.

  The legacy `/api/general/list` raw schema may be shared/imported, but S06 owns a target-row adapter
  schema that includes `programaAnteriorAsociar`. Do not weaken S05 strict output models.

- [ ] **Step 4: Run GREEN and client regression tests**

  ```bash
  npm --prefix frontend test -- src/lib/api/esquemas/actualizar-cronograma.test.ts src/lib/api/actualizar-cronograma.test.ts src/lib/api/cliente.test.ts
  npm --prefix frontend run typecheck
  ```

  Expected: all pass; no production file except `cliente.ts` contains `fetch(`.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/lib/api/esquemas/actualizar-cronograma.ts frontend/src/lib/api/esquemas/actualizar-cronograma.test.ts frontend/src/lib/api/actualizar-cronograma.ts frontend/src/lib/api/actualizar-cronograma.test.ts
  git commit -m "feat(actualizar-cronograma): tipa la frontera HTTP"
  ```

## Task 3: Domain normalization, filters and validation

**Files:**

- Create: `frontend/src/modules/actualizar-cronograma/dominio/normalizarActualizacion.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/normalizarActualizacion.test.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/filtrarActualizacion.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/filtrarActualizacion.test.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/validarActualizacion.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/validarActualizacion.test.ts`
- Reuse: S05 date/unit/ratio helpers

- [ ] **Step 1: Write RED examples for real legacy shapes**

  Include rows with HTML activity, numeric strings, empty unit, `*No Asociada*`, null association,
  duplicate source names, unknown unit and physical progress. Required assertions:

  ```ts
  expect(normalizada.name).toBe('Actividad, [Capítulo: Estructura]');
  expect(normalizada.sourceAssociation).toBeNull();
  expect(normalizada.unit).toBe('%');
  expect(normalizada.progressRatio).toBe(0.62);
  ```

  Filter tests cover default pending, full, accents/case/HTML, Id/source/chapter, `visible/total/
  pending/associated` counts and invalid URL normalization.

  Validator tests cover ISO calendar validity/order, `%` quantity null, physical limit, mapped-row
  restrictions, association-exclusive payload and source ID requirement.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- src/modules/actualizar-cronograma/dominio
  ```

  Expected: missing modules. Preserve fixtures as plain objects parsed through schemas first.

- [ ] **Step 3: Implement pure functions with one canonical model**

  Export:

  ```ts
  export function normalizarActividadActualizacion(raw: FilaActualizacionRaw, fuentes: FuenteBase[]): ActividadActualizacion;
  export function filtrarActualizacion(rows: ActividadActualizacion[], query: FiltroActualizacion): ResultadoFiltroActualizacion;
  export function validarEdicionActualizacion(row: ActividadActualizacion, draft: DraftActualizacion): ResultadoValidacion;
  export function construirPayloadGuardado(row: ActividadActualizacion, draft: DraftActualizacion, targetWeek: number): GuardarFilaPayload;
  ```

  Strip HTML through a DOM-free/text helper suitable for tests; never use `dangerouslySetInnerHTML`.
  Match legacy association name to a source only when unique; ambiguous/missing stays read-only by
  name until explicit source selection.

- [ ] **Step 4: Run GREEN and typecheck**

  ```bash
  npm --prefix frontend test -- src/modules/actualizar-cronograma/dominio
  npm --prefix frontend run typecheck
  ```

  Expected: all pure cases pass; no date is converted to `Date`.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add frontend/src/modules/actualizar-cronograma/dominio
  git commit -m "feat(actualizar-cronograma): normaliza filas filtros y validaciones"
  ```

## Task 4: Read-only vertical slice and responsive renderers

**Files:**

- Create: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts`
- Create: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.test.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/ContextoActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/ToolbarActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/FiltrosActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/TablaActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/TarjetasActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/LeyendaActualizacion.tsx`
- Create focused component tests beside each file
- Create: `frontend/src/modules/actualizar-cronograma/actualizar-cronograma.css`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

- [ ] **Step 1: Write failing page/hook/responsive tests**

  With mocked gateways, assert context loads before base/target; `baseWeek=0` skips base; project ID
  change aborts both reads; target failure is error, base failure is partial error; viewer sees no
  mutation buttons.

  Component tests assert semantic caption/headers at table mode and cards at mobile mode, never both:

  ```ts
  expect(screen.getByRole('table', { name: /actualización del cronograma/i })).toBeVisible();
  expect(screen.queryByTestId('actualizacion-cards')).not.toBeInTheDocument();
  ```

  Cover no draft CTA, all-associated empty, loading skeleton, full/pending toggle, search/counts,
  details expansion and return to `/programa-general` through router.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- src/modules/actualizar-cronograma src/shell/rutas.test.tsx
  ```

  Expected: route/page/components absent.

- [ ] **Step 3: Implement read-only pilot slice**

  Add `/app/programa-general-actualizar` and the module route expected by T01's router conventions.
  The hook owns cancellation and stale-project checks. `useMediaQuery('(max-width: 767px)')` mounts
  cards only below 768; CSS changes column visibility/details at tablet without JS user-agent tests.

  Style uses scoped `.actualizar-cronograma` selectors and tokens. Do not modify
  `public/css/tokens.css` unless a genuinely missing semantic token is documented and approved; use
  existing active surface/text/border/state/focus tokens first.

- [ ] **Step 4: Run GREEN plus frontend baseline**

  ```bash
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  ```

  Expected: complete frontend suite/build green, route remains pilot only, no legacy file changed.

- [ ] **Step 5: Future atomic commit and vertical checkpoint 1**

  ```bash
  git add frontend/src/modules/actualizar-cronograma frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
  git commit -m "feat(actualizar-cronograma): entrega lectura responsive"
  ```

  **Checkpoint 1:** viewer can inspect deterministic draft, filters and counts at all responsive
  modes/themes using mocked HTTP; no mutation/API legacy cut has happened.

## Task 5: Pure XLSX parser, upload guard and preview contract

**Files:**

- Create: `src/Services/ProgramaGeneralActualizarUploadValidator.php`
- Create: `src/Services/ProgramaGeneralActualizarImportParser.php`
- Create: `src/Services/ProgramaGeneralActualizarImportStore.php`
- Create: `src/Services/ProgramaGeneralActualizarImportService.php`
- Create: `tests/test_programa_general_actualizar_import_parser.php`
- Create: `tests/test_programa_general_actualizar_import_preview_contract.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`
- Modify: `.gitignore`

- [ ] **Step 1: Write RED parser/upload/preview tests**

  Generate disposable XLSX workbooks in PHP test temp directories and remove them in `finally`.
  Cover all header aliases, optional Unique ID, flags, hierarchy, date objects/serials/four textual
  forms, empty schema rows, duplicate IDs, missing required headers, invalid/out-of-order dates and
  zero usable rows.

  Upload guard table:

  ```php
  $cases = [
      ['error' => UPLOAD_ERR_NO_FILE, 'code' => 'INVALID_FILE'],
      ['error' => UPLOAD_ERR_INI_SIZE, 'code' => 'FILE_TOO_LARGE'],
      ['name' => 'cronograma.xls', 'code' => 'INVALID_FILE'],
      ['size' => 10_485_761, 'code' => 'FILE_TOO_LARGE'],
      ['mime' => 'text/plain', 'code' => 'INVALID_FILE'],
  ];
  ```

  Preview controller uses fake import service/store/context and asserts 403/CSRF/422/413,
  first-week date, metadata shape, TTL, binding fields and that no database-store mutation method is
  called.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_import_parser.php
  docker compose exec app php tests/test_programa_general_actualizar_import_preview_contract.php
  ```

  Expected: missing classes/routes. Verify the test database query count is zero.

- [ ] **Step 3: Extract existing parser semantics and implement preview**

  Move/copy behavior from `GeneralApiController` only after characterization. Parser output shape is
  closed and contains normalized rows plus summary; it performs no SQL. Upload validator uses the
  10 MiB/PDC MIME pattern and converts vendor exceptions to safe messages.

  Store path: `storage/programa-general-actualizar-imports/`; random 32-hex token, `.xlsx` + `.json`,
  `.htaccess` deny, 900-second TTL, opportunistic cleanup, metadata with session user, project ID,
  hash, base/target and preview summary. Add the directory to `.gitignore`.

  `ProgramaGeneralActualizarImportService::preview()` composes upload validation, parser, context and
  temporary store. Register `POST /api/programa-general-actualizar/import/preview` and validate CSRF
  key before file work. Do not alter `/api/general/import`.

- [ ] **Step 4: Run GREEN and ensure no DML**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_import_parser.php
  docker compose exec app php tests/test_programa_general_actualizar_import_preview_contract.php
  git diff --check
  ```

  Expected: tests pass, temporary directories removed, no SQL mutation executed.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add .gitignore public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarUploadValidator.php src/Services/ProgramaGeneralActualizarImportParser.php src/Services/ProgramaGeneralActualizarImportStore.php src/Services/ProgramaGeneralActualizarImportService.php tests/test_programa_general_actualizar_import_parser.php tests/test_programa_general_actualizar_import_preview_contract.php
  git commit -m "feat(actualizar-cronograma): previsualiza importacion XLSX segura"
  ```

## Task 6: Confirm import service behind a non-DML test seam

**Files:**

- Modify: `src/Services/ProgramaGeneralActualizarImportService.php`
- Create: `tests/test_programa_general_actualizar_import_confirm_contract.php`
- Modify: `src/Services/ProgramaGeneralActualizarStore.php`
- Modify: `src/Services/DatabaseProgramaGeneralActualizarStore.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`

- [ ] **Step 1: Write failing confirm tests with fake store**

  Fake store records transaction calls but performs no SQL. Test valid `initial-created`,
  `draft-created`, `draft-replaced`; token missing/malformed/expired/used; user/project/hash/base/
  target mismatch; parser failure after preview; transaction exception; single-use deletion only
  after success.

  Assert initial result has `redirectTo:'/programa-general'` and no autoassociate; draft result has
  null redirect and `shouldAutoAssociate:true`.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_import_confirm_contract.php
  ```

  Expected: missing service/controller action.

- [ ] **Step 3: Implement revalidation and transactional adapter**

  `ProgramaGeneralActualizarImportService::confirm(string $token, array $sessionIdentity)` must:

  1. load route/meta and reject malformed/expired;
  2. compare user and active project;
  3. recompute SHA-256 and base/target context;
  4. parse again from stored XLSX;
  5. call one store transaction;
  6. remove token only on success; expired/invalid cleanup is safe;
  7. return normalized counts/result.

  Database store moves the legacy transaction body without changing SQL/ID/hierarchy/recalculation
  semantics. Every query is active-project scoped. It accepts parsed rows/context, never client
  prefix/project. Register `POST /api/programa-general-actualizar/import/confirm`; preserve the
  legacy action for rollback.

- [ ] **Step 4: Run GREEN plus static SQL and PHP analysis focused on new files**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_import_confirm_contract.php
  docker compose exec app vendor/bin/phpstan analyse src/Services/ProgramaGeneralActualizarImportService.php src/Services/DatabaseProgramaGeneralActualizarStore.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php --memory-limit=1G
  docker compose exec app php tests/test_global_table_safety.php
  ```

  Expected: green; do not invoke confirm over HTTP or run existing import integration tests.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarStore.php src/Services/DatabaseProgramaGeneralActualizarStore.php src/Services/ProgramaGeneralActualizarImportService.php tests/test_programa_general_actualizar_import_confirm_contract.php
  git commit -m "feat(actualizar-cronograma): confirma importacion con contexto revalidado"
  ```

## Task 7: React import state machine and dialogs

**Files:**

- Create: `frontend/src/modules/actualizar-cronograma/dominio/maquinaImportacion.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/maquinaImportacion.test.ts`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/DialogoImportarCronograma.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/DialogoImportarCronograma.test.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/ResumenImportacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/ResumenImportacion.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/actualizar-cronograma.css`

- [ ] **Step 1: Write RED state-machine/dialog tests**

  States: `idle`, `file-selected`, `previewing`, `preview-ready`, `confirming`, `success`, `error`,
  `expired`. Illegal transitions throw in tests. Cover first date required only for initial, accept
  `.xlsx`, accessible filename/size, preview summary, warnings, replace copy, double-submit lock,
  focus on error, dirty-close confirmation and no timer redirect.

  Hook test asserts draft success reloads context/target/base then calls autoassociate exactly once;
  initial success exposes a user-triggered navigation action only.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- src/modules/actualizar-cronograma/dominio/maquinaImportacion.test.ts src/modules/actualizar-cronograma/componentes/DialogoImportarCronograma.test.tsx src/modules/actualizar-cronograma/componentes/ResumenImportacion.test.tsx src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx
  ```

  Expected: missing state machine/components/handlers.

- [ ] **Step 3: Implement accessible preview/confirm UI**

  Use the shell dialog primitive from T01. The file input remains in DOM with a label; selecting a
  file never stores it beyond component state. Preview errors render a summary plus per-row list and
  `aria-describedby`. Confirm uses server preview values, not client recalculation.

  On draft confirm, close dialog only after server success, announce outcome, clear file/token, reload
  and then request autoassociate. If autoassociate fails, import remains success and a separate retry
  action appears.

- [ ] **Step 4: Run GREEN and full frontend checks**

  ```bash
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  ```

  Expected: green; no raw file/token in local/session storage tests.

- [ ] **Step 5: Future atomic commit and vertical checkpoint 2**

  ```bash
  git add frontend/src/modules/actualizar-cronograma
  git commit -m "feat(actualizar-cronograma): completa preview y confirmacion XLSX"
  ```

  **Checkpoint 2:** initial/draft import is fully operable against fake/intercepted contracts; no
  live mutation, canonical cut or legacy deletion.

## Task 8: Dedicated row save and inheritance service

**Files:**

- Create: `src/Services/ProgramaGeneralActualizarRowService.php`
- Create: `tests/test_programa_general_actualizar_save_contract.php`
- Modify: `src/Services/ProgramaGeneralActualizarStore.php`
- Modify: `src/Services/DatabaseProgramaGeneralActualizarStore.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`

- [ ] **Step 1: Write RED validation, permission and inheritance tests**

  Use a fake store/context. Cover missing/extra fields, wrong target, active target, missing row,
  chapter, invalid dates/order, unit, quantity, ratio, mapped-row ordinary edits, source missing/
  wrong base/chapter, association exclusive, unmap and inheritance response.

  Assert controller rejects view-only and invalid CSRF before service call; response has full row and
  `inherited`; request keys `db/project/user/role` are ignored/rejected as extra.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_save_contract.php
  ```

  Expected: route/service missing.

- [ ] **Step 3: Implement dedicated save**

  `RowService::save(array $payload, array $identity)` recomputes context, validates one target row,
  normalizes persisted+incoming values and calls either `updateRow` or a transaction containing
  `inheritFromSource` plus server response. Association source ID is authoritative only after store
  verifies base/project; stored name is derived.

  Do not call `GeneralApiController::update()` or accept its compatibility flags. Register
  `POST /api/programa-general-actualizar/save` with update permission/CSRF.

- [ ] **Step 4: Run GREEN and regression characterization**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_save_contract.php
  docker compose exec app php tests/test_programa_general_input_validation.php
  docker compose exec app vendor/bin/phpstan analyse src/Services/ProgramaGeneralActualizarRowService.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php --memory-limit=1G
  ```

  Expected: S06 and S05 pure validation contracts green; no DB suites.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarStore.php src/Services/DatabaseProgramaGeneralActualizarStore.php src/Services/ProgramaGeneralActualizarRowService.php tests/test_programa_general_actualizar_save_contract.php
  git commit -m "feat(actualizar-cronograma): guarda filas y herencia por ID estable"
  ```

## Task 9: Editable table/cards and per-row recovery

**Files:**

- Create: `frontend/src/modules/actualizar-cronograma/componentes/EditorActualizacion.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/EditorActualizacion.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/componentes/TablaActualizacion.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/componentes/TablaActualizacion.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/componentes/TarjetasActualizacion.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/componentes/TarjetasActualizacion.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/actualizar-cronograma.css`

- [ ] **Step 1: Write failing interaction/recovery tests**

  Cover association search by ID/name/date, duplicate-name disambiguation, replace/unmap confirmation,
  mapped row locks, date edits, unit/quantity/progress conversion, desktop blur/Enter save, mobile
  explicit Save/Discard, one active mutation per row, parallel different rows, server row replacement,
  network error retry/discard and dirty navigation guard.

  Assert `Guardando`, `Guardado`, validation and network error live regions. Assert no optimistic
  inherited values/count changes before server response.

- [ ] **Step 2: Run RED**

  ```bash
  npm --prefix frontend test -- src/modules/actualizar-cronograma/componentes/EditorActualizacion.test.tsx src/modules/actualizar-cronograma/componentes/TablaActualizacion.test.tsx src/modules/actualizar-cronograma/componentes/TarjetasActualizacion.test.tsx src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx
  ```

  Expected: edit paths absent.

- [ ] **Step 3: Implement shared editor state**

  Keep draft/save state in hook keyed by target unique ID. Table and card receive the same edit
  commands, action booleans and source options. Desktop commits explicit select/Enter/blur after
  validation; mobile requires Save. Error keeps draft and focus. Success normalizes/replaces the
  complete row, updates counts and clears dirty.

  `beforeunload` only warns; it never attempts to flush a mutation. Route/project switch uses T01
  blocker/confirmation and clears state only after user confirms.

- [ ] **Step 4: Run GREEN and frontend baseline**

  ```bash
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  ```

  Expected: all green at both table/card modes and read-only remains unchanged.

- [ ] **Step 5: Future atomic commit and vertical checkpoint 3**

  ```bash
  git add frontend/src/modules/actualizar-cronograma
  git commit -m "feat(actualizar-cronograma): habilita mapeo y edicion responsive"
  ```

  **Checkpoint 3:** authorized user can map/edit/recover a row via fakes at all responsive modes;
  viewer cannot mutate; server contract owns final values.

## Task 10: Autoassociation service with effective thresholds and server audit

**Files:**

- Create: `src/Services/ProgramaGeneralActualizarDecisionLogService.php`
- Create: `src/Services/ProgramaGeneralActualizarAssociationService.php`
- Create: `tests/test_programa_general_actualizar_association_contract.php`
- Modify: `src/Services/ProgramaGeneralActualizarStore.php`
- Modify: `src/Services/DatabaseProgramaGeneralActualizarStore.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`
- Reuse: `src/Services/ActivityMatcherService.php`

- [ ] **Step 1: Write RED autoassociate/audit contracts**

  Characterize matcher defaults/configured values and exact classifications. Fake store cases: empty
  target, empty base, exact/high/medium/none, existing association untouched, repeated operation
  idempotent, max five deterministic candidates, source IDs included, HTML stripped.

  Assert auto exact/high inheritance and decision log share one transaction. Logger identity must be
  server arguments (`dbPrefix`, `$_SESSION['usuario']` supplied by controller identity adapter), not
  request keys. A logger failure rolls back fake transaction.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  ```

  Expected: service/route absent. If matcher characterization fails, diagnose before changing it;
  do not update thresholds to satisfy UI assumptions.

- [ ] **Step 3: Implement autoassociate endpoint**

  Service recomputes base/target, maps store rows to `ActivityMatcherService`, uses
  `ActivityMatcherService::getThresholds()`, selects IDs/names safely and transactionally inherits/
  logs identical/high only when target association is empty. Build review candidates from source IDs,
  WBS, text, chapter/date/confidence.

  Register `POST /api/programa-general-actualizar/auto-associate`. Preserve old alias for VIEW-33;
  do not let new React call `/api/general/auto-associate`.

- [ ] **Step 4: Run GREEN and matcher regression suite**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  docker compose exec app vendor/bin/phpstan analyse src/Services/ProgramaGeneralActualizarAssociationService.php src/Services/ProgramaGeneralActualizarDecisionLogService.php --memory-limit=1G
  ```

  Expected: exact service and existing matcher contracts green, zero DML.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarStore.php src/Services/DatabaseProgramaGeneralActualizarStore.php src/Services/ProgramaGeneralActualizarDecisionLogService.php src/Services/ProgramaGeneralActualizarAssociationService.php tests/test_programa_general_actualizar_association_contract.php
  git commit -m "feat(actualizar-cronograma): autoasocia con umbrales y trazabilidad servidor"
  ```

## Task 11: Atomic review batch and accessible review UI

**Files:**

- Create: `frontend/src/modules/actualizar-cronograma/dominio/revisionAsociaciones.ts`
- Create: `frontend/src/modules/actualizar-cronograma/dominio/revisionAsociaciones.test.ts`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/RevisionAsociaciones.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/RevisionAsociaciones.test.tsx`
- Modify: `src/Services/ProgramaGeneralActualizarAssociationService.php`
- Modify: `tests/test_programa_general_actualizar_association_contract.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.tsx`
- Modify: `frontend/src/modules/actualizar-cronograma/actualizar-cronograma.css`

- [ ] **Step 1: Write failing backend batch and frontend state/UI tests**

  Backend: duplicate target, accept without source, skip with source, wrong target/base, invalid source,
  one invalid among many rolls all back, accept/skip/correct counts, full rows and server-derived
  decision metadata.

  Frontend reducer/UI: four stats, pending/processed tabs, top three + expand to five, candidates as
  radios, ID/chapter/date/confidence, accept, skip, change/revert existing, dirty close, save count,
  disabled no-change batch, success/error preserving decisions, keyboard/focus/ARIA.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  npm --prefix frontend test -- src/modules/actualizar-cronograma/dominio/revisionAsociaciones.test.ts src/modules/actualizar-cronograma/componentes/RevisionAsociaciones.test.tsx src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx
  ```

  Expected: batch/UI assertions fail.

- [ ] **Step 3: Implement one true batch and reducer-driven UI**

  Add `POST /api/programa-general-actualizar/associations/batch`. Validate every decision before the
  transaction. For accept, derive whether audit action is `accept` or `correct` from the matcher
  suggestion held/recomputed server-side; for skip, store compatibility marker without clearing
  inherited fields. Append audit and row update inside the same transaction.

  Frontend reducer is the only decision-state owner. Modal uses DS tabs/dialog/buttons/chips and no
  concatenated HTML or inline confidence width; express bar progress via semantic CSS custom property
  assigned through an allowed data attribute/class scale, or `<progress>`.

- [ ] **Step 4: Run GREEN across PHP/frontend focused suites**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  npm --prefix frontend test -- src/modules/actualizar-cronograma
  npm --prefix frontend run typecheck
  ```

  Expected: batch all-or-none and UI interaction tests green.

- [ ] **Step 5: Future atomic commit and vertical checkpoint 4**

  ```bash
  git add public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarAssociationService.php tests/test_programa_general_actualizar_association_contract.php frontend/src/modules/actualizar-cronograma
  git commit -m "feat(actualizar-cronograma): revisa asociaciones en lote atomico"
  ```

  **Checkpoint 4:** autoapply, medium review, skip/change and real batch are complete through
  fake/intercepted contracts with server-owned audit.

## Task 12: Draft-only delete and complete recovery states

**Files:**

- Create: `tests/test_programa_general_actualizar_delete_contract.php`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/DialogoEliminarBorrador.tsx`
- Create: `frontend/src/modules/actualizar-cronograma/componentes/DialogoEliminarBorrador.test.tsx`
- Modify: `src/Services/ProgramaGeneralActualizarStore.php`
- Modify: `src/Services/DatabaseProgramaGeneralActualizarStore.php`
- Modify: `src/Controllers/Api/ProgramaGeneralActualizarApiController.php`
- Modify: `public/index.php`
- Modify: `frontend/src/modules/actualizar-cronograma/useActualizarCronograma.ts`
- Modify: `frontend/src/modules/actualizar-cronograma/ActualizarCronogramaPage.tsx`
- Modify focused tests/CSS

- [ ] **Step 1: Write RED delete and recovery tests**

  Backend fake-store cases: editor+draft success count, viewer 403, invalid CSRF, no draft 409,
  requested target mismatch 409, target <= max active protected 409, transaction failure 500 safe.
  Assert no soft-reset method exists/calls.

  UI tests assert button only with action true, dialog names week/row count/irreversibility, cancel no
  call, double submit lock, success reload, error keeps dialog/retry, focus restoration. Add top-level
  tests for context/list/base/import/save/match/batch/delete error envelopes and session expiry.

- [ ] **Step 2: Run RED**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_delete_contract.php
  npm --prefix frontend test -- src/modules/actualizar-cronograma/componentes/DialogoEliminarBorrador.test.tsx src/modules/actualizar-cronograma/ActualizarCronogramaPage.test.tsx src/modules/actualizar-cronograma/useActualizarCronograma.test.tsx
  ```

  Expected: endpoint/dialog absent or missing states.

- [ ] **Step 3: Implement draft-only deletion and recovery copy**

  Controller/service recompute context and call `deleteDraft(targetWeek)` only when target > max
  active and rows exist. No branch resets active data. Response includes deleted rows/base/target.
  Register `POST /api/programa-general-actualizar/delete-draft` behind update permission and the S06
  CSRF key; preserve `/api/general/delete-update` only for VIEW-33 rollback until Task 14.

  Page distinguishes empty from errors, exposes retry at the failing boundary, preserves target on
  base-load failure, disables only dependent mapping actions and delegates 401 to T01 session handler.

- [ ] **Step 4: Run GREEN and complete focused suites**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_delete_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_context_contract.php
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  ```

  Expected: green; no live mutation.

- [ ] **Step 5: Future atomic commit**

  ```bash
  git add public/index.php src/Controllers/Api/ProgramaGeneralActualizarApiController.php src/Services/ProgramaGeneralActualizarStore.php src/Services/DatabaseProgramaGeneralActualizarStore.php tests/test_programa_general_actualizar_delete_contract.php frontend/src/modules/actualizar-cronograma
  git commit -m "feat(actualizar-cronograma): protege y elimina solo el borrador"
  ```

## Task 13: Intercepted pilot QA, RBAC, accessibility and visual candidates

**Files:**

- Create: `tests/browser/fixtures/programa-general-actualizar-react.mjs`
- Create: `tests/browser/programa-general-actualizar-react.spec.mjs`
- Create: `tests/browser/programa-general-actualizar-react.a11y.mjs`
- Create: `tests/browser/programa-general-actualizar-react.visual.mjs`
- Create: `tests/test_programa_general_actualizar_routes.php`
- Modify: module/component CSS/tests only for findings rooted in S06

- [ ] **Step 1: Write failing browser/route scenarios before polishing**

  Install intercepts for `/api/session`, context, both list calls and every S06 mutation before
  `page.goto`. Fail any unhandled operational request. Fixtures include construction/preconstruction,
  chapters, mapped/unmapped/duplicate names/unknown unit, all match tiers, empty/error/read-only and
  import validation errors.

  Functional spec covers A/D/R/DCV editor, OT/V viewer, G/S/SG/C denied, initial/draft import,
  pending/full/search/counts, table/card edit, auto/review/batch/delete, dirty navigation, project
  cancellation and exact payload absence of scope fields.

  A11y covers dark/light × 390/768/1180/1440, keyboard-only upload/map/review/delete, dialogs/tabs,
  focus restoration, live regions, reduced motion, 200% zoom, no horizontal page overflow and
  blocking Axe findings.

  Route contract asserts pilot path is React, canonical remains PHP, `/api/*` and non-GET are not
  captured.

- [ ] **Step 2: Run RED and retain artifact-backed findings**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_routes.php
  npx playwright test tests/browser/programa-general-actualizar-react.spec.mjs tests/browser/programa-general-actualizar-react.a11y.mjs --workers=1
  ```

  Expected: new scenarios expose missing polish or route fixture wiring. Do not change expectations
  to match regressions; fix root S06 code only.

- [ ] **Step 3: Fix focused findings and generate uncommitted visual candidates**

  Run functional/a11y to green, then generate deterministic candidate screenshots for at least:

  - dark 1180×820 draft with mapped/unmapped rows;
  - light 1180×820 same data;
  - dark 390×844 editable cards;
  - dark 768×1024 tablet details;
  - import preview and association review states if manifest strategy requires state scenarios.

  Candidate command may use Playwright output paths, not `--update-snapshots` against approved files.
  Open candidates and inspect focus, clipping, text, both themes and realistic rows.

- [ ] **Step 4: Run pilot verification and present visual gate**

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_action_policy.php
  docker compose exec app php tests/test_programa_general_actualizar_context_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_import_parser.php
  docker compose exec app php tests/test_programa_general_actualizar_import_preview_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_import_confirm_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_save_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_delete_contract.php
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  npx playwright test tests/browser/programa-general-actualizar-react.spec.mjs tests/browser/programa-general-actualizar-react.a11y.mjs --workers=1
  ```

  Record each return code separately. Present candidate images and request explicit approval to
  version new goldens/cut canonical. If approval is absent, stop here with pilot intact; do not
  perform Task 14.

- [ ] **Step 5: Future atomic commit for non-visual pilot evidence only**

  ```bash
  git add tests/test_programa_general_actualizar_routes.php tests/browser/fixtures/programa-general-actualizar-react.mjs tests/browser/programa-general-actualizar-react.spec.mjs tests/browser/programa-general-actualizar-react.a11y.mjs tests/browser/programa-general-actualizar-react.visual.mjs frontend/src/modules/actualizar-cronograma
  git commit -m "test(actualizar-cronograma): verifica piloto React sin DML"
  ```

  Do not stage candidate PNGs or manifest hashes before approval.

## Task 14: Approved visual baseline, canonical cut and legacy retirement

**Prerequisite:** explicit approval of Task 13 visual candidates and explicit authorization to close
the implementation front. Without both, this task remains unexecuted.

**Files:**

- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `frontend/src/shell/rutas.tsx` and test
- Modify: `docs/design-system/manifests/programa-general-actualizar.json`
- Modify active design-system inventories/exceptions listed in File Structure
- Modify: `tests/design-system/state-tint-pairing.test.mjs`
- Replace: `tests/browser/programa-general-actualizar.visual.mjs`
- Add: approved React PNGs under its screenshot directory
- Delete exclusive VIEW-33/controller/JS/CSS files listed above
- Modify: `src/Controllers/Api/GeneralApiController.php` only for proven-zero-caller legacy actions
- Modify stale active comments only where dependency removal makes them false
- Modify: `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md`

- [ ] **Step 1: Capture approval metadata and prove callers before deletion**

  Record approved scenario IDs/viewports/themes and candidate hashes in the implementation log. Run:

  ```bash
  rg -n "programaGeneralActualizar\.view|ProgramaGeneralActualizarController|hot_actualizar|rule_engine|decision_logger|programa-general-actualizar\.css|/api/general/(import|delete-update|auto-associate|decision-log)" public views src frontend tests docs/design-system
  rg -n "HandsontableTomSelectEditor" public views src frontend tests
  ```

  Classify every hit. Historical audit/spec/plan prose is not a runtime caller. Preserve shared
  editor/vendor files if any runtime consumer remains. Write/update route retirement assertions
  before changing routes.

- [ ] **Step 2: Switch route and expect retirement tests RED**

  Update route tests first to expect canonical GET/HEAD React, legacy page controller/aliases absent,
  POST/API exclusions intact and manifest React sources/layouts/states. Run:

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_routes.php
  node scripts/design-system-contracts.mjs
  ```

  Expected: RED until route/assets/manifest are cut together.

- [ ] **Step 3: Perform minimal reversible cut and approved visual replacement**

  Add canonical route to the method-aware SPA router, remove PHP page route/controller/view and
  exclusive JS/CSS. Remove four legacy General API routes/methods only when caller search is zero;
  retain shared list/update/codes and any helper still referenced by S05.

  Update manifest:

  - sources point to React page/CSS/schema;
  - layouts include mobile/tablet/desktop/wide;
  - states list loading/empty/error/read-only/draft/import-preview/review;
  - vendors exclude jQuery/Bootstrap/Handsontable/TomSelect/jQuery UI for this module;
  - tests/evidence/scenarios point to approved React artifacts with recalculated SHA-256.

  Update machine-readable exceptions/inventories only for deleted files. Replace legacy visual test
  with deterministic React fixtures and approved PNGs. Keep template asset.

- [ ] **Step 4: Full post-cut verification, diff audit and rollback proof**

  Run focused tests first, then proportional static/frontend/browser/design gates. Read every return
  code on its own line:

  ```bash
  docker compose exec app php tests/test_programa_general_actualizar_routes.php
  docker compose exec app php tests/test_programa_general_actualizar_context_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_import_parser.php
  docker compose exec app php tests/test_programa_general_actualizar_import_preview_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_import_confirm_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_save_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_association_contract.php
  docker compose exec app php tests/test_programa_general_actualizar_delete_contract.php
  docker compose exec app php tests/test_global_table_safety.php
  docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npm --prefix frontend run build
  node scripts/design-system-contracts.mjs
  node --test tests/design-system/contracts.test.mjs tests/design-system/state-tint-pairing.test.mjs
  npx playwright test tests/browser/programa-general-actualizar-react.spec.mjs tests/browser/programa-general-actualizar-react.a11y.mjs tests/browser/programa-general-actualizar.visual.mjs --workers=1
  git diff --check
  git status --short
  ```

  Inspect network assertions and screenshots, verify template download remains versioned and prove
  rollback by reviewing that reverting the route/retirement commit restores VIEW-33 plus aliases.
  Do not execute rollback or DML.

- [ ] **Step 5: Future atomic cut commit and front closure workflow**

  Stage selectively after reviewing deleted/shared files:

  ```bash
  git add src/Core/SpaRouter.php public/index.php frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx docs/design-system/manifests/programa-general-actualizar.json docs/design-system/manifests/inventory.json docs/design-system/ui-groups-inventory.json docs/design-system/unlayered-delivery-inventory.json docs/design-system/exceptions.json docs/design-system/state-token-exceptions.json docs/design-system/state-tint-exceptions.json tests/design-system/state-tint-pairing.test.mjs tests/browser/programa-general-actualizar.visual.mjs tests/browser/__screenshots__/programa-general-actualizar.visual.mjs docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
  git add -u views/programa-general-actualizar src/Controllers/Programacion/ProgramaGeneralActualizarController.php public/js/modules/programa_actualizar public/css/programa-general-actualizar.css src/Controllers/Api/GeneralApiController.php
  git commit -m "feat(actualizar-cronograma): corta la ruta canonica a React"
  ```

  Add any active comment-only files only if actually changed and reviewed. Then follow the repository
  front-closing policy: verify clean status, fetch, inspect divergence, integrate `origin/main` into
  this branch if required, re-run the complete post-integration gate, record verified SHA, push branch,
  open PR to `main`, wait for CI green and merge only with authorization. Production deploy remains
  separately authorized and is not part of this plan.

## Vertical Checkpoints

| Checkpoint | Outcome | Required evidence | Legacy/cut state |
|---|---|---|---|
| 1 | Context + read responsive | PHP context/policy, Zod/gateway/domain/UI tests | VIEW-33 canonical |
| 2 | XLSX preview/confirm | parser/upload/token/fake confirm + import UI tests | VIEW-33 canonical |
| 3 | Manual mapping/edit | save/inheritance fake contract + table/card recovery | VIEW-33 canonical |
| 4 | Auto/review/delete | matcher/batch/audit/delete fake contracts + UI | VIEW-33 canonical |
| 5 | Approved cut | RBAC/a11y/visual/design-system/post-cut gates | React canonical |

No checkpoint executes DML. A green fake/intercepted mutation proves contract/orchestration, not live
database persistence. Any future live smoke requires separate authorization and a disposable,
reconciled environment.

## Traceability Matrix

| Spec criteria | Plan tasks |
|---|---|
| AC-01..08 route/context/permissions/shared reads/dead code | 1–4, 13–14 |
| AC-09..15 upload/parser/preview/confirm/results | 5–7 |
| AC-16..18 filters/table/tablet/cards | 3–4, 9, 13 |
| AC-19..22 mapping/heredity/validation/locks | 8–9 |
| AC-23..27 matcher/review/batch/audit | 10–11 |
| AC-28 delete draft | 12 |
| AC-29..32 states/HTTP/responsive/a11y | 2–4, 7, 9, 11–13 |
| AC-33..37 contracts/no-DML/cut/retirement/no-RLS | every task, especially 13–14 |

## Verification Commands That Are Explicitly Forbidden in S06

Do not run these during plan execution because they mutate shared database/project/session state or
broaden scope beyond the authorized no-DML boundary:

```text
docker compose exec app php tests/test_schedule_update_draft_import.php
docker compose exec app php tests/test_preconstruction_import_global_ids.php
docker compose exec app composer test
npx playwright test tests/browser/full-app-flow.spec.mjs
tests that enter /dev/entrar and perform a real project/week/import/save/delete flow
manual calls to any POST /api/programa-general-actualizar/* against the mounted application
```

If an implementation worker believes a live mutation is necessary, stop and request new authority;
do not substitute rollback DML, because rollback is still DML.

## Self-Review Checklist for the Executor

Before claiming S06 complete, prove all of the following from fresh outputs:

- correct worktree/branch and no unrelated diff included;
- all seven new endpoints have PHP contract coverage and exact Zod schemas;
- no direct fetch outside common client;
- no client scope/auth fields;
- context resolver never reads/writes selected session week as base authority;
- effective permissions, including denied/read-only cases;
- parser/input cases and temp-store cleanup;
- import token revalidation/single-use;
- save mapping/heredity and mapped-row locks;
- matcher configured thresholds and idempotence;
- batch atomicity and server-derived audit identity;
- draft-only delete with no soft-reset branch;
- dark/light at four viewports, keyboard/Axe/zoom/reduced motion/no overflow;
- visual approval predates any golden update;
- zero-caller evidence predates every legacy deletion;
- shared template/list/editor/vendor consumers remain;
- focused and post-cut gates read as RC 0 separately;
- no RLS/schema/grant/user/credential/admin/data change;
- no DDL/DML executed;
- closure ledger and verified SHA recorded only after actual implementation/cut.

## Cierre

**Estado documental 2026-08-30:** plan escrito y autorrevisado; implementación no iniciada. Esta
sección sólo se actualizará con evidencia real de una sesión de ejecución autorizada. Las casillas
anteriores no se marcarán retroactivamente ni se usarán como prueba de avance.
