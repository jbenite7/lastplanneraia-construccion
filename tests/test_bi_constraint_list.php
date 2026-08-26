<?php
declare(strict_types=1);
// @requiere: datos-proyecto

/**
 * Task 7 paso 3a (rol A, test writer): prueba HTTP completa del endpoint de LECTURA de la lista
 * de restricciones de la Torre — GET /api/bi/control-tower/restricciones — ANTES de que exista.
 * Debe fallar ahora (ruta inexistente) y quedar en verde cuando rol B implemente
 * `BiConstraintListController` en un paso separado. Ver:
 * .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-paso3a-report.md
 *
 * Este archivo fija el CONTRATO del endpoint (rol A tiene libertad de diseño aquí — el
 * dispatcher lo dejó explícito: "es un contrato nuevo, no uno que otro código ya consuma"). El
 * detalle completo de cada decisión vive en el reporte de arriba; resumen aquí:
 *
 * --- Ruta y controlador ---
 * `GET /api/bi/control-tower/restricciones` -> `BiConstraintListController::listar()` (archivo
 * NUEVO, `src/Controllers/Api/BiConstraintListController.php` — rol B lo crea). Hermano RESTful
 * de `POST /api/bi/control-tower/restricciones/{id}/gestion` (Task 5, `BiConstraintWriteController`),
 * mismo prefijo de ruta, controlador separado (igual que el POST, que tampoco vive en
 * `BiControlTowerApiController`). NO se toca `ControlTowerService::fetchIntermedia()` ni
 * `/api/bi/report/intermedia` — ese endpoint es compartido por las 7 hojas legacy (ruling del
 * controlador, entrada 10 de la bitácora del plan) y `fetchIntermedia()` hace `SELECT *` sin
 * agregación, insuficiente para "actividades encadenadas" de todas formas.
 *
 * --- RBAC de lectura: PERM_INTERNAL_BI_PREVIEW (A/D/R), denegado -> 404, no 403 ---
 * Investigado en `src/Controllers/Api/BiControlTowerApiController.php`: TODOS los GET de ese
 * controlador (intermedia(), semanal(), pdc(), etc.) comparten un único gate en el constructor —
 * `BiPreviewAccessPolicy::canOpen($_SESSION)` — que exige `PERM_INTERNAL_BI_PREVIEW`
 * (`RbacManager::getCapabilities()`: `$isSystemAdmin || $isDirector || $isResident`, roles A/D/R
 * únicamente) y responde 404 ("Esta página no existe") a cualquier otro rol, incluido V. Esta
 * hoja (Intermedia) ES una de esas hojas: mismo criterio, no uno más laxo ni más estricto —
 * ningún rol fuera de A/D/R puede abrir la hoja para empezar, así que no tiene sentido que su
 * endpoint de datos sea más permisivo, y usar `canEditConstraints` (A,D,R,DCV,S,G,SG,OT — un
 * conjunto MÁS AMPLIO que incluye roles que ni siquiera pueden abrir el módulo BI) sería
 * incoherente para una lectura.
 *
 * A diferencia de Task 5 (POST, que deliberadamente NO reutiliza `BiPreviewAccessPolicy::canOpen()`
 * porque necesita 403 para un intento de escritura denegado), esta lectura SÍ replica ese mismo
 * criterio de 404: es exactamente la semántica ya establecida por los otros GET del módulo ("esta
 * página no existe para tu rol"), y aquí no hay razón para diferir de esa convención porque no hay
 * una acción explícita del usuario que justifique un 403 distinto — es la carga inicial de la
 * hoja.
 *
 * --- Envelope: {ok:true,...} | {ok:false,error:{code,message}}, NO ErrorPage::render() ---
 * El status 404 replica el de `BiPreviewAccessPolicy`, pero el CUERPO no reutiliza
 * `ErrorPage::render()` (que devuelve `{"error":{"codigo","mensaje"}}`, sin `ok`, para /api/*) —
 * es el mismo problema que forzó a Task 5 a definir su propio envelope: `ct-app/src/lib/api.ts`
 * exige `body.ok` booleano o lanza `BAD_RESPONSE`. Este endpoint y el POST de Task 5 comparten
 * cliente (`api.ts`) y usuario final (`ct-app`), así que comparten el MISMO envelope
 * `{ok:false,error:{code,message}}` para que el cliente TypeScript no necesite dos formas de
 * parsear error según el verbo. Código elegido: `NOT_FOUND` (mismo string que usa Task 5 para su
 * propio 404 de aislamiento entre proyectos — aquí no hay aislamiento que probar porque no hay
 * `{id}` en la URL, la única causa de 404 es la capacidad).
 *
 * --- Sin CSRF ---
 * Ningún GET de `BiControlTowerApiController` valida CSRF (solo `requireAuth()` + el gate de
 * capacidad). Es lectura, no mutación — no hay razón para pedirlo aquí tampoco.
 *
 * --- Aislamiento: $_SESSION['project_id'] directo, no BiProjectScope ---
 * Igual que Task 5 (`BiConstraintWriteController`), NO `BiProjectScope::resolve()`/
 * `resolveProjectIds()` (que es multi-proyecto, pensado para el portafolio de los reportes BI
 * existentes) — un solo `project_id` de sesión, filtrado directo en el WHERE. Instrucción
 * explícita del dispatcher: "igual que Task 5".
 *
 * --- Fuente de cada campo ---
 * Base: `pi_shared_constraints` (Id, Semana, Restriccion, + las 5 columnas de gestión de Task 4:
 * ResponsableAsignado, FechaCompromiso, EstadoLiberacion, AsignadoPor, AsignadoEn).
 * Urgencia (Task 7 paso 2, `RestriccionUrgencia` en `ct-app/src/lib/urgencia.ts` — nombres de
 * campo IDÉNTICOS a propósito, para que el consumidor no tenga que remapear):
 *   - `actividadesEncadenadas` = COUNT(*) de `pi_shared_constraint_links` por `SharedConstraintId`.
 *   - `semanaInicioActividadBloqueada` = MIN(`Semanas_Inicio`) de `programa_consolidado`, entre
 *     TODAS las actividades que la restricción bloquea (join vía `ConsecutivoEnPrograma`+`Semana`+
 *     `project_id`, idéntico al de la rama de shared constraints en
 *     `database/bi/002_bi_pi_restricciones.sql:120-128`, incluido el filtro `Titulo=0`). `null`
 *     si `actividadesEncadenadas === 0` (sin actividades, no se conoce cuándo golpea).
 *   - `tocaRutaCritica` = true si ALGUNA actividad encadenada tiene `Ruta_Critica=1`. `false` si
 *     `actividadesEncadenadas === 0`.
 *   - `actividadBloqueada` (para pintar "actividad que bloquea", CT-8.3): nombre de una actividad
 *     representativa entre las que empatan en `semanaInicioActividadBloqueada` (la que arranca más
 *     pronto). `null` si `actividadesEncadenadas === 0`. Este test NO exige un desempate exacto
 *     entre actividades empatadas en la misma semana — verifica que el valor devuelto pertenezca
 *     al conjunto de nombres válidos para esa semana mínima, no una igualdad estricta con una fila
 *     fija, porque cuál de varias empatadas se muestra es libertad de implementación de rol B.
 * `diasVencida` (derivado, NO viene de una columna):
 *   - `FechaCompromiso IS NULL` -> `null` (no hay fecha con la que calcular vencimiento).
 *   - `FechaCompromiso >= hoy` (hoy incluido: vence hoy no es vencida todavía) -> `null`.
 *   - `FechaCompromiso < hoy` -> entero positivo, días transcurridos desde `FechaCompromiso`.
 *   Precedente EXACTO de esta forma (no inventado): `SeguimientoService::clasificarVencimiento()`
 *   (`src/Services/Pdc/SeguimientoService.php:57-79`, la regla gemela para "días de vencido" del
 *   panel de compras, CT-8.6/8.7) — sin fecha o fecha futura -> sin días de vencimiento (allá
 *   `null` + bucket; acá, sin bucket, `null` basta porque el llamador ya distingue "sin fecha" de
 *   "con fecha futura" mirando `fechaCompromiso` mismo); fecha pasada -> entero positivo de días.
 *   No se preguntó al usuario porque hay precedente directo de código en el mismo repo, tal como
 *   permite el encargo ("si no puedes derivarla [la definición], pregunta" — aquí SÍ se pudo
 *   derivar). Documentado igual como decisión explícita, no una certeza absoluta: si el rol B o
 *   una revisión posterior definen "vencida hoy" como sí-vencida, es un cambio de un símbolo
 *   (`<` por `<=`) discutible, no un hecho verificado contra la spec (CT-8.3 solo nombra la
 *   columna, no la fórmula).
 *
 * --- Filtro por estado: NINGUNO ---
 * El título del lienzo (CT-8.3, punto 3) dice "restricciones por liberar", pero
 * `ordenarPorUrgencia()` (Task 7 paso 2) ordena TODAS las restricciones que recibe sin filtrar por
 * `EstadoLiberacion` — el filtrado, si hace falta, es decisión del componente que consume esta
 * lista (`ListaRestricciones.tsx`, paso 3, fuera de este alcance) o un parámetro de query futuro,
 * no un recorte duro en el backend. Decisión documentada, no bloqueante.
 *
 * --- No incluido a propósito: acción sugerida (D89) ---
 * El brief general de Task 7 menciona "cada alerta trae su acción sugerida... con
 * `ActionRecommendationService` detrás del GET" (D89), pero el encargo puntual de este paso (3a)
 * solo pidió las columnas de CT-8.3 listadas arriba. Queda fuera de este contrato — ver Concerns
 * en el reporte.
 *
 * --- Sin composite-PK trap aquí ---
 * A diferencia de Task 5 (que necesita un `Id` que exista en un proyecto ajeno y NO en el propio
 * para probar aislamiento sobre un `{id}` de la URL), este endpoint no recibe `{id}`: no hay
 * forma de "pedir" una fila ajena. El aislamiento se prueba comparando el CONJUNTO COMPLETO de
 * ids devueltos contra el conjunto real de `pi_shared_constraints.Id` para `PROJECT_ID` (ni de
 * más -- fuga de otro proyecto -- ni de menos -- filtro roto).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';   // Mismo fixture que Task 5: test.A y test.V son miembros los dos.
const PROJECT_ID = 73;
const FOREIGN_PROJECT_ID = 68; // Solo para el aviso informativo de abajo -- no hay {id} que aislar.

/**
 * Login por la puerta de servicio (AGENTS.md). Nunca /login, nunca credenciales tecleadas.
 * Idéntico a `tests/test_bi_constraint_write.php::sesion()` -- ver ese archivo para el porqué de
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
 * GET autenticado vía curl, con cookie jar persistente.
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
 * Oráculo independiente: para un `SharedConstraintId` dado, recalcula desde las tablas fuente
 * (NO desde el endpoint) el conteo de actividades encadenadas, la semana mínima de arranque, si
 * alguna toca ruta crítica, y el conjunto de nombres de actividad válidos como "actividad que
 * bloquea" (las que empatan en la semana mínima). Mismo join que la rama de shared constraints en
 * `database/bi/002_bi_pi_restricciones.sql:120-128`.
 *
 * @return array{n:int, semanaMin:?int, rutaCritica:bool, actividadesCandidatas:list<string>}
 */
