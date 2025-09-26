<?php session_start();
$clase=$_GET["clase"];
$activa=$_GET["activa"];

if($clase=="total"){
    $_SESSION["no_requeridas"]=0;
    $_SESSION["lookahead"]=0;
    $_SESSION["no_iniciadas"]=0;
    $_SESSION["a_tiempo"]=0;
    $_SESSION["atrasadas"]=0;
    $_SESSION["terminadas"]=0;
    $_SESSION["total"]=1;
}else{
    $_SESSION["total"]=0;
}
if($clase=="no_requeridas"){
    if($activa==1){
        $_SESSION["no_requeridas"]=1;
    }else{
        $_SESSION["no_requeridas"]=0;
    }
}else if($clase=="lookahead"){
    if($activa==1){
        $_SESSION["lookahead"]=1;
    }else{
        $_SESSION["lookahead"]=0;
    }
}else if($clase=="no_iniciadas"){
    if($activa==1){
        $_SESSION["no_iniciadas"]=1;
    }else{
        $_SESSION["no_iniciadas"]=0;
    }
}else if($clase=="a_tiempo"){
    if($activa==1){
        $_SESSION["a_tiempo"]=1;
    }else{
        $_SESSION["a_tiempo"]=0;
    }
}else if($clase=="atrasadas"){
    if($activa==1){
        $_SESSION["atrasadas"]=1;
    }else{
        $_SESSION["atrasadas"]=0;
    }
}else if($clase=="terminadas"){
    if($activa==1){
        $_SESSION["terminadas"]=1;
    }else{
        $_SESSION["terminadas"]=0;
    }
}

header("Location: programa_general.php");

?>
