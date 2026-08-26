<?php
declare(strict_types=1);
// @requiere: http

/**
 * Task 5 (rol A), paso 1: prueba HTTP completa del endpoint de escritura de la Torre —
 * POST /api/bi/control-tower/restricciones/{id}/gestion — ANTES de que exista. Debe fallar
 * ahora (paso 2: FAIL completo, ruta inexistente) y quedar en verde cuando rol B implemente
 * BiConstraintWriteController en el paso 3. Ver:
 * .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-5-brief.md
 *
 * Cinco aserciones exigidas por el brief (paso 1, verbatim):
 *   1) test.A (rol permitido) escribe {responsable, fechaCompromiso, estado} con CSRF válido
 *      -> 200, {ok:true, restriccion:{...}} incluyendo AsignadoPor/AsignadoEn actualizados.
 *   2) test.V (rol denegado) la misma petición -> 403.
 *   3) La misma petición sin token CSRF -> 403.
 *   4) La misma petición con un id que pertenece a OTRO project_id -> 404 (el aislamiento no
 *      revela existencia — nunca 403).
 *   5) Escribir -> recargar -> recuperar: el estado persistido coincide con lo escrito.
 *
 * --- Convención CSRF: evidencia, no la instrucción original del brief ---
 * El brief de esta tarea pedía explícitamente "no seguir el patrón ct-app" y calcar en cambio
 * el patrón JSON-mutación de src/Controllers/Api/Plan*.php (token en cuerpo/`_csrf_token` o
 * header, validado con un form-key propio del módulo). Se investigó ese patrón (CsrfTokenManager,
 * PlanComprasPaquetesController::guardEscritura()) y luego se contrastó contra evidencia dura ya
 * commiteada en este mismo repo:
 *   - ct-app/src/lib/api.ts (commit d2a587b6, "ct-app andamiaje React"): `postGestionRestriccion()`
 *     manda el CSRF en el header `X-CSRF-Token`, leído de `globalThis.__CT_BOOTSTRAP__.csrfToken`
 *     — el mismo bootstrap que `BiViewController::renderCtPiloto()` genera con
 *     `CsrfTokenManager::generate('ct_piloto')`. El comentario en el propio archivo dice:
 *     "Task 5 no define ese endpoint, así que el CSRF se lee directo de `__CT_BOOTSTRAP__`".
 *   - ct-app/src/lib/api.test.ts confirma el contrato de cable: header `X-CSRF-Token`,
 *     `credentials: 'same-origin'`, `Content-Type: application/json`, `X-AIA-Expect-Json: 1`,
 *     envelope `{ok:true, restriccion}` | `{ok:false, error:{code,message}}`.
 *   - docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md, entrada 10 (ruling del
 *     controlador sobre la revisión de Task 6): "el CSRF de la Torre usa la clave 'ct_piloto'
 *     (...) — Task 5 debe validar contra esa misma clave, no una genérica."
 * Dado que la Task 7 "consume tal cual" ese cliente (`postGestionRestriccion()`), validar contra
 * cualquier otra clave o formato garantizaría que la integración real fallara. Se sigue entonces
 * la clave 'ct_piloto' + header X-CSRF-Token — la parte de "no soy la página piloto" de la
 * instrucción original se respeta en el MECANISMO de esta prueba (nunca se scrapea HTML ni se
 * enciende CT_PILOTO; el token se genera en sesión vía un subproceso PHP aislado, ver
 * csrfTokenForSession() más abajo) y no en la clave del formulario, que la evidencia fija.
 *
 * --- CT_PILOTO sigue apagado en .env (precedente: entrada 11 del mismo plan) ---
 * Task 6 documentó que prender CT_PILOTO=1 para probar en vivo fue denegado por el sistema de
 * permisos de la sesión y que recrear el contenedor compartido exige una ventana coordinada que
 * no se justifica por una bandera que queda apagada. Este archivo no toca .env ni el contenedor:
 * genera el token 'ct_piloto' llamando a la función real `CsrfTokenManager::generate()` dentro
 * de un subproceso PHP fijado al mismo session_id que ya trae la cookie del dev door — mismo
 * almacén de sesión (session.save_handler=files, compartido por Apache y CLI en el contenedor
 * `app`), sin pasar por CT_PILOTO. Precedente ya existente en el repo para "subproceso PHP que
 * genera y valida CsrfTokenManager": tests/test_legacy_csrf_guard.php.
 *
 * --- Composite PK: 191 filas de pi_shared_constraints repiten Id entre proyectos ---
 * PRIMARY KEY (project_id, Id) — `Id` NO es único global (medido: Id=1 existe en los 4 proyectos
 * que hoy tienen filas: 68, 73, 74, 76). El caso 4 (aislamiento) no puede usar un Id cualquiera de
 * otro proyecto: si ese mismo Id también existiera en PROJECT_ID, la petición coincidiría con la
 * fila propia en vez de probar el aislamiento. Este archivo busca en tiempo de ejecución (solo
 * lectura) un Id que exista en FOREIGN_PROJECT_ID y NO exista en PROJECT_ID.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';   // Proyecto_Proceso compartido: test.A y test.V son miembros los dos.
const PROJECT_ID = 73;         // project_id real de "Da Porto" (project_members, verificado).
const FOREIGN_PROJECT_ID = 68; // otro proyecto real con filas en pi_shared_constraints.

/**
 * Login por la puerta de servicio (AGENTS.md). Nunca /login, nunca credenciales tecleadas.
 *
 * Captura el PHPSESSID final directo del header `Set-Cookie` de la respuesta (vía
 * CURLOPT_HEADERFUNCTION), en memoria — NO lo lee de vuelta del cookie jar en disco.
 * Confirmado con prueba manual: leer el jar en el MISMO proceso PHP justo después de
 * curl_close() puede no ver el contenido que libcurl ya escribió (un `stat()` en ese mismo
 * proceso reporta "No existe el archivo" un instante después de que un `cat` en un proceso
 * aparte, sobre la misma ruta, sí lo ve completo) — 20 reintentos con clearstatcache() de por
 * medio tampoco lo resolvieron. Capturar el id directo de la respuesta evita ese timing por
 * completo: nunca depende de cuándo libcurl decide volcar el jar a disco. Si el login redirige
 * dos veces (regenerar el id de sesión tras autenticar es un patrón legítimo anti-fijación), el
 * callback se dispara una vez por cada `Set-Cookie` y se queda con el ÚLTIMO — el mismo que
 * curl reenvía en la petición siguiente.
 *
 * @return array{0:string,1:string} [ruta al cookie jar, PHPSESSID de esta sesión]
 */
