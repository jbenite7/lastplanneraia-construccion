<?php

require_once __DIR__ . '/../vendor/autoload.php';

$failed = 0;

function pdcModernPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function pdcModernFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== PDC modern update endpoint ===\n";

$view = file_get_contents(__DIR__ . '/../views/pdc/pdc.view.php') ?: '';
$routes = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
$browserTest = file_get_contents(__DIR__ . '/browser/test-pdc.mjs') ?: '';

str_contains($routes, "/api/pdc/auto/apply-from-contratos")
    ? pdcModernPass('modern PDC from contracts route is registered')
    : pdcModernFail('modern PDC from contracts route is missing');

!str_contains($routes, "/legacy/pdc/actualizar_pdc.php")
    ? pdcModernPass('legacy PDC route was removed from router')
    : pdcModernFail('legacy PDC route is still declared');

!is_file(__DIR__ . '/../src/Legacy/actualizar_pdc.php')
    ? pdcModernPass('legacy PDC updater file was removed')
    : pdcModernFail('legacy PDC updater file still exists');

str_contains($view, "/api/pdc/auto/apply-from-contratos")
    ? pdcModernPass('PDC view calls modern from-contracts endpoint')
    : pdcModernFail('PDC view does not call modern from-contracts endpoint');

!str_contains($view, "/legacy/pdc/actualizar_pdc.php")
    ? pdcModernPass('PDC view no longer calls legacy update endpoint')
    : pdcModernFail('PDC view still calls legacy update endpoint');

!str_contains($browserTest, "/legacy/pdc/actualizar_pdc.php")
    ? pdcModernPass('browser test no longer accepts legacy update endpoint')
    : pdcModernFail('browser test still accepts legacy update endpoint');

!str_contains($view . $routes . $browserTest, 'apply-from-actividades')
    ? pdcModernPass('no PDC from activities endpoint remains in reviewed files')
    : pdcModernFail('found PDC from activities wording or endpoint');

echo "=== PDC modern update endpoint: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
