# Fase A2: Maestro de Insumos — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Catálogo global único de insumos de AIA (`general_maestro_insumos`) alimentado desde los presupuestos importados: consolidación de insumos únicos por versión, auto-match exacto, cola de pendientes con creación masiva (cold start) y vinculación manual con sugerencias, con indicador de cobertura.

**Architecture:** Una migración crea el catálogo global (sin `project_id`, patrón `general_*`) y la tabla de vínculos por proyecto/versión (un vínculo por insumo único consolidado). `MaestroInsumosService` implementa generar/sugerir/vincular/crear-masivo/catálogo; `PlanComprasMaestroController` expone 6 endpoints bajo el namespace del módulo. La SPA reemplaza el placeholder del Maestro con dos secciones (Pendientes con selección múltiple + Catálogo) y muestra cobertura de la versión activa.

**Tech Stack:** PHP 8.3 + MySQL 8 (Docker lps-aia), React+TS+AG Grid Community, Vitest, Playwright. Depende de A1 (tablas de presupuesto) y convive con A1.5.

## Global Constraints

- Envelope `{ok,data|error}` + `JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE`. Errores: `FORBIDDEN` 403, `NO_PROJECT` 409, `NO_VERSION` 404, `CSRF_INVALID` 403, `VINCULO_INVALIDO` 422, `MAESTRO_DUPLICADO` 409.
- RBAC: lectura = `lps.pdc.ver`; TODA escritura (generar, vincular, crear, editar) = **`lps.pdc.maestro`** (clave nueva: catálogo PHP + patch SQL, roles A y D). CSRF `plan_compras_v2` en todos los POST (`$_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token']`).
- Normalización de descripciones = LA MISMA del parser: mayúsculas, sin acentos (Á→A…Ñ→N), trim; MÁS colapso de espacios internos (`preg_replace('/\s+/', ' ')`). Centralizada en `MaestroInsumosService::normalizar()` (pública estática) — no duplicar reglas.
- `general_maestro_insumos` SIN `project_id` (catálogo `general_*`); `pdc_insumo_vinculos` CON `project_id int NOT NULL` + índices liderados por `project_id`; ambas `utf8mb4_unicode_ci`. UNIQUEs exactas del Task 1. FK de vínculo→maestro `ON DELETE SET NULL` (borrar un insumo del maestro no rompe vínculos: quedan pendientes otra vez... NO — regla: `maestro_id` FK `ON DELETE RESTRICT`: el maestro no se borra si tiene vínculos; el catálogo usa `activo=0` para retirar).
- Generar vínculos es **idempotente** (`INSERT ... ON DUPLICATE KEY UPDATE` que NO pisa decisiones humanas: solo actualiza consolidados y re-intenta match de los `pendiente`).
- AG Grid Community; enteros 1/0 (no booleans) en campos con valueFormatter de texto; selección múltiple con `rowSelection` de Community.
- Tests PHP autoejecutables sobre MySQL real (proyectos 999901/999902; **cleanup también de `general_maestro_insumos` por marcador**: los tests crean insumos con `creado_por='test-a2'` y borran por ese campo — nunca `TRUNCATE`). Gates: PHPStan, `test_global_table_safety`, `test_global_table_reconciliation`, Vitest, e2e.
- Ramas `pdc-a2-maestro` en ambos repos; commits `feat(pdc-v2)` / `feat(pdc)`; commitear SOLO los archivos de cada task (repos con sucios ajenos).
- Docker live-mount: `docker compose up -d app db` (sin rebuild).

---

## File Structure

**lps-aia (rama `pdc-a2-maestro`):**
```
database/migrations/20260723_pdc_v2_maestro_insumos.sql   # T1
database/patches/20260723_pdc_maestro_rbac.sql            # T2
src/Security/RbacCatalog.php                              # T2: Modify
tests/test_pdc_v2_rbac_maestro.php                        # T2
src/Services/Pdc/MaestroInsumosService.php                # T3 (normalizar+consolidar+generar) y T4 (acciones)
tests/test_pdc_v2_maestro.php                             # T3+T4 (un solo test incremental)
src/Controllers/Api/PlanComprasMaestroController.php      # T5
public/index.php                                          # T5: +6 rutas
tests/browser/pdc-v2-maestro.spec.mjs                     # T8
public/pdc-app/**                                         # T8: bundle
```

**plan-de-compras (rama `pdc-a2-maestro`):**
```
src/lib/types.ts              # T6: Modify (+tipos maestro)
src/lib/maestroState.ts       # T6 (reducer de selección/cola)
src/lib/maestroState.test.ts  # T6
src/pages/MaestroInsumos.tsx  # T7: REEMPLAZO del placeholder
src/App.tsx                   # T7: Modify (nav: enlace Maestro visible)
src/styles.css                # T7: Modify
CLAUDE.md                     # T8: Modify (estado)
```

---

### Task 1: Migración del maestro y los vínculos (lps-aia)

**Files:**
- Create: `database/migrations/20260723_pdc_v2_maestro_insumos.sql`

**Interfaces:**
- Produces: tablas `general_maestro_insumos` y `pdc_insumo_vinculos` (columnas exactas abajo — T3/T4 las usan).

- [ ] **Step 1: Branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && git checkout main -q && git checkout -b pdc-a2-maestro
```

- [ ] **Step 2: Escribir la migración**

```sql
-- 20260723_pdc_v2_maestro_insumos.sql
-- PDC v2 / Fase A2: catálogo global de insumos (general_*, sin project_id)
-- y vínculos insumo-consolidado ↔ maestro por proyecto/versión.

