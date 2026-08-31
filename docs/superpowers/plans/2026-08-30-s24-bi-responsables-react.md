# S24 BI Responsables React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in order,
> stop at every vertical checkpoint and do not start a later front until the repository publication
> gate permits it. Checkbox syntax is an execution prompt only; progress and closure live in
> `Cierre` and git history, never in checkbox counts.

**Goal:** migrate `/bi/responsables` into the main React SPA as a private A/D/R support sheet whose
server-enforced scope is project for A/D and mine/project for R, whose population comes from
canonical commitments rather than materialized `cip`, and whose factual fulfillment signal is
always shown with commitment load, open restrictions, causal coverage and limitations on desktop,
tablet, mobile, dark and light, without rankings, mutations, RLS/schema/data changes or work under
`/admin/`.

**Architecture:** T01 owns session, active project, shell, sidebar, themes, routing and the sole HTTP
client. The T03 increment delivered through S17 owns sheet admission, canonical query/period,
shared states, frame, drawer and lineage. S13 owns professional identity and master links; S20 owns
restriction facts; S21 owns commitment/PAC/causal populations. S24 adds a server-first viewer-scope
policy, adapters over those read models, pure support-signal/context policies and one
`BiResponsablesReadService`. Existing `GET /api/bi/report/cip` remains the only report endpoint.
A compatibility presenter serves remaining legacy callers while S17 keeps personal signals out of
the management canvas. The SQL view and
`cip` remain untouched and cease to be authority. React renders server decisions and never mutates.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/SVG/CSS and existing AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s24-bi-responsables-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or
  another worktree.
- Inspect status and relevant diff before every task. Preserve all existing work; never clean,
  revert, stage or reformat adjacent paths.
- This planning session is documentation-only. Future commands below do not authorize
  implementation, dependencies, build, commit, push, PR, publication, deploy or data changes now.
- Start implementation only after T01, the T03 primitives delivered through S17, and the S13/S20/S21
  canonical read seams exist, are published as required by the repository gate and pass focused
  tests. If absent, execute the owning plan; do not rebuild them inside S24.
- `/admin/` is excluded.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, triggers, grants, users, credentials, memberships, role aliases, capability catalogs,
  flags, seeds, fixtures or persistent data.
- Never modify `database/bi/006_bi_cip_responsables.sql` or populate/recalculate `cip`.
- No DDL/DML, even in a transaction that rolls back. New tests use pure policies, fakes, source
  invariants, call logs, read-only snapshots and intercepted browser payloads.
- Never call `ReportProcessor::updateCICProyectos()` from a test, read, filter, refresh or detail.
- Inspect every existing test before running it. Exclude any suite that seeds, updates, deletes,
  restores or recomputes `cip/cic`.
- Preserve `BiPreviewAccessPolicy`, `internal.bi.preview`, `bi.control_tower.visible` and RBAC
  catalogs. Extend only the T03 sheet manifest already approved: A sees S24 without the flag, D/R
  see it with the flag and OT/all others receive 404.
- Every request requires one authorized project and `lps.indicadores.ver` in the real project
  membership. Foreign project returns 403 before any person reader call. Multiple projects are 422.
- A and D are always `project`. R defaults to `mine` and may explicitly choose `project` under the
  approved 2026-08-24 decision. The query never grants scope.
- In `mine`, resolve the signed-in professional server-side before reading commitments. Failure or
  ambiguity is fail-closed and never falls back to project scope.
- Accept `alcance=obra`, `resp` and old date aliases only in the compatibility parser. New links use
  canonical `scope`, `person_ref`, `desde` and `hasta`.
- Period is canonical range; week is a server-resolved shortcut through the project's real
  `semanas_activas` dates. The primary list uses the latest authorized week in the range.
- Population comes from the S21 commitment reader:
  nonblank responsible, `Activa IN ('1','NA')` and `Es_TNP=0`. PAC null remains visible.
- S24 candidate readers must never query `cip` or `bi_cip_responsables`. Enforce with a call-log or
  source-invariant test, not convention.
- `personRef` is project-qualified and never email/user/global name. Automatic comparison requires a
  unique S13 professional identity.
- PAC zero with complete records is valid. No PAC records is null/insufficient, never zero.
- Support signal is:
  comparable PAC <0.50 OR observed critical missed. `clear` requires comparable PAC >=0.50 and no
  critical missed. Everything else without observed critical failure is `insufficient`.
- Commitment load is count/current/prior/delta only. Add no saturation threshold or label.
- Open restrictions come through S20/S21 with project+week+activity keys. Causes come through S21
  with coverage and `recordedBy unavailable`.
- Never rank people, average their PAC, infer culpability, call someone a failure or use a cause as
  sole evidence for a personal decision.
- Every active filter governs headline, summary, breakdown, list and applicable history. Filter
  options alone declare their distinct basis.
- Existing `GET /api/bi/report/cip` carries page, filters and embedded detail. Add no endpoint,
  mutation, export, download, mail or notification.
- S17 must not project S24 person rows, counts or alerts into the management overview. T03 exposes
  the route in the Obra canvas for A/D/R; A's canvas preference never adds the signal to S17.
- Only `frontend/src/lib/api/cliente.ts` may call fetch. Parse success and errors with strict Zod;
  derive types with `z.infer`.
- Abort replaced requests, ignore stale completions and partition cache by session, project,
  viewerScope, period, filters, order, pagination and focus.
- Below 768px mount cards only; at or above 768px mount a semantic table only.
- Use native SVG plus visible text if visualization helps. Never canvas, hover-only or color-only.
- Reuse T03 `MarcoBi`, `FiltrosBi`, `EstadoBi` and `LinajeDrawer`. Do not make a second shell/drawer.
- Use `public/css/tokens.css`. No literal colors, inline colors, `!important`, local token family or
  new UI/table/chart/state library.
- Dark is default/fallback and light is equivalent. Validate 390x844, 480x900, 768x1024, 1180x820,
  1440x900 and 200% zoom; 44px targets, reduced motion and axe serious/critical zero.
- Browser evidence is fully intercepted and fails on unexpected URL, every non-GET request and
  console/page errors. Do not expose real names in fixtures or screenshots.
- Do not regenerate or commit visual goldens.
- Keep `views/bi/control-tower.php`, `public/js/modules/bi-spa.js`, shared BI CSS and the SQL view
  until the T03/S17-S24 caller census reaches zero.
