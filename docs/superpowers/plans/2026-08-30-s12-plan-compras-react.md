# S12 Plan de Compras v2 React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` in an explicitly
> authorized implementation session. Execute tasks in order and stop at every vertical checkpoint.
> Checkbox syntax is an execution prompt only; progress and closure live in `## Cierre` and git
> history, never in checkbox counts.

**Goal:** absorb the separate `pdc-app/` island into the main React SPA without losing any of its
eight screens, 65 consumed HTTP contracts, uploads, downloads, permissions, responsive behavior,
help, tour or domain rules; replace hash navigation with canonical nested routes while preserving
old deep links; then retire the separate build only after parity and rollback evidence.

**Architecture:** T01 supplies the authenticated shell, selected project, sidebar, theme and router.
S12 keeps all 69 registered PDC API method/path pairs and the existing PHP domain services. Only
`GET /plan-compras/api/contexto` is adapted to expose server-effective actions, navigation and
configuration. The main SPA owns one PDC envelope gateway on top of
`frontend/src/lib/api/cliente.ts`, strict Zod schemas for all 65 consumed pairs, lazy route chunks,
AG Grid Community at 768+ and native mobile collections below 768. A closed hash bridge converts
known fragments with `history.replaceState`. The old island remains the canonical rollback target
until the final cut.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, AG Grid Community 36, Playwright 1.61, Axe and AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-s12-plan-compras-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, branch
  `shell-minimo-react`. Never work in `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout
  or another worktree.
- Execute only after the T01 shell/router/client contracts consumed here exist and are green. Reuse
  them; do not create a second authenticated shell, project selector, sidebar, theme store or
  router.
- Inspect `git status --short` and relevant diffs before every task. Preserve pre-existing and
  unrelated edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future execution instructions and require
  explicit implementation authorization.
- `/admin/` is excluded. Do not touch its routes, PHP, TypeScript, CSS, permissions or tests.
- Do not modify RLS, `ProjectScope` semantics, schema, migrations, tables, columns, indexes,
  triggers, grants, users, credentials, memberships, roles, aliases, overrides or database data.
  No DDL/DML is permitted during documentation or safe verification.
- Keep the existing 69 PDC API method/path pairs. Four have no current UI consumer:
  `POST maestro`, `GET paquetes/plan-auto`, `POST paquetes/auto-asignar` and
  `GET subpaquetes/destinos`. Preserve them without creating UI.
- Adapt only `GET /plan-compras/api/contexto`. Any later response-shape change discovered in another
  endpoint must stop at a review gate, gain a PHP contract and amend the spec before implementation.
- Preserve the module envelope and endpoint payloads. Normalize the envelope in the frontend
  gateway, not by rewriting 68 server contracts.
- PHP remains authoritative for session, project, capabilities, accessible source projects,
  versions, IDs, calculations, dates and scope. React never sends `project_id` or role as authority.
- Context actions come from `RbacService::can()`. Never encode the role matrix in React. Preserve
  alias `P -> D`, effective overrides and the unusual current guards documented by S12.
- Keep CSRF purpose `plan_compras_v2` for all current mutations. GET simulation keeps edit
  permission without CSRF; rules-only GETs keep rules permission.
- Preserve all formulas and invariants: five negotiation types, four modalities, version history,
  obsolete warnings, 10 MB uploads, 0.25 percent tamiz default, `subpaqueteId=0`, plan calculations,
  real dates, vencimiento cuts and cash-flow totals.
- Characterize `PaquetesService::TIPOS` before porting UI constants. Current code accepts all five,
  including `no_aplica`; the stale island filter must not survive.
- Reconstruct the `sin-elegir` selection constant as valid text. Never copy the NUL byte present in
  `pdc-app/src/lib/planFechas.ts`.
- Only `frontend/src/lib/api/cliente.ts` may call `fetch`. Components, hooks and module gateways may
  not call it, wrap it or use a second HTTP library.
- Every consumed response has strict Zod. Wire/domain types use `z.infer`; do not port the manual
  `pdc-app/src/lib/types.ts` as a competing source.
- Mutations never retry automatically. Reads may be cancelled with `AbortSignal`. A failed mutation
  preserves useful selection, file or draft and never announces false success.
- Use AG Grid Community only. Load it in the PDC route chunk, never globally. Do not add Enterprise,
  a query library, a state library, CSS-in-JS, Bootstrap, jQuery, DataTables or Select2.
- At widths below 768 px use native cards/lists/forms with the same data/actions. Mount either grid
  or mobile collection, never both.
- Use only variables derived from `public/css/tokens.css`. Do not port local color hex, inline style
  objects, `!important` or the unlayered island theme.
- Dark and light have identical capability. Validate `390x844`, `480x900`, `768x1024`, `1180x820`
  and `1440x900`, zoom 200 percent, reduced motion, keyboard and touch.
- Keep `aia-pdc-recorrido` and `pdc-umbral-global:<projectId>` compatible. Storage failure is
  nonfatal and no sensitive value is stored.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain untracked evidence until the visual gate is approved.
- PHP contract tests use pure services, fixtures, fakes or source assertions; browser tests install
  complete network interception before navigation. No PDC test may write and then restore data.
- `SpaRouter::sirveLaSpa()` must reject `/plan-compras/api` before accepting the
  `/plan-compras` SPA prefix. APIs must retain authentication and JSON handling.
- Do not delete VIEW-31, `PlanComprasController`, `pdc-app/`, `public/pdc-app/` or dedicated scripts
  until Task 13 proves zero consumers and exercises route rollback.

## File Structure

### Create — server context and contracts

- `src/Services/Pdc/PlanComprasActionPolicy.php` — pure capability-to-action mapping.
- `src/Services/Pdc/PlanComprasContext.php` — target DTO plus transitional pilot serializer.
- `tests/fixtures/pdc-react/endpoint-inventory.json` — 69 method/path/capability/consumer records.
- `tests/fixtures/pdc-react/context-v2.php` — deterministic target and pilot context fixtures.
- `tests/test_pdc_react_route_inventory.php` — route count, identities and four compatibility pairs.
- `tests/test_pdc_react_source_invariants.php` — types, modalities, tabs, help, tour and source debts.
- `tests/test_pdc_react_context_contract.php` — action matrix, no raw role, target/pilot shape.
- `tests/test_pdc_react_spa_boundary.php` — page/API classification and authentication precedence.

### Create — frontend API and domain

- `frontend/src/modules/plan-compras/api/endpointInventory.ts`.
- `frontend/src/modules/plan-compras/api/envelope.ts`.
- `frontend/src/modules/plan-compras/api/gateway.ts`.
- `frontend/src/modules/plan-compras/api/schemas/common.ts`.
- `frontend/src/modules/plan-compras/api/schemas/context.ts`.
- `frontend/src/modules/plan-compras/api/schemas/budget.ts`.
- `frontend/src/modules/plan-compras/api/schemas/master.ts`.
- `frontend/src/modules/plan-compras/api/schemas/packages.ts`.
- `frontend/src/modules/plan-compras/api/schemas/subpackages.ts`.
- `frontend/src/modules/plan-compras/api/schemas/plan.ts`.
- `frontend/src/modules/plan-compras/api/schemas/steps.ts`.
- `frontend/src/modules/plan-compras/api/schemas/tracking.ts`.
- Colocated `.test.ts` files for every inventory, envelope, gateway and schema file.
- `frontend/src/modules/plan-compras/domain/catalogs.ts`.
- `frontend/src/modules/plan-compras/domain/text.ts`.
- `frontend/src/modules/plan-compras/domain/importState.ts`.
- `frontend/src/modules/plan-compras/domain/masterState.ts`.
- `frontend/src/modules/plan-compras/domain/budgetTree.ts`.
- `frontend/src/modules/plan-compras/domain/comparison.ts`.
- `frontend/src/modules/plan-compras/domain/tamiz.ts`.
- `frontend/src/modules/plan-compras/domain/packageState.ts`.
- `frontend/src/modules/plan-compras/domain/packageWizardState.ts`.
- `frontend/src/modules/plan-compras/domain/subpackages.ts`.
- `frontend/src/modules/plan-compras/domain/plan.ts`.
- `frontend/src/modules/plan-compras/domain/reprogramming.ts`.
- `frontend/src/modules/plan-compras/domain/steps.ts`.
- `frontend/src/modules/plan-compras/domain/tracking.ts`.
- `frontend/src/modules/plan-compras/domain/expirations.ts`.
- `frontend/src/modules/plan-compras/domain/cashFlow.ts`.
- Colocated pure `.test.ts` files for every domain module used.

### Create — routing and shared UI

- `frontend/src/modules/plan-compras/routing/routes.ts`.
- `frontend/src/modules/plan-compras/routing/hashBridge.ts`.
- `frontend/src/modules/plan-compras/routing/PlanComprasRoutes.tsx`.
- `frontend/src/modules/plan-compras/PlanComprasLayout.tsx`.
- `frontend/src/modules/plan-compras/components/ModuleNavigation.tsx`.
- `frontend/src/modules/plan-compras/components/Tabs.tsx`.
- `frontend/src/modules/plan-compras/components/FilterBar.tsx`.
- `frontend/src/modules/plan-compras/components/SearchableList.tsx`.
- `frontend/src/modules/plan-compras/components/Selector.tsx`.
- `frontend/src/modules/plan-compras/components/ResponsiveCollection.tsx`.
- `frontend/src/modules/plan-compras/components/PdcGrid.tsx`.
- `frontend/src/modules/plan-compras/components/HiddenColumnsNotice.tsx`.
- `frontend/src/modules/plan-compras/components/ConfirmDialog.tsx`.
- `frontend/src/modules/plan-compras/components/HelpButton.tsx`.
- `frontend/src/modules/plan-compras/components/GuidedTour.tsx`.
- `frontend/src/modules/plan-compras/help/content.ts`.
- `frontend/src/modules/plan-compras/help/tour.ts`.
- `frontend/src/modules/plan-compras/plan-compras.css`.
- Colocated `.test.tsx` and `.test.ts` files.

### Create — eight route screens

- `frontend/src/modules/plan-compras/budget/ImportBudgetPage.tsx`.
- `frontend/src/modules/plan-compras/master/MasterSuppliesPage.tsx`.
- `frontend/src/modules/plan-compras/budget/BudgetViewerPage.tsx`.
- `frontend/src/modules/plan-compras/budget/BudgetComparisonPage.tsx`.
- `frontend/src/modules/plan-compras/packages/ContractPackagesPage.tsx`.
- `frontend/src/modules/plan-compras/packages/PackageWizard.tsx`.
- `frontend/src/modules/plan-compras/packages/SubpackagesPanel.tsx`.
- `frontend/src/modules/plan-compras/plan/PurchasePlanPage.tsx`.
- `frontend/src/modules/plan-compras/steps/ContractStepsPage.tsx`.
- `frontend/src/modules/plan-compras/tracking/PurchaseTrackingPage.tsx`.
- Colocated tests for each page/panel and its hooks.

### Create — browser evidence

- `tests/browser/react-plan-compras-routes.spec.mjs`.
- `tests/browser/react-plan-compras-import.spec.mjs`.
- `tests/browser/react-plan-compras-master.spec.mjs`.
- `tests/browser/react-plan-compras-budget.spec.mjs`.
- `tests/browser/react-plan-compras-packages.spec.mjs`.
- `tests/browser/react-plan-compras-plan.spec.mjs`.
- `tests/browser/react-plan-compras-steps.spec.mjs`.
- `tests/browser/react-plan-compras-tracking.spec.mjs`.
- `tests/browser/react-plan-compras-responsive-a11y.spec.mjs`.
- `tests/browser/support/react-plan-compras-fixtures.mjs` — deterministic envelopes and download.
- `tests/browser/support/react-plan-compras-network.mjs` — fail-closed interception.

### Modify during implementation

- `src/Controllers/Api/PlanComprasApiController.php` — context v2 only.
- `frontend/src/lib/api/cliente.ts` and `.test.ts` — JSON, envelope, multipart, download and signals.
- `frontend/package.json` and `frontend/package-lock.json` — AG Grid Community/React 36.
- T01 route registry or `frontend/src/shell/rutas.tsx` — pilot then canonical route.
- `frontend/src/shell/NavegacionLateral.tsx` and test only if T01 has not already made navigation
  server-driven.
- `src/Core/SpaRouter.php`.
- `tests/test_spa_frontera.php`.
- `public/index.php`.
- `docs/design-system/manifests/plan-compras-v2.json`.
- `scripts/wiki-arquitectura.modulos.mjs`.
- `scripts/design-system-plan-compras-gate.mjs` — retire or retarget to main bundle after proof.
- `docs/siteground-deploy-routine.md` — remove obsolete separate-node_modules excludes.
- Current PDC browser/design-system tests that name the separate bundle but still express live
  product behavior.

### Delete at canonical cut only

- `views/plan-compras/app.view.php`.
- `src/Controllers/Gestion/PlanComprasController.php`, after zero callers.
- Entire tracked `pdc-app/` directory, 92 files at the audited baseline.
- `public/pdc-app/assets/pdc.css`.
- `public/pdc-app/assets/pdc.js`.
- Dedicated gate only if its responsibility is covered by the main design-system audit.
- No historical spec, plan, audit or evidence is rewritten merely to erase history.

## Exact Contracts to Implement

### Endpoint inventory

- 69 registered API method/path pairs, numbered 1 through 69 exactly as S12 specifies.
- 65 pairs marked `consumed`.
- Four pairs marked `compatibility-only`.
- Every consumed pair maps to exactly one response schema.
- Every mutation maps to its request schema and CSRF behavior.
- No component constructs an endpoint path outside `gateway.ts`.
- Registry tests compare method/path pairs to `public/index.php`.

### Context

Target shape:

```json
{
  "contractVersion": 2,
  "project": {"id": 27, "name": "Proyecto"},
  "user": {"id": 81, "name": "Persona"},
  "csrfToken": "opaque",
  "config": {
    "maxUploadBytes": 10485760,
    "acceptedBudgetExtensions": [".xlsx"],
    "acceptedMasterExtensions": [".xlsx"]
  },
  "navigation": {
    "defaultRoute": "/plan-compras/ensamble/importar",
    "routes": [],
    "hashAliases": {}
  },
  "actions": {}
}
```

`user.id` may be null. Pilot shape adds only the explicitly deprecated flat keys and `rol: ""`.
It never sends a real role. The SPA adapter exposes only the target fields. Task 13 removes the
deprecated keys and proves the final shape.

### Actions

The exact booleans are the 37 keys listed by S12 under `Contexto adaptado y acciones efectivas`.
Each is derived from one or two server capabilities. Project/global correspondence remain separate.
No role string participates. Endpoint guards remain authoritative.

### Client modes

`cliente.ts` exposes one low-level request primitive plus typed helpers:

- JSON GET;
- JSON mutation;
- multipart mutation without forcing `Content-Type`;
- download returning filename, MIME and Blob;
- status/envelope/schema/network error classes;
- `AbortSignal`;
- same-origin credentials;
- caller headers merged safely;
- `X-AIA-Expect-Json` for JSON;
- `X-CSRF-Token` only when requested;
- no automatic mutation retry.

The existing `pedir()` API remains compatible until all current consumers migrate.

### Routing

- Canonical base: `/plan-compras`.
- Pilot base: `/app/plan-compras`, internal and not linked from legacy sidebar.
- Eight child routes and one root redirect.
- Seven visible navigation links; Steps is nested only.
- Nine known hash aliases including `#/maestro`.
- Hash conversion uses replace, preserves allowed queries and rejects unknown paths.
- `/plan-compras/api` is never classified as SPA.
- Assets remain under `/app/assets`.

