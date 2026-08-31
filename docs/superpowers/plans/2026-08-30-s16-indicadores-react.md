# S16 Indicadores React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans in an explicitly
> authorized implementation session. Use superpowers:test-driven-development for every task and
> verification-before-completion before any completion claim. Execute tasks in order and stop at
> every vertical checkpoint. Checkbox syntax is an execution prompt only; progress and closure live
> in Cierre and git history, never in checkbox counts.

**Goal:** migrate /indicadores from VIEW-27 and its legacy script/vendor stack into the main React
SPA as an honest, responsive wrapper for the external global Power BI publish-to-web report, with
one server-authoritative permission decision, complete loading/error/offline/retry states, dark and
light framing, and a preserved but non-visual weekly indicator generation API that is scoped,
validated, CSRF-protected and atomic.

**Architecture:** T01 owns session, selected project, shell, navigation, theme and the only HTTP
client. S16 adds IndicatorsActionPolicy, a validated server-only embed configuration and GET
context. React never contains the report URL, never adds project/week filters and never calls the
generation route. A small iframe state machine models only what the host can observe. The existing
POST generator moves behind a transactional store/service over indicadores_generales, CIC and CIP;
it derives project from session and keeps the exact current aggregates. Legacy form callers remain
temporarily. T03/F5 own future BI replacement and Power BI retirement.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe and AIA design-system tokens.

**Spec:** docs/superpowers/specs/2026-08-30-s16-indicadores-react-design.md

## Global Constraints

- Work only in
  /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react on branch
  shell-minimo-react. Never use /Volumes/Crucial X6/Developer/lps-aia, the parent checkout or
  another worktree.
- Execute only after T01 shell/router/client/navigation contracts exist and are green. Reuse them;
  do not create another shell, project selector, week selector, sidebar, theme or session store.
- Inspect git status --short and relevant diffs before every task. Preserve unrelated and
  pre-existing edits. Never clean, revert or reformat adjacent work.
- This session is documentation-only. Do not implement, install dependencies, commit, push,
  publish or deploy now. Commit commands below are future instructions requiring explicit
  implementation authorization.
- /admin/ is excluded. Do not edit its controllers, views, routes, permissions or tests.
- Do not modify RLS, ProjectScope semantics, schema, migrations, tables, columns, indexes, keys,
  triggers, grants, users, credentials, memberships, roles, aliases, overrides or data.
- No DDL/DML is permitted during documentation or safe verification. Future PHP tests use pure
  policy/config/service objects, fakes, fixtures and source assertions. Browser tests intercept
  context and the external frame before navigation.
- Never call real POST /api/indicadores/generar in tests for this plan. Transaction rollback and DB
  snapshot restoration are still DML.
- Preserve lps.indicadores.ver exactly for page, context and generation. Do not invent a write
  permission or hardcode role letters in React/PHP target policy.
- Effective capability overrides and RbacService::normalizeRole remain authoritative.
- Derive project/prefix exclusively from server-side session. Client db, Base_de_Datos, project_id,
  prefix and role are never authority.
- The Power BI report is publish-to-web, global and not project/week scoped. Never append project,
  db, week, role or other LPS context to its URL.
- Do not reimplement, scrape, proxy, style, theme, test or assert KPIs inside the cross-origin frame.
- Embed configuration is server-only. It must not enter the Vite bundle, frontend fixtures committed
  with a real URL, denied responses, logs or screenshots.
- Validate configured report URL as HTTPS, exact host app.powerbi.com, exact path /view, without
  credentials or fragment. Fail closed to report unavailable.
- Do not introduce Power BI JS API, app-owns-data, embed tokens, custom postMessage or sandbox flags
  whose compatibility has not been proven.
- React does not show a Generate/Update indicators button and never calls the generator on mount,
  reload, retry, fullscreen or external-open.
- Keep the exact 980/600 ratio for desktop/tablet. Below 768 use a 70dvh useful height bounded
  320–720 px; do not force ratio at the cost of a miniature report.
- Use CSS/container layout. Do not observe sidebar state or write pixel width/height inline. Add a
  ResizeObserver only after a focused measurement proves CSS insufficient.
- Loading timeout is 20 seconds and enters slow, not definitive error. onLoad never means the
  report data is fresh or correct.
- Use native iframe/links/buttons. Do not add jQuery, Bootstrap, DataTables, Google Charts,
  AnyChart, Select2, Popper, a query library, CSS-in-JS or another visualization.
- Only frontend/src/lib/api/cliente.ts may call fetch. Gateway calls pedir or the T01 extension.
- Every consumed target response uses strict Zod. Export a generate schema/contract without wiring
  it to the S16 page.
- Generator validates week exists in active project, serializes on the project row, performs all
  existing general/CIC/CIP changes in one transaction and changes no other columns.
