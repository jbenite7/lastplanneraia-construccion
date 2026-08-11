<?php

declare(strict_types=1);
// @requiere: puro


/**
 * Verifica que la puerta de servicio de desarrollo (`App\Core\DevDoor`) permanezca cerrada
 * salvo que se cumplan SIMULTÁNEAMENTE sus tres condiciones.
 *
 * Este test existe para que el candado no se afloje sin que nadie se entere: si alguien
 * elimina una de las tres condiciones, alguno de los casos de abajo pasa a devolver true
 * y el test falla.
 *
 * Ver docs/superpowers/specs/2026-07-30-dev-door-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\DevDoor;

$fallos = 0;
$total = 0;

/**
 * Coloca el entorno completo y evalúa el candado.
 *
 * @param array<string,string|null> $env
 */
function evaluarCandado(array $env, ?string $remoteAddr): bool
{
    foreach (['APP_ENV', 'DEV_DOOR', 'DEV_DOOR_USERS'] as $clave) {
        if (array_key_exists($clave, $env) && $env[$clave] !== null) {
            $_ENV[$clave] = $env[$clave];
            putenv($clave . '=' . $env[$clave]);
        } else {
            unset($_ENV[$clave]);
            putenv($clave);
        }
    }

    if ($remoteAddr === null) {
        unset($_SERVER['REMOTE_ADDR']);
    } else {
        $_SERVER['REMOTE_ADDR'] = $remoteAddr;
    }

    return DevDoor::isOpen();
}

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

$abierto = ['APP_ENV' => 'development', 'DEV_DOOR' => '1', 'DEV_DOOR_USERS' => 'test.A,test.R,test.V'];

echo "=== DevDoor: el candado abre solo en el caso legítimo ===\n";

comprobar(
    'development + localhost + flag => abierta',
    evaluarCandado($abierto, '127.0.0.1'),
    true,
);

comprobar(
    'testing tambien abre (los e2e corren asi)',
    evaluarCandado(['APP_ENV' => 'testing'] + $abierto, '127.0.0.1'),
    true,
);

comprobar(
    'IPv6 local abre',
    evaluarCandado($abierto, '::1'),
    true,
);

echo "\n=== Condición 1: APP_ENV ===\n";

comprobar(
    'APP_ENV=production => cerrada',
    evaluarCandado(['APP_ENV' => 'production'] + $abierto, '127.0.0.1'),
    false,
);

comprobar(
    'APP_ENV ausente => cerrada (normalize cae a production)',
    evaluarCandado(['APP_ENV' => null] + $abierto, '127.0.0.1'),
    false,
);

comprobar(
    'APP_ENV con valor desconocido => cerrada',
    evaluarCandado(['APP_ENV' => 'staging'] + $abierto, '127.0.0.1'),
    false,
);

echo "\n=== Condición 2: origen de la petición ===\n";

comprobar(
    'IP publica => cerrada',
    evaluarCandado($abierto, '203.0.113.7'),
    false,
);

comprobar(
    'REMOTE_ADDR ausente => cerrada',
    evaluarCandado($abierto, null),
    false,
);

echo "\n=== Condición 3: flag explícito ===\n";

comprobar(
    'DEV_DOOR ausente => cerrada',
    evaluarCandado(['DEV_DOOR' => null] + $abierto, '127.0.0.1'),
    false,
);

comprobar(
    'DEV_DOOR=0 => cerrada',
    evaluarCandado(['DEV_DOOR' => '0'] + $abierto, '127.0.0.1'),
    false,
);

comprobar(
    'DEV_DOOR=true (no es el valor exigido) => cerrada',
    evaluarCandado(['DEV_DOOR' => 'true'] + $abierto, '127.0.0.1'),
    false,
);

comprobar(
    'DEV_DOOR_USERS vacio => cerrada',
    evaluarCandado(['DEV_DOOR_USERS' => ''] + $abierto, '127.0.0.1'),
    false,
);

echo "\n=== Lista de usuarios admitidos ===\n";

evaluarCandado($abierto, '127.0.0.1');

comprobar(
    'test.A admitido',
    DevDoor::allows('test.A'),
    true,
);

comprobar(
    'usuario fuera de la lista rechazado aun con candado abierto',
    DevDoor::allows('emmanuel.maldonado'),
    false,
);

comprobar(
    'login vacio rechazado',
    DevDoor::allows(''),
    false,
);

comprobar(
    'la comparacion es exacta, no por prefijo',
    DevDoor::allows('test.A.otro'),
    false,
);

evaluarCandado(['APP_ENV' => 'production'] + $abierto, '127.0.0.1');

comprobar(
    'con el candado cerrado no admite ni a los de la lista',
    DevDoor::allows('test.A'),
    false,
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
