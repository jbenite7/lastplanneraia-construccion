# Plan — Migración definitiva a tablas globales

## Enfoque de solución

Dos fases: **Fase A** (activar toggle en .env + desactivar creación de tablas legacy) y **Fase B** (eliminar el toggle y el código de resolución por prefijo). La migración no incluye backfill de datos existentes — el toggle activo dirige nuevas operaciones a globales mientras los datos legacy via `zleg_*` quedan como respaldo histórico.

---

## Fase A: Activar modo global (toggle ON)

### Paso A1: Activar USE_GLOBAL_TABLES=true en .env.local

**Archivos**: `.env`, `.env.example`, `docker-compose.yml` (variable de entorno del contenedor)

- Agregar `USE_GLOBAL_TABLES=true` a `.env` local
- Agregar a `.env.example` como referencia
- Agregar a `docker-compose.yml` en el servicio `app` (environment)

**Verificación**:
```bash
docker compose exec app php -r 'echo \TableResolver::useGlobalTables() ? "GLOBAL_ON" : "GLOBAL_OFF";'
# → GLOBAL_ON
```

---

### Paso A2: Verificar que las tablas globales existan en DB local

Las tablas globales deben existir en la base. Si no, se crean desde el migration canonico.

```bash
docker compose exec app mysql -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='lastplanneraia_dev' AND TABLE_NAME IN ('actividades','cambios','programa','semanas_activas','pdc','cic','profesionales','subcontratistas','programa_consolidado','programacion_semanal');"
```

Si faltan tablas, ejecutar el migration canonico:
```bash
docker compose exec app mysql < database/migrations/20260630_global_tables_contract.sql
```

---

### Paso A3: Saltar creación de tablas `{prefix}_*` en Project.php cuando global está activo

**Archivo**: `admin/src/Models/Project.php`, método `createProjectTables()` (línea 382)

Cambiar:
```php
// 1. Siempre crear tablas por-proyecto (compatibilidad hacia atrás)
foreach ($this->getProjectTableQueries($prefix) as $sql) {
    $this->db->query($sql);
}

// 2. Si USE_GLOBAL_TABLES=ON, también crear las tablas globales
if (\TableResolver::useGlobalTables()) {
    $this->createGlobalTables();
}
```

A:
```php
if (\TableResolver::useGlobalTables()) {
    $this->createGlobalTables();
} else {
    // Legacy: crear tablas por-proyecto (compatibilidad hacia atrás)
    foreach ($this->getProjectTableQueries($prefix) as $sql) {
        $this->db->query($sql);
    }
}
```

**Archivo**: `admin/src/Models/Project.php`, método `createPreConstructionTables()` (línea 827)

Mismo cambio: condicionar la creación de tablas prefijadas al toggle OFF.

---

### Paso A4: Ejecutar gates de verificación