- Do not delete stale indicator/CIC/CIP rows, update accumulated values, add categories or normalize
  current database vocabularies.
- Use variables from public/css/tokens.css. No hex, inline styles, important declarations or
  unlayered module CSS.
- Dark is default/fallback and light has identical host capability. Required viewports are 390x844,
  480x900, 768x1024, 1180x820 and 1440x900, plus 200 percent zoom.
- Accessibility assertions explicitly exclude the iframe document; do not claim the external
  report passed Axe/theme/keyboard review.
- Do not regenerate, overwrite, hash or commit visual goldens without explicit approval.
- Do not delete VIEW-27, legacy CSS/vendors, role checks or generator aliases until Task 8 proves
  the corresponding zero-caller and rollback gates.
- Do not retire /indicadores or Power BI; F5 owns that future decision.

## File Structure

### Create — PHP

- src/Services/Indicators/IndicatorsActionPolicy.php — one effective permission decision.
- src/Services/Indicators/IndicatorsEmbedConfig.php — server-only config validation/discriminated
  availability.
- src/Services/Indicators/IndicatorsContextService.php — strict context serializer.
- src/Services/Indicators/IndicatorsGenerationStore.php — scoped transaction/aggregate/write port.
- src/Services/Indicators/PdoIndicatorsGenerationStore.php — current-table implementation.
- src/Services/Indicators/IndicatorsGenerationService.php — weekly atomic orchestration.
- tests/Support/Indicators/FakeIndicatorsGenerationStore.php.
- tests/fixtures/indicators-react/contracts.php.
- tests/fixtures/indicators-react/generation.php.
- tests/test_indicators_react_policy.php.
- tests/test_indicators_react_context_contract.php.
- tests/test_indicators_react_generation.php.
- tests/test_indicators_react_routes.php.
- tests/test_indicators_react_source_invariants.php.

### Create — React/TypeScript

- frontend/src/modules/indicadores/api/esquemas.ts.
- frontend/src/modules/indicadores/api/gateway.ts.
- frontend/src/modules/indicadores/useIndicadoresContext.ts.
- frontend/src/modules/indicadores/useEstadoIframe.ts.
- frontend/src/modules/indicadores/EstadoInformeExterno.tsx.
- frontend/src/modules/indicadores/MarcoInformeExterno.tsx.
- frontend/src/modules/indicadores/RutaIndicadores.tsx.
- frontend/src/modules/indicadores/indicadores.css.
- Colocated tests for every schema, gateway, hook and component above.
- tests/browser/fixtures/indicadores-react.mjs.
- tests/browser/indicadores-react.spec.mjs.
- tests/browser/indicadores-react.a11y.mjs.
- tests/browser/indicadores-react.visual.mjs.

### Modify during implementation

- public/index.php — add context and preserve generate route.
- src/Controllers/Gestion/IndicadoresController.php — use target policy while legacy exists.
- src/Controllers/Api/IndicadoresApiController.php — thin context/generate transport and form
  compatibility.
- src/Core/SpaRouter.php — canonical route only in Task 8.
- frontend/src/shell/rutas.tsx or frontend/src/App.tsx — pilot/canonical route.
- frontend/src/shell/NavegacionLateral.tsx — consume T01 navigation action if not already migrated.
- frontend/src/lib/api/cliente.ts — only if a reusable target capability is missing.
- docs/design-system/manifests/indicadores.json — React sources, states, layouts and retained
  third-party exception.
- docs/design-system/auditoria/censo-modulos.json and generated route/navigation inventory only
  through canonical generators at cut.
- tests/test_indicadores_server_gate.php — replace runtime/dev-door test with pure policy/transport
  tests or retire when superseded.
- tests/browser/support/moduleFlows.mjs — remove real generator call from page smoke.
- e2e/tests/workflows/lps-two-weeks.spec.mjs — migrate its consumer contract separately; it remains
  excluded from the S16 no-DML gate.
- tests/browser/support/session.mjs — JSON/CSRF helper only if shared target client needs it.
- tests/browser/shell-sidebar-rollout.mjs.
- docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md at closure status only.

### Delete at canonical cut only

- views/indicadores/indicadores.view.php.
- public/css/indicadores.css if exclusive.
- exclusive legacy vendor/script includes from VIEW-27.
- raw role lists and dead DataTables/ocultos/idioma/listar logic.
- GET/form/db generate compatibility only after all callers migrate.

Keep POST /api/indicadores/generar JSON, the React route, context and the third-party manifest
exception until F5.

## Vertical Checkpoints

