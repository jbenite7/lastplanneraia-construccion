<?php
// tests/test_pdc_v2_frentes_contrato.php — el contrato de datos del que cuelgan los frentes.
//
// Un frente es un encabezado del cronograma (`Titulo = 1`) CON `unique_id`. Las dos mitades tienen
// que ser ciertas a la vez: `PlanFechasService::semanaYFrentes()` filtra por `unique_id IS NOT NULL`
// y marca `esFrente = Titulo === 1`. Si alguien anula el `unique_id` de los encabezados, la obra se
// queda sin ningún frente al que amarrar paquetes y el Plan de Compras deja de poder usarse — sin
// que ningún test de servicio se entere, porque los fixtures se siembran ya correctos.
//
// Le pasó a `prueba-lps` (proyecto 27) el 2026-07-29 al ejecutar
// `database/migrations/20260712_remap_consolidado_unique_id.php`, cuya premisa caducó.
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/ScopeFixture.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();

echo "=== PDC v2: contrato de datos de los frentes ===\n";

// ── 1 · La migración que rompió esto no puede volver a dispararse sola ───────
$mig = (string) file_get_contents(__DIR__ . '/../database/migrations/20260712_remap_consolidado_unique_id.php');
$assert(
    str_contains($mig, 'ABORTADA: esta migración está obsoleta'),
    'La migración de remap conserva su guardarraíl: aborta en vez de anular los encabezados.',
);
$assert(
    str_contains($mig, "SELECT COUNT(*) FROM programa WHERE Titulo = 1 AND unique_id IS NOT NULL"),
    'Y decide abortando sobre una MEDICIÓN de la base, no sobre una fecha ni una bandera suelta.',
);

// ── 2 · La FK que justificaba anularlos apunta a `unique_id`, y se satisface ─
// Los metadatos se piden por la puerta de `Database`, no armando la consulta a
// `information_schema` aquí: es tabla calificada por schema y el gate las rechaza, con razón.
$fk = $db->foreignKeyExists(
    'programa_consolidado',
    'fk_pc__programa__unique_id',
    'unique_id',
    'programa',
    'unique_id',
);
$assert(
    $fk,
    'La FK de `unique_id` referencia `programa.unique_id` — no `programa.Consecutivo`, que es lo que '
    . 'la migración obsoleta suponía. Dio ' . var_export($fk, true),
);

// ── 3 · Ninguna obra con paquetes amarrados puede quedarse sin frentes ───────
// Se mira solo donde importa —obras que ya usan el Plan de Compras—, y se informa de las demás en
// vez de callarlas: una cobertura que se pierde en silencio se acaba dando por hecha.
// Descubrir QUÉ obras usan el Plan de Compras cruza obras por definición: no hay un proyecto
// desde el que preguntarlo. Va como mantenimiento, con su razón escrita. Lo que se comprueba de
// cada obra, en cambio, se mira desde el alcance de esa obra, más abajo.
$obras = ScopeFixture::comoSistema(
    $db,
    'test:pdc-frentes-contrato:censo-de-obras',
    static fn () => $db->query(
        'SELECT DISTINCT f.project_id FROM pdc_paquete_frente f ORDER BY f.project_id',
    )->fetchAll(\PDO::FETCH_COLUMN),
);

if ($obras === []) {
    echo "SKIP: ninguna obra tiene paquetes amarrados en esta base; nada que comprobar.\n";
}
foreach ($obras as $projectId) {
    $projectId = (int) $projectId;
    // Cada obra se mira desde su propio alcance, que es como la miraría el producto.
    ScopeFixture::abrir($db, $projectId, 'test-pdc-frentes');
    $semana = $db->query('SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?', [$projectId])->fetchColumn();
    if ($semana === false || $semana === null) {
        echo "SKIP: la obra {$projectId} no tiene semana activa.\n";
        ScopeFixture::cerrar($db);
        continue;
    }
    $fila = $db->query(
        'SELECT COUNT(*) encabezados, SUM(unique_id IS NOT NULL) con_uid
           FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND Titulo = 1',
        [$projectId, (int) $semana],
    )->fetch(\PDO::FETCH_ASSOC);

    if ((int) $fila['encabezados'] === 0) {
        echo "SKIP: la obra {$projectId} no tiene encabezados en su semana activa ({$semana}).\n";
        ScopeFixture::cerrar($db);
        continue;
    }
    $assert(
        (int) $fila['con_uid'] > 0,
        "La obra {$projectId} conserva frentes en su semana activa ({$semana}): "
        . "{$fila['con_uid']} de {$fila['encabezados']} encabezados con unique_id. "
        . 'En cero, su Plan de Compras se queda sin ningún frente al que amarrar.',
    );

    // Y el servicio tiene que verlos de verdad, no solo la base: es la mitad que se rompió en
    // producción de pruebas, donde la consulta daba filas pero ninguna llegaba como frente.
    $frentes = (new App\Services\Pdc\PlanFechasService($db))->frentesDisponibles($projectId);
    $assert(
        $frentes !== [],
        "PlanFechasService::frentesDisponibles({$projectId}) devuelve al menos un frente. Dio "
        . count($frentes),
    );
    ScopeFixture::cerrar($db);
}

if ($failures !== []) {
    fwrite(STDERR, "\n=== " . count($failures) . " FALLO(S) ===\n");
    exit(1);
}
echo "\n=== OK ===\n";
