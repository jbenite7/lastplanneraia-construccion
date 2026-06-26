<?php

namespace App\Controllers\Api;

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
            // Sync loop: PAC + missing subs + integral for all weeks up to current
            for ($s = 1; $s <= $semana; $s++) {
                $conteo = $this->db->queryWithProject("SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE Semana = ?", [$s])->fetchColumn();
                if ($conteo > 0) {
                    $this->syncPac($dbPrefix, $s);
                }
                $this->generateMissingSubs($dbPrefix, $s);
                $this->updateIntegral($dbPrefix, $s);
            }

            // Build UNION query replicating legacy listar_CIC.php
            $filter = "AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'";
            $subs = $this->db->queryWithProject("SELECT DISTINCT(subcontratista) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE Semana <= ? {$filter} ORDER BY subcontratista ASC", [$semana])->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($subs)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(["data" => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $unionParts = [];
            $params = [];
            foreach ($subs as $sub) {
                $part = "SELECT `Id`, `Semana`, (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE `subcontratista` = ? AND Semana <= ? {$filter}) AS `semanasEnProyecto`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`, `mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`, `si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10` FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE `subcontratista` = ? AND Semana = (SELECT MAX(`Semana`) FROM " . TableResolver::resolveByPrefix($dbPrefix, 'cic') . " WHERE `subcontratista` = ? AND Semana <= ? {$filter}) {$filter}";
                $unionParts[] = $part;
                array_push($params, $sub, $semana, $sub, $sub, $semana);
            }

            $sql = "SELECT * FROM (" . implode(" UNION ", $unionParts) . ") AS tabla ORDER BY `Semana` DESC, `subcontratista` ASC";
            $rows = $this->db->queryWithProject($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

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
        $dbPrefix = $_SESSION['db'] ?? '';
        $opcion = $_POST["opcion"] ?? '';
        $id = $_POST["Id"] ?? null;
        $semana = $_POST["semana"] ?? null;

        try {
            if ($opcion === 'modificar_mdo' || $opcion === 'modificar_si') {
                $this->updateMetrics($dbPrefix, $id, $semana, $opcion);
            } else {
                $this->jsonError("Opción no soportada.");
            }
        } catch (Throwable $t) {
            $this->jsonError("Error CIC Save: " . $t->getMessage());
        }
    }

    private function syncPac(string $db, int $s): void
    {
        $subs = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM " . TableResolver::resolveByPrefix($db, 'programacion_semanal') . " WHERE Semana=? AND Sub_Contratista !='' AND (Activa=1 OR Activa='NA')", [$s])->fetchAll(PDO::FETCH_COLUMN);
        foreach ($subs as $sub) {
            $stats = $this->db->queryWithProject("SELECT ROUND(AVG(P_Completado),3) as P_Com, ROUND(AVG(PAC),3) as PAC FROM " . TableResolver::resolveByPrefix($db, 'programacion_semanal') . " WHERE Semana=? AND Sub_Contratista =? AND (Activa=1 OR Activa='NA')", [$s, $sub])->fetch(PDO::FETCH_ASSOC);
            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " SET P_Completado = ?, PAC = ? WHERE subcontratista = ? AND Semana = ?", [$stats['P_Com'] ?? 0, $stats['PAC'] ?? 0, $sub, $s]);
        }
    }

    private function generateMissingSubs(string $db, int $s): void
    {
        // 1. Auto-curación de registros huérfanos/nulos cruzando con el catálogo
        $this->db->queryWithProject("
            UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " c
            INNER JOIN " . TableResolver::resolveByPrefix($db, 'subcontratistas') . " s ON c.subcontratista = s.subcontratista
            SET c.correo_contacto = COALESCE(c.correo_contacto, s.correo_contacto),
                c.NIT = COALESCE(c.NIT, s.NIT),
                c.alcance = COALESCE(c.alcance, s.alcance),
                c.tipo_proveedor = COALESCE(c.tipo_proveedor, s.tipo_proveedor)
            WHERE c.tipo_proveedor IS NULL OR c.tipo_proveedor = ''
        ");

        // 2. Auto-curación de fallback específico para mano de obra directa interna
        $this->db->queryWithProject("
            UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . "
            SET tipo_proveedor = 'Mano de Obra',
                alcance = 'Mano de Obra'
            WHERE subcontratista = 'AIA (MO Directa)' AND (tipo_proveedor IS NULL OR tipo_proveedor = '')
        ");

        $subsInCic = $this->db->queryWithProject("SELECT subcontratista FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana = ?", [$s])->fetchAll(PDO::FETCH_COLUMN);
        $subsInPs = $this->db->queryWithProject("SELECT DISTINCT Sub_Contratista FROM " . TableResolver::resolveByPrefix($db, 'programacion_semanal') . " WHERE Semana = ? AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA')", [$s])->fetchAll(PDO::FETCH_COLUMN);

        $missing = array_diff($subsInPs, $subsInCic);
        foreach ($missing as $sub) {
            // Caso especial: AIA (MO Directa)
            if ($sub === 'AIA (MO Directa)') {
                $this->db->queryWithProject(
                    "INSERT INTO " . TableResolver::resolveByPrefix($db, 'cic') . " (Semana, subcontratista, tipo_proveedor, alcance) VALUES (?, ?, 'Mano de Obra', 'Mano de Obra')",
                    [$s, $sub],
                );
                continue;
            }

            // Consultar catálogo
            $meta = $this->db->queryWithProject(
                "SELECT correo_contacto, NIT, alcance, tipo_proveedor FROM " . TableResolver::resolveByPrefix($db, 'subcontratistas') . " WHERE subcontratista = ? LIMIT 1",
                [$sub],
            )->fetch(PDO::FETCH_ASSOC);

            if ($meta) {
                $this->db->queryWithProject(
                    "INSERT INTO " . TableResolver::resolveByPrefix($db, 'cic') . " (Semana, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES (?, ?, ?, ?, ?, ?)",
                    [$s, $sub, $meta['correo_contacto'], $meta['NIT'], $meta['alcance'], $meta['tipo_proveedor']],
                );
            } else {
                $this->db->queryWithProject("INSERT INTO " . TableResolver::resolveByPrefix($db, 'cic') . " (Semana, subcontratista) VALUES (?, ?)", [$s, $sub]);
            }
        }
    }

    private function updateMetrics(string $db, $id, $semana, string $type): void
    {
        $isMdo = ($type === 'modificar_mdo');
        $prefix = $isMdo ? 'mdo_' : 'si_';
        $obsKey = $isMdo ? 'mdo_Observaciones' : 'si_Observaciones';

        // 1. Collect individual checkbox fields
        $fields = [];
        $params = [];
        $values = []; // keyed store for calculation
        foreach ($_POST as $key => $value) {
            if (strpos($key, $prefix) === 0 && $key !== $obsKey) {
                $fields[] = "$key = ?";
                $params[] = ($value === '' ? null : $value);
                $values[$key] = $value;
            }
        }

        if (empty($fields)) {
            $this->jsonError("No hay campos para guardar.");
            return;
        }

        $obs = $_POST[$obsKey] ?? '';
        $params[] = $obs;
        $params[] = $id;

        $sql = "UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " SET " . implode(', ', $fields) . ", Observaciones = ? WHERE Id = ?";
        $this->db->queryWithProject($sql, $params);

        // 2. Calculate discipline averages (Calidad, GSA, SST, ADM)
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

        $map = ['cal' => 'Calidad', 'gsa' => 'GSA', 'sst' => 'SST', 'adm' => 'ADM'];
        $results = [];

        foreach ($disciplineConfig as $disc => $items) {
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

        // 3. Write discipline scores
        $this->db->queryWithProject(
            "UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " SET Calidad=?, GSA=?, SST=?, ADM=? WHERE Semana=? AND Id=?",
            [$results['Calidad'], $results['GSA'], $results['SST'], $results['ADM'], $semana, $id],
        );

        // 4. Recalculate PAC + Integral
        $this->recalculateAverages($db, $id, $semana);
        $this->jsonResponse("BIEN");
    }

    private function recalculateAverages(string $db, $id, $semana): void
    {
        // Fetch the row to determine its semana
        $row = $this->db->queryWithProject("SELECT Semana FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Id = ?", [$id])->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->updateIntegral($db, (int) $row['Semana']);
        }
    }

    private function updateIntegral(string $db, int $s): void
    {
        $rows = $this->db->queryWithProject("SELECT * FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana = ?", [$s])->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $cic) {
            $id = $cic['Id'];
            $sub = $cic['subcontratista'];
            $p = [$s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub, $s, $sub];

            $sql = "SELECT 
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND PAC!='NA') END) AS PAC_Acum,
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND P_Completado!='NA') END) AS P_Completado_Acum,
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND Calidad!='NA' AND Calidad!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND Calidad!='NA' AND Calidad!='NR') END) AS Calidad_Acum,
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND GSA!='NA' AND GSA!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND GSA!='NA' AND GSA!='NR') END) AS GSA_Acum,
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND SST!='NA' AND SST!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND SST!='NA' AND SST!='NR') END) AS SST_Acum,
                (SELECT CASE WHEN (SELECT COUNT(*) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND ADM!='NA' AND ADM!='NR')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Semana<=? AND subcontratista=? AND ADM!='NA' AND ADM!='NR') END) AS ADM_Acum";

            $acum = $this->db->queryWithProject($sql, $p)->fetch(\PDO::FETCH_ASSOC);
            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " SET PAC_Acum=?, P_Completado_Acum=?, Calidad_Acum=?, GSA_Acum=?, SST_Acum=?, ADM_Acum=? WHERE Id=?", [
                $acum['PAC_Acum'], $acum['P_Completado_Acum'], $acum['Calidad_Acum'], $acum['GSA_Acum'], $acum['SST_Acum'], $acum['ADM_Acum'], $id,
            ]);

            // Cal_Integral calculation (weighted formula from legacy)
            $row2 = $this->db->queryWithProject("SELECT * FROM " . TableResolver::resolveByPrefix($db, 'cic') . " WHERE Id=?", [$id])->fetch(\PDO::FETCH_ASSOC);
            $cal = $this->calcIntegral($row2['PAC'], $row2['Calidad'], $row2['SST'], $row2['GSA'], $row2['ADM']);
            $calAcum = $this->calcIntegral($row2['PAC_Acum'], $row2['Calidad_Acum'], $row2['SST_Acum'], $row2['GSA_Acum'], $row2['ADM_Acum']);
            $this->db->queryWithProject("UPDATE " . TableResolver::resolveByPrefix($db, 'cic') . " SET Cal_Integral=?, Cal_Integral_Acum=? WHERE Id=?", [$cal, $calAcum, $id]);
        }
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

    private function jsonError(string $msg): void
    {
        echo json_encode(["respuesta" => "ERROR", "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    }
}