| Checkpoint | Tasks | Demonstrable outcome | Stop condition |
|---|---|---|---|
| V1 | 1 | One policy plus validated authorized context | URL leak, raw role branch or client-selected config |
| V2 | 2–3 | Pure atomic generator and strict transport compatibility | DML test, cross-project call, partial transaction or formula drift |
| V3 | 4–5 | Strict frontend context and responsive external wrapper | URL in bundle, generator call, false health claim or overflow |
| V4 | 6 | Load/error/offline/a11y/themes/viewports evidenced | live Power BI dependency, unhandled request or serious Axe issue |
| V5 | 7–8 | Consumers classified, canonical cut and rollback proof | unknown caller, form alias still needed, F5 scope creep or red gate |

Do not start the next checkpoint while its stop condition is unresolved.

## Task 1: Unify permission and publish a validated context

**Files:**

- Create: src/Services/Indicators/IndicatorsActionPolicy.php
- Create: src/Services/Indicators/IndicatorsEmbedConfig.php
- Create: src/Services/Indicators/IndicatorsContextService.php
- Create: tests/fixtures/indicators-react/contracts.php
- Create: tests/test_indicators_react_policy.php
- Create: tests/test_indicators_react_context_contract.php
- Create: tests/test_indicators_react_routes.php
- Modify: public/index.php
- Modify: src/Controllers/Gestion/IndicadoresController.php
- Modify: src/Controllers/Api/IndicadoresApiController.php

**Step 1: Write failing pure contracts**

- RbacService capability, overrides and P->D drive view; no role letter list.
- Page and GET context both deny without lps.indicadores.ver.
- Denied HTML/JSON contains no report URL, host, path or query.
- Available context has exact title, provider, mode, ratio, false project/week scope and actions.
- Invalid scheme/host/path/credentials/fragment produces unavailable union.
- Config source cannot be request input.
- Context is no-store and rejects authority keys.
- Page legacy uses the same policy while it exists.

**Step 2: Run focused tests red**

~~~text
docker compose exec app php tests/test_indicators_react_policy.php
docker compose exec app php tests/test_indicators_react_context_contract.php
docker compose exec app php tests/test_indicators_react_routes.php
~~~

Expected: services/context route absent and tests fail. Read each RC separately.

**Step 3: Implement the smallest context boundary**

- Resolve effective capability through RbacService.
- Encapsulate the existing canonical report URL server-side without copying it to frontend code or
  tests; validate before serialization.
- Add GET /api/indicadores/context.
- Return the strict available/unavailable union and stable errors.
- Replace raw-role page guard with policy.
- Keep legacy view output only for authorized users.

**Step 4: Verify focused contracts and leak guards**

~~~text
docker compose exec app php tests/test_indicators_react_policy.php
docker compose exec app php tests/test_indicators_react_context_contract.php
docker compose exec app php tests/test_indicators_react_routes.php
rg -n "ROLES_SIN_INFORME|app\\.powerbi\\.com/view" src/Controllers frontend/src tests/fixtures/indicators-react
git diff --check
~~~

Expected: tests green; raw role list and URL are absent from target/controller/frontend fixtures.
The server config class may contain or retrieve the canonical runtime value.

**Step 5: Future atomic commit**

~~~text
git add public/index.php src/Controllers/Gestion/IndicadoresController.php src/Controllers/Api/IndicadoresApiController.php src/Services/Indicators tests/fixtures/indicators-react tests/test_indicators_react_policy.php tests/test_indicators_react_context_contract.php tests/test_indicators_react_routes.php
git commit -m "feat(indicadores): add authorized embed context"
~~~

## Task 2: Characterize and implement the pure generation service

**Files:**

- Create: src/Services/Indicators/IndicatorsGenerationStore.php
- Create: src/Services/Indicators/PdoIndicatorsGenerationStore.php
- Create: src/Services/Indicators/IndicatorsGenerationService.php
- Create: tests/Support/Indicators/FakeIndicatorsGenerationStore.php
- Create: tests/fixtures/indicators-react/generation.php
- Create: tests/test_indicators_react_generation.php
- Create: tests/test_indicators_react_source_invariants.php

**Step 1: Write failing fake-only characterization**

- Missing/nonpositive/nonexistent week rejects before transaction.
- Project/prefix are constructor/service scope, not command fields.
- Population is project+week and Activa=1 or NA.
- General returns NA on no rows, averages PAC/P_Completado otherwise and exact eight CNC counts.
- Existing/missing general selects update/insert without deleting duplicates/history.
- CIC iterates distinct nonblank Sub_Contratista and writes only PAC/P_Completado.
- CIP iterates distinct nonblank Responsable_AIA and writes only PAC/P_Completado.
- Call order begins transaction, locks project, calculates/writes all three projections, commits.
- Any failure rolls back and emits no success.
- Counts report entities processed.
- No accumulated/evaluation/email/delete operation appears in fake log.

**Step 2: Run pure test red**

~~~text
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
~~~

