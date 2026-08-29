# Task 7 — Fix round 5: frontera conservadora final del inventario DDL

## STATUS round 5

`CODE_BLOCKED`

Los cuatro findings Important de la rerevisión R4 quedaron corregidos sobre la base exacta
`4d9d3f938b271cdf10d4a4eb76367cabfc85b0be`. La solución mantiene una frontera conservadora y
auditable: resuelve solamente callables locales demostrables, analiza providers como raíces,
enlaza argumentos nombrados contra la firma declarada y degrada efectos by-reference no
demostrables a `UNKNOWN`. El lattice conserva alternativas completas acotadas en lugar de
reducirlas a `SELECT`; cualquier alternativa DDL domina. Los payloads MySQL `/*!...*/` se extraen
fuera de strings/comentarios ordinarios y se inspeccionan statement por statement.

No se añadieron allowlists por archivo ni se reetiquetaron pruebas BI/reconciliation.
`call_user_func` externo y factories first-class/`Closure::fromCallable` no resolubles fallan
cerrado. El único `call_user_func` real no-DB se reemplazó en su propio contrato por un `match`
exhaustivo de llamadas estáticas explícitas, una forma más clara y comprobable por sí misma; no se
añadió una excepción al scanner.

La rerevisión fresca de la ronda 5 activó el breaker: el scanner conserva falsos negativos
load-bearing y no puede aprobarse como frontera de seguridad. El data gate queda `CODE_BLOCKED`;
no existe una ronda 6 incremental. No se ejecutó la lane `admin-db`, `--apply`, `--enforce`,
DDL/DML, grants, usuarios, cambios de credenciales, `compose up/recreate`, deploy, merge o publish.

## TDD RED round 5

```text
inventario adversarial inicial: 9 fallos de 52 comprobaciones
  helper heredado neutral no alcanzado
  call_user_func local no alcanzado
  DataProvider con DDL no inspeccionado
  named args enlazados por posición textual
  closure use (&$sql) y parámetro by-ref sin efecto conservador
  fragmentos branch/foreach colapsados antes de concatenar
  payload /*!50003 SET ...; DROP ... */ marcado safe

runner boundary inicial: 1 fallo de 43 comprobaciones
  fixture runtime compuesto R5 no abortaba --solo-listar

RED posterior a revisión independiente: 3 fallos de 55 comprobaciones
  call_user_func externo se aceptaba sin prueba de ausencia de DB
  DataProviderExternal no entraba al inventario
  wrapper heredado desde padre fuera del source no fallaba cerrado

RED de checklist final: 3 fallos de 58 comprobaciones
  first-class callable y Closure::fromCallable externos no degradaban a UNKNOWN
  global escrito dentro de callable no invalidaba el binding posterior
```

El probe literal heredado llamado `runSql` ya activaba la heurística nominal histórica; se añadió
además `applyFixture` para demostrar RED estructural sin depender del nombre. Todos los RED fueron
análisis de strings/AST o `--solo-listar`: el SQL de los fixtures nunca se ejecutó.

## GREEN round 5

- Inventario adversarial: `58/58`; conserva los 41 probes R4 y añade herencia, call_user_func,
  provider, named args, closure/parámetro by-ref, fragmentos branch/foreach y comentario versionado
  con SET seguro y con DROP posterior, más `DataProviderExternal`, first-class,
  `Closure::fromCallable`, padre externo y global escrito.
- Runner boundary: `43/43`; el fixture PHPUnit runtime R5 aborta con RC 2 y queda nombrado sin
  ejecutarse.
- Inventario real: RC 0, cero violaciones; `136` tests del runner descubiertos, nivel puro
  `31/105`, más entradas PHPUnit. Tiempo real final: `40.59 s`, acotado y terminando.
- Durante la convergencia aparecieron tres falsos positivos reales y una regresión propia:
  reconciliation excedía el límite de alternativas correlacionadas; BI devolvía fragmentos opacos
  compuestos solo por identificadores source-controlled; design system usaba un callable externo
  finito y demostrablemente no-DB; dos contratos de sesión usaban la función de shell `exec()`, que
  se había confundido con `PDO::exec()`. Los cuatro quedaron resueltos por semántica general, sin
  excepciones por archivo.
