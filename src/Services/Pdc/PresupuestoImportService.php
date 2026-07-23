<?php

namespace App\Services\Pdc;

/**
 * Orquesta el import del presupuesto: preview (parsear + guardar temporal)
 * y confirmación transaccional (Task 5).
 */
final class PresupuestoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly PresupuestoExcelParser $parser,
    ) {
    }

    public function previewDesdeArchivo(string $rutaArchivo, string $nombreOriginal, int $projectId, string $usuario): array
    {
        $resultado = $this->parser->parse($rutaArchivo);
        if (!$resultado['valido']) {
            return ['ok' => false, 'errores' => $resultado['errores']];
        }

        $hash = hash_file('sha256', $rutaArchivo);
        $advertencias = [];
        $repetida = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ? AND archivo_hash = ?',
            [$projectId, $hash],
        )->fetchColumn();
        if ($repetida > 0) {
            $advertencias[] = 'Este archivo ya fue importado antes en este proyecto (contenido idéntico).';
        }

        $token = $this->store->guardar($rutaArchivo, [
            'nombre' => $nombreOriginal,
            'hash' => $hash,
            'projectId' => $projectId,
            'usuario' => $usuario,
        ]);

        return [
            'ok' => true,
            'importToken' => $token,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
            'advertencias' => $advertencias,
        ];
    }
}
