# CSRF en endpoints legacy de mutación de semanas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exigir un token CSRF válido en las mutaciones autenticadas `nueva_semana.php` y `eliminar_semana.php`, distribuyendo el token a sus dos callers (shell nuevo y navbar legacy) sin romper ninguno.

**Architecture:** Un helper `legacy_require_csrf($formKey)` en `src/Legacy/rbac_guard.php` valida `$_POST['_csrf_token']` con `App\Security\CsrfTokenManager` (formKey único `lps_week_admin`, token estable por sesión). El token se emite server-side en dos puntos ya presentes en cada mundo: el JSON `#shellWeekMenusData` del partial `shell_sidebar.php` (para `shell_week_admin.js`) y el JSON `{data:…}` de `datosGeneralesPagina.php` (para `funcionesGenerales6.js`, vía `window.__lpsWeekCsrf`). `verificarCICActualizada.php` queda fuera por ser read-only.

**Tech Stack:** PHP 8.3 (endpoints legacy procedurales + `CsrfTokenManager`), JS vanilla (`shell_week_admin.js`, fetch), jQuery (`funcionesGenerales6.js`), Playwright para el probe runtime, tests PHP autoejecutables del repo.

## Global Constraints

- Todo comando PHP dentro del contenedor: `docker compose exec -T app php …`.
- La ruta del repo tiene un espacio: citar siempre `cd "/Volumes/Crucial X6/Developer/lps-aia"`.
- No construir SQL con datos de usuario; mantener prepared statements (no aplica aquí, pero regla vigente).
- CSRF obligatorio en mutaciones autenticadas (AGENTS.md §Seguridad).
- `formKey` canónico para este flujo: `lps_week_admin` (exacto, mismo string en generación y validación).
- Campo POST del token: `_csrf_token` (exacto, mismo nombre en ambos callers y en el helper).
- No tocar la lógica de negocio de `nueva_semana.php` / `eliminar_semana.php`: solo insertar la validación tras el guard RBAC.
- **Rama**: antes de cada commit `git branch --show-current`; si no es `main`, avisar al usuario y no cambiar de rama sin su OK.
- Al declarar "hecho": salida real de comandos de esta sesión (verification-before-completion).

## Datos de referencia (verificados en el código)

- `rbac_guard.php` ya expone `rbac_guard_bootstrap()` (carga `vendor/autoload.php`) y `rbac_guard_require_permission()`. El helper nuevo va al final del archivo; tras `rbac_guard_bootstrap()`, la clase `\App\Security\CsrfTokenManager` está autoloaded.
- `CsrfTokenManager::generate(string $formKey): string` y `::validate(?string $token, string $formKey): bool` (src/Security/CsrfTokenManager.php); el token se guarda en `$_SESSION['_csrf_tokens'][$formKey]` y es estable durante la sesión.
- `nueva_semana.php:29-30`: `require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';` seguido de `rbac_guard_require_permission('lps.semana.crear');`.
- `eliminar_semana.php:3-5`: `session_start();` → `require_once __DIR__ . '/rbac_guard.php';` → `rbac_guard_require_permission('lps.semana.eliminar');`.
- `shell_sidebar.php`: genera `#shellWeekMenusData` (script JSON) con `json_encode([... 'db', 'esAdmin', 'maxSemana', 'canCreate', 'canDelete', 'fechaSugerida', 'cicPath'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)`. `shell_week_admin.js` lo parsea en `data` y usa `data.db`, etc.
- `shell_week_admin.js`: `postForm(url, fields)` hace `new URLSearchParams(fields)`; hay dos `postForm` de mutación — nueva_semana (`{ f_inicio_sem, opcion: "nueva_sem" }`) y eliminar_semana (`{ semana, opcion: "eliminar_sem" }`) — y uno de verificarCIC (`{ db, semana }`, NO se toca).
- `funcionesGenerales6.js`: `nueva_sem` hace `$.ajax` a `nueva_semana.php` con `data: { f_inicio_sem, opcion }` (~L82-86); `eliminar_sem` hace `$.ajax` a `eliminar_semana.php` con `data: { semana, opcion }` (~L137-141). El `$.ajax` de `verificarCICActualizada.php` (~L66-69) NO se toca.
- `datosGeneralesPagina.php`: termina con `echo json_encode(["data" => $arreglo], JSON_UNESCAPED_UNICODE);`. `$arreglo` es el mapa de datos generales. Autoload disponible (usa `App\Security\RbacService`).
- `cargarDatosGeneralesPagina2.js:330-334`: `var responseData = json_info_global && json_info_global['data']; … datosGenerales = responseData;`. Punto donde exponer `window.__lpsWeekCsrf`.
- Probe `tests/browser/shell-week-admin.mjs`: intercepta `**/nueva_semana.php*` y `**/eliminar_semana.php*` con `page.route`, capturando `route.request().postData()`. Ahí se añaden asserts de `_csrf_token`.
- Test partial `tests/test_shell_sidebar_partial.php`: renderiza el partial y hace `str_contains` sobre el HTML.

