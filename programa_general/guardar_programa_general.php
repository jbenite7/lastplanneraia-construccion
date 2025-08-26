<?php
require("../conexion.php");

$db=/*"cross"*/$_GET['db'];
$opcion=/*"nueva_sem"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar" || $opcion == "registrar") {

    $Id=$_POST["Id"];
    if($_POST["Ejecutado"] == "Nulo"){
      $Ejecutado="NULL";
    }else{
      $Ejecutado=$_POST["Ejecutado"];
    }
    $codigo_actividad =$_POST["codigo_actividad"];
    $unidad =$_POST["unidad"];

    if($_POST["cantidad_ppto"] == ""){
      $cantidad_ppto="NULL";
    }else{
      $cantidad_ppto=$_POST["cantidad_ppto"];
    }
    $editarActividadAsociar=$_POST["editarActividadAsociar"];
    if(!$_POST["actividadAsociar"] || $_POST["actividadAsociar"] == ""){
      $actividadAsociar="'*No Asociada*'";
    }else{
      $actividadAsociar="'" . $_POST["actividadAsociar"] . "'";
    }

    $semana=$_GET["semana"];
    $Fecha_Inicio=date("Y-m-d",strtotime($_POST["Fecha_Inicio"]));
    $Fecha_Fin=date("Y-m-d",strtotime($_POST["Fecha_Fin"]));

} else if ($opcion=="modificargrupo"){
    $Id=$_POST["Id"];
    $script=utf8_decode($_POST["Id"]);
    $script1=utf8_decode($_POST["Id1"]);
    $Ejecutado = $_POST["Ejecutado"];
    $semana=$_GET["semana"];
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime(/*"2020-01-03"*/$_POST["f_inicio_sem"]));
}else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
}else if($opcion=="cargar_unidad"){
    $codigo_actividad=$_POST["codigo_actividad"];
};

switch($opcion){
    case 'modificar':
        modificar($Ejecutado, $codigo_actividad, $unidad, $cantidad_ppto, $Id, $semana, $Fecha_Inicio, $Fecha_Fin, fecha_inicio_sem($semana, $db, $conexion), $actividadAsociar, $editarActividadAsociar, $db, $conexion);
        break;

    case 'modificargrupo':
        modificargrupo($Ejecutado, $script, $script1, $semana, fecha_inicio_sem($semana, $db, $conexion), $db, $conexion);
        break;

    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;

    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;

    case 'cargar_unidad':
        cargar_unidad($codigo_actividad, $conexion);
        break;
}

