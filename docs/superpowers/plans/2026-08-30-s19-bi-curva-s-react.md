# S19 BI Curva S React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Use superpowers:test-driven-development for every task and
> verification-before-completion before any completion claim. Execute tasks in order and stop at
> every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and closure live
> in Cierre and git history, never in checkbox counts.

**Goal:** migrate `/bi/curva-s` into the main React SPA as a read-only, decision-ready Curva S
sheet that preserves the current authorized project/query contracts, corrects the one-point week
behavior, shows plan/current/probable trajectory and uncertainty, evaluates the approved N6
replanning signal per project, and remains equivalent on desktop, tablet, mobile, dark and light
without changing RLS, schema or data.

**Architecture:** T01 remains owner of session, active project, shell, navigation, theme, route
outlet and the only HTTP client. T03, first delivered through S17, remains owner of BI access,
canonical query, shared sheet frame, filters, states, drawer and lineage. S18 owns the canonical
program progress and deterministic forecast seams. S19 composes those seams in a small
`BiCurveReadService`, adds pure curve-series and N6 policy objects, and serves one canonical
envelope from the existing GET route. PHP computes all figures, eligibility, decision state,
headline, actions and links; React validates with Zod and renders native SVG plus visible semantic
table/cards. A presenter preserves the old payload while legacy callers remain.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native SVG and the existing AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s19-bi-curva-s-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout
  or another worktree.
- Inspect `git status --short` and the relevant diff before every task. Preserve unrelated and
  pre-existing work. Never clean, revert or reformat adjacent files.
- This planning session is documentation-only. Do not implement, install, commit, push, publish or
  deploy now. Commit commands below are future instructions and do not authorize implementation.
- Implement S19 only after T01, the T03 primitives delivered through S17, and the S18 program
  progress/forecast seams exist and are green. Reuse them; do not build a second shell, query
  parser, access policy, progress calculator, forecast engine, responsive frame or drawer.
- `/admin/` is excluded. Do not edit its controllers, views, routes, permissions, flags UI or
  tests.
- Do not modify RLS, the runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, keys, triggers, grants, users, credentials, memberships, roles, aliases, overrides or
  data.
- No DDL or DML is permitted. New PHP tests use pure services, deterministic fakes, call logs,
  source/reflection invariants and static fixtures. Browser tests install complete interception
  before navigation and fail on any mutation.
- Do not run existing BI fixture suites that write and roll back MySQL as evidence for this plan.
  A rollback write is still DML.
- Preserve `BiPreviewAccessPolicy`: A is permitted; D/R follow the current global gate; a hidden
  sheet returns 404. Do not edit the gate.
- Preserve `lps.indicadores.ver` per project and `BiProjectScope` authorization. Client
  `project_ids`, `project_id`, `role`, `permiso`, `db`, `prefix`, `username` or
  capability-like keys never grant authority.
- The active authorized project is the default. Multi-project scope requires an explicit selection
  and retains one cutoff, coverage record, curve contribution and N6 state per project.
- Preserve T03 query fields `semana`, `desde`, `hasta`, `sub_contratista`,
  `responsable` and `etapa`. Range replaces week only in the compatibility adapter.
- A week is a terminal cutoff selector, not a one-row filter. The canonical response includes all
  comparable history through that cutoff.
- A range returns its visible points plus one earlier context point when one exists. The context
  point is identified and is not counted as part of the visible range.
- Filters define the cohort and denominator. The UI must state `Alcance filtrado` beside current
  figures and never imply that a filtered curve represents the whole project.
- Plan and real use valid `Titulo=0` activities, inclusive calendar duration and
  `project_id + unique_id` snapshot joins. Zero denominator is `insufficient` with null values,
  never a fabricated zero.
- The theoretical series is labelled `Plan vigente`; it is not the contractual baseline.
- Plan and real are cumulative, bounded to 0–100, non-decreasing and reconciled to the current
  figures. A valid zero remains distinguishable from missing data.
- Multi-project aggregates weighted numerators and denominators. Never average project percentages.
  Keep the project breakdown and each project's own cutoff.
- Reuse the S18 deterministic forecast contract: 240 simulations, at least three positive
  increments, declared contractual baseline only and `P10 <= P50 <= P90`.
- Forecast output contains probable series and lower/upper uncertainty bounds from the cutoff
  onward. It never rewrites observed points. Real values after cutoff are null.
- In multi-project mode, do not publish a consolidated uncertainty band unless every selected
  project has a comparable forecast. Per-project forecasts and limitations remain visible.
- N6 is evaluated per project, as of each cutoff: probable contractual finish at least 30 calendar
  days after declared contractual finish in two consecutive comparable cuts. One cut is
  `watch`; missing baseline/history/comparability is `insufficient`.
- A recommended N6 state uses template N1, not a red alarm, and visibly cites both qualifying
  cutoffs. No client-side threshold or decision inference is allowed.
- The server authors headline, decision, actions, evidence and authorized hrefs. The CTA to S06
  appears only when the server-resolved capability permits it. S19 remains read-only and never
  calls the action queue or a mutation.
- No named intermediate milestone source exists in scope. Show contractual start/finish, cutoff,
  current-plan finish and forecast range; say that no intermediate milestones were declared.
  Never reinterpret `Titulo=1` headings as milestones.
- Point detail is carried in the main snapshot and opens without another request.
- The existing `GET /api/bi/report/curva-s` is the only data route. Do not introduce a parallel
  endpoint.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. The S19 gateway uses its typed
  request primitive.
- Every response is parsed by Zod before components see it. Types come from `z.infer`, never a
  manually duplicated interface.
- Use native SVG with `title` and `desc`, plus a visible semantic table/cards containing every
  point and describing the uncertainty band. Do not add a chart library.
