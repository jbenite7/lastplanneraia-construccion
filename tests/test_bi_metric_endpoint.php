<?php
declare(strict_types=1);
// @requiere: datos-proyecto

/**
 * Task 7 paso 5 (rol A, test writer): prueba HTTP completa del endpoint GENÉRICO que ejecuta una
 * métrica del catálogo — GET /api/bi/control-tower/metricas/{metricKey} — ANTES de que exista.
 * Debe fallar ahora (ruta inexistente) y quedar en verde cuando rol B implemente
 * `BiMetricController` en un paso separado. Ver:
 * .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-report.md (sección "Task 7 paso 5")
 *
 * --- Por qué existe este endpoint (D59) ---
 * Ruling del controlador antes de este paso (progress.md): D59 ("las dos lecturas del cero,
 * separadas y rotuladas") se cumple mostrando SOLO la adherencia (`pi_hard_restrictions_ready_rate`)
 * como cifra dura, correctamente rotulada — nunca una señal predictiva, que la propia spec ya
 * desestimó como evidencia débil. El gap real que bloqueaba eso: NINGÚN controlador invocaba
 * `MetricExecutor::execute()` todavía (confirmado por Task 7 paso 4 y por grep sobre `src/Controllers/`
 * — `MetricExecutor` solo se mencionaba en comentarios de `MetricDictionaryService.php`). Este
 * endpoint cierra ese gap con un contrato GENÉRICO (cualquier `metricKey` `ejecutable` del catálogo),
 * no uno atado a una sola métrica — el catálogo ya tiene 3 métricas `ejecutable` (Task 3) y crecerá.
 *
 * --- Ruta y controlador ---
 * `GET /api/bi/control-tower/metricas/{metricKey}` -> `BiMetricController::ejecutar(string $metricKey)`
 * (archivo NUEVO, `src/Controllers/Api/BiMetricController.php` — rol B lo crea). Hermano RESTful de
 * `BiConstraintListController`/`BiConstraintWriteController` (mismo prefijo `/api/bi/control-tower/`,
 * controlador propio, no vive en `BiControlTowerApiController`). Ruta a registrar en `public/index.php`
 * junto a las otras dos de este módulo (línea ~356-357):
 *   `$router->get('/api/bi/control-tower/metricas/{metricKey}', [\App\Controllers\Api\BiMetricController::class, 'ejecutar']);`
 *
 * --- RBAC de lectura: mismo gate de DOS niveles que BiConstraintListController (paso 3a) ---
 * Investigado (no reinventado): esta hoja (Intermedia) exige el mismo criterio que el listado de
 * restricciones — `BiPreviewAccessPolicy::canOpen($_SESSION)` (gate global del módulo BI, A/D/R en
 * CUALQUIER proyecto) + un SEGUNDO gate acotado con `RbacService::resolveCurrentRole()` +
 * `RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)` (A/D/R en el proyecto DE
 * SESIÓN). Denegado -> 404 ("Esta página no existe"), no 403: es lectura sin acción explícita del
 * usuario, mismo razonamiento que paso 3a (ver su test, no repetido aquí). `test.V` es el caso
 * denegado (rol V en los 4 proyectos donde es miembro, igual que en paso 3a — no hace falta el
 * usuario real fuera de `test.*` para este endpoint porque aquí NO hay un `{id}` cuyo aislamiento
 * cruzado dependa del rol-por-proyecto: el único parámetro es `metricKey`, que no es sensible a rol).
 *
 * --- Envelope: {ok:true, value, basis, completeness, missing} | {ok:false, error:{code,message}} ---
 * FLAT, no anidado bajo una clave `metric` — mirror exacto de `MetricResult` (PHP) y de la interfaz
 * `MetricResult` que YA EXISTE en `ct-app/src/lib/api.ts` (agregada especulativamente en una tarea
 * anterior con el comentario "Espejo de MetricResult... Tasks 2/5" — este paso es la primera vez que
 * algo la consume de verdad). Mismo criterio de error `{ok:false,error:{code,message}}` que los dos
 * hermanos (Task 5/7-3a): `ct-app/src/lib/api.ts` exige `body.ok` booleano o lanza `BAD_RESPONSE`.
 *
 * --- Aislamiento: MetricScope se construye con [$_SESSION['project_id']], NUNCA multi-proyecto ---
 * Mismo bug que ya se corrigió DOS veces en esta etapa (Task 5 Critical 1, Task 7 paso 3a Important 1):
 * usar una resolución de proyecto que no está acotada a la sesión activa. Aquí el riesgo análogo es
 * usar `BiProjectScope::resolve()`/`resolveProjectIds()` (multi-proyecto, pensado para los reportes
 * BI existentes que SÍ agregan portafolio) en vez de un `MetricScope` de UN SOLO proyecto — el de
 * sesión. El caso 2 de abajo prueba esto con dato real, no con un mock: `test.A` es miembro de 5
 * proyectos (73, 76, 990100, 27, 68); el valor de `pi_hard_restrictions_ready_rate` para SOLO el 73
 * en la semana usada difiere del valor agregado sobre los 5 (medido en dev: 0.5833... con
 * obras_esperadas=1 vs 0.0555... con obras_esperadas=5) — un endpoint que usara el scope sin acotar
 * lo delataría de inmediato, no por coincidencia numérica.
 *
 * --- `semana`: opcional, cae a $_SESSION['semana'] real de la puerta de servicio ---
 * `Titular.tsx`/`Intermedia.tsx` (pieza 2 de este mismo paso) NO tienen selector de semana en
 * ct-app — llaman a `getMetric(metricKey)` sin parámetro de semana. El contrato entonces necesita
 * un default sin que el cliente lo mande: `$_GET['semana'] ?? $_SESSION['semana'] ?? null` (mismo
 * patrón `??` de `BiControlTowerApiController`, sin el tercer fallback a `currentWeekBogota()` —
 * YAGNI: esta ruta no depende de `ControlTowerService`, y `null` es un `MetricScope` legítimo, no un
 * error — agrega sobre TODAS las semanas del proyecto, comportamiento ya soportado nativamente por
 * `MetricExecutor::buildWhereClause()`, que omite el filtro `Semana = ?` cuando `week() === null`).
 * `ProjectSelectorController::enterProject()` fija `$_SESSION['semana']` a la "semana de aterrizaje"
 * real (`ProjectLandingService`, depende de rol/área) — este test NO adivina qué semana es: la lee
 * en vivo del propio almacén de sesión (mismo mecanismo de subproceso PHP fijado al `session_id` que
 * usa `tests/test_bi_constraint_write.php::csrfTokenForSession()`, sin tocar el candado de DevDoor) y
 * arma el oráculo con esa semana real, exactamente igual a como lo haría el controlador.
 *
 * --- `metricKey` inexistente en el catálogo: 404, nunca un error PHP crudo ---
 * `MetricDictionaryService::getDefinition($metricKey)` devuelve `[]` para una clave desconocida —
 * `MetricExecutor::execute()` recién ahí lanzaría `RuntimeException`. El contrato de este endpoint
 * valida la existencia ANTES de llamar a `execute()` (`getDefinition() === []` -> 404 NOT_FOUND con
 * mensaje legible), en vez de dejar que la excepción del executor escape como error 500 sin
 * capturar — eso rompería el contrato `{ok:false,...}` que `ct-app/src/lib/api.ts` exige y expondría
 * un stack trace crudo. (Fuera de alcance de este test: una métrica que SÍ existe en el catálogo
 * pero sigue `descriptiva` -- `execute()` lanzaría igual por falta de forma SQL reconocida --
 * `pi_hard_restrictions_ready_rate` ya es `ejecutable` desde Task 3, así que este archivo no ejercita
 * ese caso; rol B debe igual envolver esa llamada en un try/catch para no dejar pasar un 500 crudo,
 * documentado como concern en el reporte, no como caso de prueba de este archivo.)
 *
 * --- Valor real medido en dev (2026-08-26), para que quien implemente sepa qué esperar ---
 * `pi_hard_restrictions_ready_rate`, proyecto 73 (Da Porto):
 *   semana=1 -> value=0.5833333333333334 (7/12), completeness=completa, basis={obras_incluidas:1,
 *     obras_esperadas:1, corte:<hoy>, filas_usadas:12}, missing=[].
 *   semana=2 -> value=0.4375 (7/16), completeness=completa, filas_usadas=16.
 *   sin semana (agregado) -> value=0.5, filas_usadas=28.
 * Este test NO hardcodea estos números como aserción — los recalcula en vivo con el mismo
 * `MetricExecutor`/`MetricScope` que usa Task 3 (oráculo, no snapshot), para no quedar desalineado si
 * el dato de dev cambia. Se documentan aquí solo como referencia de lectura para rol B.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricScope;

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';
const PROJECT_ID = 73; // project_id real de "Da Porto" (project_members, verificado, mismo fixture de Task 5/7-3a).
const METRIC_KEY = 'pi_hard_restrictions_ready_rate'; // 'ejecutable' desde Task 3, único caso concreto pedido por el brief.
const SEMANA_EXPLICITA = '1'; // semana real con datos en dev (12 filas), ver docblock.
const METRIC_KEY_INEXISTENTE = 'no_existe_esta_metrica_xyz';
// Fix ronda 1 (Important 1, revisión de spec+calidad): semana sin ninguna fila para el proyecto 73
// -- filasUsadas=0 fuerza completeness='insuficiente' y basis.obras_incluidas=0 <
// basis.obras_esperadas=1, que es exactamente la combinación donde
// MetricExecutor::buildMissing() devuelve el array MIXTO (lista + clave 'obras_sin_datos') que
// rompía el contrato `missing: string[]`. Verificado en vivo antes del fix: json_encode() daba
// {"0":"sin_filas_que_cumplan_los_filtros","obras_sin_datos":[73]}, un objeto, no un array.
const SEMANA_SIN_DATOS = '999';
// Fix ronda 1 (Important 2): métrica real, declarada 'descriptiva' en el catálogo
// (MetricDictionaryService.php), con aggregation_policy en prosa libre que MetricExecutor no
// reconoce -- el caso concreto que D59 exige bloquear por estado declarado, no por accidente del
// parser.
const METRIC_KEY_DESCRIPTIVA = 'ps_pac_expected';

/**
 * Login por la puerta de servicio (AGENTS.md). Nunca /login, nunca credenciales tecleadas.
 * Idéntico a `tests/test_bi_constraint_write.php::sesion()` — ver ese archivo para el porqué de
 * capturar el PHPSESSID directo del header `Set-Cookie` en vez del cookie jar en disco.
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
 * GET autenticado vía curl, con cookie jar persistente. Idéntico a
 * `tests/test_bi_constraint_list.php::getReq()`.
 *
 * @return array{0:int,1:string} [código HTTP, cuerpo]
 */