function oraculo($db, int $sharedConstraintId, int $projectId): array
{
    $filas = $db->query(
        "SELECT pc.Actividad, pc.Semanas_Inicio, pc.Ruta_Critica
         FROM pi_shared_constraint_links pcl
         JOIN programa_consolidado pc
           ON pcl.ConsecutivoEnPrograma = pc.Consecutivo_en_Programa
          AND pcl.Semana = pc.Semana
          AND pcl.project_id = pc.project_id
         WHERE pcl.SharedConstraintId = ? AND pcl.project_id = ? AND pc.Titulo = 0",
        [$sharedConstraintId, $projectId],
    )->fetchAll(PDO::FETCH_ASSOC);

    $n = count($filas);
    if ($n === 0) {
        return ['n' => 0, 'semanaMin' => null, 'rutaCritica' => false, 'actividadesCandidatas' => []];
    }

    $semanas = array_map(static fn($f) => (int) $f['Semanas_Inicio'], $filas);
    $semanaMin = min($semanas);
    $rutaCritica = false;
    $candidatas = [];
    foreach ($filas as $f) {
        if ((int) $f['Ruta_Critica'] === 1) {
            $rutaCritica = true;
        }
        if ((int) $f['Semanas_Inicio'] === $semanaMin) {
            $candidatas[] = (string) $f['Actividad'];
        }
    }

    return ['n' => $n, 'semanaMin' => $semanaMin, 'rutaCritica' => $rutaCritica, 'actividadesCandidatas' => array_values(array_unique($candidatas))];
}

