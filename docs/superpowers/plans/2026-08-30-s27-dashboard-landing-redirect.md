# S27 Dashboard Landing Redirect Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` only in an
> explicitly authorized implementation session. Use `superpowers:test-driven-development` before
> each production change and `superpowers:verification-before-completion` before any completion
> claim. Apply the repository branch/PR/publication gate in order. Checkbox syntax is an execution
> prompt; `Cierre` and git history are the evidence.

**Goal:** close S27 by preserving `GET /dashboard` as a server-side, bodyless `302` transition that
uses one authorized project/role/area/week decision, fixes the Pre‑Construction wiring mismatch,
accepts only four same-origin destinations, persists a validated week and is classified as
non-rendering rather than being replaced by a fabricated React screen.

**Architecture:** `SessionMiddleware` authenticates and binds `ProjectScope`. A small
`DashboardLandingAction` validates session/scope coherence and delegates to the existing
`ProjectLandingService` through a narrow interface. The service keeps scoped reads but delegates
the selection matrix to a pure `ProjectLandingDecision` and canonicalizes roles with
`RbacService`. The controller only applies the returned week and emits `302`, a literal
`Location` and `no-store` with no body. The Design System coverage gate recognizes this one exact
non-rendering route. No frontend or API code is added.

**Tech Stack:** PHP 8.3 in Docker Compose, existing router/session/DataScope/RBAC services, PHPUnit,
standalone PHP contract tests, Node `node:test` for Design System coverage, PHPStan and the current
repository test harness.

