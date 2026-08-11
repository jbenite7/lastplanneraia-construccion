<?php
/**
 * A3.5 · El estado de DAPORTO es el punto de partida canónico: mide cuánto de él reproduce el motor
 * corriendo desde cero. Cada diferencia es una regla que falta o un dato por corregir.
 *
 * No es un test unitario — depende del proyecto real y se salta solo si no está sembrado. Su valor
 * es de regresión: si un cambio en las reglas rompe algo que ya funcionaba, la brecha crece aquí.
 *
 *   php tests/test_pdc_v2_brecha_daporto.php            resumen + fallo si la brecha supera el techo
 *   php tests/test_pdc_v2_brecha_daporto.php --detalle  además, cada diferencia con su evidencia
 *   php tests/test_pdc_v2_brecha_daporto.php --json     vuelca la brecha a brecha.json
 *
 * ---------------------------------------------------------------------------------------------
 * 2026-07-29 · POR QUÉ LA VERSIÓN DEL PRESUPUESTO YA NO VA CABLEADA
 *
 * Este test fijaba `$VERSION = 292`. Desde que las bases de desarrollo tienen otra versión activa
 * de DAPORTO (la 376), fallaba **siempre** con «no hay versión 292 en el proyecto 73» — un rojo
 * permanente que no dice nada del código y que enseña a ignorar la salida del test.
 *
 * El arreglo no es aflojar la medición, es corregirla. Lo que este test ancla es el **estado
 * canónico de asignaciones** (`pdc_insumo_paquete`), y esa tabla **no lleva `version_id`**: sus
 * filas son del proyecto, no de una versión. El presupuesto es solo la ENTRADA que el motor lee.
 * Con un id fijo, el test acabó comparando las propuestas del motor sobre un presupuesto viejo
 * contra unas asignaciones que pertenecen al presupuesto vigente: justo lo contrario de lo que su
 * primera línea promete.
 *
 * Así que la versión se resuelve como la resuelve producción: `proponerSembrado()` con `versionId`
 * en `null` toma la activa (ver `insumosDeVersion()`), y es lo que hace la pantalla, porque
 * `GET /plan-compras/api/paquetes/sugerencias` pasa `null` cuando no viene el parámetro. El test
 * mide ahora el mismo camino que corre en caliente.
 *
 * Y si el proyecto no tiene NINGUNA versión importada, eso es un dato local ausente, no un rojo del
 * código: se salta con motivo, igual que ya hacía cuando no hay asignaciones sembradas.
 * ---------------------------------------------------------------------------------------------
 */

declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;
use App\Services\Pdc\MaestroInsumosService;

/** Techo de diferencias tolerado. Bájalo cada vez que cierres un grupo: es un trinquete. */
const BRECHA_MAXIMA = 7;

/**
 * Cuántas asignaciones curadas hacen falta para que un verde signifique algo.
 *
 * El techo de la brecha se cuenta en número de diferencias, así que un proyecto con cuatro
 * asignaciones da PASS por no tener casi nada que reproducir. No es motivo para fallar —la base de
 * quien corre esto no es asunto del test—, pero sí para decirlo en voz alta: un trinquete que se
 * queda verde por falta de datos deja de ser un trinquete.
 */
const CANON_MINIMO_UTIL = 50;

$PROYECTO = 73;
// Sin id fijo: la versión activa la resuelve el propio servicio (ver la nota de cabecera).
$VERSION = null;

$db = Database::getInstance();
$svc = new PaquetesService($db);

$hay = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?',
    [$PROYECTO],
)->fetchColumn();
if ($hay === 0) {
    fwrite(STDOUT, "SKIP: el proyecto {$PROYECTO} no está sembrado en esta base.\n");
    exit(0);
}

$r = $svc->proponerSembrado($PROYECTO, $VERSION, 'todos');
if ($r === null) {
    // Único caso que queda: el proyecto tiene asignaciones pero ningún presupuesto importado. Es un
    // dato local ausente, no un fallo del motor, y leerlo como rojo manda a diagnosticar código sano.
    fwrite(STDOUT, sprintf(
        "SKIP: el proyecto %d no tiene ninguna versión de presupuesto importada en esta base,\n"
        . "      así que no hay entrada sobre la que correr el motor. Importa el presupuesto de\n"
        . "      DAPORTO (Ensamble → Cargar presupuesto) y vuelve a medir.\n",
        $PROYECTO,
    ));
    exit(0);
}
$VERSION = (int) $r['version']['id']; // la que el servicio resolvió: el resto del test mide sobre ella

$clave = static fn(string $norm, string $unidad): string => $norm . '@@' . mb_strtoupper(trim($unidad));

$estado = [];
foreach ($db->query(
    'SELECT ip.descripcion_norm, ip.unidad, ip.omitido, p.nombre
     FROM pdc_insumo_paquete ip
     LEFT JOIN general_paquetes_contratacion p ON p.id = ip.paquete_id
     WHERE ip.project_id = ?',
    [$PROYECTO],
)->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $estado[$clave($a['descripcion_norm'], (string) $a['unidad'])] =
        (int) $a['omitido'] === 1 ? null : (string) $a['nombre'];
}

