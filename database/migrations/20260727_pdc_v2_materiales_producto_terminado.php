<?php
/**
 * A3.5 · Abre `admite_materiales` en cinco paquetes «a todo costo» que sí compran producto
 * terminado, y corrige el bloque de concreto que quedó fuera de su suministro.
 *
 * El bloqueo de materiales en paquetes a todo costo (A3.3) evita el doble conteo: si el
 * contratista pone el material, listarlo además como insumo asignado lo cuenta dos veces. Pero
 * hay alcances donde el material ES el producto que se compra — el pasamanos, la teja, la placa
 * de nomenclatura — y ahí el bloqueo dejaba seis insumos ($122M) sin destino posible.
 *
 * Impacto medido antes de aplicar (simulación sobre DAPORTO v292): entran exactamente los ocho
 * materiales que el estado canónico ya tenía en esos paquetes, ni uno más. Hidrosanitarias no
 * abre la puerta a la red: solo a la gárgola y a las tuberías de filtro.
 *
 * Uso:  php database/migrations/20260727_pdc_v2_materiales_producto_terminado.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

/** Paquetes cuyo alcance a todo costo incluye comprar el producto terminado. */
$abrir = [
    'Sum + Inst BARANDAS Y PASAMANOS' => 'el pasamanos metálico es el producto que se compra',
    'Sum + Inst CUBIERTAS METÁLICAS' => 'la cubierta liviana es la teja misma',
    'Sum + Inst ANCLAJES' => 'la línea de vida es un anclaje certificado de catálogo',
    'Sum + Inst SEÑALIZACIÓN' => 'las placas de nomenclatura son producto terminado',
    'Sum + Inst INSTALACIONES HIDROSANITARIAS' => 'gárgolas y tubería de filtro las suministra el mismo instalador',
];

/** El bloque de 10x20x40 ya se movió en la revisión; el de 15x20x40 quedó atrás. */
$mover = [
    'BLOQUE DE CONCRETO DE 15 X 20 X 40' => ['UN', 'Suministro BLOQUE DE CONCRETO'],
];

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n";
$errores = [];

echo "\n— admite_materiales\n";
$abiertos = 0;
foreach ($abrir as $nombre => $porque) {
    $paq = $db->query(
        'SELECT id, admite_materiales FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1',
        [MaestroInsumosService::normalizar($nombre)],
    )->fetch(PDO::FETCH_ASSOC);
    if ($paq === false) { $errores[] = "«{$nombre}» no está activo en el catálogo"; continue; }
    if ((int) $paq['admite_materiales'] === 1) { echo "  · «{$nombre}»: ya estaba abierto\n"; continue; }
    echo "  · «{$nombre}» — {$porque}\n";
    if ($apply) {
        $db->query('UPDATE general_paquetes_contratacion SET admite_materiales = 1 WHERE id = ?', [(int) $paq['id']]);
    }
    $abiertos++;
}

echo "\n— reubicaciones\n";
$svc = new PaquetesService($db);
$P = 73;
$movidos = 0;
foreach ($mover as $desc => [$unidad, $destino]) {
    $norm = MaestroInsumosService::normalizar($desc);
    $destinoId = (int) ($db->query(
        'SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1',
        [MaestroInsumosService::normalizar($destino)],
    )->fetchColumn() ?: 0);
    if ($destinoId === 0) { $errores[] = "destino «{$destino}» no existe activo"; continue; }

    $actual = $db->query(
        'SELECT ip.paquete_id, p.nombre FROM pdc_insumo_paquete ip
         LEFT JOIN general_paquetes_contratacion p ON p.id = ip.paquete_id
         WHERE ip.project_id = ? AND ip.descripcion_norm = ? AND ip.unidad = ?',
        [$P, $norm, $unidad],
    )->fetch(PDO::FETCH_ASSOC);
    if ($actual === false) { echo "  · «{$desc}»: no está asignado en el proyecto {$P}\n"; continue; }
    if ((int) $actual['paquete_id'] === $destinoId) { echo "  · «{$desc}»: ya está en destino\n"; continue; }

    echo "  · «{$desc}»: {$actual['nombre']} → {$destino}\n";
    if ($apply) {
        $svc->asignar($P, [['descripcionNorm' => $norm, 'unidad' => $unidad]], $destinoId, 'canonico-a35');
        $real = (int) $db->query(
            'SELECT paquete_id FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = ? AND unidad = ?',
            [$P, $norm, $unidad],
        )->fetchColumn();
        if ($real !== $destinoId) { $errores[] = "«{$desc}» quedó en #{$real}, esperaba #{$destinoId}"; continue; }
    }
    $movidos++;
}

foreach ($errores as $e) { fwrite(STDERR, "  !! {$e}\n"); }
$total = (int) $db->query('SELECT COUNT(*) FROM general_paquetes_contratacion WHERE admite_materiales = 1')->fetchColumn();
echo "\n" . ($apply ? 'abiertos: ' : 'a abrir: ') . $abiertos . ' · '
   . ($apply ? 'movidos: ' : 'a mover: ') . $movidos
   . " · paquetes que admiten material: {$total}\n";
exit($errores === [] ? 0 : 1);
