<?php session_start();
$clase=$_GET["clase"];
$activa=$_GET["activa"];

if($clase=="total"){
    $_SESSION["lookahead_tpc"]=0;
    $_SESSION["no_iniciadas_tpc"]=0;
    $_SESSION["en_ejecucion_tpc"]=0;
    $_SESSION["terminadas_tpc"]=0;
    $_SESSION["total_tpc"]=1;
}else{
    $_SESSION["total_tpc"]=0;
}

if($clase=="lookahead"){
    if($activa==1){
        $_SESSION["lookahead_tpc"]=1;
    }else{
        $_SESSION["lookahead_tpc"]=0;
    }
}else if($clase=="no_iniciadas"){
    if($activa==1){
        $_SESSION["no_iniciadas_tpc"]=1;
    }else{
        $_SESSION["no_iniciadas_tpc"]=0;
    }
}else if($clase=="en_ejecucion"){
    if($activa==1){
        $_SESSION["en_ejecucion_tpc"]=1;
    }else{
        $_SESSION["en_ejecucion_tpc"]=0;
    }
}else if($clase=="terminadas"){
    if($activa==1){
        $_SESSION["terminadas_tpc"]=1;
    }else{
        $_SESSION["terminadas_tpc"]=0;
    }
}

header("Location: tareas_periodicas_compuestas.php");

?>