- Route cut is blocked until the D61 SELECT-only reconciliation passes for four real weeks in at
  least two projects. The broken SQL view is characterized, never used as parity oracle.
- Rollback changes code/route only and never data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read closure records for T01, S13, S17, S20, S21 and the T03 increment.
2. Verify backend contracts exist:
   `BiSheetAccessPolicy`, `BiProjectScope`, `BiQueryParser`, canonical error envelope and no-store
   presenter.
3. Verify frontend contracts exist:
   `MarcoBi`, `FiltrosBi`, `EstadoBi`, `LinajeDrawer`, responsive drawer and `cliente.ts`.
4. Verify S13 exposes a read-only professional resolver with project-qualified identity, cargo and
   authorized href without sync side effects.
5. Verify S21 exposes commitment population, PAC state, critical state, cause coverage and real
   previous-period lookup.
6. Verify S20 exposes restrictions linked by project/week/activity without requiring a write.
7. Verify T03's manifest keeps Responsables in the A/D/R Obra canvas and S17's canonical overview
   does not project the legacy responsible-alert field.
8. Verify exact branch/runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch; `app`, `db` and `adminer` healthy; app mounted from this
   worktree.
9. Record starting SHA and every pre-existing changed path. Do not stage them.
10. Inspect candidate existing tests for SQL verbs, fixture writes and ReportProcessor calls before
    running any.
11. Characterize current endpoint read-only and record aggregate counts only. Copy no person name,
    email or cause text into fixtures.
12. Confirm the parent checkout has no S24 artifacts and do not change `.env`.
13. If any dependency is absent or unpublished, stop S24 and execute its owner. Do not create a
    temporary duplicate seam.

## File Structure

### Create — backend

- `src/Services/Bi/Responsables/ResponsibleViewerScope.php`.
- `src/Services/Bi/Responsables/ResponsibleViewerScopePolicy.php`.
- `src/Services/Bi/Responsables/BiResponsablesQuery.php`.
- `src/Services/Bi/Responsables/BiResponsablesQueryParser.php`.
- `src/Services/Bi/Responsables/ResponsibleIdentity.php`.
- `src/Services/Bi/Responsables/ResponsibleIdentityResolver.php`.
- `src/Services/Bi/Responsables/S13ResponsibleIdentityResolver.php`.
- `src/Services/Bi/Responsables/ResponsibleCommitmentReader.php`.
- `src/Services/Bi/Responsables/S21ResponsibleCommitmentReader.php`.
- `src/Services/Bi/Responsables/ResponsibleRestrictionReader.php`.
- `src/Services/Bi/Responsables/S20ResponsibleRestrictionReader.php`.
- `src/Services/Bi/Responsables/ResponsibleFulfillment.php`.
- `src/Services/Bi/Responsables/ResponsibleFulfillmentPolicy.php`.
- `src/Services/Bi/Responsables/ResponsibleSupportSignal.php`.
- `src/Services/Bi/Responsables/ResponsibleSupportSignalPolicy.php`.
- `src/Services/Bi/Responsables/ResponsibleLoadContext.php`.
- `src/Services/Bi/Responsables/ResponsibleRestrictionContext.php`.
- `src/Services/Bi/Responsables/ResponsibleCausalCounterweight.php`.
- `src/Services/Bi/Responsables/ResponsibleComparison.php`.
- `src/Services/Bi/Responsables/ResponsibleComparisonPolicy.php`.
- `src/Services/Bi/Responsables/ResponsibleConversationOrder.php`.
- `src/Services/Bi/Responsables/ResponsibleSummary.php`.
- `src/Services/Bi/Responsables/ResponsibleHeadline.php`.
- `src/Services/Bi/Responsables/ResponsibleActionProjector.php`.
- `src/Services/Bi/Responsables/BiResponsablesReadService.php`.
- `src/Services/Bi/Responsables/BiResponsablesPresenter.php`.

### Modify — backend integration

- `src/Security/BiSheetAccessPolicy.php` — A/D/R row for S24, with the flag applied only to D/R.
- `src/Services/Bi/MetricDictionaryService.php` — parity/executable source and completeness after
  the D61 gate.
- `src/Services/Bi/LineageService.php` — canonical source, formula, basis and limitations.
- S17 sheet manifest/overview seam — preserve the no-personal-signal management boundary.
- `src/Services/ControlTowerService.php` — compatibility delegation only.
- `src/Controllers/Api/BiControlTowerApiController.php` — thin existing GET delegation.
- `src/Controllers/Bi/BiViewController.php` — remove page-only scope enforcement and hand off to SPA
  at cut; keep compatibility until then.
- `src/View/Components/BiAccessComponent.php` — shared manifest consumption only.
- `src/Core/SpaRouter.php` — add exact page prefix only after Task 11 gate.
- `public/index.php` only if the existing controller registration needs an adapter; add no route.

### Create — PHP tests and fixtures

- `tests/Support/Bi/FakeResponsibleIdentityResolver.php`.
- `tests/Support/Bi/FakeResponsibleCommitmentReader.php`.
- `tests/Support/Bi/FakeResponsibleRestrictionReader.php`.
- `tests/fixtures/bi-responsables-react/identities.php`.
- `tests/fixtures/bi-responsables-react/commitments.php`.
- `tests/fixtures/bi-responsables-react/restrictions.php`.
- `tests/fixtures/bi-responsables-react/periods.php`.
- `tests/fixtures/bi-responsables-react/queries.php`.
- `tests/test_bi_responsables_access_scope.php`.
- `tests/test_bi_responsables_query_period.php`.
- `tests/test_bi_responsables_identity_population.php`.
- `tests/test_bi_responsables_source_invariants.php`.
- `tests/test_bi_responsables_fulfillment.php`.
- `tests/test_bi_responsables_support_signal.php`.
- `tests/test_bi_responsables_context.php`.
- `tests/test_bi_responsables_comparison.php`.
- `tests/test_bi_responsables_read_service.php`.
- `tests/test_bi_responsables_filters_pagination.php`.
- `tests/test_bi_responsables_contract.php`.
- `tests/test_bi_responsables_compatibility.php`.
- `tests/test_bi_responsables_lienzo_boundary.php`.
- `tests/test_bi_responsables_reconciliation.php`.
- `tests/test_bi_responsables_routes.php`.

### Create — frontend

