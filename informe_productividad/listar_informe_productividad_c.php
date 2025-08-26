<?php

require ("../conexion.php");
// $db=$_GET['db'];
// $opcion=$_POST["opcion"];
// $semana=$_GET["semana"];
// $codigo_actividad=$_POST["codigo_actividad"];

$db="camino_verde";
$opcion="Seguimiento_Ejecucion_Proyectado";
$semana=11;
$codigo_actividad="0000011";


switch($opcion){

    case 'Seguimiento_Ejecucion':
    grafico_Seguimiento_Ejecucion($conexion, $semana, $db, $codigo_actividad);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Ejecucion_Proyectado':
    grafico_Seguimiento_Ejecucion_Proyectado($conexion, $semana, $db, $codigo_actividad);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Rendimientos':
    $oficiales_teorico=$_POST["oficiales_teorico"];
    $ayudantes_teorico=$_POST["ayudantes_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    $rendimiento_cuadrilla_tipica_teorico=$_POST["rendimiento_cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Rendimientos($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $ayudantes_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Cuadrillas_Tipicas':
    $oficiales_teorico=$_POST["oficiales_teorico"];
    $ayudantes_teorico=$_POST["ayudantes_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Cuadrillas_Tipicas($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $ayudantes_teorico, $cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Oficiales':
    $oficiales_teorico=$_POST["oficiales_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Oficiales($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Ayudantes':
    $ayudantes_teorico=$_POST["ayudantes_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Ayudantes($conexion, $semana, $db, $codigo_actividad, $ayudantes_teorico, $cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Consumo_Oficiales':
    $oficiales_teorico=$_POST["oficiales_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    $rendimiento_cuadrilla_tipica_teorico=$_POST["rendimiento_cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Consumo_Oficiales($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'Seguimiento_Consumo_Ayudantes':
    $ayudantes_teorico=$_POST["ayudantes_teorico"];
    $cuadrilla_tipica_teorico=$_POST["cuadrilla_tipica_teorico"];
    $rendimiento_cuadrilla_tipica_teorico=$_POST["rendimiento_cuadrilla_tipica_teorico"];
    grafico_Seguimiento_Consumo_Ayudantes($conexion, $semana, $db, $codigo_actividad, $ayudantes_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico);
    mysqli_close($conexion);
    break;

    case 'unidad_actividad':
    unidad_actividad($conexion, $semana, $db, $codigo_actividad);
    mysqli_close($conexion);
    break;

    case 'importar_cuadrilla_tipica':
    importar_cuadrilla_tipica($conexion, $semana, $db, $codigo_actividad);
    mysqli_close($conexion);
    break;

    case 'exportar_cuadrilla_tipica':
    $oficiales_tipica = $_POST["oficiales_tipica"];
    $ayudantes_tipica = $_POST["ayudantes_tipica"];
    $rendimiento_tipica = $_POST["rendimiento_tipica"];
    $numero_cuadrillas_tipicas = $_POST["numero_cuadrillas_tipicas"];
    exportar_cuadrilla_tipica($conexion, $semana, $db, $codigo_actividad, $oficiales_tipica, $ayudantes_tipica, $rendimiento_tipica, $numero_cuadrillas_tipicas);
    mysqli_close($conexion);
    break;
}


function grafico_Seguimiento_Ejecucion($conexion, $semana, $db, $codigo_actividad){

    $query_1 = "SELECT MIN(Fecha_Inicio), MAX(Fecha_Fin) FROM $db"."_programa_consolidado WHERE /*Semana<=$semana AND*/ codigo_actividad='$codigo_actividad'";

    $resultado_1=mysqli_query($conexion, $query_1);
    $data_1=mysqli_fetch_assoc($resultado_1);

    $MIN_Fecha_Inicio=date("Y-m-d",strtotime($data_1["MIN(Fecha_Inicio)"]));
    $MAX_Fecha_Fin=date("Y-m-d",strtotime($data_1["MAX(Fecha_Fin)"]));

    //echo "<li> $MIN_Fecha_Inicio / $MAX_Fecha_Fin";
    $query_2 = "SELECT MIN(Semana) FROM $db"."_programa_consolidado WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad'";
    $resultado_2=mysqli_query($conexion, $query_2);
    $data_2=mysqli_fetch_assoc($resultado_2);
    $MIN_Semana=$data_2["MIN(Semana)"];
    $query = "SELECT * FROM $db"."_semanas_activas WHERE Semana<=$semana AND Semana>=$MIN_Semana";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1 ="CREATE TABLE $db"."_tasas_de_produccion ( `Id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `Semana` INT(11) NOT NULL , `Cantidad_Ejecutada` FLOAT(5) NOT NULL DEFAULT '0' , `Cantidad_Acumulada` FLOAT(5) NOT NULL DEFAULT '0', `Cantidad_Teorica_Acumulada` FLOAT(5) NOT NULL DEFAULT '0') ENGINE = INNODB;";

        $resultado1 = mysqli_query($conexion, $query1);

        $query2 = "INSERT INTO $db"."_tasas_de_produccion (Semana, Cantidad_Ejecutada, Cantidad_Teorica_Acumulada) VALUES ";

        $query3 = "";

        while($data=mysqli_fetch_assoc($resultado)){
            $Semana_tabla=$data["Semana"];
            $Fecha_Inicio_Sem=date("Y-m-d",strtotime($data["Fecha_Inicio_Sem"]));
            $Fecha_Fin_Sem=date("Y-m-d",strtotime($data["Fecha_Fin_Sem"]));



            $query2 .= "($Semana_tabla,

            (SELECT
            CASE
            WHEN (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla+1) AND codigo_actividad=$codigo_actividad) IS NULL) = 1 OR (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla) AND codigo_actividad=$codigo_actividad) IS NULL) = 1

            THEN 0

            ELSE (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla+1) AND codigo_actividad=$codigo_actividad) - (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_tabla AND codigo_actividad=$codigo_actividad))
            END),

            (SELECT SUM((SELECT
            CASE
            WHEN DATEDIFF('$Fecha_Fin_Sem', Fecha_Inicio)>=0 AND DATEDIFF(Fecha_Fin, Fecha_Inicio) >= DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio) THEN (DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio)+1) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio) < DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio) THEN 1
            ELSE 0
            END) * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_tabla AND codigo_actividad='$codigo_actividad')), ";
        }

        $query2 = substr($query2, 0, -2);
        //echo "<li>" . $query2;
        $resultado2 = mysqli_query($conexion, $query2);


        $query2_1 = "INSERT INTO $db"."_tasas_de_produccion (Semana, Cantidad_Ejecutada, Cantidad_Teorica_Acumulada) VALUES ";

        $Fecha_Fin_Sem=strtotime($Fecha_Fin_Sem);
        $MAX_Fecha_Fin=strtotime($MAX_Fecha_Fin);
        $Semana_base=$Semana_tabla;
        $query_extraer_promedio_base = "SELECT AVG(Cantidad_Ejecutada), MIN(Semana) FROM $db"."_tasas_de_produccion WHERE Semana<=$Semana_base";
        $resultado_extraer_promedio_base=mysqli_query($conexion, $query_extraer_promedio_base);
        $data_extraer_promedio_base=mysqli_fetch_assoc($resultado_extraer_promedio_base);
        $query_extraer_semana_primer_registro = "SELECT MAX(Semana) FROM $db"."_programa_consolidado WHERE Semana<=$Semana_base AND codigo_actividad='$codigo_actividad' AND Ejecutado>0";
        $resultado_extraer_semana_primer_registro=mysqli_query($conexion, $query_extraer_semana_primer_registro);
        $data_extraer_semana_primer_registro=mysqli_fetch_assoc($resultado_extraer_semana_primer_registro);
        $Promedio_base=$data_extraer_promedio_base["AVG(Cantidad_Ejecutada)"];
        $Semana_primer_registro=$data_extraer_promedio_base["MIN(Semana)"];
        $Semana_primer_registro1=$data_extraer_semana_primer_registro["MAX(Semana)"];

        for($Fecha_Fin_Sem; $Fecha_Fin_Sem<$MAX_Fecha_Fin; $Fecha_Fin_Sem=strtotime(date("Y-m-d",$Fecha_Fin_Sem) . "+ 6 days")){
            $Semana_tabla=$Semana_tabla+1;
            $Fecha_Fin_Semana=date("Y-m-d",strtotime(date("Y-m-d",$Fecha_Fin_Sem) . "+ 6 days"));
            $MAX_Fecha_Final=date("Y-m-d", $MAX_Fecha_Fin);
            $query2_1 .= "($Semana_tabla,

            $Promedio_base,

            (SELECT SUM((SELECT
            CASE
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio)>=0 AND DATEDIFF(Fecha_Fin, Fecha_Inicio) >= DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio) THEN (DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio)+1) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio) < DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio) THEN 1
            ELSE 0
            END) * cantidad_ppto)

            FROM $db"."_programa_consolidado WHERE Semana=$Semana_base AND codigo_actividad='$codigo_actividad')), ";
        };


        $query2_1 = substr($query2_1, 0, -2);
        //echo "<li>" . $query2_1;
        $resultado2_1 = mysqli_query($conexion, $query2_1);
        sleep(1);


        $query3 = "UPDATE $db"."_tasas_de_produccion SET Cantidad_Acumulada=(SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_primer_registro AND codigo_actividad='$codigo_actividad') WHERE Semana=$Semana_primer_registro";

        //echo "<li>" . $query3;
        $resultado3 = mysqli_query($conexion, $query3);

        $query3_1 ="SELECT * FROM $db"."_tasas_de_produccion WHERE Semana>=$Semana_primer_registro";

        //echo "<li>" . $query3_1;
        $resultado3_1 = mysqli_query($conexion, $query3_1);

        while($data3_1=mysqli_fetch_assoc($resultado3_1)){
            $Semana=$data3_1["Semana"];
            $Cantidad_Ejecutada=$data3_1["Cantidad_Ejecutada"];
            $Cantidad_Acumulada=$data3_1["Cantidad_Acumulada"];
            $arreglo[]=array($Semana,$Cantidad_Ejecutada,$Cantidad_Acumulada);

        }


        $query3_2="";
        $Cantidad_Acumulada = $arreglo[0][2];
        $Semana_primer_registro=$arreglo[0][0];

        for($i=0; $i<count($arreglo); $i++){
            $Cantidad_Acumulada = $Cantidad_Acumulada + ($arreglo[$i][1]);

            $query3_2 .= "UPDATE $db"."_tasas_de_produccion SET Cantidad_Acumulada=$Cantidad_Acumulada WHERE Semana=($Semana_primer_registro);";

            $Semana_primer_registro=($Semana_primer_registro+1);
        }



        //echo "<li>" . $query3_2;
        $resultado3_2 = mysqli_multi_query($conexion, $query3_2);




        require ("../conexion.php");


        sleep(1);

        $query3_3="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
        $resultado3_3 = mysqli_query($conexion, $query3_3);
        $data3_3=mysqli_fetch_assoc($resultado3_3);
        $unidad=$data3_3["unidad"];


        $query4 = "SELECT * FROM $db"."_tasas_de_produccion WHERE Semana<=$Semana_base";

        $resultado4 = mysqli_query($conexion, $query4);

        if(!$resultado4){
            echo "no funciona";
            die(mysqli_error($conexion));
        } else{

            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Cantidad_Ejecutada' , 'label' => 'Cantidad Ejecutada' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Cantidad_Ejecutada_label' , 'label' => 'Cantidad Ejecutada' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Cantidad_Acumulada_Acumulada' , 'label' => 'Cantidad Acumulada' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Cantidad_Teorica_Acumulada' , 'label' => 'Cantidad Teorica Acumulada' , 'type' => 'number');

            while($row=mysqli_fetch_assoc($resultado4)){
                $Semana=(int)$row['Semana'];
                $Semana="Semana $Semana";
                $Cantidad_Ejecutada=round((float)$row['Cantidad_Ejecutada'],1);

                if($row['Cantidad_Ejecutada']=="NA"){
                    $Cantidad_Ejecutada_label="NA";
                }else{
                    $Cantidad_Ejecutada_label=round($Cantidad_Ejecutada,1) . " $unidad";
                }

                $Cantidad_Acumulada=(float)$row['Cantidad_Acumulada'];

                if($row['Cantidad_Acumulada']=="NA"){
                    $Cantidad_Acumulada=null;
                    $Cantidad_Acumulada_label="NA";
                }else{
                    $Cantidad_Acumulada_label=round($Cantidad_Acumulada,1) . " $unidad";
                }

                $Cantidad_Teorica_Acumulada=(float)$row['Cantidad_Teorica_Acumulada'];
                if($row['Cantidad_Teorica_Acumulada']=="NA"){
                    $Cantidad_Teorica_Acumulada=null;
                    $Cantidad_Teorica_Acumulada_label="NA";
                }else{
                    $Cantidad_Teorica_Acumulada_label=round($Cantidad_Teorica_Acumulada,1) . " $unidad";
                }

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                      array('v'=>$Cantidad_Ejecutada, 'f'=>$Cantidad_Ejecutada_label),
                                                      array('v'=> $Cantidad_Ejecutada),
                                                      array('v'=>$Cantidad_Acumulada, 'f'=>$Cantidad_Acumulada_label),
                                                      array('v'=>$Cantidad_Teorica_Acumulada, 'f'=>$Cantidad_Teorica_Acumulada_label)
                                                     ));
            }


            $query6 = "DROP TABLE $db"."_tasas_de_produccion";
            $resultado6 = mysqli_query($conexion, $query6);



            $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
    }
}