**Spec:** `docs/superpowers/specs/2026-08-30-s27-dashboard-landing-redirect-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or a
  different worktree.
- Inspect status and the relevant diff before every task. Preserve all existing work; never clean,
  revert, stage or reformat adjacent paths.
- This session is documentation-only. The tasks below are future execution instructions and do not
  authorize implementation, commits, push, PR, publication or deploy now.
- Implement S27 only after T01, S04, S05, S06, S08, S11 and S26 have closed according to their
  `Cierre` sections and the repository publication gate.
- `/admin/` is excluded. Do not edit anything below `admin/` or any `/admin/` route.
- Do not add `/dashboard` to `SpaRouter::RUTAS_MIGRADAS`, the React route tree, sidebar,
  breadcrumbs, command palette or mobile navigation.
- Do not create frontend files, API routes, Zod schemas, CSS, tokens, manifests, screenshots or
  goldens.
- Preserve the four destination paths and the existing landing/week/CNC rules.
- Normalize roles only through `RbacService::normalizeRole()`; do not add roles, aliases,
  capabilities or permission fallbacks.
- Do not modify `ProjectScope`, `ProjectScopeResolver`, `ProjectSqlGuard`, DataScope semantics or
  the documented RLS boundary.
- Do not modify schema, migrations, tables, columns, indexes, triggers, views, grants, users,
  credentials, memberships, seeds, fixtures or persistent data.
- No DDL or DML in product changes, tests, manual verification or rollback.
- Do not run `tests/test_api_projects_contract.php`: its successful selection path records activity.
- Every operational read retained in `ProjectLandingService` must continue through
  `queryWithProject` with the authorized project id.
- Query params, request bodies and headers never select role, project, area, week or redirect.
- Unknown destinations fail to `/proyectos` before `Location` is emitted.
- A failed action never changes `$_SESSION['semana']`.
- The response remains `302` with empty body and `Cache-Control` containing `no-store`.
- Keep `/reportes/{tipo}` and its current coverage debt untouched.
- Keep all destination views/assets during S27; their owners retire them.
- Future commits are atomic per task. This documentation session makes no commits.

## Dependency Gate

Before Task 1 in the future implementation session:

1. Read `Cierre` in T01, S04, S05, S06, S08, S11 and S26. Unchecked boxes are not evidence.
2. Verify S04 still makes `ProjectLandingService` the authority and returns its exact route.
3. Verify the four destination routes remain canonical, whether each currently renders legacy or
   React.
4. Verify S26 still uses `tests/design-system/coverage-closure.test.mjs` and
   `docs/design-system/coverage-debt.json` as the coverage authority.
5. Check the environment once:

   ```bash
   pwd
   git branch --show-current
   git status --short
   docker compose config --services
   docker compose ps
   ```

   Expected: exact worktree/branch; `app`, `db` and `adminer` are declared; the app mount points to
   this worktree.
6. Record the starting SHA and every pre-existing changed path.
7. Run the read-only baseline:

   ```bash
   docker compose exec -T app php tests/test_spa_frontera.php
   node --test tests/design-system/coverage-closure.test.mjs
   curl --silent --show-error --output /dev/null --dump-header - http://localhost:8081/dashboard
   ```

   Expected today: SPA frontier PASS; coverage gate PASS with `/dashboard` still tolerated as debt;
   the unauthenticated request is `302` to `/login`.
8. Inspect every selected regression test for write verbs, fixtures and cleanup helpers. Exclude any
   test that selects a real project or calls `logActivity`.
9. Confirm `git diff -- admin` is empty and keep it as an end-of-task invariant.
10. If a dependency changed the four destinations or landing semantics, stop and amend S27 instead
    of adapting tests to a new behavior.

## File Structure

### Create — pure domain and action

- `src/Services/Landing/ProjectLandingResolver.php` — narrow `resolve(db, role, area)` interface.
- `src/Services/Landing/ProjectLandingResult.php` — immutable typed result with compatible array
  projection.
- `src/Services/Landing/ProjectLandingDecision.php` — pure area/week/pending/role matrix.
- `src/Services/Landing/DashboardLandingResponse.php` — typed redirect plus optional week mutation.
- `src/Services/Landing/DashboardLandingAction.php` — scope/context validation and destination
  allowlist.

### Create — focused tests

- `tests/unit/ProjectLandingDecisionTest.php`.
- `tests/unit/ProjectLandingServiceContractTest.php`.
- `tests/unit/DashboardLandingActionTest.php`.
- `tests/test_dashboard_landing_redirect_contract.php`.

### Modify

- `src/Services/ProjectLandingService.php` — implement resolver, inject pure decision/RBAC and keep
  scoped reads.
- `src/Controllers/Core/DashboardController.php` — call the action and emit bodyless no-store 302.
- `tests/test_spa_frontera.php` — retain and make the exact non-SPA contract explicit.
- `tests/design-system/coverage-closure.test.mjs` — classify `/dashboard` as one exact non-rendering
  transition.
- `docs/design-system/coverage-debt.json` — remove only `/dashboard` and update its measurement.
- `docs/superpowers/plans/2026-08-30-s27-dashboard-landing-redirect.md` — fill `Cierre` only after
  execution evidence exists.

### Explicitly unchanged

- `frontend/`, `public/app/` and `public/css/tokens.css`.
- `public/index.php` route registration unless audit finds it no longer exact.
- `src/Services/ProjectAccessService.php` public behavior.
- `src/Security/DataScope/` and `docs/security/rls-runtime-boundary.md`.
- all destination views/assets.
- `admin/`.

## Task 1: Freeze the route and non-rendering boundary

**Owns:** AC 001–020 and AC 126.

**Files:**
- Create: `tests/test_dashboard_landing_redirect_contract.php`
- Modify: `tests/test_spa_frontera.php`
- Inspect only: `public/index.php`, `src/Core/SpaRouter.php`,
  `src/Controllers/Core/DashboardController.php`

**Step 1: Write characterization assertions**

The focused contract must prove:

- exactly one `GET /dashboard` registration targets `DashboardController::index`;
- no write-method registration exists for this path;
- `SpaRouter::sirveLaSpa('/dashboard')` is false;
- the index action has no `require`/`include` and no React index read;
- no `/api/dashboard` route exists;
- `/dashboard/escalamientos` is a different S25 route and is not changed.

Do not use a DB or session fixture.

**Step 2: Run the characterization**

```bash
docker compose exec -T app php tests/test_dashboard_landing_redirect_contract.php
docker compose exec -T app php tests/test_spa_frontera.php
```

Expected: PASS against the baseline. If it fails, reconcile the spec with code before continuing.

**Step 3: Record the unauthenticated HTTP shape**

```bash
curl --silent --show-error --output /dev/null --dump-header - http://localhost:8081/dashboard
```

Expected: `302` and `Location: /login`. Do not follow redirects and do not create a session.

**Step 4: Commit only the characterization tests**

```bash
git add tests/test_dashboard_landing_redirect_contract.php tests/test_spa_frontera.php
git commit -m "test: caracterizar landing redirect"
```

## Task 2: Extract the pure landing decision

**Owns:** AC 055–090 and AC 116–119.

**Files:**
- Create: `src/Services/Landing/ProjectLandingResult.php`
- Create: `src/Services/Landing/ProjectLandingDecision.php`
- Create: `tests/unit/ProjectLandingDecisionTest.php`

**Step 1: Write the failing matrix test**

Build synthetic cases for:

- Pre‑Construcción without invoking the pending callback;
- zero/negative week filtering and confirmed metadata;
- no active weeks;
- highest open, highest pending, pending greater than open and exact tie;
- no open/pending fallback to max active;
- `cal-sin-calificar` and required CNC;
- both CNC blanks independently;
- invalid numerics, non-positive commitment and the `0.0001` boundary;
- G/S/SG, C and every remaining canonical role;
- exact `module` and `reason` values;
- complete compatible result shape.

The test supplies an in-memory pending map. It must not instantiate `Database`.

**Step 2: Prove it fails for the missing classes**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingDecisionTest.php
```

