<?php

// Contrato del gate de análisis estático del PDC.
//
// El repo entero corre en nivel 5 con línea base (`phpstan.neon` + `phpstan-baseline.neon`); el
// módulo PDC, que es donde está el desarrollo activo, se exige en nivel 6 aparte
// (`phpstan-pdc.neon`) y SIN línea base. Este test no ejecuta PHPStan —eso lo hace
// `npm run test:pdc:phpstan`—: protege la configuración de erosionar en silencio.
//
// Lo que de verdad cubre: que un archivo nuevo del módulo no se quede fuera del gate. Las rutas de
// los controladores van enumeradas a mano en el .neon, así que sin esta comprobación un
// `PlanComprasLoQueSea.php` nuevo escaparía del nivel 6 sin que nadie se entere.

define('PROJECT_ROOT', dirname(__DIR__));

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $fails++;
    }
};

$pdcNeon = @file_get_contents(PROJECT_ROOT . '/phpstan-pdc.neon');
$globalNeon = @file_get_contents(PROJECT_ROOT . '/phpstan.neon');
$baseline = @file_get_contents(PROJECT_ROOT . '/phpstan-baseline.neon');

$check($pdcNeon !== false, 'existe phpstan-pdc.neon');
$check($globalNeon !== false, 'existe phpstan.neon');
$check($baseline !== false, 'existe phpstan-baseline.neon');

if ($pdcNeon === false || $globalNeon === false || $baseline === false) {
    echo "Gate PHPStan del PDC: FAIL ({$fails})\n";
    exit(1);
}

// --- Niveles ----------------------------------------------------------------------------------
$check((bool) preg_match('/^\s*level:\s*6\s*$/m', $pdcNeon), 'el gate del PDC exige nivel 6');
$check((bool) preg_match('/^\s*level:\s*5\s*$/m', $globalNeon), 'el gate global sigue en nivel 5');

// El gate del módulo tiene que ser independiente: si heredara `phpstan.neon` se traería el nivel 5
// y la línea base global, y dejaría de ser más estricto que el resto del repo.
$check(!str_contains($pdcNeon, 'phpstan-baseline.neon'), 'el gate del PDC no incluye la línea base global');

// --- Cobertura: ningún archivo del módulo puede quedar fuera --------------------------------
$archivos = array_merge(
    glob(PROJECT_ROOT . '/src/Services/Pdc/*.php') ?: [],
    glob(PROJECT_ROOT . '/src/Controllers/Api/PlanCompras*.php') ?: [],
);
$check($archivos !== [], 'se encontraron archivos del módulo PDC');

// Las rutas se comparan por LÍNEA COMPLETA, no con `str_contains` sobre el .neon entero: buscar
// «- src/Controllers/Api» como subcadena da verdadero por ser prefijo de
// «- src/Controllers/Api/PlanComprasApiController.php», y el guard no detectaría nada.
preg_match_all('/^\s*-\s*(\S+)\s*$/m', $pdcNeon, $m);
$rutasGate = $m[1] ?? [];

$cubierto = static function (string $ruta) use ($rutasGate): bool {
    $rel = str_replace(PROJECT_ROOT . '/', '', $ruta);
    foreach ($rutasGate as $r) {
        if ($rel === $r || str_starts_with($rel, rtrim($r, '/') . '/')) {
            return true;
        }
    }
    return false;
};

$fuera = array_values(array_filter($archivos, static fn (string $f): bool => !$cubierto($f)));
$check(
    $fuera === [],
    'todos los archivos del PDC están en el gate' . ($fuera === []
        ? ''
        : ' (fuera: ' . implode(', ', array_map(static fn ($f) => basename($f), $fuera)) . ')'),
);

// --- El módulo no arrastra deuda congelada ----------------------------------------------------
// Si aparece una entrada del PDC en la línea base global es que alguien congeló un hallazgo en vez
// de arreglarlo, y el módulo deja de ser la zona limpia que justifica exigirle más que al resto.
$check(
    !str_contains($baseline, 'src/Services/Pdc/') && !str_contains($baseline, 'PlanCompras'),
    'la línea base global no contiene ninguna entrada del PDC',
);

// --- El gate es ejecutable ---------------------------------------------------------------------
$pkg = json_decode((string) file_get_contents(PROJECT_ROOT . '/package.json'), true);
$check(
    isset($pkg['scripts']['test:pdc:phpstan'])
        && str_contains($pkg['scripts']['test:pdc:phpstan'], 'phpstan-pdc.neon'),
    'npm expone test:pdc:phpstan apuntando a phpstan-pdc.neon',
);

$workflow = (string) @file_get_contents(PROJECT_ROOT . '/.github/workflows/design-system.yml');
$check(str_contains($workflow, 'npm run test:pdc:phpstan'), 'CI ejecuta el gate del PDC');

echo $fails === 0 ? "Gate PHPStan del PDC: PASS\n" : "Gate PHPStan del PDC: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
