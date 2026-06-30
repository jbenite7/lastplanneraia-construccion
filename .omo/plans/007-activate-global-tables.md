# Plan: Activar USE_GLOBAL_TABLES=true

## TL;DR (For humans)

**Qué**: Activar el flag `USE_GLOBAL_TABLES=true` para que la app lea/escriba en las 16 tablas globales consolidadas en lugar de las 323 tablas legacy por proyecto.

**Por qué este enfoque**: El TableResolver y Database ya tienen la infraestructura completa (flag toggle, inyección automática de project_id). Solo falta corregir 2 bugs y activar el flag.

**Qué NO hace este plan**:
- No elimina las tablas legacy (queda para después)
- No migra datos faltantes (ya está completo)
- No modifica el esquema de tablas globales

**Esfuerzo**: ~2-3 horas de implementación + testing
**Riesgo**: MEDIO — hay 7 archivos con queries raw que necesitan fix antes de activar

## Contexto

El sistema tiene 16 tablas globales consolidadas (con `project_id`) y 323 tablas legacy por proyecto (`{prefix}_{table}`). La app actualmente usa las tablas legacy via `USE_GLOBAL_TABLES=false`. Los datos de todos los proyectos (incluyendo los 7 migrados recientemente) ya están en las tablas globales.

**Hallazgos críticos de la revisión Metis + Momus**:

1. **Bug de variable scope** en el fix de SessionMiddleware — `$db` está dentro de un `try` y puede no estar disponible en el punto de inserción
2. **7 archivos** usan `$db->query()` en lugar de `$db->queryWithProject()` — no inyectan project_id
3. **4 view files** llaman Database directamente
4. **Los smoke tests no verifican aislamiento de datos** — solo checkean "sin error 500"

## Archivos a modificar

### 1. `src/Core/SessionMiddleware.php` — FIX CRÍTICO

**Problema**: En cada page load (request PHP nuevo), se crea un `Database` singleton nuevo. Nadie llama `setProjectContext()`. Cuando `USE_GLOBAL_TABLES=true`, `queryWithProject()` detecta que la query toca una tabla global pero `$currentProjectId` es `null` → ejecuta sin `AND project_id = ?` → datos de TODOS los proyectos se mezclan.

**Solución**: Agregar auto-setup del contexto de proyecto al final de `SessionMiddleware::check()`. Este método se ejecuta en CADA request autenticado (línea 45 de `public/index.php`).

**Bug de scope identificado por Metis**: `$db` está declarado dentro de un bloque `try` (línea 36) y puede no estar disponible fuera de él. La corrección usa `\Database::getInstance()` directamente en lugar de referenciar `$db`.

**Cambio específico** — agregar después de la línea 68 (`$_SESSION['semana'] = ...`), antes del cierre del método:

```php
// Auto-establecer contexto de proyecto para tablas globales (USE_GLOBAL_TABLES=true)
if (isset($_SESSION['db']) && $_SESSION['db'] !== '') {
    try {
        $projectId = \TableResolver::getProjectIdByPrefix($_SESSION['db']);
        if ($projectId) {
            \Database::getInstance()->setProjectContext($projectId);
        }
    } catch (\Throwable $e) {
        error_log('SessionMiddleware: No se pudo establecer contexto de proyecto: ' . $e->getMessage());
    }
}
```

**Por qué funciona**:
- `TableResolver` está en classmap de Composer (autoloaded)
- `\Database::getInstance()` retorna el singleton actual (no depende de `$db` del try block)
- `$_SESSION['db']` contiene el prefijo (ej: 'prueba') establecido por `ProjectSelectorController::select()`
- `getProjectIdByPrefix()` resuelve 'prueba' → 27, 'optimizacionJMC' → 68, etc.
- Cuando `USE_GLOBAL_TABLES=false`, `TableResolver::resolve()` ignora el contexto — el fix es seguro mantenerlo

### 2. Controllers con queries raw — FIX ANTES DE ACTIVAR

**Problema**: 3 controllers usan `$db->query()` en lugar de `$db->queryWithProject()`. Cuando `USE_GLOBAL_TABLES=true`, estas queries retornan datos de TODOS los proyectos (sin filtro project_id).

**Archivos afectados** (verificados por Metis y Momus):

| Archivo | Llamadas raw | Solución |
|---------|-------------|----------|
| `src/Controllers/Api/IndicadoresApiController.php` | 14 llamadas + `generarIndicadores()` con 20+ internas | Migrar a `queryWithProject()` o usar `TableResolver::getProjectIdByPrefix()` para contexto |
| `src/Controllers/Api/ContratosApiController.php` | 30 llamadas (incluye uso de `Actividad::getByWeek()`) | Migrar a `queryWithProject()` o usar `TableResolver::getProjectIdByPrefix()` para contexto |
| `src/Controllers/Gestion/ReportController.php` | 12 llamadas en helpers de descarga | Migrar a `queryWithProject()` o usar `TableResolver::getProjectIdByPrefix()` para contexto |

