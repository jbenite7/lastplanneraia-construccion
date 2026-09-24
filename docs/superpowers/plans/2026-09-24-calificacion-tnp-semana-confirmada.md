---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-09-24
areas: [lps, programacion_semanal, tnp, backend, frontend]
fuente: docs/superpowers/plans/2026-09-24-calificacion-tnp-semana-confirmada.md
resumen: Calificación y registro de Trabajo No Planificado (TNP) en semanas confirmadas sin bloqueo HTTP 409
---

# Calificación y Registro de TNP en Semanas Confirmadas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir calificar avance real (`Real`) y registrar actividades de Trabajo No Planificado (TNP) en semanas con compromisos confirmados (`Semanal_Confirmada = 1`) sin bloqueos de HTTP 409 (Conflict) ni falsos mensajes de error de conexión.

**Architecture:** 
1. Backend: En `SemanalApiController::modificar()`, leer y verificar `$esTnp` antes de la comprobación de campos de planificación en semanas confirmadas (`$confirmed`), y no bloquear modificaciones de avance real sobre filas TNP. Además, en `$planningFieldsChanged`, tolerar cuando `Cantidad_Sugerida` en BD es `NULL` y el payload entrante trae `0.0`. En `SemanalApiController::tnp()`, habilitar `$allowIfConfirmed = true` en `\CommitmentLockGuard::guard()`.
2. Frontend: En `hot.js`, evitar que `afterChange` descarte el guardado de la fila al detectar TNP (reemplazar el `continue;` por la omisión de la validación CNC), y en `.fail()` de `saveRow()` leer `jqXHR.responseJSON?.mensaje` para mostrar el error real de la API.
3. Tests: Test enfocado en PHP (`tests/test_semanal_tnp_calificacion.php`) que reproduce la mutación en semana confirmada bajo Docker.

**Tech Stack:** PHP 8.3, MySQL, JavaScript (ES6 / jQuery / Handsontable), Docker Compose.

**Spec:** `docs/superpowers/specs/2026-09-24-calificacion-tnp-semana-confirmada-design.md`

## Global Constraints
- Runtime local: Docker Compose (`app`, `db`, `adminer`) sirviendo en http://localhost:8081. Comandos PHP siempre dentro de `app`: `docker compose exec app php ...`.
- No alterar esquemas de BD ni crear migraciones (no se requieren cambios de tabla).
- Conservar backwards compatibility estricta: filas normales (`Es_TNP = 0`) siguen bloqueando cambios en campos de planificación (`Compromiso`, `Sub_Contratista`, `Responsable_AIA`) en semanas confirmadas.
- Sesiones de test vía dev door (`DEV_DOOR=1` en `.env`).
- TDD estricto: prueba roja antes de implementación verde.

---

### Task 1: Test enfocado (Rojo) para calificación y registro de TNP en semana confirmada

**Files:**
- Create: `tests/test_semanal_tnp_calificacion.php`

**Interfaces:**
- Consumes: `POST /api/semanal/save?db=...` con `opcion=modificar` y `opcion=tnp`.
- Produces: Test automatizado con salida `OK` (RC=0) o reporte de fallos (RC=1).

- [ ] **Step 1: Escribir el test enfocado con dos aserciones críticas**

Crear `tests/test_semanal_tnp_calificacion.php`:

