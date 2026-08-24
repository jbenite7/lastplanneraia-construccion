<?php

declare(strict_types=1);
// @requiere: db

/**
 * El módulo BI (Control Tower) está oculto de la navegación mientras se desarrolla, y
 * solo el rol Admin puede abrirlo por URL directa.
 *
 * Declara `db` y no `puro` porque `RbacService::__construct()` llama a
 * `Database::getInstance()` aunque `normalizeRole()` no consulte nada
 * (src/Security/RbacService.php:14). Correrlo exige el servicio `db` levantado.
 *
 * Ver docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;
use App\Security\RbacManager;

$fallos = 0;
$total = 0;

function comprobar(string $caso, bool $obtenido, bool $esperado): void
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

echo "Capacidad internal.bi.preview:\n";
comprobar(
    'rol A la tiene',
    RbacManager::hasCapability('A', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
comprobar(
    'rol D la tiene (ampliado el 2026-08-20)',
    RbacManager::hasCapability('D', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
comprobar(
    'rol R la tiene (ampliado el 2026-08-24)',
    RbacManager::hasCapability('R', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
foreach (['V', 'C', 'DCV', 'OT', 'S', 'G', 'SG'] as $rol) {
    comprobar("rol {$rol} no la tiene", RbacManager::hasCapability($rol, RbacCatalog::PERM_INTERNAL_BI_PREVIEW), false);
}

echo "\nGate de las rutas (BiPreviewAccessPolicy::canOpen):\n";
comprobar(
    'sesion de Admin abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
comprobar(
    'sesion de Director abre (ampliado el 2026-08-20)',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    true
);
comprobar(
    'sesion de Residente abre (ampliado el 2026-08-24)',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    true
);
comprobar(
    'sesion de Visualizador no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.V'], 'V'),
    false
);
comprobar(
    'sesion vacia no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen([], ''),
    false
);
comprobar(
    'alias de rol se normaliza (RESIDENTE DE OBRA -> R) y abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'x'], 'RESIDENTE DE OBRA'),
    true
);

echo "\nInterruptor global (general_flags via FlagsService):\n";

\App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
comprobar(
    'flag apagado: Admin sigue entrando',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
comprobar(
    'flag apagado: Director NO entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    false
);

\App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => true]);
comprobar(
    'flag encendido: Director entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    true
);
comprobar(
    'flag encendido: Residente entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    true
);
comprobar(
    'flag apagado: Residente NO entra (no es Admin)',
    (function () {
        \App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
        $resultado = \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R');
        \App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => true]);
        return $resultado;
    })(),
    false
);

\App\Core\FlagsService::overrideForTests([]);
comprobar(
    'flag ilegible (override vacio = todo false): Director NO entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    false
);
comprobar(
    'flag ilegible: Admin sigue entrando',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
\App\Core\FlagsService::overrideForTests(null);

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
