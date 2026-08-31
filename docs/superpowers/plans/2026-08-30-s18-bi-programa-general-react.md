---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [bi, design-system]
fuente: docs/superpowers/plans/2026-08-30-s18-bi-programa-general-react.md
resumen: "migrate /bi/programa-general into the main React SPA as the decision sheet for six-week program order, probable contractual finish, combined delivery risk and…"
---

# S18 BI Programa General React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Use superpowers:test-driven-development for every task and
> verification-before-completion before any completion claim. Execute tasks in order and stop at
> every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and closure live
> in Cierre and git history, never in checkbox counts.

**Goal:** migrate `/bi/programa-general` into the main React SPA as the decision sheet for
six-week program order, probable contractual finish, combined delivery risk and schedule value;
preserve all seven read contracts, filters, activity/detail behavior, causal evidence and lineage;
and provide equivalent desktop/tablet/mobile, dark/light and accessible operation without any
write, RLS, schema or data change.

**Architecture:** T01 remains owner of session, active project, shell, theme, route outlet and the
only HTTP client. T03, first delivered through S17, remains owner of BI sheet access, canonical
query, filters, responsive sheet frame, detail drawer and lineage. S18 adds a pure,
project-scoped `BiProgramReadService` behind the seven current GET routes. Small adapters extract
forecast, progress, risk, schedule-value, radar and causal seams from `ControlTowerService`
without copying its monolith. PHP computes metrics, eligibility, narratives and coherent
section snapshots; React validates the envelope with Zod and only renders it. Legacy chart
payloads remain behind compatibility presenters until the T03 retirement gate.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright, native SVG and the existing AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s18-bi-programa-general-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout
  or another worktree.
- Inspect `git status --short` and relevant diffs before every task. Preserve unrelated and
  pre-existing edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future instructions and require explicit
  implementation authorization.
- Implement S18 only after the T01 shell and the T03 primitives assigned to S17 are present and
  green. Reuse them; do not build a second BI shell, query codec, access policy or drawer.
- `/admin/` is excluded. Do not edit its controllers, views, routes, flags UI, permissions or
  tests.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, keys, triggers, grants, users, credentials, memberships, roles, aliases, overrides or
  data.
- No DDL/DML is permitted. New PHP tests use pure services, deterministic fakes, call logs,
  reflection/source invariants and static fixtures. Browser tests install complete interception
  before navigation and fail on every mutation.
- Do not run existing BI fixture suites that write and roll back MySQL as evidence for this plan.
  A rollback write is still DML.
- Preserve `BiPreviewAccessPolicy`: A is permitted; D/R remain governed by the existing global
  flag; any hidden sheet is a 404. Do not edit the flag.
- Apply the approved S18 sheet policy: A/D/R may enter when the gate permits; all other roles get
  the hidden-module 404.
- Preserve `lps.indicadores.ver` per project and `BiProjectScope` authorization. Client
  `project_ids`, `project_id`, `role`, `permiso`, `db`, `prefix`, `username` or
  capability-like keys are never authority.
- The default is the active authorized project. Multi-project selection is explicit and preserves
  one cutoff and completeness record per project.
- Preserve T03 query fields `semana`, `desde`, `hasta`, `sub_contratista`,
  `responsable` and `etapa`. Every detail receives the exact canonical scope, period and
  filters of its parent.
- A main response is one coherent snapshot. A failed optional section may be partial, but no
  section may silently use a different cutoff.
- Keep probable delay and observed activity delay separate. Never add activity delay as if it were
  project delay.
- Keep the contractual line explicit. When the forecast catalog says history or baseline is
  insufficient, return `unavailable` with null dates; never manufacture a finish.
- Forecast uses the existing 240 deterministic simulations and preserves
  `P10 <= P50 <= P90`. Headline copy says probable date/range/variance in words and never exposes
  the implementation term P50.
- Progress uses inclusive-duration weighting. Gap is real minus theoretical. Compliance preserves
  its existing numerator, denominator and base.
- The execution series preserves real, theoretical and three forecast curves. Native SVG must have
  a textual table; no chart library is added.
- The initial activity snapshot is 25 rows. Keep stable activity keys, contribution, criticality,
  observed delay, finish and current blocking evidence.
- Keep `RISK-SCORE-1.0` exactly: `35*p + 25*i + 20*u + 10*c + 10*d`. Never average risk scores
  across projects. Explain drivers, restrictions, age, criticality and limitations.
- Schedule value uses only the active, non-obsolete budget version and project-scoped
  `project_id + unique_id` mappings. `PV = budgetValue * plannedProgress`,
  `EV = budgetValue * realProgress`, `SV = EV - PV`, and `SPI = EV / PV` only when
  `PV > 0`.
- Schedule value always exposes value and activity coverage. Partial coverage labels its covered
  scope; insufficient coverage exposes no money. Orphan value is never distributed.
- No actual-cost source exists in scope. Do not return or render CPI, CV, cost variance, overrun or
  any claim about actual project cost.
- Radar axes are `Avance promedio`, `Eficiencia` and `Desempeño`. Visual scale is 0–100;
  raw values above 100 remain auditable while display is capped. An ineligible axis is null, never
  zero, and each axis requires the existing minimum sample of three.
- CNP universe remains `Activa='0'` with non-empty CNP. CNC universe remains
  `Activa IN ('1','NA')` with non-empty CNC. Preserve original/canonical/known category evidence
  and unknown quantity handling.
- S18 renders one causal headline. Its read-only CNP/CNC details stay during coexistence; the final
  destination is S21 with `focus` and `category` preserved.
- All seven endpoints remain GET. Retry, load-more and filter changes must never trigger a
  mutation.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Gateways use its typed request
  primitive.
- Every response is parsed by Zod before components see it. Types come from `z.infer`, never a
  hand-maintained parallel interface.
- Use semantic table/cards/disclosure/dialog patterns. Do not add DataTables, Handsontable,
  Chart.js, jQuery, Bootstrap, Tailwind, a query/state library, a chart library or CSS-in-JS.
- At widths below 768 mount cards only; at 768 and above mount the semantic table only. Do not
  mount both and hide one only with CSS.
- Use `public/css/tokens.css`. No hex/rgb/hsl literals, inline color styles,
  `!important` rules or local theme forks.
- Dark is default/fallback and light has identical capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Do not delete `views/bi` files, `bi-spa.js`, BI CSS, Chart.js or shared legacy dependencies in
  S18. Their retirement is the T03 gate after S17–S24.

## Dependency Gate

Before Task 1 in the future implementation session:

1. Read the S17 close record and verify that these T03 contracts exist:
   `BiSheetAccessPolicy`, `BiQueryParser`, `MarcoBi`, `FiltrosBi`, `LinajeDrawer`,
   `EstadoBi` and the shared responsive detail drawer.
2. If S17 is not implemented, stop S18. Execute S17 first; do not copy its planned primitives into
   S18.
3. Verify branch, status and runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: the exact worktree and branch above; services `app`, `db`, `adminer`; no
   unexpected worktree changes; mounted `app` healthy before PHP/browser checks.
