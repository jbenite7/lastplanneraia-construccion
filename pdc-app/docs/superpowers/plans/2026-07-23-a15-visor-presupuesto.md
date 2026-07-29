# Fase A1.5: Visor del Presupuesto — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vista de solo lectura del presupuesto importado: árbol jerárquico expandible (capítulos → subcapítulos → grupos → actividades → insumos de APU) con selector de versión y totales roll-up, en `#/ensamble/presupuesto`.

**Architecture:** Un endpoint de lectura (`GET /plan-compras/api/presupuesto/arbol?versionId=N`) devuelve items e insumos PLANOS de la versión pedida (default: la activa); la SPA arma el árbol con lógica pura testeada (`presupuestoTree.ts`: visibilidad por expandidos + inserción de filas de insumos + totales roll-up) y lo pinta en AG Grid Community (sin tree-data Enterprise: indentación + toggle por clic).

**Tech Stack:** PHP 8.3 (servicio/controller existentes de A1, sin migraciones), React+TS+AG Grid Community, Vitest, Playwright.

## Global Constraints

- Envelope `{ok,data|error}` con `JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE`; errores: `NO_PROJECT` 409, `NO_VERSION` 404, `FORBIDDEN` 403.
- Lectura = RBAC `lps.pdc.ver` (sin CSRF: solo GET). PDO prepared con `project_id = ?` explícito.
- AG Grid **Community**; nada de Enterprise/tree-data nativo. Números al grid como `number` (recordar: nada de booleans donde haya valueFormatter — cellDataType).
- Tests: PHP autoejecutable sobre MySQL real de Docker (proyectos 999901/999902, cleanup idempotente); Vitest para la lógica del árbol; e2e Playwright con el fixture determinista existente.
- Ramas: `pdc-a15-visor` en ambos repos. Commits `feat(pdc-v2)` (lps-aia) / `feat(pdc)` (plan-de-compras). Repos con sucios ajenos: commitear SOLO los archivos de cada task.
- Docker: `docker compose up -d app db` (mount en vivo — sin rebuild).

---

## File Structure

**lps-aia (rama `pdc-a15-visor`):**
```
src/Services/Pdc/PresupuestoImportService.php   # T1: Modify (+arbol())
tests/test_pdc_v2_arbol.php                     # T1
src/Controllers/Api/PlanComprasImportController.php  # T2: Modify (+arbol())
public/index.php                                # T2: +1 ruta GET
tests/browser/pdc-v2-visor.spec.mjs             # T5
public/pdc-app/**                               # T5: bundle regenerado
```

**plan-de-compras (rama `pdc-a15-visor`):**
```
src/lib/types.ts             # T3: Modify (+tipos árbol)
src/lib/presupuestoTree.ts   # T3
src/lib/presupuestoTree.test.ts  # T3
src/pages/VisorPresupuesto.tsx   # T4
src/App.tsx                  # T4: Modify (+ruta y nav)
src/styles.css               # T4: Modify (+estilos visor)
CLAUDE.md                    # T5: Modify (estado)
```

---

### Task 1: Servicio `arbol()` (lps-aia, TDD)

**Files:**
- Modify: `src/Services/Pdc/PresupuestoImportService.php` (añadir método al final, antes del cierre de clase)
- Test: `tests/test_pdc_v2_arbol.php`

**Interfaces:**
- Consumes: tablas `pdc_presupuesto_*` (A1), `Database::query`.
- Produces: `arbol(int $projectId, ?int $versionId = null): ?array` — `null` si el proyecto no tiene esa versión (o ninguna activa cuando `$versionId===null`). Éxito:
  `['version'=>['id'=>int,'versionLabel'=>string,'activa'=>int], 'items'=>list<['id'=>int,'codigo'=>string,'codigoPadre'=>?string,'nivel'=>int,'tipoFila'=>string,'descripcion'=>string,'unidad'=>?string,'cantidad'=>?float]>, 'insumos'=>list<['itemId'=>int,'descripcion'=>string,'tipoInsumo'=>string,'unidad'=>string,'cantApu'=>?float,'rendimiento'=>?float,'cantidadTotal'=>?float,'valorUnitario'=>?float,'valorTotal'=>?float]>]`
  Items ordenados por `id` ASC (orden de import = orden del Excel); insumos por `id` ASC.