/** Días de vencida esperados, ver el contrato en la cabecera del archivo. */
function diasVencidaEsperado(?string $fechaCompromiso): ?int
{
    if ($fechaCompromiso === null || $fechaCompromiso === '') {
        return null;
    }
    $hoy = new DateTimeImmutable('today');
    $fecha = new DateTimeImmutable($fechaCompromiso);
    if ($fecha >= $hoy) {
        return null;
    }
    return (int) $hoy->diff($fecha)->days;
}

/**
 * Caso 6 (fix ronda 1, Important 1): idéntico en técnica al Caso 6 de
 * `tests/test_bi_constraint_write.php` — ver ese archivo para el porqué completo de correr esto
 * en un subproceso PHP aislado con `$_SESSION` poblado a mano (nunca sesión HTTP, nunca cookie,
 * nunca pasa por el candado de DevDoor: `Database::query()` puede llamar `@session_start()`
 * internamente y no debe interferir con el `$_SESSION` de este proceso principal).
 *
 * Corre `RbacService::resolveCurrentRole()` (lo que `BiConstraintListController` usa ahora, tras
 * el fix) Y `RbacService::resolveRoleForUser($usuario)` sin project scoping (el patrón viejo que
 * `BiPreviewAccessPolicy::canOpen()` todavía usa) para el MISMO usuario real.
 *
 * @return array{0:string,1:string} [rol devuelto por resolveCurrentRole(), rol devuelto por
 *                                    resolveRoleForUser() sin project scoping]
 */
