<?php

declare(strict_types=1);
// @requiere: http

/**
 * Contrato de la ruta oculta de mantenimiento (Tarea 12, S01).
 *
 * `MaintenanceMode::isActive()` bloquea el sitio entero con 503 salvo la ruta oculta;
 * `MaintenanceLoginController::show()` sirve el shell React (`SpaHostRenderer`) inyectando la
 * configuración de runtime en el HTML — nunca en el bundle; `MaintenanceLoginController::submit()`
 * replica exactamente las reglas de negocio que tenía `LoginController::maintenanceLogin()`
 * (credenciales válidas, cuenta activa, rol `A` global en un proyecto de Construcción activo),
 * con rechazo genérico (mismo 401, mismo cuerpo) para cualquier motivo.
 *
 * Los escenarios de rol permitido/denegado y cambio de contraseña pendiente NO pasan por HTTP
 * contra la base real: no hay forma de conocer la contraseña real de una cuenta de fixture sin
 * violar la restricción de este frente (nada de usuarios/credenciales/seeds, sin DDL/DML). En su
 * lugar se invoca `MaintenanceLoginController::submit()` directamente con `AuthenticationService`
 * y `Database` sustituidos por dobles mínimos — mismo principio de inyección que ya usa
 * `AuthApiController`/`AuthApiControllerTest` — sin tocar ninguna fila real. Solo lo que
 * depende del ciclo de vida completo de `public/index.php` (el gate 503, el shell servido en
 * GET, el rechazo por credenciales reales incorrectas) pasa por curl contra el servidor vivo.
 *
 * Nunca imprime el valor de `MaintenanceMode::SECRET_PATH` — solo lo usa para construir URLs.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Los `comprobar()` de más abajo imprimen a medida que corren — sin este buffer exterior, esa
// salida "envía cabeceras" a ojos de PHP y `session_start()`/`http_response_code()` de los
// escenarios de invocación directa (más abajo) fallan con "headers already sent" en vez de
// ejercer la rama que se quiere probar. Se vacía explícitamente al final del script.
ob_start();

use App\Controllers\Auth\MaintenanceLoginController;
use App\Core\MaintenanceMode;
use App\Services\Auth\AuthenticationService;

$fallos = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos;
    if ($condicion) {
        echo "OK: {$descripcion}\n";

        return;
    }
    $fallos++;
    echo "FALLO: {$descripcion}\n";
}

/**
 * @param array<string, mixed>|null $campos application/x-www-form-urlencoded si no es null.
 * @return array{codigo:int,cuerpo:string,ubicacion:?string,cabeceras:string}
 */
function requestForm(string $url, string $galletas, ?array $campos = null): array
{
    $ch = curl_init($url);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $galletas,
        CURLOPT_COOKIEFILE => $galletas,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($campos !== null) {
        $opciones[CURLOPT_POST] = true;
        $opciones[CURLOPT_POSTFIELDS] = http_build_query($campos);
    }
    curl_setopt_array($ch, $opciones);
    $respuesta = (string) curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamanoCabeceras = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $cabeceras = substr($respuesta, 0, $tamanoCabeceras);
    $cuerpo = substr($respuesta, $tamanoCabeceras);
    $ubicacion = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $cabeceras, $m) === 1) {
        $ubicacion = trim($m[1]);
    }

    return ['codigo' => $codigo, 'cuerpo' => $cuerpo, 'ubicacion' => $ubicacion, 'cabeceras' => $cabeceras];
}

/**
 * `Cache-Control: no-store` explícito de `SpaHostRenderer::render()`, con `max-age=0` — no el
 * `no-store, no-cache, must-revalidate` (sin `max-age=0`) que ya manda por su cuenta
 * `session.cache_limiter=nocache` en cuanto arranca la sesión, ANTES de que este controlador
 * corra. Sin el sufijo `max-age=0` como marca distintiva, esta comprobación no podría
 * distinguir el encabezado explícito de `SpaHostRenderer` del que ya pone PHP por defecto —
 * comprobado quitando la línea `header(...)` de `SpaHostRenderer::render()`: el valor "sin
 * max-age=0" seguía presente igual, por el límite de caché de sesión, y una comprobación que
 * solo mirara "no-store" se habría quedado en verde sin que el código bajo prueba hiciera nada.
 */
