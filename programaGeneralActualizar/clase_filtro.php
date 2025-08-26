<?php session_start();
$clase=$_GET["clase"];
$activa=$_GET["activa"];

if($clase=="total"){
    $_SESSION["no_requeridas"]=0;
    $_SESSION["lookahead"]=0;
    $_SESSION["no_iniciadas"]=0;
    $_SESSION["en_ejecucion"]=0;
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
}else if($clase=="en_ejecucion"){
    if($activa==1){
        $_SESSION["en_ejecucion"]=1;
    }else{
        $_SESSION["en_ejecucion"]=0;
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
