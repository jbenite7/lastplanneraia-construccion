# S15 Control de Cambios React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Use superpowers:test-driven-development for every task and
> verification-before-completion before any completion claim. Execute tasks in order and stop at
> every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and closure live
> in Cierre and git history, never in checkbox counts.

**Goal:** migrate /control-cambios from VIEW-10, jQuery and DataTables into the main React SPA,
preserve its project-scoped list, 14-field summary, 12 column filters, counts, readonly detail and
five state semantics, and recover the approved create/edit/delete, validations, supports-by-link,
individual PDF and Consolidado ODC XLSX capabilities across desktop, tablet, mobile, dark and light.

**Architecture:** T01 owns authentication, active project, shell, navigation, theme, route outlet,
week administration and the only HTTP client. S15 adds a narrow ControlChanges PHP boundary over
the global cambios table. GET context and GET list are pure. A strict POST save union delegates one
create/update/delete command to a transactional service. Project identity and effective
capabilities are server-authoritative. A persisted-row codec tolerates malformed historical JSON
while emitting strict wire data and warnings. React owns local filters and one explicit form draft.
The PDF definition is pure/client-side; the existing server XLSX route is adapted behind an
exporter seam. Legacy POST list and form save remain during pilot.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe, pdfmake 0.1.70 and AIA design-system tokens.

**Spec:** docs/superpowers/specs/2026-08-30-s15-control-cambios-react-design.md

## Global Constraints

- Work only in
  /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react on branch
  shell-minimo-react. Never use /Volumes/Crucial X6/Developer/lps-aia, the parent checkout or
  another worktree.
- Execute only after the T01 shell/router/client/capability contracts consumed here exist and are
  green. Reuse them; do not create another shell, project selector, week selector, sidebar, theme
  store, session store or HTTP client.
- Inspect git status --short and relevant diffs before every task. Preserve unrelated and
  pre-existing edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future instructions and require explicit
  implementation authorization.
- /admin/ is excluded. Do not edit its controllers, views, routes, permissions or tests.
- Do not modify RLS, ProjectScope semantics, schema, migrations, tables, columns, indexes, keys,
  triggers, grants, users, credentials, memberships, roles, aliases, overrides or data.
- No DDL/DML is permitted during documentation or safe verification. Future PHP tests use pure
  services, fakes, fixtures, call logs and source assertions. Future browser tests install complete
  network interception before navigation.
- Never call real POST /api/control-cambios/save or generate a real Consolidado ODC in tests for
  this plan. Rollback writes are DML and do not make a test safe.
- Preserve lps.control_cambios.ver, lps.control_cambios.editar and lps.reportes.generar exactly.
  PHP resolves effective capabilities and overrides. React never switches on a raw role.
- Derive project exclusively from server-side active session. Client db, Base_de_Datos, project_id,
  prefix and role are never authority.
- Control de Cambios is project-scoped, not week-scoped. Do not add a week selector or keep
  nueva_sem/eliminar_sem as S15-owned actions.
- Preserve the 29 persisted fields and exact requester, priority, change-type and approval
  catalogs. Unknown historical values degrade to warnings; they do not expand the write catalog.
- Treat amounts and days as canonical decimal strings on the wire. Do not coerce persisted business
  values to JavaScript numbers except inside bounded pure calculation helpers.
- Create allocates id and request date in PHP. Never send or trust an id/requestDate for create.
- Without schema changes, serialize MAX(id)+1 by locking the active project row FOR UPDATE inside
  the same transaction. Never add AUTO_INCREMENT, sequence tables or advisory locks.
- Update must not include Observaciones in SET. It remains historical and readonly.
- Create/update/delete are strict single-order atomic operations. No batch save, partial success,
  autosave or optimistic schema version.
- Apply the explicit validation decisions from the spec: Otro details, one type, 500-character
  narratives, nonnegative finite decimals, nondecreasing cost chain and date/state rules.
- Supports remain 0..20 http/https links. Do not add uploads, object storage, MIME processing,
  attachment deletion or remote URL fetching.
- Only frontend/src/lib/api/cliente.ts may call fetch. Gateways call pedir or the T01 extension.
- Every consumed response uses strict Zod. Wire/domain types come from z.infer; avoid drifting
  manual interfaces.
- Install/declare pdfmake in frontend/package.json only in Task 6. The PDF generator is pure, does
  not query the network, parse HTML or capture the DOM.
- Reuse the existing ReportController route through a narrow exporter seam. Do not create a second
  XLSX endpoint or migrate unrelated report types.
- Use native table, cards, form and dialog. Do not add DataTables, Handsontable, AG Grid, Bootstrap,
  jQuery, Font Awesome, a query/state/form library or CSS-in-JS.
- At widths below 768 mount cards only. At 768 and above mount table only. CSS hiding both mounted
  branches is not sufficient.
- Keep all 12 filters local. Do not add global search, pagination, sorting, server filtering or
  virtualisation.
- Use variables from public/css/tokens.css. Do not copy literal colors, add inline style objects,
  important declarations or unlayered module CSS.
- Dark is default/fallback and light has identical capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval. Candidate
  screenshots remain test output.
- Do not delete VIEW-10, ControlCambiosController, legacy POST list, form save modes, legacy CSS or
  week options until Task 10 proves every corresponding zero-caller and T01 transfer gate.

## File Structure

### Create — PHP domain and boundary

- src/Services/ControlChanges/ChangeOrderCatalog.php — exact enums, labels, levels and limits.
- src/Services/ControlChanges/DecimalString.php — canonical decimal parse/compare/calculation input.
- src/Services/ControlChanges/ChangeOrderCodec.php — persisted row to strict wire item and warnings.
- src/Services/ControlChanges/ChangeOrderValidator.php — create/update command validation.
- src/Services/ControlChanges/ChangeOrderActionPolicy.php — effective action booleans.
- src/Services/ControlChanges/ChangeOrderStore.php — scoped read/write/transaction port.
- src/Services/ControlChanges/PdoChangeOrderStore.php — prepared project-scoped implementation.
- src/Services/ControlChanges/ChangeOrderReadService.php — context/list orchestration.
- src/Services/ControlChanges/ChangeOrderWriteService.php — atomic create/update/delete.
- src/Services/ControlChanges/ChangeOrderReportExporter.php — narrow XLSX export port.
- src/Services/ControlChanges/PhpSpreadsheetChangeOrderExporter.php — existing 14-column output.
- tests/Support/ControlChanges/FakeChangeOrderStore.php.
- tests/Support/ControlChanges/FakeChangeOrderReportExporter.php.
- tests/fixtures/control-changes-react/rows.php.
- tests/fixtures/control-changes-react/contracts.php.
- tests/test_control_changes_react_catalog.php.
- tests/test_control_changes_react_codec.php.
- tests/test_control_changes_react_validation.php.
- tests/test_control_changes_react_policy.php.
- tests/test_control_changes_react_read_contract.php.
- tests/test_control_changes_react_write_contract.php.
- tests/test_control_changes_react_report_contract.php.
- tests/test_control_changes_react_routes.php.
- tests/test_control_changes_react_source_invariants.php.

