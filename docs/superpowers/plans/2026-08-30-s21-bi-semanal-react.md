---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [bi, design-system]
fuente: docs/superpowers/plans/2026-08-30-s21-bi-semanal-react.md
resumen: "migrate /bi/semanal into the main React SPA as one read-only weekly/daily decision sheet: project-specific cutoff, truthful PAC with numerator and denominator…"
---

# S21 BI Programacion Semanal React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in order
> and stop at every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and
> closure live in Cierre and git history, never in checkbox counts.

**Goal:** migrate `/bi/semanal` into the main React SPA as one read-only weekly/daily decision
sheet: project-specific cutoff, truthful PAC with numerator and denominator, commitment-count
counterweight, per-commitment noncompliance risk, canonical CNC/CNP causes, capture coverage,
TNP/crisis adoption, contextual actions and lineage, equivalent on desktop, tablet, mobile, dark
and light, without changing RLS, permissions, schema or data.

**Architecture:** T01 owns session, project, shell, sidebar, routes, theme and the only HTTP client.
T03 owns BI admission, authorized scope, period/filter query, shared states and contextual drawer.
S21 enriches the existing `GET /api/bi/report/semanal` through one `BiSemanalReadService`. Pure
projectors own period, PAC, history, risk features/levels, causes, adoption and factual copy. The
existing `ForecastService` delegates its weighted calculation to one extracted policy. The main
snapshot includes commitment detail; only CNP/CNC pagination reuses the two existing shared GETs.
React renders server decisions and never mutates. Canonical and legacy presenters share one model
until caller census permits retirement.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/SVG/CSS and existing AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s21-bi-semanal-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or
  another worktree.
- Inspect status and the relevant diff before every task. Preserve existing work; never clean,
  revert or reformat adjacent files.
- This session is documentation-only. Future commit commands do not authorize implementation,
  install, build, commit, push, PR, publication or deploy now.
- Start implementation only after the required T01, T03, S08, S09, S10, S17, S18 and S20
  primitives exist and pass focused tests. Reuse them instead of forking shared infrastructure.
- S08 owns weekly-programming writes, S09 CNP, S10 CNC and S20 restriction management. S21 only
  reads and navigates through server-authorized hrefs.
- `/admin/` is excluded. Do not modify its code, routes, role UI, tests or assets.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, triggers, grants, users, credentials, memberships, roles, aliases, permissions, flags
  or data.
- No DDL/DML. Rollback inside a transaction is still DML. New PHP tests use pure services, fakes,
  call logs, source/reflection checks and static synthetic fixtures.
- Inspect existing tests before running them; skip and record any suite that writes.
- Preserve `BiPreviewAccessPolicy`, per-project `lps.indicadores.ver` and `BiProjectScope`.
  Hidden modules return 404; unauthorized projects return 403 without data.
- Client project, role, permission, capability, db or prefix values never grant authority.
- Active authorized project is default. Multi-project is explicit and preserves project-qualified
  rows, periods, phases, histories and breakdowns. The admitted A/D/R payload does not vary by
  audience.
- Resolve periods from `semanas_activas` and real project dates. Never silently use Monday, ISO
  week, numeric subtraction or 28-day history.
- On `Fecha_Fin_Sem` open the closing week and expose next week only if an authorized row exists.
  Missing comparison is `insufficient`.
- Preserve T03 scope, `semana`/`desde`/`hasta`, `sub`, `resp`, `etapa`, `focus`,
  `category` and compound `commitment` query semantics.
- One authorized query snapshot governs every compatible block. Unsupported filters produce a
  typed nearby limitation.
- The existing weekly GET is the sole initial request. Reuse exactly the existing
  `programa-general/cnp-detail` and `programa-general/cnc-detail` GETs. Add no endpoint, alias,
  risk-detail route or mutation.
- Keep canonical and compatibility output separate. The legacy scorecard/donut is not target
  semantics.
- PAC population is active `1/NA` and non-TNP. Null is unrecorded, not zero. Aggregate raw
  numerators/denominators; never average project percentages.
- Compare only the immediately prior real project week. History uses real dated cuts, up to six
  selected points and one marked prior context point.
- `PPC` remains a legacy alias; React exposes one PAC metric. Always show the D91 caveat that PAC
  is not by itself a personal evaluation.
- Risk history uses four strictly prior project weeks; selected/future rows never enter.
- Contractor and responsible PAC each require three registered observations. Any missing/invalid
  feature is insufficient; never impute zero or a global average.
- Extract one pure `PacExpectedPolicy` and preserve weights 25/20/15/20/10/10.
- `WEEKLY_RISK_LEVELS_1.0` is server-owned: below 0.60 high, below 0.80 medium, otherwise low;
  null/invalid/short sample insufficient.
- Raw expected score never crosses HTTP. Only high is notification-eligible. S21 sends no email
  and executes no notification side effect.
- Order risk high/medium/low/insufficient, then critical, earliest start and stable
  `projectId:week:rowId`.
- `bi_riesgos` and `fulfillment_alert` remain compatibility-only. They do not govern React.
- All headline, reason, action and risk text is plain. Never use `dangerouslySetInnerHTML`.
- Risk detail travels in the main snapshot and opens without another request.
- CNP/CNC population, validation and completeness reuse S09/S10. Missing, incomplete,
  inconsistent and unknown rows remain visible.
- `recordedBy` is explicitly unavailable. Never relabel responsible as recorder, author or
  culprit; no personal performance ranking.
