---
capa: fuente
tipo: goal-doc
estado: cerrado
fecha: 2026-09-04
areas: [datos, rbac, qa, proceso]
fuente: goals/guard-datos-suite/goal.md
resumen: "CERRADO 2026-09-04: G_PHP_SUITE de 24 fallos a 0. Los 8 de producción y los 16 tests sin adaptar, más 2 clases PHPUnit que nadie había contado y 4 fallos de producción que estaban escondidos detrás de los tests"
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

## Cierre — primera mitad (los ocho de producción), 2026-09-04

**Los ocho tests de la lista pasan, y el arreglo está en el código de producción.** Los 16 tests sin
adaptar siguen abiertos: `G_PHP_SUITE` seguirá en rojo hasta que se cierre la segunda mitad, tal como
preveía este goal.

Verificado en el runtime aislado de CI reproducido en local (`docker-compose.yml` +
`docker-compose.ci.yml`, proyecto propio en el puerto 18081, con el worktree montado y comprobado por
`md5sum` contra el árbol local antes de leer ningún código de salida).

### Los ocho, uno por uno

```
test_pdc_v2_maestro_sinco_import      → RC=0
test_admin_global_project_model       → RC=0
test_linea_base_contractual_service   → RC=0
test_report_processor_cic_project_scope → RC=0
test_general_api_update_without_user  → RC=0
test_preconstruction_import_global_ids → RC=0
test_schedule_update_draft_import     → RC=0
test_bi_views_exist                   → RC=0
```

### Diferencial contra `main`, que es la prueba de que no se empeora nada

Misma suite, mismo stack, dos árboles. `main` en `63518a5a`:

```
main:  === 102 corridos: 78 pasaron, 24 fallaron, 0 sospechosos, 0 se saltaron solos, 37 omitidos ===
rama:  === 102 corridos: 86 pasaron, 16 fallaron, 0 sospechosos, 0 se saltaron solos, 37 omitidos ===

ARREGLADOS (8): test_admin_global_project_model, test_bi_views_exist,
                test_general_api_update_without_user, test_linea_base_contractual_service,
                test_pdc_v2_maestro_sinco_import, test_preconstruction_import_global_ids,
                test_report_processor_cic_project_scope, test_schedule_update_draft_import
NUEVOS FALLOS: ninguno
SIGUEN ROJOS: 16 (los tests sin adaptar de la segunda mitad)
```

Resto de gates sobre la rama:

```
--nivel=puro → RC=0 · 33 corridos: 33 pasaron · PHPUnit: 13 clase(s), en verde (rc=0)
phpstan analyse src admin/src --memory-limit=1G → RC=0 · [OK] No errors
test_project_scope_callsite_audit → RC=0 · 0 hallazgos
```

### Lo que NO se hizo, y es deliberado

No se relajó `ProjectSqlGuard`, no se marcó ningún test como saltado y no se movió el
`// @requiere:` de ninguno. Los dos usos nuevos de `SystemScopeRunner` entraron por la lista blanca
de `test_project_scope_callsite_audit`, cada uno con su justificación escrita, que es el mecanismo
que el repo ya tenía para esto.

### Correcciones a lo que este goal daba por cierto

1. **La medición pendiente de `information_schema` está hecha, y el conteo era otro.** De los diez
   archivos listados, **ocho ya estaban migrados**: solo conservan comentarios explicando por qué
   delegan en `Database`. Quedaba SQL real en dos — `admin/src/Models/Project.php` (cuatro
   consultas, arregladas) y `admin/src/Controllers/DashboardController.php` (una, **sigue abierta**:
   está dentro de un `try/catch` que devuelve ceros, así que falla en silencio y el panel muestra
   0 MB y 0 tablas).

2. **La consolidación de informes no estaba muerta por lo que decía `TASKS.md`.** El alias ambiguo
   —su «tercer problema»— no bloqueaba nada. Lo que la mataba eran siete consultas sin `project_id`
   que operaban sobre todas las obras, y la peor **borraba** el CIC/CIP de las demás en cada
   consolidación. El `FROM DUAL` sí era un obstáculo real y se quitó: era decorativo.

