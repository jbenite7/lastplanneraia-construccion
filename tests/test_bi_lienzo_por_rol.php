<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Prueba: qué módulo trae el enlace de entrada de la Torre según el rol.
 * Ver docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\BiAccessComponent;

$fallos = 0;
$total = 0;

function comprobar(string $caso, $obtenido, $esperado): void
{
    global $fallos, $total;
    $total++;
    if ($obtenido === $esperado) {
        echo "  OK   {$caso}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba " . var_export($esperado, true)
        . ", obtuvo " . var_export($obtenido, true) . "\n";
}

echo "Aterrizaje por rol:\n";
comprobar('Director aterriza en Intermedia', BiAccessComponent::defaultModuleForRole('D'), 'intermedia');
comprobar('Residente aterriza en Intermedia', BiAccessComponent::defaultModuleForRole('R'), 'intermedia');
comprobar(
    'Admin sin elección previa aterriza en gerencia (control-tower)',
    BiAccessComponent::defaultModuleForRole('A'),
    'control-tower'
);

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
