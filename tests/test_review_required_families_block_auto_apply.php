<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$failed = 0;

function rrfPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function rrfFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function rrfAssert(bool $condition, string $message): void
{
    $condition ? rrfPass($message) : rrfFail($message);
}

function rrfSuggestion(string $code, bool $preselected = true): array
{
    return [
        'action' => 'create_activity',
        'preselected' => $preselected,
        'analysis_payload' => [
            'technical' => ['familia_codigo' => $code],
            'quality_gate' => [
                'status' => 'ready',
                'definition_status' => 'lista',
                'score' => 92,
                'review_reasons' => [],
            ],
        ],
        'apply_payload' => [
            '_analysis' => [
                'technical' => ['familia_codigo' => $code],
                'quality_gate' => [
                    'status' => 'ready',
                    'definition_status' => 'lista',
                    'score' => 92,
                    'review_reasons' => [],
                ],
            ],
        ],
    ];
}

echo "=== Conditional review for Telecomunicaciones / Seguridad y Control ===\n";

try {
    $db = Database::getInstance();

    $pending = (int) $db->query(
        'SELECT COUNT(*) FROM general_pdc_familias
         WHERE COALESCE(activa, 1) = 1
           AND COALESCE(siempre_revision, 0) = 1',
    )->fetchColumn();
    rrfAssert($pending === 0, 'no quedan familias activas con revision obligatoria global');

    $service = new SemiAutoService($db);
    $method = (new ReflectionClass($service))->getMethod('reviewCoPresentListadoFamilies');
    $method->setAccessible(true);

    $single = $method->invoke($service, [
        rrfSuggestion('RED_TELECOMUNICACIONES'),
        rrfSuggestion('RED_ELECTRICA'),
    ]);
    rrfAssert($single[0]['preselected'] === true, 'Telecomunicaciones sola puede seguir lista');
    rrfAssert(($single[0]['analysis_payload']['quality_gate']['status'] ?? '') === 'ready', 'Telecomunicaciones sola no baja a revision');

    $combined = $method->invoke($service, [
        rrfSuggestion('RED_TELECOMUNICACIONES'),
        rrfSuggestion('SEGURIDAD_CONTROL'),
        rrfSuggestion('RED_ELECTRICA'),
    ]);

    foreach ([0 => 'RED_TELECOMUNICACIONES', 1 => 'SEGURIDAD_CONTROL'] as $index => $code) {
        $suggestion = $combined[$index];
        rrfAssert($suggestion['preselected'] === false, "{$code} no queda preseleccionada cuando aparecen ambas");
        rrfAssert(($suggestion['analysis_payload']['quality_gate']['status'] ?? '') === 'review', "{$code} baja a revision humana");
        rrfAssert(
            in_array(
                'El programa contiene Telecomunicaciones y Seguridad y Control; confirmar separación de alcance antes de aplicar.',
                $suggestion['analysis_payload']['quality_gate']['review_reasons'] ?? [],
                true,
            ),
            "{$code} explica la razon de revision",
        );
    }
    rrfAssert($combined[2]['preselected'] === true, 'familias no relacionadas no se bloquean');
} catch (Throwable $e) {
    rrfFail($e->getMessage());
}

echo "=== Conditional review for Telecomunicaciones / Seguridad y Control: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
