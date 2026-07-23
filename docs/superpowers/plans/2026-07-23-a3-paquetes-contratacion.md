# Fase A3: Paquetes de contratación + motor de sugerencias — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agrupar los insumos únicos del presupuesto activo en paquetes de contratación globales (un insumo → un paquete, meta 100% asignado), con un motor de sugerencias cross-proyecto de 3 capas (exacta / tokens / Agrupación SINCO) siempre confirmado por un humano.

**Architecture:** Catálogo global `general_paquetes_contratacion` + asignación por proyecto `pdc_insumo_paquete` clavada por `(project_id, descripcion_norm, unidad)` — el re-import hereda gratis y el motor NO tiene tabla propia: su memoria es la propia asignación agregada entre proyectos. Backend `PaquetesService` + `PlanComprasPaquetesController` (patrón A2); SPA pestaña nueva "Paquetes" con grilla de insumos + barra de cobertura. Spec: `docs/superpowers/specs/2026-07-23-a3-paquetes-contratacion-design.md`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker lps-aia), FastRoute, React+TS+Vite+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{"ok":true,"data":...}` | `{"ok":false,"error":{...}}` (trait `PlanComprasJsonRespuestas`, reusar). Códigos de A3: `PAQUETE_INVALIDO` 422, `NO_VERSION` 404, `FORBIDDEN` 403, `NO_PROJECT` 409, `CSRF_INVALID` 403.
- RBAC: lectura `lps.paquetes_contratacion.ver`, escritura `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`. **Ambas claves YA existen** en `RbacCatalog::permissionDefinitions()` (líneas 122-123) y están sembradas en `database/patches/003_seed_rbac_data.sql` (A, D, P con editar; R/DCV/OT/U/V solo ver) — **no se necesita patch RBAC nuevo**.
- Normalización canónica: `\App\Services\Pdc\MaestroInsumosService::normalizar()` — NO duplicar. Escape de comodines LIKE: `'%' . addcslashes($t, '\\%_') . '%'` (lección A2).
- Tipos de negociación exactos: `a_todo_costo`, `mano_obra`, `suministro`, `consumibles` (enum, DEFAULT `a_todo_costo`).
- Un insumo, un paquete: `UNIQUE (project_id, descripcion_norm(150), unidad)` en `pdc_insumo_paquete`; reasignar MUEVE (upsert), no duplica. FK a paquetes `ON DELETE RESTRICT`.
- Motor: 3 capas con confianza `alta`/`media`/`baja`; capa N solo si la N-1 no dio resultado; **siempre confirma un humano** (el motor solo pre-marca).
- Batch multi-fila en lotes de 200 (patrón `generarVinculos`); validación de arrays con elementos escalares en los POST (lección review final A2).
- Tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) sobre el MySQL real del Docker; proyectos de prueba 999901/999902; cleanup por marca (`creado_por/asignado_por = 'test-a3'`, nombres `TEST A3 %`) FK-safe (asignaciones antes que paquetes); gates `test_global_table_safety` + `test_global_table_reconciliation` en verde.
- SPA: AG Grid Community con módulos selectivos (`ClientSideRowModelModule` + `CellStyleModule`/`RowStyleModule` si se usan cellClass/rowClassRules + `ValidationModule` solo DEV — nunca `AllCommunityModule`); flags como number 1/0, no boolean; identificadores en inglés, comentarios/UI en español.
- Comandos: tests PHP `docker compose exec app php tests/...` (stack `docker compose up -d app db`, live-mounted sin rebuild); SPA `npm run test` / `npm run build`; bundle `npm run sync`; e2e `npx playwright test ... --workers=1` desde lps-aia (host).
- Ramas: `pdc-a3-paquetes` en ambos repos. Commits `feat(pdc-v2): ...` (lps-aia) / `feat(pdc): ...` (plan-de-compras).

---

## File Structure

**lps-aia (rama `pdc-a3-paquetes`, base = main tras A2.5):**
```
database/migrations/20260724_pdc_v2_paquetes_contratacion.sql  # T1: 2 tablas
src/Services/Pdc/PaquetesService.php                           # T2-T5 (se construye por etapas)
tests/test_pdc_v2_paquetes.php                                 # T2-T4: catálogo, asignación, cobertura
tests/test_pdc_v2_paquetes_motor.php                           # T5: motor 3 capas cross-proyecto
src/Controllers/Api/PlanComprasPaquetesController.php          # T6
public/index.php                                               # T6: +7 rutas
tests/browser/pdc-v2-paquetes.spec.mjs                         # T8: e2e ciclo completo
public/pdc-app/**                                              # T8: bundle regenerado
```

**plan-de-compras (rama `pdc-a3-paquetes`):**
```
src/lib/paquetesState.ts        # T7: reducer (TDD)
src/lib/paquetesState.test.ts   # T7
src/lib/types.ts                # T7: Modify (+tipos de paquetes)
src/pages/PaquetesContratacion.tsx  # T7: vista nueva
src/App.tsx                     # T7: Modify (+ruta y NavLink "Paquetes")
src/styles.css                  # T7: Modify
CLAUDE.md                       # T8: Modify (estado A3)
docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md  # T8: Modify (A3 implementada)
```

---

### Task 1: Migración — `general_paquetes_contratacion` + `pdc_insumo_paquete` (lps-aia)

**Files:**
- Create: `database/migrations/20260724_pdc_v2_paquetes_contratacion.sql`

**Interfaces:**
- Produces: las 2 tablas para T2-T5. Nota deliberada: `pdc_insumo_paquete.descripcion_norm` es **varchar(500)** (no 300 como decía el spec) para casar 1:1 con `pdc_insumo_vinculos.descripcion_norm varchar(500)` en los JOIN por igualdad — los índices siguen con prefijo 150.

- [ ] **Step 1: Crear branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git checkout main && git pull --ff-only 2>/dev/null; git checkout -b pdc-a3-paquetes
```

- [ ] **Step 2: Escribir la migración**

```sql
-- 20260724_pdc_v2_paquetes_contratacion.sql
-- PDC v2 / Fase A3: catálogo global de paquetes de contratación + asignación insumo→paquete por proyecto.
-- La asignación se clava por (project_id, descripcion_norm, unidad): el re-import hereda el paquete gratis.
-- El motor de sugerencias NO tiene tabla propia: agrega sobre pdc_insumo_paquete entre proyectos.
-- descripcion_norm es varchar(500) (igual que pdc_insumo_vinculos) para JOIN por igualdad; índices con prefijo 150.

CREATE TABLE IF NOT EXISTS `general_paquetes_contratacion` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `nombre_norm` varchar(200) NOT NULL,
  `tipo_negociacion` enum('a_todo_costo','mano_obra','suministro','consumibles') NOT NULL DEFAULT 'a_todo_costo',
  `activo` tinyint NOT NULL DEFAULT 1,
  `creado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gpc_nombre_norm` (`nombre_norm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_insumo_paquete` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `paquete_id` bigint NOT NULL,
  `asignado_por` varchar(100) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pip_insumo` (`project_id`, `descripcion_norm`(150), `unidad`),
  KEY `idx_pip_paquete` (`project_id`, `paquete_id`),
  KEY `idx_pip_norm` (`descripcion_norm`(150), `unidad`),
  CONSTRAINT `fk_pip_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 3: Aplicar y verificar**

```bash
docker compose up -d app db
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/20260724_pdc_v2_paquetes_contratacion.sql
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SHOW CREATE TABLE general_paquetes_contratacion\G SHOW CREATE TABLE pdc_insumo_paquete\G"'
```

Expected: ambas tablas con sus UNIQUEs, el enum de 4 tipos, la FK RESTRICT y los índices `idx_pip_paquete`/`idx_pip_norm`.

- [ ] **Step 4: Gates**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Expected: exit 0 ambos.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260724_pdc_v2_paquetes_contratacion.sql
git commit -m "feat(pdc-v2): tablas de paquetes de contratacion (catalogo global + asignacion por proyecto)"
```

---

### Task 2: PaquetesService — catálogo + crearPaquete (lps-aia, TDD)

**Files:**
- Create: `src/Services/Pdc/PaquetesService.php`
- Test: `tests/test_pdc_v2_paquetes.php` (se inicia aquí; T3/T4 le añaden secciones)

**Interfaces:**
- Consumes: `\Database`, `MaestroInsumosService::normalizar()`.
- Produces (T6 las consume):
  - `const TIPOS = ['a_todo_costo', 'mano_obra', 'suministro', 'consumibles'];`
  - `__construct(\Database $db)`
  - `catalogo(?string $busqueda = null): array` — lista de paquetes activos `[{id, nombre, tipoNegociacion, insumosGlobal}]` (insumosGlobal = asignaciones en TODOS los proyectos), orden alfabético, LIKE escapado.
  - `crearPaquete(string $nombre, string $tipo, string $usuario): array` — `{ok:true, paquete:{id,nombre,tipoNegociacion,existente:0|1}}`; duplicado por `nombre_norm` → devuelve el existente con `existente:1` (reactivándolo si estaba inactivo); tipo inválido o nombre vacío → `{ok:false, code:'PAQUETE_INVALIDO'}`.

- [ ] **Step 1: Escribir el test (falla)** — `tests/test_pdc_v2_paquetes.php`

```php
<?php
// tests/test_pdc_v2_paquetes.php — PaquetesService sobre MySQL real (proyectos 999901/999902).

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P1 = 999901; $P2 = 999902;
// Cleanup FK-safe por marca de test: asignaciones → paquetes → vínculos → versiones.
$limpiar = static function () use ($db, $P1, $P2): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a3'");
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P1, $P2]);
};
$limpiar();

// Fixture: versión activa + insumos únicos consolidados (vínculos) para P1.
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-A3', 'test-a3.xlsx', REPEAT('a', 64), 2, 4, 1000, 1, 'test-a3', NOW())",
    [$P1],
);
$vid1 = (int) $db->lastInsertId();
$insumosP1 = [
    ['PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 100, 2500000],
    ['PISO PORCELANATO 60X60', 'M2', 'MAT-ACABADOS', 50, 2400000],
    ['ACERO DE REFUERZO 60000PSI', 'KG', 'MAT-ACEROS', 800, 3360000],
    ['AYUDANTE DE OBRA', 'HC', 'MANO DE OBRA', 40, 380000],
];
foreach ($insumosP1 as $i) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'pendiente')",
        [$P1, $vid1, $i[0], $i[1], $i[0], $i[2], $i[3], $i[4]],
    );
}

echo "=== PDC v2: paquetes de contratacion ===\n";
$svc = new PaquetesService($db);

// --- crearPaquete ---
$r = $svc->crearPaquete('TEST A3 Pisos', 'suministro', 'test-a3');
$assert($r['ok'] === true && $r['paquete']['id'] > 0 && $r['paquete']['existente'] === 0, 'Crear paquete nuevo.');
$pisosId = (int) $r['paquete']['id'];

$dup = $svc->crearPaquete('  test a3 PISOS ', 'mano_obra', 'test-a3');
$assert($dup['ok'] === true && (int) $dup['paquete']['id'] === $pisosId && $dup['paquete']['existente'] === 1, 'Duplicado por nombre_norm devuelve el existente (no falla, no cambia tipo).');
$tipo = $db->query('SELECT tipo_negociacion FROM general_paquetes_contratacion WHERE id = ?', [$pisosId])->fetchColumn();
$assert($tipo === 'suministro', 'El duplicado no pisa el tipo del existente.');

$bad = $svc->crearPaquete('TEST A3 X', 'tipo_falso', 'test-a3');
$assert($bad['ok'] === false && $bad['code'] === 'PAQUETE_INVALIDO', 'Tipo inválido rechazado.');
$vacio = $svc->crearPaquete('   ', 'suministro', 'test-a3');
$assert($vacio['ok'] === false && $vacio['code'] === 'PAQUETE_INVALIDO', 'Nombre vacío rechazado.');

// --- catalogo ---
$svc->crearPaquete('TEST A3 Aceros', 'a_todo_costo', 'test-a3');
$cat = $svc->catalogo('TEST A3');
$assert(count($cat) === 2, 'Catálogo filtrado por búsqueda: 2 paquetes.');
$assert($cat[0]['nombre'] === 'TEST A3 Aceros', 'Orden alfabético.');
$assert($cat[0]['insumosGlobal'] === 0, 'Sin asignaciones aún: insumosGlobal = 0.');
$catEsc = $svc->catalogo('TEST A3 100%');
$assert($catEsc === [], 'Comodines LIKE escapados en la búsqueda.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_paquetes.php
```

Expected: FAIL — `Class "App\Services\Pdc\PaquetesService" not found`.

- [ ] **Step 3: Implementar el servicio (primera etapa)**

```php
<?php

namespace App\Services\Pdc;

/**
 * Paquetes de contratación (PDC v2 / Fase A3): catálogo global reutilizable +
 * asignación insumo→paquete por proyecto clavada por (norma, unidad) — el
 * re-import hereda gratis. El motor de sugerencias agrega sobre la propia
 * asignación entre proyectos (sin tabla nueva), siempre con confirmación humana.
 */