```php
<?php
declare(strict_types=1);
// @requiere: http, dev_door

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';
const DB_PREFIX = 'pdc_sandbox_e2e';

require_once __DIR__ . '/../src/Core/Database.php';

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

$db = Database::getInstance();
$projStmt = $db->query("SELECT project_id FROM projects WHERE db_prefix = ?", [DB_PREFIX]);
$projectId = (int) $projStmt->fetchColumn();
if (!$projectId) {
    fwrite(STDERR, "ABORT: Proyecto de prueba no encontrado.\n");
    exit(2);
}

// 1. Asegurar semana 4 confirmada
$tblSemanas = "semanas_activas";
$db->query(
    "INSERT INTO {$tblSemanas} (project_id, Semana, Semanal_Confirmada, Fecha_Cierre_Compromisos)
     VALUES (?, 4, 1, '2026-09-16')
     ON DUPLICATE KEY UPDATE Semanal_Confirmada = 1",
    [$projectId]
);

// 2. Asegurar una fila TNP en programacion_semanal para semana 4
$tblPS = "programacion_semanal";
$stmtRow = $db->query(
    "SELECT row_id FROM {$tblPS} WHERE project_id = ? AND Semana = 4 AND Es_TNP = 1 LIMIT 1",
    [$projectId]
);
$rowId = $stmtRow->fetchColumn();
if (!$rowId) {
    $db->query(
        "INSERT INTO {$tblPS} (project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Descripcion, Ubicacion, Sub_Contratista, Responsable_AIA, Empresa, Unidad, cantidad_ppto, Compromiso, Cantidad_Sugerida, Ejecutado_Real, Activa, Es_TNP)
         VALUES (?, 4, 99991, 99991, 'TNP.1', 'Actividad TNP Prueba', NULL, NULL, 'CONTRATISTA PRUEBA', 'RESPONSABLE PRUEBA', 'AIA', '%', 100, NULL, NULL, NULL, '1', 1)",
        [$projectId]
    );
    $rowId = (int) $db->lastInsertId();
} else {
    $rowId = (int) $rowId;
    $db->query("UPDATE {$tblPS} SET Compromiso = NULL, Cantidad_Sugerida = NULL, Ejecutado_Real = NULL WHERE project_id = ? AND row_id = ?", [$projectId, $rowId]);
}

$jar = sesion('test.A');
// Obtener token CSRF
[, $html] = curlReq(BASE . '/programacion-semanal', null, $jar);
preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/', $html, $mCsrf);
$csrfToken = $mCsrf[1] ?? '';
if (!$csrfToken) {
    fwrite(STDERR, "ABORT: No se pudo obtener CSRF token.\n");
    exit(2);
}

$fallos = 0;

// Test A: Calificar avance Real en la fila TNP mediante modificar
$payloadModificar = [
    'opcion' => 'modificar',
    '_csrf_token' => $csrfToken,
    'semana' => 4,
    'Id' => $rowId,
    'Compromiso' => '',
    'Cantidad_Sugerida' => '0.0',
    'Real' => '45.0',
    'Sub_Contratista' => 'CONTRATISTA PRUEBA',
    'Responsable_AIA' => 'RESPONSABLE PRUEBA',
    'Descripcion' => '',
    'Ubicacion' => '',
    'Empresa' => 'AIA',
    'Unidad' => '%',
    'Es_TNP' => '1',
];
[$codeMod, $bodyMod] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), $payloadModificar, $jar);
$resMod = json_decode($bodyMod, true);
if ($codeMod !== 200 || ($resMod['respuesta'] ?? '') !== 'BIEN') {
    $fallos++;
    echo "FALLO modificar TNP: HTTP $codeMod, cuerpo: $bodyMod (esperaba HTTP 200 y respuesta BIEN)\n";
} else {
    echo "OK modificar TNP: calificado con éxito en semana confirmada.\n";
}

// Test B: Registrar TNP mediante opcion=tnp en semana confirmada
$payloadTnp = [
    'opcion' => 'tnp',
    '_csrf_token' => $csrfToken,
    'semana' => 4,
    'Consecutivo' => 99991,
    'Ejecutado_Real' => 30.0,
    'Categoria_CP' => 'IMPREVISTOS',
    'CP' => 'Clima',
    'Observaciones_CP' => 'Lluvia torrencial',
];
[$codeTnp, $bodyTnp] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), $payloadTnp, $jar);
$resTnp = json_decode($bodyTnp, true);
if ($codeTnp !== 200 || ($resTnp['respuesta'] ?? '') !== 'BIEN') {
    $fallos++;
    echo "FALLO opcion=tnp: HTTP $codeTnp, cuerpo: $bodyTnp (esperaba HTTP 200 y respuesta BIEN)\n";
} else {
    echo "OK opcion=tnp: registrado con éxito en semana confirmada.\n";
}

echo $fallos === 0 ? "TODO OK\n" : "TOTAL FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Ejecutar el test para verificar que falla (Fase Roja)**

Ejecutar:
```bash
docker compose exec app php tests/test_semanal_tnp_calificacion.php
```
Salida esperada:
Falla con código 1, mostrando `HTTP 409` en modificar y en `opcion=tnp`.

- [ ] **Step 3: Commit del test rojo**

```bash
git add tests/test_semanal_tnp_calificacion.php
git commit -m "test(ps): prueba roja para calificacion y registro de TNP en semana confirmada"
```

---

### Task 2: Backend: Corregir `SemanalApiController.php`

**Files:**
- Modify: `src/Controllers/Api/SemanalApiController.php:300-330, 1325-1335`

**Interfaces:**
- Consumes: Parámetros POST de `/api/semanal/save`.
- Produces: Respuesta JSON con `respuesta: BIEN` en mutaciones de TNP sobre semanas confirmadas.

- [ ] **Step 1: Aplicar corrección en método `modificar()`**

En [SemanalApiController.php](file:///Volumes/Crucial%20X6/Developer/lps-aia/src/Controllers/Api/SemanalApiController.php#L308-L325):
1. Mover la variable `$esTnp` antes de las comprobaciones de `$confirmed`:
```php
        $confirmed = (int) ($weekState['Semanal_Confirmada'] ?? 0) === 1;
        $esTnp = (int) ($rowActual['Es_TNP'] ?? 0) === 1
            || (isset($_POST['Es_TNP']) && (int) $_POST['Es_TNP'] === 1);

        if ($realChanged && !$confirmed) {
            $this->jsonError('El avance real solo se registra en la fase de calificación.', 409);
            return;
        }

        // Si la fila no es TNP, verificar que no se alteren campos de planificación en semana confirmada
        if ($confirmed && !$esTnp && ($commitmentChanged || $assigneesChanged || $planningFieldsChanged)) {
            $this->jsonError('Los datos de planificación solo se editan en programación.', 409);
            return;
        }
