<?php
declare(strict_types=1);
// @requiere: datos-proyecto

/**
 * Task 7 paso 5-bis (rol A, test writer): prueba HTTP completa del endpoint DEDICADO del pareto
 * de restricciones no liberadas -- GET /api/bi/control-tower/restricciones/pareto -- ANTES de que
 * exista. Debe fallar ahora (ruta inexistente) y quedar en verde cuando rol B implemente
 * `BiRestrictionParetoController` (o el nombre que elija) en un paso separado. Ver:
 * .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/ (reporte de este paso, si el dispatcher
 * lo pide).
 *
 * --- Por que un endpoint aparte, no `/api/bi/control-tower/metricas/pi_restriction_pareto` ---
 * El catalogo (`MetricDictionaryService::buildCatalog()`, clave `pi_restriction_pareto`,
 * `src/Services/Bi/MetricDictionaryService.php:509-553`) marca esta metrica `estado_ejecucion =>
 * 'descriptiva'` A PROPOSITO: es una DISTRIBUCION por `restriction_type` (N filas), no un escalar,
 * y `MetricExecutor::execute()` esta atado arquitectonicamente a un solo `float|null`
 * (`MetricResult::value()`). `BiMetricController::ejecutar()` bloquea con 422 cualquier metrica
 * que no sea `ejecutable` -- ver su docblock y el chequeo `$estadoEjecucion !== 'ejecutable'`.
 * El propio catalogo dice la ruta a seguir en su `known_limitations`: "candidata a servirse
 * directo como lista en Task 7 (hoja de Intermedia), no via este motor de escalares". Este test
 * fija ese endpoint dedicado.
 *
 * --- Contrato ---
 * `GET /api/bi/control-tower/restricciones/pareto` -> controlador NUEVO (rol B lo crea, archivo
 * sugerido `src/Controllers/Api/BiRestrictionParetoController.php`). Hermano RESTful de
 * `BiConstraintListController`/`BiConstraintWriteController` (mismo prefijo de ruta, controlador
 * propio, mismo estilo de envelope `{ok:true,...}` / `{ok:false,error:{code,message}}`).
 *
 * --- RBAC: mismo gate de DOS niveles que BiConstraintListController/BiMetricController ---
 * 1. `BiPreviewAccessPolicy::canOpen($_SESSION)` (global, A/D/R en CUALQUIER proyecto) -> 404 si
 *    falla.
 * 2. `RbacService::resolveCurrentRole()` + `RbacManager::hasCapability($role,
 *    PERM_INTERNAL_BI_PREVIEW)` (acotado al proyecto de sesion activa) -> 404 si falla.
 * Es una lectura sin accion explicita del usuario -- mismo criterio de 404 (no 403) que el resto
 * del modulo BI, no un endpoint mas laxo ni mas estricto.
 *
 * --- Aislamiento: `[(int) $_SESSION['project_id']]` directo, NUNCA BiProjectScope ---
 * Mismo bug ya corregido tres veces en esta etapa (Task 5 Critical 1, Task 7 paso 3a Important 1,
 * prevencion en Task 7 paso 5) -- un solo `project_id` de sesion en el WHERE, nunca
 * `BiProjectScope::resolve()`/`resolveProjectIds()` (multi-proyecto, para portafolio).
 *
 * --- Fuente y filtro EXACTOS del catalogo ---
 * `execution_source: bi_pi_restricciones` (vista SQL, `database/bi/002_bi_pi_restricciones.sql`),
 * `filters: ['Titulo=0', 'is_ready=0', 'is_hard=1']`, agrupado por `restriction_type`, contado,
 * ordenado descendente por conteo -- refleja `formula: 'COUNT(*) GROUP BY restriction_type WHERE
 * is_ready=0 ORDER BY COUNT(*) DESC'`. `is_hard=1` excluye a proposito la rama de "shared
 * constraints" de la vista (siempre `is_hard=0` ahi, ver la vista): el pareto es solo sobre las 5
 * restricciones duras estructurales (D_y_E, Materiales, MdeO, Equipos, Predecesora), no sobre
 * restricciones compartidas gestionadas manualmente (esas ya tienen su propio endpoint, Task 5/7
 * paso 3a).
 *
 * --- `semana`: mismo default que BiMetricController/BiConstraintListController ---
 * `$_GET['semana'] ?? $_SESSION['semana'] ?? null`. Este test usa siempre `?semana=` explicito
 * (no depende de adivinar la semana de aterrizaje de la sesion) -- mismo patron que
 * `tests/test_bi_metric_endpoint.php` caso 1.
 *
 * --- Vocabulario de `restriction_type` -> `tipo`: SIN traduccion, valor crudo ---
 * Se buscaron mapeos legibles en `public/js/bi-spa.js` y `src/Services/Bi/*.php` (grep sobre
 * "Predecesora", "D_y_E", "MdeO"): no existe ningun diccionario de traduccion de estos 5 valores a
 * un nombre "bonito" en ningun lado del repo hoy -- solo aparecen como literales SQL/PHP en la
 * vista y en el propio catalogo. Este test fija el campo `tipo` como el valor CRUDO de
 * `restriction_type` ('D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'), tal como sale de
 * la vista. Si rol B o una revision posterior deciden traducir estos labels en el backend, es un
 * cambio de contrato que requiere tocar este test -- no se resuelve aqui por cuenta propia porque
 * ampliaria el alcance de este paso (brief: "si no existe, deja el valor crudo... y anotalo como
 * limite conocido, no inventes traducciones").
 *
 * --- Limite conocido: el gate 2 (RbacManager::hasCapability) no se ejerce aislado del gate 1 ---
 * Revision code-reviewer (Medium 2, 2026-08-26): `test.V` -- la unica cuenta "denegada" disponible
 * -- tiene rol V en LOS CUATRO proyectos donde es miembro (990100, 27, 68, 73; ver
 * `database/seeds/dev_test_users.php`, que siembra deliberadamente UN rol fijo por usuario de
 * prueba en TODOS sus proyectos). No existe hoy, entre `test.A/D/R/C/V`, ninguna cuenta con rol
 * MIXTO entre proyectos (verificado contra `project_members` el 2026-08-26: cada `test.*` tiene el
 * mismo `role` en cada fila). Por eso el caso 2/3 de abajo dispara el gate 1
 * (`BiPreviewAccessPolicy::canOpen()`, global) y el gate 2
 * (`RbacManager::hasCapability(..., PERM_INTERNAL_BI_PREVIEW)`, acotado al proyecto) A LA VEZ para
 * `test.V`, y este test NO puede distinguir cual de los dos disparo. Si una regresion cambiara
 * `resolveCurrentRole()` por `resolveRoleForUser()` (la MISMA regresion ya cometida y corregida dos
 * veces en esta etapa -- Task 5 Critical 1, Task 7 paso 3a Important 1 -- que resolveria el rol de
 * OTRO proyecto en vez del de sesion), esta suite seguiria en verde sin detectarla, porque `test.V`
 * ya falla en el gate 1 antes de llegar al 2.
 *
 * Montar el caso que aisla el gate 2 exige una cuenta con rol permitido en un proyecto y rol
 * denegado en otro -- eso implica editar `database/seeds/dev_test_users.php` (fixture COMPARTIDO
 * por toda la suite de test.* del repo, no propio de este archivo) o sembrar una fila nueva de
 * `project_members` fuera de ese seed (que el propio seed revierte en el siguiente
 * `ON DUPLICATE KEY UPDATE role = VALUES(role)`, asi que no sobrevive a un reseed). Ambas rutas
 * amplian fixtures fuera del alcance de esta ronda de correccion. Queda como limite documentado, no
 * como test inventado: si se decide cubrir esto, la seed de test.* necesita una cuenta con rol
 * mixto a proposito (o un proyecto dedicado de RBAC-testing), decision de producto/fixture que no
 * toca esta tarea.
 *
 * --- `basis`: forma minima, sin sobre-especificar ---
 * El brief pide `{filas_usadas: N, corte: "..."}` como ejemplo, no como contrato cerrado. Este
 * test verifica que `basis` sea un array con una clave `filas_usadas` (int) que coincida con la
 * suma de los conteos de `distribucion` -- no fija el contenido exacto de `corte` (string libre),
 * porque el catalogo no define su formato y no hay precedente en otro endpoint del modulo que fije
 * ese campo. Libertad de implementacion de rol B mientras `filas_usadas` cuadre.
 *
 * --- Datos reales medidos en dev (para fijar el oraculo) ---
 * Medidos con:
 *   docker compose exec app php -r '...SELECT restriction_type, COUNT(*) c FROM
 *   bi_pi_restricciones WHERE project_id=? AND Semana=? AND is_ready=0 AND is_hard=1 GROUP BY
 *   restriction_type ORDER BY c DESC...'
 * PROJECT_ID=73 (Da Porto), Semana=1:
 *   Predecesora=272, Materiales=271, D_y_E=267, Equipos=267, MdeO=266 (total 1343).
 *   Nota: D_y_E y Equipos empatan en 267 -- el ORDER BY COUNT(*) DESC no define un desempate entre
 *   ellos dos. Este test NO exige un orden estricto entre pares empatados: agrupa por conteo y
 *   verifica que dentro de cada grupo de conteo igual esten exactamente los tipos esperados, y que
 *   los conteos en si vengan en orden descendente global (ver `agruparPorConteo()`).
 * PROJECT_ID=73, Semana=3: 0 filas (ni la vista ni `programa_consolidado` tienen actividad en esa
 *   semana para este proyecto) -- caso 5, distribucion vacia.
 * PROJECT_ID=68 (mismo pool de test.A/test.V), Semana=1: D_y_E=1438, Predecesora=1438,
 *   Materiales=1437, Equipos=1391, MdeO=1391 (total 7095) -- usado solo para el aviso informativo
 *   de aislamiento (no hay `{id}` en la URL que permita "pedir" una fila ajena; el aislamiento se
 *   prueba comparando el TOTAL y cada conteo por tipo de PROJECT_ID contra el oraculo, que ya
 *   filtra por `project_id=73` -- si el endpoint no aislara, sumaria de mas y no cuadraria con el
 *   oraculo).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';      // Mismo fixture que Task 5/7 paso 3a: test.A y test.V son miembros los dos.
const PROJECT_ID = 73;
const SEMANA_CON_DATOS = '1';     // 1343 filas, 5 tipos, ver docblock.
const SEMANA_SIN_DATOS = '3';     // 0 filas para este proyecto -- ver docblock.

/**
 * Login por la puerta de servicio (AGENTS.md). Nunca /login, nunca credenciales tecleadas.
 * Identico a `tests/test_bi_constraint_list.php::sesion()`.
 *
 * @return array{0:string,1:string} [ruta al cookie jar, PHPSESSID de esta sesion]
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
 * GET autenticado via curl, con cookie jar persistente.
 *
 * @return array{0:int,1:string} [codigo HTTP, cuerpo]
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
 * Oraculo independiente: recalcula la distribucion desde la vista fuente (NO desde el endpoint),
 * exactamente con el filtro que declara el catalogo.
 *
 * @return array<string,int> tipo => conteo, ya ordenado desc por conteo (orden de PHP, estable)
 */
