<?php

declare(strict_types=1);
// @requiere: db

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\DataScope\ProjectScope;
use App\Services\ProgramaGeneralContextService;

echo "=== Test Programa General Update Assignments Contract ===\n";

$db = Database::getInstance();
$scope = new ProjectScope(1, 'test.A', 'A');
$db->dataScope()->bind($scope);

// 1. Verificar que las columnas existan en la tabla programa_consolidado
$stmt = $db->query("SELECT Responsable_AIA, Sub_Contratista FROM programa_consolidado LIMIT 0");
if ($stmt->columnCount() !== 2) {
    echo "FAIL: programa_consolidado does not expose Responsable_AIA and Sub_Contratista\n";
    exit(1);
}

// 2. Simular payload con asignaciones opcionales y validar query parametrizado
$testResponsable = 'Ing. Carlos Restrepo';
$testSubcontratista = 'Excavaciones del Norte S.A.S.';

$sql = "UPDATE programa_consolidado SET Responsable_AIA = ?, Sub_Contratista = ? WHERE project_id = ? AND unique_id = ? AND Semana = ?";
$stmtUpdate = $db->query($sql, [$testResponsable, $testSubcontratista, 1, 999999, 33]);

// 3. Validar que la consulta a subcontratistas en MySQL real use columnas existentes (subcontratista, alcance, activo)
$subStmt = $db->query("SELECT Id as id, subcontratista as nombre, alcance as especialidad FROM subcontratistas WHERE project_id = ? AND activo = 1 ORDER BY subcontratista ASC", [1]);
if (!($subStmt instanceof PDOStatement)) {
    echo "FAIL: subcontratistas query failed against MySQL\n";
    exit(1);
}

// 4. Validar que la consulta a profesionales en MySQL real use columnas existentes (nombre, cargo, activo)
$profStmt = $db->query("SELECT id, nombre, cargo FROM profesionales WHERE project_id = ? AND activo = 1 ORDER BY nombre ASC", [1]);
if (!($profStmt instanceof PDOStatement)) {
    echo "FAIL: profesionales query failed against MySQL\n";
    exit(1);
}

// 5. Validar que ProgramaGeneralContextService instanciado con Database real ejecute build sin errores de columna
$contextService = new ProgramaGeneralContextService(
    db: $db,
    permissionResolver: fn($perm) => true,
    biResolver: fn($mod) => '/bi/' . $mod,
    csrfResolver: fn($form) => 'csrf_' . $form
);

$contextReal = $contextService->build($scope, ['usuario' => 'test.A']);
if (!isset($contextReal['catalogos']['profesionales']) || !isset($contextReal['catalogos']['subcontratistas'])) {
    echo "FAIL: catalogos missing from real context payload\n";
    exit(1);
}

echo "PASS: Update assignments contract and catalog queries verified successfully against MySQL.\n";
exit(0);
