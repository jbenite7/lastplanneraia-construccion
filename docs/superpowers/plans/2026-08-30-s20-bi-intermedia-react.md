# S20 BI Intermedia React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in
> order and stop at every vertical checkpoint. Checkbox syntax is an execution prompt only;
> progress and closure live in Cierre and git history, never in checkbox counts.

**Goal:** migrate `/bi/intermedia` from the legacy/pilot split into the main React SPA as one
coherent weekly restriction-management sheet: authorized scope and cutoff, orphan alarm,
N4-ordered actionable list, factual headline, four-band traffic light, hard-restriction Pareto,
contextual detail/lineage and the existing responsible/date/status write flow, equivalent on
desktop, tablet, mobile, dark and light, without changing RLS, permissions, schema or data.

**Architecture:** T01 owns session, active project, shell, navigation, theme, CSRF and the only HTTP
client. T03 owns BI access, canonical query, filters, shared states and drawer. S20 enriches the
existing `GET /api/bi/report/intermedia` through one `BiIntermediaReadService`; pure policies
author orphan status, overdue days, N4 order, headline, traffic-light status, Pareto and action
copy. The main React page makes that single GET and the existing management POST only. A canonical
presenter and compatibility views share the same models until the pilot has zero callers. Single
project can manage when the server says so; multi-project is read-only. The route cuts only after
parity, then removes `CT_PILOTO` and the separate `ct-app` island.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/SVG/CSS and the existing AIA
design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s20-bi-intermedia-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on branch
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout
  or another worktree.
- Inspect `git status --short` and the relevant diff before every task. Preserve unrelated and
  pre-existing work. Never clean, revert or reformat adjacent files.
- This planning session is documentation-only. Do not implement, install, build, commit, push,
  publish or deploy now. Commit commands below are future instructions and do not authorize work
  in this session.
- Implement S20 only after T01 and the T03 primitives delivered through S17 exist and are green.
  Reuse them; do not build another shell, route runtime, query parser, access policy, state frame,
  drawer, theme or HTTP client.
- S07 remains owner of the operational look-ahead grid. S20 may link to it and read its governed
  seams, but must not duplicate activity editing.
- `/admin/` is excluded. Do not edit its code, routes, role UI, flags, tests or assets.
- Do not modify RLS, runtime-boundary rules, schema, migrations, SQL views, tables, columns,
  indexes, triggers, grants, users, credentials, memberships, roles, aliases, flags or data.
- No DDL/DML is permitted. A transaction rolled back after a test is still DML. New PHP tests use
  pure services, fakes, call logs, source/reflection checks and static fixtures.
- Do not run the current `tests/browser/ct-intermedia.spec.mjs` as evidence: it creates and
  deletes rows. Replace it with a fully intercepted scenario before browser verification.
- Do not install missing `ct-app` dependencies or rebuild its bundle merely to characterize it.
  Its historical 155-test claim is not current evidence.
- Preserve `BiPreviewAccessPolicy`: A is admitted; D/R follow the existing global gate; hidden
  roles receive 404. Do not edit the gate or its flag.
- Preserve per-project `lps.indicadores.ver` and `BiProjectScope`. A client project, role,
  permission or capability value never grants authority.
- The active authorized project is default. Multi-project requires explicit selection, keeps one
  project/cutoff breakdown per obra and is always read-only in S20.
- Preserve T03 query fields `semana`, `desde`, `hasta`, `sub`, `resp` and `etapa`.
  Legacy aliases remain only in the compatibility parser/presenter.
- Every S20 section uses the same authorized query snapshot. A source unable to honor it returns a
  typed limitation; it never silently falls back to session project/week.
- The existing `GET /api/bi/report/intermedia` is the sole read request made by the new page.
  Do not create a detail/list/Pareto endpoint for React.
- The existing `POST /api/bi/control-tower/restricciones/{id}/gestion` is the sole mutation. It
  accepts responsible, commitment date and state only and derives project/user/audit from session.
- The POST keeps form-key `ct_piloto` during coexistence unless T01 has already supplied a
  canonical alias backed by the same server token. Never weaken CSRF to simplify the cut.
- Orphan means all three: `sin_gestionar`, no responsible, no commitment date.
- Overdue means a real commitment date before today in Bogota. Today/future/absent returns null,
  not zero.
- N4 is server-authored: missing start week first, ascending start week, descending linked
  activity count, critical path first and deterministic stable tie-break.
- The server authors every count, rank, status, headline, recommendation, contact, capability,
  href and lineage record. React renders and filters; it does not reproduce business policy.
- Remove the undocumented client threshold `UMBRAL_SIN_ANALISIS_PCT=30`. Without an approved
  predictive model, show only observed readiness and a predictive unavailable/insufficient state.
- The four traffic-light bands are exactly week 0, 1–2, 3–4 and 5–6. The server provides totals,
  ready, pending, rate and final state; React does not round or classify them.
- Pareto keeps every raw code. A label may be added only through an explicit tested dictionary;
  unknown codes remain visible.
- Convert legacy action/risk HTML into plain text or structured fields. Never use
  `dangerouslySetInnerHTML`.
- Detail and complete linked-activity summaries travel in the main snapshot. Do not add per-row
  fetches or infer project delay days.
- A successful POST is parsed from the fresh server row and followed by a full GET refresh.
  Never replace the row from the request body or retry a POST automatically.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. S20 gateways use its typed request
  primitive.
- Every GET, POST and error response is parsed by Zod before components see it. Types derive from
  `z.infer`.
- At widths below 768 mount cards only; at 768 and above mount the semantic table only. One shared
  drawer/form handles editing at every width.
- Use `public/css/tokens.css`. No color literals, `!important`, inline color, local
  `--ct-*` tokens, local theme storage or new UI/table/chart/form/state libraries.
- Dark is default/fallback; light has identical content and actions. Required viewports are
  390x844, 480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Do not remove pilot/shared endpoints until a caller census proves zero consumers. Shared BI
  legacy retirement waits for T03/S17–S24.
- Rollback is route/code only. It never changes or restores data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read the close records for T01, S17 and the T03 delivery that owns
   `BiSheetAccessPolicy`, `BiQueryParser`, `MarcoBi`, `FiltrosBi`, `EstadoBi`,
   `LinajeDrawer` and the responsive contextual drawer.
2. Verify those contracts exist and their focused tests pass. If absent, stop and execute the
   owning plan; do not recreate them inside S20.
3. Verify S07 exposes the authorized activity/restriction read seams needed for links without
   importing its UI.
4. Verify branch, status and runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch above; services `app`, `db`, `adminer`; no unexpected
   changes; mounted app healthy before PHP or browser checks.
5. Record the starting SHA and every pre-existing path. Do not stage them.
6. Confirm `CT_PILOTO` state read-only; do not edit `.env`.
7. Confirm `ct-app` test dependencies are either already present or absent. Do not install them.

## File Structure