Expected: store/service absent and tests fail.

**Step 3: Implement service/store**

- Add narrow store port and fake.
- Add project-row FOR UPDATE serialization without schema changes.
- Use TableResolver only from server-derived project context.
- Use prepared, project-scoped queries.
- Preserve exact formulas/categories and touched columns.
- Keep transaction orchestration in service.
- Remove unused LpsService dependency if source inventory confirms no target use.

**Step 4: Run fake/source tests green**

~~~text
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
rg -n "\\$_GET\\['db'\\]|\\$_POST\\['db'\\]|DELETE FROM|_Acum\\s*=" src/Services/Indicators
git diff --check
~~~

Expected: tests green; no client db authority, delete or accumulated write in target service.

**Step 5: Future atomic commit**

~~~text
git add src/Services/Indicators tests/Support/Indicators tests/fixtures/indicators-react/generation.php tests/test_indicators_react_generation.php tests/test_indicators_react_source_invariants.php
git commit -m "refactor(indicadores): isolate weekly generation"
~~~

## Task 3: Adapt generation transport to strict JSON, CSRF and session scope

**Files:**

- Modify: src/Controllers/Api/IndicadoresApiController.php
- Modify: public/index.php only if transport dispatch needs a separate alias
- Extend: tests/test_indicators_react_generation.php
- Extend: tests/test_indicators_react_routes.php
- Extend: tests/test_indicators_react_source_invariants.php

**Step 1: Write failing transport contracts**

- POST target requires auth, indicadores.ver, JSON and indicadores CSRF.
- Body is exactly week positive integer.
- db/project_id/prefix/role and unknown keys reject.
- Project/prefix come from active session.
- Week must exist in project.
- Success shape includes week/status/three counts.
- 400/403/404/409/500 are stable and never include Throwable/SQL/prefix.
- GET target mutation is not accepted.
- Legacy form response adapts through the same service while caller inventory remains.
- Fake proves controller itself performs no query/write.

**Step 2: Run focused contracts red**

~~~text
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_routes.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
~~~

Expected: old controller accepts db/GET, lacks CSRF and fails target assertions.

**Step 3: Implement thin transport**

- Parse strict JSON target and verify CSRF.
- Resolve active project before constructing service/store.
- Return target envelope from service result.
- Add a temporary form compatibility branch that checks/matches session scope and CSRF.
- Remove GET mutation.
- Map exceptions to stable codes and internal logs.

**Step 4: Verify transport with fake only**

~~~text
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_routes.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
git diff --check
~~~

Expected: all focused tests green. Do not invoke the endpoint.

**Step 5: Future atomic commit**

~~~text
git add public/index.php src/Controllers/Api/IndicadoresApiController.php tests/test_indicators_react_generation.php tests/test_indicators_react_routes.php tests/test_indicators_react_source_invariants.php
git commit -m "feat(indicadores): secure generation transport"
~~~

## Task 4: Add strict frontend contracts and iframe state machine

**Files:**

- Create: frontend/src/modules/indicadores/api/esquemas.ts
- Create: frontend/src/modules/indicadores/api/esquemas.test.ts
- Create: frontend/src/modules/indicadores/api/gateway.ts
- Create: frontend/src/modules/indicadores/api/gateway.test.ts
- Create: frontend/src/modules/indicadores/useIndicadoresContext.ts
- Create: frontend/src/modules/indicadores/useIndicadoresContext.test.tsx
- Create: frontend/src/modules/indicadores/useEstadoIframe.ts
- Create: frontend/src/modules/indicadores/useEstadoIframe.test.tsx
- Modify: frontend/src/lib/api/cliente.ts only if required

**Step 1: Write failing Vitest cases**

- Strict available/unavailable context unions and stable error.
- Exact Power BI provider/mode/ratio/scope/action values.
- Generate request/response Zod exported but no page caller.
- Gateway calls only GET context with no authority query/body.
- Context loading/error/retry preserves no stale URL from denied response.
- Iframe states loading, loaded, slow at exactly 20 s, error and offline.
- load after slow moves loaded; retry remounts/cancels timeout.
- Retry preserves exact URL and never invokes generation.
- onLoad does not create a healthy/fresh data label.

**Step 2: Run focused frontend tests red**

~~~text
cd frontend
npm test -- src/modules/indicadores
~~~

Expected: module absent and tests fail.

**Step 3: Implement schemas/gateway/hooks**

- Derive types from z.infer.
- Keep runtime URL opaque after Zod.
- Add context-only gateway through cliente.ts.
- Add deterministic timer/online state machine with injectable clock/events.
- Return a retry key rather than mutating URL.
- Do not export a generation gateway from the page module.

**Step 4: Run tests/typecheck/source invariants**

~~~text
cd frontend
npm test -- src/modules/indicadores
npm run typecheck
cd ..
rg -n "fetch\\(" frontend/src --glob '!lib/api/cliente.ts' --glob '!*.test.ts' --glob '!test-setup.ts'
rg -n "generar|app\\.powerbi\\.com/view" frontend/src/modules/indicadores --glob '!*.test.ts'
git diff --check
~~~

Expected: tests/typecheck green; no fetch; generate appears only in exported schema names if needed,
never as a gateway/UI call; real URL absent.

**Step 5: Future atomic commit**

~~~text
git add frontend/src/modules/indicadores frontend/src/lib/api/cliente.ts
git commit -m "feat(indicadores): add typed embed state"
~~~

## Task 5: Build the responsive external-report wrapper

**Files:**

- Create: frontend/src/modules/indicadores/EstadoInformeExterno.tsx
- Create: frontend/src/modules/indicadores/EstadoInformeExterno.test.tsx
- Create: frontend/src/modules/indicadores/MarcoInformeExterno.tsx
- Create: frontend/src/modules/indicadores/MarcoInformeExterno.test.tsx
- Create: frontend/src/modules/indicadores/RutaIndicadores.tsx
- Create: frontend/src/modules/indicadores/RutaIndicadores.test.tsx
- Create: frontend/src/modules/indicadores/indicadores.css
- Modify: frontend/src/shell/rutas.tsx or frontend/src/App.tsx
- Modify: docs/design-system/manifests/indicadores.json

**Step 1: Write failing component tests**

- Visible h1, external badge, description and exact no-filter warning.
- No local project/week selector, KPI cards or Generate button.
- Context loading/error/unavailable states and named retry.
- Available iframe title, no-referrer, fullscreen, exact src and region description.
- Safe external link with new-tab announcement/rel.
- Loading/slow/error/offline copy.
- Retry reuses URL and returns focus.
- No innerHTML, role branch or health/freshness text.
- Host theme/layout classes and third-party exception metadata.

**Step 2: Run component tests red**

~~~text
cd frontend
npm test -- src/modules/indicadores
~~~

Expected: components absent/failing.

**Step 3: Implement pilot wrapper**

- Mount /app/indicadores.
- Build semantic header/status/region/frame/actions.
- Render one iframe only for available authorized context.
- Use desktop/tablet aspect ratio and mobile bounded 70dvh CSS.
- Let container layout react to sidebar width.
- Keep external frame content outside theme/a11y claims.
- Update manifest sources/layouts/states while retaining the exception.

**Step 4: Verify components/build/CSS guards**

~~~text
cd frontend
npm test -- src/modules/indicadores
npm run typecheck
npm run build
cd ..
rg -n "style=|#[0-9A-Fa-f]{3,8}|!important|MutationObserver|data-sidebar-state" frontend/src/modules/indicadores
git diff --check
~~~

Expected: tests/build green; forbidden styling/observer patterns absent.

**Step 5: Future atomic commit**

~~~text
git add frontend/src/modules/indicadores frontend/src/shell/rutas.tsx frontend/src/App.tsx docs/design-system/manifests/indicadores.json
git commit -m "feat(indicadores): add responsive power bi wrapper"
~~~

## Task 6: Prove host states, themes, accessibility and five viewports

**Files:**

- Create: tests/browser/fixtures/indicadores-react.mjs
- Create: tests/browser/indicadores-react.spec.mjs
- Create: tests/browser/indicadores-react.a11y.mjs
- Create: tests/browser/indicadores-react.visual.mjs
- Modify: frontend/src/modules/indicadores components/styles only as failures require

**Step 1: Write intercepted browser scenarios**

- Install context handler and app.powerbi.com/view handler before page.goto.
- Use a fake external document and never request live Power BI.
- Assert authorized available, unavailable and context 403.
- Assert 403 response/body/network logs contain no real host/path/query.
- Delay fake frame >20 s, then load; cover error/retry and offline/online.
- Count POST /api/indicadores/generar and require zero.
- Expand/collapse sidebar without inline resizing/overflow.
- Cover 390x844, 480x900, 768x1024, 1180x820 and 1440x900.
- Assert page scrollWidth <= clientWidth and frame host usable height.
- Cover dark/light host, keyboard/focus return, link, 200% zoom and reduced motion.
- Run Axe serious/critical on top document only and document iframe exclusion.
- Collect app console/pageerror; do not treat fake cross-origin console as app console.

**Step 2: Run focused browser tests and observe intended failures**

~~~text
npx playwright test tests/browser/indicadores-react.spec.mjs --workers=1
npx playwright test tests/browser/indicadores-react.a11y.mjs --workers=1
npx playwright test tests/browser/indicadores-react.visual.mjs --workers=1
~~~

Expected: new gaps fail. Read every RC separately.