function grafico_Seguimiento_Ejecucion_Proyectado($conexion, $semana, $db, $codigo_actividad){

  $query = "SELECT Semana, Id, Fecha_Inicio, Fecha_Fin, cantidad_ppto as Cantidad_Total, (Ejecutado * cantidad_ppto) as Acumulado_Real FROM $db"."_programa_consolidado WHERE codigo_actividad='$codigo_actividad' ORDER BY Id, Semana ASC";

  $resultado = mysqli_query($conexion, $query);

  if(!$resultado){
    die("Error");
  } else{

    $Ejecutado_Real=0;
    while($data=mysqli_fetch_assoc($resultado)){
      $arreglo[]=array_map("utf8_encode", $data);
    }
  }

  $semana_inicio = $arreglo[0]["Semana"];
  $semana_fin = $arreglo[count($arreglo)-1]["Semana"];
  //echo "<li>" . $semana_inicio . " -- " . $semana_fin;

  $query1 = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem, Semana FROM $db"."_semanas_activas WHERE Semana>=$semana_inicio AND Semana<=$semana_fin";

  $resultado1 = mysqli_query($conexion, $query1);

  if(!$resultado1){
    die("Error");
  } else{
    while($data1=mysqli_fetch_assoc($resultado1)){
      $Semana_Actividad = $data1["Semana"];
      $arreglo_semanas_activas[$Semana_Actividad]["Fecha_Inicio_Sem"]=$data1["Fecha_Inicio_Sem"];
      $arreglo_semanas_activas[$Semana_Actividad]["Fecha_Fin_Sem"]=$data1["Fecha_Fin_Sem"];
    }
  }

  //$json_codificado = json_encode($arreglo_semanas_activas, JSON_UNESCAPED_UNICODE);
  //echo "<li>" . utf8_decode($json_codificado) . "<br><br>";

  $conteo = count($arreglo);


  for($i=0; $i<$conteo; $i++){
    $Semana_Actividad = $arreglo[$i]["Semana"];

    $arreglo[$i]["Fecha_Inicio_Sem"] = $arreglo_semanas_activas[$Semana_Actividad]["Fecha_Inicio_Sem"];
    //echo "<li>" . $arreglo[$i]["Fecha_Inicio_Sem"] ;

    $arreglo[$i]["Fecha_Fin_Sem"] = $arreglo_semanas_activas[$Semana_Actividad]["Fecha_Fin_Sem"];
    //echo " / " . $arreglo[$i]["Fecha_Fin_Sem"] ;
    //echo "<li> " . $arreglo[$i]["Semana"] . " -- " . $arreglo[$i]["Fecha_Inicio_Sem"] . " -- " . $arreglo[$i]["Fecha_Fin_Sem"] . "<br>";
  }

  //unset($arreglo[0]);

  for($i=0; $i<($conteo); $i++){
    $Id =$arreglo[$i]["Id"];
    $Semana_Actividad =$arreglo[$i]["Semana"];
    $Fecha_Inicio_Act =$arreglo[$i]["Fecha_Inicio"];
    $Fecha_Fin_Act =$arreglo[$i]["Fecha_Fin"];
    $Fecha_Inicio_Sem =$arreglo[$Semana_Actividad-1]["Fecha_Inicio_Sem"];
    $Fecha_Fin_Sem =$arreglo[$Semana_Actividad-1]["Fecha_Fin_Sem"];
    $cantidad_ppto =$arreglo[$i]["Cantidad_Total"];
    $Acumulado_Real =$arreglo[$i]["Acumulado_Real"];

    //echo "<li> Id: " . $Id . " -- Semana_Actividad: " . $Semana_Actividad  . " -- Fecha_Inicio_Act: " . $Fecha_Inicio_Act  . " -- Fecha_Fin_Act: " . $Fecha_Fin_Act  . " -- Fecha_Inicio_Sem: " . $Fecha_Inicio_Sem  . " -- Fecha_Fin_Sem: " . $Fecha_Fin_Sem  . " -- Acumulado_Real: " . $Acumulado_Real . "<br>";

    if (floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)>=0 && floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)>=floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Acumulado_Teorico_Inicio"] = ((floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)+1) / (floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1)) * $cantidad_ppto;

    }else if(floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)<0){
        $arreglo[$i]["Acumulado_Teorico_Inicio"]= 0 * $cantidad_ppto;
    }else if(floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)<floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Acumulado_Teorico_Inicio"]=1 * $cantidad_ppto;
    }else{
        $arreglo[$i]["Acumulado_Teorico_Inicio"]= 0 * $cantidad_ppto;
    }

    if (floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)>=0 && floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)>=floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Acumulado_Teorico_Fin"] = ((floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)+1) / (floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1)) * $cantidad_ppto;

    }else if(floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)<0){
        $arreglo[$i]["Acumulado_Teorico_Fin"]= 0 * $cantidad_ppto;
    }else if(floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)<floor((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Acumulado_Teorico_Fin"]=1 * $cantidad_ppto;
    }else{
        $arreglo[$i]["Acumulado_Teorico_Fin"]= 0 * $cantidad_ppto;
    }

    $Acumulado_Teorico_Inicio = round($arreglo[$i]["Acumulado_Teorico_Inicio"],2);
    $Acumulado_Teorico_Fin = round($arreglo[$i]["Acumulado_Teorico_Fin"],2);
    $Ejecutado_Semanal_Teorico = $Acumulado_Teorico_Fin - $Acumulado_Teorico_Inicio;
    $Acumulado_Real = round($arreglo[$i]["Acumulado_Real"],2);

    //echo "<li>  Id: " . $Id . " -- Semana: " . $Semana_Actividad . " -- Acumulado Teorico Inicio: " . $Acumulado_Teorico_Inicio . " -- Acumulado Teorico Fin: " . $Acumulado_Teorico_Fin . " -- Acumulado Real: " . $Acumulado_Real . " -- Ejecutado Semanal Teorico: " . $Ejecutado_Semanal_Teorico;
  }

  $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
  //echo "<li>" . utf8_decode($json_codificado);

    for($i=0; $i<($conteo); $i++){
      $Acumulado_Real = $arreglo[$i]["Acumulado_Real"];
      $Acumulado_Teorico_Inicio = $arreglo[$i]["Acumulado_Teorico_Inicio"];
      $Acumulado_Teorico_Fin = $arreglo[$i]["Acumulado_Teorico_Fin"];
      $Ejecutado_Semanal_Teorico = $Acumulado_Teorico_Fin - $Acumulado_Teorico_Inicio;
      $arreglo_final[$i]["Semana"] = $arreglo[$i]["Semana"];
      for($j=$i+1; $j<($conteo);$j++){
        if($arreglo[$i]["Semana"] == $arreglo[$j]["Semana"]){

          $Acumulado_Real += $arreglo[$j]["Acumulado_Real"];

          $Acumulado_Teorico_Inicio += $arreglo[$j]["Acumulado_Teorico_Inicio"];

          $Acumulado_Teorico_Fin += $arreglo[$j]["Acumulado_Teorico_Fin"];

          $Ejecutado_Semanal_Teorico += ($Acumulado_Teorico_Fin - $Acumulado_Teorico_Inicio);

          $j = $conteo+1;
        }
      }
      $arreglo_final[$i]["Acumulado_Real"] = round($Acumulado_Real,1);
      $arreglo_final[$i]["Acumulado_Teorico_Inicio"] = round($Acumulado_Teorico_Inicio,1);
      $arreglo_final[$i]["Acumulado_Teorico_Fin"] = round($Acumulado_Teorico_Fin,1);
      $arreglo_final[$i]["Ejecutado_Semanal_Teorico"] = round($Ejecutado_Semanal_Teorico,1);

      //echo "<br><li>  Semana: " . $arreglo_final[$i]["Semana"] . " -- Acumulado Teorico Inicio: " . round($Acumulado_Teorico_Inicio,2) . " -- Acumulado Teorico Fin: " . round($Acumulado_Teorico_Fin,2) . " -- Acumulado Real: " . round($Acumulado_Real,2) . " -- Ejecutado Semanal Teorico: " . round($Ejecutado_Semanal_Teorico,2);
    }
    for($i=($semana_fin); $i<($conteo); $i++){
      unset($arreglo_final[$i]);
    }
    /*for($i=($semana_fin); $i<($conteo); $i++){
      unset($arreglo_final[$i]);
    }*/



  $json_codificado = json_encode($arreglo_final, JSON_UNESCAPED_UNICODE);
  echo "<li><li>" . utf8_decode($json_codificado);
  /*$j=0;
  for($i=1; $i<$conteo; $i++){
    $inicial = $arreglo[$i-1]["Acumulado_Real"];
    $final = $arreglo[$i]["Acumulado_Real"];
    $Ejecutado_Real = $final - $inicial;
    $arreglo[$i]["Ejecutado_Real"] = $Ejecutado_Real;
    echo "<li>" . $Ejecutado_Real;
  }


  $conteo = count($arreglo);
  for($i=1; $i<($conteo+1); $i++){
    $Fecha_Inicio_Act =$arreglo[$i]["Fecha_Inicio"];
    $Fecha_Fin_Act =$arreglo[$i]["Fecha_Fin"];
    $Fecha_Inicio_Sem =$arreglo[$i]["Fecha_Inicio_Sem"];
    $Fecha_Fin_Sem =$arreglo[$i]["Fecha_Fin_Sem"];
    $cantidad_ppto =$arreglo[$i]["Cantidad_Total"];

    if (floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)>=0 && floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)>=floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Ejecutado_Teorico"] = ((floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)+1) / (floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1));

    }else if(floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)<0){
        $arreglo[$i]["Ejecutado_Teorico"]= 0;
    }else if(floor((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)<floor((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400)){
        $arreglo[$i]["Ejecutado_Teorico"]=1;
    }

    echo "<li>" . round($arreglo[$i]["Ejecutado_Teorico"],2);
  }


  $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
  echo "<li>" . utf8_decode($json_codificado);*/
}