function getReq(string $url, string $jar): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => ['X-AIA-Expect-Json: 1'],
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/**
 * Lee `$_SESSION['semana']` de la sesión de servidor que ya abrió el dev door, SIN sesión HTTP
 * nueva, SIN cookie, sin tocar el candado de DevDoor — mismo mecanismo y mismo motivo que
 * `tests/test_bi_constraint_write.php::csrfTokenForSession()` (subproceso PHP fijado al mismo
 * `session_id`, mismo almacén de archivos que usa Apache en el contenedor `app`). Se usa aquí en vez
 * de adivinar la "semana de aterrizaje" que calcula `ProjectLandingService` (depende de rol/área,
 * lógica que este test no debe duplicar ni suponer).
 *
 * @return ?string null si la sesión no trae `semana` (no debería pasar tras un login real, pero se
 *                 distingue explícito de "0" en vez de tratarlos igual).
 */
function leerSemanaDeSesion(string $sessionId): ?string
{
    $script = '<?php '
        . 'session_id($argv[1]); '
        . '@session_start(); '
        . 'echo isset($_SESSION["semana"]) ? (string) $_SESSION["semana"] : "__NULL__"; '
        . 'session_write_close();';
    $tmp = tempnam(sys_get_temp_dir(), 'semana_sesion_');
    file_put_contents($tmp, $script);
    $salida = trim((string) shell_exec('php ' . escapeshellarg($tmp) . ' ' . escapeshellarg($sessionId) . ' 2>&1'));
    @unlink($tmp);
    if ($salida === '' ) {
        fwrite(STDERR, "ABORT: no se pudo leer \$_SESSION['semana'] (salida vacia)\n");
        exit(2);
    }
    return $salida === '__NULL__' ? null : $salida;
}

