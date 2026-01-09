<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$db->logActivity('Sistema', 'GENERAR_CURVA_S_PDC', "Iniciando generación de Curva S para PDC");

try {
    // 1. Limpiar tabla de curvas PDC
    $db->query("TRUNCATE TABLE general_curvas_pdc");

    // 2. Obtener proyectos activos con PDC habilitado
    $stmtProyectos = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1");
    $proyectos = $stmtProyectos->fetchAll();

    if (count($proyectos) === 0) {
        echo "<li>Curva S PDC - No hay proyectos activos con PDC";
    } else {
        foreach ($proyectos as $data1) {
            $proyecto = $data1["Proyecto_Proceso"];
            $dbPrefix = $data1["Base_de_Datos"];

            // Calcular semanas totales
            $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$dbPrefix}_programa_consolidado WHERE Semana = (SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas)), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$dbPrefix}_programa_consolidado";
            $dataSemanasProyecto = $db->query($sqlSemanas)->fetch();
            $semanasProyecto = (int)($dataSemanasProyecto["semanasProyecto"] ?? 0);

            // Obtener semanas activas
            $stmtSemanasActivas = $db->query("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas");
            $semanasActivas = $stmtSemanasActivas->fetchAll();

            $ultimaSemanaActiva = 0;
            $ultimaFechaFinSem = null;

            foreach ($semanasActivas as $data3) {
                $semana = $data3["Semana"];
                $fechaInicioSem = $data3["Fecha_Inicio_Sem"];
                $fechaFinSem = $data3["Fecha_Fin_Sem"];
                $ultimaSemanaActiva = $semana;
                $ultimaFechaFinSem = $fechaFinSem;

                $sqlInsertPDC = "INSERT INTO general_curvas_pdc (
                    Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                    diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                    porcentajeCompletadoReal, porcentajeCompletadoTeorico
                )
                SELECT 
                    ? AS Proyecto, ? AS Semana, ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                    SUM(diasCompletadosReal) AS diasCompletadosReal, 
                    SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                    diasTotales, 
                    (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                    (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
                FROM (
                    SELECT 
                        (DATEDIFF({$dbPrefix}_pdc.fechaInicio, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaRealInicio IS NOT NULL THEN 9 
                                WHEN {$dbPrefix}_pdc.fechaRealInsumosObra IS NOT NULL THEN 8 
                                WHEN {$dbPrefix}_pdc.fechaRealFabricacion IS NOT NULL THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaRealLegalizacionContrato IS NOT NULL THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaRealCuadrosComparativos IS NOT NULL THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaRealReciboPropuestas IS NOT NULL THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaRealEntregaPliegos IS NOT NULL THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaRealIngresoLicify IS NOT NULL THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaRealElaboracionPliegos IS NOT NULL THEN 1 
                                ELSE 0 
                            END)/9
                        ) AS diasCompletadosReal,
                        (DATEDIFF({$dbPrefix}_pdc.fechaInicio, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaInicio <= ? THEN 9 
                                WHEN {$dbPrefix}_pdc.fechaInsumosObra <= ? THEN 8 
                                WHEN {$dbPrefix}_pdc.fechaFabricacion <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaLegalizacionContrato <= ? THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaCuadrosComparativos <= ? THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaReciboPropuestas <= ? THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaEntregaPliegos <= ? THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaIngresoLicify <= ? THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaElaboracionPliegos <= ? THEN 1 
                                ELSE 0 
                            END)/9
                        ) AS diasCompletadosTeorico,
                        (SELECT SUM(DATEDIFF({$dbPrefix}_pdc.fechaInicio, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) 
                         FROM {$dbPrefix}_pdc WHERE semana = ? AND titulo = 0) AS diasTotales
                    FROM {$dbPrefix}_pdc
                    WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaInicio IS NOT NULL AND semana = ?
                ) AS tabla";

                $db->query($sqlInsertPDC, [
                    $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $semana, $semana
                ]);
            }

            // Semanas proyectadas
            for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
                $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
                $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
                $ultimaFechaFinSem = $fechaFinSem;

                $sqlInsertPDCProyectado = "INSERT INTO general_curvas_pdc (
                    Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                    diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                    porcentajeCompletadoReal, porcentajeCompletadoTeorico
                )
                SELECT 
                    ? AS Proyecto, ? AS Semana, ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                    SUM(diasCompletadosReal) AS diasCompletadosReal, 
                    SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                    diasTotales, 
                    (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                    (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
                FROM (
                    SELECT 
                        NULL AS diasCompletadosReal,
                        (DATEDIFF({$dbPrefix}_pdc.fechaInicio, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaInicio <= ? THEN 9 
                                WHEN {$dbPrefix}_pdc.fechaInsumosObra <= ? THEN 8 
                                WHEN {$dbPrefix}_pdc.fechaFabricacion <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaLegalizacionContrato <= ? THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaCuadrosComparativos <= ? THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaReciboPropuestas <= ? THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaEntregaPliegos <= ? THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaIngresoLicify <= ? THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaElaboracionPliegos <= ? THEN 1 
                                ELSE 0 
                            END)/9
                        ) AS diasCompletadosTeorico,
                        (SELECT SUM(DATEDIFF({$dbPrefix}_pdc.fechaInicio, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) 
                         FROM {$dbPrefix}_pdc WHERE semana = ? AND titulo = 0) AS diasTotales
                    FROM {$dbPrefix}_pdc
                    WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaInicio IS NOT NULL AND semana = ?
                ) AS tabla";

                $db->query($sqlInsertPDCProyectado, [
                    $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $ultimaSemanaActiva, $ultimaSemanaActiva
                ]);
            }
        }

        // Integración con Curva General y Cálculo de Diferencias
        $sqlJoin = "SELECT tablaPDC.id, tablaPDC.Proyecto, tablaPDC.semana, 
                           tablaPDC.porcentajeCompletadoTeorico,
                           tablaGeneral.porcentajeCompletadoTeorico AS porcentajeCompletadoTeoricoGeneral,
                           tablaGeneral.porcentajeCompletadoReal AS porcentajeCompletadoRealGeneral
                    FROM general_curvas_pdc AS tablaPDC
                    LEFT JOIN general_curvas AS tablaGeneral 
                    ON tablaPDC.Proyecto = tablaGeneral.Proyecto AND tablaPDC.semana = tablaGeneral.semana
                    ORDER BY tablaPDC.Proyecto, tablaPDC.semana";
        
        $stmtJoin = $db->query($sqlJoin);
        $porcentajeTeoricoAnt = 0;
        $porcentajeTeoricoGenAnt = 0;
        $proyectoAnt = null;
        $anterior100 = false;
        $anterior100Gen = false;

        while ($row = $stmtJoin->fetch()) {
            if ($proyectoAnt !== $row['Proyecto']) {
                $porcentajeTeoricoAnt = 0;
                $porcentajeTeoricoGenAnt = 0;
                $proyectoAnt = $row['Proyecto'];
                $anterior100 = false;
                $anterior100Gen = false;
            }

            $pActual = (float)$row['porcentajeCompletadoTeorico'];
            $pActualGen = (float)$row['porcentajeCompletadoTeoricoGeneral'];
            $pRealGen = (float)$row['porcentajeCompletadoRealGeneral'];

            // Lógica de 100% y NULL
            if ($pActual >= 1 && $anterior100) {
                $db->query("UPDATE general_curvas_pdc SET porcentajeCompletadoTeorico = NULL WHERE id = ?", [$row['id']]);
            }
            if ($pActual >= 1) $anterior100 = true;

            if ($pActualGen >= 1 && $anterior100Gen) {
                // Aquí el código original no ponía NULL en el campo General dentro de esta tabla, solo en el propio.
                // Pero seguiremos la lógica de cálculo de diferencias.
            }
            if ($pActualGen >= 1) $anterior100Gen = true;

            $difTeorico = $pActual - $porcentajeTeoricoAnt;
            $difTeoricoGen = $pActualGen - $porcentajeTeoricoGenAnt;

            $db->query("UPDATE general_curvas_pdc SET 
                porcentajeCompletadoTeoricoGeneral = ?,
                porcentajeCompletadoRealGeneral = ?,
                diferenciaPorcentajeCompletadoTeorico = ?,
                diferenciaPorcentajeCompletadoTeoricoGeneral = ?
                WHERE id = ?", 
                [$pActualGen, $pRealGen, $difTeorico, $difTeoricoGen, $row['id']]
            );

            $porcentajeTeoricoAnt = $pActual;
            $porcentajeTeoricoGenAnt = $pActualGen;
        }

        echo "<li>Curva S PDC - OK";
    }

    $db->logActivity('Sistema', 'GENERAR_CURVA_S_PDC', "Proceso de Curva S PDC completado con éxito");

} catch (Exception $e) {
    error_log("Error en generarCurvaSPDC.php: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>