**Step 3: Correct only S16 host gaps**

- Fix semantic/focus/layout/state defects in S16/shared canonical primitives.
- Keep external document assertions out.
- Keep screenshots as unapproved outputs.
- Do not update golden/hash.

**Step 4: Run proportional S16 wrapper gate**

~~~text
docker compose exec app php tests/test_indicators_react_policy.php
docker compose exec app php tests/test_indicators_react_context_contract.php
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_routes.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
cd frontend
npm test -- src/modules/indicadores
npm run typecheck
npm run build
cd ..
npx playwright test tests/browser/indicadores-react.spec.mjs --workers=1
npx playwright test tests/browser/indicadores-react.a11y.mjs --workers=1
npx playwright test tests/browser/indicadores-react.visual.mjs --workers=1
git diff --check
~~~

Expected: green; no live Power BI, generator request, DML or golden change.

**Step 5: Future atomic commit**

~~~text
git add tests/browser/fixtures/indicadores-react.mjs tests/browser/indicadores-react.spec.mjs tests/browser/indicadores-react.a11y.mjs tests/browser/indicadores-react.visual.mjs frontend/src/modules/indicadores
git commit -m "test(indicadores): prove external wrapper states"
~~~

## Task 7: Migrate and classify generation consumers without using them as S16 evidence

**Files:**

- Modify: tests/browser/support/moduleFlows.mjs
- Modify: e2e/tests/workflows/lps-two-weeks.spec.mjs
- Modify: tests/browser/support/session.mjs if shared JSON/CSRF helper is required
- Modify: tests/test_indicadores_server_gate.php
- Extend: tests/test_indicators_react_source_invariants.php

**Step 1: Write failing source/consumer assertions**

- moduleFlows Indicadores page smoke no longer calls generator.
- Browser S16 files contain zero generator call.
- Two-week workflow, if retained, sends JSON week, session project and valid CSRF without db.
- No GET generator caller remains.
- Old runtime server-gate test is replaced by pure policy contracts or removed.
- Every remaining real-DML workflow is tagged/excluded from S16 commands and retains its own
  restoration governance; S16 does not claim it as evidence.

**Step 2: Run only source/pure assertions red**

~~~text
docker compose exec app php tests/test_indicators_react_source_invariants.php
rg -n "/api/indicadores/generar" tests e2e frontend/src
~~~

Expected: old form/db calls are reported. Do not run the workflows.

**Step 3: Migrate callers**

- Replace module page smoke with route/frame-host assertion.
- Update operational two-week request contract through shared helper; do not execute it.
- Remove obsolete dev-door gate after equivalent pure policy coverage.
- Record the operational workflow as a retained DML consumer until its own authorized gate.
- Remove compatibility form branch only if source inventory is zero; otherwise keep and document
  exact caller.

**Step 4: Verify source classification**

~~~text
docker compose exec app php tests/test_indicators_react_source_invariants.php
rg -n "/api/indicadores/generar" tests e2e frontend/src
git diff --check
~~~

Expected: only strict intended consumer/contract occurrences remain; no command executes POST.

**Step 5: Future atomic commit**

~~~text
git add tests/browser/support/moduleFlows.mjs tests/browser/support/session.mjs e2e/tests/workflows/lps-two-weeks.spec.mjs tests/test_indicadores_server_gate.php tests/test_indicators_react_source_invariants.php
git commit -m "test(indicadores): migrate generation consumers"
~~~

## Task 8: Cut canonical route, retire VIEW-27 and rehearse rollback

**Files:**

- Modify: src/Core/SpaRouter.php
- Modify: public/index.php
- Modify: frontend/src/shell/rutas.tsx
- Modify: frontend/src/shell/NavegacionLateral.tsx if T01 still has stale local entry
- Modify: docs/design-system/manifests/indicadores.json
- Modify: docs/design-system/auditoria/censo-modulos.json through canonical generator
- Modify: generated route/navigation inventory through canonical generator
- Modify: docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
- Delete conditionally: views/indicadores/indicadores.view.php
- Delete conditionally: public/css/indicadores.css and exclusive dist output
- Delete conditionally: legacy generator aliases only with zero callers

**Step 1: Write failing route/retirement tests**

- Canonical /indicadores deep link renders SPA and applies policy.
- T01 sidebar visibility/active state follows effective permission.
- /app/indicadores pilot alias remains if program policy retains /app.
- Source inventory proves no VIEW-27/vendor/raw-role/dead helper caller.
- Static frontend output does not contain real report URL.
- POST JSON generator route remains.
- Form/GET aliases disappear only with zero caller.
- Third-party manifest exception points to React frame and remains.
- Rollback routing can restore legacy host before deletion.
- No /admin/, RLS/schema or F5 retirement path changed.

**Step 2: Run route tests red**