Expected: FAIL because the domain classes do not exist.

**Step 3: Implement the smallest pure model**

`ProjectLandingDecision` accepts canonical role, exact area, normalized week metadata and a pending
predicate. It performs no I/O, reads no globals and emits `ProjectLandingResult`. Keep destination
constants in one closed location. `ProjectLandingResult::toArray()` returns the seven legacy keys.

Do not move SQL, session handling or headers into the pure class.

**Step 4: Re-run the focused test**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingDecisionTest.php
```

Expected: PASS with all branches named.

**Step 5: Commit the pure domain**

```bash
git add src/Services/Landing/ProjectLandingResult.php src/Services/Landing/ProjectLandingDecision.php tests/unit/ProjectLandingDecisionTest.php
git commit -m "refactor: extraer decision de landing"
```

## Task 3: Wire canonical roles and preserve the service contract

**Owns:** AC 035–040 and AC 091–096.

**Files:**
- Create: `src/Services/Landing/ProjectLandingResolver.php`
- Create: `tests/unit/ProjectLandingServiceContractTest.php`
- Modify: `src/Services/ProjectLandingService.php`
- Inspect only: `src/Security/RbacService.php`, `src/Services/ProjectAccessService.php`,
  `src/Controllers/Programacion/ProgramaGeneralController.php`,
  `src/Controllers/Programacion/ProgramacionSemanalController.php`

**Step 1: Write failing compatibility tests**

With injected fakes/spies, assert:

- `ProjectLandingService` implements `ProjectLandingResolver`;
- every role passes through `RbacService::normalizeRole()`;
- P, U, textual aliases, blank and unknown roles have canonical outcomes;
- Pre‑Construcción performs zero week reads;
- invalid prefix fallback remains compatible for non-dashboard callers;
- `resolve()` returns the seven legacy keys;
- `sanitizeWeek()` uses the same preferred-week decision;
- metadata/pending failures keep current fail-soft behavior;
- all retained reads are through `queryWithProject`.

The fake DB must throw on any mutating method. No table is created.

**Step 2: Run and observe red**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingServiceContractTest.php
```

Expected: FAIL because the interface/injection/decision delegation is absent.

**Step 3: Refactor the service without changing callers**

- Add optional constructor collaborators with production defaults.
- Implement `ProjectLandingResolver`.
- Replace `normalizeRole()` with the injected `RbacService` authority.
- Keep `getWeekMetadata()` and pending reads scoped.
- Delegate route/week/reason choice to `ProjectLandingDecision`.
- Project the result back to the exact existing array.
- Reuse the same pure preference in `sanitizeWeek()`.
- Do not edit `ProjectAccessService` unless compilation proves a type-only adaptation is required;
  its call already passes area.

