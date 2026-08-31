# S14 Subcontratistas React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Execute tasks in order and stop at every vertical checkpoint.
> Checkbox syntax is an execution prompt only; progress and closure live in Cierre and git history,
> never in checkbox counts.

**Goal:** migrate /subcontratistas from VIEW-42, jQuery and Handsontable into the main React SPA
while preserving the Construccion Subcontratistas and Pre-Construccion Interesados Externos modes,
project-scoped read, exact 3/10 catalogs, create, per-row autosave, active toggle, dependency-safe
rename/delete, reload, six-column CSV and authorized BI link across desktop, tablet, mobile, dark
and light.

**Architecture:** T01 owns authentication, selected project, area, shell, route outlet, navigation,
theme and the only HTTP client. S14 adds a narrow PHP boundary around the global subcontratistas
table. GET context and GET list are pure. POST save accepts a strict single-entity JSON union and
delegates all writes to one transactional service. A canonical dependency registry covers CIC,
Programa Intermedia consolidada and Programacion Semanal. Name changes use the shadow-row
transaction required by the existing restrictive CIC foreign key and preserve the public Id.
Legacy POST list and form save remain only during the pilot. One state owner feeds a native table
at 768+ and native editable cards below 768.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe and AIA design-system tokens.

**Spec:** docs/superpowers/specs/2026-08-30-s14-subcontratistas-react-design.md

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
- /admin/ is excluded. Do not edit its controllers, views, routes, permissions or tests.
- Do not modify RLS, ProjectScope semantics, schema, migrations, tables, columns, indexes, foreign
  keys, triggers, grants, users, credentials, memberships, roles, aliases, overrides or data.
- No DDL/DML is permitted during documentation or safe verification. Future PHP tests use pure
  services, fakes, fixtures, transaction call logs and source assertions. Future browser tests
  install complete network interception before navigation.
- Never call real POST /api/subcontratistas/save in tests for this plan. Rollback writes are also
  DML and do not make the test safe.
- Preserve lps.subcontratistas.ver and lps.subcontratistas.editar exactly. PHP resolves effective
  capabilities, role aliases and overrides. React never switches on a raw role.
- Derive mode exclusively from the server-side active project area. Client db, Base_de_Datos,
  project_id, area and role are never authority.
- Preserve the exact three Construccion provider types and ten Pre-Construccion stakeholder types,
  including spelling, accents and order. A catalog change requires a spec amendment.
- Treat NIT/identificacion as a decimal string on the wire. Never coerce it to a JavaScript number.
  Canonical storage input is digits-only, bounded by signed BIGINT and by ten digits in
  Construccion because CIC is narrower.
- Use one canonical three-entry dependency registry for list decoration, delete, rename, active
  consumer characterization and tests. CIC is exact; PI and PS use the characterized comma-token
  representation. Never use substring replacement.
- Name changes are one transaction with the exact shadow-row order defined by the spec. Preserve
  Id; never disable FOREIGN_KEY_CHECKS and never alter the existing FK.
- Create/update/delete are strict single-entity atomic operations. Do not add batch save, partial
  success or automatic mutable retry.
- Use the existing project-scoped ID allocator. Do not assume AUTO_INCREMENT and do not calculate an
  Id in React.
- Management list includes active and inactive rows. Operational projections for S07 and S08 expose
  active rows only and must not duplicate a second catalog authority.
- Do not create a PDC dependency. PDC v1 is deleted and Plan de Compras v2 has no reference to this
  catalog.
- Only frontend/src/lib/api/cliente.ts may call fetch. Gateways and hooks call pedir or the T01
  extension of that client.
- Every consumed response uses a strict Zod schema. Wire/domain types come from z.infer; do not
  duplicate manual interfaces that can drift.
- Reuse frontend/src/lib/archivos/csv.ts from S13. Reuse or extract a shared keyed mutation queue;
  do not copy a module-local queue implementation.
- Mutations do not retry automatically. Queues serialize saves for one Id while allowing different
  rows to save in parallel and preserving drafts on recoverable failure.
- Use native table, cards, forms and dialog. Do not add Handsontable, AG Grid, DataTables,
  Bootstrap, jQuery, Font Awesome, a query/state/form library or CSS-in-JS.
- At widths below 768 mount cards only. At 768 and above mount the table only. CSS hiding both
  branches after mounting is not sufficient.
- Use variables derived from public/css/tokens.css. Do not copy literal colors, add inline style
  objects, important declarations or unlayered module CSS.
- Dark is default/fallback and light has identical capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output until approved.
- GET context/list and reload are no-store and pure. CSV is local and must not trigger an endpoint.
- Do not add a week selector, search, filters, server sorting, contextual drawer or PDC action.
- Do not delete VIEW-42, SubcontratistasController, legacy POST list, form save mode or exclusive CSS
  until Task 10 proves zero consumers and exercises route rollback.

## File Structure

### Create — PHP application boundary

- src/Services/Subcontractors/SubcontractorCatalog.php — modes, exact 3/10 types, labels,
  normalization and validation.
- src/Services/Subcontractors/SubcontractorActionPolicy.php — context and row actions from effective
  capability booleans.
- src/Services/Subcontractors/SubcontractorTokenList.php — characterized comma-token parser,
  normalized exact membership and exact replacement.
- src/Services/Subcontractors/SubcontractorDependencyRegistry.php — canonical CIC/PI/PS descriptors.
- src/Services/Subcontractors/SubcontractorStore.php — narrow scoped read/write/transaction port.
- src/Services/Subcontractors/PdoSubcontractorStore.php — prepared project-scoped implementation and
  shadow-row primitives.
- src/Services/Subcontractors/SubcontractorReadService.php — pure management list and active option
  projection.
- src/Services/Subcontractors/SubcontractorWriteService.php — create/update/delete and transactional
  rename orchestration.
- src/Services/Subcontractors/SubcontractorContext.php — strict server-authoritative context.
- tests/Support/Subcontractors/FakeSubcontractorStore.php.
- tests/fixtures/subcontractors-react/rows.php.
- tests/fixtures/subcontractors-react/contracts.php.
- tests/test_subcontractors_react_domain.php.
- tests/test_subcontractors_react_policy.php.
- tests/test_subcontractors_react_read_contract.php.
- tests/test_subcontractors_react_write_contract.php.
- tests/test_subcontractors_react_dependencies.php.
- tests/test_subcontractors_react_routes.php.
- tests/test_subcontractors_react_source_invariants.php.

### Create — React/TypeScript

