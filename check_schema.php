<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/construccion/src/Database.php';
$db = Database::getInstance();

try {
    $stmt = $db->query("DESCRIBE general_proyectos_procesos");
    $columns = $stmt->fetchAll();
    echo "Columns in general_proyectos_procesos:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}


