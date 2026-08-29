# SDD ledger — plan: docs/superpowers/plans/2026-08-28-rls-aplicacion-fail-closed.md

Started: 2026-08-29, worktree `shell-minimo-react`.

## Setup and baseline

- Linked worktree verified: git dir differs from common dir; branch is not `main`.
- Container mount verified: this worktree → `/var/www/html`.
- Dependencies: `npm ci` RC 0; lockfile unchanged. Existing audit reports 8 dependency
  vulnerabilities and legacy install-script warnings; no unrelated upgrade attempted.
- Baseline: global-table safety RC 0; DatabaseWrapper 32/32; PHPUnit unit 84 tests / 208
  assertions, RC 0; RBAC parity 1/1, RC 0.
- Baseline noise: PHPUnit logs the pre-existing absence of optional table
  `pg_avance_edicion_manual`; it remains RC 0 and is not attributed to this plan.
- Setup documentation committed as `f4222ac2` (`docs(shell): aprobar planes de paridad y rls`).

## Preflight consistency scan

| Task(s) | Producer → consumer / self-check | Finding |
|---|---|---|
| 1 | Tests → enum, definitions, catalog, cached Database accessor → audit contract | Internally consistent; audit intentionally permits findings until Task 7. |
| 2 | Tests → immutable scopes/context → Database lifecycle adapter | Internally consistent after clarifying that the singleton shares one context object. |
| 3 | Resolver tests → membership-first resolver → middleware/front controller/session contract | Internally consistent; later consumers must read the request-scoped failure reason, not revalidate. |
| 4 | SQL matrix → conservative guard → Database APIs → A/B DB test | Undefined `DataScope` type and SystemScope transition found; ruled below and corrected in plan. |
| 5 | Static audit → identity callsites, canonical scope and SystemScopeRunner → regressions | Runner wording contradicted its empty-context invariant; ruled below and corrected. |
| 6 | BI tests → authorized MultiProjectScope → Database API → BI callsites | Empty-user and identity-join semantics were ambiguous; ruled below and corrected. |
| 7 | Grant fixtures + dry-run migration → compose/runbook → enforce gates | Internally consistent only up to dry-run; apply/grants are a separate stop gate. |
| 8 | HTTP red → typed handling/logging → CI/docs/completion | Cross-process transaction wording was invalid; ruled below and corrected to snapshot/restore. |
| 1 ↔ 2 | `Database.php`: cached table catalog ↔ process-local data-scope context | Independent properties; no lifecycle conflict. |
| 1 ↔ 3 | Identity classification → membership-first resolver queries | Resolver uses identity SQL and never requires an operational scope. |
| 1 ↔ 4 | `TableScopeCatalog` → `ProjectSqlGuard` | One catalog remains authoritative; unclassified stays denied. |
| 1 ↔ 6 | Catalog/Database accessor → multiproject Database API | BI must reuse the same table classification. |
| 1 ↔ 7 | Schema contract in audit → migration → same contract in enforce | Deliberate red-to-green dependency; `--apply` is gated. |
| 1 ↔ 8 | Schema/callsite contracts → CI RLS gates | Task 8 consumes, does not duplicate, Task 1 contracts. |
| 2 ↔ 3 | `ProjectScope`/context → resolver and request lifecycle | Task 3 binds exactly the objects Task 2 produces. |
| 2 ↔ 4 | Scope objects/context → SQL guard union | Guard accepts the explicit union; no missing abstraction is introduced. |
| 2 ↔ 5 | `ProjectScope`/`SystemScope` → canonical callsites and runner | Runner requires empty context and always clears. |
| 2 ↔ 6 | `MultiProjectScope` → BI and Database | Empty sets remain invalid; authorization occurs before construction. |
| 2 ↔ 8 | Typed scope exceptions → safe HTTP mapper/logger | Only typed scope failures map to non-enumerable responses. |
| 3 ↔ 5 | `ProjectAccessService` and scope binding → removal of request-derived overrides | Task 5 preserves the resolver authority introduced by Task 3. |
| 3 ↔ 8 | `public/index.php`: lifecycle → typed exception boundary | Bind happens once before dispatch; cleanup remains registered after handling. |
| 4 ↔ 5 | Guard tokenizer → static callsite audit | Audit reuses parsing semantics rather than a divergent regex policy. |
| 4 ↔ 6 | `Database.php` single-project guard → explicit multiproject API | Separate APIs share parsing/catalog but cannot exchange authority objects. |
| 4 ↔ 8 | Fail-closed Database behavior → HTTP A→B scenarios | HTTP test exercises the real gateway rather than duplicating row filtering. |
| 5 ↔ 6 | Project+identity joins and explicit system bypasses → BI migration | Identity-only operations stay dedicated; scoped joins remain valid. |
| 5 ↔ 8 | Callsite audit → mandatory CI gate | Task 8 promotes Task 5's audit unchanged. |
| 6 ↔ 8 | BI isolation behavior → final RLS completion | Valid/foreign project-set scenarios remain part of the final security gate. |
| 7 ↔ 8 | Schema/grant enforce → CI and completion gate | Task 8 may close only after Task 7's separate mutation gate is authorized and green. |

