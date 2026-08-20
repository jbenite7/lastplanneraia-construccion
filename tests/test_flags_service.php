<?php
// tests/test_flags_service.php
// @requiere: db

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\FlagsService;

$total = 0;
$fallos = 0;

function comprobar(string $caso, bool $obtenido, bool $esperado): void
{
    global $total, $fallos;
    $total++;
    if ($obtenido === $esperado) {
        echo "  OK   {$caso}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba " . var_export($esperado, true)
        . ", obtuvo " . var_export($obtenido, true) . "\n";
}

echo "FlagsService::isOn contra la base real:\n";
// La migración de la Task 1 siembra el flag en '1'.
comprobar('flag sembrado en 1 devuelve true', FlagsService::isOn('bi.control_tower.visible'), true);
comprobar('clave inexistente devuelve false', FlagsService::isOn('no.existe.jamas'), false);

echo "\nCache por request:\n";
// Segunda lectura de la misma clave: debe salir del cache (no medimos la query,
// pero sí que el resultado es estable).
comprobar('segunda lectura estable', FlagsService::isOn('bi.control_tower.visible'), true);

echo "\nOverride de pruebas:\n";
FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
comprobar('override apaga sin tocar la base', FlagsService::isOn('bi.control_tower.visible'), false);
FlagsService::overrideForTests(['otra.clave' => true]);
comprobar('override enciende una clave que no existe en base', FlagsService::isOn('otra.clave'), true);
comprobar('con override activo, clave no listada es false', FlagsService::isOn('bi.control_tower.visible'), false);
FlagsService::overrideForTests(null);
comprobar('limpiar el override vuelve a la base', FlagsService::isOn('bi.control_tower.visible'), true);

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
