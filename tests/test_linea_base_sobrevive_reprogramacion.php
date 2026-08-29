<?php
// @requiere: datos-proyecto
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;
use App\Services\LineaBaseContractualService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

$fallos = [];
$db = \Database::getInstance();
$svc = new LineaBaseContractualService();
$ct = new ControlTowerService();
$scope = static fn(array $ids, string $case): MultiProjectScope => new MultiProjectScope(
    $ids,
    'fixture-linea-base',
    'R',
    'test:test_linea_base_sobrevive_reprogramacion:' . $case,
);
$systemWrite = static fn(string $case, callable $operation): mixed => (new SystemScopeRunner($db->dataScope()))->run(
    'test:test_linea_base_sobrevive_reprogramacion:fixture:' . $case,
    $operation,
);
$projectRead = static function (int $projectId, callable $read) use ($db): mixed {
    $db->dataScope()->bind(new ProjectScope($projectId, 'fixture-linea-base', 'R'));
    try {
        return $read();
    } finally {
        $db->dataScope()->clear();
    }
};

// Base compartida: se guarda y se restaura (ver restricciones globales).
$original = $systemWrite('snapshot', static fn() => $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC)) ?: ['inicio' => null, 'fin' => null];
$restored = false;
$restore = static function () use ($systemWrite, $db, $original, &$restored): void {
    if ($restored) {
        return;
    }
    $systemWrite('cleanup', static fn() => $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    ));
    $restored = true;
};
register_shutdown_function($restore);

// Proyecto 68: sus actividades de la primera semana y la última NO se solapan.
// Con línea base declarada, la fecha contractual tiene que sobrevivir igual.
try {
$systemWrite('seed-linea-base', static fn() => $db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2026-06-01', fechaFinLineaBase = '2026-07-19'
            WHERE Id = 68"));

$brief = $ct->getBrief($scope([68], 'linea-base'), 'programa-general', '5', 'R', []);
$m = $brief['charts']['programa-dias-retraso']['metrics'] ?? [];

if (($m['contractual_finish'] ?? '') !== '2026-07-19') {
    $fallos[] = 'contractual_finish deberia ser la declarada 2026-07-19, y es: '
        . var_export($m['contractual_finish'] ?? null, true);
}
if (($m['contractual_finish_basis'] ?? '') !== 'declared_project_baseline') {
    $fallos[] = 'contractual_finish_basis deberia declarar la fuente nueva, y dice: '
        . var_export($m['contractual_finish_basis'] ?? null, true);
}

// Filtrar por subcontratista NO cambia la fecha contractual.
$sub = $projectRead(68, static fn() => $db->query(
    "SELECT sub_contratista FROM bi_pg_semana
     WHERE project_id = ? AND COALESCE(sub_contratista,'') <> '' LIMIT 1",
    [68],
)->fetchColumn());
if ($sub !== false && $sub !== null) {
    $filtrado = $ct->getBrief($scope([68], 'filtrado'), 'programa-general', '5', 'R', ['sub_contratista' => [$sub]]);
    $mf = $filtrado['charts']['programa-dias-retraso']['metrics'] ?? [];
    if (($mf['contractual_finish'] ?? '') !== '2026-07-19') {
        $fallos[] = 'filtrar por subcontratista movio la fecha contractual a: '
            . var_export($mf['contractual_finish'] ?? null, true);
    }
}

// Sin línea base declarada NO se inventa ninguna.
$systemWrite('seed-sin-linea-base', static fn() => $db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68"));
$sinLb = $ct->getBrief($scope([68], 'sin-linea-base'), 'programa-general', '5', 'R', []);
$ms = $sinLb['charts']['programa-dias-retraso']['metrics'] ?? [];
if (!empty($ms['contractual_finish'])) {
    $fallos[] = 'sin linea base declarada se invento una fecha: ' . $ms['contractual_finish'];
}
} finally {
    $restore();
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: la linea base contractual sobrevive a la reprogramacion y al filtro\n";
