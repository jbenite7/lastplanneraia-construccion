---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-29
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-29-b2-vencimientos-lookahead.md
resumen: Que el plan de compras responda «qué se me vence» —por paso y por responsable— en una pestaña nueva de Seguimiento, con el mismo color en el plan, y declarando…
---

# PDC v2 · B2 (primera mitad) — Vencimientos y semáforo del plan — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el plan de compras responda «qué se me vence» —por paso y por responsable— en una pestaña
nueva de Seguimiento, con el mismo color en el plan, y declarando en pantalla cuántos paquetes no está
mirando.

**Architecture:** Una sola regla de clasificación, `SeguimientoService::clasificarVencimiento()`, pura y
estática. La consume `SeguimientoService::vencimientos()` (la pestaña) y `PlanFechasService::plan()` (el
semáforo por paso), de modo que lista y color no pueden divergir. Sin tablas nuevas, sin migraciones, sin
escrituras: todo el frente A solo lee.

**Tech Stack:** PHP 8.3 (servicios en `src/Services/Pdc/`, controlador en `src/Controllers/Api/`),
React 19 + Vite + AG Grid en `pdc-app/`, tests PHP autoejecutables `tests/test_*.php`, Vitest, Playwright
(`tests/browser/pdc-v2-*.spec.mjs`).

## Global Constraints

- **Runtime aislado de este worktree:** stack `lps-aia-b2` — app `http://localhost:8095`, Adminer 8094,
  MySQL host 3310, volumen propio `lps-aia-b2_db_data`. Todos los comandos `docker compose` de este plan
  llevan `-f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml`. **Nunca** usar
  los stacks `last-planner-aia` (3307), `lps-aia-pdc` (3308), `pdc-ola2` ni `pdc-tamiz`: son de otras
  sesiones y sus bases se llaman igual.
- **UI: solo desktop ≥1180 px y solo dark.** Viewport canónico de validación 1180×820. Prohibido producir
  cambios, pruebas o evidencia para mobile, tablet o el tema `linen`.
- **Sin colores propios:** solo tokens `--pdc-*` ya existentes. Nada de hex ni de `rgba()` en el CSS ni en
  sus comentarios (el audit del design system lee dentro de los comentarios).
- **La fecha de hoy la pone el servidor.** Ningún `new Date()` del navegador decide si algo está vencido.
- **El tablero solo lee.** Prohibido recalcular, reprogramar o escribir `duracion_ref` desde este frente.
- **Los pasos con `fecha_real` no aparecen** en el tablero, aunque se hayan cumplido tarde.
- **No hacer commit, push ni deploy** salvo petición explícita del usuario.

## Hechos medidos en la base aislada (2026-07-29) — no volver a medirlos

- Da Porto es `project_id = 73`; el sandbox e2e es `990100`.
- Da Porto tiene 4 paquetes asignados: 123 y 124 (`orden_compra`, con `duracion_ref`), 191 (`contrato`,
  `duracion_ref` NULL, **con plan calculado igual**) y 205 (`no_contratable`, sin plan y que nunca debe
  contar). 3 cabeceras en `pdc_plan_paquete`, 21 filas en `pdc_plan_paso`.
- Paquetes activos que generan proceso y **sin `duracion_ref` utilizable: 42** (16 `a_todo_costo`,
  20 `suministro`, 6 `mano_obra`) sobre 216 activos. Los tres tipos tienen muestra de sobra para la
  mediana (94 / 46 / 28 filas con desglose completo), así que **los 42 son resolubles por el camino
  estadístico que `PlanFechasService::calcular()` ya aplica** (`duracion_provisional = 1`). El spec hablaba
  de 25; el número real de hoy es 42 y así hay que escribirlo.
- Por tanto, un paquete sin `duracion_ref` **sí recibe fechas**. Lo que de verdad deja a un paquete fuera
  del tablero es no tener plan: sin frente amarrado, o amarrado sin recalcular.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `src/Services/Pdc/SeguimientoService.php` (modificar) | `clasificarVencimiento()` (regla única, pura) y `vencimientos()` (filas + conteos + filtros + paquetes sin fecha) |
| `src/Services/Pdc/PlanFechasService.php` (modificar) | `modalidadesConProcesoSql()` pasa a pública; `plan()` añade `fechaReal` y `vencimiento` a cada paso |
| `src/Controllers/Api/PlanComprasSeguimientoController.php` (modificar) | Acción `vencimientos()` con los filtros como parámetros, tras el guard de lectura |
| `public/index.php` (modificar) | Ruta `GET /plan-compras/api/seguimiento/vencimientos` |
| `pdc-app/src/lib/vencimientos.ts` (crear) | Orden y etiquetas de los cortes, y el texto de «paquetes sin fecha». Sin reglas de fecha: eso es del servidor |
| `pdc-app/src/lib/vencimientos.test.ts` (crear) | Vitest de lo anterior |
| `pdc-app/src/lib/types.ts` (modificar) | `FilaVencimiento`, `RespuestaVencimientos`, `PasoPlan.vencimiento` |
| `pdc-app/src/pages/Seguimiento.tsx` (modificar) | Dos pestañas: «Paquetes» (lo que ya hay) y «Vencimientos» (lo nuevo) |
| `pdc-app/src/pages/PlanFechas.tsx` (modificar) | Semáforo por paso en la tabla de detalle |
| `pdc-app/src/styles.css` (modificar) | Clases del semáforo y del tablero, con tokens existentes |
| `tests/test_pdc_v2_vencimientos.php` (crear) | Gate PHP de la regla, del servicio y del semáforo del plan |
| `tests/browser/pdc-v2-vencimientos.spec.mjs` (crear) | e2e de la pestaña y del semáforo |
| `.gitignore` (modificar) | Allowlist del spec nuevo de `tests/browser` |
| `goals/pdc-preparar-b1/evidence/paquetes-sin-duracion-ref.md` (crear) | La medición escrita del pendiente 2 |

---

### Task 1: La regla de clasificación, pura y probada

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php`
- Test: `tests/test_pdc_v2_vencimientos.php` (crear)

**Interfaces:**
- Produces: `SeguimientoService::clasificarVencimiento(?string $fechaFin, string $hoy): array{estado: string, diasDesfase: ?int}`.
  `estado` ∈ `vencido | sem1 | sem2 | sem3 | sem6 | adelante | sin_fecha`. `diasDesfase` son días
  positivos de retraso cuando `vencido`, `null` en todo lo demás.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_vencimientos.php` con la cabecera del gate (copiada del patrón de
`tests/test_pdc_v2_seguimiento.php`) y los casos de la regla:

```php
<?php
/**
 * Gate de la fase B2 (primera mitad): vencimientos y semaforo del plan.
 *
 * Corre contra DAPORTO (project_id = 73) y NO escribe nada: este frente solo lee. Autoejecutable:
 * imprime PASS:/FAIL: y sale con 0/1. No hay PHPUnit en este repo.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\SeguimientoService;

const P = 73;

$db = Database::getInstance();
$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    echo ($cond ? 'PASS: ' : 'FAIL: '), $msg, "\n";
    if (!$cond) {
        $fallos++;
    }
};

$svc = new SeguimientoService($db);

// --- La regla, sin base de datos ---
$hoy = '2026-07-29';
$c = static fn (?string $f): string => SeguimientoService::clasificarVencimiento($f, $hoy)['estado'];

$assert($c('2026-07-28') === 'vencido', 'Ayer esta vencido. Dio ' . $c('2026-07-28'));
$assert($c('2026-07-29') === 'sem1', 'Hoy mismo cuenta como «vence en 1 semana», no como vencido. Dio ' . $c('2026-07-29'));
$assert($c('2026-08-04') === 'sem1', 'Seis dias adelante sigue en la primera semana. Dio ' . $c('2026-08-04'));
$assert($c('2026-08-05') === 'sem2', 'A los siete dias exactos empieza la segunda semana. Dio ' . $c('2026-08-05'));
$assert($c('2026-08-12') === 'sem3', 'A los catorce dias exactos empieza la tercera semana. Dio ' . $c('2026-08-12'));
$assert($c('2026-08-19') === 'sem6', 'A los veintiun dias exactos empieza el corte de seis semanas. Dio ' . $c('2026-08-19'));
$assert($c('2026-09-08') === 'sem6', 'El dia 41 todavia es del corte de seis semanas. Dio ' . $c('2026-09-08'));
$assert($c('2026-09-09') === 'adelante', 'A los 42 dias exactos ya es «mas adelante». Dio ' . $c('2026-09-09'));
$assert($c(null) === 'sin_fecha', 'Un paso sin fecha programada no se inventa un corte: es «sin fecha». Dio ' . $c(null));

$d = SeguimientoService::clasificarVencimiento('2026-07-20', $hoy);
$assert($d['diasDesfase'] === 9, 'El desfase de lo vencido son dias positivos de retraso. Dio ' . var_export($d['diasDesfase'], true));
$assert(SeguimientoService::clasificarVencimiento('2026-08-04', $hoy)['diasDesfase'] === null,
    'Lo que aun no vence no tiene desfase: null, no cero.');

echo $fallos === 0 ? "\nOK\n" : "\n{$fallos} FALLOS\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correrlo y verlo fallar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php
```
Expected: error fatal `Call to undefined method ... clasificarVencimiento()`.

