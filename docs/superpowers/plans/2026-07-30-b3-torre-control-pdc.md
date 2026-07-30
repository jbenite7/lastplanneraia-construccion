# Fase B3 — El plan de compras en la Torre de Control — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la Torre de Control (`/bi/control-tower`) responda «cómo van las compras en todas mis obras» con los cuatro indicadores del comité, consumiendo los cálculos que ya existen en el PDC v2 en vez de reimplementarlos.

**Architecture:** El informe `pdc` de la Torre deja de leer la tabla del PDC viejo (`bi_pdc_general`) y pasa a alimentarse del PDC v2. Un método nuevo en `SeguimientoService` agrega vencimientos para **varios proyectos en una sola consulta**, reusando la misma `clasificarVencimiento()` estática que ya usan la pestaña y el semáforo. `ControlTowerService` no escribe SQL sobre vencimientos: consume el agregado. La cobertura la sigue dando `PaquetesService::resumen()`.

**Tech Stack:** PHP 8.3 sobre Docker Compose (servicio `app`), MySQL 8.0, PDO con prepared statements vía `Database`. Frontend: PHP templates en `views/bi/` + `public/js/modules/bi-spa.js` (JS plano, sin framework). Tests: scripts autoejecutables `tests/test_*.php` (no hay PHPUnit).

## Global Constraints

- **Spec de referencia:** `docs/superpowers/specs/2026-07-29-b3-torre-control-pdc-design.md`. Sus seis decisiones son vinculantes.
- **La clasificación de vencimiento NO se reimplementa.** Único dueño: `SeguimientoService::clasificarVencimiento()`. `ControlTowerService` no escribe SQL sobre `pdc_plan_paso`.
- **Unidad de conteo: el destino** (`paquete_id + subpaquete_id`). Unir solo por `paquete_id` multiplica los conteos.
- **El nombre del proveedor no aparece** en ninguna respuesta del endpoint ni en la pantalla.
- **La fecha de hoy la pone el servidor**, nunca el navegador.
- **Aislamiento por proyecto:** toda consulta operativa filtra por `project_id`. El scope lo resuelve `BiProjectScope`; no se añade una capacidad RBAC nueva.
- **UI:** desktop ≥1180px y **solo dark**. Viewport canónico de validación 1180×820. Prohibido trabajar mobile, tablet o tema `linen`. Sin hex ni estilos inline nuevos: reusar las clases y tokens que ya usan las secciones vecinas de `views/bi/control-tower.php`.
- **Git:** commit en la rama `worktree-pdc-b3-torre-control` autorizado sin pedirlo (ver `goals/pdc-preparar-b1/estado-olas.md`, «Permisos de git»). **Push y `main`: no.**
- **Todo comando PHP corre dentro del contenedor:** `docker compose exec app php ...`. Nunca un PHP del host.

---

## File Structure

| Archivo | Responsabilidad | Acción |
|---|---|---|
| `src/Services/Pdc/SeguimientoService.php` | Dueño del cálculo de vencimiento. Gana `vencimientosAgregados()` para N proyectos | Modificar |
| `src/Services/ControlTowerService.php` | Compone el brief. `fetchPdc()` pasa al PDC v2; `scorecardPDC()`, lineage y filtros se ajustan | Modificar |
| `src/Controllers/Api/BiControlTowerApiController.php` | Endpoint del drill-down nuevo | Modificar |
| `public/index.php` | Ruta del drill-down | Modificar |
| `views/bi/control-tower.php:559-573` | Marcado de la sección PDC | Modificar |
| `public/js/modules/bi-spa.js:2700-2714` | `renderPDC()` | Modificar |
| `tests/test_pdc_v2_torre_control.php` | Agregado, coincidencia con el módulo, unidad de conteo, ausencia de proveedor | Crear |
| `tests/test_pdc_v2_torre_control_rbac.php` | Rol permitido y rol denegado | Crear |

---

### Task 1: `vencimientosAgregados()` — el agregado multi-obra

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php` (añadir método tras `vencimientos()`, que termina en la línea ~470)
- Test: `tests/test_pdc_v2_torre_control.php` (crear)

**Interfaces:**
- Consumes: `SeguimientoService::clasificarVencimiento(?string $fechaFin, string $hoy): array` — ya existe, estática y pura. Devuelve al menos la clave `estado`, con valores `vencido|sem1|sem2|sem3|sem6|adelante|sin_fecha`.
- Produces: `SeguimientoService::vencimientosAgregados(array $projectIds, ?string $hoy = null): array`, con esta forma exacta:

```php
[
  'hoy' => '2026-07-30',           // string Y-m-d, puesta por el servidor
  'por_obra' => [
    123 => [
      'project_id'   => 123,
      'conteos'      => ['vencido'=>2,'sem1'=>1,'sem2'=>0,'sem3'=>0,'sem6'=>3,'adelante'=>4,'sin_fecha'=>0],
      'destinos'     => 7,          // destinos distintos con al menos un paso pendiente
      'pasos'        => 10,         // total de pasos pendientes
    ],
  ],
  'totales' => ['vencido'=>2,'sem1'=>1,'sem2'=>0,'sem3'=>0,'sem6'=>3,'adelante'=>4,'sin_fecha'=>0],
]
```

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_torre_control.php`. Sigue el arnés de `tests/test_pdc_v2_subpaquetes.php`: `$assert` que imprime PASS/FAIL y acumula en `$failures`, y al final `exit(count($failures) === 0 ? 0 : 1)`.

