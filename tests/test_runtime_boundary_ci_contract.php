<?php

declare(strict_types=1);

// @requiere: puro

$checks = 0;
$failures = [];

$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

/** @return list<string> */
function workflowSteps(string $workflow): array
{
    $steps = [];
    $current = [];
    foreach (explode("\n", $workflow) as $line) {
        if (str_starts_with($line, '      - ')) {
            if ($current !== []) {
                $steps[] = $current;
            }
            $current = [$line];
            continue;
        }
        if ($current !== []) {
            $current[] = $line;
        }
    }
    if ($current !== []) {
        $steps[] = $current;
    }

    return array_map(static fn (array $step): string => implode("\n", $step), $steps);
}

$workflow = file_get_contents(__DIR__ . '/../.github/workflows/ci.yml');
$check(is_string($workflow), 'el workflow de CI debe poder leerse');

if (is_string($workflow)) {
    $steps = workflowSteps($workflow);
    $runtimeGrants = array_values(array_filter(
        $steps,
        static fn (string $step): bool => str_contains($step, 'id: runtime-grants'),
    ));
    $phpSuiteIndex = array_search(true, array_map(
        static fn (string $step): bool => str_contains($step, 'id: php-suite'),
        $steps,
    ), true);
    $runtimeGrantsIndex = array_search(true, array_map(
        static fn (string $step): bool => str_contains($step, 'id: runtime-grants'),
        $steps,
    ), true);

    $check(count($runtimeGrants) === 1, 'CI debe declarar una única atestación runtime-grants');
    $check(
        is_int($runtimeGrantsIndex) && is_int($phpSuiteIndex) && $runtimeGrantsIndex < $phpSuiteIndex,
        'la atestación runtime-grants debe ocurrir antes de php-suite',
    );

    $runtimeStep = $runtimeGrants[0] ?? '';
    $expectedRuntimeCommand = 'exec -T app php scripts/security/audit-runtime-db-grants.php --live';
    $check(str_contains($runtimeStep, $expectedRuntimeCommand), 'runtime-grants debe ejecutar la auditoría live con la cuenta runtime');
    $check(str_contains($runtimeStep, 'continue-on-error: true'), 'runtime-grants debe continuar para que el resumen agregue su resultado');
    $check(
        !preg_match('/root|CI_DB_ADMIN_PASS|-e\s+DB_(?:USER|PASS)|LPS_ADMIN_DB_LANE/', $runtimeStep),
        'runtime-grants no debe recibir credenciales administrativas',
    );

    $adminSteps = array_values(array_filter(
        $steps,
        static fn (string $step): bool => str_contains($step, 'id: php-admin-db'),
    ));
    $check(count($adminSteps) === 1, 'CI debe declarar una única lane php-admin-db');
    $adminStep = $adminSteps[0] ?? '';
    $check(
        preg_match('/DB_USER:\s*root/', $adminStep) === 1
        && preg_match('/DB_PASS:\s*ci-admin-only-password/', $adminStep) === 1
        && preg_match('/LPS_ADMIN_DB_LANE:\s*["\']1["\']/', $adminStep) === 1,
        'las variables administrativas deben pertenecer a php-admin-db',
    );
    $check(
        str_contains($adminStep, 'exec -T -e DB_USER -e DB_PASS -e LPS_ADMIN_DB_LANE app php scripts/run-php-tests.php --nivel=admin-db'),
        'php-admin-db debe propagar sus variables sólo al exec administrativo',
    );
    foreach ($steps as $step) {
        if ($step === $adminStep) {
            continue;
        }
        $check(
            preg_match('/^\s*(?:DB_USER|DB_PASS|LPS_ADMIN_DB_LANE):/m', $step) !== 1,
            'ningún otro step puede exportar variables administrativas',
        );
    }

    $check(
        preg_match('/G_RUNTIME_GRANTS:\s*\$\{\{ steps\.runtime-grants\.outcome \}\}/', $workflow) === 1,
        'el resumen debe recibir el outcome de runtime-grants',
    );
    $check(
        preg_match('/for outcome in[\s\S]*\$G_RUNTIME_GRANTS/', $workflow) === 1,
        'el acumulador failed debe incluir G_RUNTIME_GRANTS',
    );
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "Runtime boundary CI contract: {$checks} checks\n";
