---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-29
areas: [datos, rbac]
fuente: docs/superpowers/plans/2026-08-29-rls-runtime-boundary.md
resumen: "hacer que la cuenta MySQL runtime DML-only sea la frontera autoritativa de seguridad y que la lane admin-db sea explícita, aislada y verificable, sin depender…"
---

# RLS Runtime Boundary Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** hacer que la cuenta MySQL runtime DML-only sea la frontera autoritativa de seguridad y que la lane `admin-db` sea explícita, aislada y verificable, sin depender de emular todo PHP/SQL de los tests.

**Architecture:** el runner selecciona pruebas por declaración de lane (`@requiere: admin-db` para scripts y `#[Group('admin-db')]` para PHPUnit); no usa inferencia estática de DDL para conceder o negar autoridad. La aplicación y la suite runtime se conectan únicamente con el usuario DML-only, cuya concesión efectiva se atestigua con `SHOW GRANTS` sin imprimir secretos. Las pruebas administrativas se ejecutan sólo en una base efímera con credenciales one-off; el scanner PHP existente permanece como diagnóstico advisory y nunca como control de seguridad.

**Tech Stack:** PHP 8.3, MySQL 8.0.40, PDO, PHPUnit 12, Docker Compose, GitHub Actions, Node.js 22 para contratos del workflow.

**Spec:** `docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design.md`

## Global Constraints

- El usuario runtime sólo puede tener `SELECT, INSERT, UPDATE, DELETE` sobre `{DB_NAME}.*`; nunca `CREATE`, `ALTER`, `DROP`, `GRANT OPTION`, privilegios globales ni usuario `root`.
- La cuenta runtime es la frontera efectiva: una prueba mal clasificada puede fallar por privilegios, pero nunca puede modificar schema.
- `admin-db` es una lane no acumulativa, con DB efímera y credencial administrativa sólo en el proceso del step CI.
- El manifiesto de lane es declarativo y visible; no existen allowlists ocultas por archivo ni excepciones semánticas en el scanner.
- `scripts/lib/php-test-ddl-inventory.php` no puede decidir autoridad ni convertir una ejecución runtime en admin; sólo puede producir diagnóstico.
- No se escriben secretos, contraseñas, grants efectivos ni salidas `SHOW GRANTS` en el repositorio o en artefactos.
- Todo PHP y toda prueba con base corre dentro de Docker; antes de cada comando se verifica el mount del worktree.
- Migraciones: dry-run primero; `--apply` sólo con freeze, backup restaurable, restore probado y autorización explícita separada.
- En esta replanificación no se ejecutan `admin-db` real, `--apply`, `--enforce`, DDL/DML local, grants/revokes, usuarios, credenciales ni `compose up/recreate`.

## File Structure

- Create: `scripts/lib/php-test-lane-manifest.php` — contrato puro para declarar y seleccionar lanes.
- Create: `tests/test_php_test_lane_manifest.php` — pruebas del manifiesto, sin DB.
- Create: `docs/security/rls-runtime-boundary.md` — decisión arquitectónica, amenaza mitigada y límites explícitos.
- Modify: `scripts/run-php-tests.php` — usa el manifiesto y elimina el scanner como gate de autoridad.
- Modify: `scripts/security/audit-runtime-db-grants.php` — modo `--live` read-only que resume la cuenta efectiva sin imprimir grants.
- Modify: `tests/test_php_test_runner.php` — contratos de selección no acumulativa y de ejecución bajo runtime.
- Modify: `tests/test_project_scope_schema_contract.php` — conserva probes del scanner como diagnóstico y verifica el contrato declarativo, no un inventario inferido como autorización.
- Modify: `.github/workflows/ci.yml` — atestación de grants runtime y lane admin separada.
- Modify: `tests/design-system/visual-ci-contract.test.mjs` — prueba que runtime y admin no compartan credenciales.
- Preserve: `scripts/lib/php-test-ddl-inventory.php` y `tests/test_php_test_ddl_inventory.php` como diagnóstico advisory; su matriz no se elimina.

---

### Task 1: Crear el manifiesto declarativo de lanes

**Files:**
- Create: `scripts/lib/php-test-lane-manifest.php`
- Create: `tests/test_php_test_lane_manifest.php`
- Create: `docs/security/rls-runtime-boundary.md`

**Interfaces:**
- `PhpTestLaneManifest::levels(): array<string,int>` devuelve `puro`, `db`, `http`, `datos-proyecto`, `admin-db`.
- `PhpTestLaneManifest::select(string $requested, string $declared): bool` aplica acumulación sólo a lanes runtime y coincidencia exacta para `admin-db`.
- `PhpTestLaneManifest::validateDeclaredLevels(array $levels): list<string>` devuelve rutas con nivel desconocido o ausente; nunca infiere un nivel desde SQL.