---

### Task 1: Helper `legacy_require_csrf` + test del helper

**Files:**
- Modify: `src/Legacy/rbac_guard.php` (añadir función al final, antes del cierre EOF)
- Test: Create `tests/test_legacy_csrf_guard.php` (script autoejecutable, patrón `tests/test_*.php`)

**Interfaces:**
- Consumes: `App\Security\CsrfTokenManager::generate/validate`, `rbac_guard_bootstrap()`.
- Produces: función global `legacy_require_csrf(string $formKey, array $options = []): void` — si `$_POST['_csrf_token']` valida contra `$formKey` retorna; si no, responde `403` (o `$options['http_code']`) con JSON `{respuesta:'ERROR', success:false, mensaje:'Token de seguridad inválido. Recargue la página e intente de nuevo.'}` y `exit`. Reutiliza `rbac_guard_bootstrap()` para garantizar autoload.

- [ ] **Step 1: Escribir el test que falla** — crear `tests/test_legacy_csrf_guard.php`:

```php
<?php

// Contrato del guard CSRF legacy: valida $_POST['_csrf_token'] contra un formKey.
// El helper hace exit tras responder; se ejecuta cada caso en un subproceso PHP.
define('PROJECT_ROOT', dirname(__DIR__));

$php = PHP_BINARY;
$guard = PROJECT_ROOT . '/src/Legacy/rbac_guard.php';

// Runner: genera un token, arma un escenario y captura salida + exit code.
$scenario = <<<'PHP'
define('PROJECT_ROOT', %s);
require PROJECT_ROOT . '/vendor/autoload.php';
require PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$valid = \App\Security\CsrfTokenManager::generate('lps_week_admin');
$_POST['_csrf_token'] = %s === '__VALID__' ? $valid : %s;
legacy_require_csrf('lps_week_admin');
echo 'PASSED_GUARD';
PHP;

function runScenario(string $php, string $scenarioTpl, string $tokenExpr): array
{
    $code = sprintf($scenarioTpl, var_export(PROJECT_ROOT, true), $tokenExpr, $tokenExpr);
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($php . ' -r ' . escapeshellarg($code), $descriptors, $pipes);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($proc);
    return ['out' => $out, 'exit' => $exit];
}

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) { $fails++; }
};

$valid = runScenario($php, $scenario, "'__VALID__'");
$check(str_contains($valid['out'], 'PASSED_GUARD'), 'token válido pasa el guard');

$empty = runScenario($php, $scenario, "''");
$check(!str_contains($empty['out'], 'PASSED_GUARD'), 'token vacío no pasa');
$check(str_contains($empty['out'], 'Token de seguridad'), 'token vacío responde mensaje CSRF');

$wrong = runScenario($php, $scenario, "'deadbeef'");
$check(!str_contains($wrong['out'], 'PASSED_GUARD'), 'token incorrecto no pasa');

echo $fails === 0 ? "Legacy CSRF guard: PASS\n" : "Legacy CSRF guard: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
```