- frontend/src/modules/subcontratistas/api/esquemas.ts.
- frontend/src/modules/subcontratistas/api/gateway.ts.
- frontend/src/modules/subcontratistas/dominio/subcontratista.ts.
- frontend/src/modules/subcontratistas/dominio/validacion.ts.
- frontend/src/modules/subcontratistas/useSubcontratistas.ts.
- frontend/src/modules/subcontratistas/RutaSubcontratistas.tsx.
- frontend/src/modules/subcontratistas/BarraSubcontratistas.tsx.
- frontend/src/modules/subcontratistas/TablaSubcontratistas.tsx.
- frontend/src/modules/subcontratistas/TarjetasSubcontratistas.tsx.
- frontend/src/modules/subcontratistas/AltaSubcontratista.tsx.
- frontend/src/modules/subcontratistas/DialogoEliminarSubcontratista.tsx.
- frontend/src/modules/subcontratistas/EstadoGuardadoSubcontratista.tsx.
- frontend/src/modules/subcontratistas/subcontratistas.css.
- Colocated test files for every schema, gateway, domain helper, hook and component above.
- frontend/src/lib/estado/colaPorClave.ts and its test only if S13 has not already extracted the
  shared primitive; otherwise modify and reuse the existing file.
- tests/browser/subcontratistas-react.spec.mjs.
- tests/browser/subcontratistas-react.a11y.mjs.
- tests/browser/subcontratistas-react.visual.mjs.
- tests/browser/fixtures/subcontratistas-react.mjs.

### Modify during implementation

- public/index.php — add context and dispatch existing list/save through the narrow boundary without
  removing pilot aliases early.
- src/Controllers/Api/SubcontratistasApiController.php — thin transport, content-type compatibility
  dispatch and stable errors.
- src/Controllers/Programacion/ProgramacionIntermediaController.php — consume the canonical active
  projection only if S07 has not already moved this consumer behind its own target contract.
- src/Controllers/Programacion/ProgramacionSemanalController.php — consume the canonical active
  projection only if S08 has not already moved this consumer behind its own target contract.
- src/Core/SpaRouter.php — add /subcontratistas only in Task 10.
- frontend/src/shell/rutas.tsx — mount /app/subcontratistas during pilot and canonical route at cut.
- frontend/src/shell/NavegacionLateral.tsx — consume T01 server label/action; remove only stale
  S14-specific hardcoding if T01 has not already done so.
- frontend/src/lib/api/cliente.ts — only if T01 still lacks reusable JSON CSRF/error support; extend
  centrally with tests, never wrap fetch elsewhere.
- frontend/src/lib/archivos/csv.ts — reuse as-is where sufficient; extend only through shared tests
  if contextual headers/filename need a generic option.
- docs/design-system/manifests/subcontratistas.json.
- docs/design-system/auditoria/censo-modulos.json and generated route inventory only through their
  canonical generator at the cut.
- tests/browser/shell-sidebar-rollout.mjs.
- tests/browser/support/session.mjs and moduleFlows.mjs only after classifying/removing every real
  mutation path from S14 scenarios.
- tests/test_csrf_modulos_api.php only by replacing its real-write-prone S14 check with a pure/source
  contract; do not run it against shared data as S14 evidence.
- docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md at closure status only.

### Delete at canonical cut only

- views/subcontratistas/subcontratistas.view.php.
- public/css/subcontratistas.css.
- public/dist-css/subcontratistas.css only if the canonical build proves it is an exclusive
  generated artifact.
- src/Controllers/Gestion/SubcontratistasController.php if no non-page consumer remains.
- POST /api/subcontratistas/list route.
- Form-urlencoded branch of POST /api/subcontratistas/save.
- Exclusive VIEW-42 script/vendor references made unreachable by the deletion.

Never delete the global subcontratistas table, CIC/PI/PS data, shared APIs used by S07/S08/S11 or BI
route /bi/contratistas.

## Exact Contracts to Implement

### Server endpoints

~~~text
S14-API-01 GET  /api/subcontratistas/context
S14-API-02 GET  /api/subcontratistas/list
S14-API-03 POST /api/subcontratistas/save
~~~

Compatibility:

~~~text
S14-COMP-01 POST /api/subcontratistas/list
S14-COMP-02 POST form /api/subcontratistas/save
~~~

All endpoints require session and active project. Context/list require
lps.subcontratistas.ver. Save requires lps.subcontratistas.editar plus the subcontratistas CSRF
purpose. Target requests reject client authority keys instead of silently trusting them.

### Context wire shape

~~~text
status: success
data.mode: subcontractors | stakeholders
data.labels.title: contextual string
data.labels.singular: contextual string
data.labels.name: contextual string
data.labels.identifier: contextual string
data.labels.scope: contextual string
data.labels.providerType: contextual string
data.labels.bi: contextual string
data.providerTypes: exact ordered string array for mode
data.identifier.maxDigits: 10 | 19
data.actions.view: boolean
data.actions.create: boolean
data.actions.edit: boolean
data.actions.delete: boolean
data.actions.exportCsv: boolean
data.actions.openBi: boolean
data.links.bi: authorized relative href | null
data.csrfToken: nonempty opaque string | null
~~~

Invariants:

- Construccion maps to subcontractors, the six Subcontratistas labels, three types and maxDigits 10.
- Pre-Construccion maps to stakeholders, the six Interesados labels, ten types and maxDigits 19.
- view/exportCsv derive from view capability; create/edit/delete derive from edit capability.
- links.bi is non-null exactly when actions.openBi is true.
- csrfToken is non-null exactly when at least one mutation is available.
- area, role, db, prefix, projectId and table names are absent.

### Row wire shape

~~~text
Id: positive integer
subcontratista: canonical nonempty string, maximum 100 characters
correo_contacto: canonical lowercase valid email, maximum 200
NIT: canonical decimal string, never number
alcance: canonical nonempty string, maximum 200
tipo_proveedor: stored nonempty string
activo: boolean
has_dependencies: boolean
~~~

List returns status=success and data as an array ordered by normalized name then Id. It includes
active and inactive records. An empty list is success with data=[]; a historical unknown type
remains a readable string and is decorated in the UI rather than rejected by Zod.

### Save command union

~~~text
create:
  action=create
  entity={subcontratista, correo_contacto, NIT, alcance, tipo_proveedor}

update:
  action=update
  Id=positive integer
  changes=nonempty strict subset of
    {subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo}

delete:
  action=delete
  Id=positive integer
~~~

Create rejects Id and activo; the server creates activo=true. Update merges changes with the locked
current row and validates the complete entity. Delete revalidates all dependencies. Extra keys,
arrays and empty changes fail. Create/update return the complete canonical row. Delete returns
action=delete and deletedId. There is no partial multi-row envelope.

### Normalization and validation

~~~text
subcontratista = trim + collapse internal whitespace
correo_contacto = trim + lowercase + valid email
NIT input = digits with optional spaces, periods or hyphens
NIT canonical = strip separators, digits-only, 1..mode.maxDigits,
                numeric value <= 9223372036854775807