- TNP/crisis zero means “not registered”, not “did not happen”.
  `reprogramaciones_semanales` must not be read, rendered or reintroduced.
- Do not persist 90-day verdicts, action-open events or telemetry.
- Server capabilities author hrefs to S08/S09/S10/S20 with authorized context only.
- Only `frontend/src/lib/api/cliente.ts` may call fetch. Every success/error is Zod-parsed and
  TypeScript types derive from `z.infer`.
- Query changes abort old reads; stale completions are ignored. Cache identity includes user,
  projects, period, filters, focus and cause category.
- Below 768 mount cards only; at/above 768 mount semantic tables only, for commitments and causes.
- Reuse the T03 drawer with initial focus, trap, Escape and focus return.
- Use native SVG plus visible text/table. Never mix count and percentage axes or depend on hover,
  canvas or color.
- Use `public/css/tokens.css`. No color literals, `!important`, inline colors, local theme/token
  families or new UI/table/chart/state library.
- Dark is default/fallback and light is equivalent. Validate 390x844, 480x900, 768x1024,
  1180x820 and 1440x900, plus 200% zoom, 44px touch, reduced motion and axe serious/critical zero.
- Browser evidence is fully intercepted and fails on any unexpected request or mutation.
- Do not regenerate or commit visual goldens without explicit approval.
- Keep legacy scorecard/chart compatibility and shared cause endpoints while callers remain.
  Shared BI retirement belongs to T03 after S17–S24.
- Rollback changes route/code only and never restores data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read close records for T01, T03, S08, S09, S10, S17, S18 and S20.
2. Verify shared `BiSheetAccessPolicy`, `BiProjectScope`, `BiQueryParser`, project-period value,
   `MarcoBi`, `FiltrosBi`, `EstadoBi`, `LinajeDrawer`, responsive drawer and API client exist.
3. Verify S09/S10 canonical cause seams and S20 authorized navigation seam. If absent, execute the
   owning plan; do not rebuild them inside S21.
4. Inspect `ForecastService` and its safe tests; record inputs, weights, rounding and output before
   extraction.
5. Inspect every proposed existing test for writes.
6. Verify branch, status and runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch; services `app`, `db`, `adminer`; mounted app healthy.
7. Record starting SHA and pre-existing changed paths. Do not stage them.
8. Characterize route/controller/view/JS callers read-only. Copy no credentials or real personal
   data to fixtures.

## File Structure

### Create — backend

- `src/Services/Bi/Semanal/BiSemanalReadService.php`.
- `src/Services/Bi/Semanal/WeeklyPeriodResolver.php`.
- `src/Services/Bi/Semanal/WeeklyCommitmentReader.php`.
- `src/Services/Bi/Semanal/DatabaseWeeklyCommitmentReader.php`.
- `src/Services/Bi/Semanal/WeeklyPacProjector.php`.
- `src/Services/Bi/Semanal/WeeklyRiskFeatureAssembler.php`.
- `src/Services/Bi/Semanal/WeeklyRiskLevelPolicy.php`.
- `src/Services/Bi/Semanal/WeeklyCauseSummary.php`.
- `src/Services/Bi/Semanal/WeeklyAdoptionSummary.php`.
- `src/Services/Bi/Semanal/WeeklyHeadline.php`.
- `src/Services/Bi/Semanal/WeeklyActionProjector.php`.
- `src/Services/Bi/Semanal/BiSemanalPresenter.php`.
- `src/Services/Bi/Forecast/PacExpectedPolicy.php`.

### Modify — backend integration

- `src/Services/Bi/ForecastService.php` delegates to the extracted policy.
- `src/Services/Bi/MetricDictionaryService.php` updates label/integration metadata only.
- `src/Controllers/Api/BiControlTowerApiController.php` stays thin over the existing GETs.
- `src/Controllers/Bi/BiViewController.php` hands off the page route at cut.
- `public/index.php` only if T01 registration requires it; add no endpoint.
- T03/S09/S10 shared seams only through their intended extension points.

### Create — PHP tests/fixtures

- `tests/support/Bi/FakeWeeklyCommitmentReader.php`.
- `tests/fixtures/bi-semanal-react/{legacy-report,legacy-cnp-detail,legacy-cnc-detail}.php`.
- `tests/fixtures/bi-semanal-react/{project-weeks,pac-cases,risk-history}.php`.
- `tests/fixtures/bi-semanal-react/{causes-adoption,multi-project}.php`.
- `tests/test_bi_semanal_access_period.php`.
- `tests/test_bi_semanal_contract_characterization.php`.
- `tests/test_bi_semanal_pac.php`.
- `tests/test_bi_semanal_pac_expected_policy.php`.
- `tests/test_bi_semanal_risk_features.php`.
- `tests/test_bi_semanal_risk_decision.php`.
- `tests/test_bi_semanal_causes_adoption.php`.
- `tests/test_bi_semanal_read_contract.php`.
- `tests/test_bi_semanal_source_invariants.php`.
- `tests/test_bi_semanal_routes.php`.

### Create — frontend

