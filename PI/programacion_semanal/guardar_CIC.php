<?php


require("../conexion.php");

$db=/*"clinica_del_sur"*/$_GET['db'];
$opcion=/*"modificar_mdo"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar_mdo") {
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    $mdo_cal_1=$_POST["mdo_cal_1"];
    $mdo_cal_2=$_POST["mdo_cal_2"];
    $mdo_cal_3=$_POST["mdo_cal_3"];
    $mdo_adm_1=$_POST["mdo_adm_1"];
    $mdo_adm_2=$_POST["mdo_adm_2"];
    $mdo_adm_3=$_POST["mdo_adm_3"];
    $mdo_adm_4=$_POST["mdo_adm_4"];
    $mdo_adm_5=$_POST["mdo_adm_5"];
    $mdo_gsa_1=$_POST["mdo_gsa_1"];
    $mdo_gsa_2=$_POST["mdo_gsa_2"];
    $mdo_gsa_3=$_POST["mdo_gsa_3"];
    $mdo_gsa_4=$_POST["mdo_gsa_4"];
    $mdo_gsa_5=$_POST["mdo_gsa_5"];
    $mdo_gsa_6=$_POST["mdo_gsa_6"];
    $mdo_gsa_7=$_POST["mdo_gsa_7"];
    $mdo_gsa_8=$_POST["mdo_gsa_8"];
    $mdo_sst_1=$_POST["mdo_sst_1"];
    $mdo_sst_2=$_POST["mdo_sst_2"];
    $mdo_sst_3=$_POST["mdo_sst_3"];
    $mdo_sst_4=$_POST["mdo_sst_4"];
    $mdo_sst_5=$_POST["mdo_sst_5"];
    $mdo_sst_6=$_POST["mdo_sst_6"];
    $mdo_sst_7=$_POST["mdo_sst_7"];
    $mdo_sst_8=$_POST["mdo_sst_8"];
    $mdo_sst_9=$_POST["mdo_sst_9"];
    $mdo_sst_10=$_POST["mdo_sst_10"];
    $mdo_Observaciones=$_POST["mdo_Observaciones"];
    
} else if ($opcion == "modificar_si") {
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    $si_cal_1=$_POST["si_cal_1"];
    $si_cal_2=$_POST["si_cal_2"];
    $si_cal_3=$_POST["si_cal_3"];
    $si_adm_1=$_POST["si_adm_1"];
    $si_adm_2=$_POST["si_adm_2"];
    $si_adm_3=$_POST["si_adm_3"];
    $si_adm_4=$_POST["si_adm_4"];
    $si_adm_5=$_POST["si_adm_5"];
    $si_adm_6=$_POST["si_adm_6"];
    $si_gsa_1=$_POST["si_gsa_1"];
    $si_gsa_2=$_POST["si_gsa_2"];
    $si_gsa_3=$_POST["si_gsa_3"];
    $si_gsa_4=$_POST["si_gsa_4"];
    $si_gsa_5=$_POST["si_gsa_5"];
    $si_gsa_6=$_POST["si_gsa_6"];
    $si_gsa_7=$_POST["si_gsa_7"];
    $si_gsa_8=$_POST["si_gsa_8"];
    $si_gsa_9=$_POST["si_gsa_9"];
    $si_gsa_10=$_POST["si_gsa_10"];
    $si_gsa_11=$_POST["si_gsa_11"];
    $si_gsa_12=$_POST["si_gsa_12"];
    $si_gsa_13=$_POST["si_gsa_13"];
    $si_gsa_14=$_POST["si_gsa_14"];
    $si_sst_1=$_POST["si_sst_1"];
    $si_sst_2=$_POST["si_sst_2"];
    $si_sst_3=$_POST["si_sst_3"];
    $si_sst_4=$_POST["si_sst_4"];
    $si_sst_5=$_POST["si_sst_5"];
    $si_sst_6=$_POST["si_sst_6"];
    $si_sst_7=$_POST["si_sst_7"];
    $si_sst_8=$_POST["si_sst_8"];
    $si_sst_9=$_POST["si_sst_9"];
    $si_sst_10=$_POST["si_sst_10"];
    $si_Observaciones=$_POST["si_Observaciones"];

} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
} else if($opcion=="CNC"){
    $categoria=$_POST["categoria"];
} else if($opcion=="generar"){
    $semana=$_POST["semana"];
}


/*$db="cross";
$opcion="modificar_si";
$informacion=[];*/

