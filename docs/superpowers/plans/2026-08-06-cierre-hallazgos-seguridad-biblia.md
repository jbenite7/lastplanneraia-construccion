---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-06
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia.md
resumen: Hacer reales en servidor las cuatro reglas de seguridad de la biblia: CSRF en 6 módulos, CSRF en sanear, candado de semana pasada en Programa General, y 403 de…
---

# Cierre de los hallazgos de seguridad de la biblia — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hacer reales en servidor las cuatro reglas de seguridad de la biblia: CSRF en 6 módulos, CSRF en `sanear`, candado de semana pasada en Programa General, y 403 de servidor en `/indicadores`.

**Architecture:** Reutilizar las piezas existentes: `legacy_require_csrf()` para validar, `CsrfTokenManager::generate()` + `<meta name="csrf-token">` para entregar el token (patrón ya vivo en Programación Semanal), `RbacManager` para la capacidad `canEditPastGeneralProgram`, y el 403 de `BiViewController` como plantilla para `/indicadores`. Tests HTTP autoejecutables contra el contenedor usando la dev door.

**Tech Stack:** PHP 8.3 en Docker (`docker compose exec app`), tests `tests/test_*.php` sin runner, jQuery/fetch en frontend.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design.md`.
- Todo PHP corre dentro del contenedor `app`; la app vive en `http://localhost:8081`.
- Sesión de prueba SIEMPRE por dev door: `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E` (roles: `test.A`=A, `test.R`=R, `test.V`=V). Nunca por `/login`.
- Los tests HTTP asumen `DEV_DOOR=1` en `.env`; si la puerta responde 302 a `/login` o 404, el test debe abortar con mensaje claro, no dar falso verde.
- No tocar `src/Legacy/` salvo lo especificado. No refactors adyacentes.
- Cada rol denegado se prueba junto a un rol permitido (AGENTS.md §RBAC).
- Commits atómicos por tarea; no push hasta el final (el usuario autoriza).

---

### Task 1: Guard CSRF de servidor en los 6 módulos + test HTTP paramétrico

**Files:**
- Modify: `src/Controllers/Api/SubcontratistasApiController.php:68` (método `save()`)
- Modify: `src/Controllers/Api/ProfesionalesApiController.php:110` (`save()`)
- Modify: `src/Controllers/Api/ControlCambiosApiController.php:59` (`save()`)
- Modify: `src/Controllers/Api/CicApiController.php:90` (`save()`)
- Modify: `src/Controllers/Api/CncApiController.php:46` (`save()`)
- Modify: `src/Controllers/Api/CnpApiController.php:46,87` (`save()` y `reprogramar()`)
- Test: `tests/test_csrf_modulos_api.php` (nuevo)

**Interfaces:**
- Consumes: `legacy_require_csrf(string $formKey)` de `src/Legacy/rbac_guard.php:83` (403 JSON y `exit` implícito vía `return` del caller — verificar: si NO corta la ejecución, envolver en `if` como hace el propio helper con `http_response_code`).
- Produces: formKeys canónicos que Task 2 emite en las vistas: `subcontratistas`, `profesionales`, `control-cambios`, `cic`, `cnc`, `cnp`.

- [ ] **Step 1: Leer `legacy_require_csrf` completo** (`sed -n '83,105p' src/Legacy/rbac_guard.php`) y confirmar si tras el 403 hace `exit`/`die` o retorna. Si retorna, el patrón de uso en cada controlador debe ser `legacy_require_csrf('<key>'); if (http_response_code() === 403) { return; }` — elegir el patrón según lo que haga el helper y usarlo idéntico en los 6.

- [ ] **Step 2: Escribir el test que falla** — `tests/test_csrf_modulos_api.php`:

```php
<?php
declare(strict_types=1);
// Verifica que las mutaciones de los 6 módulos exijan token CSRF (biblia, EXPERIMENTS.md fila 24).
// HTTP real contra el contenedor, sesión por dev door. Requiere DEV_DOOR=1.

const BASE = 'http://localhost:8081';
const PROYECTO = 'PDC Sandbox E2E';

function sesion(string $usuario): string {
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    [$code] = curlReq($url, null, $jar);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

// [ruta de mutación, página que emite la meta, payload mínimo sin token]
$casos = [
    ['/api/subcontratistas/save', '/subcontratistas', ['accion' => 'nada']],
    ['/api/profesionales/save', '/profesionales', ['accion' => 'nada']],
    ['/api/control-cambios/save', '/control-cambios', ['accion' => 'nada']],
    ['/api/cic/save', '/CIC', ['accion' => 'nada']],
    ['/api/cnc/save', '/CNC', ['accion' => 'nada']],
    ['/api/cnp/save', '/CNP', ['accion' => 'nada']],
    ['/api/cnp/reprogramar', '/CNP', ['accion' => 'nada']],
];
// NOTA: confirmar las rutas de página exactas en public/index.php (Step 3) y corregir aquí.

$fallos = 0; $total = 0;
$jar = sesion('test.A'); // rol con permiso de edición: si 403, es CSRF, no RBAC

foreach ($casos as [$api, $pagina, $payload]) {
    $total++;
    [$code] = curlReq(BASE . $api, $payload, $jar);
    if ($code !== 403) { $fallos++; echo "FALLO sin token: $api devolvió $code (esperaba 403)\n"; }

    $total++;
    [, $html] = curlReq(BASE . $pagina, null, $jar);
    if (!preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/', $html, $m)) {
        $fallos++; echo "FALLO meta: $pagina no emite csrf-token\n"; continue;
    }
    [$code2, $body2] = curlReq(BASE . $api, $payload + ['_csrf_token' => $m[1]], $jar);
    if ($code2 === 403 && str_contains($body2, 'CSRF')) {
        $fallos++; echo "FALLO con token: $api rechazó un token válido\n";
    }
}

echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 3: Confirmar rutas de página** de los 6 módulos en `public/index.php` (grep `subcontratistas|profesionales|control-cambios|CIC|CNC|CNP` sobre los `$router->get`) y corregir la tabla `$casos` del test si difieren.

- [ ] **Step 4: Correr el test y verificar que falla** — `docker compose exec app php tests/test_csrf_modulos_api.php`. Esperado: FALLO en los 7 casos "sin token" (hoy devuelven 200/400, no 403) y en las 6 metas (no existen aún).

- [ ] **Step 5: Añadir el guard en los 6 controladores.** Patrón idéntico en cada método de escritura, justo después del `rbac_guard_require_permission(...)`. Ejemplo en `SubcontratistasApiController::save()`:

```php
rbac_guard_require_permission('lps.subcontratistas.editar');
legacy_require_csrf('subcontratistas');
```

(ajustado al patrón que dictó el Step 1 si el helper no corta la ejecución). FormKeys: `subcontratistas`, `profesionales`, `control-cambios`, `cic`, `cnc`, `cnp` — `CnpApiController::reprogramar()` usa `cnp` igual que `save()`.

- [ ] **Step 6: Correr el test de nuevo.** Esperado: pasan los 7 "sin token" (403); las metas siguen fallando (son de Task 2). Anotarlo así en la salida del paso.

- [ ] **Step 7: Commit**

```bash
git add src/Controllers/Api/{Subcontratistas,Profesionales,ControlCambios,Cic,Cnc,Cnp}ApiController.php tests/test_csrf_modulos_api.php
git commit -m "fix(seguridad): las 6 APIs de módulos exigen token CSRF en cada mutación"
```

---

### Task 2: Entrega del token — meta en 6 vistas + helper JS + adjuntar en llamadas

**Files:**
- Modify: `views/subcontratistas/subcontratistas.view.php`, `views/profesionales/profesionales.view.php`, `views/control-cambios/controlCambios.view.php`, `views/programacion-semanal/CIC.view.php`, `views/programacion-semanal/CNC.view.php`, `views/programacion-semanal/CNP.view.php` (meta + script del helper)
- Modify: los controladores de vista que renderizan esas 6 páginas (pasar `$csrfToken`; localizarlos vía las rutas GET de `public/index.php:142-152` y hermanas)
- Create: `public/js/modules/aia_ui/csrf.js`
- Modify: los JS que escriben a esas APIs — censo en Step 1 (conocidos: `public/js/modules/programacion_intermedia/hot.js`, `public/js/modules/programacion_semanal/legacyCards.js`, `public/js/modules/programacion_semanal/hot.js`, más el JS inline de las 6 vistas)

**Interfaces:**
- Consumes: formKeys de Task 1; patrón meta de `views/programacion-semanal/programacion_semanal.view.php:34`.
- Produces: `window.aiaCsrfToken(): string` (lee la meta; cadena vacía si no hay).

- [ ] **Step 1: Censo de llamadas de escritura.** Por cada API, listar todos los callers y anotar el archivo:línea en el commit message:

```bash
grep -rn "api/subcontratistas/save\|api/profesionales/save\|api/control-cambios/save\|api/cic/save\|api/cnc/save\|api/cnp/save\|api/cnp/reprogramar" public/js views | grep -v "\.md"
```

- [ ] **Step 2: Crear el helper** `public/js/modules/aia_ui/csrf.js`:

```js
// Token CSRF de la página: lo emite la vista en <meta name="csrf-token">.
(function () {
  'use strict';
  window.aiaCsrfToken = function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return (meta && meta.getAttribute('content')) || '';
  };
})();
```

- [ ] **Step 3: Emitir la meta en las 6 vistas.** En el `<head>` de cada vista (mismo patrón que PS):

```php
<meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
```

y en el controlador de vista correspondiente (p. ej. `SubcontratistasController::index()`):

```php
$csrfToken = \App\Security\CsrfTokenManager::generate('subcontratistas');
```

(formKey del módulo de Task 1; verificar cómo cada controlador pasa variables a su vista — `compact`, `extract` o asignación directa — y seguir su patrón). Añadir también `<script src="/public/js/modules/aia_ui/csrf.js"></script>` antes del primer script que escriba.

- [ ] **Step 4: Adjuntar `_csrf_token` en cada caller del censo.** Patrón según estilo de la llamada:

```js
// jQuery: data: { ...campos, _csrf_token: window.aiaCsrfToken() }
// fetch + FormData: formData.append('_csrf_token', window.aiaCsrfToken());
// fetch + URLSearchParams: params.set('_csrf_token', window.aiaCsrfToken());
```

- [ ] **Step 5: Correr el test completo de Task 1** — `docker compose exec app php tests/test_csrf_modulos_api.php`. Esperado: `OK` (las 13 aserciones: 7 sin token → 403, 6 metas presentes y token aceptado).

- [ ] **Step 6: Lint frontend** — `npm run check:frontend`. Esperado: sin errores nuevos.

- [ ] **Step 7: Commit**

```bash
git add public/js/modules/aia_ui/csrf.js views/ public/js/modules/ src/Controllers/
git commit -m "feat(seguridad): las 6 vistas emiten token CSRF y sus llamadas de escritura lo adjuntan"
```

---

### Task 3: `sanear` exige token

**Files:**
- Modify: `src/Controllers/Api/SemanalApiController.php:128` (lista de opciones con CSRF)
- Test: `tests/test_semanal_sanear_csrf.php` (nuevo)

**Interfaces:**
- Consumes: el frontend ya envía `_csrf_token` en `sanear` (`public/js/modules/programacion_semanal/hot.js:2082`, verificado en el grilleo) con formKey `semanal_save`.

- [ ] **Step 1: Escribir el test que falla** — `tests/test_semanal_sanear_csrf.php` (reusa las funciones `sesion`/`curlReq` copiándolas del test de Task 1; scripts autoejecutables no comparten helpers):

```php
<?php
declare(strict_types=1);
// `sanear` ejecuta DELETE+INSERT y debe exigir CSRF (EXPERIMENTS.md fila 25).
// ... (copiar aquí sesion() y curlReq() de tests/test_csrf_modulos_api.php, con BASE y PROYECTO) ...

