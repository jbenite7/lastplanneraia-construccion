<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Lps\LpsService;
use App\Services\ProgramaConsolidadoNormalizationService;
use App\Services\ActivityMatcherService;
use App\Services\WeeklyRealProgressCarryoverService;
use PDO;
use Exception;
use TableResolver;

class GeneralApiController extends BaseController
{
    private LpsService $lpsService;

    public function __construct()
    {
        parent::__construct();
        $this->lpsService = new LpsService();
    }
    /**
     * API: Lista actividades del Programa General con filtros de estado.
     */
    public function list()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.ver');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semanaParam = $_GET['semana_objetivo'] ?? $_GET['semana'] ?? null;
            $semana = $semanaParam !== null ? filter_var($semanaParam, FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            // 2. Construir Filtros
            $conditions = [];

            if (!empty($_GET["activa_lookahead"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'Actividad Futura' OR Estado = 'En Liberación de Restricciones'))";
            }
            if (!empty($_GET["activa_no_iniciadas"])) {
                $conditions[] = "(Titulo = 0 AND Estado = 'Debe Iniciar')";
            }
            if (!empty($_GET["activa_a_tiempo"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'En Curso' OR Estado = 'A Tiempo'))";
            }
            if (!empty($_GET["activa_atrasadas"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'Atrasada' OR Estado = 'Ya Debió Iniciar y Restricciones Pendientes'))";
            }
            if (!empty($_GET["activa_terminadas"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'Terminada' OR Estado = 'Terminada Antes'))";
            }

            $sqlFilter = "";
            if (!empty($conditions)) {
                $sqlFilter = " AND (" . implode(" OR ", $conditions) . ")";
            }

            if (isset($_GET['filter']) && $_GET['filter'] === 'unmapped') {
                $sqlFilter .= " AND programaAnteriorAsociar = '*No Asociada*' ";
            }

            if (!empty($_GET['exclude_chapters'])) {
                $sqlFilter .= " AND Titulo = 0 ";
            }

            // 3. Obtener Fechas de la Semana
            $stmtFechas = $this->db->queryWithProject("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ? LIMIT 1", [$semana]);
            $fechasSemana = $stmtFechas->fetch(PDO::FETCH_ASSOC);

            $fechaInicioSemana = $fechasSemana['Fecha_Inicio_Sem'] ?? date('Y-m-d');
            $fechaInicioSemanaTs = $this->lpsService->toTimestamp($fechaInicioSemana);

            // 4. Consulta Principal
            $sql = "SELECT * 
                    FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " 
                    WHERE Semana = ? 
                    $sqlFilter 
                    ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";

            $stmt = $this->db->queryWithProject($sql, [$semana]);
            $data = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $unidad = trim((string) ($row['unidad'] ?? ''));
                if ($unidad === '') {
                    $unidad = '%';
                }
                $row['unidad'] = $unidad;
                if ($unidad === '%') {
                    $row['cantidad_ppto'] = null;
                }

                if ($row['Titulo'] == 1) {
                    $row['Estado'] = "Capítulo";
                    $row['boton'] = "No Boton";
                    $row['Ejecutado_Teorico'] = null;
                } else {
                    $row['boton'] = "Boton";
                    $row['Ejecutado_Teorico'] = $this->lpsService->calculateTheoreticalProgress(
                        $row['Fecha_Inicio'] ?? null,
                        $row['Fecha_Fin'] ?? null,
                        $fechaInicioSemanaTs,
                    );
                }
                $data[] = $row;
            }

            echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * API: Actualiza una actividad individual en el Programa General.
     */
    public function update()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.editar');
        header('Content-Type: application/json; charset=utf-8');

        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->lpsService->disableProductivityMeasurementTemporarily($this->db);

            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            $semanaParam = $_GET['semana_objetivo'] ?? $_GET['semana'] ?? null;
            $semana = $semanaParam !== null ? filter_var($semanaParam, FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);
            $id = $_POST['Consecutivo_en_Programa'] ?? $_POST['Id'] ?? null;

            if (($id === null || $id === '') || !$semana) {
                error_log("🔥 [DebugUpdate] Faltan parámetros. ID: " . var_export($id, true) . ", Semana: " . var_export($semana, true));
                throw new Exception("Faltan parámetros requeridos (Id, Semana).");
            }

            $checkStmt = $this->db->queryWithProject("SELECT Titulo FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$id, $semana]);
            $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingRow) {
                throw new Exception("No se encontró la actividad en Programa General.");
            }

            if ($existingRow['Titulo'] == 1) {
                throw new Exception("No se puede editar un capítulo directamente. Use las actividades hijas.");
            }

            $rawInput = $_POST["Ejecutado"] ?? null;
            $ejecutadoVisible = $this->lpsService->toFloat($rawInput);
            $ejecutadoRatioInput = $this->lpsService->toFloat($_POST["EjecutadoRatio"] ?? null, null);
            $codigoActividad = $_POST["codigo_actividad"] ?? '';
            $unidadRaw = trim($_POST["unidad"] ?? '');
            $unidad = ($unidadRaw === '') ? '%' : $unidadRaw;
            $cantidadPpto = $this->lpsService->toFloat($_POST["cantidad_ppto"] ?? null);

            if ($cantidadPpto !== null) {
                if ($cantidadPpto < 0) {
                    throw new Exception("La cantidad en presupuesto no puede ser negativa.");
                }
                $cantidadPpto = round($cantidadPpto, 1);
                if ($cantidadPpto === 0.0) {
                    $cantidadPpto = null;
                }
            }

            if ($unidad === '%') {
                $cantidadPpto = null;
            }

            $ejecutado = $ejecutadoRatioInput;
            if ($ejecutado === null && $ejecutadoVisible !== null) {
                if ($unidad !== '%' && ($cantidadPpto ?? 0) > 0) {
                    $ejecutado = ($ejecutadoVisible / $cantidadPpto);
                } else {
                    $ejecutado = ($ejecutadoVisible / 100);
                }
            }

            if ($ejecutado !== null) {
                if ($ejecutado < -0.0001 || $ejecutado > 1.0001) {
                    $pctDisplay = round($ejecutado * 100, 2);
                    throw new Exception("El valor resultante ({$pctDisplay}%) excede el rango permitido (0-100%).");
                }
                $ejecutado = round($ejecutado, 6);
            }

            $fechaInicioRaw = $_POST["Fecha_Inicio"] ?? '';
            $fechaFinRaw = $_POST["Fecha_Fin"] ?? '';
            $fechaInicio = !empty($fechaInicioRaw) ? date("Y-m-d", strtotime($fechaInicioRaw)) : null;
            $fechaFin = !empty($fechaFinRaw) ? date("Y-m-d", strtotime($fechaFinRaw)) : null;

            // 3. Productividad (Legacy: forzado a 0)
            $medirProductividad = 0;

            $actividadAsociar = $_POST['actividadAsociar'] ?? null;