4. Record the starting SHA and the pre-existing diff. Do not stage it.

## File Structure

### Create — backend domain and ports

- `src/Services/Bi/Program/BiProgramProjectReader.php` — read port for one authorized coherent
  project snapshot.
- `src/Services/Bi/Program/PdoBiProgramProjectReader.php` — prepared, read-only,
  project-scoped production adapter.
- `src/Services/Bi/Program/BiProgramForecastAdapter.php` — deterministic forecast and headline
  seam.
- `src/Services/Bi/Program/BiProgramProgressAdapter.php` — progress, compliance, execution series
  and activity detail seam.
- `src/Services/Bi/Program/BiProgramRiskAdapter.php` — RISK-SCORE-1.0 and restriction evidence.
- `src/Services/Bi/Program/BiProgramScheduleValueReader.php` — active budget, mapping coverage and
  PV/EV/SV/SPI.
- `src/Services/Bi/Program/BiProgramRadarAdapter.php` — three-axis eligibility and values.
- `src/Services/Bi/Program/BiProgramCausalAdapter.php` — CNP/CNC universes and categories.
- `src/Services/Bi/Program/BiProgramReadService.php` — coherent section orchestration.
- `src/Services/Bi/Program/BiProgramPresenter.php` — canonical main envelope and legacy adapter.
- `src/Services/Bi/Program/BiProgramDetailPresenter.php` — uniform detail envelopes and
  pagination.

### Create — PHP tests and fixtures

- `tests/Support/Bi/FakeBiProgramProjectReader.php`.
- `tests/Support/Bi/FakeBiProgramSection.php`.
- `tests/fixtures/bi-programa-general-react/brief.php`.
- `tests/fixtures/bi-programa-general-react/details.php`.
- `tests/fixtures/bi-programa-general-react/projects.php`.
- `tests/test_bi_program_query.php`.
- `tests/test_bi_program_contract_characterization.php`.
- `tests/test_bi_program_forecast.php`.
- `tests/test_bi_program_progress.php`.
- `tests/test_bi_program_risk.php`.
- `tests/test_bi_program_schedule_value.php`.
- `tests/test_bi_program_radar_contract.php`.
- `tests/test_bi_program_causal_contract.php`.
- `tests/test_bi_program_detail_contract.php`.
- `tests/test_api_bi_program_contract.php`.
- `tests/test_bi_program_source_invariants.php`.
- `tests/test_bi_program_routes.php`.

### Create — frontend API

- `frontend/src/lib/api/esquemas/biProgramaGeneral.ts`.
- `frontend/src/lib/api/esquemas/biProgramaCumplimientoDetalle.ts`.
- `frontend/src/lib/api/esquemas/biProgramaAvanceDetalle.ts`.
- `frontend/src/lib/api/esquemas/biProgramaRetrasoDetalle.ts`.
- `frontend/src/lib/api/esquemas/biProgramaRadarDetalle.ts`.
- `frontend/src/lib/api/esquemas/biProgramaCausalDetalle.ts`.
- `frontend/src/lib/api/biProgramaGeneral.ts`.
- Colocated schema and gateway tests for all seven routes.

### Create — frontend sheet

- `frontend/src/modulos/bi/ProgramaGeneralBiPagina.tsx`.
- `frontend/src/modulos/bi/programa/TitularTerminacion.tsx`.
- `frontend/src/modulos/bi/programa/RiesgoPrograma.tsx`.
- `frontend/src/modulos/bi/programa/ValorCronograma.tsx`.
- `frontend/src/modulos/bi/programa/ResumenAvance.tsx`.
- `frontend/src/modulos/bi/programa/SerieEjecucion.tsx`.
- `frontend/src/modulos/bi/programa/RadarPrograma.tsx`.
- `frontend/src/modulos/bi/programa/ActividadesPrograma.tsx`.
- `frontend/src/modulos/bi/programa/TitularCausas.tsx`.
- `frontend/src/modulos/bi/programa/DetalleCumplimiento.tsx`.
- `frontend/src/modulos/bi/programa/DetalleAvance.tsx`.
- `frontend/src/modulos/bi/programa/DetalleRetraso.tsx`.
- `frontend/src/modulos/bi/programa/DetalleRadar.tsx`.
- `frontend/src/modulos/bi/programa/DetalleCausal.tsx`.
- `frontend/src/modulos/bi/programa/useProgramaGeneralBi.ts`.
- `frontend/src/modulos/bi/programa/useDetalleBi.ts`.
- `frontend/src/modulos/bi/programa/programa-general-bi.css`.
- Colocated tests for the page, hooks and every interactive component.
- `tests/browser/bi-programa-general-react.spec.mjs`.
- `tests/browser/bi-programa-general-react.a11y.mjs`.
- `tests/browser/fixtures/bi-programa-general-react.mjs`.

### Modify during implementation

- `public/index.php` — preserve all seven API paths; cut the existing page path to the SPA host
  only at Task 10.
- `src/Controllers/Api/BiControlTowerApiController.php` — thin delegation to T03 query,
  authorization, service and presenters.
- `src/Controllers/Bi/BiViewController.php` — S18 sheet policy and SPA cut adapter.
- `src/Services/ControlTowerService.php` — delegate extracted seams while preserving legacy
  payloads.
- `src/Services/Bi/RiskScoringService.php` — only if delegation is necessary; do not change
  RISK-SCORE-1.0.
- `src/Services/Bi/LineageService.php` and `MetricDictionaryService.php` — canonical keys and
  visible lineage only; no source semantic changes.
- `frontend/src/App.tsx` or `frontend/src/shell/rutas.tsx` — shadow then canonical S18 route.
- `frontend/src/main.tsx` — module stylesheet registration if the registry does not own it.
- `frontend/src/lib/api/cliente.ts` — only if T01 still lacks AbortSignal/error metadata.
- `public/css/tokens.css` — only for a genuinely absent semantic token plus a token contract test.
- Browser config/helpers — only to register the fully intercepted safe suites.

### Explicitly preserve

- `views/bi/_filters.php`.
- `views/bi/_layout.php`.
- `views/bi/_nav.php`.
- `views/bi/control-tower.php`.
- `public/js/modules/bi-spa.js`.
- `public/css/bi-control-tower.css`.
- `public/css/bi-filter-drawer.css`.
- Existing Chart.js and lucide imports while another legacy BI caller remains.
- The seven route URLs and any compatibility keys consumed by legacy callers.

## Task 1: Lock S18 access, project scope and canonical query

**Acceptance:** S18-AC-03, S18-AC-04, S18-AC-05, S18-AC-06, S18-AC-07, S18-AC-08,
S18-AC-09, S18-AC-12.

**Files:**

