<?php
/**
 * A4 · Task 5 — hereda la `duracion_ref` de los paquetes que salieron de partir uno «a todo costo»
 * en dos: suministro por un lado, mano de obra por otro. El pariente («Sum + Inst X») ya tenía el
 * plazo del proceso de contratación; el hijo («Suministro X» / «M. de O X») nació sin él porque el
 * catálogo `general_dias_procesos_contratacion` nunca conoció ese nombre nuevo.
 *
 * Herencia por nombre exacto (normalizado), no por heurística: la lista sale de revisar en obra
 * cuáles paquetes de DAPORTO (project_id = 73) son partición de cuáles. Si el pariente no existe
 * activo, o tampoco tiene `duracion_ref`, el hijo queda sin tocar y se reporta al final junto con su
 * cuantía en DAPORTO — es el hueco que seguirá cubriéndose con la mediana provisional.
 *
 * Uso:  php database/migrations/20260728_pdc_v2_duraciones_faltantes.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;

const PDC_PROJECT_ID_DAPORTO = 73;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$herencias = [
    'Suministro PUERTAS EN MADERA' => 'Sum + Inst CARPINTERÍA DE MADERA',
    'M. de O CARPINTERÍA DE MADERA' => 'Sum + Inst CARPINTERÍA DE MADERA',
    'Suministro PUERTAS METÁLICAS' => 'Sum + Inst CARPINTERÍA METÁLICA',
    'M. de O PUERTAS METÁLICAS' => 'Sum + Inst CARPINTERÍA METÁLICA',
    'Suministro PUERTAS CORTAFUEGO' => 'Sum + Inst PUERTAS CORTAFUEGO',
    'M. de O PUERTAS CORTAFUEGO' => 'Sum + Inst PUERTAS CORTAFUEGO',
    'Suministro ANCLAJES' => 'Sum + Inst ANCLAJES',
    'Suministro DOTACIÓN COCINAS Y LAVADEROS' => 'Sum + Inst DOTACIÓN COCINAS Y LAVADEROS',
    'M. de O TOPELLANTAS' => 'Sum + Inst TOPELLANTAS',
    'M. de O INSTALACIÓN DE PISOS CERÁMICOS' => 'Sum + Inst PISOS Y ENCHAPES CERÁMICOS/PORCELANATO',
    'Suministro PISOS Y ENCHAPES CERÁMICOS/PORCELANATO' => 'Sum + Inst PISOS Y ENCHAPES CERÁMICOS/PORCELANATO',
    'Suministro APARATOS SANITARIOS Y GRIFERÍA' => 'Sum + Inst APARATOS SANITARIOS Y GRIFERÍA',
    'M. de O APARATOS SANITARIOS Y GRIFERÍA' => 'Sum + Inst APARATOS SANITARIOS Y GRIFERÍA',
];

/** Cuantía del paquete en DAPORTO (versión activa), para dimensionar lo que queda sin plazo. */
function valorEnDaporto(Database $db, int $paqueteId): float
{
    return (float) ($db->query(
        'SELECT COALESCE(SUM(v.valor_total), 0)
         FROM pdc_insumo_paquete a
         JOIN pdc_insumo_vinculos v
                ON v.project_id = a.project_id AND v.descripcion_norm = a.descripcion_norm AND v.unidad = a.unidad
         JOIN pdc_presupuesto_versiones ver ON ver.id = v.version_id AND ver.activa = 1
         WHERE a.project_id = ? AND a.paquete_id = ? AND a.omitido = 0',
        [PDC_PROJECT_ID_DAPORTO, $paqueteId],
    )->fetchColumn() ?: 0);
}

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n";
$heredados = 0;
$sinResolver = [];

foreach ($herencias as $nombre => $pariente) {
    $paq = $db->query(
        'SELECT id, nombre, duracion_ref FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1',
        [MaestroInsumosService::normalizar($nombre)],
    )->fetch(PDO::FETCH_ASSOC);

    if ($paq === false) {
        echo "  · «{$nombre}»: no está en el catálogo activo, nada que hacer\n";
        continue;
    }
    if ($paq['duracion_ref'] !== null) {
        echo "  · «{$nombre}» (#{$paq['id']}): ya tiene duracion_ref={$paq['duracion_ref']}, no se toca\n";
        continue;
    }

    $padre = $db->query(
        'SELECT id, duracion_ref FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1',
        [MaestroInsumosService::normalizar($pariente)],
    )->fetch(PDO::FETCH_ASSOC);

    if ($padre === false) {
        $sinResolver[] = ['id' => (int) $paq['id'], 'nombre' => $nombre, 'motivo' => "pariente «{$pariente}» no existe activo"];
        continue;
    }
    if ($padre['duracion_ref'] === null) {
        $sinResolver[] = ['id' => (int) $paq['id'], 'nombre' => $nombre, 'motivo' => "pariente «{$pariente}» (#{$padre['id']}) tampoco tiene duracion_ref"];
        continue;
    }

    echo "  · «{$nombre}» (#{$paq['id']}) ← duracion_ref={$padre['duracion_ref']} de «{$pariente}» (#{$padre['id']})\n";
    if ($apply) {
        $db->query(
            'UPDATE general_paquetes_contratacion SET duracion_ref = ? WHERE id = ? AND duracion_ref IS NULL',
            [(int) $padre['duracion_ref'], (int) $paq['id']],
        );
    }
    $heredados++;
}

echo ($apply ? 'heredados: ' : 'a heredar: ') . $heredados . "\n";

if ($sinResolver !== []) {
    echo "\n-- siguen sin duracion_ref (quedan con mediana provisional) --\n";
    foreach ($sinResolver as $s) {
        $valor = valorEnDaporto($db, $s['id']);
        echo sprintf("  !! «%s» (#%d): %s · valor en DAPORTO: $%s\n", $s['nombre'], $s['id'], $s['motivo'], number_format($valor, 0, ',', '.'));
    }
}

exit(0);