- [ ] **Step 1: Branch en lps-aia**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && git checkout main -q && git checkout -b pdc-a15-visor
```

- [ ] **Step 2: Test que falla — `tests/test_pdc_v2_arbol.php`**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_ARBOL_PROJECT_A = 999901;
const PDC_ARBOL_PROJECT_B = 999902;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_ARBOL_PROJECT_A, PDC_ARBOL_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

echo "=== PDC v2: arbol() del visor ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-arbol-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Sin versiones → null.
$assert($service->arbol(PDC_ARBOL_PROJECT_A) === null, 'Proyecto sin versiones → null.');

// Importar el fixture válido (8 items / 4 insumos) y pedir el árbol de la activa.
$tmp = sys_get_temp_dir() . '/pdc_arbol_v1.xlsx';
pdcFixturePresupuestoValido($tmp);
$p1 = $service->previewDesdeArchivo($tmp, 'v1.xlsx', PDC_ARBOL_PROJECT_A, 'tester');
$c1 = $service->confirmar($p1['importToken'], PDC_ARBOL_PROJECT_A);

$a = $service->arbol(PDC_ARBOL_PROJECT_A);
$assert($a !== null && $a['version']['id'] === $c1['versionId'], 'Sin versionId devuelve la versión activa.');
$assert(count($a['items']) === 8 && count($a['insumos']) === 4, 'Árbol con 8 items y 4 insumos.');
$assert($a['items'][0]['codigo'] === '01' && $a['items'][0]['tipoFila'] === 'capitulo', 'Primer item = capítulo 01 (orden del Excel).');
$act = array_values(array_filter($a['items'], fn ($i) => $i['codigo'] === '01.01.01.01'))[0];
$assert($act['tipoFila'] === 'actividad' && abs($act['cantidad'] - 18.0) < 0.001, 'Actividad con cantidad 18.');
$insumosAct = array_values(array_filter($a['insumos'], fn ($i) => $i['itemId'] === $act['id']));
$assert(count($insumosAct) === 2 && $insumosAct[0]['descripcion'] === 'TEJA DE ZINC', 'Insumos amarrados por itemId, en orden.');
$assert(abs($insumosAct[0]['valorTotal'] - 540000.0) < 0.01, 'valorTotal del insumo (21.6 × 25000).');

// Segunda versión → la activa cambia; la histórica sigue consultable por versionId.
$tmp2 = sys_get_temp_dir() . '/pdc_arbol_v2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2 = $service->previewDesdeArchivo($tmp2, 'v2.xlsx', PDC_ARBOL_PROJECT_A, 'tester');
$c2 = $service->confirmar($p2['importToken'], PDC_ARBOL_PROJECT_A);
$assert($service->arbol(PDC_ARBOL_PROJECT_A)['version']['id'] === $c2['versionId'], 'La activa es la nueva.');
$hist = $service->arbol(PDC_ARBOL_PROJECT_A, $c1['versionId']);
$assert($hist !== null && $hist['version']['id'] === $c1['versionId'] && (int) $hist['version']['activa'] === 0, 'Versión histórica consultable por id.');

// Aislamiento: B no ve la versión de A ni por id.
$assert($service->arbol(PDC_ARBOL_PROJECT_B) === null, 'Proyecto B sin árbol.');
$assert($service->arbol(PDC_ARBOL_PROJECT_B, $c1['versionId']) === null, 'Proyecto B no accede a versión de A por id.');

foreach ([$tmp, $tmp2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 3: RED**

```bash
docker compose up -d app db
docker compose exec app php tests/test_pdc_v2_arbol.php
```

Expected: FAIL — `Call to undefined method ...::arbol()`.

- [ ] **Step 4: Implementar `arbol()`** (añadir a `PresupuestoImportService`):

```php
    /** Árbol plano del presupuesto de una versión (default: la activa), o null si no existe. */
    public function arbol(int $projectId, ?int $versionId = null): ?array
    {
        if ($versionId === null) {
            $version = $this->db->query(
                'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
                [$projectId],
            )->fetch(\PDO::FETCH_ASSOC);
        } else {
            $version = $this->db->query(
                'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?',
                [$projectId, $versionId],
            )->fetch(\PDO::FETCH_ASSOC);
        }
        if ($version === false) {
            return null;
        }
        $vid = (int) $version['id'];

        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad
             FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $insumos = $this->db->query(
            'SELECT item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total
             FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ? ORDER BY id ASC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $num = static fn ($v): ?float => $v === null ? null : (float) $v;

        return [
            'version' => ['id' => $vid, 'versionLabel' => $version['version_label'], 'activa' => (int) $version['activa']],
            'items' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'codigo' => $r['codigo'],
                'codigoPadre' => $r['codigo_padre'],
                'nivel' => (int) $r['nivel'],
                'tipoFila' => $r['tipo_fila'],
                'descripcion' => $r['descripcion'],
                'unidad' => $r['unidad'],
                'cantidad' => $num($r['cantidad']),
            ], $items),
            'insumos' => array_map(static fn (array $r): array => [
                'itemId' => (int) $r['item_id'],
                'descripcion' => $r['descripcion'],
                'tipoInsumo' => $r['tipo_insumo'],
                'unidad' => $r['unidad'],
                'cantApu' => $num($r['cant_apu']),
                'rendimiento' => $num($r['rendimiento']),
                'cantidadTotal' => $num($r['cantidad_total']),
                'valorUnitario' => $num($r['valor_unitario']),
                'valorTotal' => $num($r['valor_total']),
            ], $insumos),
        ];
    }