```
2. En el cómputo de `$planningFieldsChanged`, tolerar cuando en BD `Cantidad_Sugerida` es `NULL` y el incoming es `0.0`:
```php
        $dbSuggested = $this->lpsService->toFloat($rowActual['Cantidad_Sugerida'] ?? null);
        $suggestedChanged = ($dbSuggested === null && ($suggested === null || abs($suggested) < 0.0001))
            ? false
            : $this->nullableFloatChanged($suggested, $dbSuggested);

        $planningFieldsChanged = $description !== trim((string) ($rowActual['Descripcion'] ?? ''))
            || $location !== trim((string) ($rowActual['Ubicacion'] ?? ''))
            || $company !== trim((string) ($rowActual['Empresa'] ?? ''))
            || $performance !== trim((string) ($rowActual['Rendimientos'] ?? ''))
            || $suggestedChanged;
```

- [ ] **Step 2: Aplicar corrección en método `tnp()`**

En [SemanalApiController.php](file:///Volumes/Crucial%20X6/Developer/lps-aia/src/Controllers/Api/SemanalApiController.php#L1328):
Cambiar:
```php
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'tnp');
```
por:
```php
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'tnp', true);
```

- [ ] **Step 3: Ejecutar el test para verificar que pasa a verde**

Ejecutar:
```bash
docker compose exec app php tests/test_semanal_tnp_calificacion.php
```
Salida esperada:
```
OK modificar TNP: calificado con éxito en semana confirmada.
OK opcion=tnp: registrado con éxito en semana confirmada.
TODO OK
```
Código de salida: 0.

- [ ] **Step 4: Commit del backend verde**

```bash
git add src/Controllers/Api/SemanalApiController.php
git commit -m "fix(ps): permitir calificar y registrar TNP en semanas confirmadas sin bloqueo 409"
```

---

### Task 3: Frontend: Corregir `hot.js` (`afterChange` y feedback de error)

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:2587-2593, 3073-3105`

**Interfaces:**
- Consumes: Evento `afterChange` de Handsontable y respuesta AJAX de `saveRow`.
- Produces: Guardado correcto de celdas TNP y mensajes claros de error del servidor.

- [ ] **Step 1: Corregir el bucle `afterChange` para no saltarse `saveRow` en TNP**

