<?php

namespace App\Controllers\Api;

use App\Core\Lps\LpsService;
use App\Security\CsrfTokenManager;
use App\Security\LpsWeekEditPolicy;
use App\Services\ProgramChangeDetector;
use App\Services\ProgramaConsolidadoNormalizationService;
use App\Services\RestrictionConfigResolver;
use App\Services\WeeklyRealProgressCarryoverService;
use PDO;
use Throwable;
use TableResolver;

// CommitmentLockGuard lives in global namespace (no `namespace` declaration),
// so PSR-4 autoloader cannot find it. Explicit require_once ensures it's
// available before any guard call in this controller.
if (!class_exists('\\CommitmentLockGuard', false)) {
    require_once PROJECT_ROOT . '/src/Core/CommitmentLockGuard.php';
}

class SemanalApiController
{
    private $db;
    private LpsService $lpsService;
    private WeeklyRealProgressCarryoverService $weeklyRealProgressCarryoverService;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->lpsService = new LpsService();
        $this->weeklyRealProgressCarryoverService = new WeeklyRealProgressCarryoverService($this->db, $this->lpsService);
    }

    /**
     * Resuelve nombre de tabla via TableResolver.
     */
    private function tbl(string $dbPrefix, string $tableType): string
    {
        return TableResolver::resolveByPrefix($dbPrefix, $tableType);
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');
        $dbPrefix = $_GET['db'] ?? '';
        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        if (!$this->validateContext($dbPrefix, $semana)) {
            return;
        }

        try {
            $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
            if (!$projectId) {
                $this->jsonError("Proyecto no encontrado.");
                return;
            }
            $queryCount = "SELECT COUNT(*) as total FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND (Activa = '1' OR Activa = 'NA')";
            $conteo = $this->db->query($queryCount, [$projectId, $semana])->fetchColumn() ?? 0;

            if ($conteo == 0) {
                $arreglo = ["data" => []];
            } else {
                $querySemanas = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ? LIMIT 1";
                $dataSemanas = $this->db->query($querySemanas, [$projectId, $semana])->fetch(PDO::FETCH_ASSOC);

                $Fecha_Inicio_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Inicio_Sem"] ?? 'now'));
                $Fecha_Fin_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Fin_Sem"] ?? 'now'));

                // Resolve restriction columns based on project Area
                try {
                    $restrConfig = RestrictionConfigResolver::resolve($dbPrefix);
                    $area = $restrConfig['area'];
                } catch (\Throwable $e) {
                    $area = 'Construccion';
                }
                $restrictionColumns = RestrictionConfigResolver::getAllRestrictionColumns($area);
                $restrictionSelects = array_map(
                    static fn(string $col) => "pc.{$col} AS restr_{$col}",
                    $restrictionColumns
                );
                $restrictionSelectSql = implode(', ', $restrictionSelects);

                $queryData = "SELECT ps.*,
                        ps.row_id AS Consecutivo,
                        ps.unique_id AS Consecutivo_En_Programa,
                        ps.unique_id,
                        {$restrictionSelectSql}
                    FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " ps
                    LEFT JOIN " . $this->tbl($dbPrefix, 'programa_consolidado') . " pc
                      ON pc.project_id = ps.project_id
                     AND ps.Semana = pc.Semana
                     AND ps.unique_id = pc.unique_id
                    WHERE ps.project_id = ? AND ps.Semana = ? AND (ps.Activa = '1' OR ps.Activa = 'NA')
                    ORDER BY ps.unique_id ASC, ps.Activa ASC, ps.row_id ASC";
                $stmtData = $this->db->query($queryData, [$projectId, $semana]);

                $arreglo = ["data" => []];
                while ($data = $stmtData->fetch(PDO::FETCH_ASSOC)) {
                    $this->calculateProjections($data, $Fecha_Inicio_Sem, $Fecha_Fin_Sem);
                    $arreglo["data"][] = $data;
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error del servidor: " . $t->getMessage());
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');

        $dbPrefix = $_GET['db'] ?? $_POST['db'] ?? '';
        $opcion = $_POST["opcion"] ?? '';
        $semana = filter_var($_POST['semana'] ?? $_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        // CSRF protection for mutating operations (not for read-only / listar endpoints)
        if (in_array($opcion, ['nuevo', 'modificar', 'eliminar', 'duplicar', 'autoprogramar', 'bloquear_compromisos', 'importar_actividad_no_requerida', 'EstadoEjecucion', 'tnp'], true)) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
            if (!CsrfTokenManager::validate($csrfToken, 'semanal_save')) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "Token CSRF inválido o expirado."], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError("Parámetro de base de datos inválido.");
            return;
        }
        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        $mutatingOptions = [
            'nuevo', 'modificar', 'eliminar', 'duplicar', 'autoprogramar',
            'bloquear_compromisos', 'importar_actividad_no_requerida',
            'EstadoEjecucion', 'tnp', 'sanear',
        ];
        if (in_array($opcion, $mutatingOptions, true) && (int) $semana <= 0) {
            $this->jsonError('Semana inválida.');
            return;
        }
        if (in_array($opcion, $mutatingOptions, true)
            && !$this->requireWeekEditPolicy($dbPrefix, (int) $semana, $opcion === 'modificar')) {
            return;
        }

        try {
            switch ($opcion) {
                case 'modificar':
                    $this->modificar($dbPrefix, $semana);
                    break;
                case 'EstadoEjecucion':
                    $this->estadoEjecucion($dbPrefix, $semana);
                    break;
                case 'eliminar':
                    $this->eliminar($dbPrefix, $semana);
                    break;
                case 'duplicar':
                    $this->duplicar($dbPrefix, $semana);
                    break;
                case 'nuevo':
                    $this->nuevo($dbPrefix, $semana);
                    break;
                case 'autoprogramar':
                    $this->autoprogramar($dbPrefix, $semana);
                    break;
                case 'bloquear_compromisos':
                    $this->bloquearCompromisos($dbPrefix, $semana);
                    break;
                case 'listar_excepciones_autoprogramacion':
                    $this->listarExcepciones($dbPrefix, $semana);
                    break;
                case 'importar_actividad_no_requerida':
                    $this->importarActividadNoRequerida($dbPrefix, $semana);
                    break;
                case 'sanear':
                    $this->sanear($dbPrefix, $semana);
                    break;
                case 'tnp':
                    $this->tnp($dbPrefix, $semana);
                    break;
                default:
                    $this->jsonError("Opción no válida.");
                    break;
            }
        } catch (Throwable $t) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonError("Error: " . $t->getMessage());
        }
    }

    private function validateContext(string $dbPrefix, int $semana): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError("Base de datos inválida.");
            return false;
        }
        if ($semana <= 0) {
            $this->jsonError("Semana inválida.");
            return false;
        }
        return true;
    }

    private function requireSessionDbPrefix(string $dbPrefix): bool
    {
        if ($dbPrefix !== '' && $dbPrefix === (string) ($_SESSION['db'] ?? '')) {
            return true;
        }
        $this->jsonError('El proyecto solicitado no coincide con la sesión activa.', 403);
        return false;
    }

    private function requireWeekEditPolicy(string $dbPrefix, int $week, bool $qualification = false): bool
    {
        if ((new LpsWeekEditPolicy($this->db))->allows($dbPrefix, $week, $qualification)) {
            return true;
        }
        $this->jsonError('La semana histórica no permite esta operación para su rol.', 403);
        return false;
    }

    private function calculateProjections(array &$data, string $fInicioSem, string $fFinSem): void
    {
        $data = $this->lpsService->calculateWeeklyProjections($data, $fInicioSem, $fFinSem);
    }

    private function modificar(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        $this->db->setProjectContext($projectId);

        $id = (int) ($_POST["Id"] ?? 0);
        if ($id <= 0 || $semana <= 0) {
            $this->jsonError('Actividad o semana inválida.');
            return;
        }

        $weeklyTable = $this->tbl($dbPrefix, 'programacion_semanal');
        $rowActual = $this->db->queryWithProject(
            "SELECT Semana, Es_TNP, Descripcion, Ubicacion, Empresa, Cantidad_Sugerida, Rendimientos,
                    Compromiso, Ejecutado_Real, Sub_Contratista, Responsable_AIA
             FROM {$weeklyTable} WHERE project_id = ? AND row_id = ?",
            [$projectId, $id],
            $projectId
        )->fetch(PDO::FETCH_ASSOC);
        if (!$rowActual || (int) $rowActual['Semana'] !== $semana) {
            $this->jsonError('La actividad no pertenece a la semana seleccionada.');
            return;
        }

        $weekState = $this->db->queryWithProject(
            "SELECT Semanal_Confirmada FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId
        )->fetch(PDO::FETCH_ASSOC);
        if (!$weekState) {
            $this->jsonError('La semana seleccionada no existe.');
            return;
        }

        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a actualizar.");
            return;
        }

        $compromiso = $this->lpsService->toFloat($_POST["Compromiso"] ?? null);
        $real = $this->lpsService->toFloat($_POST["Real"] ?? null);
        $compromisoActual = $this->lpsService->toFloat($rowActual['Compromiso'] ?? null);
        $realActual = $this->lpsService->toFloat($rowActual['Ejecutado_Real'] ?? null);
        $subcontractor = trim(explode(',', (string) ($_POST['Sub_Contratista'] ?? ''))[0]);
        $responsible = trim((string) ($_POST['Responsable_AIA'] ?? ''));
        $commitmentChanged = $this->nullableFloatChanged($compromiso, $compromisoActual);
        $realChanged = $this->nullableFloatChanged($real, $realActual);
        $assigneesChanged = $subcontractor !== trim((string) ($rowActual['Sub_Contratista'] ?? ''))
            || $responsible !== trim((string) ($rowActual['Responsable_AIA'] ?? ''));
        $description = trim((string) ($_POST['Descripcion'] ?? ''));
        $location = trim((string) ($_POST['Ubicacion'] ?? ''));
        $company = trim((string) ($_POST['Empresa'] ?? ''));
        $performance = trim((string) ($_POST['Rendimientos'] ?? ''));
        $suggested = $this->parseLocalizedFloat($_POST['Cantidad_Sugerida'] ?? null);
        $planningFieldsChanged = $description !== trim((string) ($rowActual['Descripcion'] ?? ''))
            || $location !== trim((string) ($rowActual['Ubicacion'] ?? ''))
            || $company !== trim((string) ($rowActual['Empresa'] ?? ''))
            || $performance !== trim((string) ($rowActual['Rendimientos'] ?? ''))
            || $this->nullableFloatChanged(
                $suggested,
                $this->lpsService->toFloat($rowActual['Cantidad_Sugerida'] ?? null),
            );
        $confirmed = (int) ($weekState['Semanal_Confirmada'] ?? 0) === 1;

        if ($realChanged && !$confirmed) {
            $this->jsonError('El avance real solo se registra en la fase de calificación.', 409);
            return;
        }
        if ($confirmed && ($commitmentChanged || $assigneesChanged || $planningFieldsChanged)) {
            $this->jsonError('Los datos de planificación solo se editan en programación.', 409);
            return;
        }
        if ($realChanged && ($subcontractor === '' || $responsible === '')) {
            $this->jsonError('Falta Sub-Contratista o Responsable AIA para registrar avance.');
            return;
        }

        $esTnp = (int) ($rowActual['Es_TNP'] ?? 0) === 1;
        if (!$esTnp && $compromiso !== null && $compromiso <= 0) {
            $this->jsonError("El compromiso no puede ser 0. Use CNP para desprogramar.");
            return;
        }
        $categoryCnc = trim((string) ($_POST['Categoria_CNC'] ?? ''));
        $causeCnc = trim((string) ($_POST['CNC'] ?? ''));
        $observationCnc = trim((string) ($_POST['Observaciones_CNC'] ?? ''));
        $otherCause = in_array($causeCnc, ['Otra', 'Otra...', 'Otros', 'Otros...'], true);
        $hasCauseDetail = ($causeCnc !== '' && !$otherCause) || $observationCnc !== '';
        if (!$esTnp && $real !== null && $compromiso !== null && $real < $compromiso
            && ($categoryCnc === '' || !$hasCauseDetail)) {
            $this->jsonError('El avance incumplido requiere categoría y causa CNC u observación.');
            return;
        }

        // Calculation of PAC/P_Completado (skip for TNP rows)
        $pac = null;
        $pCompletado = null;
        if (!$esTnp && $compromiso !== null && $real !== null && $compromiso > 0 && $real >= 0) {
            $pCompletado = ($real / $compromiso);
            $pac = ($real < $compromiso) ? 0 : 1;
        }

        $query = "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
            Descripcion = ?, Ubicacion = ?, Sub_Contratista = ?, Responsable_AIA = ?,
            Empresa = ?, Compromiso = ?, Cantidad_Sugerida = ?, Ejecutado_Real = ?,
            P_Completado = ?, PAC = ?, Rendimientos = ?,
            Categoria_CNC = ?, CNC = ?, Observaciones_CNC = ?
            WHERE project_id = ? AND row_id = ? AND Semana = ?";

        $catCnc = ($pac == 1) ? null : ($categoryCnc ?: null);
        $cnc = ($pac == 1) ? null : ($causeCnc ?: null);
        $obs = ($pac == 1) ? null : ($observationCnc ?: null);

        $params = [
            $description, $location, $subcontractor,
            $responsible, $company, $compromiso,
            $suggested, $real, $pCompletado, $pac, $performance ?: null,
            $catCnc, $cnc, $obs, $projectId, $id, $semana,
        ];

        $this->db->beginTransaction();
        $res = $this->db->queryWithProject($query, $params, $projectId);
        if (!$res || $res->rowCount() > 1) {
            $this->db->rollBack();
            $this->jsonError('No se actualizó la actividad semanal.');
            return;
        }
        if ($res->rowCount() === 1) {
            $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        }
        $this->db->commit();

        $this->jsonResponse("BIEN");
    }