```

- [ ] **Step 5: GREEN + PHPStan**

```bash
docker compose exec app php tests/test_pdc_v2_arbol.php
docker compose exec app vendor/bin/phpstan analyse src/Services/Pdc --memory-limit=1G
```

Expected: todos PASS, `=== OK ===`, exit 0; PHPStan `[OK] No errors`.

- [ ] **Step 6: Commit**

```bash
git add src/Services/Pdc/PresupuestoImportService.php tests/test_pdc_v2_arbol.php
git commit -m "feat(pdc-v2): arbol() del presupuesto por versión para el visor A1.5"
```

---

### Task 2: Endpoint `GET /plan-compras/api/presupuesto/arbol` (lps-aia)

**Files:**
- Modify: `src/Controllers/Api/PlanComprasImportController.php` (añadir método `arbol()`)
- Modify: `public/index.php` (1 ruta, junto a las de presupuesto)

**Interfaces:**
- Consumes: `PresupuestoImportService::arbol` (T1), trait `ok/fail`, `RbacService->can('lps.pdc.ver')`.
- Produces (contrato HTTP para T4): `GET /plan-compras/api/presupuesto/arbol[?versionId=N]` → `{ok:true,data:{version,items,insumos}}` | `NO_VERSION` 404 | `NO_PROJECT` 409 | `FORBIDDEN` 403. `versionId` no numérico se ignora (usa la activa).

- [ ] **Step 1: Ruta** en `public/index.php`, tras la ruta de `versiones`:

```php
$router->get('/plan-compras/api/presupuesto/arbol', [\App\Controllers\Api\PlanComprasImportController::class, 'arbol']);
```

- [ ] **Step 2: Método en el controller** (tras `versiones()`):

```php
    /** GET /plan-compras/api/presupuesto/arbol[?versionId=N] — solo lectura. */
    public function arbol(): void
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
        $versionId = filter_var($_GET['versionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $arbol = $this->service->arbol($projectId, $versionId === false ? null : $versionId);
        if ($arbol === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado (o la versión no existe).', 404);
            return;
        }
        $this->ok($arbol);
    }
```

- [ ] **Step 3: Verificación estática + regresiones**

```bash
docker compose exec app php -l src/Controllers/Api/PlanComprasImportController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasImportController.php --memory-limit=1G
docker compose exec app php tests/test_pdc_v2_import_flujo.php
docker compose exec app php tests/test_pdc_v2_contexto.php
```

Expected: sin errores; flujo y contexto siguen exit 0. (El wiring HTTP lo cubre el e2e de T5.)

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/Api/PlanComprasImportController.php public/index.php
git commit -m "feat(pdc-v2): endpoint de árbol del presupuesto con selector de versión"
```

---

### Task 3: Lógica pura del árbol en la SPA (plan-de-compras, TDD)

**Files:**
- Modify: `src/lib/types.ts` (tipos del árbol)
- Create: `src/lib/presupuestoTree.ts`
- Test: `src/lib/presupuestoTree.test.ts`

**Interfaces:**
- Produces (T4 los consume):
  - Tipos en `types.ts`: `ArbolItem {id:number; codigo:string; codigoPadre:string|null; nivel:number; tipoFila:'capitulo'|'subcapitulo'|'grupo'|'actividad'; descripcion:string; unidad:string|null; cantidad:number|null}`, `ArbolInsumo {itemId:number; descripcion:string; tipoInsumo:string; unidad:string; cantApu:number|null; rendimiento:number|null; cantidadTotal:number|null; valorUnitario:number|null; valorTotal:number|null}`, `ArbolPresupuesto {version:{id:number; versionLabel:string; activa:number}; items:ArbolItem[]; insumos:ArbolInsumo[]}`.
  - En `presupuestoTree.ts`: `FilaVisor {key:string; tipo:'item'|'insumo'; nivel:number; codigo:string; descripcion:string; unidad:string|null; cantidad:number|null; tipoInsumo:string|null; valorUnitario:number|null; valorTotal:number|null; expandible:boolean; expandido:boolean}`; `totalesPorItem(items, insumos): Map<number, number>` (roll-up: actividad = suma de sus insumos; padre = suma de hijos); `filasVisibles(items, insumos, expandidos: Set<string>): FilaVisor[]` (raíces siempre visibles; hijos solo si TODOS los ancestros están en `expandidos`; actividad expandida añade sus insumos como filas `tipo:'insumo'` con `key` = `i:{itemId}:{idx}`; `key` de items = su `codigo`).

- [ ] **Step 1: Branch en plan-de-compras**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git checkout main -q && git checkout -b pdc-a15-visor
```

- [ ] **Step 2: Test que falla — `src/lib/presupuestoTree.test.ts`**

```ts
import { describe, expect, it } from 'vitest'
import { filasVisibles, totalesPorItem } from './presupuestoTree'
import type { ArbolInsumo, ArbolItem } from './types'

const items: ArbolItem[] = [
  { id: 1, codigo: '01', codigoPadre: null, nivel: 1, tipoFila: 'capitulo', descripcion: 'PRELIMINARES', unidad: null, cantidad: null },
  { id: 2, codigo: '01.01', codigoPadre: '01', nivel: 2, tipoFila: 'subcapitulo', descripcion: 'CAMPAMENTO', unidad: null, cantidad: null },
  { id: 3, codigo: '01.01.01', codigoPadre: '01.01', nivel: 3, tipoFila: 'grupo', descripcion: 'INSTALACIONES', unidad: null, cantidad: null },
  { id: 4, codigo: '01.01.01.01', codigoPadre: '01.01.01', nivel: 4, tipoFila: 'actividad', descripcion: 'CASETA', unidad: 'M2', cantidad: 18 },
  { id: 5, codigo: '02', codigoPadre: null, nivel: 1, tipoFila: 'capitulo', descripcion: 'ESTRUCTURA', unidad: null, cantidad: null },
]

const insumos: ArbolInsumo[] = [
  { itemId: 4, descripcion: 'TEJA', tipoInsumo: 'MAT', unidad: 'M2', cantApu: 1.05, rendimiento: 1.2, cantidadTotal: 21.6, valorUnitario: 25000, valorTotal: 540000 },
  { itemId: 4, descripcion: 'AYUDANTE', tipoInsumo: 'MO', unidad: 'HC', cantApu: 8, rendimiento: 0.5, cantidadTotal: 9, valorUnitario: 9500, valorTotal: 85500 },
]

describe('totalesPorItem', () => {
  it('actividad suma sus insumos y los padres hacen roll-up', () => {
    const t = totalesPorItem(items, insumos)
    expect(t.get(4)).toBe(625500)   // 540000 + 85500
    expect(t.get(3)).toBe(625500)   // grupo
    expect(t.get(2)).toBe(625500)   // subcapítulo
    expect(t.get(1)).toBe(625500)   // capítulo
    expect(t.get(5)).toBe(0)        // capítulo sin actividades
  })
})

describe('filasVisibles', () => {
  it('colapsado: solo raíces, marcadas expandibles', () => {
    const filas = filasVisibles(items, insumos, new Set())
    expect(filas.map((f) => f.codigo)).toEqual(['01', '02'])
    expect(filas[0].expandible).toBe(true)
    expect(filas[0].expandido).toBe(false)
    expect(filas[1].expandible).toBe(false) // '02' no tiene hijos
  })

  it('expandir en cadena revela cada nivel', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01']))
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '01.01.01', '02'])
  })

  it('un hijo NO aparece si falta un ancestro intermedio', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01.01'])) // falta 01.01
    expect(filas.map((f) => f.codigo)).toEqual(['01', '01.01', '02'])
  })

  it('actividad expandida inserta sus insumos como filas', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01', '01.01.01', '01.01.01.01']))
    const desc = filas.map((f) => `${f.tipo}:${f.descripcion}`)
    expect(desc).toEqual([
      'item:PRELIMINARES', 'item:CAMPAMENTO', 'item:INSTALACIONES', 'item:CASETA',
      'insumo:TEJA', 'insumo:AYUDANTE', 'item:ESTRUCTURA',
    ])
    const teja = filas[4]
    expect(teja.nivel).toBe(5)
    expect(teja.valorTotal).toBe(540000)
    expect(teja.expandible).toBe(false)
  })

  it('la fila de actividad lleva el total roll-up en valorTotal', () => {
    const filas = filasVisibles(items, insumos, new Set(['01', '01.01', '01.01.01']))
    const caseta = filas.find((f) => f.codigo === '01.01.01.01')!
    expect(caseta.valorTotal).toBe(625500)
    expect(caseta.expandible).toBe(true) // tiene insumos
  })
})
```

- [ ] **Step 3: RED** — `npx vitest run src/lib/presupuestoTree.test.ts` → FAIL (módulo no existe).

- [ ] **Step 4: Tipos en `types.ts`** (añadir al final, formas EXACTAS del bloque Interfaces) y **implementar `src/lib/presupuestoTree.ts`**:

```ts
import type { ArbolInsumo, ArbolItem } from './types'

