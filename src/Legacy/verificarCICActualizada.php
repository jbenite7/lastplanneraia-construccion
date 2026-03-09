<?php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . "/conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$db = $_POST["db"] ?? '';
$semana = $_POST["semana"] ?? 0;

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die(json_encode(0));
}

if ($db == 'cedi_pasto') {
    $faltaCalificar = 0;
} else {
    $faltaCalificar = listar($db, $semana, $dbInstance);
}
echo json_encode($faltaCalificar);

function listar($db, $semana, $dbInstance)
{
    $stmt = $dbInstance->query("SELECT COUNT(*) AS conteo FROM {$db}_cic WHERE (Semana <= ?) AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'", [$semana]);
    $data = $stmt->fetch();
    $conteo = $data["conteo"] ?? 0;

    if ($conteo == 0) {
        return '';
    } else {
        $stmt1 = $dbInstance->query("SELECT DISTINCT(subcontratista) FROM {$db}_cic WHERE (Semana <= ?) AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos' ORDER BY `subcontratista` ASC", [$semana]);
        $rows1 = $stmt1->fetchAll();

        if (count($rows1) > 0) {
            $query2 = "SELECT COUNT(*) AS conteo, GROUP_CONCAT(subcontratista SEPARATOR ', ') AS faltaCalificar FROM (";
            $unionParts = [];

            foreach ($rows1 as $data1) {
                // Safety: Escape subcontratista manually if putting in string, but better to parameterize.
                // Since this is a massive UNION, parameterization is tricky with unknown number of unions.
                // However, we can build the string safely if we trust the DB data or escape it.
                // Since subcontratista comes from DB, we 'trust' it relative to the app logic,
                // but let's escape single quotes just in case.
                $subcontratista = $data1["subcontratista"];
                $subSafe = str_replace("'", "''", $subcontratista);

                // Note: The original query logic involves a subquery to get 'semanasEnProyecto' and filtered by MAX week.
                $part = "SELECT `Id`, `Semana`, (SELECT COUNT(*) FROM {$db}_cic WHERE `subcontratista` = '$subSafe' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos') AS `semanasEnProyecto`, `subcontratista`, `correo_contacto`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones` FROM {$db}_cic WHERE `subcontratista` = '$subSafe' AND Semana = (SELECT MAX(`Semana`) FROM {$db}_cic WHERE `subcontratista` = '$subSafe' AND Semana <= $semana AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos') AND tipo_proveedor != 'Suministro de Materiales, Herramientas o Equipos'";
                $unionParts[] = $part;
            }

            if (empty($unionParts)) {
                return 0;
            }

            $query2 .= implode(" UNION ", $unionParts);
            $query2 .= ") AS tabla WHERE MOD(tabla.`semanasEnProyecto`, 8) = 0 AND (tabla.`Calidad`='NR' OR tabla.`GSA`='NR' OR tabla.`SST`='NR' OR tabla.`ADM`='NR')";

            try {
                $stmt2 = $dbInstance->query($query2);
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

                return 0; // Or return useful error for debug: return $e->getMessage();
            }
        } else {
            return 0;
        }
    }
}