- `frontend/src/lib/api/esquemas/biSemanal.ts` and test.
- `frontend/src/lib/api/biSemanal.ts` and test.
- `frontend/src/modulos/bi/SemanalPagina.tsx` and test.
- `frontend/src/modulos/bi/semanal/estadoSemanal.ts` and test.
- `frontend/src/modulos/bi/semanal/useBiSemanal.ts` and test.
- `frontend/src/modulos/bi/semanal/ContextoSemana.tsx`.
- `frontend/src/modulos/bi/semanal/{TitularPac,ContrapesoPac,TendenciaPac}.tsx`.
- `frontend/src/modulos/bi/semanal/{ResumenRiesgo,ListaCompromisosRiesgo}.tsx`.
- `frontend/src/modulos/bi/semanal/{TablaCompromisosRiesgo,TarjetasCompromisosRiesgo}.tsx`.
- `frontend/src/modulos/bi/semanal/DetalleCompromiso.tsx`.
- `frontend/src/modulos/bi/semanal/{ResumenCausas,DesgloseCausal,CoberturaCaptura}.tsx`.
- `frontend/src/modulos/bi/semanal/ResumenAdopcion.tsx`.
- Focused tests beside non-trivial components.
- `frontend/src/modulos/bi/semanal/semanal.css`.
- T01 route registry entry for `/bi/semanal`.
- `tests/browser/bi-semanal-react.spec.mjs`.

### Preserve

- Both shared CNP/CNC detail route names and all non-S21 BI callers.
- S08/S09/S10/S20 routes and write controllers.
- `docs/security/rls-runtime-boundary.md` and every schema/migration/data asset.
- Other BI pages and shared Chart.js consumers.

No deletion is required to close S21. Shared legacy BI retirement remains gated by T03 and a
cross-surface zero-caller census.

## Task 1: Lock access, scope and real project-week semantics

**Acceptance:** S21-AC-03 through S21-AC-17.

**Files:** create `tests/test_bi_semanal_access_period.php`,
`tests/fixtures/bi-semanal-react/project-weeks.php` and
`src/Services/Bi/Semanal/WeeklyPeriodResolver.php`; extend T03 only if a primitive is absent.

**Step 1: Write failing pure tests**

Cover admitted A/D/R plus capability, hidden 404, unauthorized 403, active-project default,
explicit multi, ignored authority-like query keys, identical audience shape, explicit week, range
precedence, filters, a Tuesday-start project, today inside/end of a week, next-week availability,
missing comparable row, per-project phases and real-date previous/context selection.

Use injected policies/clock and fixtures only; no MySQL.

**Step 2: Prove RED**

       docker compose exec app php tests/test_bi_semanal_access_period.php

Expected: domain RED for absent resolver, not syntax/autoload failure.

**Step 3: Implement the smallest resolver**

Return authorized scope, mode, per-project start/end/confirmed/closeDate/phase/previous/next,
optional context point, normalized filters and limitations. Reuse T03 parser and do not authorize
inside query DTOs.

**Step 4: Prove GREEN and inspect fallbacks**

       docker compose exec app php tests/test_bi_semanal_access_period.php
       rg -n "format\\('W'\\)|startOfWeek|monday|28 DAY|semana\\s*[-+]\\s*[0-9]" \
         src/Controllers/Api/BiControlTowerApiController.php src/Services/Bi

Expected: test zero; no such fallback on the canonical S21 path.

**Step 5: Future commit**

       git add src/Services/Bi/Semanal/WeeklyPeriodResolver.php \
         tests/test_bi_semanal_access_period.php \
         tests/fixtures/bi-semanal-react/project-weeks.php
       git commit -m "test(bi): lock weekly scope and period"

Do not run it in this planning session.

## Task 2: Characterize existing GETs and freeze compatibility

**Acceptance:** S21-AC-18 through S21-AC-23.

**Files:** create the three legacy fixtures,
`tests/test_bi_semanal_contract_characterization.php`,
`tests/test_bi_semanal_read_contract.php` and
`src/Services/Bi/Semanal/BiSemanalPresenter.php`.

**Step 1: Freeze synthetic measured contracts**

Main fixture includes legacy envelope, metadata, source/count, brief, scorecard, donut, drivers,
risks/actions/lineage, a closed registered cut and an open all-null cut that legacy wrongly calls
zero/high-confidence. Detail fixtures freeze CNP/CNC pagination. Use no real people or secrets.

**Step 2: Write failing contract tests**

Prove one main GET, exactly two shared detail GETs, no new endpoint/mutation, canonical
scope/period/capabilities/coverage/meta, full project-period fields, distinct canonical/legacy
presenter methods and preserved legacy scorecard/chart keys.

**Step 3: RED, minimal presenter, GREEN**

       docker compose exec app php tests/test_bi_semanal_contract_characterization.php
       docker compose exec app php tests/test_bi_semanal_read_contract.php

Expected initial non-zero for absent presenter. Add a query-free presenter over one authorized
model; rerun both to zero.

**Step 4: Prove route uniqueness**

       rg -n "report/semanal|programa-general/(cnp|cnc)-detail" public/index.php src tests

Expected: one main route, two shared detail routes, no weekly alias/write.

**Step 5: Future commit**

       git add src/Services/Bi/Semanal/BiSemanalPresenter.php \
         tests/fixtures/bi-semanal-react tests/test_bi_semanal_contract_characterization.php \
         tests/test_bi_semanal_read_contract.php
       git commit -m "test(bi): characterize weekly report contracts"

## Task 3: Build truthful PAC, comparison and history

**Acceptance:** S21-AC-24 through S21-AC-36.

**Files:** create reader interface/adapter, `WeeklyPacProjector`, fake reader,
`pac-cases.php`, `multi-project.php` and `tests/test_bi_semanal_pac.php`.

**Step 1: Write failing pure formula tests**

Prove active 1/NA and non-TNP eligibility; PAC 1/0/null reconciliation; recorded zero versus
all-null insufficient; N/M/missing display data; real previous cut; count/PAC deltas with null
rules; six dated points plus marked context; aggregate raw counts; per-project reconciliation;
PPC absent canonically; exact D91 caveat.