export type FilaVisor = {
  key: string
  tipo: 'item' | 'insumo'
  nivel: number
  codigo: string
  descripcion: string
  unidad: string | null
  cantidad: number | null
  tipoInsumo: string | null
  valorUnitario: number | null
  valorTotal: number | null
  expandible: boolean
  expandido: boolean
}

// Roll-up de costos: actividad = suma de sus insumos; cada padre = suma de sus hijos.
export function totalesPorItem(items: ArbolItem[], insumos: ArbolInsumo[]): Map<number, number> {
  const totales = new Map<number, number>()
  const porCodigo = new Map(items.map((i) => [i.codigo, i]))
  for (const item of items) totales.set(item.id, 0)
  for (const ins of insumos) {
    totales.set(ins.itemId, (totales.get(ins.itemId) ?? 0) + (ins.valorTotal ?? 0))
  }
  // Propagar de hojas a raíces: recorrer por nivel descendente garantiza hijos antes que padres.
  const porNivelDesc = [...items].sort((a, b) => b.nivel - a.nivel)
  for (const item of porNivelDesc) {
    if (item.codigoPadre === null) continue
    const padre = porCodigo.get(item.codigoPadre)
    if (padre) totales.set(padre.id, (totales.get(padre.id) ?? 0) + (totales.get(item.id) ?? 0))
  }
  return totales
}

