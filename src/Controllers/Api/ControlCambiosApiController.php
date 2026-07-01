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
            $queryCount = "SELECT COUNT(*) as total FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cambios') . "";
            $conteo = $this->db->queryWithProject($queryCount)->fetchColumn() ?? 0;

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
                $queryData = "SELECT id, solicitanteCambio, detalleSolicitanteOtro, fechaSolicitud, prioridad, tipoCambio, responsableSolucion, detalleResponsableSolucion, justificacion, descripcion, incidenciaAlcance, tiempoCronograma, tiempoCronogramaAfectado, incidenciaCronograma, valorPresupuesto, costoDirecto, costoDirectoAIU, costoDirectoAIUIVA, valorAprobado, incidenciaPresupuesto, incidenciaCalidad, incidenciaRiesgo, incidenciaRecurso, fechaTentativaDefinicion, fechaEntregaInterventoria, Observaciones, fechaDefinicion, aprobacion, soportes FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cambios') . "";
                $arreglo["data"] = $this->db->queryWithProject($queryData)->fetchAll(PDO::FETCH_ASSOC);
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
        $params = $this->getPostParams();
        $query = "INSERT INTO " . TableResolver::resolveByPrefix($dbPrefix, 'cambios') . " (id, solicitanteCambio, detalleSolicitanteOtro, fechaSolicitud, prioridad, tipoCambio, responsableSolucion, detalleResponsableSolucion, justificacion, descripcion, incidenciaAlcance, tiempoCronograma, tiempoCronogramaAfectado, incidenciaCronograma, valorPresupuesto, costoDirecto, costoDirectoAIU, costoDirectoAIUIVA, valorAprobado, incidenciaPresupuesto, incidenciaCalidad, incidenciaRiesgo, incidenciaRecurso, fechaTentativaDefinicion, fechaEntregaInterventoria, fechaDefinicion, aprobacion, soportes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $res = $this->db->queryWithProject($query, $params);
        if ($res) {
            $this->db->logActivity('ControlCambios', 'CREAR', "Creó solicitud de cambio ID {$params[0]}", $dbPrefix);
        }
        $this->json(["respuesta" => $res ? "BIEN" : "ERROR"]);
    }

    private function modificar(string $dbPrefix): void
    {
        $params = $this->getPostParams();
        // Shift consecutivo to the end for WHERE id=?
        $id = array_shift($params);
        $params[] = $id;

        $query = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'cambios') . " SET solicitanteCambio=?, detalleSolicitanteOtro=?, fechaSolicitud=?, prioridad=?, tipoCambio=?, responsableSolucion=?, detalleResponsableSolucion=?, justificacion=?, descripcion=?, incidenciaAlcance=?, tiempoCronograma=?, tiempoCronogramaAfectado=?, incidenciaCronograma=?, valorPresupuesto=?, costoDirecto=?, costoDirectoAIU=?, costoDirectoAIUIVA=?, valorAprobado=?, incidenciaPresupuesto=?, incidenciaCalidad=?, incidenciaRiesgo=?, incidenciaRecurso=?, fechaTentativaDefinicion=?, fechaEntregaInterventoria=?, Observaciones=NULL, fechaDefinicion=?, aprobacion=?, soportes=? WHERE id=?";

        $res = $this->db->queryWithProject($query, $params);
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
        $id = $_POST["Id"] ?? 0;
        $res = $this->db->queryWithProject("DELETE FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cambios') . " WHERE id = ?", [$id]);
        if ($res) {
            $this->db->logActivity('ControlCambios', 'ELIMINAR', "Eliminó solicitud cambio ID $id", $dbPrefix);
        }
        $this->json(["respuesta" => $res ? "BIEN" : "ERROR"]);
    }

    private function obtenerNombreDirector(string $dbPrefix): void
    {
        $nombre = $this->db->queryWithProject("SELECT nombre FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE cargo = 'Director de Obra' LIMIT 1")->fetchColumn();
        echo json_encode($nombre ?: '', JSON_UNESCAPED_UNICODE);
    }

    private function obtenerURLCambios(string $dbPrefix): void
    {
        $url = $this->db->queryWithProject("SELECT urlCambios FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1", [$dbPrefix])->fetchColumn();
        echo json_encode($url ?: '', JSON_UNESCAPED_UNICODE);
    }

    private function actualizarFechaInicio(string $dbPrefix): void
    {
        $id = $_POST["idActividad"] ?? 0;
        $semana = $_POST["semana"] ?? 0;
        $fecha = $this->db->queryWithProject("SELECT Fecha_Inicio FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . " WHERE unique_id = ? AND Semana = ?", [$id, $semana])->fetchColumn();
        $this->json(["data" => ["Fecha_Inicio" => $fecha]]);
    }

    private function nueva_sem(string $dbPrefix): void
    {
        $f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"]));
        // Replicating legacy require logic
        require_once PROJECT_ROOT . '/src/Legacy/nueva_semana.php';
        require_once PROJECT_ROOT . '/src/Legacy/modificar_sem_estado.php';

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
}
