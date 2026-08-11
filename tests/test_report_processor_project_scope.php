<?php
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/RestrictionConfigResolver.php';
require_once __DIR__ . '/../src/Services/ReportProcessor.php';

use App\Services\ReportProcessor;

function reportScopeFail(string $message): void
{
    echo "=== Report Processor Scope: FAIL ===\n";
    echo " - {$message}\n";
    exit(1);
}

function reportScopeOk(string $message): void
{
    echo "OK: {$message}\n";
}

function reportScopeScalar(Database $db, string $sql, array $params = []): int
{
    return (int) $db->query($sql, $params)->fetchColumn();
}

function reportScopeColumnExists(Database $db, string $table, string $column): bool
{
    return reportScopeScalar(
        $db,
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column],
    ) > 0;
}

function reportScopeSnapshot(Database $db, string $table): array
{
    $row = $db->query("SELECT COUNT(*) AS c, COALESCE(SUM(id), 0) AS s FROM `{$table}`")->fetch();

    return [(int) $row['c'], (int) $row['s']];
}

$db = Database::getInstance();

$canonicalTables = [
    'general_curvas',
    'general_curvas_pdc',
    'general_informe_consolidado',
    'general_informe_pdc',
    'general_informe_restricciones_consolidado',
    'general_informe_subcontratistas',
];

foreach ($canonicalTables as $table) {
    if (!reportScopeColumnExists($db, $table, 'project_id')) {
        reportScopeFail("{$table}.project_id no existe; aplica la migracion 20260701_report_tables_project_scope.php");
    }
}

if (!reportScopeColumnExists($db, 'general_curvas_pdc', 'maxSemana')) {
    reportScopeFail('general_curvas_pdc.maxSemana no existe');
}

$aprBefore = reportScopeSnapshot($db, 'general_curvas_pdc_apr');

$processor = new ReportProcessor();
$processor->generateCurvaS();
$processor->generateReporteGeneral();
$processor->generateRestriccionesGeneral();
$processor->generateReportePDC();
$processor->generateReporteSubcontratistas();

$aprAfter = reportScopeSnapshot($db, 'general_curvas_pdc_apr');
if ($aprBefore !== $aprAfter) {
    reportScopeFail('general_curvas_pdc_apr cambio durante la regeneracion');
}
reportScopeOk('general_curvas_pdc_apr no se modifica');

foreach ($canonicalTables as $table) {
    $nullRows = reportScopeScalar($db, "SELECT COUNT(*) FROM `{$table}` WHERE project_id IS NULL");
    if ($nullRows !== 0) {
        reportScopeFail("{$table} tiene {$nullRows} filas con project_id NULL");
    }
}
reportScopeOk('tablas canonicas no tienen project_id NULL');

$projects = $db->query(
    "SELECT Id, Proyecto_Proceso, pdcActivo
     FROM general_proyectos_procesos
     WHERE Area = 'Construccion' AND Activo = 1"
)->fetchAll();

$restrictionColumns = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];

foreach ($projects as $project) {
    $projectId = (int) $project['Id'];
    $projectName = $project['Proyecto_Proceso'];

    $expectedGeneral = reportScopeScalar(
        $db,
        "SELECT COUNT(*)
         FROM programacion_semanal prog
         JOIN semanas_activas sem ON sem.project_id = prog.project_id AND sem.Semana = prog.Semana
         WHERE prog.project_id = ?
           AND prog.Semana >= ((SELECT MAX(Semana) FROM programacion_semanal WHERE project_id = ?) - 1)",
        [$projectId, $projectId],
    );
    $actualGeneral = reportScopeScalar($db, 'SELECT COUNT(*) FROM general_informe_consolidado WHERE project_id = ?', [$projectId]);
    if ($expectedGeneral !== $actualGeneral) {
        reportScopeFail("{$projectName}: informe consolidado esperado={$expectedGeneral}, actual={$actualGeneral}");
    }

    $expectedRestrictions = 0;
    foreach ($restrictionColumns as $column) {
        $expectedRestrictions += reportScopeScalar(
            $db,
            "SELECT COUNT(*)
             FROM programa_consolidado prog
             JOIN semanas_activas sem ON sem.project_id = prog.project_id AND sem.Semana = prog.Semana
             WHERE prog.project_id = ?
               AND prog.`{$column}` != 'N/A'
               AND prog.Titulo = 0
               AND prog.Actividad IS NOT NULL
               AND prog.Semanas_Inicio < 7
               AND prog.Ejecutado < 1
               AND prog.Semana >= ((SELECT MAX(Semana) FROM programa_consolidado WHERE project_id = ?) - 3)",
            [$projectId, $projectId],
        );
    }
    $actualRestrictions = reportScopeScalar($db, 'SELECT COUNT(*) FROM general_informe_restricciones_consolidado WHERE project_id = ?', [$projectId]);
    if ($expectedRestrictions !== $actualRestrictions) {
        reportScopeFail("{$projectName}: restricciones esperado={$expectedRestrictions}, actual={$actualRestrictions}");
    }

    $expectedSubs = reportScopeScalar($db, 'SELECT COUNT(*) FROM cic WHERE project_id = ?', [$projectId]);
    $actualSubs = reportScopeScalar($db, 'SELECT COUNT(*) FROM general_informe_subcontratistas WHERE project_id = ?', [$projectId]);
    if ($expectedSubs !== $actualSubs) {
        reportScopeFail("{$projectName}: subcontratistas esperado={$expectedSubs}, actual={$actualSubs}");
    }

    // El informe del PDC v1 se retiró el 2026-08-04 con el módulo (tabla `pdc` eliminada).
    // `general_informe_pdc` conserva el histórico y ya no se regenera, así que no hay
    // conteo de origen contra el que contrastarlo.
}

reportScopeOk('conteos de reporterias coinciden con fuentes scoped por proyecto');
echo "=== Report Processor Scope: OK ===\n";