- `frontend/src/lib/api/esquemas/biResponsables.ts` and test.
- `frontend/src/lib/api/biResponsables.ts` and test.
- `frontend/src/modulos/bi/ResponsablesPagina.tsx` and test.
- `frontend/src/modulos/bi/responsables/queryResponsables.ts` and test.
- `frontend/src/modulos/bi/responsables/estadoResponsables.ts` and test.
- `frontend/src/modulos/bi/responsables/useBiResponsables.ts` and test.
- `frontend/src/modulos/bi/responsables/PropositoResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/SelectorAlcanceResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/ResumenResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/FiltrosResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/ListaResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/TablaResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/TarjetasResponsables.tsx` and test.
- `frontend/src/modulos/bi/responsables/TarjetaResponsable.tsx` and test.
- `frontend/src/modulos/bi/responsables/EstadoApoyo.tsx` and test.
- `frontend/src/modulos/bi/responsables/BasePacResponsable.tsx` and test.
- `frontend/src/modulos/bi/responsables/ContextoCarga.tsx` and test.
- `frontend/src/modulos/bi/responsables/ContextoRestricciones.tsx` and test.
- `frontend/src/modulos/bi/responsables/ContextoCausal.tsx` and test.
- `frontend/src/modulos/bi/responsables/HistorialResponsable.tsx` and test.
- `frontend/src/modulos/bi/responsables/DetalleResponsable.tsx` and test.
- `frontend/src/modulos/bi/responsables/responsables.css`.
- `tests/browser/fixtures/bi-responsables-react.mjs`.
- `tests/browser/bi-responsables-react.spec.mjs`.
- `tests/browser/bi-responsables-react.a11y.mjs`.
- `tests/design-system/bi-responsables-react-tokens.test.mjs`.

### Modify — frontend integration

- `frontend/src/shell/rutas.tsx` — register existing `/bi/responsables`.
- `frontend/src/shell/NavegacionLateral.tsx` only through the shared server/manifest seam.
- `frontend/src/main.tsx` only if layered module CSS import is required.
- `public/css/tokens.css` only after a failing token-contract test proves a missing semantic token.

### Explicitly preserve

- `database/bi/006_bi_cip_responsables.sql` and every database asset.
- `src/Services/ReportProcessor.php` and all scheduled/report writes.
- S13/S20/S21 write services and operational pages.
- all `/admin/` paths.
- `views/bi/control-tower.php` sections not yet retired.
- `public/js/modules/bi-spa.js` and other BI callers.
- `public/css/bi-control-tower.css` and Chart.js consumers.
- `docs/security/rls-runtime-boundary.md`.

## Task 1: Lock sheet admission, viewer scope and project period

**Files:**
- Create the viewer-scope/query classes listed above.
- Modify `src/Security/BiSheetAccessPolicy.php` only through its shared manifest.
- Create `tests/test_bi_responsables_access_scope.php` and
  `tests/test_bi_responsables_query_period.php`.

**Goal:** no person reader can run before A/D/R admission, one-project authorization, viewer-scope
resolution and a valid real project period.

- [ ] **Step 1: Inspect dependency interfaces and write failing admission tests**

Cover A/D/R allowed, A admitted with flag off, D/R hidden with flag off, OT/other 404, foreign
project 403, multiple project 422 and proof that the fake reader call count remains zero for every
denial.

Run:

    docker compose exec -T app php tests/test_bi_responsables_access_scope.php

Expected: FAIL because S24 is absent from the shared sheet policy and viewer-scope policy does not
exist.

- [ ] **Step 2: Implement the minimal sheet-manifest row**

Add only S24's A/D/R composition, applying the existing flag only to D/R. Do not edit
`RbacCatalog`, aliases, capabilities or the flag itself. Keep hidden-sheet 404 distinct from
foreign-project 403.

Run the same test. Expected: admission cases pass; viewer-scope cases still fail.

- [ ] **Step 3: Write query/period failures**

Cover canonical `scope`, week/range exclusivity, real week lookup, Bogotá default, primary/history
basis, bounded text/enums/page, legacy aliases, forbidden authority keys and multiple projects.

Run:

    docker compose exec -T app php tests/test_bi_responsables_query_period.php

Expected: FAIL because parser/DTO are missing.

- [ ] **Step 4: Implement query DTO/parser**

Return typed values; never pass raw query arrays to repositories. Canonicalize aliases once. Reject
unknown arrays and authority fields. Reuse T03's period resolver rather than copy week math.

- [ ] **Step 5: Implement viewer-scope policy**

Rules are exact: A→project; D→project; R omitted→mine; R explicit project allowed; all other
combinations rejected. The policy emits a value object, not a role string for React.

- [ ] **Step 6: Run focused tests**

    docker compose exec -T app php tests/test_bi_responsables_access_scope.php
    docker compose exec -T app php tests/test_bi_responsables_query_period.php

Expected: PASS, no DB writes and denial call counts zero.

- [ ] **Step 7: Future atomic commit**

    git add src/Security/BiSheetAccessPolicy.php src/Services/Bi/Responsables tests/test_bi_responsables_access_scope.php tests/test_bi_responsables_query_period.php
    git commit -m "test(bi): lock responsables scope and period"

Do not run these git commands in this documentation session.

**Vertical checkpoint:** authorization and query rules are executable without returning a person.

## Task 2: Adapt S13 identity and S21 population without cip authority

**Files:**
- Create identity/reader interfaces and adapters.
- Create fakes and identity/commitment fixtures.
- Create `tests/test_bi_responsables_identity_population.php` and
  `tests/test_bi_responsables_source_invariants.php`.

**Goal:** produce project-qualified people from canonical commitments, with mine fail-closed and no
`cip`/ReportProcessor read side effect.

- [ ] **Step 1: Write identity failures**

Cover unique S13 match, assignment-name fallback, ambiguous match, cross-project same name,
comparison eligibility and payload-safe references. Mine unresolved/ambiguous returns a typed
identity error and zero commitment-reader calls.

- [ ] **Step 2: Write population failures**

Cover nonblank responsible, `Activa=1/NA`, TNP exclusion, PAC null inclusion, no master-only person
and stable project/week/person grouping.

Run:

    docker compose exec -T app php tests/test_bi_responsables_identity_population.php

Expected: FAIL because adapters are missing.

- [ ] **Step 3: Implement `S13ResponsibleIdentityResolver`**

Consume S13's read seam. Never sync Professionals. Emit `professional:<id>` only for a unique
project match; otherwise project-qualified opaque assignment refs for project view. Mine accepts
only the session's unique professional identity.

