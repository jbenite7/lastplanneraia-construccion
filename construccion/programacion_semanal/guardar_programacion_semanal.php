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

// Inicialización de variables comunes según la opción
if ($opcion == "modificar") {
    $Id = $_POST["Id"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $Descripcion = $_POST["Descripcion"];
    $Ubicacion = $_POST["Ubicacion"];
    $Sub_Contratista = $_POST["Sub_Contratista"];
    $Responsable_AIA = $_POST["Responsable_AIA"];
    $Empresa = $_POST["Empresa"];
    $Unidad = $_POST["Unidad"];
    $Compromiso = $_POST["Compromiso"];
    $Cantidad_Sugerida = $_POST["Cantidad_Sugerida"];
    $Ejecutado_Real = $_POST["Real"];
    $Categoria_CNC = $_POST["Categoria_CNC"];
    $CNC = $_POST["CNC"];
    $Observaciones_CNC = $_POST["Observaciones_CNC"];
    $Rendimientos = $_POST["Rendimientos"];
} else if ($opcion == "EstadoEjecucion") {
    $Id = $_POST["Id"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $Ejecutado = $_POST["Ejecutado"];
} else if ($opcion == "eliminar") {
    $Id = $_POST["Id"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $Responsable_AIA = $_POST["Responsable_AIA"];
    $Categoria_CNP = $_POST["Categoria_CNP"];
    $CNP = $_POST["CNP"];
    $Observaciones_CNP = $_POST["Observaciones_CNP"];
} else if ($opcion == "duplicar") {
    $Id = $_POST["Id"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
} else if ($opcion == "nuevo") {
    $Id = $_POST["idNuevo"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $Actividad = $_POST["Actividad"] ?? '';
    $Descripcion = $_POST["Descripcion"] ?? '';
    $Ubicacion = $_POST["Ubicacion"] ?? '';
    $Sub_Contratista = $_POST["Sub_Contratista"] ?? '';
    $Responsable_AIA = $_POST["Responsable_AIA"] ?? '';
    $Empresa = $_POST["Empresa"] ?? '';
    $Unidad = $_POST["Unidad"] ?? '';
    $Compromiso = ($_POST["Compromiso"] === "") ? null : $_POST["Compromiso"];
} else if ($opcion == "autoprogramar") {
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
} else if ($opcion == "guardar_costos_cuadrilla") {
    $costo_hora_oficial = $_POST["costo_hora_oficial"];
    $costo_hora_ayudante = $_POST["costo_hora_ayudante"];
} else if ($opcion == "ind_compromisos") {
    $nombre = $_POST['nombre'] ?? 'general';
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
} else if ($opcion == "bloquear_compromisos") {
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $fechaCierreCompromisos = (($_POST["fechaCierreCompromisos"] ?? '') === '') ? null : date("Y-m-d", strtotime($_POST["fechaCierreCompromisos"]));
} else if ($opcion == "importar_actividad_no_requerida") {
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    $Consecutivo = $_POST["Consecutivo"];
}

switch ($opcion) {
    case 'modificar':
        modificar($Id, $semana, $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, $Empresa, $Unidad, $Compromiso, $Cantidad_Sugerida, $Ejecutado_Real, $Rendimientos, $Categoria_CNC, $CNC, $Observaciones_CNC, $dbPrefix, $db);
        break;

    case 'EstadoEjecucion':
        EstadoEjecucion($Id, $semana, $Ejecutado, $dbPrefix, $db);
        break;

    case 'eliminar':
        eliminar($Id, $semana, $Responsable_AIA, $Categoria_CNP, $CNP, $Observaciones_CNP, $dbPrefix, $db);
        break;

    case 'duplicar':
        duplicar($Id, $semana, $dbPrefix, $db);
        break;

    case 'nuevo':
        agregar_actividad($Id, $semana, $Actividad, $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, $Empresa, $Unidad, $Compromiso, $dbPrefix, $db);
        break;

    case 'autoprogramar':
        autoprogramar($semana, $dbPrefix, $db);
        break;

    case 'guardar_costos_cuadrilla':
        guardar_costos_cuadrilla($costo_hora_oficial, $costo_hora_ayudante, $dbPrefix, $db);
        break;

    case 'cargar_costos_cuadrilla':
        cargar_costos_cuadrilla($dbPrefix, $db);
        break;

    case 'ind_compromisos':
        ind_compromisos($db, $semana, $dbPrefix, $nombre);
        break;

    case 'bloquear_compromisos':
        bloquear_compromisos($db, $semana, $fechaCierreCompromisos, $dbPrefix);
        break;

    case 'importar_actividad_no_requerida':
        importar_actividad_no_requerida($db, $semana, $dbPrefix, $Consecutivo);
        break;
}

function modificar($Id, $semana, $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, $Empresa, $Unidad, $Compromiso, $Cantidad_Sugerida, $Ejecutado_Real, $Rendimientos, $Categoria_CNC, $CNC, $Observaciones_CNC, $dbPrefix, $db) {
    $PAC = null;
    $P_Completado = null;

    if ($Compromiso !== "" && $Ejecutado_Real !== "" && $Compromiso >= 0 && $Ejecutado_Real >= 0) {
        $P_Completado = ($Ejecutado_Real / $Compromiso);
        $PAC = ($Ejecutado_Real < $Compromiso) ? 0 : 1;
    } else {
        $Ejecutado_Real = ($Ejecutado_Real === "") ? null : $Ejecutado_Real;
        $Compromiso = ($Compromiso === "") ? null : $Compromiso;
        if ($Compromiso === null) $Ejecutado_Real = null;
    }

    $Categoria_CNC = empty($Categoria_CNC) ? null : $Categoria_CNC;
    $CNC = empty($CNC) ? null : $CNC;
    $Observaciones_CNC = empty($Observaciones_CNC) ? null : $Observaciones_CNC;
    $Rendimientos = empty($Rendimientos) ? null : $Rendimientos;

    if ($PAC == 1) {
        $query = "UPDATE {$dbPrefix}_programacion_semanal SET 
            Descripcion = ?, Ubicacion = ?, Sub_Contratista = ?, Responsable_AIA = ?, 
            Empresa = ?, Unidad = ?, Compromiso = ?, Cantidad_Sugerida = ?, 
            Ejecutado_Real = ?, P_Completado = ?, PAC = ?, Rendimientos = ?, 
            Categoria_CNC = NULL, CNC = NULL, Observaciones_CNC = NULL 
            WHERE Consecutivo = ?";
        $params = [
            $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, 
            $Empresa, $Unidad, $Compromiso, $Cantidad_Sugerida, 
            $Ejecutado_Real, $P_Completado, $PAC, $Rendimientos, $Id
        ];
    } else {
        $query = "UPDATE {$dbPrefix}_programacion_semanal SET 
            Descripcion = ?, Ubicacion = ?, Sub_Contratista = ?, Responsable_AIA = ?, 
            Empresa = ?, Unidad = ?, Compromiso = ?, Cantidad_Sugerida = ?, 
            Ejecutado_Real = ?, P_Completado = ?, PAC = ?, Rendimientos = ?, 
            Categoria_CNC = ?, CNC = ?, Observaciones_CNC = ? 
            WHERE Consecutivo = ?";
        $params = [
            $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, 
            $Empresa, $Unidad, $Compromiso, $Cantidad_Sugerida, 
            $Ejecutado_Real, $P_Completado, $PAC, $Rendimientos, 
            $Categoria_CNC, $CNC, $Observaciones_CNC, $Id
        ];
    }

    $stmt = $db->query($query, $params);
    verificar_resultado($stmt);
}

function EstadoEjecucion($Id, $semana, $Ejecutado, $dbPrefix, $db) {
    $query1 = "UPDATE {$dbPrefix}_programa_consolidado SET Activa = 1 WHERE Consecutivo_en_Programa = ? AND Semana = ?";
    $query2 = "UPDATE {$dbPrefix}_programa_consolidado SET Ejecutado_Siguiente_Semana = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?";
    
    $db->query($query1, [$Id, $semana]);
    $stmt = $db->query($query2, [$Ejecutado, $Id, $semana]);
    verificar_resultado($stmt);
}

function agregar_actividad($Id, $semana, $Actividad, $Descripcion, $Ubicacion, $Sub_Contratista, $Responsable_AIA, $Empresa, $Unidad, $Compromiso, $dbPrefix, $db) {
    $query0 = "SELECT * FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Id = ?";
    $stmt0 = $db->query($query0, [$semana, $Id]);
    $data0 = $stmt0->fetch();

    if ($data0) {
        $consecutivo_en_programa = $data0["Consecutivo_en_Programa"];
        $Fecha_Inicio = $data0["Fecha_Inicio"];
        $Fecha_Fin = $data0["Fecha_Fin"];
        $Ejecutado = $data0["Ejecutado"];
        $medir_productividad = $data0["medir_productividad"] ?? null;
        $cantidad_ppto = $data0["cantidad_ppto"] ?? null;
        $codigo_actividad = $data0["codigo_actividad"] ?? null;
    } else {
        $consecutivo_en_programa = null;
    }

    if (!$consecutivo_en_programa) {
        $queryMaxSemanas = "SELECT MAX(Consecutivo_En_Programa) as max_ps FROM {$dbPrefix}_programacion_semanal WHERE Semana = ?";
        $stmtMaxSemanas = $db->query($queryMaxSemanas, [$semana]);
        $dataMaxSemanas = $stmtMaxSemanas->fetch();

        $queryMaxConsolidado = "SELECT MAX(Consecutivo_en_Programa) as max_pc FROM {$dbPrefix}_programa_consolidado WHERE Semana = ?";
        $stmtMaxConsolidado = $db->query($queryMaxConsolidado, [$semana]);
        $dataMaxConsolidado = $stmtMaxConsolidado->fetch();

        $maxPS = $dataMaxSemanas["max_ps"] ?? 0;
        $maxPC = $dataMaxConsolidado["max_pc"] ?? 0;
        $consecutivo_en_programa = max($maxPS, $maxPC) + 1;

        $Fecha_Inicio = null;
        $Fecha_Fin = null;
        $medir_productividad = null;
        $cantidad_ppto = null;
        $codigo_actividad = null;
        $Ejecutado = 0;
    }

    $queryInsert = "INSERT INTO {$dbPrefix}_programacion_semanal (
        Semana, Consecutivo_En_Programa, Id, Actividad, Descripcion, Ubicacion, 
        Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, 
        Ejecutado, medir_productividad, Unidad, cantidad_ppto, Compromiso, 
        Critica, Atrasada, Activa, Prog_Sin_Restricciones_100, codigo_actividad
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'NA', 0, ?)";

    $stmtInsert = $db->query($queryInsert, [
        $semana, $consecutivo_en_programa, $Id, $Actividad, $Descripcion, $Ubicacion,
        $Fecha_Inicio, $Fecha_Fin, $Sub_Contratista, $Responsable_AIA, $Empresa,
        $Ejecutado, $medir_productividad, $Unidad, $cantidad_ppto, $Compromiso, $codigo_actividad
    ]);

    verificar_resultado($stmtInsert);
}

function autoprogramar($semana, $dbPrefix, $db) {
    // La lógica de autoprogramar está en un archivo externo que debe ser refactorizado.
    require(__DIR__ . "/../funciones_generales/php/autoprogramar_actividades.php");
}

function eliminar($Id, $semana, $Responsable_AIA, $Categoria_CNP, $CNP, $Observaciones_CNP, $dbPrefix, $db) {
    $querySelect = "SELECT Activa FROM {$dbPrefix}_programacion_semanal WHERE Consecutivo = ?";
    $stmtSelect = $db->query($querySelect, [$Id]);
    $data = $stmtSelect->fetch();

    if ($data && $data["Activa"] === "NA") {
        $queryDelete = "DELETE FROM {$dbPrefix}_programacion_semanal WHERE Consecutivo = ?";
        $stmt = $db->query($queryDelete, [$Id]);
        verificar_resultado($stmt);
    } else {
        $queryUpdate = "UPDATE {$dbPrefix}_programacion_semanal SET 
            Activa = '0', Responsable_AIA = ?, Categoria_CNP = ?, CNP = ?, Observaciones_CNP = ? 
            WHERE Consecutivo = ?";
        $stmt = $db->query($queryUpdate, [$Responsable_AIA, $Categoria_CNP, $CNP, $Observaciones_CNP, $Id]);
        verificar_resultado($stmt);
    }
}

function duplicar($Id, $semana, $dbPrefix, $db) {
    $queryInsert = "INSERT INTO {$dbPrefix}_programacion_semanal (
        Semana, Consecutivo_En_Programa, Id, Actividad, Critica, Atrasada, 
        Activa, Prog_Sin_Restricciones_100, Fecha_Inicio, Fecha_Fin, 
        Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad
    ) SELECT ?, Consecutivo_en_Programa, Id, Actividad, 0, 0, 'NA', Prog_Sin_Restricciones_100, 
             Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, 0 
      FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND Consecutivo = ?";
    
    $stmt = $db->query($queryInsert, [$semana, $semana, $Id]);
    verificar_resultado($stmt);
}

function guardar_costos_cuadrilla($costo_hora_oficial, $costo_hora_ayudante, $db, $conexion){
    $query="SELECT COUNT(*) FROM general_costos_cuadrillas WHERE Proyecto='$db'";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo==0){
        $query1="INSERT INTO general_costos_cuadrillas (Id, Proyecto, Costo_Hora_Oficial, Costo_Hora_Ayudante)
        VALUES
            (NULL,
            '$db',
            $costo_hora_oficial,
            $costo_hora_ayudante)";
    }else{
        $query1="UPDATE general_costos_cuadrillas SET Costo_Hora_Oficial=$costo_hora_oficial, Costo_Hora_Ayudante=$costo_hora_ayudante WHERE Proyecto='$db'";
    }
    $resultado1=mysqli_query($conexion, $query1);
    if(!$resultado1){
        die("Error");
    } else {
      echo json_encode("OK");
    }
    mysqli_close($conexion);
}

function cargar_costos_cuadrilla($db, $conexion){
    $query="SELECT COUNT(*) FROM general_costos_cuadrillas WHERE Proyecto='$db'";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo==0){
        $query1="SELECT Costo_Hora_Oficial=NULL, Costo_Hora_Ayudante=NULL";
    }else{
        $query1="SELECT * FROM general_costos_cuadrillas WHERE Proyecto='$db'";
    }
    $resultado1=mysqli_query($conexion, $query1);
    if(!$resultado1){
        $resultado=array(0, 0);
    }else{
        $data1=mysqli_fetch_assoc($resultado1);
        $Costo_Hora_Oficial=$data1["Costo_Hora_Oficial"];
        $Costo_Hora_Ayudante=$data1["Costo_Hora_Ayudante"];
        $resultado=array($Costo_Hora_Oficial, $Costo_Hora_Ayudante);
    }
    mysqli_close($conexion);

    echo json_encode($resultado);
}

function ind_compromisos($conexion, $semana, $db, $nombre){
    $query = "SELECT (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=1 AND Atrasada=0 AND (Activa=0 OR Activa=1)) AS Criticas_Requeridas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=1 AND Atrasada=0 AND Activa=1 AND Compromiso>0) AS Criticas_Comprometidas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=0 AND Atrasada=0 AND (Activa=0 OR Activa=1)) AS No_Criticas_Requeridas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=0 AND Atrasada=0 AND Activa=1 AND Compromiso>0) AS No_Criticas_Comprometidas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=1 AND Atrasada=1 AND (Activa=0 OR Activa=1)) AS Atrasadas_Criticas_Requeridas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=1 AND Atrasada=1 AND Activa=1 AND Compromiso>0) AS Atrasadas_Criticas_Comprometidas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=0 AND Atrasada=1 AND (Activa=0 OR Activa=1)) AS Atrasadas_No_Criticas_Requeridas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Critica=0 AND Atrasada=1 AND Activa=1 AND Compromiso>0) AS Atrasadas_No_Criticas_Comprometidas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND (Activa=0 OR Activa=1)) AS Requeridas,

        (SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Prog_Sin_Restricciones_100=1 AND Compromiso>0) AS Comprometidas_Sin_Restricciones";
    $resultado= mysqli_query($conexion, $query);

    if(!$resultado){
        die("Error");
    } else {
        while($data=mysqli_fetch_assoc($resultado)){
            $Criticas_Requeridas=$data["Criticas_Requeridas"];
            $Criticas_Comprometidas=$data["Criticas_Comprometidas"];
            $No_Criticas_Requeridas=$data["No_Criticas_Requeridas"];
            $No_Criticas_Comprometidas=$data["No_Criticas_Comprometidas"];
            $Atrasadas_Criticas_Requeridas=$data["Atrasadas_Criticas_Requeridas"];
            $Atrasadas_Criticas_Comprometidas=$data["Atrasadas_Criticas_Comprometidas"];
            $Atrasadas_No_Criticas_Requeridas=$data["Atrasadas_No_Criticas_Requeridas"];
            $Atrasadas_No_Criticas_Comprometidas=$data["Atrasadas_No_Criticas_Comprometidas"];
            $Requeridas=$data["Requeridas"];
            $Comprometidas_Sin_Restricciones=$data["Comprometidas_Sin_Restricciones"];

            if($Criticas_Requeridas>0){
                $P_Criticas_Comprometidas=$Criticas_Comprometidas/$Criticas_Requeridas;
                $P_Criticas_Comprometidas_val=$P_Criticas_Comprometidas*100;
            }else{
                $P_Criticas_Comprometidas='NA';
                $P_Criticas_Comprometidas_val=0;
            }

            if($No_Criticas_Requeridas>0){
                $P_No_Criticas_Comprometidas=$No_Criticas_Comprometidas/$No_Criticas_Requeridas;
                $P_No_Criticas_Comprometidas_val=$P_No_Criticas_Comprometidas*100;
            }else{
                $P_No_Criticas_Comprometidas='NA';
                $P_No_Criticas_Comprometidas_val=0;
            }

            if($Atrasadas_Criticas_Requeridas>0){
                $P_Atrasadas_Criticas_Comprometidas=$Atrasadas_Criticas_Comprometidas/$Atrasadas_Criticas_Requeridas;
                $P_Atrasadas_Criticas_Comprometidas_val=$P_Atrasadas_Criticas_Comprometidas*100;
            }else{
                $P_Atrasadas_Criticas_Comprometidas='NA';
                $P_Atrasadas_Criticas_Comprometidas_val=0;
            }

            if($Atrasadas_No_Criticas_Requeridas>0){
                $P_Atrasadas_No_Criticas_Comprometidas=$Atrasadas_No_Criticas_Comprometidas/$Atrasadas_No_Criticas_Requeridas;
                $P_Atrasadas_No_Criticas_Comprometidas_val=$P_Atrasadas_No_Criticas_Comprometidas*100;
            }else{
                $P_Atrasadas_No_Criticas_Comprometidas='NA';
                $P_Atrasadas_No_Criticas_Comprometidas_val=0;
            }

            if($Requeridas>0){
                $P_Comprometidas_Sin_Restricciones=$Comprometidas_Sin_Restricciones/$Requeridas;
                $P_Comprometidas_Sin_Restricciones_val=$P_Comprometidas_Sin_Restricciones*100;
            }else{
                $P_Comprometidas_Sin_Restricciones='NA';
                $P_Comprometidas_Sin_Restricciones_val=0;
            }
        }
        $array = array([$P_Criticas_Comprometidas_val, $P_No_Criticas_Comprometidas_val, $P_Atrasadas_Criticas_Comprometidas_val, $P_Atrasadas_No_Criticas_Comprometidas_val, $P_Comprometidas_Sin_Restricciones_val, 100],

            [$P_Criticas_Comprometidas, $P_No_Criticas_Comprometidas, $P_Atrasadas_Criticas_Comprometidas, $P_Atrasadas_No_Criticas_Comprometidas, $P_Comprometidas_Sin_Restricciones, 100]);
    }



    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo $json_codificado;
    mysqli_close($conexion);
}


function bloquear_compromisos($conexion, $semana, $fechaCierreCompromisos, $db){
    $query ="SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa=1 AND Compromiso IS NULL";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];

    if($conteo>0 || $conteo==null || $conteo==''){
        $respuesta="No_Bloqueado";
    }else{
        $query1 ="UPDATE $db"."_semanas_activas SET Semanal_Confirmada=1, fechaCierreCompromisos=$fechaCierreCompromisos WHERE Semana=$semana";
        $resultado1= mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        } else {
          $respuesta="Bloqueado";
          generarCIC($conexion, $semana, $db);
        }
    }


    $json_codificado = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    echo $json_codificado;
    mysqli_close($conexion);
}