            $auditStmt = $this->db->queryWithProject("SELECT Ejecutado, Ejecutado_Siguiente_Semana, Estado, Titulo, unidad, cantidad_ppto FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1", [$id, $semana]);
            $auditBefore = $auditStmt->fetch(PDO::FETCH_ASSOC);
            $auditStartTime = microtime(true);
            error_log("[PGAudit] INICIO | usuario={$vars['user']} | db={$dbPrefix} | semana={$semana} | id={$id} | Titulo={$auditBefore['Titulo']} | Ejecutado_antes={$auditBefore['Ejecutado']} | EjecSigSem_antes={$auditBefore['Ejecutado_Siguiente_Semana']} | Estado_antes={$auditBefore['Estado']} | unidad={$auditBefore['unidad']} | cantidad_ppto={$auditBefore['cantidad_ppto']} | POST_Ejecutado={$rawInput} | POST_EjecutadoRatio=" . ($_POST["EjecutadoRatio"] ?? 'null') . " | POST_unidad={$unidadRaw} | POST_cantidad_ppto=" . ($_POST["cantidad_ppto"] ?? 'null') . " | ejecutado_calculado={$ejecutado}");

            // 4. Update Principal (Incluyendo Mapeo)
            $sql = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET 
                    Activa = 1,
                    Ejecutado = ?, 
                    medir_productividad = ?, 
                    unidad = ?, 
                    cantidad_ppto = ?, 
                    codigo_actividad = ?, 
                    Ejecutado_Siguiente_Semana = ?, 
                    Fecha_Inicio = ?, 
                    Fecha_Fin = ?,
                    programaAnteriorAsociar = ?
                    WHERE Consecutivo_en_Programa = ? AND Semana = ?";

            $updateStmt = $this->db->queryWithProject($sql, [ $ejecutado, $medirProductividad, $unidad, $cantidadPpto, $codigoActividad, $ejecutado, $fechaInicio, $fechaFin, $actividadAsociar, $id, $semana, ]);