$db = Database::getInstance();
$executor = new MetricExecutor($db, new MetricDictionaryService());

// ---------------------------------------------------------------------------------- Fixtures -----

// Todos los proyectos de los que test.A es miembro — para el oráculo "sin acotar" del caso 2
// (aislamiento). Descubierto en tiempo de ejecución, no hardcodeado (igual que idAjeno/candidatoRbac
// en los hermanos de Task 5/7-3a): si la membresía de test.A cambia en un refresh de dev, el test
// sigue siendo válido.
$proyectosTestA = $db->query(
    "SELECT pm.project_id
     FROM project_members pm
     JOIN general_usuarios u ON u.id = pm.user_id
     WHERE u.usuario = 'test.A'
     ORDER BY pm.project_id",
)->fetchAll(PDO::FETCH_COLUMN);
$proyectosTestA = array_map('intval', $proyectosTestA);

if (!in_array(PROJECT_ID, $proyectosTestA, true) || count($proyectosTestA) < 2) {
    fwrite(STDERR, "ABORT: se esperaba test.A miembro de PROJECT_ID=" . PROJECT_ID . " y de al menos otro proyecto (para el oraculo sin acotar del caso 2); membresia real: " . implode(',', $proyectosTestA) . "\n");
    exit(2);
}

echo 'Fixtures: PROJECT_ID=' . PROJECT_ID . ' (Da Porto) | test.A es miembro de ' . count($proyectosTestA) . ' proyectos: ' . implode(',', $proyectosTestA) . "\n";

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// ------------------------------------------------------------------------------- Sesiones -----