- Modify: `src/Security/BiSheetAccessPolicy.php`.
- Modify: `src/Services/Bi/Http/BiQuery.php`.
- Modify: `src/Services/Bi/Http/BiQueryParser.php`.
- Create: `tests/test_bi_program_query.php`.
- Modify: `tests/test_bi_sheet_access_policy.php`.
- Modify: `tests/test_bi_query_contract.php`.
- Create: `frontend/src/modulos/bi/programa/consultaPrograma.ts`.
- Create: `frontend/src/modulos/bi/programa/consultaPrograma.test.ts`.

### Step 1: Write failing PHP access/query cases

Cover:

- A, D and R permitted only when the existing preview gate permits them;
- V and every unlisted role hidden with 404;
- an authenticated user without membership/capability receives 403 and an empty data body;
- active authorized project is the default;
- explicit multi-project scope revalidates every ID;
- week and range forms are mutually coherent;
- subcontractor/responsible/stage arrays normalize deterministically;
- detail query is a copy of main canonical scope/period/filters plus only its allowlisted detail
  controls;
- `role`, `permiso`, `db`, `prefix`, `username`, `capability`,
  `project_membership` and lookalikes fail validation.

Run:

    docker compose exec app php tests/test_bi_program_query.php
    docker compose exec app php tests/test_bi_sheet_access_policy.php
    docker compose exec app php tests/test_bi_query_contract.php

Expected: new S18 cases fail because the sheet manifest and detail codecs do not exist.

### Step 2: Extend, do not fork, T03 contracts

Add `programa-general` to the shared sheet manifest with allowed canvas roles A/D/R. Add typed
detail options:

- progress: `sort`, `critical_only`, `grouping`;
- radar: `axis`;
- causal: `category`;
- every detail: `limit`, `offset`, `include_summary`.

Reject unknown authority-like keys before project resolution. Keep raw query data out of service
constructors.

### Step 3: Add the frontend query codec

`consultaPrograma.ts` must derive from T03 `consultaBi.ts`, preserve canonical ordering and
create the detail query from the current main query. It must not read role or capability from
browser storage.

Run:

    npm --prefix frontend test -- consultaPrograma.test.ts

Expected: query round trips, resets and rejected authority keys pass.

### Step 4: Re-run focused tests

Run the three PHP commands and the focused frontend test again. Expected: all pass and no database
connection is opened.

### Step 5: Future checkpoint

Inspect diff for only shared T03/S18 query files. In an authorized execution session, commit:

    git add src/Security/BiSheetAccessPolicy.php src/Services/Bi/Http tests/test_bi_program_query.php tests/test_bi_sheet_access_policy.php tests/test_bi_query_contract.php frontend/src/modulos/bi/programa/consultaPrograma.ts frontend/src/modulos/bi/programa/consultaPrograma.test.ts
    git commit -m "test(bi): lock programa general access and query"

## Task 2: Characterize and freeze the seven current HTTP contracts

**Acceptance:** S18-AC-10, S18-AC-11.

**Files:**

- Create: `tests/fixtures/bi-programa-general-react/brief.php`.
- Create: `tests/fixtures/bi-programa-general-react/details.php`.
- Create: `tests/test_bi_program_contract_characterization.php`.
- Create: `tests/test_bi_program_routes.php`.
- Read only initially: `public/index.php`.
- Read only initially: `src/Controllers/Api/BiControlTowerApiController.php`.
- Read only initially: `src/Services/ControlTowerService.php`.

### Step 1: Capture behavior, not implementation noise

Build static fixtures from the measured live shapes for:

1. `GET /api/bi/report/programa-general`;
2. `GET /api/bi/report/programa-general/compliance-detail`;
3. `GET /api/bi/report/programa-general/progress-detail`;
4. `GET /api/bi/report/programa-general/delay-detail`;
5. `GET /api/bi/report/programa-general/radar-detail`;
6. `GET /api/bi/report/programa-general/cnp-detail`;
7. `GET /api/bi/report/programa-general/cnc-detail`.

Fixtures must include zero, null, partial, empty and invalid-query examples. Remove session IDs,
timestamps unrelated to cutoff and any environment-specific data.

### Step 2: Write failing route/source assertions

Assert:

- each existing path appears exactly once;
- all are GET;
- no `/react`, `/v2` or parallel path is introduced;
- each controller action resolves sheet access and project scope before calling a reader;
- error bodies contain no project names or internal table details.

Run:

    docker compose exec app php tests/test_bi_program_contract_characterization.php
    docker compose exec app php tests/test_bi_program_routes.php

Expected: fixture characterization passes against legacy adapters; canonical-envelope assertions
remain red until Task 5.

### Step 3: Record the compatibility boundary

Add comments in the fixture contract, not production code, listing which legacy keys must stay
during coexistence and which Chart.js configuration is explicitly not part of the React contract.
Do not edit route/controller/service behavior yet.

### Step 4: Future checkpoint

In an authorized execution session, commit only characterization assets:

    git add tests/fixtures/bi-programa-general-react tests/test_bi_program_contract_characterization.php tests/test_bi_program_routes.php
    git commit -m "test(bi): characterize programa general reports"

## Task 3: Extract forecast, progress and execution-series domain seams

