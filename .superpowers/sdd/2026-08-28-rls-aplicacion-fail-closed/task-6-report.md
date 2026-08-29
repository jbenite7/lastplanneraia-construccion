# Task 6 — Alcance BI multiproyecto explícito

## Status

`DONE_WITH_CONCERNS`

La frontera BI ya recibe autoridad únicamente mediante `MultiProjectScope`, el guard single-project
conserva su contrato y `Database::queryForProjects()` clasifica, acota e intersecta todas las raíces
Project antes de PDO. Los focos contractuales están verdes. El runner amplio no está verde por deuda
fail-closed y puertas HTTP fuera del alcance de Task 6; el detalle se conserva abajo sin ocultarlo.

## Files / rulings aplicados

Archivos contractuales de producción:

- `src/Support/BiProjectScope.php`
- `src/Core/Database.php`
- `src/Controllers/Api/BiControlTowerApiController.php`
- `src/Controllers/Bi/BiViewController.php`
- `src/Services/ControlTowerService.php`
- `src/Services/Pdc/SeguimientoService.php`
- `src/Services/Bi/ActionRecommendationService.php`
- `src/Services/Bi/RiskScoringService.php`
- `src/Services/Bi/MetricScope.php`
- `src/Services/Bi/MetricExecutor.php`

Expansiones autorizadas:

- `src/Security/DataScope/ProjectSqlGuard.php` y `tests/unit/ProjectSqlGuardTest.php`: frontera pública
  separada `guardForProjects()`, reutilizando tokenizer, catálogo, propagación y rewriter existentes.
  Costo aceptado: más superficie en el guard central; mitigación: API separada, `guard()` intacto y
  regresión single-project completa.
- `src/Controllers/Api/BiMetricController.php`: toma el `ProjectScope` canónico de `DataScopeContext`,
  revalida membresía con `BiProjectScope::scope()` y nunca crea autoridad desde un entero de sesión.
- Tests de constructores `MetricScope` autorizados: `tests/test_bi_metric_endpoint.php`,
  `tests/test_bi_paridad_metricas.php`, `tests/test_bi_semaforo_franjas.php` y
  `tests/unit/MetricExecutorTest.php`.
- Tests/callsites BI autorizados: `tests/test_bi_programa_general_chart_values.php`,
  `tests/test_bi_filters_apply_to_charts.php`, `tests/test_bi_programa_general_cnp.php`,
  `tests/test_bi_programa_general_cnc.php`, `tests/test_bi_programa_general_activity_timeline.php`,
  `tests/test_bi_real_data_sources.php`, `tests/test_linea_base_sobrevive_reprogramacion.php`,
  `tests/test_bi_multi_project_governance.php`, `tests/test_pdc_v2_torre_control.php` y
  `tests/test_pdc_v2_torre_control_rbac.php`.
- `tests/support/BiContractFixture.php`: seed mutable compartido acotado mediante
  `SystemScopeRunner`; rollback transaccional ya existente y clear del contexto en `finally`.
- Nuevos focos: `tests/test_bi_multi_project_database_scope.php` y cambios en
  `tests/test_bi_project_scope.php`.

No se modificaron schema, `general_decision_log`, migraciones, grants, servicios legacy de paquetes
o reportes reales. No se ejecutaron consolidaciones, reparaciones ni mantenimiento.

## RED — evidencia antes de implementación

1. `php tests/test_bi_project_scope.php` — `rc=1`: faltaba `scope()` y `resolve()` elegía autoridad
   implícita en vez de devolver el adaptador vacío.
2. `php tests/test_bi_multi_project_database_scope.php` — `rc=1`: método
   `Database::queryForProjects()` inexistente.
3. `phpunit tests/unit/ProjectSqlGuardTest.php` — `rc=2`: método `guardForProjects()` inexistente.
4. Constructores métricos — `TypeError`: `MetricScope` todavía recibía arrays, no autoridad.
5. `phpunit tests/unit/MetricExecutorTest.php` — `rc=2`: la tabla temporal no figuraba en el
   catálogo fail-closed.