export function filasVisibles(items: ArbolItem[], insumos: ArbolInsumo[], expandidos: Set<string>): FilaVisor[] {
  const totales = totalesPorItem(items, insumos)
  const tieneHijos = new Set(items.filter((i) => i.codigoPadre !== null).map((i) => i.codigoPadre as string))
  const insumosPorItem = new Map<number, ArbolInsumo[]>()
  for (const ins of insumos) {
    const lista = insumosPorItem.get(ins.itemId) ?? []
    lista.push(ins)
    insumosPorItem.set(ins.itemId, lista)
  }
  const porCodigo = new Map(items.map((i) => [i.codigo, i]))

  const visible = (item: ArbolItem): boolean => {
    let padre = item.codigoPadre
    while (padre !== null) {
      if (!expandidos.has(padre)) return false
      padre = porCodigo.get(padre)?.codigoPadre ?? null
    }
    return true
  }

  const filas: FilaVisor[] = []
  for (const item of items) {
    if (!visible(item)) continue
    const propios = insumosPorItem.get(item.id) ?? []
    const expandible = tieneHijos.has(item.codigo) || propios.length > 0
    filas.push({
      key: item.codigo,
      tipo: 'item',
      nivel: item.nivel,
      codigo: item.codigo,
      descripcion: item.descripcion,
      unidad: item.unidad,
      cantidad: item.cantidad,
      tipoInsumo: null,
      valorUnitario: null,
      valorTotal: totales.get(item.id) ?? 0,
      expandible,
      expandido: expandidos.has(item.codigo),
    })
    if (item.tipoFila === 'actividad' && expandidos.has(item.codigo)) {
      propios.forEach((ins, idx) => {
        filas.push({
          key: `i:${item.id}:${idx}`,
          tipo: 'insumo',
          nivel: item.nivel + 1,
          codigo: '',
          descripcion: ins.descripcion,
          unidad: ins.unidad,
          cantidad: ins.cantidadTotal,
          tipoInsumo: ins.tipoInsumo,
          valorUnitario: ins.valorUnitario,
          valorTotal: ins.valorTotal,
          expandible: false,
          expandido: false,
        })
      })
    }
  }
  return filas
}
```

Tipos para `types.ts` (añadir al final):

```ts
export type ArbolItem = {
  id: number
  codigo: string
  codigoPadre: string | null
  nivel: number
  tipoFila: 'capitulo' | 'subcapitulo' | 'grupo' | 'actividad'
  descripcion: string
  unidad: string | null
  cantidad: number | null
}

