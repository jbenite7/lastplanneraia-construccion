<?php
// @requiere: puro


require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\LpsWeekEditPolicy;
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

// --- Composicion de las dos reglas (LpsWeekEditPolicy::decide) ---
// Aqui nace el comportamiento observable: en una semana historica ya confirmada,
// R/DCV pueden calificar avance aunque canEditLpsWeek() los deniegue.
// maxWeek = 6, semana 3 => historica (3 <= 6 - 2).

$composicion = [
    // [rol, semana, maxWeek, qualification, confirmada, esperado, glosa]
    ['R',   3, 6, true,  true,  true,  'R califica semana historica confirmada'],
    ['R',   3, 6, true,  false, false, 'R no califica semana historica sin confirmar'],
    ['DCV', 3, 6, true,  true,  true,  'DCV califica semana historica confirmada'],
    ['V',   3, 6, true,  true,  false, 'V nunca califica'],
    ['C',   3, 6, true,  true,  false, 'C nunca califica'],
    ['A',   3, 6, true,  true,  true,  'A edita por canEditLpsWeek'],
    ['A',   3, 6, true,  false, true,  'A edita aunque no este confirmada'],
    ['A',   3, 6, false, false, true,  'A edita sin calificacion'],
    ['R',   3, 6, false, true,  false, 'R denegado sin qualification pese a confirmada'],
    ['DCV', 3, 6, false, true,  false, 'DCV denegado sin qualification pese a confirmada'],
    ['R',   6, 6, false, false, true,  'R edita la ventana vigente sin excepcion'],
];

foreach ($composicion as [$role, $week, $maxWeek, $qualification, $confirmed, $expected, $glosa]) {
    $actual = LpsWeekEditPolicy::decide(
        $role,
        $week,
        $maxWeek,
        $qualification,
        static fn (): bool => $confirmed,
    );
    if ($actual !== $expected) {
        throw new RuntimeException("Composicion inesperada: {$glosa} ({$role}, semana {$week}/{$maxWeek})");
    }
}

// La consulta de confirmacion es perezosa: no debe ejecutarse cuando la edicion
// normal ya autorizo ni cuando el rol no puede calificar.
foreach ([['A', true], ['V', true], ['R', false]] as [$role, $qualification]) {
    $consultada = false;
    LpsWeekEditPolicy::decide($role, 3, 6, $qualification, static function () use (&$consultada): bool {
        $consultada = true;
        return true;
    });
    if ($consultada) {
        throw new RuntimeException("No debe consultarse Semanal_Confirmada para {$role} (qualification=" . var_export($qualification, true) . ')');
    }
}

echo "LPS Week Edit Policy: OK\n";