6. `php tests/test_pdc_v2_torre_control.php` — `MissingProjectScope` en seed/cleanup base.
7. Oráculo PDC legacy — rechazo de raíces nullable no acotadas (`f`, luego `pdc_insumo_paquete`);
   se reescribieron como derived prefiltradas, sin relajar el guard.
8. `test_bi_paridad_metricas.php` y `test_bi_semaforo_franjas.php` — `MissingProjectScope` en
   descubrimiento global del test.
9. Consumidores de `BiContractFixture` — `MissingProjectScope` en INSERT del helper compartido.
10. `test_bi_filters_apply_to_charts.php` alcanzó dos RED reales del SUT:
    - alias Project correlacionado no resuelto en subconsulta;
    - `project_id` bajo OR/XOR en pares obra/semana del snapshot.
11. Curva S — `DomainException: Tabla no clasificada en el schema: filtered`; el catálogo trataba
    alias CTE como tabla física. Se añadió primero el foco `CtePipeline`.
12. Activity timeline — precondiciones sin datos para proyecto 73/semana 3; el selector estaba
    desalineado del proyecto A que el propio fixture siembra. Se cambió solo el selector autorizado.

Todos los RED fueron de comportamiento/contrato o lifecycle fail-closed; no se aceptó un RED por
sintaxis o fixture accidentalmente roto.

## GREEN — evidencia final focal

- `php tests/test_bi_project_scope.php` — PASS.
- `php tests/test_bi_multi_project_database_scope.php` — PASS; A/B visibles, C excluido y rollback.
- `phpunit tests/unit/ProjectSqlGuardTest.php` — `48 tests, 73 assertions`, PASS.
- `php tests/DatabaseWrapperTest.php` — `7 checks`, PASS.
- `phpunit tests/unit/MetricExecutorTest.php` — `4 tests, 24 assertions`, PASS.
- `php tests/test_pdc_v2_torre_control.php` — `40 PASS`, incluido oráculo legacy A/B y exclusión C.
- `php tests/test_pdc_v2_torre_control_rbac.php` — `9 PASS`.
- `php tests/test_bi_paridad_metricas.php` — `27 passed, 0 failed`.
- `php tests/test_bi_semaforo_franjas.php` — `57 passed, 0 failed`.
- `php tests/test_bi_filters_apply_to_charts.php` — `3 PASS`.
- `php tests/test_bi_programa_general_chart_values.php` — `4 PASS`.
- CNP y CNC — ambos contratos PASS.
- Activity timeline — PASS.
- Governance multiproyecto — PASS.
- Real data sources — `101 passed, 0 failed`.
- Línea base — PASS con restauración exacta.
- `php tests/test_project_scope_callsite_audit.php` — `0 hallazgos`.
- PHPStan sobre los 12 archivos productivos tocados — `No errors`.
- `php -l` sobre todos los PHP tocados, incluido el test nuevo — sin errores.
- `git diff --check` — limpio.

`tests/test_bi_metric_endpoint.php` se intentó mediante el runner: `ABORT`, HTTP 401 antes del SUT
porque DEV_DOOR está cerrado. Por ruling no se cambió config ni se inventó una puerta alternativa;
queda cubierto por lint, `MetricExecutorTest`, paridad, semáforo y el resto de la ruta productiva.

## Runner amplio obligatorio

Comando literal del brief:

`LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=datos-proyecto`

No ejecutó tests: el contenedor efímero no tenía servidor HTTP en `127.0.0.1`. Se ejecutó el mismo
runner dentro del servicio `app` activo del mismo worktree:

`LPS_CODE_ROOT="$(pwd)" docker compose exec -T app php scripts/run-php-tests.php --nivel=datos-proyecto`

Resultado exacto: **135 total; 72 pasaron; 62 fallaron; 1 sospechoso**. PHPUnit adicional:
`151 tests, 307 assertions, 10 errors`.

Clasificación de los rojos amplios:

- La mayoría son lifecycle RLS fuera del alcance autorizado: fixtures/tests legacy sin
  `ProjectScope`/`SystemScopeRunner`, o consultas de `information_schema` calificadas que el gate
  fail-closed rechaza. Son la deuda explícita de las tareas siguientes; no se abrió un escape.
- Puertas/entorno HTTP: DEV_DOOR cerrado (incluido `test_bi_metric_endpoint.php`), sesiones/CSRF y
  expectativas del contenedor que no coinciden con su configuración actual.
- Fixtures/configuración ajenos: permisos ausentes, una migración sin autoload y el test de contrato
  de schema que salió 0 sin comprobar nada.

Dentro de ese runner pasaron todos los consumidores contractuales de Task 6 que sí alcanzaron el
SUT: scope, A/B/C, filters, chart-values, CNP, CNC, activity timeline, governance, paridad,
semáforo, real-data, PDC torre/RBAC, línea base y callsite audit.

## Cleanup / aislamiento

- A/B/C: transacción explícita y `rollBack()` en `finally`; no queda ninguna fila.
- MetricExecutor: `TEMPORARY TABLE` creada/sembrada/eliminada por PDO solo en fixture; snapshot y
  restauración de `TableScopeCatalog` en `finally`. El SUT siempre usa
  `MetricExecutor -> queryForProjects()`.
- PDC torre: seed y cleanup mediante `SystemScopeRunner`; oráculos legacy con `ProjectScope` exacto;
  scope y contexto se limpian en `finally`.
- `BiContractFixture`: seed mutable bajo `SystemScopeRunner`; contexto System siempre se limpia en
  `finally`; la transacción compartida registra rollback al terminar el proceso.
- Línea base proyecto 68: snapshot, seed y restore bajo `SystemScopeRunner`; restore en `finally` y
  fallback de shutdown idempotente.
- Descubrimientos globales de tests: runners acotados a una llamada; el SUT nunca corre con System.

## Decisiones y self-review

- `resolve()` conserva compatibilidad array y retorna `[]` sin usuario; `scope()` es la única
  frontera que convierte membresía revalidada en autoridad y falla tipado si queda vacía.
- `guard()` single-project no acepta `MultiProjectScope` ni listas IN. `guardForProjects()` solo
  acepta SELECT con al menos una raíz Project, rechaza Identity-only/System/unclassified, propaga
  scope por relaciones demostrables y reescribe cada filtro a la intersección server-side.
- CTE SELECT no crea una segunda clasificación: solo se reconocen alias lógicos no recursivos
  después de su definición; las raíces físicas internas siguen pasando por catálogo/tokenizer.
- El filtro snapshot obra/semana conserva el scope Project como conjunción; las semanas son datos y
  el mapa exacto se postfiltra en memoria. No se debilitó la regla OR/XOR.
- Las lecturas PDC de catálogos System/Identity se separan de las filas operativas ya scoped; los
  IDs derivados solo seleccionan metadata de presentación y nunca crean autoridad.
- `ControlTowerService` conserva el scope explícito durante una invocación y lo limpia en `finally`;
  no admite scopes anidados.
- No hay overload productivo que acepte int/array para recrear autoridad. `rg` encuentra
  `new MultiProjectScope()` en producción únicamente dentro de `BiProjectScope`.
- `git diff --check`, PHPStan, lint, guard single-project y auditoría de callsites no detectaron
  regresiones. La preocupación remanente es exclusivamente que la suite global completa sigue
  roja por los 62 casos documentados y que el endpoint HTTP no pudo atravesar DEV_DOOR.

## Ronda correctiva 1/5 — propagación correlacionada, PDC positivo y evidencia amplia

### RED observado

- Guard multiproyecto, consulta exacta bajo scope `[27,73]`: los tres casos `NOT EXISTS`,
  `NOT (EXISTS(...))` y `EXISTS` positivo con solo la raíz interna anclada no lanzaban
  `ProjectScopeViolation` (`3 failures`); la consulta con ambas raíces explícitas ya pasaba.