**Step 4: Run focused tests and syntax**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingDecisionTest.php tests/unit/ProjectLandingServiceContractTest.php
docker compose exec -T app php -l src/Services/ProjectLandingService.php
docker compose exec -T app php -l src/Services/Landing/ProjectLandingResolver.php
```

Expected: PASS and “No syntax errors detected”.

**Step 5: Commit the service refactor**

```bash
git add src/Services/ProjectLandingService.php src/Services/Landing/ProjectLandingResolver.php tests/unit/ProjectLandingServiceContractTest.php
git commit -m "refactor: unificar autoridad de landing"
```

## Task 4: Add the guarded dashboard landing action

**Owns:** AC 021–034, AC 041–054 and AC 120–123.

**Files:**
- Create: `src/Services/Landing/DashboardLandingResponse.php`
- Create: `src/Services/Landing/DashboardLandingAction.php`
- Create: `tests/unit/DashboardLandingActionTest.php`
- Inspect only: `src/Security/DataScope/ProjectScope.php`, `src/Core/Database.php`,
  `src/Core/TableResolver.php` or its actual defining path

**Step 1: Write failing action tests**

Use:

- real in-memory `ProjectScope` values;
- fake `ProjectLandingResolver` recording calls;
- fake prefix-to-project lookup;
- plain session arrays.

Cover missing user/scope/project id, malformed/foreign prefix, missing and invalid area, canonical role
from scope, ignored request-like fields, four accepted destinations, hostile destinations, negative
or malformed week, resolver exceptions and no-call guards.

Assert that the response models a week mutation only after a valid result. Do not mutate the global
`$_SESSION` in this unit test.

**Step 2: Prove red**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/DashboardLandingActionTest.php
```

Expected: FAIL because action/response do not exist.

**Step 3: Implement the action**

- Accept the resolver and prefix-project lookup as constructor collaborators.
- Accept session snapshot plus current `ProjectScope`.
- Return `/login` defensively when identity is absent.
- Return `/proyectos` without resolver invocation for invalid project context.
- Default only a missing area to `Construccion`; reject a present unknown value.
- Use `scope->role()` and pass exact area.
- Validate the service result against the four literal routes.
- Coerce only a true integer/numeric integer week greater or equal to zero.
- Never concatenate or echo request values.
- Return an immutable `DashboardLandingResponse`.

