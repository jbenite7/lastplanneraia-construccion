<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$db->logActivity('Sistema', 'GENERAR_REPORTE_PDC', "Iniciando generación de reporte consolidado de Plan de Compras (PDC)");

try {
    // 1. Limpiar tabla consolidada
    $db->query("TRUNCATE TABLE general_informe_pdc");

    // 2. Obtener proyectos activos con PDC habilitado
    $stmtProyectos = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1");
    $proyectos = $stmtProyectos->fetchAll();

    if (count($proyectos) === 0) {
        echo "<li>Plan de Compras - No hay proyectos activos";
    } else {
        foreach ($proyectos as $data1) {
            $proyecto = $data1["Proyecto_Proceso"];
            $dbPrefix = $data1["Base_de_Datos"];

            $sqlInsert = "INSERT INTO general_informe_pdc (
                Proyecto, semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaHoy, maxSemana, Proyecto_maxSemana, 
                tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, 
                fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, 
                fechaIngresoLicify, diasIngresoLicify, fechaRealIngresoLicify, 
                fechaEntregaPliegos, diasEntregaPliegos, fechaRealEntregaPliegos, 
                fechaReciboPropuestas, diasReciboPropuestas, fechaRealReciboPropuestas, 
                fechaCuadrosComparativos, diasCuadrosComparativos, fechaRealCuadrosComparativos, 
                fechaLegalizacionContrato, diasLegalizacionContrato, fechaRealLegalizacionContrato, 
                fechaFabricacion, diasFabricacion, fechaRealFabricacion, 
                fechaInsumosObra, diasInsumosObra, fechaRealInsumosObra, 
                fechaInicio, fechaInicioProyectada, fechaRealInicio, 
                idProveedorAdjudicado, proveedorAdjudicado, nitProveedorAdjudicado, 
                numeroContrato, fechaVencimientoPolizas, valorPresupuesto, valorPrimeraNegociacion, 
                valorAdjudicado, valorAnticipo, valorReclamado, valorDevoluciones, observacionesContrato
            )
            SELECT 
                ? AS Proyecto, 
                pdc.semana, 
                sem.Fecha_Inicio_Sem, 
                sem.Fecha_Fin_Sem, 
                DATE(NOW()) AS fechaHoy, 
                (SELECT MAX(semana) FROM {$dbPrefix}_pdc) AS maxSemana,
                CONCAT(?, ' (', (SELECT Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = (SELECT MAX(semana) FROM {$dbPrefix}_pdc)), ')') AS Proyecto_maxSemana,
                pdc.tipoPaquete, 
                pdc.paqueteContratacion, 
                pdc.contratos, 
                pdc.numeroSubcontratos, 
                pdc.subcontratoPaquete, 
                pdc.estado, 
                pdc.fechaElaboracionPliegos, 
                pdc.diasElaboracionPliegos, 
                pdc.fechaRealElaboracionPliegos, 
                pdc.fechaIngresoLicify, 
                pdc.diasIngresoLicify, 
                pdc.fechaRealIngresoLicify, 
                pdc.fechaEntregaPliegos, 
                pdc.diasEntregaPliegos, 
                pdc.fechaRealEntregaPliegos, 
                pdc.fechaReciboPropuestas, 
                pdc.diasReciboPropuestas, 
                pdc.fechaRealReciboPropuestas, 
                pdc.fechaCuadrosComparativos, 
                pdc.diasCuadrosComparativos, 
                pdc.fechaRealCuadrosComparativos, 
                pdc.fechaLegalizacionContrato, 
                pdc.diasLegalizacionContrato, 
                pdc.fechaRealLegalizacionContrato, 
                pdc.fechaFabricacion, 
                pdc.diasFabricacion, 
                pdc.fechaRealFabricacion, 
                pdc.fechaInsumosObra, 
                pdc.diasInsumosObra, 
                pdc.fechaRealInsumosObra, 
                pdc.fechaInicio, 
                pdc.fechaInicioProyectada, 
                pdc.fechaRealInicio, 
                pdc.idProveedorAdjudicado, 
                sub.subcontratista AS proveedorAdjudicado, 
                sub.NIT AS nitProveedorAdjudicado, 
                pdc.numeroContrato, 
                pdc.fechaVencimientoPolizas, 
                pdc.valorPresupuesto, 
                pdc.valorPrimeraNegociacion, 
                pdc.valorAdjudicado, 
                pdc.valorAnticipo, 
                pdc.valorReclamado, 
                pdc.valorDevoluciones, 
                pdc.observacionesContrato
            FROM {$dbPrefix}_pdc AS pdc
            LEFT JOIN {$dbPrefix}_semanas_activas AS sem ON sem.Semana = pdc.semana
            LEFT JOIN {$dbPrefix}_subcontratistas AS sub ON sub.Id = pdc.idProveedorAdjudicado
            WHERE pdc.titulo = 0 AND pdc.semana <= (SELECT MAX(semana) FROM {$dbPrefix}_pdc)";

            $db->query($sqlInsert, [$proyecto, $proyecto]);

            echo "<li>$proyecto - OK";
        }
        echo "<li> Plan de Compras - OK";
    }

    $db->logActivity('Sistema', 'GENERAR_REPORTE_PDC', "Reporte consolidado de Plan de Compras generado con éxito");

} catch (Exception $e) {
    error_log("Error en generarReportePDC.php: " . $e->getMessage());
    echo "<li>Error: " . $e->getMessage();
}
?>