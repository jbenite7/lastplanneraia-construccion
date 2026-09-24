<?php
declare(strict_types=1);
// @requiere: http, dev_door

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';
const DB_PREFIX = 'pdc_sandbox_e2e';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\DataScope\ProjectScope;

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
$projStmt = $db->query("SELECT Id FROM general_proyectos_procesos WHERE Base_de_Datos = ?", [DB_PREFIX]);
$projectId = (int) $projStmt->fetchColumn();
if (!$projectId) {
    fwrite(STDERR, "ABORT: Proyecto de prueba no encontrado.\n");
    exit(2);
}
$db->dataScope()->bind(new ProjectScope($projectId, 'test.A', 'A'));

// 1. Asegurar semana 4 confirmada
$tblSemanas = "semanas_activas";
$db->query(
    "INSERT INTO {$tblSemanas} (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCierreCompromisos)
     VALUES (?, 4, 4, '2026-09-10', '2026-09-16', 1, '2026-09-16')
     ON DUPLICATE KEY UPDATE Semanal_Confirmada = 1, fechaCierreCompromisos = '2026-09-16'",
    [$projectId]
);

// 1.1 Asegurar actividad en programa (FK para Consecutivo_En_Programa)
$db->query(
    "INSERT INTO programa (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
     VALUES (?, 99991, 99991, '99991', 'Actividad TNP Prueba', 0, '2026-09-10', '2026-09-16')
     ON DUPLICATE KEY UPDATE Actividad = VALUES(Actividad)",
    [$projectId]
);

// 2. Asegurar una fila TNP en programacion_semanal para semana 4
$tblPS = "programacion_semanal";
$stmtRow = $db->query(
    "SELECT row_id FROM {$tblPS} WHERE project_id = ? AND Semana = 4 AND Es_TNP = 1 LIMIT 1",
    [$projectId]
);
$rowId = $stmtRow->fetchColumn();
if (!$rowId) {
    $db->query(
        "INSERT INTO {$tblPS} (project_id, Semana, unique_id, Consecutivo_En_Programa, Id, Actividad, Descripcion, Ubicacion, Sub_Contratista, Responsable_AIA, Empresa, Unidad, cantidad_ppto, Compromiso, Cantidad_Sugerida, Ejecutado_Real, Activa, Es_TNP)
         VALUES (?, 4, 99991, 99991, 'TNP.1', 'Actividad TNP Prueba', NULL, NULL, 'CONTRATISTA PRUEBA', 'RESPONSABLE PRUEBA', 'AIA', '%', 100, NULL, NULL, NULL, '1', 1)",
        [$projectId]
    );
    $stmtRow = $db->query(
        "SELECT row_id FROM {$tblPS} WHERE project_id = ? AND Semana = 4 AND Es_TNP = 1 LIMIT 1",
        [$projectId]
    );
    $rowId = (int) $stmtRow->fetchColumn();
} else {
    $rowId = (int) $rowId;
    $db->query("UPDATE {$tblPS} SET Compromiso = NULL, Cantidad_Sugerida = NULL, Ejecutado_Real = NULL WHERE project_id = ? AND row_id = ?", [$projectId, $rowId]);
}

$jar = sesion('test.A');
// Obtener token CSRF
[, $html] = curlReq(BASE . '/programacion-semanal', null, $jar);
preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})"/', $html, $mCsrf);
$csrfToken = $mCsrf[1] ?? '';
if (!$csrfToken) {
    fwrite(STDERR, "ABORT: No se pudo obtener CSRF token.\n");
    exit(2);
}

$fallos = 0;

// Test A: Calificar avance Real en la fila TNP mediante modificar
$payloadModificar = [
    'opcion' => 'modificar',
    '_csrf_token' => $csrfToken,
    'semana' => 4,
    'Id' => $rowId,
    'Compromiso' => '',
    'Cantidad_Sugerida' => '0.0',
    'Real' => '45.0',
    'Sub_Contratista' => 'CONTRATISTA PRUEBA',
    'Responsable_AIA' => 'RESPONSABLE PRUEBA',
    'Descripcion' => '',
    'Ubicacion' => '',
    'Empresa' => 'AIA',
    'Unidad' => '%',
    'Es_TNP' => '1',
];
[$codeMod, $bodyMod] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), $payloadModificar, $jar);
$resMod = json_decode($bodyMod, true);
if ($codeMod !== 200 || ($resMod['respuesta'] ?? '') !== 'BIEN') {
    $fallos++;
    echo "FALLO modificar TNP: HTTP $codeMod, cuerpo: $bodyMod (esperaba HTTP 200 y respuesta BIEN)\n";
} else {
    echo "OK modificar TNP: calificado con éxito en semana confirmada.\n";
}

// Test B: Registrar TNP mediante opcion=tnp en semana confirmada
$payloadTnp = [
    'opcion' => 'tnp',
    '_csrf_token' => $csrfToken,
    'semana' => 4,
    'Consecutivo' => 99991,
    'Ejecutado_Real' => 30.0,
    'Categoria_CP' => 'IMPREVISTOS',
    'CP' => 'Clima',
    'Observaciones_CP' => 'Lluvia torrencial',
];
[$codeTnp, $bodyTnp] = curlReq(BASE . '/api/semanal/save?db=' . urlencode(DB_PREFIX), $payloadTnp, $jar);
$resTnp = json_decode($bodyTnp, true);
if ($codeTnp !== 200 || ($resTnp['respuesta'] ?? '') !== 'BIEN') {
    $fallos++;
    echo "FALLO opcion=tnp: HTTP $codeTnp, cuerpo: $bodyTnp (esperaba HTTP 200 y respuesta BIEN)\n";
} else {
    echo "OK opcion=tnp: registrado con éxito en semana confirmada.\n";
}

echo $fallos === 0 ? "TODO OK\n" : "TOTAL FALLOS: $fallos\n";
exit($fallos === 0 ? 0 : 1);
