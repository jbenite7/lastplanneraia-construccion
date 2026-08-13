<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Security\RbacService;
use App\Services\Bi\StorytellingService;
use App\Support\BiProjectScope;
use App\View\Components\BiAccessComponent;

$db = Database::getInstance();
BiContractFixture::begin($db);
$mixedRoleFixture = $db->prepare(
    "UPDATE project_members pm
     INNER JOIN general_usuarios u ON u.id = pm.user_id
     SET pm.role = 'C'
     WHERE u.usuario = 'test.R' AND pm.project_id = 75",
);
$mixedRoleFixture->execute();
$scope = new BiProjectScope($db);
$failures = [];

$fixtureIdentity = 'test.A';
$projects = $scope->authorizedProjects(['usuario' => $fixtureIdentity]);
if ($projects === []) {
    $failures[] = 'canonical CI administrator has no authorized BI projects';
}

$authorizedIds = array_map('intval', array_column($projects, 'project_id'));
$firstProjectId = $authorizedIds[0] ?? 0;
$session = ['usuario' => $fixtureIdentity, 'project_id' => $firstProjectId];

if ($firstProjectId > 0 && $scope->resolve((string) $firstProjectId, $session) !== [$firstProjectId]) {
    $failures[] = 'authorized requested project was not preserved';
}

if ($firstProjectId > 0 && $scope->resolve('', $session) !== [$firstProjectId]) {
    $failures[] = 'authorized session project was not used as fallback';
}

$rbac = new RbacService($db);
$memberships = $db->prepare(
    "SELECT u.usuario, p.ID AS project_id, pm.role
     FROM project_members pm
     INNER JOIN general_usuarios u ON u.id = pm.user_id
     INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
     WHERE p.Area IN ('Construccion', 'Pre-Construccion')
       AND p.Activo = 1
       AND (p.Acceso = 1 OR pm.role IN ('A', 'D', 'P'))
     ORDER BY u.usuario, p.ID",
);
$memberships->execute();
$candidates = [];
foreach ($memberships->fetchAll(PDO::FETCH_ASSOC) as $membership) {
    $usuario = (string) $membership['usuario'];
    $projectId = (int) $membership['project_id'];
    $bucket = $rbac->can('lps.indicadores.ver', (string) $membership['role'])
        ? 'allowed'
        : 'denied';
    $candidates[$usuario][$bucket][$projectId] = $projectId;
}

$crossRoleCandidate = null;
foreach ($candidates as $usuario => $candidate) {
    $allowed = $candidate['allowed'] ?? [];
    $denied = $candidate['denied'] ?? [];
    if ($allowed !== [] && $denied !== [] && array_intersect($allowed, $denied) === []) {
        $crossRoleCandidate = ['usuario' => $usuario] + $candidate;
        break;
    }
}

