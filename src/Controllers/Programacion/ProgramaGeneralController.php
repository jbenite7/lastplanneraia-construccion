<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;
use App\Services\ProjectLandingService;

class ProgramaGeneralController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $this->healWeeklyContext();

        $vars = $this->getSessionVars();
        $dbName = $vars['dbName'] ?? '';
        $semana = $vars['semana'] ?? 0;
        $proyecto = $vars['proyecto'] ?? '';
        $permiso = $vars['permiso'] ?? '';
        $pdcActivo = $vars['pdcActivo'] ?? '';
        $area = $vars['area'] ?? 'Construccion';

        $maxSemana = 0;
        $fechaInicioSem = '';
        $fechaFinSem = '';
        $fechaInicioSemYMD = '';
        $fechaFinSemYMD = '';
        $fechaDatepicker = '';
        $semanalConfirmada = '';
        $fechaCierreCompromisos = '';
        $fechaCreacionSemana = '';
        $versionCronograma = '';
        $restrictionConfig = null;

        try {
            if (!empty($dbName) && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                $sqlUltima = "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbName}_semanas_activas WHERE Semana = (SELECT MAX(Semana) FROM {$dbName}_semanas_activas)";
                $stmtUltima = $this->db->query($sqlUltima);
                $dataUltima = $stmtUltima->fetch();

                if ($dataUltima) {
                    $maxSemana = $dataUltima["Semana"];
                    $fechaInicioSemYMD = $dataUltima["Fecha_Inicio_Sem"];
                    $fechaFinSemYMD = $dataUltima["Fecha_Fin_Sem"];

                    $fechaInicioSem = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Inicio_Sem"]));
                    $fechaFinSem = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));
                    $fechaDatepicker = date("Y, n - 1, d, H, i, s", strtotime($dataUltima["Fecha_Fin_Sem"]));
                }

                $sqlDetalles = "SELECT Semanal_Confirmada, fechaCierreCompromisos, fechaCreacionSemana, 
                               (SELECT SUM(reprogramacion) FROM {$dbName}_semanas_activas WHERE Semana <= ?) AS versionCronograma 
                               FROM {$dbName}_semanas_activas WHERE Semana = ?";
                $stmtDetalles = $this->db->prepare($sqlDetalles);
                $stmtDetalles->execute([$semana, $semana]);
                $dataDetalles = $stmtDetalles->fetch();

                if ($dataDetalles) {
                    $semanalConfirmada = $dataDetalles["Semanal_Confirmada"];
                    $fechaCierreCompromisos = $dataDetalles["fechaCierreCompromisos"];
                    $fechaCreacionSemana = $dataDetalles["fechaCreacionSemana"];
                    $versionCronograma = $dataDetalles["versionCronograma"];
                }
            }
        } catch (\PDOException $e) {
            error_log("Error cargando variables ProgramaGeneral: " . $e->getMessage());
        }

        // Pre-construction: build restriction config for server-side injection
        if ($area === 'Pre-Construccion') {
            $pcLabel2 = null;
            $pcLabel3 = null;
            $pcLabel4 = null;

            try {
                if (!empty($dbName) && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                    $stmtPc = $this->db->prepare(
                        "SELECT pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre
                         FROM general_proyectos_procesos
                         WHERE Base_de_Datos = ?
                         LIMIT 1"
                    );
                    $stmtPc->execute([$dbName]);
                    $proyectoPc = $stmtPc->fetch(\PDO::FETCH_ASSOC);

                    if ($proyectoPc) {
                        $pcLabel2 = !empty($proyectoPc['pc_restr_2_nombre']) ? $proyectoPc['pc_restr_2_nombre'] : null;
                        $pcLabel3 = !empty($proyectoPc['pc_restr_3_nombre']) ? $proyectoPc['pc_restr_3_nombre'] : null;
                        $pcLabel4 = !empty($proyectoPc['pc_restr_4_nombre']) ? $proyectoPc['pc_restr_4_nombre'] : null;
                    }
                }
            } catch (\PDOException $e) {
                error_log("Error cargando restricciones PC: " . $e->getMessage());
            }

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

            if ($pcLabel2 !== null) {
                $restrictions[] = [
                    'key'       => 'restriccion_pc_2',
                    'label'     => $pcLabel2,
                    'type'      => 'soft',
                    'threshold' => 100,
                    'options'   => ['0%', '50%', '100%', 'N/A'],
                ];
                $softRestrictions[] = 'restriccion_pc_2';
            }
            if ($pcLabel3 !== null) {
                $restrictions[] = [
                    'key'       => 'restriccion_pc_3',
                    'label'     => $pcLabel3,
                    'type'      => 'soft',
                    'threshold' => 100,
                    'options'   => ['0%', '50%', '100%', 'N/A'],
                ];
                $softRestrictions[] = 'restriccion_pc_3';
            }
            if ($pcLabel4 !== null) {
                $restrictions[] = [
                    'key'       => 'restriccion_pc_4',
                    'label'     => $pcLabel4,
                    'type'      => 'soft',
                    'threshold' => 100,
                    'options'   => ['0%', '50%', '100%', 'N/A'],
                ];
                $softRestrictions[] = 'restriccion_pc_4';
            }

            $restrictionConfig = [
                'area' => 'Pre-Construccion',
                'restrictions' => $restrictions,
                'hardRestrictions' => ['restriccion_pc_1'],
                'softRestrictions' => $softRestrictions,
            ];
        }

        require PROJECT_ROOT . '/views/programa-general/programa_general.view.php';
    }

    private function healWeeklyContext(): void
    {
        $dbName = (string) ($_SESSION['db'] ?? '');

        if ($dbName === '' || preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
            header('Location: /proyectos');
            exit;
        }

        $currentWeek = (int) ($_SESSION['semana'] ?? 0);
        $landingService = new ProjectLandingService();
        $context = $landingService->sanitizeWeek($dbName, $currentWeek);

        if (!$context['hasActiveWeeks']) {
            $_SESSION['semana'] = 0;
            header('Location: /programa-general-actualizar');
            exit;
        }

        if ($currentWeek !== (int) $context['week']) {
            $_SESSION['semana'] = (int) $context['week'];
        }
    }

    /**
     * AJAX Endpoint: Obtiene los contadores para los filtros del programa general
     * Reemplaza: actualizarFiltros.php
     */
    public function getFilters()
    {
        $this->requireAuth();

        // Ensure request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Method Not Allowed"]);

            return;
        }

        $dbPrefix = $_POST['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

        // Validación del prefijo (seguridad básica)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Prefijo de base de datos inválido."]);

            return;
        }

        // Limpiar variables de sesión 'intermedia' (legacy behavior from original script)
        unset(
            $_SESSION["lookahead_intermedia"],
            $_SESSION["no_iniciadas_intermedia"],
            $_SESSION["a_tiempo_intermedia"],
            $_SESSION["terminadas_intermedia"],
        );

        $arreglo = [];
        $sessionKeys = ['lookahead', 'no_iniciadas', 'a_tiempo', 'atrasadas', 'terminadas'];

        // Cargar estado actual de los filtros desde la sesión
        foreach ($sessionKeys as $key) {
            $arreglo['activa_' . $key] = (isset($_SESSION[$key]) && $_SESSION[$key] == 1) ? 1 : 0;
        }

        // Consultas para contar registros por estado persistido (fuente única)
        $query = "SELECT 
            COALESCE(SUM(CASE WHEN (Estado = 'Actividad Futura' OR Estado = 'En Liberación de Restricciones' OR Estado = 'No Requerida') THEN 1 ELSE 0 END), 0) AS lookahead,
            COALESCE(SUM(CASE WHEN Estado = 'Debe Iniciar' THEN 1 ELSE 0 END), 0) AS no_iniciadas,
            COALESCE(SUM(CASE WHEN (Estado = 'En Curso' OR Estado = 'A Tiempo') THEN 1 ELSE 0 END), 0) AS a_tiempo,
            COALESCE(SUM(CASE WHEN (Estado = 'Atrasada' OR Estado = 'Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END), 0) AS atrasadas,
            COALESCE(SUM(CASE WHEN (Estado = 'Terminada' OR Estado = 'Terminada Antes') THEN 1 ELSE 0 END), 0) AS terminadas,
            COUNT(*) AS total
            FROM {$dbPrefix}_programa_consolidado 
            WHERE Semana = ? AND Titulo = 0";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$semana]);
            $data = $stmt->fetch();

            if ($data) {
                $arreglo = array_merge($arreglo, $data);
                $arregloFinal["data"] = $arreglo;
                header('Content-Type: application/json');
                echo json_encode($arregloFinal, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudieron obtener los filtros."]);
            }
        } catch (\Exception $e) {
            error_log("Error en ProgramaGeneralController::getFilters: " . $e->getMessage());
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
        }
    }

    /**
     * Actualiza la variable de sesión para un filtro específico y redirige
     * Reemplaza: clase_filtro.php
     */
    public function setFilter()
    {
        $this->requireAuth();

        $clase = $_GET["clase"] ?? '';
        $activa = $_GET["activa"] ?? 0;

        // Reset general logic
        if ($clase == "total") {
            $_SESSION["lookahead"] = 0;
            $_SESSION["no_iniciadas"] = 0;
            $_SESSION["a_tiempo"] = 0;
            $_SESSION["atrasadas"] = 0;
            $_SESSION["terminadas"] = 0;
            $_SESSION["total"] = 1;
        } else {
            $_SESSION["total"] = 0;
        }

        // Logic for specific filters
        $validFilters = ['lookahead', 'no_iniciadas', 'a_tiempo', 'atrasadas', 'terminadas'];

        if (in_array($clase, $validFilters)) {
            $_SESSION[$clase] = ($activa == 1) ? 1 : 0;
        }

        // Redireccionar al programa general (Legacy behavior)
        header("Location: /programa-general");
        exit;
    }
}