function sesion(string $usuario): array
{
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);

    $sid = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => ['X-AIA-Expect-Json: 1'],
        CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $header) use (&$sid): int {
            if (preg_match('/^Set-Cookie:\s*PHPSESSID=([^;]+)/i', $header, $m)) {
                $sid = $m[1];
            }
            return strlen($header);
        },
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada para {$usuario} (HTTP {$code}). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    if ($sid === null) {
        fwrite(STDERR, "ABORT: el login de {$usuario} no devolvio cookie PHPSESSID\n");
        exit(2);
    }

    return [$jar, $sid];
}

/**
 * Petición HTTP genérica vía curl, con cookie jar persistente y headers extra.
 * $jsonBody === null hace un GET; cualquier otro valor (incluida cadena vacía) hace un POST con
 * ese cuerpo y Content-Type: application/json.
 *
 * @param list<string> $extraHeaders
 * @return array{0:int,1:string} [código HTTP, cuerpo]
 */
function curlReq(string $url, ?string $jsonBody, string $jar, array $extraHeaders = []): array
{
    $headers = array_merge(['X-AIA-Expect-Json: 1'], $extraHeaders);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ];
    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $jsonBody;
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;

    $ch = curl_init($url);
    curl_setopt_array($ch, $opts);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/**
 * Genera un token CSRF real para el form-key 'ct_piloto' DENTRO de la sesión de servidor que ya
 * abrió el dev door — sin pasar por CT_PILOTO ni por HTML. Corre CsrfTokenManager::generate() en
 * un subproceso PHP fijado al mismo session_id (mismo almacén de archivos que usa Apache en el
 * contenedor `app`), para no interferir con las sesiones PHP que este mismo script abre más
 * adelante via Database (Database::query() llama a @session_start() en algunos métodos).
 */
function csrfTokenForSession(string $sessionId): string
{
    $script = '<?php '
        . 'require "/var/www/html/vendor/autoload.php"; '
        . 'session_id($argv[1]); '
        . '@session_start(); '
        . 'echo \App\Security\CsrfTokenManager::generate("ct_piloto"); '
        . 'session_write_close();';
    $tmp = tempnam(sys_get_temp_dir(), 'csrf_gen_');
    file_put_contents($tmp, $script);
    $token = trim((string) shell_exec('php ' . escapeshellarg($tmp) . ' ' . escapeshellarg($sessionId) . ' 2>&1'));
    @unlink($tmp);
    if (strlen($token) < 32) {
        fwrite(STDERR, "ABORT: no se pudo generar el token CSRF ct_piloto (salida: {$token})\n");
        exit(2);
    }
    return $token;
}