**Step 2: Prove RED**

       docker compose exec app php tests/test_bi_semanal_pac.php

**Step 3: Implement projector and explicit-column read seam**

Keep eligibility/count/null/comparison/history logic pure and round only at presentation.
Database adapter accepts authorized scope/period/filter parameters, uses prepared statements,
explicit columns and `project_id` on every base/history/join clause. Do not execute it against
MySQL in this task.

**Step 4: GREEN and source invariant**

       docker compose exec app php tests/test_bi_semanal_pac.php
       rg -n "SELECT \\*|project_id|Activa|is_TNP|PAC|PPC" src/Services/Bi/Semanal

Expected: zero; no SELECT star; PPC compatibility-only.

**Step 5: Future commit**

       git add src/Services/Bi/Semanal/WeeklyCommitmentReader.php \
         src/Services/Bi/Semanal/DatabaseWeeklyCommitmentReader.php \
         src/Services/Bi/Semanal/WeeklyPacProjector.php tests/support/Bi \
         tests/fixtures/bi-semanal-react tests/test_bi_semanal_pac.php
       git commit -m "feat(bi): project canonical weekly PAC"

## Task 4: Extract the weighted policy and assemble leak-free risk features

**Acceptance:** S21-AC-37 through S21-AC-49.

**Files:** create `PacExpectedPolicy.php`, `WeeklyRiskFeatureAssembler.php`,
`risk-history.php` and two focused tests; modify `ForecastService.php` and dictionary metadata.

**Step 1: Characterize the existing formula**

Test all valid features, 0/1 boundaries, each missing feature, both short samples, invalid range
and exact 25/20/15/20/10/10 contribution against current `ForecastService`.

**Step 2: Extract without drift**

Make `ForecastService` delegate to one pure policy, then run:

       docker compose exec app php tests/test_bi_forecast_contract.php
       docker compose exec app php tests/test_bi_semanal_pac_expected_policy.php

Inspect the old test first; if it writes, replace only its pure assertions and record exclusion.

**Step 3: Write failing assembler tests**

Prove same normalized contractor/responsible, registered PAC only, four strictly prior real weeks,
no current/future leakage, samples, current criticity, project+week+unique-id restriction join,
finite 0–1 progress, same project/activity prior CNC, project isolation, explicit missing reasons
and no imputation.

**Step 4: RED, implement, GREEN**

       docker compose exec app php tests/test_bi_semanal_risk_features.php

Expected initial domain RED; require zero after the exact six-feature assembler.

**Step 5: Update metric metadata and inspect duplication**

Label `ps_pac_expected` “Riesgo de incumplimiento” with integration
`integrated_in_programacion_semanal` while preserving generic descriptive semantics.

       rg -n "0\\.25|0\\.20|0\\.15|0\\.10|pac_expected" src/Services/Bi

Expected: one production weight definition.

**Step 6: Future commit**

       git add src/Services/Bi/Forecast src/Services/Bi/Semanal/WeeklyRiskFeatureAssembler.php \
         src/Services/Bi/ForecastService.php src/Services/Bi/MetricDictionaryService.php \
         tests/fixtures/bi-semanal-react/risk-history.php \
         tests/test_bi_semanal_pac_expected_policy.php tests/test_bi_semanal_risk_features.php
       git commit -m "feat(bi): assemble weekly commitment risk"

## Task 5: Author risk decisions, headline and safe actions

**Acceptance:** S21-AC-50 through S21-AC-66.

**Files:** create `WeeklyRiskLevelPolicy.php`, `WeeklyHeadline.php`,
`WeeklyActionProjector.php`, `BiSemanalReadService.php` and
`tests/test_bi_semanal_risk_decision.php`; extend presenter.

**Step 1: Write failing decision tests**

Cover 0.599/0.600/0.799/0.800/1 boundaries; insufficient; high-only eligibility; no notification
callback; canonical ordering/key; identity/evidence/sample/reasons; separate observed/predictive
structures; no raw score; legacy generic-risk irrelevance; all phase-aware headline templates;
plain text; embedded detail; capability/href matrix; no mutation/CSRF.

**Step 2: Prove RED**

       docker compose exec app php tests/test_bi_semanal_risk_decision.php

**Step 3: Implement pure policies and compose one model**

Level policy returns visible state/reason/eligibility/sort tuple, not score. Headline returns finite
phase-aware text. Action projector receives server capabilities only. Read service composes
authorized period, PAC, features, risks, placeholders, limitations and lineage.

**Step 4: GREEN and side-effect scan**

       docker compose exec app php tests/test_bi_semanal_risk_decision.php
       rg -n "mail\\(|Mailer|queue|notify|INSERT|UPDATE|DELETE|<b>|<small>|expectedScore" \
         src/Services/Bi/Semanal

Expected: zero and no production side effect/HTML/raw score.

**Step 5: Future commit**

       git add src/Services/Bi/Semanal tests/test_bi_semanal_risk_decision.php
       git commit -m "feat(bi): author weekly risk decisions"

## Task 6: Reuse canonical causes and expose TNP/crisis capture

**Acceptance:** S21-AC-67 through S21-AC-85.

**Files:** create `WeeklyCauseSummary.php`, `WeeklyAdoptionSummary.php`,
`causes-adoption.php` and `tests/test_bi_semanal_causes_adoption.php`; extend read service.

**Step 1: Write failing CNP/CNC tests**