## Preflight rulings

- Ruling: treat Felipe's explicit approval of the spec/plans and choice of Subagent-Driven
  execution as the front declaration and plan gate; create `goals/paridad-shell-react-rls/` to
  satisfy repository governance — the cost if wrong is a small documentation-only commit to
  rename or retire, not product behavior.
- Ruling: resolve the app container through `docker compose ps -q app` instead of the obsolete
  literal container name `app` — the cost if wrong is a failed read-only mount check, never a
  mutation.
- Ruling: type `ProjectSqlGuard::guard()` with the explicit scope union rather than inventing an
  unplanned `DataScope` abstraction — the cost if wrong is a later signature refactor confined to
  the new security namespace.
- Ruling: `SystemScopeRunner` requires an empty context and never restores a previous scope — the
  cost if wrong is rejecting a maintenance call that attempted unsafe nested authority.
- Ruling: BI `resolve()` returns `[]` without a user, while `scope()` rejects that empty result —
  the cost if wrong is a fail-closed BI error instead of silently selecting a project.
- Ruling: `queryForProjects()` rejects identity-only SQL but permits identity joins anchored by a
  project-scoped root — the cost if wrong is a denied BI report, not cross-project exposure.
- Ruling: HTTP isolation fixtures use committed marker rows plus exact snapshot/restore because
  Apache cannot share the test process transaction — the cost if wrong is test cleanup work; the
  marker namespace and `finally` prevent touching human rows.
- Ruling: Task 7 stops after dry-run and code verification before applying schema or changing
  grants in the shared DB; plan approval is not data-mutation approval — the cost if wrong is that
  RLS completion waits for Felipe instead of mutating shared state without consent.
- Ruling: invoke `task-brief` and `review-package` with explicit output paths because their bundled
  helper lacks the executable bit in this plugin installation — the cost if wrong is only a missing
  scratch artifact; product files and git history are unaffected.

## Task 1

- Base: `f4222ac2062195a00c4364038a72209602a6642a`.
- Implementer: `/root/rls_task_1` (`gpt-5.6-terra`, high).
- Implementer result: `DONE_WITH_CONCERNS`, commit `36202e61`; focused tests green.
- Concern carried to Task 7: schema audit reports 13 structural findings and one explicitly denied
  backup table. This is expected inventory, not a Task 1 correctness gap.
- Reviewer: `/root/rls_task_1_review` (`gpt-5.6-terra`, high), package
  `review-f4222ac..36202e6.diff`.
- Task 1: complete (commits `f4222ac`..`36202e6`, review clean).

## Task 2

- Base: `36202e61d204297a7bab9eb6ecc74adcc547c317`.
- Implementer: `/root/rls_task_2` (`gpt-5.6-terra`, high).
- Implementer result: `DONE`, commit `40e855cb`; DataScopeContext 7/7 and
  DatabaseWrapper 37/37 green.
- Reviewer: `/root/rls_task_2_review` (`gpt-5.6-terra`, high), package
  `review-36202e6..40e855c.diff`.
- Task 2: complete (commits `36202e6`..`40e855c`, review clean).

## Task 3

- Base: `40e855cb6667ecc8eff795120926450654aa5060`.
- Implementer: `/root/rls_task_3` (`gpt-5.6-sol`, high).
- Implementer result: `DONE`, commit `58241d5b`; resolver 5/5, session contract, selector
  contract and focused PHPStan green.
- Reviewer: `/root/rls_task_3_review` (`gpt-5.6-sol`, high), package
  `review-40e855c..58241d5.diff`.
- Task 3: fix round 1/5 (1 addressed, 0 open — same-process controller lifecycle coverage;
  commit `79f653cc`).
- Re-review note: `/root/rls_task_3_rereview_1` could not launch a shell and issued no verdict;
  replacement `/root/rls_task_3_rereview_1b` reviewed the same package and approved it.
- Task 3: complete (commits `40e855c`..`79f653c`, review clean).

## Task 4

- Base: `79f653ccaa17d28106089d2342469a58e98833cd`.
- Implementer: `/root/rls_task_4` (`gpt-5.6-sol`, xhigh).
- Implementer result: `DONE_WITH_CONCERNS`, commit `78685c14`; guard 17/17, A/B DB,
  global-table safety and focused PHPStan green.
- Concern under review: `tests/DatabaseWrapperTest.php` is red because it reflects removed legacy
  private methods; Task 5 lists that file, but the task reviewer must decide whether deferral is
  acceptable.
- Reviewer: `/root/rls_task_4_review` (`gpt-5.6-sol`, xhigh), package
  `review-79f653c..78685c1.diff`.
- Task 4: minor (deferred): `queryWithProject()` docblock still describes the removed warning
  fallback; final review must triage documentation accuracy.
- Ruling: pull forward the minimal identity-only `queryWithProject()` compatibility behavior and
  `DatabaseWrapperTest.php` migration into Task 4 fix round 1, even though Task 5 lists the broader
  callsite migration — a load-bearing login and a fatal baseline test cannot remain broken between
  tasks; the cost if wrong is only that Task 5 finds fewer callsites and performs less work.
