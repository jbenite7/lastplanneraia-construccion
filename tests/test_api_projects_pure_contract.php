<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Contrato puro (sin DB) de `ProjectApiController`: `index()` y `select()`.
 *
 * Garantías: la tarjeta de proyecto trae los seis campos que exige
 * `frontend/src/lib/api/esquemas/proyectos.ts` (`id`, `name`, `area`, `active`, `role`,
 * `roleLabel`) más `navigation.bi`; el body de selección se valida antes de tocar el servicio
 * (solo `{name}`, string, no vacío); el rechazo no enumera proyectos ajenos (inexistente y sin
 * permiso responden igual); una ruta de aterrizaje insegura nunca sale como éxito; y ninguna
 * respuesta filtra `Base_de_Datos`, `db` ni `Acceso`.
 *
 * Toda la autorización sigue en `ProjectAccessService`/`ProjectLandingService`: aquí se inyectan
 * dobles para poder correr sin base de datos. Ninguna prueba selecciona de verdad un proyecto.
 * Además contrasta los cuerpos de error reales contra `tests/fixtures/api-projects-error-bodies.json`
 * (lo lee `frontend/src/lib/api/esquemas/error.contrato.test.ts`); se regenera solo con
 * `LPS_REGENERAR_CUERPOS=1`.
 */

use App\Controllers\Api\ProjectApiController;
use App\Security\CsrfTokenManager;
use App\Services\ProjectAccessService;

require_once __DIR__ . '/../vendor/autoload.php';

const MENSAJE_RECHAZO = 'No se pudo acceder al proyecto seleccionado.';

final class ProjectAccessServiceFake extends ProjectAccessService
{
    /** @var list<array{0:string,1:string}> */
    public array $selectCalls = [];
    /** @var list<string> */
    public array $listCalls = [];

