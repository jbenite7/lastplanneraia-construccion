<?php
    require("../../conexion.php");
    $db = $_GET["db"];
    //$semana = $_GET["semana"];
    $queryMaxSem = "SELECT MAX(Semana) FROM $db"."_semanas_activas";
    $resultadoMaxSem = mysqli_query($conexion, $queryMaxSem);
    $data=mysqli_fetch_assoc($resultadoMaxSem);
    $maxSemana = $data["MAX(Semana)"];

    for ($semana=1; $semana < ($maxSemana + 1) ; $semana++) {

      $queryFechaInicioSem = "SELECT Fecha_Inicio_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
      $resultadoFechaInicioSem = mysqli_query($conexion, $queryFechaInicioSem);
      $data=mysqli_fetch_assoc($resultadoFechaInicioSem);
      $f_inicio_sem = $data["Fecha_Inicio_Sem"];
      //echo $f_inicio_sem;



      $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana";
      $resultado5= mysqli_query($conexion, $query2);
      if(!$resultado5){
          mysqli_error($conexion);
      } else{
          $query3="UPDATE $db"."_programa_consolidado SET Semanas_Inicio= CASE";
          $query3_1=" Estado_Restricciones= CASE";

          while($data=mysqli_fetch_assoc($resultado5)){
              $Id=$data["Consecutivo_en_Programa"];
              $Id_Real=$data["Id"];
              $Titulo=$data["Titulo"];
              $actividad=$data["Actividad"];
              $hoy= $f_inicio_sem;
              $manana= date("Y-m-d",strtotime($data["Fecha_Inicio"]));
              $dias=(strtotime($manana)-strtotime($hoy))/86400;
              $dias=floor($dias);
              $semanas=floor($dias/7);
              //echo "<li>" . "$Id_Real, $dias, $semanas";
              $Estado_Restricciones='NULL';
              if($Titulo==0){
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
              }


              if($data["Fecha_Inicio"]==NULL && $data["Fecha_Fin"]==NULL){
                  $semanas='NULL';
              }else{
                  if($semanas<0 || $semanas==-0){
                      $ID=$data["Id"];
                      $inicio=$data["Fecha_Inicio"];
                      $fin=$data["Fecha_Fin"];
                      $semanas=0;
                  }
              }




              $query3 .=" WHEN Consecutivo_en_Programa='$Id' THEN $semanas";
              $query3_1 .=" WHEN Consecutivo_en_Programa='$Id' AND Titulo=0 THEN $Estado_Restricciones WHEN Consecutivo_en_Programa='$Id' AND Titulo=1 THEN NULL";
          }
          $query3 .=" END,";
          $query3_1 .=" END WHERE Semana=$semana";
          $query3 .=$query3_1;
      };

      $resultado6=mysqli_query($conexion, $query3);
      sleep(0.5);

      $query4 = "UPDATE $db"."_programa_consolidado SET Ruta_Critica=NULL WHERE Titulo=1 AND Semana=$semana";

      $resultado7=mysqli_query($conexion, $query4);


      $query5 = "UPDATE $db"."_programa_consolidado SET Ejecutado=NULL, Semanas_Inicio=NULL WHERE Fecha_Inicio=NULL AND Fecha_Fin=NULL AND Titulo=1 AND Semana=$semana";

      $resultado8=mysqli_query($conexion, $query5);


      $fin_semana= date("Y-m-d",strtotime("$f_inicio_sem + 6 days"));

      $query6 = "UPDATE $db"."_programa_consolidado SET
         Estado= CASE
            WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) < 0 THEN 'Terminada Antes'

            WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) = 0 THEN 'Terminada'

            WHEN Ejecutado < 1 AND Ejecutado >= 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 THEN 'Atrasada'

            WHEN Ejecutado < 1 AND Ejecutado > 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) <= 0 THEN 'A Tiempo'

            WHEN Semanas_Inicio <= 0 AND Estado_Restricciones = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana'

            WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 AND Ejecutado=0 THEN 'Ya Debió Iniciar y Restricciones Pendientes'

            WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$f_inicio_sem', Fecha_Inicio) AND DATEDIFF('$f_inicio_sem', Fecha_Inicio) >= 1 THEN (DATEDIFF('$f_inicio_sem', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$f_inicio_sem', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$f_inicio_sem', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana y Restricciones Pendientes'

            WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado = 0 THEN 'En Liberación de Restricciones'

            WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado > 0 THEN 'A Tiempo'

            ELSE 'No Requerida'
         END
        WHERE Titulo=0 AND Semana=$semana";
      $resultado9=mysqli_query($conexion, $query6);
      if(!$resultado9){
        die(mysqli_error($conexion));
      }else{
        echo "<li>Semana $semana OK";
      }

    }

    mysqli_close($conexion);


?>
