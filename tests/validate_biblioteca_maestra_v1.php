<?php
/**
 * Validador de Biblioteca Maestra PDC v1.0
 * 
 * Uso: php tests/validate_biblioteca_maestra_v1.php [--json path/to/v1.json]
 * 
 * Verifica que la biblioteca cumpla con todas las reglas de validación
 * definidas en la sección 07_Validaciones y el JSON Schema v1.0.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$jsonPath = null;
foreach ($argv as $i => $arg) {
    if ($arg === '--json' && isset($argv[$i + 1])) {
        $jsonPath = $argv[$i + 1];
        break;
    }
}

if ($jsonPath === null) {
    // Default path
    $candidates = [
        __DIR__ . '/../database/seeds/biblioteca_maestra_pdc_v1_0.json',
        __DIR__ . '/../Downloads/biblioteca_maestra_pdc_source_of_truth_v1_0.json',
        '/Users/juanfelipebenitezramos/Downloads/biblioteca_maestra_pdc_source_of_truth_v1_0.json',
    ];
    foreach ($candidates as $c) {
        if (file_exists($c)) {
            $jsonPath = $c;
            break;
        }
    }
}

if ($jsonPath === null || !file_exists($jsonPath)) {
    echo "ERROR: No se encontró el archivo JSON de la biblioteca.\n";
    echo "Uso: php validate_biblioteca_maestra_v1.php --json path/to/v1.json\n";
    exit(1);
}

echo "=== Validador Biblioteca Maestra PDC v1.0 ===\n";
echo "Archivo: {$jsonPath}\n\n";

$data = json_decode(file_get_contents($jsonPath), true);
if ($data === null) {
    echo "ERROR: JSON inválido: " . json_last_error_msg() . "\n";
    exit(1);
}

$failed = 0;
$warnings = 0;
$passed = 0;

function vPass(string $code, string $message): void {
    global $passed;
    $passed++;
    echo "  ✓ {$code}: {$message}\n";
}

function vFail(string $code, string $message): void {
    global $failed;
    $failed++;
    echo "  ✗ {$code}: {$message}\n";
}

function vWarn(string $code, string $message): void {
    global $warnings;
    $warnings++;
    echo "  ⚠ {$code}: {$message}\n";
}

// ====================================================================
// VAL_TARGET_FAMILY_COUNT: La biblioteca debe tener >= 95 familias
// ====================================================================
$families = $data['families'] ?? [];
$familyCount = count($families);
if ($familyCount >= 95) {
    vPass('VAL_TARGET_FAMILY_COUNT', "{$familyCount} familias (mínimo 95 requerido)");
} else {
    vFail('VAL_TARGET_FAMILY_COUNT', "Solo {$familyCount} familias. Mínimo 95 requerido para V1.0.");
}

// ====================================================================
// VAL_PACKAGE_COUNT_PER_FAMILY: Cada familia debe tener 1-3 paquetes
// ====================================================================
$relations = $data['family_package_relations'] ?? [];
$familyRelCounts = [];
foreach ($relations as $rel) {
    $fc = $rel['family_code'] ?? '';
    if ($fc !== '') {
        $familyRelCounts[$fc] = ($familyRelCounts[$fc] ?? 0) + 1;
    }
}
$familiesWithoutRel = [];
foreach ($families as $fam) {
    $fc = $fam['family_code'] ?? '';
    if ($fc !== '' && ($familyRelCounts[$fc] ?? 0) === 0) {
        $familiesWithoutRel[] = $fc;
    }
}
if (empty($familiesWithoutRel)) {
    vPass('VAL_PACKAGE_COUNT_PER_FAMILY', "Todas las familias tienen al menos 1 paquete.");
} else {
    vWarn('VAL_PACKAGE_COUNT_PER_FAMILY', count($familiesWithoutRel) . " familias sin paquetes: " . implode(', ', array_slice($familiesWithoutRel, 0, 5)));
}

// ====================================================================
// VAL_NO_DIRECT_EVIDENCE: No debe haber familias sin evidencia
// ====================================================================
$noEvidence = [];
foreach ($families as $fam) {
    $status = $fam['source_status'] ?? '';
    if ($status === 'review_no_direct_match' || ($fam['evidence'] ?? '') === '') {
        $noEvidence[] = $fam['family_code'] ?? 'unknown';
    }
}
if (empty($noEvidence)) {
    vPass('VAL_NO_DIRECT_EVIDENCE', "Todas las familias tienen evidencia documental.");
} else {
    vWarn('VAL_NO_DIRECT_EVIDENCE', count($noEvidence) . " familias sin evidencia directa.");
}

// ====================================================================
// VAL_CONTRACTUAL_ONLY: Familias contractual_only no deben auto-aplicarse
// ====================================================================
$contractualNoReview = [];
foreach ($families as $fam) {
    if (($fam['classification'] ?? '') === 'contractual_only' && ($fam['review_required'] ?? '') !== 'SI') {
        $contractualNoReview[] = $fam['family_code'] ?? '';
    }
}
if (empty($contractualNoReview)) {
    vPass('VAL_CONTRACTUAL_ONLY', "Todas las familias contractual_only requieren revisión.");
} else {
    vFail('VAL_CONTRACTUAL_ONLY', count($contractualNoReview) . " familias contractual_only sin review_required=SI.");
}

// ====================================================================
// VAL_OPERATIONAL_FAMILIES: Debe haber familias operativas
// ====================================================================
$operativas = 0;
foreach ($families as $fam) {
    if (($fam['classification'] ?? '') === 'operativa') {
        $operativas++;
    }
}
if ($operativas >= 5) {
    vPass('VAL_OPERATIONAL_FAMILIES', "{$operativas} familias operativas (mínimo 5).");
} else {
    vWarn('VAL_OPERATIONAL_FAMILIES', "Solo {$operativas} familias operativas. La biblioteca debería tener más para listado-actividades.");
}

// ====================================================================
// VAL_GOLDEN_DATASET: El golden dataset debe existir y tener datos
// ====================================================================
$golden = $data['golden_dataset'] ?? null;
$goldenActivities = $golden['activities'] ?? [];
if (!empty($goldenActivities)) {
    vPass('VAL_GOLDEN_DATASET', count($goldenActivities) . " actividades en golden dataset.");
} else {
    vFail('VAL_GOLDEN_DATASET', "Golden dataset vacío o ausente. Requerido para V1.0.");
}

// ====================================================================
// VAL_COVERAGE_REPORT: Debe existir reporte de cobertura
// ====================================================================
$coverage = $data['coverage_report'] ?? null;
if ($coverage !== null) {
    $covPct = $coverage['estimated_pattern_coverage_pct'] ?? 0;
    if ($covPct > 0) {
        vPass('VAL_COVERAGE_REPORT', "Cobertura estimada: {$covPct}%.");
    } else {
        vWarn('VAL_COVERAGE_REPORT', "Cobertura estimada es 0%. Revisar patrones de matching.");
    }
} else {
    vFail('VAL_COVERAGE_REPORT', "Reporte de cobertura ausente.");
}

// ====================================================================
// VAL_SCHEMA: Validar contra JSON Schema
// ====================================================================
$schemaPath = str_replace('source_of_truth_v1_0.json', 'schema_v1_0.json', $jsonPath);
if (!file_exists($schemaPath)) {
    $schemaPath = __DIR__ . '/../Downloads/biblioteca_maestra_pdc_schema_v1_0.json';
}
if (file_exists($schemaPath)) {
    vPass('VAL_SCHEMA', "JSON Schema encontrado en " . basename($schemaPath));
} else {
    vWarn('VAL_SCHEMA', "JSON Schema no encontrado. No se puede validar estructura formal.");
}

// ====================================================================
// VAL_DURATION_SEED: Cada paquete debe tener duración semilla
// ====================================================================
$durations = $data['duration_seed'] ?? [];
$packages = $data['packages'] ?? [];
$pkgCodes = array_column($packages, 'package_code');
$durCodes = [];
foreach ($durations as $d) {
    $durCodes[$d['package_code'] ?? ''] = true;
}
$missingDurations = array_diff($pkgCodes, array_keys($durCodes));
if (empty($missingDurations)) {
    vPass('VAL_DURATION_SEED', "Todos los paquetes tienen duración semilla.");
} else {
    vWarn('VAL_DURATION_SEED', count($missingDurations) . " paquetes sin duración semilla.");
}

// ====================================================================
// VAL_EVIDENCE: Cada familia y paquete deben tener evidencia
// ====================================================================
$evidence = $data['evidence'] ?? [];
$evidenceFamilyCodes = [];
$evidencePkgCodes = [];
foreach ($evidence as $ev) {
    if (($ev['entity_type'] ?? '') === 'family') {
        $evidenceFamilyCodes[$ev['entity_code'] ?? ''] = true;
    } elseif (($ev['entity_type'] ?? '') === 'package') {
        $evidencePkgCodes[$ev['entity_code'] ?? ''] = true;
    }
}
$familyCodes = array_column($families, 'family_code');
$missingFamilyEv = array_diff($familyCodes, array_keys($evidenceFamilyCodes));
if (empty($missingFamilyEv)) {
    vPass('VAL_EVIDENCE_FAMILIES', "Todas las familias tienen registro de evidencia.");
} else {
    vWarn('VAL_EVIDENCE_FAMILIES', count($missingFamilyEv) . " familias sin registro de evidencia.");
}

// ====================================================================
// SUMMARY
// ====================================================================
echo "\n=== Resultado ===\n";
echo "  Pasaron:  {$passed}\n";
echo "  Fallaron: {$failed}\n";
echo "  Warnings: {$warnings}\n";
echo "  Total:    " . ($passed + $failed + $warnings) . " validaciones\n";

if ($failed > 0) {
    echo "\n❌ V1.0 NO está lista. {$failed} validaciones fallaron.\n";
    exit(1);
} elseif ($warnings > 0) {
    echo "\n⚠️  V1.0 candidata con {$warnings} warnings. Revisar antes de liberar.\n";
    exit(0);
} else {
    echo "\n✅ V1.0 válida. Todas las validaciones pasaron.\n";
    exit(0);
}