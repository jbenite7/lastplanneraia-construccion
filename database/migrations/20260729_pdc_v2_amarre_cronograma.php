<?php
/**
 * B1 · Llena `pdc_insumo_actividades.unique_id`, que nació NULL en A3.3 con la nota «lo llena A4»
 * y llegó a A4 sin llenarse: en DAPORTO estaban las 820 filas en NULL.
 *
 * Al medirlo se vio que el amarre 1:1 que esa nota daba por hecho no existe en los datos —de 820
 * filas, UNA casa por nombre con una actividad del cronograma— porque presupuesto y cronograma
 * hablan idiomas distintos y a distinta granularidad. El amarre real es por RAMA: el subcapítulo (o
 * el grupo) del presupuesto contra el frente del cronograma donde esa rama se construye. Todo el
 * razonamiento, el orden de resolución y el significado exacto de `unique_id` están en el encabezado
 * de `AmarreCronogramaService`; el mapa curado, en `database/seeds/sembrado_ramas_frentes.json`.
 *
 * Corre sobre TODOS los proyectos que tengan filas, no solo DAPORTO: la resolución es la misma
 * rutina que usa el motor en caliente, así que un proyecto sin cronograma o sin reglas aplicables
 * simplemente queda con sus filas en NULL y su motivo escrito, sin romper nada.
 *
 * Idempotente: solo escribe las filas cuyo amarre cambiaría. Una segunda corrida aplica 0.
 *
 * Uso:  php database/migrations/20260729_pdc_v2_amarre_cronograma.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\AmarreCronogramaService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n\n";

// ---------------------------------------------------------------------------------------------
// 1. DDL — la trazabilidad del amarre, con el mismo trío (origen / evidencia / semana) que A3.3
//    puso en `pdc_insumo_paquete` y A4 en `pdc_paquete_frente`. Sin esto un NULL no se distingue
//    de un «todavía no se calculó», que es exactamente el agujero que dejó A4.
// ---------------------------------------------------------------------------------------------
$columnas = $db->query(
    'SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    ['pdc_insumo_actividades'],
)->fetchAll(PDO::FETCH_COLUMN);

$faltan = array_diff(['origen_amarre', 'evidencia_amarre', 'semana_amarre'], $columnas);
if ($faltan === []) {
    echo "· Columnas de trazabilidad: ya existen\n";
} elseif (!$apply) {
    echo '· Columnas de trazabilidad: FALTAN (' . implode(', ', $faltan) . ") — se agregarán\n";
} else {
    $db->query(
        "ALTER TABLE pdc_insumo_actividades
           ADD COLUMN origen_amarre enum('override','exacta','tokens','sin_frente') NULL DEFAULT NULL
               COMMENT 'Cómo se resolvió unique_id (B1)',
           ADD COLUMN evidencia_amarre varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
           ADD COLUMN semana_amarre int NULL DEFAULT NULL
               COMMENT 'Semana del consolidado contra la que se resolvió'",
    );
    echo "· Columnas de trazabilidad: agregadas\n";
}
// En dry-run sin columnas no se puede simular la escritura, pero sí todo el cálculo.
$hayColumnas = $faltan === [] || $apply;

// ---------------------------------------------------------------------------------------------
// 2. Backfill por proyecto y versión.
// ---------------------------------------------------------------------------------------------
$svc = new AmarreCronogramaService($db);

$versiones = $db->query(
    'SELECT project_id, version_id, COUNT(*) filas FROM pdc_insumo_actividades
     GROUP BY project_id, version_id ORDER BY project_id, version_id',
)->fetchAll(PDO::FETCH_ASSOC);

if ($versiones === []) {
    echo "\nNo hay filas en pdc_insumo_actividades. Nada que hacer.\n";
    exit(0);
}

$totalEscritas = 0;
foreach ($versiones as $v) {
    $projectId = (int) $v['project_id'];
    $versionId = (int) $v['version_id'];

    $nulosAntes = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ? AND unique_id IS NULL',
        [$projectId, $versionId],
    )->fetchColumn();

    printf(
        "\n── proyecto %d · versión %d · %d filas (unique_id NULL antes: %d)\n",
        $projectId,
        $versionId,
        (int) $v['filas'],
        $nulosAntes,
    );

    // Misma rutina que usa el motor en caliente: el backfill no puede tener su propia lógica.
    $res = $svc->amarrarVersion($projectId, $versionId, $apply && $hayColumnas);
    if ($res['semana'] === null) {
        echo "   El proyecto no tiene semana activa en el consolidado: se deja todo en NULL.\n";
        continue;
    }
    echo "   Semana del consolidado: {$res['semana']}\n";

    echo "   Resolución:\n";
    foreach ($res['porOrigen'] as $origen => $d) {
        printf("     %-12s %4d filas   $%s\n", $origen, $d['filas'], number_format($d['valor'], 0));
    }

    if ($res['huerfanas'] !== []) {
        echo "   Sin frente (quedan en NULL, con motivo):\n";
        foreach ($res['huerfanas'] as $codigo => $d) {
            printf("     %-14s %3d filas  $%15s  %s\n", $codigo, $d['filas'], number_format($d['valor'], 0), $d['motivo']);
        }
    }

    printf("   Filas cuyo amarre cambia: %d\n", $res['cambios']);

    if (!$apply || !$hayColumnas) {
        continue;
    }
    $totalEscritas += $res['cambios'];

    $nulosDespues = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ? AND unique_id IS NULL',
        [$projectId, $versionId],
    )->fetchColumn();
    printf("   unique_id NULL: %d → %d\n", $nulosAntes, $nulosDespues);
}

echo "\n" . ($apply
    ? "[APLICADO] filas escritas: {$totalEscritas}\n"
    : "[DRY-RUN] nada se escribió. Ejecuta con --apply.\n");
exit(0);