- Task 4: fix round 1/5 (6 addressed, 0 open — LEFT JOIN root, XOR, identity-source
  INSERT…SELECT, REPLACE, login compatibility and DatabaseWrapper; commit `1f63e4dc`).
- Task 4: complete (commits `79f653c`..`1f63e4d`, review clean; one minor deferred to final review).

## Task 5

- Base: `1f63e4dc4cb6f63f55cc6ba7577c2dabbb0ac420`.
- Implementer: `/root/rls_task_5` (`gpt-5.6-sol`, xhigh).
- Ruling: add `src/Core/Database.php` to Task 5 so `queryWithProject()` delegates through the
  existing guard when an explicit `SystemScope` is active, including legacy overrides that are data
  under that already-audited authority; without SystemScope the same calls still fail closed — the
  cost if wrong is an overly broad maintenance bypass, bounded by SystemScopeRunner's empty-context
  invariant and the static allowlist of its callers.
- Implementer result: `DONE_WITH_CONCERNS`, commit `657ec851`; audit 0, runner 3/3,
  DatabaseWrapper 6/6, guard 22/22 and auth/project contracts green.
- Concern under review: one PHPStan warning at `ProgramacionIntermediaController.php:624` is reported
  as pre-existing and outside the changed hunk.
- Reviewer: `/root/rls_task_5_review` (`gpt-5.6-sol`, xhigh), package
  `review-1f63e4d..657ec85.diff`.
- Ruling: expand Task 5 fix round 1 to the additional unresolved `queryWithProject()` callsites
  truthfully exposed by the now fail-closed audit, replacing only the legacy adapter with `query()`
  where authority already comes from the active ProjectScope/SystemScope; `ReportProcessor` may
  change calls but must never create a scope — the cost if wrong is that an unscoped legacy path
  fails earlier, which is the intended safe failure rather than cross-project execution.
- Task 5: fix round 1/5 (0 addressed, 2 open; commit `d6bfa674`). Re-review found that
  classifier exceptions still fail open and that the productive-root inventory remains incomplete;
  it also found a new Important gap for injected/factory-created `SystemScopeRunner` instances.
- Ruling: fix round 2 audits all repository-owned productive PHP at the root and under `src/`,
  `public/`, `views/`, `admin/`, `scripts/` and `database/`, excluding dependencies, tests and
  generated coordination artifacts — the cost if wrong is a conservative static-audit failure in
  an additional first-party entrypoint, not runtime data exposure.
- Ruling: outside the four exact authorized entrypoints, any productive reference to the
  `SystemScopeRunner` symbol is a finding in addition to recognizable `run()` calls — the cost if
  wrong is denying a new maintenance integration until its authority boundary is explicitly
  reviewed and allowlisted.
- Ruling: fix `ProjectSqlGuard` rather than teaching the audit to accept unsupported `FOR UPDATE`
  and derived-table SQL; runtime and static classification must share the same fail-closed parser,
  with recursive project-root tests — the cost if wrong is rejecting a complex report query until
  the parser can prove every nested root is scoped.
- Ruling: move the `general_decision_log` call from the legacy `queryWithProject()` adapter to the
  authoritative `query()` path without reclassifying its absent/unscoped schema; it remains blocked
  by the guard until Task 7 adds contractual `project_id` — the cost if wrong is an unavailable
  optional decision log, never an unscoped write.
- Ruling: replace controller SQL against `information_schema` and runtime `CREATE TABLE` with
  read-only catalog checks; schema discovery belongs to the catalog and schema creation to the
  separately authorized migration gate — the cost if wrong is a fail-closed missing-table response
  instead of self-modifying request behavior.
- Task 5: fix round 2/5 (3 addressed, 1 open; commit `9d48a40c`). The classifier exception,
  productive-root inventory and injected/factory runner findings are closed; re-review found a new
  Important directional bypass where a scoped right side of `LEFT JOIN` marked the preserved left
  side as scoped through the derived-root graph.
- Ruling: derived-scope propagation must preserve outer-join direction (`LEFT` cannot constrain its
  preserved left side from the right; `RIGHT` is symmetric; unsupported `FULL` fails closed), while
  `INNER` may propagate only across a proven `project_id` equality — the cost if wrong is rejecting
  a complex query rather than copying cross-project rows.
- Task 5: fix round 3/5 (1 addressed, 0 open — directional outer-join propagation; commit
  `78be898f`).
- Task 5: complete (commits `1f63e4d`..`78be898`, review clean; `general_decision_log` remains
  deliberately fail-closed pending the Task 7 schema contract).

## Task 6

- Base: `78be898f9b643afeadcc60f10387edf267dd7e4a`.
- Implementer: `/root/rls_task_6` (`gpt-5.6-sol`, xhigh).
- Ruling: add `src/Security/DataScope/ProjectSqlGuard.php` and its unit test to Task 6 so a
  separate multiproject guard can reuse the authoritative private parser; the existing
  single-project `guard()` remains unchanged and rejects `MultiProjectScope` — the cost if wrong is
  added central-gate surface, bounded by a distinct API and adversarial non-regression tests.