### Create — backend read domain

- `src/Services/Bi/Intermedia/BiIntermediaReadService.php` — one coherent authorized read.
- `src/Services/Bi/Intermedia/IntermediaConstraintReader.php` — project/cutoff/filter-scoped
  restriction and link reads behind a fakeable interface.
- `src/Services/Bi/Intermedia/IntermediaConstraintProjector.php` — canonical row, chain,
  audit and stable key.
- `src/Services/Bi/Intermedia/IntermediaUrgencyPolicy.php` — orphan, overdue and N4 rank/evidence.
- `src/Services/Bi/Intermedia/IntermediaTrafficLightBuilder.php` — four reconciled bands.
- `src/Services/Bi/Intermedia/IntermediaParetoBuilder.php` — code/count/percent/basis.
- `src/Services/Bi/Intermedia/IntermediaHeadline.php` — finite factual headline.
- `src/Services/Bi/Intermedia/IntermediaRecommendationProjector.php` — plain-text
  recommendation/contact.
- `src/Services/Bi/Intermedia/BiIntermediaPresenter.php` — canonical plus legacy compatibility.

### Create — backend write seam

- `src/Services/Bi/Intermedia/BiConstraintManagementRequest.php` — exact body validation.
- `src/Services/Bi/Intermedia/BiConstraintManagementPolicy.php` — session project and capability.
- `src/Services/Bi/Intermedia/BiConstraintManagementRepository.php` — scoped update/fresh read
  seam.
- `src/Services/Bi/Intermedia/BiConstraintPresenter.php` — canonical/pilot response from one row.

### Modify — backend integration

- `src/Controllers/Api/BiControlTowerApiController.php`.
- `src/Controllers/Api/BiConstraintWriteController.php`.
- `src/Controllers/Bi/BiViewController.php`.
- `public/index.php` only if the SPA catch-all wiring from T01 requires registration; do not add
  an endpoint.
- Existing BI access/query/presenter files delivered by T03, only through their intended extension
  seams.

### Create — PHP tests and fixtures

- `tests/Support/Bi/FakeIntermediaConstraintReader.php`.
- `tests/Support/Bi/FakeBiConstraintManagementRepository.php`.
- `tests/fixtures/bi-intermedia-react/single.php`.
- `tests/fixtures/bi-intermedia-react/multi.php`.
- `tests/fixtures/bi-intermedia-react/empty.php`.
- `tests/fixtures/bi-intermedia-react/partial.php`.
- `tests/fixtures/bi-intermedia-react/legacy-report.php`.
- `tests/fixtures/bi-intermedia-react/pilot-write.php`.
- `tests/test_bi_intermedia_access_query.php`.
- `tests/test_bi_intermedia_contract_characterization.php`.
- `tests/test_bi_intermedia_constraints.php`.
- `tests/test_bi_intermedia_meeting_model.php`.
- `tests/test_bi_intermedia_read_contract.php`.
- `tests/test_bi_intermedia_management_contract.php`.
- `tests/test_bi_intermedia_source_invariants.php`.
- `tests/test_bi_intermedia_routes.php`.

### Create — frontend API and state

- `frontend/src/lib/api/esquemas/biIntermedia.ts`.
- `frontend/src/lib/api/esquemas/biIntermedia.test.ts`.
- `frontend/src/lib/api/biIntermedia.ts`.
- `frontend/src/lib/api/biIntermedia.test.ts`.
- `frontend/src/modulos/bi/intermedia/estadoIntermedia.ts`.
- `frontend/src/modulos/bi/intermedia/estadoIntermedia.test.ts`.
- `frontend/src/modulos/bi/intermedia/filtrarRestricciones.ts`.
- `frontend/src/modulos/bi/intermedia/filtrarRestricciones.test.ts`.
- `frontend/src/modulos/bi/intermedia/useBiIntermedia.ts`.
- `frontend/src/modulos/bi/intermedia/useBiIntermedia.test.tsx`.

### Create — frontend page

- `frontend/src/modulos/bi/IntermediaPagina.tsx`.
- `frontend/src/modulos/bi/IntermediaPagina.test.tsx`.
- `frontend/src/modulos/bi/intermedia/AlarmaHuerfanas.tsx`.
- `frontend/src/modulos/bi/intermedia/FiltrosRestricciones.tsx`.
- `frontend/src/modulos/bi/intermedia/ListaRestricciones.tsx`.
- `frontend/src/modulos/bi/intermedia/TablaRestricciones.tsx`.
- `frontend/src/modulos/bi/intermedia/TarjetasRestricciones.tsx`.
- `frontend/src/modulos/bi/intermedia/TarjetaRestriccion.tsx`.
- `frontend/src/modulos/bi/intermedia/TitularIntermedia.tsx`.
- `frontend/src/modulos/bi/intermedia/SemaforoIntermedia.tsx`.
- `frontend/src/modulos/bi/intermedia/ParetoRestricciones.tsx`.
- `frontend/src/modulos/bi/intermedia/ResumenDisponibilidad.tsx`.
- `frontend/src/modulos/bi/intermedia/DetalleRestriccion.tsx`.
- `frontend/src/modulos/bi/intermedia/FormularioGestionRestriccion.tsx`.
- Focused `.test.tsx` files beside each non-trivial component.

### Create/modify — styles, route and evidence

- `frontend/src/modulos/bi/intermedia/intermedia.css`.
- T01 route registry file that owns `/bi/intermedia`.
- `tests/browser/bi-intermedia-react.spec.mjs`.
- `docs/design-system/manifests/bi-runtime.json`.
- `docs/design-system/manifests/torre-piloto.json` only in the retirement task.

### Delete only at Task 11 after zero callers

- `ct-app/`.
- `views/bi/control-tower-piloto.php`.
- `public/ct-app/assets/ct.js`.
- `public/ct-app/assets/ct.css`.
- `docs/design-system/manifests/torre-piloto.json`.
- Pilot-only bootstrap/flag branches in `BiViewController`, `views/bi/_layout.php` and
  `public/js/modules/bi-spa.js`.

Do not delete shared metric/lineage/list/Pareto routes merely because the new page no longer calls
them. Task 11 decides from the caller census.

## Task 1: Lock access, project scope and one-cutoff query semantics

**Files:**

- Create: `tests/test_bi_intermedia_access_query.php`
- Create: `tests/fixtures/bi-intermedia-react/single.php`
- Create: `tests/fixtures/bi-intermedia-react/multi.php`
- Modify: only the T03 access/query extension seams already established
- Reference only: `src/Security/BiPreviewAccessPolicy.php`
- Reference only: `src/Security/BiProjectScope.php`
- Reference only: `src/Security/RbacCatalog.php`
- Reference only: `src/Security/RbacService.php`

**Step 1: Write the failing access/query characterization**

Cover:

- A and gate-enabled D/R admitted;
- hidden role as 404;
- unauthorized project as 403;
- active authorized project default;
- explicit multi selection with per-project cutoff;
- `semana` versus `desde/hasta`;
- `sub/resp/etapa`;
- array/date/range failures;
- rejection of role/permission/project-authority-like keys;
- single capability from server;
- multi `canManageConstraints=false`;
- no fallback from an incompatible source to session scope.

Use in-memory sessions, pure scope fakes and T03 parser fixtures. Do not connect to MySQL.

**Step 2: Run and prove red**

       docker compose exec app php tests/test_bi_intermedia_access_query.php

Expected: non-zero because S20 policy registration and coherent-cutoff projection do not exist.
Read the return code on its own line.

**Step 3: Implement the minimum T03 extension**

- register `intermedia` in the declarative sheet policy;
- resolve all projects through `BiProjectScope`;
- normalize query once;
- publish effective cutoff per project;
- force multi management false;
- reject client authority fields;
- represent source incompatibility as limitation, never fallback.

Do not change preview flags, role aliases or capability catalogs.

**Step 4: Run focused green and static review**

       docker compose exec app php tests/test_bi_intermedia_access_query.php
       rg -n "role|permiso|capability|project_id" src/Services/Bi/Intermedia src/Controllers/Api/BiControlTowerApiController.php

Expected: test zero; no request value used as authority.

**Step 5: Future implementation commit**

       git add tests/test_bi_intermedia_access_query.php tests/fixtures/bi-intermedia-react \
         <reviewed-T03-extension-files>
       git commit -m "test(bi): lock intermedia access and query"

Do not execute this commit in the planning session.

## Task 2: Characterize legacy/pilot payloads and build one canonical read envelope

**Files:**

- Create: `tests/test_bi_intermedia_contract_characterization.php`
- Create: `tests/test_bi_intermedia_read_contract.php`
- Create: `tests/fixtures/bi-intermedia-react/legacy-report.php`
- Create: `src/Services/Bi/Intermedia/BiIntermediaReadService.php`
- Create: `src/Services/Bi/Intermedia/IntermediaConstraintReader.php`
- Create: `src/Services/Bi/Intermedia/BiIntermediaPresenter.php`
- Modify: `src/Controllers/Api/BiControlTowerApiController.php`

**Step 1: Freeze both existing read shapes**

Static fixtures must include:

- the current report keys and three scorecard values;
- list pilot's 13 camelCase fields;
- Pareto pilot envelope;
- metric basis;
- embedded HTML action/risk examples;
- an empty and a partial section;
- a multi-project fixture with IDs that collide across projects.

The test proves the adapter can still emit legacy report fields while canonical output owns all
new data.

**Step 2: Specify the canonical envelope in a failing test**

Assert:

- `ok/data/meta`;
- `reportKey=intermedia`;
- scope, capabilities, coverage and limitations;
- one constraints array with `projectId:id`;
- counts reconcile with rows;
- exactly four traffic-light slots;
- Pareto denominator;
- plain-text-only output;
- partial sections do not erase coherent sections;
- only the existing report route is needed.

**Step 3: Run red**

       docker compose exec app php tests/test_bi_intermedia_contract_characterization.php
       docker compose exec app php tests/test_bi_intermedia_read_contract.php

Expected: both non-zero until read service/presenter exist.

**Step 4: Implement the orchestration seam**

`BiIntermediaReadService` receives:

- authorized normalized query;
- one project/cutoff list;
- fakeable constraint/metric/lineage/action readers;
- a Bogota clock.

It returns one immutable read model. `BiIntermediaPresenter` emits:

- canonical envelope for React;
- legacy report shape from the same model during coexistence.

Do not call legacy HTTP endpoints from PHP; call domain/services directly.

**Step 5: Wire the existing controller action**

`BiControlTowerApiController::intermedia()`:

- authenticates;
- resolves T03 policy/query;
- calls the read service once;
- selects presenter mode through an explicit compatibility signal already defined by T03, not a
  client capability;
- emits typed errors.

Do not add a route.

**Step 6: Run green**

       docker compose exec app php tests/test_bi_intermedia_contract_characterization.php
       docker compose exec app php tests/test_bi_intermedia_read_contract.php
       docker compose exec app php tests/test_bi_views_exist.php

**Step 7: Future implementation commit**

       git add src/Services/Bi/Intermedia src/Controllers/Api/BiControlTowerApiController.php \
         tests/test_bi_intermedia_contract_characterization.php \
         tests/test_bi_intermedia_read_contract.php tests/fixtures/bi-intermedia-react
       git commit -m "feat(bi): serve coherent intermedia snapshot"

Do not execute this commit now.

## Task 3: Project the actionable restriction list, orphan rule and N4 order

**Files:**

- Create: `tests/test_bi_intermedia_constraints.php`
- Create: `tests/Support/Bi/FakeIntermediaConstraintReader.php`
- Create: `src/Services/Bi/Intermedia/IntermediaConstraintProjector.php`
- Create: `src/Services/Bi/Intermedia/IntermediaUrgencyPolicy.php`
- Modify: `src/Services/Bi/Intermedia/BiIntermediaReadService.php`

**Step 1: Write pure failing cases**

Use a fixed Bogota date and cover:

- all four management states;
- orphan only when state, responsible and date predicates all match;
- blank strings normalized to null;
- yesterday overdue one; today/future/absent null;
- same local ID in two projects yields distinct keys;
- complete linked activity collection;
- representative activity belongs to the minimum-start group;
- distinct links reconcile;
- missing week before any numbered week;
- N4 tie-break sequence and stability;
- rank and evidence for every row;
- total/orphan/overdue/state counts;
- no project-delay day field.

**Step 2: Run red**

       docker compose exec app php tests/test_bi_intermedia_constraints.php

Expected: non-zero because policies/projector are absent.

**Step 3: Implement the pure policies**

`IntermediaUrgencyPolicy` owns:

- null normalization;
- orphan predicates/evidence;
- overdue calculation with injected date;
- an explicit comparable N4 tuple;
- stable tie-break by project and local ID.

`IntermediaConstraintProjector` owns:

- canonical key and project display;
- note/type/management/audit;
- representative plus full link summaries;
- chain/critical-path evidence;
- counts derived once after projection.

Do not add route, controller or React sorting logic.

**Step 4: Add source invariants without executing SQL**

Add assertions to `tests/test_bi_intermedia_source_invariants.php` that intended repository SQL:

- includes `project_id` in every base/link/activity predicate;
- uses prepared placeholders;
- excludes `Titulo=1`;
- counts distinct link rows;
- never uses dynamic prefix/table input.

This is static/reflection evidence only.

**Step 5: Run green**

       docker compose exec app php tests/test_bi_intermedia_constraints.php
       docker compose exec app php tests/test_bi_intermedia_source_invariants.php