/*function grafico_Seguimiento_Ejecucion_Proyectado($conexion, $semana, $db, $codigo_actividad){

    $query_1 = "SELECT MIN(Fecha_Inicio), MAX(Fecha_Fin) FROM $db"."_programa_consolidado WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad'";

    $resultado_1=mysqli_query($conexion, $query_1);
    $data_1=mysqli_fetch_assoc($resultado_1);

    $MIN_Fecha_Inicio=date("Y-m-d",strtotime($data_1["MIN(Fecha_Inicio)"]));
    $MAX_Fecha_Fin=date("Y-m-d",strtotime($data_1["MAX(Fecha_Fin)"]));

    //echo "<li> $MIN_Fecha_Inicio / $MAX_Fecha_Fin";
    $query_2 = "SELECT MIN(Semana) FROM $db"."_programa_consolidado WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad'";
    $resultado_2=mysqli_query($conexion, $query_2);
    $data_2=mysqli_fetch_assoc($resultado_2);
    $MIN_Semana=$data_2["MIN(Semana)"];
    $query = "SELECT * FROM $db"."_semanas_activas WHERE Semana<=$semana AND Semana>=$MIN_Semana";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1 ="CREATE TABLE $db"."_tasas_de_produccion ( `Id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `Semana` INT(11) NOT NULL , `Cantidad_Ejecutada` FLOAT(5) NOT NULL DEFAULT '0' , `Cantidad_Requerida` FLOAT(5) NOT NULL DEFAULT '0', `Cantidad_Acumulada` FLOAT(5) NOT NULL DEFAULT '0', `Cantidad_Requerida_Acumulada` FLOAT(5) NOT NULL DEFAULT '0', `Cantidad_Teorica_Acumulada` FLOAT(5) NOT NULL DEFAULT '0') ENGINE = INNODB;";

        $resultado1 = mysqli_query($conexion, $query1);

        $query2 = "INSERT INTO $db"."_tasas_de_produccion (Semana, Cantidad_Ejecutada, Cantidad_Teorica_Acumulada) VALUES ";

        $query2_1 = "";

        $query3 = "";

        while($data=mysqli_fetch_assoc($resultado)){
            $Semana_tabla=$data["Semana"];
            $Fecha_Inicio_Sem=date("Y-m-d",strtotime($data["Fecha_Inicio_Sem"]));
            $Fecha_Fin_Sem=date("Y-m-d",strtotime($data["Fecha_Fin_Sem"]));
            $Fecha_Inicio_Sem_Siguiente=date("Y-m-d",strtotime($data["Fecha_Inicio_Sem"] . "+ 6 days"));
            $Fecha_Fin_Sem_Siguiente=date("Y-m-d",strtotime($data["Fecha_Fin_Sem"] . "+ 6 days"));


            $query2 .= "($Semana_tabla,

            (SELECT
            CASE
            WHEN (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla+1) AND codigo_actividad=$codigo_actividad) IS NULL) = 1 OR (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla) AND codigo_actividad=$codigo_actividad) IS NULL) = 1

            THEN 0

            ELSE (SELECT (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=($Semana_tabla+1) AND codigo_actividad=$codigo_actividad) - (SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_tabla AND codigo_actividad=$codigo_actividad))
            END),

            (SELECT SUM((SELECT
            CASE
            WHEN DATEDIFF('$Fecha_Fin_Sem', Fecha_Inicio)>=0 AND DATEDIFF(Fecha_Fin, Fecha_Inicio) >= DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio) AND (DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio)+1)>0 THEN (DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio)+1) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio) < DATEDIFF('$Fecha_Fin_Sem',Fecha_Inicio) THEN 1
            ELSE 0
            END) * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_tabla AND codigo_actividad='$codigo_actividad')), ";
        }

        $query2 = substr($query2, 0, -2);
        // echo "<li>" . $query2;
        $resultado2 = mysqli_query($conexion, $query2);

        $query2_2 = "INSERT INTO $db"."_tasas_de_produccion (Semana, Cantidad_Ejecutada Cantidad_Teorica_Acumulada) VALUES ";

        $Fecha_Fin_Sem=strtotime($Fecha_Fin_Sem);
        $MAX_Fecha_Fin=strtotime($MAX_Fecha_Fin);
        $Semana_base=$Semana_tabla;

        $query_extraer_promedio_base = "SELECT AVG(Cantidad_Ejecutada), MIN(Semana) FROM $db"."_tasas_de_produccion WHERE Semana<=$Semana_base";
        $resultado_extraer_promedio_base=mysqli_query($conexion, $query_extraer_promedio_base);
        $data_extraer_promedio_base=mysqli_fetch_assoc($resultado_extraer_promedio_base);

        $query_extraer_semana_primer_registro = "SELECT MAX(Semana) FROM $db"."_programa_consolidado WHERE Semana<=$Semana_base AND codigo_actividad='$codigo_actividad' AND Ejecutado>0";
        $resultado_extraer_semana_primer_registro=mysqli_query($conexion, $query_extraer_semana_primer_registro);
        $data_extraer_semana_primer_registro=mysqli_fetch_assoc($resultado_extraer_semana_primer_registro);

        $Promedio_base=$data_extraer_promedio_base["AVG(Cantidad_Ejecutada)"];
        $Semana_primer_registro=$data_extraer_promedio_base["MIN(Semana)"];
        $Semana_primer_registro1=$data_extraer_semana_primer_registro["MAX(Semana)"];

         // echo "<li>" . $Promedio_base;
         // echo "<li>" . $Semana_primer_registro;
         // echo "<li>" . $Semana_primer_registro1;


        for($Fecha_Fin_Sem; $Fecha_Fin_Sem<$MAX_Fecha_Fin; $Fecha_Fin_Sem=strtotime(date("Y-m-d",$Fecha_Fin_Sem) . "+ 6 days")){
            $Semana_tabla=$Semana_tabla+1;
            $Fecha_Fin_Semana=date("Y-m-d",strtotime(date("Y-m-d",$Fecha_Fin_Sem) . "+ 6 days"));
            $MAX_Fecha_Final=date("Y-m-d", $MAX_Fecha_Fin);
            $query2_2 .= "($Semana_tabla,

            $Promedio_base,

            (SELECT SUM((SELECT
            CASE
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio)>=0 AND DATEDIFF(Fecha_Fin, Fecha_Inicio) >= DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio) AND (DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio)+1)>0 THEN (DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio)+1) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)
            WHEN DATEDIFF(Fecha_Fin, Fecha_Inicio) < DATEDIFF('$Fecha_Fin_Semana',Fecha_Inicio) THEN 1
            ELSE 0
            END) * cantidad_ppto)

            FROM $db"."_programa_consolidado WHERE Semana=$Semana_base AND codigo_actividad='$codigo_actividad')), ";
        };

        sleep(1);
        $query2_2 = substr($query2_2, 0, -2);
        //echo "<li>" . $query2_2;
        $resultado2_2 = mysqli_query($conexion, $query2_2);


        $query3 = "UPDATE $db"."_tasas_de_produccion SET Cantidad_Acumulada=(SELECT SUM(Ejecutado * cantidad_ppto) FROM $db"."_programa_consolidado WHERE Semana=$Semana_primer_registro AND codigo_actividad='$codigo_actividad')";

        //echo "<li>" . $query3;
        $resultado3 = mysqli_query($conexion, $query3);

        $query3_1 ="SELECT * FROM $db"."_tasas_de_produccion WHERE Semana>=$Semana_primer_registro";

        //echo "<li>" . $query3_1;
        $resultado3_1 = mysqli_query($conexion, $query3_1);

        while($data3_1=mysqli_fetch_assoc($resultado3_1)){
            $Semana=$data3_1["Semana"];
            $Cantidad_Ejecutada=$data3_1["Cantidad_Ejecutada"];
            $Cantidad_Acumulada=$data3_1["Cantidad_Acumulada"];
            $arreglo[]=array($Semana,$Cantidad_Ejecutada,$Cantidad_Acumulada);

        }


        $query3_2="";
        $Cantidad_Acumulada = $arreglo[0][2];
        $Semana_primer_registro=$arreglo[0][0];

        for($i=0; $i<count($arreglo); $i++){
            $Cantidad_Acumulada = $Cantidad_Acumulada + ($arreglo[$i][1]);

            $query3_2 .= "UPDATE $db"."_tasas_de_produccion SET Cantidad_Acumulada=$Cantidad_Acumulada WHERE Semana=($Semana_primer_registro);";

            $Semana_primer_registro=($Semana_primer_registro+1);
        }



        //echo "<li>" . $query3_2;
        $resultado3_2 = mysqli_multi_query($conexion, $query3_2);
        sleep(1);



        require ("../conexion.php");


        $query3_4="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
        $resultado3_4 = mysqli_query($conexion, $query3_4);
        $data3_4=mysqli_fetch_assoc($resultado3_4);
        $unidad=$data3_4["unidad"];



        $query4 = "SELECT * FROM $db"."_tasas_de_produccion WHERE Semana<=$Semana_base";

        $resultado4 = mysqli_query($conexion, $query4);

        if(!$resultado4){
            echo "no funciona";
            die(mysqli_error($conexion));
        } else{

            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Cantidad_Requerida_Acumulada' , 'label' => 'Cantidad Requerida Acumulada' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Cantidad_Ejecutada' , 'label' => 'Cantidad Ejecutada' , 'type' => 'number');
            $array['cols'][3] = array('id' => 'Cantidad_Ejecutada_label' , 'label' => 'Cantidad Ejecutada' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][4] = array('id' => 'Cantidad_Acumulada' , 'label' => 'Cantidad Acumulada' , 'type' => 'number');
            $array['cols'][5] = array('id' => 'Cantidad_Teorica_Acumulada' , 'label' => 'Cantidad Teorica Acumulada' , 'type' => 'number');
            $array['cols'][6] = array('id' => 'Cantidad_Requerida' , 'label' => 'Cantidad Requerida' , 'type' => 'number');

            while($row=mysqli_fetch_assoc($resultado4)){
                $Semana=(int)$row['Semana'];
                $Semana="Semana $Semana";
                $Cantidad_Ejecutada=round((float)$row['Cantidad_Ejecutada'],1);
                if($row['Cantidad_Ejecutada']=="NA"){
                    $Cantidad_Ejecutada_label="NA";
                }else{
                    $Cantidad_Ejecutada_label=round($Cantidad_Ejecutada,1) . " $unidad";
                }

                $Cantidad_Requerida=round((float)$row['Cantidad_Requerida'],1);
                if($row['Cantidad_Requerida']=="NA"){
                    $Cantidad_Requerida_label="NA";
                }else{
                    $Cantidad_Requerida_label=round($Cantidad_Requerida,1) . " $unidad";
                }

                $Cantidad_Acumulada=(float)$row['Cantidad_Acumulada'];
                if($row['Cantidad_Acumulada']=="NA"){
                    $Cantidad_Acumulada=null;
                    $Cantidad_Acumulada_label="NA";
                }else{
                    $Cantidad_Acumulada_label=round($Cantidad_Acumulada,1) . " $unidad";
                }


                $Cantidad_Teorica_Acumulada=(float)$row['Cantidad_Teorica_Acumulada'];
                if($row['Cantidad_Teorica_Acumulada']=="NA"){
                    $Cantidad_Teorica_Acumulada=null;
                    $Cantidad_Teorica_Acumulada_label="NA";
                }else{
                    $Cantidad_Teorica_Acumulada_label=round($Cantidad_Teorica_Acumulada,1) . " $unidad";
                }

                $Cantidad_Requerida_Acumulada=(float)$row['Cantidad_Requerida_Acumulada'];
                if($row['Cantidad_Requerida_Acumulada']=="NA"){
                    $Cantidad_Requerida=null;
                    $Cantidad_Requerida_Acumulada_label="NA";
                }else{
                    $Cantidad_Requerida_Acumulada_label=round($Cantidad_Requerida_Acumulada,1) . " $unidad";
                }

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                      array('v'=>$Cantidad_Requerida_Acumulada, 'f'=>$Cantidad_Requerida_Acumulada_label),
                                                      array('v'=>$Cantidad_Ejecutada, 'f'=>$Cantidad_Ejecutada_label),
                                                      array('v'=> $Cantidad_Ejecutada),
                                                      array('v'=>$Cantidad_Acumulada, 'f'=>$Cantidad_Acumulada_label),
                                                      array('v'=>$Cantidad_Teorica_Acumulada, 'f'=>$Cantidad_Teorica_Acumulada_label),
                                                      array('v'=>$Cantidad_Requerida, 'f'=>$Cantidad_Requerida_label)
                                                     ));
            }

            $query5 = "SELECT * FROM $db"."_tasas_de_produccion WHERE Semana>$Semana_base";
            $resultado5 = mysqli_query($conexion, $query5);

            while($row=mysqli_fetch_assoc($resultado5)){
                $Semana=(int)$row['Semana'];
                $Semana="Semana $Semana";
                $Cantidad_Ejecutada=round((float)$row['Cantidad_Ejecutada'],1);
                if($row['Cantidad_Ejecutada']=="NA"){
                    $Cantidad_Ejecutada_label="NA";
                }else{
                    $Cantidad_Ejecutada_label=round($Cantidad_Ejecutada,1) . " $unidad";
                }

                $Cantidad_Requerida=round((float)$row['Cantidad_Requerida'],1);
                if($row['Cantidad_Requerida']=="NA"){
                    $Cantidad_Requerida_label="NA";
                }else{
                    $Cantidad_Requerida_label=round($Cantidad_Requerida,1) . " $unidad";
                }

                $Cantidad_Acumulada=(float)$row['Cantidad_Acumulada'];
                if($row['Cantidad_Acumulada']=="NA"){
                    $Cantidad_Acumulada=null;
                    $Cantidad_Acumulada_label="NA";
                }else{
                    $Cantidad_Acumulada_label=round($Cantidad_Acumulada,1) . " $unidad";
                }


                $Cantidad_Teorica_Acumulada=(float)$row['Cantidad_Teorica_Acumulada'];
                if($row['Cantidad_Teorica_Acumulada']=="NA"){
                    $Cantidad_Teorica_Acumulada=null;
                    $Cantidad_Teorica_Acumulada_label="NA";
                }else{
                    $Cantidad_Teorica_Acumulada_label=round($Cantidad_Teorica_Acumulada,1) . " $unidad";
                }

                $Cantidad_Requerida_Acumulada=(float)$row['Cantidad_Requerida_Acumulada'];
                if($row['Cantidad_Requerida_Acumulada']=="NA"){
                    $Cantidad_Requerida=null;
                    $Cantidad_Requerida_Acumulada_label="NA";
                }else{
                    $Cantidad_Requerida_Acumulada_label=round($Cantidad_Requerida_Acumulada,1) . " $unidad";
                }

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                      array('v'=>$Cantidad_Requerida_Acumulada, 'f'=>$Cantidad_Requerida_Acumulada_label),
                                                      array('v'=>$Cantidad_Ejecutada, 'f'=>$Cantidad_Ejecutada_label),
                                                      array('v'=> $Cantidad_Ejecutada),
                                                      array('v'=>$Cantidad_Acumulada, 'f'=>$Cantidad_Acumulada_label),
                                                      array('v'=>$Cantidad_Teorica_Acumulada, 'f'=>$Cantidad_Teorica_Acumulada_label),
                                                      array('v'=>$Cantidad_Requerida, 'f'=>$Cantidad_Requerida_label)
                                                     ));
            }




            $query6 = "DROP TABLE $db"."_tasas_de_produccion";
            //$resultado6 = mysqli_query($conexion, $query6);



            $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
    }
}*/