**Step 4: Run the action and decision tests**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/DashboardLandingActionTest.php tests/unit/ProjectLandingDecisionTest.php
```

Expected: PASS.

**Step 5: Commit the action**

```bash
git add src/Services/Landing/DashboardLandingResponse.php src/Services/Landing/DashboardLandingAction.php tests/unit/DashboardLandingActionTest.php
git commit -m "feat: proteger transicion de dashboard"
```

## Task 5: Adapt the controller to a bodyless no-store 302

**Owns:** AC 105–109 and AC 124–125.

**Files:**
- Modify: `src/Controllers/Core/DashboardController.php`
- Modify: `tests/test_dashboard_landing_redirect_contract.php`
- Inspect only: `src/Core/SessionMiddleware.php`

**Step 1: Extend the contract so it fails**

Require the index action to:

- obtain only the current `ProjectScope` from the request DataScope;
- delegate the decision to `DashboardLandingAction`;
- write `$_SESSION['semana']` only when the response carries a valid week;
- set `302` explicitly;
- set `Cache-Control` with `no-store`;
- set the already validated `Location`;
- emit no body and exit.

Add a child-process or injectable-emitter test if needed; never fake success by source regex alone
when the response object can be exercised.

**Step 2: Run and observe red**

```bash
docker compose exec -T app php tests/test_dashboard_landing_redirect_contract.php
```

Expected: FAIL on the new action/no-store contract.

**Step 3: Make the smallest controller change**

Add optional constructor injection for tests while preserving the router's zero-argument
construction. Modify only `index()`; `escalamientos()` belongs to S25 and must remain byte-for-byte
unless a namespace import is mechanically required.

Do not render or call React. Do not catch the middleware's inactive/timeout redirects.

**Step 4: Run focused controller tests and HTTP smoke**

```bash
docker compose exec -T app php tests/test_dashboard_landing_redirect_contract.php
curl --silent --show-error --output /dev/null --dump-header - http://localhost:8081/dashboard
```

Expected: contract PASS; unauthenticated HTTP remains `302 /login`. The success shape is covered by
the injected action/response contract without opening a real project.

**Step 5: Commit the adapter**

```bash
git add src/Controllers/Core/DashboardController.php tests/test_dashboard_landing_redirect_contract.php
git commit -m "feat: emitir landing redirect seguro"
```

## Task 6: Classify the route as non-rendering coverage

**Owns:** AC 110–115 and AC 127.

**Files:**
- Modify: `tests/design-system/coverage-closure.test.mjs`
- Modify: `docs/design-system/coverage-debt.json`
- Inspect only: `docs/design-system/manifests/`

**Step 1: Write the failing exact classification assertion**

Add an explicit set such as `RUTAS_SIN_DOCUMENTO = new Set(['/dashboard'])` and make
`esPantalla()` exclude only members of that set. Assert:

- `/dashboard` is discovered as GET but classified non-rendering;
- it is absent from visual debt;
- no manifest claims it;
- `/reportes/{tipo}` remains untouched.

Do not add a broad “name contains dashboard” filter.

**Step 2: Run and observe red before debt edit**

```bash
node --test tests/design-system/coverage-closure.test.mjs
```

Expected: FAIL because `coverage-debt.json` still carries a now-stale tolerance.

**Step 3: Remove only the stale dashboard debt**

Update the measurement narrative and remove `/dashboard` from
`pantallas_sin_manifiesto`. Do not change the report download entry, missing GET entries or
`foundation-shell` tolerance.

**Step 4: Run the coverage suite**

```bash
node --test tests/design-system/coverage-closure.test.mjs
npm run test:design-system:static
```

Expected: PASS; debt count drops by one; no visual artifact is generated.

**Step 5: Prove no frontend/visual changes**

```bash
git diff -- frontend public/app public/css/tokens.css docs/design-system/manifests
```

Expected: empty.

**Step 6: Commit the classification**

```bash
git add tests/design-system/coverage-closure.test.mjs docs/design-system/coverage-debt.json
git commit -m "test: clasificar dashboard como redirect"
```

## Task 7: Run scoped-read and compatibility regressions

**Owns:** AC 097–104 and AC 128–130.

**Files:**
- Modify only if a failing focused test proves an S27 regression.
- Do not modify tests merely to make unrelated failures green.

**Step 1: Re-run the complete focused S27 set**

Run each command separately and record its exit code on its own line:

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingDecisionTest.php
docker compose exec -T app vendor/bin/phpunit tests/unit/ProjectLandingServiceContractTest.php
docker compose exec -T app vendor/bin/phpunit tests/unit/DashboardLandingActionTest.php
docker compose exec -T app php tests/test_dashboard_landing_redirect_contract.php
docker compose exec -T app php tests/test_spa_frontera.php
node --test tests/design-system/coverage-closure.test.mjs
```

Expected: all PASS.

**Step 2: Run read-only scope guards**

First inspect these tests again for writes. Then run only the confirmed static/read-only guards:

```bash
docker compose exec -T app php tests/test_global_table_safety.php
docker compose exec -T app php tests/test_project_scope_callsite_audit.php
```

Expected: PASS. Never substitute `tests/test_api_projects_contract.php`.

**Step 3: Run syntax and focused static analysis**

```bash
docker compose exec -T app php -l src/Services/Landing/ProjectLandingResolver.php
docker compose exec -T app php -l src/Services/Landing/ProjectLandingResult.php
docker compose exec -T app php -l src/Services/Landing/ProjectLandingDecision.php
docker compose exec -T app php -l src/Services/Landing/DashboardLandingResponse.php
docker compose exec -T app php -l src/Services/Landing/DashboardLandingAction.php
docker compose exec -T app php -l src/Services/ProjectLandingService.php
docker compose exec -T app php -l src/Controllers/Core/DashboardController.php
docker compose exec -T app vendor/bin/phpstan analyse src/Services/Landing src/Services/ProjectLandingService.php src/Controllers/Core/DashboardController.php --memory-limit=1G
```

