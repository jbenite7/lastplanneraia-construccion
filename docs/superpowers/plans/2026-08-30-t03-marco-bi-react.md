# T03 Marco BI React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` only in an
> explicitly authorized implementation session. Use `superpowers:test-driven-development` before
> each production change and `superpowers:verification-before-completion` before a green/complete
> claim. Apply the repository branch/PR/publication gate in order. Checkbox syntax is an execution
> prompt; `Cierre` and git history are the evidence.

**Goal:** deliver one shared BI runtime inside the main React SPA with a server-authoritative
eight-sheet manifest, the approved Gerencia/Obra canvases, one canonical URL query, scoped
project/period/filter contracts, typed common endpoints, accessible state/figure/drawer primitives
and a deferred zero-caller retirement of all PHP/JS BI and `ct-app`—without changing RLS, schema or
data.

**Architecture:** T03-A establishes `BiSheetAccessPolicy`, a pure sheet manifest, `BiQueryParser`,
a minimal `GET /api/bi/context`, additive presenters for common endpoints, strict Zod gateways,
generation-based remote state and a single `MarcoBi` within T01. S17–S24 plug real content into the
outlet by vertical slices. T03-R runs only after all eight leaves have cut and been published; it
then removes VIEW-04…09, imperative BI runtime, `ct-app`, generated bundle and exclusive
CSS/vendors with a recorded caller census.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, Zod 4, React Router 7, Vitest 4,
Testing Library, Playwright, existing Database/DataScope/RBAC services, semantic HTML/SVG and AIA
tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-t03-marco-bi-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or a
  different worktree.
- Inspect status and relevant diffs before every task. Preserve pre-existing edits and never clean,
  revert, reformat or stage unrelated work.
- This documentation session authorizes no implementation, commit, push, PR, publication or deploy.
  The commands below are future execution instructions.
- Do not begin T03-A until T01 `Cierre` proves AppShell, route outlet, server navigation, theme
  bootstrap, project/session generation and the common typed client seam are published.
- Do not wait for all sheets to implement T03-A. S17 is the first real consumer and proves the seam.
- Do not cut any `/bi/*` route to a placeholder, blank shell or mocked product screen.
- Do not start T03-R until S17–S24 are published and their `Cierre` sections prove React cutover.
- `/admin/` is excluded.
- Do not modify RLS, `src/Security/DataScope`, schema, migrations, views SQL, tables, indexes, FKs,
  grants, users, credentials, memberships, seeds, fixtures or persistent data.
- Do not run DDL/DML or tests that seed, update, recalculate or delete operational BI/LPS data.
- Do not change `internal.bi.preview`, `bi.control_tower.visible`, role aliases or capability maps.
- Normalize trusted roles with `RbacService::normalizeRole()`.
- A can open all eight leaves; its canvas is a navigation preference. D/R can open the seven Obra
  leaves and depend on the existing flag. Other roles remain hidden.
- Apply the same `BiSheetAccessPolicy` to each page and its main API before reading data.
- Preserve `BiProjectScope`/`MultiProjectScope` and `project_id` predicates. Query data never grants
  access.
- Do not create `/api/bi/v2/*` or duplicate report endpoints.
- The only new T03 route is `GET /api/bi/context` and it requires a strict Zod schema plus PHP
  contract.
- Preserve existing common endpoint paths and legacy keys additively until their caller census is
  zero.
- Report, detail and mutation contracts remain owned by S17–S24.
- No component or hook calls `fetch`. All React traffic passes through
  `frontend/src/lib/api/cliente.ts`.
- Zod is the only TypeScript type authority for HTTP payloads.
- Do not import or copy files from `ct-app` into `frontend/src`.
- Do not hand-edit `public/ct-app/assets/ct.js` or `ct.css`.
- New React BI pages do not load Chart.js, Lucide or Tailwind CDN globals.
- Use `public/css/tokens.css`; no literal colors, inline styles, CSS-in-JS, `!important` or a parallel
  token family.
- Dark is default; light is equivalent. Validate the canonical 1180×820 dark viewport plus the four
  additional spec viewports.
- Keep legacy visual baselines/goldens unchanged unless a separately approved visual update says
  otherwise.
- Future commits are atomic. This documentation session creates none.

## Delivery Gates

### T03-A — Shared platform gate

Complete Tasks 1–11. Closure means the common policy, context, query, endpoint adapters, gateways,
state controller and UI primitives are published and consumed by at least the real S17 slice. All
legacy views/assets may remain intentionally available.

### T03-R — Retirement gate

Complete Task 12 only after S17–S24 are published. Closure means every BI leaf serves the main React
SPA, the caller census for all shared legacy pieces is zero, exclusive assets are removed, and the
eight-leaf regression is green.

## Dependency Gate for T03-A

1. Read T01 `Cierre` rather than its checkboxes.
2. Verify T01 exposes:
   - one AppShell and route outlet;
   - server-authored sidebar navigation;
   - `cliente.ts` with Zod, AbortSignal and typed non-2xx errors;
   - session/project generation invalidation;
   - dark/light bootstrap before paint;
   - shared error destinations and focus primitives.
3. Inspect current state once:

    pwd
    git branch --show-current
    git status --short
    git diff --stat
    git rev-parse HEAD
    docker compose config --services
    docker compose ps

