<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\LineaBaseContractualService;

$fallos = [];
$svc = new LineaBaseContractualService();
$db = \Database::getInstance();

// La base de desarrollo es COMPARTIDA con otras sesiones. Se guarda el estado del proyecto 68 y se
// restaura pase lo que pase — incluso si la prueba muere a mitad.
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

// 1. Un proyecto con línea base declarada la devuelve tal cual.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2020-01-01', fechaFinLineaBase = '2020-12-31'
            WHERE Id = 68");
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01' || ($lb['fin'] ?? null) !== '2020-12-31') {
    $fallos[] = 'declaradaDe no devuelve las fechas declaradas';
}

// 2. sembrarSiFalta NO sobrescribe una línea base existente.
if ($svc->sembrarSiFalta(68) !== false) {
    $fallos[] = 'sembrarSiFalta sobrescribió una línea base ya declarada';
}
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01') {
    $fallos[] = 'sembrarSiFalta pisó la fecha declarada';
}

// 3. Sin línea base declarada, declaradaDe devuelve null y sembrarSiFalta escribe.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68");
if ($svc->declaradaDe(68) !== null) {
    $fallos[] = 'declaradaDe debería devolver null sin fechas declaradas';
}
$deducida = $svc->deducidaDelPrimerCorte(68);
if ($deducida === null) {
    $fallos[] = 'deducidaDelPrimerCorte no encontró el primer corte del proyecto 68';
}
if ($svc->sembrarSiFalta(68) !== true) {
    $fallos[] = 'sembrarSiFalta no escribió cuando faltaba la línea base';
}
if ($svc->declaradaDe(68) != $deducida) {
    $fallos[] = 'lo sembrado no coincide con lo deducido del primer corte';
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: linea base contractual — declarada, deducida y sembrado write-once\n";