**Step 6: Future implementation commit**

       git add src/Services/Bi/Intermedia/IntermediaConstraintProjector.php \
         src/Services/Bi/Intermedia/IntermediaUrgencyPolicy.php \
         src/Services/Bi/Intermedia/BiIntermediaReadService.php \
         tests/test_bi_intermedia_constraints.php \
         tests/test_bi_intermedia_source_invariants.php tests/Support/Bi
       git commit -m "feat(bi): rank intermedia restrictions on server"

Do not execute this commit now.

## Task 4: Author the weekly meeting model on the server

**Files:**

- Create: `tests/test_bi_intermedia_meeting_model.php`
- Create: `src/Services/Bi/Intermedia/IntermediaTrafficLightBuilder.php`
- Create: `src/Services/Bi/Intermedia/IntermediaParetoBuilder.php`
- Create: `src/Services/Bi/Intermedia/IntermediaHeadline.php`
- Create: `src/Services/Bi/Intermedia/IntermediaRecommendationProjector.php`
- Modify: `src/Services/Bi/Intermedia/BiIntermediaReadService.php`
- Modify: `src/Services/Bi/Intermedia/BiIntermediaPresenter.php`

**Step 1: Write failing headline/alarm tests**

Assert:

- canvas section order metadata is context, alarm, list, headline, traffic light, Pareto, lineage;
- list is above headline;
- active/clear/insufficient orphan alarm;
- headline priority orphan, overdue, insufficient, observed fact;
- every headline has finite kind, text, variables and evidence;
- no 30-percent branch;
- valid observed zero is not missing;
- predictive status remains unavailable/insufficient without an approved model;
- no prediction copy promises failure.

**Step 2: Write failing traffic-light tests**

For each of 0, 1–2, 3–4 and 5–6:

- total = ready + pending;
- rate reconciles or is null;
- insufficient denominator is neutral/insufficient;
- zero pending healthy;
- week 0 pending urgent;
- 1–2 and 3–4 pending attention;
- 5–6 pending neutral;
- the array has exactly four ordered entries.

The builder receives aggregate counts. It does not round a client rate back into counts.

**Step 3: Write failing Pareto/action tests**

Assert:

- hard + not-ready only;
- project/cutoff included in basis;
- descending count and stable code tie-break;
- percentages reconcile to denominator;
- unknown code retained;
- optional label only from tested dictionary;
- recommendation/contact are server-authored;
- any `<b>`, tag or entity from legacy is normalized to plain text;
- no unsafe HTML field appears.

**Step 4: Run red**

       docker compose exec app php tests/test_bi_intermedia_meeting_model.php

Expected: non-zero.

**Step 5: Implement the four pure projectors**

- Build all states from numerators/denominators.
- Keep observed and predictive fields structurally separate.
- Use finite headline templates.
- Preserve raw Pareto code.
- Adapt `ActionRecommendationService` output to structured plain text.
- Include lineage keys and limitations with each section.

Do not add a chart library or client threshold.

**Step 6: Run green and regression characterization**

       docker compose exec app php tests/test_bi_intermedia_meeting_model.php
       docker compose exec app php tests/test_bi_metric_contracts.php
       docker compose exec app php tests/test_bi_semaforo_franjas.php
       docker compose exec app php tests/test_bi_restriction_pareto.php

The last two existing tests may connect/write depending on their current implementation. Inspect
them first; if either uses MySQL or DML, do not execute it. Record the limitation and rely on the
new pure test plus source invariants.

**Step 7: Future implementation commit**

       git add src/Services/Bi/Intermedia tests/test_bi_intermedia_meeting_model.php
       git commit -m "feat(bi): author intermedia meeting signals"

Do not execute this commit now.

## Task 5: Stabilize Zod contracts, the single gateway and request state

**Files:**

- Create: `frontend/src/lib/api/esquemas/biIntermedia.ts`
- Create: `frontend/src/lib/api/esquemas/biIntermedia.test.ts`
- Create: `frontend/src/lib/api/biIntermedia.ts`
- Create: `frontend/src/lib/api/biIntermedia.test.ts`
- Create: `frontend/src/modulos/bi/intermedia/estadoIntermedia.ts`
- Create: `frontend/src/modulos/bi/intermedia/estadoIntermedia.test.ts`
- Create: `frontend/src/modulos/bi/intermedia/useBiIntermedia.ts`
- Create: `frontend/src/modulos/bi/intermedia/useBiIntermedia.test.tsx`

**Step 1: Write failing Zod tests from PHP fixtures**

Cover:

- full single and multi envelopes;
- partial section;
- valid zero versus null;
- composed constraint key;
- exactly four traffic bands;
- Pareto unknown code;
- plain-text fields;
- POST canonical and compatibility responses;
- every canonical error code;
- rejection of wrong enum, malformed date, missing denominator and HTML-only legacy shape.

Types must be exported from `z.infer`; do not create parallel interfaces.

**Step 2: Run red**

       cd frontend
       npm test -- src/lib/api/esquemas/biIntermedia.test.ts

Expected: non-zero because schema is absent.

**Step 3: Implement schemas and fixtures**

Share primitive project/query/error schemas from T01/T03. Add only S20 fields. Use
`.superRefine()` for reconciliation invariants where useful:

- counts versus row flags;
- traffic light totals;
- Pareto denominator;
- single/multi capability constraints.

**Step 4: Write gateway tests**

Assert:

- one GET to `/api/bi/report/intermedia`;
- canonical query serialization through T03;
- one POST to exact selected ID;
- same-origin/JSON/expect-json headers;
- CSRF on POST only;
- no pilot list, Pareto, metric or lineage GET;
- cancellation signal forwarded;
- parsed result or typed API error.

Mock `cliente.ts`, not global `fetch`.

**Step 5: Implement gateway using cliente.ts**

No component or hook calls `fetch`. The gateway exports read/manage functions only.

**Step 6: Write hook/state tests**

Cover:

- loading/ready/partial/empty/insufficient/offline/invalid/error;
- refreshing preserves prior snapshot;
- query change aborts prior request;
- stale completion ignored;
- cache key includes user/project/period/filter;
- project change evicts inaccessible cache and closes selected row;
- lineage/detail read from snapshot;
- no automatic POST retry.

**Step 7: Implement minimum hook/state reducer**

Reuse T03 query/cache primitives. Do not add a state library.

**Step 8: Run green**

       cd frontend
       npm test -- src/lib/api/esquemas/biIntermedia.test.ts \
         src/lib/api/biIntermedia.test.ts \
         src/modulos/bi/intermedia/estadoIntermedia.test.ts \
         src/modulos/bi/intermedia/useBiIntermedia.test.tsx
       npm run typecheck

**Step 9: Future implementation commit**

       git add frontend/src/lib/api frontend/src/modulos/bi/intermedia
       git commit -m "feat(frontend): type intermedia read and write state"

Do not execute this commit now.

## Task 6: Refactor the existing management POST behind a pure contract

**Files:**

