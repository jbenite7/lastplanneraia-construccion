# Paridad funcional del shell React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development
> (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** convertir `/app` en el shell de uso real con, como mínimo, todas las capacidades
observables del login, selector de proyecto y shell legacy, sin reescribir todavía los módulos PHP.

**Architecture:** PHP conserva sesión, RBAC, dominio y rutas de módulos. Servicios compartidos
producen el bootstrap, manifiesto de navegación y contexto semanal; los adaptadores JSON y legacy
consumen esos mismos servicios. React valida todo con Zod, coordina los seis estados de arranque y
abre los módulos no migrados mediante navegación de página completa.

**Tech Stack:** PHP 8.3, React 19.2, TypeScript 7, Vite 8, Zod 4, Vitest 4, Testing Library y
Playwright 1.61. No se añade una librería visual ni una dependencia de iconos.

**Spec:** `docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design.md`

**Visual source:** `docs/superpowers/specs/evidencia/2026-08-28-shell-react-design-system-atlas.html`

**Hard dependency:** completar primero
`docs/superpowers/plans/2026-08-28-rls-aplicacion-fail-closed.md` y conservar verdes sus
gates. Este plan nunca introduce un bypass para hacer funcionar la interfaz.

## Global Constraints

- React no contiene mapas de roles ni decide autorización. Renderiza el manifiesto y las
  capacidades resueltas por PHP.
- Cada endpoint consumido por React tiene contrato PHP real y esquema Zod; el esquema genera el
  tipo TypeScript.
- Solo `frontend/src/lib/api/cliente.ts` llama `fetch`.
- Login, selector y módulos PHP comparten la cookie PHP de mismo origen; no hay token de sesión
  alterno, iframe ni microfrontend.
- Las mutaciones usan CSRF y un identificador estable; nunca se reintentan automáticamente.
- Los links a módulos no migrados son `<a>` normales y producen recarga completa.
- Los estilos nuevos consumen `public/css/tokens.css` y los alias activos de
  `aia-design-system.css`. No se permiten colores, sombras, radios o escalas locales.
- La fuente visual vigente es el atlas aprobado. Los tokens y assets del repo prevalecen sobre los
  valores orientativos de la guía compacta de marca AIA.
- Montserrat se limita a marca/títulos y Inter a interfaz/lectura, mediante
  `--ds-font-display` y `--ds-font-body`.
- Claro es la entrada; oscuro se aplica antes del primer render. Construcción usa el token de
  dominio naranja y Pre-Construcción un tratamiento neutral.
- Viewports contractuales: 390 px, 768 px y 1180 px. El drawer existe solo por debajo de 1180 px.
- Recuperación y restablecimiento de contraseña continúan en PHP.
- Una fila de la matriz solo pasa a `accepted` tras implementación, prueba y revisión; el
  script no inventa aceptaciones.
- Docker es la fuente de verdad para PHP. Verificar el mount antes de contratos HTTP o con DB.
- Ningún test guarda credenciales, cookies, tokens CSRF ni datos humanos en evidencia.

## File Structure

**Backend compartido:**

- `src/Http/ApiJsonResponse.php` — éxito/error JSON estable y correlación.
- `src/Services/Shell/ShellNavigationService.php` — destinos filtrados por RBAC.
- `src/Services/Shell/ShellWeekService.php` — snapshot, cambio, creación y borrado semanal.
- `src/Services/Shell/ShellBootstrapService.php` — modelo canónico de arranque.
- `src/Services/Auth/ForcedPasswordChangeService.php` — flujo compartido API/legacy.
- `src/Controllers/Api/ShellWeekApiController.php` — adaptador JSON semanal.

**Frontend:**

- `frontend/src/lib/api/esquemas/{error,auth,proyectos,sesion}.ts` — contratos Zod.
- `frontend/src/shell/SesionProvider.tsx` — estado canónico y expiración.
- `frontend/src/design-system/*.tsx` — primitivas accesibles.
- `frontend/src/styles/{shell,auth,project-picker}.css` — composición token-driven.
- `frontend/src/shell/{CambioClaveObligatorio,AppShell,MenuCuenta,ContextoSemana}.tsx`.

**Evidencia y gates:**

- `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json`.
- `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md`.
- `scripts/shell-parity-matrix.mjs`.
- `tests/browser/react-shell-{parity,responsive,a11y,visual}.spec.mjs`.

---

### Task 1: Hacer ejecutable el contrato de paridad

**Files:**
- Create: `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json`
- Create: `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md`
- Create: `scripts/shell-parity-matrix.mjs`
- Create: `tests/shell-parity-matrix.test.mjs`
- Modify: `package.json`

**Interfaces:**
- Source: JSON con `id, surface, legacySource, scenario, roles, areas, action, expected,
  backendContract, reactOwner, automatedEvidence, visualEvidence, status`.
- Command: `node scripts/shell-parity-matrix.mjs verify`.
- Command: `node scripts/shell-parity-matrix.mjs render` actualiza el Markdown sin cambiar
  estados.

- [ ] **Step 1: Write the failing matrix contract**

El test exige IDs únicos, rutas existentes, estados válidos y estos IDs mínimos:

```text
auth.brand-context auth.safe-messages auth.timeout-notice auth.inactive-notice
auth.credentials auth.password-visibility auth.submit-busy auth.forgot-link
auth.forced-change auth.forced-validation auth.forced-cancel
picker.account picker.logout picker.theme picker.control-tower picker.search
picker.live-count picker.card-metadata picker.select-busy picker.empty
picker.no-results picker.retry
nav.groups nav.server-authorization nav.active nav.project-user
nav.change-project nav.logout
week.current week.list-dates week.change week.module-destinations
week.create-next week.delete-last-only
responsive.desktop-sidebar responsive.drawer responsive.focus-escape-scrolllock
responsive.no-horizontal-overflow
theme.light theme.dark theme.prepaint-persistence
startup.loading startup.anonymous startup.auth-no-project startup.auth-project
startup.expired-or-revoked startup.recoverable-failure
```

Para cada fila `implemented` o posterior, `backendContract` y
`automatedEvidence` deben apuntar a archivos existentes. Para
`verified/accepted`, toda evidencia requerida debe existir y no puede contener
`pending`.

- [ ] **Step 2: Run and see red**

```bash
node --test tests/shell-parity-matrix.test.mjs
```

Expected: FAIL porque la matriz y su validador no existen.

- [ ] **Step 3: Inventory the legacy sources**

Crear las filas desde estos dueños exactos:

- login: `views/auth/login.view.php` y `src/Controllers/Auth/LoginController.php`;
- selector: `views/core/project_selector.view.php` y
  `src/Services/ProjectAccessService.php`;
- navegación/contexto: `views/partials/shell_sidebar.php`;
- semana: `public/js/modules/aia_ui/shell_week_admin.js`,
  `src/Legacy/nueva_semana.php` y `src/Legacy/eliminar_semana.php`;
- responsive/tema: `tests/browser/shell-menu-flotante.mjs`,
  `tests/browser/shell-week-admin.mjs` y `frontend/src/shell/tema.ts`.

Cada fila empieza como `inventoried`. El Markdown se genera con una tabla por superficie y
una leyenda que define `inventoried → implemented → verified → accepted`.

- [ ] **Step 4: Implement and run the validator**

Añadir a `package.json`:

```json
"test:shell-parity": "node --test tests/shell-parity-matrix.test.mjs && node scripts/shell-parity-matrix.mjs verify"
```

Run:

```bash
npm run test:shell-parity
```

Expected: PASS con todas las filas inventariadas y cero evidencia falsa.

- [ ] **Step 5: Commit**

```bash
git add docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md \
  scripts/shell-parity-matrix.mjs tests/shell-parity-matrix.test.mjs package.json
git commit -m "test(shell): inventariar paridad observable"
```

---

### Task 2: Selección de proyecto por ID y errores JSON estables

**Files:**
- Create: `src/Http/ApiJsonResponse.php`
- Create: `tests/unit/ApiJsonResponseTest.php`
- Modify: `src/Services/ProjectAccessService.php`
- Modify: `src/Controllers/Api/ProjectApiController.php`
- Modify: `src/Controllers/Core/ProjectSelectorController.php`
- Modify: `src/Controllers/Core/DevDoorController.php`
- Modify: `public/index.php`
- Modify: `tests/test_api_projects_contract.php`
- Test: `tests/test_selector_proyectos_criterio_unico.php`

**Interfaces:**
- `GET /api/proyectos` → `{projects: ProjectSummary[]}`.
- `ProjectSummary` =
  `{id:int,name:string,area:"Construccion"|"Pre-Construccion",status:{code:"active",label:string},role:{code:string,name:string}}`.
- `POST /api/proyectos/seleccionar` body `{id:int}`.
- `DELETE /api/proyectos/seleccion` limpia solo el proyecto, no la autenticación.
- Error = `{error:{code,message,fieldErrors?,correlationId}}`.

- [ ] **Step 1: Extend the HTTP contract first**

El contrato debe fallar si:

1. falta área, estado o nombre de rol;
2. la selección acepta nombre en vez de ID;
3. un ID ajeno revela si existe;
4. cambiar proyecto deja `project_id`, `proyecto`, `db`,
   `semana` o `permiso` en sesión;
5. 401/403/404/422 carecen de `error.code` o correlación.

Añadir el unit test para que `ApiJsonResponse::error()` no incluya excepción, SQL, ruta
local ni detalles adicionales.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ApiJsonResponseTest.php
docker compose exec app php tests/test_api_projects_contract.php
```

- [ ] **Step 3: Add the shared responder**

La API expone:

```php
ApiJsonResponse::ok(array $payload, int $status = 200): void
ApiJsonResponse::error(
    int $status,
    string $code,
    string $message,
    array $fieldErrors = [],
    ?string $correlationId = null,
): void
```

La correlación usa `bin2hex(random_bytes(8))` cuando no llega una ya creada. El log guarda
la excepción por separado; el cuerpo nunca la serializa.

- [ ] **Step 4: Make ID canonical without breaking legacy**

En `ProjectAccessService`:

```php
public function selectById(string $username, int $projectId): array
public function selectByName(string $username, string $projectName): array
public function clearSelection(): void
```

Ambas selecciones llaman un único método privado que valida la fila de membresía y aplica la sesión.
`selectById()` consulta `u.usuario + p.ID`; el nombre no participa. El controlador
legacy conserva `selectByName()` y la puerta de desarrollo lo usa solo después de resolver
su fixture. La API acepta únicamente `id` entero positivo.

`clearSelection()` elimina exactamente:
`project_id, proyecto, db, semana, permiso, permiso_canonico, pdcActivo` y limpia
`Database::dataScope()`.

- [ ] **Step 5: Run contracts and selector ownership gate**

```bash
docker compose exec app php tests/test_api_projects_contract.php
docker compose exec app php tests/test_selector_proyectos_criterio_unico.php
```

Expected: PASS; el selector legacy continúa entrando por nombre y React ya usa ID.

- [ ] **Step 6: Commit**

```bash
git add src/Http/ApiJsonResponse.php tests/unit/ApiJsonResponseTest.php \
  src/Services/ProjectAccessService.php src/Controllers/Api/ProjectApiController.php \
  src/Controllers/Core/ProjectSelectorController.php src/Controllers/Core/DevDoorController.php \
  public/index.php tests/test_api_projects_contract.php \
  tests/test_selector_proyectos_criterio_unico.php
git commit -m "feat(shell): seleccionar proyectos por id estable"
```

---

### Task 3: Cambio obligatorio de contraseña dentro de React

**Files:**
- Create: `src/Services/Auth/ForcedPasswordChangeService.php`
- Create: `tests/support/TemporaryShellUser.php`
- Create: `tests/unit/ForcedPasswordChangeServiceTest.php`
- Modify: `src/Controllers/Api/AuthApiController.php`
- Modify: `src/Controllers/Auth/LoginController.php`
- Modify: `src/Services/Auth/AuthenticationService.php`
- Modify: `public/index.php`
- Modify: `tests/test_api_auth_contract.php`

**Interfaces:**
- Login success → `{success:true,next:"password_change"|"projects"}`.
- `POST /api/auth/password/change` body `{password,confirmation}`.
- `POST /api/auth/password/cancel` body vacío.
- Ambos requieren CSRF y una sesión pendiente; ninguna ruta acepta username del body.

- [ ] **Step 1: Write service and HTTP red tests**

`TemporaryShellUser` inserta un usuario único de máximo 20 caracteres con
`nombre,email,cargo,usuario,password,force_password_change=1,activo=1`, usando una clave
aleatoria generada en memoria. `finally` elimina por ID. No usa cuentas humanas ni imprime
la clave.

El contrato prueba:

- login correcto produce `next=password_change`;
- recargar bootstrap conserva el estado pendiente;
- cambio sin CSRF → 403;
- clave corta, sin mayúscula, sin especial o distinta → 422 con errores de campo;
- éxito limpia `usuario_temp/must_change_password` y permite entrar con la nueva clave;
- cancelar destruye únicamente la sesión pendiente y vuelve a estado anónimo.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ForcedPasswordChangeServiceTest.php
docker compose exec app php tests/test_api_auth_contract.php
```

- [ ] **Step 3: Extract the shared domain flow**

`ForcedPasswordChangeService` expone:

```php
public function required(array $session): bool
public function change(array &$session, string $password, string $confirmation): array
public function cancel(array &$session): void
```

`change()` toma el usuario únicamente de `usuario_temp`, delega la política y el
hash en `UserPasswordService`, promueve la sesión con
`AuthenticationService::completePendingSession()` y conserva el comportamiento de
mantenimiento. `LoginController` se vuelve adaptador form/redirect del mismo servicio.

- [ ] **Step 4: Add the JSON routes**

Registrar como públicas solo porque pertenecen al estado previo a autenticación completa:

```php
$router->post('/api/auth/password/change', [AuthApiController::class, 'changePassword']);
$router->post('/api/auth/password/cancel', [AuthApiController::class, 'cancelPasswordChange']);
```

“Pública” no significa abierta: ambas requieren sesión pendiente + CSRF y responden 401 genérico
si no existe el estado.

- [ ] **Step 5: Run auth and password regressions**

```bash
docker compose exec app php tests/test_api_auth_contract.php
docker compose exec app php tests/test_password_reset_resultados.php
```

Expected: PASS y el usuario temporal desaparece incluso si una aserción falla.

- [ ] **Step 6: Commit**

```bash
git add src/Services/Auth/ForcedPasswordChangeService.php \
  tests/support/TemporaryShellUser.php tests/unit/ForcedPasswordChangeServiceTest.php \
  src/Controllers/Api/AuthApiController.php src/Controllers/Auth/LoginController.php \
  src/Services/Auth/AuthenticationService.php public/index.php tests/test_api_auth_contract.php
git commit -m "feat(shell): integrar cambio obligatorio de clave"
```

---

### Task 4: Un manifiesto de navegación autorizado para React y legacy

**Files:**
- Create: `src/Services/Shell/ShellNavigationService.php`
- Create: `tests/unit/ShellNavigationServiceTest.php`
- Create: `tests/test_shell_navigation_manifest.php`
- Modify: `views/partials/shell_sidebar.php`
- Modify: `views/core/project_selector.view.php`
- Modify: `tests/browser/project-selector-sidebar.spec.mjs`
- Modify: `tests/browser/shell-sidebar-rollout.mjs`

**Interfaces:**
- `forProject(role, area, activeId): {groups,account,controlTower}`.
- `forProjectPicker(role): {groups,account,controlTower}`.
- Item = `{id,label,href:string|null,action:"weeks"|null,icon,current}`.

- [ ] **Step 1: Write the role/area matrix**

Los unit tests usan un callable de permiso falso y fijan este mapa:

| id | permiso |
|---|---|
| profesionales | `lps.profesionales.ver` |
| subcontratistas | `lps.subcontratistas.ver` |
| indicadores | `lps.indicadores.ver` |
| control-cambios | `lps.control_cambios.ver` |
| programa-general | `lps.programa_general.ver` |
| programacion-intermedia | `lps.programacion_intermedia.ver` |
| programacion-semanal | `lps.programacion_semanal.ver` |
| actualizar-cronograma | `lps.programa_general_actualizar.ver` |
| plan-compras | `lps.pdc.ver` |

`semanas-proyecto` es una acción contextual, no un permiso de mutación.
`plan-compras` se excluye en Pre-Construcción. Control Tower usa exclusivamente
`BiAccessComponent`. Los IDs e iconos permitidos quedan como constantes del servicio.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ShellNavigationServiceTest.php
docker compose exec app php tests/test_shell_navigation_manifest.php
```

- [ ] **Step 3: Implement the service**

El servicio filtra con `RbacService::can(permission, role)`, agrupa
Información/Obra/Compras y marca `current` solo después de autorizar el ID. Cuenta devuelve
acciones `change_project` y `logout`; tema permanece como control local siempre
disponible.

No transcribir `$shellHiddenByRole`. Eliminar esa tabla de
`shell_sidebar.php` y pasar la salida del servicio a
`DesignSystemComponent::navigation()`. El selector legacy consume
`forProjectPicker()`.

- [ ] **Step 4: Prove legacy and manifest use the same source**

`tests/test_shell_navigation_manifest.php` falla si:

- reaparece `shellHiddenByRole` u `ocultasPorRol`;
- una ruta visible no tiene permiso o excepción BI;
- la vista declara manualmente un destino;
- un rol sin permiso recibe el item en JSON.

- [ ] **Step 5: Run focused browser regressions**

```bash
npx playwright test tests/browser/project-selector-sidebar.spec.mjs \
  tests/browser/shell-sidebar-rollout.mjs --workers=1
```

- [ ] **Step 6: Commit**

```bash
git add src/Services/Shell/ShellNavigationService.php \
  tests/unit/ShellNavigationServiceTest.php tests/test_shell_navigation_manifest.php \
  views/partials/shell_sidebar.php views/core/project_selector.view.php \
  tests/browser/project-selector-sidebar.spec.mjs tests/browser/shell-sidebar-rollout.mjs
git commit -m "refactor(shell): centralizar navegacion autorizada"
```

---

### Task 5: Extraer el dominio semanal y ofrecer contratos JSON

**Files:**
- Create: `src/Services/Shell/WeekMutationResult.php`
- Create: `src/Services/Shell/ShellWeekService.php`
- Create: `src/Controllers/Api/ShellWeekApiController.php`
- Create: `tests/unit/ShellWeekServiceTest.php`
- Create: `tests/test_shell_week_api_contract.php`
- Modify: `src/Controllers/Core/ContextController.php`
- Modify: `src/Controllers/Api/ControlCambiosApiController.php`
- Modify: `src/Legacy/nueva_semana.php`
- Modify: `src/Legacy/eliminar_semana.php`
- Modify: `src/Legacy/verificarCICActualizada.php`
- Modify: `src/Legacy/modificar_sem_estado.php`
- Modify: `public/index.php`
- Modify: `tests/test_linea_base_sembrado_al_consolidar.php`
- Modify: `tests/test_control_cambios_nueva_sem_include_scope.php`
- Test: `tests/test_week_context_write_scope.php`
- Test: `tests/test_weekly_governance.php`

**Interfaces:**
- `snapshot(ProjectScope, role): array`.
- `change(ProjectScope, int week): WeekMutationResult`.
- `createNext(ProjectScope, DateTimeImmutable start, string role): WeekMutationResult`.
- `deleteLast(ProjectScope, int week, string role): WeekMutationResult`.
- `POST /api/shell/week`, `POST /api/shell/weeks`,
  `DELETE /api/shell/weeks/{week:\d+}`.

- [ ] **Step 1: Write domain and HTTP tests**

Los tests cubren:

- snapshot ordenado con número, inicio y fin;
- sugerencia = día posterior al fin de la última semana;
- cambio solo a una semana existente en el proyecto activo;
- creación requiere `lps.semana.crear` y conserva las reglas de CIC/confirmación;
- primera semana exige Programa Maestro;
- copia Construcción y Pre-Construcción sin perder normalización, carryover o línea base;
- eliminación requiere `lps.semana.eliminar` y solo acepta el máximo;
- crear/eliminar son transaccionales y restauran al fallar;
- ID/prefijo del body nunca cambia el `ProjectScope`.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ShellWeekServiceTest.php
docker compose exec app php tests/test_shell_week_api_contract.php
```

- [ ] **Step 3: Move, do not duplicate, the legacy business logic**

Mover el cuerpo de creación de `nueva_semana.php` y el cascade de
`eliminar_semana.php` a `ShellWeekService`. Conservar llamadas a:

- `RestrictionConfigResolver`;
- `ProgramaConsolidadoNormalizationService`;
- `WeeklyRealProgressCarryoverService`;
- `LineaBaseContractualService::sembrarSiFalta()`;
- las funciones de cálculo de estado existentes.

`createNext()` hace una transacción única desde inserción hasta actualización de estado.
`deleteLast()` bloquea `semanas_activas` con `FOR UPDATE` antes de
revalidar el máximo. El resultado tipado contiene `code,message,currentWeek,maxWeek` y
metadatos necesarios para que cada adaptador traduzca su forma.

- [ ] **Step 4: Turn legacy files into thin adapters**

Los scripts legacy conservan rutas, CSRF, RBAC y formas de respuesta históricas, pero no contienen
SQL de creación/borrado. `ContextController::setWeek()` delega a `change()`.
`ControlCambiosApiController` llama el servicio, no hace `require` de scripts.
Actualizar los dos tests estáticos para exigir el nuevo dueño en vez del include antiguo.

- [ ] **Step 5: Add JSON adapters**

Todos usan `ApiJsonResponse`:

```json
{"week":{"current":8,"max":12,"items":[{"number":8,"startDate":"2026-08-24","endDate":"2026-08-30"}],"canCreate":true,"canDelete":true,"nextNumber":13,"suggestedStartDate":"2026-11-16"}}
```

422 cubre fecha/semana inválida; 403 permiso; 404 semana ajena o inexistente; 409 confirmación/CIC o
cambio concurrente.

- [ ] **Step 6: Run domain, contract and legacy gates**

```bash
docker compose exec app php tests/test_shell_week_api_contract.php
docker compose exec app php tests/test_week_context_write_scope.php
docker compose exec app php tests/test_weekly_governance.php
docker compose exec app php tests/test_linea_base_sembrado_al_consolidar.php
docker compose exec app php tests/test_control_cambios_nueva_sem_include_scope.php
npx playwright test tests/browser/shell-week-admin.mjs --workers=1
```

- [ ] **Step 7: Commit**

```bash
git add src/Services/Shell/WeekMutationResult.php src/Services/Shell/ShellWeekService.php \
  src/Controllers/Api/ShellWeekApiController.php tests/unit/ShellWeekServiceTest.php \
  tests/test_shell_week_api_contract.php src/Controllers/Core/ContextController.php \
  src/Controllers/Api/ControlCambiosApiController.php src/Legacy/nueva_semana.php \
  src/Legacy/eliminar_semana.php src/Legacy/verificarCICActualizada.php \
  src/Legacy/modificar_sem_estado.php public/index.php \
  tests/test_linea_base_sembrado_al_consolidar.php \
  tests/test_control_cambios_nueva_sem_include_scope.php
git commit -m "refactor(shell): compartir dominio de semanas"
```

---

### Task 6: Producir un bootstrap canónico completo

**Files:**
- Create: `src/Services/Shell/ShellBootstrapService.php`
- Create: `tests/unit/ShellBootstrapServiceTest.php`
- Modify: `src/Controllers/Api/SessionApiController.php`
- Modify: `tests/test_api_session_contract.php`

**Interfaces:**
- `ShellBootstrapService::build(array $session, ?string $reason): array`.
- `GET /api/session` sigue respondiendo 200 para estado anónimo.

- [ ] **Step 1: Write the discriminated bootstrap contract**

La forma exacta es:

```ts
type BootstrapState = 'anonymous' | 'password_change_required' | 'authenticated';

type Bootstrap = {
  state: BootstrapState;
  reason: null | 'missing_session' | 'timeout' | 'inactive' | 'stale_session' | 'session_unverified';
  user: null | { username: string; displayName: string; role: string };
  project: null | { id: number; name: string; area: 'Construccion' | 'Pre-Construccion' };
  capabilities: Record<string, boolean>;
  navigation: {
    groups: NavigationGroup[];
    account: { label: string; items: AccountItem[] } | null;
    controlTower: { label: string; href: string } | null;
  };
  week: null | WeekSnapshot;
  csrfToken: string;
};
```

Invariantes:

- `anonymous`: user/project/week nulos, navegación vacía;
- `password_change_required`: no filtra `usuario_temp`, proyecto ni rol;
- autenticado sin proyecto: user presente, project/week nulos, navegación de selector;
- autenticado con proyecto: el objeto ProjectScope coincide con project.id y habilita nav/week;
- jamás salen `db`, cookies, tokens internos, hashes o permisos crudos de membresía.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ShellBootstrapServiceTest.php
docker compose exec app php tests/test_api_session_contract.php
```

- [ ] **Step 3: Compose existing services**

`ShellBootstrapService` recibe `ShellNavigationService`,
`ShellWeekService`, `ForcedPasswordChangeService` y `RbacService`.
No consulta tablas directamente. `SessionApiController` serializa la razón ya capturada por
`SessionMiddleware::requestFailureReason()` al inicio del request; no vuelve a ejecutar la
validación ni altera el scope enlazado.

El token `shell_api` continúa estable durante la sesión y rota cuando se destruye.

- [ ] **Step 4: Run all backend shell contracts**

```bash
docker compose exec app php tests/test_api_session_contract.php
docker compose exec app php tests/test_api_auth_contract.php
docker compose exec app php tests/test_api_projects_contract.php
docker compose exec app php tests/test_shell_week_api_contract.php
docker compose exec app php tests/test_shell_navigation_manifest.php
```

Expected: cinco RC 0.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Shell/ShellBootstrapService.php \
  tests/unit/ShellBootstrapServiceTest.php src/Controllers/Api/SessionApiController.php \
  tests/test_api_session_contract.php
git commit -m "feat(shell): servir bootstrap autorizado completo"
```

---

### Task 7: Tipar la frontera React y modelar los seis estados de arranque

**Files:**
- Create: `frontend/src/lib/api/esquemas/error.ts`
- Create: `frontend/src/lib/api/esquemas/auth.ts`
- Create: `frontend/src/lib/api/esquemas/proyectos.ts`
- Create: `frontend/src/shell/SesionProvider.tsx`
- Create: `frontend/src/shell/SesionProvider.test.tsx`
- Create: `frontend/src/shell/ErrorBoundaryShell.tsx`
- Create: `frontend/src/shell/ErrorBoundaryShell.test.tsx`
- Modify: `frontend/src/lib/api/esquemas/sesion.ts`
- Modify: `frontend/src/lib/api/cliente.ts`
- Modify: `frontend/src/lib/api/cliente.test.ts`
- Modify: `frontend/src/shell/useSesion.ts`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `frontend/src/App.tsx`

**Interfaces:**
- Todos los tipos públicos son `z.infer`.
- `ErrorApi` conserva status, code, message, fieldErrors y correlationId.
- `SesionProvider` expone `bootstrap, estado, recargar, entrarAnonimo`.

- [ ] **Step 1: Write schema and client red tests**

Cubrir:

- discriminación de los tres `state` y sus invariantes;
- rechazo de href externo, icono desconocido, week inconsistente y proyecto sin área;
- parseo del error estable;
- 401 emite expiración y limpia estado;
- GET reintenta una vez solo ante red/5xx;
- POST/DELETE no reintentan;
- JSON mal formado y shape inválida conservan la ruta en el error de desarrollo, no en UI.

- [ ] **Step 2: Run and see red**

```bash
npm --prefix frontend test -- \
  src/lib/api/cliente.test.ts src/shell/SesionProvider.test.tsx
```

- [ ] **Step 3: Define Zod as the only model**

`sesion.ts` declara esquemas para `NavigationItem`,
`NavigationGroup`, `WeekSnapshot` y el bootstrap. La validación agrega
`superRefine`:

```ts
if (value.state === 'authenticated' && value.user === null) {
  ctx.addIssue({ code: 'custom', path: ['user'], message: 'authenticated exige user' });
}
if (value.project === null && value.week !== null) {
  ctx.addIssue({ code: 'custom', path: ['week'], message: 'week exige project' });
}
```

`auth.ts` y `proyectos.ts` contienen las respuestas de sus endpoints; ningún
componente declara un `type Proyecto` paralelo.

- [ ] **Step 4: Implement typed HTTP failures and retry policy**

`cliente.ts` conserva una sola función pública:

```ts
pedir<T>(
  ruta: string,
  esquema: ZodType<T>,
  opciones?: RequestInit & { reintentosLectura?: 0 | 1 },
): Promise<T>
```

Solo GET/HEAD con `reintentosLectura=1` reintenta una vez y únicamente ante excepción de
red o status 500–599. Para 401 despacha `aia:session-expired` antes de lanzar
`ErrorApi`. 403/404/409/422 llegan tipados al componente. No usar sleeps en tests:
inyectar el backoff como promesa resuelta.

- [ ] **Step 5: Add provider and contained error boundary**

`SesionProvider` consulta bootstrap al montar, escucha expiración, descarta inmediatamente
user/project/navigation/week y vuelve a estado anónimo con aviso. `recargar()` no conserva
contenido operativo durante la espera.

`ErrorBoundaryShell` recibe `fallback` y `onRetry`; contiene errores del
shell sin ocultar el botón de recuperación. `useSesion()` pasa a ser un hook del contexto,
no un segundo fetch.

- [ ] **Step 6: Run frontend state gates**

```bash
npm run frontend:typecheck
npm run frontend:test
```

Expected: ambos RC 0 y cero `fetch(` fuera de `cliente.ts`:

```bash
test "$(rg -l 'fetch\(' frontend/src --glob '*.{ts,tsx}' | wc -l | tr -d ' ')" = "1"
```

- [ ] **Step 7: Commit**

```bash
git add frontend/src/lib/api/esquemas/error.ts frontend/src/lib/api/esquemas/auth.ts \
  frontend/src/lib/api/esquemas/proyectos.ts frontend/src/lib/api/esquemas/sesion.ts \
  frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts \
  frontend/src/shell/SesionProvider.tsx frontend/src/shell/SesionProvider.test.tsx \
  frontend/src/shell/ErrorBoundaryShell.tsx frontend/src/shell/ErrorBoundaryShell.test.tsx \
  frontend/src/shell/useSesion.ts frontend/src/shell/rutas.tsx \
  frontend/src/shell/rutas.test.tsx frontend/src/App.tsx
git commit -m "feat(shell): tipar bootstrap y expiracion"
```

---

### Task 8: Construir las primitivas React del sistema visual aprobado

**Files:**
- Create: `frontend/src/design-system/Boton.tsx`
- Create: `frontend/src/design-system/Campo.tsx`
- Create: `frontend/src/design-system/CampoContrasena.tsx`
- Create: `frontend/src/design-system/Aviso.tsx`
- Create: `frontend/src/design-system/Dialogo.tsx`
- Create: `frontend/src/design-system/Icono.tsx`
- Create: `frontend/src/design-system/EstadoPantalla.tsx`
- Create: `frontend/src/design-system/primitivas.test.tsx`
- Create: `frontend/src/styles/shell.css`
- Create: `frontend/src/styles/auth.css`
- Create: `frontend/src/styles/project-picker.css`
- Create: `scripts/design-system-react-contract.mjs`
- Create: `tests/design-system/react-shell-tokens.test.mjs`
- Modify: `frontend/src/main.tsx`
- Modify: `package.json`

**Interfaces:**
- Botón: primary/secondary/ghost/danger + busy.
- Aviso: info/success/warning/error con `role` correcto.
- Diálogo: título/descripción, foco inicial, Escape, restore focus.
- Icono: allowlist local de los IDs que entrega el servidor; siempre decorativo salvo label explícito.

- [ ] **Step 1: Write component and CSS governance tests**

Testing Library verifica:

- botones ocupados no admiten segundo envío y anuncian estado;
- campo de contraseña alterna tipo, `aria-pressed` y label;
- aviso error usa `role=alert`; estados no urgentes usan `status`;
- diálogo devuelve foco al disparador y cierra con Escape;
- un icono desconocido falla en desarrollo y usa fallback neutral en producción.

El gate de CSS recorre solo `frontend/src/styles/*.css` y rechaza:

- hex, `rgb()`, `hsl()`, sombras o radios literales;
- `!important`;
- fuentes distintas de los tokens;
- `transition: all`;
- media queries distintas de `prefers-reduced-motion`, `(max-width: 1179px)` y
  `(max-width: 639px)`.

Se permiten `transparent`, `currentColor`, porcentajes y dimensiones estructurales.

- [ ] **Step 2: Run and see red**

```bash
npm --prefix frontend test -- src/design-system/primitivas.test.tsx
node --test tests/design-system/react-shell-tokens.test.mjs
```

- [ ] **Step 3: Implement primitives with existing AIA tokens**

Consumir, entre otros:

- `--ds-font-display/body`;
- `--ds-active-bg-canvas/page/surface/surface-raised`;
- `--ds-active-text-primary/secondary`;
- `--ds-active-action-primary/action-text/domain-construction`;
- `--ds-active-border/border-control/focus-ring`;
- `--ds-space-*`, `--ds-radius-*`, `--ds-elevation-*`,
  `--ds-motion-*` y `--ds-target-min`.

No copiar los colores del atlas: el atlas define composición; el repo define tokens.
`prefers-reduced-motion: reduce` reduce toda transición a los tokens de movimiento reducido.

- [ ] **Step 4: Import CSS once**

`main.tsx` importa las tres hojas, en orden shell → auth → selector. El HTML continúa
cargando tokens/DS antes del bundle y aplicando el tema guardado antes de CSS, por lo que no aparece
flash oscuro/claro.

Añadir:

```json
"test:design-system:react-shell": "node --test tests/design-system/react-shell-tokens.test.mjs"
```

- [ ] **Step 5: Run proportional visual-system gates**

```bash
npm --prefix frontend test -- src/design-system/primitivas.test.tsx
npm run test:design-system:react-shell
npm run test:design-system:static
npm run frontend:typecheck
```

- [ ] **Step 6: Commit**

```bash
git add frontend/src/design-system/Boton.tsx frontend/src/design-system/Campo.tsx \
  frontend/src/design-system/CampoContrasena.tsx frontend/src/design-system/Aviso.tsx \
  frontend/src/design-system/Dialogo.tsx frontend/src/design-system/Icono.tsx \
  frontend/src/design-system/EstadoPantalla.tsx frontend/src/design-system/primitivas.test.tsx \
  frontend/src/styles/shell.css frontend/src/styles/auth.css \
  frontend/src/styles/project-picker.css frontend/src/main.tsx \
  scripts/design-system-react-contract.mjs tests/design-system/react-shell-tokens.test.mjs \
  package.json
git commit -m "feat(shell): crear primitivas visuales AIA"
```

---

### Task 9: Cerrar la paridad del login

**Files:**
- Create: `frontend/src/shell/CambioClaveObligatorio.tsx`
- Create: `frontend/src/shell/CambioClaveObligatorio.test.tsx`
- Modify: `frontend/src/shell/PantallaLogin.tsx`
- Modify: `frontend/src/shell/PantallaLogin.test.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/styles/auth.css`

**Interfaces:**
- Login llama `/api/auth/login` y actúa según `next`.
- Cambio de clave llama los dos endpoints de Task 3.
- El enlace de recuperación sigue siendo `/password/forgot`.

- [ ] **Step 1: Expand the component tests first**

Casos:

1. muestra lockup, contexto AIA y formulario;
2. reason timeout/inactive produce aviso correcto; missing_session no;
3. muestra/oculta contraseña y vuelve a ocultarla al limpiar;
4. Enter envía una vez, deshabilita controles y muestra `Entrando…`;
5. 401 usa copy no enumerable;
6. 422 enlaza errores con campos y resumen;
7. `next=password_change` abre diálogo sin navegar a PHP;
8. política de clave se anuncia junto al campo y servidor sigue siendo autoridad;
9. cancelar limpia estado pendiente y devuelve foco;
10. éxito recarga bootstrap y muestra selector;
11. recuperación abre la ruta PHP completa;
12. tema se puede alternar por teclado.

- [ ] **Step 2: Run and see red**

```bash
npm --prefix frontend test -- \
  src/shell/PantallaLogin.test.tsx src/shell/CambioClaveObligatorio.test.tsx
```

- [ ] **Step 3: Implement the approved composition**

Escritorio: dos paneles, marca/contexto y formulario. Tablet reduce el panel de marca. Móvil usa una
columna sin esconder el nombre del producto. Los mensajes del servidor se muestran mediante
`Aviso`; los detalles técnicos solo viven en logs.

`CambioClaveObligatorio` usa `Dialogo`, dos `CampoContrasena` y acciones
explícitas Guardar/Salir. No cierra haciendo click en el backdrop; Escape ejecuta cancelación segura,
igual que el botón Salir.

- [ ] **Step 4: Run login and complete frontend gates**

```bash
npm --prefix frontend test -- \
  src/shell/PantallaLogin.test.tsx src/shell/CambioClaveObligatorio.test.tsx \
  src/shell/rutas.test.tsx
npm run frontend:typecheck
docker compose exec app php tests/test_api_auth_contract.php
```

- [ ] **Step 5: Update parity rows to implemented**

Cambiar solo las filas `auth.*` y estados de arranque ejercitados a
`implemented`, enlazando tests reales. No marcarlas verified todavía.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/shell/CambioClaveObligatorio.tsx \
  frontend/src/shell/CambioClaveObligatorio.test.tsx \
  frontend/src/shell/PantallaLogin.tsx frontend/src/shell/PantallaLogin.test.tsx \
  frontend/src/shell/rutas.tsx frontend/src/styles/auth.css \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md
git commit -m "feat(shell): completar paridad del login"
```

---

### Task 10: Cerrar la paridad del selector de proyecto

**Files:**
- Create: `frontend/src/shell/MenuCuenta.tsx`
- Create: `frontend/src/shell/MenuCuenta.test.tsx`
- Modify: `frontend/src/shell/SelectorProyecto.tsx`
- Modify: `frontend/src/shell/SelectorProyecto.test.tsx`
- Modify: `frontend/src/styles/project-picker.css`

**Interfaces:**
- Lista y selección usan los esquemas de `proyectos.ts`.
- Buscar es local sobre la lista ya autorizada.
- Salida usa `POST /api/auth/logout`; Control Tower usa href server-side.

- [ ] **Step 1: Write all selector states first**

Probar:

- identidad y menú de cuenta;
- logout y tema;
- Control Tower solo cuando bootstrap trae destino;
- carga, error + reintento, lista vacía y búsqueda sin resultados;
- búsqueda sin sensibilidad a mayúsculas/diacríticos;
- conteo inicial y filtrado mediante `aria-live=polite`;
- tarjeta con nombre, área, Activo y nombre del rol;
- selección envía `{id}`, ocupa solo la tarjeta elegida y deshabilita las demás;
- respuesta 404/409 muestra error recuperable sin revelar proyecto.

- [ ] **Step 2: Run and see red**

```bash
npm --prefix frontend test -- \
  src/shell/SelectorProyecto.test.tsx src/shell/MenuCuenta.test.tsx
```

- [ ] **Step 3: Implement behavior and responsive grid**

Usar `useMemo` para filtrar y `String.normalize('NFD')` para búsqueda. El count
visible dice “N proyectos disponibles” sin filtro y “N proyectos encontrados” con filtro.

CSS contractual:

- 1180: tres columnas;
- 768: dos columnas;
- 390: una columna;
- ningún botón depende del color para explicar área/estado;
- Construcción usa `--ds-active-domain-construction`; Pre-Construcción usa superficie/chip
  neutral.

- [ ] **Step 4: Run selector and API contracts**

```bash
npm --prefix frontend test -- \
  src/shell/SelectorProyecto.test.tsx src/shell/MenuCuenta.test.tsx
npm run frontend:typecheck
docker compose exec app php tests/test_api_projects_contract.php
```

- [ ] **Step 5: Update selector parity rows to implemented and commit**

```bash
git add frontend/src/shell/MenuCuenta.tsx frontend/src/shell/MenuCuenta.test.tsx \
  frontend/src/shell/SelectorProyecto.tsx frontend/src/shell/SelectorProyecto.test.tsx \
  frontend/src/styles/project-picker.css \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md
git commit -m "feat(shell): completar selector de proyectos"
```

---

### Task 11: Cerrar navegación, cuenta, drawer y contexto semanal

**Files:**
- Create: `frontend/src/shell/AppShell.tsx`
- Create: `frontend/src/shell/AppShell.test.tsx`
- Create: `frontend/src/shell/ContextoSemana.tsx`
- Create: `frontend/src/shell/ContextoSemana.test.tsx`
- Modify: `frontend/src/shell/NavegacionLateral.tsx`
- Modify: `frontend/src/shell/NavegacionLateral.test.tsx`
- Modify: `frontend/src/shell/ConmutadorTema.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `frontend/src/styles/shell.css`

**Interfaces:**
- Navegación recibe `navigation.groups`; no importa roles.
- Cuenta ejecuta `change_project` y `logout`.
- Semana usa los tres endpoints de Task 5 y refresca bootstrap después de mutar.

- [ ] **Step 1: Write interaction tests before components**

Probar:

- grupos/labels/href/current se renderizan exactamente desde el manifiesto;
- item sin href y action `weeks` abre contexto, no navega;
- cambiar proyecto hace DELETE, refresca y muestra selector;
- logout hace POST, descarta contenido y muestra login;
- semana actual, fechas y destinos están disponibles por teclado;
- cambio de semana valida éxito antes de navegar;
- crear muestra próxima semana, fecha sugerida, confirmación y estado busy;
- eliminar solo aparece para `max` y capacidad verdadera;
- 403 oculta/inhabilita tras refrescar; 409 conserva diálogo con mensaje;
- links de módulo causan navegación completa;
- no existe `ocultasPorRol`, `switch(role)` ni URL privilegiada local.

Drawer:

- abre desde trigger bajo 1180;
- mueve foco al primer control;
- Escape cierra, desbloquea body y devuelve foco;
- click en velo cierra;
- Tab no sale del drawer;
- al cruzar a 1180 se cierra y restablece body;
- escritorio persiste expandido/colapsado en `aia-shell-sidebar-collapsed`.

- [ ] **Step 2: Run and see red**

```bash
npm --prefix frontend test -- \
  src/shell/NavegacionLateral.test.tsx src/shell/ContextoSemana.test.tsx \
  src/shell/AppShell.test.tsx
```

- [ ] **Step 3: Implement the project shell**

`AppShell` compone topbar móvil, sidebar/drawer, context bar, cuenta, tema y un inicio
honesto con proyecto/semana y destinos autorizados. No inventa métricas del atlas. El atlas gobierna
jerarquía y composición, no datos falsos.

`NavegacionLateral` elimina `ocultasPorRol` y `grupos` locales. Iconos
solo traducen el identificador visual recibido. `ContextoSemana` no acepta project ID,
prefijo ni rol como props.

Usar `window.matchMedia('(min-width: 1180px)')` para el comportamiento y
`@media (max-width: 1179px)` para layout. El listener se desmonta en cleanup.

- [ ] **Step 4: Run full frontend gates**

```bash
npm run frontend:test
npm run frontend:typecheck
npm run frontend:build
npm run test:design-system:react-shell
```

Expected: cuatro RC 0 y bundle bajo `public/app` sin archivos fuente inesperados.

- [ ] **Step 5: Update nav/week/responsive/theme rows to implemented**

Adjuntar tests de componentes y contratos PHP. Conservar `verified` pendiente de navegador.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/shell/AppShell.tsx frontend/src/shell/AppShell.test.tsx \
  frontend/src/shell/ContextoSemana.tsx frontend/src/shell/ContextoSemana.test.tsx \
  frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/NavegacionLateral.test.tsx \
  frontend/src/shell/ConmutadorTema.tsx frontend/src/shell/rutas.tsx \
  frontend/src/shell/rutas.test.tsx frontend/src/styles/shell.css \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md
git commit -m "feat(shell): completar navegacion y contexto semanal"
```

---

### Task 12: Verificar en navegador, promover y conservar rollback

**Files:**
- Create: `src/Core/ShellRolloutPolicy.php`
- Create: `tests/test_shell_rollout_policy.php`
- Create: `tests/browser/support/temporary-shell-user.mjs`
- Create: `tests/browser/react-shell-parity.spec.mjs`
- Create: `tests/browser/react-shell-responsive.spec.mjs`
- Create: `tests/browser/react-shell-a11y.spec.mjs`
- Create: `tests/browser/react-shell-visual.spec.mjs`
- Create: `frontend/README.md`
- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `.github/workflows/ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `TASKS.md`
- Modify: `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json`
- Modify: `docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- `REACT_SHELL_ENABLED=true` promueve únicamente requests GET/HEAD de `/`, `/login` y
  `/proyectos` hacia `/app`.
- `false` conserva legacy sin desactivar RLS.
- `/password/forgot` y `/password/reset` nunca se redirigen.
- Los POST legacy de login y selección de proyecto siempre llegan a sus controladores PHP; el
  rollout no intercepta ni redirige mutaciones.

- [ ] **Step 1: Write rollout and browser scenarios**

`test_shell_rollout_policy.php` prueba matriz true/false, GET/HEAD, las excepciones de password y
que los POST de login/selección nunca se redirigen.

`temporary-shell-user.mjs` crea/elimina el fixture de Task 3 mediante PHP dentro de Docker.
Mantiene usuario/clave solo en memoria, no los imprime, no los adjunta y elimina en `finally`.

`react-shell-parity.spec.mjs` cubre con PHP real:

1. login normal;
2. cambio obligatorio y cancelación;
3. selector, búsqueda y proyecto por ID;
4. cambio de proyecto;
5. navegación autorizada y denegada para dos roles;
6. cambio, creación y eliminación de semana con snapshot/restauración de DB;
7. navegación a una ruta PHP y regreso a `/app`;
8. logout;
9. timeout simulado;
10. fallo recuperable de bootstrap.

- [ ] **Step 2: Add responsive, accessibility and visual matrices**

`react-shell-responsive.spec.mjs` prueba 390×844, 768×1024 y 1180×820: drawer/sidebar,
foco, Escape, scroll lock y
`document.documentElement.scrollWidth === document.documentElement.clientWidth`.

`react-shell-a11y.spec.mjs` usa axe y teclado en login, selector y shell, claro y oscuro.
Falla por violaciones serious/critical, controles sin nombre, foco invisible o targets por debajo
del contrato.

`react-shell-visual.spec.mjs` genera **18 goldens**:

- tres superficies: login, selector, shell;
- dos temas: light/dark;
- tres viewports: 390/768/1180.

Ocultar únicamente timestamps/correlaciones dinámicas mediante locators específicos. No usar
`maxDiffPixels` amplio ni enmascarar regiones de producto.

- [ ] **Step 3: Run functional browser gates before accepting snapshots**

```bash
npm run frontend:build
npx playwright test tests/browser/react-shell-parity.spec.mjs \
  tests/browser/react-shell-responsive.spec.mjs \
  tests/browser/react-shell-a11y.spec.mjs --workers=1
```

Expected: todos PASS, cero `console.error`, page error, 401 inesperado o request fallido.

- [ ] **Step 4: Generate and review the 18 visual baselines**

Primera generación:

```bash
npx playwright test tests/browser/react-shell-visual.spec.mjs --workers=1 --update-snapshots
```

Abrir el reporte y comparar cada cruce con el atlas. **Detenerse aquí para aprobación visual de
Felipe.** No aceptar automáticamente diferencias. Después de aprobación, ejecutar sin update:

```bash
npx playwright test tests/browser/react-shell-visual.spec.mjs --workers=1
```

- [ ] **Step 5: Verify coexistence and rollback**

```bash
docker compose exec -e REACT_SHELL_ENABLED=false app php tests/test_shell_rollout_policy.php
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Después repetir el policy test con `REACT_SHELL_ENABLED=true`. Confirmar que módulos PHP,
forgot/reset y rutas admin siguen intactos. Nunca cambiar `USE_GLOBAL_TABLES` ni el gate
RLS para probar rollback.

- [ ] **Step 6: Make CI and documentation authoritative**

CI ejecuta, en orden:

```yaml
- run: npm run test:shell-parity
- run: npm run frontend:typecheck
- run: npm run frontend:test
- run: npm run frontend:build
- run: docker compose exec -T app php tests/test_api_session_contract.php
- run: docker compose exec -T app php tests/test_api_auth_contract.php
- run: docker compose exec -T app php tests/test_api_projects_contract.php
- run: docker compose exec -T app php tests/test_shell_week_api_contract.php
- run: npx playwright test tests/browser/react-shell-parity.spec.mjs tests/browser/react-shell-responsive.spec.mjs tests/browser/react-shell-a11y.spec.mjs tests/browser/react-shell-visual.spec.mjs --workers=1
```

README/frontend README documentan desarrollo, build, contratos, transición por URL y rollback.
CHANGELOG registra la promoción. TASKS enlaza spec/planes/evidencia sin reescribir historia.

- [ ] **Step 7: Close the parity matrix with evidence**

Pasar filas a `verified` solo si todos sus archivos/evidencias existen y el gate específico
está verde. Mostrar la matriz y los 18 cruces a Felipe. Tras su aprobación explícita, cambiar las
filas a `accepted`, renderizar Markdown y correr:

```bash
npm run test:shell-parity
```

- [ ] **Step 8: Run the complete completion gate**

Cada comando por separado:

```bash
git diff --check
npm run test:shell-parity
npm run frontend:typecheck
npm run frontend:test
npm run frontend:build
npm run test:design-system:react-shell
npm run test:design-system:static
docker compose exec app php tests/test_api_session_contract.php
docker compose exec app php tests/test_api_auth_contract.php
docker compose exec app php tests/test_api_projects_contract.php
docker compose exec app php tests/test_shell_week_api_contract.php
docker compose exec app php tests/test_project_scope_http_isolation.php
npm run test:rbac-parity
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G --no-progress
npx playwright test tests/browser/react-shell-parity.spec.mjs \
  tests/browser/react-shell-responsive.spec.mjs \
  tests/browser/react-shell-a11y.spec.mjs \
  tests/browser/react-shell-visual.spec.mjs --workers=1
```

- [ ] **Step 9: Commit verified rollout artifacts**

```bash
git add src/Core/ShellRolloutPolicy.php tests/test_shell_rollout_policy.php \
  tests/browser/support/temporary-shell-user.mjs \
  tests/browser/react-shell-parity.spec.mjs tests/browser/react-shell-responsive.spec.mjs \
  tests/browser/react-shell-a11y.spec.mjs tests/browser/react-shell-visual.spec.mjs \
  tests/browser/__screenshots__/react-shell-visual.spec.mjs \
  src/Core/SpaRouter.php public/index.php \
  .github/workflows/ci.yml frontend/README.md README.md CHANGELOG.md TASKS.md \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.json \
  docs/superpowers/evidencia/2026-08-28-shell-react-parity-matrix.md
git commit -m "feat(shell): promover paridad React verificada"
```

## Completion Gate

El shell queda listo solo cuando:

- el plan RLS previo conserva todos sus gates verdes;
- las filas de la matriz están `accepted` y tienen evidencia real;
- React no contiene mapas de roles, project IDs ni URLs privilegiadas inventadas;
- los seis estados de arranque no filtran contenido antes de resolver sesión/proyecto;
- login, clave obligatoria, selector, navegación, semanas, cuenta, tema y logout pasan E2E;
- las rutas PHP, forgot/reset y rollback legacy siguen funcionando;
- los 18 goldens fueron aprobados y luego pasan sin update;
- claro/oscuro × 390/768/1180 pasan accesibilidad, foco y overflow;
- contratos PHP, Vitest, typecheck, build, RBAC, RLS y PHPStan están verdes;
- el árbol está limpio y no contiene claves, cookies, snapshots de DB ni artefactos temporales.