~~~text
docker compose exec app php tests/test_indicators_react_routes.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
cd frontend
npm test -- src/shell/rutas.test.tsx src/modules/indicadores
cd ..
~~~

Expected: canonical cut/retirement assertions fail before changes.

**Step 3: Cut only proven artifacts**

- Route /indicadores through SpaRouter.
- Regenerate only canonical inventories.
- Remove VIEW-27/CSS/vendor references if zero callers and rollback policy permits.
- Remove raw roles and dead script code.
- Remove generator compatibility only if Task 7 inventory is zero.
- Keep JSON generator, external context and exception.
- Update atlas to spec/plan written and implementation closure status only when true.
- Do not retire /indicadores or Power BI.

**Step 4: Rehearse route-only rollback, restore target and verify**

~~~text
docker compose exec app php tests/test_indicators_react_policy.php
docker compose exec app php tests/test_indicators_react_context_contract.php
docker compose exec app php tests/test_indicators_react_generation.php
docker compose exec app php tests/test_indicators_react_routes.php
docker compose exec app php tests/test_indicators_react_source_invariants.php
cd frontend
npm test
npm run typecheck
npm run build
cd ..
npx playwright test tests/browser/indicadores-react.spec.mjs --workers=1
npx playwright test tests/browser/indicadores-react.a11y.mjs --workers=1
npx playwright test tests/browser/indicadores-react.visual.mjs --workers=1
rg -n "indicadores\\.view|ROLES_SIN_INFORME|dataTables|anychart|select2|MutationObserver|/api/indicadores/generar" public src views frontend tests e2e --glob '!docs/**'
git status --short
git diff --check
~~~

Expected: target gate green; each retained generator caller is classified; rollback touched only
routing/artifacts, performed no generation/DML and is restored to target.

**Step 5: Future atomic commit**

~~~text
git add public/index.php src/Core/SpaRouter.php src/Controllers frontend/src docs/design-system docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md tests e2e
git add -u views/indicadores public/css/indicadores.css public/dist-css/indicadores.css
git commit -m "feat(indicadores): cut canonical react wrapper"
~~~

Stage only paths actually owned by S16. Finishing the branch, PR and CI follows repository closure
policy and separate authorization. Deployment and Power BI retirement remain separately authorized.

## Traceability Matrix

| Acceptance | Tasks | Primary evidence |
|---|---|---|
| S16-AC-01 | 1–8 | scoped diff excludes /admin/ |
| S16-AC-02 | 5, 8 | pilot/canonical deep-link tests |
| S16-AC-03 | 1–3, 8 | shared policy route contracts |
| S16-AC-04 | 1, 5, 8 | raw-role source absence |
| S16-AC-05 | 1, 6, 8 | T01 navigation capability fixture |
| S16-AC-06 | 1 | override/normalization policy cases |
| S16-AC-07 | 1, 6 | denied response/network leak guard |
| S16-AC-08 | 1, 4, 8 | build/source URL absence |
| S16-AC-09 | 1, 4 | PHP/Zod context contracts |
| S16-AC-10 | 1 | request-independent config tests |
| S16-AC-11 | 1 | URL allowlist matrix |
| S16-AC-12 | 1, 5, 6 | unavailable union/UI |
| S16-AC-13 | 5, 6 | visible heading assertions |
| S16-AC-14 | 5, 6 | external badge/description |
| S16-AC-15 | 5, 6 | exact no-filter warning |
| S16-AC-16 | 1, 4, 6 | exact URL request log |
| S16-AC-17 | 5, 8 | KPI component/source absence |
| S16-AC-18 | 4–6 | context state scenarios |
| S16-AC-19 | 4–6 | iframe loading/load scenarios |
| S16-AC-20 | 4, 6 | fake-timer/delayed-frame test |
| S16-AC-21 | 4–6 | frame error/retry |
| S16-AC-22 | 4–6 | offline/online scenario |
| S16-AC-23 | 4, 6 | exact retry URL assertion |
| S16-AC-24 | 4–6 | zero generator request count |
| S16-AC-25 | 5, 6 | safe external link |
| S16-AC-26 | 5, 6 | iframe fullscreen attribute |
| S16-AC-27 | 4–6 | copy/source absence of health claim |
| S16-AC-28 | 5, 6 | title/region association |
| S16-AC-29 | 5, 6 | desktop/tablet ratio measurement |
| S16-AC-30 | 5, 6 | mobile height/overflow measurement |
| S16-AC-31 | 5, 6 | sidebar container layout test |
| S16-AC-32 | 6 | five viewport scroll measurements |
| S16-AC-33 | 5, 6 | paired dark/light host scenarios |
| S16-AC-34 | 5, 8 | manifest exception contract |
| S16-AC-35 | 5, 6 | keyboard/focus/touch/zoom/axe |
| S16-AC-36 | 5, 6 | local selector absence |
| S16-AC-37 | 5, 6 | generate button absence |
| S16-AC-38 | 4–7 | gateway/network/source tripwire |
| S16-AC-39 | 3, 4 | generation PHP/Zod contracts |
| S16-AC-40 | 3 | CSRF transport contract |
| S16-AC-41 | 2, 3 | server scope fake call log |
| S16-AC-42 | 3 | authority-key rejection |
| S16-AC-43 | 2, 3 | week validation cases |
| S16-AC-44 | 2 | transaction/project-lock call order |
| S16-AC-45 | 2 | general aggregate fixture |
| S16-AC-46 | 2 | CIC narrow-write fixture |
| S16-AC-47 | 2 | CIP narrow-write fixture |
| S16-AC-48 | 2 | forbidden-operation call-log absence |
| S16-AC-49 | 2, 3 | typed counts response |
| S16-AC-50 | 1–3 | stable error/source tests |
| S16-AC-51 | 1–3 | pure/fake PHP suite |
| S16-AC-52 | 6 | pre-navigation route registration |
| S16-AC-53 | 6 | zero POST browser assertion |
| S16-AC-54 | 4, 8 | fetch source search |
| S16-AC-55 | 7, 8 | zero-caller alias gate |
| S16-AC-56 | 8 | VIEW/vendor retirement gate |
| S16-AC-57 | 8 | route-only rollback log |
| S16-AC-58 | 1–8 | schema/RLS/data diff audit |
| S16-AC-59 | 6, 8 | visual artifact/status audit |
| S16-AC-60 | 5, 8 | retained route/exception and no F5 diff |