- Ruling: migrate the four existing MetricScope constructor tests to explicit
  `MultiProjectScope` fixtures rather than retain an array overload; arrays may carry presentation
  data but cannot create database authority — the cost if wrong is test-fixture churn revealing a
  real caller that must also adopt the explicit scope object.
- Ruling: repair `MetricExecutorTest` only at the fixture boundary with a connection-local temporary
  table and a test-local catalog snapshot/restore; production queries still use
  `queryForProjects()` and no runtime catalog-registration escape is added — the cost if wrong is a
  leaked singleton catalog inside the test process, mitigated by unconditional teardown restore.
- Ruling: run the three migrated metric scripts because inspection shows read-only queries/GETs and
  no maintenance DDL/DML; stop any script that attempts a non-fixture mutation — the cost if wrong
  is a read-only regression failure against current dev data, not a state change.
- Ruling: update the inherited PDC BI scope test so `resolve()` returns `[]` for an empty user while
  `scope()` remains the fail-closed authority boundary; this aligns the approved adapter contract
  without weakening RBAC or its per-user cache test — the cost if wrong is only a changed adapter
  expectation, with authority construction still rejected.
- Ruling: migrate all enumerated BI service test callers from integer/array authority to explicit
  `MultiProjectScope` fixtures, preserving IDs and expectations; do not retain overloads in
  `ControlTowerService`, `SeguimientoService`, `RiskScoringService` or
  `ActionRecommendationService` — the cost if wrong is broad test-signature churn, while the
  production boundary becomes strictly typed and non-forgeable from presentation arrays.
- Ruling: add `BiMetricController` to Task 6 and derive its single-project metric scope from the
  request's canonical `ProjectScope`, then revalidate it through `BiProjectScope::scope()`; never
  construct multiproject authority from the raw session integer — the cost if wrong is an extra
  membership lookup on metric reads, preferred over forged authority.
- Ruling: replace Control Tower's per-project legacy PDC calls with aggregate methods on the already
  contractual `SeguimientoService`, accepting one `MultiProjectScope` and using
  `queryForProjects()` for coverage and stale-plan maps; legacy module methods remain unchanged —
  the cost if wrong is duplicated PDC read logic, mitigated by parity characterization against the
  existing single-project results and an excluded-project case.
- Ruling: wrap only the PDC test's cross-project seed/cleanup in canonical `SystemScopeRunner`, and
  bind a temporary `ProjectScope` per project only for legacy parity oracles; new SUT reads remain
  explicit `MultiProjectScope` calls — the cost if wrong is fixture-only broad authority, bounded by
  `finally` cleanup and never exposed to production code.
- Ruling: allow test-only global discovery through `SystemScopeRunner` in the two read-only metric
  scripts and explicit project scopes for focal oracles; keep the HTTP endpoint test documented as
  environment-blocked when DEV_DOOR is closed, without changing configuration — the cost if wrong
  is reduced direct HTTP evidence, offset by lint and lower-level contract coverage.
- Ruling: rewrite `SeguimientoService::paquetesSinFechas()` nullable joins as project-prefiltered
  derived tables instead of weakening the guard's conservative `LEFT JOIN ON` policy; SQL semantics
  and parameters remain equivalent — the cost if wrong is a legacy query regression caught by the
  preserved single-project parity oracle.
- Ruling: add the shared `BiContractFixture` and permit lifecycle-only scope adaptation in the ten
  already enumerated BI callsite tests: SystemScopeRunner for seed/cleanup or unavoidable global
  discovery, ProjectScope for single-project oracles, MultiProjectScope for the SUT — the cost if
  wrong is broad fixture churn, bounded by unchanged data and expectations plus per-test cleanup.
- Ruling: align the activity-timeline test's project selector with the fixture project that actually
  owns week 3, without changing the week or expectations; the hard-coded project 73 currently has
  only weeks 1–2 — the cost if wrong is fixture-only selection drift, revealed by the unchanged
  timeline assertions.
- Implementer result: `DONE_WITH_CONCERNS`, commit `1e7a1b90`; focal A/B/C, guard,
  MetricExecutor, PDC, callsite audit, PHPStan and lint green.
- Concern under review: the broad data-project runner reports 135 total / 72 pass / 62 fail /
  1 suspicious, and PHPUnit reports 151 tests / 307 assertions / 10 errors; reviewer must classify
  whether any are Task 6 regressions versus inherited fail-closed lifecycle and closed HTTP gates.
- Reviewer: `/root/rls_task_6_review` (`gpt-5.6-sol`, xhigh), package
  `review-78be898..1e7a1b9.diff`.
- Reviewer findings: Critical correlated anti-subquery scope propagation; Important empty PDC
  coverage/staleness fixture; Important non-granular wide-suite evidence; Minor PDC detail ordering.
- Ruling: scope relations never propagate across distinct SELECT/query blocks; each physical
  project root must be independently authorized or the multiproject query fails closed — the cost
  if wrong is rejecting a correlated BI query until it carries explicit A/B filters in every block.
