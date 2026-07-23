<?php

namespace App\Services\Pdc;

/**
 * Import del maestro SINCO: preview (parsear + guardar temporal) y confirmar
 * (upsert transaccional por codigo_sinco, con enriquecimiento de filas de A2).
 */
final class MaestroSincoImportService
{
    public function __construct(
        private readonly \Database $db,
        private readonly PresupuestoImportStore $store,
        private readonly MaestroSincoParser $parser,
    ) {
    }

    public function preview(string $rutaArchivo, string $nombre, string $usuario): array
    {
        $r = $this->parser->parse($rutaArchivo);
        if (!$r['valido']) {
            return ['ok' => false, 'errores' => $r['errores']];
        }
        $token = $this->store->guardar($rutaArchivo, ['nombre' => $nombre, 'usuario' => $usuario]);
        return ['ok' => true, 'importToken' => $token, 'resumen' => $r['resumen']];
    }

    public function confirmar(string $token): array
    {
        $ruta = $this->store->ruta($token);
        $meta = $this->store->meta($token);
        if ($ruta === null || $meta === null) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED'];
        }
        try {
            $r = $this->parser->parse($ruta);
        } catch (\RuntimeException) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        if (!$r['valido']) {
            $this->store->eliminar($token);
            return ['ok' => false, 'code' => 'INVALID_FILE'];
        }
        $usuario = (string) ($meta['usuario'] ?? '');

        $creados = 0; $actualizados = 0; $enriquecidos = 0; $conflictos = [];

        $this->db->beginTransaction();
        try {
            foreach ($r['insumos'] as $ins) {
                // tipo_insumo es varchar(100); el parser lo trae hasta 150 (espeja Agrupacion).
                $tipoInsumo = mb_substr($ins['tipoInsumo'], 0, 100);

                $porCodigo = $this->db->query(
                    'SELECT id FROM general_maestro_insumos WHERE codigo_sinco = ?',
                    [$ins['codigoSinco']],
                )->fetchColumn();

                if ($porCodigo !== false) {
                    $this->db->query(
                        'UPDATE general_maestro_insumos
                         SET descripcion = ?, descripcion_norm = ?, unidad = ?, tipo_insumo = ?, agrupacion = ?,
                             tipo_recurso = ?, valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                         WHERE id = ?',
                        [$ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $tipoInsumo, $ins['agrupacion'],
                         $ins['tipoRecurso'], $ins['valorUnitario'], $ins['iva'], $usuario, (int) $porCodigo],
                    );
                    $actualizados++;
                    continue;
                }

                // Sin match por código: ¿hay una fila con la misma norma+unidad?
                $huerfana = $this->db->query(
                    'SELECT id, codigo_sinco FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
                    [$ins['descripcionNorm'], $ins['unidad']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($huerfana !== false) {
                    if ($huerfana['codigo_sinco'] === null || $huerfana['codigo_sinco'] === '') {
                        $this->db->query(
                            'UPDATE general_maestro_insumos
                             SET codigo_sinco = ?, descripcion = ?, tipo_insumo = ?, agrupacion = ?, tipo_recurso = ?,
                                 valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                             WHERE id = ?',
                            [$ins['codigoSinco'], $ins['descripcion'], $tipoInsumo, $ins['agrupacion'], $ins['tipoRecurso'],
                             $ins['valorUnitario'], $ins['iva'], $usuario, (int) $huerfana['id']],
                        );
                        $enriquecidos++;
                    } else {
                        // Otro insumo SINCO ya ocupa esa norma+unidad: no pisar; reportar.
                        $conflictos[] = ['codigoSinco' => $ins['codigoSinco'], 'descripcion' => $ins['descripcion'], 'chocaCon' => $huerfana['codigo_sinco']];
                    }
                    continue;
                }

                $this->db->query(
                    'INSERT INTO general_maestro_insumos
                        (codigo_sinco, descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, valor_unitario, iva, activo, creado_por, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())',
                    [$ins['codigoSinco'], $ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $tipoInsumo,
                     $ins['agrupacion'], $ins['tipoRecurso'], $ins['valorUnitario'], $ins['iva'], $usuario],
                );
                $creados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        $this->store->eliminar($token);
        return ['ok' => true, 'creados' => $creados, 'actualizados' => $actualizados, 'enriquecidos' => $enriquecidos, 'conflictos' => $conflictos];
    }
}