function tieneNoStoreExplicito(string $cabeceras): bool
{
    return preg_match('/^Cache-Control:.*no-store.*max-age=0/mi', $cabeceras) === 1;
}

/**
 * Variante con una cookie de sesión forjada (mismo mecanismo que
 * tests/test_api_auth_contract.php::sesionArtificial()), para el escenario "ya autenticado".
 *
 * @param array<string,mixed> $sesion
 */
function sesionArtificial(array $sesion): string
{
    $sessionId = bin2hex(random_bytes(16));
    $codigo = <<<'PHP'
touch(sys_get_temp_dir() . '/sess_' . $argv[1]);
chmod(sys_get_temp_dir() . '/sess_' . $argv[1], 0666);
session_id($argv[1]);
session_start();
$_SESSION = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
session_write_close();
PHP;
    $comando = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($codigo)
        . ' ' . escapeshellarg($sessionId)
        . ' ' . escapeshellarg(json_encode($sesion, JSON_THROW_ON_ERROR));
    exec($comando, $salida, $estado);
    if ($estado !== 0) {
        throw new RuntimeException('No se pudo preparar la sesión artificial para el contrato de mantenimiento.');
    }

    return "PHPSESSID={$sessionId}";
}

function borrarSesionForjada(string $cookie): void
{
    if (preg_match('/PHPSESSID=([a-f0-9]+)/', $cookie, $m) === 1) {
        @unlink(sys_get_temp_dir() . '/sess_' . $m[1]);
    }
}

/**
 * @return array{mode:string,action?:string,error?:bool,state?:string,csrfToken?:string}|null
 */
function configuracionInyectada(string $html): ?array
{
    if (preg_match('/<script id="aia-runtime-config" type="application\/json">(.*?)<\/script>/s', $html, $m) !== 1) {
        return null;
    }

    /** @var array<string,mixed>|null $decodificado */
    $decodificado = json_decode($m[1], true);

    return is_array($decodificado) ? $decodificado : null;
}

/**
 * Subclase testable: `redirect()` es la única vía observable para saber a dónde `submit()`
 * quiso redirigir sin depender de `headers_list()` (vacío bajo el SAPI CLI — comprobado: un
 * `header()` normal en un script de línea de comandos no deja rastro ahí).
 */
final class MaintenanceLoginControllerObservable extends MaintenanceLoginController
{
    public ?string $ubicacionRedirigida = null;
    public ?int $estadoRedirigido = null;

    protected function redirect(string $location, int $status): void
    {
        $this->ubicacionRedirigida = $location;
        $this->estadoRedirigido = $status;
        http_response_code($status);
    }
}

/**
 * Doble de `AuthenticationService`: devuelve un resultado de credenciales fijo sin tocar
 * `general_usuarios`. `beginAuthenticatedSession()`/`beginPasswordChange()` NO se sobreescriben
 * — corren de verdad, porque son las que hay que comprobar (que $_SESSION quede como se
 * espera), y solo tocan el array `$_SESSION` en memoria del propio proceso de este script.
 *
 * NOTA para quien algún día marque `AuthenticationService`/`Database` como `final`: este
 * archivo depende de que NO lo sean — es lo que le permite doblarlas sin PHPUnit
 * (`createStub`/`createMock`), mismo principio que ya sentó `AuthApiControllerTest`. Si se
 * vuelven `final`, este script deja de compilar y los escenarios de rol denegado/permitido/
 * cambio pendiente de más abajo hay que migrarlos a una clase PHPUnit con dobles reales.
 */
final class AuthenticationServiceDoble extends AuthenticationService
{
    /** @param array<string,mixed>|null $resultado */
    public function __construct(private ?array $resultado)
    {
    }

    public function verifyCredentials(string $usuario, string $password): ?array
    {
        return $this->resultado;
    }
}

/**
 * Doble de `Database`: solo sustituye `query()`, que es lo único que
 * `MaintenanceLoginController::userHasGlobalAdminRole()` usa. No llama al constructor privado
 * de `Database` — define el suyo propio, así que nunca abre una conexión real.
 */