### Responsive

- `<768`: native mobile representation.
- `>=768`: grid/table representation.
- Exactly one mounted representation.
- Viewport tests: 390, 480, 768, 1180, 1440.
- No page overflow.
- Internal scroll only where declared by S12.

## Task 1: Freeze the route, catalog and source invariants

**Files:**

- Create `tests/fixtures/pdc-react/endpoint-inventory.json`.
- Create `tests/test_pdc_react_route_inventory.php`.
- Create `tests/test_pdc_react_source_invariants.php`.
- Create `frontend/src/modules/plan-compras/api/endpointInventory.ts`.
- Create `frontend/src/modules/plan-compras/api/endpointInventory.test.ts`.
- Create `frontend/src/modules/plan-compras/domain/catalogs.ts`.
- Create `frontend/src/modules/plan-compras/domain/catalogs.test.ts`.

### Step 1: Write failing inventory and literal catalog tests

- [ ] Assert 69 unique method/path pairs, 65 consumed and four compatibility-only.
- [ ] Compare all 69 identities against `public/index.php`.
- [ ] Assert eight screens, seven nav entries, 13 stable tabs, equipment conditional and six tour
  steps.
- [ ] Assert five negotiation types and four modalities equal server constants.
- [ ] Assert the target selection token is exactly `sin-elegir` and contains no NUL.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_route_inventory.php
docker compose exec app php tests/test_pdc_react_source_invariants.php
npm --prefix frontend test -- src/modules/plan-compras/api/endpointInventory.test.ts src/modules/plan-compras/domain/catalogs.test.ts
```

Expected: missing fixtures/modules and the stale creatable-type/NUL assumptions fail. Capture each RC
on its own line.

### Step 3: Implement the minimum registry and catalogs

- [ ] Transcribe the audited inventory from S12 without changing routes.
- [ ] Mark exactly four compatibility-only pairs.
- [ ] Define five types/four modalities from characterized backend values.
- [ ] Define route/tab/help/tour IDs without JSX.
- [ ] Recreate `sin-elegir` from valid source text, never by copying the binary line.

### Step 4: Run GREEN and source audit

```bash
docker compose exec app php tests/test_pdc_react_route_inventory.php
docker compose exec app php tests/test_pdc_react_source_invariants.php
npm --prefix frontend test -- src/modules/plan-compras/api/endpointInventory.test.ts src/modules/plan-compras/domain/catalogs.test.ts
rg -n "fetch\\(" frontend/src --glob "*.ts" --glob "*.tsx"
```

Expected: contracts green; the only existing fetch call remains `cliente.ts`.

### Step 5: Review and future commit

- [ ] Inspect diff for invented endpoint/UI behavior.
- [ ] Confirm no runtime route changed.
- [ ] Future commit: `test(pdc-react): freeze endpoint and catalog inventory`.

## Task 2: Add the server-effective context contract

**Files:**

- Create `src/Services/Pdc/PlanComprasActionPolicy.php`.
- Create `src/Services/Pdc/PlanComprasContext.php`.
- Create `tests/fixtures/pdc-react/context-v2.php`.
- Create `tests/test_pdc_react_context_contract.php`.
- Modify `src/Controllers/Api/PlanComprasApiController.php`.
- Modify `pdc-app/src/lib/bootstrap.ts` and its test only for temporary compatibility.

### Step 1: Write failing policy and contract tests

- [ ] Cover A, D, OT, R, DCV, V, G and C fallback capability fixtures.
- [ ] Cover a synthetic override combination that no named role has.
- [ ] Assert 37 exact action keys and separate project/global correspondence.
- [ ] Assert target context has no role.
- [ ] Assert pilot context carries `rol: ""`, never the real role, and old bootstrap tolerates it.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_context_contract.php
npm --prefix pdc-app test -- src/lib/bootstrap.test.ts
```

