# Follow-ups del review final A2 (maestro de insumos) — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar los 4 follow-ups triados del FINAL REVIEW A2: captura de errno 1062 en creación de insumos del maestro, batch multi-fila del upsert de `generarVinculos`, escape de comodines LIKE, y paquete de retiro de insumos (`activo=0` + reactivación + reversión de auto-match + auditoría `updated_at`).

**Architecture:** Todos los cambios de backend viven en `../lps-aia` (servicio `MaestroInsumosService`, controlador `PlanComprasMaestroController`, rutas en `public/index.php`, migración DDL). El SPA de este repo (`src/pages/MaestroInsumos.tsx`, `src/lib/types.ts`) agrega la UI mínima de retiro/reactivación en el catálogo. Verificación: `tests/test_pdc_v2_maestro.php` extendido, PHPStan, e2e `pdc-v2-maestro.spec.mjs` extendido, con la secuencia alternada PHP → e2e → PHP.

**Tech Stack:** PHP 8.2 + MySQL 8 (Docker de lps-aia), React + Vite + AG Grid Community, Vitest, Playwright.

## Global Constraints

- Trabajar en rama `pdc-a2-followups` en AMBOS repos (main de lps-aia recibe trabajo ajeno en paralelo: no tocar commits ajenos, re-verificar main antes de merge).
- Tablas nuevas/alteradas: catálogos `general_*` sin `project_id`; migraciones DDL `.sql` en `lps-aia/database/migrations/`.
- Verificación de BD sobre el MySQL real de Docker (nunca mocks).
- No usar features Enterprise de AG Grid ni AllCommunityModule.
- Idioma: docs/comentarios en español; identificadores en su idioma original.
- Envelope JSON `{ok, data|error}`; escritura = RBAC `lps.pdc.maestro` + CSRF `plan_compras_v2`.

---

### Task 1: Migración — columnas de auditoría del maestro

**Files:**
- Create: `../lps-aia/database/migrations/20260723_pdc_v2_maestro_retiro.sql`

**Interfaces:**
- Produces: columnas `general_maestro_insumos.actualizado_por` (varchar 100, default '') y `general_maestro_insumos.updated_at` (datetime NULL). Las usan Tasks 2, 5 y 6.

- [ ] **Step 1: Escribir la migración**

```sql
-- 20260723_pdc_v2_maestro_retiro.sql
-- Follow-up A2: auditoría de ediciones del catálogo global de insumos
-- (retiro/reactivación con activo=0/1 y trazabilidad de quién/cuándo).

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `actualizado_por` varchar(100) NOT NULL DEFAULT '' AFTER `creado_por`,
  ADD COLUMN `updated_at` datetime NULL DEFAULT NULL AFTER `created_at`;
```

- [ ] **Step 2: Aplicarla al MySQL de Docker**

En `../lps-aia`: `docker compose up -d db app` y aplicar el `.sql` con el cliente mysql del contenedor db (credenciales del docker-compose). Verificar con `SHOW COLUMNS FROM general_maestro_insumos` que existen `actualizado_por` y `updated_at`.

- [ ] **Step 3: Commit en lps-aia** (`git checkout -b pdc-a2-followups` primero)

```bash
git add database/migrations/20260723_pdc_v2_maestro_retiro.sql
git commit -m "feat(pdc-v2): migración de auditoría del maestro (actualizado_por, updated_at)"
```

### Task 2: Captura de errno 1062 en `crearDesdePendientes` y `crearManual`

**Files:**
- Modify: `../lps-aia/src/Services/Pdc/MaestroInsumosService.php` (métodos `crearDesdePendientes`, `crearManual`; helpers nuevos)
- Test: `../lps-aia/tests/test_pdc_v2_maestro.php`