export type ArbolInsumo = {
  itemId: number
  descripcion: string
  tipoInsumo: string
  unidad: string
  cantApu: number | null
  rendimiento: number | null
  cantidadTotal: number | null
  valorUnitario: number | null
  valorTotal: number | null
}

export type ArbolPresupuesto = {
  version: { id: number; versionLabel: string; activa: number }
  items: ArbolItem[]
  insumos: ArbolInsumo[]
}
```

- [ ] **Step 5: GREEN completo** — `npm run test` → 18 + 6 = **24 tests PASS**; `npm run build` OK.

- [ ] **Step 6: Commit**

```bash
git add src/lib/types.ts src/lib/presupuestoTree.ts src/lib/presupuestoTree.test.ts
git commit -m "feat(pdc): lógica pura del árbol del visor (visibilidad, insumos y totales roll-up)"
```

---

### Task 4: Vista `VisorPresupuesto` + nav (plan-de-compras)

**Files:**
- Create: `src/pages/VisorPresupuesto.tsx`
- Modify: `src/App.tsx` (nav + ruta), `src/styles.css`

**Interfaces:**
- Consumes: `apiGet`, `PdcApiError` (con code `NO_VERSION`), `filasVisibles`/`FilaVisor`, tipos `ArbolPresupuesto`/`VersionPresupuesto`.
- Produces (contrato e2e T5): título `h1` "Presupuesto"; `select[data-testid="pdc-visor-version"]`; grilla `[data-testid="pdc-visor-arbol"]`; clic en la celda descripción togglea expansión; estado vacío `[data-testid="pdc-visor-vacio"]` cuando `NO_VERSION`.

- [ ] **Step 1: `src/pages/VisorPresupuesto.tsx`**

```tsx
import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { ClientSideRowModelModule, ModuleRegistry, themeQuartz } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import { PdcApiError, apiGet } from '../lib/api'
import { filasVisibles } from '../lib/presupuestoTree'
import type { FilaVisor } from '../lib/presupuestoTree'
import type { ArbolPresupuesto, VersionPresupuesto } from '../lib/types'

ModuleRegistry.registerModules([ClientSideRowModelModule])

const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

const moneda = (v: number | null) => (v == null || v === 0 ? '' : `$ ${v.toLocaleString('es-CO')}`)