- Integración A/B/C: `queryForProjects()` aceptaba el anti-subquery y el test reportó
  `queryForProjects propagated inner authority to an unanchored outer query block`.
- PDC: después de corregir la precondición del fixture (`semanas_activas.Id` es obligatorio), las
  comprobaciones alcanzaron el SUT; solo falló el empate por nombre porque la implementación
  ordenaba por `paquete_id`.

### GREEN y decisiones

- `guardForProjects()` descarta aristas de propagación cuando alguna raíz física de la relación no
  pertenece al `SELECT` local, usando `projectReferencesInSelect()` del tokenizer existente. No se
  añadió regex ni se cambió `guard()` single-project.
- Las consultas correlacionadas productivas de `RiskScoringService` y `ControlTowerService` ahora
  llevan una lista scope explícita en cada subquery; los IDs se repiten como datos derivados del
  mismo `MultiProjectScope`, sin crear autoridad nueva.
- Guard completo: `51 tests, 77 assertions`; cubre los tres rechazos, ambas listas hostiles
  intersectadas, y conserva derived/CTE/INNER/LEFT/RIGHT y single-project.
- A/B/C DB: PASS; el bypass real es rechazado antes de PDO y el rollback exacto permanece verde.
- PDC positivo: A tiene cobertura `50.0/25.0` y 1 desactualizado; B `75.0/60.0` y 2; ambos
  coinciden exactamente con oráculos legacy bajo `ProjectScope`. C tiene filas comprobadas en las
  seis fuentes reales y no aparece en mapas ni brief A/B.
- `detalleDestinos()` ordena en PHP por fecha null-last, fecha ascendente, nombre de paquete y orden
  del paso. El fixture crea ZETA antes que ALFA para que ID y nombre tengan orden opuesto; la salida
  observable es ALFA, ZETA y no expone columnas nuevas.
- Cleanup PDC: los proyectos marker `999950/999951/999952` se borran solo en las tablas fixture,
  los paquetes se borran solo por `creado_por = 'test-b3'`, todo dentro de `SystemScopeRunner` y el
  `finally` restaura el contexto previo.

### Runner amplio del HEAD — evidencia reproducible

Comando:

`LPS_CODE_ROOT="$(pwd)" docker compose exec -T app php scripts/run-php-tests.php --nivel=datos-proyecto`

Artefacto completo ignorado:
`.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/task-6-wide-runner-head.log`
(`825` líneas, `56,890` bytes, SHA-256
`8d6157ea1fd08d631a950519e257ea9ba04c1d21a399e69eed967e987bf15f7d`).

Resultado exacto del HEAD: **135 total; 72 pasaron; 62 fallaron; 1 sospechoso**. PHPUnit:
**154 tests; 311 assertions; 10 errors**.

No se ejecutó comparación base. El servidor HTTP activo sirve exclusivamente el bind mount del
HEAD en `/var/www/html`; ejecutar un checkout `78be898` contra ese servidor mezclaría archivos base
con endpoints HEAD. Una comparación válida exigiría otro stack/mount o cambiar configuración, y la
ronda lo prohibió. Por tanto, la tabla siguiente describe evidencia del HEAD y **no afirma que los
fallos sean preexistentes**.

Clases y relación con archivos tocados:

- `RLS-SCOPE`: primera causa `MissingProjectScope`. El stack atraviesa el gate/Database tocados por
  Task 6, pero el caller o fixture que no enlaza scope no fue modificado en esta ronda; sin base
  comparable, la atribución queda indeterminada.
- `RLS-SCHEMA`: el gate tocado rechaza SQL calificado por schema; el caller no fue tocado en esta
  ronda y tampoco se atribuye a base ni HEAD sin comparación.
- `HTTP-ENV`: estado DEV_DOOR, sesión, CSRF o status HTTP; no atraviesa las cuatro correcciones
  productivas de esta ronda.