alcance = trim + collapse internal whitespace
tipo_proveedor = exact member of current server mode catalog
activo = strict boolean
~~~

Name, lowercase email and canonical NIT are each unique inside project_id while excluding the
current Id during update. Validation order is required fields, format/range, catalog, then
uniqueness. Stable errors are INVALID_JSON, UNAUTHENTICATED, PROJECT_REQUIRED, FORBIDDEN,
CSRF_INVALID, ENTITY_NOT_FOUND, DUPLICATE_NAME, DUPLICATE_EMAIL, DUPLICATE_IDENTIFIER,
DEPENDENCIES_EXIST, INVALID_ENTITY and WRITE_FAILED.

### Dependency registry and rename transaction

Canonical descriptors:

~~~text
cic.subcontratista                         exact normalized value
programa_consolidado.Sub_Contratista      exact normalized comma token
programacion_semanal.Sub_Contratista      exact normalized comma token
~~~

SubcontractorTokenList characterizes the current split-on-comma, trim and join-with-comma-space
format used by HandsontableTomSelectEditor. Membership and replacement compare complete normalized
tokens, preserve order, preserve unrelated tokens and never replace substrings.

When normalized name changes, the store call log must be exactly:

~~~text
begin
lock original by project_id + Id
revalidate three uniqueness rules
allocate temporary project-scoped Id
insert shadow with final canonical entity and temporary Id
update exact CIC references old name -> new name
replace exact PI tokens old name -> new name
replace exact PS tokens old name -> new name
delete original after dependencies moved
change shadow Id temporary -> original Id
reread canonical row
commit
~~~

Any failure produces rollback and WRITE_FAILED, with no returned shadow row. A metadata-only update
uses a normal locked transaction and does not create a shadow. No path emits FOREIGN_KEY_CHECKS.

### Frontend gateway

~~~text
getSubcontractorContext(signal?)
listSubcontractors(signal?)
saveSubcontractor(command, csrfToken)
~~~

Every method delegates transport to cliente.ts and validates a strict schema. Reads accept
AbortSignal. Mutations do not automatically retry and are not aborted after dispatch.

## Task 1: Characterize dual mode, normalization, token lists, dependencies and policy

**Files:**

- Create: src/Services/Subcontractors/SubcontractorCatalog.php
- Create: src/Services/Subcontractors/SubcontractorActionPolicy.php
- Create: src/Services/Subcontractors/SubcontractorTokenList.php
- Create: src/Services/Subcontractors/SubcontractorDependencyRegistry.php
- Create: tests/fixtures/subcontractors-react/rows.php
- Create: tests/test_subcontractors_react_domain.php
- Create: tests/test_subcontractors_react_policy.php
- Create: tests/test_subcontractors_react_dependencies.php
- Create: tests/test_subcontractors_react_source_invariants.php
- Read only: public/js/HandsontableTomSelectEditor.js
- Read only: src/Security/RbacCatalog.php

- [ ] **Step 1: Write failing catalog and normalization characterization**

  Assert both canonical area values, mode mapping, six labels per mode, exact order/spelling of the
  3/10 types, identifier digit limits, trim/collapse rules, lowercase email, decimal-string
  canonicalization, signed BIGINT limit, name/email/scope lengths and strict boolean behavior.
  Include invalid letters, symbols, overflow, eleven-digit Construccion and twenty-digit
  Pre-Construccion fixtures.

- [ ] **Step 2: Run focused RED**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  ~~~

  Read each RC on its own line. Expected RED is missing production classes, never a DB connection,
  malformed fixture or changed schema.

- [ ] **Step 3: Write failing policy, token and dependency cases**

  Cover view/edit/denied booleans, nullable BI/CSRF invariants, dependent/nondependent rows, exact
  CIC equality, PI/PS single and multiple tokens, spaces, case normalization, name prefixes and
  substrings that must not match. Source-assert the legacy editor split/join behavior and the exact
  three descriptors.

- [ ] **Step 4: Implement the minimum pure domain and run GREEN**

  Implement immutable catalogs, pure normalization/validation, action composition, token parsing
  and one dependency registry with no DB or HTTP access.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_policy.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm no /admin/, SQL, schema, PDC or role-matrix diff. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors/SubcontractorCatalog.php src/Services/Subcontractors/SubcontractorActionPolicy.php src/Services/Subcontractors/SubcontractorTokenList.php src/Services/Subcontractors/SubcontractorDependencyRegistry.php tests/fixtures/subcontractors-react/rows.php tests/test_subcontractors_react_domain.php tests/test_subcontractors_react_policy.php tests/test_subcontractors_react_dependencies.php tests/test_subcontractors_react_source_invariants.php
  git commit -m "test: characterize subcontractor domain"
  ~~~

## Task 2: Add pure scoped context and list contracts

**Files:**

- Create: src/Services/Subcontractors/SubcontractorStore.php
- Create: src/Services/Subcontractors/PdoSubcontractorStore.php
- Create: src/Services/Subcontractors/SubcontractorReadService.php
- Create: src/Services/Subcontractors/SubcontractorContext.php
- Create: tests/Support/Subcontractors/FakeSubcontractorStore.php
- Create: tests/fixtures/subcontractors-react/contracts.php
- Create: tests/test_subcontractors_react_read_contract.php
- Create: tests/test_subcontractors_react_routes.php
- Modify: src/Controllers/Api/SubcontratistasApiController.php
- Modify: public/index.php

- [ ] **Step 1: Write failing context/list contracts**

  Use fake scope, fake capabilities and fake store to assert exact envelopes for both modes, 3/10
  types, maxDigits, BI/CSRF invariants, all eight row fields, NIT string serialization, booleans,
  stable alphabetical order, active+inactive inclusion, historical unknown type and empty success.

- [ ] **Step 2: Add purity, scope and routing RED cases**

  Tripwire every write/transaction method. Assert GET context/list make zero writes, reject client
  authority, require view before repository access, emit no-store and stable errors, and route by
  exact method. Keep POST list on the compatibility branch during the pilot.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_read_contract.php
  docker compose exec app php tests/test_subcontractors_react_routes.php
  ~~~

- [ ] **Step 3: Implement repository read boundary and thin transport**

  Resolve projectId and area from ProjectScope/session. PdoSubcontractorStore uses
  TableResolver::resolve, prepared statements and project_id on every query. ReadService decorates
  has_dependencies through the canonical registry. Context uses effective RBAC, the authorized
  /bi/contratistas policy and subcontratistas CSRF purpose.

