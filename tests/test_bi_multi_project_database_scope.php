<?php

declare(strict_types=1);
// @requiere: db

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScopeViolation;
use App\Security\DataScope\SystemScope;
use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricScope;

$db = Database::getInstance();
$context = $db->dataScope();
$marker = 'RLS_BI_' . bin2hex(random_bytes(6));
$projectA = random_int(991000, 992999);
$projectB = $projectA + 3000;
$projectC = $projectA + 6000;
$week = random_int(8100, 8900);
$failures = [];
$rolledBack = false;

try {
    $context->clear();
    $context->bind(SystemScope::forMaintenance('test:bi-multi-project:fixture'));
    $db->beginTransaction();

    foreach ([
        [$projectA, $marker . '_A', 'rlsBiA' . $projectA],
        [$projectB, $marker . '_B', 'rlsBiB' . $projectB],
        [$projectC, $marker . '_C', 'rlsBiC' . $projectC],
    ] as [$projectId, $name, $prefix]) {
        $db->query(
            "INSERT INTO general_proyectos_procesos
                (ID, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso)
             VALUES (?, ?, ?, 'Construccion', 1, 1)",
            [$projectId, $name, $prefix],
        );
    }

    foreach ([
        [$projectA, 11, $marker . '_A'],
        [$projectB, 13, $marker . '_B'],
        [$projectC, 97, $marker . '_C'],
    ] as [$projectId, $consecutivo, $detail]) {
        $db->query(
            'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)',
            [$projectId, $week, $consecutivo, 'comprometer', $detail],
        );
    }

    $context->clear();
    $multiProjectScope = new MultiProjectScope(
        [$projectB, $projectA],
        'test.bi.fixture',
        'V',
        'test:bi-multi-project',
    );

    $visibleWithoutProjectFilter = $db->queryForProjects(
        $multiProjectScope,
        'SELECT detalle FROM auto_program_log WHERE detalle LIKE ? ORDER BY detalle',
        [$marker . '%'],
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($visibleWithoutProjectFilter !== [$marker . '_A', $marker . '_B']) {
        $failures[] = 'queryForProjects without project_id returned ' . json_encode($visibleWithoutProjectFilter);
    }

    $visibleFromHostileList = $db->queryForProjects(
        $multiProjectScope,
        'SELECT detalle FROM auto_program_log WHERE project_id IN (?, ?, ?) AND detalle LIKE ? ORDER BY detalle',
        [$projectA, $projectB, $projectC, $marker . '%'],
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($visibleFromHostileList !== [$marker . '_A', $marker . '_B']) {
        $failures[] = 'queryForProjects trusted hostile project list containing C';
    }

    try {
        $db->queryForProjects(
            $multiProjectScope,
            'SELECT Proyecto_Proceso FROM general_proyectos_procesos WHERE ID IN (?, ?)',
            [$projectA, $projectB],
        );
        $failures[] = 'queryForProjects accepted an identity-only query';
    } catch (ProjectScopeViolation) {
        // Expected: BI data access must be anchored by a Project root.
    }

    $projectIdentityRows = $db->queryForProjects(
        $multiProjectScope,
        'SELECT l.detalle, p.Proyecto_Proceso FROM auto_program_log l INNER JOIN general_proyectos_procesos p ON p.ID = l.project_id WHERE l.detalle LIKE ? ORDER BY l.detalle',
        [$marker . '%'],
    )->fetchAll(PDO::FETCH_ASSOC);
    if (array_column($projectIdentityRows, 'detalle') !== [$marker . '_A', $marker . '_B']) {
        $failures[] = 'queryForProjects rejected or leaked through a Project-anchored Identity join';
    }

    $metricScope = new MetricScope($multiProjectScope, week: (string) $week);
    $metricExecutor = new MetricExecutor(
        $db,
        new class extends MetricDictionaryService {
            public function getDefinition(string $metricKey): array
            {
                return $metricKey === 'test_bi_multi_scope'
                    ? [
                        'metric_key' => 'test_bi_multi_scope',
                        'execution_source' => 'auto_program_log',
                        'filters' => ["accion = 'comprometer'"],
                        'aggregation_policy' => 'ratio:consecutivo',
                    ]
                    : [];
            }
        },
    );
    $metric = $metricExecutor->execute('test_bi_multi_scope', $metricScope);
    if ($metric->basis()['filas_usadas'] !== 2 || $metric->basis()['obras_incluidas'] !== 2) {
        $failures[] = 'MetricScope did not preserve the A/B authority set';
    }
    if ($metric->value() === null || abs($metric->value() - 12.0) > 0.0001) {
        $failures[] = 'MetricExecutor aggregated project C outside its MultiProjectScope';
    }
} catch (Throwable $error) {
    $failures[] = $error::class . ': ' . $error->getMessage();
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
        $rolledBack = true;
    }
    $context->clear();
}

if (!$rolledBack) {
    $failures[] = 'BI A/B/C fixture transaction did not reach rollback in finally';
}

try {
    $context->bind(SystemScope::forMaintenance('test:bi-multi-project:cleanup-check'));
    $remaining = (int) $db->query(
        'SELECT COUNT(*) FROM auto_program_log WHERE detalle LIKE ?',
        [$marker . '%'],
    )->fetchColumn();
    if ($remaining !== 0) {
        $failures[] = "rollback left {$remaining} BI fixture rows";
    }
} catch (Throwable $error) {
    $failures[] = 'cleanup verification failed: ' . $error->getMessage();
} finally {
    $context->clear();
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI MultiProjectScope isolates A/B from C and rolls back exact fixtures\n";