- `FIXTURE-CONTRACT`: dato, permiso, catálogo o configuración faltante; sin relación directa con
  los archivos productivos corregidos.
- `BOOTSTRAP`: autoload/migración no carga `DataScopeContext`; no nace de los cambios de esta ronda.

#### Los 62 tests fallidos

| # | Test | Primera causa observable | Clase |
|---:|---|---|---|
| 1 | `test_admin_dev_door_guard.php` | login dev esperado 302 no cumple el contrato dinámico | HTTP-ENV |
| 2 | `test_admin_global_project_model.php` | schema calificado rechazado por el gate | RLS-SCHEMA |
| 3 | `test_admin_modulos.php` | sesión A no obtiene 200; siguen cinco contratos HTTP fallidos | HTTP-ENV |
| 4 | `test_bi_alcance_responsables.php` | `MissingProjectScope` | RLS-SCOPE |
| 5 | `test_bi_constraint_list.php` | `MissingProjectScope` | RLS-SCOPE |
| 6 | `test_bi_constraint_write.php` | `MissingProjectScope` | RLS-SCOPE |
| 7 | `test_bi_metric_endpoint.php` | DEV_DOOR cerrado, HTTP 401 antes del SUT | HTTP-ENV |
| 8 | `test_bi_restriction_pareto.php` | DEV_DOOR cerrado, HTTP 401 | HTTP-ENV |
| 9 | `test_bi_source_reconciliation.php` | `MissingProjectScope` | RLS-SCOPE |
| 10 | `test_bi_views_exist.php` | `bi_pg_semana` exige `ProjectScope` | RLS-SCOPE |
| 11 | `test_bitacora_avance_endpoint.php` | `queryWithProject` exige scope activo | RLS-SCOPE |
| 12 | `test_causas_codificacion.php` | `MissingProjectScope` | RLS-SCOPE |
| 13 | `test_cip_poblado.php` | `MissingProjectScope` | RLS-SCOPE |
| 14 | `test_constraints_gestion_schema.php` | schema calificado rechazado | RLS-SCHEMA |
| 15 | `test_csrf_lps_api.php` | falta meta `lps-drawer-csrf-token` | HTTP-ENV |
| 16 | `test_csrf_modulos_api.php` | POST sin token devolvió 200, esperaba 403 | HTTP-ENV |
| 17 | `test_dev_door_http.php` | candado cerrado en el entorno | HTTP-ENV |
| 18 | `test_general_api_update_without_user.php` | `queryWithProject` exige scope activo | RLS-SCOPE |
| 19 | `test_goal_close_blockers_manifest.php` | catálogo no conserva familias esperadas | FIXTURE-CONTRACT |
| 20 | `test_human_decision_actions_package.php` | `DataScopeContext` no encontrado | BOOTSTRAP |
| 21 | `test_human_decision_approval_checklist.php` | BD no conserva las 13 decisiones esperadas | FIXTURE-CONTRACT |
| 22 | `test_human_decision_matrix_coverage.php` | catálogo de familias no coincide | FIXTURE-CONTRACT |
| 23 | `test_indicadores_server_gate.php` | rol restringido recibió 200, esperaba 403 | HTTP-ENV |
| 24 | `test_linea_base_contractual_service.php` | `MissingProjectScope` | RLS-SCOPE |
| 25 | `test_password_reset_resultados.php` | `APP_URL` sin configurar | FIXTURE-CONTRACT |
| 26 | `test_pdc_v2_amarre_cronograma.php` | `MissingProjectScope` | RLS-SCOPE |
| 27 | `test_pdc_v2_arbol.php` | `MissingProjectScope` | RLS-SCOPE |
| 28 | `test_pdc_v2_brecha_daporto.php` | `MissingProjectScope` | RLS-SCOPE |
| 29 | `test_pdc_v2_comparar.php` | `MissingProjectScope` | RLS-SCOPE |
| 30 | `test_pdc_v2_duraciones_editables.php` | `MissingProjectScope` | RLS-SCOPE |
| 31 | `test_pdc_v2_equipo_clasificacion.php` | queda 1 insumo con tipo genérico `EQUIPO` | FIXTURE-CONTRACT |
| 32 | `test_pdc_v2_flujo_caja.php` | `MissingProjectScope` | RLS-SCOPE |
| 33 | `test_pdc_v2_frentes_contrato.php` | schema calificado rechazado | RLS-SCHEMA |
| 34 | `test_pdc_v2_frentes_remapeados.php` | schema calificado rechazado | RLS-SCHEMA |
| 35 | `test_pdc_v2_impacto_reimport.php` | `MissingProjectScope` | RLS-SCOPE |
| 36 | `test_pdc_v2_import_flujo.php` | `MissingProjectScope` | RLS-SCOPE |
| 37 | `test_pdc_v2_maestro.php` | `MissingProjectScope` | RLS-SCOPE |
| 38 | `test_pdc_v2_maestro_sinco_import.php` | `MissingProjectScope` al actualizar pendientes | RLS-SCOPE |
| 39 | `test_pdc_v2_paquetes.php` | `MissingProjectScope` | RLS-SCOPE |
| 40 | `test_pdc_v2_paquetes_motor.php` | `MissingProjectScope` | RLS-SCOPE |
| 41 | `test_pdc_v2_pasos_configurables.php` | `MissingProjectScope` | RLS-SCOPE |
| 42 | `test_pdc_v2_pasos_copiar.php` | `MissingProjectScope` | RLS-SCOPE |
| 43 | `test_pdc_v2_pasos_historial.php` | `MissingProjectScope` | RLS-SCOPE |
| 44 | `test_pdc_v2_plan_fechas.php` | `MissingProjectScope` | RLS-SCOPE |
| 45 | `test_pdc_v2_plan_fechas_correspondencias.php` | `MissingProjectScope` | RLS-SCOPE |
| 46 | `test_pdc_v2_rbac_pasos.php` | permiso `lps.paquetes_contratacion.reglas` ausente | FIXTURE-CONTRACT |
| 47 | `test_pdc_v2_reenganche_pendientes.php` | `MissingProjectScope` | RLS-SCOPE |
| 48 | `test_pdc_v2_reprogramacion.php` | `MissingProjectScope` | RLS-SCOPE |
| 49 | `test_pdc_v2_seguimiento.php` | `MissingProjectScope` | RLS-SCOPE |
| 50 | `test_pdc_v2_subpaquetes.php` | `MissingProjectScope` | RLS-SCOPE |
| 51 | `test_pdc_v2_subpaquetes_volumen.php` | `MissingProjectScope` | RLS-SCOPE |
| 52 | `test_pdc_v2_tamiz_presupuesto.php` | `MissingProjectScope` | RLS-SCOPE |
| 53 | `test_pdc_v2_vencimientos.php` | `MissingProjectScope` al llegar a `vencimientos()` | RLS-SCOPE |
| 54 | `test_pdc_v2_versionado.php` | `MissingProjectScope` | RLS-SCOPE |
| 55 | `test_pg_pasado_servidor.php` | `MissingProjectScope` | RLS-SCOPE |
| 56 | `test_preconstruction_import_global_ids.php` | schema calificado rechazado | RLS-SCHEMA |
| 57 | `test_program_unique_id_refactor.php` | migración no carga `DataScopeContext` | BOOTSTRAP |
| 58 | `test_report_processor_cic_project_scope.php` | `MissingProjectScope` | RLS-SCOPE |
| 59 | `test_report_processor_project_scope.php` | schema calificado rechazado | RLS-SCHEMA |
| 60 | `test_schedule_update_draft_import.php` | schema calificado rechazado | RLS-SCHEMA |
| 61 | `test_semanal_sanear_csrf.php` | POST sin token devolvió 200, esperaba 403 | HTTP-ENV |
| 62 | `test_weekly_governance.php` | `MissingProjectScope` | RLS-SCOPE |

