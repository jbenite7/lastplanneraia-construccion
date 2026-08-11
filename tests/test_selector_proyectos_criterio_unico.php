<?php

declare(strict_types=1);

/**
 * Task 5 (frente 1a): un solo criterio de "proyecto cerrado visible para la jefatura",
 * consumido por ProjectSelectorController y por BiProjectScope; ProjectSelectorController
 * normaliza roles con App\Security\RbacService::normalizeRole() (que traduce alias de texto
 * vía RbacCatalog::roleAliases()) en vez de un normalizador privado incompleto; index() y
 * enterProject() filtran/comprueban con el mismo rol ya normalizado; y la barra de avance
 * inventada (`rand(0, 100)`) se retiró del selector.
 *
 * Nota sobre el brief original: pedía sustituir el privado por
 * Admin\Core\RoleManager::cleanCargo(), pero esa función es un limpiador de texto para
 * fuzzy-matching contra role_intelligence (devuelve "director obra", no "D"), no un
 * normalizador de código de rol. El coordinador lo confirmó y corrigió AGENTS.md; el
 * reemplazo real es RbacService::normalizeRole().
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;
use App\Security\RbacService;

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $obtenido, bool $esperado): void
{
    global $fallos, $total;
    $total++;

    if ($obtenido === $esperado) {
        echo "  OK   {$descripcion}\n";

        return;
    }

    $fallos++;
    echo '  FAIL ' . $descripcion
        . ' (esperado ' . var_export($esperado, true)
        . ', obtenido ' . var_export($obtenido, true) . ")\n";
}

echo "=== Criterio compartido en RbacCatalog ===\n";

comprobar(
    'managementRoles() son los roles ya normalizados que ven un proyecto cerrado (A, D)',
    RbacCatalog::managementRoles() === ['A', 'D'],
    true,
);

comprobar(
    "closedProjectVisibleRoles() (SQL crudo, lo usa BiProjectScope) incluye 'P', el alias legado de Director",
    RbacCatalog::closedProjectVisibleRoles() === ['A', 'D', 'P'],
    true,
);

$biScopeSrc = (string) file_get_contents(__DIR__ . '/../src/Support/BiProjectScope.php');
comprobar(
    'BiProjectScope sigue consumiendo el criterio compartido (no una lista propia)',
    str_contains($biScopeSrc, 'RbacCatalog::closedProjectVisibleRoles()'),
    true,
);

echo "\n=== El selector normaliza con RbacService::normalizeRole(), no con un privado propio ===\n";

$rbac = new RbacService(\Database::getInstance());

comprobar(
    "'Director de Obra' (alias de texto) normaliza a 'D', no al fallback",
    $rbac->normalizeRole('Director de Obra') === 'D',
    true,
);

comprobar(
    "el alias legado 'P' sigue normalizando a 'D' (no se perdió la traducción del privado)",
    $rbac->normalizeRole('P') === 'D',
    true,
);

comprobar(
    "el alias legado 'U' sigue normalizando a 'V' (no se perdió la traducción del privado)",
    $rbac->normalizeRole('U') === 'V',
    true,
);

$selectorSrc = (string) file_get_contents(__DIR__ . '/../src/Controllers/Core/ProjectSelectorController.php');

comprobar(
    'ProjectSelectorController ya no define el normalizador privado (normalizeRoleCode)',
    str_contains($selectorSrc, 'function normalizeRoleCode'),
    false,
);

comprobar(
    'index() normaliza con $this->rbac->normalizeRole()',
    (bool) preg_match('/\$this->rbac->normalizeRole\(/', $selectorSrc),
    true,
);

echo "\n=== La invariante: index() y enterProject() filtran/comprueban con el mismo rol ===\n";

comprobar(
    "ambos usan RbacCatalog::managementRoles() como único criterio para 'Acceso=0'",
    substr_count($selectorSrc, 'RbacCatalog::managementRoles()') === 2,
    true,
);

comprobar(
    "index() ya no filtra por rol crudo en el SQL (perdía alias de texto, ver hallazgo 3)",
    (bool) preg_match('/pm\\.role IN \\(/', $selectorSrc),
    false,
);

echo "\n=== La barra de avance inventada se retira, no se sustituye ===\n";

comprobar(
    "ProjectSelectorController ya no genera 'progreso' con rand()",
    str_contains($selectorSrc, 'rand(0, 100)') || str_contains($selectorSrc, "['progreso']"),
    false,
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