function oraculo($db, int $projectId, string $semana): array
{
    $filas = $db->query(
        'SELECT restriction_type, COUNT(*) AS c
         FROM bi_pi_restricciones
         WHERE project_id = ? AND Semana = ? AND is_ready = 0 AND is_hard = 1
         GROUP BY restriction_type
         ORDER BY c DESC',
        [$projectId, $semana],
    )->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($filas as $f) {
        $out[(string) $f['restriction_type']] = (int) $f['c'];
    }
    return $out;
}

/**
 * Agrupa una lista ordenada [tipo => conteo] en grupos por valor de conteo IGUAL, preservando el
 * orden descendente entre grupos. Sirve para comparar sin exigir un desempate exacto entre tipos
 * que empatan en conteo (ver docblock, nota sobre D_y_E/Equipos=267 en Semana=1).
 *
 * @param array<string,int> $mapa
 * @return list<array{conteo:int, tipos:list<string>}>
 */
function agruparPorConteo(array $mapa): array
{
    $grupos = [];
    foreach ($mapa as $tipo => $conteo) {
        $grupos[$conteo][] = $tipo;
    }
    krsort($grupos, SORT_NUMERIC);
    $out = [];
    foreach ($grupos as $conteo => $tipos) {
        sort($tipos);
        $out[] = ['conteo' => $conteo, 'tipos' => $tipos];
    }
    return $out;
}

