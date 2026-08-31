---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [bi, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s23-bi-contratistas-react.md
resumen: "migrate /bi/contratistas into the main React SPA as one shared A/D/R provider decision sheet that shows the five CIC components, withholds current and…"
---

# S23 BI Proveedores React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in order
> and stop at each vertical checkpoint. Checkbox syntax is an execution prompt only; progress and
> closure live in `Cierre` and git history, never in checkbox counts.

**Goal:** migrate `/bi/contratistas` into the main React SPA as one shared A/D/R provider decision
sheet that shows the five CIC components, withholds current and accumulated integral/status until
all five are scored, compares each provider only with its own prior evaluation, exposes actionable
completeness and server-authorized links, and works equivalently on desktop, tablet, mobile, dark
and light without changing RLS, permissions, SQL views, schema or data.

**Architecture:** T01 owns session, project, shell, sidebar, routes, theme and the only HTTP client.
The T03 slice delivered through S17 owns sheet admission, authorized scope, canonical period/query,
shared states, frame, drawer and lineage. S11 owns CIC population, stable provider identity,
presence, evaluation values, diagnostics and all writes; S14 owns the provider master. S23 adds pure
BI completeness/integral/decision/comparison policies and one `BiCicReadService`. The existing
`GET /api/bi/report/cic` remains the sole report endpoint and carries summary, page and embedded
detail. S17 delegates its contractor-alert count to the same model. Canonical and legacy presenters
coexist until the shared caller census permits retirement. React renders server decisions and never
mutates.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/SVG/CSS and existing AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s23-bi-contratistas-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or
  another worktree.
- Inspect status and relevant diff before every task. Preserve all existing work; never clean,
  revert or reformat adjacent paths.
- This planning session is documentation-only. Future commit commands below do not authorize
  implementation, install, build, commit, push, PR, publication or deploy now.
- Start only after T01, the T03 primitives delivered through S17, S11's canonical read model and
  S14's provider-link contract exist and pass focused tests.
- `/admin/` is excluded.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, triggers, grants, users, credentials, memberships, aliases, capability catalogs, flags
  or data.
- Never modify `database/bi/005_bi_cic_contratistas.sql` in this plan.
- No DDL/DML, even inside a rolled-back transaction. New tests use pure policies, fakes, source
  invariants, call logs and static fixtures.
- Inspect every existing test before running it. Exclude BI reconciliation and CIC browser suites
  that seed, save, restore or otherwise mutate data.
- Preserve `BiPreviewAccessPolicy`, `internal.bi.preview`, `bi.control_tower.visible` and RBAC
  catalogs. Extend only the T03 sheet manifest already approved: A/D/R share S23; all others 404.
- Every project requires membership, visibility and `lps.indicadores.ver`. Foreign scope returns
  403 before a provider reader call.
- A defaults to its T03-authorized portfolio; D/R default to the active authorized project. There
  is one data model and composition, not audience variants.
- Period is canonical `desde/hasta`; week is a server-resolved shortcut through each project's real
  week dates.
- Population, providerRef, presence, latest/prior evaluation, metrics and diagnostics come from S11.
  Do not duplicate its queries or materialize projected providers.
- Provider identity is project-qualified and never name-based.
- Component wire state is `scored(0..1)`, `not-rated`, `not-applicable` or `invalid`. Zero is valid.
- Current and accumulated integral each require all five corresponding components scored. NA does
  not complete an integral; NR names a capture owner; invalid never autocorrects.
- BI formula with complete inputs is 30/20/20/20/10. No redistribution in BI.
- Use internal thresholds 0.70 and 0.50. Never trust SQL-view thresholds 70/50 over 0..1.
- `cic_aprobacion_status` inherits completeness from `cic_cal_integral`.
- `reviewBeforeAssignment` requires complete accumulated score below 0.50.
  `attentionThisPeriod` requires complete current score below 0.70 and current-period evidence.
- Status is decision support only and does not block a contract, assignment or mutation.
- Compare only same provider/project with the real previous evaluation. No week-1 shortcut,
  provider league, ordinal rank or overall average integral.
- Every active filter governs headline, summary, coverage, list and breakdown. Filter options alone
  declare scope-period applicability.
- Existing `GET /api/bi/report/cic` handles every page/filter/focus. Add no endpoint, mutation,
  export or download.
- Drawer detail is embedded in each row; no per-row request.
- S17 contractor-alert count delegates to the complete accumulated status, not view alerts or
  historical row count.
- S11/S14 remain sole owners of edits. Server supplies capability-based hrefs or null.
- Email, phone, NIT, contact, individual answers and version tokens never cross S23 HTTP.
- Only `frontend/src/lib/api/cliente.ts` may call fetch. Parse success/error through strict Zod and
  derive types with `z.infer`.
- Abort replaced requests, ignore stale completions and partition cache by user, scope, period,
  filters, pagination and focus.
- Below 768 px mount cards only; at or above 768 px mount semantic table only.
- Use native SVG plus visible text/table; never canvas, hover-only or color-only.
- Reuse T03 `MarcoBi`, `FiltrosBi`, `EstadoBi` and `LinajeDrawer`.
- Use `public/css/tokens.css`. No color literals, `!important`, inline colors, local token family or
  new UI/table/chart/state library.
- Dark is default/fallback and light is equivalent. Validate 390x844, 480x900, 768x1024, 1180x820,
  1440x900 and 200 percent zoom; 44 px targets, reduced motion and axe serious/critical zero.
- Browser evidence is fully intercepted and fails on unexpected URL, every non-GET request and
  console/page errors.
- Do not regenerate or commit visual goldens.
- Keep `views/bi/control-tower.php`, `bi-spa.js`, shared BI CSS/Chart.js and other sheets until the
  T03/S17–S24 caller census reaches zero.
- Rollback changes route/code only and never data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read closure records for T01, S11, S14, S17 and the T03 increment.
2. Verify shared backend contracts:
   `BiSheetAccessPolicy`, `BiProjectScope`, `BiQueryParser` and canonical error envelope.
3. Verify shared frontend contracts:
   `MarcoBi`, `FiltrosBi`, `EstadoBi`, `LinajeDrawer`, responsive drawer and `cliente.ts`.
4. Verify S11 exposes a read-only provider service with stable `providerRef`, selected/prior
   evaluation, component unions, diagnostics and no read side effects.
5. Verify S14 exposes an authorized provider href seam.
6. Verify branch/runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch; `app`, `db`, `adminer` healthy; app mounted from this worktree.
7. Record starting SHA and all pre-existing changed paths. Do not stage them.
8. Inspect all candidate existing tests for DML before executing.
9. Characterize current S23 page/endpoint/callers read-only; copy no provider names or contact data
   into fixtures.
10. If a dependency is absent, execute its owning plan. Do not rebuild S11/S14/T03 inside S23.
11. Do not change `.env` or use the login form when the development door is closed.

## File Structure

### Create — backend

- `src/Services/Bi/Cic/CicComponentValue.php`.
- `src/Services/Bi/Cic/CicComponentNormalizer.php`.
- `src/Services/Bi/Cic/CicBiCompletenessPolicy.php`.
- `src/Services/Bi/Cic/CicBiIntegralPolicy.php`.
- `src/Services/Bi/Cic/CicDecisionLevelPolicy.php`.
- `src/Services/Bi/Cic/BiCicProviderReader.php`.
- `src/Services/Bi/Cic/S11BiCicProviderReader.php`.
- `src/Services/Bi/Cic/CicProviderPeriod.php`.
- `src/Services/Bi/Cic/CicProviderComparison.php`.
- `src/Services/Bi/Cic/CicProviderPriority.php`.
- `src/Services/Bi/Cic/CicProviderSummary.php`.
- `src/Services/Bi/Cic/CicProviderHeadline.php`.
- `src/Services/Bi/Cic/CicProviderActionProjector.php`.
- `src/Services/Bi/Cic/CicProviderQuery.php`.
- `src/Services/Bi/Cic/CicProviderQueryParser.php`.
- `src/Services/Bi/Cic/BiCicReadService.php`.
- `src/Services/Bi/Cic/BiCicPresenter.php`.

### Modify — backend integration

- `src/Security/BiSheetAccessPolicy.php` — shared A/D/R sheet row only.
- S11 read-only extension seam only if its completed implementation lacks the prior-evaluation
  adapter; do not modify S11 formulas or writes.
- `src/Services/Bi/MetricDictionaryService.php` — version 2 completeness/scale metadata.
- `src/Services/Bi/LineageService.php` — expose corrected basis.
- S17 `BiOverviewService`/reader extension seam — delegate contractor-alert count.
- `src/Services/ControlTowerService.php` — compatibility delegation only.
- `src/Controllers/Api/BiControlTowerApiController.php` — thin existing GET delegation.
- `src/Controllers/Bi/BiViewController.php` — policy/SPA handoff at cut.
- `src/View/Components/BiAccessComponent.php` — consume shared manifest, no role branch.
- `public/index.php` only if route adapter registration changes; add no route.

### Create — PHP tests and fixtures

- `tests/Support/Bi/FakeBiCicProviderReader.php`.
- `tests/fixtures/bi-proveedores-react/components.php`.
- `tests/fixtures/bi-proveedores-react/providers.php`.
- `tests/fixtures/bi-proveedores-react/periods.php`.
- `tests/fixtures/bi-proveedores-react/queries.php`.
- `tests/test_bi_cic_access_period.php`.
- `tests/test_bi_cic_components.php`.
- `tests/test_bi_cic_completeness_integral.php`.
- `tests/test_bi_cic_decision_policy.php`.
- `tests/test_bi_cic_provider_reader.php`.
- `tests/test_bi_cic_comparison_summary.php`.
- `tests/test_bi_cic_query.php`.
- `tests/test_bi_cic_read_contract.php`.
- `tests/test_bi_cic_overview_delegation.php`.
- `tests/test_bi_cic_compatibility.php`.
- `tests/test_bi_cic_source_invariants.php`.
- `tests/test_bi_cic_routes.php`.

### Create — frontend

- `frontend/src/lib/api/esquemas/biProveedores.ts` and test.
- `frontend/src/lib/api/biProveedores.ts` and test.
- `frontend/src/modulos/bi/ProveedoresPagina.tsx` and test.
- `frontend/src/modulos/bi/proveedores/consultaProveedores.ts` and test.
- `frontend/src/modulos/bi/proveedores/estadoProveedores.ts` and test.
- `frontend/src/modulos/bi/proveedores/useBiProveedores.ts` and test.
- `frontend/src/modulos/bi/proveedores/TitularProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/ResumenProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/CoberturaComponentes.tsx` and test.
- `frontend/src/modulos/bi/proveedores/FiltrosProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/ListaProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/TablaProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/TarjetasProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/ComponentesProveedor.tsx` and test.
- `frontend/src/modulos/bi/proveedores/ComparacionProveedor.tsx` and test.
- `frontend/src/modulos/bi/proveedores/DesglosesProveedores.tsx` and test.
- `frontend/src/modulos/bi/proveedores/DetalleProveedor.tsx` and test.
- `frontend/src/modulos/bi/proveedores/proveedores.css`.
- `tests/browser/fixtures/bi-proveedores-react.mjs`.
- `tests/browser/bi-proveedores-react.spec.mjs`.
- `tests/browser/bi-proveedores-react.a11y.mjs`.
- `tests/design-system/bi-proveedores-react-tokens.test.mjs`.

### Modify — frontend integration

- `frontend/src/shell/rutas.tsx` — register existing `/bi/contratistas`.
- `frontend/src/shell/NavegacionLateral.tsx` only through the shared server/manifest seam.
- `frontend/src/main.tsx` only if layered module CSS import is required.
- `public/css/tokens.css` only after a failing token-contract test proves a missing semantic token.

### Explicitly preserve

- `database/bi/005_bi_cic_contratistas.sql` and every database asset.
- S11 editor/API/questionnaire/write services.
- S14 master/API/export/write services.
- all `/admin/` paths.
- `views/bi/control-tower.php` and non-S23 sections.
- `public/js/modules/bi-spa.js` and other callers.
- `public/css/bi-control-tower.css` and Chart.js consumers.
- `docs/security/rls-runtime-boundary.md`.

## Task 1: Lock shared sheet admission, project scope and real periods

**Vertical outcome:** A/D/R can request the same S23 model with correct default scope and
project-specific periods; hidden roles and foreign projects never reach a reader.

**Files:** create `CicProviderPeriod.php` and `tests/test_bi_cic_access_period.php`; extend the T03
sheet manifest only.

### Step 1: Write failing pure tests

Cover:

- A/D/R allowed;
- OT and every other role hidden;
- global flag behavior;
- page/API parity;
- A default authorized portfolio;
- D/R active authorized project;
- explicit multi;
- foreign project rejected before fake reader;
- week shortcut resolved through actual project dates;
- explicit date range;
- no future evaluation;
- period-by-project output;
- client role/project values do not authorize;
- no RBAC/RLS mutation.

### Step 2: Prove RED

    docker compose exec app php tests/test_bi_cic_access_period.php

Expected: FAIL because S23 policy/period value are absent.

### Step 3: Implement minimum shared policy and period value

Add one A/D/R manifest row. Reuse T03 scope/period parsers. Do not add a capability or role branch.

### Step 4: Prove GREEN

    docker compose exec app php tests/test_bi_cic_access_period.php

Expected: PASS with fake reader call count zero on denials.

### Step 5: Review and future atomic commit

    git add src/Security/BiSheetAccessPolicy.php src/Services/Bi/Cic/CicProviderPeriod.php tests/fixtures/bi-proveedores-react/periods.php tests/test_bi_cic_access_period.php
    git commit -m "test(bi): lock provider sheet scope and period"

**Checkpoint:** authority and time semantics are fixed without provider business logic.

## Task 2: Make component, completeness, integral and decision policies executable

**Vertical outcome:** synthetic CIC values prove that zero is valid, missing/applicable/invalid are
distinct, incomplete integral is withheld and 0.70/0.50 thresholds work only on complete data.

**Files:** create the five component/completeness/integral/decision classes and focused tests.

### Step 1: Write failing pure tests

Cover:

- numeric 0, 0.5 and 1;
- null/empty/NR;
- NA;
- out-of-range and unknown text;
- raw storage value eliminated;
- five named current/accumulated components;
- basis 5/N;
- not-rated owners;
- NA no capture CTA;
- invalid diagnostics;
- complete-only formula 30/20/20/20/10;
- no redistribution;
- equivalence with S11 complete-input calculator;
- stored mismatch tolerance 0.001;
- threshold edges 0.49/0.50/0.69/0.70;
- status inherits completeness;
- current vs accumulated independence;
- decision actions and negative-action suppression;
- no automated block or predictive wording.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_cic_components.php
    docker compose exec app php tests/test_bi_cic_completeness_integral.php
    docker compose exec app php tests/test_bi_cic_decision_policy.php

Expected: FAIL because policies do not exist.

### Step 3: Implement pure values and policies

Use immutable values and exhaustive matches. Generate owner-role labels from the server catalog
adapter, never frontend constants. Do not read SQL-view derived status/alert columns.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_cic_components.php
    docker compose exec app php tests/test_bi_cic_completeness_integral.php
    docker compose exec app php tests/test_bi_cic_decision_policy.php

Expected: PASS, including three incomplete providers yielding three unavailable and zero
not-accepted alerts.

### Step 5: Review and future commit

    git add src/Services/Bi/Cic/CicComponentValue.php src/Services/Bi/Cic/CicComponentNormalizer.php src/Services/Bi/Cic/CicBiCompletenessPolicy.php src/Services/Bi/Cic/CicBiIntegralPolicy.php src/Services/Bi/Cic/CicDecisionLevelPolicy.php tests/fixtures/bi-proveedores-react/components.php tests/test_bi_cic_components.php tests/test_bi_cic_completeness_integral.php tests/test_bi_cic_decision_policy.php
    git commit -m "feat(bi): enforce complete provider decisions"

**Checkpoint:** the central D44 correction is independently tested and reversible.

## Task 3: Adapt the S11 population, stable identity and prior evaluation

**Vertical outcome:** S23 receives one read-only provider/project row at cutoff, with real prior
evaluation and no query/persistence duplication.

**Files:** create reader port/adapter/fake and `tests/test_bi_cic_provider_reader.php`.

### Step 1: Write failing reader-contract tests

Using an S11 fake, cover:

- one provider/project row;
- same name in two projects remains distinct;
- stable providerRef not based on name;
- latest evaluation <= cutoff;
- prior real evaluation;
- presence/evaluation-in-period flags;
- projected provider remains unpersisted;
- duplicate/metadata diagnostics;
- page stability;
- no future data;
- no PII/answers in BI read DTO;
- read call ledger contains no write/transaction;
- no N+1 for prior evaluation.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_cic_provider_reader.php

Expected: FAIL on absent adapter.

### Step 3: Implement a thin S11 adapter

Consume the completed S11 read interface. Add only a batch prior-evaluation projection if S11's
extension point explicitly allows it. Do not copy SQL or call CIC list endpoints that mutate.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_cic_provider_reader.php

Expected: PASS with zero write calls.

### Step 5: Review and future commit

    git add src/Services/Bi/Cic/BiCicProviderReader.php src/Services/Bi/Cic/S11BiCicProviderReader.php tests/Support/Bi/FakeBiCicProviderReader.php tests/fixtures/bi-proveedores-react/providers.php tests/test_bi_cic_provider_reader.php
    git commit -m "feat(bi): read canonical CIC providers"

**Checkpoint:** S23 has real domain input without owning S11.

## Task 4: Assemble comparison, summary, headline, contract and S17 delegation

**Vertical outcome:** one fake-backed service produces truthful provider decisions and makes the
overview count exactly the complete accumulated providers below 0.50.

**Files:** create comparison/priority/summary/headline/action/service/presenter classes; update
metric/lineage and S17 extension seam; add focused tests.

### Step 1: Write failing tests

Cover:

- same-provider/project comparison only;
- prior cutoff and percentage-point delta;
- incomplete comparison;
- no rank/mean score;
- count-only multi aggregation;
- filtered population/evaluation/completeness/status summaries;
- component coverage with denominators;
- decision/project/type/scope breakdowns;
- default priority;
- finite factual headline;
- authorized/null S11/S14 hrefs;
- strict canonical envelope;
- corrected metric metadata/version;
- S17 alert distinct providers only;
- S17 excludes historical/incomplete rows;
- privacy DTO;
- compatibility scorecard/action/headline from canonical decisions.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_cic_comparison_summary.php
    docker compose exec app php tests/test_bi_cic_read_contract.php
    docker compose exec app php tests/test_bi_cic_overview_delegation.php

Expected: FAIL because projectors/service/presenter are absent.

### Step 3: Implement one read orchestration

Compose Tasks 1–3. Do not call legacy `briefCIC()`, `actionsFromCIC()` or view alert/status.
Use the same provider decision collection for S23 and S17.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_cic_comparison_summary.php
    docker compose exec app php tests/test_bi_cic_read_contract.php
    docker compose exec app php tests/test_bi_cic_overview_delegation.php

Expected: PASS.

After inspecting `tests/test_bi_metric_contracts.php` for read-only behavior, run it if safe:

    docker compose exec app php tests/test_bi_metric_contracts.php

Otherwise record it as skipped and rely on the pure S23 catalog contract.

### Step 5: Review and future commit

    git add src/Services/Bi/Cic/CicProviderComparison.php src/Services/Bi/Cic/CicProviderPriority.php src/Services/Bi/Cic/CicProviderSummary.php src/Services/Bi/Cic/CicProviderHeadline.php src/Services/Bi/Cic/CicProviderActionProjector.php src/Services/Bi/Cic/BiCicReadService.php src/Services/Bi/Cic/BiCicPresenter.php src/Services/Bi/MetricDictionaryService.php src/Services/Bi/LineageService.php tests/test_bi_cic_comparison_summary.php tests/test_bi_cic_read_contract.php tests/test_bi_cic_overview_delegation.php
    git commit -m "feat(bi): assemble provider decision brief"

Stage the actual S17 extension file only when changed and reviewed.

**Checkpoint:** backend decision output and overview correction are coherent without a controller.

## Task 5: Add validated filters, ordering and bounded pagination to the same GET

**Vertical outcome:** every user filter governs the complete sheet while the provider page remains
bounded and shareable without PII.

**Files:** create query/parser and tests; extend read service/presenter.

### Step 1: Write failing query tests

Cover:

- projects/week/range;
- q 0–100 and only name/type/scope;
- type/scope catalog values;
- current/cumulative decision basis;
- completeness/missing component;
- project in authorized scope;
- sort/direction catalog;
- limit 1–100 and non-negative offset;
- focus revalidation;
- filter change reset behavior contract;
- all summary/breakdown/list filters aligned;
- filter options scope-period;
- unavailable last on score sort;
- normalized query;
- URL contains no PII;
- typed 422 fields;
- row detail embedded and no detail route.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_cic_query.php
    docker compose exec app php tests/test_bi_cic_read_contract.php

Expected: FAIL on missing parser.

### Step 3: Implement parser and bounded read path

Apply filters before summary/pagination. Keep filter-option catalog from the scope-period base.
Reuse T03 query conventions and canonical ordering.

### Step 4: Run GREEN

    docker compose exec app php tests/test_bi_cic_query.php
    docker compose exec app php tests/test_bi_cic_read_contract.php

Expected: PASS.

### Step 5: Review and future commit

    git add src/Services/Bi/Cic/CicProviderQuery.php src/Services/Bi/Cic/CicProviderQueryParser.php src/Services/Bi/Cic/BiCicReadService.php src/Services/Bi/Cic/BiCicPresenter.php tests/fixtures/bi-proveedores-react/queries.php tests/test_bi_cic_query.php tests/test_bi_cic_read_contract.php
    git commit -m "feat(bi): filter provider decisions"

**Checkpoint:** the single backend contract now covers initial view and exploration.

## Task 6: Wire the existing endpoint and compatibility without SQL-view authority

**Vertical outcome:** `/api/bi/report/cic` delegates to the canonical service, legacy callers receive
compatible corrected output, and no database/view/write change exists.

**Files:** modify controller/service/view handoff/manifest seams; add compatibility/source/route
tests.

### Step 1: Write failing no-write tests

Use controller fakes/query spies/source inspection to assert:

- same page/API sheet policy;
- scope/query resolved before service;
- exactly one existing CIC report route;
- no detail/mutation route;
- reader uses scoped S11 seam;
- no selection of PII/answers for BI DTO;
- no use of view `aprobacion_status`/`alert_contractor_future_risk` as decision input;
- no `briefCIC()`/`actionsFromCIC()` authority;
- legacy scorecard counts distinct corrected providers;
- presenter compatibility contains required old keys;
- SQL-view file is byte-unchanged;
- no DDL/DML token in new S23 paths.

### Step 2: Run RED

    docker compose exec app php tests/test_bi_cic_compatibility.php
    docker compose exec app php tests/test_bi_cic_source_invariants.php
    docker compose exec app php tests/test_bi_cic_routes.php

Expected: FAIL before delegation.

### Step 3: Implement thin integration

Controller performs auth, T03 policy/scope/query and service call. `ControlTowerService` delegates
CIC compatibility. Page controller keeps legacy until Task 11 route cut. Add no route.

### Step 4: Run GREEN and census

    docker compose exec app php tests/test_bi_cic_compatibility.php
    docker compose exec app php tests/test_bi_cic_source_invariants.php
    docker compose exec app php tests/test_bi_cic_routes.php
    rg -n "/api/bi/report/cic|/bi/contratistas|renderCIC|view-cic|alert_contractor_future_risk" public src views public/js tests frontend

Expected: PASS; census records known callers.

### Step 5: Review and future commit

    git add src/Controllers/Api/BiControlTowerApiController.php src/Controllers/Bi/BiViewController.php src/Services/ControlTowerService.php src/View/Components/BiAccessComponent.php public/index.php tests/test_bi_cic_compatibility.php tests/test_bi_cic_source_invariants.php tests/test_bi_cic_routes.php
    git commit -m "feat(bi): wire canonical provider report"

Do not stage unchanged optional files. Never stage a database path.

**Checkpoint:** backend route is compatible and schema-neutral.

## Task 7: Define strict Zod, gateway, query codec and remote state

**Vertical outcome:** frontend loads/paginates/filters/refocuses the single GET through `cliente.ts`,
with abort, cache isolation and all remote states.

**Files:** create API/schema/query/state/hook files/tests; extend `cliente.ts` only if T01 lacks typed
errors or AbortSignal.

### Step 1: Write failing frontend tests

Cover:

- strict success/error schemas;
- component discriminated union;
- PII/answer/version fields rejected;
- null integral and completeness;
- current/accumulated decisions;
- query round trip;
- q debounce 250 ms;
- filter resets offset;
- focus validation response;
- one GET only;
- AbortSignal;
- stale completion ignored;
- cache identity;
- refresh keeps snapshot;
- partial/offline/invalid;
- period change clears prior comparison/page;
- source search for fetch.

### Step 2: Run RED

    npm --prefix frontend test -- biProveedores consultaProveedores estadoProveedores useBiProveedores

Expected: FAIL because files do not exist.

### Step 3: Implement minimal client state

Reuse T03 query/error/cache primitives and `cliente.ts`. Add no state/query library. Derive all
types from Zod.

### Step 4: Run GREEN and typecheck

    npm --prefix frontend test -- biProveedores consultaProveedores estadoProveedores useBiProveedores
    npm --prefix frontend run typecheck
    rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts'

Expected: PASS and no fetch outside client.

### Step 5: Review and future commit

    git add frontend/src/lib/api/esquemas/biProveedores.ts frontend/src/lib/api/esquemas/biProveedores.test.ts frontend/src/lib/api/biProveedores.ts frontend/src/lib/api/biProveedores.test.ts frontend/src/modulos/bi/proveedores/consultaProveedores.ts frontend/src/modulos/bi/proveedores/consultaProveedores.test.ts frontend/src/modulos/bi/proveedores/estadoProveedores.ts frontend/src/modulos/bi/proveedores/estadoProveedores.test.ts frontend/src/modulos/bi/proveedores/useBiProveedores.ts frontend/src/modulos/bi/proveedores/useBiProveedores.test.ts frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts
    git commit -m "feat(frontend): add provider report client state"

Do not stage unchanged client files.

**Checkpoint:** validated remote state exists before the page.

## Task 8: Render the first useful provider decision slice

**Vertical outcome:** intercepted data renders headline/period, review and attention counts,
completeness declaration and actual provider rows with five components.

**Files:** create page, headline, summary, component coverage/list/component components and tests.

### Step 1: Write failing component tests

Assert:

- no KPI rows masquerade as providers;
- order: headline, actions, completeness, list;
- actual provider/project identity;
- five named components;
- 0 %, not-rated, not-applicable and invalid distinct;
- integral disabled with N/5 and missing owners;
- complete current/accumulated status;
- review/attention action wording;
- no false acceptable/future-risk copy;
- no average/rank;
- PII/answers absent;
- authorized/null hrefs;
- loading/refresh/partial/empty/insufficient/offline/invalid/error;
- plain observation only in detail, not primary list.

### Step 2: Run RED

    npm --prefix frontend test -- ProveedoresPagina TitularProveedores ResumenProveedores CoberturaComponentes ListaProveedores ComponentesProveedor

Expected: FAIL because components do not exist.

### Step 3: Implement smallest composed page

Use `MarcoBi`, `FiltrosBi` and `EstadoBi`. Render server decisions without local formulas. Keep drawer
and advanced filters for Task 9.

### Step 4: Run GREEN

    npm --prefix frontend test -- ProveedoresPagina TitularProveedores ResumenProveedores CoberturaComponentes ListaProveedores ComponentesProveedor
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 5: Review and future commit

    git add frontend/src/modulos/bi/ProveedoresPagina.tsx frontend/src/modulos/bi/ProveedoresPagina.test.tsx frontend/src/modulos/bi/proveedores/TitularProveedores.tsx frontend/src/modulos/bi/proveedores/TitularProveedores.test.tsx frontend/src/modulos/bi/proveedores/ResumenProveedores.tsx frontend/src/modulos/bi/proveedores/ResumenProveedores.test.tsx frontend/src/modulos/bi/proveedores/CoberturaComponentes.tsx frontend/src/modulos/bi/proveedores/CoberturaComponentes.test.tsx frontend/src/modulos/bi/proveedores/ListaProveedores.tsx frontend/src/modulos/bi/proveedores/ListaProveedores.test.tsx frontend/src/modulos/bi/proveedores/ComponentesProveedor.tsx frontend/src/modulos/bi/proveedores/ComponentesProveedor.test.tsx
    git commit -m "feat(frontend): render provider decision core"

**Checkpoint:** a useful, truthful provider list exists before visualization richness.

## Task 9: Add filters, comparison, breakdowns, table/cards and contextual drawer

**Vertical outcome:** users can explore and inspect complete evidence without a row request or write.

**Files:** create filters, table/cards, comparison, breakdown, detail and tests; compose shared
drawer.

### Step 1: Write failing tests

Cover:

- q/type/scope/decision/basis/completeness/component/project;
- sort/pagination;
- filter catalogs do not collapse;
- same provider prior comparison;
- percentage-point delta and insufficient state;
- no rank/mean;
- semantic table and mobile cards;
- exclusive responsive mount;
- component/decision/project/type breakdowns;
- native SVG plus visible data table;
- drawer from row/status/component;
- formula/weights/threshold/basis;
- observation plain;
- PII/answers/version absent;
- focus/trap/Escape/return;
- CIC/S14 href allowed/null;
- no save/mutation action.

### Step 2: Run RED

    npm --prefix frontend test -- FiltrosProveedores TablaProveedores TarjetasProveedores ComparacionProveedor DesglosesProveedores DetalleProveedor ProveedoresPagina

Expected: FAIL.

### Step 3: Implement with shared T03 primitives

Use shared viewport/drawer/query primitives. Do not use CSS-only duplicate representations. Do not
calculate scores in React.

### Step 4: Run GREEN

    npm --prefix frontend test -- FiltrosProveedores TablaProveedores TarjetasProveedores ComparacionProveedor DesglosesProveedores DetalleProveedor ProveedoresPagina
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 5: Review and future commit

    git add frontend/src/modulos/bi/proveedores/FiltrosProveedores.tsx frontend/src/modulos/bi/proveedores/FiltrosProveedores.test.tsx frontend/src/modulos/bi/proveedores/TablaProveedores.tsx frontend/src/modulos/bi/proveedores/TablaProveedores.test.tsx frontend/src/modulos/bi/proveedores/TarjetasProveedores.tsx frontend/src/modulos/bi/proveedores/TarjetasProveedores.test.tsx frontend/src/modulos/bi/proveedores/ComparacionProveedor.tsx frontend/src/modulos/bi/proveedores/ComparacionProveedor.test.tsx frontend/src/modulos/bi/proveedores/DesglosesProveedores.tsx frontend/src/modulos/bi/proveedores/DesglosesProveedores.test.tsx frontend/src/modulos/bi/proveedores/DetalleProveedor.tsx frontend/src/modulos/bi/proveedores/DetalleProveedor.test.tsx frontend/src/modulos/bi/ProveedoresPagina.tsx frontend/src/modulos/bi/ProveedoresPagina.test.tsx
    git commit -m "feat(frontend): explore provider evidence"

**Checkpoint:** every decision can be inspected without exposing or changing the questionnaire.

## Task 10: Integrate shared sidebar/route, responsive themes and accessibility

**Vertical outcome:** Proveedores appears in both approved lienzos and works across themes,
viewports, zoom, keyboard and assistive technology.

**Files:** route/sidebar integration, module CSS, token and accessibility tests.

### Step 1: Write failing tests

Cover:

- exact `/bi/contratistas` route;
- same page A/D/R;
- nav visible/active for both lienzos;
- hidden other roles;
- dark/default and light;
- token-only CSS;
- 44 px targets;
- table/cards exclusive at 768;
- five viewports and zoom without page overflow;
- focus/aria-sort;
- 0/not-rated/NA/invalid accessible text;
- integral-disabled reason;
- SVG alternative;
- reduced motion;
- full keyboard path;
- axe serious/critical zero;
- `/admin/` untouched.

### Step 2: Run RED

    npm --prefix frontend test -- rutas NavegacionLateral ProveedoresPagina
    node --test tests/design-system/bi-proveedores-react-tokens.test.mjs

Expected: FAIL before integration.

### Step 3: Implement shared registration and CSS

Consume the server/shared manifest; no role conditions in React. Add a token only after a failing
contract demonstrates the gap.

### Step 4: Run GREEN and build

    npm --prefix frontend test -- rutas NavegacionLateral ProveedoresPagina
    node --test tests/design-system/bi-proveedores-react-tokens.test.mjs
    npm --prefix frontend run typecheck
    npm --prefix frontend run build

Expected: PASS.

### Step 5: Review and future commit

    git add frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/NavegacionLateral.test.tsx frontend/src/modulos/bi/ProveedoresPagina.tsx frontend/src/modulos/bi/ProveedoresPagina.test.tsx frontend/src/modulos/bi/proveedores/proveedores.css frontend/src/main.tsx public/css/tokens.css tests/design-system/bi-proveedores-react-tokens.test.mjs
    git commit -m "feat(frontend): integrate accessible provider sheet"

Do not stage unchanged optional files.

**Checkpoint:** frontend is ready for served, intercepted evidence.

## Task 11: Cut the existing page route and verify in browser

**Vertical outcome:** served `/bi/contratistas` is React, network is GET-only/expected, console is
clean, and shared legacy remains for other BI sheets.

**Files:** intercepted fixtures/specs plus final page-route handoff and caller census.

### Step 1: Create deterministic fixtures and failing scenarios

Fixtures:

- A/D/R identical payload;
- hidden role 404;
- foreign project 403;
- three providers, all incomplete, legacy would falsely alert;
- complete scores at threshold edges;
- score zero;
- NA/NR/invalid;
- prior comparison;
- multi-project;
- empty/filter-empty;
- partial/offline/invalid;
- dark/light.

Scenarios run 390x844, 480x900, 768x1024, 1180x820, 1440x900 and 200 percent zoom. Fail unexpected
URLs, every non-GET request and all console/page errors.

### Step 2: Run RED before cut

    npx playwright test tests/browser/bi-proveedores-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-proveedores-react.a11y.mjs --workers=1

Expected: FAIL on legacy route/missing fixture integration.

### Step 3: Complete route cut

Switch only `/bi/contratistas` to T01 SPA after API/UI contracts are green. Keep endpoint and
compatibility. Delete no shared legacy asset.

### Step 4: Run GREEN

    npx playwright test tests/browser/bi-proveedores-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-proveedores-react.a11y.mjs --workers=1

Expected: PASS, mutation count zero, unexpected requests zero, console clean, axe zero.

### Step 5: Caller census and rollback

    rg -n "/api/bi/report/cic|/bi/contratistas|renderCIC|view-cic|alert_contractor_future_risk" public src views public/js tests frontend
    git diff -- src/Controllers/Bi/BiViewController.php src/Controllers/Api/BiControlTowerApiController.php frontend/src/shell/rutas.tsx

Record:

- canonical callers;
- compatibility callers;
- S17 delegation;
- shared retirement eligibility false until T03/S17–S24;
- rollback page adapter to legacy, no data operation.

### Step 6: Review and future commit

    git add tests/browser/fixtures/bi-proveedores-react.mjs tests/browser/bi-proveedores-react.spec.mjs tests/browser/bi-proveedores-react.a11y.mjs src/Controllers/Bi/BiViewController.php src/Controllers/Api/BiControlTowerApiController.php frontend/src/shell/rutas.tsx
    git commit -m "test(bi): verify provider React route"

**Checkpoint:** S23 is vertically complete and independently reversible.

## Task 12: Run focused-to-broad verification and prepare closure evidence

**Vertical outcome:** fresh read-only evidence supports the exact SHA and the diff proves that no
database/security/admin surface changed.

### Step 1: Audit proposed tests for writes

Open every existing suite before use. Do not run:

- `tests/test_bi_source_reconciliation.php`;
- CIC browser tests that save/restore;
- any fixture seed, report processor, migration or transaction that writes.

Record exclusions; do not convert them into pass claims.

### Step 2: Run focused PHP tests separately

    docker compose exec app php tests/test_bi_cic_access_period.php
    docker compose exec app php tests/test_bi_cic_components.php
    docker compose exec app php tests/test_bi_cic_completeness_integral.php
    docker compose exec app php tests/test_bi_cic_decision_policy.php
    docker compose exec app php tests/test_bi_cic_provider_reader.php
    docker compose exec app php tests/test_bi_cic_comparison_summary.php
    docker compose exec app php tests/test_bi_cic_query.php
    docker compose exec app php tests/test_bi_cic_read_contract.php
    docker compose exec app php tests/test_bi_cic_overview_delegation.php
    docker compose exec app php tests/test_bi_cic_compatibility.php
    docker compose exec app php tests/test_bi_cic_source_invariants.php
    docker compose exec app php tests/test_bi_cic_routes.php

Read each return code independently. Expected: PASS.

### Step 3: Run focused frontend/static checks

    npm --prefix frontend test -- biProveedores Proveedores consultaProveedores estadoProveedores useBiProveedores
    npm --prefix frontend run typecheck
    npm --prefix frontend run build
    node --test tests/design-system/bi-proveedores-react-tokens.test.mjs
    git diff --check

Expected: PASS.

### Step 4: Run intercepted browser checks

    npx playwright test tests/browser/bi-proveedores-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-proveedores-react.a11y.mjs --workers=1

Expected: PASS.

### Step 5: Run proportional broader checks

After source-inspecting for no writes:

    npm --prefix frontend test
    docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G

Expected: PASS. Unsafe suites remain explicitly skipped.

### Step 6: Audit forbidden changes

    git status --short
    git diff --name-only
    git diff -- database/bi/005_bi_cic_contratistas.sql docs/security/rls-runtime-boundary.md src/Security/RbacCatalog.php admin
    rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts'
    rg -n "CREATE |ALTER |DROP |INSERT |UPDATE |DELETE " src/Services/Bi/Cic
    git diff --check
    git rev-parse HEAD

Expected:

- SQL view/RLS/RBAC/admin diff empty;
- no mutation SQL;
- no fetch outside client;
- no PII fixture copied from real data;
- no schema/data/credential artifacts;
- clean diff check.

### Step 7: Review and closure authorization

Use `superpowers:requesting-code-review` after implementation and fix verified findings through TDD.
Use `superpowers:verification-before-completion` on the final SHA.

Only explicit user authorization permits commits beyond planned atomic execution, push, PR,
publication or deploy; follow repository PR/CI policy at branch closure.

### Step 8: Record Cierre

Record:

- exact SHA;
- every command/independent return code;
- A/D/R same-sheet and hidden-role evidence;
- incomplete providers produce unavailable, not alerts;
- 0 score evidence;
- threshold and S17 delegation evidence;
- PII/answers absent;
- five viewports/two themes/zoom/axe;
- mutation/unexpected request/console counts zero;
- SQL view/RLS/schema/data/admin changes none;
- unsafe tests skipped with reason;
- callers/retirement gate;
- rollback.

**Checkpoint:** only `Cierre` plus git history can declare implementation complete.

## Acceptance Traceability

Every criterion has one primary owner. Additional tests may overlap, but none may disappear.

| Criterion | Primary task | Evidence |
|---|---:|---|
| S23-AC-001 | Task 1 | pure sheet-access and period tests |
| S23-AC-002 | Task 1 | pure sheet-access and period tests |
| S23-AC-003 | Task 1 | pure sheet-access and period tests |
| S23-AC-004 | Task 1 | pure sheet-access and period tests |
| S23-AC-005 | Task 1 | pure sheet-access and period tests |
| S23-AC-006 | Task 1 | pure sheet-access and period tests |
| S23-AC-007 | Task 1 | pure sheet-access and period tests |
| S23-AC-008 | Task 1 | pure sheet-access and period tests |
| S23-AC-009 | Task 1 | pure sheet-access and period tests |
| S23-AC-010 | Task 1 | pure sheet-access and period tests |
| S23-AC-011 | Task 1 | pure sheet-access and period tests |
| S23-AC-012 | Task 1 | pure sheet-access and period tests |
| S23-AC-013 | Task 1 | pure sheet-access and period tests |
| S23-AC-014 | Task 1 | pure sheet-access and period tests |
| S23-AC-015 | Task 1 | pure sheet-access and period tests |
| S23-AC-016 | Task 1 | pure sheet-access and period tests |
| S23-AC-017 | Task 3 | S11 reader identity/population tests |
| S23-AC-018 | Task 3 | S11 reader identity/population tests |
| S23-AC-019 | Task 3 | S11 reader identity/population tests |
| S23-AC-020 | Task 3 | S11 reader identity/population tests |
| S23-AC-021 | Task 3 | S11 reader identity/population tests |
| S23-AC-022 | Task 3 | S11 reader identity/population tests |
| S23-AC-023 | Task 3 | S11 reader identity/population tests |
| S23-AC-024 | Task 3 | S11 reader identity/population tests |
| S23-AC-025 | Task 3 | S11 reader identity/population tests |
| S23-AC-026 | Task 3 | S11 reader identity/population tests |
| S23-AC-027 | Task 3 | S11 reader identity/population tests |
| S23-AC-028 | Task 3 | S11 reader identity/population tests |
| S23-AC-029 | Task 2 | component normalization/completeness tests |
| S23-AC-030 | Task 2 | component normalization/completeness tests |
| S23-AC-031 | Task 2 | component normalization/completeness tests |
| S23-AC-032 | Task 2 | component normalization/completeness tests |
| S23-AC-033 | Task 2 | component normalization/completeness tests |
| S23-AC-034 | Task 2 | component normalization/completeness tests |
| S23-AC-035 | Task 2 | component normalization/completeness tests |
| S23-AC-036 | Task 2 | component normalization/completeness tests |
| S23-AC-037 | Task 2 | component normalization/completeness tests |
| S23-AC-038 | Task 2 | component normalization/completeness tests |
| S23-AC-039 | Task 2 | component normalization/completeness tests |
| S23-AC-040 | Task 2 | component normalization/completeness tests |
| S23-AC-041 | Task 2 | component normalization/completeness tests |
| S23-AC-042 | Task 2 | component normalization/completeness tests |
| S23-AC-043 | Task 2 | component normalization/completeness tests |
| S23-AC-044 | Task 2 | component normalization/completeness tests |
| S23-AC-045 | Task 2 | component normalization/completeness tests |
| S23-AC-046 | Task 2 | component normalization/completeness tests |
| S23-AC-047 | Task 2 | component normalization/completeness tests |
| S23-AC-048 | Task 2 | component normalization/completeness tests |
| S23-AC-049 | Task 2 | integral/decision/action policy tests |
| S23-AC-050 | Task 2 | integral/decision/action policy tests |
| S23-AC-051 | Task 2 | integral/decision/action policy tests |
| S23-AC-052 | Task 2 | integral/decision/action policy tests |
| S23-AC-053 | Task 2 | integral/decision/action policy tests |
| S23-AC-054 | Task 2 | integral/decision/action policy tests |
| S23-AC-055 | Task 2 | integral/decision/action policy tests |
| S23-AC-056 | Task 2 | integral/decision/action policy tests |
| S23-AC-057 | Task 2 | integral/decision/action policy tests |
| S23-AC-058 | Task 2 | integral/decision/action policy tests |
| S23-AC-059 | Task 2 | integral/decision/action policy tests |
| S23-AC-060 | Task 2 | integral/decision/action policy tests |
| S23-AC-061 | Task 2 | integral/decision/action policy tests |
| S23-AC-062 | Task 2 | integral/decision/action policy tests |
| S23-AC-063 | Task 2 | integral/decision/action policy tests |
| S23-AC-064 | Task 2 | integral/decision/action policy tests |
| S23-AC-065 | Task 2 | integral/decision/action policy tests |
| S23-AC-066 | Task 2 | integral/decision/action policy tests |
| S23-AC-067 | Task 2 | integral/decision/action policy tests |
| S23-AC-068 | Task 2 | integral/decision/action policy tests |
| S23-AC-069 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-070 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-071 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-072 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-073 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-074 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-075 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-076 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-077 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-078 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-079 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-080 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-081 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-082 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-083 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-084 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-085 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-086 | Task 4 | comparison/summary/S17 delegation tests |
| S23-AC-087 | Task 5 | query/pagination/contract tests |
| S23-AC-088 | Task 5 | query/pagination/contract tests |
| S23-AC-089 | Task 5 | query/pagination/contract tests |
| S23-AC-090 | Task 5 | query/pagination/contract tests |
| S23-AC-091 | Task 5 | query/pagination/contract tests |
| S23-AC-092 | Task 5 | query/pagination/contract tests |
| S23-AC-093 | Task 5 | query/pagination/contract tests |
| S23-AC-094 | Task 5 | query/pagination/contract tests |
| S23-AC-095 | Task 5 | query/pagination/contract tests |
| S23-AC-096 | Task 5 | query/pagination/contract tests |
| S23-AC-097 | Task 5 | query/pagination/contract tests |
| S23-AC-098 | Task 5 | query/pagination/contract tests |
| S23-AC-099 | Task 5 | query/pagination/contract tests |
| S23-AC-100 | Task 5 | query/pagination/contract tests |
| S23-AC-101 | Task 5 | query/pagination/contract tests |
| S23-AC-102 | Task 9 | drawer/action component tests |
| S23-AC-103 | Task 9 | drawer/action component tests |
| S23-AC-104 | Task 9 | drawer/action component tests |
| S23-AC-105 | Task 9 | drawer/action component tests |
| S23-AC-106 | Task 9 | drawer/action component tests |
| S23-AC-107 | Task 9 | drawer/action component tests |
| S23-AC-108 | Task 9 | drawer/action component tests |
| S23-AC-109 | Task 7 | hook/state tests |
| S23-AC-110 | Task 7 | hook/state tests |
| S23-AC-111 | Task 7 | hook/state tests |
| S23-AC-112 | Task 7 | hook/state tests |
| S23-AC-113 | Task 7 | hook/state tests |
| S23-AC-114 | Task 7 | hook/state tests |
| S23-AC-115 | Task 7 | hook/state tests |
| S23-AC-116 | Task 7 | hook/state tests |
| S23-AC-117 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-118 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-119 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-120 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-121 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-122 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-123 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-124 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-125 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-126 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-127 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-128 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-129 | Task 10 | responsive/theme/accessibility tests |
| S23-AC-130 | Task 11 | Playwright console ledger |
| S23-AC-131 | Task 7 | fetch source invariant |
| S23-AC-132 | Task 11 | intercepted network ledger |
| S23-AC-133 | Task 6 | scoped reader call-log tests |
| S23-AC-134 | Task 4 | strict DTO/privacy contract |
| S23-AC-135 | Task 12 | forbidden-diff/write-free test audit |
| S23-AC-136 | Task 12 | forbidden-diff/write-free test audit |
| S23-AC-137 | Task 10 | excluded-path test |
| S23-AC-138 | Task 6 | compatibility presenter tests |
| S23-AC-139 | Task 6 | compatibility presenter tests |
| S23-AC-140 | Task 10 | shared sidebar manifest test |
| S23-AC-141 | Task 11 | caller census and rollback evidence |
| S23-AC-142 | Task 11 | caller census and rollback evidence |
| S23-AC-143 | Task 11 | caller census and rollback evidence |
| S23-AC-144 | Task 12 | final diff/status verification |
| S23-AC-145 | Task 12 | final diff/status verification |

## Vertical Checkpoints

| Checkpoint | Outcome | Rollback |
|---|---|---|
| 1 | shared access and real period | remove S23 manifest/period value |
| 2 | truthful complete-only decisions | remove pure policies |
| 3 | canonical S11 provider input | remove adapter |
| 4 | summary/contract/S17 coherent | keep legacy route |
| 5 | bounded filters/page | keep default query only |
| 6 | existing endpoint wired | delegate compatibility back |
| 7 | validated client state | no page route |
| 8 | useful provider core | route remains gated |
| 9 | comparison/drawer/breakdowns | core still functions |
| 10 | route/sidebar/themes/a11y | remove frontend registration |
| 11 | served React cut | switch page adapter to legacy |
| 12 | verified closure evidence | no data rollback ever |

## Completion Gate

S23 can be called implementation-complete only when:

1. all 145 criteria have fresh evidence;
2. A/D/R share one model and hidden roles receive 404;
3. foreign projects fail before reader invocation;
4. 0/NR/NA/invalid remain distinct;
5. both integrals require five scored components;
6. 0.70/0.50 boundaries and stored mismatch are tested;
7. incomplete providers never become not-accepted/alert;
8. S17 delegates to distinct complete accumulated providers below 0.50;
9. provider comparison is intraprovider and no rank/average exists;
10. one existing GET passes PHP/Zod contracts and browser emits GET only;
11. PII/answers/version tokens are absent;
12. table/cards mount exclusively;
13. dark/light, viewports, zoom, keyboard and axe pass;
14. SQL view/RLS/RBAC/schema/data/admin diff is empty;
15. unsafe DML suites are recorded as not run;
16. compatibility, callers and rollback are recorded;
17. `git diff --check` is clean;
18. no completion/publication/deploy claim precedes authorization and final verification.

## Cierre

> Pendiente de una futura sesión de implementación autorizada. Este plan fue escrito y
> autorrevisado; no se ejecutó. Sus pasos no representan avance. La evidencia futura debe registrar
> resultados reales y el SHA exacto verificado.