4. Record current callers:

    rg -n "views/bi/(_filters|_layout|_nav|control-tower-piloto|control-tower|index)\.php" public src views tests docs
    rg -n "bi-spa\.js|bi_filter_drawer\.js|bi_chart_theme\.js|bi-access\.js" public src views tests docs
    rg -n "ct-app|public/ct-app|CT_PILOTO" public src views ct-app tests docs
    rg -n "Chart\.js|cdn\.jsdelivr|cdn\.tailwindcss|lucide" views/bi public/js public/css tests

5. Inspect tests before running them. Use only read-only/static baselines:

    npm --prefix frontend test
    npm --prefix frontend run typecheck
    docker compose exec -T app php tests/test_bi_preview_gate.php
    docker compose exec -T app php tests/test_bi_access_context.php
    docker compose exec -T app php tests/test_bi_lienzo_por_rol.php
    node --test tests/design-system/bi-utilities.test.mjs tests/test_foundation_shell_contract.mjs

6. Skip any test that writes or depends on mutable seeded operational data; replace its T03
   assertion with fakes/static fixtures.
7. Confirm prohibited diffs are empty before and after every production task:

    git diff -- admin
    git diff -- src/Security/DataScope docs/security database
    git diff -- .env

## File Structure

### Create — backend common runtime

- `src/Security/BiSheetAccessPolicy.php`.
- `src/Services/Bi/Manifest/BiSheetDefinition.php`.
- `src/Services/Bi/Manifest/BiSheetManifest.php`.
- `src/Services/Bi/Http/BiQuery.php`.
- `src/Services/Bi/Http/BiQueryParser.php`.
- `src/Services/Bi/Http/BiApiError.php`.
- `src/Services/Bi/Http/BiContextPresenter.php`.
- `src/Services/Bi/Http/BiCommonPresenter.php`.
- `src/Controllers/Api/BiContextApiController.php`.

### Create — backend tests and fixtures

- `tests/fixtures/bi-framework/manifest.php`.
- `tests/fixtures/bi-framework/context.php`.
- `tests/fixtures/bi-framework/query.php`.
- `tests/test_bi_migration_inventory.php`.
- `tests/test_bi_sheet_access_policy.php`.
- `tests/test_bi_context_contract.php`.
- `tests/test_bi_query_contract.php`.
- `tests/test_bi_common_endpoint_contracts.php`.
- `tests/test_bi_framework_scope_guards.php`.
- `tests/test_bi_retirement_census.php`.

### Create — frontend common runtime

- `frontend/src/lib/api/esquemas/biComun.ts`.
- `frontend/src/lib/api/esquemas/biComun.test.ts`.
- `frontend/src/lib/api/biContexto.ts`.
- `frontend/src/lib/api/biContexto.test.ts`.
- `frontend/src/lib/api/biFiltros.ts`.
- `frontend/src/lib/api/biFiltros.test.ts`.
- `frontend/src/modulos/bi/manifiestoBi.ts`.
- `frontend/src/modulos/bi/manifiestoBi.test.ts`.
- `frontend/src/modulos/bi/consultaBi.ts`.
- `frontend/src/modulos/bi/consultaBi.test.ts`.
- `frontend/src/modulos/bi/MarcoBi.tsx`.
- `frontend/src/modulos/bi/MarcoBi.test.tsx`.
- `frontend/src/modulos/bi/NavegacionBi.tsx`.
- `frontend/src/modulos/bi/NavegacionBi.test.tsx`.
- `frontend/src/modulos/bi/FiltrosBi.tsx`.
- `frontend/src/modulos/bi/FiltrosBi.test.tsx`.
- `frontend/src/modulos/bi/EstadoBi.tsx`.
- `frontend/src/modulos/bi/EstadoBi.test.tsx`.
- `frontend/src/modulos/bi/FiguraBi.tsx`.
- `frontend/src/modulos/bi/FiguraBi.test.tsx`.
- `frontend/src/modulos/bi/LeyendaBi.tsx`.
- `frontend/src/modulos/bi/LinajeDrawer.tsx`.
- `frontend/src/modulos/bi/LinajeDrawer.test.tsx`.
- `frontend/src/modulos/bi/useConsultaBi.ts`.
- `frontend/src/modulos/bi/useConsultaBi.test.tsx`.
- `frontend/src/modulos/bi/bi.css`.

### Create — browser/intercepted evidence

- `tests/browser/bi-marco-react.spec.mjs`.
- `tests/browser/bi-marco-react.a11y.mjs`.
- `tests/browser/fixtures/bi-marco-react.mjs`.

### Modify during T03-A

- `public/index.php` — register only `GET /api/bi/context`; preserve all existing routes.
- `src/Controllers/Bi/BiViewController.php` — use sheet policy and serve SPA only with a real leaf.
- `src/Controllers/Api/BiControlTowerApiController.php` — delegate common endpoint presenters.
- `src/View/Components/BiAccessComponent.php` — consume manifest/canvas landing, no duplicate map.
- `src/Support/BiProjectScope.php` — only if a characterized shared scope gap requires a minimal
  extraction; do not change authorization semantics.
- `frontend/src/lib/api/cliente.ts` — only for a test-proven T01 transport gap.
- `frontend/src/shell/rutas.tsx` — register nested BI routes.
- `frontend/src/shell/NavegacionLateral.tsx` — continue consuming server navigation; no BI role map.
- `frontend/src/App.tsx` or provider composition — mount the shared frame once.
- `frontend/src/main.tsx` — import the layered BI CSS once.
- `public/css/tokens.css` — only if a semantic token is demonstrably absent, with tests.
- existing BI tests/manifests — adapt to coexistence without deleting legacy assertions.

