<?php

/**
 * Smoke test: Auto-Definir Contratos
 *
 * Verifies:
 *   1. ActivityMatcher returns confidence as float in matchActivity()
 *   2. ContratosApiController auto-define methods exist (structural)
 *   3. New columns exist in actividades table
 *   4. Auto contrato log table structure
 *   5. RBAC permission exists
 *
 * Usage: docker compose exec app php tests/test_auto_definir_contratos.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$passed = 0;
$failed = 0;
$skipped = 0;

function pass(string $test_name): void
{
    global $passed;
    echo "  \u{2705} PASS: $test_name\n";
    $passed++;
}

function fail(string $test_name, string $detail = ''): void
{
    global $failed;
    $msg = $detail ? " \u{2014} $detail" : '';
    echo "  \u{274C} FAIL: $test_name$msg\n";
    $failed++;
}

function skip(string $reason): void
{
    global $skipped;
    echo "  \u{23ED}\u{FE0F}  SKIP: $reason\n";
    $skipped++;
}

echo "=== Smoke Test: Auto-Definir Contratos ===\n\n";

$db = Database::getInstance();
$actividadesTable = 'actividades';
$logTable = 'auto_contrato_log';

// ─────────────────────────────────────────────
// Test 1: ActivityMatcher confidence as float
// ─────────────────────────────────────────────
echo "--- Test 1: ActivityMatcher confidence ---\n";

require_once __DIR__ . '/../src/Support/ActivityMatcher.php';

$matcher = new \App\Support\ActivityMatcher();
$rules = $matcher->loadRules();

if (empty($rules)) {
    skip('No matching rules found in general_pdc_activity_rules');
} else {
    echo "  (Found " . count($rules) . " rules)\n";

    // Try a sample PG activity
    $testActivity = ['Actividad' => 'PRELIMINARES - CAMPAMENTO'];
    $result = $matcher->matchActivity($testActivity, $rules);

    if ($result === null) {
        skip('Sample activity did not match any rule (data-dependent)');
    } else {
        pass('matchActivity() returned a match result');

        // Check confidence key exists
        if (isset($result['confidence'])) {
            pass('matchActivity() result contains confidence key');

            $confidence = $result['confidence'];

            if (is_float($confidence)) {
                pass('confidence is type float');
            } elseif (is_int($confidence)) {
                fail('confidence is int, expected float', 'Value: ' . $confidence);
            } elseif (is_numeric($confidence)) {
                fail('confidence is numeric but not float', 'Type: ' . gettype($confidence) . ' Value: ' . $confidence);
            } else {
                fail('confidence is not numeric', 'Type: ' . gettype($confidence));
            }

            if ($confidence >= 0 && $confidence <= 100) {
                pass('confidence is in range [0, 100]');
            } else {
                fail('confidence out of range', 'Value: ' . $confidence);
            }
        } else {
            fail('matchActivity() result missing confidence key', 'Keys: ' . implode(', ', array_keys($result)));
        }
    }
}

// ─────────────────────────────────────────────
// Test 2: Method existence (structural)
// ─────────────────────────────────────────────
echo "--- Test 2: ContratosApiController method structure ---\n";

$controllerClass = 'App\\Controllers\\Api\\ContratosApiController';

if (class_exists($controllerClass)) {
    pass('ContratosApiController class exists');

    $expectedMethods = ['autoDefine', 'autoDefineApply', 'autoDefineReanalyze', 'autoDefineUndo'];
    foreach ($expectedMethods as $method) {
        if (method_exists($controllerClass, $method)) {
            pass("Method $method() exists");
        } else {
            fail("Method $method() does not exist");
        }
    }
} else {
    fail('ContratosApiController class not found', 'Autoloader may be misconfigured');
}

// ─────────────────────────────────────────────
// Test 3: New columns in actividades table
// ─────────────────────────────────────────────
echo "--- Test 3: Nuevas columnas en actividades ---\n";

try {
    $stmt = $db->query("DESCRIBE {$actividadesTable}");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }

    $newColumns = ['numeroSubcontratos', 'confianza_deteccion', 'ultimo_auto_definir', 'fechaInicioProyectada'];
    foreach ($newColumns as $col) {
        if (isset($columns[$col])) {
            pass("Column '$col' exists");
        } else {
            fail("Column '$col' does not exist", 'Table is missing this column');
        }
    }
} catch (\Throwable $e) {
    fail('Could not describe actividades table', $e->getMessage());
}

// ─────────────────────────────────────────────
// Test 4: Auto contrato log table
// ─────────────────────────────────────────────
echo "--- Test 4: Auto contrato log table ---\n";

$stmt = $db->query("SHOW TABLES LIKE '$logTable'");
$logTableExists = $stmt->fetch() !== false;

if (!$logTableExists) {
    skip("$logTable table does not exist (created at runtime by ensureAutoContratoLogTable())");
} else {
    pass("$logTable table exists");

    $stmt = $db->query("DESCRIBE $logTable");
    $logCols = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $logCols[$row['Field']] = $row;
    }

    $expectedLogCols = ['id', 'semana', 'Id_actividad', 'accion', 'batch_id', 'creado_en'];
    foreach ($expectedLogCols as $col) {
        if (isset($logCols[$col])) {
            pass("Log column '$col' exists");
        } else {
            fail("Log column '$col' does not exist");
        }
    }
}

// ─────────────────────────────────────────────
// Test 5: RBAC permission
// ─────────────────────────────────────────────
echo "--- Test 5: RBAC permission ---\n";

try {
    $stmt = $db->query(
        "SELECT COUNT(*) as cnt FROM rbac_permissions WHERE permission_key = 'lps.contratos.auto_definir'"
    );
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cnt = (int) ($result['cnt'] ?? 0);

    if ($cnt > 0) {
        pass("lps.contratos.auto_definir permission exists in DB ($cnt row(s))");
    } else {
        fail('lps.contratos.auto_definir permission not found in rbac_permissions');
    }
} catch (\Throwable $e) {
    fail('Could not query rbac_permissions table', $e->getMessage());
}

// ─────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────
echo "\n=== Results: $passed passed, $failed failed, $skipped skipped ===\n";
exit($failed > 0 ? 1 : 0);