- [ ] **Step 2: Verificar que falla**

Run: `cd "/Volumes/Crucial X6/Developer/lps-aia" && docker compose exec -T app php tests/test_legacy_csrf_guard.php`
Expected: FAIL (o error fatal `Call to undefined function legacy_require_csrf()` en el subproceso → asserts en FAIL).

- [ ] **Step 3: Implementar el helper** — en `src/Legacy/rbac_guard.php`, tras el cierre de la función `rbac_guard_require_permission` (última `}` del archivo), añadir:

```php

if (!function_exists('legacy_require_csrf')) {
    function legacy_require_csrf(string $formKey, array $options = []): void
    {
        rbac_guard_bootstrap();

        $token = $_POST['_csrf_token'] ?? null;
        if (is_string($token) && \App\Security\CsrfTokenManager::validate($token, $formKey)) {
            return;
        }

        http_response_code((int) ($options['http_code'] ?? 403));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'respuesta' => 'ERROR',
            'success' => false,
            'mensaje' => $options['message'] ?? 'Token de seguridad inválido. Recargue la página e intente de nuevo.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
```

- [ ] **Step 4: Verificar que pasa**

Run: `docker compose exec -T app php tests/test_legacy_csrf_guard.php`
Expected: `Legacy CSRF guard: PASS`
Run: `docker compose exec -T app php -l src/Legacy/rbac_guard.php` → sin errores.

- [ ] **Step 5: Commit**

```bash
git branch --show-current   # confirmar rama con el usuario si no es main
git add src/Legacy/rbac_guard.php tests/test_legacy_csrf_guard.php
git commit -m "feat(security): helper legacy_require_csrf para mutaciones de semanas

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Exigir CSRF en `nueva_semana.php` y `eliminar_semana.php`

**Files:**
- Modify: `src/Legacy/nueva_semana.php` (tras L30)
- Modify: `src/Legacy/eliminar_semana.php` (tras L5)

**Interfaces:**
- Consumes: `legacy_require_csrf('lps_week_admin')` (Task 1).
- Produces: ambos endpoints rechazan con 403 JSON cualquier POST sin `_csrf_token` válido, antes de mutar. Sin cambios de shape en la respuesta de éxito.

- [ ] **Step 1: Insertar la validación en `nueva_semana.php`** — tras la línea `rbac_guard_require_permission('lps.semana.crear');` (L30), añadir en la línea siguiente:

```php
legacy_require_csrf('lps_week_admin');
```

- [ ] **Step 2: Insertar la validación en `eliminar_semana.php`** — tras `rbac_guard_require_permission('lps.semana.eliminar');` (L5), añadir en la línea siguiente:

```php
legacy_require_csrf('lps_week_admin');
```

- [ ] **Step 3: Verificar sintaxis**

Run: `docker compose exec -T app php -l src/Legacy/nueva_semana.php`
Run: `docker compose exec -T app php -l src/Legacy/eliminar_semana.php`
Expected: `No syntax errors detected` en ambos.

- [ ] **Step 4: Commit**

```bash
git add src/Legacy/nueva_semana.php src/Legacy/eliminar_semana.php
git commit -m "feat(security): exige CSRF en nueva_semana y eliminar_semana

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Emitir el token en el shell y enviarlo desde `shell_week_admin.js`

**Files:**
- Modify: `views/partials/shell_sidebar.php` (generar token + campo JSON)
- Modify: `public/js/modules/aia_ui/shell_week_admin.js` (enviar `_csrf_token` en los dos postForm de mutación)
- Test: `tests/test_shell_sidebar_partial.php` (assert del campo `csrfToken` en el JSON)

