<?php

declare(strict_types=1);
// @requiere: db


/**
 * Verifica que el candado de semana (CommitmentLockGuard + LpsWeekEditPolicy) falle hacia
 * el mismo lado cuando no puede resolver el proyecto: cierra, en las dos piezas.
 *
 * Antes de esta prueba, CommitmentLockGuard::guard() dejaba pasar la mutación ("se deja
 * pasar por seguridad") cuando el proyecto no se resolvía, mientras que
 * LpsWeekEditPolicy::allows() ya denegaba en el mismo caso. Y guard(allowIfConfirmed: true)
 * retornaba en su primera línea sin comprobar rol ni política — un pase libre.
 *
 * guard() termina el proceso con exit() en el caso de bloqueo, así que cada escenario que
 * lo dispara se corre en un subproceso PHP aparte para poder observar el exit code sin que
 * termine este script.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/TableResolver.php';
require_once __DIR__ . '/../src/Core/CommitmentLockGuard.php';
require_once __DIR__ . '/../src/Security/RbacCatalog.php';
require_once __DIR__ . '/../src/Security/RbacService.php';
require_once __DIR__ . '/../src/Security/LpsWeekEditPolicy.php';

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;

    if ($condicion) {
        echo "  OK   {$descripcion}\n";

        return;
    }

    $fallos++;
    echo "  FAIL {$descripcion}\n";
}

$prefijoInexistente = 'prefijo_que_no_existe_en_general_proyectos_procesos_' . bin2hex(random_bytes(4));

echo "=== Candado de semana: LpsWeekEditPolicy::allows() con proyecto irresoluble ===\n";

$db = Database::getInstance();
$policy = new App\Security\LpsWeekEditPolicy($db);
comprobar(
    'allows() deniega cuando el proyecto no se resuelve',
    $policy->allows($prefijoInexistente, 1) === false,
);

echo "\n=== Candado de semana: CommitmentLockGuard::guard() con proyecto irresoluble ===\n";

/**
 * Corre CommitmentLockGuard::guard() en un subproceso y devuelve [exitCode, salida].
 *
 * @return array{0:int,1:string}
 */
function correrGuardEnSubproceso(string $dbPrefix, int $semana, string $operacion, bool $allowIfConfirmed): array
{
    $codigo = <<<PHP
        require_once '%s/../vendor/autoload.php';
        require_once '%s/../src/Core/Database.php';
        require_once '%s/../src/Core/TableResolver.php';
        require_once '%s/../src/Core/CommitmentLockGuard.php';
        require_once '%s/../src/Security/RbacCatalog.php';
        require_once '%s/../src/Security/RbacService.php';
        CommitmentLockGuard::guard(%s, %d, %s, %s);
        echo "NO_EXIT\\n";
        PHP;
    $raiz = __DIR__;
    $codigo = sprintf(
        $codigo,
        $raiz, $raiz, $raiz, $raiz, $raiz, $raiz,
        var_export($dbPrefix, true),
        $semana,
        var_export($operacion, true),
        var_export($allowIfConfirmed, true),
    );

    $tmp = tempnam(sys_get_temp_dir(), 'candado_test_');
    file_put_contents($tmp, "<?php\n{$codigo}\n");
    $salida = shell_exec('php ' . escapeshellarg($tmp) . ' 2>&1; echo "EXITCODE:$?"');
    unlink($tmp);

    if (!preg_match('/EXITCODE:(\d+)\s*$/', (string) $salida, $m)) {
        return [-1, (string) $salida];
    }

    return [(int) $m[1], (string) $salida];
}

[$codigo, $salida] = correrGuardEnSubproceso($prefijoInexistente, 1, 'modificar', false);
comprobar(
    'guard() deniega (HTTP 409 + exit) cuando el proyecto no se resuelve',
    $codigo === 0 && str_contains($salida, '"respuesta":"ERROR"') && !str_contains($salida, 'NO_EXIT'),
);

echo "\n=== Candado de semana: guard(allowIfConfirmed: true) ya no es un pase libre ===\n";

// Sin sesión con rol habilitado, allowIfConfirmed=true debe seguir denegando: comprueba
// rol y política en vez de retornar en la primera línea.
[$codigo, $salida] = correrGuardEnSubproceso($prefijoInexistente, 1, 'registrar_avance', true);
comprobar(
    'guard(allowIfConfirmed: true) deniega cuando el proyecto no se resuelve (ni siquiera llega a comprobar rol)',
    $codigo === 0 && str_contains($salida, '"respuesta":"ERROR"') && !str_contains($salida, 'NO_EXIT'),
);

// Proyecto resoluble (optimizacionJMC, projectId 68) pero sin sesión con rol habilitado:
// esto solo puede fallar si guard() efectivamente comprueba rol/política en vez de
// retornar en la primera línea, como hacía antes.
[$codigo, $salida] = correrGuardEnSubproceso('optimizacionJMC', 9992, 'registrar_avance', true);
comprobar(
    'guard(allowIfConfirmed: true) deniega por rol no habilitado cuando SÍ hay proyecto (ya no es pase libre)',
    $codigo === 0 && str_contains($salida, '"respuesta":"ERROR"') && !str_contains($salida, 'NO_EXIT'),
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
