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
}



function modificar_mdo($db, $Id, $semana, $mdo_cal_1, $mdo_cal_2, $mdo_cal_3, $mdo_adm_1, $mdo_adm_2, $mdo_adm_3, $mdo_adm_4, $mdo_adm_5, $mdo_gsa_1, $mdo_gsa_2, $mdo_gsa_3, $mdo_gsa_4, $mdo_gsa_5, $mdo_gsa_6, $mdo_gsa_7, $mdo_gsa_8, $mdo_sst_1, $mdo_sst_2, $mdo_sst_3, $mdo_sst_4, $mdo_sst_5, $mdo_sst_6, $mdo_sst_7, $mdo_sst_8, $mdo_sst_9, $mdo_sst_10, $mdo_Observaciones, $conexion){

    $query= "UPDATE $db"."_cic SET mdo_cal_1='$mdo_cal_1', mdo_cal_2='$mdo_cal_2', mdo_cal_3='$mdo_cal_3', mdo_adm_1='$mdo_adm_1', mdo_adm_2='$mdo_adm_2', mdo_adm_3='$mdo_adm_3', mdo_adm_4='$mdo_adm_4', mdo_adm_5='$mdo_adm_5', mdo_gsa_1='$mdo_gsa_1', mdo_gsa_2='$mdo_gsa_2', mdo_gsa_3='$mdo_gsa_3', mdo_gsa_4='$mdo_gsa_4', mdo_gsa_5='$mdo_gsa_5', mdo_gsa_6='$mdo_gsa_6', mdo_gsa_7='$mdo_gsa_7', mdo_gsa_8='$mdo_gsa_8', mdo_sst_1='$mdo_sst_1', mdo_sst_2='$mdo_sst_2', mdo_sst_3='$mdo_sst_3', mdo_sst_4='$mdo_sst_4', mdo_sst_5='$mdo_sst_5', mdo_sst_6='$mdo_sst_6', mdo_sst_7='$mdo_sst_7', mdo_sst_8='$mdo_sst_8', mdo_sst_9='$mdo_sst_9', mdo_sst_10='$mdo_sst_10', Observaciones='$mdo_Observaciones' WHERE Id=$Id;";

    //echo $query ."<br>";

    $resultado= mysqli_query($conexion, $query);

    $conteo_cal= 0 + conteo($mdo_cal_1) + conteo($mdo_cal_2) + conteo($mdo_cal_3);

    $conteo_adm= 0 + conteo($mdo_adm_1) + conteo($mdo_adm_2) + conteo($mdo_adm_3) + conteo($mdo_adm_4) + conteo($mdo_adm_5);

    $conteo_gsa= 0 + conteo($mdo_gsa_1) + conteo($mdo_gsa_2) + conteo($mdo_gsa_3) + conteo($mdo_gsa_4) + conteo($mdo_gsa_5) + conteo($mdo_gsa_6) + conteo($mdo_gsa_7) + conteo($mdo_gsa_8);

    $conteo_sst= 0 + conteo($mdo_sst_1) + conteo($mdo_sst_2) + conteo($mdo_sst_3) + conteo($mdo_sst_4) + conteo($mdo_sst_5) + conteo($mdo_sst_6) + conteo($mdo_sst_7) + conteo($mdo_sst_8) + conteo($mdo_sst_9) + conteo($mdo_sst_10);

    $conteo_cal_nr= 0 + conteoNR($mdo_cal_1) + conteoNR($mdo_cal_2) + conteoNR($mdo_cal_3);

    $conteo_adm_nr= 0 + conteoNR($mdo_adm_1) + conteoNR($mdo_adm_2) + conteoNR($mdo_adm_3) + conteoNR($mdo_adm_4) + conteoNR($mdo_adm_5);

    $conteo_gsa_nr= 0 + conteoNR($mdo_gsa_1) + conteoNR($mdo_gsa_2) + conteoNR($mdo_gsa_3) + conteoNR($mdo_gsa_4) + conteoNR($mdo_gsa_5) + conteoNR($mdo_gsa_6) + conteoNR($mdo_gsa_7) + conteoNR($mdo_gsa_8);

    $conteo_sst_nr= 0 + conteoNR($mdo_sst_1) + conteoNR($mdo_sst_2) + conteoNR($mdo_sst_3) + conteoNR($mdo_sst_4) + conteoNR($mdo_sst_5) + conteoNR($mdo_sst_6) + conteoNR($mdo_sst_7) + conteoNR($mdo_sst_8) + conteoNR($mdo_sst_9) + conteoNR($mdo_sst_10);

    $mdo_cal_1 = normalizarCal($mdo_cal_1);
    $mdo_cal_2 = normalizarCal($mdo_cal_2);
    $mdo_cal_3 = normalizarCal($mdo_cal_3);

    $mdo_adm_1 = normalizarCal($mdo_adm_1);
    $mdo_adm_2 = normalizarCal($mdo_adm_2);
    $mdo_adm_3 = normalizarCal($mdo_adm_3);
    $mdo_adm_4 = normalizarCal($mdo_adm_4);
    $mdo_adm_5 = normalizarCal($mdo_adm_5);

    $mdo_gsa_1 = normalizarCal($mdo_gsa_1);
    $mdo_gsa_2 = normalizarCal($mdo_gsa_2);
    $mdo_gsa_3 = normalizarCal($mdo_gsa_3);
    $mdo_gsa_4 = normalizarCal($mdo_gsa_4);
    $mdo_gsa_5 = normalizarCal($mdo_gsa_5);
    $mdo_gsa_6 = normalizarCal($mdo_gsa_6);
    $mdo_gsa_7 = normalizarCal($mdo_gsa_7);
    $mdo_gsa_8 = normalizarCal($mdo_gsa_8);

    $mdo_sst_1 = normalizarCal($mdo_sst_1);
    $mdo_sst_2 = normalizarCal($mdo_sst_2);
    $mdo_sst_3 = normalizarCal($mdo_sst_3);
    $mdo_sst_4 = normalizarCal($mdo_sst_4);
    $mdo_sst_5 = normalizarCal($mdo_sst_5);
    $mdo_sst_6 = normalizarCal($mdo_sst_6);
    $mdo_sst_7 = normalizarCal($mdo_sst_7);
    $mdo_sst_8 = normalizarCal($mdo_sst_8);
    $mdo_sst_9 = normalizarCal($mdo_sst_9);
    $mdo_sst_10 = normalizarCal($mdo_sst_10);

    //echo $conteo_cal ."<br>". $conteo_adm ."<br>". $conteo_gsa ."<br>". $conteo_sst ."<br>";

    if($conteo_cal==0){
        if($conteo_cal_nr == 3){
          $calidad='NR';
        }else{
          $calidad='NA';
        }
    }else{
        $calidad=round(($mdo_cal_1+$mdo_cal_2+$mdo_cal_3)/$conteo_cal,3);
    }

    if($conteo_adm==0){
      if($conteo_adm_nr == 5){
        $adm='NR';
      }else{
        $adm='NA';
      }
    }else{
        $adm=round(($mdo_adm_1+$mdo_adm_2+$mdo_adm_3+$mdo_adm_4+$mdo_adm_5)/$conteo_adm,3);
    }

    if($conteo_gsa==0){
      if($conteo_gsa_nr == 8){
        $gsa='NR';
      }else{
        $gsa='NA';
      }
    }else{
        $gsa=round(($mdo_gsa_1+$mdo_gsa_2+$mdo_gsa_3+$mdo_gsa_4+$mdo_gsa_5+$mdo_gsa_6+$mdo_gsa_7+$mdo_gsa_8)/$conteo_gsa,3);
    }

    if($conteo_sst==0){
      if($conteo_sst_nr == 10){
        $sst='NR';
      }else{
        $sst='NA';
      }
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

    $query= "UPDATE $db"."_cic SET si_cal_1='$si_cal_1', si_cal_2='$si_cal_2', si_cal_3='$si_cal_3', si_adm_1='$si_adm_1', si_adm_2='$si_adm_2', si_adm_3='$si_adm_3', si_adm_4='$si_adm_4', si_adm_5='$si_adm_5', si_adm_6='$si_adm_6', si_gsa_1='$si_gsa_1', si_gsa_2='$si_gsa_2', si_gsa_3='$si_gsa_3', si_gsa_4='$si_gsa_4', si_gsa_5='$si_gsa_5', si_gsa_6='$si_gsa_6', si_gsa_7='$si_gsa_7', si_gsa_8='$si_gsa_8', si_gsa_9='$si_gsa_9', si_gsa_10='$si_gsa_10', si_gsa_11='$si_gsa_11', si_gsa_12='$si_gsa_12', si_gsa_13='$si_gsa_13', si_gsa_14='$si_gsa_14', si_sst_1='$si_sst_1', si_sst_2='$si_sst_2', si_sst_3='$si_sst_3', si_sst_4='$si_sst_4', si_sst_5='$si_sst_5', si_sst_6='$si_sst_6', si_sst_7='$si_sst_7', si_sst_8='$si_sst_8', si_sst_9='$si_sst_9', si_sst_10='$si_sst_10', Observaciones='$si_Observaciones' WHERE Id=$Id;";

    $resultado= mysqli_query($conexion, $query);

    /*if(!$resultado7){
        echo mysqli_error($conexion);
    }else{
        echo "OK";
    }*/

    $conteo_cal= 0 + conteo($si_cal_1) + conteo($si_cal_2) + conteo($si_cal_3);

    $conteo_adm= 0 + conteo($si_adm_1) + conteo($si_adm_2) + conteo($si_adm_3) + conteo($si_adm_4) + conteo($si_adm_5) + conteo($si_adm_6);

    $conteo_gsa= 0 + conteo($si_gsa_1) + conteo($si_gsa_2) + conteo($si_gsa_3) + conteo($si_gsa_4) + conteo($si_gsa_5) + conteo($si_gsa_6) + conteo($si_gsa_7) + conteo($si_gsa_8) + conteo($si_gsa_9) + conteo($si_gsa_10) + conteo($si_gsa_11) + conteo($si_gsa_12) + conteo($si_gsa_13) + conteo($si_gsa_14);

    $conteo_sst= 0 + conteo($si_sst_1) + conteo($si_sst_2) + conteo($si_sst_3) + conteo($si_sst_4) + conteo($si_sst_5) + conteo($si_sst_6) + conteo($si_sst_7) + conteo($si_sst_8) + conteo($si_sst_9) + conteo($si_sst_10);

    $conteo_cal_nr= 0 + conteoNR($si_cal_1) + conteoNR($si_cal_2) + conteoNR($si_cal_3);

    $conteo_adm_nr= 0 + conteoNR($si_adm_1) + conteoNR($si_adm_2) + conteoNR($si_adm_3) + conteoNR($si_adm_4) + conteoNR($si_adm_5) + conteoNR($si_adm_6);

    $conteo_gsa_nr= 0 + conteoNR($si_gsa_1) + conteoNR($si_gsa_2) + conteoNR($si_gsa_3) + conteoNR($si_gsa_4) + conteoNR($si_gsa_5) + conteoNR($si_gsa_6) + conteoNR($si_gsa_7) + conteoNR($si_gsa_8) + conteoNR($si_gsa_9) + conteoNR($si_gsa_10) + conteoNR($si_gsa_11) + conteoNR($si_gsa_12) + conteoNR($si_gsa_13) + conteoNR($si_gsa_14);

    $conteo_sst_nr= 0 + conteoNR($si_sst_1) + conteoNR($si_sst_2) + conteoNR($si_sst_3) + conteoNR($si_sst_4) + conteoNR($si_sst_5) + conteoNR($si_sst_6) + conteoNR($si_sst_7) + conteoNR($si_sst_8) + conteoNR($si_sst_9) + conteoNR($si_sst_10);

    $si_cal_1 = normalizarCal($si_cal_1);
    $si_cal_2 = normalizarCal($si_cal_2);
    $si_cal_3 = normalizarCal($si_cal_3);

    $si_adm_1 = normalizarCal($si_adm_1);
    $si_adm_2 = normalizarCal($si_adm_2);
    $si_adm_3 = normalizarCal($si_adm_3);
    $si_adm_4 = normalizarCal($si_adm_4);
    $si_adm_5 = normalizarCal($si_adm_5);
    $si_adm_6 = normalizarCal($si_adm_6);

    $si_gsa_1 = normalizarCal($si_gsa_1);
    $si_gsa_2 = normalizarCal($si_gsa_2);
    $si_gsa_3 = normalizarCal($si_gsa_3);
    $si_gsa_4 = normalizarCal($si_gsa_4);
    $si_gsa_5 = normalizarCal($si_gsa_5);
    $si_gsa_6 = normalizarCal($si_gsa_6);
    $si_gsa_7 = normalizarCal($si_gsa_7);
    $si_gsa_8 = normalizarCal($si_gsa_8);
    $si_gsa_9 = normalizarCal($si_gsa_9);
    $si_gsa_10 = normalizarCal($si_gsa_10);
    $si_gsa_11 = normalizarCal($si_gsa_11);
    $si_gsa_12 = normalizarCal($si_gsa_12);
    $si_gsa_13 = normalizarCal($si_gsa_13);
    $si_gsa_14 = normalizarCal($si_gsa_14);

    $si_sst_1 = normalizarCal($si_sst_1);
    $si_sst_2 = normalizarCal($si_sst_2);
    $si_sst_3 = normalizarCal($si_sst_3);
    $si_sst_4 = normalizarCal($si_sst_4);
    $si_sst_5 = normalizarCal($si_sst_5);
    $si_sst_6 = normalizarCal($si_sst_6);
    $si_sst_7 = normalizarCal($si_sst_7);
    $si_sst_8 = normalizarCal($si_sst_8);
    $si_sst_9 = normalizarCal($si_sst_9);
    $si_sst_10 = normalizarCal($si_sst_10);

    //echo $conteo_cal_nr ."<br>". $conteo_adm_nr ."<br>". $conteo_gsa_nr ."<br>". $conteo_sst_nr ."<br>";

    if($conteo_cal==0){
        if($conteo_cal_nr == 3){
          $calidad='NR';
        }else{
          $calidad='NA';
        }
    }else{
        $calidad=round(($si_cal_1+$si_cal_2+$si_cal_3)/$conteo_cal,3);
    }

    if($conteo_adm==0){
      if($conteo_adm_nr == 6){
        $adm='NR';
      }else{
        $adm='NA';
      }
    }else{
        $adm=round(($si_adm_1+$si_adm_2+$si_adm_3+$si_adm_4+$si_adm_5+$si_adm_6)/$conteo_adm,3);
    }

    if($conteo_gsa==0){
      if($conteo_gsa_nr == 14){
        $gsa='NR';
      }else{
        $gsa='NA';
      }
    }else{
        $gsa=round(($si_gsa_1+$si_gsa_2+$si_gsa_3+$si_gsa_4+$si_gsa_5+$si_gsa_6+$si_gsa_7+$si_gsa_8+$si_gsa_9+$si_gsa_10+$si_gsa_11+$si_gsa_12+$si_gsa_13+$si_gsa_14)/$conteo_gsa,3);
    }

    if($conteo_sst==0){
      if($conteo_sst_nr == 10){
        $sst='NR';
      }else{
        $sst='NA';
      }
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

function normalizarCal($calificacion){
  if($calificacion=='NA' || $calificacion=='NR'){
      $calificacion=0;
  }
  return $calificacion;
}

function conteo($calificacion){
  if($calificacion=='NA' || $calificacion=='NR'){
    $conteo = 0;
  }else{
    $conteo = 1;
  }
  return $conteo;
}

function conteoNR($calificacion){
  if($calificacion=='NR'){
    $conteoNR = 1;
  }else{
    $conteoNR = 0;
  }
  return $conteoNR;
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    //mysqli_close($conexion);
    //require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}


function eliminar_sem($semana, $db, $conexion){
    require("../funciones_generales/eliminar_semana.php");
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
        //mysqli_close($conexion);


}

function actualizar_integral($semana, $db, $conexion){
  //require("../conexion.php");
  $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semana;";
  $resultado5= mysqli_query($conexion, $query5);

  while ($cic = mysqli_fetch_assoc($resultado5)){
    $Id=$cic['Id'];
    $subcontratista=$cic['subcontratista'];

    $query6 ="SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA') END) AS 'PAC_Acum',";

    $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA') END) AS 'P_Completado_Acum',";

    $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA' AND Calidad!='NR') END) AS 'Calidad_Acum',";

    $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA' AND GSA!='NR') END) AS 'GSA_Acum',";

    $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA' AND SST!='NR') END) AS 'SST_Acum',";

    $query6 .= "(SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA' AND ADM!='NR') END) AS 'ADM_Acum'";

    $resultado6= mysqli_query($conexion, $query6);

    $data=mysqli_fetch_assoc($resultado6);
    $PAC_Acum=$data["PAC_Acum"];
    $P_Completado_Acum=$data["P_Completado_Acum"];
    $Calidad_Acum=$data["Calidad_Acum"];
    $GSA_Acum=$data["GSA_Acum"];
    $SST_Acum=$data["SST_Acum"];
    $ADM_Acum=$data["ADM_Acum"];



    $query6_1 = "UPDATE $db"."_cic SET
        PAC_Acum = '$PAC_Acum',

        P_Completado_Acum = '$P_Completado_Acum',

        Calidad_Acum = '$Calidad_Acum',

        GSA_Acum = '$GSA_Acum',

        SST_Acum = '$SST_Acum',

        ADM_Acum = '$ADM_Acum'

        WHERE Id=$Id";

    $resultado6_1= mysqli_query($conexion, $query6_1);


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

    if($PAC == ""){
      $cal_integral = "NULL";
    }else{
      if($calidad=='NA' || $calidad=='NR'){
          if($sst=='NA' || $sst=='NR'){
              if($gsa=='NA' || $gsa=='NR'){
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.7/7)*7);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
                  }
              }else{
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                  }
              }
          }else{
              if($gsa=='NA' || $gsa=='NR'){
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                  }
              }else{
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                  }
              }
          }
      }else{
          if($sst=='NA' || $sst=='NR'){
              if($gsa=='NA' || $gsa=='NR'){
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                  }
              }else{
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                  }
              }
          }else{
              if($gsa=='NA' || $gsa=='NR'){
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                  }
              }else{
                  if($adm=='NA' || $adm=='NR'){
                      $cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
                  }else{
                      $cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
                  }
              }
          }
      }
    }


    if($PAC_Acum == ""){
      $cal_integral_acum = "NULL";
    }else{
      if($calidad_acum=='NA' || $calidad_acum=='NR'){
          if($sst_acum=='NA' || $sst_acum=='NR'){
              if($gsa_acum=='NA' || $gsa_acum=='NR'){
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                  }
              }else{
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                  }
              }
          }else{
              if($gsa_acum=='NA' || $gsa_acum=='NR'){
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                  }
              }else{
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                  }
              }
          }
      }else{
          if($sst_acum=='NA' || $sst_acum=='NR'){
              if($gsa_acum=='NA' || $gsa_acum=='NR'){
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                  }
              }else{
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                  }
              }
          }else{
              if($gsa_acum=='NA' || $gsa_acum=='NR'){
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                  }
              }else{
                  if($adm_acum=='NA' || $adm_acum=='NR'){
                      $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                  }else{
                      $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                  }
              }
          }
      }
    }
    //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;
    $query7 = "UPDATE $db"."_cic SET Cal_Integral = $cal_integral, Cal_Integral_Acum = $cal_integral_acum WHERE Id=$Id;";
    // echo $query7;
    $resultado7= mysqli_query($conexion, $query7);
  }
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
