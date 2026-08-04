<?php

declare(strict_types=1);

/**
 * Verifica que la puerta de servicio de desarrollo de `admin/` (`GET /dev/entrar` registrada
 * en `admin/public/index.php`) permanezca cerrada fuera de desarrollo y no otorgue nada por
 * encima de la cuenta usada.
 *
 * `admin/` NO define un `Admin\Core\DevDoor` propio: reutiliza `App\Core\DevDoor::isOpen()`
 * tal cual (decisión de docs/superpowers/specs/2026-08-03-admin-dev-door-design.md), porque
 * comparte proceso, `.env` y `APP_ENV` con la app principal. El candado de los tres requisitos
 * (`APP_ENV`, IP, `DEV_DOOR`/`DEV_DOOR_USERS`) ya está cubierto exhaustivamente por
 * tests/test_dev_door_guard.php contra esa misma clase — este test NO repite esas aserciones
 * en abstracto: en su lugar EJECUTA ese guard (ver más abajo) y falla si deja de pasar.
 *
 * Dos mitades:
 *
 *   ESTÁTICA — análisis de código fuente. Deliberadamente NO exige la forma textual exacta
 *   (ese es el error de "el guard valida la declaración contra sí misma": una regex que calca
 *   el código actual solo detecta que alguien cambió el formato, no que rompió la garantía).
 *   En su lugar comprueba invariantes semánticos que sobreviven a un refactor razonable:
 *     - el registro de la ruta '/dev/entrar' ocurre DENTRO del bloque `if (DevDoor::isOpen())`,
 *       verificado con el tokenizador de PHP (balance real de llaves, no una regex de texto que
 *       un comentario con una llave suelta podría romper),
 *     - DevDoor::allows() se invoca en el handler y hay al menos dos caminos a notFound() (uno
 *       por login no admitido, otro por usuario inactivo/inexistente) — sin exigir la secuencia
 *       literal de un `if` concreto,
 *     - ningún camino de rechazo usa http_response_code(403) (matched como llamada real, no
 *       como substring — evita el falso rojo de un comentario que mencione "403").
 *
 *   DINÁMICA — HTTP real contra el contenedor en su estado actual (el runtime local con el que
 *   se corre este test tiene `DEV_DOOR=1` en `.env`, ver Task 9). Cubre lo que el análisis
 *   estático no puede: que el comportamiento observable coincide con el spec.
 *     - `?u=test.A` (rol A, con requireAdminRole) → 302 a /admin/,
 *     - `?u=<login inexistente>` → 404,
 *     - `?u=test.V` (rol V, sin requireAdminRole) seguido de /admin/dashboard con la misma
 *       cookie → 403 del guard de rol real, no un salto de RBAC.
 *
 *   La rama "candado cerrado ⇒ 404" NO se prueba aquí por HTTP: cerrarla requeriría reiniciar
 *   el contenedor con otro `.env` (`DEV_DOOR=0` o `APP_ENV=production`), lo que este script no
 *   hace porque tocaría el runtime compartido de la sesión. Esa rama ya quedó demostrada por el
 *   Task 9 (`DevDoor::isOpen() === false` con `-e DEV_DOOR=0`) y aquí se cubre solo de forma
 *   estática: el `if` que condiciona el registro de la ruta existe y la envuelve.
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

/**
 * Tokeniza $codigo con el tokenizador real de PHP y comprueba que, tras encontrar una llamada
 * a "isOpen" seguida del bloque `{` que abre su `if`, el literal $rutaLiteral aparece como
 * token de cadena ANTES de que ese mismo bloque cierre (balance de llaves por tokens reales,
 * no por texto — inmune a llaves dentro de comentarios o strings).
 */