### Preserve through T03-A

- `views/bi/_filters.php`.
- `views/bi/_layout.php`.
- `views/bi/_nav.php`.
- `views/bi/control-tower-piloto.php`.
- `views/bi/control-tower.php`.
- `views/bi/index.php`.
- `public/js/modules/bi-spa.js`.
- `public/js/modules/bi_filter_drawer.js`.
- `public/js/modules/bi_chart_theme.js`.
- `public/js/modules/bi-access.js`.
- shared/exclusive BI CSS pending census.
- `ct-app/` and `public/ct-app/`.

### Deferred delete/trim — T03-R only

- the six VIEW files above;
- the four imperative BI JS modules when zero callers;
- `ct-app/` and `public/ct-app/` after S20 closure;
- `CT_PILOTO` branches/bootstrap;
- exclusive BI CSS/adapters;
- Chart.js/Lucide/Tailwind CDN includes exclusive to legacy BI;
- compatibility fields/aliases only when their API caller census is zero;
- dead manifests/tests that assert legacy hosts rather than product behavior.

## Task 1: Freeze ownership, measured behavior and caller census

**Files:**

- Create `tests/fixtures/bi-framework/manifest.php`
- Create `tests/test_bi_migration_inventory.php`
- Reference the six VIEW files, four JS modules, CSS assets, `ct-app` and route declarations
- Modify the T03 spec or atlas only if the current code disproves an inventory fact

### Step 1: Write the static characterization test

Assert:

- the eight HTML routes and main report endpoints retain their exact keys;
- VIEW-04…09 each have one T03 ownership record;
- VIEW-09 has no runtime require/include/render/route;
- `bi-spa.js` contains the eight endpoint map and direct `fetch` calls;
- `ct-app` is reachable only through the Intermedia pilot host/flag;
- each legacy asset is classified shared, S20-specific or exclusive.

Run:

    docker compose exec -T app php tests/test_bi_migration_inventory.php

Expected: FAIL because the fixture/test does not exist.

### Step 2: Implement fixture-only characterization

Do not change production. Encode exact routes, labels, report keys, canvases, owners and current
callers.

### Step 3: Re-run the focused test

    docker compose exec -T app php tests/test_bi_migration_inventory.php

Expected: PASS without a database connection.

### Step 4: Record baseline searches

Store command, SHA and result in the future T03-A closure evidence. Do not infer zero callers from a
single glob.

### Step 5: Commit in the authorized implementation session

    git add tests/fixtures/bi-framework/manifest.php tests/test_bi_migration_inventory.php
    git commit -m "test(bi): freeze shared framework inventory"

**Vertical checkpoint:** ownership and legacy surface are executable facts; runtime is unchanged.

## Task 2: Extend the common transport only for a proven T01 gap

**Files:**

- Modify `frontend/src/lib/api/cliente.test.ts`
- Modify `frontend/src/lib/api/cliente.ts` only when the failing test proves the gap
- Create no other HTTP client

### Step 1: Characterize current transport

Test:

- success validation;
- non-2xx JSON error preserves status/code/retryable/fieldErrors;
- malformed non-2xx becomes a safe typed transport error;
- AbortSignal reaches `fetch` and abort remains distinguishable;
- session-expired status is available to T01.

Run:

    npm --prefix frontend test -- cliente.test.ts

Expected: existing success tests pass; typed non-2xx assertions fail if T01 did not already close
the seam.

### Step 2: Implement the smallest shared improvement

Add one typed error value and parse a known safe error schema before throwing. Preserve existing
call signatures and Zod validation.

### Step 3: Re-run focused tests and typecheck

    npm --prefix frontend test -- cliente.test.ts
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 4: Commit after authorization

    git add frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts
    git commit -m "refactor(frontend): preserve typed API errors"

**Vertical checkpoint:** T03 can use the one client without duplicating transport.

## Task 3: Implement the sheet manifest, canvases and access policy

**Files:**

- Create backend manifest/policy files
- Create `tests/test_bi_sheet_access_policy.php`
- Create `frontend/src/modulos/bi/manifiestoBi.ts` and test
- Modify `BiAccessComponent` and `BiViewController` only after policy tests pass
- Align S17/S22/S24 documentation statements with the closed Admin canvas decision

### Step 1: Write failing pure PHP policy tests

Cover:

- exact eight keys/routes/endpoints;
- exact Gerencia and Obra order;
- A allowed on all eight without flag;
- A first landing overview and later valid session module;
- shared leaf preserves prior canvas;
- D/R only seven Obra leaves and Intermedia landing;
- D/R flag off hidden;
- every other normalized role hidden;
- `MULTI` never grants access;
- page/API policy method returns identical decisions.

Run:

    docker compose exec -T app php tests/test_bi_sheet_access_policy.php

Expected: FAIL because manifest/policy do not exist.

### Step 2: Implement pure manifest and policy

Keep the manifest free of Database access. Pass a trusted normalized role and the current flag
decision into the policy. Do not edit RBAC catalogs.

### Step 3: Write and pass frontend registry parity tests

The presentation registry may contain icons/component references, but keys, labels, hrefs and order
must match the validated context fixture.

    npm --prefix frontend test -- manifiestoBi.test.ts

