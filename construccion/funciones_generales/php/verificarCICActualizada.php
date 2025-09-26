<?php
require("../../conexion.php");

$db = $_POST["db"];
$semana = $_POST["semana"];
// $db = "laMasia";
// $semana = 19;
if($db == 'cedi_pasto'){
  $faltaCalificar = 0;
}else{
  $faltaCalificar = listar($db, $semana, $conexion);
}
echo json_encode($faltaCalificar);



function listar($db, $semana, $conexion){
    //require ("../conexion.php");
    $query = "SELECT COUNT(*) FROM $db"."_cic WHERE (Semana<=$semana)AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' ";
    $resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo==0){
        $faltaCalificar = '';
        return $faltaCalificar;
    }else{
        $query1 = "SELECT DISTINCT(subcontratista) FROM $db"."_cic WHERE (Semana<=$semana) AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' ORDER BY `Semana` DESC, `subcontratista` ASC";
        $resultado1 = mysqli_query($conexion, $query1);
        if(!$resultado1){
            die("Error");
        }else{
          $query2 = "SELECT COUNT(*), GROUP_CONCAT(subcontratista SEPARATOR ', ') AS faltaCalificar FROM";
          $query2 .= " (";
          while($data1=mysqli_fetch_assoc($resultado1)){
            $subcontratista = $data1["subcontratista"];
            $query2 .= "SELECT `Id`, `Semana`, (SELECT COUNT(*) FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos')  AS `semanasEnProyecto`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana = (SELECT MAX(`Semana`) FROM $db"."_cic WHERE `subcontratista` = '$subcontratista' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos') AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' UNION ";
          }
          $query2 = substr($query2, 0, -7);
          $query2 .= ") AS tabla WHERE MOD(tabla.`semanasEnProyecto`, 8) = 0 AND (tabla.`Calidad`='NR' OR tabla.`GSA`='NR' OR tabla.`SST`='NR' OR tabla.`ADM`='NR')  ORDER BY `Semana` DESC, `subcontratista` ASC";
          $resultado2 = mysqli_query($conexion, $query2);

          if(!$resultado2){
              die("Error");
          }else{
            $dataFaltaCalificar=mysqli_fetch_assoc($resultado2);
            $conteoFaltaCalificar = $dataFaltaCalificar["COUNT(*)"];
            if($conteoFaltaCalificar > 1){
              return " de los Subcontratistas " . $dataFaltaCalificar["faltaCalificar"];
            }else if($conteoFaltaCalificar == 1){
              return " del Subcontratista " . $dataFaltaCalificar["faltaCalificar"];
            }else{
              return 0;
            }

          }
        }
    }
    mysqli_close($conexion);
}
 ?>
