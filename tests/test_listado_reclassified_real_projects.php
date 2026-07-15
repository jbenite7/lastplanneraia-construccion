<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;

$failed = 0;
$db = null;
$lrrOwnedFixtures = [];

const LRR_FIXTURES = [
    68 => ['name' => 'JMC', 'db' => 'optimizacionJMC', 'week' => 9871, 'base' => 68098700],
    73 => ['name' => 'Da Porto', 'db' => 'da_porto', 'week' => 9872, 'base' => 73098700],
];

const LRR_FAMILIES = [
    'CIMENTACIONES' => 'Cimentaciones',
    'TOPOGRAFIA' => 'Topografía',
    'RED_ELECTRICA' => 'Red Eléctrica',
    'PINTURAS' => 'Pinturas Interiores y Exteriores',
];

function lrrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lrrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lrrAssert(bool $condition, string $message): void
{
    $condition ? lrrPass($message) : lrrFail($message);
}

function lrrRunId(int $projectId, string $module): string
{
    $fixture = LRR_FIXTURES[$projectId];
    return "run_lrr_{$projectId}_{$fixture['week']}_{$module}";
}

function lrrSourceRows(array $fixture): array
{
    $base = $fixture['base'];
    return [
        [$base + 1, '1.1', 'LOSA DE CIMENTACION [Capitulo: Torre QA]', '2099-01-13'],
        [$base + 2, '1.2', 'LOCALIZACION Y REPLANTEO [Capitulo: Torre QA]', '2099-01-06'],
        [$base + 3, '1.3', 'LOCALIZACION Y REPLANTEO [Capitulo: Torre QA]', '2099-01-08'],
        [$base + 4, '1.4', 'RED ELECTRICA [Capitulo: Torre QA]', '2099-01-15'],
        [$base + 5, '1.5', 'PINTURA INTERIOR [Capitulo: Torre QA]', '2099-01-17'],
    ];
}

function lrrCleanup(Database $db, int $projectId): void
{
    global $lrrOwnedFixtures;
    if (empty($lrrOwnedFixtures[$projectId])) {
        return;
    }

    $fixture = LRR_FIXTURES[$projectId];
    $week = $fixture['week'];
    $sourceIds = array_column(lrrSourceRows($fixture), 0);
    $holders = implode(', ', array_fill(0, count($sourceIds), '?'));
    $runIds = [lrrRunId($projectId, 'listado'), lrrRunId($projectId, 'contratos')];

    foreach ($runIds as $runId) {
        $db->query('DELETE FROM semi_auto_assistant_feedback WHERE run_id = ?', [$runId]);
        $db->query('DELETE FROM semi_auto_decisions WHERE run_id = ?', [$runId]);
        $db->query('DELETE FROM semi_auto_suggestions WHERE run_id = ?', [$runId]);
        $db->query('DELETE FROM semi_auto_runs WHERE run_id = ?', [$runId]);
        $db->query('DELETE FROM semi_auto_proactive_queue WHERE source_ref = ?', [$runId]);
    }

    $db->query(
        "DELETE FROM actividad_programa_fuentes
         WHERE project_id = ? AND semana = ? AND programa_unique_id IN ({$holders})",
        array_merge([$projectId, $week], $sourceIds),
    );
    $db->query(
        "DELETE FROM actividades
         WHERE project_id = ? AND semanaActualizacion = ? AND actividadInicio IN ({$holders})",
        array_merge([$projectId, $week], $sourceIds),
    );
    $db->query(
        'DELETE FROM programa_consolidado WHERE project_id = ? AND Semana = ?',
        [$projectId, $week],
    );
    $db->query(
        "DELETE FROM programa WHERE project_id = ? AND unique_id IN ({$holders})",
        array_merge([$projectId], $sourceIds),
    );
    $db->query(
        'DELETE FROM semanas_activas WHERE project_id = ? AND Id = ? AND Semana = ?',
        [$projectId, $week, $week],
    );
}

function lrrScopeIsVacant(Database $db, int $projectId): bool
{
    $fixture = LRR_FIXTURES[$projectId];
    $week = $fixture['week'];
    $checks = [
        'semanas_activas' => 'SELECT COUNT(*) FROM semanas_activas WHERE project_id = ? AND Semana = ?',
        'programa_consolidado' => 'SELECT COUNT(*) FROM programa_consolidado WHERE project_id = ? AND Semana = ?',
        'actividades' => 'SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?',
        'actividad_programa_fuentes' => 'SELECT COUNT(*) FROM actividad_programa_fuentes WHERE project_id = ? AND semana = ?',
        'semi_auto_proactive_queue' => 'SELECT COUNT(*) FROM semi_auto_proactive_queue WHERE project_id = ? AND semana = ?',
    ];
    $vacant = true;
    foreach ($checks as $table => $sql) {
        $count = (int) $db->query($sql, [$projectId, $week])->fetchColumn();
        lrrAssert($count === 0, "{$fixture['name']}: semana reservada {$week} vacía en {$table}");
        $vacant = $vacant && $count === 0;
    }
    foreach ([lrrRunId($projectId, 'listado'), lrrRunId($projectId, 'contratos')] as $runId) {
        $count = (int) $db->query('SELECT COUNT(*) FROM semi_auto_runs WHERE run_id = ?', [$runId])->fetchColumn();
        lrrAssert($count === 0, "{$fixture['name']}: corrida reservada {$runId} ausente");
        $vacant = $vacant && $count === 0;
    }
    return $vacant;
}