final class PaquetesService
{
    public const TIPOS = ['a_todo_costo', 'mano_obra', 'suministro', 'consumibles'];

    public function __construct(private readonly \Database $db)
    {
    }

    /** Resuelve la versión (activa por defecto) del proyecto, o null. */
    private function versionDe(int $projectId, ?int $versionId): ?array
    {
        $sql = $versionId === null
            ? 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1'
            : 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?';
        $params = $versionId === null ? [$projectId] : [$projectId, $versionId];
        $row = $this->db->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** Paquetes globales activos con su nº de asignaciones en todos los proyectos. */
    public function catalogo(?string $busqueda = null): array
    {
        $where = 'p.activo = 1';
        $params = [];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $where .= ' AND p.nombre_norm LIKE ?';
            $params[] = '%' . addcslashes(MaestroInsumosService::normalizar($busqueda), '\\%_') . '%';
        }
        $rows = $this->db->query(
            "SELECT p.id, p.nombre, p.tipo_negociacion, COUNT(a.id) AS insumos_global
             FROM general_paquetes_contratacion p
             LEFT JOIN pdc_insumo_paquete a ON a.paquete_id = p.id
             WHERE {$where}
             GROUP BY p.id, p.nombre, p.tipo_negociacion
             ORDER BY p.nombre ASC",
            $params,
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'nombre' => $r['nombre'],
            'tipoNegociacion' => $r['tipo_negociacion'],
            'insumosGlobal' => (int) $r['insumos_global'],
        ], $rows);
    }

    /** Crea un paquete global; duplicado por nombre_norm devuelve el existente (reactivado si estaba inactivo). */
    public function crearPaquete(string $nombre, string $tipo, string $usuario): array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($tipo, self::TIPOS, true)) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $norm = mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200);

        $existente = $this->db->query(
            'SELECT id, nombre, tipo_negociacion, activo FROM general_paquetes_contratacion WHERE nombre_norm = ?',
            [$norm],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($existente !== false) {
            if ((int) $existente['activo'] === 0) {
                $this->db->query(
                    'UPDATE general_paquetes_contratacion SET activo = 1, updated_at = NOW() WHERE id = ?',
                    [(int) $existente['id']],
                );
            }
            return ['ok' => true, 'paquete' => [
                'id' => (int) $existente['id'],
                'nombre' => $existente['nombre'],
                'tipoNegociacion' => $existente['tipo_negociacion'],
                'existente' => 1,
            ]];
        }

        try {
            $this->db->query(
                'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, activo, creado_por, created_at)
                 VALUES (?, ?, ?, 1, ?, NOW())',
                [mb_substr($nombre, 0, 200), $norm, $tipo, $usuario],
            );
        } catch (\PDOException $e) {
            // Carrera: otro proceso lo creó entre el SELECT y el INSERT (errno 1062) → devolver el existente.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            $row = $this->db->query(
                'SELECT id, nombre, tipo_negociacion FROM general_paquetes_contratacion WHERE nombre_norm = ?',
                [$norm],
            )->fetch(\PDO::FETCH_ASSOC);
            return ['ok' => true, 'paquete' => [
                'id' => (int) $row['id'], 'nombre' => $row['nombre'],
                'tipoNegociacion' => $row['tipo_negociacion'], 'existente' => 1,
            ]];
        }
        return ['ok' => true, 'paquete' => [
            'id' => (int) $this->db->lastInsertId(),
            'nombre' => mb_substr($nombre, 0, 200),
            'tipoNegociacion' => $tipo,
            'existente' => 0,
        ]];
    }
}
```

- [ ] **Step 4: Correr y ver que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_paquetes.php
```

Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 5: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PaquetesService.php --memory-limit=1G
git add src/Services/Pdc/PaquetesService.php tests/test_pdc_v2_paquetes.php
git commit -m "feat(pdc-v2): catalogo global de paquetes de contratacion (crear con dedupe por nombre_norm)"
```

---

### Task 3: PaquetesService — asignar/desasignar masivo (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PaquetesService.php` (añadir 2 métodos)
- Modify: `tests/test_pdc_v2_paquetes.php` (añadir sección, antes del `echo` final)

**Interfaces:**
- Consumes: tablas T1, `crearPaquete` (T2).
- Produces:
  - `asignar(int $projectId, array $insumos, int $paqueteId, string $usuario): array` — `$insumos` = lista de `{descripcionNorm:string, unidad:string}`; valida paquete existente+activo (`PAQUETE_INVALIDO`) y elementos bien formados (descarta no-válidos); upsert por lotes de 200 (`ON DUPLICATE KEY UPDATE paquete_id=VALUES(...)` → reasignar MUEVE); retorna `{ok:true, asignados:int}`.
  - `desasignar(int $projectId, array $insumos): array` — DELETE por tuplas `(descripcion_norm, unidad) IN (...)` en lotes de 200; `{ok:true, desasignados:int}`.

- [ ] **Step 1: Añadir la sección al test (falla)** — insertar antes del `echo $failures === [] ...` final:

```php
// --- asignar / desasignar ---
$aceros = $svc->crearPaquete('TEST A3 Aceros', 'a_todo_costo', 'test-a3'); // ya existe → existente:1
$acerosId = (int) $aceros['paquete']['id'];

$a1 = $svc->asignar($P1, [
    ['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2'],
    ['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2'],
], $pisosId, 'test-a3');
$assert($a1['ok'] === true && $a1['asignados'] === 2, 'Asignación masiva: 2 insumos a Pisos.');

// Reasignar mueve (no duplica): el porcelanato pasa a Aceros.
$a2 = $svc->asignar($P1, [['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2']], $acerosId, 'test-a3');
$assert($a2['ok'] === true, 'Reasignación aceptada.');
$filas = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [$P1])->fetchColumn();
$assert($filas === 2, 'Un insumo, un paquete: reasignar no duplica filas.');
$pq = $db->query("SELECT paquete_id FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = 'PISO PORCELANATO 60X60' AND unidad = 'M2'", [$P1])->fetchColumn();
$assert((int) $pq === $acerosId, 'El insumo quedó en el paquete nuevo.');

// Paquete inexistente / inactivo → PAQUETE_INVALIDO.
$aX = $svc->asignar($P1, [['descripcionNorm' => 'AYUDANTE DE OBRA', 'unidad' => 'HC']], 99999999, 'test-a3');
$assert($aX['ok'] === false && $aX['code'] === 'PAQUETE_INVALIDO', 'Paquete inexistente rechazado.');

// Elementos malformados se descartan sin explotar (lección A2: escalares).
$aM = $svc->asignar($P1, [['descripcionNorm' => ['no' => 'array'], 'unidad' => 'M2'], 'basura'], $pisosId, 'test-a3');
$assert($aM['ok'] === true && $aM['asignados'] === 0, 'Elementos malformados descartados (0 asignados).');

// Aislamiento por proyecto: P2 no ve las asignaciones de P1.
$filasP2 = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [$P2])->fetchColumn();
$assert($filasP2 === 0, 'Aislamiento por project_id.');

// desasignar
$d1 = $svc->desasignar($P1, [['descripcionNorm' => 'PISO PORCELANATO 60X60', 'unidad' => 'M2']]);
$assert($d1['ok'] === true && $d1['desasignados'] === 1, 'Desasignar elimina la fila.');
$filas2 = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [$P1])->fetchColumn();
$assert($filas2 === 1, 'Queda 1 asignación.');
```