- [ ] **Step 4: Run focused GREEN and compatibility assertions**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_policy.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_read_contract.php
  docker compose exec app php tests/test_subcontractors_react_routes.php
  ~~~

  Source-assert that POST list remains reachable for VIEW-42 and that neither GET path accepts db or
  area fallback.

- [ ] **Step 5: Review and future atomic commit**

  Inspect every query for project_id and every error for absence of exception text. Do not make a
  runtime HTTP request. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors/SubcontractorStore.php src/Services/Subcontractors/PdoSubcontractorStore.php src/Services/Subcontractors/SubcontractorReadService.php src/Services/Subcontractors/SubcontractorContext.php tests/Support/Subcontractors/FakeSubcontractorStore.php tests/fixtures/subcontractors-react/contracts.php tests/test_subcontractors_react_read_contract.php tests/test_subcontractors_react_routes.php src/Controllers/Api/SubcontratistasApiController.php public/index.php
  git commit -m "feat: add scoped subcontractor reads"
  ~~~

## Task 3: Add strict Zod schemas, gateway, validation, queue and CSV reuse

**Files:**

- Create: frontend/src/modules/subcontratistas/api/esquemas.ts
- Create: frontend/src/modules/subcontratistas/api/esquemas.test.ts
- Create: frontend/src/modules/subcontratistas/api/gateway.ts
- Create: frontend/src/modules/subcontratistas/api/gateway.test.ts
- Create: frontend/src/modules/subcontratistas/dominio/subcontratista.ts
- Create: frontend/src/modules/subcontratistas/dominio/subcontratista.test.ts
- Create: frontend/src/modules/subcontratistas/dominio/validacion.ts
- Create: frontend/src/modules/subcontratistas/dominio/validacion.test.ts
- Create or modify: frontend/src/lib/estado/colaPorClave.ts
- Create or modify: frontend/src/lib/estado/colaPorClave.test.ts
- Modify only if generic support is missing: frontend/src/lib/archivos/csv.ts
- Modify only if generic support is missing: frontend/src/lib/archivos/csv.test.ts

- [ ] **Step 1: Write failing strict schema cases**

  Cover both context variants, exact action/link/CSRF invariants, strict row/list/save/error
  envelopes, NIT as string, real booleans, unknown historical tipo as string, missing fields,
  unexpected keys, malformed hrefs and number-valued NIT rejection.

- [ ] **Step 2: Write failing domain, gateway and shared primitive cases**

  Assert labels derive only from context, local validation mirrors PHP, create/update command
  formation is strict, AbortSignal reaches reads, mutations pass CSRF once, no retry occurs, keyed
  queue order is A1-A2 while B1 may overlap, and shared CSV preserves BOM/CRLF/escaping.

  ~~~bash
  cd frontend
  npm test -- --run src/modules/subcontratistas/api/esquemas.test.ts src/modules/subcontratistas/api/gateway.test.ts src/modules/subcontratistas/dominio/subcontratista.test.ts src/modules/subcontratistas/dominio/validacion.test.ts src/lib/estado/colaPorClave.test.ts src/lib/archivos/csv.test.ts
  ~~~

- [ ] **Step 3: Implement minimum schemas, gateway and pure helpers**

  Derive types with z.infer. Delegate every request to cliente.ts. Reuse the S13 CSV serializer and
  extract its queue only if still module-local; change S13 imports mechanically and keep S13 queue
  tests green. Do not add a package.

- [ ] **Step 4: Run focused GREEN, typecheck and fetch source invariant**

  ~~~bash
  cd frontend
  npm test -- --run src/modules/subcontratistas src/lib/estado/colaPorClave.test.ts src/lib/archivos/csv.test.ts
  npm run typecheck
  rg -n "fetch\\(" src --glob "*.ts" --glob "*.tsx"
  ~~~

  The source search may report only frontend/src/lib/api/cliente.ts and its intentional tests.

- [ ] **Step 5: Review and future atomic commit**

  Confirm no duplicated manual wire interface, no raw role/area policy and no module-local fetch. In
  an authorized execution session only:

  ~~~bash
  git diff --check
  git add frontend/src/modules/subcontratistas frontend/src/lib/estado/colaPorClave.ts frontend/src/lib/estado/colaPorClave.test.ts frontend/src/lib/archivos/csv.ts frontend/src/lib/archivos/csv.test.ts
  git commit -m "feat: add subcontractor frontend contracts"
  ~~~

  Stage shared CSV files only if this task actually changed them.

## Task 4: Build the read-only responsive vertical slice

**Files:**

- Create: frontend/src/modules/subcontratistas/useSubcontratistas.ts
- Create: frontend/src/modules/subcontratistas/useSubcontratistas.test.tsx
- Create: frontend/src/modules/subcontratistas/RutaSubcontratistas.tsx
- Create: frontend/src/modules/subcontratistas/RutaSubcontratistas.test.tsx
- Create: frontend/src/modules/subcontratistas/BarraSubcontratistas.tsx
- Create: frontend/src/modules/subcontratistas/BarraSubcontratistas.test.tsx
- Create: frontend/src/modules/subcontratistas/TablaSubcontratistas.tsx
- Create: frontend/src/modules/subcontratistas/TablaSubcontratistas.test.tsx
- Create: frontend/src/modules/subcontratistas/TarjetasSubcontratistas.tsx
- Create: frontend/src/modules/subcontratistas/TarjetasSubcontratistas.test.tsx
- Create: frontend/src/modules/subcontratistas/subcontratistas.css
- Modify: frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/rutas.test.tsx

- [ ] **Step 1: Write failing load, mode and state tests**

  Cover loading, context error, list error, empty editable/readonly, stale-with-data, active/inactive,
  dependent and historical-invalid-type rows. Prove Construccion and Pre-Construccion headings,
  descriptions, labels and counts come from one context with no delayed relabel.

- [ ] **Step 2: Write failing responsive DOM tests**

  Mock matchMedia around 768 and assert exactly one branch mounts. Table has caption/thead/th scope
  and all six business fields plus actions; cards expose the same fields/actions/headings. Assert
  there is no week selector, search, filter, drawer, sync or PDC action.

  ~~~bash
  cd frontend
  npm test -- --run src/modules/subcontratistas/useSubcontratistas.test.tsx src/modules/subcontratistas/RutaSubcontratistas.test.tsx src/modules/subcontratistas/BarraSubcontratistas.test.tsx src/modules/subcontratistas/TablaSubcontratistas.test.tsx src/modules/subcontratistas/TarjetasSubcontratistas.test.tsx
  ~~~

- [ ] **Step 3: Implement readonly route and shared state owner**

  Load context then list with cancellation on unmount/project change. Preserve rows as stale when a
  refresh fails. Mount pilot /app/subcontratistas. Render disabled native controls and a focalizable
  dependency/type warning with explicit reason.

