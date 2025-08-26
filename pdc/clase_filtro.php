<?php session_start();
$clase=$_GET["clase"];
$activa=$_GET["activa"];

if($clase=="total"){
    $_SESSION["no_requeridas_intermedia"]=0;
    $_SESSION["lookahead_intermedia"]=0;
    $_SESSION["no_iniciadas_intermedia"]=0;
    $_SESSION["en_ejecucion_pendientes_intermedia"]=0;
    $_SESSION["en_ejecucion_terminadas_intermedia"]=0;
    $_SESSION["total_intermedia"]=1;
}else{
    $_SESSION["total_intermedia"]=0;
}

if($clase=="lookahead"){
    if($activa==1){
        $_SESSION["lookahead_intermedia"]=1;
    }else{
        $_SESSION["lookahead_intermedia"]=0;
    }
}else if($clase=="no_iniciadas"){
    if($activa==1){
        $_SESSION["no_iniciadas_intermedia"]=1;
    }else{
        $_SESSION["no_iniciadas_intermedia"]=0;
    }
}else if($clase=="en_ejecucion_pendientes"){
    if($activa==1){
        $_SESSION["en_ejecucion_pendientes_intermedia"]=1;
    }else{
        $_SESSION["en_ejecucion_pendientes_intermedia"]=0;
    }
}else if($clase=="en_ejecucion_terminadas"){
    if($activa==1){
        $_SESSION["en_ejecucion_terminadas_intermedia"]=1;
    }else{
        $_SESSION["en_ejecucion_terminadas_intermedia"]=0;
    }
}

header("Location: programacion_intermedia.php");

?>