### Step 4: Integrate the server entry seam

`BiAccessComponent` asks the manifest/policy for landing and href. `BiViewController` gates the
requested sheet before reading scope or rendering. Do not cut a route to React yet.

### Step 5: Run focused regressions

    docker compose exec -T app php tests/test_bi_sheet_access_policy.php
    docker compose exec -T app php tests/test_bi_preview_gate.php
    docker compose exec -T app php tests/test_bi_access_context.php
    docker compose exec -T app php tests/test_bi_lienzo_por_rol.php
    npm --prefix frontend test -- manifiestoBi.test.ts NavegacionLateral.test.tsx

Expected: PASS with no RBAC/DataScope diff.

### Step 6: Commit after authorization

    git add src/Security/BiSheetAccessPolicy.php src/Services/Bi/Manifest src/View/Components/BiAccessComponent.php src/Controllers/Bi/BiViewController.php tests/test_bi_sheet_access_policy.php frontend/src/modulos/bi/manifiestoBi.ts frontend/src/modulos/bi/manifiestoBi.test.ts docs/superpowers/specs docs/superpowers/plans
    git commit -m "feat(bi): centralize sheet canvas policy"

**Vertical checkpoint:** one server policy governs leaf visibility without changing capabilities.

## Task 4: Add the minimal typed BI context endpoint

**Files:**

- Create `src/Controllers/Api/BiContextApiController.php`
- Create `src/Services/Bi/Http/BiContextPresenter.php`
- Create `tests/fixtures/bi-framework/context.php`
- Create `tests/test_bi_context_contract.php`
- Create `frontend/src/lib/api/esquemas/biComun.ts` and tests
- Create `frontend/src/lib/api/biContexto.ts` and tests
- Modify `public/index.php` once

### Step 1: Write the failing PHP route/contract test

Test exact success fields, headers, active sheet/canvas, authorized canvas/sheets and control
capabilities. Test A, D, R, hidden role, invalid sheet and sensitive-field absence.

Run:

    docker compose exec -T app php tests/test_bi_context_contract.php

Expected: FAIL because the route/controller do not exist.

### Step 2: Write failing Zod/gateway tests

Cover strict success/error unions, enums, hrefs, unique sheet keys, active membership and rejection
of role/db/prefix fields.

    npm --prefix frontend test -- biComun.test.ts biContexto.test.ts

Expected: FAIL because schemas/gateway do not exist.

### Step 3: Implement policy → presenter → controller

Register only:

    GET /api/bi/context

The controller requires auth, validates `sheet`, applies preview/sheet policy, presents navigation
and sends `Cache-Control: no-store`. It does not resolve metrics/projects.

### Step 4: Implement gateway through `cliente.ts`

No component may know the endpoint string.

### Step 5: Re-run contract/type tests

    docker compose exec -T app php tests/test_bi_context_contract.php
    npm --prefix frontend test -- biComun.test.ts biContexto.test.ts
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 6: Commit after authorization

    git add public/index.php src/Controllers/Api/BiContextApiController.php src/Services/Bi/Http/BiContextPresenter.php tests/fixtures/bi-framework/context.php tests/test_bi_context_contract.php frontend/src/lib/api/esquemas/biComun.ts frontend/src/lib/api/esquemas/biComun.test.ts frontend/src/lib/api/biContexto.ts frontend/src/lib/api/biContexto.test.ts
    git commit -m "feat(bi): expose typed framework context"

**Vertical checkpoint:** React can render authorized BI chrome without loading a report.

## Task 5: Implement the canonical query, scope and URL codec

**Files:**

- Create `src/Services/Bi/Http/BiQuery.php`
- Create `src/Services/Bi/Http/BiQueryParser.php`
- Create `tests/fixtures/bi-framework/query.php`
- Create `tests/test_bi_query_contract.php`
- Create `frontend/src/modulos/bi/consultaBi.ts` and test
- Create `frontend/src/lib/api/biFiltros.ts` and test

### Step 1: Write failing backend matrix tests

Cover canonical keys, aliases, CSV/repeated IDs, dedupe/order, date validation, week/range
incompatibility, unexpected arrays, authority-like keys, sheet filter extensions, single/multi
policy and error fields.

    docker compose exec -T app php tests/test_bi_query_contract.php

Expected: FAIL.

### Step 2: Implement immutable query value and parser

Parse intent only. Resolve authorization through `BiProjectScope` after parsing. Legacy aliases are
an adapter mode; canonical mode rejects them.

### Step 3: Write failing frontend codec tests

Cover parse/serialize round-trip, one project representation, supported-key projection, Back/Forward
fixtures, focus enums, project ordering and clearing behavior.

    npm --prefix frontend test -- consultaBi.test.ts biFiltros.test.ts

Expected: FAIL.

### Step 4: Implement codec and common filter gateway

Use `URLSearchParams` inside the codec module only. Do not store a second copy in DOM/global state.

### Step 5: Run focused tests

    docker compose exec -T app php tests/test_bi_query_contract.php
    docker compose exec -T app php tests/test_bi_project_scope.php
    npm --prefix frontend test -- consultaBi.test.ts biFiltros.test.ts
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 6: Commit after authorization

    git add src/Services/Bi/Http/BiQuery.php src/Services/Bi/Http/BiQueryParser.php tests/fixtures/bi-framework/query.php tests/test_bi_query_contract.php frontend/src/modulos/bi/consultaBi.ts frontend/src/modulos/bi/consultaBi.test.ts frontend/src/lib/api/biFiltros.ts frontend/src/lib/api/biFiltros.test.ts
    git commit -m "feat(bi): define canonical scoped query"