[$jarA, $sidA] = sesion('test.A');
[$jarV, $sidV] = sesion('test.V');

// ---------------------------------------------- Caso 1: test.A, semana explícita -> 200 -----

$urlExplicita = BASE . '/api/bi/control-tower/metricas/' . METRIC_KEY . '?semana=' . SEMANA_EXPLICITA;
[$code1, $body1] = getReq($urlExplicita, $jarA);
$json1 = json_decode($body1, true);

$oraculo1 = $executor->execute(METRIC_KEY, new MetricScope([PROJECT_ID], null, null, SEMANA_EXPLICITA));

$check($code1 === 200, 'caso 1: test.A con ?semana=' . SEMANA_EXPLICITA . ' -> 200', "HTTP {$code1}, body: " . substr($body1, 0, 300));
$check(is_array($json1), 'caso 1: el cuerpo es JSON valido (no un error PHP crudo)', 'body: ' . substr($body1, 0, 300));

if (is_array($json1)) {
    $check(($json1['ok'] ?? null) === true, 'caso 1: envelope {ok:true}');
    $check(
        array_key_exists('value', $json1) && is_numeric($json1['value']) && $oraculo1->value() !== null
            && abs(((float) $json1['value']) - $oraculo1->value()) < 1e-9,
        'caso 1: value == oraculo de MetricExecutor para el mismo scope (no un snapshot hardcodeado)',
        'real: ' . var_export($json1['value'] ?? null, true) . ' | oraculo: ' . var_export($oraculo1->value(), true),
    );
    $check(
        ($json1['completeness'] ?? null) === $oraculo1->completeness(),
        'caso 1: completeness == oraculo',
        'real: ' . var_export($json1['completeness'] ?? null, true) . ' | oraculo: ' . $oraculo1->completeness(),
    );
    $basis1 = $json1['basis'] ?? null;
    $check(is_array($basis1), 'caso 1: basis es un objeto/array');
    if (is_array($basis1)) {
        foreach (['obras_incluidas', 'obras_esperadas', 'filas_usadas'] as $campo) {
            $check(
                array_key_exists($campo, $basis1) && $basis1[$campo] === $oraculo1->basis()[$campo],
                "caso 1: basis.{$campo} == oraculo",
                'real: ' . var_export($basis1[$campo] ?? null, true) . ' | oraculo: ' . var_export($oraculo1->basis()[$campo], true),
            );
        }
        $check(
            array_key_exists('corte', $basis1) && $basis1['corte'] === $oraculo1->basis()['corte'],
            'caso 1: basis.corte == oraculo (fecha de hoy, MetricScope::cutoff())',
        );
    }
    $check(
        array_key_exists('missing', $json1) && $json1['missing'] === $oraculo1->missing(),
        'caso 1: missing == oraculo (vacio, completeness=completa)',
        'real: ' . json_encode($json1['missing'] ?? null),
    );
}

