<?php

namespace Admin\Controllers;

use Admin\Models\User;
use Admin\Core\Security;
use Database;

class UserController extends AdminController
{
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User(Database::getInstance());
    }

    /**
     * List all users.
     */
    public function index()
    {
        $users = $this->userModel->getAll();
        
        $this->render('users/index', [
            'title' => 'Gestión de Usuarios - Admin Panel',
            'pageTitle' => 'Usuarios',
            'breadcrumb' => 'Usuarios',
            'users' => $users
        ]);
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        $this->render('users/create', [
            'title' => 'Crear Usuario - Admin Panel',
            'pageTitle' => 'Nuevo Usuario',
            'breadcrumb' => 'Usuarios / Crear',
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    /**
     * Store a new user.
     */
    public function store()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'cargo' => trim($_POST['cargo'] ?? ''),
            'proyecto' => trim($_POST['proyecto'] ?? ''),
            'permiso' => trim($_POST['permiso'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];

        if (empty($data['usuario']) || empty($data['password'])) {
            $this->json(['success' => false, 'message' => 'Usuario y contraseña son requeridos']);
        }

        if ($this->userModel->findByUsername($data['usuario'])) {
            $this->json(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        }

        if ($this->userModel->create($data)) {
            $this->json(['success' => true, 'message' => 'Usuario creado correctamente']);
        }

        $this->json(['success' => false, 'message' => 'Error al crear el usuario']);
    }

    /**
     * Show edit user form.
     */
    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /admin/usuarios');
            exit;
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            header('Location: /admin/usuarios');
            exit;
        }

        $this->render('users/edit', [
            'title' => 'Editar Usuario - Admin Panel',
            'pageTitle' => 'Editar Usuario',
            'breadcrumb' => 'Usuarios / Editar',
            'user' => $user,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'cargo' => trim($_POST['cargo'] ?? ''),
            'proyecto' => trim($_POST['proyecto'] ?? ''),
            'permiso' => trim($_POST['permiso'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];

        if ($this->userModel->update($id, $data)) {
            $this->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el usuario']);
    }

    /**
     * Delete a user.
     */
    public function delete()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        }

        if ($this->userModel->delete($id)) {
            $this->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        }

        $this->json(['success' => false, 'message' => 'Error al eliminar el usuario']);
    }
}