- Create: `tests/test_bi_intermedia_management_contract.php`
- Create: `tests/Support/Bi/FakeBiConstraintManagementRepository.php`
- Create: `tests/fixtures/bi-intermedia-react/pilot-write.php`
- Create: `src/Services/Bi/Intermedia/BiConstraintManagementRequest.php`
- Create: `src/Services/Bi/Intermedia/BiConstraintManagementPolicy.php`
- Create: `src/Services/Bi/Intermedia/BiConstraintManagementRepository.php`
- Create: `src/Services/Bi/Intermedia/BiConstraintPresenter.php`
- Modify: `src/Controllers/Api/BiConstraintWriteController.php`

**Step 1: Freeze the observable POST contract**

Pure fixture tests cover:

- current route and form key;
- responsible required/trimmed/120 max;
- real ISO date, including past;
- exact four-state enum;
- project/user ignored from body;
- capability from current project role;
- single-scope requirement;
- same 404 for missing/foreign row;
- 403 capability and CSRF differentiated;
- prepared repository call contains only responsible/date/state plus server audit;
- fresh row drives response;
- canonical and pilot presenters derive from the same row.

Do not invoke the HTTP controller against MySQL.

**Step 2: Run red**

       docker compose exec app php tests/test_bi_intermedia_management_contract.php

Expected: non-zero until seams exist.

**Step 3: Extract request/policy/repository seams**

- Keep the controller thin.
- Resolve current role/project from server session.
- Validate CSRF before mutation.
- Reject malformed body before repository call.
- Repository takes explicit server project and ID.
- Repository update uses prepared parameters and fresh read.
- No extra writable field or schema change.

If T03 provides a stale-session project token, validate it as server context metadata. Do not add
`projectId` to the writable body.

**Step 4: Preserve compatibility**

Canonical callers receive `data.constraint`; pilot callers may receive `restriccion`. Both come
from `BiConstraintPresenter` and the same fresh row.

**Step 5: Add static source assertions**

`tests/test_bi_intermedia_source_invariants.php` proves:

- update is scoped by `project_id + Id`;
- prepared placeholders are used;
- no dynamic table/prefix;
- no field beyond the five documented database columns is in SET;
- no body role/project/user is consulted as authority.

**Step 6: Run green**

       docker compose exec app php tests/test_bi_intermedia_management_contract.php
       docker compose exec app php tests/test_bi_intermedia_source_invariants.php

Do not run `tests/test_bi_constraint_write.php` unless inspection proves it has no DML. Its
current historical design writes rows and therefore is not valid evidence under this plan.

**Step 7: Future implementation commit**

       git add src/Controllers/Api/BiConstraintWriteController.php \
         src/Services/Bi/Intermedia/BiConstraintManagementRequest.php \
         src/Services/Bi/Intermedia/BiConstraintManagementPolicy.php \
         src/Services/Bi/Intermedia/BiConstraintManagementRepository.php \
         src/Services/Bi/Intermedia/BiConstraintPresenter.php \
         tests/test_bi_intermedia_management_contract.php \
         tests/test_bi_intermedia_source_invariants.php tests/Support/Bi \
         tests/fixtures/bi-intermedia-react/pilot-write.php
       git commit -m "refactor(bi): isolate constraint management contract"

Do not execute this commit now.

## Task 7: Render alarm, actionable list, meeting signals and contextual detail

**Files:**

- Create: `frontend/src/modulos/bi/IntermediaPagina.tsx`
- Create: `frontend/src/modulos/bi/IntermediaPagina.test.tsx`
- Create: `frontend/src/modulos/bi/intermedia/filtrarRestricciones.ts`
- Create: `frontend/src/modulos/bi/intermedia/filtrarRestricciones.test.ts`
- Create: component files listed in File Structure through `DetalleRestriccion.tsx`
- Create: focused component tests beside each non-trivial component

**Step 1: Write failing page/order tests**

Render a canonical fixture and assert:

- T03 context/filter frame;
- one h1;
- alarm before list;
- list before headline;
- headline before traffic light/Pareto;
- lineage/limitations last;
- no legacy Programado/Ejecutado duplicate;
- links to S07/S21 preserve scope/query from server hrefs.

**Step 2: Write failing list/filter tests**

Pure filter tests cover:

- accent/case-tolerant search over restriction/activity/responsible;
- orphan/overdue/status/type/critical/project toggles;
- visible/total count;
- clear all;
- N4 order preserved;
- filtering entire snapshot before the 25-row presentation window;
- Show more increments without network;
- local filters never alter report numbers or management body.

Component tests cover both single and multi project labels.

**Step 3: Write failing detail tests**

Assert that selecting a row opens T03 drawer with:

- complete restriction/note/type;
- linked activities and representative activity;
- chain/critical evidence;
- audit;
- recommendation/contact;
- lineage;
- explicit delivery-impact limitation;
- no second gateway call;
- focus initial/trap/Escape/return;
- discard confirmation for dirty form state.

**Step 4: Write failing table/card boundary tests**

Mock the T03 responsive primitive, not CSS:

- 767 mounts cards only;
- 768 mounts table only;
- table has caption, headers and scoped cells;
- cards are one semantic list;
- mobile visible face has project when multi, restriction, activity, state, responsible, date/overdue,
  rank/critical evidence and detail/manage action;
- table and cards use the same selected-row model.

**Step 5: Implement the read-only vertical**

- Compose shared `MarcoBi/FiltrosBi/EstadoBi`.
- Render server order/status/copy.
- Use buttons for alarm filters, detail, Show more and reload.
- Render traffic/Pareto with native accessible bars plus visible text/table; no chart library.
- Do not mount the management form yet except a disabled capability placeholder required by tests.

**Step 6: Run focused green**

       cd frontend
       npm test -- src/modulos/bi/IntermediaPagina.test.tsx \
         src/modulos/bi/intermedia/filtrarRestricciones.test.ts \
         src/modulos/bi/intermedia/AlarmaHuerfanas.test.tsx \
         src/modulos/bi/intermedia/ListaRestricciones.test.tsx \
         src/modulos/bi/intermedia/DetalleRestriccion.test.tsx \
         src/modulos/bi/intermedia/SemaforoIntermedia.test.tsx \
         src/modulos/bi/intermedia/ParetoRestricciones.test.tsx
       npm run typecheck

**Step 7: Future implementation commit**

       git add frontend/src/modulos/bi
       git commit -m "feat(frontend): render bi intermedia decision sheet"

Do not execute this commit now.

## Task 8: Add the single management form and save-refresh reconciliation

**Files:**

- Create: `frontend/src/modulos/bi/intermedia/FormularioGestionRestriccion.tsx`
- Create: `frontend/src/modulos/bi/intermedia/FormularioGestionRestriccion.test.tsx`
- Modify: `frontend/src/modulos/bi/intermedia/DetalleRestriccion.tsx`
- Modify: `frontend/src/modulos/bi/intermedia/useBiIntermedia.ts`
- Modify: corresponding tests