CREATE TABLE IF NOT EXISTS `general_maestro_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(500) NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `activo` tinyint NOT NULL DEFAULT 1,
  `creado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmi_norm_unidad` (`descripcion_norm`(191), `unidad`),
  KEY `idx_gmi_tipo` (`tipo_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_insumo_vinculos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `descripcion_original` varchar(500) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `cantidad_total` decimal(18,4) NOT NULL DEFAULT 0,
  `valor_total` decimal(18,2) NOT NULL DEFAULT 0,
  `apariciones` int NOT NULL DEFAULT 0,
  `maestro_id` bigint DEFAULT NULL,
  `estado` enum('pendiente','auto','confirmado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_piv_version_insumo` (`project_id`, `version_id`, `descripcion_norm`(150), `unidad`),
  KEY `idx_piv_project_version_estado` (`project_id`, `version_id`, `estado`),
  CONSTRAINT `fk_piv_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_piv_maestro` FOREIGN KEY (`maestro_id`) REFERENCES `general_maestro_insumos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 3: Aplicar y verificar** (mismo comando mysql de A1, ver reporte T1 de A1):

```bash
docker compose up -d app db
source .env; docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/20260723_pdc_v2_maestro_insumos.sql
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW CREATE TABLE general_maestro_insumos\G SHOW CREATE TABLE pdc_insumo_vinculos\G" | grep -E "Table:|UNIQUE|CONSTRAINT|utf8mb4_unicode_ci"
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Expected: tablas con UNIQUEs/FKs exactas; ambos gates exit 0.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/20260723_pdc_v2_maestro_insumos.sql
git commit -m "feat(pdc-v2): tablas del maestro global de insumos y vínculos por versión"
```

---

### Task 2: RBAC `lps.pdc.maestro` (lps-aia, TDD)

**Files:**
- Modify: `src/Security/RbacCatalog.php` (entrada tras `lps.pdc.importar` en `permissionDefinitions()`; clave en el array extra del rol `'D'`)
- Create: `database/patches/20260723_pdc_maestro_rbac.sql`
- Test: `tests/test_pdc_v2_rbac_maestro.php`

**Interfaces:**
- Produces: `RbacService->can('lps.pdc.maestro')` true para A y D; false para R/OT/DCV/V/C/S/G/SG.

- [ ] **Step 1: Test que falla** — `tests/test_pdc_v2_rbac_maestro.php` (mismo patrón que `test_pdc_v2_rbac_importar.php`, cambiando la clave):

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\RbacCatalog;
use App\Security\RbacService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

echo "=== PDC v2: permiso lps.pdc.maestro ===\n";
$assert(in_array('lps.pdc.maestro', RbacCatalog::permissionKeys(), true), 'La clave existe en el catálogo.');
$rbac = new RbacService(Database::getInstance());
$assert($rbac->can('lps.pdc.maestro', 'A'), 'A administra el maestro (wildcard).');
$assert($rbac->can('lps.pdc.maestro', 'D'), 'D administra el maestro.');
foreach (['R', 'OT', 'DCV', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    $assert(!$rbac->can('lps.pdc.maestro', $rol), "{$rol} NO administra el maestro.");
}
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: RED** — `docker compose exec app php tests/test_pdc_v2_rbac_maestro.php` → FAIL en "existe en el catálogo".

- [ ] **Step 3: Implementar** — en `permissionDefinitions()`, tras la entrada `lps.pdc.importar`:

```php
['key' => 'lps.pdc.maestro', 'module' => 'lps', 'action' => 'pdc_maestro', 'description' => 'Administrar el maestro global de insumos del plan de compras v2'],
```

en el array extra del rol `'D'` (junto a `'lps.pdc.importar',`): `'lps.pdc.maestro',`. Patch SQL:

```sql
-- 20260723_pdc_maestro_rbac.sql — idempotente.
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.pdc.maestro', 'lps', 'pdc_maestro', 'Administrar el maestro global de insumos del plan de compras v2', 1, 1, NOW(), NOW());

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A', 'lps.pdc.maestro', 1, 'patch_20260723', NOW(), NOW()),
    ('D', 'lps.pdc.maestro', 1, 'patch_20260723', NOW(), NOW());
```

Aplicar el patch con el comando mysql estándar.

- [ ] **Step 4: GREEN** — 11 PASS, exit 0.

- [ ] **Step 5: Commit**

```bash
git add src/Security/RbacCatalog.php database/patches/20260723_pdc_maestro_rbac.sql tests/test_pdc_v2_rbac_maestro.php
git commit -m "feat(pdc-v2): permiso lps.pdc.maestro (catálogo + patch BD, roles A y D)"
```

---

### Task 3: Servicio — normalizar, consolidar y generar vínculos (lps-aia, TDD)

**Files:**
- Create: `src/Services/Pdc/MaestroInsumosService.php`
- Test: `tests/test_pdc_v2_maestro.php` (primera mitad; T4 lo extiende)

**Interfaces:**
- Consumes: tablas T1, `Database`, tablas A1.
- Produces:
  - `MaestroInsumosService::__construct(\Database $db)`
  - `public static function normalizar(string $s): string` — mayúsculas, sin acentos, trim, espacios colapsados.
  - `generarVinculos(int $projectId, ?int $versionId = null): ?array` — `null` si no hay versión; consolida (GROUP BY norm+unidad sobre `pdc_presupuesto_apu_insumos` de la versión: SUM cantidad_total/valor_total, COUNT apariciones, MIN(descripcion) como original, primer tipo_insumo), upsert de vínculos SIN pisar `confirmado` ni des-vincular; re-match exacto de los `pendiente` contra el maestro (`estado='auto'`). Retorna `['versionId'=>int, 'total'=>int, 'auto'=>int, 'confirmados'=>int, 'pendientes'=>int]`.
  - `vinculos(int $projectId, ?int $versionId = null): ?array` — `['version'=>..., 'resumen'=>{total,auto,confirmados,pendientes,cobertura}, 'vinculos'=>list<{id,descripcionOriginal,descripcionNorm,unidad,tipoInsumo,cantidadTotal,valorTotal,apariciones,maestroId,maestroDescripcion,estado}>]` orden: pendientes primero, luego por valorTotal DESC. `cobertura` = % vinculados (auto+confirmado) redondeado a 1 decimal, 100.0 si total=0.

- [ ] **Step 1: Test que falla** — `tests/test_pdc_v2_maestro.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_M_PROJECT_A = 999901;
const PDC_M_PROJECT_B = 999902;
const PDC_M_MARCA = 'test-a2';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_M_PROJECT_A, PDC_M_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$pid]);
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA]);
};
$limpiar();

echo "=== PDC v2: maestro de insumos (normalizar/consolidar/generar) ===\n";

// normalizar()
$assert(MaestroInsumosService::normalizar('  Teja   de Zinc  ') === 'TEJA DE ZINC', 'normalizar: mayúsculas, trim y espacios colapsados.');
$assert(MaestroInsumosService::normalizar('Ñandú Ácido') === 'NANDU ACIDO', 'normalizar: sin acentos ni Ñ.');

$maestro = new MaestroInsumosService($db);
$importSvc = new PresupuestoImportService($db, new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-m-store-' . getmypid()), new PresupuestoExcelParser());

// Sin versión → null.
$assert($maestro->generarVinculos(PDC_M_PROJECT_A) === null, 'Sin versión activa → null.');

// Importar fixture (4 filas APU; TEJA/AYUDANTE/CONCRETO/BOMBEO — todos distintos → 4 únicos).
$tmp = sys_get_temp_dir() . '/pdc_m_v1.xlsx';
pdcFixturePresupuestoValido($tmp);
$p = $importSvc->previewDesdeArchivo($tmp, 'v1.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$c = $importSvc->confirmar($p['importToken'], PDC_M_PROJECT_A);

// Cold start: maestro vacío → todo pendiente.
$g1 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g1['total'] === 4 && $g1['pendientes'] === 4 && $g1['auto'] === 0, 'Cold start: 4 únicos, todos pendientes.');

// Idempotencia: regenerar no duplica ni cambia estados.
$g2 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g2['total'] === 4 && $g2['pendientes'] === 4, 'Regenerar es idempotente.');

// Consolidación real: mismo insumo en 2 actividades suma cantidades/valores.
// (El fixture SinIdApu de A1-T3 tiene un insumo; usamos un import a B con el fixture válido
// y verificamos los agregados del vínculo de TEJA en A.)
$v = $maestro->vinculos(PDC_M_PROJECT_A);
$teja = array_values(array_filter($v['vinculos'], fn ($x) => $x['descripcionNorm'] === 'TEJA DE ZINC'))[0];
$assert(abs($teja['cantidadTotal'] - 21.6) < 0.001 && abs($teja['valorTotal'] - 540000.0) < 0.01 && $teja['apariciones'] === 1, 'Consolidado de TEJA correcto.');
$assert($v['resumen']['cobertura'] === 0.0, 'Cobertura 0% en cold start.');
$assert($v['vinculos'][0]['estado'] === 'pendiente', 'Orden: pendientes primero.');

// Aislamiento: B sin vínculos.
$assert($maestro->generarVinculos(PDC_M_PROJECT_B) === null, 'B sin versión → null.');

echo "--- acciones (T4) se agregan después ---\n";
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: RED** — clase no existe.

- [ ] **Step 3: Implementar `MaestroInsumosService` (parte 1)**

