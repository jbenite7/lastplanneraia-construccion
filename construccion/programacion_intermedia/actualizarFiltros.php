<?php session_start();
$db = $_POST['db'];
$semana = $_POST['semana'];

require("../conexion.php");

unset($_SESSION["no_requeridas"],
$_SESSION["lookahead"],
$_SESSION["no_iniciadas"],
$_SESSION["en_ejecucion"],
$_SESSION["terminadas"]);

if(isset($_SESSION['lookahead_intermedia'])){
    if($_SESSION['lookahead_intermedia']==1){
        $arreglo['activa_lookahead'] = 1;
    }else{
        $arreglo['activa_lookahead'] = 0;
    }
}else{
    $arreglo['activa_lookahead'] = 0;
}

if(isset($_SESSION['no_iniciadas_intermedia'])){
    if($_SESSION['no_iniciadas_intermedia']==1){
        $arreglo['activa_no_iniciadas'] = 1;
    }else{
        $arreglo['activa_no_iniciadas'] = 0;
    }
}else{
    $arreglo['activa_no_iniciadas'] = 0;
}

if(isset($_SESSION['en_ejecucion_pendientes_intermedia'])){
    if($_SESSION['en_ejecucion_pendientes_intermedia']==1){
        $arreglo['activa_en_ejecucion_pendientes'] = 1;
    }else{
        $arreglo['activa_en_ejecucion_pendientes'] = 0;
    }
}else{
    $arreglo['activa_en_ejecucion_pendientes'] = 0;
}

if(isset($_SESSION['en_ejecucion_terminadas_intermedia'])){
    if($_SESSION['en_ejecucion_terminadas_intermedia']==1){
        $arreglo['activa_en_ejecucion_terminadas'] = 1;
    }else{
        $arreglo['activa_en_ejecucion_terminadas'] = 0;
    }
}else{
    $arreglo['activa_en_ejecucion_terminadas'] = 0;
}

$query="SELECT (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE ((Semanas_Inicio<=0 AND Ejecutado=1) OR (Semanas_Inicio>6 AND Ejecutado=0)) AND Semana=$semana AND Titulo=0) AS 'no_requeridas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0 AND Titulo=0) AS 'lookahead', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Semanas_Inicio<=0 AND Ejecutado=0 AND Titulo=0) AS 'no_iniciadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semanas_Inicio<=6 AND Semana=$semana AND Ejecutado>0 AND Ejecutado<1 AND Titulo=0 AND Estado_Restricciones<1) AS 'en_ejecucion_pendientes', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semanas_Inicio<=6 AND Semana=$semana AND Ejecutado>0 AND Ejecutado<1 AND Titulo=0 AND Estado_Restricciones=1) AS 'en_ejecucion_terminadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semanas_Inicio<=6 AND Ejecutado<1 AND Semana=$semana AND Titulo=0) AS 'total'";
$stmt = $db->query($query);
if(!$stmt){
  die("Error");
}else{
  $data = $stmt->fetch();
  $arreglo['no_requeridas']=$data['no_requeridas'];
  $arreglo['lookahead']=$data['lookahead'];
  $arreglo['no_iniciadas']=$data['no_iniciadas'];
  $arreglo['en_ejecucion_pendientes']=$data['en_ejecucion_pendientes'];
  $arreglo['en_ejecucion_terminadas']=$data['en_ejecucion_terminadas'];
  $arreglo['total']=$data['total'];
  $arregloFinal["data"]=$arreglo;
  echo utf8_decode(json_encode($arregloFinal));
}
// mysqli_close($conexion);

?>