```php
<?php
// tests/test_pdc_v2_torre_control.php — Fase B3 sobre MySQL real (proyectos 999950 y 999951).
//
// Prueba la condición de hecho del spec `2026-07-29-b3-torre-control-pdc-design.md`:
// el agregado multi-obra, que sus números coincidan con los de la pestaña del módulo para la
// misma obra y el mismo día, y que la unidad de conteo sea el destino (paquete + lote).
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m;
    fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$A = 999950;
$B = 999951;
$HOY = '2026-07-30';

$limpiar = static function () use ($db, $A, $B): void {
    foreach ([$A, $B] as $p) {
        $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$p]);
        $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$p]);
        $db->query('DELETE FROM pdc_subpaquete WHERE project_id = ?', [$p]);
    }
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-b3'");
};
$limpiar();

// Fixture: obra A con un paquete y dos pasos (uno vencido ayer, uno a 10 días);
// obra B con un paquete partido en dos lotes, un paso pendiente cada uno.
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, tipo, activo, creado_por) VALUES ('TEST B3 A','material',1,'test-b3')",
);
$paqA = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, tipo, activo, creado_por) VALUES ('TEST B3 B','material',1,'test-b3')",
);
$paqB = (int) $db->lastInsertId();

$db->query(
    'INSERT INTO pdc_plan_paquete (project_id, paquete_id, subpaquete_id, responsable_user_id) VALUES (?,?,0,NULL)',
    [$A, $paqA],
);
$db->query(
    'INSERT INTO pdc_plan_paso (project_id, paquete_id, subpaquete_id, paso_id, orden, paso, fecha_fin, fecha_real)
     VALUES (?,?,0,1,1,?,?,NULL), (?,?,0,2,2,?,?,NULL)',
    [$A, $paqA, 'Pliegos', '2026-07-29', $A, $paqA, 'Propuestas', '2026-08-09'],
);

foreach ([1, 2] as $lote) {
    $db->query(
        'INSERT INTO pdc_subpaquete (project_id, id, paquete_id, nombre, es_resto) VALUES (?,?,?,?,0)',
        [$B, $lote, $paqB, "Lote {$lote}"],
    );
    $db->query(
        'INSERT INTO pdc_plan_paquete (project_id, paquete_id, subpaquete_id, responsable_user_id) VALUES (?,?,?,NULL)',
        [$B, $paqB, $lote],
    );
    $db->query(
        'INSERT INTO pdc_plan_paso (project_id, paquete_id, subpaquete_id, paso_id, orden, paso, fecha_fin, fecha_real)
         VALUES (?,?,?,1,1,?,?,NULL)',
        [$B, $paqB, $lote, 'Pliegos', '2026-07-28'],
    );
}

$svc = new SeguimientoService($db);
$agg = $svc->vencimientosAgregados([$A, $B], $HOY);

$assert($agg['hoy'] === $HOY, 'el agregado devuelve la fecha de corte que se le pasó');
$assert(($agg['por_obra'][$A]['conteos']['vencido'] ?? -1) === 1, 'obra A: un paso vencido');
$assert(($agg['por_obra'][$A]['conteos']['sem2'] ?? -1) === 1, 'obra A: el paso a 10 días cae en sem2');
$assert(($agg['por_obra'][$A]['destinos'] ?? -1) === 1, 'obra A: un solo destino');

// Decisión 6: el paquete partido en dos lotes cuenta DOS destinos, no uno.
$assert(($agg['por_obra'][$B]['destinos'] ?? -1) === 2, 'obra B: el paquete partido cuenta dos destinos');
$assert(($agg['por_obra'][$B]['conteos']['vencido'] ?? -1) === 2, 'obra B: dos pasos vencidos, uno por lote');

$assert(($agg['totales']['vencido'] ?? -1) === 3, 'los totales suman las dos obras');

// Punto 3 de la condición de hecho: Torre y módulo coinciden para la misma obra el mismo día.
$modulo = $svc->vencimientos($A, [], $HOY);
$assert(
    $modulo['conteos'] === $agg['por_obra'][$A]['conteos'],
    'los conteos de la Torre coinciden exactamente con los de la pestaña del módulo',
);

$limpiar();
fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: FAIL — `Call to undefined method App\Services\Pdc\SeguimientoService::vencimientosAgregados()`

- [ ] **Step 3: Implementar el método**

En `src/Services/Pdc/SeguimientoService.php`, tras `vencimientos()`. La consulta **copia la forma de unión de `vencimientos()`** (por `paquete_id + subpaquete_id`), pero agrupa en vez de listar y acepta N proyectos:

```php
    /**
     * Agregado de vencimientos para VARIAS obras, para la Torre de Control (fase B3).
     *
     * Una sola consulta con IN (...), no N consultas: el número de obras autorizadas crece y
     * el panel de gerencia las pide todas de golpe.
     *
     * La clasificación NO se recalcula aquí: se delega en clasificarVencimiento(), la misma que
     * consumen la pestaña del módulo y el semáforo del plan. Dos definiciones de «vencido» en la
     * misma empresa es peor que no tener ninguna.
     *
     * @param int[] $projectIds
     * @return array{hoy:string,por_obra:array<int,array{project_id:int,conteos:array<string,int>,destinos:int,pasos:int}>,totales:array<string,int>}
     */
    public function vencimientosAgregados(array $projectIds, ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');

        $ids = array_values(array_unique(array_map('intval', $projectIds)));
        $vacio = ['vencido' => 0, 'sem1' => 0, 'sem2' => 0, 'sem3' => 0, 'sem6' => 0, 'adelante' => 0, 'sin_fecha' => 0];
        if ($ids === []) {
            return ['hoy' => $hoy, 'por_obra' => [], 'totales' => $vacio];
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        // La unión va por DESTINO (paquete + lote), igual que vencimientos(): unir solo por
        // paquete hace que un paso de un paquete partido en tres se cuente tres veces.
        $rows = $this->db->query(
            "SELECT ps.project_id, ps.paquete_id, ps.subpaquete_id, ps.fecha_fin
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
                                     AND pp.subpaquete_id = ps.subpaquete_id
             JOIN general_paquetes_contratacion p ON p.id = ps.paquete_id
             WHERE ps.project_id IN ({$ph}) AND ps.fecha_real IS NULL AND p.activo = 1",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $porObra = [];
        $totales = $vacio;
        $destinos = [];
        foreach ($rows as $r) {
            $pid = (int) $r['project_id'];
            if (!isset($porObra[$pid])) {
                $porObra[$pid] = ['project_id' => $pid, 'conteos' => $vacio, 'destinos' => 0, 'pasos' => 0];
                $destinos[$pid] = [];
            }

            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $estado = (string) self::clasificarVencimiento($fechaFin, $hoy)['estado'];

            $porObra[$pid]['conteos'][$estado]++;
            $porObra[$pid]['pasos']++;
            $totales[$estado]++;
            $destinos[$pid][$r['paquete_id'] . ':' . $r['subpaquete_id']] = true;
        }

        foreach ($destinos as $pid => $claves) {
            $porObra[$pid]['destinos'] = count($claves);
        }

        return ['hoy' => $hoy, 'por_obra' => $porObra, 'totales' => $totales];
    }
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: PASS en las siete aserciones, salida final `OK`, código de salida 0.

Si falla la última aserción (coincidencia con el módulo), **no ajustes el test**: significa que el agregado y `vencimientos()` no están clasificando igual, que es exactamente el bug que este test existe para atrapar.

- [ ] **Step 5: PHPStan**

Run: `docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G`
Expected: sin errores nuevos respecto a la línea base.

- [ ] **Step 6: Commit**

```bash
git add src/Services/Pdc/SeguimientoService.php tests/test_pdc_v2_torre_control.php
git commit -m "feat(pdc): el calculo de vencimientos aprende a mirar varias obras a la vez"
```

---

### Task 2: `fetchPdc()` deja de leer el PDC viejo

**Files:**
- Modify: `src/Services/ControlTowerService.php:516-522` (`fetchPdc`), `:339-357` (`fetchOverview`)
- Test: `tests/test_pdc_v2_torre_control.php` (ampliar)

**Interfaces:**
- Consumes: `SeguimientoService::vencimientosAgregados()` (Task 1) y `PaquetesService::resumen(int $projectId, ?int $versionId = null): ?array`, que ya devuelve las claves `cobertura` (float, % por conteo) y `coberturaValor` (float, % por valor).
- Produces: `fetchPdc()` devuelve **una fila por obra** con estas claves exactas, consumidas por las tareas 3 y 5:

```php
[
  'project_id'      => 123,
  'obra'            => 'Da Porto',
  'cobertura'       => 62.5,   // % por conteo
  'cobertura_valor' => 71.0,   // % por valor
  'vencidos'        => 2,
  'en_riesgo'       => 4,      // sem1 + sem2 + sem3
  'destinos'        => 7,
  'pasos'           => 10,
  'sin_mirar'       => 3,      // paquetes con cronograma desactualizado
  'hoy'             => '2026-07-30',
]
```

**Nota de alcance:** la cobertura se pide con un `resumen()` por obra, en bucle. Es N consultas, a diferencia de los vencimientos. Se acepta porque N es el número de obras autorizadas del usuario (unidades, no miles) y porque `resumen()` es el dueño del cálculo y no se va a duplicar. Si el punto 4 de la condición de hecho lo desmiente al medir con volumen real (Task 7), **eso es un hallazgo, no un fallo del plan**: anótalo y propón el agregado.

- [ ] **Step 1: Escribir el test que falla**

Añadir al final de `tests/test_pdc_v2_torre_control.php`, **antes** de `$limpiar()`:

```php
// --- El informe de la Torre ya no lee el PDC viejo ------------------------------------------------
$ct = new \App\Services\ControlTowerService($db);
$brief = $ct->getBrief('pdc', [$A, $B], '1', 'A');

$assert($brief['respuesta'] === 'BIEN', 'el brief responde BIEN');
$assert(count($brief['scorecard']) > 0, 'el scorecard trae indicadores');

$json = json_encode($brief, JSON_UNESCAPED_UNICODE);
$assert(stripos($json, 'subcontratoPaquete') === false, 'el brief ya no expone columnas del PDC viejo');
$assert(stripos($json, 'bi_pdc_general') === false, 'el lineage ya no apunta a la tabla del PDC viejo');

// Punto 5 de la condición de hecho: el proveedor no sale de la Torre.
foreach (['proveedor', 'subcontratista'] as $prohibido) {
    $assert(stripos($json, $prohibido) === false, "el brief no expone «{$prohibido}»");
}
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: FAIL en `el brief ya no expone columnas del PDC viejo` y en `el lineage ya no apunta...` — hoy `fetchPdc()` hace `SELECT * FROM bi_pdc_general`.

- [ ] **Step 3: Reemplazar `fetchPdc()`**

En `src/Services/ControlTowerService.php`, sustituir el cuerpo de `fetchPdc()` (líneas 516-522):

```php
    /**
     * Fase B3: el informe de compras se alimenta del PDC v2, no de bi_pdc_general (PDC viejo).
     *
     * El parámetro $semana se acepta y se IGNORA a propósito (Decisión 5 del spec): los
     * vencimientos se calculan contra hoy, con la fecha puesta por el servidor, para que este
     * panel y la pestaña del módulo no puedan discrepar el mismo día. El rótulo de la tarjeta
     * lo dice, para que no se lea como un fallo.
     */
    private function fetchPdc(array $projectIds, string $semana, array $filters): array
    {
        $seguimiento = new \App\Services\Pdc\SeguimientoService($this->db);
        $paquetes    = new \App\Services\Pdc\PaquetesService($this->db);

        $agg = $seguimiento->vencimientosAgregados($projectIds);
        $nombres = $this->nombresDeProyecto($projectIds);

        $filas = [];
        foreach ($projectIds as $pid) {
            $pid = (int) $pid;
            $obra = $agg['por_obra'][$pid] ?? ['conteos' => [], 'destinos' => 0, 'pasos' => 0];
            $c = $obra['conteos'];
            $resumen = $paquetes->resumen($pid) ?? [];

            $filas[] = [
                'project_id'      => $pid,
                'obra'            => $nombres[$pid] ?? ('Obra ' . $pid),
                'cobertura'       => (float) ($resumen['cobertura'] ?? 0.0),
                'cobertura_valor' => (float) ($resumen['coberturaValor'] ?? 0.0),
                'vencidos'        => (int) ($c['vencido'] ?? 0),
                'en_riesgo'       => (int) ($c['sem1'] ?? 0) + (int) ($c['sem2'] ?? 0) + (int) ($c['sem3'] ?? 0),
                'destinos'        => (int) $obra['destinos'],
                'pasos'           => (int) $obra['pasos'],
                'sin_mirar'       => count($seguimiento->paquetesDesactualizados($pid)),
                'hoy'             => $agg['hoy'],
            ];
        }

        return $filas;
    }

    /** @param int[] $projectIds @return array<int,string> */
    private function nombresDeProyecto(array $projectIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $projectIds)));
        if ($ids === []) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->queryAll("SELECT id, nombre FROM general_proyectos WHERE id IN ({$ph})", $ids);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = (string) $r['nombre'];
        }

        return $out;
    }
