<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;

function adminCatalogPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function adminCatalogFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function adminCatalogAssert(bool $condition, string $message): void
{
    $condition ? adminCatalogPass($message) : adminCatalogFail($message);
}

echo "=== Admin family catalog CRUD ===\n";

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/admin/public/index.php') ?: '';
$controllerFile = $root . '/admin/src/Controllers/FamilyCatalogController.php';
$viewFile = $root . '/admin/views/pages/matching/family_catalog.php';
$layout = file_get_contents($root . '/admin/views/layouts/main.php') ?: '';

foreach ([
    "/matching/family-catalog",
    "/matching/family-catalog/rule",
    "/matching/family-catalog/approve",
    "/matching/family-catalog/resolve-decision",
    "/matching/family-catalog/import",
    "/matching/family-catalog/export",
] as $route) {
    adminCatalogAssert(str_contains($routes, $route), "ruta admin {$route} registrada");
}
adminCatalogAssert(file_exists($controllerFile), 'controlador admin del catálogo existe');
adminCatalogAssert(file_exists($viewFile), 'vista admin del catálogo existe');

if (file_exists($controllerFile)) {
    $controller = file_get_contents($controllerFile) ?: '';
    foreach (['index', 'saveFamily', 'saveAlias', 'saveContractualElement', 'saveRuleAssignment', 'approveCatalogItem', 'resolvePendingDecision', 'importCatalog', 'exportCatalog'] as $method) {
        adminCatalogAssert(str_contains($controller, "function {$method}("), "controlador implementa {$method}");
    }
    adminCatalogAssert(str_contains($controller, 'requireAdminRole'), 'controlador restringe a administradores');
    adminCatalogAssert(str_contains($controller, 'validateCsrfToken'), 'controlador valida CSRF en guardados');
    adminCatalogAssert(str_contains($controller, 'general_pdc_family_aliases'), 'controlador mantiene aliases');
    adminCatalogAssert(str_contains($controller, 'general_pdc_contractual_elements'), 'controlador mantiene elementos contractuales');
    adminCatalogAssert(str_contains($controller, 'general_pdc_family_rule_audit'), 'controlador audita reasignación de reglas');
    adminCatalogAssert(str_contains($controller, 'Content-Disposition: attachment'), 'controlador exporta CSV');
    adminCatalogAssert(str_contains($controller, 'str_getcsv'), 'controlador importa CSV');
    adminCatalogAssert(str_contains($controller, 'logActivity'), 'controlador registra auditoría');
    adminCatalogAssert(str_contains($controller, 'activeFamilyConflictMessage'), 'controlador valida conflictos antes de activar familias');
    adminCatalogAssert(str_contains($controller, 'pendingDecisions'), 'controlador expone decisiones pendientes');
}

if (file_exists($viewFile)) {
    $view = file_get_contents($viewFile) ?: '';
    foreach (['Familias operativas', 'Aliases', 'Elementos contractuales', 'Decisiones pendientes', 'Mantener en Listado', 'Pasar a Contratos', 'Impacto', 'Auditoría', 'Exportar catálogo', 'Importar catálogo', 'Reglas de detección'] as $label) {
        adminCatalogAssert(str_contains($view, $label), "vista muestra {$label}");
    }
    adminCatalogAssert(str_contains($view, 'Define si estas filas siguen en Listado o pasan a Contratos.'), 'vista explica decision Listado vs Contratos');
    foreach (['/admin/matching/family-catalog/family', '/admin/matching/family-catalog/alias', '/admin/matching/family-catalog/contractual', '/admin/matching/family-catalog/rule', '/admin/matching/family-catalog/approve', '/admin/matching/family-catalog/resolve-decision', '/admin/matching/family-catalog/import'] as $action) {
        adminCatalogAssert(str_contains($view, $action), "vista tiene formulario {$action}");
    }
    foreach (['type=families', 'type=aliases', 'type=contractual', 'type=rules'] as $export) {
        adminCatalogAssert(str_contains($view, $export), "vista permite exportar {$export}");
    }
}

adminCatalogAssert(str_contains($layout, '/admin/matching/family-catalog'), 'sidebar enlaza el catálogo');

