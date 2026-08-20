<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Database;

/**
 * Interruptores globales de módulos (tabla general_flags).
 * Spec: docs/superpowers/specs/2026-08-20-interruptor-control-tower-admin-design.md
 */
class ModulosController extends AdminController
{
    /** Los flags que esta pantalla conoce y su texto en humano. */
    private const FLAGS = [
        'bi.control_tower.visible' => 'Control Tower (BI) visible para roles no-Admin',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminRole('Solo administradores pueden gestionar módulos.');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $flags = [];

        try {
            $stmt = $db->prepare('SELECT clave, valor, actualizado_por, actualizado_en FROM general_flags');
            $stmt->execute();
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $flags[$row['clave']] = $row;
            }
        } catch (\Exception $e) {
            error_log('[ModulosController] Error leyendo general_flags: ' . $e->getMessage());
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('modulos/index', [
            'title' => 'Módulos',
            'pageTitle' => 'Módulos',
            'breadcrumb' => 'Módulos',
            'conocidos' => self::FLAGS,
            'flags' => $flags,
            'csrf_token' => Security::generateCsrfToken(),
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ]);
    }

    public function update(): void
    {
        // Los `return` que siguen son solo por claridad de lectura: BaseController::json() hace
        // exit() internamente, así que estos rechazos (405/403/422) ya cortan la ejecución ahí.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $clave = (string) ($_POST['clave'] ?? '');
        $valor = ($_POST['valor'] ?? '') === '1' ? '1' : '0';

        if (!array_key_exists($clave, self::FLAGS)) {
            http_response_code(422);
            $this->json(['success' => false, 'message' => 'Flag desconocido']);
        }

        $usuario = (string) ($_SESSION['admin_user']['usuario'] ?? 'admin');

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO general_flags (clave, valor, actualizado_por) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), actualizado_por = VALUES(actualizado_por)'
            );
            $stmt->execute([$clave, $valor, $usuario]);
            $_SESSION['flash_success'] = 'Interruptor guardado.';
        } catch (\Exception $e) {
            error_log('[ModulosController] Error guardando flag: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo guardar el interruptor.';
        }

        header('Location: /admin/modulos');
        exit;
    }
}
