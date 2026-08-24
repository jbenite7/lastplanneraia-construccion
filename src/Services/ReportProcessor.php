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
        $sql = "SELECT Id AS project_id, Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1";
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

        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$tableName],
        );

        if ((int) $stmt->fetchColumn() > 0) {
            return true;
        }

        foreach (TableResolver::getValidTables() as $globalTable) {
            if (str_ends_with($tableName, '_' . $globalTable)) {
                $stmt = $this->db->query(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                    [$globalTable],
                );

                return (int) $stmt->fetchColumn() > 0;
            }
        }

        return false;
    }

    private function reportTableHasProjectId(string $table): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'project_id'",
            [$table],
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    private function clearReportTable(string $table, bool $pdcOnly = false): void
    {
        $activeClause = $pdcOnly ? "" : " AND Activo=1";

        if ($this->reportTableHasProjectId($table)) {
            $this->db->query(
                "DELETE FROM {$table}
                 WHERE project_id IN (
                    SELECT Id FROM general_proyectos_procesos
                    WHERE Area='Construccion'{$activeClause}
                 )",
            );

            return;
        }

        $this->db->query(
            "DELETE FROM {$table}
             WHERE Proyecto IN (
                SELECT Proyecto_Proceso FROM general_proyectos_procesos
                WHERE Area='Construccion'{$activeClause}
             )",
        );
    }

    private function projectIdFromProjectRow(array $project): ?int
    {
        if (isset($project['project_id']) && $project['project_id'] !== null) {
            return (int) $project['project_id'];
        }

        return $this->pid($project['Base_de_Datos']);
    }

    public function generateCurvaS()
    {
        $this->db->logActivity('Sistema', 'GENERAR_CURVA_S', "Iniciando proceso de actualización masiva de Curvas S");
        $results = [];

        try {
            $this->clearReportTable('general_curvas');
            $this->clearReportTable('general_curvas_pdc', true);

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
                    $projectId = $this->projectIdFromProjectRow($data1);
                    $this->db->queryWithProject("SELECT 1 FROM {$tProgCons} LIMIT 1", [], $projectId);
                    $this->reportSubprocess('Curva S', $proyecto, 'Validando programa consolidado', 'ok');

                    // Calculate total weeks
                    $tSemanasActivas = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
                    $sqlSemanas = "SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM {$tProgCons} WHERE Semana = (SELECT MAX(Semana) FROM {$tSemanasActivas} WHERE project_id = ?) AND project_id = ?), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM {$tProgCons} WHERE project_id = ?";
                    $dataSemanasProyecto = $this->db->queryWithProject($sqlSemanas, [$projectId, $projectId, $projectId], $projectId)->fetch();
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
                            project_id, Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem,
                            diasCompletadosReal, diasCompletadosTeorico, diasCompletadosLineaBase,
                            diasTotales, diasTotalesLineaBase,
                            porcentajeCompletadoReal, porcentajeCompletadoTeorico, porcentajeCompletadoLineaBase,
                            diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoLineaBase
                        )
                        SELECT
                            ? AS project_id, ? AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto,
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
                                 FROM {$tProgCons} WHERE project_id = ? AND Semana = ? AND Titulo = 0) AS diasTotales
                            FROM {$tProgCons}
                            WHERE project_id = ? AND Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
                        ) AS tabla";

                        $this->db->queryWithProject($sqlInsertReal, [
                            $projectId, $proyecto, $semana, $fechaInicioSem, $fechaFinSem,
                            $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                            $projectId, $semana, $projectId, $semana,
                        ], $projectId);
                    }

                    $this->reportSubprocess('Curva S', $proyecto, 'Insertando semanas reales', 'ok', count($semanasActivas) . ' semanas');

                    // Process projected weeks
                    for ($i = ($ultimaSemanaActiva + 1); $i <= $semanasProyecto; $i++) {
                        $fechaInicioSem = date("Y-m-d", strtotime($ultimaFechaFinSem . "+ 1 days"));
                        $fechaFinSem = date("Y-m-d", strtotime($fechaInicioSem . "+ 6 days"));
                        $ultimaFechaFinSem = $fechaFinSem;

                        $sqlInsertProyectada = "INSERT INTO general_curvas (
                            project_id, Proyecto, fInicioProyecto, fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem,
                            diasCompletadosReal, diasCompletadosTeorico, diasCompletadosLineaBase,
                            diasTotales, diasTotalesLineaBase,
                            porcentajeCompletadoReal, porcentajeCompletadoTeorico, porcentajeCompletadoLineaBase,
                            diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoLineaBase
                        )
                        SELECT
                            ? AS project_id, ? AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto,
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
                                 FROM {$this->t($dbPrefix, 'programa_consolidado')} WHERE project_id = ? AND Semana = ? AND Titulo = 0) AS diasTotales
                            FROM {$this->t($dbPrefix, 'programa_consolidado')}
                            WHERE project_id = ? AND Titulo = 0 AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semana = ?
                        ) AS tabla";

                        $this->db->queryWithProject($sqlInsertProyectada, [
                            $projectId, $proyecto, $i, $fechaInicioSem, $fechaFinSem,
                            $fechaInicioSem, $fechaInicioSem, $fechaInicioSem,
                            $projectId, $ultimaSemanaActiva, $projectId, $ultimaSemanaActiva,
                        ], $projectId);
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
            $stmtCurvas = $this->db->query("SELECT * FROM general_curvas WHERE project_id IS NOT NULL ORDER BY project_id, Semana");
            $porcentajeAnterior = 0;
            $proyectoAnterior = null;
            while ($row = $stmtCurvas->fetch()) {
                if ($proyectoAnterior !== $row['project_id']) {
                    $porcentajeAnterior = 0;
                    $proyectoAnterior = $row['project_id'];
                }

                $porcentajeActual = (float) $row['porcentajeCompletadoTeorico'];

                $diferencia = $porcentajeActual - $porcentajeAnterior;
                $porcentajeAnterior = $porcentajeActual;

                $this->db->query("UPDATE general_curvas SET diferenciaPorcentajeCompletadoTeorico = ? WHERE id = ?", [$diferencia, $row['id']]);
            }

            $this->reportSubprocess('Curva S', '', 'Calculando diferencias', 'ok');
            $results[] = "Curva S - OK";

            // La Curva S del PDC v1 se retiró el 2026-08-04 con el módulo: se alimentaba de la
            // tabla `pdc`, eliminada. `general_curvas_pdc` conserva el histórico ya calculado.

            $this->db->logActivity('Sistema', 'GENERAR_CURVA_S', "Proceso de Curvas S completado con éxito");

            return ['success' => true, 'messages' => $results];

        } catch (Exception $e) {
            error_log("ReportProcessor::generateCurvaS Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateReporteGeneral()
    {
        $this->db->logActivity('Sistema', 'GENERAR_REPORTE_GRAL', "Iniciando generación de reporte consolidado general");
        $this->clearReportTable('general_informe_consolidado');

        $result = $this->getConstructionProjects();
        $messages = [];
        $totalGeneral = count($result);
        $genIdx = 0;

        foreach ($result as $data1) {
            // `$proyecto` se resuelve ANTES del try: el catch de abajo lo usa para el mensaje
            // de error, y estando dentro del try quedaba indefinido si fallaba su propia
            // asignacion — el manejador reventaba y TAPABA el error original.
            // Detectado por PHPStan 2.2 al subir de version (2026-08-08).
            $proyecto = (string) ($data1["Proyecto_Proceso"] ?? '(proyecto sin nombre)');
            try {
                $dbPrefix = $data1["Base_de_Datos"];
                $genIdx++;

                if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $messages)) {
                    $this->reportProgress('General', $proyecto, $genIdx, $totalGeneral, 'skip');
                    continue;
                }

                $projectId = $this->projectIdFromProjectRow($data1);
                $tPS = $this->t($dbPrefix, 'programacion_semanal');
                $tSA595 = $this->t($dbPrefix, 'semanas_activas');
                $sqlInsert = "INSERT INTO general_informe_consolidado (
                project_id, Proyecto, Semana, maxSemana, Proyecto_maxSemana, Actividad,
                Fecha_Inicio, Fecha_Fin, Fecha_Inicio_Sem, Fecha_Fin_Sem,
                Critica, Atrasada, Activa, Ejecutado, cantidad_ppto,
                Cantidad_Sugerida, Compromiso, Unidad, Ejecutado_Real,
                PAC, P_Completado, Categoria_CNP, CNP, Observaciones_CNP,
                Categoria_CNC, CNC, Observaciones_CNC, Responsable_AIA, Sub_Contratista
            )
            SELECT
                ? AS project_id,
                ? AS Proyecto,
                prog.Semana,
                (SELECT MAX(Semana) FROM {$tPS} WHERE project_id = ?) AS maxSemana,
                CONCAT(?, ' (', (SELECT Fecha_Fin_Sem FROM {$tSA595} WHERE Semana = (SELECT MAX(Semana) FROM {$tPS} WHERE project_id = ?) AND project_id = ?), ')') AS Proyecto_maxSemana,
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
            FROM {$tPS} AS prog
            LEFT JOIN {$tSA595} AS sem ON sem.Semana = prog.Semana AND sem.project_id = prog.project_id
            WHERE prog.Semana >= ((SELECT MAX(Semana) FROM {$tPS} WHERE project_id = ?) - 1)
              AND prog.project_id = ?";

                $this->db->queryWithProject($sqlInsert, [$projectId, $proyecto, $projectId, $proyecto, $projectId, $projectId, $projectId, $projectId], $projectId);
                $this->reportSubprocess('General', $proyecto, 'Insertando informe consolidado', 'ok');
                $this->db->query("DELETE FROM general_informe_consolidado WHERE project_id = ? AND (Fecha_Inicio_Sem IS NULL OR Fecha_Fin_Sem IS NULL)", [$projectId]);
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
        $this->clearReportTable('general_informe_restricciones_consolidado');
        $proyectos = $this->getConstructionProjects();
        $messages = ["Liberación de Restricciones - OK"];
        $totalRest = count($proyectos);
        $restIdx = 0;

        foreach ($proyectos as $data1) {
            // `$proyecto` se resuelve ANTES del try: el catch de abajo lo usa para el mensaje
            // de error, y estando dentro del try quedaba indefinido si fallaba su propia
            // asignacion — el manejador reventaba y TAPABA el error original.
            // Detectado por PHPStan 2.2 al subir de version (2026-08-08).
            $proyecto = (string) ($data1["Proyecto_Proceso"] ?? '(proyecto sin nombre)');
            try {
                $dbPrefix = $data1["Base_de_Datos"];
                $restIdx++;

                if ($this->collectInvalidDbPrefixMessage($dbPrefix, $proyecto, $messages)) {
                    $this->reportProgress('Restricciones', $proyecto, $restIdx, $totalRest, 'skip');
                    continue;
                }

                // Resolve restriction config per project
                $projectId = $this->projectIdFromProjectRow($data1);
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
                    project_id, Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Actividad,
                    Fecha_Inicio, Fecha_Fin, Semanas_Inicio, Restriccion, valorRestriccion, estadoActividad
                )
                SELECT
                    ? AS project_id,
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
                LEFT JOIN {$this->t($dbPrefix, 'semanas_activas')} AS sem ON sem.Semana = prog.Semana AND sem.project_id = prog.project_id
                WHERE prog.project_id = ?
                  AND prog.{$columna} != 'N/A'
                  AND prog.Titulo = 0
                  AND prog.Actividad IS NOT NULL
                  AND prog.Semanas_Inicio < 7
                  AND prog.Ejecutado < 1
                  AND prog.Semana >= ((SELECT MAX(Semana) FROM {$this->t($dbPrefix, 'programa_consolidado')} WHERE project_id = ?) - 3)
                  AND sem.Fecha_Inicio_Sem IS NOT NULL";

                    $this->db->queryWithProject($sqlInsert, [$projectId, $proyecto, $nombreLabel, $projectId, $projectId], $projectId);
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
    /**
     * El informe consolidado del PDC v1 se retiró el 2026-08-04 junto con el módulo: leía la
     * tabla `pdc`, eliminada. El método se conserva como no-op para no romper a sus llamadores
     * (`/reportes/pdc` y `run-all`) mientras el Plan de Compras v2 no publique su propio informe.
     * `general_informe_pdc` y `general_curvas_pdc` NO se borraron: guardan el histórico ya generado.
     */
    public function generateReportePDC()
    {
        return ['success' => true, 'messages' => ['El informe del PDC v1 se retiró con el módulo el 2026-08-04.']];
    }

    public function generateReporteSubcontratistas()
    {
        $this->clearReportTable('general_informe_subcontratistas');
        $proyectos = $this->getConstructionProjects();
        $messages = [];
        $totalSub = count($proyectos);
        $subIdx = 0;

        foreach ($proyectos as $proyectoData) {
            // `$proyecto` se resuelve ANTES del try: el catch de abajo lo usa para el mensaje
            // de error, y estando dentro del try quedaba indefinido si fallaba su propia
            // asignacion — el manejador reventaba y TAPABA el error original.
            // Detectado por PHPStan 2.2 al subir de version (2026-08-08).
            $proyecto = (string) ($proyectoData["Proyecto_Proceso"] ?? '(proyecto sin nombre)');
            try {
                $base_de_datos = $proyectoData["Base_de_Datos"];
                $projectId = $this->projectIdFromProjectRow($proyectoData);
                $subIdx++;

                if (!$this->isValidDbPrefix($base_de_datos)) {
                    error_log("Nombre de base de datos no válido encontrado: " . $base_de_datos);
                    $messages[] = "$proyecto - Error: Nombre de base de datos inválido.";
                    $this->reportProgress('Subcontratistas', $proyecto, $subIdx, $totalSub, 'skip');
                    continue;
                }

                $sql = "INSERT INTO general_informe_subcontratistas (
                `project_id`, `Proyecto`, `Semana`, `maxSemana`, `Proyecto_maxSemana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`,
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
                ?,
                cic.Semana,
                (SELECT MAX(cic_inner.Semana) FROM {$this->t($base_de_datos, 'cic')} cic_inner WHERE cic_inner.project_id = ?),
                CONCAT(?, ' (', (SELECT sa_inner.Fecha_Fin_Sem FROM {$this->t($base_de_datos, 'semanas_activas')} sa_inner WHERE sa_inner.project_id = ? AND sa_inner.Semana = (SELECT MAX(cic_inner.Semana) FROM {$this->t($base_de_datos, 'cic')} cic_inner WHERE cic_inner.project_id = ?)), ')'),
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
            LEFT JOIN {$this->t($base_de_datos, 'semanas_activas')} sa ON sa.Semana = cic.Semana AND sa.project_id = cic.project_id
            WHERE cic.project_id = ?";

                $this->db->queryWithProject($sql, [$projectId, $proyecto, $projectId, $proyecto, $projectId, $projectId, $projectId], $projectId);

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
                // `cic` se puebla cada semana porque CicApiController::list() corre un
                // "sync loop" (`for ($s = 1; $s <= $semana; $s++)`) cada vez que alguien
                // abre la pantalla de Calificación Integral Contratistas — por eso acumula
                // filas de semanas pasadas aunque este método (`updateCICProyectos`) solo
                // procese la semana activa. `cip` no tiene esa pantalla equivalente
                // (no existe un CipApiController), así que aquí es el único lugar que la
                // puebla; por eso replicamos el mismo backfill 1..semanaProyecto solo para
                // `cip`, sin tocar el bloque de `cic`. Detectado 2026-08-24 (Task 1,
                // control-tower-f0-higiene-datos): con una sola llamada a la semana activa,
                // `cip` solo captura responsables cuyo PAC ya estaba marcado justo en el
                // instante del run — casi nunca pasa — y por eso la tabla quedaba vacía.
                for ($semanaCip = 1; $semanaCip <= $semanaProyecto; $semanaCip++) {
                    $this->reportSubprocess('CIC', $proyecto, 'Procesando CIP profesionales', 'running');
                    $this->processCalificacionEntidad(
                        $proyecto,
                        $dbName,
                        $semanaCip,
                        'cip',
                        'profesional',
                        'CIC Profesionales',
                        [$this, 'updatePACProfesionales'],
                        [$this, 'generateProfesionales'],
                        $messages,
                    );
                }

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
                // cic.Id no es auto_increment: es una secuencia por proyecto (mismo patrón que
                // CicApiController); sin él, el INSERT muere en modo estricto.
                $nextId = (int) $this->db->queryWithProject("SELECT COALESCE(MAX(Id), 0) + 1 FROM {$this->t($dbName, 'cic')} WHERE project_id = ?", [$this->pid($dbName) ?? 0], $this->pid($dbName))->fetchColumn();
                $sql = "INSERT INTO {$this->t($dbName, 'cic')} (Id, Semana, subcontratista) VALUES (?, ?, ?)";
                [$sql, $params] = $this->db->insertProjectId($sql, $this->pid($dbName) ?? 0, [$nextId, $semana, $subcontratista]);
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
                // cip.Id tampoco es auto_increment, y a diferencia de cic su PRIMARY KEY es
                // global (solo Id, sin project_id): la secuencia se calcula sobre toda la tabla.
                $nextId = (int) $this->db->query("SELECT COALESCE(MAX(Id), 0) + 1 FROM {$this->t($dbName, 'cip')}")->fetchColumn();
                $sql = "INSERT INTO {$this->t($dbName, 'cip')} (Id, Semana, profesional) VALUES (?, ?, ?)";
                [$sql, $params] = $this->db->insertProjectId($sql, $this->pid($dbName) ?? 0, [$nextId, $semana, $profesional]);
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
                            INNER JOIN {$this->t($dbName, 'subcontratistas')} sub
                                ON cic.subcontratista = sub.subcontratista
                               AND sub.project_id = cic.project_id
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
                            INNER JOIN {$this->t($dbName, 'profesionales')} prof
                                ON cip.profesional = prof.nombre
                               AND prof.project_id = cip.project_id
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
        $projectId = $this->pid($dbName);
        $stmtCic = $this->db->queryWithProject("SELECT Id, subcontratista, PAC, Calidad, ADM, GSA, SST FROM {$this->t($dbName, 'cic')} WHERE Semana = ?", [$semana], $projectId);
        $cicRows = $stmtCic->fetchAll();

        foreach ($cicRows as $cic) {
            $id = $cic['Id'];
            $subcontratista = $cic['subcontratista'];

            $queryAcum = "SELECT
                (SELECT ROUND(AVG(PAC), 3) FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND PAC != 'NA') AS PAC_Acum,
                (SELECT ROUND(AVG(P_Completado), 3) FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND P_Completado != 'NA') AS P_Completado_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Calidad), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND Calidad NOT IN ('NA', 'NR')) AS Calidad_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(GSA), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND GSA NOT IN ('NA', 'NR')) AS GSA_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(SST), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND SST NOT IN ('NA', 'NR')) AS SST_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(ADM), 3) END FROM {$this->t($dbName, 'cic')} WHERE Semana <= ? AND subcontratista = ? AND project_id = ? AND ADM NOT IN ('NA', 'NR')) AS ADM_Acum
                FROM DUAL";

            // Params duplicated due to named placeholders limitation in raw copy-paste or simple positional mapping
            // Using positional params strictly
            $paramsAcum = [
                $semana, $subcontratista, $projectId,
                $semana, $subcontratista, $projectId,
                $semana, $subcontratista, $projectId,
                $semana, $subcontratista, $projectId,
                $semana, $subcontratista, $projectId,
                $semana, $subcontratista, $projectId,
            ];

            $stmtAcum = $this->db->queryWithProject($queryAcum, $paramsAcum, $projectId);
            $acum = $stmtAcum->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cic')} SET
                            PAC_Acum = ?, P_Completado_Acum = ?, Calidad_Acum = ?,
                            GSA_Acum = ?, SST_Acum = ?, ADM_Acum = ?
                          WHERE Id = ?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Calidad_Acum'],
                $acum['GSA_Acum'], $acum['SST_Acum'], $acum['ADM_Acum'], $id,
            ], $projectId);

            $cal_integral = $this->calculateLogicaIntegral($cic['PAC'] ?? 'NA', $cic['Calidad'] ?? 'NA', $cic['SST'] ?? 'NA', $cic['GSA'] ?? 'NA', $cic['ADM'] ?? 'NA');
            $cal_integral_val = is_numeric($cal_integral) ? (float) $cal_integral : 0.0;

            $cal_integral_acum = $this->calculateLogicaIntegral($acum['PAC_Acum'] ?? 'NA', $acum['Calidad_Acum'] ?? 'NA', $acum['SST_Acum'] ?? 'NA', $acum['GSA_Acum'] ?? 'NA', $acum['ADM_Acum'] ?? 'NA');
            $cal_integral_acum_val = is_numeric($cal_integral_acum) ? (float) $cal_integral_acum : 0.0;

            $cal_integral_str = $cal_integral_val ? number_format($cal_integral_val, 3, '.', '') : '0';
            $cal_integral_acum_str = $cal_integral_acum_val ? number_format($cal_integral_acum_val, 3, '.', '') : '0';

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cic')} SET Cal_Integral = ?, Cal_Integral_Acum = ? WHERE Id = ?", [
                $cal_integral_str, $cal_integral_acum_str, $id,
            ], $projectId);
        }
    }

    private function updateIntegralProfesionales($semana, $dbName)
    {
        $projectId = $this->pid($dbName);
        $stmtCip = $this->db->queryWithProject("SELECT profesional, PAC FROM {$this->t($dbName, 'cip')} WHERE Semana = ?", [$semana], $projectId);
        $cipRows = $stmtCip->fetchAll();

        foreach ($cipRows as $cip) {
            $profesional = $cip['profesional'];

            $queryStats = "SELECT
                (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND project_id = ? AND Activa = 1 AND Critica = 1 AND Atrasada = 0) AS Act_Criticas_Cumplidas,
                (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND project_id = ? AND (Activa = 1 OR Activa = 'NA') AND Critica = 0 AND Atrasada = 0) AS Act_No_Criticas_Cumplidas,
                (SELECT CASE WHEN COUNT(Atrasada) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Atrasada), 3) ELSE 'NA' END
                 FROM {$this->t($dbName, 'programacion_semanal')} WHERE Semana = ? AND Responsable_AIA = ? AND project_id = ? AND Activa = 1 AND Atrasada = 1) AS Act_Atrasadas_Cumplidas
                FROM DUAL";

            $stmtStats = $this->db->queryWithProject($queryStats, [
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
            ], $projectId);
            $stats = $stmtStats->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cip')} SET
                            Act_Criticas_Cumplidas = ?, Act_No_Criticas_Cumplidas = ?, Act_Atrasadas_Cumplidas = ?
                          WHERE profesional = ? AND Semana = ?", [
                $stats['Act_Criticas_Cumplidas'], $stats['Act_No_Criticas_Cumplidas'],
                $stats['Act_Atrasadas_Cumplidas'], $profesional, $semana,
            ], $projectId);

            $queryAcum = "SELECT
                (SELECT ROUND(AVG(PAC), 3) FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND project_id = ? AND PAC != 'NA') AS PAC_Acum,
                (SELECT ROUND(AVG(P_Completado), 3) FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND project_id = ? AND P_Completado != 'NA') AS P_Completado_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Criticas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND project_id = ? AND Act_Criticas_Cumplidas != 'NA') AS Act_Criticas_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_No_Criticas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND project_id = ? AND Act_No_Criticas_Cumplidas != 'NA') AS Act_No_Criticas_Acum,
                (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Atrasadas_Cumplidas), 3) END FROM {$this->t($dbName, 'cip')} WHERE Semana <= ? AND profesional = ? AND project_id = ? AND Act_Atrasadas_Cumplidas != 'NA') AS Act_Atrasadas_Acum
                FROM DUAL";

            $paramsAcum = [
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
                $semana, $profesional, $projectId,
            ];
            $stmtAcum = $this->db->queryWithProject($queryAcum, $paramsAcum, $projectId);
            $acum = $stmtAcum->fetch();

            $this->db->queryWithProject("UPDATE {$this->t($dbName, 'cip')} SET
                            PAC_Acum = ?, P_Completado_Acum = ?,
                            Act_Criticas_Cumplidas_Acum = ?, Act_No_Criticas_Cumplidas_Acum = ?, Act_Atrasadas_Cumplidas_Acum = ?
                          WHERE profesional = ? AND Semana = ?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Act_Criticas_Acum'],
                $acum['Act_No_Criticas_Acum'], $acum['Act_Atrasadas_Acum'], $profesional, $semana,
            ], $projectId);

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
            ], $projectId);
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
        // Las columnas de `cip` son texto y mezclan numeros con 'NA', 'NR', NULL y vacios;
        // en PHP 8 multiplicar cadenas no numericas es TypeError, asi que todo lo no
        // numerico se colapsa a 'NA' y un PAC no numerico consolida en 0.
        $pac = is_numeric($pac) ? (float) $pac : null;
        $crit = is_numeric($crit) ? (float) $crit : 'NA';
        $nocrit = is_numeric($nocrit) ? (float) $nocrit : 'NA';
        $atr = is_numeric($atr) ? (float) $atr : 'NA';
        if ($pac === null) {
            return 0;
        }

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