// -------------------------------------- Caso 2: aislamiento por project_id, no sin acotar -----
// El riesgo nombrado por el dispatcher: que MetricScope se construya con TODOS los proyectos del
// rol (como BiProjectScope::resolve()/resolveProjectIds(), pensado para los reportes multi-proyecto
// existentes) en vez de [$_SESSION['project_id']]. Se prueba con dato real, no con un mock: el valor
// agregado sobre TODOS los proyectos de test.A es demostrablemente distinto del valor de un solo
// proyecto (medido en dev, ver docblock) — si el endpoint devolviera el primero, este caso lo agarra.

$oraculoSinAcotar = $executor->execute(METRIC_KEY, new MetricScope($proyectosTestA, null, null, SEMANA_EXPLICITA));

$check(
    $oraculo1->value() !== null && $oraculoSinAcotar->value() !== null
        && abs($oraculo1->value() - $oraculoSinAcotar->value()) > 0.01,
    'caso 2 (fixture): el oraculo de UN proyecto difiere del oraculo SIN ACOTAR (' . count($proyectosTestA) . ' proyectos) — no es una coincidencia numerica',
    'un proyecto: ' . var_export($oraculo1->value(), true) . ' | sin acotar: ' . var_export($oraculoSinAcotar->value(), true),
);

if (is_array($json1)) {
    $check(
        ($json1['basis']['obras_esperadas'] ?? null) === 1,
        'caso 2: basis.obras_esperadas del endpoint == 1 (solo el proyecto de sesion), no ' . count($proyectosTestA),
        'real: ' . var_export($json1['basis']['obras_esperadas'] ?? null, true),
    );
    $check(
        is_numeric($json1['value'] ?? null) && abs(((float) $json1['value']) - $oraculoSinAcotar->value()) > 0.01,
        'caso 2: el endpoint NO devuelve el valor del scope sin acotar (bug de aislamiento ya corregido 2 veces en esta etapa)',
        'real: ' . var_export($json1['value'] ?? null, true) . ' | sin acotar (lo que devolveria el bug): ' . var_export($oraculoSinAcotar->value(), true),
    );
}

// ------------------------------------ Caso 3: sin `semana` -> cae a $_SESSION['semana'] real -----
// Ruta real que usara `getMetric()` en ct-app (pieza 2 de este paso): sin selector de semana en la
// UI, Intermedia.tsx llama sin query param. La semana de aterrizaje real se lee de la sesion de
// servidor (leerSemanaDeSesion()), no se adivina.

$semanaSesionA = leerSemanaDeSesion($sidA);
$urlSinSemana = BASE . '/api/bi/control-tower/metricas/' . METRIC_KEY;
[$code3, $body3] = getReq($urlSinSemana, $jarA);
$json3 = json_decode($body3, true);

$oraculo3 = $executor->execute(METRIC_KEY, new MetricScope([PROJECT_ID], null, null, $semanaSesionA));

