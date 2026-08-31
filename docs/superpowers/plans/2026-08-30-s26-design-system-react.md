---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [arquitectura, design-system]
fuente: docs/superpowers/plans/2026-08-30-s26-design-system-react.md
resumen: "migrate /internal/design-system from 13 PHP views/partials and imperative JavaScript to a protected global React laboratory that can open without a selected…"
---

# S26 Design System React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` only in an
> explicitly authorized implementation session. Use `superpowers:test-driven-development` for each
> change and `superpowers:verification-before-completion` before any completion claim. Apply the
> repository front/publication gate in order. Checkbox syntax is an execution prompt; `Cierre` and
> git history are the evidence.

**Goal:** migrate `/internal/design-system` from 13 PHP views/partials and imperative JavaScript to
a protected global React laboratory that can open without a selected project, serves one strict
read-only catalog API, preserves ten families and their governed contracts, represents multiple
approvals truthfully, runs ten synthetic non-admin fixtures, classifies live/retired adapters from
a versioned caller census, and validates dark/light plus responsive layouts without touching RLS,
data, credentials or `/admin/`.

**Architecture:** the existing PHP controller remains the page gate and serves the React index only
after `DesignSystemLabAccessPolicy` returns 200; S26 never becomes a generic `SpaRouter` prefix. A
new internal API applies the same policy, reads nine versioned contract sources through an
injectable loader and returns one deterministic snapshot. React adds an authenticated global outlet
before the project gate, but no product-sidebar entry. A closed registry lazy-loads exactly one of
ten family renderers. Fixtures are local state. Vendor status comes from a SHA-bound census; the lab
loads no vendor assets. The 1.2.0 version promotion is the final atomic gate, not an early edit.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright, native HTML/CSS, existing AIA tokens and versioned JSON
contracts.

