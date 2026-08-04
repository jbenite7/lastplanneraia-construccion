# Retiro del modo legacy (`USE_GLOBAL_TABLES=false` / tablas `zleg_*`)

- **Fecha:** 2026-07-29
- **Estado:** **ARCHIVADO 2026-07-29.** Diseño aprobado, sin plan de implementación.
  Congelado a petición del dueño hasta que cierren PDC V2 y dark-mode-todos-los-modulos.
  Al reactivar: releer «Riesgos», porque el estado de producción y los rojos preexistentes
  del gate estático habrán cambiado, y la estimación (3–3½ h) los daba por vigentes.
- **Contexto previo:** purga de las 239 tablas `zleg_*` de la base local (`lastplanneraia_dev`), respaldada en `~/Backups/lps-aia/zleg-tablas-20260729.sql`

## Problema

Las tablas globales ya son la única fuente de verdad. El modelo anterior de una tabla
por proyecto (`{prefix}_tabla`, archivado como `zleg_{prefix}_tabla`) sobrevive en el
código como camino de rollback, pero ya no tiene a qué caer: las tablas archivadas se
purgaron de la base local, y `test_global_table_reconciliation.php` confirmó antes de
la purga que las 229 tablas legacy verificadas no tenían ninguna clave sin equivalente
global por `project_id`.

Quedan dos formas de ruido:

1. **Documentación desfasada.** `docs/global-tables-architecture.md` describe el modo
   OFF como soportado. Además lo describe mal: atribuye el fallback a `zleg_*` al
   resolver, cuando `TableResolver.php` no menciona `zleg` en ninguna línea.
2. **Código muerto.** El camino OFF sigue en el runtime, y en modo OFF cuesta un
   `SHOW TABLES` por tabla y por consulta.

## Alcance

### Se retira

| Área | Archivo | Cambio |
|---|---|---|
| Runtime | `src/Core/Database.php` | Eliminar `rewriteLegacyArchiveTables()` (líneas ~635-653) y sus llamadas (~479, ~487). `usesGlobalTables()` devuelve `true` sin consultar el entorno ni ejecutar `SHOW TABLES LIKE 'semanas_activas'`. |
| Resolver | `src/Core/TableResolver.php` | `useGlobalTables()` devuelve `true`. `resolve()` y `resolveByPrefix()` pierden la rama de prefijo. Se elimina `$prefixCache` y su consulta a `general_proyectos_procesos`. Corregir el PHPDoc que afirma que el default es `false`. |
| Admin | `admin/src/Models/Project.php` | Eliminar las ramas `else` legacy en las líneas ~390 y ~830. |
| Config | `docker-compose.yml`, `docker-compose.ci.yml` | Quitar la variable `USE_GLOBAL_TABLES`. |
| Contratos CI | `scripts/design-system-ci-compose-contract.mjs:114`, `tests/design-system/ci-preflight.test.mjs:78` | Dejar de exigir `USE_GLOBAL_TABLES: 'true'`. |
| Fixture | `database/fixtures/design-system-ci.sql` | Quitar `zleg_da_porto_programa` y `zleg_da_porto_actividades` (CREATE + INSERT). |
| Test | `tests/test_global_table_reconciliation.php` | Retirar. Tras la purga reporta «Tablas legacy verificadas: 0»: pasa en verde sin verificar nada. Registrar el retiro en `validation-log.md`. |
| Doc | `docs/global-tables-architecture.md` | Quitar la sección de rollback; añadir nota de retiro fechada; corregir la atribución errónea del fallback al resolver. |

### Se conserva

- **`isUsingGlobalTables()` en `Database.php`**, devolviendo `true`.
  `tests/test_global_table_safety.php:69` verifica, leyendo el código como texto, que
  todo `TRUNCATE`/`CREATE` esté protegido por la cadena literal `isUsingGlobalTables()`.
  Borrar el método rompe ese test. Además tiene cuatro consumidores.
- **`database/migrations/*` y `tests/test_migrate_legacy_to_global.php`.** Son historia
  y herramienta: documentan cómo se llegó al modelo actual y sirven si aparece una base
  antigua sin migrar. El test pasa hoy y no depende de las tablas purgadas: fabrica sus
  propias `zleg_` de usar y tirar.
- **`src/Legacy/productividad_temporal.php`.** Tiene ramas que consultan el flag, pero
  `AGENTS.md` manda cambio mínimo en legado. Con `isUsingGlobalTables()` fijo en `true`
  quedan inertes.
- **`getProjectTableQueries()` en `Project.php`.** `createGlobalTables()` la sigue usando
  como plantilla. No es código legacy pese al nombre.

## Guardián de arranque

Método nuevo en `src/Core/AppEnvironment.php`, invocado desde `public/index.php`: si el
entorno define `USE_GLOBAL_TABLES` con un valor falso (`false`, `0`, `no`, `off`), abortar
con un mensaje que indique que el modo legacy se retiró el 2026-07-29 y que hay que quitar
esa línea del `.env`.

Razón: el `.env` de producción se edita a mano. Sin el guardián, ponerlo en `false`
arrancaría la app en silencio bajo una suposición que el código ya no cumple. El guardián
convierte un fallo difuso en un mensaje explícito. Cuando ese `.env` se limpie, no cuesta
nada.

## Verificación

- `docker compose exec app php tests/test_global_table_safety.php`
- `docker compose exec app php tests/test_migrate_legacy_to_global.php`
- `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`
- `npm run test:design-system:static` (cubre los dos contratos de CI tocados)
- Humo en navegador sobre una ruta con datos por proyecto, verificando que el aislamiento
  por `project_id` sigue intacto. Desktop ≥1180px, dark mode.

## Riesgos

- **Producción está ~147 commits detrás y conserva sus tablas `zleg_*`.** Su `.env` tiene
  `USE_GLOBAL_TABLES=true`, así que el comportamiento no cambia al desplegar; el guardián
  solo actuaría si alguien lo pusiera en `false`. Las tablas `zleg_*` del servidor quedan
  huérfanas: **este cambio no las borra**, y no conviene borrarlas hasta que el release
  esté estable.
- **`test_global_table_safety.php` depende de una cadena literal en el código fuente.** Es
  un acoplamiento frágil: cualquier renombrado futuro de `isUsingGlobalTables()` lo rompe
  en silencio conceptual. Se documenta aquí, no se arregla en este alcance.
- El fixture de CI y los contratos se tocan a la vez que el runtime. Si algo va a ponerse
  rojo sin relación con el cambio, será ahí; conviene correr el gate estático antes y
  después para distinguir rojos preexistentes.

## Fuera de alcance

- Borrar las tablas `zleg_*` de producción.
- Modernizar `src/Legacy/`.
- Retirar los scripts de migración legacy y su test.
- Rediseñar el acoplamiento textual de `test_global_table_safety.php`.
