<?php

/**
 * Smoke test: retiro del auto-definir legacy de Contratos.
 *
 * Usage: docker compose exec app php tests/test_auto_definir_contratos.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$passed = 0;
$failed = 0;
$skipped = 0;

function pass(string $testName): void
{
    global $passed;
    echo "  PASS: $testName\n";
    $passed++;
}

function fail(string $testName, string $detail = ''): void
{
    global $failed;
    $message = $detail !== '' ? " - $detail" : '';
    echo "  FAIL: $testName$message\n";
    $failed++;
}

function skip(string $reason): void
{
    global $skipped;
    echo "  SKIP: $reason\n";
    $skipped++;
}

echo "=== Smoke Test: Contratos moderno reemplaza auto-definir legacy ===\n\n";

$root = dirname(__DIR__);
$db = Database::getInstance();

echo "--- Matcher y permisos base ---\n";
$matcher = new \App\Support\ActivityMatcher();
$rules = $matcher->loadRules();
if (empty($rules)) {
    skip('No hay reglas en general_pdc_activity_rules para validar match.');
} else {
    $result = $matcher->matchActivity(['Actividad' => 'PRELIMINARES - CAMPAMENTO'], $rules);
    if ($result === null) {
        skip('La actividad de muestra no hizo match; depende de datos locales.');
    } else {
        is_float($result['confidence'] ?? null)
            ? pass('ActivityMatcher entrega confianza como decimal')
            : fail('ActivityMatcher no entrega confianza decimal', gettype($result['confidence'] ?? null));
    }
}

try {
    $stmt = $db->query(
        "SELECT COUNT(*) AS total FROM rbac_permissions WHERE permission_key = 'lps.contratos.auto_definir'"
    );
    $total = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    $total > 0
        ? pass('El permiso de asistente de Contratos existe')
        : fail('Falta el permiso lps.contratos.auto_definir');
} catch (Throwable $e) {
    fail('No se pudo consultar permisos', $e->getMessage());
}

echo "--- Controlador y rutas ---\n";
$controllerClass = 'App\\Controllers\\Api\\ContratosApiController';
if (!class_exists($controllerClass)) {
    fail('ContratosApiController no existe');
} else {
    pass('ContratosApiController existe');
    foreach (['list', 'save', 'autoAssign'] as $method) {
        method_exists($controllerClass, $method)
            ? pass("Metodo moderno/base $method() disponible")
            : fail("Falta metodo $method()");
    }
    foreach (['autoDefine', 'autoDefineApply', 'autoDefineReanalyze', 'autoDefineUndo'] as $method) {
        !method_exists($controllerClass, $method)
            ? pass("Metodo legacy $method() retirado del controlador")
            : fail("Metodo legacy $method() sigue en el controlador");
    }
}

$routes = file_get_contents($root . '/public/index.php') ?: '';
!str_contains($routes, "/api/contratos/auto-define")
    ? pass('Las rutas legacy auto-define fueron retiradas del router')
    : fail('Alguna ruta legacy auto-define sigue declarada');

!str_contains($routes, "[\\App\\Controllers\\Api\\ContratosApiController::class, 'autoDefine")
    ? pass('Las rutas auto-define ya no apuntan al controlador')
    : fail('Alguna ruta auto-define sigue conectada al controlador');

echo "--- Vista de Contratos ---\n";
$view = file_get_contents($root . '/views/contratos/contratos.view.php') ?: '';
$semiAutoService = file_get_contents($root . '/src/Services/SemiAutoService.php') ?: '';
$semiAutoUi = file_get_contents($root . '/public/js/modules/semi_auto_review.js') ?: '';
str_contains($view, "SemiAutoReview.open('contratos')")
    ? pass('El boton abre el asistente moderno de Contratos')
    : fail('El boton no abre el asistente moderno');

!str_contains($view, 'modalAutoAsignarContratos') && !str_contains($view, '/api/contratos/auto-define')
    ? pass('La vista no conserva modal ni llamadas legacy auto-define')
    : fail('La vista conserva residuos legacy auto-define');

str_contains($semiAutoService, 'catalog_status') && str_contains($semiAutoService, 'FamilyCatalogStatusResolver')
    ? pass('El asistente de Contratos incluye estado derivado del catálogo')
    : fail('El asistente de Contratos no expone catalog_status');

str_contains($semiAutoUi, 'catalogStatusHtml') && str_contains($semiAutoUi, 'Paquete sugerido')
    ? pass('La UI del asistente muestra estado, motivo y paquete sugerido')
    : fail('La UI del asistente no renderiza el estado del catálogo');

str_contains($semiAutoService, 'cantidadDefault') && str_contains($semiAutoService, 'cantidad_default')
    ? pass('Contratos usa cantidad por defecto de paquetes guiados')
    : fail('Contratos no usa cantidad por defecto de paquetes guiados');

echo "\n=== Results: $passed passed, $failed failed, $skipped skipped ===\n";
exit($failed > 0 ? 1 : 0);