**Step 1: Write failing form validation tests**

Cover:

- hidden/absent management action when capability false or multi;
- required trimmed responsible and 120 limit;
- accessible real date field; past accepted;
- exact state options;
- no inputs for project, role, audit, note or activity;
- cancel has zero gateway call;
- one submit while saving;
- field-linked 422;
- specific CSRF/forbidden/not-found/offline recovery;
- dirty-close/change-row confirmation.

**Step 2: Write failing reconciliation tests**

Assert sequence:

1. exactly one POST;
2. body contains exactly responsible/date/state;
3. POST response schema is parsed;
4. fresh row is displayed;
5. exactly one canonical GET follows;
6. final UI uses refreshed snapshot for all indicators;
7. request body is never treated as saved row;
8. refresh failure enters `saved_refresh_pending`;
9. retry in that state performs GET only;
10. POST is never automatically replayed.

**Step 3: Run red**

       cd frontend
       npm test -- src/modulos/bi/intermedia/FormularioGestionRestriccion.test.tsx \
         src/modulos/bi/intermedia/useBiIntermedia.test.tsx \
         src/modulos/bi/intermedia/DetalleRestriccion.test.tsx

**Step 4: Implement the minimum form/reducer transitions**

- Use controlled native inputs.
- Keep draft scoped to selected `projectId:id`.
- Call the typed gateway once.
- Replace selected detail from fresh POST row.
- Then invoke canonical refresh.
- Announce save/refresh through the shared live region.
- Preserve draft after recoverable error.

Do not add a form library or optimistic calculation.

**Step 5: Run green and typecheck**

       cd frontend
       npm test -- src/modulos/bi/intermedia/FormularioGestionRestriccion.test.tsx \
         src/modulos/bi/intermedia/useBiIntermedia.test.tsx \
         src/modulos/bi/intermedia/DetalleRestriccion.test.tsx
       npm run typecheck

**Step 6: Future implementation commit**

       git add frontend/src/modulos/bi/intermedia
       git commit -m "feat(frontend): manage intermedia constraints in drawer"

Do not execute this commit now.

## Task 9: Complete states, responsive craft, dark/light and accessibility

**Files:**

- Create: `frontend/src/modulos/bi/intermedia/intermedia.css`
- Modify: all S20 components and focused tests
- Modify: `docs/design-system/manifests/bi-runtime.json`
- Create/modify: relevant design-system static test only if the manifest contract requires it

**Step 1: Write static design-contract failures**

Assert across S20 source:

- only design tokens;
- no hex/rgb/hsl;
- no `!important`;
- no inline color/style width for charts;
- no `--ct-*`;
- no local theme toggle/storage key;
- no `dangerouslySetInnerHTML`;
- no chart/table/UI dependency;
- no simultaneously mounted table/cards.

Use semantic `meter`, CSS grid or SVG attributes for dynamic bars; do not introduce inline colors.

**Step 2: Cover every state in component tests**

Fixtures:

- loading;
- ready;
- refreshing over content;
- partial traffic/Pareto;
- empty list;
- insufficient observed metric;
- predictive unavailable;
- offline;
- invalid query;
- fatal error;
- save error;
- saved refresh pending.

Every state has a visible title/message and an appropriate retry/clear action where meaningful.

**Step 3: Craft with shared tokens**

- compact weekly-meeting hierarchy;
- severity rail/status chip primitive from the design system;
- fixed readable table headers without page overflow;
- card density that preserves seven required fields;
- drawer sizing from T03;
- 44x44 mobile targets;
- full cause/chain available;
- dark default and light parity;
- reduced-motion query.

Remove no pilot CSS yet; Task 11 does so after cut.

**Step 4: Run focused tests and scans**

       cd frontend
       npm test -- src/modulos/bi/IntermediaPagina.test.tsx src/modulos/bi/intermedia
       npm run typecheck
       npm run lint
       cd ..
       rg -n "#[0-9a-fA-F]{3,8}|rgb\\(|hsl\\(|!important|--ct-|dangerouslySetInnerHTML|ct-piloto-theme" \
         frontend/src/modulos/bi frontend/src/lib/api/esquemas/biIntermedia.ts

Expected: tests/typecheck/lint zero; scan has no S20 violation.

**Step 5: Future implementation commit**

       git add frontend/src/modulos/bi docs/design-system/manifests/bi-runtime.json
       git commit -m "style(bi): complete intermedia responsive themes"

Do not execute this commit now.

## Task 10: Cut the page route and prove behavior with fully intercepted browser scenarios

**Files:**

- Create: `tests/browser/bi-intermedia-react.spec.mjs`
- Create: `tests/test_bi_intermedia_routes.php`
- Modify: T01 route registry
- Modify: `src/Controllers/Bi/BiViewController.php` only for the staged route cut
- Modify: navigation links from S17/S07/S21 only through their typed route seam

**Step 1: Write the route test first**

Assert:

- `GET /bi/intermedia` is owned by SPA runtime at cut;
- API report and existing POST routes remain;
- no new API read route exists;
- hidden role/page and unauthorized project semantics remain;
- links to S07/S21 preserve authorized scope/period/filters;
- `/admin/` is untouched.

Run:

       docker compose exec app php tests/test_bi_intermedia_routes.php

Expected: red before route cut.

**Step 2: Build a strict browser interception harness**

Before navigation, intercept:

- session bootstrap;
- BI projects/weeks/filter options;
- the one report GET;
- the one management POST only in save tests;
- refreshed report GET.

Fail immediately on:

- pilot list/Pareto/metric/lineage GET;
- any unexpected route;
- any mutation outside the exact authorized POST;
- wrong ID, headers or body;
- duplicate POST.

No request may reach MySQL.

**Step 3: Write browser scenarios**

Cover:

- A allowed;
- D/R gate representation;
- hidden page 404;
- unauthorized project 403;
- single read/manage;
- multi read-only;
- huerfanas and clear state;
- overdue and no-date distinction;
- empty/partial/insufficient/offline;
- factual observed zero and predictive unavailable;
- filter/clear/show-more;
- drawer keyboard/focus/Escape/return;
- successful POST + refresh;
- 422, CSRF, 404 and network save errors;
- saved-refresh-pending GET-only recovery;
- S07/S21 navigation;
- 390x844, 480x900, 768x1024, 1180x820, 1440x900;
- dark and light;
- 200 percent zoom;
- reduced motion;
- axe serious/critical zero;
- console error zero.

Do not take/update goldens.

**Step 4: Implement the route cut**

- Register S20 route in T01.
- Make `BiViewController::intermedia` serve the SPA shell through the canonical T01 path.
- Preserve the old route implementation behind code rollback until Task 11 proves the cut.
- Do not read or change `CT_PILOTO` yet in this step.

**Step 5: Run focused browser evidence**

       npx playwright test tests/browser/bi-intermedia-react.spec.mjs --workers=1

