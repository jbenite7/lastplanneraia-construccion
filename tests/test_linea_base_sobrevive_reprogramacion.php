<?php
// @requiere: datos-proyecto
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;
use App\Services\LineaBaseContractualService;

$fallos = [];
$db = \Database::getInstance();
$svc = new LineaBaseContractualService();
$ct = new ControlTowerService();

// Base compartida: se guarda y se restaura (ver restricciones globales).
$original = $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC) ?: ['inicio' => null, 'fin' => null];
register_shutdown_function(static function () use ($db, $original): void {
    $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    );
});

// Proyecto 68: sus actividades de la primera semana y la última NO se solapan.
// Con línea base declarada, la fecha contractual tiene que sobrevivir igual.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2026-06-01', fechaFinLineaBase = '2026-07-19'
            WHERE Id = 68");

$brief = $ct->getBrief('programa-general', [68], '5', 'R', []);
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
$sub = $db->query("SELECT sub_contratista FROM bi_pg_semana
                   WHERE project_id = 68 AND COALESCE(sub_contratista,'') <> '' LIMIT 1")->fetchColumn();
if ($sub !== false && $sub !== null) {
    $filtrado = $ct->getBrief('programa-general', [68], '5', 'R', ['sub_contratista' => [$sub]]);
    $mf = $filtrado['charts']['programa-dias-retraso']['metrics'] ?? [];
    if (($mf['contractual_finish'] ?? '') !== '2026-07-19') {
        $fallos[] = 'filtrar por subcontratista movio la fecha contractual a: '
            . var_export($mf['contractual_finish'] ?? null, true);
    }
}

// Sin línea base declarada NO se inventa ninguna.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68");
$sinLb = $ct->getBrief('programa-general', [68], '5', 'R', []);
$ms = $sinLb['charts']['programa-dias-retraso']['metrics'] ?? [];
if (!empty($ms['contractual_finish'])) {
    $fallos[] = 'sin linea base declarada se invento una fecha: ' . $ms['contractual_finish'];
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: la linea base contractual sobrevive a la reprogramacion y al filtro\n";