Expected: context services do not exist and current controller exposes raw role. If island
dependencies are unavailable, install nothing silently; record the missing dependency and run its
test only in the authorized execution environment after `npm ci`.

### Step 3: Implement pure action policy and thin controller

- [ ] Map current capabilities to action keys without role checks.
- [ ] Serialize target and transitional pilot shapes.
- [ ] Keep project/user resolution and CSRF purpose unchanged.
- [ ] Remove real role from JSON context.
- [ ] Make old bootstrap treat absent/blank role as an unused compatibility value.

### Step 4: Run GREEN plus denial checks

```bash
docker compose exec app php tests/test_pdc_react_context_contract.php
npm --prefix pdc-app test -- src/lib/bootstrap.test.ts
docker compose exec app php tests/test_pdc_v2_contexto.php
```

The last existing test may require an update only if it asserts the superseded raw-role shape; never
relax session/project/capability expectations.

### Step 5: Review and future commit

- [ ] Verify only context response changed.
- [ ] Verify no permission key or alias changed.
- [ ] Future commit: `feat(pdc-react): expose effective context actions`.

## Task 3: Build the common client, schemas, routes and shared primitives

**Files:**

- Modify `frontend/src/lib/api/cliente.ts` and `.test.ts`.
- Modify `frontend/package.json` and `frontend/package-lock.json`.
- Create common/context API files from File Structure.
- Create routing, help, tour and shared component files from File Structure.
- Create `frontend/src/modules/plan-compras/plan-compras.css`.

