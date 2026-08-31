---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [lps, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s25-escalamientos-react.md
resumen: "migrate /dashboard/escalamientos into the main React SPA as a project-scoped board of all active LPS crises, preserving four hierarchical attention stations…"
---

# S25 Escalamientos React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Use `superpowers:test-driven-development` for every task and
> `superpowers:verification-before-completion` before any completion claim. Execute tasks in order,
> stop at every vertical checkpoint and do not start a later front until the repository publication
> gate permits it. Checkbox syntax is an execution prompt only; progress and closure live in
> `Cierre` and git history, never in checkbox counts.

**Goal:** migrate `/dashboard/escalamientos` into the main React SPA as a project-scoped board of
all active LPS crises, preserving four hierarchical attention stations, counts, card context,
threaded comments, copy/SOS, close and return behavior while fixing the legacy week-target mismatch,
using the shared T02 drawer on desktop, tablet and mobile in dark and light, without activating
autoescalation or changing RLS, schema, data, grants, users, credentials or `/admin/`.

**Architecture:** T01 owns session, ProjectScope-aware bootstrap, AppShell, sidebar, themes, route
outlet, errors and the sole HTTP client. T02 owns the contextual LPS target, drawer, comments,
crisis mutations and notification entry. S05/S07/S08 own the PG/PI/PS origins. S25 adds one scoped,
SELECT-only `EscalationBoardReadService` and `GET /api/lps/escalamientos`. A shared
`EscalationTargetResolver` derives activity, week and module from alert ID; an actor-eligibility
policy gates only comment/close against the exact historical FK. React renders server actions,
groups a flat snapshot into fixed stations and refetches after mutations. VIEW-12 is strangled at
the route; VIEW-28 waits for T02.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8,
Zod 4, Vitest 4, Testing Library, Playwright, native HTML/CSS and existing AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s25-escalamientos-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or
  another worktree.
- Inspect status and relevant diff before every task. Preserve all existing work; never clean,
  revert, stage or reformat adjacent paths.
- This planning session is documentation-only. Future commands below do not authorize
  implementation, dependencies, build, commit, push, PR, publication, deploy or data changes now.
- Start implementation only after the required T01/T02 seams and S05/S07/S08 target producers
  exist, are published as required by the repository gate and pass focused tests. If absent, execute
  their owning plan; do not build temporary duplicates inside S25.
- `/admin/` is excluded.
- Do not modify RLS, `ProjectScope` semantics, `ProjectSqlGuard`, runtime-boundary rules, schema,
  migrations, tables, columns, indexes, triggers, foreign keys, views, grants, users, credentials,
  memberships, role aliases, capability catalogs, seeds, fixtures or persistent data.
- No DDL/DML, even in a transaction that rolls back. New tests use pure value objects, repositories
  fake, source invariants, call logs, read-only SELECT and intercepted HTTP.
- Do not run `tests/browser/escalamientos-acciones.spec.mjs` while it inserts/deletes alerts. Inspect
  every existing test for write verbs before executing it.
- Do not invoke, schedule, route or expose `LpsService::escalarAlertasActivas()`.
- The board is project-global. Never filter it by active shell week; each alert owns its week.
- Never accept `project_id`, `db`, prefix, project name, user, role, week, module or activity from
  the browser as authorization for an alert target.
- Page and board API require `lps.programacion_semanal.ver`. Comment, register/SOS and close require
  `lps.programacion_semanal.editar`. React consumes server actions and contains no role table.
- Actor eligibility gates only writes that persist actor IDs in the current FK: comment and close.
  Do not accidentally remove authorized register/copy behavior for an otherwise incompatible actor.
- Preserve the exact actor numeric-ID semantics; do not map by name/email/cargo or create a
  professional.
- Mentions are thread metadata, not delivered notifications. S25 does not call
  `/api/notifications/*`, create polling or duplicate the shell bell.
- Registering an already-active alert does not change `nivel_actual`. Never move a card
  optimistically or claim external delivery.
- Simulation remains copy-only. Missing contacts remain an explicit clipboard fallback.
- Existing comment/crisis aliases remain until the T02 caller census reaches zero.
- All productive HTTP calls live in `frontend/src/lib/api/cliente.ts`. Hooks/components call typed
  gateways; strict Zod parses every success and error.
- No automatic mutation retry. Abort replaced reads, ignore stale completions and refetch target and
  snapshot after success.
- Use only `public/css/tokens.css`. No literal colors, inline color styles, `!important`, local
  token family, jQuery, Bootstrap, Handsontable, CSS-in-JS or new state/query/UI library.
- Dark is default/fallback; light is equivalent. Validate 390×844, 480×900, 768×1024, 1180×820,
  1440×900, 320 px and 200% zoom.
- New browser scenarios intercept all APIs and fail on unexpected URL, method, console error,
  page error or real mutation. Fixtures use synthetic names/text.
- Every browser scenario first proves authenticated session, active project, exact URL, h1 and
  board. Login is never accepted as Escalamientos evidence; unavailable dev door is explicit.
- Do not regenerate or commit visual goldens.
- Keep VIEW-12, `public/css/escalamientos.css` and legacy JS during pilot. Remove only exclusive
  assets after route/caller gates. Keep VIEW-28 and shared drawer assets until T02.
- Rollback changes code/routes only and never data.

## Dependency Gate

Before Task 1 in a future implementation session:

1. Read closure records for T01, T02, S05, S07 and S08.
2. Verify T01 exposes:
   - server-driven navigation groups/actions;
   - AppShell/route outlet;
   - dark-first theme bootstrap;
   - typed errors and `cliente.ts` with JSON/form/AbortSignal;
   - project/session invalidation.
3. Verify T02 exposes:
   - `LpsTarget` or equivalent immutable target;
   - one shared drawer;
   - strict comment/mutation schemas and gateway;
   - focus/inert/return-focus lifecycle;
   - single shell notification entry.
4. Verify S05/S07/S08 pass alert targets with canonical activity/module/week and no independent
   drawer copy.
5. Verify current schema/FK metadata read-only; do not change it.
6. Verify exact branch/runtime once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch; `app`, `db` and `adminer` available; app mounted from this
   worktree.
7. Record starting SHA and every pre-existing changed path. Do not stage them.
8. Confirm `ProjectSqlGuard` rejects an unscoped operational read using the existing safe contract
   test; do not probe by weakening scope.
9. Inspect every candidate test for INSERT/UPDATE/DELETE/DDL, seed helpers and cleanup hooks.
10. Confirm `LpsService::escalarAlertasActivas()` still has no productive caller.
11. Confirm the parent checkout has no S25 artifacts and do not edit `.env`.
12. If any dependency is absent/unpublished, stop S25 and execute its owner. Do not add fallback
    code to bypass the dependency.

## File Structure

### Create — backend

- `src/Services/Lps/EscalationBoardRepository.php` — scoped read interface.
- `src/Services/Lps/ScopedEscalationBoardRepository.php` — `queryWithProject` implementation.
- `src/Services/Lps/EscalationHierarchy.php` — levels, display station, next level and terminal.
- `src/Services/Lps/EscalationActionPolicy.php` — read/edit/profile/terminal actions.
- `src/Services/Lps/EscalationTarget.php` — immutable alert/activity/week/module target.
- `src/Services/Lps/EscalationTargetResolver.php` — project+alert lookup.
- `src/Services/Lps/LpsActorEligibility.php` — exact current-FK compatibility.
- `src/Services/Lps/EscalationBoardReadService.php` — orchestration, normalization, counts.
- `src/Services/Lps/EscalationBoardPresenter.php` — safe API shape and legacy adapter seam.

### Create — frontend

- `frontend/src/modules/escalamientos/api/esquemas.ts` and `esquemas.test.ts`.
- `frontend/src/modules/escalamientos/api/escalamientos.ts` and `escalamientos.test.ts`.
- `frontend/src/modules/escalamientos/dominio/jerarquia.ts` and `jerarquia.test.ts`.
- `frontend/src/modules/escalamientos/dominio/agrupar.ts` and `agrupar.test.ts`.
- `frontend/src/modules/escalamientos/estado/useEscalamientos.ts` and `useEscalamientos.test.tsx`.
- `frontend/src/modules/escalamientos/estado/useEscalamientoSeleccionado.ts` and test.
- `frontend/src/modules/escalamientos/componentes/CabeceraEscalamientos.tsx`.
- `frontend/src/modules/escalamientos/componentes/ResumenEscalamientos.tsx`.
- `frontend/src/modules/escalamientos/componentes/SelectorNivelEscalamiento.tsx`.
- `frontend/src/modules/escalamientos/componentes/TableroEscalamientos.tsx`.
- `frontend/src/modules/escalamientos/componentes/ColumnaEscalamiento.tsx`.
- `frontend/src/modules/escalamientos/componentes/TarjetaEscalamiento.tsx`.
- `frontend/src/modules/escalamientos/componentes/EstadoEscalamientos.tsx`.
- `frontend/src/modules/escalamientos/componentes/LeyendaEscalamientos.tsx`.
- `frontend/src/modules/escalamientos/PaginaEscalamientos.tsx` and test.
- `frontend/src/modules/escalamientos/escalamientos.css`.

### Create — tests/evidence contracts

- `tests/test_escalamientos_access_policy.php`.
- `tests/test_escalamientos_navigation_contract.php`.
- `tests/test_escalamientos_hierarchy.php`.
- `tests/test_escalamientos_read_service.php`.
- `tests/test_api_escalamientos_contract.php`.
- `tests/test_lps_escalation_target.php`.
- `tests/test_lps_escalation_thread_contract.php`.
- `tests/test_lps_actor_eligibility.php`.
- `tests/test_lps_escalation_actions_contract.php`.
- `tests/test_escalamientos_no_write_invariants.php`.
- `tests/browser/fixtures/escalamientos-react.mjs`.
- `tests/browser/escalamientos-react.spec.mjs`.
- `tests/browser/escalamientos-react.a11y.mjs`.
- `tests/browser/escalamientos-react.visual.mjs` only as uncommitted candidates.

### Modify — integration

- `src/Controllers/Api/LpsApiController.php` — dependency injection, new GET and canonical targets.
- `src/Controllers/Core/DashboardController.php` — shared read policy during pilot; retire VIEW-12
  render after cut.
- `src/Services/LpsService.php` — delegate read/target validation while preserving transaction
  semantics and dormant autoescalation.
- `public/index.php` — register GET and cut page route.
- `src/Core/SpaRouter.php`, `tests/test_spa_frontera.php` and
  `tests/test_spa_frontera_http.php` — pilot/canonical route and refresh.
- T01 navigation implementation present at execution time, expected
  `src/Controllers/Api/SessionApiController.php` plus its extracted manifest — add one
  server-authorized item; do not add a client role table.
- `frontend/src/lib/api/cliente.ts` and test only if T02 did not already supply the exact form/error
  behavior; extend shared behavior rather than branch S25.
- T02 schemas/gateway/drawer files — add alert target and S25 adapter, never fork them.
- `frontend/src/shell/rutas.tsx` and test — S25 route/outlet.
- `frontend/src/shell/navegacion/BarraLateral.tsx` and test, or the exact T01 successor — active
  state only; navigation rows still come from server.
- `frontend/src/App.tsx` only if T01 router registration requires it.
- `docs/design-system/manifests/escalamientos.json`,
  `docs/design-system/manifests/inventory.json` and `tests/design-system/contracts.test.mjs` —
  declare React sources, two themes, viewports, states and tests.
- Existing safe escalation/drawer tests — retarget assertions only after equivalent React coverage.
- `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — closure status only
  after evidence.

### Preserve

- `src/Core/Database.php`, `TableResolver` and all RLS/runtime-boundary files.
- `RbacCatalog` permissions/roles and project overrides.
- `LpsService::escalarAlertasActivas()` exactly dormant.
- Current transaction semantics of register/close.
- T01 shell, T02 drawer/notification ownership and S05/S07/S08 origin screens.
- Every `/admin/` path.
- All database assets and data.

### Retire only after post-canonical gate

- `views/dashboard/escalamientos.php` (VIEW-12).
- `public/css/escalamientos.css` when reference audit proves exclusive.
- VIEW-12-only inline adapter/third-party includes.
- Legacy page branch in `DashboardController::escalamientos()`.
- Existing tests that assert the PHP document rather than behavior, only after equivalent coverage.

Do not retire VIEW-28, `lps_drawer.js` or comment/crisis aliases until T02 reaches zero consumers.

## Task 1: Lock access, scope, global-week semantics and navigation declaration

**Files:**
- Create `src/Services/Lps/EscalationActionPolicy.php`.
- Create `tests/test_escalamientos_access_policy.php`.
- Create `tests/test_escalamientos_navigation_contract.php`.
- Inspect T01 navigation manifest and `DashboardController`; no route cut yet.

**Goal:** no alert reader runs before session, active project and effective weekly-view capability;
the board is declared once in authorized navigation and is explicitly independent from shell week.

- [ ] **Step 1: Write failing policy tests**

Cover allowed/denied/override cases, missing session/project, foreign project, view vs edit, reader
call count zero on denial and proof that changing week does not alter the board scope key.

Run:

    docker compose exec -T app php tests/test_escalamientos_access_policy.php

Expected: FAIL because the policy/board action shape does not exist.

- [ ] **Step 2: Implement the minimal pure action policy**

Consume `RbacService` decisions; emit booleans and actor block separately. Do not edit the catalog or
normalize roles in React.

- [ ] **Step 3: Write navigation contract failures**

Require id/label/group/order/href/capability, server filtering, no client role branch and no item for
a denied user.

Run:

    docker compose exec -T app php tests/test_escalamientos_navigation_contract.php

Expected: FAIL until the T01 manifest row is supplied through its real seam.

- [ ] **Step 4: Add only the manifest declaration**

Group `obra`, after Programación Semanal, href `/dashboard/escalamientos`. Do not create a second
sidebar or page guard yet.

- [ ] **Step 5: Run focused tests**

    docker compose exec -T app php tests/test_escalamientos_access_policy.php
    docker compose exec -T app php tests/test_escalamientos_navigation_contract.php

Expected: PASS with zero repository calls on denial and no data writes.

- [ ] **Step 6: Future atomic commit**

    git add src/Services/Lps/EscalationActionPolicy.php tests/test_escalamientos_access_policy.php tests/test_escalamientos_navigation_contract.php
    git commit -m "test(lps): lock escalation access and navigation"

Do not run git commands in this documentation session.

**Vertical checkpoint:** the server can decide admission/actions and declare navigation without
reading an alert.

## Task 2: Build the scoped active-alert read model and hierarchy

**Files:**
- Create repository, hierarchy, read service and presenter listed above.
- Create `tests/test_escalamientos_hierarchy.php`.
- Create `tests/test_escalamientos_read_service.php`.
- Create `tests/test_escalamientos_no_write_invariants.php`.

**Goal:** produce a complete safe snapshot for one project with no controller, React or write.

- [ ] **Step 1: Write hierarchy decision-table tests**

Cover current levels 1–5, display 1→2, labels, next, terminal, fixed level order, count sums and
stable sort including null dates/ties.

Run:

    docker compose exec -T app php tests/test_escalamientos_hierarchy.php

Expected: FAIL because hierarchy types do not exist.

- [ ] **Step 2: Implement `EscalationHierarchy`**

Use finite constants/value objects. Keep current/display/next distinct. Reject rather than coerce
levels outside 1–5.

- [ ] **Step 3: Write reader/service failures**

Use fake raw rows to cover active only, cross-project rejection, project+activity+week join,
mixed legacy types, HTML stripping, whitespace, fallbacks, modules, ISO dates, privacy omissions,
four empty levels, global empty and ordering.

- [ ] **Step 4: Implement repository/read service/presenter**

Repository uses `queryWithProject` under already-bound scope. Service normalizes and counts.
Presenter emits only the spec shape. No active-week precondition.

- [ ] **Step 5: Add no-write/autoescalation invariants**

Production class graph/call log must contain no write verb, transaction, scheduler or call to
`escalarAlertasActivas`. GET may only invoke the reader.

Run:

    docker compose exec -T app php tests/test_escalamientos_read_service.php
    docker compose exec -T app php tests/test_escalamientos_no_write_invariants.php

- [ ] **Step 6: Future atomic commit**

    git add src/Services/Lps/EscalationBoardRepository.php src/Services/Lps/ScopedEscalationBoardRepository.php src/Services/Lps/EscalationHierarchy.php src/Services/Lps/EscalationBoardReadService.php src/Services/Lps/EscalationBoardPresenter.php tests/test_escalamientos_hierarchy.php tests/test_escalamientos_read_service.php tests/test_escalamientos_no_write_invariants.php
    git commit -m "feat(lps): read scoped escalation board"

**Vertical checkpoint:** PHP can create the full project-global snapshot from fakes/SELECT-only
without HTTP or side effects.

## Task 3: Expose GET /api/lps/escalamientos with a typed contract

**Files:**
- Modify `src/Controllers/Api/LpsApiController.php`.
- Modify `public/index.php`.
- Create `tests/test_api_escalamientos_contract.php`.
- Update safe routing contract if it enumerates the new route.

**Goal:** one authenticated GET returns the snapshot and typed errors without requiring session week.

- [ ] **Step 1: Write controller contract failures**

Inject fake policy/reader. Cover success, empty, missing session/project, capability denied,
repository exception, request ID, no-store, JSON content type and zero reader calls on denial.

Run:

    docker compose exec -T app php tests/test_api_escalamientos_contract.php

Expected: FAIL because route/controller action are absent.

- [ ] **Step 2: Add dependency injection and action**

Do not construct authority from request parameters. Bind ProjectScope through the existing request
lifecycle, authorize, call read service and pass presenter output.

- [ ] **Step 3: Register exact GET route**

Add only `GET /api/lps/escalamientos`. Do not add POST, aliases or week query.

- [ ] **Step 4: Prove GET cannot write**

Run the contract with a fake that throws on transaction/write and verify method/route inventory.

- [ ] **Step 5: Run focused tests**

    docker compose exec -T app php tests/test_api_escalamientos_contract.php
    docker compose exec -T app php tests/test_escalamientos_no_write_invariants.php

- [ ] **Step 6: Future atomic commit**

    git add src/Controllers/Api/LpsApiController.php public/index.php tests/test_api_escalamientos_contract.php
    git commit -m "feat(api): expose escalation board snapshot"

**Vertical checkpoint:** curl/controller-level GET can return a strict scoped snapshot; no frontend
exists yet.

## Task 4: Resolve drawer threads by alert instead of shell week

**Files:**
- Create `src/Services/Lps/EscalationTarget.php`.
- Create `src/Services/Lps/EscalationTargetResolver.php`.
- Modify comment read/add seams in `LpsApiController.php` and `LpsService.php`.
- Create `tests/test_lps_escalation_target.php`.
- Create `tests/test_lps_escalation_thread_contract.php`.

**Goal:** historical cards read/write the exact alert thread; client week/module/activity can never
redirect the target.

- [ ] **Step 1: Write target resolver failures**

Cover active alert in project, foreign project, missing/closed, activity/week/module extraction,
duplicate IDs across projects, no positive shell week and ignored authority fields.

- [ ] **Step 2: Implement immutable target resolver**

Query by active ProjectScope + alert ID. Return typed target. Use the alert's week, activity and
module for all downstream calls.

- [ ] **Step 3: Write thread contract failures**

Cover canonical `alerta_id` read/add, root/reply order, empty, parent same target, escalation ID same
target, malformed mentions, role tokens, aliases, capability, CSRF and no user/project/week/module
authority from body.

- [ ] **Step 4: Adapt comments through the target**

Canonical React path uses alert. Legacy consecutive/escalation aliases remain but delegate through
one policy/validator and cannot cross scope. Preserve recursive output through the T02 presenter.

- [ ] **Step 5: Run focused tests**

    docker compose exec -T app php tests/test_lps_escalation_target.php
    docker compose exec -T app php tests/test_lps_escalation_thread_contract.php
    docker compose exec -T app php tests/test_csrf_lps_api.php

Expected: PASS with fake services and zero INSERT.

- [ ] **Step 6: Future atomic commit**

    git add src/Services/Lps/EscalationTarget.php src/Services/Lps/EscalationTargetResolver.php src/Controllers/Api/LpsApiController.php src/Services/LpsService.php tests/test_lps_escalation_target.php tests/test_lps_escalation_thread_contract.php
    git commit -m "fix(lps): bind drawer threads to escalation alerts"

**Vertical checkpoint:** an alert from an old week resolves its own thread under the current project,
without a real write.

## Task 5: Make actor eligibility and server actions explicit

**Files:**
- Create `src/Services/Lps/LpsActorEligibility.php`.
- Extend `EscalationActionPolicy.php` and board/target presenters.
- Create `tests/test_lps_actor_eligibility.php`.
- Extend access/read/thread contract tests.

**Goal:** avoid generic FK failures while preserving read and authorized register/copy behavior.

- [ ] **Step 1: Write eligibility failures**

Cases: exact general-user ID has matching project professional ID, absent, same ID foreign project,
name/email-only near match, edit denied, stale client action and read-only continuity.

- [ ] **Step 2: Implement exact compatibility check**

Return `eligible|profile_required|forbidden`. No sync, creation, mutation or text matching.

- [ ] **Step 3: Write action-matrix failures**

Verify:
- read board/thread follows view;
- comment/close follow edit + eligibility;
- notify/register follows edit + nonterminal hierarchy and is not blocked by actor FK;
- level 5 has no next;
- every item action is server-authored.

- [ ] **Step 4: Integrate action shape**

Use `actorWriteBlock`; never one coarse boolean that disables unrelated actions. A stale write gets
`PROFILE_REQUIRED`.

- [ ] **Step 5: Run focused tests**

    docker compose exec -T app php tests/test_lps_actor_eligibility.php
    docker compose exec -T app php tests/test_escalamientos_access_policy.php
    docker compose exec -T app php tests/test_escalamientos_read_service.php

- [ ] **Step 6: Future atomic commit**

    git add src/Services/Lps/LpsActorEligibility.php src/Services/Lps/EscalationActionPolicy.php src/Services/Lps/EscalationBoardReadService.php src/Services/Lps/EscalationBoardPresenter.php tests/test_lps_actor_eligibility.php tests/test_escalamientos_access_policy.php tests/test_escalamientos_read_service.php
    git commit -m "feat(lps): expose safe escalation actions"

**Vertical checkpoint:** server payload distinguishes read, actor-bound write and SOS/terminal
actions without changing identity data.

## Task 6: Harden comment, copy/SOS and close semantics

**Files:**
- Modify canonical mutation adapters in `LpsApiController.php` and `LpsService.php`.
- Create `tests/test_lps_escalation_actions_contract.php`.
- Extend T02 gateway/presenter contracts if already published.
- Preserve aliases and dormant autoescalation.

**Goal:** every visible action reports its real effect and returns enough state for an authoritative
refetch.

- [ ] **Step 1: Write comment success/error contracts**

With fakes, cover trimmed nonempty text, positive comment ID, reload instruction, parent/mentions,
profile required, forbidden, CSRF, validation and no automatic retry.

- [ ] **Step 2: Write register/SOS contracts**

Cover alert-derived activity/week/module, trigger enum, existing active alert, terminal, forbidden,
simulation no-request and response copy that never says level changed or message delivered.

- [ ] **Step 3: Write close contracts**

Cover 99/100 characters, whitespace, active/closed/missing/foreign alerts, profile required,
transaction success/failure via fake and typed conflict.

Run:

    docker compose exec -T app php tests/test_lps_escalation_actions_contract.php

Expected: FAIL until adapters use the canonical target/error shape.

- [ ] **Step 4: Implement minimal adapters**

Keep current transaction service. Add preflight and sanitized errors around it. Do not modify SQL,
actor storage or level logic.

- [ ] **Step 5: Re-run CSRF and alias contracts**

    docker compose exec -T app php tests/test_lps_escalation_actions_contract.php
    docker compose exec -T app php tests/test_lps_escalation_thread_contract.php
    docker compose exec -T app php tests/test_csrf_lps_api.php

Expected: PASS, all service doubles and no database mutation.

- [ ] **Step 6: Future atomic commit**

    git add src/Controllers/Api/LpsApiController.php src/Services/LpsService.php tests/test_lps_escalation_actions_contract.php tests/test_lps_escalation_thread_contract.php
    git commit -m "fix(lps): report escalation action effects accurately"

**Vertical checkpoint:** all drawer actions have typed, truthful, fake-tested contracts.

## Task 7: Add strict Zod, gateway and remote state

**Files:**
- Create S25 API/schema/state files listed above.
- Modify `cliente.ts` only through shared T01/T02 seams when required.
- Add focused Vitest files.

**Goal:** React can load, cancel, cache, refresh and invalidate S25 without rendering yet.

- [ ] **Step 1: Write strict schema failures**

Cover all four levels, item refinements, total mismatch, level 1 display 2, terminal/next consistency,
ISO/null dates, action/profile combinations, unknown keys and every typed error.

Run:

    cd frontend
    npm test -- --run src/modules/escalamientos/api/esquemas.test.ts

Expected: FAIL because schema is absent.

- [ ] **Step 2: Implement schemas/types**

Use `z.strictObject`/refinements and `z.infer`. Do not hand-maintain duplicate interfaces.

- [ ] **Step 3: Write gateway/remote-state failures**

Cover exact GET, no query/week, `cliente.ts` only, AbortSignal, session/project cache partition,
loading/refreshing/error, stale ignore, manual refresh, deep-link lookup and post-action refetch.

- [ ] **Step 4: Implement gateway/hooks**

No fetch/import outside client. Changing project/session clears everything; changing week does not
reload. Mutations never auto-retry.

- [ ] **Step 5: Run focused tests/typecheck**

    cd frontend
    npm test -- --run src/modules/escalamientos/api src/modules/escalamientos/estado
    npm run typecheck

- [ ] **Step 6: Future atomic commit**

    git add frontend/src/modules/escalamientos/api frontend/src/modules/escalamientos/estado frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts
    git commit -m "feat(frontend): add escalation data boundary"

**Vertical checkpoint:** a hook can expose a validated snapshot and lifecycle with no S25 DOM.

## Task 8: Render the board, responsive compositions, states and legend

**Files:**
- Create S25 page/components/domain/CSS listed above.
- Create/extend component/domain tests.
- Modify design-system manifest after behavior exists.

**Goal:** deliver the useful read-only board in AppShell with complete state and both themes.

- [ ] **Step 1: Write grouping/presentation failures**

Cover fixed stations, 1→2, stable order, total/counts, card fields/fallbacks and no duplicate item.

- [ ] **Step 2: Implement pure grouping and initial page**

Render h1, project, all-weeks scope, total, reload, four level headers, cards and global/per-level
empty. Card is a native button.

- [ ] **Step 3: Write responsive/theme failures**

Use matchMedia/container helpers to assert:
- >=1180 four columns;
- 768–1179 2×2;
- <768 one mounted level with all counts;
- dark default/light equivalent;
- no page overflow assumptions or duplicated hidden board.

- [ ] **Step 4: Implement token-driven CSS and mobile selector**

No literal color/local tokens. Use approved DS primitives. Do not load vendor CSS.

- [ ] **Step 5: Write product-state/legend failures**

Cover loading skeleton, refreshing snapshot, stale/offline, all typed errors, retry, announcements,
legend meanings, 44 px targets, title and safe return fallback.

- [ ] **Step 6: Implement states and legend**

Preserve last valid snapshot on recoverable network error. Do not invent search/filter/export.

- [ ] **Step 7: Run focused tests**

    cd frontend
    npm test -- --run src/modules/escalamientos/dominio src/modules/escalamientos/componentes src/modules/escalamientos/PaginaEscalamientos.test.tsx
    npm run typecheck

- [ ] **Step 8: Future atomic commit**

    git add frontend/src/modules/escalamientos docs/design-system/manifests/escalamientos.json
    git commit -m "feat(frontend): render responsive escalation board"

**Vertical checkpoint:** intercepted snapshot produces an accessible read-only board in all
compositions/themes; drawer is not wired yet.

## Task 9: Integrate the shared T02 drawer, deep links and focus lifecycle

**Files:**
- Modify T02 target/schema/gateway/drawer files present at execution time.
- Modify `useEscalamientoSeleccionado.ts`, page and card components.
- Add integration tests in S25/T02, without creating a local drawer.

**Goal:** one card activation opens the exact alert target; URL/back/focus and partial errors are
predictable.

- [ ] **Step 1: Write selection/deep-link failures**

Cover click/Enter/Space, `?alerta`, refresh, foreign/missing/closed target, browser Back closes drawer,
forward reopens, project switch clears and no fallback to another card.

- [ ] **Step 2: Integrate immutable T02 target**

Pass alert ID and server item context. T02 loads authoritative target/thread. Never pass shell week
as target authority.

- [ ] **Step 3: Write focus/accessibility failures**

Cover focus trap, inert background, Escape/button, return to existing card/level heading, unsent text
protection, live announcements, headings/landmarks and no color-only information.

- [ ] **Step 4: Add comment/action UI using server actions**

Profile required leaves thread readable; terminal hides next; simulation copies without request;
errors preserve draft; success reloads thread and board.

- [ ] **Step 5: Run focused tests**

    cd frontend
    npm test -- --run src/modules/escalamientos
    npm run typecheck

Run T02's focused drawer suite as named in its closure record.

- [ ] **Step 6: Future atomic commit**

    git add frontend/src/modules/escalamientos frontend/src/shared
    git commit -m "feat(frontend): connect escalation contextual drawer"

Adjust the shared path to T02's actual published location; never create `frontend/src/shared` only to
satisfy this command.

**Vertical checkpoint:** synthetic cards support the complete drawer lifecycle and actions through
intercepted contracts.

## Task 10: Integrate route/sidebar, preserve notification ownership and cut VIEW-12

**Files:**
- Modify T01 route/sidebar/session manifest files.
- Modify `SpaRouter.php`, `public/index.php` and route tests.
- Modify design-system manifest/contracts.
- Retire VIEW-12/exclusive assets only after gate.
- Record caller census.

**Goal:** canonical URL refreshes into React, navigation is authorized, shell bell remains unique and
rollback remains code-only.

- [ ] **Step 1: Write pilot/canonical route failures**

Cover `/app/dashboard/escalamientos` if required, canonical refresh, API exclusion, active link,
denied route, 404, title, history fallback and rollback map.

- [ ] **Step 2: Register React route/outlet**

Keep PHP canonical during pilot. Verify page through intercepted APIs.

- [ ] **Step 3: Add notification-boundary assertions**

Assert S25 imports/calls no notification endpoint/poller and AppShell contains one T02 entry.

- [ ] **Step 4: Run pre-cut caller census**

Search VIEW-12, CSS, inline adapter, drawer partial, aliases and autoescalation. Classify every
caller. Do not remove shared pieces.

- [ ] **Step 5: Pass cut gate, then update SpaRouter**

Only after Tasks 1–9 and safe route/browser tests are green. Canonical GET serves SPA; API and
assets remain unaffected.

- [ ] **Step 6: Retire exclusive legacy pieces**

Remove VIEW-12 and `public/css/escalamientos.css` only when census proves exclusivity. Preserve
VIEW-28, shared JS/CSS and aliases. Update manifest paths/tests to React.

- [ ] **Step 7: Run route/design contracts**

    docker compose exec -T app php tests/test_spa_frontera.php
    docker compose exec -T app php tests/test_spa_frontera_http.php
    node --test tests/design-system/contracts.test.mjs

- [ ] **Step 8: Future atomic commit**

    git add src/Core/SpaRouter.php public/index.php src/Controllers/Core/DashboardController.php frontend/src/shell frontend/src/modules/escalamientos docs/design-system/manifests/escalamientos.json tests/test_spa_frontera.php tests/test_spa_frontera_http.php tests/design-system/contracts.test.mjs views/dashboard/escalamientos.php public/css/escalamientos.css
    git commit -m "feat(frontend): cut escalation route to React"

Stage only paths that actually changed; a removed path is included only after the gate.

**Vertical checkpoint:** the canonical route is React, bell remains unique, VIEW-12 is gone and
rollback is a route/code revert.

## Task 11: Run focused-to-broad contracts and integrity audits

**Files:**
- No production changes expected.
- Update tests only to fix genuine coverage defects, never to bless a regression.
- Produce local evidence outside git unless the repository contract names a ledger.

**Goal:** prove backend/frontend/RLS/no-write boundaries on the exact candidate before browser cut
evidence.

- [ ] **Step 1: Run PHP focused suite**

    docker compose exec -T app php tests/test_escalamientos_access_policy.php
    docker compose exec -T app php tests/test_escalamientos_navigation_contract.php
    docker compose exec -T app php tests/test_escalamientos_hierarchy.php
    docker compose exec -T app php tests/test_escalamientos_read_service.php
    docker compose exec -T app php tests/test_api_escalamientos_contract.php
    docker compose exec -T app php tests/test_lps_escalation_target.php
    docker compose exec -T app php tests/test_lps_escalation_thread_contract.php
    docker compose exec -T app php tests/test_lps_actor_eligibility.php
    docker compose exec -T app php tests/test_lps_escalation_actions_contract.php
    docker compose exec -T app php tests/test_escalamientos_no_write_invariants.php
    docker compose exec -T app php tests/test_csrf_lps_api.php

Read each exit code in its own command. Expected: all PASS.

- [ ] **Step 2: Run frontend focused then full**

    cd frontend
    npm test -- --run src/modules/escalamientos
    npm run typecheck
    npm test
    npm run build

Expected: focused/full/typecheck/build PASS.

- [ ] **Step 3: Run static boundary audits**

Confirm:
- fetch only in `cliente.ts`;
- no role table in S25;
- no notification endpoint/poller;
- no caller added to autoescalation;
- no write verb in GET path;
- no literal colors/local tokens;
- no jQuery/Bootstrap/Handsontable import;
- no real personal data in fixtures/evidence.

- [ ] **Step 4: Audit forbidden diff**

    git diff --name-only -- database src/Security/DataScope docs/security admin
    git diff --check

Expected: no RLS/database/`admin/` path and no whitespace error. A legitimate pre-existing user diff
is recorded, never reverted or attributed to S25.

- [ ] **Step 5: Run proportional shared regressions**

Run T01/T02 route/client/drawer suites and relevant design-system contracts. Do not run any test
whose setup writes DB.

- [ ] **Step 6: Record exact SHA**

    git rev-parse HEAD
    git status --short

No completion claim yet; browser evidence remains.

**Vertical checkpoint:** exact candidate passes all safe contracts with zero forbidden diff/write.

## Task 12: Verify intercepted browser behavior and prepare closure

**Files:**
- Create/complete browser fixture/spec/a11y files.
- Do not commit visual candidates without approval.
- Update closure records only after exact-SHA evidence.

**Goal:** prove observable parity and recovery without login false positives or real mutation.

- [ ] **Step 1: Run functional browser matrix**

    npx playwright test tests/browser/escalamientos-react.spec.mjs --workers=1 --reporter=line

The spec must intercept all operational APIs, assert authenticated project/URL/h1/board before each
scenario, reject unexpected methods and cover allowed/read-only/profile-required, comment, copy,
register, close, stale, empty, refresh, back and project invalidation.

- [ ] **Step 2: Run accessibility/reflow matrix**

    npx playwright test tests/browser/escalamientos-react.a11y.mjs --workers=1 --reporter=line

Cover dark/light × 390/480/768/1180/1440, 320/200% zoom, keyboard, focus, inert, reduced motion,
overflow and Axe serious/critical zero.

- [ ] **Step 3: Verify console/network**

No page error, console error, failed/unexpected request, unhandled rejection or real non-GET.
A closed dev door is reported explicitly; it cannot turn login into a green board test.

- [ ] **Step 4: Exercise rollback map safely**

Test route-map rollback with the PHP route contract/fake host only. Do not change data or publish.

- [ ] **Step 5: Re-run exact post-integration gates**

If main was integrated, repeat Tasks 11 and 12 on the new SHA. Record SHA and separate exit codes.
Do not chain verification and publication.

- [ ] **Step 6: Future closure/publication**

In the authorized implementation session:
- update `Cierre`/ledger with exact evidence;
- make remaining atomic commit;
- fetch and integrate origin/main in the feature branch;
- reverify exact SHA;
- push branch and open PR to main;
- require CI green before merge;
- confirm origin/main contains the verified commit;
- deploy only with separate explicit authorization.

No commit, push, PR or publication occurs in this documentation session.

**Vertical checkpoint:** browser evidence is real, intercepted, accessible and tied to the exact
candidate SHA.

## Acceptance Traceability

| Criterion | Owner | Primary evidence |
|---|---|---|
| S25-AC-001 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-002 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-003 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-004 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-005 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-006 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-007 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-008 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-009 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-010 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-011 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-012 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-013 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-014 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-015 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-016 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-017 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-018 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-019 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-020 | Task 1 | access, project-scope, capability and navigation-policy tests |
| S25-AC-021 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-022 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-023 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-024 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-025 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-026 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-027 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-028 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-029 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-030 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-031 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-032 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-033 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-034 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-035 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-036 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-037 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-038 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-039 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-040 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-041 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-042 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-043 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-044 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-045 | Task 2 | scoped reader, hierarchy, normalization, ordering and no-write tests |
| S25-AC-046 | Task 3 | new GET endpoint and PHP contract tests |
| S25-AC-047 | Task 3 | new GET endpoint and PHP contract tests |
| S25-AC-048 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-049 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-050 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-051 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-052 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-053 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-054 | Task 7 | strict Zod, gateway, cancellation, cache and refetch tests |
| S25-AC-055 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-056 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-057 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-058 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-059 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-060 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-061 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-062 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-063 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-064 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-065 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-066 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-067 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-068 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-069 | Task 4 | alert-target, thread, aliases, mentions and CSRF tests |
| S25-AC-070 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-071 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-072 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-073 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-074 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-075 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-076 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-077 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-078 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-079 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-080 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-081 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-082 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-083 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-084 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-085 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-086 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-087 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-088 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-089 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-090 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-091 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-092 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-093 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-094 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-095 | Task 6 | comment, SOS/register, copy and close behavior tests |
| S25-AC-096 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-097 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-098 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-099 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-100 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-101 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-102 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-103 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-104 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-105 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-106 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-107 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-108 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-109 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-110 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-111 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-112 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-113 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-114 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-115 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-116 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-117 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-118 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-119 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-120 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-121 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-122 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-123 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-124 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-125 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-126 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-127 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-128 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-129 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-130 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-131 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-132 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-133 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-134 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-135 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-136 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-137 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-138 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-139 | Task 3 | new GET endpoint and PHP contract tests |
| S25-AC-140 | Task 5 | server action policy and actor-eligibility tests |
| S25-AC-141 | Task 11 | focused-to-broad invariant and regression verification |
| S25-AC-142 | Task 8 | board, product-state, responsive, theme and legend component tests |
| S25-AC-143 | Task 9 | shared drawer, deep-link, focus and accessibility integration tests |
| S25-AC-144 | Task 12 | intercepted browser preconditions and no-DML evidence |
| S25-AC-145 | Task 12 | intercepted browser preconditions and no-DML evidence |
| S25-AC-146 | Task 12 | intercepted browser preconditions and no-DML evidence |
| S25-AC-147 | Task 12 | intercepted browser preconditions and no-DML evidence |
| S25-AC-148 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-149 | Task 10 | route/sidebar/notification boundary, caller census and cut tests |
| S25-AC-150 | Task 8 | board, product-state, responsive, theme and legend component tests |

## Vertical Checkpoints

1. **Boundary:** session/project/capability/navigation/global-week policy, no alert read.
2. **Snapshot:** scoped active rows, hierarchy, normalization, counts and no side effects.
3. **HTTP:** new GET contract works without active week.
4. **Target:** alert ID resolves authoritative activity/week/module and thread.
5. **Actions:** read/edit/profile/terminal decisions are explicit and independent.
6. **Mutations:** comment/SOS/close contracts are truthful and fake-tested.
7. **Client boundary:** strict Zod/gateway/state through `cliente.ts`.
8. **Useful UI:** board/states/responsive/themes/legend without drawer.
9. **Complete UI:** T02 drawer, deep link, focus and all actions.
10. **Cut:** canonical route/sidebar/manifest, unique notification entry and VIEW-12 retirement.
11. **Safe verification:** PHP/frontend/invariants/diff pass on exact SHA.
12. **Browser/closure:** intercepted parity/a11y and repository PR/CI gate ready.

A failed checkpoint stops later tasks. Do not compensate in React for a failed backend/security
checkpoint.

## Completion Gate

S25 is complete only when all are true:

- exact worktree and branch verified;
- T01/T02/S05/S07/S08 dependencies are real and published;
- page and GET require effective weekly-view capability;
- mutations require weekly-edit capability;
- project scope is server-derived and foreign alert IDs fail closed;
- board includes all active weeks and does not depend on shell week;
- snapshot fields, sanitization, levels, order and counts pass;
- level 1 remains visibly level 1 while grouped under Director attention;
- GET works without active week and cannot write/call autoescalation;
- alert target derives activity/week/module in server;
- thread parent/escalation/mentions are target-scoped;
- actor mismatch produces PROFILE_REQUIRED only for actor-bound writes;
- register/copy remains correctly authorized and does not claim delivery/level movement;
- close requires 100 characters and stale close conflicts/refetches;
- strict Zod/PHP contracts agree and no component calls fetch;
- board/drawer/states work at all viewports, both themes, zoom and keyboard;
- focus/inert/return and Axe pass;
- browser first-state assertions prevent login false positives;
- browser traffic is intercepted and no real mutation occurs;
- shell notification entry remains unique and S25 has no poller;
- no new autoescalation caller exists;
- no RLS/schema/data/grant/user/credential/`/admin/` diff exists;
- no goldens regenerated;
- VIEW-12/exclusive CSS retire only after caller census;
- VIEW-28/aliases remain until T02;
- rollback is code/route only and tested;
- exact SHA verification and closure evidence are recorded;
- the implementation front is merged by PR with CI green before another front starts.

## Cierre

Estado inicial: pendiente de implementación. This document was written and self-reviewed in a
documentation-only session. No implementation, test mutation, commit, push, PR, publication,
deployment, RLS/schema/data change or `/admin/` work was performed by writing this plan.
