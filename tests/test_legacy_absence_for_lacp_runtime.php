<?php

/**
 * Runtime guard: Listado, Contratos y PDC no deben volver a conectar legacy retirado.
 *
 * Usage: docker compose exec app php tests/test_legacy_absence_for_lacp_runtime.php
 */

$root = dirname(__DIR__);
$failed = 0;
$passed = 0;

function legacyPass(string $message): void
{
    global $passed;
    echo "  PASS: {$message}\n";
    $passed++;
}

function legacyFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function fileText(string $path): string
{
    $text = file_get_contents($path);
    return $text === false ? '' : $text;
}

echo "=== Legacy absence guard for Listado, Contratos y PDC ===\n";

$runtimeFiles = [
    'views/pdc/pdc.view.php',
    'public/js/modules/semi_auto_review.js',
    'src/Controllers/Api/PdcAutoGenerateController.php',
    'src/Controllers/Api/SemiAutoController.php',
    'src/Services/SemiAutoService.php',
];

$forbiddenRuntime = [
    '/legacy/pdc/actualizar_pdc.php',
    '/api/contratos/auto-define',
    'modalAutoAsignarContratos',
    'btnAutoAsignar',
    'cargarSugerenciasAutoDefine',
    'aplicarSeleccionadas',
    'deshacerUltimaCorrida',
    'function autoDefine',
    'apply-from-actividades',
];

foreach ($runtimeFiles as $relativePath) {
    if (!file_exists($root . '/' . $relativePath)) {
        continue;
    }
    $text = fileText($root . '/' . $relativePath);
    foreach ($forbiddenRuntime as $needle) {
        if (str_contains($text, $needle)) {
            legacyFail("{$relativePath} conserva {$needle}");
        }
    }
}

$failed === 0
    ? legacyPass('No hay llamadas legacy retiradas en archivos runtime del alcance')
    : null;

$lacpViews = [
    'views/pdc/pdc.view.php',
];

foreach ($lacpViews as $relativePath) {
    $text = fileText($root . '/' . $relativePath);
    !str_contains($text, '/legacy/cambiar_pagina.php')
        ? legacyPass("{$relativePath} usa navegacion moderna")
        : legacyFail("{$relativePath} conserva navegacion legacy");
}

$routes = fileText($root . '/public/index.php');
!str_contains($routes, '/legacy/pdc/actualizar_pdc.php')
    ? legacyPass('La ruta legacy de PDC fue retirada del router')
    : legacyFail('La ruta legacy de PDC sigue declarada');

!str_contains($routes, '/api/contratos/auto-define')
    ? legacyPass('Las rutas legacy auto-define de Contratos fueron retiradas')
    : legacyFail('Alguna ruta legacy auto-define de Contratos sigue declarada');

!str_contains($routes, "[\\App\\Controllers\\Api\\ContratosApiController::class, 'autoDefine")
    ? legacyPass('Las rutas auto-define no ejecutan controlador legacy')
    : legacyFail('Alguna ruta auto-define ejecuta controlador legacy');

str_contains($routes, '/api/pdc/auto/apply-from-contratos')
    && !str_contains($routes, '/api/pdc/auto/apply-from-actividades')
    ? legacyPass('PDC se expone desde Contratos, no desde Actividades')
    : legacyFail('PDC conserva endpoint desde Actividades o falta endpoint desde Contratos');

echo "=== Legacy absence guard: {$failed} failed, {$passed} passed ===\n";
exit($failed === 0 ? 0 : 1);