$db = Database::getInstance();

// ---------------------------------------------------------------- Fixtures (solo lectura) -----

// Dos filas propias de PROJECT_ID en estado 'sin_gestionar' y sin datos de gestion: una para el
// caso 1 (escritura válida) + caso 5 (recarga/recupera), otra para el caso 3 (sin CSRF) —
// separadas para que un caso no interfiera con el estado que verifica el otro.
$propias = $db->query(
    "SELECT Id, Semana, Restriccion, EstadoLiberacion, ResponsableAsignado, FechaCompromiso, AsignadoPor, AsignadoEn
     FROM pi_shared_constraints
     WHERE project_id = ? AND EstadoLiberacion = 'sin_gestionar'
     ORDER BY Id LIMIT 2",
    [PROJECT_ID],
)->fetchAll(PDO::FETCH_ASSOC);

if (count($propias) < 2) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " no tiene 2 filas 'sin_gestionar' en pi_shared_constraints para fixtures\n");
    exit(2);
}
[$filaEscritura, $filaSinCsrf] = $propias;
$idEscritura = (int) $filaEscritura['Id'];
$idSinCsrf = (int) $filaSinCsrf['Id'];

// Un Id que exista en FOREIGN_PROJECT_ID pero NO en PROJECT_ID (composite PK, ver cabecera).
$filaAjena = $db->query(
    "SELECT Id, Semana, Restriccion, EstadoLiberacion, ResponsableAsignado, FechaCompromiso, AsignadoPor, AsignadoEn
     FROM pi_shared_constraints
     WHERE project_id = ?
       AND Id NOT IN (SELECT Id FROM pi_shared_constraints WHERE project_id = ?)
     ORDER BY Id LIMIT 1",
    [FOREIGN_PROJECT_ID, PROJECT_ID],
)->fetch(PDO::FETCH_ASSOC);

if ($filaAjena === false) {
    fwrite(STDERR, "ABORT: no se encontro un Id de FOREIGN_PROJECT_ID=" . FOREIGN_PROJECT_ID . " ausente en PROJECT_ID=" . PROJECT_ID . "\n");
    exit(2);
}
$idAjeno = (int) $filaAjena['Id'];

echo "Fixtures: PROJECT_ID=" . PROJECT_ID . " (Da Porto) idEscritura={$idEscritura} idSinCsrf={$idSinCsrf}"
    . " | FOREIGN_PROJECT_ID=" . FOREIGN_PROJECT_ID . " idAjeno={$idAjeno}\n";

// ------------------------------------------------------------------------- Sesiones + CSRF -----

[$jarA, $sidA] = sesion('test.A');
$tokenA = csrfTokenForSession($sidA);

[$jarV, $sidV] = sesion('test.V');
$tokenV = csrfTokenForSession($sidV);

$fechaCompromiso = (new DateTimeImmutable('+14 days'))->format('Y-m-d');
$responsable = 'QA Task 5';
$payload = json_encode([
    'responsable' => $responsable,
    'fechaCompromiso' => $fechaCompromiso,
    'estado' => 'en_gestion',
], JSON_THROW_ON_ERROR);

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// ------------------------------------------------------------- Caso 1: test.A escribe -> 200 ---

$urlEscritura = BASE . '/api/bi/control-tower/restricciones/' . $idEscritura . '/gestion';
[$code1, $body1] = curlReq($urlEscritura, $payload, $jarA, ['X-CSRF-Token: ' . $tokenA]);
$json1 = json_decode($body1, true);

$check($code1 === 200, 'caso 1: test.A con CSRF valido -> 200', "HTTP {$code1}, body: " . substr($body1, 0, 300));
$check(is_array($json1) && ($json1['ok'] ?? null) === true, 'caso 1: envelope {ok:true}');
$restriccion1 = is_array($json1) ? ($json1['restriccion'] ?? null) : null;
$check(is_array($restriccion1), 'caso 1: envelope trae "restriccion"');
if (is_array($restriccion1)) {
    $check(
        array_key_exists('AsignadoPor', $restriccion1) && $restriccion1['AsignadoPor'] === 'test.A',
        'caso 1: restriccion.AsignadoPor === test.A',
        'real: ' . var_export($restriccion1['AsignadoPor'] ?? null, true),
    );
    $check(
        array_key_exists('AsignadoEn', $restriccion1) && !empty($restriccion1['AsignadoEn']),
        'caso 1: restriccion.AsignadoEn no vacio',
        'real: ' . var_export($restriccion1['AsignadoEn'] ?? null, true),
    );
}