3. **Tres de los ocho no eran «producción rota» en el sentido de la tabla.** `GeneralApiController`
   se comportaba correctamente al exigir alcance; lo que fallaba era que los tests construían el
   controlador saltándose `SessionMiddleware`, que es quien enlaza el `ProjectScope` en una petición
   real. Se resolvió con `tests/support/SessionScopeHarness.php`, que enlaza esa mitad con el mismo
   `ProjectScopeResolver` de producción — no con un atajo. Sí había un fallo de producción en ese
   controlador, y se arregló: un `project_id` repetido en el WHERE que rompía el borrado de
   huérfanas del import.

### Hallazgo enrutado, no arreglado

`src/Legacy/nueva_semana.php` no puede activar un borrador de semana: su `UPDATE` con tabla derivada
no satisface al guard. Lo destapó `test_schedule_update_draft_import` al llegar más lejos que antes;
ese test **pasa igual**, porque su aserción mira el conteo de filas y no el payload. Queda anotado en
`TASKS.md` como tarea propia: es legado sin cobertura, y reescribir ese `UPDATE` de paso habría
convertido este frente en otro.

## Cierre — segunda mitad (los 16 tests) y del frente, 2026-09-04

**Los 16 pasan, y `G_PHP_SUITE` queda en verde: el nivel `http` va de 24 fallos a 0.** Verificado en
el mismo runtime aislado de CI reproducido en local, con el worktree montado y comprobado por
`md5sum` contra el árbol local antes de leer ningún código de salida — y otra vez al montar `main`
para el diferencial, que es donde esa comprobación de verdad gana algo.

### Diferencial contra `main` (`63518a5a`), misma suite y mismo stack

```
main:  === 102 corridos: 78 pasaron, 24 fallaron, 0 sospechosos, 0 se saltaron solos, 37 omitidos ===
rama:  === 102 corridos: 100 pasaron,  0 fallaron, 0 sospechosos, 2 se saltaron solos, 37 omitidos ===

ARREGLADOS: los 24
NUEVOS FALLOS: ninguno
```

Los 2 «se saltaron solos» son `test_bi_alcance_responsables` y `test_bitacora_avance_endpoint`:
llegan a su `SKIP` propio porque el fixture sintético de CI no trae la fila que necesitan. Es su
comportamiento de diseño, no un silenciamiento — antes ni siquiera alcanzaban esa línea.

Resto de gates sobre la rama:

```
--nivel=puro → RC=0 · 33 corridos: 33 pasaron · PHPUnit: 13 clase(s), en verde (rc=0)
--nivel=http → RC=0 · PHPUnit: 17 clase(s), en verde (rc=0)
phpstan analyse src admin/src --memory-limit=1G → RC=0 · [OK] No errors
test_project_scope_callsite_audit → RC=0
```

### La condición de hecho, cumplida en una corrida real de GitHub Actions

