---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-23
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-23-a16-comparativo-versiones.md
resumen: Comparar dos versiones importadas del presupuesto de un proyecto — por actividad (jerárquico) y por insumo (Pareto) — mostrando Δvalor, clasificación…
---

# Fase A1.6: Comparativo de versiones del presupuesto — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Comparar dos versiones importadas del presupuesto de un proyecto — por actividad (jerárquico) y por insumo (Pareto) — mostrando Δvalor, clasificación (nuevo/eliminado/modificado/igual) y un resumen de sobrecostos vs ahorros, incorporando y mejorando el comparativo de la app externa de Tomás.

**Architecture:** Solo lectura, sin migraciones. `PresupuestoImportService::comparar()` computa el diff en PHP fusionando por clave (actividades por `codigo` con roll-up; insumos consolidados por `descripcion_norm+unidad`) y devuelve filas ya diferenciadas. Un endpoint GET nuevo (RBAC `lps.pdc.ver`). La SPA clona `VisorPresupuesto` con dos selectores de versión y una lógica pura de árbol/orden en `comparativo.ts`. Spec: `docs/superpowers/specs/2026-07-23-a16-comparativo-versiones-design.md`.

**Tech Stack:** PHP 8.3 + PDO/MySQL 8 (Docker lps-aia), FastRoute, React+TS+Vite+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{"ok":true,"data":...}` | `{"ok":false,"error":{...}}` (trait `PlanComprasJsonRespuestas`, reusar). Códigos: `NO_VERSION` 404, `PARAMS_INVALIDOS` 422, `FORBIDDEN` 403, `NO_PROJECT` 409.
- RBAC lectura `lps.pdc.ver` (mismo patrón inline que `arbol()`/`versiones()` — **no hay** helper `guardLectura`; se copia el bloque de dos `if`). Proyecto activo desde `$_SESSION['project_id']`.
- Normalización canónica de insumos: `\App\Services\Pdc\MaestroInsumosService::normalizar()` — NO duplicar.
- Identidad del diff: **actividades por `codigo`**; **insumos por `(descripcion_norm, unidad)`** (misma clave que A2).
- `costoA`/`costoB` = suma de `valorTotal` de insumos consolidados por versión (auto-consistente: `delta == sobrecostos + ahorros`).
- `deltaValor = valorB − valorA`; `> 0` sobrecosto, `< 0` ahorro; `estado` ∈ `nuevo|eliminado|modificado|igual` (epsilon 0.01). `deltaPct = valorA==0 ? null : deltaValor/valorA*100`.
- El **front siempre envía ambos** `versionA` y `versionB` (preselecciona activa vs anterior); el backend requiere ambos.
- AG Grid Community con módulos selectivos (`ClientSideRowModelModule` + `ValidationModule` dev-only — nunca `AllCommunityModule`); flags/valores numéricos, sin boolean cells; colorear con `cellClass`/`cellClassRules` (strings), no con checkboxes.
- Tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1) sobre el MySQL real del Docker; proyectos 999901/999902; cleanup por `DELETE FROM pdc_presupuesto_versiones WHERE project_id IN (...)` (CASCADE limpia items/insumos). Gates `test_global_table_safety`/`reconciliation` en verde.
- SPA: TypeScript estricto, Vitest excluye `.claude`; identificadores en inglés, comentarios/UI en español.
- Comandos: tests PHP `docker compose exec app php tests/...` (stack `docker compose up -d app db`, live-mounted); SPA `npm run test`/`npm run build`; bundle `npm run sync`; e2e `npx playwright test ... --workers=1` desde lps-aia.
- Ramas: `pdc-a16-comparativo` en ambos repos. Commits `feat(pdc-v2)` (lps-aia) / `feat(pdc)` (plan-de-compras).

---

## File Structure

**lps-aia (rama `pdc-a16-comparativo`):**
```
src/Services/Pdc/PresupuestoImportService.php   # T1: +comparar() +totalesPorCodigo() +insumosConsolidados()
tests/test_pdc_v2_comparar.php                  # T1
src/Controllers/Api/PlanComprasImportController.php  # T2: +comparar()
public/index.php                                # T2: +1 ruta
tests/browser/pdc-v2-comparar.spec.mjs          # T5
public/pdc-app/**                               # T5: bundle
```

**plan-de-compras (rama `pdc-a16-comparativo`):**
```
src/lib/comparativo.ts        # T3: lógica pura (árbol diff + orden insumos + clase)
src/lib/comparativo.test.ts   # T3
src/lib/types.ts              # T3: Modify (+tipos del comparativo)
src/pages/ComparativoPresupuesto.tsx  # T4
src/App.tsx                   # T4: Modify (+ruta y NavLink "Comparar")
src/styles.css                # T4: Modify
CLAUDE.md                     # T5: Modify
docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md  # T5: Modify (A1.6 implementada)
```

---

### Task 1: Backend — `comparar()` + test PHP (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php`
- Test: `tests/test_pdc_v2_comparar.php`

**Interfaces:**
- Consumes: tablas de A1; `MaestroInsumosService::normalizar()`; el constructor existente `(\Database, PresupuestoImportStore, PresupuestoExcelParser)`.
- Produces:
  - `comparar(int $projectId, int $versionA, int $versionB): ?array` — `null` si alguna versión no existe/no es del proyecto. Retorno:
    ```
    {
      versionA:{id,label}, versionB:{id,label},
      resumen:{costoA, costoB, delta, sobrecostos, ahorros, nuevos, eliminados, modificados},
      actividades:[{codigo, codigoPadre, nivel, tipoFila, descripcion, valorA, valorB, deltaValor, deltaPct, estado}],
      insumos:[{descripcionNorm, unidad, descripcion, tipoInsumo, cantidadA, cantidadB, valorA, valorB, deltaValor, deltaPct, estado}]
    }
    ```
    `actividades` planas con jerarquía (para armar árbol en el front), ordenadas por `id` de la versión B (o A si el código solo existe en A). `insumos` ordenados por `MAX(valorA, valorB) DESC`.

- [ ] **Step 1: Crear branch**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git checkout main && git checkout -b pdc-a16-comparativo
```

- [ ] **Step 2: Escribir el test (falla)** — `tests/test_pdc_v2_comparar.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_CMP_A = 999901;
const PDC_CMP_B = 999902;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_CMP_A, PDC_CMP_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

// V2 del mismo presupuesto: TEJA sube de precio, AYUDANTE se elimina, se agrega MALLA a la losa, el resto igual.
$fixtureV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',     '',         '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01',       'CAMPAMENTO',       '01',       '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01.01',    'INSTALACIONES',    '01.01',    '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',  '01.01.01', 'M2', 18,   '', 102, 'PI_TEST_2', 'APU-001', null, null, null, null,   '',              ''],
        ['',            'TEJA DE ZINC',     '',         'M2', null, '', 102, 'PI_TEST_2', '',        1.05, 1.2,  19,   30000,  'MAT-CUBIERTAS', ''],
        ['02',          'ESTRUCTURA',       '',         '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01',       'CONCRETOS',        '02',       '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01.01',    'LOSAS',            '02.01',    '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01.01.01', 'LOSA MACIZA E=12', '02.01.01', 'M3', 40,   '', 102, 'PI_TEST_2', 'APU-002', null, null, null, null,   '',              ''],
        ['',            'CONCRETO 4000PSI', '',         'M3', null, '', 102, 'PI_TEST_2', '',        1.0,  1.05, 19,   620000, 'MAT-CONCRETOS', ''],
        ['',            'SERVICIO BOMBEO',  '',         'M3', null, '', 102, 'PI_TEST_2', '',        1.0,  1.0,  null, 28000,  'EQUIPOS',       ''],
        ['',            'MALLA ELECTROSOLDADA', '',     'KG', null, '', 102, 'PI_TEST_2', '',        1.0,  1.0,  19,   6000,   'MAT-ACEROS',    ''],
    ]);
};

echo "=== PDC v2: comparar() de versiones ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-cmp-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Importar dos versiones en el proyecto A.
$v1 = sys_get_temp_dir() . '/pdc_cmp_v1.xlsx';
$v2 = sys_get_temp_dir() . '/pdc_cmp_v2.xlsx';
pdcFixturePresupuestoValido($v1);
$fixtureV2($v2);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_CMP_A, 'tester');
$c1 = $service->confirmar($p1['importToken'], PDC_CMP_A);
$p2 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_CMP_A, 'tester');
$c2 = $service->confirmar($p2['importToken'], PDC_CMP_A);

// Versión inexistente → null.
$assert($service->comparar(PDC_CMP_A, $c1['versionId'], 999999) === null, 'Versión inexistente → null.');
$assert($service->comparar(PDC_CMP_B, $c1['versionId'], $c2['versionId']) === null, 'Aislamiento: proyecto B no compara versiones de A.');

$r = $service->comparar(PDC_CMP_A, $c1['versionId'], $c2['versionId']);
$assert($r !== null, 'Comparación válida devuelve resultado.');

$insPorNorm = [];
foreach ($r['insumos'] as $i) { $insPorNorm[$i['descripcionNorm']] = $i; }

$assert(($insPorNorm['TEJA DE ZINC']['estado'] ?? '') === 'modificado' && $insPorNorm['TEJA DE ZINC']['deltaValor'] > 0, 'TEJA: modificado con sobrecosto (subió el vr. unitario).');
$assert(($insPorNorm['AYUDANTE']['estado'] ?? '') === 'eliminado' && $insPorNorm['AYUDANTE']['deltaValor'] < 0, 'AYUDANTE: eliminado (ahorro).');
$assert(($insPorNorm['AYUDANTE']['valorB'] ?? -1) === 0.0, 'AYUDANTE: valorB = 0 en la versión nueva.');
$assert(($insPorNorm['MALLA ELECTROSOLDADA']['estado'] ?? '') === 'nuevo' && $insPorNorm['MALLA ELECTROSOLDADA']['valorA'] === 0.0, 'MALLA: nuevo (no existía en v1).');
$assert(($insPorNorm['CONCRETO 4000PSI']['estado'] ?? '') === 'igual', 'CONCRETO: igual (sin cambios).');

$assert($r['resumen']['nuevos'] === 1 && $r['resumen']['eliminados'] === 1 && $r['resumen']['modificados'] === 1, 'Resumen: 1 nuevo, 1 eliminado, 1 modificado.');
$assert($r['resumen']['sobrecostos'] > 0 && $r['resumen']['ahorros'] < 0, 'Resumen: hay sobrecostos y ahorros.');
$assert(abs(($r['resumen']['costoB'] - $r['resumen']['costoA']) - $r['resumen']['delta']) < 0.01, 'delta = costoB - costoA.');
$assert(abs(($r['resumen']['sobrecostos'] + $r['resumen']['ahorros']) - $r['resumen']['delta']) < 0.01, 'delta = sobrecostos + ahorros (auto-consistente).');

// Actividades: la losa (donde se agregó MALLA) aparece modificada; el orden lleva jerarquía.
$actPorCodigo = [];
foreach ($r['actividades'] as $a) { $actPorCodigo[$a['codigo']] = $a; }
$assert(($actPorCodigo['02.01.01.01']['estado'] ?? '') === 'modificado', 'Actividad de la losa: modificada (se agregó un insumo).');
$assert(($actPorCodigo['02']['tipoFila'] ?? '') === 'capitulo' && $actPorCodigo['02']['valorB'] > $actPorCodigo['02']['valorA'], 'Capítulo 02 agrega el sobrecosto de sus hijos (roll-up).');
$mags = array_map(static fn ($i) => max($i['valorA'], $i['valorB']), $r['insumos']);
$magsOrden = $mags; rsort($magsOrden);
$assert($mags === $magsOrden, 'Insumos ordenados por magnitud del valor (desc).');

foreach ([$v1, $v2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 3: Correr y ver que falla**

Run: `docker compose up -d app db && docker compose exec app php tests/test_pdc_v2_comparar.php`
Expected: FAIL — `Call to undefined method ... comparar()`.

- [ ] **Step 4: Implementar** — añadir a `PresupuestoImportService` (tras `arbol()`):

```php
    /** Compara dos versiones del presupuesto: diff por actividad (roll-up) y por insumo consolidado. */
    public function comparar(int $projectId, int $versionA, int $versionB): ?array
    {
        $va = $this->versionMeta($projectId, $versionA);
        $vb = $this->versionMeta($projectId, $versionB);
        if ($va === null || $vb === null) {
            return null;
        }

        // --- Insumos consolidados por (norm, unidad) en cada versión ---
        $insA = $this->insumosConsolidados($projectId, $versionA);
        $insB = $this->insumosConsolidados($projectId, $versionB);
        $claves = array_unique(array_merge(array_keys($insA), array_keys($insB)));
        $insumos = [];
        $costoA = 0.0; $costoB = 0.0; $sobrecostos = 0.0; $ahorros = 0.0;
        $nuevos = 0; $eliminados = 0; $modificados = 0;
        foreach ($claves as $k) {
            $a = $insA[$k] ?? null;
            $b = $insB[$k] ?? null;
            $valorA = $a['valorTotal'] ?? 0.0;
            $valorB = $b['valorTotal'] ?? 0.0;
            $cantA = $a['cantidadTotal'] ?? 0.0;
            $cantB = $b['cantidadTotal'] ?? 0.0;
            $delta = $valorB - $valorA;
            $costoA += $valorA; $costoB += $valorB;
            if ($delta > 0) { $sobrecostos += $delta; } elseif ($delta < 0) { $ahorros += $delta; }
            $estado = $this->estadoDiff($a !== null, $b !== null, $delta, $cantB - $cantA);
            if ($estado === 'nuevo') { $nuevos++; } elseif ($estado === 'eliminado') { $eliminados++; } elseif ($estado === 'modificado') { $modificados++; }
            $ref = $b ?? $a;
            $insumos[] = [
                'descripcionNorm' => $ref['norm'],
                'unidad' => $ref['unidad'],
                'descripcion' => $ref['descripcion'],
                'tipoInsumo' => $ref['tipoInsumo'],
                'cantidadA' => $cantA, 'cantidadB' => $cantB,
                'valorA' => $valorA, 'valorB' => $valorB,
                'deltaValor' => $delta,
                'deltaPct' => $valorA == 0.0 ? null : round($delta / $valorA * 100, 1),
                'estado' => $estado,
            ];
        }
        usort($insumos, static fn ($x, $y) => max($y['valorA'], $y['valorB']) <=> max($x['valorA'], $x['valorB']));

        // --- Actividades por codigo con roll-up en cada versión ---
        $totA = $this->totalesPorCodigo($projectId, $versionA);
        $totB = $this->totalesPorCodigo($projectId, $versionB);
        $codigos = array_unique(array_merge(array_keys($totA), array_keys($totB)));
        $actividades = [];
        foreach ($codigos as $cod) {
            $a = $totA[$cod] ?? null;
            $b = $totB[$cod] ?? null;
            $ref = $b ?? $a;
            $valorA = $a['total'] ?? 0.0;
            $valorB = $b['total'] ?? 0.0;
            $delta = $valorB - $valorA;
            $actividades[] = [
                'codigo' => $cod,
                'codigoPadre' => $ref['codigoPadre'],
                'nivel' => $ref['nivel'],
                'tipoFila' => $ref['tipoFila'],
                'descripcion' => $ref['descripcion'],
                'valorA' => $valorA, 'valorB' => $valorB,
                'deltaValor' => $delta,
                'deltaPct' => $valorA == 0.0 ? null : round($delta / $valorA * 100, 1),
                'estado' => $this->estadoDiff($a !== null, $b !== null, $delta, 0.0),
                'orden' => $ref['orden'],
            ];
        }
        usort($actividades, static fn ($x, $y) => $x['orden'] <=> $y['orden']);
        foreach ($actividades as &$act) { unset($act['orden']); }
        unset($act);

        return [
            'versionA' => ['id' => (int) $va['id'], 'label' => $va['version_label']],
            'versionB' => ['id' => (int) $vb['id'], 'label' => $vb['version_label']],
            'resumen' => [
                'costoA' => round($costoA, 2), 'costoB' => round($costoB, 2),
                'delta' => round($costoB - $costoA, 2),
                'sobrecostos' => round($sobrecostos, 2), 'ahorros' => round($ahorros, 2),
                'nuevos' => $nuevos, 'eliminados' => $eliminados, 'modificados' => $modificados,
            ],
            'actividades' => $actividades,
            'insumos' => $insumos,
        ];
    }

    /** Cabecera de una versión del proyecto, o null. */
    private function versionMeta(int $projectId, int $versionId): ?array
    {
        $row = $this->db->query(
            'SELECT id, version_label FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
            [$projectId, $versionId],
        )->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** Insumos consolidados por (descripcion_norm, unidad) de una versión. */
    private function insumosConsolidados(int $projectId, int $versionId): array
    {
        $rows = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad, cantidad_total, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $acc = [];
        foreach ($rows as $r) {
            $norm = MaestroInsumosService::normalizar($r['descripcion']);
            $clave = $norm . '|' . $r['unidad'];
            if (!isset($acc[$clave])) {
                $acc[$clave] = ['norm' => $norm, 'descripcion' => $r['descripcion'], 'tipoInsumo' => $r['tipo_insumo'], 'unidad' => $r['unidad'], 'cantidadTotal' => 0.0, 'valorTotal' => 0.0];
            }
            $acc[$clave]['cantidadTotal'] += (float) ($r['cantidad_total'] ?? 0);
            $acc[$clave]['valorTotal'] += (float) ($r['valor_total'] ?? 0);
        }
        return $acc; // indexado por "norm|unidad" (clave de fusión del diff = descripcion_norm + unidad, spec A1.6)
    }

    /** Total por codigo de item con roll-up de hojas a raíces (hoja = suma de sus insumos). */
    private function totalesPorCodigo(int $projectId, int $versionId): array
    {
        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, nivel, tipo_fila, descripcion
             FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $sumaHojas = $this->db->query(
            'SELECT item_id, SUM(valor_total) AS total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ? GROUP BY item_id',
            [$projectId, $versionId],
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        $porCodigo = [];
        $orden = 0;
        foreach ($items as $it) {
            $porCodigo[$it['codigo']] = [
                'codigo' => $it['codigo'], 'codigoPadre' => $it['codigo_padre'],
                'nivel' => (int) $it['nivel'], 'tipoFila' => $it['tipo_fila'], 'descripcion' => $it['descripcion'],
                'total' => (float) ($sumaHojas[$it['id']] ?? 0), 'orden' => $orden++,
            ];
        }
        // Propagar de hojas a raíces: por nivel descendente, sumar cada hijo a su padre.
        usort($items, static fn ($a, $b) => (int) $b['nivel'] <=> (int) $a['nivel']);
        foreach ($items as $it) {
            $padre = $it['codigo_padre'];
            if ($padre !== null && isset($porCodigo[$padre])) {
                $porCodigo[$padre]['total'] += $porCodigo[$it['codigo']]['total'];
            }
        }
        return $porCodigo;
    }

    /** Clasifica un renglón del diff. */
    private function estadoDiff(bool $enA, bool $enB, float $deltaValor, float $deltaCantidad): string
    {
        if (!$enA && $enB) { return 'nuevo'; }
        if ($enA && !$enB) { return 'eliminado'; }
        return (abs($deltaValor) < 0.01 && abs($deltaCantidad) < 0.01) ? 'igual' : 'modificado';
    }
```

- [ ] **Step 5: Correr y ver que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_comparar.php`
Expected: todos PASS, `=== OK ===`, exit 0.

- [ ] **Step 6: PHPStan + gates + commit**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc/PresupuestoImportService.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_arbol.php
docker compose exec app php tests/test_global_table_safety.php
git add src/Services/Pdc/PresupuestoImportService.php tests/test_pdc_v2_comparar.php
git commit -m "feat(pdc-v2): comparar() de versiones — diff por actividad (roll-up) e insumo consolidado"
```

Expected: PHPStan limpio; regresión `arbol` verde; gate exit 0.

---

### Task 2: Controller + ruta del comparativo (lps-aia)

**Files:**
- Modify: `src/Controllers/Api/PlanComprasImportController.php` (añadir `comparar()`)
- Modify: `public/index.php` (1 ruta)

**Interfaces:**
- Consumes: `PresupuestoImportService::comparar()` (T1).
- Produces (contrato HTTP para T4): `GET /plan-compras/api/presupuesto/comparar?versionA=N&versionB=M` → `{ok:true,data:{versionA,versionB,resumen,actividades,insumos}}` | `PARAMS_INVALIDOS` 422 (falta/igual) | `NO_VERSION` 404 | `FORBIDDEN` 403 | `NO_PROJECT` 409.

- [ ] **Step 1: Ruta en `public/index.php`** — tras la ruta `GET /plan-compras/api/presupuesto/arbol`:

```php
$router->get('/plan-compras/api/presupuesto/comparar', [\App\Controllers\Api\PlanComprasImportController::class, 'comparar']);
```

- [ ] **Step 2: Implementar `comparar()`** — en `PlanComprasImportController`, tras `arbol()`:

```php
    /** GET /plan-compras/api/presupuesto/comparar?versionA=N&versionB=M — solo lectura. */
    public function comparar(): void
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $va = filter_var($_GET['versionA'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $vb = filter_var($_GET['versionB'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($va === false || $va === null || $vb === false || $vb === null || $va === $vb) {
            $this->fail('PARAMS_INVALIDOS', 'Debes elegir dos versiones distintas para comparar.', 422);
            return;
        }
        $r = $this->service->comparar($projectId, (int) $va, (int) $vb);
        if ($r === null) {
            $this->fail('NO_VERSION', 'Alguna de las versiones no existe en este proyecto.', 404);
            return;
        }
        $this->ok($r);
    }
```

- [ ] **Step 3: Verificar y commit**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasImportController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasImportController.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_import.php
git add src/Controllers/Api/PlanComprasImportController.php public/index.php
git commit -m "feat(pdc-v2): endpoint GET presupuesto/comparar (RBAC ver, dos versiones distintas)"
```

Expected: sin errores; suite de import de A1 sigue verde (no tocamos su lógica).

---

### Task 3: SPA — lógica pura del comparativo (plan-de-compras, TDD)

**Files:**
- Create: `src/lib/comparativo.ts`, `src/lib/comparativo.test.ts`
- Modify: `src/lib/types.ts`

**Interfaces:**
- Produces:
  - Tipos: `EstadoDiff = 'nuevo'|'eliminado'|'modificado'|'igual'`; `ActividadDiff`, `InsumoDiff`, `ResumenDiff`, `Comparativo` (ver Step 2).
  - `filasComparativoVisibles(actividades: ActividadDiff[], expandidos: Set<string>): FilaComparativo[]` — filas de actividades visibles según expandidos (jerarquía por `codigo`/`codigoPadre`), cada una con `expandible`/`expandido` + los campos de diff.
  - `claseDelta(deltaValor: number, estado: EstadoDiff): string` — `'pdc-cmp-sobrecosto' | 'pdc-cmp-ahorro' | ''` para colorear.

- [ ] **Step 1: Tipos en `src/lib/types.ts`** (añadir al final):

```ts
export type EstadoDiff = 'nuevo' | 'eliminado' | 'modificado' | 'igual'

export type ActividadDiff = {
  codigo: string
  codigoPadre: string | null
  nivel: number
  tipoFila: 'capitulo' | 'subcapitulo' | 'grupo' | 'actividad'
  descripcion: string
  valorA: number
  valorB: number
  deltaValor: number
  deltaPct: number | null
  estado: EstadoDiff
}

export type InsumoDiff = {
  descripcionNorm: string
  unidad: string
  descripcion: string
  tipoInsumo: string
  cantidadA: number
  cantidadB: number
  valorA: number
  valorB: number
  deltaValor: number
  deltaPct: number | null
  estado: EstadoDiff
}

export type ResumenDiff = {
  costoA: number
  costoB: number
  delta: number
  sobrecostos: number
  ahorros: number
  nuevos: number
  eliminados: number
  modificados: number
}

export type Comparativo = {
  versionA: { id: number; label: string }
  versionB: { id: number; label: string }
  resumen: ResumenDiff
  actividades: ActividadDiff[]
  insumos: InsumoDiff[]
}
```

- [ ] **Step 2: Test (falla)** — `src/lib/comparativo.test.ts`

```ts
import { describe, expect, it } from 'vitest'
import { claseDelta, filasComparativoVisibles } from './comparativo'
import type { ActividadDiff } from './types'

const act = (codigo: string, codigoPadre: string | null, nivel: number, tipoFila: ActividadDiff['tipoFila'], estado: ActividadDiff['estado'] = 'igual'): ActividadDiff => ({
  codigo, codigoPadre, nivel, tipoFila, descripcion: codigo, valorA: 100, valorB: 120, deltaValor: 20, deltaPct: 20, estado,
})

const arbol: ActividadDiff[] = [
  act('01', null, 1, 'capitulo'),
  act('01.01', '01', 2, 'subcapitulo'),
  act('01.01.01.01', '01.01', 3, 'actividad', 'modificado'),
  act('02', null, 1, 'capitulo'),
]

describe('filasComparativoVisibles', () => {
  it('sin expandidos solo muestra las raíces', () => {
    const filas = filasComparativoVisibles(arbol, new Set())
    expect(filas.map((f) => f.codigo)).toEqual(['01', '02'])
    expect(filas[0].expandible).toBe(true)
    expect(filas[1].expandible).toBe(false)
  })

  it('expandir un padre revela sus hijos directos', () => {
    const filas = filasComparativoVisibles(arbol, new Set(['01']))
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '02'])
    expect(filas.find((f) => f.codigo === '01')?.expandido).toBe(true)
  })

  it('un nieto solo es visible si toda su cadena está expandida', () => {
    expect(filasComparativoVisibles(arbol, new Set(['01'])).map((f) => f.codigo)).not.toContain('01.01.01.01')
    expect(filasComparativoVisibles(arbol, new Set(['01', '01.01'])).map((f) => f.codigo)).toContain('01.01.01.01')
  })
})

describe('claseDelta', () => {
  it('sobrecosto / ahorro / neutro', () => {
    expect(claseDelta(50, 'modificado')).toBe('pdc-cmp-sobrecosto')
    expect(claseDelta(-50, 'eliminado')).toBe('pdc-cmp-ahorro')
    expect(claseDelta(0, 'igual')).toBe('')
  })
})
```

- [ ] **Step 3: Correr y ver que falla**

Run: `npx vitest run src/lib/comparativo.test.ts`
Expected: FAIL — `Cannot find module './comparativo'`.

- [ ] **Step 4: Implementar `src/lib/comparativo.ts`**

```ts
import type { ActividadDiff } from './types'

export type FilaComparativo = ActividadDiff & {
  key: string
  expandible: boolean
  expandido: boolean
}

/** Filas de actividades visibles según el set de códigos expandidos (jerarquía por codigo/codigoPadre). */
export function filasComparativoVisibles(actividades: ActividadDiff[], expandidos: Set<string>): FilaComparativo[] {
  const porCodigo = new Map(actividades.map((a) => [a.codigo, a]))
  const tieneHijos = new Set(actividades.filter((a) => a.codigoPadre !== null).map((a) => a.codigoPadre as string))

  const visible = (a: ActividadDiff): boolean => {
    let padre = a.codigoPadre
    while (padre !== null) {
      if (!expandidos.has(padre)) return false
      padre = porCodigo.get(padre)?.codigoPadre ?? null
    }
    return true
  }

  const filas: FilaComparativo[] = []
  for (const a of actividades) {
    if (!visible(a)) continue
    filas.push({
      ...a,
      key: a.codigo,
      expandible: tieneHijos.has(a.codigo),
      expandido: expandidos.has(a.codigo),
    })
  }
  return filas
}

/** Clase CSS para colorear un delta: sobrecosto (sube) / ahorro (baja) / neutro. */
export function claseDelta(deltaValor: number, estado: string): string {
  if (estado === 'igual' || deltaValor === 0) return ''
  return deltaValor > 0 ? 'pdc-cmp-sobrecosto' : 'pdc-cmp-ahorro'
}
```

- [ ] **Step 5: Correr y ver que pasa**

Run: `npx vitest run src/lib/comparativo.test.ts`
Expected: PASS (todos).

- [ ] **Step 6: Commit**

```bash
git add src/lib/comparativo.ts src/lib/comparativo.test.ts src/lib/types.ts
git commit -m "feat(pdc): logica pura del comparativo (arbol diff visible + clase de color)"
```

---

### Task 4: SPA — vista Comparativo + ruta/nav (plan-de-compras)

**Files:**
- Create: `src/pages/ComparativoPresupuesto.tsx`
- Modify: `src/App.tsx`, `src/styles.css`

**Interfaces:**
- Consumes: `apiGet`/`PdcApiError`, `VersionPresupuesto`, `Comparativo`, `filasComparativoVisibles`/`claseDelta`.

- [ ] **Step 1: Branch (repo SPA)**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git checkout main && git checkout -b pdc-a16-comparativo
```

- [ ] **Step 2: Vista `src/pages/ComparativoPresupuesto.tsx`**

```tsx
import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ClientSideRowModelModule, ModuleRegistry, ValidationModule, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet } from '../lib/api'
import { claseDelta, filasComparativoVisibles } from '../lib/comparativo'
import type { FilaComparativo } from '../lib/comparativo'
import type { Comparativo, InsumoDiff, VersionPresupuesto } from '../lib/types'

ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule, // cellClassRules para colorear sobrecosto/ahorro
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e', foregroundColor: '#f4f1ea', accentColor: '#69b578', headerBackgroundColor: '#1a3c2a',
})

const moneda = (v: number | null) => (v == null || v === 0 ? '' : `$ ${v.toLocaleString('es-CO')}`)
const signo = (v: number) => (v > 0 ? '+' : '') + v.toLocaleString('es-CO')

export default function ComparativoPresupuesto() {
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [idA, setIdA] = useState<number | null>(null)
  const [idB, setIdB] = useState<number | null>(null)
  const [data, setData] = useState<Comparativo | null>(null)
  const [eje, setEje] = useState<'actividades' | 'insumos'>('insumos')
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => {
        setVersiones(d.versiones)
        // Preselección: B = activa (o la más reciente), A = la inmediatamente anterior.
        if (d.versiones.length >= 2) {
          const activa = d.versiones.find((v) => v.activa === 1) ?? d.versiones[0]
          const anterior = d.versiones.find((v) => v.id !== activa.id) ?? d.versiones[1]
          setIdB(activa.id)
          setIdA(anterior.id)
        }
      })
      .catch(() => setVersiones([]))
  }, [])

  useEffect(() => {
    if (idA == null || idB == null || idA === idB) { setData(null); return }
    setError(null)
    apiGet<Comparativo>(`/plan-compras/api/presupuesto/comparar?versionA=${idA}&versionB=${idB}`)
      .then((d) => { setData(d); setExpandidos(new Set()) })
      .catch((e) => {
        setData(null)
        setError(e instanceof PdcApiError ? e.message : e instanceof Error ? e.message : String(e))
      })
  }, [idA, idB])

  const filasAct = useMemo(
    () => (data ? filasComparativoVisibles(data.actividades, expandidos) : []),
    [data, expandidos],
  )

  const colsAct: ColDef<FilaComparativo>[] = useMemo(() => [
    {
      field: 'descripcion', headerName: 'Actividad', flex: 1, minWidth: 320, cellClass: 'pdc-visor-descripcion',
      valueFormatter: (p) => {
        const f = p.data as FilaComparativo
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    { field: 'valorA', headerName: 'Versión A', width: 150, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorB', headerName: 'Versión B', width: 150, valueFormatter: (p) => moneda(p.value) },
    {
      field: 'deltaValor', headerName: 'Δ', width: 140, valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as FilaComparativo).estado),
    },
    { field: 'estado', headerName: 'Estado', width: 120 },
  ], [])

  const colsIns: ColDef<InsumoDiff>[] = useMemo(() => [
    { field: 'descripcion', headerName: 'Insumo', flex: 1, minWidth: 280 },
    { field: 'tipoInsumo', headerName: 'Tipo', width: 150 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'valorA', headerName: 'Versión A', width: 150, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorB', headerName: 'Versión B', width: 150, valueFormatter: (p) => moneda(p.value) },
    {
      field: 'deltaValor', headerName: 'Δ', width: 140, valueFormatter: (p) => signo(p.value),
      cellClass: (p) => claseDelta(p.value, (p.data as InsumoDiff).estado),
    },
    { field: 'estado', headerName: 'Estado', width: 120 },
  ], [])

  const onCellClickedAct = (e: CellClickedEvent<FilaComparativo>) => {
    const f = e.data
    if (!f || !f.expandible || e.colDef.field !== 'descripcion') return
    setExpandidos((prev) => {
      const next = new Set(prev)
      if (next.has(f.key)) next.delete(f.key)
      else next.add(f.key)
      return next
    })
  }

  const selectorVersion = (value: number | null, on: (id: number | null) => void, testid: string) => (
    <select data-testid={testid} value={value ?? ''} onChange={(e) => on(e.target.value === '' ? null : Number(e.target.value))}>
      <option value="">—</option>
      {versiones.map((v) => (
        <option key={v.id} value={v.id}>{v.versionLabel} — {v.createdAt}{v.activa ? ' (activa)' : ''}</option>
      ))}
    </select>
  )

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Comparativo de versiones</h1>
          <p>Elige dos versiones para ver qué cambió: sobrecostos y ahorros por actividad e insumo.</p>
        </div>
        <div className="pdc-cmp-selectores">
          <label className="pdc-selector">A {selectorVersion(idA, setIdA, 'pdc-cmp-version-a')}</label>
          <label className="pdc-selector">B {selectorVersion(idB, setIdB, 'pdc-cmp-version-b')}</label>
        </div>
      </header>

      {error && <div className="pdc-error" role="alert">{error}</div>}
      {versiones.length < 2 && (
        <div className="pdc-bloque pdc-vacio">Necesitas al menos dos versiones importadas para comparar.</div>
      )}

      {data && (
        <>
          <div className="pdc-cmp-resumen" data-testid="pdc-cmp-resumen">
            <span>{moneda(data.resumen.costoA)} → {moneda(data.resumen.costoB)}</span>
            <span className={claseDelta(data.resumen.delta, data.resumen.delta === 0 ? 'igual' : 'modificado')}>
              Δ {signo(data.resumen.delta)}
            </span>
            <span className="pdc-cmp-sobrecosto">Sobrecostos {moneda(data.resumen.sobrecostos)}</span>
            <span className="pdc-cmp-ahorro">Ahorros {moneda(data.resumen.ahorros)}</span>
            <span>{data.resumen.nuevos} nuevos · {data.resumen.eliminados} eliminados · {data.resumen.modificados} modificados</span>
          </div>

          <div className="pdc-cmp-toggle">
            <button type="button" className={eje === 'insumos' ? 'activo' : ''} onClick={() => setEje('insumos')} data-testid="pdc-cmp-eje-insumos">Insumos</button>
            <button type="button" className={eje === 'actividades' ? 'activo' : ''} onClick={() => setEje('actividades')} data-testid="pdc-cmp-eje-actividades">Actividades</button>
          </div>

          <div style={{ height: 520 }} data-testid="pdc-cmp-grid">
            {eje === 'actividades' ? (
              <AgGridReact<FilaComparativo> theme={pdcTheme} rowData={filasAct} columnDefs={colsAct} getRowId={(p) => p.data.key} onCellClicked={onCellClickedAct} />
            ) : (
              <AgGridReact<InsumoDiff> theme={pdcTheme} rowData={data.insumos} columnDefs={colsIns} getRowId={(p) => p.data.descripcionNorm} />
            )}
          </div>
        </>
      )}
    </section>
  )
}
```

- [ ] **Step 3: Ruta y NavLink en `src/App.tsx`** — importar la vista, añadir el NavLink tras "Presupuesto" y la ruta:

```tsx
import ComparativoPresupuesto from './pages/ComparativoPresupuesto'
```
```tsx
<NavLink to="/ensamble/comparar" className="pdc-nav-link">Comparar</NavLink>
```
```tsx
<Route path="/ensamble/comparar" element={<ComparativoPresupuesto />} />
```

- [ ] **Step 4: Estilos en `src/styles.css`** (añadir):

```css
.pdc-cmp-selectores { display: flex; gap: 16px; flex-wrap: wrap; }
.pdc-cmp-resumen { display: flex; gap: 20px; flex-wrap: wrap; align-items: center; padding: 12px 0; font-size: 14px; }
.pdc-cmp-toggle { display: flex; gap: 8px; margin: 8px 0; }
.pdc-cmp-toggle button { background: #2c2c2e; color: #f4f1ea; border: 1px solid #3a3a3c; border-radius: 6px; padding: 6px 14px; cursor: pointer; }
.pdc-cmp-toggle button.activo { background: #1a3c2a; border-color: #69b578; }
.pdc-cmp-sobrecosto { color: #ff6b6b; }
.pdc-cmp-ahorro { color: #69b578; }
```

- [ ] **Step 5: Verificar y commit**

```bash
npm run test && npm run build
git add src/pages/ComparativoPresupuesto.tsx src/App.tsx src/styles.css
git commit -m "feat(pdc): vista Comparativo de versiones (selectores A/B, resumen, ejes actividades/insumos)"
```

Expected: Vitest verde (incluye los 4 nuevos de comparativo); build OK.

---

### Task 5: Bundle + e2e + docs (lps-aia + plan-de-compras)

**Files:**
- Create (lps-aia): `tests/browser/pdc-v2-comparar.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md`, `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`
- Generated (lps-aia): `public/pdc-app/**`

**Interfaces:**
- Consumes: `npm run sync`; helpers `loginAndSelectProject`/`logout`; el fixture de import de A1 (para tener 2 versiones); test-ids de T4.

- [ ] **Step 1: Sync del bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run sync
```

- [ ] **Step 2: Escribir el e2e** — `tests/browser/pdc-v2-comparar.spec.mjs` (leer antes `tests/browser/pdc-v2-import.spec.mjs` para el idioma de import de dos versiones por la UI; si el proyecto Da Porto ya tiene ≥2 versiones, basta con abrir Comparar):

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test('comparativo: dos versiones muestran resumen y ejes', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Prep: asegurar ≥2 versiones importadas (reusar el flujo de import del spec pdc-v2-import;
    // <adaptar: importar el fixture mini dos veces por la vista Importar si el proyecto no tiene 2 versiones>).

    await page.goto('/plan-compras#/ensamble/comparar', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Comparativo de versiones', { timeout: 15000 });

    // Con ≥2 versiones, la preselección A/B dispara la comparación.
    await expect(page.locator('[data-testid="pdc-cmp-resumen"]')).toBeVisible({ timeout: 15000 });

    // Toggle a Actividades y de vuelta a Insumos.
    await page.locator('[data-testid="pdc-cmp-eje-actividades"]').click();
    await expect(page.locator('[data-testid="pdc-cmp-grid"] .ag-row').first()).toBeVisible({ timeout: 15000 });
    await page.locator('[data-testid="pdc-cmp-eje-insumos"]').click();

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
npx playwright test tests/browser/pdc-v2-comparar.spec.mjs --workers=1
npx playwright test tests/browser/pdc-v2-visor.spec.mjs tests/browser/pdc-v2-import.spec.mjs --workers=1
```

Expected: comparar passed; regresión visor/import verde.

- [ ] **Step 4: Commit lps-aia**

```bash
git add public/pdc-app tests/browser/pdc-v2-comparar.spec.mjs
git commit -m "feat(pdc-v2): bundle con la vista Comparar + e2e del comparativo de versiones"
```

- [ ] **Step 5: CLAUDE.md + roadmap en plan-de-compras + commit**

En `CLAUDE.md`, en "Estado actual", añadir A1.6 a la lista de fases implementadas con una frase: *comparativo de versiones (`#/ensamble/comparar`): diff por actividad (jerárquico) e insumo (Pareto), sobrecostos vs ahorros; endpoint `GET /plan-compras/api/presupuesto/comparar`, sin migraciones.* — preservando el resto.

En el roadmap `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md`, marcar la Fase A1.6 con *(Implementada.)* al final de su entrada.

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md
git commit -m "docs(pdc): CLAUDE.md y roadmap reflejan la Fase A1.6 (comparativo de versiones)"
```

---

## Verificación end-to-end (tras Task 5)

1. lps-aia: `test_pdc_v2_comparar.php` exit 0; regresión `test_pdc_v2_arbol.php` + `test_pdc_v2_import.php` verdes; gates safety+reconciliation exit 0; PHPStan `src/Services/Pdc src/Controllers/Api` limpio.
2. plan-de-compras: Vitest (incluye 4 de comparativo) + build OK.
3. e2e: `pdc-v2-comparar` + regresión visor/import → passed.
4. Visual (navegador integrado, cierre de sprint): login → Da Porto → Comparar → resumen y ejes.

## Riesgos anotados

- **Roll-up de actividades en PHP:** O(items) por versión; medido barato a escala DAPORTO. Si un presupuesto real lo exige, mover a SQL recursivo (follow-up).
- **Clave de fusión de insumos = `norm|unidad`** (igual que A2): dos insumos con misma descripción normalizada pero distinta unidad son renglones distintos del diff (correcto). La `descripcionNorm` que se muestra es la norma pura (sin la unidad concatenada).
- **Preselección A/B:** activa vs la más reciente distinta; el usuario puede elegir cualquier par. Si hay una sola versión, la vista muestra el estado vacío.
