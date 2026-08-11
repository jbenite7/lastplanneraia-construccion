<?php

declare(strict_types=1);
// @requiere: puro


/**
 * Candado de autorización de Programación Semanal para los roles de solo lectura.
 *
 * Contexto (2026-07-30): en `/programacion-semanal` el rol `V` (Visualizador) ve tres botones
 * de acción — Autoprogramar, Agregar Actividad y Confirmar Compromisos — porque `syncPhaseUI()`
 * en `public/js/modules/programacion_semanal/hot.js` los vuelve a mostrar tras el ocultamiento
 * por rol de `maestroPermisos()`. Se dejan visibles pero deshabilitados; NO es una falla de
 * autorización, porque el servidor rechaza con 403 todas las mutaciones del módulo.
 *
 * Este test fija esa defensa del servidor: si alguien quita el guard de un entrypoint mutador o
 * le concede `lps.programacion_semanal.editar` a un rol de solo lectura, la visibilidad del botón
 * deja de ser cosmética y pasa a ser una escalada real. Aquí es donde se entera.
 *
 * Verificado en sesión real vía la puerta de servicio: `test.V` recibe 403 en
 * save(autoprogramar|bloquear_compromisos|tnp|nuevo), reabrir y auto-program; `test.R` recibe 200.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;

$fallos = 0;
$total = 0;

function verificar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "  OK   {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FAIL {$descripcion}\n";
}

const PERMISO_EDITAR = 'lps.programacion_semanal.editar';

echo "1) Los roles de solo lectura no tienen el permiso de edición\n";

$porRol = RbacCatalog::fallbackPermissionsByRole();

foreach (['V', 'C'] as $rol) {
    verificar(
        "el rol {$rol} no incluye " . PERMISO_EDITAR,
        !in_array(PERMISO_EDITAR, $porRol[$rol] ?? [], true)
    );
}

verificar(
    'el rol V conserva el permiso de lectura lps.programacion_semanal.ver',
    in_array('lps.programacion_semanal.ver', $porRol['V'] ?? [], true)
);

verificar(
    'el rol R sí incluye ' . PERMISO_EDITAR,
    in_array(PERMISO_EDITAR, $porRol['R'] ?? [], true)
);

echo "2) Cada entrypoint mutador del controlador exige el permiso\n";

$fuente = file_get_contents(__DIR__ . '/../src/Controllers/Api/SemanalApiController.php');
if ($fuente === false) {
    echo "  FAIL no se pudo leer SemanalApiController.php\n";
    exit(1);
}

// Métodos públicos que escriben. `list`, `getTnpActivities` y `getAutoProgramLog` son de lectura.
$mutadores = ['save', 'reabrir', 'autoProgram'];

foreach ($mutadores as $metodo) {
    $inicio = strpos($fuente, "public function {$metodo}(");
    if ($inicio === false) {
        verificar("existe el método {$metodo}()", false);
        continue;
    }
    // Basta con mirar el prólogo: el guard debe ser lo primero, antes de leer parámetros.
    $prologo = substr($fuente, $inicio, 400);
    verificar(
        "{$metodo}() exige " . PERMISO_EDITAR . ' antes de operar',
        str_contains($prologo, "rbac_guard_require_permission('" . PERMISO_EDITAR . "')")
    );
}

echo "\n{$total} comprobaciones, {$fallos} fallos\n";
exit($fallos === 0 ? 0 : 1);
