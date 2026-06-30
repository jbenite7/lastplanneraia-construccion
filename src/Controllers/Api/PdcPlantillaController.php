<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

class PdcPlantillaController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * GET /api/pdc/plantillas
     * Lista todas las plantillas activas
     */
    public function list(): void
    {
        $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar plantillas PDC.');

        try {
            $stmt = $this->db->queryWithProject(
                "SELECT p.*,
                    (SELECT COUNT(*) FROM general_pdc_plantilla_items WHERE plantilla_id = p.id) AS total_items
                 FROM general_pdc_plantillas p
                 WHERE p.activa = 1
                 ORDER BY p.tipo_obra, p.nombre"
            );
            $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => $plantillas,
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Error al consultar plantillas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pdc/plantillas/{id}
     * Detalle de una plantilla específica
     */
    public function show(string $id = ''): void
    {
        $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar plantillas PDC.');

        $id = (int) ($id ?: ($_GET['id'] ?? 0));

        if ($id <= 0) {
            $this->jsonError('ID de plantilla inválido.');
            return;
        }

        try {
            $stmt = $this->db->queryWithProject(
                "SELECT p.*,
                    (SELECT COUNT(*) FROM general_pdc_plantilla_items WHERE plantilla_id = p.id) AS total_items
                 FROM general_pdc_plantillas p
                 WHERE p.id = ?",
                [$id]
            );
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plantilla) {
                $this->jsonError('Plantilla no encontrada.', 404);
                return;
            }

            $this->json([
                'status' => 'success',
                'data' => $plantilla,
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Error al consultar plantilla: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pdc/plantillas/{id}/items
     * Items de una plantilla específica, agrupados por capítulo
     */
    public function items(string $id = ''): void
    {
        $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar plantillas PDC.');

        $id = (int) ($id ?: ($_GET['id'] ?? 0));

        if ($id <= 0) {
            $this->jsonError('ID de plantilla inválido.');
            return;
        }

        try {
            $stmt = $this->db->queryWithProject(
                "SELECT i.*, cr.nombre AS categoria_nombre
                 FROM general_pdc_plantilla_items i
                 LEFT JOIN general_pdc_categoria_recurso cr ON cr.nombre = i.tipo_paquete OR cr.nombre = i.capitulo
                 WHERE i.plantilla_id = ?
                 ORDER BY i.orden ASC, i.capitulo, i.actividad",
                [$id]
            );
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as &$item) {
                $item['tipo_paquete'] = $this->tipoPaqueteLabel($item['tipo_paquete'] ?? '');
                $item['actividad'] = $this->expandVisibleAbbreviations($item['actividad'] ?? '');
                $item['paquete_sugerido'] = $this->expandVisibleAbbreviations($item['paquete_sugerido'] ?? '');
            }
            unset($item);

            // Agrupar por capítulo
            $agrupados = [];
            foreach ($items as $item) {
                $cap = $item['capitulo'] ?? 'Sin capítulo';
                if (!isset($agrupados[$cap])) {
                    $agrupados[$cap] = [];
                }
                $agrupados[$cap][] = $item;
            }

            $this->json([
                'status' => 'success',
                'data' => $items,
                'agrupados' => $agrupados,
                'total' => count($items),
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Error al consultar items: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pdc/categorias-recurso
     * Lista las categorías de recurso disponibles
     */
    public function categorias(): void
    {
        $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar categorías.');

        try {
            $stmt = $this->db->queryWithProject(
                "SELECT * FROM general_pdc_categoria_recurso ORDER BY orden ASC"
            );
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => $categorias,
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Error al consultar categorías: ' . $e->getMessage(), 500);
        }
    }

    // ─── HELPERS ───────────────────────────────────────────────────────

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        $this->json([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    private function tipoPaqueteLabel(string $value): string
    {
        return match ($value) {
            'SI' => 'Suministro e Instalación',
            'S' => 'Suministro',
            'MO' => 'Mano de Obra',
            'MO+S' => 'Mano de Obra y Suministro por separado',
            default => $value,
        };
    }

    private function expandVisibleAbbreviations(string $value): string
    {
        $value = preg_replace('/\bMO\b/u', 'Mano de Obra', $value);
        return is_string($value) ? $value : '';
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }
}
