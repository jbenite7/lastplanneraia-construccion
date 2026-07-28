<?php
/**
 * A3.5 · Retira los cuatro paquetes cuyo alcance se fusionó con otro en la revisión en obra
 * del 2026-07-27. Ninguno tiene insumos asignados en ningún proyecto: su contenido se movió
 * al paquete que absorbe el alcance.
 *
 *   M. de O ENCHAPES CERÁMICOS              → M. de O INSTALACIÓN DE PISOS CERÁMICOS
 *     «Pisos y enchapes son el mismo contrato»: lo instala el mismo enchapador.
 *   M. de O TOPOGRAFÍA                      → Sum + Inst TOPOGRAFÍA
 *     «Unificar todo topografía en uno»: la comisión y el replanteo son el mismo topógrafo.
 *   Sum + Inst PUERTAS EN MADERA            → Suministro PUERTAS EN MADERA
 *     La puerta es producto de catálogo: se compra, no se contrata a todo costo.
 *   Sum + Inst IMPERMEABILIZACIÓN FOSO      → Sum + Inst IMPERMEABILIZACIONES
 *     El foso lo hace el mismo impermeabilizador que el resto de la obra.
 *
 * Retiro y no borrado: `pdc_insumo_paquete` puede referenciarlos en el histórico de otros
 * proyectos y las reglas del motor ya no los nombran. Reversible poniendo activo = 1.
 *
 * Uso:  php database/migrations/20260727_pdc_v2_retirar_paquetes_fusionados.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$fusionados = [
    'M. de O ENCHAPES CERÁMICOS' => 'M. de O INSTALACIÓN DE PISOS CERÁMICOS',
    'M. de O TOPOGRAFÍA' => 'Sum + Inst TOPOGRAFÍA',
    'Sum + Inst PUERTAS EN MADERA' => 'Suministro PUERTAS EN MADERA',
    'Sum + Inst IMPERMEABILIZACIÓN FOSO DE ASCENSOR' => 'Sum + Inst IMPERMEABILIZACIONES',
];

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n";
$retirados = 0;
$bloqueados = [];

foreach ($fusionados as $nombre => $destino) {
    $norm = MaestroInsumosService::normalizar($nombre);
    $paq = $db->query(
        'SELECT id, activo FROM general_paquetes_contratacion WHERE nombre_norm = ?',
        [$norm],
    )->fetch(PDO::FETCH_ASSOC);

    if ($paq === false) {
        echo "  · «{$nombre}»: no está en el catálogo, nada que hacer\n";
        continue;
    }
    if ((int) $paq['activo'] === 0) {
        echo "  · «{$nombre}»: ya estaba retirado\n";
        continue;
    }

    // Guardia: retirar un paquete con insumos vivos dejaría asignaciones apuntando a un destino
    // que la UI ya no ofrece. Si alguien lo usó, esta migración no lo toca.
    $enUso = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_insumo_paquete WHERE paquete_id = ? AND omitido = 0',
        [(int) $paq['id']],
    )->fetchColumn();
    if ($enUso > 0) {
        $bloqueados[] = "«{$nombre}» tiene {$enUso} insumo(s) asignado(s); muévelos a «{$destino}» antes de retirarlo";
        continue;
    }

    $destinoId = (int) ($db->query(
        'SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ? AND activo = 1',
        [MaestroInsumosService::normalizar($destino)],
    )->fetchColumn() ?: 0);
    if ($destinoId === 0) {
        $bloqueados[] = "«{$nombre}»: su destino «{$destino}» no existe activo en el catálogo";
        continue;
    }

    echo "  · «{$nombre}» (#{$paq['id']}) → absorbe «{$destino}» (#{$destinoId})\n";
    if ($apply) {
        $db->query('UPDATE general_paquetes_contratacion SET activo = 0 WHERE id = ?', [(int) $paq['id']]);
    }
    $retirados++;
}

foreach ($bloqueados as $b) {
    fwrite(STDERR, "  !! {$b}\n");
}

$activos = (int) $db->query('SELECT COUNT(*) FROM general_paquetes_contratacion WHERE activo = 1')->fetchColumn();
echo ($apply ? 'retirados: ' : 'a retirar: ') . $retirados . " · catálogo activo: {$activos}\n";
exit($bloqueados === [] ? 0 : 1);
