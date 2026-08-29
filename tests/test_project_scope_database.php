<?php
// @requiere: db

declare(strict_types=1);

use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\ProjectScopeViolation;
use App\Security\DataScope\SystemScope;

require_once __DIR__ . '/../vendor/autoload.php';

$db = Database::getInstance();
$scope = $db->dataScope();
$marker = 'RLS_TEST_' . bin2hex(random_bytes(6));
$detailA = $marker . '_A';
$detailB = $marker . '_B';
$detailTouched = $marker . '_TOUCHED';
$failures = [];
$rolledBack = false;

try {
    $scope->clear();
    $scope->bind(SystemScope::forMaintenance('RLS test fixture setup'));
    $db->beginTransaction();

    // RLS_TEST fixture write: transactional and always rolled back in finally.
    $db->query(
        'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)',
        [73, 9998, 910001, 'comprometer', $detailA],
    );
    $db->query(
        'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)',
        [27, 9998, 910002, 'comprometer', $detailB],
    );

    $scope->clear();
    $scope->bind(new ProjectScope(73, 'test.A', 'A'));

    $visible = $db->query(
        "SELECT detalle FROM auto_program_log WHERE detalle LIKE ? ORDER BY detalle",
        [$marker . '%'],
    )->fetchAll(PDO::FETCH_COLUMN);
    if ($visible !== [$detailA]) {
        $failures[] = 'ProjectScope(73) vio ' . json_encode($visible) . ' en vez de solo la fila A.';
    }

    $prepared = $db->prepare(
        'SELECT detalle FROM auto_program_log WHERE detalle LIKE ? ORDER BY detalle',
    );
    if (!$prepared instanceof DatabasePreparedStatement) {
        $failures[] = 'prepare() no difirió la tabla de proyecto hasta recibir parámetros en execute().';
    } else {
        $prepared->execute([$marker . '%']);
        $preparedVisible = $prepared->fetchAll(PDO::FETCH_COLUMN);
        if ($preparedVisible !== [$detailA]) {
            $failures[] = 'prepare()->execute() no conservó el aislamiento A/B.';
        }
    }

    try {
        $db->queryWithProject(
            'SELECT detalle FROM auto_program_log WHERE detalle LIKE ?',
            [$marker . '%'],
            27,
        );
        $failures[] = 'queryWithProject aceptó override 27 bajo ProjectScope(73).';
    } catch (ProjectScopeViolation) {
        // Expected: compatibility override cannot create authority.
    }

    try {
        $db->query(
            'UPDATE auto_program_log SET detalle = ? WHERE project_id = ? AND detalle = ?',
            [$detailTouched, 27, $detailB],
        );
        $failures[] = 'El UPDATE contradictorio alcanzó PDO sin ProjectScopeViolation.';
    } catch (ProjectScopeViolation) {
        // Expected: contradiction rejected by the SQL preflight.
    }

    $scope->clear();
    $scope->bind(SystemScope::forMaintenance('test verification'));
    $storedB = $db->query(
        'SELECT detalle FROM auto_program_log WHERE project_id = ? AND detalle = ?',
        [27, $detailB],
    )->fetchColumn();
    if ($storedB !== $detailB) {
        $failures[] = 'La fila B cambió pese al rechazo del UPDATE contradictorio.';
    }
} catch (Throwable $error) {
    $failures[] = $error::class . ': ' . $error->getMessage();
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
        $rolledBack = true;
    }
    $scope->clear();
}

if (!$rolledBack) {
    $failures[] = 'La transacción de fixtures no llegó al rollback de finally.';
}

try {
    $scope->bind(SystemScope::forMaintenance('RLS test cleanup verification'));
    $remaining = (int) $db->query(
        'SELECT COUNT(*) FROM auto_program_log WHERE detalle LIKE ?',
        [$marker . '%'],
    )->fetchColumn();
    if ($remaining !== 0) {
        $failures[] = "Rollback incompleto: quedaron {$remaining} fixtures {$marker}.";
    }
} catch (Throwable $error) {
    $failures[] = 'No se pudo verificar el cleanup: ' . $error->getMessage();
} finally {
    $scope->clear();
}

if ($failures !== []) {
    echo "=== Project Scope Database: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "=== Project Scope Database: OK ===\n";
echo "A/B aislado; contradicción rechazada antes de PDO; fixture {$marker} revertido y cleanup=0.\n";