**Acceptance:** S18-AC-13, S18-AC-14, S18-AC-15, S18-AC-16, S18-AC-17, S18-AC-18,
S18-AC-19, S18-AC-20, S18-AC-21, S18-AC-22, S18-AC-23, S18-AC-24, S18-AC-25,
S18-AC-26, S18-AC-27, S18-AC-28, S18-AC-29.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramProjectReader.php`.
- Create: `src/Services/Bi/Program/PdoBiProgramProjectReader.php`.
- Create: `src/Services/Bi/Program/BiProgramForecastAdapter.php`.
- Create: `src/Services/Bi/Program/BiProgramProgressAdapter.php`.
- Create: `tests/Support/Bi/FakeBiProgramProjectReader.php`.
- Create: `tests/fixtures/bi-programa-general-react/projects.php`.
- Create: `tests/test_bi_program_forecast.php`.
- Create: `tests/test_bi_program_progress.php`.
- Modify: `src/Services/ControlTowerService.php` only after characterization is green.
- Create: `frontend/src/modulos/bi/programa/TitularTerminacion.tsx`.
- Create: `frontend/src/modulos/bi/programa/ResumenAvance.tsx`.
- Create: `frontend/src/modulos/bi/programa/SerieEjecucion.tsx`.
- Create corresponding colocated component tests.

### Step 1: Write pure forecast tests

Use fake project histories for:

- on-time, ahead, delayed and unavailable finish;
- missing contractual baseline;
- fewer than the catalog minimum history samples;
- exactly 240 deterministic simulations;
- ordered P10/P50/P90 output;
- multi-project breakdown with independent cutoffs;
- headline with probable date, range and variance words;
- headline copy that never includes `P50`;
- no invented date in unavailable state.

Run:

    docker compose exec app php tests/test_bi_program_forecast.php

Expected: fail because the adapter does not exist.

### Step 2: Extract the forecast seam

Move only the pure algorithm boundary necessary to feed typed history and return a typed forecast.
Keep the existing seed/determinism, metric-catalog eligibility and 240 simulations. Add a finite
headline formatter using semantic states rather than free-form generated prose.

Delegate the corresponding legacy method back to the adapter and re-run the characterization test
to prove legacy output has not drifted.

### Step 3: Write pure progress tests

Cover:

- inclusive duration weighting;
- zero-duration/invalid dates rejected from the denominator;
- real, theoretical and gap values;
- compliance numerator, denominator and base;
- actual, theoretical and three projected series with aligned ISO dates;
- probable versus observed delay as separate fields;
- explicit limitation that observed activity delay is not additive at project level;
- link query to S19 preserving scope and filters.

Run:

    docker compose exec app php tests/test_bi_program_progress.php

Expected: fail before extraction and pass after `BiProgramProgressAdapter` delegates the legacy
formula.

### Step 4: Build accessible presentational components test-first

Tests must assert:

- titular uses natural-language date/range/variance and has unavailable copy;
- values expose units, base and cutoff;
- projection toggle is a real button with `aria-expanded` and `aria-controls`;
- SVG has `title` and `desc`;
- the full textual date/value table is keyboard reachable;
- visible S19 link is an `href`, not a click handler;
- no calculation occurs in React.

Run:

    npm --prefix frontend test -- TitularTerminacion ResumenAvance SerieEjecucion

Expected: all focused component tests pass after the minimum components exist.

### Step 5: Source invariant

Assert `PdoBiProgramProjectReader`:

- receives authorized numeric project IDs from the service;
- binds IDs and dates;
- has no dynamic table prefix or client-supplied database name;
- performs SELECT-only access;
- never updates baseline or history.

Do not execute it against MySQL.

### Step 6: Future checkpoint

Re-run characterization plus focused pure/frontend tests. In an authorized implementation session,
commit the seam as one vertical slice:

    git add src/Services/Bi/Program tests/Support/Bi/FakeBiProgramProjectReader.php tests/fixtures/bi-programa-general-react/projects.php tests/test_bi_program_forecast.php tests/test_bi_program_progress.php src/Services/ControlTowerService.php frontend/src/modulos/bi/programa/TitularTerminacion* frontend/src/modulos/bi/programa/ResumenAvance* frontend/src/modulos/bi/programa/SerieEjecucion*
    git commit -m "feat(bi): extract programa forecast and progress"

## Task 4: Add combined risk and read-only schedule value

**Acceptance:** S18-AC-37, S18-AC-38, S18-AC-39, S18-AC-40, S18-AC-41, S18-AC-42,
S18-AC-43, S18-AC-44, S18-AC-45, S18-AC-46, S18-AC-47, S18-AC-48, S18-AC-49.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramRiskAdapter.php`.
- Create: `src/Services/Bi/Program/BiProgramScheduleValueReader.php`.
- Create: `tests/test_bi_program_risk.php`.
- Create: `tests/test_bi_program_schedule_value.php`.
- Modify: `src/Services/Bi/RiskScoringService.php` only for delegation, never formula changes.
- Create: `frontend/src/modulos/bi/programa/RiesgoPrograma.tsx`.
- Create: `frontend/src/modulos/bi/programa/ValorCronograma.tsx`.
- Create corresponding component tests.

### Step 1: Freeze risk formula and explanation

Write failing pure tests for each isolated driver and a mixed example:

    score = 35*p + 25*i + 20*u + 10*c + 10*d

Assert:

- component values and their normalized inputs are returned;
- multi-project result retains per-project scores instead of averaging;
- active restrictions, oldest age and critical links are visible evidence;
- missing restriction age/criticality yields partial evidence, not zero;
- formula version and limitations are included.

Run:

    docker compose exec app php tests/test_bi_program_risk.php

### Step 2: Implement the smallest risk adapter

Delegate to `RiskScoringService`, add typed explanatory evidence, and preserve its version and
ordering. Do not change weights, thresholds or sources.

### Step 3: Write schedule-value tests before the reader

Static fake cases:

- one active, non-obsolete budget version with complete mappings;
- partial value/activity coverage;
- no active version;
- obsolete-only version;
- mapping whose `unique_id` exists in another project;
- orphan mapped value;
- `PV=0`;
- invalid monetary value;
- multi-project currencies that cannot consolidate;
- absent actual-cost source.

Assert exact PV/EV/SV/SPI formulas, currency, cutoff, coverage by value and activity, and
`available|partial|insufficient` discriminants. Assert the output contains no
`actualCost`, `CPI`, `CV`, `costVariance`, `overrun` or synonym.

Run:

    docker compose exec app php tests/test_bi_program_schedule_value.php

Expected: fail because the read model is absent.

### Step 4: Implement read-only schedule value

Read only the same project's active/non-obsolete budget version, items/APU inputs and
`pdc_insumo_actividades` mappings. Join every mapping with both `project_id` and
`unique_id`. Never distribute orphan value and never invoke amarre recalculation.

For partial coverage, return the covered numerator/denominator and label that calculations apply
only to the covered scope. For insufficient coverage, money values are null.

### Step 5: Render risk and schedule value

Component tests assert:

- risk is explained with formula, drivers, restrictions/age/criticality and limitations;
- schedule value shows PV/EV/SV/SPI only when permitted by its status;
- partial scope copy and coverage are adjacent to figures;
- insufficient has no currency figure;
- mandatory copy says this is schedule value and no reconciled actual cost is available;
- no cost-performance claim appears.

Run:

    npm --prefix frontend test -- RiesgoPrograma ValorCronograma

### Step 6: Prove read-only/project scope statically

Extend `tests/test_bi_program_source_invariants.php` to reject mutation verbs, unbound project
scope, dynamic DB/prefix selection and any call to amarre mutation helpers. Do not connect to the
database.

### Step 7: Future checkpoint

Re-run focused PHP/component tests and contract characterization. In an authorized session,
commit:

    git add src/Services/Bi/Program/BiProgramRiskAdapter.php src/Services/Bi/Program/BiProgramScheduleValueReader.php src/Services/Bi/RiskScoringService.php tests/test_bi_program_risk.php tests/test_bi_program_schedule_value.php tests/test_bi_program_source_invariants.php frontend/src/modulos/bi/programa/RiesgoPrograma* frontend/src/modulos/bi/programa/ValorCronograma*
    git commit -m "feat(bi): explain risk and schedule value"

## Task 5: Stabilize PHP envelopes, Zod contracts, gateway and lineage

