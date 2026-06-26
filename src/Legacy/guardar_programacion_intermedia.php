<?php

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/estado_programa_general.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$dbSession = trim((string) ($_SESSION['db'] ?? ''));
$dbRequest = trim((string) ($_GET['db'] ?? ($_POST['db'] ?? '')));

$dbPrefix = $dbRequest !== '' ? $dbRequest : $dbSession;
$opcion = $_POST["opcion"] ?? '';

if (!function_exists('pi_json_error')) {
    function pi_json_error(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        echo json_encode([
            "respuesta" => "ERROR",
            "mensaje" => $message,
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('pi_parse_positive_int')) {
    function pi_parse_positive_int($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($parsed === false) {
            return null;
        }

        return (int) $parsed;
    }
}

if ($dbRequest !== '' && $dbSession !== '' && $dbRequest !== $dbSession) {
    pi_json_error('Conflicto de contexto: la base de datos solicitada no coincide con la sesion activa.', 409);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
    pi_json_error('Base de datos invalida o sesion expirada.', 400);
    exit;
}

if ($dbSession === '') {
    $_SESSION['db'] = $dbPrefix;

    // Centralizar contexto de proyecto en Database
    $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
    if ($projectId) {
        $dbInstance->setProjectContext($projectId);
    }
}

// Resolve table names via TableResolver
$tProgConsolidado = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');
$tSemanasActivas = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');

$semanaRequest = pi_parse_positive_int($_GET['semana'] ?? ($_POST['semana'] ?? null));
$semanaSession = pi_parse_positive_int($_SESSION['semana'] ?? null);

if ($semanaRequest !== null && $semanaSession !== null && $semanaRequest !== $semanaSession) {
    pi_json_error('Conflicto de contexto: la semana solicitada no coincide con la sesion activa.', 409);
    exit;
}

$semana = $semanaRequest ?? $semanaSession;
if ($semanaRequest !== null) {
    $_SESSION['semana'] = $semanaRequest;
}

if (!function_exists('pi_parse_ratio_input')) {
    function pi_parse_ratio_input($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || strcasecmp($raw, 'N/A') === 0 || strcasecmp($raw, 'NO APLICA') === 0) {
            return null;
        }

        $normalized = pg_normalize_numeric($raw);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        $ratio = (float) $normalized;

        if (strpos($raw, '%') !== false) {
            $ratio = $ratio / 100;
        }

        while ($ratio > 1.0 && $ratio <= 10000.0) {
            $ratio = $ratio / 100;
        }

        if ($ratio < 0.0) {
            $ratio = 0.0;
        }

        if ($ratio > 1.0) {
            $ratio = 1.0;
        }

        return $ratio;
    }
}

if (!function_exists('pi_snap_allowed_ratio')) {
    function pi_snap_allowed_ratio(float $ratio, array $allowed): float
    {
        $nearest = (float) $allowed[0];
        $minDiff = abs($nearest - $ratio);

        foreach ($allowed as $candidate) {
            $candidateRatio = (float) $candidate;
            $diff = abs($candidateRatio - $ratio);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $candidateRatio;
            }
        }

        return $nearest;
    }
}

if (!function_exists('pi_clean_restriction_input')) {
    function pi_clean_restriction_input($value, array $allowed)
    {
        $ratio = pi_parse_ratio_input($value);
        if ($ratio === null) {
            return 'N/A';
        }

        $snapped = pi_snap_allowed_ratio($ratio, $allowed);
        if ($snapped <= 0.0) {
            return 0;
        }

        if ($snapped >= 1.0) {
            return 1;
        }

        return round($snapped, 5);
    }
}

switch ($opcion) {
    case 'modificar':
        if ($semana === null || $semana < 1) {
            pi_json_error('Semana invalida para modificar programacion intermedia.', 400);
            break;
        }

        $Id = pi_parse_positive_int($_POST['Id'] ?? null);
        if ($Id === null) {
            pi_json_error('Id de actividad invalido para modificar programacion intermedia.', 400);
            break;
        }

        // Detect area type for dynamic field handling
        $isPreConstruccion = isset($_SESSION['area']) && stripos($_SESSION['area'], 'Pre-Construccion') !== false;

        $longScale = [0.0, 0.33, 0.66, 1.0];
        $halfScale = [0.0, 0.5, 1.0];

        if ($isPreConstruccion) {
            // Pre-Construction uses 4 restriction fields
            $D_y_E = pi_clean_restriction_input($_POST['restriccion_pc_1'] ?? 'N/A', $longScale);
            $Materiales = pi_clean_restriction_input($_POST['restriccion_pc_2'] ?? 'N/A', $longScale);
            $MdeO = pi_clean_restriction_input($_POST['restriccion_pc_3'] ?? 'N/A', $longScale);
            $Equipos = pi_clean_restriction_input($_POST['restriccion_pc_4'] ?? 'N/A', $longScale);
            $Predecesora = 'N/A';
            $Pdto_Cons = 'N/A';
            $Modelo = 'N/A';
        } else {
            // Construction uses original 7 fields
            $D_y_E = pi_clean_restriction_input($_POST['D_y_E'] ?? 'N/A', $longScale);
            $Materiales = pi_clean_restriction_input($_POST['Materiales'] ?? 'N/A', $longScale);
            $MdeO = pi_clean_restriction_input($_POST['MdeO'] ?? 'N/A', $longScale);
            $Equipos = pi_clean_restriction_input($_POST['Equipos'] ?? 'N/A', $longScale);
            $Predecesora = pi_clean_restriction_input($_POST['Predecesora'] ?? 'N/A', $halfScale);
            $Pdto_Cons = pi_clean_restriction_input($_POST['Pdto_Cons'] ?? 'N/A', $halfScale);
            $Modelo = pi_clean_restriction_input($_POST['Modelo'] ?? 'N/A', $halfScale);
        }

        $Sub_Contratista = $_POST['Sub_Contratista'] ?? '';
        $Responsable_AIA = $_POST['Responsable_AIA'] ?? '';
        $Observaciones = $_POST['Observaciones'] ?? '';

        modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana, fecha_inicio_sem($semana, $tSemanasActivas, $dbInstance), $dbPrefix, $dbInstance, $isPreConstruccion, $tProgConsolidado, $tSemanasActivas);
        break;
    default:
        pi_json_error('Opcion no valida para programacion intermedia.', 400);
        break;
}

function modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana, $inicio_semana, $dbPrefix, $dbInstance, $isPreConstruccion, $tProgConsolidado, $tSemanasActivas)
{
    try {
        $dbInstance->beginTransaction();

        $stmtSi = $dbInstance->queryWithProject("SELECT Semanas_Inicio FROM {$tProgConsolidado} WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$Id, $semana]);
        $rowSi = $stmtSi->fetch(PDO::FETCH_ASSOC);
        $si = is_numeric($rowSi['Semanas_Inicio'] ?? null) ? (int) $rowSi['Semanas_Inicio'] : 999;

        if ($si <= 6) {
            $sql = "UPDATE {$tProgConsolidado} SET Activa = 1 WHERE Consecutivo_en_Programa = ? AND Semana = ?";
            $dbInstance->queryWithProject($sql, [$Id, $semana]);
        }

        if ($isPreConstruccion) {
            $sql1 = "UPDATE {$tProgConsolidado} SET restriccion_pc_1 = ?, restriccion_pc_2 = ?, restriccion_pc_3 = ?, restriccion_pc_4 = ?, Sub_Contratista = ?, Responsable_AIA = ?, Observaciones = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?";
            $dbInstance->queryWithProject($sql1, [$D_y_E, $Materiales, $MdeO, $Equipos, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana]);
        } else {
            $sql1 = "UPDATE {$tProgConsolidado} SET D_y_E = ?, Materiales = ?, MdeO = ?, Equipos = ?, Predecesora = ?, Pdto_Cons = ?, Modelo = ?, Sub_Contratista = ?, Responsable_AIA = ?, Observaciones = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?";
            $dbInstance->queryWithProject($sql1, [$D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Sub_Contratista, $Responsable_AIA, $Observaciones, $Id, $semana]);
        }

        // Logic from modificar_rest, simplified for the single updated row
        $campos = [
            ['valor' => $D_y_E, 'threshold' => 1.0],
            ['valor' => $Materiales, 'threshold' => 1.0],
            ['valor' => $MdeO, 'threshold' => 1.0],
            ['valor' => $Equipos, 'threshold' => 1.0],
            ['valor' => $Predecesora, 'threshold' => 0.5],
            ['valor' => $Pdto_Cons, 'threshold' => 1.0],
            ['valor' => $Modelo, 'threshold' => 1.0],
        ];
        $conteo = 0;
        $suma = 0;

        foreach ($campos as $campo) {
            $val = $campo['valor'];
            if ($val !== "N/A") {
                $conteo++;
                $suma += min(round((float) $val, 5) / $campo['threshold'], 1.0);
            }
        }

        if ($conteo == 0) {
            $Estado_Restricciones = 1;
        } else {
            $Estado_Restricciones = round(($suma / $conteo), 5);
        }

        $sql3 = "UPDATE {$tProgConsolidado} SET Estado_Restricciones = ? WHERE Consecutivo_en_Programa = ? AND Titulo = 0 AND Semana = ?";
        $dbInstance->queryWithProject($sql3, [$Estado_Restricciones, $Id, $semana]);

        $dbInstance->commit();

        $dbInstance->logActivity('ProgramacionIntermedia', 'MODIFICAR', "Actualizó restricciones actividad ID $Id semana $semana", $dbPrefix);

        // Call modificar_estado_act after the transaction commits
        $estadoActividad = modificar_estado_act($Id, $semana, $inicio_semana, $tProgConsolidado, $tSemanasActivas, $dbInstance);

        echo json_encode([
            "respuesta" => "BIEN",
            "estado_restricciones" => $Estado_Restricciones,
            "estado" => $estadoActividad['Estado'] ?? null,
            "semanas_inicio" => $estadoActividad['Semanas_Inicio'] ?? null,
        ]);

    } catch (Exception $e) {
        $dbInstance->rollBack();
        pi_json_error('No se pudo guardar la programacion intermedia. Intente nuevamente.', 500);
    }
}

function modificar_estado_act($Id, $semana, $inicio_semana, $tProgConsolidado, $tSemanasActivas, $dbInstance)
{
    $stmtSemana = $dbInstance->queryWithProject("SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSemanasActivas} WHERE Semana = ?", [$semana]);
    $dataSemana = $stmtSemana->fetch(PDO::FETCH_ASSOC);

    if (!$dataSemana || empty($dataSemana['Fecha_Inicio_Sem'])) {
        return ["Semanas_Inicio" => null, "Estado" => null];
    }

    $fechaInicioSemana = $dataSemana['Fecha_Inicio_Sem'];
    $fechaFinSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;

    $stmtActividad = $dbInstance->queryWithProject(
        "SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
         FROM {$tProgConsolidado}
         WHERE Consecutivo_en_Programa = ? AND Semana = ?",
        [$Id, $semana],
    );
    $dataAct = $stmtActividad->fetch(PDO::FETCH_ASSOC);

    if (!$dataAct) {
        return ["Semanas_Inicio" => null, "Estado" => null];
    }

    $semanasInicio = pg_calculate_week_offset($dataAct['Fecha_Inicio'] ?? null, $fechaInicioSemana);
    $estado = pg_calculate_status(
        $dataAct['Titulo'] ?? 0,
        $dataAct['Ejecutado'] ?? 0,
        $dataAct['Fecha_Inicio'] ?? null,
        $dataAct['Fecha_Fin'] ?? null,
        $fechaInicioSemana,
        $fechaFinSemana,
    );

    $dbInstance->queryWithProject(
        "UPDATE {$tProgConsolidado}
         SET Semanas_Inicio = ?, Estado = ?
         WHERE Consecutivo_en_Programa = ? AND Semana = ?",
        [$semanasInicio, $estado, $Id, $semana],
    );

    return [
        "Semanas_Inicio" => $semanasInicio,
        "Estado" => $estado,
    ];
}

function fecha_inicio_sem($semana, $tSemanasActivas, $dbInstance)
{
    $stmt = $dbInstance->queryWithProject("SELECT COUNT(*) FROM {$tSemanasActivas}");
    $conteo = $stmt->fetchColumn();

    if ($conteo == 0) {
        $inicio_semana = null;
    } else {
        $stmt = $dbInstance->queryWithProject("SELECT Fecha_Inicio_Sem FROM {$tSemanasActivas} WHERE Semana = ?", [$semana]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $inicio_semana = $data["Fecha_Inicio_Sem"] ?? null;
    }

    return $inicio_semana;
}
