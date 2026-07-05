<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Support\ActivityMatcher;

$db = Database::getInstance();
$failed = 0;
$projectId = 987654;
$week = 1;
$prefix = 'da_porto_feedback_test';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario'] = 'jbenitez';
$_SESSION['permiso'] = 'A';
$_SESSION['permiso_canonico'] = 'A';

function passDaPorto(string $message): void
{
    echo "  PASS: {$message}\n";
}

function failDaPorto(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function cleanupDaPorto(Database $db, int $projectId, string $prefix): void
{
    foreach ([
        'semi_auto_decisions',
        'semi_auto_suggestions',
        'semi_auto_runs',
        'semi_auto_feedback',
        'semi_auto_assistant_feedback',
        'semi_auto_learning_rules',
        'semi_auto_learning_candidates',
        'semi_auto_proactive_queue',
        'actividades',
        'pdc',
        'programa_consolidado',
        'programa',
        'semanas_activas',
    ] as $table) {
        $db->query("DELETE FROM {$table} WHERE project_id = ?", [$projectId]);
    }
    $db->query("DELETE FROM general_proyectos_procesos WHERE Id = ? OR Base_de_Datos = ?", [$projectId, $prefix]);
}

function applyDaPortoPatch(Database $db): void
{
    foreach ([
        __DIR__ . '/../database/patches/20260701_da_porto_feedback_semi_auto.sql',
        __DIR__ . '/../database/migrations/20260708_contract_defaults_feedback.sql',
        __DIR__ . '/../database/migrations/20260709_inactivate_alias_contractual_families.sql',
        __DIR__ . '/../database/migrations/20260710_equipment_families_require_review.sql',
    ] as $patch) {
        applyDaPortoSqlFile($db, $patch);
    }
}

function applyDaPortoSqlFile(Database $db, string $patch): void
{
    $sql = file_get_contents($patch);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer ' . basename($patch) . '.');
    }
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $db->query($statement);
    }
}

function expectMatchCode(ActivityMatcher $matcher, array $rules, string $activity, string $expectedCode): void
{
    $match = $matcher->matchActivity(['Actividad' => $activity], $rules);
    $actual = (string) ($match['familia_codigo'] ?? '');
    $actual === $expectedCode
        ? passDaPorto("{$activity} -> {$expectedCode}")
        : failDaPorto("{$activity} expected {$expectedCode}, got " . ($actual !== '' ? $actual : 'no match'));
}

function expectFamilyExists(Database $db, string $code): void
{
    $exists = (int) $db->query(
        "SELECT COUNT(*) FROM general_pdc_familias WHERE codigo = ?",
        [$code],
    )->fetchColumn();
    $exists === 1 ? passDaPorto("family {$code} exists") : failDaPorto("family {$code} is missing");
}

function expectFamilyInactive(Database $db, string $code): void
{
    $active = (int) $db->query(
        "SELECT COALESCE(activa, 1) FROM general_pdc_familias WHERE codigo = ? LIMIT 1",
        [$code],
    )->fetchColumn();
    $active === 0
        ? passDaPorto("family {$code} inactive for Listado")
        : failDaPorto("family {$code} should be inactive for Listado");
}

function expectContractualElement(Database $db, string $name, string $package): void
{
    $exists = (int) $db->query(
        "SELECT COUNT(*) FROM general_pdc_contractual_elements WHERE nombre = ? AND paquete_nombre = ? AND activa = 1",
        [$name, $package],
    )->fetchColumn();
    $exists > 0
        ? passDaPorto("contractual {$name} -> {$package}")
        : failDaPorto("contractual {$name} -> {$package} is missing");
}

function familyItems(Database $db, string $code): array
{
    return $db->query(
        "SELECT COALESCE(i.tipo_paquete, o.tipo_paquete) AS tipo_paquete, i.paquete_nombre
         FROM general_pdc_familias f
         JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
         JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
         WHERE f.codigo = ?
         ORDER BY i.orden ASC, i.id ASC",
        [$code],
    )->fetchAll(PDO::FETCH_ASSOC);
}

function expectItem(Database $db, string $code, string $type, string $name): void
{
    foreach (familyItems($db, $code) as $item) {
        if (($item['tipo_paquete'] ?? '') === $type && ($item['paquete_nombre'] ?? '') === $name) {
            passDaPorto("{$code} has {$type}: {$name}");
            return;
        }
    }
    failDaPorto("{$code} missing {$type}: {$name}");
}

function expectOption(Database $db, string $code, int $type, string $package): void
{
    $exists = (int) $db->query(
        "SELECT COUNT(*)
         FROM general_pdc_familias f
         JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
         WHERE f.codigo = ? AND o.tipo_contrato = ? AND o.tipo_paquete = ?",
        [$code, $type, $package],
    )->fetchColumn();
    $exists > 0
        ? passDaPorto("{$code} option {$type}: {$package}")
        : failDaPorto("{$code} missing option {$type}: {$package}");
}