- [ ] **Step 4: Run focused GREEN, typecheck and route tests**

  ~~~bash
  cd frontend
  npm test -- --run src/modules/subcontratistas src/shell/rutas.test.tsx
  npm run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm one h1 in the shell composition, no page overflow styles, no literal colors and no
  network call outside the gateway. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add frontend/src/modules/subcontratistas frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
  git commit -m "feat: render responsive subcontractor catalog"
  ~~~

## Task 5: Implement strict create and accessible draft recovery

**Files:**

- Create: src/Services/Subcontractors/SubcontractorWriteService.php
- Create: tests/test_subcontractors_react_write_contract.php
- Modify: src/Controllers/Api/SubcontratistasApiController.php
- Modify: tests/test_subcontractors_react_routes.php
- Create: frontend/src/modules/subcontratistas/AltaSubcontratista.tsx
- Create: frontend/src/modules/subcontratistas/AltaSubcontratista.test.tsx
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.ts
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.test.tsx
- Modify: frontend/src/modules/subcontratistas/RutaSubcontratistas.tsx

- [ ] **Step 1: Write failing PHP create contracts**

  Cover exact JSON/content type, edit capability, CSRF, missing/extra fields, mode-specific type,
  NIT formatting/range, name length, triple uniqueness within project, same values in another
  project, project-scoped Id allocation, activo=true and full canonical response. Use only fake
  store call logs.

- [ ] **Step 2: Write failing create UI scenarios**

  Assert five labeled fields, mode labels/catalog, client validation, summary focus, no submit while
  invalid, one request on double activation, busy state, canonical row insertion, draft retention
  for 409/422/500, returned focus and readonly absence.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_routes.php
  cd frontend
  npm test -- --run src/modules/subcontratistas/AltaSubcontratista.test.tsx src/modules/subcontratistas/useSubcontratistas.test.tsx
  ~~~

- [ ] **Step 3: Implement minimum create vertical**

  Add strict transport dispatch and a WriteService using the scoped allocator and one transaction.
  React creates only through the explicit Alta form/card, uses the context CSRF token once and
  inserts the returned canonical row in stable order without a success reload.

- [ ] **Step 4: Run focused GREEN**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_routes.php
  cd frontend
  npm test -- --run src/modules/subcontratistas
  npm run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm strict single-entity command, no partial success, no real write and no create control for
  readonly users. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors/SubcontractorWriteService.php tests/test_subcontractors_react_write_contract.php src/Controllers/Api/SubcontratistasApiController.php tests/test_subcontractors_react_routes.php frontend/src/modules/subcontratistas
  git commit -m "feat: create subcontractors in React"
  ~~~

## Task 6: Implement per-row autosave, validation and recovery

**Files:**

- Modify: src/Services/Subcontractors/SubcontractorWriteService.php
- Modify: tests/test_subcontractors_react_write_contract.php
- Modify: src/Controllers/Api/SubcontratistasApiController.php
- Create: frontend/src/modules/subcontratistas/EstadoGuardadoSubcontratista.tsx
- Create: frontend/src/modules/subcontratistas/EstadoGuardadoSubcontratista.test.tsx
- Modify: frontend/src/modules/subcontratistas/TablaSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/TablaSubcontratistas.test.tsx
- Modify: frontend/src/modules/subcontratistas/TarjetasSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/TarjetasSubcontratistas.test.tsx
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.ts
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.test.tsx

- [ ] **Step 1: Write failing server update contracts**

  Cover nonempty allowlisted changes, locked current row, complete merged validation, exclusion of
  current Id in triple uniqueness, type historical requiring correction on save, active false with
  references untouched, canonical response and all stable 404/409/422/500 errors.

- [ ] **Step 2: Write failing autosave interaction and queue tests**

  Text saves on blur/Enter, selects/Activo on change, Escape restores confirmed value, one Id saves
  in order, different Ids overlap, response replaces draft/server row, 422 focuses the field,
  409/500 preserve draft and offer explicit retry, stale reload does not discard drafts and no
  automatic retry occurs.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  cd frontend
  npm test -- --run src/modules/subcontratistas/TablaSubcontratistas.test.tsx src/modules/subcontratistas/TarjetasSubcontratistas.test.tsx src/modules/subcontratistas/useSubcontratistas.test.tsx src/modules/subcontratistas/EstadoGuardadoSubcontratista.test.tsx src/lib/estado/colaPorClave.test.ts
  ~~~

- [ ] **Step 3: Implement minimum autosave behavior**

  Send only changed allowlisted fields while PHP validates the merged entity. Keep confirmed and
  draft state separate. Feed both table and cards through the same callbacks and keyed queue. Use a
  polite live region without announcing every keystroke.

- [ ] **Step 4: Run focused GREEN and typecheck**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  cd frontend
  npm test -- --run src/modules/subcontratistas src/lib/estado/colaPorClave.test.ts
  npm run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm active=false never deletes/rewrites references, server rows change only on canonical
  success and each control is equivalently editable in table/cards. In an authorized session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors/SubcontractorWriteService.php tests/test_subcontractors_react_write_contract.php src/Controllers/Api/SubcontratistasApiController.php frontend/src/modules/subcontratistas
  git commit -m "feat: autosave subcontractor rows"
  ~~~

## Task 7: Make rename atomic across CIC, PI and PS

**Files:**

- Modify: src/Services/Subcontractors/SubcontractorStore.php
- Modify: src/Services/Subcontractors/PdoSubcontractorStore.php
- Modify: src/Services/Subcontractors/SubcontractorWriteService.php
- Modify: src/Services/Subcontractors/SubcontractorTokenList.php
- Modify: tests/Support/Subcontractors/FakeSubcontractorStore.php
- Modify: tests/test_subcontractors_react_dependencies.php
- Modify: tests/test_subcontractors_react_write_contract.php
- Modify: tests/test_subcontractors_react_source_invariants.php
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.test.tsx
- Modify: frontend/src/modules/subcontratistas/EstadoGuardadoSubcontratista.test.tsx

- [ ] **Step 1: Write failing transaction call-log cases**

  Assert the exact twelve-step begin-to-commit order for a name change with CIC plus multi-token PI
  and PS references, preservation of public Id, project_id on every operation, exact token
  replacement, stable unrelated order and no visible temporary row.

- [ ] **Step 2: Add rollback and hostile-name RED cases**

  Inject failure at insert, each dependency update, original delete, Id reassignment and reread.
  Every case must log rollback/no commit and surface WRITE_FAILED without internal text. Include
  ACME versus ACME Norte, duplicate token, case/space normalization and source rejection of
  FOREIGN_KEY_CHECKS.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  ~~~

