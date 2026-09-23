<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Services/RestrictionConfigResolver.php';

use App\Services\RestrictionConfigResolver;

function assertStrictEqual($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $msg = $message !== '' ? $message . ' ' : '';
        throw new RuntimeException($msg . 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

echo "=== Test Programa General Restriction Contract ===\n";

// 1. Construcción
$construction = RestrictionConfigResolver::presentationConfig('Construccion');
assertStrictEqual('Construccion', $construction['area'], 'Construccion area');
assertStrictEqual(
    ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'],
    array_column($construction['restrictions'], 'key'),
    'Construccion keys'
);
assertStrictEqual(['0%', '50%', '100%', 'N/A'], $construction['restrictions'][4]['options'], 'Predecesora options');
assertStrictEqual(['0%', '100%', 'N/A'], $construction['restrictions'][0]['options'], 'D_y_E options');
assertStrictEqual(100, $construction['restrictions'][0]['thresholdPercent'], 'D_y_E threshold');
assertStrictEqual(50, $construction['restrictions'][4]['thresholdPercent'], 'Predecesora threshold');
assertStrictEqual(['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'], $construction['hardRestrictions'], 'Hard restrictions');
assertStrictEqual(['Pdto_Cons', 'Modelo'], $construction['softRestrictions'], 'Soft restrictions');

// 2. Pre-Construcción con labels personalizadas
$pre = RestrictionConfigResolver::presentationConfig('Pre-Construccion', [
    'restriccion_pc_2' => 'Licencia',
    'restriccion_pc_3' => '',
    'restriccion_pc_4' => 'Cliente',
]);
assertStrictEqual('Pre-Construccion', $pre['area'], 'Pre-Construccion area');
assertStrictEqual(
    ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_4'],
    array_column($pre['restrictions'], 'key'),
    'Pre-Construccion filtered keys'
);
assertStrictEqual(['0%', '50%', '100%', 'N/A'], $pre['restrictions'][0]['options'], 'PC1 options');
assertStrictEqual(50, $pre['restrictions'][0]['thresholdPercent'], 'PC1 threshold');
assertStrictEqual(['restriccion_pc_1'], $pre['hardRestrictions'], 'PC hard restrictions');
assertStrictEqual(['restriccion_pc_2', 'restriccion_pc_4'], $pre['softRestrictions'], 'PC soft restrictions');

echo "PASS: Presentation config contract verified for both areas.\n";