function rolesEscopadosVsGlobales(string $usuario, string $proyecto): array
{
    $script = '<?php '
        . 'require "/var/www/html/vendor/autoload.php"; '
        . 'require "/var/www/html/src/Core/Database.php"; '
        . '$_SESSION["usuario"] = $argv[1]; '
        . '$_SESSION["proyecto"] = $argv[2]; '
        . '$db = Database::getInstance(); '
        . '$rbac = new \App\Security\RbacService($db); '
        . '$scoped = $rbac->resolveCurrentRole(); '
        . '$unscoped = $rbac->resolveRoleForUser($argv[1]); '
        . 'echo json_encode(["scoped" => $scoped, "unscoped" => $unscoped]);';
    $tmp = tempnam(sys_get_temp_dir(), 'rbac_scope_');
    file_put_contents($tmp, $script);
    $salida = trim((string) shell_exec(
        'php ' . escapeshellarg($tmp) . ' ' . escapeshellarg($usuario) . ' ' . escapeshellarg($proyecto) . ' 2>&1'
    ));
    @unlink($tmp);
    $decoded = json_decode($salida, true);
    if (!is_array($decoded) || !isset($decoded['scoped'], $decoded['unscoped'])) {
        fwrite(STDERR, "ABORT: no se pudo resolver roles escopados/globales (salida: {$salida})\n");
        exit(2);
    }
    return [(string) $decoded['scoped'], (string) $decoded['unscoped']];
}

$db = Database::getInstance();

// ---------------------------------------------------------------- Fixtures (solo lectura) -----

$idsEsperados = array_map(
    static fn($f) => (int) $f['Id'],
    $db->query('SELECT Id FROM pi_shared_constraints WHERE project_id = ?', [PROJECT_ID])->fetchAll(PDO::FETCH_ASSOC),
);
if (count($idsEsperados) === 0) {
    fwrite(STDERR, "ABORT: PROJECT_ID=" . PROJECT_ID . " no tiene filas en pi_shared_constraints\n");
    exit(2);
}
sort($idsEsperados);

// Fila dedicada a los sub-casos de diasVencida: se muta y se restaura al final. Buscada en
// tiempo de ejecución (no hardcodeada) para no depender de un snapshot fijo de datos de dev.
$filaFecha = $db->query(
    "SELECT Id, EstadoLiberacion, ResponsableAsignado, FechaCompromiso, AsignadoPor, AsignadoEn
     FROM pi_shared_constraints WHERE project_id = ? ORDER BY Id LIMIT 1",
    [PROJECT_ID],
)->fetch(PDO::FETCH_ASSOC);
if ($filaFecha === false) {
    fwrite(STDERR, "ABORT: no se pudo tomar una fila fixture para los sub-casos de diasVencida\n");
    exit(2);
}
$idFecha = (int) $filaFecha['Id'];