- PHP lint limpio en los cinco PHP modificados/nuevos; PHPStan focal del helper/runner:
  `[OK] No errors`; `git diff --check` sin salida.
- Workflow visual: `13/13`. Listados read-only `db`, `http` y `admin-db`: RC 0; clasificación sin
  cambios (`136/89/47`, `136/99/37`, `136/1/135`). Admin selecciona únicamente el migrador y omite
  `test_php_test_runner.php`.
- Contrato schema/inventario: `Pure contracts: 215 checks`, RC 0 en `--audit`, con los cuatro
  hallazgos físicos/de clasificación ya conocidos. Reconciliación read-only RC 0 pero vacía:
  `Tablas legacy verificadas: 0`.

## DRY_RUN round 5

```text
=== Project Scope Schema Migration: DRY-RUN ===
DRY-RUN SQL: ALTER TABLE `auto_program_log` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `general_pdc_project_family_strategy` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `programa_consolidado_estado_respaldo_20260819` ADD INDEX `idx_programa_consolidado_estado_respaldo_20260819_project_scope` (`project_id`)
tables_checked=56 null_rows=0 columns_changed=2 indexes_added=1
No statements executed. Use --apply only after the data-change gate.
```

Se ejecutó exclusivamente dentro del `app` efectivo y sin `--apply`. Dos intentos iniciales de
audit/reconciliation en contenedores efímeros sin el entorno runtime se descartaron como evidencia;
los resultados válidos se obtuvieron después con `docker compose exec -T app`, sin inyectar ni
imprimir credenciales.

## GATE y concerns round 5

1. `CODE_BLOCKED`: el breaker R5/5 rechazó el scanner como frontera fail-closed.
2. El auditor efectivo read-only continúa sin certificar la cuenta actual:
   `runtime_db_grants=fail reason=no-grants grants_checked=0`; no imprimió grants.
3. `backup_licify_general_informe_pdc_20260612` continúa `Unclassified/denied`.
4. La reconciliación verde continúa vacía y no demuestra paridad de fuentes legacy.
5. La lane administrativa solo se analizó/listó; no se ejecutó localmente.
6. Commit round 5: `65005169`.

## Rerevisión final y breaker R5/5

Veredicto independiente: `NEEDS FIXES`. Los cuatro findings R4 no quedaron cerrados de forma
sonora:

- Herencia/callables/providers sigue abierta: un padre externo sin argumentos inseguros, aliases
  de first-class/`Closure::fromCallable`, callables pasadas por parámetro y colisiones FQCN de
  `DataProviderExternal` producen cero findings.
- Named args y by-reference quedan parciales: los casos básicos pasan, pero un wrapper variadic
  pierde argumentos posteriores capaces de formar DDL.
- El lattice de alternativas sigue abierto: branch→array→foreach puede perder la rama DDL y
  `safe_fragment` puede ocultar un `DROP` construido por el caller. Además pierde correlación y
  genera falsos positivos en pares imposibles.
- Los comentarios versionados quedan parciales: detectan payloads ejecutables reales, pero la
  segunda inspección raw marca `/*!...*/` inerte dentro de strings/comentarios ordinarios.

Adjudicación del breaker: los falsos negativos de call graph, providers, variadics y joins son
load-bearing porque el inventario decide qué prueba puede entrar a la lane runtime. No se aparcan
como deuda ni se solicita autorización de datos. La estrategia incremental de emular PHP/SQL en un
scanner monolítico queda agotada después de cinco rondas; para reabrir Task 7 hace falta aprobar una
arquitectura distinta donde el usuario MySQL DML-only sea la frontera autoritativa y la
clasificación admin sea declarativa/aislada, no inferida como control de seguridad.

## Historial: fix round 4

## STATUS round 4

`DONE_WITH_CONCERNS`

Los cuatro findings Important de la rerevisión quedaron corregidos sobre la base exacta
`cd10497f35b906cdcd39ddbfc3617fc3ceb58095`. El scanner incorpora métodos PHPUnit y closures al
grafo de llamadas, trata todo SQL incompleto como `UNKNOWN`, une ramas por may-analysis y aplica la
regla real de MySQL para comentarios `--`. Una fuente `DELIMITER` no se sobreinterpreta: se rechaza
fail-closed, de modo que un trigger nunca queda clasificado como DML seguro por un split parcial.