- Ruling: make PDC A/B/C evidence positive and distinguishable, including real C rows that remain
  excluded, instead of accepting zero-against-zero parity — the cost if wrong is additional
  fixture setup/cleanup only.
- Ruling: wide-suite status is not classified as inherited without granular names/errors and, when
  safely possible, a comparable base run; otherwise report uncertainty explicitly — the cost if
  wrong is retaining an open verification concern rather than overstating completion.
- Task 6: fix round 1/5 (4 addressed, 0 open): la frontera multiproyecto ya no propaga autoridad
  entre query blocks; los subqueries BI llevan anclas independientes; PDC demuestra A/B positivos
  y distintos con C sembrado/excluido; `detalleDestinos()` recupera el orden observable legacy.
- Evidencia amplia de la ronda: HEAD `135 = 72 pass / 62 fail / 1 suspicious`; PHPUnit
  `154 tests / 311 assertions / 10 errors`. La salida completa y su SHA-256 quedan registrados en
  `task-6-report.md`; no se ejecutó una comparación base inválida contra el servidor HTTP del HEAD
  y ningún fallo se etiqueta como preexistente.
- Task 6: fix round 2/5 (1 addressed, 0 open): `guardForProjects()` ya no permite que una raíz
  interna anclada transporte autoridad a una raíz externa mediante un alias derivado; cada bloque
  conserva filtros multiproyecto independientes y `guard()` single-project no cambia.
- Evidencia amplia round 2: HEAD `135 = 72 pass / 62 fail / 1 suspicious`; PHPUnit
  `156 tests / 315 assertions / 10 errors`. Los nombres y primeras causas coinciden con la tabla
  granular de round 1; artefacto completo y SHA-256 nuevos en `task-6-report.md`, sin atribución a
  baseline.
- Re-review de ronda 1: PDC positivo A/B/C, evidencia amplia granular y orden legacy aprobados;
  permanece un Critical porque una tabla derivada todavía puede transportar autoridad desde su
  raíz física interna hacia una raíz física no anclada del SELECT externo.
- Ruling: los alias derivados tampoco transportan autoridad entre query blocks; un alias lógico
  puede participar en la semántica local del bloque que lo consume, pero nunca convertir el ancla
  de una raíz física interna en prueba de scope para una raíz física externa — el cost if wrong es
  exigir filtros explícitos redundantes en consultas derivadas complejas, preferible a una fuga
  cross-project.
- Task 6: fix round 2/5 (0 addressed, 1 open — derived alias bridge), base `f4011c8d`.
- Task 6: fix round 2/5 implementada en `4cece4c5`: se elimina la propagación de autoridad
  `raíz interna -> alias derivado -> raíz externa`; guard `53 tests / 81 assertions` y matriz focal
  verde. El runner amplio conserva `135 = 72 pass / 62 fail / 1 suspicious` y PHPUnit
  `156 tests / 315 assertions / 10 errors`, sin atribución a baseline.
- Task 6: complete (commits `1e7a1b90`, `f4011c8d`, `4cece4c5`; re-review final clean). La
  rerevisión adicional probó alias repetido, igualdad invertida y derived-first INNER/LEFT, además
  de A/B/C y el wrapper; ningún finding Critical/Important permanece abierto.

## Task 7

- Base: `4cece4c5c2f0d8a8db5d943ed68d21035ca02d52`.
- Implementer: `/root/rls_task_7` (`gpt-5.6-sol`, xhigh).
- Gate: esta ejecución puede crear código, tests, documentación y obtener el dry-run; no puede
  ejecutar `--apply`, DDL/DML de backfill, `CREATE USER`, `GRANT`/`REVOKE`, recrear Compose ni
  certificar `--enforce` hasta completar freeze, backup, restore probado y autorización explícita.
- Ruling: el `MYSQL_USER` de la imagen oficial recibe privilegios amplios sobre `MYSQL_DATABASE`
  durante la inicialización; el Compose puede incluir un init config declarativo que revoca ese
  grant automático y concede únicamente DML explícito para volúmenes nuevos. No se ejecuta contra
  el volumen actual; el runbook mantiene un procedimiento separado y gated para volúmenes
  existentes — el cost if wrong es que un volumen nuevo falle al iniciar antes que otorgar ALL al
  usuario runtime.
- Implementer result: `DONE_WITH_CONCERNS`, commit `48e06072`; auditor/contrato `39` checks,
  PHPStan/lint/Compose render/reconciliación verdes.
- Dry-run factual (sin `--apply`): `tables_checked=66 null_rows=0 columns_changed=4
  indexes_added=11`; 15 ALTER propuestos y 0 ejecutados. No se requiere backfill en este snapshot.
- Gate state: `WAITING_FOR_EXPLICIT_DATA_CHANGE_AUTHORIZATION`. Persisten 13 hallazgos
  estructurales hasta aplicar; una tabla backup no clasificada mantiene `--enforce` bloqueado; el
  usuario runtime/init todavía no se ha probado en un volumen real.
- Reviewer: `/root/rls_task_7_review` (`gpt-5.6-sol`, xhigh), package
  `review-task7-4cece4c..48e0607.diff`; verdict `Needs fixes` before any data gate.