## Verification Commands Explicitly Forbidden in S16

Do not run these as S16 implementation evidence:

~~~text
tests/browser/support/moduleFlows.mjs while Indicadores calls the real generator
e2e/tests/workflows/lps-two-weeks.spec.mjs
tests/browser/full-app-flow.spec.mjs
tests/test_indicadores_server_gate.php before it is pure
manual POST /api/indicadores/generar
live navigation to the configured app.powerbi.com report as a deterministic gate
any SQL INSERT, UPDATE, DELETE, REPLACE, ALTER, CREATE, DROP or TRUNCATE
rollback SQL or database snapshot restore after a write
composer-wide or browser-wide suites whose DML behavior has not been classified
snapshot or golden update flags
~~~

If an executor believes live generation or Power BI is necessary, stop and request new authority.
This plan proves the contracts with fakes/interception.

## Self-Review Checklist for the Executor

- [ ] Correct worktree/branch confirmed; parent checkout untouched.
- [ ] T01 prerequisites are present and green.
- [ ] No unrelated diff was reverted or staged.
- [ ] /admin/ has no diff.
- [ ] Page/context/generator use one effective capability policy.
- [ ] No raw role matrix remains in target.
- [ ] Denied responses and frontend bundle contain no report URL.
- [ ] Config validation is HTTPS exact host/path and fail-closed.
- [ ] UI states report only observable host facts.
- [ ] Global/no-project/no-week warning is visible.
- [ ] No KPI, local selector or generate button was introduced.
- [ ] Retry preserves URL and emits zero generator requests.
- [ ] Desktop/tablet ratio and mobile useful height are proven.
- [ ] Dark/light affect only host; third-party exception remains.
- [ ] Accessibility evidence excludes external document honestly.
- [ ] Generator accepts strict week and server scope only.
- [ ] Generator is CSRF-protected and transactional.
- [ ] General/CIC/CIP formulas and narrow writes match current behavior.
- [ ] No stale-row deletion or accumulated write was introduced.
- [ ] PHP tests use fakes and browser uses fake external document.
- [ ] No fetch exists outside cliente.ts.
- [ ] Remaining generator callers are classified; no real one was executed.
- [ ] RLS, schema, grants, users, credentials and data are unchanged.
- [ ] Visual goldens were not changed without approval.
- [ ] VIEW/aliases retire only after zero callers.
- [ ] Rollback touched routing/artifacts only.
- [ ] F5/Power BI retirement was not implemented.
- [ ] Each verification RC was read on its own line.
- [ ] git diff --check is clean.

## Cierre

Estado actual: plan escrito y autorrevisado; no ejecutado.

S16 closes only when an authorized implementation session has completed Tasks 1–8, all vertical
checkpoints are green, every acceptance criterion has evidence, no forbidden generation/DML or live
Power BI dependency occurred, the canonical route cut and route-only rollback were exercised,
remaining generator consumers are classified, the third-party exception remains honest, the branch
is clean, CI on the required PR is green and the verified SHA is recorded. Deployment and F5
retirement remain separately authorized and are not part of this plan.
