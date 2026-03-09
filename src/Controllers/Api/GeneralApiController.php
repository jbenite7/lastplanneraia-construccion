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

}