/*if ($opcion == "modificar_mdo") {
    $Id=34;
    $semana=1;
    $mdo_cal_1='NA';
    $mdo_cal_2='NA';
    $mdo_cal_3='NA';
    $mdo_adm_1=1;
    $mdo_adm_2=1;
    $mdo_adm_3=1;
    $mdo_adm_4=1;
    $mdo_adm_5=1;
    $mdo_gsa_1=0.5;
    $mdo_gsa_2=0.5;
    $mdo_gsa_3=0.5;
    $mdo_gsa_4=0.5;
    $mdo_gsa_5=0.5;
    $mdo_gsa_6=0.5;
    $mdo_gsa_7=0.5;
    $mdo_gsa_8=0.5;
    $mdo_sst_1=1;
    $mdo_sst_2=1;
    $mdo_sst_3=1;
    $mdo_sst_4=1;
    $mdo_sst_5=1;
    $mdo_sst_6=1;
    $mdo_sst_7=1;
    $mdo_sst_8=1;
    $mdo_sst_9=1;
    $mdo_sst_10=1;
    $mdo_Observaciones="";
    
} else if ($opcion == "modificar_si") {
    $Id=294;
    $semana=1;
    $si_cal_1=1;
    $si_cal_2=1;
    $si_cal_3=1;
    $si_adm_1=0.5;
    $si_adm_2=0.5;
    $si_adm_3=0.5;
    $si_adm_4=0.5;
    $si_adm_5=0.5;
    $si_adm_6=0.5;
    $si_gsa_1=0.5;
    $si_gsa_2=0.5;
    $si_gsa_3=0.5;
    $si_sst_1=0.5;
    $si_sst_2=0.5;
    $si_sst_3=0.5;
    $si_sst_4=0.5;
    $si_sst_5=0.5;
    $si_sst_6=0.5;
    $si_sst_7=0.5;
    $si_sst_8=0.5;
    $si_sst_9=0.5;
    $si_sst_10=0.5;
    $si_Observaciones="hola";

}else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="CNC"){
    $categoria=$_POST["categoria"];
} else if($opcion=="generar"){
    $semana=2;
}*/




