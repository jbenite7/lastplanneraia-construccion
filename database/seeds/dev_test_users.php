<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();

$users = [
    ['test.A', 'Test Admin', 'test.a@aia.local', 'Administrador', 'A'],
    ['test.D', 'Test Director', 'test.d@aia.local', 'Director de Proyecto', 'D'],
    ['test.R', 'Test Residente', 'test.r@aia.local', 'Residente de Obra', 'R'],
    ['test.C', 'Test Subcontratista', 'test.c@aia.local', 'Subcontratista', 'C'],
];

foreach ($users as [$username, $name, $email, $cargo, $role]) {
    $row = $db->query('SELECT id FROM general_usuarios WHERE usuario = ? LIMIT 1', [$username])->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $userId = (int) $row['id'];
        $db->query(
            'UPDATE general_usuarios SET nombre = ?, email = ?, cargo = ?, password = ?, activo = 1, force_password_change = 0 WHERE id = ?',
            [$name, $email, $cargo, password_hash('aia2026', PASSWORD_DEFAULT), $userId],
        );
    } else {
        $db->query(
            'INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, force_password_change, activo) VALUES (?, ?, ?, ?, ?, 0, 1)',
            [$name, $email, $cargo, $username, password_hash('aia2026', PASSWORD_DEFAULT)],
        );
        $userId = (int) $db->lastInsertId();
    }

    foreach ([73, 75] as $projectId) {
        $exists = (int) $db->query('SELECT COUNT(*) FROM general_proyectos_procesos WHERE Id = ?', [$projectId])->fetchColumn();
        if ($exists !== 1) {
            continue;
        }
        $db->query(
            'INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE role = VALUES(role)',
            [$projectId, $userId, $role],
        );
    }
}

echo "Usuarios de prueba dev sincronizados.\n";
