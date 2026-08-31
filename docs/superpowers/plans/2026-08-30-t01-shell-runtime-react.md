---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [arquitectura, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-t01-shell-runtime-react.md
resumen: "close the measured gaps in the existing React shell so T02, T03 and S01–S27 can rely on one server-authoritative runtime for bootstrap, session, navigation…"
---

# T01 Shell and React Runtime Implementation Plan

> **For agentic workers:** this is an implementation plan, not implementation authorization. In a
> future session explicitly authorized to execute it, use `superpowers:test-driven-development` for
> every production change and `superpowers:verification-before-completion` before either gate is
> claimed. Execute tasks in order and stop at every vertical checkpoint. The existing shell is the
> baseline to complete; do not rebuild it. Checkbox state is not closure evidence: record evidence
> in `Cierre` and git history under the repository policy.

**Goal:** close the measured gaps in the existing React shell so T02, T03 and S01–S27 can rely on
one server-authoritative runtime for bootstrap, session, navigation, project, week, themes, errors,
responsive behavior and legacy coexistence, while deferring removal of VIEW-26, VIEW-29 and
VIEW-30 until their real caller census is zero.

**Architecture:** PHP remains authoritative for session, CSRF, membership, `ProjectScope`, RBAC,
week rules and authorized destinations. `GET /api/session` is the only bootstrap and returns one
validated state plus a server-filtered navigation/week manifest. React owns presentation and
transitions through `AppShell`; it receives no client-side role matrix and invents no privileged
URL. `frontend/src/lib/api/cliente.ts` is the sole transport boundary and turns both successful and
failed JSON into typed values. `SpaRouter` keeps an explicit route map so React and PHP coexist and
rollback never changes RLS or data.

**Tech Stack:** PHP 8.3 inside Docker Compose at `/var/www/html`, React 19, TypeScript 7,
react-router-dom 7, Vite 8, Zod 4, Vitest 4, Testing Library, Playwright, native HTML/CSS and
`public/css/tokens.css`.

**Spec:** `docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. The application container must see that worktree at `/var/www/html`.
- Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or another worktree.
- Inspect `pwd`, branch, status and relevant diff before every future task. Preserve existing work;
  never clean, revert, stage or reformat adjacent paths.
- This planning session is documentation-only. Commands and commits below are instructions for a
  separately authorized implementation session; they authorize nothing now.
- Close gaps in the shell already present in `frontend/`; do not scaffold another app, session,
  sidebar, router, theme system or HTTP client.
- `/admin/` is excluded. Do not modify its routes, controllers, views, flags UI, permissions,
  assets or tests.
- Consume the existing RLS/runtime boundary. Do not modify `DataScope`, RLS, policies, schema,
  migrations, tables, views, columns, indexes, triggers, grants, users, credentials, memberships,
  role aliases, seeds, fixtures or persistent data.
- No DDL/DML is permitted as test or closure evidence. Week create/delete tests use pure PHP
  services with fakes/call logs and a fully intercepted browser. A real persistence scenario needs
  separate authorization and is not part of either T01 gate.
- Preserve `RbacCatalog`; normalize aliases only with `RbacService::normalizeRole()`. React never
  branches on raw role codes.
- `GET /api/session` remains the sole bootstrap. Do not add a parallel bootstrap endpoint or expose
  `db`, prefixes, cookies, internal tokens, temporary users or secrets.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Parse success and error bodies with Zod,
  derive TypeScript types with `z.infer`, and never insert an unexpected HTML body in the UI.
- Navigation is server-authoritative. React receives ordered groups/items/actions and computes only
  active state from the current URL. It contains no role matrix and does not construct privileged
  destinations.
- Modules S01–S27 contribute route/nav metadata only through the shared contract. T01 owns shell,
  account, project, week, sidebar, responsive drawer, global state and module outlet.
- Dark is the initial/fallback theme when preference is missing, invalid or unavailable. Light and
  dark have identical controls and behavior. Every shell style consumes `public/css/tokens.css`.
- Keep `/api/*` and `/app/assets/*` outside SPA screen routing. Never register `/` as a catch-all
  migrated prefix.
- A failed mutating request is never retried automatically. Session/project generation changes
  abort or ignore all stale work before any prior-project UI can render.
- Do not regenerate visual baselines or goldens without explicit approval.
- T01-A does not delete VIEW-26, VIEW-29 or VIEW-30. T01-R is a separate, deferred retirement gate.
- Rollback changes only the explicit screen-route map and adapters; it never changes or disables
  RLS, restores data or accepts a client-provided project/database authority.

## Delivery Gates

### T01-A — shell platform available

T01-A closes after Tasks 1–10 when bootstrap, transport, navigation, `AppShell`, account, project,
week context, session lifecycle, themes, errors, responsive/accessibility and coexistence all have
fresh focused evidence. T02, T03 and S01–S27 may then consume the platform even while the three
legacy views remain deliberately present.

### T01-R — deferred legacy retirement

T01-R is not part of T01-A and cannot start merely because React parity exists. It closes only in a
later authorized session after Task 11 proves a zero real caller census independently for VIEW-26,
VIEW-29 and VIEW-30, preserves a safe error path for residual PHP routes, exercises rollback and
passes the full post-removal gate. A nonzero or ambiguous count leaves the corresponding view in
place without blocking T01-A.

## Measured Baseline to Preserve

- `SpaRouter::RUTAS_MIGRADAS` currently contains only `/app`.
- `Rutas` already distinguishes loading, error, anonymous, no-project and ready states.
- `SessionApiController` already returns identity, active project, capabilities, one BI navigation
  entry and CSRF, but not the complete state/navigation/week manifest.
- `cliente.ts` already owns `fetch` and validates successful JSON, but discards typed error bodies.
- `NavegacionLateral.tsx` already renders the shell, but contains `ocultasPorRol` and hard-coded
  destinations.
- `Rutas` currently renders only the sidebar plus project name in ready state; there is no reusable
  `AppShell`/module outlet.
- `tema.ts` and `frontend/index.html` currently fall back to light, contrary to the approved dark
  initial contract.
- `/context/week`, `/context/clear-week`, `/session/touch` and the legacy create/delete scripts
  already exist. The typed create/delete adapters do not.
- VIEW-26, VIEW-29 and VIEW-30 still have live PHP responsibilities and must remain through T01-A.

## File Structure

### Complete — shared frontend runtime

- `frontend/index.html` — pre-paint dark/default theme bootstrap and shared assets.
- `frontend/src/App.tsx`, `frontend/src/main.tsx` — provider/root composition only.
- `frontend/src/lib/api/cliente.ts` — sole transport, typed success/error parsing and abort support.
- `frontend/src/lib/api/esquemas/sesion.ts` — refined canonical bootstrap schema/types.
- `frontend/src/lib/api/esquemas/error.ts` — typed API error envelope.
- `frontend/src/lib/api/esquemas/contexto.ts` — week/touch/logout response schemas.
- `frontend/src/shell/AppShell.tsx` — account, project, sidebar, responsive drawer and outlet.
- `frontend/src/shell/NavegacionLateral.tsx` — render server manifest; no role/destination catalogs.
- `frontend/src/shell/CuentaMenu.tsx`, `ContextoSemana.tsx`, `SesionProvider.tsx` — shared controls.
- `frontend/src/shell/rutas.tsx` — bootstrap state machine, route boundaries and module outlet.
- `frontend/src/shell/tema.ts`, `ConmutadorTema.tsx` — dark fallback and equivalent persistence.
- `frontend/src/shell/sesion/ControlActividad.ts` — one timeout/touch owner.
- `frontend/src/shell/shell.css` — shell-only styles using existing tokens.

### Complete — server contracts and adapters

- `src/Controllers/Api/SessionApiController.php` — canonical bootstrap and filtered manifest.
- `src/Controllers/Api/AuthApiController.php` — typed/idempotent logout contract.
- `src/Controllers/Core/SessionController.php` — authenticated touch contract.
- `src/Controllers/Core/ContextController.php` — typed select/clear week contracts.
- `src/Controllers/Api/WeekContextApiController.php` — create/delete-last adapters.
- `src/Services/Shell/ShellBootstrapService.php` — state/navigation/week composition.
- `src/Services/Shell/ShellNavigationService.php` — ordered server-authorized manifest.
- `src/Services/Shell/WeekContextService.php` — read/select/clear context.
- `src/Services/Shell/WeekAdministrationService.php` — extracted create/delete rules behind fakes.
- `src/Core/SpaRouter.php` — explicit coexistence/rollback route map.
- `public/index.php` — register only the two typed week adapters and preserve existing routes.

Names may adapt to an already-established shared seam discovered at execution time, but ownership,
contracts and evidence may not be weakened or duplicated.

### Create/complete — focused evidence

- PHP: `tests/test_shell_bootstrap_contract.php`, `tests/test_shell_navigation_manifest.php`,
  `tests/test_shell_week_context_contract.php`, `tests/test_shell_week_administration_contract.php`,
  `tests/test_shell_session_lifecycle.php`, existing auth/session/SPA frontier tests.
- Frontend: tests beside `cliente`, bootstrap schemas, `SesionProvider`, `AppShell`, navigation,
  week context, activity control, route boundaries and theme.
- Browser: `tests/browser/shell-runtime-react.spec.mjs` and
  `tests/browser/shell-runtime-react.a11y.mjs`, fully intercepted before navigation.
- Static/design: `tests/design-system/shell-runtime-react-tokens.test.mjs` and a caller-census test
  for the three owned views.

### Preserve through T01-A

- `views/errors/error.view.php` (VIEW-26).
- `views/partials/head_brand.php` (VIEW-29).
- `views/partials/shell_sidebar.php` (VIEW-30).
- `public/js/core/SessionTimeoutManager.js`, `public/js/modules/aia_ui/theme.js`,
  `public/js/modules/sidebar_navigation.js` and `public/js/modules/shell_week_admin.js` while any
  legacy host still calls them.
- `docs/security/rls-runtime-boundary.md`, every database path and every `/admin/` path.

## Task 1: Freeze baseline, route ownership and real callers

**Owns:** no acceptance criterion by itself; it makes every later gate reproducible.

**Files:** create focused characterization/census fixtures and tests only; modify no production
behavior.

**Vertical outcome:** the existing shell, API routes, screen-route map and callers of VIEW-26,
VIEW-29 and VIEW-30 are named before any gap is closed.

1. Verify exact worktree, branch and `/var/www/html` mount once. Record the starting SHA and all
   pre-existing changes without staging them.
2. Read the current shell/API/SPA tests before running them. Inspect candidate PHP tests for writes;
   exclude any test that mutates persistent data.
3. Add a pure route-map characterization test covering `/app`, `/api/*`, `/app/assets/*`, exact
   migrated paths, similar non-migrated paths, refresh and deep-link host behavior.
4. Add a static caller-census test that reports each direct include/render/reference to VIEW-26,
   VIEW-29 and VIEW-30. The initial expected count is measured from the code, never guessed.
5. Characterize current `cliente.ts`, bootstrap states, navigation role map and light fallback with
   focused tests that fail only on the target gaps.
6. Run focused baseline tests and record independent return codes.
7. In a future authorized session, commit only characterization artifacts atomically.

**Checkpoint:** there is a reproducible baseline and retirement census; no behavior or data changed.

## Task 2: Complete the sole client and canonical bootstrap

**Owns:** bootstrap states, canonical bootstrap transport and the sole-client boundary.

**Files:** `cliente.ts`, error/bootstrap/context schemas, `SessionApiController`, bootstrap service,
`useSesion`/`SesionProvider` and their focused tests.

**Vertical outcome:** the seven startup states and every HTTP outcome enter React through one typed
transport and one PHP bootstrap.

1. Write failing `cliente.ts` tests for JSON success, empty success, typed 401/403/404/409/422/5xx,
   malformed JSON, unexpected HTML, correlation ID, field errors, redirect, abort and network loss.
2. Write failing Zod refinement tests for all seven startup states, forbidden field combinations,
   absent project/week/navigation and extensible boolean capabilities.
3. Extend the PHP contract test for anonymous, password-change-required, authenticated/no-project,
   ready, timeout, inactive, stale/unverified session and recoverable server failure. Assert
   `Cache-Control: no-store` and absence of database/prefix/secret fields.
4. Run PHP and Vitest targets and prove RED against the measured gaps.
5. Implement the minimum typed `ApiError`/result parsing in `cliente.ts`; keep its existing
   same-origin credentials and header merge. Do not add a second wrapper.
6. Compose the complete bootstrap in PHP from session middleware, active `ProjectScope`, server
   navigation and week services. Keep `GET /api/session` as the only source.
7. Replace ad-hoc `useSesion` transitions with the seven-state provider without preserving stale
   operational UI during revalidation.
8. Run focused PHP/Vitest tests, typecheck and a source scan proving that production `fetch(` occurs
   only in `frontend/src/lib/api/cliente.ts`.
9. Future atomic commits may separate transport/schema from PHP bootstrap, but T01-A requires both.

**Checkpoint:** bootstrap and transport are typed end to end; module UI still need not exist.

## Task 3: Make navigation server-authoritative and preserve exclusions

**Owns:** server-authoritative navigation and the excluded-path/security boundary.

**Files:** shell navigation service/bootstrap presenter, session schema, navigation component/tests
and static exclusion tests.

**Vertical outcome:** React renders only server-issued destinations and contains no authorization
matrix, while `/admin/`, data and security boundaries remain untouched.

1. Write failing pure PHP tests for ordered groups/items/actions across an allowed and denied role,
   active project area and relevant flags. Assert denied items and privileged hrefs are absent.
2. Write failing component/source tests proving `ocultasPorRol`, raw role branches, route catalogs
   and privileged URL construction are absent from React.
3. Implement one server manifest using `RbacManager`, normalized roles, active membership, project
   area and existing feature gates. Hiding remains separate from route authorization.
4. Make `NavegacionLateral` render label/group/icon/href supplied by the manifest and compute only
   exact/pattern active state declared by route metadata.
5. Add a diff/static gate for `/admin/`, RLS/DataScope, database, grants, users, credentials, seeds
   and persistent fixtures.
6. Run focused PHP/component tests and the forbidden-authority source scan.

**Checkpoint:** navigation is server-authoritative; no excluded subsystem changed.

## Task 4: Complete AppShell, account, project, sidebar and module outlet

**Owns:** the reusable shell, account, project, week, sidebar and outlet behavior.

**Files:** `AppShell`, account/week/sidebar composition, route outlet, shell CSS and focused tests.

**Vertical outcome:** ready state renders one reusable shell with account, project, week, navigation
and module outlet at 390×844, 768×1024, 1180×820 and 1440×900.

1. Write component tests for one `nav`, one `main`, skip link, one active destination, account menu,
   current project/week, outlet content and absence of duplicate shells.
2. Write responsive state tests for mobile/tablet drawer and desktop persistent/collapsible sidebar,
   including close-on-route, Escape, backdrop, body scroll lock and focus return.
3. Implement `AppShell` around the existing root instead of scaffolding a new app. Modules enter only
   through `Outlet`; non-migrated hrefs continue as full-page PHP navigation.
4. Implement account actions from server-authorized data: change project, theme and logout. Do not
   add a destructive GET.
5. Use only existing tokens and shell component classes; add a shared semantic token only after a
   failing design contract proves it absent.
6. Run component tests, typecheck and the token/source contract.

**Checkpoint:** all downstream surfaces have one shell/outlet contract but no surface is migrated.

## Task 5: Complete week context and create/delete adapters with fakes

**Owns:** week selection, clearing and administration parity.

**Files:** week services/controllers/routes, bootstrap week schema, `ContextoSemana`, focused PHP and
component/browser tests.

**Vertical outcome:** select, clear, create and delete-last preserve legacy rules, RBAC and project
scope without accepting `db` or running DML in evidence.

1. Inspect `ContextController`, `nueva_semana.php`, `eliminar_semana.php`, their callers and every
   candidate test. Document create/delete invariants before extraction.
2. Write pure service tests with fake repositories/call logs for CIC pending, previous-week
   confirmation/admin exception, first-week master program, seven-day range,
   Construction/Pre-Construction differences, normalization/carryover ordering, delete-last and
   cascaded operation plan. Assert no repository method runs after a denial.
3. Write HTTP contract tests for CSRF, capability allowed/denied, project membership, valid/invalid
   week, no client `db`/prefix/project authority, typed 400/403/404/409/422/5xx and updated context.
4. Run the new pure tests and prove RED. Do not run any existing suite that seeds or deletes rows.
5. Extract legacy rules behind `WeekAdministrationService` and add the two typed adapters. Keep the
   legacy scripts as compatibility callers; do not duplicate rules in React.
6. Make select/clear return canonical refreshed week/bootstrap data and verify the selected week
   belongs to the active project.
7. Render week number/range and only server-issued actions. Create/delete refetch canonical state;
   no optimistic local list mutation and no automatic retry.
8. Browser tests install interception before navigation and fulfill every week mutation with
   synthetic responses. Assert expected request bodies, zero `db`, zero unexpected requests and
   zero real DML.
9. Run pure PHP, Vitest and intercepted browser tests.

**Checkpoint:** week behavior is contract-complete without persistent test writes.

## Task 6: Own timeout, touch, logout and project-generation invalidation

**Owns:** logout, timeout, touch and project-generation invalidation.

**Files:** session lifecycle controller/contracts, activity owner, session provider/cache generation
and tests.

**Vertical outcome:** one shell controller owns human activity, timeout and logout; session/project
changes invalidate every prior operational result.

1. Write fake-timer tests for the 3600-second contract, warning/touch scheduling, one listener set,
   `X-AIA-Idle-Refresh: 0` background reads and no module-owned timer.
2. Write lifecycle tests for idempotent CSRF logout, valid/invalid touch, 401, timeout, inactive,
   stale session and project membership loss.
3. Write generation tests: switching project, logout or invalid project aborts in-flight requests,
   clears week/navigation/module cache and ignores late responses from the prior generation.
4. Run focused tests and prove RED.
5. Implement one `ControlActividad` integrated with `SesionProvider`; use the typed common client for
   touch/logout and expose generation/abort signals to module clients.
6. Retain `SessionTimeoutManager.js` only for legacy hosts during coexistence; prevent it from
   loading inside the React host.
7. Run PHP/Vitest tests and intercepted timeout/logout/project-switch browser scenarios.

**Checkpoint:** stale session or project state cannot survive a lifecycle transition.

## Task 7: Make dark initial and light fully equivalent without flash

**Owns:** dark initial/fallback behavior and complete light parity.

**Files:** `frontend/index.html`, theme module/toggle/tests and token contract.

**Vertical outcome:** missing, invalid or unavailable preference paints dark before CSS; valid dark
or light persists and exposes identical controls.

1. Replace tests that characterize the current light fallback with failing target tests for missing,
   `dark`, `light`, corrupt and blocked storage.
2. Add a static bootstrap-order test proving the minimal inline theme script runs before stylesheet
   links and never imports the legacy theme script.
3. Implement dark fallback in both pre-paint bootstrap and `leerTemaGuardado`; keep only `dark` and
   `light` in storage and synchronize accessible toggle name/state.
4. Add component/browser assertions that every shell control exists and remains usable in both
   themes, with focus visible and no theme flash.
5. Run theme unit tests, token/source checks and intercepted two-theme browser checks. Do not update
   visual baselines.

**Checkpoint:** theme selection is deterministic before first paint and functionally equivalent.

## Task 8: Complete typed errors, responsive behavior and accessibility

**Owns:** typed error recovery, responsive integrity and accessibility.

**Files:** route error boundary/global states, shell components/CSS, accessibility and browser tests.

**Vertical outcome:** 401/403/404/409/422/5xx, offline and invalid contracts recover safely, and the
shell has no page overflow, focus trap defect or unnamed control.

1. Write route-state tests for bootstrap network recovery, 401 session reset, 403 exit, 404 landing,
   contextual 409/422, 5xx correlation and render-error boundary. Assert no raw HTML/body leak.
2. Write keyboard tests for skip link, account/sidebar/week controls, drawer trap/Escape/return,
   single `aria-current`, document title and live announcements.
3. Add browser fixtures for all error states before navigation. Fail unexpected requests,
   non-intercepted navigation, console/page errors and horizontal page overflow.
4. Implement the minimum shared error/feedback components and responsive fixes in the existing shell.
5. Validate 390×844, 768×1024, 1180×820 and 1440×900, 200% zoom, 44px targets, reduced motion and
   axe serious/critical zero in dark and light.
6. Run focused component, design-contract and intercepted browser suites.

**Checkpoint:** global failures and shell accessibility are safe before any route cut expands.

## Task 9: Prove coexistence, deep links and route-map rollback

**Owns:** coexistence, deep links and route-map-only rollback.

**Files:** `SpaRouter`, route-map tests, HTTP frontier tests and intercepted coexistence fixtures.

**Vertical outcome:** React deep links return the SPA host, legacy screens keep PHP behavior and a
route-map-only rollback is executable without touching RLS or data.

1. Extend pure/HTTP tests for exact migrated paths, nested routes, sibling false positives,
   `/api/*`, `/app/assets/*`, PHP routes, refresh and deep links.
2. Add a rollback fixture that removes one sample migrated screen from the explicit map, proves it
   returns to its PHP adapter, restores the map and proves React again. It must not edit environment,
   security or data state.
3. Add browser navigation from React to one non-migrated PHP route and back; verify same-origin
   session continuity and full-page navigation where expected.
4. Implement exact/prefix semantics without a global `/` catch-all and preserve API/asset exclusions.
5. Run SPA frontier, HTTP and intercepted browser tests. Inspect the diff for RLS/DataScope/data
   paths after the rollback exercise.

**Checkpoint:** coexistence and rollback are proven independently of legacy-view retirement.

## Task 10: Close T01-A and publish the platform contract

**Owns:** T01-A closure evidence for every platform criterion; it does not claim deferred
retirement.

**Files:** implementation closure record only after all focused gates are green.

**Vertical outcome:** T02, T03 and S01–S27 can consume the shell platform while VIEW-26, VIEW-29 and
VIEW-30 remain safely in place.

1. Confirm exact worktree, branch, mounted source, starting/current SHA and intended diff.
2. Run focused PHP contracts, frontend unit/component tests, typecheck/build, route frontier,
   design-token checks and intercepted browser/a11y matrix. Read each return code independently.
3. Re-run source/diff invariants: sole `fetch`, no React role map/privileged URL catalog, token-only
   styles, no `/admin/`, RLS/DataScope, database, grants, users, credentials, seeds or data changes.
4. Record mutation count zero for test/browser evidence, unexpected requests zero, console/page
   errors zero and visual baselines unchanged.
5. Record the current nonzero/zero caller count for each owned view and state explicitly that T01-A
   does not authorize deletion.
6. Exercise one route-map rollback and restore it before final verification.
7. Request code review and follow repository PR/CI publication policy only when the user explicitly
   authorizes implementation closure. Green tests alone authorize no commit, push, PR or deploy.

**T01-A checkpoint:** platform available; retirement remains a separate deferred gate.

## Task 11: Retire VIEW-26, VIEW-29 and VIEW-30 only at T01-R

**Owns:** the deferred zero-caller/replacement/retirement proof.

**Files:** the three owned views and their legacy assets only if each independent census is zero;
caller tests, safe residual error adapter and closure record.

**Vertical outcome:** each legacy view is removed only after its final real consumer is gone, with
equivalent React ownership and a safe rollback record.

1. Start only in a later explicitly authorized session. Re-run the static and runtime caller census
   against the then-current integrated branch; do not reuse T01-A counts.
2. Gate each view separately:
   - VIEW-26: SPA errors are equivalent and every residual PHP route has another safe error output.
   - VIEW-29: every principal PHP host has stopped including it and the React host serves equivalent
     favicon/touch assets.
   - VIEW-30: every principal surface uses `AppShell`; account/week/sidebar behavior and typed week
     adapters are complete; no inline legacy shell caller remains.
3. If any count is nonzero or ambiguous, keep that view and stop its retirement without reopening
   T01-A. Never delete by expected future ownership.
4. For a zero-count view, first make the census test fail when a synthetic caller is introduced;
   then remove only the proven-dead file/includes/assets and run the focused gate.
5. Exercise route rollback and safe PHP error behavior after removal. Restore the React route map.
6. Run the complete post-removal PHP/frontend/browser/a11y/source/diff gate and compare all results
   on the exact SHA.
7. Record each zero census, replacement owner, deleted path, rollback and remaining legacy assets.
8. Follow repository PR/CI policy only with explicit user authorization. T01-R never authorizes
   deploy or data changes.

**T01-R checkpoint:** all three independent zero-caller gates and post-removal verification pass.

## Acceptance Traceability

Each T01 acceptance ID appears exactly once in this table.

| Acceptance ID | Owning task | Evidence contract |
|---|---:|---|
| T01-AC-01 | Task 2 | seven-state bootstrap PHP/Zod/provider tests |
| T01-AC-02 | Task 2 | sole `/api/session` PHP and Zod contract |
| T01-AC-03 | Task 3 | server navigation and forbidden React role-map/URL tests |
| T01-AC-04 | Task 4 | AppShell/account/project/week/outlet viewport tests |
| T01-AC-05 | Task 7 | pre-paint dark fallback and two-theme parity tests |
| T01-AC-06 | Task 6 | logout/timeout/invalid-project generation tests |
| T01-AC-07 | Task 5 | fake-backed week select/create/delete policy and intercepted browser tests |
| T01-AC-08 | Task 2 | production source scan proving sole `cliente.ts` fetch |
| T01-AC-09 | Task 8 | typed HTTP/contract/render recovery tests |
| T01-AC-10 | Task 8 | overflow/focus/name/keyboard/zoom/axe tests |
| T01-AC-11 | Task 9 | legacy coexistence and deep-link HTTP/browser tests |
| T01-AC-12 | Task 9 | route-map rollback test plus RLS/data diff invariant |
| T01-AC-13 | Task 11 | independent zero-caller/replacement/post-removal gates for three views |
| T01-AC-14 | Task 3 | excluded-path and security/data diff audit |

## Vertical Checkpoints

1. Baseline/routes/callers frozen.
2. Typed transport and sole bootstrap complete.
3. Server-authoritative navigation and exclusions complete.
4. Shared AppShell/outlet complete.
5. Week context/adapters proven with fakes and interception, no DML evidence.
6. Session lifecycle/generation invalidation complete.
7. Dark initial and light parity complete.
8. Errors/responsive/accessibility complete.
9. Coexistence/deep links/rollback complete.
10. T01-A platform evidence complete; three views preserved.
11. T01-R zero-caller retirement complete in a later authorized session.

A failed checkpoint blocks later tasks. T01-A and T01-R are separate claims.

## Completion Gates

### T01-A is complete only when

- every T01-A platform criterion maps to fresh evidence;
- the bootstrap and error schemas agree between PHP and Zod;
- server navigation contains no unauthorized entry and React contains no role matrix;
- AppShell/account/project/week/outlet work at all four viewports;
- week policy tests use fakes and browser interception with zero real DML;
- timeout/logout/project changes invalidate stale generations;
- dark fallback and light parity have no first-paint flash;
- HTTP/error/a11y/overflow gates pass;
- coexistence/deep links and route-map rollback pass;
- only `cliente.ts` calls `fetch`;
- VIEW-26/29/30 remain present unless T01-R has independently closed;
- no `/admin/`, RLS/DataScope, database, grant, user, credential, seed, fixture or data change exists;
- exact diff and test return codes are recorded;
- no commit, push, PR, publication or deploy occurs without separate authorization.

### T01-R is complete only when

- T01-A is already complete;
- the deferred retirement criterion maps to fresh evidence;
- VIEW-26, VIEW-29 and VIEW-30 each have a real zero caller census;
- each has an equivalent current owner and safe residual behavior;
- removal, rollback and full post-removal verification pass on the same exact SHA;
- no RLS/data/admin change or deploy is implied by retirement.

## Self-Review Record

- The plan treats the existing React shell as the baseline and closes measured gaps; it does not
  scaffold a replacement shell.
- Eleven ordered tasks cover the requested baseline, transport/bootstrap, navigation, AppShell,
  week context, session lifecycle, themes, errors/accessibility, coexistence, T01-A and T01-R.
- T01-A and T01-R are independent gates; no legacy view deletion is required for platform use.
- The traceability table contains fourteen unique acceptance rows, no range shorthand and no extra
  criterion.
- Every production HTTP call remains behind `cliente.ts`; navigation and week actions remain
  server-authoritative.
- Week administration evidence uses pure services, fakes, call logs and browser interception; it
  requires no DML, schema, RLS or persistent fixture change.
- Dark is initial/fallback, light is complete, and all shell styles consume the shared token file.
- Rollback is route-map-only and never relaxes authorization or changes data.
- `/admin/`, DataScope/RLS, database assets, grants, users and credentials are explicitly excluded.
- The plan contains no implementation claim, open product decision or placeholder.

## Cierre

Pending future implementation. This document closes planning only; it records no implementation,
test execution, commit, publication or retirement claim.