export default function VisorPresupuesto() {
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [versionId, setVersionId] = useState<number | null>(null)
  const [arbol, setArbol] = useState<ArbolPresupuesto | null>(null)
  const [expandidos, setExpandidos] = useState<Set<string>>(new Set())
  const [sinPresupuesto, setSinPresupuesto] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }, [])

  useEffect(() => {
    const q = versionId != null ? `?versionId=${versionId}` : ''
    setError(null)
    apiGet<ArbolPresupuesto>(`/plan-compras/api/presupuesto/arbol${q}`)
      .then((a) => {
        setArbol(a)
        setSinPresupuesto(false)
        setExpandidos(new Set())
      })
      .catch((e) => {
        if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinPresupuesto(true)
        else setError(e instanceof Error ? e.message : String(e))
      })
  }, [versionId])

  const filas = useMemo(
    () => (arbol ? filasVisibles(arbol.items, arbol.insumos, expandidos) : []),
    [arbol, expandidos],
  )

  const cols: ColDef<FilaVisor>[] = useMemo(() => [
    { field: 'codigo', headerName: 'Código', width: 130 },
    {
      field: 'descripcion', headerName: 'Descripción', flex: 1, minWidth: 320,
      valueFormatter: (p) => {
        const f = p.data as FilaVisor
        const sangria = ' '.repeat((f.nivel - 1) * 4)
        const marca = f.expandible ? (f.expandido ? '▾ ' : '▸ ') : f.tipo === 'insumo' ? '· ' : '  '
        return `${sangria}${marca}${f.descripcion}`
      },
    },
    { field: 'tipoInsumo', headerName: 'Tipo insumo', width: 160 },
    { field: 'unidad', headerName: 'Und', width: 80 },
    { field: 'cantidad', headerName: 'Cantidad', width: 110 },
    { field: 'valorUnitario', headerName: 'Vr. unitario', width: 130, valueFormatter: (p) => moneda(p.value) },
    { field: 'valorTotal', headerName: 'Valor total', width: 150, valueFormatter: (p) => moneda(p.value) },
  ], [])

  const onCellClicked = (e: CellClickedEvent<FilaVisor>) => {
    const f = e.data
    if (!f || !f.expandible || e.colDef.field !== 'descripcion') return
    setExpandidos((prev) => {
      const next = new Set(prev)
      if (next.has(f.key)) next.delete(f.key)
      else next.add(f.key)
      return next
    })
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Presupuesto</h1>
          <p>Vista del presupuesto importado. Haz clic en una fila para expandirla.</p>
        </div>
        {versiones.length > 0 && (
          <label className="pdc-selector">
            Versión{' '}
            <select
              data-testid="pdc-visor-version"
              value={versionId ?? ''}
              onChange={(e) => setVersionId(e.target.value === '' ? null : Number(e.target.value))}
            >
              <option value="">Activa</option>
              {versiones.map((v) => (
                <option key={v.id} value={v.id}>
                  {v.versionLabel} — {v.createdAt}{v.activa ? ' (activa)' : ''}
                </option>
              ))}
            </select>
          </label>
        )}
      </header>

      {error && <div className="pdc-error" role="alert">{error}</div>}

      {sinPresupuesto ? (
        <div className="pdc-bloque pdc-vacio" data-testid="pdc-visor-vacio">
          Este proyecto aún no tiene un presupuesto importado. Ve a <strong>Ensamble → Importar</strong>.
        </div>
      ) : (
        <div style={{ height: 560 }} data-testid="pdc-visor-arbol">
          <AgGridReact<FilaVisor>
            theme={pdcTheme}
            rowData={filas}
            columnDefs={cols}
            getRowId={(p) => p.data.key}
            onCellClicked={onCellClicked}
          />
        </div>
      )}
    </section>
  )
}
```

- [ ] **Step 2: Nav y ruta en `App.tsx`** — dentro del `<nav>` añadir tras el link de Ensamble:

```tsx
<NavLink to="/ensamble/presupuesto" className="pdc-nav-link">Presupuesto</NavLink>
```

y en `<Routes>`:

```tsx
<Route path="/ensamble/presupuesto" element={<VisorPresupuesto />} />
```

con su import. (El link "Ensamble" existente sigue apuntando a importar.)

- [ ] **Step 3: Estilos** (añadir a `styles.css`):

```css
.pdc-header-fila { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
.pdc-selector select { background: #2c2c2e; color: #f4f1ea; border: 1px solid #3a3a3c; border-radius: 6px; padding: 6px 10px; }
.pdc-vacio { padding: 24px; border: 1px dashed #3a3a3c; border-radius: 8px; opacity: 0.85; }
```

- [ ] **Step 4: Gates** — `npm run test` (24 PASS) && `npm run build` OK.

- [ ] **Step 5: Commit**

```bash
git add src/pages/VisorPresupuesto.tsx src/App.tsx src/styles.css
git commit -m "feat(pdc): vista del visor de presupuesto con árbol expandible y selector de versión"
```

---

### Task 5: Bundle + e2e + cierre de fase (ambos repos)

**Files:**
- Generated (lps-aia): `public/pdc-app/**` (`npm run sync`)
- Create (lps-aia): `tests/browser/pdc-v2-visor.spec.mjs`
- Modify (plan-de-compras): `CLAUDE.md` (línea de estado: añadir "visor `#/ensamble/presupuesto` (A1.5)")

**Interfaces:**
- Consumes: helpers `loginAndSelectProject`/`logout`, fixture Da Porto, selectores del T4, fixture `presupuesto-mini.xlsx` e import spec existentes.

- [ ] **Step 1: Sync + commit bundle**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run sync
cd "/Volumes/Crucial X6/Developer/lps-aia" && git add public/pdc-app && git commit -m "feat(pdc-v2): bundle con el visor de presupuesto A1.5"
```

- [ ] **Step 2: Spec e2e — `tests/browser/pdc-v2-visor.spec.mjs`**

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';

test('visor: árbol expandible del presupuesto activo con insumos y totales', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // Garantizar una versión activa: importar el fixture (idempotente para el visor).
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    // Ir al visor.
    await page.locator('nav >> text=Presupuesto').click();
    await expect(page.locator('h1')).toContainText('Presupuesto', { timeout: 15000 });
    const arbol = page.locator('[data-testid="pdc-visor-arbol"]');

    // Colapsado: capítulos con total roll-up.
    const cap = arbol.locator('.ag-cell', { hasText: 'PRELIMINARES' }).first();
    await expect(cap).toBeVisible({ timeout: 15000 });
    await expect(arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' })).toHaveCount(0);

    // Expandir cadena: 01 → 01.01 → 01.01.01 → actividad → insumos.
    await cap.click();
    await arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO' }).first().click();
    await arbol.locator('.ag-cell', { hasText: 'INSTALACIONES' }).first().click();
    await arbol.locator('.ag-cell', { hasText: 'CAMPAMENTO 18M2' }).first().click();
    await expect(arbol.locator('.ag-cell', { hasText: 'TEJA DE ZINC' }).first()).toBeVisible();
    await expect(arbol.locator('.ag-cell', { hasText: '$ 540.000' }).first()).toBeVisible();

    // Selector de versión presente.
    await expect(page.locator('[data-testid="pdc-visor-version"]')).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 3: Correr e2e (visor + regresión import/fundación)**

```bash
docker compose up -d app db
npx playwright test tests/browser/pdc-v2-visor.spec.mjs tests/browser/pdc-v2-import.spec.mjs tests/browser/pdc-v2-fundacion.spec.mjs --workers=1
```

Expected: **3 passed**. Si el clic en `CAMPAMENTO` matchea también `CAMPAMENTO 18M2` (substring), afinar el locator con exact/regex — sin debilitar asserts.

- [ ] **Step 4: Commits de cierre**

```bash
git add -f tests/browser/pdc-v2-visor.spec.mjs
git commit -m "test(pdc-v2): e2e del visor — árbol expandible, insumos y selector de versión"
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
# editar CLAUDE.md: en la línea de estado, tras "vista `#/ensamble/importar`", añadir " y visor `#/ensamble/presupuesto` (A1.5)"
git add CLAUDE.md && git commit -m "docs(pdc): CLAUDE.md refleja la Fase A1.5 (visor)"
```

---

## Verificación end-to-end

1. lps-aia: `test_pdc_v2_arbol.php` + suite A1 completa (`rbac_importar`, `import_parser`, `import_flujo`, `contexto`) + gates BD + PHPStan → todo exit 0 / OK.
2. plan-de-compras: `npm run test` (24) + `npm run build`.
3. e2e: visor + import + fundación → 3 passed.
4. Review final de rama (ambos repos) + merge local FF a main + verificación visual en el navegador (árbol expandido).

## Riesgos

- Substring matching en los clics del e2e (CAMPAMENTO vs CAMPAMENTO 18M2) — resolver con locators exactos si aparece.
- Presupuestos grandes (DAPORTO ~1300 filas): `filasVisibles` es O(n) por render — aceptable; virtualización de filas la da AG Grid.