#### El test sospechoso

| Test | Primera causa observable | Clase | Relación con diff |
|---|---|---|---|
| `test_project_scope_schema_contract.php` | salió 0 sin imprimir ninguna comprobación | FIXTURE-CONTRACT | dominio RLS, archivo no tocado; no demuestra verde ni rojo |

#### Los 10 errores PHPUnit

| # | Caso | Primera causa | Clase / relación |
|---:|---|---|---|
| 1 | `CarryoverAvanceSemanalTest::testSumaElAvanceReportadoEnLaSemanaOrigen` | `MissingProjectScope` en fixture línea 97 | RLS-SCOPE; unit no tocado |
| 2 | `CarryoverAvanceSemanalTest::testElArrastreEsIdempotente` | `MissingProjectScope` en fixture línea 97 | RLS-SCOPE; unit no tocado |
| 3 | `CarryoverAvanceSemanalTest::testSumaUnAvanceReportadoDespuesDeLaPrimeraCorrida` | `MissingProjectScope` en fixture línea 97 | RLS-SCOPE; unit no tocado |
| 4 | `CarryoverAvanceSemanalTest::testDestrabaUnaFilaCongeladaSinTestigo` | `MissingProjectScope` en fixture línea 97 | RLS-SCOPE; unit no tocado |
| 5 | `CarryoverAvanceSemanalTest::testNoPisaLaEdicionManualDelResidente` | `MissingProjectScope` en fixture línea 97 | RLS-SCOPE; unit no tocado |
| 6 | `PgAvanceEdicionManualTest::testRegistraUnaEdicionDirecta` | `MissingProjectScope` en fixture línea 68 | RLS-SCOPE; unit no tocado |
| 7 | `PgAvanceEdicionManualTest::testNoRegistraSiElValorNoCambio` | `MissingProjectScope` en fixture línea 68 | RLS-SCOPE; unit no tocado |
| 8 | `PgAvanceEdicionManualTest::testNoRegistraLaHerenciaSiLaAsociacionNoCambio` | `MissingProjectScope` en fixture línea 68 | RLS-SCOPE; unit no tocado |
| 9 | `PgAvanceEdicionManualTest::testRegistraLaHerenciaSiLaAsociacionCambio` | `MissingProjectScope` en fixture línea 68 | RLS-SCOPE; unit no tocado |
| 10 | `PgAvanceEdicionManualTest::testDevuelveFalseSiElInsertFalla` | `MissingProjectScope` en fixture línea 68 | RLS-SCOPE; unit no tocado |

Conclusión del runner: los focos Task 6 que alcanzan el SUT están verdes, pero el gate global queda
`DONE_WITH_CONCERNS` por los 62 fallos, el sospechoso y los 10 errores arriba. La evidencia no
permite atribuirlos al baseline ni descartarlos como regresiones sin una comparación base válida.

### Gates finales de la ronda correctiva

- Guard completo: `51 tests, 77 assertions`, PASS; DatabaseWrapper: `7 checks`, PASS.
- Scope BI y aislamiento A/B/C: PASS; MetricExecutor: `4 tests, 24 assertions`, PASS.
- PDC positivo A/B/C, paridad legacy, exclusión de C y orden observable: `46 PASS`, cleanup exacto.
- Consumidores focales: filters `3 PASS`, governance PASS, activity timeline PASS, chart-values
  `4 PASS`, CNP PASS, CNC PASS, paridad `27/0`, semáforo `57/0`, real-data `101/0`, línea base
  PASS y PDC RBAC `9 PASS`.
- Auditoría de callsites: `0 hallazgos`.
- PHPStan sobre los cuatro archivos productivos corregidos: `No errors`; `php -l` sobre los diez
  PHP de la ronda: PASS; `git diff --check`: limpio.
- El intento de PHPStan mediante `docker compose run --rm --no-deps app` no fue evidencia válida:
  ese contenedor efímero no montó el worktree y reportó que la ruta no existía. El gate válido se
  ejecutó en el servicio `app`, cuyo `/var/www/html` estaba verificado contra este worktree.