- At widths below 768 mount cards only; at 768 and above mount the semantic table only. Do not
  mount both and hide one only with CSS.
- Use `public/css/tokens.css`. No hex/rgb/hsl literals, inline colors, `!important`, local theme
  forks or new UI/state/chart libraries.
- Dark remains default/fallback; light has identical capabilities. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Keep shared legacy BI views, JavaScript, CSS and Chart.js through the T03 retirement gate after
  S17–S24. S19 may cut only its page route. Rollback is route/code only and never restores data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read the S17 and S18 close records.
2. Verify that these T03 contracts exist and are green:
   `BiSheetAccessPolicy`, `BiQueryParser`, `MarcoBi`, `FiltrosBi`, `LinajeDrawer`,
   `EstadoBi` and the shared responsive detail drawer.
3. Verify that S18 provides one canonical project-scoped progress seam and one deterministic
   forecast seam with declared-baseline metadata. If either seam is absent, stop and implement its
   owning plan; do not copy it into S19.
4. Verify branch, status and runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: the exact worktree and branch above; services `app`, `db`, `adminer`; no
   unexpected worktree changes; the mounted `app` is healthy before PHP/browser checks.
5. Record the starting SHA and pre-existing diff. Do not stage either.

## File Structure

### Create — backend curve domain

- `src/Services/Bi/Curve/BiCurveSeriesBuilder.php` — inclusive weighted plan/real points,
  reconciliation, context-point marking and project breakdown.
- `src/Services/Bi/Curve/BiCurveForecastProjector.php` — adapter from the S18 forecast seam to
  probable/lower/upper curve points and key dates.
- `src/Services/Bi/Curve/BiCurveReplanningPolicy.php` — pure N6 evaluation per project and cutoff.
- `src/Services/Bi/Curve/BiCurveHeadline.php` — server-authored decision text, evidence and
  authorized action links.
- `src/Services/Bi/Curve/BiCurveReadService.php` — coherent project/range/filter orchestration.
- `src/Services/Bi/Curve/BiCurvePresenter.php` — canonical envelope plus legacy compatibility
  adapter.

### Create — PHP tests and static fixtures

- `tests/Support/Bi/FakeBiCurveProgramReader.php`.
- `tests/Support/Bi/FakeBiCurveForecast.php`.
- `tests/fixtures/bi-curva-s-react/week-history.php`.
- `tests/fixtures/bi-curva-s-react/range-context.php`.
- `tests/fixtures/bi-curva-s-react/multi-project.php`.
- `tests/fixtures/bi-curva-s-react/legacy-response.php`.
- `tests/test_bi_curve_query.php`.
- `tests/test_bi_curve_contract_characterization.php`.
- `tests/test_bi_curve_series.php`.
- `tests/test_bi_curve_forecast.php`.
- `tests/test_bi_curve_replanning.php`.
- `tests/test_bi_curve_headline.php`.
- `tests/test_api_bi_curve_contract.php`.
- `tests/test_bi_curve_source_invariants.php`.
- `tests/test_bi_curve_routes.php`.

### Create — frontend API

- `frontend/src/lib/api/esquemas/biCurvaS.ts`.
- `frontend/src/lib/api/esquemas/biCurvaS.test.ts`.
- `frontend/src/lib/api/biCurvaS.ts`.
- `frontend/src/lib/api/biCurvaS.test.ts`.

### Create — frontend sheet

- `frontend/src/modulos/bi/CurvaSPagina.tsx`.
- `frontend/src/modulos/bi/CurvaSPagina.test.tsx`.
- `frontend/src/modulos/bi/curva/TitularCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/ResumenCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/GraficoCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/FechasClaveCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/TendenciaCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/DesgloseProyectosCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/PuntosCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/DetalleCorteCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/AccionesCurvaS.tsx`.
- `frontend/src/modulos/bi/curva/useCurvaS.ts`.
- `frontend/src/modulos/bi/curva/curva-s.css`.
- Colocated focused tests for every interactive component and hook.
- `tests/browser/fixtures/bi-curva-s-react.mjs`.
- `tests/browser/bi-curva-s-react.spec.mjs`.
- `tests/browser/bi-curva-s-react.a11y.mjs`.

### Modify during implementation

- `public/index.php` — preserve the existing API GET and cut only the page route at Task 9.
- `src/Controllers/Api/BiControlTowerApiController.php` — thin delegation to T03 query/access,
  S19 read service and presenter.
- `src/Controllers/Bi/BiViewController.php` — preserve hidden-sheet behavior and page cut.
- `src/Services/ControlTowerService.php` — delegate the curve seam while preserving legacy
  callers; do not broaden its responsibilities.
- `src/Services/Bi/StorytellingService.php` and
  `src/Services/Bi/ActionRecommendationService.php` — delegate to the canonical last-point
  headline only if characterization proves they are still direct curve callers.
- `src/Services/Bi/LineageService.php` and `MetricDictionaryService.php` — canonical curve keys
  and accurate execution-source descriptions only.
- `frontend/src/App.tsx` or `frontend/src/shell/rutas.tsx` — shadow route, then canonical S19
  route.
- `frontend/src/main.tsx` — stylesheet registration only if the module registry does not own it.
- `frontend/src/lib/api/cliente.ts` — only if T01 still lacks AbortSignal or canonical error
  metadata.
- `public/css/tokens.css` — only for a truly absent semantic token, with its contract test.
- Browser config/helpers — only to register fully intercepted safe suites.

### Explicitly preserve

- `views/bi/_filters.php`.
- `views/bi/_layout.php`.
- `views/bi/_nav.php`.
- `views/bi/curva-s.php`.
- `public/js/modules/bi-spa.js`.
- `public/css/bi-control-tower.css`.
- `public/css/bi-filter-drawer.css`.
- Shared Chart.js assets and all other BI sheets.