function lrrInsertFixture(Database $db, int $projectId): void
{
    global $lrrOwnedFixtures;
    $fixture = LRR_FIXTURES[$projectId];
    $week = $fixture['week'];
    $lrrOwnedFixtures[$projectId] = true;
    $db->query(
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (?, ?, ?, ?, ?)',
        [$projectId, $week, $week, '2099-01-01', '2099-01-07'],
    );
    foreach (lrrSourceRows($fixture) as [$uniqueId, $id, $activity, $start]) {
        $db->query(
            'INSERT INTO programa
             (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?)',
            [$projectId, $uniqueId, $uniqueId, $id, $activity, $start, '2099-01-31'],
        );
        $db->query(
            'INSERT INTO programa_consolidado
             (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
              Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)',
            [$projectId, $uniqueId, $uniqueId, $week, $uniqueId, $uniqueId, $id, $activity, $start, '2099-01-31'],
        );
    }
}

function lrrSuggestionForFamily(array $preview, string $familyCode): ?array
{
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        if (($suggestion['action'] ?? '') === 'create_activity'
            && ($suggestion['analysis']['technical']['familia_codigo'] ?? '') === $familyCode) {
            return $suggestion;
        }
    }
    return null;
}

function lrrActivity(Database $db, int $projectId, int $week, string $name): array
{
    return $db->query(
        'SELECT * FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND actividad = ? LIMIT 1',
        [$projectId, $week, $name],
    )->fetch(PDO::FETCH_ASSOC) ?: [];
}

function lrrContractSuggestion(array $preview, int $activityId): ?array
{
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        if (($suggestion['action'] ?? '') === 'update_contracts'
            && (int) ($suggestion['target_pk'] ?? 0) === $activityId) {
            return $suggestion;
        }
    }
    return null;
}