**Verificación**:
```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
npx playwright test tests/browser/preconstruccion-full-cycle.mjs --workers=1
npx playwright test tests/browser/test-pdc.mjs tests/browser/auto-definir-contratos.mjs --workers=1
docker compose exec app php tests/test_auto_definir_contratos.php
docker compose exec app php tests/test_pi_shared_payload_smoke.php
docker compose exec app php tests/test_weekly_governance.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

**Adicional**: Auditoría MySQL general_log para detectar queries a `{prefix}_*`:
```sql
SET GLOBAL general_log = ON;
SET GLOBAL log_output = 'TABLE';
-- ejecutar suite principal
SELECT * FROM mysql.general_log WHERE argument LIKE '%prueba_%' AND argument NOT LIKE '%zleg_%';
SET GLOBAL general_log = OFF;
```

---

### Paso A5: QA manual en local

Verificar flujos críticos navegando en http://localhost:8081 con usuario `test.A` / proyecto `Prueba`:
- Programa general: carga, editar actividad, ver restricciones
- Programación semanal: ver compromisos, CNC, CNP
- PDC: ver paquetes, crear, editar
- Listado de actividades: filtrar, editar
- Contratos: ver contratos asociados

---

### Paso A6: Deploy a SiteGround pruebas

```bash
ssh siteground-pruebas-lastplanner
cd ~/public_html
git pull --ff-only origin main
/usr/local/php83/bin/php-cli -d memory_limit=4096M /usr/local/bin/composer.phar install --no-dev --optimize-autoloader
```

Agregar `USE_GLOBAL_TABLES=true` al `.env` en pruebas.

Validar flujos manualmente.

---

### Paso A7: Deploy a producción

Mismo procedimiento que Paso A6 pero en `siteground-produccion-lastplanner`.

---

## Fase B: Eliminar el toggle y el código legacy

### Paso B1: Hardcodear useGlobalTables=true en TableResolver

**Archivo**: `src/Core/TableResolver.php`

- `useGlobalTables()` → return `true` directamente, sin consultar ENV
- Eliminar `resolveByPrefix()` o simplificarlo para solo validar table type y devolver el nombre sin prefijo
- Eliminar `prefixCache`, `setUseGlobalTablesForTest()`

---

### Paso B2: Eliminar lógica de resolución por prefijo en Database.php

**Archivo**: `src/Core/Database.php`

- Eliminar `resolveProjectIdByPrefix()`, `projectIdByPrefix`, `rewritePrefixedTables()`, `rewriteLegacyArchiveTables()`, `assertScopedPrefixedGlobalQuery()`
- Simplificar `globalTablesAvailable()` a return `true`
- Mantener `injectProjectId()`, `queryWithProject()`, `projectScopedIdColumn()`, `nextProjectScopedId()` (siguen siendo necesarios)

---

### Paso B3: Eliminar creación de tablas legacy en Project.php

**Archivo**: `admin/src/Models/Project.php`

- Eliminar `getProjectTableQueries()` (o dejarlo como código muerto comentado con referencia al migration canonico)
- Eliminar la rama `else` en `createProjectTables()` y `createPreConstructionTables()` que crea tablas prefijadas
- `createGlobalTables()` se vuelve la única rama → renombrar a `createOrVerifyTables()`

---

### Paso B4: Eliminar toggle de configuraciones

- Eliminar `USE_GLOBAL_TABLES` de `.env`, `.env.example`, `docker-compose.yml`
- Eliminar referencia en `SessionMiddleware.php`

---

### Paso B5: Repetir gates y QA (A4 + A5)

Los mismos gates del Paso A4 + A5, más verificación de que no haya código muerto.

---

### Paso B6: Deploy (A6 + A7)

Desplegar Fase B a pruebas y producción.

---

## Riesgos y preguntas abiertas

1. **Tablas globales vacías en producción**: Como no hay backfill, los datos históricos no estarán en globales. Si un query global se ejecuta contra datos legacy que no están migrados, dará resultados vacíos. Mitigación: las tablas `zleg_*` y el rewrite en Database.php redirigen queries legacy a `zleg_*` cuando la tabla prefijada no existe. Con toggle ON, `rewritePrefixedTables()` rewrites `prueba_actividades` → `actividades` (global) — si no hay datos en global, el resultado será vacío. **Esto es aceptado** según la decisión de no hacer backfill.
2. **Dependencia de `Base_de_Datos`**: `TableResolver` actualmente usa `Base_de_Datos` para resolver prefijos. En Fase B se elimina eso. Verificar que ningún flujo dependa de `Base_de_Datos` para algo que no sea resolución de tablas — hoy se usa también para display de nombre de BD en UI de admin. Eso debe preservarse.
3. **Scripts legacy con nombres de tabla hardcodeados**: Scripts en `src/Legacy/` que usan `{$prefix}_tabla` en queries SQL. Con toggle ON, `TableResolver::resolveByPrefix()` ignora el prefijo y devuelve solo el nombre de tabla. Pero si scripts usan concatenación directa de cadenas (no `TableResolver`), el query apuntaría a `prueba_actividades` que puede no existir. Mitigación: `Database::rewritePrefixedTables()` captura esos casos y los rewrites a la tabla global.

---

## Resumen de archivos tocados por fase

| Archivo | Fase A | Fase B |
|---|---|---|
| `.env` | Agregar `USE_GLOBAL_TABLES=true` | Eliminar |
| `.env.example` | Agregar referencia | Eliminar |
| `docker-compose.yml` | Agregar env var | Eliminar |
| `admin/src/Models/Project.php` | Condicionar creación legacy al toggle OFF | Eliminar rama legacy + `getProjectTableQueries()` |
| `src/Core/TableResolver.php` | — | Hardcodear true, eliminar prefix cache y resolveByPrefix |
| `src/Core/Database.php` | — | Eliminar rewrite legacy, resolvePrefix, simplify globalTableAvailable |
| `src/Core/SessionMiddleware.php` | — | Eliminar referencia a USE_GLOBAL_TABLES |