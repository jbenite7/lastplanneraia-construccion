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
            'projects' => $projects,
            'csrf_token' => Security::generateCsrfToken()
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

    /**
     * Procesar la actualización de un proyecto.
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/proyectos');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }

        $id = $_POST['id'] ?? null;
        $nombre = $_POST['nombre'] ?? '';
        $base_datos = $_POST['base_datos'] ?? ''; // Aunque no se configure en creación, puede ser útil en edición
        $area = $_POST['area'] ?? 'Construccion';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $acceso = isset($_POST['acceso']) ? 1 : 0;
        $pdc_activo = isset($_POST['pdc_activo']) ? 1 : 0;
        $fecha_inicio_lb = $_POST['fecha_inicio_lb'] ?: null;
        $fecha_fin_lb = $_POST['fecha_fin_lb'] ?: null;
        $costo_retraso = $_POST['costo_retraso'] ?: 5000000;
        $url_cambios = $_POST['url_cambios'] ?: null;

        if (!$id || !$nombre || !$base_datos) {
            header('Location: /admin/proyectos/editar?id=' . $id . '&error=missing_fields');
            exit;
        }

        $data = [
            'nombre' => $nombre,
            'base_datos' => $base_datos,
            'area' => $area,
            'activo' => $activo,
            'acceso' => $acceso,
            'pdc_activo' => $pdc_activo,
            'fecha_inicio_lb' => $fecha_inicio_lb,
            'fecha_fin_lb' => $fecha_fin_lb,
            'costo_retraso' => $costo_retraso,
            'url_cambios' => $url_cambios
        ];

        if ($this->projectModel->update($id, $data)) {
            header('Location: /admin/proyectos?success=updated');
        } else {
            header('Location: /admin/proyectos/editar?id=' . $id . '&error=db_error');
        }
        exit;
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        $this->render('projects/create', [
            'title' => 'Crear Proyecto - Admin Panel',
            'pageTitle' => 'Nuevo Proyecto',
            'breadcrumb' => 'Proyectos / Crear',
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    /**
     * Guardar nuevo proyecto.
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/proyectos/crear');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }

        $nombre = $_POST['nombre'] ?? '';
        $area = $_POST['area'] ?? 'Construccion';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $acceso = isset($_POST['acceso']) ? 1 : 0;
        $pdc_activo = isset($_POST['pdc_activo']) ? 1 : 0;
        $fecha_inicio_lb = $_POST['fecha_inicio_lb'] ?: null;
        $fecha_fin_lb = $_POST['fecha_fin_lb'] ?: null;
        $costo_retraso = $_POST['costo_retraso'] ?: 5000000;
        $url_cambios = $_POST['url_cambios'] ?: null;

        if (!$nombre) {
            header('Location: /admin/proyectos/crear?error=missing_fields');
            exit;
        }

        $data = [
            'nombre' => $nombre,
            'area' => $area,
            'activo' => $activo,
            'acceso' => $acceso,
            'pdc_activo' => $pdc_activo,
            'fecha_inicio_lb' => $fecha_inicio_lb,
            'fecha_fin_lb' => $fecha_fin_lb,
            'costo_retraso' => $costo_retraso,
            'url_cambios' => $url_cambios
        ];

        if ($this->projectModel->create($data)) {
            header('Location: /admin/proyectos?success=created');
        } else {
            header('Location: /admin/proyectos/crear?error=db_error');
        }
        exit;
    }

    /**
     * Eliminar proyecto.
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/proyectos');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }

        $id = $_POST['id'] ?? null;
        if ($id && $this->projectModel->delete($id)) {
            header('Location: /admin/proyectos?success=deleted');
        } else {
            header('Location: /admin/proyectos?error=delete_failed');
        }
        exit;
    }

    /**
     * Generar y descargar copia de seguridad SQL del proyecto.
     */
    public function backup()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /admin/proyectos');
            exit;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            header('Location: /admin/proyectos?error=not_found');
            exit;
        }

        $sqlContent = $this->projectModel->exportToSql($id);
        
        if (!$sqlContent) {
            header('Location: /admin/proyectos?error=backup_failed');
            exit;
        }

        $filename = "backup_" . $project['Base_de_Datos'] . "_" . date('Ymd_His') . ".sql";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $sqlContent;
        exit;
    }


    /**
     * Alternar el estado de un campo booleano de un proyecto vía AJAX.
     */
    public function toggleStatus()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Priorizar el token enviado en el body del POST para evitar problemas con cabeceras custom
        $token = $_POST['csrf_token'] ?? '';
        
        // Si no está en el body, buscar en las cabeceras (X-CSRF-TOKEN o HTTP_X_CSRF_TOKEN)
        if (empty($token)) {
            $headers = getallheaders();
            $token = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? '';
        }

        if (!Security::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        $id = $_POST['id'] ?? null;
        $field = $_POST['field'] ?? 'Activo';
        $value = isset($_POST['value']) ? (int)$_POST['value'] : null;

        if ($id === null || $value === null) {
            echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes']);
            exit;
        }

        // Mapeo de nombres de campo del frontend a la base de datos si es necesario
        $fieldMap = [
            'activo' => 'Activo',
            'acceso' => 'Acceso',
            'pdc'    => 'pdcActivo'
        ];

        $dbField = $fieldMap[$field] ?? $field;

        if ($this->projectModel->updateField($id, $dbField, $value)) {
            echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        exit;
    }
}
