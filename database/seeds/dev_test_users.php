<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();

$users = [
    ['test.A', 'Test Admin', 'test.a@aia.local', 'Administrador', 'A'],
    ['test.D', 'Test Director', 'test.d@aia.local', 'Director de Proyecto', 'D'],
    ['test.R', 'Test Residente', 'test.r@aia.local', 'Residente de Obra', 'R'],
    ['test.C', 'Test Subcontratista', 'test.c@aia.local', 'Subcontratista', 'C'],
    ['test.V', 'Test Visualizador', 'test.v@aia.local', 'Visualizador', 'V'],
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

    // 27 «Prueba» es el proyecto que la suite de navegador de Programación Semanal usa como
    // banco de pruebas de CNP/CNC/CIC: es el único con semanas 5, 6 y 7 sembradas y con los
    // estados de confirmación que esas pruebas miden. Nunca se sembró aquí, así que cuando el
    // listado de proyectos pasó a filtrar por `project_members` la tarjeta desapareció y una
    // docena de casos empezó a morir en «Project card not found: Prueba». No es un proyecto
    // nuevo: es el dato real al que ya apuntaban los fixtures.
    // 68 «Optimización Aeropuerto JMC» es el otro banco de pruebas de la suite de navegador:
    // siete specs lo nombran por su fixture `jmc` (tests/browser/fixtures/projects.mjs) porque es
    // el proyecto sembrado que llega hasta la semana 6, y esas fases avanzadas son justo lo que
    // miden — calificación, semana confirmada, histórico. Le pasó lo mismo que al 27: nunca se
    // sembró la membresía, así que desde que el listado filtra por `project_members` la tarjeta
    // no existe y los casos mueren en «Project card not found: Optimización Aeropuerto JMC».
    $projectIds = $username === 'test.A' ? [27, 68, 73, 75, 76] : [27, 68, 73, 75];
    foreach ($projectIds as $projectId) {
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