- [ ] **Step 1: Write the failing pure test**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../scripts/lib/php-test-lane-manifest.php';

$checks = 0;
$failures = [];
$same = static function (mixed $expected, mixed $actual, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($expected !== $actual) {
        $failures[] = $message;
    }
};

$same(true, PhpTestLaneManifest::select('db', 'puro'), 'db acumula puro');
$same(true, PhpTestLaneManifest::select('http', 'db'), 'http acumula db');
$same(false, PhpTestLaneManifest::select('db', 'admin-db'), 'db nunca acumula admin-db');
$same(true, PhpTestLaneManifest::select('admin-db', 'admin-db'), 'admin-db selecciona su propia lane');
$same(false, PhpTestLaneManifest::select('admin-db', 'puro'), 'admin-db no acumula puro');
$same([], PhpTestLaneManifest::validateDeclaredLevels([
    '/tmp/runtime.php' => 'db',
    '/tmp/admin.php' => 'admin-db',
]), 'niveles declarados válidos');
$same(['/tmp/missing.php', '/tmp/unknown.php'], PhpTestLaneManifest::validateDeclaredLevels([
    '/tmp/missing.php' => '',
    '/tmp/unknown.php' => 'inventado',
]), 'niveles ausentes/desconocidos fallan cerrado');

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "Lane manifest: {$checks} checks\n";
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T app php tests/test_php_test_lane_manifest.php`

Expected: FAIL because `scripts/lib/php-test-lane-manifest.php` does not exist.

- [ ] **Step 3: Write the minimal implementation**

Crear `PhpTestLaneManifest` con `levels()`, `select()` y `validateDeclaredLevels()` exactamente
con las firmas anteriores. `select()` devuelve `requested === declared` si cualquiera de los dos
niveles es `admin-db`; para el resto compara los enteros de `levels()`. `validateDeclaredLevels()`
ordena y devuelve cada ruta cuyo valor no esté en `levels()`.

Documentar en `docs/security/rls-runtime-boundary.md` que la declaración es el contrato de la
prueba, el privilegio DML-only es la frontera y el scanner es advisory.

- [ ] **Step 4: Run the pure test to verify it passes**

Run: `docker compose exec -T app php tests/test_php_test_lane_manifest.php`

Expected: `Lane manifest: 7 checks` y RC 0.

- [ ] **Step 5: Commit**

```bash
git add scripts/lib/php-test-lane-manifest.php tests/test_php_test_lane_manifest.php docs/security/rls-runtime-boundary.md
git commit -m "feat(security): declarar lanes runtime y admin"
```

---

### Task 2: Atestar grants efectivos sin exponer secretos

**Files:**
- Modify: `scripts/security/audit-runtime-db-grants.php`
- Modify: `tests/test_project_scope_schema_contract.php`

**Interfaces:**
- `runtimeGrantAuditParseArguments()` acepta `--live` además de `--grants-file`.
- `runtimeGrantAuditLive(PDO $pdo): array{ok: bool, grants_checked: int, reason: string}` ejecuta únicamente `SHOW GRANTS FOR CURRENT_USER` y pasa el texto directamente al parser.
- El proceso imprime sólo `runtime_db_grants=ok|fail reason=<token> grants_checked=<n>`; nunca imprime una línea `GRANT`.

- [ ] **Step 1: Write the failing pure tests**

Añadir a `tests/test_project_scope_schema_contract.php` casos de argumentos y un doble PDO que
devuelva dos filas de grant. Comprobar `ok=true` para DML exacto más `USAGE`, `ok=false` para
`root`, DDL o target global, y que la salida del subproceso no contiene `GRANT `. Añadir `--live`
sin conexión: debe devolver RC 1 con `reason=unavailable`, nunca RC 0.

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit`

Expected: FAIL porque `--live` y `runtimeGrantAuditLive()` no existen.

- [ ] **Step 3: Implement the read-only live path**

Crear `runtimeGrantAuditLive(PDO $pdo)` con `query('SHOW GRANTS FOR CURRENT_USER')`, recolectar
las columnas sin imprimirlas y reutilizar `runtimeGrantAudit(implode("\n", $lines), DB_NAME)`.
Capturar `Throwable` y devolver `reason=unavailable`; prohibir `--live` junto con `--grants-file`.

- [ ] **Step 4: Run the contract and static checks**