### Create — React/TypeScript

- frontend/src/modules/control-cambios/api/esquemas.ts.
- frontend/src/modules/control-cambios/api/gateway.ts.
- frontend/src/modules/control-cambios/dominio/ordenCambio.ts.
- frontend/src/modules/control-cambios/dominio/calculos.ts.
- frontend/src/modules/control-cambios/dominio/filtros.ts.
- frontend/src/modules/control-cambios/dominio/validacion.ts.
- frontend/src/modules/control-cambios/useControlCambios.ts.
- frontend/src/modules/control-cambios/useFormularioOrdenCambio.ts.
- frontend/src/modules/control-cambios/RutaControlCambios.tsx.
- frontend/src/modules/control-cambios/BarraControlCambios.tsx.
- frontend/src/modules/control-cambios/FiltrosControlCambios.tsx.
- frontend/src/modules/control-cambios/TablaControlCambios.tsx.
- frontend/src/modules/control-cambios/TarjetasControlCambios.tsx.
- frontend/src/modules/control-cambios/DialogoOrdenCambio.tsx.
- frontend/src/modules/control-cambios/SeccionGeneral.tsx.
- frontend/src/modules/control-cambios/SeccionImpactos.tsx.
- frontend/src/modules/control-cambios/SeccionAprobacion.tsx.
- frontend/src/modules/control-cambios/EditorSoportes.tsx.
- frontend/src/modules/control-cambios/DialogoEliminarOrden.tsx.
- frontend/src/modules/control-cambios/PdfOrdenCambio.ts.
- frontend/src/modules/control-cambios/EstadoControlCambios.tsx.
- frontend/src/modules/control-cambios/control-cambios.css.
- frontend/src/types/pdfmake.d.ts — minimal local declaration if the package ships no usable types.
- Colocated test files for every schema, gateway, domain helper, hook and component above.
- tests/browser/control-cambios-react.spec.mjs.
- tests/browser/control-cambios-react.a11y.mjs.
- tests/browser/control-cambios-react.visual.mjs.
- tests/browser/fixtures/control-cambios-react.mjs.

### Modify during implementation

- public/index.php — add context and GET list; preserve aliases until cut.
- src/Controllers/Api/ControlCambiosApiController.php — thin transport and compatibility dispatch.
- src/Controllers/Integracion/ControlCambiosController.php — enforce view guard while legacy exists.
- src/Controllers/Gestion/ReportController.php — delegate only consolidado-odc to exporter boundary.
- src/Core/SpaRouter.php — canonical /control-cambios only in Task 10.
- frontend/src/App.tsx or frontend/src/shell/rutas.tsx — pilot /app/control-cambios and cut route.
- frontend/src/shell/NavegacionLateral.tsx — consume T01 action; remove stale local S15 role entry only
  if T01 has not already done it.
- frontend/src/lib/api/cliente.ts — only if T01 lacks JSON CSRF/blob/navigation support; extend
  centrally with tests, never wrap fetch elsewhere.
- frontend/package.json and frontend/package-lock.json — declare exact pdfmake dependency in Task 6.
- docs/design-system/manifests/control-cambios.json.
- docs/design-system/auditoria/censo-modulos.json and generated route inventory through canonical
  generators only at cut.
- tests/browser/shell-sidebar-rollout.mjs.
- tests/browser/control-cambios-listado.spec.mjs — replace DML setup with intercepted fixtures before
  it can remain in a gate, or retire when superseded.
- tests/browser/support/moduleFlows.mjs — remove real S15 mutations or redirect to intercepted flow.
- tests/browser/fixtures/projects.mjs — remove mutable S15 report/edit fixture assumptions.
- tests/browser/support/session.mjs — classify target APIs without introducing real writes.
- tests/test_csrf_modulos_api.php — source/fake contract only; do not run real mutation as evidence.
- docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md at closure status only.

### Delete at canonical cut only

- views/control-cambios/controlCambios.view.php.
- public/css/control-cambios.css.
- public/dist-css/control-cambios.css only if build ownership proves exclusive.
- DataTables/vendor includes exclusive to VIEW-10.
- Legacy POST list alias after zero callers.
- Legacy form/opcion save modes after zero callers.
- obtenerNombreDirector, obtenerURLCambios and actualizarFechaInicio options after zero callers.
- nueva_sem and eliminar_sem options only after T01 endpoints and caller migration are green.

Do not delete /reportes/consolidado-odc; it remains the shared D16 route.

## Vertical Checkpoints

| Checkpoint | Tasks | Demonstrable outcome | Stop condition |
|---|---|---|---|
| V1 | 1–2 | Pure strict domain plus scoped context/list | catalog drift, client authority, DML GET or malformed-wire crash |
| V2 | 3–4 | Strict gateway and responsive readonly pilot | Zod drift, fetch outside client, duplicate UI or page overflow |
| V3 | 5–6 | Full validated form, supports and pure PDF | invented rule, unsafe URL, lost draft or networked PDF |
| V4 | 7 | Atomic create/update/delete with preserved history | race, partial write, Observaciones loss or wrong capability |
| V5 | 8 | Permissioned 14-column XLSX behind fakeable seam | real file write, route duplication or field drift |
| V6 | 9 | RBAC, filters, documents, a11y, themes/viewports evidenced | unhandled request, mutation, overflow or serious axe issue |
| V7 | 10 | Canonical SPA route, aliases retired safely and rollback proven | caller remains, T01 week gap, dirty tree or red verification |

Do not start the next checkpoint while the previous stop condition is unresolved.

## Task 1: Freeze catalogs, persisted codecs and validation rules

**Files:**