try {
    $db = Database::getInstance();
    $reflection = new ReflectionClass(\Admin\Controllers\FamilyCatalogController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('activeFamilyConflictMessage');
    $method->setAccessible(true);

    $contractualConflict = (string) $method->invoke($controller, $db, 'Acero de Refuerzo y Estructural', 0);
    adminCatalogAssert(str_contains($contractualConflict, 'elemento contractual'), 'admin bloquea contractual como familia activa');

    $aliasConflict = (string) $method->invoke($controller, $db, 'Red RCI', 0);
    adminCatalogAssert(str_contains($aliasConflict, 'alias'), 'admin bloquea alias como familia activa');

    $noConflict = $method->invoke($controller, $db, 'Familia Operativa QA Sin Conflicto', 0);
    adminCatalogAssert($noConflict === null, 'admin permite familia operativa sin conflicto');

    $pending = $reflection->getMethod('pendingDecisions');
    $pending->setAccessible(true);
    $rows = $pending->invoke($controller, $db);
    $equipmentRows = array_values(array_filter($rows, static fn(array $row): bool => ($row['categoria'] ?? '') === 'EQUIPOS'));
    adminCatalogAssert($equipmentRows !== [], 'admin muestra EQUIPOS en decisiones pendientes');

    $code = 'QA_DECISION_TEMP_' . random_int(100000, 999999);
    $name = 'QA Decision Temporal ' . random_int(100000, 999999);
    $motivo = 'Prueba automatizada decision pendiente';
    $db->query('DELETE FROM general_pdc_contractual_elements WHERE nombre = ?', [$name]);
    $db->query('DELETE FROM general_pdc_familias WHERE codigo = ?', [$code]);
    $db->query(
        'INSERT INTO general_pdc_familias (codigo, nombre, categoria, orden, siempre_revision, activa)
         VALUES (?, ?, ?, ?, 1, 1)',
        [$code, $name, 'EQUIPOS', 9999],
    );
    $familyId = (int) $db->query('SELECT id FROM general_pdc_familias WHERE codigo = ?', [$code])->fetchColumn();
    $db->query(
        'INSERT INTO general_pdc_activity_rules (familia_id, patron_regex, modalidad_sugerida, confianza, prioridad, descripcion, activa)
         VALUES (?, ?, ?, ?, ?, ?, 1)',
        [$familyId, '/QA_DECISION_TEMP/u', 'Equipos', 90, 10, 'QA decision temporal'],
    );
    $ruleId = (int) $db->query('SELECT id FROM general_pdc_activity_rules WHERE familia_id = ? ORDER BY id DESC LIMIT 1', [$familyId])->fetchColumn();
    $family = ['id' => $familyId, 'codigo' => $code, 'nombre' => $name, 'categoria' => 'EQUIPOS'];

    $keep = $reflection->getMethod('keepPendingFamilyInListado');
    $keep->setAccessible(true);
    $keep->invoke($controller, $db, $family, $motivo);
    $kept = $db->query('SELECT activa, siempre_revision FROM general_pdc_familias WHERE id = ?', [$familyId])->fetch(PDO::FETCH_ASSOC);
    adminCatalogAssert((int) $kept['activa'] === 1 && (int) $kept['siempre_revision'] === 0, 'resolver mantiene familia en Listado y cierra revision');

    $db->query('UPDATE general_pdc_familias SET siempre_revision = 1, activa = 1 WHERE id = ?', [$familyId]);
    $move = $reflection->getMethod('movePendingFamilyToContracts');
    $move->setAccessible(true);
    $move->invoke($controller, $db, $family, 'Suministro', 'QA DECISION TEMPORAL', $motivo);
    $moved = $db->query('SELECT activa, siempre_revision FROM general_pdc_familias WHERE id = ?', [$familyId])->fetch(PDO::FETCH_ASSOC);
    $ruleActive = (int) $db->query('SELECT COALESCE(activa, 1) FROM general_pdc_activity_rules WHERE id = ?', [$ruleId])->fetchColumn();
    $contractual = (int) $db->query('SELECT COUNT(*) FROM general_pdc_contractual_elements WHERE nombre = ? AND paquete_nombre = ? AND activa = 1', [$name, 'QA DECISION TEMPORAL'])->fetchColumn();
    adminCatalogAssert((int) $moved['activa'] === 0 && (int) $moved['siempre_revision'] === 0, 'resolver pasa familia a Contratos e inactiva Listado');
    adminCatalogAssert($ruleActive === 0, 'resolver desactiva reglas de familia pasada a Contratos');
    adminCatalogAssert($contractual > 0, 'resolver crea elemento contractual activo');

    $db->query('DELETE FROM general_pdc_family_rule_audit WHERE motivo = ?', [$motivo]);
    $db->query('DELETE FROM general_pdc_activity_rules WHERE id = ?', [$ruleId]);
    $db->query('DELETE FROM general_pdc_contractual_elements WHERE nombre = ?', [$name]);
    $db->query('DELETE FROM general_pdc_familias WHERE id = ?', [$familyId]);
} catch (Throwable $e) {
    adminCatalogFail('validación runtime de catálogo admin falló: ' . $e->getMessage());
}

echo "=== Admin family catalog CRUD: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