### Step 1: Write failing client/router/shared tests

- [ ] JSON success/error/envelope/schema/network/status.
- [ ] Multipart does not force JSON content type.
- [ ] Download parses safe filename and rejects HTTP/error HTML.
- [ ] AbortSignal reaches fetch; POST has no retry.
- [ ] Nine hash mappings preserve allowed queries and use replace.
- [ ] Unknown hash is rejected.
- [ ] Seven links/eight help records/six tour steps.
- [ ] Tabs keyboard, dialog focus and exclusive responsive mounting.

### Step 2: Run RED

```bash
npm --prefix frontend test -- src/lib/api/cliente.test.ts src/modules/plan-compras/api src/modules/plan-compras/routing src/modules/plan-compras/components src/modules/plan-compras/help
```

Expected: helpers, schemas and components are missing.

### Step 3: Implement the minimum foundation

- [ ] In the authorized execution session run
  `npm --prefix frontend install ag-grid-community@^36.0.2 ag-grid-react@^36.0.2`.
- [ ] Preserve `pedir()` while adding envelope, multipart and download helpers.
- [ ] Add strict common/context schemas and typed PDC gateway.
- [ ] Implement pure route manifest and hash bridge.
- [ ] Port help/tour content and storage keys.
- [ ] Implement accessible tabs, selectors, dialog, filter bar and responsive switch.
- [ ] Configure AG Grid through CSS variables derived only from tokens.

### Step 4: Run GREEN and dependency checks

```bash
npm --prefix frontend test -- src/lib/api/cliente.test.ts src/modules/plan-compras/api src/modules/plan-compras/routing src/modules/plan-compras/components src/modules/plan-compras/help
npm --prefix frontend run typecheck
npm --prefix frontend run build
```

Confirm non-PDC shell tests do not import AG Grid and the main initial chunk does not eagerly include
the PDC route chunk.

### Step 5: Review and future commit

- [ ] Audit for extra fetch calls, literal colors, global AG Grid registration and duplicated shell.
- [ ] Future commit: `feat(pdc-react): add typed client and module foundation`.

## Task 4: Port budget import and version history

**Files:**

- Create `frontend/src/modules/plan-compras/api/schemas/budget.ts` and test.
- Create `frontend/src/modules/plan-compras/domain/importState.ts` and test.
- Create `frontend/src/modules/plan-compras/budget/ImportBudgetPage.tsx` and test.
- Create `tests/fixtures/pdc-react/budget.php`.
- Create `tests/test_pdc_react_budget_contract.php`.
- Create `tests/browser/react-plan-compras-import.spec.mjs`.

### Step 1: Write failing contract/domain/page tests

- [ ] Preview, confirm, versions, activate and impact schemas.
- [ ] `.xlsx`, 10 MB, drag/drop and multipart field `archivo`.
- [ ] Validation rows, warnings, no-change and impact groups.
- [ ] Token expiry and no automatic confirm retry.
- [ ] History search, maximum two selections, viewer/comparison canonical links.
- [ ] Activate impact/confirmation and read-only actions.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_budget_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/budget.test.ts src/modules/plan-compras/domain/importState.test.ts src/modules/plan-compras/budget/ImportBudgetPage.test.tsx
```

### Step 3: Implement the import vertical

- [ ] Port the reducer and history helpers without manual wire types.
- [ ] Use gateway multipart and JSON mutations.
- [ ] Wait for server success before changing version state.
- [ ] Preserve file/draft on recoverable failure.
- [ ] Render authorized/read-only states and accessible confirmation.
- [ ] Keep all legacy terminology for appearances versus distinct supplies.

### Step 4: Run GREEN and intercepted browser scenario

```bash
docker compose exec app php tests/test_pdc_react_budget_contract.php
npm --prefix frontend test -- src/modules/plan-compras/budget src/modules/plan-compras/api/schemas/budget.test.ts src/modules/plan-compras/domain/importState.test.ts
npx playwright test tests/browser/react-plan-compras-import.spec.mjs --workers=1
```

The browser fixture must fail on any unregistered request and must not upload to the real app.

### Step 5: Review and future commit

- [ ] Confirm no real file/token/version was touched.
- [ ] Future commit: `feat(pdc-react): port budget import and versions`.

## Task 5: Port the global supplies master

**Files:**

- Create `frontend/src/modules/plan-compras/api/schemas/master.ts` and test.
- Create `frontend/src/modules/plan-compras/domain/masterState.ts` and test.
- Create `frontend/src/modules/plan-compras/master/MasterSuppliesPage.tsx` and test.
- Create `tests/fixtures/pdc-react/master.php`.
- Create `tests/test_pdc_react_master_contract.php`.
- Create `tests/browser/react-plan-compras-master.spec.mjs`.

### Step 1: Write failing schema and behavior tests

- [ ] Twelve consumed master pairs; manual create remains unconsumed.
- [ ] Coverage, pending selection, suggestions and confirmation.
- [ ] Generate-links runs only when action allows and is announced.
- [ ] Retire/reactivate and reenganchados.
- [ ] SINCO preview/confirm/conflicts.
- [ ] Equipment queue without project budget, hints and human classification.
- [ ] Three stable tabs plus conditional Equipment.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_master_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/master.test.ts src/modules/plan-compras/domain/masterState.test.ts src/modules/plan-compras/master/MasterSuppliesPage.test.tsx
```

### Step 3: Implement the master vertical

- [ ] Port catalog/pending/import/equipment flows.
- [ ] Skip automatic POST for read-only users and load links directly.
- [ ] Keep the global-scope warning on destructive catalog actions.
- [ ] Keep equipment visible when budget is absent.
- [ ] Never add a manual-create form.
- [ ] Recover from failed auto-generation without hiding the read view.

