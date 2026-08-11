<?php
// tests/test_pdc_v2_amarre_cronograma.php — B1: amarre insumo↔cronograma sobre MySQL real.
//
// Fija el invariante que A4 dejó abierto: `pdc_insumo_actividades.unique_id` deja de ser «NULL hasta
// que alguien lo llene» y pasa a ser «resuelto por rama, o NULL CON MOTIVO ESCRITO». La diferencia
// importa: un NULL mudo es indistinguible de un cálculo que nunca corrió, y así fue como las 820
// filas de DAPORTO llegaron vacías hasta B1.
//
// Escenario sintético (no mocks: MySQL real) con un cronograma de juguete que reproduce las cuatro
// situaciones que el motor tiene que distinguir, más una verificación sobre los datos reales de
// DAPORTO cuando están presentes.

declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\AmarreCronogramaService;
use App\Services\Pdc\PlanFechasService;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$P = 999911;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_insumo_actividades WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_items WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

echo "=== PDC v2 · B1: amarre insumo↔cronograma ===\n";

// ---------------------------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------------------------
$nodo = static function (int $cons, int $uid, string $nombre, int $titulo, string $ini) use ($db, $P): void {
    // `programa_consolidado.Consecutivo_en_Programa` tiene FK contra `programa`: el snapshot semanal
    // no puede existir sin su fila en el programa vivo.
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$P, $cons, $uid, '<b>' . $nombre . ', </b> <small>[Capítulo: TORRE T]</small>', $titulo, $ini],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, 7, ?, ?, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, $cons, $uid, $cons, '<b>' . $nombre . ', </b> <small>[Capítulo: TORRE T]</small>', $titulo, $ini],
    );
};
// Semanas 6 y 7: la 7 es la activa. Tener dos comprueba que se lee MAX(Semana) y no cualquiera.
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
     VALUES (?, 1, 6, "2026-05-18", "2026-05-24"), (?, 2, 7, "2026-05-25", "2026-05-31")',
    [$P, $P],
);

$nodo(1, 8001, 'ESTRUCTURA', 1, '2026-08-18');
$nodo(2, 8002, 'CARPINTERIA EN MADERA', 1, '2027-05-21');
$nodo(3, 8003, 'VENTANERÍA', 1, '2027-05-06');
$nodo(4, 8004, 'MAMPOSTERÍA', 1, '2027-02-09');
$nodo(5, 8005, 'REVOQUE TRADICIONAL', 1, '2027-03-12');
// Padre e hijo homónimos: el amarre tiene que quedarse con el que arranca antes.
$nodo(6, 8006, 'PISOS Y ENCHAPES', 1, '2027-05-12');
$nodo(7, 8007, 'PISOS Y ENCHAPES', 1, '2027-07-08');
// Hoja, no frente: es el único ancla posible para una rama que el cronograma no modeló.
$nodo(8, 8008, 'LOSA AÉREA CUBIERTA', 0, '2027-07-27');
// Hoja homónima de un frente: el emparejamiento automático NO debe caer en ella.
$nodo(9, 8009, 'ESTRUCTURA', 0, '2028-01-01');

$db->query(
    "INSERT INTO pdc_presupuesto_versiones (project_id, version_label, version_numero, archivo_nombre, archivo_hash,
         total_actividades, total_insumos, costo_total, activa, importado_por, created_at)
     VALUES (?, 'V-B1', 1, 'test-b1.xlsx', REPEAT('b', 64), 7, 7, 7000, 1, 'test-b1', NOW())",
    [$P],
);
$VID = (int) $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ?', [$P])->fetchColumn();

$rama = static function (string $codigo, int $nivel, string $tipo, string $desc) use ($db, $P, $VID): void {
    $db->query(
        'INSERT INTO pdc_presupuesto_items (project_id, version_id, codigo, codigo_padre, nivel, tipo_fila, descripcion)
         VALUES (?, ?, ?, NULL, ?, ?, ?)',
        [$P, $VID, $codigo, $nivel, $tipo, $desc],
    );
};
$rama('01', 1, 'capitulo', 'COSTO DIRECTO');
$rama('01.04', 2, 'subcapitulo', 'ESTRUCTURA');                 // exacta
$rama('01.05', 2, 'subcapitulo', 'MAMPOSTERIA Y REVOQUE');      // tokens
$rama('01.05.06', 3, 'grupo', 'REVOQUES');                      // override de grupo pisa al subcapítulo
$rama('01.05.01', 3, 'grupo', 'MUROS Y CHAPAS');                // sin regla: hereda del subcapítulo
$rama('01.07', 2, 'subcapitulo', 'CUBIERTA');                   // override hacia una HOJA
$rama('01.14', 2, 'subcapitulo', 'PISOS Y ENCHAPES');           // homónimos: gana el más temprano
$rama('01.19', 2, 'subcapitulo', 'CARPINTERIA METALICA');       // override pisa un automático ERRÓNEO
$rama('01.99', 2, 'subcapitulo', 'RAMA QUE NO EXISTE EN OBRA'); // sin frente → NULL con motivo

