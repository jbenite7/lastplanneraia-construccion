<?php

namespace Admin\Controllers;

use Admin\Models\Project;
use Admin\Core\Security;
use Database;

class ProjectController extends AdminController
{
    private $projectModel;

    public function __construct()
    {
        parent::__construct();
        $this->projectModel = new Project(\Database::getInstance());
    }

    /**
     * Listado principal de proyectos.
     */
    public function index()
    {
        $projects = $this->projectModel->getAll();
        
        $this->render('projects/index', [
            'title' => 'Gestión de Proyectos - Admin Panel',
            'pageTitle' => 'Proyectos de Construcción',
            'breadcrumb' => 'Proyectos',
            'projects' => $projects
        ]);
    }

    /**
     * Ver detalles de un proyecto (o formulario de edición).
     */
    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /admin/proyectos');
            exit;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            header('Location: /admin/proyectos');
            exit;
        }

        $this->render('projects/edit', [
            'title' => 'Editar Proyecto - Admin Panel',
            'pageTitle' => 'Editar Proyecto: ' . $project['Proyecto_Proceso'],
            'breadcrumb' => 'Proyectos / Editar',
            'project' => $project,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }
}
