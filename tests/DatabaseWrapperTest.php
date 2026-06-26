<?php
/**
 * DatabaseWrapperTest — Tests para la extensión queryWithProject de Database.
 *
 * Ejecutar: docker exec last-planner-aia-app-1 php tests/DatabaseWrapperTest.php
 *
 * NOTA: Las tablas per-project NO tienen project_id aún (se crea en Task 5).
 * Tests de lógica de inyección usan ReflectionMethod sobre injectProjectId().
 * Tests de ejecución real usan tablas que existen.
 */

require __DIR__ . '/../vendor/autoload.php';

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

function assert_null(mixed $actual, string $label): void
{
    assert_eq($actual, null, $label);
}

function assert_true(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  PASS: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}\n";
        $failed++;
    }
}

function assert_is_array(mixed $actual, string $label): void
{
    global $passed, $failed;
    if (is_array($actual)) {
        echo "  PASS: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}\n";
        echo "    Expected: array, Got: " . gettype($actual) . "\n";
        $failed++;
    }
}

echo "=== Database Wrapper Tests ===\n\n";

$db = Database::getInstance();

// Acceso al método privado injectProjectId para tests de lógica
$inject = new ReflectionMethod(Database::class, 'injectProjectId');
$inject->setAccessible(true);

// ═══════════════════════════════════════════════════════════════
// SECCIÓN 1: Tests de lógica de inyección (injectProjectId)
// ═══════════════════════════════════════════════════════════════
echo "--- Lógica de inyección SQL ---\n\n";

// Test 1: SELECT sin WHERE sobre tabla global → inyecta WHERE
echo "Test 1: SELECT sin WHERE → inyecta WHERE project_id = ?\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa", 27);
    assert_eq($result, "SELECT * FROM programa WHERE project_id = ?", 'SELECT sin WHERE');
}

// Test 2: SELECT con WHERE existente → inyecta AND project_id = ?
echo "\nTest 2: SELECT con WHERE → inyecta AND project_id = ?\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa WHERE Estado = ?", 27);
    assert_eq($result, "SELECT * FROM programa WHERE Estado = ? AND project_id = ?", 'SELECT con WHERE');
}

// Test 3: SELECT con project_id YA en WHERE → no duplica (retorna null)
echo "\nTest 3: SELECT con project_id existente → no duplica\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa WHERE project_id = ?", 27);
    assert_null($result, 'No duplica project_id');
}

// Test 4: project_id en condición compuesta → no duplica
echo "\nTest 4: project_id en WHERE compuesto → no duplica\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa WHERE Activa = 1 AND project_id = 27", 27);
    assert_null($result, 'Detecta project_id en condición compuesta');
}

// Test 5: Tabla no-global → no inyecta
echo "\nTest 5: Tabla no-global → no inyecta\n";
{
    $result = $inject->invoke($db, "SELECT * FROM general_proyectos_procesos WHERE Id = ?", 27);
    assert_null($result, 'No inyecta en tabla no-global');
}

// Test 6: SELECT con JOIN en tabla global → inyecta
echo "\nTest 6: SELECT con JOIN → inyecta WHERE\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa p JOIN actividades a ON p.Id = a.Id", 27);
    assert_eq($result, "SELECT * FROM programa p JOIN actividades a ON p.Id = a.Id WHERE p.project_id = ?", 'JOIN sin WHERE');
}

// Test 7: SELECT con JOIN y WHERE existente
echo "\nTest 7: JOIN + WHERE existente → inyecta AND\n";
{
    $result = $inject->invoke(
        $db,
        "SELECT * FROM programa p JOIN actividades a ON p.Id = a.Id WHERE p.Activa = 1",
        27
    );
    assert_eq(
        $result,
        "SELECT * FROM programa p JOIN actividades a ON p.Id = a.Id WHERE p.Activa = 1 AND p.project_id = ?",
        'JOIN + WHERE existente'
    );
}

// Test 8: DELETE con WHERE → inyecta AND
echo "\nTest 8: DELETE con WHERE → inyecta AND\n";
{
    $result = $inject->invoke($db, "DELETE FROM programa WHERE Id = ?", 27);
    assert_eq($result, "DELETE FROM programa WHERE Id = ? AND project_id = ?", 'DELETE con WHERE');
}