Expected: all syntax checks and PHPStan PASS.

**Step 4: Inspect safety diffs**

```bash
git diff --check
git diff -- admin
git diff -- src/Security/DataScope docs/security
git diff -- database
git status --short
git rev-parse HEAD
```

Expected: diff check PASS; Admin, RLS/security boundary and database diffs empty. Record the SHA.

**Step 5: Fix only demonstrated S27 regressions and commit**

If no fix is needed, make no empty commit. If one is needed, add the exact failing test first, make
the minimal change, re-run all affected commands, then commit only those files.

## Task 8: Cut over, document closure and prepare the repository gate

**Owns:** AC 131–139.

**Files:**
- Modify: `docs/superpowers/plans/2026-08-30-s27-dashboard-landing-redirect.md`
- Modify only when required by the program ledger:
  `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md`

**Step 1: Confirm no separate route cut exists**

`/dashboard` is already the production path. Do not add a feature flag, alias, rewrite, client
bridge or `/app/dashboard`. Confirm the four destinations resolve on the same origin.

**Step 2: Run the final focused gate from a clean task state**

Repeat Task 7 after any closure-document edit that can affect a contract. Record exact outputs,
exit codes, SHA and environment. Do not claim browser visual coverage because no document renders.

**Step 3: Fill `Cierre` with observed evidence**

Record:

- implementation SHA;
- focused test outputs;
- unauthenticated HTTP `302 /login`;
- exact destination/action matrix coverage;
- coverage debt change;
- no DDL/DML;
- empty Admin/RLS/database/frontend diffs;
- retained legacy destination assets;
- rollback as code-only.

Do not mark old checkboxes retroactively.

**Step 4: Commit closure documentation atomically**

```bash
git add docs/superpowers/plans/2026-08-30-s27-dashboard-landing-redirect.md docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
git commit -m "docs: cerrar landing redirect S27"
```

Omit the atlas path if it required no edit.

**Step 5: Apply the branch closure policy**

Only after local green and explicit authorization for the implementation front:

1. fetch `origin`;
2. inspect divergence;
3. integrate `origin/main` into this branch if needed;
4. re-run the complete gate and record the new SHA;
5. push the branch;
6. open a PR to `main`;
7. wait for green CI;
8. merge only with the repository's required approval;
9. confirm `origin/main` equals the verified merge SHA.

Publishing code is not production deploy. Production still requires separate explicit authorization.

## Acceptance Traceability

