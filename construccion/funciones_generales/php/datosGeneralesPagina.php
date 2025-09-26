<?php session_start();

require("../../conexion.php");

$proyecto=$_SESSION['proyecto'];
$db=$_SESSION['db'];
$semana=$_SESSION['semana'];
$permiso=$_SESSION['permiso'];
$pdcActivo=$_SESSION['pdcActivo'];
$nombreUsuario=$_SESSION['nombreUsuario'];
$seccion=$_POST['seccion'];

$arreglo["proyecto"]=$_SESSION['proyecto'];
$arreglo["db"]=$_SESSION['db'];
$arreglo["semana"]=$_SESSION['semana'];
$arreglo["permiso"]=$_SESSION['permiso'];
$arreglo["pdcActivo"]=$_SESSION['pdcActivo'];
$arreglo["nombreUsuario"]=$_SESSION['nombreUsuario'];
$arreglo["seccion"]=$_POST['seccion'];

$query="SELECT COUNT(*) FROM $db"."_semanas_activas";
$resultado= mysqli_query($conexion, $query);
$data=mysqli_fetch_assoc($resultado);
$conteo=$data["COUNT(*)"];

if($conteo==0){
  $Fecha_Inicio_Sem=date("Y-m-d");
  $arreglo["Fecha_Inicio_SemYMD"]=$Fecha_Inicio_Sem;
  $Fecha_Fin_Sem=date("Y-m-d",strtotime($Fecha_Inicio_Sem ."+6 days"));
  $arreglo["Fecha_Fin_SemYMD"]=$Fecha_Fin_Sem;

  $arreglo["Fecha_Inicio_Sem"]=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Inicio_Sem));
  $arreglo["Fecha_Fin_Sem"]=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Fin_Sem));

  $arreglo["Fecha_datepicker"]=$Fecha_Inicio_Sem;
  $arreglo["Max_Semana"]=0;
  $_SESSION["Max_Semana"]=0;
  $arreglo["listadoSemanas"][]="";
  //echo "$Fecha_Inicio_Sem <br> $Fecha_Fin_Sem";
}else{
  $query1="SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
  $resultado1= mysqli_query($conexion, $query1);
  $data1=mysqli_fetch_assoc($resultado1);

  $Fecha_Inicio_Sem=$data1["Fecha_Inicio_Sem"];
  $arreglo["Fecha_Inicio_SemYMD"]=$Fecha_Inicio_Sem;
  $arreglo["Fecha_Inicio_Sem"]=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Inicio_Sem"));

  $Fecha_Fin_Sem=$data1["Fecha_Fin_Sem"];
  $arreglo["Fecha_Fin_SemYMD"]=$Fecha_Fin_Sem;
  $arreglo["Fecha_Fin_Sem"]=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Fin_Sem"));

  $Fecha_datepicker=$data1["Fecha_Fin_Sem"];
  $arreglo["Fecha_datepicker"]=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_datepicker"));

  $arreglo["Max_Semana"]=$data1["Semana"];
  $_SESSION["Max_Semana"]=$data1["Semana"];
  // echo "<script>console.log(" . $_SESSION["Max_Semana"] . ")</script>";
  $query2="SELECT Semanal_Confirmada, fechaCierreCompromisos, fechaCreacionSemana, (SELECT SUM(reprogramacion) FROM $db"."_semanas_activas WHERE Semana<=$semana) AS versionCronograma  FROM $db"."_semanas_activas WHERE Semana=$semana";
  $resultado2= mysqli_query($conexion, $query2);
  if(!$resultado2){
    die("Error");
  }else{
    $data2=mysqli_fetch_assoc($resultado2);
    $arreglo["Semanal_Confirmada"]=$data2["Semanal_Confirmada"];
    $_SESSION["Semanal_Confirmada"]=$data2["Semanal_Confirmada"];

    $arreglo["fechaCierreCompromisos"]=$data2["fechaCierreCompromisos"];
    $_SESSION["fechaCierreCompromisos"]=$data2["fechaCierreCompromisos"];

    $arreglo["fechaCreacionSemana"]=$data2["fechaCreacionSemana"];
    $_SESSION["fechaCreacionSemana"]=$data2["fechaCreacionSemana"];

    $arreglo["versionCronograma"]=$data2["versionCronograma"];
    $_SESSION["versionCronograma"]=$data2["versionCronograma"];

    $query3="SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas";
    $resultado3= mysqli_query($conexion, $query3);
    if(!$resultado3){
      die("Error");
    }else{
      while($data3=mysqli_fetch_assoc($resultado3)){
        $arreglo["listadoSemanas"][]=array_map("utf8_encode", $data3);
      }
    }
  }
}

$_SESSION["Fecha_Inicio_SemYMD"] = $arreglo["Fecha_Inicio_SemYMD"];

$arregloFinal["data"]=$arreglo;

echo utf8_decode(json_encode($arregloFinal));

mysqli_close($conexion);

?>