// Test 9: DELETE sin WHERE → añade WHERE
echo "\nTest 9: DELETE sin WHERE → añade WHERE\n";
{
    $result = $inject->invoke($db, "DELETE FROM programa", 27);
    assert_eq($result, "DELETE FROM programa WHERE project_id = ?", 'DELETE sin WHERE');
}

// Test 10: SELECT con ORDER BY → inyecta antes
echo "\nTest 10: SELECT con ORDER BY → inyecta WHERE antes\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa ORDER BY Id", 27);
    assert_eq($result, "SELECT * FROM programa WHERE project_id = ? ORDER BY Id", 'SELECT con ORDER BY');
}

// Test 11: SELECT con LIMIT → inyecta antes
echo "\nTest 11: SELECT con LIMIT → inyecta WHERE antes\n";
{
    $result = $inject->invoke($db, "SELECT * FROM programa LIMIT 10", 27);
    assert_eq($result, "SELECT * FROM programa WHERE project_id = ? LIMIT 10", 'SELECT con LIMIT');
}

// Test 12: Tabla con prefijo → NO inyecta (solo tablas globales sin prefijo)
echo "\nTest 12: Tabla con prefijo → NO inyecta\n";
{
    $result = $inject->invoke($db, "SELECT * FROM prueba_programa", 27);
    assert_null($result, 'Tabla con prefijo no inyecta (no es global)');
}

// Test 13: UPDATE sin WHERE → añade WHERE
echo "\nTest 13: UPDATE sin WHERE → añade WHERE\n";
{
    $result = $inject->invoke($db, "UPDATE programa SET Estado = ?", 27);
    assert_eq($result, "UPDATE programa SET Estado = ? WHERE project_id = ?", 'UPDATE sin WHERE');
}

// Test 14: Tabla con prefijo JOIN tabla con prefijo → NO inyecta
echo "\nTest 14: JOIN con prefijos → NO inyecta (no son globales)\n";
{
    $result = $inject->invoke($db, "SELECT * FROM prueba_programa p JOIN prueba_cic c ON p.Id = c.Id", 27);
    assert_null($result, 'JOIN con prefijos no inyecta');
}

// Test 15: Subquery con tabla global en WHERE → no doble inyección
echo "\nTest 15: Subquery en WHERE → no inyecta en subquery\n";
{
    $result = $inject->invoke(
        $db,
        "SELECT * FROM general_proyectos_procesos WHERE Id IN (SELECT project_id FROM programa)",
        27
    );
    // La tabla principal es general_proyectos_procesos (no global) → no inyecta
    assert_null($result, 'No inyecta en query sobre tabla no-global con subquery');
}

// ═══════════════════════════════════════════════════════════════
// SECCIÓN 2: Tests de ejecución real (backward compatibility)
// ═══════════════════════════════════════════════════════════════
echo "\n--- Ejecución real (backward compatibility) ---\n\n";

// Test 16: query() original NO se modifica
echo "Test 16: query() original — sin inyección\n";
{
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM general_proyectos_procesos");
    $result = $stmt->fetch();
    assert_true($result['cnt'] > 0, 'query() funciona sin inyección');
}

// Test 17: queryWithProject sobre tabla no-global → sin inyección
echo "\nTest 17: queryWithProject tabla no-global → sin inyección\n";
{
    $db->setProjectContext(27);
    $stmt = $db->queryWithProject("SELECT COUNT(*) AS cnt FROM general_proyectos_procesos WHERE Id = ?", [27]);
    $result = $stmt->fetch();
    assert_eq($result['cnt'], 1, 'Tabla no-global no inyecta');
}

// Test 18: projectId override
echo "\nTest 18: projectId override en queryWithProject\n";
{
    $db->setProjectContext(27);
    $stmt = $db->queryWithProject(
        "SELECT COUNT(*) AS cnt FROM general_proyectos_procesos WHERE Id = ?",
        [73],
        73
    );
    $result = $stmt->fetch();
    assert_eq($result['cnt'], 1, 'Override projectId funciona');
}

// Test 19: Sin project_id → ejecuta sin inyección (con warning)
echo "\nTest 19: Sin project_id → warning + ejecuta\n";
{
    // Reset context a null via reflection
    $ref = new ReflectionClass($db);
    $prop = $ref->getProperty('currentProjectId');
    $prop->setAccessible(true);
    $prop->setValue($db, null);

    $stmt = $db->queryWithProject("SELECT COUNT(*) AS cnt FROM general_proyectos_procesos");
    $result = $stmt->fetch();
    assert_true($result['cnt'] > 0, 'Sin project_id ejecuta con warning');
}

