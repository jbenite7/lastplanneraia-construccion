<?php
// Vuelca a JSON el mapa real de capacidades por rol.
// Se ejecuta dentro del contenedor: docker compose exec -T app php scripts/wiki-arquitectura-rbac.php
require __DIR__ . '/../vendor/autoload.php';

$roles = ['A', 'D', 'R', 'DCV', 'OT', 'G', 'S', 'SG', 'C', 'V'];
$salida = [];
foreach ($roles as $rol) {
    $salida[$rol] = \App\Security\RbacManager::getCapabilities($rol);
}
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