```

**Antes de dar el paso por bueno**, confirma el nombre real de la tabla y la columna de proyectos:

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; var_dump(Database::getInstance()->query("SHOW COLUMNS FROM general_proyectos")->fetchAll(PDO::FETCH_COLUMN));'
```

Si la tabla o la columna se llaman de otro modo, ajusta la consulta — **no inventes un nombre**.

- [ ] **Step 4: Arreglar `fetchOverview()`, que se rompe con el cambio**

`fetchOverview()` (línea ~341) sigue esperando la forma del PDC viejo: `listo_para_iniciar` y `pdc_items`. Con la fila nueva, `pdc_at_risk_count` daría siempre 0 en silencio, que es peor que un error. Sustituir esas dos líneas:

```php
            'pdc_at_risk_count' => array_sum(array_map(fn($r) => (int) ($r['vencidos'] ?? 0), $pdc)),
            'pdc_items' => $pdc,
```

- [ ] **Step 5: Correr el test y verificar que pasa**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: PASS en todas, incluidas las cuatro nuevas.

- [ ] **Step 6: Commit**

```bash
git add src/Services/ControlTowerService.php tests/test_pdc_v2_torre_control.php
git commit -m "feat(bi): el informe de compras de la Torre deja de leer el PDC viejo"
```

