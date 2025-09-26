<?php session_start();

require __DIR__ . '../../../../vendor/autoload.php';
// require '../../composerFiles/vendor/autoload.php';
require ("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];
// $db="prueba";
// $opcion="cargarExcel";

if($opcion == "cargarExcel"){
  $archivoExcel=$_FILES["archivoExcel"];
  $f_inicio_sem=date("Y-m-d",strtotime($_GET["f_inicio_sem"]));
  // $archivoExcel=1;
}elseif ($opcion == "eliminarActualizacion") {
  $semana = $_POST["semana"];
}

switch($opcion){
  case "cargarExcel":
  cargarExcel($conexion, $db, $archivoExcel, $f_inicio_sem);
  break;

  case "eliminarActualizacion":
  eliminarActualizacion($semana, $conexion, $db);
  break;


}

function cargarExcel($conexion, $db, $archivoExcel, $f_inicio_sem){


  $semanaActual = $_SESSION["Max_Semana"];
  $info = new SplFileInfo($archivoExcel["name"]);
  $extension = pathinfo($info->getFilename(), PATHINFO_EXTENSION);
  // $extension = "xlsx";
  if($extension == "csv" || $extension == "xlsx"){
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    // $filename = "actualizacionCronogramaLPS.xlsx";
    $filename = $archivoExcel['tmp_name'];

    $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filename);

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

    $spreadsheet = $reader->load($filename);
    $tabla = $spreadsheet->getActiveSheet()->toArray();
    $tabla2 = $spreadsheet->getActiveSheet()->toArray();
    $indexaciones = count($tabla);


    $numeroFila = 0;
    $query = "TRUNCATE TABLE ".$db."_programa";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
    } else{
      $query = "INSERT INTO ".$db."_programa (Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica) VALUES ";
      $numeroPuntos = "";
      foreach($tabla as $row){
        if($numeroFila != 0){
          $arrayEsquemaActividad = explode(".", (string)$row[0]);
          $arrayEsquemaActividadFinal["nivel_1"] = (string)$arrayEsquemaActividad[0];
          for($i = 1; $i < (count($arrayEsquemaActividad)); $i++){
            $nivelAnterior = "nivel_$i";
            $nivelActual = "nivel_" . ($i+1);
            $arrayEsquemaActividadFinal[$nivelActual] = (string)$arrayEsquemaActividadFinal[$nivelAnterior];

            if(!$arrayEsquemaActividad[$i]){
              $arrayEsquemaActividadFinal[$nivelActual] = "";
            }else{
              $arrayEsquemaActividadFinal[$nivelActual] = (string)$arrayEsquemaActividadFinal[$nivelActual] . "." . (string)$arrayEsquemaActividad[$i];
            }
          }

          $contadorNiveles = 0;
          for($i = 1; $i < (count($arrayEsquemaActividad)+1); $i++){
            $numeroNivel = "nivel_$i";
            $nivel = (string)$arrayEsquemaActividadFinal[$numeroNivel];
            $iteracion = "";
            foreach($tabla2 as $row2){
              $iteracionAnterior = $iteracion;
              if($nivel === (string)$row2[0] && $nivel != ""){
                $iteracion = $row2[1];
                $contadorNiveles ++;
                break;
              }
            }
            $arrayEsquemaTexto[$numeroNivel][]= ($iteracion == "") ? "" : "$iteracion";
          }

          $arrayEsquemaTexto["numeroNiveles"][] = $contadorNiveles;

          $nivelInicio = "nivel_$contadorNiveles";
          if($contadorNiveles == 1){
            $nombre = "<b>" . end($arrayEsquemaTexto[$nivelInicio]) . "</b>";
          }else{
            $nombre = "<b>" . end($arrayEsquemaTexto[$nivelInicio]) . ",  </b> <small>[Capítulo:";
          }

          for($i = ($contadorNiveles - 1); $i > 0; $i--){
            $nivelActual = "nivel_$i";
            $nombre .= end($arrayEsquemaTexto[$nivelActual]) . ",  ";
          }

          if($contadorNiveles > 1){
            $nombre = substr($nombre, 0, -3);
            $nombre = $nombre . "]</small>";
          }
          $arrayEsquemaTexto["nombre"][] = $nombre;

          $row[1] = str_replace("'", "\'", $nombre);
          $row[1] = str_replace('"', '\"', $nombre);


          if($row[2] == "Sí"){
            $row[2] = 1;
          }else{
            $row[2] = 0;
          }

          if($row[5] == "Sí"){
            $row[5] = 1;
          }else{
            $row[5] = 0;
          }

          $row[3]=date("Y-m-d", strtotime($row[3]));
          $row[4]=date("Y-m-d", strtotime($row[4]));

          $arreglo = ["Id"=>$row[0],"Actividad"=>$row[1],"Titulo"=>$row[2],"Fecha_Inicio"=>$row[3],"Fecha_Fin"=>$row[4],"Ruta_Critica"=>$row[5]];

          if((string)$row[0] != ""){
            $query .= "('', '".(string)$row[0]."', '".(string)$row[1]."', ".$row[2].", '".$row[3]."', '".$row[4]."', ".$row[5]."), ";
            $arregloProgramaNuevo["data"][]=array_map("utf8_encode", $arreglo);
          }
        }
        $numeroFila++;
      }
      $query = substr($query,0,-2);
      $resultado=mysqli_query($conexion, $query);
      if(!$resultado){
        $errores = "No carga desde excel";
      } else{
        $errores = "";

        $queryProgramaAnterior = "SELECT * FROM ".$db."_programa_consolidado WHERE Semana = $semanaActual";
        $resultadoProgramaAnterior=mysqli_query($conexion, $queryProgramaAnterior);
        if(!$resultadoProgramaAnterior){
          $errores = "No carga el programa anterior";
        } else{
          while ($dataProgramaAnterior=mysqli_fetch_assoc($resultadoProgramaAnterior)){
            $arregloProgramaAnterior["data"][]=array_map("utf8_encode", $dataProgramaAnterior);
          }
        }

        $semanaNueva = $semanaActual+1;
        $Consecutivo_en_Programa = 0;
        $queryInsertarProgramaNuevo = "INSERT INTO `".$db."_programa_consolidado`(`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `programaAnteriorAsociar`) VALUES ";
        foreach($arregloProgramaNuevo["data"] as $rowProgramaNuevo){
          $rowProgramaNuevo["Ejecutado"] = "";
          foreach($arregloProgramaAnterior["data"] as $rowProgramaAnterior){
            if($rowProgramaNuevo["Actividad"] == $rowProgramaAnterior["Actividad"]){
              $rowProgramaNuevo["Ejecutado"] = $rowProgramaAnterior["Ejecutado"] == "" ? "NULL" : $rowProgramaAnterior["Ejecutado"];
              $rowProgramaNuevo["Estado"] = $rowProgramaAnterior["Estado"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Estado"] . "'";
              $rowProgramaNuevo["Semanas_Inicio"] = $rowProgramaAnterior["Semanas_Inicio"] == "" ? "NULL" : $rowProgramaAnterior["Semanas_Inicio"];
              $rowProgramaNuevo["Estado_Restricciones"] = $rowProgramaAnterior["Estado_Restricciones"] == "" ? "NULL" : $rowProgramaAnterior["Estado_Restricciones"];
              $rowProgramaNuevo["D_y_E"] = $rowProgramaAnterior["D_y_E"] == "" ? "NULL" : "'" . $rowProgramaAnterior["D_y_E"] . "'";
              $rowProgramaNuevo["Materiales"] = $rowProgramaAnterior["Materiales"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Materiales"] . "'";
              $rowProgramaNuevo["MdeO"] = $rowProgramaAnterior["MdeO"] == "" ? "NULL" : "'" . $rowProgramaAnterior["MdeO"] . "'";
              $rowProgramaNuevo["Equipos"] = $rowProgramaAnterior["Equipos"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Equipos"] . "'";
              $rowProgramaNuevo["Predecesora"] = $rowProgramaAnterior["Predecesora"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Predecesora"] . "'";
              $rowProgramaNuevo["Pdto_Cons"] = $rowProgramaAnterior["Pdto_Cons"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Pdto_Cons"] . "'";
              $rowProgramaNuevo["Modelo"] = $rowProgramaAnterior["Modelo"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Modelo"] . "'";
              $rowProgramaNuevo["Sub_Contratista"] = $rowProgramaAnterior["Sub_Contratista"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Sub_Contratista"] . "'";
              $rowProgramaNuevo["Responsable_AIA"] = $rowProgramaAnterior["Responsable_AIA"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Responsable_AIA"] . "'";
              $rowProgramaNuevo["Observaciones"] = $rowProgramaAnterior["Observaciones"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Observaciones"] . "'";
              $rowProgramaNuevo["Ult_Act_Est"] = $rowProgramaAnterior["Ult_Act_Est"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Ult_Act_Est"] . "'";
              $rowProgramaNuevo["Ult_Act_Restr"] = $rowProgramaAnterior["Ult_Act_Restr"] == "" ? "NULL" : "'" . $rowProgramaAnterior["Ult_Act_Restr"] . "'";
              $rowProgramaNuevo["Activa"] = $rowProgramaAnterior["Activa"] == "" ? "NULL" : $rowProgramaAnterior["Activa"];
              $rowProgramaNuevo["Ejecutado_Siguiente_Semana"] = $rowProgramaAnterior["Ejecutado_Siguiente_Semana"] == "" ? "NULL" : $rowProgramaAnterior["Ejecutado_Siguiente_Semana"];
              $rowProgramaNuevo["codigo_actividad"] = $rowProgramaAnterior["codigo_actividad"] == "" ? "NULL" : "'" . (string)$rowProgramaAnterior["codigo_actividad"] . "'";
              $rowProgramaNuevo["medir_productividad"] = $rowProgramaAnterior["medir_productividad"] == "" ? "NULL" : $rowProgramaAnterior["medir_productividad"];
              $rowProgramaNuevo["cantidad_ppto"] = $rowProgramaAnterior["cantidad_ppto"] == "" ? "NULL" : $rowProgramaAnterior["cantidad_ppto"];
              $rowProgramaNuevo["unidad"] = $rowProgramaAnterior["unidad"] == "" ? "NULL" : "'" . $rowProgramaAnterior["unidad"] . "'";

              $arregloProgramaActualizado["data"][]=array_map("utf8_encode", $rowProgramaNuevo);

              $queryInsertarProgramaNuevo .= "(null, $semanaNueva, $Consecutivo_en_Programa, '" . (string)$rowProgramaNuevo['Id'] . "', '" . $rowProgramaNuevo['Actividad'] . "', " . $rowProgramaNuevo['Titulo'] . ", '" . $rowProgramaNuevo['Fecha_Inicio'] . "', '" . $rowProgramaNuevo['Fecha_Fin'] . "', " . $rowProgramaNuevo['Ruta_Critica'] . ", " . $rowProgramaNuevo['Ejecutado'] . ", " . $rowProgramaNuevo['Estado'] . ", " . $rowProgramaNuevo['Semanas_Inicio'] . ", " . $rowProgramaNuevo['Estado_Restricciones'] . ", " . $rowProgramaNuevo['D_y_E'] . ", " . $rowProgramaNuevo['Materiales'] . ", " . $rowProgramaNuevo['MdeO'] . ", " . $rowProgramaNuevo['Equipos'] . ", " . $rowProgramaNuevo['Predecesora'] . ", " . $rowProgramaNuevo['Pdto_Cons'] . ", " . $rowProgramaNuevo['Modelo'] . ", " . $rowProgramaNuevo['Sub_Contratista'] . ", " . $rowProgramaNuevo['Responsable_AIA'] . ", " . $rowProgramaNuevo['Observaciones'] . ", " . $rowProgramaNuevo['Ult_Act_Est'] . ", " . $rowProgramaNuevo['Ult_Act_Restr'] . ", " . $rowProgramaNuevo['Activa'] . ", " . $rowProgramaNuevo['Ejecutado_Siguiente_Semana'] . ", " . $rowProgramaNuevo['codigo_actividad'] . ", " . $rowProgramaNuevo['medir_productividad'] . ", " . $rowProgramaNuevo['cantidad_ppto'] . ", " . $rowProgramaNuevo['unidad'] . ", NULL), ";

              $Consecutivo_en_Programa++;
              break;
            }
          }
          if($rowProgramaNuevo["Ejecutado"] === ""){
            $queryInsertarProgramaNuevo .= "(null, $semanaNueva, $Consecutivo_en_Programa, '" . $rowProgramaNuevo['Id'] . "', '" . $rowProgramaNuevo['Actividad'] . "', " . $rowProgramaNuevo['Titulo'] . ", '" . $rowProgramaNuevo['Fecha_Inicio'] . "', '" . $rowProgramaNuevo['Fecha_Fin'] . "', " . $rowProgramaNuevo['Ruta_Critica'] . ", NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '*No Asociada*'), ";

            $Consecutivo_en_Programa++;
          }
        }
        $queryInsertarProgramaNuevo = utf8_decode(substr($queryInsertarProgramaNuevo,0,-2));

        $queryBorrarProgramaSiguienteSem = "DELETE FROM ".$db."_programa_consolidado WHERE Semana = $semanaNueva";
        $resultadoBorrarProgramaSiguienteSem=mysqli_query($conexion, $queryBorrarProgramaSiguienteSem);
        if(!$resultadoBorrarProgramaSiguienteSem){
          $errores = "No sobreescribe el nuevo programa";
        } else{
          $resultadoInsertarProgramaNuevo=mysqli_query($conexion, $queryInsertarProgramaNuevo);
          if(!$resultadoInsertarProgramaNuevo){
            $errores = "No carga el nuevo programa";
          }else{
            // echo "ok";
            // verificar_resultado($resultadoInsertarProgramaNuevo, $errores);
            $semana = $semanaNueva;
            $ejecucionActualizada = 1;
            require("../funciones_generales/php/modificar_sem_estado.php");
          }
        }
        // $json_codificado = json_encode($arregloProgramaActualizado, JSON_UNESCAPED_UNICODE);
        // echo utf8_decode($json_codificado);
      }
    }
    mysqli_close($conexion);
  }
}

function eliminarActualizacion($semana, $conexion, $db){
  $semanaEliminar = $semana + 1;
  $errores = "";

  $query = "DELETE FROM `".$db."_programa_consolidado` WHERE Semana = $semanaEliminar";
  $resultado=mysqli_query($conexion, $query);
  if(!$resultado){
    $errores = "No elimina el programa anterior";
  } else{
    verificar_resultado($resultado, $errores);
  }

  mysqli_close($conexion);
}

function verificar_resultado($resultado, $errores){
    if(!$resultado){
        $informacion["respuesta"] ="ERROR";
    }
    if($errores ==''){
        $informacion["respuesta"] = "BIEN";
    }
    echo json_encode($informacion);
}

 ?>
