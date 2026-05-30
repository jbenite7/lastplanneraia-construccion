<?php

namespace Admin\Controllers;

use Admin\Core\RoleManager;
use Admin\Core\Security;
use Admin\Models\Project;
use Admin\Models\User;
use App\Services\ProjectProfessionalsSyncService;
use Database;

class UserController extends AdminController
{
    private $userModel;
    private $projectModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminRole('Solo administradores pueden gestionar usuarios.');
        $db = Database::getInstance();
        $this->userModel = new User($db);
        $this->projectModel = new Project($db);
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
            'users' => $users,
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
            'csrf_token' => Security::generateCsrfToken(),
            'roles' => RoleManager::getAll(),
            'projects' => $this->projectModel->getAll(),
        ]);
    }

    /**
     * Get unique job titles for selection.
     */
    public function getCargos()
    {
        $db = Database::getInstance();

        // Obtener cargos de usuarios existentes y de la tabla de inteligencia
        $sql = "SELECT DISTINCT cargo as title FROM general_usuarios WHERE cargo IS NOT NULL AND cargo != ''
                UNION
                SELECT DISTINCT cargo_title as title FROM role_intelligence
                ORDER BY title ASC";

        $stmt = $db->query($sql);
        $cargos = $stmt->fetchAll();

        $this->json(['success' => true, 'cargos' => array_column($cargos, 'title')]);
    }

    /**
     * Suggest a unique username based on full name or email prefix.
     * Also checks for name and email duplicates.
     */
    public function suggestUsername()
    {
        $nombreCompleto = trim($_GET['nombre'] ?? '');
        $email = trim($_GET['email'] ?? '');

        $response = [
            'success' => true,
            'usuario' => '',
            'nombreExiste' => false,
            'emailExiste' => false,
        ];

        // Verificar duplicados de nombre y email
        if (!empty($nombreCompleto) && $this->userModel->findByName($nombreCompleto)) {
            $response['nombreExiste'] = true;
        }

        if (!empty($email) && $this->userModel->findByEmail($email)) {
            $response['emailExiste'] = true;
        }

        $baseUsuario = '';
        $n1 = '';
        $n2 = '';
        $a1 = '';
        $a2 = '';

        // 1. Si hay email, el prefijo manda
        if (!empty($email) && strpos($email, '@') !== false) {
            $baseUsuario = explode('@', $email)[0];
            $baseUsuario = $this->normalizeString($baseUsuario);
            $baseUsuario = str_replace(' ', '.', $baseUsuario);
        }

        // 2. Lógica del nombre
        if (!empty($nombreCompleto)) {
            $nombreLimpio = $this->normalizeString($nombreCompleto);
            $partes = array_values(array_filter(explode(' ', $nombreLimpio)));
            $numPartes = count($partes);

            if ($numPartes > 0) {
                $n1 = $partes[0];
                if ($numPartes == 2) {
                    $a1 = $partes[1];
                } elseif ($numPartes == 3) {
                    $n2 = $partes[1];
                    $a1 = $partes[2];
                    $a2 = $partes[1];
                } elseif ($numPartes >= 4) {
                    $n2 = $partes[1];
                    $a1 = $partes[2];
                    $a2 = $partes[3];
                }

                if (empty($baseUsuario)) {
                    $baseUsuario = !empty($a1) ? "{$n1}.{$a1}" : $n1;
                }
            }
        }

        if (!empty($baseUsuario)) {
            $usuarioSugerido = $baseUsuario;
            if (!$this->userModel->findByUsername($usuarioSugerido)) {
                $response['usuario'] = $usuarioSugerido;
            } else {
                if (!empty($a2) && $a2 !== $a1) {
                    $intento = "{$n1}.{$a2}";
                    if (!$this->userModel->findByUsername($intento)) {
                        $response['usuario'] = $intento;
                    }
                }

                if (empty($response['usuario'])) {
                    $contador = 1;
                    while ($this->userModel->findByUsername($baseUsuario . $contador)) {
                        $contador++;
                    }
                    $response['usuario'] = $baseUsuario . $contador;
                }
            }
        }

        $this->json($response);
    }

    /**
     * Normalize string for username creation.
     */
    private function normalizeString($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $string = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $string,
        );
        // Eliminar cualquier cosa que no sea letras, números, espacios o puntos
        $string = preg_replace('/[^a-z0-9\s.]/', '', $string);
        // Limpiar espacios múltiples
        $string = preg_replace('/\s+/', ' ', trim($string));

        return $string;
    }

    /**
     * Store a new user.
     */
    public function store()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $assignments = $this->parseAssignments($_POST['assignments'] ?? []);

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'cargo' => trim($_POST['cargo'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'assignments' => $assignments,
        ];

        if (empty($data['usuario']) || empty($data['password']) || empty($data['nombre'])) {
            $this->json(['success' => false, 'message' => 'Nombre, usuario y contraseña son requeridos']);
        }

        $assignmentError = $this->validateAssignments($assignments);
        if ($assignmentError !== null) {
            $this->json(['success' => false, 'message' => $assignmentError]);
        }

        // Validaciones estrictas de duplicados
        if ($this->userModel->findByName($data['nombre'])) {
            $this->json(['success' => false, 'message' => 'Ya existe un usuario con este nombre completo']);

            return;
        }

        if (!empty($data['email']) && $this->userModel->findByEmail($data['email'])) {
            $this->json(['success' => false, 'message' => 'Ya existe un usuario con este correo electrónico']);

            return;
        }

        if ($this->userModel->findByUsername($data['usuario'])) {
            $this->json(['success' => false, 'message' => 'El nombre de usuario ya existe']);

            return;
        }

        if ($this->userModel->create($data)) {
            // Aprender el cargo si es nuevo
            $primaryRole = $assignments[0]['role'] ?? 'V';
            RoleManager::learn($data['cargo'], $primaryRole);

            // Auditoría
            Database::getInstance()->logActivity(
                'Usuarios',
                'CREAR',
                "Se creó el usuario '{$data['usuario']}' ({$data['nombre']}) con " . count($assignments) . " proyecto(s) asignado(s)",
            );

            $this->json(['success' => true, 'message' => 'Usuario creado correctamente']);

            return;
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
            'assignments' => $this->userModel->getProjectAssignments((int) $id),
            'csrf_token' => Security::generateCsrfToken(),
            'roles' => RoleManager::getAll(),
            'projects' => $this->projectModel->getAll(),
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

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuario no encontrado']);
        }

        $assignments = $this->parseAssignments($_POST['assignments'] ?? []);
        $currentAdminId = (int) ($_SESSION['admin_user']['id'] ?? 0);
        $isSelfUpdate = $currentAdminId > 0 && $currentAdminId === (int) $id;

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'cargo' => trim($_POST['cargo'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'activo' => $this->normalizeBooleanInput($_POST['activo'] ?? '1'),
            'force_password_change' => $this->normalizeBooleanInput($_POST['force_password_change'] ?? '0'),
            'assignments' => $assignments,
        ];

        if (empty($data['usuario']) || empty($data['nombre'])) {
            $this->json(['success' => false, 'message' => 'Nombre y usuario son requeridos']);
        }

        if ($isSelfUpdate && (int) $data['activo'] !== 1) {
            $this->json(['success' => false, 'message' => 'No puedes inactivar tu propia cuenta desde la sesión actual.']);
        }

        $assignmentError = $this->validateAssignments($assignments);
        if ($assignmentError !== null) {
            $this->json(['success' => false, 'message' => $assignmentError]);
        }

        $existingByName = $this->userModel->findByName($data['nombre']);
        if ($existingByName && (int) $existingByName['id'] !== (int) $id) {
            $this->json(['success' => false, 'message' => 'Ya existe un usuario con este nombre completo']);
        }

        $existingByEmail = $this->userModel->findByEmail($data['email']);
        if (!empty($data['email']) && $existingByEmail && (int) $existingByEmail['id'] !== (int) $id) {
            $this->json(['success' => false, 'message' => 'Ya existe un usuario con este correo electrónico']);
        }

        $existingByUsername = $this->userModel->findByUsername($data['usuario']);
        if ($existingByUsername && (int) $existingByUsername['id'] !== (int) $id) {
            $this->json(['success' => false, 'message' => 'El nombre de usuario ya existe']);
        }

        $previousAssignments = $this->userModel->getProjectAssignments((int) $id);
        $removedAssignments = $this->findRemovedAssignments($previousAssignments, $assignments);

        if ($this->userModel->update($id, $data)) {
            $emailForRevocation = trim((string) ($user['email'] ?? ''));
            if ($emailForRevocation === '') {
                $emailForRevocation = trim((string) ($data['email'] ?? ''));
            }

            $this->blockProfessionalAccess($removedAssignments, $emailForRevocation);

            // Auditoría
            \Database::getInstance()->logActivity(
                'Usuarios',
                'MODIFICAR',
                "Se actualizó el usuario con ID: $id ('{$data['usuario']}'), estado " . ((int) $data['activo'] === 1 ? 'activo' : 'inactivo') . ", cambio de clave " . ((int) $data['force_password_change'] === 1 ? 'pendiente' : 'normal') . " y " . count($assignments) . " proyecto(s)",
            );

            $this->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el usuario']);
    }

    /**
     * Delete a user.
     */
    public function delete()
    {
        http_response_code(405);
        $this->json([
            'success' => false,
            'message' => 'Los usuarios ya no se eliminan. Usa Activo/Inactivo para bloquear su acceso.',
        ]);
    }

    public function toggleActive()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $active = $this->normalizeBooleanInput($_POST['value'] ?? '0') === 1;

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        }

        if ((int) ($_SESSION['admin_user']['id'] ?? 0) === $id && !$active) {
            $this->json(['success' => false, 'message' => 'No puedes inactivar tu propia cuenta desde la sesión actual.']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuario no encontrado']);
        }

        if (!$this->userModel->setActive($id, $active)) {
            $this->json(['success' => false, 'message' => 'No se pudo actualizar el estado del usuario']);
        }

        Database::getInstance()->logActivity(
            'Usuarios',
            'ESTADO',
            "Se marcó al usuario '{$user['usuario']}' como " . ($active ? 'activo' : 'inactivo'),
        );

        $this->json([
            'success' => true,
            'active' => $active,
            'message' => $active
                ? 'Usuario activado correctamente.'
                : 'Usuario inactivado correctamente. Ya no podrá iniciar sesión.',
        ]);
    }

    public function toggleForcePasswordChange()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $enabled = $this->normalizeBooleanInput($_POST['value'] ?? '0') === 1;

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuario no encontrado']);
        }

        if (!$this->userModel->setForcePasswordChange($id, $enabled)) {
            $this->json(['success' => false, 'message' => 'No se pudo actualizar la política de contraseña']);
        }

        Database::getInstance()->logActivity(
            'Usuarios',
            'SEGURIDAD',
            "Se " . ($enabled ? 'activó' : 'desactivó') . " el cambio obligatorio de contraseña para '{$user['usuario']}'",
        );

        $this->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? 'El usuario deberá cambiar su contraseña en su próximo inicio de sesión.'
                : 'El cambio obligatorio de contraseña fue desactivado para este usuario.',
        ]);
    }

    public function revokeAllProjects()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $userId = (int) ($_POST['id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        }

        if ((int) ($_SESSION['admin_user']['id'] ?? 0) === $userId) {
            $this->json(['success' => false, 'message' => 'No puedes revocar todos tus permisos desde tu propia sesión.']);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuario no encontrado']);
        }

        $result = $this->userModel->revokeAllProjects($userId);
        if (!$result['success']) {
            $this->json(['success' => false, 'message' => 'No se pudieron revocar los permisos del usuario']);
        }

        $assignments = $result['assignments'];
        $this->blockProfessionalAccess($assignments, (string) ($user['email'] ?? ''));

        Database::getInstance()->logActivity(
            'Usuarios',
            'REVOCAR_TODOS_PROYECTOS',
            "Se revocaron todos los permisos de '{$user['usuario']}' en " . count($assignments) . ' proyecto(s) sin borrar su historial.',
        );

        $message = empty($assignments)
            ? 'El usuario ya no tenía permisos activos en proyectos.'
            : 'Se revocaron todos los permisos del usuario sin afectar su historial en los proyectos.';

        $this->json([
            'success' => true,
            'revoked_projects' => count($assignments),
            'message' => $message,
        ]);
    }

    /**
     * Remove a user from a specific project (AJAX).
     */
    public function removeProject()
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $projectId = (int) ($_POST['project_id'] ?? 0);

        if ($userId <= 0 || $projectId <= 0) {
            $this->json(['success' => false, 'message' => 'Parámetros inválidos']);
        }

        try {
            $blockReason = $this->userModel->canRemoveFromProject($userId, $projectId);
            if ($blockReason !== null) {
                $this->json(['success' => false, 'message' => $blockReason]);
            }

            if (!$this->userModel->removeFromProject($userId, $projectId)) {
                $this->json(['success' => false, 'message' => 'Error al quitar el proyecto']);
            }

            // Block the professional in the project tables
            $project = $this->projectModel->find($projectId);
            $user = $this->userModel->find($userId);

            if ($project && !empty($project['Base_de_Datos']) && $user && !empty($user['email'])) {
                try {
                    (new ProjectProfessionalsSyncService(Database::getInstance()))
                        ->blockProfessionalByEmail((string) $project['Base_de_Datos'], (string) $user['email']);
                } catch (\Throwable $e) {
                    error_log("Error al bloquear profesional en SyncService: " . $e->getMessage());
                }
            }

            // Audit
            $userName = $user ? $user['usuario'] : "ID:$userId";
            $projectName = $project ? $project['Proyecto_Proceso'] : "ID:$projectId";
            Database::getInstance()->logActivity(
                'Usuarios',
                'QUITAR_PROYECTO',
                "Se retiró a '{$userName}' del proyecto '{$projectName}'",
            );

            $this->json(['success' => true, 'message' => 'Proyecto retirado correctamente']);
        } catch (\Throwable $e) {
            error_log("Error crítico en removeProject: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }

    private function parseAssignments($rawAssignments): array
    {
        if (!is_array($rawAssignments)) {
            return [];
        }

        $normalized = [];

        foreach ($rawAssignments as $row) {
            if (!is_array($row)) {
                continue;
            }

            $projectId = (int) ($row['project_id'] ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $normalized[$projectId] = [
                'project_id' => $projectId,
                'role' => RoleManager::normalizeRole((string) ($row['role'] ?? 'V')),
            ];
        }

        return array_values($normalized);
    }

    private function validateAssignments(array $assignments): ?string
    {
        // Permitido que un usuario no tenga proyectos asignados (PDCA Ciclo 2)

        $validProjects = $this->projectModel->getAll();
        $validProjectIds = [];
        foreach ($validProjects as $project) {
            $validProjectIds[(int) $project['Id']] = true;
        }

        foreach ($assignments as $assignment) {
            $projectId = (int) ($assignment['project_id'] ?? 0);
            if ($projectId <= 0 || !isset($validProjectIds[$projectId])) {
                return 'Una de las asignaciones tiene un proyecto inválido.';
            }
        }

        return null;
    }

    private function normalizeBooleanInput($value): int
    {
        return in_array((string) $value, ['1', 'true', 'on'], true) ? 1 : 0;
    }

    private function findRemovedAssignments(array $previousAssignments, array $nextAssignments): array
    {
        $nextProjectIds = [];
        foreach ($nextAssignments as $assignment) {
            $projectId = (int) ($assignment['project_id'] ?? 0);
            if ($projectId > 0) {
                $nextProjectIds[$projectId] = true;
            }
        }

        $removed = [];
        foreach ($previousAssignments as $assignment) {
            $projectId = (int) ($assignment['project_id'] ?? 0);
            if ($projectId > 0 && !isset($nextProjectIds[$projectId])) {
                $removed[] = $assignment;
            }
        }

        return $removed;
    }

    private function blockProfessionalAccess(array $assignments, string $email): void
    {
        $normalizedEmail = trim($email);
        if ($normalizedEmail === '' || empty($assignments)) {
            return;
        }

        $syncService = new ProjectProfessionalsSyncService(Database::getInstance());
        foreach ($assignments as $assignment) {
            $projectId = (int) ($assignment['project_id'] ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $project = $this->projectModel->find($projectId);
            if (!$project || empty($project['Base_de_Datos'])) {
                continue;
            }

            try {
                $syncService->blockProfessionalByEmail((string) $project['Base_de_Datos'], $normalizedEmail);
            } catch (\Throwable $e) {
                error_log('Error al bloquear profesional por revocación administrativa: ' . $e->getMessage());
            }
        }
    }
}