## Task 1: Lock access, project scope and terminal-period semantics

**Acceptance:** S19-AC-03 through S19-AC-14, and S19-AC-17.

**Files:**

- Create: `tests/test_bi_curve_query.php`
- Modify: T03 `BiQueryParser` only if the shared canonical contract lacks a required primitive
- Modify later: `src/Controllers/Api/BiControlTowerApiController.php`

**Step 1: Write failing pure query/access tests**

Cover:

- permitted A and gate-permitted D/R with `lps.indicadores.ver`;
- hidden-module 404 and unauthorized-project 403 without payload leakage;
- active authorized project default;
- explicit multi-project selection and rejection of unauthorized members;
- rejection of authority-like keys;
- preservation and canonicalization of week/range/subcontractor/responsible/stage;
- week as terminal cutoff with all comparable history through it;
- range as visible points plus one marked earlier context point;
- distinct project cutoff dates even when week labels coincide;
- shared sheet contract with no audience-specific payload shape.

The test must drive pure policy/query collaborators and fakes. It must not boot or write MySQL.

**Step 2: Run the focused test and prove RED**

       docker compose exec app php tests/test_bi_curve_query.php

Expected: non-zero because terminal history/context behavior or the S19 service boundary does not
exist. A syntax or fixture-path failure is not an acceptable RED.

**Step 3: Introduce the smallest canonical period value**

Reuse the T03 parser. Add only the missing representation needed to carry:

- normalized scope;
- terminal cutoff;
- visible range;
- optional earlier context marker;
- canonical cohort filters.

Do not place authorization in query DTOs and do not accept client authority.

**Step 4: Make the focused test GREEN**

       docker compose exec app php tests/test_bi_curve_query.php

Expected: zero, with every branch above asserted.

**Step 5: Run source invariants**

       rg -n "project_id|project_ids|semana|desde|hasta|sub_contratista|responsable|etapa" \
         src/Controllers/Api/BiControlTowerApiController.php src/Services/Bi

Review manually that scope is server-authorized, prepared/read-only boundaries are retained and no
dynamic table/schema selection was introduced.

**Step 6: Future implementation commit**

       git add tests/test_bi_curve_query.php src/Services/Bi
       git commit -m "test(bi): lock curva s scope and period"

Do not run this commit in the planning session.

## Task 2: Characterize the existing GET and freeze compatibility

**Acceptance:** S19-AC-15, S19-AC-16 and S19-AC-80.

**Files:**

- Create: `tests/fixtures/bi-curva-s-react/legacy-response.php`
- Create: `tests/test_bi_curve_contract_characterization.php`
- Create: `tests/test_bi_curve_source_invariants.php`
- Modify later: `src/Services/Bi/Curve/BiCurvePresenter.php`
- Modify later: `src/Controllers/Api/BiControlTowerApiController.php`

**Step 1: Freeze the measured old payload**

Encode the currently observed response shape as a static fixture, including:

- top-level `respuesta`, project scope, week/report/role/filter metadata;
- `data_source`, raw row count and current null placeholders;
- executive brief, scorecard, charts, drivers, risks, actions and lineage;
- `chart-curva-s` labels and exactly the old theoretical/real dataset structure.

Do not copy secrets or database-derived personal data into the fixture.

**Step 2: Write a failing characterization test**

The test proves:

- `GET /api/bi/report/curva-s` remains the only endpoint;
- the canonical presenter can produce the new envelope;
- the compatibility presenter reproduces the frozen legacy keys until zero callers;
- current legacy semantics are described, not silently mistaken for target semantics.

**Step 3: Prove RED**

       docker compose exec app php tests/test_bi_curve_contract_characterization.php

Expected: non-zero because `BiCurvePresenter` does not exist.

**Step 4: Add the presenter seam**

Create a presenter with separate explicit methods for canonical and compatibility output. Never
infer target semantics from the old two-series chart array. Keep the controller thin.

**Step 5: Prove GREEN and endpoint uniqueness**

       docker compose exec app php tests/test_bi_curve_contract_characterization.php
       docker compose exec app php tests/test_bi_curve_source_invariants.php
       rg -n "curva-s" public/index.php src tests

Expected: both tests zero and exactly one production API route.

**Step 6: Future implementation commit**

       git add src/Services/Bi/Curve tests/fixtures/bi-curva-s-react \
         tests/test_bi_curve_contract_characterization.php tests/test_bi_curve_source_invariants.php
       git commit -m "test(bi): characterize curva s contract"

Do not run this commit in the planning session.

## Task 3: Build the inclusive weighted base curve

**Acceptance:** S19-AC-18 through S19-AC-30.

**Files:**

- Create: `src/Services/Bi/Curve/BiCurveSeriesBuilder.php`
- Create: `tests/Support/Bi/FakeBiCurveProgramReader.php`
- Create: `tests/fixtures/bi-curva-s-react/week-history.php`
- Create: `tests/fixtures/bi-curva-s-react/range-context.php`
- Create: `tests/fixtures/bi-curva-s-react/multi-project.php`
- Create: `tests/test_bi_curve_series.php`

**Step 1: Write failing formula tests**

Build small deterministic activity/snapshot fixtures that prove:

- only `Titulo=0` rows with valid start, finish and positive inclusive duration enter;
- duration is `finish - start + 1` calendar days;
- planned numerator is elapsed eligible duration as of cutoff;
- real numerator is clamped executed fraction times eligible duration;
- snapshot joins use `project_id + unique_id`;
- zero total duration returns `status=insufficient` and null figures;
- plan/real points are ISO dated, sorted, unique, cumulative, bounded and non-decreasing;
- current figures equal the last visible point;
- gap is `real - plan`;
- trend is current gap minus the immediately preceding comparable gap;
- multi-project results aggregate numerators/denominators rather than percentages;
- project breakdown reconciles exactly to aggregate values;
- a valid numeric zero remains present, not null.

