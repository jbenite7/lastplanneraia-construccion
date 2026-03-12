<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Lps\LpsService;
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
            $semana = isset($_GET['semana']) ? filter_var($_GET['semana'], FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);

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

            // 3. Obtener Fechas de la Semana
            $stmtFechas = $this->db->prepare("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ? LIMIT 1");
            $stmtFechas->execute([$semana]);
            $fechasSemana = $stmtFechas->fetch(PDO::FETCH_ASSOC);
            
            $fechaInicioSemana = $fechasSemana['Fecha_Inicio_Sem'] ?? date('Y-m-d');

            // 4. Auto-actualización de unidades vacías a '%' (paridad con legacy)
            $sqlAutoUpdate = "UPDATE {$dbPrefix}_programa_consolidado SET unidad = '%' WHERE Semana = ? AND (unidad IS NULL OR TRIM(unidad) = '') AND Titulo = 0";
            $this->db->prepare($sqlAutoUpdate)->execute([$semana]);

            // 5. Consulta Principal
            $sql = "SELECT * 
                    FROM {$dbPrefix}_programa_consolidado 
                    WHERE Semana = ? 
                    AND Fecha_Inicio IS NOT NULL 
                    AND Fecha_Fin IS NOT NULL 
                    $sqlFilter 
                    ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$semana]);
            $data = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

            $semana = isset($_GET['semana']) ? filter_var($_GET['semana'], FILTER_VALIDATE_INT) : ($vars['semana'] ?? 0);
            $id = $_POST['Id'] ?? null;

            if (!$id || !$semana) {
                throw new Exception("Faltan parámetros requeridos (Id, Semana).");
            }

            $ejecutado = $this->lpsService->toFloat($_POST["Ejecutado"] ?? null);
            $codigoActividad = $_POST["codigo_actividad"] ?? '';
            $unidad = $_POST["unidad"] ?? '';
            $cantidadPpto = $this->lpsService->toFloat($_POST["cantidad_ppto"] ?? null);
            $fechaInicio = date("Y-m-d", strtotime($_POST["Fecha_Inicio"]));
            $fechaFin = date("Y-m-d", strtotime($_POST["Fecha_Fin"]));
            
            if ($ejecutado !== null) {
                if ($ejecutado < 0 || $ejecutado > 1) {
                    throw new Exception("El valor de Ejecutado debe estar entre 0 y 100%.");
                }
                $ejecutado = round($ejecutado, 4);
            }

            if ($cantidadPpto !== null) {
                if ($cantidadPpto < 0) throw new Exception("La cantidad en presupuesto no puede ser negativa.");
                $cantidadPpto = round($cantidadPpto, 1);
                if ($cantidadPpto === 0.0) $cantidadPpto = null;
            }

            // 3. Productividad (Legacy: forzado a 0)
            $medirProductividad = 0;

            // 4. Update Principal
            $sql = "UPDATE {$dbPrefix}_programa_consolidado SET 
                    Activa = 1,
                    Ejecutado = ?, 
                    medir_productividad = ?, 
                    unidad = ?, 
                    cantidad_ppto = ?, 
                    codigo_actividad = ?, 
                    Ejecutado_Siguiente_Semana = ?, 
                    Fecha_Inicio = ?, 
                    Fecha_Fin = ? 
                    WHERE Consecutivo_en_Programa = ? AND Semana = ?";
            
            $this->db->prepare($sql)->execute([
                $ejecutado, $medirProductividad, $unidad, $cantidadPpto, 
                $codigoActividad, $ejecutado, $fechaInicio, $fechaFin, $id, $semana
            ]);

            // 5. Recalcular Estado
            $ctxStmt = $this->db->prepare("SELECT Titulo FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?");
            $ctxStmt->execute([$id, $semana]);
            $row = $ctxStmt->fetch(PDO::FETCH_ASSOC);

            $stmtFechas = $this->db->prepare("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?");
            $stmtFechas->execute([$semana]);
            $inicioSemanaRow = $stmtFechas->fetch(PDO::FETCH_ASSOC);
            
            $fechaCorte = $inicioSemanaRow['Fecha_Inicio_Sem'] ?? date('Y-m-d');
            $fechaFinSemana = $inicioSemanaRow['Fecha_Fin_Sem'] ?? null;

            $nuevoEstado = $this->lpsService->calculateGeneralStatus($row['Titulo'], $ejecutado, $fechaInicio, $fechaFin, $fechaCorte, $fechaFinSemana);
            $semanasInicio = $this->lpsService->toTimestamp($fechaInicio) !== null ? round(($this->lpsService->toTimestamp($fechaInicio) - $this->lpsService->toTimestamp($fechaCorte)) / (7 * 86400)) : null;

            $this->db->prepare("UPDATE {$dbPrefix}_programa_consolidado SET Estado = ?, Semanas_Inicio = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?")
                     ->execute([$nuevoEstado, $semanasInicio, $id, $semana]);

            echo json_encode(['respuesta' => 'BIEN', 'estado' => $nuevoEstado, 'Semanas_Inicio' => $semanasInicio]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
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
            $excelData = $spreadsheet->getActiveSheet()->toArray();
            $todoElExcel = $excelData; 
            array_shift($excelData); 

            // 1. Detección inteligente de semana inicial
            $stmtMax = $this->db->prepare("SELECT MAX(Semana) as max_sem FROM {$dbPrefix}_programa_consolidado");
            $stmtMax->execute();
            $maxSem = (int)($stmtMax->fetch(PDO::FETCH_ASSOC)['max_sem'] ?? 0);
            
            // Si no hay datos, forzamos Semana 1, de lo contrario usamos el incremento correlativo
            $semanaNueva = ($maxSem === 0) ? 1 : ($semana + 1);

            $logFile = PROJECT_ROOT . "/public/debug_import.log";
            $debug = function($msg) use ($logFile) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
            };

            $debug("DEBUG IMPORT: Semana actual detectada en DB: $maxSem. Semana destino: $semanaNueva");
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

            $historico = $this->getPreviousWeekData($dbPrefix, $semana);
            $debug("DEBUG IMPORT: Registros históricos encontrados: " . count($historico));

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

                $titulo = (isset($row[2]) && (stripos($row[2], 'S') !== false || $row[2] == '1')) ? 1 : 0;
                $fInicio = !empty($row[3]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[3]))) : null;
                $fFin = !empty($row[4]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[4]))) : null;
                $rutaCritica = (isset($row[5]) && (stripos($row[5], 'S') !== false || $row[5] == '1')) ? 1 : 0;

                $prev = $historico[$nombreLimpio] ?? [];
                $itemsParaInsertar[] = [
                    'Semana' => $semanaNueva, 'Consecutivo_en_Programa' => $consecutivoEnProg++,
                    'Id' => $esquema, 'Actividad' => $nombreActividadHtml, 'Titulo' => $titulo,
                    'Fecha_Inicio' => $fInicio, 'Fecha_Fin' => $fFin, 'Ruta_Critica' => $rutaCritica,
                    'Ejecutado' => $prev['Ejecutado'] ?? null, 'Responsable_AIA' => $prev['Responsable_AIA'] ?? null,
                    'Sub_Contratista' => $prev['Sub_Contratista'] ?? null, 'Observaciones' => $prev['Observaciones'] ?? null,
                    'codigo_actividad' => $prev['codigo_actividad'] ?? null, 'medir_productividad' => $prev['medir_productividad'] ?? null,
                    'cantidad_ppto' => $prev['cantidad_ppto'] ?? null, 'unidad' => $prev['unidad'] ?? null,
                ];
            }

            $this->db->beginTransaction();

            // 1. Borrar registros de la semana destino si existen (sobrescribir)
            $this->db->prepare("DELETE FROM {$dbPrefix}_programa_consolidado WHERE Semana = ?")->execute([$semanaNueva]);

             // 2. Insertar nuevos registros
            $queryInsert = "INSERT INTO {$dbPrefix}_programa_consolidado (
                Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 
                Ejecutado, Responsable_AIA, Sub_Contratista, Observaciones, codigo_actividad, medir_productividad, cantidad_ppto, unidad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $this->db->prepare($queryInsert);

            foreach ($itemsParaInsertar as $item) {
                $stmtInsert->execute(array_values($item));
            }

            // 3. Activar semana si no existe (indispensable para visualización)
            $stmtCheckSem = $this->db->prepare("SELECT COUNT(*) FROM {$dbPrefix}_semanas_activas WHERE Semana = ?");
            $stmtCheckSem->execute([$semanaNueva]);
            if ($stmtCheckSem->fetchColumn() == 0) {
                $f_final_sem = date('Y-m-d', strtotime($f_inicio_sem . ' + 6 days'));
                $stmtInsertSem = $this->db->prepare("INSERT INTO {$dbPrefix}_semanas_activas (Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (?, ?, ?, ?)");
                $stmtInsertSem->execute([$semanaNueva, $f_inicio_sem, $f_final_sem, date('Y-m-d')]);
                $debug("DEBUG IMPORT: Creada nueva semana activa: $semanaNueva ($f_inicio_sem a $f_final_sem)");
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

            echo json_encode(['respuesta' => 'BIEN', 0 => (int)$semanaNueva]);

        } catch (\Throwable $e) {
            if (isset($debug)) {
                $debug("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
            }
            if ($this->db->inTransaction()) $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['respuesta' => 'ERROR', 'mensaje' => $e->getMessage()]);
        }
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
            $semana = $_POST['semana'] ?? ($vars['semana'] ?? 0);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
                throw new Exception("Base de datos inválida.");
            }

            $sql = "UPDATE {$dbPrefix}_programa_consolidado SET 
                    Ejecutado = 0, Responsable_AIA = NULL, Sub_Contratista = NULL, Observaciones = NULL
                    WHERE Semana = ?";
            
            $this->db->prepare($sql)->execute([$semana]);
            $this->updateBatch();

            echo json_encode(['respuesta' => 'BIEN']);

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
            $key = strip_tags($row['Actividad']);
            $mapped[$key] = $row;
        }
        return $mapped;
    }
}