function generarCIC($conexion, $semana, $db){
  for($semanaCiclo = 1; $semanaCiclo < ($semana+1); $semanaCiclo++){
    $query="SELECT  COUNT(*) FROM $db"."_cic WHERE (Semana=$semanaCiclo)";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo>0){
        actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo);
    }

    //require ("../conexion.php");
    $query1="SELECT * FROM $db"."_cic WHERE (Semana=$semanaCiclo)";
    $resultado1= mysqli_query($conexion, $query1);
    $script_subcontratistas="";
    while ($data1 = mysqli_fetch_assoc($resultado1)){
        $subcontratista=$data1["subcontratista"];
        $script_subcontratistas .="AND Sub_Contratista != '$subcontratista' ";
        //echo $script_subcontratistas;
    }
    generar_subcontratistas($semanaCiclo, $db, $conexion, $conteo, $script_subcontratistas);
  }
}

function generar_subcontratistas($semanaCiclo, $db, $conexion, $conteo, $script_subcontratistas){
    //require ("../conexion.php");
    $query2="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo $script_subcontratistas  AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA') ";
    // AND (PAC='1' OR PAC='0')
    //echo $query2;
    $resultado2= mysqli_query($conexion, $query2);
    while($data2=mysqli_fetch_assoc($resultado2)){
        $subcontratista=$data2["Sub_Contratista"];
        $query3="INSERT INTO $db"."_cic (Semana, subcontratista) VALUES (0, '$subcontratista');";
        //echo $query3;
        $resultado3= mysqli_query($conexion, $query3);
    }


    actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo1=0);
    actualizar_integral_subcontratistas($semanaCiclo, $db, $conexion);

}

