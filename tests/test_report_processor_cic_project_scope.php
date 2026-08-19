<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/RestrictionConfigResolver.php';
require_once __DIR__ . '/../src/Services/ReportProcessor.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Services\ReportProcessor;

/**
 * Verifica que la actualización CIC/CIP aísla cálculos y metadata por `project_id`.
 *
 * Hasta el 2026-08-19 dependía de datos vivos del proyecto 73 (Da Porto) y de un proyecto 75
 * que ya no existe: cualquier restauración ajena lo dejaba en rojo («no existe CIC del
 * proyecto 73»). Ahora corre sobre los proyectos sacrificables de `BiContractFixture`: el A
 * lleva la programación y metadata reales del escenario, y el B lleva centinelas con los
 * MISMOS nombres y metadata distinta — si un JOIN pierde el aislamiento por proyecto, la
 * metadata del centinela contamina al A y el test lo detecta. Todo dentro de una transacción
 * que se revierte al final.
 */

$db = Database::getInstance();
$failures = [];

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

$projectA = BiContractFixture::PROYECTO_A;
$projectB = BiContractFixture::PROYECTO_B;
$semana = 1;

try {
    BiContractFixture::seedCausalRows($db);
    BiContractFixture::seedCicScenario($db);
    \TableResolver::clearCache();

    $outsideCicBefore = cicProjectRows($db, 'cic', $projectB);
    $outsideCipBefore = cicProjectRows($db, 'cip', $projectB);

    $processorSource = file_get_contents(__DIR__ . '/../src/Services/ReportProcessor.php');
    if (!str_contains($processorSource, 'sub.project_id = cic.project_id')) {
        $failures[] = 'JOIN CIC no aisla metadata de subcontratistas por proyecto';
    }
    if (!str_contains($processorSource, 'prof.project_id = cip.project_id')) {
        $failures[] = 'JOIN CIP no aisla metadata de profesionales por proyecto';
    }

    // Espejo del contrato de fetchPacAndCompletadoStats: promedio de PAC y P_Completado sobre
    // las filas activas (Activa 1|NA) de la entidad en la semana.
    $expected = $db->query(
        "SELECT
            ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN PAC ELSE 0 END) /
                   COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS PAC,
            ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN P_Completado ELSE 0 END) /
                   COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS P_Completado
         FROM programacion_semanal
         WHERE project_id = ? AND Semana = ? AND Sub_Contratista = 'Proveedor CI Construccion'",
        [$projectA, $semana],
    )->fetch();

    $result = (new ReportProcessor())->updateCICProyectos(null);
    $warnings = array_values(array_filter(
        $result['messages'] ?? [],
        static fn (string $message): bool => (str_contains($message, 'Warning') || str_contains($message, 'Error CIC'))
            && str_contains($message, 'CI Sandbox'),
    ));
    if ($warnings !== []) {
        $failures[] = 'actualizacion CIC/CIP emitio warnings en los proyectos del fixture: ' . implode(' | ', $warnings);
    }

    // El proyecto B no tiene compromisos activos: su CIC/CIP debe quedar exactamente igual.
    if ($outsideCicBefore !== cicProjectRows($db, 'cic', $projectB)) {
        $failures[] = "CIC del proyecto {$projectB} cambio durante la actualizacion del proyecto {$projectA}";
    }
    if ($outsideCipBefore !== cicProjectRows($db, 'cip', $projectB)) {
        $failures[] = "CIP del proyecto {$projectB} cambio durante la actualizacion del proyecto {$projectA}";
    }

    $actualCic = $db->query(
        "SELECT PAC, PAC_Acum, P_Completado, P_Completado_Acum, correo_contacto, NIT
         FROM cic
         WHERE project_id = ? AND Semana = ? AND subcontratista = 'Proveedor CI Construccion'",
        [$projectA, $semana],
    )->fetch();
    if (!$actualCic) {
        $failures[] = "no existe CIC del proyecto {$projectA}";
    } else {
        foreach (['PAC', 'PAC_Acum'] as $column) {
            if (!cicDecimalMatches($expected['PAC'], $actualCic[$column])) {
                $failures[] = "CIC {$projectA} {$column} no coincide con su programacion";
            }
        }
        foreach (['P_Completado', 'P_Completado_Acum'] as $column) {
            if (!cicDecimalMatches($expected['P_Completado'], $actualCic[$column])) {
                $failures[] = "CIC {$projectA} {$column} no coincide con su programacion";
            }
        }
        if ($actualCic['correo_contacto'] !== 'proveedor-a@ci.invalid' || (string) $actualCic['NIT'] !== '900990200') {
            $failures[] = "CIC {$projectA} copio metadata del centinela del proyecto {$projectB}";
        }
    }

    $actualCip = $db->query(
        "SELECT PAC, PAC_Acum, P_Completado, P_Completado_Acum, correo_contacto
         FROM cip
         WHERE project_id = ? AND Semana = ? AND profesional = 'Profesional CI Construccion'",
        [$projectA, $semana],
    )->fetch();
    if (!$actualCip) {
        $failures[] = "no existe CIP del proyecto {$projectA}";
    } else {
        foreach (['PAC', 'PAC_Acum'] as $column) {
            if (!cicDecimalMatches($expected['PAC'], $actualCip[$column])) {
                $failures[] = "CIP {$projectA} {$column} no coincide con su programacion";
            }
        }
        foreach (['P_Completado', 'P_Completado_Acum'] as $column) {
            if (!cicDecimalMatches($expected['P_Completado'], $actualCip[$column])) {
                $failures[] = "CIP {$projectA} {$column} no coincide con su programacion";
            }
        }
        if ($actualCip['correo_contacto'] !== 'profesional-a@ci.invalid') {
            $failures[] = "CIP {$projectA} copio metadata del centinela del proyecto {$projectB}";
        }
    }
} catch (Throwable $error) {
    $failures[] = get_class($error) . ': ' . $error->getMessage();
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    \TableResolver::clearCache();
}

if ($failures !== []) {
    echo "=== CIC Project Scope: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "OK: CIC/CIP sin warnings; proyecto {$projectB} intacto; calculos y metadata del {$projectA} aislados\n";
echo "=== CIC Project Scope: OK ===\n";