function grafico_Seguimiento_Rendimientos($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $ayudantes_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Rendimiento' , 'label' => 'Rendimiento' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Rendimiento_label' , 'label' => 'Rendimiento' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Rendimiento_Tendencia' , 'label' => 'Rendimiento Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Rendimiento_Teorico' , 'label' => 'Rendimiento Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Cantidad_Semana=0;
                $Oficiales_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i]=='n' || $Rendimientos[$i]=='N' || $Rendimientos[$i]==''){
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana + round((float)$Rendimientos[$i-1],3);
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],3);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],3);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($data);
            }

            $conteo_tareas=1;
            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Cantidad_Semana=$arreglo[$i]["Cantidad_Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Cantidad_Semana = $Cantidad_Semana + $arreglo[$j]["Cantidad_Semana"];
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;
                        $conteo_tareas=$saltos;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                        $j=count($arreglo);
                    };
                }
                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Rendimiento_Semana"]=round(($Cantidad_Semana / $Dias_Semana) / ceil($Oficiales_Semana/$Dias_Semana),1);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }

            $query3_3="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
            $resultado3_3 = mysqli_query($conexion, $query3_3);
            $data3_3=mysqli_fetch_assoc($resultado3_3);
            $unidad=$data3_3["unidad"];

            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Rendimiento' , 'label' => 'Rendimiento' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Rendimiento_label' , 'label' => 'Rendimiento' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Rendimiento_Tendencia' , 'label' => 'Rendimiento Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Rendimiento_Teorico' , 'label' => 'Rendimiento Teorico' , 'type' => 'number');

            $Rendimiento_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Rendimiento_Suma=($Rendimiento_Suma+$arreglo_consolidado[$i]["Rendimiento_Semana"]);
                $Rendimiento_Tendencia=$Rendimiento_Suma/($i+1);

                $arreglo_consolidado[$i]["Rendimiento_Tendencia"]=round($Rendimiento_Tendencia,1);


                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>(float)$arreglo_consolidado[$i]["Rendimiento_Semana"], 'f'=>(float)$arreglo_consolidado[$i]["Rendimiento_Semana"] . " $unidad" . "/Cuadrilla-dia"),
                                                          array('v'=> $arreglo_consolidado[$i]["Rendimiento_Semana"]),
                                                          array('v'=>$arreglo_consolidado[$i]["Rendimiento_Tendencia"], 'f'=>$arreglo_consolidado[$i]["Rendimiento_Tendencia"] . " $unidad" . "/Cuadrilla-dia"),
                                                          array('v'=>round($rendimiento_cuadrilla_tipica_teorico,1), 'f'=>round($rendimiento_cuadrilla_tipica_teorico,1) . " $unidad" . "/Cuadrilla-dia")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function grafico_Seguimiento_Cuadrillas_Tipicas($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $ayudantes_teorico, $cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Oficiales' , 'label' => 'Oficiales' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Oficiales_label' , 'label' => 'Oficiales' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Oficiales_Tendencia' , 'label' => 'Oficiales Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Oficiales_Teorico' , 'label' => 'Oficiales Teorico' , 'type' => 'number');
        $array['cols'][5] = array('id' => 'Ayudantes_Tendencia' , 'label' => 'Ayudantes Tendencia' , 'type' => 'number');
        $array['cols'][6] = array('id' => 'Ayudantes_Teorico' , 'label' => 'Ayudantes Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Oficiales_Semana=0;
                $Ayudantes_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i-1]=='n' || $Rendimientos[$i-1]=='N' || $Rendimientos[$i-1]==''){
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],3);
                        $Ayudantes_Semana = $Ayudantes_Semana + round((float)$Rendimientos[$i+1],3);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],3);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($arreglo);
            }

            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Ayudantes_Semana=$arreglo[$i]["Ayudantes_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                $Oficiales_Promedio_Dia=($Oficiales_Semana / $Dias_Semana);
                $Ayudantes_Promedio_Dia=($Ayudantes_Semana / $Dias_Semana);
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Ayudantes_Semana = $Ayudantes_Semana + $arreglo[$j]["Ayudantes_Semana"];

                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia + ($Oficiales_Semana / $Dias_Semana);

                        $Ayudantes_Promedio_Dia = $Ayudantes_Promedio_Dia + ($Ayudantes_Semana / $Dias_Semana);
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia;
                        $j=count($arreglo);
                    };
                }

                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana_consolidado["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Dias_Semana"]=$Dias_Semana;
                $Registro_Semana_consolidado["Oficiales_Promedio_Dia"]= ceil($Oficiales_Promedio_Dia);
                $Registro_Semana_consolidado["Ayudantes_Promedio_Dia"]= ceil($Ayudantes_Promedio_Dia);

                $Registro_Semana_consolidado["cuadrilla_tipica_oficiales"] = round($Registro_Semana_consolidado["Oficiales_Promedio_Dia"] / $oficiales_teorico,0);

                $Registro_Semana_consolidado["cuadrilla_tipica_ayudantes"] = round($Registro_Semana_consolidado["Ayudantes_Promedio_Dia"] / $ayudantes_teorico,0);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }
            //print_r($arreglo_consolidado);


            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Cuadrilla_Tipica' , 'label' => 'Cuadrilla Tipica' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Cuadrilla_Tipica_label' , 'label' => 'Cuadrilla Tipica' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Cuadrilla_Tipica_Tendencia' , 'label' => 'Cuadrilla Tipica Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Cuadrilla_Tipica_Teorico' , 'label' => 'Cuadrilla Tipica Teorico' , 'type' => 'number');

            $Cuadrilla_Tipica_Oficiales_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Cuadrilla_Tipica_Oficiales_Suma=($Cuadrilla_Tipica_Oficiales_Suma+$arreglo_consolidado[$i]["cuadrilla_tipica_oficiales"]);
                $Cuadrilla_Tipica_Oficiales_Tendencia=$Cuadrilla_Tipica_Oficiales_Suma/($i+1);

                $arreglo_consolidado[$i]["cuadrilla_tipica_oficiales_Tendencia"]=round($Cuadrilla_Tipica_Oficiales_Tendencia,1);

                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>(float)$arreglo_consolidado[$i]["cuadrilla_tipica_oficiales"], 'f'=>(float)$arreglo_consolidado[$i]["cuadrilla_tipica_oficiales"] . " Cuadrillas de $oficiales_teorico Of + $ayudantes_teorico Ay"),
                                                          array('v'=> $arreglo_consolidado[$i]["cuadrilla_tipica_oficiales"]),
                                                          array('v'=>$arreglo_consolidado[$i]["cuadrilla_tipica_oficiales_Tendencia"], 'f'=>$arreglo_consolidado[$i]["cuadrilla_tipica_oficiales_Tendencia"] . " Cuadrillas de $oficiales_teorico Of + $ayudantes_teorico Ay"),
                                                          array('v'=>$cuadrilla_tipica_teorico, 'f'=>$cuadrilla_tipica_teorico . " Cuadrillas de $oficiales_teorico Of + $ayudantes_teorico Ay")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function grafico_Seguimiento_Oficiales($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Oficiales' , 'label' => 'Oficiales' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Oficiales_label' , 'label' => 'Oficiales' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Oficiales_Tendencia' , 'label' => 'Oficiales Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Oficiales_Teorico' , 'label' => 'Oficiales Teorico' , 'type' => 'number');
        $array['cols'][5] = array('id' => 'Ayudantes_Tendencia' , 'label' => 'Ayudantes Tendencia' , 'type' => 'number');
        $array['cols'][6] = array('id' => 'Ayudantes_Teorico' , 'label' => 'Ayudantes Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Oficiales_Semana=0;
                $Ayudantes_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i-1]=='n' || $Rendimientos[$i-1]=='N' || $Rendimientos[$i-1]==''){
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],3);
                        $Ayudantes_Semana = $Ayudantes_Semana + round((float)$Rendimientos[$i+1],3);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],3);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($arreglo);
            }

            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Ayudantes_Semana=$arreglo[$i]["Ayudantes_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                $Oficiales_Promedio_Dia=($Oficiales_Semana / $Dias_Semana);
                $Ayudantes_Promedio_Dia=($Ayudantes_Semana / $Dias_Semana);
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Ayudantes_Semana = $Ayudantes_Semana + $arreglo[$j]["Ayudantes_Semana"];

                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia + ($Oficiales_Semana / $Dias_Semana);

                        $Ayudantes_Promedio_Dia = $Ayudantes_Promedio_Dia + ($Ayudantes_Semana / $Dias_Semana);
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia;
                        $j=count($arreglo);
                    };
                }

                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana_consolidado["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Dias_Semana"]=$Dias_Semana;
                $Registro_Semana_consolidado["Oficiales_Promedio_Dia"]= ceil($Oficiales_Promedio_Dia);
                $Registro_Semana_consolidado["Ayudantes_Promedio_Dia"]= ceil($Ayudantes_Promedio_Dia);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }
            //print_r($arreglo_consolidado);


            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Oficiales' , 'label' => 'Oficiales' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Oficiales_label' , 'label' => 'Oficiales' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Oficiales_Tendencia' , 'label' => 'Oficiales Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Oficiales_Teorico' , 'label' => 'Oficiales Teorico' , 'type' => 'number');

            $Oficiales_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Oficiales_Suma=($Oficiales_Suma+$arreglo_consolidado[$i]["Oficiales_Promedio_Dia"]);
                $Oficiales_Tendencia=$Oficiales_Suma/($i+1);

                $arreglo_consolidado[$i]["Oficiales_Tendencia"]=round($Oficiales_Tendencia,3);

                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>(float)$arreglo_consolidado[$i]["Oficiales_Promedio_Dia"], 'f'=>(float)$arreglo_consolidado[$i]["Oficiales_Promedio_Dia"] . " Oficiales/Dia"),
                                                          array('v'=> $arreglo_consolidado[$i]["Oficiales_Promedio_Dia"]),
                                                          array('v'=>$arreglo_consolidado[$i]["Oficiales_Tendencia"], 'f'=>$arreglo_consolidado[$i]["Oficiales_Tendencia"] . " Oficiales/Dia"),
                                                          array('v'=>($oficiales_teorico * $cuadrilla_tipica_teorico), 'f'=>($oficiales_teorico * $cuadrilla_tipica_teorico) . " Oficiales/Dia")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function grafico_Seguimiento_Ayudantes($conexion, $semana, $db, $codigo_actividad, $ayudantes_teorico, $cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Ayudantes' , 'label' => 'Ayudantes' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Oficiales_label' , 'label' => 'Ayudantes' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Ayudantes_Tendencia' , 'label' => 'Ayudantes Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Ayudantes_Teorico' , 'label' => 'Ayudantes Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Oficiales_Semana=0;
                $Ayudantes_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i-1]=='n' || $Rendimientos[$i-1]=='N' || $Rendimientos[$i-1]==''){
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],3);
                        $Ayudantes_Semana = $Ayudantes_Semana + round((float)$Rendimientos[$i+1],3);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],3);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($arreglo);
            }


            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Ayudantes_Semana=$arreglo[$i]["Ayudantes_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                $Oficiales_Promedio_Dia=($Oficiales_Semana / $Dias_Semana);
                $Ayudantes_Promedio_Dia=($Ayudantes_Semana / $Dias_Semana);
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Ayudantes_Semana = $Ayudantes_Semana + $arreglo[$j]["Ayudantes_Semana"];

                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia + ($Oficiales_Semana / $Dias_Semana);

                        $Ayudantes_Promedio_Dia = $Ayudantes_Promedio_Dia + ($Ayudantes_Semana / $Dias_Semana);
                    }else{
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;

                        $Oficiales_Promedio_Dia = $Oficiales_Promedio_Dia;
                        $j=count($arreglo);
                    };
                }

                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana_consolidado["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Dias_Semana"]=$Dias_Semana;
                $Registro_Semana_consolidado["Oficiales_Promedio_Dia"]= ceil($Oficiales_Promedio_Dia);
                $Registro_Semana_consolidado["Ayudantes_Promedio_Dia"]= ceil($Ayudantes_Promedio_Dia);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }
            //print_r($arreglo_consolidado);


            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Ayudantes' , 'label' => 'Ayudantes' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Oficiales_label' , 'label' => 'Ayudantes' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Ayudantes_Tendencia' , 'label' => 'Ayudantes Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Ayudantes_Teorico' , 'label' => 'Ayudantes Teorico' , 'type' => 'number');

            $Ayudantes_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Ayudantes_Suma=($Ayudantes_Suma+$arreglo_consolidado[$i]["Ayudantes_Promedio_Dia"]);
                $Ayudantes_Tendencia=$Ayudantes_Suma/($i+1);

                $arreglo_consolidado[$i]["Ayudantes_Tendencia"]=round($Ayudantes_Tendencia,3);


                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>(float)$arreglo_consolidado[$i]["Ayudantes_Promedio_Dia"], 'f'=>(float)$arreglo_consolidado[$i]["Ayudantes_Promedio_Dia"]. " Ayudantes/Dia"),
                                                          array('v'=> $arreglo_consolidado[$i]["Ayudantes_Promedio_Dia"]),
                                                          array('v'=>$arreglo_consolidado[$i]["Ayudantes_Tendencia"], 'f'=>$arreglo_consolidado[$i]["Ayudantes_Tendencia"]. " Ayudantes/Dia"),
                                                          array('v'=>($ayudantes_teorico * $cuadrilla_tipica_teorico), 'f'=>($ayudantes_teorico * $cuadrilla_tipica_teorico) . " Ayudantes/Dia")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function grafico_Seguimiento_Consumo_Oficiales($conexion, $semana, $db, $codigo_actividad, $oficiales_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Consumo_Oficiales_Hora' , 'label' => 'Consumo de Horas-Oficial' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Consumo_Oficiales_Hora_label' , 'label' => 'Consumo de Horas-Oficial' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Consumo_Oficiales_Hora_Tendencia' , 'label' => 'Consumo de Horas-Oficial Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Consumo_Oficiales_Hora_Teorico' , 'label' => 'Consumo de Horas-Oficial Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Cantidad_Semana =0;
                $Oficiales_Semana=0;
                $Ayudantes_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i-1]=='n' || $Rendimientos[$i-1]=='N' || $Rendimientos[$i-1]==''){
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana + round((float)$Rendimientos[$i-1],2);
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],2);
                        $Ayudantes_Semana = $Ayudantes_Semana + round((float)$Rendimientos[$i+1],2);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],2);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($arreglo);
            }


            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Cantidad_Semana=$arreglo[$i]["Cantidad_Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Ayudantes_Semana=$arreglo[$i]["Ayudantes_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Cantidad_Semana = $Cantidad_Semana + $arreglo[$j]["Cantidad_Semana"];
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Ayudantes_Semana = $Ayudantes_Semana + $arreglo[$j]["Ayudantes_Semana"];

                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                        $j=count($arreglo);
                    };
                }
                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana_consolidado["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana_consolidado["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Dias_Semana"]=$Dias_Semana;
                $Registro_Semana_consolidado["Consumo_Oficiales_Hora"]=round(($Horas_Semana/($Cantidad_Semana / $Oficiales_Semana))/$Dias_Semana,2);
                $Registro_Semana_consolidado["Consumo_Ayudantes_Hora"]=round(($Horas_Semana/($Cantidad_Semana / $Ayudantes_Semana))/$Dias_Semana,2);
                $Registro_Semana_consolidado["Oficiales_Promedio_Dia"]=round(($Oficiales_Semana / $Dias_Semana),2);
                $Registro_Semana_consolidado["Ayudantes_Promedio_Dia"]=round(($Ayudantes_Semana / $Dias_Semana),2);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }
            //print_r($arreglo_consolidado);

            $query3_3="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
            $resultado3_3 = mysqli_query($conexion, $query3_3);
            $data3_3=mysqli_fetch_assoc($resultado3_3);
            $unidad=$data3_3["unidad"];

            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Consumo_Oficiales_Hora' , 'label' => 'Consumo de Horas-Oficial' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Consumo_Oficiales_Hora_label' , 'label' => 'Consumo de Horas-Oficial' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Consumo_Oficiales_Hora_Tendencia' , 'label' => 'Consumo de Horas-Oficial Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Consumo_Oficiales_Hora_Teorico' , 'label' => 'Consumo de Horas-Oficial Teorico' , 'type' => 'number');

            $Consumo_Oficiales_Hora_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Consumo_Oficiales_Hora_Suma=($Consumo_Oficiales_Hora_Suma+$arreglo_consolidado[$i]["Consumo_Oficiales_Hora"]);
                $Consumo_Oficiales_Hora_Tendencia=$Consumo_Oficiales_Hora_Suma/($i+1);

                $arreglo_consolidado[$i]["Consumo_Oficiales_Hora_Tendencia"]=round($Consumo_Oficiales_Hora_Tendencia,1);


                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>round((float)$arreglo_consolidado[$i]["Consumo_Oficiales_Hora"],1), 'f'=>round((float)$arreglo_consolidado[$i]["Consumo_Oficiales_Hora"],1) . " Horas-Oficial/" . "$unidad"),
                                                          array('v'=> round($arreglo_consolidado[$i]["Consumo_Oficiales_Hora"],1)),
                                                          array('v'=>round($arreglo_consolidado[$i]["Consumo_Oficiales_Hora_Tendencia"],1), 'f'=>round($arreglo_consolidado[$i]["Consumo_Oficiales_Hora_Tendencia"],1) . " Horas-Oficial/" . "$unidad"),
                                                          array('v'=>round((1/(($rendimiento_cuadrilla_tipica_teorico/9)/$oficiales_teorico)),1), 'f'=>round((1/(($rendimiento_cuadrilla_tipica_teorico/9)/$oficiales_teorico)),1)   . " Horas-Oficial/" . "$unidad")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function grafico_Seguimiento_Consumo_Ayudantes($conexion, $semana, $db, $codigo_actividad, $ayudantes_teorico, $cuadrilla_tipica_teorico, $rendimiento_cuadrilla_tipica_teorico){
    $query0 = "SELECT COUNT(*) FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
    $resultado0=mysqli_query($conexion, $query0);
    $data0=mysqli_fetch_assoc($resultado0);
    $conteo= $data0["COUNT(*)"];

    if($conteo==0){
        $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
        $array['cols'][1] = array('id' => 'Consumo_Ayudantes_Hora' , 'label' => 'Consumo de Horas-Ayudante' , 'type' => 'number');
        $array['cols'][2] = array('id' => 'Consumo_Ayudantes_Hora_label' , 'label' => 'Consumo de Horas-Ayudante' , 'type' => 'string', 'role' => 'annotation');
        $array['cols'][3] = array('id' => 'Consumo_Ayudantes_Hora_Tendencia' , 'label' => 'Consumo de Horas-Ayudante Tendencia' , 'type' => 'number');
        $array['cols'][4] = array('id' => 'Consumo_Ayudantes_Hora_Teorico' , 'label' => 'Consumo de Horas-Ayudante Teorico' , 'type' => 'number');

        $array['rows'][] = array('c' => array( array('v'=> $semana),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>"N/A"),
                                              array('v'=>"N/A", 'f'=>0),
                                              array('v'=>0, 'f'=>0)
                                             ));

    }else{
        $query = "SELECT Id, Semana, codigo_actividad, Rendimientos FROM $db"."_programacion_semanal WHERE Semana<=$semana AND codigo_actividad='$codigo_actividad' AND (Activa=1 OR Activa='NA') AND (Rendimientos IS NOT NULL AND Rendimientos!='') ORDER BY Semana ASC";
        $resultado=mysqli_query($conexion, $query);
        if(!$resultado){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado)){
                $Semana_tabla=$data["Semana"];
                $Id=$data["Id"];
                $Rendimientos=$data["Rendimientos"];
                $Rendimientos=explode(";", $Rendimientos);
                $Cantidad_Semana =0;
                $Oficiales_Semana=0;
                $Ayudantes_Semana=0;
                $Horas_Semana=0;
                $Dias_Semana=0;
                for($i=1; $i<29; $i=$i+4){

                    if($Rendimientos[$i-1]=='n' || $Rendimientos[$i-1]=='N' || $Rendimientos[$i-1]==''){
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana + round((float)$Rendimientos[$i-1],2);
                        $Oficiales_Semana = $Oficiales_Semana + round((float)$Rendimientos[$i],2);
                        $Ayudantes_Semana = $Ayudantes_Semana + round((float)$Rendimientos[$i+1],2);
                        $Horas_Semana = $Horas_Semana + round((float)$Rendimientos[$i+2],2);
                        $Dias_Semana = $Dias_Semana + 1;
                    }
                }
                $Registro_Semana["Semana"]=$Semana_tabla;
                $Registro_Semana["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana["Dias_Semana"]=$Dias_Semana;

                $arreglo[]=array_map("utf8_encode", $Registro_Semana);
                //print_r($arreglo);
            }


            for($i=0; $i< count($arreglo); $i++){
                $Semana_ciclo=$arreglo[$i]["Semana"];
                $Cantidad_Semana=$arreglo[$i]["Cantidad_Semana"];
                $Oficiales_Semana=$arreglo[$i]["Oficiales_Semana"];
                $Ayudantes_Semana=$arreglo[$i]["Ayudantes_Semana"];
                $Horas_Semana=$arreglo[$i]["Horas_Semana"];
                $Dias_Semana=$arreglo[$i]["Dias_Semana"];
                //echo "<li> $Semana_ciclo -> $Rendimiento_Semana";
                $saltos=0;
                for ($j=$i+1; $j<count($arreglo); $j++){
                    if($arreglo[$j]["Semana"]==$Semana_ciclo){
                        $Cantidad_Semana = $Cantidad_Semana + $arreglo[$j]["Cantidad_Semana"];
                        $Oficiales_Semana = $Oficiales_Semana + $arreglo[$j]["Oficiales_Semana"];
                        $Ayudantes_Semana = $Ayudantes_Semana + $arreglo[$j]["Ayudantes_Semana"];

                        $Horas_Semana = $Horas_Semana + $arreglo[$j]["Horas_Semana"];
                        $Dias_Semana = $Dias_Semana + $arreglo[$j]["Dias_Semana"];
                        $saltos=$saltos+1;
                    }else{
                        $Cantidad_Semana = $Cantidad_Semana;
                        $Oficiales_Semana = $Oficiales_Semana;
                        $Ayudantes_Semana = $Ayudantes_Semana;
                        $Horas_Semana = $Horas_Semana;
                        $Dias_Semana = $Dias_Semana;
                        $j=count($arreglo);
                    };
                }
                $i=$i+$saltos;

                $Registro_Semana_consolidado["Semana"]=$Semana_ciclo;
                $Registro_Semana_consolidado["Cantidad_Semana"]=$Cantidad_Semana;
                $Registro_Semana_consolidado["Oficiales_Semana"]=$Oficiales_Semana;
                $Registro_Semana_consolidado["Ayudantes_Semana"]=$Ayudantes_Semana;
                $Registro_Semana_consolidado["Horas_Semana"]=$Horas_Semana;
                $Registro_Semana_consolidado["Dias_Semana"]=$Dias_Semana;
                $Registro_Semana_consolidado["Consumo_Oficiales_Hora"]=round(($Horas_Semana/($Cantidad_Semana / $Oficiales_Semana))/$Dias_Semana,2);
                $Registro_Semana_consolidado["Consumo_Ayudantes_Hora"]=round(($Horas_Semana/($Cantidad_Semana / $Ayudantes_Semana))/$Dias_Semana,2);
                $Registro_Semana_consolidado["Oficiales_Promedio_Dia"]=round(($Oficiales_Semana / $Dias_Semana),2);
                $Registro_Semana_consolidado["Ayudantes_Promedio_Dia"]=round(($Ayudantes_Semana / $Dias_Semana),2);

                $arreglo_consolidado[]=array_map("utf8_encode", $Registro_Semana_consolidado);
            }
            //print_r($arreglo_consolidado);

            $query3_3="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
            $resultado3_3 = mysqli_query($conexion, $query3_3);
            $data3_3=mysqli_fetch_assoc($resultado3_3);
            $unidad=$data3_3["unidad"];

            $array['cols'][0] = array('id' => 'Semanas' , 'label' => 'Semanas' , 'type' => 'string');
            $array['cols'][1] = array('id' => 'Consumo_Ayudantes_Hora' , 'label' => 'Consumo de Horas-Ayudante' , 'type' => 'number');
            $array['cols'][2] = array('id' => 'Consumo_Ayudantes_Hora_label' , 'label' => 'Consumo de Horas-Ayudante' , 'type' => 'string', 'role' => 'annotation');
            $array['cols'][3] = array('id' => 'Consumo_Ayudantes_Hora_Tendencia' , 'label' => 'Consumo de Horas-Ayudante Tendencia' , 'type' => 'number');
            $array['cols'][4] = array('id' => 'Consumo_Ayudantes_Hora_Teorico' , 'label' => 'Consumo de Horas-Ayudante Teorico' , 'type' => 'number');

            $Consumo_Ayudantes_Hora_Suma=0;
            for($i=0; $i<count($arreglo_consolidado); $i++){
                $Consumo_Ayudantes_Hora_Suma=($Consumo_Ayudantes_Hora_Suma+$arreglo_consolidado[$i]["Consumo_Ayudantes_Hora"]);
                $Consumo_Ayudantes_Hora_Tendencia=$Consumo_Ayudantes_Hora_Suma/($i+1);

                $arreglo_consolidado[$i]["Consumo_Ayudantes_Hora_Tendencia"]=round($Consumo_Ayudantes_Hora_Tendencia,1);


                $Semana=(int)$arreglo_consolidado[$i]["Semana"];
                $Semana="Semana $Semana";

                $array['rows'][] = array('c' => array( array('v'=> $Semana),
                                                          array('v'=>round((float)$arreglo_consolidado[$i]["Consumo_Ayudantes_Hora"],1), 'f'=>round((float)$arreglo_consolidado[$i]["Consumo_Ayudantes_Hora"],1)  . " Horas-Ayudante/" . "$unidad"),
                                                          array('v'=> round($arreglo_consolidado[$i]["Consumo_Ayudantes_Hora"],1)),
                                                          array('v'=> round($arreglo_consolidado[$i]["Consumo_Ayudantes_Hora_Tendencia"],1), 'f'=> round($arreglo_consolidado[$i]["Consumo_Ayudantes_Hora_Tendencia"],1)  . " Horas-Ayudante/" . "$unidad"),
                                                          array('v'=>round((1/(($rendimiento_cuadrilla_tipica_teorico/9)/$ayudantes_teorico)),1), 'f'=>round((1/(($rendimiento_cuadrilla_tipica_teorico/9)/$ayudantes_teorico)),1)   . " Horas-Ayudante/" . "$unidad")
                                                         ));


            }
        }
    }
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

