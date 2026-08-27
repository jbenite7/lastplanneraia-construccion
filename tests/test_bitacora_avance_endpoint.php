<?php
declare(strict_types=1);
// @requiere: http

// Comprueba que editar el avance por el endpoint REAL deja su firma en la bitacora. No basta con
// probar el servicio aislado: el riesgo medido es que `update()` siga escribiendo por el camino
// viejo sin que nadie lo note. Spec:
// docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

const BASE = 'http://localhost';
const PROYECTO = 'Da Porto';
const DB_PREFIX = 'da_porto';
const PROJECT_ID = 73;
const UID = 1473;      // Campamentos, semana 2: acumulado 0.9 en el dump de produccion
const SEMANA = 2;

function sesion(string $usuario): string {
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    [$code] = curlReq($url, null, $jar);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

$db = Database::getInstance();
$db->setProjectContext(PROJECT_ID);

$proyectoExiste = $db->query(
    "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Id = ? AND Base_de_Datos = ?",
    [PROJECT_ID, DB_PREFIX],
)->fetchColumn();
if (!$proyectoExiste) {
    fwrite(STDERR, "ABORT: no existe el proyecto " . PROJECT_ID . " (Da Porto) en la base local\n");
    exit(2);
}

// El fixture sintetico de CI (a diferencia del dump de dev/produccion que da nombre a UID) no
// siempre trae esta fila especifica -- se salta solo (patron SKIP: + exit 0, el que
// scripts/run-php-tests.php reconoce como salto real, no el ABORT/exit(2) del guardarraíl de
// arriba, que el runner cuenta como fallo). Medido el 2026-08-27: design-system-ci.sql solo
// siembra Da Porto en Semana 1.
$filaExiste = $db->queryWithProject(
    "SELECT COUNT(*) FROM programa_consolidado WHERE Semana = ? AND unique_id = ?",
    [SEMANA, UID], PROJECT_ID,
)->fetchColumn();
if (!$filaExiste) {
    echo "SKIP: no existe la fila unique_id=" . UID . ", Semana=" . SEMANA . " en este fixture\n";
    exit(0);
}

$avanceOriginal = $db->queryWithProject(
    "SELECT Ejecutado FROM programa_consolidado WHERE Semana = ? AND unique_id = ?",
    [SEMANA, UID], PROJECT_ID,
)->fetchColumn();

$db->query("DELETE FROM pg_avance_edicion_manual WHERE project_id = ? AND unique_id = ?", [PROJECT_ID, UID]);

$jar = sesion('test.A');

// El token CSRF que exige `requireProgramaGeneralCsrf()` viaja como meta tag en la pagina del
// modulo (views/programa-general/programa_general.view.php: `<meta name="csrf-token" ...>`), no
// como campo de formulario `csrf_token`. El endpoint lo espera en `_csrf_token`
// (GeneralApiController::requireProgramaGeneralCsrf()).
[, $html] = curlReq(BASE . '/programa-general?db=' . urlencode(DB_PREFIX), null, $jar);
preg_match('/name="csrf-token"[^>]*content="([^"]+)"/', $html, $m);
$token = $m[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "ABORT: no se pudo leer el token CSRF de /programa-general\n");
    exit(2);
}

$nuevoValor = 55; // 55% — distinto del 90% que trae el dump, para que cuente como cambio
[$code] = curlReq(
    BASE . '/api/general/update?db=' . urlencode(DB_PREFIX) . '&semana_objetivo=' . SEMANA,
    ['unique_id' => UID, 'Id' => UID, 'opcion' => 'editar', 'Ejecutado' => $nuevoValor,
     'unidad' => '%', '_csrf_token' => $token],
    $jar,
);

$filas = $db->query(
    "SELECT valor_anterior, valor_nuevo, usuario FROM pg_avance_edicion_manual
     WHERE project_id = ? AND unique_id = ?",
    [PROJECT_ID, UID],
)->fetchAll();

// Restaurar el dato al valor del dump y limpiar la bitacora, pase o falle.
$db->queryWithProject(
    "UPDATE programa_consolidado SET Ejecutado = ? WHERE Semana = ? AND unique_id = ?",
    [$avanceOriginal, SEMANA, UID], PROJECT_ID,
);
$db->query("DELETE FROM pg_avance_edicion_manual WHERE project_id = ? AND unique_id = ?", [PROJECT_ID, UID]);

$fallos = 0;
if ($code !== 200) { $fallos++; echo "FALLO: el endpoint devolvio $code (esperaba 200)\n"; }
if (count($filas) !== 1) {
    $fallos++;
    echo 'FALLO: se esperaba 1 fila en la bitacora, hay ' . count($filas) . "\n";
} else {
    if (abs((float) $filas[0]['valor_nuevo'] - 0.55) > 0.001) {
        $fallos++;
        echo "FALLO: valor_nuevo es {$filas[0]['valor_nuevo']} (esperaba 0.55)\n";
    }
    if ($filas[0]['usuario'] !== 'test.A') {
        $fallos++;
        echo "FALLO: usuario es {$filas[0]['usuario']} (esperaba test.A)\n";
    }
}

echo $fallos === 0 ? "OK: la edicion por el endpoint real quedo firmada\n" : "FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
