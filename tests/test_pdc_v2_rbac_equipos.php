<?php

// test_pdc_v2_rbac_equipos.php
// PDC v2 / Ola 2 — quién puede clasificar un equipo como alquilado o comprado.
//
// Clasificar toca `general_maestro_insumos`, que es un catálogo GLOBAL de la empresa: la decisión
// vale para todos los proyectos de AIA. Por eso la capacidad es de ADMINISTRACIÓN del maestro
// (`lps.pdc.maestro`, descrita en RbacCatalog como «Administrar el maestro global de insumos del
// plan de compras v2»), y NO `lps.paquetes_contratacion.reglas`, que gobierna reglas y overrides del
// motor de sembrado — otra puerta, otro objeto.
//
// El spec dejaba la elección «a confirmar al escribir el plan»; este test la fija con un rol
// permitido y uno denegado, resueltos por `RbacService` contra la BD real (no contra el catálogo en
// código: `getPermissionMap()` lee primero de `rbac_role_permissions` y sólo cae al fallback si está
// vacía, así que un entorno sembrado puede contradecir al catálogo).
//
// Uso:  docker compose exec app php tests/test_pdc_v2_rbac_equipos.php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\RbacService;

$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    if ($cond) {
        fwrite(STDOUT, "  OK   $msg\n");
        return;
    }
    fwrite(STDOUT, "  FAIL $msg\n");
    $fallos++;
};

$db = Database::getInstance();
$rbac = new RbacService($db);

// De dónde sale la respuesta, para que la evidencia no sea ambigua.
$origen = 'fallback (catálogo en código)';
try {
    $hayTabla = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rbac_role_permissions'",
    )->fetchColumn();
    if ($hayTabla > 0) {
        $filas = (int) $db->query('SELECT COUNT(*) FROM rbac_role_permissions')->fetchColumn();
        if ($filas > 0) {
            $origen = "BD (`rbac_role_permissions`, $filas filas)";
        }
    }
} catch (\Throwable $e) {
    $origen = 'fallback (no se pudo consultar rbac_role_permissions: ' . $e->getMessage() . ')';
}
fwrite(STDOUT, "Origen de los permisos: $origen\n\n");

const MAESTRO = 'lps.pdc.maestro';
const VER_PDC = 'lps.pdc.ver';
const REGLAS_MOTOR = 'lps.paquetes_contratacion.reglas';

// ── Rol PERMITIDO ───────────────────────────────────────────────────────────
// Administrador: el maestro de insumos es de la empresa, y su administración es la capacidad que
// `PlanComprasMaestroController::guardEscritura()` exige para POST /maestro/equipos/clasificar.
fwrite(STDOUT, "Rol PERMITIDO — A (Administrador):\n");
$assert($rbac->can(MAESTRO, 'A') === true, 'A puede administrar el maestro y por tanto clasificar equipos (' . MAESTRO . ').');
$assert($rbac->can(VER_PDC, 'A') === true, 'A puede leer la cola de equipos (' . VER_PDC . ').');

// ── Rol DENEGADO ────────────────────────────────────────────────────────────
// Visualizador: puede mirar el plan de compras, no cambiar el catálogo de la empresa.
fwrite(STDOUT, "\nRol DENEGADO — V (Visualizador):\n");
$assert($rbac->can(MAESTRO, 'V') === false, 'V NO puede clasificar equipos (' . MAESTRO . ' denegado → 403 en el POST).');
$assert($rbac->can(VER_PDC, 'V') === true, 'V sí puede LEER la cola: el GET es de lectura y no cambia nada.');

// ── La obra tampoco: clasificar no es una capacidad de obra ──────────────────
// Es el punto del spec: «clasificar es tocar el maestro global → capacidad de administración, nunca
// la obra». R (Residente) es el rol del día a día en obra.
fwrite(STDOUT, "\nRoles de obra — clasificar no es una capacidad de obra:\n");
foreach (['R', 'C', 'S', 'G', 'V'] as $rol) {
    $assert($rbac->can(MAESTRO, $rol) === false, "$rol NO puede clasificar equipos del maestro global.");
}

// ── La capacidad es la del maestro, no la de las reglas del motor ────────────
// Si ambas dieran exactamente lo mismo para todos los roles, elegir una u otra sería indistinguible
// y el spec no habría tenido que preguntar. Se deja constancia del reparto de cada una.
fwrite(STDOUT, "\nReparto de cada capacidad (para que la elección quede justificada):\n");
$reparto = static function (RbacService $rbac, string $cap): array {
    $con = [];
    foreach (['A', 'D', 'R', 'DCV', 'OT', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
        if ($rbac->can($cap, $rol)) {
            $con[] = $rol;
        }
    }
    return $con;
};
$conMaestro = $reparto($rbac, MAESTRO);
$conReglas = $reparto($rbac, REGLAS_MOTOR);
fwrite(STDOUT, '  ' . MAESTRO . ': ' . implode(', ', $conMaestro) . "\n");
fwrite(STDOUT, '  ' . REGLAS_MOTOR . ': ' . implode(', ', $conReglas) . "\n");
$assert($conMaestro !== [], 'Alguien puede administrar el maestro (si no, la cola sería irresoluble).');
$assert(!in_array('R', $conMaestro, true), 'El residente de obra no está entre quienes administran el maestro.');
$assert(!in_array('V', $conMaestro, true), 'El visualizador tampoco.');

fwrite(STDOUT, "\n" . ($fallos === 0 ? "TODO OK\n" : "$fallos FALLO(S)\n"));
exit($fallos === 0 ? 0 : 1);