private function autoprogramar(string $dbPrefix, int $semana): void
    {
        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->setProjectContext($projectId);
            \CommitmentLockGuard::guard($dbPrefix, $semana, 'autoprogramar');

            $area = $_SESSION['area'] ?? 'Construccion';
            $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql('', $area);

            // 1. Identificar actividades ya programadas
            $stmtExistentes = $this->db->queryWithProject("SELECT DISTINCT(unique_id) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ?", [$projectId, $semana], $projectId);
            $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);

            $whereExistentes = "";
            $paramsInsert = [$projectId, $semana];
            if (!empty($existentes)) {
                $placeholders = implode(',', array_fill(0, count($existentes), '?'));
                $whereExistentes = "AND unique_id NOT IN ($placeholders)";
                $paramsInsert = array_merge($paramsInsert, $existentes);
            }

            // 2. Insertar nuevas actividades desde el consolidado (Con Split)
            $sqlSelectNuevas = "SELECT
                {$semana}, unique_id, unique_id, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                Sub_Contratista, Responsable_AIA, 'AIA', Ejecutado, 0,
                Ruta_Critica,
                CASE WHEN (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END,
                '1', COALESCE(NULLIF(TRIM(unidad), ''), '%'), cantidad_ppto, codigo_actividad
            FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
            WHERE project_id = ? AND Semana = ? AND Titulo = 0
              AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
              AND (
                Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
              )
              $whereExistentes";

            $stmtNuevas = $this->db->queryWithProject($sqlSelectNuevas, $paramsInsert, $projectId);
            $nuevasFilas = $stmtNuevas->fetchAll(PDO::FETCH_NUM);

            if (!empty($nuevasFilas)) {
                $queryInsertSingle = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (
                    project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($nuevasFilas as $f) {
                    $subsRaw = $f[7] ?? '';
                    $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
                    if (empty($subs)) {
                        $subs = [''];
                    }
                    foreach ($subs as $sub) {
                        $f[7] = $sub;
                        $this->db->queryWithProject($queryInsertSingle, array_merge([$projectId], $f), $projectId);
                    }
                }
            }

            // 3. Actualizar detalles y compromisos (Preservando Subcontratista Split, sin tocar actividades con compromiso)
            $stmtSemanal = $this->db->queryWithProject("SELECT row_id AS Consecutivo, unique_id AS Consecutivo_En_Programa, Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Activa != 'NA' AND (Compromiso IS NULL OR Compromiso <= 0)", [$projectId, $semana], $projectId);
            $actividadesSemanales = $stmtSemanal->fetchAll();

            foreach ($actividadesSemanales as $item) {
                $con_pk = $item["Consecutivo"];
                $con_pg = $item["unique_id"] ?? $item["Consecutivo_En_Programa"];
                $sub_split = $item["Sub_Contratista"];

                $dataCons = $this->db->queryWithProject("SELECT *, row_id AS Consecutivo, unique_id AS Consecutivo_en_Programa FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Semana = ? AND unique_id = ?", [$projectId, $semana, $con_pg], $projectId)->fetch();
                if (!$dataCons) {
                    continue;
                }

                $dataAnt = $this->db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND unique_id = ? AND Sub_Contratista = ?", [$projectId, $semana - 1, $con_pg, $sub_split], $projectId)->fetch();
                if (!$dataAnt) {
                    $dataAnt = $this->db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND unique_id = ?", [$projectId, $semana - 1, $con_pg], $projectId)->fetch();
                }

                $sub = $sub_split ?: ($dataCons["Sub_Contratista"] ?? null);
                $resp = $dataCons["Responsable_AIA"] ?: ($dataAnt["Responsable_AIA"] ?? null);

                $sqlActSemana = "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
                    Fecha_Inicio = ?, Fecha_Fin = ?, Sub_Contratista = ?, Responsable_AIA = ?,
                    Ejecutado = ?, medir_productividad = ?, Critica = ?,
                    Atrasada = (CASE WHEN ? IN ('Atrasada', 'Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END),
                    Descripcion = ?, Ubicacion = ?, Empresa = ?, Unidad = COALESCE(NULLIF(TRIM(?), ''), '%'),
                    cantidad_ppto = ?, codigo_actividad = ?
                    WHERE project_id = ? AND Semana = ? AND row_id = ?";

                $this->db->queryWithProject($sqlActSemana, [
                    $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
                    (float) $dataCons['Ejecutado'], 0, (int) ($dataCons["Ruta_Critica"] ?? 0),
                    $dataCons["Estado"], $dataAnt["Descripcion"] ?? null, $dataAnt["Ubicacion"] ?? null,
                    $dataAnt["Empresa"] ?? 'AIA', $dataCons["unidad"],
                    ((float) ($dataCons["cantidad_ppto"] ?? 0) > 0 ? (float) $dataCons["cantidad_ppto"] : null),
                    $dataCons["codigo_actividad"], $projectId, $semana, $con_pk,
                ], $projectId);
            }

            // 4. Limpieza: actividades que ya no califican (sin compromiso ni avance real)
            $eligibleSubSql = "SELECT unique_id FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
                WHERE project_id = ? AND Semana = ? AND Titulo = 0
                  AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
                  AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                    OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";
            $this->db->queryWithProject("
                DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                WHERE project_id = ? AND Semana = ? AND Activa = '1'
                  AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
                  AND (Compromiso IS NULL OR Compromiso <= 0)
                  AND unique_id NOT IN ({$eligibleSubSql})
            ", [$projectId, $semana, $projectId, $semana], $projectId);

            $this->syncRestrictionFlags($dbPrefix, $semana, $area);

            // 5. Identificar actividades que no se autoprogramaron por restricciones pendientes y ejecución cero
            if ($area === 'Pre-Construccion') {
                $alertColumns = 'restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4';
            } else {
                $alertColumns = 'D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo';
            }
            $sqlRestricciones = "SELECT
                Id, Actividad, {$alertColumns}
            FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
            WHERE project_id = ? AND Semana = ? AND Titulo = 0
              AND COALESCE(Ejecutado, 0) <= 0.001
              AND NOT {$restrictionEligibilitySql}
              AND (
                Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
              )
              $whereExistentes";

            $stmtRest = $this->db->queryWithProject($sqlRestricciones, $paramsInsert, $projectId);
            $fallidas = $stmtRest->fetchAll(PDO::FETCH_ASSOC);

            $alertasRestricciones = [];
            if ($area === 'Pre-Construccion') {
                $hardRestrictionLabels = [
                    'restriccion_pc_1' => ['label' => 'Predecesora', 'threshold' => 0.5],
                ];
                $softRestrictionLabels = [
                    'restriccion_pc_2' => ['label' => 'Restricción PC 2', 'threshold' => 1.0],
                    'restriccion_pc_3' => ['label' => 'Restricción PC 3', 'threshold' => 1.0],
                    'restriccion_pc_4' => ['label' => 'Restricción PC 4', 'threshold' => 1.0],
                ];
            } else {
                $hardRestrictionLabels = [
                    'D_y_E' => ['label' => 'D. y Especificaciones', 'threshold' => 1.0],
                    'Materiales' => ['label' => 'Materiales', 'threshold' => 1.0],
                    'MdeO' => ['label' => 'Mano de Obra', 'threshold' => 1.0],
                    'Equipos' => ['label' => 'Equipos', 'threshold' => 1.0],
                    'Predecesora' => ['label' => 'Predecesora', 'threshold' => 0.5],
                ];
                $softRestrictionLabels = [
                    'Pdto_Cons' => ['label' => 'Pdto. Constructivo', 'threshold' => 1.0],
                    'Modelo' => ['label' => 'Modelo BIM', 'threshold' => 1.0],
                ];
            }

            foreach ($fallidas as $row) {
                $pendientes = $this->buildRestrictionAlertParts($row, $hardRestrictionLabels);
                if (empty($pendientes)) {
                    continue;
                }
                $blandas = $this->buildRestrictionAlertParts($row, $softRestrictionLabels);
                $actLabel = trim(preg_replace('/\s+/', ' ', preg_replace('/<[^>]*>/', ' ', (string) ($row['Actividad'] ?? ''))));
                $alertasRestricciones[] = [
                    'Id' => $row['Id'],
                    'Actividad' => $actLabel,
                    'RestriccionesPendientes' => implode(', ', $pendientes),
                    'RestriccionesBlandas' => implode(', ', $blandas),
                ];
            }

            $this->db->queryWithProject(
                "UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET fecha_ultimo_saneo = NOW() WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            );
            echo json_encode(["respuesta" => "OK", "alertasRestricciones" => $alertasRestricciones], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error Autoprogramar: " . $t->getMessage());
        }
    }

    private function syncRestrictionFlags(string $dbPrefix, int $semana, string $area = 'Construccion'): void
    {
        $projectId = $this->projectId($dbPrefix);
        $alias = $area === 'Pre-Construccion' ? '' : 'pc';
        $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql($alias, $area);

        $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " ps
            JOIN " . $this->tbl($dbPrefix, 'programa_consolidado') . " pc
              ON pc.project_id = ps.project_id
             AND ps.unique_id = pc.unique_id
             AND ps.Semana = pc.Semana
            SET ps.Prog_Sin_Restricciones_100 = (CASE WHEN {$restrictionEligibilitySql} THEN 0 ELSE 1 END)
            WHERE ps.project_id = ? AND ps.Semana = ? AND ps.Activa != 'NA'", [$projectId, $semana], $projectId);

        $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET Prog_Sin_Restricciones_100 = 0 WHERE project_id = ? AND Semana = ? AND Activa = 'NA'", [$projectId, $semana], $projectId);
    }

    private function getAutoprogramRestrictionEligibilitySql(string $alias = '', string $area = 'Construccion'): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        if ($area === 'Pre-Construccion') {
            return '(' . $this->restrictionAtLeastOrNotApplicableSql($prefix . 'restriccion_pc_1', 0.5) . ')';
        }

        return '(' . implode(' AND ', [
            $this->restrictionAtLeastOrNotApplicableSql($prefix . 'D_y_E', 1.0),
            $this->restrictionAtLeastOrNotApplicableSql($prefix . 'Materiales', 1.0),
            $this->restrictionAtLeastOrNotApplicableSql($prefix . 'MdeO', 1.0),
            $this->restrictionAtLeastOrNotApplicableSql($prefix . 'Equipos', 1.0),
            $this->restrictionAtLeastOrNotApplicableSql($prefix . 'Predecesora', 0.5),
        ]) . ')';
    }

    private function restrictionAtLeastOrNotApplicableSql(string $column, float $minimumRatio): string
    {
        $text = "TRIM(COALESCE({$column}, ''))";
        $normalized = $this->restrictionRatioSql($column);
        $threshold = number_format($minimumRatio, 5, '.', '');

        return "(UPPER({$text}) IN ('N/A', 'NO APLICA') OR {$normalized} >= {$threshold})";
    }

    private function restrictionRatioSql(string $column): string
    {
        $text = "TRIM(COALESCE({$column}, ''))";
        $compact = "REPLACE({$text}, ' ', '')";
        $numeric = "CAST(REPLACE(REPLACE({$compact}, '%', ''), ',', '.') AS DECIMAL(10,5))";

        return "(CASE WHEN LOCATE('%', {$compact}) > 0 THEN {$numeric} / 100 WHEN {$numeric} > 1 AND {$numeric} <= 10000 THEN {$numeric} / 100 ELSE {$numeric} END)";
    }

    private function buildRestrictionAlertParts(array $row, array $rules): array
    {
        $parts = [];
        foreach ($rules as $column => $rule) {
            $value = $row[$column] ?? null;
            if ($this->restrictionValueMeetsThreshold($value, (float) $rule['threshold'])) {
                continue;
            }

            $ratio = $this->parseRestrictionRatioValue($value) ?? 0.0;
            $parts[] = $rule['label'] . ' (' . round($ratio * 100) . '%)';
        }

        return $parts;
    }

    private function restrictionValueMeetsThreshold($value, float $threshold): bool
    {
        $text = trim((string) ($value ?? ''));
        $upper = strtoupper($text);
        if ($upper === 'N/A' || $upper === 'NO APLICA') {
            return true;
        }

        $ratio = $this->parseRestrictionRatioValue($value);
        return $ratio !== null && ($ratio + 0.0001) >= $threshold;
    }

    private function parseRestrictionRatioValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || strtolower($raw) === 'null') {
            return null;
        }

        $hasPercent = strpos($raw, '%') !== false;
        $normalized = str_replace('%', '', preg_replace('/\s+/', '', $raw));
        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            $normalized = $commaPos > $dotPos
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $ratio = (float) $normalized;
        if ($hasPercent) {
            $ratio /= 100;
        }
        while ($ratio > 1 && $ratio <= 10000) {
            $ratio /= 100;
        }

        return max(0.0, min(1.0, $ratio));
    }

    private function jsonResponse(string $res): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["respuesta" => $res], JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $msg, int $status = 422): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["respuesta" => "ERROR", "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    }

    private function parseLocalizedFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace(['$', ' ', ','], ['', '', '.'], $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function nullableFloatChanged(?float $incoming, ?float $current): bool
    {
        if ($incoming === null || $current === null) {
            return $incoming !== $current;
        }

        return abs($incoming - $current) > 0.0001;
    }

    private function getWeeklyProgramId(string $dbPrefix, int $weeklyRowId): ?int
    {
        if ($weeklyRowId <= 0) {
            return null;
        }
        $projectId = $this->projectId($dbPrefix);

        $programId = $this->db->queryWithProject(
            "SELECT unique_id AS Consecutivo_En_Programa FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND row_id = ? LIMIT 1",
            [$projectId, $weeklyRowId],
            $projectId,
        )->fetchColumn();

        if ($programId === false || $programId === null) {
            return null;
        }

        return (int) $programId;
    }

    private function syncNextWeekCarryover(string $dbPrefix, int $sourceWeek, int $sourceProgramId): void
    {
        if ($sourceWeek <= 0 || $sourceProgramId <= 0) {
            return;
        }

        $targetWeek = $sourceWeek + 1;
        $projectId = $this->projectId($dbPrefix);
        $exists = (int) $this->db->queryWithProject(
            "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ?",
            [$projectId, $targetWeek],
            $projectId,
        )->fetchColumn();

        if ($exists === 0) {
            return;
        }

        $result = $this->weeklyRealProgressCarryoverService->syncWeek($dbPrefix, $sourceWeek, $targetWeek, $sourceProgramId);
        $updatedProgramIds = $result['updatedProgramIds'] ?? [];

        if (!empty($updatedProgramIds)) {
            $this->refreshGeneralStatuses($dbPrefix, $targetWeek, $updatedProgramIds);
        }
    }

    private function refreshGeneralStatuses(string $dbPrefix, int $semana, array $programIds): void
    {
        $programIds = array_values(array_unique(array_filter(array_map('intval', $programIds), static fn($id) => $id > 0)));
        if (empty($programIds)) {
            return;
        }

        $projectId = $this->projectId($dbPrefix);
        $semanaData = $this->db->queryWithProject(
            "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ? LIMIT 1",
            [$projectId, $semana],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        if (!$semanaData) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $params = array_merge([$projectId, $semana], $programIds);
        $rows = $this->db->queryWithProject(
            "SELECT unique_id AS Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
             FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
             WHERE project_id = ? AND Semana = ? AND unique_id IN ({$placeholders})",
            $params,
            $projectId,
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $estado = $this->lpsService->calculateGeneralStatus(
                $row['Titulo'] ?? 0,
                $row['Ejecutado'] ?? 0,
                $row['Fecha_Inicio'] ?? null,
                $row['Fecha_Fin'] ?? null,
                $semanaData['Fecha_Inicio_Sem'] ?? null,
                $semanaData['Fecha_Fin_Sem'] ?? null,
            );

            $this->db->queryWithProject(
                "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Estado = ? WHERE project_id = ? AND Semana = ? AND unique_id = ?",
                [$estado, $projectId, $semana, $row['unique_id'] ?? $row['Consecutivo_en_Programa']],
                $projectId,
            );
        }
    }

    // Stub methods for the rest of the logic to be completed in subsequent edits if necessary
    private function estadoEjecucion(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'estado_ejecucion');
        $id = $_POST["Id"];
        $ejecutado = $_POST["Ejecutado"];
        $query1 = "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Activa = 1 WHERE project_id = ? AND unique_id = ? AND Semana = ?";
        $query2 = "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Ejecutado_Siguiente_Semana = ? WHERE project_id = ? AND unique_id = ? AND Semana = ?";
        $this->db->queryWithProject($query1, [$projectId, $id, $semana], $projectId);
        $res = $this->db->queryWithProject($query2, [$ejecutado, $projectId, $id, $semana], $projectId);

        $normalizationService = new ProgramaConsolidadoNormalizationService($this->db);
        $normalizationService->normalizeChapters($dbPrefix, $semana);

        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }

    private function eliminar(string $dbPrefix, int $semana): void
    {
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'eliminar');
        $id = (int) ($_POST["Id"] ?? 0);
        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a eliminar.");
            return;
        }

        $this->db->beginTransaction();
        $projectId = $this->projectId($dbPrefix);
        $querySelect = "SELECT Activa FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND row_id = ?";
        $data = $this->db->queryWithProject($querySelect, [$projectId, $id], $projectId)->fetch(PDO::FETCH_ASSOC);

        if ($data && $data["Activa"] === "NA") {
            $res = $this->db->queryWithProject("DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND row_id = ?", [$projectId, $id], $projectId);
        } else {
            $queryUpdate = "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET Activa = '0', Responsable_AIA = ?, Categoria_CNP = ?, CNP = ?, Observaciones_CNP = ?, Reprogramada_Por_Usuario = 0 WHERE project_id = ? AND row_id = ?";
            $res = $this->db->queryWithProject($queryUpdate, [$_POST["Responsable_AIA"], $_POST["Categoria_CNP"], $_POST["CNP"], $_POST["Observaciones_CNP"], $projectId, $id], $projectId);
        }

        $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        $this->db->commit();
        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }

    private function duplicar(string $dbPrefix, int $semana): void
    {
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'duplicar');
        $id = (int) ($_POST["Id"] ?? 0);
        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a duplicar.");
            return;
        }

        $this->db->beginTransaction();
        $projectId = $this->projectId($dbPrefix);
        $queryInsert = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad) SELECT ?, ?, unique_id, unique_id, Id, Actividad, 0, 0, 'NA', Prog_Sin_Restricciones_100, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, 0 FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND row_id = ?";
        $res = $this->db->queryWithProject($queryInsert, [$projectId, $semana, $projectId, $semana, $id], $projectId);
        $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        $this->db->commit();
        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }
    private function nuevo(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'nuevo');
        $idBase = trim((string) ($_POST["idNuevo"] ?? ''));
        $subs = array_filter(array_map('trim', explode(',', $_POST["Sub_Contratista"])));
        $actividadNombre = trim((string) ($_POST["Actividad"] ?? ''));

        // Validate base activity exists in programa_consolidado
        $query0 = "SELECT *, unique_id AS Consecutivo_en_Programa, row_id AS Consecutivo FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Semana = ? AND Id = ? AND Titulo = 0 AND Semanas_Inicio <= 12 AND Semanas_Inicio >= 1 AND Ejecutado = 0 LIMIT 1";
        $data0 = $this->db->queryWithProject($query0, [$projectId, $semana, $idBase], $projectId)->fetch(PDO::FETCH_ASSOC);
        if (!$data0) {
            $this->jsonError("Actividad base no válida.");
            return;
        }

        // Dedup check: prevent inserting activity already in the same week for same sub
        $tblPS = $this->tbl($dbPrefix, 'programacion_semanal');
        $dupChecks = [];
        foreach ($subs as $sub) {
            $dupQuery = "SELECT COUNT(*) FROM {$tblPS} WHERE project_id = ? AND Semana = ? AND unique_id = ? AND Sub_Contratista = ?";
            $exists = $this->db->queryWithProject($dupQuery, [$projectId, $semana, $data0["unique_id"], $sub], $projectId)->fetchColumn();
            if ($exists > 0) {
                $dupChecks[] = $sub;
            }
        }
        if (count($dupChecks) > 0) {
            $this->jsonError("La actividad ya existe en esta semana para: " . implode(', ', $dupChecks));
            return;
        }

        $queryInsert = "INSERT INTO {$tblPS} (project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Descripcion, Ubicacion, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad, Unidad, cantidad_ppto, Compromiso, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100, codigo_actividad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'NA', 0, ?)";
        $isFirst = true;
        $this->db->beginTransaction();
        $compromisoValue = $this->parseLocalizedFloat($_POST["Compromiso"]);
        foreach ($subs as $sub) {
            $this->db->queryWithProject($queryInsert, [$projectId, $semana, $data0["unique_id"], $data0["unique_id"], $idBase, $actividadNombre, $_POST["Descripcion"], $_POST["Ubicacion"], $data0["Fecha_Inicio"], $data0["Fecha_Fin"], $sub, $_POST["Responsable_AIA"], $_POST["Empresa"], $data0["Ejecutado"], $data0["medir_productividad"], $_POST["Unidad"] ?: '%', $data0["cantidad_ppto"], $isFirst ? $compromisoValue : null, $data0["codigo_actividad"]], $projectId);
            $isFirst = false;
        }

        // Audit log before commit
        $this->db->logActivity(
            'ProgramacionSemanal',
            'AGREGAR_ACTIVIDAD',
            "Agregada actividad '{$actividadNombre}' (Id: {$idBase}, unique_id: {$data0["unique_id"]}) en semana {$semana} para proyecto {$dbPrefix}",
            $projectId
        );

        $this->syncNextWeekCarryover($dbPrefix, $semana, (int) ($data0["unique_id"] ?? $data0["Consecutivo_en_Programa"]));
        $this->db->commit();
        $this->jsonResponse("BIEN");
    }

    private function bloquearCompromisos(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        $queryCount = "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Activa = 1 AND (Compromiso IS NULL OR Compromiso <= 0 OR TRIM(COALESCE(Sub_Contratista, '')) = '' OR LOWER(TRIM(COALESCE(Sub_Contratista, ''))) = 'null' OR TRIM(COALESCE(Responsable_AIA, '')) = '' OR LOWER(TRIM(COALESCE(Responsable_AIA, ''))) = 'null')";
        if ($this->db->queryWithProject($queryCount, [$projectId, $semana], $projectId)->fetchColumn() > 0) {
            echo json_encode(["respuesta" => "No_Bloqueado", "mensaje" => "Hay actividades sin compromiso o sin asignaciones obligatorias."]);
            return;
        }
        $res = $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET Semanal_Confirmada = 1, fechaCierreCompromisos = ? WHERE project_id = ? AND Semana = ?", [$_POST["fechaCierreCompromisos"] ?: null, $projectId, $semana], $projectId);
        if ($res) {
            $this->generarCIC($dbPrefix, $semana);
            echo json_encode(["respuesta" => "Bloqueado", "mensaje" => "Semana bloqueada y CIC generado."]);
        } else {
            $this->jsonError("No se pudo bloquear.");
        }
    }

    public function reabrir(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');

        $dbPrefix = $_GET['db'] ?? $_POST['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo'] ?? '');

        // CSRF protection: reabrir es una mutación privilegiada, igual que las opciones de save().
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate($csrfToken, 'semanal_save')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Token CSRF inválido o expirado."], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        if ($semana <= 0) {
            $this->jsonError("Semana inválida.");
            return;
        }
        if (!$this->requireWeekEditPolicy($dbPrefix, (int) $semana)) {
            return;
        }
        if (strlen($motivo) < 20) {
            $this->jsonError("El motivo debe tener al menos 20 caracteres.");
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->setProjectContext($projectId);
            $this->db->beginTransaction();

            $this->db->queryWithProject(
                "UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET Semanal_Confirmada = 0, fechaCierreCompromisos = NULL WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId
            );

            $this->db->logActivity('ProgramacionSemanal', 'REABRIR', "Semana {$semana} reabierta por Admin. Motivo: {$motivo}", $projectId);

            $this->db->commit();
            $this->jsonResponse("OK");
        } catch (Throwable $t) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonError("Error al reabrir: " . $t->getMessage());
        }
    }

    private function generarCIC(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        $tblSub = $this->tbl($dbPrefix, 'subcontratistas');
        $tblPS = $this->tbl($dbPrefix, 'programacion_semanal');
        $tblCic = $this->tbl($dbPrefix, 'cic');

        // Self-heal: ensure every Sub_Contratista referenced in programacion_semanal exists
        // in the subcontratistas master table. The cic FK (fk_cic__subcontratistas__subcontratista)
        // would otherwise reject the insert below. Mirrors migration 003/2b pattern.
        $this->db->queryWithProject(
            "INSERT IGNORE INTO {$tblSub}
                (project_id, Id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor)
             SELECT DISTINCT
                src.project_id,
                COALESCE((SELECT MAX(Id) FROM {$tblSub} WHERE project_id = src.project_id), 0)
                    + ROW_NUMBER() OVER (PARTITION BY src.project_id ORDER BY src.Sub_Contratista) AS Id,
                src.Sub_Contratista,
                'placeholder@example.com',
                0,
                'Internal',
                'Internal'
             FROM (
                SELECT DISTINCT project_id, Semana, Sub_Contratista
                FROM {$tblPS}
                WHERE project_id = ?
                  AND Sub_Contratista IS NOT NULL AND TRIM(Sub_Contratista) != ''
                  AND (Activa = 1 OR Activa = 'NA')
                  AND Semana <= ?
             ) src
             LEFT JOIN {$tblSub} sub
                ON sub.subcontratista = src.Sub_Contratista AND sub.project_id = src.project_id
             WHERE sub.subcontratista IS NULL",
            [$projectId, $semana],
            $projectId
        );

        for ($s = 1; $s <= $semana; $s++) {
            $this->actualizarPacSubcontratistas($dbPrefix, $s);
            $subsSemana = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM {$tblPS} WHERE project_id = ? AND Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$projectId, $s], $projectId)->fetchAll(PDO::FETCH_COLUMN);
            foreach ($subsSemana as $sub) {
                $exists = $this->db->queryWithProject("SELECT 1 FROM {$tblCic} WHERE project_id = ? AND Semana = ? AND subcontratista = ?", [$projectId, $s, $sub], $projectId)->fetchColumn();
                if (!$exists) {
                    $this->db->queryWithProject("INSERT INTO {$tblCic} (project_id, Semana, subcontratista) VALUES (?, ?, ?)", [$projectId, $s, $sub], $projectId);
                }
            }
            $this->actualizarPacSubcontratistas($dbPrefix, $s);
        }
    }

    private function actualizarPacSubcontratistas(string $dbPrefix, int $s): void
    {
        $projectId = $this->projectId($dbPrefix);
        $subs = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$projectId, $s], $projectId)->fetchAll(PDO::FETCH_COLUMN);
        foreach ($subs as $sub) {
            $stats = $this->db->queryWithProject("SELECT ROUND(AVG(P_Completado),3) as P_Com, ROUND(AVG(PAC),3) as PAC FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Sub_Contratista = ? AND (Activa=1 OR Activa='NA')", [$projectId, $s, $sub], $projectId)->fetch(PDO::FETCH_ASSOC);
            $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'cic') . " SET P_Completado = ?, PAC = ? WHERE project_id = ? AND subcontratista = ? AND Semana = ?", [$stats['P_Com'] ?? 0, $stats['PAC'] ?? 0, $projectId, $sub, $s], $projectId);
        }
    }

    private function listarExcepciones(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        $area = $_SESSION['area'] ?? 'Construccion';

        // Bandeja de No Autoprogramadas = ventana de Programación Intermedia (PI = 6 semanas).
        // Muestra las mismas actividades que PI con Semanas_Inicio 1-6, sin filtrar
        // por estado ni elegibilidad de restricciones. Orden: inicio más cercano a más lejano.
        $piWindowWeeks = 6;
        $restrictionColumns = ($area === 'Pre-Construccion')
            ? 'restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4'
            : 'D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo';

        $query = "SELECT
                Id, Actividad, Estado,
                Semanas_Inicio, Fecha_Inicio,
                Sub_Contratista, Responsable_AIA, unidad AS Unidad,
                {$restrictionColumns}
            FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
            WHERE project_id = ? AND Semana = ? AND Titulo = 0
              AND Semanas_Inicio <= {$piWindowWeeks} AND Semanas_Inicio >= 1
              AND COALESCE(Ejecutado, 0) <= 0.001
            ORDER BY Semanas_Inicio ASC, Fecha_Inicio ASC, Id ASC";

        $data = $this->db->queryWithProject($query, [$projectId, $semana], $projectId)->fetchAll(PDO::FETCH_ASSOC);

        // Etiquetas de restricciones duras (mismas que usa autoprogramar() para alertasRestricciones)
        if ($area === 'Pre-Construccion') {
            $hardRestrictionLabels = [
                'restriccion_pc_1' => ['label' => 'Predecesora', 'threshold' => 0.5],
            ];
        } else {
            $hardRestrictionLabels = [
                'D_y_E' => ['label' => 'D. y Especificaciones', 'threshold' => 1.0],
                'Materiales' => ['label' => 'Materiales', 'threshold' => 1.0],
                'MdeO' => ['label' => 'Mano de Obra', 'threshold' => 1.0],
                'Equipos' => ['label' => 'Equipos', 'threshold' => 1.0],
                'Predecesora' => ['label' => 'Predecesora', 'threshold' => 0.5],
            ];
        }

        foreach ($data as &$row) {
            // Strip HTML markup from data fields (some source data has legacy HTML)
            $row['Actividad'] = strip_tags((string) ($row['Actividad'] ?? ''));
            $row['Estado'] = strip_tags((string) ($row['Estado'] ?? ''));
            $row['Sub_Contratista'] = strip_tags((string) ($row['Sub_Contratista'] ?? ''));
            $row['Responsable_AIA'] = strip_tags((string) ($row['Responsable_AIA'] ?? ''));
            $row['Unidad'] = trim((string) ($row['Unidad'] ?? '')) ?: '%';
            $row['Semanas_Inicio'] = isset($row['Semanas_Inicio']) && $row['Semanas_Inicio'] !== null
                ? (int) $row['Semanas_Inicio']
                : null;

            // Motivo = restricciones pendientes si las hay; si no, "Lista para autoprogramar"
            $pendientes = $this->buildRestrictionAlertParts($row, $hardRestrictionLabels);
            $row['Motivo'] = !empty($pendientes)
                ? implode(', ', $pendientes)
                : 'Lista para autoprogramar';
        }
        unset($row);

        echo json_encode(["respuesta" => "BIEN", "data" => $data], JSON_UNESCAPED_UNICODE);
    }


    private function sanear(string $dbPrefix, int $semana): void
    {
        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->setProjectContext($projectId);
            $area = $_SESSION['area'] ?? 'Construccion';
            $confirmada = $this->db->queryWithProject(
                "SELECT Semanal_Confirmada FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            )->fetchColumn();

            if ($confirmada == 1) {
                $this->jsonResponse("OK");
                return;
            }

            $fechaUltimoSaneo = $this->db->queryWithProject(
                "SELECT fecha_ultimo_saneo FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            )->fetchColumn();

            if ($fechaUltimoSaneo !== null && $fechaUltimoSaneo !== false) {
                $lastChange = $this->db->queryWithProject(
                    "SELECT GREATEST(
                        COALESCE(MAX(Ult_Act_Est), '1970-01-01'),
                        COALESCE(MAX(Ult_Act_Restr), '1970-01-01')
                    ) FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Semana = ?",
                    [$projectId, $semana],
                    $projectId,
                )->fetchColumn();

                if ($lastChange !== null && $lastChange !== false && $lastChange <= $fechaUltimoSaneo) {
                    $this->jsonResponse("OK");
                    return;
                }
            }

            $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql('', $area);
            $eligibleSubSql = "SELECT unique_id FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
                WHERE project_id = ? AND Semana = ? AND Titulo = 0
                  AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
                  AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                    OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";

            $this->db->queryWithProject("
                DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                WHERE project_id = ? AND Semana = ? AND Activa = '1'
                  AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
                  AND (Compromiso IS NULL OR Compromiso <= 0)
                  AND unique_id NOT IN ({$eligibleSubSql})
            ", [$projectId, $semana, $projectId, $semana], $projectId);

            $stmtExistentes = $this->db->queryWithProject(
                "SELECT DISTINCT(unique_id) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            );
            $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);

            $whereExistentes = "";
            $paramsInsert = [$projectId, $semana];
            if (!empty($existentes)) {
                $placeholders = implode(',', array_fill(0, count($existentes), '?'));
                $whereExistentes = "AND unique_id NOT IN ($placeholders)";
                $paramsInsert = array_merge($paramsInsert, $existentes);
            }

            $sqlSelectNuevas = "SELECT
                {$semana}, unique_id, unique_id, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                Sub_Contratista, Responsable_AIA, 'AIA', Ejecutado, 0,
                Ruta_Critica,
                CASE WHEN (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END,
                '1', COALESCE(NULLIF(TRIM(unidad), ''), '%'), cantidad_ppto, codigo_actividad
            FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
            WHERE project_id = ? AND Semana = ? AND Titulo = 0
              AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
              AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')
              {$whereExistentes}";

            $stmtNuevas = $this->db->queryWithProject($sqlSelectNuevas, $paramsInsert, $projectId);
            $nuevasFilas = $stmtNuevas->fetchAll(PDO::FETCH_NUM);

            if (!empty($nuevasFilas)) {
                $queryInsertSingle = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (
                    project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($nuevasFilas as $f) {
                    $subsRaw = $f[7] ?? '';
                    $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
                    if (empty($subs)) {
                        $subs = [''];
                    }
                    foreach ($subs as $sub) {
                        $f[7] = $sub;
                        $this->db->queryWithProject($queryInsertSingle, array_merge([$projectId], $f), $projectId);
                    }
                }
            }

            $stmtSemanal = $this->db->queryWithProject(
                "SELECT row_id AS Consecutivo, unique_id AS Consecutivo_En_Programa, Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Activa != 'NA' AND (Compromiso IS NULL OR Compromiso <= 0)",
                [$projectId, $semana],
                $projectId,
            );
            foreach ($stmtSemanal->fetchAll() as $item) {
                $con_pk = $item["Consecutivo"];
                $con_pg = $item["unique_id"] ?? $item["Consecutivo_En_Programa"];
                $sub_split = $item["Sub_Contratista"];

                $dataCons = $this->db->queryWithProject(
                    "SELECT *, row_id AS Consecutivo, unique_id AS Consecutivo_en_Programa FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Semana = ? AND unique_id = ?",
                    [$projectId, $semana, $con_pg],
                    $projectId,
                )->fetch();
                if (!$dataCons) {
                    continue;
                }

                $dataAnt = $this->db->queryWithProject(
                    "SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND unique_id = ? AND Sub_Contratista = ?",
                    [$projectId, $semana - 1, $con_pg, $sub_split],
                    $projectId,
                )->fetch();
                if (!$dataAnt) {
                    $dataAnt = $this->db->queryWithProject(
                        "SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND unique_id = ?",
                        [$projectId, $semana - 1, $con_pg],
                        $projectId,
                    )->fetch();
                }

                $sub = $sub_split ?: ($dataCons["Sub_Contratista"] ?? null);
                $resp = $dataCons["Responsable_AIA"] ?: ($dataAnt["Responsable_AIA"] ?? null);

                $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
                    Fecha_Inicio = ?, Fecha_Fin = ?, Sub_Contratista = ?, Responsable_AIA = ?,
                    Ejecutado = ?, medir_productividad = ?, Critica = ?,
                    Atrasada = (CASE WHEN ? IN ('Atrasada','Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END),
                    Descripcion = ?, Ubicacion = ?, Empresa = ?, Unidad = COALESCE(NULLIF(TRIM(?), ''), '%'),
                    cantidad_ppto = ?, codigo_actividad = ?
                    WHERE project_id = ? AND Semana = ? AND row_id = ?", [
                    $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
                    (float) $dataCons['Ejecutado'], 0, (int) ($dataCons["Ruta_Critica"] ?? 0),
                    $dataCons["Estado"], $dataAnt["Descripcion"] ?? null, $dataAnt["Ubicacion"] ?? null,
                    $dataAnt["Empresa"] ?? 'AIA', $dataCons["unidad"],
                    ((float) ($dataCons["cantidad_ppto"] ?? 0) > 0 ? (float) $dataCons["cantidad_ppto"] : null),
                    $dataCons["codigo_actividad"], $projectId, $semana, $con_pk,
                ], $projectId);
            }

            $this->syncRestrictionFlags($dbPrefix, $semana, $area);
            $this->db->queryWithProject(
                "UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET fecha_ultimo_saneo = NOW() WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            );
            $this->jsonResponse("OK");
        } catch (Throwable $t) {
            error_log("Error sanear PS: " . $t->getMessage());
            $this->jsonResponse("OK");
        }
    }

    private function tnp(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        \CommitmentLockGuard::guard($dbPrefix, $semana, 'tnp');
        $consecutivo = filter_input(INPUT_POST, 'Consecutivo', FILTER_VALIDATE_INT);
        $id = trim($_POST['Id'] ?? '');
        $ejecutadoReal = filter_input(INPUT_POST, 'Ejecutado_Real', FILTER_VALIDATE_FLOAT);
        $categoriaCp = trim($_POST['Categoria_CP'] ?? '');
        $cp = trim($_POST['CP'] ?? '');
        $observacionesCp = trim($_POST['Observaciones_CP'] ?? '');

        if ($ejecutadoReal === false || $ejecutadoReal <= 0) {
            $this->jsonError("Ejecutado Real debe ser mayor a 0");
            return;
        }

        if (!$consecutivo && !$id) {
            $this->jsonError("Se requiere Consecutivo o Id de actividad");
            return;
        }

        try {
            if ($consecutivo) {
                // Try UPDATE first — if matching row exists in programacion_semanal
                $stmt = $this->db->queryWithProject(
                    "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND unique_id = ? AND Semana = ?",
                    [$projectId, $consecutivo, $semana],
                    $projectId,
                );
                $exists = (int) $stmt->fetchColumn();

                if ($exists > 0) {
                    $this->db->queryWithProject(
                        "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
                         Ejecutado_Real = ?, Es_TNP = 1, Categoria_CP = ?, CP = ?,
                         Observaciones_CP = ?, PAC = NULL
                         WHERE project_id = ? AND unique_id = ? AND Semana = ?",
                        [$ejecutadoReal, $categoriaCp, $cp, $observacionesCp,
                         $projectId, $consecutivo, $semana],
                        $projectId,
                    );
                    $this->jsonResponse("BIEN");
                    return;
                }
            }

            // INSERT — activity not in programacion_semanal
            $pgData = $this->db->queryWithProject(
                "SELECT * FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Id = ? AND Semana = ?",
                [$projectId, $id, $semana],
                $projectId,
            )->fetch(PDO::FETCH_ASSOC);

            if (!$pgData) {
                $this->jsonError("Actividad no encontrada en Programa Consolidado");
                return;
            }

            $maxCon = $this->db->queryWithProject(
                "SELECT MAX(row_id) as maxCon FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ?",
                [$projectId, $semana],
                $projectId,
            )->fetch(PDO::FETCH_ASSOC);
            $nextConsecutivo = ($maxCon['maxCon'] ?? 0) + 1;

            $this->db->queryWithProject(
                "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                 (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Unidad, cantidad_ppto,
                  Compromiso, Ejecutado_Real, Activa, Es_TNP, Categoria_CP, CP, Observaciones_CP,
                  PAC, Sub_Contratista, Responsable_AIA)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, '1', 1, ?, ?, ?, NULL, ?, ?)",
                [$projectId, $nextConsecutivo, $nextConsecutivo, $semana,
                 $consecutivo ?: ($pgData['unique_id'] ?? $pgData['Consecutivo_en_Programa'] ?? null),
                 $consecutivo ?: ($pgData['unique_id'] ?? $pgData['Consecutivo_en_Programa'] ?? null),
                 $pgData['Id'] ?? null, $pgData['Actividad'] ?? '', $pgData['Unidad'] ?? '',
                 $pgData['Cuantia'] ?? $pgData['cantidad_ppto'] ?? 0, $ejecutadoReal,
                 $categoriaCp, $cp, $observacionesCp,
                 $pgData['Sub_Contratista'] ?? null, $pgData['Responsable_AIA'] ?? null,
                ],
                $projectId,
            );

            $this->jsonResponse("BIEN");
        } catch (Throwable $t) {
            $this->jsonError("Error TNP: " . $t->getMessage());
        }
    }

    private function importarActividadNoRequerida(string $dbPrefix, int $semana): void
    {
        $projectId = $this->projectId($dbPrefix);
        $id = $_POST["Consecutivo"];
        $data = $this->db->queryWithProject("SELECT Actividad, Responsable_AIA, Sub_Contratista, unidad, cantidad_ppto FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE project_id = ? AND Semana = ? AND Id = ?", [$projectId, $semana, $id], $projectId)->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["respuesta" => "BIEN", "data" => $data], JSON_UNESCAPED_UNICODE);
    }








    public function getTnpActivities(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');
        $dbPrefix = $_GET['db'] ?? '';
        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        if (!$this->validateContext($dbPrefix, $semana)) {
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $query = "SELECT pc.Id, pc.unique_id AS Consecutivo_en_Programa, pc.unique_id, pc.Actividad, pc.Sub_Contratista, pc.Responsable_AIA, pc.codigo_actividad,
                           CASE WHEN ps.unique_id IS NOT NULL THEN 1 ELSE 0 END AS previamente_programada
                    FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " pc
                    LEFT JOIN " . $this->tbl($dbPrefix, 'programacion_semanal') . " ps
                      ON ps.project_id = pc.project_id
                     AND pc.unique_id = ps.unique_id
                     AND ps.Semana = ?
                    WHERE pc.project_id = ? AND pc.Semana = ? AND pc.Titulo = 0
                      AND pc.Semanas_Inicio <= 12
                      AND pc.Semanas_Inicio >= 1
                      AND pc.Ejecutado = 0
                      AND (pc.Activa = 0 OR ps.unique_id IS NULL)
                    ORDER BY previamente_programada DESC, pc.codigo_actividad ASC";

            $stmt = $this->db->queryWithProject($query, [$semana, $projectId, $semana], $projectId);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Strip HTML markup from data fields
            foreach ($rows as &$row) {
                $row['Actividad'] = strip_tags($row['Actividad'] ?? '');
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error del servidor: " . $t->getMessage());
        }
    }

    public function autoProgram(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');

        $dbPrefix = $_POST['db'] ?? $_GET['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? $_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError('Parámetro de base de datos inválido.');
            return;
        }
        if ($semana <= 0) {
            $this->jsonError('Semana inválida.');
            return;
        }
        if (!$this->requireWeekEditPolicy($dbPrefix, (int) $semana)) {
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->setProjectContext($projectId);
            $area = $_SESSION['area'] ?? 'Construccion';
            $detector = new ProgramChangeDetector();
            $log = $detector->run($dbPrefix, $semana);

            $this->syncRestrictionFlags($dbPrefix, $semana, $area);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'log' => $log,
                'total_acciones' => count($log),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError('Error al auto-programar: ' . $t->getMessage());
        }
    }

    public function getAutoProgramLog(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');

        $dbPrefix = $_GET['db'] ?? '';
        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$this->requireSessionDbPrefix($dbPrefix)) {
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError('Parámetro de base de datos inválido.');
            return;
        }
        if ($semana <= 0) {
            $this->jsonError('Semana inválida.');
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->setProjectContext($projectId);
            $detector = new ProgramChangeDetector();
            $log = $detector->getLog($dbPrefix, $semana);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'log' => $log,
                'total' => count($log),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError('Error al obtener log: ' . $t->getMessage());
        }
    }

    private function projectId(string $dbPrefix): int
    {
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        if (!$projectId) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        return $projectId;
    }
}