            $verifyStmt = $this->db->queryWithProject("SELECT unidad, cantidad_ppto, Ejecutado FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1", [$id, $semana]);
            $updatedRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$updatedRow) {
                throw new Exception("No se encontró la actividad a actualizar en Programa General.");
            }

            $unidad = trim((string) ($updatedRow['unidad'] ?? ''));
            if ($unidad === '') {
                $unidad = '%';
            }
            $cantidadPpto = $this->lpsService->toFloat($updatedRow['cantidad_ppto'] ?? null);
            $ejecutado = $this->lpsService->toFloat($updatedRow['Ejecutado'] ?? null);

            $expectedUnidad = ($unidadRaw === '') ? '%' : $unidadRaw;
            $expectedCantidadPpto = $this->lpsService->toFloat($_POST["cantidad_ppto"] ?? null);
            if ($expectedCantidadPpto !== null) {
                $expectedCantidadPpto = round($expectedCantidadPpto, 1);
                if ($expectedCantidadPpto === 0.0) {
                    $expectedCantidadPpto = null;
                }
            }
            if ($expectedUnidad === '%') {
                $expectedCantidadPpto = null;
            }

            $expectedRatio = $ejecutadoRatioInput;
            if ($expectedRatio === null && $ejecutadoVisible !== null) {
                if ($expectedUnidad !== '%' && ($expectedCantidadPpto ?? 0) > 0) {
                    $expectedRatio = $ejecutadoVisible / $expectedCantidadPpto;
                } else {
                    $expectedRatio = $ejecutadoVisible / 100;
                }
                $expectedRatio = round($expectedRatio, 6);
            }

            $cantidadMatches = ($cantidadPpto === null && $expectedCantidadPpto === null)
                || ($cantidadPpto !== null && $expectedCantidadPpto !== null && abs($cantidadPpto - $expectedCantidadPpto) < 0.0001);
            $ejecutadoMatches = ($ejecutado === null && $expectedRatio === null)
                || ($ejecutado !== null && $expectedRatio !== null && abs($ejecutado - $expectedRatio) < 0.0001);

            if ($unidad !== $expectedUnidad || !$cantidadMatches || !$ejecutadoMatches) {
                throw new Exception("No fue posible persistir completamente el cambio solicitado en Programa General.");
            }

            // 5. Herencia Manual (Mapeo Manual LPS)
            if (!empty($_POST['editarActividadAsociar']) && !empty($actividadAsociar) && $actividadAsociar !== '*No Asociada*') {
                $semanaAnterior = $semana - 1;
                $historico = $this->getPreviousWeekData($dbPrefix, $semanaAnterior);
                $keyBusqueda = trim($actividadAsociar);
                $dataHerencia = $historico[$keyBusqueda] ?? null;

                if ($dataHerencia) {
                    $sqlApply = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET 
                                    Responsable_AIA = ?, Sub_Contratista = ?, Observaciones = ?, codigo_actividad = ?, 
                                    medir_productividad = ?, cantidad_ppto = ?, unidad = ?, 
                                    Estado_Restricciones = ?, D_y_E = ?, Materiales = ?, MdeO = ?, Equipos = ?, 
                                    Predecesora = ?, Pdto_Cons = ?, Modelo = ?, Ejecutado = ?
                                 WHERE Consecutivo_en_Programa = ? AND Semana = ?";
                    $this->db->queryWithProject($sqlApply, [
                        $dataHerencia['Responsable_AIA'], $dataHerencia['Sub_Contratista'], $dataHerencia['Observaciones'], $dataHerencia['codigo_actividad'],
                        $dataHerencia['medir_productividad'], $dataHerencia['cantidad_ppto'], $dataHerencia['unidad'],
                        $dataHerencia['Estado_Restricciones'], $dataHerencia['D_y_E'], $dataHerencia['Materiales'], $dataHerencia['MdeO'], $dataHerencia['Equipos'],
                        $dataHerencia['Predecesora'], $dataHerencia['Pdto_Cons'], $dataHerencia['Modelo'], $dataHerencia['Ejecutado'], $id, $semana,
                    ]);

                    $ejecutado = $dataHerencia['Ejecutado'];
                    $unidad = $dataHerencia['unidad'];
                }
            }

            // 6. Recalcular Estado
            $ctxStmt = $this->db->queryWithProject("SELECT Titulo FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$id, $semana]);
            $row = $ctxStmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception("Error post-update: Registro no encontrado.");
            }

            $stmtFechas = $this->db->queryWithProject("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ?", [$semana]);
            $inicioSemanaRow = $stmtFechas->fetch(PDO::FETCH_ASSOC);

            $fechaCorte = $inicioSemanaRow['Fecha_Inicio_Sem'] ?? date('Y-m-d');
            $fechaFinSemana = $inicioSemanaRow['Fecha_Fin_Sem'] ?? null;

            $nuevoEstado = $this->lpsService->calculateGeneralStatus($row['Titulo'], $ejecutado, $fechaInicio, $fechaFin, $fechaCorte, $fechaFinSemana);
            $semanasInicio = $this->lpsService->toTimestamp($fechaInicio) !== null ? round(($this->lpsService->toTimestamp($fechaInicio) - $this->lpsService->toTimestamp($fechaCorte)) / (7 * 86400)) : null;

            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET Estado = ?, Semanas_Inicio = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$nuevoEstado, $semanasInicio, $id, $semana]);

            $auditEndTime = microtime(true);
            $auditDuration = round(($auditEndTime - $auditStartTime) * 1000, 2);
            $auditStmt2 = $this->db->queryWithProject("SELECT Ejecutado, Ejecutado_Siguiente_Semana, Estado FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1", [$id, $semana]);
            $auditAfter = $auditStmt2->fetch(PDO::FETCH_ASSOC);
            error_log("[PGAudit] FINAL | usuario={$vars['user']} | semana={$semana} | id={$id} | Ejecutado_despues={$auditAfter['Ejecutado']} | EjecSigSem_despues={$auditAfter['Ejecutado_Siguiente_Semana']} | Estado_despues={$auditAfter['Estado']} | duracion_ms={$auditDuration}");

            $normalizationService = new ProgramaConsolidadoNormalizationService($this->db);
            $normalizationService->normalizeChapters($dbPrefix, $semana);

            $response = [
                'respuesta' => 'BIEN',
                'estado' => $nuevoEstado,
                'Semanas_Inicio' => $semanasInicio,
                'unidad' => $unidad,
                'cantidad_ppto' => $cantidadPpto,
                'Ejecutado' => $ejecutado, // Retornamos el ratio decimal calculado
            ];

            // Si hubo herencia, devolvemos los campos actualizados (prioridad sobre el input manual)
            if (!empty($_POST['editarActividadAsociar']) && !empty($actividadAsociar) && $actividadAsociar !== '*No Asociada*') {
                $inheritanceFields = "unidad, cantidad_ppto, Ejecutado, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo";
                $inheritanceStmt = $this->db->queryWithProject("SELECT $inheritanceFields FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$id, $semana]);
                $inheritedData = $inheritanceStmt->fetch(PDO::FETCH_ASSOC);
                if ($inheritedData) {
                    foreach ($inheritedData as $k => $v) {
                        $response[$k] = $v;
                    }
                }
            }

            echo json_encode($response);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'respuesta' => 'ERROR', 'error' => $e->getMessage(), 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * API: Actualización masiva de estados y semanas de inicio.
     * Consume avances de PS de la semana anterior (N-1) y los aplica al consolidado
     * de la semana actual (N) vía WeeklyRealProgressCarryoverService.
     */
    public function updateBatch()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.editar');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = isset($_GET['semana']) ? filter_var($_GET['semana'], FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix) || $semana <= 0) {
                throw new Exception('Parámetros inválidos.');
            }

            $stmtFecha = $this->db->queryWithProject("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ? LIMIT 1", [$semana]);
            $dataSemana = $stmtFecha->fetch(PDO::FETCH_ASSOC);

            if (!$dataSemana || empty($dataSemana['Fecha_Inicio_Sem'])) {
                throw new Exception('No se encontró la semana activa para recalcular estados.');
            }

            $inicioSemana = $dataSemana['Fecha_Inicio_Sem'];
            $finSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;

            // 1) Consumir avances de PS semana N-1 hacia PG semana N (semántica legacy).
            //    El carryover service usa programaAnteriorAsociar con normalización
            //    (strip_tags + lowercase) por lo que también maneja el caso en que
            //    el cronograma fue actualizado (IDs/nombres cambiaron).
            $carryoverActualizadas = 0;
            if ($semana > 1) {
                $carryoverService = new WeeklyRealProgressCarryoverService($this->db, $this->lpsService);
                $carryoverResult = $carryoverService->syncWeek($dbPrefix, $semana - 1, $semana);
                $carryoverActualizadas = (int) ($carryoverResult['updatedRows'] ?? 0);
                $updatedProgramIds = $carryoverResult['updatedProgramIds'] ?? [];
                if (!empty($updatedProgramIds)) {
                    $this->refreshGeneralStatuses($dbPrefix, $semana, $updatedProgramIds);
                }
            }

            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET Activa = 1 WHERE Semana = ?", [$semana]);
            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET Estado = 'Capítulo' WHERE Semana = ? AND Titulo = 1", [$semana]);

            $rows = $this->db->queryWithProject("SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Semana = ? AND Titulo = 0", [$semana]);
            $activities = $rows->fetchAll(PDO::FETCH_ASSOC);

            $actualizadas = 0;
            $updateStmt = $this->db->prepareWithProject("UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET Estado = ?, Semanas_Inicio = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?");
            $pid = $this->db->getCurrentProjectId() ?? TableResolver::getProjectIdByPrefix($dbPrefix);

            foreach ($activities as $row) {
                $estadoNuevo = $this->lpsService->calculateGeneralStatus($row['Titulo'], $row['Ejecutado'] ?? 0, $row['Fecha_Inicio'], $row['Fecha_Fin'], $inicioSemana, $finSemana);
                $semanasInicio = $this->lpsService->toTimestamp($row['Fecha_Inicio']) !== null ? round(($this->lpsService->toTimestamp($row['Fecha_Inicio']) - $this->lpsService->toTimestamp($inicioSemana)) / (7 * 86400)) : null;
                $updateStmt->execute([$estadoNuevo, $semanasInicio, $row['Consecutivo_en_Programa'], $semana, $pid]);
                $actualizadas++;
            }

            echo json_encode([
                'respuesta' => 'BIEN',
                'actualizadas' => $actualizadas,
                'carryover_actualizadas' => $carryoverActualizadas,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * Refresca el campo Estado de las actividades indicadas, usando las fechas
     * de la semana activa. Mismo patrón que SemanalApiController::refreshGeneralStatuses.
     */
    private function refreshGeneralStatuses(string $dbPrefix, int $semana, array $programIds): void
    {
        $programIds = array_values(array_unique(array_filter(array_map('intval', $programIds), static fn($id) => $id > 0)));
        if (empty($programIds)) {
            return;
        }

        $semanaData = $this->db->queryWithProject("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ? LIMIT 1", [$semana]);
        $semanaRow = $semanaData->fetch(PDO::FETCH_ASSOC);
        if (!$semanaRow) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $params = array_merge([$semana], $programIds);
        $stmt = $this->db->prepareWithProject(
            "SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
             FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . "
             WHERE Semana = ? AND Consecutivo_en_Programa IN ({$placeholders})"
        );
        $pid = $this->db->getCurrentProjectId() ?? TableResolver::getProjectIdByPrefix($dbPrefix);
        $stmt->execute(array_merge($params, [$pid]));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updateEstado = $this->db->prepareWithProject(
            "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET Estado = ? WHERE Semana = ? AND Consecutivo_en_Programa = ?"
        );

        foreach ($rows as $row) {
            $estado = $this->lpsService->calculateGeneralStatus(
                $row['Titulo'] ?? 0,
                $row['Ejecutado'] ?? 0,
                $row['Fecha_Inicio'] ?? null,
                $row['Fecha_Fin'] ?? null,
                $semanaRow['Fecha_Inicio_Sem'] ?? null,
                $semanaRow['Fecha_Fin_Sem'] ?? null,
            );
            $updateEstado->execute([$estado, $semana, $row['Consecutivo_en_Programa'], $pid]);
        }
    }

    /**
     * API: Get activity codes from master table.
     */
    public function getCodigos()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.ver');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $stmt = $this->db->queryWithProject("SELECT codigo_actividad, actividad, unidad FROM general_codigos_actividades ORDER BY codigo_actividad ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: Importa el programa general desde un archivo Excel (.xlsx).
     */
    public function importExcel()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.editar');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $isPc = ($vars['area'] ?? 'Construccion') === 'Pre-Construccion';
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = (int) ($_GET['semana'] ?? ($vars['semana'] ?? 0));
            $f_inicio_sem = $_GET['f_inicio_sem'] ?? date('Y-m-d');

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            $archivo = $_FILES['archivoExcel'] ?? null;
            if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Archivo inváido.");
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $excelData = $sheet->toArray();
            $headerRow = $excelData[0] ?? [];
            $columnMap = $this->detectImportColumnMap($headerRow);
            $todoElExcel = $excelData;
            array_shift($excelData);

            // 1. Detección inteligente de
            $stmtMaxCons = $this->db->queryWithProject("SELECT MAX(Semana) as max_sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . "");
            $maxSemCons = (int) ($stmtMaxCons->fetch(PDO::FETCH_ASSOC)['max_sem'] ?? 0);

            $stmtMaxAct = $this->db->queryWithProject("SELECT MAX(Semana) as max_sem FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . "");
            $maxSemAct = (int) ($stmtMaxAct->fetch(PDO::FETCH_ASSOC)['max_sem'] ?? 0);

            // Lógica de Autodetección de Semana Destino:
            if ($maxSemCons === 0) {
                // Caso A: Proyecto Nuevo -> Semana 1
                $semanaNueva = 1;
            } elseif ($maxSemCons > $maxSemAct) {
                // Caso B: Ya existe un borrador (Draft) -> Mantener en la misma semana para re-importar/mapear
                $semanaNueva = $maxSemCons;
            } else {
                // Caso C: Al día -> Preparar la siguiente semana (Next)
                $semanaNueva = $maxSemAct + 1;
            }

            $logFile = PROJECT_ROOT . "/public/debug_import.log";
            $debug = function ($msg) use ($logFile) {
                $timestamp = date('Y-m-d H:i:s');
                file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
            };

            $debug("DEBUG IMPORT: Semana Actual: $semana. Max Consolidado: $maxSemCons. Max Activas: $maxSemAct. Destino: $semanaNueva");
            $debug("DEBUG IMPORT: Modo Pre-Construccion: " . ($isPc ? 'SI' : 'NO'));
            $debug("DEBUG IMPORT: Filas en Excel (sin header): " . count($excelData));

            $colEsquema = $columnMap['schema'];
            $colActividad = $columnMap['task'];
            $debug("DEBUG IMPORT: Columnas detectadas: " . json_encode($columnMap));

            // Herencia inteligente: buscar en la semana activa más reciente
            $semanaOrigen = ($maxSemAct > 0) ? $maxSemAct : $semana;
            $historico = $this->getPreviousWeekData($dbPrefix, $semanaOrigen);
            $debug("DEBUG IMPORT: Herencia desde Semana $semanaOrigen. Registros: " . count($historico));

            $consecutivoEnProg = 0;
            $itemsParaInsertar = [];

            foreach ($excelData as $index => $row) {
                $esquema = $this->normalizeImportCellText($row[$colEsquema] ?? null);
                if ($esquema === '') {
                    $debug("DEBUG IMPORT: Fila $index omitida (Esquema vacío en col $colEsquema)");
                    continue;
                }

                if ($index === 0) {
                    $debug("DEBUG IMPORT: Procesando primera fila de datos: " . json_encode($row));
                }

                $nombreActividadHtml = $this->formatTaskNameWithHierarchy($esquema, $todoElExcel, $colEsquema, $colActividad);
                $nombreLimpio = strip_tags($nombreActividadHtml);

                $excelRowNumber = $index + 2;
                $titulo = $this->isTruthyImportedFlag($row[$columnMap['summary']] ?? null) ? 1 : 0;
                $fInicio = $this->normalizeImportedDate(
                    $this->getWorksheetRawValue($sheet, $columnMap['start'], $excelRowNumber),
                    $row[$columnMap['start']] ?? null,
                    $excelRowNumber,
                    'Fecha_Inicio',
                );
                $fFin = $this->normalizeImportedDate(
                    $this->getWorksheetRawValue($sheet, $columnMap['end'], $excelRowNumber),
                    $row[$columnMap['end']] ?? null,
                    $excelRowNumber,
                    'Fecha_Fin',
                );
                $rutaCritica = $this->isTruthyImportedFlag($row[$columnMap['critical']] ?? null) ? 1 : 0;

                $prev = $historico[$nombreLimpio] ?? [];

                // Si no hay match exacto con el nombre limpio, intentamos una búsqueda más flexible (opcional pero recomendado)
                if (empty($prev)) {
                    foreach ($historico as $hKey => $hRow) {
                        if (trim($hKey) === $nombreLimpio) {
                            $prev = $hRow;
                            break;
                        }
                    }
                }

                $item = [
                    'Semana' => $semanaNueva, 'Consecutivo_en_Programa' => $consecutivoEnProg++,
                    'Id' => $esquema, 'Actividad' => $nombreActividadHtml, 'Titulo' => $titulo,
                    'Fecha_Inicio' => $fInicio, 'Fecha_Fin' => $fFin, 'Ruta_Critica' => $rutaCritica,
                    'Ejecutado' => isset($prev['Ejecutado']) ? $prev['Ejecutado'] : 0,
                    'Responsable_AIA' => $prev['Responsable_AIA'] ?? null,
                    'Sub_Contratista' => $prev['Sub_Contratista'] ?? null,
                    'Observaciones' => $prev['Observaciones'] ?? null,
                    'codigo_actividad' => $prev['codigo_actividad'] ?? null,
                    'medir_productividad' => $prev['medir_productividad'] ?? null,
                    'cantidad_ppto' => $prev['cantidad_ppto'] ?? null,
                    'unidad' => $prev['unidad'] ?? null,
                    'Estado_Restricciones' => isset($prev['Estado_Restricciones']) ? $prev['Estado_Restricciones'] : 0,
                ];

                if ($isPc) {
                    // Pre-Construccion: restricciones PC en vez de D_y_E, Materiales, MdeO, Equipos
                    $item['restriccion_pc_1'] = $prev['restriccion_pc_1'] ?? '0';
                    $item['restriccion_pc_2'] = $prev['restriccion_pc_2'] ?? '0';
                    $item['restriccion_pc_3'] = $prev['restriccion_pc_3'] ?? '0';
                    $item['restriccion_pc_4'] = $prev['restriccion_pc_4'] ?? '0';
                } else {
                    // Construccion: columnas clásicas
                    $item['D_y_E'] = $prev['D_y_E'] ?? '0';
                    $item['Materiales'] = $prev['Materiales'] ?? '0';
                    $item['MdeO'] = $prev['MdeO'] ?? '0';
                    $item['Equipos'] = $prev['Equipos'] ?? '0';
                }

                $item['Predecesora'] = $prev['Predecesora'] ?? '0';
                $item['Pdto_Cons'] = $prev['Pdto_Cons'] ?? '0';
                $item['Modelo'] = $prev['Modelo'] ?? '0';
                $item['programaAnteriorAsociar'] = empty($prev) ? '*No Asociada*' : $nombreLimpio;

                $itemsParaInsertar[] = $item;
            }

            $this->db->beginTransaction();

            // 1A. Actualizar _programa (Baseline/Maestro)
            $this->db->queryWithProject("DELETE FROM {$dbPrefix}_programa");
            $qProg = "INSERT INTO {$dbPrefix}_programa (Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtProg = $this->db->prepareWithProject($qProg);
            foreach ($itemsParaInsertar as $item) {
                $stmtProg->execute([$item['Id'], $item['Actividad'], $item['Titulo'], $item['Fecha_Inicio'], $item['Fecha_Fin'], $item['Ruta_Critica']]);
            }
            $debug("DEBUG IMPORT: _programa actualizado con " . count($itemsParaInsertar) . " registros.");

            // 1B. Borrar borrador anterior en consolidado
            $this->db->queryWithProject("DELETE FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Semana = ?", [$semanaNueva]);

            // 2. Insertar nuevos registros
            $baseColumns = 'Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Responsable_AIA, Sub_Contratista, Observaciones, codigo_actividad, medir_productividad, cantidad_ppto, unidad, Estado_Restricciones';
            if ($isPc) {
                $dynamicColumns = 'restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4';
            } else {
                $dynamicColumns = 'D_y_E, Materiales, MdeO, Equipos';
            }
            $tailColumns = 'Predecesora, Pdto_Cons, Modelo, programaAnteriorAsociar';
            $allColumns = "{$baseColumns}, {$dynamicColumns}, {$tailColumns}";
            $placeholderCount = substr_count($allColumns, ',') + 1;
            $placeholders = implode(', ', array_fill(0, $placeholderCount, '?'));
            $queryInsert = "INSERT INTO " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " ({$allColumns}) VALUES ({$placeholders})";
            $stmtInsert = $this->db->prepareWithProject($queryInsert);

            foreach ($itemsParaInsertar as $item) {
                $stmtInsert->execute(array_values($item));
            }

            // 3. Activar semana (Solo automáticamente para S1; S2+ queda como borrador)
            $stmtCheckSem = $this->db->queryWithProject("SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " WHERE Semana = ?", [$semanaNueva]);
            $existeSemana = (int) $stmtCheckSem->fetchColumn();

            if ($existeSemana == 0 && $semanaNueva === 1) {
                $f_final_sem = date('Y-m-d', strtotime($f_inicio_sem . ' + 6 days'));
                $stmtInsertSem = $this->db->queryWithProject("INSERT INTO " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . " (Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (?, ?, ?, ?)", [$semanaNueva, $f_inicio_sem, $f_final_sem, date('Y-m-d')]);
                $debug("DEBUG IMPORT: Creada semana activa 1 automáticamente.");
            } elseif ($existeSemana == 0) {
                $debug("DEBUG IMPORT: Semana $semanaNueva guardada como BORRADOR. Use 'Nueva Semana' para activarla.");
            }

            $this->db->commit();

            // 3. Integración Legacy: Recalcular estados
            $dbName = $dbPrefix; // Variable esperada por script legacy
            $semana = $semanaNueva; // Variable esperada por script legacy
            $ejecucionActualizada = 1;

            error_log("DEBUG IMPORT: Iniciando integración legacy para Semana $semanaNueva");

            ob_start();
            require PROJECT_ROOT . "/src/Legacy/modificar_sem_estado.php";
            $legacyOutput = ob_get_clean();

            error_log("DEBUG IMPORT: Salida de modificar_sem_estado: " . $legacyOutput);

            $semanaBase = ($maxSemAct > 0) ? min($maxSemAct, max(0, $semanaNueva - 1)) : 0;
            echo json_encode(['respuesta' => 'BIEN', 0 => (int) $semanaNueva, 'semana_base' => (int) $semanaBase]);

        } catch (\Throwable $e) {
            if (isset($debug)) {
                $debug("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
            }
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }

    private function getWorksheetRawValue($sheet, int $zeroBasedColumnIndex, int $rowNumber)
    {
        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($zeroBasedColumnIndex + 1);
        return $sheet->getCell($column . $rowNumber)->getValue();
    }

    private function detectImportColumnMap(array $headerRow): array
    {
        $aliases = [
            'schema' => ['numero de esquema', 'esquema', 'wbs', 'edt', 'id'],
            'task' => ['nombre de tarea', 'actividad', 'tarea', 'nombre'],
            'summary' => ['resumen', 'titulo', 'summary'],
            'start' => ['comienzo', 'fecha inicio', 'fecha de inicio', 'inicio', 'start'],
            'end' => ['fin', 'fecha fin', 'fecha de fin', 'finish'],
            'critical' => ['tareas criticas', 'ruta critica', 'critica', 'critical'],
        ];

        $columnMap = [];
        foreach ($headerRow as $index => $heading) {
            $normalizedHeading = $this->normalizeImportHeading($heading);
            if ($normalizedHeading === '') {
                continue;
            }

            foreach ($aliases as $field => $fieldAliases) {
                if (!isset($columnMap[$field]) && in_array($normalizedHeading, $fieldAliases, true)) {
                    $columnMap[$field] = (int) $index;
                    break;
                }
            }
        }

        $required = ['schema', 'task', 'summary', 'start', 'end', 'critical'];
        $missing = array_values(array_diff($required, array_keys($columnMap)));
        if (!empty($missing)) {
            throw new Exception(
                'Formato de Excel inválido. No se detectaron las columnas requeridas: ' . implode(', ', $missing)
                . '. Descarga y usa la plantilla base de Actualización de Cronograma.',
            );
        }

        return $columnMap;
    }

    private function normalizeImportHeading($value): string
    {
        $text = $this->normalizeImportCellText($value);
        if ($text === '') {
            return '';
        }

        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'ñ' => 'n', 'Ñ' => 'n',
        ]);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function normalizeImportCellText($value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        $text = trim((string) $value);
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text, " \t\n\r\0\x0B'\"");
    }

    private function isTruthyImportedFlag($value): bool
    {
        $text = $this->normalizeImportHeading($value);

        return in_array($text, ['1', 's', 'si', 'yes', 'true'], true);
    }

    private function normalizeImportedDate($rawValue, $formattedValue, int $rowNumber, string $fieldName): ?string
    {
        if ($rawValue instanceof \DateTimeInterface) {
            return $rawValue->format('Y-m-d');
        }

        if ($formattedValue instanceof \DateTimeInterface) {
            return $formattedValue->format('Y-m-d');
        }

        $rawText = $this->dateValueToString($rawValue);
        $formattedText = $this->dateValueToString($formattedValue);

        if ($rawText === '' && $formattedText === '') {
            return null;
        }

        if ($this->isExcelSerialCandidate($rawValue)) {
            return $this->excelSerialToYmd((float) $rawValue, $rowNumber, $fieldName);
        }

        if ($this->isExcelSerialCandidate($rawText)) {
            return $this->excelSerialToYmd((float) $rawText, $rowNumber, $fieldName);
        }

        $primaryText = $rawText !== '' ? $rawText : $formattedText;
        $parsed = $this->parseDateTextToYmd($primaryText);
        if ($parsed !== null) {
            return $parsed;
        }

        if ($formattedText !== '' && $formattedText !== $primaryText && !$this->isExcelSerialCandidate($formattedText)) {
            $parsed = $this->parseDateTextToYmd($formattedText);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        throw new Exception("Fecha inválida en fila {$rowNumber}, {$fieldName}: " . ($primaryText !== '' ? $primaryText : $formattedText));
    }

    private function dateValueToString($value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return trim($value->getPlainText());
        }

        if (is_bool($value)) {
            return '';
        }

        $text = trim((string) $value);
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        return trim($text, " \t\n\r\0\x0B'\"");
    }

    private function isExcelSerialCandidate($value): bool
    {
        if ($value === null || $value instanceof \DateTimeInterface || is_bool($value)) {
            return false;
        }

        $text = trim((string) $value);
        if ($text === '' || !preg_match('/^\d+(?:\.\d+)?$/', $text)) {
            return false;
        }

        $serial = (float) $text;
        return $serial > 0 && $serial < 100000;
    }

    private function excelSerialToYmd(float $serial, int $rowNumber, string $fieldName): string
    {
        try {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial);
            $year = (int) $date->format('Y');
            if ($year < 1900 || $year > 2200) {
                throw new Exception('Serial Excel fuera de rango.');
            }

            return $date->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new Exception("Fecha Excel inválida en fila {$rowNumber}, {$fieldName}: {$serial}");
        }
    }

    private function parseDateTextToYmd(string $value): ?string
    {
        $text = trim($value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

        if (preg_match('/^(\d{4})[-\/]([0-9]{1,2})[-\/]([0-9]{1,2})(?:[ T].*)?$/', $text, $parts)) {
            $year = (int) $parts[1];
            $middle = (int) $parts[2];
            $last = (int) $parts[3];

            return $this->buildYmd($year, $middle, $last)
                ?? $this->buildYmd($year, $last, $middle);
        }

        if (preg_match('/^([0-9]{1,2})[-\/]([0-9]{1,2})[-\/](\d{4})(?:[ T].*)?$/', $text, $parts)) {
            $first = (int) $parts[1];
            $second = (int) $parts[2];
            $year = (int) $parts[3];

            return $this->buildYmd($year, $second, $first)
                ?? $this->buildYmd($year, $first, $second);
        }

        return null;
    }

    private function buildYmd(int $year, int $month, int $day): ?string
    {
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * API: Elimina la actualización actual del cronograma.
     */
    public function deleteUpdate()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.editar');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = (int) ($_GET['semana_objetivo'] ?? $_GET['semana'] ?? $_POST['semana_objetivo'] ?? $_POST['semana'] ?? ($vars['semana'] ?? 0));

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            // 1. Determinar la última semana activa oficialmente
            $stmtMax = $this->db->queryWithProject("SELECT MAX(Semana) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas') . "");
            $maxSemanaActiva = (int) $stmtMax->fetchColumn();

            // 2. Si la semana que se quiere eliminar es superior a la activa, es un borrador (Draft)
            // Procedemos con el borrado físico para que el usuario pueda re-importar/mapear de cero
            if ($semana > $maxSemanaActiva) {
                $sqlDelete = "DELETE FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Semana = ?";
                $this->db->queryWithProject($sqlDelete, [$semana]);
            } else {
                // Si es una semana activa, solo reseteamos los campos de actualización (Soft Reset)
                $sqlReset = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " SET 
                        Ejecutado = 0, Responsable_AIA = NULL, Sub_Contratista = NULL, Observaciones = NULL,
                        programaAnteriorAsociar = '*No Asociada*'
                        WHERE Semana = ?";
                $this->db->queryWithProject($sqlReset, [$semana]);
            }

            echo json_encode([
                'respuesta' => 'BIEN',
                'semana_activa' => $maxSemanaActiva,
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }
    /**
     * Reconstruye el nombre de la tarea con formato de jerarquía (HTML).
     */
    private function formatTaskNameWithHierarchy(string $esquema, array $todoElExcel, int $colEsquema = 0, int $colActividad = 1): string
    {
        $niveles = explode('.', (string) $esquema);
        $contadorNiveles = count($niveles);
        $jerarquia = [];
        $esquemaParcial = '';

        foreach ($niveles as $nivel) {
            $esquemaParcial = ($esquemaParcial === '') ? $nivel : "{$esquemaParcial}.{$nivel}";
            foreach ($todoElExcel as $row) {
                if ($this->normalizeImportCellText($row[$colEsquema] ?? null) === $esquemaParcial) {
                    $jerarquia[] = $this->normalizeImportCellText($row[$colActividad] ?? null) ?: 'Sin Nombre';
                    break;
                }
            }
        }

        $nombrePrincipal = end($jerarquia);
        if ($nombrePrincipal === false) {
            $nombrePrincipal = 'Sin Nombre';
        }

        // AIA 2026: Si el nombre principal ya contiene jerarquía (ej. exportado previo), evitamos duplicar
        if (strpos($nombrePrincipal, '[Capítulo:') !== false) {
            return "<b>" . htmlspecialchars($nombrePrincipal) . "</b>";
        }

        if ($contadorNiveles === 1) {
            return "<b>" . htmlspecialchars($nombrePrincipal) . "</b>";
        }

        $capitulos = array_slice($jerarquia, 0, -1);
        $capituloTexto = htmlspecialchars(implode(', ', array_reverse($capitulos)));
        return "<b>" . htmlspecialchars($nombrePrincipal) . ", </b> <small>[Capítulo: {$capituloTexto}]</small>";
    }

    /**
     * Obtiene los datos de la semana anterior para el rollover.
     */
    private function getPreviousWeekData(string $dbPrefix, int $semanaAnterior): array
    {
        $sql = "SELECT * FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE Semana = ?";
        $stmt = $this->db->queryWithProject($sql, [$semanaAnterior]);
        $results = $stmt->fetchAll();
        $mapped = [];
        foreach ($results as $row) {
            $key = trim(strip_tags((string) $row['Actividad']));

            // Priorización AIA 2026: Si ya tenemos un registro con este nombre,
            // solo lo reemplazamos si el nuevo registro tiene "más datos" o el actual está vacío.
            $hasData = (!empty($row['unidad']) && $row['unidad'] !== '%') || !empty($row['cantidad_ppto']) || !empty($row['Ejecutado']);

            if (!isset($mapped[$key]) || ($hasData && empty($mapped[$key]['cantidad_ppto']))) {
                $mapped[$key] = $row;
            }
        }
        return $mapped;
    }

    /**
     * Auto-asociación semántica entre programa anterior (semana - 1) y actual (semana objetivo).
     *
     * Queries source/target activities, runs fuzzy matching via ActivityMatcherService,
     * and auto-persists high-confidence matches into programmaAnteriorAsociar column.
     *
     * POST /api/general/auto-associate
     * @param string $db           Database prefix
     * @param int    $semana_objetivo  Target week number
     *
     * @return JSON {success, data: {identical: N, high: N, medium: [...], none: N, updated: N}}
     */
    public function autoAssociate()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general_actualizar.editar');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_POST['db'] ?? ($_GET['db'] ?? ($vars['dbName'] ?? ''));
            $semanaObjetivo = isset($_POST['semana_objetivo'])
                ? filter_var($_POST['semana_objetivo'], FILTER_VALIDATE_INT)
                : (isset($_GET['semana_objetivo'])
                    ? filter_var($_GET['semana_objetivo'], FILTER_VALIDATE_INT)
                    : null);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            if ($semanaObjetivo === false || $semanaObjetivo === null || $semanaObjetivo <= 0) {
                throw new Exception("semana_objetivo inválida o no especificada.");
            }

            $semanaSource = $semanaObjetivo - 1;
            $matcher = new ActivityMatcherService();

            // ── 1. Query SOURCE activities (previous week, activities only) ──
            $sqlSource = "SELECT Id, Actividad, Fecha_Inicio FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " 
                          WHERE Semana = ? AND Titulo = 0";
            $stmtSource = $this->db->queryWithProject($sqlSource, [$semanaSource]);
            $sourceRows = $stmtSource->fetchAll(PDO::FETCH_ASSOC);

            // ── 2. Query TARGET activities (current week, activities only) ──
            // Include ALL non-title activities so the modal always appears for verification/editing
            $sqlTarget = "SELECT Consecutivo_en_Programa, Id, Actividad, programaAnteriorAsociar FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " 
                          WHERE Semana = ? AND Titulo = 0";
            $stmtTarget = $this->db->queryWithProject($sqlTarget, [$semanaObjetivo]);
            $targetRows = $stmtTarget->fetchAll(PDO::FETCH_ASSOC);

            // Edge case: no activities found in either week
            if (empty($targetRows)) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'identical' => 0,
                        'high' => 0,
                        'medium' => [],
                        'none' => 0,
                        'updated' => 0,
                    ],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (empty($sourceRows)) {
                // No source to match against — all targets are "none"
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'identical' => 0,
                        'high' => 0,
                        'medium' => [],
                        'none' => count($targetRows),
                        'updated' => 0,
                    ],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // ── 3. Prepare arrays for matchAll ──
            // matchAll expects ['name' => raw, 'chapter' => null, 'row' => dbRow]
            $sources = array_map(fn($row) => [
                'name'         => $row['Actividad'],
                'chapter'      => $matcher->extractChapter($row['Actividad']),
                'fecha_inicio' => $row['Fecha_Inicio'] ?? null,
                'id'           => $row['Id'],
                'row'          => $row,
            ], $sourceRows);
            $targets = array_map(fn($row) => [
                'name' => $row['Actividad'],
                'chapter' => $matcher->extractChapter($row['Actividad']),
                'row' => $row,
            ], $targetRows);

            // ── 4. Run fuzzy matching ──
            $matches = $matcher->matchAll($targets, $sources);

            // ── 5. Auto-persist identical + high confidence matches ──
            // Pre-existing matchAll thresholds: identical (1.0+exact), high (≥0.8), medium (≥0.5), none (<0.5)
            $updated = 0;
            $autoItems = array_merge($matches['identical'], $matches['high']);

            // Clean up previously stored numeric IDs (legacy bug: stored consecutive numbers instead of activity names)
            $sqlCleanup = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " 
                           SET programaAnteriorAsociar = '*No Asociada*' 
                           WHERE Semana = ? AND Titulo = 0
                           AND programaAnteriorAsociar IS NOT NULL AND programaAnteriorAsociar != ''
                           AND programaAnteriorAsociar != '*No Asociada*'
                           AND programaAnteriorAsociar REGEXP '^[0-9]+(\\.[0-9]+)*$'";
            $stmtCleanup = $this->db->queryWithProject($sqlCleanup, [$semanaObjetivo]);

            if (!empty($autoItems)) {
                $this->db->beginTransaction();
                try {
                    $sqlUpdate = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " 
                                  SET programaAnteriorAsociar = ? 
                                  WHERE Consecutivo_en_Programa = ? AND Semana = ?
                                  AND (programaAnteriorAsociar IS NULL OR programaAnteriorAsociar = '' OR programaAnteriorAsociar = '*No Asociada*')";
                    $stmtUpdate = $this->db->prepareWithProject($sqlUpdate);
                    $pidAuto = $this->db->getCurrentProjectId() ?? TableResolver::getProjectIdByPrefix($dbPrefix);

                    foreach ($autoItems as $item) {
                        // Pre-existing matchAll: 'target' is the full input item, 'matched' is the best source
                        $sourceRow = $item['matched']['row'] ?? null;
                        $targetRow = $item['target']['row'] ?? null;
                        $sourceName = $sourceRow['Actividad'] ?? null;
                        $targetConsecutivo = $targetRow['Consecutivo_en_Programa'] ?? null;

                        if ($sourceName !== null && $targetConsecutivo !== null) {
                            $stmtUpdate->execute([$sourceName, $targetConsecutivo, $semanaObjetivo, $pidAuto]);
                            $updated++;
                        }
                    }

                    $this->db->commit();
                } catch (Exception $e) {
                    $this->db->rollBack();
                    throw $e;
                }
            }

            // ── 6. Build response ──
            // Medium items: include candidates with name, chapter, confidence
            // 'row' returns the Consecutivo_en_Programa so JS can find the visual row index
            $mediumItems = array_map(fn($item) => [
                'row' => $item['target']['row']['Consecutivo_en_Programa'] ?? null,
                'activityName' => strip_tags($item['target']['name'] ?? ''),
                'candidates' => array_map(fn($c) => [
                    'name' => strip_tags($c['activity']['name'] ?? ''),
                    'chapter' => $c['activity']['chapter'] ?? null,
                    'fecha_inicio' => $c['activity']['fecha_inicio'] ?? null,
                    'id' => $c['activity']['id'] ?? null,
                    'confidence' => $c['confidence'],
                ], $item['candidates'] ?? []),
                // Mark if this activity already has an association (for re-run display)
                'alreadyAssociated' => !empty($item['target']['row']['programaAnteriorAsociar'])
                    && $item['target']['row']['programaAnteriorAsociar'] !== '*No Asociada*',
                'currentAssociation' => (!empty($item['target']['row']['programaAnteriorAsociar'])
                    && $item['target']['row']['programaAnteriorAsociar'] !== '*No Asociada*')
                    ? $item['target']['row']['programaAnteriorAsociar']
                    : null,
            ], $matches['medium']);

            echo json_encode([
                'success' => true,
                'data' => [
                    'identical' => count($matches['identical']),
                    'high' => count($matches['high']),
                    'medium' => $mediumItems,
                    'none' => count($matches['none']),
                    'updated' => $updated,
                ],
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Registra una decisión de asociación del usuario para entrenamiento ML futuro.
     *
     * POST /api/general/decision-log
     * @return JSON {success: true, id: int} | {success: false, error: string}
     */
    public function decisionLog()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general_actualizar.editar');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $proyectoId = $_POST['proyecto_id'] ?? '';
            $semanaObjetivo = filter_var($_POST['semana_objetivo'] ?? 0, FILTER_VALIDATE_INT);
            $actividadConsecutivo = $_POST['actividad_consecutivo'] ?? '';
            $actividadNombre = $_POST['actividad_nombre'] ?? '';
            $actividadTokens = $_POST['actividad_tokens'] ?? '[]';
            $actividadPosicionPg = filter_var($_POST['actividad_posicion_pg'] ?? 0, FILTER_VALIDATE_INT);
            $actividadVecinos = $_POST['actividad_vecinos'] ?? '[]';
            $actividadCapitulo = $_POST['actividad_capitulo'] ?? null;
            $engineUsado = $_POST['engine_usado'] ?? 'rule_engine';
            $procesoSugerido = $_POST['proceso_sugerido'] ?? null;
            $confianza = filter_var($_POST['confianza'] ?? 0, FILTER_VALIDATE_FLOAT);
            $reglaAplicada = $_POST['regla_aplicada'] ?? null;
            $candidatosAlternativos = $_POST['candidatos_alternativos'] ?? '[]';
            $explicacion = $_POST['explicacion'] ?? null;
            $decisionUsuario = $_POST['decision_usuario'] ?? '';
            $procesoFinal = $_POST['proceso_final'] ?? '';
            $procesoFinalId = isset($_POST['proceso_final_id']) ? filter_var($_POST['proceso_final_id'], FILTER_VALIDATE_INT) : null;

            // Validate required fields
            if (empty($proyectoId) || $semanaObjetivo <= 0 || empty($actividadConsecutivo) || empty($actividadNombre)) {
                throw new Exception("Faltan parámetros requeridos (proyecto_id, semana_objetivo, actividad_consecutivo, actividad_nombre).");
            }

            $validDecisions = ['accept', 'correct', 'skip', 'manual'];
            if (!in_array($decisionUsuario, $validDecisions, true)) {
                throw new Exception("decision_usuario inválido. Debe ser: " . implode(', ', $validDecisions));
            }

            if (empty($procesoFinal)) {
                throw new Exception("proceso_final es requerido.");
            }

            $vars = $this->getSessionVars();
            $usuarioId = $vars['user'] ?? null;

            $sql = "INSERT INTO general_decision_log (
                proyecto_id, semana_objetivo, actividad_consecutivo, actividad_nombre,
                actividad_tokens, actividad_posicion_pg, actividad_vecinos, actividad_capitulo,
                engine_usado, proceso_sugerido, confianza, regla_aplicada,
                candidatos_alternativos, explicacion, decision_usuario, proceso_final,
                proceso_final_id, usuario_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            $stmt = $this->db->queryWithProject($sql, [ $proyectoId, $semanaObjetivo, $actividadConsecutivo, $actividadNombre, $actividadTokens, $actividadPosicionPg, $actividadVecinos, $actividadCapitulo, $engineUsado, $procesoSugerido, $confianza, $reglaAplicada, $candidatosAlternativos, $explicacion, $decisionUsuario, $procesoFinal, $procesoFinalId, $usuarioId, ]);

            $insertedId = $this->db->lastInsertId();

            echo json_encode([
                'success' => true,
                'id' => $insertedId,
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: Configuración dinámica de restricciones según el área de sesión.
     *
     * GET /api/general/restriction-config
     *
     * Para Area='Construccion' devuelve el conjunto estándar de 7 restricciones.
     * Para Area='Pre-Construccion' consulta los nombres personalizados desde
     * general_proyectos_procesos (pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre).
     *
     * @return JSON con estructura {area, restrictions[], hardRestrictions[], softRestrictions[]}
     */
    public function restrictionConfig()
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $area = $vars['area'] ?? 'Construccion';

            if ($area === 'Pre-Construccion') {
                $dbPrefix = $vars['dbName'] ?? '';
                $label2 = null;
                $label3 = null;
                $label4 = null;

                if (!empty($dbPrefix) && preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                    $stmt = $this->db->queryWithProject(
                        "SELECT pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre
                         FROM general_proyectos_procesos
                         WHERE Base_de_Datos = ?
                         LIMIT 1",
                        [$dbPrefix]
                    );
                    $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($proyecto) {
                        $label2 = !empty($proyecto['pc_restr_2_nombre']) ? $proyecto['pc_restr_2_nombre'] : null;
                        $label3 = !empty($proyecto['pc_restr_3_nombre']) ? $proyecto['pc_restr_3_nombre'] : null;
                        $label4 = !empty($proyecto['pc_restr_4_nombre']) ? $proyecto['pc_restr_4_nombre'] : null;
                    }
                }

                // Always include hard restriction (Predecesora)
                $restrictions = [
                    [
                        'key'       => 'restriccion_pc_1',
                        'label'     => 'Predecesora',
                        'type'      => 'hard',
                        'threshold' => 50,
                        'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                    ],
                ];

                $softRestrictions = [];

                // Only include soft restrictions that have a custom name
                if ($label2 !== null) {
                    $restrictions[] = [
                        'key'       => 'restriccion_pc_2',
                        'label'     => $label2,
                        'type'      => 'soft',
                        'threshold' => 100,
                        'options'   => ['0%', '50%', '100%', 'N/A'],
                    ];
                    $softRestrictions[] = 'restriccion_pc_2';
                }
                if ($label3 !== null) {
                    $restrictions[] = [
                        'key'       => 'restriccion_pc_3',
                        'label'     => $label3,
                        'type'      => 'soft',
                        'threshold' => 100,
                        'options'   => ['0%', '50%', '100%', 'N/A'],
                    ];
                    $softRestrictions[] = 'restriccion_pc_3';
                }
                if ($label4 !== null) {
                    $restrictions[] = [
                        'key'       => 'restriccion_pc_4',
                        'label'     => $label4,
                        'type'      => 'soft',
                        'threshold' => 100,
                        'options'   => ['0%', '50%', '100%', 'N/A'],
                    ];
                    $softRestrictions[] = 'restriccion_pc_4';
                }

                $response = [
                    'area' => $area,
                    'restrictions' => $restrictions,
                    'hardRestrictions' => ['restriccion_pc_1'],
                    'softRestrictions' => $softRestrictions,
                ];
            } else {
                $response = [
                    'area' => 'Construccion',
                    'restrictions' => [
                        [
                            'key'       => 'D_y_E',
                            'label'     => 'Diseños y Especificaciones',
                            'type'      => 'hard',
                            'threshold' => 100,
                            'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'Materiales',
                            'label'     => 'Materiales',
                            'type'      => 'hard',
                            'threshold' => 100,
                            'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'MdeO',
                            'label'     => 'Mano de Obra',
                            'type'      => 'hard',
                            'threshold' => 100,
                            'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'Equipos',
                            'label'     => 'Equipos y Herramienta',
                            'type'      => 'hard',
                            'threshold' => 100,
                            'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'Predecesora',
                            'label'     => 'Actividad Predecesora',
                            'type'      => 'hard',
                            'threshold' => 50,
                            'options'   => ['0%', '33%', '66%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'Pdto_Cons',
                            'label'     => 'Procedimiento Constructivo',
                            'type'      => 'soft',
                            'threshold' => 100,
                            'options'   => ['0%', '50%', '100%', 'N/A'],
                        ],
                        [
                            'key'       => 'Modelo',
                            'label'     => 'Modelación BIM',
                            'type'      => 'soft',
                            'threshold' => 100,
                            'options'   => ['0%', '50%', '100%', 'N/A'],
                        ],
                    ],
                    'hardRestrictions' => ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
                    'softRestrictions' => ['Pdto_Cons', 'Modelo'],
                ];
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
