<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function eqReviewPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function eqReviewFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function eqReviewAssert(bool $condition, string $message): void
{
    $condition ? eqReviewPass($message) : eqReviewFail($message);
}

echo "=== Equipment families route to contracts ===\n";

try {
    $db = Database::getInstance();
    $equipmentCodes = [
        'BOMBA_CONCRETO',
        'EXCAVADORA',
        'MALACATE',
        'MONTACARGAS',
        'MOTORGRUA',
        'PLANTA_CONCRETO',
        'TORREGRUA',
        'VOLQUETA',
    ];

    foreach ($equipmentCodes as $code) {
        $row = $db->query(
            'SELECT nombre, activa, siempre_revision FROM general_pdc_familias WHERE codigo = ?',
            [$code],
        )->fetch(PDO::FETCH_ASSOC);
        eqReviewAssert($row !== false, "{$code} existe en catalogo historico");
        eqReviewAssert((int) ($row['activa'] ?? 1) === 0, "{$code} no queda activo como familia de Listado");
        eqReviewAssert((int) ($row['siempre_revision'] ?? 1) === 0, "{$code} no queda como pendiente global");

        $activeRules = (int) $db->query(
            'SELECT COUNT(*)
             FROM general_pdc_activity_rules r
             JOIN general_pdc_familias f ON f.id = r.familia_id
             WHERE f.codigo = ? AND r.activa = 1',
            [$code],
        )->fetchColumn();
        eqReviewAssert($activeRules === 0, "{$code} no tiene reglas activas de Listado");

        $contractual = (int) $db->query(
            'SELECT COUNT(*)
             FROM general_pdc_contractual_elements
             WHERE nombre = ? AND activa = 1',
            [(string) ($row['nombre'] ?? '')],
        )->fetchColumn();
        eqReviewAssert($contractual > 0, "{$code} queda como elemento contractual activo");
    }
} catch (Throwable $e) {
    eqReviewFail($e->getMessage());
}

echo "=== Equipment families route to contracts: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
