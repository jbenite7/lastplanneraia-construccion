# Plan — Revisión de queries SQL (project_id y Unique ID)

## Enfoque de solución

La app tiene un mecanismo de auto-inyección de `project_id` vía `Database::injectProjectId()` y `Database::injectProjectFilter()` que protege la mayoría de queries. El plan consiste en:

1. **Hacer explícitos** los `WHERE project_id = ?` en queries que hoy dependen de auto-inyección (defense-in-depth)
2. **Corregir** los 3 vulnerabilidades críticas donde la auto-inyección NO funciona (INSERT...SELECT y UNION dinámico)
3. **Migrar** la tabla `cambios` a tabla global con `project_id`
4. **Documentar** el estado de Unique IDs / scoped IDs por tabla
5. **Validar** con Playwright todos los workflows

---

## Pasos ordenados

### Paso 1: Migrar tabla `cambios` a global con `project_id` ⚠️ PRERREQUISITO

**Archivos**: `admin/src/Models/Project.php`, `database/migrations/`

- Agregar migración SQL para crear tabla global `cambios` con columna `project_id`
- Modificar `Project.php` para que al crear proyecto también cree la tabla global
- Migrar datos existentes de `{prefix}_cambios` → `cambios`

**Verificación**: Consultar tabla `cambios` existente en DB, verificar columnas, migrar datos.

---

### Paso 2: Corregir vulnerabilidades críticas (INSERT...SELECT)

#### 2a. `src/Legacy/_pdc_functions.php` — líneas 34-64

**Archivo**: `src/Legacy/_pdc_functions.php`

Agregar `AND project_id = ?` a cada subquery SELECT dentro del INSERT...SELECT de las líneas 34-64 (hay 5 subqueries UNION que consultan `{$tActividades}`). Pasar `$projectId` como parámetro adicional.

```sql
-- Antes (simplificado):
SELECT ... FROM {$tActividades} WHERE semanaActualizacion = ? AND tipoContrato = ?
-- Después:
SELECT ... FROM {$tActividades} WHERE project_id = ? AND semanaActualizacion = ? AND tipoContrato = ?
```

#### 2b: `src/Legacy/_pdc_functions.php` — líneas 99-108

Agregar `AND project_id = ?` al SELECT subquery del INSERT...SELECT.

```sql
-- Antes:
SELECT ... FROM {$tPdc} WHERE consecutivo = ?
-- Después:
SELECT ... FROM {$tPdc} WHERE project_id = ? AND consecutivo = ?
```

#### 2c: `src/Legacy/verificarCICActualizada.php` — líneas 66-78

El UNION dinámico es complejo. La auto-inyección falla porque `project_id` no existe en la tabla derivada. Solución:
- Agregar `AND project_id = ?` en cada subquery UNION individual (hay N subqueries, una por subcontratista)
- Alternativamente, cambiar el approach a un solo query con `GROUP BY subcontratista`

**Archivo**: `src/Legacy/verificarCICActualizada.php`

**Verificación**: Ejecutar el endpoint de CIC después del cambio y confirmar que los datos sean correctos y no haya error SQL.

---

### Paso 3: Agregar `WHERE project_id = ?` explícito en API Controllers

#### 3a: `SubcontratistasApiController.php`

Agregar `AND project_id = ?` (o `WHERE project_id = ?` en queries sin WHERE) en:
- Línea 40 — subquery en SELECT principal: `(SELECT COUNT(*) FROM $tbl WHERE $tbl.$col = s.subcontratista AND project_id = ?)`
- Línea 47 — SELECT principal: agregar `WHERE project_id = ? AND ...` (el query actual no tiene WHERE)
- Línea 142 — UPDATE `WHERE project_id = ? AND Id = ?`
- Línea 178 — UPDATE de dependencias: `WHERE project_id = ? AND $col = ?`
- Línea 201 — INSERT: agregar `project_id` en columnas y valor
- Línea 210 — SELECT: `WHERE project_id = ? AND correo_contacto = ?`
- Línea 223 — SELECT: `WHERE project_id = ? AND Id = ?`
- Línea 235 — DELETE: `WHERE project_id = ? AND Id = ?`
- Línea 248 — SELECT COUNT dependencias: `WHERE project_id = ? AND $col = ?`
- Línea 265 — SELECT: `WHERE project_id = ? AND Id = ?`
- Línea 361 — SELECT: `WHERE project_id = ? AND ...` (el query no tiene WHERE)

