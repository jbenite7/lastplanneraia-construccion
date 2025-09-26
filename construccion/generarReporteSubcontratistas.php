<?php session_start();
require ("conexion.php");


$query="TRUNCATE TABLE general_informe_subcontratistas";
//echo "$query <br>" ;

$resultado= mysqli_query($conexion, $query);

$query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Proyecto_Proceso!='Prueba' AND Activo=1";
//echo "$query1 <br>" ;

$resultado1= mysqli_query($conexion, $query1);
$query2="INSERT INTO general_informe_subcontratistas (`Proyecto`, `Semana`, `maxSemana`, `Proyecto_maxSemana`,	 `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`)";
while ($data1=mysqli_fetch_assoc($resultado1)){
    $Proyecto=$data1["Proyecto_Proceso"];
    //echo "<li> $Proyecto";
    $Base_de_Datos=$data1["Base_de_Datos"];

    // $query2 .= " SELECT '$Proyecto', `Semana`, `Actividad`, NULL, NULL, `Critica`, `Atrasada`, `Activa`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `PAC`, `P_Completado`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Responsable_AIA`, `Sub_Contratista` FROM $Base_de_Datos"."_programacion_semanal WHERE Semana<=((SELECT MAX(Semana) FROM $Base_de_Datos"."_programacion_semanal)) UNION ";

    $query2 .= " SELECT '$Proyecto',
    $Base_de_Datos"."_cic.`Semana`,
    (SELECT MAX($Base_de_Datos"."_cic.`Semana`) FROM $Base_de_Datos"."_cic),
    CONCAT('$Proyecto (', (SELECT `Fecha_Fin_Sem` FROM $Base_de_Datos"."_semanas_activas WHERE Semana = (SELECT MAX($Base_de_Datos"."_cic.`Semana`) FROM $Base_de_Datos"."_cic)),')'),
    $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`,
    $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`,
    $Base_de_Datos"."_cic.`subcontratista`,
    $Base_de_Datos"."_cic.`correo_contacto`,
    $Base_de_Datos"."_cic.`NIT`,
    $Base_de_Datos"."_cic.`alcance`,
    $Base_de_Datos"."_cic.`tipo_proveedor`,
    $Base_de_Datos"."_cic.`PAC`,
    $Base_de_Datos"."_cic.`PAC_Acum`,
    $Base_de_Datos"."_cic.`P_Completado`,
    $Base_de_Datos"."_cic.`P_Completado_Acum`,
    $Base_de_Datos"."_cic.`Calidad`,
    $Base_de_Datos"."_cic.`Calidad_Acum`,
    $Base_de_Datos"."_cic.`GSA`,
    $Base_de_Datos"."_cic.`GSA_Acum`,
    $Base_de_Datos"."_cic.`SST`,
    $Base_de_Datos"."_cic.`SST_Acum`,
    $Base_de_Datos"."_cic.`ADM`,
    $Base_de_Datos"."_cic.`ADM_Acum`,
    $Base_de_Datos"."_cic.`Cal_Integral`,
    $Base_de_Datos"."_cic.`Cal_Integral_Acum`,
    $Base_de_Datos"."_cic.`Observaciones`,
    $Base_de_Datos"."_cic.`mdo_cal_1`,
    $Base_de_Datos"."_cic.`mdo_cal_2`,
    $Base_de_Datos"."_cic.`mdo_cal_3`,
    $Base_de_Datos"."_cic.`mdo_adm_1`,
    $Base_de_Datos"."_cic.`mdo_adm_2`,
    $Base_de_Datos"."_cic.`mdo_adm_3`,
    $Base_de_Datos"."_cic.`mdo_adm_4`,
    $Base_de_Datos"."_cic.`mdo_adm_5`,
    $Base_de_Datos"."_cic.`mdo_gsa_1`,
    $Base_de_Datos"."_cic.`mdo_gsa_2`,
    $Base_de_Datos"."_cic.`mdo_gsa_3`,
    $Base_de_Datos"."_cic.`mdo_gsa_4`,
    $Base_de_Datos"."_cic.`mdo_gsa_5`,
    $Base_de_Datos"."_cic.`mdo_gsa_6`,
    $Base_de_Datos"."_cic.`mdo_gsa_7`,
    $Base_de_Datos"."_cic.`mdo_gsa_8`,
    $Base_de_Datos"."_cic.`mdo_sst_1`,
    $Base_de_Datos"."_cic.`mdo_sst_2`,
    $Base_de_Datos"."_cic.`mdo_sst_3`,
    $Base_de_Datos"."_cic.`mdo_sst_4`,
    $Base_de_Datos"."_cic.`mdo_sst_5`,
    $Base_de_Datos"."_cic.`mdo_sst_6`,
    $Base_de_Datos"."_cic.`mdo_sst_7`,
    $Base_de_Datos"."_cic.`mdo_sst_8`,
    $Base_de_Datos"."_cic.`mdo_sst_9`,
    $Base_de_Datos"."_cic.`mdo_sst_10`,
    $Base_de_Datos"."_cic.`si_cal_1`,
    $Base_de_Datos"."_cic.`si_cal_2`,
    $Base_de_Datos"."_cic.`si_cal_3`,
    $Base_de_Datos"."_cic.`si_adm_1`,
    $Base_de_Datos"."_cic.`si_adm_2`,
    $Base_de_Datos"."_cic.`si_adm_3`,
    $Base_de_Datos"."_cic.`si_adm_4`,
    $Base_de_Datos"."_cic.`si_adm_5`,
    $Base_de_Datos"."_cic.`si_adm_6`,
    $Base_de_Datos"."_cic.`si_gsa_1`,
    $Base_de_Datos"."_cic.`si_gsa_2`,
    $Base_de_Datos"."_cic.`si_gsa_3`,
    $Base_de_Datos"."_cic.`si_gsa_4`,
    $Base_de_Datos"."_cic.`si_gsa_5`,
    $Base_de_Datos"."_cic.`si_gsa_6`,
    $Base_de_Datos"."_cic.`si_gsa_7`,
    $Base_de_Datos"."_cic.`si_gsa_8`,
    $Base_de_Datos"."_cic.`si_gsa_9`,
    $Base_de_Datos"."_cic.`si_gsa_10`,
    $Base_de_Datos"."_cic.`si_gsa_11`,
    $Base_de_Datos"."_cic.`si_gsa_12`,
    $Base_de_Datos"."_cic.`si_gsa_13`,
    $Base_de_Datos"."_cic.`si_gsa_14`,
    $Base_de_Datos"."_cic.`si_sst_1`,
    $Base_de_Datos"."_cic.`si_sst_2`,
    $Base_de_Datos"."_cic.`si_sst_3`,
    $Base_de_Datos"."_cic.`si_sst_4`,
    $Base_de_Datos"."_cic.`si_sst_5`,
    $Base_de_Datos"."_cic.`si_sst_6`,
    $Base_de_Datos"."_cic.`si_sst_7`,
    $Base_de_Datos"."_cic.`si_sst_8`,
    $Base_de_Datos"."_cic.`si_sst_9`,
    $Base_de_Datos"."_cic.`si_sst_10`

    FROM $Base_de_Datos"."_cic LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana`=$Base_de_Datos"."_cic.`Semana` UNION ";


}
$query2 = substr($query2, 0, -7);
//echo "<li> $query2";
$resultado2= mysqli_query($conexion, $query2);
if(!$resultado2){
    die(mysqli_error($conexion));
} else{
    echo "<li>Calificación Subcontratistas - OK";
}
    //mysqli_free_result($resultado);
    //mysqli_free_result($resultado1);
    //mysqli_free_result($resultado2);

    mysqli_close($conexion);

//session_destroy();

?>