Prove S09/S10 populations including incomplete/unknown/inconsistent/missing; unknown originals;
full cause; quantities/gap/critical/no-responsible; eligible/documented/missing/rate reconciliation;
responsible filter without ranking; `recordedBy.status=unavailable`; responsible never copied;
kind/category/limit/offset/total/hasMore/query; S18 focus/category semantics.

**Step 2: Write failing adoption tests**

Cover TNP total/categorized/missing/coverage, crisis count per project/cut, multi reconciliation,
registered-zero wording, correction href and retired-field absence.

**Step 3: RED, thin adapters, GREEN**

       docker compose exec app php tests/test_bi_semanal_causes_adoption.php

Delegate population/completeness to S09/S10. Project only counts, categories, coverage,
limitations and actions. Adoption describes observed capture; it persists nothing.

       docker compose exec app php tests/test_bi_semanal_causes_adoption.php
       docker compose exec app php tests/test_bi_semanal_read_contract.php
       rg -n "reprogramaciones_semanales|registrador|culpable|desempe" src/Services/Bi/Semanal

Expected: tests zero; retired/accusatory fields absent.

**Step 4: Future commit**

       git add src/Services/Bi/Semanal/WeeklyCauseSummary.php \
         src/Services/Bi/Semanal/WeeklyAdoptionSummary.php \
         src/Services/Bi/Semanal/BiSemanalReadService.php \
         tests/fixtures/bi-semanal-react/causes-adoption.php \
         tests/test_bi_semanal_causes_adoption.php
       git commit -m "feat(bi): summarize weekly causes and adoption"

## Task 7: Parse transport and implement isolated abortable state

**Acceptance:** S21-AC-100 through S21-AC-103.

**Files:** create Zod schema/gateway tests, `estadoSemanal` and `useBiSemanal` with tests; modify
`cliente.ts` only if generic signal/error support is absent.

**Step 1: Write failing Zod tests**

Parse ready/partial/empty/insufficient, closed zero/open null, all risk levels, single/multi
periods, cause pagination and canonical errors. Reject unreconciled counts, invalid enums, missing
meta and forbidden raw-score/unsafe shapes. Export only `z.infer` types.

**Step 2: Write failing gateway/hook tests**

Mock `cliente.ts`, not global fetch. Assert one initial GET, cause GETs only on drilldown,
AbortSignal, parse-before-return, no risk-detail/mutation/CSRF, all ten visible states, old data
during refresh, abort/stale race, safe retry, isolated cache identity and logout invalidation.

**Step 3: Prove RED**

       npm --prefix frontend test -- \
         src/lib/api/esquemas/biSemanal.test.ts src/lib/api/biSemanal.test.ts \
         src/modulos/bi/semanal/estadoSemanal.test.ts \
         src/modulos/bi/semanal/useBiSemanal.test.tsx

**Step 4: Implement minimally and prove GREEN**

Gateway calls the shared client and parses. Hook orchestrates only; no PAC/risk/action policy.

       npm --prefix frontend test -- \
         src/lib/api/esquemas/biSemanal.test.ts src/lib/api/biSemanal.test.ts \
         src/modulos/bi/semanal/estadoSemanal.test.ts \
         src/modulos/bi/semanal/useBiSemanal.test.tsx
       npm --prefix frontend run typecheck
       rg -n "\\bfetch\\s*\\(" frontend/src \
         --glob '!lib/api/cliente.ts' --glob '!**/*.test.*'

Expected: tests/typecheck zero and no production fetch match.

**Step 5: Future commit**

       git add frontend/src/lib/api frontend/src/modulos/bi/semanal/estadoSemanal* \
         frontend/src/modulos/bi/semanal/useBiSemanal*
       git commit -m "feat(frontend): add weekly BI data state"

Stage only named S21/client extension files.

## Task 8: Render PAC/risk and exclusive responsive commitment list

**Acceptance:** S21-AC-86 through S21-AC-90, S21-AC-92, S21-AC-94 and S21-AC-95.

**Files:** create `SemanalPagina`, context/PAC/risk/list/table/cards/detail components and focused
tests.

**Step 1: Write failing page/content tests**

Assert one h1 and exact order: context, PAC, comparison/history, risk, CNC, CNP, adoption,
lineage. Both weekly-meeting and daily-review purpose appear in one page/payload.

**Step 2: Write failing responsive tests**

At 767 mount cards/no table; at 768 table/no cards; resize replaces DOM. Both consume one ordered
model. Table has caption/headers/scopes; cards are a list. Mobile exposes all required fields and
server action. Detail opens from snapshot. Risk meaning is textual and no raw score appears.

**Step 3: Write failing SVG/alternative tests**

Require native SVG title/desc, visible semantic point table, labelled context point, separate
PAC/count scales, click/keyboard access, no hover/canvas-only meaning and 44px controls.

**Step 4: RED, render-only implementation, GREEN**

       npm --prefix frontend test -- \
         src/modulos/bi/SemanalPagina.test.tsx \
         src/modulos/bi/semanal/TablaCompromisosRiesgo.test.tsx \
         src/modulos/bi/semanal/TarjetasCompromisosRiesgo.test.tsx \
         src/modulos/bi/semanal/TendenciaPac.test.tsx \
         src/modulos/bi/semanal/DetalleCompromiso.test.tsx

Render server fields only and reuse shared responsive primitive. Rerun to zero plus typecheck.