- [ ] **Step 4: Implement `S21ResponsibleCommitmentReader`**

Adapt S21 rows under an already-authorized one-project scope. Preserve PAC null and canonical
population. Add no SQL if S21 already exposes the row set; do not call an HTTP endpoint internally.

- [ ] **Step 5: Add source invariants**

The candidate class graph/call log must reject `cip`, `bi_cip_responsables`,
`updateCICProyectos` and write verbs. It may mention those strings only in the invariant test's
denial list/documentation, never the production reader.

Run:

    docker compose exec -T app php tests/test_bi_responsables_source_invariants.php

Expected: PASS only when the production candidate has zero forbidden dependency.

- [ ] **Step 6: Run focused tests**

    docker compose exec -T app php tests/test_bi_responsables_identity_population.php
    docker compose exec -T app php tests/test_bi_responsables_source_invariants.php

- [ ] **Step 7: Future atomic commit**

    git add src/Services/Bi/Responsables tests/Support/Bi tests/fixtures/bi-responsables-react tests/test_bi_responsables_identity_population.php tests/test_bi_responsables_source_invariants.php
    git commit -m "feat(bi): read responsables from canonical commitments"

**Vertical checkpoint:** a fake/real read can list the correct scoped population, including open PAC,
without composing a signal or UI.

## Task 3: Make fulfillment completeness and support signal executable

**Files:**
- Create fulfillment/support value objects and policies.
- Create `tests/test_bi_responsables_fulfillment.php` and
  `tests/test_bi_responsables_support_signal.php`.

**Goal:** distinguish zero from missing, close the legacy false-health/false-alert paths and express
the approved metric without judging a person.

- [ ] **Step 1: Write fulfillment table tests**

Cases: complete 3/4, complete 0/4, partial 1/4, open 0/4, closed missing records, no population,
critical missed, noncritical missed and invalid PAC rejected by adapter.

- [ ] **Step 2: Implement `ResponsibleFulfillmentPolicy`**

Return counts, percentage-or-null, phase, completeness, missing facts and basis. Percentage divides
fulfilled by recorded only after the comparability rule passes; never silently uses all rows.

- [ ] **Step 3: Write signal decision-table tests**

Cover PAC 0.49 active, 0.50 clear, critical missed active with partial PAC, PAC absent insufficient,
PAC high with no critical clear, and no copy containing punitive words.

- [ ] **Step 4: Implement `ResponsibleSupportSignalPolicy`**

Use finite enums/reason codes and server text facts. Do not use causes, load or restrictions to
activate `cip_fulfillment_alert`; they are context only.

- [ ] **Step 5: Run focused tests**

    docker compose exec -T app php tests/test_bi_responsables_fulfillment.php
    docker compose exec -T app php tests/test_bi_responsables_support_signal.php

Expected: PASS including PAC-zero and PAC-null regression cases.

- [ ] **Step 6: Future atomic commit**

    git add src/Services/Bi/Responsables/ResponsibleFulfillment.php src/Services/Bi/Responsables/ResponsibleFulfillmentPolicy.php src/Services/Bi/Responsables/ResponsibleSupportSignal.php src/Services/Bi/Responsables/ResponsibleSupportSignalPolicy.php tests/test_bi_responsables_fulfillment.php tests/test_bi_responsables_support_signal.php
    git commit -m "feat(bi): define factual responsible support signal"

**Vertical checkpoint:** given one person's canonical rows, PHP produces a defensible signal and
basis with no database access.

## Task 4: Add load, restrictions, causal counterweight and self-comparison

**Files:**
- Create load/restriction/causal/comparison classes and S20 adapter.
- Create `tests/test_bi_responsables_context.php` and
  `tests/test_bi_responsables_comparison.php`.

**Goal:** every signal carries explanatory context while avoiding invented saturation, causal
attribution or person-to-person comparison.

- [ ] **Step 1: Write load/comparison failures**

Cover current/prior count, real previous week, missing week, identity-name fallback, PAC
comparability and no threshold/ordinal output.

- [ ] **Step 2: Implement load and comparison**

Use S21's real period relation. Comparison is null for non-professional/ambiguous identity and for
incomparable PAC. Keep raw counts/deltas.

- [ ] **Step 3: Write restriction failures**

Cover distinct restriction count, distinct blocked commitments, duplicate links, cross-project
same activity id, missing coverage and no confusion between commitment responsible/restriction
assignee.

- [ ] **Step 4: Implement `S20ResponsibleRestrictionReader`**

Adapt S20 facts by project+week+activity key. No mutation, no POST and no internal HTTP.

- [ ] **Step 5: Write causal counterweight failures**

Cover with-cause/without-cause/coverage, complete text, `recordedBy unavailable`, responsible
labeling and no ranking/decision derived from a cause.

- [ ] **Step 6: Implement counterweight**

Reuse S21's canonical causal population and return only authorized detail. Keep actor unavailable
explicitly.

- [ ] **Step 7: Run focused tests**

    docker compose exec -T app php tests/test_bi_responsables_context.php
    docker compose exec -T app php tests/test_bi_responsables_comparison.php

- [ ] **Step 8: Future atomic commit**

    git add src/Services/Bi/Responsables tests/test_bi_responsables_context.php tests/test_bi_responsables_comparison.php
    git commit -m "feat(bi): add responsible support context"

**Vertical checkpoint:** a person row now explains fulfillment with load, restrictions, causes and
self-history, still without an HTTP response.

## Task 5: Assemble the canonical read service, filters, ordering and pagination

**Files:**
- Create service, summary, headline, order, action and presenter classes.
- Create `tests/test_bi_responsables_read_service.php` and
  `tests/test_bi_responsables_filters_pagination.php`.

**Goal:** one service produces the complete filtered snapshot and embedded drawer detail before any
controller or React wiring.

- [ ] **Step 1: Write orchestration failures**

Assert authorization inputs, one primary week, full filtered summary vs page, purpose metadata,
coverage, person rows, filter options, limitations, lineage refs and authorized href/null behavior.

- [ ] **Step 2: Implement `BiResponsablesReadService`**

Order: resolve period/population → identity → fulfillment/signal → context/comparison → filters →
summary/breakdowns/headline → conversation order → pagination → embedded detail. Never summarize
the paginated slice.

- [ ] **Step 3: Write filter/order failures**

