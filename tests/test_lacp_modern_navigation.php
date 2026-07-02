<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Controllers/BaseController.php';

use App\Controllers\BaseController;

$failed = 0;
$root = dirname(__DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('error_log', sys_get_temp_dir() . '/lacp_modern_navigation_test.log');

function lacpNavPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lacpNavFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== LACP modern navigation ===\n";

$views = [
    'Listado' => $root . '/views/listado-actividades/listadoActividades.view.php',
    'Contratos' => $root . '/views/contratos/contratos.view.php',
    'PDC' => $root . '/views/pdc/pdc.view.php',
];

foreach ($views as $label => $path) {
    $content = file_get_contents($path);
    if ($content === false) {
        lacpNavFail("No se pudo leer {$label}");
        continue;
    }

    !str_contains($content, '/legacy/cambiar_pagina.php?seccion=info_listadoActividades')
        ? lacpNavPass("{$label} no navega a Actividades por legacy")
        : lacpNavFail("{$label} todavia navega a Actividades por legacy");
    !str_contains($content, '/legacy/cambiar_pagina.php?seccion=info_contratos')
        ? lacpNavPass("{$label} no navega a Contratos por legacy")
        : lacpNavFail("{$label} todavia navega a Contratos por legacy");
    !str_contains($content, '/legacy/cambiar_pagina.php?seccion=planCompras')
        ? lacpNavPass("{$label} no navega a PDC por legacy")
        : lacpNavFail("{$label} todavia navega a PDC por legacy");
}

$allViews = implode("\n", array_map(static fn(string $path): string => (string) file_get_contents($path), $views));
str_contains($allViews, '/listado-actividades?semana=')
    ? lacpNavPass('La navegacion moderna incluye /listado-actividades?semana=')
    : lacpNavFail('Falta navegacion moderna hacia listado');
str_contains($allViews, '/contratos?semana=')
    ? lacpNavPass('La navegacion moderna incluye /contratos?semana=')
    : lacpNavFail('Falta navegacion moderna hacia contratos');
str_contains($allViews, '/pdc?semana=')
    ? lacpNavPass('La navegacion moderna incluye /pdc?semana=')
    : lacpNavFail('Falta navegacion moderna hacia PDC');

$controllerFiles = [
    $root . '/src/Controllers/Gestion/ListadoActividadesController.php',
    $root . '/src/Controllers/Gestion/ContratosController.php',
    $root . '/src/Controllers/Gestion/PdcController.php',
];
foreach ($controllerFiles as $path) {
    $content = (string) file_get_contents($path);
    str_contains($content, 'syncRequestedWeekContext()')
        ? lacpNavPass(basename($path) . ' sincroniza semana desde ruta moderna')
        : lacpNavFail(basename($path) . ' no sincroniza semana desde ruta moderna');
}

try {
    $_SESSION['project_id'] = 68;
    $_SESSION['semana'] = 1;
    $_GET['semana'] = '7';

    $controller = new class extends BaseController {
        public function syncForTest(): bool
        {
            return $this->syncRequestedWeekContext();
        }
    };

    $controller->syncForTest() && (int) $_SESSION['semana'] === 7
        ? lacpNavPass('La ruta moderna acepta una semana valida del proyecto activo')
        : lacpNavFail('La ruta moderna no sincronizo una semana valida');

    $_SESSION['semana'] = 7;
    $_GET['semana'] = '999999';
    !$controller->syncForTest() && (int) $_SESSION['semana'] === 7
        ? lacpNavPass('La ruta moderna rechaza semanas inexistentes del proyecto activo')
        : lacpNavFail('La ruta moderna acepto una semana inexistente');
} catch (Throwable $e) {
    lacpNavFail($e->getMessage());
}

echo "=== LACP modern navigation: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
