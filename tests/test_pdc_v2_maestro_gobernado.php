<?php

// test_pdc_v2_maestro_gobernado.php
// PDC v2 / Ola 1 — cierre pre-lanzamiento, punto 5 del spec
// `docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md`.
//
// En el comité del 2026-07-29 se afirmó que «la obra ya no puede tocar el maestro global de paquetes y
// solo un administrador lo actualiza». Este test convierte esa afirmación en algo comprobable, con un rol
// permitido y uno denegado, resueltos por `RbacService` contra la BD real (no contra el catálogo en
// código: `getPermissionMap()` lee primero de `rbac_role_permissions` y solo cae al fallback si está
// vacío, así que un entorno sembrado puede contradecir al catálogo).
//
// Se comprueban DOS capacidades, porque el maestro global se toca por dos puertas distintas:
//   · `lps.paquetes_contratacion.reglas`  — aprobar reglas y overrides GLOBALES del motor
//     (PlanComprasPlanController::guardaReglaGlobal / overrides).
//   · `lps.paquetes_contratacion.editar`  — la puerta que de verdad inserta una fila nueva en
//     `general_paquetes_contratacion` (PlanComprasPaquetesController::crear → guardEscritura →
//     PaquetesService::crearPaquete). Verificar solo `.reglas` dejaría la afirmación del comité sin
//     cubrir su camino más directo.
//
// Uso:  docker compose exec app php tests/test_pdc_v2_maestro_gobernado.php

declare(strict_types=1);
// @requiere: db


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

const REGLAS = 'lps.paquetes_contratacion.reglas';
const EDITAR = 'lps.paquetes_contratacion.editar';
const VER = 'lps.paquetes_contratacion.ver';

// ── Rol PERMITIDO: Oficina Técnica / Compras ────────────────────────────────
// Es el dueño acordado del maestro (grilleo 2026-07-26, migración 20260726_pdc_v2_permiso_reglas_motor).
fwrite(STDOUT, "Rol PERMITIDO — OT (Oficina Técnica / Compras):\n");
$assert($rbac->can(REGLAS, 'OT') === true, 'OT puede aprobar reglas globales (' . REGLAS . ').');
$assert($rbac->can(EDITAR, 'OT') === true, 'OT puede crear paquetes en el maestro (' . EDITAR . ').');

// ── Rol DENEGADO: Residente de Obra ─────────────────────────────────────────
// Es «la obra» del día a día: el rol que la afirmación del comité dice que no puede tocar el maestro.
fwrite(STDOUT, "\nRol DENEGADO — R (Residente de Obra, «la obra»):\n");
$assert($rbac->can(REGLAS, 'R') === false, 'R NO puede aprobar reglas globales (' . REGLAS . ').');
$assert($rbac->can(EDITAR, 'R') === false, 'R NO puede crear paquetes en el maestro (' . EDITAR . ').');
$assert($rbac->can(VER, 'R') === true, 'R sí puede VER el maestro (asigna insumos sin editar el catálogo).');

// ── Los otros roles de obra tampoco ─────────────────────────────────────────
fwrite(STDOUT, "\nOtros roles sin mando sobre el maestro:\n");
foreach (['DCV', 'V', 'C', 'S', 'G'] as $rol) {
    $assert($rbac->can(EDITAR, $rol) === false, "$rol NO puede crear paquetes en el maestro.");
    $assert($rbac->can(REGLAS, $rol) === false, "$rol NO puede aprobar reglas globales.");
}

// ── Lo que sí puede tocarlo, dicho sin adornos ──────────────────────────────
// La afirmación del comité («solo un administrador») es MÁS ESTRECHA que el reparto real y acordado:
// D y OT también pueden, por decisión explícita del grilleo del 2026-07-26. El test fija el reparto real
// para que nadie lo cambie por accidente, y la bitácora del goal corrige la frase del comité.
fwrite(STDOUT, "\nReparto real (más ancho que «solo un administrador», por decisión del 2026-07-26):\n");
$conMando = [];
foreach (['A', 'D', 'R', 'DCV', 'OT', 'V', 'C', 'S', 'G', 'SG'] as $rol) {
    if ($rbac->can(EDITAR, $rol)) {
        $conMando[] = $rol;
    }
}
fwrite(STDOUT, '  Roles con ' . EDITAR . ': ' . implode(', ', $conMando) . "\n");
$assert($conMando === ['A', 'D', 'OT'], 'El maestro lo escriben exactamente A, D y OT. Dio: ' . implode(', ', $conMando));

fwrite(STDOUT, "\n" . ($fallos === 0 ? "TODO OK\n" : "$fallos FALLO(S)\n"));
exit($fallos === 0 ? 0 : 1);
