<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$failed = 0;
$projectId = 987655;
$week = 1;
$prefix = 'contract_sources_test';
$activityId = 1;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario'] = 'qa-contract-sources';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';

function csmPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function csmFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function csmCleanup(Database $db, int $projectId, string $prefix): void
{
    foreach ([
        'semi_auto_decisions',
        'semi_auto_suggestions',
        'semi_auto_runs',
        'semi_auto_feedback',
        'semi_auto_assistant_feedback',
        'semi_auto_learning_rules',
        'semi_auto_learning_candidates',
        'semi_auto_proactive_queue',
        'actividad_programa_fuentes',
        'actividades',
        'pdc',
        'programa_consolidado',
        'programa',
        'semanas_activas',
    ] as $table) {
        $db->query("DELETE FROM {$table} WHERE project_id = ?", [$projectId]);
    }
    $db->query('DELETE FROM general_proyectos_procesos WHERE Id = ? OR Base_de_Datos = ?', [$projectId, $prefix]);
}

function csmPackageNames(array $fields): array
{
    $names = [];
    foreach (['SI', 'S', 'MO', 'OC'] as $prefix) {
        for ($i = 1; $i <= 5; $i++) {
            $name = trim((string) ($fields["paquete{$prefix}{$i}"] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
    }

    return $names;
}

echo "=== Contratos activity sources multi-group ===\n";

try {
    $db = Database::getInstance();
    csmCleanup($db, $projectId, $prefix);

    $familyId = (int) $db->query(
        "SELECT id FROM general_pdc_familias WHERE codigo = 'ESTRUCTURA_CONCRETO' AND activa = 1 LIMIT 1",
    )->fetchColumn();
    if ($familyId <= 0) {
        throw new RuntimeException('No existe la familia activa ESTRUCTURA_CONCRETO.');
    }

    $db->query(
        "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
         VALUES (?, 'Contract Sources Test', ?, 'Construccion', 1, 1, 1)",
        [$projectId, $prefix],
    );
    $db->query(
        "INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (?, 1, ?, '2030-01-01', '2030-01-07')",
        [$projectId, $week],
    );
    $db->query(
        "INSERT INTO actividades
         (project_id, Id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio,
          fechaInicio, tipoContrato, semanaActualizacion, numeroSubcontratos)
         VALUES (?, ?, ?, 'Estructura en Concreto', 'Actividades asociadas a estructura en concreto.',
                 NULL, NULL, '2030-01-10', NULL, ?, 1)",
        [$projectId, $activityId, $activityId, $week],
    );

    $service = new SemiAutoService($db);
    $reflection = new ReflectionClass($service);
    $replace = $reflection->getMethod('replaceActivityProgramSources');
    $replace->setAccessible(true);
    $replace->invoke($service, $projectId, $activityId, $week, [
        [
            'unique_id' => 900101,
            'activity' => 'Columnas en concreto [Capitulo: Estructura, Intervencion 5A]',
            'start_date' => '2030-01-10',
            'context' => 'Estructura, intervención 5A',
            'location_hint' => 'Eje 48',
            'intervention_hint' => '5A',
            'family_id' => $familyId,
            'family' => 'Estructura en Concreto',
            'matched_rule' => 'familia_operativa',
            'confidence' => 93,
            'risk_flags' => [],
        ],
        [
            'unique_id' => 900102,
            'activity' => 'Columnas en concreto [Capitulo: Estructura, Intervencion 5B]',
            'start_date' => '2030-01-12',
            'context' => 'Estructura, intervención 5B',
            'location_hint' => 'Eje 49',
            'intervention_hint' => '5B',
            'family_id' => $familyId,
            'family' => 'Estructura en Concreto',
            'matched_rule' => 'familia_operativa',
            'confidence' => 93,
            'risk_flags' => [],
        ],
    ]);

    $context = ['projectId' => $projectId, 'dbPrefix' => $prefix, 'semana' => $week];
    $preview = $service->preview(SemiAutoService::MODULE_CONTRATOS, $context);
    $suggestion = null;
    foreach (($preview['suggestions'] ?? []) as $candidate) {
        if (($candidate['action'] ?? '') === 'update_contracts' && (int) ($candidate['target_pk'] ?? 0) === $activityId) {
            $suggestion = $candidate;
            break;
        }
    }

    if ($suggestion === null) {
        throw new RuntimeException('Contratos no generó sugerencia para la actividad con fuentes múltiples.');
    }

    $fields = $suggestion['proposed'] ?? [];
    ((int) ($fields['numeroSubcontratos'] ?? 0) === 2)
        ? csmPass('Contratos propone dos subcontratos por dos intervenciones fuente')
        : csmFail('Contratos no respetó las dos intervenciones fuente');

    (($suggestion['analysis']['quality_gate']['source_count'] ?? 0) === 2)
        ? csmPass('Contratos conserva las dos fuentes en la explicación')
        : csmFail('Contratos no expone las dos fuentes de origen');

    $packageNames = csmPackageNames($fields);
    !empty($packageNames)
        ? csmPass('Contratos propone paquetes de contratación')
        : csmFail('Contratos no propuso paquetes de contratación');
    count($packageNames) === count(array_unique($packageNames))
        ? csmPass('Contratos no duplica paquetes por fuente')
        : csmFail('Contratos duplicó paquetes por fuente: ' . implode(', ', $packageNames));

    $apply = $service->apply(SemiAutoService::MODULE_CONTRATOS, $context, (string) $preview['run_id'], [
        (string) $suggestion['suggestion_id'],
    ]);
    ((int) ($apply['aplicadas'] ?? 0) === 1 && (int) ($apply['errores'] ?? 0) === 0)
        ? csmPass('La sugerencia de Contratos se aplica sin errores')
        : csmFail('La sugerencia de Contratos no se aplicó correctamente');

    $row = $db->query(
        'SELECT * FROM actividades WHERE project_id = ? AND Id = ? AND semanaActualizacion = ? LIMIT 1',
        [$projectId, $activityId, $week],
    )->fetch(PDO::FETCH_ASSOC) ?: [];
    ((int) ($row['numeroSubcontratos'] ?? 0) === 2)
        ? csmPass('La actividad aplicada queda con dos subcontratos')
        : csmFail('La actividad aplicada no quedó con dos subcontratos');
    ((int) $db->query('SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?', [$projectId, $week])->fetchColumn() === 1)
        ? csmPass('Contratos no crea actividades duplicadas')
        : csmFail('Contratos creó actividades adicionales');
} catch (Throwable $e) {
    csmFail($e->getMessage());
} finally {
    if (isset($db)) {
        csmCleanup($db, $projectId, $prefix);
    }
}

echo "=== Contratos activity sources multi-group: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