if ($crossRoleCandidate === null) {
    $failures[] = 'local DB has no user with both permitted and denied BI project roles';
} else {
    $candidateSession = [
        'usuario' => $crossRoleCandidate['usuario'],
        'project_id' => reset($crossRoleCandidate['allowed']),
    ];
    $candidateScope = new BiProjectScope($db);
    $actualAllowedIds = array_map(
        'intval',
        array_column($candidateScope->authorizedProjects($candidateSession), 'project_id'),
    );
    $expectedAllowedIds = array_values($crossRoleCandidate['allowed']);
    sort($actualAllowedIds, SORT_NUMERIC);
    sort($expectedAllowedIds, SORT_NUMERIC);
    if ($actualAllowedIds !== $expectedAllowedIds) {
        $failures[] = 'authorized projects did not match the user roles with BI permission';
    }

    $allowedProjectId = $expectedAllowedIds[0];
    $deniedProjectId = reset($crossRoleCandidate['denied']);
    if ($candidateScope->resolve((string) $allowedProjectId, $candidateSession) !== [$allowedProjectId]) {
        $failures[] = 'candidate allowed project was not resolved';
    }

    foreach ([$deniedProjectId, [$allowedProjectId, $deniedProjectId]] as $requestedProjectIds) {
        try {
            $candidateScope->resolve($requestedProjectIds, $candidateSession);
            $failures[] = 'denied BI project request was not rejected';
        } catch (\DomainException $exception) {
            if (!str_contains($exception->getMessage(), 'permiso')) {
                $failures[] = 'denied BI project request returned an unclear error';
            }
        }
    }

    $previousSession = $_SESSION ?? [];
    $_SESSION = [
        'usuario' => $crossRoleCandidate['usuario'],
        'project_id' => $deniedProjectId,
    ];
    try {
        // Las dos comprobaciones de BiAccessComponent que había aquí afirmaban que el
        // acceso global se veía y el contextual no. Desde el 2026-08-13
        // (docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md) el componente
        // devuelve false siempre —el módulo está oculto de la navegación mientras se
        // desarrolla— así que ya no distinguen nada. Lo que este test debe seguir
        // protegiendo es el ALCANCE, que no ha cambiado: se comprueba contra
        // BiProjectScope directamente, que es su dueño.
        // Al revertir el ocultamiento, restaurar las dos líneas desde el historial.
        if (!$candidateScope->hasAnyAccess($_SESSION)) {
            $failures[] = 'global BI scope was hidden despite an allowed project';
        }
        if ($candidateScope->canAccessProject($deniedProjectId, $_SESSION)) {
            $failures[] = 'denied active project was reported as accessible';
        }
        if ($candidateScope->resolve($allowedProjectId, $_SESSION) !== [$allowedProjectId]) {
            $failures[] = 'allowed BI project was blocked by denied active project';
        }
        if (BiAccessComponent::globalUrl() !== '/bi/control-tower') {
            $failures[] = 'global BI URL inherited the denied active project';
        }
    } finally {
        $_SESSION = $previousSession;
    }
}

$normalized = BiProjectScope::normalizeProjectIds([$firstProjectId, (string) $firstProjectId, '0']);
if ($firstProjectId > 0 && $normalized !== [$firstProjectId]) {
    $failures[] = 'project normalization did not deduplicate valid IDs';
}

if (count($authorizedIds) > 1
    && $scope->reportRole(array_slice($authorizedIds, 0, 2), $session) !== 'MULTI'
) {
    $failures[] = 'multi-project report role is not explicit';
}

$multiBrief = (new StorytellingService())->composeExecutiveBrief(
    'programa-general',
    [['is_critical_late' => 1]],
    'MULTI',
);
if (!str_contains((string) ($multiBrief['priority_action'] ?? ''), 'Asignar recursos')) {
    $failures[] = 'multi-project storytelling did not use executive wording';
}
if (str_contains((string) ($multiBrief['priority_action'] ?? ''), 'Revisa y recupera')) {
    $failures[] = 'multi-project storytelling used resident instructions';
}

$unauthorizedId = max($authorizedIds ?: [0]) + 100000;
try {
    $scope->resolve([$firstProjectId, $unauthorizedId], $session);
    $failures[] = 'mixed authorized/unauthorized request was not rejected';
} catch (\DomainException $exception) {
    if (!str_contains($exception->getMessage(), 'permiso')) {
        $failures[] = 'unauthorized request returned an unclear error';
    }
}

$identitylessScope = new BiProjectScope($db);
$identitylessProjects = $identitylessScope->authorizedProjects([
    'project_id' => 912345,
    'permiso_canonico' => 'R',
]);
if ($identitylessProjects !== []) {
    $failures[] = 'session without user identity was authorized by project and role alone';
}

foreach ([
    __DIR__ . '/../src/Controllers/Bi/BiViewController.php',
    __DIR__ . '/../src/Controllers/Api/BiControlTowerApiController.php',
] as $controllerFile) {
    $source = file_get_contents($controllerFile) ?: '';
    if (str_contains($source, "authorizePermission('lps.indicadores.ver')")) {
        $failures[] = 'BI controller still gates access with the active-project role';
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo 'PASS: BI project scope authorizes, normalizes and rejects mixed scope requests'
    . PHP_EOL;