**Patrón**: Cambiar `queryWithProject()` → `query()` (porque ya no necesitamos auto-inyección si el SQL es explícito). O mantener `queryWithProject()` pero con el WHERE explícito (la auto-inyección detecta que ya tiene `project_id` y no duplica).

#### 3b: `ProfesionalesApiController.php`

Agregar `WHERE project_id = ?` en:
- Línea 67 — subquery en SELECT: `(SELECT COUNT(*) FROM $tbl WHERE $tbl.$col = p.nombre AND project_id = ?)`
- Línea 73 — SELECT principal
- Línea 190 — UPDATE
- Línea 215 — UPDATE
- Línea 249 — UPDATE dependencias
- Línea 270 — INSERT (agregar columna project_id)
- Línea 275 — SELECT `WHERE project_id = ? AND email = ?`
- Línea 288 — SELECT `WHERE project_id = ? AND id = ?`
- Línea 314 — SELECT COUNT dependencias
- Línea 321 — DELETE `WHERE project_id = ? AND id = ?`
- Línea 338 — SELECT `WHERE project_id = ? AND id = ?`
- Línea 403 — SELECT (buscar duplicados)

#### 3c: `ControlCambiosApiController.php`

- Línea 30 — SELECT COUNT: `WHERE project_id = ?`
- Línea 45 — SELECT listado: `WHERE project_id = ?`
- Línea 105 — INSERT: agregar project_id
- Línea 121 — UPDATE: `WHERE project_id = ? AND id = ?`
- Línea 182 — DELETE: `WHERE project_id = ? AND id = ?`
- Línea 191 — SELECT profesionales: `WHERE project_id = ? AND cargo = ?`
- Línea 205 — SELECT programa_consolidado: `WHERE project_id = ? AND unique_id = ? AND Semana = ?`

**Nota**: Este paso DEPENDE del Paso 1 (migración de tabla cambios a global).

#### 3d: `CncApiController.php`

- Línea 31 — SELECT: `WHERE project_id = ? AND Semana = ? AND Activa = 1`
- Línea 54 — UPDATE: `WHERE project_id = ? AND row_id = ?`

#### 3e: `CnpApiController.php`

- Línea 31 — SELECT: `WHERE project_id = ? AND Semana = ? AND Activa = 0`
- Línea 54 — UPDATE: `WHERE project_id = ? AND row_id = ?`
- Línea 75 — UPDATE: `WHERE project_id = ? AND row_id = ?`

#### 3f: `GeneralApiController.php`

Agregar `WHERE project_id = ?` en todos los queries a tablas globales:
- `semanas_activas` — líneas 75, 316, 385, 449, 549, 745, 751, 752
- `programa_consolidado` — líneas 82-91, 156, 216, 222-235, 237, 290-301, 309, 325, 329, 348, 410, 411, 413, 417, 457-461, 466-467, 546, 724, 737-738, 1163, 1169-1170, 1173-1177, 1234-1235, 1291-1293, 1298-1300, 1357-1363, 1368-1371

**Patrón**: Para la mayoría, agregar `AND project_id = ?` al WHERE existente y pasar `$projectId` como último parámetro. Para queries sin WHERE, empezar con `WHERE project_id = ? AND ...`.

#### 3g: `SemanalApiController.php`

Agregar `WHERE project_id = ?` en todos los queries a:
- `programacion_semanal` — ~30 queries
- `programa_consolidado` — ~15 queries
- `semanas_activas` — ~10 queries
- `cic` — ~3 queries

**Nota**: Este archivo es el más grande (~60 queries). El método `list()` (líneas 35-112) ya usa `query()` con `WHERE project_id = ?` explícito — usar ese como patrón de referencia.

---

### Paso 4: Legacy scripts

#### 4a: `nueva_semana.php`

Agregar `AND project_id = ?` explícito en ~15 queries que hoy usan `queryWithProject()`:
- DELETE, UPDATE, SELECT en `semanas_activas`, `programa_consolidado`, `programa`

#### 4b: `eliminar_semana.php`

Línea 57 — DELETE dentro de loop: `WHERE project_id = ? AND $columna >= ?`

#### 4c: `modificar_sem_estado.php`

Agregar `setProjectContext($projectId)` al inicio del archivo (hoy no lo tiene, hereda contexto).
Además agregar `AND project_id = ?` en los 7 queries.

#### 4d: `actualizarEjecucion.php`

Agregar `AND project_id = ?` en los 2 queries (líneas 30, 64).

#### 4e: `autoprogramar_actividades.php`

Agregar `AND project_id = ?` en ~12 queries (SELECT/UPDATE/DELETE).

