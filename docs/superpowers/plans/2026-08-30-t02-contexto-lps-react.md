---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [arquitectura, lps, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-t02-contexto-lps-react.md
resumen: "deliver one typed React contextual LPS drawer and one shell notification inbox, backed by server-authoritative activity/alert targets, the approved…"
---

# T02 Contexto LPS React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use `superpowers:executing-plans` only in an
> explicitly authorized implementation session. Use `superpowers:test-driven-development` before
> each production change and `superpowers:verification-before-completion` before a green/complete
> claim. Apply the repository branch/PR/publication gate in order. Checkbox syntax is an execution
> prompt; `Cierre` and git history are the evidence.

**Goal:** deliver one typed React contextual LPS drawer and one shell notification inbox, backed by
server-authoritative activity/alert targets, the approved hard-restriction severity model,
capability/actor-aware comments and crisis actions, honest simulation/channel semantics and a
zero-caller retirement gate for VIEW-28—without changing RLS, schema or data.

**Architecture:** T02-A adds pure restriction/severity/digest modules, scoped target/thread/action
services, additive legacy-compatible API presenters, strict Zod gateways, a single AppShell
`LpsDrawerProvider` and a single identity inbox. Four product modules provide small canonical
adapters; no grid is passed into shared code. T02-R runs only after S05/S07/S08/S25 have cut, then
removes the PHP partial, imperative JavaScript and exclusive CSS with a generated caller census.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, Zod 4, Vitest 4, Testing Library,
Playwright, existing Database/DataScope/RBAC/CSRF services, native dialog semantics and AIA tokens.

**Spec:** `docs/superpowers/specs/2026-08-30-t02-contexto-lps-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react` on
  `shell-minimo-react`. Never use `/Volumes/Crucial X6/Developer/lps-aia`, the parent checkout or a
  different worktree.
- Inspect status and relevant diffs before each task. Preserve all existing edits; never clean,
  revert or reformat adjacent files.
- This session is documentation-only. The tasks below are future instructions and do not authorize
  implementation, commits, push, PR, publication or deploy now.
- Implement T02-A only after T01 is closed and published. T01 must expose AppShell extension slots,
  dark/light bootstrap, the common client with AbortSignal/form/error support and a shell CSRF
  token usable by the compatibility notification client.
- Do not wait for S05/S07/S08/S25 to build T02-A. They consume the published provider.
- Do not run T02-R until all four consumer `Cierre` sections and an exact current caller census say
  zero legacy consumers.
- `/admin/` is excluded.
- Do not add a T02 route or sidebar navigation item. Only the notification entry belongs in shell
  utilities.
- No component calls `fetch`. All React traffic uses `frontend/src/lib/api/cliente.ts` through T02
  gateways.
- Preserve existing endpoint paths and form encoding during coexistence. Additive envelopes must
  keep the exact keys consumed by legacy.
- Do not add `lps.drawer.*` permissions. Reading uses `lps.programacion_semanal.ver`; mutating uses
  `lps.programacion_semanal.editar` plus actor/target policy.
- Normalize role decisions with `RbacService`; never put a role matrix in React.
- Require `ProjectScope` before any activity/comment/crisis read or write and keep
  `queryWithProject`/project predicates.
- Keep `system_notifications` Identity-scoped by `user_id`. Its nullable project field is not
  numeric RLS scope and is never sent to React.
- Do not modify DataScope classes, RLS boundary docs, schema, migrations, tables, FKs, indexes,
  grants, users, credentials, memberships, seeds, fixtures or persistent data.
- No DDL/DML in tests or manual verification. Use repositories, fakes, spies and intercepted browser
  routes.
- Do not run `tests/browser/escalamientos-acciones.spec.mjs` or any flow that seeds/deletes alerts.
- Do not activate, call or schedule `LpsService::escalarAlertasActivas()`.
- Mentions remain metadata. Do not emit notifications from them.
- Do not send WhatsApp/email. Browser handoff and clipboard must be described truthfully.
- Simulation remains default true under the existing storage key.
- Hard restrictions alone decide readiness; soft restrictions remain informational.
- Dark is default; light is equivalent. Use only `public/css/tokens.css` and shared T01 primitives.
- New T02 files have zero literal colors, inline style attributes/objects, CSS-in-JS and
  `!important`.
- Keep legacy golden files unchanged. Never run snapshot update flags.
- T02-A may close with VIEW-28 intentionally retained; record the compatibility inventory.
- T02-R rollback is code/assets-only and never reverses user comments, crises or notification state.
- Future commits are atomic per task/front. This documentation session makes none.

## Delivery Gates

### T02-A — Platform gate

Complete Tasks 1–11. Closure means the provider, backend seam, additive contracts, inbox and
intercepted harness are published and ready for consumers. VIEW-28, `lps_drawer.js` and adapters
remain intentionally available.

### T02-R — Retirement gate

Complete Task 12 only after S05, S07, S08 and S25 are published. Closure means zero productive
callers and safe removal of exclusive legacy assets. This deferred gate does not block product
fronts from consuming T02-A.

## Dependency Gate for T02-A

1. Read T01 `Cierre`, not its checkboxes.
2. Verify T01 provides:
   - one authenticated AppShell and outlet;
   - a provider/utility composition point;
   - `cliente.ts` transport for JSON, form-urlencoded, CSRF, AbortSignal and typed error bodies;
   - project/session generation keys;
   - dark/light bootstrap before paint;
   - shell `csrfToken` or equivalent for mark-read.
3. Verify current consumer inventory:

   ```bash
   rg -n "drawer_unificado|lps_drawer\.js|LPSContextualDrawer" views public/js src frontend tests
   rg -n "/api/lps/(comments|crisis)" public/index.php public/js src tests e2e
   rg -n "/api/notifications/(unread|read)" public/index.php public/js src frontend tests
   ```

4. Check environment once:

   ```bash
   pwd
   git branch --show-current
   git status --short
   docker compose config --services
   docker compose ps
   ```

5. Record starting SHA and pre-existing changes.
6. Run read-only/static baselines:

   ```bash
   docker compose exec -T app php tests/test_spa_frontera.php
   docker compose exec -T app php tests/test_global_table_safety.php
   node --test tests/design-system/cascada-lps-a11y.test.mjs tests/design-system/shell-drawer.test.mjs
   npx playwright test tests/browser/lps-drawer-fetch-lifecycle.mjs --workers=1
   ```

   Inspect the Playwright file first; run only the intercepted/unit harness cases if its
   operational scenario would require a real selected project under the no-DML constraint.
7. Do not use an authenticated browser result until it asserts expected route, project and heading;
   Login is not drawer evidence.
8. Confirm `git diff -- admin`, `git diff -- src/Security/DataScope` and `git diff -- database` are
   empty.

## File Structure

### Create — backend domain/services

- `src/Services/Lps/LpsTarget.php`.
- `src/Services/Lps/LpsTargetResolver.php`.
- `src/Services/Lps/LpsActivityTargetAdapter.php`.
- `src/Services/Lps/LpsActionPolicy.php`.
- `src/Services/Lps/LpsActorEligibility.php`.
- `src/Services/Lps/LpsThreadRepository.php`.
- `src/Services/Lps/ScopedLpsThreadRepository.php`.
- `src/Services/Lps/LpsThreadService.php`.
- `src/Services/Lps/LpsThreadPresenter.php`.
- `src/Services/Lps/LpsApiError.php`.

### Create — backend tests/contracts

- `tests/unit/LpsTargetResolverTest.php`.
- `tests/unit/LpsActionPolicyTest.php`.
- `tests/unit/LpsActorEligibilityTest.php`.
- `tests/unit/LpsThreadServiceTest.php`.
- `tests/unit/NotificationInboxServiceTest.php`.
- `tests/test_lps_api_contract.php`.
- `tests/test_notifications_api_contract.php`.
- `tests/test_t02_lps_caller_census.mjs`.

### Create — frontend shared runtime

- `frontend/src/shared/lps/api/esquemas.ts` and tests.
- `frontend/src/shared/lps/api/hilo.ts` and tests.
- `frontend/src/shared/lps/api/crisis.ts` and tests.
- `frontend/src/shared/lps/api/notificaciones.ts` and tests.
- `frontend/src/shared/lps/dominio/contexto.ts`.
- `frontend/src/shared/lps/dominio/restricciones.ts` and tests.
- `frontend/src/shared/lps/dominio/severidad.ts` and tests.
- `frontend/src/shared/lps/dominio/diagnostico.ts` and tests.
- `frontend/src/shared/lps/dominio/digest.ts` and tests.
- `frontend/src/shared/lps/estado/LpsDrawerProvider.tsx` and tests.
- `frontend/src/shared/lps/estado/useLpsDrawer.ts`.
- `frontend/src/shared/lps/estado/useHiloLps.ts` and tests.
- `frontend/src/shared/lps/estado/useNotificaciones.ts` and tests.
- `frontend/src/shared/lps/componentes/CajonContextualLps.tsx`.
- `frontend/src/shared/lps/componentes/DisparadorLps.tsx`.
- `frontend/src/shared/lps/componentes/DiagnosticoLps.tsx`.
- `frontend/src/shared/lps/componentes/IndicadorRestricciones.tsx`.
- `frontend/src/shared/lps/componentes/HiloLps.tsx`.
- `frontend/src/shared/lps/componentes/FormularioComentario.tsx`.
- `frontend/src/shared/lps/componentes/AccionesSos.tsx`.
- `frontend/src/shared/lps/componentes/CierreCrisis.tsx`.
- `frontend/src/shared/lps/componentes/DigestLps.tsx`.
- `frontend/src/shared/lps/componentes/BandejaNotificaciones.tsx`.
- `frontend/src/shared/lps/lps-contexto.css`.
- `frontend/src/shared/lps/testing/HarnessLps.tsx`.

### Create — browser/intercepted evidence

- `tests/browser/t02-lps-drawer-react.spec.mjs`.
- `tests/browser/t02-lps-notifications-react.spec.mjs`.

### Modify — coexistence/backend

- `src/Controllers/Api/LpsApiController.php`.
- `src/Services/LpsService.php` by delegation only.
- `src/Controllers/Core/NotificationController.php`.
- `src/Services/NotificationService.php`.
- `public/js/components/notifications.js` only as a temporary CSRF-compatible adapter if a legacy
  shell still calls it.
- `public/index.php` only if contract metadata/route aliases need a mechanical update.

### Modify — T01 seam

Use actual T01 paths after its closure; expected owners include:

- `frontend/src/shell/AppShell.tsx` or equivalent provider composition;
- `frontend/src/shell/NavegacionLateral.tsx` notification utility slot;
- `frontend/src/lib/api/cliente.ts` only for a missing generic transport ability already specified by
  T01.

### Deferred delete/trim — T02-R only

- `views/partials/drawer_unificado.php`.
- `public/js/modules/lps_drawer.js`.
- exclusive drawer rules in `public/css/handsontable-module.css`.
- `public/css/design-system/adapters/lps-drawer.css` only when S26 caller census is zero.
- obsolete compatibility glue in each consumer already cut.
- POST aliases only after explicit compatibility decision.
- **Añadido el 2026-08-31, al cerrar la Tarea 4.** Las cinco clases `LpsLegacy*` que esa tarea creó
  en `src/Services/Lps/`: `LpsLegacyGeneralActivityAdapter`, `LpsLegacyIntermediateActivityAdapter`,
  `LpsLegacyWeeklyActivityAdapter`, `LpsLegacyAlertRepository` y `LpsLegacyActorCompatibilityChecker`.
  Sus docblocks ya se declaran «temporales», pero esta lista es el inventario de retiro que manda, y
  lo temporal que no figura aquí sobrevive. Existen por dos motivos concretos, y cada uno da su
  condición de retiro: `programa_consolidado` todavía no distingue Programa General de Programación
  Intermedia (retirar cuando lo haga, momento en que los dos primeros adaptadores colapsan en uno), y
  `lps_escalamientos`/`profesionales` aún no tienen modelo propio portado (retirar cuando lo tengan).

## Task 1: Freeze ownership, routes and callers

**Owns:** AC 001–006.

**Files:**
- Create: `tests/test_t02_lps_caller_census.mjs`
- Create: `tests/test_lps_api_contract.php` with characterization-only assertions first
- Inspect: VIEW-28, legacy JS/CSS, routes and four consumers

**Step 1: Generate exact baseline inventory**

The test must enumerate:

- VIEW-28 includes in PG/PI/PS/S25;
- all `LPSContextualDrawer.init/updateContext` calls;
- endpoint registrations and aliases;
- notification client and routes;
- adapter CSS imports;
- no React T02 duplicate.

Store expected owners as structured data in the test, not a prose count.

**Step 2: Run baseline**

```bash
node --test tests/test_t02_lps_caller_census.mjs
docker compose exec -T app php tests/test_lps_api_contract.php
```

Expected: PASS against current source; four productive VIEW-28 consumers.

**Step 3: Commit characterization only**

```bash
git add tests/test_t02_lps_caller_census.mjs tests/test_lps_api_contract.php
git commit -m "test: inventariar contexto LPS compartido"
```

## Task 2: Extend the common transport only where T01 left a proven gap

**Owns:** no exclusive criteria; enables Tasks 5–9 and must stay generic.

**Files:**
- Modify only if required: `frontend/src/lib/api/cliente.ts` and its test
- Create later gateways under `frontend/src/shared/lps/api/`

**Step 1: Run T01 client tests**

```bash
npm --prefix frontend test -- --run src/lib/api/cliente.test.ts
```

Verify form-urlencoded, JSON, CSRF header/body choice, AbortSignal, no-idle-refresh and typed error
body. Do not assume a gap from the pre-T01 audit.

**Step 2: If and only if a capability is missing, add a failing generic client test**

No LPS endpoint names belong in `cliente.test.ts`. Add the smallest reusable option and keep
`fetch` in this file only.

**Step 3: Re-run and commit only a demonstrated gap**

```bash
npm --prefix frontend test -- --run src/lib/api/cliente.test.ts
npm --prefix frontend run typecheck
```

If no change was needed, do not create an empty commit.

## Task 3: Port restrictions, ITR, severity, diagnosis and digest as pure TypeScript

**Owns:** AC 036–074.

**Files:**
- Create domain files/tests under `frontend/src/shared/lps/dominio/`

**Step 1: Write failing table-driven tests**

Cover:

- both area configurations;
- hard vs soft membership and thresholds;
- percent/comma/ratio normalization;
- N/A, blank, absent and invalid;
- no-applicable-hard neutral result;
- deep gap;
- every PG/PI horizon case in the authoritative matrix;
- PS program/qualification states;
- SOS/header precedence;
- severity label/tone/action copy;
- digest grouping and no-data outcome.

**Step 2: Prove red**

```bash
npm --prefix frontend test -- --run src/shared/lps/dominio
```

Expected: FAIL because modules do not exist.

**Step 3: Implement pure functions**

No DOM, React, storage, network, dates from the wall clock or raw HTML. The caller supplies state
and restriction config. Return semantic tones, not CSS classes/colors.

**Step 4: Run focused tests/typecheck**

```bash
npm --prefix frontend test -- --run src/shared/lps/dominio
npm --prefix frontend run typecheck
```

Expected: PASS.

**Step 5: Commit the domain**

```bash
git add frontend/src/shared/lps/dominio
git commit -m "feat: extraer dominio contextual LPS"
```

## Task 4: Build the scoped target and action boundary

**Owns:** AC 011–020, AC 075–079 and AC 178–181.

**Files:**
- Create target/resolver/action/eligibility backend files and unit tests
- Inspect only: ProjectScope/DataScope/RbacCatalog/TableResolver/current schema contracts

**Step 1: Write failing unit tests with repository fakes**

Target matrix:

- activity PG/PI/PS;
- alert with persisted week/module/activity;
- XOR violations;
- malformed IDs;
- project/alert/activity mismatch;
- Pre‑Construction week zero;
- legacy consecutive without module;
- stale/closed alert by operation;
- actor eligible/profile-required/forbidden;
- read/edit capability overrides;
- terminal hierarchy.

Spies must fail if SQL mutates or if a scope predicate is omitted.

**Step 2: Prove red**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsTargetResolverTest.php tests/unit/LpsActionPolicyTest.php tests/unit/LpsActorEligibilityTest.php
```

**Step 3: Implement minimal services**

- Immutable `LpsTarget`.
- Resolver receives `ProjectScope` plus repository/adapters.
- Activity adapters are interfaces; temporary legacy adapters validate existing module datasets.
- Alert route derives all context from alert.
- Action policy returns booleans and `actorWriteBlock`.
- No session globals below controller boundary.
- No schema or DataScope modification.

**Step 4: Run tests/static guards**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsTargetResolverTest.php tests/unit/LpsActionPolicyTest.php tests/unit/LpsActorEligibilityTest.php
docker compose exec -T app php tests/test_global_table_safety.php
docker compose exec -T app php tests/test_project_scope_callsite_audit.php
```

**Step 5: Commit**

```bash
git add src/Services/Lps tests/unit/LpsTargetResolverTest.php tests/unit/LpsActionPolicyTest.php tests/unit/LpsActorEligibilityTest.php
git commit -m "feat: resolver targets LPS autorizados"
```

## Task 5: Normalize thread, comments, replies and mentions

**Owns:** AC 080–104.

**Files:**
- Create thread repository/service/presenter/error and tests
- Modify `LpsApiController` comments/addComment
- Extend `tests/test_lps_api_contract.php`

**Step 1: Write failing service/controller contracts**

Use in-memory rows for roots/replies and assert:

- scoped order;
- same-target parent;
- no reply-to-reply;
- canonical mention roles/dedup;
- unknown token not metadata;
- no actor/internal scope fields in React presenter;
- additive legacy keys;
- HTTP/status/error vocabulary;
- CSRF/capability before repository;
- profile-required;
- comment success/refetch semantics;
- no retry instruction.

**Step 2: Prove red**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsThreadServiceTest.php
docker compose exec -T app php tests/test_lps_api_contract.php
```

**Step 3: Implement by delegation**

Keep legacy endpoint paths. Controller resolves request/CSRF and selects presenter; service receives a
validated target/actor. `LpsService` may delegate old public methods temporarily; do not keep two
queries.

Render no raw exception. Do not emit `NotificationService` for mentions.

**Step 4: Run focused tests**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsThreadServiceTest.php tests/unit/LpsTargetResolverTest.php
docker compose exec -T app php tests/test_lps_api_contract.php
docker compose exec -T app php tests/test_csrf_lps_api.php
```

Before the last command, confirm its valid-token cases still fail validation before writes.

**Step 5: Commit**

```bash
git add src/Controllers/Api/LpsApiController.php src/Services/Lps src/Services/LpsService.php tests/unit/LpsThreadServiceTest.php tests/test_lps_api_contract.php
git commit -m "feat: tipar hilos y comentarios LPS"
```

## Task 6: Harden crisis actions, simulation contract and digest

**Owns:** AC 105–136.

**Files:**
- Modify backend LPS controller/services/tests
- Create frontend SOS/close/digest domain/component tests later consumed by Task 8

**Step 1: Add failing backend contracts**

Assert:

- target-derived module/week/activity;
- trigger enum;
- active-alert idempotence;
- no level change;
- capability/CSRF/actor checks;
- 100-character trimmed close;
- stale alert behavior;
- transactional repository call order via spy;
- safe errors;
- zero reference to autoescalation.

**Step 2: Add failing frontend behavior tests**

Assert simulation does not call a mutation; operational mode calls register exactly once and only
then invokes a fake channel; absent contact falls back to copy; clipboard failure exposes selectable
text; close error preserves text; success requests authoritative refresh; digest uses only supplied
items.

No real `window.open`, clipboard or network.

**Step 3: Implement minimum**

Keep legacy service semantics. External channel adapters are injected functions. Do not place phone
or email in logs/errors.

**Step 4: Run focused tests**

```bash
docker compose exec -T app php tests/test_lps_api_contract.php
npm --prefix frontend test -- --run src/shared/lps/dominio/digest.test.ts src/shared/lps/componentes/AccionesSos.test.tsx src/shared/lps/componentes/CierreCrisis.test.tsx
```

**Step 5: Commit**

```bash
git add src/Controllers/Api/LpsApiController.php src/Services/Lps frontend/src/shared/lps
git commit -m "feat: preservar acciones de crisis LPS"
```

## Task 7: Add strict Zod gateways for every shared endpoint

**Owns:** AC 153–154.

**Files:**
- Create API schema/gateway files/tests under `frontend/src/shared/lps/api/`

**Step 1: Write failing schema fixtures**

Cover exact success/error envelopes, legacy additive keys, target variants, recursive one-level
comments, mentions, actor block, crisis, notification snake_case transform, malformed date/ID,
non-2xx JSON and AbortError.

**Step 2: Prove red**

```bash
npm --prefix frontend test -- --run src/shared/lps/api
```

**Step 3: Implement gateways**

Only these files call `pedir`/the T01 transport. Components receive typed operations. Form encoding
and headers live here. No automatic mutation retry.

**Step 4: Run focused suite/typecheck**

```bash
npm --prefix frontend test -- --run src/shared/lps/api
npm --prefix frontend run typecheck
```

**Step 5: Commit**

```bash
git add frontend/src/shared/lps/api
git commit -m "feat: tipar API contextual LPS"
```

## Task 8: Build the provider, drawer and responsive accessible UI

**Owns:** AC 007–010, AC 021–035 and AC 155–177.

**Files:**
- Create provider/hooks/components/CSS/harness and tests
- Modify the actual T01 provider composition/notification slot

**Step 1: Write failing provider lifecycle tests**

Cover open/close, target switch, stale response, project/session/week generations, S25 week
independence, filtered selection, disappeared target, deep-link back, focus fallback, drafts and
partial errors.

**Step 2: Write failing component/accessibility tests**

Cover native trigger/dialog, labels, focus trap/return, Escape/overlay draft confirmation, inert,
live regions, 44px semantic contract, link BI authorization, no raw HTML, no grid/global usage and a
single mounted drawer.

**Step 3: Implement provider and components**

Compose one provider at AppShell level. Use a portal/overlay root owned by T01. Desktop grid pushes
the module via AppShell state; tablet/mobile overlay without body padding. CSS contains semantic
classes and tokens only.

**Step 4: Run tests, token guards and typecheck**

```bash
npm --prefix frontend test -- --run src/shared/lps/estado src/shared/lps/componentes
npm --prefix frontend run typecheck
rg -n '#[0-9a-fA-F]{3,8}|rgb\(|hsl\(|oklch\(|!important|style=' frontend/src/shared/lps
```

Expected: tests/typecheck PASS; detector empty.

**Step 5: Commit T02 React surface**

```bash
git add frontend/src/shared/lps frontend/src/shell
git commit -m "feat: crear cajon contextual LPS React"
```

Stage only actual T01 seam paths; do not include unrelated shell edits.

## Task 9: Migrate the single notification inbox

**Owns:** AC 137–152.

**Files:**
- Create frontend notification gateway/hook/component tests
- Create backend notification tests
- Modify NotificationController/Service
- Modify temporary legacy client only if still live

**Step 1: Write failing backend tests**

With fake identity repository:

- unauthenticated 401 JSON;
- unread only for session username;
- output omits project prefix;
- positive mark ID;
- CSRF before update;
- predicate includes username;
- zero-row/foreign/already-read stays idempotent and non-enumerative;
- no ProjectScope requirement.

**Step 2: Write failing frontend tests**

Use fake timers/document visibility for initial load, one 120s cycle, hidden pause, resume/manual
retry, unmount/logout abort, no-idle-refresh, stale data on error and mark-read after success only.
Assert one DOM list across responsive presentations.

**Step 3: Implement**

Keep snake_case transport for compatibility; gateway transforms. If legacy `notifications.js` still
runs, add the T01 shell token and CSRF without creating a second timer. Remove direct legacy client
only when its callers cut.

**Step 4: Run**

```bash
docker compose exec -T app vendor/bin/phpunit tests/unit/NotificationInboxServiceTest.php
docker compose exec -T app php tests/test_notifications_api_contract.php
npm --prefix frontend test -- --run src/shared/lps/api/notificaciones.test.ts src/shared/lps/estado/useNotificaciones.test.ts src/shared/lps/componentes/BandejaNotificaciones.test.tsx
```

**Step 5: Commit**

```bash
git add src/Controllers/Core/NotificationController.php src/Services/NotificationService.php tests/unit/NotificationInboxServiceTest.php tests/test_notifications_api_contract.php frontend/src/shared/lps public/js/components/notifications.js
git commit -m "feat: unificar bandeja de notificaciones"
```

Omit the legacy JS path if it required no change.

## Task 10: Prove the four-consumer seam without DML

**Owns:** AC 182–186.

**Files:**
- Create `HarnessLps.tsx` and browser specs
- Add adapter contract fixtures for PG/PI/PS/S25
- Do not integrate product modules yet unless their own execution front is authorized

**Step 1: Build four synthetic adapters**

Each fixture opens the same provider with canonical data:

- PG Construction and Pre‑Construction;
- PI future/deep-gap/current-critical;
- PS programming/qualification;
- S25 alert target/terminal/profile-required.

All HTTP is intercepted. Assert the provider never receives a raw row/grid.

**Step 2: Run responsive/theme/accessibility matrix**

```bash
npx playwright test tests/browser/t02-lps-drawer-react.spec.mjs tests/browser/t02-lps-notifications-react.spec.mjs --workers=1
```

Projects: 390×844, 768×1024, 1180×820, 1440×900; dark/light; reduced motion; 320/200% reflow.
Expected: no serious/critical Axe findings, no page overflow, no console/network errors.

**Step 3: Run frontend and PHP focused suites**

```bash
npm --prefix frontend test -- --run src/shared/lps
npm --prefix frontend run typecheck
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsTargetResolverTest.php tests/unit/LpsActionPolicyTest.php tests/unit/LpsActorEligibilityTest.php tests/unit/LpsThreadServiceTest.php tests/unit/NotificationInboxServiceTest.php
docker compose exec -T app php tests/test_lps_api_contract.php
docker compose exec -T app php tests/test_notifications_api_contract.php
```

**Step 4: Commit harness/evidence contracts**

Do not commit screenshots without explicit approval.

```bash
git add frontend/src/shared/lps/testing tests/browser/t02-lps-drawer-react.spec.mjs tests/browser/t02-lps-notifications-react.spec.mjs
git commit -m "test: validar plataforma contextual LPS"
```

## Task 11: Close and publish T02-A

**Owns:** platform closure; no new acceptance criteria.

**Step 1: Run final T02-A gate**

Run each command separately and record its exit code:

```bash
npm --prefix frontend test -- --run src/shared/lps
npm --prefix frontend run typecheck
docker compose exec -T app vendor/bin/phpunit tests/unit/LpsTargetResolverTest.php tests/unit/LpsActionPolicyTest.php tests/unit/LpsActorEligibilityTest.php tests/unit/LpsThreadServiceTest.php tests/unit/NotificationInboxServiceTest.php
docker compose exec -T app php tests/test_lps_api_contract.php
docker compose exec -T app php tests/test_notifications_api_contract.php
docker compose exec -T app php tests/test_csrf_lps_api.php
node --test tests/test_t02_lps_caller_census.mjs
npx playwright test tests/browser/t02-lps-drawer-react.spec.mjs tests/browser/t02-lps-notifications-react.spec.mjs --workers=1
docker compose exec -T app vendor/bin/phpstan analyse src/Services/Lps src/Controllers/Api/LpsApiController.php src/Controllers/Core/NotificationController.php src/Services/NotificationService.php --memory-limit=1G
git diff --check
```

**Step 2: Verify scope guards**

```bash
git diff -- admin
git diff -- src/Security/DataScope docs/security
git diff -- database
git status --short
git rev-parse HEAD
```

Expected: prohibited diffs empty. Record exact SHA.

**Step 3: Fill T02-A closure evidence**

Record provider/API/inbox versions, tests, caller census with four compatibility consumers, no
DDL/DML, no snapshots updated and rollback.

**Step 4: Commit closure and apply the branch/PR gate**

Use the repository publication procedure. T02-A closes when the verified code is merged/published;
VIEW-28 remains by explicit contract. Production deploy still requires separate authorization.

## Task 12: Execute T02-R after the last consumer

**Owns:** AC 187–194.

**Dependency gate:** S05, S07, S08 and S25 are closed and published; S26 caller census is current.

**Files:**
- Delete/trim only the zero-caller paths listed under deferred retirement
- Update caller census, relevant manifests and this plan `Cierre`

**Step 1: Make the zero-caller test fail on stale legacy artifacts**

Update the structured census expected count from four to zero and assert:

- no VIEW-28 include;
- no `lps_drawer.js` script/global;
- no raw LPS endpoint call outside gateways/compat tests;
- no adapter CSS productive caller;
- all four React adapters call the provider.

Run:

```bash
node --test tests/test_t02_lps_caller_census.mjs
```

Expected before removal: FAIL and list only known legacy artifacts.

**Step 2: Delete/trim the exact zero-caller artifacts**

Use `apply_patch`/normal repository edits; never bulk-delete a directory. Keep shared
`handsontable-module.css` rules unrelated to the drawer. Keep aliases unless compatibility decision
explicitly authorizes removal.

**Step 3: Re-run all four product matrices plus T02**

Run T02-A final gate and the focused browser/contract suite declared by S05/S07/S08/S25. All writes
remain intercepted. Re-run S26 static/caller census. Never update baselines automatically.

**Step 4: Verify rollback**

A code-only revert must restore the partial/adapter without data action. Do not revert comments,
crises, notification reads or DB state.

**Step 5: Fill T02-R closure and publish through PR**

Record zero-caller output, removed paths, retained aliases/reason, tests, SHA, prohibited diffs and
rollback. Apply the same fetch/integrate/reverify/push/PR/CI gate.

## Acceptance Traceability

| Criterion | Owner | Evidence |
|---|---|---|
| T02-AC-001 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-002 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-003 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-004 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-005 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-006 | Task 1 | inventory, ownership and legacy caller-census contract |
| T02-AC-007 | Task 8 | single provider and canonical consumer adapter tests |
| T02-AC-008 | Task 8 | single provider and canonical consumer adapter tests |
| T02-AC-009 | Task 8 | single provider and canonical consumer adapter tests |
| T02-AC-010 | Task 8 | single provider and canonical consumer adapter tests |
| T02-AC-011 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-012 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-013 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-014 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-015 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-016 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-017 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-018 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-019 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-020 | Task 4 | server-authoritative target resolver and scope tests |
| T02-AC-021 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-022 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-023 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-024 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-025 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-026 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-027 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-028 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-029 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-030 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-031 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-032 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-033 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-034 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-035 | Task 8 | provider lifecycle, stale-response and focus-return tests |
| T02-AC-036 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-037 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-038 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-039 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-040 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-041 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-042 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-043 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-044 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-045 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-046 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-047 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-048 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-049 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-050 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-051 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-052 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-053 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-054 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-055 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-056 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-057 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-058 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-059 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-060 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-061 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-062 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-063 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-064 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-065 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-066 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-067 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-068 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-069 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-070 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-071 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-072 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-073 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-074 | Task 3 | pure restriction, ITR, severity and diagnosis matrices |
| T02-AC-075 | Task 4 | thread target and capability policy contracts |
| T02-AC-076 | Task 4 | thread target and capability policy contracts |
| T02-AC-077 | Task 4 | thread target and capability policy contracts |
| T02-AC-078 | Task 4 | thread target and capability policy contracts |
| T02-AC-079 | Task 4 | thread target and capability policy contracts |
| T02-AC-080 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-081 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-082 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-083 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-084 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-085 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-086 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-087 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-088 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-089 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-090 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-091 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-092 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-093 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-094 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-095 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-096 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-097 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-098 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-099 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-100 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-101 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-102 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-103 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-104 | Task 5 | comments/replies/mentions API and UI contract |
| T02-AC-105 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-106 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-107 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-108 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-109 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-110 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-111 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-112 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-113 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-114 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-115 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-116 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-117 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-118 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-119 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-120 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-121 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-122 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-123 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-124 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-125 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-126 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-127 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-128 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-129 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-130 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-131 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-132 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-133 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-134 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-135 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-136 | Task 6 | crisis, simulation, channel handoff, close and digest contracts |
| T02-AC-137 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-138 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-139 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-140 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-141 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-142 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-143 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-144 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-145 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-146 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-147 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-148 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-149 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-150 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-151 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-152 | Task 9 | identity inbox, CSRF, timer and mark-read contracts |
| T02-AC-153 | Task 7 | strict Zod and cliente.ts gateway contracts |
| T02-AC-154 | Task 7 | strict Zod and cliente.ts gateway contracts |
| T02-AC-155 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-156 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-157 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-158 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-159 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-160 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-161 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-162 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-163 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-164 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-165 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-166 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-167 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-168 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-169 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-170 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-171 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-172 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-173 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-174 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-175 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-176 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-177 | Task 8 | drawer composition, accessibility, responsive and theme evidence |
| T02-AC-178 | Task 4 | ProjectScope, identity-scope and no-RLS-change guards |
| T02-AC-179 | Task 4 | ProjectScope, identity-scope and no-RLS-change guards |
| T02-AC-180 | Task 4 | ProjectScope, identity-scope and no-RLS-change guards |
| T02-AC-181 | Task 4 | ProjectScope, identity-scope and no-RLS-change guards |
| T02-AC-182 | Task 10 | no-DML contract/browser matrix and runtime evidence |
| T02-AC-183 | Task 10 | no-DML contract/browser matrix and runtime evidence |
| T02-AC-184 | Task 10 | no-DML contract/browser matrix and runtime evidence |
| T02-AC-185 | Task 10 | no-DML contract/browser matrix and runtime evidence |
| T02-AC-186 | Task 10 | no-DML contract/browser matrix and runtime evidence |
| T02-AC-187 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-188 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-189 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-190 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-191 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-192 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-193 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |
| T02-AC-194 | Task 12 | T02-R zero-caller retirement, rollback and closure evidence |

## Verification Matrix

| Contract | Evidence |
|---|---|
| ownership | structured caller census, VIEW-28 exactly once |
| target | activity/alert XOR, scope/project/week/module matrix |
| restrictions | hard/soft matrices for both areas |
| severity | PG/PI/PS table tests and textual semantics |
| thread | roots/replies/mentions/parent scope |
| actor | eligible/profile-required/forbidden |
| crisis | trigger/idempotence/close/100 chars/no autoescalation |
| simulation | no mutation, channel/copy fakes |
| digest | authorized list only, no grid/network |
| inbox | identity predicate, CSRF, 120s visible timer, one DOM |
| transport | strict Zod, common client, abort/error bodies |
| accessibility | focus/inert/drafts/live/Axe/reflow |
| themes | dark/light at four viewports |
| RLS/data | ProjectScope/Identity guards, no DataScope diff, no DDL/DML |
| coexistence | additive envelopes and four callers documented |
| retirement | zero-caller test, exact deletion and code-only rollback |

## Cierre

### T02-A

**Estado de ejecución:** no iniciado. La plataforma y sus pruebas aún no se han implementado. Al
ejecutar, registrar:

- SHA inicial/verificado:
- commits y tareas:
- PHP/frontend/browser outputs y códigos:
- caller census de compatibilidad:
- oscuro/claro/responsive/Axe:
- DDL/DML/datos:
- diffs Admin/RLS/database:
- rollback:
- PR/CI/publicación:
### T02-R

**Estado de ejecución:** diferido por diseño hasta que S05, S07, S08 y S25 estén publicados. Esto no
es un blocker de T02-A ni de sus consumidores. Al ejecutar, registrar:

- SHAs de los cuatro consumidores:
- censo cero:
- archivos retirados:
- aliases retenidos/retirados y decisión:
- regresiones de cuatro superficies:
- S26 caller census:
- rollback:
- PR/CI/publicación:
