<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$db->logActivity('Sistema', 'GENERAR_CURVA_S', "Iniciando proceso de actualización masiva de Curvas S");

try {
    // 1. Limpiar tabla de curvas
    $db->query("TRUNCATE TABLE general_curvas");

    // 2. Obtener proyectos activos de construcción
    $stmtProyectos = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area = 'Construccion' AND Activo = 1");
    $proyectos = $stmtProyectos->fetchAll();

    foreach ($proyectos as $data1) {
        $proyecto = $data1["Proyecto_Proceso"];
        $dbPrefix = $data1["Base_de_Datos"];

        // Calcular semanas totales del proyecto
        $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$dbPrefix}_programa_consolidado WHERE Semana = (SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas)), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$dbPrefix}_programa_consolidado";
        $dataSemanasProyecto = $db->query($sqlSemanas)->fetch();
        $semanasProyecto = (int)($dataSemanasProyecto["semanasProyecto"] ?? 0);

        // Obtener semanas activas
        $stmtSemanasActivas = $db->query("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas");
        $semanasActivas = $stmtSemanasActivas->fetchAll();

        $ultimaSemanaActiva = 0;
        $ultimaFechaFinSem = null;

        // Procesar semanas reales (activas)
        foreach ($semanasActivas as $data3) {
            $semana = $data3["Semana"];
            $fechaInicioSem = $data3["Fecha_Inicio_Sem"];
            $fechaFinSem = $data3["Fecha_Fin_Sem"];
            $ultimaSemanaActiva = $semana;
            $ultimaFechaFinSem = $fechaFinSem;

            $sqlInsertReal = "INSERT INTO general_curvas (
                Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                porcentajeCompletadoReal, porcentajeCompletadoTeorico
            )
            SELECT 
                ? AS Proyecto, 
                MIN(Fecha_Inicio) AS fInicioProyecto, 
                MAX(Fecha_Fin) AS fFinProyecto, 
                ? AS Semana, 
                ? AS Fecha_Inicio_Sem, 
                ? AS Fecha_Fin_Sem, 
                SUM(diasCompletadosReal) AS diasCompletadosReal, 
                SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                diasTotales, 
                (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
            FROM (
                SELECT 
                    {$dbPrefix}_programa_consolidado.Fecha_Inicio, 
                    {$dbPrefix}_programa_consolidado.Fecha_Fin,
                    (SELECT 
                        CASE 
                            WHEN (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) <= 0 THEN 0 
                            WHEN (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) >= (DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) THEN (DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                            ELSE (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                        END
                    ) AS diasCompletadosTeorico,
                    (DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) * {$dbPrefix}_programa_consolidado.Ejecutado AS diasCompletadosReal,
                    (SELECT SUM(DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                     FROM {$dbPrefix}_programa_consolidado 
                     WHERE Semana = ? AND Titulo = 0) AS diasTotales
                FROM {$dbPrefix}_programa_consolidado
                WHERE Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
            ) AS tabla";

            $db->query($sqlInsertReal, [
                $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $semana, $semana
            ]);
        }

        // Procesar semanas proyectadas
        for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
            $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
            $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
            $ultimaFechaFinSem = $fechaFinSem;

            $sqlInsertProyectada = "INSERT INTO general_curvas (
                Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                porcentajeCompletadoReal, porcentajeCompletadoTeorico
            )
            SELECT 
                ? AS Proyecto, 
                MIN(Fecha_Inicio) AS fInicioProyecto, 
                MAX(Fecha_Fin) AS fFinProyecto, 
                ? AS Semana, 
                ? AS Fecha_Inicio_Sem, 
                ? AS Fecha_Fin_Sem, 
                SUM(diasCompletadosReal) AS diasCompletadosReal, 
                SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                diasTotales, 
                (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
            FROM (
                SELECT 
                    {$dbPrefix}_programa_consolidado.Fecha_Inicio, 
                    {$dbPrefix}_programa_consolidado.Fecha_Fin,
                    (SELECT 
                        CASE 
                            WHEN (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) <= 0 THEN 0 
                            WHEN (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) >= (DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) THEN (DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                            ELSE (DATEDIFF(?, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                        END
                    ) AS diasCompletadosTeorico,
                    NULL AS diasCompletadosReal,
                    (SELECT SUM(DATEDIFF({$dbPrefix}_programa_consolidado.Fecha_Fin, {$dbPrefix}_programa_consolidado.Fecha_Inicio)+1) 
                     FROM {$dbPrefix}_programa_consolidado 
                     WHERE Semana = ? AND Titulo = 0) AS diasTotales
                FROM {$dbPrefix}_programa_consolidado
                WHERE Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
            ) AS tabla";

            $db->query($sqlInsertProyectada, [
                $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $ultimaSemanaActiva, $ultimaSemanaActiva
            ]);
        }
    }

    // Calcular diferencias de porcentaje teórico para general_curvas
    $stmtCurvas = $db->query("SELECT * FROM general_curvas ORDER BY Proyecto, Semana");
    $porcentajeAnterior = 0;
    $proyectoAnterior = null;
    $anterior100 = false;

    while ($row = $stmtCurvas->fetch()) {
        if ($proyectoAnterior !== $row['Proyecto']) {
            $porcentajeAnterior = 0;
            $proyectoAnterior = $row['Proyecto'];
            $anterior100 = false;
        }

        $porcentajeActual = (float)$row['porcentajeCompletadoTeorico'];

        // Lógica de "NULL" si ya llegó al 100% anteriormente (manteniendo compatibilidad con lógica original)
        if ($porcentajeActual >= 1 && $anterior100) {
            $db->query("UPDATE general_curvas SET porcentajeCompletadoTeorico = NULL WHERE id = ?", [$row['id']]);
        }

        if ($porcentajeActual >= 1) {
            $anterior100 = true;
        }

        $diferencia = $porcentajeActual - $porcentajeAnterior;
        $porcentajeAnterior = $porcentajeActual;

        $db->query("UPDATE general_curvas SET diferenciaPorcentajeCompletadoTeorico = ? WHERE id = ?", [$diferencia, $row['id']]);
    }

    echo "<li>Curva S - OK";

    // --- SECCIÓN: CURVA S PDC APR ---
    
    $db->query("TRUNCATE TABLE general_curvas_pdc_apr");

    $stmtProyectosPDC = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1");
    $proyectosPDC = $stmtProyectosPDC->fetchAll();

    if (count($proyectosPDC) === 0) {
        echo "<li>Curva S PDC APR - No hay proyectos";
    } else {
        foreach ($proyectosPDC as $data1) {
            $proyecto = $data1["Proyecto_Proceso"];
            $dbPrefix = $data1["Base_de_Datos"];

            // Calcular semanas totales del proyecto (reutilizamos lógica)
            $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$dbPrefix}_programa_consolidado WHERE Semana = (SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas)), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$dbPrefix}_programa_consolidado";
            $dataSemanasProyecto = $db->query($sqlSemanas)->fetch();
            $semanasProyecto = (int)($dataSemanasProyecto["semanasProyecto"] ?? 0);

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

                $sqlInsertPDCReal = "INSERT INTO general_curvas_pdc_apr (
                    Proyecto, semana, maxSemana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                    diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                    porcentajeCompletadoReal, porcentajeCompletadoTeorico
                )
                SELECT 
                    ? AS Proyecto, ? AS Semana, (SELECT MAX(semana) FROM {$dbPrefix}_pdc) AS maxSemana, 
                    ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                    SUM(diasCompletadosReal) AS diasCompletadosReal, 
                    SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                    diasTotales, 
                    (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                    (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
                FROM (
                    SELECT 
                        (DATEDIFF({$dbPrefix}_pdc.fechaFabricacion, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaFabricacion <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaInsumosObra <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaLegalizacionContrato <= ? THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaCuadrosComparativos <= ? THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaReciboPropuestas <= ? THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaEntregaPliegos <= ? THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaIngresoLicify <= ? THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaELaboracionPliegos <= ? THEN 1 
                                ELSE 0 
                            END)/7
                        ) AS diasCompletadosTeorico,
                        (DATEDIFF({$dbPrefix}_pdc.fechaFabricacion, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaRealInicio IS NOT NULL THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaRealInsumosObra IS NOT NULL THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaRealFabricacion IS NOT NULL THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaRealLegalizacionContrato IS NOT NULL THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaRealCuadrosComparativos IS NOT NULL THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaRealReciboPropuestas IS NOT NULL THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaRealEntregaPliegos IS NOT NULL THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaRealIngresoLicify IS NOT NULL THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaRealELaboracionPliegos IS NOT NULL THEN 1 
                                ELSE 0 
                            END)/7
                        ) AS diasCompletadosReal,
                        (SELECT SUM(DATEDIFF({$dbPrefix}_pdc.fechaFabricacion, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) 
                         FROM {$dbPrefix}_pdc WHERE semana = ? AND titulo = 0) AS diasTotales
                    FROM {$dbPrefix}_pdc
                    WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaFabricacion IS NOT NULL AND semana = ?
                ) AS tabla";

                $db->query($sqlInsertPDCReal, [
                    $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $semana, $semana
                ]);
            }

            // Semanas proyectadas PDC
            for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
                $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
                $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
                $ultimaFechaFinSem = $fechaFinSem;

                $sqlInsertPDCProyectado = "INSERT INTO general_curvas_pdc_apr (
                    Proyecto, semana, maxSemana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                    diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                    porcentajeCompletadoReal, porcentajeCompletadoTeorico
                )
                SELECT 
                    ? AS Proyecto, ? AS Semana, (SELECT MAX(semana) FROM {$dbPrefix}_pdc) AS maxSemana, 
                    ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                    SUM(diasCompletadosReal) AS diasCompletadosReal, 
                    SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                    diasTotales, 
                    (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                    (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico 
                FROM (
                    SELECT 
                        (DATEDIFF({$dbPrefix}_pdc.fechaFabricacion, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) * (
                            (SELECT CASE 
                                WHEN {$dbPrefix}_pdc.fechaFabricacion <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaInsumosObra <= ? THEN 7 
                                WHEN {$dbPrefix}_pdc.fechaLegalizacionContrato <= ? THEN 6 
                                WHEN {$dbPrefix}_pdc.fechaCuadrosComparativos <= ? THEN 5 
                                WHEN {$dbPrefix}_pdc.fechaReciboPropuestas <= ? THEN 4 
                                WHEN {$dbPrefix}_pdc.fechaEntregaPliegos <= ? THEN 3 
                                WHEN {$dbPrefix}_pdc.fechaIngresoLicify <= ? THEN 2 
                                WHEN {$dbPrefix}_pdc.fechaELaboracionPliegos <= ? THEN 1 
                                ELSE 0 
                            END)/7
                        ) AS diasCompletadosTeorico,
                        NULL AS diasCompletadosReal,
                        (SELECT SUM(DATEDIFF({$dbPrefix}_pdc.fechaFabricacion, {$dbPrefix}_pdc.fechaElaboracionPliegos)+1) 
                         FROM {$dbPrefix}_pdc WHERE semana = ? AND titulo = 0) AS diasTotales
                    FROM {$dbPrefix}_pdc
                    WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaFabricacion IS NOT NULL AND semana = ?
                ) AS tabla";

                $db->query($sqlInsertPDCProyectado, [
                    $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                    $ultimaSemanaActiva, $ultimaSemanaActiva
                ]);
            }
        }

        // Integración con Curva General y Cálculo de Diferencias PDC
        $sqlJoin = "SELECT tablaPDC.id, tablaPDC.Proyecto, tablaPDC.semana, 
                           tablaPDC.porcentajeCompletadoTeorico,
                           tablaGeneral.porcentajeCompletadoTeorico AS porcentajeCompletadoTeoricoGeneral,
                           tablaGeneral.porcentajeCompletadoReal AS porcentajeCompletadoRealGeneral
                    FROM general_curvas_pdc_apr AS tablaPDC
                    LEFT JOIN general_curvas AS tablaGeneral 
                    ON tablaPDC.Proyecto = tablaGeneral.Proyecto AND tablaPDC.semana = tablaGeneral.semana
                    ORDER BY tablaPDC.Proyecto, tablaPDC.semana";
        
        $stmtJoin = $db->query($sqlJoin);
        $porcentajeTeoricoAnt = 0;
        $porcentajeTeoricoGenAnt = 0;
        $proyectoAnt = null;

        while ($row = $stmtJoin->fetch()) {
            if ($proyectoAnt !== $row['Proyecto']) {
                $porcentajeTeoricoAnt = 0;
                $porcentajeTeoricoGenAnt = 0;
                $proyectoAnt = $row['Proyecto'];
            }

            $pActual = (float)$row['porcentajeCompletadoTeorico'];
            $pActualGen = (float)$row['porcentajeCompletadoTeoricoGeneral'];
            $pRealGen = (float)$row['porcentajeCompletadoRealGeneral'];

            $difTeorico = round($pActual - $porcentajeTeoricoAnt, 4);
            $difTeoricoGen = round($pActualGen - $porcentajeTeoricoGenAnt, 4);

            $db->query("UPDATE general_curvas_pdc_apr SET 
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

        echo "<li>Curva S PDC APR - OK";
    }

    $db->logActivity('Sistema', 'GENERAR_CURVA_S', "Proceso de Curvas S completado con éxito");

} catch (Exception $e) {
    error_log("Error en generarCurvaS.php: " . $e->getMessage());
    echo "<li>Error: " . $e->getMessage();
}
?>