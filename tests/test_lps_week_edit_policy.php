<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;

$cases = [
    ['A', 1, 6, true],
    ['D', 4, 6, true],
    ['R', 5, 6, true],
    ['R', 4, 6, false],
    ['DCV', 6, 6, true],
    ['DCV', 4, 6, false],
    ['G', 6, 6, false],
    ['R', 0, 6, false],
];

foreach ($cases as [$role, $week, $maxWeek, $expected]) {
    $actual = RbacCatalog::canEditLpsWeek($role, $week, $maxWeek);
    if ($actual !== $expected) {
        throw new RuntimeException("Política inesperada para {$role} semana {$week}/{$maxWeek}");
    }
}

foreach (['A', 'D', 'R', 'DCV'] as $role) {
    if (!RbacCatalog::canQualifyWeeklyCommitment($role)) {
        throw new RuntimeException("{$role} debe poder calificar compromisos");
    }
}
foreach (['OT', 'G', 'S', 'SG', 'C', 'V'] as $role) {
    if (RbacCatalog::canQualifyWeeklyCommitment($role)) {
        throw new RuntimeException("{$role} no debe calificar compromisos");
    }
}

echo "LPS Week Edit Policy: OK\n";
