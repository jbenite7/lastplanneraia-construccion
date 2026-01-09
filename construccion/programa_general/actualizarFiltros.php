<?php session_start();
require_once __DIR__ . "/../conexion.php";

/** @var Database $db */
// $db ya está inicializado en conexion.php

$dbPrefix = $_POST['db'] ?? '';
$semana = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

// Validación del prefijo
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Prefijo de base de datos inválido."]));
}

unset(
    $_SESSION["lookahead_intermedia"],
    $_SESSION["no_iniciadas_intermedia"],
    $_SESSION["a_tiempo_intermedia"],
    $_SESSION["terminadas_intermedia"]
);

$arreglo = [];
$sessionKeys = ['no_requeridas', 'lookahead', 'no_iniciadas', 'a_tiempo', 'atrasadas', 'terminadas'];

foreach ($sessionKeys as $key) {
    $arreglo['activa_' . $key] = (isset($_SESSION[$key]) && $_SESSION[$key] == 1) ? 1 : 0;
}

$query = "SELECT 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND Estado = 'No Requerida') AS no_requeridas, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND Estado = 'En Liberación de Restricciones') AS lookahead, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND (Estado = 'Debe Iniciar esta Semana' OR Estado = 'Debe Iniciar esta Semana y Restricciones Pendientes')) AS no_iniciadas, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND Estado = 'A Tiempo') AS a_tiempo, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND (Estado = 'Atrasada' OR Estado = 'Ya Debió Iniciar y Restricciones Pendientes')) AS atrasadas, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0 AND (Estado = 'Terminada' OR Estado = 'Terminada Antes')) AS terminadas, 
    (SELECT COUNT(*) FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0) AS total";

try {
    $stmt = $db->query($query, [$semana, $semana, $semana, $semana, $semana, $semana, $semana]);
    $data = $stmt->fetch();

    if ($data) {
        $arreglo = array_merge($arreglo, $data);
        $arregloFinal["data"] = $arreglo;
        header('Content-Type: application/json');
        echo json_encode($arregloFinal, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudieron obtener los filtros."]);
    }
} catch (Exception $e) {
    error_log("Error en actualizarFiltros.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}
?>