switch($opcion){
    case 'modificar_mdo':
        modificar_mdo($db, $Id, $semana, $mdo_cal_1, $mdo_cal_2, $mdo_cal_3, $mdo_adm_1, $mdo_adm_2, $mdo_adm_3, $mdo_adm_4, $mdo_adm_5, $mdo_gsa_1, $mdo_gsa_2, $mdo_gsa_3, $mdo_gsa_4, $mdo_gsa_5, $mdo_gsa_6, $mdo_gsa_7, $mdo_gsa_8, $mdo_sst_1, $mdo_sst_2, $mdo_sst_3, $mdo_sst_4, $mdo_sst_5, $mdo_sst_6, $mdo_sst_7, $mdo_sst_8, $mdo_sst_9, $mdo_sst_10, $mdo_Observaciones, $conexion);
        break;
    
    case 'modificar_si':
        modificar_si($db, $Id, $semana, $si_cal_1, $si_cal_2, $si_cal_3, $si_adm_1, $si_adm_2, $si_adm_3, $si_adm_4, $si_adm_5, $si_adm_6, $si_gsa_1, $si_gsa_2, $si_gsa_3, $si_gsa_4, $si_gsa_5, $si_gsa_6, $si_gsa_7, $si_gsa_8, $si_gsa_9, $si_gsa_10, $si_gsa_11, $si_gsa_12, $si_gsa_13, $si_gsa_14, $si_sst_1, $si_sst_2, $si_sst_3, $si_sst_4, $si_sst_5, $si_sst_6, $si_sst_7, $si_sst_8, $si_sst_9, $si_sst_10, $si_Observaciones, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
    
    case 'CNC':
        CNC($categoria, $db, $conexion);
        break; 
        
    case 'generar':
        generar($semana, $db, $conexion);
        
}



function modificar_mdo($db, $Id, $semana, $mdo_cal_1, $mdo_cal_2, $mdo_cal_3, $mdo_adm_1, $mdo_adm_2, $mdo_adm_3, $mdo_adm_4, $mdo_adm_5, $mdo_gsa_1, $mdo_gsa_2, $mdo_gsa_3, $mdo_gsa_4, $mdo_gsa_5, $mdo_gsa_6, $mdo_gsa_7, $mdo_gsa_8, $mdo_sst_1, $mdo_sst_2, $mdo_sst_3, $mdo_sst_4, $mdo_sst_5, $mdo_sst_6, $mdo_sst_7, $mdo_sst_8, $mdo_sst_9, $mdo_sst_10, $mdo_Observaciones, $conexion){
    
    $query= "UPDATE $db"."_cic SET mdo_cal_1='$mdo_cal_1', mdo_cal_2='$mdo_cal_2', mdo_cal_3='$mdo_cal_3', mdo_adm_1='$mdo_adm_1', mdo_adm_2='$mdo_adm_2', mdo_adm_3='$mdo_adm_3', mdo_adm_4='$mdo_adm_4', mdo_adm_5='$mdo_adm_5', mdo_gsa_1='$mdo_gsa_1', mdo_gsa_2='$mdo_gsa_2', mdo_gsa_3='$mdo_gsa_3', mdo_gsa_4='$mdo_gsa_4', mdo_gsa_5='$mdo_gsa_5', mdo_gsa_6='$mdo_gsa_6', mdo_gsa_7='$mdo_gsa_7', mdo_gsa_8='$mdo_gsa_8', mdo_sst_1='$mdo_sst_1', mdo_sst_2='$mdo_sst_2', mdo_sst_3='$mdo_sst_3', mdo_sst_4='$mdo_sst_4', mdo_sst_5='$mdo_sst_5', mdo_sst_6='$mdo_sst_6', mdo_sst_7='$mdo_sst_7', mdo_sst_8='$mdo_sst_8', mdo_sst_9='$mdo_sst_9', mdo_sst_10='$mdo_sst_10', Observaciones='$mdo_Observaciones' WHERE (Semana=$semana AND Id=$Id);";
    
    //echo $query ."<br>";

    $resultado= mysqli_query($conexion, $query);
    $conteo_cal=0;
    $conteo_adm=0;
    $conteo_gsa=0;
    $conteo_sst=0;

    if($mdo_cal_1=='NA'){
        $mdo_cal_1=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($mdo_cal_2=='NA'){
        $mdo_cal_2=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($mdo_cal_3=='NA'){
        $mdo_cal_3=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($mdo_adm_1=='NA'){
        $mdo_adm_1=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($mdo_adm_2=='NA'){
        $mdo_adm_2=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($mdo_adm_3=='NA'){
        $mdo_adm_3=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($mdo_adm_4=='NA'){
        
        $mdo_adm_4=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($mdo_adm_5=='NA'){
        
        $mdo_adm_5=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($mdo_gsa_1=='NA'){
        
        $mdo_gsa_1=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_2=='NA'){
        
        $mdo_gsa_2=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_3=='NA'){
        
        $mdo_gsa_3=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_4=='NA'){
        
        $mdo_gsa_4=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_5=='NA'){
        
        $mdo_gsa_5=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_6=='NA'){
        
        $mdo_gsa_6=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_7=='NA'){
        
        $mdo_gsa_7=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_gsa_8=='NA'){
        
        $mdo_gsa_8=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($mdo_sst_1=='NA'){
        
        $mdo_sst_1=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_2=='NA'){
        
        $mdo_sst_2=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_3=='NA'){
        
        $mdo_sst_3=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_4=='NA'){
        
        $mdo_sst_4=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_5=='NA'){
        
        $mdo_sst_5=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_6=='NA'){
        
        $mdo_sst_6=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_7=='NA'){
        
        $mdo_sst_7=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_8=='NA'){
        
        $mdo_sst_8=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_9=='NA'){
        
        $mdo_sst_9=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($mdo_sst_10=='NA'){
        
        $mdo_sst_10=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    
    //echo $conteo_cal ."<br>". $conteo_adm ."<br>". $conteo_gsa ."<br>". $conteo_sst ."<br>";
    
    if($conteo_cal==0){
        $calidad='NA';
    }else{
        $calidad=round(($mdo_cal_1+$mdo_cal_2+$mdo_cal_3)/$conteo_cal,3);
    }
    
    if($conteo_adm==0){
        $adm='NA';
    }else{
        $adm=round(($mdo_adm_1+$mdo_adm_2+$mdo_adm_3+$mdo_adm_4+$mdo_adm_5)/$conteo_adm,3);
    }
    
    if($conteo_gsa==0){
        $gsa='NA';
    }else{
        $gsa=round(($mdo_gsa_1+$mdo_gsa_2+$mdo_gsa_3+$mdo_gsa_4+$mdo_gsa_5+$mdo_gsa_6+$mdo_gsa_7+$mdo_gsa_8)/$conteo_gsa,3);
    }
    
    if($conteo_sst==0){
        $sst='NA';
    }else{
        $sst=round(($mdo_sst_1+$mdo_sst_2+$mdo_sst_3+$mdo_sst_4+$mdo_sst_5+$mdo_sst_6+$mdo_sst_7+$mdo_sst_8+$mdo_sst_9+$mdo_sst_10)/$conteo_sst,3);
    }
    
    //echo $calidad ."<br>". $adm ."<br>". $gsa ."<br>". $sst ."<br>";
    
    
    
    $query2="UPDATE $db"."_cic SET Calidad='$calidad', GSA='$gsa', SST='$sst', ADM='$adm' WHERE (Semana=$semana AND Id=$Id);";
    //echo $query2;
    
    $resultado4= mysqli_query($conexion, $query2);
    
    verificar_resultado("OK");
    actualizar_PAC($semana, $db, $conexion); 
    actualizar_integral($semana, $db, $conexion);
}

function modificar_si($db, $Id, $semana, $si_cal_1, $si_cal_2, $si_cal_3, $si_adm_1, $si_adm_2, $si_adm_3, $si_adm_4, $si_adm_5, $si_adm_6, $si_gsa_1, $si_gsa_2, $si_gsa_3, $si_gsa_4, $si_gsa_5, $si_gsa_6, $si_gsa_7, $si_gsa_8, $si_gsa_9, $si_gsa_10, $si_gsa_11, $si_gsa_12, $si_gsa_13, $si_gsa_14, $si_sst_1, $si_sst_2, $si_sst_3, $si_sst_4, $si_sst_5, $si_sst_6, $si_sst_7, $si_sst_8, $si_sst_9, $si_sst_10, $si_Observaciones, $conexion){
    
    $query= "UPDATE $db"."_cic SET si_cal_1='$si_cal_1', si_cal_2=$si_cal_2, si_cal_3=$si_cal_3, si_adm_1=$si_adm_1, si_adm_2=$si_adm_2, si_adm_3=$si_adm_3, si_adm_4=$si_adm_4, si_adm_5=$si_adm_5, si_adm_6=$si_adm_6, si_gsa_1=$si_gsa_1, si_gsa_2=$si_gsa_2, si_gsa_3=$si_gsa_3, si_gsa_4=$si_gsa_4, si_gsa_5=$si_gsa_5, si_gsa_6=$si_gsa_6, si_gsa_7=$si_gsa_7, si_gsa_8=$si_gsa_8, si_gsa_9=$si_gsa_9, si_gsa_10=$si_gsa_10, si_gsa_11=$si_gsa_11, si_gsa_12=$si_gsa_12, si_gsa_13=$si_gsa_13, si_gsa_14=$si_gsa_14, si_sst_1=$si_sst_1, si_sst_2=$si_sst_2, si_sst_3=$si_sst_3, si_sst_4=$si_sst_4, si_sst_5=$si_sst_5, si_sst_6=$si_sst_6, si_sst_7=$si_sst_7, si_sst_8=$si_sst_8, si_sst_9=$si_sst_9, si_sst_10=$si_sst_10 , si_sst_8=$si_sst_8, si_sst_9=$si_sst_9, si_sst_10=$si_sst_10, Observaciones='$si_Observaciones' WHERE (Semana=$semana AND Id=$Id);";

    $resultado= mysqli_query($conexion, $query);
    $conteo_cal=0;
    $conteo_adm=0;
    $conteo_gsa=0;
    $conteo_sst=0;

    if($si_cal_1=='NA'){
        $si_cal_1=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($si_cal_2=='NA'){
        $si_cal_2=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($si_cal_3=='NA'){
        $si_cal_3=0;
    }else{
        $conteo_cal=$conteo_cal+1;
    }
    if($si_adm_1=='NA'){
        $si_adm_1=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($si_adm_2=='NA'){
        $si_adm_2=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($si_adm_3=='NA'){
        $si_adm_3=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($si_adm_4=='NA'){
        
        $si_adm_4=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($si_adm_5=='NA'){
        
        $si_adm_5=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    
    if($si_adm_6=='NA'){
        
        $si_adm_6=0;
    }else{
        $conteo_adm=$conteo_adm+1;
    }
    if($si_gsa_1=='NA'){
        
        $si_gsa_1=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_2=='NA'){
        
        $si_gsa_2=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_3=='NA'){
        
        $si_gsa_3=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_4=='NA'){
        
        $si_gsa_4=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_5=='NA'){
        
        $si_gsa_5=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_6=='NA'){
        
        $si_gsa_6=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_7=='NA'){
        
        $si_gsa_7=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_8=='NA'){
        
        $si_gsa_8=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_9=='NA'){
        
        $si_gsa_9=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_10=='NA'){
        
        $si_gsa_10=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_11=='NA'){
        
        $si_gsa_11=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_12=='NA'){
        
        $si_gsa_12=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_14=='NA'){
        
        $si_gsa_14=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_gsa_14=='NA'){
        
        $si_gsa_14=0;
    }else{
        $conteo_gsa=$conteo_gsa+1;
    }
    if($si_sst_1=='NA'){
        
        $si_sst_1=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_2=='NA'){
        
        $si_sst_2=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_3=='NA'){
        
        $si_sst_3=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_4=='NA'){
        
        $si_sst_4=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_5=='NA'){
        
        $si_sst_5=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_6=='NA'){
        
        $si_sst_6=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_7=='NA'){
        
        $si_sst_7=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_8=='NA'){
        
        $si_sst_8=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_9=='NA'){
        
        $si_sst_9=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    if($si_sst_10=='NA'){
        
        $si_sst_10=0;
    }else{
        $conteo_sst=$conteo_sst+1;
    }
    
    //echo $conteo_cal ."<br>". $conteo_adm ."<br>". $conteo_gsa ."<br>". $conteo_sst ."<br>";
    
    if($conteo_cal==0){
        $calidad='NA';
    }else{
        $calidad=round(($si_cal_1+$si_cal_2+$si_cal_3)/$conteo_cal,3);
    }
    
    if($conteo_adm==0){
        $adm='NA';
    }else{
        $adm=round(($si_adm_1+$si_adm_2+$si_adm_3+$si_adm_4+$si_adm_5+$si_adm_6)/$conteo_adm,3);
    }
    
    if($conteo_gsa==0){
        $gsa='NA';
    }else{
        $gsa=round(($si_gsa_1+$si_gsa_2+$si_gsa_3+$si_gsa_4+$si_gsa_5+$si_gsa_6+$si_gsa_7+$si_gsa_8+$si_gsa_9+$si_gsa_10+$si_gsa_11+$si_gsa_12+$si_gsa_13+$si_gsa_14)/$conteo_gsa,3);
    }
    
    if($conteo_sst==0){
        $sst='NA';
    }else{
        $sst=round(($si_sst_1+$si_sst_2+$si_sst_3+$si_sst_4+$si_sst_5+$si_sst_6+$si_sst_7+$si_sst_8+$si_sst_9+$si_sst_10)/$conteo_sst,3);
    }
    
    //echo $calidad ."<br>". $adm ."<br>". $gsa ."<br>". $sst ."<br>";
    
    
    
    $query2="UPDATE $db"."_cic SET Calidad='$calidad', GSA='$gsa', SST='$sst', ADM='$adm' WHERE (Semana=$semana AND Id=$Id);";
    
    $resultado4= mysqli_query($conexion, $query2);
    
    verificar_resultado("OK");
    actualizar_PAC($semana, $db, $conexion); 
    actualizar_integral($semana, $db, $conexion);
}


function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function activar_checklists($semana, $db, $conexion){
    $query = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND (Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas')";
    require("../conexion.php");
    $resultado=mysqli_query($conexion, $query);
    
    while($data=mysqli_fetch_assoc($resultado)){
        $consecutivo = $data["Consecutivo_en_Programa"];
        $checklist = $data["Checklist"];
        
        if($checklist==NULL){
        }else{
            require("../conexion.php");
            $query1 = "SELECT MAX(Consecutivo_Requerimiento) FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
            $resultado1=mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $requerimientos=$data1["MAX(Consecutivo_Requerimiento)"];

            $query2 = "SELECT ";
            for($i=1; $i<=$requerimientos; $i++){

                require("../conexion.php");
                $query2 .= "(SELECT CASE WHEN R$i = 'NA' THEN 0 ELSE R$i END) AS 'valor$i'";
                if($i<$requerimientos){
                    $query2 .=", ";
                }
            }
            $query2 .= " FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa = $consecutivo";

            $resultado2=mysqli_query($conexion, $query2);
            $data2=mysqli_fetch_assoc($resultado2);

            $query3 = "UPDATE $db"."_programa_consolidado SET ";
            for($i=1; $i<=$requerimientos; $i++){
                $valor = $data2["valor$i"];

                require("../conexion.php");
                $query3 .= "R$i = $valor, ";
            }
            $query3 .="Estado_Restricciones=0 WHERE Consecutivo_en_Programa = $consecutivo"; 
            $resultado3=mysqli_query($conexion, $query3);
        }
    }
    $query4 = "UPDATE $db"."_programa_consolidado SET Estado_Restricciones=1 WHERE Categoria = 'periodicas_simples' OR Categoria = 'propias' OR ((Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas') AND Checklist='')";
    $resultado4=mysqli_query($conexion, $query4);
}

function eliminar_sem($semana, $db, $conexion){    
    require("../funciones_generales/eliminar_semana.php");
}

function CNC($categoria, $db, $conexion){
        $query="SELECT * FROM general_cnc WHERE Categoria_CNC='$categoria'";
        $resultado= mysqli_query($conexion, $query);
        $cadena="<option value=''></option>";
        while ($valores = mysqli_fetch_array($resultado)){
            $valores=$valores['CNC'];
            $cadena.= "<option value='$valores'>$valores</option>";
        };
        echo $cadena;
}
    
function generar($semana, $db, $conexion){
    $query="SELECT  COUNT(*) FROM $db"."_cic WHERE Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    //echo $conteo;
    if ($conteo==0){
        $query1="INSERT INTO $db"."_cic (subcontratista) SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista !='' AND Activa=1";
        $resultado1= mysqli_query($conexion, $query1);    
    }
    verificar_resultado($resultado);
    actualizar_PAC($semana, $db, $conexion);
    actualizar_integral($semana, $db, $conexion);
            
}

function actualizar_PAC($semana, $db, $conexion){
    $query5 ="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista !=''";
        $resultado5= mysqli_query($conexion, $query5);
        $query6 ="";
        while($data=mysqli_fetch_assoc($resultado5)){
            $subcontratista = $data['Sub_Contratista'];
            $query6 ="UPDATE $db"."_cic INNER JOIN $db"."_subcontratistas ON $db"."_cic . subcontratista = $db"."_subcontratistas . subcontratista SET 
                $db"."_cic . P_Completado = (SELECT ROUND(SUM(P_Completado)/COUNT(P_Completado),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND Activa=1), 
                
                $db"."_cic . PAC = (SELECT ROUND(SUM(PAC)/COUNT(PAC),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND Activa=1), 
                
                $db"."_cic . Semana = $semana, $db"."_cic . correo_contacto = $db"."_subcontratistas . correo_contacto, $db"."_cic . NIT = $db"."_subcontratistas . NIT, $db"."_cic . alcance = $db"."_subcontratistas . alcance, $db"."_cic . tipo_proveedor = $db"."_subcontratistas . tipo_proveedor WHERE $db"."_cic . subcontratista = '$subcontratista'  AND Semana=0;";
                
            $resultado6= mysqli_query($conexion, $query6);
            //echo $query3 ."<br>" . $query4;
        }
        
        mysqli_free_result($resultado5);
        mysqli_close($conexion);
    
        
}

function actualizar_integral($semana, $db, $conexion){
    require("../conexion.php");
    $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semana;";
    $resultado5= mysqli_query($conexion, $query5);

    while ($cic = mysqli_fetch_assoc($resultado5)){
        $Id=$cic['Id'];
        $subcontratista=$cic['subcontratista'];
        $query6 = "UPDATE $db"."_cic SET 
            PAC_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA') END), 

            P_Completado_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA') END), 

            Calidad_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA') END),

            GSA_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA') END),

            SST_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA') END),

            ADM_Acum = (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA') END)

            WHERE Id=$Id";
        //echo $query6;
        $resultado6= mysqli_query($conexion, $query6); 

        $query7 ="SELECT * FROM $db"."_cic WHERE Id=$Id;";
        $resultado7= mysqli_query($conexion, $query7);
        $cic1 = mysqli_fetch_assoc($resultado7);

        $PAC=$cic1['PAC'];
        $PAC_acum=$cic1['PAC_Acum'];
        $calidad=$cic1['Calidad'];
        $calidad_acum=$cic1['Calidad_Acum'];
        $adm=$cic1['ADM'];
        $adm_acum=$cic1['ADM_Acum'];
        $gsa=$cic1['GSA'];
        $gsa_acum=$cic1['GSA_Acum'];
        $sst=$cic1['SST'];
        $sst_acum=$cic1['SST_Acum'];

        if($calidad=='NA'){
            if($sst=='NA'){
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst=='NA'){
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa=='NA'){
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm=='NA'){
                        $cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
                    }
                }
            }
        }


        if($calidad_acum=='NA'){
            if($sst_acum=='NA'){
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }
        }else{
            if($sst_acum=='NA'){
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }
            }else{
                if($gsa_acum=='NA'){
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                    }
                }else{
                    if($adm_acum=='NA'){
                        $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                    }else{
                        $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                    }
                }
            }
        }

        //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;

        $query7 = "UPDATE $db"."_cic SET Cal_Integral = ROUND($cal_integral,3), Cal_Integral_Acum = ROUND($cal_integral_acum,3) WHERE Id=$Id;";
        //echo $query7;

        //echo $query7;
        $resultado7= mysqli_query($conexion, $query7); 


    };
        //echo $query8;  

        //$resultado4= mysqli_multi_query($conexion, $query4);
        mysqli_close($conexion);
        //mysqli_free_result($resultado);
}

function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);   
}

function cerrar($conexion){
    mysqli_close($conexion);
}





?>