El análisis de wrappers y retornos usa un punto fijo acotado; el entorno abstracto conserva
alternativas DDL/unknown sobre las seguras. Para evitar allowlists por archivo, las construcciones
del inventario real se resuelven mediante contratos de expresiones y callables: constantes de
clase source-controlled, `TableResolver`, helpers puros, capturas/globales, retornos destructurados
y el contrato SQL-preserving de `Database::insertProjectId`. Un argumento runtime no queda saneado
por esos contratos.

El data gate permanece `CODE_BLOCKED_PENDING_REREVIEW`. No se ejecutó la lane `admin-db`, ni
`--apply`, `--enforce`, DDL/DML, grants, usuarios, credenciales, `compose up` o recreación.

## TDD RED round 4

```text
inventario adversarial inicial: 10 fallos de 38 comprobaciones
  método PHPUnit directo/anidado no detectado
  closure DML marcada por atravesar su cuerpo aunque no ejecutaba DDL
  SELECT interpolado/alias dinámico marcado safe
  if/switch/loop ocultaban DDL por última asignación lexical
  SELECT 1--2; DROP ocultaba el segundo statement
  trigger con DELIMITER marcado safe

runner boundary inicial: 1 fallo de 41 comprobaciones
  wrapper DDL en método PHPUnit runtime no bloqueaba --solo-listar

inventario real durante la convergencia: 14 -> 5 -> 3 -> 1 falsos positivos
regresión SQL-preserving focal: 1 fallo de 40
coalesce dinámico focal: 1 fallo de 41
```

Todos los RED fueron análisis de strings/AST o `--solo-listar`; ningún SQL de fixture se ejecutó.

## GREEN round 4

- Inventario adversarial: `41/41`; conserva los 25 casos de round 3 y cubre método directo/anidado,
  closure DDL/DML, interpolación y alias dinámicos, concat constante, `if/else`, `switch`, loop,
  ternario/coalesce, regla `--`, `DELIMITER`, retorno/destructuring y transformer SQL-preserving.
- Runner boundary: `41/41`; el nuevo fixture PHPUnit runtime aborta con RC 2 y se nombra sin
  ejecutarse.
- Inventario real: `153 files`, `0 violations`; no hubo reetiquetado ni allowlist por archivo.
- Contrato schema/grants/inventario: `Pure contracts: 215 checks`, RC 0 en `--audit`, con los cuatro
  hallazgos físicos/de clasificación ya conocidos.
- Workflow visual: `13/13`; lint de los cuatro PHP modificados/nuevos y PHPStan focal del
  scanner/runner: `[OK] No errors`.
- Listados read-only: `db 136/89/47`, `http 136/99/37`, `admin-db 136/1/135`; el migrador solo se
  selecciona en admin y `test_php_test_runner.php` queda fuera de esa lane.
- Compose base y CI renderizados con valores efímeros y salida booleana redactada: runtime user en
  app/DB; app sin secreto o marker admin; root remoto ausente en base y presente solo en CI.
- Reconciliación read-only: RC 0, pero vacía (`Tablas legacy verificadas: 0`). `git diff --check`
  sin salida.

## DRY_RUN round 4

```text
=== Project Scope Schema Migration: DRY-RUN ===
DRY-RUN SQL: ALTER TABLE `auto_program_log` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `general_pdc_project_family_strategy` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `programa_consolidado_estado_respaldo_20260819` ADD INDEX `idx_programa_consolidado_estado_respaldo_20260819_project_scope` (`project_id`)
tables_checked=56 null_rows=0 columns_changed=2 indexes_added=1
No statements executed. Use --apply only after the data-change gate.
```

## GATE y concerns round 4

1. `CODE_BLOCKED_PENDING_REREVIEW`: los tres ALTER siguen propuestos y no aplicados.
2. El auditor efectivo read-only no certificó la cuenta actual:
   `runtime_db_grants=fail reason=invalid-line grants_checked=2`; no imprimió grants.
3. `backup_licify_general_informe_pdc_20260612` continúa `Unclassified/denied`.
4. La reconciliación verde continúa vacía y no demuestra paridad de fuentes legacy.
5. La lane administrativa solo se analizó/listó; no se ejecutó localmente.
6. Commit round 4: `4d9d3f93`.