// Usuario real (no `test.*`) con rol denegado (no A/D/R) en PROJECT_ID y rol permitido (A/D/R,
// el conjunto exacto de PERM_INTERNAL_BI_PREVIEW) en algún OTRO proyecto — la forma exacta que
// el caso 6 necesita para probar que el segundo gate del controlador (fix ronda 1, Important 1)
// resuelve el rol por el proyecto de sesión y no por "el más privilegiado en cualquier proyecto".
// Descubierto en tiempo de ejecución, igual que el resto de fixtures de este archivo.
$candidatoRbac = $db->query(
    "SELECT u.usuario, pm.role AS rol_en_sesion
     FROM project_members pm
     JOIN general_usuarios u ON u.id = pm.user_id
     WHERE pm.project_id = ?
       AND pm.role NOT IN ('A', 'D', 'R')
       AND EXISTS (
           SELECT 1 FROM project_members pmOtro
           WHERE pmOtro.user_id = pm.user_id
             AND pmOtro.project_id != ?
             AND pmOtro.role IN ('A', 'D', 'R')
       )
     ORDER BY u.usuario LIMIT 1",
    [PROJECT_ID, PROJECT_ID],
)->fetch(PDO::FETCH_ASSOC);

echo "Fixtures: PROJECT_ID=" . PROJECT_ID . " (Da Porto), " . count($idsEsperados) . " restricciones | "
    . "fila diasVencida: Id={$idFecha}"
    . " | candidatoRbac=" . ($candidatoRbac !== false ? $candidatoRbac['usuario'] . " (rol_en_sesion={$candidatoRbac['rol_en_sesion']})" : 'ninguno') . "\n";

// ------------------------------------------------------------------------- Sesiones -----------

[$jarA, ] = sesion('test.A');
[$jarV, ] = sesion('test.V');

$url = BASE . '/api/bi/control-tower/restricciones';