```php
<?php

namespace App\Services\Pdc;

/**
 * Maestro global de insumos (Fase A2): consolidación de insumos únicos por
 * versión de presupuesto, matching exacto contra el catálogo general_maestro_insumos
 * y gestión de la cola de vínculos (auto / confirmado / pendiente).
 */
final class MaestroInsumosService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /** Normalización canónica de descripciones (idéntica base del parser + espacios colapsados). */
    public static function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        $s = strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'Ü' => 'U']);
        return preg_replace('/\s+/', ' ', $s) ?? $s;
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

    public function generarVinculos(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];

        // 1) Consolidar insumos únicos de la versión.
        $consolidados = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad,
                    SUM(cantidad_total) AS cantidad_total, SUM(valor_total) AS valor_total, COUNT(*) AS apariciones
             FROM pdc_presupuesto_apu_insumos
             WHERE project_id = ? AND version_id = ?
             GROUP BY descripcion, unidad, tipo_insumo',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Re-agrupar por (norm, unidad): descripciones distintas pueden normalizar igual.
        $porClave = [];
        foreach ($consolidados as $c) {
            $norm = self::normalizar((string) $c['descripcion']);
            $clave = $norm . '|' . $c['unidad'];
            if (!isset($porClave[$clave])) {
                $porClave[$clave] = [
                    'norm' => $norm,
                    'unidad' => (string) $c['unidad'],
                    'original' => (string) $c['descripcion'],
                    'tipo' => (string) $c['tipo_insumo'],
                    'cantidad' => 0.0,
                    'valor' => 0.0,
                    'apariciones' => 0,
                ];
            }
            $porClave[$clave]['cantidad'] += (float) $c['cantidad_total'];
            $porClave[$clave]['valor'] += (float) $c['valor_total'];
            $porClave[$clave]['apariciones'] += (int) $c['apariciones'];
        }

        // 2) Upsert de vínculos sin pisar decisiones humanas ni des-vincular.
        foreach ($porClave as $u) {
            $this->db->query(
                'INSERT INTO pdc_insumo_vinculos
                    (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'pendiente\')
                 ON DUPLICATE KEY UPDATE
                    descripcion_original = VALUES(descripcion_original),
                    tipo_insumo = VALUES(tipo_insumo),
                    cantidad_total = VALUES(cantidad_total),
                    valor_total = VALUES(valor_total),
                    apariciones = VALUES(apariciones)',
                [$projectId, $vid, $u['norm'], $u['unidad'], mb_substr($u['original'], 0, 500), $u['tipo'], round($u['cantidad'], 4), round($u['valor'], 2), $u['apariciones']],
            );
        }

        // 3) Auto-match exacto de los pendientes contra el maestro activo.
        $this->db->query(
            'UPDATE pdc_insumo_vinculos v
             JOIN general_maestro_insumos m
               ON m.descripcion_norm = v.descripcion_norm AND m.unidad = v.unidad AND m.activo = 1
             SET v.maestro_id = m.id, v.estado = \'auto\'
             WHERE v.project_id = ? AND v.version_id = ? AND v.estado = \'pendiente\'',
            [$projectId, $vid],
        );

        return $this->resumen($projectId, $vid) + ['versionId' => $vid];
    }

    public function vinculos(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $rows = $this->db->query(
            'SELECT v.id, v.descripcion_original, v.descripcion_norm, v.unidad, v.tipo_insumo,
                    v.cantidad_total, v.valor_total, v.apariciones, v.maestro_id, v.estado,
                    m.descripcion AS maestro_descripcion
             FROM pdc_insumo_vinculos v
             LEFT JOIN general_maestro_insumos m ON m.id = v.maestro_id
             WHERE v.project_id = ? AND v.version_id = ?
             ORDER BY (v.estado = \'pendiente\') DESC, v.valor_total DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'version' => ['id' => $vid, 'versionLabel' => $version['version_label'], 'activa' => (int) $version['activa']],
            'resumen' => $this->resumen($projectId, $vid),
            'vinculos' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'descripcionOriginal' => $r['descripcion_original'],
                'descripcionNorm' => $r['descripcion_norm'],
                'unidad' => $r['unidad'],
                'tipoInsumo' => $r['tipo_insumo'],
                'cantidadTotal' => (float) $r['cantidad_total'],
                'valorTotal' => (float) $r['valor_total'],
                'apariciones' => (int) $r['apariciones'],
                'maestroId' => $r['maestro_id'] === null ? null : (int) $r['maestro_id'],
                'maestroDescripcion' => $r['maestro_descripcion'],
                'estado' => $r['estado'],
            ], $rows),
        ];
    }

    private function resumen(int $projectId, int $vid): array
    {
        $r = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'auto') AS auto,
                    SUM(estado = 'confirmado') AS confirmados,
                    SUM(estado = 'pendiente') AS pendientes
             FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ?",
            [$projectId, $vid],
        )->fetch(\PDO::FETCH_ASSOC);
        $total = (int) $r['total'];
        $vinculados = (int) $r['auto'] + (int) $r['confirmados'];
        return [
            'total' => $total,
            'auto' => (int) $r['auto'],
            'confirmados' => (int) $r['confirmados'],
            'pendientes' => (int) $r['pendientes'],
            'cobertura' => $total === 0 ? 100.0 : round($vinculados * 100 / $total, 1),
        ];
    }
}
```

- [ ] **Step 4: GREEN + PHPStan** — test exit 0; `phpstan analyse src/Services/Pdc` OK.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/MaestroInsumosService.php tests/test_pdc_v2_maestro.php
git commit -m "feat(pdc-v2): consolidación de insumos únicos y generación idempotente de vínculos con auto-match"
```

---

### Task 4: Servicio — acciones de la cola (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/MaestroInsumosService.php` (añadir métodos)
- Test: `tests/test_pdc_v2_maestro.php` (reemplazar la línea `--- acciones (T4) se agregan después ---` por los casos nuevos)

**Interfaces:**
- Produces:
  - `sugerencias(int $projectId, int $vinculoId, int $limite = 8): array` — insumos activos del maestro cuya `descripcion_norm` comparte tokens con la del vínculo (`LIKE` por cada palabra de ≥4 letras, unión, ordenado por nº de coincidencias DESC); `[{id,descripcion,unidad,tipoInsumo}]`.
  - `vincular(int $projectId, int $vinculoId, int $maestroId): array` — valida pertenencia del vínculo al proyecto y existencia del maestro activo; set `maestro_id`, `estado='confirmado'`. Errores: `['ok'=>false,'code'=>'VINCULO_INVALIDO']`. Éxito `['ok'=>true]`.
  - `crearDesdePendientes(int $projectId, array $vinculoIds, string $usuario): array` — TRANSACCIÓN: por cada vínculo `pendiente` del proyecto en la lista: si ya existe en el maestro (norm+unidad, carrera benigna) vincula a ése; si no, INSERT en `general_maestro_insumos` (descripcion = descripcion_original, norm, unidad, tipo, creado_por=$usuario, NOW()) y vincula; `estado='confirmado'`. Retorna `['ok'=>true,'creados'=>int,'vinculados'=>int]`.
  - `crearManual(int $projectId, string $descripcion, string $unidad, string $tipoInsumo, string $usuario): array` — inserta si no existe (norm+unidad); si existe → `['ok'=>false,'code'=>'MAESTRO_DUPLICADO']`; éxito `['ok'=>true,'id'=>int]`.
  - `catalogo(?string $busqueda = null, int $limite = 200): array` — insumos activos, filtro `LIKE` sobre `descripcion_norm` (busqueda normalizada), orden alfabético; `[{id,descripcion,unidad,tipoInsumo,creadoPor,createdAt}]`.

- [ ] **Step 1: Casos nuevos del test** (reemplazan la línea marcador):

```php
echo "=== PDC v2: maestro — acciones de la cola ===\n";

// Re-preparar: importar y generar de nuevo (la limpieza de arriba borró todo).
$tmpB = sys_get_temp_dir() . '/pdc_m_v1b.xlsx';
pdcFixturePresupuestoValido($tmpB);
$pB = $importSvc->previewDesdeArchivo($tmpB, 'v1b.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$cB = $importSvc->confirmar($pB['importToken'], PDC_M_PROJECT_A);
$maestro->generarVinculos(PDC_M_PROJECT_A);
$v = $maestro->vinculos(PDC_M_PROJECT_A);
$ids = array_column($v['vinculos'], 'id');

// Cold start masivo: crear TODOS los pendientes en el maestro.
$r = $maestro->crearDesdePendientes(PDC_M_PROJECT_A, $ids, PDC_M_MARCA);
$assert($r['ok'] === true && $r['creados'] === 4 && $r['vinculados'] === 4, 'Creación masiva: 4 creados y vinculados.');
$v2 = $maestro->vinculos(PDC_M_PROJECT_A);
$assert($v2['resumen']['pendientes'] === 0 && $v2['resumen']['cobertura'] === 100.0, 'Cobertura 100% tras el masivo.');
$assert($v2['vinculos'][0]['estado'] !== 'pendiente' && $v2['vinculos'][0]['maestroDescripcion'] !== null, 'Vínculos con maestro asignado.');

// Segundo import (contenido idéntico) → auto-match 100% sin intervención.
$tmp2 = sys_get_temp_dir() . '/pdc_m_v2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2 = $importSvc->previewDesdeArchivo($tmp2, 'v2.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$c2 = $importSvc->confirmar($p2['importToken'], PDC_M_PROJECT_A);
$g = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g['total'] === 4 && $g['auto'] === 4 && $g['pendientes'] === 0, 'Re-import: auto-match 100% contra el maestro poblado.');

// Idempotencia del masivo: repetir con los mismos ids no duplica el catálogo.
$antes = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA])->fetchColumn();
$maestro->crearDesdePendientes(PDC_M_PROJECT_A, $ids, PDC_M_MARCA);
$despues = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA])->fetchColumn();
$assert($antes === $despues && $antes === 4, 'El masivo repetido no duplica insumos del maestro.');

// Sugerencias: buscar para un pendiente artificial con texto similar.
$db->query(
    'INSERT INTO pdc_insumo_vinculos (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, \'pendiente\')',
    [PDC_M_PROJECT_A, $g['versionId'], 'TEJA ZINC CALIBRE 34', 'M2', 'Teja Zinc calibre 34', 'MAT', ],
);
$pendienteId = (int) $db->lastInsertId();
$sug = $maestro->sugerencias(PDC_M_PROJECT_A, $pendienteId);
$assert(count($sug) >= 1 && str_contains($sug[0]['descripcion'], 'TEJA'), 'Sugerencias por tokens encuentran TEJA DE ZINC.');

// Vincular manual a una sugerencia.
$rv = $maestro->vincular(PDC_M_PROJECT_A, $pendienteId, $sug[0]['id']);
$assert($rv['ok'] === true, 'Vinculación manual confirma.');
$assert($maestro->vincular(PDC_M_PROJECT_A, 999999999, $sug[0]['id'])['code'] === 'VINCULO_INVALIDO', 'Vínculo inexistente rechazado.');

// Crear manual duplicado → MAESTRO_DUPLICADO.
$dup = $maestro->crearManual(PDC_M_PROJECT_A, 'Teja de Zinc', 'M2', 'MAT', PDC_M_MARCA);
$assert($dup['ok'] === false && $dup['code'] === 'MAESTRO_DUPLICADO', 'Crear manual duplicado se rechaza.');

// Catálogo con búsqueda.
$cat = $maestro->catalogo('teja');
$assert(count($cat) >= 1 && str_contains($cat[0]['descripcion'], 'TEJA'), 'Catálogo filtra por búsqueda normalizada.');

foreach ([$tmpB, $tmp2] as $f) { @unlink($f); }
```

(y mantener al final del archivo `@unlink($tmp); $limpiar();` + exit — el `$limpiar()` de la parte 1 se mueve aquí al final.)

- [ ] **Step 2: RED** — métodos no existen.

- [ ] **Step 3: Implementar** (añadir a la clase):

```php
    public function sugerencias(int $projectId, int $vinculoId, int $limite = 8): array
    {
        $vinculo = $this->db->query(
            'SELECT descripcion_norm FROM pdc_insumo_vinculos WHERE project_id = ? AND id = ?',
            [$projectId, $vinculoId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($vinculo === false) {
            return [];
        }
        $tokens = array_values(array_filter(explode(' ', $vinculo['descripcion_norm']), static fn ($t) => mb_strlen($t) >= 4));
        if ($tokens === []) {
            return [];
        }
        $condiciones = implode(' + ', array_fill(0, count($tokens), '(descripcion_norm LIKE ?)'));
        $params = array_map(static fn ($t) => "%{$t}%", $tokens);
        $rows = $this->db->query(
            "SELECT id, descripcion, unidad, tipo_insumo, ({$condiciones}) AS coincidencias
             FROM general_maestro_insumos
             WHERE activo = 1
             HAVING coincidencias > 0
             ORDER BY coincidencias DESC, descripcion ASC
             LIMIT " . (int) $limite,
            $params,
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'descripcion' => $r['descripcion'],
            'unidad' => $r['unidad'],
            'tipoInsumo' => $r['tipo_insumo'],
        ], $rows);
    }

    public function vincular(int $projectId, int $vinculoId, int $maestroId): array
    {
        $existeVinculo = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_insumo_vinculos WHERE project_id = ? AND id = ?',
            [$projectId, $vinculoId],
        )->fetchColumn();
        $existeMaestro = (int) $this->db->query(
            'SELECT COUNT(*) FROM general_maestro_insumos WHERE id = ? AND activo = 1',
            [$maestroId],
        )->fetchColumn();
        if ($existeVinculo === 0 || $existeMaestro === 0) {
            return ['ok' => false, 'code' => 'VINCULO_INVALIDO'];
        }
        $this->db->query(
            "UPDATE pdc_insumo_vinculos SET maestro_id = ?, estado = 'confirmado' WHERE project_id = ? AND id = ?",
            [$maestroId, $projectId, $vinculoId],
        );
        return ['ok' => true];
    }

    public function crearDesdePendientes(int $projectId, array $vinculoIds, string $usuario): array
    {
        $ids = array_values(array_filter(array_map('intval', $vinculoIds), static fn ($i) => $i > 0));
        if ($ids === []) {
            return ['ok' => true, 'creados' => 0, 'vinculados' => 0];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $pendientes = $this->db->query(
            "SELECT id, descripcion_norm, unidad, descripcion_original, tipo_insumo
             FROM pdc_insumo_vinculos
             WHERE project_id = ? AND estado = 'pendiente' AND id IN ({$marcadores})",
            array_merge([$projectId], $ids),
        )->fetchAll(\PDO::FETCH_ASSOC);

        $creados = 0;
        $vinculados = 0;
        $this->db->beginTransaction();
        try {
            foreach ($pendientes as $p) {
                $maestroId = $this->db->query(
                    'SELECT id FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
                    [$p['descripcion_norm'], $p['unidad']],
                )->fetchColumn();
                if ($maestroId === false) {
                    $this->db->query(
                        'INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
                         VALUES (?, ?, ?, ?, 1, ?, NOW())',
                        [$p['descripcion_original'], $p['descripcion_norm'], $p['unidad'], $p['tipo_insumo'], $usuario],
                    );
                    $maestroId = (int) $this->db->lastInsertId();
                    $creados++;
                }
                $this->db->query(
                    "UPDATE pdc_insumo_vinculos SET maestro_id = ?, estado = 'confirmado' WHERE project_id = ? AND id = ?",
                    [(int) $maestroId, $projectId, (int) $p['id']],
                );
                $vinculados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        return ['ok' => true, 'creados' => $creados, 'vinculados' => $vinculados];
    }

    public function crearManual(int $projectId, string $descripcion, string $unidad, string $tipoInsumo, string $usuario): array
    {
        $norm = self::normalizar($descripcion);
        $unidad = trim($unidad);
        if ($norm === '' || $unidad === '') {
            return ['ok' => false, 'code' => 'VINCULO_INVALIDO'];
        }
        $existe = (int) $this->db->query(
            'SELECT COUNT(*) FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
            [$norm, $unidad],
        )->fetchColumn();
        if ($existe > 0) {
            return ['ok' => false, 'code' => 'MAESTRO_DUPLICADO'];
        }
        $this->db->query(
            'INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
             VALUES (?, ?, ?, ?, 1, ?, NOW())',
            [mb_substr(trim($descripcion), 0, 500), $norm, mb_substr($unidad, 0, 20), mb_substr(trim($tipoInsumo), 0, 100), $usuario],
        );
        return ['ok' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function catalogo(?string $busqueda = null, int $limite = 200): array
    {
        $where = 'activo = 1';
        $params = [];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $where .= ' AND descripcion_norm LIKE ?';
            $params[] = '%' . self::normalizar($busqueda) . '%';
        }
        $rows = $this->db->query(
            "SELECT id, descripcion, unidad, tipo_insumo, creado_por, created_at
             FROM general_maestro_insumos WHERE {$where} ORDER BY descripcion ASC LIMIT " . (int) $limite,
            $params,
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'descripcion' => $r['descripcion'],
            'unidad' => $r['unidad'],
            'tipoInsumo' => $r['tipo_insumo'],
            'creadoPor' => $r['creado_por'],
            'createdAt' => $r['created_at'],
        ], $rows);
    }
```