## Historial: fix round 3

## STATUS

`DONE_WITH_CONCERNS`

El único finding Important de la rerevisión quedó corregido en scanner, fixtures, tests y runbook.
El inventario dejó de ser una regex lineal evadible: ahora parsea el AST de PHP, propaga valores y
parámetros de wrappers hasta punto fijo y falla cerrado ante SQL dinámico no clasificable. La
separación runtime/admin de round 2 permanece intacta y la lane admin no se ejecutó localmente.

El data gate continúa `CODE_BLOCKED_PENDING_REREVIEW`. No se aplicó schema ni se mutaron filas,
cuentas, grants, credenciales activas, contenedores o volúmenes.

## Fix round 3

- Base exacta: `5e540d746b36af401f14bf93d91b47fc5932989c`.
- Commit: `cd10497f35b906cdcd39ddbfc3617fc3ceb58095`.
- Alcance: `scripts/lib/php-test-ddl-inventory.php`, test puro nuevo, fixtures/contrato del runner y
  actualización causal del runbook. Workflow, Compose y runner de producción no cambiaron.

### Causa y decisiones

- El scanner anterior exigía una comilla seguida por `CREATE/DROP/ALTER/TRUNCATE TABLE`, guardaba
  un booleano por asignación inmediata y solo conocía una lista plana de sinks. No podía seguir
  heredoc/nowdoc, alias, wrappers locales o objetos distintos de TABLE.
- El nuevo análisis usa `nikic/php-parser`; resuelve strings, heredoc/nowdoc, concatenación, alias,
  arrays/foreach y rutas literales `.sql` confinadas al repositorio. No lee `.env`, configuración
  ni credenciales.
- Los parámetros de wrappers locales se resumen hasta punto fijo. Un valor DDL o desconocido que
  alcanza `exec/query/prepare` o helper SQL equivalente genera hallazgo; un SQL esperado que nunca
  llega a sink permanece inerte.
- Se cubren `CREATE`, `DROP`, `ALTER`, `TRUNCATE`, `RENAME`, `GRANT` y `REVOKE` sin depender del
  tipo de objeto, incluidos `CREATE USER`, statements posteriores a `;` y comentarios versionados.
- El inventario real inicialmente reveló tres falsos positivos: dos arrays/foreach estáticamente
  resolubles y un archivo SQL externo. Los dos primeros se resolvieron por flujo AST; el tercero se
  resolvió leyendo únicamente su ruta literal `.sql` source-controlled y clasificando cada
  statement. Ningún test DML se reetiquetó como admin.

### TDD RED round 3

```text
inventario adversarial inicial: 15 fallos de 20 comprobaciones
runner boundary: 1 fallo de 39 comprobaciones
  wrapper DDL anidado bajo runtime no bloqueaba

inventario real: 1 fallo de contrato / 3 archivos señalados
  2 arrays/foreach resolubles + 1 SQL source-controlled aún no resuelto

foreach focal: 2 fallos de 22 comprobaciones
segundo statement DDL: 1 fallo de 25 comprobaciones
```

Ningún probe ejecutó el SQL que contiene; todos operaron sobre strings/AST o `--solo-listar`.

### GREEN round 3

- Adversariales puros: `25/25`, incluidos literal single/double, expected-only, `execSql`, wrapper
  local/anidado, heredoc, nowdoc, alias chain, CREATE VIEW, familias DDL/admin, valor dinámico,
  arrays/foreach, segundo statement y DDL citado dentro de un literal SQL.
- Runner focal: `39/39`; el wrapper runtime aborta RC 2 y el fixture admin solo se lista.
- Contrato schema/grants/inventario real: `Pure contracts: 215 checks`, RC 0 en `--audit`; quedan
  solo los cuatro hallazgos físicos/ clasificación ya conocidos.
- Workflow visual: `13/13`. PHP lint sin errores y PHPStan focal `[OK] No errors`.
- Listados: `db 136/89/47`, `http 136/99/37`, `admin-db 136/1/135`; el migrador solo aparece
  seleccionado en admin y el nuevo test puro queda excluido de admin.
