<?php

declare(strict_types=1);
// @requiere: db

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\DataScope\ProjectScope;

echo "=== Test Programa General Update Assignments Contract ===\n";

$db = Database::getInstance();
$scope = new ProjectScope(1, 'test.A', 'A');
$db->dataScope()->bind($scope);

// Verificar que las columnas existan en la tabla programa_consolidado
$stmt = $db->query("SELECT Responsable_AIA, Sub_Contratista FROM programa_consolidado LIMIT 0");
if ($stmt->columnCount() !== 2) {
    echo "FAIL: programa_consolidado does not expose Responsable_AIA and Sub_Contratista\n";
    exit(1);
}

// Simular payload con asignaciones opcionales
$testResponsable = 'Ing. Carlos Restrepo';
$testSubcontratista = 'Excavaciones del Norte S.A.S.';

// Validar que el SQL de update admita ambos campos
$sql = "UPDATE programa_consolidado SET Responsable_AIA = ?, Sub_Contratista = ? WHERE project_id = ? AND unique_id = ? AND Semana = ?";
$stmtUpdate = $db->query($sql, [$testResponsable, $testSubcontratista, 1, 999999, 33]);

echo "PASS: Update assignments contract verified successfully.\n";
exit(0);
