<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$db = Database::getInstance();
$failed = 0;
$feedbackNote = 'ci-learning-persistence-project-73';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function lpcPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lpcFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lpcAssert(bool $condition, string $message): void
{
    $condition ? lpcPass($message) : lpcFail($message);
}

function lpcScalar(Database $db, string $sql, array $params = []): mixed
{
    return $db->query($sql, $params)->fetchColumn();
}

echo "=== Learning persistence in catalog DB ===\n";

try {
    $_SESSION['usuario'] = 'test.A';
    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';
    $_SESSION['project_id'] = 73;
    $_SESSION['db'] = 'da_porto';
    $_SESSION['semana'] = 1;

    $db->query('DELETE FROM semi_auto_feedback WHERE notes = ?', [$feedbackNote]);
    $feedback = (new SemiAutoService($db))->feedback(
        SemiAutoService::MODULE_LISTADO,
        ['projectId' => 73, 'semana' => 1],
        [
            'feedback_type' => 'catalog_validation',
            'original' => ['source' => 'ci-learning-persistence'],
            'notes' => $feedbackNote,
        ],
    );
    lpcAssert(($feedback['respuesta'] ?? '') === 'BIEN', 'feedback semi-auto se escribe por el servicio');

    foreach ([
        'general_pdc_familias',
        'general_pdc_family_aliases',
        'general_pdc_contractual_elements',
        'general_pdc_activity_rules',
        'general_pdc_family_contract_options',
        'general_pdc_family_contract_option_items',
        'general_pdc_family_rule_audit',
        'semi_auto_feedback',
    ] as $table) {
        $exists = (int) lpcScalar(
            $db,
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table],
        );
        lpcAssert($exists === 1, "{$table} existe como tabla global");
    }

    $catalogCounts = [
        'familias activas' => ['SELECT COUNT(*) FROM general_pdc_familias WHERE COALESCE(activa, 1) = 1', 1],
        'aliases activos' => ['SELECT COUNT(*) FROM general_pdc_family_aliases WHERE activa = 1', 1],
        'elementos contractuales activos' => ['SELECT COUNT(*) FROM general_pdc_contractual_elements WHERE activa = 1', 1],
        'reglas activas' => ['SELECT COUNT(*) FROM general_pdc_activity_rules WHERE activa = 1', 1],
    ];
    foreach ($catalogCounts as $label => [$sql, $minimum]) {
        $count = (int) lpcScalar($db, $sql);
        lpcAssert($count >= $minimum, "{$label}: {$count}");
    }
    $projectFeedback = (int) lpcScalar(
        $db,
        'SELECT COUNT(*) FROM semi_auto_feedback WHERE project_id = 73 AND notes = ?',
        [$feedbackNote],
    );
    lpcAssert($projectFeedback === 1, 'feedback semi-auto registrado para proyecto 73');
    $otherProjectFeedback = (int) lpcScalar(
        $db,
        'SELECT COUNT(*) FROM semi_auto_feedback WHERE project_id = 75 AND notes = ?',
        [$feedbackNote],
    );
    lpcAssert($otherProjectFeedback === 0, 'feedback de proyecto 73 no aparece en proyecto 75');

    foreach ([
        'Enchapes Ceramicos en Muros' => 'PISOS_ENCHAPES',
        'Red RCI' => 'RED_EXTINCION',
        'Red Contra Incendio - Piping' => 'RED_EXTINCION',
    ] as $alias => $canonicalCode) {
        $target = lpcScalar(
            $db,
            'SELECT f.codigo
             FROM general_pdc_family_aliases a
             JOIN general_pdc_familias f ON f.id = a.familia_id
             WHERE a.alias_nombre = ? AND a.activa = 1',
            [$alias],
        );
        lpcAssert($target === $canonicalCode, "{$alias} apunta a {$canonicalCode}");

        $legacyActive = (int) lpcScalar(
            $db,
            'SELECT COUNT(*) FROM general_pdc_familias
             WHERE nombre = ? AND COALESCE(activa, 1) = 1',
            [$alias],
        );
        lpcAssert($legacyActive === 0, "{$alias} no queda como familia activa");
    }

    foreach ([
        'Acero de Refuerzo y Estructural',
        'Equipos de Extincion',
        'Mano de Obra - Acabados',
        'Luminarias y Artefactos Electricos',
        'Bomba de Concreto',
        'Excavadora',
        'Malacate',
        'Montacargas',
        'Motorgrua',
        'Planta de Concreto',
        'Torregrua',
        'Volqueta',
        'Botada de Escombros',
        'Campamento de Obra',
        'Amenidades Especiales de Cubierta',
    ] as $contractualName) {
        $contractualRows = (int) lpcScalar(
            $db,
            'SELECT COUNT(*) FROM general_pdc_contractual_elements
             WHERE nombre = ? AND activa = 1',
            [$contractualName],
        );
        lpcAssert($contractualRows > 0, "{$contractualName} vive como elemento contractual");

        $familyRows = (int) lpcScalar(
            $db,
            'SELECT COUNT(*) FROM general_pdc_familias
             WHERE nombre = ? AND COALESCE(activa, 1) = 1',
            [$contractualName],
        );
        lpcAssert($familyRows === 0, "{$contractualName} no queda como familia activa");
    }

    $redElectricaOption = $db->query(
        'SELECT o.id, o.tipo_contrato, o.tipo_paquete
         FROM general_pdc_familias f
         JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
         WHERE f.codigo = ?',
        ['RED_ELECTRICA'],
    )->fetch(PDO::FETCH_ASSOC);
    lpcAssert($redElectricaOption !== false, 'Red Electrica tiene default activo de contrato');
    lpcAssert((int) ($redElectricaOption['tipo_contrato'] ?? 0) === 1, 'Red Electrica queda como MO + S');

    $redItems = $db->query(
        'SELECT i.tipo_paquete, i.paquete_nombre
         FROM general_pdc_family_contract_option_items i
         WHERE i.option_id = ?
         ORDER BY i.orden ASC',
        [(int) ($redElectricaOption['id'] ?? 0)],
    )->fetchAll(PDO::FETCH_ASSOC);
    $redLabels = array_map(
        static fn (array $row): string => $row['tipo_paquete'] . ':' . $row['paquete_nombre'],
        $redItems,
    );
    lpcAssert(in_array('Suministro:MATERIALES RED ELECTRICA', $redLabels, true), 'Red Electrica conserva paquete de suministro');
    lpcAssert(in_array('Mano de Obra:MANO DE OBRA RED ELECTRICA', $redLabels, true), 'Red Electrica conserva paquete de mano de obra');

    $activePaintingOptions = $db->query(
        'SELECT o.id, o.tipo_contrato, o.tipo_paquete
         FROM general_pdc_familias f
         JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
         WHERE f.codigo = ?',
        ['PINTURAS'],
    )->fetchAll(PDO::FETCH_ASSOC);
    lpcAssert(count($activePaintingOptions) === 1, 'Pinturas tiene un solo default activo');
    $paintingOption = $activePaintingOptions[0] ?? [];
    lpcAssert((int) ($paintingOption['tipo_contrato'] ?? 0) === 2, 'Pinturas queda como SI');
    lpcAssert(($paintingOption['tipo_paquete'] ?? '') === 'Suministro e Instalación', 'Pinturas no conserva MO + SI activo');

    $paintingMoItems = (int) lpcScalar(
        $db,
        'SELECT COUNT(*)
         FROM general_pdc_family_contract_option_items
         WHERE option_id = ? AND tipo_paquete = ?',
        [(int) ($paintingOption['id'] ?? 0), 'Mano de Obra'],
    );
    lpcAssert($paintingMoItems === 0, 'Pinturas no tiene item activo de Mano de Obra separado');

    $reviewFamilies = (int) lpcScalar(
        $db,
        'SELECT COUNT(*) FROM general_pdc_familias
         WHERE COALESCE(activa, 1) = 1 AND COALESCE(siempre_revision, 0) = 1',
    );
    lpcAssert($reviewFamilies === 0, 'no quedan familias ambiguas protegidas globalmente despues de aprobacion humana');

    foreach ([
        'ASEO' => ['activa' => 1, 'siempre_revision' => 0],
        'RED_TELECOMUNICACIONES' => ['activa' => 1, 'siempre_revision' => 0],
        'SEGURIDAD_CONTROL' => ['activa' => 1, 'siempre_revision' => 0],
        'DOTACION_ZONAS_COMUNES' => ['activa' => 1, 'siempre_revision' => 0],
        'CAMPAMENTO' => ['activa' => 0, 'siempre_revision' => 0],
        'BOTADA_ESCOMBROS' => ['activa' => 0, 'siempre_revision' => 0],
        'AMENIDADES_CUBIERTA' => ['activa' => 0, 'siempre_revision' => 0],
    ] as $code => $expected) {
        $row = $db->query(
            'SELECT activa, siempre_revision FROM general_pdc_familias WHERE codigo = ?',
            [$code],
        )->fetch(PDO::FETCH_ASSOC);
        lpcAssert($row !== false, "{$code} existe en catalogo");
        lpcAssert((int) ($row['activa'] ?? -1) === $expected['activa'], "{$code} activa={$expected['activa']}");
        lpcAssert((int) ($row['siempre_revision'] ?? -1) === $expected['siempre_revision'], "{$code} revision={$expected['siempre_revision']}");
    }
} catch (Throwable $e) {
    lpcFail($e->getMessage());
} finally {
    $db->query('DELETE FROM semi_auto_feedback WHERE notes = ?', [$feedbackNote]);
}

echo "=== Learning persistence in catalog DB: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