function unidad_actividad($conexion, $semana, $db, $codigo_actividad){
    $query="SELECT unidad FROM general_codigos_actividades WHERE codigo_actividad='$codigo_actividad'";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
        $unidad_actividad="";
    } else{
        $data=mysqli_fetch_assoc($resultado);
        $unidad_actividad=$data["unidad"];
    }
    $json_codificado = json_encode($unidad_actividad, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);

}

function importar_cuadrilla_tipica($conexion, $semana, $db, $codigo_actividad){
    $query="SELECT * FROM general_cuadrillas_tipicas WHERE proyecto='$db' AND codigo_actividad='$codigo_actividad'";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $data=mysqli_fetch_assoc($resultado);
        if(!$data){
            $data=array();
            $data["oficiales_tipica"]=1;
            $data["ayudantes_tipica"]=1;
            $data["rendimiento_tipica"]=1;
            $data["numero_cuadrillas_tipicas"]=1;
        }else{
            $arreglo["data"]=array_map("utf8_encode", $data);
        }
    }
    $json_codificado = json_encode($data, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);

}

function exportar_cuadrilla_tipica($conexion, $semana, $db, $codigo_actividad, $oficiales_tipica, $ayudantes_tipica, $rendimiento_tipica, $numero_cuadrillas_tipicas){
    $query="SELECT COUNT(*) FROM general_cuadrillas_tipicas WHERE proyecto='$db' AND codigo_actividad='$codigo_actividad'";
    $resultado=mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    if($data["COUNT(*)"]==0){
        $query1="INSERT INTO general_cuadrillas_tipicas (proyecto, codigo,actividad, oficiales_tipica, ayudantes_tipica, rendimiento_tipica, numero_cuadrillas_tipicas) VALUES ('$db', '$codigo_actividad', $oficiales_tipica, $ayudantes_tipica, $rendimiento_tipica, $numero_cuadrillas_tipicas)";
    }else{
        $query1="UPDATE general_cuadrillas_tipicas SET oficiales_tipica=$oficiales_tipica, ayudantes_tipica=$ayudantes_tipica, rendimiento_tipica=$rendimiento_tipica, numero_cuadrillas_tipicas=$numero_cuadrillas_tipicas";
    }
    $resultado1=mysqli_query($conexion, $query1);
    if(!$resultado1){
        die(mysqli_error($conexion));
        $enviar_resultado="Error";
    }else{
        $enviar_resultado="Ok";
    }

    $json_codificado = json_encode($enviar_resultado, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}

?>