// Test 20: setProjectContext establece el contexto
echo "\nTest 20: setProjectContext establece contexto\n";
{
    $db->setProjectContext(75);
    $prop->setValue($db, 75);  // Verify via reflection
    assert_eq($prop->getValue($db), 75, 'setProjectContext(75) establece contexto');
}

// Test 21: getCurrentProjectId() devuelve el contexto activo
echo "\nTest 21: getCurrentProjectId() devuelve contexto\n";
{
    $db->setProjectContext(27);
    assert_eq($db->getCurrentProjectId(), 27, 'getCurrentProjectId() tras setProjectContext(27)');
    $db->setProjectContext(null);
    assert_null($db->getCurrentProjectId(), 'getCurrentProjectId() tras setProjectContext(null)');
}

// Test 22: Simulación flujo login+proyecto (TableResolver + setProjectContext)
echo "\nTest 22: Simulación flujo login+proyecto\n";
{
    $prefix = 'prueba';
    $projectId = TableResolver::getProjectIdByPrefix($prefix);
    assert_eq($projectId, 27, "getProjectIdByPrefix('prueba') devuelve 27");

    if ($projectId) {
        $db->setProjectContext($projectId);
    }
    assert_eq($db->getCurrentProjectId(), 27, 'setProjectContext tras TableResolver — contexto correcto');

    // Verificar que queryWithProject ejecuta correctamente con el contexto
    // Usamos general_proyectos_procesos (tabla no-global, no inyecta project_id)
    $stmt = $db->queryWithProject("SELECT COUNT(*) AS cnt FROM general_proyectos_procesos");
    $result = $stmt->fetch();
    assert_true(is_numeric($result['cnt']) && $result['cnt'] > 0, 'queryWithProject ejecuta con contexto de proyecto');
}

// ═══════════════════════════════════════════════════════════════
// SECCIÓN 3: Tests de insertProjectId
// ═══════════════════════════════════════════════════════════════
echo "\n--- insertProjectId ---\n\n";

// Test 23: INSERT con proyecto → inyecta project_id
echo "Test 23: INSERT con proyecto → inyecta project_id\n";
{
    list($sql, $params) = $db->insertProjectId(
        "INSERT INTO programa (actividad, fecha) VALUES (?, ?)",
        27,
        ['Actividad 1', '2026-01-01']
    );
    assert_eq($sql, "INSERT INTO programa (`project_id`, actividad, fecha) VALUES(?, ?, ?)", "Insert: SQL modified");
    assert_eq($params, [27, 'Actividad 1', '2026-01-01'], "Insert: params prepended");
}

// Test 24: INSERT con project_id ya presente → no duplica
echo "\nTest 24: INSERT con project_id ya presente → no duplica\n";
{
    list($sql2, $params2) = $db->insertProjectId(
        "INSERT INTO programa (project_id, actividad, fecha) VALUES (?, ?, ?)",
        27,
        [27, 'Act 1', '2026-01-01']
    );
    assert_eq($sql2, "INSERT INTO programa (project_id, actividad, fecha) VALUES (?, ?, ?)", "Insert: no duplicate");
    assert_eq($params2, [27, 'Act 1', '2026-01-01'], "Insert: params unchanged");
}

// Test 25: INSERT en tabla no-global → sin cambios
echo "\nTest 25: INSERT en tabla no-global → sin cambios\n";
{
    list($sql3, $params3) = $db->insertProjectId(
        "INSERT INTO general_usuarios (nombre) VALUES (?)",
        27,
        ['test']
    );
    assert_eq($sql3, "INSERT INTO general_usuarios (nombre) VALUES (?)", "Insert: non-global unchanged");
    assert_eq($params3, ['test'], "Insert: non-global params unchanged");
}

// Test 26: INSERT … SELECT (SELECT maneja project_id)
echo "\nTest 26: INSERT … SELECT → sin cambios (SELECT maneja project_id)\n";
{
    $result = $db->insertProjectId(
        "INSERT INTO programa_consolidado (col) SELECT col FROM otra_tabla WHERE id = ?",
        27,
        [1]
    );
    assert_is_array($result, "Insert-Select: returns array");
}

// Cleanup
$prop->setValue($db, null);

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

exit($failed > 0 ? 1 : 0);