- Reviewer findings: Critical migration DDL against nine VIEWs; Critical destructive/inexecutable
  INT coercion of the textual `system_notifications.project_id`; Important CI DDL fixtures under a
  DML-only runtime account; Important connection failures returning RC 0; Important no documented
  admin apply path; Important grant parser accepts USAGE-only and a suffixed second GRANT.
- Ruling: `system_notifications` is Identity-scoped by its authenticated recipient; its nullable
  textual `project_id` stores legacy project-prefix metadata for grouping and is not the numeric
  RLS key. Add it explicitly to Identity definitions and characterize this contract instead of
  coercing or backfilling its 157 values — the cost if wrong is that project isolation for this
  table remains an application-level user_id boundary, matching every current read/write path.
- Ruling: physical schema convergence applies only to `BASE TABLE`; Project-classified VIEWs stay
  logically guarded but are never candidates for `MODIFY` or indexes. Catalog metadata must expose
  object type and column type, and the full DDL batch must be validated before the first autocommit
  statement — the cost if wrong is a blocked migration rather than a partial schema change.
- Ruling: never broaden the production runtime grant to satisfy tests. CI application queries run
  with the DML-only runtime account; any unavoidable fixture DDL uses a distinct ephemeral admin
  setup channel or is removed through test doubles/DML fixtures — the cost if wrong is extra CI
  setup complexity, preferred over normalizing DDL capability in runtime.
- Ruling: `--apply` uses an explicit one-off administrative connection documented behind the data
  gate; dry-run and application default remain runtime. Any connection/preflight failure returns
  non-zero — the cost if wrong is a failed deployment step instead of a false-green migration.
- Ruling: the grant auditor accepts `USAGE ON *.*` only as an additional neutral line, requires the
  complete contractual DML grant, anchors each grant to the full line and rejects extra statements
  or trailing syntax — the cost if wrong is rejecting noncanonical SHOW GRANTS output until it is
  explicitly supported.
- Task 7: fix round 1/5 implementada sobre `48e06072`; los seis findings tienen corrección y
  evidencia local, pendientes de rerevisión. El data gate sigue
  `CODE_BLOCKED_PENDING_REREVIEW`, no awaiting authorization.
- TDD RED reproducido sin mutaciones: catálogo (`1 error + 2 failures`), contrato auditor/migración
  (`10 failures`), preflight anterior RC 0, USAGE-only anterior RC 0 y los dos fixtures DDL
  localizados sin ejecutarlos. GREEN: PHPUnit focal, wrapper read-only `7 checks`, contrato
  schema/grants `65 checks`, PHPStan/lint, workflow 12/12, runner listing y render Compose.
- Dry-run real nuevo, sin `--apply`: RC 0; `tables_checked=56 null_rows=0 columns_changed=2
  indexes_added=1`; tres ALTER propuestos, ninguna VIEW ni `system_notifications`, cero statements
  ejecutados. Catálogo real: nueve VIEWs Project lógicas; `system_notifications` Identity,
  `varchar(100)`, 157/157 filas con metadata intacta.
- Gate cerrado: no apply/DDL/DML/backfill, freeze/backup/restore, usuarios/grants/revokes,
  credenciales activas, Compose up/recreate ni `--enforce`. Auditor read-only efectivo:
  `runtime_db_grants=fail reason=invalid-line grants_checked=1`; grants no impresos.
- Concerns actuales: tres hallazgos físicos propuestos, backup no clasificado, cuenta efectiva sin
  certificar, init de volumen no ejecutado y reconciliación read-only vacía (0 fuentes). Reporte:
  `task-7-report.md`; fix `39c2530e`.
- Re-review round 1: VIEW/type/Identity/preflight/admin-path/grant findings approved; CI DML-only
  remains Important because `tests/test_migrate_legacy_to_global.php` is still selected by
  `--nivel=http` while executing DROP/CREATE through the runtime account. The static check and
  runbook missed this fourth test.
- Ruling: genuine migration tests that require fixture DDL run in an explicit, non-cumulative
  `admin-db` lane against the isolated ephemeral CI database and a one-off admin credential; they
  are never selected by normal `db`/`http` runtime lanes. CI must still run that lane, and runner
  plus workflow contracts prove both inclusion and separation — the cost if wrong is an extra CI
  lane, preferred over granting DDL to the application account or silently dropping coverage.
- Ruling: runtime-DDL discovery is inventory-based rather than a three-file allowlist so a new
  selected test with executable CREATE/DROP cannot bypass least privilege — the cost if wrong is a
  conservative CI failure requiring explicit `admin-db` classification.
- Task 7: fix round 2/5 (0 addressed, 1 open — maintenance migration test in runtime lane), base
  `39c2530e`; data gate remains `CODE_BLOCKED`.
- Task 7: fix round 2/5 implemented in `5e540d74`: explicit non-cumulative `admin-db` lane,
  isolated CI admin step, migration-test guard and inventory contract; original CI finding closed.
- Re-review round 2: lane/workflow approved, but a new Important remains because the DDL inventory
  misses direct heredoc/nowdoc, variable aliases, equivalent local wrappers and CREATE VIEW.