$fila = static function (string $codigo, float $valor) use ($db, $P, $VID): void {
    $db->query(
        'INSERT INTO pdc_insumo_actividades (project_id, version_id, descripcion_norm, unidad, item_id, codigo, actividad, cantidad, valor)
         VALUES (?, ?, ?, "UN", 1, ?, "ACTIVIDAD DE PRUEBA", 1, ?)',
        [$P, $VID, 'INSUMO ' . $codigo, $codigo, $valor],
    );
};
foreach (['01.04.01.01', '01.05.01.01', '01.05.06.01', '01.07.01.01', '01.14.01.01', '01.19.01.01', '01.99.01.01'] as $c) {
    $fila($c, 1000.0);
}

// ---------------------------------------------------------------------------------------------
// Resolución
// ---------------------------------------------------------------------------------------------
$svc = new AmarreCronogramaService($db);
$res = $svc->resolverVersion($P, $VID);

$assert($res['semana'] === 7, 'Se resuelve contra MAX(Semana) del consolidado, no contra cualquier semana.');

// Ojo con `??`: un `uniqueId` legítimamente NULL no debe confundirse con «no se resolvió».
$uid = static fn (array $r, string $codigo): ?int => isset($r['porCodigo'][$codigo])
    ? $r['porCodigo'][$codigo]['uniqueId']
    : -1;
$org = static fn (array $r, string $codigo): string => $r['porCodigo'][$codigo]['origen'] ?? '(sin resolver)';

$assert($uid($res, '01.04.01.01') === 8001 && $org($res, '01.04.01.01') === 'exacta',
    'Un subcapítulo que se llama igual que un frente casa exacto (y no cae en la hoja homónima).');

$assert($uid($res, '01.05.01.01') === 8004 && $org($res, '01.05.01.01') === 'tokens',
    '«MAMPOSTERIA Y REVOQUE» llega a MAMPOSTERÍA por similitud de palabras.');

$assert($uid($res, '01.05.06.01') === 8005 && $org($res, '01.05.06.01') === 'override',
    'La regla del GRUPO «REVOQUES» pisa el amarre que heredaría de su subcapítulo.');

$assert($uid($res, '01.07.01.01') === 8008 && $org($res, '01.07.01.01') === 'override',
    'Una regla puede anclar a una HOJA cuando el cronograma no modeló el frente (CUBIERTA).');

$assert($uid($res, '01.14.01.01') === 8006,
    'Entre dos nodos homónimos gana el que arranca antes: es el que fija la primera entrega.');

$assert($uid($res, '01.19.01.01') === 8003 && $org($res, '01.19.01.01') === 'override',
    'La regla gana al automático cuando el texto engaña (CARPINTERIA METALICA → VENTANERÍA, no MADERA).');

// El automático a secas se equivoca aquí: es lo que justifica que la regla exista.
$sinReglas = (new AmarreCronogramaService($db, false))->resolverVersion($P, $VID);
$assert(($sinReglas['porCodigo']['01.19.01.01']['uniqueId'] ?? null) === 8002,
    'Sin la regla, «CARPINTERIA METALICA» se iría a CARPINTERIA EN MADERA: la regla no es redundante.');

$assert($uid($res, '01.99.01.01') === null && $org($res, '01.99.01.01') === 'sin_frente',
    'Una rama sin frente en el cronograma queda en NULL, no inventa un destino.');
$assert(str_contains($res['porCodigo']['01.99.01.01']['evidencia'] ?? '', 'RAMA QUE NO EXISTE EN OBRA'),
    'El NULL viene con el motivo escrito y nombra la rama que se intentó.');

// ---------------------------------------------------------------------------------------------
// Escritura: idempotencia y motivo persistido
// ---------------------------------------------------------------------------------------------
$w1 = $svc->amarrarVersion($P, $VID);
$assert($w1['cambios'] === 7, 'La primera escritura toca las 7 filas.');

$w2 = $svc->amarrarVersion($P, $VID);
$assert($w2['cambios'] === 0, 'La segunda corrida no cambia nada: el amarre es idempotente.');