- [ ] **Step 3: Implement exact shadow-row transaction**

  Lock/revalidate, allocate a temporary scoped Id, insert the final parent, move CIC exact
  references, replace PI/PS exact tokens, remove the old parent, restore original Id on the shadow,
  reread and commit. Metadata-only update remains on the simple transaction. Do not emulate cascade
  and do not issue generic dynamic SQL.

- [ ] **Step 4: Run focused GREEN plus frontend failure recovery**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  cd frontend
  npm test -- --run src/modules/subcontratistas/useSubcontratistas.test.tsx src/modules/subcontratistas/EstadoGuardadoSubcontratista.test.tsx
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Inspect transaction boundaries, prepared statements and project scope. Confirm no schema/FK
  migration and no real DB invocation. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors tests/Support/Subcontractors/FakeSubcontractorStore.php tests/test_subcontractors_react_dependencies.php tests/test_subcontractors_react_write_contract.php tests/test_subcontractors_react_source_invariants.php frontend/src/modules/subcontratistas
  git commit -m "fix: preserve subcontractor references on rename"
  ~~~

## Task 8: Add dependency-safe delete and active operational projections

**Files:**

- Modify: src/Services/Subcontractors/SubcontractorReadService.php
- Modify: src/Services/Subcontractors/SubcontractorWriteService.php
- Modify: src/Services/Subcontractors/PdoSubcontractorStore.php
- Modify: tests/test_subcontractors_react_read_contract.php
- Modify: tests/test_subcontractors_react_write_contract.php
- Modify: tests/test_subcontractors_react_dependencies.php
- Create: frontend/src/modules/subcontratistas/DialogoEliminarSubcontratista.tsx
- Create: frontend/src/modules/subcontratistas/DialogoEliminarSubcontratista.test.tsx
- Modify: frontend/src/modules/subcontratistas/TablaSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/TarjetasSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.ts
- Modify: frontend/src/modules/subcontratistas/useSubcontratistas.test.tsx
- Modify conditionally: src/Controllers/Programacion/ProgramacionIntermediaController.php
- Modify conditionally: src/Controllers/Programacion/ProgramacionSemanalController.php

- [ ] **Step 1: Write failing delete and projection contracts**

  Assert has_dependencies and delete use the same three-entry registry, delete rechecks inside its
  transaction, returns DEPENDENCIES_EXIST for each source, deletes only an unreferenced row in the
  scoped project and returns deletedId. Assert management list includes inactive while active option
  projection excludes it and keeps stable names/Ids.

- [ ] **Step 2: Write failing accessible UI and consumer cases**

  Cover focalizable blocked action with reason, enabled confirmation dialog, title/content by mode,
  focus trap, Escape, cancel, return focus, pending state, one request, failure retention and success
  removal. Characterize S07/S08 initial and refresh consumers so both use active-only data without
  introducing a PDC consumer.

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_read_contract.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  cd frontend
  npm test -- --run src/modules/subcontratistas/DialogoEliminarSubcontratista.test.tsx src/modules/subcontratistas/useSubcontratistas.test.tsx src/modules/subcontratistas/TablaSubcontratistas.test.tsx src/modules/subcontratistas/TarjetasSubcontratistas.test.tsx
  ~~~

- [ ] **Step 3: Implement minimum delete and active projection**

  Revalidate dependencies after locking and delete only when all three sources are empty. Keep
  inactive as the nondestructive alternative. Expose one server-side active projection and route
  S07/S08 consumers to it only where their already-approved plans have not done so; do not modify
  their unrelated behavior.

- [ ] **Step 4: Run focused GREEN and consumer source assertions**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_read_contract.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  cd frontend
  npm test -- --run src/modules/subcontratistas
  npm run typecheck
  ~~~

- [ ] **Step 5: Review and future atomic commit**

  Confirm no dependent delete, no role logic in React, no S07/S08 week/activity changes and no PDC
  diff. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add src/Services/Subcontractors tests/test_subcontractors_react_read_contract.php tests/test_subcontractors_react_write_contract.php tests/test_subcontractors_react_dependencies.php tests/test_subcontractors_react_source_invariants.php frontend/src/modules/subcontratistas src/Controllers/Programacion/ProgramacionIntermediaController.php src/Controllers/Programacion/ProgramacionSemanalController.php
  git commit -m "feat: protect subcontractor deletion"
  ~~~

  Stage the two consumer controllers only if their active projection actually changed.

## Task 9: Complete CSV, BI, RBAC, accessibility, themes and viewport evidence

**Files:**

- Modify: frontend/src/modules/subcontratistas/BarraSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/BarraSubcontratistas.test.tsx
- Modify: frontend/src/modules/subcontratistas/RutaSubcontratistas.tsx
- Modify: frontend/src/modules/subcontratistas/subcontratistas.css
- Modify: frontend/src/lib/archivos/csv.test.ts
- Create: tests/browser/fixtures/subcontratistas-react.mjs
- Create: tests/browser/subcontratistas-react.spec.mjs
- Create: tests/browser/subcontratistas-react.a11y.mjs
- Create: tests/browser/subcontratistas-react.visual.mjs
- Modify: docs/design-system/manifests/subcontratistas.json
- Modify: tests/browser/shell-sidebar-rollout.mjs

- [ ] **Step 1: Write failing CSV, BI and effective-permission component cases**

  Assert exact six contextual headers, active Si/No, alphabetical persisted rows only, BOM/CRLF,
  quote/comma/newline escaping, header-only empty export, mode/project/date filename, object URL
  revocation and live confirmation. Assert BI label/href/null invariant and editor/viewer/denied UI
  from context actions without raw roles.

- [ ] **Step 2: Add fully intercepted browser scenarios**

  Install route interception before navigation for context/list/save. Cover Construccion
  editor/viewer/denied, Pre-Construccion editor/viewer, active/inactive/dependent/unknown type,
  create, autosave ordering, conflicts, shadow rename success/failure fixtures, delete, reload
  purity, CSV and BI. Fail on unhandled request, console error or unexpected mutation.

- [ ] **Step 3: Add accessibility, theme and responsive measurements**

  Exercise keyboard edit/escape/delete dialog/focus return, touch targets, live regions, axe,
  reduced motion, 200 percent zoom, dark/light and 390x844, 480x900, 768x1024, 1180x820,
  1440x900. Measure document scrollWidth <= clientWidth and assert one responsive branch in each
  viewport. Save screenshots only as uncommitted output.

- [ ] **Step 4: Implement presentation and run focused evidence**

  Use only design tokens and layered CSS. Adapt long labels without truncating essential content.

  ~~~bash
  cd frontend
  npm test -- --run src/modules/subcontratistas src/lib/archivos/csv.test.ts
  npm run typecheck
  cd ..
  npx playwright test tests/browser/subcontratistas-react.spec.mjs tests/browser/subcontratistas-react.a11y.mjs tests/browser/subcontratistas-react.visual.mjs --workers=1
  ~~~

  Read each RC independently. Do not run an update-snapshots flag.

