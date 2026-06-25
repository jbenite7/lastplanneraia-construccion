<?php

require('./conexion.php');
require_once __DIR__ . '/estado_programa_general.php';

use App\Services\RestrictionConfigResolver;

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$db = $_GET['db'] ?? $_POST['db'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die('Error: Nombre de DB inválido');
}

try {
    $stmtMax = $dbInstance->query("SELECT MAX(Semana) as maxVal FROM {$db}_semanas_activas");
    $dataMax = $stmtMax->fetch();
    $maxSemana = (int) ($dataMax['maxVal'] ?? 0);

    // Resolve project Area ONCE before the loop (avoid N+1 queries)
    $config = RestrictionConfigResolver::resolve($db);
    $projectArea = $config['area'];
    $restrictionColumns = $config['allRestrictions'];
    $restrictionColumnsSql = implode(', ', $restrictionColumns);

    for ($semana = 1; $semana <= $maxSemana; $semana++) {
        $stmtSemana = $dbInstance->query(
            "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$db}_semanas_activas WHERE Semana = ?",
            [$semana],
        );
        $dataSemana = $stmtSemana->fetch();

        if (!$dataSemana || empty($dataSemana['Fecha_Inicio_Sem'])) {
            continue;
        }

        $fechaInicioSemana = $dataSemana['Fecha_Inicio_Sem'];
        $fechaFinSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;

        $stmtProg = $dbInstance->query(
            "SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin,
                    {$restrictionColumnsSql}
             FROM {$db}_programa_consolidado
             WHERE Semana = ?",
            [$semana],
        );
        $rowsProg = $stmtProg->fetchAll();

        foreach ($rowsProg as $data) {
            $id = (int) $data['Consecutivo_en_Programa'];
            $titulo = (int) ($data['Titulo'] ?? 0);

            $estadoRestricciones = 0;
            if ($titulo === 0) {
                $estadoRestricciones = RestrictionConfigResolver::calculateEstadoRestricciones($data, $projectArea);
            }

            $semanasInicio = pg_calculate_week_offset($data['Fecha_Inicio'] ?? null, $fechaInicioSemana);

            $estado = pg_calculate_status(
                $titulo,
                $data['Ejecutado'] ?? 0,
                $data['Fecha_Inicio'] ?? null,
                $data['Fecha_Fin'] ?? null,
                $fechaInicioSemana,
                $fechaFinSemana,
            );

            $dbInstance->query(
                "UPDATE {$db}_programa_consolidado
                 SET Semanas_Inicio = ?, Estado_Restricciones = ?, Estado = ?
                 WHERE Consecutivo_en_Programa = ? AND Semana = ?",
                [$semanasInicio, $estadoRestricciones, $estado, $id, $semana],
            );
        }

        usleep(500000);

        $dbInstance->query(
            "UPDATE {$db}_programa_consolidado
             SET Ruta_Critica = NULL
             WHERE Titulo = 1 AND Semana = ?",
            [$semana],
        );

        $dbInstance->query(
            "UPDATE {$db}_programa_consolidado
             SET Ejecutado = NULL, Semanas_Inicio = NULL
             WHERE Fecha_Inicio IS NULL AND Fecha_Fin IS NULL AND Titulo = 1 AND Semana = ?",
            [$semana],
        );

        echo "<li>Semana $semana OK";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Error');
}