#### 4f: `guardar_programacion_intermedia.php`

Agregar `AND project_id = ?` en los ~10 queries.

#### 4g: `_pdc_functions.php` (adicional a paso 2)

Agregar `AND project_id = ?` en:
- Línea 82 — SELECT: `WHERE project_id = ? AND semana = ? AND ...`
- Línea 90 — SELECT COUNT: `WHERE project_id = ? AND semana = ? AND ...`
- Línea 119 — SELECT: `WHERE project_id = ? AND semana = ? AND ...`
- Línea 183 — UPDATE: `WHERE project_id = ? AND consecutivo = ?`

---

### Paso 5: Vistas (views/)

#### 5a: `views/programacion-semanal/CNP.view.php` — línea 479

Agregar `WHERE project_id = ? AND Activo=1`

#### 5b: `views/programa-general-actualizar/*.view.php` — línea 441

Agregar `AND project_id = ?` a `WHERE Semana = ?`

#### 5c: `views/programacion-semanal/programacion_semanal.view.php` — línea 1009

Agregar `AND project_id = ?` a `WHERE Semana = ?`

---

### Paso 6: Admin panel

#### 6a: `admin/src/Models/Project.php:178`

Agregar `WHERE project_id = ?` al `SELECT * FROM \`{$table}\``. Determinar si esto es export/backup — si es export de datos de un proyecto específico, agregar filtro.

---

### Paso 7: Services

#### 7a: `src/Services/ProjectProfessionalsSyncService.php`

Verificar y agregar `WHERE project_id = ?` en los queries del método `countProfessionalDependencies()` (línea 582) y otros que falten.

#### 7b: `src/Services/SemiAutoService.php`

Verificar línea 1821 — `UPDATE {$table}` dinámico ya tiene `WHERE project_id = ? AND {$pkName} = ?` según exploración previa. Confirmar y documentar.

#### 7c: Buscar `IndirectCostProcessor.php`

El archivo no existe. Investigar si fue renombrado o si está en otra ubicación (`grep` por IndirectCostProcessor).

---

### Paso 8: Documentar Unique ID / scoped ID por tabla

**Archivo**: `goals/revision-queries-project-id/unique-id-audit.md`

Consultar el esquema de cada tabla global y documentar:
- Tablas que tienen `project_id + auto_increment_id` (la mayoría)
- Tablas que tienen scoped ID secuencial (`pdc_row_id`, `consecutivo`)
- Tablas que tienen `unique_id` (programa_consolidado, programacion_semanal)
- Tablas que no tienen ningún scoped ID

```bash
docker compose exec app php -r "echo json_encode(\$db->query('SHOW CREATE TABLE ...')->fetch());"
```

---

### Paso 9: Validación con Playwright

Ejecutar tests Playwright cubriendo todos los workflows mencionados por el usuario:

```bash
npx playwright test --grep "programa-general|programacion-intermedia|programacion-semanal|cnp|cnc|cic|profesionales|subcontratistas|listado-actividades|contratos|pdc"
```

**Workflows a cubrir**:
- `/programa-general/`
- `/programacion-intermedia/`
- `/programacion-semanal/`
- `/programacion-semanal/cnp`
- `/programacion-semanal/cnc`
- `/programacion-semanal/cic`
- `/profesionales/`
- `/subcontratistas/`
- `/programa-general-actualizar/`
- `/listado-actividades/`
- `/contratos/`
- `/pdc/`

---

### Paso 10: Code review y deploy

- Revisar cada cambio con code review
- Desplegar a SiteGround pruebas (`prueba-lps.lastplanneraia.com`)
- Validar workflows en ambiente de pruebas
- Desplegar a producción (`lastplanneraia.com`)

---

## Riesgos y preguntas abiertas

1. **`USE_GLOBAL_TABLES` en producción**: La auto-inyección via `query()` solo funciona cuando `USE_GLOBAL_TABLES=true`. Si en algún ambiente está en `false`, la protección no existe. Verificar el valor en cada ambiente.
2. **`modificar_sem_estado.php` standalone**: Si este archivo se llama como endpoint independiente (sin ser incluido desde otro script), no tiene `setProjectContext()`. Investigar si tiene rutas registradas.
3. **Validar columna `project_id` en `cambios`**: Antes de migrar, confirmar el esquema exacto de `{prefix}_cambios`.
4. **Rendimiento**: Agregar `WHERE project_id = ?` puede cambiar el plan de ejecución del query — verificar que los índices cubran la nueva columna.