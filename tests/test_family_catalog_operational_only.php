<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function fcoPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function fcoFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function fcoAssert(bool $condition, string $message): void
{
    $condition ? fcoPass($message) : fcoFail($message);
}

echo "=== Family catalog operational only ===\n";

try {
    $db = Database::getInstance();

    $activeAliasFamilies = $db->query(
        'SELECT f.codigo, f.nombre, a.alias_nombre
         FROM general_pdc_familias f
         JOIN general_pdc_family_aliases a ON a.alias_family_id = f.id AND a.activa = 1
         WHERE COALESCE(f.activa, 1) = 1
         ORDER BY f.nombre',
    )->fetchAll(PDO::FETCH_ASSOC);
    fcoAssert($activeAliasFamilies === [], 'aliases conocidos no quedan activos como familias');

    $activeContractualFamilies = $db->query(
        'SELECT f.codigo, f.nombre, e.nombre AS contractual_nombre
         FROM general_pdc_familias f
         JOIN general_pdc_contractual_elements e
           ON e.nombre COLLATE utf8mb4_unicode_ci = f.nombre COLLATE utf8mb4_unicode_ci
          AND e.activa = 1
         WHERE COALESCE(f.activa, 1) = 1
         ORDER BY f.nombre',
    )->fetchAll(PDO::FETCH_ASSOC);
    fcoAssert($activeContractualFamilies === [], 'elementos contractuales conocidos no quedan activos como familias');

    $badRules = $db->query(
        'SELECT r.id, f.codigo, f.nombre
         FROM general_pdc_activity_rules r
         JOIN general_pdc_familias f ON f.id = r.familia_id
         WHERE r.activa = 1 AND COALESCE(f.activa, 1) = 0
         ORDER BY r.id',
    )->fetchAll(PDO::FETCH_ASSOC);
    fcoAssert($badRules === [], 'no hay reglas activas apuntando a familias inactivas');

    $canonicalAliases = $db->query(
        'SELECT COUNT(*)
         FROM general_pdc_family_aliases a
         JOIN general_pdc_familias f ON f.id = a.familia_id
         WHERE a.activa = 1 AND COALESCE(f.activa, 1) = 1',
    )->fetchColumn();
    fcoAssert((int) $canonicalAliases > 0, 'aliases activos apuntan a familias canonicas activas');

    $contractualCount = $db->query(
        'SELECT COUNT(*) FROM general_pdc_contractual_elements WHERE activa = 1',
    )->fetchColumn();
    fcoAssert((int) $contractualCount > 0, 'elementos contractuales siguen disponibles para Contratos');
} catch (Throwable $e) {
    fcoFail($e->getMessage());
}

echo "=== Family catalog operational only: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