**Spec:** `docs/superpowers/specs/2026-08-30-s26-design-system-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or a
  different worktree.
- Inspect status and relevant diff before every task. Preserve all existing work; never clean,
  revert, stage or reformat adjacent paths.
- This session is documentation-only. The commands below are future execution instructions and do
  not authorize implementation, commits, push, PR, publication, deploy or data changes now.
- Start S26 implementation only after T01–T03 and S01–S25 are closed and published according to the
  repository gate. Their final code is the caller-census authority.
- `/admin/` is excluded. Do not edit any path below `admin/` or any Admin controller/view/script,
  do not import AdminLTE and do not use Admin as a fixture consumer.
- Preserve `internal.design-system.view` and current role resolution. Do not add/rename capabilities,
  role aliases or client-side role tables.
- Do not modify RLS, `ProjectScope`, `ProjectSqlGuard`, runtime-boundary rules, schema, migrations,
  tables, columns, indexes, triggers, views, grants, users, credentials, memberships, seeds,
  fixtures or persistent data.
- No DDL/DML, even in rollback transactions. All PHP tests use arrays, temp files, injected readers
  and spies that fail if Database is touched.
- Page and API remain global: no selected project or week is required and neither may influence the
  payload.
- Do not add `/internal/design-system` to `SpaRouter::RUTAS_MIGRADAS`.
- Register page and API only in development/testing, then re-run the shared policy in each
  controller. Outside those environments return 404 without information leakage.
- All productive HTTP calls flow through `frontend/src/lib/api/cliente.ts`. No component, hook or
  family renderer calls `fetch`.
- Every endpoint response and error is parsed by strict Zod; every new endpoint has a PHP contract.
- No mutation endpoint, polling, automatic retry or client cache outside memory.
- Runtime request handling never scans git/source callers. Generate
  `adapter-caller-census.json` before closure and read that contract at runtime.
- The census scope is productive non-admin manifests/sources. Lab/demo/test/self references do not
  count as callers.
- Do not load vendor JS/CSS in the React laboratory, even for an adapter classified active.
- Fixtures are synthetic, local, deterministic and state explicitly that they do not save.
- Dark is fallback/default; light is equivalent. Do not restore `linen`.
- Validate 390×844, 768×1024, 1180×820 and 1440×900 in both themes, plus 320 px/200% reflow.
- Touch is forced at ≤1180; Compact is default only above 1180.
- Use `public/css/tokens.css` and shared T01 primitives. New S26 sources have zero literal colors,
  inline styles, `!important`, CSS-in-JS and vendor classes.
- Keep current legacy goldens and PHP runtime during development. Never run snapshot update flags.
  New images remain untracked candidates until explicit human approval.
- Do not “fix” the pre-existing broad Biome debt as part of S26. Add an exact zero-budget check for
  new S26 files and keep the existing static suite green.
- Rollback changes code/routes/contracts only. It never touches data.
- Do not retire a source or asset until its exact non-admin caller census is zero.
- Future commits are atomic per checkpoint. This documentation session makes none.

## Dependency Gate

Before Task 1 in the future implementation session:

1. Read `Cierre` for T01, T02, T03 and S01–S25; do not infer completion from checkboxes.
2. Verify T01 exposes:
   - authenticated session before project selection;
   - dark/light bootstrap and one theme switch;
   - `cliente.ts` with AbortSignal/error support;
   - AppShell/route outlet and focus primitives.
3. Verify T02 overlays and T03 BI primitives are canonical and no S26 copy is necessary.
4. Verify each S01–S25 manifest lists its remaining vendor dependencies accurately.
5. Verify exact environment once:

       pwd
       git branch --show-current
       git status --short
       docker compose config --services
       docker compose ps

   Expected: exact worktree/branch; `app`, `db` and `adminer` available; app mount points to this
   worktree.
6. Record starting SHA and all pre-existing changed paths. Never stage them accidentally.
7. Run the current read-only baseline:

       docker compose exec -T app php tests/test_design_system_lab_access.php
       node --test tests/design-system/operational-fixtures.test.mjs tests/design-system/laboratory-hardening.test.mjs tests/design-system/ui-groups-inventory.test.mjs
       npm run test:design-system:static

   Expected: access PASS, focused 12/12 PASS and static suite PASS.
8. Record that `npm run check:design-system:biome` is pre-existing red; do not claim it green or
   reformat unrelated files.
9. Confirm `git diff -- admin` is empty and establish an end-of-task guard for that invariant.
10. Inspect every planned browser/PHP test for write verbs, cleanup hooks and database helpers.
11. Confirm dev door is enabled for the authorized test user before browser work; do not use
    `/login`.
12. If a dependency/census input is missing, stop S26 and complete its owning front. Do not create a
    temporary vendor bridge or duplicate component.

## File Structure

### Create — backend

- `src/Services/DesignSystem/CatalogSourceReader.php` — injectable source interface.
- `src/Services/DesignSystem/FilesystemCatalogSourceReader.php` — bounded relative-path reader.
- `src/Services/DesignSystem/DesignSystemCatalogException.php` — typed fail-closed error.
- `src/Services/DesignSystem/DesignSystemCatalogLoader.php` — parse/schema/reference inputs.
- `src/Services/DesignSystem/DesignSystemCatalogService.php` — multimap approvals, filtering and
  composition.
- `src/Services/DesignSystem/DesignSystemCatalogPresenter.php` — deterministic public shape.
- `src/Controllers/Internal/DesignSystemCatalogController.php` — GET endpoint.
- `scripts/design-system-adapter-census.mjs` — deterministic non-admin census generator/checker.
- `docs/design-system/adapter-caller-census.schema.json`.
- `docs/design-system/adapter-caller-census.json`, generated at the closure SHA.

### Create — frontend API/domain/state

- `frontend/src/modules/design-system/api/esquemas.ts` and test.
- `frontend/src/modules/design-system/api/catalogo.ts` and test.
- `frontend/src/modules/design-system/dominio/familias.ts` and test.
- `frontend/src/modules/design-system/dominio/candidatos.ts` and test.
- `frontend/src/modules/design-system/dominio/densidad.ts` and test.
- `frontend/src/modules/design-system/estado/useCatalogoDesignSystem.ts` and test.
- `frontend/src/modules/design-system/estado/useFamiliaUrl.ts` and test.

### Create — frontend shell/components

- `frontend/src/modules/design-system/componentes/InternalShell.tsx`.
- `frontend/src/modules/design-system/componentes/CabeceraLaboratorio.tsx`.
- `frontend/src/modules/design-system/componentes/RailFamilias.tsx`.
- `frontend/src/modules/design-system/componentes/EstadoCandidato.tsx`.
- `frontend/src/modules/design-system/componentes/ListaCandidatos.tsx`.
- `frontend/src/modules/design-system/componentes/IndiceGruposUi.tsx`.
- `frontend/src/modules/design-system/componentes/FixtureOperacional.tsx`.
- `frontend/src/modules/design-system/componentes/LedgerAdapters.tsx`.
- `frontend/src/modules/design-system/componentes/EstadoLaboratorio.tsx`.
- `frontend/src/modules/design-system/PaginaDesignSystem.tsx` and test.
- `frontend/src/modules/design-system/design-system.css`.

### Create — ten family renderers

- `frontend/src/modules/design-system/familias/registro.ts` and test.
- `frontend/src/modules/design-system/familias/fundamentos.tsx`.
- `frontend/src/modules/design-system/familias/navegacion.tsx`.
- `frontend/src/modules/design-system/familias/estructura.tsx`.
- `frontend/src/modules/design-system/familias/acciones.tsx`.
- `frontend/src/modules/design-system/familias/formularios.tsx`.
- `frontend/src/modules/design-system/familias/estados.tsx`.
- `frontend/src/modules/design-system/familias/datos.tsx`.
- `frontend/src/modules/design-system/familias/overlays.tsx`.
- `frontend/src/modules/design-system/familias/adapters.tsx`.
- `frontend/src/modules/design-system/familias/bi.tsx`.
- colocated tests for each renderer or one exhaustive `familias.test.tsx`.

Exact legacy disposition:

| Piece | Legacy source | React owner |
|---|---|---|
| VIEW-13 | `views/design-system/families/actions.php` | `familias/acciones.tsx` |
| VIEW-14 | `views/design-system/families/bi-primitives.php` | `familias/bi.tsx` |
| VIEW-15 | `views/design-system/families/data-display.php` | `familias/datos.tsx` |
| VIEW-16 | `views/design-system/families/forms-filters.php` | `familias/formularios.tsx` |
| VIEW-17 | `views/design-system/families/foundations.php` | `familias/fundamentos.tsx` |
| VIEW-18 | `views/design-system/families/overlays.php` | `familias/overlays.tsx` |
| VIEW-19 | `views/design-system/families/page-structure.php` | `familias/estructura.tsx` |
| VIEW-20 | `views/design-system/families/shell-navigation.php` | `familias/navegacion.tsx` |
| VIEW-21 | `views/design-system/families/states-feedback.php` | `familias/estados.tsx` |
| VIEW-22 | `views/design-system/families/vendor-adapters.php` | `familias/adapters.tsx` |
| VIEW-23 | `views/design-system/lab.view.php` | `PaginaDesignSystem.tsx` |
| VIEW-24 | `views/design-system/operational-fixtures.php` | `FixtureOperacional.tsx` + fixture registry |
| VIEW-25 | `views/design-system/ui-group-index.php` | `IndiceGruposUi.tsx` |

### Create — fixtures

- `frontend/src/modules/design-system/fixtures/registro.ts` and test.
- one renderer per non-admin fixture under
  `frontend/src/modules/design-system/fixtures/`:
  `selector-proyecto.tsx`, `credenciales.tsx`, `semana.tsx`, `grilla.tsx`,
  `tabla-legacy.tsx`, `notificaciones.tsx`, `drawer-lps.tsx`, `bi-drilldown.tsx`,
  `selector-avanzado.tsx` and `fecha.tsx`.

### Create — tests/evidence contracts

- `tests/test_design_system_react_access.php`.
- `tests/test_design_system_catalog_loader.php`.
- `tests/test_design_system_catalog_service.php`.
- `tests/test_api_design_system_catalog_contract.php`.
- `tests/test_design_system_catalog_no_database.php`.
- `tests/design-system/adapter-caller-census.test.mjs`.
- `tests/design-system/design-system-react-contract.test.mjs`.
- `tests/browser/fixtures/design-system-react.mjs`.
- `tests/browser/design-system-react.spec.mjs`.
- `tests/browser/design-system-react.a11y.mjs`.
- `tests/browser/design-system-react.performance.mjs`.
- `tests/browser/design-system-react.visual.mjs` only for untracked candidate captures.

### Modify — integration/contracts

- `src/Controllers/Internal/DesignSystemLabController.php` — keep policy, switch body to React host
  only at Task 11.
- `public/index.php` — add internal API route inside the existing environment gate.
- `frontend/src/shell/rutas.tsx` and test — exact authenticated internal outlet before project gate.
- `frontend/src/lib/api/cliente.ts` and test only for missing shared Abort/error semantics.
- `frontend/src/main.tsx`/`App.tsx` only if route composition requires it.
- `docs/design-system/ui-groups-inventory.json` and schema — dark/light for non-admin groups; Admin
  source remains outside S26.
- `docs/design-system/homologation.json` and applicable schema/gate — supported/required layouts.
- `docs/design-system/manifests/laboratory.json` — React sources, no vendors, approved matrix.
- `docs/design-system/manifests/inventory.json`.
- `docs/design-system/decisions.md` and `CHANGELOG.md`.
- `docs/design-system/version.json` and every live document enumerated by the version gate only at
  Task 11; historical measurements remain untouched.
- `scripts/design-system-contracts.mjs` and `scripts/design-system-static-suite.mjs`.
- focused design-system tests that currently encode PHP, dark-only or Admin fixture output.
- root/frontend package scripts only to expose focused gates.

### Preserve

- `src/Security/DesignSystemLabAccessPolicy.php` semantics and
  `RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW`.
- all RLS/runtime-boundary/database code and data.
- all historical runtime measurements and release history; the stable 1.0.0 API guarantee remains
  semantically identical even when its live `designSystemVersion` envelope advances.
- T01–T03 and S01–S25 component ownership.
- every path under `admin/`.
- `public/css/design-system/adapters/admin-lte.css` and Admin vendor assets.
- existing goldens until explicit approval.

### Retire only after post-canonical gate

- `views/design-system/families/*.php` (VIEW-13–22).
- `views/design-system/lab.view.php` (VIEW-23).
- `views/design-system/operational-fixtures.php` (VIEW-24).
- `views/design-system/ui-group-index.php` (VIEW-25).
- `public/js/modules/aia_ui/design_system_lab.js`.
- `public/css/design-system/lab.css`, `lab-entrypoint.css` and
  `laboratory-foundation.css` only if caller census proves exclusive.
- PHP-only browser/static assertions after equivalent React coverage.

## Task 1: Lock the environment, capability and page-frontier contract

**Files:**
- Create `tests/test_design_system_react_access.php`.
- Inspect `src/Security/DesignSystemLabAccessPolicy.php`,
  `src/Controllers/Internal/DesignSystemLabController.php`, `public/index.php` and
  `src/Core/SpaRouter.php`.
- Modify policy code only if a characterization test exposes a real mismatch.

**Goal:** freeze 404/403/200, global role resolution, authentication, no-project behavior and the
rule that S26 never becomes a generic SPA prefix.

- [ ] **Step 1: Add pure access characterization**

Cover production/unknown 404, dev/testing denied 403, allowed 200, exact existing capability,
fallback role, no selected project/week dependency and zero new capability keys.

Run:

    docker compose exec -T app php tests/test_design_system_react_access.php

Expected: PASS for preserved behavior; any failure is an audit discrepancy to resolve before
continuing.

- [ ] **Step 2: Add source-boundary assertions**

Assert route registration remains inside `allowsInternalTools()`, `SpaRouter::RUTAS_MIGRADAS` does
not contain S26, SessionMiddleware treats it as authenticated and no `/admin/` path is referenced.

Run the same command. Expected: PASS.

- [ ] **Step 3: Add a shared-policy seam for the future API only if needed**

Reuse `DesignSystemLabAccessPolicy` directly; do not create a second permission policy and do not
change role aliases.

- [ ] **Step 4: Record Admin guard**

Capture:

    git diff --name-only -- admin

Expected: empty.

Future commit after this checkpoint, if files changed:

    git add tests/test_design_system_react_access.php
    git commit -m "test(design-system): lock internal React access boundary"

**Vertical checkpoint:** access/security behavior is frozen before any catalog or React code.

## Task 2: Add fail-closed source contracts and a SHA-bound adapter census

**Files:**
- Create backend reader/loader/exception files.
- Create census script, schema, JSON and tests.
- Modify design-system contract runner to validate the new schema/document pair.

**Goal:** every catalog source is bounded, parseable and cross-referenceable; caller status is
versioned and never calculated during an HTTP request.

- [ ] **Step 1: Write failing loader tests**

Use a temp directory/in-memory reader. Cover missing file, malformed JSON, wrong schema version,
unknown family/candidate/component/group/fixture, missing approved candidate, duplicate ID,
absolute/traversal path and sanitized exception.

Run:

    docker compose exec -T app php tests/test_design_system_catalog_loader.php

Expected: FAIL because loader types do not exist.

- [ ] **Step 2: Implement the minimum bounded reader and loader**

Read only the nine allowlisted relative files. No arbitrary filename comes from query. Do not touch
Database.

Run the PHP test. Expected: PASS.

- [ ] **Step 3: Write failing census tests**

Require deterministic order, `schemaVersion:1`, `scope:"product-non-admin"`, 40-char sourceRef,
vendor IDs, caller IDs, evidence manifests, active/retired status, zero Admin entries and
`assetsLoadedByLab:false`.

The generator must:

- read governed module manifests;
- exclude laboratory/test/demo/history and every Admin route/module;
- cross-check productive non-admin source references against declared vendors;
- fail on an undeclared caller;
- emit no `generatedAt`.

Run:

    node --test tests/design-system/adapter-caller-census.test.mjs

Expected: FAIL before script/schema exist.

- [ ] **Step 4: Implement generator and check mode**

Provide `generate` for an authorized closure update and `check` for CI. Generate against the current
candidate SHA only after S01–S25 closure; during development use a test fixture artifact.

- [ ] **Step 5: Wire contract validation**

Run:

    node scripts/design-system-adapter-census.mjs check
    npm run test:design-system:static

Expected: census PASS and static suite PASS.

Future commit:

    git add src/Services/DesignSystem scripts/design-system-adapter-census.mjs docs/design-system/adapter-caller-census.schema.json docs/design-system/adapter-caller-census.json tests/test_design_system_catalog_loader.php tests/design-system/adapter-caller-census.test.mjs scripts/design-system-contracts.mjs
    git commit -m "feat(design-system): validate catalog sources and adapter census"

**Vertical checkpoint:** catalog inputs and caller truth exist without HTTP, React or runtime scans.

## Task 3: Compose the canonical catalog without collapsing approvals

**Files:**
- Create `DesignSystemCatalogService.php` and `DesignSystemCatalogPresenter.php`.
- Create `tests/test_design_system_catalog_service.php` and no-Database test.
- Use loaded authorities from Task 2.

**Goal:** create the exact deterministic snapshot, preserving all approvals and filtering Admin only
at the S26 presentation boundary.

- [ ] **Step 1: Write failing composition tests**

Assert:

- exactly ten families in source order;
- exact active candidate/null;
- all 12 approval records retained;
- two approved IDs for shell and states;
- active and approved modes;
- family status derivation;
- 29 components classified;
- all 87 source groups accounted for as included/excluded;
- API output excludes AdminLTE/admin-operations/admin consumers;
- ten output fixtures;
- dead PDC v1/Contratos/Listado consumers removed;
- vendor ledger matches census;
- sourceHash deterministic under key/order normalization;
- no path/session/project/week fields.

Run:

    docker compose exec -T app php tests/test_design_system_catalog_service.php

Expected: FAIL.

- [ ] **Step 2: Implement multimap and status rules**

Never assign `familyId → last candidate`. Produce arrays and derive `approved/candidate/mixed/
reference-only` exactly as the spec states.

- [ ] **Step 3: Implement output filtering**

Filter Admin at composition/presentation, leaving source documents available to their existing
non-S26 governance. Remove stale consumers only from S26 output unless their source contract is
separately authorized for correction.

- [ ] **Step 4: Prove no database**

Inject a reader and a Database spy/forbidden autoload path. Run:

    docker compose exec -T app php tests/test_design_system_catalog_no_database.php

Expected: PASS with zero Database/ProjectScope calls.

- [ ] **Step 5: Verify deterministic hashes**

Permute object key order in fixtures and prove the canonical hash remains stable while a semantic
value change changes it.

Future commit:

    git add src/Services/DesignSystem/DesignSystemCatalogService.php src/Services/DesignSystem/DesignSystemCatalogPresenter.php tests/test_design_system_catalog_service.php tests/test_design_system_catalog_no_database.php
    git commit -m "feat(design-system): compose truthful catalog snapshot"

**Vertical checkpoint:** one pure snapshot represents every family, approval, group, fixture and
adapter without Admin or data access.

## Task 4: Expose the protected read-only catalog endpoint

**Files:**
- Create `src/Controllers/Internal/DesignSystemCatalogController.php`.
- Create `tests/test_api_design_system_catalog_contract.php`.
- Modify `public/index.php` inside the existing internal-tools block.

**Goal:** one GET returns the strict snapshot with the same environment/capability policy as the
page and no mutation surface.

- [ ] **Step 1: Write failing controller/HTTP tests**

Cover page/API status parity, GET-only route, family/mode/fixture allowlist, unknown query 400,
legacy fixture alias, content type, no-store, Vary, 500 mapping, requestId, no stack/path and no body
authorization.

Run:

    docker compose exec -T app php tests/test_api_design_system_catalog_contract.php

Expected: FAIL because controller/route do not exist.

- [ ] **Step 2: Implement controller with injected service**

Do not read files in the controller. Normalize query once, call policy before loader, and catch only
typed catalog exceptions before generic failure mapping.

- [ ] **Step 3: Register route safely**

Place `GET /api/internal/design-system/catalog` inside `allowsInternalTools()`. Do not add it to
public routes and do not add any mutation method.

- [ ] **Step 4: Verify status and no reads on denial**

Spies must prove loader call count zero for 401/403/404 paths.

- [ ] **Step 5: Run focused and existing access tests**

    docker compose exec -T app php tests/test_design_system_lab_access.php
    docker compose exec -T app php tests/test_design_system_react_access.php
    docker compose exec -T app php tests/test_api_design_system_catalog_contract.php

Expected: all PASS.

Future commit:

    git add src/Controllers/Internal/DesignSystemCatalogController.php public/index.php tests/test_api_design_system_catalog_contract.php
    git commit -m "feat(design-system): expose protected catalog snapshot"

**Vertical checkpoint:** HTTP contract is complete, read-only and security-equivalent before UI.

## Task 5: Add strict Zod, gateway and the authenticated global outlet

**Files:**
- Create frontend API/domain/state files and tests.
- Modify `frontend/src/shell/rutas.tsx` and its test.
- Extend `cliente.ts` only if Task dependency lacks Abort/error metadata.

**Goal:** S26 can load before project selection, while every other module remains project-gated.

- [ ] **Step 1: Write failing Zod/gateway tests**

Test exact success shape, all error codes, ten-family closed IDs, candidate/group/fixture/vendor
nested shapes, extra-field rejection, malformed payload, AbortSignal forwarding, one request,
manual retry and stale completion suppression.

Run:

    npm --prefix frontend test -- design-system/api design-system/estado

Expected: FAIL.

- [ ] **Step 2: Implement schemas and gateway**

Only `catalogo.ts` calls `pedir`; no direct fetch. Reuse shared error and cancellation behavior.

- [ ] **Step 3: Write failing route-order tests**

Cases:

- unauthenticated S26 → login;
- authenticated authorized path without project → S26 page;
- authenticated non-S26 path without project → selector;
- normal project path → AppShell;
- no S26 product-sidebar item;
- exact path only; prefix/lookalike does not bypass.

Run:

    npm --prefix frontend test -- shell/rutas

Expected: FAIL before the outlet exists.

- [ ] **Step 4: Implement exact internal outlet**

Branch after authentication and before project selection. Server remains authority; client
capability may improve UX but cannot grant access. Do not create a second session fetch.

- [ ] **Step 5: Verify typecheck**

    npm --prefix frontend run typecheck

Expected: PASS.

Future commit:

    git add frontend/src/modules/design-system/api frontend/src/modules/design-system/dominio frontend/src/modules/design-system/estado frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts
    git commit -m "feat(frontend): add global internal design-system outlet"

**Vertical checkpoint:** typed catalog state can render without a project and cannot broaden other
routes.

## Task 6: Build the internal shell, closed registry and URL/history behavior

**Files:**
- Create page, InternalShell, header, rail, state components, registry and tests.
- Create initial `design-system.css` using tokens only.

**Goal:** an accessible page can navigate ten lazy families, but only a placeholder renderer is used
until Tasks 7–9.

- [ ] **Step 1: Write failing registry tests**

Require exactly ten known IDs, one lazy import per ID, no Admin ID, no duplicate and hard failure for
unknown/missing renderer.

- [ ] **Step 2: Write failing URL/focus tests**

Cover initial normalization, invalid replace, click push, back/forward, `aria-current`, h1 focus,
mobile disclosure and no duplicate sidebar/router.

- [ ] **Step 3: Implement shell and registry**

Use shared account/theme controls. No product navigation groups. Mount only active family inside
`Suspense` with an accessible loading state.

- [ ] **Step 4: Implement density state**

Use matchMedia: ≤1180 Touch; >1180 Compact default with Touch override. Do not persist.

- [ ] **Step 5: Run tests and zero-budget style check**

    npm --prefix frontend test -- design-system/PaginaDesignSystem design-system/dominio/familias
    npx biome check frontend/src/modules/design-system frontend/src/shell/rutas.tsx

Expected: PASS for new scope only.

Future commit:

    git add frontend/src/modules/design-system/PaginaDesignSystem.tsx frontend/src/modules/design-system/PaginaDesignSystem.test.tsx frontend/src/modules/design-system/componentes frontend/src/modules/design-system/familias/registro.ts frontend/src/modules/design-system/familias/registro.test.ts frontend/src/modules/design-system/design-system.css
    git commit -m "feat(design-system): add accessible React laboratory shell"

**Vertical checkpoint:** routing/navigation works end-to-end with a closed family boundary.

## Task 7: Port shared specimens, UI index and Foundations first

**Files:**
- Create shared candidate/index/specimen components.
- Create `familias/fundamentos.tsx` and tests.
- Adapt catalog data, never hardcode the source values.

**Goal:** produce the first useful vertical family from API to semantic React specimens.

- [ ] **Step 1: Write failing candidate/index tests**

Prove active candidate vs approved references, two-approved rendering, candidate badges, 29
component maturity values, UI-group source/evidence and no edit controls.

- [ ] **Step 2: Write failing Foundations tests**

Cover brand roles, Montserrat/Inter, semantic color/spacing/radius/elevation/focus tokens, labels and
resolved values in both themes without literal colors.

- [ ] **Step 3: Implement shared renderers and Foundations**

Swatch presentation may use CSS custom properties referenced by an allowlisted token name; never
place raw color values into inline style. Prefer a class/data-token mapping generated from a closed
registry.

- [ ] **Step 4: Add semantic source contract**

Run:

    npm --prefix frontend test -- design-system/componentes design-system/familias/fundamentos
    npx biome check frontend/src/modules/design-system

Expected: PASS.

Future commit:

    git add frontend/src/modules/design-system/componentes frontend/src/modules/design-system/familias/fundamentos.tsx frontend/src/modules/design-system/familias/fundamentos.test.tsx
    git commit -m "feat(design-system): port foundations and catalog specimens"

**Vertical checkpoint:** one family is fully useful, typed, truthful and theme-aware.

## Task 8: Port the remaining nine family renderers

**Files:**
- Create the remaining renderer files/tests.
- Reuse T01–T03/shared product components wherever they exist.

**Goal:** preserve every observable family specimen without copying legacy frameworks.

- [ ] **Step 1: Write a parity matrix test**

Map VIEW-13–22 to renderer IDs and required specimen assertions. Fail if any required behavior has
no semantic element.

- [ ] **Step 2: Implement Actions, Navigation and Page Structure**

Actions: primary/secondary/destructive/loading/disabled.
Navigation: sidebar/context/week/notifications, no Admin.
Structure: header/canvas/sections/project selector.

- [ ] **Step 3: Implement Forms and States**

Forms: synthetic auth, filters, date, enriched select, validation states.
States: levels, hues, text/icon, all approved candidates, semantic table.

- [ ] **Step 4: Implement Data and Overlays**

Data: one collection, desktop table/touch cards, sort/pagination/local states.
Overlays: modal/drawer, focus trap, inert, Escape and return.

- [ ] **Step 5: Implement BI**

Native SVG/HTML only; include title, summary, legend, data table and local drilldown/load-more.

- [ ] **Step 6: Leave Adapters to Task 9**

Renderer may display a loading contract but cannot invent census results.

- [ ] **Step 7: Run component suite**

    npm --prefix frontend test -- design-system/familias
    npm --prefix frontend run typecheck

Expected: PASS.

Future commit:

    git add frontend/src/modules/design-system/familias
    git commit -m "feat(design-system): port governed family specimens"

**Vertical checkpoint:** all non-adapter families preserve their contracts without vendor runtime.

## Task 9: Port ten local fixtures and the adapter ledger, excluding Admin

**Files:**
- Create fixture registry/renderers/tests.
- Finish `familias/adapters.tsx` and `LedgerAdapters.tsx`.
- Use server-filtered consumers and census.

**Goal:** replace VIEW-24/22 behavior with honest local demos and prove that Admin/vendor assets are
absent.

- [ ] **Step 1: Write failing fixture-registry tests**

Require exactly ten IDs, every source non-admin fixture classified, default+error, declared states,
local state isolation, `aria-pressed`, live region and “no guarda”.

- [ ] **Step 2: Add explicit exclusion tests**

Assert output/DOM/source requests omit `admin-operations`, `admin-auth`, AdminLTE and Admin
consumers; stale PDC v1, Contratos and Listado consumers are absent.

- [ ] **Step 3: Implement fixtures as React state**

No fetch, storage, timer-dependent success or production-looking data. Replace autosave timer with a
deterministic state transition controlled by testable events; if delay is kept for feedback, use
fake timers and reduced-motion-safe behavior.

- [ ] **Step 4: Implement adapter ledger**

Display active/retired/foundation, caller count and note. Active still renders a semantic canonical
specimen; retired renders documentation only. `assetsLoadedByLab` is always false.

- [ ] **Step 5: Add network/static guards**

Test bundle/source for vendor imports and browser allowlist for requests. Do not remove physical
assets yet.

- [ ] **Step 6: Run focused suites**

    npm --prefix frontend test -- design-system/fixtures design-system/familias/adapters
    node --test tests/design-system/adapter-caller-census.test.mjs

Expected: PASS.

Future commit:

    git add frontend/src/modules/design-system/fixtures frontend/src/modules/design-system/familias/adapters.tsx frontend/src/modules/design-system/componentes/LedgerAdapters.tsx
    git commit -m "feat(design-system): port local fixtures and adapter ledger"

**Vertical checkpoint:** all runtime demos are local, non-admin and vendor-free.

## Task 10: Close theme, responsive, accessibility, performance and evidence contracts

**Files:**
- Modify S26 CSS/components.
- Modify `DesignSystemLabController.php` only after unit/build gates to expose the local React
  candidate; keep every legacy source for immediate code rollback.
- Update non-admin UI-group theme metadata and schemas.
- Create/retarget browser a11y/performance tests.
- Update laboratory manifest only after evidence approval.

**Goal:** make dark/light and mobile/tablet/desktop/wide contractually equivalent without laundering
new snapshots.

- [ ] **Step 1: Add failing responsive/theme tests**

Cover the eight required viewport/theme combinations, 320 px, 200% zoom, no page overflow, Touch/
Compact rules, 44px touch targets and identical information/actions.

- [ ] **Step 2: Update non-admin theme contracts**

Change the 86 non-admin groups to dark+light only after their specimens pass. Leave AdminLTE source
outside S26. Update schema from dark const to a closed allowed/required theme rule.

- [ ] **Step 3: Add accessibility gates**

Axe serious/critical zero, landmarks, one h1, skip link, focus order, history focus, overlays,
tables, charts, live regions, no color-only meaning, no duplicate IDs and reduced motion.

- [ ] **Step 4: Add performance gate**

Three cold samples at 1180 and 1440; add JS/chunk measures; assert one catalog request, one mounted
family, no vendor assets, CLS ≤0.1 and long-task/resource budgets. Preserve exact SHA/diff/untracked
provenance.

- [ ] **Step 5: Expose the React candidate in this feature branch**

After frontend build/unit/static gates pass, make the protected controller serve the React index.
Do not delete PHP/JS/CSS, bump the version or publish the branch. Origin/main remains the legacy
canonical runtime during this candidate review.

- [ ] **Step 6: Produce untracked visual candidates**

Never use `--update-snapshots`. Capture dark/light and responsive candidates to an ignored evidence
directory, then stop for explicit human visual approval.

- [ ] **Step 7: After approval, update manifest/hashes atomically**

Keep old goldens until replacement approval. Record decision IDs and exact capture semantics. If
approval is withheld, S26 remains incomplete; do not fake evidence.

- [ ] **Step 8: Run focused gates**

    npm --prefix frontend test -- design-system
    npm --prefix frontend run typecheck
    npx playwright test tests/browser/design-system-react.a11y.mjs --workers=1
    npx playwright test tests/browser/design-system-react.performance.mjs --workers=1
    npm run test:design-system:static

Expected: all PASS except visual replacement remains blocked until explicit approval.

Future commit after approval:

    git add src/Controllers/Internal/DesignSystemLabController.php frontend/src/modules/design-system docs/design-system/ui-groups-inventory.json docs/design-system/ui-groups-inventory.schema.json docs/design-system/manifests/laboratory.json tests/browser/design-system-react.a11y.mjs tests/browser/design-system-react.performance.mjs tests/design-system
    git commit -m "feat(design-system): validate React candidate across themes"

**Vertical checkpoint:** S26 is accessible, responsive, theme-complete and measurable before route
cut.

## Task 11: Cut the protected page to React, promote 1.2.0 and retire exclusive legacy sources

**Files:**
- Modify `DesignSystemLabController.php` to serve `public/app/index.html` after policy 200.
- Update manifest/inventory/decisions/changelog/current versioned contracts.
- Retire VIEW-13–25 and exclusive JS/CSS only after census.
- Preserve all Admin paths/assets.

**Goal:** make React canonical with an atomic, reversible code cut and no orphaned callers.

- [ ] **Step 1: Write failing final route tests**

Prove production 404 before index, denied 403 before index, authorized 200 React host, no project
required, deep-link refresh, API parity, no generic SPA prefix and no sidebar item.

- [ ] **Step 2: Confirm the canonical controller body**

Keep the Task 10 candidate body: require the built React index only after the policy. Remove any
temporary preview seam if one was introduced during local diagnosis; the final controller has one
authorized body and no query/flag that can select the legacy renderer.

- [ ] **Step 3: Generate final census on candidate SHA**

    node scripts/design-system-adapter-census.mjs generate
    node scripts/design-system-adapter-census.mjs check

Review every active/retired row. Do not accept self/test/Admin as callers.

- [ ] **Step 4: Run caller census before each deletion**

For VIEW-13–25, JS and CSS, prove zero exclusive references. Remove only those paths. If CSS is
shared, keep it and record owner; do not force removal.

- [ ] **Step 5: Promote current contracts to 1.2.0 atomically**

Update:

- `version.json` to stable 1.2.0;
- current homologation/catalog/approvals/state/groups/vendors/census contracts that carry the live
  design-system version;
- every live document and current module manifest/inventory enumerated by
  `scripts/design-system-contracts.mjs` after S01–S25 closure;
- `CHANGELOG.md` and new decisions for React, themes, responsive, approval multimap, Admin exclusion
  and caller-governed adapters.

Update only the `designSystemVersion` envelope in `stable-api-1.0.0.json`; keep its target version,
guarantee and component list unchanged. Do not rewrite historical runtime measurements or old
release artifacts. In `closeout-evidence.json`, never alter a receipt's status/date/hash/source to
make it appear current; add S26 evidence only after the real command succeeds. Create
`baseline-approvals/1.2.0-carry-forward.json` only when audit-baseline before/after hashes are
identical and an explicit approval reference exists; otherwise stop the promotion.

Run the version gate immediately:

    npm run test:design-system:static

Expected: PASS with no `designSystemVersion must equal` mismatch.

- [ ] **Step 6: Guard Admin and data boundaries**

    git diff --name-only -- admin
    git diff --check

Expected: Admin output empty; diff check PASS.

- [ ] **Step 7: Run route/static/frontend suites**

    docker compose exec -T app php tests/test_design_system_react_access.php
    docker compose exec -T app php tests/test_api_design_system_catalog_contract.php
    npm --prefix frontend run build
    npm --prefix frontend test -- design-system
    npm run test:design-system:static

Expected: PASS.

Future commit:

    git add -A -- src/Controllers/Internal/DesignSystemLabController.php public/index.php docs/design-system/version.json docs/design-system/CHANGELOG.md docs/design-system/decisions.md docs/design-system/adapter-caller-census.json docs/design-system/component-catalog.json docs/design-system/family-approvals.json docs/design-system/homologation.json docs/design-system/state-semantics.json docs/design-system/ui-groups-inventory.json docs/design-system/vendors.json docs/design-system/a11y-baseline.json docs/design-system/a11y-exceptions.json docs/design-system/evidence-exceptions.json docs/design-system/legacy-aliases.json docs/design-system/stable-api-1.0.0.json docs/design-system/closeout-evidence.json docs/design-system/baseline-approvals/1.2.0-carry-forward.json docs/design-system/manifests/laboratory.json docs/design-system/manifests/inventory.json views/design-system public/js/modules/aia_ui/design_system_lab.js public/css/design-system/lab.css public/css/design-system/lab-entrypoint.css public/css/design-system/laboratory-foundation.css
    git diff --cached --name-only
    git commit -m "feat(design-system): cut protected laboratory to React"

Before this fixed-path staging call, print the final inventory-derived live manifest list, review it
and stage those manifest paths explicitly in a separate `git add` call. Never use command
substitution, a broad directory add or an unresolved glob. Before committing, unstage any unrelated
or Admin path; the staged list must match the gate's live documents exactly.

**Vertical checkpoint:** canonical protected route is React, versioning is coherent and every
retirement is census-backed.

## Task 12: Run the intercepted browser matrix and prepare repository closure

**Files:**
- Finish browser fixtures/spec.
- Update plan `Cierre` only after all evidence.
- Do not create or publish artifacts in this planning session.

**Goal:** prove the served application, not just components, and hand the exact verified SHA to the
repository PR/CI gate.

- [ ] **Step 1: Create synthetic intercepted fixtures**

Catalog includes ten families, multiple approvals, ten fixtures and adapter statuses. Fail any
unexpected API method/URL or vendor asset request. Never use real names/projects/credentials.

- [ ] **Step 2: Prove browser preconditions**

Use dev door. Before each scenario assert response 200, exact route, authenticated session, h1 and
family rail. A login page, 403, 404 or project selector is a hard failure, not evidence.

- [ ] **Step 3: Run functional matrix**

    npx playwright test tests/browser/design-system-react.spec.mjs --workers=1

Cover no-project access, URL/history, ten families, two-approval families, ten fixtures, Admin
absence, local states, adapter ledger, dark/light, density and zero console/page errors.

- [ ] **Step 4: Run accessibility/performance**

    npx playwright test tests/browser/design-system-react.a11y.mjs tests/browser/design-system-react.performance.mjs --workers=1

Expected: PASS.

- [ ] **Step 5: Run full focused-to-broad verification in separate commands**

    docker compose exec -T app php tests/test_design_system_lab_access.php
    docker compose exec -T app php tests/test_design_system_react_access.php
    docker compose exec -T app php tests/test_design_system_catalog_loader.php
    docker compose exec -T app php tests/test_design_system_catalog_service.php
    docker compose exec -T app php tests/test_api_design_system_catalog_contract.php
    docker compose exec -T app php tests/test_design_system_catalog_no_database.php
    node --test tests/design-system/adapter-caller-census.test.mjs tests/design-system/design-system-react-contract.test.mjs
    npm --prefix frontend run test
    npm --prefix frontend run typecheck
    npm --prefix frontend run build
    npm run test:design-system:static
    git diff --check
    git diff --name-only -- admin
    git status --short

Read and record each return code separately. Do not chain publication after verification.

- [ ] **Step 6: Verify trace and source integrity**

Assert 150 unique spec criteria and 150 one-to-one plan rows; verify every VIEW-13–25 disposition,
every catalog source and every excluded Admin object.

- [ ] **Step 7: Apply the branch closure gate only when explicitly executing**

After local green and atomic commits:

- fetch origin and inspect divergence;
- merge origin/main into this branch if required;
- re-run verification on the integrated SHA;
- push the feature branch;
- open PR to main;
- require CI green;
- confirm merged SHA on origin/main;
- deploy only with separate explicit authorization.

No commit, push, PR, publication or deploy occurs in this documentation session.

**Vertical checkpoint:** browser evidence is real, safe and tied to the exact candidate SHA.

## Acceptance Traceability

| Criterion | Owner | Primary evidence |
|---|---|---|
| S26-AC-001 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-002 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-003 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-004 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-005 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-006 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-007 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-008 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-009 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-010 | Task 11 | canonical route cut, versioning, manifest and caller-retirement contracts |
| S26-AC-011 | Task 11 | canonical route cut, versioning, manifest and caller-retirement contracts |
| S26-AC-012 | Task 1 | policy, environment, session and route-frontier PHP contracts |
| S26-AC-013 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-014 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-015 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-016 | Task 11 | canonical route cut, versioning, manifest and caller-retirement contracts |
| S26-AC-017 | Task 12 | intercepted browser matrix and exact-SHA closure evidence |
| S26-AC-018 | Task 12 | intercepted browser matrix and exact-SHA closure evidence |
| S26-AC-019 | Task 12 | intercepted browser matrix and exact-SHA closure evidence |
| S26-AC-020 | Task 12 | intercepted browser matrix and exact-SHA closure evidence |
| S26-AC-021 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-022 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-023 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-024 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-025 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-026 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-027 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-028 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-029 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-030 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-031 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-032 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-033 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-034 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-035 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-036 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-037 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-038 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-039 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-040 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-041 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-042 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-043 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-044 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-045 | Task 2 | source-loader, schema, census and fail-closed contract tests |
| S26-AC-046 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-047 | Task 4 | HTTP endpoint, query, headers and PHP response contracts |
| S26-AC-048 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-049 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-050 | Task 5 | strict Zod, gateway, cancellation and global-outlet tests |
| S26-AC-051 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-052 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-053 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-054 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-055 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-056 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-057 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-058 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-059 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-060 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-061 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-062 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-063 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-064 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-065 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-066 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-067 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-068 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-069 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-070 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-071 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-072 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-073 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-074 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-075 | Task 3 | catalog composition, approvals, governance and presenter tests |
| S26-AC-076 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-077 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-078 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-079 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-080 | Task 8 | nine family renderer parity tests |
| S26-AC-081 | Task 8 | nine family renderer parity tests |
| S26-AC-082 | Task 8 | nine family renderer parity tests |
| S26-AC-083 | Task 8 | nine family renderer parity tests |
| S26-AC-084 | Task 7 | shared specimens, foundations and UI-index component tests |
| S26-AC-085 | Task 8 | nine family renderer parity tests |
| S26-AC-086 | Task 8 | nine family renderer parity tests |
| S26-AC-087 | Task 8 | nine family renderer parity tests |
| S26-AC-088 | Task 8 | nine family renderer parity tests |
| S26-AC-089 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-090 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-091 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-092 | Task 7 | shared specimens, foundations and UI-index component tests |
| S26-AC-093 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-094 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-095 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-096 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-097 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-098 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-099 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-100 | Task 6 | page shell, family registry, URL/history/focus and navigation tests |
| S26-AC-101 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-102 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-103 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-104 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-105 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-106 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-107 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-108 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-109 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-110 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-111 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-112 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-113 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-114 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-115 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-116 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-117 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-118 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-119 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-120 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-121 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-122 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-123 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-124 | Task 9 | fixture registry, Admin exclusion, caller census and adapter-ledger tests |
| S26-AC-125 | Task 11 | canonical route cut, versioning, manifest and caller-retirement contracts |
| S26-AC-126 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-127 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-128 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-129 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-130 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-131 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-132 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-133 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-134 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-135 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-136 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-137 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-138 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-139 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-140 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-141 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-142 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-143 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-144 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-145 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-146 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-147 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-148 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-149 | Task 10 | theme, responsive, accessibility, network and performance gates |
| S26-AC-150 | Task 12 | intercepted browser matrix and exact-SHA closure evidence |

## Vertical Checkpoints

1. **Boundary:** environment/capability/session/no-project route behavior frozen.
2. **Inputs:** bounded loader and SHA-bound non-admin census.
3. **Snapshot:** multiapproval catalog, Admin filtering and deterministic presenter.
4. **HTTP:** protected GET, strict query/headers/errors, no loader call on denial.
5. **Client boundary:** strict Zod/gateway and exact global outlet.
6. **Shell:** ten-family lazy registry, URL/history/focus/density.
7. **First useful family:** shared specimens, UI index and Foundations.
8. **Family parity:** remaining non-adapter families.
9. **Fixtures/adapters:** ten local demos, truthful ledger, zero vendor/Admin.
10. **Quality matrix:** dark/light, responsive, a11y, performance and approved evidence.
11. **Cut:** protected React host, 1.2.0, census-backed retirement.
12. **Closure:** intercepted browser matrix and exact-SHA repository gate.

A failed checkpoint stops later tasks. Do not compensate in React for a failed security/catalog
checkpoint and do not promote 1.2.0 with pending visual approval.

## Completion Gate

S26 is complete only when all are true:

- exact worktree/branch and dependency closures verified;
- production/unknown 404, denied 403 and authorized 200 remain exact;
- page and API share the policy and no generic SPA prefix exists;
- authorized session without project can open S26;
- product sidebar has no Design System item;
- no `/admin/` path changed;
- API has strict PHP/Zod contracts and no mutation methods;
- runtime touches no Database/RLS/project/week;
- loader validates nine authorities and fails closed;
- ten families, 17 candidates, 12 approvals, 29 components and all source groups are accounted for;
- shell/states preserve both approvals;
- ten non-admin fixtures render from synthetic local state;
- AdminLTE/admin-operations/admin consumers are absent from S26 output and requests;
- adapter ledger matches a closure-SHA census and loads zero vendor assets;
- exactly one family mounts and one catalog request occurs;
- dark/light and 390/768/1180/1440 plus 320/200% pass;
- keyboard/focus/overlay/BI equivalence and Axe pass;
- performance budget passes three cold samples;
- visual candidates received explicit approval before manifest/hash changes;
- 1.2.0 live envelopes are atomic; stable API semantics and historical measurements remain
  unchanged, and no receipt is made to look fresher than its real execution;
- VIEW-13–25 and exclusive assets are retired only with zero callers;
- PHP, frontend, static and browser gates are green on exact SHA;
- 150/150 acceptance criteria have one owner/evidence row;
- `git diff --check` passes and Admin diff is empty;
- plan `Cierre` records evidence rather than checkbox counts;
- PR/CI/publication follows repository policy;
- deploy remains separately authorized.

## Cierre

**Estado:** plan escrito; no ejecutado.

Esta sesión sólo produjo la spec S26 y este plan. No implementó archivos de producto, no ejecutó
DDL/DML, no tocó RLS, datos, credenciales ni `/admin/`, no regeneró goldens y no hizo commit, push,
PR, publicación ni deploy.

Al ejecutar el frente, reemplazar este cierre con:

- SHA inicial/final;
- commits por checkpoint;
- comandos y RC;
- resultado de 150/150;
- caller census y lista de retiros;
- evidencia dark/light/responsive/a11y/performance;
- aprobación visual;
- guard Admin vacío;
- estado PR/CI/publicación;
- límites o rollback pendiente.