**Solución recomendada (Opción B — cambio mínimo)**: En `Database::query()`, auto-inyectar `project_id` cuando el contexto está disponible y la query toca una tabla global. Esto corrige TODAS las llamadas raw de golpe, incluyendo las 56+ internas de `IndicadoresApiController` y `ContratosApiController`.

**Alternativa**: Migrar cada llamada individualmente a `queryWithProject()` — más trabajo pero más explícito.

### 3. Services con queries raw — FIX ANTES DE ACTIVAR

**Problema**: 4 services usan `$this->db->query()` en lugar de `queryWithProject()`:

| Service | Tablas afectadas |
|---------|-----------------|
| `ProgramChangeDetector` | `programacion_semanal`, `programa_consolidado`, `programa_general` |
| `ProjectProfessionalsSyncService` | datos por proyecto |
| `LpsService` | datos por proyecto |
| `productividad_temporal.php` | `programacion_semanal`, `programa_consolidado` |

**Solución**: Migrar a `queryWithProject()` o aplicar el fix de `Database::query()` (Opción B arriba).

### 4. Docker `.env` — Activar flag

Agregar al archivo `.env` dentro del contenedor Docker:

```
USE_GLOBAL_TABLES=true
```

**Ubicación**: `/var/www/html/.env` dentro del contenedor `last-planner-aia-app-1`

**Método**: `docker exec` para agregar la línea, o modificar el `.env` en el host y rebuild.

### 5. Reiniciar contenedor de app

```bash
docker restart last-planner-aia-app-1
```

Esto fuerza a PHP a recargar el `.env` y aplicar el cambio.

## Secuencia de ejecución

```
Paso 0: Verificar integridad pre-activación (16 tablas globales existen y tienen datos)
   ↓
Paso 1: Aplicar fix en SessionMiddleware.php (auto-set project context)
   ↓
Paso 2: Aplicar fix en Database.php (auto-inject project_id en query())
   ↓
Paso 3: Verificar que los fixes no rompen funcionalidad existente (USE_GLOBAL_TABLES=false)
   ↓
Paso 4: Agregar USE_GLOBAL_TABLES=true al .env
   ↓
Paso 5: Reiniciar contenedor de app
   ↓
Paso 6: Smoke tests — FASE 1-6 (verificar que la app carga sin errores)
   ↓
Paso 7: Smoke tests — FASE 7 APIs (verificar que respondan con datos del proyecto correcto)
   ↓
Paso 8: Verificar aislamiento de datos (counts por proyecto, test cruzado)
```

### Paso 0: Verificación pre-activación

Ejecutar en Docker para confirmar que las 16 tablas globales existen y tienen datos:

```sql
-- Verificar que todas las tablas globales existen
SELECT table_name, table_rows 
FROM information_schema.tables 
WHERE table_schema = 'lastplanneraia_dev' 
  AND table_name IN ('actividades','auto_program_log','cambios','cic','contrato','control_cambios',
                      'indicadores_generales','lps_drawer_comentarios','lps_escalamientos',
                      'pdc','pi_shared_constraint_links','pi_shared_constraints',
                      'profesionales','programa','programa_consolidado',
                      'programacion_semanal','semanas_activas','subcontratistas')
ORDER BY table_name;

-- Verificar distribution de project_id en tablas clave
SELECT 'programacion_semanal' AS tabla, project_id, COUNT(*) AS cnt FROM programacion_semanal GROUP BY project_id
UNION ALL
SELECT 'actividades', project_id, COUNT(*) FROM actividades GROUP BY project_id
UNION ALL
SELECT 'cic', project_id, COUNT(*) FROM cic GROUP BY project_id
UNION ALL
SELECT 'pdc', project_id, COUNT(*) FROM pdc GROUP BY project_id
UNION ALL
SELECT 'profesionales', project_id, COUNT(*) FROM profesionales GROUP BY project_id
UNION ALL
SELECT 'subcontratistas', project_id, COUNT(*) FROM subcontratistas GROUP BY project_id
ORDER BY tabla, project_id;
```

## Smoke Tests (Paso 4) — Suite Completa

Cada test navega con Playwright al proyecto **"Prueba"** (project_id=27) y verifica:
- La página carga sin error 500
- No hay errores en consola del navegador
- La API subyacente responde con datos (cuando aplica)

### FASE 1: Core y Auth