$db = Database::getInstance();

// ------------------------------------------------------------------------- Sesiones -----------

[$jarA, ] = sesion('test.A');
[$jarV, ] = sesion('test.V');

$url = BASE . '/api/bi/control-tower/restricciones/pareto';

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// -------------------------------------------------- Caso 1: test.A (permitido) -> 200 ---------

$oraculo1 = oraculo($db, PROJECT_ID, SEMANA_CON_DATOS);
$totalEsperado = array_sum($oraculo1);

echo 'Fixtures: PROJECT_ID=' . PROJECT_ID . ' (Da Porto), semana=' . SEMANA_CON_DATOS
    . ', distribucion esperada=' . json_encode($oraculo1) . " (total {$totalEsperado})\n";

if ($totalEsperado === 0) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " semana=" . SEMANA_CON_DATOS . " no tiene filas is_ready=0/is_hard=1 -- fixture invalido\n");
    exit(2);
}

[$code1, $body1] = getReq($url . '?semana=' . SEMANA_CON_DATOS, $jarA);
$json1 = json_decode($body1, true);

$check($code1 === 200, 'caso 1: test.A (rol A, PERM_INTERNAL_BI_PREVIEW) -> 200', "HTTP {$code1}, body: " . substr($body1, 0, 300));
$check(is_array($json1) && ($json1['ok'] ?? null) === true, 'caso 1: envelope {ok:true}');