**Vertical checkpoint:** page URLs are reproducible and cannot grant authority.

## Task 6: Stabilize the common endpoint contracts

**Files:**

- Create `src/Services/Bi/Http/BiApiError.php`
- Create `src/Services/Bi/Http/BiCommonPresenter.php`
- Create `tests/test_bi_common_endpoint_contracts.php`
- Extend `biComun.ts` and gateway tests
- Modify `BiControlTowerApiController` by delegation only

### Step 1: Write failing PHP contract tests

Using fakes/static fixtures, cover:

- projects success/no access and no raw role;
- weeks single/multi/intersection empty/project denied;
- filter options dependency and project isolation;
- lineage list/key/unknown/hidden;
- metric executable/descriptive/unknown/insufficient;
- JSON UTF-8, `no-store` and canonical errors;
- legacy fields retained additively.

Run:

    docker compose exec -T app php tests/test_bi_common_endpoint_contracts.php

Expected: FAIL on canonical blocks/presenters.

### Step 2: Add strict frontend schemas

Test completeness enums, null handling, lineage fields, weeks and option values. Types use
`z.infer`.

### Step 3: Implement presenters and delegate controller methods

Do not change report formulas, service readers or routes. Do not turn descriptive metrics into
executable ones.

### Step 4: Run focused regressions

    docker compose exec -T app php tests/test_bi_common_endpoint_contracts.php
    docker compose exec -T app php tests/test_bi_metric_endpoint.php
    npm --prefix frontend test -- biComun.test.ts biFiltros.test.ts
    npm --prefix frontend run typecheck

Inspect `test_bi_metric_endpoint.php` first and run only if its setup is read-only; otherwise use
the new fake-backed contract test.

### Step 5: Commit after authorization

    git add src/Services/Bi/Http/BiApiError.php src/Services/Bi/Http/BiCommonPresenter.php src/Controllers/Api/BiControlTowerApiController.php tests/test_bi_common_endpoint_contracts.php frontend/src/lib/api/esquemas/biComun.ts frontend/src/lib/api/biFiltros.ts frontend/src/lib/api
    git commit -m "refactor(bi): stabilize common API contracts"

**Vertical checkpoint:** shared endpoints are typed without a parallel API.

## Task 7: Build generation-based remote state

**Files:**

- Create `frontend/src/modulos/bi/useConsultaBi.ts` and test
- Extend `biContexto.ts`/`biFiltros.ts` tests
- Modify T01 session/project generation hook only if its published seam requires registration

### Step 1: Write failing lifecycle tests

Use deferred promises and fake history to prove:

- query/sheet change aborts old context/options/report;
- stale completions cannot commit;
- option failure is local;
- report failure preserves frame/query/retry;
- context failure mounts no sheet;
- exact cache keys partition session/sheet/scope/period/filters/page/focus;
- logout/project generation clears cache;
- reload preserves URL and bypasses prior freshness;
- there is no report polling.

    npm --prefix frontend test -- useConsultaBi.test.tsx

Expected: FAIL.

### Step 2: Implement a reducer/hook with explicit generations

Keep report loading as an injected leaf gateway. T03 owns lifecycle, not report schemas.

### Step 3: Test T01 session-expiry integration

A typed unauthenticated error calls the one T01 path; T03 does not navigate independently to login.

### Step 4: Re-run focused tests/typecheck

    npm --prefix frontend test -- useConsultaBi.test.tsx biContexto.test.ts biFiltros.test.ts
    npm --prefix frontend run typecheck

Expected: PASS.

### Step 5: Commit after authorization

    git add frontend/src/modulos/bi/useConsultaBi.ts frontend/src/modulos/bi/useConsultaBi.test.tsx frontend/src/lib/api
    git commit -m "feat(bi): guard remote state by query generation"

**Vertical checkpoint:** shared async state cannot mix sheets or project scopes.

## Task 8: Build the single BI frame, navigation, filters and states

**Files:**

- Create `MarcoBi`, `NavegacionBi`, `FiltrosBi`, `EstadoBi` and tests
- Create initial `bi.css` with tokens only
- Modify T01 route/provider seam without cutting a leaf prematurely

### Step 1: Write failing component tests

Cover:

- one frame/outlet;
- exact route links and `aria-current`;
- A canvas switch; D/R no switch;
- desktop rail and mobile accessible menu using one link source;
- inline/drawer filters;
- active count/chips/remove/clear/apply;
- supported controls by sheet;
- loading/empty/insufficient/partial/stale/403/404/422/error;
- focus entry/return/Escape/inert;
- Back/Forward integration through codec.

    npm --prefix frontend test -- MarcoBi.test.tsx NavegacionBi.test.tsx FiltrosBi.test.tsx EstadoBi.test.tsx

Expected: FAIL.

### Step 2: Implement semantic components

Use native links/buttons/forms. Do not use `onclick` strings, globals or hidden duplicate controls.

### Step 3: Add token-only responsive CSS

Start at 320 px, then add layout changes for tablet/desktop. No literal colors or local tokens when
canonical tokens exist.

### Step 4: Register nested routes without production placeholder

