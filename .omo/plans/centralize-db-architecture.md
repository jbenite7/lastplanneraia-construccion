# centralize-db-architecture - Work Plan

## TL;DR (For humans)

**What you'll get:** Una base de datos unificada donde todos los proyectos comparten 16 tablas globales (en vez de 144 tablas separadas). Un sistema que automáticamente garantiza que ningún proyecto vea datos de otro, con cero interrupción del servicio durante la migración.

**Why this approach:** Migración dual-write progresiva: las 16 tablas globales se crean junto a las 144 existentes, cada operación escribe en ambas, y solo después de 8 semanas de verificación se retiran las viejas. Si algo falla, un flag toggle revierte todo en segundos. Es la única forma de eliminar los 4 riesgos críticos identificados por Metis y Momus: fuga de datos entre proyectos, pérdida de datos por schema drift, rollback imposible, y brechas de seguridad RBAC.

**What it will NOT do:** No eliminará las tablas viejas hasta verificación completa (8 semanas mínimo). No modificará la lógica de negocio — solo cambia cómo se accede a los datos. No permitirá queries sin project_id en el nuevo código (bloqueo en CI). No afectará staging/producción sin verificación previa en entorno de desarrollo.

**Effort:** Large (20+ tareas, 6 olas de ejecución, ~100 archivos modificados)
**Risk:** Medium — los 4 riesgos críticos se mitigan con dual-write, schema audit previo, Database wrapper con project_id automático, y cross-project leak tests como CI gate
**Decisions to sanity-check:** Duración del dual-write (8 semanas por defecto); estrategia para código legacy (actualizar vs puentear con VIEWs); solo migrar proyectos activos (9 de 9+)

Your next move: approve este plan, luego `$start-work centralize-db-architecture`. Detalle completo abajo.

---

> TL;DR (machine): Zero-downtime dual-write migration from 144 per-project tables to 16 global tables with project_id discriminator. 6 waves, 20+ tasks, 8-week safety window. All 4 critical + 6 high risks mitigated.

## Scope
### Must have
- Backup completo de `lastplanneraia_dev` como PRIMER paso (antes de cualquier modificación)
- 16 tablas globales con columna `project_id` y todos los proyectos activos (9) migrados
- `TableResolver` centralizado que resuelve nombres de tabla (`programa`, `actividades`, etc.)
- `Database` wrapper que inyecta automáticamente `project_id` en cada query
- Dual-write: cada INSERT/UPDATE escribe en tabla vieja Y tabla global
- Flag toggle para revertir lecturas a tablas viejas en segundos
- Cross-project leak tests: Playwright E2E que prueba que Proyecto A nunca ve datos de Proyecto B
- Performance benchmarks pre/post migración en todas las queries críticas
- Rollback drill completo ejecutado en staging antes de tocar producción
- Las tablas viejas se dropean solo tras 8 semanas de verificación sin incidencias

### Must NOT have (guardrails, anti-slop, scope boundaries)
- NO dropear tablas viejas hasta verificación completa (8 semanas mínimo post-cutover)
- NO permitir queries SQL sin `project_id` en el nuevo código — CI gate bloquea el PR
- NO permitir construcción de nombres de tabla en JavaScript — toda referencia a BD va por API
- NO modificar la lógica de negocio — solo cambia el mecanismo de acceso a datos
- NO hacer la migración en staging o producción sin verificación previa en entorno dev Docker
- NO ignorar código legacy — todo script en `src/Legacy/` y `construccion/` debe actualizarse
- NO hacer migration flag-day (big-bang) — siempre dual-write progresivo

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: tests-after + Playwright E2E + PHP smoke tests
- Framework: PHPUnit (PHP tests) + Playwright (browser E2E) + PHPStan level 3
- Evidence: `.omo/evidence/task-<N>-centralize-db-architecture.<log|json|md>`

## Execution strategy
### Parallel execution waves

**Wave 0 — Safety Net** (1 task) ✅ COMPLETE
- Task 0: Backup completo de BD

**Wave 1 — Audit** (2 tasks) ✅ COMPLETE
- Task 1: Schema audit — comparar 144 tablas
- Task 2: Corregir schema drift

**Wave 2 — Infrastructure** (3 tasks) ✅ COMPLETE
- Task 3: Crear `TableResolver` centralizado
- Task 4: Extender `Database` wrapper con `project_id` automático
- Task 5: Crear 16 tablas globales con índices compuestos

**Wave 2.5 — Pre-Migration Blockers** (3 tasks, secuencial — NUEVO)
- Task 6: Wire `setProjectContext()` en login/project selection flow
- Task 7: Añadir `resolveByPrefix()` a TableResolver + manejar INSERT project_id + cache
- Task 8: Inventario real de todas las queries a migrar (100+ archivos)

**Wave 3 — Data Migration** (3 tasks, secuencial — subió de orden)
- Task 9: Script de migración de datos (144 tablas → 16 globales con verificación)
- Task 10: Ejecutar migración en entorno dev Docker
- Task 11: Verificación de integridad post-migración + Foreign Keys

**Wave 4 — Query Migration** (5 tasks, secuencial con datos ya poblados — bajó de orden)
- Task 12: Migrar queries en `src/Services/`
- Task 13: Migrar queries en `src/Controllers/`
- Task 14: Migrar queries en `src/Legacy/`
- Task 15: Actualizar 43 patches SQL + global equivalents
- Task 16: Revisar views/ + admin/ + AJAX endpoints + reportes

**Wave 5 — Verification** (4 tasks, paralelo)
- Task 17: Rollback drill (probar revertir a tablas viejas)
- Task 18: Cross-project leak tests (Playwright E2E)
- Task 19: PHP smoke tests + Playwright + PHPStan
- Task 20: Performance benchmarks pre/post migración