function modificar($Ejecutado, $codigo_actividad, $unidad, $cantidad_ppto, $Id, $semana, $Fecha_Inicio, $Fecha_Fin, $inicio_semana, $actividadAsociar, $editarActividadAsociar, $db, $conexion){
    if(empty($cantidad_ppto) || $cantidad_ppto=='' || $cantidad_ppto==0){
        $cantidad_ppto="NULL";
    }
    if(empty($codigo_actividad) || $codigo_actividad=='' || $codigo_actividad==null){
        $medir_productividad=0;
    }else{
        $medir_productividad=1;
        $query_="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
        $resultado_= mysqli_query($conexion, $query_);
        $data_=mysqli_fetch_assoc($resultado_);
        $unidad=$data_["unidad"];
    }

    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana;";
    if($editarActividadAsociar == 0){
      $query1 = "UPDATE $db"."_programa_consolidado SET Ejecutado=$Ejecutado, medir_productividad=$medir_productividad, unidad='$unidad', cantidad_ppto= $cantidad_ppto, codigo_actividad= '$codigo_actividad', Ejecutado_Siguiente_Semana=$Ejecutado, Fecha_Inicio='$Fecha_Inicio', Fecha_Fin='$Fecha_Fin' WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";
    }else{
      $query1 = "UPDATE $db"."_programa_consolidado SET Ejecutado=$Ejecutado, medir_productividad=$medir_productividad, unidad='$unidad', cantidad_ppto= $cantidad_ppto, codigo_actividad= '$codigo_actividad', Ejecutado_Siguiente_Semana=$Ejecutado, Fecha_Inicio='$Fecha_Inicio', Fecha_Fin='$Fecha_Fin', programaAnteriorAsociar=$actividadAsociar WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";
    }

    $resultado= mysqli_query($conexion, $query);
    // echo $query1;
    //cerrar($conexion);
    //require("../conexion.php");
    $resultado1= mysqli_query($conexion, $query1);
    verificar_resultado($resultado1);
    //cerrar($conexion);
    //require("../conexion.php");
    modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion);
    //cerrar($conexion);
}
function modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion){

    $fin_semana= date("Y-m-d",strtotime("$inicio_semana + 6 days"));

    $query = "UPDATE $db"."_programa_consolidado SET
       Estado= CASE
          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) < 0 THEN 'Terminada Antes'

          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) = 0 THEN 'Terminada'

          WHEN Ejecutado < 1 AND Ejecutado >= 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 THEN 'Atrasada'

          WHEN Ejecutado < 1 AND Ejecutado > 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) <= 0 THEN 'A Tiempo'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 AND Ejecutado=0 THEN 'Ya Debió Iniciar y Restricciones Pendientes'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana y Restricciones Pendientes'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado = 0 THEN 'En Liberación de Restricciones'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado > 0 THEN 'A Tiempo'

          ELSE 'No Requerida'
       END
      WHERE Titulo=0 AND Consecutivo_en_Programa='$Id' AND Semana=$semana";
    //echo $query;
    $resultado=mysqli_query($conexion, $query);
    mysqli_close($conexion);
}

function modificargrupo($Ejecutado, $script, $script1, $semana, $inicio_semana, $db, $conexion){
    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE $script1 AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET Ejecutado=$Ejecutado WHERE $script1 AND Semana=$semana";
    $resultado= mysqli_multi_query($conexion, $query);
    $resultado1= mysqli_multi_query($conexion, $query1);
    verificar_resultado($resultado);
    //require("../conexion.php");

    $fin_semana= date("Y-m-d",strtotime("$inicio_semana + 6 days"));
    $query2 = "UPDATE $db"."_programa_consolidado SET
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'OK'
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado<1 THEN 'Atrasada'
                                                    WHEN (Fecha_Inicio<'$fin_semana') AND Ejecutado<1 AND Estado_Restricciones<1 THEN 'Restricciones Pendientes para Iniciar'
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes'
                                                    ELSE 'NI'
                                                 END
                                                WHERE Titulo=0 AND $script1 AND Semana=$semana
                                                ";
    //echo $query;
    $resultado2=mysqli_multi_query($conexion, $query2);
    mysqli_close($conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    //mysqli_close($conexion);
    //require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}


function eliminar_sem($semana, $db, $conexion){
    require("../funciones_generales/eliminar_semana.php");
}

function cargar_unidad($codigo_actividad, $conexion){
    if(empty($codigo_actividad) || $codigo_actividad=='' || $codigo_actividad==null){
        $unidad='';
    }else{
        $query="SELECT * FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $unidad=$data["unidad"];
    }
    $resultado=array($unidad);

    mysqli_close($conexion);

    echo json_encode($resultado);
}

function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}

function fecha_inicio_sem($semana, $db, $conexion){
    //require("../conexion.php");
    $query="SELECT COUNT(*) FROM $db"."_semanas_activas";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];

    if($conteo==0){
        $inicio_semana=date("Y-m-d");

    }else{
        $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
        $resultado1= mysqli_query($conexion, $query1);
        $data1=mysqli_fetch_assoc($resultado1);
        $inicio_semana=$data1["Fecha_Inicio_Sem"];
    }


    return $inicio_semana;
}
?>