**Interfaces:**
- Consumes: `App\Security\CsrfTokenManager::generate('lps_week_admin')`; el JSON `#shellWeekMenusData` y `postForm` existentes.
- Produces: `#shellWeekMenusData` incluye `"csrfToken":"<hex>"`; `shell_week_admin.js` envía `_csrf_token: data.csrfToken` en crear y eliminar (no en verificarCIC).

- [ ] **Step 1: Assert nuevo en el test del partial (falla)** — en `tests/test_shell_sidebar_partial.php`, tras la línea `$check(str_contains($admin, '"esAdmin":true'), 'A: JSON esAdmin');`, añadir:

```php
$check((bool) preg_match('/"csrfToken":"[a-f0-9]{64}"/', $admin), 'A: JSON incluye csrfToken de 64 hex');
```

- [ ] **Step 2: Verificar que falla**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
Expected: FAIL en `A: JSON incluye csrfToken de 64 hex`.

- [ ] **Step 3: Generar el token en el partial** — en `views/partials/shell_sidebar.php`, tras la línea `$shellDb = (string) ($_SESSION['db'] ?? '');` (bloque de resolución de permisos/máximos), añadir:

```php
$shellWeekCsrf = \App\Security\CsrfTokenManager::generate('lps_week_admin');
```

- [ ] **Step 4: Añadir el campo al JSON** — en el mismo archivo, dentro del `json_encode` de `#shellWeekMenusData`, tras la línea `'cicPath' => '/programacion-semanal/cic',`, añadir:

```php
'csrfToken' => $shellWeekCsrf,
```

- [ ] **Step 5: Enviar el token desde `shell_week_admin.js`** — en `public/js/modules/aia_ui/shell_week_admin.js`:

(a) En el `postForm` de creación (objeto `{ f_inicio_sem…, opcion: "nueva_sem" }`), añadir la clave `_csrf_token`:

```js
            return postForm(`/legacy/funciones_generales/php/nueva_semana.php?db=${encodeURIComponent(data.db)}`, {
              f_inicio_sem: dateInput ? dateInput.value : "",
              opcion: "nueva_sem",
              _csrf_token: data.csrfToken,
            }).then((info) => {
```

(b) En el `postForm` de eliminación (objeto `{ semana…, opcion: "eliminar_sem" }`), añadir la clave:

```js
        postForm(`/legacy/funciones_generales/php/eliminar_semana.php?db=${encodeURIComponent(data.db)}`, {
          semana: String(deleteWeek),
          opcion: "eliminar_sem",
          _csrf_token: data.csrfToken,
        })
```

(El `postForm` de `verificarCICActualizada.php` NO cambia.)

- [ ] **Step 6: Verificar que pasa + gates**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → `Shell sidebar partial: PASS`
Run: `docker compose exec -T app php -l views/partials/shell_sidebar.php` → sin errores.
Run: `node --check public/js/modules/aia_ui/shell_week_admin.js` → sin salida.
Run: `npx biome check public/js/modules/aia_ui/shell_week_admin.js` → limpio (si marca template-literals, `npx biome check --write --unsafe` y re-verificar).

- [ ] **Step 7: Commit**

```bash
git add views/partials/shell_sidebar.php public/js/modules/aia_ui/shell_week_admin.js tests/test_shell_sidebar_partial.php
git commit -m "feat(security): shell emite y envía CSRF en crear/eliminar semana

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Distribuir el token al navbar legacy (`datosGeneralesPagina.php` → `funcionesGenerales6.js`)

**Files:**
- Modify: `src/Legacy/datosGeneralesPagina.php` (añadir token al `$arreglo`)
- Modify: `public/js/cargarDatosGeneralesPagina2.js` (exponer `window.__lpsWeekCsrf`)
- Modify: `public/js/funcionesGenerales6.js` (enviar `_csrf_token` en los dos `$.ajax` de mutación)

**Interfaces:**
- Consumes: `App\Security\CsrfTokenManager::generate('lps_week_admin')`; el JSON `{data:…}` y `responseData` existentes.
- Produces: `datosGeneralesPagina.php` retorna `data.weekCsrfToken`; `cargarDatosGeneralesPagina2.js` fija `window.__lpsWeekCsrf`; `nueva_sem`/`eliminar_sem` en `funcionesGenerales6.js` envían `_csrf_token: window.__lpsWeekCsrf || ''`.

- [ ] **Step 1: Emitir el token en `datosGeneralesPagina.php`** — antes de la línea final `echo json_encode(["data" => $arreglo], JSON_UNESCAPED_UNICODE);`, añadir:

```php
    $arreglo["weekCsrfToken"] = \App\Security\CsrfTokenManager::generate('lps_week_admin');
```

(Indentación: dentro del `try`, mismo nivel que las asignaciones previas a `$arreglo`.)

- [ ] **Step 2: Verificar sintaxis PHP**

Run: `docker compose exec -T app php -l src/Legacy/datosGeneralesPagina.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Exponer el token en `cargarDatosGeneralesPagina2.js`** — en el bloque `success`, tras la línea `datosGenerales = responseData;` (≈L334), añadir:

```js
      window.__lpsWeekCsrf = responseData.weekCsrfToken || '';
```

- [ ] **Step 4: Enviar el token en `funcionesGenerales6.js` (crear)** — en `nueva_sem`, el segundo `$.ajax` (POST a `nueva_semana.php`), cambiar su `data`:

```js
          data: { f_inicio_sem: f_inicio_sem, opcion: opcion, _csrf_token: window.__lpsWeekCsrf || '' },
```

- [ ] **Step 5: Enviar el token en `funcionesGenerales6.js` (eliminar)** — en `eliminar_sem`, el `$.ajax` (POST a `eliminar_semana.php`), cambiar su `data`:

```js
      data: { semana: semana, opcion: opcion, _csrf_token: window.__lpsWeekCsrf || '' },
```

(El `$.ajax` a `verificarCICActualizada.php` NO cambia.)

- [ ] **Step 6: Gates de sintaxis JS**

Run: `node --check public/js/cargarDatosGeneralesPagina2.js` → sin salida.
Run: `node --check public/js/funcionesGenerales6.js` → sin salida.
Run: `npx biome check public/js/cargarDatosGeneralesPagina2.js public/js/funcionesGenerales6.js` → sin nuevos errores (estos archivos legacy pueden tener reds preexistentes; comparar contra `git stash` si aparece alguno para confirmar que no lo introduce este cambio).

- [ ] **Step 7: Commit**

```bash
git add src/Legacy/datosGeneralesPagina.php public/js/cargarDatosGeneralesPagina2.js public/js/funcionesGenerales6.js
git commit -m "feat(security): distribuye CSRF de semanas al navbar legacy

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Actualizar el probe runtime para exigir y enviar el token

**Files:**
- Modify: `tests/browser/shell-week-admin.mjs` (asserts de `_csrf_token` en los bodies interceptados)

**Interfaces:**
- Consumes: la vista `/programacion-intermedia` con Tasks 1-3 aplicadas; los `page.route` existentes que capturan `postData()`.
- Produces: el probe verifica que crear y eliminar envían un `_csrf_token` no vacío en el body.

- [ ] **Step 1: Assert de token en crear** — en `tests/browser/shell-week-admin.mjs`, en el `check('crear: pre-check CIC + POST + redirect a PG semana nueva', …)`, ampliar la condición para exigir el token. Reemplazar ese bloque `check(...)` por:

```js
check('crear: pre-check CIC + POST + redirect a PG semana nueva',
  calls.cic.length === 1 && calls.crear.length === 1
    && calls.crear[0].body.includes('opcion=nueva_sem')
    && /(?:^|&)_csrf_token=[a-f0-9]{64}(?:&|$)/.test(calls.crear[0].body)
    && afterCreate.some((c) => c.week === 5 && c.path === '/programa-general'),
  JSON.stringify({ cic: calls.cic.length, crear: calls.crear.length, redirects: afterCreate }));
