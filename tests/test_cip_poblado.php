<?php
// @requiere: db
// Prueba: procesar una semana con responsables deja filas en `cip`.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/ScopeFixture.php';

// Database es una clase global (src/Core/Database.php no declara namespace),
// pese a lo que sugiere la documentacion del repo.
$db = Database::getInstance();
$projectId = (int) ($argv[1] ?? 68);

// El test entero mira una sola obra —la que le pasen o la 68— y sus dos consultas ya venían
// acotadas por ese project_id: el alcance solo declara lo que ya decían.
ScopeFixture::abrir($db, $projectId, 'test-cip-poblado');

$responsables = (int) $db->query(
    "SELECT COUNT(DISTINCT Responsable_AIA) FROM programacion_semanal
     WHERE project_id = ? AND Responsable_AIA <> ''", [$projectId]
)->fetchColumn();

$enCip = (int) $db->query(
    "SELECT COUNT(DISTINCT profesional) FROM cip WHERE project_id = ?", [$projectId]
)->fetchColumn();

if ($responsables === 0) {
    echo "SKIP: el proyecto $projectId no tiene responsables\n";
    exit(0);
}
if ($enCip === 0) {
    echo "FALLA: $responsables responsables con compromisos y 0 en cip (proyecto $projectId)\n";
    exit(1);
}
echo "PASA (OK): $enCip de $responsables responsables presentes en cip\n";
exit(0);
