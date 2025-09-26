<?php session_start();
$clase=$_GET["clase"];
$activa=$_GET["activa"];

if($clase=="total"){
    $_SESSION["lookahead_tpr"]=0;
    $_SESSION["no_iniciadas_tpr"]=0;
    $_SESSION["en_ejecucion_tpr"]=0;
    $_SESSION["terminadas_tpr"]=0;
    $_SESSION["total_tpr"]=1;
}else{
    $_SESSION["total_tpr"]=0;
}

if($clase=="lookahead"){
    if($activa==1){
        $_SESSION["lookahead_tpr"]=1;
    }else{
        $_SESSION["lookahead_tpr"]=0;
    }
}else if($clase=="no_iniciadas"){
    if($activa==1){
        $_SESSION["no_iniciadas_tpr"]=1;
    }else{
        $_SESSION["no_iniciadas_tpr"]=0;
    }
}else if($clase=="en_ejecucion"){
    if($activa==1){
        $_SESSION["en_ejecucion_tpr"]=1;
    }else{
        $_SESSION["en_ejecucion_tpr"]=0;
    }
}else if($clase=="terminadas"){
    if($activa==1){
        $_SESSION["terminadas_tpr"]=1;
    }else{
        $_SESSION["terminadas_tpr"]=0;
    }
}

header("Location: tareas_propias.php");

?>