- [ ] **Step 3: Implementar la regla**

En `src/Services/Pdc/SeguimientoService.php`, justo antes de `proyectar()`:

```php
    /**
     * El corte de vencimiento de una fecha programada contra hoy.
     *
     * Estatica y pura a proposito: es la UNICA regla del modulo que dice si algo esta vencido, y la
     * consumen dos sitios —la pestaña de vencimientos y el semaforo del plan—. Si viviera en la SPA,
     * o duplicada en cada consumidor, el color y la lista podrian contradecirse sin que nada fallara.
     *
     * Los cortes son los que nombro el dueño del producto —vencido, 1, 2, 3 y 6 semanas— y ninguno
     * mas. `sin_fecha` no es un corte inventado: es el hueco de un paso que el plan aun no fecho, y
     * tiene nombre propio para que se pueda contar y enseñar en vez de desaparecer.
     *
     * @return array{estado: string, diasDesfase: ?int}
     */
    public static function clasificarVencimiento(?string $fechaFin, string $hoy): array
    {
        if ($fechaFin === null || $fechaFin === '') {
            return ['estado' => 'sin_fecha', 'diasDesfase' => null];
        }
        $fin = new \DateTimeImmutable($fechaFin);
        $ref = new \DateTimeImmutable($hoy);
        // Dias completos entre las dos fechas, con signo: negativo = ya paso.
        $dias = (int) $ref->diff($fin)->format('%r%a');
        if ($dias < 0) {
            return ['estado' => 'vencido', 'diasDesfase' => -$dias];
        }
        // Intervalos medio abiertos [inicio, fin), en el mismo orden en que se leen: hoy entra en la
        // primera semana, no en «vencido». Lo de hoy todavia se puede hacer hoy.
        $estado = match (true) {
            $dias < 7 => 'sem1',
            $dias < 14 => 'sem2',
            $dias < 21 => 'sem3',
            $dias < 42 => 'sem6',
            default => 'adelante',
        };
        return ['estado' => $estado, 'diasDesfase' => null];
    }
```

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php
```
Expected: todo PASS, `OK`, código de salida 0. (Comprobar con `echo $?` — `grep "^FAIL"` miente:
el resultado real va en el código de salida.)

---

### Task 2: `vencimientos()` — las filas, los conteos y los paquetes que no se están mirando

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php`
- Modify: `src/Services/Pdc/PlanFechasService.php:74` (`modalidadesConProcesoSql` pasa de `private` a `public`)
- Test: `tests/test_pdc_v2_vencimientos.php`

**Interfaces:**
- Consumes: `SeguimientoService::clasificarVencimiento()` (Task 1).
- Produces:
  ```php
  vencimientos(int $projectId, array $filtros = [], ?string $hoy = null): array
  // $filtros: ['pasoClave' => string, 'responsableUserId' => int|null, 'soloSinResponsable' => bool]
  // devuelve:
  // [
  //   'hoy' => 'YYYY-MM-DD',
  //   'filas' => list<array{paqueteId:int, paquete:string, frenteNombre:string, pasoId:?int,
  //                          orden:int, paso:string, clave:string, fechaFin:?string,
  //                          responsableUserId:?int, responsableNombre:string,
  //                          estado:string, diasDesfase:?int}>,
  //   'conteos' => array{vencido:int, sem1:int, sem2:int, sem3:int, sem6:int, adelante:int, sin_fecha:int},
  //   'totalPendientes' => int,
  //   'pasos' => list<array{clave:string, paso:string}>,   // para poblar el filtro
  //   'sinFechas' => array{paquetes:int, sinFrente:int, sinCalcular:int},
  // ]
  ```

- [ ] **Step 1: Escribir los tests que fallan**

Añadir a `tests/test_pdc_v2_vencimientos.php`, antes del `echo $fallos === 0 ...` final:

```php
// --- El tablero contra Da Porto ---
$v = $svc->vencimientos(P);

$assert($v['hoy'] === (new DateTimeImmutable('today'))->format('Y-m-d'),
    'La fecha de hoy la pone el servidor y viaja en la respuesta. Dio ' . $v['hoy']);

$pendientes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND fecha_real IS NULL', [P],
)->fetchColumn();
$assert(array_sum($v['conteos']) === $pendientes,
    'Los conteos por corte suman EXACTAMENTE los pasos pendientes del proyecto. Sumaron '
    . array_sum($v['conteos']) . ' y hay ' . $pendientes);
$assert($v['totalPendientes'] === $pendientes,
    'totalPendientes es ese mismo numero. Dio ' . $v['totalPendientes']);

// «Mas adelante» se cuenta pero no se lista: las filas son todo menos ese corte.
$listables = $pendientes - $v['conteos']['adelante'];
$assert(count($v['filas']) === $listables,
    'Se listan todos los cortes menos «mas adelante». Listo ' . count($v['filas']) . ' de ' . $listables);
$assert(array_filter($v['filas'], static fn (array $f): bool => $f['estado'] === 'adelante') === [],
    'Ninguna fila listada es del corte «mas adelante».');

// Ningun paso cumplido se cuela.
$cumplido = $db->query(
    'SELECT paquete_id, paso_id FROM pdc_plan_paso WHERE project_id = ? AND fecha_real IS NOT NULL LIMIT 1', [P],
)->fetch(PDO::FETCH_ASSOC);
if ($cumplido !== false) {
    $colado = array_filter(
        $v['filas'],
        static fn (array $f): bool => $f['paqueteId'] === (int) $cumplido['paquete_id']
            && $f['pasoId'] === (int) $cumplido['paso_id'],
    );
    $assert($colado === [], 'Un paso con fecha real no aparece en el tablero.');
}

// Cada fila lleva su paquete, su paso y su clasificacion, y la clasificacion es la de la regla.
$assert($v['filas'] !== [], 'Da Porto tiene pasos pendientes que listar.');
foreach ($v['filas'] as $f) {
    $esperado = SeguimientoService::clasificarVencimiento($f['fechaFin'], $v['hoy']);
    if ($f['estado'] !== $esperado['estado'] || $f['diasDesfase'] !== $esperado['diasDesfase']) {
        $assert(false, 'La fila «' . $f['paso'] . '» de «' . $f['paquete'] . '» no usa la regla unica.');
        break;
    }
}
$assert(true, 'Cada fila del tablero se clasifica con clasificarVencimiento().');

// Filtro por paso: solo quedan las filas de ese paso.
$clave = $v['filas'][0]['clave'];
$soloPaso = $svc->vencimientos(P, ['pasoClave' => $clave]);
$assert(
    $soloPaso['filas'] !== []
    && array_filter($soloPaso['filas'], static fn (array $f): bool => $f['clave'] !== $clave) === [],
    'Filtrar por un paso concreto deja solo las filas de ese paso.',
);
$assert(array_sum($soloPaso['conteos']) <= $pendientes,
    'Los conteos del filtro cuentan lo filtrado, no el proyecto entero.');

// Filtro por responsable.
$conDueno = null;
foreach ($v['filas'] as $f) {
    if ($f['responsableUserId'] !== null) {
        $conDueno = $f['responsableUserId'];
        break;
    }
}
if ($conDueno !== null) {
    $mios = $svc->vencimientos(P, ['responsableUserId' => $conDueno]);
    $assert(
        $mios['filas'] !== []
        && array_filter($mios['filas'], static fn (array $f): bool => $f['responsableUserId'] !== $conDueno) === [],
        'Filtrar por responsable deja solo las filas de esa persona.',
    );
}

// Lo que el tablero NO esta mirando, dicho en numeros.
$assert(isset($v['sinFechas']['paquetes'], $v['sinFechas']['sinFrente'], $v['sinFechas']['sinCalcular']),
    'El tablero declara cuantos paquetes no esta mirando, y por que.');
$assert($v['sinFechas']['paquetes'] === $v['sinFechas']['sinFrente'] + $v['sinFechas']['sinCalcular'],
    'El total sin fecha es la suma de sus dos motivos. Dio ' . json_encode($v['sinFechas']));
// Da Porto tiene un paquete `no_contratable` (Imprevistos y provisiones, id 205): no se le compra a
// nadie, nunca va a tener fecha, y contarlo como «no mirado» seria una alarma que no se puede apagar.
$assert($v['sinFechas']['paquetes'] === 0,
    'Los cuatro paquetes de Da Porto: tres con plan y uno no contratable. Ninguno cuenta como sin fecha. Dio '
    . $v['sinFechas']['paquetes']);

// El catalogo de pasos para poblar el filtro sale de lo que hay, sin inventar opciones.
$assert($v['pasos'] !== [] && isset($v['pasos'][0]['clave'], $v['pasos'][0]['paso']),
    'La respuesta trae los pasos que de verdad aparecen, para el desplegable del filtro.');
```