**Wave 6 — Cutover** (2 tasks, secuencial)
- Task 21: Flag toggle global + monitoreo de queries viejas (MySQL general log)
- Task 22: Documentación de nueva arquitectura + cleanup

| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 0. Backup BD | - | 1 | - |
| 1. Schema audit | 0 | 2 | - |
| 2. Corregir drift | 1 | 3,4,5 | - |
| 3. TableResolver | 2 | 6,7,9 | 4,5 |
| 4. Database wrapper | 2 | 6,7,9 | 3,5 |
| 5. Crear 16 tablas globales | 2 | 6,9 | 3,4 |
| 6. Wire setProjectContext() | 3,4 | 7,9 | 5 |
| 7. resolveByPrefix + INSERT + cache | 3,4,6 | 8,9 | 5 |
| 8. Inventario real de queries | 7 | 12,13,14,15,16 | - |
| 9. Script migración datos | 4,5,6,7 | 10 | - |
| 10. Migrar datos (dev) | 9 | 11 | - |
| 11. Verificar integridad | 10 | 17,18,19,20 | - |
| 12. Migrar Services | 8,11 | - | 13,14,15,16 |
| 13. Migrar Controllers | 8,11 | - | 12,14,15,16 |
| 14. Migrar Legacy | 8,11 | - | 12,13,15,16 |
| 15. Actualizar 43 patches | 8,11 | - | 12,13,14,16 |
| 16. Revisar views/admin/AJAX | 8,11 | - | 12,13,14,15 |
| 17. Rollback drill | 11 | 21 | 18,19,20 |
| 18. Cross-project leak tests | 11 | 21 | 17,19,20 |
| 19. PHP smoke + Playwright + PHPStan | 12,13,14,15,16 | 21 | 17,18,20 |
| 20. Performance benchmarks | 11 | 21 | 17,18,19 |
| 21. Flag toggle + monitoreo | 17,18,19,20 | 22 | - |
| 22. Documentación + cleanup | 21 | - | - |

## Todos
> Implementation + Test = ONE todo. Never separate.

- [x] 0. Backup completo de `lastplanneraia_dev` + verificar integridad
  What to do / Must NOT do: Ejecutar `mysqldump` de toda la BD `lastplanneraia_dev` desde el contenedor Docker db. Verificar que el dump es restaurable (importar en una BD temporal de prueba). Guardar el dump en `database/backups/pre-migration-YYYYMMDD.sql`. NO modificar ningún dato ni tabla durante este paso.
  Parallelization: Wave 0 | Blocked by: none | Blocks: 1
  References: Docker container `last-planner-aia-db-1`, MySQL credentials from local environment, `docker-compose.yml`
  Acceptance criteria (agent-executable):
  - Dump generado sin errores: `docker exec last-planner-aia-db-1 mysqldump -uroot -p'<DB_ROOT_PASSWORD>' lastplanneraia_dev > database/backups/pre-migration-$(date +%Y%m%d).sql 2>&1; echo "EXIT: $?"`
  - Dump restaurable: crear BD temporal `lastplanneraia_dev_restore_test`, importar dump, verificar `SELECT COUNT(*) FROM general_proyectos_procesos` = 10
  - BD temporal dropeada después de verificar
  QA scenarios:
  - happy: Dump > 10MB, import exit 0, row count coincide → Evidence `.omo/evidence/task-0-backup.log`
  - failure: mysqldump error → verificar espacio en disco, permisos Docker, logs del contenedor
  Commit: N | infrastructure(db): pre-migration full backup

- [x] 1. Schema audit: comparar las 144 tablas de proyecto y detectar drift
  What to do / Must NOT do: Para cada uno de los 16 tipos de tabla (programa, actividades, cic, pdc, etc.), comparar la estructura (columnas, tipos, defaults, índices) entre los 9 proyectos activos. Generar un reporte JSON con diferencias detectadas. NO modificar ninguna tabla. Solo leer.
  Parallelization: Wave 1 | Blocked by: 0 | Blocks: 2
  References: Proyectos activos en `general_proyectos_procesos` (Ids 27,68,69,70,71,72,73,74,75), `information_schema.COLUMNS`, `information_schema.STATISTICS`
  Acceptance criteria (agent-executable):
  - Reporte `database/audit/schema-drift.json` generado con exactamente 16 secciones (una por tipo de tabla)
  - Cada sección lista los 9 proyectos y sus columnas/índices, marcando diferencias
  - Si hay drift: reporte incluye SQL correctivo para cada diferencia
  QA scenarios:
  - happy: 0 diferencias → reporte muestra "NO DRIFT DETECTED" en las 16 secciones
  - failure: Hay drift → reporte muestra exactamente qué columnas/índices faltan o difieren en qué proyectos
  - Evidence: `database/audit/schema-drift.json`
  Commit: N | quality(db): schema drift audit report

- [x] 2. Corregir schema drift (solo si Task 1 detectó diferencias)
  What to do / Must NOT do: Aplicar los ALTER TABLE correctivos generados por Task 1 a las tablas de proyecto que tengan columnas faltantes o tipos divergentes. Usar `IF NOT EXISTS` / `information_schema` guards para idempotencia. SOLO añadir columnas faltantes o corregir tipos — NUNCA dropear columnas existentes ni modificar datos. Si el drift es estructuralmente incompatible (ej. mismo nombre de columna con tipo radicalmente distinto), documentarlo como excepción y NO forzar el cambio.
  Parallelization: Wave 1 | Blocked by: 1 | Blocks: 3,4,5
  References: `database/audit/schema-drift.json` (generado por Task 1), `information_schema.COLUMNS`, Docker db container
  Acceptance criteria (agent-executable):
  - Re-ejecutar Task 1 (schema audit) post-corrección → reporte muestra "NO DRIFT DETECTED" en las 16 secciones
  - Todos los ALTER TABLE ejecutados con éxito (errores "Duplicate column" son aceptables si la columna ya existe)
  QA scenarios:
  - happy: 0 diferencias tras corrección → schema-audit re-run limpio
  - failure: ALTER TABLE falla en alguna tabla → documentar la excepción en el reporte, NO forzar
  - Evidence: `database/audit/schema-drift-post-fix.json`
  Commit: Y | fix(db): resolve schema drift across project tables