The frame may be imported by tests. A production `/bi/*` leaf switches only when its S17–S24 page
component exists and its route gate is enabled.

### Step 5: Run focused suite and static CSS guards

    npm --prefix frontend test -- MarcoBi.test.tsx NavegacionBi.test.tsx FiltrosBi.test.tsx EstadoBi.test.tsx
    npm --prefix frontend run typecheck
    rg -n "#[0-9A-Fa-f]{3,8}|rgb\(|hsl\(|style=|!important|fetch\(" frontend/src/modulos/bi

Expected: tests/typecheck PASS; search has no prohibited production matches.

### Step 6: Commit after authorization

    git add frontend/src/modulos/bi frontend/src/shell/rutas.tsx frontend/src/App.tsx frontend/src/main.tsx
    git commit -m "feat(bi): add shared React report frame"

**Vertical checkpoint:** BI chrome is reusable and accessible; no route shows fake content.

## Task 9: Build evidence, drawer, figure and visual accessibility primitives

**Files:**

- Create `FiguraBi`, `LeyendaBi`, `LinajeDrawer` and tests
- Extend `bi.css`
- Create `tests/browser/bi-marco-react.a11y.mjs` and fixtures

### Step 1: Write failing primitive tests

Prove:

- figure requires title, summary, unit, legend and equivalent data;
- null/insufficient never renders zero/success;
- keyboard does not depend on Canvas/hover/tooltip;
- embedded lineage opens without network;
- fallback lineage uses the common gateway;
- one drawer stack supports detail → lineage → back;
- focus trap/Escape/return/deep link;
- server-authored href/capability only;
- S22 candidate display causes no network/write/dedupe claim.

    npm --prefix frontend test -- FiguraBi.test.tsx LinajeDrawer.test.tsx

Expected: FAIL.

### Step 2: Implement semantic primitives

Use HTML/SVG and a visible or accessible equivalent data region. Do not add a chart dependency.

### Step 3: Add responsive/theme/Axe fixtures

Render canonical frame states at 1440×900, 1180×820, 768×1024, 480×900 and 390×844 in dark/light.
Include 320 px + zoom 200%, reduced motion and forced colors in component/browser coverage.

### Step 4: Run focused evidence

    npm --prefix frontend test -- FiguraBi.test.tsx LinajeDrawer.test.tsx
    npx playwright test tests/browser/bi-marco-react.a11y.mjs --workers=1

Expected: PASS; zero serious/critical Axe violations and no page overflow.

### Step 5: Commit after authorization

    git add frontend/src/modulos/bi tests/browser/bi-marco-react.a11y.mjs tests/browser/fixtures/bi-marco-react.mjs
    git commit -m "feat(bi): add accessible evidence primitives"

**Vertical checkpoint:** every leaf can present charts/details/lineage without global vendors.

## Task 10: Prove safety and the eight-leaf consumer seam

**Files:**

- Create `tests/test_bi_framework_scope_guards.php`
- Create `tests/browser/bi-marco-react.spec.mjs`
- Modify shared route tests/manifests
- Consume a real S17 page component; do not build S17 business content in T03

### Step 1: Write failing safety guards

Static/fake-backed assertions:

- every common controller orders sheet policy before scope/reader;
- every reader port receives `MultiProjectScope` or project ID;
- no new dynamic prefix/table SQL;
- common responses are `no-store`;
- sensitive keys/log patterns absent;
- no DML strings in new T03 tests/services except negative assertions.

### Step 2: Write the intercepted browser matrix

Cover:

- A both canvas, first/last landing and all eight direct leaves;
- D/R Obra/Intermedia and overview 404;
- hidden role 404;
- project denied 403;
- URL filters/Back/Forward/reload;
- aborted request and stale response;
- context/options/report failures;
- five viewports, two themes and console/network cleanliness.

Only the S17 leaf uses real product content at T03-A. Stub network responses, not product UI.

### Step 3: Run focused matrix

    docker compose exec -T app php tests/test_bi_framework_scope_guards.php
    docker compose exec -T app php tests/test_bi_context_contract.php
    docker compose exec -T app php tests/test_bi_query_contract.php
    docker compose exec -T app php tests/test_bi_common_endpoint_contracts.php
    npm --prefix frontend test
    npm --prefix frontend run typecheck
    npx playwright test tests/browser/bi-marco-react.spec.mjs tests/browser/bi-marco-react.a11y.mjs --workers=1

Expected: PASS. Read every return code on its own line.

### Step 4: Inspect runtime only after intercepted tests

Use `/dev/entrar` with allowed seeded accounts. Confirm correct route, active sheet and project before
capturing evidence. Do not perform S20 management or other mutations.

### Step 5: Check prohibited diffs and traffic

    git diff -- admin
    git diff -- src/Security/DataScope docs/security database
    git diff -- .env
    rg -n "fetch\(" frontend/src --glob '!lib/api/cliente.ts'
    rg -n "ct-app|__CT_BOOTSTRAP__|Chart\.|window\.BI|switchView" frontend/src

Expected: empty prohibited diffs and no forbidden runtime references.

### Step 6: Commit after authorization

    git add tests/test_bi_framework_scope_guards.php tests/browser/bi-marco-react.spec.mjs tests/browser/fixtures/bi-marco-react.mjs frontend/src src/Services/Bi src/Security/BiSheetAccessPolicy.php public/index.php
    git commit -m "test(bi): prove shared React framework seam"

