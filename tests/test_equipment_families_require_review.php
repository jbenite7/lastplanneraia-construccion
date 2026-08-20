<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

/**
 * Verifica que la migración de decisiones humanas (20260711) enruta las familias de equipos
 * hacia contratos: familia desactivada, sin pendiente global, sin reglas de Listado activas,
 * y con su elemento contractual activo.
 *
 * Hasta el 2026-08-19 este test asertaba el estado PERSISTENTE del catálogo en la base
 * compartida de dev, y cualquier restauración ajena que borrara una familia (pasó con
 * MALACATE) lo dejaba en rojo sin relación con el código. Ahora es determinista: dentro de
 * una transacción siembra las 8 familias en su peor estado posible (activas y con pendiente
 * global), aplica la migración real desde su archivo y aserta el estado final; todo se
 * revierte al terminar. Lo que mide es la lógica de la migración, no la salud de la base.
 */

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

$db = Database::getInstance();
$db->beginTransaction();

try {
    $equipmentFamilies = [
        'BOMBA_CONCRETO' => 'Bomba de Concreto',
        'EXCAVADORA' => 'Excavadora',
        'MALACATE' => 'Malacate',
        'MONTACARGAS' => 'Montacargas',
        'MOTORGRUA' => 'Motorgrua',
        'PLANTA_CONCRETO' => 'Planta de Concreto',
        'TORREGRUA' => 'Torregrua',
        'VOLQUETA' => 'Volqueta',
    ];

    // Precondición: la familia existe en el catálogo, en el peor estado posible para la
    // migración (activa y con pendiente global). Si ya existe, se fuerza ese estado para que
    // la aserción final pruebe que la migración lo corrige de verdad.
    $orden = 900100;
    foreach ($equipmentFamilies as $codigo => $nombre) {
        $db->query(
            'INSERT INTO general_pdc_familias (codigo, nombre, categoria, orden, siempre_revision, activa)
             VALUES (?, ?, ?, ?, 1, 1)
             ON DUPLICATE KEY UPDATE siempre_revision = 1, activa = 1',
            [$codigo, $nombre, 'EQUIPOS', $orden++],
        );
    }

    // Aplica la migración real, sentencia por sentencia (solo DML idempotente; sin DDL,
    // así que la transacción la cubre completa).
    $migrationSql = file_get_contents(dirname(__DIR__) . '/database/migrations/20260711_apply_human_family_decisions.sql');
    if ($migrationSql === false) {
        throw new RuntimeException('No se pudo leer la migración 20260711_apply_human_family_decisions.sql');
    }
    foreach (preg_split('/;\s*\n/', $migrationSql) as $statement) {
        $lines = array_filter(
            array_map('rtrim', explode("\n", $statement)),
            static fn(string $line): bool => trim($line) !== '' && !str_starts_with(trim($line), '--'),
        );
        $statement = trim(implode("\n", $lines));
        if ($statement === '') {
            continue;
        }
        $db->query($statement);
    }

    foreach ($equipmentFamilies as $code => $nombre) {
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
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo $failed > 0
    ? "=== Equipment families route to contracts: {$failed} failed ===\n"
    : "=== Equipment families route to contracts: OK ===\n";
exit($failed > 0 ? 1 : 0);