**Step 5: Future commit**

       git add frontend/src/modulos/bi/SemanalPagina* frontend/src/modulos/bi/semanal
       git commit -m "feat(frontend): render weekly BI decisions"

## Task 9: Add responsive causal drilldowns in the shared drawer

**Acceptance:** S21-AC-91 and S21-AC-93.

**Files:** create cause/coverage/adoption components and tests; integrate `SemanalPagina` and T03
drawer.

**Step 1: Write failing interaction tests**

Cover category click/Enter/Space; focus/category deep-link opening and title focus; recoverable
unknown focus; preserved query on pagination/navigation; button/Escape close and focus return;
Tab trap; 767 causal cards only/768 table only; full cause/unknown category/limitation; responsible
filter without ranking; drawer loading/empty/error; exact GET ledger.

**Step 2: Prove RED**

       npm --prefix frontend test -- \
         src/modulos/bi/semanal/ResumenCausas.test.tsx \
         src/modulos/bi/semanal/DesgloseCausal.test.tsx \
         src/modulos/bi/semanal/CoberturaCaptura.test.tsx \
         src/modulos/bi/semanal/ResumenAdopcion.test.tsx

**Step 3: Reuse shared primitives and prove GREEN**

Do not build local modal, focus trap, paginator, breakpoint or parser. Show unavailable recorder as
a source limitation.

       npm --prefix frontend test -- src/modulos/bi/semanal src/modulos/bi/SemanalPagina.test.tsx
       npm --prefix frontend run typecheck

Expected: both zero.

**Step 4: Future commit**

       git add frontend/src/modulos/bi/semanal frontend/src/modulos/bi/SemanalPagina.tsx
       git commit -m "feat(frontend): add weekly cause drilldowns"

## Task 10: Complete theme, accessibility, lineage and safe errors

**Acceptance:** S21-AC-96 through S21-AC-99, S21-AC-104 and S21-AC-105.

**Files:** create `semanal.css` and `tests/test_bi_semanal_source_invariants.php`; modify S21
components/tests; touch `tokens.css` only after a failing shared-token contract.

**Step 1: Write failing source/design tests**

Assert no color literal, important, inline color, local theme, new visual library, HTML injection,
canvas or duplicate responsive DOM. Assert landmarks/headings/live regions/labels, textual
statuses, complete lineage for PAC/risk/causes/adoption/samples/thresholds/filters and safe errors
without SQL/table/path/stack/foreign IDs.

**Step 2: Write failing render-state/theme tests**

Render loading/refreshing/partial/empty/insufficient/offline/invalid/error. Keep old data on
refresh, expose safe retry, and prove dark/light names/actions/data identical. Cover focus-visible,
reduced motion and long Spanish text.

**Step 3: RED, token-driven implementation, GREEN**

       docker compose exec app php tests/test_bi_semanal_source_invariants.php
       npm --prefix frontend test -- src/modulos/bi/SemanalPagina.test.tsx \
         src/modulos/bi/semanal

Use existing tokens and shared `EstadoBi`/`LinajeDrawer`. Then rerun both plus typecheck.

**Step 4: Static scan**

       rg -n "#[0-9a-fA-F]{3,8}|rgb\\(|hsl\\(|!important|dangerouslySetInnerHTML|<canvas|style=.*color" \
         frontend/src/modulos/bi/semanal frontend/src/modulos/bi/SemanalPagina.tsx

Expected: no production matches.

**Step 5: Future commit**

       git add frontend/src/modulos/bi tests/test_bi_semanal_source_invariants.php
       git commit -m "feat(frontend): finish weekly BI accessibility"

Stage only exact S21 files.

## Task 11: Cut the SPA route and prove the surface without mutation

**Acceptance:** S21-AC-02, S21-AC-106 through S21-AC-108, S21-AC-113,
S21-AC-115 and S21-AC-116.

**Files:** create `tests/test_bi_semanal_routes.php` and
`tests/browser/bi-semanal-react.spec.mjs`; modify only the T01 route registry, S21 view handoff and
`public/index.php` if required.

**Step 1: Write failing pure route tests**

Prove page SPA handoff, hidden 404, project 403, one main/two shared GET routes, no mutation,
authorized link context and available compatibility adapter.

       docker compose exec app php tests/test_bi_semanal_routes.php

Expected RED because the page is legacy. Cut only S21 and rerun to zero.

**Step 2: Build a fully intercepted browser contract**

Fixtures cover permitted closing/open/multi, all risk levels, CNP/CNC, registered-zero adoption,
partial/empty/offline/invalid/error, deep links and both weekly/daily rhythms in one page.

Intercept before navigation and throw on undeclared request, any non-GET, login/dev-door/db/admin,
risk-detail/weekly-cause alias, console error or page error.

**Step 3: Run browser evidence**

       npx playwright test tests/browser/bi-semanal-react.spec.mjs --workers=1

Require five viewports, 767/768 exclusive DOM, 200% zoom, no page overflow, dark/light parity,
keyboard/touch/focus/reduced motion, axe serious/critical zero, clean console and exact GET ledger.
Do not regenerate goldens.

**Step 4: Run focused integration**

       docker compose exec app php tests/test_bi_semanal_access_period.php
       docker compose exec app php tests/test_bi_semanal_contract_characterization.php
       docker compose exec app php tests/test_bi_semanal_pac.php
       docker compose exec app php tests/test_bi_semanal_pac_expected_policy.php
       docker compose exec app php tests/test_bi_semanal_risk_features.php
       docker compose exec app php tests/test_bi_semanal_risk_decision.php
       docker compose exec app php tests/test_bi_semanal_causes_adoption.php
       docker compose exec app php tests/test_bi_semanal_read_contract.php
       docker compose exec app php tests/test_bi_semanal_source_invariants.php
       docker compose exec app php tests/test_bi_semanal_routes.php
       npm --prefix frontend test -- biSemanal Semanal semanal
       npm --prefix frontend run typecheck
       npm --prefix frontend run build