Cover q, person_ref, support, open_restrictions, missing_pac, cause category, every sort, stable
ties, page bounds, mine filter options and all-filter governance.

- [ ] **Step 4: Implement filtering and conversation order**

No SQL/client ordering may diverge from the pure policy. Do not emit a displayed rank. Keep
`scopePeriod` on filter options.

- [ ] **Step 5: Implement server headlines/actions**

Finite templates for mine/project/empty/insufficient. Hrefs use dependency capability seams and are
null when unavailable. Copy passes a forbidden-punitive-word test.

- [ ] **Step 6: Run focused tests**

    docker compose exec -T app php tests/test_bi_responsables_read_service.php
    docker compose exec -T app php tests/test_bi_responsables_filters_pagination.php

- [ ] **Step 7: Future atomic commit**

    git add src/Services/Bi/Responsables tests/test_bi_responsables_read_service.php tests/test_bi_responsables_filters_pagination.php
    git commit -m "feat(bi): compose responsible support snapshot"

**Vertical checkpoint:** a fake-backed service returns the exact canonical JSON-ready model for
mine and project scopes.

## Task 6: Wire the existing GET, compatibility, lineage and S17 boundary

**Files:**
- Modify the backend integration files listed above, except route cut.
- Create contract, compatibility and canvas-boundary tests.

**Goal:** expose the canonical model through the existing route, with typed errors, one
compatibility computation and no leakage.

- [ ] **Step 1: Write the PHP HTTP contract test**

Test 200 shape/invariants plus 400/401/403/404/409/422/429/500/503 presenters. Assert no raw role,
email, username, DB prefix, table name, SQL or stack. Assert `Cache-Control: no-store`.

Run:

    docker compose exec -T app php tests/test_bi_responsables_contract.php

Expected: FAIL because `cip()` still delegates to the legacy brief.

- [ ] **Step 2: Make the controller thin**

Parse request, resolve admitted project/viewer scope, call service, present response. Catch expected
domain errors explicitly. Add no route and no per-row endpoint.

- [ ] **Step 3: Add one compatibility presenter**

Derive legacy `respuesta/scorecard/executive_brief` from canonical summary/headline while old callers
exist. Never read the SQL view to fill compatibility fields.

Run:

    docker compose exec -T app php tests/test_bi_responsables_compatibility.php

- [ ] **Step 4: Put the metric into parity state**

Update dictionary/lineage metadata to name the candidate and completeness, but do not mark
`ejecutable` until Task 11's real reconciliation passes. Preserve version history/known limitation.

- [ ] **Step 5: Enforce the S17 canvas boundary**

Assert the canonical management overview does not project person rows or person-alert counts.
Assert the shared manifest exposes S24 only inside the A/D/R Obra canvas and that A's access to the
sheet does not add personal signals or an S24 drilldown to S17.

Run:

    docker compose exec -T app php tests/test_bi_responsables_lienzo_boundary.php

- [ ] **Step 6: Run focused backend tests**

    docker compose exec -T app php tests/test_bi_responsables_contract.php
    docker compose exec -T app php tests/test_bi_responsables_compatibility.php
    docker compose exec -T app php tests/test_bi_responsables_lienzo_boundary.php
    docker compose exec -T app php tests/test_bi_metric_contracts.php
    docker compose exec -T app php tests/test_bi_restriction_thresholds.php

- [ ] **Step 7: Future atomic commit**

    git add src/Controllers/Api/BiControlTowerApiController.php src/Services/ControlTowerService.php src/Services/Bi/MetricDictionaryService.php src/Services/Bi/LineageService.php src/Services/Bi/Responsables tests/test_bi_responsables_contract.php tests/test_bi_responsables_compatibility.php tests/test_bi_responsables_lienzo_boundary.php
    git commit -m "feat(bi): expose canonical responsables report"

**Vertical checkpoint:** the existing GET is contract-valid and S17 preserves the privacy canvas
boundary, but the page route remains legacy and the metric remains parity-gated.

## Task 7: Define strict Zod, gateway, query codec and remote state

**Files:**
- Create schema/gateway/query/state/hook files and tests.

**Goal:** the main SPA can consume S24 only through `cliente.ts` and preserve correct state across
scope/filter/page changes.

- [ ] **Step 1: Write strict schema tests**

Cover success, every union, embedded detail, pagination, purpose, error envelopes, unknown fields,
missing basis, invalid enum and accidental sensitive fields.

Run:

    npm --prefix frontend test -- biResponsables

Expected: FAIL because files do not exist.

- [ ] **Step 2: Implement schemas and inferred types**

Use discriminated unions for fulfillment/signal/identity/error. Keep schemas strict at security and
business boundaries.

- [ ] **Step 3: Write gateway/query tests**

Assert one GET URL, canonical params, legacy aliases decoded but never emitted, repeated forbidden
project ids rejected, no direct fetch and abort signal propagation.

- [ ] **Step 4: Implement gateway through `pedir`/T03 error-aware client**

If T03 has evolved `cliente.ts` to typed HTTP errors, use it. Do not create another fetch wrapper.

- [ ] **Step 5: Write state/hook tests**

Cover loading, ready, refresh, empty, partial, identity unresolved, hidden, forbidden, invalid,
offline, abort, stale completion, cache partition and preserving content on refresh/offline.

- [ ] **Step 6: Implement state machine/hook**

Scope changes reset page and close detail; filter changes update canonical URL; stale responses
cannot overwrite the new scope.

- [ ] **Step 7: Run focused tests and typecheck**

    npm --prefix frontend test -- biResponsables
    npm --prefix frontend run typecheck

- [ ] **Step 8: Future atomic commit**

    git add frontend/src/lib/api frontend/src/modulos/bi/responsables
    git commit -m "feat(frontend): add responsables data boundary"

**Vertical checkpoint:** frontend data/state is complete without rendering the final page.

## Task 8: Render the first useful private-support slice

**Files:**
- Create page, purpose, scope selector, summary, basic list/card and tests.

**Goal:** render one useful vertical slice: purpose + mine/project scope + headline + factual people
list, using server decisions.

- [ ] **Step 1: Write page tests**

R mine shows second-person purpose and selector; R project shows whole-project copy; A and D show
project without selector; no response contains a role branch in components.
Empty/identity/loading/error states are distinct.

- [ ] **Step 2: Implement `ResponsablesPagina` inside T03 frame**

Do not create a sidebar, header, drawer or filter framework. Page receives session frame context and
the S24 hook.