- Create: src/Services/ControlChanges/ChangeOrderCatalog.php
- Create: src/Services/ControlChanges/DecimalString.php
- Create: src/Services/ControlChanges/ChangeOrderCodec.php
- Create: src/Services/ControlChanges/ChangeOrderValidator.php
- Create: tests/fixtures/control-changes-react/rows.php
- Create: tests/test_control_changes_react_catalog.php
- Create: tests/test_control_changes_react_codec.php
- Create: tests/test_control_changes_react_validation.php

**Step 1: Write failing pure tests**

- Assert exact 4 requester/responsible, 3 priority, 6 type and 5 approval catalogs and state levels.
- Characterize all 29 persisted fields.
- Cover canonical decimal strings, negative, exponent, NaN-like, currency and comma ambiguity.
- Decode valid/invalid tipoCambio and soportes without executing strings.
- Assert warnings for unknown enums, malformed JSON, invalid dates and unsafe links.
- Assert Otro, one-type, text length, cost chain and date/state decisions.
- Assert Observaciones is read-only and absent from writable shape.

**Step 2: Run the focused tests and read RC**

~~~text
docker compose exec app php tests/test_control_changes_react_catalog.php
docker compose exec app php tests/test_control_changes_react_codec.php
docker compose exec app php tests/test_control_changes_react_validation.php
~~~

Expected: each fails because the services do not exist. Read each exit code separately; do not
chain commands.

**Step 3: Implement the smallest pure domain**

- Add immutable catalog constants and lookup helpers.
- Normalize decimals without floats in the wire layer.
- Decode persisted rows into a strict associative wire shape with known warning codes.
- Encode strict write shapes back to the current JSON columns.
- Validate all conditional/date/cost/support rules exactly as S15.
- Keep tolerant read separate from strict write.

**Step 4: Run focused tests green and source guards**

~~~text
docker compose exec app php tests/test_control_changes_react_catalog.php
docker compose exec app php tests/test_control_changes_react_codec.php
docker compose exec app php tests/test_control_changes_react_validation.php
rg -n "Observaciones.*=" src/Services/ControlChanges
git diff --check
~~~

Expected: pure tests green; source search finds no writable Observaciones assignment; diff check
clean.

**Step 5: Future atomic commit**

~~~text
git add src/Services/ControlChanges tests/fixtures/control-changes-react tests/test_control_changes_react_catalog.php tests/test_control_changes_react_codec.php tests/test_control_changes_react_validation.php
git commit -m "test(control-cambios): freeze change order domain"
~~~

## Task 2: Add server-authoritative context and pure scoped list

**Files:**

- Create: src/Services/ControlChanges/ChangeOrderActionPolicy.php
- Create: src/Services/ControlChanges/ChangeOrderStore.php
- Create: src/Services/ControlChanges/PdoChangeOrderStore.php
- Create: src/Services/ControlChanges/ChangeOrderReadService.php
- Create: tests/Support/ControlChanges/FakeChangeOrderStore.php
- Create: tests/test_control_changes_react_policy.php
- Create: tests/test_control_changes_react_read_contract.php
- Create: tests/test_control_changes_react_routes.php
- Modify: public/index.php
- Modify: src/Controllers/Api/ControlCambiosApiController.php
- Modify: src/Controllers/Integracion/ControlCambiosController.php

**Step 1: Write failing contracts**

- Page route requires lps.control_cambios.ver.
- Context requires view and derives project/director/support URL/capabilities server-side.
- supportFolderUrl unsafe value becomes null.
- exportXlsx follows lps.reportes.generar, independently from edit.
- GET list is pure, no body, project-scoped and id ASC.
- Empty list is items=[]/total=0 with no sentinel.
- db/project_id/role keys cannot select another project.
- POST list legacy remains during pilot.
- Error envelopes do not contain exceptions.
- Fake call log contains no write for context/list.

**Step 2: Run focused tests red**

~~~text
docker compose exec app php tests/test_control_changes_react_policy.php
docker compose exec app php tests/test_control_changes_react_read_contract.php
docker compose exec app php tests/test_control_changes_react_routes.php
~~~

Expected: new routes/services are absent and tests fail.

**Step 3: Implement read boundary**

- Add the two GET routes.
- Resolve active project and effective capabilities before service calls.
- Query director deterministically and project URL through prepared scoped access.
- Query exact 29 columns WHERE project_id = ? ORDER BY id ASC.
- Map every row through ChangeOrderCodec.
- Return no-store strict target responses.
- Dispatch POST list through compatibility serializer without affecting GET.
- Add page capability guard.

**Step 4: Verify read boundary green**

~~~text
docker compose exec app php tests/test_control_changes_react_policy.php
docker compose exec app php tests/test_control_changes_react_read_contract.php
docker compose exec app php tests/test_control_changes_react_routes.php
rg -n "\\$_GET\\['db'\\]|\\$_POST\\['db'\\]" src/Services/ControlChanges
git diff --check
~~~

Expected: tests green; no client database authority in new services.

**Step 5: Future atomic commit**

~~~text
git add public/index.php src/Controllers/Api/ControlCambiosApiController.php src/Controllers/Integracion/ControlCambiosController.php src/Services/ControlChanges tests/Support/ControlChanges tests/test_control_changes_react_policy.php tests/test_control_changes_react_read_contract.php tests/test_control_changes_react_routes.php
git commit -m "feat(control-cambios): add scoped read contracts"
~~~

## Task 3: Build strict frontend schemas, domain helpers and gateway

**Files:**

- Create: frontend/src/modules/control-cambios/api/esquemas.ts
- Create: frontend/src/modules/control-cambios/api/esquemas.test.ts
- Create: frontend/src/modules/control-cambios/api/gateway.ts
- Create: frontend/src/modules/control-cambios/api/gateway.test.ts
- Create: frontend/src/modules/control-cambios/dominio/ordenCambio.ts
- Create: frontend/src/modules/control-cambios/dominio/calculos.ts
- Create: frontend/src/modules/control-cambios/dominio/calculos.test.ts
- Create: frontend/src/modules/control-cambios/dominio/filtros.ts
- Create: frontend/src/modules/control-cambios/dominio/filtros.test.ts
- Create: frontend/src/modules/control-cambios/dominio/validacion.ts
- Create: frontend/src/modules/control-cambios/dominio/validacion.test.ts
- Modify: frontend/src/lib/api/cliente.ts only if required

**Step 1: Write failing Vitest cases**