$check($code3 === 200, 'caso 3: test.A sin ?semana -> 200 (cae al default)', "HTTP {$code3}, body: " . substr($body3, 0, 300) . ' | semana de sesion real: ' . var_export($semanaSesionA, true));
if (is_array($json3)) {
    $check(($json3['ok'] ?? null) === true, 'caso 3: envelope {ok:true}');
    $valorEsperado3 = $oraculo3->value();
    $check(
        ($valorEsperado3 === null && ($json3['value'] ?? 'MISSING') === null)
            || (is_numeric($json3['value'] ?? null) && $valorEsperado3 !== null && abs(((float) $json3['value']) - $valorEsperado3) < 1e-9),
        'caso 3: value == oraculo con la semana REAL de sesion (' . var_export($semanaSesionA, true) . '), no null/0 fijo ni la semana explicita del caso 1',
        'real: ' . var_export($json3['value'] ?? null, true) . ' | oraculo: ' . var_export($valorEsperado3, true),
    );
}

// ------------------------------------------------------- Caso 4: test.V (denegado) -> 404 -----

[$code4, $body4] = getReq($urlExplicita, $jarV);
$json4 = json_decode($body4, true);

$check($code4 === 404, 'caso 4: test.V (rol V, sin PERM_INTERNAL_BI_PREVIEW) -> 404', "HTTP {$code4}, body: " . substr($body4, 0, 300));
$check(is_array($json4) && ($json4['ok'] ?? null) === false, 'caso 4: envelope {ok:false}');
$check(
    is_array($json4) && is_array($json4['error'] ?? null) && ($json4['error']['code'] ?? null) === 'NOT_FOUND',
    'caso 4: error.code == NOT_FOUND',
    'real: ' . var_export($json4['error']['code'] ?? null, true),
);

// ------------------------------------------- Caso 5: metricKey inexistente -> 404 claro -----

$urlInexistente = BASE . '/api/bi/control-tower/metricas/' . METRIC_KEY_INEXISTENTE;
[$code5, $body5] = getReq($urlInexistente, $jarA);
$json5 = json_decode($body5, true);

$check($code5 === 404, 'caso 5: metricKey inexistente -> 404', "HTTP {$code5}, body: " . substr($body5, 0, 300));
$check(is_array($json5), 'caso 5: el cuerpo es JSON valido (nunca un error PHP crudo: sin stack trace, sin HTML de Fatal error)', 'body: ' . substr($body5, 0, 300));
if (is_array($json5)) {
    $check(($json5['ok'] ?? null) === false, 'caso 5: envelope {ok:false}');
    $check(
        is_array($json5['error'] ?? null) && !empty($json5['error']['message'] ?? ''),
        'caso 5: error.message no vacio y legible',
        'real: ' . var_export($json5['error']['message'] ?? null, true),
    );
}
$check(
    stripos($body5, 'Fatal error') === false && stripos($body5, '<br') === false && stripos($body5, 'Stack trace') === false,
    'caso 5: sin señales de error PHP crudo en el cuerpo de la respuesta',
);

// ------------------------- Caso 6 (fix ronda 1, Important 1): missing en 'insuficiente' -----
// Semana sin ninguna fila para el proyecto de sesion -> completeness='insuficiente' con
// obras_incluidas=0 < obras_esperadas=1: la combinacion exacta donde
// MetricExecutor::buildMissing() trae el array mixto (lista + clave 'obras_sin_datos') que
// json_encode() serializaria como OBJETO si el controlador no lo normalizara antes de responder.

$urlSinDatos = BASE . '/api/bi/control-tower/metricas/' . METRIC_KEY . '?semana=' . SEMANA_SIN_DATOS;
[$code6, $body6] = getReq($urlSinDatos, $jarA);
$json6 = json_decode($body6, true);

