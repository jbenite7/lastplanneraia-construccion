# RLS fail-closed en la aplicación Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** impedir que cualquier lectura o mutación operativa se ejecute sin un alcance de proyecto autorizado, incluidos SQL global, compatibilidad por prefijo y consultas BI multiproyecto.

**Architecture:** PHP resuelve un alcance inmutable desde sesión + membresía y lo enlaza al inicio de cada request. `Database` clasifica las tablas desde el schema, rechaza consultas operativas sin alcance y aplica/valida `project_id`; BI usa una API multiproyecto explícita. MySQL sigue sin RLS nativo, por lo que este gate de aplicación se acompaña con schema contractual, pruebas A→B y una cuenta de runtime de mínimos privilegios.

**Tech Stack:** PHP 8.3, MySQL 8.0.40, PDO sin prepares emulados, PHPUnit 12, runner PHP propio y Docker Compose.

**Spec:** `docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design.md`

## Global Constraints

- Toda consulta operativa se aísla por `project_id`; RBAC sigue gobernando la acción.
- El proyecto llega de sesión + membresía activa, nunca de un parámetro del navegador.
- La ausencia o contradicción de alcance falla antes de preparar SQL; no existe fallback con warning.
- `project_members`, `general_usuarios` y `general_proyectos_procesos` son identidad/membresía y se consultan sin imponer el proyecto activo.
- El alcance multiproyecto solo se crea desde IDs autorizados por `BiProjectScope`.
- Los prefijos `{Base_de_Datos}_*` solo son compatibilidad: deben resolver al alcance autorizado.
- No se escriben secretos, contraseñas ni grants efectivos en commits o salidas de prueba.
- Las migraciones son dry-run primero; `--apply` requiere respaldo restaurable y autorización explícita.
- Todo PHP y toda prueba con base corren dentro de Docker; verifica primero el mount del contenedor.
- No se modifica la lógica de negocio de módulos ni se migra interfaz en este plan.

## File Structure

**Nuevas unidades de seguridad:**

- `src/Security/DataScope/TableScopeKind.php` — enum de clasificación.
- `src/Security/DataScope/TableScopeDefinitions.php` — listas explícitas de identidad y sistema.
- `src/Security/DataScope/TableScopeCatalog.php` — catálogo construido desde `information_schema`.
- `src/Security/DataScope/ProjectScope.php` — alcance de un proyecto.
- `src/Security/DataScope/MultiProjectScope.php` — conjunto explícito y autorizado.
- `src/Security/DataScope/SystemScope.php` — bypass explícito para mantenimiento, nunca por ausencia.
- `src/Security/DataScope/DataScopeContext.php` — bind/clear por request o comando.
- `src/Security/DataScope/ProjectScopeResolver.php` — valida sesión, usuario, membresía y proyecto.
- `src/Security/DataScope/ProjectSqlGuard.php` — preflight de SQL, prefijos, INSERT y joins.
- `src/Security/DataScope/MissingProjectScope.php` y `ProjectScopeViolation.php` — fallos tipados.

**Integración:**

- `src/Core/Database.php` — único punto de ejecución, fail-closed.
- `src/Core/SessionMiddleware.php` y `public/index.php` — lifecycle de alcance.
- `src/Support/BiProjectScope.php` y servicios BI — multiproyecto explícito.
- `src/Services/Auth/AuthenticationService.php`, `src/Services/ProjectAccessService.php` y consumidores de identidad — dejan de usar la API operativa.

**Gates:**

- `tests/unit/TableScopeCatalogTest.php`
- `tests/unit/DataScopeContextTest.php`
- `tests/unit/ProjectSqlGuardTest.php`
- `tests/test_project_scope_schema_contract.php`
- `tests/test_project_scope_database.php`
- `tests/test_project_scope_callsite_audit.php`
- `tests/test_project_scope_http_isolation.php`
- `database/migrations/20260828_project_scope_contract.php`
- `scripts/security/audit-runtime-db-grants.php`

---

### Task 1: Catálogo de tablas derivado del schema

**Files:**
- Create: `src/Security/DataScope/TableScopeKind.php`
- Create: `src/Security/DataScope/TableScopeDefinitions.php`
- Create: `src/Security/DataScope/TableScopeCatalog.php`
- Create: `docs/security/data-scope-table-catalog.md`
- Create: `tests/unit/TableScopeCatalogTest.php`
- Create: `tests/test_project_scope_schema_contract.php`
- Modify: `src/Core/Database.php` (exponer el catálogo, sin bloquear todavía)

**Interfaces:**
- Produces: `TableScopeCatalog::fromPdo(PDO): self`
- Produces: `TableScopeCatalog::kind(string): TableScopeKind`
- Produces: `TableScopeCatalog::projectScopedTables(): array`
- Produces: `TableScopeCatalog::unclassifiedTables(): array`
- Consumes later: `ProjectSqlGuard` y `Database` consultan esta instancia; no mantienen otra lista.

- [ ] **Step 1: Write the failing pure tests**

Crear `tests/unit/TableScopeCatalogTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\TableScopeCatalog;
use App\Security\DataScope\TableScopeKind;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class TableScopeCatalogTest extends TestCase
{
    public function testClasificaPorContratoSinConfundirMembresia(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            ['TABLE_NAME' => 'programa', 'has_project_id' => 1, 'project_id_nullable' => 0, 'has_leading_index' => 1],
            ['TABLE_NAME' => 'project_members', 'has_project_id' => 1, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'general_usuarios', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'general_flags', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'backup_fuera_de_runtime', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
        ]);

        self::assertSame(TableScopeKind::Project, $catalog->kind('programa'));
        self::assertSame(TableScopeKind::Identity, $catalog->kind('project_members'));
        self::assertSame(TableScopeKind::Identity, $catalog->kind('general_usuarios'));
        self::assertSame(TableScopeKind::System, $catalog->kind('general_flags'));
        self::assertSame(TableScopeKind::Unclassified, $catalog->kind('backup_fuera_de_runtime'));
        self::assertSame(['programa'], $catalog->projectScopedTables());
        self::assertSame(['backup_fuera_de_runtime'], $catalog->unclassifiedTables());
    }

    public function testUnaTablaAjenaAlSchemaNoSeVuelveGlobalPorDefecto(): void
    {
        $catalog = TableScopeCatalog::fromRows([]);

        $this->expectException(\DomainException::class);
        $catalog->kind('tabla_inventada');
    }
}
```