- [ ] **Step 4: GREEN + PHPStan + gates BD** — test completo exit 0; PHPStan OK; `test_global_table_safety` OK.

- [ ] **Step 5: Commit**

```bash
git add src/Services/Pdc/MaestroInsumosService.php tests/test_pdc_v2_maestro.php
git commit -m "feat(pdc-v2): acciones del maestro — masivo cold-start, vincular con sugerencias, crear manual y catálogo"
```

---

### Task 5: Controller + 7 rutas (lps-aia)

**Files:**
- Create: `src/Controllers/Api/PlanComprasMaestroController.php`
- Modify: `public/index.php` (6 rutas tras las de presupuesto)

**Interfaces:**
- Consumes: `MaestroInsumosService`, trait `PlanComprasJsonRespuestas`, `RbacService`, `CsrfTokenManager`.
- Produces (contrato HTTP para T6/T7):
  - `GET /plan-compras/api/maestro?busqueda=` → `{ok,data:{insumos:[...]}}` (lps.pdc.ver)
  - `GET /plan-compras/api/maestro/vinculos[?versionId=N]` → `{ok,data:{version,resumen,vinculos}}` | `NO_VERSION` 404 (lps.pdc.ver)
  - `GET /plan-compras/api/maestro/sugerencias?vinculoId=N` → `{ok,data:{sugerencias:[...]}}` (lps.pdc.ver)
  - `POST /plan-compras/api/maestro/vinculos/generar` `{versionId?}` → resumen | `NO_VERSION` (lps.pdc.maestro + CSRF)
  - `POST /plan-compras/api/maestro/vinculos/confirmar` `{vinculoId, maestroId}` → `{ok:true}` | `VINCULO_INVALIDO` 422
  - `POST /plan-compras/api/maestro/crear-desde-pendientes` `{vinculoIds:[...]}` → `{creados,vinculados,resumen}` (re-consulta resumen tras crear)
  - `POST /plan-compras/api/maestro` `{descripcion,unidad,tipoInsumo}` → `{id}` | `MAESTRO_DUPLICADO` 409

- [ ] **Step 1: Rutas en `public/index.php`** (tras la ruta `arbol` de A1.5):

```php
// Api/Plan de Compras v2 — maestro de insumos (A2)
$router->get('/plan-compras/api/maestro', [\App\Controllers\Api\PlanComprasMaestroController::class, 'catalogo']);
$router->get('/plan-compras/api/maestro/vinculos', [\App\Controllers\Api\PlanComprasMaestroController::class, 'vinculos']);
$router->get('/plan-compras/api/maestro/sugerencias', [\App\Controllers\Api\PlanComprasMaestroController::class, 'sugerencias']);
$router->post('/plan-compras/api/maestro/vinculos/generar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'generar']);
$router->post('/plan-compras/api/maestro/vinculos/confirmar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'confirmar']);
$router->post('/plan-compras/api/maestro/crear-desde-pendientes', [\App\Controllers\Api\PlanComprasMaestroController::class, 'crearDesdePendientes']);
$router->post('/plan-compras/api/maestro', [\App\Controllers\Api\PlanComprasMaestroController::class, 'crearManual']);
```

- [ ] **Step 2: Controller completo**

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\MaestroInsumosService;

/**
 * Endpoints del maestro global de insumos (PDC v2 / Fase A2).
 * Lectura: lps.pdc.ver. Escritura: lps.pdc.maestro + CSRF plan_compras_v2.
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasMaestroController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private MaestroInsumosService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new MaestroInsumosService($this->db);
    }

    /** GET /plan-compras/api/maestro?busqueda= */
    public function catalogo(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $busqueda = isset($_GET['busqueda']) ? (string) $_GET['busqueda'] : null;
        $this->ok(['insumos' => $this->service->catalogo($busqueda)]);
    }

    /** GET /plan-compras/api/maestro/vinculos[?versionId=N] */
    public function vinculos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $v = $this->service->vinculos($projectId, $this->versionIdParam());
        if ($v === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($v);
    }

    /** GET /plan-compras/api/maestro/sugerencias?vinculoId=N */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $vinculoId = filter_var($_GET['vinculoId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($vinculoId === false || $vinculoId === null) {
            $this->fail('VINCULO_INVALIDO', 'vinculoId inválido.', 422);
            return;
        }
        $this->ok(['sugerencias' => $this->service->sugerencias($projectId, $vinculoId)]);
    }

    /** POST /plan-compras/api/maestro/vinculos/generar {versionId?} */
    public function generar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $versionId = isset($body['versionId']) ? (int) $body['versionId'] : null;
        $r = $this->service->generarVinculos($projectId, $versionId !== null && $versionId > 0 ? $versionId : null);
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** POST /plan-compras/api/maestro/vinculos/confirmar {vinculoId, maestroId} */
    public function confirmar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->vincular($projectId, (int) ($body['vinculoId'] ?? 0), (int) ($body['maestroId'] ?? 0));
        if (!$r['ok']) {
            $this->fail('VINCULO_INVALIDO', 'El vínculo o el insumo del maestro no existen.', 422);
            return;
        }
        $this->ok(['confirmado' => 1]);
    }

    /** POST /plan-compras/api/maestro/crear-desde-pendientes {vinculoIds:[]} */
    public function crearDesdePendientes(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ids = is_array($body['vinculoIds'] ?? null) ? $body['vinculoIds'] : [];
        $r = $this->service->crearDesdePendientes($projectId, $ids, $this->usuario());
        $vin = $this->service->vinculos($projectId);
        $this->ok(['creados' => $r['creados'], 'vinculados' => $r['vinculados'], 'resumen' => $vin['resumen'] ?? null]);
    }

    /** POST /plan-compras/api/maestro {descripcion, unidad, tipoInsumo} */
    public function crearManual(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->crearManual(
            $projectId,
            (string) ($body['descripcion'] ?? ''),
            (string) ($body['unidad'] ?? ''),
            (string) ($body['tipoInsumo'] ?? ''),
            $this->usuario(),
        );
        if (!$r['ok']) {
            if ($r['code'] === 'MAESTRO_DUPLICADO') {
                $this->fail('MAESTRO_DUPLICADO', 'Ya existe un insumo con esa descripción y unidad en el maestro.', 409);
            } else {
                $this->fail('VINCULO_INVALIDO', 'Descripción y unidad son obligatorias.', 422);
            }
            return;
        }
        $this->ok(['id' => $r['id']]);
    }

    // ── guards ──────────────────────────────────────────────

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        return $projectId;
    }

    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.pdc.maestro')) {
            $this->fail('FORBIDDEN', 'No autorizado para administrar el maestro de insumos.', 403);
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

    private function body(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }
}
```

- [ ] **Step 3: Verificación** — `php -l` + PHPStan del controller; regresiones `test_pdc_v2_contexto.php` y `test_pdc_v2_maestro.php` exit 0.

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/Api/PlanComprasMaestroController.php public/index.php
git commit -m "feat(pdc-v2): endpoints del maestro de insumos con RBAC lps.pdc.maestro y CSRF"
```

---

### Task 6: Tipos + reducer del maestro en la SPA (plan-de-compras, TDD)

**Files:**
- Modify: `src/lib/types.ts`
- Create: `src/lib/maestroState.ts`
- Test: `src/lib/maestroState.test.ts`

**Interfaces:**
- Produces:
  - Tipos: `VinculoInsumo {id:number; descripcionOriginal:string; descripcionNorm:string; unidad:string; tipoInsumo:string; cantidadTotal:number; valorTotal:number; apariciones:number; maestroId:number|null; maestroDescripcion:string|null; estado:'pendiente'|'auto'|'confirmado'}`, `ResumenVinculos {total:number; auto:number; confirmados:number; pendientes:number; cobertura:number}`, `MaestroInsumo {id:number; descripcion:string; unidad:string; tipoInsumo:string; creadoPor?:string; createdAt?:string}`, `SugerenciaMaestro {id:number; descripcion:string; unidad:string; tipoInsumo:string}`.
  - `maestroState.ts`: `MaestroState {seleccion: Set<number>; vinculando: VinculoInsumo | null; ocupado: boolean; mensaje: string | null}`; acciones `TOGGLE_SEL {id}`, `SEL_TODOS {ids:number[]}`, `LIMPIAR_SEL`, `ABRIR_VINCULAR {vinculo}`, `CERRAR_VINCULAR`, `OCUPADO`, `LISTO {mensaje?:string}`, `FALLO {mensaje}`; `maestroReducer`, `estadoInicialMaestro`.

- [ ] **Step 1: Branch** — `cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git checkout main -q && git checkout -b pdc-a2-maestro` *(si A1.5 aún no está en main, partir de la rama `pdc-a15-visor` mergeada — el controlador de la ejecución decide la base y lo anota)*.

- [ ] **Step 2: Test que falla** — `src/lib/maestroState.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { estadoInicialMaestro, maestroReducer } from './maestroState'
import type { VinculoInsumo } from './types'

const vinculo: VinculoInsumo = {
  id: 7, descripcionOriginal: 'Teja', descripcionNorm: 'TEJA', unidad: 'M2', tipoInsumo: 'MAT',
  cantidadTotal: 1, valorTotal: 100, apariciones: 1, maestroId: null, maestroDescripcion: null, estado: 'pendiente',
}

describe('maestroReducer', () => {
  it('toggle de selección agrega y quita ids', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'TOGGLE_SEL', id: 7 })
    expect(s.seleccion.has(7)).toBe(true)
    s = maestroReducer(s, { type: 'TOGGLE_SEL', id: 7 })
    expect(s.seleccion.has(7)).toBe(false)
  })

  it('SEL_TODOS reemplaza la selección y LIMPIAR_SEL la vacía', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'SEL_TODOS', ids: [1, 2, 3] })
    expect([...s.seleccion]).toEqual([1, 2, 3])
    s = maestroReducer(s, { type: 'LIMPIAR_SEL' })
    expect(s.seleccion.size).toBe(0)
  })

  it('abrir/cerrar el panel de vinculación', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'ABRIR_VINCULAR', vinculo })
    expect(s.vinculando?.id).toBe(7)
    s = maestroReducer(s, { type: 'CERRAR_VINCULAR' })
    expect(s.vinculando).toBeNull()
  })

  it('OCUPADO/LISTO/FALLO gobiernan ocupado y mensaje; LISTO limpia selección y panel', () => {
    let s = maestroReducer(estadoInicialMaestro, { type: 'SEL_TODOS', ids: [1] })
    s = maestroReducer(s, { type: 'ABRIR_VINCULAR', vinculo })
    s = maestroReducer(s, { type: 'OCUPADO' })
    expect(s.ocupado).toBe(true)
    s = maestroReducer(s, { type: 'LISTO', mensaje: 'Hecho' })
    expect(s.ocupado).toBe(false)
    expect(s.mensaje).toBe('Hecho')
    expect(s.seleccion.size).toBe(0)
    expect(s.vinculando).toBeNull()
    s = maestroReducer(s, { type: 'FALLO', mensaje: 'Error X' })
    expect(s.mensaje).toBe('Error X')
    expect(s.ocupado).toBe(false)
  })
})
```

- [ ] **Step 3: RED**, luego **implementar**:

```ts
import type { VinculoInsumo } from './types'

export type MaestroState = {
  seleccion: Set<number>
  vinculando: VinculoInsumo | null
  ocupado: boolean
  mensaje: string | null
}

export type MaestroAction =
  | { type: 'TOGGLE_SEL'; id: number }
  | { type: 'SEL_TODOS'; ids: number[] }
  | { type: 'LIMPIAR_SEL' }
  | { type: 'ABRIR_VINCULAR'; vinculo: VinculoInsumo }
  | { type: 'CERRAR_VINCULAR' }
  | { type: 'OCUPADO' }
  | { type: 'LISTO'; mensaje?: string }
  | { type: 'FALLO'; mensaje: string }

export const estadoInicialMaestro: MaestroState = { seleccion: new Set(), vinculando: null, ocupado: false, mensaje: null }

export function maestroReducer(state: MaestroState, action: MaestroAction): MaestroState {
  switch (action.type) {
    case 'TOGGLE_SEL': {
      const seleccion = new Set(state.seleccion)
      if (seleccion.has(action.id)) seleccion.delete(action.id)
      else seleccion.add(action.id)
      return { ...state, seleccion }
    }
    case 'SEL_TODOS':
      return { ...state, seleccion: new Set(action.ids) }
    case 'LIMPIAR_SEL':
      return { ...state, seleccion: new Set() }
    case 'ABRIR_VINCULAR':
      return { ...state, vinculando: action.vinculo, mensaje: null }
    case 'CERRAR_VINCULAR':
      return { ...state, vinculando: null }
    case 'OCUPADO':
      return { ...state, ocupado: true, mensaje: null }
    case 'LISTO':
      return { seleccion: new Set(), vinculando: null, ocupado: false, mensaje: action.mensaje ?? null }
    case 'FALLO':
      return { ...state, ocupado: false, mensaje: action.mensaje }
  }
}
```

(+ los 4 tipos en `types.ts`, formas EXACTAS del bloque Interfaces.)

- [ ] **Step 4: GREEN** — suite completa (24 de A1.5 + 4 = 28) + build.

- [ ] **Step 5: Commit**

```bash
git add src/lib/types.ts src/lib/maestroState.ts src/lib/maestroState.test.ts
git commit -m "feat(pdc): tipos y reducer del maestro de insumos (selección múltiple y panel de vinculación)"
```

---

### Task 7: Vista Maestro de Insumos (plan-de-compras)

**Files:**
- Modify (REEMPLAZO total): `src/pages/MaestroInsumos.tsx`
- Modify: `src/styles.css`

**Interfaces:**
- Consumes: `apiGet`/`apiPost`, `maestroReducer`, tipos T6, contrato HTTP T5.
- Produces (contrato e2e T8): `h1` "Maestro de insumos"; resumen `[data-testid="pdc-maestro-cobertura"]` con texto `Cobertura: X%`; grilla pendientes `[data-testid="pdc-maestro-pendientes"]` con checkbox por fila (columna con `checkboxSelection` manual vía celda clicable `[data-col="sel"]`… KISS: la selección se hace con un botón "Seleccionar todos" + clic en la fila) — implementación concreta: botón `[data-testid="pdc-maestro-sel-todos"]`, botón `[data-testid="pdc-maestro-crear-masivo"]` (deshabilitado sin selección), clic en fila pendiente togglea su selección (columna "✓" muestra ● si seleccionada); botón por fila NO — el panel de vinculación se abre con doble clic; grilla catálogo `[data-testid="pdc-maestro-catalogo"]` con búsqueda `[data-testid="pdc-maestro-busqueda"]`; panel vinculación `[data-testid="pdc-maestro-panel"]` con sugerencias clicables.

- [ ] **Step 1: Reemplazar `MaestroInsumos.tsx`**

```tsx
import { useCallback, useEffect, useMemo, useReducer, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ClientSideRowModelModule, ModuleRegistry, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, RowDoubleClickedEvent } from 'ag-grid-community'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { estadoInicialMaestro, maestroReducer } from '../lib/maestroState'
import type { MaestroInsumo, ResumenVinculos, SugerenciaMaestro, VinculoInsumo } from '../lib/types'

ModuleRegistry.registerModules([ClientSideRowModelModule])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const moneda = (v: number | null | undefined) => (v == null ? '' : `$ ${v.toLocaleString('es-CO')}`)

export default function MaestroInsumos() {
  const [state, dispatch] = useReducer(maestroReducer, estadoInicialMaestro)
  const [vinculos, setVinculos] = useState<VinculoInsumo[]>([])
  const [resumen, setResumen] = useState<ResumenVinculos | null>(null)
  const [catalogo, setCatalogo] = useState<MaestroInsumo[]>([])
  const [busqueda, setBusqueda] = useState('')
  const [sugerencias, setSugerencias] = useState<SugerenciaMaestro[]>([])
  const [sinPresupuesto, setSinPresupuesto] = useState(false)

  const cargar = useCallback(async () => {
    try {
      const g = await apiPost<ResumenVinculos & { versionId: number }>('/plan-compras/api/maestro/vinculos/generar', {})
      void g
      const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
      setResumen(v.resumen)
      setVinculos(v.vinculos)
      setSinPresupuesto(false)
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinPresupuesto(true)
      else if (e instanceof PdcApiError && e.code === 'FORBIDDEN') {
        // Sin permiso de escritura: cargar solo lectura.
        try {
          const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
          setResumen(v.resumen)
          setVinculos(v.vinculos)
        } catch { setSinPresupuesto(true) }
      } else dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }, [])

  const cargarCatalogo = useCallback((q: string) => {
    apiGet<{ insumos: MaestroInsumo[] }>(`/plan-compras/api/maestro?busqueda=${encodeURIComponent(q)}`)
      .then((d) => setCatalogo(d.insumos))
      .catch(() => setCatalogo([]))
  }, [])

  useEffect(() => { void cargar() }, [cargar])
  useEffect(() => { cargarCatalogo(busqueda) }, [busqueda, cargarCatalogo])

  useEffect(() => {
    if (!state.vinculando) { setSugerencias([]); return }
    apiGet<{ sugerencias: SugerenciaMaestro[] }>(`/plan-compras/api/maestro/sugerencias?vinculoId=${state.vinculando.id}`)
      .then((d) => setSugerencias(d.sugerencias))
      .catch(() => setSugerencias([]))
  }, [state.vinculando])

  const pendientes = useMemo(() => vinculos.filter((v) => v.estado === 'pendiente'), [vinculos])

  const colsPendientes: ColDef<VinculoInsumo>[] = useMemo(() => [
    {
      headerName: '✓', width: 60, field: 'id',
      valueFormatter: (p) => (state.seleccion.has(p.value as number) ? '●' : ''),
    },
    { field: 'descripcionOriginal', headerName: 'Insumo', flex: 1, minWidth: 260 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 150 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'apariciones', headerName: 'Usos', width: 80 },
    { field: 'valorTotal', headerName: 'Valor total', width: 140, valueFormatter: (p) => moneda(p.value) },
  ], [state.seleccion])

  const colsCatalogo: ColDef<MaestroInsumo>[] = useMemo(() => [
    { field: 'descripcion', headerName: 'Insumo', flex: 1, minWidth: 280 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 160 },
  ], [])

  const onPendienteClick = (e: CellClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'TOGGLE_SEL', id: e.data.id })
  }
  const onPendienteDoble = (e: RowDoubleClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'ABRIR_VINCULAR', vinculo: e.data })
  }

  const crearMasivo = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ creados: number; vinculados: number }>('/plan-compras/api/maestro/crear-desde-pendientes', {
        vinculoIds: [...state.seleccion],
      })
      dispatch({ type: 'LISTO', mensaje: `${r.creados} insumos creados en el maestro, ${r.vinculados} vinculados.` })
      await cargar()
      cargarCatalogo(busqueda)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const vincularA = async (maestroId: number) => {
    if (!state.vinculando) return
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/maestro/vinculos/confirmar', { vinculoId: state.vinculando.id, maestroId })
      dispatch({ type: 'LISTO', mensaje: 'Insumo vinculado.' })
      await cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  if (sinPresupuesto) {
    return (
      <section className="pdc-page">
        <header className="pdc-header"><h1>Maestro de insumos</h1></header>
        <div className="pdc-bloque pdc-vacio" data-testid="pdc-maestro-vacio">
          Este proyecto aún no tiene un presupuesto importado. Ve a <strong>Ensamble → Importar</strong>.
        </div>
      </section>
    )
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Maestro de insumos</h1>
          <p>Catálogo único de AIA. Vincula o crea los insumos del presupuesto activo.</p>
        </div>
        {resumen && (
          <p data-testid="pdc-maestro-cobertura" className="pdc-cobertura">
            Cobertura: {resumen.cobertura}% · {resumen.pendientes} pendientes de {resumen.total}
          </p>
        )}
      </header>

      {state.mensaje && <div className="pdc-exito" role="status">{state.mensaje}</div>}

      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Pendientes por vincular ({pendientes.length})</h2>
          <div>
            <button type="button" data-testid="pdc-maestro-sel-todos" onClick={() => dispatch({ type: 'SEL_TODOS', ids: pendientes.map((p) => p.id) })}>
              Seleccionar todos
            </button>{' '}
            <button
              type="button"
              data-testid="pdc-maestro-crear-masivo"
              disabled={state.seleccion.size === 0 || state.ocupado}
              onClick={crearMasivo}
            >
              {state.ocupado ? 'Procesando…' : `Crear ${state.seleccion.size} en el maestro`}
            </button>
          </div>
        </div>
        <p className="pdc-ayuda">Clic = seleccionar · doble clic = vincular a un insumo existente.</p>
        <div style={{ height: 300 }} data-testid="pdc-maestro-pendientes">
          <AgGridReact<VinculoInsumo>
            theme={pdcTheme}
            rowData={pendientes}
            columnDefs={colsPendientes}
            getRowId={(p) => String(p.data.id)}
            onCellClicked={onPendienteClick}
            onRowDoubleClicked={onPendienteDoble}
          />
        </div>
      </div>

      {state.vinculando && (
        <div className="pdc-bloque pdc-panel" data-testid="pdc-maestro-panel">
          <h2>Vincular «{state.vinculando.descripcionOriginal}» ({state.vinculando.unidad})</h2>
          {sugerencias.length === 0 ? (
            <p>Sin sugerencias — créalo con la acción masiva o búscalo en el catálogo.</p>
          ) : (
            <ul>
              {sugerencias.map((s) => (
                <li key={s.id}>
                  <button type="button" disabled={state.ocupado} onClick={() => vincularA(s.id)}>
                    {s.descripcion} ({s.unidad})
                  </button>
                </li>
              ))}
            </ul>
          )}
          <button type="button" onClick={() => dispatch({ type: 'CERRAR_VINCULAR' })}>Cerrar</button>
        </div>
      )}

      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Catálogo global</h2>
          <input
            data-testid="pdc-maestro-busqueda"
            type="search"
            placeholder="Buscar insumo…"
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
          />
        </div>
        <div style={{ height: 280 }} data-testid="pdc-maestro-catalogo">
          <AgGridReact<MaestroInsumo>
            theme={pdcTheme}
            rowData={catalogo}
            columnDefs={colsCatalogo}
            getRowId={(p) => String(p.data.id)}
          />
        </div>
      </div>
    </section>
  )
}
```