- [ ] **Step 3: Implement purpose/scope/summary**

Purpose and no-projection copy are visible and programmatically associated. Scope selector updates
URL and fetches server scope; it never filters an already-loaded project list locally.

- [ ] **Step 4: Implement minimal person presentation**

Show name/cargo, support text, PAC base, load, open restrictions and critical misses. No color-only
status or rank.

- [ ] **Step 5: Test language and states**

Add a source/component test for forbidden punitive words and assertions that empty never says
everyone complies.

Run:

    npm --prefix frontend test -- ResponsablesPagina PropositoResponsables SelectorAlcanceResponsables ResumenResponsables estadoResponsables

- [ ] **Step 6: Future atomic commit**

    git add frontend/src/modulos/bi/ResponsablesPagina.tsx frontend/src/modulos/bi/responsables
    git commit -m "feat(frontend): render responsables support core"

**Vertical checkpoint:** intercepted data renders a private, useful S24 core before filters,
responsive table or drawer.

## Task 9: Add filters, table/cards, context and contextual drawer

**Files:**
- Create remaining S24 components, module CSS and tests.

**Goal:** complete observable capability on desktop/tablet/mobile without changing data or adding a
detail request.

- [ ] **Step 1: Write filter/pagination tests**

Search, support, restrictions, missing PAC, cause category, sort, page and clear must update
canonical query, announce count and preserve viewer scope.

- [ ] **Step 2: Implement filters**

Use T03 controls. Debounce only text search; enums submit immediately. Disable impossible options
from server rather than infer options client-side.

- [ ] **Step 3: Write responsive equivalence tests**

At >=768 only table exists; below only cards. Both expose the same support/PAC/load/restriction/
critical facts and detail action. No hidden duplicate interactive tree.

- [ ] **Step 4: Implement table/cards and native visuals**

Semantic table/caption/headers. Cards use headings/definitions. Any SVG has visible numeric text and
a useful accessible name.

- [ ] **Step 5: Write drawer tests**

Open by click/keyboard, embedded detail only, title association, focus trap/return/Escape, complete
cause text, recordedBy unavailable, limitations, lineage and authorized/null actions.

- [ ] **Step 6: Implement one shared detail body**

Desktop drawer and mobile full-screen surface share `DetalleResponsable`. No form and no second GET.

- [ ] **Step 7: Run focused tests**

    npm --prefix frontend test -- FiltrosResponsables ListaResponsables TablaResponsables TarjetasResponsables DetalleResponsable ContextoCausal
    npm --prefix frontend run typecheck

- [ ] **Step 8: Future atomic commit**

    git add frontend/src/modulos/bi/responsables
    git commit -m "feat(frontend): complete responsables responsive detail"

**Vertical checkpoint:** the whole S24 interaction works in component tests, still without route cut.

## Task 10: Integrate route/sidebar, themes and accessibility

**Files:**
- Modify frontend integration files.
- Add design-token and browser-a11y fixtures/tests.
- Modify `public/css/tokens.css` only if a failing contract proves a missing semantic token.

**Goal:** integrate S24 into the one shell and meet responsive/theme/accessibility gates before any
server route cut.

- [ ] **Step 1: Write route/sidebar failures**

Assert exact `/bi/responsables` match, A/D/R Obra navigation/active label, absence from A's Gerencia
canvas, no duplicate sidebar and R scope control only inside the page.

- [ ] **Step 2: Register route through the shared manifest**

Use T03 route metadata. Do not add a legacy `window.location` branch or role list in
`NavegacionLateral.tsx`.

- [ ] **Step 3: Write token/style failures**

Scan S24 CSS/TSX for literal colors, inline colors, `!important`, unapproved token definitions,
canvas and extra libraries.

- [ ] **Step 4: Implement layered CSS with existing tokens**

Dark fallback and light equivalence; table/card breakpoint; drawer/reflow; visible focus; 44px
targets; reduced motion. Add a shared semantic token only after the failing test and verify all
consumers.

- [ ] **Step 5: Complete accessibility tests**

Headings, purpose/no-projection, scope group, table associations, cards, filters, live announcements,
drawer focus and SVG alternatives.

- [ ] **Step 6: Run focused gates**

    npm --prefix frontend test -- ResponsablesPagina biResponsables
    npm --prefix frontend run typecheck
    node tests/design-system/bi-responsables-react-tokens.test.mjs

- [ ] **Step 7: Future atomic commit**

    git add frontend/src/shell frontend/src/modulos/bi frontend/src/main.tsx tests/design-system tests/browser public/css/tokens.css
    git commit -m "feat(frontend): integrate responsables into shared shell"

**Vertical checkpoint:** route-level React is ready in unit/component tests, but PHP still serves
legacy until Task 11 passes D61 and runs the browser matrix.

## Task 11: Pass D61 reconciliation, cut the page route and verify real behavior

**Files:**
- Create reconciliation/route/browser tests.
- Modify metric state, SPA route, view controller handoff and S17 boundary as needed.
- Do not modify database assets or data.

**Goal:** prove the canonical source on real read-only data, then and only then mark the metric
executable and route the page to React.

- [ ] **Step 1: Inspect reconciliation test source before running**

The test must contain SELECT/read-only reductions only. Fail if its own source includes write verbs,
ReportProcessor or persistent fixture helpers.

- [ ] **Step 2: Implement independent candidate and oracle comparison**

Candidate calls `BiResponsablesReadService`. Oracle independently reduces row-level S21 canonical
commitments. Compare at least four real weeks in two projects for population keys, counts, PAC
status/value, critical misses and signal. Output aggregates only.

Run:

    docker compose exec -T app php tests/test_bi_responsables_reconciliation.php

Expected: PASS with named project IDs/week counts only; no person names/emails and no writes. Any
mismatch blocks every remaining step.

- [ ] **Step 3: Promote metric metadata only after PASS**

Change `cip_fulfillment_alert` from parity to executable with canonical execution source and
completeness. Keep the historical SQL-view limitation in lineage. Re-run metric contracts.

- [ ] **Step 4: Verify the S17 boundary after metric promotion**

Assert the management overview still omits personal counts/alerts and has no S24 personal
drilldown, while the A/D/R Obra manifest exposes the route.

- [ ] **Step 5: Add exact SPA page route**

Update `SpaRouter::RUTAS_MIGRADAS` for `/bi/responsables` only after reconciliation. Preserve API
routing and other BI pages. Remove page-only redirect as authority; server endpoint owns scope.