$nulos = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND unique_id IS NULL',
    [$P],
)->fetchColumn();
$assert($nulos === 1, 'Queda en NULL solo la rama sin frente.');

$sinMotivo = (int) $db->query(
    "SELECT COUNT(*) FROM pdc_insumo_actividades
     WHERE project_id = ? AND (origen_amarre IS NULL OR evidencia_amarre = '' OR semana_amarre IS NULL)",
    [$P],
)->fetchColumn();
$assert($sinMotivo === 0, 'INVARIANTE: ninguna fila queda sin origen, evidencia y semana — ni siquiera las NULL.');

// Un proyecto sin cronograma no revienta: deja todo en NULL y lo dice.
$db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
$sinCron = $svc->amarrarVersion($P, $VID);
$assert($sinCron['semana'] === null && $sinCron['cambios'] === 0,
    'Un proyecto sin semana activa no escribe nada en vez de fallar.');

// ---------------------------------------------------------------------------------------------
// Datos reales: DAPORTO, si está sembrado en esta base
// ---------------------------------------------------------------------------------------------
$daporto = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = 73')->fetchColumn();
if ($daporto === 0) {
    echo "SKIP: DAPORTO (project_id 73) no está en esta base; se omite la verificación sobre datos reales.\n";
} else {
    $nulosReales = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = 73 AND unique_id IS NULL',
    )->fetchColumn();
    $mudos = (int) $db->query(
        "SELECT COUNT(*) FROM pdc_insumo_actividades
         WHERE project_id = 73 AND unique_id IS NULL AND (origen_amarre IS NULL OR evidencia_amarre = '')",
    )->fetchColumn();
    $assert($nulosReales < $daporto, "DAPORTO ya no está todo en NULL: {$nulosReales} de {$daporto}.");
    $assert($mudos === 0, 'En DAPORTO ninguna fila sin amarre quedó sin motivo escrito.');

    // Toda regla sembrada tiene que estar sosteniendo algo (disciplina de A3.3: nada de memoria muerta).
    $conR = (new AmarreCronogramaService($db, true))->resolverVersion(73, 292);
    $sinR = (new AmarreCronogramaService($db, false))->resolverVersion(73, 292);
    if ($conR['porCodigo'] === []) {
        echo "SKIP: la versión 292 de DAPORTO no está; se omite la poda de reglas.\n";
    } else {
        $seed = json_decode((string) file_get_contents(__DIR__ . '/../database/seeds/sembrado_ramas_frentes.json'), true);
        $sostienen = [];
        foreach ($conR['porCodigo'] as $codigo => $a) {
            if ($a['origen'] !== 'override' || preg_match('/^La rama «(.+?)» se ancla/u', $a['evidencia'], $m) !== 1) {
                continue;
            }
            if (($sinR['porCodigo'][$codigo]['uniqueId'] ?? null) !== $a['uniqueId']) {
                $sostienen[$m[1]] = true;
            }
        }
        // El seed tiene DOS consumidores desde el merge de A4.2 (2026-07-29): este servicio, que
        // amarra el insumo a su rama, y `PlanFechasService`, que propone el frente del paquete.
        // Medir la poda contra uno solo declara muerta una regla que el otro sí está usando: fue lo
        // que pasó con URBANISMO Y OBRAS EXTERIORES, redundante aquí (el automático llega solo tras
        // ganar las PALABRAS_VACIAS de A4.2, Jaccard 1/3 = 0,3333 contra el umbral 0,33) y viva
        // allá, donde sostiene la propuesta de 9 paquetes con confianza ALTA. Sin la regla caen a
        // similitud, y 0,3333 < 0,7 los deja en MEDIA: el botón que acepta solo las altas dejaría
        // de cubrirlos. Una regla está viva si sostiene a CUALQUIERA de los dos.
        foreach ((new PlanFechasService($db))->sugerirFrentes(73) as $s) {
            if (($s['origen'] ?? '') === 'correspondencia'
                && preg_match('/^Sus insumos están en «(.+?)»/u', (string) ($s['evidencia'] ?? ''), $mp) === 1) {
                $sostienen[$mp[1]] = true;
            }
        }
        $muertas = array_diff(array_keys($seed['reglas'] ?? []), array_keys($sostienen));
        $assert($muertas === [],
            'Ninguna regla sembrada es redundante en DAPORTO (medido contra los dos motores). Sobran: '
            . (implode(', ', $muertas) ?: '—'));
    }
}

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
$limpiar();
exit($failures === [] ? 0 : 1);
