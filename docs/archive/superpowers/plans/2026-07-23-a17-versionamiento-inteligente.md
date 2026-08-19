---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-23
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-23-a17-versionamiento-inteligente.md
resumen: Que cada cargue del presupuesto reciba un identificador automático (Versión N secuencial por proyecto + fecha), no cree versiones idénticas (anti-duplicado por…
---

# Fase A1.7: Versionamiento inteligente del importador — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cada cargue del presupuesto reciba un identificador automático (Versión N secuencial por proyecto + fecha), no cree versiones idénticas (anti-duplicado por hash del contenido, no del binario), y muestre al confirmar qué cambió vs la versión anterior (reusa el comparativo A1.6).

**Architecture:** Refuerzo de A1 sin endpoints nuevos. Una migración aditiva agrega `version_numero` y `contenido_hash` a `pdc_presupuesto_versiones` (+backfill). `PresupuestoImportService` calcula un hash de contenido canónico, auto-numera, detecta "sin cambios" vs la versión activa, y devuelve `versionIdAnterior` para el auto-comparativo. La SPA compone el display "Versión N · fecha", avisa cuando no hay cambios, y tras confirmar llama al endpoint `comparar` (A1.6) para el resumen. Spec: `docs/superpowers/specs/2026-07-23-a17-versionamiento-inteligente-design.md`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker lps-aia), FastRoute, React+TS+Vite+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{"ok":true,"data":...}` | `{"ok":false,"error":{...}}` (trait `PlanComprasJsonRespuestas`, reusar). RBAC escritura `lps.pdc.importar` (guard `guardEscritura` existente); lectura `lps.pdc.ver`. CSRF `plan_compras_v2`.
- Normalización canónica: `\App\Services\Pdc\MaestroInsumosService::normalizar()` — NO duplicar.
- **Hash de contenido** = SHA-256 de una serialización determinista (ordenada) de items + insumos — estable ante reordenamiento de filas y metadata del Excel; distinto del `archivo_hash` (binario) que se conserva.
- **Anti-duplicado solo contra la versión activa** del proyecto (no todo el historial): recargar exactamente lo activo → no crea versión; volver a una versión anterior distinta de la activa → sí crea.
- **Auto-numeración siempre:** `version_numero = COALESCE(MAX(version_numero),0)+1` por proyecto; el `version_label` (columna VERSION del Excel) queda como descripción secundaria.
- Migración aditiva (`ADD COLUMN` nullable / con DEFAULT), patrón `AFTER` + comentario de cabecera + backfill idempotente (como `20260723_pdc_import_token_idempotencia.sql`). `utf8mb4_unicode_ci`. Aplicar con `docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < archivo.sql`.
- Idempotencia por token existente (`versionPorToken`) se conserva; se extiende con `version_numero`.
- SPA: AG Grid Community selectivo (`ClientSideRowModelModule` + `ValidationModule` dev-only; nunca `AllCommunityModule`); flags/números 1/0, sin boolean cells; identificadores en inglés, UI/comentarios en español; Vitest excluye `.claude`.
- Tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) sobre el MySQL real; proyectos 999901/999902; cleanup por `DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (...)` (CASCADE); gates `test_global_table_safety`/`reconciliation` en verde.
- Comandos: tests PHP `docker compose exec app php tests/...` (stack `docker compose up -d app db`, live-mounted); SPA `npm run test`/`npm run build`; bundle `npm run sync`; e2e `npx playwright test ... --workers=1` desde lps-aia.
- Ramas: `pdc-a17-versionado` en ambos repos. Commits `feat(pdc-v2)` (lps-aia) / `feat(pdc)` (plan-de-compras).

---

## File Structure

**lps-aia (rama `pdc-a17-versionado`):**
```
database/migrations/20260723_pdc_v2_versionamiento_inteligente.sql  # T1
src/Services/Pdc/PresupuestoImportService.php                       # T2 (hashContenido, preview, confirmar, versiones)
tests/test_pdc_v2_versionado.php                                    # T2
src/Controllers/Api/PlanComprasImportController.php                 # T3 (propagar campos)
tests/browser/pdc-v2-versionado.spec.mjs                            # T6
public/pdc-app/**                                                   # T6 bundle
```

**plan-de-compras (rama `pdc-a17-versionado`):**
```
src/lib/types.ts             # T4 (VersionPresupuesto+numero, ImportPreview+sinCambios, ImportConfirmResult)
src/lib/importState.ts       # T4 (CONFIRMADO con payload)
src/lib/importState.test.ts  # T4
src/lib/versionLabel.ts      # T4 (helper etiquetaVersion) + .test.ts
src/pages/ImportarPresupuesto.tsx   # T5 (aviso sinCambios + auto-comparativo + display)
src/pages/VisorPresupuesto.tsx      # T5 (option display)
src/pages/ComparativoPresupuesto.tsx # T5 (option display)
CLAUDE.md                    # T6
docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md  # T6 (A1.7 implementada)
```

---

### Task 1: Migración — `version_numero` + `contenido_hash` + backfill (lps-aia)

**Files:**
- Create: `database/migrations/20260723_pdc_v2_versionamiento_inteligente.sql`

**Interfaces:**
- Produces: en `pdc_presupuesto_versiones`, `version_numero int NOT NULL DEFAULT 0`, `contenido_hash char(64) DEFAULT NULL`, `KEY idx_pdcpv_project_numero`. Backfill numera las existentes por `created_at` asc por proyecto.

- [ ] **Step 1: Branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git checkout main 2>/dev/null; git checkout -b pdc-a17-versionado
```
(Si `git checkout main` falla por un cambio ajeno sin commitear, crea la rama desde el commit actual de main: `git branch pdc-a17-versionado main && git checkout pdc-a17-versionado`.)

- [ ] **Step 2: Escribir la migración**

```sql
-- 20260723_pdc_v2_versionamiento_inteligente.sql
-- PDC v2 / Fase A1.7: versionamiento inteligente del importador.
-- version_numero: identificador secuencial por proyecto (Versión N), independiente
--   de la columna VERSION del Excel (que puede venir vacía, como en el DAPORTO real).
-- contenido_hash: SHA-256 del CONTENIDO canónico (items+insumos), no del binario;
--   permite detectar re-cargues idénticos vs la versión activa (anti-duplicado).
-- NULL en contenido_hash para versiones históricas previas a esta migración.

ALTER TABLE `pdc_presupuesto_versiones`
  ADD COLUMN `version_numero` int NOT NULL DEFAULT 0 AFTER `version_label`,
  ADD COLUMN `contenido_hash` char(64) DEFAULT NULL AFTER `archivo_hash`,
  ADD KEY `idx_pdcpv_project_numero` (`project_id`, `version_numero`);

-- Backfill idempotente: numera las versiones existentes por created_at asc dentro de cada proyecto.
UPDATE `pdc_presupuesto_versiones` v
JOIN (
  SELECT `id`, ROW_NUMBER() OVER (PARTITION BY `project_id` ORDER BY `created_at`, `id`) AS rn
  FROM `pdc_presupuesto_versiones`
) n ON n.`id` = v.`id`
SET v.`version_numero` = n.rn
WHERE v.`version_numero` = 0;
```

- [ ] **Step 3: Aplicar y verificar**

```bash
docker compose up -d app db
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/20260723_pdc_v2_versionamiento_inteligente.sql
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SHOW CREATE TABLE pdc_presupuesto_versiones\G" ' | grep -E "version_numero|contenido_hash|idx_pdcpv_project_numero"
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -N -e "SELECT project_id, MIN(version_numero), MAX(version_numero), COUNT(*) FROM pdc_presupuesto_versiones GROUP BY project_id;"'
```

Expected: las 2 columnas + el índice; y el backfill dejó cada proyecto numerado `1..N` (MIN=1, MAX=COUNT).

- [ ] **Step 4: Gates**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Expected: exit 0 ambos.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/20260723_pdc_v2_versionamiento_inteligente.sql
git commit -m "feat(pdc-v2): version_numero + contenido_hash en pdc_presupuesto_versiones (versionamiento inteligente)"
```

---

### Task 2: Service — hash de contenido + auto-numeración + anti-duplicado (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php`
- Test: `tests/test_pdc_v2_versionado.php`

**Interfaces:**
- Consumes: tablas A1 (con columnas T1), `MaestroInsumosService::normalizar()`.
- Produces:
  - `hashContenido(array $items, array $insumos): string` (público, testeable) — SHA-256 canónico estable ante reordenamiento.
  - `previewDesdeArchivo(...)` retorna además `sinCambios: bool` y `versionActiva: ?array{id,numero,label,createdAt}`; guarda `contenidoHash` en el meta del token.
  - `confirmar(token, projectId)` retorna además `versionNumero: int`, `versionIdAnterior: ?int`, `sinCambios: bool`. Si el contenido es idéntico a la activa → NO crea versión (retorna la activa con `sinCambios:true`).
  - `versiones(projectId)` incluye `versionNumero`.

- [ ] **Step 1: Escribir el test (falla)** — `tests/test_pdc_v2_versionado.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_VER_A = 999901;
const PDC_VER_B = 999902;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_VER_A, PDC_VER_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

// v2: mismas actividades pero un insumo cambia de precio (contenido distinto → nueva versión).
$fixtureV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01','PRELIMINARES','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01','CAMPAMENTO','01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01','INSTALACIONES','01.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01.01','CAMPAMENTO 18M2','01.01.01','M2',18,'',102,'PI_V2','APU-001',null,null,null,null,'',''],
        ['','TEJA DE ZINC','','M2',null,'',102,'PI_V2','',1.05,1.2,19,30000,'MAT-CUBIERTAS',''],
        ['','AYUDANTE','','HC',null,'',102,'PI_V2','',8.0,0.5,null,9500,'MANO DE OBRA',''],
        ['02','ESTRUCTURA','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01','CONCRETOS','02','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01','LOSAS','02.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01.01','LOSA MACIZA E=12','02.01.01','M3',40,'',102,'PI_V2','APU-002',null,null,null,null,'',''],
        ['','CONCRETO 4000PSI','','M3',null,'',102,'PI_V2','',1.0,1.05,19,620000,'MAT-CONCRETOS',''],
        ['','SERVICIO BOMBEO','','M3',null,'',102,'PI_V2','',1.0,1.0,null,28000,'EQUIPOS',''],
    ]);
};

echo "=== PDC v2: versionamiento inteligente ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-ver-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// --- Hash de contenido: estable ante reordenamiento de filas ---
$items = [
    ['codigo' => '02', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
    ['codigo' => '01', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
];
$itemsRev = array_reverse($items);
$insumos = [
    ['codigo_actividad' => '01.01.01.01', 'descripcion' => 'Teja de Zinc', 'unidad' => 'M2', 'cantidad_total' => 21.6, 'valor_total' => 540000],
    ['codigo_actividad' => '02.01.01.01', 'descripcion' => 'Concreto 4000PSI', 'unidad' => 'M3', 'cantidad_total' => 42, 'valor_total' => 26040000],
];
$insumosRev = array_reverse($insumos);
$assert($service->hashContenido($items, $insumos) === $service->hashContenido($itemsRev, $insumosRev), 'hashContenido estable ante reordenamiento.');
$assert($service->hashContenido($items, $insumos) !== $service->hashContenido($items, array_slice($insumos, 0, 1)), 'hashContenido distingue contenidos distintos.');

// --- Primer cargue → Versión 1 ---
$v1 = sys_get_temp_dir() . '/pdc_ver_v1.xlsx';
pdcFixturePresupuestoValido($v1);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_VER_A, 'tester');
$assert($p1['sinCambios'] === false && $p1['versionActiva'] === null, 'Primer preview: sin activa, sin "sin cambios".');
$c1 = $service->confirmar($p1['importToken'], PDC_VER_A);
$assert($c1['ok'] === true && $c1['versionNumero'] === 1 && $c1['versionIdAnterior'] === null && $c1['sinCambios'] === false, 'Confirmar 1 → Versión 1, sin anterior.');

// --- Re-cargue idéntico → sin cambios, NO crea versión ---
$v1b = sys_get_temp_dir() . '/pdc_ver_v1b.xlsx';
pdcFixturePresupuestoValido($v1b);
$p2 = $service->previewDesdeArchivo($v1b, 'v1b.xlsx', PDC_VER_A, 'tester');
$assert($p2['sinCambios'] === true && (int) $p2['versionActiva']['numero'] === 1, 'Preview idéntico avisa "sin cambios" (Versión 1 activa).');
$c2 = $service->confirmar($p2['importToken'], PDC_VER_A);
$assert($c2['ok'] === true && $c2['sinCambios'] === true && $c2['versionId'] === $c1['versionId'], 'Confirmar idéntico NO crea versión (retorna la activa).');
$total = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_VER_A])->fetchColumn();
$assert($total === 1, 'Sigue habiendo 1 sola versión tras el re-cargue idéntico.');

// --- Cargue con contenido distinto → Versión 2, con anterior ---
$v2 = sys_get_temp_dir() . '/pdc_ver_v2.xlsx';
$fixtureV2($v2);
$p3 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_VER_A, 'tester');
$assert($p3['sinCambios'] === false, 'Preview con contenido distinto: no "sin cambios".');
$c3 = $service->confirmar($p3['importToken'], PDC_VER_A);
$assert($c3['ok'] === true && $c3['versionNumero'] === 2 && $c3['versionIdAnterior'] === $c1['versionId'] && $c3['sinCambios'] === false, 'Confirmar distinto → Versión 2 con versionIdAnterior = V1.');

// --- versiones() incluye versionNumero; aislamiento por proyecto ---
$vers = $service->versiones(PDC_VER_A);
$assert(isset($vers[0]['versionNumero']) && $vers[0]['versionNumero'] === 2, 'versiones() trae versionNumero (la más reciente = 2).');
$assert($service->versiones(PDC_VER_B) === [], 'Aislamiento: proyecto B sin versiones.');

foreach ([$v1, $v1b, $v2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr y ver que falla**

Run: `docker compose up -d app db && docker compose exec app php tests/test_pdc_v2_versionado.php`
Expected: FAIL — `Call to undefined method ... hashContenido()`.

- [ ] **Step 3: Implementar** — cambios en `PresupuestoImportService.php`:

**(a) Añadir el helper `hashContenido()`** (público, tras el constructor):

```php
    /**
     * Hash SHA-256 del CONTENIDO canónico del presupuesto (items + insumos), estable
     * ante reordenamiento de filas y metadata del Excel. Base del anti-duplicado por contenido.
     */
    public function hashContenido(array $items, array $insumos): string
    {
        $itemLineas = array_map(static function (array $i): string {
            return implode('|', [
                (string) ($i['codigo'] ?? ''),
                (string) ($i['tipo_fila'] ?? ''),
                (string) ($i['unidad'] ?? ''),
                number_format((float) ($i['cantidad'] ?? 0), 4, '.', ''),
            ]);
        }, $items);
        sort($itemLineas, SORT_STRING);

        $insumoLineas = array_map(static function (array $x): string {
            return implode('|', [
                (string) ($x['codigo_actividad'] ?? ''),
                MaestroInsumosService::normalizar((string) ($x['descripcion'] ?? '')),
                (string) ($x['unidad'] ?? ''),
                number_format((float) ($x['cantidad_total'] ?? 0), 4, '.', ''),
                number_format((float) ($x['valor_total'] ?? 0), 2, '.', ''),
            ]);
        }, $insumos);
        sort($insumoLineas, SORT_STRING);

        return hash('sha256', implode("\n", $itemLineas) . "\n##\n" . implode("\n", $insumoLineas));
    }

    /** Versión activa del proyecto (con su número y hash de contenido), o null. */
    private function versionActivaDe(int $projectId): ?array
    {
        $row = $this->db->query(
            'SELECT id, version_numero, version_label, contenido_hash, created_at
             FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
            [$projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
```

**(b) `previewDesdeArchivo()`** — reemplazar el `return` (y añadir el cálculo del hash de contenido + sinCambios). Sustituir el bloque desde `$token = $this->store->guardar(...)` hasta el `return`:

```php
        $contenidoHash = $this->hashContenido($resultado['items'], $resultado['insumos']);
        $activa = $this->versionActivaDe($projectId);
        $sinCambios = $activa !== null && $activa['contenido_hash'] !== null && $activa['contenido_hash'] === $contenidoHash;

        $token = $this->store->guardar($rutaArchivo, [
            'nombre' => $nombreOriginal,
            'hash' => $hash,
            'contenidoHash' => $contenidoHash,
            'projectId' => $projectId,
            'usuario' => $usuario,
        ]);

        return [
            'ok' => true,
            'importToken' => $token,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
            'advertencias' => $advertencias,
            'sinCambios' => $sinCambios,
            'versionActiva' => $activa === null ? null : [
                'id' => (int) $activa['id'],
                'numero' => (int) $activa['version_numero'],
                'label' => $activa['version_label'],
                'createdAt' => $activa['created_at'],
            ],
        ];
```

**(c) `confirmar()`** — insertar la detección de "sin cambios" antes de la transacción, la auto-numeración y la captura del anterior dentro de ella, y extender el return. Reemplazar desde `$this->db->beginTransaction();` hasta el `return [...]` final:

```php
        $contenidoHash = $this->hashContenido($resultado['items'], $resultado['insumos']);
        $activa = $this->versionActivaDe($projectId);
        if ($activa !== null && $activa['contenido_hash'] !== null && $activa['contenido_hash'] === $contenidoHash) {
            // Anti-duplicado: el contenido es idéntico a la versión activa → no se crea una versión nueva.
            $this->store->eliminar($token);
            return [
                'ok' => true,
                'sinCambios' => true,
                'versionId' => (int) $activa['id'],
                'versionNumero' => (int) $activa['version_numero'],
                'versionLabel' => $activa['version_label'],
                'versionIdAnterior' => null,
                'resumen' => $resultado['resumen'],
            ];
        }
        $versionIdAnterior = $activa === null ? null : (int) $activa['id'];

        $this->db->beginTransaction();
        try {
            $numero = (int) $this->db->query(
                'SELECT COALESCE(MAX(version_numero), 0) + 1 FROM pdc_presupuesto_versiones WHERE project_id = ?',
                [$projectId],
            )->fetchColumn();
            $this->db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ? AND activa = 1', [$projectId]);
            $this->db->query(
                'INSERT INTO pdc_presupuesto_versiones
                    (project_id, version_label, version_numero, archivo_nombre, archivo_hash, contenido_hash, import_token, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                [
                    $projectId,
                    (string) ($resultado['versionLabel'] ?? ''),
                    $numero,
                    (string) ($meta['nombre'] ?? ''),
                    (string) ($meta['hash'] ?? ''),
                    $contenidoHash,
                    $token,
                    $resultado['resumen']['actividades'],
                    $resultado['resumen']['insumos'],
                    $resultado['resumen']['costoTotal'],
                    (string) ($meta['usuario'] ?? ''),
                ],
            );
            $versionId = (int) $this->db->lastInsertId();

            $idPorCodigo = [];
            foreach ($resultado['items'] as $item) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_items
                        (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad, id_apu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$projectId, $versionId, $item['codigo'], $item['codigo_padre'], $item['nivel'], $item['tipo_fila'], $item['descripcion'], $item['unidad'], $item['cantidad'], $item['id_apu']],
                );
                $idPorCodigo[$item['codigo']] = (int) $this->db->lastInsertId();
            }

            foreach ($resultado['insumos'] as $insumo) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_apu_insumos
                        (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $projectId, $versionId, $idPorCodigo[$insumo['codigo_actividad']],
                        $insumo['descripcion'], $insumo['tipo_insumo'], $insumo['unidad'],
                        $insumo['cant_apu'], $insumo['rendimiento'], $insumo['cantidad_total'],
                        $insumo['valor_unitario'], $insumo['valor_total'], $insumo['iva'],
                    ],
                );
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token); // un solo uso

        return [
            'ok' => true,
            'sinCambios' => false,
            'versionId' => $versionId,
            'versionNumero' => $numero,
            'versionLabel' => $resultado['versionLabel'],
            'versionIdAnterior' => $versionIdAnterior,
            'resumen' => $resultado['resumen'],
        ];
```

**(d) `versionPorToken()`** (retorno idempotente) — añadir `version_numero` al SELECT y al retorno, más los campos nuevos con defaults:

```php
        $row = $this->db->query(
            'SELECT id, version_label, version_numero, total_actividades, total_insumos, costo_total
             FROM pdc_presupuesto_versiones WHERE project_id = ? AND import_token = ?',
            [$projectId, $token],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'ok' => true,
            'sinCambios' => false,
            'versionId' => (int) $row['id'],
            'versionNumero' => (int) $row['version_numero'],
            'versionLabel' => $row['version_label'],
            'versionIdAnterior' => null,
            'resumen' => [
                'capitulos' => 0,
                'subcapitulos' => 0,
                'grupos' => 0,
                'actividades' => (int) $row['total_actividades'],
                'insumos' => (int) $row['total_insumos'],
                'costoTotal' => (float) $row['costo_total'],
            ],
        ];
```

**(e) `versiones()`** — añadir `version_numero` al SELECT y `'versionNumero' => (int) $r['version_numero']` al array mapeado (junto a los campos existentes).

- [ ] **Step 4: Correr y ver que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_versionado.php`
Expected: todos PASS, exit 0.

- [ ] **Step 5: PHPStan + regresión + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PresupuestoImportService.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_import_flujo.php
docker compose exec app php tests/test_pdc_v2_arbol.php
docker compose exec app php tests/test_pdc_v2_comparar.php
git add src/Services/Pdc/PresupuestoImportService.php tests/test_pdc_v2_versionado.php
git commit -m "feat(pdc-v2): auto-numeracion + anti-duplicado por contenido + versionIdAnterior en el import"
```

Nota: `test_pdc_v2_import_flujo.php` afirma hoy `$p2['advertencias'] !== []` para un re-import idéntico — sigue válido (la advertencia binaria se conserva). Si ese test afirmara algo sobre el conteo de versiones tras re-import idéntico que ahora cambia por el anti-duplicado, ajústalo para reflejar el nuevo comportamiento (documenta el cambio en el reporte).

- [ ] **Step 6:** Si `test_pdc_v2_import_flujo.php` falla por el nuevo anti-duplicado (el 2º import idéntico ya no crea 2ª versión), actualiza SUS asserts a la nueva semántica (re-import idéntico → `sinCambios`, 1 versión) y re-commitea junto al anterior. Corre de nuevo hasta verde.

---

### Task 3: Controller — propagar los campos nuevos (lps-aia)

**Files:**
- Modify: `src/Controllers/Api/PlanComprasImportController.php` (`preview()` y `confirmar()`)

**Interfaces:**
- Produces (contrato HTTP para T4/T5):
  - `POST …/preview` data += `sinCambios: bool`, `versionActiva: {id,numero,label,createdAt}|null`.
  - `POST …/confirmar` data = `{versionId, versionNumero, versionLabel, versionIdAnterior:int|null, sinCambios:bool, resumen}`.

- [ ] **Step 1: `preview()`** — extender el `$this->ok([...])` final (solo ese bloque cambia):

```php
        $this->ok([
            'importToken' => $r['importToken'],
            'versionLabel' => $r['versionLabel'],
            'resumen' => $r['resumen'],
            'advertencias' => $r['advertencias'],
            'sinCambios' => $r['sinCambios'],
            'versionActiva' => $r['versionActiva'],
        ]);
```

- [ ] **Step 2: `confirmar()`** — reemplazar el `$this->ok([...])` final:

```php
        $this->ok([
            'versionId' => $r['versionId'],
            'versionNumero' => $r['versionNumero'],
            'versionLabel' => $r['versionLabel'],
            'versionIdAnterior' => $r['versionIdAnterior'],
            'sinCambios' => $r['sinCambios'],
            'resumen' => $r['resumen'],
        ]);
```

- [ ] **Step 3: Verificar y commit**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasImportController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasImportController.php --memory-limit=1G
git add src/Controllers/Api/PlanComprasImportController.php
git commit -m "feat(pdc-v2): el endpoint de import propaga sinCambios/versionNumero/versionIdAnterior"
```

---

### Task 4: SPA — tipos + reducer + helper de etiqueta (plan-de-compras, TDD)

**Files:**
- Modify: `src/lib/types.ts`
- Modify: `src/lib/importState.ts`
- Create: `src/lib/importState.test.ts` (si no existe) o Modify
- Create: `src/lib/versionLabel.ts`, `src/lib/versionLabel.test.ts`

**Interfaces:**
- Produces:
  - `VersionPresupuesto` += `versionNumero: number`.
  - `ImportPreview` += `sinCambios: boolean`, `versionActiva: { id: number; numero: number; label: string | null; createdAt: string } | null`.
  - `ImportConfirmResult = { versionId: number; versionNumero: number; versionLabel: string | null; versionIdAnterior: number | null; sinCambios: boolean; resumen: ImportResumen }`.
  - `importReducer`: acción `CONFIRMADO` lleva `{ resultado: ImportConfirmResult }`; `ImportState` += `resultado: ImportConfirmResult | null`.
  - `etiquetaVersion(v: { versionNumero: number; createdAt: string; versionLabel?: string | null }): string` → `"Versión N · <fecha>"` (+ ` · <label>` si el label no está vacío).

- [ ] **Step 1: Branch**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git checkout main && git checkout -b pdc-a17-versionado
```

- [ ] **Step 2: Tipos en `src/lib/types.ts`** — (a) añadir `versionNumero: number` a `VersionPresupuesto` (tras `id`); (b) extender `ImportPreview`; (c) añadir `ImportConfirmResult`:

```ts
// En VersionPresupuesto, añadir tras `id: number`:
  versionNumero: number
```
```ts
// Reemplazar ImportPreview por:
export type ImportPreview = {
  importToken: string
  versionLabel: string | null
  resumen: ImportResumen
  advertencias: string[]
  sinCambios: boolean
  versionActiva: { id: number; numero: number; label: string | null; createdAt: string } | null
}

export type ImportConfirmResult = {
  versionId: number
  versionNumero: number
  versionLabel: string | null
  versionIdAnterior: number | null
  sinCambios: boolean
  resumen: ImportResumen
}
```

- [ ] **Step 3: Reducer en `src/lib/importState.ts`** — extender el estado y la acción CONFIRMADO:

```ts
import type { ImportConfirmResult, ImportErrorFila, ImportPreview } from './types'

export type ImportState = {
  fase: 'idle' | 'subiendo' | 'previewOk' | 'previewErrores' | 'confirmando' | 'confirmado'
  preview: ImportPreview | null
  resultado: ImportConfirmResult | null
  errores: ImportErrorFila[]
  mensajeError: string | null
}

export type ImportAction =
  | { type: 'SUBIR' }
  | { type: 'PREVIEW_OK'; preview: ImportPreview }
  | { type: 'PREVIEW_ERRORES'; errores: ImportErrorFila[] }
  | { type: 'FALLO'; mensaje: string }
  | { type: 'CONFIRMAR' }
  | { type: 'CONFIRMADO'; resultado: ImportConfirmResult }
  | { type: 'REINICIAR' }

export const estadoInicial: ImportState = { fase: 'idle', preview: null, resultado: null, errores: [], mensajeError: null }

export function importReducer(state: ImportState, action: ImportAction): ImportState {
  switch (action.type) {
    case 'SUBIR':
      return { ...estadoInicial, fase: 'subiendo' }
    case 'PREVIEW_OK':
      return { fase: 'previewOk', preview: action.preview, resultado: null, errores: [], mensajeError: null }
    case 'PREVIEW_ERRORES':
      return { fase: 'previewErrores', preview: null, resultado: null, errores: action.errores, mensajeError: null }
    case 'FALLO':
      return { ...state, fase: 'idle', mensajeError: action.mensaje }
    case 'CONFIRMAR':
      return { ...state, fase: 'confirmando', mensajeError: null }
    case 'CONFIRMADO':
      return { ...state, fase: 'confirmado', resultado: action.resultado, mensajeError: null }
    case 'REINICIAR':
      return estadoInicial
  }
}
```

- [ ] **Step 4: Test del reducer (falla)** — `src/lib/importState.test.ts` (si ya existe, añade el caso; si no, créalo):

```ts
import { describe, expect, it } from 'vitest'
import { estadoInicial, importReducer } from './importState'
import type { ImportConfirmResult, ImportPreview } from './types'

const preview: ImportPreview = {
  importToken: 'a'.repeat(32),
  versionLabel: null,
  resumen: { capitulos: 1, subcapitulos: 1, grupos: 1, actividades: 2, insumos: 4, costoTotal: 100 },
  advertencias: [],
  sinCambios: false,
  versionActiva: null,
}
const resultado: ImportConfirmResult = {
  versionId: 5, versionNumero: 2, versionLabel: null, versionIdAnterior: 4, sinCambios: false,
  resumen: preview.resumen,
}

describe('importReducer', () => {
  it('CONFIRMADO guarda el resultado', () => {
    let s = importReducer(estadoInicial, { type: 'PREVIEW_OK', preview })
    s = importReducer(s, { type: 'CONFIRMAR' })
    s = importReducer(s, { type: 'CONFIRMADO', resultado })
    expect(s.fase).toBe('confirmado')
    expect(s.resultado?.versionNumero).toBe(2)
    expect(s.resultado?.versionIdAnterior).toBe(4)
  })
  it('PREVIEW_OK conserva sinCambios/versionActiva', () => {
    const s = importReducer(estadoInicial, { type: 'PREVIEW_OK', preview: { ...preview, sinCambios: true, versionActiva: { id: 4, numero: 1, label: null, createdAt: '2026-07-23' } } })
    expect(s.preview?.sinCambios).toBe(true)
    expect(s.preview?.versionActiva?.numero).toBe(1)
  })
})
```

- [ ] **Step 5: Helper `src/lib/versionLabel.ts` + test**

```ts
// src/lib/versionLabel.ts
export function etiquetaVersion(v: { versionNumero: number; createdAt: string; versionLabel?: string | null }): string {
  const base = `Versión ${v.versionNumero} · ${v.createdAt}`
  const label = (v.versionLabel ?? '').trim()
  return label === '' ? base : `${base} · ${label}`
}
```

```ts
// src/lib/versionLabel.test.ts
import { describe, expect, it } from 'vitest'
import { etiquetaVersion } from './versionLabel'

describe('etiquetaVersion', () => {
  it('compone Versión N · fecha', () => {
    expect(etiquetaVersion({ versionNumero: 3, createdAt: '2026-07-23 16:00' })).toBe('Versión 3 · 2026-07-23 16:00')
  })
  it('añade el label del Excel si existe', () => {
    expect(etiquetaVersion({ versionNumero: 3, createdAt: '2026-07-23', versionLabel: 'PI_Version_3' })).toBe('Versión 3 · 2026-07-23 · PI_Version_3')
  })
  it('ignora label vacío', () => {
    expect(etiquetaVersion({ versionNumero: 1, createdAt: '2026-07-23', versionLabel: '' })).toBe('Versión 1 · 2026-07-23')
  })
})
```

- [ ] **Step 6: Correr y commit**

```bash
npx vitest run src/lib/importState.test.ts src/lib/versionLabel.test.ts
npm run test && npm run build
git add src/lib/types.ts src/lib/importState.ts src/lib/importState.test.ts src/lib/versionLabel.ts src/lib/versionLabel.test.ts
git commit -m "feat(pdc): tipos+reducer del versionamiento inteligente + etiquetaVersion (Version N · fecha)"
```

Expected: Vitest verde (los previos + los nuevos); build OK.

---

### Task 5: SPA — vista Importar (aviso + auto-comparativo) + selectores (plan-de-compras)

**Files:**
- Modify: `src/pages/ImportarPresupuesto.tsx`
- Modify: `src/pages/VisorPresupuesto.tsx`, `src/pages/ComparativoPresupuesto.tsx`

**Interfaces:**
- Consumes: `ImportConfirmResult`/`ImportPreview` (T4), `etiquetaVersion` (T4), `apiGet` para el comparativo (endpoint A1.6), `Comparativo`/`ResumenDiff` (A1.6).

- [ ] **Step 1: `ImportarPresupuesto.tsx`** — cambios:

(a) Imports: añadir `apiGet` ya está; añadir `etiquetaVersion` y tipos:
```tsx
import { etiquetaVersion } from '../lib/versionLabel'
import type { Comparativo, ImportConfirmResult, ImportErrorFila, ImportPreview, ResumenDiff, VersionPresupuesto } from '../lib/types'
```

(b) Estado local para el resumen del auto-comparativo:
```tsx
  const [cmp, setCmp] = useState<ResumenDiff | null>(null)
```

(c) `onConfirmar` — tipar la respuesta, despachar el resultado y disparar el auto-comparativo:
```tsx
  const onConfirmar = async () => {
    if (!state.preview) return
    dispatch({ type: 'CONFIRMAR' })
    setCmp(null)
    try {
      const resultado = await apiPost<ImportConfirmResult>('/plan-compras/api/presupuesto/confirmar', { importToken: state.preview.importToken })
      dispatch({ type: 'CONFIRMADO', resultado })
      cargarVersiones()
      if (!resultado.sinCambios && resultado.versionIdAnterior != null) {
        apiGet<Comparativo>(`/plan-compras/api/presupuesto/comparar?versionA=${resultado.versionIdAnterior}&versionB=${resultado.versionId}`)
          .then((c) => setCmp(c.resumen))
          .catch(() => setCmp(null))
      }
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }
```

(d) En el bloque de preview (`previewOk`/`confirmando`), tras las advertencias, avisar cuando el cargue es idéntico a la activa:
```tsx
          {state.preview?.sinCambios && state.preview.versionActiva && (
            <p className="pdc-advertencia" data-testid="pdc-import-sincambios">
              ⚠ Este presupuesto es idéntico a la <strong>Versión {state.preview.versionActiva.numero}</strong> (activa). No se creará una versión nueva.
            </p>
          )}
```

(e) Reemplazar el bloque `state.fase === 'confirmado'` por uno que distinga sin-cambios vs versión nueva + el resumen del comparativo:
```tsx
      {state.fase === 'confirmado' && state.resultado && (
        <div className="pdc-bloque pdc-exito" role="status" data-testid="pdc-import-confirmado">
          {state.resultado.sinCambios ? (
            <p>Sin cambios: se mantiene la <strong>Versión {state.resultado.versionNumero}</strong> activa.</p>
          ) : (
            <>
              <p>Cargada la <strong>Versión {state.resultado.versionNumero}</strong> — ahora es la versión activa del proyecto.</p>
              {cmp && (
                <div data-testid="pdc-import-comparativo">
                  <p>
                    Cambios vs la versión anterior: {cmp.nuevos} nuevos · {cmp.eliminados} eliminados · {cmp.modificados} modificados ·{' '}
                    <span className="pdc-cmp-sobrecosto">sobrecostos $ {cmp.sobrecostos.toLocaleString('es-CO')}</span> ·{' '}
                    <span className="pdc-cmp-ahorro">ahorros $ {cmp.ahorros.toLocaleString('es-CO')}</span>
                  </p>
                  <a className="pdc-nav-link" href="#/ensamble/comparar">Ver comparativo completo →</a>
                </div>
              )}
            </>
          )}
        </div>
      )}
```

(f) La grilla de versiones — reemplazar la columna `versionLabel` por una columna "Versión" compuesta:
```tsx
const colsVersiones: ColDef<VersionPresupuesto>[] = [
  {
    headerName: 'Versión', flex: 1, minWidth: 220,
    valueGetter: (p) => (p.data ? etiquetaVersion(p.data) : ''),
  },
  { field: 'archivoNombre', headerName: 'Archivo', flex: 1 },
  { field: 'totalActividades', headerName: 'Actividades', width: 120 },
  { field: 'totalInsumos', headerName: 'Insumos', width: 110 },
  {
    field: 'costoTotal', headerName: 'Costo total', width: 150,
    valueFormatter: (p) => p.value != null ? `$ ${Number(p.value).toLocaleString('es-CO')}` : '',
  },
  { field: 'importadoPor', headerName: 'Importó', width: 130 },
  { field: 'activa', headerName: 'Estado', width: 100, valueFormatter: (p) => (p.value ? 'Activa' : '') },
]
```

- [ ] **Step 2: Selectores en `VisorPresupuesto.tsx` y `ComparativoPresupuesto.tsx`** — cambiar el `<option>` para usar `etiquetaVersion`.

En `VisorPresupuesto.tsx` (añade el import `import { etiquetaVersion } from '../lib/versionLabel'`), reemplazar el contenido del `<option>`:
```tsx
              {versiones.map((v) => (
                <option key={v.id} value={v.id}>
                  {etiquetaVersion(v)}{v.activa ? ' (activa)' : ''}
                </option>
              ))}
```

En `ComparativoPresupuesto.tsx` (añade el mismo import), en el helper `selectorVersion`:
```tsx
      {versiones.map((v) => (
        <option key={v.id} value={v.id}>{etiquetaVersion(v)}{v.activa ? ' (activa)' : ''}</option>
      ))}
```

- [ ] **Step 3: Verificar y commit**

```bash
npm run test && npm run build
git add src/pages/ImportarPresupuesto.tsx src/pages/VisorPresupuesto.tsx src/pages/ComparativoPresupuesto.tsx
git commit -m "feat(pdc): import muestra Version N · fecha, aviso sin-cambios y resumen del auto-comparativo"
```

Expected: Vitest verde; build OK.

---

### Task 6: Bundle + e2e + docs (lps-aia + plan-de-compras)

**Files:**
- Create (lps-aia): `tests/browser/pdc-v2-versionado.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md`, `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`
- Generated (lps-aia): `public/pdc-app/**`

**Interfaces:**
- Consumes: `npm run sync`; helpers `loginAndSelectProject`/`logout`; el flujo de import de `pdc-v2-import.spec.mjs`; test-ids de T5.

- [ ] **Step 1: Sync**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run sync
```

- [ ] **Step 2: e2e** — `tests/browser/pdc-v2-versionado.spec.mjs` (leer antes `tests/browser/pdc-v2-import.spec.mjs` para el idioma de subida del fixture por la vista Importar):

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx'; // el que usa pdc-v2-import.spec.mjs

test('versionamiento inteligente: re-cargue idéntico avisa sin cambios', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/importar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Importar presupuesto', { timeout: 15000 });

    // Primer cargue del fixture.
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toBeVisible({ timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('[data-testid="pdc-import-confirmado"]')).toBeVisible({ timeout: 20000 });

    // Segundo cargue idéntico → aviso "sin cambios".
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-sincambios"]')).toBeVisible({ timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('[data-testid="pdc-import-confirmado"]')).toContainText('Sin cambios', { timeout: 20000 });

    // El historial muestra "Versión N · fecha".
    await expect(page.locator('[data-testid="pdc-import-versiones"]')).toContainText('Versión', { timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Correr e2e + regresión**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-versionado.spec.mjs --workers=1
npx playwright test tests/browser/pdc-v2-import.spec.mjs tests/browser/pdc-v2-comparar.spec.mjs tests/browser/pdc-v2-visor.spec.mjs --workers=1
docker compose exec app php tests/test_pdc_v2_versionado.php
```

Expected: versionado passed; regresión import/comparar/visor passed; test PHP exit 0. Nota: el fixture de import puede haber dejado ya varias versiones en Da Porto; el aviso "sin cambios" depende de que el 2º cargue sea idéntico al 1º de ESTA corrida (que lo es). Si el spec de import corre antes y deja la misma activa, el primer cargue de este spec podría avisar "sin cambios" antes de tiempo — en ese caso, el spec debe subir un fixture y, si avisa sin-cambios en el primer intento, aceptarlo como estado válido (el proyecto ya tenía ese contenido) y continuar con el assert del 2º; ajusta el spec para ser robusto a ese orden (documenta la adaptación).

- [ ] **Step 4: Commit lps-aia**

```bash
git add public/pdc-app tests/browser/pdc-v2-versionado.spec.mjs
git commit -m "feat(pdc-v2): bundle con el versionamiento inteligente + e2e (re-cargue identico avisa sin cambios)"
```

- [ ] **Step 5: CLAUDE.md + roadmap + commit (plan-de-compras)**

En `CLAUDE.md` "Estado actual", añadir A1.7 con una frase: *versionamiento inteligente del importador (`#/ensamble/importar`): auto-numeración (Versión N · fecha) por proyecto, anti-duplicado por hash de contenido vs la versión activa, y resumen del auto-comparativo tras cargar (reusa A1.6).* — preservando el resto.

En el roadmap, marcar la Fase A1.7 con *(Implementada.)*.

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md
git commit -m "docs(pdc): CLAUDE.md y roadmap reflejan la Fase A1.7 (versionamiento inteligente)"
```

---

## Verificación end-to-end (tras Task 6)

1. lps-aia: `test_pdc_v2_versionado.php` exit 0; regresión `test_pdc_v2_import_flujo` + `arbol` + `comparar` verdes; gates safety+reconciliation exit 0; PHPStan limpio.
2. plan-de-compras: Vitest (incluye reducer + etiquetaVersion) + build OK.
3. e2e: `pdc-v2-versionado` + regresión import/comparar/visor → passed.
4. Visual (navegador integrado, cierre de sprint): Importar → subir el DAPORTO real → ver "Versión N · fecha" (ya no "—"); re-subir → aviso "sin cambios"; subir una versión modificada → resumen del auto-comparativo + CTA.

## Riesgos anotados

- **Hash canónico y el `iva`/`valor_unitario`:** el hash usa `valor_total` y `cantidad_total` (lo económico). Un cambio solo en `iva` sin efecto en `valor_total` no dispararía "cambio" — aceptable (mismo costo). Documentado.
- **Backfill con contenido_hash NULL en históricas:** la comparación trata NULL como "distinto" → el primer re-cargue tras la migración siempre crea versión (puebla el hash); los siguientes idénticos ya avisan. Correcto.
- **Orden de specs e2e sobre el catálogo/proyecto compartido:** el aviso "sin cambios" depende del estado previo del proyecto Da Porto; el spec se hace robusto al orden (ver nota en Task 6, Step 3).
- **`test_pdc_v2_import_flujo.php` existente** afirma que el 2º import idéntico crea una 2ª versión; con el anti-duplicado eso cambia — Task 2 Step 6 lo actualiza a la nueva semántica.