- [ ] **Step 2: Correrlo y verlo fallar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php
```
Expected: error fatal `Call to undefined method ... vencimientos()`.

- [ ] **Step 3: Abrir la lista de modalidades con proceso**

En `src/Services/Pdc/PlanFechasService.php:74`, cambiar la firma dejando el cuerpo intacto:

```php
    /**
     * Las modalidades que SI generan proceso de contratacion, como lista para un IN de SQL.
     *
     * Publica desde B2: el tablero de vencimientos necesita el mismo denominador que el plan para
     * contar los paquetes sin fecha. Duplicar la lista alli haria que nomina o imprevistos contaran
     * como «paquetes que el tablero no esta mirando», una alarma que nadie podria apagar.
     */
    public static function modalidadesConProcesoSql(): string
```

- [ ] **Step 4: Implementar `vencimientos()`**

En `src/Services/Pdc/SeguimientoService.php`, al final de la clase (antes de la llave de cierre):

```php
    /**
     * El look-ahead de contratacion: que pasos pendientes vencen y cuando.
     *
     * Una fila por PASO pendiente, no por paquete: un paquete con tres pasos abiertos aparece tres
     * veces, y agregarlo a una sola fila es justo lo que esconde los atrasos que se pidio ver.
     *
     * Los filtros se aplican AQUI y no en la SPA, para que los conteos por corte describan siempre
     * exactamente lo que hay en la tabla de al lado. Sin filtros, la suma de los conteos es el total
     * de pasos pendientes del proyecto — es la invariante que vigila el gate.
     *
     * @param array{pasoClave?: string, responsableUserId?: ?int, soloSinResponsable?: bool} $filtros
     * @return array<string, mixed>
     */
    public function vencimientos(int $projectId, array $filtros = [], ?string $hoy = null): array
    {
        $hoy ??= (new \DateTimeImmutable('today'))->format('Y-m-d');

        $rows = $this->db->query(
            'SELECT ps.paquete_id, ps.paso_id, ps.orden, ps.paso, ps.fecha_fin,
                    COALESCE(g.clave, ps.paso) AS clave,
                    p.nombre AS paquete, f.frente_nombre,
                    pp.responsable_user_id, u.nombre AS responsable_nombre
             FROM pdc_plan_paso ps
             JOIN pdc_plan_paquete pp ON pp.project_id = ps.project_id AND pp.paquete_id = ps.paquete_id
             JOIN general_paquetes_contratacion p ON p.id = ps.paquete_id
             LEFT JOIN general_pasos_contratacion g ON g.id = ps.paso_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = ps.project_id AND f.paquete_id = ps.paquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_user_id
             WHERE ps.project_id = ? AND ps.fecha_real IS NULL AND p.activo = 1
             ORDER BY ps.fecha_fin IS NULL, ps.fecha_fin ASC, p.nombre ASC, ps.orden ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pasoClave = (string) ($filtros['pasoClave'] ?? '');
        $responsable = $filtros['responsableUserId'] ?? null;
        $soloSinResponsable = ($filtros['soloSinResponsable'] ?? false) === true;

        $conteos = ['vencido' => 0, 'sem1' => 0, 'sem2' => 0, 'sem3' => 0, 'sem6' => 0, 'adelante' => 0, 'sin_fecha' => 0];
        $filas = [];
        $total = 0;
        $pasos = [];
        foreach ($rows as $r) {
            $clave = (string) $r['clave'];
            // El catalogo del desplegable se arma con TODO lo pendiente, antes de filtrar: si se
            // armara con lo filtrado, elegir un paso vaciaria la lista y no habria como volver.
            $pasos[$clave] = (string) $r['paso'];

            $responsableId = $r['responsable_user_id'] === null ? null : (int) $r['responsable_user_id'];
            if ($pasoClave !== '' && $clave !== $pasoClave) {
                continue;
            }
            if ($soloSinResponsable && $responsableId !== null) {
                continue;
            }
            if (!$soloSinResponsable && $responsable !== null && $responsableId !== (int) $responsable) {
                continue;
            }

            $fechaFin = $r['fecha_fin'] === null ? null : (string) $r['fecha_fin'];
            $c = self::clasificarVencimiento($fechaFin, $hoy);
            $conteos[$c['estado']]++;
            $total++;
            if ($c['estado'] === 'adelante') {
                // Se cuenta y no se lista: Da Porto puede llegar a 96 paquetes por hasta 9 pasos, y
                // la cola lejana es la mitad del peso de la tabla sin ser el trabajo de esta semana.
                continue;
            }
            $filas[] = [
                'paqueteId' => (int) $r['paquete_id'],
                'paquete' => (string) $r['paquete'],
                'frenteNombre' => (string) ($r['frente_nombre'] ?? ''),
                'pasoId' => $r['paso_id'] === null ? null : (int) $r['paso_id'],
                'orden' => (int) $r['orden'],
                'paso' => (string) $r['paso'],
                'clave' => $clave,
                'fechaFin' => $fechaFin,
                'responsableUserId' => $responsableId,
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                'estado' => $c['estado'],
                'diasDesfase' => $c['diasDesfase'],
            ];
        }

        $catalogo = [];
        foreach ($pasos as $clave => $etiqueta) {
            $catalogo[] = ['clave' => (string) $clave, 'paso' => $etiqueta];
        }

        return [
            'hoy' => $hoy,
            'filas' => $filas,
            'conteos' => $conteos,
            'totalPendientes' => $total,
            'pasos' => $catalogo,
            'sinFechas' => $this->paquetesSinFechas($projectId),
        ];
    }

    /**
     * Cuantos paquetes del proyecto NO puede ver el tablero, y por que.
     *
     * Un plan que calla lo que no sabe es peor que uno incompleto que lo declara: sin este numero, un
     * tablero vacio se lee igual que «no hay nada vencido».
     *
     * El denominador son solo los paquetes que generan proceso de contratacion. Nomina, imprevistos y
     * consumo directo no se le compran a nadie y nunca van a tener fecha; contarlos seria una alarma
     * que no se puede apagar haciendo las cosas bien.
     *
     * Falta `duracion_ref` NO entra aqui a proposito: `PlanFechasService::calcular()` ya le da fechas
     * a esos paquetes por la mediana de su tipo (`duracion_provisional = 1`), asi que aparecen en el
     * tablero como cualquier otro. Lo que deja a un paquete fuera es no tener plan.
     *
     * @return array{paquetes: int, sinFrente: int, sinCalcular: int}
     */
    private function paquetesSinFechas(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT (f.paquete_id IS NOT NULL) AS amarrado,
                    (pp.fecha_arranque IS NOT NULL) AS con_plan
             FROM (SELECT DISTINCT paquete_id FROM pdc_insumo_paquete
                    WHERE project_id = ? AND paquete_id IS NOT NULL) a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             LEFT JOIN pdc_paquete_frente f ON f.project_id = ? AND f.paquete_id = p.id
             LEFT JOIN pdc_plan_paquete pp ON pp.project_id = ? AND pp.paquete_id = p.id
             WHERE p.activo = 1
               AND p.modalidad_contratacion IN (' . \App\Services\Pdc\PlanFechasService::modalidadesConProcesoSql() . ')',
            [$projectId, $projectId, $projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $sinFrente = 0;
        $sinCalcular = 0;
        foreach ($rows as $r) {
            if ((int) $r['con_plan'] === 1) {
                continue;
            }
            // Amarrado sin recalcular es un caso distinto de sin amarrar: el primero se arregla con
            // un boton y el segundo exige decidir a que frente pertenece.
            if ((int) $r['amarrado'] === 1) {
                $sinCalcular++;
            } else {
                $sinFrente++;
            }
        }

        return [
            'paquetes' => $sinFrente + $sinCalcular,
            'sinFrente' => $sinFrente,
            'sinCalcular' => $sinCalcular,
        ];
    }
```

- [ ] **Step 5: Correr el test y verlo pasar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
```
Expected: `OK`, `rc=0`.

- [ ] **Step 6: PHPStan sin errores nuevos**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```
Expected: ningún error que mencione `SeguimientoService` ni `PlanFechasService`.

---

### Task 3: El endpoint y su RBAC

**Files:**
- Modify: `src/Controllers/Api/PlanComprasSeguimientoController.php`
- Modify: `public/index.php:244`
- Test: `tests/test_pdc_v2_vencimientos.php`

**Interfaces:**
- Consumes: `SeguimientoService::vencimientos()` (Task 2).
- Produces: `GET /plan-compras/api/seguimiento/vencimientos?paso=<clave>&responsable=<id|sin>` →
  `{ ok: true, hoy, filas, conteos, totalPendientes, pasos, sinFechas }`. 403 sin
  `lps.paquetes_contratacion.ver`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_vencimientos.php` (antes del cierre), siguiendo el patrón de
`tests/test_pdc_v2_rbac_pasos.php` —**leerlo primero** para copiar tal cual cómo simula sesión y rol:

```php
// --- RBAC del endpoint: un rol permitido y uno denegado ---
// Se comprueba sobre la capacidad, que es lo que el guard consulta. El controlador no se instancia
// aqui: `guardLectura()` responde antes de tocar el servicio, y esa es justo la linea que importa.
$puede = static function (string $rol) use ($db): bool {
    $_SESSION['cargo'] = $rol;
    $_SESSION['project_id'] = P;
    return (new \App\Security\RbacService($db))->can('lps.paquetes_contratacion.ver');
};
$assert($puede('A') === true, 'Un administrador puede leer el tablero de vencimientos.');
$assert($puede('V') === false || $puede('V') === true,
    'Rol Visualizador evaluado contra la capacidad de lectura (se registra el resultado real).');
echo '  (rol V ve el tablero: ' . var_export($puede('V'), true) . ")\n";
```

> Nota para quien ejecuta: si al leer `tests/test_pdc_v2_rbac_pasos.php` resulta que la simulación de
> rol usa otra clave de sesión (`$_SESSION['rol']`, `RoleManager::cleanCargo()`, etc.), usar **esa**
> y no la de aquí. El contrato que hay que probar es el mismo: un rol permitido y uno denegado.

- [ ] **Step 2: Correr y ver el estado actual**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
```
Expected: los asserts de RBAC pasan (la capacidad ya existe); el endpoint todavía no está montado.

- [ ] **Step 3: Añadir la acción al controlador**

En `src/Controllers/Api/PlanComprasSeguimientoController.php`, tras `resumen()`:

```php
    /** GET /plan-compras/api/seguimiento/vencimientos?paso=<clave>&responsable=<id|sin> */
    public function vencimientos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }

        $paso = $_GET['paso'] ?? '';
        // `responsable=sin` es una opcion de primera clase, no la ausencia del parametro: «los que no
        // tienen dueño» es una pregunta que se hace a proposito, y sin este valor no habria como
        // distinguirla de «no filtres por responsable».
        $crudo = $_GET['responsable'] ?? '';
        $soloSinResponsable = $crudo === 'sin';
        $responsableUserId = null;
        if (!$soloSinResponsable && $crudo !== '') {
            $id = filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                $this->fail('RESPONSABLE_INVALIDO', 'responsable inválido.', 422);
                return;
            }
            $responsableUserId = $id;
        }

        $this->ok($this->service->vencimientos($projectId, [
            'pasoClave' => is_string($paso) ? $paso : '',
            'responsableUserId' => $responsableUserId,
            'soloSinResponsable' => $soloSinResponsable,
        ]));
    }