**Acceptance:** S18-AC-70, S18-AC-74, S18-AC-82, S18-AC-86.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramReadService.php`.
- Create: `src/Services/Bi/Program/BiProgramPresenter.php`.
- Create: `src/Services/Bi/Program/BiProgramDetailPresenter.php`.
- Modify: `src/Controllers/Api/BiControlTowerApiController.php`.
- Modify: `src/Services/ControlTowerService.php`.
- Modify: `src/Services/Bi/LineageService.php`.
- Modify: `src/Services/Bi/MetricDictionaryService.php`.
- Create: `tests/test_api_bi_program_contract.php`.
- Create: `tests/test_bi_program_source_invariants.php`.
- Create the six schema files and gateway listed in File Structure.
- Create colocated schema/gateway tests.

### Step 1: Write failing canonical-envelope tests

For all seven routes, assert:

- success is `{ok:true,data}`;
- failure is `{ok:false,error:{code,message,retryable,fieldErrors}}`;
- scope, period, filters, cutoff and completeness are explicit;
- detail pagination always has `limit`, `offset`, `total`, `returnedCount`,
  `nextOffset`, `hasMore`;
- compliance accepts target offset;
- denied scope contains no data;
- optional section failure produces a coherent partial main snapshot;
- lineage has metric key, formula/version, source label, cutoff, completeness and limitation.

Run:

    docker compose exec app php tests/test_api_bi_program_contract.php

Expected: fail on the current mixed envelopes.

### Step 2: Implement service and presenters

`BiProgramReadService` receives only the authorized `BiQuery` and section adapters. It acquires
one snapshot context, passes it to every section and records partial failures without mixing
cutoffs. The controller only maps HTTP/query/access to service and presenter.

`BiProgramPresenter` emits the canonical React envelope and, through a named compatibility
method, the legacy shape. Do not leave anonymous array rewrites in the controller.

### Step 3: Define strict Zod building blocks

Write invalid fixture tests first for:

- malformed ISO dates and project IDs;
- unordered forecast percentiles;
- available forecast without 240 samples;
- unavailable forecast with dates;
- inconsistent progress gap;
- schedule value status/value mismatch or any cost field;
- radar display above 100, missing denominator or unavailable axis with value;
- duplicate activity key per page;
- incoherent pagination;
- causal detail without `readOnly=true` and `actionAvailable=false`.

Then implement schemas using shared BI primitives and `z.infer` exports only.

Run:

    npm --prefix frontend test -- biProgramaGeneral biProgramaCumplimientoDetalle biProgramaAvanceDetalle biProgramaRetrasoDetalle biProgramaRadarDetalle biProgramaCausalDetalle

### Step 4: Implement the seven-method gateway

`frontend/src/lib/api/biProgramaGeneral.ts` may import only `cliente.ts`, schemas and the
canonical query encoder. Each method accepts `AbortSignal`, calls its existing route and parses
before returning.

Add a source test that fails on direct `fetch(` anywhere below
`frontend/src/modulos/bi/programa`.

Run:

    npm --prefix frontend test -- biProgramaGeneral
    rg -n "fetch\\s*\\(" frontend/src --glob '!lib/api/cliente.ts'

Expected: gateway tests pass; the search returns no S18 caller.

### Step 5: Display lineage through T03

Map every headline/metric/detail to the shared `LinajeDrawer`. Lineage stays server-authored and
must expose source/formula/version/cutoff/completeness/limitation without raw table or credential
details.

### Step 6: Safe PHP gate

Run:

    docker compose exec app php tests/test_bi_program_query.php
    docker compose exec app php tests/test_api_bi_program_contract.php
    docker compose exec app php tests/test_bi_program_source_invariants.php
    docker compose exec app php tests/test_bi_program_routes.php

Expected: all pass using fakes/static source inspection; no MySQL fixture setup.

### Step 7: Future checkpoint

In an authorized execution session, commit the canonical boundary:

    git add src/Services/Bi/Program src/Controllers/Api/BiControlTowerApiController.php src/Services/ControlTowerService.php src/Services/Bi/LineageService.php src/Services/Bi/MetricDictionaryService.php tests/test_api_bi_program_contract.php tests/test_bi_program_source_invariants.php frontend/src/lib/api/esquemas/biPrograma* frontend/src/lib/api/biProgramaGeneral*
    git commit -m "feat(bi): add typed programa general contracts"

## Task 6: Assemble the coherent main sheet and activity snapshot

**Acceptance:** S18-AC-30, S18-AC-31, S18-AC-71.

**Files:**

- Create: `frontend/src/modulos/bi/ProgramaGeneralBiPagina.tsx`.
- Create: `frontend/src/modulos/bi/programa/useProgramaGeneralBi.ts`.
- Create: `frontend/src/modulos/bi/programa/ActividadesPrograma.tsx`.
- Create: `frontend/src/modulos/bi/programa/programa-general-bi.css`.
- Create corresponding page/hook/component tests.
- Modify: `frontend/src/main.tsx` only if needed for CSS registration.

### Step 1: Write the main state-machine tests

Cover:

- loading, ready, partial, empty, offline, invalid-query and server-error states;
- atomic main-response replacement;
- stale response ignored after scope/period/filter change;
- explicit refetch status;
- partial section preserves the coherent successful sections;
- zero remains distinct from null/unavailable;
- cache/query key includes user, project scope, period and filters.

Run:

    npm --prefix frontend test -- useProgramaGeneralBi ProgramaGeneralBiPagina

Expected: fail before hook/page creation.

### Step 2: Build the page around T03

Use `MarcoBi`, `FiltrosBi`, shared status and lineage. Render in this order:

1. sheet heading/scope;
2. finish headline;
3. risk and schedule value;
4. progress/execution series;
5. radar placeholder owned by Task 7;
6. activity snapshot;
7. causal headline placeholder owned by Task 9;
8. evidence/lineage.

Do not recalculate or merge metric payloads in components.

### Step 3: Build the initial activity snapshot

Tests assert exactly the first 25 ordered records from the server and preserve:

- activity key/name/project;
- contribution;
- critical marker;
- observed delay;
- planned/current finish;
- restriction/blocking evidence;
- status text and metric lineage.

At 768 and above render a semantic table. Below 768 render one editable-free read card per
activity. This BI sheet remains read-only.

### Step 4: Verify snapshot coherence

Inject fixtures where one section has a different cutoff and assert the Zod/main-state boundary
rejects it rather than mixing cards. Inject one optional section error with the same snapshot
context and assert `partial` remains usable.

### Step 5: Future checkpoint

Run focused tests and typecheck:

    npm --prefix frontend test -- ProgramaGeneralBiPagina useProgramaGeneralBi ActividadesPrograma
    npm --prefix frontend run typecheck

In an authorized session, commit:

    git add frontend/src/modulos/bi/ProgramaGeneralBiPagina* frontend/src/modulos/bi/programa/useProgramaGeneralBi* frontend/src/modulos/bi/programa/ActividadesPrograma* frontend/src/modulos/bi/programa/programa-general-bi.css frontend/src/main.tsx
    git commit -m "feat(bi): assemble programa general decision sheet"

## Task 7: Correct and render the accessible radar

**Acceptance:** S18-AC-50, S18-AC-51, S18-AC-52, S18-AC-53, S18-AC-54, S18-AC-55,
S18-AC-56, S18-AC-57, S18-AC-58.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramRadarAdapter.php`.
- Create: `tests/test_bi_program_radar_contract.php`.
- Create: `frontend/src/modulos/bi/programa/RadarPrograma.tsx`.
- Create: `frontend/src/modulos/bi/programa/DetalleRadar.tsx`.
- Create corresponding frontend tests.

### Step 1: Characterize each radar axis with pure fixtures

Assert:

- keys/labels are `productividad/Avance promedio`, `eficiencia/Eficiencia`,
  `desempeno/Desempeño`;
- only active, non-TNP, valid records enter;
- minimum sample is three per axis;
- raw efficiency above 100 remains raw while display is 100;
- missing sample returns unavailable with null raw/display, never zero;
- numerator, denominator, formula, sample and exclusion reasons are present;
- project breakdown retains independent eligibility.

Run:

    docker compose exec app php tests/test_bi_program_radar_contract.php

### Step 2: Extract and delegate the radar adapter

Use the current live formulas, rename the first axis from the misleading productivity label to
Avance promedio, and keep all source lineage. Do not read the empty/incorrect productivity source.
Delegate legacy output through its compatibility presenter.

### Step 3: Build SVG plus equivalent table

Component tests assert:

- SVG uses a fixed 0–100 coordinate system and token-backed styles;
- each axis is labelled and described;
- raw above 100 is available in text while the point remains within the plot;
- unavailable axes show neutral text, not a center-point zero;
- a complete table lists raw/display/numerator/denominator/formula/sample;
- axis tabs use proper ARIA roles, arrow keys, Home/End and visible focus;
- touch target is at least 44x44;
- no hover or color-only information.

Run:

    npm --prefix frontend test -- RadarPrograma DetalleRadar

### Step 4: Detail-state integration

Use shared `useDetalleBi` from Task 8 when available; until then keep detail transport behind a
small prop seam. Changing axis must reset offset, abort the previous GET and preserve focus.

### Step 5: Future checkpoint

Run PHP, component and contract tests. In an authorized session, commit:

    git add src/Services/Bi/Program/BiProgramRadarAdapter.php tests/test_bi_program_radar_contract.php frontend/src/modulos/bi/programa/RadarPrograma* frontend/src/modulos/bi/programa/DetalleRadar*
    git commit -m "feat(bi): add accessible programa radar"

## Task 8: Implement all six paginated detail experiences

**Acceptance:** S18-AC-32, S18-AC-33, S18-AC-34, S18-AC-35, S18-AC-36, S18-AC-67,
S18-AC-68, S18-AC-69, S18-AC-72, S18-AC-73.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramDetailPresenter.php` if not completed in Task 5.
- Create: `tests/test_bi_program_detail_contract.php`.
- Create: `frontend/src/modulos/bi/programa/useDetalleBi.ts`.
- Create: `frontend/src/modulos/bi/programa/DetalleCumplimiento.tsx`.
- Create: `frontend/src/modulos/bi/programa/DetalleAvance.tsx`.
- Create: `frontend/src/modulos/bi/programa/DetalleRetraso.tsx`.
- Integrate: `frontend/src/modulos/bi/programa/DetalleRadar.tsx`.
- Create corresponding hook/component tests.

### Step 1: Write uniform PHP detail contract tests

For compliance, progress, delay, radar, CNP and CNC:

- `limit` accepts 1..100;
- `offset` is non-negative;
- total/returnedCount/nextOffset/hasMore are coherent;
- out-of-range pagination returns empty records with the same summary;
- every record has a stable key;
- every detail reuses canonical scope/period/filters.

Additionally progress supports:

- `sort=all|missing|earned`;
- `critical_only`;
- `grouping=project|stage|responsible|subcontractor`.

Run:

    docker compose exec app php tests/test_bi_program_detail_contract.php

### Step 2: Implement the shared detail reducer/hook

States:

- closed;
- initial loading;
- ready;
- empty;
- loading more;
- append error retaining rows;
- fatal error;
- retrying.

Key by detail kind plus canonical query plus detail controls. Each request gets an ID and
`AbortSignal`; stale responses are ignored. Changing sort/group/critical/axis/category clears
pages and restarts at offset zero. Append deduplicates by stable record key.

### Step 3: Write hook race/failure tests

Use deferred promises to prove:

- late response from previous query cannot append;
- load-more error retains prior rows and next retry uses the same offset;
- duplicate records are ignored;
- fatal initial error has no stale rows;
- retry and load-more call GET gateway methods only;
- close aborts and returns focus to opener.

Run:

    npm --prefix frontend test -- useDetalleBi

### Step 4: Build accessible detail surfaces

Use the T03 dialog/drawer primitive:

- initial focus on heading or first meaningful control;
- trapped tab sequence;
- Escape closes;
- opener focus returns;
- visible Close button;
- loading/empty/error/retry announced only after user action;
- >=768 semantic table, <768 cards only;
- buttons are real controls and 44x44.

Compliance, progress and delay get their own semantic record renderer. Radar plugs into the same
state machine. Causal details plug in during Task 9.

### Step 5: Future checkpoint

Run:

    docker compose exec app php tests/test_bi_program_detail_contract.php
    npm --prefix frontend test -- useDetalleBi DetalleCumplimiento DetalleAvance DetalleRetraso DetalleRadar
    npm --prefix frontend run typecheck

In an authorized session, commit:

    git add src/Services/Bi/Program/BiProgramDetailPresenter.php tests/test_bi_program_detail_contract.php frontend/src/modulos/bi/programa/useDetalleBi* frontend/src/modulos/bi/programa/DetalleCumplimiento* frontend/src/modulos/bi/programa/DetalleAvance* frontend/src/modulos/bi/programa/DetalleRetraso* frontend/src/modulos/bi/programa/DetalleRadar*
    git commit -m "feat(bi): add programa general drilldowns"

## Task 9: Preserve causal evidence and prepare the S21 transition

**Acceptance:** S18-AC-59, S18-AC-60, S18-AC-61, S18-AC-62, S18-AC-63, S18-AC-64,
S18-AC-65, S18-AC-66.

**Files:**

- Create: `src/Services/Bi/Program/BiProgramCausalAdapter.php`.
- Create: `tests/test_bi_program_causal_contract.php`.
- Create: `frontend/src/modulos/bi/programa/TitularCausas.tsx`.
- Create: `frontend/src/modulos/bi/programa/DetalleCausal.tsx`.
- Create corresponding component tests.
- Modify: `frontend/src/lib/api/esquemas/biProgramaCausalDetalle.ts`.
- Modify: `frontend/src/shell/rutas.tsx` only for the future S21 href codec, not an S21 route cut.

### Step 1: Freeze CNP/CNC universes

Pure fixtures assert:

- CNP includes only `Activa='0'` and non-empty CNP;
- CNC includes only `Activa IN ('1','NA')` and non-empty CNC;
- original category, canonical category and `known` survive;
- invalid CNC quantity yields `unknown`, never zero;
- summary totals reconcile with detail rows and pagination;
- detail is `readOnly=true`, `actionAvailable=false`.

Run:

    docker compose exec app php tests/test_bi_program_causal_contract.php

### Step 2: Extract the causal seam

Delegate the current formulas/categories without editing data or adding category authority. Keep
impact, suggested action and lineage as explanation only. No create/close/reclassify action is
exposed.

### Step 3: Render only the causal headline in the main canvas

Show CNP/CNC totals, dominant known category or explicit unknown/empty state, and one visible
evidence action. Do not restore two full causal charts to the main sheet.

### Step 4: Keep coexistence details and add canonical destination

`DetalleCausal` uses the Task 8 state machine for both kinds. Add an ordinary link to the S21
destination preserving canonical scope and adding `focus=cnp|cnc` plus normalized
`category` when present. Before S21 cuts, the current S18 detail remains functional. After S21
cuts and passes its deep-link tests, the link becomes the primary action and the temporary detail
may be retired only under Task 11.

### Step 5: Frontend tests

Assert:

- main sheet contains one causal headline, not duplicate charts;
- empty, unknown and known states are distinguishable;
- details have no mutation control;
- destination is a real href with encoded focus/category/scope;
- legacy details still open during coexistence.

Run:

    npm --prefix frontend test -- TitularCausas DetalleCausal

### Step 6: Future checkpoint

Re-run pure, schema, hook and component tests. In an authorized session, commit:

    git add src/Services/Bi/Program/BiProgramCausalAdapter.php tests/test_bi_program_causal_contract.php frontend/src/modulos/bi/programa/TitularCausas* frontend/src/modulos/bi/programa/DetalleCausal* frontend/src/lib/api/esquemas/biProgramaCausalDetalle.ts frontend/src/shell/rutas.tsx
    git commit -m "feat(bi): preserve programa causal evidence"

## Task 10: Cut the SPA route and verify responsive, themes and accessibility

**Acceptance:** S18-AC-01, S18-AC-02, S18-AC-75, S18-AC-76, S18-AC-77, S18-AC-78,
S18-AC-79, S18-AC-80, S18-AC-81, S18-AC-83, S18-AC-87.

**Files:**

- Modify: `frontend/src/App.tsx` or `frontend/src/shell/rutas.tsx`.
- Modify: `src/Controllers/Bi/BiViewController.php`.
- Modify: `public/index.php` only if the existing host mapping needs delegation.
- Modify: `frontend/src/modulos/bi/programa/programa-general-bi.css`.
- Modify: `public/css/tokens.css` only if a missing semantic token is proven.
- Create: `tests/browser/fixtures/bi-programa-general-react.mjs`.
- Create: `tests/browser/bi-programa-general-react.spec.mjs`.
- Create: `tests/browser/bi-programa-general-react.a11y.mjs`.
- Modify shared browser config only if registration is required.

### Step 1: Install interception before navigation

The fixture must:

- intercept T01/T03 context and all seven S18 GETs;
- fail immediately on POST/PUT/PATCH/DELETE;
- fail on any unhandled S18 network request;
- offer A, D, R, V/hidden, denied project, single/multi-project, week/range, partial and error
  variants;
- never log secrets or use a real database.

### Step 2: Write the failing route/capability scenarios

Cover:

- `/bi/programa-general` mounts inside the React shell;
- A/D/R allowed under the gate;
- V/hidden gets 404;
- unauthorized project gets 403 without leaked data;
- browser-supplied role/project authority does not change access;
- refresh and deep links preserve canonical query;
- no `/admin/` route, asset or control is introduced.

### Step 3: Write full interaction scenarios

Cover:

- headline available/ahead/delayed/on-time/unavailable;
- baseline missing;
- risk complete/partial;
- schedule value available/partial/insufficient and no cost claim;
- projections toggle and S19 link;
- radar raw >100, partial and unavailable axis;
- activity table/cards;
- all six details;
- sort/group/critical/axis/category changes;
- load more, append error, retry, dedupe and stale-response race;
- causal S21 link;
- lineage;
- main partial and fatal errors;
- zero versus no data;
- refetch.

### Step 4: Write the accessibility/responsive matrix

Run every relevant scenario at:

- 390x844;
- 480x900;
- 768x1024;
- 1180x820;
- 1440x900.

Validate dark and light capability parity, page overflow <=1 px, keyboard-only use, touch target,
focus-visible, dialog trap/return/Escape, 200 percent zoom, reduced motion, SVG text alternatives,
no color-only/hover/double-click-only action, console clean and zero serious/critical axe findings.

At <768 assert the activity/detail table is not mounted. At >=768 assert cards are not mounted.

### Step 5: Implement CSS from tokens

Use module classes and semantic custom properties from `tokens.css`. If a semantic token is
missing, first add a failing token contract test, then add one documented dark/light token pair.
Reject:

- color literals;
- inline color;
- `!important`;
- fixed page widths;
- CSS-only duplicate table/cards;
- motion without reduced-motion fallback.

### Step 6: Shadow then canonical cut

First mount the page behind the existing BI pilot/shadow decision without changing the public
route. Compare static contract fixtures and browser scenarios. Only after green evidence, map
`/bi/programa-general` to `ProgramaGeneralBiPagina` inside the SPA host.

Keep the same route URL and seven API URLs. Do not touch `/programa-general`, which is the
separate operational Programa General surface.

### Step 7: Run focused browser gates

    npx playwright test tests/browser/bi-programa-general-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-programa-general-react.a11y.mjs --workers=1
    npm --prefix frontend run typecheck

Expected: all scenarios green, zero mutation requests, zero serious/critical axe findings and no
console errors.

Do not update a golden. If a visual baseline appears necessary, stop and request explicit
approval.

### Step 8: Future checkpoint

In an authorized session, commit the cut:

    git add frontend/src/App.tsx frontend/src/shell/rutas.tsx frontend/src/modulos/bi/programa/programa-general-bi.css src/Controllers/Bi/BiViewController.php public/index.php public/css/tokens.css tests/browser/fixtures/bi-programa-general-react.mjs tests/browser/bi-programa-general-react.spec.mjs tests/browser/bi-programa-general-react.a11y.mjs
    git commit -m "feat(bi): cut programa general to react"

Only add paths that actually changed; do not stage untouched optional files.

## Task 11: Prove rollback and defer shared legacy retirement

**Acceptance:** S18-AC-84, S18-AC-85.

**Files:**

- Modify: `docs/superpowers/plans/2026-08-30-s18-bi-programa-general-react.md` — future Cierre
  evidence only.
- Modify: T03 retirement ledger/manifest created by S17.
- Preserve: every legacy shared file listed above.

### Step 1: Prove route-only rollback

In the authorized implementation session, use a temporary local route flag or reversible code
change:

1. route `/bi/programa-general` back to the legacy render;
2. verify A/D/R permitted, V hidden and unauthorized project denied;
3. verify one forecast available and one unavailable fixture through compatibility contracts;
4. restore the React route;
5. rerun the two focused browser suites.

Do not restore, seed, mutate or reconcile data. Rollback is code/routing only.

### Step 2: Record shared legacy callers

Before deleting anything, use source inventory to list every caller of:

- `views/bi/control-tower.php` S18 section/dialogs;
- S18 fetch/render functions in `public/js/modules/bi-spa.js`;
- S18-only CSS selectors;
- Chart.js bindings;
- CNP/CNC detail endpoints/UI.

S18 marks those items `eligible after T03`, never deleted. CNP/CNC UI additionally requires the
S21 deep-link/browser gate.

### Step 3: Final focused verification

Run from a clean, unchanged runtime:

    docker compose exec app php tests/test_bi_program_query.php
    docker compose exec app php tests/test_bi_program_contract_characterization.php
    docker compose exec app php tests/test_bi_program_forecast.php
    docker compose exec app php tests/test_bi_program_progress.php
    docker compose exec app php tests/test_bi_program_risk.php
    docker compose exec app php tests/test_bi_program_schedule_value.php
    docker compose exec app php tests/test_bi_program_radar_contract.php
    docker compose exec app php tests/test_bi_program_causal_contract.php
    docker compose exec app php tests/test_bi_program_detail_contract.php
    docker compose exec app php tests/test_api_bi_program_contract.php
    docker compose exec app php tests/test_bi_program_source_invariants.php
    docker compose exec app php tests/test_bi_program_routes.php
    npm --prefix frontend test -- biPrograma ProgramaGeneralBi programa
    npm --prefix frontend run typecheck
    npx playwright test tests/browser/bi-programa-general-react.spec.mjs --workers=1
    npx playwright test tests/browser/bi-programa-general-react.a11y.mjs --workers=1
    git diff --check

Read and record every return code on its own line. If any command is red, S18 is not complete.

### Step 4: Whole-program boundary checks

Verify:

- `rg -n "fetch\\s*\\(" frontend/src --glob '!lib/api/cliente.ts'` has no S18 direct fetch;
- `rg -n "(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)"` across new S18 backend/test
  files has no executable mutation;
- `git diff --name-only` contains no `admin/`, migration, schema, RLS, grant, credential or data
  path;
- no visual golden changed;
- no shared legacy file was deleted.

### Step 5: Close and publish only under repository policy

Update `## Cierre` with exact SHA, commands, results, limitations, zero data touched and the
deferred retirement ledger. Then follow the repository PR/CI gate. Production deploy still
requires a separate explicit authorization.

## Traceability Matrix

Each acceptance criterion is owned exactly once:

| Criterion | Owning task |
|---|---:|
| S18-AC-01 | 10 |
| S18-AC-02 | 10 |
| S18-AC-03 | 1 |
| S18-AC-04 | 1 |
| S18-AC-05 | 1 |
| S18-AC-06 | 1 |
| S18-AC-07 | 1 |
| S18-AC-08 | 1 |
| S18-AC-09 | 1 |
| S18-AC-10 | 2 |
| S18-AC-11 | 2 |
| S18-AC-12 | 1 |
| S18-AC-13 | 3 |
| S18-AC-14 | 3 |
| S18-AC-15 | 3 |
| S18-AC-16 | 3 |
| S18-AC-17 | 3 |
| S18-AC-18 | 3 |
| S18-AC-19 | 3 |
| S18-AC-20 | 3 |
| S18-AC-21 | 3 |
| S18-AC-22 | 3 |
| S18-AC-23 | 3 |
| S18-AC-24 | 3 |
| S18-AC-25 | 3 |
| S18-AC-26 | 3 |
| S18-AC-27 | 3 |
| S18-AC-28 | 3 |
| S18-AC-29 | 3 |
| S18-AC-30 | 6 |
| S18-AC-31 | 6 |
| S18-AC-32 | 8 |
| S18-AC-33 | 8 |
| S18-AC-34 | 8 |
| S18-AC-35 | 8 |
| S18-AC-36 | 8 |
| S18-AC-37 | 4 |
| S18-AC-38 | 4 |
| S18-AC-39 | 4 |
| S18-AC-40 | 4 |
| S18-AC-41 | 4 |
| S18-AC-42 | 4 |
| S18-AC-43 | 4 |
| S18-AC-44 | 4 |
| S18-AC-45 | 4 |
| S18-AC-46 | 4 |
| S18-AC-47 | 4 |
| S18-AC-48 | 4 |
| S18-AC-49 | 4 |
| S18-AC-50 | 7 |
| S18-AC-51 | 7 |
| S18-AC-52 | 7 |
| S18-AC-53 | 7 |
| S18-AC-54 | 7 |
| S18-AC-55 | 7 |
| S18-AC-56 | 7 |
| S18-AC-57 | 7 |
| S18-AC-58 | 7 |
| S18-AC-59 | 9 |
| S18-AC-60 | 9 |
| S18-AC-61 | 9 |
| S18-AC-62 | 9 |
| S18-AC-63 | 9 |
| S18-AC-64 | 9 |
| S18-AC-65 | 9 |
| S18-AC-66 | 9 |
| S18-AC-67 | 8 |
| S18-AC-68 | 8 |
| S18-AC-69 | 8 |
| S18-AC-70 | 5 |
| S18-AC-71 | 6 |
| S18-AC-72 | 8 |
| S18-AC-73 | 8 |
| S18-AC-74 | 5 |
| S18-AC-75 | 10 |
| S18-AC-76 | 10 |
| S18-AC-77 | 10 |
| S18-AC-78 | 10 |
| S18-AC-79 | 10 |
| S18-AC-80 | 10 |
| S18-AC-81 | 10 |
| S18-AC-82 | 5 |
| S18-AC-83 | 10 |
| S18-AC-84 | 11 |
| S18-AC-85 | 11 |
| S18-AC-86 | 5 |
| S18-AC-87 | 10 |

## Vertical Checkpoints

1. **Contract and finish decision:** Tasks 1–3 green. Access/query, seven legacy paths,
   contractual finish, progress, series, delay separation and S19 link are testable.
2. **Operational explanation:** Tasks 4–6 green. Risk, schedule value, lineage, coherent sheet and
   initial activities are useful without any drilldown.
3. **Radar and drilldowns:** Tasks 7–8 green. Correct radar and all six robust details operate with
   pagination/race/error/accessibility coverage.
4. **Causes and cut:** Tasks 9–11 green. Causal transition, route, five viewports, both themes,
   accessibility, rollback and deferred retirement are proven.

Do not advance to the next checkpoint while a focused check is red.

## Definition of Done

S18 is complete only when:

- all 87 criteria above have fresh evidence;
- all seven current GET routes have PHP and Zod contract coverage;
- `/bi/programa-general` is served by the main React SPA;
- A/D/R and denied-role/project behavior is proven;
- forecast, progress, series, risk, schedule value, radar, activities, causes, six details and
  lineage are observable and accessible;
- dark/light and all five viewports pass without page overflow;
- no direct component fetch or mutation request exists;
- no database, RLS, schema, grant, user, credential or data change occurred;
- no shared legacy asset or golden was prematurely removed/updated;
- rollback is route-only and proven;
- the implementation session records a clean verification SHA and follows the PR/CI publication
  policy.

## Cierre

Pendiente. This plan is documentation only and authorizes no implementation, commit, publication
or deploy.
