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

    public function confirmar(string $token, int $projectId): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null || (int) ($meta['projectId'] ?? 0) !== $projectId) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }

        try {
            $resultado = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$resultado['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('UPDATE pdc_presupuesto_versiones SET activa = 0 WHERE project_id = ? AND activa = 1', [$projectId]);
            $this->db->query(
                'INSERT INTO pdc_presupuesto_versiones
                    (project_id, version_label, archivo_nombre, archivo_hash, total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                [
                    $projectId,
                    (string) ($resultado['versionLabel'] ?? ''),
                    (string) ($meta['nombre'] ?? ''),
                    (string) ($meta['hash'] ?? ''),
                    $resultado['resumen']['actividades'],
                    $resultado['resumen']['insumos'],
                    $resultado['resumen']['costoTotal'],
                    (string) ($meta['usuario'] ?? ''),
                ],
            );
            $versionId = (int) $this->db->lastInsertId();

            $idPorCodigo = [];
            foreach ($resultado['items'] as $item) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_items
                        (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion, unidad, cantidad, id_apu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$projectId, $versionId, $item['codigo'], $item['codigo_padre'], $item['nivel'], $item['tipo_fila'], $item['descripcion'], $item['unidad'], $item['cantidad'], $item['id_apu']],
                );
                $idPorCodigo[$item['codigo']] = (int) $this->db->lastInsertId();
            }

            foreach ($resultado['insumos'] as $insumo) {
                $this->db->query(
                    'INSERT INTO pdc_presupuesto_apu_insumos
                        (project_id, version_id, item_id, descripcion, tipo_insumo, unidad, cant_apu, rendimiento, cantidad_total, valor_unitario, valor_total, iva)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $projectId, $versionId, $idPorCodigo[$insumo['codigo_actividad']],
                        $insumo['descripcion'], $insumo['tipo_insumo'], $insumo['unidad'],
                        $insumo['cant_apu'], $insumo['rendimiento'], $insumo['cantidad_total'],
                        $insumo['valor_unitario'], $insumo['valor_total'], $insumo['iva'],
                    ],
                );
            }

            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token); // un solo uso

        return [
            'ok' => true,
            'versionId' => $versionId,
            'versionLabel' => $resultado['versionLabel'],
            'resumen' => $resultado['resumen'],
        ];
    }

    public function versiones(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, version_label, archivo_nombre, total_actividades, total_insumos, costo_total, activa, importado_por, created_at
             FROM pdc_presupuesto_versiones WHERE project_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'versionLabel' => $r['version_label'],
            'archivoNombre' => $r['archivo_nombre'],
            'totalActividades' => (int) $r['total_actividades'],
            'totalInsumos' => (int) $r['total_insumos'],
            'costoTotal' => (float) $r['costo_total'],
            'activa' => (int) $r['activa'],
            'importadoPor' => $r['importado_por'],
            'createdAt' => $r['created_at'],
        ], $rows);
    }
}
