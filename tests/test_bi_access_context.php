<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\BiAccessComponent;

$failures = [];

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    global $failures;

    if ($expected !== $actual) {
        $failures[] = $label . '\nExpected: ' . var_export($expected, true)
            . '\nActual: ' . var_export($actual, true);
    }
}

$originalGet = $_GET;
$hadSession = array_key_exists('_SESSION', $GLOBALS);
$originalSession = $_SESSION ?? [];

$_GET = [
    'semana' => '99',
    'desde' => '2000-01-01',
    'theme' => 'light',
];
$_SESSION = [
    'project_id' => 999,
    'semana' => 98,
];

try {
    $filters = [
        'semana' => 12,
        'desde' => '2026-07-01',
        'hasta' => '2026-07-31',
        'sub' => 'Acme & Hijos/Ñ',
        'resp' => 'Ana María + QA',
        'etapa' => 'Planeación & ejecución',
        'theme' => 'dark mode/alto',
    ];

    $individual = BiAccessComponent::url('programa-general', $filters + ['project_id' => 17]);
    assertSameValue(
        '/bi/programa-general?project_id=17&semana=12&desde=2026-07-01&hasta=2026-07-31'
            . '&sub=Acme+%26+Hijos%2F%C3%91&resp=Ana+Mar%C3%ADa+%2B+QA'
            . '&etapa=Planeaci%C3%B3n+%26+ejecuci%C3%B3n&theme=dark+mode%2Falto',
        $individual,
        'individual project context preserves all filters with RFC1738 encoding',
    );

    $multi = BiAccessComponent::url('control-tower', $filters + [
        'project_id' => 17,
        'project_ids' => [73, 74],
    ]);
    assertSameValue(
        '/bi/control-tower?project_ids%5B0%5D=73&project_ids%5B1%5D=74&semana=12&desde=2026-07-01'
            . '&hasta=2026-07-31&sub=Acme+%26+Hijos%2F%C3%91&resp=Ana+Mar%C3%ADa+%2B+QA'
            . '&etapa=Planeaci%C3%B3n+%26+ejecuci%C3%B3n&theme=dark+mode%2Falto',
        $multi,
        'multi-project context preserves all filters with RFC1738 encoding',
    );

    parse_str((string) parse_url($multi, PHP_URL_QUERY), $multiQuery);
    assertSameValue(['73', '74'], $multiQuery['project_ids'] ?? null, 'multi-project IDs remain ordered');
    assertSameValue(false, array_key_exists('project_id', $multiQuery), 'multi-project URL omits session project ID');
    assertSameValue(12, (int) ($multiQuery['semana'] ?? 0), 'explicit week wins over session and query state');
    foreach (['desde', 'hasta', 'sub', 'resp', 'etapa', 'theme'] as $filter) {
        assertSameValue($filters[$filter], $multiQuery[$filter] ?? null, "multi-project preserves {$filter}");
    }

    $csvMulti = BiAccessComponent::url('control-tower', $filters + [
        'project_id' => 17,
        'project_ids' => '73,74',
    ]);
    parse_str((string) parse_url($csvMulti, PHP_URL_QUERY), $csvMultiQuery);
    assertSameValue(['73', '74'], $csvMultiQuery['project_ids'] ?? null, 'CSV project IDs preserve multi-project scope');
    assertSameValue(false, array_key_exists('project_id', $csvMultiQuery), 'CSV multi-project URL omits session project ID');
} finally {
    $_GET = $originalGet;
    if ($hadSession) {
        $_SESSION = $originalSession;
    } else {
        unset($_SESSION);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI access URL preserves explicit single and multi-project context without RBAC/session coupling\n";
