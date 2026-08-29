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