- Render Compose redactado conserva app/DB runtime, app sin secreto/marker admin y root remoto solo
  en CI. `git diff --check` sin salida.

## Base, alcance y commit de round 2

- Base exacta: `39c2530e970df8e397a68569facee422269ae44d`.
- Commit: `5e540d746b36af401f14bf93d91b47fc5932989c`.
- Expansiones autorizadas usadas: runner y su test/fixtures, workflow y su contrato visual,
  `docker-compose.ci.yml`, el test migrador, el runbook y la librería causal compartida de
  inventario DDL.
- Este reporte y `progress.md` son artefactos locales ignorados por git.

## Evidencia conservada de fix round 1

Esta ronda parte del fix `39c2530e970df8e397a68569facee422269ae44d`, construido sobre
`48e06072cecff2ecf5cc8784043357131c08995b`. Se conserva la evidencia causal previa:

- RED catálogo: `7 tests / 19 assertions`, con `1 error + 2 failures` por clasificación/tipo de
  `system_notifications` y metadata física ausente.
- RED contrato: `56 checks / 10 failures`; además preflight anterior RC 0 y USAGE-only anterior
  RC 0. Los dos fixtures DDL señalados entonces se localizaron sin ejecutarlos.
- GREEN focal: `72 tests / 150 assertions`; wrapper read-only `7 checks`; contrato schema/grants
  `65 checks`; PHPStan/lint y workflow histórico `12/12`.
- El catálogo real caracterizó nueve VIEWs Project lógicas, excluidas de convergencia física.
  `system_notifications` quedó Identity, `varchar(100)`, con `157/157` filas de metadata intactas.
- El auditor read-only de la cuenta efectiva terminó
  `runtime_db_grants=fail reason=invalid-line grants_checked=1` sin imprimir grants.
- La reconciliación read-only terminó RC 0 pero fue vacía: `Tablas legacy verificadas: 0`.

El finding que sobrevivió aquella rerevisión —el cuarto test con DROP/CREATE todavía seleccionado
por `http`— es precisamente el corregido por esta round 2.

## Decisiones implementadas en round 2

- `scripts/run-php-tests.php` reconoce `admin-db` como lane no acumulativa. `db`, `http` y
  `datos-proyecto` nunca seleccionan tests administrativos; `admin-db` tampoco arrastra `puro` o
  `db`.
- `test_migrate_legacy_to_global.php` declara `admin-db` y además exige
  `LPS_ADMIN_DB_LANE=1`, de modo que una invocación directa accidental falla con RC 2.
- Antes de listar o ejecutar, el runner inventaría todos los scripts y clases PHPUnit que él mismo
  descubre. El scanner basado en tokens identifica DDL pasado a llamadas SQL ejecutables y exige
  la clasificación `admin-db`; SQL esperado que solo se almacena o compara no se marca.
- CI conserva `php-suite --nivel=http` con el usuario runtime DML-only y añade un step separado
  `php-admin-db`. Sus variables admin existen únicamente en ese step y se pasan por nombre al
  proceso `docker compose exec`; no se incorporan al servicio `app`.
- `MYSQL_ROOT_HOST=%` está únicamente en `docker-compose.ci.yml` para la base desechable del job.
  No amplía grants runtime ni modifica Compose local/producción.
- El outcome de la lane admin entra al resumen y al gate final del workflow; un fallo no queda
  oculto por `continue-on-error`.

## TDD: RED observado en round 2

Las primeras ejecuciones fueron puras/listados; no corrieron fixtures DDL.

```text
tests/test_php_test_runner.php: 9 fallos de 33 comprobaciones
causa: admin-db no existía y el runner acumulativo no podía incluir/excluir la nueva lane

visual-ci-contract.test.mjs: 12 pass / 1 fail
causa: faltaba el step CI admin aislado

test_project_scope_schema_contract.php --audit: RC 1
FAIL: test_migrate_legacy_to_global.php contiene DDL ejecutable y no declara admin-db.

fixture DDL mal etiquetado: 1 fallo de 35 comprobaciones
causa: el runner todavía no abortaba su inventario antes de --solo-listar
```

## GREEN y verificaciones de round 2