Corrida [33893451861](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/33893451861)
(PR #30). Leído de las variables `G_*` del paso «Summarize gate results», que es lo que el goal
exigía — no del `conclusion` de los pasos, que llevan `continue-on-error` y muestran «✓» aunque
fallen.

```
                              main (33886885126)   PR #30 (33893451861)
G_PHP_SUITE                        failure       →     success   ← la condición de hecho
G_FULL_APP_FLOW                    failure             failure
G_RUNTIME_BUDGET_CHECK             failure             failure
G_KEYBOARD_REFLOW_EVIDENCE         failure             failure
(los nueve gates restantes)        success             success
```

`G_PHP_SUITE: success` **en los dos temas de la matriz**, claro y oscuro.

Los otros tres siguen en rojo **y ya lo estaban en `main`**: son los frentes que este goal declara
fuera de alcance, cada uno con su entrada en `TASKS.md`. El job `design-system-runtime` sigue
fallando por ellos, así que el CI del PR está en rojo por causas ajenas a este frente — mergear con
esos tres rojos preexistentes es decisión de Felipe, no de este cierre.

### Cuatro fallos de producción más, que estaban escondidos detrás de los tests

Ninguno se veía antes: el test moría en su primera consulta sin alcance y nunca llegaba tan lejos.

| Qué está roto para un usuario | Dónde |
|---|---|
| El catálogo de paquetes solo muestra los que la obra ya usa | `PaquetesService::catalogo()` — bajo alcance de obra el gate vuelve `INNER` su `LEFT JOIN` |
| Las sugerencias de paquete no responden | `/plan-compras/api/paquetes/sugerencias`, sus tres capas |
| Los avisos del visor de presupuesto revientan | `PresupuestoImportService::avisosDelPresupuesto()` |
| Actividades por insumo revienta | `PaquetesService::actividadesPorInsumo()` |

Los dos últimos son el mismo defecto: unían `pdc_presupuesto_items` con
`pdc_presupuesto_apu_insumos` **solo por `id`**, sin relacionar `project_id`. El gate los rechazaba
con razón — es la forma exacta en que un JOIN se salta el aislamiento entre obras.

### La decisión que hubo que subir

**El catálogo de paquetes se lee entre obras a propósito, y eso ningún `ProjectScope` puede
expresarlo.** Decisión de Felipe, 2026-09-04: se declara el cruce, no se suprime. Las cuatro
lecturas pasan por `PaquetesService::leerCatalogoEntreObras()` con su razón escrita, autorizadas en
la lista blanca de `test_project_scope_callsite_audit`. Lo que viaja entre obras es id y nombre de
paquete —catálogo de la empresa— y un conteo de obras; nunca una fila de la obra ajena. La
alternativa evaluada, sugerir solo con la historia propia, dejaba a cada obra nueva sin ninguna
sugerencia, que es cuando más ayudan.

### Correcciones a lo que este goal daba por cierto

1. **Los 16 no eran 16: eran 16 scripts más dos clases PHPUnit**, `CarryoverAvanceSemanalTest` y
   `PgAvanceEdicionManualTest`, con 9 errores de la misma causa. No las contó ninguna de las dos
   mediciones de CI ni la de este goal, y no por descuido: `scripts/run-php-tests.php` reporta
   PHPUnit en una línea `===` **aparte** de la de los scripts sueltos, así que quien lee «24
   fallaron» se lleva solo la mitad del tablero. Sin ellas `G_PHP_SUITE` no habría quedado verde.

2. **«Mecánicos salvo donde la consulta es la aserción» se quedó corto.** El orden sugerido daba los
   16 por trámite. De trámite tuvieron poco: cuatro destaparon producción rota y uno obligó a subir
   una decisión de producto. La lección es la de la primera mitad otra vez — el guard no distingue
   entre un test perezoso y un fallo real, y hasta que el test no llega al final no se sabe cuál de
   los dos era.

### Lo que NO se hizo, y es deliberado

No se relajó `ProjectSqlGuard`, no se marcó ningún test como saltado y no se movió el `// @requiere:`
de ninguno. `tests/test_bi_constraint_write.php` —el de las cinco consultas, dos de ellas
aserciones— **sigue abierto a propósito**: es nivel `datos-proyecto`, fuera del CI, y no bloquea la
condición de hecho. Queda anotado en `TASKS.md` con la regla que ahora sí está escrita para
resolverlo.

### La herramienta que queda

`tests/support/ScopeFixture.php`, y sobre todo la regla que lleva escrita en su cabecera: el alcance
de una **aserción de aislamiento** es el de la obra que se **observa**, nunca el de la que se acaba
de escribir. Elegirlo al revés no rompe el test — lo deja en verde midiendo lo contrario de lo que
dice, que es peor.

## Archivos de este goal

- [[goals/guard-datos-suite/goal|goal.md]] (este archivo)
- [[memoria/goals/estado|Estado de los goals]]
- Contexto previo: [[TASKS]] › «Gate de datos: lo que el guard destapó y sigue roto (2026-09-02)»