function lrrRunProject(Database $db, SemiAutoService $service, int $projectId): void
{
    $fixture = LRR_FIXTURES[$projectId];
    $week = $fixture['week'];
    $context = ['projectId' => $projectId, 'project_id' => $projectId, 'dbPrefix' => $fixture['db'], 'semana' => $week];
    lrrInsertFixture($db, $projectId);

    $listado = $service->preview(SemiAutoService::MODULE_LISTADO, $context, lrrRunId($projectId, 'listado'));
    lrrAssert(($listado['run_id'] ?? '') === lrrRunId($projectId, 'listado'), "{$fixture['name']}: preview de Listado guarda la corrida reservada");
    lrrAssert((int) ($listado['total'] ?? 0) === count(LRR_FAMILIES), "{$fixture['name']}: preview crea las cuatro familias canónicas");

    $listadoIds = [];
    foreach (LRR_FAMILIES as $familyCode => $familyName) {
        $suggestion = lrrSuggestionForFamily($listado, $familyCode);
        lrrAssert($suggestion !== null, "{$fixture['name']}: preview detecta {$familyName}");
        if ($suggestion === null) {
            throw new RuntimeException("{$fixture['name']}: falta sugerencia {$familyCode}");
        }
        lrrAssert(($suggestion['proposed']['actividad'] ?? '') === $familyName, "{$fixture['name']}: {$familyCode} conserva nombre canónico");
        $listadoIds[] = (string) $suggestion['suggestion_id'];
    }
    $applyListado = $service->apply(SemiAutoService::MODULE_LISTADO, $context, lrrRunId($projectId, 'listado'), $listadoIds);
    $applyErrors = $db->query(
        "SELECT result_payload FROM semi_auto_decisions WHERE run_id = ? AND decision = 'error'",
        [lrrRunId($projectId, 'listado')],
    )->fetchAll(PDO::FETCH_COLUMN);
    lrrAssert((int) ($applyListado['aplicadas'] ?? 0) === count(LRR_FAMILIES) && (int) ($applyListado['errores'] ?? 0) === 0, "{$fixture['name']}: apply de Listado persiste todas las familias" . ($applyErrors === [] ? '' : ': ' . implode(' | ', $applyErrors)));

    $count = (int) $db->query('SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?', [$projectId, $week])->fetchColumn();
    lrrAssert($count === count(LRR_FAMILIES), "{$fixture['name']}: apply no crea actividades extra");
    $duplicates = (int) $db->query('SELECT COUNT(*) - COUNT(DISTINCT actividad) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?', [$projectId, $week])->fetchColumn();
    lrrAssert($duplicates === 0, "{$fixture['name']}: apply no duplica familias");

    $topografia = lrrActivity($db, $projectId, $week, LRR_FAMILIES['TOPOGRAFIA']);
    $earliestSourceId = $fixture['base'] + 2;
    $sourceRows = (int) $db->query('SELECT COUNT(*) FROM actividad_programa_fuentes WHERE project_id = ? AND semana = ? AND actividad_id = ?', [$projectId, $week, (int) ($topografia['Id'] ?? 0)])->fetchColumn();
    lrrAssert($sourceRows === 2, "{$fixture['name']}: Topografía conserva ambas fuentes trazables");
    lrrAssert((int) ($topografia['actividadInicio'] ?? 0) === $earliestSourceId && ($topografia['fechaInicio'] ?? '') === '2099-01-06', "{$fixture['name']}: Topografía usa la fuente más temprana");

    $electrica = lrrActivity($db, $projectId, $week, LRR_FAMILIES['RED_ELECTRICA']);
    $pinturas = lrrActivity($db, $projectId, $week, LRR_FAMILIES['PINTURAS']);
    lrrAssert(!empty($electrica) && !empty($pinturas), "{$fixture['name']}: familias con contrato quedaron en Listado");

    $contratos = $service->preview(SemiAutoService::MODULE_CONTRATOS, $context, lrrRunId($projectId, 'contratos'));
    $electricaSuggestion = lrrContractSuggestion($contratos, (int) ($electrica['Id'] ?? 0));
    $pinturasSuggestion = lrrContractSuggestion($contratos, (int) ($pinturas['Id'] ?? 0));
    lrrAssert($electricaSuggestion !== null && $pinturasSuggestion !== null, "{$fixture['name']}: preview de Contratos propone las dos familias");
    if ($electricaSuggestion === null || $pinturasSuggestion === null) {
        throw new RuntimeException("{$fixture['name']}: faltan sugerencias de contrato");
    }
    $electricaFields = $electricaSuggestion['proposed'] ?? [];
    $pinturasFields = $pinturasSuggestion['proposed'] ?? [];
    lrrAssert(($electricaFields['tipoContrato'] ?? '') === 'S,MO' && ($electricaFields['paqueteS1'] ?? '') === 'MATERIALES RED ELECTRICA' && ($electricaFields['paqueteMO1'] ?? '') === 'MANO DE OBRA RED ELECTRICA', "{$fixture['name']}: Red Eléctrica propone modalidad y paquetes canónicos");
    lrrAssert(($pinturasFields['tipoContrato'] ?? '') === 'SI' && ($pinturasFields['paqueteSI1'] ?? '') === 'PINTURAS', "{$fixture['name']}: Pinturas propone modalidad y paquete canónicos");

    $applyContratos = $service->apply(SemiAutoService::MODULE_CONTRATOS, $context, lrrRunId($projectId, 'contratos'), [(string) $electricaSuggestion['suggestion_id'], (string) $pinturasSuggestion['suggestion_id']]);
    lrrAssert((int) ($applyContratos['aplicadas'] ?? 0) === 2 && (int) ($applyContratos['errores'] ?? 0) === 0, "{$fixture['name']}: apply de Contratos persiste las dos propuestas");

    $electrica = lrrActivity($db, $projectId, $week, LRR_FAMILIES['RED_ELECTRICA']);
    $pinturas = lrrActivity($db, $projectId, $week, LRR_FAMILIES['PINTURAS']);
    lrrAssert(($electrica['tipoContrato'] ?? '') === 'S,MO' && ($electrica['paqueteS1'] ?? '') === 'MATERIALES RED ELECTRICA' && ($electrica['paqueteMO1'] ?? '') === 'MANO DE OBRA RED ELECTRICA', "{$fixture['name']}: Red Eléctrica aplicada conserva paquetes");
    lrrAssert(($pinturas['tipoContrato'] ?? '') === 'SI' && ($pinturas['paqueteSI1'] ?? '') === 'PINTURAS', "{$fixture['name']}: Pinturas aplicada conserva paquete");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario'] = 'qa-listado-reclassified';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';

echo "=== Listado reclassified real projects ===\n";

register_shutdown_function(static function (): void {
    global $db;
    if (!$db instanceof Database) {
        return;
    }
    foreach (array_keys(LRR_FIXTURES) as $projectId) {
        try {
            lrrCleanup($db, $projectId);
        } catch (Throwable $e) {
            fwrite(STDERR, "LRR cleanup failed for {$projectId}: {$e->getMessage()}\n");
        }
    }
});

try {
    $db = Database::getInstance();
    $service = new SemiAutoService($db);
    foreach (array_keys(LRR_FIXTURES) as $projectId) {
        if (!lrrScopeIsVacant($db, $projectId)) {
            throw new RuntimeException("{$projectId}: la reserva de fixture no está vacía; se aborta sin borrar datos.");
        }
        lrrRunProject($db, $service, $projectId);
    }
} catch (Throwable $e) {
    lrrFail($e->getMessage());
} finally {
    if ($db instanceof Database) {
        foreach (array_keys(LRR_FIXTURES) as $projectId) {
            lrrCleanup($db, $projectId);
        }
    }
}

echo "=== Listado reclassified real projects: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