### Step 4: Run GREEN and browser matrix

```bash
docker compose exec app php tests/test_pdc_react_master_contract.php
npm --prefix frontend test -- src/modules/plan-compras/master src/modules/plan-compras/api/schemas/master.test.ts src/modules/plan-compras/domain/masterState.test.ts
npx playwright test tests/browser/react-plan-compras-master.spec.mjs --workers=1
```

Cover writable OT, read-only R and no-module G through intercepted context/actions.

### Step 5: Review and future commit

- [ ] Confirm no global master write reached a database.
- [ ] Future commit: `feat(pdc-react): port supplies master`.

## Task 6: Port budget viewer and comparison

**Files:**

- Extend `frontend/src/modules/plan-compras/api/schemas/budget.ts` and test.
- Create budget tree/comparison/tamiz/text domain files and tests.
- Create `BudgetViewerPage.tsx`, `BudgetComparisonPage.tsx` and tests.
- Extend `tests/fixtures/pdc-react/budget.php`.
- Extend `tests/test_pdc_react_budget_contract.php`.
- Create `tests/browser/react-plan-compras-budget.spec.mjs`.

### Step 1: Write failing data and UI tests

- [ ] Tree/flat hierarchy, levels, totals, warnings and version query.
- [ ] Search/type/unit/grid filters and chips.
- [ ] Tamiz default, million rounding, project-scoped key and storage failure.
- [ ] Comparison A/B validation, obsolete warnings and summary.
- [ ] Activity hierarchy and supply/Pareto axis.
- [ ] Desktop grid/mobile card information parity.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_budget_contract.php
npm --prefix frontend test -- src/modules/plan-compras/budget src/modules/plan-compras/domain/budgetTree.test.ts src/modules/plan-compras/domain/comparison.test.ts src/modules/plan-compras/domain/tamiz.test.ts
```

### Step 3: Implement both read-only screens

- [ ] Port pure tree/comparison/tamiz helpers.
- [ ] Read `version`, `a`, `b` from canonical query params.
- [ ] Keep server warnings before summaries.
- [ ] Share filter state between grid/cards.
- [ ] Provide textual table equivalents for Pareto information.
- [ ] Never issue a mutation.

### Step 4: Run GREEN and deep-link browser checks

```bash
docker compose exec app php tests/test_pdc_react_budget_contract.php
npm --prefix frontend test -- src/modules/plan-compras/budget src/modules/plan-compras/domain/budgetTree.test.ts src/modules/plan-compras/domain/comparison.test.ts src/modules/plan-compras/domain/tamiz.test.ts
npx playwright test tests/browser/react-plan-compras-budget.spec.mjs --workers=1
```

### Step 5: Review and future commit

- [ ] Verify hash and canonical query links produce identical state.
- [ ] Future commit: `feat(pdc-react): port budget viewer and comparison`.

## Task 7: Port packages and the step-by-step assistant

**Files:**

- Create package schema/domain/page/wizard files and tests.
- Create `tests/fixtures/pdc-react/packages.php`.
- Create `tests/test_pdc_react_packages_contract.php`.
- Create `tests/browser/react-plan-compras-packages.spec.mjs`.

### Step 1: Write failing contracts and interaction tests

- [ ] Ten consumed package pairs; two automatic pairs remain compatibility-only.
- [ ] Four filters, search, grouping, resource type, grid filters and chips.
- [ ] Count/value coverage and nullable success rate.
- [ ] Suggestions preserve layer/confidence/evidence.
- [ ] Assign/omit/unassign and batch results.
- [ ] Create package offers five types/four modalities.
- [ ] Wizard assign/create/omit/skip/undo/add candidates.
- [ ] Activity evidence and 100-percent state.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_packages_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/packages.test.ts src/modules/plan-compras/domain/packageState.test.ts src/modules/plan-compras/domain/packageWizardState.test.ts src/modules/plan-compras/packages/ContractPackagesPage.test.tsx src/modules/plan-compras/packages/PackageWizard.test.tsx
```

### Step 3: Implement packages without automatic UI expansion

- [ ] Port state/provenance helpers.
- [ ] Use strict request schemas for supply identity/provenance.
- [ ] Keep batch action busy/error state scoped.
- [ ] Keep all five accepted types synchronized with backend.
- [ ] Open subpackages from package rows but leave the panel implementation to Task 8.
- [ ] Never call `plan-auto` or `auto-asignar`.

### Step 4: Run GREEN and intercepted workflows

```bash
docker compose exec app php tests/test_pdc_react_packages_contract.php
npm --prefix frontend test -- src/modules/plan-compras/packages src/modules/plan-compras/api/schemas/packages.test.ts src/modules/plan-compras/domain/packageState.test.ts src/modules/plan-compras/domain/packageWizardState.test.ts
npx playwright test tests/browser/react-plan-compras-packages.spec.mjs --workers=1
```

### Step 5: Review and future commit

- [ ] Confirm no auto-assignment endpoint was requested.
- [ ] Future commit: `feat(pdc-react): port package assembly and assistant`.

## Task 8: Port subpackages with accessible confirmations

**Files:**

- Create subpackage schema/domain/panel files and tests.
- Create `tests/fixtures/pdc-react/subpackages.php`.
- Create `tests/test_pdc_react_subpackages_contract.php`.
- Extend `tests/browser/react-plan-compras-packages.spec.mjs`.

### Step 1: Write failing contract and invariant tests