---

### Task 3: Scorecard, lineage y filtros

**Files:**
- Modify: `src/Services/ControlTowerService.php:651-660` (`scorecardPDC`), `:820-823` (lineage), `:101` (filtros)

**Interfaces:**
- Consumes: las filas de `fetchPdc()` (Task 2).
- Produces: el scorecard que `renderPDC()` pinta (Task 5). `kpi(string $name, float|int $value, string $unit, ?string $action): array` ya existe en la línea 3549.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_torre_control.php`, antes de `$limpiar()`:

```php
$nombresKpi = array_map(fn($k) => (string) ($k['kpi'] ?? $k['name'] ?? ''), $brief['scorecard']);
$hay = static fn(string $frag): bool => (bool) array_filter($nombresKpi, fn($n) => stripos($n, $frag) !== false);

$assert($hay('Cobertura'), 'el scorecard trae cobertura');
$assert($hay('valor'), 'la cobertura por valor aparece junto a la de conteo');
$assert($hay('Vencid'), 'el scorecard trae vencidos');
$assert($brief['lineage']['grain'] === 'project_id + paquete_id + subpaquete_id (destino), contra la fecha de hoy',
    'el lineage declara el grano por destino');
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: FAIL — hoy el scorecard dice «Paquetes PDC / No listos / Sin configurar» y el grano es `project_id + semana + consecutivo`.

- [ ] **Step 3: Reescribir `scorecardPDC()`**

```php
    private function scorecardPDC(array $data): array
    {
        $vencidos  = array_sum(array_map(fn($r) => (int) ($r['vencidos'] ?? 0), $data));
        $enRiesgo  = array_sum(array_map(fn($r) => (int) ($r['en_riesgo'] ?? 0), $data));
        $destinos  = array_sum(array_map(fn($r) => (int) ($r['destinos'] ?? 0), $data));
        $sinMirar  = array_sum(array_map(fn($r) => (int) ($r['sin_mirar'] ?? 0), $data));

        // Cobertura promedio ponderada por destinos: la media simple daría el mismo peso a una obra
        // de tres paquetes que a una de noventa.
        $cob = $destinos > 0
            ? array_sum(array_map(fn($r) => $this->number($r['cobertura'] ?? 0) * (int) ($r['destinos'] ?? 0), $data)) / $destinos
            : 0.0;
        $cobValor = $destinos > 0
            ? array_sum(array_map(fn($r) => $this->number($r['cobertura_valor'] ?? 0) * (int) ($r['destinos'] ?? 0), $data)) / $destinos
            : 0.0;

        return [
            // Los dos números de cobertura van SIEMPRE juntos: cada uno por separado cuenta media verdad.
            $this->kpi('Cobertura (conteo)', round($cob, 1), '%', null),
            $this->kpi('Cobertura (valor)', round($cobValor, 1), '%', null),
            $this->kpi('Vencidos', $vencidos, 'count', $vencidos > 0 ? 'Escalar' : null),
            $this->kpi('En riesgo (3 semanas)', $enRiesgo, 'count', $enRiesgo > 0 ? 'Revisar' : null),
            $this->kpi('Destinos con pasos abiertos', $destinos, 'count', null),
            // Un tablero vacío y un tablero ciego se ven igual. Esta cifra es la diferencia.
            $this->kpi('Paquetes sin mirar', $sinMirar, 'count', $sinMirar > 0 ? 'Actualizar cronograma' : null),
        ];
    }
```

- [ ] **Step 4: Actualizar el lineage**

Sustituir la entrada `'pdc'` (línea ~820):

```php
            'pdc' => [
                'source_relations' => ['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_subpaquete', 'general_paquetes_contratacion'],
                'grain' => 'project_id + paquete_id + subpaquete_id (destino), contra la fecha de hoy',
            ],
```

- [ ] **Step 5: Quitar el PDC viejo del catálogo de filtros**

En `getFilterOptions()` (línea 101), borrar esta línea, porque su columna es del PDC viejo y el proveedor no debe salir de la Torre:

```php
                ['bi_pdc_general', 'pdc', ['week' => 'semana'], 'subcontratoPaquete'],
```

- [ ] **Step 6: Correr el test y PHPStan**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: PASS en todas.

Run: `docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G`
Expected: sin errores nuevos.

- [ ] **Step 7: Commit**

