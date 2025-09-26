<?php
	require ("../conexion.php");
  $db=/*"prueba"*/$_GET['db'];
  $semana=/*7*/$_GET["semana"];
  $activa_no_requeridas=/*1*/$_GET["activa_no_requeridas"];
  $activa_lookahead=/*1*/$_GET["activa_lookahead"];
  $activa_no_iniciadas=/*1*/$_GET["activa_no_iniciadas"];
  $activa_a_tiempo=/*1*/$_GET["activa_a_tiempo"];
	$activa_atrasadas=/*1*/$_GET["activa_atrasadas"];
  $activa_terminadas=/*1*/$_GET["activa_terminadas"];

	// $db="brizaDelCabrero";
  // $semana=12;
  // $activa_no_requeridas=0;
  // $activa_lookahead=0;
  // $activa_no_iniciadas=0;
  // $activa_a_tiempo=0;
	// $activa_atrasadas=1;
  // $activa_terminadas=0;

  $script="";
  if($activa_no_requeridas==1){
      $script .= "AND ((Semanas_Inicio>6 AND Ejecutado=0 AND Estado='No Requerida') ";
	}

  if($activa_lookahead==1){
      if($script==""){
          $script .= "AND ((Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0  AND Estado='En Liberación de Restricciones') ";
      }else{
          $script .= "OR (Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0  AND Estado='En Liberación de Restricciones') ";
      }
  }
  if($activa_no_iniciadas==1){
      if($script==""){
          $script .= "AND ((Semanas_Inicio<=0 AND Ejecutado=0  AND (Estado='Debe Iniciar esta Semana' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')) ";
      }else{
          $script .= "OR (Semanas_Inicio<=0 AND Ejecutado=0  AND (Estado='Debe Iniciar esta Semana' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')) ";
      }
  }
  if($activa_a_tiempo==1){
      if($script==""){
          $script .= "AND ((Ejecutado>0 AND Ejecutado<1  AND Estado='A Tiempo') ";
      }else{
          $script .= "OR (Ejecutado>0 AND Ejecutado<1  AND Estado='A Tiempo') ";
      }
  }
	if($activa_atrasadas==1){
      if($script==""){
          $script .= "AND ((Ejecutado>=0 AND Ejecutado<1  AND (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')) ";
      }else{
          $script .= "OR (Ejecutado>=0 AND Ejecutado<1  AND (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')) ";
      }
  }
  if($activa_terminadas==1){
      if($script==""){
          $script .= "AND ((Ejecutado=1  AND (Estado='Terminada' OR Estado='Terminada Antes')) ";
      }else{
          $script .= "OR (Ejecutado=1  AND (Estado='Terminada' OR Estado='Terminada Antes')) ";
      }
  }
  if($script==""){
  }else{
      $script .= ")";
  }

	//echo utf8_decode(json_encode($script));

  $query="SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 $script";
  $resultado= mysqli_query($conexion, $query);
  $data=mysqli_fetch_assoc($resultado);
  $conteo=$data["COUNT(*)"];
  if ($conteo==0){
      $arreglo1["data"][]=array("Consecutivo" => "","Semana" => "","Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Semanas_Inicio" =>"","Fecha_Inicio" => "","Fecha_Fin" => "", "Ruta_Critica" => "", "unidad" => "", "cantidad_ppto" => "", "medir_productividad" => "", "codigo_actividad" => "", "Ejecutado_Teorico" =>"", "Ejecutado" => "","Estado" => "","Estado_Restricciones" =>"","Responsable_AIA" => "","Sub_Contratista" => "", "boton" =>"");
      echo json_encode($arreglo1);
  }else{
      $query_= "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
      $resultado_ = mysqli_query($conexion, $query_);
      $data_=mysqli_fetch_assoc($resultado_);
      $Fecha_Inicio_Sem=date("Y-m-d",strtotime($data_["Fecha_Inicio_Sem"]));
      $Fecha_Fin_Sem=date("Y-m-d",strtotime($data_["Fecha_Fin_Sem"]));

      $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL $script ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";
      $resultado1 = mysqli_query($conexion, $query1);

      if(!$resultado1){
          die("Error");
      } else{
          while($data1=mysqli_fetch_assoc($resultado1)){
              $titulo=$data1['Titulo'];
              $Fecha_Inicio_Act=date("Y-m-d",strtotime($data1['Fecha_Inicio']));
              $Fecha_Fin_Act=date("Y-m-d",strtotime($data1['Fecha_Fin']));
              if($titulo==1){
                  $data1["boton"]="No Boton";
              }else{
                  $data1["boton"]="Boton";
              }

              //echo "<li> $titulo, $Fecha_Inicio_Sem, $Fecha_Fin_Sem, $Fecha_Inicio_Act, $Fecha_Fin_Act <br>";

							$diasLleva = ((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400);
							$diasTotales = ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1;


              if($titulo==1 /*&& $data1['Fecha_Inicio']==NULL && $data1['Fecha_Fin']==NULL*/){
                  $data1["Ejecutado_Teorico"]= NULL;
              }else if ($diasLleva>=1 && $diasTotales>=$diasLleva){
                  $data1["Ejecutado_Teorico"]= ($diasLleva / $diasTotales);
              }else if($diasTotales<$diasLleva){
                  $data1["Ejecutado_Teorico"]= 1;
              }else if($diasLleva<1){
                  $data1["Ejecutado_Teorico"]=0;
              }

              $arreglo["data"][]=array_map("utf8_encode", $data1);
          }
          $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
          echo utf8_decode($json_codificado);
      }
      mysqli_free_result($resultado);
  }
  mysqli_close($conexion);


?>
