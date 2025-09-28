<?php
session_start();
require("conexion.php"); // Esto ya nos da el objeto $db

// Usamos el nuevo método seguro para la consulta inicial
$db->query("TRUNCATE TABLE general_informe_subcontratistas");

// Obtenemos todos los proyectos activos
$stmt1 = $db->query("SELECT Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE Area='Construccion' AND Proyecto_Proceso!='Prueba' AND Activo=1");
$proyectos = $stmt1->fetchAll();

// Iteramos sobre cada proyecto para insertar sus datos de forma segura
foreach ($proyectos as $proyectoData) {
    $proyecto = $proyectoData["Proyecto_Proceso"];
    $base_de_datos = $proyectoData["Base_de_Datos"];

    // Medida de seguridad: Validar el nombre de la base de datos para prevenir inyecciones complejas.
    // Esto es crucial porque los nombres de tabla no pueden ser parametrizados en consultas preparadas.
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $base_de_datos)) {
        // Si el nombre de la BD no es válido, lo saltamos y continuamos con el siguiente.
        error_log("Nombre de base de datos no válido encontrado: " . $base_de_datos);
        continue;
    }

    // Construimos la consulta para cada proyecto de forma segura.
    // Los nombres de las tablas se insertan de forma segura después de la validación.
    $sql = "INSERT INTO general_informe_subcontratistas (
        `Proyecto`, `Semana`, `maxSemana`, `Proyecto_maxSemana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, 
        `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, 
        `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, 
        `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, 
        `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, 
        `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, 
        `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, 
        `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, 
        `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, 
        `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, 
        `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, 
        `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`
    )
    SELECT 
        ?, 
        cic.Semana,
        (SELECT MAX(cic_inner.Semana) FROM {$base_de_datos}_cic cic_inner),
        CONCAT(?, ' (', (SELECT sa_inner.Fecha_Fin_Sem FROM {$base_de_datos}_semanas_activas sa_inner WHERE sa_inner.Semana = (SELECT MAX(cic_inner.Semana) FROM {$base_de_datos}_cic cic_inner)), ')'),
        sa.Fecha_Inicio_Sem,
        sa.Fecha_Fin_Sem,
        cic.subcontratista, cic.correo_contacto, cic.NIT, cic.alcance, cic.tipo_proveedor, cic.PAC, cic.PAC_Acum,
        cic.P_Completado, cic.P_Completado_Acum, cic.Calidad, cic.Calidad_Acum, cic.GSA, cic.GSA_Acum, cic.SST, cic.SST_Acum,
        cic.ADM, cic.ADM_Acum, cic.Cal_Integral, cic.Cal_Integral_Acum, cic.Observaciones, cic.mdo_cal_1, cic.mdo_cal_2,
        cic.mdo_cal_3, cic.mdo_adm_1, cic.mdo_adm_2, cic.mdo_adm_3, cic.mdo_adm_4, cic.mdo_adm_5, cic.mdo_gsa_1,
        cic.mdo_gsa_2, cic.mdo_gsa_3, cic.mdo_gsa_4, cic.mdo_gsa_5, cic.mdo_gsa_6, cic.mdo_gsa_7, cic.mdo_gsa_8,
        cic.mdo_sst_1, cic.mdo_sst_2, cic.mdo_sst_3, cic.mdo_sst_4, cic.mdo_sst_5, cic.mdo_sst_6, cic.mdo_sst_7,
        cic.mdo_sst_8, cic.mdo_sst_9, cic.mdo_sst_10, cic.si_cal_1, cic.si_cal_2, cic.si_cal_3, cic.si_adm_1,
        cic.si_adm_2, cic.si_adm_3, cic.si_adm_4, cic.si_adm_5, cic.si_adm_6, cic.si_gsa_1, cic.si_gsa_2, cic.si_gsa_3,
        cic.si_gsa_4, cic.si_gsa_5, cic.si_gsa_6, cic.si_gsa_7, cic.si_gsa_8, cic.si_gsa_9, cic.si_gsa_10, cic.si_gsa_11,
        cic.si_gsa_12, cic.si_gsa_13, cic.si_gsa_14, cic.si_sst_1, cic.si_sst_2, cic.si_sst_3, cic.si_sst_4, cic.si_sst_5,
        cic.si_sst_6, cic.si_sst_7, cic.si_sst_8, cic.si_sst_9, cic.si_sst_10
    FROM {$base_de_datos}_cic cic
    LEFT JOIN {$base_de_datos}_semanas_activas sa ON sa.Semana = cic.Semana";

    // Ejecutamos la consulta para cada proyecto con sus parámetros correspondientes
    $db->query($sql, [$proyecto, $proyecto]);
}

echo "<li>Calificación Subcontratistas - OK</li>";

// No es necesario cerrar la conexión manualmente, el objeto $db lo maneja al final del script.
?>