**Vertical checkpoint:** shared platform is proven with real S17 composition and no data changes.

## Task 11: Verify, close and publish T03-A

### Step 1: Re-run complete focused verification

Run exact T03/S17 static, PHP, frontend and intercepted suites from Tasks 1–10. Then run proportional
repo gates required by the current `AGENTS.md`. Do not substitute old output.

### Step 2: Inspect UI manually

At minimum:

- A management and site canvas;
- D and R site canvas;
- hidden role;
- 403 project;
- dark/light;
- five viewports;
- filters, drawer, Back/Forward, reload;
- console and network.

### Step 3: Record coexistence census

Document that VIEW-04…09, JS, CSS and `ct-app` remain intentionally. Record each active caller and
its owning uncut sheet.

### Step 4: Final safety review

Confirm no RLS/database/admin/data changes and no production placeholder. Record starting and
verified SHAs.

### Step 5: Follow the repository close gate

Use atomic commits already created, fetch/integrate/reverify, push the exact verified branch, open a
PR to `main` and require green CI. Deployment remains separately unauthorized.

### Step 6: Update `Cierre`

Record exact commands, return codes, visual evidence, caller census, rollback, PR and CI.

**T03-A checkpoint:** common platform is published and consumable; retirement remains deferred.

## Task 12: Execute T03-R after all eight sheets close

**Dependency proof:**

- S17, S18, S19, S20, S21, S22, S23 and S24 `Cierre` sections name published SHAs;
- each of the eight `/bi/*` routes serves React;
- S20 proves `ct-app` functionality has been rebuilt;
- a current caller census returns zero productive legacy consumers.

### Step 1: Write the failing zero-caller test

`tests/test_bi_retirement_census.php` scans production routes/views/assets/imports and fails while
any shared legacy caller remains. Documentation references do not count as runtime callers.

### Step 2: Run and classify every match

    docker compose exec -T app php tests/test_bi_retirement_census.php
    rg -n "views/bi/|bi-spa\.js|bi_filter_drawer\.js|bi_chart_theme\.js|bi-access\.js|ct-app|CT_PILOTO" public src views frontend tests

Expected before deletion: test FAILS with an explicit inventory. Every remaining runtime match must
be removed or justified as non-exclusive before continuing.

### Step 3: Remove hosts and island

Delete with `apply_patch`/normal version-control edits:

- VIEW-04…09;
- `ct-app/` and `public/ct-app/`;
- pilot bootstrap/flag branches;
- obsolete generated-asset build wiring.

VIEW-09 gets no replacement.

### Step 4: Remove imperative JS/CSS/vendors by exact census

Delete each module/adapter/include only if no caller outside BI legacy exists. If a vendor still has
another consumer, keep it and record that owner.

### Step 5: Retire compatibility fields/aliases separately

Do not conflate UI retirement with API adapter retirement. Remove an alias only after its own API
caller census is zero and contract tests are updated.

### Step 6: Update manifests and documentation

Update design-system/runtime manifests, route coverage, 42-view atlas and any tests that enumerated
legacy hosts. Preserve behavior tests for the eight product routes.

### Step 7: Run full regression

    docker compose exec -T app php tests/test_bi_retirement_census.php
    docker compose exec -T app php tests/test_bi_sheet_access_policy.php
    docker compose exec -T app php tests/test_bi_context_contract.php
    docker compose exec -T app php tests/test_bi_query_contract.php
    docker compose exec -T app php tests/test_bi_common_endpoint_contracts.php
    docker compose exec -T app php tests/test_bi_framework_scope_guards.php
    npm --prefix frontend test
    npm --prefix frontend run typecheck
    npx playwright test tests/browser/bi-marco-react.spec.mjs tests/browser/bi-marco-react.a11y.mjs --workers=1

Also run every S17–S24 focused suite named in its current `Cierre`. Expected: PASS, zero legacy
runtime callers, no console/network errors.

### Step 8: Prove prohibited diffs and data state

    git diff -- admin
    git diff -- src/Security/DataScope docs/security database
    git diff -- .env
    git status --short

Expected: only authorized T03-R code/assets/docs; no data/schema/security drift.

### Step 9: Commit and close through PR

Use atomic deletion/manifest commits, integrate latest `origin/main` into the branch, re-run the
gates, publish the exact verified SHA and require green PR CI. Do not deploy.

### Step 10: Record T03-R `Cierre`

Include eight consumer SHAs, zero-caller output, exact deletions, retained vendors with owners,
commands/RCs, visual evidence, rollback, PR and CI.

**Final checkpoint:** every BI route uses the main SPA and no PHP/JS island remains.

## Acceptance Traceability