- [x] 3. Crear `TableResolver` — punto único de resolución de nombres de tabla
  What to do / Must NOT do: Crear `src/Core/TableResolver.php` con un método estático `resolve(string $projectId, string $tableType): string` que devuelve el nombre de tabla correcto según el flag de feature toggle. Implementar un flag `USE_GLOBAL_TABLES` (`.env` o config) que cuando es `false` devuelve `{$prefix}_{$tableType}` (comportamiento actual) y cuando es `true` devuelve `$tableType` (tabla global). Lista de 16 `tableType` válidos. Lanzar excepción si `tableType` no es válido. NO modificar ningún otro archivo aún.
  Parallelization: Wave 2 | Blocked by: 2 | Blocks: 6,7,8,9
  References: `admin/src/Models/Project.php:getProjectTableQueries()` (lista de 16 tablas), `general_proyectos_procesos.Base_de_Datos` (prefijo → project_id mapping), `src/Core/Database.php`
  Acceptance criteria (agent-executable):
  - `TableResolver::resolve(27, 'programa')` con flag OFF → `prueba_programa`
  - `TableResolver::resolve(27, 'programa')` con flag ON → `programa`
  - `TableResolver::resolve(75, 'programacion_semanal')` con flag ON → `programacion_semanal`
  - `TableResolver::resolve(999, 'programa')` → exception (project not found)
  - `TableResolver::resolve(27, 'tabla_inexistente')` → exception (invalid table type)
  QA scenarios:
  - happy: Todas las assertions pasan → test unitario en `tests/TableResolverTest.php`
  - failure: Flag no existe en .env → usar default OFF
  - Evidence: `tests/TableResolverTest.php` (creado como parte de este task)
  Commit: Y | feat(core): add TableResolver for centralized table name resolution

- [x] 4. Extender `Database` wrapper con inyección automática de `project_id`
  What to do / Must NOT do: Modificar `src/Core/Database.php` para añadir: (a) propiedad `$currentProjectId` settable via `setProjectContext(int $projectId)`, (b) método `queryWithProject(string $sql, array $params, ?int $projectId = null)` que automáticamente inyecta `project_id = ?` en el WHERE de la query si no está ya presente, (c) detector de queries peligrosas (SELECT/UPDATE/DELETE sobre las 16 tablas globales sin project_id → log warning en modo ON, excepción en modo strict). NO romper el método `query()` existente — añadir el nuevo como método separado.
  Parallelization: Wave 2 | Blocked by: 2 | Blocks: 6,7,8,9,11
  References: `src/Core/Database.php:53-95` (método query existente), `src/Core/TableResolver.php` (creado en Task 3)
  Acceptance criteria (agent-executable):
  - `$db->setProjectContext(27); $db->queryWithProject("SELECT * FROM programa")` → ejecuta `SELECT * FROM programa WHERE project_id = 27`
  - `$db->queryWithProject("SELECT * FROM programa WHERE project_id = ?", [27])` → NO duplica el WHERE (detecta que ya existe)
  - `$db->queryWithProject("DELETE FROM programa")` sin project_id → log warning (modo ON) o exception (modo strict)
  - El método `query()` original sigue funcionando igual (sin inyección)
  QA scenarios:
  - happy: Query con project_id inyectado correctamente, sin duplicación
  - failure: SQL malformado → la inyección falla gracefully, loguea el error, lanza DatabaseException
  - Evidence: `tests/DatabaseWrapperTest.php`
  Commit: Y | feat(core): add project_id auto-injection to Database wrapper

- [x] 5. Crear las 16 tablas globales con índices compuestos
  What to do / Must NOT do: Crear las 16 tablas globales (`programa`, `actividades`, `cambios`, `cic`, `pdc`, `profesionales`, `programacion_semanal`, `programa_consolidado`, `semanas_activas`, `subcontratistas`, `auto_program_log`, `lps_drawer_comentarios`, `lps_escalamientos`, `pg_tracking`, `pi_shared_constraints`, `pi_shared_constraint_links`) en `lastplanneraia_dev`. Todas con `CREATE TABLE IF NOT EXISTS`. Cada tabla incluye: `project_id INT NOT NULL` como primera columna, índices compuestos `(project_id, ...)` para las queries más frecuentes, y AUTO_INCREMENT en `id` global (no por proyecto). NO crear foreign keys aún (se añaden post-migración en Task 13). NO dropear las tablas viejas.
  Parallelization: Wave 2 | Blocked by: 2 | Blocks: 11
  References: `admin/src/Models/Project.php:446-802` (CREATE TABLE SQL para las 10 base), `admin/src/Models/Project.php:933-1065` (SQL para programacion_semanal, cic con columnas PC), `database/patches/20260624_pc_reponer_tablas_faltantes.sql` (6 tablas extras)
  Acceptance criteria (agent-executable):
  - `SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='lastplanneraia_dev' AND TABLE_NAME IN ('programa', 'actividades', 'cambios', 'cic', 'pdc', 'profesionales', 'programacion_semanal', 'programa_consolidado', 'semanas_activas', 'subcontratistas', 'auto_program_log', 'lps_drawer_comentarios', 'lps_escalamientos', 'pg_tracking', 'pi_shared_constraints', 'pi_shared_constraint_links')` → 16
  - Cada tabla tiene columna `project_id INT NOT NULL` → verificado con `SHOW COLUMNS FROM <table> LIKE 'project_id'`
  - Cada tabla tiene al menos 1 índice compuesto con `project_id`
  QA scenarios:
  - happy: 16 tablas creadas con project_id + índices → 0 errores
  - failure: CREATE TABLE falla → verificar que no existan ya (IF NOT EXISTS), permisos MySQL
  - Evidence: `database/migrations/001_create_global_tables.sql` (el script SQL usado)
  Commit: Y | feat(db): create 16 global tables with project_id and composite indexes