// --------------------------------------------------------- Caso 2: test.V (denegado) -> 403 ---

[$code2, $body2] = curlReq($urlEscritura, $payload, $jarV, ['X-CSRF-Token: ' . $tokenV]);
$check($code2 === 403, 'caso 2: test.V (rol denegado) -> 403', "HTTP {$code2}, body: " . substr($body2, 0, 300));

// ------------------------------------------------------------- Caso 3: sin token CSRF -> 403 ---

$urlSinCsrf = BASE . '/api/bi/control-tower/restricciones/' . $idSinCsrf . '/gestion';
[$code3, $body3] = curlReq($urlSinCsrf, $payload, $jarA, []); // sin header X-CSRF-Token
$check($code3 === 403, 'caso 3: test.A sin token CSRF -> 403', "HTTP {$code3}, body: " . substr($body3, 0, 300));

// -------------------------------------------------- Caso 4: id de OTRO project_id -> 404 -----

$urlAjeno = BASE . '/api/bi/control-tower/restricciones/' . $idAjeno . '/gestion';
[$code4, $body4] = curlReq($urlAjeno, $payload, $jarA, ['X-CSRF-Token: ' . $tokenA]);
$check($code4 === 404, 'caso 4: id de otro project_id -> 404 (no 403)', "HTTP {$code4}, body: " . substr($body4, 0, 300));

// -------------------------------------------- Caso 5: escribir -> recargar -> recuperar -----
// "Recargar" es una lectura fresca e independiente: no hay endpoint GET-por-id en el alcance de
// esta tarea (el brief solo agrega una ruta POST), asi que la recarga es un SELECT nuevo contra
// la base — la misma tecnica que usan tests/test_bitacora_avance_endpoint.php y
// tests/test_constraints_gestion_schema.php para probar persistencia real tras un endpoint HTTP.

$fresca = $db->query(
    "SELECT EstadoLiberacion, ResponsableAsignado, FechaCompromiso, AsignadoPor, AsignadoEn
     FROM pi_shared_constraints WHERE project_id = ? AND Id = ?",
    [PROJECT_ID, $idEscritura],
)->fetch(PDO::FETCH_ASSOC);

$check(
    $fresca !== false && $fresca['EstadoLiberacion'] === 'en_gestion',
    'caso 5: EstadoLiberacion persistido == en_gestion',
    'real: ' . var_export($fresca['EstadoLiberacion'] ?? null, true),
);
$check(
    $fresca !== false && $fresca['ResponsableAsignado'] === $responsable,
    'caso 5: ResponsableAsignado persistido == lo escrito',
    'real: ' . var_export($fresca['ResponsableAsignado'] ?? null, true),
);
$check(
    $fresca !== false && $fresca['FechaCompromiso'] === $fechaCompromiso,
    'caso 5: FechaCompromiso persistida == lo escrito',
    'real: ' . var_export($fresca['FechaCompromiso'] ?? null, true),
);
$check(
    $fresca !== false && $fresca['AsignadoPor'] === 'test.A',
    'caso 5: AsignadoPor persistido == test.A',
    'real: ' . var_export($fresca['AsignadoPor'] ?? null, true),
);
$check(
    $fresca !== false && !empty($fresca['AsignadoEn'])
        && abs(time() - strtotime((string) $fresca['AsignadoEn'])) < 300,
    'caso 5: AsignadoEn persistido y reciente (<5 min)',
    'real: ' . var_export($fresca['AsignadoEn'] ?? null, true),
);

// ------------------------------------------------------------------------------- Cleanup -----
// Deja las tres filas exactamente como estaban antes de correr esta prueba, pase o falle.

foreach ([[PROJECT_ID, $idEscritura, $filaEscritura], [PROJECT_ID, $idSinCsrf, $filaSinCsrf], [FOREIGN_PROJECT_ID, $idAjeno, $filaAjena]] as [$pid, $id, $original]) {
    $db->query(
        "UPDATE pi_shared_constraints
         SET EstadoLiberacion = ?, ResponsableAsignado = ?, FechaCompromiso = ?, AsignadoPor = ?, AsignadoEn = ?
         WHERE project_id = ? AND Id = ?",
        [
            $original['EstadoLiberacion'],
            $original['ResponsableAsignado'] === '' ? null : $original['ResponsableAsignado'],
            $original['FechaCompromiso'] === '' ? null : $original['FechaCompromiso'],
            $original['AsignadoPor'] === '' ? null : $original['AsignadoPor'],
            $original['AsignadoEn'] === '' ? null : $original['AsignadoEn'],
            $pid,
            $id,
        ],
    );
}

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);