$distribucion = is_array($json1) ? ($json1['distribucion'] ?? null) : null;
$check(is_array($distribucion), 'caso 1: envelope trae "distribucion" (array)');
$check(is_array($json1) && is_array($json1['basis'] ?? null), 'caso 1: envelope trae "basis" (array)');

// --------------------------- Caso 1b: la distribucion cuadra contra el oraculo -----------------

if (is_array($distribucion)) {
    $mapaRecibido = [];
    foreach ($distribucion as $fila) {
        $check(is_array($fila) && array_key_exists('tipo', $fila) && array_key_exists('conteo', $fila), 'caso 1b: cada fila trae "tipo" y "conteo"', 'fila: ' . json_encode($fila));
        $mapaRecibido[(string) ($fila['tipo'] ?? '')] = (int) ($fila['conteo'] ?? -1);
        $check(is_int($fila['conteo'] ?? null), 'caso 1b: tipo=' . ($fila['tipo'] ?? '?') . ' conteo es int JSON (no string)');
    }

    $check(
        array_keys($mapaRecibido) !== [] && $mapaRecibido === $oraculo1,
        'caso 1b: mapa tipo=>conteo == oraculo (mismo conjunto de tipos y mismos conteos)',
        'recibido: ' . json_encode($mapaRecibido) . ' | esperado: ' . json_encode($oraculo1),
    );

    // Orden descendente por conteo, sin exigir desempate entre tipos de igual conteo (ver
    // docblock: D_y_E/Equipos empatan en 267 para este fixture).
    $conteosRecibidos = array_values($mapaRecibido);
    $conteosOrdenados = $conteosRecibidos;
    rsort($conteosOrdenados, SORT_NUMERIC);
    $check(
        $conteosRecibidos === $conteosOrdenados,
        'caso 1b: conteos vienen en orden descendente',
        'recibido: ' . json_encode($conteosRecibidos),
    );

    $gruposEsperados = agruparPorConteo($oraculo1);
    foreach ($gruposEsperados as $grupo) {
        $tiposDelGrupoRecibidos = [];
        foreach ($mapaRecibido as $tipo => $conteo) {
            if ($conteo === $grupo['conteo']) {
                $tiposDelGrupoRecibidos[] = $tipo;
            }
        }
        sort($tiposDelGrupoRecibidos);
        $check(
            $tiposDelGrupoRecibidos === $grupo['tipos'],
            "caso 1b: grupo de conteo={$grupo['conteo']} trae exactamente " . implode(',', $grupo['tipos']),
            'recibido: ' . implode(',', $tiposDelGrupoRecibidos),
        );
    }

    // -------------------------------- Caso 1c: basis.filas_usadas == suma de conteos ----------
    $basis = is_array($json1) ? ($json1['basis'] ?? []) : [];
    $filasUsadas = is_array($basis) ? ($basis['filas_usadas'] ?? null) : null;
    $check(
        $filasUsadas === $totalEsperado,
        'caso 1c: basis.filas_usadas == suma de conteos de distribucion',
        'recibido: ' . var_export($filasUsadas, true) . ' esperado: ' . var_export($totalEsperado, true),
    );
} else {
    $check(false, 'caso 1b/1c: comparacion contra oraculo -- omitida, "distribucion" no es un array');
}

