<?php

require("../conexion.php");

$db=/*"camino_verde"*/$_GET['db'];
$opcion=/*"restricciones"*/$_POST["opcion"];
$informacion=[];

/*if ($opcion == "modificar") {
    $Id='2';
    $D_y_E= 1;
    $Materiales=0.33333 ;
    $MdeO= 0.66666;
    $Equipos= 0;
    $Predecesora= 1;
    $Pdto_Cons= 0;
    $Modelo= "0";
    $Responsable_AIA= "Mauricio Martinez" ;
    $Observaciones= "Hola Mundo" ;
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime("2019-11-26"));;
};*/

if ($opcion == "modificar") {
    $semana=/*2*/$_GET["semana"];
    $Id=/*28*/$_POST["Id"];
    $D_y_E=/*1*/$_POST["D_y_E"];
    $Materiales=/*1*/$_POST["Materiales"];
    $MdeO=/*"N/A"*/$_POST["MdeO"];
    $Equipos=/*0.66*/$_POST["Equipos"];
    $Predecesora=/*"N/A"*/$_POST["Predecesora"];
    $Pdto_Cons=/*1*/$_POST["Pdto_Cons"];
    $Modelo=/*1*/$_POST["Modelo"];
    if($D_y_E=="N/A"){
    }else{
        $D_y_E=round($D_y_E*100/100,2);
    }
    if($Materiales=="N/A"){
    }else{
        $Materiales=round($Materiales*100/100,2);
    }
    if($MdeO=="N/A"){
    }else{
        $MdeO=round($MdeO*100/100,2);
    }
    if($Equipos=="N/A"){
    }else{
        $Equipos=round($Equipos*100/100,2);
    }
    if($Predecesora=="N/A"){
    }else{
        $Predecesora=round($Predecesora*100/100,2);
    }
    if($Pdto_Cons=="N/A"){
    }else{
        $Pdto_Cons=round($Pdto_Cons*100/100,2);
    }
    if($Modelo=="N/A"){
    }else{
        $Modelo=round($Modelo*100/100,2);
    }
    $Sub_Contratista=/*'Sergio Rendon'*/$_POST["Sub_Contratista"];
    $Responsable_AIA=/*'Sergio Rendon'*/$_POST["Responsable_AIA"];
    $Observaciones=/*'ñ'*/$_POST["Observaciones"];
}else if($opcion=="restricciones"){
    $semana=$_GET["semana"];
    $nombre=/*"consolidado general"*/$_POST["nombre"];
};
//echo $D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Responsable_AIA, $Observaciones, $Id;




switch($opcion){
    case 'modificar':
        modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana, fecha_inicio_sem($semana, $db, $conexion), $db, $conexion);
        break;
}



function modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana, $inicio_semana, $db, $conexion){

    //echo $inicio_semana;

    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET D_y_E='$D_y_E', Materiales='$Materiales', MdeO='$MdeO', Equipos='$Equipos', Predecesora='$Predecesora', Pdto_Cons='$Pdto_Cons', Modelo='$Modelo', Sub_Contratista='$Sub_Contratista', Responsable_AIA='$Responsable_AIA', Observaciones='$Observaciones' WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";

    //echo $query ."<br>". $query1;

    $resultado= mysqli_query($conexion, $query);
    //require("../conexion.php");
    $resultado1= mysqli_query($conexion, $query1);
    modificar_rest($Id, $semana, $inicio_semana, $db, $conexion);
    //require("../conexion.php");
    modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion);
}
function modificar_rest($Id, $semana, $inicio_semana, $db, $conexion){
    //require("../conexion.php");
    $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_Programa='$Id'";
    $resultado= mysqli_query($conexion, $query2);
	if(!$resultado){
        die("Error");
    } else{
        $query3="";
        while($data=mysqli_fetch_array($resultado)){
            $Id=$data["Consecutivo_en_Programa"];
            $D_y_E2=$data["D_y_E"];
            $Materiales2=$data["Materiales"];
            $MdeO2=$data["MdeO"];
            $Equipos2=$data["Equipos"];
            $Predecesora2=$data["Predecesora"];
            $Pdto_Cons2=$data["Pdto_Cons"];
            $Modelo2=$data["Modelo"];
            $conteo=0;
            $suma=0;
            if($D_y_E2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($D_y_E2 , 5);
            }
            if($Materiales2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Materiales2 , 5);
            }
            if($MdeO2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($MdeO2 , 5);
            }
            if($Equipos2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Equipos2 , 5);
            }
            if($Predecesora2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Predecesora2 , 5);
            }
            if($Pdto_Cons2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Pdto_Cons2 , 5);
            }
            if($Modelo2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Modelo2 , 5);
            }
            //echo $conteo . "<br>" . $suma;
            if($conteo==0){
                $Estado_Restricciones=1;
            }else{
                $Estado_Restricciones=round(($suma/$conteo),5);
            }

            $query3 .="UPDATE $db"."_programa_consolidado SET Estado_Restricciones=$Estado_Restricciones WHERE Consecutivo_en_Programa='$Id' AND Titulo=0 AND Semana=$semana;";

        }
        //echo $query3;
    };
    //require("../conexion.php");
    $resultado=mysqli_multi_query($conexion, $query3);
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
    $resultado=mysqli_multi_query($conexion, $query);
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
        //echo $inicio_semana;
    }


    return $inicio_semana;
}





?>