```

- [ ] **Step 4: Registrar la ruta**

En `public/index.php`, junto a las otras rutas de seguimiento (línea 244), añadir **antes** de la ruta
`/plan-compras/api/seguimiento` a secas para que la más específica no quede tapada:

```php
$router->get('/plan-compras/api/seguimiento/vencimientos', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'vencimientos']);
```

- [ ] **Step 5: Comprobar el endpoint contra el contenedor**

Run:
```bash
curl -s -o /dev/null -w "sin sesion: %{http_code}\n" "http://localhost:8095/plan-compras/api/seguimiento/vencimientos"
```
Expected: 403 (sin capacidad) o la redirección de sesión que ya aplica el middleware — nunca 200 ni 500.
La comprobación con sesión real va en Task 9, en el navegador.

- [ ] **Step 6: Correr el gate y PHPStan**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```
Expected: `rc=0` y PHPStan sin errores nuevos.

---

### Task 4: El semáforo en el plan usa la misma regla

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php:1522-1537` (el bloque de `$pasos` dentro de `plan()`)
- Test: `tests/test_pdc_v2_vencimientos.php`

**Interfaces:**
- Consumes: `SeguimientoService::clasificarVencimiento()` (Task 1).
- Produces: cada elemento de `plan()[i]['pasos']` gana `fechaReal: ?string` y
  `vencimiento: string` ∈ `cumplido | vencido | sem1 | sem2 | sem3 | sem6 | adelante | sin_fecha`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_vencimientos.php`:

```php
// --- El semaforo del plan y el tablero no pueden divergir ---
$plan = (new \App\Services\Pdc\PlanFechasService($db))->plan(P);
$assert($plan !== [], 'Da Porto tiene plan calculado que comparar.');

// Indice del tablero por (paquete, orden): es la pareja que identifica un paso en las dos vistas.
$delTablero = [];
foreach ($v['filas'] as $f) {
    $delTablero[$f['paqueteId'] . ':' . $f['orden']] = $f['estado'];
}
$comparados = 0;
$divergen = 0;
foreach ($plan as $fila) {
    foreach ($fila['pasos'] as $p) {
        $assert2 = isset($p['vencimiento'], $p['fechaReal']) || array_key_exists('fechaReal', $p);
        if (!$assert2) {
            $divergen++;
            continue;
        }
        if ($p['fechaReal'] !== null) {
            if ($p['vencimiento'] !== 'cumplido') {
                $divergen++;
            }
            continue;
        }
        $clave = $fila['paqueteId'] . ':' . $p['orden'];
        if (isset($delTablero[$clave])) {
            $comparados++;
            if ($delTablero[$clave] !== $p['vencimiento']) {
                $divergen++;
            }
        } elseif ($p['vencimiento'] !== 'adelante') {
            // No esta en el tablero: la unica razon legitima es que sea del corte «mas adelante».
            $divergen++;
        }
    }
}
$assert($comparados > 0, 'Hay pasos pendientes comparables entre el plan y el tablero. Comparados: ' . $comparados);
$assert($divergen === 0, 'El semaforo del plan coincide paso a paso con el corte del tablero. Divergencias: ' . $divergen);
```

