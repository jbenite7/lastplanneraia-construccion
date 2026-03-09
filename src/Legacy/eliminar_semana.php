<?php

session_start();
require_once __DIR__ . "/../../conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$dbName = $_GET['db'] ?? $_POST['db'] ?? '';
$semana = (int)($_POST["semana"] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

try {
    // 1. Verificar si es la última semana
    $stmtMax = $dbInstance->query("SELECT MAX(Semana) AS maxSemana FROM {$dbName}_semanas_activas");
    $dataMax = $stmtMax->fetch();
    $maxSemana = (int)($dataMax["maxSemana"] ?? 0);

    if ($maxSemana > $semana) {
        $arreglo = [
            "maxSemana" => $maxSemana,
            "puedeEliminar" => "NO",
            "mensaje" => "Solo se puede eliminar la última semana activa para mantener la integridad de los datos.",
        ];
        echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    } else {
        // 2. Realizar eliminación en cascada de la semana seleccionada (y superiores por seguridad)
        $tablas = [
            "{$dbName}_semanas_activas" => "Semana",
            "{$dbName}_programa_consolidado" => "Semana",
            "{$dbName}_programacion_semanal" => "Semana",
            "{$dbName}_cic" => "Semana",
            "{$dbName}_pdc" => "semana",
            "{$dbName}_actividades" => "semanaActualizacion",
        ];

        foreach ($tablas as $tabla => $columna) {
            $dbInstance->query("DELETE FROM $tabla WHERE $columna >= ?", [$semana]);
        }

        $dbInstance->logActivity('Sistema', 'ELIMINAR_SEMANA', "Eliminación de semana $semana y superiores en proyecto $dbName");

        $arreglo = [
            "maxSemana" => $maxSemana,
            "puedeEliminar" => "SI",
        ];
        echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("Error en eliminar_semana.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al eliminar la semana."]);
}
