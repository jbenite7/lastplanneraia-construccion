<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../construccion/conexion.php';

use Admin\Models\User;
use Admin\Models\Project;

echo "--- Testing User Model ---\n";
$userModel = new User($db);
$users = $userModel->getAll();
echo "Total users found: " . count($users) . "\n";
if (count($users) > 0) {
    echo "First user: " . $users[0]['usuario'] . "\n";
}

echo "\n--- Testing Project Model ---\n";
$projectModel = new Project($db);
$projects = $projectModel->getAllActive();
echo "Total active projects found: " . count($projects) . "\n---\n";
if (count($projects) > 0) {
    echo "First project: " . $projects[0]['Proyecto_Proceso'] . "\n";
}


