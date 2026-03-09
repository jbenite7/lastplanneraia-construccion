<?php
/**
 * Test User Generator (Phase 5C - QA Tool)
 * Generate standard test users for every RBAC role to allow smooth cross-validation of UI and permissions.
 * Run via CLI: `docker-compose exec app php seed_test_users.php`
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/construccion/conexion.php';
require_once __DIR__ . '/admin/src/Core/RoleManager.php';

use Admin\Core\RoleManager;

echo "Iniciando generador de usuarios de prueba (RBAC)...\n\n";

$db = Database::getInstance();
$roles = RoleManager::getAll();
$passwordHash = password_hash('aia2026', PASSWORD_DEFAULT);

// Ensure the "Prueba" project exists in our scope (we'll fetch its ID and Prefix)
$proyectoResult = $db->query("SELECT Id, Base_de_Datos FROM general_proyectos_procesos WHERE Proyecto_Proceso = 'Prueba' LIMIT 1")->fetch();

if (!$proyectoResult) {
    die("ERROR: No se encontró el proyecto 'Prueba' en la tabla `general_proyectos_procesos`. \nPor favor crea un proyecto de prueba primero.\n");
}

$proyectoId = $proyectoResult['Id'];
$proyectoPrefijo = $proyectoResult['Base_de_Datos'];

echo "Proyecto objetivo: Prueba (ID: $proyectoId, Prefijo: $proyectoPrefijo)\n";
echo "Semilla de contraseña: aia2026\n\n";

foreach ($roles as $code => $roleData) {
    if (!isset($roleData['name'])) continue; // Skip aliases if returned by getAll
    
    $name = $roleData['name'];
    $email = "test.{$code}@aia.com.co";
    $fullName = "Test Usuario ($name)";
    
    echo "Procesando Rol [$code] - $name...\n";
    
    // 1. Chequear si el usuario existe o crearlo (guardamos su ID)
    $stmt = $db->query("SELECT id FROM general_usuarios WHERE email = ?", [$email]);
    $userRow = $stmt->fetch();
    
    if ($userRow) {
        $userId = $userRow['id'];
        echo " -> Usuario existente (ID: $userId). Actualizando clave y cargo.\n";
        $db->query("UPDATE general_usuarios SET password = ?, cargo = ? WHERE id = ?", [
            $passwordHash, 
            $name, 
            $userId
        ]);
    } else {
        echo " -> Creando nuevo usuario...\n";
        $db->query("INSERT INTO general_usuarios (nombre, email, usuario, password, cargo) VALUES (?, ?, ?, ?, ?)", [
            $fullName,
            $email,
            'test.'.$code,
            $passwordHash, // Hash seguro
            $name
        ]);
        $userId = $db->lastInsertId();
    }
    
    // 2. Asignar el usuario al proyecto 'Prueba' explicitamente con este rol (sobreescribimos si existe)
    echo " -> Asignando perfil [$code] en project_members al proyecto 'Prueba'...\n";
    $db->query("DELETE FROM project_members WHERE user_id = ? AND project_id = ?", [$userId, $proyectoId]);
    
    $db->query("INSERT INTO project_members (user_id, project_id, role) VALUES (?, ?, ?)", [
        $userId,
        $proyectoId,
        $code
    ]);
    
    echo "[OK] \n\n";
}

echo "\nSembrado completado exitosamente.\n";
echo "Puedes iniciar sesión con cualquiera de los siguientes correos:\n";
foreach ($roles as $code => $roleData) {
    if (!isset($roleData['name'])) continue;
    echo "- test.{$code}@aia.com.co (Clave: aia2026)\n";
}
echo "\n";