function actualizar_PAC_subcontratistas($semanaCiclo, $db, $conexion, $semanaCiclo1){
    $query3 ="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')";
        $resultado3= mysqli_query($conexion, $query3);
        //$conteo1=mysqli_num_rows($resultado3);
        //echo $conteo1;
        $script ="";
        while($data1=mysqli_fetch_assoc($resultado3)){
            $subcontratista = $data1['Sub_Contratista'];
            $query4="SELECT (SELECT ROUND((SUM(P_Completado)/COUNT(P_Completado)),3) FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'P_Completado', (SELECT ROUND((SUM(PAC)/COUNT(PAC)),3) FROM $db"."_programacion_semanal WHERE Semana=$semanaCiclo AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'PAC'";
            //echo $query4;
            $resultado4= mysqli_query($conexion, $query4);
            $data2=mysqli_fetch_assoc($resultado4);
            $PAC=$data2["PAC"];
            $P_Completado=$data2["P_Completado"];
            //echo $PAC, $P_Completado;
            if($subcontratista == "AIA (MO Directa)"){
              $query5 ="UPDATE $db"."_cic SET
                  P_Completado = '$P_Completado',

                  PAC = '$PAC',

                  Semana = $semanaCiclo, correo_contacto = null, NIT = null, alcance = null, tipo_proveedor = 'Mano de Obra' WHERE subcontratista = '$subcontratista'  AND Semana=$semanaCiclo1;";
            }else{
              $query5 ="UPDATE $db"."_cic INNER JOIN $db"."_subcontratistas ON $db"."_cic . subcontratista = $db"."_subcontratistas . subcontratista SET
                  $db"."_cic . P_Completado = '$P_Completado',

                  $db"."_cic . PAC = '$PAC',

                  $db"."_cic . Semana = $semanaCiclo, $db"."_cic . correo_contacto = $db"."_subcontratistas . correo_contacto, $db"."_cic . NIT = $db"."_subcontratistas . NIT, $db"."_cic . alcance = $db"."_subcontratistas . alcance, $db"."_cic . tipo_proveedor = $db"."_subcontratistas . tipo_proveedor WHERE $db"."_cic . subcontratista = '$subcontratista'  AND Semana=$semanaCiclo1;";
            }



            $resultado5= mysqli_query($conexion, $query5);


            //echo $query5 ."<br>" /*. $query4 ."<br>"*/;

            $script .="AND subcontratista != '$subcontratista' ";
        }

        $query6="DELETE FROM $db"."_cic WHERE Semana=$semanaCiclo $script";
        //echo $query6 ."<br>";
        $resultado6= mysqli_query($conexion, $query6);

        mysqli_free_result($resultado3);

        //mysqli_close($conexion);


}