```bash
docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit
docker compose exec -T app php -l scripts/security/audit-runtime-db-grants.php
```

Expected: contrato verde y lint limpio. No se conecta con root ni imprime credenciales en local.

- [ ] **Step 5: Commit**

```bash
git add scripts/security/audit-runtime-db-grants.php tests/test_project_scope_schema_contract.php
git commit -m "feat(security): atestar grants runtime efectivos"
```

---

### Task 3: Quitar el scanner de la autoridad del runner

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`
- Modify: `tests/test_project_scope_schema_contract.php`
- Preserve: `scripts/lib/php-test-ddl-inventory.php`, `tests/test_php_test_ddl_inventory.php`

**Interfaces:**
- `scripts/run-php-tests.php` carga `PhpTestLaneManifest` y sólo selecciona por declaraciones.
- `phpTestDdlLevelViolations()` deja de ejecutarse desde el runner y desde el contrato de schema;
  se conserva para diagnóstico advisory.
- Un script DDL mal etiquetado no obtiene permisos: se selecciona en runtime y su ejecución real
  debe fallar bajo el usuario DML-only; `--solo-listar` sólo informa la declaración.

- [ ] **Step 1: Write the failing boundary test**

Añadir a `tests/test_php_test_runner.php` un fixture runtime con `@requiere: db` que contenga
`DROP TABLE` en un helper. Comprobar que `--solo-listar` devuelve RC 0, lo lista como `[ejecuta]`
y no aborta por un inventario inferido. Mantener un fixture `@requiere: admin-db` que sólo aparece
en `admin-db`.

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T app php tests/test_php_test_runner.php`

Expected: FAIL porque el runner actual aborta antes de listar por `phpTestDdlLevelViolations()`.

- [ ] **Step 3: Refactor runner to the manifest**

Reemplazar `NIVELES`/`nivelSeleccionado()` por `PhpTestLaneManifest::levels()` y
`PhpTestLaneManifest::select()`. Eliminar sólo el bloque que llama al scanner y aborta por DDL;
conservar validación de etiquetas/grupos, códigos RC 1/2 y la comprobación de entorno.

- [ ] **Step 4: Run focused runner and advisory tests**

```bash
docker compose exec -T app php tests/test_php_test_runner.php
docker compose exec -T app php tests/test_php_test_ddl_inventory.php
docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit
```

Expected: runner y scanner advisory verdes; ningún fixture DDL se ejecuta durante estos comandos.

- [ ] **Step 5: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/test_project_scope_schema_contract.php
git commit -m "refactor(security): separar runner de inferencia DDL"
```

---

### Task 4: Aislar y atestar las lanes en CI

**Files:**
- Modify: `.github/workflows/ci.yml`
- Modify: `docker-compose.ci.yml` sólo si el contrato exige una variable explícita
- Modify: `tests/design-system/visual-ci-contract.test.mjs` — contrato estructural del workflow.
- Create: `tests/test_runtime_boundary_ci_contract.php` — contrato puro adicional de variables y lanes.

**Interfaces:**
- El job runtime ejecuta `php scripts/security/audit-runtime-db-grants.php --live` con la cuenta
  DML-only antes de la suite HTTP.
- El step admin sigue siendo el único que exporta `DB_USER=root`, `DB_PASS` administrativo y
  `LPS_ADMIN_DB_LANE=1`; esas variables no existen en el servicio `app` ni en el step runtime.
- Un fallo de la atestación runtime hace fallar el resumen del job, igual que `php-suite` y
  `php-admin-db`; no se usa `continue-on-error` sin agregación final.

- [ ] **Step 1: Write the failing workflow contract**

Añadir aserciones que exijan un step runtime con `--live`, que el comando no incluya `root` ni
`CI_DB_ADMIN_PASS`, y que las variables administrativas sólo estén bajo el step `php-admin-db`.

- [ ] **Step 2: Run it to verify it fails**

Run: `node tests/design-system/visual-ci-contract.test.mjs`

Expected: FAIL porque el workflow todavía no atestigua grants efectivos.

- [ ] **Step 3: Add the isolated runtime attestation**

Insertar después de levantar la DB CI y antes de `php-suite`:

```yaml
- name: Atestar grants efectivos de runtime
  id: runtime-grants
  run: docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml exec -T app php scripts/security/audit-runtime-db-grants.php --live