// -------------------------------------------------- Caso 2/3: test.V (denegado) -> 404 --------
// test.V tiene rol V en LOS CUATRO proyectos donde es miembro (990100, 27, 68, 73 -- verificado en
// dev), asi que no hay diferencia observable entre "denegado en el proyecto de sesion" (segundo
// gate) y "bloqueo global de canOpen()" (primer gate) para este usuario: los dos disparan aqui a
// la vez. Cubre el caso 2 del brief (denegado, sin PERM_INTERNAL_BI_PREVIEW) y el caso 3
// (bloqueo global) con el mismo fixture -- igual que hace `test_bi_constraint_list.php` caso 4.

[$code2, $body2] = getReq($url . '?semana=' . SEMANA_CON_DATOS, $jarV);
$json2 = json_decode($body2, true);

$check($code2 === 404, 'caso 2/3: test.V (rol V, sin PERM_INTERNAL_BI_PREVIEW, y sin A/D/R en ningun proyecto) -> 404', "HTTP {$code2}, body: " . substr($body2, 0, 300));
$check(is_array($json2) && ($json2['ok'] ?? null) === false, 'caso 2/3: envelope {ok:false}');
$check(is_array($json2) && is_array($json2['error'] ?? null) && ($json2['error']['code'] ?? null) === 'NOT_FOUND', 'caso 2/3: error.code == NOT_FOUND', 'real: ' . var_export($json2['error']['code'] ?? null, true));

// -------------------------------------------------- Caso 4: aislamiento entre proyectos --------
// Sin `{id}` en la URL no hay forma de "pedir" una fila ajena (mismo razonamiento que
// `test_bi_constraint_list.php` caso 2/nota final). El aislamiento se prueba indirectamente: el
// oraculo YA filtra por project_id=73, y el caso 1b ya verifico que el mapa recibido cuadra
// EXACTAMENTE con ese oraculo (ni de mas -- fuga de otro proyecto, que infla los conteos -- ni de
// menos). PROJECT_ID=68, misma semana, tiene una distribucion real MUY distinta (D_y_E=1438,
// Predecesora=1438, Materiales=1437, Equipos=1391, MdeO=1391, total 7095) medida en dev: si el
// endpoint no aislara por `project_id` de sesion, el total recibido en el caso 1 se acercaria a
// 1343+7095 o a los conteos de 68, no a 1343 exacto. Se deja como asercion explicita adicional
// para que la intencion quede documentada en el propio test, no solo en el comentario.

