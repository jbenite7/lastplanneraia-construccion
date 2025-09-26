<?php session_start();
$db = $_POST['db'];
$semana = $_POST['semana'];

require("../conexion.php");

unset(/*$_SESSION["no_requeridas_intermedia"],*/
$_SESSION["lookahead_intermedia"],
$_SESSION["no_iniciadas_intermedia"],
$_SESSION["a_tiempo_intermedia"],
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

if(isset($_SESSION['a_tiempo'])){
  if($_SESSION['a_tiempo']==1){
    $arreglo['activa_a_tiempo'] = 1;
  }else{
    $arreglo['activa_a_tiempo'] = 0;
  }
}else{
  $arreglo['activa_a_tiempo'] = 0;
}

if(isset($_SESSION['atrasadas'])){
  if($_SESSION['atrasadas']==1){
    $arreglo['activa_atrasadas'] = 1;
  }else{
    $arreglo['activa_atrasadas'] = 0;
  }
}else{
  $arreglo['activa_atrasadas'] = 0;
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

$query="SELECT (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Estado='No Requerida') AS 'no_requeridas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Estado='En Liberación de Restricciones') AS 'lookahead', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND (Estado='Debe Iniciar esta Semana' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')) AS 'no_iniciadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Estado='A Tiempo') AS 'a_tiempo', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')) AS 'atrasadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND (Estado='Terminada' OR Estado='Terminada Antes')) AS 'terminadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0) AS 'total'";
$resultado= mysqli_query($conexion, $query);
if(!$resultado){
  die("Error");
}else{
  $data=mysqli_fetch_assoc($resultado);
  $arreglo['no_requeridas']=$data['no_requeridas'];
  $arreglo['lookahead']=$data['lookahead'];
  $arreglo['no_iniciadas']=$data['no_iniciadas'];
  $arreglo['a_tiempo']=$data['a_tiempo'];
  $arreglo['terminadas']=$data['terminadas'];
  $arreglo['atrasadas']=$data['atrasadas'];
  $arreglo['total']=$data['total'];
  $arregloFinal["data"]=$arreglo;
  echo utf8_decode(json_encode($arregloFinal));
}
mysqli_close($conexion);

?>