Record each return code separately; never chain verification to commit/push.

**Step 5: Future commit**

       git add <exact T01/S21 route files> tests/test_bi_semanal_routes.php \
         tests/browser/bi-semanal-react.spec.mjs
       git commit -m "feat(frontend): cut weekly BI route to React"

## Task 12: Prove coexistence, code-only rollback and untouched boundaries

**Acceptance:** S21-AC-01, S21-AC-109 through S21-AC-112 and S21-AC-114.

**Files:** compatibility presenter only if characterization finds drift; closure record only after
evidence. Preserve shared endpoints/data/RLS/admin.

**Step 1: Census callers**

       rg -n "bi/semanal|report/semanal|renderSemanal|programa-general/(cnp|cnc)-detail" \
         public src views frontend tests docs \
         --glob '!docs/superpowers/plans/2026-08-30-s21-bi-semanal-react.md'

Classify every caller. Cause endpoints stay. Legacy scorecard/chart stays while a caller or
rollback contract remains. S21 requires no deletion.

**Step 2: Exercise code-only rollback**

Using `apply_patch`, temporarily hand the local route back to legacy, run the safe characterization
smoke, restore React, then rerun the route test. Never use destructive git restore and never touch
data. Do not commit the temporary rollback.

**Step 3: Audit forbidden boundaries**

       git diff --name-only
       git diff --check
       git diff -- docs/security/rls-runtime-boundary.md
       git diff -- admin
       git diff -- '*.sql' '*migration*' '.env*'
       rg -n "mail\\(|Mailer|queue|print|export|download|localStorage|sessionStorage" \
         src/Services/Bi/Semanal frontend/src/modulos/bi/semanal

Expected: no admin/RLS/schema/grant/user/credential/migration/SQL/data/env diff and no email,
export, print, download or persisted action.

**Step 4: Invoke verification-before-completion**

Record `git rev-parse HEAD` and run every Task 11 focused command plus the full frontend test,
typecheck, build and intercepted S21 browser suite. Inspect any wider suite before running it.
Read every return code separately.

**Step 5: Review and record future closure**

Record exact SHA/diff; access and single/multi; project periods; PAC zero/null/history; risk
samples/no-leakage/threshold/no-score; cause/adoption attribution; themes/viewports/zoom/a11y;
network/console/zero mutation; caller census; rollback; untouched boundaries; limitations.

**Step 6: Future commit**

       git add <only exact reviewed S21 closure files>
       git commit -m "test(bi): verify weekly React parity"

Do not commit, push, open a PR, publish or deploy in this planning session. Future closure follows
the repository gate and explicit authorization.

## Traceability Matrix

Every S21 acceptance criterion appears exactly once:

| Criterion | Task |
|---|---:|
| S21-AC-01 | 12 |
| S21-AC-02 | 11 |
| S21-AC-03 | 1 |
| S21-AC-04 | 1 |
| S21-AC-05 | 1 |
| S21-AC-06 | 1 |
| S21-AC-07 | 1 |
| S21-AC-08 | 1 |
| S21-AC-09 | 1 |
| S21-AC-10 | 1 |
| S21-AC-11 | 1 |
| S21-AC-12 | 1 |
| S21-AC-13 | 1 |
| S21-AC-14 | 1 |
| S21-AC-15 | 1 |
| S21-AC-16 | 1 |
| S21-AC-17 | 1 |
| S21-AC-18 | 2 |
| S21-AC-19 | 2 |
| S21-AC-20 | 2 |
| S21-AC-21 | 2 |
| S21-AC-22 | 2 |
| S21-AC-23 | 2 |
| S21-AC-24 | 3 |
| S21-AC-25 | 3 |
| S21-AC-26 | 3 |
| S21-AC-27 | 3 |
| S21-AC-28 | 3 |
| S21-AC-29 | 3 |
| S21-AC-30 | 3 |
| S21-AC-31 | 3 |
| S21-AC-32 | 3 |
| S21-AC-33 | 3 |
| S21-AC-34 | 3 |
| S21-AC-35 | 3 |
| S21-AC-36 | 3 |
| S21-AC-37 | 4 |
| S21-AC-38 | 4 |
| S21-AC-39 | 4 |
| S21-AC-40 | 4 |
| S21-AC-41 | 4 |
| S21-AC-42 | 4 |
| S21-AC-43 | 4 |
| S21-AC-44 | 4 |
| S21-AC-45 | 4 |
| S21-AC-46 | 4 |
| S21-AC-47 | 4 |
| S21-AC-48 | 4 |
| S21-AC-49 | 4 |
| S21-AC-50 | 5 |
| S21-AC-51 | 5 |
| S21-AC-52 | 5 |
| S21-AC-53 | 5 |
| S21-AC-54 | 5 |
| S21-AC-55 | 5 |
| S21-AC-56 | 5 |
| S21-AC-57 | 5 |
| S21-AC-58 | 5 |
| S21-AC-59 | 5 |
| S21-AC-60 | 5 |
| S21-AC-61 | 5 |
| S21-AC-62 | 5 |
| S21-AC-63 | 5 |
| S21-AC-64 | 5 |
| S21-AC-65 | 5 |
| S21-AC-66 | 5 |
| S21-AC-67 | 6 |
| S21-AC-68 | 6 |
| S21-AC-69 | 6 |
| S21-AC-70 | 6 |
| S21-AC-71 | 6 |
| S21-AC-72 | 6 |
| S21-AC-73 | 6 |
| S21-AC-74 | 6 |
| S21-AC-75 | 6 |
| S21-AC-76 | 6 |
| S21-AC-77 | 6 |
| S21-AC-78 | 6 |
| S21-AC-79 | 6 |
| S21-AC-80 | 6 |
| S21-AC-81 | 6 |
| S21-AC-82 | 6 |
| S21-AC-83 | 6 |
| S21-AC-84 | 6 |
| S21-AC-85 | 6 |
| S21-AC-86 | 8 |
| S21-AC-87 | 8 |
| S21-AC-88 | 8 |
| S21-AC-89 | 8 |
| S21-AC-90 | 8 |
| S21-AC-91 | 9 |
| S21-AC-92 | 8 |
| S21-AC-93 | 9 |
| S21-AC-94 | 8 |
| S21-AC-95 | 8 |
| S21-AC-96 | 10 |
| S21-AC-97 | 10 |
| S21-AC-98 | 10 |
| S21-AC-99 | 10 |
| S21-AC-100 | 7 |
| S21-AC-101 | 7 |
| S21-AC-102 | 7 |
| S21-AC-103 | 7 |
| S21-AC-104 | 10 |
| S21-AC-105 | 10 |
| S21-AC-106 | 11 |
| S21-AC-107 | 11 |
| S21-AC-108 | 11 |
| S21-AC-109 | 12 |
| S21-AC-110 | 12 |
| S21-AC-111 | 12 |
| S21-AC-112 | 12 |
| S21-AC-113 | 11 |
| S21-AC-114 | 12 |
| S21-AC-115 | 11 |
| S21-AC-116 | 11 |