**Interfaces:**
- Produces: `maestroPorClave(string $norm, string $unidad): ?array{id:int,activo:int}` (búsqueda con la semántica de la unique key: prefijo de 191 chars + unidad) y `reactivarSiInactivo(array $maestro, string $usuario): int` (usado también por Task 5's semántica). `crearManual` sigue devolviendo `['ok'=>false,'code'=>'MAESTRO_DUPLICADO']` ante duplicado, ahora también bajo carrera/colisión de prefijo.

- [ ] **Step 1: Tests que fallan** — añadir al final de `test_pdc_v2_maestro.php` (antes del cleanup): colisión por prefijo de 191 chars.

```php
echo "=== PDC v2: maestro — follow-ups A2 (1062 / colisión de prefijo) ===\n";

// La unique key es (descripcion_norm(191), unidad): dos normas que comparten los
// primeros 191 chars chocan en el INSERT aunque el igual estricto no las encuentre.
$prefijo191 = str_repeat('X', 191);
$mL = $maestro->crearManual(PDC_M_PROJECT_A, $prefijo191 . 'AAA', 'UN', 'MAT', PDC_M_MARCA);
$assert($mL['ok'] === true, 'Prefijo: primer insumo largo se crea.');

// crearManual: el pre-check (igualdad completa) no lo ve → INSERT 1062 → MAESTRO_DUPLICADO, no 500.
$mL2 = $maestro->crearManual(PDC_M_PROJECT_A, $prefijo191 . 'CCC', 'UN', 'MAT', PDC_M_MARCA);
$assert($mL2['ok'] === false && $mL2['code'] === 'MAESTRO_DUPLICADO', 'crearManual captura 1062 como MAESTRO_DUPLICADO.');

// crearDesdePendientes: vincula al existente en vez de abortar el lote con excepción.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], $prefijo191 . 'BBB', 'UN', $prefijo191 . 'BBB', 'MAT'],
);
$vinculoLargo = (int) $db->lastInsertId();
$rL = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, [$vinculoLargo], PDC_M_MARCA);
$assert($rL['ok'] === true && $rL['creados'] === 0 && $rL['vinculados'] === 1, 'Colisión de prefijo: vincula al existente sin crear ni abortar.');
```

- [ ] **Step 2: Correr el test y verificar que falla** (`docker compose exec app php tests/test_pdc_v2_maestro.php`) — esperado: excepción PDO 1062 o FAIL en los nuevos asserts.

- [ ] **Step 3: Implementación** — en `MaestroInsumosService`:

```php
/** Fila del maestro que ocupa la clave única (prefijo de 191 chars de la norma + unidad), o null. */
private function maestroPorClave(string $norm, string $unidad): ?array
{
    $fila = $this->db->query(
        'SELECT id, activo FROM general_maestro_insumos WHERE LEFT(descripcion_norm, 191) = LEFT(?, 191) AND unidad = ? LIMIT 1',
        [$norm, $unidad],
    )->fetch(\PDO::FETCH_ASSOC);
    return $fila === false ? null : ['id' => (int) $fila['id'], 'activo' => (int) $fila['activo']];
}

private function reactivarSiInactivo(array $maestro, string $usuario): int
{
    if ($maestro['activo'] === 0) {
        $this->db->query(
            'UPDATE general_maestro_insumos SET activo = 1, actualizado_por = ?, updated_at = NOW() WHERE id = ?',
            [$usuario, $maestro['id']],
        );
    }
    return $maestro['id'];
}

private static function esDuplicado(\PDOException $e): bool
{
    return (int) ($e->errorInfo[1] ?? 0) === 1062;
}
```

Loop de `crearDesdePendientes` (reemplaza el SELECT por igualdad + INSERT sin catch):

```php
foreach ($pendientes as $p) {
    $existente = $this->maestroPorClave($p['descripcion_norm'], $p['unidad']);
    if ($existente === null) {
        try {
            $this->db->query(
                'INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
                 VALUES (?, ?, ?, ?, 1, ?, NOW())',
                [$p['descripcion_original'], $p['descripcion_norm'], $p['unidad'], $p['tipo_insumo'], $usuario],
            );
            $maestroId = (int) $this->db->lastInsertId();
            $creados++;
        } catch (\PDOException $e) {
            // Carrera concurrente (otro proceso insertó la misma clave): re-leer y vincular.
            if (!self::esDuplicado($e) || ($existente = $this->maestroPorClave($p['descripcion_norm'], $p['unidad'])) === null) {
                throw $e;
            }
            $maestroId = $this->reactivarSiInactivo($existente, $usuario);
        }
    } else {
        $maestroId = $this->reactivarSiInactivo($existente, $usuario);
    }
    $this->db->query(
        "UPDATE pdc_insumo_vinculos SET maestro_id = ?, estado = 'confirmado' WHERE project_id = ? AND id = ?",
        [$maestroId, $projectId, (int) $p['id']],
    );
    $vinculados++;
}
```

`crearManual`: envolver el INSERT en try/catch:

```php
try {
    $this->db->query(
        'INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
         VALUES (?, ?, ?, ?, 1, ?, NOW())',
        [mb_substr(trim($descripcion), 0, 500), $norm, mb_substr($unidad, 0, 20), mb_substr(trim($tipoInsumo), 0, 100), $usuario],
    );
} catch (\PDOException $e) {
    if (self::esDuplicado($e)) {
        return ['ok' => false, 'code' => 'MAESTRO_DUPLICADO'];
    }
    throw $e;
}
```

- [ ] **Step 4: Correr el test → PASS completo** (todos los asserts previos siguen en verde).
- [ ] **Step 5: Commit** `fix(pdc-v2): maestro tolera 1062 — vincula al existente en vez de abortar (carrera/colisión de prefijo)`

### Task 3: Batch multi-fila del upsert de `generarVinculos`

**Files:**
- Modify: `../lps-aia/src/Services/Pdc/MaestroInsumosService.php` (`generarVinculos`, sección 2)

**Interfaces:**
- Consumes/Produces: firma y semántica de `generarVinculos` intactas (idempotencia y no-pisar decisiones humanas, cubiertas por los asserts existentes).

- [ ] **Step 1: Reemplazar el foreach de upserts fila a fila por lotes de 200 filas**

```php
// 2) Upsert de vínculos sin pisar decisiones humanas ni des-vincular.
//    Multi-fila por lotes: un presupuesto real trae ~800 insumos únicos y el
//    upsert fila a fila costaba ~800 round-trips en cada carga de la vista.
foreach (array_chunk(array_values($porClave), 200) as $lote) {
    $valores = implode(', ', array_fill(0, count($lote), "(?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')"));
    $params = [];
    foreach ($lote as $u) {
        array_push($params, $projectId, $vid, $u['norm'], $u['unidad'], mb_substr($u['original'], 0, 500), $u['tipo'], round($u['cantidad'], 4), round($u['valor'], 2), $u['apariciones']);
    }
    $this->db->query(
        "INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
         VALUES {$valores}
         ON DUPLICATE KEY UPDATE
            descripcion_original = VALUES(descripcion_original),
            tipo_insumo = VALUES(tipo_insumo),
            cantidad_total = VALUES(cantidad_total),
            valor_total = VALUES(valor_total),
            apariciones = VALUES(apariciones)",
        $params,
    );
}
```

- [ ] **Step 2: Correr `tests/test_pdc_v2_maestro.php` → PASS** (los asserts de cold start, idempotencia y consolidación son el gate del refactor).
- [ ] **Step 3: Commit** `perf(pdc-v2): upsert de vínculos del maestro en lotes multi-fila (200/query)`

### Task 4: Escape de comodines LIKE en `sugerencias()` y `catalogo()`

**Files:**
- Modify: `../lps-aia/src/Services/Pdc/MaestroInsumosService.php`
- Test: `../lps-aia/tests/test_pdc_v2_maestro.php`

- [ ] **Step 1: Tests que fallan** (añadir tras los de Task 2):

```php
echo "=== PDC v2: maestro — follow-ups A2 (escape de comodines LIKE) ===\n";

$maestro->crearManual(PDC_M_PROJECT_A, 'Viga C_10', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Viga C 10', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Malla 100%', 'UN', 'MAT', PDC_M_MARCA);
$maestro->crearManual(PDC_M_PROJECT_A, 'Malla 100337', 'UN', 'MAT', PDC_M_MARCA);

$descs = array_column($maestro->catalogo('C_10'), 'descripcion');
$assert(in_array('Viga C_10', $descs, true) && !in_array('Viga C 10', $descs, true), 'Catálogo: _ se busca literal, no como comodín.');
$descs = array_column($maestro->catalogo('100%'), 'descripcion');
$assert(in_array('Malla 100%', $descs, true) && !in_array('Malla 100337', $descs, true), 'Catálogo: % se busca literal, no como comodín.');

// Sugerencias: el token C_10 solo debe puntuar el match literal.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], 'VIGA C_10 REFORZADA', 'UN', 'Viga C_10 reforzada', 'MAT'],
);
$sugL = $maestro->sugerencias(PDC_M_PROJECT_A, (int) $db->lastInsertId());
$assert($sugL !== [] && $sugL[0]['descripcion'] === 'Viga C_10', 'Sugerencias: tokens con _ puntúan solo el literal.');
```

- [ ] **Step 2: Correr y verificar FAIL** en los 3 asserts nuevos.
- [ ] **Step 3: Implementación** — en `sugerencias()`:

```php
$params = array_map(static fn ($t) => '%' . addcslashes($t, '\\%_') . '%', $tokens);
```

En `catalogo()`:

```php
$params[] = '%' . addcslashes(self::normalizar($busqueda), '\\%_') . '%';
```

- [ ] **Step 4: Correr → PASS completo.**
- [ ] **Step 5: Commit** `fix(pdc-v2): comodines LIKE escapados en sugerencias y búsqueda del catálogo`

### Task 5: Retiro de insumos — servicio, controlador y rutas

**Files:**
- Modify: `../lps-aia/src/Services/Pdc/MaestroInsumosService.php` (nuevos `desactivar`, `reactivar`; `catalogo` con `$incluirInactivos`)
- Modify: `../lps-aia/src/Controllers/Api/PlanComprasMaestroController.php` (acciones `desactivar`, `reactivar`; `catalogo` lee `incluirInactivos`)
- Modify: `../lps-aia/public/index.php` (2 rutas POST nuevas)
- Test: `../lps-aia/tests/test_pdc_v2_maestro.php`

**Interfaces:**
- Produces: `desactivar(int $maestroId, string $usuario): array{ok:bool, code?:string, revertidos?:int}` — pone `activo=0`, audita, y revierte a `pendiente` (con `maestro_id=NULL`) los vínculos `auto` de TODOS los proyectos; los `confirmado` (decisión humana) se conservan. `reactivar(int $maestroId, string $usuario): array{ok:bool, code?:string}`. `catalogo(?string $busqueda, bool $incluirInactivos = false, int $limite = 200)` devuelve además `activo` (int) y `updatedAt` (string|null).
- Endpoints: `POST /plan-compras/api/maestro/desactivar {maestroId}` → `{revertidos}`; `POST /plan-compras/api/maestro/reactivar {maestroId}` → `{reactivado:1}`; ambos con guardEscritura. `GET /plan-compras/api/maestro?busqueda=&incluirInactivos=1`.

- [ ] **Step 1: Tests que fallan** (añadir tras los de Task 4):

```php
echo "=== PDC v2: maestro — follow-ups A2 (retiro y reactivación) ===\n";

// Estado al llegar aquí: TEJA DE ZINC tiene un vínculo auto (re-import) y uno
// confirmado (TEJA ZINC CALIBRE 34 vinculado a mano más arriba).
$midTeja = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'TEJA DE ZINC' AND unidad = 'M2'")->fetchColumn();
$rd = $maestro->desactivar($midTeja, PDC_M_MARCA);
$assert($rd['ok'] === true && $rd['revertidos'] === 1, 'Retiro revierte exactamente el vínculo auto.');
$auto = $db->query("SELECT estado, maestro_id FROM pdc_insumo_vinculos WHERE project_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A])->fetch(\PDO::FETCH_ASSOC);
$assert($auto['estado'] === 'pendiente' && $auto['maestro_id'] === null, 'Vínculo auto vuelve a pendiente sin maestro.');
$conf = $db->query("SELECT estado, maestro_id FROM pdc_insumo_vinculos WHERE project_id = ? AND descripcion_norm = 'TEJA ZINC CALIBRE 34'", [PDC_M_PROJECT_A])->fetch(\PDO::FETCH_ASSOC);
$assert($conf['estado'] === 'confirmado' && (int) $conf['maestro_id'] === $midTeja, 'Vínculo confirmado (decisión humana) se conserva.');

// Auditoría y visibilidad en catálogo.
$aud = $db->query('SELECT activo, actualizado_por, updated_at FROM general_maestro_insumos WHERE id = ?', [$midTeja])->fetch(\PDO::FETCH_ASSOC);
$assert((int) $aud['activo'] === 0 && $aud['actualizado_por'] === PDC_M_MARCA && $aud['updated_at'] !== null, 'Retiro audita actualizado_por y updated_at.');
$assert(array_column($maestro->catalogo('teja de zinc'), 'id') === [], 'Catálogo por defecto oculta retirados.');
$idsInactivos = array_column($maestro->catalogo('teja de zinc', true), 'id');
$assert(in_array($midTeja, $idsInactivos, true), 'Catálogo con incluirInactivos muestra retirados.');

// Guardas.
$assert($maestro->desactivar($midTeja, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Retirar dos veces se rechaza.');
$assert($maestro->desactivar(999999999, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Retirar inexistente se rechaza.');

// Reactivar + regenerar repone el auto-match.
$assert($maestro->reactivar($midTeja, PDC_M_MARCA)['ok'] === true, 'Reactivación OK.');
$assert($maestro->reactivar($midTeja, PDC_M_MARCA)['code'] === 'MAESTRO_INVALIDO', 'Reactivar un activo se rechaza.');
$gR = $maestro->generarVinculos(PDC_M_PROJECT_A);
$autoR = $db->query("SELECT estado FROM pdc_insumo_vinculos WHERE project_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A])->fetchColumn();
$assert($autoR === 'auto', 'Tras reactivar, regenerar repone el auto-match.');

// crearDesdePendientes reactiva un maestro retirado en vez de duplicar o fallar.
$maestro->desactivar($midTeja, PDC_M_MARCA);
$vidTeja = (int) $db->query("SELECT id FROM pdc_insumo_vinculos WHERE project_id = ? AND descripcion_norm = 'TEJA DE ZINC'", [PDC_M_PROJECT_A])->fetchColumn();
$rReact = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, [$vidTeja], PDC_M_MARCA);
$actTeja = (int) $db->query('SELECT activo FROM general_maestro_insumos WHERE id = ?', [$midTeja])->fetchColumn();
$assert($rReact['creados'] === 0 && $rReact['vinculados'] === 1 && $actTeja === 1, 'El masivo reactiva un maestro retirado y vincula.');
```

- [ ] **Step 2: Correr y verificar FAIL** (métodos inexistentes).
- [ ] **Step 3: Implementación del servicio**

```php
public function desactivar(int $maestroId, string $usuario): array
{
    $activo = $this->db->query('SELECT activo FROM general_maestro_insumos WHERE id = ?', [$maestroId])->fetchColumn();
    if ($activo === false || (int) $activo === 0) {
        return ['ok' => false, 'code' => 'MAESTRO_INVALIDO'];
    }
    $this->db->beginTransaction();
    try {
        $this->db->query(
            'UPDATE general_maestro_insumos SET activo = 0, actualizado_por = ?, updated_at = NOW() WHERE id = ?',
            [$usuario, $maestroId],
        );
        // Reversión global del auto-match: los vínculos confirmados (decisión humana) se conservan.
        $stmt = $this->db->query(
            "UPDATE pdc_insumo_vinculos SET maestro_id = NULL, estado = 'pendiente' WHERE maestro_id = ? AND estado = 'auto'",
            [$maestroId],
        );
        $revertidos = $stmt->rowCount();
        $this->db->commit();
    } catch (\Throwable $t) {
        $this->db->rollBack();
        throw $t;
    }
    return ['ok' => true, 'revertidos' => $revertidos];
}

public function reactivar(int $maestroId, string $usuario): array
{
    $activo = $this->db->query('SELECT activo FROM general_maestro_insumos WHERE id = ?', [$maestroId])->fetchColumn();
    if ($activo === false || (int) $activo === 1) {
        return ['ok' => false, 'code' => 'MAESTRO_INVALIDO'];
    }
    $this->db->query(
        'UPDATE general_maestro_insumos SET activo = 1, actualizado_por = ?, updated_at = NOW() WHERE id = ?',
        [$usuario, $maestroId],
    );
    return ['ok' => true];
}
```

`catalogo` (nueva firma `catalogo(?string $busqueda = null, bool $incluirInactivos = false, int $limite = 200)`):

```php
$where = $incluirInactivos ? '1 = 1' : 'activo = 1';
// ... búsqueda igual que hoy (con el escape de Task 4) ...
$rows = $this->db->query(
    "SELECT id, descripcion, unidad, tipo_insumo, activo, creado_por, created_at, updated_at
     FROM general_maestro_insumos WHERE {$where} ORDER BY descripcion ASC LIMIT " . (int) $limite,
    $params,
)->fetchAll(\PDO::FETCH_ASSOC);
return array_map(static fn (array $r): array => [
    'id' => (int) $r['id'],
    'descripcion' => $r['descripcion'],
    'unidad' => $r['unidad'],
    'tipoInsumo' => $r['tipo_insumo'],
    'activo' => (int) $r['activo'],
    'creadoPor' => $r['creado_por'],
    'createdAt' => $r['created_at'],
    'updatedAt' => $r['updated_at'],
], $rows);
```

- [ ] **Step 4: Controlador** — `catalogo()` pasa `($_GET['incluirInactivos'] ?? '') === '1'`; nuevas acciones:

```php
/** POST /plan-compras/api/maestro/desactivar {maestroId} */
public function desactivar(): void
{
    if ($this->guardEscritura() === null) {
        return;
    }
    $body = $this->body();
    $r = $this->service->desactivar((int) ($body['maestroId'] ?? 0), $this->usuario());
    if (!$r['ok']) {
        $this->fail('MAESTRO_INVALIDO', 'El insumo no existe o ya está retirado.', 422);
        return;
    }
    $this->ok(['revertidos' => $r['revertidos']]);
}

/** POST /plan-compras/api/maestro/reactivar {maestroId} */
public function reactivar(): void
{
    if ($this->guardEscritura() === null) {
        return;
    }
    $body = $this->body();
    $r = $this->service->reactivar((int) ($body['maestroId'] ?? 0), $this->usuario());
    if (!$r['ok']) {
        $this->fail('MAESTRO_INVALIDO', 'El insumo no existe o ya está activo.', 422);
        return;
    }
    $this->ok(['reactivado' => 1]);
}
```

- [ ] **Step 5: Rutas** en `public/index.php` junto a las del maestro (~línea 198):

```php
$router->post('/plan-compras/api/maestro/desactivar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'desactivar']);
$router->post('/plan-compras/api/maestro/reactivar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'reactivar']);
```

- [ ] **Step 6: Correr `tests/test_pdc_v2_maestro.php` → PASS completo; PHPStan en verde.**
- [ ] **Step 7: Commit** `feat(pdc-v2): retiro de insumos del maestro — activo=0, reversión del auto-match, reactivación y auditoría`

### Task 6: SPA — UI de retiro/reactivación en el catálogo

**Files:**
- Modify: `src/lib/types.ts` (`MaestroInsumo` += `activo: number; updatedAt: string | null`)
- Modify: `src/pages/MaestroInsumos.tsx`

**Interfaces:**
- Consumes: endpoints de Task 5. `apiGet`/`apiPost` de `src/lib/api.ts` (ya envían CSRF).

- [ ] **Step 1: Ampliar el tipo** `MaestroInsumo` con `activo` y `updatedAt`.
- [ ] **Step 2: UI** — estado `verRetirados`; `cargarCatalogo` añade `&incluirInactivos=1` cuando aplica; checkbox `data-testid="pdc-maestro-ver-retirados"` junto a la búsqueda; columna de acción en el grid del catálogo (`Retirar`/`Reactivar` según `activo`, click → `apiPost` a `/desactivar` o `/reactivar`, mensaje de éxito con los vínculos revertidos, recarga de cola+catálogo). Columna `Estado` visible solo con `verRetirados`.
- [ ] **Step 3: Gates del repo**: `npm run test` (28 pasan; sin tests nuevos: el cambio es de presentación y el reducer no cambia) y `npm run build`.
- [ ] **Step 4: `npm run sync`** para copiar `dist/` a `../lps-aia/public/pdc-app/`.
- [ ] **Step 5: Commits** — aquí: `feat(pdc): retiro y reactivación de insumos desde el catálogo del maestro`; en lps-aia: `chore(pdc-v2): sync bundle pdc-app (retiro de insumos)`

### Task 7: e2e + gates finales + merge

**Files:**
- Modify: `../lps-aia/tests/browser/pdc-v2-maestro.spec.mjs`

- [ ] **Step 1: Extender el e2e** tras la búsqueda del catálogo: buscar `bombeo`, click en `Retirar` de la fila `SERVICIO BOMBEO` → la fila desaparece del catálogo; activar `pdc-maestro-ver-retirados` → la fila reaparece con acción `Reactivar`; click en `Reactivar`; recargar la vista del maestro → `Cobertura: 100%` (regenerar repone el auto-match). Sin `Fatal error` en el body.
- [ ] **Step 2: Secuencia alternada** (gate de aislamiento de A2): `test_pdc_v2_maestro.php` → e2e `pdc-v2-maestro.spec.mjs --workers=1` → `test_pdc_v2_maestro.php` de nuevo. Los tres en verde.
- [ ] **Step 3: Gates amplios en lps-aia**: `test_pdc_v2_rbac_maestro.php`, `test_global_table_safety.php`, `test_global_table_reconciliation.php`, PHPStan.
- [ ] **Step 4: Merge**: re-verificar main de cada repo (trabajo ajeno en lps-aia), merge de `pdc-a2-followups` a main en ambos, borrar ramas. Actualizar `CLAUDE.md` de este repo (línea de estado: follow-ups A2 aplicados) y `progress.md`.
- [ ] **Step 5: Verificación visual de cierre**: abrir el navegador integrado en `#/ensamble/maestro` con el dev server.
