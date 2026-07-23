<?php

namespace App\Services\Pdc;

/**
 * Maestro global de insumos (Fase A2): consolidación de insumos únicos por
 * versión de presupuesto, matching exacto contra el catálogo general_maestro_insumos
 * y gestión de la cola de vínculos (auto / confirmado / pendiente).
 */
final class MaestroInsumosService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /** Normalización canónica de descripciones (idéntica base del parser + espacios colapsados). */
    public static function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        $s = strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'Ü' => 'U']);
        return preg_replace('/\s+/', ' ', $s) ?? $s;
    }

    /** Resuelve la versión (activa por defecto) del proyecto, o null. */
    private function versionDe(int $projectId, ?int $versionId): ?array
    {
        $sql = $versionId === null
            ? 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1'
            : 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?';
        $params = $versionId === null ? [$projectId] : [$projectId, $versionId];
        $row = $this->db->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function generarVinculos(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];

        // 1) Consolidar insumos únicos de la versión.
        $consolidados = $this->db->query(
            'SELECT descripcion, tipo_insumo, unidad,
                    SUM(cantidad_total) AS cantidad_total, SUM(valor_total) AS valor_total, COUNT(*) AS apariciones
             FROM pdc_presupuesto_apu_insumos
             WHERE project_id = ? AND version_id = ?
             GROUP BY descripcion, unidad, tipo_insumo',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Re-agrupar por (norm, unidad): descripciones distintas pueden normalizar igual.
        $porClave = [];
        foreach ($consolidados as $c) {
            $norm = self::normalizar((string) $c['descripcion']);
            $clave = $norm . '|' . $c['unidad'];
            if (!isset($porClave[$clave])) {
                $porClave[$clave] = [
                    'norm' => $norm,
                    'unidad' => (string) $c['unidad'],
                    'original' => (string) $c['descripcion'],
                    'tipo' => (string) $c['tipo_insumo'],
                    'cantidad' => 0.0,
                    'valor' => 0.0,
                    'apariciones' => 0,
                ];
            }
            $porClave[$clave]['cantidad'] += (float) $c['cantidad_total'];
            $porClave[$clave]['valor'] += (float) $c['valor_total'];
            $porClave[$clave]['apariciones'] += (int) $c['apariciones'];
        }

        // 2) Upsert de vínculos sin pisar decisiones humanas ni des-vincular.
        foreach ($porClave as $u) {
            $this->db->query(
                'INSERT INTO pdc_insumo_vinculos
                    (project_id, version_id, descripcion_norm, unidad, descripcion_original, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'pendiente\')
                 ON DUPLICATE KEY UPDATE
                    descripcion_original = VALUES(descripcion_original),
                    tipo_insumo = VALUES(tipo_insumo),
                    cantidad_total = VALUES(cantidad_total),
                    valor_total = VALUES(valor_total),
                    apariciones = VALUES(apariciones)',
                [$projectId, $vid, $u['norm'], $u['unidad'], mb_substr($u['original'], 0, 500), $u['tipo'], round($u['cantidad'], 4), round($u['valor'], 2), $u['apariciones']],
            );
        }

        // 3) Auto-match exacto de los pendientes contra el maestro activo.
        $this->db->query(
            'UPDATE pdc_insumo_vinculos v
             JOIN general_maestro_insumos m
               ON m.descripcion_norm = v.descripcion_norm AND m.unidad = v.unidad AND m.activo = 1
             SET v.maestro_id = m.id, v.estado = \'auto\'
             WHERE v.project_id = ? AND v.version_id = ? AND v.estado = \'pendiente\'',
            [$projectId, $vid],
        );

        return $this->resumen($projectId, $vid) + ['versionId' => $vid];
    }

    public function vinculos(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $rows = $this->db->query(
            'SELECT v.id, v.descripcion_original, v.descripcion_norm, v.unidad, v.tipo_insumo,
                    v.cantidad_total, v.valor_total, v.apariciones, v.maestro_id, v.estado,
                    m.descripcion AS maestro_descripcion
             FROM pdc_insumo_vinculos v
             LEFT JOIN general_maestro_insumos m ON m.id = v.maestro_id
             WHERE v.project_id = ? AND v.version_id = ?
             ORDER BY (v.estado = \'pendiente\') DESC, v.valor_total DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'version' => ['id' => $vid, 'versionLabel' => $version['version_label'], 'activa' => (int) $version['activa']],
            'resumen' => $this->resumen($projectId, $vid),
            'vinculos' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'descripcionOriginal' => $r['descripcion_original'],
                'descripcionNorm' => $r['descripcion_norm'],
                'unidad' => $r['unidad'],
                'tipoInsumo' => $r['tipo_insumo'],
                'cantidadTotal' => (float) $r['cantidad_total'],
                'valorTotal' => (float) $r['valor_total'],
                'apariciones' => (int) $r['apariciones'],
                'maestroId' => $r['maestro_id'] === null ? null : (int) $r['maestro_id'],
                'maestroDescripcion' => $r['maestro_descripcion'],
                'estado' => $r['estado'],
            ], $rows),
        ];
    }

    private function resumen(int $projectId, int $vid): array
    {
        $r = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'auto') AS auto,
                    SUM(estado = 'confirmado') AS confirmados,
                    SUM(estado = 'pendiente') AS pendientes
             FROM pdc_insumo_vinculos WHERE project_id = ? AND version_id = ?",
            [$projectId, $vid],
        )->fetch(\PDO::FETCH_ASSOC);
        $total = (int) $r['total'];
        $vinculados = (int) $r['auto'] + (int) $r['confirmados'];
        return [
            'total' => $total,
            'auto' => (int) $r['auto'],
            'confirmados' => (int) $r['confirmados'],
            'pendientes' => (int) $r['pendientes'],
            'cobertura' => $total === 0 ? 100.0 : round($vinculados * 100 / $total, 1),
        ];
    }
}
