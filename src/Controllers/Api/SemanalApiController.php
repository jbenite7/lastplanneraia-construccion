<?php

namespace App\Controllers\Api;

use App\Core\Lps\LpsService;
use App\Services\ProgramChangeDetector;
use App\Services\ProgramaConsolidadoNormalizationService;
use App\Services\RestrictionConfigResolver;
use App\Services\WeeklyRealProgressCarryoverService;
use PDO;
use Throwable;
use TableResolver;

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
                $arreglo["data"][] = [
                    "Consecutivo" => "", "Id" => "", "Actividad" => "", "Fecha_Inicio" => "", "Fecha_Fin" => "",
                    "Prog_Sin_Restricciones_100" => "", "Descripcion" => "", "Ubicacion" => "", "Ejecutado" => "",
                    "Ejecutado_Fin_Semana" => "", "Sub_Contratista" => "", "Responsable_AIA" => "", "Empresa" => "",
                    "medir_productividad" => "", "Unidad" => "", "cantidad_ppto" => "", "Compromiso" => "",
                    "Ejecutado_Real" => "", "P_Completado" => "", "PAC" => "", "Activa" => "", "Categoria_CNC" => "",
                    "CNC" => "", "Observaciones_CNC" => "", "Rendimientos" => "", "codigo_actividad" => "",
                    "proyeccionSemana" => "", "diasSemanaInicial" => "", "diasLleva" => "", "diasSemana" => "", "diasTotales" => "",
                ];
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

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError("Parámetro de base de datos inválido.");
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

    private function calculateProjections(array &$data, string $fInicioSem, string $fFinSem): void
    {
        $data = $this->lpsService->calculateWeeklyProjections($data, $fInicioSem, $fFinSem);
    }

    private function modificar(string $dbPrefix, int $semana): void
    {
        $id = (int) ($_POST["Id"] ?? 0);
        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a actualizar.");
            return;
        }

        $compromiso = $this->lpsService->toFloat($_POST["Compromiso"] ?? null);
        $real = $this->lpsService->toFloat($_POST["Real"] ?? null);

        $esTnp = isset($_POST['Es_TNP']) && ($_POST['Es_TNP'] == 1 || $_POST['Es_TNP'] === '1');
        if (!$esTnp && $compromiso !== null && $compromiso <= 0) {
            $this->jsonError("El compromiso no puede ser 0. Use CNP para desprogramar.");
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
            WHERE row_id = ?";

        $catCnc = ($pac == 1) ? null : ($_POST["Categoria_CNC"] ?: null);
        $cnc = ($pac == 1) ? null : ($_POST["CNC"] ?: null);
        $obs = ($pac == 1) ? null : ($_POST["Observaciones_CNC"] ?: null);

        $params = [
            $_POST["Descripcion"], $_POST["Ubicacion"], explode(',', $_POST["Sub_Contratista"])[0],
            $_POST["Responsable_AIA"], $_POST["Empresa"], $compromiso,
            $this->parseLocalizedFloat($_POST["Cantidad_Sugerida"] ?? null),
            $real, $pCompletado, $pac, $_POST["Rendimientos"] ?: null,
            $catCnc, $cnc, $obs, $id,
        ];

        $this->db->beginTransaction();
        $res = $this->db->queryWithProject($query, $params);
        $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        $this->db->commit();

        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }

    private function autoprogramar(string $dbPrefix, int $semana): void
    {
        try {
            $area = $_SESSION['area'] ?? 'Construccion';
            $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql('', $area);

            // 1. Identificar actividades ya programadas
            $stmtExistentes = $this->db->queryWithProject("SELECT DISTINCT(unique_id) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ?", [$semana]);
            $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);

            $whereExistentes = "";
            $paramsInsert = [$semana, $semana];
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
            WHERE Semana = ? AND Titulo = 0
              AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
              AND (
                Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
              )
              $whereExistentes";

            array_shift($paramsInsert);
            $stmtNuevas = $this->db->queryWithProject($sqlSelectNuevas, $paramsInsert);
            $nuevasFilas = $stmtNuevas->fetchAll(PDO::FETCH_NUM);

            if (!empty($nuevasFilas)) {
                $queryInsertSingle = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (
                    Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($nuevasFilas as $f) {
                    $subsRaw = $f[7] ?? '';
                    $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
                    if (empty($subs)) {
                        $subs = [''];
                    }
                    foreach ($subs as $sub) {
                        $f[7] = $sub;
                        $this->db->queryWithProject($queryInsertSingle, $f);
                    }
                }
            }

            // 3. Actualizar detalles y compromisos (Preservando Subcontratista Split, sin tocar actividades con compromiso)
            $stmtSemanal = $this->db->queryWithProject("SELECT row_id AS Consecutivo, unique_id AS Consecutivo_En_Programa, Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Activa != 'NA' AND (Compromiso IS NULL OR Compromiso <= 0)", [$semana]);
            $actividadesSemanales = $stmtSemanal->fetchAll();

            foreach ($actividadesSemanales as $item) {
                $con_pk = $item["Consecutivo"];
                $con_pg = $item["unique_id"] ?? $item["Consecutivo_En_Programa"];
                $sub_split = $item["Sub_Contratista"];

                $dataCons = $this->db->queryWithProject("SELECT *, row_id AS Consecutivo, unique_id AS Consecutivo_en_Programa FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Semana = ? AND unique_id = ?", [$semana, $con_pg])->fetch();
                if (!$dataCons) {
                    continue;
                }

                $dataAnt = $this->db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND unique_id = ? AND Sub_Contratista = ?", [$semana - 1, $con_pg, $sub_split])->fetch();
                if (!$dataAnt) {
                    $dataAnt = $this->db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND unique_id = ?", [$semana - 1, $con_pg])->fetch();
                }

                $sub = $sub_split ?: ($dataCons["Sub_Contratista"] ?? null);
                $resp = $dataCons["Responsable_AIA"] ?: ($dataAnt["Responsable_AIA"] ?? null);

                $sqlActSemana = "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
                    Fecha_Inicio = ?, Fecha_Fin = ?, Sub_Contratista = ?, Responsable_AIA = ?,
                    Ejecutado = ?, medir_productividad = ?, Critica = ?,
                    Atrasada = (CASE WHEN ? IN ('Atrasada', 'Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END),
                    Descripcion = ?, Ubicacion = ?, Empresa = ?, Unidad = COALESCE(NULLIF(TRIM(?), ''), '%'),
                    cantidad_ppto = ?, codigo_actividad = ?
                    WHERE Semana = ? AND row_id = ?";

                $this->db->queryWithProject($sqlActSemana, [
                    $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
                    (float) $dataCons['Ejecutado'], 0, (int) ($dataCons["Ruta_Critica"] ?? 0),
                    $dataCons["Estado"], $dataAnt["Descripcion"] ?? null, $dataAnt["Ubicacion"] ?? null,
                    $dataAnt["Empresa"] ?? 'AIA', $dataCons["unidad"],
                    ((float) ($dataCons["cantidad_ppto"] ?? 0) > 0 ? (float) $dataCons["cantidad_ppto"] : null),
                    $dataCons["codigo_actividad"], $semana, $con_pk,
                ]);
            }

            // 4. Limpieza: actividades que ya no califican (sin compromiso ni avance real)
            $eligibleSubSql = "SELECT unique_id FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
                WHERE Semana = ? AND Titulo = 0
                  AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
                  AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                    OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";
            $this->db->queryWithProject("
                DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                WHERE Semana = ? AND Activa = '1'
                  AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
                  AND (Compromiso IS NULL OR Compromiso <= 0)
                  AND unique_id NOT IN ({$eligibleSubSql})
            ", [$semana, $semana]);

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
            WHERE Semana = ? AND Titulo = 0
              AND COALESCE(Ejecutado, 0) <= 0.001
              AND NOT {$restrictionEligibilitySql}
              AND (
                Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
              )
              $whereExistentes";

            $stmtRest = $this->db->queryWithProject($sqlRestricciones, $paramsInsert);
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
                "UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET fecha_ultimo_saneo = NOW() WHERE Semana = ?",
                [$semana],
            );
            echo json_encode(["respuesta" => "OK", "alertasRestricciones" => $alertasRestricciones], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error Autoprogramar: " . $t->getMessage());
        }
    }

    private function syncRestrictionFlags(string $dbPrefix, int $semana, string $area = 'Construccion'): void
    {
        $alias = $area === 'Pre-Construccion' ? '' : 'pc';
        $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql($alias, $area);

        $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " ps
            JOIN " . $this->tbl($dbPrefix, 'programa_consolidado') . " pc
              ON ps.unique_id = pc.unique_id
             AND ps.Semana = pc.Semana
            SET ps.Prog_Sin_Restricciones_100 = (CASE WHEN {$restrictionEligibilitySql} THEN 0 ELSE 1 END)
            WHERE ps.Semana = ? AND ps.Activa != 'NA'", [$semana]);

        $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET Prog_Sin_Restricciones_100 = 0 WHERE Semana = ? AND Activa = 'NA'", [$semana]);
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

    private function jsonError(string $msg): void
    {
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

    private function getWeeklyProgramId(string $dbPrefix, int $weeklyRowId): ?int
    {
        if ($weeklyRowId <= 0) {
            return null;
        }

        $programId = $this->db->queryWithProject(
            "SELECT unique_id AS Consecutivo_En_Programa FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE row_id = ? LIMIT 1",
            [$weeklyRowId],
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
        $exists = (int) $this->db->queryWithProject(
            "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE Semana = ?",
            [$targetWeek],
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

        $semanaData = $this->db->queryWithProject(
            "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE Semana = ? LIMIT 1",
            [$semana],
        )->fetch(PDO::FETCH_ASSOC);

        if (!$semanaData) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $params = array_merge([$semana], $programIds);
        $rows = $this->db->queryWithProject(
            "SELECT unique_id AS Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
             FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
             WHERE Semana = ? AND unique_id IN ({$placeholders})",
            $params,
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
                "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Estado = ? WHERE Semana = ? AND unique_id = ?",
                [$estado, $semana, $row['unique_id'] ?? $row['Consecutivo_en_Programa']],
            );
        }
    }

    // Stub methods for the rest of the logic to be completed in subsequent edits if necessary
    private function estadoEjecucion(string $dbPrefix, int $semana): void
    {
        $id = $_POST["Id"];
        $ejecutado = $_POST["Ejecutado"];
        $query1 = "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Activa = 1 WHERE unique_id = ? AND Semana = ?";
        $query2 = "UPDATE " . $this->tbl($dbPrefix, 'programa_consolidado') . " SET Ejecutado_Siguiente_Semana = ? WHERE unique_id = ? AND Semana = ?";
        $this->db->queryWithProject($query1, [$id, $semana]);
        $res = $this->db->queryWithProject($query2, [$ejecutado, $id, $semana]);

        $normalizationService = new ProgramaConsolidadoNormalizationService($this->db);
        $normalizationService->normalizeChapters($dbPrefix, $semana);

        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }

    private function eliminar(string $dbPrefix, int $semana): void
    {
        $id = (int) ($_POST["Id"] ?? 0);
        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a eliminar.");
            return;
        }

        $this->db->beginTransaction();
        $querySelect = "SELECT Activa FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE row_id = ?";
        $data = $this->db->queryWithProject($querySelect, [$id])->fetch(PDO::FETCH_ASSOC);

        if ($data && $data["Activa"] === "NA") {
            $res = $this->db->queryWithProject("DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE row_id = ?", [$id]);
        } else {
            $queryUpdate = "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET Activa = '0', Responsable_AIA = ?, Categoria_CNP = ?, CNP = ?, Observaciones_CNP = ?, Reprogramada_Por_Usuario = 0 WHERE row_id = ?";
            $res = $this->db->queryWithProject($queryUpdate, [$_POST["Responsable_AIA"], $_POST["Categoria_CNP"], $_POST["CNP"], $_POST["Observaciones_CNP"], $id]);
        }

        $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        $this->db->commit();
        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }

    private function duplicar(string $dbPrefix, int $semana): void
    {
        $id = (int) ($_POST["Id"] ?? 0);
        $sourceProgramId = $this->getWeeklyProgramId($dbPrefix, $id);
        if ($sourceProgramId === null) {
            $this->jsonError("No se encontró la actividad semanal a duplicar.");
            return;
        }

        $this->db->beginTransaction();
        $queryInsert = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad) SELECT ?, unique_id, unique_id, Id, Actividad, 0, 0, 'NA', Prog_Sin_Restricciones_100, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, 0 FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND row_id = ?";
        $res = $this->db->queryWithProject($queryInsert, [$semana, $semana, $id]);
        $this->syncNextWeekCarryover($dbPrefix, $semana, $sourceProgramId);
        $this->db->commit();
        $this->jsonResponse($res ? "BIEN" : "ERROR");
    }
    private function nuevo(string $dbPrefix, int $semana): void
    {
        $idBase = trim((string) ($_POST["idNuevo"] ?? ''));
        $query0 = "SELECT *, unique_id AS Consecutivo_en_Programa, row_id AS Consecutivo FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Semana = ? AND Id = ? AND Titulo = 0 AND Semanas_Inicio <= 12 AND Semanas_Inicio >= 1 AND Ejecutado = 0 LIMIT 1";
        $data0 = $this->db->queryWithProject($query0, [$semana, $idBase])->fetch(PDO::FETCH_ASSOC);
        if (!$data0) {
            $this->jsonError("Actividad base no válida.");
            return;
        }
        $queryInsert = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Descripcion, Ubicacion, Fecha_Inicio, Fecha_Fin, Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad, Unidad, cantidad_ppto, Compromiso, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100, codigo_actividad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'NA', 0, ?)";
        $subs = array_filter(array_map('trim', explode(',', $_POST["Sub_Contratista"])));
        $isFirst = true;
        $this->db->beginTransaction();
        foreach ($subs as $sub) {
            $this->db->queryWithProject($queryInsert, [$semana, $data0["unique_id"], $data0["unique_id"], $idBase, $_POST["Actividad"], $_POST["Descripcion"], $_POST["Ubicacion"], $data0["Fecha_Inicio"], $data0["Fecha_Fin"], $sub, $_POST["Responsable_AIA"], $_POST["Empresa"], $data0["Ejecutado"], $data0["medir_productividad"], $_POST["Unidad"] ?: '%', $data0["cantidad_ppto"], $isFirst ? $this->parseLocalizedFloat($_POST["Compromiso"]) : null, $data0["codigo_actividad"]]);
            $isFirst = false;
        }
        $this->syncNextWeekCarryover($dbPrefix, $semana, (int) ($data0["unique_id"] ?? $data0["Consecutivo_en_Programa"]));
        $this->db->commit();
        $this->jsonResponse("BIEN");
    }

    private function bloquearCompromisos(string $dbPrefix, int $semana): void
    {
        $queryCount = "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Activa = 1 AND (Compromiso IS NULL OR Compromiso <= 0 OR TRIM(COALESCE(Sub_Contratista, '')) = '' OR LOWER(TRIM(COALESCE(Sub_Contratista, ''))) = 'null' OR TRIM(COALESCE(Responsable_AIA, '')) = '' OR LOWER(TRIM(COALESCE(Responsable_AIA, ''))) = 'null')";
        if ($this->db->queryWithProject($queryCount, [$semana])->fetchColumn() > 0) {
            echo json_encode(["respuesta" => "No_Bloqueado", "mensaje" => "Hay actividades sin compromiso o sin asignaciones obligatorias."]);
            return;
        }
        $res = $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET Semanal_Confirmada = 1, fechaCierreCompromisos = ? WHERE Semana = ?", [$_POST["fechaCierreCompromisos"] ?: null, $semana]);
        if ($res) {
            $this->generarCIC($dbPrefix, $semana);
            echo json_encode(["respuesta" => "Bloqueado", "mensaje" => "Semana bloqueada y CIC generado."]);
        } else {
            $this->jsonError("No se pudo bloquear.");
        }
    }
    private function generarCIC(string $dbPrefix, int $semana): void
    {
        for ($s = 1; $s <= $semana; $s++) {
            $this->actualizarPacSubcontratistas($dbPrefix, $s);
            $subsSemana = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$s])->fetchAll(PDO::FETCH_COLUMN);
            foreach ($subsSemana as $sub) {
                $exists = $this->db->queryWithProject("SELECT 1 FROM " . $this->tbl($dbPrefix, 'cic') . " WHERE Semana = ? AND subcontratista = ?", [$s, $sub])->fetchColumn();
                if (!$exists) {
                    $this->db->queryWithProject("INSERT INTO " . $this->tbl($dbPrefix, 'cic') . " (Semana, subcontratista) VALUES (?, ?)", [$s, $sub]);
                }
            }
            $this->actualizarPacSubcontratistas($dbPrefix, $s);
        }
    }

    private function actualizarPacSubcontratistas(string $dbPrefix, int $s): void
    {
        $subs = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$s])->fetchAll(PDO::FETCH_COLUMN);
        foreach ($subs as $sub) {
            $stats = $this->db->queryWithProject("SELECT ROUND(AVG(P_Completado),3) as P_Com, ROUND(AVG(PAC),3) as PAC FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana=? AND Sub_Contratista =? AND (Activa=1 OR Activa='NA')", [$s, $sub])->fetch(PDO::FETCH_ASSOC);
            $this->db->queryWithProject("UPDATE " . $this->tbl($dbPrefix, 'cic') . " SET P_Completado = ?, PAC = ? WHERE subcontratista = ? AND Semana = ?", [$stats['P_Com'] ?? 0, $stats['PAC'] ?? 0, $sub, $s]);
        }
    }

    private function listarExcepciones(string $dbPrefix, int $semana): void
    {
        $area = $_SESSION['area'] ?? 'Construccion';
        $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql('', $area);
        $query = "SELECT Id, Actividad, Estado FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
            WHERE Semana = ? AND Titulo = 0
              AND COALESCE(Ejecutado, 0) <= 0.001
              AND NOT {$restrictionEligibilitySql}
              AND (
                Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
              )";
        $data = $this->db->queryWithProject($query, [$semana])->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["respuesta" => "BIEN", "data" => $data], JSON_UNESCAPED_UNICODE);
    }


    private function sanear(string $dbPrefix, int $semana): void
    {
        try {
            $area = $_SESSION['area'] ?? 'Construccion';
            $confirmada = $this->db->queryWithProject(
                "SELECT Semanal_Confirmada FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE Semana = ?",
                [$semana],
            )->fetchColumn();

            if ($confirmada == 1) {
                $this->jsonResponse("OK");
                return;
            }

            $fechaUltimoSaneo = $this->db->queryWithProject(
                "SELECT fecha_ultimo_saneo FROM " . $this->tbl($dbPrefix, 'semanas_activas') . " WHERE Semana = ?",
                [$semana],
            )->fetchColumn();

            if ($fechaUltimoSaneo !== null && $fechaUltimoSaneo !== false) {
                $lastChange = $this->db->queryWithProject(
                    "SELECT GREATEST(
                        COALESCE(MAX(Ult_Act_Est), '1970-01-01'),
                        COALESCE(MAX(Ult_Act_Restr), '1970-01-01')
                    ) FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Semana = ?",
                    [$semana],
                )->fetchColumn();

                if ($lastChange !== null && $lastChange !== false && $lastChange <= $fechaUltimoSaneo) {
                    $this->jsonResponse("OK");
                    return;
                }
            }

            $restrictionEligibilitySql = $this->getAutoprogramRestrictionEligibilitySql('', $area);
            $eligibleSubSql = "SELECT unique_id FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . "
                WHERE Semana = ? AND Titulo = 0
                  AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
                  AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                    OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";

            $this->db->queryWithProject("
                DELETE FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                WHERE Semana = ? AND Activa = '1'
                  AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
                  AND (Compromiso IS NULL OR Compromiso <= 0)
                  AND unique_id NOT IN ({$eligibleSubSql})
            ", [$semana, $semana]);

            $stmtExistentes = $this->db->queryWithProject(
                "SELECT DISTINCT(unique_id) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ?",
                [$semana],
            );
            $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);

            $whereExistentes = "";
            $paramsInsert = [$semana, $semana];
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
            WHERE Semana = ? AND Titulo = 0
              AND (COALESCE(Ejecutado, 0) > 0.001 OR {$restrictionEligibilitySql})
              AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
                OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')
              {$whereExistentes}";

            array_shift($paramsInsert);
            $stmtNuevas = $this->db->queryWithProject($sqlSelectNuevas, $paramsInsert);
            $nuevasFilas = $stmtNuevas->fetchAll(PDO::FETCH_NUM);

            if (!empty($nuevasFilas)) {
                $queryInsertSingle = "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . " (
                    Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($nuevasFilas as $f) {
                    $subsRaw = $f[7] ?? '';
                    $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
                    if (empty($subs)) {
                        $subs = [''];
                    }
                    foreach ($subs as $sub) {
                        $f[7] = $sub;
                        $this->db->queryWithProject($queryInsertSingle, $f);
                    }
                }
            }

            $stmtSemanal = $this->db->queryWithProject(
                "SELECT row_id AS Consecutivo, unique_id AS Consecutivo_En_Programa, Sub_Contratista FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Activa != 'NA' AND (Compromiso IS NULL OR Compromiso <= 0)",
                [$semana],
            );
            foreach ($stmtSemanal->fetchAll() as $item) {
                $con_pk = $item["Consecutivo"];
                $con_pg = $item["unique_id"] ?? $item["Consecutivo_En_Programa"];
                $sub_split = $item["Sub_Contratista"];

                $dataCons = $this->db->queryWithProject(
                    "SELECT *, row_id AS Consecutivo, unique_id AS Consecutivo_en_Programa FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Semana = ? AND unique_id = ?",
                    [$semana, $con_pg],
                )->fetch();
                if (!$dataCons) {
                    continue;
                }

                $dataAnt = $this->db->queryWithProject(
                    "SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND unique_id = ? AND Sub_Contratista = ?",
                    [$semana - 1, $con_pg, $sub_split],
                )->fetch();
                if (!$dataAnt) {
                    $dataAnt = $this->db->queryWithProject(
                        "SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND unique_id = ?",
                        [$semana - 1, $con_pg],
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
                    WHERE Semana = ? AND row_id = ?", [
                    $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
                    (float) $dataCons['Ejecutado'], 0, (int) ($dataCons["Ruta_Critica"] ?? 0),
                    $dataCons["Estado"], $dataAnt["Descripcion"] ?? null, $dataAnt["Ubicacion"] ?? null,
                    $dataAnt["Empresa"] ?? 'AIA', $dataCons["unidad"],
                    ((float) ($dataCons["cantidad_ppto"] ?? 0) > 0 ? (float) $dataCons["cantidad_ppto"] : null),
                    $dataCons["codigo_actividad"], $semana, $con_pk,
                ]);
            }

            $this->syncRestrictionFlags($dbPrefix, $semana, $area);
            $this->db->queryWithProject(
                "UPDATE " . $this->tbl($dbPrefix, 'semanas_activas') . " SET fecha_ultimo_saneo = NOW() WHERE Semana = ?",
                [$semana],
            );
            $this->jsonResponse("OK");
        } catch (Throwable $t) {
            error_log("Error sanear PS: " . $t->getMessage());
            $this->jsonResponse("OK");
        }
    }

    private function tnp(string $dbPrefix, int $semana): void
    {
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
                    "SELECT COUNT(*) FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE unique_id = ? AND Semana = ?",
                    [$consecutivo, $semana],
                );
                $exists = (int) $stmt->fetchColumn();

                if ($exists > 0) {
                    $this->db->queryWithProject(
                        "UPDATE " . $this->tbl($dbPrefix, 'programacion_semanal') . " SET
                         Ejecutado_Real = ?, Es_TNP = 1, Categoria_CP = ?, CP = ?,
                         Observaciones_CP = ?, PAC = NULL
                         WHERE unique_id = ? AND Semana = ?",
                        [$ejecutadoReal, $categoriaCp, $cp, $observacionesCp,
                         $consecutivo, $semana],
                    );
                    $this->jsonResponse("BIEN");
                    return;
                }
            }

            // INSERT — activity not in programacion_semanal
            $pgData = $this->db->queryWithProject(
                "SELECT * FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Id = ? AND Semana = ?",
                [$id, $semana],
            )->fetch(PDO::FETCH_ASSOC);

            if (!$pgData) {
                $this->jsonError("Actividad no encontrada en Programa Consolidado");
                return;
            }

            $maxCon = $this->db->queryWithProject(
                "SELECT MAX(row_id) as maxCon FROM " . $this->tbl($dbPrefix, 'programacion_semanal') . " WHERE Semana = ?",
                [$semana],
            )->fetch(PDO::FETCH_ASSOC);
            $nextConsecutivo = ($maxCon['maxCon'] ?? 0) + 1;

            $this->db->queryWithProject(
                "INSERT INTO " . $this->tbl($dbPrefix, 'programacion_semanal') . "
                 (row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Unidad, cantidad_ppto,
                  Compromiso, Ejecutado_Real, Activa, Es_TNP, Categoria_CP, CP, Observaciones_CP,
                  PAC, Sub_Contratista, Responsable_AIA)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, '1', 1, ?, ?, ?, NULL, ?, ?)",
                [$nextConsecutivo, $nextConsecutivo, $semana,
                 $consecutivo ?: ($pgData['unique_id'] ?? $pgData['Consecutivo_en_Programa'] ?? null),
                 $consecutivo ?: ($pgData['unique_id'] ?? $pgData['Consecutivo_en_Programa'] ?? null),
                 $pgData['Id'] ?? null, $pgData['Actividad'] ?? '', $pgData['Unidad'] ?? '',
                 $pgData['Cuantia'] ?? $pgData['cantidad_ppto'] ?? 0, $ejecutadoReal,
                 $categoriaCp, $cp, $observacionesCp,
                 $pgData['Sub_Contratista'] ?? null, $pgData['Responsable_AIA'] ?? null,
            ]);

            $this->jsonResponse("BIEN");
        } catch (Throwable $t) {
            $this->jsonError("Error TNP: " . $t->getMessage());
        }
    }

    private function importarActividadNoRequerida(string $dbPrefix, int $semana): void
    {
        $id = $_POST["Consecutivo"];
        $data = $this->db->queryWithProject("SELECT Actividad, Responsable_AIA, Sub_Contratista, unidad, cantidad_ppto FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " WHERE Semana = ? AND Id = ?", [$semana, $id])->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["respuesta" => "BIEN", "data" => $data], JSON_UNESCAPED_UNICODE);
    }








    public function getTnpActivities(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');
        $dbPrefix = $_GET['db'] ?? '';
        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$this->validateContext($dbPrefix, $semana)) {
            return;
        }

        try {
            $query = "SELECT pc.Id, pc.unique_id AS Consecutivo_en_Programa, pc.unique_id, pc.Actividad, pc.Sub_Contratista, pc.Responsable_AIA, pc.codigo_actividad,
                           CASE WHEN ps.unique_id IS NOT NULL THEN 1 ELSE 0 END AS previamente_programada
                    FROM " . $this->tbl($dbPrefix, 'programa_consolidado') . " pc
                    LEFT JOIN " . $this->tbl($dbPrefix, 'programacion_semanal') . " ps
                      ON pc.unique_id = ps.unique_id AND ps.Semana = ?
                    WHERE pc.Semana = ? AND pc.Titulo = 0
                      AND pc.Semanas_Inicio <= 12
                      AND pc.Semanas_Inicio >= 1
                      AND pc.Ejecutado = 0
                      AND (pc.Activa = 0 OR ps.unique_id IS NULL)
                    ORDER BY previamente_programada DESC, pc.codigo_actividad ASC";

            $stmt = $this->db->queryWithProject($query, [$semana, $semana]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError('Parámetro de base de datos inválido.');
            return;
        }
        if ($semana <= 0) {
            $this->jsonError('Semana inválida.');
            return;
        }

        try {
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

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            $this->jsonError('Parámetro de base de datos inválido.');
            return;
        }
        if ($semana <= 0) {
            $this->jsonError('Semana inválida.');
            return;
        }

        try {
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
}