| Criterion | Owner | Evidence |
|---|---|---|
| S27-AC-001 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-002 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-003 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-004 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-005 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-006 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-007 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-008 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-009 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-010 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-011 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-012 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-013 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-014 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-015 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-016 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-017 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-018 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-019 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-020 | Task 1 | route, method, no-UI and closed-server-boundary characterization |
| S27-AC-021 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-022 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-023 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-024 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-025 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-026 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-027 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-028 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-029 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-030 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-031 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-032 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-033 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-034 | Task 4 | session, scope, project and prefix guards in DashboardLandingAction |
| S27-AC-035 | Task 3 | canonical role wiring through RbacService |
| S27-AC-036 | Task 3 | canonical role wiring through RbacService |
| S27-AC-037 | Task 3 | canonical role wiring through RbacService |
| S27-AC-038 | Task 3 | canonical role wiring through RbacService |
| S27-AC-039 | Task 3 | canonical role wiring through RbacService |
| S27-AC-040 | Task 3 | canonical role wiring through RbacService |
| S27-AC-041 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-042 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-043 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-044 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-045 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-046 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-047 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-048 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-049 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-050 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-051 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-052 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-053 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-054 | Task 4 | area, closed destination and session-week action contracts |
| S27-AC-055 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-056 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-057 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-058 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-059 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-060 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-061 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-062 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-063 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-064 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-065 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-066 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-067 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-068 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-069 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-070 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-071 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-072 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-073 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-074 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-075 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-076 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-077 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-078 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-079 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-080 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-081 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-082 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-083 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-084 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-085 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-086 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-087 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-088 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-089 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-090 | Task 2 | pure landing matrix and ProjectLandingResult unit tests |
| S27-AC-091 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-092 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-093 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-094 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-095 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-096 | Task 3 | service compatibility, sanitizeWeek and caller regression contracts |
| S27-AC-097 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-098 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-099 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-100 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-101 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-102 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-103 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-104 | Task 7 | scoped-read, no-write, RLS and admin guards |
| S27-AC-105 | Task 5 | HTTP emitter, no-store and no-intermediate-document contract |
| S27-AC-106 | Task 5 | HTTP emitter, no-store and no-intermediate-document contract |
| S27-AC-107 | Task 5 | HTTP emitter, no-store and no-intermediate-document contract |
| S27-AC-108 | Task 5 | HTTP emitter, no-store and no-intermediate-document contract |
| S27-AC-109 | Task 5 | HTTP emitter, no-store and no-intermediate-document contract |
| S27-AC-110 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-111 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-112 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-113 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-114 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-115 | Task 6 | non-rendering coverage classification and no-frontend diff guards |
| S27-AC-116 | Task 2 | pure exhaustive matrix, tie and CNC tolerance tests |
| S27-AC-117 | Task 2 | pure exhaustive matrix, tie and CNC tolerance tests |
| S27-AC-118 | Task 2 | pure exhaustive matrix, tie and CNC tolerance tests |
| S27-AC-119 | Task 2 | pure exhaustive matrix, tie and CNC tolerance tests |
| S27-AC-120 | Task 4 | action fakes, rejection and accepted-destination tests |
| S27-AC-121 | Task 4 | action fakes, rejection and accepted-destination tests |
| S27-AC-122 | Task 4 | action fakes, rejection and accepted-destination tests |
| S27-AC-123 | Task 4 | action fakes, rejection and accepted-destination tests |
| S27-AC-124 | Task 5 | controller contract and unauthenticated HTTP smoke |
| S27-AC-125 | Task 5 | controller contract and unauthenticated HTTP smoke |
| S27-AC-126 | Task 1 | SpaRouter frontier test |
| S27-AC-127 | Task 6 | coverage-closure test |
| S27-AC-128 | Task 7 | focused regression, static analysis and recorded exit codes |
| S27-AC-129 | Task 7 | focused regression, static analysis and recorded exit codes |
| S27-AC-130 | Task 7 | focused regression, static analysis and recorded exit codes |
| S27-AC-131 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-132 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-133 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-134 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-135 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-136 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-137 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-138 | Task 8 | cutover, rollback and documentation closure evidence |
| S27-AC-139 | Task 8 | cutover, rollback and documentation closure evidence |

## Verification Matrix

| Surface | Required evidence |
|---|---|
| route/method | exact router contract and no write routes |
| authentication | middleware baselines plus defensive action test |
| project coherence | scope/project/prefix/area fake matrix |
| role | `RbacService` alias matrix |
| week | pure open/pending/confirmed matrix |
| CNC | active-row, numeric and epsilon cases |
| redirect safety | four accepted destinations; hostile values rejected |
| HTTP | `302`, `Location`, `no-store`, empty body |
| React | explicit zero frontend changes |
| visual/a11y/themes | explicit non-applicability because no document renders |
| coverage | exact non-render route and stale debt removal |
| RLS/data | scoped-read guards; no RLS diff; no DDL/DML |
| Admin | empty `git diff -- admin` |
| rollback | code/document-only |

## Cierre

**Estado de ejecución:** no iniciado. Este plan fue escrito y autorrevisado en una sesión
exclusivamente documental. No existe todavía evidencia de implementación, pruebas post-cambio,
commit, PR, publicación o deploy.

Al ejecutar, sustituir este bloque por evidencia observada sin reescribir retrospectivamente las
casillas:

- SHA inicial y SHA verificado:
- tareas ejecutadas:
- pruebas y códigos de salida:
- smoke HTTP:
- matriz de landing:
- cobertura no visual:
- diff Admin/RLS/database/frontend:
- datos tocados o restaurados:
- límites pendientes:
- rollback verificado:
- PR/CI/publicación:
