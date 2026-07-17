<?php

namespace App\Controllers\Api;

use App\Security\RbacCatalog;
use App\Security\RbacService;
use PDO;
use Throwable;

use TableResolver;
class CicApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cic.ver');
        $dbPrefix = $_SESSION['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? $_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$dbPrefix || $semana <= 0) {
            $this->jsonError("Sesión expirada o semana inválida.");
            return;
        }

        try {
            $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
            if (!$projectId) {
                $this->jsonError("Proyecto no encontrado.");
                return;
            }
            // Sync loop: PAC + missing subs + integral for all weeks up to current
            for ($s = 1; $s <= $semana; $s++) {
                $conteo = $this->db->query("SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE project_id = ? AND Semana = ?", [$projectId, $s])->fetchColumn();
                if ($conteo > 0) {
                    $this->syncPac($dbPrefix, $s);
                }
                $this->generateMissingSubs($dbPrefix, $s);
                $this->updateIntegral($dbPrefix, $s);
            }

            // Build UNION query replicating legacy listar_CIC.php
            $filter = "AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'";
            $subs = $this->db->query("SELECT DISTINCT(subcontratista) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE project_id = ? AND Semana <= ? {$filter} ORDER BY subcontratista ASC", [$projectId, $semana])->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($subs)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(["data" => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $unionParts = [];
            $params = [];
            foreach ($subs as $sub) {
                $part = "SELECT `Id`, `Semana`, (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE project_id = ? AND `subcontratista` = ? AND Semana <= ? {$filter}) AS `semanasEnProyecto`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE project_id = ? AND `subcontratista` = ? AND Semana = (SELECT MAX(`Semana`) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE project_id = ? AND `subcontratista` = ? AND Semana <= ? {$filter}) {$filter}";
                $unionParts[] = $part;
                array_push($params, $projectId, $sub, $semana, $projectId, $sub, $projectId, $sub, $semana);
            }

            $sql = "SELECT * FROM (" . implode(" UNION ", $unionParts) . ") AS tabla ORDER BY `Semana` DESC, `subcontratista` ASC";
            $rows = $this->db->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

            // Deduplicate by subcontratista
            $seen = [];
            $result = [];
            foreach ($rows as $row) {
                $key = $row['subcontratista'];
                if (!isset($seen[$key])) {
                    $result[] = $row;
                    $seen[$key] = true;
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["data" => $result], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $t) {
            $this->jsonError("Error CIC List: " . $t->getMessage());
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cic.editar');
        $role = (new RbacService($this->db))->resolveCurrentRole();
        $allowedDisciplines = RbacCatalog::cicDisciplinesForRole($role);
        if ($allowedDisciplines === []) {
            $this->jsonError('Su rol no puede calificar disciplinas CIC.', 403);
            return;
        }
        $dbPrefix = $_SESSION['db'] ?? '';
        $opcion = $_POST["opcion"] ?? '';
        $id = filter_var($_POST["Id"] ?? null, FILTER_VALIDATE_INT);
        $semana = filter_var($_POST["semana"] ?? null, FILTER_VALIDATE_INT);

        if (!$dbPrefix || !$id || !$semana) {
            $this->jsonError('Datos insuficientes para guardar CIC.', 422);
            return;
        }

        try {
            if ($opcion === 'modificar_mdo' || $opcion === 'modificar_si') {
                $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
                $table = TableResolver::resolveByPrefix($dbPrefix, 'cic');
                $row = $this->db->query(
                    "SELECT Semana, tipo_proveedor FROM {$table} WHERE project_id = ? AND Id = ?",
                    [$projectId, $id],
                )->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int) $row['Semana'] !== $semana) {
                    $this->jsonError('La calificación no pertenece a la semana seleccionada.', 409);
                    return;
                }
                $expected = $row['tipo_proveedor'] === 'Mano de Obra' ? 'modificar_mdo' : 'modificar_si';
                if ($opcion !== $expected) {
                    $this->jsonError('El formulario no corresponde al tipo de proveedor.', 422);
                    return;
                }
                $this->updateMetrics($dbPrefix, $id, $semana, $opcion, $allowedDisciplines);
            } else {
                $this->jsonError("Opción no soportada.");
            }
        } catch (Throwable $t) {
            $this->jsonError("Error CIC Save: " . $t->getMessage());
        }
    }

    private function syncPac(string $db, int $s): void
    {
        $projectId = TableResolver::getProjectIdByPrefix($db);
        $tPs = TableResolver::resolveByPrefix($db, 'programacion_semanal');
        $tCic = TableResolver::resolveByPrefix($db, 'cic');
        $subs = $this->db->query("SELECT DISTINCT Sub_Contratista FROM {$tPs} WHERE project_id = ? AND Semana=? AND Sub_Contratista !='' AND (Activa=1 OR Activa='NA')", [$projectId, $s])->fetchAll(PDO::FETCH_COLUMN);
        foreach ($subs as $sub) {
            $stats = $this->db->query("SELECT ROUND(AVG(P_Completado),3) as P_Com, ROUND(AVG(PAC),3) as PAC FROM {$tPs} WHERE project_id = ? AND Semana=? AND Sub_Contratista =? AND (Activa=1 OR Activa='NA')", [$projectId, $s, $sub])->fetch(PDO::FETCH_ASSOC);
            $this->db->query("UPDATE {$tCic} SET P_Completado = ?, PAC = ? WHERE project_id = ? AND subcontratista = ? AND Semana = ?", [$stats['P_Com'] ?? 0, $stats['PAC'] ?? 0, $projectId, $sub, $s]);
        }
    }

    private function generateMissingSubs(string $db, int $s): void
    {
        $projectId = TableResolver::getProjectIdByPrefix($db);
        $tCic = TableResolver::resolveByPrefix($db, 'cic');
        $tSubs = TableResolver::resolveByPrefix($db, 'subcontratistas');
        $tPs = TableResolver::resolveByPrefix($db, 'programacion_semanal');
        // 1. Auto-curación de registros huérfanos/nulos cruzando con el catálogo
        $this->db->query("
            UPDATE {$tCic} c
            INNER JOIN {$tSubs} s ON s.project_id = c.project_id AND c.subcontratista = s.subcontratista
            SET c.correo_contacto = COALESCE(c.correo_contacto, s.correo_contacto),
                c.NIT = COALESCE(c.NIT, s.NIT),
                c.alcance = COALESCE(c.alcance, s.alcance),
                c.tipo_proveedor = COALESCE(c.tipo_proveedor, s.tipo_proveedor)
            WHERE c.project_id = ? AND (c.tipo_proveedor IS NULL OR c.tipo_proveedor = '')
        ", [$projectId]);

        // 2. Auto-curación de fallback específico para mano de obra directa interna
        $this->db->query("
            UPDATE {$tCic}
            SET tipo_proveedor = 'Mano de Obra',
                alcance = 'Mano de Obra'
            WHERE project_id = ? AND subcontratista = 'AIA (MO Directa)' AND (tipo_proveedor IS NULL OR tipo_proveedor = '')
        ", [$projectId]);

        $subsInCic = $this->db->query("SELECT subcontratista FROM {$tCic} WHERE project_id = ? AND Semana = ?", [$projectId, $s])->fetchAll(PDO::FETCH_COLUMN);
        $subsInPs = $this->db->query("SELECT DISTINCT Sub_Contratista FROM {$tPs} WHERE project_id = ? AND Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$projectId, $s])->fetchAll(PDO::FETCH_COLUMN);

        $missing = array_diff($subsInPs, $subsInCic);
        foreach ($missing as $sub) {
            // Caso especial: AIA (MO Directa)
            if ($sub === 'AIA (MO Directa)') {
                $this->db->query(
                    "INSERT INTO {$tCic} (project_id, Id, Semana, subcontratista, tipo_proveedor, alcance) VALUES (?, ?, ?, ?, 'Mano de Obra', 'Mano de Obra')",
                    [$projectId, $this->nextCicId($projectId), $s, $sub],
                );
                continue;
            }

            // Consultar catálogo
            $meta = $this->db->query(
                "SELECT correo_contacto, NIT, alcance, tipo_proveedor FROM {$tSubs} WHERE project_id = ? AND subcontratista = ? LIMIT 1",
                [$projectId, $sub],
            )->fetch(PDO::FETCH_ASSOC);

            if ($meta) {
                $this->db->query(
                    "INSERT INTO {$tCic} (project_id, Id, Semana, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$projectId, $this->nextCicId($projectId), $s, $sub, $meta['correo_contacto'], $meta['NIT'], $meta['alcance'], $meta['tipo_proveedor']],
                );
            } else {
                $this->db->query("INSERT INTO {$tCic} (project_id, Id, Semana, subcontratista) VALUES (?, ?, ?, ?)", [$projectId, $this->nextCicId($projectId), $s, $sub]);
            }
        }
    }

    private function updateMetrics(
        string $db,
        $id,
        $semana,
        string $type,
        array $allowedDisciplines,
    ): void
    {
        $isMdo = ($type === 'modificar_mdo');
        $prefix = $isMdo ? 'mdo_' : 'si_';
        $obsKey = $isMdo ? 'mdo_Observaciones' : 'si_Observaciones';
        $allowedPattern = $isMdo
            ? '/^mdo_(?:cal_[1-3]|adm_[1-5]|gsa_[1-8]|sst_(?:[1-9]|10))$/'
            : '/^si_(?:cal_[1-3]|adm_[1-6]|gsa_(?:[1-9]|1[0-4])|sst_(?:[1-9]|10))$/';
        $disciplineConfig = $isMdo ? [
            'cal' => ['cal_1','cal_2','cal_3'],
            'adm' => ['adm_1','adm_2','adm_3','adm_4','adm_5'],
            'gsa' => ['gsa_1','gsa_2','gsa_3','gsa_4','gsa_5','gsa_6','gsa_7','gsa_8'],
            'sst' => ['sst_1','sst_2','sst_3','sst_4','sst_5','sst_6','sst_7','sst_8','sst_9','sst_10'],
        ] : [
            'cal' => ['cal_1','cal_2','cal_3'],
            'adm' => ['adm_1','adm_2','adm_3','adm_4','adm_5','adm_6'],
            'gsa' => ['gsa_1','gsa_2','gsa_3','gsa_4','gsa_5','gsa_6','gsa_7','gsa_8','gsa_9','gsa_10','gsa_11','gsa_12','gsa_13','gsa_14'],
            'sst' => ['sst_1','sst_2','sst_3','sst_4','sst_5','sst_6','sst_7','sst_8','sst_9','sst_10'],
        ];

        // 1. Collect individual checkbox fields
        $fields = [];
        $params = [];
        $values = []; // keyed store for calculation
        $submittedDisciplines = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, $prefix) === 0 && $key !== $obsKey) {
                if (!preg_match($allowedPattern, $key)) {
                    $this->jsonError('El formulario contiene un campo no permitido.', 422);
                    return;
                }
                preg_match('/^(?:mdo|si)_(cal|adm|gsa|sst)_/', $key, $disciplineMatch);
                $discipline = $disciplineMatch[1] ?? '';
                if (!in_array($discipline, $allowedDisciplines, true)) {
                    $this->jsonError('No puede calificar una disciplina ajena a su rol.', 403);
                    return;
                }
                $submittedDisciplines[$discipline] = true;
                if (!in_array((string) $value, ['0', '0.5', '1', 'NA', 'NR'], true)) {
                    $this->jsonError('El formulario contiene una calificación no permitida.', 422);
                    return;
                }
                $fields[] = "$key = ?";
                $params[] = ($value === '' ? null : $value);
                $values[$key] = $value;
            }
        }

        foreach (array_keys($submittedDisciplines) as $discipline) {
            foreach ($disciplineConfig[$discipline] as $item) {
                if (!array_key_exists($prefix . $item, $values)) {
                    $this->jsonError(
                        'Debe completar todas las preguntas de la disciplina antes de guardar.',
                        422,
                    );
                    return;
                }
            }
        }

        if (empty($fields)) {
            $this->jsonError("No hay campos para guardar.");
            return;
        }

        $obs = $_POST[$obsKey] ?? '';
        $params[] = $obs;
        $projectId = TableResolver::getProjectIdByPrefix($db);
        $params[] = $projectId;
        $params[] = $id;
        $params[] = $semana;

        $tCic = TableResolver::resolveByPrefix($db, 'cic');
        $sql = "UPDATE {$tCic} SET " . implode(', ', $fields)
            . ", Observaciones = ? WHERE project_id = ? AND Id = ? AND Semana = ?";
        $this->db->beginTransaction();
        try {
        $this->db->query($sql, $params);

        // 2. Calculate discipline averages (Calidad, GSA, SST, ADM)
        $map = ['cal' => 'Calidad', 'gsa' => 'GSA', 'sst' => 'SST', 'adm' => 'ADM'];
        $results = [];

        foreach ($disciplineConfig as $disc => $items) {
            if (!isset($submittedDisciplines[$disc])) {
                continue;
            }
            $count = 0;
            $countNR = 0;
            $sum = 0;
            foreach ($items as $item) {
                $k = $prefix . $item;
                $v = $values[$k] ?? null;
                if ($v === 'NA' || $v === 'NR') {
                    if ($v === 'NR') {
                        $countNR++;
                    }
                } else {
                    $count++;
                    $sum += (float) $v;
                }
            }
            if ($count === 0) {
                $results[$map[$disc]] = ($countNR === count($items)) ? 'NR' : 'NA';
            } else {
                $results[$map[$disc]] = round($sum / $count, 3);
            }
        }

        // 3. Write only the discipline scores present in this authorized payload.
        $scoreAssignments = [];
        $scoreParams = [];
        foreach ($results as $column => $result) {
            $scoreAssignments[] = "{$column}=?";
            $scoreParams[] = $result;
        }
        array_push($scoreParams, $projectId, $semana, $id);
        $this->db->query(
            "UPDATE {$tCic} SET " . implode(', ', $scoreAssignments)
                . " WHERE project_id = ? AND Semana=? AND Id=?",
            $scoreParams,
        );

        // 4. Recalculate PAC + Integral
        $this->recalculateAverages($db, $id, $semana);
            $this->db->commit();
        } catch (Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }
        $this->jsonResponse("BIEN");
    }

    private function recalculateAverages(string $db, $id, $semana): void
    {
        // Fetch the row to determine its semana
        $projectId = TableResolver::getProjectIdByPrefix($db);
        $row = $this->db->query("SELECT Semana FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE project_id = ? AND Id = ?", [$projectId, $id])->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->updateIntegral($db, (int) $row['Semana']);
        }
    }

    private function updateIntegral(string $db, int $s): void
    {
        $projectId = TableResolver::getProjectIdByPrefix($db);
        $tCic = TableResolver::resolveByPrefix($db, 'cic');
        $rows = $this->db->query("SELECT * FROM {$tCic} WHERE project_id = ? AND Semana = ?", [$projectId, $s])->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $cic) {
            $id = $cic['Id'];
            $sub = $cic['subcontratista'];

            $sql = "SELECT
                CASE WHEN SUM(PAC!='NA')=0 THEN NULL ELSE ROUND(AVG(CASE WHEN PAC!='NA' THEN CAST(PAC AS DECIMAL(10,3)) END),3) END AS PAC_Acum,
                CASE WHEN SUM(P_Completado!='NA')=0 THEN NULL ELSE ROUND(AVG(CASE WHEN P_Completado!='NA' THEN CAST(P_Completado AS DECIMAL(10,3)) END),3) END AS P_Completado_Acum,
                CASE WHEN SUM(Calidad!='NA' AND Calidad!='NR')=0 THEN 'NA' ELSE ROUND(AVG(CASE WHEN Calidad!='NA' AND Calidad!='NR' THEN CAST(Calidad AS DECIMAL(10,3)) END),3) END AS Calidad_Acum,
                CASE WHEN SUM(GSA!='NA' AND GSA!='NR')=0 THEN 'NA' ELSE ROUND(AVG(CASE WHEN GSA!='NA' AND GSA!='NR' THEN CAST(GSA AS DECIMAL(10,3)) END),3) END AS GSA_Acum,
                CASE WHEN SUM(SST!='NA' AND SST!='NR')=0 THEN 'NA' ELSE ROUND(AVG(CASE WHEN SST!='NA' AND SST!='NR' THEN CAST(SST AS DECIMAL(10,3)) END),3) END AS SST_Acum,
                CASE WHEN SUM(ADM!='NA' AND ADM!='NR')=0 THEN 'NA' ELSE ROUND(AVG(CASE WHEN ADM!='NA' AND ADM!='NR' THEN CAST(ADM AS DECIMAL(10,3)) END),3) END AS ADM_Acum
                FROM {$tCic}
                WHERE project_id = ? AND Semana <= ? AND subcontratista = ?";

            $acum = $this->db->query($sql, [$projectId, $s, $sub])->fetch(\PDO::FETCH_ASSOC);
            $this->db->query("UPDATE {$tCic} SET PAC_Acum=?, P_Completado_Acum=?, Calidad_Acum=?, GSA_Acum=?, SST_Acum=?, ADM_Acum=? WHERE project_id = ? AND Id=?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Calidad_Acum'], $acum['GSA_Acum'], $acum['SST_Acum'], $acum['ADM_Acum'], $projectId, $id,
            ]);

            // Cal_Integral calculation (weighted formula from legacy)
            $row2 = $this->db->query("SELECT * FROM {$tCic} WHERE project_id = ? AND Id=?", [$projectId, $id])->fetch(\PDO::FETCH_ASSOC);
            $cal = $this->calcIntegral($row2['PAC'], $row2['Calidad'], $row2['SST'], $row2['GSA'], $row2['ADM']);
            $calAcum = $this->calcIntegral($row2['PAC_Acum'], $row2['Calidad_Acum'], $row2['SST_Acum'], $row2['GSA_Acum'], $row2['ADM_Acum']);
            $this->db->query("UPDATE {$tCic} SET Cal_Integral=?, Cal_Integral_Acum=? WHERE project_id = ? AND Id=?", [$cal, $calAcum, $projectId, $id]);
        }
    }

    private function nextCicId(int $projectId): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(Id), 0) + 1 FROM cic WHERE project_id = ?', [$projectId])->fetchColumn();
    }

    private function calcIntegral($pac, $cal, $sst, $gsa, $adm): ?float
    {
        if ($pac === '' || $pac === null) {
            return null;
        }
        $dims = ['cal' => $cal, 'sst' => $sst, 'gsa' => $gsa, 'adm' => $adm];
        $weights = ['cal' => 0.2, 'sst' => 0.2, 'gsa' => 0.2, 'adm' => 0.1];
        $pacW = 0.3;
        $spare = 0;
        $active = [];
        foreach ($dims as $k => $v) {
            if ($v === 'NA' || $v === 'NR' || $v === '' || $v === null) {
                $spare += $weights[$k];
            } else {
                $active[$k] = (float) $v;
            }
        }
        if (empty($active)) {
            return round((float) $pac * 1.0, 3);
        }
        $totalActiveW = array_sum(array_intersect_key($weights, $active));
        $result = (float) $pac * ($pacW + $spare * ($pacW / ($pacW + $totalActiveW)));
        foreach ($active as $k => $v) {
            $result += $v * ($weights[$k] + $spare * ($weights[$k] / ($pacW + $totalActiveW)));
        }
        return round($result, 3);
    }

    private function jsonResponse(string $res): void
    {
        echo json_encode(["respuesta" => $res], JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $msg, int $status = 422): void
    {
        http_response_code($status);
        echo json_encode(["respuesta" => "ERROR", "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    }
}