```

- [ ] **Step 2: Assert de token en eliminar** — reemplazar el `check('eliminar: POST correcto + redirect a semana-1', …)` por:

```js
check('eliminar: POST correcto + redirect a semana-1',
  calls.eliminar.length === 1
    && calls.eliminar[0].body.includes('opcion=eliminar_sem')
    && /(?:^|&)_csrf_token=[a-f0-9]{64}(?:&|$)/.test(calls.eliminar[0].body)
    && afterDelete.some((c) => c.week === maxSemana - 1),
  JSON.stringify({ eliminar: calls.eliminar.length, redirects: afterDelete }));
```

- [ ] **Step 3: Ejecutar el probe**

Run: `cd "/Volumes/Crucial X6/Developer/lps-aia" && node tests/browser/shell-week-admin.mjs`
Expected: `7/7 checks OK` (si `_csrf_token` sale vacío, el defecto está en Task 3, no en el test).

- [ ] **Step 4: Commit**

```bash
git add tests/browser/shell-week-admin.mjs
git commit -m "test(security): el probe exige _csrf_token en crear/eliminar semana

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 6: Validación en navegador y cierre

**Files:** ninguno nuevo (evidencia; ajustes menores solo si el flujo real lo exige)

- [ ] **Step 1: Validación runtime del rechazo y del éxito** — con el stack Docker arriba, en el navegador integrado a 1180×820 dark, autenticado, en `/programacion-intermedia`:
  - Confirmar que el flujo shell de crear/eliminar sigue funcionando (los diálogos operan; los endpoints ya no rechazan porque el token viaja). Interceptar no es necesario aquí; basta observar que no aparece el error "Token de seguridad inválido".
  - Prueba negativa dirigida (consola del navegador): `fetch('/legacy/funciones_generales/php/eliminar_semana.php?db='+encodeURIComponent(JSON.parse(document.getElementById('shellWeekMenusData').textContent).db), {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'semana=1&opcion=eliminar_sem'}).then(r=>r.status)` → debe devolver `403` (sin `_csrf_token`). **No** ejecutar la variante con token válido (mutaría la BD).
  - Revisar consola: sin errores nuevos.
- [ ] **Step 2: Suite de regresión enfocada**

Run: `docker compose exec -T app php tests/test_legacy_csrf_guard.php` → PASS.
Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → PASS.
Run: `node tests/browser/shell-week-admin.mjs` → 7/7.
Run: `node tests/test_foundation_shell_contract.mjs` → exit 0.

- [ ] **Step 3: Reporte al usuario** — qué se verificó (comandos + salidas), el 403 en la prueba negativa, la rama actual del worktree, y que `verificarCICActualizada.php` queda sin CSRF por ser read-only (decisión de diseño, no omisión).

---

## Notas de diseño

- **`verificarCICActualizada.php` sin CSRF**: es un pre-check read-only que no muta estado; CSRF protege mutaciones. Incluirlo obligaría a enviar el token en un tercer punto sin beneficio de seguridad. Si en el futuro pasara a mutar, el token ya está distribuido y bastaría añadir `legacy_require_csrf('lps_week_admin')`.
- **Token estable por sesión**: `CsrfTokenManager::generate` devuelve el mismo valor para `lps_week_admin` durante toda la sesión, por eso emitirlo en dos puntos (shell y datosGenerales) es consistente y no genera desincronización.
- **Fallback seguro en callers**: ambos callers envían `''` si el token no llegó; el servidor entonces responde 403 en lugar de mutar — falla cerrada, no abierta.
