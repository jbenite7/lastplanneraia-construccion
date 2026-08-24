<?php

declare(strict_types=1);
// @requiere: db

/**
 * Prueba: resolver el nombre en `profesionales` de la persona en sesión, cruzando
 * por email contra `general_usuarios`. Es lo que usa el filtro por defecto de
 * Responsables para el Residente.
 * Ver docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Bi\BiViewController;

$db = \Database::getInstance();
$projectId = (int) ($argv[1] ?? 68);

// Toma cualquier profesional real con email de ese proyecto para armar el caso.
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
$tProf = \TableResolver::resolveByPrefix((string) $dbName, 'profesionales');
$fila = $db->query(
    "SELECT p.nombre, u.usuario FROM {$tProf} p
     INNER JOIN general_usuarios u ON u.email = p.email
     WHERE p.project_id = ? AND p.email <> '' LIMIT 1",
    [$projectId]
)->fetch();

if ($fila === false) {
    echo "SKIP: el proyecto $projectId no tiene ningún profesional con usuario cruzado por email\n";
    exit(0);
}

$controller = new class extends BiViewController {
    public function exponerResolverNombre(string $usuario, int $projectId): ?string
    {
        return $this->resolveOwnProfessionalName($usuario, $projectId);
    }
};

$_SESSION['db'] = $dbName;

$obtenido = $controller->exponerResolverNombre($fila['usuario'], $projectId);

if ($obtenido !== $fila['nombre']) {
    echo "FALLA: esperaba '{$fila['nombre']}', obtuvo '" . var_export($obtenido, true) . "'\n";
    exit(1);
}
echo "OK: '{$fila['usuario']}' resuelve a '{$obtenido}'\n";
exit(0);
