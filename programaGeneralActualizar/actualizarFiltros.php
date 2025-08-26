<?php session_start();
$db = $_POST['db'];
$semana = $_POST['semana'];

require("../conexion.php");

unset(/*$_SESSION["no_requeridas_intermedia"],*/
$_SESSION["lookahead_intermedia"],
$_SESSION["no_iniciadas_intermedia"],
$_SESSION["en_ejecucion_intermedia"],
$_SESSION["terminadas_intermedia"]);

if(isset($_SESSION['no_requeridas'])){
  if($_SESSION['no_requeridas']==1){
    $arreglo['activa_no_requeridas'] = 1;
  }else{
    $arreglo['activa_no_requeridas'] = 0;
  }
}else{
  $arreglo['activa_no_requeridas'] = 0;
}

if(isset($_SESSION['lookahead'])){
  if($_SESSION['lookahead']==1){
    $arreglo['activa_lookahead'] = 1;
  }else{
    $arreglo['activa_lookahead'] = 0;
  }
}else{
  $arreglo['activa_lookahead'] = 0;
}

if(isset($_SESSION['no_iniciadas'])){
  if($_SESSION['no_iniciadas']==1){
    $arreglo['activa_no_iniciadas'] = 1;
  }else{
    $arreglo['activa_no_iniciadas'] = 0;
  }
}else{
  $arreglo['activa_no_iniciadas'] = 0;
}

if(isset($_SESSION['en_ejecucion'])){
  if($_SESSION['en_ejecucion']==1){
    $arreglo['activa_en_ejecucion'] = 1;
  }else{
    $arreglo['activa_en_ejecucion'] = 0;
  }
}else{
  $arreglo['activa_en_ejecucion'] = 0;
}

if(isset($_SESSION['terminadas'])){
  if($_SESSION['terminadas']==1){
    $arreglo['activa_terminadas'] = 1;
  }else{
    $arreglo['activa_terminadas'] = 0;
  }
}else{
  $arreglo['activa_terminadas'] = 0;
}

$query="SELECT (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Semanas_Inicio>6 AND Ejecutado=0 AND Titulo=0) AS 'no_requeridas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0 AND Titulo=0) AS 'lookahead', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Semanas_Inicio<=0 AND Ejecutado=0 AND Titulo=0) AS 'no_iniciadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Ejecutado>0 AND Ejecutado<1 AND Titulo=0) AS 'en_ejecucion', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Ejecutado=1 AND Titulo=0) AS 'terminadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0) AS 'total'";
$resultado= mysqli_query($conexion, $query);
if(!$resultado){
  die("Error");
}else{
  $data=mysqli_fetch_assoc($resultado);
  $arreglo['no_requeridas']=$data['no_requeridas'];
  $arreglo['lookahead']=$data['lookahead'];
  $arreglo['no_iniciadas']=$data['no_iniciadas'];
  $arreglo['en_ejecucion']=$data['en_ejecucion'];
  $arreglo['terminadas']=$data['terminadas'];
  $arreglo['total']=$data['total'];
  $arregloFinal["data"]=$arreglo;
  echo utf8_decode(json_encode($arregloFinal));
}
mysqli_close($conexion);

?>