| Criterion | Task | Evidence |
|---|---:|---|
| T03-AC-001 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-002 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-003 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-004 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-005 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-006 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-007 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-008 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-009 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-010 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-011 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-012 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-013 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-014 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-015 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-016 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-017 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-018 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-019 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-020 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-021 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-022 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-023 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-024 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-025 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-026 | Task 1 | ownership, measured inventory and caller-census characterization |
| T03-AC-027 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-028 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-029 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-030 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-031 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-032 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-033 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-034 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-035 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-036 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-037 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-038 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-039 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-040 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-041 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-042 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-043 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-044 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-045 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-046 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-047 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-048 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-049 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-050 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-051 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-052 | Task 3 | single manifest, canvas and server sheet-policy contracts |
| T03-AC-053 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-054 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-055 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-056 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-057 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-058 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-059 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-060 | Task 4 | minimal BI context endpoint, PHP contract and strict Zod schema |
| T03-AC-061 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-062 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-063 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-064 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-065 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-066 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-067 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-068 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-069 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-070 | Task 6 | shared endpoint stabilization, ownership and compatibility adapters |
| T03-AC-071 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-072 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-073 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-074 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-075 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-076 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-077 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-078 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-079 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-080 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-081 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-082 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-083 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-084 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-085 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-086 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-087 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-088 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-089 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-090 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-091 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-092 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-093 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-094 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-095 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-096 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-097 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-098 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-099 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-100 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-101 | Task 5 | canonical query codec, scope, period, filters and URL behavior |
| T03-AC-102 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-103 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-104 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-105 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-106 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-107 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-108 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-109 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-110 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-111 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-112 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-113 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-114 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-115 | Task 6 | shared endpoint envelopes, lineage, metric and error semantics |
| T03-AC-116 | Task 2 | single cliente.ts typed-error and AbortSignal transport seam |
| T03-AC-117 | Task 2 | single cliente.ts typed-error and AbortSignal transport seam |
| T03-AC-118 | Task 2 | single cliente.ts typed-error and AbortSignal transport seam |
| T03-AC-119 | Task 2 | single cliente.ts typed-error and AbortSignal transport seam |
| T03-AC-120 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-121 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-122 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-123 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-124 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-125 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-126 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-127 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-128 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-129 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-130 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-131 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-132 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-133 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-134 | Task 7 | generation-based remote state, stale-response and cache lifecycle |
| T03-AC-135 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-136 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-137 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-138 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-139 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-140 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-141 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-142 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-143 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-144 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-145 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-146 | Task 8 | single BI frame, route navigation, filters and common states |
| T03-AC-147 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-148 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-149 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-150 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-151 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-152 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-153 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-154 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-155 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-156 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-157 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-158 | Task 9 | drawer, evidence, figure, server-authored action and no-side-effect signal contracts |
| T03-AC-159 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-160 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-161 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-162 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-163 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-164 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-165 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-166 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-167 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-168 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-169 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-170 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-171 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-172 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-173 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-174 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-175 | Task 9 | responsive, dark/light, tokens and accessibility evidence |
| T03-AC-176 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-177 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-178 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-179 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-180 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-181 | Task 10 | scope, SQL/log/cache safety guards and prohibited-diff checks |
| T03-AC-182 | Task 10 | static/frontend/intercepted verification matrix |
| T03-AC-183 | Task 10 | static/frontend/intercepted verification matrix |
| T03-AC-184 | Task 10 | static/frontend/intercepted verification matrix |
| T03-AC-185 | Task 10 | static/frontend/intercepted verification matrix |
| T03-AC-186 | Task 10 | static/frontend/intercepted verification matrix |
| T03-AC-187 | Task 11 | T03-A closure and published consumer seam |
| T03-AC-188 | Task 11 | T03-A closure and published consumer seam |
| T03-AC-189 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-190 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-191 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-192 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-193 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-194 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-195 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |
| T03-AC-196 | Task 12 | T03-R eight-sheet zero-caller retirement and full regression |

## Verification Matrix

| Contract | Evidence |
|---|---|
| ownership | static six-VIEW/eight-route/four-JS/ct-app inventory |
| canvas | pure manifest/policy A all, D/R Obra, hidden others |
| landing | A first/last and D/R Intermedia |
| page/API | identical policy decisions and error ordering |
| context | PHP route/header/shape tests + strict Zod |
| query | backend parser + frontend round-trip/history tests |
| scope | single/multi/denied, project-qualified guards |
| period | week/range/server-date/sheet capability matrix |
| endpoints | projects/weeks/options/lineage/metric contracts |
| transport | one client, typed non-2xx, AbortSignal |
| lifecycle | generations, stale suppression, cache invalidation |
| frame | one outlet/nav/filter/state tree |
| evidence | figure text/data equivalence and drawer stack |
| responsive | 1440, 1180, 768, 480, 390, plus 320/zoom |
| themes | dark/light, tokens, contrast, forced colors |
| accessibility | keyboard/focus/live/reduced-motion/Axe |
| security | policy/scope/order, prepared SQL, safe logs/errors |
| RLS/data | prohibited diffs and no-DML fake-backed tests |
| coexistence | active caller inventory at T03-A |
| retirement | eight SHAs, zero-caller test and full regression |

## Cierre

### T03-A

**Estado de ejecución:** no iniciado. En la sesión autorizada se registrarán:

- SHA inicial y SHA verificado;
- tareas/commits;
- resultados PHP/frontend/browser con códigos;
- canvas/roles/scope;
- oscuro/claro/viewports/Axe;
- censo de convivencia;
- DDL/DML/datos;
- diffs Admin/RLS/database;
- rollback;
- PR/CI/publicación.
### T03-R

**Estado de ejecución:** diferido por diseño hasta que S17–S24 estén publicados. En su ejecución se
registrarán:

- ocho SHAs consumidores;
- censo cero;
- archivos/aliases/vendors retirados o retenidos;
- regresión de ocho hojas;
- oscuro/claro/viewports/Axe;
- DDL/DML/datos;
- diffs Admin/RLS/database;
- rollback;
- PR/CI/publicación.