Then inspect console/network assertions from the test report. Read the return code separately.

**Step 6: Run frontend/PHP focused suites**

       docker compose exec app php tests/test_bi_intermedia_routes.php
       docker compose exec app php tests/test_bi_intermedia_access_query.php
       docker compose exec app php tests/test_bi_intermedia_read_contract.php
       docker compose exec app php tests/test_bi_intermedia_management_contract.php
       cd frontend
       npm test -- src/lib/api/esquemas/biIntermedia.test.ts \
         src/lib/api/biIntermedia.test.ts src/modulos/bi/IntermediaPagina.test.tsx \
         src/modulos/bi/intermedia
       npm run typecheck

**Step 7: Future implementation commit**

       git add <only-reviewed-S20-route-navigation-browser-paths>
       git commit -m "feat(bi): cut intermedia to main react spa"

Do not execute this commit now.

## Task 11: Retire the pilot safely, prove rollback and close the vertical

**Files:**

- Modify: `src/Controllers/Bi/BiViewController.php`
- Modify: `views/bi/_layout.php`
- Modify: `public/js/modules/bi-spa.js`
- Delete after census: `views/bi/control-tower-piloto.php`
- Delete after census: `ct-app/`
- Delete after census: `public/ct-app/assets/ct.js`
- Delete after census: `public/ct-app/assets/ct.css`
- Delete after census: `docs/design-system/manifests/torre-piloto.json`
- Modify: `docs/design-system/manifests/bi-runtime.json`
- Modify: only caller-specific pilot tests proven obsolete

**Step 1: Census every pilot caller**

       rg -n "CT_PILOTO|__CT_PILOTO_ENABLED__|__CT_BOOTSTRAP__|ct-app|public/ct-app|control-tower-piloto|control-tower/restricciones|restricciones/pareto|pi_semaforo_semana_|ct-piloto-theme" \
         --glob '!docs/superpowers/**' --glob '!vendor/**' --glob '!node_modules/**' .

Classify each hit:

- S20 page caller to remove;
- shared endpoint consumer to retain;
- historical documentation to keep;
- test to replace;
- asset/build/config to delete.

Do not delete a shared route while any live caller remains.

**Step 2: Prove parity gate before deletion**

Required green evidence:

- all 108 S20 criteria traced;
- focused PHP/front-end/browser suites;
- allowed/hidden/denied access;
- single/multi capability behavior;
- exact save path;
- all states;
- five viewports, dark/light, zoom, axe, console/network;
- no DML;
- no unexpected mutation;
- no golden update;
- reviewed diff.

If any gate is red, keep the pilot/flag and stop only this retirement task.

**Step 3: Remove the island and flag**

After the gate:

- remove `ctPilotoEnabled`, its env reader and bootstrap branch;
- remove `window.__CT_PILOTO_ENABLED__` and legacy navigation jump;
- remove pilot view/assets/source/toolchain;
- remove local theme storage/tokens;
- update `bi-runtime` manifest;
- delete `torre-piloto` manifest;
- retain shared endpoints with live callers;
- retain shared legacy BI assets until T03/S17–S24.

Do not edit `.env`; removing code consumption makes the old variable inert.

**Step 4: Run source and route absence checks**

       rg -n "CT_PILOTO|__CT_PILOTO_ENABLED__|ct-piloto-theme|public/ct-app|control-tower-piloto" \
         src views public frontend docs/design-system tests --glob '!docs/superpowers/**'
       docker compose exec app php tests/test_bi_intermedia_routes.php

Expected: no live pilot/flag/theme hit; shared endpoint hits are separately justified.

**Step 5: Prove code-only rollback**

In a disposable test branch or reviewed patch application:

1. restore the pre-cut route/code commit;
2. verify the legacy route can render using static/intercepted responses;
3. restore the S20 cut;
4. rerun the focused route/browser smoke;
5. record that no data restoration was needed.

Do not use destructive reset/checkout and do not touch database state.

**Step 6: Final verification before any completion claim**

       git diff --check
       docker compose exec app php tests/test_bi_intermedia_access_query.php
       docker compose exec app php tests/test_bi_intermedia_contract_characterization.php
       docker compose exec app php tests/test_bi_intermedia_constraints.php
       docker compose exec app php tests/test_bi_intermedia_meeting_model.php
       docker compose exec app php tests/test_bi_intermedia_read_contract.php
       docker compose exec app php tests/test_bi_intermedia_management_contract.php
       docker compose exec app php tests/test_bi_intermedia_source_invariants.php
       docker compose exec app php tests/test_bi_intermedia_routes.php
       cd frontend
       npm test -- src/lib/api/esquemas/biIntermedia.test.ts \
         src/lib/api/biIntermedia.test.ts src/modulos/bi/IntermediaPagina.test.tsx \
         src/modulos/bi/intermedia
       npm run typecheck
       npm run lint
       cd ..
       npx playwright test tests/browser/bi-intermedia-react.spec.mjs --workers=1

Read every return code on its own line. Do not claim completion from old output.

**Step 7: Review the final diff boundaries**

       git status --short
       git diff --stat
       git diff -- src/Controllers/Api/BiControlTowerApiController.php \
         src/Controllers/Api/BiConstraintWriteController.php src/Controllers/Bi/BiViewController.php \
         src/Services/Bi/Intermedia frontend/src/lib/api frontend/src/modulos/bi \
         public/js/modules/bi-spa.js views/bi docs/design-system tests

Confirm:

- no `/admin/`;
- no RLS/security-catalog/schema/migration/data file;
- no `.env`;
- no generated golden;
- no unrelated user work;
- no unreviewed bundle.

**Step 8: Future implementation commit**

       git add <only-the-reviewed-S20-retirement-and-evidence-paths>
       git commit -m "refactor(bi): retire intermedia pilot island"

Do not execute this commit in the planning session. Branch finishing, PR, publication and deploy
remain separate gates and authorizations.

## Traceability Matrix

Every acceptance criterion appears exactly once.