Run:

    docker compose exec -T app php tests/test_bi_responsables_routes.php
    docker compose exec -T app php tests/test_spa_frontera.php
    docker compose exec -T app php tests/test_spa_frontera_http.php

- [ ] **Step 6: Run intercepted browser matrix**

Fixtures contain synthetic names only. Test R mine/project/back, A/D project/no toggle, A with flag
off, OT/others 404, foreign project 403, PAC null, PAC zero, critical miss, filters, drawer,
authorized links,
390/480/768/1180/1440, dark/light, zoom, axe and no unexpected request/non-GET/console error.

    npx playwright test tests/browser/bi-responsables-react.spec.mjs tests/browser/bi-responsables-react.a11y.mjs --workers=1

- [ ] **Step 7: Inspect served network and route census**

Confirm one initial report GET, no detail request, no POST, no `cip` compatibility fetch and active
sidebar. Record every remaining legacy caller before removing nothing.

- [ ] **Step 8: Build and smoke the mounted artifact**

    npm --prefix frontend run build

Verify the served `/app/` asset and `/bi/responsables` route from this worktree mount, not a host
preview or parent checkout.

- [ ] **Step 9: Future atomic commit**

    git add src/Core/SpaRouter.php src/Controllers/Bi/BiViewController.php src/Services/Bi/MetricDictionaryService.php src/Services/Bi/LineageService.php frontend tests
    git commit -m "feat(bi): cut responsables to React"

**Vertical checkpoint:** S24 is routed to React only with D61, role, data-state and browser evidence
green.

## Task 12: Run focused-to-broad verification and prepare closure evidence

**Files:**
- All S24 paths plus closure record when the active front authorizes it.
- No database, `/admin/`, goldens or unrelated files.

**Goal:** verify the exact final SHA proportionally, inspect diff/scope and prepare the repository's
PR-based closure without claiming or publishing in this planning session.

- [ ] **Step 1: Confirm worktree and diff scope**

    pwd
    git branch --show-current
    git status --short
    git diff --check
    git diff --name-only

Expected: exact worktree/branch; only intended S24/shared integration files plus pre-recorded work.

- [ ] **Step 2: Re-run every focused PHP test**

    docker compose exec -T app php tests/test_bi_responsables_access_scope.php
    docker compose exec -T app php tests/test_bi_responsables_query_period.php
    docker compose exec -T app php tests/test_bi_responsables_identity_population.php
    docker compose exec -T app php tests/test_bi_responsables_source_invariants.php
    docker compose exec -T app php tests/test_bi_responsables_fulfillment.php
    docker compose exec -T app php tests/test_bi_responsables_support_signal.php
    docker compose exec -T app php tests/test_bi_responsables_context.php
    docker compose exec -T app php tests/test_bi_responsables_comparison.php
    docker compose exec -T app php tests/test_bi_responsables_read_service.php
    docker compose exec -T app php tests/test_bi_responsables_filters_pagination.php
    docker compose exec -T app php tests/test_bi_responsables_contract.php
    docker compose exec -T app php tests/test_bi_responsables_compatibility.php
    docker compose exec -T app php tests/test_bi_responsables_lienzo_boundary.php
    docker compose exec -T app php tests/test_bi_responsables_reconciliation.php
    docker compose exec -T app php tests/test_bi_responsables_routes.php

Read each exit code on its own command. Do not pipe or chain a publication action.

- [ ] **Step 3: Re-run frontend/static gates**

    npm --prefix frontend test
    npm --prefix frontend run typecheck
    npm --prefix frontend run build
    node tests/design-system/bi-responsables-react-tokens.test.mjs
    git diff --check

- [ ] **Step 4: Re-run browser gates once on the final inputs**

    npx playwright test tests/browser/bi-responsables-react.spec.mjs tests/browser/bi-responsables-react.a11y.mjs --workers=1

Record viewports/themes, axe, console, network and route outcomes. Do not regenerate snapshots.

- [ ] **Step 5: Run proportionate shared regressions**

    docker compose exec -T app php tests/test_bi_metric_contracts.php
    docker compose exec -T app php tests/test_bi_restriction_thresholds.php
    docker compose exec -T app php tests/test_bi_alcance_responsables.php
    docker compose exec -T app php tests/test_spa_frontera.php
    node --test tests/test_foundation_shell_contract.mjs

Inspect any additional suite before running it. Do not run ReportProcessor/CIP mutation suites.

- [ ] **Step 6: Audit invariants and sensitive output**

Search production S24 paths for direct fetch, `cip` authority, ReportProcessor, write verbs, role
branches, sensitive fields, literal colors, canvas, punitive copy and TODOs. Confirm no logs,
fixtures, screenshots or docs contain real person names/emails.

- [ ] **Step 7: Exercise rollback locally without data**

Temporarily verify the route adapter can return legacy and restore React in code/test scope without
touching data. Re-run route and endpoint contracts after restoration.

- [ ] **Step 8: Record exact SHA and closure evidence**

Record commands, exit codes, D61 projects/weeks/counts, browser matrix, caller census, exclusions,
no-DML statement, no RLS/schema/data changes and rollback result. Do not fabricate checkbox
history.

- [ ] **Step 9: Future branch closure only when explicitly authorized**

Follow repository policy: atomic commits, fetch/integrate in the feature branch, reverify exact SHA,
push branch, open PR to `main`, wait for green CI and merge only under the active front's authority.
Production deploy remains separately authorized.

**Vertical checkpoint:** final evidence is sufficient for review; completion is not claimed until
all checks pass on the exact SHA and repository publication gate is satisfied.

## Acceptance Traceability