**Step 2: Prove RED**

       docker compose exec app php tests/test_bi_curve_series.php

Expected: non-zero because the pure series builder is absent.

**Step 3: Implement the pure builder**

Keep date arithmetic, eligibility, numerator/denominator accumulation and rounding boundaries in
named value objects or small private functions. Preserve raw numerators in the result so the
presenter never re-aggregates rounded percentages.

**Step 4: Make every formula test GREEN**

       docker compose exec app php tests/test_bi_curve_series.php

Expected: zero.

**Step 5: Add mutation-resistant boundary cases**

Add fixtures for:

- cutoff before activity start and after activity finish;
- executed values below zero and above 100;
- duplicated snapshots;
- same `unique_id` in two projects;
- identical week labels with different ISO cutoffs;
- filtered cohort with a smaller denominator.

Run the test again and require zero.

**Step 6: Future implementation commit**

       git add src/Services/Bi/Curve/BiCurveSeriesBuilder.php tests/Support/Bi \
         tests/fixtures/bi-curva-s-react tests/test_bi_curve_series.php
       git commit -m "feat(bi): build canonical curva s series"

Do not run this commit in the planning session.

## Task 4: Project probable finish, uncertainty and key dates

**Acceptance:** S19-AC-31 through S19-AC-43, and S19-AC-86.

**Files:**

- Create: `src/Services/Bi/Curve/BiCurveForecastProjector.php`
- Create: `tests/Support/Bi/FakeBiCurveForecast.php`
- Create: `tests/test_bi_curve_forecast.php`
- Modify: S18 forecast port only if it lacks a read-only adapter interface

**Step 1: Write failing forecast-projection tests**

Prove:

- output series are plan, real, probable, lower and upper;
- observed real becomes null after cutoff;
- forecast and band begin at cutoff and never alter earlier observations;
- exactly 240 deterministic simulations are requested from the S18 seam;
- fewer than three positive increments produces `insufficient`;
- lower <= median <= upper for each projected point;
- declared forecast dates satisfy P10 <= P50 <= P90;
- contractual finish is sourced only from a declared contractual baseline;
- missing baseline yields null variance and prevents N6 eligibility;
- key dates distinguish contractual start/finish, cutoff, current-plan finish and forecast range;
- `Titulo=1` rows cannot become milestones;
- missing milestone source produces the explicit no-intermediate-milestones message;
- a multi-project aggregate band is omitted when any project forecast is non-comparable.

**Step 2: Prove RED**

       docker compose exec app php tests/test_bi_curve_forecast.php

Expected: non-zero because the projection adapter does not exist.

**Step 3: Implement a thin S18 adapter**

Do not copy simulation math. Convert S18 outcomes into curve points, preserving:

- seed/context metadata;
- cutoff and baseline identifiers;
- sample/increment sufficiency;
- lower/median/upper ordering;
- explicit limitations.

Interpolation for display must be deterministic, monotone and independently tested. It may not
change terminal forecast dates.

**Step 4: Make tests GREEN**

       docker compose exec app php tests/test_bi_curve_forecast.php

Expected: zero.

**Step 5: Verify no duplicate forecast engine**

       rg -n "240|P10|P50|P90|simulation|simul" src/Services/Bi

Review that S19 calls the S18 engine and does not add a second simulation implementation.

**Step 6: Future implementation commit**

       git add src/Services/Bi/Curve/BiCurveForecastProjector.php tests/Support/Bi \
         tests/test_bi_curve_forecast.php
       git commit -m "feat(bi): project curva s forecast band"

Do not run this commit in the planning session.

## Task 5: Evaluate N6 and author the server decision

**Acceptance:** S19-AC-44 through S19-AC-53, and S19-AC-87.

**Files:**

- Create: `src/Services/Bi/Curve/BiCurveReplanningPolicy.php`
- Create: `src/Services/Bi/Curve/BiCurveHeadline.php`
- Create: `tests/test_bi_curve_replanning.php`
- Create: `tests/test_bi_curve_headline.php`

**Step 1: Write the N6 decision table as failing tests**

Cover exact boundaries:

| Current comparable cut | Previous comparable cut | Result |
|---|---|---|
| <30 days | any | not_recommended |
| exactly 30 days | <30 or absent | watch |
| >30 days | <30 or absent | watch |
| >=30 days | >=30 days | recommended |
| missing baseline/history/comparability | any | insufficient |

Repeat the table with two selected projects to prove independent per-project evaluation and no
aggregate shortcut.

**Step 2: Write failing headline/action tests**

Prove:

- the headline says probable finish date, range and calendar gap in words, never `P50`;
- `recommended` uses the N1 meeting template and cites both qualifying cutoffs with dates/gaps;
- `watch` is not a recommendation and is not red-alarm copy;
- server-resolved capabilities govern the S06 CTA;
- S18/S05 links preserve canonical scope, period and filters;
- no action queue identifier or mutation instruction appears;
- decision, evidence, actions and hrefs are server-authored.

**Step 3: Prove RED**

       docker compose exec app php tests/test_bi_curve_replanning.php
       docker compose exec app php tests/test_bi_curve_headline.php

Expected: both non-zero because the policy/headline objects are absent.

**Step 4: Implement pure policy and headline**

Evaluate each cutoff as-of that cutoff. Do not use future history when classifying an earlier
cutoff. Keep calendar-day calculation explicit and retain both evidence rows in the canonical
envelope.

**Step 5: Make tests GREEN**

       docker compose exec app php tests/test_bi_curve_replanning.php
       docker compose exec app php tests/test_bi_curve_headline.php