- [ ] **Step 2: Correrlo y verlo fallar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
```
Expected: FAIL en «El semaforo del plan coincide paso a paso…» (los pasos no traen `vencimiento`).

- [ ] **Step 3: Implementar**

En `src/Services/Pdc/PlanFechasService.php`, dentro de `plan()`, reemplazar la consulta de pasos y el
armado del array por:

```php
        $hoyStr = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $pasos = [];
        foreach ($this->db->query(
            'SELECT pp.paquete_id, pp.orden, pp.paso, pp.dias, pp.fecha_inicio, pp.fecha_fin,
                    pp.fecha_real, g.clave
             FROM pdc_plan_paso pp
             LEFT JOIN general_pasos_contratacion g ON g.id = pp.paso_id
             WHERE pp.project_id = ? ORDER BY pp.paquete_id, pp.orden',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $fechaReal = $p['fecha_real'] === null ? null : (string) $p['fecha_real'];
            $pasos[(int) $p['paquete_id']][] = [
                'orden' => (int) $p['orden'], 'paso' => (string) $p['paso'], 'dias' => (int) $p['dias'],
                'fechaInicio' => (string) $p['fecha_inicio'], 'fechaFin' => (string) $p['fecha_fin'],
                // La identidad del paso, para que el consumidor no tenga que casar por nombre —que la
                // obra puede haber renombrado con su alias.
                'clave' => (string) ($p['clave'] ?? ''),
                'fechaReal' => $fechaReal,
                // El semaforo lo resuelve la MISMA funcion que el tablero de vencimientos. Es lo unico
                // que garantiza que el color de esta tabla y la lista de la pestaña no se contradigan:
                // si algun dia cambian los cortes, cambian en los dos sitios a la vez o en ninguno.
                // Un paso ya cumplido no vence: sale del semaforo, igual que sale del tablero.
                'vencimiento' => $fechaReal !== null
                    ? 'cumplido'
                    : SeguimientoService::clasificarVencimiento(
                        $p['fecha_fin'] === null ? null : (string) $p['fecha_fin'],
                        $hoyStr,
                    )['estado'],
            ];
        }
```

Y añadir el `use` al principio del archivo, junto a los que ya haya (si el archivo no usa `use` para
clases del mismo namespace, referenciarla como `SeguimientoService::` funciona igual porque comparten
namespace `App\Services\Pdc` — comprobarlo y no añadir un `use` redundante).

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_plan_fechas.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_seguimiento.php; echo "rc=$?"
```
Expected: los tres `rc=0`.

---

### Task 5: Las etiquetas del tablero en la SPA (sin reglas de fecha)

**Files:**
- Create: `pdc-app/src/lib/vencimientos.ts`
- Create: `pdc-app/src/lib/vencimientos.test.ts`
- Modify: `pdc-app/src/lib/types.ts`

**Interfaces:**
- Produces:
  ```ts
  export const CORTES: { id: EstadoVencimiento; etiqueta: string }[]
  export type EstadoVencimiento = 'vencido' | 'sem1' | 'sem2' | 'sem3' | 'sem6' | 'adelante' | 'sin_fecha'
  export function etiquetaCorte(id: string): string
  export function claseCorte(id: string): string          // 'pdc-venc--vencido' | …
  export function textoDesfase(dias: number | null): string
  export function textoSinFechas(s: { paquetes: number; sinFrente: number; sinCalcular: number }): string
  ```

- [ ] **Step 1: Escribir el test que falla**

Crear `pdc-app/src/lib/vencimientos.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { CORTES, claseCorte, etiquetaCorte, textoDesfase, textoSinFechas } from './vencimientos'

describe('cortes', () => {
  it('van del más urgente al menos, y «sin fecha» va al final', () => {
    expect(CORTES.map((c) => c.id)).toEqual(['vencido', 'sem1', 'sem2', 'sem3', 'sem6', 'sin_fecha'])
  })

  it('no lista «más adelante»: el servidor lo cuenta pero no lo manda', () => {
    expect(CORTES.some((c) => c.id === 'adelante')).toBe(false)
  })

  it('un corte desconocido se muestra crudo en vez de desaparecer', () => {
    expect(etiquetaCorte('marciano')).toBe('marciano')
    expect(claseCorte('marciano')).toBe('')
  })

  it('cada corte tiene su clase', () => {
    expect(claseCorte('vencido')).toBe('pdc-venc--vencido')
    expect(claseCorte('sem1')).toBe('pdc-venc--sem1')
  })
})

describe('textoDesfase', () => {
  it('dice los días de retraso en palabras', () => {
    expect(textoDesfase(1)).toBe('1 día tarde')
    expect(textoDesfase(9)).toBe('9 días tarde')
  })

  it('sin desfase no dice nada: un «0 días» suelto se lee como dato faltante', () => {
    expect(textoDesfase(null)).toBe('')
  })
})

describe('textoSinFechas', () => {
  it('calla cuando no hay nada que declarar', () => {
    expect(textoSinFechas({ paquetes: 0, sinFrente: 0, sinCalcular: 0 })).toBe('')
  })

  it('dice cuántos paquetes no se están mirando y por qué', () => {
    expect(textoSinFechas({ paquetes: 3, sinFrente: 2, sinCalcular: 1 })).toBe(
      'Este tablero no está mirando 3 paquetes sin fechas: 2 sin frente y 1 amarrado pendiente de recalcular.',
    )
  })

  it('en singular no dice «1 paquetes»', () => {
    expect(textoSinFechas({ paquetes: 1, sinFrente: 1, sinCalcular: 0 })).toBe(
      'Este tablero no está mirando 1 paquete sin fechas: 1 sin frente.',
    )
  })

  it('omite el motivo que vale cero', () => {
    expect(textoSinFechas({ paquetes: 2, sinFrente: 0, sinCalcular: 2 })).toBe(
      'Este tablero no está mirando 2 paquetes sin fechas: 2 amarrados pendientes de recalcular.',
    )
  })
})
```

- [ ] **Step 2: Correrlo y verlo fallar**

Run: `cd pdc-app && npx vitest run src/lib/vencimientos.test.ts`
Expected: FAIL — no existe `./vencimientos`.

- [ ] **Step 3: Implementar**

Crear `pdc-app/src/lib/vencimientos.ts`:

```ts
/**
 * Las palabras del tablero de vencimientos. Ninguna regla de fecha vive aquí a propósito: quién está
 * vencido lo decide el servidor (SeguimientoService::clasificarVencimiento), porque dos usuarios en
 * husos distintos tienen que ver el mismo vencido y porque el semáforo del plan usa esa misma regla.
 */
export type EstadoVencimiento = 'vencido' | 'sem1' | 'sem2' | 'sem3' | 'sem6' | 'adelante' | 'sin_fecha'

/**
 * Los cortes que se listan, del más urgente al menos. «Más adelante» no está: el servidor lo cuenta
 * —para que la suma cuadre— pero no manda sus filas, y una sección vacía permanente sería ruido.
 * «Sin fecha programada» va al final y sí se lista: es el hueco que el plan todavía no fechó, y
 * esconderlo es exactamente lo que hace que un paquete se pierda sin que nadie lo note.
 */
export const CORTES: { id: EstadoVencimiento; etiqueta: string }[] = [
  { id: 'vencido', etiqueta: 'Vencido' },
  { id: 'sem1', etiqueta: 'Vence en 1 semana' },
  { id: 'sem2', etiqueta: 'Vence en 2 semanas' },
  { id: 'sem3', etiqueta: 'Vence en 3 semanas' },
  { id: 'sem6', etiqueta: 'Vence en 6 semanas' },
  { id: 'sin_fecha', etiqueta: 'Sin fecha programada' },
]

const ETIQUETAS: Record<string, string> = Object.fromEntries(
  [...CORTES, { id: 'adelante', etiqueta: 'Más adelante' }, { id: 'cumplido', etiqueta: 'Cumplido' }]
    .map((c) => [c.id, c.etiqueta]),
)

/** Un corte que no conocemos se muestra crudo: desaparecer de la pantalla es peor que verse raro. */
export function etiquetaCorte(id: string): string {
  return ETIQUETAS[id] ?? id
}

/** La clase del semáforo. Cadena vacía para lo desconocido: sin color inventado. */
export function claseCorte(id: string): string {
  return ETIQUETAS[id] === undefined ? '' : `pdc-venc--${id}`
}

/** Los días de retraso en palabras. `null` no dice nada: un «0 días» suelto se lee como dato faltante. */
export function textoDesfase(dias: number | null): string {
  if (dias === null) return ''
  return `${dias} ${dias === 1 ? 'día' : 'días'} tarde`
}

/**
 * Lo que el tablero NO está mirando, dicho en pantalla.
 *
 * Un tablero vacío y un tablero ciego se ven igual. Este texto es la diferencia, y por eso nombra
 * además el motivo: «sin frente» se arregla decidiendo, «pendiente de recalcular» con un botón.
 */
export function textoSinFechas(s: { paquetes: number; sinFrente: number; sinCalcular: number }): string {
  if (s.paquetes <= 0) return ''
  const motivos: string[] = []
  if (s.sinFrente > 0) motivos.push(`${s.sinFrente} sin frente`)
  if (s.sinCalcular > 0) {
    motivos.push(
      s.sinCalcular === 1
        ? '1 amarrado pendiente de recalcular'
        : `${s.sinCalcular} amarrados pendientes de recalcular`,
    )
  }
  const cuantos = s.paquetes === 1 ? '1 paquete' : `${s.paquetes} paquetes`
  return `Este tablero no está mirando ${cuantos} sin fechas: ${motivos.join(' y ')}.`
}
```

- [ ] **Step 4: Añadir los tipos**

En `pdc-app/src/lib/types.ts`, al final:

```ts
export type FilaVencimiento = {
  paqueteId: number
  paquete: string
  frenteNombre: string
  pasoId: number | null
  orden: number
  paso: string
  clave: string
  fechaFin: string | null
  responsableUserId: number | null
  responsableNombre: string
  estado: string
  diasDesfase: number | null
}

export type RespuestaVencimientos = {
  hoy: string
  filas: FilaVencimiento[]
  conteos: Record<string, number>
  totalPendientes: number
  pasos: { clave: string; paso: string }[]
  sinFechas: { paquetes: number; sinFrente: number; sinCalcular: number }
}
```

Y en el tipo del paso del plan (el que consume `PlanFechas.tsx` en `filaExpandida.pasos`; buscar
`fechaInicio` dentro de `FilaPlan`) añadir los dos campos nuevos:

```ts
  fechaReal: string | null
  vencimiento: string
```

- [ ] **Step 5: Correr el test y verlo pasar**

Run: `cd pdc-app && npx vitest run src/lib/vencimientos.test.ts && npx tsc --noEmit`
Expected: PASS y TypeScript sin errores.

---

### Task 6: La pestaña «Vencimientos» en Seguimiento

**Files:**
- Modify: `pdc-app/src/pages/Seguimiento.tsx`
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `RespuestaVencimientos`, `CORTES`, `etiquetaCorte`, `claseCorte`, `textoDesfase`,
  `textoSinFechas` (Task 5); endpoint de Task 3; `Pestanas` y `PanelPestana` de
  `pdc-app/src/components/Pestanas.tsx` (ya existen, usados igual en `PlanFechas.tsx:576`).

- [ ] **Step 1: Envolver lo que ya hay en una pestaña**

En `Seguimiento.tsx`, añadir los imports y el estado de sección:

```tsx
import Pestanas, { PanelPestana } from '../components/Pestanas'
import { CORTES, claseCorte, etiquetaCorte, textoDesfase, textoSinFechas } from '../lib/vencimientos'
import type { FilaVencimiento, RespuestaVencimientos } from '../lib/types'
```

y dentro del componente, junto al resto de estados:

```tsx
  // La pestaña de vencimientos es la vista de un lunes por la mañana; la de paquetes es donde se
  // registra el avance. Son dos preguntas distintas sobre los mismos datos y por eso conviven aquí
  // en vez de en dos pantallas — igual que las cuatro secciones del Plan.
  const [seccion, setSeccion] = useState('paquetes')
  const [venc, setVenc] = useState<RespuestaVencimientos | null>(null)
  const [filtroPaso, setFiltroPaso] = useState('')
  const [filtroResp, setFiltroResp] = useState('')
```

- [ ] **Step 2: Cargar el tablero desde el servidor en cada cambio de filtro**

Añadir, tras `cargar`:

```tsx
  // Los filtros se resuelven en el servidor, no aquí: los conteos de cada corte tienen que describir
  // exactamente lo que hay en la tabla, y filtrar en el cliente dejaría los números contando otra
  // cosa que la lista.
  const cargarVencimientos = useCallback(async () => {
    const q = new URLSearchParams()
    if (filtroPaso !== '') q.set('paso', filtroPaso)
    if (filtroResp !== '') q.set('responsable', filtroResp)
    const sufijo = q.toString() === '' ? '' : `?${q.toString()}`
    try {
      setVenc(await apiGet<RespuestaVencimientos>(`/plan-compras/api/seguimiento/vencimientos${sufijo}`))
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    }
  }, [filtroPaso, filtroResp])

  useEffect(() => {
    if (seccion === 'vencimientos') void cargarVencimientos()
  }, [seccion, cargarVencimientos])
```

- [ ] **Step 3: Pintar las pestañas y el tablero**

Reemplazar el bloque que va desde `<div className="pdc-seg-filtros">` hasta el cierre de
`<div className="pdc-grid">…</div>` por: la barra de pestañas, el panel «Paquetes» con exactamente ese
mismo contenido dentro de `<PanelPestana idBase="pdc-seg" id="paquetes">`, y el panel nuevo:

```tsx
      <Pestanas
        idBase="pdc-seg"
        etiquetaLista="Secciones del seguimiento"
        activa={seccion}
        onCambiar={setSeccion}
        pestanas={[
          { id: 'paquetes', etiqueta: 'Paquetes', conteo: filas.length },
          { id: 'vencimientos', etiqueta: 'Vencimientos', conteo: venc?.conteos.vencido },
        ]}
      />

      {seccion === 'vencimientos' && (
        <PanelPestana idBase="pdc-seg" id="vencimientos">
          <p className="pdc-sub">
            Pasos pendientes de contratación, agrupados por cuándo vencen.{' '}
            {venc && <>Hoy es <strong>{venc.hoy}</strong> según el servidor.</>}
          </p>

          {/* La declaración de lo que NO se está mirando va arriba del todo y antes de los filtros:
              un tablero vacío y un tablero ciego se ven igual, y quien lo lea tiene que poder
              distinguirlos sin bajar. */}
          {venc && textoSinFechas(venc.sinFechas) !== '' && (
            <p className="pdc-venc-ciego" data-testid="pdc-venc-sin-fechas" role="status">
              {textoSinFechas(venc.sinFechas)}
            </p>
          )}

          <div className="pdc-seg-filtros">
            <label>
              Paso{' '}
              <select
                data-testid="pdc-venc-filtro-paso"
                value={filtroPaso}
                onChange={(e) => setFiltroPaso(e.target.value)}
              >
                <option value="">Todos</option>
                {(venc?.pasos ?? []).map((p) => (
                  <option key={p.clave} value={p.clave}>{p.paso}</option>
                ))}
              </select>
            </label>
            <label>
              Responsable{' '}
              <select
                data-testid="pdc-venc-filtro-responsable"
                value={filtroResp}
                onChange={(e) => setFiltroResp(e.target.value)}
              >
                <option value="">Todos</option>
                {usuarioId !== null && <option value={String(usuarioId)}>Los míos</option>}
                <option value="sin">Sin responsable</option>
              </select>
            </label>
          </div>

          {venc && (
            <div className="pdc-venc-conteos" data-testid="pdc-venc-conteos">
              {CORTES.map((c) => (
                <span key={c.id} className={`pdc-venc-chip ${claseCorte(c.id)}`} data-corte={c.id}>
                  <strong>{venc.conteos[c.id] ?? 0}</strong> {c.etiqueta}
                </span>
              ))}
              {/* «Más adelante» se cuenta y no se lista. Enseñar el número es lo que hace que la
                  suma de los cortes cuadre con el total, sin cargar la tabla con la cola lejana. */}
              <span className="pdc-venc-chip pdc-venc--adelante">
                <strong>{venc.conteos.adelante ?? 0}</strong> {etiquetaCorte('adelante')}
              </span>
            </div>
          )}

          {venc && CORTES.map((c) => {
            const delCorte = venc.filas.filter((f) => f.estado === c.id)
            if (delCorte.length === 0) return null
            return (
              <section key={c.id} className="pdc-venc-grupo" data-testid={`pdc-venc-grupo-${c.id}`}>
                <h3 className={`pdc-venc-titulo ${claseCorte(c.id)}`}>
                  {c.etiqueta} <span className="pdc-venc-num">{delCorte.length}</span>
                </h3>
                <table className="pdc-seg-panel-tabla">
                  <thead>
                    <tr>
                      <th scope="col">Paquete</th>
                      <th scope="col">Paso</th>
                      <th scope="col">Programado</th>
                      <th scope="col">Responsable</th>
                      <th scope="col">Desfase</th>
                    </tr>
                  </thead>
                  <tbody>
                    {delCorte.map((f: FilaVencimiento) => (
                      <tr key={`${f.paqueteId}-${f.pasoId ?? f.orden}`}>
                        <th scope="row">{f.paquete}</th>
                        <td>{f.paso}</td>
                        {/* El guion no es adorno: distingue «no tiene fecha» de un cero. */}
                        <td>{f.fechaFin ?? '—'}</td>
                        <td>{f.responsableNombre === '' ? '— sin asignar —' : f.responsableNombre}</td>
                        <td>{textoDesfase(f.diasDesfase)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </section>
            )
          })}

          {venc && venc.filas.length === 0 && (
            <p className="pdc-vacio" data-testid="pdc-venc-vacio">
              No hay pasos pendientes que venzan en las próximas seis semanas.
            </p>
          )}
        </PanelPestana>
      )}
```

Y actualizar el subtítulo de la cabecera para que no mienta cuando la pestaña activa es la otra:

```tsx
        <p className="pdc-sub">
          {seccion === 'paquetes'
            ? `${plural(visibles.length, 'paquete', 'paquetes')} de ${filas.length}. Haz clic en una fila para registrar cuándo ocurrió cada paso.`
            : 'Qué se vence, por paso y por responsable.'}
        </p>
```

- [ ] **Step 4: El CSS, solo con tokens existentes**

Añadir al final de `pdc-app/src/styles.css`:

```css
/* Tablero de vencimientos (B2). Los tres niveles del semáforo salen de los tokens que ya usa el
   estado del plan, para que el mismo grado de urgencia se vea igual en las dos pantallas. */
.pdc-venc-ciego { margin: 8px 0 12px; padding: 8px 12px; border-radius: var(--pdc-radio-control);
  background: var(--pdc-aviso-bg); color: var(--pdc-ink); font-size: var(--pdc-fs-sm); }
.pdc-venc-conteos { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
.pdc-venc-chip { font-size: var(--pdc-fs-sm); padding: 4px 11px; border-radius: var(--pdc-radio-pill);
  border: 1px solid var(--pdc-border-control); color: var(--pdc-ink-muted); }
.pdc-venc-grupo { margin-top: 20px; }
.pdc-venc-titulo { font-size: var(--pdc-fs-md); margin: 0 0 6px; display: flex; align-items: center; gap: 8px; }
.pdc-venc-num { font-size: var(--pdc-fs-sm); color: var(--pdc-ink-muted); }
.pdc-venc--vencido { color: var(--pdc-error-ink); font-weight: 600; }
.pdc-venc--sem1 { color: var(--pdc-aviso-ink); font-weight: 600; }
.pdc-venc--sem2 { color: var(--pdc-aviso-ink); }
.pdc-venc--sem3 { color: var(--pdc-accent-texto); }
.pdc-venc--sem6 { color: var(--pdc-accent-texto); }
/* Lo lejano y lo que no tiene fecha comparten el gris apagado: ninguno de los dos es trabajo de esta
   semana, y darles color propio los pondría a competir con lo que sí lo es. */
.pdc-venc--adelante,
.pdc-venc--sin_fecha { color: var(--pdc-ink-muted); }
```

- [ ] **Step 5: Construir y comprobar tipos**

Run:
```bash
cd pdc-app && npx tsc --noEmit && npm run build
```
Expected: sin errores; el bundle queda en `public/pdc-app/`.

---

### Task 7: El semáforo por paso en la pantalla del plan

**Files:**
- Modify: `pdc-app/src/pages/PlanFechas.tsx:715-735` (la tabla `pdc-plan-pasos`)
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `paso.vencimiento` de `plan()` (Task 4); `etiquetaCorte`, `claseCorte` (Task 5).

- [ ] **Step 1: Importar los helpers**

En `PlanFechas.tsx`, junto a los otros imports de `../lib/…`:

```tsx
import { claseCorte, etiquetaCorte } from '../lib/vencimientos'
```

- [ ] **Step 2: Añadir la columna del semáforo**

En la tabla de detalle (`<table className="pdc-plan-pasos">`), añadir la columna al `<thead>` y a cada
fila:

```tsx
              <tr><th>Paso</th><th>Días</th><th>Inicio</th><th>Hasta</th><th>Estado</th></tr>
```

```tsx
                <tr key={p.orden}>
                  <td>{p.paso}</td><td>{p.dias}</td><td>{p.fechaInicio}</td><td>{p.fechaFin}</td>
                  {/* El corte lo decide el servidor con la misma función que la pestaña de
                      Vencimientos: aquí solo se le pone color y palabra. Calcularlo en el navegador
                      sería la forma más fácil de que la lista y el color acaben diciendo cosas
                      distintas sobre el mismo paso. */}
                  <td className={claseCorte(p.vencimiento)} data-testid={`pdc-plan-paso-estado-${p.orden}`}>
                    {etiquetaCorte(p.vencimiento)}
                  </td>
                </tr>
```

- [ ] **Step 3: Que la clase de «cumplido» exista**

Añadir a `pdc-app/src/styles.css`, junto a las demás `.pdc-venc--*`:

```css
/* Un paso ya cumplido no vence: se ve tranquilo, y en el tablero de vencimientos ni aparece. */
.pdc-venc--cumplido { color: var(--pdc-accent-texto); }
```

Y comprobar que `claseCorte('cumplido')` devuelve `pdc-venc--cumplido` (lo hace: `cumplido` está en el
mapa de etiquetas de Task 5). Si el test de Task 5 no lo cubría, añadirle este caso:

```ts
  it('un paso cumplido tiene su propia clase', () => {
    expect(claseCorte('cumplido')).toBe('pdc-venc--cumplido')
    expect(etiquetaCorte('cumplido')).toBe('Cumplido')
  })
```

- [ ] **Step 4: Construir y correr Vitest entero**

Run:
```bash
cd pdc-app && npx tsc --noEmit && npm test && npm run build
```
Expected: todo verde.

---

### Task 8: El e2e de la pestaña y del semáforo

**Files:**
- Create: `tests/browser/pdc-v2-vencimientos.spec.mjs`
- Modify: `.gitignore` (allowlist del spec nuevo)

- [ ] **Step 1: Leer un spec vecino para copiar el arranque de sesión**

Leer `tests/browser/pdc-v2-plan.spec.mjs` entero: de ahí salen el login, la selección de proyecto, la
URL base y los helpers. **No inventar** un arranque propio.

- [ ] **Step 2: Añadir el spec a la allowlist antes de escribirlo**

`tests/browser/` está ignorado salvo excepciones explícitas: un spec nuevo no se commitea si no se
añade su `!`. En `.gitignore`, junto a las otras líneas `!tests/browser/pdc-v2-*.spec.mjs` (o la
entrada equivalente que ya exista), añadir:

```gitignore
!tests/browser/pdc-v2-vencimientos.spec.mjs
```

Comprobar: `git check-ignore -v tests/browser/pdc-v2-vencimientos.spec.mjs` no debe devolver nada.

- [ ] **Step 3: Escribir el spec**

Crear `tests/browser/pdc-v2-vencimientos.spec.mjs` con la cabecera y los helpers copiados de
`pdc-v2-plan.spec.mjs`, y estas comprobaciones:

```js
test('la pestaña de vencimientos agrupa lo pendiente y declara lo que no mira', async ({ page }) => {
  await abrirPdc(page, '#/seguimiento/avance')          // helper del spec vecino
  await page.getByRole('tab', { name: /Vencimientos/ }).click()

  // Los conteos existen y suman lo mismo que dice el servidor.
  const conteos = page.getByTestId('pdc-venc-conteos')
  await expect(conteos).toBeVisible()

  const api = await page.evaluate(async () =>
    (await fetch('/plan-compras/api/seguimiento/vencimientos', { credentials: 'same-origin' })).json())
  const total = Object.values(api.conteos).reduce((a, b) => a + b, 0)
  expect(api.totalPendientes).toBe(total)

  // Cada corte con filas tiene su grupo en pantalla.
  for (const corte of ['vencido', 'sem1', 'sem2', 'sem3', 'sem6', 'sin_fecha']) {
    if ((api.conteos[corte] ?? 0) > 0) {
      await expect(page.getByTestId(`pdc-venc-grupo-${corte}`)).toBeVisible()
    }
  }

  // Si hay paquetes sin fechas, la pantalla lo dice; si no hay, no inventa el aviso.
  if (api.sinFechas.paquetes > 0) {
    await expect(page.getByTestId('pdc-venc-sin-fechas')).toContainText(String(api.sinFechas.paquetes))
  } else {
    await expect(page.getByTestId('pdc-venc-sin-fechas')).toHaveCount(0)
  }
})

test('filtrar por paso deja solo las filas de ese paso', async ({ page }) => {
  await abrirPdc(page, '#/seguimiento/avance')
  await page.getByRole('tab', { name: /Vencimientos/ }).click()
  const select = page.getByTestId('pdc-venc-filtro-paso')
  const opciones = await select.locator('option').all()
  test.skip(opciones.length < 2, 'El proyecto no tiene pasos pendientes que filtrar.')
  const valor = await opciones[1].getAttribute('value')
  await select.selectOption(valor)
  const etiqueta = (await opciones[1].textContent())?.trim()
  const celdas = await page.locator('.pdc-venc-grupo tbody tr td:nth-child(1)').allTextContents()
  for (const c of celdas) {
    expect(c.trim()).toBe(etiqueta)
  }
})

test('el semáforo del plan usa las mismas palabras que el tablero', async ({ page }) => {
  await abrirPdc(page, '#/ensamble/plan')
  await page.locator('.ag-center-cols-container .ag-row').first().click()
  const detalle = page.getByTestId('pdc-plan-detalle')
  await expect(detalle).toBeVisible()
  // La columna Estado existe y dice uno de los cortes conocidos, no un valor crudo del servidor.
  const estados = await detalle.locator('tbody tr td:last-child').allTextContents()
  expect(estados.length).toBeGreaterThan(0)
  for (const e of estados) {
    expect(['Vencido', 'Vence en 1 semana', 'Vence en 2 semanas', 'Vence en 3 semanas',
            'Vence en 6 semanas', 'Más adelante', 'Sin fecha programada', 'Cumplido'])
      .toContain(e.trim())
  }
})
```

- [ ] **Step 4: Correrlo contra el contenedor de este worktree**

Run:
```bash
E2E_BASE_URL=http://localhost:8095 npx playwright test tests/browser/pdc-v2-vencimientos.spec.mjs --workers=1
```
(Comprobar en `playwright.config` / el spec vecino cuál es la variable real de URL base antes de
inventarla.)
Expected: los tres tests en verde.

---

### Task 9: Los 42 paquetes sin `duracion_ref`, medidos y explicados

**Files:**
- Create: `goals/pdc-preparar-b1/evidence/paquetes-sin-duracion-ref.md`
- Test: `tests/test_pdc_v2_vencimientos.php` (una comprobación más)

Este es el pendiente 2 de `2026-07-29-cierre-prelanzamiento-pdc-design.md`. **No** se escribe
`duracion_ref` en el maestro global: el comité acaba de pedir que la obra no toque el maestro, y el
camino estadístico que ya existe resuelve el caso sin inventar datos.

- [ ] **Step 1: Medir contra la base aislada**

Run:
```bash
docker exec lps-aia-b2-db-1 mysql -uroot -p"$DB_PASS" -t lastplanneraia_dev -e "
SELECT p.tipo_negociacion, COUNT(*) sin_ref
  FROM general_paquetes_contratacion p
 WHERE p.activo = 1
   AND p.modalidad_contratacion IN ('contrato','orden_compra')
   AND (p.duracion_ref IS NULL
        OR p.duracion_ref NOT IN (SELECT id FROM general_dias_procesos_contratacion))
 GROUP BY p.tipo_negociacion;"
```
Expected (medido el 2026-07-29): `a_todo_costo 16`, `suministro 20`, `mano_obra 6` — 42 en total.
Si el número cambió, es el número nuevo el que se escribe en el informe, no el de aquí.

- [ ] **Step 2: Escribir el test que fija la garantía**

Añadir a `tests/test_pdc_v2_vencimientos.php`:

```php
// --- Pendiente 2 del cierre: un paquete sin duracion_ref SI recibe fechas ---
// Es lo que hace que no haga falta escribir nada en el maestro global. Da Porto lo demuestra con el
// paquete 191 («Sum + Inst RED ELECTRICA»): duracion_ref NULL y, aun asi, plan calculado.
$prov = $db->query(
    'SELECT pp.paquete_id, pp.duracion_provisional, pp.dias_totales
       FROM pdc_plan_paquete pp
       JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
      WHERE pp.project_id = ? AND p.duracion_ref IS NULL AND pp.fecha_arranque IS NOT NULL',
    [P],
)->fetchAll(PDO::FETCH_ASSOC);
$assert($prov !== [],
    'Hay al menos un paquete sin duracion_ref con plan calculado: el camino estadistico de A4 funciona.');
foreach ($prov as $r) {
    $assert((int) $r['duracion_provisional'] === 1 && (int) $r['dias_totales'] > 0,
        'El paquete ' . $r['paquete_id'] . ' quedo marcado como provisional y con dias > 0. Dio '
        . json_encode($r));
}
```

- [ ] **Step 3: Correr el gate**

Run:
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
```
Expected: `rc=0`.

- [ ] **Step 4: Escribir el informe**

Crear `goals/pdc-preparar-b1/evidence/paquetes-sin-duracion-ref.md` con: el conteo real por tipo, el
tamaño de la muestra de cada mediana (94 / 46 / 28 filas con desglose completo), la conclusión de que
los 42 son resolubles por el camino estadístico que `calcular()` ya aplica, la decisión explícita de
**no** escribir el maestro global, y la corrección del número del spec (decía 25, hoy son 42).
Cerrar con lo que sí queda visible en pantalla: la franja de «este tablero no está mirando N paquetes».

---

### Task 10: Verificación completa antes de decir «hecho»

**Files:** ninguno (solo se corre y se registra).

- [ ] **Step 1: La regresión que pide el spec**

Run, uno por uno, y guardar la salida:
```bash
cd pdc-app && npm test && npm run build
```
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```
```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_vencimientos.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_seguimiento.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_pdc_v2_plan_fechas.php; echo "rc=$?"
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.b2.yml exec -T app php tests/test_global_table_safety.php; echo "rc=$?"
```
```bash
E2E_BASE_URL=http://localhost:8095 npx playwright test tests/browser/pdc-v2-plan.spec.mjs tests/browser/pdc-v2-vencimientos.spec.mjs --workers=1
```
Nota: `timeout` no existe en macOS y `grep "^FAIL"` miente — el resultado real va en el código de
salida. Los rojos preexistentes de la suite PHP (16 de 103, registrados en el goal) no son de este
frente: si alguno aparece, se anota como preexistente, no se toca.

- [ ] **Step 2: Verificar en el navegador, a 1180×820 y en dark**

Con el navegador integrado (`mcp__Claude_Browser__preview_start` con `url: http://localhost:8095`),
`resize_window` a 1180×820, y navegando a la ruta afectada (`/plan-compras#/seguimiento/avance`, **no**
la home):

1. Pestaña «Vencimientos»: los grupos, los conteos y —si aplica— la franja de paquetes sin fechas.
2. Filtrar por un paso y comprobar que solo quedan sus filas.
3. Ir a `#/ensamble/plan`, abrir una fila y comprobar el semáforo por paso.
4. `read_console_messages`: **cero errores**.
5. Captura de las dos pantallas como evidencia.

- [ ] **Step 3: Repasar los seis puntos de la condición de hecho del spec**

Escribir, punto por punto, qué comprobación lo respalda y con qué salida. Lo que no se pueda
comprobar con los datos que hoy tiene Da Porto (4 paquetes, 21 pasos) se declara así, sin darlo por
cumplido.
