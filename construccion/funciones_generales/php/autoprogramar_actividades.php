<?php

// require ("../conexion.php");
// $db="cedi_pasto";
// $semana=35;


$query="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
//echo "$query <br>" ;
$resultado= mysqli_query($conexion, $query);
$data=mysqli_fetch_assoc($resultado);
$Fecha_Inicio_Sem=date("Y-m-d",strtotime($data["Fecha_Inicio_Sem"]));
$Fecha_Fin_Sem=date("Y-m-d",strtotime($data["Fecha_Fin_Sem"]));
mysqli_free_result($resultado);
//echo "$inicio_sem <br> $fin_sem <br>" ;

$query1="SELECT DISTINCT(Consecutivo_En_Programa) FROM $db"."_programacion_semanal WHERE Semana=$semana";
$resultado1= mysqli_query($conexion, $query1);
$script1="";
while ($data1=mysqli_fetch_assoc($resultado1)){
    $Consecutivo_En_Programa=$data1["Consecutivo_En_Programa"];
    $script1 .="AND Consecutivo_en_Programa!=$Consecutivo_En_Programa ";
}
mysqli_free_result($resultado1);

$query1_1 = "INSERT INTO $db"."_programacion_semanal (Semana, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad, Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad) SELECT
$semana,
Consecutivo_en_Programa,
Id,
Actividad,
Fecha_Inicio,
Fecha_Fin,
Sub_Contratista,
Responsable_AIA,
'AIA',
Ejecutado,
medir_productividad,
Ruta_Critica,
CASE WHEN (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END,
'1',
unidad,
cantidad_ppto,
codigo_actividad


FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND (Estado='A Tiempo' OR Estado='Atrasada' OR Estado='Debe Iniciar Esta Semana' OR Estado='Ya Debió Iniciar y Restricciones Pendientes' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes') $script1";

//AND Estado_Restricciones=1

//echo "$query1_1 <br>";
$resultado1_1 = mysqli_query($conexion, $query1_1);

$query1_2="SELECT Consecutivo_En_Programa, Ejecutado FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa != 'NA'";
$resultado1_2= mysqli_query($conexion, $query1_2);
$query1_6="";

sleep(1);

while ($data1_2=mysqli_fetch_assoc($resultado1_2)){
    $Consecutivo_En_Programa=$data1_2["Consecutivo_En_Programa"];
    $query1_3="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_programa=$Consecutivo_En_Programa";
    $resultado1_3= mysqli_query($conexion, $query1_3);
    $data1_3=mysqli_fetch_assoc($resultado1_3);
    $Fecha_Inicio_Act=date("Y-m-d",strtotime($data1_3['Fecha_Inicio']));
    $Fecha_Fin_Act=date("Y-m-d",strtotime($data1_3['Fecha_Fin']));

    if (floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)>=0 && floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)>=floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $Ejecutado_Fin_Semana= ((floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)+1) / (floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1));
    }else if(floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)<floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $Ejecutado_Fin_Semana=1;
    }else{
        $Ejecutado_Fin_Semana=0;
    }

    $Sub_Contratista=$data1_3["Sub_Contratista"];
    $Responsable_AIA=$data1_3["Responsable_AIA"];
    $Ejecutado=$data1_3["Ejecutado"];
    $medir_productividad=is_null($data1_3["medir_productividad"]) ? 0 : $data1_3["medir_productividad"];
    $Critica=$data1_3["Ruta_Critica"];
    $Estado=$data1_3["Estado"];
    $Unidad=$data1_3["unidad"];
    $cantidad_ppto=$data1_3["cantidad_ppto"];
    $codigo_actividad=$data1_3["codigo_actividad"];

    if(!$cantidad_ppto){
        $Compromiso=round((($Ejecutado_Fin_Semana - $Ejecutado)*100),1);
        $cantidad_ppto="NULL";
        //echo "<li> $Compromiso + $cantidad_ppto";
    }else{
        $Compromiso=round((($Ejecutado_Fin_Semana - $Ejecutado) * $cantidad_ppto),1);
        //echo "<li> $Compromiso + $cantidad_ppto";
    }


    $query1_4="SELECT * FROM $db"."_programacion_semanal WHERE Semana=($semana-1) AND Consecutivo_En_programa=$Consecutivo_En_Programa";
    $resultado1_4= mysqli_query($conexion, $query1_4);
    $data1_4=mysqli_fetch_assoc($resultado1_4);
    if(!$data1_4){
        $Descripcion=null;
        $Ubicacion=null;
        $Empresa='AIA';

    }else{
        $Descripcion=$data1_4["Descripcion"];
        $Ubicacion=$data1_4["Ubicacion"];
        if($Sub_Contratista=='' || $Sub_Contratista==null){
            $Sub_Contratista=$data1_4["Sub_Contratista"];
        }
        if($Responsable_AIA=='' || $Responsable_AIA==null){
            $Responsable_AIA=$data1_4["Responsable_AIA"];
        }
        $Empresa=$data1_4["Empresa"];
    }




    $query1_5="SELECT * FROM $db"."_programacion_semanal WHERE Semana=($semana) AND Consecutivo_En_programa=$Consecutivo_En_Programa";
    $resultado1_5= mysqli_query($conexion, $query1_5);
    $data1_5=mysqli_fetch_assoc($resultado1_5);
    $Compromiso1=$data1_5["Compromiso"];
    $Activa=$data1_5["Activa"];
    $cantidad_ppto1=$data1_5["cantidad_ppto"];
    $Ejecutado1=$data1_5["Ejecutado"];
    if(!$data1_5){
        $Descripcion=null;
        $Ubicacion=null;
        $Empresa='AIA';

    }else{
        $Descripcion=$data1_5["Descripcion"];
        $Ubicacion=$data1_5["Ubicacion"];
        if($Sub_Contratista != $data1_5["Sub_Contratista"] && $data1_5["Sub_Contratista"] != ''){
            $Sub_Contratista=$data1_5["Sub_Contratista"];
        }
        if($Responsable_AIA != $data1_5["Responsable_AIA"] && $data1_5["Responsable_AIA"] != ''){
            $Responsable_AIA=$data1_5["Responsable_AIA"];
        }
        if($Empresa != $data1_5["Empresa"] && $data1_5["Empresa"] != ''){
            $Empresa=$data1_5["Empresa"];
        }
        if($Unidad != $data1_5["Unidad"] && $data1_5["Unidad"] != ''){
            $Unidad=$data1_5["Unidad"];
        }

        //OJO CON ESTA PARTE
        //echo "<li>$Consecutivo_En_Programa -> $Ejecutado1 vs $Ejecutado";
        if($Ejecutado1 != $Ejecutado){
            $Compromiso = $Compromiso;
        }/*else if($cantidad_ppto1 != $cantidad_ppto){
            $Compromiso = $Compromiso;
        }*/else if($Compromiso1 != $Compromiso && $Compromiso1 != null){
            $Compromiso= $Compromiso1;
        }

        if($Compromiso<=0){
            $Compromiso='NULL';
        }
    }










    $query1_6 .="UPDATE $db"."_programacion_semanal SET

    Fecha_Inicio = '$Fecha_Inicio_Act',
    Fecha_Fin = '$Fecha_Fin_Act',
    Sub_Contratista = '$Sub_Contratista',
    Responsable_AIA = '$Responsable_AIA',
    Ejecutado = $Ejecutado,
    medir_productividad = $medir_productividad,
    Critica = $Critica,
    Atrasada = (CASE WHEN '$Estado'='Atrasada' THEN 1 ELSE 0 END),
    Descripcion='$Descripcion',
    Ubicacion='$Ubicacion',
    Sub_Contratista='$Sub_Contratista',
    Empresa='$Empresa',
    Unidad='$Unidad',
    cantidad_ppto=$cantidad_ppto,
    Activa=$Activa,
    codigo_actividad='$codigo_actividad'


    WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa;";

    //Compromiso=$Compromiso,
    //echo "<br><br>$query1_6";


}

