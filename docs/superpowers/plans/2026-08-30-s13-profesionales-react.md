---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [lps, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s13-profesionales-react.md
resumen: "migrate /profesionales from VIEW-32, jQuery and Handsontable into the main React SPA while preserving project-scoped list, twelve cargos, manual/admin lock…"
---

# S13 Profesionales React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Execute tasks in order and stop at every vertical checkpoint.
> Checkbox syntax is an execution prompt only; progress and closure live in Cierre and git history,
> never in checkbox counts.

**Goal:** migrate /profesionales from VIEW-32, jQuery and Handsontable into the main React SPA while
preserving project-scoped list, twelve cargos, manual/admin lock rules, create, per-row autosave,
active toggle, dependency-safe rename/delete, explicit member synchronization, reload, CSV and the
authorized BI Responsables link across desktop, tablet, mobile, dark and light.

**Architecture:** T01 owns authentication, selected project, shell, route outlet, theme and the only
HTTP client. S13 adds a narrow PHP application boundary around the existing professional tables and
ProjectProfessionalsSyncService. GET context and GET list are pure. POST sync is the only UI path
that invokes the existing synchronization service. POST save accepts a strict JSON union for
create/update/delete and delegates atomic writes to a service; legacy form/list modes remain only
during the pilot. A native semantic table is mounted at 768+ and native editable cards below 768,
both over one hook and row queue.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe and AIA design-system tokens.

**Spec:** docs/superpowers/specs/2026-08-30-s13-profesionales-react-design.md

## Global Constraints

- Work only in
  /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react on branch
  shell-minimo-react. Never work in /Volumes/Crucial X6/Developer/lps-aia, the parent checkout or
  another worktree.
- Execute only after the T01 shell/router/client contracts consumed here exist and are green. Reuse
  them; do not create another shell, project selector, week selector, sidebar, theme store, session
  store or HTTP client.
- Inspect git status --short and relevant diffs before every task. Preserve unrelated and
  pre-existing edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future instructions and require explicit
  implementation authorization.
- /admin/ is excluded. Do not edit its controllers, views, routes, permissions or tests. The source
  assertions may prove that its existing calls to blockProfessionalByEmail remain intact.
- Do not modify RLS, ProjectScope semantics, schema, migrations, tables, columns, indexes, triggers,
  grants, users, credentials, memberships, roles, role aliases, overrides or database data.
- No DDL/DML is permitted during documentation or safe verification. Future PHP tests use pure
  services, fakes, fixtures and source assertions. Future browser tests install complete network
  interception before navigation.
- Do not call GET or POST /api/profesionales/list against the real runtime during implementation
  verification until POST legacy behavior has been retired: the current controller synchronizes
  and writes.
- Never call real /api/profesionales/sync or /api/profesionales/save in tests for this plan.
  Rollback writes are also DML and do not make the test safe.
- Preserve lps.profesionales.ver and lps.profesionales.editar exactly. PHP resolves capabilities,
  aliases and overrides. React never switches on a raw role.
- Preserve all twelve cargos and their exact order. A cargo addition/removal is a spec amendment,
  not an implementation convenience.
- Preserve the four row-origin states: manual, current unique admin member, removed admin member
  and duplicate admin email.
- Use one canonical five-entry dependency registry for list, rename, delete, dedup characterization
  and tests. Do not add schema or foreign keys.
- Create/update/delete are single-row atomic operations. Do not add a batch endpoint or partial
  success.
- Use Database project-scoped ID allocation. Do not assume AUTO_INCREMENT or calculate an ID in
  React.
- Keep ProjectProfessionalsSyncService and its public methods because /admin/ consumes
  blockProfessionalByEmail. Wrap it behind an injected interface; do not move or rewrite admin
  callers.
- Only frontend/src/lib/api/cliente.ts may call fetch. Gateways and hooks call pedir or the T01
  extension of that client.
- Every consumed response is strict Zod. Wire/domain types come from z.infer; do not duplicate
  manual interfaces that can drift.
- Mutations do not retry automatically. Per-row queues serialize saves and retain drafts on
  recoverable failure.
- Use native table/cards/forms. Do not add Handsontable, AG Grid, DataTables, Bootstrap, jQuery,
  Font Awesome, a state/query/form library or CSS-in-JS.
- At widths below 768 mount cards only. At 768 and above mount the table only.
- Use variables derived from public/css/tokens.css. Do not copy literal colors, inline style
  objects, important or unlayered CSS.
- Dark is default/fallback and light has identical capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output until approved.
- GET context/list are no-store and pure. Reload, CSV and route mount must never invoke sync.
- POST sync requires an explicit user action, edit capability, professionals CSRF and one request
  per confirmation.
- Do not delete VIEW-32, ProfesionalesController, legacy POST list, form save mode or
  public/css/profesionales.css until Task 10 proves zero consumers and exercises rollback.

## File Structure

### Create — PHP application boundary

- src/Services/Professionals/ProfessionalCatalog.php — twelve cargos, normalization and field
  validation independent of HTTP/DB.
- src/Services/Professionals/ProfessionalActionPolicy.php — effective context actions and row
  action composition.
- src/Services/Professionals/ProfessionalDependencyRegistry.php — the five canonical textual
  dependencies and operations through an injected store.
- src/Services/Professionals/ProfessionalStore.php — narrow read/write transaction interface.
- src/Services/Professionals/PdoProfessionalStore.php — project-scoped prepared implementation.
- src/Services/Professionals/ProfessionalReadService.php — pure list projection and row decoration.
- src/Services/Professionals/ProfessionalWriteService.php — create/update/delete orchestration.
- src/Services/Professionals/ProfessionalSynchronizer.php — injectable synchronization port.
- src/Services/Professionals/ProjectProfessionalSynchronizerAdapter.php — derives the server-side
  prefix for the scoped project, then invokes ProjectProfessionalsSyncService.
- src/Services/Professionals/ProfessionalContext.php — strict context serializer.
- tests/Support/Professionals/FakeProfessionalStore.php.
- tests/Support/Professionals/FakeProfessionalSynchronizer.php.
- tests/fixtures/professionals-react/rows.php.
- tests/fixtures/professionals-react/contracts.php.
- tests/test_professionals_react_domain.php.
- tests/test_professionals_react_policy.php.
- tests/test_professionals_react_read_contract.php.
- tests/test_professionals_react_write_contract.php.
- tests/test_professionals_react_sync_contract.php.
- tests/test_professionals_react_routes.php.
- tests/test_professionals_react_source_invariants.php.

### Create — React/TypeScript

- frontend/src/modules/profesionales/api/esquemas.ts.
- frontend/src/modules/profesionales/api/gateway.ts.
- frontend/src/modules/profesionales/dominio/profesional.ts.
- frontend/src/modules/profesionales/dominio/validacion.ts.
- frontend/src/modules/profesionales/dominio/colaPorFila.ts.
- frontend/src/modules/profesionales/useProfesionales.ts.
- frontend/src/modules/profesionales/RutaProfesionales.tsx.
- frontend/src/modules/profesionales/BarraProfesionales.tsx.
- frontend/src/modules/profesionales/TablaProfesionales.tsx.
- frontend/src/modules/profesionales/TarjetasProfesionales.tsx.
- frontend/src/modules/profesionales/AltaProfesional.tsx.
- frontend/src/modules/profesionales/DialogoEliminarProfesional.tsx.
- frontend/src/modules/profesionales/EstadoGuardadoProfesional.tsx.
- frontend/src/modules/profesionales/profesionales.css.
- Colocated test files for every schema, gateway, domain helper, hook and component above.
- frontend/src/lib/archivos/csv.ts — shared strict CSV serializer reusable by S14 and other modules.
- frontend/src/lib/archivos/csv.test.ts.
- tests/browser/profesionales-react.spec.mjs.
- tests/browser/profesionales-react.a11y.mjs.
- tests/browser/profesionales-react.visual.mjs.
- tests/browser/fixtures/profesionales-react.mjs.

### Modify during implementation

- public/index.php — add context/sync routes and target existing list/save to the narrow boundary
  without removing pilot aliases early.
- src/Controllers/Api/ProfesionalesApiController.php — thin transport, method/content-type
  compatibility dispatch and stable errors.
- src/Core/SpaRouter.php — add /profesionales only in Task 10.
- frontend/src/shell/rutas.tsx — mount /app/profesionales during pilot and canonical route at cut.
- frontend/src/shell/NavegacionLateral.tsx — consume T01 capability navigation; remove no role logic
  solely for S13 if T01 has not already done so.
- frontend/src/lib/api/cliente.ts — only if the T01 version still lacks reusable JSON CSRF/error
  support; extend centrally with tests, never wrap fetch elsewhere.
- docs/design-system/manifests/profesionales.json.
- docs/design-system/auditoria/censo-modulos.json and generated route inventory only through their
  canonical generator at the cut.
- tests/browser/shell-sidebar-rollout.mjs.
- tests/browser/support/session.mjs and moduleFlows.mjs only after classifying/removing every real
  mutation path from S13 scenarios.
- tests/test_csrf_modulos_api.php only by replacing its real-write-prone contract with a pure/source
  contract; do not run it against shared data as S13 evidence.
- docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md at closure status only.

### Delete at canonical cut only

- views/profesionales/profesionales.view.php.
- public/css/profesionales.css.
- src/Controllers/Gestion/ProfesionalesController.php if no non-page consumer remains.
- POST /api/profesionales/list route.
- Form-urlencoded branch of POST /api/profesionales/save.
- Exclusive VIEW-32 script/vendor references made unreachable by the deletion.

Never delete src/Services/ProjectProfessionalsSyncService.php or its public API while /admin/ or
another active caller remains.

## Exact Contracts to Implement

### Server context

~~~text
S13-API-01 GET  /api/profesionales/context
S13-API-02 GET  /api/profesionales/list
S13-API-03 POST /api/profesionales/sync
S13-API-04 POST /api/profesionales/save
~~~

Compatibility:

~~~text
S13-COMP-01 POST /api/profesionales/list
S13-COMP-02 POST form /api/profesionales/save
~~~

The target never accepts db, Base_de_Datos, project_id or role as authority. A request that includes
one of those authority keys in target JSON is invalid rather than silently trusted.

### Context actions

~~~text
view
create
editIdentity
editActive
delete
sync
exportCsv
openBi
~~~

view/exportCsv require ver. Five mutations require editar. openBi comes from the BI policy and an
authorized /bi/responsables href. Context includes the twelve cargos and a professionals-purpose
CSRF token only when a mutation is available.

### Row wire shape

~~~text
id: positive integer
nombre: nonempty canonical string
email: canonical lowercase valid email
cargo: allowed cargo or empty only for an admin-duplicate blocked row
activo: boolean
has_dependencies: boolean
is_admin_managed: boolean
is_current_member: boolean
is_blocked: boolean
block_reason: nonempty string | null
can_edit_identity: boolean
can_edit_active: boolean
identity_edit_reason: nonempty string | null
active_edit_reason: nonempty string | null
can_delete: boolean
delete_reason: nonempty string | null
~~~

The PHP serializer and Zod refinements enforce coherent reasons/actions. A blocked row cannot expose
any row mutation. An admin-managed row cannot expose identity/delete.

### Save union

~~~text
create: {action, professional:{nombre,email,cargo}}
update: {action, id, changes:{one or more of nombre,email,cargo,activo}}
delete: {action, id}
~~~

Unknown keys, empty update changes, non-positive IDs, numeric booleans and invalid fields fail.
Create/update return the full canonical row. Delete returns deletedId. Stable errors follow the
codes in the spec.

### Synchronization summary

~~~text
inserted
reactivated
updated
blocked
deduplicated
warnings
~~~

All counters are non-negative integers. reactivated remains characterized even though the audited
service never increments it. Ordinary load/reload must prove zero synchronizer calls.

### Frontend gateway

~~~text
getProfessionalContext(signal?)
listProfessionals(signal?)
syncProfessionals(csrfToken)
saveProfessional(command, csrfToken)
~~~

Every method delegates transport to cliente.ts and validates a strict schema. Reads accept
AbortSignal. Mutations do not accept automatic retry or abort after dispatch.

## Task 1: Characterize catalog, normalization, locks, dependencies and action policy

**Files:**

- Create: src/Services/Professionals/ProfessionalCatalog.php
- Create: src/Services/Professionals/ProfessionalActionPolicy.php
- Create: src/Services/Professionals/ProfessionalDependencyRegistry.php
- Create: tests/fixtures/professionals-react/rows.php
- Create: tests/test_professionals_react_domain.php
- Create: tests/test_professionals_react_policy.php
- Create: tests/test_professionals_react_source_invariants.php
- Read only: src/Services/ProjectProfessionalsSyncService.php
- Read only: src/Security/RbacCatalog.php

- [ ] **Step 1: Write failing catalog and normalization characterization**

  Assert all twelve cargo labels/order, trim/collapse rules, lowercase email, valid/invalid email,
  strict boolean domain and allowed empty cargo only for the blocked Admin duplicate projection.
  Add source assertions for the eight ROLE_TO_CARGO mappings and current summary fields without
  exposing that private map to React.

- [ ] **Step 2: Run the focused RED tests**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_domain.php
  docker compose exec app php tests/test_professionals_react_source_invariants.php
  ~~~

  Read each RC on its own line. Expected RED is missing production classes, not a DB connection,
  malformed fixture or changed source.

- [ ] **Step 3: Write failing policy/dependency cases**

  Cover view/edit/denied plus overrides through injected booleans; manual/current-admin/
  removed-admin/duplicate-admin/dependent rows; reasons; the five exact dependency descriptors; and
  the rule that list/delete/rename receive the same registry.

- [ ] **Step 4: Implement the minimum pure domain and run GREEN**

  Implement constants, pure normalization/validation, action composition and a dependency registry
  that carries logical table type plus column but performs no IO itself.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_domain.php
  docker compose exec app php tests/test_professionals_react_policy.php
  docker compose exec app php tests/test_professionals_react_source_invariants.php
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm no /admin/ diff, no SQL, no role matrix outside the server policy and no changed source
  service. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Professionals/ProfessionalCatalog.php src/Services/Professionals/ProfessionalActionPolicy.php src/Services/Professionals/ProfessionalDependencyRegistry.php tests/fixtures/professionals-react/rows.php tests/test_professionals_react_domain.php tests/test_professionals_react_policy.php tests/test_professionals_react_source_invariants.php
  git commit -m "test: characterize professional domain"
  ~~~

## Task 2: Add pure scoped context and list contracts

**Files:**

- Create: src/Services/Professionals/ProfessionalStore.php
- Create: src/Services/Professionals/PdoProfessionalStore.php
- Create: src/Services/Professionals/ProfessionalReadService.php
- Create: src/Services/Professionals/ProfessionalContext.php
- Create: tests/Support/Professionals/FakeProfessionalStore.php
- Create: tests/fixtures/professionals-react/contracts.php
- Create: tests/test_professionals_react_read_contract.php
- Create: tests/test_professionals_react_routes.php
- Modify: src/Controllers/Api/ProfesionalesApiController.php
- Modify: public/index.php

- [ ] **Step 1: Write failing read/context contracts**

  Use a fake store and fake project scope to assert exact context/list envelopes, integer/boolean
  serialization, twelve cargos, BI nullable invariant, CSRF nullable invariant, order by id, empty
  success, all four row states and coherent reasons.

- [ ] **Step 2: Add purity, scope and route RED cases**

  Install fake tripwires for transaction/write/synchronizer calls. Assert GET list makes none,
  rejects db/project_id/role authority, routes context/list by exact method, requires view and emits
  no-store/stable errors. Keep POST list routed to the legacy path during pilot.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_read_contract.php
  docker compose exec app php tests/test_professionals_react_routes.php
  ~~~

- [ ] **Step 3: Implement repository, read service, context and thin transport**

  Resolve projectId from current ProjectScope. PdoProfessionalStore uses TableResolver::resolve with
  projectId, prepared statements and the canonical dependency registry. Context uses effective RBAC,
  BiAccessComponent for /bi/responsables and CsrfTokenManager for professionals. GET list bypasses
  sync entirely.

- [ ] **Step 4: Run focused GREEN plus legacy compatibility assertions**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_domain.php
  docker compose exec app php tests/test_professionals_react_policy.php
  docker compose exec app php tests/test_professionals_react_read_contract.php
  docker compose exec app php tests/test_professionals_react_routes.php
  ~~~

  Source-assert that POST list still reaches the compatibility branch and GET does not.

- [ ] **Step 5: Review and future atomic commit**

  Inspect every SQL statement for project_id, ensure errors omit exceptions and verify no runtime
  HTTP request was made. In an authorized session only:

  ~~~bash
  git diff --check
  git add src/Services/Professionals src/Controllers/Api/ProfesionalesApiController.php public/index.php tests/Support/Professionals tests/fixtures/professionals-react/contracts.php tests/test_professionals_react_read_contract.php tests/test_professionals_react_routes.php
  git commit -m "feat: add pure professional read contracts"
  ~~~

## Task 3: Add strict Zod schemas, gateway, domain helpers and CSV primitive

**Files:**

- Create: frontend/src/modules/profesionales/api/esquemas.ts
- Create: frontend/src/modules/profesionales/api/esquemas.test.ts
- Create: frontend/src/modules/profesionales/api/gateway.ts
- Create: frontend/src/modules/profesionales/api/gateway.test.ts
- Create: frontend/src/modules/profesionales/dominio/profesional.ts
- Create: frontend/src/modules/profesionales/dominio/profesional.test.ts
- Create: frontend/src/modules/profesionales/dominio/validacion.ts
- Create: frontend/src/modules/profesionales/dominio/validacion.test.ts
- Create: frontend/src/lib/archivos/csv.ts
- Create: frontend/src/lib/archivos/csv.test.ts
- Modify only if required by T01: frontend/src/lib/api/cliente.ts and its test

- [ ] **Step 1: Write failing strict schema tests**

  Cover context, sixteen row fields, list, sync summary, save success union and stable error. Reject
  string IDs, numeric booleans, extra keys, missing reasons, forbidden actions on blocked/admin rows,
  invalid BI link/token invariants and malformed error fields.

- [ ] **Step 2: Run schema RED**

  ~~~bash
  npm --prefix frontend test -- src/modules/profesionales/api/esquemas.test.ts
  ~~~

  Expected RED is missing schema implementation only.

- [ ] **Step 3: Write failing gateway/domain/CSV tests**

  Assert exact four routes/methods, AbortSignal on reads, JSON body, professionals CSRF header,
  single-client delegation, no retry, normalizers, field errors, seven CSV columns, BOM, CRLF,
  quotes/commas/newlines and deterministic filename input.

- [ ] **Step 4: Implement minimum helpers and run GREEN**

  Infer all wire types from Zod. Keep server strings as wire fields and create display helpers
  separately. Add a generic CSV serializer without DOM side effects; a module helper will own Blob
  download later.

  ~~~bash
  npm --prefix frontend test -- src/modules/profesionales src/lib/archivos/csv.test.ts
  npm --prefix frontend run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Search for forbidden fetch outside cliente.ts and duplicated manual wire interfaces:

  ~~~bash
  rg -n "fetch\\(" frontend/src --glob "!lib/api/cliente.ts" --glob "!*.test.ts"
  rg -n "interface .*Profes|type .*Profes" frontend/src/modules/profesionales
  git diff --check
  ~~~

  In an authorized session only, stage these exact files and commit feat: add professional frontend
  contracts.

## Task 4: Build the read-only responsive vertical slice

**Files:**

- Create: frontend/src/modules/profesionales/useProfesionales.ts
- Create: frontend/src/modules/profesionales/useProfesionales.test.tsx
- Create: frontend/src/modules/profesionales/RutaProfesionales.tsx
- Create: frontend/src/modules/profesionales/RutaProfesionales.test.tsx
- Create: frontend/src/modules/profesionales/BarraProfesionales.tsx
- Create: frontend/src/modules/profesionales/TablaProfesionales.tsx
- Create: frontend/src/modules/profesionales/TarjetasProfesionales.tsx
- Create: frontend/src/modules/profesionales/EstadoGuardadoProfesional.tsx
- Create: frontend/src/modules/profesionales/profesionales.css
- Modify: frontend/src/shell/rutas.tsx

- [ ] **Step 1: Write failing route/hook load-state tests**

  Cover context/list parallel load, cancellation, access denied, context error, list error, empty,
  readonly, editable, stale-data reload and the invariant that load/reload never calls sync.

- [ ] **Step 2: Write failing table/card parity tests**

  Assert the same five visible data fields, state/origin text, row reasons and allowed actions in
  both presentations; table semantics at 768+; cards below 768; only one presentation mounted; no
  week selector/drawer; BI only when context supplies the exact href.

- [ ] **Step 3: Run component RED**

  ~~~bash
  npm --prefix frontend test -- src/modules/profesionales/useProfesionales.test.tsx src/modules/profesionales/RutaProfesionales.test.tsx
  ~~~

- [ ] **Step 4: Implement the read slice and run GREEN**

  Add the pilot route /app/profesionales. Use matchMedia through a tested shared T01 hook if one
  exists; otherwise add the smallest module-local hook and migrate it to T01 when shared. Render
  native readonly controls for viewer and accessible lock reasons.

  ~~~bash
  npm --prefix frontend test -- src/modules/profesionales
  npm --prefix frontend run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Inspect DOM for one main/h1 in shell composition, one table/cards branch, no role switch, no
  literal colors and no hidden duplicated UI. Future authorized commit: feat: add professional
  responsive read slice.

## Task 5: Implement strict create and accessible draft recovery

**Files:**

- Create: src/Services/Professionals/ProfessionalWriteService.php
- Create: tests/test_professionals_react_write_contract.php
- Modify: src/Controllers/Api/ProfesionalesApiController.php
- Create: frontend/src/modules/profesionales/AltaProfesional.tsx
- Create: frontend/src/modules/profesionales/AltaProfesional.test.tsx
- Modify: frontend/src/modules/profesionales/useProfesionales.ts
- Modify: frontend/src/modules/profesionales/RutaProfesionales.tsx
- Modify: frontend src module tests

- [ ] **Step 1: Write failing PHP create contracts**

  Cover strict JSON action, CSRF, edit permission, project scope, normalization, twelve cargos,
  duplicate email, admin-managed email, invalid/extra fields, project-scoped ID allocator, canonical
  returned row and stable 400/403/409/422/500 errors through fake store.

- [ ] **Step 2: Write failing create UI tests and run RED**

  Cover explicit submit, field association, invalid focus, no request for partial draft, double
  submit lock, retained draft on failure, clear/focus after success, sorted returned row and both
  desktop row/mobile card presentation.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales/AltaProfesional.test.tsx
  ~~~

- [ ] **Step 3: Implement create transaction and JSON transport**

  Decode content type strictly. Delegate JSON create to ProfessionalWriteService. Keep form create
  compatibility isolated. Recheck email/scoped locks in the transaction and return the read
  serializer output, not hand-built divergent fields.

- [ ] **Step 4: Implement create UI and run GREEN**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales
  npm --prefix frontend run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm no live request, no ID calculation, no automatic create on last field and no form mode
  deletion yet. Future authorized commit: feat: add professional creation.

## Task 6: Implement per-row autosave, validation and recovery

**Files:**

- Create: frontend/src/modules/profesionales/dominio/colaPorFila.ts
- Create: frontend/src/modules/profesionales/dominio/colaPorFila.test.ts
- Modify: src/Services/Professionals/ProfessionalWriteService.php
- Modify: tests/test_professionals_react_write_contract.php
- Modify: frontend/src/modules/profesionales/useProfesionales.ts
- Modify: frontend/src/modules/profesionales/TablaProfesionales.tsx
- Modify: frontend/src/modules/profesionales/TarjetasProfesionales.tsx
- Modify: frontend/src/modules/profesionales/EstadoGuardadoProfesional.tsx
- Modify: colocated tests

- [ ] **Step 1: Write failing PHP update contracts**

  Cover partial strict changes merged into the current row, full-row validation, manual identity,
  current-admin active-only, removed/duplicate blocked, not found, duplicate email, canonical row,
  no-op response and no partial multi-row outcome.

- [ ] **Step 2: Write failing queue and interaction tests**

  Cover blur/Enter text saves, select/checkbox immediate save, Escape revert, coalesced same-row
  commands, parallel different rows, canonical response replacement, aria-busy per row, 422 field
  focus, 409 refresh/reason, 500 retained draft/retry and navigation-pending integration.

- [ ] **Step 3: Run RED**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales
  ~~~

- [ ] **Step 4: Implement update and row queues; run GREEN**

  Keep mutations non-abortable after dispatch and never retry automatically. Compose global context
  permission with row actions in UI and re-resolve server rules in the service.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales
  npm --prefix frontend run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Use fake call logs to prove ordering and no duplicate send. Verify a viewer has no editable
  controls and blocked reasons remain focusable. Future authorized commit: feat: add professional
  autosave.

## Task 7: Make rename and deletion dependency-safe and atomic

**Files:**

- Modify: src/Services/Professionals/ProfessionalDependencyRegistry.php
- Modify: src/Services/Professionals/PdoProfessionalStore.php
- Modify: src/Services/Professionals/ProfessionalWriteService.php
- Modify: tests/Support/Professionals/FakeProfessionalStore.php
- Modify: tests/test_professionals_react_write_contract.php
- Create: frontend/src/modules/profesionales/DialogoEliminarProfesional.tsx
- Create: frontend/src/modules/profesionales/DialogoEliminarProfesional.test.tsx
- Modify: hook/table/cards and tests

- [ ] **Step 1: Write failing atomic rename tests**

  Use fake transaction logs to assert row plus all five dependency replacements commit together;
  any failure rolls back logically; unchanged normalized name performs no cascade; another project
  is untouched.

- [ ] **Step 2: Write failing delete and dialog tests**

  PHP: manual/no dependency success; each of five dependencies conflicts; admin/blocked/not-found;
  dependency appearing after list; activity log only after commit. UI: accessible confirmation,
  cancel/Escape/focus return, aria-disabled reason, one request, conflict refresh and post-delete
  focus placement.

- [ ] **Step 3: Run RED**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales/DialogoEliminarProfesional.test.tsx
  ~~~

- [ ] **Step 4: Implement one dependency registry path and run GREEN**

  PdoProfessionalStore resolves all five tables through projectId and prepared queries. Keep the
  transaction boundary in ProfessionalWriteService. Do not alter schema or shared tables.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_write_contract.php
  npm --prefix frontend test -- src/modules/profesionales
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Search for ad hoc dependency arrays outside the registry and projectless UPDATE/DELETE. Future
  authorized commit: fix: protect professional dependency writes.

## Task 8: Add explicit synchronization without changing admin consumers

**Files:**

- Create: src/Services/Professionals/ProfessionalSynchronizer.php
- Create: src/Services/Professionals/ProjectProfessionalSynchronizerAdapter.php
- Create: tests/Support/Professionals/FakeProfessionalSynchronizer.php
- Create: tests/test_professionals_react_sync_contract.php
- Modify: src/Controllers/Api/ProfesionalesApiController.php
- Modify: public/index.php
- Modify: frontend/src/modules/profesionales/BarraProfesionales.tsx
- Modify: frontend/src/modules/profesionales/useProfesionales.ts
- Modify: colocated tests
- Read/source assert only: admin/src/Controllers/ProjectController.php
- Read/source assert only: admin/src/Controllers/UserController.php

- [ ] **Step 1: Write failing sync route/service contracts**

  Assert POST only, edit, CSRF, server-derived prefix verified against projectId, one fake sync call,
  five counters, deduplicated warnings, stable failure and no list write. Assert context/list/reload
  make zero synchronizer calls.

- [ ] **Step 2: Write failing UI confirmation/recovery tests**

  Cover hidden for viewer, explanatory confirmation, one dispatch, busy lock, success summary,
  warnings, explicit pure list reload after success, failure preserving rows and manual retry.

- [ ] **Step 3: Run RED**

  ~~~bash
  docker compose exec app php tests/test_professionals_react_sync_contract.php
  npm --prefix frontend test -- src/modules/profesionales
  ~~~

- [ ] **Step 4: Implement adapter, endpoint and UI; run GREEN**

  Adapter is the only new caller of syncProjectProfessionals. It obtains Base_de_Datos from a
  server-side project lookup and verifies the same active scoped project. Do not change
  blockProfessionalByEmail or admin controllers.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_sync_contract.php
  docker compose exec app php tests/test_professionals_react_source_invariants.php
  npm --prefix frontend test -- src/modules/profesionales
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Source-prove existing /admin/ callers remain byte-for-byte outside the diff. Prove no GET reaches
  the adapter. Future authorized commit: feat: add explicit professional synchronization.

## Task 9: Complete CSV, BI, RBAC, responsive, accessibility and theme evidence

**Files:**

- Modify: frontend/src/modules/profesionales/BarraProfesionales.tsx
- Modify: frontend/src/modules/profesionales/RutaProfesionales.tsx
- Modify: frontend/src/modules/profesionales/profesionales.css
- Modify: module tests
- Create: tests/browser/fixtures/profesionales-react.mjs
- Create: tests/browser/profesionales-react.spec.mjs
- Create: tests/browser/profesionales-react.a11y.mjs
- Create: tests/browser/profesionales-react.visual.mjs
- Modify: docs/design-system/manifests/profesionales.json only after visual review approval

- [ ] **Step 1: Write failing toolbar/CSV/BI tests**

  Assert seven columns, all/empty export, deterministic filename, object URL cleanup, live status,
  pure reload, sync separation and exact authorized BI href. Viewer retains CSV/reload; denied user
  does not see the module.

- [ ] **Step 2: Add fully intercepted role/scope/interaction scenarios**

  Fixtures cover editor, viewer, denied, manual/admin/removed/duplicate/dependent rows, empty,
  stale/error, create, autosave, conflict, delete, sync warnings/failure and a project-B response.
  Intercept every POST before navigation and fail on any unexpected request.

- [ ] **Step 3: Add responsive/accessibility/theme scenarios**

  Validate five viewports, one table/cards branch, no page overflow, 44px touch, keyboard workflow,
  focus restoration, live regions, reasons, 200 percent zoom, reduced motion, axe, dark/light
  parity, no console errors and no unhandled network calls.

- [ ] **Step 4: Implement polish and run GREEN**

  ~~~bash
  npm --prefix frontend test -- src/modules/profesionales src/lib/archivos/csv.test.ts
  npm --prefix frontend run typecheck
  npx playwright test tests/browser/profesionales-react.spec.mjs tests/browser/profesionales-react.a11y.mjs --workers=1
  ~~~

  Visual test may create candidate output but must not update a golden. Open and inspect candidate
  images manually before requesting approval.

- [ ] **Step 5: Review and future atomic commit**

  Record viewport/theme/axe/network evidence. Update manifest only after approval and with exact
  hash generated by the canonical tool. Future authorized commit: test: verify professional React
  parity.

## Task 10: Cut the canonical route and retire exclusive legacy pieces

**Files:**

- Modify: src/Core/SpaRouter.php
- Modify: public/index.php
- Modify: frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/NavegacionLateral.tsx if needed
- Modify: tests/test_professionals_react_routes.php
- Modify: tests/browser/shell-sidebar-rollout.mjs
- Modify after classification: tests/browser/support/session.mjs
- Modify after classification: tests/browser/support/moduleFlows.mjs
- Delete only with zero callers: views/profesionales/profesionales.view.php
- Delete only with zero callers: public/css/profesionales.css
- Delete only with zero callers: src/Controllers/Gestion/ProfesionalesController.php
- Retire only with zero callers: POST list and form save compatibility
- Modify: program atlas/status and generated route/design inventories

- [ ] **Step 1: Write failing canonical route and retirement tests**

  Assert /profesionales serves SPA, /api/profesionales paths never do, deep link/refresh work,
  unauthenticated shell behavior remains T01, POST list is absent or 410 by chosen global
  deprecation convention, JSON save remains, and source has no VIEW-32/vendor references.

- [ ] **Step 2: Prove zero callers and shared owners before deletion**

  ~~~bash
  rg -n "profesionales\\.view|ProfesionalesController|public/css/profesionales\\.css|/api/profesionales/list|/api/profesionales/save|ProjectProfessionalsSyncService|blockProfessionalByEmail" public src views frontend tests docs admin
  rg -n "profesionales/list|profesionales/save" public/js views frontend tests
  ~~~

  Classify every match. Keep ProjectProfessionalsSyncService/admin callers and any shared API used
  by S07/S08. Do not weaken or delete a test merely because it performs unsafe DML; replace it with
  an intercepted/contract equivalent first.

- [ ] **Step 3: Perform cut and focused GREEN**

  Add exact /profesionales SPA classification after API exclusions. Remove only exclusive aliases,
  view/controller/CSS with zero callers. Update sidebar/navigation, route inventory and manifest.

  ~~~bash
  docker compose exec app php tests/test_professionals_react_routes.php
  docker compose exec app php tests/test_professionals_react_source_invariants.php
  npm --prefix frontend test -- src/modules/profesionales src/shell
  npm --prefix frontend run typecheck
  ~~~

- [ ] **Step 4: Exercise rollback, then final verification**

  Revert only the route/cut diff locally to prove VIEW-32 recovery path, reapply it without touching
  data, and run:

  ~~~bash
  docker compose exec app php tests/test_professionals_react_domain.php
  docker compose exec app php tests/test_professionals_react_policy.php
  docker compose exec app php tests/test_professionals_react_read_contract.php
  docker compose exec app php tests/test_professionals_react_write_contract.php
  docker compose exec app php tests/test_professionals_react_sync_contract.php
  docker compose exec app php tests/test_professionals_react_routes.php
  docker compose exec app php tests/test_professionals_react_source_invariants.php
  npm --prefix frontend test
  npm --prefix frontend run typecheck
  npx playwright test tests/browser/profesionales-react.spec.mjs tests/browser/profesionales-react.a11y.mjs --workers=1
  git diff --check
  git status --short
  ~~~

  Read every RC separately. Confirm browser interception logs contain zero real mutations.

- [ ] **Step 5: Future atomic cut commit and closure workflow**

  In an authorized implementation session, selectively stage the classified cut, commit, then
  follow the repository PR/CI closure policy. Do not deploy. Record verified SHA and update the S13
  closure ledger only after CI is green.

## Vertical Checkpoints

| Checkpoint | Tasks | Demonstrable outcome | Stop condition |
|---|---|---|---|
| V1 | 1–2 | Pure scoped context/list with exact rows and zero DML | Any GET reaches sync/write or leaks authority |
| V2 | 3–4 | Strict frontend contracts and responsive readonly route | Zod drift, duplicate UI or fetch outside client |
| V3 | 5–6 | Create plus serialized per-row autosave | Partial success, lost draft or wrong row permission |
| V4 | 7–8 | Atomic dependencies/delete and explicit sync | Project leak, non-atomic rename or changed admin caller |
| V5 | 9 | CSV, BI, RBAC, a11y, themes and viewports evidenced | Unexpected network call, overflow or a11y failure |
| V6 | 10 | Canonical SPA route, legacy retirement and rollback proof | Unclassified caller, dirty tree or failed verification |

Do not start the next checkpoint while the previous stop condition is unresolved.

## Traceability Matrix

| Acceptance | Tasks | Primary evidence |
|---|---|---|
| S13-AC-01 | 4, 10 | route/component and SPA boundary tests |
| S13-AC-02 | 1, 8, 10 | source invariant and diff audit |
| S13-AC-03 | 2, 4, 8 | purity tripwire and hook tests |
| S13-AC-04 | 8 | sync route/permission/CSRF contract |
| S13-AC-05 | 2, 5–8 | scoped fake/store contracts |
| S13-AC-06 | 2, 3, 5 | authority rejection tests |
| S13-AC-07 | 2, 9 | context/list permission cases |
| S13-AC-08 | 5–8 | mutation permission cases |
| S13-AC-09 | 1, 2, 9 | effective policy fixtures |
| S13-AC-10 | 3, 4, 9 | source search and component tests |
| S13-AC-11 | 1, 2, 3 | catalog PHP/Zod tests |
| S13-AC-12 | 2, 3 | serializer and strict row schema |
| S13-AC-13 | 2, 4 | empty contract/UI state |
| S13-AC-14 | 1, 3, 5, 6 | normalizer and write contracts |
| S13-AC-15 | 1, 5, 6 | validation/conflict tests |
| S13-AC-16 | 1, 5 | catalog validation tests |
| S13-AC-17 | 5 | create component/gateway tests |
| S13-AC-18 | 6 | interaction/autosave tests |
| S13-AC-19 | 6 | per-row queue call log |
| S13-AC-20 | 5, 6 | canonical response merge tests |
| S13-AC-21 | 1, 4, 6 | admin identity readonly cases |
| S13-AC-22 | 1, 6 | active-only policy/write cases |
| S13-AC-23 | 1, 2, 6 | removed member lock cases |
| S13-AC-24 | 1, 2, 6 | duplicate Admin lock cases |
| S13-AC-25 | 4, 7, 9 | focused reason/a11y evidence |
| S13-AC-26 | 1, 7 | five dependency delete cases |
| S13-AC-27 | 7 | transaction call-log tests |
| S13-AC-28 | 7 | delete revalidation contracts |
| S13-AC-29 | 5–7 | strict single-row union contracts |
| S13-AC-30 | 2, 4, 8 | pure reload tripwire |
| S13-AC-31 | 3, 8 | sync schema/PHP contract |
| S13-AC-32 | 8 | sync failure UI test |
| S13-AC-33 | 3, 9 | CSV unit/component evidence |
| S13-AC-34 | 2, 3, 9 | scoped list/export fixture |
| S13-AC-35 | 2, 4, 9 | BI link invariant/tests |
| S13-AC-36 | 4, 9 | DOM absence assertions |
| S13-AC-37 | 4, 6, 7, 9 | desktop/tablet parity scenarios |
| S13-AC-38 | 4, 6, 7, 9 | mobile parity scenarios |
| S13-AC-39 | 4, 9 | one-branch mount assertions |
| S13-AC-40 | 9 | five viewport measurements |
| S13-AC-41 | 9 | dark/light scenarios |
| S13-AC-42 | 4, 7, 9 | keyboard/focus/axe/zoom evidence |
| S13-AC-43 | 3, 10 | fetch source search |
| S13-AC-44 | 3 | strict schema tests |
| S13-AC-45 | 2, 5, 7, 8 | PHP route/contract tests |
| S13-AC-46 | 2, 5, 7, 8 | stable error snapshots |
| S13-AC-47 | 1–10 | fakes/interception logs |
| S13-AC-48 | 1–10 | scoped diff and schema audit |
| S13-AC-49 | 10 | zero-caller proof |
| S13-AC-50 | 1, 8, 10 | admin caller source invariants |
| S13-AC-51 | 10 | route rollback rehearsal |
| S13-AC-52 | 9, 10 | visual artifact/status audit |

## Verification Commands Explicitly Forbidden in S13

Do not run these as S13 implementation evidence:

~~~text
tests/browser/full-app-flow.spec.mjs
tests/browser/support/operationalCycle.mjs
tests/test_csrf_modulos_api.php against the mounted mutable application
manual GET or POST /api/profesionales/list against Docker before alias retirement
manual POST /api/profesionales/sync
manual POST /api/profesionales/save
any /admin/ flow that blocks/synchronizes professionals
any SQL INSERT, UPDATE, DELETE, REPLACE, ALTER, CREATE, DROP or TRUNCATE
rollback SQL after a write
composer-wide or browser-wide suites whose DML behavior has not been classified
snapshot or golden update flags
~~~

If an executor believes a real mutation is necessary, stop and request new authority. A transaction
rollback is still DML and is not an acceptable workaround.

## Self-Review Checklist for the Executor

- [ ] Correct worktree and branch confirmed; parent checkout untouched.
- [ ] T01 prerequisites are present and green.
- [ ] No unrelated diff was reverted or staged.
- [ ] /admin/ has no diff.
- [ ] Twelve cargos and eight sync role mappings are characterized.
- [ ] Context/list are pure and no-store.
- [ ] Project comes from ProjectScope, not client authority.
- [ ] Effective RBAC is server-side; React has no role matrix.
- [ ] All sixteen row fields have strict PHP/Zod types.
- [ ] Five dependencies use one registry.
- [ ] Rename/delete are atomic/revalidated.
- [ ] Existing admin sync callers remain intact.
- [ ] Create/update/delete are one-row strict commands.
- [ ] Row queues are ordered and mutations are not retried.
- [ ] Table/cards have the same information/actions and only one mounts.
- [ ] No week selector or drawer was introduced.
- [ ] CSV has exact seven columns and safe encoding.
- [ ] BI link is server-authorized.
- [ ] Dark/light, five viewports, zoom, keyboard, touch and reduced motion are evidenced.
- [ ] No fetch exists outside cliente.ts.
- [ ] No real mutation occurred in verification.
- [ ] Visual goldens were not changed without approval.
- [ ] Zero callers and rollback are proven before deletion.
- [ ] Each verification RC was read on its own line.
- [ ] git diff --check is clean.

## Cierre

Estado actual: plan escrito y autorrevisado; no ejecutado.

S13 closes only when an authorized implementation session has completed Tasks 1–10, all vertical
checkpoints are green, every acceptance criterion has evidence, no forbidden DML occurred in tests,
the canonical route cut and rollback were exercised, shared /admin/ consumers remain intact, the
branch is clean, CI on the required PR is green and the verified SHA is recorded. Deployment remains
separately authorized and is not part of this plan.