- Parse exact context/list/save/report target envelopes with strict rejection of unknown keys.
- Reject number-valued decimals and malformed dates in wire.
- Cover both percentage result kinds.
- Cover schedule/budget zero calculations with bounded decimal conversion.
- Cover all 12 local filters, total/filtered and reset.
- Mirror PHP validation cases using shared fixture values, not copied business types.
- Assert gateway paths/methods, empty report body and no db/project/role keys.
- Assert same-origin report URL validation.

**Step 2: Run focused frontend tests red**

~~~text
cd frontend
npm test -- src/modules/control-cambios/api/esquemas.test.ts src/modules/control-cambios/api/gateway.test.ts src/modules/control-cambios/dominio/calculos.test.ts src/modules/control-cambios/dominio/filtros.test.ts src/modules/control-cambios/dominio/validacion.test.ts
~~~

Expected: files/modules absent and run fails.

**Step 3: Implement schemas/helpers/gateway**

- Derive wire types from z.infer.
- Keep write DTO separate from read item.
- Add pure percentage/display helpers with no persisted output.
- Add one filter state shape with 12 keys.
- Call only cliente.ts for context/list/save/report.
- Reject unsafe report URLs before navigation.
- Extend cliente.ts centrally only for a missing reusable capability.

**Step 4: Run tests/typecheck/source invariant**

~~~text
cd frontend
npm test -- src/modules/control-cambios
npm run typecheck
cd ..
rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts' --glob '!*.test.ts' --glob '!test-setup.ts'
git diff --check
~~~

Expected: tests and typecheck green; fetch search empty outside the authorized client.

**Step 5: Future atomic commit**

~~~text
git add frontend/src/modules/control-cambios frontend/src/lib/api/cliente.ts
git commit -m "feat(control-cambios): add typed frontend boundary"
~~~

## Task 4: Deliver readonly pilot with filters, table and mobile cards

**Files:**

- Create: frontend/src/modules/control-cambios/useControlCambios.ts
- Create: frontend/src/modules/control-cambios/useControlCambios.test.tsx
- Create: frontend/src/modules/control-cambios/RutaControlCambios.tsx
- Create: frontend/src/modules/control-cambios/RutaControlCambios.test.tsx
- Create: frontend/src/modules/control-cambios/BarraControlCambios.tsx
- Create: frontend/src/modules/control-cambios/FiltrosControlCambios.tsx
- Create: frontend/src/modules/control-cambios/FiltrosControlCambios.test.tsx
- Create: frontend/src/modules/control-cambios/TablaControlCambios.tsx
- Create: frontend/src/modules/control-cambios/TablaControlCambios.test.tsx
- Create: frontend/src/modules/control-cambios/TarjetasControlCambios.tsx
- Create: frontend/src/modules/control-cambios/TarjetasControlCambios.test.tsx
- Create: frontend/src/modules/control-cambios/EstadoControlCambios.tsx
- Create: frontend/src/modules/control-cambios/control-cambios.css
- Modify: frontend/src/shell/rutas.tsx or frontend/src/App.tsx
- Modify: docs/design-system/manifests/control-cambios.json

**Step 1: Write failing component tests**

- Loading, one ratified empty state, initial error, stale and retry.
- Toolbar capability visibility.
- Exact 14 summary positions and accessible Open control.
- All 12 labeled filters, filtered count and reset.
- No global search/sort/paging UI.
- Same summary/actions in cards.
- MatchMedia at <768 mounts cards only; >=768 table only.
- Read-only user opens a structured detail shell but sees no write controls.
- Scroll region is focusable/named; table caption and headings exist.

**Step 2: Run focused tests red**

~~~text
cd frontend
npm test -- src/modules/control-cambios
~~~

Expected: components absent/failing.

**Step 3: Implement readonly vertical**

- Add /app/control-cambios pilot route.
- Load context/list through hook and keep serverRows separate from filteredRows.
- Render token-based toolbar, filters and state feedback.
- Render native table/cards with exclusive mount.
- Add readonly detail shell sufficient to inspect every field; Task 5 completes edit modes.
- Preserve filters across successful reload and old rows on failed reload.

**Step 4: Verify components and build**

~~~text
cd frontend
npm test -- src/modules/control-cambios
npm run typecheck
npm run build
cd ..
git diff --check
~~~

Expected: all focused tests, typecheck and build green.

**Step 5: Future atomic commit**

~~~text
git add frontend/src/modules/control-cambios frontend/src/shell/rutas.tsx frontend/src/App.tsx docs/design-system/manifests/control-cambios.json
git commit -m "feat(control-cambios): add responsive readonly pilot"
~~~

## Task 5: Build the complete create/edit/read form and support editor

**Files:**

- Create: frontend/src/modules/control-cambios/useFormularioOrdenCambio.ts
- Create: frontend/src/modules/control-cambios/useFormularioOrdenCambio.test.tsx
- Create: frontend/src/modules/control-cambios/DialogoOrdenCambio.tsx
- Create: frontend/src/modules/control-cambios/DialogoOrdenCambio.test.tsx
- Create: frontend/src/modules/control-cambios/SeccionGeneral.tsx
- Create: frontend/src/modules/control-cambios/SeccionImpactos.tsx
- Create: frontend/src/modules/control-cambios/SeccionAprobacion.tsx
- Create: frontend/src/modules/control-cambios/EditorSoportes.tsx
- Create: frontend/src/modules/control-cambios/EditorSoportes.test.tsx
- Create: frontend/src/modules/control-cambios/DialogoEliminarOrden.tsx
- Modify: frontend/src/modules/control-cambios/RutaControlCambios.tsx
- Modify: frontend/src/modules/control-cambios/control-cambios.css

**Step 1: Write failing form tests**

- create/edit/read field population and capability gates.
- Pending id and server-owned request date in create.
- Otro detail required/cleared.
- At least one of six types.
- Eight counters/500 boundary.
- Nonnegative decimal and cost-chain errors.
- Both percentage formulas, including Not calculable.
- Date/state rules and historical warning.
- Observaciones visible readonly and absent from DTO.
- Supports add/remove/reorder, 20 cap and unsafe URL rejection.
- Validation summary/focus, draft retention, double-submit lock.
- Dirty close confirmation and focus return.
- Delete names id and requires explicit confirmation.

**Step 2: Run component tests red**

