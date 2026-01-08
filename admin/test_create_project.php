<?php
// admin/test_create_project.php

// 1. Cargar dependencias
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Simular conexión a BD (usando la lógica de tu index.php)
if (file_exists(__DIR__ . '/../construccion/src/Database.php')) {
    require_once __DIR__ . '/../construccion/src/Database.php';
    $db = \Database::getInstance();
} else {
    die("Error: No se encontró Database.php\n");
}

use Admin\Models\Project;

echo "\n--- INICIANDO PRUEBA DE CREACIÓN DE PROYECTO ---\n";

$model = new Project($db);

// Datos de prueba
$testName = "PROYECTO_TEST_" . time();
$testCode = "TEST" . rand(100, 999);
$data = [
    'nombre' => $testName,
    'codigo' => $testCode,
    'activo' => 1
];

// 1. INTENTO DE CREACIÓN
echo "1. Intentando crear proyecto: '$testName' ($testCode)... ";
try {
    $created = $model->create($data);
    if ($created) {
        echo "[OK] Creado exitosamente.\n";
    } else {
        echo "[ERROR] Falló la creación.\n";
        exit;
    }
} catch (Exception $e) {
    echo "[EXCEPCIÓN] " . $e->getMessage() . "\n";
    exit;
}

// 2. VERIFICACIÓN DE LECTURA
echo "2. Buscando el proyecto en la base de datos... ";
// Obtenemos todos y filtramos (ya que create no devuelve ID en este modelo simple)
$allProjects = $model->getAll();
$foundProject = null;

foreach ($allProjects as $p) {
    if ($p['Base_de_Datos'] === $testCode && $p['Proyecto_Proceso'] === $testName) {
        $foundProject = $p;
        break;
    }
}

if ($foundProject) {
    echo "[OK] Encontrado. ID: " . $foundProject['Id'] . "\n";
} else {
    echo "[ERROR] No se encontró el proyecto recién creado.\n";
    exit;
}

// 3. LIMPIEZA (ELIMINAR)
echo "3. Eliminando el proyecto de prueba... ";
$deleted = $model->delete($foundProject['Id']);
if ($deleted) {
    echo "[OK] Eliminado exitosamente.\n";
} else {
    echo "[ERROR] No se pudo eliminar el proyecto de prueba.\n";
}

echo "\n--- PRUEBA FINALIZADA CON ÉXITO ---\n";