final class DatabaseRolDoble extends \Database
{
    public function __construct(private bool $tieneRolAdminGlobal)
    {
    }

    public function query($sql, $params = [])
    {
        $tieneRol = $this->tieneRolAdminGlobal;

        return new class($tieneRol) {
            public function __construct(private bool $valor)
            {
            }

            public function fetchColumn()
            {
                return $this->valor ? 1 : 0;
            }
        };
    }
}

$base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
$archivoMantenimiento = PROJECT_ROOT . '/.maintenance';
$existiaAntes = file_exists($archivoMantenimiento);
$contenidoAnterior = $existiaAntes ? (string) file_get_contents($archivoMantenimiento) : null;
$sesionOriginal = $_SESSION ?? [];
$galletasAnonimo = tempnam(sys_get_temp_dir(), 'maint_');
$galletasSesionForjada = null;

if ($galletasAnonimo === false) {
    fwrite(STDERR, "ABORT: no se pudo crear el cookie jar temporal\n");
    exit(2);
}

try {
    // --- activar mantenimiento (con try/finally para restaurar exactamente) -------------

    file_put_contents($archivoMantenimiento, "contrato de prueba\n");
    comprobar('MaintenanceMode::isActive() ve el archivo recién creado', MaintenanceMode::isActive());

    // --- el gate 503 sigue cerrando el sitio público --------------------------------------

    $login503 = requestForm("{$base}/login", $galletasAnonimo);
    comprobar('GET /login responde 503 con mantenimiento activo', $login503['codigo'] === 503);

    // --- GET del host oculto: 200, sin error, runtime de configuración anonymous ----------

    $hostOculto = requestForm("{$base}" . MaintenanceMode::SECRET_PATH, $galletasAnonimo);
    $configuracion = configuracionInyectada($hostOculto['cuerpo']);
    comprobar(
        'GET del host oculto responde 200 con configuración de runtime válida (state=anonymous, error=false)',
        $hostOculto['codigo'] === 200
            && is_array($configuracion)
            && ($configuracion['mode'] ?? null) === 'maintenance'
            && ($configuracion['error'] ?? null) === false
            && ($configuracion['state'] ?? null) === 'anonymous'
            && is_string($configuracion['csrfToken'] ?? null)
            && preg_match('/^[a-f0-9]{64}$/', (string) $configuracion['csrfToken']) === 1,
    );

    $csrfAnonimo = is_array($configuracion) ? ($configuracion['csrfToken'] ?? null) : null;

    // La respuesta lleva un CSRF vivo — sin `no-store`, un intermediario de caché (producción
    // es hosting compartido) podría servirle esa misma copia, token incluido, a otra visita.
    comprobar(
        'GET del host oculto responde con Cache-Control: no-store (el CSRF que lleva no debe quedar cacheado)',
        tieneNoStoreExplicito($hostOculto['cabeceras']),
    );

    // --- POST con csrf inválido: rechazo genérico, mismo 401 que credenciales malas -------

    $csrfInvalido = requestForm("{$base}" . MaintenanceMode::SECRET_PATH, $galletasAnonimo, [
        'usuario' => 'quien-sea',
        'password' => 'lo-que-sea',
        'csrf_token' => 'token-que-no-coincide-con-la-sesion',
    ]);
    $configCsrfInvalido = configuracionInyectada($csrfInvalido['cuerpo']);
    comprobar(
        'POST con csrf inválido responde 401 genérico (error=true), sin distinguir la causa',
        $csrfInvalido['codigo'] === 401
            && is_array($configCsrfInvalido)
            && ($configCsrfInvalido['error'] ?? null) === true
            && ($configCsrfInvalido['state'] ?? null) === 'anonymous',
    );
    comprobar(
        'el rechazo genérico también responde con Cache-Control: no-store',
        tieneNoStoreExplicito($csrfInvalido['cabeceras']),
    );

    // --- POST con csrf válido pero credenciales reales incorrectas: mismo rechazo ---------
    // Usuario real de fixture (test.A), contraseña aleatoria generada en tiempo de ejecución —
    // nunca un secreto real, igual que tests/test_api_auth_contract.php.

    $credencialesMalas = requestForm("{$base}" . MaintenanceMode::SECRET_PATH, $galletasAnonimo, [
        'usuario' => 'test.A',
        'password' => bin2hex(random_bytes(24)),
        'csrf_token' => (string) $csrfAnonimo,
    ]);
    $configCredencialesMalas = configuracionInyectada($credencialesMalas['cuerpo']);
    comprobar(
        'POST con csrf válido y credenciales incorrectas responde 401 genérico, indistinguible del csrf inválido',
        $credencialesMalas['codigo'] === $csrfInvalido['codigo']
            && is_array($configCredencialesMalas)
            && $configCredencialesMalas['error'] === $configCsrfInvalido['error']
            && $configCredencialesMalas['state'] === $configCsrfInvalido['state'],
    );

    // --- ya autenticado (sesión forjada, sin login real): GET redirige a /proyectos -------

    $galletasSesionForjada = sesionArtificial(['usuario' => 'test.A', 'nombreUsuario' => 'Test A']);
    // La sesión forjada se manda vía cookie cruda, no vía jar: curl directo, sin requestForm().
    $ch = curl_init("{$base}" . MaintenanceMode::SECRET_PATH);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIE => $galletasSesionForjada,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $crudo = (string) curl_exec($ch);
    $codigoAutenticado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    comprobar(
        'GET del host oculto con sesión completa ya autenticada redirige (303) a /proyectos',
        in_array($codigoAutenticado, [301, 302, 303], true)
            && preg_match('#^Location:\s*/proyectos#mi', $crudo) === 1,
    );

    // --- reglas de negocio de submit(): rol denegado, sin tocar la base real --------------
    //
    // `CsrfTokenManager::validate()` (código real, no doblado) arranca sesión si no hay una
    // activa — si se dejara arrancar DENTRO de submit(), pisaría el `$_SESSION` que sembramos
    // a mano un instante antes. Se arranca aquí, UNA vez, para que `session_status()` ya sea
    // `PHP_SESSION_ACTIVE` cuando `submit()` la consulte y el array manual sobreviva intacto.
    $idSesionDirecta = null;
    if (session_status() === PHP_SESSION_NONE) {
        session_save_path(sys_get_temp_dir());
        @session_start();
        $idSesionDirecta = session_id() ?: null;
    }

    $accionDePrueba = '/ruta-de-prueba-no-es-el-secreto-real';

    $_SERVER['REQUEST_URI'] = $accionDePrueba;
    $_POST = ['usuario' => 'fixture', 'password' => 'cualquiera', 'csrf_token' => 'no-se-valida-en-este-doble'];
    $_SESSION = [];

    $controladorRolDenegado = new MaintenanceLoginControllerObservable(
        new DatabaseRolDoble(false),
        new AuthenticationServiceDoble(['usuario' => 'fixture', 'activo' => 1, 'force_password_change' => 0]),
    );

    // El CSRF real se valida contra sesión: sembramos el token esperado para aislar esta
    // comprobación de la del csrf y probar solo la rama de rol.
    $_SESSION['_csrf_tokens']['shell_maintenance_login'] = 'token-de-prueba';
    $_POST['csrf_token'] = 'token-de-prueba';

    ob_start();
    $controladorRolDenegado->submit();
    $salidaRolDenegado = (string) ob_get_clean();
    $configRolDenegado = configuracionInyectada($salidaRolDenegado);

    comprobar(
        'submit() con credenciales válidas pero SIN rol A global en Construcción rechaza genéricamente (401, error=true)',
        http_response_code() === 401
            && $controladorRolDenegado->ubicacionRedirigida === null
            && is_array($configRolDenegado)
            && $configRolDenegado['error'] === true
            && empty($_SESSION['maintenance_bypass']),
    );

    // --- rol permitido (A global en Construcción activa) -----------------------------------

    $_SERVER['REQUEST_URI'] = $accionDePrueba;
    $_POST = ['usuario' => 'fixture', 'password' => 'cualquiera', 'csrf_token' => 'token-de-prueba-2'];
    $_SESSION = ['_csrf_tokens' => ['shell_maintenance_login' => 'token-de-prueba-2']];

    $controladorPermitido = new MaintenanceLoginControllerObservable(
        new DatabaseRolDoble(true),
        new AuthenticationServiceDoble(['usuario' => 'fixture', 'activo' => 1, 'force_password_change' => 0]),
    );

    ob_start();
    $controladorPermitido->submit();
    ob_end_clean();

    comprobar(
        'submit() con rol A global en Construcción activa el bypass y redirige a /proyectos (303)',
        $controladorPermitido->ubicacionRedirigida === '/proyectos'
            && $controladorPermitido->estadoRedirigido === 303
            && ($_SESSION['maintenance_bypass'] ?? null) === true
            && ($_SESSION['usuario'] ?? null) === 'fixture',
    );

    // --- el bypass existe ANTES del redirect de cambio de clave pendiente ------------------

    $_SERVER['REQUEST_URI'] = $accionDePrueba;
    $_POST = ['usuario' => 'fixture', 'password' => 'cualquiera', 'csrf_token' => 'token-de-prueba-3'];
    $_SESSION = ['_csrf_tokens' => ['shell_maintenance_login' => 'token-de-prueba-3']];

    $controladorPendiente = new MaintenanceLoginControllerObservable(
        new DatabaseRolDoble(true),
        new AuthenticationServiceDoble(['usuario' => 'fixture', 'activo' => 1, 'force_password_change' => 1]),
    );

    ob_start();
    $controladorPendiente->submit();
    ob_end_clean();

    comprobar(
        'submit() con cambio de clave pendiente fija el bypass ANTES de redirigir a la misma acción oculta',
        ($_SESSION['maintenance_bypass'] ?? null) === true
            && ($_SESSION['must_change_password'] ?? null) === true
            && $controladorPendiente->ubicacionRedirigida === $accionDePrueba
            && $controladorPendiente->estadoRedirigido === 303,
    );

    // GET posterior (misma sesión, en memoria) debe ofrecer el runtime de cambio pendiente.
    ob_start();
    $controladorPendiente->show();
    $salidaPendiente = (string) ob_get_clean();
    $configPendiente = configuracionInyectada($salidaPendiente);

    comprobar(
        'GET posterior con cambio de clave pendiente sirve state=password_change_required',
        is_array($configPendiente)
            && ($configPendiente['state'] ?? null) === 'password_change_required'
            && is_string($configPendiente['csrfToken'] ?? null)
            && preg_match('/^[a-f0-9]{64}$/', (string) $configPendiente['csrfToken']) === 1,
    );

} finally {
    if ($existiaAntes) {
        file_put_contents($archivoMantenimiento, (string) $contenidoAnterior);
    } else {
        @unlink($archivoMantenimiento);
    }

    $_SESSION = $sesionOriginal;
    unset($_SERVER['REQUEST_URI'], $_POST);

    @unlink($galletasAnonimo);
    if ($galletasSesionForjada !== null) {
        borrarSesionForjada($galletasSesionForjada);
    }
    // `beginAuthenticatedSession()`/`beginPasswordChange()` regeneran el id de sesión (código
    // real): el id capturado al arrancar puede no ser el vigente al terminar. Se limpian los
    // dos, más el que esté activo en este instante, para no dejar ningún archivo de sesión
    // real suelto en el contenedor.
    if (isset($idSesionDirecta) && $idSesionDirecta !== null) {
        @unlink(sys_get_temp_dir() . '/sess_' . $idSesionDirecta);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $idSesionFinal = session_id();
        session_write_close();
        if (is_string($idSesionFinal) && $idSesionFinal !== '') {
            @unlink(sys_get_temp_dir() . '/sess_' . $idSesionFinal);
        }
    }
}

echo $fallos === 0 ? "OK: contrato de la ruta oculta de mantenimiento\n" : "{$fallos} fallo(s)\n";
$salidaCompleta = (string) ob_get_clean();
fwrite(STDOUT, $salidaCompleta);
exit($fallos === 0 ? 0 : 1);