~~~text
cd frontend
npm test -- src/modules/control-cambios/useFormularioOrdenCambio.test.tsx src/modules/control-cambios/DialogoOrdenCambio.test.tsx src/modules/control-cambios/EditorSoportes.test.tsx
~~~

Expected: form components absent/failing.

**Step 3: Implement accessible form**

- Use one reducer/state owner for draft/touched/errors/mode.
- Build the five spec sections with semantic fieldsets/headings.
- Keep derived percentages out of DTO.
- Preserve invalid historical values as warning until user chooses save.
- Implement accessible support reorder buttons.
- Keep save/delete gateway calls injected; Task 7 makes server writes pass.
- Implement dirty guard, validation summary and feedback without native alert.

**Step 4: Verify focused tests and keyboard semantics**

~~~text
cd frontend
npm test -- src/modules/control-cambios
npm run typecheck
cd ..
git diff --check
~~~

Expected: form/domain/component suite green; no type errors.

**Step 5: Future atomic commit**

~~~text
git add frontend/src/modules/control-cambios
git commit -m "feat(control-cambios): add validated change order form"
~~~

## Task 6: Add pure individual PDF generation

**Files:**

- Create: frontend/src/modules/control-cambios/PdfOrdenCambio.ts
- Create: frontend/src/modules/control-cambios/PdfOrdenCambio.test.ts
- Create: frontend/src/types/pdfmake.d.ts if needed
- Modify: frontend/src/modules/control-cambios/DialogoOrdenCambio.tsx
- Modify: frontend/package.json
- Modify: frontend/package-lock.json

**Step 1: Write failing PDF definition tests**

- Definition includes project/director, identity, general data, eight narratives, schedule,
  five amounts, percentages, approval, historical Observaciones and supports.
- Unsafe links are text, safe links use link annotation.
- HTML-like content stays literal text.
- Definition contains no remote image/font URL, token or session data.
- Filename is deterministic and sanitized.
- generatePdf capability controls the action independently from edit.
- Generator failure announces retry and leaves dialog open.

**Step 2: Run focused test red**

~~~text
cd frontend
npm test -- src/modules/control-cambios/PdfOrdenCambio.test.ts
~~~

Expected: generator absent/failing.

**Step 3: Declare pdfmake and implement pure adapter**

- Add exact pdfmake 0.1.70 dependency to frontend manifest/lock.
- Add the smallest type declaration needed if package types are unavailable.
- Build a pure document-definition function.
- Load only bundled fonts/resources.
- Add a lazy client adapter invoked by the button; no network request.
- Sanitize filename and all link decisions.

**Step 4: Verify dependency, tests and build**

~~~text
cd frontend
npm test -- src/modules/control-cambios/PdfOrdenCambio.test.ts src/modules/control-cambios/DialogoOrdenCambio.test.tsx
npm run typecheck
npm run build
cd ..
rg -n "fetch\\(|https?://" frontend/src/modules/control-cambios/PdfOrdenCambio.ts
git diff --check
~~~

Expected: tests/build green; source has no fetch or remote resource URL.

**Step 5: Future atomic commit**

~~~text
git add frontend/package.json frontend/package-lock.json frontend/src/types/pdfmake.d.ts frontend/src/modules/control-cambios/PdfOrdenCambio.ts frontend/src/modules/control-cambios/PdfOrdenCambio.test.ts frontend/src/modules/control-cambios/DialogoOrdenCambio.tsx
git commit -m "feat(control-cambios): add safe individual pdf"
~~~

## Task 7: Implement atomic create, update and delete

**Files:**

- Create: src/Services/ControlChanges/ChangeOrderWriteService.php
- Extend: src/Services/ControlChanges/ChangeOrderStore.php
- Extend: src/Services/ControlChanges/PdoChangeOrderStore.php
- Extend: tests/Support/ControlChanges/FakeChangeOrderStore.php
- Create: tests/test_control_changes_react_write_contract.php
- Create: tests/test_control_changes_react_source_invariants.php
- Modify: src/Controllers/Api/ControlCambiosApiController.php
- Modify: frontend/src/modules/control-cambios/useControlCambios.ts
- Modify: frontend/src/modules/control-cambios/useFormularioOrdenCambio.ts
- Modify: frontend/src/modules/control-cambios/RutaControlCambios.tsx

**Step 1: Write failing service and integration contracts**

- JSON union rejects unknown/mixed actions and authority keys.
- All actions require edit + CSRF.
- create call order is begin, project lock FOR UPDATE, MAX scoped, insert, log, commit.
- create ignores client id/date and returns canonical item.
- duplicate allocation retries within a bounded service path or returns CONFLICT.
- update validates, locks/finds project+id, excludes Observaciones, writes full strict shape, logs,
  commits and returns canonical item.
- delete locks/finds project+id, deletes composite key, logs and commits.
- missing row is NOT_FOUND; exception rolls back; no success after zero row.
- exactly one write command per request.
- form legacy dispatches through the same validator/service during pilot.
- frontend preserves draft on 422/409/500, removes item only on confirmed delete and merges canonical
  response on create/update.

**Step 2: Run focused tests red**

~~~text
docker compose exec app php tests/test_control_changes_react_write_contract.php
docker compose exec app php tests/test_control_changes_react_source_invariants.php
cd frontend
npm test -- src/modules/control-cambios/useControlCambios.test.tsx src/modules/control-cambios/useFormularioOrdenCambio.test.tsx
~~~

Expected: write service/wiring absent and tests fail.

**Step 3: Implement transactional service and hook integration**

- Add transaction primitives and project-row allocator to store.
- Keep prepared statements scoped by project_id.
- Encode current tipoCambio/soportes storage shapes.
- Omit Observaciones from update SQL.
- Place logActivity inside the transaction abstraction or make commit semantics testable so failure
  cannot report success.
- Return stable errors without Throwable text.
- Wire explicit submit/delete with one in-flight operation.

**Step 4: Verify write contracts without database**

~~~text
docker compose exec app php tests/test_control_changes_react_write_contract.php
docker compose exec app php tests/test_control_changes_react_source_invariants.php
cd frontend
npm test -- src/modules/control-cambios
npm run typecheck
cd ..
rg -n "Observaciones\\s*=|SET FOREIGN_KEY_CHECKS|\\$_GET\\['db'\\]" src/Services/ControlChanges src/Controllers/Api/ControlCambiosApiController.php
git diff --check
~~~

