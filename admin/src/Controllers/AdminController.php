<?php

namespace Admin\Controllers;

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
                $this->json(['success' => false, 'message' => 'Sesión expirada'], 401);
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
}
