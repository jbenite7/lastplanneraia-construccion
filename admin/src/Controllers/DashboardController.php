<?php

namespace Admin\Controllers;

class DashboardController extends BaseController
{
    public function __construct()
    {
        if (!isset($_SESSION['admin_user'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Show dashboard home.
     */
    public function index()
    {
        $this->render('dashboard', [
            'title' => 'Dashboard - Admin Panel',
            'user' => $_SESSION['admin_user']
        ]);
    }
}
