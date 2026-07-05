# Facts — Migración definitiva a tablas globales

- Se activa USE_GLOBAL_TABLES=true en .env de local, pruebas y producción.
- Después de validar en producción, se elimina el toggle: TableResolver hardcodea useGlobalTables=true y se borra la lógica de resolución por prefijo (resolveByPrefix con prefijo, prefixCache, consulta a general_proyectos_procesos.Base_de_Datos).
- Project.php deja de crear tablas {prefix}_* cuando USE_GLOBAL_TABLES=true. La rama legacy de getProjectTableQueries() se salta condicionalmente. En la fase B se elimina permanentemente.
- Todas las 46 tablas en TableResolver::$validTables se manejan como globales. Ninguna tabla operativa queda fuera.
- El deploy sigue el orden: 1) Local Docker con tests completos, 2) SiteGround pruebas con validación manual + automatizada, 3) Producción.
- No se ejecuta backfill de datos legacy a tablas globales. Los datos existentes en {prefix}_* o zleg_* permanecen ahí para consulta legacy si es necesario. El toggle activo dirige todas las nuevas operaciones a tablas globales.
- Se ejecutan los gates definidos en docs/global-tables-architecture.md: test_global_table_safety.php, test_global_table_reconciliation.php, Playwright full-app-flow, PHPStan, test_auto_definir_contratos, test_pi_shared_payload_smoke.
- Se activa MySQL general_log en modo TABLE y se ejecuta la suite principal para verificar cero queries a tablas {prefix}_* migradas.
- Se realiza QA manual de flujos críticos en pruebas: programa-general, programación semanal, PDC, listado de actividades, contratos.
- El rollback inmediato es toggle OFF (USE_GLOBAL_TABLES=false). En fase B (sin toggle), el rollback requiere restaurar el toggle o revertir el commit.
- Las tablas zleg_* existentes se preservan como respaldo histórico. El toggle activo no las elimina ni modifica.