---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-08-30-s05-programa-general-react.md
resumen: "migrate /programa-general from VIEW-34 + Handsontable/jQuery to a native React surface with complete read/edit/operations/drawer parity, server-resolved…"
---

# S05 Programa General React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development`
> (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax only as execution prompts; progress and closure follow the repository
> ledger/commit policy, not retrospective checkbox counting.

**Goal:** migrate `/programa-general` from VIEW-34 + Handsontable/jQuery to a native React surface
with complete read/edit/operations/drawer parity, server-resolved permissions, dark and light themes,
desktop/tablet tables, editable mobile cards, and a gate-controlled retirement of legacy-exclusive
assets without changing RLS or data.

**Architecture:** T01 supplies the shell, server-driven navigation, account, project and week
bootstrap; S04 supplies project switching. S05 adds one read-only module context endpoint, adapts the
existing General/Report/LPS endpoints behind Zod gateways, and keeps PHP authoritative for scope,
permissions, confirmation, state and carryover. A normalized TypeScript model feeds one filter
pipeline and two responsive renderers. The pilot runs at `/app/programa-general`; canonical GET/HEAD
is promoted only after functional, RBAC, responsive, theme and accessibility gates, then VIEW-34 and
its exclusive assets are retired.

**Tech Stack:** PHP 8.3 in Docker Compose, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4,
Vitest 4, Testing Library, Playwright 1.61, Axe, PhpSpreadsheet and AIA design-system tokens.

**Spec:** `docs/superpowers/specs/2026-08-29-programa-general-react-design.md`

## Global Constraints

- Work only in
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, branch
  `shell-minimo-react`; never use `/Volumes/Crucial X6/Developer/lps-aia` or another checkout.
- Execute after T01, S04 and their shared shell contracts are present. Reuse `BrowserRouter`,
  `BarraLateral`, server-driven `navigation.groups`, week bootstrap, project switch, dark-first
  theme, `ErrorApi` and method-aware `SpaRouter`; do not recreate them inside S05.
- Inspect `git status --short` and the relevant diff before every task. Preserve all unrelated and
  pre-existing changes; no courtesy refactors.
- Documentation phase only now: do not implement, commit, push, publish or deploy. Every commit
  command below is an instruction for a later, explicitly authorized execution session.
- `/admin/` is excluded. Do not read as an implementation dependency, edit, test, route, restyle or
  publish any admin surface.
- Do not modify RLS, `ProjectScope` semantics, schema, migrations, grants, users, credentials,
  memberships, role aliases, permission assignments or database data. Do not execute DDL/DML.
- PHP is authoritative for project, week existence, confirmation, actions, state, carryover, report
  scope and drawer scope. React never sends `db`, table prefix, `project_id`, user, role, area or a
  client-authored permission/confirmation flag.
- Preserve `lps.programa_general.ver/editar`, `lps.reportes.generar`,
  `lps.programacion_semanal.ver/editar`, `canEditPastGeneralProgram` and role normalization. Fix UI
  contradictions by consuming effective server actions, never by expanding capabilities.
- All productive HTTP calls remain in `frontend/src/lib/api/cliente.ts`; components and hooks call
  typed gateways. Every response is parsed by Zod before entering domain/components.
- Every new endpoint has a PHP contract test. Existing consumed endpoints gain a Zod contract and a
  PHP/pure characterization before their behavior is changed.
- Mutations are never automatically retried. Tests for save, batch, cut, comments and crisis use
  pure fakes or Playwright interception; they never reach a real write path. Do not run existing DB
  mutation suites such as `tests/test_general_api_update_without_user.php`,
  `tests/test_pg_pasado_servidor.php`, `tests/browser/programa-general-runtime-requests.mjs` or
  `tests/browser/full-app-flow.spec.mjs` during S05 verification.
- Keep PHP state calculation, `WeeklyRealProgressCarryoverService`,
  `ProgramaConsolidadoNormalizationService`, `PgAvanceEdicionManualService`, report workbook layout
  and LPS comment/crisis services. Extract test seams; do not port these algorithms to TypeScript.
- Use only `public/css/tokens.css` tokens. No literal colors, inline style objects, `!important`,
  Bootstrap, jQuery, Handsontable, Toastr, Font Awesome, CSS-in-JS or a new grid/state/query library.
- Dark is default/fallback; light has identical capability. Required viewports are `390×844`,
  `768×1024`, `1180×820` and `1440×900`; no horizontal page overflow.
- Do not regenerate, overwrite or bless visual goldens without explicit approval. New screenshots are
  candidates outside git until approved.
- No real dev-door login, project selection, week write, XLSX generation, comment/crisis write or
  shared-session probe is part of this plan. Browser coverage intercepts every operational request.

## File Structure

### Create

- `frontend/src/lib/api/esquemas/programa-general.ts` — strict context, list, code, mutation, report
  and drawer schemas; all S05 TypeScript types come from `z.infer`.
- `frontend/src/lib/api/esquemas/programa-general.test.ts` — strictness, legacy mixed types,
  recursive comments, safe XLSX path and error forms.
- `frontend/src/lib/api/programa-general.ts` and `.test.ts` — the only S05 gateway, exact methods,
  queries, forms, CSRF and no retry.
- `frontend/src/modules/programa-general/dominio/normalizarPrograma.ts` and `.test.ts` — row adapter,
  units, ratios, dates, chapters and server states.
- `frontend/src/modules/programa-general/dominio/filtrarPrograma.ts` and `.test.ts` — search, column
  filters, chips, facet counts and URL query codec.
- `frontend/src/modules/programa-general/dominio/validarActividad.ts` and `.test.ts` — shared client
  validation and canonical update form.
- `frontend/src/modules/programa-general/dominio/exportarCsv.ts` and `.test.ts` — visible thirteen
  columns, chapters, RFC 4180 and BOM.
- `frontend/src/modules/programa-general/dominio/retornoLote.ts` and `.test.ts` — project/week scoped
  `sessionStorage` state machine.
- `frontend/src/modules/programa-general/useProgramaGeneral.ts` and `.test.tsx` — context/list/codes,
  cancellation, drafts, reload, individual save and batch orchestration.
- `frontend/src/modules/programa-general/ProgramaGeneralPage.tsx` and `.test.tsx` — page state machine
  and shell composition.
- `frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx`,
  `ProgramaFilters.tsx`, `ProgramaTable.tsx`, `ProgramaCards.tsx`, `EditorActividad.tsx`,
  `ProgramaLegend.tsx`, `ProgramaDrawer.tsx` and focused `.test.tsx` files.
- `frontend/src/modules/programa-general/programa-general.css` — scoped, token-driven responsive
  presentation for both themes.
- `src/Security/ProgramaGeneralActionPolicy.php` — pure action matrix for edit/progress/batch.
- `src/Services/ProgramaGeneralContextService.php` — scoped week/context/restriction/action reader.
- `src/Services/ProgramaGeneralInputValidator.php` — strict dates, quantities, units and ratios.
- `src/Services/CorteProgramaGeneralService.php` — report generation seam around the existing
  workbook logic.
- `src/Services/LpsContextResolver.php` — shared active-scope/user/week resolver without an area
  hard-code, reusable by S07/S08.
- `src/Controllers/Api/ProgramaGeneralContextApiController.php` — `GET` context adapter.
- `tests/test_programa_general_restriction_contract.php` — pure presentation catalog contract.
- `tests/test_programa_general_action_policy.php` — complete roles/week/confirmation decision table.
- `tests/test_programa_general_context_contract.php` — new endpoint response contract with fakes.
- `tests/test_programa_general_input_validation.php` — pure save normalization/validation contract.
- `tests/test_programa_general_cut_contract.php` — fake generator, safe response, no file write.
- `tests/test_lps_programa_general_scope.php` — active `ProjectScope` for both areas, no DML.
- `tests/browser/fixtures/programa-general-react.mjs` — synthetic Construction/Preconstruction data.
- `tests/browser/programa-general-react.spec.mjs` — functional/RBAC/responsive requests intercepted.
- `tests/browser/programa-general-react.a11y.mjs` — keyboard, focus, axe, zoom and reflow.
- `tests/browser/programa-general-react.visual.mjs` — uncommitted visual candidates only.

### Modify

- `frontend/src/lib/api/cliente.ts` and `.test.ts` — JSON, `URLSearchParams`, `FormData`, JSON errors,
  `AbortSignal` and no mutation retry.
- `frontend/src/lib/api/esquemas/sesion.ts` and tests created by T01 — consume server-driven groups
  and week; no S05-specific navigation branch.
- `frontend/src/shell/rutas.tsx` and `.test.tsx` — route aliases and protected S05 outlet.
- `frontend/src/shell/navegacion/BarraLateral.tsx` and `.test.tsx` — active link and week/project
  transition integration only; structure remains T01-owned.
- `frontend/src/App.tsx` only if T01's router outlet requires the S05 route registration.
- `frontend/src/main.tsx` only to import the S05 stylesheet if route-level import is unavailable.
- `src/Services/RestrictionConfigResolver.php` — add one pure presentation catalog and custom PC
  labels; preserve existing threshold calculations.
- `src/Controllers/Api/GeneralApiController.php` — shared restriction resolver, scoped list, strict
  preflight/confirmed guard and batch request cleanup while preserving S06 behavior.
- `src/Controllers/Programacion/ProgramaGeneralController.php` — consume shared restriction resolver
  during pilot; remove duplicated PC catalog before VIEW-34 retirement.
- `src/Controllers/Gestion/ReportController.php` — session/scope + CSRF and injectable cut service.
- `src/Controllers/Api/LpsApiController.php` — active scope resolution, injectable service, strict
  mentions and canonical payload validation.
- `public/index.php` — new context route, pilot/canonical routing and post-gate legacy route cleanup.
- `src/Core/SpaRouter.php`, `tests/test_spa_frontera.php`, `tests/test_spa_frontera_http.php` —
  method-aware pilot/canonical boundary.
- `docs/design-system/manifests/programa-general.json`, relevant design inventories/exceptions and
  `tests/design-system/contracts.test.mjs` — React sources, roles, states, tests and scenarios.
- Existing PG design/runtime tests whose assertions name VIEW-34/Handsontable — migrate them to the
  React contract or retire them only when their behavior has equivalent coverage.
- `public/app/index.html` and `public/app/assets/index-*` — generated by Vite; never hand-edit.
- `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — mark S05 plan/cut status
  only after the corresponding evidence exists.

### Preserve

- `src/Legacy/estado_programa_general.php`, `LpsService::calculateGeneralStatus()`,
  `WeeklyRealProgressCarryoverService`, `ProgramaConsolidadoNormalizationService`,
  `PgAvanceEdicionManualService`, audit events and report sheet semantics.
- T01 shell ownership, S04 project selection, S06 General import/delete/associate/decision routes,
  all S07/S08 drawer consumers, Plan de Compras v2, BI implementation and every `/admin/` file.
- Database tables, rows, project memberships, session records, RLS, grants and credentials.

### Retire only after the post-canonical gate

- `views/programa-general/programa_general.view.php` (VIEW-34).
- `public/js/modules/programa_general/hot.js`.
- `public/css/programa-general.css` when reference audit proves it is exclusive.
- GET page handling in `ProgramaGeneralController::index()` and legacy-only filter methods/routes
  `/programa-general/filtros`, `/programa-general/set-filtro`.
- VIEW-34-only test expectations and vendor declarations; never shared General API methods or LPS
  drawer aliases still consumed by S07/S08.

---

### Task 1: Add the scoped context, shared restriction catalog and action policy

**Files:**
- Create: `src/Security/ProgramaGeneralActionPolicy.php`
- Create: `src/Services/ProgramaGeneralContextService.php`
- Create: `src/Controllers/Api/ProgramaGeneralContextApiController.php`
- Modify: `src/Services/RestrictionConfigResolver.php`
- Modify: `src/Controllers/Api/GeneralApiController.php`
- Modify: `src/Controllers/Programacion/ProgramaGeneralController.php`
- Modify: `public/index.php`
- Create: `tests/test_programa_general_restriction_contract.php`
- Create: `tests/test_programa_general_action_policy.php`
- Create: `tests/test_programa_general_context_contract.php`

**Interfaces:**
- `RestrictionConfigResolver::presentationConfig(string $area, array $pcLabels = []): array` returns
  `area`, ordered `restrictions`, `hardRestrictions`, `softRestrictions` with percentage thresholds.
- `ProgramaGeneralActionPolicy::resolve(bool $canEdit, bool $canEditPast, int $week, int $maxWeek,
  bool $confirmed, bool $canDownload, bool $canReadDrawer, bool $canWriteDrawer): array` returns the
  six S05 action booleans from effective capabilities and the server week snapshot.
- `ProgramaGeneralContextService::build(ProjectScope $scope, array $session): array` is read-only and
  returns the exact `data` object consumed by Task 1.
- `ProgramaGeneralContextApiController::show()` owns `GET /api/programa-general/context` and emits
  `Cache-Control: no-store`.

- [ ] **Step 1: Write failing pure PHP contracts**

The restriction test must prove order, labels, types, thresholds and options for both areas,
including omission of unnamed PC soft restrictions:

```php
$construction = RestrictionConfigResolver::presentationConfig('Construccion');
assertSame(
    ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'],
    array_column($construction['restrictions'], 'key'),
);
assertSame(['0%', '50%', '100%', 'N/A'], $construction['restrictions'][4]['options']);

$pre = RestrictionConfigResolver::presentationConfig('Pre-Construccion', [
    'restriccion_pc_2' => 'Licencia',
    'restriccion_pc_3' => '',
    'restriccion_pc_4' => 'Cliente',
]);
assertSame(
    ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_4'],
    array_column($pre['restrictions'], 'key'),
);
assertSame(['0%', '50%', '100%', 'N/A'], $pre['restrictions'][0]['options']);
```

The policy test must table-drive every branch without role literals in production:

```php
$cases = [
    'reader' => [[false, false, 6, 6, false], [false, false, false]],
    'current editor' => [[true, false, 6, 6, false], [true, true, true]],
    'current confirmed' => [[true, false, 6, 6, true], [false, true, true]],
    'past privileged' => [[true, true, 4, 6, false], [true, true, true]],
    'past privileged confirmed' => [[true, true, 4, 6, true], [false, true, true]],
    'past ordinary editor' => [[true, false, 5, 6, false], [false, false, false]],
];
foreach ($cases as $label => [$input, $expected]) {
    $actions = ProgramaGeneralActionPolicy::resolve(
        canEdit: $input[0], canEditPast: $input[1], week: $input[2], maxWeek: $input[3],
        confirmed: $input[4], canDownload: true, canReadDrawer: true, canWriteDrawer: true,
    );
    assertSame($expected, [
        $actions['editPlanFields'], $actions['editProgress'], $actions['runBatch'],
    ], $label);
}
```

The context contract supplies a fake DB/result set, fake permission resolver, fake BI resolver and
deterministic token factory. Assert exact top-level/nested keys, no `db`, prefix, role or user, PC
labels, `number=0` behavior, BI `null`, and the route registration.

- [ ] **Step 2: Run the pure contracts and confirm RED**

```bash
docker compose exec -T app php tests/test_programa_general_restriction_contract.php
docker compose exec -T app php tests/test_programa_general_action_policy.php
docker compose exec -T app php tests/test_programa_general_context_contract.php
```

Expected: each command exits non-zero because the new API and pure methods do not exist.

- [ ] **Step 3: Implement the smallest shared PHP boundary**

`ProgramaGeneralActionPolicy` contains no DB/session access:

```php
public static function resolve(
    bool $canEdit,
    bool $canEditPast,
    int $week,
    int $maxWeek,
    bool $confirmed,
    bool $canDownload,
    bool $canReadDrawer,
    bool $canWriteDrawer,
): array {
    $validWeek = $week > 0 && $maxWeek > 0 && $week <= $maxWeek;
    $editableWeek = $validWeek && ($week === $maxWeek || ($week < $maxWeek && $canEditPast));
    $mayMutate = $canEdit && $editableWeek;

    return [
        'editPlanFields' => $mayMutate && !$confirmed,
        'editProgress' => $mayMutate,
        'runBatch' => $mayMutate,
        'downloadCut' => $validWeek && $canDownload,
        'readDrawer' => $validWeek && $canReadDrawer,
        'writeDrawer' => $validWeek && $canWriteDrawer,
    ];
}
```

`presentationConfig()` reuses existing hard/soft key and ratio threshold methods, converts threshold
ratios to integer percentages, assigns the audited labels/options, and filters only empty custom PC
labels. Do not change `calculateEstadoRestricciones()`.

The context service reads only the active `ProjectScope` project, session prefix/name/area/week,
`semanas_activas` max/confirmation, PC labels and effective permissions. It accepts optional test
callables for permission, BI and token generation; production defaults call `RbacService`,
`BiAccessComponent` and `CsrfTokenManager`. The controller still calls `requireAuth()` and
`authorizePermission('lps.programa_general.ver')` before the service.

Register exactly:

```php
$router->get(
    '/api/programa-general/context',
    [\App\Controllers\Api\ProgramaGeneralContextApiController::class, 'show'],
);
```

Replace both inline restriction catalogs with `presentationConfig()` during the pilot. Add
`lps.programa_general.ver` to `restrictionConfig()`; preserve its existing JSON shape.

- [ ] **Step 4: Verify the contracts and legacy threshold regression GREEN**

```bash
docker compose exec -T app php tests/test_programa_general_restriction_contract.php
docker compose exec -T app php tests/test_programa_general_action_policy.php
docker compose exec -T app php tests/test_programa_general_context_contract.php
docker compose exec -T app vendor/bin/phpunit tests/unit/EstadoProgramaGeneralTest.php --group puro
```

Expected: all commands exit `0`; the last proves state calculation was not moved or changed.

- [ ] **Step 5: Commit the context slice (future execution only)**

```bash
git add src/Security/ProgramaGeneralActionPolicy.php src/Services/ProgramaGeneralContextService.php src/Services/RestrictionConfigResolver.php src/Controllers/Api/ProgramaGeneralContextApiController.php src/Controllers/Api/GeneralApiController.php src/Controllers/Programacion/ProgramaGeneralController.php public/index.php tests/test_programa_general_restriction_contract.php tests/test_programa_general_action_policy.php tests/test_programa_general_context_contract.php
git commit -m "feat(programa-general): expose scoped module context"
```

---

### Task 2: Freeze S05 HTTP contracts and upgrade the shared client

**Files:**
- Modify: `frontend/src/lib/api/cliente.ts`
- Modify: `frontend/src/lib/api/cliente.test.ts`
- Create: `frontend/src/lib/api/esquemas/programa-general.ts`
- Create: `frontend/src/lib/api/esquemas/programa-general.test.ts`
- Create: `frontend/src/lib/api/programa-general.ts`
- Create: `frontend/src/lib/api/programa-general.test.ts`

**Interfaces:**
- Produces only `z.infer` types: `ContextoProgramaGeneral`, `FilaProgramaGeneralLegacy`,
  `CodigoProgramaGeneral`, `SolicitudGuardarActividad`, `ResultadoGuardarActividad`,
  `ResultadoLote`, `ResultadoCorte`, `ComentarioLps`.
- Produces gateways `obtenerContextoProgramaGeneral`, `listarProgramaGeneral`,
  `listarCodigosProgramaGeneral`, `guardarActividadProgramaGeneral`,
  `actualizarEjecucionProgramaGeneral`, `solicitarCorteProgramaGeneral`,
  `cargarComentariosLps`, `agregarComentarioLps`, `registrarCrisisLps`, `cerrarCrisisLps`.
- Consumes `pedir()` only. Components never receive URL, headers, `RequestInit` or legacy response
  objects.

- [ ] **Step 1: Write failing client and strict-schema tests**

Add cases proving JSON still receives `application/json`, `URLSearchParams` receives
`application/x-www-form-urlencoded;charset=UTF-8`, `FormData` receives no manual content type,
`AbortSignal` is forwarded, and a JSON error body is exposed through the shared typed error.

```ts
test('contexto, fila mixta y URL de corte respetan la frontera estricta', () => {
  expect(EsquemaContextoProgramaGeneral.parse({
    success: true,
    data: {
      project: { id: 73, name: 'Da Porto', area: 'Construccion' },
      week: { number: 6, max: 6, confirmed: false },
      actions: {
        editPlanFields: true, editProgress: true, runBatch: true,
        downloadCut: true, readDrawer: true, writeDrawer: true,
      },
      csrf: { programaGeneral: 'a'.repeat(64), drawer: 'b'.repeat(64) },
      restrictionConfig: {
        area: 'Construccion', restrictions: [], hardRestrictions: [], softRestrictions: [],
      },
      links: { bi: '/bi/programa-general' },
    },
  }).data.week.number).toBe(6);

  expect(EsquemaListaProgramaGeneral.parse({ data: [{
    unique_id: '10', Id: '1.2', Semana: '6', Actividad: 'Excavación', Titulo: '0',
    Fecha_Inicio: '2026-08-24', Fecha_Fin: '2026-08-30', Ruta_Critica: '1',
    Ejecutado: '0.25', Ejecutado_Teorico: 0.5, Estado: 'En Curso', Semanas_Inicio: '0',
    Estado_Restricciones: '0.75', codigo_actividad: 'EX-01', unidad: 'm3',
    cantidad_ppto: '100', Sub_Contratista: null, Responsable_AIA: 'Ana', alerta_crisis: null,
    D_y_E: '1', Materiales: '1', MdeO: '0.5', Equipos: '1', Predecesora: '0.5',
    Pdto_Cons: '1', Modelo: 'N/A', restriccion_pc_1: null, restriccion_pc_2: null,
    restriccion_pc_3: null, restriccion_pc_4: null, project_id: 73,
  }] }).data).toHaveLength(1); // only the row adapter may discard passthrough keys

  expect(EsquemaResultadoCorte.safeParse({
    url: '/public/storage/cortesProgramacion/corte.xlsx',
  }).success).toBe(true);
  expect(EsquemaResultadoCorte.safeParse({ url: '//evil.example/corte.xlsx' }).success).toBe(false);
  expect(EsquemaResultadoCorte.safeParse({
    url: '/public/storage/cortesRestricciones/corte.xlsx',
  }).success).toBe(false);
});
```

Gateway tests mock `pedir`, call every function once and assert exact method/path/body/CSRF. In
particular, save must send only the ten canonical form keys and batch/cut must not contain `db`:

```ts
expect([...new URLSearchParams(call.options.body as URLSearchParams).keys()]).toEqual([
  'unique_id', 'Consecutivo_en_Programa', 'Id', 'Ejecutado', 'EjecutadoRatio',
  'codigo_actividad', 'unidad', 'cantidad_ppto', 'Fecha_Inicio', 'Fecha_Fin',
]);
expect(call.path).toBe('/api/general/update?semana=6');
expect(String(batchCall.options.body ?? '')).not.toContain('db');
```

- [ ] **Step 2: Run the focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/lib/api/cliente.test.ts src/lib/api/esquemas/programa-general.test.ts src/lib/api/programa-general.test.ts
```

Expected: FAIL because S05 schema/gateway files do not exist and `pedir()` mislabels form bodies.

- [ ] **Step 3: Implement the schemas, body negotiation and gateway exactly**

Use strict objects everywhere except the audited `SELECT *` row object. The row schema is
`.passthrough()` only so `normalizarPrograma.ts` can discard unknown keys; no other schema is lax.

```ts
const NumeroLegacy = z.union([z.number(), z.string(), z.null()]);
const FechaLegacy = z.union([z.string(), z.null()]);
const TokenCsrf = z.string().regex(/^[a-f0-9]{64}$/);
const RutaInterna = z.string().regex(/^\/(?!\/)[^\u0000-\u001f\u007f\\]+$/);

const Acciones = z.object({
  editPlanFields: z.boolean(), editProgress: z.boolean(), runBatch: z.boolean(),
  downloadCut: z.boolean(), readDrawer: z.boolean(), writeDrawer: z.boolean(),
}).strict();

export const EsquemaContextoProgramaGeneral = z.object({
  success: z.literal(true),
  data: z.object({
    project: z.object({
      id: z.number().int().positive(),
      name: z.string().trim().min(1),
      area: z.enum(['Construccion', 'Pre-Construccion']),
    }).strict(),
    week: z.object({
      number: z.number().int().nonnegative(),
      max: z.number().int().nonnegative(),
      confirmed: z.boolean(),
    }).strict(),
    actions: Acciones,
    csrf: z.object({ programaGeneral: TokenCsrf, drawer: TokenCsrf }).strict(),
    restrictionConfig: EsquemaConfiguracionRestricciones,
    links: z.object({ bi: RutaInterna.nullable() }).strict(),
  }).strict(),
}).strict();

export const EsquemaResultadoCorte = z.object({
  url: z.string().regex(/^\/public\/storage\/cortesProgramacion\/[A-Za-z0-9._-]+\.xlsx$/),
}).strict();
```

In `cliente.ts`, determine content type from the actual body before merging explicit headers:

```ts
const body = opciones.body;
if (body instanceof URLSearchParams) {
  encabezados.set('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
} else if (typeof FormData !== 'undefined' && body instanceof FormData) {
  encabezados.delete('Content-Type');
} else if (body !== undefined && body !== null) {
  encabezados.set('Content-Type', 'application/json');
}
```

Gateways parse request objects before transport, use `encodeURIComponent(String(semana))`, set
`X-CSRF-Token` on every mutation, and never catch-and-retry. `solicitarCorteProgramaGeneral` sends
`new URLSearchParams()` only if the shared client requires a body to mark POST; it sends no scope
field.

- [ ] **Step 4: Re-run focused tests and verify GREEN**

```bash
npm --prefix frontend test -- src/lib/api/cliente.test.ts src/lib/api/esquemas/programa-general.test.ts src/lib/api/programa-general.test.ts
npm --prefix frontend run typecheck
```

Expected: all focused tests pass and TypeScript reports no errors.

- [ ] **Step 5: Commit the contract/client slice (future execution only)**

```bash
git add frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts frontend/src/lib/api/esquemas/programa-general.ts frontend/src/lib/api/esquemas/programa-general.test.ts frontend/src/lib/api/programa-general.ts frontend/src/lib/api/programa-general.test.ts
git commit -m "feat(programa-general): define typed HTTP contracts"
```

---

### Task 3: Normalize legacy rows and present server states/restriction alerts

**Files:**
- Create: `frontend/src/modules/programa-general/dominio/normalizarPrograma.ts`
- Create: `frontend/src/modules/programa-general/dominio/normalizarPrograma.test.ts`
- Create: `frontend/src/modules/programa-general/dominio/presentarEstado.ts`
- Create: `frontend/src/modules/programa-general/dominio/presentarEstado.test.ts`
- Modify: `frontend/src/lib/api/esquemas/programa-general.ts`

**Interfaces:**
- `normalizarFilaPrograma(fila, config): ActividadProgramaGeneral` is the only legacy-to-domain
  adapter and explicitly picks every domain key.
- `presentarEstado(estadoServidor, area)` maps known aliases to a visual key/label/level without
  changing the stored value.
- `clasificarAlertaRestricciones(actividad, config)` returns `null | 'r0' | 'r1' | 'r2-3' | 'r4-6'`.
- `ActividadProgramaGeneral` is inferred from `EsquemaActividadProgramaGeneral`; no parallel
  hand-written interface.

- [ ] **Step 1: Write failing normalization/state tests**

```ts
test('normaliza tipos mixtos sin propagar alcance ni calcular Estado', () => {
  const activity = normalizarFilaPrograma(filaLegacy({
    unique_id: '10', Semana: '6', Titulo: '0', Ruta_Critica: '1',
    Ejecutado: '0.250000', Ejecutado_Teorico: '0.5', Semanas_Inicio: '0',
    unidad: '', cantidad_ppto: '100', project_id: 73, Base_de_Datos: 'no-sale',
  }), configConstruccion);

  expect(activity).toMatchObject({
    uniqueId: 10, week: 6, isChapter: false, criticalPath: true,
    progressRatio: 0.25, theoreticalProgress: 0.5, unit: '%', budgetQuantity: null,
  });
  expect(activity).not.toHaveProperty('project_id');
  expect(activity).not.toHaveProperty('Base_de_Datos');
  expect(activity.state.original).toBe('En Curso');
});

test('capítulo y unidad desconocida permanecen estructurales/legibles', () => {
  expect(normalizarFilaPrograma(filaLegacy({ Titulo: 1 }), configConstruccion).isChapter).toBe(true);
  const custom = normalizarFilaPrograma(filaLegacy({ unidad: 'jornada' }), configConstruccion);
  expect(custom.unit).toBe('jornada');
  expect(custom.unitEditable).toBe(false);
});

test('alerta secundaria exige actividad incompleta, dura bajo umbral y ventana 0..6', () => {
  expect(clasificarAlertaRestricciones(
    actividad({ progressRatio: 0.2, weeksToStart: 0, restrictions: { Materiales: 0.66 } }),
    configConstruccion,
  )).toBe('r0');
  expect(clasificarAlertaRestricciones(
    actividad({ progressRatio: 1, weeksToStart: 0, restrictions: { Materiales: 0 } }),
    configConstruccion,
  )).toBeNull();
});
```

Table-drive every canonical state and legacy alias. Assert Construction and Preconstruction labels
change presentation copy only, and unknown states map to the safe `sin-datos` presentation while
preserving `original`.

- [ ] **Step 2: Run focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/normalizarPrograma.test.ts src/modules/programa-general/dominio/presentarEstado.test.ts
```

Expected: FAIL because the domain adapter and presentation catalog do not exist.

- [ ] **Step 3: Implement explicit normalization and presentation**

Use helpers that reject non-finite/out-of-range ratios rather than clamping them. Dates remain ISO
strings, `''` becomes `null`, empty unit aliases to `%`, `%` always nulls quantity, and unknown unit
text is preserved with `unitEditable=false`.

```ts
export function normalizarFilaPrograma(
  fila: FilaProgramaGeneralLegacy,
  config: ConfiguracionRestricciones,
): ActividadProgramaGeneral {
  const unit = String(fila.unidad ?? '').trim() || '%';
  const isChapter = numero(fila.Titulo) === 1;
  const progressRatio = ratio(fila.Ejecutado);

  return EsquemaActividadProgramaGeneral.parse({
    uniqueId: enteroPositivo(fila.unique_id),
    id: String(fila.Id ?? '').trim(),
    week: enteroPositivo(fila.Semana),
    activity: String(fila.Actividad ?? '').trim(),
    isChapter,
    startDate: fechaIsoONull(fila.Fecha_Inicio),
    endDate: fechaIsoONull(fila.Fecha_Fin),
    criticalPath: numero(fila.Ruta_Critica) === 1,
    progressRatio,
    theoreticalProgress: ratio(fila.Ejecutado_Teorico),
    state: presentarEstado(isChapter ? 'Capítulo' : String(fila.Estado ?? ''), config.area),
    weeksToStart: enteroONull(fila.Semanas_Inicio),
    restrictionRelease: ratio(fila.Estado_Restricciones),
    activityCode: String(fila.codigo_actividad ?? '').trim(),
    unit,
    unitEditable: UNIDADES_EDITABLES.includes(unit as UnidadEditable),
    budgetQuantity: unit === '%' ? null : numeroONull(fila.cantidad_ppto),
    subcontractor: textoONull(fila.Sub_Contratista),
    responsible: textoONull(fila.Responsable_AIA),
    crisisAlert: normalizarCrisis(fila.alerta_crisis),
    phone: textoONull(fila.Telefono ?? fila.telefono_subcontratista),
    email: textoONull(fila.Correo ?? fila.correo_responsable),
    escalationLevel: enteroONull(fila.nivel_actual),
    escalationId: enteroONull(fila.escalamiento_id),
    alertId: enteroONull(fila.alerta_id),
    restrictions: extraerRestricciones(fila, config),
  });
}
```

The state catalog reads semantic keys/levels from the checked-in state contract; do not duplicate
colors. Alert classification uses only `hardRestrictions`, ignores `null/'N/A'`, compares ratios to
percentage thresholds converted once, and never replaces the main state.

- [ ] **Step 4: Verify domain tests GREEN**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/normalizarPrograma.test.ts src/modules/programa-general/dominio/presentarEstado.test.ts
npm --prefix frontend run typecheck
```

Expected: tests and typecheck pass; no imported legacy JS or state calculator exists.

- [ ] **Step 5: Commit the normalization slice (future execution only)**

```bash
git add frontend/src/lib/api/esquemas/programa-general.ts frontend/src/modules/programa-general/dominio/normalizarPrograma.ts frontend/src/modules/programa-general/dominio/normalizarPrograma.test.ts frontend/src/modules/programa-general/dominio/presentarEstado.ts frontend/src/modules/programa-general/dominio/presentarEstado.test.ts
git commit -m "feat(programa-general): normalize rows and state presentation"
```

---

### Task 4: Implement search, structured filters, facet counts and URL state

**Files:**
- Create: `frontend/src/modules/programa-general/dominio/filtrarPrograma.ts`
- Create: `frontend/src/modules/programa-general/dominio/filtrarPrograma.test.ts`
- Modify: `frontend/src/lib/api/esquemas/programa-general.ts`

**Interfaces:**
- `leerFiltrosPrograma(searchParams)` and `escribirFiltrosPrograma(filtros)` own the query codec.
- `derivarVistaPrograma(filas, filtros)` returns `{ visibles, conteos, totalActividades }` using the
  single ordered pipeline from the spec.
- `FiltrosProgramaGeneral` is inferred from a Zod schema and never contains an unvalidated column
  name/value.

- [ ] **Step 1: Write failing pipeline/query tests**

```ts
test('conteos se calculan antes de chips y excluyen capítulos', () => {
  const rows = [
    actividad({ uniqueId: 1, state: estado('Atrasada') }),
    actividad({ uniqueId: 2, state: estado('En Curso') }),
    actividad({ uniqueId: 3, isChapter: true, state: estado('Capítulo') }),
  ];
  const view = derivarVistaPrograma(rows, filtros({ states: ['atrasada'] }));
  expect(view.visibles.map(({ uniqueId }) => uniqueId)).toEqual([1]);
  expect(view.conteos.atrasada).toBe(1);
  expect(view.conteos['en-curso']).toBe(1);
  expect(view.totalActividades).toBe(2);
});

test('búsqueda ignora caja/acentos y cubre cinco campos', () => {
  const row = actividad({
    activityCode: 'EX-01', activity: 'Excavación', responsible: 'Ángela',
    subcontractor: 'Gómez SAS', state: estado('En Curso'),
  });
  for (const q of ['ex-01', 'EXCAVACION', 'angela', 'gomez', 'en curso']) {
    expect(derivarVistaPrograma([row], filtros({ query: q })).visibles).toHaveLength(1);
  }
});

test('query codec conserva repetidos y limpia valores inválidos', () => {
  const parsed = leerFiltrosPrograma(new URLSearchParams(
    'semana=6&q=exc&estado=atrasada&estado=en-curso&critica=si&col.noExiste=x',
  ));
  expect(parsed).toMatchObject({ week: 6, query: 'exc', states: ['atrasada', 'en-curso'] });
  expect(escribirFiltrosPrograma(parsed).getAll('estado')).toEqual(['atrasada', 'en-curso']);
  expect(escribirFiltrosPrograma(parsed).has('col.noExiste')).toBe(false);
});
```

Add ranges/equality tests for Id, code, activity, start/end, weeks, critical, unit, quantity,
theoretical/real/restriction release; add Ctrl/Cmd chip toggle and clear semantics as pure reducer
tests.

- [ ] **Step 2: Run the focused test and confirm RED**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/filtrarPrograma.test.ts
```

Expected: FAIL because the filter domain does not exist.

- [ ] **Step 3: Implement the one ordered derivation pipeline**

```ts
export function derivarVistaPrograma(
  filas: readonly ActividadProgramaGeneral[],
  filtros: FiltrosProgramaGeneral,
): VistaProgramaGeneral {
  const afterSearch = filas.filter((row) => coincideBusqueda(row, filtros.query));
  const afterColumns = afterSearch.filter((row) => coincideColumnas(row, filtros.columns));
  const activities = afterColumns.filter((row) => !row.isChapter);
  const counts = contarFacetas(activities);
  const visibles = aplicarChipsConCapitulos(afterColumns, filtros.states);

  return { visibles, conteos: counts, totalActividades: activities.length };
}
```

Memoizable search keys are created once per normalized row using `normalize('NFD')` and combining
marks removal. Preserve source order. Chapters remain when they structurally precede at least one
visible activity; never include them in counts. URL keys are exactly `semana`, `q`, repeated
`estado`, `semInicio`, `critica`, and whitelisted `col.<nombre>`.

- [ ] **Step 4: Verify filter/query tests GREEN**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/filtrarPrograma.test.ts
npm --prefix frontend run typecheck
```

Expected: all cases pass with stable ordering and no HTTP calls.

- [ ] **Step 5: Commit the filter slice (future execution only)**

```bash
git add frontend/src/lib/api/esquemas/programa-general.ts frontend/src/modules/programa-general/dominio/filtrarPrograma.ts frontend/src/modules/programa-general/dominio/filtrarPrograma.test.ts
git commit -m "feat(programa-general): add filters counts and URL state"
```

---

### Task 5: Deliver the read-only React nucleus across desktop, tablet and mobile

**Files:**
- Create: `frontend/src/lib/ui/useMediaQuery.ts`
- Create: `frontend/src/lib/ui/useMediaQuery.test.tsx`
- Create: `frontend/src/modules/programa-general/useProgramaGeneral.ts`
- Create: `frontend/src/modules/programa-general/useProgramaGeneral.test.tsx`
- Create: `frontend/src/modules/programa-general/ProgramaGeneralPage.tsx`
- Create: `frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaFilters.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaTable.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaCards.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaLegend.tsx`
- Create focused component tests beside each file.
- Create: `frontend/src/modules/programa-general/programa-general.css`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `frontend/src/shell/navegacion/BarraLateral.tsx`
- Modify: `frontend/src/shell/navegacion/BarraLateral.test.tsx`

**Interfaces:**
- `useProgramaGeneral({sesion, searchParams})` owns read requests/cancellation only in this task.
- `ProgramaGeneralPage` chooses one responsive renderer and composes toolbar/filters/legend/states.
- `ProgramaTable` renders 13 columns at desktop and 8 + details at tablet.
- `ProgramaCards` renders activities and noneditable chapter separators below 768 px.

- [ ] **Step 1: Write failing hook/page/renderer tests**

Mock gateways, never `fetch`. Cover context+list+codes success, partial codes failure, aborted stale
week, project-id mismatch, initial loading, background reload, no weeks, empty rows, chapters only,
no results, 401, 403, list error and retry.

```tsx
test('desktop/tablet use table and mobile uses editable-card structure only', async () => {
  mockProgramaSuccess();
  setViewport(1180);
  const desktop = render(<ProgramaGeneralPage />);
  expect(await desktop.findByRole('table', { name: /programa general/i })).toBeVisible();
  expect(desktop.queryByTestId('programa-cards')).not.toBeInTheDocument();

  desktop.unmount();
  setViewport(390);
  const mobile = render(<ProgramaGeneralPage />);
  expect(await mobile.findByTestId('programa-cards')).toBeVisible();
  expect(mobile.queryByRole('table', { name: /programa general/i })).not.toBeInTheDocument();
  expect(mobile.getByRole('heading', { name: /capítulo cimentación/i })).toBeVisible();
});

test('lector recibe filas y ninguna acción de mutación', async () => {
  mockProgramaSuccess({
    actions: { editPlanFields: false, editProgress: false, runBatch: false },
  });
  render(<ProgramaGeneralPage />);
  expect(await screen.findByText('Excavación')).toBeVisible();
  expect(screen.queryByRole('button', { name: /actualizar ejecución/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('textbox', { name: /avance real/i })).not.toBeInTheDocument();
});
```

Assert the table caption/headers, tablet `aria-expanded`, eight chips with `aria-pressed`, facet
counts, Construction/PC legend copy, BI link only from `links.bi`, and that the sidebar active item
comes from T01 navigation rather than role lists.

- [ ] **Step 2: Run focused UI tests and confirm RED**

```bash
npm --prefix frontend test -- src/lib/ui/useMediaQuery.test.tsx src/modules/programa-general/useProgramaGeneral.test.tsx src/modules/programa-general/ProgramaGeneralPage.test.tsx src/modules/programa-general/componentes
```

Expected: FAIL because the S05 UI/read orchestration does not exist.

- [ ] **Step 3: Implement the read nucleus and pilot route**

The hook loads context first, verifies `context.project.id === sesion.project.id`, then requests list
and codes in parallel with one `AbortController`. A codes failure is stored as a partial error; a
list/context failure drives the page state. On week/project change, abort, clear drawer/drafts/data,
and never write an old response.

`ProgramaGeneralPage` uses `useMediaQuery('(max-width: 767px)')`; tablet is CSS-driven inside the
table from 768–1179. Render exactly one of table/cards. Use `<caption>`, `<th scope="col">`,
`<th scope="row">` where appropriate, buttons for chip/row details, and live regions with deduped
messages.

Register `/app/programa-general` first. The canonical `/programa-general` remains VIEW-34 in this
task. `BarraLateral` receives no local PG menu definition; it only marks the T01 item active from
the current URL.

The CSS root is `.pg-react`; import tokens, use grid/minmax/container queries only when supported by
the existing build, and style state via semantic data attributes mapped to design-system tokens.

- [ ] **Step 4: Verify the complete read nucleus GREEN**

```bash
npm --prefix frontend test -- src/lib/ui/useMediaQuery.test.tsx src/modules/programa-general
npm --prefix frontend run typecheck
npm --prefix frontend run build
```

Expected: tests/typecheck/build pass; the bundle contains no Handsontable/jQuery/Bootstrap import.

- [ ] **Step 5: Commit the read nucleus (future execution only)**

```bash
git add frontend/src/lib/ui/useMediaQuery.ts frontend/src/lib/ui/useMediaQuery.test.tsx frontend/src/modules/programa-general frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/navegacion/BarraLateral.tsx frontend/src/shell/navegacion/BarraLateral.test.tsx public/app
git commit -m "feat(programa-general): deliver responsive read nucleus"
```

---

### Task 6: Harden the PHP save preflight without executing a write

**Files:**
- Create: `src/Services/ProgramaGeneralInputValidator.php`
- Create: `tests/test_programa_general_input_validation.php`
- Modify: `src/Controllers/Api/GeneralApiController.php`
- Modify: `tests/test_programa_general_sprint_contract.mjs`

**Interfaces:**
- `ProgramaGeneralInputValidator::validate(array $input): array` returns normalized dates, code,
  unit, nullable quantity, visible progress and canonical ratio, or throws `InvalidArgumentException`.
- `ProgramaGeneralInputValidator::planningChanged(array $persisted, array $normalized): bool`
  compares normalized code/dates/unit/quantity.
- `GeneralApiController::update()` performs every S05 preflight before the first DML/service side
  effect and maps validation `422`, confirmed planning `409`, forbidden week `403`, missing row `404`.

- [ ] **Step 1: Write the failing pure validator/policy regression**

```php
$valid = ProgramaGeneralInputValidator::validate([
    'unique_id' => '10',
    'Consecutivo_en_Programa' => '10',
    'Id' => '1.2',
    'Ejecutado' => '25.00',
    'EjecutadoRatio' => '0.250000',
    'codigo_actividad' => 'EX-01',
    'unidad' => 'm3',
    'cantidad_ppto' => '100.04',
    'Fecha_Inicio' => '2026-08-24',
    'Fecha_Fin' => '2026-08-30',
]);
assertSame(100.0, $valid['cantidad_ppto']);
assertSame(0.25, $valid['ejecutado_ratio']);

assertThrows(fn() => ProgramaGeneralInputValidator::validate($base + [
    'Fecha_Inicio' => '2026-02-30',
]), 'Fecha_Inicio');
assertThrows(fn() => ProgramaGeneralInputValidator::validate($base + [
    'Fecha_Inicio' => '2026-08-31', 'Fecha_Fin' => '2026-08-30',
]), 'Fecha_Inicio');
assertThrows(fn() => ProgramaGeneralInputValidator::validate($base + [
    'cantidad_ppto' => '-1',
]), 'cantidad_ppto');
assertThrows(fn() => ProgramaGeneralInputValidator::validate($base + [
    'EjecutadoRatio' => '1.000101',
]), 'EjecutadoRatio');

$percent = ProgramaGeneralInputValidator::validate($base + [
    'unidad' => '%', 'cantidad_ppto' => '100', 'Ejecutado' => '25', 'EjecutadoRatio' => '0.25',
]);
assertSame(null, $percent['cantidad_ppto']);
```

Add exact real-date checks (leap/non-leap), missing dates, negative progress, physical progress above
quantity, zero quantity to null, six-decimal ratio, allowed unit set, matching/mismatching IDs, and
`planningChanged()` equality after normalization.

Extend the static sprint contract to assert that `disableProductivityMeasurementTemporarily()` and
the first `UPDATE` occur after row/week/policy/input/confirmation preflight, and that
`semana_objetivo` keeps the S06 compatibility branch.

- [ ] **Step 2: Run safe focused tests and confirm RED**

```bash
docker compose exec -T app php tests/test_programa_general_input_validation.php
node tests/test_programa_general_sprint_contract.mjs
```

Expected: validator test fails because the class does not exist; static contract fails until the
write order/compatibility branch is explicit. Neither command executes DML.

- [ ] **Step 3: Implement strict validation and reorder the controller preflight**

Validate real ISO dates with `DateTimeImmutable::createFromFormat('!Y-m-d', $value)` plus
`DateTimeImmutable::getLastErrors()`, then enforce start <= end. Allowed editable units are exactly
`ml`, `m2`, `m3`, `un`, `gl`, `kg`, `%`, `Niveles`; empty aliases to `%`.

For S05 requests (`semana` present and `semana_objetivo` absent), the controller order is:

```text
auth → capability → CSRF → active ProjectScope/prefix match → active week/row snapshot
→ max/confirmed snapshot → ProgramaGeneralActionPolicy
→ ProgramaGeneralInputValidator → confirmed planning comparison
→ disable productivity/write/audit/state/normalization
```

Load the persisted row with `Titulo`, code, dates, unit, quantity and progress. Deny chapters. If
`editProgress=false`, return `403`. If planning differs and `editPlanFields=false`, return `409` with
`PG_WEEK_CONFIRMED`. Ignore client copies of planning when only progress is allowed and write the
persisted normalized values. Never trust a client confirmation flag.

Keep the existing `semana_objetivo` branch and its S06 payload semantics unchanged. Do not remove
audit, manual-edit signature, inheritance compatibility, state calculation or chapter normalization.

- [ ] **Step 4: Verify validation/wiring GREEN without DML**

```bash
docker compose exec -T app php tests/test_programa_general_input_validation.php
node tests/test_programa_general_sprint_contract.mjs
docker compose exec -T app php -l src/Services/ProgramaGeneralInputValidator.php
docker compose exec -T app php -l src/Controllers/Api/GeneralApiController.php
```

Expected: all commands exit `0`; no controller mutation test is run.

- [ ] **Step 5: Commit the safe write preflight (future execution only)**

```bash
git add src/Services/ProgramaGeneralInputValidator.php src/Controllers/Api/GeneralApiController.php tests/test_programa_general_input_validation.php tests/test_programa_general_sprint_contract.mjs
git commit -m "fix(programa-general): enforce server save preflight"
```

---

### Task 7: Add identical individual editing to table and mobile cards

**Files:**
- Create: `frontend/src/modules/programa-general/dominio/validarActividad.ts`
- Create: `frontend/src/modules/programa-general/dominio/validarActividad.test.ts`
- Create: `frontend/src/modules/programa-general/componentes/EditorActividad.tsx`
- Create: `frontend/src/modules/programa-general/componentes/EditorActividad.test.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaTable.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaTable.test.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaCards.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaCards.test.tsx`
- Modify: `frontend/src/modules/programa-general/useProgramaGeneral.ts`
- Modify: `frontend/src/modules/programa-general/useProgramaGeneral.test.tsx`
- Modify: `frontend/src/modules/programa-general/programa-general.css`

**Interfaces:**
- `validarBorradorActividad(activity, draft): ResultadoValidacion` mirrors Task 6 and returns the ten
  canonical string fields only on success.
- `EditorActividad` receives field-level action flags, draft, codes, status and callbacks; it never
  imports the API gateway.
- `useProgramaGeneral.guardar(uniqueId, draft)` serializes one mutation per row and reconciles the
  exact server response.

- [ ] **Step 1: Write failing domain/editor parity tests**

```ts
test('tabla y tarjeta producen el mismo formulario canónico', async () => {
  const activity = actividadEditable();
  const expected = validarBorradorActividad(activity, {
    startDate: '2026-08-24', endDate: '2026-08-30', unit: 'm3',
    budgetQuantity: '100', progressDisplay: '25', activityCode: 'EX-01',
  });
  expect(expected.ok).toBe(true);

  const fromTable = await editAndSubmit(<ProgramaTable rows={[activity]} />);
  const fromCard = await editAndSubmit(<ProgramaCards rows={[activity]} />);
  expect(fromTable).toEqual(expected.form);
  expect(fromCard).toEqual(expected.form);
});

test('confirmada permite avance pero no planificación', () => {
  render(<EditorActividad activity={actividadEditable()} actions={{
    editPlanFields: false, editProgress: true,
  }} />);
  expect(screen.getByRole('textbox', { name: /avance real/i })).toBeEnabled();
  expect(screen.queryByRole('textbox', { name: /fecha inicio/i })).not.toBeInTheDocument();
});
```

Add tests for chapter/reader/past locks, invalid real/inverted dates, negative quantity/progress,
physical and percent max, code failure, unknown unit read-only, Enter/blur/select deduplication,
percentage conversion confirmation/cancel, one in-flight mutation per row, other rows editable,
server reconciliation and retry/discard after network/422/409.

- [ ] **Step 2: Run focused editing tests and confirm RED**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/validarActividad.test.ts src/modules/programa-general/componentes/EditorActividad.test.tsx src/modules/programa-general/componentes/ProgramaTable.test.tsx src/modules/programa-general/componentes/ProgramaCards.test.tsx src/modules/programa-general/useProgramaGeneral.test.tsx
```

Expected: FAIL because validation/editor/save orchestration is absent.

- [ ] **Step 3: Implement one editor and one canonical form builder**

`validarBorradorActividad` uses strings to avoid locale/timezone drift, validates with Zod plus an
actual calendar check, rounds quantity to one decimal and ratio to six. For physical units,
`ratio=progressDisplay/budgetQuantity`; for `%` or no quantity, `ratio=progressDisplay/100`.

```ts
return {
  ok: true,
  form: {
    unique_id: String(activity.uniqueId),
    Consecutivo_en_Programa: String(activity.uniqueId),
    Id: activity.id,
    Ejecutado: formatDecimal(progressDisplay, 2),
    EjecutadoRatio: ratio.toFixed(6),
    codigo_actividad: draft.activityCode.trim(),
    unidad: normalizedUnit,
    cantidad_ppto: normalizedUnit === '%' ? '' : formatDecimal(quantity, 1),
    Fecha_Inicio: draft.startDate,
    Fecha_Fin: draft.endDate,
  },
};
```

Table/card delegate to `EditorActividad`; they do not duplicate validation. Use text/select/date/
decimal inputs with associated labels/errors. Converting physical unit to `%` opens the shared
accessible confirmation primitive; accept preserves ratio/nulls quantity, cancel restores both.

The hook stores draft/status by `uniqueId`, suppresses same-value commits and additional submits for
the same row, calls the gateway once, then replaces only fields returned by PHP. Do not optimistically
change state/weeks. Keep failed drafts until retry/discard. Expose a dirty-draft guard to T01/S04
week/project navigation.

- [ ] **Step 4: Verify editor parity GREEN**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/validarActividad.test.ts src/modules/programa-general/componentes/EditorActividad.test.tsx src/modules/programa-general/componentes/ProgramaTable.test.tsx src/modules/programa-general/componentes/ProgramaCards.test.tsx src/modules/programa-general/useProgramaGeneral.test.tsx
npm --prefix frontend run typecheck
```

Expected: all tests/typecheck pass with identical table/card form objects.

- [ ] **Step 5: Commit individual editing (future execution only)**

```bash
git add frontend/src/modules/programa-general/dominio/validarActividad.ts frontend/src/modules/programa-general/dominio/validarActividad.test.ts frontend/src/modules/programa-general/componentes/EditorActividad.tsx frontend/src/modules/programa-general/componentes/EditorActividad.test.tsx frontend/src/modules/programa-general/componentes/ProgramaTable.tsx frontend/src/modules/programa-general/componentes/ProgramaTable.test.tsx frontend/src/modules/programa-general/componentes/ProgramaCards.tsx frontend/src/modules/programa-general/componentes/ProgramaCards.test.tsx frontend/src/modules/programa-general/useProgramaGeneral.ts frontend/src/modules/programa-general/useProgramaGeneral.test.tsx frontend/src/modules/programa-general/programa-general.css
git commit -m "feat(programa-general): add individual activity editing"
```

---

### Task 8: Add batch, return-once and resilient reload orchestration

**Files:**
- Create: `frontend/src/modules/programa-general/dominio/retornoLote.ts`
- Create: `frontend/src/modules/programa-general/dominio/retornoLote.test.ts`
- Modify: `frontend/src/modules/programa-general/useProgramaGeneral.ts`
- Modify: `frontend/src/modules/programa-general/useProgramaGeneral.test.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx`
- Create/Modify: `frontend/src/modules/programa-general/componentes/ProgramaToolbar.test.tsx`
- Modify: `frontend/src/modules/programa-general/ProgramaGeneralPage.tsx`
- Modify: `frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx`
- Modify: `src/Controllers/Api/GeneralApiController.php`
- Modify: `tests/test_programa_general_sprint_contract.mjs`

**Interfaces:**
- `scopeRetorno(projectId, week)` yields a versioned storage key.
- `marcarSalidaPrograma`, `consumirRetornoPrograma` and `limpiarRetornoPrograma` form a pure
  storage adapter; project/week and navigation type are mandatory.
- `useProgramaGeneral.actualizarEjecucion(origen)` accepts `manual | retorno`, has one in-flight
  batch, reconciles context/list and never retries.

- [ ] **Step 1: Write failing batch/return/reload tests**

```ts
test('primera entrada y reload no ejecutan lote; retorno lo consume una vez', () => {
  const storage = memoriaSessionStorage();
  expect(consumirRetornoPrograma(storage, { projectId: 73, week: 6, navigationType: 'navigate' }))
    .toBe(false);
  marcarSalidaPrograma(storage, { projectId: 73, week: 6 });
  expect(consumirRetornoPrograma(storage, { projectId: 73, week: 6, navigationType: 'reload' }))
    .toBe(false);
  marcarSalidaPrograma(storage, { projectId: 73, week: 6 });
  expect(consumirRetornoPrograma(storage, { projectId: 73, week: 6, navigationType: 'back_forward' }))
    .toBe(true);
  expect(consumirRetornoPrograma(storage, { projectId: 73, week: 6, navigationType: 'back_forward' }))
    .toBe(false);
});
```

Hook/page tests assert manual confirmation with dirty drafts, one POST, mutation controls disabled but
filters readable, both counts announced, context+list reload, filter/scroll preservation, reader no
button/request, batch failure no loop/data loss, reload aborts previous GET and retains stale data
with warning. Add project/week isolation and `sessionStorage` parse corruption cleanup.

- [ ] **Step 2: Run focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/retornoLote.test.ts src/modules/programa-general/useProgramaGeneral.test.tsx src/modules/programa-general/componentes/ProgramaToolbar.test.tsx src/modules/programa-general/ProgramaGeneralPage.test.tsx
node tests/test_programa_general_sprint_contract.mjs
```

Expected: RED because return/batch orchestration and the cleaned server contract are absent.

- [ ] **Step 3: Implement return-once and safe batch behavior**

Use key `aia:pg:return:v1:<projectId>:<week>` with JSON `{left:true}`. On full-document navigation,
the T01 navigation callback marks before `location.assign`; on SPA transition, the route coordinator
marks before leaving. A `PerformanceNavigationTiming.type === 'reload'` clears without executing.
Changing project/week clears the prior scope. Unauthorized context clears and never calls batch.

Manual/return batch calls `/api/general/update-batch?semana=n` once, with CSRF and no legacy body.
After success or recoverable failure, issue fresh context/list; retain previous rows until new data
arrives and keep query filters/scroll when row anchors still exist. Consume the return marker before
the call so failure cannot loop.

In PHP, derive prefix/project/week from session/scope, reject an explicitly supplied mismatching
`db`, reuse `ProgramaGeneralActionPolicy`, ignore `opcion/Id1/Ejecutado/inicio_semana`, and leave
carryover/state services unchanged. Static tests assert no body field governs SQL and preflight
precedes the service call; do not execute it.

- [ ] **Step 4: Verify orchestration GREEN without DML**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/retornoLote.test.ts src/modules/programa-general/useProgramaGeneral.test.tsx src/modules/programa-general/componentes/ProgramaToolbar.test.tsx src/modules/programa-general/ProgramaGeneralPage.test.tsx
node tests/test_programa_general_sprint_contract.mjs
npm --prefix frontend run typecheck
```

Expected: all commands pass; no operational POST reaches Docker.

- [ ] **Step 5: Commit batch/reload behavior (future execution only)**

```bash
git add frontend/src/modules/programa-general/dominio/retornoLote.ts frontend/src/modules/programa-general/dominio/retornoLote.test.ts frontend/src/modules/programa-general/useProgramaGeneral.ts frontend/src/modules/programa-general/useProgramaGeneral.test.tsx frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx frontend/src/modules/programa-general/componentes/ProgramaToolbar.test.tsx frontend/src/modules/programa-general/ProgramaGeneralPage.tsx frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx src/Controllers/Api/GeneralApiController.php tests/test_programa_general_sprint_contract.mjs
git commit -m "feat(programa-general): add batch return and reload flows"
```

---

### Task 9: Add visible-set CSV and scoped cut download

**Files:**
- Create: `frontend/src/modules/programa-general/dominio/exportarCsv.ts`
- Create: `frontend/src/modules/programa-general/dominio/exportarCsv.test.ts`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx`
- Modify: `frontend/src/modules/programa-general/componentes/ProgramaToolbar.test.tsx`
- Create: `src/Services/CorteProgramaGeneralService.php`
- Modify: `src/Controllers/Gestion/ReportController.php`
- Create: `tests/test_programa_general_cut_contract.php`
- Modify: `public/index.php` only if the generic report route needs explicit method metadata; do not
  add a duplicate report endpoint.

**Interfaces:**
- `crearCsvPrograma(filasVisibles): string` returns BOM + RFC 4180 text with fixed 13-column order.
- `descargarCsvPrograma(filasVisibles, document)` owns Blob/object URL/click/revoke as an injectable
  browser adapter.
- `CorteProgramaGeneralService::generate(int $projectId, string $dbPrefix, int $week,
  string $projectName): string` returns the relative `.xlsx` URL.
- `ReportController::downloadCorteProgramacion()` accepts no client scope, validates CSRF and delegates
  once to the injectable service.

- [ ] **Step 1: Write failing CSV and fake-cut contracts**

```ts
test('CSV exports the visible rows, chapters and exact header order', () => {
  const csv = crearCsvPrograma([
    actividad({ id: '1', activity: 'Capítulo, estructura', isChapter: true }),
    actividad({ id: '1.1', activity: 'Excavación\nmanual', progressRatio: 0.25 }),
  ]);
  expect(csv.charCodeAt(0)).toBe(0xfeff);
  expect(csv.split('\r\n')[0]).toBe(
    'Id,Código,Actividad,Sem. inicio,Fecha inicio,Fecha fin,Crítica,Unidad,Cantidad PPTO,Ejecución teórica,Ejecución real,Estado,Lib. restricciones',
  );
  expect(csv).toContain('"Capítulo, estructura"');
  expect(csv).toContain('"Excavación\nmanual"');
});
```

The PHP contract injects a fake cut service and deterministic active scope/session/token. Assert:

- missing/invalid CSRF returns `403` before generator;
- missing report permission returns `403`;
- request body/query `db`, `project_id`, `semana` cannot change the service arguments;
- valid request calls the fake once with active scope + session week and returns exactly `{url}`;
- unsafe/cross-directory/non-XLSX service URL becomes a safe `500` domain error;
- the test creates no directory/file and executes no DB query/write.

- [ ] **Step 2: Run focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/exportarCsv.test.ts src/modules/programa-general/componentes/ProgramaToolbar.test.tsx
docker compose exec -T app php tests/test_programa_general_cut_contract.php
```

Expected: RED because CSV/cut service and protected delegation do not exist.

- [ ] **Step 3: Implement CSV and extract the existing workbook generator**

CSV cell escaping is deterministic:

```ts
function csvCell(value: unknown): string {
  const text = value === null || value === undefined ? '' : String(value);
  return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}
export function crearCsvPrograma(rows: readonly ActividadProgramaGeneral[]): string {
  return '\ufeff' + [CSV_HEADERS, ...rows.map(toCsvRow)]
    .map((row) => row.map(csvCell).join(','))
    .join('\r\n');
}
```

Move the existing `Corte Programacion`/`ASSEMBLE` workbook body intact into
`CorteProgramaGeneralService`; preserve headers, date formats, state fills, styles, filename and
storage directory. The controller resolves `ProjectScope`, validated session prefix/week/name,
checks `lps.reportes.generar` and `programa_general_save` CSRF, delegates and whitelists the returned
path. It ignores/rejects client scope fields; React POST sends none.

Toolbar shows CSV whenever rows are loaded and cut only when `downloadCut=true`. Both expose busy/
error text; CSV uses current `visibles`, not all source rows. Cut calls the typed gateway then
`location.assign(validatedUrl)`; failure leaves rows/filter/selection unchanged.

- [ ] **Step 4: Verify CSV/cut contracts GREEN**

```bash
npm --prefix frontend test -- src/modules/programa-general/dominio/exportarCsv.test.ts src/modules/programa-general/componentes/ProgramaToolbar.test.tsx
docker compose exec -T app php tests/test_programa_general_cut_contract.php
docker compose exec -T app php -l src/Services/CorteProgramaGeneralService.php
docker compose exec -T app php -l src/Controllers/Gestion/ReportController.php
```

Expected: all commands exit `0`; no workbook is written during tests.

- [ ] **Step 5: Commit exports (future execution only)**

```bash
git add frontend/src/modules/programa-general/dominio/exportarCsv.ts frontend/src/modules/programa-general/dominio/exportarCsv.test.ts frontend/src/modules/programa-general/componentes/ProgramaToolbar.tsx frontend/src/modules/programa-general/componentes/ProgramaToolbar.test.tsx src/Services/CorteProgramaGeneralService.php src/Controllers/Gestion/ReportController.php tests/test_programa_general_cut_contract.php public/index.php
git commit -m "feat(programa-general): add CSV and scoped cut export"
```

---

### Task 10: Migrate the contextual drawer and all PG actions

**Files:**
- Create: `src/Services/LpsContextResolver.php`
- Modify: `src/Controllers/Api/LpsApiController.php`
- Create: `tests/test_lps_programa_general_scope.php`
- Modify: `frontend/src/lib/api/esquemas/programa-general.ts`
- Modify: `frontend/src/lib/api/esquemas/programa-general.test.ts`
- Modify: `frontend/src/lib/api/programa-general.ts`
- Modify: `frontend/src/lib/api/programa-general.test.ts`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaDrawer.tsx`
- Create: `frontend/src/modules/programa-general/componentes/ProgramaDrawer.test.tsx`
- Create: `frontend/src/modules/programa-general/dominio/resumenDrawer.ts`
- Create: `frontend/src/modules/programa-general/dominio/resumenDrawer.test.ts`
- Modify: `frontend/src/modules/programa-general/ProgramaGeneralPage.tsx`
- Modify: `frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx`
- Modify: `frontend/src/modules/programa-general/programa-general.css`

**Interfaces:**
- `LpsContextResolver::resolve(array $session): array` returns active `dbPrefix`, `week`, `userId`,
  `projectId` from `ProjectScope`; it never queries by project name/`Area='Construccion'`.
- `ProgramaDrawer` consumes one selected normalized activity, restriction config, complete dataset,
  `readDrawer/writeDrawer`, CSRF and gateway callbacks.
- `crearMensajeSos`, `crearResumenSemanal` and `siguienteTriggerSos` are pure local functions.
- The gateway uses only canonical `/comments/add`, `/crisis/register`, `/crisis/close` mutation routes.

- [ ] **Step 1: Write failing scope, schema, domain and drawer tests**

The pure PHP test uses fake data scope/query/service and runs both area labels. Assert the resolver
returns `ProjectScope::projectId()` for Construction and Preconstruction, rejects missing/mismatched
scope/session/week/user, never runs an `Area='Construccion'` query, and never calls a write method.
Validate request guards for positive consecutive/alert IDs, nonempty comment, `menciones` JSON shape,
`modulo=PG`, trigger in `MANUAL|SOS-RES|SOS-DIR|SOS-COO|SOS-GER`, and 100-char closure.

```tsx
test('drawer loads recursive comments and preserves focus contract', async () => {
  mockComments([{ id: 1, comentario: 'Bloqueo', respuestas: [replyFixture] }]);
  const trigger = renderPageAndSelectActivity();
  await user.click(trigger);
  expect(await screen.findByText('Bloqueo')).toBeVisible();
  expect(screen.getByText(replyFixture.comentario)).toBeVisible();
  expect(screen.getByRole('dialog', { name: /actividad excavación/i })
    .contains(document.activeElement)).toBe(true);
  await user.keyboard('{Escape}');
  expect(trigger).toHaveFocus();
});

test('SOS registra antes de WhatsApp y simulación sólo copia', async () => {
  const { sos, register, clipboard, open } = renderDrawer({ phone: '+57 300 123 4567' });
  await user.click(sos.whatsapp);
  expect(register).toHaveBeenCalledWith(expect.objectContaining({ modulo: 'PG', trigger: 'SOS-DIR' }));
  expect(open).toHaveBeenCalledWith(expect.stringMatching(/^https:\/\/api\.whatsapp\.com\/send\?/));

  setSimulatedMode(true);
  await user.click(sos.whatsapp);
  expect(register).toHaveBeenCalledTimes(1);
  expect(clipboard.writeText).toHaveBeenCalled();
});
```

Also cover reader drawer, writer comment/reply/mentions, empty/error/retry, selected row hidden by
filter, dataset/week/project clearing, chapter no actions, manual crisis, email, missing contact
clipboard fallback, close 99/100 chars, background inert, Tab trap, overlay/push modes, recursive
schema extra-key rejection/XSS-as-text, weekly digest grouping/copy and aborting stale comments.

- [ ] **Step 2: Run safe focused tests and confirm RED**

```bash
docker compose exec -T app php tests/test_lps_programa_general_scope.php
npm --prefix frontend test -- src/lib/api/esquemas/programa-general.test.ts src/lib/api/programa-general.test.ts src/modules/programa-general/dominio/resumenDrawer.test.ts src/modules/programa-general/componentes/ProgramaDrawer.test.tsx src/modules/programa-general/ProgramaGeneralPage.test.tsx
```

Expected: RED because scope resolver/drawer/domain actions do not exist.

- [ ] **Step 3: Implement active-scope PHP resolution and the React drawer**

`LpsContextResolver` requires a current `ProjectScope`, positive session week, matching session
prefix through `ModuleRequestContext`, and authenticated username resolved to user ID. It uses the
scope project ID directly, so area never appears in its project lookup. Inject resolver and
`LpsService` into `LpsApiController` with production defaults; preserve existing RBAC keys/CSRF.

Parse mentions as `{roles: string[]}` with unique uppercase role tokens and a bounded count/length.
Return safe generic server failures rather than raw SQL/stack text. Continue returning legacy
`respuesta` forms required by S07/S08. Do not remove aliases in this task.

The React drawer:

- loads comments only with `readDrawer=true` and cancels when selection changes;
- renders comments/replies as React text, never `dangerouslySetInnerHTML`;
- posts root/reply/menciones only with `writeDrawer=true` and refreshes once;
- derives diagnostics from the normalized activity + shared restriction config;
- registers manual crisis with `MANUAL` and SOS with the observed next-level trigger;
- if `lps_simulated_mode=true`, copies only; otherwise registers first, then opens same-origin-safe
  WhatsApp/mail URI or copies with an explicit missing-contact message;
- builds/copies the weekly digest locally from the already authorized dataset;
- closes only with a known alert ID and 100+ trimmed characters;
- uses the shared T01 drawer primitive for dialog semantics, inert background, Escape, trap, overlay
  below desktop, content push at desktop and focus return.

- [ ] **Step 4: Verify drawer contracts GREEN without writes**

```bash
docker compose exec -T app php tests/test_lps_programa_general_scope.php
npm --prefix frontend test -- src/lib/api/esquemas/programa-general.test.ts src/lib/api/programa-general.test.ts src/modules/programa-general/dominio/resumenDrawer.test.ts src/modules/programa-general/componentes/ProgramaDrawer.test.tsx src/modules/programa-general/ProgramaGeneralPage.test.tsx
npm --prefix frontend run typecheck
```

Expected: all commands pass; fake counters prove no LPS service write occurred in PHP tests.

- [ ] **Step 5: Commit the drawer slice (future execution only)**

```bash
git add src/Services/LpsContextResolver.php src/Controllers/Api/LpsApiController.php tests/test_lps_programa_general_scope.php frontend/src/lib/api/esquemas/programa-general.ts frontend/src/lib/api/esquemas/programa-general.test.ts frontend/src/lib/api/programa-general.ts frontend/src/lib/api/programa-general.test.ts frontend/src/modules/programa-general/componentes/ProgramaDrawer.tsx frontend/src/modules/programa-general/componentes/ProgramaDrawer.test.tsx frontend/src/modules/programa-general/dominio/resumenDrawer.ts frontend/src/modules/programa-general/dominio/resumenDrawer.test.ts frontend/src/modules/programa-general/ProgramaGeneralPage.tsx frontend/src/modules/programa-general/ProgramaGeneralPage.test.tsx frontend/src/modules/programa-general/programa-general.css
git commit -m "feat(programa-general): migrate contextual drawer actions"
```

---

### Task 11: Prove the pilot with intercepted browser, accessibility and design-system gates

**Files:**
- Create: `tests/browser/fixtures/programa-general-react.mjs`
- Create: `tests/browser/programa-general-react.spec.mjs`
- Create: `tests/browser/programa-general-react.a11y.mjs`
- Create: `tests/browser/programa-general-react.visual.mjs`
- Modify: `docs/design-system/manifests/programa-general.json` for pilot sources/tests only.
- Modify: `tests/design-system/contracts.test.mjs`
- Modify existing static PG design tests only where they assert an implementation vendor rather than
  observable behavior.

**Interfaces:**
- `installProgramaGeneralFixtures(page, scenario)` intercepts session/context/list/codes/week/save/
  batch/cut/drawer routes before navigation and records requests; no request falls through.
- Construction and Preconstruction fixture builders return Zod-valid objects with chapters,
  activities, all states, restrictions, codes, contacts, crisis and recursive comments.
- Browser gates target `/app/programa-general`; canonical `/programa-general` remains legacy here.

- [ ] **Step 1: Write failing functional and accessibility pilot scenarios**

```js
test.beforeEach(async ({ page }) => {
  audit = await installProgramaGeneralFixtures(page, { role: 'A', area: 'Construccion' });
  await page.goto('/app/programa-general?semana=6', { waitUntil: 'domcontentloaded' });
});

test('filtros, edición, batch, CSV, corte y drawer never fall through', async ({ page }) => {
  await page.getByRole('searchbox', { name: /buscar/i }).fill('excavacion');
  await page.getByRole('button', { name: /atrasada/i }).click();
  await page.getByRole('textbox', { name: /avance real.*excavación/i }).fill('25');
  await page.getByRole('textbox', { name: /avance real.*excavación/i }).press('Enter');
  await expect.poll(() => audit.save).toHaveLength(1);
  expect([...audit.save[0].form.keys()]).toEqual(EXACT_SAVE_KEYS);
  expect(audit.unhandled).toEqual([]);
});
```

Functional matrix:

- A/D current/past/confirmed actions, R/DCV current/past, OT/G/S/SG/V read, C no nav/403;
- week change/deep link invalid, project mismatch/stale response, browser back/forward;
- search, every structured filter, simple/multi chips, facet counts, clean URL;
- desktop/tablet/mobile, all screen/partial/error states, Construction/Preconstruction;
- save success/422/409/network/retry/discard, percent conversion, dirty navigation;
- manual/return-once batch, reload, CSV content, safe/unsafe cut URL;
- comments/replies/mentions, SOS manual/WhatsApp/mail/clipboard/simulation, digest and close crisis;
- exact request counts/methods/forms/CSRF and zero unhandled operational request.

Accessibility test runs shared keyboard helpers and Axe at all four viewports in both themes. Assert
no critical/serious violations, visible focus, 44 px targets, 200% zoom, no page overflow, table
headers/details, card headings, chip state, live-region deduplication, dialog trap/inert/Escape/
return, reduced-motion and no console error.

- [ ] **Step 2: Run pilot scenarios and confirm RED**

```bash
npx playwright test tests/browser/programa-general-react.spec.mjs tests/browser/programa-general-react.a11y.mjs --workers=1
node --test tests/design-system/contracts.test.mjs
```

Expected: RED until fixtures, selectors, accessibility and manifest pilot registration are complete.
All operational routes are intercepted; no DML can occur.

- [ ] **Step 3: Complete deterministic fixtures, pilot fixes and visual candidates**

Route patterns must abort the test if an unexpected `/api/general/*`, `/api/lps/*`, `/context/*` or
`/reportes/*` request would continue. Use synthetic names/IDs only. Download interception captures
Blob content and navigation without writing a user file.

Fix implementation defects exposed by the scenarios in the smallest owning file. Do not weaken
assertions, hide console errors, regenerate hashes or add arbitrary waits. Update the manifest only
to list React pilot sources/tests alongside legacy; do not replace canonical legacy scenarios yet.

Run the visual script for eight candidates and store output under `test-output/` or another ignored
path. It must not write `tests/browser/__screenshots__`:

```bash
npx playwright test tests/browser/programa-general-react.visual.mjs --workers=1
```

Present the eight candidates for the repository-required explicit visual approval. Until approved,
do not update golden paths/hashes and do not start Task 12.

- [ ] **Step 4: Re-run pilot gates and verify GREEN**

```bash
npx playwright test tests/browser/programa-general-react.spec.mjs tests/browser/programa-general-react.a11y.mjs --workers=1
node --test tests/design-system/contracts.test.mjs
npm --prefix frontend test -- src/modules/programa-general
npm --prefix frontend run typecheck
npm --prefix frontend run build
```

Expected: functional/a11y/design/frontend gates pass, candidates exist outside git, and no golden was
changed. Task 12 remains blocked until explicit candidate approval.

- [ ] **Step 5: Commit pilot evidence code, not candidate images (future execution only)**

```bash
git add tests/browser/fixtures/programa-general-react.mjs tests/browser/programa-general-react.spec.mjs tests/browser/programa-general-react.a11y.mjs tests/browser/programa-general-react.visual.mjs docs/design-system/manifests/programa-general.json tests/design-system/contracts.test.mjs frontend/src/modules/programa-general public/app
git commit -m "test(programa-general): prove intercepted React pilot"
```

---

### Task 12: Promote canonical GET/HEAD with a reversible method-aware cut

**Precondition:** Task 11 is green and Felipe has explicitly approved the eight React visual
candidates. Without that approval, stop here; do not replace baselines or promote the route.

**Files:**
- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `tests/test_spa_frontera.php`
- Modify: `tests/test_spa_frontera_http.php`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `tests/browser/programa-general-react.spec.mjs`
- Modify: `docs/design-system/manifests/programa-general.json`
- Modify approved golden files/hashes only through the repository visual-baseline workflow.
- Modify: `tests/design-system/contracts.test.mjs`

**Interfaces:**
- `SpaRouter::sirveLaSpa(method, path)` recognizes GET/HEAD `/programa-general` exactly as a migrated
  surface, never POST/filter/API/assets.
- React route aliases `/app/programa-general` and `/programa-general` render the same page.
- Rollback is one versioned change: remove canonical path from `RUTAS_MIGRADAS`; the still-present
  VIEW-34 route serves again until Task 13.

- [ ] **Step 1: Write failing method-aware boundary/canonical browser tests**

```php
assertTrue(SpaRouter::sirveLaSpa('GET', '/programa-general'));
assertTrue(SpaRouter::sirveLaSpa('HEAD', '/programa-general'));
assertFalse(SpaRouter::sirveLaSpa('POST', '/programa-general'));
assertFalse(SpaRouter::sirveLaSpa('POST', '/programa-general/filtros'));
assertFalse(SpaRouter::sirveLaSpa('GET', '/api/general/list'));
assertFalse(SpaRouter::sirveLaSpa('GET', '/app/assets/index.js'));
```

HTTP boundary asserts anonymous GET returns shell HTML, HEAD has no legacy body, APIs remain JSON,
POST filter is not captured as SPA, and `/programa-general-actualizar` stays legacy. Browser runs the
same intercepted scenario against pilot and canonical and compares accessible content/action state,
not implementation markup.

- [ ] **Step 2: Run cut tests and confirm RED**

```bash
docker compose exec -T app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
npx playwright test tests/browser/programa-general-react.spec.mjs --workers=1 --grep "pilot y canónica"
node --test tests/design-system/contracts.test.mjs
```

Expected: RED because canonical still resolves VIEW-34 and manifest/goldens still describe legacy.
Playwright operational APIs remain intercepted.

- [ ] **Step 3: Promote the route and approved visual contract**

Add `/programa-general` as an exact migrated GET/HEAD surface following the T01/S04 method-aware map;
do not use a broad prefix that captures POST or `/api`. Pass `$_SERVER['REQUEST_METHOD']` at both
`public/index.php` SPA decisions. Register both React aliases and use the canonical URL in T01
navigation/contextual links.

Through the approved baseline workflow only, promote the reviewed dark/light candidates for 390,
768, 1180 and 1440, update hashes/platform metadata, and change manifest canonical sources/tests to
React while retaining legacy sources as temporary rollback evidence until Task 13. Do not alter an
unapproved pixel or regenerate unrelated scenarios.

- [ ] **Step 4: Verify canonical/rollback gate GREEN**

```bash
docker compose exec -T app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
npx playwright test tests/browser/programa-general-react.spec.mjs tests/browser/programa-general-react.a11y.mjs --workers=1
node --test tests/design-system/contracts.test.mjs
npm --prefix frontend run build
```

Then temporarily remove `/programa-general` from the migrated map with `apply_patch`, rerun only the
focused boundary assertion proving VIEW-34 responds, and restore the exact verified cut with
`apply_patch`; finish with `git diff --check`. Do not use checkout/reset.

Expected: canonical React, APIs/POST/other modules preserved, approved visuals green, rollback proven
without any data change.

- [ ] **Step 5: Commit the canonical cut (future execution only)**

```bash
git add src/Core/SpaRouter.php public/index.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx tests/browser/programa-general-react.spec.mjs docs/design-system/manifests/programa-general.json tests/design-system/contracts.test.mjs public/app
git commit -m "feat(programa-general): promote canonical React route"
```

Before that commit, stage each approved golden path reported by `git diff --name-only` explicitly;
never stage the whole screenshot directory because it may contain unrelated candidates.

---

### Task 13: Retire VIEW-34 exclusively and run the complete no-DML closure

**Files:**
- Delete after zero-reference proof: `views/programa-general/programa_general.view.php`
- Delete after zero-reference proof: `public/js/modules/programa_general/hot.js`
- Delete after zero-reference proof: `public/css/programa-general.css`
- Delete after zero-reference proof: `src/Controllers/Programacion/ProgramaGeneralController.php`
- Modify: `public/index.php` — remove page/filter routes only.
- Modify: `docs/design-system/manifests/programa-general.json` and design inventories/exceptions.
- Modify/retire after mapping: `tests/browser/programa-general-legend-hue.mjs`,
  `programa-general-legend-modal-dark.mjs`, `programa-general-runtime-requests.mjs`,
  `programa-general-state-hue.mjs`, `programa-general.visual.mjs` and the PG branch in
  `design-system-body-canvas-dark.mjs`.
- Modify/retire after mapping: `tests/design-system/cascada-lps-a11y.test.mjs`,
  `legend-solid-contract.test.mjs`, `ops-state-contract.test.mjs`, `pg-severity-rail.test.mjs`,
  `programa-general-runtime-requests.test.mjs`, `state-tint-ladder.test.mjs` and
  `tests/test_programa_general_sprint_contract.mjs`.
- Modify current references/comments: `src/View/Components/DesignSystemHeadComponent.php`,
  `public/css/styles.css`, `public/css/programa-general-actualizar.css`,
  `public/css/programacion-intermedia.css`, `tests/test_bitacora_avance_endpoint.php`,
  `docs/design-system/coverage-debt.json`, `exceptions.json`, `manifests/inventory.json`,
  `ui-groups-inventory.json` and `unlayered-delivery-inventory.json`.
- Modify: `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` — S05 final status.

**Interfaces:**
- Canonical route remains React through `SpaRouter`; no PHP view/controller fallback is active.
- General list/restriction/codes/update/batch, report, LPS services/routes and all S06/S07/S08
  consumers remain.
- Post-retirement rollback is `git revert` of the retirement commit plus the cut-map revert; no DB
  restoration is involved.

- [ ] **Step 1: Write/update failing zero-reference and parity contracts**

Create a static assertion in the existing PG contract suite that requires:

```text
no registered /programa-general/filtros or /programa-general/set-filtro
no production reference to VIEW-34, hot.js or programa-general.css
React sources/tests present in the manifest
no handsontable-adapter/bootstrap/jquery/toastr/font-awesome/jquery-ui vendor for S05
every retained General/LPS/Report route still registered
```

Build a traceability table in the test source mapping every retired legacy assertion (toolbar,
states, filters, responsive, editing, batch, cut, drawer, themes, a11y) to a named React/PHP test.
The test must fail if a legacy file is deleted before its mapping exists.

- [ ] **Step 2: Run reference/parity checks and confirm RED**

```bash
rg -n "programa_general\.view|modules/programa_general/hot\.js|css/programa-general\.css|programa-general/filtros|programa-general/set-filtro" public src views frontend tests docs/design-system/manifests docs/design-system/exceptions.json docs/design-system/coverage-debt.json docs/design-system/ui-groups-inventory.json docs/design-system/unlayered-delivery-inventory.json
node tests/test_programa_general_sprint_contract.mjs
node --test tests/design-system/contracts.test.mjs
```

Expected: references still exist; the new retirement contract is RED until mapped and removed.

- [ ] **Step 3: Remove only proven-exclusive legacy and reconcile manifests/tests**

Use `apply_patch` for text deletions. Historical audit/measurement documents may continue to cite
the retired implementation as history; they are not active production references and must not be
rewritten to fabricate a different past. Remove the three legacy routes and controller only after the
route/reference inventory proves no non-S05 consumer. Remove VIEW-34/hot/CSS only after all behavior
has a named replacement. Keep historical approved screenshots as repository evidence unless the
visual-governance contract explicitly requires deletion and Felipe authorizes it; do not silently
delete baselines.

Update manifest sources/components/vendors/persistence/states/roles to the final React contract:

- sources are S05 React CSS/components plus scoped PHP adapters;
- components exclude `handsontable-adapter` and legacy overlay;
- vendors contain none of the retired libraries for S05;
- roles list A/D/R/DCV/OT/G/S/SG/V and explicit C-denied coverage;
- persistence is URL filters + session project/week + scoped return marker;
- tests name the intercepted functional/a11y/visual and PHP contracts.

Do not delete drawer aliases, General methods shared by S06, legacy state PHP, services or storage
directories.

- [ ] **Step 4: Run the complete safe closure and read each exit code**

```bash
docker compose config --services
docker compose ps
docker compose exec -T app php tests/test_programa_general_restriction_contract.php
docker compose exec -T app php tests/test_programa_general_action_policy.php
docker compose exec -T app php tests/test_programa_general_context_contract.php
docker compose exec -T app php tests/test_programa_general_input_validation.php
docker compose exec -T app php tests/test_programa_general_cut_contract.php
docker compose exec -T app php tests/test_lps_programa_general_scope.php
docker compose exec -T app vendor/bin/phpunit tests/unit/EstadoProgramaGeneralTest.php --group puro
docker compose exec -T app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
node tests/test_programa_general_sprint_contract.mjs
node --test tests/design-system/contracts.test.mjs
npx playwright test tests/browser/programa-general-react.spec.mjs tests/browser/programa-general-react.a11y.mjs --workers=1
git diff --check
```

Run each command separately and record its own exit code. Do not pipe or chain the gate. In addition,
verify source invariants read-only:

```bash
rg -n "fetch\(" frontend/src --glob '!lib/api/cliente.ts' --glob '!*.test.ts' --glob '!*.test.tsx'
rg -n "#[0-9A-Fa-f]{3,8}|rgba?\(|hsla?\(|!important|style=\{" frontend/src/modules/programa-general
rg -n "jquery|handsontable|bootstrap|toastr|font-awesome|hot\.js" frontend/src public/app/index.html
```

Expected: first two invariant searches return no production violations; vendor search returns no S05
bundle/import. Browser routes are intercepted, so no DML/report file occurs. Do not substitute any
excluded real-mutation suite for this gate.

- [ ] **Step 5: Record closure and commit retirement atomically (future execution only)**

Update the master atlas S05 row to “spec autorrevisada; plan ejecutado; canonical React; VIEW-34
retirada; safe gates green; real DML E2E intentionally not run”. Record exact SHA and verification
outputs in the plan closure section used by the repository; do not mark historical checkboxes as
evidence.

Stage only the paths named in Task 13 after reviewing `git diff --name-only`; never use a broad
`git add tests`, `git add frontend`, `git add src` or `git add docs` in a dirty shared worktree.

```bash
git add public/index.php docs/design-system/manifests/programa-general.json docs/design-system/coverage-debt.json docs/design-system/exceptions.json docs/design-system/manifests/inventory.json docs/design-system/ui-groups-inventory.json docs/design-system/unlayered-delivery-inventory.json src/View/Components/DesignSystemHeadComponent.php public/css/styles.css public/css/programa-general-actualizar.css public/css/programacion-intermedia.css tests/test_bitacora_avance_endpoint.php tests/test_programa_general_sprint_contract.mjs docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
git add -u views/programa-general/programa_general.view.php public/js/modules/programa_general/hot.js public/css/programa-general.css src/Controllers/Programacion/ProgramaGeneralController.php tests/browser/programa-general-legend-hue.mjs tests/browser/programa-general-legend-modal-dark.mjs tests/browser/programa-general-runtime-requests.mjs tests/browser/programa-general-state-hue.mjs tests/browser/programa-general.visual.mjs tests/browser/design-system-body-canvas-dark.mjs tests/design-system/cascada-lps-a11y.test.mjs tests/design-system/legend-solid-contract.test.mjs tests/design-system/ops-state-contract.test.mjs tests/design-system/pg-severity-rail.test.mjs tests/design-system/programa-general-runtime-requests.test.mjs tests/design-system/state-tint-ladder.test.mjs
git commit -m "refactor(programa-general): retire legacy surface"
```

Do not push, open a PR, merge, publish or deploy unless the closing-session authorization and the
repository publication gate explicitly require it.

---

## Vertical Checkpoints

| Checkpoint | Tasks | Demonstrable outcome | Gate before continuing |
|---|---:|---|---|
| V1 — Authorized read nucleus | 1–5 | Scoped context, exact restrictions/actions, typed gateway, normalized rows, search/filter/counts, legend, responsive table/cards, all read/empty/error states | PHP pure contracts + focused Vitest + typecheck/build |
| V2 — Individual editing | 6–7 | Server preflight/confirmed guard and identical table/mobile editing with validation/recovery | Pure PHP validation/static wiring + editor parity tests; no DML |
| V3 — Operations | 8–9 | Batch/manual-return/reload, visible CSV and scoped cut | Pure/frontend tests and fake report; no carryover/file write |
| V4 — Drawer | 10 | Both-area active scope, comments/replies/mentions, SOS, simulation, digest and crisis close | Fake PHP service + component/domain tests; no LPS write |
| V5 — Pilot | 11 | End-to-end observable parity at pilot path in two themes/four viewports | All requests intercepted, Axe/keyboard/reflow green, visual candidates reviewed |
| V6 — Canonical and retirement | 12–13 | Method-aware canonical React, approved visual contract, rollback proof, VIEW-34/assets retired | Full safe closure, zero-reference audit, no DML |

Do not begin a later checkpoint while its preceding gate is red. A green task test is not permission
to skip the vertical checkpoint or the explicit visual approval between V5 and V6.

## Spec Traceability

| Plan task | Primary UX requirements | Primary acceptance criteria |
|---:|---|---|
| 1 | UX-01, UX-03, UX-08 | AC-02, AC-03, AC-05, AC-08, AC-12 |
| 2 | UX-02, UX-04, UX-20, UX-22 | AC-04…07, AC-09, AC-13, AC-18, AC-21, AC-22 |
| 3 | UX-03, UX-08, UX-12 | AC-07, AC-08, AC-24 |
| 4 | UX-05…07 | AC-10 |
| 5 | UX-01…12, UX-21, UX-24…26 | AC-01…12, AC-25…28 |
| 6 | UX-12, UX-14, UX-15 | AC-12, AC-14, AC-15 |
| 7 | UX-11…16 | AC-13…17 |
| 8 | UX-04, UX-17, UX-18, UX-21 | AC-18, AC-19, AC-25 |
| 9 | UX-19, UX-20 | AC-20, AC-21 |
| 10 | UX-22, UX-23, UX-26 | AC-22, AC-23, AC-28 |
| 11 | all observable UX | AC-03, AC-09…29 |
| 12 | UX-01, UX-25, UX-26 | AC-01, AC-02, AC-27…30 |
| 13 | no legacy-only UX remains | AC-29…31 |

Prefix every shorthand above with `S05-`; ranges are inclusive. The combination covers all 26 UX
requirements and all 31 acceptance criteria from the spec.

## Explicitly Excluded Verification

The following are not evidence for S05 under the current no-DML rule and must not be run by this
plan: successful real `update`, `update-batch`, week/project selection, report generation against
project rows, comment/reply/crisis writes, dev-door flows, `full-app-flow`, operational-cycle helpers
or any test that starts a DB transaction and issues DML even if it later rolls back. Rollback is
still DML and does not satisfy the user's prohibition.

This exclusion does not turn mutations “green by assumption”: it is documented as the verification
boundary. Contract/policy/domain/component/intercept tests must demonstrate request and response
behavior; a future isolated disposable-database E2E requires separate authorization and is not part
of this plan.

## Self-Review Record

- Thirteen tasks each contain exact files/interfaces and five RED–GREEN–commit steps.
- The sequence starts with the read/context nucleus, then editing, operations, drawer, pilot, cut and
  retirement; it does not create a horizontal “all backend then all UI” migration.
- All new/consumed endpoints have a named Zod and/or PHP contract. The only passthrough schema is the
  audited legacy row adapter, which discards extras immediately.
- T01 owns sidebar/week/theme; S04 owns project switching; S05 does not introduce a second contract.
- No component calls `fetch`; no gateway infers role/scope; no client payload grants authorization.
- Action policy matches server authority: A/D past, R/DCV only maximum week, confirmed planning
  locked but progress/batch retained for an otherwise authorized editor.
- Construction and Preconstruction restrictions, labels/options, drawer scope and fixtures are all
  explicit.
- Table desktop/tablet, editable mobile cards, chapters, filters/counts, legend, validation, save,
  batch, return-once, reload, CSV, cut and every drawer action have tests and recovery states.
- Dark default/light complete, four viewports, tokens, keyboard, focus, Axe, zoom, reduced motion and
  no overflow are gated.
- RLS, database structure/data, grants, credentials, users, memberships and `/admin/` remain untouched.
- S06 request semantics and S07/S08 drawer consumers/aliases are preserved.
- Legacy exclusive files retire only after canonical proof and zero references; shared APIs/services
  remain. Visual baselines require explicit approval and are never silently regenerated.
- No task or final gate executes DDL/DML, including rolled-back writes.

## Decisions Pending

No business, product, strategy or PM decision is pending for S05. The visual-candidate approval in
Task 11 is an obligatory future release gate, not an unresolved architecture choice: without an
approved artifact, canonical cut does not proceed. No implementation begins from this document in
the current documentation-only session.