- Runner focal: `OK: 35 comprobaciones pasaron`.
- Contrato workflow/Compose: Node test runner `13/13`, RC 0.
- Contrato schema/grants e inventario: `Pure contracts: 214 checks`, RC 0 en `--audit`; conserva
  cuatro hallazgos factuales no bloqueantes del modo audit.
- Listados finales:

```text
db:       135 descubiertos, 88 seleccionados, 47 omitidos; migrate [omite]
http:     135 descubiertos, 98 seleccionados, 37 omitidos; migrate [omite]
admin-db: 135 descubiertos, 1 seleccionado, 134 omitidos; migrate [ejecuta]
          test_php_test_runner.php y SystemScopeRunnerTest.php [omite]
```

- PHP lint: siete archivos modificados/nuevos, sin errores.
- PHPStan focal de runner/scanner: `[OK] No errors`.
- Render de Compose CI con valores efímeros y salida booleana redactada: usuario runtime en app y
  DB; app sin secreto ni marker admin; root remoto solo en la DB CI.
- `git diff --check`: sin salida.
- La lane `admin-db` real no se ejecutó localmente. Solo se verificó `--solo-listar`.

## DRY_RUN exacto

Migración ejecutada sin `--apply` sobre el MySQL compartido ya existente:

```text
=== Project Scope Schema Migration: DRY-RUN ===
DRY-RUN SQL: ALTER TABLE `auto_program_log` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `general_pdc_project_family_strategy` MODIFY `project_id` INT NOT NULL
DRY-RUN SQL: ALTER TABLE `programa_consolidado_estado_respaldo_20260819` ADD INDEX `idx_programa_consolidado_estado_respaldo_20260819_project_scope` (`project_id`)
tables_checked=56 null_rows=0 columns_changed=2 indexes_added=1
No statements executed. Use --apply only after the data-change gate.
```

No aparece ninguna de las nueve VIEWs ni `system_notifications`. No se encontraron NULLs en las
56 tablas físicas Project y no hay backfill que diseñar para este snapshot. Los tres ALTER siguen
siendo propuesta no aplicada.

## GATE_STATE

`CODE_BLOCKED_PENDING_REREVIEW`

No ejecutado:

- lane `admin-db` real ni el fixture DROP/CREATE;
- `--apply`, `ALTER`, otro DDL o DML/backfill;
- freeze, backup, restore o proyecto 0;
- `CREATE USER`, `ALTER USER`, `GRANT` o `REVOKE`;
- cambios o lectura/impresión de credenciales activas;
- `docker compose up`, recreate, init de volumen o cambio del volumen actual;
- `--enforce` contra un schema no aplicado.

## CONCERNS

1. La lane administrativa solo queda validada de forma estática/listada localmente; su ejecución
   real ocurre exclusivamente en CI contra la DB efímera una vez integrado el cambio.
2. Los tres ALTER del dry-run no están aplicados: quedan dos columnas nullable y un índice físico
   pendiente.
3. `backup_licify_general_informe_pdc_20260612` continúa `Unclassified/denied`; junto con los tres
   hallazgos físicos mantiene `--enforce` fuera de alcance.
4. La cuenta efectiva actual no pasó el auditor exacto en la ronda anterior y no se alteró en esta
   ronda.
5. La reconciliación read-only anterior fue verde pero vacía (`0` fuentes legacy), por lo que no
   prueba paridad.

## Adenda de replanificación — Task 5 (2026-08-29)

El estado histórico `CODE_BLOCKED` del Task 7 y su breaker R5 se conserva literalmente. Esta adenda
no reescribe la evidencia, no convierte el scanner en una frontera perfecta y no cambia los gates
de datos ya registrados.

La replanificación aprobada está documentada en el [plan RLS Runtime Boundary](../../../docs/superpowers/plans/2026-08-29-rls-runtime-boundary.md).
Su decisión arquitectónica mueve la autoridad de seguridad a la cuenta runtime DML-only y hace que
`admin-db` sea una lane declarativa, aislada en una DB efímera con credencial one-off. El scanner
queda como diagnóstico advisory: callables dinámicos, providers externos y joins de flujo pueden
quedar fuera de su demostración, por lo que ningún resultado del inventario autoriza `--apply`, DDL,
DML, grants, usuarios o `--enforce`.
