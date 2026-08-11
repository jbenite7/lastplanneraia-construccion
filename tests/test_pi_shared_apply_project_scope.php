<?php
// @requiere: puro


$source = file_get_contents(__DIR__ . '/../src/Controllers/Programacion/ProgramacionIntermediaController.php');
$failures = [];

$checks = [
    'resolve project id' => '$projectId = $this->db->getCurrentProjectId()',
    'scope shared constraint insert id' => '(project_id, Id, Semana, Restriccion, ValorObjetivo, Nota, CreadoPor)',
    'scope shared link insert id' => '(project_id, Id, SharedConstraintId, Semana, unique_id, ConsecutivoEnPrograma, ValorAplicado)',
    'reserve shared constraint id' => '$nextSharedId',
    'reserve shared link id' => '$nextLinkId',
    'scope batch update' => 'WHERE project_id = ? AND unique_id = ? AND Semana = ? AND Titulo = 0',
    'bind project id on update' => '$updateParams[] = $projectId;',
];

foreach ($checks as $label => $needle) {
    if (!str_contains((string) $source, $needle)) {
        $failures[] = $label;
    }
}

if ($failures !== []) {
    echo "=== PI Shared Apply Project Scope: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "=== PI Shared Apply Project Scope: OK ===\n";