Expected: fake/source tests and frontend suite green; forbidden source patterns absent from new
target path. Do not run the endpoint.

**Step 5: Future atomic commit**

~~~text
git add src/Services/ControlChanges src/Controllers/Api/ControlCambiosApiController.php tests/Support/ControlChanges tests/test_control_changes_react_write_contract.php tests/test_control_changes_react_source_invariants.php frontend/src/modules/control-cambios
git commit -m "feat(control-cambios): add atomic change order writes"
~~~

## Task 8: Adapt Consolidado ODC behind a fakeable exporter

**Files:**

- Create: src/Services/ControlChanges/ChangeOrderReportExporter.php
- Create: src/Services/ControlChanges/PhpSpreadsheetChangeOrderExporter.php
- Create: tests/Support/ControlChanges/FakeChangeOrderReportExporter.php
- Create: tests/test_control_changes_react_report_contract.php
- Modify: src/Controllers/Gestion/ReportController.php
- Modify: frontend/src/modules/control-cambios/api/esquemas.ts
- Modify: frontend/src/modules/control-cambios/api/gateway.ts
- Modify: frontend/src/modules/control-cambios/BarraControlCambios.tsx

**Step 1: Write failing report contracts**

- Route requires session, active project, report capability and report CSRF independently.
- Target JSON ignores/rejects db authority.
- Query uses project_id and id ASC.
- Exporter receives rows in order and emits exact 14 headers.
- Field eight reads tiempoCronogramaAfectado, not inputTiempoCronogramaAfectado.
- Five approval labels/state fills remain.
- Success response has safe URL, filename and rowCount.
- Failure has stable code and no SQL/path/exception.
- Fake proves no directory/file is created.
- Frontend avoids double-submit and rejects cross-origin/invalid URL.
- Legacy GET/form compatibility remains until D16/zero callers.

**Step 2: Run focused tests red**

~~~text
docker compose exec app php tests/test_control_changes_react_report_contract.php
cd frontend
npm test -- src/modules/control-cambios/api/gateway.test.ts src/modules/control-cambios/BarraControlCambios.test.tsx
~~~

Expected: exporter seam and target contract absent/failing.

**Step 3: Extract only Consolidado ODC**

- Move its query/table/workbook logic behind the port without changing unrelated report cases.
- Correct the affected-days field.
- Derive project and permission in the controller target path.
- Preserve compatibility dispatch and current storage location for runtime.
- Validate returned URL/filename.
- Wire toolbar action only when exportXlsx.

**Step 4: Verify with fake only**

~~~text
docker compose exec app php tests/test_control_changes_react_report_contract.php
cd frontend
npm test -- src/modules/control-cambios/api src/modules/control-cambios/BarraControlCambios.test.tsx
npm run typecheck
cd ..
test ! -e public/storage/ordenes/s15-test-output.xlsx
git diff --check
~~~

Expected: contracts green; sentinel file absent. Do not invoke /reportes/consolidado-odc.

**Step 5: Future atomic commit**

~~~text
git add src/Services/ControlChanges src/Controllers/Gestion/ReportController.php tests/Support/ControlChanges tests/test_control_changes_react_report_contract.php frontend/src/modules/control-cambios
git commit -m "feat(control-cambios): adapt consolidated report contract"
~~~

## Task 9: Prove responsive, RBAC, documents, themes and accessibility without DML

**Files:**

- Create: tests/browser/fixtures/control-cambios-react.mjs
- Create: tests/browser/control-cambios-react.spec.mjs
- Create: tests/browser/control-cambios-react.a11y.mjs
- Create: tests/browser/control-cambios-react.visual.mjs
- Modify: tests/browser/control-cambios-listado.spec.mjs
- Modify: tests/browser/support/moduleFlows.mjs
- Modify: tests/browser/fixtures/projects.mjs
- Modify: tests/browser/support/session.mjs
- Modify: tests/browser/shell-sidebar-rollout.mjs
- Modify: frontend/src/modules/control-cambios components/styles as failures require

**Step 1: Write intercepted scenarios before navigation**

- Register handlers for context, list, save and report before page.goto.
- Assert zero unhandled target requests.
- Cover editor and readonly capabilities plus 403 revocation.
- Cover empty/populated/malformed history/error/stale.
- Cover 12 filters/count/reset and no global search.
- Cover create/update/delete success and 422/404/409/500 using in-memory fixture state only.
- Cover safe/unsafe supports, dirty close and field-error focus.
- Stub PDF download APIs and assert no extra network request.
- Stub report JSON and download navigation without filesystem output.
- Cover 390x844, 480x900, 768x1024, 1180x820 and 1440x900.
- Assert exclusive branch mount and document/page scrollWidth <= clientWidth.
- Cover dark/light, keyboard, focus return, touch size, 200% zoom and reduced motion.
- Run Axe serious/critical and collect pageerror/console.
- Rewrite or supersede the existing DML list spec before including it.

**Step 2: Run focused browser tests and observe intended failures**

~~~text
npx playwright test tests/browser/control-cambios-react.spec.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.a11y.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.visual.mjs --workers=1
~~~

Expected: newly asserted gaps fail. Each command is separate and its RC is read separately.

**Step 3: Make smallest UI/test-harness corrections**

- Correct semantics, focus, layout or feedback only within S15/shared canonical primitives.
- Remove every real S15 create/delete/report operation from moduleFlows and old spec.
- Keep screenshots as unapproved test output.
- Do not weaken assertions or update goldens.

**Step 4: Run the proportional S15 gate**

~~~text
docker compose exec app php tests/test_control_changes_react_catalog.php
docker compose exec app php tests/test_control_changes_react_codec.php
docker compose exec app php tests/test_control_changes_react_validation.php
docker compose exec app php tests/test_control_changes_react_policy.php
docker compose exec app php tests/test_control_changes_react_read_contract.php
docker compose exec app php tests/test_control_changes_react_write_contract.php
docker compose exec app php tests/test_control_changes_react_report_contract.php
docker compose exec app php tests/test_control_changes_react_routes.php
docker compose exec app php tests/test_control_changes_react_source_invariants.php
cd frontend
npm test -- src/modules/control-cambios
npm run typecheck
npm run build
cd ..
npx playwright test tests/browser/control-cambios-react.spec.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.a11y.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.visual.mjs --workers=1
git diff --check
~~~

Expected: all green, no real DML/file write, no console error, no page overflow. Read every RC on
its own line.

**Step 5: Future atomic commit**