- [ ] **Step 2: Run the pure tests and see red**

Run:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/TableScopeCatalogTest.php
```

Expected: FAIL porque el namespace `App\Security\DataScope` todavía no existe.

- [ ] **Step 3: Add the enum and catalog**

Crear `TableScopeKind.php`:

```php
<?php

declare(strict_types=1);

namespace App\Security\DataScope;

enum TableScopeKind: string
{
    case Project = 'project';
    case Identity = 'identity';
    case System = 'system';
    case Unclassified = 'unclassified';
}
```

Crear `TableScopeDefinitions.php`. `IDENTITY` contiene exactamente
`general_proyectos_procesos`, `general_usuarios`, `password_history`, `password_reset_tokens`,
`project_members`, `rbac_permissions`, `rbac_role_permissions` y `rbac_roles`.

`SYSTEM` contiene exactamente el baseline global compartido observado el 2026-08-28:
`bi_lineage`, `event_dictionary`, `general_auditoria_acciones`, `general_cnc`,
`general_codigos_actividades`, `general_costos_cuadrillas`, `general_cuadrillas_tipicas`,
`general_curvas_pdc_apr`, `general_dias_defaults_categoria`,
`general_dias_procesos_contratacion`, `general_feature_flags`, `general_flags`,
`general_maestro_insumos`, `general_matching_config`, `general_paquetes_contratacion`,
`general_pasos_contratacion`, `general_pdc_activity_rules`,
`general_pdc_chapter_category_map`, `general_pdc_contractual_elements`, `general_pdc_familias`,
`general_pdc_family_aliases`, `general_pdc_family_contract_option_items`,
`general_pdc_family_contract_options`, `general_pdc_family_rule_audit`,
`general_pdc_paquete_aliases`, `general_rama_frente`, `notification_types`,
`role_intelligence`, `role_notification_defaults` y `v_pdc_inventory`.

No añadir `backup_*`, tablas con `project_id` ni nombres descubiertos solo para conseguir verde.
Cada alta al listado exige justificar dueño y semántica en `docs/security/data-scope-table-catalog.md`.

Crear `TableScopeCatalog.php` con estas categorías exactas. Una tabla del schema que tenga
`project_id` es `Project`, salvo las excepciones `IDENTITY`; una tabla sin `project_id` solo es
`System` o `Identity` si aparece en las definiciones explícitas. El resto es `Unclassified` y el
guard lo rechaza:

```php
<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use DomainException;
use PDO;

final class TableScopeCatalog
{
    /** @param array<string, TableScopeKind> $kinds */
    private function __construct(
        private readonly array $kinds,
        private readonly array $schemaRows,
    ) {}

    public static function fromPdo(PDO $pdo): self
    {
        $rows = $pdo->query(
            "SELECT c.TABLE_NAME,
                    MAX(c.COLUMN_NAME = 'project_id') AS has_project_id,
                    MAX(c.COLUMN_NAME = 'project_id' AND c.IS_NULLABLE = 'YES') AS project_id_nullable,
                    EXISTS (
                        SELECT 1 FROM information_schema.STATISTICS s
                        WHERE s.TABLE_SCHEMA = c.TABLE_SCHEMA
                          AND s.TABLE_NAME = c.TABLE_NAME
                          AND s.SEQ_IN_INDEX = 1
                          AND s.COLUMN_NAME = 'project_id'
                    ) AS has_leading_index
             FROM information_schema.COLUMNS c
             WHERE c.TABLE_SCHEMA = DATABASE()
             GROUP BY c.TABLE_NAME
             ORDER BY c.TABLE_NAME"
        )->fetchAll(PDO::FETCH_ASSOC);

        return self::fromRows($rows ?: []);
    }

    /** @param list<array<string, mixed>> $rows */
    public static function fromRows(array $rows): self
    {
        $kinds = [];
        $normalized = [];
        foreach ($rows as $row) {
            $table = strtolower((string) ($row['TABLE_NAME'] ?? ''));
            if ($table === '') {
                continue;
            }
            $normalized[$table] = $row;
            $kinds[$table] = in_array($table, TableScopeDefinitions::IDENTITY, true)
                ? TableScopeKind::Identity
                : ((int) ($row['has_project_id'] ?? 0) === 1
                    ? TableScopeKind::Project
                    : (in_array($table, TableScopeDefinitions::SYSTEM, true)
                        ? TableScopeKind::System
                        : TableScopeKind::Unclassified));
        }

        return new self($kinds, $normalized);
    }

    public function kind(string $table): TableScopeKind
    {
        $key = strtolower(trim($table, " `\t\n\r\0\x0B"));
        return $this->kinds[$key]
            ?? throw new DomainException("Tabla no clasificada en el schema: {$key}");
    }

    /** @return list<string> */
    public function projectScopedTables(): array
    {
        return array_keys(array_filter(
            $this->kinds,
            static fn(TableScopeKind $kind): bool => $kind === TableScopeKind::Project,
        ));
    }