Expected: zero.

**Step 6: Search for stale minus-five logic**

       rg -n -- "-5|replan|reprogram|replanning" src views public/js frontend/src

Any remaining legacy threshold may stay only behind compatibility and must not drive canonical
S19 output. Record each caller before changing it.

**Step 7: Future implementation commit**

       git add src/Services/Bi/Curve/BiCurveReplanningPolicy.php \
         src/Services/Bi/Curve/BiCurveHeadline.php tests/test_bi_curve_replanning.php \
         tests/test_bi_curve_headline.php
       git commit -m "feat(bi): evaluate curva s replanning signal"

Do not run this commit in the planning session.

## Task 6: Serve the canonical envelope, Zod schema and gateway

**Acceptance:** S19-AC-72 through S19-AC-79.

**Files:**

- Create: `src/Services/Bi/Curve/BiCurveReadService.php`
- Create: `src/Services/Bi/Curve/BiCurvePresenter.php`
- Create: `tests/test_api_bi_curve_contract.php`
- Create: `frontend/src/lib/api/esquemas/biCurvaS.ts`
- Create: `frontend/src/lib/api/esquemas/biCurvaS.test.ts`
- Create: `frontend/src/lib/api/biCurvaS.ts`
- Create: `frontend/src/lib/api/biCurvaS.test.ts`
- Modify: `src/Controllers/Api/BiControlTowerApiController.php`
- Modify: `src/Services/Bi/LineageService.php`
- Modify: `src/Services/Bi/MetricDictionaryService.php`

**Step 1: Write a failing PHP contract test**

Assert the exact canonical envelope:

- `meta`, canonical `query`, authorized `scope`, `coverage`, `current`, `curve`,
  `forecast`, `replanning`, `key_dates`, `headline`, `actions`, `lineage`,
  `limitations`, `status` and canonical error code;
- one internally coherent snapshot/cutoff;
- point-level detail embedded for drawer use;
- coverage counts for eligible/excluded rows, duration and snapshots;
- lineage per figure, series and decision with accurate live source names;
- safe canonical errors with no SQL, path, stack or credential leakage.

Use fakes and a controller harness; do not use MySQL.

**Step 2: Prove PHP RED**

       docker compose exec app php tests/test_api_bi_curve_contract.php

Expected: non-zero because the service/canonical presenter is incomplete.

**Step 3: Implement orchestration and envelope**

The service requests all project data through the authorized S18/T03 seams, then passes immutable
results to the pure builders. Optional forecast failure may yield `partial`; it may not erase
coherent plan/real data.

**Step 4: Make PHP contract GREEN**

       docker compose exec app php tests/test_api_bi_curve_contract.php

Expected: zero.

**Step 5: Write failing Zod tests**

Test:

- one full ready envelope;
- valid zero versus nullable missing values;
- partial forecast;
- insufficient history/baseline;
- per-project breakdown;
- malformed dates, unordered bands, unknown statuses and missing lineage rejected;
- safe canonical error envelope accepted;
- legacy payload accepted only by the compatibility adapter, never by React components.

**Step 6: Prove frontend RED**

       cd frontend
       npm test -- --run src/lib/api/esquemas/biCurvaS.test.ts src/lib/api/biCurvaS.test.ts

Expected: non-zero because schema/gateway are absent.

**Step 7: Implement schema and gateway**

Derive all exported types with `z.infer`. Use only the typed primitive from
`frontend/src/lib/api/cliente.ts`; pass `AbortSignal` and canonical query unchanged.

**Step 8: Make frontend contract GREEN**

       cd frontend
       npm test -- --run src/lib/api/esquemas/biCurvaS.test.ts src/lib/api/biCurvaS.test.ts
       npm run typecheck

Expected: zero.

**Step 9: Prove read-only/source invariants**

       rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts'
       rg -n "POST|PUT|PATCH|DELETE" frontend/src/lib/api/biCurvaS.ts \
         src/Controllers/Api/BiControlTowerApiController.php
       rg -n "CREATE|ALTER|DROP|TRUNCATE|INSERT|UPDATE|DELETE" src/Services/Bi/Curve tests/test_bi_curve

Expected: no S19 direct fetch, mutation or DDL/DML path. Review false positives such as prose or
method names manually.

**Step 10: Future implementation commit**

       git add src/Services/Bi/Curve src/Controllers/Api/BiControlTowerApiController.php \
         src/Services/Bi/LineageService.php src/Services/Bi/MetricDictionaryService.php \
         tests/test_api_bi_curve_contract.php frontend/src/lib/api
       git commit -m "feat(bi): serve typed curva s snapshot"

Do not run this commit in the planning session.

## Task 7: Render the decision sheet and accessible curve

**Acceptance:** S19-AC-58, S19-AC-59, S19-AC-60, S19-AC-85 and S19-AC-88.

**Files:**

- Create: `frontend/src/modulos/bi/CurvaSPagina.tsx`
- Create: `frontend/src/modulos/bi/CurvaSPagina.test.tsx`
- Create: `frontend/src/modulos/bi/curva/TitularCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/ResumenCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/GraficoCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/FechasClaveCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/TendenciaCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/DesgloseProyectosCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/AccionesCurvaS.tsx`
- Create colocated component tests

**Step 1: Write failing render tests**

Assert the sheet order:

1. monthly/milestone reporting rhythm, cutoff and `Alcance filtrado`;
2. server headline and N6 evidence;
3. current plan/real/gap/trend figures;
4. native SVG curve;
5. visible textual point representation;
6. key dates and explicit milestone limitation;
7. per-project breakdown when multi;
8. server-authorized actions.

Assert valid zero renders as `0`, while null uses the explicit unavailable label.

**Step 2: Write failing SVG/accessibility tests**

