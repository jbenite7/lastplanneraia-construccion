<?php

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/familias_revision_obligatoria.php';

$failed = 0;

function hdaPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function hdaFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function hdaAssert(bool $condition, string $message): void
{
    $condition ? hdaPass($message) : hdaFail($message);
}

echo "=== Human decision actions package ===\n";

try {
    $db = Database::getInstance();
    $packagePath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/human-decision-proposed-actions.json';
    $matrixPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/human-decision-matrix-13-families.md';
    $statusPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/STATUS.md';

    $package = json_decode(file_get_contents($packagePath) ?: '', true);
    hdaAssert(is_array($package), 'paquete JSON de decisiones es valido');
    hdaAssert(($package['status'] ?? '') === 'approved_applied', 'paquete queda aprobado y aplicado');
    hdaAssert(($package['safety']['applies_database_changes'] ?? false) === true, 'paquete registra cambios de BD aplicados');
    hdaAssert(($package['safety']['requires_explicit_user_approval'] ?? true) === false, 'paquete ya no queda pendiente de aprobacion');

    $allowed = array_flip($package['allowed_actions'] ?? []);
    foreach (['keep_family', 'move_to_contracts', 'keep_review', 'refine_rules', 'merge_to_operational_family'] as $action) {
        hdaAssert(isset($allowed[$action]), "accion permitida {$action}");
    }

    $decisions = $package['decisions'] ?? [];
    hdaAssert(count($decisions) === 13, 'paquete contiene 13 decisiones propuestas');

    $byCode = [];
    foreach ($decisions as $decision) {
        $code = (string) ($decision['family_code'] ?? '');
        hdaAssert($code !== '', 'cada decision tiene codigo');
        hdaAssert(!isset($byCode[$code]), "codigo unico {$code}");
        $byCode[$code] = $decision;

        $action = (string) ($decision['recommended_action'] ?? '');
        hdaAssert(isset($allowed[$action]), "{$code} tiene accion valida");

        if ($action === 'move_to_contracts') {
            hdaAssert(!empty($decision['contractual_package']['tipo_paquete'] ?? ''), "{$code} define tipo de paquete contractual");
            hdaAssert(!empty($decision['contractual_package']['paquete_nombre'] ?? ''), "{$code} define nombre de paquete contractual");
            hdaAssert(($decision['requires_rule_changes'] ?? false) === true, "{$code} exige cambios de reglas al pasar a Contratos");
        }

        if (in_array($action, ['keep_review', 'refine_rules', 'merge_to_operational_family'], true)) {
            hdaAssert(($decision['requires_rule_changes'] ?? false) || !empty($decision['contractual_package'] ?? null), "{$code} explica accion futura");
        }
    }

    $rows = $db->query(
        "SELECT codigo, nombre
         FROM general_pdc_familias
         WHERE codigo IN (
             'AMENIDADES_CUBIERTA', 'ASEO', 'BOMBA_CONCRETO', 'EXCAVADORA',
             'MALACATE', 'MONTACARGAS', 'MOTORGRUA', 'PLANTA_CONCRETO',
             'TORREGRUA', 'VOLQUETA', 'RED_TELECOMUNICACIONES', 'CAMPAMENTO',
             'BOTADA_ESCOMBROS'
         )
         ORDER BY categoria, nombre",
    )->fetchAll(PDO::FETCH_ASSOC);
    hdaAssert(count($rows) === 13, 'BD conserva trazabilidad de las 13 familias originales');

    foreach ($rows as $row) {
        $code = (string) $row['codigo'];
        hdaAssert(isset($byCode[$code]), "paquete cubre {$code}");
        hdaAssert(($byCode[$code]['family_name'] ?? '') === (string) $row['nombre'], "{$code} conserva nombre de BD");
    }

    $pending = familiasConRevisionObligatoria($db);
    hdaAssert($pending === FAMILIAS_REVISION_OBLIGATORIA, 'el catalogo mantiene exactamente las familias con revision obligatoria vigentes');

    $matrix = file_get_contents($matrixPath) ?: '';
    $status = file_get_contents($statusPath) ?: '';
    hdaAssert(str_contains($matrix, 'human-decision-proposed-actions.json'), 'matriz enlaza paquete JSON');
    hdaAssert(str_contains($status, 'human-decision-proposed-actions.json'), 'STATUS enlaza paquete JSON');
} catch (Throwable $e) {
    hdaFail($e->getMessage());
}

echo "=== Human decision actions package: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