- [ ] Six consumed pairs; `GET destinos` remains compatibility-only.
- [ ] `subpaqueteId=0` stays distinct from null.
- [ ] Split payload is `{paqueteId,nombres}` and creates a remainder.
- [ ] Add/update/delete/move exact payloads.
- [ ] Delete last real lot unpartitions.
- [ ] Confirm dialog explains redistribution and restores focus.
- [ ] Mobile supports every mutation.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_subpackages_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/subpackages.test.ts src/modules/plan-compras/domain/subpackages.test.ts src/modules/plan-compras/packages/SubpackagesPanel.test.tsx
```

### Step 3: Implement the subpackage panel

- [ ] Port umbrella/remainder calculations.
- [ ] Use package plus subpackage identity everywhere.
- [ ] Replace `window.confirm` with shared dialog.
- [ ] Keep mutations authorized by action keys.
- [ ] Refresh only the opened package after success.
- [ ] Do not use the unused destinations endpoint.

### Step 4: Run GREEN and package integration

```bash
docker compose exec app php tests/test_pdc_react_subpackages_contract.php
npm --prefix frontend test -- src/modules/plan-compras/packages src/modules/plan-compras/api/schemas/subpackages.test.ts src/modules/plan-compras/domain/subpackages.test.ts
npx playwright test tests/browser/react-plan-compras-packages.spec.mjs --workers=1
```

### Step 5: Review and future commit

- [ ] Confirm no browser-native confirm remains.
- [ ] Future commit: `feat(pdc-react): port subpackage management`.

## Task 9: Port the purchase plan and reprogramming

**Files:**

- Create plan schema/domain/reprogramming/page files and tests.
- Create `tests/fixtures/pdc-react/plan.php`.
- Create `tests/test_pdc_react_plan_contract.php`.
- Create `tests/browser/react-plan-compras-plan.spec.mjs`.

### Step 1: Write failing contracts and flows

- [ ] Fourteen plan pairs and exact guards.
- [ ] Plan/Sin frente/Sin calcular/Desfases tabs.
- [ ] Header-without-ID anchor fallback remains represented as a front.
- [ ] Suggestions, reasons, high-confidence batch and manual anchors.
- [ ] Attach/detach/calculate.
- [ ] Individual/batch/clear responsible.
- [ ] Project versus global correspondence actions.
- [ ] Simulation before apply; real dates preserved; orphans visible.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_plan_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/plan.test.ts src/modules/plan-compras/domain/plan.test.ts src/modules/plan-compras/domain/reprogramming.test.ts src/modules/plan-compras/plan/PurchasePlanPage.test.tsx
```

### Step 3: Implement the plan vertical

- [ ] Port plan/status/selection helpers from valid text.
- [ ] Key rows by package/subpackage.
- [ ] Gate each action from context and keep server errors authoritative.
- [ ] Require accessible detach and apply confirmation.
- [ ] Keep simulation selection explicit.
- [ ] Link Steps through canonical nested route.
- [ ] Announce recalculation and mismatches.

### Step 4: Run GREEN and capability scenarios

```bash
docker compose exec app php tests/test_pdc_react_plan_contract.php
npm --prefix frontend test -- src/modules/plan-compras/plan src/modules/plan-compras/api/schemas/plan.test.ts src/modules/plan-compras/domain/plan.test.ts src/modules/plan-compras/domain/reprogramming.test.ts
npx playwright test tests/browser/react-plan-compras-plan.spec.mjs --workers=1
```

Cover D/OT edit, R read-only, global correspondence rules and GET-simulation edit guard.

### Step 5: Review and future commit

- [ ] Confirm no program schedule endpoint or data was mutated in tests.
- [ ] Future commit: `feat(pdc-react): port purchase plan and reprogramming`.

## Task 10: Port configurable contract steps and durations

**Files:**

- Create steps schema/domain/page files and tests.
- Create `tests/fixtures/pdc-react/steps.php`.
- Create `tests/test_pdc_react_steps_contract.php`.
- Create `tests/browser/react-plan-compras-steps.spec.mjs`.

### Step 1: Write failing contracts and editor tests

- [ ] Nine step pairs with exact view/rules guard split.
- [ ] Add/remove/reorder/alias/fixed days validation.
- [ ] Save recalculates.
- [ ] Accessible origin list and copy preview.
- [ ] Incomplete-copy warning.
- [ ] History readable without rules.
- [ ] Reset and global duration warning.
- [ ] No hardcoded seven-step assumption.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_steps_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/steps.test.ts src/modules/plan-compras/domain/steps.test.ts src/modules/plan-compras/steps/ContractStepsPage.test.tsx
```

### Step 3: Implement the nested Steps screen

- [ ] Use server catalog/effective configuration.
- [ ] Keep read sections for package viewers.
- [ ] Show rules-only sections only when authorized.
- [ ] Revalidate selected source/duration through server response.
- [ ] Wait for recalculation result before success.
- [ ] Restore focus after copy/reset confirmations.

### Step 4: Run GREEN and nested-route scenario

```bash
docker compose exec app php tests/test_pdc_react_steps_contract.php
npm --prefix frontend test -- src/modules/plan-compras/steps src/modules/plan-compras/api/schemas/steps.test.ts src/modules/plan-compras/domain/steps.test.ts
npx playwright test tests/browser/react-plan-compras-steps.spec.mjs --workers=1
```

### Step 5: Review and future commit

- [ ] Confirm no global duration or project configuration write reached the database.
- [ ] Future commit: `feat(pdc-react): port contract steps and durations`.

## Task 11: Port tracking, expirations and cash flow

**Files:**

- Create tracking schemas/domains/page files and tests.
- Create `tests/fixtures/pdc-react/tracking.php`.
- Create `tests/test_pdc_react_tracking_contract.php`.
- Create `tests/browser/react-plan-compras-tracking.spec.mjs`.
- Extend client download tests.

### Step 1: Write failing contracts and screen tests

- [ ] Six tracking pairs.
- [ ] Package filters, mine by nullable user ID and stale-plan warning.
- [ ] Detail planned/projected/actual dates.
- [ ] Register and clear actual date.
- [ ] Server-today expiration cuts, step/responsible filters and no-date disclosure.
- [ ] Three cash-flow origins, coverage, excluded and exact residual.
- [ ] CSV BOM, semicolon, method and safe filename.
- [ ] Read-only tracking still downloads CSV.

### Step 2: Run RED

```bash
docker compose exec app php tests/test_pdc_react_tracking_contract.php
npm --prefix frontend test -- src/modules/plan-compras/api/schemas/tracking.test.ts src/modules/plan-compras/domain/tracking.test.ts src/modules/plan-compras/domain/expirations.test.ts src/modules/plan-compras/domain/cashFlow.test.ts src/modules/plan-compras/tracking/PurchaseTrackingPage.test.tsx src/lib/api/cliente.test.ts
```

### Step 3: Implement the tracking vertical

- [ ] Port filters and date labels.
- [ ] Keep server `hoy` as authority.
- [ ] Use `user.id`, never username/role, for Mine.
- [ ] Validate ISO date or null and preserve detail on error.
- [ ] Render cash chart plus equivalent table.
- [ ] Download through the common client.

### Step 4: Run GREEN and download scenario

```bash
docker compose exec app php tests/test_pdc_react_tracking_contract.php
npm --prefix frontend test -- src/modules/plan-compras/tracking src/modules/plan-compras/api/schemas/tracking.test.ts src/modules/plan-compras/domain/tracking.test.ts src/modules/plan-compras/domain/expirations.test.ts src/modules/plan-compras/domain/cashFlow.test.ts src/lib/api/cliente.test.ts
npx playwright test tests/browser/react-plan-compras-tracking.spec.mjs --workers=1
```

### Step 5: Review and future commit

- [ ] Confirm browser interception produced the CSV and no real download request escaped.
- [ ] Future commit: `feat(pdc-react): port purchase tracking and cash flow`.

## Task 12: Integrate all routes, responsive parity, themes, help and RBAC

**Files:**

- Create/complete `PlanComprasRoutes.tsx`, `PlanComprasLayout.tsx` and module CSS.
- Modify the T01 route registry or `frontend/src/shell/rutas.tsx` for pilot only.
- Complete all browser specs and support files.
- Update `docs/design-system/manifests/plan-compras-v2.json` as a candidate, without changing golden
  hashes before approval.

### Step 1: Write failing aggregate route and quality tests

- [ ] Eight pilot routes and root redirect.
- [ ] Seven nav entries, active state and Steps back link.
- [ ] Help on all eight; tour six; localStorage failures nonfatal.
- [ ] A/D/OT/R/DCV/V/G/C action outcomes from intercepted context.
- [ ] Loading/empty/filter-empty/read-only/error matrix.
- [ ] 390/480/768/1180/1440 exclusive representations.
- [ ] Dark/light, focus, keyboard, touch, zoom and reduced motion.
- [ ] Any unmocked request fails.

### Step 2: Run RED

```bash
npm --prefix frontend test -- src/modules/plan-compras src/shell
npx playwright test tests/browser/react-plan-compras-routes.spec.mjs tests/browser/react-plan-compras-responsive-a11y.spec.mjs --workers=1
```

### Step 3: Complete pilot integration

- [ ] Lazy-load the module at `/app/plan-compras`.
- [ ] Mount all eight route components.
- [ ] Keep legacy `/plan-compras` canonical.
- [ ] Finish token-only CSS and AG Grid theme bridge.
- [ ] Ensure grid/cards share one collection and only one mounts.
- [ ] Complete help/tour and focus/live regions.
- [ ] Update manifest sources/routes/states/viewports/themes without approving images.

### Step 4: Run the full safe PDC React gate

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
docker compose exec app php tests/test_pdc_react_route_inventory.php
docker compose exec app php tests/test_pdc_react_context_contract.php
docker compose exec app php tests/test_pdc_react_budget_contract.php
docker compose exec app php tests/test_pdc_react_master_contract.php
docker compose exec app php tests/test_pdc_react_packages_contract.php
docker compose exec app php tests/test_pdc_react_subpackages_contract.php
docker compose exec app php tests/test_pdc_react_plan_contract.php
docker compose exec app php tests/test_pdc_react_steps_contract.php
docker compose exec app php tests/test_pdc_react_tracking_contract.php
npx playwright test tests/browser/react-plan-compras-routes.spec.mjs tests/browser/react-plan-compras-import.spec.mjs tests/browser/react-plan-compras-master.spec.mjs tests/browser/react-plan-compras-budget.spec.mjs tests/browser/react-plan-compras-packages.spec.mjs tests/browser/react-plan-compras-plan.spec.mjs tests/browser/react-plan-compras-steps.spec.mjs tests/browser/react-plan-compras-tracking.spec.mjs tests/browser/react-plan-compras-responsive-a11y.spec.mjs --workers=1
```