    /** @return list<string> */
    public function unclassifiedTables(): array
    {
        return array_keys(array_filter(
            $this->kinds,
            static fn(TableScopeKind $kind): bool => $kind === TableScopeKind::Unclassified,
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function schemaRows(): array
    {
        return $this->schemaRows;
    }
}
```

- [ ] **Step 4: Expose one cached catalog from Database**

Modificar `Database.php`:

```php
private ?\App\Security\DataScope\TableScopeCatalog $tableScopeCatalog = null;

public function tableScopeCatalog(): \App\Security\DataScope\TableScopeCatalog
{
    return $this->tableScopeCatalog ??= \App\Security\DataScope\TableScopeCatalog::fromPdo($this->pdo);
}
```

Mantener `globalTableNames()` durante la transición; Task 8 lo hará delegar en el catálogo.

- [ ] **Step 5: Add the live schema contract test**

Crear `tests/test_project_scope_schema_contract.php` (`// @requiere: db`) que itere
`Database::getInstance()->tableScopeCatalog()->schemaRows()` y detecte cuando una tabla `Project`
tenga `project_id_nullable=1` o `has_leading_index=0`. También debe imprimir toda tabla
`Unclassified`; esa categoría es segura porque el runtime la rechaza, pero cada nombre debe quedar
registrado como `denied` con razón en `docs/security/data-scope-table-catalog.md`. En modo
`--audit` imprime hallazgos y retorna 0; en modo `--enforce` retorna 1. Task 1 usa audit para no
dejar un commit deliberadamente rojo; Task 7 corrige el schema y activa enforce.

- [ ] **Step 6: Run focused tests**

Run, leyendo ambos RC por separado:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/TableScopeCatalogTest.php
```

Expected: PASS.

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php tests/test_project_scope_schema_contract.php --audit
```

Expected: RC 0 y una lista concreta de tablas que Task 7 deberá converger.

- [ ] **Step 7: Commit**

```bash
git add src/Security/DataScope/TableScopeKind.php \
  src/Security/DataScope/TableScopeDefinitions.php \
  src/Security/DataScope/TableScopeCatalog.php \
  src/Core/Database.php \
  docs/security/data-scope-table-catalog.md \
  tests/unit/TableScopeCatalogTest.php \
  tests/test_project_scope_schema_contract.php
git commit -m "feat(security): inventariar tablas por alcance"
```

---

### Task 2: Alcances inmutables y lifecycle explícito

**Files:**
- Create: `src/Security/DataScope/ProjectScope.php`
- Create: `src/Security/DataScope/MultiProjectScope.php`
- Create: `src/Security/DataScope/SystemScope.php`
- Create: `src/Security/DataScope/DataScopeContext.php`
- Create: `src/Security/DataScope/MissingProjectScope.php`
- Create: `src/Security/DataScope/ProjectScopeViolation.php`
- Create: `tests/unit/DataScopeContextTest.php`

**Interfaces:**
- Produces: `ProjectScope::projectId(): int`, `user(): string`, `role(): string`
- Produces: `MultiProjectScope::projectIds(): array`, `allows(int): bool`, `reason(): string`
- Produces: `DataScopeContext::bind(ProjectScope|MultiProjectScope|SystemScope): void`
- Produces: `DataScopeContext::current()` y `clear()`

- [ ] **Step 1: Write tests for invalid IDs, deduplication and cleanup**

Crear `tests/unit/DataScopeContextTest.php` con grupo `puro` y estos casos:

```php
public function testProyectoUnicoRechazaIdNoPositivo(): void
{
    $this->expectException(InvalidArgumentException::class);
    new ProjectScope(0, 'test.A', 'A');
}

public function testMultiproyectoNormalizaYNoAceptaConjuntoVacio(): void
{
    $scope = new MultiProjectScope([73, 27, 73], 'test.A', 'A', 'bi:control-tower');
    self::assertSame([27, 73], $scope->projectIds());
    self::assertTrue($scope->allows(73));
}

public function testContextoSeLimpiaEntreRequests(): void
{
    $context = new DataScopeContext();
    $context->bind(new ProjectScope(73, 'test.A', 'A'));
    self::assertInstanceOf(ProjectScope::class, $context->current());
    $context->clear();
    self::assertNull($context->current());
}
```

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/DataScopeContextTest.php
```

Expected: FAIL por clases ausentes.

- [ ] **Step 3: Implement the value objects**

Usar clases `final readonly`; normalizar usuario/rol con `trim`, ordenar IDs multiproyecto y lanzar
`InvalidArgumentException` ante usuario, razón o conjunto vacíos. `SystemScope` solo se construye con:

```php
public static function forMaintenance(string $reason): self
{
    $reason = trim($reason);
    if ($reason === '') {
        throw new InvalidArgumentException('SystemScope exige una razón auditable.');
    }
    return new self($reason);
}
```

`DataScopeContext` no debe aceptar un segundo bind sin `clear()`:

```php
public function bind(ProjectScope|MultiProjectScope|SystemScope $scope): void
{
    if ($this->scope !== null) {
        throw new LogicException('El alcance ya estaba enlazado; limpia antes de reutilizar el proceso.');
    }
    $this->scope = $scope;
}
```

Añadir `MissingProjectScope extends RuntimeException` y
`ProjectScopeViolation extends RuntimeException`.

- [ ] **Step 4: Add one process-global context to Database**

En `Database`:

```php
private \App\Security\DataScope\DataScopeContext $dataScopeContext;

public function dataScope(): \App\Security\DataScope\DataScopeContext
{
    return $this->dataScopeContext;
}
```

Inicializar `$this->dataScopeContext = new DataScopeContext();` al final del constructor existente,
inmediatamente después de establecer la conexión PDO y la zona horaria. No crear contextos nuevos
en `query()`, `prepare()` ni en sus wrappers: toda la instancia de `Database` comparte exactamente
el mismo objeto hasta que el lifecycle explícito lo limpie.

`setProjectContext()` queda como adaptador temporal: crea `ProjectScope` únicamente cuando existe
usuario/rol en sesión y delega en el contexto; Task 5 elimina sus usos productivos directos.

- [ ] **Step 5: Run focused tests and DatabaseWrapperTest**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/DataScopeContextTest.php
```

Expected: PASS.

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/DatabaseWrapperTest.php
```

Expected: PASS; si el adaptador temporal cambia una expectativa, actualizar el test para comprobar
`dataScope()->current()` y no una propiedad privada obsoleta.

- [ ] **Step 6: Commit**

```bash
git add src/Security/DataScope/ProjectScope.php \
  src/Security/DataScope/MultiProjectScope.php \
  src/Security/DataScope/SystemScope.php \
  src/Security/DataScope/DataScopeContext.php \
  src/Security/DataScope/MissingProjectScope.php \
  src/Security/DataScope/ProjectScopeViolation.php \
  src/Core/Database.php tests/unit/DataScopeContextTest.php tests/DatabaseWrapperTest.php
git commit -m "feat(security): modelar alcance de datos por request"
```

---

### Task 3: Resolver alcance desde membresía, no desde el request

**Files:**
- Create: `src/Security/DataScope/ProjectScopeResolver.php`
- Create: `tests/unit/ProjectScopeResolverTest.php`
- Modify: `src/Core/SessionMiddleware.php`
- Modify: `src/Controllers/Api/SessionApiController.php`
- Modify: `src/Services/ProjectAccessService.php`
- Modify: `public/index.php`
- Test: `tests/test_api_session_contract.php`

**Interfaces:**
- Produces: `ProjectScopeResolver::resolve(array $session): ?ProjectScope`
- Produces: `SessionMiddleware::beginRequest(bool $requireAuthentication): ?string`
- Produces: `SessionMiddleware::requestFailureReason(): ?string`
- Requires: membership `user + project_id`, proyecto activo, área válida y rol canónico.

- [ ] **Step 1: Write resolver tests with a fake membership lookup**

El constructor recibe un callable para tests y usa PDO real por defecto:

```php
$lookup = static fn(string $user, int $projectId): ?array =>
    $user === 'test.A' && $projectId === 73
        ? ['project_id' => 73, 'role' => 'A', 'Activo' => 1, 'Area' => 'Construccion']
        : null;
$resolver = new ProjectScopeResolver($lookup, new RbacService());

self::assertSame(73, $resolver->resolve([
    'usuario' => 'test.A', 'project_id' => 73, 'permiso' => 'A',
])?->projectId());
self::assertNull($resolver->resolve([
    'usuario' => 'test.A', 'project_id' => 27, 'permiso' => 'A',
]));
```

Añadir casos de usuario vacío, proyecto inactivo y rol de sesión distinto al de la membresía; el
rol resultante siempre es el de `project_members` normalizado.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ProjectScopeResolverTest.php
```

- [ ] **Step 3: Implement resolver with identity SQL**

La consulta predeterminada debe ser exactamente membership-first y no usar `queryWithProject()`:

```sql
SELECT p.ID AS project_id, p.Activo, p.Area, p.Acceso, pm.role
FROM project_members pm
INNER JOIN general_usuarios u ON u.id = pm.user_id
INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
WHERE u.usuario = ? AND p.ID = ?
  AND p.Activo = 1
  AND p.Area IN ('Construccion', 'Pre-Construccion')
LIMIT 1
```

Si no hay fila, devolver `null`; no elegir el primer proyecto autorizado.

- [ ] **Step 4: Bind and clear once in SessionMiddleware**

Añadir `beginRequest()`: limpia el contexto una vez, reinicia y almacena la razón request-scoped,
valida la sesión y, cuando sea válida, resuelve y enlaza el alcance. `validationFailureReason()`
queda como validación sin efectos sobre el contexto; consumidores posteriores leen
`requestFailureReason()` y nunca vuelven a validar, para que `/api/session` no borre un scope ya
enlazado. Si la sesión declara proyecto pero el resolver lo rechaza, eliminar solo `project_id`,
`proyecto`, `db`, `semana`, `permiso` y `permiso_canonico`; conservar la sesión autenticada para
volver al selector.

```php
private static ?string $requestFailureReason = null;

public static function beginRequest(bool $requireAuthentication): ?string
{
    $db = \Database::getInstance();
    $db->dataScope()->clear();
    self::$requestFailureReason = null;
    $reason = self::validationFailureReason();
    if ($reason !== null) {
        self::$requestFailureReason = $reason;
        if ($requireAuthentication) {
            self::finishUnauthorized(self::redirectFor($reason), $reason);
        }
        return $reason;
    }
    $scope = (new ProjectScopeResolver($db))->resolve($_SESSION);
    if ($scope !== null) {
        $db->dataScope()->bind($scope);
    }
    return null;
}

public static function requestFailureReason(): ?string
{
    return self::$requestFailureReason;
}
```

- [ ] **Step 5: Initialize scope once in the front controller**

Antes de despachar rutas, llamar una sola vez con `false` para rutas públicas/SPA y con `true` para
rutas privadas. Registrar además un shutdown handler que limpie el contexto incluso ante excepción:

```php
$reason = \App\Core\SessionMiddleware::beginRequest($routeRequiresAuthentication);
register_shutdown_function(static function (): void {
    \Database::getInstance()->dataScope()->clear();
});
```

La llamada es segura para anónimos: devuelve la razón y deja el contexto vacío.

- [ ] **Step 6: Rebind after project selection**

En `ProjectAccessService::select()`, después de escribir sesión, resolver el mismo proyecto y enlazar
el alcance. Eliminar la resolución separada `TableResolver::getProjectIdByPrefix()` como fuente de
autorización.

- [ ] **Step 7: Extend the session contract**

`SessionApiController` consume exclusivamente `SessionMiddleware::requestFailureReason()`, incluye
`reason` solo en estado anónimo (`missing_session`, `timeout`, `inactive`, `stale_session`,
`session_unverified`) y reporta `project=null` cuando el alcance activo no pudo validarse. El test
de contrato demuestra que leer `/api/session` no vuelve a invocar la validación ni limpia el scope
ya enlazado. Ajustar el esquema React en el segundo plan; aquí solo el contrato PHP.

- [ ] **Step 8: Run focused tests**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ProjectScopeResolverTest.php
```

```bash
app_container_id="$(docker compose ps -q app)"
docker inspect "$app_container_id" --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'
docker compose exec app php tests/test_api_session_contract.php
```

Expected: ambos PASS; el segundo conserva `200 authenticated=false` sin sesión.

- [ ] **Step 9: Commit**

```bash
git add src/Security/DataScope/ProjectScopeResolver.php \
  src/Core/SessionMiddleware.php src/Controllers/Api/SessionApiController.php \
  src/Services/ProjectAccessService.php public/index.php \
  tests/unit/ProjectScopeResolverTest.php tests/test_api_session_contract.php
git commit -m "feat(security): resolver scope desde membresia activa"
```

---

### Task 4: Gate SQL fail-closed en Database

**Files:**
- Create: `src/Security/DataScope/ScopedQuery.php`
- Create: `src/Security/DataScope/ProjectSqlGuard.php`
- Create: `tests/unit/ProjectSqlGuardTest.php`
- Create: `tests/test_project_scope_database.php`
- Modify: `src/Core/Database.php`

**Interfaces:**
- Produces: `ProjectSqlGuard::guard(string, array, ProjectScope|MultiProjectScope|SystemScope|null, TableScopeCatalog): ScopedQuery`
- `ScopedQuery` contiene `sql`, `params` y `tables`.
- `Database::query()` y `queryWithProject()` pasan por el mismo guard antes de `PDO::prepare()`.

- [ ] **Step 1: Write the pure SQL matrix**

Cubrir al menos estos escenarios en `ProjectSqlGuardTest.php`:

```php
yield 'simple select is scoped' => [
    'SELECT * FROM programa WHERE Semana = ?', [8], 73,
    'programa.project_id = ?', [73, 8],
];
yield 'explicit matching id is validated without a second filter' => [
    'SELECT * FROM programa WHERE project_id = ? AND Semana = ?', [73, 8], 73,
    'programa.project_id = ?', [73, 8],
];
yield 'identity query needs no project' => [
    'SELECT * FROM project_members WHERE user_id = ?', [9], null,
    null, [9],
];
```

Añadir asserts independientes:

- tabla de proyecto sin alcance → `MissingProjectScope`;
- tabla conocida por schema pero `Unclassified` → rechazo antes de PDO;
- override 27 con alcance 73 → `ProjectScopeViolation`;
- prefijo `da_porto_programa` que resuelve 73 bajo alcance 27 → violación;
- INSERT sin `project_id` lo recibe del alcance;
- INSERT con `project_id` 27 bajo alcance 73 → violación antes de PDO;
- dos tablas project-scoped sin relación `a.project_id = b.project_id` → violación;
- join relacionado y con raíz canónica → válido.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ProjectSqlGuardTest.php
```

- [ ] **Step 3: Implement conservative guard rules**

Tokenizar SQL respetando strings, identificadores citados, comentarios y paréntesis; no decidir
seguridad con una búsqueda regex sobre texto crudo. Extraer nombres tras `FROM`, `JOIN`, `UPDATE`,
`INTO` y `DELETE FROM`, conservando alias. Para una sola tabla, insertar la condición canónica al
comienzo del `WHERE`, antes de los parámetros ya existentes. Para INSERT, ubicar `project_id` en la
lista de columnas y el placeholder homólogo; si falta, anteponer columna, `?` y scope ID. Rechazar
literals de proyecto y cualquier forma SQL que el tokenizer no pueda demostrar segura; el audit de
Task 5 migra esos callsites a una forma soportada.

Para varias referencias project-scoped, exigir:

1. una raíz con `alias.project_id = ?` cuyo parámetro sea el scope ID; y
2. cada alias adicional unido por `otro.project_id = raiz.project_id` o con su propio placeholder
   igual al scope ID.

El guard no acepta solo contar la palabra `project_id`.

- [ ] **Step 4: Route both Database APIs through one preflight**

En `Database::query()`:

```php
$scope = $this->dataScope()->current();
$guarded = (new ProjectSqlGuard($this))->guard(
    $sql,
    $params,
    $scope,
    $this->tableScopeCatalog(),
);
$stmt = $this->pdo->prepare($guarded->sql);
$stmt->execute($guarded->params);
```

`queryWithProject()` deja de ejecutar al faltar `$pid`. El tercer argumento solo es compatibilidad:
si existe debe coincidir con `ProjectScope::projectId()`. Luego delega en `query()`; no mantiene un
segundo rewriter.

`prepare()` y `prepareWithProject()` devuelven `DatabasePreparedStatement` cuando el SQL toca tabla
de proyecto, para que el guard corra en `execute()` con los parámetros completos.

- [ ] **Step 5: Add a real A/B database test**

`tests/test_project_scope_database.php` abre transacción, crea dos filas marcadas en
`auto_program_log` para proyectos distintos y enlaza `ProjectScope(73, 'test.A', 'A')`.

Verificar:

```php
$visible = $db->query(
    "SELECT detalle FROM auto_program_log WHERE detalle LIKE 'RLS_TEST_%' ORDER BY detalle"
)->fetchAll();
```

solo devuelve la fila 73. Después ejecutar
`UPDATE auto_program_log SET detalle = ? WHERE project_id = ? AND detalle = ?` con los parámetros
`['RLS_TEST_TOUCHED', 27, 'RLS_TEST_B']`, limpiar el `ProjectScope` y enlazar
`SystemScope::forMaintenance('test verification')` para comprobar que la fila 27 conserva
`RLS_TEST_B`. `finally` hace rollback y `dataScope()->clear()`.

- [ ] **Step 6: Run focused tests**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  vendor/bin/phpunit tests/unit/ProjectSqlGuardTest.php
```

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php tests/test_project_scope_database.php
```

Expected: PASS. Correr también `tests/test_global_table_safety.php`; no aceptar una regresión en
INSERT…SELECT o prefijos complejos.

- [ ] **Step 7: Commit**

```bash
git add src/Security/DataScope/ScopedQuery.php \
  src/Security/DataScope/ProjectSqlGuard.php src/Core/Database.php \
  tests/unit/ProjectSqlGuardTest.php tests/test_project_scope_database.php \
  tests/test_global_table_safety.php
git commit -m "feat(security): cerrar consultas sin project scope"
```

---

### Task 5: Eliminar bypasses y consultas de identidad mal clasificadas

**Files:**
- Create: `tests/test_project_scope_callsite_audit.php`
- Create: `src/Security/DataScope/SystemScopeRunner.php`
- Create: `tests/unit/SystemScopeRunnerTest.php`
- Modify: `src/Services/Auth/AuthenticationService.php`
- Modify: `src/Services/ProjectAccessService.php`
- Modify: `src/Controllers/Auth/LoginController.php`
- Modify: `src/Controllers/Api/LpsApiController.php`
- Modify: `src/Controllers/Core/DashboardController.php`
- Modify: `src/Controllers/Api/ControlCambiosApiController.php`
- Modify: `src/Controllers/Api/GeneralApiController.php`
- Modify: `src/Controllers/Api/SemanalApiController.php`
- Modify: `src/Controllers/Programacion/ProgramaGeneralController.php`
- Modify: `src/Controllers/Programacion/ProgramacionIntermediaController.php`
- Modify: `src/Legacy/datosGeneralesPagina.php`
- Modify: `src/Legacy/eliminar_semana.php`
- Modify: `src/Legacy/guardar_programacion_intermedia.php`
- Modify: `src/Legacy/modificar_sem_estado.php`
- Modify: `src/Legacy/nueva_semana.php`
- Modify: `src/Legacy/verificarCICActualizada.php`
- Modify: `src/Controllers/Gestion/ReportController.php`
- Modify: `admin/src/Controllers/DashboardController.php`
- Modify: `admin/async/consolidate.php`
- Modify: `scripts/higiene/reparar-mojibake-causas.php`
- Modify: `tests/DatabaseWrapperTest.php`

**Interfaces:**
- Identity SQL uses `query()`/`prepare()` and only membership services.
- Productive code may not call `setProjectContext()` outside `SessionMiddleware` and
  `ProjectAccessService`.
- `SystemScopeRunner::run(string $reason, callable $operation): mixed` enlaza, ejecuta y limpia en
  `finally`; solo entrypoints autorizados pueden llamarlo.

- [ ] **Step 1: Write the static audit first**

El test recorre `src/**/*.php` y `admin/src/**/*.php`, extrae las tablas de cada statement con el
mismo tokenizer del guard y falla ante:

- `queryWithProject(` cuando **todas** las tablas del statement son `Identity`; un join entre una
  tabla `Project` y una tabla `Identity` conserva el scope y no es hallazgo;
- `setProjectContext(` fuera de `src/Core/SessionMiddleware.php` y
  `src/Services/ProjectAccessService.php`;
- acceso directo a `$_GET['project_id']` o `$_POST['project_id']` usado como tercer argumento de
  `queryWithProject()`;
- `SystemScope::forMaintenance()` o `SystemScopeRunner::run()` fuera de
  `ReportController`, `admin/src/Controllers/DashboardController.php`,
  `admin/async/consolidate.php`, scripts de mantenimiento, migraciones y tests;
- el texto legado `executing without injection`.

Imprimir `ruta:línea:regla`; el baseline esperado es cero.

- [ ] **Step 2: Run the audit and capture the exact red list**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php tests/test_project_scope_callsite_audit.php
```

Expected: FAIL con los consumidores existentes, incluidos AuthenticationService y
ProjectAccessService.

- [ ] **Step 3: Migrate identity consumers**

Cambiar solo la API de acceso, no el SQL ni su comportamiento:

```php
// antes
$this->db->queryWithProject($membershipSql, $params);
// después
$this->db->query($membershipSql, $params);
```

Aplicar en `AuthenticationService`, `ProjectAccessService`, `LoginController`, `LpsApiController`,
`DashboardController`, `ControlCambiosApiController`, `GeneralApiController`,
`ProgramaGeneralController` y `ProgramacionIntermediaController`. `prepare()` es válido para
identidad; no crear `SystemScope` para login o selector. No cambiar los joins operativos de
`LpsService`/BI que combinan datos de proyecto con nombres de identidad: esos deben seguir
atravesando el guard con el proyecto activo.

- [ ] **Step 4: Remove controller scope overrides**

En APIs que hoy llaman `setProjectContext($projectId)` con un ID derivado del request/prefijo,
obtener el ID canónico así:

```php
$scope = $this->db->dataScope()->current();
if (!$scope instanceof ProjectScope) {
    throw new MissingProjectScope('La operación requiere un proyecto activo.');
}
$projectId = $scope->projectId();
```

No re-enlazar el contexto dentro del controlador.

- [ ] **Step 5: Make cross-project maintenance explicit**

`SystemScopeRunner` exige que el contexto esté vacío antes de mantenimiento, enlaza
`SystemScope::forMaintenance($reason)` y siempre limpia en `finally`; no sustituye ni restaura un
scope previo. El test cubre éxito, excepción y rechazo cuando ya existe un `ProjectScope`.

En `ReportController`, envolver únicamente los casos globales `curva-s`, `general`,
`restricciones-general`, `pdc`, `subcontratistas` y `run-all`, después de
`authorizePermission('lps.reportes.generar', ...)`, con razón `report:<tipo>:<usuario>`. En las dos
entradas admin usar `admin:consolidation:<usuario>`. En el script de mojibake usar
`maintenance:repair-mojibake`; el gate `AUTORIZADO_POR_FELIPE=1` sigue siendo obligatorio para
escribir. No crear scope de sistema dentro de `ReportProcessor`: el servicio debe fallar si el
entrypoint olvidó autorizarlo.

- [ ] **Step 6: Run audit and focused regressions**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php tests/test_project_scope_callsite_audit.php
```

Expected: PASS con cero hallazgos.

```bash
docker compose exec app php tests/test_api_auth_contract.php
docker compose exec app php tests/test_api_projects_contract.php
```

Expected: ambos PASS; login y selector siguen funcionando sin proyecto activo.

- [ ] **Step 7: Commit**

Stage solo la lista contractual de esta tarea y el gate:

```bash
git add tests/test_project_scope_callsite_audit.php tests/DatabaseWrapperTest.php \
  tests/unit/SystemScopeRunnerTest.php src/Security/DataScope/SystemScopeRunner.php \
  src/Services/Auth/AuthenticationService.php src/Services/ProjectAccessService.php \
  src/Controllers/Auth/LoginController.php src/Controllers/Api/LpsApiController.php \
  src/Controllers/Core/DashboardController.php src/Controllers/Api/ControlCambiosApiController.php \
  src/Controllers/Api/GeneralApiController.php src/Controllers/Api/SemanalApiController.php \
  src/Controllers/Programacion/ProgramaGeneralController.php \
  src/Controllers/Programacion/ProgramacionIntermediaController.php \
  src/Legacy/datosGeneralesPagina.php src/Legacy/eliminar_semana.php \
  src/Legacy/guardar_programacion_intermedia.php src/Legacy/modificar_sem_estado.php \
  src/Legacy/nueva_semana.php src/Legacy/verificarCICActualizada.php \
  src/Controllers/Gestion/ReportController.php admin/src/Controllers/DashboardController.php \
  admin/async/consolidate.php scripts/higiene/reparar-mojibake-causas.php
git commit -m "refactor(security): retirar bypasses de project scope"
```

Antes del commit, revisar `git diff --cached --name-only`; un archivo adicional exige primero
actualizar la sección **Files** y explicar qué regla del audit lo incorporó.

---

### Task 6: Alcance BI multiproyecto explícito

**Files:**
- Modify: `src/Support/BiProjectScope.php`
- Modify: `src/Core/Database.php`
- Modify: `src/Controllers/Api/BiControlTowerApiController.php`
- Modify: `src/Controllers/Bi/BiViewController.php`
- Modify: `src/Services/ControlTowerService.php`
- Modify: `src/Services/Pdc/SeguimientoService.php`
- Modify: `src/Services/Bi/ActionRecommendationService.php`
- Modify: `src/Services/Bi/RiskScoringService.php`
- Modify: `src/Services/Bi/MetricScope.php`
- Modify: `src/Services/Bi/MetricExecutor.php`
- Modify: `tests/test_bi_project_scope.php`
- Create: `tests/test_bi_multi_project_database_scope.php`

**Interfaces:**
- Produces: `BiProjectScope::scope($requested, array $session, string $reason): MultiProjectScope`
- Produces: `Database::queryForProjects(MultiProjectScope, string, array): PDOStatement`
- A normal `ProjectScope` never accepts `IN` with more than one project.

- [ ] **Step 1: Extend tests before implementation**

En `test_bi_project_scope.php` comprobar que `[73, 27]` autorizado produce un
`MultiProjectScope` ordenado, que `[73, 999999]` lanza `DomainException`, que `resolve()` devuelve
`[]` ante ausencia de usuario y que `scope()` rechaza ese conjunto vacío con `DomainException`; en
ningún caso se elige el primer proyecto global.

En el test de base, sembrar filas A/B/C y verificar que `queryForProjects(scope[A,B], ...)` solo
devuelve A/B aunque el SQL omita `project_id`. Añadir una prueba que pase un `MetricScope`
construido desde el mismo `MultiProjectScope` y verifique que no puede agregar C.

- [ ] **Step 2: Run and see red**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_bi_project_scope.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_bi_multi_project_database_scope.php
```

- [ ] **Step 3: Return a scope object from BiProjectScope**

Conservar `resolve()` como adaptador temporal que devuelve IDs, pero añadir:

```php
public function scope($requestedRaw, array $session, string $reason): MultiProjectScope
{
    $ids = $this->resolve($requestedRaw, $session);
    return new MultiProjectScope(
        $ids,
        trim((string) ($session['usuario'] ?? '')),
        $this->reportRole($ids, $session),
        $reason,
    );
}
```

- [ ] **Step 4: Add the explicit Database API**

`queryForProjects()` rechaza consultas compuestas únicamente por tablas de identidad y reescribe
cada tabla de proyecto con `project_id IN (?,...)`, insertando la lista autorizada. Puede conservar
un join de identidad cuando existe al menos una raíz project-scoped correctamente enlazada. Un SQL
que ya declara IDs solicitados se intersecta con el scope en el servidor; no se confía en esa lista.

- [ ] **Step 5: Migrate BI call sites**

`BiControlTowerApiController` y `BiViewController` construyen una vez el scope con una razón estable
como `bi:control-tower:general` y lo pasan a `ControlTowerService`. Ese servicio lo propaga a
`SeguimientoService`, `ActionRecommendationService` y `RiskScoringService`; las consultas de esos
cuatro servicios usan `queryForProjects()`. `MetricScope` recibe un `MultiProjectScope` en vez de
crear autoridad desde un array, y `MetricExecutor` usa ese objeto al consultar. Eliminar arrays de
IDs sueltos en la frontera de `Database`; pueden seguir existiendo como datos de presentación
obtenidos mediante `$scope->projectIds()`.

- [ ] **Step 6: Run BI and isolation tests**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_bi_project_scope.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_bi_multi_project_database_scope.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=datos-proyecto
```

Expected: PASS; ningún reporte mezcla un ID no autorizado.

- [ ] **Step 7: Commit**

```bash
git add src/Support/BiProjectScope.php src/Core/Database.php \
  src/Controllers/Api/BiControlTowerApiController.php src/Controllers/Bi/BiViewController.php \
  src/Services/ControlTowerService.php src/Services/Pdc/SeguimientoService.php \
  src/Services/Bi/ActionRecommendationService.php src/Services/Bi/RiskScoringService.php \
  src/Services/Bi/MetricScope.php src/Services/Bi/MetricExecutor.php \
  tests/test_bi_project_scope.php tests/test_bi_multi_project_database_scope.php
git commit -m "feat(security): hacer explicito el scope multiproyecto BI"
```

---

### Task 7: Convergencia de schema y cuenta MySQL de runtime

**Files:**
- Create: `database/migrations/20260828_project_scope_contract.php`
- Create: `scripts/security/audit-runtime-db-grants.php`
- Create: `docs/security/runtime-db-user.md`
- Modify: `docker-compose.yml`
- Modify: `docker-compose.ci.yml`
- Modify: `docs/global-tables-architecture.md`
- Test: `tests/test_project_scope_schema_contract.php`

**Interfaces:**
- Migration default is dry-run; `--apply` is idempotent and aborts on NULL project IDs.
- Grant audit exits non-zero for root, `ALL PRIVILEGES`, `GRANT OPTION`, `CREATE`, `ALTER`, `DROP`,
  `FILE`, `SUPER`, `PROCESS` or grants server-globales `*.*`; el DML explícito sobre `lps.*` sí es
  válido para runtime.

- [ ] **Step 1: Write the grant audit test behavior**

El script acepta `--grants-file` para pruebas puras y stdin real por defecto. Crear fixtures inline
que comprueben:

```text
GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`  -> RC 0
GRANT ALL PRIVILEGES ON *.* TO `root`@`%` WITH GRANT OPTION          -> RC 1
```

- [ ] **Step 2: Implement the dry-run migration**

Por cada tabla `Project`:

1. contar `project_id IS NULL` y abortar si el conteo es mayor a cero;
2. generar `ALTER TABLE ... MODIFY project_id INT NOT NULL` solo si es nullable;
3. generar `ALTER TABLE ... ADD INDEX idx_<tabla>_project_scope (project_id)` solo si ningún índice
   empieza por `project_id`;
4. ejecutar únicamente con `--apply`;
5. imprimir al final `tables_checked`, `null_rows`, `columns_changed`, `indexes_added`.

Usar quoting de identificadores validado con `/^[A-Za-z0-9_]+$/`; valores siempre preparados.

- [ ] **Step 3: Run dry-run and resolve factual violations**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php database/migrations/20260828_project_scope_contract.php
```

Expected: RC 0 y lista de SQL propuesto. Si hay NULLs, el plan se detiene antes de `--apply`: se
registra tabla/conteo y se diseña un backfill específico con respaldo; no se asigna proyecto 0.

- [ ] **Step 4: Configure a separate runtime user in Docker**

Agregar variables `DB_RUNTIME_USER` y `DB_RUNTIME_PASS` al servicio `db` como `MYSQL_USER` y
`MYSQL_PASSWORD`, y al servicio `app` como `DB_USER`/`DB_PASS`. Mantener `MYSQL_ROOT_PASSWORD`
separado como `DB_ADMIN_PASS`. En CI usar valores efímeros declarados en el workflow/compose CI;
ningún valor real entra al repo.

Documentar para un volumen existente los comandos exactos de creación/grant usando variables del
shell y entrada silenciosa de contraseña. El runbook separa local, CI y SiteGround y exige ejecutar
`audit-runtime-db-grants.php` después.

- [ ] **Step 5: Apply only behind the data-change gate**

Antes de ejecutar:

1. congelar escrituras de la base compartida;
2. crear respaldo nuevo;
3. probar restauración;
4. mostrar dry-run y pedir autorización explícita a Felipe.

Después de autorización:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php database/migrations/20260828_project_scope_contract.php --apply
```

No ejecutar este paso solo porque el plan fue aprobado; es una mutación de schema con gate propio.

- [ ] **Step 6: Verify schema and grants**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_project_scope_schema_contract.php --enforce
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/security/audit-runtime-db-grants.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_global_table_reconciliation.php
```

Expected: tres RC 0.

- [ ] **Step 7: Commit code and documentation, never env or secrets**

```bash
git add database/migrations/20260828_project_scope_contract.php \
  scripts/security/audit-runtime-db-grants.php docs/security/runtime-db-user.md \
  docker-compose.yml docker-compose.ci.yml docs/global-tables-architecture.md \
  tests/test_project_scope_schema_contract.php
git commit -m "feat(security): endurecer schema y usuario runtime"
```

---

### Task 8: Gate HTTP A→B, observabilidad y cierre de RLS

**Files:**
- Create: `tests/test_project_scope_http_isolation.php`
- Create: `src/Security/DataScope/ScopeViolationLogger.php`
- Modify: `public/index.php` (respuesta segura de excepciones de scope)
- Modify: `scripts/run-php-tests.php`
- Modify: `.github/workflows/ci.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `TASKS.md`

**Interfaces:**
- Scope violations produce `404` for foreign resources, `403` for forbidden known actions and a
  correlation ID; never include SQL/table/project ajeno in the response.
- Logs include correlation, route, user and active project, without cookies, CSRF or payload.

- [ ] **Step 1: Write the HTTP isolation scenarios**

El test usa dos proyectos y membresías de fixture existentes, crea solo filas marcadas y guarda una
instantánea exacta de cada fila afectada. Como HTTP usa otra conexión, no mantiene una transacción
abierta esperando que el servidor la comparta: restaura o elimina explícitamente cada marca en
`finally`. Abrir sesión como usuario A por sesión artificial, seleccionar A y probar:

1. GET operativo con `project_id=B` no devuelve la marca B;
2. POST de una fila B devuelve 404/403 y deja la fila intacta;
3. prefijo B en query/route legacy queda rechazado;
4. una segunda sesión B en el mismo proceso de test no hereda scope A;
5. respuesta contiene `correlationId`, no `project_id`, tabla ni SQL.

Usar una fila marcada y rollback/restore en `finally`; no depender de datos humanos.

- [ ] **Step 2: Run and see red**

```bash
app_container_id="$(docker compose ps -q app)"
docker inspect "$app_container_id" --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'
docker compose exec app php tests/test_project_scope_http_isolation.php
```

- [ ] **Step 3: Add centralized safe handling**

En la frontera HTTP capturar `MissingProjectScope`/`ProjectScopeViolation`, generar
`bin2hex(random_bytes(8))`, registrar mediante `ScopeViolationLogger` y responder:

```json
{"error":{"code":"RESOURCE_NOT_FOUND","message":"No encontramos ese recurso.","correlationId":"…"}}
```

No capturar `Throwable` globalmente como 404; solo las excepciones tipadas de scope.

- [ ] **Step 4: Make RLS tests mandatory**

Registrar los nuevos scripts en el nivel `db/http` del runner según dependencia. Añadir al CI:

```yaml
- name: RLS application gates
  run: |
    docker compose exec -T app php tests/test_project_scope_callsite_audit.php
    docker compose exec -T app php tests/test_project_scope_schema_contract.php --enforce
    docker compose exec -T app php tests/test_project_scope_database.php
    docker compose exec -T app php tests/test_project_scope_http_isolation.php
```

- [ ] **Step 5: Run the complete proportional verification**

Cada comando separado:

```bash
docker compose exec app php tests/test_project_scope_http_isolation.php
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
docker compose exec app php scripts/run-php-tests.php --nivel=http
npm run test:rbac-parity
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G --no-progress
```

Expected: todos RC 0. Conservar la salida del test A→B como evidencia del frente.

- [ ] **Step 6: Update docs with the measured result**

README explica el modelo single/multi/system y cómo enlazar/limpiar scope en comandos. CHANGELOG
registra fail-closed. TASKS enlaza esta spec y el plan, sin marcar casillas históricas.

- [ ] **Step 7: Commit**

```bash
git add tests/test_project_scope_http_isolation.php \
  src/Security/DataScope/ScopeViolationLogger.php public/index.php \
  scripts/run-php-tests.php .github/workflows/ci.yml README.md CHANGELOG.md TASKS.md
git commit -m "test(security): bloquear acceso horizontal entre proyectos"
```

## Completion Gate

RLS queda listo para habilitar el plan del shell solo cuando:

- `queryWithProject()` no contiene ni documenta fallback sin alcance;
- el audit de callsites da cero;
- el contrato de schema da cero;
- toda tabla descubierta queda clasificada explícitamente; `Unclassified` permanece denegada;
- la cuenta de runtime pasa el audit de grants;
- A→B falla en lectura y mutación;
- BI acepta únicamente el conjunto autorizado;
- suite HTTP, RBAC y PHPStan están verdes;
- el árbol está limpio y los commits no contienen `.env`, respaldos ni evidencia con datos.
