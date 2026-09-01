<?php
// tests/test_pdc_v2_duraciones_obra_rbac.php — comportamiento de los dos verbos de duración por
// obra: permiso .editar, CSRF plan_compras_v2, pertenencia de la fila a la obra, validaciones.
//
// Complementa a tests/test_pdc_v2_duraciones_obra_contrato.php, que solo afirma strings en el
// código fuente y no ejercita ningún request real. Este archivo sí llama a los métodos del
// controlador, con sesión, CSRF y cuerpo JSON simulados, y comprueba el efecto sobre
// pdc_proyecto_duraciones.
declare(strict_types=1);
// @requiere: db

namespace App\Controllers\Api {
    // Sustituye file_get_contents() SOLO dentro de este namespace: PlanComprasPlanController::body()
    // lo llama sin calificar, y la resolución de funciones sin calificar en PHP ocurre en TIEMPO DE
    // EJECUCIÓN — primero revisa el namespace actual y solo si no encuentra nada cae al global. Es el
    // mismo truco que se usa para mockear time()/rand() en pruebas. No hay servidor HTTP real detrás
    // de este script, así que no existe un php://input de verdad que leer: sin este reemplazo no
    // habría forma de simular un cuerpo JSON.
    function file_get_contents(string $filename, ...$args)
    {
        if ($filename === 'php://input') {
            return $GLOBALS['__TEST_HTTP_BODY__'] ?? '';
        }
        return \file_get_contents($filename, ...$args);
    }
}

namespace {

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Controllers\Api\PlanComprasPlanController;
use App\Security\CsrfTokenManager;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();

// Sesión real UNA VEZ antes de tocar $_SESSION a mano: CsrfTokenManager::validate() llama
// session_start() internamente si no está activa, y eso REEMPLAZA $_SESSION por lo que hubiera en
// disco para el id nuevo (nada, la primera vez). Arrancarla aquí, antes de asignar nada, deja la
// sesión activa para el resto del script: las siguientes llamadas a session_start() son no-op y
// las reasignaciones manuales de $_SESSION sobreviven.
session_start();
$csrfValido = CsrfTokenManager::generate('plan_compras_v2');

$capture = static function (callable $fn): array {
    ob_start();
    $fn();
    $raw = (string) ob_get_clean();
    return json_decode($raw, true) ?? ['__raw' => $raw];
};

$P = 999910; // proyecto de pruebas propio de esta suite, no compartido con otras

$limpiar = static function () use ($db, $P): void {
    // calcular() escribe en pdc_plan_paso/pdc_plan_paquete, que referencian a
    // general_paquetes_contratacion por llave foránea: hay que borrarlas primero o el borrado del
    // paquete sintético falla con «Cannot delete or update a parent row».
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_proyecto_duraciones WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'test-dur-obra-rbac'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ZZTEST DUR OBRA RBAC'");
};
$limpiar();

// Fixture mínimo: una fila del catálogo y un paquete que la usa, amarrado a la obra P vía
// pdc_paquete_frente. Es lo mínimo que DuracionesCatalogoService::deProyecto() necesita para decir
// que esta obra usa $refSint (mismo patrón que tests/test_pdc_v2_pasos_configurables.php).
$db->query(
    "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete,
        diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos,
        diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('ZZTEST DUR OBRA RBAC', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)",
);
$refSint = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion,
        modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('ZZTEST DUR OBRA RBAC', 'zztest dur obra rbac', 'a_todo_costo', 'contrato', ?, 1, 'test-dur-obra-rbac', NOW())",
    [$refSint],
);
$paqSint = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO pdc_paquete_frente (project_id, paquete_id, unique_id, frente_nombre, fecha_ancla,
        semana_origen, origen, evidencia, confirmado_humano, asignado_por, updated_at)
     VALUES (?, ?, 9101, 'ZZTEST DUR OBRA RBAC FRENTE', '2027-01-01', 1, 'humano', '', 1, 'test-dur-obra-rbac', NOW())",
    [$P, $paqSint],
);

$refInexistente = $refSint + 999000; // no la usa ningún paquete de esta obra

$sesion = static function (string $rol, ?int $projectId) use ($csrfValido): void {
    $_SESSION = [
        // Login que no existe en general_usuarios: SesionUsuario::resolverId() debe volver null sin
        // romper, y de hecho es el caso normal — el usuario de prueba no está sembrado.
        'usuario' => 'test-dur-obra-rbac-usuario',
        'nombreUsuario' => 'Test Dur Obra',
        'permiso' => $rol,
        'permiso_canonico' => $rol,
        'project_id' => $projectId,
        'proyecto' => 'ZZTEST DUR OBRA RBAC',
        '_csrf_tokens' => ['plan_compras_v2' => $csrfValido],
    ];
};

