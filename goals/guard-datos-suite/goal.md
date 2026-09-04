---
capa: fuente
tipo: goal-doc
estado: abierto
fecha: 2026-09-04
areas: [datos, rbac, qa, proceso]
fuente: goals/guard-datos-suite/goal.md
resumen: "Devolver G_PHP_SUITE a verde tras ProjectSqlGuard: 24 tests en rojo en cada corrida de CI, medidos y clasificados el 2026-09-04 — 16 son tests sin adaptar y 8 destapan código de producción roto"
---

# Frente: guard-datos-suite

## Objetivo

Devolver `G_PHP_SUITE` a verde en el job `design-system-runtime`, resolviendo los **24 tests** que
fallan en cada corrida de CI desde que entró `ProjectSqlGuard` (`48e06072`, 2026-08-29), **sin
bajarle la severidad al guard y sin marcar ningún test como saltado para conseguirlo**.

El aislamiento por proyecto es la propiedad de seguridad central de este repo. Un guard más
permisivo, o un test silenciado, cierran el gate y dejan el agujero abierto: eso no cuenta como
cumplir este objetivo.

## Condición de hecho

`G_PHP_SUITE` en `success` en una corrida real de GitHub Actions, en los **dos** temas de la matriz,
leída de la tabla del paso «Summarize gate results» y no del campo `conclusion` de los pasos, que
llevan `continue-on-error` y muestran «✓» aunque fallen.

Además, los **ocho** fallos de producción de la tabla de abajo tienen que quedar arreglados **en el
código**, no en el test que los detecta.

## Medición — 2026-09-04

