<?php

namespace App\Services;

use Database;
use Exception;
use PDO;
use TableResolver;
use App\Services\RestrictionConfigResolver;

class ReportProcessor
{
    private $db;
    private $progressCallback;
    private $subprocessCallback;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    public function setSubprocessCallback(callable $callback): void
    {
        $this->subprocessCallback = $callback;
    }

    private function reportProgress(string $reportLabel, string $project, int $index, int $total, string $status, ?string $message = null): void
    {
        if ($this->progressCallback) {
            ($this->progressCallback)($reportLabel, $project, $index, $total, $status, $message);
        }
    }

    private function reportSubprocess(string $reportLabel, string $project, string $subprocess, string $status, ?string $message = null): void
    {
        error_log("[REPORT] {$reportLabel} / {$project} / {$subprocess}: {$status}" . ($message !== null ? " — {$message}" : ""));
        if ($this->subprocessCallback) {
            ($this->subprocessCallback)($reportLabel, $project, $subprocess, $status, $message);
        }
    }

    private function isValidDbPrefix($dbPrefix)
    {
        return is_string($dbPrefix) && preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix) === 1;
    }

    /**
     * Resuelve el nombre de tabla para un prefijo de proyecto dado.
     */
    private function t(string $dbPrefix, string $tableType): string
    {
        return TableResolver::resolveByPrefix($dbPrefix, $tableType);
    }

    /**
     * Obtiene el projectId para un prefijo de proyecto dado.
     */
    private function pid(string $dbPrefix): ?int
    {
        return TableResolver::getProjectIdByPrefix($dbPrefix);
    }

    private function getConstructionProjects($pdcOnly = false)
    {
        $sql = "SELECT Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1";
        if ($pdcOnly) {
            $sql .= " AND pdcActivo=1";
        }

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    private function collectInvalidDbPrefixMessage($dbPrefix, $proyecto, array &$messages)
    {
        if ($this->isValidDbPrefix($dbPrefix)) {
            return false;
        }

        $messages[] = "$proyecto - Error: Nombre de base de datos inválido.";

        return true;
    }

    private function resolveSemanaProyecto($dbName, $semana)
    {
        if ($semana !== null) {
            return (int) $semana;
        }

        $t = TableResolver::resolveByPrefix($dbName, 'programacion_semanal');
        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        $stmtMax = $this->db->queryWithProject("SELECT MAX(Semana) FROM {$t}", [], $projectId);
        $semanaProyecto = $stmtMax->fetchColumn();

        return $semanaProyecto ? (int) $semanaProyecto : null;
    }

    private function tableExists($tableName)
    {
        if (!$this->isValidDbPrefix($tableName)) {
            return false;
        }

        $stmt = $this->db->queryWithProject(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$tableName],
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    private function hasReportablePdc(string $dbPrefix, bool $requiresDates = false): bool
    {
        if (!$this->isValidDbPrefix($dbPrefix)) {
            return false;
        }

        $tPdc = TableResolver::resolveByPrefix($dbPrefix, 'pdc');
        if (!$this->tableExists($tPdc)) {
            return false;
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        $where = "titulo = 0";

        if ($requiresDates) {
            $where .= " AND fechaElaboracionPliegos IS NOT NULL AND fechaFabricacion IS NOT NULL";
        }

        $count = (int) $this->db
            ->queryWithProject("SELECT COUNT(*) FROM {$tPdc} WHERE {$where}", [], $projectId)
            ->fetchColumn();

        return $count > 0;
    }

    public function generateCurvaS()
    {
        $this->db->logActivity('Sistema', 'GENERAR_CURVA_S', "Iniciando proceso de actualización masiva de Curvas S");
        $results = [];

        try {
            $this->db->query("TRUNCATE TABLE general_curvas");
            $this->db->query("TRUNCATE TABLE general_curvas_pdc_apr");

            $proyectos = $this->getConstructionProjects();
            $totalProyectos = count($proyectos);
            $idx = 0;

            foreach ($proyectos as $data1) {
                try {
                    $proyecto = $data1["Proyecto_Proceso"];
                    $dbPrefix = $data1["Base_de_Datos"];
                    $idx++;

                    // Validar prefijo
                    if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $results)) {
                        $this->reportProgress('Curva S', $proyecto, $idx, $totalProyectos, 'skip');
                        continue;
                    }

                    // Verify table exists
                    $tProgCons = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');
                    $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
                    $this->db->queryWithProject("SELECT 1 FROM {$tProgCons} LIMIT 1", [], $projectId);
                    $this->reportSubprocess('Curva S', $proyecto, 'Validando programa consolidado', 'ok');

                    // Calculate total weeks
                    $tSemanasActivas = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
                    $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$tProgCons} WHERE Semana = (SELECT MAX(Semana) FROM {$tSemanasActivas})), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$tProgCons}";
                    $dataSemanasProyecto = $this->db->queryWithProject($sqlSemanas, [], $projectId)->fetch();
                    $semanasProyecto = (int) ($dataSemanasProyecto["semanasProyecto"] ?? 0);
                    $this->reportSubprocess('Curva S', $proyecto, 'Calculando semanas', 'ok', "{$semanasProyecto} semanas");

                    // Get active weeks
                    $stmtSemanasActivas = $this->db->queryWithProject("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSemanasActivas}", [], $projectId);
                    $semanasActivas = $stmtSemanasActivas->fetchAll();

                    $ultimaSemanaActiva = 0;
                    $ultimaFechaFinSem = null;

                    // Process active weeks (Real)
                    foreach ($semanasActivas as $data3) {
                        $semana = $data3["Semana"];
                        $fechaInicioSem = $data3["Fecha_Inicio_Sem"];
                        $fechaFinSem = $data3["Fecha_Fin_Sem"];
                        $ultimaSemanaActiva = $semana;
                        $ultimaFechaFinSem = $fechaFinSem;

                        $sqlInsertReal = "INSERT INTO general_curvas (
                            Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                            diasCompletadosReal, diasCompletadosTeorico, diasCompletadosLineaBase, 
                            diasTotales, diasTotalesLineaBase, 
                            porcentajeCompletadoReal, porcentajeCompletadoTeorico, porcentajeCompletadoLineaBase,
                            diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoLineaBase
                        )
                        SELECT 
                            ? AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto, 
                            ? AS Semana, ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                            SUM(diasCompletadosReal) AS diasCompletadosReal, 
                            SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                            0 AS diasCompletadosLineaBase,
                            diasTotales, 
                            0 AS diasTotalesLineaBase,
                            (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                            (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico,
                            0 AS porcentajeCompletadoLineaBase,
                            0 AS diferenciaPorcentajeCompletadoTeorico,
                            0 AS diferenciaPorcentajeCompletadoLineaBase
                        FROM (
                            SELECT 
                                {$tProgCons}.Fecha_Inicio, 
                                {$tProgCons}.Fecha_Fin,
                                (SELECT CASE 
                                    WHEN (DATEDIFF(?, {$tProgCons}.Fecha_Inicio)+1) <= 0 THEN 0 
                                    WHEN (DATEDIFF(?, {$tProgCons}.Fecha_Inicio)+1) >= (DATEDIFF({$tProgCons}.Fecha_Fin, {$tProgCons}.Fecha_Inicio)+1) THEN (DATEDIFF({$tProgCons}.Fecha_Fin, {$tProgCons}.Fecha_Inicio)+1) 
                                    ELSE (DATEDIFF(?, {$tProgCons}.Fecha_Inicio)+1) 
                                END) AS diasCompletadosTeorico,
                                (DATEDIFF({$tProgCons}.Fecha_Fin, {$tProgCons}.Fecha_Inicio)+1) * {$tProgCons}.Ejecutado AS diasCompletadosReal,
                                (SELECT SUM(DATEDIFF({$tProgCons}.Fecha_Fin, {$tProgCons}.Fecha_Inicio)+1) 
                                 FROM {$tProgCons} WHERE Semana = ? AND Titulo = 0) AS diasTotales
                            FROM {$tProgCons}
                            WHERE Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
                        ) AS tabla";

                        $this->db->queryWithProject($sqlInsertReal, [
                            $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                            $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                            $semana, $semana,
                        ], $projectId);
                    }

                    $this->reportSubprocess('Curva S', $proyecto, 'Insertando semanas reales', 'ok', count($semanasActivas) . ' semanas');

                    // Process projected weeks
                    for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
                        $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
                        $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
                        $ultimaFechaFinSem = $fechaFinSem;

                        $sqlInsertProyectada = "INSERT INTO general_curvas (
                            Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                            diasCompletadosReal, diasCompletadosTeorico, diasCompletadosLineaBase, 
                            diasTotales, diasTotalesLineaBase, 
                            porcentajeCompletadoReal, porcentajeCompletadoTeorico, porcentajeCompletadoLineaBase,
                            diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoLineaBase
                        )
                        SELECT 
                            ? AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto, 
                            ? AS Semana, ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                            SUM(diasCompletadosReal) AS diasCompletadosReal, 
                            SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, 
                            0 AS diasCompletadosLineaBase,
                            diasTotales, 
                            0 AS diasTotalesLineaBase,
                            (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, 
                            (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico,
                            0 AS porcentajeCompletadoLineaBase,
                            0 AS diferenciaPorcentajeCompletadoTeorico,
                            0 AS diferenciaPorcentajeCompletadoLineaBase
                        FROM (
                            SELECT 
                                {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio, 
                                {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Fin,
                                (SELECT CASE 
                                    WHEN (DATEDIFF(?, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) <= 0 THEN 0 
                                    WHEN (DATEDIFF(?, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) >= (DATEDIFF({$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Fin, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) THEN (DATEDIFF({$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Fin, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) 
                                    ELSE (DATEDIFF(?, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) 
                                END) AS diasCompletadosTeorico,
                                0 AS diasCompletadosReal,
                                (SELECT SUM(DATEDIFF({$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Fin, {$this->t($dbPrefix, 'programa_consolidado')}.Fecha_Inicio)+1) 
                                 FROM {$this->t($dbPrefix, 'programa_consolidado')} WHERE Semana = ? AND Titulo = 0) AS diasTotales
                            FROM {$this->t($dbPrefix, 'programa_consolidado')}
                            WHERE Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
                        ) AS tabla";

                        $this->db->queryWithProject($sqlInsertProyectada, [
                            $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                            $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                            $ultimaSemanaActiva, $ultimaSemanaActiva,
                        ]);
                    }

                    $proyectadas = max(0, $semanasProyecto - $ultimaSemanaActiva);
                    $this->reportSubprocess('Curva S', $proyecto, 'Insertando semanas proyectadas', 'ok', $proyectadas . ' semanas');
                    $this->reportProgress('Curva S', $proyecto, $idx, $totalProyectos, 'ok');

                } catch (\Exception $e) {
                    $results[] = "$proyecto - Error: " . $e->getMessage();
                    $this->reportProgress('Curva S', $proyecto, $idx, $totalProyectos, 'error', $e->getMessage());
                }
            }

            // Calculate differences
            $stmtCurvas = $this->db->query("SELECT * FROM general_curvas ORDER BY Proyecto, Semana");
            $porcentajeAnterior = 0;
            $proyectoAnterior = null;
            while ($row = $stmtCurvas->fetch()) {
                if ($proyectoAnterior !== $row['Proyecto']) {
                    $porcentajeAnterior = 0;
                    $proyectoAnterior = $row['Proyecto'];
                }

                $porcentajeActual = (float) $row['porcentajeCompletadoTeorico'];

                $diferencia = $porcentajeActual - $porcentajeAnterior;
                $porcentajeAnterior = $porcentajeActual;

                $this->db->query("UPDATE general_curvas SET diferenciaPorcentajeCompletadoTeorico = ? WHERE id = ?", [$diferencia, $row['id']]);
            }

            $this->reportSubprocess('Curva S', '', 'Calculando diferencias', 'ok');
            $results[] = "Curva S - OK";

            // --- Curva S PDC APR ---
            $proyectosPDC = $this->getConstructionProjects(true);

            if (count($proyectosPDC) === 0) {
                $results[] = "Curva S PDC APR - No hay proyectos";
            } else {
                $totalPDC = count($proyectosPDC);
                $pdcIdx = 0;
                foreach ($proyectosPDC as $data1) {
                    $pdcProyecto = $data1["Proyecto_Proceso"];
                    $pdcIdx++;
                    $this->reportSubprocess('Curva S PDC APR', $pdcProyecto, 'Procesando PDC APR', 'running');
                    try {
                        $pdcProcessed = $this->processProjectPDC_APR($data1);
                        $status = $pdcProcessed === false ? 'skip' : 'ok';
                        $this->reportSubprocess('Curva S PDC APR', $pdcProyecto, 'Procesando PDC APR', $status, $pdcProcessed === false ? 'sin PDC con fechas' : null);
                        $this->reportProgress('Curva S PDC APR', $pdcProyecto, $pdcIdx, $totalPDC, $status);
                    } catch (\Exception $e) {
                        $this->reportSubprocess('Curva S PDC APR', $pdcProyecto, 'Procesando PDC APR', 'error', $e->getMessage());
                        $results[] = $pdcProyecto . " - Error PDC: " . $e->getMessage();
                        $this->reportProgress('Curva S PDC APR', $pdcProyecto, $pdcIdx, $totalPDC, 'error', $e->getMessage());
                    }
                }

                $results[] = "Curva S PDC APR - OK";
            }

            $this->db->logActivity('Sistema', 'GENERAR_CURVA_S', "Proceso de Curvas S completado con éxito");

            return ['success' => true, 'messages' => $results];

        } catch (Exception $e) {
            error_log("ReportProcessor::generateCurvaS Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function processProjectPDC_APR($data1)
    {
        $proyecto = $data1["Proyecto_Proceso"];
        $dbPrefix = $data1["Base_de_Datos"];

        if (!$this->isValidDbPrefix($dbPrefix)) {
            throw new Exception("Nombre de base de datos inválido: {$dbPrefix}");
        }

        if (!$this->hasReportablePdc($dbPrefix, true)) {
            return false;
        }

        // Weeks calculation for PDC
        $projectId = $this->pid($dbPrefix);
        $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$this->t($dbPrefix, 'programa_consolidado')} WHERE Semana = (SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'semanas_activas')})), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$this->t($dbPrefix, 'programa_consolidado')}";
        $dataSemanasProyecto = $this->db->queryWithProject($sqlSemanas, [], $projectId)->fetch();
        $semanasProyecto = (int) ($dataSemanasProyecto["semanasProyecto"] ?? 0);
        $this->reportSubprocess('Curva S PDC APR', $proyecto, 'Calculando semanas PDC APR', 'ok', "{$semanasProyecto} semanas");

                    $stmtSemanasActivas = $this->db->queryWithProject("SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$this->t($dbPrefix, 'semanas_activas')}", [], $projectId);
        $semanasActivas = $stmtSemanasActivas->fetchAll();

        // (Reusing logic structure from legacy script)
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
                porcentajeCompletadoReal, porcentajeCompletadoTeorico,
                porcentajeCompletadoTeoricoGeneral, porcentajeCompletadoRealGeneral,
                diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoTeoricoGeneral
            )
            SELECT 
                ? AS Proyecto, ? AS Semana, (SELECT MAX(semana) FROM {$this->t($dbPrefix, 'pdc')}) AS maxSemana, 
                ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                COALESCE(SUM(diasCompletadosReal), 0) AS diasCompletadosReal, 
                COALESCE(SUM(diasCompletadosTeorico), 0) AS diasCompletadosTeorico, 
                diasTotales, 
                CASE WHEN diasTotales > 0 THEN (COALESCE(SUM(diasCompletadosReal), 0)/diasTotales) ELSE 0 END AS porcentajeCompletadoReal, 
                CASE WHEN diasTotales > 0 THEN (COALESCE(SUM(diasCompletadosTeorico), 0)/diasTotales) ELSE 0 END AS porcentajeCompletadoTeorico,
                0 AS porcentajeCompletadoTeoricoGeneral,
                0 AS porcentajeCompletadoRealGeneral,
                0 AS diferenciaPorcentajeCompletadoTeorico,
                0 AS diferenciaPorcentajeCompletadoTeoricoGeneral 
            FROM (
                SELECT 
                    (DATEDIFF({$this->t($dbPrefix, 'pdc')}.fechaFabricacion, {$this->t($dbPrefix, 'pdc')}.fechaElaboracionPliegos)+1) * (
                        (SELECT CASE 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaFabricacion <= ? THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaInsumosObra <= ? THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaLegalizacionContrato <= ? THEN 6 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaCuadrosComparativos <= ? THEN 5 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaReciboPropuestas <= ? THEN 4 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaEntregaPliegos <= ? THEN 3 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaELaboracionPliegos <= ? THEN 2 
                            ELSE 0 
                        END)/7
                    ) AS diasCompletadosTeorico,
                    (DATEDIFF({$this->t($dbPrefix, 'pdc')}.fechaFabricacion, {$this->t($dbPrefix, 'pdc')}.fechaElaboracionPliegos)+1) * (
                        (SELECT CASE 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealInicio IS NOT NULL THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealInsumosObra IS NOT NULL THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealFabricacion IS NOT NULL THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealLegalizacionContrato IS NOT NULL THEN 6 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealCuadrosComparativos IS NOT NULL THEN 5 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealReciboPropuestas IS NOT NULL THEN 4 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealEntregaPliegos IS NOT NULL THEN 3 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaRealELaboracionPliegos IS NOT NULL THEN 2 
                            ELSE 0 
                        END)/7
                    ) AS diasCompletadosReal,
                    COALESCE((SELECT SUM(DATEDIFF({$this->t($dbPrefix, 'pdc')}.fechaFabricacion, {$this->t($dbPrefix, 'pdc')}.fechaElaboracionPliegos)+1) 
                     FROM {$this->t($dbPrefix, 'pdc')} WHERE semana = ? AND titulo = 0), 0) AS diasTotales
                FROM {$this->t($dbPrefix, 'pdc')}
                WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaFabricacion IS NOT NULL AND semana = ?
            ) AS tabla";

            $this->db->queryWithProject($sqlInsertPDCReal, [
                $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $semana, $semana,
            ]);
        }

        $this->reportSubprocess('Curva S PDC APR', $proyecto, 'Insertando semanas reales PDC APR', 'ok', count($semanasActivas) . ' semanas');

        // Projected
        for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
            $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
            $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
            $ultimaFechaFinSem = $fechaFinSem;

            $sqlInsertPDCProyectado = "INSERT INTO general_curvas_pdc_apr (
                Proyecto, semana, maxSemana, Fecha_Inicio_Sem, Fecha_Fin_Sem, 
                diasCompletadosReal, diasCompletadosTeorico, diasTotales, 
                porcentajeCompletadoReal, porcentajeCompletadoTeorico,
                porcentajeCompletadoTeoricoGeneral, porcentajeCompletadoRealGeneral,
                diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoTeoricoGeneral
            )
            SELECT 
                ? AS Proyecto, ? AS Semana, (SELECT MAX(semana) FROM {$this->t($dbPrefix, 'pdc')}) AS maxSemana, 
                ? AS Fecha_Inicio_Sem, ? AS Fecha_Fin_Sem, 
                COALESCE(SUM(diasCompletadosReal), 0) AS diasCompletadosReal, 
                COALESCE(SUM(diasCompletadosTeorico), 0) AS diasCompletadosTeorico, 
                diasTotales, 
                CASE WHEN diasTotales > 0 THEN (COALESCE(SUM(diasCompletadosReal), 0)/diasTotales) ELSE 0 END AS porcentajeCompletadoReal, 
                CASE WHEN diasTotales > 0 THEN (COALESCE(SUM(diasCompletadosTeorico), 0)/diasTotales) ELSE 0 END AS porcentajeCompletadoTeorico,
                0 AS porcentajeCompletadoTeoricoGeneral,
                0 AS porcentajeCompletadoRealGeneral,
                0 AS diferenciaPorcentajeCompletadoTeorico,
                0 AS diferenciaPorcentajeCompletadoTeoricoGeneral 
            FROM (
                SELECT 
                    (DATEDIFF({$this->t($dbPrefix, 'pdc')}.fechaFabricacion, {$this->t($dbPrefix, 'pdc')}.fechaElaboracionPliegos)+1) * (
                        (SELECT CASE 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaFabricacion <= ? THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaInsumosObra <= ? THEN 7 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaLegalizacionContrato <= ? THEN 6 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaCuadrosComparativos <= ? THEN 5 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaReciboPropuestas <= ? THEN 4 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaEntregaPliegos <= ? THEN 3 
                            WHEN {$this->t($dbPrefix, 'pdc')}.fechaELaboracionPliegos <= ? THEN 2 
                            ELSE 0 
                        END)/7
                    ) AS diasCompletadosTeorico,
                    0 AS diasCompletadosReal,
                    COALESCE((SELECT SUM(DATEDIFF({$this->t($dbPrefix, 'pdc')}.fechaFabricacion, {$this->t($dbPrefix, 'pdc')}.fechaElaboracionPliegos)+1) 
                     FROM {$this->t($dbPrefix, 'pdc')} WHERE semana = ? AND titulo = 0), 0) AS diasTotales
                FROM {$this->t($dbPrefix, 'pdc')}
                WHERE titulo = 0 AND fechaElaboracionPliegos IS NOT NULL AND fechaFabricacion IS NOT NULL AND semana = ?
            ) AS tabla";

            $this->db->queryWithProject($sqlInsertPDCProyectado, [
                $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $fechaInicioSem, $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                $ultimaSemanaActiva, $ultimaSemanaActiva,
            ]);
        }

        $proyectadas = max(0, $semanasProyecto - $ultimaSemanaActiva);
        $this->reportSubprocess('Curva S PDC APR', $proyecto, 'Insertando semanas proyectadas PDC APR', 'ok', $proyectadas . ' semanas');

        // Integration with General Curve
        $sqlJoin = "SELECT tablaPDC.id, tablaPDC.Proyecto, tablaPDC.semana, 
                           tablaPDC.porcentajeCompletadoTeorico,
                           tablaGeneral.porcentajeCompletadoTeorico AS porcentajeCompletadoTeoricoGeneral,
                           tablaGeneral.porcentajeCompletadoReal AS porcentajeCompletadoRealGeneral
                    FROM general_curvas_pdc_apr AS tablaPDC
                    LEFT JOIN general_curvas AS tablaGeneral 
                    ON tablaPDC.Proyecto = tablaGeneral.Proyecto AND tablaPDC.semana = tablaGeneral.semana
                    ORDER BY tablaPDC.Proyecto, tablaPDC.semana";

        $stmtJoin = $this->db->query($sqlJoin);
        $porcentajeTeoricoAnt = 0;
        $porcentajeTeoricoGenAnt = 0;
        $proyectoAnt = null;

        while ($row = $stmtJoin->fetch()) {
            if ($proyectoAnt !== $row['Proyecto']) {
                $porcentajeTeoricoAnt = 0;
                $porcentajeTeoricoGenAnt = 0;
                $proyectoAnt = $row['Proyecto'];
            }

            $pActual = (float) $row['porcentajeCompletadoTeorico'];
            $pActualGen = (float) $row['porcentajeCompletadoTeoricoGeneral'];
            $pRealGen = (float) $row['porcentajeCompletadoRealGeneral'];

            $difTeorico = round($pActual - $porcentajeTeoricoAnt, 4);
            $difTeoricoGen = round($pActualGen - $porcentajeTeoricoGenAnt, 4);

            $this->db->query(
                "UPDATE general_curvas_pdc_apr SET 
                porcentajeCompletadoTeoricoGeneral = ?,
                porcentajeCompletadoRealGeneral = ?,
                diferenciaPorcentajeCompletadoTeorico = ?,
                diferenciaPorcentajeCompletadoTeoricoGeneral = ?
                WHERE id = ?",
                [$pActualGen, $pRealGen, $difTeorico, $difTeoricoGen, $row['id']],
            );

            $porcentajeTeoricoAnt = $pActual;
            $porcentajeTeoricoGenAnt = $pActualGen;
        }

        $this->reportSubprocess('Curva S PDC APR', $proyecto, 'Integrando con curva general', 'ok');
        return true;
    }

    public function generateReporteGeneral()
    {
        $this->db->logActivity('Sistema', 'GENERAR_REPORTE_GRAL', "Iniciando generación de reporte consolidado general");
        $this->db->query("TRUNCATE TABLE general_informe_consolidado");

        $result = $this->getConstructionProjects();
        $messages = [];
        $totalGeneral = count($result);
        $genIdx = 0;

        foreach ($result as $data1) {
            try {
                $proyecto = $data1["Proyecto_Proceso"];
                $dbPrefix = $data1["Base_de_Datos"];
                $genIdx++;

                if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $messages)) {
                    $this->reportProgress('General', $proyecto, $genIdx, $totalGeneral, 'skip');
                    continue;
                }

                $projectId = $this->pid($dbPrefix);
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
                (SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'programacion_semanal')}) AS maxSemana,
                CONCAT(?, ' (', (SELECT Fecha_Fin_Sem FROM {$this->t($dbPrefix, 'semanas_activas')} WHERE Semana = (SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'programacion_semanal')})), ')') AS Proyecto_maxSemana,
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
            FROM {$this->t($dbPrefix, 'programacion_semanal')} AS prog
            LEFT JOIN {$this->t($dbPrefix, 'semanas_activas')} AS sem ON sem.Semana = prog.Semana
            WHERE prog.Semana >= ((SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'programacion_semanal')}) - 1)";

                $this->db->queryWithProject($sqlInsert, [$proyecto, $proyecto], $projectId);
                $this->reportSubprocess('General', $proyecto, 'Insertando informe consolidado', 'ok');
                $this->db->query("DELETE FROM general_informe_consolidado WHERE Fecha_Inicio_Sem IS NULL OR Fecha_Fin_Sem IS NULL");
                $this->reportSubprocess('General', $proyecto, 'Limpiando registros inválidos', 'ok');
                $messages[] = "$proyecto - OK";
                $this->reportProgress('General', $proyecto, $genIdx, $totalGeneral, 'ok');
            } catch (\Exception $e) {
                $messages[] = "$proyecto - Error: " . $e->getMessage();
                $this->reportProgress('General', $proyecto, $genIdx, $totalGeneral, 'error', $e->getMessage());
            }
        }

        $this->db->logActivity('Sistema', 'GENERAR_REPORTE_GRAL', "Reporte consolidado general generado con éxito");

        return ['success' => true, 'messages' => $messages];
    }

    public function generateRestriccionesGeneral()
    {
        $this->db->logActivity('Sistema', 'GENERAR_RESTRICCIONES_GRAL', "Iniciando consolidación de listado de restricciones general");
        $this->db->query("TRUNCATE TABLE general_informe_restricciones_consolidado");
        $proyectos = $this->getConstructionProjects();
        $messages = ["Liberación de Restricciones - OK"];
        $totalRest = count($proyectos);
        $restIdx = 0;

        foreach ($proyectos as $data1) {
            try {
                $proyecto = $data1["Proyecto_Proceso"];
                $dbPrefix = $data1["Base_de_Datos"];
                $restIdx++;

                if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $messages)) {
                    $this->reportProgress('Restricciones', $proyecto, $restIdx, $totalRest, 'skip');
                    continue;
                }

                // Resolve restriction config per project
                $projectId = $this->pid($dbPrefix);
                try {
                    $restrConfig = RestrictionConfigResolver::resolve($dbPrefix);
                    $area = $restrConfig['area'];
                } catch (\Throwable $e) {
                    error_log("ReportProcessor: RestrictionConfigResolver failed for {$dbPrefix}: " . $e->getMessage());
                    $area = 'Construccion';
                }

                // Build restriction label => column map based on Area
                if ($area === 'Pre-Construccion') {
                    $pcLabel2 = 'Restricción 2';
                    $pcLabel3 = 'Restricción 3';
                    $pcLabel4 = 'Restricción 4';

                    try {
                        $stmtPc = $this->db->query(
                            "SELECT pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre
                             FROM general_proyectos_procesos
                             WHERE Base_de_Datos = ? LIMIT 1",
                            [$dbPrefix]
                        );
                        $proyectoPc = $stmtPc->fetch(PDO::FETCH_ASSOC);
                        if ($proyectoPc) {
                            if (!empty($proyectoPc['pc_restr_2_nombre'])) {
                                $pcLabel2 = $proyectoPc['pc_restr_2_nombre'];
                            }
                            if (!empty($proyectoPc['pc_restr_3_nombre'])) {
                                $pcLabel3 = $proyectoPc['pc_restr_3_nombre'];
                            }
                            if (!empty($proyectoPc['pc_restr_4_nombre'])) {
                                $pcLabel4 = $proyectoPc['pc_restr_4_nombre'];
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log("ReportProcessor: Error loading PC restriction labels for {$dbPrefix}: " . $e->getMessage());
                    }

                    $restricciones = [
                        'Predecesora' => 'restriccion_pc_1',
                        $pcLabel2 => 'restriccion_pc_2',
                        $pcLabel3 => 'restriccion_pc_3',
                        $pcLabel4 => 'restriccion_pc_4',
                    ];
                } else {
                    $restricciones = [
                        'D_y_E' => 'D_y_E',
                        'Materiales' => 'Materiales',
                        'MdeO' => 'MdeO',
                        'Equipos' => 'Equipos',
                        'Predecesora' => 'Predecesora',
                        'Pdto_Cons' => 'Pdto_Cons',
                        'Modelo' => 'Modelo',
                    ];
                }

                $restriccionesCount = 0;
                foreach ($restricciones as $nombreLabel => $columna) {
                    $sqlInsert = "INSERT INTO general_informe_restricciones_consolidado (
                    Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Actividad, 
                    Fecha_Inicio, Fecha_Fin, Semanas_Inicio, Restriccion, valorRestriccion, estadoActividad
                )
                SELECT 
                    ? AS Proyecto, 
                    prog.Semana, 
                    sem.Fecha_Inicio_Sem, 
                    sem.Fecha_Fin_Sem, 
                    prog.Actividad, 
                    prog.Fecha_Inicio, 
                    prog.Fecha_Fin, 
                    prog.Semanas_Inicio, 
                    ? AS Restriccion,
                    (CASE 
                        WHEN prog.{$columna} IS NULL THEN '0%' 
                        WHEN prog.{$columna} = 'N/A' THEN 'N/A' 
                        ELSE CONCAT(FLOOR(prog.{$columna} * 100), '%') 
                    END) AS valorRestriccion,
                    prog.Ejecutado
                FROM {$this->t($dbPrefix, 'programa_consolidado')} AS prog
                LEFT JOIN {$this->t($dbPrefix, 'semanas_activas')} AS sem ON sem.Semana = prog.Semana
                WHERE prog.{$columna} != 'N/A' 
                  AND prog.Titulo = 0 
                  AND prog.Semanas_Inicio < 7 
                  AND prog.Ejecutado < 1 
                  AND prog.Semana >= ((SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'programa_consolidado')}) - 3)
                  AND sem.Fecha_Inicio_Sem IS NOT NULL";

                    $this->db->queryWithProject($sqlInsert, [$proyecto, $nombreLabel], $projectId);
                    $restriccionesCount++;
                }
                $messages[] = "$proyecto - OK";
                $this->reportSubprocess('Restricciones', $proyecto, 'Insertando restricciones', 'ok', $restriccionesCount . ' tipos');
                $this->reportProgress('Restricciones', $proyecto, $restIdx, $totalRest, 'ok');
            } catch (\Exception $e) {
                $messages[] = "$proyecto - Error: " . $e->getMessage();
                $this->reportProgress('Restricciones', $proyecto, $restIdx, $totalRest, 'error', $e->getMessage());
            }
        }

        $this->db->logActivity('Sistema', 'GENERAR_RESTRICCIONES_GRAL', "Consolidación de restricciones completada con éxito");

        return ['success' => true, 'messages' => $messages];
    }
    public function generateReportePDC()
    {
        $this->db->logActivity('Sistema', 'GENERAR_REPORTE_PDC', "Iniciando generación de reporte consolidado de Plan de Compras (PDC)");
        $this->db->query("TRUNCATE TABLE general_informe_pdc");

        $proyectos = $this->getConstructionProjects(true);
        $messages = [];

        if (count($proyectos) === 0) {
            $messages[] = "Plan de Compras - No hay proyectos activos";
        } else {
            $totalPDC = count($proyectos);
            $pdcIdx = 0;
            foreach ($proyectos as $data1) {
                try {
                    $proyecto = $data1["Proyecto_Proceso"];
                    $dbPrefix = $data1["Base_de_Datos"];
                    $pdcIdx++;

                    if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $messages)) {
                        $this->reportProgress('PDC', $proyecto, $pdcIdx, $totalPDC, 'skip');
                        continue;
                    }

                    if (!$this->hasReportablePdc($dbPrefix)) {
                        $messages[] = "$proyecto - Skip: PDC requerido pero pendiente de elaboración";
                        $this->reportProgress('PDC', $proyecto, $pdcIdx, $totalPDC, 'skip', 'PDC pendiente de elaboración');
                        continue;
                    }

                    $projectId = $this->pid($dbPrefix);
                    $sqlInsert = "INSERT INTO general_informe_pdc (
                    Proyecto, semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaHoy, maxSemana, Proyecto_maxSemana, 
                    tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, 
                    fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, 
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
                    (SELECT MAX(semana) FROM {$this->t($dbPrefix, 'pdc')}) AS maxSemana,
                    CONCAT(?, ' (', (SELECT Fecha_Fin_Sem FROM {$this->t($dbPrefix, 'semanas_activas')} WHERE Semana = (SELECT MAX(semana) FROM {$this->t($dbPrefix, 'pdc')})), ')') AS Proyecto_maxSemana,
                    pdc.tipoPaquete, 
                    pdc.paqueteContratacion, 
                    pdc.contratos, 
                    pdc.numeroSubcontratos, 
                    pdc.subcontratoPaquete, 
                    pdc.estado, 
                    pdc.fechaElaboracionPliegos, 
                    pdc.diasElaboracionPliegos, 
                    pdc.fechaRealElaboracionPliegos, 
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
                FROM {$this->t($dbPrefix, 'pdc')} AS pdc
                LEFT JOIN {$this->t($dbPrefix, 'semanas_activas')} AS sem ON sem.Semana = pdc.semana
                LEFT JOIN {$this->t($dbPrefix, 'subcontratistas')} AS sub ON sub.Id = pdc.idProveedorAdjudicado
                WHERE pdc.titulo = 0 AND pdc.semana <= (SELECT MAX(semana) FROM {$this->t($dbPrefix, 'pdc')})";

                    $this->db->queryWithProject($sqlInsert, [$proyecto, $proyecto], $projectId);

                    $messages[] = "$proyecto - OK";
                    $this->reportSubprocess('PDC', $proyecto, 'Insertando informe PDC', 'ok');
                    $this->reportProgress('PDC', $proyecto, $pdcIdx, $totalPDC, 'ok');
                } catch (\Exception $e) {
                    $messages[] = "$proyecto - Error: " . $e->getMessage();
                    $this->reportProgress('PDC', $proyecto, $pdcIdx, $totalPDC, 'error', $e->getMessage());
                }
            }
        }

        $this->db->logActivity('Sistema', 'GENERAR_REPORTE_PDC', "Reporte consolidado de Plan de Compras generado con éxito");

        return ['success' => true, 'messages' => $messages];
    }

    public function generateReporteSubcontratistas()
    {
        $this->db->query("TRUNCATE TABLE general_informe_subcontratistas");
        $proyectos = $this->getConstructionProjects();
        $messages = [];
        $totalSub = count($proyectos);
        $subIdx = 0;

        foreach ($proyectos as $proyectoData) {
            try {
                $proyecto = $proyectoData["Proyecto_Proceso"];
                $base_de_datos = $proyectoData["Base_de_Datos"];
                $subIdx++;

                if (!$this->isValidDbPrefix($base_de_datos)) {
                    error_log("Nombre de base de datos no válido encontrado: " . $base_de_datos);
                    $messages[] = "$proyecto - Error: Nombre de base de datos inválido.";
                    $this->reportProgress('Subcontratistas', $proyecto, $subIdx, $totalSub, 'skip');
                    continue;
                }

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
                cic.ADM, cic.ADM_Acum, COALESCE(cic.Cal_Integral, 'NR'), COALESCE(cic.Cal_Integral_Acum, 'NR'), cic.Observaciones, cic.mdo_cal_1, cic.mdo_cal_2,
                cic.mdo_cal_3, cic.mdo_adm_1, cic.mdo_adm_2, cic.mdo_adm_3, cic.mdo_adm_4, cic.mdo_adm_5, cic.mdo_gsa_1,
                cic.mdo_gsa_2, cic.mdo_gsa_3, cic.mdo_gsa_4, cic.mdo_gsa_5, cic.mdo_gsa_6, cic.mdo_gsa_7, cic.mdo_gsa_8,
                cic.mdo_sst_1, cic.mdo_sst_2, cic.mdo_sst_3, cic.mdo_sst_4, cic.mdo_sst_5, cic.mdo_sst_6, cic.mdo_sst_7,
                cic.mdo_sst_8, cic.mdo_sst_9, cic.mdo_sst_10, cic.si_cal_1, cic.si_cal_2, cic.si_cal_3, cic.si_adm_1,
                cic.si_adm_2, cic.si_adm_3, cic.si_adm_4, cic.si_adm_5, cic.si_adm_6, cic.si_gsa_1, cic.si_gsa_2, cic.si_gsa_3,
                cic.si_gsa_4, cic.si_gsa_5, cic.si_gsa_6, cic.si_gsa_7, cic.si_gsa_8, cic.si_gsa_9, cic.si_gsa_10, cic.si_gsa_11,
                cic.si_gsa_12, cic.si_gsa_13, cic.si_gsa_14, cic.si_sst_1, cic.si_sst_2, cic.si_sst_3, cic.si_sst_4, cic.si_sst_5,
                cic.si_sst_6, cic.si_sst_7, cic.si_sst_8, cic.si_sst_9, cic.si_sst_10
            FROM {$this->t($base_de_datos, 'cic')} cic
            LEFT JOIN {$this->t($base_de_datos, 'semanas_activas')} sa ON sa.Semana = cic.Semana";

                $this->db->queryWithProject($sql, [$proyecto, $proyecto], $this->pid($base_de_datos));

                $messages[] = "$proyecto - OK";
                $this->reportSubprocess('Subcontratistas', $proyecto, 'Insertando informe subcontratistas', 'ok');
                $this->reportProgress('Subcontratistas', $proyecto, $subIdx, $totalSub, 'ok');
            } catch (\Exception $e) {
                $messages[] = "$proyecto - Error: " . $e->getMessage();
                $this->reportProgress('Subcontratistas', $proyecto, $subIdx, $totalSub, 'error', $e->getMessage());
            }
        }

        $messages[] = "Calificación Subcontratistas - OK";

        return ['success' => true, 'messages' => $messages];
    }
    public function updateCICProyectos($semana = null)
    {
        $this->db->logActivity('Sistema', 'ACTUALIZAR_CIC', "Iniciando actualización de Calificación Integral (CIC/CIP)");

        $proyectos = $this->getConstructionProjects();
        $messages = [];
        $totalCIC = count($proyectos);
        $cicIdx = 0;

        $messages[] = "Calificación Integral:";

        foreach ($proyectos as $dataProyectos) {
            $proyecto = $dataProyectos["Proyecto_Proceso"];
            $dbName = $dataProyectos["Base_de_Datos"];
            $cicIdx++;

            if ($this->collectInvalidDbPrefixMessage($dbName, $proyecto, $messages)) {
                $this->reportProgress('CIC', $proyecto, $cicIdx, $totalCIC, 'skip');
                continue;
            }

            try {
                // Determine week for this project
                $semanaProyecto = $this->resolveSemanaProyecto($dbName, $semana);

                if (!$semanaProyecto) {
                    $messages[] = "$proyecto - Skip: No hay semanas activas";
                    $this->reportProgress('CIC', $proyecto, $cicIdx, $totalCIC, 'skip');
                    continue;
                }

                // --- PROCESAR SUB-CONTRATISTAS (CIC) ---
                $this->reportSubprocess('CIC', $proyecto, 'Procesando CIC subcontratistas', 'running');
                $this->processCalificacionEntidad(
                    $proyecto,
                    $dbName,
                    $semanaProyecto,
                    'cic',
                    'subcontratista',
                    'CIC Subcontratistas',
                    [$this, 'updatePACSubcontratistas'],
                    [$this, 'generateSubcontratistas'],
                    $messages,
                );

                // --- PROCESAR PROFESIONALES (CIP) ---
                $this->reportSubprocess('CIC', $proyecto, 'Procesando CIP profesionales', 'running');
                $this->processCalificacionEntidad(
                    $proyecto,
                    $dbName,
                    $semanaProyecto,
                    'cip',
                    'profesional',
                    'CIC Profesionales',
                    [$this, 'updatePACProfesionales'],
                    [$this, 'generateProfesionales'],
                    $messages,
                );

                $messages[] = "$proyecto (Semana $semanaProyecto) - OK";
                $this->reportProgress('CIC', $proyecto, $cicIdx, $totalCIC, 'ok');

            } catch (\Exception $e) {
                $messages[] = "$proyecto - Error CIC: " . $e->getMessage();
                $this->reportProgress('CIC', $proyecto, $cicIdx, $totalCIC, 'error', $e->getMessage());
            }
        }

        $this->db->logActivity('Sistema', 'ACTUALIZAR_CIC', "Actualización de Calificación Integral finalizada");

        return ['success' => true, 'messages' => $messages];
    }

    private function processCalificacionEntidad(
        $proyecto,
        $dbName,
        $semanaProyecto,
        $tableSuffix,
        $entityColumn,
        $warningLabel,
        callable $updateCallback,
        callable $generateCallback,
        array &$messages,
    ) {
        try {
            $tableName = $this->t($dbName, $tableSuffix);
            $projectId = $this->pid($dbName);
            $this->reportSubprocess($warningLabel, $proyecto, 'Verificando tabla', 'running', $tableName);
            if (!$this->tableExists($tableName)) {
                return;
            }
            $this->reportSubprocess($warningLabel, $proyecto, 'Verificando tabla', 'ok', 'Tabla existe');

            $stmtConteo = $this->db->queryWithProject("SELECT COUNT(*) as conteo FROM {$tableName} WHERE Semana = ?", [$semanaProyecto], $projectId);
            $conteo = (int) ($stmtConteo->fetchColumn() ?: 0);
            if ($conteo > 0) {
                $this->reportSubprocess($warningLabel, $proyecto, 'Actualizando PAC existentes', 'running');
                call_user_func($updateCallback, $semanaProyecto, $dbName, $semanaProyecto);
                $this->reportSubprocess($warningLabel, $proyecto, 'Actualizando PAC existentes', 'ok');
            }

            $this->reportSubprocess($warningLabel, $proyecto, 'Generando registros faltantes', 'running');
            $stmtExisting = $this->db->queryWithProject("SELECT {$entityColumn} FROM {$tableName} WHERE Semana = ?", [$semanaProyecto], $projectId);
            $excludeEntities = $stmtExisting->fetchAll(\PDO::FETCH_COLUMN);
            call_user_func($generateCallback, $semanaProyecto, $dbName, $excludeEntities);
            $this->reportSubprocess($warningLabel, $proyecto, 'Generando registros faltantes', 'ok');
        } catch (\Exception $e) {
            $messages[] = "$proyecto - Warning {$warningLabel}: " . $e->getMessage();
        }
    }

    private function fetchDistinctProgramacionEntities($dbName, $columnName, $semana, array $exclude = [])
    {
        $allowedColumns = ['Sub_Contratista', 'Responsable_AIA'];
        if (!in_array($columnName, $allowedColumns, true)) {
            throw new Exception("Columna no permitida para consulta de entidades: {$columnName}");
        }

        $params = [$semana];
        $sqlExclude = "";

        if (!empty($exclude)) {
            $placeholders = [];
            foreach ($exclude as $value) {
                $placeholders[] = "?";
                $params[] = $value;
            }
            $sqlExclude = " AND {$columnName} NOT IN (" . implode(',', $placeholders) . ")";
        }

        $query = "SELECT DISTINCT {$columnName}
                  FROM {$this->t($dbName, 'programacion_semanal')}
                  WHERE Semana = ?
                    AND {$columnName} != ''
                    AND (Activa = '1' OR Activa = 'NA')
                    AND (PAC = '1' OR PAC = '0')
                    {$sqlExclude}";

        $stmt = $this->db->queryWithProject($query, $params, $this->pid($dbName));

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function fetchPacAndCompletadoStats($dbName, $columnName, $entityValue, $semana)
    {
        $allowedColumns = ['Sub_Contratista', 'Responsable_AIA'];
        if (!in_array($columnName, $allowedColumns, true)) {
            throw new Exception("Columna no permitida para calculo de indicadores: {$columnName}");
        }

        $queryStats = "SELECT
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN P_Completado ELSE 0 END) /
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS P_Completado,
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN PAC ELSE 0 END) /
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS PAC
                       FROM {$this->t($dbName, 'programacion_semanal')}
                       WHERE {$columnName} = ? AND Semana = ?";

        $stmtStats = $this->db->queryWithProject($queryStats, [$entityValue, $semana], $this->pid($dbName));
        $stats = $stmtStats->fetch();

        return [
            'PAC' => $stats['PAC'] ?? 0,
            'P_Completado' => $stats['P_Completado'] ?? 0,
        ];
    }

    private function deleteRowsNotInProcessedEntities($tableName, $entityColumn, $semana, array $processedEntities, ?int $projectId = null)
    {
        $sqlDelete = "DELETE FROM {$tableName} WHERE Semana = ?";
        $deleteParams = [$semana];

        if (!empty($processedEntities)) {
            $placeholders = [];
            foreach ($processedEntities as $entity) {
                $placeholders[] = "?";
                $deleteParams[] = $entity;
            }
            $sqlDelete .= " AND {$entityColumn} NOT IN (" . implode(',', $placeholders) . ")";
        }

        $this->db->queryWithProject($sqlDelete, $deleteParams, $projectId);
    }

    private function generateSubcontratistas($semana, $dbName, $excludeSubcontratistas)
    {
        $subcontratistas = $this->fetchDistinctProgramacionEntities($dbName, 'Sub_Contratista', $semana, $excludeSubcontratistas);

        foreach ($subcontratistas as $subcontratista) {
            // Check if record exists for this week
            $check = $this->db->queryWithProject("SELECT COUNT(*) FROM {$this->t($dbName, 'cic')} WHERE Semana = ? AND subcontratista = ?", [$semana, $subcontratista], $this->pid($dbName))->fetchColumn();
            if ($check == 0) {
                $sql = "INSERT INTO {$this->t($dbName, 'cic')} (Semana, subcontratista) VALUES (?, ?)";
                [$sql, $params] = $this->db->insertProjectId($sql, $this->pid($dbName) ?? 0, [$semana, $subcontratista]);
                $this->db->query($sql, $params);
            }
        }

        $this->updatePACSubcontratistas($semana, $dbName, $semana);
        $this->updateIntegralSubcontratistas($semana, $dbName);
    }

    private function generateProfesionales($semana, $dbName, $excludeProfesionales)
    {
        $profesionales = $this->fetchDistinctProgramacionEntities($dbName, 'Responsable_AIA', $semana, $excludeProfesionales);

        foreach ($profesionales as $profesional) {
            // Check if record exists for this week
            $check = $this->db->queryWithProject("SELECT COUNT(*) FROM {$this->t($dbName, 'cip')} WHERE Semana = ? AND profesional = ?", [$semana, $profesional], $this->pid($dbName))->fetchColumn();
            if ($check == 0) {
                $sql = "INSERT INTO {$this->t($dbName, 'cip')} (Semana, profesional) VALUES (?, ?)";
                [$sql, $params] = $this->db->insertProjectId($sql, $this->pid($dbName) ?? 0, [$semana, $profesional]);
                $this->db->query($sql, $params);
            }
        }

        $this->updatePACProfesionales($semana, $dbName, $semana);
        $this->updateIntegralProfesionales($semana, $dbName);
    }

    private function updatePACSubcontratistas($semana, $dbName, $semanaFiltro)
    {
        $subcontratistas = $this->fetchDistinctProgramacionEntities($dbName, 'Sub_Contratista', $semana);
        $processedSubs = [];

        foreach ($subcontratistas as $subcontratista) {
            $processedSubs[] = $subcontratista;
            $stats = $this->fetchPacAndCompletadoStats($dbName, 'Sub_Contratista', $subcontratista, $semana);

            $pac = $stats['PAC'];
            $pCompletado = $stats['P_Completado'];

            $updateQuery = "UPDATE {$this->t($dbName, 'cic')} cic
                            INNER JOIN {$this->t($dbName, 'subcontratistas')} sub ON cic.subcontratista = sub.subcontratista 
                            SET cic.P_Completado = ?,
                                cic.PAC = ?,
                                cic.Semana = ?,
                                cic.correo_contacto = sub.correo_contacto,
                                cic.NIT = sub.NIT,
                                cic.alcance = sub.alcance,
                                cic.tipo_proveedor = sub.tipo_proveedor 
                            WHERE cic.subcontratista = ? AND cic.Semana = ?";

            $this->db->queryWithProject($updateQuery, [$pCompletado, $pac, $semana, $subcontratista, $semanaFiltro], $this->pid($dbName));
        }

        $this->deleteRowsNotInProcessedEntities("{$this->t($dbName, 'cic')}", 'subcontratista', $semana, $processedSubs, $this->pid($dbName));
    }

    private function updatePACProfesionales($semana, $dbName, $semanaFiltro)
    {
        $profesionales = $this->fetchDistinctProgramacionEntities($dbName, 'Responsable_AIA', $semana);
        $processedProfs = [];

        foreach ($profesionales as $profesional) {
            $processedProfs[] = $profesional;
            $stats = $this->fetchPacAndCompletadoStats($dbName, 'Responsable_AIA', $profesional, $semana);

            $pac = $stats['PAC'];
            $pCompletado = $stats['P_Completado'];

            $updateQuery = "UPDATE {$this->t($dbName, 'cip')} cip
                            INNER JOIN {$this->t($dbName, 'profesionales')} prof ON cip.profesional = prof.nombre 
                            SET cip.P_Completado = ?,
                                cip.PAC = ?,
                                cip.Semana = ?,
                                cip.correo_contacto = prof.email 
                            WHERE cip.profesional = ? AND cip.Semana = ?";

            $this->db->queryWithProject($updateQuery, [$pCompletado, $pac, $semana, $profesional, $semanaFiltro], $this->pid($dbName));
        }

        $this->deleteRowsNotInProcessedEntities("{$this->t($dbName, 'cip')}", 'profesional', $semana, $processedProfs, $this->pid($dbName));
    }

    private function updateIntegralSubcontratistas($semana, $dbName)
    {
        $stmtCic = $this->db->queryWithProject("SELECT Id, subcontratista, PAC, Calidad, ADM, GSA, SST FROM {$this->t($dbName, 'cic')} WHERE Semana = ?", [$semana], $this->pid($dbName));
        $cicRows = $stmtCic->fetchAll();

        foreach ($cicRows as $cic) {
            $id = $cic['Id'];
            $subcontratista = $cic['subcontratista'];

            $queryAcum = "SELECT 
                (SELECT ROUND(AVG(PAC), 3) FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND PAC != 'NA') AS PAC_Acum,
                (SELECT ROUND(AVG(P_Completado), 3) FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND P_Completado != 'NA') AS P_Completado_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Calidad), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND Calidad NOT IN ('NA', 'NR')) AS Calidad_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(GSA), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND GSA NOT IN ('NA', 'NR')) AS GSA_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(SST), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND SST NOT IN ('NA', 'NR')) AS SST_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(ADM), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND ADM NOT IN ('NA', 'NR')) AS ADM_Acum
                FROM DUAL";

            // Params duplicated due to named placeholders limitation in raw copy-paste or simple positional mapping
            // Using positional params strictly
            $paramsAcum = [
                $semana, $subcontratista,
                $semana, $subcontratista,
                $semana, $subcontratista,
                $semana, $subcontratista,
                $semana, $subcontratista,
                $semana, $subcontratista,
            ];

            $stmtAcum = $this->db->queryWithProject($queryAcum, $paramsAcum, $this->pid($dbName));
            $acum = $stmtAcum->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cic')} SET 
                            PAC_Acum = ?, P_Completado_Acum = ?, Calidad_Acum = ?, 
                            GSA_Acum = ?, SST_Acum = ?, ADM_Acum = ? 
                          WHERE Id = ?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Calidad_Acum'],
                $acum['GSA_Acum'], $acum['SST_Acum'], $acum['ADM_Acum'], $id,
            ]);

            $cal_integral = $this->calculateLogicaIntegral($cic['PAC'] ?? 'NA', $cic['Calidad'] ?? 'NA', $cic['SST'] ?? 'NA', $cic['GSA'] ?? 'NA', $cic['ADM'] ?? 'NA');
            $cal_integral_val = is_numeric($cal_integral) ? (float) $cal_integral : 0.0;

            $cal_integral_acum = $this->calculateLogicaIntegral($acum['PAC_Acum'] ?? 'NA', $acum['Calidad_Acum'] ?? 'NA', $acum['SST_Acum'] ?? 'NA', $acum['GSA_Acum'] ?? 'NA', $acum['ADM_Acum'] ?? 'NA');
            $cal_integral_acum_val = is_numeric($cal_integral_acum) ? (float) $cal_integral_acum : 0.0;

            $cal_integral_str = $cal_integral_val ? number_format($cal_integral_val, 3, '.', '') : '0';
            $cal_integral_acum_str = $cal_integral_acum_val ? number_format($cal_integral_acum_val, 3, '.', '') : '0';

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cic')} SET Cal_Integral = ?, Cal_Integral_Acum = ? WHERE Id = ?", [
                $cal_integral_str, $cal_integral_acum_str, $id,
            ]);
        }
    }

    private function updateIntegralProfesionales($semana, $dbName)
    {
        $stmtCip = $this->db->queryWithProject("SELECT profesional, PAC FROM {$this->t($dbName, 'cip')} WHERE Semana = ?", [$semana], $this->pid($dbName));
        $cipRows = $stmtCip->fetchAll();

        foreach ($cipRows as $cip) {
            $profesional = $cip['profesional'];

            $queryStats = "SELECT 
                (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END 
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND Activa = 1 AND Critica = 1 AND Atrasada = 0) AS Act_Criticas_Cumplidas,
                (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END 
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND (Activa = 1 OR Activa = 'NA') AND Critica = 0 AND Atrasada = 0) AS Act_No_Criticas_Cumplidas,
                (SELECT CASE WHEN COUNT(Atrasada) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Atrasada), 3) ELSE 'NA' END 
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND Activa = 1 AND Atrasada = 1) AS Act_Atrasadas_Cumplidas
                FROM DUAL";

            $stmtStats = $this->db->queryWithProject($queryStats, [$semana, $profesional, $semana, $profesional, $semana, $profesional], $this->pid($dbName));
            $stats = $stmtStats->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cip')} SET 
                            Act_Criticas_Cumplidas = ?, Act_No_Criticas_Cumplidas = ?, Act_Atrasadas_Cumplidas = ? 
                          WHERE profesional = ? AND Semana = ?", [
                $stats['Act_Criticas_Cumplidas'], $stats['Act_No_Criticas_Cumplidas'],
                $stats['Act_Atrasadas_Cumplidas'], $profesional, $semana,
            ]);

            $queryAcum = "SELECT 
                (SELECT ROUND(AVG(PAC), 3) FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND PAC != 'NA') AS PAC_Acum,
                (SELECT ROUND(AVG(P_Completado), 3) FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND P_Completado != 'NA') AS P_Completado_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Criticas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND Act_Criticas_Cumplidas != 'NA') AS Act_Criticas_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_No_Criticas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND Act_No_Criticas_Cumplidas != 'NA') AS Act_No_Criticas_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Atrasadas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND Act_Atrasadas_Cumplidas != 'NA') AS Act_Atrasadas_Acum
                FROM DUAL";

            $paramsAcum = [
                $semana, $profesional,
                $semana, $profesional,
                $semana, $profesional,
                $semana, $profesional,
                $semana, $profesional,
            ];
            $stmtAcum = $this->db->queryWithProject($queryAcum, $paramsAcum, $this->pid($dbName));
            $acum = $stmtAcum->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cip')} SET 
                            PAC_Acum = ?, P_Completado_Acum = ?, 
                            Act_Criticas_Cumplidas_Acum = ?, Act_No_Criticas_Cumplidas_Acum = ?, Act_Atrasadas_Cumplidas_Acum = ? 
                          WHERE profesional = ? AND Semana = ?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Act_Criticas_Acum'],
                $acum['Act_No_Criticas_Acum'], $acum['Act_Atrasadas_Acum'], $profesional, $semana,
            ]);

            $pac_cons = $this->calculatePACConsolidado(
                $cip['PAC'] ?? 'NA',
                $stats['Act_Criticas_Cumplidas'] ?? 'NA',
                $stats['Act_No_Criticas_Cumplidas'] ?? 'NA',
                $stats['Act_Atrasadas_Cumplidas'] ?? 'NA',
            );
            $pac_cons_val = is_numeric($pac_cons) ? (float) $pac_cons : 0.0;

            $pac_cons_acum = $this->calculatePACConsolidado(
                $acum['PAC_Acum'] ?? 'NA',
                $acum['Act_Criticas_Acum'] ?? 'NA',
                $acum['Act_No_Criticas_Acum'] ?? 'NA',
                $acum['Act_Atrasadas_Acum'] ?? 'NA',
            );
            $pac_cons_acum_val = is_numeric($pac_cons_acum) ? (float) $pac_cons_acum : 0.0;

            $pac_cons_str = $pac_cons_val ? number_format($pac_cons_val, 3, '.', '') : '0';
            $pac_cons_acum_str = $pac_cons_acum_val ? number_format($pac_cons_acum_val, 3, '.', '') : '0';

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cip')} SET PAC_Consolidado = ?, PAC_Consolidado_Acum = ? 
                          WHERE profesional = ? AND Semana = ?", [
                $pac_cons_str, $pac_cons_acum_str, $profesional, $semana,
            ]);
        }
    }

    private function calculateLogicaIntegral($pac, $calidad, $sst, $gsa, $adm)
    {
        // Safe defaults for math operations
        $pac = ($pac === 'NA' || $pac === 'NR' || $pac === null) ? 0 : (float) $pac;
        $calidad_num = ($calidad === 'NA' || $calidad === 'NR' || $calidad === null) ? 0 : (float) $calidad;
        $sst_num = ($sst === 'NA' || $sst === 'NR' || $sst === null) ? 0 : (float) $sst;
        $gsa_num = ($gsa === 'NA' || $gsa === 'NR' || $gsa === null) ? 0 : (float) $gsa;
        $adm_num = ($adm === 'NA' || $adm === 'NR' || $adm === null) ? 0 : (float) $adm;

        if ($calidad == 'NA' || $calidad == 'NR') {
            if ($sst == 'NA' || $sst == 'NR') {
                if ($gsa == 'NA' || $gsa == 'NR') {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.7 / 7) * 7);
                    } else {
                        return $pac * (0.3 + (0.6 / 4) * 3) + $adm * (0.1 + (0.6 / 4) * 1);
                    }
                } else {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.5 / 5) * 3) + $gsa * (0.2 + (0.5 / 5) * 2);
                    } else {
                        return $pac * (0.3 + (0.4 / 6) * 3) + $gsa * (0.2 + (0.4 / 6) * 2) + $adm * (0.1 + (0.4 / 6) * 1);
                    }
                }
            } else {
                if ($gsa == 'NA' || $gsa == 'NR') {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.5 / 5) * 3) + $sst_num * (0.2 + (0.5 / 5) * 2);
                    } else {
                        return $pac * (0.3 + (0.4 / 6) * 3) + $sst_num * (0.2 + (0.4 / 6) * 2) + $adm_num * (0.1 + (0.4 / 6) * 1);
                    }
                } else {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.3 / 7) * 3) + $sst_num * (0.2 + (0.3 / 7) * 2) + $gsa_num * (0.2 + (0.3 / 7) * 2);
                    } else {
                        return $pac * (0.3 + (0.2 / 8) * 3) + $sst_num * (0.2 + (0.2 / 8) * 2) + $gsa_num * (0.2 + (0.2 / 8) * 2) + $adm_num * (0.1 + (0.2 / 8) * 1);
                    }
                }
            }
        } else {
            if ($sst == 'NA' || $sst == 'NR') {
                if ($gsa == 'NA' || $gsa == 'NR') {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.5 / 5) * 3) + $calidad_num * (0.2 + (0.5 / 5) * 2);
                    } else {
                        return $pac * (0.3 + (0.4 / 6) * 3) + $calidad_num * (0.2 + (0.4 / 6) * 2) + $adm_num * (0.1 + (0.4 / 6) * 1);
                    }
                } else {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.3 / 7) * 3) + $calidad_num * (0.2 + (0.3 / 7) * 2) + $gsa_num * (0.2 + (0.3 / 7) * 2);
                    } else {
                        return $pac * (0.3 + (0.2 / 8) * 3) + $calidad_num * (0.2 + (0.2 / 8) * 2) + $gsa_num * (0.2 + (0.2 / 8) * 2) + $adm_num * (0.1 + (0.2 / 8) * 1);
                    }
                }
            } else {
                if ($gsa == 'NA' || $gsa == 'NR') {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.3 / 7) * 3) + $calidad_num * (0.2 + (0.3 / 7) * 2) + $sst_num * (0.2 + (0.3 / 7) * 2);
                    } else {
                        return $pac * (0.3 + (0.2 / 8) * 3) + $calidad_num * (0.2 + (0.2 / 8) * 2) + $sst_num * (0.2 + (0.2 / 8) * 2) + $adm_num * (0.1 + (0.2 / 8) * 1);
                    }
                } else {
                    if ($adm == 'NA' || $adm == 'NR') {
                        return $pac * (0.3 + (0.1 / 9) * 3) + $calidad_num * (0.2 + (0.1 / 9) * 2) + $sst_num * (0.2 + (0.1 / 9) * 2) + $gsa_num * (0.2 + (0.1 / 9) * 2);
                    } else {
                        return $pac * (0.3 + (0.0 / 10) * 3) + $calidad_num * (0.2 + (0.0 / 10) * 2) + $sst_num * (0.2 + (0.0 / 10) * 2) + $gsa_num * (0.2 + (0.0 / 10) * 2) + $adm_num * (0.1 + (0.0 / 10) * 1);
                    }
                }
            }
        }
    }

    private function calculatePACConsolidado($pac, $crit, $nocrit, $atr)
    {
        if ($crit != 'NA' && $nocrit != 'NA' && $atr != 'NA') {
            return round($pac * ($crit * 0.4 + $nocrit * 0.2 + $atr * 0.4), 3);
        }
        if ($crit != 'NA' && $nocrit != 'NA' && $atr == 'NA') {
            return round($pac * ($crit * 0.6667 + $nocrit * 0.3333), 3);
        }
        if ($crit != 'NA' && $nocrit == 'NA' && $atr != 'NA') {
            return round($pac * ($crit * 0.5 + $atr * 0.5), 3);
        }
        if ($crit == 'NA' && $nocrit != 'NA' && $atr != 'NA') {
            return round($pac * ($nocrit * 0.3333 + $atr * 0.6667), 3);
        }
        if ($crit != 'NA' && $nocrit == 'NA' && $atr == 'NA') {
            return round($pac * $crit, 3);
        }
        if ($crit == 'NA' && $nocrit != 'NA' && $atr == 'NA') {
            return round($pac * $nocrit, 3);
        }
        if ($crit == 'NA' && $nocrit == 'NA' && $atr != 'NA') {
            return round($pac * $atr, 3);
        }

        return 0;
    }
}