    /**
     * @param list<array<string,mixed>> $projects
     * @param array<string,mixed>|\Throwable $selection
     */
    public function __construct(
        private readonly array $projects = [],
        private readonly array|\Throwable $selection = ['success' => false, 'message' => 'x', 'route' => null],
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function listForUser(string $usuario): array
    {
        $this->listCalls[] = $usuario;

        return $this->projects;
    }

    /** @return array{success:bool,message:string|null,route:string|null} */
    public function select(string $usuario, string $proyectoSeleccionado): array
    {
        $this->selectCalls[] = [$usuario, $proyectoSeleccionado];
        if ($this->selection instanceof \Throwable) {
            throw $this->selection;
        }

        /** @var array{success:bool,message:string|null,route:string|null} $seleccion */
        $seleccion = $this->selection;

        return $seleccion;
    }
}

/**
 * @param array<string,mixed>|null $bi
 * @return array{0:int,1:array<string,mixed>|null,2:string}
 */
function ejecutarProyectos(
    string $method,
    ProjectAccessServiceFake $service,
    string $body = '',
    ?string $csrf = null,
    ?array $bi = ['visible' => false, 'href' => null],
    bool $biExplota = false,
): array {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf ?? '';
    http_response_code(200);
    ob_start();
    $resolver = static function () use ($bi, $biExplota): array {
        if ($biExplota) {
            throw new RuntimeException('BiAccessComponent SQLSTATE secreto');
        }

        /** @var array<string,mixed> $resuelto */
        $resuelto = $bi ?? [];

        return $resuelto;
    };
    (new ProjectApiController($service, static fn (): string => $body, $resolver))->{$method}();
    $raw = (string) ob_get_clean();

    return [(int) http_response_code(), json_decode($raw, true), $raw];
}

function checkProyectos(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

/**
 * Bloque `error` anidado que lee `frontend/src/lib/api/cliente.ts`; `campos` solo si hay campos y
 * nunca claves en `null`.
 *
 * @param array<string,mixed>|null $body
 */
function bloqueErrorProyectos(?array $body, string $code, ?string $campo): bool
{
    $error = $body['error'] ?? null;
    if (!is_array($error) || ($error['codigo'] ?? null) !== $code || ($error['mensaje'] ?? null) !== ($body['message'] ?? false)) {
        return false;
    }
    if (($body['success'] ?? null) !== false || ($body['code'] ?? null) !== $code) {
        return false;
    }
    if ($campo === null) {
        return !array_key_exists('campos', $error);
    }

    return is_array($error['campos'] ?? null)
        && array_keys($error['campos']) === [$campo]
        && is_string($error['campos'][$campo]);
}

function sinInternos(string $raw): bool
{
    foreach (['Base_de_Datos', 'Acceso', 'pdcActivo', '"db"', 'SQLSTATE', 'secreto'] as $fuga) {
        if (str_contains($raw, $fuga)) {
            return false;
        }
    }

    return true;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
$csrf = CsrfTokenManager::generate('shell_api');
$_SESSION['usuario'] = 'fixture';
$failures = 0;
$capturas = [];

// Buffer externo: sin él, el primer `echo` de checkProyectos() deja `headers_sent()` en true y los
// http_response_code() siguientes no surten efecto (ver test_api_password_reset_contract.php).
ob_start();

$filaProyecto = [
    'ID' => 73,
    'Proyecto_Proceso' => 'Da Porto',
    'Area' => 'Construccion',
    'Activo' => 1,
    'Acceso' => 1,
    'Base_de_Datos' => 'daporto_secreto',
    'permiso' => 'A',
    'rol_nombre' => 'Administrador',
];

// --- index() --------------------------------------------------------------------------------
$listado = new ProjectAccessServiceFake([$filaProyecto]);
[$s, $b, $raw] = ejecutarProyectos('index', $listado);
checkProyectos(
    $s === 200 && $b === [
        'projects' => [[
            'id' => 73,
            'name' => 'Da Porto',
            'area' => 'Construccion',
            'active' => true,
            'role' => 'A',
            'roleLabel' => 'Administrador',
        ]],
        'navigation' => ['bi' => ['visible' => false, 'href' => null]],
    ],
    'index: la tarjeta trae los seis campos del contrato y navigation.bi, sin claves extra',
);
checkProyectos($listado->listCalls === ['fixture'], 'index: un listForUser(fixture)');
checkProyectos(sinInternos($raw), 'index: no filtra Base_de_Datos, Acceso ni el prefijo de la DB');

[$s, $b] = ejecutarProyectos('index', new ProjectAccessServiceFake([$filaProyecto]), '', null, ['visible' => true, 'href' => '/bi/control-tower']);
checkProyectos(
    $s === 200 && ($b['navigation']['bi'] ?? null) === ['visible' => true, 'href' => '/bi/control-tower'],
    'index: BI visible emite su href interno',
);

// `visible=false` manda sobre el href: el resolver puede traer ruido, pero la respuesta se
// normaliza a la rama `{visible:false, href:null}` de la unión discriminada del cliente.
[$s, $b] = ejecutarProyectos('index', new ProjectAccessServiceFake([$filaProyecto]), '', null, ['visible' => false, 'href' => '/bi/control-tower']);
checkProyectos(
    $s === 200 && ($b['navigation']['bi'] ?? null) === ['visible' => false, 'href' => null],
    'index: BI invisible con href se normaliza a href null',
);

foreach ([
    'protocolo relativo' => ['visible' => true, 'href' => '//evil.example'],
    'absoluta externa' => ['visible' => true, 'href' => 'https://evil.example/bi'],
    'sin href' => ['visible' => true, 'href' => null],
    'href no string' => ['visible' => true, 'href' => 7],
    'salto de línea' => ['visible' => true, 'href' => "/bi\n/x"],
] as $etiqueta => $biRoto) {
    [$s, $b, $raw] = ejecutarProyectos('index', new ProjectAccessServiceFake([$filaProyecto]), '', null, $biRoto);
    checkProyectos(
        $s === 500 && bloqueErrorProyectos($b, 'invalid_navigation', null) && !array_key_exists('projects', $b ?? []),
        "index: BI inconsistente ({$etiqueta}) responde 500 invalid_navigation, no una lista 200",
    );
    checkProyectos(!str_contains($raw, 'evil.example'), "index: 500 ({$etiqueta}) no refleja el href inseguro");
    $capturas['500_invalid_navigation'] = ['ruta' => '/api/proyectos', 'status' => $s, 'raw' => $raw];
}

[$s, $b, $raw] = ejecutarProyectos('index', new ProjectAccessServiceFake([$filaProyecto]), '', null, null, true);
checkProyectos($s === 500 && bloqueErrorProyectos($b, 'invalid_navigation', null), 'index: resolver BI que explota responde 500 invalid_navigation');
checkProyectos(sinInternos($raw), 'index: el 500 del resolver no filtra la causa interna');

$vacio = new ProjectAccessServiceFake([]);
[$s, $b] = ejecutarProyectos('index', $vacio);
checkProyectos($s === 200 && ($b['projects'] ?? null) === [], 'index: sin membresías responde lista vacía, no error');

unset($_SESSION['usuario']);
$sinSesion = new ProjectAccessServiceFake([$filaProyecto]);
[$s, $b, $raw] = ejecutarProyectos('index', $sinSesion);
checkProyectos(
    $s === 401 && bloqueErrorProyectos($b, 'session_invalid', null) && $sinSesion->listCalls === [],
    'index: sin sesión responde 401 session_invalid sin llamar al servicio',
);
$capturas['401_session_invalid'] = ['ruta' => '/api/proyectos', 'status' => $s, 'raw' => $raw];
$_SESSION['usuario'] = 'fixture';

// --- select() -------------------------------------------------------------------------------
$exito = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => '/programacion-semanal']);
[$s, $b, $raw] = ejecutarProyectos('select', $exito, '{"name":"  Da Porto  "}', $csrf);
checkProyectos(
    $s === 200 && $b === ['success' => true, 'message' => null, 'route' => '/programacion-semanal'],
    'select: éxito responde la forma exacta con la ruta que decidió el servidor',
);
checkProyectos($exito->selectCalls === [['fixture', 'Da Porto']], 'select: un select(fixture, Da Porto) con el nombre recortado');
checkProyectos(sinInternos($raw), 'select: el éxito no filtra datos internos');

foreach (['/programacion-semanal/cic', '/programa-general', '/programa-general-actualizar', '/dashboard'] as $ruta) {
    $landing = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => $ruta]);
    [$s, $b] = ejecutarProyectos('select', $landing, '{"name":"Da Porto"}', $csrf);
    checkProyectos($s === 200 && ($b['route'] ?? null) === $ruta, "select: la ruta real {$ruta} pasa el contrato");
}

