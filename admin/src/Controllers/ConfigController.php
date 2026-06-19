<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Database;

class ConfigController extends AdminController
{
    /**
     * GET /admin/matching/config — Show matching threshold configuration form.
     */
    public function index(): void
    {
        $db = Database::getInstance();
        $thresholds = ['high_threshold' => 0.90, 'medium_threshold' => 0.70, 'chapter_threshold' => 0.70];

        try {
            $stmt = $db->query("SELECT config_key, config_value FROM general_matching_config");
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            if (!empty($rows)) {
                foreach ($thresholds as $key => &$val) {
                    if (isset($rows[$key])) {
                        $val = (float) $rows[$key];
                    }
                }
                unset($val);
            }
        } catch (\Exception $e) {
            // Table may not exist yet — use defaults
            error_log('[ConfigController] Error reading matching_config: ' . $e->getMessage());
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('matching/config', [
            'title' => 'Configuración de Matching',
            'pageTitle' => 'Matching Config',
            'breadcrumb' => 'Matching Config',
            'thresholds' => $thresholds,
            'csrf_token' => Security::generateCsrfToken(),
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ]);
    }

    /**
     * POST /admin/matching/config — Update matching thresholds.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $keys = ['high_threshold', 'medium_threshold', 'chapter_threshold'];
        $values = [];
        $errors = [];

        foreach ($keys as $key) {
            $raw = trim($_POST[$key] ?? '');
            if ($raw === '') {
                $errors[$key] = 'El valor es obligatorio.';
                continue;
            }

            if (!is_numeric($raw)) {
                $errors[$key] = 'Debe ser un número.';
                continue;
            }

            $val = (float) $raw;

            if ($val < 0.00 || $val > 1.00) {
                $errors[$key] = 'El valor debe estar entre 0.00 y 1.00.';
                continue;
            }

            // Validate step of 0.05
            $rounded = round($val * 20) / 20;
            if (abs($val - $rounded) > 0.0001) {
                $errors[$key] = 'El valor debe ser múltiplo de 0.05.';
                continue;
            }

            $values[$key] = $val;
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = 'Errores de validación: ' . implode(' ', $errors);
            header('Location: /admin/matching/config');
            exit;
        }

        $db = Database::getInstance();
        $updatedBy = $_SESSION['admin_user']['id'] ?? null;

        try {
            foreach ($values as $key => $val) {
                $stmt = $db->prepare(
                    "INSERT INTO general_matching_config (config_key, config_value, updated_by)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_by = VALUES(updated_by)"
                );
                $stmt->execute([$key, $val, $updatedBy]);
            }

            $db->logActivity(
                'Configuracion',
                'ACTUALIZAR',
                'Configuración de matching actualizada: ' . implode(', ', array_map(
                    fn($k, $v) => "$k=$v",
                    array_keys($values),
                    $values,
                )),
                null,
            );

            $_SESSION['flash_success'] = 'Umbrales de matching actualizados correctamente.';
        } catch (\Exception $e) {
            error_log('[ConfigController] Error updating matching_config: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al guardar la configuración.';
        }

        header('Location: /admin/matching/config');
        exit;
    }
}