| Criterion | Owning task | Primary evidence |
|---|---:|---|
| S24-AC-001 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-002 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-003 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-004 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-005 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-006 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-007 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-008 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-009 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-010 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-011 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-012 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-013 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-014 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-015 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-016 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-017 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-018 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-019 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-020 | Task 1 | access, viewer-scope and period policy tests |
| S24-AC-021 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-022 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-023 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-024 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-025 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-026 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-027 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-028 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-029 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-030 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-031 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-032 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-033 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-034 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-035 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-036 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-037 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-038 | Task 2 | identity/population adapter and source-invariant tests |
| S24-AC-039 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-040 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-041 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-042 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-043 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-044 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-045 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-046 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-047 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-048 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-049 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-050 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-051 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-052 | Task 3 | fulfillment/completeness/support-signal pure tests |
| S24-AC-053 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-054 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-055 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-056 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-057 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-058 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-059 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-060 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-061 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-062 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-063 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-064 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-065 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-066 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-067 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-068 | Task 4 | load/restriction/causal/comparison pure tests |
| S24-AC-069 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-070 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-071 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-072 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-073 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-074 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-075 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-076 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-077 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-078 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-079 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-080 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-081 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-082 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-083 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-084 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-085 | Task 5 | read-service, filter, ordering and pagination tests |
| S24-AC-086 | Task 6 | PHP endpoint/compatibility contract and delegation tests |
| S24-AC-087 | Task 6 | PHP endpoint/compatibility contract and delegation tests |
| S24-AC-088 | Task 7 | strict Zod schema tests |
| S24-AC-089 | Task 7 | sole-client and gateway tests |
| S24-AC-090 | Task 6 | PHP endpoint/compatibility contract and delegation tests |
| S24-AC-091 | Task 9 | embedded contextual drawer tests |
| S24-AC-092 | Task 9 | detail-context equivalence tests |
| S24-AC-093 | Task 8 | visible private-purpose component tests |
| S24-AC-094 | Task 8 | no-projection accessibility tests |
| S24-AC-095 | Task 8 | loading-state component tests |
| S24-AC-096 | Task 8 | refreshing-state component tests |
| S24-AC-097 | Task 8 | scoped empty-state component tests |
| S24-AC-098 | Task 8 | filtered-empty recovery tests |
| S24-AC-099 | Task 8 | fail-closed identity-state tests |
| S24-AC-100 | Task 8 | offline-state component tests |
| S24-AC-101 | Task 8 | typed error-state component tests |
| S24-AC-102 | Task 7 | Zod, query, remote-state and first-state component tests |
| S24-AC-103 | Task 7 | Zod, query, remote-state and first-state component tests |
| S24-AC-104 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-105 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-106 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-107 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-108 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-109 | Task 9 | responsive list/drawer/visual-equivalence tests |
| S24-AC-110 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-111 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-112 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-113 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-114 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-115 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-116 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-117 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-118 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-119 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-120 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-121 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-122 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-123 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-124 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-125 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-126 | Task 10 | route/sidebar/theme/accessibility tests |
| S24-AC-127 | Task 12 | final RLS/runtime-boundary diff audit |
| S24-AC-128 | Task 12 | final schema/view asset diff audit |
| S24-AC-129 | Task 12 | final data/security artifact audit |
| S24-AC-130 | Task 12 | no-DML source and execution evidence |
| S24-AC-131 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-132 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-133 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-134 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-135 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-136 | Task 11 | SELECT-only parity, catalog, S17 boundary and cut gates |
| S24-AC-137 | Task 11 | management canvas omits S24 personal signals |
| S24-AC-138 | Task 11 | A/D/R Obra manifest and no-name Gerencia boundary tests |
| S24-AC-139 | Task 12 | focused-to-broad PHP/frontend verification |
| S24-AC-140 | Task 12 | focused-to-broad PHP/frontend verification |
| S24-AC-141 | Task 12 | focused-to-broad PHP/frontend verification |
| S24-AC-142 | Task 11 | intercepted browser role/data-state/network evidence |
| S24-AC-143 | Task 11 | intercepted browser role/data-state/network evidence |
| S24-AC-144 | Task 11 | intercepted browser role/data-state/network evidence |
| S24-AC-145 | Task 11 | intercepted browser role/data-state/network evidence |
| S24-AC-146 | Task 12 | closure, no-DML, rollback and caller-census evidence |
| S24-AC-147 | Task 12 | closure, no-DML, rollback and caller-census evidence |
| S24-AC-148 | Task 12 | closure, no-DML, rollback and caller-census evidence |
| S24-AC-149 | Task 12 | closure, no-DML, rollback and caller-census evidence |
| S24-AC-150 | Task 12 | closure, no-DML, rollback and caller-census evidence |

## Vertical Checkpoints

1. **Boundary:** A/D/R + one project + project or mine/project + real period, no person data.
2. **Population:** canonical S13/S21 identity/population, no `cip`.
3. **Signal:** PAC/completeness/support pure policies.
4. **Context:** load/restrictions/causes/self-comparison.
5. **Snapshot:** filtered/paginated canonical service with embedded detail.
6. **HTTP:** existing GET, compatibility and S17 privacy boundary.
7. **Client boundary:** Zod/gateway/state through `cliente.ts`.
8. **Useful UI:** private purpose/scope/summary/list.
9. **Complete UI:** filters/table/cards/drawer.
10. **Integration:** route manifest, sidebar, themes and a11y ready.
11. **D61/cut:** SELECT-only parity passes before executable metric/SPA route.
12. **Closure:** exact SHA verified, scope audited, caller census and rollback recorded.

A failed checkpoint stops later tasks. Do not compensate in React for a failed backend checkpoint.

## Completion Gate

S24 is complete only when all are true:

- exact worktree and branch verified;
- S13/S17/S20/S21/T03 dependencies are real and published per gate;
- A/D/R admission, A independent of the flag and OT/others 404 pass;
- R mine/project and A/D project are server-enforced;
- identity unresolved is fail-closed;
- canonical population includes PAC-null commitments and excludes TNP;
- production candidate has zero `cip`/view/ReportProcessor dependency;
- PAC zero/missing/partial/closed semantics pass;
- signal/load/restrictions/causal counterweight/self-comparison pass;
- contract PHP and strict Zod agree;
- S17 projects no S24 person count/alert in Gerencia and T03 exposes the route in Obra to A/D/R;
- D61 candidate/oracle parity passes four weeks/two projects without DML;
- metric is promoted only after parity;
- page route cuts only after the gate;
- table/cards, drawer, themes, viewports, zoom, keyboard and axe pass;
- browser emits no unexpected URL, non-GET, console or page error;
- no RLS/schema/view/data/grant/user/credential/`/admin/` change exists;
- no real personal data entered fixtures/evidence;
- no goldens regenerated;
- rollback and caller census are recorded;
- final diff is clean and exact SHA tests are green;
- the active front is closed through the repository's PR/CI policy before a new front begins.

## Cierre

Estado inicial: pendiente de implementación. This document was written and self-reviewed in a
documentation-only session. No implementation, test mutation, commit, push, PR, publication,
deployment, RLS/schema/data change or `/admin/` work was performed by writing this plan.