Corridas de origen: [33801827068](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/33801827068)
(rama `main`, sha `d413b92e`) y [33883327414](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/33883327414)
(PR #28). **Las dos listan exactamente los mismos 24 tests**, así que la cifra es estable y no
depende del frente que corra.

Las trazas del log de CI vienen recortadas: `scripts/run-php-tests.php` imprime solo las **últimas
15 líneas** de un test fallido, y eso corta justo el stack trace de nueve de ellos. La clasificación
de abajo se completó ejecutando esos nueve en el runtime aislado de CI reproducido en local
(`docker-compose.yml` + `docker-compose.ci.yml`, proyecto propio, sha `ee6e2826`).

### Las dos causas

| Causa | Tests |
|---|---|
| `MissingProjectScope` — consulta a tablas de proyecto sin `ProjectScope` activo (`ProjectSqlGuard.php:57`) | 19 |
| `DomainException: Las tablas calificadas por schema no están soportadas por el gate` (`ProjectSqlGuard.php:1751`, vía `extractTableReferences()`) — es `information_schema` pasando por `Database::query()` | 5 |

### Lo que de verdad decide el tamaño: dónde falla

**Producción rota (8).** Aquí el guard no destapó un test perezoso: destapó código que hoy no
funciona para un usuario real. Es la mitad que hay que arreglar primero.

| Test que lo detecta | Código de producción |
|---|---|
| `test_pdc_v2_maestro_sinco_import.php` | `src/Services/Pdc/MaestroInsumosService.php:578`, llamado desde `MaestroSincoImportService.php:152` (`reengancharPendientes()`) |
| `test_admin_global_project_model.php` | `admin/src/Models/Project.php:1192` (`tableExists()` / `tableHasColumn()`, `information_schema` por `Database::query()`) |
| `test_linea_base_contractual_service.php` | `src/Services/LineaBaseContractualService.php` |
| `test_report_processor_cic_project_scope.php` | `ReportProcessor` — ya documentado en `TASKS.md`: la consolidación de informes está muerta entera |
| `test_general_api_update_without_user.php`, `test_preconstruction_import_global_ids.php`, `test_schedule_update_draft_import.php` | los tres ejercitan `GeneralApiController` (`update()`, `importExcel()`) |
| `test_bi_views_exist.php` | las vistas BI (`bi_pg_semana`, `bi_pi_restricciones`, …) |

**Tests sin adaptar (16).** Consultan tablas de proyecto directamente desde el archivo de prueba,
sin declarar alcance. El patrón de arreglo ya existe en el repo (`SystemScopeRunner`, como lo usa
`tests/test_shell_week_context_contract.php` para sus fixtures).

```
test_bi_alcance_responsables      test_pdc_v2_arbol            test_pdc_v2_paquetes
test_bi_source_reconciliation     test_pdc_v2_comparar         test_pdc_v2_reenganche_pendientes
test_bitacora_avance_endpoint     test_pdc_v2_impacto_reimport test_pdc_v2_tamiz_presupuesto
test_causas_codificacion          test_pdc_v2_import_flujo     test_pdc_v2_versionado
test_cip_poblado                  test_pdc_v2_maestro          test_constraints_gestion_schema
                                                               test_pdc_v2_frentes_contrato
```

**Cuidado con estos dos, que no son fixtures.** `tests/test_bi_constraint_write.php` (nivel
`datos-proyecto`, fuera del alcance del CI y por tanto fuera de los 24) tiene **cinco** consultas sin
scope, y **dos de ellas son la comprobación de persistencia**, no preparación. Elegirles alcance a
ojo puede tapar justo la propiedad de aislamiento que ese test existe para probar. Antes de tocar
cualquier test del bloque de arriba hay que distinguir fixture de aserción.

### `information_schema`: dónde está y cuál es la salida

`Database` ya resuelve esto bien y en privado, con PDO crudo y caché (`rawTableExists`,
`rawColumnExists`); la migración `20260828_project_scope_contract.php` fija el patrón. Los archivos
de producción que hoy nombran `information_schema` son diez, sin contar `src/Core/Database.php` y
`src/Security/DataScope/TableScopeCatalog.php`, que son la vía correcta:

```
src/Core/Lps/LpsService.php                        src/Services/ReportProcessor.php
src/Security/EventService.php                      src/Services/ProgramChangeDetector.php
src/Security/RbacService.php                       src/Controllers/Api/ProfesionalesApiController.php
src/Legacy/productividad_temporal.php              src/Controllers/Api/SubcontratistasApiController.php
admin/src/Models/Project.php  (6 usos, 5 por Database::query — el foco)
admin/src/Controllers/DashboardController.php
```

**No está medido, y hay que medirlo antes de tocarlos:** cuáles de esos diez pasan de verdad por
`Database::query()` y cuáles ya usan PDO crudo. El conteo por archivo se hizo con un grep de
contexto, que no distingue una consulta armada en una variable. El único confirmado con traza real
es `admin/src/Models/Project.php`.

## Contención

Medida el 2026-09-04, antes de declarar el frente.

- `src/Security/DataScope/` — 8 commits en 30 días, el último `ae61cf94` (#25, 2026-09-03). Es
  superficie activa: hay que refrescar contención al arrancar cada tarea.
- `src/Services/Pdc/` — 4 commits en 30 días, el último `292703de` (#24).
- `admin/src/Models/Project.php` — 0 commits en 30 días.
- `tests/` afectados — último toque relevante `16d7d36a` (etiquetado de niveles), sin actividad
  reciente por test.
- `.claude/sesiones.md` no existe en el árbol: el registro vivo de sesiones no está disponible, así
  que la contención por sesión no se pudo comprobar. Declararlo al arrancar.

## Archivos declarados

`src/Security/DataScope/`, `src/Services/Pdc/MaestroInsumosService.php`,
`src/Services/LineaBaseContractualService.php`, `src/Services/ReportProcessor.php`,
`src/Controllers/Api/GeneralApiController.php`, `admin/src/Models/Project.php`, y los archivos de
`tests/` listados arriba.

## Orden sugerido, y por qué

1. **Producción primero (8).** Son fallos que un usuario sufre hoy: import SINCO del PDC, panel de
   administración, consolidación de informes. Un test rojo molesta; una función rota cuesta dinero.
2. **`information_schema` como bloque.** Una sola decisión de patrón (PDO crudo por `Database`)
   resuelve los cinco fallos de esa causa y previene los siguientes.
3. **Tests al final (16).** Mecánicos salvo donde la consulta es la aserción. Ahí, criterio.

## Lo que este frente NO toca

Relajar `ProjectSqlGuard`, marcar tests como saltados, o mover el nivel de un test para sacarlo del
CI. Si alguna de esas tres parece la salida, es señal de que hay que escalar la decisión, no de que
sea la solución.

Los otros tres gates rojos de `design-system-runtime` (`G_FULL_APP_FLOW`,
`G_RUNTIME_BUDGET_CHECK`, `G_KEYBOARD_REFLOW_EVIDENCE`) son frentes distintos y tienen su propia
entrada en `TASKS.md`.

## Archivos de este goal

- [[goals/guard-datos-suite/goal|goal.md]] (este archivo)
- [[memoria/goals/estado|Estado de los goals]]
- Contexto previo: [[TASKS]] › «Gate de datos: lo que el guard destapó y sigue roto (2026-09-02)»
