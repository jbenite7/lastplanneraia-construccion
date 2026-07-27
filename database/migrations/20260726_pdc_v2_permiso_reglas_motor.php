<?php

// 20260726_pdc_v2_permiso_reglas_motor.php
// PDC v2 / Fase A3.3 — permiso `lps.paquetes_contratacion.reglas` en la BD de RBAC.
//
// Aprobar una regla o un override GLOBAL no es lo mismo que asignar insumos en un proyecto: cambia
// el criterio del motor para toda AIA y para las obras que vengan. Por eso va aparte de
// `.editar`, y lo tiene quien conoce el vocabulario de los insumos.
//
// Reparto acordado con el usuario (grilleo 2026-07-26):
//   · Oficina Técnica / Compras (OT) — el profesional de presupuestos: aprueba reglas y overrides.
//   · Director de Obra (D) — control operativo total, audita el sembrado antes de que pase a fechas.
//   · Administrador (A) — ya tiene '*'.
//
// El catálogo en código (RbacCatalog) es el fallback; RbacService lee primero de estas tablas, así
// que sin esta migración el permiso no existiría en un entorno ya sembrado.
//
// Uso:  php database/migrations/20260726_pdc_v2_permiso_reglas_motor.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

const PERMISO = 'lps.paquetes_contratacion.reglas';
const ROLES = ['OT', 'D'];

$existeTabla = static function (Database $db, string $t): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$t],
    )->fetchColumn() > 0;
};

if (!$existeTabla($db, 'rbac_permissions') || !$existeTabla($db, 'rbac_role_permissions')) {
    fwrite(STDOUT, "[OMITIDO] Este entorno no tiene tablas RBAC sembradas; manda el catálogo en código.\n");
    exit(0);
}

$yaExiste = (int) $db->query('SELECT COUNT(*) FROM rbac_permissions WHERE permission_key = ?', [PERMISO])->fetchColumn() > 0;
$rolesFaltantes = [];
foreach (ROLES as $rol) {
    $hay = (int) $db->query(
        'SELECT COUNT(*) FROM rbac_role_permissions WHERE role_code = ? AND permission_key = ?',
        [$rol, PERMISO],
    )->fetchColumn() > 0;
    if (!$hay) {
        $rolesFaltantes[] = $rol;
    }
}

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] permiso ' . PERMISO . ': ' . ($yaExiste ? 'ya existe' : 'FALTA (se creará)') . "\n");
    fwrite(STDOUT, '          roles por asignar: ' . (implode(', ', $rolesFaltantes) ?: 'ninguno') . "\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if (!$yaExiste) {
    $db->query(
        'INSERT INTO rbac_permissions (permission_key, module_name, action_name, description, is_write, is_sensitive)
         VALUES (?, ?, ?, ?, 1, 1)',
        [PERMISO, 'lps', 'paquetes_contratacion_reglas', 'Aprobar reglas y overrides globales del motor de sembrado'],
    );
}
foreach ($rolesFaltantes as $rol) {
    $db->query(
        'INSERT INTO rbac_role_permissions (role_code, permission_key, allowed, source) VALUES (?, ?, 1, ?)',
        [$rol, PERMISO, 'pdc-a33'],
    );
}

fwrite(STDOUT, '[APLICADO] permiso listo; roles asignados: ' . (implode(', ', $rolesFaltantes) ?: 'ya estaban') . "\n");
exit(0);