Read every RC separately. Review console, network and downloads. Visual comparisons remain candidates
until explicit approval.

### Step 5: Review and future commit

- [ ] Compare every S12 acceptance criterion to evidence.
- [ ] Future commit: `feat(pdc-react): complete pilot parity`.

## Task 13: Cut canonical routes and retire the separate island

**Files:**

- Modify `src/Core/SpaRouter.php`, `tests/test_spa_frontera.php`,
  `tests/test_pdc_react_spa_boundary.php` and `public/index.php`.
- Modify T01 route registry/sidebar destination.
- Modify manifest/wiki/deploy/gate files listed in File Structure.
- Delete only the zero-consumer island/view/controller/assets listed in File Structure.
- Update browser specs from pilot base to canonical base.

### Step 1: Write failing canonical boundary and retirement tests

- [ ] `/plan-compras` and eight descendants classify as SPA.
- [ ] `/plan-compras/api` exact/descendant classify as API, never SPA.
- [ ] The old hash bridge runs once on canonical entry.
- [ ] The old page controller route is absent after cut.
- [ ] APIs remain 69/69.
- [ ] Source audit finds no runtime import/link/build reference to separate assets.
- [ ] Main bundle owns the module and lazy AG Grid chunk.

### Step 2: Prove consumers and exercise rollback before deletion

```bash
rg -n "PlanComprasController|views/plan-compras/app.view.php|/pdc-app/assets|pdc-app/src|window\\.__PDC_BOOTSTRAP__" public src views frontend scripts .github tests package.json
rg -n "plan-compras" public/index.php src/Core/SpaRouter.php frontend/src tests
docker compose exec app php tests/test_pdc_react_spa_boundary.php
```

- [ ] With the island still present, switch the local route gate to legacy and prove it loads.
- [ ] Switch back to SPA and rerun the boundary test.
- [ ] Record the exact remaining consumers and update only active source/config, not historical docs.
- [ ] Stop if any exclusive asset has a live consumer.

### Step 3: Perform the canonical cut

- [ ] Exclude `/plan-compras/api` before adding `/plan-compras` to `RUTAS_MIGRADAS`.
- [ ] Remove only the page-controller route; retain all 69 API routes.
- [ ] Point sidebar/navigation to canonical nested default.
- [ ] Remove transitional flat context fields and update PHP/Zod contracts.
- [ ] Delete VIEW-31, controller, island source and generated assets after proof.
- [ ] Retire/retarget dedicated design gate, wiki note and deploy excludes.
- [ ] Update manifest to main sources and canonical scenarios.

### Step 4: Re-run aggregate gate after deletion

```bash
docker compose exec app php tests/test_spa_frontera.php
docker compose exec app php tests/test_pdc_react_spa_boundary.php
docker compose exec app php tests/test_pdc_react_route_inventory.php
docker compose exec app php tests/test_pdc_react_context_contract.php
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
npx playwright test tests/browser/react-plan-compras-routes.spec.mjs tests/browser/react-plan-compras-responsive-a11y.spec.mjs --workers=1
rg -n "PlanComprasController|views/plan-compras/app.view.php|/pdc-app/assets|window\\.__PDC_BOOTSTRAP__" public src views frontend scripts .github tests package.json
git diff --check
```

The final `rg` may find historical test assertions only if deliberately retained and documented; it
must find no runtime/build consumer. Verify all 69 API identities again.

### Step 5: Future atomic cut commit and closure

- [ ] Stage only S12 files; never `.env`, local evidence or unrelated work.
- [ ] Future commit: `feat(pdc-react): cut over plan de compras`.
- [ ] Follow repository PR/CI closure policy only in an authorized implementation session.
- [ ] Deployment remains separately authorized and is not implied by merge.

