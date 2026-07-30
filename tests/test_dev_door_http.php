<?php

declare(strict_types=1);

/**
 * Verifica la puerta de servicio EXTREMO A EXTREMO, por HTTP contra la app servida.
 *
 * Complementa a test_dev_door_guard.php, que prueba la lógica del candado en aislamiento.
 * Ese test puede pasar mientras la puerta no funciona: el candado abre pero la ruta no
 * llega a registrarse, o registra y no deja una sesión utilizable. Ese hueco se detectó en
 * la práctica —una sesión hermana reportó 404 con el candado abierto— y este test existe
 * para cerrarlo: aquí se comprueba que la ruta EXISTE y que la sesión resultante SIRVE.
 *
 * Requiere la aplicación levantada. Se golpea 127.0.0.1 desde dentro del contenedor, de
 * modo que REMOTE_ADDR es local y la condición de origen del candado se cumple de verdad,
 * sin simularla.
 *
 * Ver docs/superpowers/specs/2026-07-30-dev-door-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\DevDoor;

// Los scripts CLI no pasan por public/index.php, que es quien carga el .env. Sin esto,
// DEV_DOOR no existe en el entorno y el candado parecería cerrado aunque la app servida
// lo tenga abierto — un falso negativo que costaría un rato entender.
if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

const BASE = 'http://127.0.0.1';

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, $obtenido, $esperado): void
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
 * @return array{code:int, location:string, cookies:string}
 */
function pedir(string $path, string $cookies = ''): array
{
    $ch = curl_init(BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($cookies !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }

    $respuesta = curl_exec($ch);

    if ($respuesta === false) {
        echo 'ERROR: la aplicación no responde en ' . BASE . ' (' . curl_error($ch) . ")\n";
        echo "Levanta el stack antes de correr este test.\n";
        exit(1);
    }

    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr((string) $respuesta, 0, $headerSize);
    curl_close($ch);

    preg_match('/^Location:\s*(.*)$/mi', $headers, $loc);
    preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $headers, $cookieMatches, PREG_SET_ORDER);

    // La puerta llama a session_regenerate_id(), que emite DOS Set-Cookie para PHPSESSID:
    // el id viejo y el nuevo. Enviarlos ambos hace que el servidor tome el viejo —ya
    // destruido— y la petición salga deslogueada. Nos quedamos con el último por nombre,
    // que es lo que hace cualquier cliente real.
    $cookies = [];
    foreach ($cookieMatches as $cookie) {
        $cookies[trim($cookie[1])] = $cookie[2];
    }

    $serializadas = [];
    foreach ($cookies as $nombre => $valor) {
        $serializadas[] = $nombre . '=' . $valor;
    }

    return [
        'code' => $code,
        'location' => trim($loc[1] ?? ''),
        'cookies' => implode('; ', $serializadas),
    ];
}

/**
 * Primer proyecto del que el usuario es miembro, o null si no lo es de ninguno.
 */
function proyectoDe(string $usuario): ?string
{
    try {
        $pdo = new PDO(
            'mysql:host=' . (getenv('DB_HOST') ?: 'db')
                . ';port=' . (getenv('DB_PORT') ?: '3306')
                . ';dbname=' . getenv('DB_NAME'),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
        );

        $stmt = $pdo->prepare(
            'SELECT p.Proyecto_Proceso
             FROM project_members pm
             INNER JOIN general_usuarios u ON u.id = pm.user_id
             INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
             WHERE u.usuario = ?
               AND p.Activo = 1
               AND p.Area IN (\'Construccion\', \'Pre-Construccion\')
             LIMIT 1',
        );
        $stmt->execute([$usuario]);
        $proyecto = $stmt->fetchColumn();
    } catch (\Throwable $e) {
        echo '  aviso: no se pudo consultar la base (' . $e->getMessage() . ")\n";

        return null;
    }

    return $proyecto === false ? null : (string) $proyecto;
}

echo "=== La puerta debe estar abierta para que este test tenga sentido ===\n";

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

if (!DevDoor::isOpen()) {
    echo "ERROR: el candado está cerrado en este entorno.\n";
    echo "Revisa DEV_DOOR=1 y DEV_DOOR_USERS en .env — recuerda que .env NO se versiona,\n";
    echo "así que un worktree recién clonado no tiene esas claves aunque tenga el código.\n";
    exit(1);
}

echo "  OK   candado abierto\n";

echo "\n=== La ruta existe y deja una sesión utilizable ===\n";

$usuario = DevDoor::allowedUsers()[0];
$entrada = pedir('/dev/entrar?u=' . urlencode($usuario));

comprobar(
    'GET /dev/entrar responde 302 (la ruta ESTA registrada, no es 404)',
    $entrada['code'],
    302,
);

comprobar(
    'sin proyecto aterriza en /proyectos',
    str_contains($entrada['location'], '/proyectos'),
    true,
);

comprobar(
    'la respuesta entrega cookie de sesion',
    str_contains($entrada['cookies'], 'PHPSESSID'),
    true,
);

$sesion = $entrada['cookies'];

$protegida = pedir('/proyectos', $sesion);

comprobar(
    'con esa cookie una ruta protegida responde 200, no redirige a /login',
    $protegida['code'],
    200,
);

echo "\n=== Entrada con proyecto ===\n";

// El proyecto se deduce de la base en vez de fijarlo: las fixtures de CI y la base local
// no tienen los mismos proyectos, y un nombre hardcodeado haría fallar el test por un
// motivo que no es el que se está probando.
$proyecto = proyectoDe($usuario);

if ($proyecto === null) {
    echo "  SKIP {$usuario} no es miembro de ningun proyecto en esta base;\n";
    echo "       no se puede probar la entrada con proyecto (no es un fallo de la puerta)\n";
} else {
    $conProyecto = pedir('/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode($proyecto));

    comprobar(
        "entrar a '{$proyecto}' redirige fuera de /proyectos (aterriza en el modulo)",
        $conProyecto['code'] === 302 && !str_contains($conProyecto['location'], '/proyectos'),
        true,
    );

    $protegidaConProyecto = pedir('/proyectos', $conProyecto['cookies']);

    comprobar(
        'la sesion con proyecto tambien sirve en una ruta protegida',
        $protegidaConProyecto['code'],
        200,
    );
}

echo "\n=== Rechazos ===\n";

$fuera = pedir('/dev/entrar?u=usuario.que.no.esta.en.la.lista');

comprobar(
    'usuario fuera de DEV_DOOR_USERS => 404',
    $fuera['code'],
    404,
);

$sinUsuario = pedir('/dev/entrar');

comprobar(
    'sin parametro u => 404',
    $sinUsuario['code'],
    404,
);

echo "\n=== La ruta sigue siendo condicional en el front controller ===\n";

// Barato, pero atrapa el error que haría inútil todo el candado: que alguien saque el
// registro de la ruta fuera del if y quede activa en cualquier entorno.
$frontController = (string) file_get_contents(__DIR__ . '/../public/index.php');

preg_match('/if \(\$devDoorIsOpen\) \{\s*\$router->get\(\'\/dev\/entrar\'/', $frontController, $registroCondicional);

comprobar(
    'el $router->get(/dev/entrar) sigue dentro de if ($devDoorIsOpen)',
    $registroCondicional !== [],
    true,
);

comprobar(
    'la ruta se registra una sola vez',
    substr_count($frontController, "\$router->get('/dev/entrar'"),
    1,
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
