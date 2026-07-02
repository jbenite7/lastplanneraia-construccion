<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function lrrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lrrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lrrAssert(bool $condition, string $message): void
{
    $condition ? lrrPass($message) : lrrFail($message);
}

function lrrActivity(Database $db, int $projectId, int $week, string $name): ?array
{
    $row = $db->query(
        'SELECT * FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND actividad = ? LIMIT 1',
        [$projectId, $week, $name],
    )->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function lrrAssertActivity(Database $db, int $projectId, int $week, string $project, string $name): void
{
    lrrAssert(lrrActivity($db, $projectId, $week, $name) !== null, "{$project}: existe {$name}");
}

function lrrAssertNoDuplicateNames(Database $db, int $projectId, int $week, string $project): void
{
    $duplicates = $db->query(
        'SELECT actividad, COUNT(*) total
         FROM actividades
         WHERE project_id = ? AND semanaActualizacion = ?
         GROUP BY actividad
         HAVING COUNT(*) > 1',
        [$projectId, $week],
    )->fetchAll(PDO::FETCH_ASSOC);
    lrrAssert($duplicates === [], "{$project}: no duplica familias por zona, piso o intervencion");
}

function lrrAssertEarliestSource(Database $db, int $projectId, int $week, string $project, string $activityName): void
{
    $activity = lrrActivity($db, $projectId, $week, $activityName);
    if ($activity === null) {
        lrrFail("{$project}: no se pudo validar inicio de {$activityName}");
        return;
    }

    $source = $db->query(
        'SELECT programa_unique_id, source_start_date
         FROM actividad_programa_fuentes
         WHERE project_id = ? AND semana = ? AND actividad_id = ?
         ORDER BY source_start_date ASC, programa_unique_id ASC
         LIMIT 1',
        [$projectId, $week, (int) $activity['Id']],
    )->fetch(PDO::FETCH_ASSOC);

    lrrAssert($source !== false, "{$project}: {$activityName} tiene fuentes trazables");
    if ($source === false) {
        return;
    }
    lrrAssert((string) $activity['fechaInicio'] === (string) $source['source_start_date'], "{$project}: {$activityName} usa la fecha fuente mas temprana");
    lrrAssert((int) $activity['actividadInicio'] === (int) $source['programa_unique_id'], "{$project}: {$activityName} usa la actividad fuente mas temprana");
}

function lrrAssertContract(Database $db, int $projectId, int $week, string $project, string $name, string $tipo, array $packages): void
{
    $activity = lrrActivity($db, $projectId, $week, $name);
    if ($activity === null) {
        lrrFail("{$project}: no se pudo validar contrato de {$name}");
        return;
    }
    lrrAssert((string) $activity['tipoContrato'] === $tipo, "{$project}: {$name} tipoContrato {$tipo}");
    foreach ($packages as $field => $expected) {
        lrrAssert((string) ($activity[$field] ?? '') === $expected, "{$project}: {$name} {$field} = {$expected}");
    }
}

function lrrProgramTermCount(Database $db, int $projectId, int $week, string $term): int
{
    return (int) $db->query(
        'SELECT COUNT(*) FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND UPPER(Actividad) LIKE ?',
        [$projectId, $week, '%' . mb_strtoupper($term, 'UTF-8') . '%'],
    )->fetchColumn();
}

echo "=== Listado reclassified real projects ===\n";

try {
    $db = Database::getInstance();
    $projects = [
        ['id' => 68, 'week' => 7, 'name' => 'JMC'],
        ['id' => 73, 'week' => 8, 'name' => 'Da Porto'],
    ];

    foreach ($projects as $project) {
        lrrAssertNoDuplicateNames($db, $project['id'], $project['week'], $project['name']);
    }

    foreach ([
        'Cimentaciones',
        'Estabilización del Suelo',
        'Aseo',
        'Carpinteria Metalica',
        'Topografía',
        'Red Hidrosanitaria',
        'Red Eléctrica',
        'Mesones de Cocina y Baños',
        'Paisajismo',
    ] as $family) {
        if ($family !== 'Mesones de Cocina y Baños') {
            lrrAssertActivity($db, 68, 7, 'JMC', $family);
        }
        lrrAssertActivity($db, 73, 8, 'Da Porto', $family);
    }

    foreach (['Red de Detección de Incendio', 'Red de Extinción de Incendios', 'Red de Telecomunicaciones'] as $family) {
        lrrAssertActivity($db, 68, 7, 'JMC', $family);
    }

    foreach (['INCENDIO', 'EXTINCION', 'DETECCION', 'TELECOM', 'DATOS', 'CCTV', 'RCI'] as $term) {
        lrrAssert(lrrProgramTermCount($db, 73, 8, $term) === 0, "Da Porto: no crea familia de {$term} sin fuente en programa");
    }

    foreach ([
        [68, 7, 'JMC', 'Topografía'],
        [68, 7, 'JMC', 'Red Eléctrica'],
        [73, 8, 'Da Porto', 'Topografía'],
        [73, 8, 'Da Porto', 'Cimentaciones'],
        [73, 8, 'Da Porto', 'Mesones de Cocina y Baños'],
    ] as [$projectId, $week, $projectName, $activityName]) {
        lrrAssertEarliestSource($db, $projectId, $week, $projectName, $activityName);
    }

    foreach ([[68, 7, 'JMC'], [73, 8, 'Da Porto']] as [$projectId, $week, $projectName]) {
        lrrAssertContract($db, $projectId, $week, $projectName, 'Red Eléctrica', 'MO,S', [
            'paqueteS1' => 'MATERIALES RED ELECTRICA',
            'paqueteMO1' => 'MANO DE OBRA RED ELECTRICA',
        ]);
        lrrAssertContract($db, $projectId, $week, $projectName, 'Pinturas Interiores y Exteriores', 'SI', [
            'paqueteSI1' => 'PINTURAS',
        ]);
    }
} catch (Throwable $e) {
    lrrFail($e->getMessage());
}

echo "=== Listado reclassified real projects: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