## Vertical Checkpoints

1. **After Task 3 — foundation:** endpoint inventory, context actions, client, schemas and routing
   primitives are green; no product route changed.
2. **After Task 6 — budget/master:** four screens work in pilot with uploads/read-only/deep links.
3. **After Task 8 — assembly:** packages, wizard and subpackages work; no auto UI invented.
4. **After Task 11 — operations:** plan, steps and tracking work with safe contracts.
5. **After Task 12 — parity:** all eight screens, responsive, themes, RBAC and a11y pass in pilot.
6. **After Task 13 — cut:** API precedence, hash compatibility, rollback and zero consumers pass.

Do not cross a checkpoint with a red focused test, unreviewed contract change, live DML, extra
endpoint, unapproved visual or scope drift.

## Traceability Matrix

| Acceptance | Task(s) | Primary evidence |
|---:|---|---|
| 1 | 3, 12, 13 | route/API precedence tests |
| 2 | 1, 3, 12 | route/nav inventory |
| 3 | 3, 6, 13 | hash bridge/deep links |
| 4 | 3, 13 | unknown-hash rejection |
| 5 | 3, 12, 13 | shell/nav tests |
| 6 | 3, 12 | fetch source audit |
| 7 | 3, 4, 11 | client-mode tests |
| 8 | 2, 13 | context PHP contracts |
| 9 | 1, 2, 13 | route/payload inventory |
| 10 | 1, 5, 7, 8 | compatibility-only audit |
| 11 | 1, 4-11 | schema coverage registry |
| 12 | 2 | context role absence |
| 13 | 2, 12 | action policy and endpoint guards |
| 14 | 2, 12 | alias/override fixtures |
| 15 | 4 | upload contracts |
| 16 | 4, 6 | version/history flows |
| 17 | 5 | master page tests |
| 18 | 2, 5 | generate-links action |
| 19 | 6 | viewer/tamiz tests |
| 20 | 6 | comparison tests |
| 21 | 7 | package page/wizard |
| 22 | 1, 7 | literal type/modality contract |
| 23 | 8 | subpackage invariants |
| 24 | 9 | plan tabs/actions |
| 25 | 9 | reprogram simulation flow |
| 26 | 10 | Steps screen |
| 27 | 11 | Tracking screen |
| 28 | 3, 11 | download contract |
| 29 | 3, 12 | help/tour contracts |
| 30 | 4-12 | action-aware empty states |
| 31 | 3, 12 | error matrix |
| 32 | 3-12 | mutation recovery |
| 33 | 3, 7, 12 | lazy Community grid |
| 34 | 6-12 | desktop/tablet parity |
| 35 | 4-12 | mobile native parity |
| 36 | 3, 12 | exclusive mounting |
| 37 | 12 | viewport overflow checks |
| 38 | 3, 12 | token/theme tests |
| 39 | 3, 12 | a11y/zoom/touch/motion |
| 40 | 3, 8, 9, 12 | dialog focus tests |
| 41 | 2, 12 | project cache invalidation |
| 42 | 1-13 | fake/intercepted evidence |
| 43 | 4-13 | fail-closed network harness |
| 44 | 13 | route rollback record |
| 45 | 13 | zero-consumer proof |
| 46 | 1, 13 | 69/69 post-cut inventory |
| 47 | 12, 13 | manifest scenarios |
| 48 | 1-13 | forbidden-scope diff audit |

## Verification Commands Explicitly Forbidden in S12

Do not run against the real mounted application/database:

```text
any POST under /plan-compras/api
real budget or SINCO upload
real version activation
real link generation/confirmation
real equipment classification
real package or subpackage mutation
real attach/detach/calculate/responsible/reprogram action
real steps/duration mutation
real tracking date mutation
tests that seed, sandbox, restore or reconcile PDC rows
migration, seed, backfill or schema command
SQL snapshot/restore used to make a mutation appear reversible
```

Existing `tests/test_pdc_v2_*.php` and `tests/browser/pdc-v2-*` are not globally safe. Execute a
specific existing case only after reading it and proving it performs no DML. Prefer the new pure
contracts and fully intercepted browser fixtures.

## Self-Review Checklist for the Executor

- [ ] Correct worktree/branch and T01 dependency confirmed.
- [ ] 69 registered pairs, 65 schemas and four compatibility-only pairs remain exact.
- [ ] Only context changed server shape and has pilot/final PHP contracts.
- [ ] Context exposes actions, never a real role.
- [ ] Alias `P`, overrides and endpoint guards remain server-effective.
- [ ] `/plan-compras/api` is excluded before SPA prefix matching.
- [ ] No fetch outside `cliente.ts`; multipart/download use it too.
- [ ] All wire types originate in strict Zod.
- [ ] Five types/four modalities match PHP; no stale filter.
- [ ] No NUL byte copied.
- [ ] Uploads, versions, master, viewer and compare preserve behavior.
- [ ] Packages/wizard/subpackages preserve provenance and zero sentinel.
- [ ] Plan/reprogramming preserves real dates and explicit simulation.
- [ ] Steps guard split and recalculation remain exact.
- [ ] Tracking uses server today and user ID; CSV is exact.
- [ ] Seven nav links, eight help records and six tour steps remain exact.
- [ ] Hash bridge preserves allowed queries and rejects unknown paths.
- [ ] Grid/card variants mount exclusively and expose equivalent actions.
- [ ] Dark/light and five viewports pass without page overflow.
- [ ] Keyboard, focus, live regions, zoom, touch and motion pass.
- [ ] Browser tests fail on unexpected network and use no credentials.
- [ ] No mutable PDC test was run as safe evidence.
- [ ] No visual baseline changed without approval.
- [ ] Rollback was exercised before deletion.
- [ ] Separate source/assets/controller have zero runtime/build consumers.
- [ ] All 69 APIs remain after cut.
- [ ] No RLS/schema/grant/user/credential/data/admin change.
- [ ] Focused then proportional checks ran with RC captured separately.
- [ ] `git diff --check` is clean and final diff has no unrelated edits.

## Cierre

Estado al redactar este plan: **plan escrito y autorrevisado; implementacion no iniciada**.

No se instalaron dependencias, ejecutaron tests mutables, hicieron DDL/DML, cambiaron RLS/schema,
commits, push, PR, deploy ni publicacion. La implementacion futura debe registrar:

- commits y SHA final realmente verificado;
- comandos y RC separados;
- evidencia de los seis checkpoints;
- 69/69 routes, 65/65 schemas and four compatibility-only pairs;
- decision visual explicita;
- rollback y cero consumidores;
- limites o desviaciones aprobadas;
- PR/CI conforme a politica, sin confundirlo con deploy.