Prove:

- SVG has a meaningful accessible name, `title` and `desc`;
- band is described in words and not only color/opacity;
- each series has text/shape/legend differentiation;
- no decision is hover-only, canvas-only or color-only;
- every plotted point appears in the visible table/cards;
- unavailable consolidated multi band is explained.

**Step 3: Prove RED**

       cd frontend
       npm test -- --run src/modulos/bi/CurvaSPagina.test.tsx \
         src/modulos/bi/curva

Expected: non-zero because the page/components are absent.

**Step 4: Build with native SVG and semantic HTML**

Use server values only. SVG coordinate calculation may transform presentation coordinates but may
not calculate metrics, thresholds, forecasts or decisions. Keep chart text independent of pointer
interaction.

**Step 5: Make focused tests GREEN**

       cd frontend
       npm test -- --run src/modulos/bi/CurvaSPagina.test.tsx \
         src/modulos/bi/curva
       npm run typecheck

Expected: zero.

**Step 6: Enforce token and dependency constraints**

       rg -n "#[0-9a-fA-F]{3,8}|rgb\\(|hsl\\(|!important|style=|Chart\\.js|chart\\.js" \
         frontend/src/modulos/bi

Expected: no new literal color, inline color, `!important` or chart dependency in S19.

**Step 7: Future implementation commit**

       git add frontend/src/modulos/bi
       git commit -m "feat(bi): render accessible curva s sheet"

Do not run this commit in the planning session.

## Task 8: Add responsive point history and cutoff detail drawer

**Acceptance:** S19-AC-54 through S19-AC-57.

**Files:**

- Create: `frontend/src/modulos/bi/curva/PuntosCurvaS.tsx`
- Create: `frontend/src/modulos/bi/curva/DetalleCorteCurvaS.tsx`
- Create colocated tests
- Reuse: T03 shared responsive detail drawer

**Step 1: Write failing interaction tests**

For both semantic representations, prove:

- >=768 mounts one table and no card list;
- <768 mounts one card list and no table;
- selecting a point opens its embedded detail without another gateway request;
- detail shows values, raw numerator/denominator, project breakdown, coverage, limitations and
  lineage;
- context point is labelled and visually/textually distinguished from visible-range points;
- close button, Escape and backdrop follow the shared drawer contract;
- opening moves focus inside, focus is trapped, closing returns focus to the invoking row/card;
- controls have at least 44x44 touch targets at mobile widths.

**Step 2: Prove RED**

       cd frontend
       npm test -- --run src/modulos/bi/curva/PuntosCurvaS.test.tsx \
         src/modulos/bi/curva/DetalleCorteCurvaS.test.tsx

Expected: non-zero because responsive representations/detail are absent.

**Step 3: Implement using T03 primitives**

Do not create a S19-specific modal framework. Keep the invoking point key stable between
table/card views and derive no new metric in the drawer.

**Step 4: Make tests GREEN**

       cd frontend
       npm test -- --run src/modulos/bi/curva/PuntosCurvaS.test.tsx \
         src/modulos/bi/curva/DetalleCorteCurvaS.test.tsx

Expected: zero.

