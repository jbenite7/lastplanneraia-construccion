<?php
require_once (__DIR__ . "/../conexion.php");
// El objeto $db (instancia de Database) ya está disponible desde conexion.php

$dbPrefix = $_GET['db'] ?? '';
// Validación estricta del prefijo de la base de datos
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
    die(json_encode(["error" => "Parámetro de base de datos inválido."]));
}

$opcion = $_POST["opcion"] ?? '';
$informacion = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "nuevo") {
    $inputConsecutivo = $_POST['inputConsecutivo'];
    $inputFechaSolicitud = empty($_POST['inputFechaSolicitud']) ? null : $_POST['inputFechaSolicitud'];
    $inputSolicitanteCambio = empty($_POST['inputSolicitanteCambio']) ? null : $_POST['inputSolicitanteCambio'];
    $inputDetalleSolicitanteOtro = empty($_POST['inputDetalleSolicitanteOtro']) ? null : $_POST['inputDetalleSolicitanteOtro'];
    $inputPrioridad = empty($_POST['inputPrioridad']) ? null : $_POST['inputPrioridad'];
    
    $tiposArr = [
        "Alcance" => $_POST['inputTipoCambioAlcance'] ?? 0,
        "Cronograma" => $_POST['inputTipoCambioCronograma'] ?? 0,
        "Costo" => $_POST['inputTipoCambioCosto'] ?? 0,
        "Calidad" => $_POST['inputTipoCambioCalidad'] ?? 0,
        "Riesgo" => $_POST['inputTipoCambioRiesgo'] ?? 0,
        "Recurso" => $_POST['inputTipoCambioRecurso'] ?? 0
    ];
    $inputTipoCambio = json_encode(["tiposCambio" => $tiposArr]);

    $inputResponsableSolucion = empty($_POST['inputResponsableSolucion']) ? null : $_POST['inputResponsableSolucion'];
    $inputDetalleResponsableSolucion = empty($_POST['inputDetalleResponsableSolucion']) ? null : $_POST['inputDetalleResponsableSolucion'];
    $inputJustificacion = empty($_POST['inputJustificacion']) ? null : $_POST['inputJustificacion'];
    $inputDescripcion = empty($_POST['inputDescripcion']) ? null : $_POST['inputDescripcion'];
    $inputIncidenciaAlcance = empty($_POST['inputIncidenciaAlcance']) ? null : $_POST['inputIncidenciaAlcance'];
    
    $cleanNum = function($v) { return floatval(str_replace(['$', ','], '', $v ?? 0)); };
    $inputTiempoCronograma = $cleanNum($_POST['inputTiempoCronograma'] ?? 0);
    $inputTiempoCronogramaAfectado = $cleanNum($_POST['inputTiempoCronogramaAfectado'] ?? 0);
    $inputIncidenciaCronograma = empty($_POST['inputIncidenciaCronograma']) ? null : $_POST['inputIncidenciaCronograma'];
    $inputValorPresupuesto = $cleanNum($_POST['inputValorPresupuesto'] ?? 0);
    $inputCostoDirecto = $cleanNum($_POST['inputCostoDirecto'] ?? 0);
    $inputCostoDirectoAIU = $cleanNum($_POST['inputCostoDirectoAIU'] ?? 0);
    $inputCostoDirectoAIUIVA = $cleanNum($_POST['inputCostoDirectoAIUIVA'] ?? 0);
    $inputValorAprobado = $cleanNum($_POST['inputValorAprobado'] ?? 0);
    
    $inputIncidenciaPresupuesto = empty($_POST['inputIncidenciaPresupuesto']) ? null : $_POST['inputIncidenciaPresupuesto'];
    $inputIncidenciaCalidad = empty($_POST['inputIncidenciaCalidad']) ? null : $_POST['inputIncidenciaCalidad'];
    $inputIncidenciaRiesgo = empty($_POST['inputIncidenciaRiesgo']) ? null : $_POST['inputIncidenciaRiesgo'];
    $inputIncidenciaRecurso = empty($_POST['inputIncidenciaRecurso']) ? null : $_POST['inputIncidenciaRecurso'];
    $inputFechaEntregaInterventoria = empty($_POST['inputFechaEntregaInterventoria']) ? null : $_POST['inputFechaEntregaInterventoria'];
    $inputFechaTentativaDefinicion = empty($_POST['inputFechaTentativaDefinicion']) ? null : $_POST['inputFechaTentativaDefinicion'];
    $inputAprobacion = empty($_POST['inputAprobacion']) ? null : $_POST['inputAprobacion'];
    $inputFechaDefinicion = empty($_POST['inputFechaDefinicion']) ? null : $_POST['inputFechaDefinicion'];
    $soportes = $_POST['soportes'] ?? null;
    $errores = '';

    $query = "INSERT INTO {$dbPrefix}_cambios (
        `id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, 
        `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, 
        `incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, 
        `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, 
        `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, 
        `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `fechaDefinicion`, `aprobacion`, `soportes`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $inputConsecutivo, $inputSolicitanteCambio, $inputDetalleSolicitanteOtro, $inputFechaSolicitud, $inputPrioridad, $inputTipoCambio,
        $inputResponsableSolucion, $inputDetalleResponsableSolucion, $inputJustificacion, $inputDescripcion,
        $inputIncidenciaAlcance, $inputTiempoCronograma, $inputTiempoCronogramaAfectado, $inputIncidenciaCronograma,
        $inputValorPresupuesto, $inputCostoDirecto, $inputCostoDirectoAIU, $inputCostoDirectoAIUIVA, $inputValorAprobado,
        $inputIncidenciaPresupuesto, $inputIncidenciaCalidad, $inputIncidenciaRiesgo, $inputIncidenciaRecurso,
        $inputFechaTentativaDefinicion, $inputFechaEntregaInterventoria, $inputFechaDefinicion, $inputAprobacion, $soportes
    ];

    $stmt = $db->query($query, $params);
    verificar_resultado($stmt, $errores);

} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "modificar") {
    $inputConsecutivo = $_POST['inputConsecutivo'];
    $inputFechaSolicitud = empty($_POST['inputFechaSolicitud']) ? null : $_POST['inputFechaSolicitud'];
    $inputSolicitanteCambio = $_POST['inputSolicitanteCambio'];
    $inputDetalleSolicitanteOtro = empty($_POST['inputDetalleSolicitanteOtro']) ? null : $_POST['inputDetalleSolicitanteOtro'];
    $inputPrioridad = $_POST['inputPrioridad'];
    
    $tiposArr = [
        "Alcance" => $_POST['inputTipoCambioAlcance'] ?? 0,
        "Cronograma" => $_POST['inputTipoCambioCronograma'] ?? 0,
        "Costo" => $_POST['inputTipoCambioCosto'] ?? 0,
        "Calidad" => $_POST['inputTipoCambioCalidad'] ?? 0,
        "Riesgo" => $_POST['inputTipoCambioRiesgo'] ?? 0,
        "Recurso" => $_POST['inputTipoCambioRecurso'] ?? 0
    ];
    $inputTipoCambio = json_encode(["tiposCambio" => $tiposArr]);

    $inputResponsableSolucion = empty($_POST['inputResponsableSolucion']) ? null : $_POST['inputResponsableSolucion'];
    $inputDetalleResponsableSolucion = empty($_POST['inputDetalleResponsableSolucion']) ? null : $_POST['inputDetalleResponsableSolucion'];
    $inputJustificacion = empty($_POST['inputJustificacion']) ? null : $_POST['inputJustificacion'];
    $inputDescripcion = empty($_POST['inputDescripcion']) ? null : $_POST['inputDescripcion'];
    $inputIncidenciaAlcance = empty($_POST['inputIncidenciaAlcance']) ? null : $_POST['inputIncidenciaAlcance'];
    
    $cleanNum = function($v) { return floatval(str_replace(['$', ','], '', $v ?? 0)); };
    $inputTiempoCronograma = $cleanNum($_POST['inputTiempoCronograma'] ?? 0);
    $inputTiempoCronogramaAfectado = $cleanNum($_POST['inputTiempoCronogramaAfectado'] ?? 0);
    $inputIncidenciaCronograma = empty($_POST['inputIncidenciaCronograma']) ? null : $_POST['inputIncidenciaCronograma'];
    $inputValorPresupuesto = $cleanNum($_POST['inputValorPresupuesto'] ?? 0);
    $inputCostoDirecto = $cleanNum($_POST['inputCostoDirecto'] ?? 0);
    $inputCostoDirectoAIU = $cleanNum($_POST['inputCostoDirectoAIU'] ?? 0);
    $inputCostoDirectoAIUIVA = $cleanNum($_POST['inputCostoDirectoAIUIVA'] ?? 0);
    $inputValorAprobado = $cleanNum($_POST['inputValorAprobado'] ?? 0);
    
    $inputIncidenciaPresupuesto = empty($_POST['inputIncidenciaPresupuesto']) ? null : $_POST['inputIncidenciaPresupuesto'];
    $inputIncidenciaCalidad = empty($_POST['inputIncidenciaCalidad']) ? null : $_POST['inputIncidenciaCalidad'];
    $inputIncidenciaRiesgo = empty($_POST['inputIncidenciaRiesgo']) ? null : $_POST['inputIncidenciaRiesgo'];
    $inputIncidenciaRecurso = empty($_POST['inputIncidenciaRecurso']) ? null : $_POST['inputIncidenciaRecurso'];
    $inputFechaEntregaInterventoria = empty($_POST['inputFechaEntregaInterventoria']) ? null : $_POST['inputFechaEntregaInterventoria'];
    $inputFechaTentativaDefinicion = empty($_POST['inputFechaTentativaDefinicion']) ? null : $_POST['inputFechaTentativaDefinicion'];
    $inputAprobacion = $_POST['inputAprobacion'];
    $inputFechaDefinicion = empty($_POST['inputFechaDefinicion']) ? null : $_POST['inputFechaDefinicion'];
    $soportes = $_POST['soportes'] ?? null;
    $errores = '';

    $query = "UPDATE {$dbPrefix}_cambios SET 
        `solicitanteCambio`=?, `detalleSolicitanteOtro`=?, `fechaSolicitud`=?, `prioridad`=?, `tipoCambio`=?, 
        `responsableSolucion`=?, `detalleResponsableSolucion`=?, `justificacion`=?, `descripcion`=?, 
        `incidenciaAlcance`=?, `tiempoCronograma`=?, `tiempoCronogramaAfectado`=?, `incidenciaCronograma`=?, 
        `valorPresupuesto`=?, `costoDirecto`=?, `costoDirectoAIU`=?, `costoDirectoAIUIVA`=?, `valorAprobado`=?, 
        `incidenciaPresupuesto`=?, `incidenciaCalidad`=?, `incidenciaRiesgo`=?, `incidenciaRecurso`=?, 
        `fechaTentativaDefinicion`=?, `fechaEntregaInterventoria`=?, `Observaciones`=NULL, 
        `fechaDefinicion`=?, `aprobacion`=?, `soportes`=? 
        WHERE `id`=?";

    $params = [
        $inputSolicitanteCambio, $inputDetalleSolicitanteOtro, $inputFechaSolicitud, $inputPrioridad, $inputTipoCambio,
        $inputResponsableSolucion, $inputDetalleResponsableSolucion, $inputJustificacion, $inputDescripcion,
        $inputIncidenciaAlcance, $inputTiempoCronograma, $inputTiempoCronogramaAfectado, $inputIncidenciaCronograma,
        $inputValorPresupuesto, $inputCostoDirecto, $inputCostoDirectoAIU, $inputCostoDirectoAIUIVA, $inputValorAprobado,
        $inputIncidenciaPresupuesto, $inputIncidenciaCalidad, $inputIncidenciaRiesgo, $inputIncidenciaRecurso,
        $inputFechaTentativaDefinicion, $inputFechaEntregaInterventoria, $inputFechaDefinicion, $inputAprobacion, $soportes,
        $inputConsecutivo
    ];

    $stmt = $db->query($query, $params);
    verificar_resultado($stmt, $errores);

} else if ($opcion == "nueva_sem") {
    $f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"]));
    nueva_sem($f_inicio_sem, $dbPrefix, $db);
} else if ($opcion == "eliminar_sem") {
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    eliminar_sem($semana, $dbPrefix, $db);
} else if ($opcion == "eliminar") {
    $Id = $_POST["Id"];
    eliminar($Id, $dbPrefix, $db);
} else if ($opcion == "actualizarFechaInicio") {
    $Id = $_POST["idActividad"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    actualizarFechaInicio($Id, $semana, $dbPrefix, $db);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "obtenerNombreDirector") {
    obtenerNombreDirector($dbPrefix, $db);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "obtenerURLCambios") {
    obtenerURLCambios($dbPrefix, $db);
}

function nueva_sem($f_inicio_sem, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/nueva_semana.php");
    require(__DIR__ . "/../funciones_generales/modificar_sem_estado.php");
}

function obtenerNombreDirector($dbPrefix, $db) {
    $query = "SELECT nombre FROM {$dbPrefix}_profesionales WHERE cargo = 'Director de Obra' LIMIT 1";
    $stmt = $db->query($query);
    $data = $stmt->fetch();
    echo json_encode($data["nombre"] ?? '', JSON_UNESCAPED_UNICODE);
}

function obtenerURLCambios($dbPrefix, $db) {
    $query = "SELECT urlCambios FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1";
    $stmt = $db->query($query, [$dbPrefix]);
    $data = $stmt->fetch();
    echo json_encode($data["urlCambios"] ?? '', JSON_UNESCAPED_UNICODE);
}

function eliminar_sem($semana, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/eliminar_semana.php");
}

function eliminar($Id, $dbPrefix, $db) {
    $query = "DELETE FROM {$dbPrefix}_cambios WHERE id = ?";
    $stmt = $db->query($query, [$Id]);
    verificar_resultado($stmt, '');
}

function actualizarFechaInicio($Id, $semana, $dbPrefix, $db) {
    $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?";
    $stmt = $db->query($query, [$Id, $semana]);
    $data = $stmt->fetch();
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
}

function verificar_resultado($stmt, $errores) {
    $respuesta = ($stmt) ? "BIEN" : "ERROR";
    if (!empty($errores)) $respuesta = $errores;
    echo json_encode(["respuesta" => $respuesta]);
}
?>