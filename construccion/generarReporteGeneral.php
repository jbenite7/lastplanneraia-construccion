<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$db->logActivity('Sistema', 'GENERAR_REPORTE_GRAL', "Iniciando generación de reporte consolidado general");

try {
    // 1. Limpiar tabla consolidada
    $db->query("TRUNCATE TABLE general_informe_consolidado");

    // 2. Obtener proyectos activos
    $stmtProyectos = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1");
    $proyectos = $stmtProyectos->fetchAll();

    foreach ($proyectos as $data1) {
        $proyecto = $data1["Proyecto_Proceso"];
        $dbPrefix = $data1["Base_de_Datos"];

        $sqlInsert = "INSERT INTO general_informe_consolidado (
            Proyecto, Semana, maxSemana, Proyecto_maxSemana, Actividad, 
            Fecha_Inicio, Fecha_Fin, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
            Critica, Atrasada, Activa, Ejecutado, cantidad_ppto, 
            Cantidad_Sugerida, Compromiso, Unidad, Ejecutado_Real, 
            PAC, P_Completado, Categoria_CNP, CNP, Observaciones_CNP, 
            Categoria_CNC, CNC, Observaciones_CNC, Responsable_AIA, Sub_Contratista
        )
        SELECT 
            ? AS Proyecto, 
            prog.Semana, 
            (SELECT MAX(Semana) FROM {$dbPrefix}_programacion_semanal) AS maxSemana,
            CONCAT(?, ' (', (SELECT Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = (SELECT MAX(Semana) FROM {$dbPrefix}_programacion_semanal)), ')') AS Proyecto_maxSemana,
            prog.Actividad, 
            prog.Fecha_Inicio, 
            prog.Fecha_Fin, 
            sem.Fecha_Inicio_Sem, 
            sem.Fecha_Fin_Sem, 
            prog.Critica, 
            prog.Atrasada, 
            prog.Activa, 
            prog.Ejecutado, 
            prog.cantidad_ppto, 
            prog.Cantidad_Sugerida, 
            prog.Compromiso, 
            prog.unidad, 
            prog.Ejecutado_Real, 
            prog.PAC, 
            prog.P_Completado, 
            prog.Categoria_CNP, 
            prog.CNP, 
            prog.Observaciones_CNP, 
            prog.Categoria_CNC, 
            prog.CNC, 
            prog.Observaciones_CNC, 
            prog.Responsable_AIA, 
            prog.Sub_Contratista
        FROM {$dbPrefix}_programacion_semanal AS prog
        LEFT JOIN {$dbPrefix}_semanas_activas AS sem ON sem.Semana = prog.Semana
        WHERE prog.Semana >= ((SELECT MAX(Semana) FROM {$dbPrefix}_programacion_semanal) - 1)";

        $db->query($sqlInsert, [$proyecto, $proyecto]);

        // Limpiar registros inconsistentes
        $db->query("DELETE FROM general_informe_consolidado WHERE Fecha_Inicio_Sem IS NULL OR Fecha_Fin_Sem IS NULL");

        echo "<li>$proyecto - OK";
    }

    $db->logActivity('Sistema', 'GENERAR_REPORTE_GRAL', "Reporte consolidado general generado con éxito");
    echo "<li>Programación Semanal - OK";

} catch (Exception $e) {
    error_log("Error en generarReporteGeneral.php: " . $e->getMessage());
    echo "<li>Error: " . $e->getMessage();
}
?>