<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
use PDO;

class PgBreadcrumbController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function standardize(): void
    {
        try {
            $this->requirePermission('lps.programa_general.editar', 'No autorizado para estandarizar el programa general.');

            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            $tabla = "{$dbPrefix}_programa_consolidado";
            if (!$this->tableExists($tabla)) {
                $this->jsonError('No existe el programa general del proyecto.', 404);
                return;
            }

            if ($semana <= 0) {
                $semana = $this->resolveMaxSemana($tabla);
            }
            if ($semana <= 0) {
                $this->jsonError('No hay semanas disponibles en el programa general.', 404);
                return;
            }

            $stmt = $this->db->query(
                "SELECT Consecutivo, Consecutivo_en_Programa, Id, Actividad, Titulo
                 FROM `{$tabla}`
                 WHERE Semana = :semana
                 ORDER BY Consecutivo_en_Programa",
                [':semana' => $semana]
            );
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                $this->jsonError('No hay actividades en la semana seleccionada.', 404);
                return;
            }

            $chapterMap = [];
            foreach ($rows as $row) {
                if ((int) $row['Titulo'] === 1) {
                    $chapterMap[(string) $row['Id']] = $this->stripHtml((string) $row['Actividad']);
                }
            }

            $actualizados = 0;
            $yaTenian = 0;
            $sinId = 0;
            $errores = [];

            foreach ($rows as $row) {
                $id = trim((string) ($row['Id'] ?? ''));
                $actividadRaw = (string) ($row['Actividad'] ?? '');

                if ($id === '' || strpos($id, '.') === false) {
                    $sinId++;
                    continue;
                }

                $fullBreadcrumb = $this->buildFullBreadcrumb($id, $chapterMap);
                if ($fullBreadcrumb === '') {
                    $sinId++;
                    continue;
                }

                $cleanName = $this->extractCleanName($actividadRaw);
                if ($cleanName === '') {
                    continue;
                }

                if ($this->hasFullBreadcrumb($actividadRaw, $fullBreadcrumb)) {
                    $yaTenian++;
                    continue;
                }

                $newActividad = '<b>' . htmlspecialchars($cleanName) . ', </b> <small>[Capítulo:' . htmlspecialchars($fullBreadcrumb) . ']</small>';

                try {
                    $this->db->query(
                        "UPDATE `{$tabla}` SET Actividad = :actividad WHERE Consecutivo = :id",
                        [':actividad' => $newActividad, ':id' => $row['Consecutivo']]
                    );
                    $actualizados++;
                } catch (\Throwable $e) {
                    $errores[] = "Consecutivo {$row['Consecutivo']}: " . $e->getMessage();
                }
            }

            $this->db->logActivity('ProgramaGeneral', 'ESTANDARIZAR_BREADCRUMB', "Estandarización breadcrumb en {$dbPrefix} semana {$semana}: {$actualizados} actualizados, {$yaTenian} ya tenían, {$sinId} sin jerarquía", $dbPrefix);

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'actualizados' => $actualizados,
                'yaTenian' => $yaTenian,
                'sinId' => $sinId,
                'errores' => $errores,
                'semana' => $semana,
                'sugerencia' => $sinId > 0
                    ? "Hay {$sinId} actividades sin jerarquía (IDs sin puntos). El Auto-Generar PDC creará actividades vinculadas automáticamente."
                    : null,
            ]);

        } catch (\Throwable $e) {
            $this->jsonError('Error interno: ' . $e->getMessage(), 500);
        }
    }

    public function preview(): void
    {
        try {
            $this->requirePermission('lps.programa_general.editar', 'No autorizado.');

            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            $tabla = "{$dbPrefix}_programa_consolidado";
            if (!$this->tableExists($tabla)) {
                $this->jsonError('No existe el programa general del proyecto.', 404);
                return;
            }

            if ($semana <= 0) {
                $semana = $this->resolveMaxSemana($tabla);
            }

            $stmt = $this->db->query(
                "SELECT Consecutivo, Consecutivo_en_Programa, Id, Actividad, Titulo
                 FROM `{$tabla}`
                 WHERE Semana = :semana
                 ORDER BY Consecutivo_en_Programa",
                [':semana' => $semana]
            );
            $rows = $stmt->fetchAll();

            $chapterMap = [];
            foreach ($rows as $row) {
                if ((int) $row['Titulo'] === 1) {
                    $chapterMap[(string) $row['Id']] = $this->stripHtml((string) $row['Actividad']);
                }
            }

            $cambios = [];
            $sinCambios = 0;

            foreach ($rows as $row) {
                $id = trim((string) ($row['Id'] ?? ''));
                $actividadRaw = (string) ($row['Actividad'] ?? '');

                if ($id === '' || strpos($id, '.') === false) {
                    continue;
                }

                $fullBreadcrumb = $this->buildFullBreadcrumb($id, $chapterMap);
                if ($fullBreadcrumb === '') {
                    continue;
                }

                $cleanName = $this->extractCleanName($actividadRaw);
                if ($cleanName === '') {
                    continue;
                }

                if ($this->hasFullBreadcrumb($actividadRaw, $fullBreadcrumb)) {
                    $sinCambios++;
                    continue;
                }

                $cambios[] = [
                    'consecutivo' => $row['Consecutivo'],
                    'consecutivoEnPrograma' => (int) $row['Consecutivo_en_Programa'],
                    'nombreActual' => $cleanName,
                    'breadcrumbActual' => $this->extractExistingBreadcrumb($actividadRaw),
                    'breadcrumbCompleto' => $fullBreadcrumb,
                ];
            }

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'semana' => $semana,
                'totalActividades' => count($rows),
                'cambiosNecesarios' => count($cambios),
                'sinCambios' => $sinCambios,
                'cambios' => array_slice($cambios, 0, 100),
            ]);

        } catch (\Throwable $e) {
            $this->jsonError('Error interno: ' . $e->getMessage(), 500);
        }
    }

    private function buildFullBreadcrumb(string $id, array $chapterMap): string
    {
        $parts = explode('.', $id);
        $levels = [];
        $current = '';

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $current = $current === '' ? $parts[$i] : $current . '.' . $parts[$i];
            if (isset($chapterMap[$current])) {
                $levels[] = $chapterMap[$current];
            }
        }

        return implode(', ', array_reverse($levels));
    }

    private function extractCleanName(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $pos = mb_strpos($text, '[Capítulo');
        if ($pos !== false) {
            $text = mb_substr($text, 0, $pos);
        }
        $text = rtrim(trim($text), ',');
        return trim($text);
    }

    private function extractExistingBreadcrumb(string $html): string
    {
        if (preg_match('/\[Capítulo[:\s]*([^\]]+)\]/ui', $html, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function hasFullBreadcrumb(string $html, string $fullBreadcrumb): bool
    {
        $existing = $this->extractExistingBreadcrumb($html);
        if ($existing === '') {
            return false;
        }
        $existingNorm = mb_strtoupper(str_replace([' ', ', '], ['', ','], $existing));
        $fullNorm = mb_strtoupper(str_replace([' ', ', '], ['', ','], $fullBreadcrumb));
        return $existingNorm === $fullNorm;
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $pos = mb_strpos($text, '[Capítulo');
        if ($pos !== false) {
            $text = mb_substr($text, 0, $pos);
        }
        return trim(rtrim(trim($text), ','));
    }

    private function resolveMaxSemana(string $tabla): int
    {
        $stmt = $this->db->query("SELECT MAX(Semana) as max_sem FROM `{$tabla}`");
        $row = $stmt->fetch();
        return (int) ($row['max_sem'] ?? 0);
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t",
                [':t' => $table]
            );
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        $this->jsonResponse([
            'respuesta' => 'ERROR',
            'mensaje' => $message,
        ]);
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }
}