- [ ] **Step 2: Correr y ver que falla**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes.php`
Expected: FAIL — `Call to undefined method ... asignar()`.

- [ ] **Step 3: Implementar** — añadir a `PaquetesService`:

```php
    /** Filtra y normaliza la lista de insumos {descripcionNorm, unidad}; descarta elementos malformados. */
    private static function insumosValidos(array $insumos): array
    {
        $out = [];
        foreach ($insumos as $i) {
            if (!is_array($i) || !is_string($i['descripcionNorm'] ?? null) || !is_string($i['unidad'] ?? null)) {
                continue;
            }
            $norm = trim($i['descripcionNorm']);
            $unidad = trim($i['unidad']);
            if ($norm === '' || $unidad === '') {
                continue;
            }
            $out[] = ['norm' => mb_substr($norm, 0, 500), 'unidad' => mb_substr($unidad, 0, 20)];
        }
        return $out;
    }

    /** Asignación masiva insumo→paquete (upsert: reasignar mueve, no duplica). */
    public function asignar(int $projectId, array $insumos, int $paqueteId, string $usuario): array
    {
        $paquete = $this->db->query(
            'SELECT id FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetchColumn();
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $validos = self::insumosValidos($insumos);
        // Lotes multi-fila (patrón generarVinculos): evita un round-trip por insumo.
        foreach (array_chunk($validos, 200) as $lote) {
            $valores = implode(', ', array_fill(0, count($lote), '(?, ?, ?, ?, ?, NOW())'));
            $params = [];
            foreach ($lote as $u) {
                array_push($params, $projectId, $u['norm'], $u['unidad'], $paqueteId, $usuario);
            }
            $this->db->query(
                "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, asignado_por, updated_at)
                 VALUES {$valores}
                 ON DUPLICATE KEY UPDATE paquete_id = VALUES(paquete_id), asignado_por = VALUES(asignado_por), updated_at = NOW()",
                $params,
            );
        }
        return ['ok' => true, 'asignados' => count($validos)];
    }

    /** Quita asignaciones (el insumo vuelve a "sin asignar"). */
    public function desasignar(int $projectId, array $insumos): array
    {
        $validos = self::insumosValidos($insumos);
        $total = 0;
        foreach (array_chunk($validos, 200) as $lote) {
            $tuplas = implode(', ', array_fill(0, count($lote), '(?, ?)'));
            $params = [$projectId];
            foreach ($lote as $u) {
                array_push($params, $u['norm'], $u['unidad']);
            }
            $stmt = $this->db->query(
                "DELETE FROM pdc_insumo_paquete WHERE project_id = ? AND (descripcion_norm, unidad) IN ({$tuplas})",
                $params,
            );
            $total += $stmt->rowCount();
        }
        return ['ok' => true, 'desasignados' => $total];
    }
```

- [ ] **Step 4: Correr y ver que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes.php`
Expected: todos PASS (T2 + T3), exit 0.

- [ ] **Step 5: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PaquetesService.php --memory-limit=1G
git add src/Services/Pdc/PaquetesService.php tests/test_pdc_v2_paquetes.php
git commit -m "feat(pdc-v2): asignacion masiva insumo-paquete (upsert por lotes, un insumo un paquete)"
```

---

### Task 4: PaquetesService — insumosDeVersion + resumen (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PaquetesService.php`
- Modify: `tests/test_pdc_v2_paquetes.php`

**Interfaces:**
- Consumes: vínculos de A2 (`pdc_insumo_vinculos` de la versión activa), asignaciones (T3), `general_maestro_insumos.agrupacion` (A2.5).
- Produces:
  - `insumosDeVersion(int $projectId, string $filtro = 'todos', ?int $versionId = null): ?array` — `null` si no hay versión; si hay: `{version:{id,label}, insumos:[{descripcionNorm, unidad, descripcion, tipoInsumo, agrupacion, cantidadTotal, valorTotal, paqueteId, paqueteNombre}]}`; `filtro` ∈ `sin_asignar|asignados|todos`; agrupación viene del maestro vía `maestro_id` del vínculo (NULL si no vinculado); orden `valor_total DESC`.
  - `resumen(int $projectId, ?int $versionId = null): ?array` — `{version:{id,label}, total, asignados, cobertura(float 0-100, 1 decimal), porPaquete:[{paqueteId, nombre, tipoNegociacion, insumos, subtotal}]}`.

- [ ] **Step 1: Añadir la sección al test (falla)** — antes del `echo` final (estado al llegar: PISO CERAMICO→Pisos; los otros 3 sin asignar):

```php
// --- insumosDeVersion + resumen ---
$iv = $svc->insumosDeVersion($P1, 'todos');
$assert($iv !== null && count($iv['insumos']) === 4, 'insumosDeVersion: 4 únicos de la versión activa.');
$assert((int) $iv['version']['id'] === $vid1, 'Versión activa resuelta.');
$ceramico = array_values(array_filter($iv['insumos'], fn ($i) => $i['descripcionNorm'] === 'PISO CERAMICO 30X30'))[0];
$assert((int) $ceramico['paqueteId'] === $pisosId && $ceramico['paqueteNombre'] === 'TEST A3 Pisos', 'Insumo asignado trae su paquete.');
$sinA = $svc->insumosDeVersion($P1, 'sin_asignar');
$assert(count($sinA['insumos']) === 3, 'Filtro sin_asignar: 3.');
$conA = $svc->insumosDeVersion($P1, 'asignados');
$assert(count($conA['insumos']) === 1, 'Filtro asignados: 1.');
$assert($svc->insumosDeVersion($P2, 'todos') === null, 'Proyecto sin presupuesto → null (NO_VERSION).');

$res = $svc->resumen($P1);
$assert($res['total'] === 4 && $res['asignados'] === 1, 'Resumen: 1 de 4 asignados.');
$assert(abs($res['cobertura'] - 25.0) < 0.01, 'Cobertura 25%.');
$assert(count($res['porPaquete']) === 1 && $res['porPaquete'][0]['insumos'] === 1, 'porPaquete: Pisos con 1 insumo.');
$assert(abs((float) $res['porPaquete'][0]['subtotal'] - 2500000) < 0.01, 'Subtotal del paquete = valor consolidado del insumo.');

// Herencia en re-import: nueva versión activa con el mismo insumo → conserva su paquete sin re-asignar.
$db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ?', [$P1]);
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-A3-2', 'test-a3b.xlsx', REPEAT('b', 64), 1, 2, 500, 1, 'test-a3', NOW())",
    [$P1],
);
$vid2 = (int) $db->lastInsertId();
foreach ([['PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 60, 1500000], ['INSUMO NUEVO A3', 'UN', 'OTROS', 5, 50000]] as $i) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'pendiente')",
        [$P1, $vid2, $i[0], $i[1], $i[0], $i[2], $i[3], $i[4]],
    );
}
$iv2 = $svc->insumosDeVersion($P1, 'todos');
$assert(count($iv2['insumos']) === 2, 'Nueva versión: 2 insumos.');
$cer2 = array_values(array_filter($iv2['insumos'], fn ($i) => $i['descripcionNorm'] === 'PISO CERAMICO 30X30'))[0];
$assert((int) $cer2['paqueteId'] === $pisosId, 'HERENCIA: el insumo reaparecido conserva su paquete en el re-import.');
$nuevo = array_values(array_filter($iv2['insumos'], fn ($i) => $i['descripcionNorm'] === 'INSUMO NUEVO A3'))[0];
$assert($nuevo['paqueteId'] === null, 'Insumo nuevo queda sin asignar.');
```

- [ ] **Step 2: Correr y ver que falla**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes.php`
Expected: FAIL — `Call to undefined method ... insumosDeVersion()`.

- [ ] **Step 3: Implementar** — añadir a `PaquetesService`:

```php
    /** Insumos únicos de la versión (activa por defecto) con su asignación y agrupación SINCO. */
    public function insumosDeVersion(int $projectId, string $filtro = 'todos', ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $extra = match ($filtro) {
            'sin_asignar' => ' AND a.id IS NULL',
            'asignados' => ' AND a.id IS NOT NULL',
            default => '',
        };
        $rows = $this->db->query(
            "SELECT v.descripcion_norm, v.unidad, v.descripcion_original, v.tipo_insumo,
                    v.cantidad_total, v.valor_total,
                    m.agrupacion,
                    a.paquete_id, p.nombre AS paquete_nombre
             FROM pdc_insumo_vinculos v
             LEFT JOIN general_maestro_insumos m ON m.id = v.maestro_id
             LEFT JOIN pdc_insumo_paquete a
                    ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             LEFT JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE v.project_id = ? AND v.version_id = ?{$extra}
             ORDER BY v.valor_total DESC",
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return [
            'version' => ['id' => $vid, 'label' => $version['version_label']],
            'insumos' => array_map(static fn (array $r): array => [
                'descripcionNorm' => $r['descripcion_norm'],
                'unidad' => $r['unidad'],
                'descripcion' => $r['descripcion_original'],
                'tipoInsumo' => $r['tipo_insumo'],
                'agrupacion' => $r['agrupacion'],
                'cantidadTotal' => (float) $r['cantidad_total'],
                'valorTotal' => (float) $r['valor_total'],
                'paqueteId' => $r['paquete_id'] === null ? null : (int) $r['paquete_id'],
                'paqueteNombre' => $r['paquete_nombre'],
            ], $rows),
        ];
    }

    /** Cobertura de la meta 100% + subtotales por paquete sobre la versión (activa por defecto). */
    public function resumen(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $tot = $this->db->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) AS asignados
             FROM pdc_insumo_vinculos v
             LEFT JOIN pdc_insumo_paquete a
                    ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             WHERE v.project_id = ? AND v.version_id = ?',
            [$projectId, $vid],
        )->fetch(\PDO::FETCH_ASSOC);
        $total = (int) $tot['total'];
        $asignados = (int) $tot['asignados'];
        $porPaquete = $this->db->query(
            'SELECT p.id, p.nombre, p.tipo_negociacion, COUNT(*) AS insumos, SUM(v.valor_total) AS subtotal
             FROM pdc_insumo_vinculos v
             JOIN pdc_insumo_paquete a
                   ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE v.project_id = ? AND v.version_id = ?
             GROUP BY p.id, p.nombre, p.tipo_negociacion
             ORDER BY subtotal DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return [
            'version' => ['id' => $vid, 'label' => $version['version_label']],
            'total' => $total,
            'asignados' => $asignados,
            'cobertura' => $total === 0 ? 0.0 : round($asignados * 100 / $total, 1),
            'porPaquete' => array_map(static fn (array $r): array => [
                'paqueteId' => (int) $r['id'],
                'nombre' => $r['nombre'],
                'tipoNegociacion' => $r['tipo_negociacion'],
                'insumos' => (int) $r['insumos'],
                'subtotal' => (float) $r['subtotal'],
            ], $porPaquete),
        ];
    }
```

- [ ] **Step 4: Correr y ver que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes.php`
Expected: todos PASS (T2+T3+T4), exit 0.

- [ ] **Step 5: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PaquetesService.php --memory-limit=1G
git add src/Services/Pdc/PaquetesService.php tests/test_pdc_v2_paquetes.php
git commit -m "feat(pdc-v2): insumos por version con asignacion heredable + resumen de cobertura 100%"
```

---

### Task 5: Motor de sugerencias — 3 capas cross-proyecto (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PaquetesService.php`
- Test: `tests/test_pdc_v2_paquetes_motor.php` (archivo nuevo)

**Interfaces:**
- Consumes: asignaciones multi-proyecto (`pdc_insumo_paquete`), `general_maestro_insumos` (agrupacion, por norma+unidad).
- Produces:
  - `sugerencias(int $projectId, ?int $versionId = null): ?array` — `null` sin versión; si hay: `{version:{id,label}, sugerencias:[{descripcionNorm, unidad, paqueteId, paqueteNombre, capa:'exacta'|'tokens'|'agrupacion', confianza:'alta'|'media'|'baja', evidencia:string}]}` SOLO para insumos sin asignar. Capa N solo si la N-1 no dio. Capa exacta EXCLUYE el propio proyecto; capa agrupación puede incluirlo. Solo sugiere paquetes `activo=1`.

- [ ] **Step 1: Escribir el test (falla)** — `tests/test_pdc_v2_paquetes_motor.php`

```php
<?php
// tests/test_pdc_v2_paquetes_motor.php — motor de 3 capas sobre MySQL real.
// Escenario: P2 (999902) tiene historial de asignaciones; P1 (999901) recibe sugerencias.

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P1 = 999901; $P2 = 999902;
$limpiar = static function () use ($db, $P1, $P2): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-a3'");
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (?, ?)', [$P1, $P2]);
    $db->query("DELETE FROM general_maestro_insumos WHERE creado_por = 'test-a3'");
};
$limpiar();

echo "=== PDC v2: motor de sugerencias (3 capas) ===\n";
$svc = new PaquetesService($db);

// Paquetes globales.
$pisosId = (int) $svc->crearPaquete('TEST A3 Pisos', 'suministro', 'test-a3')['paquete']['id'];
$acabadosId = (int) $svc->crearPaquete('TEST A3 Acabados', 'a_todo_costo', 'test-a3')['paquete']['id'];

// Historial en P2 (la "memoria" del motor): dos asignaciones a Pisos, una a Acabados.
$db->query(
    "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, asignado_por, updated_at) VALUES
     (?, 'PISO CERAMICO 30X30', 'M2', ?, 'test-a3', NOW()),
     (?, 'PISO GRES 40X40', 'M2', ?, 'test-a3', NOW()),
     (?, 'ESTUCO PLASTICO', 'M2', ?, 'test-a3', NOW())",
    [$P2, $pisosId, $P2, $pisosId, $P2, $acabadosId],
);

// Maestro: insumo de P1 con agrupación (para la capa 3) — y un asignado de P2 con la misma agrupación.
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, activo, creado_por, created_at) VALUES
     ('Pintura vinilo tipo 1', 'PINTURA VINILO TIPO 1', 'GL', 'MAT-ACABADOS', 'TEST-A3-ACABADOS', 1, 'test-a3', NOW()),
     ('Estuco plastico', 'ESTUCO PLASTICO', 'M2', 'MAT-ACABADOS', 'TEST-A3-ACABADOS', 1, 'test-a3', NOW())",
);
$maestroPinturaId = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE descripcion_norm = 'PINTURA VINILO TIPO 1' AND unidad = 'GL'")->fetchColumn();

// Versión activa de P1 con 4 insumos sin asignar:
//  1. PISO CERAMICO 30X30 / M2  → capa exacta (existe idéntico en P2 → Pisos)
//  2. PISO PORCELANATO 60X60 / M2 → capa tokens ("PISO" comparte token con los de P2 → Pisos)
//  3. PINTURA VINILO TIPO 1 / GL → capa agrupación (maestro agrupación TEST-A3-ACABADOS → Acabados vía ESTUCO)
//  4. ZZZZ INSUMO INEDITO / UN   → sin sugerencia
$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-MOTOR', 'motor.xlsx', REPEAT('c', 64), 1, 4, 100, 1, 'test-a3', NOW())",
    [$P1],
);
$vid = (int) $db->lastInsertId();
$fixtures = [
    ['PISO CERAMICO 30X30', 'M2', null],
    ['PISO PORCELANATO 60X60', 'M2', null],
    ['PINTURA VINILO TIPO 1', 'GL', $maestroPinturaId],
    ['ZZZZ INSUMO INEDITO', 'UN', null],
];
foreach ($fixtures as $f) {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, maestro_id, estado)
         VALUES (?, ?, ?, ?, ?, 'X', 1, 100, 1, ?, 'pendiente')",
        [$P1, $vid, $f[0], $f[1], $f[0], $f[2]],
    );
}

$r = $svc->sugerencias($P1);
$assert($r !== null, 'Con versión activa hay respuesta.');
$porNorm = [];
foreach ($r['sugerencias'] as $s) { $porNorm[$s['descripcionNorm']] = $s; }

$s1 = $porNorm['PISO CERAMICO 30X30'] ?? null;
$assert($s1 !== null && $s1['capa'] === 'exacta' && $s1['confianza'] === 'alta' && (int) $s1['paqueteId'] === $pisosId, 'Capa 1 exacta: mismo insumo en P2 → Pisos (alta).');
$assert($s1 !== null && str_contains($s1['evidencia'], '1'), 'Evidencia exacta menciona el nº de proyectos.');

$s2 = $porNorm['PISO PORCELANATO 60X60'] ?? null;
$assert($s2 !== null && $s2['capa'] === 'tokens' && $s2['confianza'] === 'media' && (int) $s2['paqueteId'] === $pisosId, 'Capa 2 tokens: "PISO" coincide → Pisos (media).');

$s3 = $porNorm['PINTURA VINILO TIPO 1'] ?? null;
$assert($s3 !== null && $s3['capa'] === 'agrupacion' && $s3['confianza'] === 'baja' && (int) $s3['paqueteId'] === $acabadosId, 'Capa 3 agrupación: TEST-A3-ACABADOS → Acabados (baja).');

$assert(!isset($porNorm['ZZZZ INSUMO INEDITO']), 'Insumo inédito: sin sugerencia (no se inventa).');

// La capa exacta NO usa el propio proyecto: asignar en P1 y pedir de nuevo no debe auto-sugerirse.
$svc->asignar($P1, [['descripcionNorm' => 'PISO CERAMICO 30X30', 'unidad' => 'M2']], $pisosId, 'test-a3');
$r2 = $svc->sugerencias($P1);
$normsR2 = array_column($r2['sugerencias'], 'descripcionNorm');
$assert(!in_array('PISO CERAMICO 30X30', $normsR2, true), 'Insumo ya asignado sale de las sugerencias.');

// Comodines en tokens no rompen (insumo con % en la descripción).
$db->query(
    "INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, 'SOLUCION 50%_ ESPECIAL', 'LT', 'Solucion 50%_ especial', 'X', 1, 10, 1, 'pendiente')",
    [$P1, $vid],
);
$r3 = $svc->sugerencias($P1); // no debe lanzar ni sugerir por el comodín
$assert($r3 !== null, 'Tokens con % y _ escapados: la consulta no explota.');

$assert($svc->sugerencias($P2) === null, 'P2 sin presupuesto → null.');

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes_motor.php`
Expected: FAIL — `Call to undefined method ... sugerencias()`.

- [ ] **Step 3: Implementar** — añadir a `PaquetesService`:

```php
    /**
     * Motor de sugerencias para los insumos SIN asignar de la versión (activa por defecto).
     * 3 capas en cascada (la N solo si la N-1 no dio): exacta (alta) → tokens (media) → agrupación (baja).
     * Sin tabla propia: la memoria es pdc_insumo_paquete agregada entre proyectos. Nada se aplica
     * sin confirmación humana (esto solo PRE-marca).
     */
    public function sugerencias(int $projectId, ?int $versionId = null): ?array
    {
        $sin = $this->insumosDeVersion($projectId, 'sin_asignar', $versionId);
        if ($sin === null) {
            return null;
        }
        $sugerencias = [];
        foreach ($sin['insumos'] as $insumo) {
            $s = $this->sugerirExacta($projectId, $insumo)
                ?? $this->sugerirPorTokens($projectId, $insumo)
                ?? $this->sugerirPorAgrupacion($insumo);
            if ($s !== null) {
                $sugerencias[] = array_merge(
                    ['descripcionNorm' => $insumo['descripcionNorm'], 'unidad' => $insumo['unidad']],
                    $s,
                );
            }
        }
        return ['version' => $sin['version'], 'sugerencias' => $sugerencias];
    }

    /** Capa 1: mismo (norma, unidad) asignado en OTROS proyectos. Consenso = más proyectos. */
    private function sugerirExacta(int $projectId, array $insumo): ?array
    {
        $row = $this->db->query(
            'SELECT a.paquete_id, p.nombre, COUNT(DISTINCT a.project_id) AS proyectos
             FROM pdc_insumo_paquete a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE a.descripcion_norm = ? AND a.unidad = ? AND a.project_id <> ?
             GROUP BY a.paquete_id, p.nombre
             ORDER BY proyectos DESC, p.nombre ASC
             LIMIT 1',
            [$insumo['descripcionNorm'], $insumo['unidad'], $projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'exacta',
            'confianza' => 'alta',
            'evidencia' => "Mismo insumo asignado en {$row['proyectos']} proyecto(s).",
        ];
    }

    /** Capa 2: similitud por tokens (>=4 chars, comodines escapados) contra asignaciones de otros proyectos. */
    private function sugerirPorTokens(int $projectId, array $insumo): ?array
    {
        $tokens = array_values(array_filter(
            explode(' ', $insumo['descripcionNorm']),
            static fn ($t) => mb_strlen($t) >= 4,
        ));
        if ($tokens === []) {
            return null;
        }
        $condiciones = implode(' + ', array_fill(0, count($tokens), '(a.descripcion_norm LIKE ?)'));
        $params = array_map(static fn ($t) => '%' . addcslashes($t, '\\%_') . '%', $tokens);
        $params[] = $projectId;
        $row = $this->db->query(
            "SELECT a.paquete_id, p.nombre,
                    SUM({$condiciones}) AS score, COUNT(DISTINCT a.project_id) AS proyectos
             FROM pdc_insumo_paquete a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE a.project_id <> ?
             GROUP BY a.paquete_id, p.nombre
             HAVING score > 0
             ORDER BY score DESC, proyectos DESC, p.nombre ASC
             LIMIT 1",
            $params,
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'tokens',
            'confianza' => 'media',
            'evidencia' => 'Insumos similares asignados a este paquete en otros proyectos.',
        ];
    }

    /** Capa 3 (respaldo): paquete más frecuente entre insumos ya asignados de la misma agrupación SINCO. */
    private function sugerirPorAgrupacion(array $insumo): ?array
    {
        if (($insumo['agrupacion'] ?? null) === null || $insumo['agrupacion'] === '') {
            return null;
        }
        $row = $this->db->query(
            'SELECT a.paquete_id, p.nombre, COUNT(*) AS usos
             FROM pdc_insumo_paquete a
             JOIN general_maestro_insumos m
                   ON m.descripcion_norm = a.descripcion_norm AND m.unidad = a.unidad
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE m.agrupacion = ?
             GROUP BY a.paquete_id, p.nombre
             ORDER BY usos DESC, p.nombre ASC
             LIMIT 1',
            [$insumo['agrupacion']],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'agrupacion',
            'confianza' => 'baja',
            'evidencia' => "Agrupación «{$insumo['agrupacion']}» suele ir a este paquete.",
        ];
    }
```

- [ ] **Step 4: Correr y ver que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_paquetes_motor.php`
Expected: todos PASS, exit 0. Regresión: `docker compose exec app php tests/test_pdc_v2_paquetes.php` sigue verde.

- [ ] **Step 5: PHPStan + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PaquetesService.php --memory-limit=1G
git add src/Services/Pdc/PaquetesService.php tests/test_pdc_v2_paquetes_motor.php
git commit -m "feat(pdc-v2): motor de sugerencias de paquetes en 3 capas (exacta/tokens/agrupacion) cross-proyecto"
```

---

### Task 6: Controller + rutas de paquetes (lps-aia)

**Files:**
- Create: `src/Controllers/Api/PlanComprasPaquetesController.php`
- Modify: `public/index.php` (7 rutas tras el bloque del import del maestro)

**Interfaces:**
- Consumes: `PaquetesService` completo, trait, `RbacService`, `CsrfTokenManager`.
- Produces (contrato HTTP para T7):
  - `GET /plan-compras/api/paquetes?busqueda=` → `{paquetes:[...]}` (lectura)
  - `GET /plan-compras/api/paquetes/insumos?filtro=&versionId=` → insumosDeVersion | `NO_VERSION` 404
  - `GET /plan-compras/api/paquetes/sugerencias?versionId=` → sugerencias | `NO_VERSION` 404
  - `GET /plan-compras/api/paquetes/resumen?versionId=` → resumen | `NO_VERSION` 404
  - `POST /plan-compras/api/paquetes` `{nombre, tipoNegociacion}` → paquete | `PAQUETE_INVALIDO` 422 (escritura+CSRF)
  - `POST /plan-compras/api/paquetes/asignar` `{insumos:[{descripcionNorm,unidad}], paqueteId}` → `{asignados}` | `PAQUETE_INVALIDO` 422
  - `POST /plan-compras/api/paquetes/desasignar` `{insumos:[...]}` → `{desasignados}`

- [ ] **Step 1: Rutas en `public/index.php`** — tras `POST /plan-compras/api/maestro/importar/confirmar`, antes de `// Api/PDC Plantillas`:

```php
// Api/Plan de Compras v2 — paquetes de contratación (A3)
$router->get('/plan-compras/api/paquetes', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'catalogo']);
$router->get('/plan-compras/api/paquetes/insumos', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'insumos']);
$router->get('/plan-compras/api/paquetes/sugerencias', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'sugerencias']);
$router->get('/plan-compras/api/paquetes/resumen', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'resumen']);
$router->post('/plan-compras/api/paquetes', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'crear']);
$router->post('/plan-compras/api/paquetes/asignar', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'asignar']);
$router->post('/plan-compras/api/paquetes/desasignar', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'desasignar']);
```

- [ ] **Step 2: Implementar el controller**

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PaquetesService;

/**
 * Paquetes de contratación (PDC v2 / Fase A3).
 * Lectura: lps.paquetes_contratacion.ver. Escritura: lps.paquetes_contratacion.editar + CSRF plan_compras_v2.
 */
class PlanComprasPaquetesController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private PaquetesService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new PaquetesService($this->db);
    }

    /** GET /plan-compras/api/paquetes */
    public function catalogo(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $busqueda = isset($_GET['busqueda']) && is_string($_GET['busqueda']) ? $_GET['busqueda'] : null;
        $this->ok(['paquetes' => $this->service->catalogo($busqueda)]);
    }

    /** GET /plan-compras/api/paquetes/insumos?filtro=&versionId= */
    public function insumos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $filtro = in_array($_GET['filtro'] ?? '', ['sin_asignar', 'asignados', 'todos'], true) ? $_GET['filtro'] : 'todos';
        $r = $this->service->insumosDeVersion($projectId, $filtro, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** GET /plan-compras/api/paquetes/sugerencias?versionId= */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $r = $this->service->sugerencias($projectId, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** GET /plan-compras/api/paquetes/resumen?versionId= */
    public function resumen(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $r = $this->service->resumen($projectId, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** POST /plan-compras/api/paquetes  {nombre, tipoNegociacion} */
    public function crear(): void
    {
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $nombre = is_string($body['nombre'] ?? null) ? $body['nombre'] : '';
        $tipo = is_string($body['tipoNegociacion'] ?? null) ? $body['tipoNegociacion'] : '';
        $r = $this->service->crearPaquete($nombre, $tipo, $this->usuario());
        if (!$r['ok']) {
            $this->fail('PAQUETE_INVALIDO', 'Nombre vacío o tipo de negociación inválido.', 422);
            return;
        }
        $this->ok(['paquete' => $r['paquete']]);
    }

    /** POST /plan-compras/api/paquetes/asignar  {insumos:[{descripcionNorm,unidad}], paqueteId} */
    public function asignar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $insumos = is_array($body['insumos'] ?? null) ? $body['insumos'] : [];
        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $r = $this->service->asignar($projectId, $insumos, (int) $paqueteId, $this->usuario());
        if (!$r['ok']) {
            $this->fail('PAQUETE_INVALIDO', 'El paquete no existe o está inactivo.', 422);
            return;
        }
        $this->ok(['asignados' => $r['asignados']]);
    }

    /** POST /plan-compras/api/paquetes/desasignar  {insumos:[...]} */
    public function desasignar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $insumos = is_array($body['insumos'] ?? null) ? $body['insumos'] : [];
        $r = $this->service->desasignar($projectId, $insumos);
        $this->ok(['desasignados' => $r['desasignados']]);
    }

    /** ?versionId=N validado, o null (el servicio usa la versión activa). */
    private function versionIdParam(): ?int
    {
        $versionId = filter_var($_GET['versionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $versionId === false || $versionId === null ? null : $versionId;
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }

    /** RBAC ver + proyecto. Retorna projectId o null (ya respondió). */
    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para ver paquetes de contratación.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        return $projectId;
    }

    /** RBAC editar + proyecto + CSRF. Retorna projectId o null (ya respondió). */
    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.editar')) {
            $this->fail('FORBIDDEN', 'No autorizado para editar paquetes de contratación.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return null;
        }
        return $projectId;
    }
}
```

- [ ] **Step 3: Verificación estática + regresión**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasPaquetesController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasPaquetesController.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_paquetes.php
docker compose exec app php tests/test_pdc_v2_paquetes_motor.php
docker compose exec app php tests/test_pdc_v2_maestro.php
docker compose exec app php tests/test_pdc_v2_rbac.php
```

Expected: todo verde. (El test RBAC existente es la regresión del contrato de permisos: `lps.paquetes_contratacion.ver/editar` ya están en catálogo y seeds — si ese archivo no cubre estas claves, añadir en él dos asserts de `RbacService->can()` para rol D con cada clave, siguiendo el patrón de los asserts de `lps.pdc.maestro`.)

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/Api/PlanComprasPaquetesController.php public/index.php
git commit -m "feat(pdc-v2): endpoints de paquetes de contratacion (RBAC paquetes_contratacion + CSRF)"
```

---

### Task 7: SPA — paquetesState + vista Paquetes + ruta (plan-de-compras, TDD)

**Files:**
- Create: `src/lib/paquetesState.ts`, `src/lib/paquetesState.test.ts`, `src/pages/PaquetesContratacion.tsx`
- Modify: `src/lib/types.ts`, `src/App.tsx`, `src/styles.css`

**Interfaces:**
- Consumes: `apiGet`/`apiPost` (existen), contrato HTTP de T6.
- Produces:
  - Tipos: `PaqueteCatalogo {id, nombre, tipoNegociacion, insumosGlobal}`, `InsumoPaquete {descripcionNorm, unidad, descripcion, tipoInsumo, agrupacion, cantidadTotal, valorTotal, paqueteId, paqueteNombre}`, `SugerenciaPaquete {descripcionNorm, unidad, paqueteId, paqueteNombre, capa, confianza, evidencia}`, `ResumenPaquetes {version, total, asignados, cobertura, porPaquete}`, `TIPOS_NEGOCIACION` (const con los 4 valores + labels es).
  - `paquetesState.ts`: `seleccion: Set<string>` (clave `norm@@unidad`), `sugerencias: Map<string, SugerenciaPaquete>`, `ocupado`, `mensaje`; acciones `TOGGLE_SEL/SEL_TODOS/LIMPIAR_SEL/SUGERENCIAS_OK/LIMPIAR_SUGERENCIAS/OCUPADO/LISTO/FALLO`; helper exportado `claveInsumo(norm, unidad): string`.
  - Vista con test-ids: `pdc-paq-grid` (grilla insumos), `pdc-paq-filtro`, `pdc-paq-cobertura`, `pdc-paq-sugerir`, `pdc-paq-aceptar-sugeridos`, `pdc-paq-asignar`, `pdc-paq-select-paquete`, `pdc-paq-crear-nombre`, `pdc-paq-crear-tipo`, `pdc-paq-crear`, `pdc-paq-desasignar`, `pdc-paq-paquetes` (lista de paquetes).

- [ ] **Step 1: Branch**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git checkout main && git checkout -b pdc-a3-paquetes
```

- [ ] **Step 2: Tipos en `src/lib/types.ts`** (añadir al final):

```ts
export type PaqueteCatalogo = { id: number; nombre: string; tipoNegociacion: string; insumosGlobal: number }

export type InsumoPaquete = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  tipoInsumo: string
  agrupacion: string | null
  cantidadTotal: number
  valorTotal: number
  paqueteId: number | null
  paqueteNombre: string | null
}

export type SugerenciaPaquete = {
  descripcionNorm: string
  unidad: string
  paqueteId: number
  paqueteNombre: string
  capa: 'exacta' | 'tokens' | 'agrupacion'
  confianza: 'alta' | 'media' | 'baja'
  evidencia: string
}

export type ResumenPaquetes = {
  version: { id: number; label: string }
  total: number
  asignados: number
  cobertura: number
  porPaquete: { paqueteId: number; nombre: string; tipoNegociacion: string; insumos: number; subtotal: number }[]
}

export const TIPOS_NEGOCIACION: { value: string; label: string }[] = [
  { value: 'a_todo_costo', label: 'A todo costo' },
  { value: 'mano_obra', label: 'Mano de obra' },
  { value: 'suministro', label: 'Suministro' },
  { value: 'consumibles', label: 'Consumibles' },
]
```

- [ ] **Step 3: Test del reducer (falla)** — `src/lib/paquetesState.test.ts`

```ts
import { describe, expect, it } from 'vitest'
import { claveInsumo, estadoInicialPaquetes, paquetesReducer } from './paquetesState'
import type { SugerenciaPaquete } from './types'

const sug: SugerenciaPaquete = {
  descripcionNorm: 'PISO CERAMICO 30X30', unidad: 'M2',
  paqueteId: 7, paqueteNombre: 'Pisos', capa: 'exacta', confianza: 'alta', evidencia: 'en 2 proyectos',
}

describe('paquetesReducer', () => {
  it('selección: toggle, todos, limpiar', () => {
    const k = claveInsumo('PISO CERAMICO 30X30', 'M2')
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(true)
    s = paquetesReducer(s, { type: 'TOGGLE_SEL', clave: k })
    expect(s.seleccion.has(k)).toBe(false)
    s = paquetesReducer(s, { type: 'SEL_TODOS', claves: [k, claveInsumo('AYUDANTE', 'HC')] })
    expect(s.seleccion.size).toBe(2)
    s = paquetesReducer(s, { type: 'LIMPIAR_SEL' })
    expect(s.seleccion.size).toBe(0)
  })

  it('sugerencias se indexan por clave y se limpian', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'SUGERENCIAS_OK', sugerencias: [sug] })
    expect(s.sugerencias.get(claveInsumo(sug.descripcionNorm, sug.unidad))?.paqueteId).toBe(7)
    s = paquetesReducer(s, { type: 'LIMPIAR_SUGERENCIAS' })
    expect(s.sugerencias.size).toBe(0)
  })

  it('ocupado/listo/fallo', () => {
    let s = paquetesReducer(estadoInicialPaquetes, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    s = paquetesReducer(s, { type: 'LISTO', mensaje: '2 asignados' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('2 asignados')
    s = paquetesReducer(s, { type: 'FALLO', mensaje: 'error' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('error')
  })
})
```

- [ ] **Step 4: Correr y ver que falla**

Run: `npx vitest run src/lib/paquetesState.test.ts`
Expected: FAIL — `Cannot find module './paquetesState'`.

- [ ] **Step 5: Implementar `src/lib/paquetesState.ts`**

```ts
import type { SugerenciaPaquete } from './types'

/** Clave estable de un insumo único: norma + unidad. */
export function claveInsumo(descripcionNorm: string, unidad: string): string {
  return `${descripcionNorm}@@${unidad}`
}

export type PaquetesState = {
  seleccion: Set<string>
  sugerencias: Map<string, SugerenciaPaquete>
  ocupado: boolean
  mensaje: string | null
}

export type PaquetesAction =
  | { type: 'TOGGLE_SEL'; clave: string }
  | { type: 'SEL_TODOS'; claves: string[] }
  | { type: 'LIMPIAR_SEL' }
  | { type: 'SUGERENCIAS_OK'; sugerencias: SugerenciaPaquete[] }
  | { type: 'LIMPIAR_SUGERENCIAS' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialPaquetes: PaquetesState = {
  seleccion: new Set(), sugerencias: new Map(), ocupado: false, mensaje: null,
}

export function paquetesReducer(state: PaquetesState, action: PaquetesAction): PaquetesState {
  switch (action.type) {
    case 'TOGGLE_SEL': {
      const seleccion = new Set(state.seleccion)
      if (seleccion.has(action.clave)) seleccion.delete(action.clave)
      else seleccion.add(action.clave)
      return { ...state, seleccion }
    }
    case 'SEL_TODOS':
      return { ...state, seleccion: new Set(action.claves) }
    case 'LIMPIAR_SEL':
      return { ...state, seleccion: new Set() }
    case 'SUGERENCIAS_OK': {
      const sugerencias = new Map<string, SugerenciaPaquete>()
      for (const s of action.sugerencias) sugerencias.set(claveInsumo(s.descripcionNorm, s.unidad), s)
      return { ...state, sugerencias, ocupado: false, mensaje: null }
    }
    case 'LIMPIAR_SUGERENCIAS':
      return { ...state, sugerencias: new Map() }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      return { ...state, ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
  }
}
```

- [ ] **Step 6: Correr y ver que pasa**

Run: `npx vitest run src/lib/paquetesState.test.ts`
Expected: PASS (3 tests).

- [ ] **Step 7: Vista `src/pages/PaquetesContratacion.tsx`**

```tsx
import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ClientSideRowModelModule, ModuleRegistry, RowStyleModule, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { ColDef, RowClickedEvent } from 'ag-grid-community'
import { apiGet, apiPost } from '../lib/api'
import { claveInsumo, estadoInicialPaquetes, paquetesReducer } from '../lib/paquetesState'
import { TIPOS_NEGOCIACION } from '../lib/types'
import type { InsumoPaquete, PaqueteCatalogo, ResumenPaquetes, SugerenciaPaquete } from '../lib/types'

// Módulos selectivos (nunca AllCommunityModule); Validation solo en dev (patrón del repo).
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule,
  RowStyleModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const tema = themeQuartz.withParams({
  backgroundColor: '#1c1c1e', foregroundColor: '#e5e5e7', headerBackgroundColor: '#2c2c2e',
  accentColor: '#30d158', borderColor: '#3a3a3c',
})

const fmtCOP = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 })
const CONFIANZA_LABEL: Record<string, string> = { alta: 'alta', media: 'media', baja: 'baja' }

type Filtro = 'sin_asignar' | 'asignados' | 'todos'

export default function PaquetesContratacion() {
  const [state, dispatch] = useReducer(paquetesReducer, estadoInicialPaquetes)
  const [insumos, setInsumos] = useState<InsumoPaquete[]>([])
  const [paquetes, setPaquetes] = useState<PaqueteCatalogo[]>([])
  const [resumen, setResumen] = useState<ResumenPaquetes | null>(null)
  const [filtro, setFiltro] = useState<Filtro>('todos')
  const [agrupacion, setAgrupacion] = useState<string>('')
  const [sinVersion, setSinVersion] = useState(false)
  const [paqueteDestino, setPaqueteDestino] = useState<number | ''>('')
  const [nuevoNombre, setNuevoNombre] = useState('')
  const [nuevoTipo, setNuevoTipo] = useState(TIPOS_NEGOCIACION[0].value)

  const cargar = useCallback((f: Filtro) => {
    apiGet<{ version: unknown; insumos: InsumoPaquete[] }>(`/plan-compras/api/paquetes/insumos?filtro=${f}`)
      .then((d) => { setInsumos(d.insumos); setSinVersion(false) })
      .catch(() => { setInsumos([]); setSinVersion(true) })
    apiGet<{ paquetes: PaqueteCatalogo[] }>('/plan-compras/api/paquetes')
      .then((d) => setPaquetes(d.paquetes))
      .catch(() => setPaquetes([]))
    apiGet<ResumenPaquetes>('/plan-compras/api/paquetes/resumen')
      .then(setResumen)
      .catch(() => setResumen(null))
  }, [])
  useEffect(() => cargar(filtro), [cargar, filtro])

  const agrupaciones = useMemo(
    () => [...new Set(insumos.map((i) => i.agrupacion).filter((a): a is string => !!a))].sort(),
    [insumos],
  )
  const visibles = useMemo(
    () => (agrupacion === '' ? insumos : insumos.filter((i) => i.agrupacion === agrupacion)),
    [insumos, agrupacion],
  )

  const cols = useMemo<ColDef<InsumoPaquete>[]>(() => [
    {
      headerName: '', width: 44, cellClass: 'pdc-paq-check', sortable: false,
      valueGetter: (p) => (p.data && state.seleccion.has(claveInsumo(p.data.descripcionNorm, p.data.unidad)) ? 1 : 0),
      valueFormatter: (p) => (p.value === 1 ? '✔' : ''),
    },
    { headerName: 'Insumo', field: 'descripcion', flex: 2, minWidth: 220 },
    { headerName: 'Agrupación', field: 'agrupacion', flex: 1, minWidth: 130, valueFormatter: (p) => p.value ?? '—' },
    { headerName: 'Und', field: 'unidad', width: 80 },
    { headerName: 'Valor total', field: 'valorTotal', width: 130, valueFormatter: (p) => fmtCOP.format(p.value ?? 0) },
    { headerName: 'Paquete', field: 'paqueteNombre', flex: 1, minWidth: 140, valueFormatter: (p) => p.value ?? '—' },
    {
      headerName: 'Sugerencia', flex: 1, minWidth: 180, sortable: false,
      valueGetter: (p) => {
        if (!p.data) return ''
        const s = state.sugerencias.get(claveInsumo(p.data.descripcionNorm, p.data.unidad))
        return s ? `${s.paqueteNombre} (${s.capa} · ${CONFIANZA_LABEL[s.confianza]})` : ''
      },
    },
  ], [state.seleccion, state.sugerencias])

  const onRowClicked = (e: RowClickedEvent<InsumoPaquete>) => {
    if (!e.data) return
    dispatch({ type: 'TOGGLE_SEL', clave: claveInsumo(e.data.descripcionNorm, e.data.unidad) })
  }

  const seleccionados = useMemo(
    () => visibles.filter((i) => state.seleccion.has(claveInsumo(i.descripcionNorm, i.unidad))),
    [visibles, state.seleccion],
  )

  const refrescar = () => { cargar(filtro); dispatch({ type: 'LIMPIAR_SEL' }) }

  const onSugerir = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const d = await apiGet<{ version: unknown; sugerencias: SugerenciaPaquete[] }>('/plan-compras/api/paquetes/sugerencias')
      dispatch({ type: 'SUGERENCIAS_OK', sugerencias: d.sugerencias })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onAceptarSugeridos = async () => {
    // Agrupa las sugerencias por paquete y asigna en bloque; el humano ya las vio en la grilla.
    const porPaquete = new Map<number, { descripcionNorm: string; unidad: string }[]>()
    for (const s of state.sugerencias.values()) {
      const lista = porPaquete.get(s.paqueteId) ?? []
      lista.push({ descripcionNorm: s.descripcionNorm, unidad: s.unidad })
      porPaquete.set(s.paqueteId, lista)
    }
    if (porPaquete.size === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      let total = 0
      for (const [paqueteId, lista] of porPaquete) {
        const r = await apiPost<{ asignados: number }>('/plan-compras/api/paquetes/asignar', { insumos: lista, paqueteId })
        total += r.asignados
      }
      dispatch({ type: 'LIMPIAR_SUGERENCIAS' })
      dispatch({ type: 'LISTO', mensaje: `${total} sugerencia(s) aceptada(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onAsignar = async () => {
    if (paqueteDestino === '' || seleccionados.length === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ asignados: number }>('/plan-compras/api/paquetes/asignar', {
        insumos: seleccionados.map((i) => ({ descripcionNorm: i.descripcionNorm, unidad: i.unidad })),
        paqueteId: paqueteDestino,
      })
      dispatch({ type: 'LISTO', mensaje: `${r.asignados} insumo(s) asignado(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onDesasignar = async () => {
    if (seleccionados.length === 0) return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ desasignados: number }>('/plan-compras/api/paquetes/desasignar', {
        insumos: seleccionados.map((i) => ({ descripcionNorm: i.descripcionNorm, unidad: i.unidad })),
      })
      dispatch({ type: 'LISTO', mensaje: `${r.desasignados} insumo(s) desasignado(s).` })
      refrescar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onCrearPaquete = async () => {
    if (nuevoNombre.trim() === '') return
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ paquete: PaqueteCatalogo & { existente: number } }>('/plan-compras/api/paquetes', {
        nombre: nuevoNombre, tipoNegociacion: nuevoTipo,
      })
      setNuevoNombre('')
      setPaqueteDestino(r.paquete.id)
      dispatch({ type: 'LISTO', mensaje: r.paquete.existente === 1 ? 'El paquete ya existía; seleccionado.' : 'Paquete creado.' })
      cargar(filtro)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  if (sinVersion) {
    return (
      <section className="pdc-bloque">
        <h1>Paquetes de contratación</h1>
        <p>El proyecto no tiene un presupuesto importado. Importa un presupuesto primero.</p>
      </section>
    )
  }

  return (
    <section className="pdc-bloque pdc-paquetes">
      <header className="pdc-paq-header">
        <div>
          <h1>Paquetes de contratación</h1>
          <p>Agrupa los insumos del presupuesto activo en paquetes. Meta: 100% asignado.</p>
        </div>
        {resumen && (
          <div data-testid="pdc-paq-cobertura" className="pdc-paq-cobertura">
            <strong>Cobertura: {resumen.cobertura}%</strong> · {resumen.asignados} de {resumen.total} insumos asignados
            <div className="pdc-paq-barra"><div className="pdc-paq-barra-fill" style={{ width: `${resumen.cobertura}%` }} /></div>
          </div>
        )}
      </header>

      {state.mensaje && <div className="pdc-info" role="status">{state.mensaje}</div>}

      <div className="pdc-paq-toolbar">
        <select data-testid="pdc-paq-filtro" value={filtro} onChange={(e) => setFiltro(e.target.value as Filtro)}>
          <option value="todos">Todos</option>
          <option value="sin_asignar">Sin asignar</option>
          <option value="asignados">Asignados</option>
        </select>
        <select value={agrupacion} onChange={(e) => setAgrupacion(e.target.value)}>
          <option value="">Todas las agrupaciones</option>
          {agrupaciones.map((a) => <option key={a} value={a}>{a}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-sugerir" disabled={state.ocupado} onClick={onSugerir}>
          Sugerir asignaciones
        </button>
        {state.sugerencias.size > 0 && (
          <button type="button" data-testid="pdc-paq-aceptar-sugeridos" disabled={state.ocupado} onClick={onAceptarSugeridos}>
            Aceptar {state.sugerencias.size} sugerida(s)
          </button>
        )}
        <span className="pdc-paq-sel">{seleccionados.length} seleccionado(s)</span>
        <select data-testid="pdc-paq-select-paquete" value={paqueteDestino} onChange={(e) => setPaqueteDestino(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">Paquete destino…</option>
          {paquetes.map((p) => <option key={p.id} value={p.id}>{p.nombre}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-asignar" disabled={state.ocupado || paqueteDestino === '' || seleccionados.length === 0} onClick={onAsignar}>
          Asignar a paquete
        </button>
        <button type="button" data-testid="pdc-paq-desasignar" disabled={state.ocupado || seleccionados.length === 0} onClick={onDesasignar}>
          Desasignar
        </button>
      </div>

      <div className="pdc-paq-crear">
        <input data-testid="pdc-paq-crear-nombre" placeholder="Nuevo paquete…" value={nuevoNombre} onChange={(e) => setNuevoNombre(e.target.value)} />
        <select data-testid="pdc-paq-crear-tipo" value={nuevoTipo} onChange={(e) => setNuevoTipo(e.target.value)}>
          {TIPOS_NEGOCIACION.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
        </select>
        <button type="button" data-testid="pdc-paq-crear" disabled={state.ocupado || nuevoNombre.trim() === ''} onClick={onCrearPaquete}>
          Crear paquete
        </button>
      </div>

      <div data-testid="pdc-paq-grid" className="pdc-grid-wrap">
        <AgGridReact<InsumoPaquete>
          theme={tema}
          rowData={visibles}
          columnDefs={cols}
          onRowClicked={onRowClicked}
          domLayout="autoHeight"
          suppressCellFocus
        />
      </div>

      <h2>Paquetes</h2>
      <ul data-testid="pdc-paq-paquetes" className="pdc-paq-lista">
        {(resumen?.porPaquete ?? []).map((p) => (
          <li key={p.paqueteId}>
            <strong>{p.nombre}</strong> · {TIPOS_NEGOCIACION.find((t) => t.value === p.tipoNegociacion)?.label ?? p.tipoNegociacion}
            {' · '}{p.insumos} insumo(s) · {fmtCOP.format(p.subtotal)}
          </li>
        ))}
        {(resumen?.porPaquete ?? []).length === 0 && <li>Aún no hay insumos asignados.</li>}
      </ul>
    </section>
  )
}
```

- [ ] **Step 8: Ruta y NavLink en `src/App.tsx`** — añadir el import, el NavLink (tras "Presupuesto") y la ruta:

```tsx
import PaquetesContratacion from './pages/PaquetesContratacion'
```
```tsx
<NavLink to="/ensamble/paquetes">Paquetes</NavLink>
```
```tsx
<Route path="/ensamble/paquetes" element={<PaquetesContratacion />} />
```

(Leer el archivo real y respetar su estructura/clases de NavLink existentes.)

- [ ] **Step 9: Estilos en `src/styles.css`** (añadir):

```css
.pdc-paq-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.pdc-paq-cobertura { min-width: 260px; text-align: right; }
.pdc-paq-barra { height: 8px; background: #2c2c2e; border-radius: 4px; margin-top: 4px; overflow: hidden; }
.pdc-paq-barra-fill { height: 100%; background: #30d158; transition: width .3s; }
.pdc-paq-toolbar, .pdc-paq-crear { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin: 12px 0; }
.pdc-paq-sel { color: #8e8e93; font-size: 13px; }
.pdc-paq-lista { list-style: none; padding: 0; display: grid; gap: 6px; }
.pdc-paq-check { text-align: center; color: #30d158; }
```

- [ ] **Step 10: Verificar y commit**

```bash
npm run test && npm run build
git add src/lib/paquetesState.ts src/lib/paquetesState.test.ts src/lib/types.ts src/pages/PaquetesContratacion.tsx src/App.tsx src/styles.css
git commit -m "feat(pdc): vista Paquetes — asignacion masiva, sugerencias con capa/confianza y cobertura 100%"
```

Expected: Vitest todo verde (31 previos + 3 nuevos = 34); build OK.

---

### Task 8: Bundle + e2e + docs (lps-aia + plan-de-compras)

**Files:**
- Create (lps-aia): `tests/browser/pdc-v2-paquetes.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md`, `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`
- Generated (lps-aia): `public/pdc-app/**`

**Interfaces:**
- Consumes: `npm run sync`; helpers `loginAndSelectProject`/`logout`; fixture e2e del presupuesto ya existente (el spec `pdc-v2-maestro.spec.mjs` muestra el idioma); test-ids de T7.

- [ ] **Step 1: Sync del bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run sync
```

- [ ] **Step 2: Escribir el e2e** — `tests/browser/pdc-v2-paquetes.spec.mjs` (leer antes `tests/browser/pdc-v2-maestro.spec.mjs` para el idioma exacto de helpers y de la preparación del presupuesto — reusar su fixture de import si el proyecto no tiene presupuesto):

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test('paquetes: crear, asignar seleccionados y cobertura sube', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Preparación: garantiza un presupuesto importado (mismo helper/fixture del spec del maestro).
    // <adaptar del spec pdc-v2-maestro.spec.mjs: si la vista muestra "no tiene un presupuesto importado",
    //  importar el fixture mini por la vista Importar como hace ese spec>

    await page.goto('/plan-compras#/ensamble/paquetes', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).toBeVisible({ timeout: 15000 });

    // Crear paquete (idempotente: si ya existe, el backend devuelve el existente).
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill('E2E Paquete Pisos');
    await page.locator('[data-testid="pdc-paq-crear-tipo"]').selectOption('suministro');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    // Seleccionar el primer insumo sin asignar y asignarlo.
    await page.locator('[data-testid="pdc-paq-filtro"]').selectOption('sin_asignar');
    const primeraFila = page.locator('[data-testid="pdc-paq-grid"] .ag-row').first();
    await primeraFila.click();
    await expect(page.locator('.pdc-paq-sel')).toContainText('1 seleccionado');
    // El select destino quedó apuntando al paquete creado.
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    // La cobertura refleja al menos 1 asignado.
    await expect(page.locator('[data-testid="pdc-paq-cobertura"]')).not.toContainText('0 de', { timeout: 15000 });
    // El paquete aparece en la lista con subtotal.
    await expect(page.locator('[data-testid="pdc-paq-paquetes"]')).toContainText('E2E Paquete Pisos');

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

Nota para el implementador: el bloque de preparación del presupuesto se adapta del spec del maestro (mismo fixture mini); si el catálogo global ya tiene el paquete `E2E Paquete Pisos` de una corrida anterior, `crearPaquete` devuelve el existente — el spec debe ser verde en corridas repetidas. Cleanup e2e: el test PHP de paquetes limpia por proyectos 999901/999902 y marca `test-a3`, que NO cubre los datos e2e (proyecto Da Porto real + paquete `E2E Paquete Pisos`); si el residuo e2e afecta a otros specs, añadir la desasignación al final del propio spec (desasignar lo asignado) en lugar de tocar el catálogo global.

- [ ] **Step 3: Correr e2e ×2 + regresión**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-paquetes.spec.mjs --workers=1
npx playwright test tests/browser/pdc-v2-paquetes.spec.mjs --workers=1
npx playwright test tests/browser/pdc-v2-maestro.spec.mjs tests/browser/pdc-v2-maestro-sinco.spec.mjs --workers=1
docker compose exec app php tests/test_pdc_v2_paquetes.php
```

Expected: paquetes 1 passed ×2 (idempotente); regresión maestro/sinco passed; test PHP sigue exit 0 (aislamiento cruzado).

- [ ] **Step 4: Commit lps-aia**

```bash
git add public/pdc-app tests/browser/pdc-v2-paquetes.spec.mjs
git commit -m "feat(pdc-v2): bundle con la vista Paquetes + e2e del ciclo crear/asignar/cobertura"
```

- [ ] **Step 5: CLAUDE.md + roadmap en plan-de-compras + commit**

En `CLAUDE.md`, actualizar el primer párrafo de "Estado actual": "Fases A1, A1.5, A2, A2.5 **y A3** implementadas", añadiendo una frase: *paquetes de contratación (`#/ensamble/paquetes`): catálogo global `general_paquetes_contratacion` + asignación por proyecto `pdc_insumo_paquete` (un insumo un paquete, herencia en re-import) con motor de sugerencias cross-proyecto de 3 capas (exacta/tokens/Agrupación SINCO, confirmación humana) y cobertura hacia el 100%; RBAC `lps.paquetes_contratacion.ver/editar`.* — preservando el resto del párrafo.

En el roadmap `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`, marcar la Fase A3 con *(Implementada.)* al final de su entrada (como A2).

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md
git commit -m "docs(pdc): CLAUDE.md y roadmap reflejan la Fase A3 (paquetes + motor)"
```

---

## Verificación end-to-end (tras Task 8)

1. lps-aia: `test_pdc_v2_paquetes.php` + `test_pdc_v2_paquetes_motor.php` exit 0; regresión `test_pdc_v2_maestro.php` + `test_pdc_v2_maestro_sinco_import.php` verdes; gates safety+reconciliation exit 0; PHPStan `src/Services/Pdc src/Controllers/Api` limpio.
2. plan-de-compras: Vitest 34/34 + build OK.
3. e2e: `pdc-v2-paquetes` ×2 + regresión maestro/sinco/visor/import → passed.
4. Visual (navegador integrado, cierre de sprint): login → Da Porto → Paquetes → sugerir → aceptar → cobertura visible.

## Riesgos anotados

- **Rendimiento del motor**: hasta 3 SELECTs por insumo sin asignar (~800 → ~2.400 queries on-demand al pulsar "Sugerir"). Con índices `idx_pip_norm` y catálogo chico es aceptable en local; medir con datos reales y, si excede ~5s, batchear por capa (follow-up).
- **Capa agrupación depende del maestro SINCO**: sin el primer import real de A2.5 los insumos no tienen `agrupacion` (vía `maestro_id`) y la capa 3 no aporta — el motor degrada con gracia a capas 1-2.
- **Residuo e2e en el catálogo global** (`E2E Paquete Pisos`): inocuo y reutilizado por corridas siguientes (crear devuelve el existente); vigilar que no contamine el test PHP (usa marca `test-a3`, disjunta).
- **`nombre_norm` UNIQUE global**: dos usuarios creando "Pisos" a la vez → errno 1062 capturado → devuelve el existente (mismo patrón del follow-up A2).