- Ruling: DDL inventory uses PHP-aware token/AST analysis with conservative dataflow through local
  assignments, aliases and wrapper parameters to DB execution sinks; it covers heredoc/nowdoc and
  every schema/privilege-changing verb, while keeping inert expected-SQL assertions distinguishable.
  Any unresolved value reaching a DDL-capable test helper fails closed or requires explicit
  `admin-db` classification — the cost if wrong is conservatively moving a dynamic fixture test to
  the isolated admin lane rather than silently running it under runtime.
- Task 7: fix round 3/5 (0 addressed, 1 open — inventory bypass), base `5e540d74`; data gate remains
  `CODE_BLOCKED`.
- Task 7: fix round 3/5 implemented in `cd10497f`: PHP-aware inventory, 25/25 declared
  adversarials and 215 contract checks; the prior heredoc/alias/local-wrapper finding is addressed.
- Re-review round 3: three new Important false negatives remain in the fix diff: class methods are
  skipped as scopes; interpolated/incomplete SQL with a DML prefix is treated safe; and lexical
  last-assignment wins across mutually exclusive branches, hiding a possible DDL value. Repo path
  containment for literal `.sql` sources is approved.
- Ruling: class methods/closures participate in the same wrapper call graph as functions; any
  interpolated or otherwise unresolved SQL reaching an execution sink is unsafe regardless of a
  SELECT/DML prefix; branch joins use a may-analysis union where one DDL/unknown path dominates a
  safe path — the cost if wrong is conservative `admin-db` classification for dynamic test SQL,
  never a runtime-lane false negative.
- Re-review round 3 terminal added a fourth Important: the SQL splitter treats every `--` as a
  MySQL comment (hiding a later DDL statement in `SELECT 1--2; DROP ...`) and treats DELIMITER
  trigger sources as safe.
- Ruling: SQL splitting follows MySQL's `--` whitespace/control rule; `DELIMITER` sources are
  either parsed with their declared delimiter or rejected fail-closed, never classified safe by a
  partial standard-semicolon parse — the cost if wrong is moving a complex migration fixture to
  `admin-db` rather than missing its DDL.
- Task 7: fix round 4/5 (0 addressed, 4 open — class methods, dynamic SQL, branch joins, SQL
  splitting), base `cd10497f`; fresh implementer required by SDD escalation and data gate remains
  `CODE_BLOCKED`.
- Task 7: fix round 4/5 implemented in `4d9d3f93`: 41/41 declared adversarials, 153-file real
  inventory clean, bounded fixed points and no per-file allowlists. Direct same-class methods,
  dynamic-prefix SQL, complete control-flow statements and `--`/DELIMITER cases are addressed.
- Re-review round 4: four Important families remain: inheritance/call_user_func/DataProvider;
  named-argument and by-reference effects; safe-alternative collapse losing DDL fragments; and
  MySQL versioned comments hiding non-initial DDL. The helper's current abstractions do not model
  these load-bearing semantics despite 1,936 lines.
- Ruling: round 5 favors conservative classification over fuller PHP emulation. Methods/providers
  may be scanned as independent roots; unresolved indirect callables and by-reference effects are
  UNKNOWN; named arguments bind by declared name; joins preserve a set/taint or collapse ambiguity
  to UNKNOWN; every `/*!...*/` payload is inspected for all statements or rejected fail-closed —
  the cost if wrong is moving dynamic fixtures to `admin-db`, not another false-negative lattice.
- Task 7: fix round 5/5 (0 addressed, 4 open — indirect callables, binding/effects, alternative
  fragments, executable comments), base `4d9d3f93`; breaker applies after rereview and data gate
  remains `CODE_BLOCKED`.
- Task 7: fix round 5/5 implementada sobre `4d9d3f93`: herencia, call_user_func local y providers
  entran a la frontera conservadora; named args se enlazan por firma y efectos by-ref no
  demostrables degradan a unknown. `SqlValue` conserva alternativas acotadas y cualquier DDL
  domina; todos los statements de `/*!...*/` se inspeccionan.
- RED seguro round 5: inventario 9/52, runner 1/43, revisión independiente 3/55 y checklist final
  3/58. GREEN: adversariales 58/58, runner 43/43, inventario real RC 0 en 40.59 s, schema
  215 checks, visual 13/13, lint/PHPStan/listados verdes.
  Los falsos positivos de reconciliation, BI, design system y la regresión `exec()` global se
  cerraron por semántica general, sin allowlists ni reclasificación.
- Dry-run round 5 sin `--apply`: `tables_checked=56 null_rows=0 columns_changed=2
  indexes_added=1`, tres ALTER propuestos y cero ejecutados. Reconciliación RC 0 pero vacía;
  auditor efectivo `runtime_db_grants=fail reason=no-grants grants_checked=0`.
- Gate round 5: `CODE_BLOCKED_PENDING_REREVIEW`; rerevisión fresca y breaker pendientes. No se
  ejecutó admin-db real, apply/enforce, DDL/DML, grants/users/cambios de credenciales ni Compose
  up/recreate. Reporte actualizado: `task-7-report.md`; commit `65005169`.