| # | Módulo | URL | Verificación |
|---|--------|-----|-------------|
| 1 | Login | `POST /login` con `test.A` / `<TEST_PASSWORD>` | Redirect a /proyectos |
| 2 | Selector de proyectos | `GET /proyectos` | Lista muestra "Prueba" como proyecto accesible |
| 3 | Selección de proyecto | `POST /proyecto/seleccionar` con proyecto="Prueba" | Redirect a landing page |

### FASE 2: Programación

| # | Módulo | URL | Tablas globales involucradas | Verificación |
|---|--------|-----|----------------------------|-------------|
| 4 | **Programa General** | `GET /programa-general` | `programa_consolidado`, `semanas_activas` | Muestra tabla con actividades y semanas |
| 5 | **Programa General Actualizar** | `GET /programa-general-actualizar` | `programa`, `programa_consolidado`, `semanas_activas` | Muestra formulario de actualización semanal |
| 6 | **Programación Semanal** | `GET /programacion-semanal` | `programacion_semanal`, `subcontratistas`, `profesionales` | Muestra treegrid con actividades, PAC, Ejecutado |
| 7 | **Programación Intermedia** | `GET /programacion-intermedia` | `programa_consolidado`, `pi_shared_constraints` | Muestra vista PI con restricciones |

### FASE 3: Sub-módulos de Programación Semanal

| # | Módulo | URL | Tablas globales | Verificación |
|---|--------|-----|----------------|-------------|
| 8 | **CNP** (Causas No Programación) | `GET /programacion-semanal/cnp` | `programacion_semanal` | Muestra vista CNP sin error |
| 9 | **CNC** (Causas No Cumplimiento) | `GET /programacion-semanal/cnc` | `programacion_semanal` | Muestra vista CNC sin error |
| 10 | **CIC** (Calificación Integral) | `GET /programacion-semanal/cic` | `cic`, `subcontratistas` | Muestra tabla CIC con subcontratistas |

### FASE 4: Gestión

| # | Módulo | URL | Tablas globales | Verificación |
|---|--------|-----|----------------|-------------|
| 11 | **PDC** | `GET /pdc` | `pdc`, `actividades` | Muestra vista PDC sin error |
| 12 | **Profesionales** | `GET /profesionales` | `profesionales` | Muestra lista de profesionales |
| 13 | **Subcontratistas** | `GET /subcontratistas` | `subcontratistas` | Muestra lista de subcontratistas |
| 14 | **Contratos** | `GET /contratos` | `actividades` | Muestra vista de contratos sin error |
| 15 | **Listado de Actividades** | `GET /listado-actividades` | `actividades` | Muestra listado sin error |
| 16 | **Indicadores** | `GET /indicadores` | (verificar tablas) | Muestra vista de indicadores sin error |

### FASE 5: Integración

| # | Módulo | URL | Tablas globales | Verificación |
|---|--------|-----|----------------|-------------|
| 17 | **Control de Cambios** | `GET /control-cambios` | `cambios` | Muestra lista de cambios sin error |

### FASE 6: Reportes

| # | Módulo | URL | Verificación |
|---|--------|-----|-------------|
| 18 | **Reporte Programación Semanal** | `GET /reportes/semanal` | Genera respuesta sin error |
| 19 | **Reporte CIC** | `GET /reportes/cic` | Genera respuesta sin error |

### FASE 7: APIs (verificar que respondan con datos del proyecto correcto)

Cada API se llama con `semana` del contexto de sesión y se verifica que la respuesta sea JSON válido con datos.

| # | API Endpoint | Método | Tabla global | Verificación |
|---|-------------|--------|-------------|-------------|
| 20 | `/api/semanal/list` | POST | `programacion_semanal` | JSON con array de actividades |
| 21 | `/api/cic/list` | POST | `cic` | JSON con array de subcontratistas CIC |
| 22 | `/api/cnc/list` | POST | `programacion_semanal` | JSON con actividades CNC |
| 23 | `/api/cnp/list` | POST | `programacion_semanal` | JSON con actividades CNP |
| 24 | `/api/subcontratistas/list` | POST | `subcontratistas` | JSON con array de subcontratistas |
| 25 | `/api/profesionales/list` | POST | `profesionales` | JSON con array de profesionales |
| 26 | `/api/contratos/list` | POST | `actividades` | JSON con actividades de contratos |
| 27 | `/api/listado-actividades/list` | POST | `actividades` | JSON con listado de actividades |
| 28 | `/api/pdc/list` | POST | `pdc` | JSON con elementos PDC |
| 29 | `/api/general/list` | POST | `programa_consolidado` | JSON con programa consolidado |
| 30 | `/api/control-cambios/list` | POST | `cambios` | JSON con control de cambios |
| 31 | `/api/pi/list` | GET | `programa_consolidado` | JSON con programa intermedio |

## Verificación de aislamiento (Paso 5)

### Test de aislamiento por tabla

Ejecutar queries SQL en Docker para comparar counts:

```sql
-- Contar filas por proyecto en cada tabla global clave
SELECT 'programacion_semanal' AS tabla, project_id, COUNT(*) AS cnt 
FROM programacion_semanal GROUP BY project_id ORDER BY project_id;

SELECT 'cic' AS tabla, project_id, COUNT(*) AS cnt 
FROM cic GROUP BY project_id ORDER BY project_id;

SELECT 'pdc' AS tabla, project_id, COUNT(*) AS cnt 
FROM pdc GROUP BY project_id ORDER BY project_id;

SELECT 'actividades' AS tabla, project_id, COUNT(*) AS cnt 
FROM actividades GROUP BY project_id ORDER BY project_id;

SELECT 'subcontratistas' AS tabla, project_id, COUNT(*) AS cnt 
FROM subcontratistas GROUP BY project_id ORDER BY project_id;

SELECT 'profesionales' AS tabla, project_id, COUNT(*) AS cnt 
FROM profesionales GROUP BY project_id ORDER BY project_id;
```

### Test de aislamiento via UI

1. Login como test.A
2. Seleccionar proyecto "Prueba" (project_id=27)
3. Navegar a Programación Semanal → contar actividades visibles
4. Comparar con query: `SELECT COUNT(*) FROM programacion_semanal WHERE project_id = 27 AND semana = [actual]`
5. Los counts deben coincidir (no hay datos de otros proyectos)
6. Repetir para: Programa General, CIC, PDC, Subcontratistas, Profesionales

### Test de cruzamiento entre proyectos

1. Login como test.A
2. Seleccionar proyecto "Prueba" → anotar count de actividades
3. Volver a /proyectos → seleccionar "Optimización Aeropuerto JMC" (project_id=68)
4. Verificar que los datos son diferentes (no son los de Prueba)
5. Si Optimización no tiene datos visibles, usar otro proyecto activo con datos

## Rollback plan

Si algo falla después de activar `USE_GLOBAL_TABLES=true`:

```bash
# 1. Quitar la línea del .env
docker exec last-planner-aia-app-1 sed -i '/USE_GLOBAL_TABLES/d' /var/www/html/.env

# 2. Reiniciar
docker restart last-planner-aia-app-1

# La app vuelve a usar tablas legacy inmediatamente
```

El fix en `SessionMiddleware.php` es seguro mantenerlo — cuando `USE_GLOBAL_TABLES=false`, `TableResolver::resolve()` ignora el contexto y devuelve tablas legacy como antes.

## Riesgos conocidos

1. **Legacy scripts**: Los scripts en `src/Legacy/` ya llaman `setProjectContext()` explícitamente. El fix en SessionMiddleware no afecta negativamente — solo agrega redundancia.
2. **INSERTs masivos**: El `Database::insertProjectId()` inyecta `project_id` como primera columna. Los INSERTs que ya tienen `project_id` en la columna se saltan (detecta y retorna sin cambios).
3. **DELETEs/UPDATEs**: `injectProjectId()` detecta si ya hay `project_id` en el WHERE y no duplica.
4. **Subqueries complejas**: Las INSERT...SELECT se saltan porque el SELECT ya maneja project_id via `queryWithProject()`.

## Criterio de éxito

- [ ] **Paso 0**: Verificación pre-activación: 16 tablas globales existen con datos
- [ ] **Paso 1**: Fix en SessionMiddleware.php aplicado correctamente (usa `\Database::getInstance()`)
- [ ] **Paso 2**: Fix en Database.php aplicado correctamente (auto-inject en `query()`)
- [ ] **Paso 3**: Smoke tests con `USE_GLOBAL_TABLES=false` pasan (regression check)
- [ ] **Paso 4**: `USE_GLOBAL_TABLES=true` activado en .env
- [ ] **Paso 5**: App reiniciada y operativa
- [ ] **Paso 6**: **FASE 1** (Core/Auth): 3/3 tests pasan
- [ ] **Paso 6**: **FASE 2** (Programación): 4/4 tests pasan
- [ ] **Paso 6**: **FASE 3** (Sub-módulos PS): 3/3 tests pasan
- [ ] **Paso 6**: **FASE 4** (Gestión): 6/6 tests pasan
- [ ] **Paso 6**: **FASE 5** (Integración): 1/1 tests pasan
- [ ] **Paso 6**: **FASE 6** (Reportes): 2/2 tests pasan
- [ ] **Paso 7**: **FASE 7** (APIs): 12/12 tests pasan
- [ ] **Paso 7**: APIs retornan solo datos del proyecto 27 (no datos de otros proyectos)
- [ ] **Paso 8**: Verificación de aislamiento: counts por proyecto coinciden
- [ ] **Paso 8**: Test de cruzamiento entre proyectos: datos son distintos
- [ ] Rollback funciona correctamente