~~~text
git add tests/browser frontend/src/modules/control-cambios
git commit -m "test(control-cambios): prove responsive accessible parity"
~~~

## Task 10: Cut the canonical route, retire exclusive legacy and rehearse rollback

**Files:**

- Modify: src/Core/SpaRouter.php
- Modify: public/index.php
- Modify: frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/NavegacionLateral.tsx if T01 still has stale local data
- Modify: docs/design-system/auditoria/censo-modulos.json through canonical generator
- Modify: generated architecture/navigation inventory through canonical generator
- Modify: docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
- Delete conditionally: views/control-cambios/controlCambios.view.php
- Delete conditionally: public/css/control-cambios.css and exclusive dist output
- Delete conditionally: legacy aliases/options listed in File Structure

**Step 1: Write failing route/caller/retirement tests**

- Canonical /control-cambios deep link renders SPA and requires view.
- Sidebar comes from T01 capabilities and marks active route.
- /app/control-cambios remains a valid pilot alias if program policy retains /app.
- Search proves no VIEW-10, DataTables or removed endpoint caller.
- Search proves actualizarFechaInicio has zero callers.
- Search proves nueva_sem/eliminar_sem consumers use green T01 contracts before removal.
- Report route remains.
- Rollback toggle/router fixture can send canonical route to VIEW-10 before deletion.
- No /admin/ or RLS/schema paths changed.

**Step 2: Run route tests red**

~~~text
docker compose exec app php tests/test_control_changes_react_routes.php
docker compose exec app php tests/test_control_changes_react_source_invariants.php
cd frontend
npm test -- src/shell/rutas.test.tsx src/modules/control-cambios
cd ..
~~~

Expected: canonical route/retirement assertions fail before cut.

**Step 3: Cut only after zero-caller evidence**

- Route canonical path through SpaRouter.
- Regenerate only canonical inventories.
- Remove VIEW-10/CSS/vendors only if every caller is zero and rollback artifact policy allows.
- Remove POST list/form modes only if network/source inventory is zero.
- Fold director/support options into context and remove after zero callers.
- Remove actualizarFechaInicio after zero callers.
- Remove week options only after T01 transfer proof.
- Keep report route and shared assets.
- Update atlas row from Por redactar to spec + plan written, not implemented.

If any zero-caller or T01 transfer check fails, keep that compatibility artifact and record the
remaining caller. Do not claim full retirement, but the SPA cut may proceed only if the artifact is
safe to retain.

**Step 4: Rehearse route-only rollback, restore target and verify full S15 gate**

~~~text
docker compose exec app php tests/test_control_changes_react_routes.php
docker compose exec app php tests/test_control_changes_react_source_invariants.php
cd frontend
npm test
npm run typecheck
npm run build
cd ..
npx playwright test tests/browser/control-cambios-react.spec.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.a11y.mjs --workers=1
npx playwright test tests/browser/control-cambios-react.visual.mjs --workers=1
rg -n "controlCambios\\.view|dataTables|actualizarFechaInicio|obtenerNombreDirector|obtenerURLCambios|nueva_sem|eliminar_sem" public src views frontend tests --glob '!docs/**'
git status --short
git diff --check
~~~

Expected: target gate green; source inventory either zero or each retained compatibility caller is
documented. Rollback changes routing/artifacts only and is restored to target. No data is touched.

**Step 5: Future atomic commit**

~~~text
git add public/index.php src/Core/SpaRouter.php src/Controllers frontend/src docs/design-system docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md tests
git add -u views/control-cambios public/css/control-cambios.css public/dist-css/control-cambios.css
git commit -m "feat(control-cambios): cut canonical react surface"
~~~

Stage only paths actually owned by S15. Omit every retained compatibility artifact and unrelated
diff. Finishing the branch, PR and CI requires the repository closure policy and separate
authorization; deployment remains separately authorized.

## Traceability Matrix

