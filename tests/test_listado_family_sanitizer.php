<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Support\ListadoFamilySanitizer;

$sanitizer = new ListadoFamilySanitizer();

$cases = [
    [
        'candidate' => 'Morteros de Nivelacion de Losas - Nivel 5',
        'family' => 'Morteros de Nivelacion de Losas',
        'expected' => true,
    ],
    [
        'candidate' => 'Carpinteria Metalica - Zona Torre 3',
        'family' => 'Carpinteria Metalica',
        'expected' => true,
    ],
    [
        'candidate' => 'Red de Extinción - Staff',
        'family' => 'Red de Extinción',
        'expected' => false,
    ],
    [
        'candidate' => 'Red de Extinción - Suministro',
        'family' => 'Red de Extinción',
        'expected' => false,
    ],
];

foreach ($cases as $case) {
    $actual = $sanitizer->isFamilyWithOnlyContextSuffix($case['candidate'], $case['family']);
    if ($actual !== $case['expected']) {
        echo 'FAIL: ' . $case['candidate'] . ' expected ' . ($case['expected'] ? 'true' : 'false') . "\n";
        exit(1);
    }
}

$hint = $sanitizer->contextHint('Piso en mortero afinado Nivel 5, [Capítulo: Piso en mortero afinado]');
if ($hint !== 'Nivel 5') {
    echo "FAIL: expected Nivel 5, got {$hint}\n";
    exit(1);
}

$normalized = $sanitizer->normalizeFamilyLabel(
    'Morteros de Nivelacion de Losas - Nivel 5',
    'Morteros de Nivelacion de Losas',
);
if ($normalized !== 'Morteros de Nivelacion de Losas') {
    echo "FAIL: expected normalized family, got {$normalized}\n";
    exit(1);
}

echo "PASS: listado family sanitizer separates context from family names\n";