```

Agregar `G_RUNTIME_GRANTS` al resumen final y al acumulador `failed`. No pasar `DB_USER` ni
`DB_PASS` administrativos a este comando; hereda sólo el entorno runtime del servicio.

- [ ] **Step 4: Run the workflow contract and render checks**

Run: `node tests/design-system/visual-ci-contract.test.mjs` y `docker compose exec -T app php tests/test_runtime_boundary_ci_contract.php`.

Expected: todos verdes, con la separación runtime/admin demostrada por texto y estructura.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci.yml docker-compose.ci.yml tests/design-system/visual-ci-contract.test.mjs tests/test_runtime_boundary_ci_contract.php
git commit -m "ci(security): atestar y separar lanes de base de datos"
```

---

### Task 5: Documentar límites, diagnóstico y amenaza residual

**Files:**
- Modify: `docs/security/rls-runtime-boundary.md`
- Modify: `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/task-7-report.md`
- Modify: `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md`

**Interfaces:**
- El documento explica qué garantiza la cuenta DML-only, qué garantiza la declaración de lane y
  qué NO garantiza el scanner advisory (callables dinámicos, providers externos, joins de flujo).
- El reporte conserva el breaker R5 histórico y enlaza este plan como replanificación aprobada; no
  se reescribe evidencia ni se presenta el scanner como perfecto.

- [ ] **Step 1: Write the failing documentation contract**

Añadir un contrato que falle si el documento no contiene literalmente `DML-only`, `admin-db`,
`advisory`, `SHOW GRANTS`, `--apply`, `backup` y `restore`, y si el reporte no conserva
`CODE_BLOCKED` para el plan anterior.

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit`

Expected: FAIL hasta que el documento y el reporte declaren la nueva frontera y el estado previo.

- [ ] **Step 3: Write the decision record**

Documentar el flujo: `runtime request → DML-only user → MySQL denies DDL`; `admin-db → DB efímera →
credencial one-off`. Incluir ejemplos de fallo esperado, el hecho de que el scanner puede ser
incompleto y la razón por la que no decide autoridad.

- [ ] **Step 4: Run documentation and diff checks**

Run: `docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit` y
`git diff --check`. Expected: RC 0 y sin whitespace errors.

- [ ] **Step 5: Commit**

```bash
git add docs/security/rls-runtime-boundary.md .superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/task-7-report.md .superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md
git commit -m "docs(security): fijar frontera runtime DML-only"
```

---

### Task 6: Revalidar dry-run y cerrar el gate de código

**Files:**
- Modify: `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/task-7-report.md`
- Modify: `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md`

**Interfaces:**
- El dry-run sigue produciendo los tres `ALTER` conocidos y `No statements executed`.
- La auditoría `--audit` comprueba schema/catalog/grants contractuales sin aplicar cambios.
- El gate final sólo puede ser `CODE_READY_FOR_DATA_GATE` si CI/contratos muestran runtime DML-only,
  admin aislado y no hay una acción de datos autorizada pendiente; de lo contrario permanece
  `CODE_BLOCKED` con la razón exacta.

- [ ] **Step 1: Run the focal read-only matrix**

```bash
docker compose exec -T app php tests/test_php_test_lane_manifest.php
docker compose exec -T app php tests/test_php_test_runner.php
docker compose exec -T app php tests/test_php_test_ddl_inventory.php
docker compose exec -T app php tests/test_project_scope_schema_contract.php --audit
docker compose exec -T app php database/migrations/20260828_project_scope_contract.php
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon --memory-limit=1G --no-progress
node tests/design-system/visual-ci-contract.test.mjs
git diff --check
```

Expected: contratos focales verdes; migración sólo dry-run con 56 tablas, 0 NULL, 2 columnas,
1 índice y tres `ALTER` propuestos; cero statements ejecutados.

- [ ] **Step 2: Verify no forbidden operation occurred**

Revisar salida y `git status --short`; no debe haber `--apply`, `--enforce`, grants/revokes,
usuarios, DDL/DML de fixtures, `compose up/recreate` ni credenciales impresas.

- [ ] **Step 3: Record evidence and state**

Actualizar el reporte con comandos, RC, tiempos, mount del contenedor, auditoría live sólo si se
ejecutó en CI, y los pendientes externos: backup no clasificado, reconciliación vacía, tres ALTER
sin aplicar y autorización de datos.

- [ ] **Step 4: Commit evidence**

```bash
git add .superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/task-7-report.md .superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/progress.md
git commit -m "test(security): cerrar gate de código runtime"
```

- [ ] **Step 5: Fresh review**

Generar un paquete sobre el rango completo del plan y despachar un revisor fresco. Debe confirmar
que la seguridad no depende del scanner, que el usuario runtime efectivo es DML-only y que la lane
admin no es acumulativa. Si falla, mantener `CODE_BLOCKED`; no aplicar schema.
