<?php
use App\Security\RbacCatalog;

session_start();
require_once __DIR__ . "/../../conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$dbName = $_SESSION['db'] ?? '';
$semana = (int)($_SESSION['semana'] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

$permisoCodigo = $_SESSION['permiso'] ?? '';
$rolHumano = RbacCatalog::getRoleName($permisoCodigo);

$arreglo = [
    "proyecto" => $_SESSION['proyecto'] ?? '',
    "db" => $dbName,
    "semana" => $semana,
    "permiso" => $permisoCodigo,
    "pdcActivo" => $_SESSION['pdcActivo'] ?? '',
    "nombreUsuario" => $_SESSION['nombreUsuario'] ?? '',
    "rolUsuario" => $rolHumano,
    "seccion" => $_POST['seccion'] ?? '',
];

try {
    $stmtConteo = $dbInstance->query("SELECT COUNT(*) AS total FROM {$dbName}_semanas_activas");
    $dataConteo = $stmtConteo->fetch();
    $conteo = (int)($dataConteo["total"] ?? 0);

    if ($conteo === 0) {
        $fechaInicioSem = date("Y-m-d");
        $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . " +6 days"));

        $arreglo["Fecha_Inicio_SemYMD"] = $fechaInicioSem;
        $arreglo["Fecha_Fin_SemYMD"] = $fechaFinSem;
        $arreglo["Fecha_Inicio_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($fechaInicioSem));
        $arreglo["Fecha_Fin_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($fechaFinSem));
        $arreglo["Fecha_datepicker"] = $fechaInicioSem;
        $arreglo["Max_Semana"] = 0;
        $_SESSION["Max_Semana"] = 0;
        $arreglo["listadoSemanas"] = [""];
    } else {
        $sqlUltima = "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbName}_semanas_activas WHERE Semana = (SELECT MAX(Semana) FROM {$dbName}_semanas_activas)";
        $stmtUltima = $dbInstance->query($sqlUltima);
        $dataUltima = $stmtUltima->fetch();

        $arreglo["Fecha_Inicio_SemYMD"] = $dataUltima["Fecha_Inicio_Sem"];
        $arreglo["Fecha_Inicio_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Inicio_Sem"]));

        $arreglo["Fecha_Fin_SemYMD"] = $dataUltima["Fecha_Fin_Sem"];
        $arreglo["Fecha_Fin_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));

        $arreglo["Fecha_datepicker"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));
        $arreglo["Max_Semana"] = $dataUltima["Semana"];
        $_SESSION["Max_Semana"] = $dataUltima["Semana"];

        $sqlDetalles = "SELECT Semanal_Confirmada, fechaCierreCompromisos, fechaCreacionSemana, 
                       (SELECT SUM(reprogramacion) FROM {$dbName}_semanas_activas WHERE Semana <= ?) AS versionCronograma 
                       FROM {$dbName}_semanas_activas WHERE Semana = ?";

        $stmtDetalles = $dbInstance->query($sqlDetalles, [$semana, $semana]);
        $dataDetalles = $stmtDetalles->fetch();

        if ($dataDetalles) {
            $arreglo["Semanal_Confirmada"] = $dataDetalles["Semanal_Confirmada"];
            $_SESSION["Semanal_Confirmada"] = $dataDetalles["Semanal_Confirmada"];

            $arreglo["fechaCierreCompromisos"] = $dataDetalles["fechaCierreCompromisos"];
            $_SESSION["fechaCierreCompromisos"] = $dataDetalles["fechaCierreCompromisos"];

            $arreglo["fechaCreacionSemana"] = $dataDetalles["fechaCreacionSemana"];
            $_SESSION["fechaCreacionSemana"] = $dataDetalles["fechaCreacionSemana"];

            $arreglo["versionCronograma"] = $dataDetalles["versionCronograma"];
            $_SESSION["versionCronograma"] = $dataDetalles["versionCronograma"];
        }

        $stmtLista = $dbInstance->query("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbName}_semanas_activas");
        while ($row = $stmtLista->fetch()) {
            $arreglo["listadoSemanas"][] = $row;
        }
    }

    $_SESSION["Fecha_Inicio_SemYMD"] = $arreglo["Fecha_Inicio_SemYMD"];

    header('Content-Type: application/json');
    echo json_encode(["data" => $arreglo], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en datosGeneralesPagina.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al cargar datos generales."]);
}