$valor = [];
foreach ($db->query(
    'SELECT descripcion, unidad, tipo_insumo, SUM(valor_total) v FROM pdc_presupuesto_apu_insumos
     WHERE project_id = ? AND version_id = ? GROUP BY descripcion, unidad, tipo_insumo',
    [$PROYECTO, $VERSION],
)->fetchAll(PDO::FETCH_ASSOC) as $x) {
    $k = $clave(MaestroInsumosService::normalizar((string) $x['descripcion']), (string) $x['unidad']);
    $valor[$k] = ['v' => (float) $x['v'], 'tr' => (string) $x['tipo_insumo']];
}

$coincide = 0;
$difs = [];
foreach ($r['propuestas'] as $p) {
    $k = $clave($p['descripcionNorm'], (string) $p['unidad']);
    if (!array_key_exists($k, $estado) || $estado[$k] === null) {
        continue; // sin destino canónico (omitido o pendiente): no hay nada que reproducir
    }
    $canon = $estado[$k];
    $motor = $p['propuesta']['paqueteNombre'] ?? null;
    if ($motor !== null && mb_strtoupper($motor) === mb_strtoupper($canon)) {
        $coincide++;
        continue;
    }
    $difs[] = [
        'd' => $p['descripcionNorm'], 'u' => $p['unidad'],
        'tr' => $valor[$k]['tr'] ?? '', 'v' => $valor[$k]['v'] ?? 0.0,
        'canon' => $canon, 'motor' => $motor ?? '(sin propuesta)',
        'ev' => mb_substr((string) ($p['propuesta']['evidencia'] ?? ''), 0, 110),
    ];
}
usort($difs, static fn($a, $b) => $b['v'] <=> $a['v']);

$conDestino = $coincide + count($difs);
$valorTotal = array_sum(array_column($valor, 'v'));
$valorDif = array_sum(array_column($difs, 'v'));

if (in_array('--detalle', $argv, true)) {
    foreach ($difs as $i => $x) {
        printf(
            "%2d. [%s] %-46s %14s\n      canon: %s\n      motor: %s\n            %s\n",
            $i + 1, mb_substr($x['tr'], 0, 4), mb_substr($x['d'], 0, 46),
            '$' . number_format($x['v'], 0, ',', '.'), $x['canon'], $x['motor'], $x['ev'],
        );
    }
}
if (in_array('--json', $argv, true)) {
    file_put_contents(__DIR__ . '/../brecha.json', json_encode($difs, JSON_UNESCAPED_UNICODE));
}

printf(
    "Versión %d (activa) · el motor reproduce %d de %d (%.1f%%) · difieren %d · valor en desacuerdo %s de %s (%.1f%%)\n",
    $VERSION,
    $coincide, $conDestino, 100 * $coincide / max(1, $conDestino), count($difs),
    number_format($valorDif, 0, ',', '.'), number_format($valorTotal, 0, ',', '.'),
    100 * $valorDif / max(1.0, $valorTotal),
);

// Un trinquete que se queda verde por falta de datos no protege nada: que lo diga, en vez de dejar
// que un PASS de cuatro insumos se lea como «el motor reproduce DAPORTO».
//
// Va por STDOUT y no por STDERR —a diferencia del aviso de residuo de más abajo— porque es parte del
// informe de la medición, no un error, y porque así queda pegado al resumen al que califica: bajo
// `docker compose exec` los dos flujos se entremezclan y un aviso a STDERR aparecía suelto.
if ($conDestino < CANON_MINIMO_UTIL) {
    fwrite(STDOUT, sprintf(
        "AVISO: solo %d insumo(s) con destino canónico (el mínimo para que la medida diga algo es %d).\n"
        . "       Esta base tiene el estado de DAPORTO a medio sembrar, así que un PASS aquí NO\n"
        . "       significa que el motor reproduzca el plan real: significa que hay poco que reproducir.\n",
        $conDestino,
        CANON_MINIMO_UTIL,
    ));
}

// Los paquetes del sandbox e2e viven en el catálogo GLOBAL (`general_paquetes_contratacion` no
// lleva project_id), y el motor aprende de lo asignado en otros proyectos. El seed los limpia al
// EMPEZAR cada test, así que la última corrida de Playwright deja residuo y esta medición se
// inflaba con un «motor: ZZTEST …» que no tiene nada que ver con las reglas. Costó un rato
// entender el falso positivo la primera vez; que lo diga el propio test.
$residuo = array_filter(
    $difs,
    static fn (array $x): bool => str_starts_with(mb_strtoupper((string) $x['motor']), 'ZZTEST')
        || str_starts_with(mb_strtoupper((string) $x['motor']), 'E2E '),
);
if ($residuo !== []) {
    fwrite(STDERR, sprintf(
        "AVISO: %d diferencia(s) apuntan a paquetes del sandbox e2e, no a las reglas.\n"
        . "       Limpia el residuo y vuelve a medir:\n"
        . "       docker compose exec -T app php database/seeds/pdc_e2e_sandbox_project.php\n",
        count($residuo),
    ));
}

if (count($difs) > BRECHA_MAXIMA) {
    fwrite(STDERR, sprintf("FAIL: %d diferencias, el techo es %d.\n", count($difs), BRECHA_MAXIMA));
    exit(1);
}
fwrite(STDOUT, sprintf("PASS: la brecha (%d) está dentro del techo (%d).\n", count($difs), BRECHA_MAXIMA));
exit(0);
