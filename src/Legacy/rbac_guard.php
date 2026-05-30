<?php

if (!function_exists('rbac_guard_bootstrap')) {
    function rbac_guard_bootstrap(): void
    {
        static $bootstrapped = false;
        if ($bootstrapped) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $projectRoot = dirname(__DIR__, 3);
        $rootAutoload = $projectRoot . '/vendor/autoload.php';
        if (file_exists($rootAutoload)) {
            require_once $rootAutoload;
        } else {
            // Fallback para entornos donde el root es diferente
            $projectRoot = realpath(__DIR__ . '/../../../');
            $rootAutoload = $projectRoot . '/vendor/autoload.php';
            if (file_exists($rootAutoload)) {
                require_once $rootAutoload;
            }
        }

        if (!class_exists('Database')) {
            $dbClass = $projectRoot . '/src/Core/Database.php';
            if (file_exists($dbClass)) {
                require_once $dbClass;
            }
        }

        $bootstrapped = true;
    }
}

if (!function_exists('rbac_guard_require_permission')) {
    function rbac_guard_require_permission(string $permissionKey, array $options = []): void
    {
        rbac_guard_bootstrap();

        if (!class_exists('Database') || !class_exists('App\\Security\\RbacService') || !class_exists('App\\Security\\EventService')) {
            error_log("RBAC Guard Error: Classes not found. DB: " . (class_exists('Database') ? 'Y' : 'N') . ", RBAC: " . (class_exists('App\\Security\\RbacService') ? 'Y' : 'N') . ", Event: " . (class_exists('App\\Security\\EventService') ? 'Y' : 'N'));
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'respuesta' => 'ERROR',
                'mensaje' => 'No se pudo inicializar el validador de permisos.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $db = Database::getInstance();
        $rbac = new \App\Security\RbacService($db);

        if ($rbac->can($permissionKey)) {
            return;
        }

        $project = $options['project'] ?? ($_SESSION['db'] ?? ($_GET['db'] ?? ($_POST['db'] ?? null)));
        $events = new \App\Security\EventService($db);
        $events->emitAuthorizationDenied($permissionKey, [
            'route' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'role' => $rbac->resolveCurrentRole(),
            'user' => $_SESSION['usuario'] ?? ($_SESSION['admin_user']['usuario'] ?? 'desconocido'),
        ], $project);

        http_response_code((int) ($options['http_code'] ?? 403));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'respuesta' => 'ERROR',
            'success' => false,
            'mensaje' => $options['message'] ?? 'No autorizado para esta accion.',
            'permission_key' => $permissionKey,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