$check($code6 === 200, 'caso 6: semana sin datos -> 200 (insuficiente no es un error)', "HTTP {$code6}, body: " . substr($body6, 0, 300));
if (is_array($json6)) {
    $check(($json6['ok'] ?? null) === true, 'caso 6: envelope {ok:true}');
    $check(
        array_key_exists('value', $json6) && $json6['value'] === null,
        'caso 6: value == null (sin filas, nunca un valor inventado)',
        // ?? trata null como "no seteado" (isset() da false con valores null) -- por eso se usa
        // array_key_exists() explicito en vez de (...['value'] ?? 'MISSING') === null, que
        // fallaria siempre que value SEA null (justo el caso que este check quiere confirmar).
        'real: ' . (array_key_exists('value', $json6) ? var_export($json6['value'], true) : 'CLAVE_AUSENTE'),
    );
    $check(($json6['completeness'] ?? null) === 'insuficiente', 'caso 6: completeness == insuficiente', 'real: ' . var_export($json6['completeness'] ?? null, true));
}
$check(
    str_contains($body6, '"missing":['),
    "caso 6: missing viaja como array JSON plano ('[', no '{') -- json_encode() de un array con clave string 'obras_sin_datos' serializaria un objeto sin el fix",
    'body: ' . substr($body6, 0, 400),
);
if (is_array($json6)) {
    $check(
        is_array($json6['missing'] ?? null) && array_is_list($json6['missing']),
        'caso 6: missing decodifica a una lista PHP (array_is_list), no a un array asociativo',
        'real: ' . var_export($json6['missing'] ?? null, true),
    );
    $check(
        ($json6['missing'] ?? null) === ['sin_filas_que_cumplan_los_filtros'],
        "caso 6: missing == ['sin_filas_que_cumplan_los_filtros'], sin la clave 'obras_sin_datos' colada adentro",
        'real: ' . var_export($json6['missing'] ?? null, true),
    );
    $check(
        ($json6['basis']['obras_sin_datos'] ?? null) === [PROJECT_ID],
        'caso 6: basis.obras_sin_datos == [' . PROJECT_ID . '] -- el dato no se pierde, se mueve a basis',
        'real: ' . var_export($json6['basis']['obras_sin_datos'] ?? null, true),
    );
}

// --------------------- Caso 7 (fix ronda 1, Important 2): métrica 'descriptiva' -> 422 -----
// ps_pac_expected existe en el catalogo pero esta declarada 'descriptiva' (no 'ejecutable'). El
// endpoint debe bloquearla por ese estado declarado, no por que MetricExecutor falle al no
// reconocer su aggregation_policy en prosa libre -- la invariante D59 no puede depender de un
// accidente del parser.

$urlDescriptiva = BASE . '/api/bi/control-tower/metricas/' . METRIC_KEY_DESCRIPTIVA;
[$code7, $body7] = getReq($urlDescriptiva, $jarA);
$json7 = json_decode($body7, true);

$check($code7 === 422, "caso 7: metricKey 'descriptiva' (" . METRIC_KEY_DESCRIPTIVA . ') -> 422', "HTTP {$code7}, body: " . substr($body7, 0, 300));
$check(is_array($json7), 'caso 7: el cuerpo es JSON valido (nunca un error PHP crudo)', 'body: ' . substr($body7, 0, 300));
if (is_array($json7)) {
    $check(($json7['ok'] ?? null) === false, 'caso 7: envelope {ok:false}');
    $check(
        ($json7['error']['code'] ?? null) === 'METRIC_NOT_EXECUTABLE',
        'caso 7: error.code == METRIC_NOT_EXECUTABLE',
        'real: ' . var_export($json7['error']['code'] ?? null, true),
    );
    $check(
        is_array($json7['error'] ?? null) && !empty($json7['error']['message'] ?? ''),
        'caso 7: error.message no vacio y legible',
        'real: ' . var_export($json7['error']['message'] ?? null, true),
    );
}
$check(
    stripos($body7, 'Fatal error') === false && stripos($body7, '<br') === false && stripos($body7, 'Stack trace') === false,
    'caso 7: sin señales de error PHP crudo en el cuerpo de la respuesta',
);

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);