function actualizar_integral_subcontratistas($semanaCiclo, $db, $conexion){
    //require("../conexion.php");
    $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semanaCiclo;";
    $resultado5= mysqli_query($conexion, $query5);

    while ($cic = mysqli_fetch_assoc($resultado5)){
      $Id=$cic['Id'];
      $subcontratista=$cic['subcontratista'];

      $query6 ="SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND PAC!='NA') END) AS 'PAC_Acum',";

      $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND P_Completado!='NA') END) AS 'P_Completado_Acum',";

      $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR') END) AS 'Calidad_Acum',";

      $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR') END) AS 'GSA_Acum',";

      $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR') END) AS 'SST_Acum',";

      $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semanaCiclo AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR') END) AS 'ADM_Acum'";

      $resultado6= mysqli_query($conexion, $query6);

      $data=mysqli_fetch_assoc($resultado6);
      $PAC_Acum=$data["PAC_Acum"];
      $P_Completado_Acum=$data["P_Completado_Acum"];
      $Calidad_Acum=$data["Calidad_Acum"];
      $GSA_Acum=$data["GSA_Acum"];
      $SST_Acum=$data["SST_Acum"];
      $ADM_Acum=$data["ADM_Acum"];



      $query6_1 = "UPDATE $db"."_cic SET
          PAC_Acum = '$PAC_Acum',

          P_Completado_Acum = '$P_Completado_Acum',

          Calidad_Acum = '$Calidad_Acum',

          GSA_Acum = '$GSA_Acum',

          SST_Acum = '$SST_Acum',

          ADM_Acum = '$ADM_Acum'

          WHERE Id=$Id";

      $resultado6_1= mysqli_query($conexion, $query6_1);


      $query7 ="SELECT * FROM $db"."_cic WHERE Id=$Id;";
      $resultado7= mysqli_query($conexion, $query7);
      $cic1 = mysqli_fetch_assoc($resultado7);

      $PAC=$cic1['PAC'];
      $PAC_acum=$cic1['PAC_Acum'];
      $calidad=$cic1['Calidad'];
      $calidad_acum=$cic1['Calidad_Acum'];
      $adm=$cic1['ADM'];
      $adm_acum=$cic1['ADM_Acum'];
      $gsa=$cic1['GSA'];
      $gsa_acum=$cic1['GSA_Acum'];
      $sst=$cic1['SST'];
      $sst_acum=$cic1['SST_Acum'];

      if($PAC == ""){
        $cal_integral = "NULL";
      }else{
        if($calidad=='NA' || $calidad=='NR'){
            if($sst=='NA' || $sst=='NR'){
                if($gsa=='NA' || $gsa=='NR'){
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa=='NA' || $gsa=='NR'){
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst=='NA' || $sst=='NR'){
                if($gsa=='NA' || $gsa=='NR'){
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa=='NA' || $gsa=='NR'){
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm=='NA' || $adm=='NR'){
                        $cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
                    }
                }
            }
        }
      }


      if($PAC_Acum == ""){
        $cal_integral_acum = "NULL";
      }else{
        if($calidad_acum=='NA' || $calidad_acum=='NR'){
            if($sst_acum=='NA' || $sst_acum=='NR'){
                if($gsa_acum=='NA' || $gsa_acum=='NR'){
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA' || $gsa_acum=='NR'){
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst_acum=='NA' || $sst_acum=='NR'){
                if($gsa_acum=='NA' || $gsa_acum=='NR'){
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA' || $gsa_acum=='NR'){
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm_acum=='NA' || $adm_acum=='NR'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                    }
                }
            }
        }
      }
      //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;
      $query7 = "UPDATE $db"."_cic SET Cal_Integral = $cal_integral, Cal_Integral_Acum = $cal_integral_acum WHERE Id=$Id;";
      // echo $query7;
      $resultado7= mysqli_query($conexion, $query7);
  }
}



function importar_actividad_no_requerida($conexion, $semana, $db, $Consecutivo){
    $query ="SELECT Actividad, Responsable_AIA, Sub_Contratista, unidad FROM $db"."_programa_consolidado WHERE Semana=$semana AND Id='$Consecutivo'";
    $resultado= mysqli_query($conexion, $query);

    if(!$resultado){
        die("Error");
    } else {
        $data=mysqli_fetch_assoc($resultado);
        $Actividad=$data["Actividad"];
        // $Actividad=str_replace("<small>","",$Actividad);
        // $Actividad=str_replace("</small>","",$Actividad);
        // $Actividad=str_replace("<b>","",$Actividad);
        // $Actividad=str_replace("</b>","",$Actividad);
        $Actividad=utf8_decode($Actividad);
        $data["Actividad"] = $Actividad;
        $arreglo["data"]=array_map("utf8_encode", $data);
    }


    $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    echo $json_codificado;
    mysqli_close($conexion);
}



function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}
?>