En [hot.js](file:///Volumes/Crucial%20X6/Developer/lps-aia/public/js/modules/programacion_semanal/hot.js#L3073-L3102):
Reemplazar:
```javascript
          if (prop === 'Ejecutado_Real') {
            var esTnpRow = rowData.Es_TNP === 1 || rowData.Es_TNP === '1';
            if (!esTnpRow && typeof getStateKey === 'function') {
              esTnpRow = (getStateKey(rowData) === 'cal-tnp');
            }
            if (esTnpRow) {
              continue;
            }
          }

          // HARD GUARD: Block real execution registration if missing assignees
          if (prop === 'Ejecutado_Real') {
            var isSubMissing = isBlank(rowData.Sub_Contratista);
            var isResMissing = isBlank(rowData.Responsable_AIA);

            if (isSubMissing || isResMissing) {
              revertCell(rowIndex, prop, oldValue);
              showFeedback('error', 'Falta Sub-Contratista o Resp. AIA para registrar avance');
              continue;
            }

            // Si el avance real es menor al compromiso, SIEMPRE pedir CNC
            if (requiresCnc(rowData, newValue)) {
                queueCncSave(rowIndex, rowData, prop, oldValue, newValue, { mobile: false });

                revertCell(rowIndex, prop, oldValue);
                continue;
            }
          }
```
Por:
```javascript
          if (prop === 'Ejecutado_Real') {
            var esTnpRow = rowData.Es_TNP === 1 || rowData.Es_TNP === '1';
            if (!esTnpRow && typeof getStateKey === 'function') {
              esTnpRow = (getStateKey(rowData) === 'cal-tnp');
            }

            // En filas TNP no se exige CNC ni asignados previos; en filas planificadas sí
            if (!esTnpRow) {
              var isSubMissing = isBlank(rowData.Sub_Contratista);
              var isResMissing = isBlank(rowData.Responsable_AIA);

              if (isSubMissing || isResMissing) {
                revertCell(rowIndex, prop, oldValue);
                showFeedback('error', 'Falta Sub-Contratista o Resp. AIA para registrar avance');
                continue;
              }

              // Si el avance real es menor al compromiso, SIEMPRE pedir CNC
              if (requiresCnc(rowData, newValue)) {
                queueCncSave(rowIndex, rowData, prop, oldValue, newValue, { mobile: false });
                revertCell(rowIndex, prop, oldValue);
                continue;
              }
            }
          }
```

- [ ] **Step 2: Corregir el manejador `.fail()` de `saveRow()` para reportar errores del servidor**

En [hot.js](file:///Volumes/Crucial%20X6/Developer/lps-aia/public/js/modules/programacion_semanal/hot.js#L2587-L2592):
Reemplazar:
```javascript
    }).fail(function () {
      if (!isMobileSave) { revertCell(visualRow, prop, oldValue); }
      setMobileSaveState(visualRow, prop, 'error', 'Error de red');
      renderMobileCards(getFilteredRows());
      showFeedback('error', 'No se pudo guardar: sin conexión con el servidor. Revisa la red y vuelve a escribir el dato.');
    });
```
Por:
```javascript
    }).fail(function (jqXHR) {
      if (!isMobileSave) { revertCell(visualRow, prop, oldValue); }
      var serverMsg = (jqXHR && jqXHR.responseJSON && (jqXHR.responseJSON.mensaje || jqXHR.responseJSON.message))
        ? jqXHR.responseJSON.mensaje || jqXHR.responseJSON.message
        : null;
      var finalError = serverMsg || 'No se pudo guardar: sin conexión con el servidor. Revisa la red y vuelve a escribir el dato.';
      setMobileSaveState(visualRow, prop, 'error', serverMsg ? 'Error al guardar' : 'Error de red');
      renderMobileCards(getFilteredRows());
      showFeedback('error', finalError);
    });
```

- [ ] **Step 3: Commit de los cambios frontend**

```bash
git add public/js/modules/programacion_semanal/hot.js
git commit -m "fix(ps): corregir guardado de TNP en afterChange y mostrar mensaje de error de API en fail"
```

---

### Task 4: Verificación estática y suites de regresión

**Files:**
- Verify: Todas las rutas afectadas.

- [x] **Step 1: Análisis estático con PHPStan**

Ejecutar:
```bash
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/SemanalApiController.php --memory-limit=1G
```
Esperado: [OK] No errors.

- [x] **Step 2: Suite de seguridad de tablas globales**

Ejecutar:
```bash
docker compose exec app php tests/test_global_table_safety.php
```
Esperado: OK.

- [x] **Step 3: Suite de conciliación de tablas globales**

Ejecutar:
```bash
docker compose exec app php tests/test_global_table_reconciliation.php
```
Esperado: OK.

- [x] **Step 4: Re-ejecutar test de TNP calificado**

Ejecutar:
```bash
docker compose exec app php tests/test_semanal_tnp_calificacion.php
```
Esperado: OK (RC = 0).

---

## Guía Operativa para Producción (SiteGround)

Para aplicar el hotfix directamente en el servidor de producción:

1. Abrir terminal SSH o Administrador de Archivos de SiteGround en `~/www/lastplanneraia.com/public_html/src/Controllers/Api/SemanalApiController.php`.
2. En `modificar()` (~línea 308):
   - Mover la asignación `$esTnp = (int) ($rowActual['Es_TNP'] ?? 0) === 1 || (isset($_POST['Es_TNP']) && (int) $_POST['Es_TNP'] === 1);` arriba del `if ($confirmed ...`.
   - Modificar la condición a: `if ($confirmed && !$esTnp && ($commitmentChanged || $assigneesChanged || $planningFieldsChanged))`.
3. En `tnp()` (~línea 1328):
   - Cambiar a `\CommitmentLockGuard::guard($dbPrefix, $semana, 'tnp', true);`.
4. Guardar archivo. No requiere reiniciar Apache ni vaciar caches de PHP (se aplica al instante).