## Vertical Checkpoints

1. **Authorized period and compatible transport:** Tasks 1–2 prove scope, real per-project cutoffs
   and the existing three GET contracts.
2. **Truthful weekly model:** Tasks 3–5 prove PAC, history, leak-free features, N5, headline and
   server actions without frontend inference or side effects.
3. **Canonical causes and typed client:** Tasks 6–7 reconcile CNP/CNC/TNP/crisis and establish
   Zod-validated abortable isolated state.
4. **Usable sheet:** Tasks 8–10 deliver exclusive table/cards, SVG/text history, shared drawer,
   themes, states, lineage and accessibility.
5. **Cut and boundary proof:** Tasks 11–12 cut only S21, run intercepted evidence, retain
   compatibility and prove code-only rollback plus untouched RLS/data.

Stop after each checkpoint for review in an authorized implementation session. Do not combine
checkpoint commits with unrelated surfaces.

## Definition of Done

- All 116 criteria trace exactly once with fresh focused evidence.
- `/bi/semanal` uses the main SPA and shared shell/sidebar/theme/query/states/drawer.
- One weekly GET loads initially; only the two shared cause GETs load on explicit drilldown.
- A/D/R/project scope is server-authorized; hidden 404 and unauthorized 403 remain.
- Each project uses real cutoff dates, phase, previous/next and history.
- Open all-null PAC is null/insufficient; registered zero remains zero.
- PAC counts, comparison, history, aggregate and project breakdown reconcile; PPC is not duplicated
  and D91 is visible.
- One pure policy owns weights; risk uses four prior real weeks and exact samples/joins.
- Thresholds/order/eligibility/evidence are server-authored; no raw score or notification side
  effect reaches React.
- Causes match S09/S10; missing/unknown/inconsistent records and full text remain recoverable.
- TNP/crisis describe captured records and never revive the retired field or personal ranking.
- Actions are server-capability gated and preserve authorized context.
- Zod validates success/error; only `cliente.ts` calls fetch; stale/cache isolation holds.
- Commitment and cause table/cards mount exclusively at 768.
- Native SVG has visible textual equivalence; drawer focus contract passes.
- Dark/light, five viewports, zoom, keyboard, touch, reduced motion and axe pass.
- Console and network ledger are clean; intercepted browser evidence performs no DML/mutation.
- No forbidden CSS/HTML/local-theme/new-library pattern exists.
- No admin, RLS, runtime boundary, schema, grants, users, credentials, migrations, SQL views, data,
  goldens, export, print or persisted action changed.
- Compatibility/shared endpoints remain until zero callers; rollback is code-only.
- Final evidence names exact SHA, commands, separate return codes, caller census and limitations.

## Cierre

Estado inicial: plan escrito; implementacion no iniciada.

El cierre futuro debe registrar:

- SHA exacto y diff revisado;
- resultados PHP/frontend/browser con codigos separados;
- acceso permitido/oculto/denegado y single/multi;
- corte por obra y PAC cero/null/comparacion/historia;
- muestras, no leakage, umbrales y ausencia de score crudo;
- reconciliacion CNP/CNC/TNP/crisis y atribucion unavailable;
- cinco viewports, ambos temas, zoom, teclado/touch/foco/axe;
- consola limpia, ledger exacto de GET y cero mutaciones;
- cero DML y frontera RLS/schema/admin/datos intacta;
- caller census, compatibilidad y rollback;
- limites o trabajo diferido.

No cerrar por checkboxes. No hacer commit, push, PR, publicacion o deploy sin el gate del
repositorio y la autorizacion correspondiente.