// El rechazo no es un error de transporte: es 200 con la forma que espera
// `EsquemaResultadoSeleccionProyecto` (strict), sin `code` ni bloque `error`.
$rechazos = [
    'sin membresía' => ['success' => false, 'message' => 'No tienes permiso para acceder a este proyecto.', 'route' => null],
    'inactivo para el perfil' => ['success' => false, 'message' => 'El proyecto seleccionado se encuentra inactivo para tu perfil.', 'route' => null],
];
$rawRechazo = null;
foreach ($rechazos as $etiqueta => $resultado) {
    $fake = new ProjectAccessServiceFake([], $resultado);
    [$s, $b, $raw] = ejecutarProyectos('select', $fake, '{"name":"Da Porto"}', $csrf);
    checkProyectos(
        $s === 200 && $b === ['success' => false, 'message' => MENSAJE_RECHAZO, 'route' => null],
        "select: rechazo ({$etiqueta}) responde 200 con el literal común y route null",
    );
    checkProyectos($rawRechazo === null || $rawRechazo === $raw, "select: rechazo ({$etiqueta}) byte a byte idéntico — no enumera proyectos ajenos");
    $rawRechazo ??= $raw;
}

foreach ([
    'protocolo relativo' => '//evil.example',
    'absoluta externa' => 'https://evil.example/panel',
    'relativa' => 'programacion-semanal',
    'con backslash' => '/programacion\\semanal',
    'vacía' => '',
] as $etiqueta => $rutaMala) {
    $roto = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => $rutaMala]);
    [$s, $b, $raw] = ejecutarProyectos('select', $roto, '{"name":"Da Porto"}', $csrf);
    checkProyectos(
        $s === 500 && bloqueErrorProyectos($b, 'invalid_landing', null) && ($b['success'] ?? null) === false,
        "select: aterrizaje inseguro ({$etiqueta}) responde 500 invalid_landing, nunca éxito",
    );
    checkProyectos(!str_contains($raw, 'evil.example'), "select: 500 ({$etiqueta}) no refleja la ruta insegura");
    $capturas['500_invalid_landing'] = ['ruta' => '/api/proyectos/seleccionar', 'status' => $s, 'raw' => $raw];
}