| Criterion | Task |
|---|---:|
| S20-AC-01 | 11 |
| S20-AC-02 | 10 |
| S20-AC-03 | 1 |
| S20-AC-04 | 11 |
| S20-AC-05 | 1 |
| S20-AC-06 | 1 |
| S20-AC-07 | 1 |
| S20-AC-08 | 1 |
| S20-AC-09 | 1 |
| S20-AC-10 | 1 |
| S20-AC-11 | 1 |
| S20-AC-12 | 1 |
| S20-AC-13 | 1 |
| S20-AC-14 | 1 |
| S20-AC-15 | 2 |
| S20-AC-16 | 2 |
| S20-AC-17 | 2 |
| S20-AC-18 | 1 |
| S20-AC-19 | 2 |
| S20-AC-20 | 2 |
| S20-AC-21 | 2 |
| S20-AC-22 | 2 |
| S20-AC-23 | 2 |
| S20-AC-24 | 2 |
| S20-AC-25 | 3 |
| S20-AC-26 | 3 |
| S20-AC-27 | 3 |
| S20-AC-28 | 3 |
| S20-AC-29 | 3 |
| S20-AC-30 | 3 |
| S20-AC-31 | 3 |
| S20-AC-32 | 3 |
| S20-AC-33 | 3 |
| S20-AC-34 | 3 |
| S20-AC-35 | 3 |
| S20-AC-36 | 3 |
| S20-AC-37 | 4 |
| S20-AC-38 | 4 |
| S20-AC-39 | 4 |
| S20-AC-40 | 4 |
| S20-AC-41 | 4 |
| S20-AC-42 | 4 |
| S20-AC-43 | 4 |
| S20-AC-44 | 4 |
| S20-AC-45 | 4 |
| S20-AC-46 | 4 |
| S20-AC-47 | 4 |
| S20-AC-48 | 4 |
| S20-AC-49 | 4 |
| S20-AC-50 | 4 |
| S20-AC-51 | 4 |
| S20-AC-52 | 4 |
| S20-AC-53 | 4 |
| S20-AC-54 | 4 |
| S20-AC-55 | 4 |
| S20-AC-56 | 4 |
| S20-AC-57 | 4 |
| S20-AC-58 | 4 |
| S20-AC-59 | 4 |
| S20-AC-60 | 7 |
| S20-AC-61 | 7 |
| S20-AC-62 | 7 |
| S20-AC-63 | 7 |
| S20-AC-64 | 6 |
| S20-AC-65 | 6 |
| S20-AC-66 | 6 |
| S20-AC-67 | 6 |
| S20-AC-68 | 6 |
| S20-AC-69 | 6 |
| S20-AC-70 | 6 |
| S20-AC-71 | 6 |
| S20-AC-72 | 6 |
| S20-AC-73 | 6 |
| S20-AC-74 | 6 |
| S20-AC-75 | 6 |
| S20-AC-76 | 8 |
| S20-AC-77 | 8 |
| S20-AC-78 | 8 |
| S20-AC-79 | 8 |
| S20-AC-80 | 8 |
| S20-AC-81 | 7 |
| S20-AC-82 | 7 |
| S20-AC-83 | 7 |
| S20-AC-84 | 7 |
| S20-AC-85 | 7 |
| S20-AC-86 | 7 |
| S20-AC-87 | 9 |
| S20-AC-88 | 9 |
| S20-AC-89 | 9 |
| S20-AC-90 | 9 |
| S20-AC-91 | 9 |
| S20-AC-92 | 5 |
| S20-AC-93 | 5 |
| S20-AC-94 | 5 |
| S20-AC-95 | 5 |
| S20-AC-96 | 5 |
| S20-AC-97 | 10 |
| S20-AC-98 | 10 |
| S20-AC-99 | 10 |
| S20-AC-100 | 11 |
| S20-AC-101 | 11 |
| S20-AC-102 | 11 |
| S20-AC-103 | 11 |
| S20-AC-104 | 11 |
| S20-AC-105 | 11 |
| S20-AC-106 | 10 |
| S20-AC-107 | 11 |
| S20-AC-108 | 10 |

## Vertical Checkpoints

1. **Authorized coherent read:** Tasks 1–2 keep legacy usable while proving one authorized
   project/period/filter snapshot and one canonical/compatibility presenter.
2. **Actionable meeting model:** Tasks 3–4 prove restriction chain, orphan/overdue/N4, headline,
   traffic light, Pareto, recommendation and lineage without frontend inference.
3. **Typed transport and safe write:** Tasks 5–6 establish Zod/gateway/state and preserve the exact
   scoped management POST through pure tests.
4. **Usable sheet:** Tasks 7–9 deliver list-before-headline, semantic table/cards, drawer/form,
   save-refresh behavior, states, dark/light and accessibility.
5. **Cut and retirement:** Tasks 10–11 cut the route, prove strict intercepted behavior, remove the
   flag/island only after zero callers and verify code-only rollback.

Stop after every checkpoint for review in an authorized implementation session. Do not combine
checkpoint commits with other surfaces.

## Definition of Done

- All 108 S20 criteria trace exactly once and have fresh focused evidence.
- `/bi/intermedia` is served by the main SPA and uses the shared shell/sidebar/theme/drawer.
- The page makes one canonical report GET and, only for an authorized save, the existing POST.
- Every block has one authorized scope/cutoff/filter context or an explicit limitation.
- Single project may manage according to server capability; multi is read-only.
- Constraint keys are project-qualified and all link queries/writes are project-scoped.
- Orphan, overdue, N4, counts, headline, traffic light, Pareto and recommendations are
  server-authored and reconciled.
- The undocumented 30-percent threshold is gone; no pseudo-predictive claim exists.
- The list is above the headline and exposes complete recoverable cause/chain detail.
- POST validates responsible/date/state, enforces CSRF/capability/isolation and returns a fresh row.
- React parses the fresh row, refreshes the snapshot and never retries a mutation automatically.
- Table/cards are mounted exclusively at 768; one accessible drawer/form edits at every width.
- Dark/light, five viewports, 200 percent zoom, keyboard, touch and reduced motion pass.
- Axe serious/critical, console errors and unexpected network requests are zero.
- Browser evidence is fully intercepted and performs no DML.
- No component fetch, manual response interface, HTML injection, color literal, `!important`,
  local theme or new UI/chart library exists.
- No `/admin/`, RLS, runtime-boundary, permission catalog, schema, grant, user, credential,
  migration, SQL view, data or golden changed.
- `CT_PILOTO`, pilot view/assets/source/toolchain and local theme are removed only after parity
  and zero callers.
- Shared pilot/legacy endpoints remain for live consumers; shared BI retirement waits T03/S17–S24.
- Rollback changes route/code only and requires no data restoration.
- Final evidence names exact SHA, commands, individual return codes, caller census, browser
  limitations and untouched data boundary before any branch-finishing decision.

## Cierre

Estado inicial: plan escrito; implementacion no iniciada.

El cierre futuro debe registrar:

- SHA exacto implementado y diff revisado;
- resultados PHP/frontend/browser con codigos de salida leidos por separado;
- evidencia permitida/oculta/denegada y single/multi;
- evidencia de scope/corte/filtros coherentes;
- evidencia de POST unico, CSRF, body, fila fresca y refetch;
- cinco viewports, ambos temas, zoom, teclado/touch, axe, consola y red;
- prueba de cero DML y frontera RLS/schema/datos intacta;
- censo final de callers y endpoints compartidos retenidos;
- eliminacion de flag/isla o razon concreta para conservarlos;
- resultado de rollback de codigo;
- limites o trabajo diferido.

No cerrar por conteo de checkboxes. No hacer commit, push, PR, publicacion o deploy sin el gate de
cierre del repositorio y la autorizacion correspondiente.
