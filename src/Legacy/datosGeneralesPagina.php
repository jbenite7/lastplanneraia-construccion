<?php

use App\Security\RbacCatalog;

session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$dbName = $_SESSION['db'] ?? '';
$semana = (int) ($_SESSION['semana'] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

// Resolve table names via TableResolver
$tSemanasActivas = TableResolver::resolveByPrefix($dbName, 'semanas_activas');

// Set project context for queryWithProject auto-injection
$projectId = TableResolver::getProjectIdByPrefix($dbName);
if ($projectId) {
    $dbInstance->setProjectContext($projectId);
}

$permisoCodigo = $_SESSION['permiso'] ?? '';
$rolHumano = RbacCatalog::getRoleName($permisoCodigo);
// Pasa por el mismo interruptor que la vista: si el módulo está oculto, el JSON que
// consume el JS tiene que decir lo mismo que la barra lateral (spec del 2026-08-13:
// docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md).
$canAccessBi = \App\View\Components\BiAccessComponent::canAccess();

// Re-leer el cargo de general_usuarios en cada request para que los cambios
// hechos desde el Admin se reflejen sin necesidad de re-login.
$cargoUsuario = '';
$usuarioSesion = trim((string) ($_SESSION['usuario'] ?? ''));
if ($usuarioSesion !== '') {
    $stmtCargo = $dbInstance->query(
        'SELECT cargo FROM general_usuarios WHERE usuario = ? LIMIT 1',
        [$usuarioSesion]
    );
    $rowCargo = $stmtCargo->fetch();
    if ($rowCargo) {
        $cargoUsuario = trim(preg_replace('/\s+/u', ' ', (string) ($rowCargo['cargo'] ?? '')) ?? '');
    }
}
if ($cargoUsuario !== '') {
    $rolHumano = $cargoUsuario;
}

$arreglo = [
    "proyecto" => $_SESSION['proyecto'] ?? '',
    "project_id" => (int) ($_SESSION['project_id'] ?? 0),
    "db" => $dbName,
    "semana" => $semana,
    "permiso" => $permisoCodigo,
    "permiso_canonico" => $_SESSION['permiso_canonico'] ?? $permisoCodigo,
    "canAccessBi" => $canAccessBi,
    "area" => $_SESSION['area'] ?? 'Construccion',
    "pdcActivo" => $_SESSION['pdcActivo'] ?? '',
    "nombreUsuario" => $_SESSION['nombreUsuario'] ?? '',
    "rolUsuario" => $rolHumano,
    "seccion" => $_POST['seccion'] ?? '',
];

try {
    // Aislamiento por proyecto explícito (no delegado a la reescritura de queryWithProject).
    $stmtConteo = $dbInstance->queryWithProject("SELECT COUNT(*) AS total FROM {$tSemanasActivas} WHERE project_id = ?", [$projectId]);
    $dataConteo = $stmtConteo->fetch();
    $conteo = (int) ($dataConteo["total"] ?? 0);

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
        $sqlUltima = "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSemanasActivas} WHERE project_id = ? ORDER BY Semana DESC LIMIT 1";
        $stmtUltima = $dbInstance->queryWithProject($sqlUltima, [$projectId]);
        $dataUltima = $stmtUltima->fetch();

        $arreglo["Fecha_Inicio_SemYMD"] = $dataUltima["Fecha_Inicio_Sem"];
        $arreglo["Fecha_Inicio_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Inicio_Sem"]));

        $arreglo["Fecha_Fin_SemYMD"] = $dataUltima["Fecha_Fin_Sem"];
        $arreglo["Fecha_Fin_Sem"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));

        $arreglo["Fecha_datepicker"] = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));
        $arreglo["Max_Semana"] = $dataUltima["Semana"];
        $_SESSION["Max_Semana"] = $dataUltima["Semana"];

        $sqlDetalles = "SELECT Semanal_Confirmada, fechaCierreCompromisos, fechaCreacionSemana,
                       (SELECT SUM(reprogramacion) FROM {$tSemanasActivas} WHERE Semana <= ? AND project_id = ?) AS versionCronograma
                       FROM {$tSemanasActivas} WHERE Semana = ? AND project_id = ?";

        $stmtDetalles = $dbInstance->queryWithProject($sqlDetalles, [$semana, $projectId, $semana, $projectId]);
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

        $stmtLista = $dbInstance->queryWithProject("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSemanasActivas}");
        while ($row = $stmtLista->fetch()) {
            $arreglo["listadoSemanas"][] = $row;
        }
    }

    $_SESSION["Fecha_Inicio_SemYMD"] = $arreglo["Fecha_Inicio_SemYMD"];

    $arreglo["weekCsrfToken"] = \App\Security\CsrfTokenManager::generate('lps_week_admin');

    header('Content-Type: application/json');
    echo json_encode(["data" => $arreglo], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en datosGeneralesPagina.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al cargar datos generales."]);
}