function expectReviewFlag(Database $db, string $code, int $expected): void
{
    $actual = (int) $db->query(
        "SELECT siempre_revision FROM general_pdc_familias WHERE codigo = ?",
        [$code],
    )->fetchColumn();
    $actual === $expected
        ? passDaPorto("{$code} review flag is {$expected}")
        : failDaPorto("{$code} review flag expected {$expected}, got {$actual}");
}

echo "=== Da Porto semi-auto feedback model ===\n";

try {
    applyDaPortoPatch($db);
    cleanupDaPorto($db, $projectId, $prefix);

    $matcher = new ActivityMatcher();
    $rules = $matcher->loadRules();

    foreach ([
        'REVOQUE_HUMEDO',
        'REVOQUE_SECO',
        'CABINAS_BANO',
        'CARPINTERIA_METALICA',
        'PLANTA_ELECTRICA',
        'MALACATE',
        'GRIFERIAS_INCRUSTACIONES',
        'ASEO',
        'BOTADA_ESCOMBROS',
        'CAMPAMENTO',
        'AMENIDADES_CUBIERTA',
    ] as $code) {
        expectFamilyExists($db, $code);
    }
    expectFamilyInactive($db, 'GEODREN');
    expectFamilyInactive($db, 'MALACATE');
    expectFamilyInactive($db, 'BOTADA_ESCOMBROS');
    expectFamilyInactive($db, 'CAMPAMENTO');
    expectFamilyInactive($db, 'AMENIDADES_CUBIERTA');
    expectContractualElement($db, 'Geodren', 'GEODREN');
    expectContractualElement($db, 'Malacate', 'MALACATE');
    expectContractualElement($db, 'Botada de Escombros', 'RETIRO Y DISPOSICION DE ESCOMBROS');
    expectContractualElement($db, 'Campamento de Obra', 'CAMPAMENTO DE OBRA');
    expectContractualElement($db, 'Amenidades Especiales de Cubierta', 'AMENIDADES ESPECIALES DE CUBIERTA');

    expectMatchCode($matcher, $rules, 'REVOQUE HUMEDO MUROS APARTAMENTOS', 'REVOQUE_HUMEDO');
    expectMatchCode($matcher, $rules, 'REVOQUE SECO EN DRYWALL', 'REVOQUE_SECO');
    expectMatchCode($matcher, $rules, 'PLANTA ELECTRICA DE EMERGENCIA', 'PLANTA_ELECTRICA');
    expectMatchCode($matcher, $rules, 'CABINAS DE BANO EN VIDRIO', 'CABINAS_BANO');
    expectMatchCode($matcher, $rules, 'ESPEJOS BANO APARTAMENTOS', 'CARPINTERIA_METALICA');
    expectMatchCode($matcher, $rules, 'BARANDAS DE BALCON EN VIDRIO', 'CARPINTERIA_METALICA');
    expectMatchCode($matcher, $rules, 'PASAMANOS TUBULAR ESCALERAS', 'CARPINTERIA_METALICA');
    $malacate = $matcher->matchActivity(['Actividad' => 'MALACATE DE OBRA'], $rules);
    $malacate === null
        ? passDaPorto('MALACATE routes to Contratos, not Listado')
        : failDaPorto('MALACATE should not create Listado family, got ' . ($malacate['familia_codigo'] ?? 'unknown'));
    expectMatchCode($matcher, $rules, 'GRIFERIAS E INCRUSTACIONES APARTAMENTOS', 'GRIFERIAS_INCRUSTACIONES');
    $geodren = $matcher->matchActivity(['Actividad' => 'GEODREN MUROS DE CONTENCION'], $rules);
    $geodren === null
        ? passDaPorto('GEODREN routes to Contratos, not Listado')
        : failDaPorto('GEODREN should not create Listado family, got ' . ($geodren['familia_codigo'] ?? 'unknown'));
    expectMatchCode($matcher, $rules, 'ASEO FINAL DE APARTAMENTOS', 'ASEO');
    $botadaEscombros = $matcher->matchActivity(['Actividad' => 'BOTADA DE ESCOMBROS ACABADOS'], $rules);
    $botadaEscombros === null
        ? passDaPorto('BOTADA DE ESCOMBROS routes to Contratos, not Listado')
        : failDaPorto('BOTADA DE ESCOMBROS should not create Listado family, got ' . ($botadaEscombros['familia_codigo'] ?? 'unknown'));

    $botadaTierra = $matcher->matchActivity(['Actividad' => 'BOTADA DE TIERRA EXCAVACION'], $rules);
    (($botadaTierra['familia_codigo'] ?? '') !== 'BOTADA_ESCOMBROS')
        ? passDaPorto('BOTADA DE TIERRA does not map to BOTADA_ESCOMBROS')
        : failDaPorto('BOTADA DE TIERRA should not map to BOTADA_ESCOMBROS');

    expectOption($db, 'SANITARIOS', 1, 'Mano de Obra y Suministro por separado');
    expectItem($db, 'SANITARIOS', 'Suministro', 'APARATOS SANITARIOS');
    expectItem($db, 'SANITARIOS', 'Mano de Obra', 'INSTALACION APARATOS SANITARIOS');
    expectOption($db, 'GRIFERIAS_INCRUSTACIONES', 5, 'Orden de Compra');
    expectItem($db, 'GRIFERIAS_INCRUSTACIONES', 'Orden de Compra', 'GRIFERIAS E INCRUSTACIONES');
    expectOption($db, 'PINTURAS', 2, 'Suministro e Instalación');
    expectOption($db, 'PLANTA_ELECTRICA', 2, 'Suministro e Instalación');
    expectOption($db, 'CARPINTERIA_METALICA', 2, 'Suministro e Instalación');
    expectItem($db, 'CARPINTERIA_MADERA', 'Suministro', 'CARPINTERIA MADERA - FABRICACION Y SUMINISTRO');
    expectItem($db, 'CARPINTERIA_MADERA', 'Mano de Obra', 'CARPINTERIA MADERA - INSTALACION');
    expectOption($db, 'MALACATE', 6, 'Equipos');
    expectItem($db, 'MALACATE', 'Equipos', 'MALACATE');
    expectReviewFlag($db, 'RED_ELECTRICA', 0);
    expectReviewFlag($db, 'PINTURAS', 0);
    expectReviewFlag($db, 'PLANTA_ELECTRICA', 0);

    $db->query(
        "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
         VALUES (?, 'Da Porto Feedback Test', ?, 'Construccion', 1, 1, 1)",
        [$projectId, $prefix],
    );
    $db->query(
        "INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (?, 1, ?, '2026-07-01', '2026-07-07')",
        [$projectId, $week],
    );
    $db->query(
        "INSERT INTO programa
         (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, 1001, 1001, '1.1', 'CAMPAMENTO DE OBRA', 0, '2026-07-10', '2026-07-20')",
        [$projectId],
    );
    $db->query(
        "INSERT INTO programa_consolidado
         (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
         VALUES (?, 1, 1, ?, 1001, 1001, '1.1', 'CAMPAMENTO DE OBRA', 0, '2026-07-10', '2026-07-20', 1)",
        [$projectId, $week],
    );
    $db->query(
        "INSERT INTO actividades
         (project_id, Id, codigo, actividad, descripcionActividad, fechaInicio, tipoContrato, paqueteMO1, semanaActualizacion, confianza_deteccion)
         VALUES (?, 1, 1, 'Aseo de Apartamentos y Obra', 'Aseo apartamentos', '2026-07-10', 'MO', 'ASEO APARTAMENTOS Y OBRA', ?, 88)",
        [$projectId, $week],
    );

    $service = new SemiAutoService($db);
    $preview = $service->preview(SemiAutoService::MODULE_LISTADO, [
        'projectId' => $projectId,
        'dbPrefix' => $prefix,
        'semana' => $week,
    ]);

    $campamento = null;
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        if (($suggestion['proposed']['actividad'] ?? '') === 'Campamento de Obra') {
            $campamento = $suggestion;
            break;
        }
    }

    $campamento === null
        ? passDaPorto('Campamento routes to Contratos and does not create Listado suggestion')
        : failDaPorto('Campamento should not generate Listado suggestion');

    $pdcPreview = $service->preview(SemiAutoService::MODULE_PDC, [
        'projectId' => $projectId,
        'dbPrefix' => $prefix,
        'semana' => $week,
    ]);
    $aseoPdc = null;
    foreach (($pdcPreview['suggestions'] ?? []) as $suggestion) {
        if (($suggestion['proposed']['paqueteContratacion'] ?? '') === 'ASEO APARTAMENTOS Y OBRA') {
            $aseoPdc = $suggestion;
            break;
        }
    }
    if ($aseoPdc === null) {
        failDaPorto('Aseo PDC suggestion was not generated');
    } else {
        empty($aseoPdc['preselected'])
            ? passDaPorto('Aseo PDC requires review and is not preselected')
            : failDaPorto('Aseo PDC should not be preselected');
        empty($aseoPdc['proposed']['observacionesContrato'])
            ? passDaPorto('PDC does not auto-fill observacionesContrato')
            : failDaPorto('PDC should not auto-fill observacionesContrato');
    }
} catch (Throwable $e) {
    failDaPorto($e->getMessage());
} finally {
    cleanupDaPorto($db, $projectId, $prefix);
}

echo $failed === 0
    ? "=== Da Porto semi-auto feedback model: OK ===\n"
    : "=== Da Porto semi-auto feedback model: FAIL ({$failed}) ===\n";
exit($failed === 0 ? 0 : 1);
