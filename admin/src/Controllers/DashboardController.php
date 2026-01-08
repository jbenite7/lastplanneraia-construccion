<?php

namespace Admin\Controllers;

class DashboardController extends AdminController
{
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