try {
    fwrite(STDERR, "=== 1. rol permitido + CSRF válido + fila de esta obra: guarda y luego restablece ===\n");
    $sesion('D', $P);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfValido;
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['diasFabricacion' => 21]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === true, 'guardarDuracionObra() responde ok:true con permiso, CSRF y fila válidos. Dio ' . json_encode($out));
    $fila = $db->query(
        'SELECT dias FROM pdc_proyecto_duraciones WHERE project_id = ? AND duracion_ref = ? AND columna = ?',
        [$P, $refSint, 'diasFabricacion'],
    )->fetchColumn();
    $assert((int) $fila === 21, 'La corrección quedó escrita en pdc_proyecto_duraciones. Dio ' . var_export($fila, true));

    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'columnas' => ['diasFabricacion']]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->borrarDuracionObra());
    $assert(($out['ok'] ?? null) === true, 'borrarDuracionObra() responde ok:true. Dio ' . json_encode($out));
    $quedan = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_proyecto_duraciones WHERE project_id = ? AND duracion_ref = ?',
        [$P, $refSint],
    )->fetchColumn();
    $assert($quedan === 0, 'Restablecer quitó la fila: la obra vuelve al número del catálogo. Dio ' . $quedan);

    fwrite(STDERR, "=== 2. rol denegado (V, sin .editar) → 403 FORBIDDEN, sin tocar la tabla ===\n");
    $sesion('V', $P);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfValido;
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['diasFabricacion' => 55]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'FORBIDDEN',
        'V sin lps.paquetes_contratacion.editar recibe FORBIDDEN. Dio ' . json_encode($out));
    $assert(http_response_code() === 403, 'Y el status HTTP es 403. Dio ' . http_response_code());
    $sinCambios = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_proyecto_duraciones WHERE project_id = ? AND duracion_ref = ?',
        [$P, $refSint],
    )->fetchColumn();
    $assert($sinCambios === 0, 'Y la tabla sigue sin filas: el rechazo fue antes de escribir.');

    fwrite(STDERR, "=== 3. sin project_id en sesión → 409 NO_PROJECT ===\n");
    $sesion('D', null);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfValido;
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['diasFabricacion' => 55]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'NO_PROJECT',
        'Sin proyecto activo responde NO_PROJECT. Dio ' . json_encode($out));
    $assert(http_response_code() === 409, 'Y el status HTTP es 409. Dio ' . http_response_code());

    fwrite(STDERR, "=== 4. sin CSRF → 403 CSRF_INVALID ===\n");
    $sesion('D', $P);
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['diasFabricacion' => 55]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'CSRF_INVALID',
        'Sin token CSRF responde CSRF_INVALID. Dio ' . json_encode($out));
    $assert(http_response_code() === 403, 'Y el status HTTP es 403. Dio ' . http_response_code());

    fwrite(STDERR, "=== 5. duracionRef que la obra NO usa → 403 DURACION_NO_DISPONIBLE ===\n");
    $sesion('D', $P);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfValido;
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refInexistente, 'dias' => ['diasFabricacion' => 55]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'DURACION_NO_DISPONIBLE',
        'Una fila que la obra no usa responde DURACION_NO_DISPONIBLE. Dio ' . json_encode($out));
    $assert(http_response_code() === 403, 'Y el status HTTP es 403. Dio ' . http_response_code());

    // El mismo guard vale para restablecer: sin esto, la pantalla de una obra podría borrar
    // correcciones de una fila que no le pertenece.
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refInexistente, 'columnas' => ['diasFabricacion']]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->borrarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'DURACION_NO_DISPONIBLE',
        'borrarDuracionObra() aplica el mismo guard de pertenencia. Dio ' . json_encode($out));

    fwrite(STDERR, "=== 6. validaciones de datos: días negativos y columna fuera de lista blanca ===\n");
    $sesion('D', $P);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfValido;
    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['diasFabricacion' => -5]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'DIAS_INVALIDOS',
        'Días negativos responde DIAS_INVALIDOS. Dio ' . json_encode($out));
    $assert(http_response_code() === 422, 'Y el status HTTP es 422. Dio ' . http_response_code());

    $GLOBALS['__TEST_HTTP_BODY__'] = json_encode(['duracionRef' => $refSint, 'dias' => ['columnaInventada' => 5]]);
    $out = $capture(static fn () => (new PlanComprasPlanController())->guardarDuracionObra());
    $assert(($out['ok'] ?? null) === false && ($out['error']['code'] ?? '') === 'COLUMNA_INVALIDA',
        'Una columna fuera de la lista blanca responde COLUMNA_INVALIDA. Dio ' . json_encode($out));
    $assert(http_response_code() === 422, 'Y el status HTTP es 422. Dio ' . http_response_code());

    $sinFilas = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_proyecto_duraciones WHERE project_id = ? AND duracion_ref = ?',
        [$P, $refSint],
    )->fetchColumn();
    $assert($sinFilas === 0, 'Ninguna validación fallida dejó una fila a medias.');
} finally {
    $limpiar();
    unset($_SERVER['HTTP_X_CSRF_TOKEN'], $GLOBALS['__TEST_HTTP_BODY__']);
}

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);

}