$rutaAusente = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => null]);
[$s, $b] = ejecutarProyectos('select', $rutaAusente, '{"name":"Da Porto"}', $csrf);
checkProyectos($s === 500 && bloqueErrorProyectos($b, 'invalid_landing', null), 'select: éxito sin ruta responde 500 invalid_landing');

$csrfMalo = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => '/programacion-semanal']);
[$s, $b, $raw] = ejecutarProyectos('select', $csrfMalo, '{"name":"Da Porto"}', 'csrf-invalido');
checkProyectos(
    $s === 403 && bloqueErrorProyectos($b, 'csrf_invalid', null) && $csrfMalo->selectCalls === [],
    'select: CSRF inválido responde 403 sin llamar al servicio',
);
$capturas['403_csrf_invalid'] = ['ruta' => '/api/proyectos/seleccionar', 'status' => $s, 'raw' => $raw];

$sinCsrf = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => '/programacion-semanal']);
[$s] = ejecutarProyectos('select', $sinCsrf, '{"name":"Da Porto"}', null);
checkProyectos($s === 403 && $sinCsrf->selectCalls === [], 'select: sin CSRF responde 403 sin llamar al servicio');

unset($_SESSION['usuario']);
$selectSinSesion = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => '/programacion-semanal']);
[$s, $b] = ejecutarProyectos('select', $selectSinSesion, '{"name":"Da Porto"}', $csrf);
checkProyectos(
    $s === 401 && bloqueErrorProyectos($b, 'session_invalid', null) && $selectSinSesion->selectCalls === [],
    'select: sin sesión responde 401 antes que CSRF y sin llamar al servicio',
);
$_SESSION['usuario'] = 'fixture';

foreach ([
    'JSON roto' => '{"name":',
    'vacío' => '',
    'lista' => '["Da Porto"]',
    'string' => '"Da Porto"',
    'nombre no string' => '{"name":73}',
    'nombre nulo' => '{"name":null}',
    'nombre vacío' => '{"name":"   "}',
    'sin name' => '{}',
    'clave extra project_id' => '{"name":"Da Porto","project_id":73}',
    'clave extra route' => '{"name":"Da Porto","route":"/admin"}',
] as $etiqueta => $cuerpo) {
    $forma = new ProjectAccessServiceFake([], ['success' => true, 'message' => null, 'route' => '/programacion-semanal']);
    [$s, $b, $raw] = ejecutarProyectos('select', $forma, $cuerpo, $csrf);
    checkProyectos(
        $s === 422 && bloqueErrorProyectos($b, 'validation_error', 'name') && $forma->selectCalls === [],
        "select: forma inválida ({$etiqueta}) responde 422 sin llamar al servicio",
    );
    checkProyectos(!str_contains($raw, '/admin') && !str_contains($raw, '73'), "select: 422 ({$etiqueta}) no refleja el body recibido");
    $capturas['422_validation_error'] = ['ruta' => '/api/proyectos/seleccionar', 'status' => $s, 'raw' => $raw];
}

// --- Contrato PHP↔Zod: cuerpos de error reales, render en proceso (origen render-puro) --------
ksort($capturas);
$actual = [];
foreach ($capturas as $caso => $captura) {
    $actual[$caso] = [
        'ruta' => $captura['ruta'],
        'status' => $captura['status'],
        'origen' => 'render-puro',
        'cuerpo' => json_decode($captura['raw'], false),
    ];
}
$archivo = __DIR__ . '/fixtures/api-projects-error-bodies.json';
$serializado = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
if (getenv('LPS_REGENERAR_CUERPOS') === '1') {
    file_put_contents($archivo, $serializado);
    echo "INFO: regenerado {$archivo}\n";
}
checkProyectos(
    is_file($archivo) && json_encode(json_decode((string) file_get_contents($archivo), false)) === json_encode(json_decode($serializado, false)),
    'los cuerpos de error coinciden con tests/fixtures/api-projects-error-bodies.json (regenerar con LPS_REGENERAR_CUERPOS=1 si el cambio es intencional)',
);

echo (string) ob_get_clean();
exit($failures === 0 ? 0 : 1);
