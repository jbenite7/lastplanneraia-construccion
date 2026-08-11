<?php
// @requiere: puro


if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Programacion\ProgramacionIntermediaController;

$reflection = new ReflectionClass(ProgramacionIntermediaController::class);
$method = $reflection->getMethod('resolveSharedConstraintPayload');
$method->setAccessible(true);
$controller = $reflection->newInstanceWithoutConstructor();

function runTest(ReflectionMethod $method, $controller, array $post, string $label): void
{
    $_POST = $post;
    $result = $method->invoke($controller, true);
    echo "Test: {$label}\n";
    echo "  ok: " . var_export($result['ok'], true) . "\n";
    if (!$result['ok']) {
        echo "  mensaje: " . ($result['mensaje'] ?? 'N/A') . "\n";
    } else {
        echo "  applyRestriction: " . var_export($result['applyRestriction'], true) . "\n";
        echo "  applyAssignments: " . var_export($result['applyAssignments'], true) . "\n";
        echo "  subContratista: '" . ($result['subContratista'] ?? '') . "'\n";
        echo "  responsableAia: '" . ($result['responsableAia'] ?? '') . "'\n";
    }
    echo "\n";
}

// Test 1: S3 - solo restricción, sin asignaciones (debe pasar)
runTest($method, $controller, [
    'db' => 'prueba',
    'semana' => '1',
    'apply_restriction' => '1',
    'apply_assignments' => '0',
    'restriction_type' => 'D_y_E',
    'target_value' => '1',
    'restrictions' => json_encode([['type' => 'D_y_E', 'value' => '1']]),
    'activity_ids' => ['1','2','3'],
    'sub_contratista' => '',
    'responsable_aia' => '',
    'note' => '',
], 'S3: solo restricción, sin asignaciones (debe OK)');

// Test 2: applyAssignments=true sin valores (debe fallar)
runTest($method, $controller, [
    'db' => 'prueba',
    'semana' => '1',
    'apply_restriction' => '1',
    'apply_assignments' => '1',
    'restriction_type' => 'D_y_E',
    'target_value' => '1',
    'restrictions' => json_encode([['type' => 'D_y_E', 'value' => '1']]),
    'activity_ids' => ['1','2','3'],
    'sub_contratista' => '',
    'responsable_aia' => '',
    'note' => '',
], 'applyAssignments=true sin sub ni resp (debe FALLAR)');

// Test 3: applyAssignments=true con sub (debe pasar)
runTest($method, $controller, [
    'db' => 'prueba',
    'semana' => '1',
    'apply_restriction' => '0',
    'apply_assignments' => '1',
    'restriction_type' => '',
    'target_value' => '',
    'restrictions' => '[]',
    'activity_ids' => ['1','2','3'],
    'sub_contratista' => 'SubA',
    'responsable_aia' => '',
    'note' => '',
], 'applyAssignments=true con sub (debe OK, sub vacío en respAia)');

// Test 4: applyAssignments=true con resp (debe pasar)
runTest($method, $controller, [
    'db' => 'prueba',
    'semana' => '1',
    'apply_restriction' => '0',
    'apply_assignments' => '1',
    'restriction_type' => '',
    'target_value' => '',
    'restrictions' => '[]',
    'activity_ids' => ['1','2','3'],
    'sub_contratista' => '',
    'responsable_aia' => 'RespX',
    'note' => '',
], 'applyAssignments=true con resp (debe OK)');