$fallos = 0;
$total = 0;
$check = function (bool $ok, string $nombre, string $detalle = '') use (&$fallos, &$total): void {
    $total++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre . ($detalle !== '' ? " — {$detalle}" : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

// -------------------------------------------------------- Caso 1: test.A (permitido) -> 200 ---

[$code1, $body1] = getReq($url, $jarA);
$json1 = json_decode($body1, true);

$check($code1 === 200, 'caso 1: test.A (rol A, PERM_INTERNAL_BI_PREVIEW) -> 200', "HTTP {$code1}, body: " . substr($body1, 0, 300));
$check(is_array($json1) && ($json1['ok'] ?? null) === true, 'caso 1: envelope {ok:true}');
$restricciones = is_array($json1) ? ($json1['restricciones'] ?? null) : null;
$check(is_array($restricciones), 'caso 1: envelope trae "restricciones" (array)');

// ----------------------------------------- Caso 2: aislamiento -- conjunto exacto de ids -----

if (is_array($restricciones)) {
    $idsRecibidos = array_map(static fn($r) => (int) $r['id'], $restricciones);
    sort($idsRecibidos);
    $check(
        $idsRecibidos === $idsEsperados,
        'caso 2: ids devueltos == ids reales de PROJECT_ID (sin fuga de otro proyecto, sin faltantes)',
        'esperados: ' . count($idsEsperados) . ', recibidos: ' . count($idsRecibidos)
            . (count($idsRecibidos) < 20 ? ' | recibidos=' . implode(',', $idsRecibidos) : ''),
    );
} else {
    $check(false, 'caso 2: aislamiento -- omitido, "restricciones" no es un array');
}

// --------------------------------------- Caso 3: cada fila contra el oráculo de la BD -----

if (is_array($restricciones)) {
    foreach ($restricciones as $fila) {
        $id = (int) ($fila['id'] ?? 0);
        $base = $db->query(
            'SELECT Restriccion, Semana, EstadoLiberacion, ResponsableAsignado, FechaCompromiso, AsignadoPor, AsignadoEn
             FROM pi_shared_constraints WHERE project_id = ? AND Id = ?',
            [PROJECT_ID, $id],
        )->fetch(PDO::FETCH_ASSOC);

        if ($base === false) {
            $check(false, "caso 3: fila id={$id} -- no existe en pi_shared_constraints (fuga?)");
            continue;
        }

        $check(($fila['restriccion'] ?? null) === $base['Restriccion'], "caso 3: id={$id} restriccion", 'real: ' . var_export($fila['restriccion'] ?? null, true) . ' esperado: ' . var_export($base['Restriccion'], true));
        $check(($fila['semana'] ?? null) === (int) $base['Semana'], "caso 3: id={$id} semana");
        $check(($fila['estadoLiberacion'] ?? null) === $base['EstadoLiberacion'], "caso 3: id={$id} estadoLiberacion");

        // `?? 'MISSING'` trata "clave ausente" y "clave presente con valor null" como lo mismo
        // (semantica de isset()) -- bug real cuando el valor CORRECTO es null (sin gestion, sin
        // fecha). array_key_exists() distingue las dos cosas; el valor se compara aparte.
        $respEsperado = $base['ResponsableAsignado'] === '' ? null : $base['ResponsableAsignado'];
        $check(array_key_exists('responsableAsignado', $fila) && $fila['responsableAsignado'] === $respEsperado, "caso 3: id={$id} responsableAsignado", 'real: ' . var_export($fila['responsableAsignado'] ?? null, true) . ' esperado: ' . var_export($respEsperado, true));

        $fechaEsperada = $base['FechaCompromiso'] === '' ? null : $base['FechaCompromiso'];
        $check(array_key_exists('fechaCompromiso', $fila) && $fila['fechaCompromiso'] === $fechaEsperada, "caso 3: id={$id} fechaCompromiso");

        $asigPorEsperado = $base['AsignadoPor'] === '' ? null : $base['AsignadoPor'];
        $check(array_key_exists('asignadoPor', $fila) && $fila['asignadoPor'] === $asigPorEsperado, "caso 3: id={$id} asignadoPor");

        $diasEsperado = diasVencidaEsperado($fechaEsperada);
        $check(array_key_exists('diasVencida', $fila) && $fila['diasVencida'] === $diasEsperado, "caso 3: id={$id} diasVencida", 'real: ' . var_export($fila['diasVencida'] ?? null, true) . ' esperado: ' . var_export($diasEsperado, true));

        $orac = oraculo($db, $id, PROJECT_ID);
        $check(($fila['actividadesEncadenadas'] ?? null) === $orac['n'], "caso 3: id={$id} actividadesEncadenadas", 'real: ' . var_export($fila['actividadesEncadenadas'] ?? null, true) . ' esperado: ' . var_export($orac['n'], true));
        $check(($fila['semanaInicioActividadBloqueada'] ?? 'MISSING') === $orac['semanaMin'], "caso 3: id={$id} semanaInicioActividadBloqueada", 'real: ' . var_export($fila['semanaInicioActividadBloqueada'] ?? null, true) . ' esperado: ' . var_export($orac['semanaMin'], true));
        $check(($fila['tocaRutaCritica'] ?? null) === $orac['rutaCritica'], "caso 3: id={$id} tocaRutaCritica", 'real: ' . var_export($fila['tocaRutaCritica'] ?? null, true) . ' esperado: ' . var_export($orac['rutaCritica'], true));
        $check(is_bool($fila['tocaRutaCritica'] ?? null), "caso 3: id={$id} tocaRutaCritica es bool JSON (no 0/1)");

        if ($orac['n'] === 0) {
            // Mismo bug de ?? 'MISSING' que arriba -- no se disparo con los datos de dev de hoy
            // (ninguna fila de PROJECT_ID tiene 0 links), pero es el mismo defecto mecanico.
            $check(array_key_exists('actividadBloqueada', $fila) && $fila['actividadBloqueada'] === null, "caso 3: id={$id} actividadBloqueada null (sin encadenamiento)");
        } else {
            $check(
                in_array($fila['actividadBloqueada'] ?? null, $orac['actividadesCandidatas'], true),
                "caso 3: id={$id} actividadBloqueada entre las candidatas de la semana minima",
                'real: ' . var_export($fila['actividadBloqueada'] ?? null, true) . ' candidatas: ' . implode(' | ', $orac['actividadesCandidatas']),
            );
        }
    }
} else {
    $check(false, 'caso 3: comparacion contra oraculo -- omitida, "restricciones" no es un array');
}

// ------------------------------------------------------- Caso 4: test.V (denegado) -> 404 -----

[$code4, $body4] = getReq($url, $jarV);
$json4 = json_decode($body4, true);

$check($code4 === 404, 'caso 4: test.V (rol V, sin PERM_INTERNAL_BI_PREVIEW) -> 404', "HTTP {$code4}, body: " . substr($body4, 0, 300));
$check(is_array($json4) && ($json4['ok'] ?? null) === false, 'caso 4: envelope {ok:false}');
$check(is_array($json4) && is_array($json4['error'] ?? null) && ($json4['error']['code'] ?? null) === 'NOT_FOUND', 'caso 4: error.code == NOT_FOUND', 'real: ' . var_export($json4['error']['code'] ?? null, true));

// ---------------------------------------------------- Caso 5: diasVencida, los tres bordes -----

/**
 * Detalle legible para las aserciones de diasVencida del caso 5. `?? 'FILA_NO_ENCONTRADA'` sobre
 * el propio valor es el MISMO bug de fondo que el fix de arriba (no distingue "fila no encontrada"
 * de "fila encontrada con diasVencida=null", que es justo el caso correcto en 5b/5c/5d) -- aqui
 * solo afecta al texto de diagnostico, no al veredicto PASS/FAIL (ese ya usa array_key_exists),
 * pero un mensaje que dice "FILA_NO_ENCONTRada" sobre un PASS real es enganoso igual.
 *
 * @param ?array<string,mixed> $fila
 */
function describirDiasVencida(?array $fila): string
{
    if ($fila === null) {
        return 'FILA_NO_ENCONTRADA';
    }
    if (!array_key_exists('diasVencida', $fila)) {
        return 'CLAVE_AUSENTE';
    }
    return var_export($fila['diasVencida'], true);
}

try {
    // 5a: FechaCompromiso 5 dias atras -> diasVencida = 5
    $fechaPasada = (new DateTimeImmutable('-5 days'))->format('Y-m-d');
    $db->query('UPDATE pi_shared_constraints SET FechaCompromiso = ? WHERE project_id = ? AND Id = ?', [$fechaPasada, PROJECT_ID, $idFecha]);
    [, $bodyA] = getReq($url, $jarA);
    $filaA = buscarFila(json_decode($bodyA, true), $idFecha);
    $check($filaA !== null && array_key_exists('diasVencida', $filaA) && $filaA['diasVencida'] === 5, 'caso 5a: FechaCompromiso hace 5 dias -> diasVencida=5', 'real: ' . describirDiasVencida($filaA));

    // 5b: FechaCompromiso hoy -> diasVencida = null (vence hoy, no vencida todavia)
    $fechaHoy = (new DateTimeImmutable('today'))->format('Y-m-d');
    $db->query('UPDATE pi_shared_constraints SET FechaCompromiso = ? WHERE project_id = ? AND Id = ?', [$fechaHoy, PROJECT_ID, $idFecha]);
    [, $bodyB] = getReq($url, $jarA);
    $filaB = buscarFila(json_decode($bodyB, true), $idFecha);
    $check($filaB !== null && array_key_exists('diasVencida', $filaB) && $filaB['diasVencida'] === null, 'caso 5b: FechaCompromiso hoy -> diasVencida=null', 'real: ' . describirDiasVencida($filaB));

    // 5c: FechaCompromiso en 5 dias -> diasVencida = null
    $fechaFutura = (new DateTimeImmutable('+5 days'))->format('Y-m-d');
    $db->query('UPDATE pi_shared_constraints SET FechaCompromiso = ? WHERE project_id = ? AND Id = ?', [$fechaFutura, PROJECT_ID, $idFecha]);
    [, $bodyC] = getReq($url, $jarA);
    $filaC = buscarFila(json_decode($bodyC, true), $idFecha);
    $check($filaC !== null && array_key_exists('diasVencida', $filaC) && $filaC['diasVencida'] === null, 'caso 5c: FechaCompromiso en 5 dias -> diasVencida=null', 'real: ' . describirDiasVencida($filaC));

    // 5d: FechaCompromiso NULL -> diasVencida = null
    $db->query('UPDATE pi_shared_constraints SET FechaCompromiso = NULL WHERE project_id = ? AND Id = ?', [PROJECT_ID, $idFecha]);
    [, $bodyD] = getReq($url, $jarA);
    $filaD = buscarFila(json_decode($bodyD, true), $idFecha);
    $check($filaD !== null && array_key_exists('diasVencida', $filaD) && $filaD['diasVencida'] === null, 'caso 5d: FechaCompromiso NULL -> diasVencida=null', 'real: ' . describirDiasVencida($filaD));
} finally {
    // ------------------------------------------------------------------------- Cleanup -----
    $db->query(
        'UPDATE pi_shared_constraints
         SET EstadoLiberacion = ?, ResponsableAsignado = ?, FechaCompromiso = ?, AsignadoPor = ?, AsignadoEn = ?
         WHERE project_id = ? AND Id = ?',
        [
            $filaFecha['EstadoLiberacion'],
            $filaFecha['ResponsableAsignado'] === '' ? null : $filaFecha['ResponsableAsignado'],
            $filaFecha['FechaCompromiso'] === '' ? null : $filaFecha['FechaCompromiso'],
            $filaFecha['AsignadoPor'] === '' ? null : $filaFecha['AsignadoPor'],
            $filaFecha['AsignadoEn'] === '' ? null : $filaFecha['AsignadoEn'],
            PROJECT_ID,
            $idFecha,
        ],
    );
}

// --------------------------------------- Caso 6: RBAC scoped por proyecto, no el global -----
// Fix ronda 1 (Important 1): `BiPreviewAccessPolicy::canOpen()` resuelve el rol vía
// `RbacService::resolveRoleForUser()` SIN project scoping -- el rol MÁS PRIVILEGIADO que el
// usuario tenga en CUALQUIER proyecto, no su rol en el proyecto de la sesión activa (el mismo
// bug que Task 5 tuvo que cerrar en su propio Caso 6). `BiConstraintListController::listar()`
// ahora agrega un SEGUNDO gate acotado con `RbacService::resolveCurrentRole()`. `test.V` no
// sirve para probar esta propiedad (V en los 4 proyectos donde es miembro, mismo límite que
// Task 5), así que se prueba al nivel donde SÍ es legítimo y suficiente -- ver el comentario de
// `rolesEscopadosVsGlobales()` para el porqué de no usar una sesión HTTP.

if ($candidatoRbac === false) {
    echo 'AVISO caso 6: no se encontro en dev un usuario con rol denegado (no A/D/R) en PROJECT_ID='
        . PROJECT_ID . " y rol permitido (A/D/R) en otro proyecto -- omitido, no hay dato real que lo pruebe hoy.\n";
} else {
    $usuarioRbac = (string) $candidatoRbac['usuario'];
    $rolEsperado = (string) $candidatoRbac['rol_en_sesion'];

    [$rolScoped, $rolUnscoped] = rolesEscopadosVsGlobales($usuarioRbac, PROYECTO);

    $check(
        $rolScoped === $rolEsperado,
        "caso 6: resolveCurrentRole() de {$usuarioRbac} en " . PROYECTO . " -> {$rolEsperado} (scoped, no el mas privilegiado)",
        'real: ' . var_export($rolScoped, true) . ' | unscoped (patron viejo, el que sigue usando canOpen()) hubiera dado: ' . var_export($rolUnscoped, true),
    );
    $check(
        !\App\Security\RbacManager::hasCapability($rolScoped, \App\Security\RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
        "caso 6: hasCapability(rol scoped={$rolScoped}, PERM_INTERNAL_BI_PREVIEW) -> false",
        'unscoped (patron viejo) hasCapability=' . var_export(\App\Security\RbacManager::hasCapability($rolUnscoped, \App\Security\RbacCatalog::PERM_INTERNAL_BI_PREVIEW), true),
    );
}

echo PHP_EOL . ($fallos === 0 ? "OK ({$total} aserciones)\n" : "FALLOS: {$fallos} de {$total}\n");
exit($fallos === 0 ? 0 : 1);

/** Busca una fila por id en el envelope decodificado del endpoint; null si no aplica. */
function buscarFila($json, int $id): ?array
{
    if (!is_array($json) || !is_array($json['restricciones'] ?? null)) {
        return null;
    }
    foreach ($json['restricciones'] as $fila) {
        if ((int) ($fila['id'] ?? -1) === $id) {
            return $fila;
        }
    }
    return null;
}
