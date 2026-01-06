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

// Inicialización de variables comunes
$Id = $_POST["Id"] ?? null;
$semana = filter_var($_GET['semana'] ?? ($_POST['semana'] ?? 0), FILTER_VALIDATE_INT);

if ($opcion == "modificar" || $opcion == "registrar") {
    $Ejecutado = ($_POST["Ejecutado"] === "Nulo") ? null : $_POST["Ejecutado"];
    $codigo_actividad = $_POST["codigo_actividad"] ?? '';
    $unidad = $_POST["unidad"] ?? '';
    $cantidad_ppto = (($_POST["cantidad_ppto"] ?? "") === "") ? null : $_POST["cantidad_ppto"];
    $editarActividadAsociar = filter_var($_POST["editarActividadAsociar"] ?? 0, FILTER_VALIDATE_INT);
    $actividadAsociar = (!isset($_POST["actividadAsociar"]) || $_POST["actividadAsociar"] === "") ? "*No Asociada*" : $_POST["actividadAsociar"];

    $Fecha_Inicio = date("Y-m-d", strtotime($_POST["Fecha_Inicio"]));
    $Fecha_Fin = date("Y-m-d", strtotime($_POST["Fecha_Fin"]));

} else if ($opcion == "modificargrupo") {
    $script1 = $_POST["Id1"] ?? '1=0'; 
    $Ejecutado = $_POST["Ejecutado"] ?? 0;
} else if ($opcion == "nueva_sem") {
    $f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"]));
} else if ($opcion == "cargar_unidad") {
    $codigo_actividad = $_POST["codigo_actividad"] ?? '';
}

switch ($opcion) {
    case 'modificar':
        modificar($Ejecutado, $codigo_actividad, $unidad, $cantidad_ppto, $Id, $semana, $Fecha_Inicio, $Fecha_Fin, fecha_inicio_sem($semana, $dbPrefix, $db), $actividadAsociar, $editarActividadAsociar, $dbPrefix, $db);
        break;

    case 'modificargrupo':
        modificargrupo($Ejecutado, $script1, $semana, fecha_inicio_sem($semana, $dbPrefix, $db), $dbPrefix, $db);
        break;

    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $dbPrefix, $db);
        break;

    case 'eliminar_sem':
        eliminar_sem($semana, $dbPrefix, $db);
        break;

    case 'cargar_unidad':
        cargar_unidad($codigo_actividad, $db);
        break;
}

function modificar($Ejecutado, $codigo_actividad, $unidad, $cantidad_ppto, $Id, $semana, $Fecha_Inicio, $Fecha_Fin, $inicio_semana, $actividadAsociar, $editarActividadAsociar, $dbPrefix, $db) {
    if (empty($cantidad_ppto) || $cantidad_ppto === 0) {
        $cantidad_ppto = null;
    }
    
    if (empty($codigo_actividad)) {
        $medir_productividad = 0;
    } else {
        $medir_productividad = 1;
        $queryCode = "SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad = ?";
        $stmtCode = $db->query($queryCode, [$codigo_actividad]);
        $dataCode = $stmtCode->fetch();
        $unidad = $dataCode["unidad"] ?? $unidad;
    }

    // Activar la actividad
    $queryActiva = "UPDATE {$dbPrefix}_programa_consolidado SET Activa = 1 WHERE Consecutivo_en_Programa = ? AND Semana = ?";
    $db->query($queryActiva, [$Id, $semana]);

    // Actualizar datos de la actividad
    $params = [
        $Ejecutado, $medir_productividad, $unidad, $cantidad_ppto, 
        $codigo_actividad, $Ejecutado, $Fecha_Inicio, $Fecha_Fin
    ];

    if ($editarActividadAsociar == 0) {
        $queryUpdate = "UPDATE {$dbPrefix}_programa_consolidado SET 
            Ejecutado = ?, medir_productividad = ?, unidad = ?, cantidad_ppto = ?, 
            codigo_actividad = ?, Ejecutado_Siguiente_Semana = ?, Fecha_Inicio = ?, Fecha_Fin = ? 
            WHERE Consecutivo_en_Programa = ? AND Semana = ?";
        $params[] = $Id;
        $params[] = $semana;
    } else {
        $queryUpdate = "UPDATE {$dbPrefix}_programa_consolidado SET 
            Ejecutado = ?, medir_productividad = ?, unidad = ?, cantidad_ppto = ?, 
            codigo_actividad = ?, Ejecutado_Siguiente_Semana = ?, Fecha_Inicio = ?, Fecha_Fin = ?, 
            programaAnteriorAsociar = ? 
            WHERE Consecutivo_en_Programa = ? AND Semana = ?";
        $params[] = $actividadAsociar;
        $params[] = $Id;
        $params[] = $semana;
    }

    $stmtUpdate = $db->query($queryUpdate, $params);
    verificar_resultado($stmtUpdate);

    modificar_estado_act($Id, $semana, $inicio_semana, $dbPrefix, $db);
}