**Step 5: Future implementation commit**

       git add frontend/src/modulos/bi/curva/PuntosCurvaS.tsx \
         frontend/src/modulos/bi/curva/DetalleCorteCurvaS.tsx \
         frontend/src/modulos/bi/curva/*.test.tsx
       git commit -m "feat(bi): add curva s cutoff detail"

Do not run this commit in the planning session.

## Task 9: Complete states, concurrency, route cut and browser evidence

**Acceptance:** S19-AC-01, S19-AC-02, S19-AC-61 through S19-AC-71, S19-AC-83 and S19-AC-84.

**Files:**

- Create: `frontend/src/modulos/bi/curva/useCurvaS.ts`
- Create: `frontend/src/modulos/bi/curva/useCurvaS.test.tsx`
- Create: `frontend/src/modulos/bi/curva/curva-s.css`
- Create: `tests/browser/fixtures/bi-curva-s-react.mjs`
- Create: `tests/browser/bi-curva-s-react.spec.mjs`
- Create: `tests/browser/bi-curva-s-react.a11y.mjs`
- Modify: `frontend/src/App.tsx` or `frontend/src/shell/rutas.tsx`
- Modify: `public/index.php`
- Modify: `src/Controllers/Bi/BiViewController.php`

**Step 1: Write failing state/concurrency tests**

Cover visible `loading`, `ready`, `partial`, `empty`, `insufficient`, `offline`,
`invalid` and `error`. Prove:

- partial forecast preserves coherent plan/real;
- changing query aborts the old request;
- stale completion cannot overwrite the latest query;
- replacement is atomic and does not mix cutoffs;
- cache keys include user/session identity, authorized scope, period and filters;
- retry is GET only;
- rhythm copy says monthly or milestone cadence and never promises weekly email.

**Step 2: Prove state RED**

       cd frontend
       npm test -- --run src/modulos/bi/curva/useCurvaS.test.tsx

Expected: non-zero because the hook/state machine is absent.

**Step 3: Implement the hook**

Reuse T03 state primitives and `cliente.ts`. Do not add a query library. Keep prior coherent data
only when the shared BI policy explicitly marks it stale; otherwise replace atomically.

**Step 4: Make state tests GREEN**

       cd frontend
       npm test -- --run src/modulos/bi/curva/useCurvaS.test.tsx

Expected: zero.

**Step 5: Build intercepted browser fixtures before route cut**

Fixtures must provide ready, partial, empty, insufficient, invalid, offline, multi-project,
recommended N6, watch and no-baseline responses. Intercept the existing GET before navigation and
throw on every POST/PUT/PATCH/DELETE.

**Step 6: Write failing browser tests against a shadow route**

Cover:

- dark and light with identical controls/actions;
- 390x844, 480x900, 768x1024, 1180x820 and 1440x900;
- no page-level horizontal overflow;
- table/card mount boundary;
- keyboard and touch point selection;
- focus order, drawer trap/Escape/return;
- reduced motion;
- 200 percent zoom readability/actions;
- console errors zero;
- axe serious/critical zero;
- no mutation requests;
- no visual-golden update.

**Step 7: Prove browser RED for the right reason**

       npx playwright test tests/browser/bi-curva-s-react.spec.mjs \
         tests/browser/bi-curva-s-react.a11y.mjs --workers=1

Expected: non-zero because the shadow route is not wired, not because interception or authentication
leaked to the live backend.

**Step 8: Wire the shadow route, then cut the canonical page**

First make browser behavior green on the shadow route. Then route `GET /bi/curva-s` through the
SPA host while preserving the existing API route and authorization. Do not touch `/admin/` or
any other BI page.

**Step 9: Run route/access tests**

       docker compose exec app php tests/test_bi_curve_routes.php

Expected: allowed path serves SPA, hidden path returns 404, unauthorized project API returns 403,
and no admin or sibling route changed.

**Step 10: Run focused frontend/browser checks**

       cd frontend
       npm test -- --run src/lib/api/esquemas/biCurvaS.test.ts \
         src/lib/api/biCurvaS.test.ts src/modulos/bi/CurvaSPagina.test.tsx \
         src/modulos/bi/curva
       npm run typecheck
       cd ..
       npx playwright test tests/browser/bi-curva-s-react.spec.mjs \
         tests/browser/bi-curva-s-react.a11y.mjs --workers=1

Expected: zero.

**Step 11: Manually inspect the served app**

Use the approved development door only if it is enabled in the actual environment. Validate one
permitted and one denied role, dark/light, console, network and the canonical desktop/mobile
viewports. Do not type credentials into login and do not change environment data to enable access.

**Step 12: Future implementation commit**

       git add frontend/src/modulos/bi frontend/src/App.tsx frontend/src/shell/rutas.tsx \
         public/index.php src/Controllers/Bi/BiViewController.php tests/browser \
         tests/test_bi_curve_routes.php
       git commit -m "feat(bi): cut curva s to react"

Stage only paths that actually changed. Do not run this commit in the planning session.

## Task 10: Prove coexistence, rollback and deferred legacy retirement

**Acceptance:** S19-AC-81 and S19-AC-82.

**Files:**

- Modify only if needed: `tests/test_bi_curve_routes.php`
- Modify only if needed: `tests/test_bi_curve_source_invariants.php`
- Preserve all shared legacy files listed above
- Record closure later in this plan

**Step 1: Write failing coexistence/rollback assertions**

Prove:

- S19 page route can switch back to the legacy view without schema/data restoration;
- the API compatibility presenter remains available while any legacy caller exists;
- shared BI layout, JavaScript, CSS and Chart.js still exist;
- no S17–S24 shared retirement occurs before the T03 gate;
- rollback changes only route/code selection.

**Step 2: Prove RED if a retirement or irreversible coupling exists**

       docker compose exec app php tests/test_bi_curve_routes.php
       docker compose exec app php tests/test_bi_curve_source_invariants.php

Expected: zero after Task 9. If non-zero, correct only the coexistence seam; never restore data.

**Step 3: Run all S19 PHP tests**

       docker compose exec app php tests/test_bi_curve_query.php
       docker compose exec app php tests/test_bi_curve_contract_characterization.php
       docker compose exec app php tests/test_bi_curve_series.php
       docker compose exec app php tests/test_bi_curve_forecast.php
       docker compose exec app php tests/test_bi_curve_replanning.php
       docker compose exec app php tests/test_bi_curve_headline.php
       docker compose exec app php tests/test_api_bi_curve_contract.php
       docker compose exec app php tests/test_bi_curve_source_invariants.php
       docker compose exec app php tests/test_bi_curve_routes.php

Read each return code on its own command. Expected: all zero.

**Step 4: Run the proportional frontend suite**

       cd frontend
       npm test -- --run src/lib/api/esquemas/biCurvaS.test.ts \
         src/lib/api/biCurvaS.test.ts src/modulos/bi/CurvaSPagina.test.tsx \
         src/modulos/bi/curva
       npm run typecheck
       npm run build

Expected: zero.

**Step 5: Run the fully intercepted browser suite**

       npx playwright test tests/browser/bi-curva-s-react.spec.mjs \
         tests/browser/bi-curva-s-react.a11y.mjs --workers=1

Expected: zero mutations, zero console errors, zero axe serious/critical failures and no page
overflow at all required viewports/themes.

**Step 6: Review the final diff and data boundary**

       git diff --check
       git status --short
       git diff --stat
       git diff -- public/index.php src frontend tests docs

Confirm no `admin/`, RLS, migration, schema, SQL view, credential, environment, data, golden or
unrelated file entered the diff.

**Step 7: Future implementation commit**

       git add <only-the-reviewed-S19-paths>
       git commit -m "test(bi): verify curva s react migration"

Do not run this commit in the planning session. Branch finishing, PR, publication and deployment
remain separate authorization/gate decisions.

## Traceability Matrix

Every acceptance criterion appears exactly once.

| Criterion | Task |
|---|---:|
| S19-AC-01 | 9 |
| S19-AC-02 | 9 |
| S19-AC-03 | 1 |
| S19-AC-04 | 1 |
| S19-AC-05 | 1 |
| S19-AC-06 | 1 |
| S19-AC-07 | 1 |
| S19-AC-08 | 1 |
| S19-AC-09 | 1 |
| S19-AC-10 | 1 |
| S19-AC-11 | 1 |
| S19-AC-12 | 1 |
| S19-AC-13 | 1 |
| S19-AC-14 | 1 |
| S19-AC-15 | 2 |
| S19-AC-16 | 2 |
| S19-AC-17 | 1 |
| S19-AC-18 | 3 |
| S19-AC-19 | 3 |
| S19-AC-20 | 3 |
| S19-AC-21 | 3 |
| S19-AC-22 | 3 |
| S19-AC-23 | 3 |
| S19-AC-24 | 3 |
| S19-AC-25 | 3 |
| S19-AC-26 | 3 |
| S19-AC-27 | 3 |
| S19-AC-28 | 3 |
| S19-AC-29 | 3 |
| S19-AC-30 | 3 |
| S19-AC-31 | 4 |
| S19-AC-32 | 4 |
| S19-AC-33 | 4 |
| S19-AC-34 | 4 |
| S19-AC-35 | 4 |
| S19-AC-36 | 4 |
| S19-AC-37 | 4 |
| S19-AC-38 | 4 |
| S19-AC-39 | 4 |
| S19-AC-40 | 4 |
| S19-AC-41 | 4 |
| S19-AC-42 | 4 |
| S19-AC-43 | 4 |
| S19-AC-44 | 5 |
| S19-AC-45 | 5 |
| S19-AC-46 | 5 |
| S19-AC-47 | 5 |
| S19-AC-48 | 5 |
| S19-AC-49 | 5 |
| S19-AC-50 | 5 |
| S19-AC-51 | 5 |
| S19-AC-52 | 5 |
| S19-AC-53 | 5 |
| S19-AC-54 | 8 |
| S19-AC-55 | 8 |
| S19-AC-56 | 8 |
| S19-AC-57 | 8 |
| S19-AC-58 | 7 |
| S19-AC-59 | 7 |
| S19-AC-60 | 7 |
| S19-AC-61 | 9 |
| S19-AC-62 | 9 |
| S19-AC-63 | 9 |
| S19-AC-64 | 9 |
| S19-AC-65 | 9 |
| S19-AC-66 | 9 |
| S19-AC-67 | 9 |
| S19-AC-68 | 9 |
| S19-AC-69 | 9 |
| S19-AC-70 | 9 |
| S19-AC-71 | 9 |
| S19-AC-72 | 6 |
| S19-AC-73 | 6 |
| S19-AC-74 | 6 |
| S19-AC-75 | 6 |
| S19-AC-76 | 6 |
| S19-AC-77 | 6 |
| S19-AC-78 | 6 |
| S19-AC-79 | 6 |
| S19-AC-80 | 2 |
| S19-AC-81 | 10 |
| S19-AC-82 | 10 |
| S19-AC-83 | 9 |
| S19-AC-84 | 9 |
| S19-AC-85 | 7 |
| S19-AC-86 | 4 |
| S19-AC-87 | 5 |
| S19-AC-88 | 7 |

## Vertical Checkpoints

1. **Base curve:** Tasks 1–3 leave the old route usable while proving authorized terminal history,
   range context, inclusive weighting and multi-project reconciliation.
2. **Probable outcome:** Tasks 4–5 add forecast/band, key dates and the exact N6 decision without
   any frontend inference or mutation.
3. **Typed read path:** Task 6 exposes one coherent canonical envelope through the existing GET and
   validates it before React.
4. **Usable decision sheet:** Tasks 7–8 deliver the accessible visual/text curve and point detail
   across the table/card boundary.
5. **Cut and evidence:** Tasks 9–10 cut only S19, prove all themes/viewports/states and retain a
   code-only rollback plus shared legacy coexistence.

Stop after each checkpoint for review in an authorized implementation session. Do not combine
checkpoint commits with unrelated surfaces.

## Definition of Done

- All 88 S19 criteria trace exactly once and have passing focused evidence.
- The existing page route serves React; the existing API GET is the sole data endpoint.
- Week returns comparable history through a terminal cutoff; range returns visible points plus
  marked prior context when present.
- Plan/real formulas, aggregate numerators and project breakdown reconcile.
- Forecast and band reuse S18 and preserve deterministic/sufficiency/baseline rules.
- N6 is exact, per project, two-cut and evidence-bearing; no old minus-five rule drives React.
- The server owns headline, actions, hrefs and capability gating; S19 performs no mutation.
- Native SVG and visible semantic table/cards expose every point and distinguish zero from missing.
- Point detail needs no second request and meets the shared drawer focus contract.
- Dark/light, five viewports, 200 percent zoom, keyboard, touch and reduced motion pass.
- Axe serious/critical and console errors are zero in the fully intercepted suite.
- No direct component fetch, chart library, color literal, `!important` or CSS-only duplicate
  responsive representation exists.
- No `/admin/`, RLS, runtime-boundary, schema, grant, user, credential, data, migration, SQL view
  or visual golden changed.
- Legacy compatibility remains until zero callers; shared retirement waits for T03/S17–S24.
- Rollback changes route/code only and needs no data restoration.
- Final evidence names the exact SHA, commands, return codes, browser limitations and untouched
  data boundary before any branch-finishing decision.

## Cierre

Estado inicial: plan escrito; implementación no iniciada.

El cierre futuro must record:

- exact implemented SHA and reviewed diff;
- focused PHP/frontend/browser command results with separately read return codes;
- permitted and denied access evidence;
- viewport/theme/zoom/accessibility/console/network evidence;
- proof of zero mutation and untouched RLS/schema/data boundary;
- compatibility callers remaining and T03 retirement status;
- rollback result;
- any limitations or deferred work.

Do not mark this plan complete from checkbox counts. Do not commit, push, open a PR, publish or
deploy without the repository's applicable close gate and explicit authorization.