- [ ] **Step 2: Estilos** (añadir):

```css
.pdc-fila-acciones { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.pdc-ayuda { opacity: 0.6; font-size: 12px; margin: 4px 0 8px; }
.pdc-cobertura { font-weight: 600; color: #9fd3ae; }
.pdc-panel { border: 1px solid #69b578; border-radius: 8px; padding: 12px 16px; }
.pdc-panel ul { list-style: none; padding: 0; margin: 8px 0; display: flex; flex-direction: column; gap: 6px; }
.pdc-fila-acciones input[type='search'] { background: #2c2c2e; color: #f4f1ea; border: 1px solid #3a3a3c; border-radius: 6px; padding: 6px 10px; min-width: 240px; }
```

(El enlace de nav "Maestro" a `/ensamble/maestro` ya existe desde A1-T8 — verificar; si no es visible en la nav, añadir `<NavLink to="/ensamble/maestro" className="pdc-nav-link">Maestro</NavLink>`.)

- [ ] **Step 3: Gates** — `npm run test` (28) + `npm run build` OK. *(La página de contexto que vivía aquí desaparece: el e2e de fundación asserta `pdc-contexto` en `/ensamble/maestro` — se ACTUALIZA en T8 para asertar el maestro real; no debilitar, reemplazar por asserts equivalentes del nuevo contenido.)*

- [ ] **Step 4: Commit**

```bash
git add src/pages/MaestroInsumos.tsx src/styles.css src/App.tsx
git commit -m "feat(pdc): vista del maestro de insumos — pendientes con acción masiva, vinculación con sugerencias y catálogo"
```

---

### Task 8: Bundle + e2e + actualización del e2e de fundación + CLAUDE.md

**Files:**
- Generated (lps-aia): `public/pdc-app/**`
- Create (lps-aia): `tests/browser/pdc-v2-maestro.spec.mjs`
- Modify (lps-aia): `tests/browser/pdc-v2-fundacion.spec.mjs` (los asserts de `pdc-contexto`/grilla de contexto se reemplazan por: `h1` "Maestro de insumos" visible en `#/ensamble/maestro` — el bootstrap y el assert HTTP del contexto se conservan intactos)
- Modify (plan-de-compras): `CLAUDE.md` (estado: A2 implementada)

**Interfaces:**
- Consumes: helpers e2e, fixture import, selectores T7.

- [ ] **Step 1: Sync + commit bundle** (`npm run sync`; commit `public/pdc-app` en lps-aia).

- [ ] **Step 2: Spec `tests/browser/pdc-v2-maestro.spec.mjs`**

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('maestro: cold start masivo y re-import con auto-match', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Import fresco para tener versión activa con vínculos regenerables.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // Maestro: la carga genera vínculos.
    await page.locator('nav >> text=Maestro').click();
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    const pendientesGrid = page.locator('[data-testid="pdc-maestro-pendientes"]');
    const cobertura = page.locator('[data-testid="pdc-maestro-cobertura"]');

    // Si hay pendientes (cold start o insumos nuevos): masivo → cobertura 100%.
    const textoCob = await cobertura.innerText();
    if (!textoCob.includes('Cobertura: 100%')) {
      await page.locator('[data-testid="pdc-maestro-sel-todos"]').click();
      await page.locator('[data-testid="pdc-maestro-crear-masivo"]').click();
      await expect(cobertura).toContainText('Cobertura: 100%', { timeout: 20000 });
    }
    await expect(cobertura).toContainText('0 pendientes', { timeout: 15000 });

    // El catálogo global contiene los insumos del fixture.
    const catalogo = page.locator('[data-testid="pdc-maestro-catalogo"]');
    await expect(catalogo.locator('.ag-cell', { hasText: 'TEJA DE ZINC' }).first()).toBeVisible({ timeout: 15000 });

    // Búsqueda del catálogo.
    await page.locator('[data-testid="pdc-maestro-busqueda"]').fill('concreto');
    await expect(catalogo.locator('.ag-cell', { hasText: 'CONCRETO 4000PSI' }).first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Actualizar `pdc-v2-fundacion.spec.mjs`** — reemplazar los DOS asserts finales de contenido (`pdc-contexto` contiene el nombre; celda `${projectId} — ${name}`) por:

```js
    // La página del maestro (A2) reemplazó la página de contexto de la fundación.
    await expect(page.locator('h1')).toContainText('Maestro de insumos', { timeout: 15000 });
```

manteniendo intactos: status < 400, asserts de `window.__PDC_BOOTSTRAP__`, assert HTTP `getJson` del contexto, y el check de Fatal error.

- [ ] **Step 4: Correr los 4 e2e**

```bash
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-maestro.spec.mjs tests/browser/pdc-v2-visor.spec.mjs tests/browser/pdc-v2-import.spec.mjs tests/browser/pdc-v2-fundacion.spec.mjs --workers=1
```

Expected: **4 passed**.

- [ ] **Step 5: Commits de cierre**

```bash
git add -f tests/browser/pdc-v2-maestro.spec.mjs tests/browser/pdc-v2-fundacion.spec.mjs
git commit -m "test(pdc-v2): e2e del maestro (masivo + auto-match) y fundación actualizada a la vista A2"
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
# CLAUDE.md: actualizar la línea de estado → "Fases A1, A1.5 y A2 implementadas — … maestro de insumos global (#/ensamble/maestro) con cola de vínculos y catálogo …" ajustando el conteo real de Vitest.
git add CLAUDE.md && git commit -m "docs(pdc): CLAUDE.md refleja la Fase A2 (maestro de insumos)"
```

---

## Verificación end-to-end

1. lps-aia: `test_pdc_v2_rbac_maestro` + `test_pdc_v2_maestro` + suite A1/A1.5 completa + gates BD + PHPStan (`src/Services/Pdc src/Controllers/Api/PlanComprasMaestroController.php`) → todo verde.
2. plan-de-compras: `npm run test` (28) + `npm run build`.
3. e2e: 4 specs passed.
4. Review final de rama (ambos repos) + merge local FF + verificación visual en el navegador (cola → masivo → cobertura 100% → catálogo).

## Riesgos anotados

- `SUM(estado = 'auto')` retorna NULL con 0 filas — cubierto por casts `(int)` en `resumen()`.
- Sugerencias por LIKE token a token: O(tokens) escaneos sobre el catálogo — aceptable a escala actual (cientos–miles); índice fulltext como mejora futura si crece.
- El reemplazo de la página de contexto obliga a actualizar el e2e de fundación (previsto en T8) — no debilitar los asserts de bootstrap/HTTP.
- La UNIQUE de `pdc_insumo_vinculos` usa prefijo 150 sobre `descripcion_norm` — colisión teórica de norms >150 chars idénticos en prefijo: aceptable (descripciones reales <150).
