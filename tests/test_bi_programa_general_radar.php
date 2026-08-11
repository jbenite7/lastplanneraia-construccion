<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$bi = new ControlTowerService();
$method = new ReflectionMethod($bi, 'programaRadar');
$sourceMethod = new ReflectionMethod($bi, 'dataSourceForReport');
$failures = [];

function radarRow(array $values = []): array
{
    return array_merge([
        'project_id' => 1,
        'Semana' => 7,
        'row_id' => 1,
        'Actividad' => 'Actividad de prueba',
        'Unidad' => 'm2',
        'Compromiso' => null,
        'Ejecutado_Real' => null,
        'P_Completado' => null,
        'PAC' => null,
        'Activa' => '1',
        'Es_TNP' => 0,
    ], $values);
}

function radarAxis(array $radar, string $key): array
{
    return $radar['axes'][$key] ?? [];
}

function radarValue(array $axis, string $key): mixed
{
    return array_key_exists($key, $axis) ? $axis[$key] : 'missing';
}

function radarAssertClose(array &$failures, string $label, mixed $actual, ?float $expected): void
{
    if ($expected === null) {
        if ($actual !== null) {
            $failures[] = "{$label}: expected null, got " . json_encode($actual);
        }
        return;
    }

    if (!is_numeric($actual) || abs((float) $actual - $expected) > 0.05) {
        $failures[] = "{$label}: expected {$expected}, got " . json_encode($actual);
    }
}

$empty = $method->invoke($bi, []);
$programaSource = $sourceMethod->invoke($bi, 'programa-general');
if (!in_array('programacion_semanal', $programaSource['source_relations'] ?? [], true)) {
    $failures[] = 'programa-general lineage must declare programacion_semanal as the Radar source';
}
foreach (['productividad', 'eficiencia', 'desempeno'] as $key) {
    $axis = radarAxis($empty, $key);
    if (($axis['available'] ?? true) !== false || ($axis['sample_size'] ?? -1) !== 0 || ($axis['min_sample'] ?? 0) !== 3) {
        $failures[] = "{$key}: no-data axis must be unavailable with minimum sample 3";
    }
    radarAssertClose($failures, "{$key}: no-data raw", radarValue($axis, 'raw_value'), null);
    radarAssertClose($failures, "{$key}: no-data display", radarValue($axis, 'display_value'), null);
}

$onlyProductivity = $method->invoke($bi, [
    radarRow(['P_Completado' => 0]),
    radarRow(['row_id' => 2, 'P_Completado' => 0.5]),
    radarRow(['row_id' => 3, 'P_Completado' => 1]),
]);
$productivity = radarAxis($onlyProductivity, 'productividad');
if (($productivity['available'] ?? false) !== true || ($productivity['sample_size'] ?? 0) !== 3) {
    $failures[] = 'productividad: three valid values, including zero, must be available';
}
radarAssertClose($failures, 'productividad: raw', $productivity['raw_value'] ?? null, 50.0);
radarAssertClose($failures, 'productividad: display', $productivity['display_value'] ?? null, 50.0);
foreach (['eficiencia', 'desempeno'] as $key) {
    if ((radarAxis($onlyProductivity, $key)['available'] ?? true) !== false) {
        $failures[] = "{$key}: an independent axis must not borrow productividad population";
    }
}

$overachievedProductivity = $method->invoke($bi, [
    radarRow(['P_Completado' => 1.4]),
    radarRow(['row_id' => 2, 'P_Completado' => 0.6]),
    radarRow(['row_id' => 3, 'P_Completado' => 0]),
]);
$overachievedAxis = radarAxis($overachievedProductivity, 'productividad');
if (($overachievedAxis['sample_size'] ?? 0) !== 3) {
    $failures[] = 'productividad: overachievement must remain in the valid population';
}
radarAssertClose($failures, 'productividad: overachievement is capped per row', $overachievedAxis['raw_value'] ?? null, 53.3);

$rows = [
    radarRow(['project_id' => 10, 'row_id' => 1, 'P_Completado' => 0, 'Compromiso' => 10, 'Ejecutado_Real' => 20, 'PAC' => 1]),
    radarRow(['project_id' => 10, 'row_id' => 2, 'P_Completado' => 0.5, 'Compromiso' => 2, 'Ejecutado_Real' => 4, 'PAC' => 0, 'Unidad' => 'kg']),
    radarRow(['project_id' => 20, 'row_id' => 3, 'P_Completado' => 1, 'Compromiso' => 4, 'Ejecutado_Real' => 2, 'PAC' => 1, 'Unidad' => 'h']),
    radarRow(['project_id' => 20, 'row_id' => 4, 'P_Completado' => null, 'Compromiso' => 0, 'Ejecutado_Real' => 5, 'PAC' => null]),
    radarRow(['project_id' => 20, 'row_id' => 5, 'P_Completado' => -1, 'Compromiso' => 3, 'Ejecutado_Real' => -1, 'PAC' => 2]),
    radarRow(['project_id' => 20, 'row_id' => 6, 'P_Completado' => 1, 'Compromiso' => 1, 'Ejecutado_Real' => 10, 'PAC' => 1, 'Es_TNP' => 1]),
];
$radar = $method->invoke($bi, $rows);

