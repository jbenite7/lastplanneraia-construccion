---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [bi, pdc, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s22-bi-pdc-react.md
resumen: "migrate /bi/pdc into the main React SPA as one read-only Obra sheet that distinguishes confirmed overdue steps from past dates without recorded progress…"
---

# S22 BI Plan de Compras React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in order
> and stop at each vertical checkpoint. Checkbox syntax is an execution prompt only; progress and
> closure live in `Cierre` and git history, never in checkbox counts.

**Goal:** migrate `/bi/pdc` into the main React SPA as one read-only Obra sheet that distinguishes
confirmed overdue steps from past dates without recorded progress, exposes raw coverage
numerators/denominators, real-vs-planned counterweights, missing responsibility/dates, a paginated
action list and safe links to Plan de Compras v2, with equivalent desktop, tablet, mobile, dark and
light behavior, without changing RLS, permissions, schema or data.

**Architecture:** T01 owns session, active project, shell, sidebar, routes, theme and the only HTTP
client. The T03 slice delivered through S17 owns per-sheet admission, authorized project scope,
canonical query, shared states, frame, drawer and lineage. S22 adds one
`BiPdcReadService` around a single Bogotá cutoff and one scoped PDC v2 reader. Pure projectors own
schedule state, evidence of progress, coverage, gaps, headline, actions, breakdowns and distribution
signals. The two existing GET routes remain the only report endpoints. Canonical and legacy
presenters share the same read model until the caller census permits retirement. React renders
server decisions and never mutates.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/SVG/CSS and existing AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s22-bi-pdc-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or
  another worktree.
- Inspect status and the relevant diff before every task. Preserve existing work; never clean,
  revert or reformat adjacent files.
- This planning session is documentation-only. The future commit commands below do not authorize
  implementation, install, build, commit, push, PR, publication or deploy now.
- Start implementation only after T01, the T03 primitives delivered through S17 and the S12 Plan de
  Compras v2 route/capability contracts exist and pass focused tests.
- `/admin/` is excluded. Do not modify its code, routes, UI, tests or assets.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, triggers, grants, users, credentials, memberships, role aliases, capability catalogs,
  flags or data.
- No DDL/DML. Rollback inside a transaction is still DML. New PHP tests use pure services, fakes,
  call logs, source/reflection checks and static synthetic fixtures.
- Inspect every existing test before running it. Never run
  `tests/test_pdc_v2_torre_control.php` as S22 evidence: it writes and deletes database fixtures.
- Preserve PDC v2 as the only domain. Never read or reintroduce PDC v1, `bi_pdc_general`,
  `/pdc`, `/api/pdc/*` or deleted tables.
- Preserve `BiPreviewAccessPolicy`, `internal.bi.preview`, `bi.control_tower.visible` and
  `RbacCatalog`. Extend only the already-approved T03 `BiSheetAccessPolicy` manifest: S22 admits A
  without the flag and D/R with the flag, and returns 404 to OT and all other roles.
- Every selected project requires membership, visibility and `lps.indicadores.ver`. Invalid project
  scope returns 403 after sheet admission; hidden sheet returns 404 before data.
- OT acts through S12. No BI bypass or role-derived frontend permission is allowed.
- Use one immutable `PdcCutoff` for a request. It is server-owned, today in
  `America/Bogota`; client date input is rejected and legacy week input is declared ignored.
- Grain is project + package + subpackage + step. A lot never borrows progress or responsibility
  from another lot.
- Coverage aggregates raw numerators and denominators. Zero denominator is null/insufficient, never
  zero percent. List filters do not alter scope-level coverage.
- `overdue` requires a past planned date, no real date on the step and at least one closed actual
  step in the same destination. A past date without any destination progress is
  `unrecorded_progress`.
- Keep schedule bucket separate from decision state. Preserve exact 0/6/7/13/14/20/21/41/42 day
  boundaries.
- Label all catalog-derived dates/durations as planned. Mark company-median duration provisional.
- Show closed-with-actual / planned counterweight and destinations without responsible.
- Provider, contact, per-row price and per-row value never cross the BI endpoints.
- Main includes first urgent page. Existing detail GET handles filtering/search/pagination. Add no
  endpoint, alias, mutation, export or download.
- Server authors states, headline, sufficiency, ordering, capabilities, hrefs, limitations and pure
  signal eligibility. React does not reconstruct domain decisions.
- Signals are projections only. S22 sends and persists nothing. T03 owns transition,
  recipients, daily grouping, deduplication, channel and historical accuracy.
- S12 remains sole owner of writes and operational deep links. Use only canonical high-level hrefs
  until S12 defines a stable row-level query.
- Only `frontend/src/lib/api/cliente.ts` may call fetch. Parse success and error with Zod; derive
  TypeScript types with `z.infer`.
- Abort replaced requests and ignore stale completions. Cache identity includes user, scope, cutoff,
  filters, search, pagination and focus.
- Below 768 px mount cards only. At or above 768 px mount semantic table only.
- Use native SVG plus visible data table; never canvas, hover-only disclosure or color-only meaning.
- Reuse T03 `MarcoBi`, `FiltrosBi`, `EstadoBi` and `LinajeDrawer`.
- Use `public/css/tokens.css`. No color literals, `!important`, inline colors, local token family or
  new UI/table/chart/state library.
- Dark is default/fallback and light is equivalent. Validate 390x844, 480x900, 768x1024, 1180x820
  and 1440x900, plus 200 percent zoom, reduced motion, 44 px targets and axe serious/critical zero.
- Browser evidence is fully intercepted and fails on unexpected requests, any non-GET request and
  console errors.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Keep legacy views, `bi-spa.js`, shared BI CSS and Chart.js until the T03/S17–S24 caller census
  reaches zero. No deletion is required to close S22.
- Rollback changes route/code only; it never restores or modifies data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read closure records for T01, S12, S17 and the T03 increment.
2. Verify these shared contracts exist:
   `BiSheetAccessPolicy`, `BiProjectScope`, `BiQueryParser`, `MarcoBi`, `FiltrosBi`,
   `EstadoBi`, `LinajeDrawer`, the responsive drawer and `cliente.ts`.
3. Verify S12 owns and serves:
   `/plan-compras/ensamble/plan` and `/plan-compras/seguimiento/avance`.
4. Verify PDC v1 remains absent.
5. Inspect each proposed existing test for writes before running it.
6. Verify branch and runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree and branch; services `app`, `db`, `adminer`; app mount points to this
   worktree.
7. Record starting SHA and all pre-existing changed paths. Do not stage them.
8. Characterize existing page/main/detail callers without writing data.
9. If any dependency is absent, execute its owning plan first. Do not rebuild it inside S22.
10. Do not open the login form, change `.env` or request credentials. Use pure/intercepted evidence
    when the development door is closed.

## File Structure

### Create — backend

- `src/Services/Bi/Pdc/PdcCutoff.php`.
- `src/Services/Bi/Pdc/PdcSchedulePolicy.php`.
- `src/Services/Bi/Pdc/PdcPlanningReader.php`.
- `src/Services/Bi/Pdc/DatabasePdcPlanningReader.php`.
- `src/Services/Bi/Pdc/PdcCoverageProjector.php`.
- `src/Services/Bi/Pdc/PdcExecutionProjector.php`.
- `src/Services/Bi/Pdc/PdcPlanningGapProjector.php`.
- `src/Services/Bi/Pdc/PdcBreakdownProjector.php`.
- `src/Services/Bi/Pdc/PdcHeadline.php`.
- `src/Services/Bi/Pdc/PdcActionProjector.php`.
- `src/Services/Bi/Pdc/PdcDistributionSignalProjector.php`.
- `src/Services/Bi/Pdc/PdcDetailQuery.php`.
- `src/Services/Bi/Pdc/PdcDetailQueryParser.php`.
- `src/Services/Bi/Pdc/BiPdcReadService.php`.
- `src/Services/Bi/Pdc/BiPdcPresenter.php`.

### Modify — backend integration

- `src/Security/BiSheetAccessPolicy.php` — add the already-approved A/D/R PDC sheet manifest entry,
  with the flag applying only to D/R.
- `src/Services/Pdc/SeguimientoService.php` — delegate temporal classification/shared reads; preserve
  S12 contract.
- `src/Services/Bi/MetricDictionaryService.php` — correct PDC applicability and register descriptive
  duration metadata.
- `src/Services/Bi/LineageService.php` — expose corrected lineage through the canonical seam.
- `src/Services/ControlTowerService.php` — compatibility presenter delegation only.
- `src/Controllers/Api/BiControlTowerApiController.php` — thin main/detail delegation.
- `src/Controllers/Bi/BiViewController.php` — sheet policy and SPA handoff at cut.
- `src/View/Components/BiAccessComponent.php` — consume shared sheet manifest; no role condition.
- `public/index.php` only if T01 route registration needs an existing handler adapter; add no route.

### Create — PHP tests and fixtures

- `tests/Support/Bi/FakePdcPlanningReader.php`.
- `tests/fixtures/bi-pdc-react/schedule-cases.php`.
- `tests/fixtures/bi-pdc-react/coverage-cases.php`.
- `tests/fixtures/bi-pdc-react/progress-cases.php`.
- `tests/fixtures/bi-pdc-react/detail-cases.php`.
- `tests/test_bi_pdc_access_scope.php`.
- `tests/test_bi_pdc_schedule_policy.php`.
- `tests/test_bi_pdc_coverage.php`.
- `tests/test_bi_pdc_execution.php`.
- `tests/test_bi_pdc_planning_gaps.php`.
- `tests/test_bi_pdc_detail_query.php`.
- `tests/test_bi_pdc_read_contract.php`.
- `tests/test_bi_pdc_lineage_signal.php`.
- `tests/test_bi_pdc_compatibility.php`.
- `tests/test_bi_pdc_source_invariants.php`.
- `tests/test_bi_pdc_routes.php`.

### Create — frontend

- `frontend/src/lib/api/esquemas/biPdc.ts` and test.
- `frontend/src/lib/api/biPdc.ts` and test.
- `frontend/src/modulos/bi/PdcPagina.tsx` and test.
- `frontend/src/modulos/bi/pdc/consultaPdc.ts` and test.
- `frontend/src/modulos/bi/pdc/estadoPdc.ts` and test.
- `frontend/src/modulos/bi/pdc/useBiPdc.ts` and test.
- `frontend/src/modulos/bi/pdc/TitularPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/ResumenCobertura.tsx` and test.
- `frontend/src/modulos/bi/pdc/ContrapesoEjecucion.tsx` and test.
- `frontend/src/modulos/bi/pdc/AlarmasPlanificacion.tsx` and test.
- `frontend/src/modulos/bi/pdc/FiltrosPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/ListaPasosPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/TablaPasosPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/TarjetasPasosPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/EscalaVencimiento.tsx` and test.
- `frontend/src/modulos/bi/pdc/DesglosesPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/DetallePasoPdc.tsx` and test.
- `frontend/src/modulos/bi/pdc/pdc.css`.
- `tests/browser/fixtures/bi-pdc-react.mjs`.
- `tests/browser/bi-pdc-react.spec.mjs`.
- `tests/browser/bi-pdc-react.a11y.mjs`.
- `tests/design-system/bi-pdc-react-tokens.test.mjs`.

### Modify — frontend integration

- `frontend/src/shell/rutas.tsx` — register the existing `/bi/pdc` path.
- `frontend/src/shell/NavegacionLateral.tsx` only through the shared server/manifest seam established
  by T01/T03.
- `frontend/src/main.tsx` only to import layered module CSS if the registry does not.
- `public/css/tokens.css` only if a semantic token is truly absent, with a failing contract test
  first.

### Explicitly preserve

- S12 source and all write routes.
- `pdc-app/` until S12 cuts it according to its own plan.
- `views/bi/control-tower.php` and other BI sections.
- `public/js/modules/bi-spa.js` and other callers.
- `public/css/bi-control-tower.css` and shared Chart.js consumers.
- `docs/security/rls-runtime-boundary.md`.
- every schema, migration, SQL view, data asset and `/admin/` path.

## Task 1: Lock sheet access, project scope and cutoff authority

**Vertical outcome:** A/D/R can request one authorized today snapshot; A remains admitted without
the flag, OT and hidden roles receive 404, project leakage is impossible and no role/RLS catalog
changes occur.

**Files:** create `tests/test_bi_pdc_access_scope.php`,
`src/Services/Bi/Pdc/PdcCutoff.php` and the fake scope fixtures; modify only the S17
`BiSheetAccessPolicy` extension point.

### Step 1: Write failing pure tests

Cover:

- A allowed regardless of the global flag;
- D and R allowed when the global flag is on;
- OT and all other roles hidden;
- flag off hides D/R but not A;
- page, main and detail share policy;
- authorized default and explicit multi scope;
- foreign project rejected before reader invocation;
- one Bogotá date object reused;
- client date rejected;
- legacy week declared ignored;
- no data in 404 response;
- no RbacCatalog or RLS modification.

Use in-memory sessions, fake project authorizer and reader call logs. Do not connect to MySQL.

### Step 2: Run and prove RED

    docker compose exec app php tests/test_bi_pdc_access_scope.php

Expected: FAIL because S22 is absent from `BiSheetAccessPolicy` and `PdcCutoff` does not exist.

### Step 3: Implement the minimum policy and cutoff

Add one PDC manifest row for A/D/R. Compose existing preview/project capability checks and apply the
existing global flag only to D/R; do not change the flag itself. Make `PdcCutoff` immutable and
injectable.

### Step 4: Run GREEN and invariant search

    docker compose exec app php tests/test_bi_pdc_access_scope.php
    rg -n "internal\\.bi\\.preview|RbacCatalog|CREATE |ALTER |DROP |INSERT |UPDATE |DELETE " src/Services/Bi/Pdc src/Security/BiSheetAccessPolicy.php

Expected: test PASS; search shows references only where expected and no SQL mutation.

### Step 5: Review and future atomic commit

Inspect only S22 policy/cutoff/test paths. In an authorized execution session:

    git add src/Security/BiSheetAccessPolicy.php src/Services/Bi/Pdc/PdcCutoff.php tests/test_bi_pdc_access_scope.php
    git commit -m "test(bi): lock PDC sheet access and cutoff"

**Checkpoint:** no reader or UI exists yet; access semantics are independently verified.

## Task 2: Define schedule, progress evidence and stable ordering

**Vertical outcome:** the system can distinguish 6 confirmed overdue steps from 53 past dates
without recorded progress in the measured shape, using synthetic data and exact boundary rules.

**Files:** create `PdcSchedulePolicy.php`, `PdcExecutionProjector.php`, schedule/progress fixtures and
their tests; modify `SeguimientoService.php` only to delegate the existing temporal classification.

### Step 1: Write failing pure tests

Cover:

- buckets at -past, 0, 6, 7, 13, 14, 20, 21, 41 and 42 days;
- null and invalid dates;
- positive/zero/negative/null `daysDelta`;
- destination evidence by project/package/subpackage;
- no progress borrowed between lots;
- `overdue` versus `unrecorded_progress`;
- closed step excluded from pending list but included in counterweight;
- stable key with step ID and order fallback;
- urgent priority and deterministic tie-breaks;
- compatibility output from `clasificarVencimiento()`.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_pdc_schedule_policy.php
    docker compose exec app php tests/test_bi_pdc_execution.php

Expected: FAIL because policies do not exist.

### Step 3: Implement pure policies

Keep schedule bucket and decision state as separate values. Accept an injected ISO cutoff. Never
infer progress from schedule dates. Make `SeguimientoService::clasificarVencimiento()` a compatibility
adapter over the single policy without changing S12 keys.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_pdc_schedule_policy.php
    docker compose exec app php tests/test_bi_pdc_execution.php

Expected: both PASS, including 59 synthetic past dates split as 6 overdue and 53 unrecorded.

### Step 5: Review and future commit

    git add src/Services/Bi/Pdc/PdcSchedulePolicy.php src/Services/Bi/Pdc/PdcExecutionProjector.php src/Services/Pdc/SeguimientoService.php tests/fixtures/bi-pdc-react/schedule-cases.php tests/fixtures/bi-pdc-react/progress-cases.php tests/test_bi_pdc_schedule_policy.php tests/test_bi_pdc_execution.php
    git commit -m "feat(bi): classify PDC schedule evidence"

**Checkpoint:** the highest-risk semantic correction is pure, measured and reversible.

## Task 3: Build scoped coverage, responsibility, duration and planning gaps

**Vertical outcome:** a fake reader can produce correct count/value coverage, closure counterweight,
unassigned/provisional counts and actionable planning gaps for one or many projects.

**Files:** create reader port/fake, `PdcCoverageProjector.php`,
`PdcPlanningGapProjector.php` and focused tests.

### Step 1: Write failing tests

Cover:

- raw count/value numerators and denominators;
- one-project percentages;
- multi-project raw aggregation;
- proof that average-of-percentages is rejected;
- zero denominator;
- invalid numerator/denominator;
- per-project breakdown;
- list filters do not alter scope coverage;
- planned/closed/pending counts;
- destinations without responsibility counted once;
- company median marked provisional;
- missing duration remains unavailable;
- waiting dates, no front, pending recalculation, no-date step and stale schedule remain distinct;
- no provider/contact/row value in read DTOs;
- project and lot isolation.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_pdc_coverage.php
    docker compose exec app php tests/test_bi_pdc_planning_gaps.php
    docker compose exec app php tests/test_bi_pdc_execution.php

Expected: FAIL because projectors/reader port are missing.

### Step 3: Implement pure projectors and read port

Define exact typed array shapes in PHPDoc/value constructors. Keep commercial aggregate value only
inside coverage. Reader results use IDs internally; presenter privacy is tested later.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_pdc_coverage.php
    docker compose exec app php tests/test_bi_pdc_planning_gaps.php
    docker compose exec app php tests/test_bi_pdc_execution.php

Expected: PASS.

### Step 5: Review and future commit

    git add src/Services/Bi/Pdc/PdcPlanningReader.php src/Services/Bi/Pdc/PdcCoverageProjector.php src/Services/Bi/Pdc/PdcPlanningGapProjector.php tests/Support/Bi/FakePdcPlanningReader.php tests/fixtures/bi-pdc-react/coverage-cases.php tests/test_bi_pdc_coverage.php tests/test_bi_pdc_planning_gaps.php tests/test_bi_pdc_execution.php
    git commit -m "feat(bi): project PDC coverage and planning gaps"

**Checkpoint:** scope-level decision numbers work without database or UI.

## Task 4: Assemble the canonical read model, headline, contract, signal and lineage

**Vertical outcome:** `BiPdcReadService` returns one coherent canonical main payload from fakes,
including first urgent page, factual copy, authorized hrefs, pure signals and complete lineage.

**Files:** create headline/action/signal/breakdown/service/presenter classes and contract tests;
modify metric dictionary/lineage through existing seams.

### Step 1: Write failing contract tests

Assert:

- one reader call set and one cutoff;
- `ok/data/meta` shape;
- first urgent page and pagination;
- canonical coverage/execution/gaps/breakdowns;
- finite factual headline;
- no PDC v1 fields or claims;
- hrefs present/null by server capability;
- no unsupported row deep link;
- pure signal with stable key and `windowEnteredOn`;
- no signal side effect on repeated reads;
- corrected `pdc_at_risk` metadata;
- `supportsDateRange=false`;
- descriptive `compras.duracion_real_paso` with minimum 20;
- provider/commercial details absent;
- typed error envelopes.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_pdc_read_contract.php
    docker compose exec app php tests/test_bi_pdc_lineage_signal.php

Expected: FAIL because service/presenter do not exist.

### Step 3: Implement one orchestration path

Compose the pure projectors. Do not call legacy `briefPDC()` or `actionsFromPDC()`. Keep all strings
plain. Signal projection returns values only and has no transport dependency.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_pdc_read_contract.php
    docker compose exec app php tests/test_bi_pdc_lineage_signal.php
    docker compose exec app php tests/test_bi_metric_contracts.php

Run `test_bi_metric_contracts.php` only after source inspection confirms it is read-only; otherwise
record the exclusion and cover the affected dictionary seam with the pure S22 test.

### Step 5: Review and future commit

    git add src/Services/Bi/Pdc/PdcBreakdownProjector.php src/Services/Bi/Pdc/PdcHeadline.php src/Services/Bi/Pdc/PdcActionProjector.php src/Services/Bi/Pdc/PdcDistributionSignalProjector.php src/Services/Bi/Pdc/BiPdcReadService.php src/Services/Bi/Pdc/BiPdcPresenter.php src/Services/Bi/MetricDictionaryService.php src/Services/Bi/LineageService.php tests/test_bi_pdc_read_contract.php tests/test_bi_pdc_lineage_signal.php
    git commit -m "feat(bi): assemble canonical PDC brief"

**Checkpoint:** the complete business payload is demonstrable with no database write and no React.

## Task 5: Stabilize detail search, filters, pagination and shared row schema

**Vertical outcome:** the existing detail GET contract can answer bounded, authorized exploration
without loading every pending step.

**Files:** create `PdcDetailQuery.php`, `PdcDetailQueryParser.php` and tests; extend fake reader and
`BiPdcReadService` detail path.

### Step 1: Write failing tests

Cover:

- q length 0–100;
- closed status catalog;
- exact step key;
- authorized responsible or `unassigned`;
- project inside authorized scope;
- limit 1–100 and offset >=0;
- boolean `include_summary`;
- invalid field errors;
- search normalization/display preservation;
- filter catalog from full scope;
- filtered summary opt-in;
- total/returned/next/hasMore;
- stable order;
- main first page equals unfiltered detail;
- cutoff mismatch metadata;
- filters never grant project access.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_pdc_detail_query.php
    docker compose exec app php tests/test_bi_pdc_read_contract.php

Expected: FAIL on missing parser/detail.

### Step 3: Implement bounded detail

Apply filters through the read port; do not filter PHP after loading an unbounded real dataset.
Reuse the exact row projector and order from main. Keep summary optional.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_pdc_detail_query.php
    docker compose exec app php tests/test_bi_pdc_read_contract.php

Expected: PASS.

### Step 5: Review and future commit

    git add src/Services/Bi/Pdc/PdcDetailQuery.php src/Services/Bi/Pdc/PdcDetailQueryParser.php src/Services/Bi/Pdc/BiPdcReadService.php tests/Support/Bi/FakePdcPlanningReader.php tests/fixtures/bi-pdc-react/detail-cases.php tests/test_bi_pdc_detail_query.php tests/test_bi_pdc_read_contract.php
    git commit -m "feat(bi): paginate PDC decision detail"

**Checkpoint:** both endpoint use cases are complete against fakes.

## Task 6: Add the scoped database adapter and wire existing routes with compatibility

**Vertical outcome:** both existing GETs delegate to the canonical service, use one project-scoped
adapter and preserve legacy consumers without a new endpoint.

**Files:** create `DatabasePdcPlanningReader.php`; modify controllers, compatibility service,
view handoff and route/component integration; add source/route/compatibility tests.

### Step 1: Write failing no-write tests

Use a query spy/fake database to assert:

- every operational read receives authorized project IDs;
- prepared placeholders are used;
- no dynamic table/prefix input;
- one base step read serves aggregates/detail projection;
- raw coverage magnitudes are read;
- destination progress and provisional duration are present;
- provider/per-row value are not selected for HTTP DTO;
- main/detail controllers call the same service/policy;
- legacy presenter retains required current keys;
- canonical presenter contains none of the removed v1 fields;
- route registry has exactly the two existing GETs and page route;
- no mutating SQL token exists in new adapter.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_pdc_source_invariants.php
    docker compose exec app php tests/test_bi_pdc_compatibility.php
    docker compose exec app php tests/test_bi_pdc_routes.php

Expected: FAIL because adapter/delegation are absent.

### Step 3: Implement the adapter and thin integrations

Reuse `queryForProjects` and prepared APIs. The API controller resolves sheet policy, scope and query,
then delegates. `ControlTowerService` calls the compatibility presenter only. The page controller
hands off to the SPA at the route cut. Add no route and no write.

### Step 4: Run GREEN and route census

    docker compose exec app php tests/test_bi_pdc_source_invariants.php
    docker compose exec app php tests/test_bi_pdc_compatibility.php
    docker compose exec app php tests/test_bi_pdc_routes.php
    rg -n "/api/bi/report/pdc|/bi/pdc|pdcDetail|renderPDC" public src views public/js tests frontend

Expected: tests PASS; census is saved for Task 11; only known routes/callers appear.

### Step 5: Review and future commit

    git add src/Services/Bi/Pdc/DatabasePdcPlanningReader.php src/Controllers/Api/BiControlTowerApiController.php src/Controllers/Bi/BiViewController.php src/Services/ControlTowerService.php src/View/Components/BiAccessComponent.php public/index.php tests/test_bi_pdc_source_invariants.php tests/test_bi_pdc_compatibility.php tests/test_bi_pdc_routes.php
    git commit -m "feat(bi): wire scoped PDC report routes"

Do not stage `public/index.php` if no change was necessary.

**Checkpoint:** backend is route-complete, compatibility-safe and write-free.

## Task 7: Define Zod contracts, API gateway, query codec and remote-state controller

**Vertical outcome:** React can load, validate, refresh, search, filter and paginate S22 through the
only HTTP client while rejecting stale or invalid results.

**Files:** create API schemas/gateway, query/state/hook files and tests; extend `cliente.ts` only if
the already-approved T01 API lacks typed errors or `AbortSignal` support.

### Step 1: Write failing frontend tests

Cover:

- canonical success/error schemas;
- discriminated states and nulls;
- privacy fields rejected/stripped according to strict schema policy;
- stable query serialization;
- 250 ms search debounce;
- filter change resets offset;
- allowed filter/focus round trip;
- main first page reuse;
- detail loaded only on interaction;
- AbortSignal propagation;
- stale completion ignored;
- cache identity includes user/scope/cutoff/query/focus;
- cutoff mismatch triggers one main refresh;
- main survives detail error;
- refresh keeps coherent stale data;
- offline/partial/invalid-contract behavior;
- source search finds fetch only in `cliente.ts`.

### Step 2: Run RED

    npm --prefix frontend test -- biPdc consultaPdc estadoPdc useBiPdc

Expected: FAIL because files do not exist.

### Step 3: Implement minimum client layer

Derive all TypeScript types from Zod. Reuse T03 query/cache/error primitives. Add no state/query
library. Pass `signal` through `pedir` only if T01 has not already done so.

### Step 4: Run GREEN and typecheck

    npm --prefix frontend test -- biPdc consultaPdc estadoPdc useBiPdc
    npm --prefix frontend run typecheck
    rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts'

Expected: tests/typecheck PASS; source search returns no fetch outside `cliente.ts`.

### Step 5: Review and future commit

    git add frontend/src/lib/api/esquemas/biPdc.ts frontend/src/lib/api/esquemas/biPdc.test.ts frontend/src/lib/api/biPdc.ts frontend/src/lib/api/biPdc.test.ts frontend/src/modulos/bi/pdc/consultaPdc.ts frontend/src/modulos/bi/pdc/consultaPdc.test.ts frontend/src/modulos/bi/pdc/estadoPdc.ts frontend/src/modulos/bi/pdc/estadoPdc.test.ts frontend/src/modulos/bi/pdc/useBiPdc.ts frontend/src/modulos/bi/pdc/useBiPdc.test.ts frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts
    git commit -m "feat(frontend): add PDC report client state"

Do not stage `cliente.ts` or its test when unchanged.

**Checkpoint:** the client boundary is valid before any production page renders.

## Task 8: Ship the first visible decision slice

**Vertical outcome:** intercepted A/D/R data renders headline/cutoff, planning alarms, urgent steps,
coverage and real/planned counterweight in one usable page.

**Files:** create `PdcPagina`, headline, coverage, counterweight, alarms and list components/tests.

### Step 1: Write failing component tests

Assert:

- order: headline/cutoff, alarms, urgent list, coverage, counterweight;
- week selector hidden/disabled with explicit reason;
- 6 overdue and 53 unrecorded remain distinct;
- no-date/unassigned alarms are first;
- raw numerator/denominator visible;
- zero denominator says insufficient;
- waiting-date empty state names S12 action;
- no-results state keeps global summary;
- provisional duration label;
- row contains project/destination/step/state/date/days/responsible/action;
- href can be null;
- no provider/value row;
- loading/refreshing/partial/empty/insufficient/offline/invalid/error text;
- no `dangerouslySetInnerHTML`.

### Step 2: Run RED

    npm --prefix frontend test -- PdcPagina TitularPdc ResumenCobertura ContrapesoEjecucion AlarmasPlanificacion ListaPasosPdc

Expected: FAIL because components do not exist.

### Step 3: Implement the smallest composed page

Use `MarcoBi`, `FiltrosBi` and `EstadoBi`. Render server-authored text/states. Keep controls read-only.
Do not add charts or drawer yet.

### Step 4: Run GREEN and typecheck

    npm --prefix frontend test -- PdcPagina TitularPdc ResumenCobertura ContrapesoEjecucion AlarmasPlanificacion ListaPasosPdc
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 5: Review and future commit

    git add frontend/src/modulos/bi/PdcPagina.tsx frontend/src/modulos/bi/PdcPagina.test.tsx frontend/src/modulos/bi/pdc/TitularPdc.tsx frontend/src/modulos/bi/pdc/TitularPdc.test.tsx frontend/src/modulos/bi/pdc/ResumenCobertura.tsx frontend/src/modulos/bi/pdc/ResumenCobertura.test.tsx frontend/src/modulos/bi/pdc/ContrapesoEjecucion.tsx frontend/src/modulos/bi/pdc/ContrapesoEjecucion.test.tsx frontend/src/modulos/bi/pdc/AlarmasPlanificacion.tsx frontend/src/modulos/bi/pdc/AlarmasPlanificacion.test.tsx frontend/src/modulos/bi/pdc/ListaPasosPdc.tsx frontend/src/modulos/bi/pdc/ListaPasosPdc.test.tsx
    git commit -m "feat(frontend): render PDC decision core"

**Checkpoint:** S22 has an independently demonstrable useful core before richer exploration.

## Task 9: Add filters, table/cards, accessible breakdowns and contextual drawer

**Vertical outcome:** the user can explore the same decision model by status, step, responsibility
or project and open evidence/action detail without mutation.

**Files:** create filters, table/cards, scale, breakdown and detail components/tests; compose T03
drawer.

### Step 1: Write failing tests

Cover:

- search/status/step/responsible/project controls;
- filter catalogs remain complete;
- pagination;
- exact visible counts;
- semantic table at desktop mode;
- cards at mobile mode;
- never mount both;
- SVG title/description and visible data table;
- horizon includes ahead;
- breakdown units declared;
- unassigned first;
- overdue and unrecorded split;
- open drawer from KPI, scale, breakdown and row;
- one drawer instance with list/detail/back;
- evidence/cutoff/source/provisional/limitation;
- focus query;
- initial focus/trap/Escape/return;
- authorized/null href;
- no mutation control.

### Step 2: Run RED

    npm --prefix frontend test -- FiltrosPdc TablaPasosPdc TarjetasPasosPdc EscalaVencimiento DesglosesPdc DetallePasoPdc PdcPagina

Expected: FAIL on missing components.

### Step 3: Implement with shared primitives

Drive responsive mounting with the shared T03 viewport primitive, not duplicated CSS visibility.
Use native SVG and a visible table. Open the shared `LinajeDrawer`/context drawer instead of creating
a second modal system.

### Step 4: Run GREEN

    npm --prefix frontend test -- FiltrosPdc TablaPasosPdc TarjetasPasosPdc EscalaVencimiento DesglosesPdc DetallePasoPdc PdcPagina
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 5: Review and future commit

    git add frontend/src/modulos/bi/pdc/FiltrosPdc.tsx frontend/src/modulos/bi/pdc/FiltrosPdc.test.tsx frontend/src/modulos/bi/pdc/TablaPasosPdc.tsx frontend/src/modulos/bi/pdc/TablaPasosPdc.test.tsx frontend/src/modulos/bi/pdc/TarjetasPasosPdc.tsx frontend/src/modulos/bi/pdc/TarjetasPasosPdc.test.tsx frontend/src/modulos/bi/pdc/EscalaVencimiento.tsx frontend/src/modulos/bi/pdc/EscalaVencimiento.test.tsx frontend/src/modulos/bi/pdc/DesglosesPdc.tsx frontend/src/modulos/bi/pdc/DesglosesPdc.test.tsx frontend/src/modulos/bi/pdc/DetallePasoPdc.tsx frontend/src/modulos/bi/pdc/DetallePasoPdc.test.tsx frontend/src/modulos/bi/PdcPagina.tsx frontend/src/modulos/bi/PdcPagina.test.tsx
    git commit -m "feat(frontend): explore PDC report context"

**Checkpoint:** every aggregate is drillable and every detail remains read-only.

## Task 10: Integrate route/sidebar, tokens, responsive themes and accessibility

**Vertical outcome:** S22 is reachable in the Obra sidebar for A/D/R—A without the flag and D/R
with it—and is equivalent across the approved themes, viewports, zoom, keyboard and assistive
technology.

**Files:** modify route/sidebar integration; create CSS/token contract and accessibility tests.

### Step 1: Write failing integration/design tests

Cover:

- exact `/bi/pdc` route;
- PDC nav visible/active for A when Obra is selected and for authorized D/R;
- PDC nav absent from A's Gerencia canvas and from OT/other roles;
- all component states in dark/default and light;
- token-only CSS;
- 44 px targets;
- exclusive mobile/table mounting;
- five viewports and 200 percent zoom without page overflow;
- focus visible;
- reduced motion;
- full keyboard path;
- heading/table/list/live-region semantics;
- full accessible names;
- axe serious/critical zero.

### Step 2: Run RED

    npm --prefix frontend test -- rutas NavegacionLateral PdcPagina
    node --test tests/design-system/bi-pdc-react-tokens.test.mjs

Expected: FAIL because route/CSS contract are not integrated.

### Step 3: Implement route and token CSS

Consume the server/shared manifest; do not add raw role checks to `NavegacionLateral`. Add tokens
only after a failing contract proves a gap. Keep CSS layered and module-scoped.

### Step 4: Run GREEN and build

    npm --prefix frontend test -- rutas NavegacionLateral PdcPagina
    node --test tests/design-system/bi-pdc-react-tokens.test.mjs
    npm --prefix frontend run typecheck
    npm --prefix frontend run build

Expected: all PASS.

### Step 5: Review and future commit

    git add frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/NavegacionLateral.test.tsx frontend/src/modulos/bi/PdcPagina.tsx frontend/src/modulos/bi/PdcPagina.test.tsx frontend/src/modulos/bi/pdc/pdc.css frontend/src/main.tsx public/css/tokens.css tests/design-system/bi-pdc-react-tokens.test.mjs
    git commit -m "feat(frontend): integrate accessible PDC sheet"

Do not stage unchanged optional files.

**Checkpoint:** the full frontend surface is ready for intercepted browser evidence.

## Task 11: Cut the page behind the existing route and verify behavior in browser

**Vertical outcome:** the served application uses React for `/bi/pdc` with no unexpected network
traffic, mutation, console error or cross-role leak, while compatibility remains available.

**Files:** create intercepted fixtures/specs; finalize controller/view route cut and caller census.

### Step 1: Create deterministic fixtures and failing Playwright scenarios

Fixture set includes:

- A/D/R ready data with 6 overdue and 53 unrecorded;
- A ready with the flag off; OT/other roles 404;
- foreign project 403;
- empty awaiting-dates;
- insufficient coverage;
- partial/offline/invalid;
- main/detail cutoff mismatch;
- paginated/filter results;
- dark/light.

Scenarios cover 390x844, 480x900, 768x1024, 1180x820, 1440x900 and 200 percent zoom. Fail every
unexpected URL, any request whose method is not GET, and every console/page error.

### Step 2: Run RED against pre-cut route

    npx playwright test tests/browser/bi-pdc-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-pdc-react.a11y.mjs --workers=1

Expected: FAIL before the route handoff or on missing fixture integration.

### Step 3: Complete the route cut

Switch only `/bi/pdc` to the T01 SPA handler after page/API contracts are green. Keep both API route
names and compatibility presenter. Do not delete shared legacy assets.

### Step 4: Run GREEN and inspect evidence

    npx playwright test tests/browser/bi-pdc-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-pdc-react.a11y.mjs --workers=1

Expected: PASS, clean console, zero mutation and axe serious/critical zero.

### Step 5: Census callers and write rollback note

    rg -n "/api/bi/report/pdc|/bi/pdc|pdcDetail|renderPDC|view-pdc" public src views public/js tests frontend
    git diff -- src/Controllers/Bi/BiViewController.php src/Controllers/Api/BiControlTowerApiController.php frontend/src/shell/rutas.tsx

Record:

- current canonical callers;
- compatibility callers remaining;
- legacy deletion eligibility = false until T03/S17–S24;
- rollback = route adapter to legacy, no data operation.

### Step 6: Review and future commit

    git add tests/browser/fixtures/bi-pdc-react.mjs tests/browser/bi-pdc-react.spec.mjs tests/browser/bi-pdc-react.a11y.mjs src/Controllers/Bi/BiViewController.php src/Controllers/Api/BiControlTowerApiController.php frontend/src/shell/rutas.tsx
    git commit -m "test(bi): verify PDC React route"

**Checkpoint:** S22 is vertically complete and independently reversible; shared retirement remains
closed.

## Task 12: Run focused-to-broad verification and prepare closure evidence

**Vertical outcome:** the exact implementation SHA is supported by fresh, read-only evidence and a
cleanly scoped diff. This task does not authorize publication or deploy.

### Step 1: Inspect all commands for writes

Before running any PHP test not created by S22, open it and confirm no insert/update/delete,
fixture setup, truncate or migration. Skip and record unsafe suites.

### Step 2: Run focused backend tests one command at a time

    docker compose exec app php tests/test_bi_pdc_access_scope.php
    docker compose exec app php tests/test_bi_pdc_schedule_policy.php
    docker compose exec app php tests/test_bi_pdc_coverage.php
    docker compose exec app php tests/test_bi_pdc_execution.php
    docker compose exec app php tests/test_bi_pdc_planning_gaps.php
    docker compose exec app php tests/test_bi_pdc_detail_query.php
    docker compose exec app php tests/test_bi_pdc_read_contract.php
    docker compose exec app php tests/test_bi_pdc_lineage_signal.php
    docker compose exec app php tests/test_bi_pdc_compatibility.php
    docker compose exec app php tests/test_bi_pdc_source_invariants.php
    docker compose exec app php tests/test_bi_pdc_routes.php

Read each return code separately. Expected: PASS.

### Step 3: Run focused frontend and static checks

    npm --prefix frontend test -- biPdc Pdc consultaPdc estadoPdc useBiPdc
    npm --prefix frontend run typecheck
    npm --prefix frontend run build
    node --test tests/design-system/bi-pdc-react-tokens.test.mjs
    git diff --check

Expected: PASS and no whitespace errors.

### Step 4: Run intercepted browser evidence

    npx playwright test tests/browser/bi-pdc-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-pdc-react.a11y.mjs --workers=1

Expected: PASS.

### Step 5: Run proportional broader checks

Run the existing shell/T03/S12 focused suites only after verifying each is read-only. Do not run
the DML PDC tower-control test. Record every omission and why.

Suggested safe checks after inspection:

    npm --prefix frontend test
    docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G

Expected: PASS. If an existing suite is unsafe, do not replace it with a claim; record it as not run.

### Step 6: Audit scope and forbidden changes

    git status --short
    git diff --name-only
    git diff -- docs/security/rls-runtime-boundary.md src/Security/RbacCatalog.php admin
    rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts'
    rg -n "bi_pdc_general|/api/pdc/|FROM .*pdc_" src/Services/Bi/Pdc src/Controllers/Api/BiControlTowerApiController.php
    git diff --check
    git rev-parse HEAD

Expected:

- no RLS/RBAC catalog/admin diff;
- no fetch outside client;
- no PDC v1;
- scoped PDC v2 reads only;
- no credentials/data artifacts;
- clean diff check.

### Step 7: Request review and close only with authorization

Use `superpowers:requesting-code-review` after implementation. Fix verified findings with TDD. Use
`superpowers:verification-before-completion` on the final SHA.

Only if the user explicitly authorizes branch closure, follow repository PR/CI policy. Do not
commit, push, open a PR, publish or deploy merely because tests are green.

### Step 8: Record Cierre

Record:

- exact SHA;
- commands and independent return codes;
- A/D/R allowed, A independent of the flag and OT/others hidden evidence;
- 6 vs 53 semantic fixture evidence;
- coverage aggregation evidence;
- five viewports/two themes/zoom/axe;
- unexpected requests = 0;
- mutations = 0;
- console errors = 0;
- RLS/schema/data/admin changed = none;
- legacy callers remaining and retirement gate;
- rollback path;
- limitations or skipped unsafe tests.

**Checkpoint:** only `Cierre` plus git history can declare implementation complete.

## Acceptance Traceability

Every criterion appears exactly once in this primary-owner matrix. Tests may cover additional
criteria, but no acceptance item may disappear during execution.

| Criterion | Primary task | Evidence |
|---|---:|---|
| S22-AC-001 | Task 1 | pure access/scope tests |
| S22-AC-002 | Task 1 | pure access/scope tests |
| S22-AC-003 | Task 1 | pure access/scope tests |
| S22-AC-004 | Task 1 | pure access/scope tests |
| S22-AC-005 | Task 1 | pure access/scope tests |
| S22-AC-006 | Task 1 | pure access/scope tests |
| S22-AC-007 | Task 1 | pure access/scope tests |
| S22-AC-008 | Task 1 | pure access/scope tests |
| S22-AC-009 | Task 1 | pure access/scope tests |
| S22-AC-010 | Task 1 | pure access/scope tests |
| S22-AC-011 | Task 1 | pure access/scope tests |
| S22-AC-012 | Task 1 | pure access/scope tests |
| S22-AC-013 | Task 4 | PHP contract and cutoff tests |
| S22-AC-014 | Task 4 | PHP contract and cutoff tests |
| S22-AC-015 | Task 4 | PHP contract and cutoff tests |
| S22-AC-016 | Task 4 | PHP contract and cutoff tests |
| S22-AC-017 | Task 4 | PHP contract and cutoff tests |
| S22-AC-018 | Task 4 | PHP contract and cutoff tests |
| S22-AC-019 | Task 4 | PHP contract and cutoff tests |
| S22-AC-020 | Task 4 | PHP contract and cutoff tests |
| S22-AC-021 | Task 4 | PHP contract and cutoff tests |
| S22-AC-022 | Task 4 | PHP contract and cutoff tests |
| S22-AC-023 | Task 4 | PHP contract and cutoff tests |
| S22-AC-024 | Task 4 | PHP contract and cutoff tests |
| S22-AC-025 | Task 3 | coverage/planning projector tests |
| S22-AC-026 | Task 3 | coverage/planning projector tests |
| S22-AC-027 | Task 3 | coverage/planning projector tests |
| S22-AC-028 | Task 3 | coverage/planning projector tests |
| S22-AC-029 | Task 3 | coverage/planning projector tests |
| S22-AC-030 | Task 3 | coverage/planning projector tests |
| S22-AC-031 | Task 3 | coverage/planning projector tests |
| S22-AC-032 | Task 3 | coverage/planning projector tests |
| S22-AC-033 | Task 3 | coverage/planning projector tests |
| S22-AC-034 | Task 3 | coverage/planning projector tests |
| S22-AC-035 | Task 3 | coverage/planning projector tests |
| S22-AC-036 | Task 3 | coverage/planning projector tests |
| S22-AC-037 | Task 2 | schedule/progress policy tests |
| S22-AC-038 | Task 2 | schedule/progress policy tests |
| S22-AC-039 | Task 2 | schedule/progress policy tests |
| S22-AC-040 | Task 2 | schedule/progress policy tests |
| S22-AC-041 | Task 2 | schedule/progress policy tests |
| S22-AC-042 | Task 2 | schedule/progress policy tests |
| S22-AC-043 | Task 2 | schedule/progress policy tests |
| S22-AC-044 | Task 2 | schedule/progress policy tests |
| S22-AC-045 | Task 2 | schedule/progress policy tests |
| S22-AC-046 | Task 2 | schedule/progress policy tests |
| S22-AC-047 | Task 2 | schedule/progress policy tests |
| S22-AC-048 | Task 2 | schedule/progress policy tests |
| S22-AC-049 | Task 2 | schedule/progress policy tests |
| S22-AC-050 | Task 2 | schedule/progress policy tests |
| S22-AC-051 | Task 2 | schedule/progress policy tests |
| S22-AC-052 | Task 2 | schedule/progress policy tests |
| S22-AC-053 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-054 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-055 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-056 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-057 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-058 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-059 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-060 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-061 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-062 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-063 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-064 | Task 3 | responsibility/duration/privacy tests |
| S22-AC-065 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-066 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-067 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-068 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-069 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-070 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-071 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-072 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-073 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-074 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-075 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-076 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-077 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-078 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-079 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-080 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-081 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-082 | Task 5 | detail query, pagination and drawer tests |
| S22-AC-083 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-084 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-085 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-086 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-087 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-088 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-089 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-090 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-091 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-092 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-093 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-094 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-095 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-096 | Task 9 | breakdown/signal/lineage tests |
| S22-AC-097 | Task 7 | hook and state component tests |
| S22-AC-098 | Task 7 | hook and state component tests |
| S22-AC-099 | Task 7 | hook and state component tests |
| S22-AC-100 | Task 7 | hook and state component tests |
| S22-AC-101 | Task 7 | hook and state component tests |
| S22-AC-102 | Task 7 | hook and state component tests |
| S22-AC-103 | Task 7 | hook and state component tests |
| S22-AC-104 | Task 7 | hook and state component tests |
| S22-AC-105 | Task 10 | responsive/theme/a11y tests |
| S22-AC-106 | Task 10 | responsive/theme/a11y tests |
| S22-AC-107 | Task 10 | responsive/theme/a11y tests |
| S22-AC-108 | Task 10 | responsive/theme/a11y tests |
| S22-AC-109 | Task 10 | responsive/theme/a11y tests |
| S22-AC-110 | Task 10 | responsive/theme/a11y tests |
| S22-AC-111 | Task 10 | responsive/theme/a11y tests |
| S22-AC-112 | Task 10 | responsive/theme/a11y tests |
| S22-AC-113 | Task 10 | responsive/theme/a11y tests |
| S22-AC-114 | Task 10 | responsive/theme/a11y tests |
| S22-AC-115 | Task 10 | responsive/theme/a11y tests |
| S22-AC-116 | Task 10 | responsive/theme/a11y tests |
| S22-AC-117 | Task 7 | source invariant plus gateway test |
| S22-AC-118 | Task 11 | intercepted Playwright request ledger |
| S22-AC-119 | Task 1 | scope reader call-log tests |
| S22-AC-120 | Task 1 | scope reader call-log tests |
| S22-AC-121 | Task 6 | compatibility and no-v1 tests |
| S22-AC-122 | Task 6 | compatibility and no-v1 tests |
| S22-AC-123 | Task 12 | write-free test audit |
| S22-AC-124 | Task 10 | route/sidebar and excluded-path tests |
| S22-AC-125 | Task 10 | route/sidebar and excluded-path tests |
| S22-AC-126 | Task 11 | Playwright console assertion |
| S22-AC-127 | Task 11 | caller census and rollback evidence |
| S22-AC-128 | Task 11 | caller census and rollback evidence |
| S22-AC-129 | Task 12 | final diff/status verification |
| S22-AC-130 | Task 12 | final diff/status verification |

## Vertical Checkpoints

| Checkpoint | User-visible or contract outcome | Rollback |
|---|---|---|
| 1 | sheet access and today cutoff fixed | remove S22 manifest/cutoff files |
| 2 | overdue vs unrecorded semantics fixed | restore compatibility delegation |
| 3 | truthful coverage/gaps/counterweight | remove pure projectors |
| 4 | canonical main payload | route remains on legacy |
| 5 | bounded detail exploration | keep main only |
| 6 | existing endpoints wired compatibly | delegate back to legacy presenter |
| 7 | validated client state | no page route mounted |
| 8 | useful decision core | route remains gated |
| 9 | filters/drawer/breakdowns | core still works |
| 10 | responsive themed accessible UI | remove route registration |
| 11 | served React cut | switch page adapter to legacy |
| 12 | verified closure evidence | no data rollback ever |

## Completion Gate

S22 can be called implementation-complete only when:

1. all 130 criteria map to fresh evidence;
2. A/D/R are allowed, A is independent of the flag and OT/others are hidden by the shared policy;
3. foreign project scope fails before the reader;
4. overdue and unrecorded progress are distinct;
5. coverage uses raw magnitudes and zero denominator is insufficient;
6. planned/closed counterweight and unassigned destinations are visible;
7. both existing GETs pass PHP and Zod contracts;
8. browser uses GET only and unexpected requests are zero;
9. cards/table mount exclusively by breakpoint;
10. dark/light, five viewports, zoom, keyboard and axe pass;
11. no PDC v1, provider detail, RLS, RBAC catalog, schema, data or `/admin/` change exists;
12. unsafe DML tests are explicitly not run;
13. compatibility callers and rollback are recorded;
14. `git diff --check` is clean;
15. no completion, commit, PR, publication or deploy claim precedes authorization and final
    verification.

## Cierre

> Pendiente de una futura sesión de implementación autorizada. Este plan fue escrito y
> autorrevisado; no se ejecutó. Las casillas y pasos no representan avance. La evidencia futura debe
> registrar resultados reales y el SHA exacto verificado.