| Acceptance | Tasks | Primary evidence |
|---|---|---|
| S15-AC-01 | 1–10 | scoped diff excludes /admin/ |
| S15-AC-02 | 4, 10 | pilot/canonical deep-link tests |
| S15-AC-03 | 1, 4–8 | visible baseline plus recovered action scenarios |
| S15-AC-04 | 2, 10 | page route guard contract |
| S15-AC-05 | 2, 9, 10 | T01 sidebar capability fixtures |
| S15-AC-06 | 2, 7, 8 | server scope fake call logs |
| S15-AC-07 | 2, 3, 7, 8 | authority-key rejection contracts |
| S15-AC-08 | 2, 3 | context PHP/Zod contracts |
| S15-AC-09 | 2, 3 | list PHP/Zod contracts |
| S15-AC-10 | 3, 7 | save union PHP/Zod contracts |
| S15-AC-11 | 3, 8 | report PHP/Zod contracts |
| S15-AC-12 | 2–4, 9 | role-token source search |
| S15-AC-13 | 2, 7–9 | independent capability fixtures |
| S15-AC-14 | 2 | scoped list call log |
| S15-AC-15 | 2 | ORDER BY contract |
| S15-AC-16 | 1, 2, 4 | no-sentinel codec/empty UI |
| S15-AC-17 | 1–5 | 29-field fixture and detail component |
| S15-AC-18 | 1, 3, 5, 7 | decimal-string PHP/Zod/form cases |
| S15-AC-19 | 1, 3, 5 | ISO/null contracts |
| S15-AC-20 | 1, 4, 5 | unknown-enum warning fixtures |
| S15-AC-21 | 1, 4, 5 | malformed historical safe display |
| S15-AC-22 | 7 | allocator call log |
| S15-AC-23 | 5, 7 | create DTO/service date assertion |
| S15-AC-24 | 7 | begin/lock/max/insert call sequence |
| S15-AC-25 | 1, 3, 5, 7 | conditional detail tests |
| S15-AC-26 | 1, 5, 7 | canonical detail clearing |
| S15-AC-27 | 1, 3, 5, 7 | one-type validation |
| S15-AC-28 | 1, 3, 5, 7 | eight 500-boundary cases |
| S15-AC-29 | 1, 3, 5, 7 | decimal validation matrix |
| S15-AC-30 | 1, 3, 5, 7 | cost-chain matrix |
| S15-AC-31 | 1, 3, 5 | schedule zero calculation |
| S15-AC-32 | 1, 3, 5 | budget zero calculation |
| S15-AC-33 | 1, 3, 5, 7 | writable-shape/source invariant |
| S15-AC-34 | 1, 5, 7 | immutable request date contract |
| S15-AC-35 | 1, 3, 5, 7 | optional date matrix |
| S15-AC-36 | 1, 3, 5, 7 | final-state date matrix |
| S15-AC-37 | 1, 3, 5, 7 | study-state null date matrix |
| S15-AC-38 | 1, 5, 7 | readonly component and SQL invariant |
| S15-AC-39 | 1, 3, 5, 7 | support bounds/scheme tests |
| S15-AC-40 | 1, 5, 6, 9 | unsafe historical link scenarios |
| S15-AC-41 | 5, 9, 10 | upload control/source absence |
| S15-AC-42 | 7 | transaction/audit fake call logs |
| S15-AC-43 | 2, 7 | composite-key SQL contracts |
| S15-AC-44 | 3–5, 7, 9 | one-command/no-autosave assertions |
| S15-AC-45 | 3, 4, 9 | 12-filter fixture scenarios |
| S15-AC-46 | 3, 4, 9 | total/filtered assertions |
| S15-AC-47 | 3, 4, 9 | reset scenario |
| S15-AC-48 | 4, 9 | control/DOM absence assertions |
| S15-AC-49 | 4, 9 | table header/field contracts |
| S15-AC-50 | 4, 9 | card parity fixtures |
| S15-AC-51 | 4, 9 | exclusive mount assertions |
| S15-AC-52 | 4, 9 | five viewport scroll measurements |
| S15-AC-53 | 5, 9 | three-mode dialog scenarios |
| S15-AC-54 | 5, 9 | dirty close keyboard flow |
| S15-AC-55 | 5, 7, 9 | 422/409/500 draft assertions |
| S15-AC-56 | 5, 7, 9 | canonical response merge |
| S15-AC-57 | 6, 9 | pure document definition coverage |
| S15-AC-58 | 6, 9 | source/network tripwire |
| S15-AC-59 | 8 | 14-column exporter fixture |
| S15-AC-60 | 3, 8, 9 | same-origin URL tests |
| S15-AC-61 | 2, 4, 9 | GET-only reload/network log |
| S15-AC-62 | 4, 9, 10 | week UI absence and T01 transfer |
| S15-AC-63 | 4, 9 | drawer absence assertion |
| S15-AC-64 | 3, 6, 10 | fetch source search |
| S15-AC-65 | 2, 7, 8 | stable error fixtures |
| S15-AC-66 | 1, 2, 7, 8 | fake-only PHP suite |
| S15-AC-67 | 9 | route interception registration log |
| S15-AC-68 | 9 | old DML flow removal/source guard |
| S15-AC-69 | 4, 9 | dark/light paired scenarios |
| S15-AC-70 | 4–6, 9 | keyboard/focus/touch/zoom/axe |
| S15-AC-71 | 1–10 | schema/RLS/data diff audit |
| S15-AC-72 | 10 | zero-caller retirement proof |
| S15-AC-73 | 2, 10 | T01 week transfer gate |
| S15-AC-74 | 10 | route-only rollback rehearsal |
| S15-AC-75 | 9, 10 | visual artifact/status audit |

## Verification Commands Explicitly Forbidden in S15

Do not run these as S15 implementation evidence:

~~~text
tests/browser/control-cambios-listado.spec.mjs before its DML setup is removed
the controlCambios.edit scenario in tests/browser/support/moduleFlows.mjs
the Control de Cambios mutation/report leg in tests/browser/full-app-flow.spec.mjs
manual POST /api/control-cambios/save
manual POST /reportes/consolidado-odc
any report test that writes public/storage/ordenes
tests/test_csrf_modulos_api.php against the mounted mutable application
any SQL INSERT, UPDATE, DELETE, REPLACE, ALTER, CREATE, DROP or TRUNCATE
rollback SQL after a write
composer-wide or browser-wide suites whose DML behavior has not been classified
snapshot or golden update flags
~~~

If an executor believes a real mutation or file generation is necessary, stop and request new
authority. A transaction rollback is still DML and deleting a generated file does not make the
test safe.

## Self-Review Checklist for the Executor

- [ ] Correct worktree/branch confirmed; parent checkout untouched.
- [ ] T01 prerequisites are present and green.
- [ ] No unrelated diff was reverted or staged.
- [ ] /admin/ has no diff.
- [ ] Recovery scope remains separated from measured visible parity.
- [ ] Page, context, list, save and report have correct independent guards.
- [ ] Project comes only from server session.
- [ ] React has no role matrix.
- [ ] All 29 fields have strict wire/domain representation.
- [ ] Unknown historical enums/JSON produce safe warnings.
- [ ] Amounts/days are decimal strings and percentages never persist.
- [ ] Create id/date are server-owned and allocation is serialized.
- [ ] Otro/type/text/cost/date rules match the spec.
- [ ] Observaciones is readonly and absent from update SET.
- [ ] Supports are bounded safe links and no upload exists.
- [ ] One order is saved atomically with audit log.
- [ ] Twelve filters/count/reset work without search/sort/paging.
- [ ] Table/cards have equal information/actions and only one mounts.
- [ ] Dialog create/edit/read preserves draft and focus.
- [ ] PDF is pure, safe and network-free.
- [ ] XLSX has exact 14 columns and correct affected-days field.
- [ ] No week selector, week mutation or drawer was added.
- [ ] Dark/light, five viewports, zoom, keyboard, touch and reduced motion are evidenced.
- [ ] No fetch exists outside cliente.ts.
- [ ] No real mutation or report file write occurred in verification.
- [ ] RLS, schema, grants, users, credentials and data are unchanged.
- [ ] Visual goldens were not changed without approval.
- [ ] Zero callers/T01 transfer and rollback are proven before deletion.
- [ ] Each verification RC was read on its own line.
- [ ] git diff --check is clean.

## Cierre

Estado actual: plan escrito y autorrevisado; no ejecutado.

S15 closes only when an authorized implementation session has completed Tasks 1–10, all vertical
checkpoints are green, every acceptance criterion has evidence, no forbidden DML or report file
write occurred in tests, the canonical route cut and route-only rollback were exercised, T01 owns
week operations, the shared D16 report route remains compatible, the branch is clean, CI on the
required PR is green and the verified SHA is recorded. Deployment remains separately authorized
and is not part of this plan.
