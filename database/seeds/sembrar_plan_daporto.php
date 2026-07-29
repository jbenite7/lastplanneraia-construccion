<?php
/**
 * Deja a DAPORTO (project_id = 73) con plan de compras calculado.
 *
 * El amarre de ramas y el cálculo del plan se hicieron en su día contra el stack retirado
 * (`lps-aia-pdc`, puerto 3308), que tenía su propia base. El código viajó con el merge; los datos no.
 * Este script los reconstruye con las MISMAS rutinas del producto —nada de INSERT a mano—, así que
 * lo que quede sembrado es exactamente lo que la aplicación produciría.
 *
 * Uso:  php database/seeds/sembrar_plan_daporto.php            (dry-run: solo informa)
 *       php database/seeds/sembrar_plan_daporto.php --apply    (escribe)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\AmarreCronogramaService;
use App\Services\Pdc\PlanFechasService;

const PROYECTO = 73;
const USUARIO = 'seed-b1';

$aplicar = in_array('--apply', $argv, true);
$db = Database::getInstance();

$version = $db->query(
    'SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1',
    [PROYECTO],
)->fetchColumn();
if ($version === false) {
    fwrite(STDERR, "ABORTADO: DAPORTO no tiene version de presupuesto activa.\n");
    exit(1);
}
$version = (int) $version;
echo 'Version activa: ', $version, $aplicar ? " (APLICANDO)\n" : " (DRY-RUN)\n";

// (1) Ramas del presupuesto -> frentes del cronograma. Idempotente por diseño (ver A4.2).
$amarre = new AmarreCronogramaService($db);
$rama = $amarre->amarrarVersion(PROYECTO, $version, $aplicar);
echo 'Ramas amarradas: ', json_encode($rama, JSON_UNESCAPED_UNICODE), "\n";

// (2) Paquetes -> frente. Se toma la propuesta del motor tal cual: es la misma que vería quien
// abriera la pantalla del Plan y pulsara «amarrar» en cada fila.
$svc = new PlanFechasService($db);
$sugerencias = $svc->sugerirFrentes(PROYECTO, $version);
echo 'Paquetes con propuesta de frente: ', count($sugerencias), "\n";

$amarrados = 0;
foreach ($sugerencias as $paqueteId => $s) {
    if (!$aplicar) {
        $amarrados++;
        continue;
    }
    $r = $svc->amarrar(PROYECTO, (int) $paqueteId, (int) $s['uniqueId'], USUARIO, [
        'origen' => 'sugerencia',
        'evidencia' => $s['evidencia'] ?? '',
    ]);
    if (($r['ok'] ?? false) === true) {
        $amarrados++;
    } else {
        echo '  paquete ', $paqueteId, ' NO amarrado: ', json_encode($r, JSON_UNESCAPED_UNICODE), "\n";
    }
}
echo 'Paquetes amarrados: ', $amarrados, "\n";

// (3) El cálculo del plan. En dry-run no se corre: sin amarres no tendria nada que calcular.
if ($aplicar) {
    $calc = $svc->calcular(PROYECTO, USUARIO);
    echo 'Calculo: ', json_encode($calc, JSON_UNESCAPED_UNICODE), "\n";

    $pasos = (int) $db->query('SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ?', [PROYECTO])->fetchColumn();
    echo 'Filas de paso resultantes: ', $pasos, "\n";
    if ($pasos === 0) {
        fwrite(STDERR, "ABORTADO: el calculo no dejo ninguna fila de paso.\n");
        exit(1);
    }
}

echo $aplicar ? "OK\n" : "Dry-run terminado. Repite con --apply para escribir.\n";
