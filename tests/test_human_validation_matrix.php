<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../docs/qa/pdc_family_corpus_extractor.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;

$failed = 0;
$root = dirname(__DIR__);
$xlsxPath = $root . '/docs/qa/matriz-validacion-humana.xlsx';
$jsonPath = $root . '/docs/qa/matriz-validacion-humana.summary.json';
$mdPath = $root . '/docs/qa/matriz-validacion-humana.summary.md';

function matrixPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function matrixFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function matrixAssert(bool $condition, string $message): void
{
    $condition ? matrixPass($message) : matrixFail($message);
}

echo "=== Human validation matrix ===\n";

matrixAssert(is_file($xlsxPath), 'existe el XLSX de matriz');
matrixAssert(is_file($jsonPath), 'existe el resumen JSON');
matrixAssert(is_file($mdPath), 'existe el resumen Markdown');

if (is_file($xlsxPath)) {
    $spreadsheet = IOFactory::load($xlsxPath);
    $validation = $spreadsheet->getSheetByName('Validacion');
    $lists = $spreadsheet->getSheetByName('Listas');
    $summary = $spreadsheet->getSheetByName('Resumen');
    matrixAssert($validation !== null, 'existe la hoja Validacion');
    matrixAssert($lists !== null, 'existe la hoja Listas');
    matrixAssert($summary !== null, 'existe la hoja Resumen');

    if ($validation !== null) {
        $headers = [];
        foreach (range(1, count(matrixHeaders())) as $column) {
            $headers[] = (string) $validation->getCell(Coordinate::stringFromColumnIndex($column) . '1')->getValue();
        }
        matrixAssert($headers === matrixHeaders(), 'encabezados esperados');
        matrixAssert(((int) $validation->getHighestDataRow() - 1) === MATRIX_SAMPLE_SIZE, 'contiene 300 casos');
        foreach (['J2' => 'decision_humana', 'K2' => 'familia_correcta', 'N2' => 'accion_recomendada'] as $cell => $label) {
            $rule = $validation->getCell($cell)->getDataValidation();
            matrixAssert($rule->getType() === DataValidation::TYPE_LIST, "lista desplegable en {$label}");
            matrixAssert($rule->getFormula1() !== '', "rango de lista en {$label}");
        }
        foreach (['J' => 'decision_humana', 'K' => 'familia_correcta', 'L' => 'nombre_actividad_correcto', 'M' => 'motivo', 'N' => 'accion_recomendada', 'O' => 'clasificacion_familia'] as $column => $label) {
            $emptyFound = false;
            for ($row = 2; $row <= MATRIX_SAMPLE_SIZE + 1; $row++) {
                if (trim((string) $validation->getCell($column . $row)->getValue()) === '') {
                    $emptyFound = true;
                    break;
                }
            }
            matrixAssert(!$emptyFound, "{$label} viene prellenada");
        }
        $invalidDecisionFound = false;
        $invalidFamilyFound = false;
        $oldRciFamilyFound = false;
        for ($row = 2; $row <= MATRIX_SAMPLE_SIZE + 1; $row++) {
            $decision = trim((string) $validation->getCell('J' . $row)->getValue());
            $suggestedFamily = trim((string) $validation->getCell('I' . $row)->getValue());
            $correctFamily = trim((string) $validation->getCell('K' . $row)->getValue());
            if ($decision === 'No es actividad') {
                $invalidDecisionFound = true;
            }
            foreach ([$suggestedFamily, $correctFamily] as $family) {
                if (!matrixFamilyAllowed($family)) {
                    $invalidFamilyFound = true;
                }
                if (matrixFamilyName($family) !== $family) {
                    $oldRciFamilyFound = true;
                }
            }
        }
        matrixAssert(!$invalidDecisionFound, 'no contiene decision No es actividad');
        matrixAssert(!$invalidFamilyFound, 'no contiene familias excluidas de Mano de Obra');
        matrixAssert(!$oldRciFamilyFound, 'no contiene familias RCI sin unificar');
    }
    if ($lists !== null) {
        $decisionOptions = [];
        for ($row = 2; $row <= $lists->getHighestDataRow('A'); $row++) {
            $value = trim((string) $lists->getCell('A' . $row)->getValue());
            if ($value !== '') {
                $decisionOptions[] = $value;
            }
        }
        matrixAssert(!in_array('No es actividad', $decisionOptions, true), 'desplegable no incluye No es actividad');

        $familyOptions = [];
        for ($row = 2; $row <= $lists->getHighestDataRow('B'); $row++) {
            $value = trim((string) $lists->getCell('B' . $row)->getValue());
            if ($value !== '') {
                $familyOptions[] = $value;
            }
        }
        matrixAssert(count($familyOptions) >= 70, 'desplegable usa el catalogo vivo de familias depurado');
        foreach (MATRIX_EXCLUDED_FAMILIES as $excludedFamily) {
            matrixAssert(!in_array($excludedFamily, $familyOptions, true), "excluye {$excludedFamily}");
        }
        foreach (array_keys(MATRIX_FAMILY_ALIASES) as $oldRciFamily) {
            matrixAssert(!in_array($oldRciFamily, $familyOptions, true), "no incluye {$oldRciFamily} como familia separada");
        }
        foreach ([MATRIX_RCI_FAMILY, 'Pisos y Enchapes', 'Deteccion de Incendio', 'Botada de Escombros'] as $expectedFamily) {
            matrixAssert(in_array($expectedFamily, $familyOptions, true), "incluye familia guardada {$expectedFamily}");
        }
    }
    $spreadsheet->disconnectWorksheets();
}

if (is_file($jsonPath)) {
    $summary = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
    matrixAssert(($summary['total_cases'] ?? null) === MATRIX_SAMPLE_SIZE, 'resumen JSON reporta 300 casos');
    foreach (['project_counts', 'pattern_counts', 'family_counts', 'decision_counts', 'classification_counts'] as $key) {
        matrixAssert(!empty($summary[$key]) && is_array($summary[$key]), "resumen tiene {$key}");
    }
    matrixAssert(isset($summary['classification_counts']['elemento_contractual']), 'resumen reporta elementos contractuales separados');
    matrixAssert(isset($summary['classification_counts']['familia_operativa']), 'resumen reporta familias operativas');
    $projectNames = implode(' ', array_keys($summary['project_counts'] ?? []));
    matrixAssert(str_contains(normalize($projectNames), 'JMC') || str_contains(normalize($projectNames), 'AEROPUERTO'), 'muestra incluye JMC');
    matrixAssert(str_contains(normalize($projectNames), 'DA PORTO') || str_contains(normalize($projectNames), 'DAPORTO'), 'muestra incluye Da Porto');
    matrixAssert(str_contains(normalize($projectNames), 'MILAN'), 'muestra incluye Milan');
    matrixAssert(str_contains(normalize($projectNames), 'METROLINEA'), 'muestra incluye Metrolinea');
}

echo "=== Human validation matrix: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
