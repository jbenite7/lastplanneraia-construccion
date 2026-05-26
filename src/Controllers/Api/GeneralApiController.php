<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Lps\LpsService;
use App\Services\ProgramaConsolidadoNormalizationService;
use PDO;
use Exception;

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

            if (!empty($_GET["activa_no_requeridas"])) {
                $conditions[] = "(Titulo = 0 AND Estado = 'No Requerida')";
            }
            if (!empty($_GET["activa_lookahead"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'Actividad Futura' OR Estado = 'En Liberación de Restricciones'))";
            }
            if (!empty($_GET["activa_no_iniciadas"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'Debe Iniciar esta Semana' OR Estado = 'Debe Iniciar esta Semana y Restricciones Pendientes'))";
            }
            if (!empty($_GET["activa_a_tiempo"])) {
                $conditions[] = "(Titulo = 0 AND (Estado = 'En Curso' OR Estado = 'Adelantada' OR Estado = 'A Tiempo'))";
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
            $stmtFechas = $this->db->prepare("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ? LIMIT 1");
            $stmtFechas->execute([$semana]);
            $fechasSemana = $stmtFechas->fetch(PDO::FETCH_ASSOC);
            
            $fechaInicioSemana = $fechasSemana['Fecha_Inicio_Sem'] ?? date('Y-m-d');

            // 4. Consulta Principal
            $sql = "SELECT * 
                    FROM {$dbPrefix}_programa_consolidado 
                    WHERE Semana = ? 
                    $sqlFilter 
                    ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$semana]);
            $data = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $unidad = trim((string)($row['unidad'] ?? ''));
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
                        $fechaInicioSemana
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

            $checkStmt = $this->db->prepare("SELECT Titulo FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?");
            $checkStmt->execute([$id, $semana]);
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
                if ($cantidadPpto < 0) throw new Exception("La cantidad en presupuesto no puede ser negativa.");
                $cantidadPpto = round($cantidadPpto, 1);
                if ($cantidadPpto === 0.0) $cantidadPpto = null;
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

            $auditStmt = $this->db->prepare("SELECT Ejecutado, Ejecutado_Siguiente_Semana, Estado, Titulo, unidad, cantidad_ppto FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1");
            $auditStmt->execute([$id, $semana]);
            $auditBefore = $auditStmt->fetch(PDO::FETCH_ASSOC);
            $auditStartTime = microtime(true);
            error_log("[PGAudit] INICIO | usuario={$vars['user']} | db={$dbPrefix} | semana={$semana} | id={$id} | Titulo={$auditBefore['Titulo']} | Ejecutado_antes={$auditBefore['Ejecutado']} | EjecSigSem_antes={$auditBefore['Ejecutado_Siguiente_Semana']} | Estado_antes={$auditBefore['Estado']} | unidad={$auditBefore['unidad']} | cantidad_ppto={$auditBefore['cantidad_ppto']} | POST_Ejecutado={$rawInput} | POST_EjecutadoRatio=" . ($_POST["EjecutadoRatio"] ?? 'null') . " | POST_unidad={$unidadRaw} | POST_cantidad_ppto=" . ($_POST["cantidad_ppto"] ?? 'null') . " | ejecutado_calculado={$ejecutado}");

            // 4. Update Principal (Incluyendo Mapeo)
            $sql = "UPDATE {$dbPrefix}_programa_consolidado SET 
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
            
            $updateStmt = $this->db->prepare($sql);
            $updateStmt->execute([
                $ejecutado, $medirProductividad, $unidad, $cantidadPpto, 
                $codigoActividad, $ejecutado, $fechaInicio, $fechaFin, $actividadAsociar, $id, $semana
            ]);

            $verifyStmt = $this->db->prepare("SELECT unidad, cantidad_ppto, Ejecutado FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1");
            $verifyStmt->execute([$id, $semana]);
            $updatedRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$updatedRow) {
                throw new Exception("No se encontró la actividad a actualizar en Programa General.");
            }

            $unidad = trim((string)($updatedRow['unidad'] ?? ''));
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
                    $sqlApply = "UPDATE {$dbPrefix}_programa_consolidado SET 
                                    Responsable_AIA = ?, Sub_Contratista = ?, Observaciones = ?, codigo_actividad = ?, 
                                    medir_productividad = ?, cantidad_ppto = ?, unidad = ?, 
                                    Estado_Restricciones = ?, D_y_E = ?, Materiales = ?, MdeO = ?, Equipos = ?, 
                                    Predecesora = ?, Pdto_Cons = ?, Modelo = ?, Ejecutado = ?
                                 WHERE Consecutivo_en_Programa = ? AND Semana = ?";
                    $this->db->prepare($sqlApply)->execute([
                        $dataHerencia['Responsable_AIA'], $dataHerencia['Sub_Contratista'], $dataHerencia['Observaciones'], $dataHerencia['codigo_actividad'],
                        $dataHerencia['medir_productividad'], $dataHerencia['cantidad_ppto'], $dataHerencia['unidad'],
                        $dataHerencia['Estado_Restricciones'], $dataHerencia['D_y_E'], $dataHerencia['Materiales'], $dataHerencia['MdeO'], $dataHerencia['Equipos'],
                        $dataHerencia['Predecesora'], $dataHerencia['Pdto_Cons'], $dataHerencia['Modelo'], $dataHerencia['Ejecutado'], $id, $semana
                    ]);

                    $ejecutado = $dataHerencia['Ejecutado'];
                    $unidad = $dataHerencia['unidad'];
                }
            }

            // 6. Recalcular Estado
            $ctxStmt = $this->db->prepare("SELECT Titulo FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?");
            $ctxStmt->execute([$id, $semana]);
            $row = $ctxStmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception("Error post-update: Registro no encontrado.");
            }

            $stmtFechas = $this->db->prepare("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?");
            $stmtFechas->execute([$semana]);
            $inicioSemanaRow = $stmtFechas->fetch(PDO::FETCH_ASSOC);
            
            $fechaCorte = $inicioSemanaRow['Fecha_Inicio_Sem'] ?? date('Y-m-d');
            $fechaFinSemana = $inicioSemanaRow['Fecha_Fin_Sem'] ?? null;

            $nuevoEstado = $this->lpsService->calculateGeneralStatus($row['Titulo'], $ejecutado, $fechaInicio, $fechaFin, $fechaCorte, $fechaFinSemana);
            $semanasInicio = $this->lpsService->toTimestamp($fechaInicio) !== null ? round(($this->lpsService->toTimestamp($fechaInicio) - $this->lpsService->toTimestamp($fechaCorte)) / (7 * 86400)) : null;

            $this->db->prepare("UPDATE {$dbPrefix}_programa_consolidado SET Estado = ?, Semanas_Inicio = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?")
                     ->execute([$nuevoEstado, $semanasInicio, $id, $semana]);

            $auditEndTime = microtime(true);
            $auditDuration = round(($auditEndTime - $auditStartTime) * 1000, 2);
            $auditStmt2 = $this->db->prepare("SELECT Ejecutado, Ejecutado_Siguiente_Semana, Estado FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ? LIMIT 1");
            $auditStmt2->execute([$id, $semana]);
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
                'Ejecutado' => $ejecutado // Retornamos el ratio decimal calculado
            ];
            
            // Si hubo herencia, devolvemos los campos actualizados (prioridad sobre el input manual)
            if (!empty($_POST['editarActividadAsociar']) && !empty($actividadAsociar) && $actividadAsociar !== '*No Asociada*') {
                $inheritanceFields = "unidad, cantidad_ppto, Ejecutado, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo";
                $inheritanceStmt = $this->db->prepare("SELECT $inheritanceFields FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?");
                $inheritanceStmt->execute([$id, $semana]);
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
     */
    public function updateBatch()
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = isset($_GET['semana']) ? filter_var($_GET['semana'], FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix) || $semana <= 0) {
                throw new Exception('Parámetros inválidos.');
            }

            $stmtFecha = $this->db->prepare("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ? LIMIT 1");
            $stmtFecha->execute([$semana]);
            $dataSemana = $stmtFecha->fetch(PDO::FETCH_ASSOC);

            if (!$dataSemana || empty($dataSemana['Fecha_Inicio_Sem'])) {
                throw new Exception('No se encontró la semana activa para recalcular estados.');
            }

            $inicioSemana = $dataSemana['Fecha_Inicio_Sem'];
            $finSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;

            $this->db->prepare("UPDATE {$dbPrefix}_programa_consolidado SET Activa = 1 WHERE Semana = ?")->execute([$semana]);
            $this->db->prepare("UPDATE {$dbPrefix}_programa_consolidado SET Estado = 'Capítulo' WHERE Semana = ? AND Titulo = 1")->execute([$semana]);

            $rows = $this->db->prepare("SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Titulo = 0");
            $rows->execute([$semana]);
            $activities = $rows->fetchAll(PDO::FETCH_ASSOC);

            $actualizadas = 0;
            $updateStmt = $this->db->prepare("UPDATE {$dbPrefix}_programa_consolidado SET Estado = ?, Semanas_Inicio = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?");

            foreach ($activities as $row) {
                $estadoNuevo = $this->lpsService->calculateGeneralStatus($row['Titulo'], $row['Ejecutado'] ?? 0, $row['Fecha_Inicio'], $row['Fecha_Fin'], $inicioSemana, $finSemana);
                $semanasInicio = $this->lpsService->toTimestamp($row['Fecha_Inicio']) !== null ? round(($this->lpsService->toTimestamp($row['Fecha_Inicio']) - $this->lpsService->toTimestamp($inicioSemana)) / (7 * 86400)) : null;
                $updateStmt->execute([$estadoNuevo, $semanasInicio, $row['Consecutivo_en_Programa'], $semana]);
                $actualizadas++;
            }

            echo json_encode(['respuesta' => 'BIEN', 'actualizadas' => $actualizadas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * API: Get activity codes from master table.
     */
    public function getCodigos()
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $stmt = $this->db->query("SELECT codigo_actividad, actividad, unidad FROM general_codigos_actividades ORDER BY codigo_actividad ASC");
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
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = (int)($_GET['semana'] ?? ($vars['semana'] ?? 0));
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
            $todoElExcel = $excelData; 
            array_shift($excelData); 

            // 1. Detección inteligente de
            $stmtMaxCons = $this->db->prepare("SELECT MAX(Semana) as max_sem FROM {$dbPrefix}_programa_consolidado");
            $stmtMaxCons->execute();
            $maxSemCons = (int)($stmtMaxCons->fetch(PDO::FETCH_ASSOC)['max_sem'] ?? 0);

            $stmtMaxAct = $this->db->prepare("SELECT MAX(Semana) as max_sem FROM {$dbPrefix}_semanas_activas");
            $stmtMaxAct->execute();
            $maxSemAct = (int)($stmtMaxAct->fetch(PDO::FETCH_ASSOC)['max_sem'] ?? 0);
            
            // Lógica de Autodetección de Semana Destino:
            if ($maxSemCons === 0) {
                // Caso A: Proyecto Nuevo -> Semana 1
                $semanaNueva = 1;
            } else if ($maxSemCons > $maxSemAct) {
                // Caso B: Ya existe un borrador (Draft) -> Mantener en la misma semana para re-importar/mapear
                $semanaNueva = $maxSemCons;
            } else {
                // Caso C: Al día -> Preparar la siguiente semana (Next)
                $semanaNueva = $maxSemAct + 1;
            }

            $logFile = PROJECT_ROOT . "/public/debug_import.log";
            $debug = function($msg) use ($logFile) {
                $timestamp = date('Y-m-d H:i:s');
                file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
            };

            $debug("DEBUG IMPORT: Semana Actual: $semana. Max Consolidado: $maxSemCons. Max Activas: $maxSemAct. Destino: $semanaNueva");
            $debug("DEBUG IMPORT: Filas en Excel (sin header): " . count($excelData));

            // 2. Encontrar columna de esquema (puede no ser la 0)
            $colEsquema = 0;
            if (!empty($excelData)) {
                $primeraFila = $excelData[0];
                foreach ($primeraFila as $i => $cell) {
                    if (preg_match('/^\d+(\.\d+)*$/', (string)$cell)) {
                        $colEsquema = $i;
                        $debug("DEBUG IMPORT: Columna de esquema detectada en índice: $colEsquema");
                        break;
                    }
                }
            }

            // Herencia inteligente: buscar en la semana activa más reciente
            $semanaOrigen = ($maxSemAct > 0) ? $maxSemAct : $semana;
            $historico = $this->getPreviousWeekData($dbPrefix, $semanaOrigen);
            $debug("DEBUG IMPORT: Herencia desde Semana $semanaOrigen. Registros: " . count($historico));

            $consecutivoEnProg = 0;
            $itemsParaInsertar = [];

            foreach ($excelData as $index => $row) {
                if (empty($row[$colEsquema])) {
                    $debug("DEBUG IMPORT: Fila $index omitida (Esquema vacío en col $colEsquema)");
                    continue;
                }
                
                if ($index === 0) {
                    $debug("DEBUG IMPORT: Procesando primera fila de datos: " . json_encode($row));
                }

                $esquema = (string)$row[$colEsquema];
                $nombreActividadHtml = $this->formatTaskNameWithHierarchy($esquema, $todoElExcel, $colEsquema);
                $nombreLimpio = strip_tags($nombreActividadHtml);

                $excelRowNumber = $index + 2;
                $titulo = (isset($row[2]) && (stripos($row[2], 'S') !== false || $row[2] == '1')) ? 1 : 0;
                $fInicio = $this->normalizeImportedDate(
                    $this->getWorksheetRawValue($sheet, 3, $excelRowNumber),
                    $row[3] ?? null,
                    $excelRowNumber,
                    'Fecha_Inicio'
                );
                $fFin = $this->normalizeImportedDate(
                    $this->getWorksheetRawValue($sheet, 4, $excelRowNumber),
                    $row[4] ?? null,
                    $excelRowNumber,
                    'Fecha_Fin'
                );
                $rutaCritica = (isset($row[5]) && (stripos($row[5], 'S') !== false || $row[5] == '1')) ? 1 : 0;

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

                $itemsParaInsertar[] = [
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
                    'D_y_E' => $prev['D_y_E'] ?? '0',
                    'Materiales' => $prev['Materiales'] ?? '0', 
                    'MdeO' => $prev['MdeO'] ?? '0', 
                    'Equipos' => $prev['Equipos'] ?? '0',
                    'Predecesora' => $prev['Predecesora'] ?? '0', 
                    'Pdto_Cons' => $prev['Pdto_Cons'] ?? '0', 
                    'Modelo' => $prev['Modelo'] ?? '0',
                    'programaAnteriorAsociar' => empty($prev) ? '*No Asociada*' : $nombreLimpio
                ];
            }

            $this->db->beginTransaction();

            // 1A. Actualizar _programa (Baseline/Maestro)
            $this->db->prepare("DELETE FROM {$dbPrefix}_programa")->execute();
            $qProg = "INSERT INTO {$dbPrefix}_programa (Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtProg = $this->db->prepare($qProg);
            foreach ($itemsParaInsertar as $item) {
                $stmtProg->execute([$item['Id'], $item['Actividad'], $item['Titulo'], $item['Fecha_Inicio'], $item['Fecha_Fin'], $item['Ruta_Critica']]);
            }
            $debug("DEBUG IMPORT: _programa actualizado con " . count($itemsParaInsertar) . " registros.");

            // 1B. Borrar borrador anterior en consolidado
            $this->db->prepare("DELETE FROM {$dbPrefix}_programa_consolidado WHERE Semana = ?")->execute([$semanaNueva]);

             // 2. Insertar nuevos registros
            $queryInsert = "INSERT INTO {$dbPrefix}_programa_consolidado (
                Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 
                Ejecutado, Responsable_AIA, Sub_Contratista, Observaciones, codigo_actividad, medir_productividad, cantidad_ppto, unidad,
                Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, programaAnteriorAsociar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $this->db->prepare($queryInsert);

            foreach ($itemsParaInsertar as $item) {
                $stmtInsert->execute(array_values($item));
            }

            // 3. Activar semana (Solo automáticamente para S1; S2+ queda como borrador)
            $stmtCheckSem = $this->db->prepare("SELECT COUNT(*) FROM {$dbPrefix}_semanas_activas WHERE Semana = ?");
            $stmtCheckSem->execute([$semanaNueva]);
            $existeSemana = (int)$stmtCheckSem->fetchColumn();

            if ($existeSemana == 0 && $semanaNueva === 1) {
                $f_final_sem = date('Y-m-d', strtotime($f_inicio_sem . ' + 6 days'));
                $stmtInsertSem = $this->db->prepare("INSERT INTO {$dbPrefix}_semanas_activas (Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (?, ?, ?, ?)");
                $stmtInsertSem->execute([$semanaNueva, $f_inicio_sem, $f_final_sem, date('Y-m-d')]);
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
            echo json_encode(['respuesta' => 'BIEN', 0 => (int)$semanaNueva, 'semana_base' => (int)$semanaBase]);

        } catch (\Throwable $e) {
            if (isset($debug)) {
                $debug("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
            }
            if ($this->db->inTransaction()) $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }

    private function getWorksheetRawValue($sheet, int $zeroBasedColumnIndex, int $rowNumber)
    {
        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($zeroBasedColumnIndex + 1);
        return $sheet->getCell($column . $rowNumber)->getValue();
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
            return $this->excelSerialToYmd((float)$rawValue, $rowNumber, $fieldName);
        }

        if ($this->isExcelSerialCandidate($rawText)) {
            return $this->excelSerialToYmd((float)$rawText, $rowNumber, $fieldName);
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

        $text = trim((string)$value);
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        return trim($text, " \t\n\r\0\x0B'\"");
    }

    private function isExcelSerialCandidate($value): bool
    {
        if ($value === null || $value instanceof \DateTimeInterface || is_bool($value)) {
            return false;
        }

        $text = trim((string)$value);
        if ($text === '' || !preg_match('/^\d+(?:\.\d+)?$/', $text)) {
            return false;
        }

        $serial = (float)$text;
        return $serial > 0 && $serial < 100000;
    }

    private function excelSerialToYmd(float $serial, int $rowNumber, string $fieldName): string
    {
        try {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial);
            $year = (int)$date->format('Y');
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
            $year = (int)$parts[1];
            $middle = (int)$parts[2];
            $last = (int)$parts[3];

            return $this->buildYmd($year, $middle, $last)
                ?? $this->buildYmd($year, $last, $middle);
        }

        if (preg_match('/^([0-9]{1,2})[-\/]([0-9]{1,2})[-\/](\d{4})(?:[ T].*)?$/', $text, $parts)) {
            $first = (int)$parts[1];
            $second = (int)$parts[2];
            $year = (int)$parts[3];

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
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vars = $this->getSessionVars();
            $dbPrefix = $_GET['db'] ?? ($vars['dbName'] ?? '');
            $semana = (int)($_GET['semana_objetivo'] ?? $_GET['semana'] ?? $_POST['semana_objetivo'] ?? $_POST['semana'] ?? ($vars['semana'] ?? 0));

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            // 1. Determinar la última semana activa oficialmente
            $stmtMax = $this->db->prepare("SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas");
            $stmtMax->execute();
            $maxSemanaActiva = (int)$stmtMax->fetchColumn();

            // 2. Si la semana que se quiere eliminar es superior a la activa, es un borrador (Draft)
            // Procedemos con el borrado físico para que el usuario pueda re-importar/mapear de cero
            if ($semana > $maxSemanaActiva) {
                $sqlDelete = "DELETE FROM {$dbPrefix}_programa_consolidado WHERE Semana = ?";
                $this->db->prepare($sqlDelete)->execute([$semana]);
            } else {
                // Si es una semana activa, solo reseteamos los campos de actualización (Soft Reset)
                $sqlReset = "UPDATE {$dbPrefix}_programa_consolidado SET 
                        Ejecutado = 0, Responsable_AIA = NULL, Sub_Contratista = NULL, Observaciones = NULL,
                        programaAnteriorAsociar = '*No Asociada*'
                        WHERE Semana = ?";
                $this->db->prepare($sqlReset)->execute([$semana]);
            }

            echo json_encode([
                'respuesta' => 'BIEN',
                'semana_activa' => $maxSemanaActiva
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
    }
    /**
     * Reconstruye el nombre de la tarea con formato de jerarquía (HTML).
     */
    private function formatTaskNameWithHierarchy(string $esquema, array $todoElExcel, int $colEsquema = 0): string
    {
        $niveles = explode('.', (string)$esquema);
        $contadorNiveles = count($niveles);
        $jerarquia = [];
        $esquemaParcial = '';

        foreach ($niveles as $nivel) {
            $esquemaParcial = ($esquemaParcial === '') ? $nivel : "{$esquemaParcial}.{$nivel}";
            foreach ($todoElExcel as $row) {
                if ((string)($row[$colEsquema] ?? '') === $esquemaParcial) {
                    $jerarquia[] = $row[$colEsquema + 1] ?? 'Sin Nombre';
                    break;
                }
            }
        }

        $nombrePrincipal = end($jerarquia);
        
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
        $sql = "SELECT * FROM {$dbPrefix}_programa_consolidado WHERE Semana = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$semanaAnterior]);
        $results = $stmt->fetchAll();
        $mapped = [];
        foreach ($results as $row) {
            $key = trim(strip_tags((string)$row['Actividad']));
            
            // Priorización AIA 2026: Si ya tenemos un registro con este nombre, 
            // solo lo reemplazamos si el nuevo registro tiene "más datos" o el actual está vacío.
            $hasData = (!empty($row['unidad']) && $row['unidad'] !== '%') || !empty($row['cantidad_ppto']) || !empty($row['Ejecutado']);
            
            if (!isset($mapped[$key]) || ($hasData && empty($mapped[$key]['cantidad_ppto']))) {
                $mapped[$key] = $row;
            }
        }
        return $mapped;
    }
}
