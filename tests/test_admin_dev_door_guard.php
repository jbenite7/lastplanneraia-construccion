<?php

declare(strict_types=1);

/**
 * Verifica que la puerta de servicio de desarrollo de `admin/` (`GET /dev/entrar` registrada
 * en `admin/public/index.php`) permanezca cerrada fuera de desarrollo.
 *
 * `admin/` NO define un `Admin\Core\DevDoor` propio: reutiliza `App\Core\DevDoor::isOpen()`
 * tal cual (decisión de docs/superpowers/specs/2026-08-03-admin-dev-door-design.md), porque
 * comparte proceso, `.env` y `APP_ENV` con la app principal. El candado de los tres requisitos
 * ya está cubierto exhaustivamente por tests/test_dev_door_guard.php — este test NO repite esas
 * aserciones, solo referencia que ese guard existe y sigue verde.
 *
 * Lo que SÍ es nuevo aquí y cubre este test:
 *   (a) que el registro de la ruta en admin/public/index.php está condicionado a
 *       DevDoor::isOpen() (no se registra incondicionalmente),
 *   (b) que DevDoorController::enter() exige DevDoor::allows($login) antes de tocar sesión,
 *   (c) que DevDoorController::enter() exige activo === 1 antes de tocar sesión,
 *   (d) que devuelve 404 (nunca 403) en ambos rechazos.
 *
 * Como no hay runner HTTP disponible para `admin/` en este script standalone, (a)-(d) se
 * verifican por análisis estático del código fuente: se falla si la estructura condicional
 * desaparece, se invierte, o dejan de llamarse los guards en el orden esperado.
 *
 * Ver docs/superpowers/specs/2026-08-03-admin-dev-door-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

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

echo "=== admin/ reutiliza App\\Core\\DevDoor, no duplica el candado ===\n";

comprobar(
    'App\\Core\\DevDoor existe y admin/ no define un Admin\\Core\\DevDoor propio',
    class_exists(\App\Core\DevDoor::class) && !class_exists('Admin\\Core\\DevDoor'),
    true,
);

echo "\n=== admin/public/index.php: registro de /dev/entrar condicionado ===\n";

$indexFuente = file_get_contents(__DIR__ . '/../admin/public/index.php');

if ($indexFuente === false) {
    echo "  FAIL no se pudo leer admin/public/index.php\n";
    $fallos++;
    $total++;
} else {
    // El registro de la ruta debe vivir dentro de un `if (\App\Core\DevDoor::isOpen())`,
    // en ese orden: primero el guard, luego el `$router->add`.
    $patronGuardAntesDeRuta = '/if\s*\(\s*\\\\?App\\\\Core\\\\DevDoor::isOpen\(\)\s*\)\s*\{[^}]*\$router->add\(\s*[\'"]GET[\'"]\s*,\s*[\'"]\/dev\/entrar[\'"]/s';

    comprobar(
        "\$router->add('/dev/entrar', ...) está dentro de un bloque if (DevDoor::isOpen())",
        (bool) preg_match($patronGuardAntesDeRuta, $indexFuente),
        true,
    );

    // Ningún registro incondicional (fuera del if) de la misma ruta debe existir: se detecta
    // contando cuántas veces aparece la ruta en el archivo — debe ser exactamente una vez, y
    // esa única vez ya se confirmó arriba que está dentro del guard.
    comprobar(
        "'/dev/entrar' se registra una sola vez en admin/public/index.php",
        substr_count($indexFuente, "'/dev/entrar'") === 1,
        true,
    );
}

echo "\n=== admin/src/Controllers/DevDoorController.php: guards antes de tocar sesión ===\n";

$controllerFuente = file_get_contents(__DIR__ . '/../admin/src/Controllers/DevDoorController.php');

if ($controllerFuente === false) {
    echo "  FAIL no se pudo leer admin/src/Controllers/DevDoorController.php\n";
    $fallos++;
    $total++;
} else {
    // El docstring de cabecera menciona '$_SESSION[\'admin_user\']', 'DevDoor::allows(' y '403'
    // en prosa explicativa; recortamos el archivo a partir de la apertura de la clase para que
    // esas menciones no contaminen las posiciones que comparamos abajo.
    $inicioClase = strpos($controllerFuente, 'class DevDoorController');
    $cuerpoClase = $inicioClase !== false ? substr($controllerFuente, $inicioClase) : $controllerFuente;

    $posAllows = strpos($cuerpoClase, 'DevDoor::allows(');
    $posActivo = strpos($cuerpoClase, "(int) (\$user['activo']");
    $posSesion = strpos($cuerpoClase, "\$_SESSION['admin_user']");

    comprobar(
        'DevDoorController::enter() llama a DevDoor::allows($login)',
        $posAllows !== false,
        true,
    );

    comprobar(
        'DevDoorController::enter() comprueba activo === 1',
        $posActivo !== false,
        true,
    );

    comprobar(
        'DevDoor::allows() se evalúa antes de tocar $_SESSION[\'admin_user\']',
        $posAllows !== false && $posSesion !== false && $posAllows < $posSesion,
        true,
    );

    comprobar(
        'el chequeo de activo se evalúa antes de tocar $_SESSION[\'admin_user\']',
        $posActivo !== false && $posSesion !== false && $posActivo < $posSesion,
        true,
    );

    comprobar(
        'rechazo de DevDoor::allows() usa notFound() (404), no un 403',
        (bool) preg_match('/!DevDoor::allows\(\$login\)\)\s*\{\s*\$this->notFound\(\);/s', $cuerpoClase),
        true,
    );

    comprobar(
        'notFound() responde http_response_code(404)',
        (bool) preg_match('/private function notFound\(\):\s*never\s*\{\s*http_response_code\(404\);/s', $cuerpoClase),
        true,
    );

    comprobar(
        'no hay http_response_code(403) en el cuerpo de la clase',
        strpos($cuerpoClase, '403') === false,
        true,
    );
}

echo "\n=== Referencia: el candado de los tres requisitos está cubierto por el guard existente ===\n";

comprobar(
    'tests/test_dev_door_guard.php existe (cubre APP_ENV, IP, DEV_DOOR/DEV_DOOR_USERS)',
    is_file(__DIR__ . '/test_dev_door_guard.php'),
    true,
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
