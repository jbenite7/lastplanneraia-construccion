<?php
/**
 * TableResolverTest — Tests unitarios para TableResolver.
 *
 * Ejecutar: docker exec last-planner-aia-app-1 php tests/TableResolverTest.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Cargar .env si vlucas/phpdotenv está disponible
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$passed = 0;
$failed = 0;

function assert_eq(mixed $actual, mixed $expected, string $label): void
{
    global $passed, $failed;
    if ($actual === $expected) {
        echo "  PASS: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}\n";
        echo "    Expected: " . var_export($expected, true) . "\n";
        echo "    Actual:   " . var_export($actual, true) . "\n";
        $failed++;
    }
}

function assert_true(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  PASS: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}\n";
        echo "    Expected: true, Got: " . var_export($condition, true) . "\n";
        $failed++;
    }
}

function assert_throws(callable $fn, string $label): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  FAIL: {$label} — no exception thrown\n";
        $failed++;
    } catch (InvalidArgumentException $e) {
        echo "  PASS: {$label} — threw: " . $e->getMessage() . "\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  FAIL: {$label} — unexpected exception: " . get_class($e) . ": " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "=== TableResolver Tests ===\n\n";

// --- Test 1: resolve() con flag OFF devuelve prueba_programa para projectId=27 ---
echo "Test 1: resolve() con flag OFF → prueba_programa\n";
TableResolver::setUseGlobalTablesForTest(false);
assert_eq(
    TableResolver::resolve(27, 'programa'),
    'prueba_programa',
    'resolve(27, "programa") with flag OFF'
);

// --- Test 2: resolve() con flag ON devuelve programa (sin prefijo) ---
echo "\nTest 2: resolve() con flag ON → programa\n";
TableResolver::setUseGlobalTablesForTest(true);
assert_eq(
    TableResolver::resolve(27, 'programa'),
    'programa',
    'resolve(27, "programa") with flag ON'
);
assert_eq(
    TableResolver::resolve(75, 'programacion_semanal'),
    'programacion_semanal',
    'resolve(75, "programacion_semanal") with flag ON'
);

// --- Test 3: resolve() con projectId inexistente → exception ---
echo "\nTest 3: resolve() con projectId inexistente → exception\n";
TableResolver::setUseGlobalTablesForTest(false);
assert_throws(
    fn () => TableResolver::resolve(999, 'programa'),
    'resolve(999, "programa") should throw'
);

// --- Test 4: resolve() con tableType inválido → exception ---
echo "\nTest 4: resolve() con tableType inválido → exception\n";
assert_throws(
    fn () => TableResolver::resolve(27, 'tabla_inexistente'),
    'resolve(27, "tabla_inexistente") should throw'
);

// --- Test 5: getProjectIdByPrefix('prueba') → 27 ---
echo "\nTest 5: getProjectIdByPrefix('prueba') → 27\n";
assert_eq(
    TableResolver::getProjectIdByPrefix('prueba'),
    27,
    'getProjectIdByPrefix("prueba")'
);

// --- Test adicional: getProjectIdByPrefix con prefijo inexistente ---
echo "\nTest 6: getProjectIdByPrefix('no_existe') → null\n";
assert_eq(
    TableResolver::getProjectIdByPrefix('no_existe'),
    null,
    'getProjectIdByPrefix("no_existe")'
);

// --- Test adicional: getValidTables() devuelve 16 tipos ---
echo "\nTest 7: getValidTables() → 16 tipos\n";
assert_eq(
    count(TableResolver::getValidTables()),
    20,
    'getValidTables() count'
);

// --- Test 8: resolveByPrefix con flag OFF → 'prueba_programa' ---
echo "\nTest 8: resolveByPrefix('prueba', 'programa') con flag OFF\n";
TableResolver::setUseGlobalTablesForTest(false);
assert_eq(
    TableResolver::resolveByPrefix('prueba', 'programa'),
    'prueba_programa',
    'resolveByPrefix("prueba", "programa") with flag OFF'
);

// --- Test 9: resolveByPrefix con flag ON → 'programa' (sin prefijo) ---
echo "\nTest 9: resolveByPrefix('prueba', 'programa') con flag ON\n";
TableResolver::setUseGlobalTablesForTest(true);
assert_eq(
    TableResolver::resolveByPrefix('prueba', 'programa'),
    'programa',
    'resolveByPrefix("prueba", "programa") with flag ON'
);
TableResolver::setUseGlobalTablesForTest(false);

// --- Test 10: Cache performance — 100 lookups en < 50ms ---
echo "\nTest 10: Cache performance — 100 lookups en < 50ms\n";
TableResolver::clearCache();
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    TableResolver::resolve(27, 'programa');
}
$elapsed = round((microtime(true) - $start) * 1000, 2);
assert_true($elapsed < 50, "Cache test: 100 lookups in {$elapsed}ms (expect <50ms)");

// --- Test 11: resolveByPrefix con tableType inválido → exception ---
echo "\nTest 11: resolveByPrefix con tableType inválido → exception\n";
assert_throws(
    fn () => TableResolver::resolveByPrefix('prueba', 'tabla_inexistente'),
    'resolveByPrefix("prueba", "tabla_inexistente") should throw'
);

// --- Resumen ---
echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

// Cleanup
TableResolver::setUseGlobalTablesForTest(false);

exit($failed > 0 ? 1 : 0);