- [x] 6. Wire `setProjectContext()` en login/project selection flow
  What to do / Must NOT do: Modificar el login/project-selection flow para que llame a `Database::getInstance()->setProjectContext($projectId)` en cuanto se determine qué proyecto está viendo el usuario. Esto es crítico (Momus blocker #2): actualmente `setProjectContext()` tiene 0 calls en producción — sin esto, `queryWithProject()` no inyecta `project_id`. Típicamente en `src/Controllers/Auth/LoginController.php` o un middleware de sesión. El `$projectId` se obtiene de `$_SESSION['db']` (prefijo) → `TableResolver::getProjectIdByPrefix()`. NO modificar la lógica de login.
  Parallelization: Wave 2.5 | Blocked by: 3,4 | Blocks: 7,9
  References: `src/Core/Database.php` (`setProjectContext` definido), `src/Core/TableResolver.php` (`getProjectIdByPrefix`), `src/Controllers/Auth/LoginController.php`, `public/index.php` (front controller)
  Acceptance criteria (agent-executable):
  - Después de login, `Database::getInstance()->getCurrentProjectId()` devuelve el ID del proyecto seleccionado
  - `queryWithProject("SELECT * FROM programa")` ejecuta con `project_id` correcto
  - Sin usuario logueado: `getCurrentProjectId()` es null → `queryWithProject()` log warning pero no crashea
  QA scenarios:
  - happy: Login correcto → project_id seteado → queries scoped
  - failure: Login sin proyecto → warning logueado (no crash)
  - Evidence: `.omo/evidence/task-6-setProjectContext.log`
  Commit: Y | fix(core): wire setProjectContext into project selection flow

- [x] 7. Añadir `resolveByPrefix()` + manejar INSERT project_id + caching
  What to do / Must NOT do:
  (a) Añadir método `resolveByPrefix(string $prefix, string $tableType): string` a `TableResolver::class` — acepta prefijo en vez de project_id, útil para legacy code que solo tiene `$dbName`. Implementación: `return self::useGlobalTables() ? $tableType : ($prefix . '_' . $tableType);` (sin query a BD).
  (b) Añadir `insertProjectId(string $sql, int $projectId, array $columns): string` a `Database::class` — transforma INSERTs para incluir `project_id` como columna: `INSERT INTO programa (col1, col2) VALUES (?, ?)` → `INSERT INTO programa (project_id, col1, col2) VALUES (?, ?, ?)` y añade `$projectId` al array de params. Esto resuelve el blocker #3 (Metis + Momus): INSERT no tiene WHERE, no se puede inyectar con `injectProjectId()`.
  (c) Añadir static cache en `TableResolver::resolve()`: `private static array $prefixCache = []` — en flag OFF mode, cachea el resultado de la query a `general_proyectos_procesos` para evitar 50+ queries idénticas por page load.
  Parallelization: Wave 2.5 | Blocked by: 3,4,6 | Blocks: 8,9
  References: `src/Core/TableResolver.php`, `src/Core/Database.php`
  Acceptance criteria (agent-executable):
  - `TableResolver::resolveByPrefix('prueba', 'programa')` con flag OFF → `prueba_programa`
  - `TableResolver::resolveByPrefix('prueba', 'programa')` con flag ON → `programa`
  - `insertProjectId("INSERT INTO programa (actividad, fecha) VALUES (?, ?)", 27, [])` → `INSERT INTO programa (project_id, actividad, fecha) VALUES (?, ?, ?)` + params `[27, ?, ?]`
  - Cache test: resolver mismo prefix 100 veces toma < 5ms (sin cache son ~500ms)
  QA scenarios:
  - happy: INSERT transformado correctamente, cache reduce queries
  - failure: INSERT con ON DUPLICATE KEY no se rompe (test específico)
  - Evidence: pruebas agregadas a `tests/TableResolverTest.php` y `tests/DatabaseWrapperTest.php`
  Commit: Y | feat(core): add resolveByPrefix, insertProjectId, and static cache

- [x] 8. Inventario real de todas las queries a migrar
  What to do / Must NOT do: Hacer un barrido exhaustivo (grep) de TODOS los archivos PHP que construyan nombres de tabla con variables (`$dbName`, `$dbPrefix`, `$prefix`, `{$db}_`, `{$dbName}_`). Categorizar: Services, Controllers, Legacy, Patches, Views, Admin, Reportes, JS. Generar un reporte `database/audit/query-inventory.json` con: archivo, línea, patrón de tabla, tipo de operación (SELECT/INSERT/UPDATE/DELETE), complejidad (simple/medium/complex). Esto resuelve el hallazgo de Momus de que la superficie real es 4x mayor (~100 archivos vs 29 estimados).
  Parallelization: Wave 2.5 | Blocked by: 7 | Blocks: 12,13,14,15,16
  References: Todo `src/`, `construccion/` (si existe), `admin/`, `public/js/`, `views/` (4 files tienen Database::getInstance())
  Acceptance criteria (agent-executable):
  - `database/audit/query-inventory.json` generado con archivo, línea, patrón, tipo
  - Categorizado por capa (Services / Controllers / Legacy / Patches / Views / Admin / JS)
  - Total de archivos reportados coincide con el escaneo
  QA scenarios:
  - happy: Inventario completo con todos los archivos identificados
  - failure: Faltan archivos → re-ejecutar grep con patrones adicionales
  - Evidence: `database/audit/query-inventory.json`
  Commit: N | docs: query inventory report

- [x] 9. Crear script de migración de datos (144 tablas → 16 globales)
  What to do / Must NOT do: Crear `database/migrations/002_migrate_data.sql` que, para cada uno de los 9 proyectos activos, haga `INSERT INTO <global_table> (project_id, col1, col2, ...) SELECT <project_id>, col1, col2, ... FROM <old_table>`. Usar `INSERT IGNORE` para idempotencia. Incluir: (a) mapeo de project_id desde `general_proyectos_procesos.Id`, (b) verificación de row count antes y después, (c) solo migrar proyectos con `Activo=1`. Este script es SOLO generación — NO se ejecuta aún.
  Parallelization: Wave 3 | Blocked by: 4,5,6,7 | Blocks: 10
  References: `general_proyectos_procesos` (Ids 27,68,69,70,71,72,73,74,75), `admin/src/Models/Project.php:getProjectTableQueries()` (16 tipos de tabla)
  Acceptance criteria (agent-executable):
  - El script generado tiene exactamente 9 proyectos × 16 tablas = 144 bloques INSERT INTO...SELECT
  - Cada bloque incluye un comentario con el nombre del proyecto y conteo esperado de filas
  - El script incluye `SELECT COUNT(*)` pre y post para cada tabla global
  QA scenarios:
  - happy: Script se genera sin errores, revisión manual de 2-3 bloques muestra SQL correcto
  - failure: Alguna tabla vieja no existe para algún proyecto → skip con warning, documentar en el script
  - Evidence: `database/migrations/002_migrate_data.sql`
  Commit: Y | feat(db): data migration script from per-project tables to global tables

- [x] 10. Ejecutar migración de datos en entorno dev Docker
  What to do / Must NOT do: Ejecutar `database/migrations/002_migrate_data.sql` contra `lastplanneraia_dev` en Docker. Esto inserta los datos de las 144 tablas viejas en las 16 tablas globales. Las tablas viejas NO se dropean. Verificar que las queries de lectura funcionan con el flag ON. Usar `INSERT IGNORE` — si falla alguna, documentar la excepción.
  Parallelization: Wave 3 | Blocked by: 9 | Blocks: 11
  References: `database/migrations/002_migrate_data.sql`, Docker db container, flag `USE_GLOBAL_TABLES=true` en `.env`
  Acceptance criteria (agent-executable):
  - Row count pre-migración (tablas viejas) = row count post-migración (tablas globales agrupadas por project_id)
  - `SELECT COUNT(*) FROM programa WHERE project_id=27` = `SELECT COUNT(*) FROM prueba_programa`
  - Verificar para 3 proyectos aleatorios + 3 tablas aleatorias (9 verificaciones)
  QA scenarios:
  - happy: Todos los conteos coinciden → migración exitosa
  - failure: Conteo no coincide para alguna tabla → investigar schema drift residual, INSERT IGNORE silenciando errores
  - Evidence: `.omo/evidence/task-10-data-migration.log`
  Commit: N | infrastructure(db): execute data migration in dev environment

- [x] 11. Verificación de integridad post-migración + Foreign Keys
  What to do / Must NOT do: (a) Verificar integridad: `CHECKSUM TABLE` para cada tabla global vs suma de checksums de tablas viejas. (b) Añadir foreign keys en tablas globales: `programa_consolidado.project_id → programa.project_id`, `actividades.programa_id → programa.id`, etc. Usar `ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY ... ON DELETE CASCADE`. (c) Verificar que no hay orphan rows (ej. `actividades` que referencian `programa.id` inexistente). Si hay orphans, documentarlos y decidir: eliminar o marcar con project_id=0.
  Parallelization: Wave 3 | Blocked by: 10 | Blocks: 17,18,19,20
  References: `database/migrations/003_add_foreign_keys.sql` (a crear), relaciones implícitas entre tablas de proyecto
  Acceptance criteria (agent-executable):
  - `CHECKSUM TABLE programa` global = suma de checksums de `prueba_programa + optimizacionJMC_programa + ...` (9 proyectos)
  - Foreign keys añadidas sin errores
  - `SELECT COUNT(*) FROM actividades a LEFT JOIN programa p ON a.programa_id = p.id AND a.project_id = p.project_id WHERE p.id IS NULL` → 0 (sin orphans)
  QA scenarios:
  - happy: Checksums coinciden, 0 orphans, FKs creadas
  - failure: Orphans detectados → script `fix_orphans.sql` los corrige o documenta
  - Evidence: `database/migrations/003_add_foreign_keys.sql`, `.omo/evidence/task-11-integrity.log`
  Commit: Y | feat(db): add foreign keys and verify data integrity post-migration

- [x] 12. Migrar queries en `src/Services/` para usar `TableResolver` + `queryWithProject`
  What to do / Must NOT do: En cada archivo PHP dentro de `src/Services/` que construya nombres de tabla con variables (`$prefix`, `$dbName`, `$this->dbName`, etc.), reemplazar por `TableResolver::resolve($projectId, 'tipo_tabla')` y cambiar `$db->query($sql)` por `$db->queryWithProject($sql, $params)`. Archivos inventariados en Task 8. NO modificar la lógica de negocio. NO romper la compatibilidad con el flag OFF (las queries deben funcionar igual con el flag apagado).
  Parallelization: Wave 4 | Blocked by: 8,11 | Blocks: 19 | Can parallel: 13,14,15,16
  References: `database/audit/query-inventory.json` (generado por Task 8), `src/Core/TableResolver.php`, `src/Core/Database.php`
  Acceptance criteria (agent-executable):
  - Con flag OFF: todas las queries generan los mismos nombres de tabla que antes (comportamiento idéntico)
  - Con flag ON: todas las queries usan nombres de tabla globales + project_id
  - PHPStan level 3 en archivos modificados → 0 errores NUEVOS
  QA scenarios:
  - happy: Flag OFF → queries legacy funcionan. Flag ON → queries globales funcionan
  - failure: Alguna query usa patrón no estándar → log warning, documentar en el reporte
  - Evidence: `.omo/evidence/task-12-services-migration.diff`
  Commit: Y | refactor(services): use TableResolver and queryWithProject for DB access

 - [x] 13. Migrar queries en `src/Controllers/` para usar `TableResolver` + `queryWithProject`
  What to do / Must NOT do: Igual que Task 12 pero en `src/Controllers/`. Archivos inventariados en Task 8. Mismo patrón: `TableResolver::resolve()` + `queryWithProject()`. El `LoginController` es especial — la query de login usa `general_usuarios`, no necesita modificación.
  Parallelization: Wave 4 | Blocked by: 8,11 | Blocks: 19 | Can parallel: 12,14,15,16
  References: `database/audit/query-inventory.json`, `src/Controllers/`
  Acceptance criteria (agent-executable):
  - Flag OFF: comportamiento idéntico al actual
  - Flag ON: queries globales con project_id
  - PHPStan level 3 en archivos modificados → 0 errores NUEVOS
  QA scenarios:
  - happy: Flag toggle ON/OFF → ambos modos funcionales
  - failure: Controller que construye tabla vía múltiples capas → tracing manual, documentar
  - Evidence: `.omo/evidence/task-13-controllers-migration.diff`
  Commit: Y | refactor(controllers): use TableResolver and queryWithProject for DB access

 - [x] 14. Migrar queries en `src/Legacy/` para usar `TableResolver` + `queryWithProject`
  What to do / Must NOT do: Actualizar TODOS los scripts en `src/Legacy/` que construyen nombres de tabla con `$db` o `$dbName`. Archivos inventariados en Task 8. El patrón `{$db}_semanas_activas` se convierte en `TableResolver::resolve($projectId, 'semanas_activas')` o `TableResolver::resolveByPrefix($dbName, 'semanas_activas')`. Usar `queryWithProject()` para inyección de `project_id`. Para INSERTs: usar `insertProjectId()`. NO romper la lógica de estos scripts (son críticos para la operación semanal).
  Parallelization: Wave 4 | Blocked by: 8,11 | Blocks: 19 | Can parallel: 12,13,15,16
  References: `database/audit/query-inventory.json`, `src/Legacy/nueva_semana.php:40-177`, `src/Legacy/autoprogramar_actividades.php:29-248`
  Acceptance criteria (agent-executable):
  - Flag OFF: comportamiento idéntico al actual en todos los scripts
  - Flag ON: queries globales con project_id
  - Ejecutar `test_weekly_governance.php` y `test_auto_definir_contratos.php` → ambos PASS
  QA scenarios:
  - happy: Tests semanales pasan en ambos modos (ON/OFF)
  - failure: Algún script usa `$db` de forma no estándar → documentar y adaptar
  - Evidence: `.omo/evidence/task-14-legacy-migration.diff`
  Commit: Y | refactor(legacy): use TableResolver and queryWithProject for DB access

 - [x] 15. Actualizar 43 patches SQL para esquema global
  What to do / Must NOT do: Revisar cada uno de los 43 archivos en `database/patches/`. Para cada patch: (a) si usa `CREATE TABLE {prefix}_*` → reescribir para crear la tabla global (si no existe) o añadir columnas a la tabla global, (b) si usa `ALTER TABLE {prefix}_*` → reescribir para `ALTER TABLE <global_table>`, (c) si usa `INSERT/UPDATE {prefix}_*` → añadir `project_id` o convertir a operación global, (d) si el patch ya fue aplicado en la BD actual y es solo migración de datos → marcarlo como "applied-legacy". NO modificar los patches originales — crear nuevos archivos `database/patches/global/` con el mismo nombre + sufijo `_global.sql`.
  Parallelization: Wave 4 | Blocked by: 8,11 | Blocks: 19 | Can parallel: 12,13,14,16
  References: `database/patches/` (43 archivos), `database/audit/query-inventory.json`
  Acceptance criteria (agent-executable):
  - `ls database/patches/global/ | wc -l` → >= 43 (un equivalente global por cada patch original)
  - Cada patch global tiene un comentario header explicando la transformación aplicada
  - Los patches que son "applied-legacy" tienen un archivo `.applied` marcador
  QA scenarios:
  - happy: 43 archivos globales creados, cada uno revisado y documentado
  - failure: Patch con lógica inaplicable al modelo global → documentar, crear issue de seguimiento
  - Evidence: `database/patches/global/` directory + `database/patches/global/README.md`
  Commit: Y | refactor(patches): create global-table equivalents for all 43 SQL patches

 - [x] 16. Revisar views/ + admin/ + AJAX endpoints + reportes
  What to do / Must NOT do: Revisar los archivos adicionales que Metis identificó con queries a BD: views (4 archivos con `Database::getInstance()`), admin/ (formularios de creación/edición de proyectos), AJAX handlers, scripts de exportación Excel/PDF. Aplicar mismo patrón que Tasks 12-14. Si algún archivo construye nombre de tabla, migrar. Si no construye (usa solo tablas `general_*`), verificar y documentar.
  Parallelization: Wave 4 | Blocked by: 8,11 | Blocks: 19 | Can parallel: 12,13,14,15
  References: `database/audit/query-inventory.json`, `views/`, `admin/`, `public/js/`
  Acceptance criteria (agent-executable):
  - Todos los archivos con queries a BD de proyecto migrados a TableResolver
  - Archivos sin queries de proyecto verificados y documentados
  QA scenarios:
  - happy: Todos los archivos migrados o documentados
  - failure: Se omite algún archivo → detectado en MySQL general log (Task 21)
  - Evidence: `.omo/evidence/task-16-extra-files.log`
  Commit: Y | refactor(misc): migrate views, admin forms, and report exports

- [ ] 17. Rollback drill: probar revertir a tablas viejas
  What to do / Must NOT do: Simular un escenario de rollback: (1) cambiar `USE_GLOBAL_TABLES=false` en `.env`, (2) verificar que TODAS las queries vuelven a usar tablas viejas, (3) ejecutar PHP smoke tests y Playwright E2E con flag OFF, (4) verificar que los datos en tablas viejas siguen intactos (no fueron modificados por la migración). Esto prueba que el rollback es viable en < 60 segundos.
  Parallelization: Wave 5 | Blocked by: 11 | Blocks: 21 | Can parallel: 18,19,20
  References: `.env` flag `USE_GLOBAL_TABLES`, `TableResolver.php`, `tests/test_pi_shared_payload_smoke.php`, `tests/browser/preconstruccion-full-cycle.mjs`
  Acceptance criteria (agent-executable):
  - Flag OFF → `TableResolver::resolve(27, 'programa')` devuelve `prueba_programa`
  - PHP smoke tests: 3/3 PASS con flag OFF
  - Playwright E2E: 21/21 PASS con flag OFF
  - Datos en tablas viejas sin cambios: `CHECKSUM TABLE prueba_programa` antes y después de la migración = idéntico
  QA scenarios:
  - happy: Rollback en < 60s, todos los tests pasan, datos intactos
  - failure: Algún test falla → indica que las queries no respetan el flag correctamente
  - Evidence: `.omo/evidence/task-17-rollback-drill.log`
  Commit: N | test: rollback drill verifies flag toggle restores old behavior

- [ ] 18. Cross-project leak tests — Playwright E2E
  What to do / Must NOT do: Crear `tests/browser/cross-project-isolation.mjs` con tests que: (a) crean datos únicos en Proyecto A (prueba, Id 27), (b) inician sesión como usuario de Proyecto B (da_porto, Id 73), (c) verifican que en NINGUNA pantalla (PG, PI, PS, PDC, CIC, reportes) aparecen los datos de Proyecto A. Repetir para combinación Pre-Construccion vs Construccion. Debe cubrir TODAS las 16 tablas.
  Parallelization: Wave 5 | Blocked by: 11 | Blocks: 21 | Can parallel: 17,19,20
  References: `tests/browser/preconstruccion-full-cycle.mjs` (patrón de tests), `playwright.config.mjs`
  Acceptance criteria (agent-executable):
  - `npx playwright test tests/browser/cross-project-isolation.mjs` → todos los tests PASS
  - Al menos 1 test por cada una de las 16 tablas (16+ tests)
  - Cada test: crea dato único en proyecto A, verifica que proyecto B NO lo ve
  QA scenarios:
  - happy: Todos los tests de aislamiento pasan → 0 leaks
  - failure: Algún test detecta datos de otro proyecto → CRÍTICO: bloquear deploy hasta resolver
  - Evidence: `tests/browser/cross-project-isolation.mjs` + `.omo/evidence/task-18-leak-tests.log`
  Commit: Y | test(e2e): add cross-project data isolation verification

- [ ] 19. PHP smoke tests + Playwright + PHPStan (flag ON y OFF)
  What to do / Must NOT do: Ejecutar TODOS los tests en AMBOS modos:
  (a) PHP smoke: `test_pi_shared_payload_smoke.php` (4/4), `test_weekly_governance.php` (9/9), `test_auto_definir_contratos.php` (14/14)
  (b) Playwright: `preconstruccion-full-cycle.mjs` (21/21) + `test-pg-color-fix.mjs` (1/1)
  (c) PHPStan level 3 en TODOS los archivos modificados (Tasks 3-16)
  Si algún test falla en modo ON pero pasa en modo OFF → error de migración de queries.
  Parallelization: Wave 5 | Blocked by: 12,13,14,15,16 | Blocks: 21 | Can parallel: 17,18,20
  References: `tests/test_pi_shared_payload_smoke.php`, `tests/browser/preconstruccion-full-cycle.mjs`, `vendor/bin/phpstan`
  Acceptance criteria (agent-executable):
  - 3/3 PHP suites PASS en flag OFF y flag ON
  - 21/21 Playwright PASS en flag OFF y flag ON
  - PHPStan: 0 errores NUEVOS en archivos modificados
  QA scenarios:
  - happy: Todos los tests pasan en ambos modos → migración correcta
  - failure: Test pasa en OFF pero falla en ON → query mal migrada
  - Evidence: `.omo/evidence/task-19-full-regression.log`
  Commit: N | test: full regression across both flag states

- [ ] 20. Performance benchmarks pre/post migración
  What to do / Must NOT do: (a) Capturar EXPLAIN plans de las 20 queries más frecuentes ANTES de la migración (con flag OFF). (b) Capturar EXPLAIN plans de las mismas queries DESPUÉS (con flag ON). (c) Comparar: rows examined, index used, query cost. (d) Si alguna query degrada > 2x, optimizar índice o query. (e) Ejecutar cada query 100 veces y medir tiempo promedio pre/post. Generar reporte `database/audit/performance-benchmark.json`.
  Parallelization: Wave 5 | Blocked by: 11 | Blocks: 21 | Can parallel: 17,18,19
  References: MySQL `EXPLAIN`, `SHOW PROFILES`, queries más frecuentes identificadas en Tasks 12-16
  Acceptance criteria (agent-executable):
  - Reporte generado con 20 queries × 2 modos = 40 EXPLAIN plans
  - Ninguna query degrada > 2x en rows examined
  - Tiempo promedio post no excede 1.5x el tiempo pre para ninguna query
  QA scenarios:
  - happy: Performance equivalente o mejor → índices compuestos funcionan
  - failure: Query degrada > 2x → requiere optimización de índice o reescritura
  - Evidence: `database/audit/performance-benchmark.json`
  Commit: N | perf(db): pre/post migration performance benchmark

- [ ] 21. Flag toggle global + monitoreo de queries viejas (MySQL general log)
  What to do / Must NOT do: (a) Activar `USE_GLOBAL_TABLES=true` en `.env`. (b) Habilitar MySQL General Query Log en modo staging: `SET GLOBAL general_log = 'ON'; SET GLOBAL log_output = 'TABLE';`. (c) Ejecutar una regresión completa (todos los PHP tests + Playwright E2E). (d) Analizar el query log: `SELECT argument FROM mysql.general_log WHERE argument LIKE '%prueba_programa%' OR argument LIKE '%da_porto_actividades%'` → cualquier resultado indica una query NO migrada. (e) Corregir queries detectadas. (f) Deshabilitar query log.
  Parallelization: Wave 6 | Blocked by: 17,18,19,20 | Blocks: 22
  References: MySQL `general_log` table, `.env` flag, `database/audit/query-inventory.json`
  Acceptance criteria (agent-executable):
  - General query log NO contiene queries a tablas viejas después de la regresión completa
  - Si contiene: cada query detectada es corregida y la verificación se re-ejecuta
  - Flag ON: `TableResolver::resolve(27, 'programa')` → `programa`
  QA scenarios:
  - happy: 0 queries a tablas viejas en el log → migración 100% completa
  - failure: Queries viejas detectadas → se corrigen y se re-verifica (loop hasta 0)
  - Evidence: `.omo/evidence/task-21-query-log-audit.log`
  Commit: Y | feat(config): enable global table flag and verify zero legacy queries

- [ ] 22. Documentación de nueva arquitectura + cleanup
  What to do / Must NOT do: Crear `docs/database-architecture.md` documentando: (a) las 16 tablas globales y su propósito, (b) el sistema de `TableResolver` y flag toggle, (c) el `Database` wrapper con inyección de project_id, (d) cómo añadir una nueva tabla en el futuro, (e) cómo añadir un nuevo proyecto, (f) cómo funciona el dual-write y cuándo se retiran las tablas viejas, (g) el plan de retiro de tablas viejas (fecha objetivo: 8 semanas post-cutover). Actualizar `GEMINI.md` y `README.md` si es necesario.
  Parallelization: Wave 6 | Blocked by: 21 | Blocks: none
  References: `docs/`, `GEMINI.md`, `README.md`, `src/Core/TableResolver.php`, `src/Core/Database.php`
  Acceptance criteria (agent-executable):
  - `docs/database-architecture.md` existe con todas las secciones (a)-(g)
  - `GEMINI.md` referencia la nueva arquitectura
  QA scenarios:
  - happy: Documentación completa, otro developer puede entender la arquitectura sin preguntar
  - failure: Sección faltante → completar antes de cerrar
  - Evidence: `docs/database-architecture.md`
  Commit: Y | docs: document centralized database architecture and migration

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE. Surface results and wait for the user's explicit okay before declaring complete.
- [ ] F1. Plan compliance audit: Los 21 tasks completados, toda la evidencia en `.omo/evidence/`, ningún paso saltado. Cada task tiene su archivo de evidencia.
- [ ] F2. Code quality review: PHPStan 0 errores nuevos, todos los tests pasan (ON y OFF), cross-project leak tests 100% pass.
- [ ] F3. Real manual QA: Login en `http://localhost:8081` con test.A, ver 3 proyectos diferentes, confirmar que Programa General carga datos correctos y distintos por proyecto.
- [ ] F4. Scope fidelity: Las 144 tablas viejas siguen intactas (no dropeadas), el flag toggle funciona, la BD tiene tanto tablas viejas como globales con datos consistentes. Cero cambios a lógica de negocio — solo cambió el mecanismo de acceso a datos.

## Commit strategy
Commits atómicos por task, mensajes conventional commits. Feature branch `opencode/centralize-db`. NO merge hasta verificación completa. NO squash hasta que el dual-write complete 8 semanas.

## Success criteria
1. Backup completo generado y verificado como restaurable
2. Schema audit: 0 drift entre proyectos (o drift documentado y corregido)
3. TableResolver funcional con flag toggle ON/OFF + `resolveByPrefix()` + cache
4. Database wrapper inyecta project_id automáticamente (SELECT/UPDATE/DELETE) + `insertProjectId()` para INSERTs
5. 16 tablas globales creadas con índices compuestos
6. `setProjectContext()` wired en login/project selection flow (Momus blocker #2 resuelto)
7. INSERT project_id manejado (Momus blocker #3 resuelto)
8. Inventario real de queries generado (Momus blocker #4 resuelto)
9. ~100+ archivos PHP migrados a TableResolver + queryWithProject
10. 43 patches actualizados con equivalentes globales
11. Datos migrados: row counts coinciden entre tablas viejas y globales
12. Foreign keys añadidas, 0 orphan rows
13. Rollback drill: flag OFF → todos los tests pasan en < 60s
14. Cross-project leak tests: 16+ tests, 0 leaks detectados
15. PHP smoke tests: 3/3 PASS en ambos modos (ON y OFF)
16. Performance: ninguna query degrada > 2x post-migración
17. PHPStan: 0 errores nuevos en archivos modificados
18. MySQL query log: 0 queries a tablas viejas con flag ON
19. Documentación de arquitectura completa en `docs/database-architecture.md`
