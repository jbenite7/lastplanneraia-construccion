<?php

declare(strict_types=1);
// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Security/ProgramaGeneralActionPolicy.php';

use App\Security\ProgramaGeneralActionPolicy;

function assertStrictEqual($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $msg = $message !== '' ? $message . ' ' : '';
        throw new RuntimeException($msg . 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

echo "=== Test Programa General Action Policy ===\n";

$cases = [
    'reader' => [
        [false, false, 6, 6, false],
        [false, false, false, true, true, true],
    ],
    'current editor' => [
        [true, false, 6, 6, false],
        [true, true, true, true, true, true],
    ],
    'current confirmed' => [
        [true, false, 6, 6, true],
        [false, true, true, true, true, true],
    ],
    'past privileged' => [
        [true, true, 4, 6, false],
        [true, true, true, true, true, true],
    ],
    'past privileged confirmed' => [
        [true, true, 4, 6, true],
        [false, true, true, true, true, true],
    ],
    'past ordinary editor' => [
        [true, false, 5, 6, false],
        [false, false, false, true, true, true],
    ],
    'invalid future week' => [
        [true, true, 7, 6, false],
        [false, false, false, false, false, false],
    ],
    'invalid zero week' => [
        [true, true, 0, 6, false],
        [false, false, false, false, false, false],
    ],
];

foreach ($cases as $label => [$input, $expected]) {
    $actions = ProgramaGeneralActionPolicy::resolve(
        canEdit: $input[0],
        canEditPast: $input[1],
        week: $input[2],
        maxWeek: $input[3],
        confirmed: $input[4],
        canDownload: true,
        canReadDrawer: true,
        canWriteDrawer: true,
    );
    assertStrictEqual($expected[0], $actions['editPlanFields'], "$label: editPlanFields");
    assertStrictEqual($expected[1], $actions['editProgress'], "$label: editProgress");
    assertStrictEqual($expected[2], $actions['runBatch'], "$label: runBatch");
    assertStrictEqual($expected[3], $actions['downloadCut'], "$label: downloadCut");
    assertStrictEqual($expected[4], $actions['readDrawer'], "$label: readDrawer");
    assertStrictEqual($expected[5], $actions['writeDrawer'], "$label: writeDrawer");
}

echo "PASS: Action policy decision table verified completely.\n";