```bash
git add src/Services/ControlTowerService.php tests/test_pdc_v2_torre_control.php
git commit -m "feat(bi): los indicadores de compras dicen cobertura, vencidos y lo que no estan mirando"
```

---

### Task 4: Aislamiento por obra, verificado con rol permitido y rol denegado

**Files:**
- Create: `tests/test_pdc_v2_torre_control_rbac.php`

**Interfaces:**
- Consumes: `App\Support\BiProjectScope::resolve($requestedRaw, array $session): array`, que lanza `DomainException` cuando se piden proyectos fuera de los autorizados (`src/Support/BiProjectScope.php:30`).

Este es el **punto 2 de la condición de hecho** y el que impide que esto sea un incidente. No se implementa nada nuevo: se **verifica** que la regla existente cubre el informe de compras.

- [ ] **Step 1: Escribir el test**

```php
<?php
// tests/test_pdc_v2_torre_control_rbac.php — Punto 2 de la condición de hecho del spec B3:
// ninguna obra ve datos de contratación de otra sin permiso.
//
// No prueba código nuevo: prueba que la regla que ya existe (BiProjectScope) cubre también el
// informe de compras. Es la comprobación que separa esto de un incidente.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Support\BiProjectScope;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m;
    fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$scope = new BiProjectScope($db);

// Toma un usuario real y sus proyectos autorizados, y una obra que NO le pertenece.
$fila = $db->query(
    'SELECT u.usuario, pm.project_id
     FROM general_usuarios u
     JOIN general_proyecto_miembros pm ON pm.usuario = u.usuario
     LIMIT 1',
)->fetch(\PDO::FETCH_ASSOC);

if (!$fila) {
    fwrite(STDERR, "SKIP: no hay usuarios con proyecto en esta base; siembra el fixture antes.\n");
    exit(1);
}

$usuario   = (string) $fila['usuario'];
$permitido = (int) $fila['project_id'];
$session   = ['usuario' => $usuario, 'project_id' => $permitido];

// Rol permitido: su propia obra se resuelve sin error.
$resuelto = $scope->resolve([$permitido], $session);
$assert($resuelto === [$permitido], 'rol permitido: el usuario resuelve su propia obra');

// Rol denegado: una obra que no es suya lanza.
$ajena = (int) $db->query(
    'SELECT id FROM general_proyectos WHERE id NOT IN
     (SELECT project_id FROM general_proyecto_miembros WHERE usuario = ?) LIMIT 1',
    [$usuario],
)->fetchColumn();

if ($ajena > 0) {
    $lanzo = false;
    try {
        $scope->resolve([$ajena], $session);
    } catch (\DomainException) {
        $lanzo = true;
    }
    $assert($lanzo, 'rol denegado: pedir una obra ajena lanza DomainException');

    // Y no se cuela mezclándola con una propia.
    $lanzoMixto = false;
    try {
        $scope->resolve([$permitido, $ajena], $session);
    } catch (\DomainException) {
        $lanzoMixto = true;
    }
    $assert($lanzoMixto, 'rol denegado: una obra ajena mezclada con una propia también lanza');
} else {
    fwrite(STDERR, "AVISO: no hay obra ajena a este usuario; la mitad denegada no se pudo probar.\n");
    $failures[] = 'no se pudo probar el caso denegado';
}

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correrlo**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control_rbac.php`
Expected: PASS en las tres aserciones.

Confirma antes los nombres reales de `general_proyecto_miembros` y su columna de usuario:

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; var_dump(Database::getInstance()->query("SHOW TABLES LIKE \"%miembro%\"")->fetchAll(PDO::FETCH_COLUMN));'
```

Ajusta la consulta al esquema real; **no cambies la aserción** para que pase.

- [ ] **Step 3: Registrar el test en la allowlist si hace falta**

`tests/browser` tiene allowlist en `.gitignore`. Comprueba que `tests/test_*.php` no la necesita:

```bash
git check-ignore -v tests/test_pdc_v2_torre_control_rbac.php || echo "no ignorado, se puede commitear"
```

- [ ] **Step 4: Commit**

```bash
git add tests/test_pdc_v2_torre_control_rbac.php
git commit -m "test(bi): ninguna obra ve las compras de otra, con rol permitido y denegado"
```

---

### Task 5: La pantalla

**Files:**
- Modify: `views/bi/control-tower.php:559-573`
- Modify: `public/js/modules/bi-spa.js:2700-2714`

**Interfaces:**
- Consumes: `data.scorecard` (Task 3) y `data.raw_row_count`. Las filas por obra llegan en el brief; el rótulo de fecha sale de la primera fila de `fetchPdc()` (`hoy`).

- [ ] **Step 1: Cambiar el encabezado de la tabla**

En `views/bi/control-tower.php`, sustituir el `<thead>` y el `<tbody>` de la sección `view-pdc` (líneas 568-569). **Reusa exactamente las mismas clases que las secciones vecinas** (`view-cic` en la línea 583) — no introduzcas clases ni colores nuevos:

```php
                <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Indicador</th><th class="p-2 text-left font-semibold text-gray-600">Valor</th><th class="p-2 text-left font-semibold text-gray-600">Acción</th></tr></thead>
                <tbody id="pdc-body"><tr><td class="p-4 text-center text-gray-400" colspan="3">Cargando datos de compras...</td></tr></tbody>
```

Y añade, justo después del `<h3>` (línea 564), los dos rótulos que el spec exige:

```php
            <p id="pdc-fecha-corte" class="text-xs text-gray-500"></p>
            <p class="text-xs text-gray-500">Un paquete partido en lotes cuenta un destino por lote: el total sube cuando una obra parte un paquete.</p>
