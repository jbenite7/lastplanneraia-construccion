<?php

namespace Admin\Controllers;

use App\Security\RbacService;
use App\Security\EventService;

/**
 * Base Controller for all administrative routes that require authentication.
 */
abstract class AdminController extends BaseController
{
    public function __construct()
    {
        // 1. Verify if user is logged in
        if (!isset($_SESSION['admin_user'])) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                $this->json(['success' => false, 'message' => 'Sesión expirada']);
            }
            header('Location: /admin/login');
            exit;
        }

        // 2. Additional security checks can be added here
    }

    /**
     * Helper to check if the current user has a permission.
     */
    protected function userCan($permission)
    {
        return \Admin\Models\User::can($_SESSION['admin_user'], $permission);
    }

    /**
     * Helper to check if the request is AJAX.
     */
    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    protected function requireAdminRole(string $message = 'Acceso restringido a administradores')
    {
        $db = \Database::getInstance();
        $rbac = new RbacService($db);

        $role = $rbac->normalizeRole($_SESSION['admin_user']['permiso'] ?? '');
        if ($role === 'A') {
            $_SESSION['admin_user']['permiso'] = 'A';
            return;
        }

        $events = new EventService($db);
        $events->emitAuthorizationDenied('admin.permisos.gestionar', [
            'route' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'scope' => 'admin',
            'role' => $role,
        ]);

        if ($this->isAjaxRequest()) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => $message]);
        }

        http_response_code(403);
        echo '403 - ' . $message;
        exit;
    }
}