//echo "$query1_6 <br>";

mysqli_free_result($resultado1_2);
mysqli_free_result($resultado1_3);
mysqli_free_result($resultado1_4);
mysqli_free_result($resultado1_5);
$resultado1_6= mysqli_multi_query($conexion, $query1_6);

mysqli_close($conexion);
require("../conexion.php");
sleep(1);

$query1_7="SELECT Consecutivo_en_Programa FROM $db"."_programa_consolidado WHERE Semana=$semana AND Ejecutado=0 AND Semanas_Inicio>0 AND Activa != 'NA'";
$resultado1_7= mysqli_query($conexion, $query1_7);
$script1_7="";
while ($data1_7=mysqli_fetch_assoc($resultado1_7)){
    $Consecutivo_en_Programa=$data1_7["Consecutivo_en_Programa"];
    $script1_7 .="OR Consecutivo_en_Programa=$Consecutivo_en_Programa ";
}
mysqli_free_result($resultado1_7);
//$script1_7=substr($script1_7, 2);


$query1_8="DELETE FROM $db"."_programacion_semanal WHERE Semana=$semana AND ((Ejecutado=1 AND Activa != 'NA') $script1_7)";

// echo $query1_8;

$resultado1_8= mysqli_query($conexion, $query1_8);
if(!$resultado1_8){
    die("Error");
} else{
  $query1_9="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana";
  //echo "$query2 <br>" ;
  $resultado1_9= mysqli_query($conexion, $query1_9);
  $data1_9=mysqli_fetch_assoc($resultado1_9);
  $conteo=$data1_9["COUNT(*)"];
  //echo "$conteo <br>" ;
  if ($conteo==0){
  }else{
    $query1_10="SELECT * FROM $db"."_programacion_semanal WHERE Semana=$semana";
    $resultado1_10= mysqli_query($conexion, $query1_10);
    $query1_11="";
    while ($data1_10=mysqli_fetch_assoc($resultado1_10)){
      $Consecutivo_En_Programa=$data1_10["Consecutivo_En_Programa"];
      $Activa=$data1_10["Activa"];
      $Ejecutado1_10=round($data1_10["Ejecutado"],0);
      //echo "<li> $Consecutivo_En_Programa -> $Ejecutado1";
      if($Activa!='NA'){
        $query1_11 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=(SELECT CASE WHEN $db"."_programa_consolidado . Estado_Restricciones<1 THEN 1 ELSE 0 END FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_Programa=$Consecutivo_En_Programa), Ejecutado = (SELECT Ejecutado FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa) WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa AND Activa!='NA';";

          /*,  Compromiso = CASE WHEN $Ejecutado1 != ROUND((SELECT Ejecutado FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa),0) THEN NULL ELSE Compromiso END,*/

      }else{
        $query1_11 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=0 WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa;";
      }

        //echo $query3;
    };

    //echo $query3;
    $resultado1_11 = mysqli_multi_query($conexion, $query1_11);
    if(!$resultado1_8){
      die("Error");
    } else{
      $json_codificado = json_encode("OK", JSON_UNESCAPED_UNICODE);
      echo utf8_decode($json_codificado);
      sleep(1);
    }
  }
}

mysqli_close($conexion);

?>