function modificar_estado_act($Id, $semana, $inicio_semana, $dbPrefix, $db) {
    $query = "UPDATE {$dbPrefix}_programa_consolidado SET
       Estado = CASE
          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) < 0 THEN 'Terminada Antes'
          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) = 0 THEN 'Terminada'
          WHEN Ejecutado < 1 AND Ejecutado >= 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) > 0 THEN 'Atrasada'
          WHEN Ejecutado < 1 AND Ejecutado > 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) <= 0 THEN 'A Tiempo'
          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END), 3) = 0 AND Ejecutado = 0 THEN 'Debe Iniciar esta Semana'
          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) > 0 AND Ejecutado = 0 THEN 'Ya Debió Iniciar y Restricciones Pendientes'
          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END), 3) = 0 AND Ejecutado = 0 THEN 'Debe Iniciar esta Semana y Restricciones Pendientes'
          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado = 0 THEN 'En Liberación de Restricciones'
          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado > 0 THEN 'A Tiempo'
          ELSE 'No Requerida'
       END
      WHERE Titulo = 0 AND Consecutivo_en_Programa = ? AND Semana = ?";
    
    $params = array_fill(0, 28, $inicio_semana); 
    $params[] = $Id;
    $params[] = $semana;

    $db->query($query, $params);
}

function modificargrupo($Ejecutado, $script1, $semana, $inicio_semana, $dbPrefix, $db) {
    $queryActiva = "UPDATE {$dbPrefix}_programa_consolidado SET Activa = 1 WHERE $script1 AND Semana = ?";
    $queryEjecutado = "UPDATE {$dbPrefix}_programa_consolidado SET Ejecutado = ? WHERE $script1 AND Semana = ?";
    
    $db->query($queryActiva, [$semana]);
    $stmtEjecutado = $db->query($queryEjecutado, [$Ejecutado, $semana]);
    verificar_resultado($stmtEjecutado);

    $fin_semana = date("Y-m-d", strtotime("$inicio_semana + 6 days"));
    $queryEstado = "UPDATE {$dbPrefix}_programa_consolidado SET
                        Estado = CASE
                            WHEN Fecha_Fin < ? AND Ejecutado = 1 THEN 'OK'
                            WHEN Fecha_Fin < ? AND Ejecutado < 1 THEN 'Atrasada'
                            WHEN (Fecha_Inicio < ?) AND Ejecutado < 1 AND Estado_Restricciones < 1 THEN 'Restricciones Pendientes para Iniciar'
                            WHEN (Fecha_Inicio >= ? OR Fecha_Fin >= ?) AND Ejecutado = 1 THEN 'Terminada Antes'
                            ELSE 'NI'
                        END
                    WHERE Titulo = 0 AND $script1 AND Semana = ?";
    
    $db->query($queryEstado, [$fin_semana, $fin_semana, $fin_semana, $fin_semana, $fin_semana, $semana]);
}

function nueva_sem($f_inicio_sem, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/nueva_semana.php");
    require(__DIR__ . "/../funciones_generales/modificar_sem_estado.php");
}

function eliminar_sem($semana, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/eliminar_semana.php");
}

function cargar_unidad($codigo_actividad, $db) {
    $unidad = '';
    if (!empty($codigo_actividad)) {
        $query = "SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad = ?";
        $stmt = $db->query($query, [$codigo_actividad]);
        $data = $stmt->fetch();
        $unidad = $data["unidad"] ?? '';
    }
    echo json_encode([$unidad]);
}

function verificar_resultado($stmt) {
    $informacion["respuesta"] = $stmt ? "BIEN" : "ERROR";
    echo json_encode($informacion);
}

function fecha_inicio_sem($semana, $dbPrefix, $db) {
    $queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_semanas_activas";
    $stmtCount = $db->query($queryCount);
    $rowCount = $stmtCount->fetch();

    if (($rowCount['total'] ?? 0) == 0) {
        return date("Y-m-d");
    } else {
        $querySemana = "SELECT Fecha_Inicio_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?";
        $stmtSemana = $db->query($querySemana, [$semana]);
        $dataSemana = $stmtSemana->fetch();
        return $dataSemana["Fecha_Inicio_Sem"] ?? date("Y-m-d");
    }
}
?>