- Task 7: fix round 4/5 implementada sobre `cd10497f`: métodos PHPUnit y closures entran al grafo
  de callables; SQL incompleto permanece unknown aunque empiece SELECT; ramas/loops se unen por
  may-analysis; `--` sigue la regla whitespace/control de MySQL y toda fuente `DELIMITER` se
  rechaza fail-closed. Punto fijo acotado y sin allowlists por archivo.
- RED seguro round 4: inventario 10/38 y runner 1/41; durante la convergencia, inventario real
  14→5→3→1 falsos positivos, transformer SQL-preserving 1/40 y coalesce dinámico 1/41. GREEN:
  adversariales 41/41, runner 41/41, inventario real 153/0, schema/grants 215 checks, visual 13/13,
  lint/PHPStan/listados/Compose render verdes.
- Dry-run round 4 sin `--apply`: `tables_checked=56 null_rows=0 columns_changed=2
  indexes_added=1`, tres ALTER propuestos y cero ejecutados. Auditor efectivo read-only:
  `runtime_db_grants=fail reason=invalid-line grants_checked=2`; reconciliación RC 0 pero vacía.
- Gate round 4: `CODE_BLOCKED_PENDING_REREVIEW`; no admin-db real, apply/enforce, DDL/DML,
  grants/users/credenciales ni Compose up/recreate. Reporte: `task-7-report.md`; commit
  `4d9d3f93`.
- Task 7: fix round 2 implementada sobre `39c2530e`: `admin-db` es explícita y no acumulativa;
  `db/http` excluyen el migrador y el listado admin selecciona solo ese test. La lane runtime
  conserva DML-only; CI inyecta admin one-off únicamente al proceso del step contra su DB efímera.
- TDD RED seguro: runner 9/33, workflow 12/13, inventario RC 1 y fixture DDL mal etiquetado 1/35.
  GREEN: runner 35/35, workflow 13/13, contrato schema/grants/inventario 214 checks, lint y PHPStan
  focal verdes; render Compose redactado confirma separación runtime/admin.
- Dry-run real sin `--apply`: `tables_checked=56 null_rows=0 columns_changed=2 indexes_added=1`,
  tres ALTER propuestos y cero ejecutados. No se ejecutó la lane `admin-db`, DDL/DML, apply,
  enforce, grants/users, credenciales ni Compose up/recreate.
- Gate: `CODE_BLOCKED_PENDING_REREVIEW`; queda pendiente rerevisión y, más adelante, el gate de
  cambio de datos. Reporte consolidado: `task-7-report.md`; commit `5e540d74`.
- Task 7: fix round 3 implementada sobre `5e540d74`: inventario AST con alias, arrays/foreach,
  heredoc/nowdoc y wrappers locales propagados hasta punto fijo; todo SQL desconocido que llega a
  sink falla cerrado. Cubre verbos schema/privilegios, múltiples statements y evita falsos
  positivos de SQL esperado inerte.
- RED round 3 seguro: adversariales 15/20, runner 1/39, inventario real con 3 falsos positivos,
  foreach 2/22 y segundo statement 1/25. GREEN: adversariales 25/25, runner 39/39, contrato real
  215 checks, visual 13/13, lint/PHPStan/listados/Compose render verdes.
- Dry-run sin `--apply` se mantiene `tables_checked=56 null_rows=0 columns_changed=2
  indexes_added=1`, tres ALTER propuestos y cero ejecutados. No se ejecutó admin-db, DDL/DML,
  apply/enforce, grants/users/credenciales ni Compose up/recreate.
- Gate: `CODE_BLOCKED_PENDING_REREVIEW`; reporte `task-7-report.md`; commit round 3 `cd10497f`.
- Re-review round 5/5: `NEEDS FIXES`. Findings load-bearing reproducidos: padre externo sin args
  unsafe; aliases/parámetros/arrays de first-class y `Closure::fromCallable`; colisión FQCN de
  `DataProviderExternal`; variadic que forma DDL; branch→array→foreach y `safe_fragment` que
  pierden una ruta DDL. También persisten falsos positivos por correlación perdida y por
  `/*!...*/` inerte dentro de strings/comentarios.
- Breaker R5/5 adjudicado: los falsos negativos son load-bearing para el inventario que clasifica
  runtime/admin. Task 7 queda `CODE_BLOCKED`; no hay ronda 6, no se avanza al data gate y no se
  ejecutan apply/enforce/admin-db/grants/DDL/DML. Reabrir requiere cambiar la arquitectura del
  control, no otra ampliación incremental del emulador PHP/SQL.
- Task 5 de la [replanificación RLS Runtime Boundary](../../../docs/superpowers/plans/2026-08-29-rls-runtime-boundary.md):
  se conserva literalmente el estado histórico `CODE_BLOCKED` del Task 7 y se documenta la frontera
  runtime DML-only con `admin-db` aislada. El scanner permanece advisory e incompleto para callables
  dinámicos, providers externos y joins de flujo; esta adenda no reescribe evidencia ni autoriza
  `--apply`, DDL/DML, grants, usuarios o `--enforce`.