```

- [ ] **Step 2: Reescribir `renderPDC()`**

En `public/js/modules/bi-spa.js`, sustituir la función completa (líneas 2700-2714):

```js
function renderPDC(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const body = document.getElementById('pdc-body');
  if (!body) return;

  // Decisión 5 del spec: este panel ignora el selector de semana y siempre responde «hoy».
  // El rótulo existe para que eso no se lea como un fallo. La fecha la pone el servidor.
  const fecha = document.getElementById('pdc-fecha-corte');
  const items = Array.isArray(data.pdc_items) ? data.pdc_items : [];
  if (fecha) {
    const hoy = items.length ? items[0].hoy : '';
    fecha.textContent = hoy ? `Al ${hoy} · no depende de la semana seleccionada` : '';
  }

  if (!rows.length) {
    body.innerHTML = '<tr><td class="p-4 text-center text-gray-400" colspan="3">Sin datos de compras.</td></tr>';
    return;
  }

  body.innerHTML = '';
  rows.forEach((row) => {
    const unidad = row.unit === '%' ? '%' : '';
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="p-2">${escapeHtml(row.kpi || '--')}</td><td class="p-2">${escapeHtml(String(row.value ?? '--'))}${unidad}</td><td class="p-2">${escapeHtml(row.action || '--')}</td>`;
    body.appendChild(tr);
  });
}
```

- [ ] **Step 3: Verificar que `pdc_items` llega al frontend**

`getBrief()` no incluye `pdc_items` en su retorno para el informe `pdc` (solo `fetchOverview` lo mete). Comprueba con el navegador integrado o con `curl` dentro del contenedor qué claves llegan realmente:

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; $b=(new App\Services\ControlTowerService(Database::getInstance()))->getBrief("pdc",[1],"1","A"); echo implode(", ", array_keys($b)), "\n";'
```

Si `pdc_items` no está, **no lo fuerces desde el JS**: añade la clave en `getBrief()` solo para `report_key === 'pdc'`, junto a `raw_row_count`, y déjalo comentado con el motivo (el rótulo de fecha necesita la fecha que puso el servidor).

- [ ] **Step 4: Validar en el navegador**

```bash
npm run check:frontend
```

Luego, con el stack arriba, abre `/bi/control-tower` en el navegador integrado a **1180×820 en dark**, entra a la pestaña «Plan de Compras» y comprueba:

1. Los seis indicadores se pintan y ninguno dice `NaN` ni `--` cuando hay datos.
2. El rótulo de fecha aparece con la fecha del servidor.
3. Consola sin errores.
4. Sin scroll horizontal a 1180px.

Guarda una captura en `goals/pdc-preparar-b1/evidence/`.

- [ ] **Step 5: Commit**

```bash
git add views/bi/control-tower.php public/js/modules/bi-spa.js
git commit -m "feat(bi): la pestaña de compras de la Torre muestra los seis indicadores y su fecha de corte"
```

---

### Task 6: El drill-down al paquete

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php` (método nuevo)
- Modify: `src/Controllers/Api/BiControlTowerApiController.php` (tras `pdc()`, línea 207)
- Modify: `public/index.php` (tras la línea 368)

**Interfaces:**
- Produces: `GET /api/bi/report/pdc/detail?project_id=N` → `{ "respuesta":"BIEN", "hoy":"...", "paquetes":[{ "paquete":"...","lote":"...","paso":"...","fecha_fin":"...","estado":"vencido","responsable":"...","valor":0.0 }] }`

**Sin nombre de proveedor en ningún campo** (Decisión 3).

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_torre_control.php`, antes de `$limpiar()`:

```php
$detalle = $svc->detalleDestinos([$A], $HOY);
$assert(count($detalle) === 2, 'el detalle de la obra A trae sus dos pasos pendientes');
$assert(isset($detalle[0]['paquete'], $detalle[0]['estado']), 'cada fila del detalle trae paquete y estado');
$assert(!array_key_exists('proveedor', $detalle[0]), 'el detalle NO trae proveedor');
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: FAIL — `Call to undefined method ...::detalleDestinos()`

- [ ] **Step 3: Implementar `detalleDestinos()`**

En `SeguimientoService`, junto a `vencimientosAgregados()`. Reusa la consulta de `vencimientos()` ampliada a N proyectos, **sin ninguna columna de proveedor**:

```php
    /**
     * Detalle del drill-down de la Torre de Control: un renglón por paso pendiente.
     *
     * No selecciona proveedor a propósito (Decisión 3 del spec): ese dato no sale del módulo.
     *
     * @param int[] $projectIds
     * @return list<array{project_id:int,paquete:string,lote:?string,paso:string,fecha_fin:?string,estado:string,responsable:?string}>
     */
    public function detalleDestinos(array $projectIds, ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');
        $ids = array_values(array_unique(array_map('intval', $projectIds)));
        if ($ids === []) {
            return [];
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->query(
            "SELECT ps.project_id, p.nombre AS paquete, s.nombre AS lote, ps.paso, ps.fecha_fin,
                    u.nombre AS responsable
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
                                     AND pp.subpaquete_id = ps.subpaquete_id
             JOIN general_paquetes_contratacion p ON p.id = ps.paquete_id
             LEFT JOIN pdc_subpaquete s ON s.project_id = ps.project_id AND s.id = ps.subpaquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             WHERE ps.project_id IN ({$ph}) AND ps.fecha_real IS NULL AND p.activo = 1
             ORDER BY ps.fecha_fin IS NULL, ps.fecha_fin ASC, p.nombre ASC, ps.orden ASC",
            $ids,
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $out[] = [
                'project_id'  => (int) $r['project_id'],
                'paquete'     => (string) $r['paquete'],
                'lote'        => $r['lote'] === null ? null : (string) $r['lote'],
                'paso'        => (string) $r['paso'],
                'fecha_fin'   => $fechaFin,
                'estado'      => (string) self::clasificarVencimiento($fechaFin, $hoy)['estado'],
                'responsable' => $r['responsable'] === null ? null : (string) $r['responsable'],
            ];
        }

        return $out;
    }
```

- [ ] **Step 4: Añadir el endpoint**

En `BiControlTowerApiController`, tras `pdc()`. **Los proyectos se resuelven con `resolveProjectIds()`**, que ya pasa por `BiProjectScope`: no aceptes un `project_id` crudo del cliente.

```php
    public function pdcDetail(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();

        $seguimiento = new \App\Services\Pdc\SeguimientoService(\Database::getInstance());
        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'respuesta' => 'BIEN',
            'hoy'       => $hoy,
            'paquetes'  => $seguimiento->detalleDestinos($projectIds, $hoy),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
```

En `public/index.php`, tras la línea 368:

```php
$router->get('/api/bi/report/pdc/detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'pdcDetail']);
```

- [ ] **Step 5: Correr los tests y PHPStan**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: PASS en todas.

Run: `docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G`
Expected: sin errores nuevos.

- [ ] **Step 6: Commit**

```bash
git add src/Services/Pdc/SeguimientoService.php src/Controllers/Api/BiControlTowerApiController.php public/index.php tests/test_pdc_v2_torre_control.php
git commit -m "feat(bi): el drill-down de compras abre el paquete, y el proveedor no sale de ahi"
```

---

### Task 7: Los otros dos indicadores — avance por paso y carga por responsable

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php` (ampliar `vencimientosAgregados()`)
- Modify: `src/Services/ControlTowerService.php` (`fetchPdc()`, `scorecardPDC()`)
- Test: `tests/test_pdc_v2_torre_control.php` (ampliar)

Son dos de los cuatro indicadores que pidió el comité. Los datos crudos ya los trae la consulta de la Task 1; falta agruparlos.

**Interfaces:**
- Produces: `vencimientosAgregados()` gana dos claves de primer nivel, consumidas por `fetchPdc()`:

```php
  'por_paso'        => ['Pliegos' => ['pendientes' => 7, 'vencidos' => 2], ...],
  'por_responsable' => [ 12 => ['nombre' => 'Ana', 'pendientes' => 5, 'vencidos' => 1], 0 => ['nombre' => 'Sin responsable', ...] ],
```

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_torre_control.php`, antes de `$limpiar()`:

```php
$assert(($agg['por_paso']['Pliegos']['pendientes'] ?? -1) === 3, 'avance por paso: tres pasos «Pliegos» pendientes entre las dos obras');
$assert(($agg['por_paso']['Pliegos']['vencidos'] ?? -1) === 3, 'avance por paso: los tres están vencidos');
$assert(($agg['por_paso']['Propuestas']['vencidos'] ?? -1) === 0, 'avance por paso: «Propuestas» no está vencido');
$assert(isset($agg['por_responsable'][0]), 'carga por responsable: los pasos sin responsable se agrupan aparte');
$assert(($agg['por_responsable'][0]['pendientes'] ?? -1) === 4, 'carga por responsable: los cuatro pasos del fixture no tienen responsable');
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_pdc_v2_torre_control.php`
Expected: FAIL en las cinco — `vencimientosAgregados()` todavía no devuelve esas claves.

- [ ] **Step 3: Ampliar la consulta y el agregado**

En `vencimientosAgregados()`, añadir `ps.paso`, `pp.responsable_user_id` y el nombre del responsable al `SELECT` (la unión con `general_usuarios` ya existe en `vencimientos()`; cópiala igual):

```php
                    ps.paso, pp.responsable_user_id, u.nombre AS responsable_nombre
```

```php
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
```

Inicializar antes del bucle y acumular dentro:

```php
        $porPaso = [];
        $porResponsable = [];
```

```php
            $paso = (string) $r['paso'];
            if (!isset($porPaso[$paso])) {
                $porPaso[$paso] = ['pendientes' => 0, 'vencidos' => 0];
            }
            $porPaso[$paso]['pendientes']++;
            if ($estado === 'vencido') {
                $porPaso[$paso]['vencidos']++;
            }

            // El 0 agrupa lo que no tiene dueño. Repartirlo o esconderlo haría que «quién está
            // sobrecargado» ignorara justo el trabajo que nadie ha reclamado.
            $rid = $r['responsable_user_id'] === null ? 0 : (int) $r['responsable_user_id'];
            if (!isset($porResponsable[$rid])) {
                $porResponsable[$rid] = [
                    'nombre' => $rid === 0 ? 'Sin responsable' : (string) ($r['responsable_nombre'] ?? ('Usuario ' . $rid)),
                    'pendientes' => 0,
                    'vencidos' => 0,
                ];
            }
            $porResponsable[$rid]['pendientes']++;
            if ($estado === 'vencido') {
                $porResponsable[$rid]['vencidos']++;
            }
```

Y añadirlas al retorno, junto a `totales`. Actualiza también el `@return` del docblock.

- [ ] **Step 4: Exponerlas en el informe**

En `fetchPdc()`, la fila por obra no cambia; añade **una clave más al brief** para que la pantalla las pueda pintar. En `getBrief()`, junto a `raw_row_count`, solo para `pdc`:

```php
            'pdc_breakdown'         => $reportKey === 'pdc' ? $this->pdcBreakdown($projectIds) : null,
```

```php
    /** Avance por paso y carga por responsable, para el panel de compras (fase B3). */
    private function pdcBreakdown(array $projectIds): array
    {
        $agg = (new \App\Services\Pdc\SeguimientoService($this->db))->vencimientosAgregados($projectIds);

        return ['por_paso' => $agg['por_paso'], 'por_responsable' => $agg['por_responsable']];
    }
```

Y en `scorecardPDC()`, añadir el KPI que responde «quién está sobrecargado» — el responsable con más pasos vencidos:

```php
        // La pregunta de gerencia no es cuántos vencidos hay, sino de quién son.
        $this->kpi('Responsables con vencidos', $conVencidos, 'count', $conVencidos > 0 ? 'Redistribuir' : null),
```

calculado antes del `return` a partir de `$this->pdcBreakdown()`; si prefieres no repetir la consulta, pasa el breakdown a `scorecardPDC()` como segundo parámetro y actualiza su llamada en `composeScorecard()`.

- [ ] **Step 5: Pintar las dos tablas**

En `views/bi/control-tower.php`, dentro de `view-pdc`, añade dos tablas más con la misma estructura y clases que la existente: `pdc-paso-body` (Paso · Pendientes · Vencidos) y `pdc-resp-body` (Responsable · Pendientes · Vencidos). En `renderPDC()`, recórrelas desde `data.pdc_breakdown`, con el mismo `escapeHtml` y el mismo mensaje de vacío.

- [ ] **Step 6: Correr tests, PHPStan y frontend**

```bash
docker compose exec app php tests/test_pdc_v2_torre_control.php
docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G
npm run check:frontend
```

Expected: todo en verde.

- [ ] **Step 7: Commit**

```bash
git add src/Services/Pdc/SeguimientoService.php src/Services/ControlTowerService.php views/bi/control-tower.php public/js/modules/bi-spa.js tests/test_pdc_v2_torre_control.php
git commit -m "feat(bi): la Torre dice por que paso va cada compra y quien esta sobrecargado"
```

---

### Task 8: Volumen real — el punto que más fácil se da por bueno

**Files:**
- Create: `goals/pdc-preparar-b1/evidence/b3-volumen.md`

El punto 4 de la condición de hecho. La Ola 1 declaró que se validó con **4 paquetes y 21 pasos**, no con los 96 previstos: la regla está probada, **no estresada**.

- [ ] **Step 1: Medir el volumen real disponible**

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; $d=Database::getInstance(); var_dump($d->query("SELECT project_id, COUNT(*) pasos FROM pdc_plan_paso GROUP BY project_id ORDER BY pasos DESC")->fetchAll(PDO::FETCH_ASSOC));'
```

- [ ] **Step 2: Cronometrar el brief con todas las obras con datos**

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; $d=Database::getInstance(); $ids=$d->query("SELECT DISTINCT project_id FROM pdc_plan_paso")->fetchAll(PDO::FETCH_COLUMN); $t=microtime(true); $b=(new App\Services\ControlTowerService($d))->getBrief("pdc",$ids,"1","A"); printf("%d obras, %d filas, %.3f s\n", count($ids), $b["raw_row_count"], microtime(true)-$t);'
```

- [ ] **Step 3: Escribir la medición, diga lo que diga**

Crea `goals/pdc-preparar-b1/evidence/b3-volumen.md` con: número de obras, pasos por obra, tiempo del brief y **cuántas consultas hace la cobertura** (una por obra, según la nota de la Task 2).

**Si el volumen disponible sigue siendo pequeño, escríbelo tal cual.** «Medido con 4 paquetes» es un resultado honesto; «los indicadores cargan bien» sin cifra al lado no lo es. Si el tiempo se dispara por el bucle de `resumen()`, anótalo como hallazgo y propón el agregado — no lo implementes sin gate.

- [ ] **Step 4: Commit**

```bash
git add goals/pdc-preparar-b1/evidence/b3-volumen.md
git commit -m "docs(pdc): la medicion de volumen de B3, con su limite declarado"
```

---

### Task 9: Regresión y cierre

- [ ] **Step 1: Los tests que toca este cambio**

```bash
docker compose exec app php tests/test_pdc_v2_torre_control.php
docker compose exec app php tests/test_pdc_v2_torre_control_rbac.php
docker compose exec app php tests/test_pdc_v2_subpaquetes.php
docker compose exec app php tests/test_global_table_safety.php
```

Expected: los cuatro en verde. Si alguno ya estaba rojo antes de tu cambio, **compruébalo contra `origin/main` antes de atribuírtelo** — hay rojos preexistentes conocidos en la suite.

- [ ] **Step 2: PHPStan y frontend**

```bash
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npm run check:frontend
```

- [ ] **Step 3: e2e del PDC**

```bash
npx playwright test e2e --grep pdc-v2 --workers=1
```

Expected: sin regresiones. Los que fallen por entorno, decláralos.

- [ ] **Step 4: Marcar la fila 9 en el tablero de relevos**

Solo si los puntos 1-6 de la condición de hecho están cumplidos **con salida real de comandos**. En `goals/pdc-preparar-b1/estado-olas.md`, fila 9: `HECHO`, el sha y la fecha. Si algo quedó a medias, `PARADA` con el motivo en una línea.

- [ ] **Step 5: Commit final**

```bash
git add goals/pdc-preparar-b1/estado-olas.md
git commit -m "docs(pdc): la fila 9 queda cerrada con su verificacion"
```

**No hagas push.** Lo hace quien lleva el ciclo de integración.

---

## Self-Review

**Cobertura del spec:**

| Requisito del spec | Tarea |
|---|---|
| Decisión 1 — el dato vive en la app | Tasks 2, 3 (nada sale a Power BI) |
| Decisión 2 — solo tus obras autorizadas | Task 4 |
| Decisión 3 — agregado con drill-down, sin proveedor | Tasks 3, 6 |
| Decisión 4 — sustituir el informe viejo | Task 2 (+ `fetchOverview`) |
| Decisión 5 — semana ignorada, rotulada | Tasks 2, 5 |
| Decisión 6 — unidad de conteo por destino | Task 1 (test explícito), Task 6 |
| Cobertura por valor y conteo | Tasks 2, 3 |
| Vencidos y en riesgo | Tasks 1, 3 |
| Avance por paso | Task 7 |
| Carga por responsable | Task 7 |
| «Cuántos paquetes no está mirando» | Tasks 2, 3 (`paquetesDesactualizados()`) |
| Condición de hecho 1-6 | Tasks 4, 1 (coincidencia), 8 (volumen), 2 (proveedor), 9 (regresión) |

La primera pasada de esta revisión dejó «avance por paso» y «carga por responsable» sin tarea —dos de los cuatro indicadores que pidió el comité—. Se corrigió añadiendo la **Task 7**, que los agrupa ampliando `vencimientosAgregados()` en vez de abrir una consulta nueva.

**Escaneo de placeholders:** sin TBD ni «implementar después». Los tres puntos donde el plan no puede saber el esquema real (nombre de la tabla de proyectos, la de miembros, y si `pdc_items` llega al brief) llevan un comando para averiguarlo y la instrucción explícita de **no inventar el nombre ni ablandar la aserción**.

**Consistencia de tipos:** `vencimientosAgregados()` y `detalleDestinos()` se llaman igual en todas las tareas; las claves de la fila de `fetchPdc()` (`cobertura`, `cobertura_valor`, `vencidos`, `en_riesgo`, `destinos`, `pasos`, `sin_mirar`, `hoy`) son las mismas en las Tasks 2, 3 y 5; `kpi()` se usa con la firma real de la línea 3549.
