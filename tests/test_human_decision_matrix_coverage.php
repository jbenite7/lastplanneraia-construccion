<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/familias_revision_obligatoria.php';

$failed = 0;

function hdmPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function hdmFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function hdmAssert(bool $condition, string $message): void
{
    $condition ? hdmPass($message) : hdmFail($message);
}

echo "=== Human decision matrix coverage ===\n";

try {
    $db = Database::getInstance();
    $matrixPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/human-decision-matrix-13-families.md';
    $auditPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/catalog-human-audit.md';
    $statusPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/STATUS.md';

    $matrix = file_get_contents($matrixPath) ?: '';
    $audit = file_get_contents($auditPath) ?: '';
    $status = file_get_contents($statusPath) ?: '';

    hdmAssert($matrix !== '', 'existe matriz de decision humana');

    $decisionRows = $db->query(
        "SELECT codigo, nombre, categoria
         FROM general_pdc_familias
         WHERE codigo IN (
             'AMENIDADES_CUBIERTA', 'ASEO', 'BOMBA_CONCRETO', 'EXCAVADORA',
             'MALACATE', 'MONTACARGAS', 'MOTORGRUA', 'PLANTA_CONCRETO',
             'TORREGRUA', 'VOLQUETA', 'RED_TELECOMUNICACIONES', 'CAMPAMENTO',
             'BOTADA_ESCOMBROS'
         )
         ORDER BY categoria, nombre",
    )->fetchAll(PDO::FETCH_ASSOC);

    hdmAssert(count($decisionRows) === 13, 'la BD conserva trazabilidad de las 13 decisiones humanas');

    foreach ($decisionRows as $row) {
        $name = (string) $row['nombre'];
        hdmAssert(str_contains($matrix, '| ' . $name . ' |'), "la matriz cubre {$name}");
    }

    $pending = familiasConRevisionObligatoria($db);
    hdmAssert($pending === FAMILIAS_REVISION_OBLIGATORIA, 'el catalogo mantiene exactamente las familias con revision obligatoria vigentes');

    foreach (['Pasar a Contratos', 'Mantener en revision', 'Aprobar como familias operativas'] as $section) {
        hdmAssert(str_contains($matrix, $section), "la matriz incluye seccion {$section}");
    }

    hdmAssert(str_contains($audit, 'human-decision-matrix-13-families.md'), 'la auditoria enlaza la matriz');
    hdmAssert(str_contains($status, 'human-decision-matrix-13-families.md'), 'el estado del goal enlaza la matriz');
} catch (Throwable $e) {
    hdmFail($e->getMessage());
}

echo "=== Human decision matrix coverage: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