function rutaRegistradaDentroDelGuardIsOpen(string $codigo, string $rutaLiteral): bool
{
    $tokens = token_get_all($codigo);
    $n = count($tokens);

    for ($i = 0; $i < $n; $i++) {
        $token = $tokens[$i];

        if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== 'isOpen') {
            continue;
        }

        // Encontrado "isOpen"; buscar hacia adelante el primer '{' que abre su bloque `if`.
        $j = $i + 1;
        $profundidadParentesis = 0;
        $llaveAbreEncontrada = false;

        for (; $j < $n; $j++) {
            $t = $tokens[$j];
            $texto = is_array($t) ? $t[1] : $t;

            if ($texto === '(') {
                $profundidadParentesis++;
            } elseif ($texto === ')') {
                $profundidadParentesis--;
            } elseif ($texto === '{' && $profundidadParentesis <= 0) {
                $llaveAbreEncontrada = true;
                break;
            }
        }

        if (!$llaveAbreEncontrada) {
            continue;
        }

        // Desde el '{' que abre el bloque, contar balance de llaves reales hasta que cierre;
        // si el literal de ruta aparece como cadena dentro de ese rango, la ruta está adentro.
        $profundidadLlaves = 1;
        $j++;

        for (; $j < $n && $profundidadLlaves > 0; $j++) {
            $t = $tokens[$j];
            $texto = is_array($t) ? $t[1] : $t;

            if ($texto === '{') {
                $profundidadLlaves++;
            } elseif ($texto === '}') {
                $profundidadLlaves--;
                continue;
            }

            if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $sinComillas = substr($t[1], 1, -1);

                if ($sinComillas === $rutaLiteral) {
                    return true;
                }
            }
        }
    }

    return false;
}

echo "=== admin/ reutiliza App\\Core\\DevDoor, no duplica el candado ===\n";

comprobar(
    'App\\Core\\DevDoor existe y admin/ no define un Admin\\Core\\DevDoor propio',
    class_exists(\App\Core\DevDoor::class) && !class_exists('Admin\\Core\\DevDoor'),
    true,
);

echo "\n=== ESTÁTICA — admin/public/index.php: registro de /dev/entrar condicionado ===\n";

$indexFuente = file_get_contents(__DIR__ . '/../admin/public/index.php');

if ($indexFuente === false) {
    echo "  FAIL no se pudo leer admin/public/index.php\n";
    $fallos++;
    $total++;
} else {
    comprobar(
        "\$router->add('/dev/entrar', ...) está dentro del bloque if que llama a DevDoor::isOpen() (balance real de llaves, tokenizado)",
        rutaRegistradaDentroDelGuardIsOpen($indexFuente, '/dev/entrar'),
        true,
    );

    comprobar(
        "'/dev/entrar' se registra una sola vez en admin/public/index.php (no hay un segundo registro incondicional)",
        substr_count($indexFuente, "'/dev/entrar'") === 1,
        true,
    );
}

echo "\n=== ESTÁTICA — admin/src/Controllers/DevDoorController.php: invariantes semánticos ===\n";

$controllerFuente = file_get_contents(__DIR__ . '/../admin/src/Controllers/DevDoorController.php');

if ($controllerFuente === false) {
    echo "  FAIL no se pudo leer admin/src/Controllers/DevDoorController.php\n";
    $fallos++;
    $total++;
} else {
    // El docstring de cabecera menciona '$_SESSION[\'admin_user\']', 'DevDoor::allows(' y '403'
    // en prosa explicativa; recortamos el archivo a partir de la apertura de la clase para que
    // esas menciones no contaminen las posiciones/conteos que comparamos abajo.
    $inicioClase = strpos($controllerFuente, 'class DevDoorController');
    $cuerpoClase = $inicioClase !== false ? substr($controllerFuente, $inicioClase) : $controllerFuente;

    $posAllows = strpos($cuerpoClase, 'DevDoor::allows(');
    $posActivo = strpos($cuerpoClase, "(int) (\$user['activo']");
    $posSesion = strpos($cuerpoClase, "\$_SESSION['admin_user']");
    $vecesNotFound = substr_count($cuerpoClase, '$this->notFound()');

    comprobar(
        'DevDoorController::enter() invoca DevDoor::allows($login) en algún punto del handler',
        $posAllows !== false,
        true,
    );

    comprobar(
        'DevDoorController::enter() comprueba activo === 1 en algún punto del handler',
        $posActivo !== false,
        true,
    );

    comprobar(
        'DevDoor::allows() se evalúa antes de escribir $_SESSION[\'admin_user\']',
        $posAllows !== false && $posSesion !== false && $posAllows < $posSesion,
        true,
    );

    comprobar(
        'el chequeo de activo se evalúa antes de escribir $_SESSION[\'admin_user\']',
        $posActivo !== false && $posSesion !== false && $posActivo < $posSesion,
        true,
    );

    comprobar(
        'hay al menos dos caminos a notFound() (login no admitido + usuario inactivo/inexistente) — no se exige la forma literal de cada if',
        $vecesNotFound >= 2,
        true,
    );

    comprobar(
        'notFound() responde con una llamada real a http_response_code(404)',
        (bool) preg_match('/function\s+notFound\s*\([^)]*\)[^{]*\{\s*http_response_code\s*\(\s*404\s*\)/s', $cuerpoClase),
        true,
    );

    comprobar(
        'ningún camino de rechazo llama a http_response_code(403) (match de llamada real, no de substring "403" en prosa)',
        (bool) preg_match('/http_response_code\s*\(\s*403\s*\)/', $cuerpoClase) === false,
        true,
    );
}

