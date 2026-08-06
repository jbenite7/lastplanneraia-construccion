<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

use TableResolver;
class ControlCambiosApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.control_cambios.ver');
        $dbPrefix = $_GET['db'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["error" => "Parámetro de base de datos inválido."]);
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $queryCount = "SELECT COUNT(*) as total FROM cambios WHERE project_id = ?";
            $conteo = $this->db->queryWithProject($queryCount, [$projectId], $projectId)->fetchColumn() ?? 0;

            if ($conteo == 0) {
                $arreglo["data"][] = [
                    "id" => "", "solicitanteCambio" => "", "detalleSolicitanteOtro" => "", "fechaSolicitud" => "",
                    "prioridad" => "", "tipoCambio" => "", "responsableSolucion" => "", "detalleResponsableSolucion" => "",
                    "justificacion" => "", "descripcion" => "", "incidenciaAlcance" => "", "tiempoCronograma" => "",
                    "tiempoCronogramaAfectado" => "", "incidenciaCronograma" => "", "costoDirecto" => "", "valorPresupuesto" => "",
                    "costoDirectoAIU" => "", "costoDirectoAIUIVA" => "", "valorAprobado" => "", "incidenciaPresupuesto" => "",
                    "incidenciaCalidad" => "", "incidenciaRiesgo" => "", "incidenciaRecurso" => "", "fechaTentativaDefinicion" => "",
                    "fechaEntregaInterventoria" => "", "Observaciones" => "", "fechaDefinicion" => "", "aprobacion" => "",
                    "soportes" => "{\"soportes\": [{\"consecutivo\":1,\"descripcion\":\"\",\"link\":\"\"}]}",
                ];
            } else {
                $queryData = "SELECT id, solicitanteCambio, detalleSolicitanteOtro, fechaSolicitud, prioridad, tipoCambio, responsableSolucion, detalleResponsableSolucion, justificacion, descripcion, incidenciaAlcance, tiempoCronograma, tiempoCronogramaAfectado, incidenciaCronograma, valorPresupuesto, costoDirecto, costoDirectoAIU, costoDirectoAIUIVA, valorAprobado, incidenciaPresupuesto, incidenciaCalidad, incidenciaRiesgo, incidenciaRecurso, fechaTentativaDefinicion, fechaEntregaInterventoria, Observaciones, fechaDefinicion, aprobacion, soportes FROM cambios WHERE project_id = ?";
                $arreglo["data"] = $this->db->queryWithProject($queryData, [$projectId], $projectId)->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->json($arreglo);
        } catch (Throwable $t) {
            $this->json(["error" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.control_cambios.editar');
        legacy_require_csrf('control-cambios');
        $dbPrefix = $_GET['db'] ?? '';
        $opcion = $_POST["opcion"] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Prefijo de base de datos inválido."]);
            return;
        }

        try {
            switch ($opcion) {
                case 'nuevo':
                    $this->nuevo($dbPrefix);
                    break;
                case 'modificar':
                    $this->modificar($dbPrefix);
                    break;
                case 'eliminar':
                    $this->eliminar($dbPrefix);
                    break;
                case 'obtenerNombreDirector':
                    $this->obtenerNombreDirector($dbPrefix);
                    break;
                case 'obtenerURLCambios':
                    $this->obtenerURLCambios($dbPrefix);
                    break;
                case 'actualizarFechaInicio':
                    $this->actualizarFechaInicio($dbPrefix);
                    break;
                case 'nueva_sem':
                    $this->nueva_sem($dbPrefix);
                    break;
                case 'eliminar_sem':
                    $this->eliminar_sem($dbPrefix);
                    break;
                default:
                    $this->json(["respuesta" => "ERROR", "mensaje" => "Opción no válida ($opcion)."]);
                    break;
            }
        } catch (Throwable $t) {
            $this->json(["respuesta" => "ERROR", "mensaje" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    private function nuevo(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $params = $this->getPostParams();
        $changeId = filter_var($params[0] ?? null, FILTER_VALIDATE_INT);
        if (!$changeId) {
            $changeId = $this->nextChangeId($dbPrefix, $projectId);
            $params[0] = $changeId;
        }
        array_unshift($params, $projectId);
        $query = "INSERT INTO cambios (project_id, id, solicitanteCambio, detalleSolicitanteOtro, fechaSolicitud, prioridad, tipoCambio, responsableSolucion, detalleResponsableSolucion, justificacion, descripcion, incidenciaAlcance, tiempoCronograma, tiempoCronogramaAfectado, incidenciaCronograma, valorPresupuesto, costoDirecto, costoDirectoAIU, costoDirectoAIUIVA, valorAprobado, incidenciaPresupuesto, incidenciaCalidad, incidenciaRiesgo, incidenciaRecurso, fechaTentativaDefinicion, fechaEntregaInterventoria, fechaDefinicion, aprobacion, soportes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $res = $this->db->queryWithProject($query, $params, $projectId);
        if ($res) {
            $this->db->logActivity('ControlCambios', 'CREAR', "Creó solicitud de cambio ID {$changeId}", $dbPrefix);
        }
        $this->json(["respuesta" => $res ? "BIEN" : "ERROR"]);
    }

    private function modificar(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $params = $this->getPostParams();
        // Shift consecutivo to the end for WHERE id=?
        $id = array_shift($params);
        $params[] = $projectId;
        $params[] = $id;

        $query = "UPDATE cambios SET solicitanteCambio=?, detalleSolicitanteOtro=?, fechaSolicitud=?, prioridad=?, tipoCambio=?, responsableSolucion=?, detalleResponsableSolucion=?, justificacion=?, descripcion=?, incidenciaAlcance=?, tiempoCronograma=?, tiempoCronogramaAfectado=?, incidenciaCronograma=?, valorPresupuesto=?, costoDirecto=?, costoDirectoAIU=?, costoDirectoAIUIVA=?, valorAprobado=?, incidenciaPresupuesto=?, incidenciaCalidad=?, incidenciaRiesgo=?, incidenciaRecurso=?, fechaTentativaDefinicion=?, fechaEntregaInterventoria=?, Observaciones=NULL, fechaDefinicion=?, aprobacion=?, soportes=? WHERE project_id = ? AND id=?";

        $res = $this->db->queryWithProject($query, $params, $projectId);
        if ($res) {
            $this->db->logActivity('ControlCambios', 'MODIFICAR', "Modificó solicitud de cambio ID $id", $dbPrefix);
        }
        $this->json(["respuesta" => $res ? "BIEN" : "ERROR"]);
    }

    private function getPostParams(): array
    {
        $cleanNum = function ($v) {
            return floatval(str_replace(['$', ','], '', $v ?? 0));
        };

        $consecutivo = $_POST['inputConsecutivo'] ?? null;
        $solicitante = $_POST['inputSolicitanteCambio'] ?? null;
        $detalleSolicitante = $_POST['inputDetalleSolicitanteOtro'] ?? null;
        $fechaSolicitud = !empty($_POST['inputFechaSolicitud']) ? $_POST['inputFechaSolicitud'] : null;
        $prioridad = $_POST['inputPrioridad'] ?? null;

        $tiposArr = [
            "Alcance" => $_POST['inputTipoCambioAlcance'] ?? 0,
            "Cronograma" => $_POST['inputTipoCambioCronograma'] ?? 0,
            "Costo" => $_POST['inputTipoCambioCosto'] ?? 0,
            "Calidad" => $_POST['inputTipoCambioCalidad'] ?? 0,
            "Riesgo" => $_POST['inputTipoCambioRiesgo'] ?? 0,
            "Recurso" => $_POST['inputTipoCambioRecurso'] ?? 0,
        ];
        $tipoCambio = json_encode(["tiposCambio" => $tiposArr]);

        return [
            $consecutivo, $solicitante, $detalleSolicitante, $fechaSolicitud, $prioridad, $tipoCambio,
            $_POST['inputResponsableSolucion'] ?? null,
            $_POST['inputDetalleResponsableSolucion'] ?? null,
            $_POST['inputJustificacion'] ?? null,
            $_POST['inputDescripcion'] ?? null,
            $_POST['inputIncidenciaAlcance'] ?? null,
            $cleanNum($_POST['inputTiempoCronograma'] ?? 0),
            $cleanNum($_POST['inputTiempoCronogramaAfectado'] ?? 0),
            $_POST['inputIncidenciaCronograma'] ?? null,
            $cleanNum($_POST['inputValorPresupuesto'] ?? 0),
            $cleanNum($_POST['inputCostoDirecto'] ?? 0),
            $cleanNum($_POST['inputCostoDirectoAIU'] ?? 0),
            $cleanNum($_POST['inputCostoDirectoAIUIVA'] ?? 0),
            $cleanNum($_POST['inputValorAprobado'] ?? 0),
            $_POST['inputIncidenciaPresupuesto'] ?? null,
            $_POST['inputIncidenciaCalidad'] ?? null,
            $_POST['inputIncidenciaRiesgo'] ?? null,
            $_POST['inputIncidenciaRecurso'] ?? null,
            !empty($_POST['inputFechaTentativaDefinicion']) ? $_POST['inputFechaTentativaDefinicion'] : null,
            !empty($_POST['inputFechaEntregaInterventoria']) ? $_POST['inputFechaEntregaInterventoria'] : null,
            !empty($_POST['inputFechaDefinicion']) ? $_POST['inputFechaDefinicion'] : null,
            $_POST['inputAprobacion'] ?? null,
            $_POST['soportes'] ?? null,
        ];
    }

    private function eliminar(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $id = $_POST["Id"] ?? 0;
        $res = $this->db->queryWithProject("DELETE FROM cambios WHERE project_id = ? AND id = ?", [$projectId, $id], $projectId);
        if ($res) {
            $this->db->logActivity('ControlCambios', 'ELIMINAR', "Eliminó solicitud cambio ID $id", $dbPrefix);
        }
        $this->json(["respuesta" => $res ? "BIEN" : "ERROR"]);
    }

    private function obtenerNombreDirector(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $nombre = $this->db->queryWithProject(
            "SELECT nombre FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE project_id = ? AND cargo = 'Director de Obra' LIMIT 1",
            [$projectId],
            $projectId,
        )->fetchColumn();
        echo json_encode($nombre ?: '', JSON_UNESCAPED_UNICODE);
    }

    private function obtenerURLCambios(string $dbPrefix): void
    {
        $url = $this->db->queryWithProject("SELECT urlCambios FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1", [$dbPrefix])->fetchColumn();
        echo json_encode($url ?: '', JSON_UNESCAPED_UNICODE);
    }

    private function actualizarFechaInicio(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $id = $_POST["idActividad"] ?? 0;
        $semana = $_POST["semana"] ?? 0;
        $fecha = $this->db->queryWithProject(
            "SELECT Fecha_Inicio FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND unique_id = ? AND Semana = ?",
            [$projectId, $id, $semana],
            $projectId,
        )->fetchColumn();
        $this->json(["data" => ["Fecha_Inicio" => $fecha]]);
    }

    private function nueva_sem(string $dbPrefix): void
    {
        $f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"]));
        // `nueva_semana.php` incluye él mismo `modificar_sem_estado.php` al final de su camino
        // feliz, y sólo ahí: es un script include-scoped que lee `$dbName` y `$semana` del ámbito
        // que lo incluye, y `nueva_semana.php` las asigna justo antes. Requerirlo también desde
        // aquí no aporta nada cuando todo va bien (el `_once` lo convierte en no-op) y es dañino
        // cuando no: en las tres salidas de `nueva_semana.php` que no llegan a su include —el
        // `return` por Programa Maestro vacío, la rama de semana anterior sin confirmar y su
        // `catch`— este ámbito no tiene esas variables y el legacy muere con un TypeError que
        // se cuela como un segundo JSON en una respuesta ya emitida.
        require_once PROJECT_ROOT . '/src/Legacy/nueva_semana.php';

        $this->db->logActivity('ControlCambios', 'NUEVA_SEMANA', "Creó nueva semana iniciada el $f_inicio_sem", $dbPrefix);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function eliminar_sem(string $dbPrefix): void
    {
        $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
        require_once PROJECT_ROOT . '/src/Legacy/eliminar_semana.php';

        $this->db->logActivity('ControlCambios', 'ELIMINAR_SEMANA', "Eliminó semana $semana", $dbPrefix);
        $this->json(["respuesta" => "BIEN"]);
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function projectId(string $dbPrefix): int
    {
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        if (!$projectId) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        return $projectId;
    }

    private function nextChangeId(string $dbPrefix, int $projectId): int
    {
        return (int) $this->db->queryWithProject(
            "SELECT COALESCE(MAX(id), 0) + 1 FROM cambios WHERE project_id = ?",
            [$projectId],
            $projectId,
        )->fetchColumn();
    }
}