$jar = sesion('test.A');
$fallos = 0;
// La ruta de save de PS: confirmarla en public/index.php (grep SemanalApiController).
[$code, $body] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), ['opcion' => 'sanear', 'semana' => '1'], $jar);
if ($code !== 403) { $fallos++; echo "FALLO: sanear sin token devolvió $code (esperaba 403)\n"; }
echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
```

(`DB_PREFIX`: obtenerlo igual que lo hace el frontend — al entrar por la dev door la sesión ya carga el proyecto; si el endpoint toma `db` de sesión cuando falta el parámetro, omitirlo. Confirmar en `SemanalApiController::save():124` y ajustar.)

- [ ] **Step 2: Correr y verificar que falla** — `docker compose exec app php tests/test_semanal_sanear_csrf.php`. Esperado: FALLO (hoy `sanear` esquiva la validación).

- [ ] **Step 3: Fix de una línea** — en `SemanalApiController.php:128` añadir `'sanear'` a la lista:

```php
if (in_array($opcion, ['nuevo', 'modificar', 'eliminar', 'duplicar', 'autoprogramar', 'bloquear_compromisos', 'importar_actividad_no_requerida', 'EstadoEjecucion', 'tnp', 'sanear'], true)) {
```

- [ ] **Step 4: Correr y verificar que pasa.** Esperado: `OK`.

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/Api/SemanalApiController.php tests/test_semanal_sanear_csrf.php
git commit -m "fix(seguridad): sanear exige token CSRF como el resto de mutaciones de PS"
```

---

### Task 4: Candado de semana pasada en Programa General

**Files:**
- Modify: `src/Controllers/Api/GeneralApiController.php` — `update():136`, `updateBatch():379`, `deleteUpdate():1172` + helper privado nuevo
- Test: `tests/test_pg_pasado_servidor.php` (nuevo)

**Interfaces:**
- Consumes: `RbacService->resolveCurrentRole()`, `RbacManager::getCapabilities($role)['canEditPastGeneralProgram']` (A y D, `RbacManager:37`), consulta de semana máxima al estilo `LpsWeekEditPolicy:27-31`.
- Produces: helper privado `assertNotPastWeekOrPrivileged(int $semana, string $dbPrefix, int $projectId): bool` — devuelve `false` y emite el 403 JSON si la semana es pasada y el rol no puede.

- [ ] **Step 1: Escribir el test que falla** — `tests/test_pg_pasado_servidor.php` (copiar `sesion`/`curlReq`):

```php
<?php
declare(strict_types=1);
// «Editar el pasado del PG» debe existir en servidor, no solo como capacidad declarada
// (EXPERIMENTS.md fila 26). Rol permitido: A. Rol denegado: R.
// ... sesion() y curlReq() ...

$fallos = 0;
// 1. Con test.A, obtener una actividad y la semana máxima vía /api/general/list.
$jarA = sesion('test.A');
[, $lista] = curlReq(BASE . '/api/general/list', [], $jarA);
$datos = json_decode($lista, true);
// Extraer: $uniqueId de la primera fila no-capítulo, y $semanaMax del payload o de una
// segunda llamada; si la respuesta no trae semanas, usar semana=1 como pasada solo si
// la semana activa del proyecto sembrado es >1 (verificar contra el fixture del sandbox
// y ABORTAR con exit(2) y mensaje si el proyecto solo tiene una semana).

// 2. test.R sobre semana pasada → 403
$jarR = sesion('test.R');
[$code] = curlReq(BASE . '/api/general/update?semana_objetivo=1', ['unique_id' => $uniqueId, 'Actividad' => 'x'], $jarR);
if ($code !== 403) { $fallos++; echo "FALLO: R editó semana pasada ($code)\n"; }

// 3. test.A sobre semana pasada → NO 403 por el candado (400 de validación es aceptable)
[$codeA, $bodyA] = curlReq(BASE . '/api/general/update?semana_objetivo=1', ['unique_id' => $uniqueId, 'Actividad' => 'x'], $jarA);
if ($codeA === 403 && str_contains($bodyA, 'pasado')) { $fallos++; echo "FALLO: A bloqueado en semana pasada\n"; }

// 4. test.R sobre la semana vigente → NO 403 por el candado
[$codeV, $bodyV] = curlReq(BASE . '/api/general/update?semana_objetivo=' . $semanaMax, ['unique_id' => $uniqueId, 'Actividad' => 'x'], $jarR);
if ($codeV === 403 && str_contains($bodyV, 'pasado')) { $fallos++; echo "FALLO: R bloqueado en semana vigente\n"; }

echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
```

Antes de darlo por escrito: inspeccionar la respuesta real de `/api/general/list` en el sandbox (una llamada manual con curl) y dejar la extracción de `$uniqueId`/`$semanaMax` con las claves reales del JSON — sin suposiciones. El PG exige además su propio CSRF (`requireProgramaGeneralCsrf`): obtener ese token como lo hace el frontend del PG (inspeccionar `public/js/modules/programa_general/hot.js`, buscar `_csrf_token`) e incluirlo en los POST del test para que el 403 medido sea el del candado de semana, no el de CSRF.

- [ ] **Step 2: Correr y verificar que falla.** Esperado: FALLO en el caso 2 (hoy R puede editar el pasado).

- [ ] **Step 3: Implementar el helper y llamarlo en los 3 endpoints.** En `GeneralApiController`:

```php
private function assertNotPastWeekOrPrivileged(int $semana, string $dbPrefix, int $projectId): bool
{
    $weeksTable = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
    $maxWeek = (int) $this->db->queryWithProject(
        "SELECT COALESCE(MAX(Semana), 0) FROM {$weeksTable} WHERE project_id = ?",
        [$projectId],
        $projectId,
    )->fetchColumn();
    if ($semana >= $maxWeek) {
        return true;
    }
    $role = (new \App\Security\RbacService($this->db))->resolveCurrentRole();
    if (!empty(\App\Security\RbacManager::getCapabilities($role)['canEditPastGeneralProgram'])) {
        return true;
    }
    http_response_code(403);
    echo json_encode(['respuesta' => 'ERROR', 'mensaje' => 'Editar semanas pasadas del Programa General requiere rol Admin o Director.'], JSON_UNESCAPED_UNICODE);
    return false;
}
```

(Verificar la firma real de `RbacManager::getCapabilities` — estática o de instancia — y de `RbacService`; seguir el uso exacto de `LpsWeekEditPolicy:33-35`.) Llamarlo en `update()`, `updateBatch()` y `deleteUpdate()` justo después de resolver `$semana`, `$dbPrefix` y `$projectId`:

```php
if (!$this->assertNotPastWeekOrPrivileged((int) $semana, $dbPrefix, $projectId)) {
    return;
}
```

En `updateBatch()`: si el lote trae semanas por fila, validar la mínima del lote; si usa una sola semana global, validar esa (confirmar leyendo `updateBatch():379-420`).

- [ ] **Step 4: Correr y verificar que pasa.** Esperado: `OK` en los 4 casos.

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/Api/GeneralApiController.php tests/test_pg_pasado_servidor.php
git commit -m "fix(seguridad): el candado de semanas pasadas del PG existe ahora en el servidor"
```

---

### Task 5: 403 de servidor en `/indicadores` y URL fuera del HTML

**Files:**
- Modify: `src/Controllers/Gestion/IndicadoresController.php` (guard al estilo `BiViewController:179`)
- Modify: `views/indicadores/indicadores.view.php:111,151` (no emitir `POWER_BI_REPORT_URL` a roles restringidos)
- Test: `tests/test_indicadores_server_gate.php` (nuevo)

**Interfaces:**
- Consumes: el patrón exacto de 403 de `src/Controllers/Bi/BiViewController.php:179` (leerlo y replicarlo, incluida la vista/plantilla de error que use); roles restringidos: `G`, `S`, `SG`, `C`.

- [ ] **Step 1: Escribir el test que falla** — `tests/test_indicadores_server_gate.php` (copiar `sesion`/`curlReq`):

```php
<?php
declare(strict_types=1);
// /indicadores ocultaba el informe solo en el navegador (EXPERIMENTS.md fila 49).
// Permitido: V (Visualizador ve informes). Denegado: G (Ambiental).
// NOTA: la dev door solo siembra test.A/R/V por defecto; para el rol G usar la cuenta
// sembrada test.C/test.D si alguna tiene rol restringido, o añadir la comprobación
// del HTML con el rol restringido que sí exista. Si ningún rol restringido está
// sembrado, ABORTAR con exit(2) y mensaje pidiendo habilitarlo en DEV_DOOR_USERS.
// ... sesion() y curlReq() ...

$fallos = 0;
$jarV = sesion('test.V');
[$codeV, $htmlV] = curlReq(BASE . '/indicadores', null, $jarV);
if ($codeV !== 200) { $fallos++; echo "FALLO: V no ve /indicadores ($codeV)\n"; }

$jarG = sesion('test.G'); // ajustar a la cuenta restringida real disponible
[$codeG, $htmlG] = curlReq(BASE . '/indicadores', null, $jarG);
if ($codeG !== 403) { $fallos++; echo "FALLO: rol restringido recibió $codeG (esperaba 403)\n"; }
if (str_contains($htmlG, 'POWER_BI_REPORT_URL') || str_contains($htmlG, 'app.powerbi.com')) {
    $fallos++; echo "FALLO: la URL del informe viaja en el HTML del rol restringido\n";
}

echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Confirmar qué cuenta sembrada tiene un rol restringido** (`grep DEV_DOOR_USERS .env` + `database/seeds/dev_test_users.php`) y ajustar el test. Si no existe ninguna, añadir una cuenta `test.G` al seed siguiendo el patrón de las existentes (cambio pequeño y dentro del alcance: sin ella el rol denegado no es verificable).

- [ ] **Step 3: Correr y verificar que falla.** Esperado: FALLO (hoy el rol restringido recibe 200 con la URL en el HTML).

- [ ] **Step 4: Implementar.** En `IndicadoresController::index()`, antes de renderizar: replicar el guard de `BiViewController:179` (leerlo primero; usar su misma forma de resolver el rol y responder 403). En la vista, mover la declaración de `POWER_BI_REPORT_URL` (hoy en `:111`) dentro de la condición de visibilidad que ya existe en `:151`, de modo que el HTML de un rol sin acceso no contenga la constante ni la URL.

- [ ] **Step 5: Correr y verificar que pasa.** Esperado: `OK`.

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Gestion/IndicadoresController.php views/indicadores/indicadores.view.php tests/test_indicadores_server_gate.php database/seeds/
git commit -m "fix(seguridad): /indicadores comprueba el rol en servidor y no filtra la URL del informe"
```

---

### Task 6: Smoke de navegador, cierre documental y verificación final

**Files:**
- Modify: `docs/EXPERIMENTS.md:24-26,49` (columna de estado: abierto → cerrado con hash de commit)
- Test: los 4 tests nuevos + smoke browser

**Interfaces:**
- Consumes: todo lo anterior; dev door; navegador integrado.

- [ ] **Step 1: Correr los 4 tests seguidos** dentro del contenedor:

```bash
docker compose exec app php tests/test_csrf_modulos_api.php
docker compose exec app php tests/test_semanal_sanear_csrf.php
docker compose exec app php tests/test_pg_pasado_servidor.php
docker compose exec app php tests/test_indicadores_server_gate.php
```

Esperado: `OK` los cuatro.

- [ ] **Step 2: Smoke en navegador (dev door, `test.A`).** Abrir con el navegador integrado cada módulo y ejecutar UN guardado real por módulo: `/subcontratistas` (editar un registro), `/profesionales` (ídem), `/control-cambios` (ídem), `/CIC`, `/CNC`, `/CNP` (una mutación cada uno), y en PS ejecutar `sanear`. Consola sin errores; el guardado persiste tras recargar. Si algún guardado falla con 403, hay un caller sin token: volver al censo de Task 2.

- [ ] **Step 3: Actualizar `docs/EXPERIMENTS.md`** — en las filas 24, 25, 26 y 49 cambiar `abierto` por `cerrado <hash-del-commit>` (el hash de cada commit de Tasks 1-5 según corresponda).

- [ ] **Step 4: Suite de regresión mínima** — `docker compose exec app php tests/test_dev_door_guard.php` y `npm run test:design-system:static`. Esperado: verde (los cambios no tocan design system, esto es red de seguridad).

- [ ] **Step 5: Commit final**

```bash
git add docs/EXPERIMENTS.md
git commit -m "docs(seguridad): las filas 24-26 y 49 de EXPERIMENTS.md cierran con sus commits"
```

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** SubcontratistasApiController.php:69 legacy_require_csrf; SemanalApiController.php:130,150,190; GeneralApiController.php:1740 assertNotPastWeekOrPrivileged; tests/test_csrf_modulos_api.php

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
