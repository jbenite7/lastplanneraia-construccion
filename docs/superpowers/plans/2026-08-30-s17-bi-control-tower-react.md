---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [bi, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s17-bi-control-tower-react.md
resumen: "migrate /bi/control-tower into the main React SPA as the management-only Executive Summary: compare every authorized project, state which project needs…"
---

# S17 BI Control Tower React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Use superpowers:test-driven-development for every task and
> verification-before-completion before any completion claim. Execute tasks in order and stop at
> every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and closure live
> in Cierre and git history, never in checkbox counts.

**Goal:** migrate /bi/control-tower into the main React SPA as the management-only Executive
Summary: compare every authorized project, state which project needs intervention and why, expose
auditable headline, portfolio signals, complete actions, scorecard, drivers, risks and lineage,
preserve filters and drilldown navigation, and provide equivalent desktop/tablet/mobile,
dark/light and accessible behavior without any write, RLS, schema or data change.

**Architecture:** T01 remains owner of session, active project, shell, theme, route outlet and the
only HTTP client. This vertical slice creates the first reusable T03 BI frame: sheet access policy,
canonical query codec, authorized portfolio context, BI layout and lineage drawer. S17 adds a pure
overview read model on top of the existing metric/catalog services. PHP resolves one coherent
snapshot per authorized project, computes deterministic priority and narrative, and emits a strict
envelope. React renders that envelope and never calculates metrics, permissions, priority or
narrative. The existing GET endpoint remains the URL; a compatibility adapter serves legacy
consumers until all eight BI sheets migrate.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright, the existing accessibility harness and AIA design-system
tokens.

**Spec:** docs/superpowers/specs/2026-08-30-s17-bi-control-tower-react-design.md

## Global Constraints

- Work only in
  /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react on branch
  shell-minimo-react. Never use /Volumes/Crucial X6/Developer/lps-aia, the parent checkout or
  another worktree.
- Inspect git status --short and relevant diffs before every task. Preserve unrelated and
  pre-existing edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future instructions and require explicit
  implementation authorization.
- /admin/ is excluded. Do not edit its controllers, views, routes, flags UI, permissions or tests.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, keys, triggers, grants, users, credentials, memberships, roles, aliases, overrides or
  data.
- No DDL/DML is permitted. New PHP tests use pure services, fakes, call logs and static fixtures.
  Browser tests install complete interception before navigation and fail on every mutation.
- Do not run existing BI fixture suites that write and roll back MySQL as evidence for this plan.
  A rollback write is still DML.
- Preserve BiPreviewAccessPolicy: Admin always passes; D/R remain governed by the global flag for
  the BI sheets they own. Do not edit the flag.
- Add a sheet-level policy from the approved canvases: overview belongs to Admin A; D and R land on
  Intermedia and receive the existing hidden-module 404 on direct overview access.
- Admin A may choose either Gerencia or Obra. That navigation preference does not revoke direct
  overview authorization: S17 remains a Gerencia-classified, A-only sheet and D/R still receive 404.
- Preserve lps.indicadores.ver per project and BiProjectScope authorization. Client project_ids,
  project_id, role, permiso, db, prefix, username or capabilities are never authority.
- Overview alone defaults to all BI-authorized projects when the query omits project_ids. Do not
  change the single-project default of S18–S24.
- Multi-project period uses the T03 canonical range and preserves a cutoff per project. Never imply
  that equal week numbers mean an equal calendar cutoff across projects.
- Never hide a selected project because its metrics are missing. Emit completeness=insufficient
  and null values, never synthetic zero.
- Never average project percentages. Preserve numerator and denominator and aggregate ratios from
  sums when a consolidated ratio is legitimate.
- Publish forecast only when the metric catalog says its history and contractual baseline are
  sufficient. Always pair probable finish with P10–P90 range, committed finish and variance words.
- Implement portfolio ordering exactly as the spec: orphan constraints, orphan count, positive
  finish variance, worsening trend, number of adverse signals, stable project name. Do not add a
  weighted score.
- At most one row has requiresIntervention=true. If no row has an adverse publishable signal, none
  is highlighted.
- Narrative is a finite server-side template with templateKey, facts, confidence and cutoff. Do
  not use generative AI or rebuild grammar in React.
- Keep the eight current overview KPIs by stable key. Never index by array position and never label
  them PPC, Programadas, Ejecutadas or Brecha.
- Remove the two overview PAC/PPC chart presentations. Do not replace them with generic charts.
- Drivers, risks and scorecard remain available as secondary evidence. Lineage must become visible.
- Recommended actions are read-only recommendations. Do not call createAction, closeAction,
  restriction management or any POST/PUT/PATCH/DELETE endpoint.
- Every action shows project/scope, owner, absolute due date or explicit absence, impact, evidence
  and an href to S18–S24. No onclick-only navigation.
- Only frontend/src/lib/api/cliente.ts may call fetch. Gateways call its typed request primitive.
- Every response is parsed by Zod before components see it. Types come from z.infer, never a manual
  parallel interface.
- Use native semantic table/cards/disclosure/dialog patterns. Do not add DataTables, Chart.js,
  jQuery, Bootstrap, Tailwind, a query library, a state library, a chart library or CSS-in-JS.
- At widths below 768 mount cards only; at 768 and above mount the semantic table only. Do not mount
  both and hide one only with CSS.
- Use public/css/tokens.css. No hex/rgb/hsl literals, inline style colors, important rules or local
  theme forks.
- Dark is default/fallback and light has identical capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Do not delete views/bi files, bi-spa.js, BI CSS, Chart.js or lucide legacy dependencies in this
  plan. Their retirement is the T03 gate after S17–S24.

## File Structure

### Create — shared T03 slice

- src/Security/BiSheetAccessPolicy.php — approved canvas-to-role policy.
- src/Support/BiOverviewScopeResolver.php — all-authorized default only for overview.
- src/Services/Bi/Http/BiQuery.php — canonical parsed query value.
- src/Services/Bi/Http/BiQueryParser.php — query validation and alias adapter.
- tests/Support/Bi/FakeAuthorizedProjects.php — deterministic project authorization fake.
- tests/test_bi_sheet_access_policy.php.
- tests/test_bi_overview_scope.php.
- tests/test_bi_query_contract.php.
- frontend/src/modulos/bi/MarcoBi.tsx.
- frontend/src/modulos/bi/NavegacionBi.tsx.
- frontend/src/modulos/bi/FiltrosBi.tsx.
- frontend/src/modulos/bi/LinajeDrawer.tsx.
- frontend/src/modulos/bi/EstadoBi.tsx.
- frontend/src/modulos/bi/bi-marco.css.
- frontend/src/lib/api/esquemas/biComun.ts.
- frontend/src/modulos/bi/consultaBi.ts.
- Colocated unit/component tests for those files.

### Create — S17 backend

- src/Services/Bi/Overview/BiOverviewProjectReader.php — read port, one coherent project snapshot.
- src/Services/Bi/Overview/PdoBiOverviewProjectReader.php — scoped production adapter.
- src/Services/Bi/Overview/BiOverviewMetric.php — metric/completeness value objects.
- src/Services/Bi/Overview/BiOverviewPriority.php — pure ordering rule.
- src/Services/Bi/Overview/BiOverviewHeadline.php — finite narrative templates.
- src/Services/Bi/Overview/BiOverviewActionTarget.php — action-to-sheet links.
- src/Services/Bi/Overview/BiOverviewService.php — orchestration.
- src/Services/Bi/Overview/BiOverviewPresenter.php — canonical wire envelope plus legacy adapter.
- tests/Support/Bi/FakeBiOverviewProjectReader.php.
- tests/fixtures/bi-overview-react/contracts.php.
- tests/fixtures/bi-overview-react/projects.php.
- tests/test_bi_overview_priority.php.
- tests/test_bi_overview_headline.php.
- tests/test_bi_overview_service.php.
- tests/test_api_bi_overview_contract.php.
- tests/test_bi_overview_source_invariants.php.
- tests/test_bi_overview_routes.php.

### Create — S17 frontend

- frontend/src/lib/api/esquemas/biResumenEjecutivo.ts.
- frontend/src/lib/api/biResumenEjecutivo.ts.
- frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx.
- frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.tsx.
- frontend/src/modulos/bi/resumen/PanoramaObras.tsx.
- frontend/src/modulos/bi/resumen/FilaPanorama.tsx.
- frontend/src/modulos/bi/resumen/TarjetaPanorama.tsx.
- frontend/src/modulos/bi/resumen/AccionesRecomendadas.tsx.
- frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.tsx.
- frontend/src/modulos/bi/resumen/RiesgosEjecutivos.tsx.
- frontend/src/modulos/bi/resumen/useResumenEjecutivo.ts.
- frontend/src/modulos/bi/resumen/resumen-ejecutivo.css.
- Colocated test files for schema, gateway, hook and every component above.
- tests/browser/bi-resumen-ejecutivo-react.spec.mjs.
- tests/browser/bi-resumen-ejecutivo-react.a11y.mjs.
- tests/browser/fixtures/bi-resumen-ejecutivo-react.mjs.

### Modify during implementation

- public/index.php — preserve routes; add no parallel overview endpoint.
- src/Controllers/Api/BiControlTowerApiController.php — delegate overview to parser/service/presenter.
- src/Controllers/Bi/BiViewController.php — sheet policy and SPA cut adapter.
- src/Services/ControlTowerService.php — compatibility delegation only; do not rewrite leaf reports.
- src/View/Components/BiAccessComponent.php — consume shared sheet/canvas manifest.
- frontend/src/App.tsx or frontend/src/shell/rutas.tsx — pilot and canonical S17 routes.
- frontend/src/shell/NavegacionLateral.tsx — consume server BI destination only; no raw role logic.
- frontend/src/lib/api/cliente.ts — only if T01 lacks AbortSignal/error metadata needed by the hook.
- frontend/src/main.tsx — import layered S17/T03 CSS if the module registry does not do it.
- public/css/tokens.css — only if a semantic token is truly absent, with its contract test.
- tests/browser/playwright.config.mjs or shared helpers — only to register the new safe suite.

### Explicitly preserve

- views/bi/_filters.php.
- views/bi/_layout.php.
- views/bi/_nav.php.
- views/bi/control-tower-piloto.php.
- views/bi/control-tower.php.
- views/bi/index.php.
- public/js/modules/bi-spa.js.
- public/css/bi-control-tower.css.
- public/css/bi-filter-drawer.css.
- ct-app/.
- Every S18–S24 endpoint.

## Task 1: Freeze the measured legacy and approved S17 contract

**Files:**

- Create: tests/fixtures/bi-overview-react/contracts.php
- Create: tests/fixtures/bi-overview-react/projects.php
- Create: tests/test_bi_overview_legacy_characterization.php
- Create: frontend/src/lib/api/esquemas/biResumenEjecutivo.test.ts
- Reference: views/bi/control-tower.php
- Reference: public/js/modules/bi-spa.js
- Reference: src/Services/ControlTowerService.php
- Reference: docs/superpowers/specs/2026-08-30-s17-bi-control-tower-react-design.md

### Step 1: Write the PHP characterization test

Use static fixture arrays only. Assert:

- the legacy scorecard order is Que hacer, Podemos, Se hara, Criticas atrasadas;
- the old view labels PPC/Programadas/Ejecutadas/Brecha are not the target contract;
- overview carries drivers, risks, actions and lineage;
- actions expose the complete existing server fields;
- the target fixture has one row per project and explicit completeness.

Representative assertion:

    $legacy = require __DIR__ . '/fixtures/bi-overview-react/contracts.php';
    assertSame('¿Qué hacer?', $legacy['scorecard'][0]['kpi']);
    assertSame('activities_to_do', $legacy['target']['scorecard'][0]['metricKey']);
    assertArrayHasKey('lineage', $legacy['target']);

### Step 2: Write the failing Zod contract tests

Cover:

- valid success/error union;
- null forecast with insufficient status;
- ratio requires numerator and denominator;
- forecast available requires all four dates/variance fields;
- exactly zero or one requiresIntervention;
- ranks are unique/contiguous;
- action project scope requires project identity;
- authority keys are stripped/rejected.

Run:

    npm --prefix frontend test -- biResumenEjecutivo.test.ts

Expected: FAIL because the schema does not exist.

### Step 3: Run PHP characterization

    docker compose exec app php tests/test_bi_overview_legacy_characterization.php

Expected: PASS; it documents the measured input and approved target without touching a database.

### Step 4: Record the mismatch, not a compatibility promise

Add comments to the fixture explaining why positional labels and duplicated PAC/PPC charts must not
survive. Do not modify production files.

### Step 5: Commit after the authorized implementation session

    git add tests/fixtures/bi-overview-react tests/test_bi_overview_legacy_characterization.php frontend/src/lib/api/esquemas/biResumenEjecutivo.test.ts
    git commit -m "test(bi): characterize executive overview contract"

**Vertical checkpoint:** target data contract is executable as failing tests; no route or runtime
behavior changed.

## Task 2: Build the first reusable T03 access, scope and query slice

**Files:**

- Create: src/Security/BiSheetAccessPolicy.php
- Create: src/Support/BiOverviewScopeResolver.php
- Create: src/Services/Bi/Http/BiQuery.php
- Create: src/Services/Bi/Http/BiQueryParser.php
- Create: tests/Support/Bi/FakeAuthorizedProjects.php
- Create: tests/test_bi_sheet_access_policy.php
- Create: tests/test_bi_overview_scope.php
- Create: tests/test_bi_query_contract.php
- Create: frontend/src/lib/api/esquemas/biComun.ts
- Create: frontend/src/modulos/bi/consultaBi.ts
- Create: frontend/src/modulos/bi/consultaBi.test.ts
- Modify: src/View/Components/BiAccessComponent.php

### Step 1: Write failing sheet-policy tests

Cases:

- A can open overview;
- D and R cannot open overview;
- D/R can still map to Intermedia;
- roles outside A/D/R have no BI canvas;
- aliases normalize before policy;
- policy is independent from client role input.

Use the approved manifest:

    private const MANAGEMENT = ['overview', 'programa-general', 'curva-s', 'cic'];
    private const SITE = ['intermedia', 'programa-general', 'semanal', 'curva-s', 'pdc', 'cic', 'cip'];

Do not implement a new RBAC capability.

### Step 2: Write failing overview-scope tests

Fake authorized projects A=[73,76,81]:

- no query resolves [73,76,81];
- query [76,73] resolves canonical [73,76];
- unauthorized 999 throws DomainException;
- empty authorization throws the existing no-project message;
- the ordinary BiProjectScope default remains untouched.

### Step 3: Write failing query parser tests

Cover:

- repeated and CSV project_ids compatibility;
- project_id alias;
- canonical sorted/deduplicated IDs;
- single scope accepts semana;
- multi scope publishes range;
- ISO dates and desde<=hasta;
- sub/resp/etapa trim and limits;
- legacy aliases normalize once;
- reject role, permiso, db, prefix, usuario and capability;
- preserve a typed focus enum only.

### Step 4: Run the focused red set

    docker compose exec app php tests/test_bi_sheet_access_policy.php
    docker compose exec app php tests/test_bi_overview_scope.php
    docker compose exec app php tests/test_bi_query_contract.php
    npm --prefix frontend test -- consultaBi.test.ts

Expected: FAIL because policy/parser/resolver/codec do not exist.

### Step 5: Implement the smallest pure policy and query types

BiSheetAccessPolicy receives a normalized trusted role and report key. BiOverviewScopeResolver
receives an authorization port, never Database directly. BiQueryParser returns a readonly value
object. The TypeScript codec mirrors query syntax but does not authorize.

Representative interface:

    final readonly class BiQuery
    {
        public function __construct(
            public array $projectIds,
            public ?int $week,
            public ?string $from,
            public ?string $to,
            public string $subcontractor,
            public string $responsible,
            public string $stage,
            public ?string $focus,
        ) {}
    }

### Step 6: Make BiAccessComponent consume the manifest

- default A overview;
- D/R intermedia;
- render links only for allowed sheets;
- keep global gate and active-project project access;
- do not add client-side role branching.

### Step 7: Run green

    docker compose exec app php tests/test_bi_sheet_access_policy.php
    docker compose exec app php tests/test_bi_overview_scope.php
    docker compose exec app php tests/test_bi_query_contract.php
    npm --prefix frontend test -- consultaBi.test.ts

Expected: PASS, zero database connection.

### Step 8: Commit

    git add src/Security/BiSheetAccessPolicy.php src/Support/BiOverviewScopeResolver.php src/Services/Bi/Http tests/Support/Bi tests/test_bi_sheet_access_policy.php tests/test_bi_overview_scope.php tests/test_bi_query_contract.php src/View/Components/BiAccessComponent.php frontend/src/lib/api/esquemas/biComun.ts frontend/src/modulos/bi/consultaBi.ts frontend/src/modulos/bi/consultaBi.test.ts
    git commit -m "feat(bi): define shared sheet scope and query contract"

**Vertical checkpoint:** access, portfolio default and URL grammar are decided and tested; no BI
data endpoint changed.

## Task 3: Build the project snapshot, priority and headline domain

**Files:**

- Create: src/Services/Bi/Overview/BiOverviewProjectReader.php
- Create: src/Services/Bi/Overview/BiOverviewMetric.php
- Create: src/Services/Bi/Overview/BiOverviewPriority.php
- Create: src/Services/Bi/Overview/BiOverviewHeadline.php
- Create: src/Services/Bi/Overview/BiOverviewActionTarget.php
- Create: src/Services/Bi/Overview/BiOverviewService.php
- Create: tests/Support/Bi/FakeBiOverviewProjectReader.php
- Create: tests/test_bi_overview_priority.php
- Create: tests/test_bi_overview_headline.php
- Create: tests/test_bi_overview_service.php

### Step 1: Write the failing priority tests

Create permutations of the same projects and assert identical result:

1. projects with orphanCount>0 before the rest;
2. greater orphanCount first;
3. then greater positive varianceDays;
4. then worsening trend;
5. then number of adverse signals;
6. then normalized projectName;
7. unknown does not outrank a known adverse value;
8. at most one requiresIntervention;
9. no adverse signal means zero highlighted rows;
10. reasons match the winning comparisons.

Do not assert a numeric score because none may exist.

### Step 2: Write the failing headline tests

Cover template keys:

- insufficient_portfolio;
- portfolio_stable;
- project_orphans;
- project_finish_variance;
- project_worsening;
- deterministic tie.

Assert text, facts, confidence, cutoff and priorityProjectId. Use fixed dates.

### Step 3: Write the failing service tests

Fake reader returns:

- three authorized projects;
- one complete;
- one partial with missing forecast;
- one insufficient;
- different cutoffs;
- exact numerator/denominator;
- risks/actions/lineage with project identity.

Assert:

- all three remain;
- ratios are not averaged;
- no missing value becomes zero;
- project cutoffs remain distinct;
- forecast union is consistent;
- target href/query retains project and period;
- scorecard uses keys, never positions;
- actions without a project are explicitly consolidated;
- lineage keys cover every visible metric.

### Step 4: Run red

    docker compose exec app php tests/test_bi_overview_priority.php
    docker compose exec app php tests/test_bi_overview_headline.php
    docker compose exec app php tests/test_bi_overview_service.php

Expected: FAIL because the overview domain does not exist.

### Step 5: Implement immutable metric states

Represent completeness explicitly:

    final readonly class BiOverviewMetric
    {
        public function __construct(
            public string $metricKey,
            public int|float|null $value,
            public string $unit,
            public string $completeness,
            public ?int $numerator = null,
            public ?int $denominator = null,
        ) {}
    }

Validate invariants at construction. Do not silently clamp malformed metrics.

### Step 6: Implement priority, headline and action targets

- priority is a pure stable comparator;
- headline consumes ordered rows and emits a finite template result;
- action target maps actionType/metricKey to S18–S24 route and typed focus;
- URL generation uses the shared query codec.

### Step 7: Implement orchestration

BiOverviewService:

1. reads each selected project through the port;
2. preserves original authorization set;
3. catches a project-level read failure into meta.partialFailures;
4. creates an insufficient row for that project;
5. orders rows;
6. creates headline;
7. aggregates legitimate counts and ratio-of-sums only;
8. enriches actions;
9. verifies lineage coverage.

It does not query Database and does not know HTTP.

### Step 8: Run green

    docker compose exec app php tests/test_bi_overview_priority.php
    docker compose exec app php tests/test_bi_overview_headline.php
    docker compose exec app php tests/test_bi_overview_service.php

Expected: PASS.

### Step 9: Commit

    git add src/Services/Bi/Overview tests/Support/Bi/FakeBiOverviewProjectReader.php tests/test_bi_overview_priority.php tests/test_bi_overview_headline.php tests/test_bi_overview_service.php
    git commit -m "feat(bi): model executive portfolio decision"

**Vertical checkpoint:** pure server domain can answer which authorized project needs intervention,
without HTTP, React or database writes.

## Task 4: Stabilize GET /api/bi/control-tower and its PHP/Zod contract

**Files:**

- Create: src/Services/Bi/Overview/PdoBiOverviewProjectReader.php
- Create: src/Services/Bi/Overview/BiOverviewPresenter.php
- Create: tests/test_api_bi_overview_contract.php
- Create: tests/test_bi_overview_source_invariants.php
- Create: tests/test_bi_overview_routes.php
- Implement: frontend/src/lib/api/esquemas/biResumenEjecutivo.ts
- Modify: src/Controllers/Api/BiControlTowerApiController.php
- Modify: src/Services/ControlTowerService.php
- Modify: public/index.php only if route wiring requires dependency construction

### Step 1: Write the failing pure controller/presenter contract test

Instantiate presenter/service with fakes and capture the body. Cover:

- 200 canonical ok envelope;
- temporary respuesta/project_id compatibility;
- 422 invalid query;
- 403 unauthorized project;
- 404 sheet hidden;
- 500 stable retryable error;
- no db/prefix/role authority fields;
- denied response has no project names or lineage;
- canonical dates/numbers/enums.

Do not instantiate Database.

### Step 2: Write failing source-invariant tests

Static assertions:

- production reader uses a MultiProjectScope/queryForProjects boundary;
- every project-sensitive query includes project_id;
- no SQL concatenates raw query values;
- no INSERT/UPDATE/DELETE/DDL token occurs in overview reader/controller;
- controller delegates and does not manually reimplement metrics;
- frontend gateway is the only S17 caller of cliente.ts and contains no fetch.

### Step 3: Run red

    docker compose exec app php tests/test_api_bi_overview_contract.php
    docker compose exec app php tests/test_bi_overview_source_invariants.php
    docker compose exec app php tests/test_bi_overview_routes.php
    npm --prefix frontend test -- biResumenEjecutivo.test.ts

Expected: FAIL on missing reader/presenter/schema/delegation.

### Step 4: Implement the production reader as an adapter

Use existing sources rather than new SQL formulas:

- MetricExecutor/MetricDictionaryService for executable metrics;
- existing forecast adapter for pg_finish_variance_days_p50 while descriptive;
- current restrictions management columns for orphan count;
- current risk/action/lineage services;
- general_proyectos_procesos only for authorized project name/baseline already in scope;
- semanas_activas for cutoff and comparable trend cuts.

The adapter reads only. Do not alter SQL views or the metric catalog state. Where an existing
service cannot provide a project-specific result without broad refactor, inject a narrow adapter
and retain compatibility.

### Step 5: Implement presenter and controller delegation

Order:

1. BiPreviewAccessPolicy hidden gate;
2. require auth under existing convention;
3. BiSheetAccessPolicy overview;
4. parse query;
5. resolve authorized overview scope;
6. service;
7. presenter;
8. stable JSON/error mapping.

Keep GET /api/bi/control-tower. Do not create /v2.

### Step 6: Implement Zod from the fixture

Use discriminated unions and superRefine. Export types only as z.infer:

    export const respuestaResumenEjecutivoSchema = z.discriminatedUnion('ok', [
      resumenEjecutivoExitoSchema,
      errorApiBiSchema,
    ])

No manual interface duplicates the schema.

### Step 7: Keep legacy adapter

ControlTowerService::getBrief overview may delegate to the new service/presenter compatibility
shape while S18–S24 continue using the old path. Do not change their response shapes.

### Step 8: Run green

    docker compose exec app php tests/test_api_bi_overview_contract.php
    docker compose exec app php tests/test_bi_overview_source_invariants.php
    docker compose exec app php tests/test_bi_overview_routes.php
    npm --prefix frontend test -- biResumenEjecutivo.test.ts

Expected: PASS, no MySQL.

### Step 9: Commit

    git add src/Services/Bi/Overview/PdoBiOverviewProjectReader.php src/Services/Bi/Overview/BiOverviewPresenter.php src/Controllers/Api/BiControlTowerApiController.php src/Services/ControlTowerService.php public/index.php tests/test_api_bi_overview_contract.php tests/test_bi_overview_source_invariants.php tests/test_bi_overview_routes.php frontend/src/lib/api/esquemas/biResumenEjecutivo.ts frontend/src/lib/api/esquemas/biResumenEjecutivo.test.ts
    git commit -m "feat(bi): stabilize executive overview API"

**Vertical checkpoint:** authorized GET returns a strict coherent portfolio snapshot and errors,
with compatibility for legacy consumers and no writes.

## Task 5: Add the typed gateway and coherent loading state machine

**Files:**

- Create: frontend/src/lib/api/biResumenEjecutivo.ts
- Create: frontend/src/lib/api/biResumenEjecutivo.test.ts
- Create: frontend/src/modulos/bi/resumen/useResumenEjecutivo.ts
- Create: frontend/src/modulos/bi/resumen/useResumenEjecutivo.test.tsx
- Modify: frontend/src/lib/api/cliente.ts only if AbortSignal is missing

### Step 1: Write failing gateway tests

Mock cliente.ts, not global fetch. Assert:

- endpoint and canonical query;
- repeated project_ids;
- no authority keys;
- schema parse;
- endpoint/field named on malformed response;
- AbortSignal forwarded;
- server error metadata retained.

### Step 2: Write failing hook tests

State transitions:

- loading-context;
- loading-report to ready;
- partial;
- empty-scope;
- no-authorized-projects;
- empty-period;
- insufficient;
- query-error;
- offline;
- server-error;
- retry;
- refetch marked Actualizando;
- stale response ignored;
- abort on query change/unmount;
- one snapshot, no mixed sections.

Use deferred promises and fake timers; no network.

### Step 3: Run red

    npm --prefix frontend test -- biResumenEjecutivo.test.ts useResumenEjecutivo.test.tsx

Expected: FAIL because gateway/hook do not exist.

### Step 4: Implement gateway through cliente.ts

No fetch, axios or ad hoc JSON parse. Keep one function:

    export function obtenerResumenEjecutivo(
      consulta: ConsultaBi,
      signal?: AbortSignal,
    ): Promise<ResumenEjecutivo>

Unwrap ok only after Zod. Throw the canonical client error for ok=false.

### Step 5: Implement reducer/state machine

Keep requestId and queryKey. Replace the whole snapshot atomically. Retry repeats only GET. Do not
persist project/week outside URL/T03.

### Step 6: Run green and source search

    npm --prefix frontend test -- biResumenEjecutivo.test.ts useResumenEjecutivo.test.tsx
    rg -n "fetch\\(" frontend/src/modulos/bi frontend/src/lib/api/biResumenEjecutivo.ts

Expected: tests PASS; source search returns no S17/T03 fetch call.

### Step 7: Commit

    git add frontend/src/lib/api/biResumenEjecutivo.ts frontend/src/lib/api/biResumenEjecutivo.test.ts frontend/src/modulos/bi/resumen/useResumenEjecutivo.ts frontend/src/modulos/bi/resumen/useResumenEjecutivo.test.tsx frontend/src/lib/api/cliente.ts
    git commit -m "feat(frontend): load executive overview coherently"

**Vertical checkpoint:** React can safely load/retry/cancel the strict read-only snapshot without
rendering the final UI.

## Task 6: Render headline, portfolio and complete actions

**Files:**

- Create: frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx
- Create: frontend/src/modulos/bi/ResumenEjecutivoPagina.test.tsx
- Create: frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.tsx
- Create: frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.test.tsx
- Create: frontend/src/modulos/bi/resumen/PanoramaObras.tsx
- Create: frontend/src/modulos/bi/resumen/PanoramaObras.test.tsx
- Create: frontend/src/modulos/bi/resumen/FilaPanorama.tsx
- Create: frontend/src/modulos/bi/resumen/TarjetaPanorama.tsx
- Create: frontend/src/modulos/bi/resumen/AccionesRecomendadas.tsx
- Create: frontend/src/modulos/bi/resumen/AccionesRecomendadas.test.tsx
- Create: frontend/src/modulos/bi/EstadoBi.tsx
- Modify: frontend/src/App.tsx or frontend/src/shell/rutas.tsx for pilot route only

### Step 1: Write failing headline/page tests

Assert:

- one h1;
- visible authorized scope and period;
- headline text is rendered verbatim from server;
- facts/confidence/cutoff are available;
- priority project is linked;
- all state-machine branches have safe copy/action;
- partial keeps valid data and warning;
- no PAC/PPC charts or labels.

### Step 2: Write failing panorama tests

At desktop branch:

- semantic table/caption/headers;
- rows in server order;
- one textual Intervenir esta semana;
- orphan count/readiness denominator;
- forecast date/range/committed/variance words;
- trend text;
- insufficient row remains;
- unknown is not green;
- drilldown href preserves project/range/filters/focus.

At mobile branch:

- cards only;
- same facts and hrefs;
- no hidden semantic table in DOM.

Mock matchMedia or the established responsive hook; do not decide both branches in CSS.

### Step 3: Write failing actions tests

Assert action, project/scope, owner, due date, impact, evidence, status and href. Assert no button
named create/complete/save and no mutation callback.

### Step 4: Run red

    npm --prefix frontend test -- ResumenEjecutivoPagina.test.tsx EncabezadoEjecutivo.test.tsx PanoramaObras.test.tsx AccionesRecomendadas.test.tsx

Expected: FAIL because components do not exist.

### Step 5: Implement the smallest semantic composition

Components only format:

- Intl.NumberFormat es-CO;
- Intl.DateTimeFormat es-CO with original datetime attribute;
- server text/reasons;
- shared T03 links/query.

They do not sort, aggregate, calculate trend or choose priority.

### Step 6: Add the pilot route

Mount at /app/bi/control-tower or the T01 pilot namespace. Do not cut /bi/control-tower yet. The
pilot is not linked for non-authorized roles.

### Step 7: Run green

    npm --prefix frontend test -- ResumenEjecutivoPagina.test.tsx EncabezadoEjecutivo.test.tsx PanoramaObras.test.tsx AccionesRecomendadas.test.tsx

Expected: PASS.

### Step 8: Commit

    git add frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx frontend/src/modulos/bi/ResumenEjecutivoPagina.test.tsx frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.tsx frontend/src/modulos/bi/resumen/EncabezadoEjecutivo.test.tsx frontend/src/modulos/bi/resumen/PanoramaObras.tsx frontend/src/modulos/bi/resumen/PanoramaObras.test.tsx frontend/src/modulos/bi/resumen/FilaPanorama.tsx frontend/src/modulos/bi/resumen/TarjetaPanorama.tsx frontend/src/modulos/bi/resumen/AccionesRecomendadas.tsx frontend/src/modulos/bi/resumen/AccionesRecomendadas.test.tsx frontend/src/modulos/bi/EstadoBi.tsx frontend/src/App.tsx frontend/src/shell/rutas.tsx
    git commit -m "feat(frontend): render executive project panorama"

**Vertical checkpoint:** pilot answers the management decision with equivalent table/cards and
complete read-only actions.

## Task 7: Expose scorecard, drivers, risks and lineage as secondary evidence

**Files:**

- Create: frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.tsx
- Create: frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.test.tsx
- Create: frontend/src/modulos/bi/resumen/RiesgosEjecutivos.tsx
- Create: frontend/src/modulos/bi/resumen/RiesgosEjecutivos.test.tsx
- Create: frontend/src/modulos/bi/LinajeDrawer.tsx
- Create: frontend/src/modulos/bi/LinajeDrawer.test.tsx
- Modify: frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx
- Modify: frontend/src/modulos/bi/ResumenEjecutivoPagina.test.tsx

### Step 1: Write failing evidence tests

Assert:

- section starts collapsed or below the decision canvas;
- eight scorecard items selected by metricKey;
- exact labels, values, units and completeness;
- ratios include base;
- drivers show project/evidence/impact/action;
- healthy and insufficient driver states differ;
- risks show level, score, confidence, source and computed date;
- risk score never changes panorama order;
- every metric has Como se calcula.

### Step 2: Write failing lineage drawer tests

Cover:

- opens from keyboard;
- accessible name;
- definition/formula/source/tables/grain/cutoff/filters/version/limitations;
- focus moves in and returns;
- Escape and close button;
- body inert/scroll lock through shared primitive;
- no refetch when embedded lineage exists;
- typed fallback GET only when absent;
- stale fallback response cannot replace another metric.

### Step 3: Run red

    npm --prefix frontend test -- EvidenciaEjecutiva.test.tsx RiesgosEjecutivos.test.tsx LinajeDrawer.test.tsx ResumenEjecutivoPagina.test.tsx

Expected: FAIL.

### Step 4: Implement evidence as disclosure, not another dashboard

Use headings and lists/cards. Do not install charting. Keep metric keys in data attributes only for
test/debug hooks, not as visible labels unless useful.

### Step 5: Implement the shared T03 drawer

The component accepts a lineage object and optional fallback loader. Reuse it later in S18–S24.
Avoid window globals and raw innerHTML.

### Step 6: Prove no hidden payload remains

    npm --prefix frontend test -- EvidenciaEjecutiva.test.tsx RiesgosEjecutivos.test.tsx LinajeDrawer.test.tsx ResumenEjecutivoPagina.test.tsx

Expected: PASS; each scorecard/driver/risk/lineage fixture is asserted in visible or operable UI.

### Step 7: Commit

    git add frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.tsx frontend/src/modulos/bi/resumen/EvidenciaEjecutiva.test.tsx frontend/src/modulos/bi/resumen/RiesgosEjecutivos.tsx frontend/src/modulos/bi/resumen/RiesgosEjecutivos.test.tsx frontend/src/modulos/bi/LinajeDrawer.tsx frontend/src/modulos/bi/LinajeDrawer.test.tsx frontend/src/modulos/bi/ResumenEjecutivoPagina.tsx frontend/src/modulos/bi/ResumenEjecutivoPagina.test.tsx
    git commit -m "feat(frontend): expose executive evidence and lineage"

**Vertical checkpoint:** all server content required by S17 is visible and operable; nothing travels
unused.

## Task 8: Finish the T03 frame, responsive layout, themes and accessibility

**Files:**

- Create: frontend/src/modulos/bi/MarcoBi.tsx
- Create: frontend/src/modulos/bi/MarcoBi.test.tsx
- Create: frontend/src/modulos/bi/NavegacionBi.tsx
- Create: frontend/src/modulos/bi/NavegacionBi.test.tsx
- Create: frontend/src/modulos/bi/FiltrosBi.tsx
- Create: frontend/src/modulos/bi/FiltrosBi.test.tsx
- Create: frontend/src/modulos/bi/bi-marco.css
- Create: frontend/src/modulos/bi/resumen/resumen-ejecutivo.css
- Create: tests/design-system/bi-resumen-ejecutivo-tokens.test.mjs
- Modify: public/css/tokens.css only if proven necessary
- Modify: frontend/src/main.tsx if CSS registration is centralized there

### Step 1: Write failing frame/filter/nav tests

Assert:

- A sees management canvas nav;
- D/R fixture does not include overview;
- selected projects and period are URL-backed;
- multi-project uses date range and per-project cutoff summary;
- filter drawer focus/escape/apply/reset;
- changing filters creates one canonical navigation;
- aliases become canonical;
- no role switching in components.

### Step 2: Write failing responsive/theme source tests

Assert:

- module CSS contains no color literals;
- all custom properties resolve to tokens;
- no important/inline color;
- no legacy BI utility classes;
- no Chart.js/canvas;
- table/cards branch uses the responsive hook breakpoint 768;
- reduced-motion media rule exists;
- focus tokens are consumed.

### Step 3: Write failing accessibility component tests

Cover:

- h1/heading order;
- scope region;
- table caption/headers;
- cards headings;
- disclosures ARIA;
- keyboard filter/action/lineage flow;
- focus return;
- dates datetime;
- priority text;
- 44x44 target class/contract;
- announcement after requested reorder;
- no noisy live region on initial render.

### Step 4: Run red

    npm --prefix frontend test -- MarcoBi.test.tsx NavegacionBi.test.tsx FiltrosBi.test.tsx ResumenEjecutivoPagina.test.tsx
    node --test tests/design-system/bi-resumen-ejecutivo-tokens.test.mjs

Expected: FAIL.

### Step 5: Implement with canonical tokens

- shared BI surface/layout primitives;
- module layer imported after components, before utilities if the layering contract requires;
- severity rail only on one priority row/card;
- neutral incomplete states;
- no fixed page heights;
- container/grid reflow;
- cards/table exclusive mount.

Add a token only after rg proves no suitable semantic token exists. Extend its contract/catalog in
the same commit.

### Step 6: Run green, typecheck and build

    npm --prefix frontend test -- MarcoBi.test.tsx NavegacionBi.test.tsx FiltrosBi.test.tsx ResumenEjecutivoPagina.test.tsx
    node --test tests/design-system/bi-resumen-ejecutivo-tokens.test.mjs
    npm --prefix frontend run typecheck
    npm --prefix frontend run build

Expected: PASS.

### Step 7: Commit

    git add frontend/src/modulos/bi/MarcoBi.tsx frontend/src/modulos/bi/MarcoBi.test.tsx frontend/src/modulos/bi/NavegacionBi.tsx frontend/src/modulos/bi/NavegacionBi.test.tsx frontend/src/modulos/bi/FiltrosBi.tsx frontend/src/modulos/bi/FiltrosBi.test.tsx frontend/src/modulos/bi/bi-marco.css frontend/src/modulos/bi/resumen/resumen-ejecutivo.css frontend/src/main.tsx public/css/tokens.css tests/design-system/bi-resumen-ejecutivo-tokens.test.mjs
    git commit -m "feat(frontend): finish accessible BI executive frame"

**Vertical checkpoint:** pilot meets dark/light, five viewport, keyboard and token contracts in
component tests.

## Task 9: Prove the safe browser matrix and cut the canonical route

**Files:**

- Create: tests/browser/fixtures/bi-resumen-ejecutivo-react.mjs
- Create: tests/browser/bi-resumen-ejecutivo-react.spec.mjs
- Create: tests/browser/bi-resumen-ejecutivo-react.a11y.mjs
- Modify: src/Controllers/Bi/BiViewController.php
- Modify: frontend/src/App.tsx or frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/NavegacionLateral.tsx only through server manifest consumption
- Modify: route/SPA fallback configuration used by T01

### Step 1: Build a complete intercepted fixture

Before page.goto:

- intercept T01 session/context;
- intercept T03 BI context/projects/filters;
- intercept GET /api/bi/control-tower;
- intercept lineage fallback;
- abort and fail the test on every POST/PUT/PATCH/DELETE;
- abort unexpected external requests;
- collect requests and console errors.

Fixtures:

- A with three projects, mixed completeness;
- A healthy;
- A all insufficient;
- A partial failure;
- D/R hidden overview;
- unauthorized project;
- invalid range;
- offline/server error.

### Step 2: Write browser tests against pilot

Scenarios:

- default loads all authorized projects;
- subset/query reload;
- headline/panorama/actions/evidence/lineage;
- correct scorecard labels;
- no PAC/PPC chart;
- action drilldowns preserve scope/period;
- partial/insufficient/error/retry;
- dark/light;
- sidebar expanded/collapsed;
- 390x844, 480x900, 768x1024, 1180x820, 1440x900;
- 200 percent zoom;
- keyboard-only filter to action to lineage;
- reduced motion;
- no horizontal page overflow;
- zero mutation requests;
- clean console.

### Step 3: Write role/security browser tests

- A route 200;
- D/R overview 404 and nav omission;
- project 999 403 with no project names;
- denied response not cached into next authorized session;
- session expiration follows T01 behavior without showing stale data.

### Step 4: Run pilot browser suite

    npx playwright test tests/browser/bi-resumen-ejecutivo-react.spec.mjs tests/browser/bi-resumen-ejecutivo-react.a11y.mjs --workers=1

Expected: PASS without real BI API/data writes.

### Step 5: Cut /bi/control-tower

- route page to React host;
- keep GET endpoint;
- keep legacy renderer behind the narrow rollback branch;
- do not change S18–S24 routes;
- ensure server manifest omits overview for D/R;
- keep /app pilot redirect only if T01 requires it.

### Step 6: Re-run after cut

    npx playwright test tests/browser/bi-resumen-ejecutivo-react.spec.mjs tests/browser/bi-resumen-ejecutivo-react.a11y.mjs --workers=1
    npm --prefix frontend test
    npm --prefix frontend run typecheck
    npm --prefix frontend run build

Expected: PASS on canonical route.

### Step 7: Inspect served runtime only in an authorized implementation session

Check Docker services once, then use dev door:

- test.A on /bi/control-tower;
- test.D/test.R verify hidden overview and Intermedia landing;
- no login form;
- no credential logging;
- Network has GET only;
- Console clean;
- no database inspection or mutation.

Do not run if the dev door or safe interception conditions are absent.

### Step 8: Commit

    git add tests/browser/fixtures/bi-resumen-ejecutivo-react.mjs tests/browser/bi-resumen-ejecutivo-react.spec.mjs tests/browser/bi-resumen-ejecutivo-react.a11y.mjs src/Controllers/Bi/BiViewController.php frontend/src/App.tsx frontend/src/shell/rutas.tsx frontend/src/shell/NavegacionLateral.tsx
    git commit -m "feat(bi): cut executive overview to React"

**Vertical checkpoint:** canonical S17 is browser-proven for role, theme, viewport, errors,
accessibility and zero mutations.

## Task 10: Prove compatibility, rollback and the deferred retirement gate

**Files:**

- Modify: tests/test_bi_overview_routes.php
- Modify: tests/test_bi_overview_source_invariants.php
- Create: docs/qa/bi-resumen-ejecutivo-react.md
- Modify: docs/superpowers/plans/2026-08-30-s17-bi-control-tower-react.md — Cierre only during execution
- Reference only: views/bi/*, public/js/modules/bi-spa.js, public/css/bi-*.css, ct-app/

### Step 1: Add route/compatibility tests

Assert:

- canonical page uses SPA host;
- GET API retains canonical and temporary legacy fields;
- S18–S24 endpoints/shapes are untouched;
- no duplicate /api/bi/control-tower route;
- legacy aliases still reach the adapter;
- rollback branch renders old route only when deliberately selected.

### Step 2: Add zero-caller reports without deleting

Record current callers for:

    rg -n "views/bi/(_layout|_filters|_nav|control-tower|control-tower-piloto|index)\\.php|bi-spa\\.js|bi-control-tower\\.css|bi-filter-drawer\\.css|ct-app" public src views frontend tests

Expected now: callers remain because S18–S24 and S20 still use them. The test/document must say
retirement deferred, not fail S17 for expected shared callers.

### Step 3: Exercise rollback without writes

- switch route adapter to legacy in a test-only configuration;
- load A with intercepted/read-only requests;
- confirm legacy route;
- restore React route;
- rerun canonical test.

No data restoration step exists because S17 writes nothing.

### Step 4: Write QA handoff

Document:

- route and access matrix;
- query/default scope;
- fields and completeness;
- five viewports/two themes;
- intercepted fixture provenance;
- zero-mutation proof;
- legacy shared files retained and their owning future gate;
- rollback steps;
- explicit RLS/schema/data non-change.

### Step 5: Run the focused full S17 gate

    docker compose exec app php tests/test_bi_sheet_access_policy.php
    docker compose exec app php tests/test_bi_overview_scope.php
    docker compose exec app php tests/test_bi_query_contract.php
    docker compose exec app php tests/test_bi_overview_priority.php
    docker compose exec app php tests/test_bi_overview_headline.php
    docker compose exec app php tests/test_bi_overview_service.php
    docker compose exec app php tests/test_api_bi_overview_contract.php
    docker compose exec app php tests/test_bi_overview_source_invariants.php
    docker compose exec app php tests/test_bi_overview_routes.php
    npm --prefix frontend test
    npm --prefix frontend run typecheck
    npm --prefix frontend run build
    node --test tests/design-system/bi-resumen-ejecutivo-tokens.test.mjs
    npx playwright test tests/browser/bi-resumen-ejecutivo-react.spec.mjs tests/browser/bi-resumen-ejecutivo-react.a11y.mjs --workers=1

Read each return code separately. Do not pipe or chain publication behind verification.

### Step 6: Run source/scope checks

    rg -n "fetch\\(" frontend/src/modulos/bi frontend/src/lib/api/biResumenEjecutivo.ts
    rg -n "#[0-9a-fA-F]{3,8}|rgba?\\(|hsla?\\(|!important" frontend/src/modulos/bi
    rg -n "INSERT|UPDATE|DELETE|ALTER|CREATE TABLE|DROP|TRUNCATE" src/Services/Bi/Overview src/Controllers/Api/BiControlTowerApiController.php
    git diff --name-only -- database admin .env

Expected:

- no direct fetch;
- no literal colors/important;
- no mutations/DDL in S17 boundary;
- no database, admin or env changes.

### Step 7: Apply verification-before-completion

- inspect git diff and status;
- ensure unrelated user changes remain untouched;
- trace all 75 acceptance criteria below;
- record exact commands/outputs/limits in Cierre;
- do not claim shared BI legacy retired;
- do not commit/push until the implementation session is explicitly authorized and its publication
  gate is applicable.

### Step 8: Commit only in that future authorized session

    git add tests/test_bi_overview_routes.php tests/test_bi_overview_source_invariants.php docs/qa/bi-resumen-ejecutivo-react.md docs/superpowers/plans/2026-08-30-s17-bi-control-tower-react.md
    git commit -m "test(bi): close executive overview migration"

**Vertical checkpoint:** S17 is independently reversible and verified; T03 shared legacy retirement
remains explicitly pending S18–S24.

## Acceptance Traceability

Every criterion in the spec is assigned to an implementation task:

| Task | Criteria |
|---|---|
| 1 | S17-AC-39, S17-AC-40, S17-AC-42, S17-AC-44, S17-AC-55 |
| 2 | S17-AC-03, S17-AC-04, S17-AC-05, S17-AC-06, S17-AC-07, S17-AC-08, S17-AC-09, S17-AC-10, S17-AC-11, S17-AC-12, S17-AC-13, S17-AC-14, S17-AC-15, S17-AC-16, S17-AC-17 |
| 3 | S17-AC-21, S17-AC-22, S17-AC-23, S17-AC-24, S17-AC-25, S17-AC-26, S17-AC-27, S17-AC-28, S17-AC-29, S17-AC-30, S17-AC-31, S17-AC-32, S17-AC-33, S17-AC-34, S17-AC-35, S17-AC-36, S17-AC-37, S17-AC-38, S17-AC-43, S17-AC-45 |
| 4 | S17-AC-18, S17-AC-19, S17-AC-20, S17-AC-69, S17-AC-70, S17-AC-74 |
| 5 | S17-AC-56, S17-AC-57, S17-AC-58 |
| 6 | S17-AC-41, S17-AC-46, S17-AC-47, S17-AC-48, S17-AC-49, S17-AC-50, S17-AC-51 |
| 7 | S17-AC-52, S17-AC-53, S17-AC-54 |
| 8 | S17-AC-59, S17-AC-60, S17-AC-61, S17-AC-62, S17-AC-63, S17-AC-64, S17-AC-65, S17-AC-66, S17-AC-67, S17-AC-68 |
| 9 | S17-AC-01, S17-AC-02, S17-AC-71, S17-AC-75 |
| 10 | S17-AC-72, S17-AC-73 |

The union is S17-AC-01 through S17-AC-75 exactly once.

## Plan self-review

Before execution, verify:

- every created endpoint contract has both PHP and Zod tests;
- there is no new endpoint when the existing GET suffices;
- T03 ownership is reusable and not duplicated inside S17;
- sheet access follows the approved canvases;
- overview all-project default is isolated from other leaves;
- period/cutoff semantics never imply false comparability;
- completeness is explicit;
- priority has no invented score;
- narrative remains server-side/auditable;
- scorecard uses keys;
- drivers/risks/lineage are rendered;
- actions remain read-only;
- no direct fetch;
- no DB-mutating tests;
- no /admin/, RLS, schema, grant, user, credential or data changes;
- legacy shared retirement is deferred;
- rollback requires no data restore;
- all 75 criteria are traceable.

## Cierre

No ejecutado. Este documento es un plan de implementacion escrito y autorrevisado durante la
sesion documental de las 27 superficies. No se modifico codigo, no se ejecutaron pruebas mutantes,
no se tocaron datos/RLS/schema/grants/usuarios/credenciales, no se hizo commit, push, publicacion o
deploy. La implementacion requiere una sesion futura explicitamente autorizada que invoque
superpowers:executing-plans.