- [ ] **Step 5: Review and future atomic commit**

  Inspect browser network/console artifacts, both themes and all measurements. Confirm no golden
  changed. In an authorized execution session only:

  ~~~bash
  git diff --check
  git add frontend/src/modules/subcontratistas frontend/src/lib/archivos/csv.test.ts tests/browser/fixtures/subcontratistas-react.mjs tests/browser/subcontratistas-react.spec.mjs tests/browser/subcontratistas-react.a11y.mjs tests/browser/subcontratistas-react.visual.mjs docs/design-system/manifests/subcontratistas.json tests/browser/shell-sidebar-rollout.mjs
  git commit -m "test: verify subcontractors across responsive modes"
  ~~~

## Task 10: Cut the canonical route and retire exclusive legacy pieces

**Files:**

- Modify: src/Core/SpaRouter.php
- Modify: public/index.php
- Modify: frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/rutas.test.tsx
- Modify: frontend/src/shell/NavegacionLateral.tsx
- Modify: frontend/src/shell/NavegacionLateral.test.tsx
- Modify: tests/test_subcontractors_react_routes.php
- Modify: tests/test_subcontractors_react_source_invariants.php
- Modify: tests/browser/subcontratistas-react.spec.mjs
- Modify: tests/browser/shell-sidebar-rollout.mjs
- Modify: docs/design-system/auditoria/censo-modulos.json through canonical generator
- Modify: generated architecture inventory through canonical generator
- Delete after zero-caller proof: views/subcontratistas/subcontratistas.view.php
- Delete after zero-caller proof: public/css/subcontratistas.css
- Delete conditionally: public/dist-css/subcontratistas.css
- Delete conditionally: src/Controllers/Gestion/SubcontratistasController.php
- Delete after zero-caller proof: POST /api/subcontratistas/list
- Delete after zero-caller proof: form branch of POST /api/subcontratistas/save

- [ ] **Step 1: Write failing canonical-route and zero-caller assertions**

  Assert /subcontratistas and refresh enter SpaRouter, /app/subcontratistas remains a compatible
  pilot path or canonical redirect as T01 specifies, sidebar label follows project mode without
  flash, API target methods remain, legacy page/form aliases have no callers and shared
  S07/S08/S11/BI consumers remain.

- [ ] **Step 2: Capture pre-cut search and rollback recipe**

  Search PHP, JS, tests, docs and generated manifests for VIEW-42, legacy controller, POST list,
  form fields, CSS and vendor references. Classify every hit. Record exact route/file restoration
  from the cut commit; rollback changes routing/artifacts only and never data, FK, RLS or grants.

  ~~~bash
  rg -n "subcontratistas\\.view|SubcontratistasController|api/subcontratistas/list|subcontratistas\\.css|guardar_cambios|accion.*(crear|registrar|eliminar)" public src views frontend tests docs
  ~~~

- [ ] **Step 3: Perform minimum cut and retire only exclusive artifacts**

  Add the exact SpaRouter path, point sidebar/deep links to it, remove the page route and compatibility
  branches only after zero callers, delete exclusive VIEW/CSS/controller artifacts and regenerate
  inventories with their canonical commands. Preserve the target API and shared catalog services.

- [ ] **Step 4: Run post-cut verification and rehearse rollback without data**

  ~~~bash
  docker compose exec app php tests/test_subcontractors_react_domain.php
  docker compose exec app php tests/test_subcontractors_react_policy.php
  docker compose exec app php tests/test_subcontractors_react_read_contract.php
  docker compose exec app php tests/test_subcontractors_react_write_contract.php
  docker compose exec app php tests/test_subcontractors_react_dependencies.php
  docker compose exec app php tests/test_subcontractors_react_routes.php
  docker compose exec app php tests/test_subcontractors_react_source_invariants.php
  cd frontend
  npm test -- --run
  npm run typecheck
  cd ..
  npx playwright test tests/browser/subcontratistas-react.spec.mjs tests/browser/subcontratistas-react.a11y.mjs tests/browser/subcontratistas-react.visual.mjs tests/browser/shell-sidebar-rollout.mjs --workers=1
  ~~~

  Read each RC on its own line. Rehearse route rollback in tests or a temporary patch, then restore
  the verified cut without making any DB call.

- [ ] **Step 5: Review, future atomic commit and closure gate**

  Confirm zero legacy callers, no /admin/ diff, no golden change, clean source searches and every
  S14 acceptance criterion mapped below. In an authorized implementation session only:

  ~~~bash
  git diff --check
  git status --short
  git add src/Core/SpaRouter.php public/index.php frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/NavegacionLateral.test.tsx tests/test_subcontractors_react_routes.php tests/test_subcontractors_react_source_invariants.php tests/browser/subcontratistas-react.spec.mjs tests/browser/shell-sidebar-rollout.mjs docs/design-system/auditoria/censo-modulos.json
  git add -u views/subcontratistas public/css/subcontratistas.css public/dist-css/subcontratistas.css src/Controllers/Gestion/SubcontratistasController.php
  git commit -m "feat: cut subcontractors to React"
  ~~~

  Stage only paths that were actually changed or deleted. Then follow the repository PR/CI closure
  policy; deployment remains separately authorized.

## Vertical Checkpoints

| Checkpoint | Tasks | Demonstrable outcome | Stop condition |
|---|---|---|---|
| V1 | 1–2 | Dual-mode pure scoped context/list with exact catalogs | Area/query authority, DML GET or contract drift |
| V2 | 3–4 | Strict frontend contracts and responsive readonly pilot | Zod drift, duplicate UI or fetch outside client |
| V3 | 5–6 | Explicit create plus serialized per-row autosave | Lost draft, partial success or wrong capability |
| V4 | 7–8 | Atomic rename, safe delete and active projections | FK risk, substring mutation or dependent delete |
| V5 | 9 | CSV, BI, RBAC, a11y, themes and viewports evidenced | Unhandled request, overflow or accessibility failure |
| V6 | 10 | Canonical SPA route, legacy retirement and rollback proof | Unclassified caller, dirty tree or failed verification |

Do not start the next checkpoint while the previous stop condition is unresolved.

## Traceability Matrix

