<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario'] = 'qa-pdc-three-projects';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';

$failed = 0;
$targetDate = '2026-07-02';
$projects = [
    ['id' => 68, 'name' => 'Optimización Aeropuerto JMC', 'db' => 'optimizacionJMC'],
    ['id' => 73, 'name' => 'Da Porto', 'db' => 'da_porto'],
    ['id' => 74, 'name' => 'Milán Campestre Torre 19', 'db' => 'milan_campestre_torre'],
];

function pdc3Pass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function pdc3Fail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function pdc3PackageKey(string $type, string $name): string
{
    return mb_strtolower(trim($type) . '|' . trim($name), 'UTF-8');
}

function pdc3ContractPackages(Database $db, int $projectId, int $week): array
{
    $labels = [
        'SI' => 'Suministro e Instalación',
        'S' => 'Suministro',
        'MO' => 'Mano de Obra',
        'OC' => 'Orden de Compra',
    ];
    $rows = $db->query(
        "SELECT * FROM actividades
         WHERE project_id = ? AND semanaActualizacion = ?
           AND tipoContrato IS NOT NULL AND tipoContrato <> ''",
        [$projectId, $week],
    )->fetchAll(PDO::FETCH_ASSOC);

    $keys = [];
    foreach ($rows as $row) {
        foreach ($labels as $prefix => $label) {
            for ($i = 1; $i <= 5; $i++) {
                $name = trim((string) ($row["paquete{$prefix}{$i}"] ?? ''));
                if ($name !== '') {
                    $keys[pdc3PackageKey($label, $name)] = true;
                }
            }
        }
    }

    return $keys;
}

function pdc3PdcPackages(Database $db, int $projectId, int $week): array
{
    $rows = $db->query(
        "SELECT tipoPaquete, paqueteContratacion
         FROM pdc
         WHERE project_id = ? AND semana = ? AND titulo = 0",
        [$projectId, $week],
    )->fetchAll(PDO::FETCH_ASSOC);

    $keys = [];
    foreach ($rows as $row) {
        $key = pdc3PackageKey((string) $row['tipoPaquete'], (string) $row['paqueteContratacion']);
        $keys[$key] = ($keys[$key] ?? 0) + 1;
    }

    return $keys;
}

echo "=== PDC three mandatory projects perfect at {$targetDate} ===\n";

try {
    $db = Database::getInstance();
    $service = new SemiAutoService($db);

    foreach ($projects as $project) {
        $week = $db->query(
            "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem
             FROM semanas_activas
             WHERE project_id = ? AND ? BETWEEN Fecha_Inicio_Sem AND Fecha_Fin_Sem
             ORDER BY Semana DESC LIMIT 1",
            [$project['id'], $targetDate],
        )->fetch(PDO::FETCH_ASSOC);

        if (!$week) {
            pdc3Fail($project['name'] . ' no tiene semana activa que cubra ' . $targetDate);
            continue;
        }

        $weekNumber = (int) $week['Semana'];
        pdc3Pass($project['name'] . " usa semana activa {$weekNumber} para {$targetDate}");

        $contractPackages = pdc3ContractPackages($db, (int) $project['id'], $weekNumber);
        $pdcPackages = pdc3PdcPackages($db, (int) $project['id'], $weekNumber);
        $pdcPackageKeys = array_keys($pdcPackages);
        $missing = array_diff(array_keys($contractPackages), $pdcPackageKeys);
        $extra = array_diff($pdcPackageKeys, array_keys($contractPackages));
        $duplicates = array_keys(array_filter($pdcPackages, static fn(int $count): bool => $count > 1));

        !empty($contractPackages)
            ? pdc3Pass($project['name'] . ' tiene paquetes definidos en Contratos')
            : pdc3Fail($project['name'] . ' no tiene paquetes definidos en Contratos');
        count($contractPackages) === count($pdcPackages)
            ? pdc3Pass($project['name'] . ' tiene el mismo número de paquetes en Contratos y PDC')
            : pdc3Fail($project['name'] . ' no cuadra paquetes Contratos/PDC');
        empty($missing)
            ? pdc3Pass($project['name'] . ' no tiene paquetes faltantes en PDC')
            : pdc3Fail($project['name'] . ' faltantes: ' . implode(', ', $missing));
        empty($extra)
            ? pdc3Pass($project['name'] . ' no tiene paquetes PDC extra frente a Contratos')
            : pdc3Fail($project['name'] . ' extras: ' . implode(', ', $extra));
        empty($duplicates)
            ? pdc3Pass($project['name'] . ' no tiene paquetes PDC duplicados')
            : pdc3Fail($project['name'] . ' duplicados: ' . implode(', ', $duplicates));

        $incomplete = (int) $db->query(
            "SELECT COUNT(*) FROM pdc
             WHERE project_id = ? AND semana = ? AND titulo = 0
               AND (COALESCE(paqueteContratacion, '') = '' OR COALESCE(contratos, '') = '' OR fechaInicio IS NULL)",
            [$project['id'], $weekNumber],
        )->fetchColumn();
        $incomplete === 0
            ? pdc3Pass($project['name'] . ' no tiene filas PDC incompletas para paquete/contrato/inicio')
            : pdc3Fail($project['name'] . " tiene {$incomplete} filas PDC incompletas");

        $preview = $service->preview(SemiAutoService::MODULE_PDC, [
            'projectId' => (int) $project['id'],
            'project_id' => (int) $project['id'],
            'dbPrefix' => $project['db'],
            'db' => $project['db'],
            'semana' => $weekNumber,
        ]);
        (($preview['total'] ?? 0) === 0)
            ? pdc3Pass($project['name'] . ' no deja propuestas pendientes en segundo pase PDC')
            : pdc3Fail($project['name'] . ' dejó ' . (int) ($preview['total'] ?? 0) . ' propuestas PDC pendientes');
    }
} catch (Throwable $e) {
    pdc3Fail($e->getMessage());
}

echo "=== PDC three mandatory projects perfect: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