echo "\n=== Referencia: el candado de los tres requisitos se re-ejecuta (no se repite en abstracto) ===\n";

$rutaGuardViejo = __DIR__ . '/test_dev_door_guard.php';
$salidaGuardViejo = [];
$codigoGuardViejo = 1;
exec('php ' . escapeshellarg($rutaGuardViejo) . ' 2>&1', $salidaGuardViejo, $codigoGuardViejo);

comprobar(
    'tests/test_dev_door_guard.php (candado APP_ENV/IP/DEV_DOOR contra App\\Core\\DevDoor) sigue en verde al re-ejecutarlo',
    $codigoGuardViejo === 0,
    true,
);

if ($codigoGuardViejo !== 0) {
    echo "  --- salida de test_dev_door_guard.php ---\n";
    foreach ($salidaGuardViejo as $linea) {
        echo "  {$linea}\n";
    }
}

echo "\n=== DINÁMICA — HTTP real contra el contenedor (estado actual: DEV_DOOR abierto en .env) ===\n";

if (!function_exists('curl_init')) {
    echo "  SKIP ext-curl no disponible; se omite la mitad dinámica (la estática ya cubrió estructura)\n";
} else {
    $base = 'http://localhost/admin/dev/entrar';

    /**
     * @return array{codigo:int,location:?string}
     */
    $peticion = static function (string $url, ?string $cookieJar = null, ?string $cookieUsar = null): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HEADER => true,
        ]);

        if ($cookieJar !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        }

        if ($cookieUsar !== null) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieUsar);
        }

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta === false || $error !== '') {
            return ['codigo' => 0, 'location' => null];
        }

        $location = null;

        if (preg_match('/^Location:\s*(.+)$/mi', (string) $respuesta, $m)) {
            $location = trim($m[1]);
        }

        return ['codigo' => $codigo, 'location' => $location];
    };

    // Sondeo de alcanzabilidad: si el servidor no responde nada, la mitad dinámica se omite
    // en vez de fallar en falso (por ejemplo, corriendo este script fuera del contenedor app).
    $sondeo = $peticion($base . '?u=__sondeo_alcanzabilidad__');

    if ($sondeo['codigo'] === 0) {
        echo "  SKIP no se pudo alcanzar http://localhost dentro de este entorno; se omite la mitad dinámica\n";
    } else {
        $rolPermitido = $peticion($base . '?u=test.A');

        comprobar(
            "GET /admin/dev/entrar?u=test.A (candado abierto, rol A) -> 302",
            $rolPermitido['codigo'] === 302,
            true,
        );

        comprobar(
            "GET /admin/dev/entrar?u=test.A redirige a /admin/",
            $rolPermitido['location'] !== null && str_contains($rolPermitido['location'], '/admin/'),
            true,
        );

        $loginInexistente = $peticion($base . '?u=usuario-inexistente-no-registrado-xyz');

        comprobar(
            "GET /admin/dev/entrar?u=<login inexistente> -> 404",
            $loginInexistente['codigo'] === 404,
            true,
        );

        $cookieJar = sys_get_temp_dir() . '/admin_dev_door_guard_test_' . getmypid() . '.cookies';
        $peticion($base . '?u=test.V', $cookieJar);
        $dashboardConRolV = $peticion('http://localhost/admin/dashboard', null, $cookieJar);

        if (is_file($cookieJar)) {
            unlink($cookieJar);
        }

        comprobar(
            "u=test.V (rol V, sin requireAdminRole) abre sesión pero /admin/dashboard responde 403 — la puerta no otorga nada por encima de la cuenta",
            $dashboardConRolV['codigo'] === 403,
            true,
        );
    }
}

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
