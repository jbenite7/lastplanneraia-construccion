# Migración definitiva a tablas globales

## Objetivo

Completar la migración del sistema LPS-AIA al modelo de tablas globales operativas, eliminando por completo el código legacy de tablas independientes por proyecto. Activar `USE_GLOBAL_TABLES=true` permanentemente y remover toda la lógica de resolución por prefijo de base de datos.

## Entendimiento compartido

Ver `facts.md` para el conjunto de hechos validados como criterios de aceptación.

## Plan de ejecución

Ver `plan.md` para los pasos detallados, archivos involucrados, verificaciones y riesgos.

## Condición de "terminado"

- [ ] `USE_GLOBAL_TABLES=true` está activo en .env de local, pruebas y producción
- [ ] Todas las 46 tablas globales existen y funcionan en todos los ambientes
- [ ] Project.php no crea tablas `{prefix}_*` cuando el toggle está activo
- [ ] La lógica de resolución por prefijo está eliminada de TableResolver y Database
- [ ] Los gates de seguridad pasan: test_global_table_safety, reconciliation, Playwright, PHPStan
- [ ] Auditoría MySQL general_log confirma cero queries a tablas legacy prefijadas
- [ ] QA manual validado en local y pruebas para flujos críticos
- [ ] Deploy exitoso a pruebas y producción
- [ ] Documentación actualizada (AGENTS.md, README, docs/global-tables-architecture.md)