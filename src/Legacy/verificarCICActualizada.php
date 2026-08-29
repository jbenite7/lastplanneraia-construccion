<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');
require_once __DIR__ . "/conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$db = $_POST["db"] ?? '';
$semana = $_POST["semana"] ?? 0;

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die(json_encode(0));
}

// Resolve table names via TableResolver
$tCic = TableResolver::resolveByPrefix($db, 'cic');

$scope = $dbInstance->dataScope()->current();
if (!$scope instanceof \App\Security\DataScope\ProjectScope) {
    throw new \App\Security\DataScope\MissingProjectScope('La operación requiere un proyecto activo.');
}
$projectId = $scope->projectId();

try {
    if ($db == 'cedi_pasto' || empty($db)) {
        $faltaCalificar = 0;
    } else {
        $faltaCalificar = listar($db, $semana, $dbInstance, $tCic, $projectId);
    }
} catch (Throwable $e) {
    error_log("Error fatal en verificarCICActualizada.php: " . $e->getMessage());
    $faltaCalificar = 0;
}
echo json_encode($faltaCalificar);

function listar($db, $semana, $dbInstance, $tCic, $projectId)
{
    $stmt = $dbInstance->queryWithProject(
        "SELECT COUNT(*) AS conteo FROM {$tCic} WHERE project_id = ? AND (Semana <= ?) AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'",
        [$projectId, $semana],
        $projectId,
    );
    $data = $stmt->fetch();
    $conteo = $data["conteo"] ?? 0;

    if ($conteo == 0) {
        return 0;
    } else {
        $query2 = "SELECT COUNT(*) AS conteo, GROUP_CONCAT(tabla.subcontratista SEPARATOR ', ') AS faltaCalificar
            FROM (
                SELECT c.Id, c.Semana, stats.semanasEnProyecto, c.subcontratista,
                       c.Calidad, c.GSA, c.SST, c.ADM
                FROM {$tCic} c
                INNER JOIN (
                    SELECT subcontratista, COUNT(*) AS semanasEnProyecto, MAX(Semana) AS maxSemana
                    FROM {$tCic}
                    WHERE project_id = ?
                      AND Semana <= ?
                      AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'
                    GROUP BY subcontratista
                ) stats
                  ON stats.subcontratista = c.subcontratista
                 AND stats.maxSemana = c.Semana
                WHERE c.project_id = ?
                  AND c.tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'
            ) AS tabla
            WHERE MOD(tabla.semanasEnProyecto, 8) = 0
              AND (tabla.Calidad = 'NR' OR tabla.GSA = 'NR' OR tabla.SST = 'NR' OR tabla.ADM = 'NR')";

        try {
            $stmt2 = $dbInstance->queryWithProject($query2, [$projectId, $semana, $projectId], $projectId);
            $dataFaltaCalificar = $stmt2->fetch();
            $conteoFaltaCalificar = $dataFaltaCalificar["conteo"] ?? 0;

            if ($conteoFaltaCalificar > 1) {
                return " de los Subcontratistas " . ($dataFaltaCalificar["faltaCalificar"] ?? '');
            } elseif ($conteoFaltaCalificar == 1) {
                return " del Subcontratista " . ($dataFaltaCalificar["faltaCalificar"] ?? '');
            } else {
                return 0;
            }
        } catch (Throwable $e) {
            error_log("Error CIC: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            return 0;
        }
    }
}
