<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/RestrictionConfigResolver.php';
require_once __DIR__ . '/../src/Services/ReportProcessor.php';

use App\Services\ReportProcessor;

function cicProjectRows(Database $db, string $table, int $projectId): array
{
    return $db->query(
        "SELECT * FROM `{$table}` WHERE project_id = ? ORDER BY Id",
        [$projectId],
    )->fetchAll();
}

function cicDecimalMatches(mixed $expected, mixed $actual): bool
{
    return abs((float) $expected - (float) $actual) <= 0.0005;
}

$db = Database::getInstance();
$failures = [];
$outsideCicBefore = cicProjectRows($db, 'cic', 75);
$outsideCipBefore = cicProjectRows($db, 'cip', 75);

$db->beginTransaction();

try {
    $db->query(
        'INSERT INTO subcontratistas (project_id, Id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [75, 99, 'Proveedor CI Construccion', 'cross-project-sentinel@ci.invalid', '999999999', 'Sentinel', 'Sentinel', 1],
    );
    $db->query(
        'INSERT INTO profesionales (project_id, id, nombre, email, cargo, activo) VALUES (?, ?, ?, ?, ?, ?)',
        [75, 99, 'Profesional CI Construccion', 'cross-project-sentinel@ci.invalid', 'Sentinel', 1],
    );

    $processorSource = file_get_contents(__DIR__ . '/../src/Services/ReportProcessor.php');
    if (!str_contains($processorSource, 'sub.project_id = cic.project_id')) {
        $failures[] = 'JOIN CIC no aisla metadata de subcontratistas por proyecto';
    }
    if (!str_contains($processorSource, 'prof.project_id = cip.project_id')) {
        $failures[] = 'JOIN CIP no aisla metadata de profesionales por proyecto';
    }

    $expected = $db->query(
        "SELECT ROUND(AVG(PAC), 3) AS PAC, ROUND(AVG(P_Completado), 3) AS P_Completado
         FROM programacion_semanal
         WHERE project_id = 73 AND Semana = 1",
    )->fetch();

    $result = (new ReportProcessor())->updateCICProyectos(null);
    $warnings = array_values(array_filter(
        $result['messages'] ?? [],
        static fn (string $message): bool => str_contains($message, 'Warning') || str_contains($message, 'Error CIC'),
    ));
    if ($warnings !== []) {
        $failures[] = 'actualizacion CIC/CIP emitio warnings: ' . implode(' | ', $warnings);
    }

    if ($outsideCicBefore !== cicProjectRows($db, 'cic', 75)) {
        $failures[] = 'CIC del proyecto 75 cambio durante la actualizacion del proyecto 73';
    }
    if ($outsideCipBefore !== cicProjectRows($db, 'cip', 75)) {
        $failures[] = 'CIP del proyecto 75 cambio durante la actualizacion del proyecto 73';
    }

    $actualCic = $db->query(
        "SELECT PAC, PAC_Acum, P_Completado, P_Completado_Acum, correo_contacto, NIT
         FROM cic
         WHERE project_id = 73 AND Semana = 1 AND subcontratista = 'Proveedor CI Construccion'",
    )->fetch();
    if (!$actualCic) {
        $failures[] = 'no existe CIC del proyecto 73';
    } else {
        foreach (['PAC', 'PAC_Acum'] as $column) {
            if (!cicDecimalMatches($expected['PAC'], $actualCic[$column])) {
                $failures[] = "CIC 73 {$column} no coincide con su programacion";
            }
        }
        foreach (['P_Completado', 'P_Completado_Acum'] as $column) {
            if (!cicDecimalMatches($expected['P_Completado'], $actualCic[$column])) {
                $failures[] = "CIC 73 {$column} no coincide con su programacion";
            }
        }
        if ($actualCic['correo_contacto'] !== 'supplier73@ci.invalid' || (string) $actualCic['NIT'] !== '900000073') {
            $failures[] = 'CIC 73 copio metadata del proyecto 75';
        }
    }

    $actualCip = $db->query(
        "SELECT PAC, PAC_Acum, P_Completado, P_Completado_Acum, correo_contacto
         FROM cip
         WHERE project_id = 73 AND Semana = 1 AND profesional = 'Profesional CI Construccion'",
    )->fetch();
    if (!$actualCip) {
        $failures[] = 'no existe CIP del proyecto 73';
    } else {
        foreach (['PAC', 'PAC_Acum'] as $column) {
            if (!cicDecimalMatches($expected['PAC'], $actualCip[$column])) {
                $failures[] = "CIP 73 {$column} no coincide con su programacion";
            }
        }
        foreach (['P_Completado', 'P_Completado_Acum'] as $column) {
            if (!cicDecimalMatches($expected['P_Completado'], $actualCip[$column])) {
                $failures[] = "CIP 73 {$column} no coincide con su programacion";
            }
        }
        if ($actualCip['correo_contacto'] !== 'professional73@ci.invalid') {
            $failures[] = 'CIP 73 copio metadata del proyecto 75';
        }
    }
} catch (Throwable $error) {
    $failures[] = get_class($error) . ': ' . $error->getMessage();
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

if ($failures !== []) {
    echo "=== CIC Project Scope: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "OK: CIC/CIP sin warnings; proyecto 75 intacto; calculos y metadata del 73 aislados\n";
echo "=== CIC Project Scope: OK ===\n";
