<?php

$failed = 0;
$root = dirname(__DIR__);
$view = (string) file_get_contents($root . '/views/contratos/contratos.view.php');
$routes = (string) file_get_contents($root . '/public/index.php');
$browserTest = (string) file_get_contents($root . '/tests/browser/auto-definir-contratos.mjs');

function contratosModernPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function contratosModernFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== Contratos modern assistant UI ===\n";

str_contains($view, "window.SemiAutoReview.open('contratos')")
    ? contratosModernPass('El boton visible abre el asistente moderno de Contratos')
    : contratosModernFail('El boton visible no abre el asistente moderno de Contratos');

str_contains($view, '/api/contratos/auto/preview?db=')
    ? contratosModernPass('El badge de pendientes usa preview moderno')
    : contratosModernFail('El badge de pendientes no usa preview moderno');

!str_contains($view, "$('#modalAutoAsignarContratos').modal('show')")
    ? contratosModernPass('La UI visible ya no abre el modal viejo como fallback')
    : contratosModernFail('La UI visible todavia abre el modal viejo como fallback');

!str_contains($view, 'modalAutoAsignarContratos')
    && !str_contains($view, '/api/contratos/auto-define')
    && !str_contains($view, 'btnAutoAsignar')
    && !str_contains($view, 'aad-')
    ? contratosModernPass('La vista no carga modal ni JS legacy auto-define')
    : contratosModernFail('La vista conserva residuos del modal o JS legacy auto-define');

str_contains($browserTest, '/api/contratos/auto/preview')
    ? contratosModernPass('El E2E espera el endpoint moderno de Contratos')
    : contratosModernFail('El E2E no cubre el endpoint moderno de Contratos');

!str_contains($routes, "[\\App\\Controllers\\Api\\ContratosApiController::class, 'autoDefine")
    ? contratosModernPass('Las rutas auto-define ya no apuntan al controlador legacy')
    : contratosModernFail('Alguna ruta auto-define todavia apunta al controlador legacy');

!str_contains($routes, '/api/contratos/auto-define')
    ? contratosModernPass('Las rutas auto-define legacy fueron retiradas')
    : contratosModernFail('Alguna ruta auto-define legacy sigue declarada');

echo "=== Contratos modern assistant UI: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