$otroProyectoOraculo = oraculo($db, 68, SEMANA_CON_DATOS);
$check(
    is_array($distribucion) && $totalEsperado !== array_sum($otroProyectoOraculo),
    'caso 4: fixture de aislamiento -- PROJECT_ID=68 misma semana tiene un total distinto (' . array_sum($otroProyectoOraculo) . ') al de PROJECT_ID=73 (' . $totalEsperado . '), sirve para detectar fuga',
);
$check(
    is_array($distribucion) && ($totalEsperado === array_sum(array_column($distribucion, 'conteo'))),
    'caso 4: suma de conteos recibidos == total real de PROJECT_ID=73 (no incluye filas de PROJECT_ID=68)',
    'recibido: ' . (is_array($distribucion) ? array_sum(array_column($distribucion, 'conteo')) : 'N/A') . ' esperado: ' . $totalEsperado,
);

// -------------------------------------- Caso 5: semana sin restricciones -> [] no error --------

// Guardia de fixture (Low, revision code-reviewer 2026-08-26): si SEMANA_SIN_DATOS gana actividad
// algun dia, caso 5 dejaria de probar lo que dice probar y pasaria en verde por la razon
// equivocada -- misma logica que la guardia de SEMANA_CON_DATOS de arriba (linea ~268).
$oraculo5 = oraculo($db, PROJECT_ID, SEMANA_SIN_DATOS);
if (array_sum($oraculo5) !== 0) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " semana=" . SEMANA_SIN_DATOS . " ya tiene filas is_ready=0/is_hard=1 (" . array_sum($oraculo5) . ") -- fixture invalido para el caso 5, elige otra semana vacia\n");
    exit(2);
}

[$code5, $body5] = getReq($url . '?semana=' . SEMANA_SIN_DATOS, $jarA);
$json5 = json_decode($body5, true);

$check($code5 === 200, 'caso 5: semana=' . SEMANA_SIN_DATOS . ' sin filas -> 200 (no 404, no error)', "HTTP {$code5}, body: " . substr($body5, 0, 300));
$check(is_array($json5) && ($json5['ok'] ?? null) === true, 'caso 5: envelope {ok:true} incluso vacio');
$check(is_array($json5) && is_array($json5['distribucion'] ?? null) && $json5['distribucion'] === [], 'caso 5: distribucion == [] (array vacio, no null, no objeto)', 'real: ' . json_encode($json5['distribucion'] ?? null));

// ------------------------------- Caso 6: `semana` invalida -> 422, no reflejada cruda ------------
// Revision code-reviewer (Medium 1, 2026-08-26): `?semana[]=1` reflejaba "Semana Array, ..." y
// `?semana=1' OR '1'='1` daba 200 con datos de la semana 1 (MySQL coacciona el string contra la
// columna `Semana`, entera). Ninguno de los dos debe seguir dando 200 con un cuerpo que refleje la
// entrada cruda.

[$code6a, $body6a] = getReq($url . '?semana[]=1&semana[]=2', $jarA);
$json6a = json_decode($body6a, true);
$check($code6a === 422, 'caso 6a: semana[] (array) -> 422', "HTTP {$code6a}, body: " . substr($body6a, 0, 300));
$check(is_array($json6a) && ($json6a['ok'] ?? null) === false, 'caso 6a: envelope {ok:false}');

$semanaInyeccion = "1' OR '1'='1";
[$code6b, $body6b] = getReq($url . '?semana=' . urlencode($semanaInyeccion), $jarA);
$json6b = json_decode($body6b, true);
$check($code6b === 422, 'caso 6b: semana con comilla/operador SQL -> 422 (no 200, no reflejada)', "HTTP {$code6b}, body: " . substr($body6b, 0, 300));
$check(is_array($json6b) && ($json6b['ok'] ?? null) === false, 'caso 6b: envelope {ok:false}');
$check(!str_contains($body6b, $semanaInyeccion), 'caso 6b: el cuerpo NO refleja la entrada cruda', 'body: ' . substr($body6b, 0, 300));

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);
