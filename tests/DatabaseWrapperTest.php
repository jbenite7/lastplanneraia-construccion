<?php
// @requiere: db

declare(strict_types=1);

use App\Security\DataScope\MissingProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\ProjectScopeViolation;

require_once __DIR__ . '/../vendor/autoload.php';

$db = Database::getInstance();
$scope = $db->dataScope();
$passed = 0;
$failures = [];

/** @param callable(): void $assertion */
function wrapperCheck(string $name, callable $assertion): void
{
    global $passed, $failures;
    try {
        $assertion();
        $passed++;
        echo "PASS: {$name}\n";
    } catch (Throwable $error) {
        $failures[] = $name . ': ' . $error::class . ': ' . $error->getMessage();
        echo "FAIL: {$name}\n";
    }
}

try {
    $scope->clear();

    wrapperCheck('identity-only queryWithProject remains legal without project scope', static function () use ($db): void {
        $count = (int) $db->queryWithProject(
            'SELECT COUNT(*) FROM general_usuarios WHERE Usuario = ?',
            ['test.A'],
        )->fetchColumn();
        if ($count < 1) {
            throw new RuntimeException('No encontró la identidad sembrada test.A.');
        }
    });

    wrapperCheck('project queryWithProject fails closed without project scope', static function () use ($db): void {
        try {
            $db->queryWithProject('SELECT COUNT(*) FROM auto_program_log');
        } catch (MissingProjectScope) {
            return;
        }
        throw new RuntimeException('La consulta project-scoped no lanzó MissingProjectScope.');
    });

    wrapperCheck('identity override cannot create project authority', static function () use ($db): void {
        try {
            $db->queryWithProject('SELECT COUNT(*) FROM general_usuarios', [], 73);
        } catch (MissingProjectScope) {
            return;
        }
        throw new RuntimeException('El override sin ProjectScope fue aceptado.');
    });

    $scope->bind(new ProjectScope(73, 'test.A', 'A'));

    wrapperCheck('matching scope reaches the single Database preflight', static function () use ($db): void {
        $count = (int) $db->queryWithProject(
            'SELECT COUNT(*) FROM auto_program_log WHERE detalle LIKE ?',
            ['WRAPPER_TEST_NO_MATCH_%'],
            73,
        )->fetchColumn();
        if ($count !== 0) {
            throw new RuntimeException("Esperaba cero filas, obtuvo {$count}.");
        }
    });

    wrapperCheck('contradictory override is rejected before PDO', static function () use ($db): void {
        try {
            $db->queryWithProject('SELECT COUNT(*) FROM auto_program_log', [], 27);
        } catch (ProjectScopeViolation) {
            return;
        }
        throw new RuntimeException('El override 27 fue aceptado bajo ProjectScope(73).');
    });
} finally {
    $scope->clear();
}

if ($failures !== []) {
    echo "=== Database Wrapper: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "=== Database Wrapper: OK ({$passed} checks) ===\n";