$expected = [
    'productividad' => ['numerator' => 1.5, 'denominator' => 3.0, 'raw' => 50.0, 'display' => 50.0],
    'eficiencia' => ['numerator' => 4.5, 'denominator' => 3.0, 'raw' => 150.0, 'display' => 100.0],
    'desempeno' => ['numerator' => 2.0, 'denominator' => 3.0, 'raw' => 66.7, 'display' => 66.7],
];
foreach ($expected as $key => $values) {
    $axis = radarAxis($radar, $key);
    if (($axis['available'] ?? false) !== true || ($axis['sample_size'] ?? 0) !== 3) {
        $failures[] = "{$key}: valid population must contain exactly three non-TNP rows";
    }
    foreach (['numerator', 'denominator'] as $field) {
        radarAssertClose($failures, "{$key}: {$field}", $axis[$field] ?? null, $values[$field]);
    }
    radarAssertClose($failures, "{$key}: raw", $axis['raw_value'] ?? null, $values['raw']);
    radarAssertClose($failures, "{$key}: display", $axis['display_value'] ?? null, $values['display']);
    foreach (['name', 'label', 'source', 'formula', 'warning', 'status', 'project_breakdown'] as $field) {
        if (!array_key_exists($field, $axis)) {
            $failures[] = "{$key}: traceability field {$field} is missing";
        }
    }
}
if (($radar['axes']['eficiencia']['over_target'] ?? false) !== true) {
    $failures[] = 'eficiencia: raw value above 100 must be marked over-target while display is clamped';
}
if (($radar['display_values'] ?? []) !== [50.0, 100.0, 66.7]) {
    $failures[] = 'radar display values must come from independent axes';
}

$withInactive = $method->invoke($bi, array_merge($rows, [
    radarRow(['project_id' => 30, 'row_id' => 7, 'Activa' => '0', 'P_Completado' => 1, 'Compromiso' => 1, 'Ejecutado_Real' => 1, 'PAC' => 1]),
]));
foreach ($expected as $key => $values) {
    radarAssertClose($failures, "{$key}: inactive rows do not alter numerator", radarAxis($withInactive, $key)['numerator'] ?? null, $values['numerator']);
    radarAssertClose($failures, "{$key}: inactive rows do not alter denominator", radarAxis($withInactive, $key)['denominator'] ?? null, $values['denominator']);
}

$insufficient = $method->invoke($bi, [
    radarRow(['P_Completado' => 0]),
    radarRow(['row_id' => 2, 'P_Completado' => 1]),
]);
$axis = radarAxis($insufficient, 'productividad');
if (($axis['available'] ?? true) !== false || ($axis['sample_size'] ?? 0) !== 2) {
    $failures[] = 'productividad: fewer than three valid values must remain unavailable';
}
radarAssertClose($failures, 'productividad: insufficient raw', radarValue($axis, 'raw_value'), null);

$multiProject = $method->invoke($bi, [
    radarRow(['project_id' => 1, 'row_id' => 1, 'P_Completado' => 1]),
    radarRow(['project_id' => 1, 'row_id' => 2, 'P_Completado' => 1]),
    radarRow(['project_id' => 1, 'row_id' => 3, 'P_Completado' => 1]),
    radarRow(['project_id' => 2, 'row_id' => 4, 'P_Completado' => 0]),
    radarRow(['project_id' => 2, 'row_id' => 5, 'P_Completado' => 0]),
    radarRow(['project_id' => 2, 'row_id' => 6, 'P_Completado' => 0]),
]);
radarAssertClose($failures, 'multiproyecto: global productividad', radarAxis($multiProject, 'productividad')['raw_value'] ?? null, 50.0);
$breakdown = radarAxis($multiProject, 'productividad')['project_breakdown'] ?? [];
if (count($breakdown) !== 2) {
    $failures[] = 'multiproyecto: productividad must retain a reconstructable project breakdown';
} else {
    radarAssertClose($failures, 'multiproyecto: project 1 productividad', $breakdown[0]['raw_value'] ?? null, 100.0);
    radarAssertClose($failures, 'multiproyecto: project 2 productividad', $breakdown[1]['raw_value'] ?? null, 0.0);
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: Radar uses independent valid populations, minimum samples, zero values and global denominators\n";
echo "PASS: Radar avoids mixed-unit sums and exposes raw/display traceability\n";