| Acceptance | Tasks | Primary evidence |
|---|---|---|
| S14-AC-01 | 4, 10 | route/component and deep-link tests |
| S14-AC-02 | 1, 2, 4, 9 | Construccion catalog/context/browser fixture |
| S14-AC-03 | 1, 2, 4, 9 | Pre-Construccion context and no-flash browser test |
| S14-AC-04 | 1, 2, 4 | server mode mapping and context-only labels |
| S14-AC-05 | 2 | area authority rejection contract |
| S14-AC-06 | 2, 5–8 | scoped store/fake call logs |
| S14-AC-07 | 2, 3, 5 | authority-key rejection and gateway tests |
| S14-AC-08 | 1, 2, 9 | read capability contracts |
| S14-AC-09 | 2, 5–8 | mutation capability/CSRF contracts |
| S14-AC-10 | 3, 4, 9 | frontend source search/component fixtures |
| S14-AC-11 | 1–3 | exact Construccion catalog tests |
| S14-AC-12 | 1–3 | exact Pre-Construccion catalog tests |
| S14-AC-13 | 2, 3 | strict PHP/Zod row serializer |
| S14-AC-14 | 2 | GET tripwire, scope and cache headers |
| S14-AC-15 | 2, 4 | empty contract and UI states |
| S14-AC-16 | 2, 4 | active/inactive management fixture |
| S14-AC-17 | 2, 8 | active projection consumer contracts |
| S14-AC-18 | 1, 3, 5, 6 | normalizer and write cases |
| S14-AC-19 | 1–3, 5, 6 | decimal-string PHP/Zod/write tests |
| S14-AC-20 | 1, 3, 5 | identifier boundary fixtures |
| S14-AC-21 | 1, 5, 6 | varchar-100 validation cases |
| S14-AC-22 | 1, 5–7 | scoped triple uniqueness tests |
| S14-AC-23 | 1, 5, 6 | mode catalog save rejection |
| S14-AC-24 | 2–4, 6 | historical-type read/warning/save cases |
| S14-AC-25 | 5 | create component and hook tests |
| S14-AC-26 | 6 | table/card autosave interactions |
| S14-AC-27 | 3, 6 | keyed queue call log |
| S14-AC-28 | 5, 6 | canonical response merge tests |
| S14-AC-29 | 6, 8 | active update and dependency call log |
| S14-AC-30 | 1, 2, 7, 8 | CIC registry and transaction tests |
| S14-AC-31 | 1, 2, 7, 8 | PI token registry tests |
| S14-AC-32 | 1, 2, 7, 8 | PS token registry tests |
| S14-AC-33 | 8 | delete lock/revalidation call log |
| S14-AC-34 | 7 | three-source rename call log |
| S14-AC-35 | 7 | shadow-row Id/FK sequence tests |
| S14-AC-36 | 1, 7 | FOREIGN_KEY_CHECKS source invariant |
| S14-AC-37 | 5–8 | strict one-command envelopes |
| S14-AC-38 | 2, 4, 9 | pure reload tripwire/network log |
| S14-AC-39 | 3, 9 | six-column persisted-row CSV tests |
| S14-AC-40 | 1, 3, 9 | contextual header/filename tests |
| S14-AC-41 | 2–4, 9 | BI label/href invariant |
| S14-AC-42 | 1, 8, 10 | PDC absence source assertions |
| S14-AC-43 | 4, 9 | DOM absence assertions |
| S14-AC-44 | 4, 6, 8, 9 | table parity scenarios |
| S14-AC-45 | 4, 6, 8, 9 | card parity scenarios |
| S14-AC-46 | 4, 9 | one-branch mount assertions |
| S14-AC-47 | 9 | five viewport measurements |
| S14-AC-48 | 9 | dark/light browser scenarios |
| S14-AC-49 | 4, 8, 9 | keyboard/focus/touch/zoom/axe evidence |
| S14-AC-50 | 3, 10 | fetch source search |
| S14-AC-51 | 3 | strict schema tests |
| S14-AC-52 | 2, 5, 7, 8 | PHP route/contract tests |
| S14-AC-53 | 2, 5, 7, 8 | stable error/source tests |
| S14-AC-54 | 1–10 | fakes/interception logs |
| S14-AC-55 | 1–10 | scoped diff/schema/RLS audit |
| S14-AC-56 | 10 | zero-caller proof |
| S14-AC-57 | 10 | route-only rollback rehearsal |
| S14-AC-58 | 9, 10 | visual artifact/status audit |

## Verification Commands Explicitly Forbidden in S14

Do not run these as S14 implementation evidence:

~~~text
tests/browser/full-app-flow.spec.mjs
tests/browser/support/operationalCycle.mjs
tests/test_csrf_modulos_api.php against the mounted mutable application
manual POST /api/subcontratistas/save
manual POST /api/subcontratistas/list when its behavior has not been classified
any CIC, PI or PS browser flow that materializes or rewrites operational data
any SQL INSERT, UPDATE, DELETE, REPLACE, ALTER, CREATE, DROP or TRUNCATE
SET FOREIGN_KEY_CHECKS
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
- [ ] Both modes, six contextual labels and exact 3/10 catalogs are characterized.
- [ ] Context/list are pure and no-store.
- [ ] Project and area come from server scope, never client authority.
- [ ] Effective RBAC is server-side; React has no role matrix.
- [ ] All eight row fields have strict PHP/Zod types.
- [ ] NIT is a bounded decimal string throughout the wire/frontend.
- [ ] Management list includes inactive; operational projections do not.
- [ ] Three dependencies use one registry and exact token semantics.
- [ ] Rename follows the shadow sequence, preserves Id and never disables constraints.
- [ ] Delete is revalidated and dependent rows remain.
- [ ] Create/update/delete are strict one-entity commands.
- [ ] Row queues are ordered and mutations are not retried.
- [ ] Table/cards have the same information/actions and only one mounts.
- [ ] No search, filters, week selector, drawer, PDC or batch save was introduced.
- [ ] CSV has exact six contextual columns and only persisted rows.
- [ ] BI link is server-authorized and contextually labeled.
- [ ] Dark/light, five viewports, zoom, keyboard, touch and reduced motion are evidenced.
- [ ] No fetch exists outside cliente.ts.
- [ ] No real mutation occurred in verification.
- [ ] RLS, schema, FK, grants, users, credentials and data are unchanged.
- [ ] Visual goldens were not changed without approval.
- [ ] Zero callers and rollback are proven before deletion.
- [ ] Each verification RC was read on its own line.
- [ ] git diff --check is clean.

## Cierre

Estado actual: plan escrito y autorrevisado; no ejecutado.

S14 closes only when an authorized implementation session has completed Tasks 1–10, all vertical
checkpoints are green, every acceptance criterion has evidence, no forbidden DML occurred in tests,
the canonical route cut and rollback were exercised, S07/S08 active projections and S11/BI consumers
remain intact, the branch is clean, CI on the required PR is green and the verified SHA is recorded.
Deployment remains separately authorized and is not part of this plan.
