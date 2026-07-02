<?php

declare(strict_types=1);

use App\Support\OperationalFamilyPolicy;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/Core/Database.php';

const EXPECTED_EXCEL_FILES = 8;
const EXPECTED_PROGRAM_PROJECTS = 14;
const MATRIX_SAMPLE_SIZE = 300;
const MATRIX_RCI_FAMILY = OperationalFamilyPolicy::RCI_FAMILY;
const MATRIX_EXCLUDED_FAMILIES = [
    'Acero de Refuerzo y Estructural',
    'Aligerantes Perdidos y Recuperables',
    'Contenedores',
    'Encofrado y Obra Falsa',
    'Equipos de Extincion',
    'Estuco',
    'Fachada HPL, Vidrio y Aluminio',
    'Geodren',
    'Losas de Cimentacion',
    'Luminarias y Artefactos Electricos',
    'Mano de Obra - Acabados',
    'Mano de Obra - Cimentacion',
    'Mano de Obra - Estructura',
    'Mano de Obra - Excavaciones',
    'Mano de Obra - Instalaciones',
    'Mano de Obra - Mamposteria',
    'Mano de Obra - Urbanismo',
];
const MATRIX_FAMILY_ALIASES = [
    'Enchapes Ceramicos en Muros' => 'Pisos y Enchapes',
    'Red RCI' => MATRIX_RCI_FAMILY,
    'Red Contra Incendio - Piping' => MATRIX_RCI_FAMILY,
];

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(main($argv));
}

function main(array $argv): int
{
    $root = dirname(__DIR__, 2);
    $pdcDir = $root . '/docs/pdc';
    $outMd = $root . '/docs/qa/corpus-maestro-familias-pdc.md';
    $outJson = $root . '/docs/qa/corpus-maestro-familias-pdc.summary.json';
    $matrixXlsx = $root . '/docs/qa/matriz-validacion-humana.xlsx';
    $matrixJson = $root . '/docs/qa/matriz-validacion-humana.summary.json';
    $matrixMd = $root . '/docs/qa/matriz-validacion-humana.summary.md';

    $db = Database::getInstance();

    if (in_array('--verify-matrix', $argv, true)) {
        return verifyMatrix($matrixXlsx, $matrixJson, $matrixMd);
    }

    $corpus = buildCorpus($db, $pdcDir);
    $errors = validateCorpus($corpus);

    if (in_array('--verify', $argv, true)) {
        foreach ($errors as $error) {
            fwrite(STDERR, "[ERROR] {$error}\n");
        }
        echo "Fuentes Excel: " . count($corpus['excel_files']) . "\n";
        echo "Proyectos con cronograma: " . count($corpus['program_projects']) . "\n";
        echo "Familias candidatas: " . count($corpus['families']) . "\n";
        echo "Casos dudosos: " . count($corpus['confusions']) . "\n";
        return empty($errors) ? 0 : 1;
    }

    if (in_array('--matrix', $argv, true)) {
        $matrix = buildHumanValidationMatrix($corpus, MATRIX_SAMPLE_SIZE);
        writeHumanValidationMatrix($matrix, $matrixXlsx, $matrixJson, $matrixMd);
        echo "Matriz generada: {$matrixXlsx}\n";
        echo "Resumen JSON generado: {$matrixJson}\n";
        echo "Resumen Markdown generado: {$matrixMd}\n";
        return 0;
    }

    file_put_contents($outMd, renderMarkdown($corpus));
    file_put_contents(
        $outJson,
        json_encode(summaryPayload($corpus), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
    );

    echo "Documento generado: {$outMd}\n";
    echo "Resumen generado: {$outJson}\n";
    if (!empty($errors)) {
        foreach ($errors as $error) {
            fwrite(STDERR, "[WARN] {$error}\n");
        }
    }

    return 0;
}

function buildCorpus(Database $db, string $pdcDir): array
{
    $corpus = [
        'generated_at' => date('c'),
        'program_projects' => loadProgramProjects($db),
        'pdc_rows' => loadPdcRows($db),
        'report_pdc_rows' => loadReportPdcRows($db),
        'activity_rows' => loadActivityRows($db),
        'catalog' => loadCurrentCatalog($db),
        'excel_files' => [],
        'families' => [],
        'packages' => [],
        'confusions' => [],
        'matrix_candidates' => [],
        'samples' => ['jmc' => [], 'da_porto' => [], 'milan' => [], 'metrolinea' => []],
    ];

    foreach (loadProgramRows($db) as $row) {
        addSourceRecord($corpus, [
            'source' => 'programa_consolidado',
            'project_id' => (int) $row['project_id'],
            'project' => (string) $row['Proyecto_Proceso'],
            'source_id' => (string) ($row['unique_id'] ?? $row['row_id'] ?? ''),
            'week' => (string) ($row['Semana'] ?? ''),
            'start_date' => (string) ($row['Fecha_Inicio'] ?? ''),
            'activity' => (string) $row['Actividad'],
            'chapter' => chapterFromActivity((string) $row['Actividad']),
            'package' => '',
            'modality' => inferModality((string) $row['Actividad']),
        ]);
    }

    foreach ($corpus['activity_rows'] as $row) {
        addSourceRecord($corpus, [
            'source' => 'actividades',
            'project_id' => (int) $row['project_id'],
            'project' => (string) $row['Proyecto_Proceso'],
            'source_id' => (string) ($row['Id'] ?? ''),
            'week' => (string) ($row['semanaActualizacion'] ?? ''),
            'start_date' => (string) ($row['fechaInicio'] ?? ''),
            'activity' => (string) $row['actividad'],
            'chapter' => '',
            'package' => '',
            'modality' => normalizeModality((string) ($row['tipoContrato'] ?? '')),
        ]);
    }

    foreach ($corpus['pdc_rows'] as $row) {
        addSourceRecord($corpus, [
            'source' => 'pdc',
            'project_id' => (int) $row['project_id'],
            'project' => (string) $row['Proyecto_Proceso'],
            'source_id' => (string) ($row['pdc_row_id'] ?? ''),
            'week' => (string) ($row['semana'] ?? ''),
            'start_date' => (string) ($row['fechaInicio'] ?? ''),
            'activity' => trimText(($row['paqueteContratacion'] ?? '') . ' ' . ($row['contratos'] ?? '')),
            'chapter' => '',
            'package' => (string) ($row['paqueteContratacion'] ?? ''),
            'modality' => normalizeModality((string) ($row['tipoPaquete'] ?? '')),
        ]);
    }

    foreach ($corpus['report_pdc_rows'] as $row) {
        addSourceRecord($corpus, [
            'source' => 'general_informe_pdc',
            'project_id' => (int) $row['project_id'],
            'project' => (string) $row['Proyecto'],
            'source_id' => (string) ($row['id'] ?? ''),
            'week' => (string) ($row['semana'] ?? ''),
            'start_date' => (string) ($row['fechaInicio'] ?? ''),
            'activity' => trimText(($row['paqueteContratacion'] ?? '') . ' ' . ($row['contratos'] ?? '')),
            'chapter' => '',
            'package' => (string) ($row['paqueteContratacion'] ?? ''),
            'modality' => normalizeModality((string) ($row['tipoPaquete'] ?? '')),
        ]);
    }

    foreach (extractExcelPlans($pdcDir) as $fileSummary) {
        $corpus['excel_files'][] = $fileSummary['summary'];
        foreach ($fileSummary['records'] as $record) {
            addSourceRecord($corpus, $record);
        }
    }

    finalizeFamilies($corpus);
    return $corpus;
}

function loadProgramProjects(Database $db): array
{
    return $db->query(
        "SELECT g.Id AS project_id, g.Proyecto_Proceso, g.Base_de_Datos, g.Area,
                COUNT(pc.project_id) AS filas,
                SUM(CASE WHEN COALESCE(pc.Titulo, 0) = 0 THEN 1 ELSE 0 END) AS actividades,
                MIN(pc.Semana) AS semana_min, MAX(pc.Semana) AS semana_max,
                MIN(pc.Fecha_Inicio) AS fecha_min, MAX(pc.Fecha_Inicio) AS fecha_max
         FROM general_proyectos_procesos g
         JOIN programa_consolidado pc ON pc.project_id = g.Id
         GROUP BY g.Id, g.Proyecto_Proceso, g.Base_de_Datos, g.Area
         ORDER BY filas DESC",
    )->fetchAll(PDO::FETCH_ASSOC);
}

function loadProgramRows(Database $db): array
{
    return $db->query(
        "SELECT pc.project_id, g.Proyecto_Proceso, pc.row_id, pc.unique_id, pc.Semana, pc.Fecha_Inicio, pc.Actividad
         FROM programa_consolidado pc
         JOIN general_proyectos_procesos g ON g.Id = pc.project_id
         WHERE COALESCE(pc.Titulo, 0) = 0
           AND pc.Actividad IS NOT NULL
           AND pc.Actividad <> ''",
    )->fetchAll(PDO::FETCH_ASSOC);
}

function loadActivityRows(Database $db): array
{
    return $db->query(
        "SELECT a.project_id, g.Proyecto_Proceso, a.Id, a.semanaActualizacion,
                a.fechaInicio, a.actividad, a.tipoContrato
         FROM actividades a
         JOIN general_proyectos_procesos g ON g.Id = a.project_id
         WHERE COALESCE(a.actividad, '') <> ''",
    )->fetchAll(PDO::FETCH_ASSOC);
}

function loadPdcRows(Database $db): array
{
    return $db->query(
        "SELECT p.project_id, g.Proyecto_Proceso, p.pdc_row_id, p.semana, p.fechaInicio,
                p.tipoPaquete, p.paqueteContratacion, p.contratos
         FROM pdc p
         JOIN general_proyectos_procesos g ON g.Id = p.project_id
         WHERE COALESCE(p.titulo, 0) = 0
           AND (COALESCE(p.paqueteContratacion, '') <> '' OR COALESCE(p.contratos, '') <> '')",
    )->fetchAll(PDO::FETCH_ASSOC);
}

function loadReportPdcRows(Database $db): array
{
    return $db->query(
        "SELECT id, project_id, Proyecto, semana, fechaInicio, tipoPaquete, paqueteContratacion, contratos
         FROM general_informe_pdc
         WHERE COALESCE(paqueteContratacion, '') <> '' OR COALESCE(contratos, '') <> ''",
    )->fetchAll(PDO::FETCH_ASSOC);
}

function loadCurrentCatalog(Database $db): array
{
    $families = $db->query(
        "SELECT codigo, nombre, categoria, orden, siempre_revision
         FROM general_pdc_familias
         ORDER BY categoria, orden, nombre",
    )->fetchAll(PDO::FETCH_ASSOC);
    $rules = $db->query(
        "SELECT f.codigo, f.nombre, r.patron_regex, r.confianza, r.prioridad, r.descripcion
         FROM general_pdc_activity_rules r
         JOIN general_pdc_familias f ON f.id = r.familia_id
         WHERE r.activa = 1
         ORDER BY r.prioridad DESC, r.confianza DESC, f.codigo",
    )->fetchAll(PDO::FETCH_ASSOC);

    return ['families' => $families, 'rules' => $rules];
}

function extractExcelPlans(string $pdcDir): array
{
    $files = glob($pdcDir . '/*.xlsx') ?: [];
    sort($files);
    $output = [];
    foreach ($files as $file) {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $summary = [
            'file' => basename($file),
            'candidate_sheets' => [],
            'rows_extracted' => 0,
        ];
        $records = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $header = detectPlanHeader($sheet);
            if ($header === null) {
                continue;
            }
            $summary['candidate_sheets'][] = $sheet->getTitle();
            $rows = extractPlanRows($sheet, $header, basename($file));
            $summary['rows_extracted'] += count($rows);
            array_push($records, ...$rows);
        }
        $spreadsheet->disconnectWorksheets();
        $output[] = ['summary' => $summary, 'records' => $records];
    }

    return $output;
}

function cellValue(Worksheet $sheet, int $column, int $row, bool $formatted = true): string
{
    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
    $value = $cell->getValue();
    if (is_string($value) && str_starts_with($value, '=')) {
        $savedValue = $cell->getOldCalculatedValue();
        if ($savedValue !== null && $savedValue !== '') {
            $value = $savedValue;
        }
    }

    return (string) $value;
}

function detectPlanHeader(Worksheet $sheet): ?array
{
    $maxRow = min((int) $sheet->getHighestDataRow(), 15);
    $maxCol = min(columnIndex($sheet->getHighestDataColumn()), 40);
    for ($row = 1; $row <= $maxRow; $row++) {
        $activityCol = null;
        $chapterCol = null;
        for ($col = 1; $col <= $maxCol; $col++) {
            $value = normalize(cellValue($sheet, $col, $row, false));
            if ($value === 'ACTIVIDAD') {
                $activityCol = $col;
            }
            if (in_array($value, ['CAPITULO', 'CAPÍTULO', 'ITEM'], true)) {
                $chapterCol = $col;
            }
        }
        if ($activityCol !== null) {
            return ['row' => $row, 'activity_col' => $activityCol, 'chapter_col' => $chapterCol];
        }
    }

    return null;
}

function extractPlanRows(Worksheet $sheet, array $header, string $file): array
{
    $records = [];
    $project = detectExcelProjectName($sheet) ?: pathinfo($file, PATHINFO_FILENAME);
    $maxRow = (int) $sheet->getHighestDataRow();
    $activityCol = (int) $header['activity_col'];
    $chapterCol = $header['chapter_col'] !== null ? (int) $header['chapter_col'] : null;
    for ($row = ((int) $header['row']) + 1; $row <= $maxRow; $row++) {
        $activity = trimText(cellValue($sheet, $activityCol, $row));
        $chapter = $chapterCol !== null
            ? trimText(cellValue($sheet, $chapterCol, $row))
            : '';
        if (!isUsefulActivity($activity)) {
            continue;
        }
        $records[] = [
            'source' => 'excel_pdc',
            'project_id' => 0,
            'project' => $project,
            'activity' => $activity,
            'chapter' => $chapter,
            'package' => $activity,
            'modality' => inferModality($activity),
            'file' => $file,
            'sheet' => $sheet->getTitle(),
            'source_row' => (string) $row,
        ];
    }

    return $records;
}

function detectExcelProjectName(Worksheet $sheet): string
{
    for ($row = 1; $row <= 5; $row++) {
        for ($col = 1; $col <= 5; $col++) {
            $value = normalize(cellValue($sheet, $col, $row, false));
            if ($value === 'OBRA') {
                for ($next = $col + 1; $next <= min($col + 4, 8); $next++) {
                    $candidate = trimText(cellValue($sheet, $next, $row));
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }
        }
    }

    return '';
}

function addSourceRecord(array &$corpus, array $record): void
{
    $activity = stripChapterFromActivity(trimText((string) ($record['activity'] ?? '')));
    $chapter = trimText((string) ($record['chapter'] ?? ''));
    $record['activity'] = $activity;
    $catalog = $corpus['catalog'] ?? ['families' => [], 'rules' => []];
    $activityFamily = inferFamilyFromCatalog($activity, $catalog) ?: inferFamily($activity);
    $activityFamily = canonicalFamilyName($activityFamily, $catalog);
    $chapterFamily = '';
    if ($activityFamily === '') {
        $chapterFamily = inferFamilyFromCatalog($chapter, $catalog) ?: inferFamily($chapter);
        $chapterFamily = canonicalFamilyName($chapterFamily, $catalog);
    }
    $family = $activityFamily !== '' ? $activityFamily : $chapterFamily;
    if ($family === '') {
        return;
    }
    $rawFamily = $family;
    $family = matrixFamilyName($family);
    $classification = matrixFamilyClassification($family);
    $record['family_source'] = $activityFamily !== '' ? 'actividad' : 'contexto';

    $key = slug($family);
    if (!isset($corpus['families'][$key])) {
        $corpus['families'][$key] = [
            'name' => $family,
            'classification' => $classification,
            'counts' => [],
            'aliases' => [],
            'packages' => [],
            'projects' => [],
            'modalities' => [],
            'examples' => [],
        ];
    }

    $source = (string) ($record['source'] ?? 'desconocido');
    increment($corpus['families'][$key]['counts'], $source);
    pushLimited($corpus['families'][$key]['aliases'], cleanAlias($activity), 12);
    if (normalize($rawFamily) !== normalize($family)) {
        pushLimited($corpus['families'][$key]['aliases'], $rawFamily, 12);
    }
    pushLimited($corpus['families'][$key]['projects'], (string) ($record['project'] ?? ''), 10);
    pushLimited($corpus['families'][$key]['modalities'], (string) ($record['modality'] ?? ''), 8);
    pushLimited($corpus['families'][$key]['examples'], sourceExample($record), 6);
    if (!empty($record['package'])) {
        pushLimited($corpus['families'][$key]['packages'], cleanAlias((string) $record['package']), 12);
        $packageKey = slug($family . '|' . $record['package']);
        if (!isset($corpus['packages'][$packageKey])) {
            $corpus['packages'][$packageKey] = [
                'family' => $family,
                'package' => cleanAlias((string) $record['package']),
                'modalities' => [],
                'sources' => [],
                'projects' => [],
                'count' => 0,
            ];
        }
        $corpus['packages'][$packageKey]['count']++;
        pushLimited($corpus['packages'][$packageKey]['modalities'], (string) ($record['modality'] ?? ''), 6);
        pushLimited($corpus['packages'][$packageKey]['sources'], $source, 4);
        pushLimited($corpus['packages'][$packageKey]['projects'], (string) ($record['project'] ?? ''), 6);
    }

    $confusions = detectConfusions($record, $family);
    foreach ($confusions as $confusion) {
        appendConfusion($corpus['confusions'], $confusion);
    }
    foreach (validationCandidates($record, $family, $confusions) as $candidate) {
        $corpus['matrix_candidates'][] = $candidate;
    }
    addSample($corpus['samples'], $record, $family);
}

function finalizeFamilies(array &$corpus): void
{
    uasort($corpus['families'], static function (array $a, array $b): int {
        return array_sum($b['counts']) <=> array_sum($a['counts']);
    });
    uasort($corpus['packages'], static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
}

function inferFamilyFromCatalog(string $text, array $catalog): string
{
    $normalized = normalize($text);
    if ($normalized === '') {
        return '';
    }
    static $cache = [];
    if (array_key_exists($normalized, $cache)) {
        return $cache[$normalized];
    }
    foreach (catalogRulesForMatching($catalog) as $rule) {
        $pattern = (string) ($rule['patron_regex'] ?? '');
        if ($pattern === '') {
            continue;
        }
        $needles = $rule['quick_needles'] ?? [];
        if (!empty($needles) && !containsAny($normalized, $needles)) {
            continue;
        }
        if (@preg_match($pattern, $normalized) === 1) {
            $cache[$normalized] = (string) ($rule['nombre'] ?? '');
            return $cache[$normalized];
        }
    }
    if (str_contains($normalized, 'SEGURIDAD Y CONTROL')) {
        if (str_contains($normalized, 'DETECCION') || str_contains($normalized, 'INCENDIO')) {
            $cache[$normalized] = catalogFamilyByCode($catalog, 'DETECCION_INCENDIO');
            return $cache[$normalized];
        }

        $cache[$normalized] = catalogFamilyByCode($catalog, 'RED_TELECOMUNICACIONES');
        return $cache[$normalized];
    }
    if (str_contains($normalized, 'EXTINCION') && containsAny($normalized, ['RED', 'REDES', 'TUBERIA', 'ACCESORIO', 'ROCIADOR', 'SPRINKLER', 'VALVULA'])) {
        $cache[$normalized] = catalogFamilyByCode($catalog, 'RED_CONTRAINCENDIO') ?: catalogFamilyByCode($catalog, 'RCI');
        return $cache[$normalized];
    }

    $cache[$normalized] = '';
    return '';
}

function catalogRulesForMatching(array $catalog): array
{
    static $prepared = null;
    if ($prepared !== null) {
        return $prepared;
    }

    $prepared = [];
    foreach (($catalog['rules'] ?? []) as $rule) {
        $rule['quick_needles'] = regexLiteralNeedles((string) ($rule['patron_regex'] ?? ''));
        $prepared[] = $rule;
    }

    return $prepared;
}

function regexLiteralNeedles(string $pattern): array
{
    $body = preg_replace('/^\\/(.*)\\/[a-zA-Z]*$/', '$1', $pattern) ?? $pattern;
    preg_match_all('/[A-ZÁÉÍÓÚÜÑ0-9]{3,}/u', $body, $matches);

    return array_values(array_unique($matches[0] ?? []));
}

function canonicalFamilyName(string $family, array $catalog): string
{
    if ($family === '') {
        return '';
    }
    $existing = catalogFamilyByName($catalog, $family);
    if ($existing !== '') {
        return $existing;
    }
    $aliases = [
        'Red contra incendio (RCI)' => 'RED_CONTRAINCENDIO',
        'Seguridad y control' => 'RED_TELECOMUNICACIONES',
        'Equipos de extinción' => 'EQUIPOS_INCENDIO',
        'Red hidrosanitaria' => 'RED_HIDROSANITARIA',
        'Red eléctrica' => 'RED_ELECTRICA',
        'Excavaciones y movimiento de tierra' => 'EXCAVACIONES',
        'Cimentaciones y cárcamos' => 'CIMENTACION_LOSAS',
        'Carpintería y revestimientos' => 'CARPINTERIA_METALICA',
        'Estructura metálica y acero' => 'ESTRUCTURA_ACERO',
        'Estructura en concreto' => 'ESTRUCTURA_CONCRETO',
        'Preliminares y demoliciones' => 'PRELIMINARES',
        'Cielos rasos' => 'CIELOS_RASOS',
        'Enchapes y recubrimientos' => 'ENCHAPES',
        'Pinturas y acabados' => 'PINTURAS',
        'Mampostería y muros' => 'MAMPOSTERIA',
        'Pisos y morteros' => 'PISOS',
        'Puertas y accesorios' => 'PUERTAS',
        'Vidrios y ventanería' => 'VIDRIERIA',
        'Impermeabilización' => 'IMPERMEABILIZACIONES',
        'Cubiertas' => 'FACHADA',
        'Aire acondicionado y ventilación' => 'AIRE_ACONDICIONADO',
        'Equipos de obra' => 'MALACATE',
        'Aseo y entrega' => 'ASEO',
        'Paisajismo' => 'PAISAJISMO',
        'Topografía y estudios' => 'PMT',
    ];
    if (isset($aliases[$family])) {
        $mapped = catalogFamilyByCode($catalog, $aliases[$family]);
        if ($mapped !== '') {
            return $mapped;
        }
    }

    return $family;
}

function catalogFamilyByCode(array $catalog, string $code): string
{
    foreach (($catalog['families'] ?? []) as $family) {
        if ((string) ($family['codigo'] ?? '') === $code) {
            return (string) ($family['nombre'] ?? '');
        }
    }

    return '';
}

function catalogFamilyByName(array $catalog, string $name): string
{
    $needle = normalize($name);
    foreach (($catalog['families'] ?? []) as $family) {
        if (normalize((string) ($family['nombre'] ?? '')) === $needle) {
            return (string) ($family['nombre'] ?? '');
        }
    }

    return '';
}

function inferFamily(string $text): string
{
    $n = normalize($text);
    if (str_contains($n, 'SEGURIDAD Y CONTROL')) {
        return 'Seguridad y control';
    }
    if (str_contains($n, 'EXTINCION') && containsAny($n, ['RED', 'REDES', 'TUBERIA', 'ACCESORIO', 'ROCIADOR', 'SPRINKLER', 'VALVULA'])) {
        return 'Red contra incendio (RCI)';
    }

    $rules = [
        'Red contra incendio (RCI)' => ['RED CONTRA INCENDIO', 'CONTRA INCENDIO', 'TUBERIA RCI', 'ROCIADOR', 'SPRINKLER', 'SIAMESA', 'VALVULA INCENDIO'],
        'Seguridad y control' => ['SEGURIDAD Y CONTROL', 'CCTV', 'CAMARA', 'DOMO', 'PTZ', 'CONTROL ACCESO', 'TORNIQUETE', 'VERIPASS', 'DETECCION', 'ALARMA', 'SUICHE'],
        'Equipos de extinción' => ['EXTINTOR', 'EXTINTORES', 'GABINETE INCENDIO', 'MANGUERA INCENDIO', 'HIDRANTE', 'EQUIPOS DE EXTINCION'],
        'Red hidrosanitaria' => ['HIDROSANITARIA', 'HIDRAULICA', 'SANITARIA', 'DESAGUE', 'AGUA POTABLE', 'ALCANTARILLADO', 'TUBERIA SANITARIA'],
        'Red eléctrica' => ['RED ELECTRICA', 'ELECTRICA', 'TABLERO', 'CABLEADO', 'CANALIZACION', 'SUBESTACION'],
        'Excavaciones y movimiento de tierra' => ['EXCAVACION', 'EXCAVACIONES', 'MOVIMIENTO DE TIERRA', 'RELLENO', 'LLENO', 'DESCAPOTE', 'NIVELACION TERRENO'],
        'Cimentaciones y cárcamos' => ['CIMENTACION', 'CIMENTACIONES', 'CARCAMO', 'ZAPATA', 'FUNDACION', 'VIGA DE AMARRE', 'LOSA DE CIMENTACION'],
        'Carpintería y revestimientos' => ['CARPINTERIA', 'FORMICA', 'MUEBLE', 'CLOSET', 'PUERTA MADERA', 'REVESTIMIENTO'],
        'Estructura metálica y acero' => ['ESTRUCTURA METALICA', 'ACERO', 'STEEL DECK', 'METALDECK', 'PERFILES METALICOS', 'CUBIERTA METALICA'],
        'Estructura en concreto' => ['ESTRUCTURA CONCRETO', 'CONCRETO ESTRUCTURAL', 'COLUMNA', 'VIGA CONCRETO', 'LOSA CONCRETO', 'VACIADO CONCRETO'],
        'Preliminares y demoliciones' => ['PRELIMINAR', 'DEMOLICION', 'DESMONTE', 'RETIRO', 'ACTA VECINDAD', 'CAMPAMENTO'],
        'Cielos rasos' => ['CIELO RASO', 'CIELOS'],
        'Enchapes y recubrimientos' => ['ENCHAPE', 'RECUBRIMIENTO', 'CERAMICA'],
        'Pinturas y acabados' => ['PINTURA', 'ESTUCO'],
        'Mampostería y muros' => ['MAMPOSTERIA', 'MURO', 'MUROS'],
        'Pisos y morteros' => ['PISO', 'MORTERO', 'PORCELANATO'],
        'Puertas y accesorios' => ['PUERTA', 'CERRADURA', 'BISAGRA'],
        'Vidrios y ventanería' => ['VIDRIO', 'VENTANERIA', 'FACHADA'],
        'Impermeabilización' => ['IMPERMEABILIZ'],
        'Cubiertas' => ['CUBIERTA', 'TEJA', 'CANAL'],
        'Aire acondicionado y ventilación' => ['AIRE ACONDICIONADO', 'HVAC', 'VENTILACION', 'DUCTO'],
        'Equipos de obra' => ['ALQUILER', 'TORRE GRUA', 'MALACATE', 'ANDAMIO', 'EXCAVADORA', 'MINICARGADOR'],
        'Aseo y entrega' => ['ASEO', 'LIMPIEZA', 'ENTREGA FINAL'],
        'Paisajismo' => ['PAISAJISMO', 'JARDIN'],
        'Topografía y estudios' => ['TOPOGRAFIA', 'ESTUDIO', 'ASESORIA', 'DISENO', 'DISEÑO'],
    ];
    foreach ($rules as $family => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($n, $needle)) {
                return $family;
            }
        }
    }

    return '';
}

function containsAny(string $text, array $needles): bool
{
    foreach ($needles as $needle) {
        if (str_contains($text, $needle)) {
            return true;
        }
    }

    return false;
}

function isLocationLikeActivity(string $normalizedActivity): bool
{
    return preg_match('/^(EJE|EJES|ZONA|PISO|TRAMO|FRENTE|AREA|NIVEL|FRANJA|GRUPO|SECTOR|LOCAL)\b/u', $normalizedActivity) === 1;
}

function detectConfusions(array $record, string $family): array
{
    $activity = (string) ($record['activity'] ?? '');
    $chapter = (string) ($record['chapter'] ?? '');
    $a = normalize($activity);
    $c = normalize($chapter);
    $combined = trim($a . ' ' . $c);
    $items = [];

    if (($record['family_source'] ?? '') === 'contexto') {
        $items[] = confusion('Familia inferida solo por contexto/capítulo', $record, $family);
    }
    if (isLocationLikeActivity($a)) {
        $items[] = confusion('Ubicación usada como actividad', $record, $family);
    }
    if (str_contains($a, 'EXCAV') && str_contains($c, 'ESTRUCTURA')) {
        $items[] = confusion('Excavación bajo capítulo de estructura', $record, $family);
    }
    if (containsAny($combined, ['CARCAMO', 'CIMENT']) && str_contains($combined, 'EXCAV')) {
        $items[] = confusion('Revisar separación excavación vs cimentación/cárcamo', $record, $family);
    }
    if ((str_contains($c, 'CARPINTER') || str_contains($c, 'FORMICA') || str_contains($c, 'REVESTIMIENTO')) && str_contains($a, 'EJE')) {
        $items[] = confusion('Carpintería/revestimiento expresado como eje', $record, $family);
    }
    if (str_contains($c, 'SEGURIDAD Y CONTROL') && (str_contains($a, 'EXTINCION') || str_contains($a, 'GABINETE') || str_contains($a, 'EQUIPO'))) {
        $items[] = confusion('Seguridad/control confundible con incendio', $record, $family);
    }
    if (str_contains($c, 'EXTINCION') && containsAny($a, ['TUBERIA', 'RED', 'ACCESORIO', 'VALVULA', 'ROCIADOR'])) {
        $items[] = confusion('Extinción con redes/tubería debe revisarse como RCI', $record, $family);
    }
    if (str_starts_with($family, 'Red ') && containsAny($a, ['EQUIPO', 'GABINETE', 'APARATO', 'ACCESORIO'])) {
        $items[] = confusion('Mezcla red con equipo/accesorio', $record, $family);
    }

    return $items;
}

function confusion(string $pattern, array $record, string $family): array
{
    return [
        'pattern' => $pattern,
        'family' => $family,
        'source' => (string) ($record['source'] ?? ''),
        'project' => (string) ($record['project'] ?? ''),
        'activity' => cleanAlias((string) ($record['activity'] ?? '')),
        'chapter' => cleanAlias((string) ($record['chapter'] ?? '')),
    ];
}

function appendConfusion(array &$items, array $value): void
{
    static $seen = [];
    $signature = implode('|', [
        $value['pattern'] ?? '',
        $value['project'] ?? '',
        $value['activity'] ?? '',
        $value['chapter'] ?? '',
        $value['family'] ?? '',
    ]);
    if (isset($seen[$signature])) {
        return;
    }
    $seen[$signature] = true;
    $items[] = $value;
}

function validationCandidates(array $record, string $family, array $confusions): array
{
    $patterns = empty($confusions)
        ? ['Familia frecuente sin patrón crítico']
        : array_values(array_unique(array_map(static fn(array $item): string => (string) $item['pattern'], $confusions)));
    $candidates = [];
    foreach ($patterns as $pattern) {
        $proposal = proposedReviewFields($record, $family, $pattern);
        $activity = cleanAlias((string) ($record['activity'] ?? ''));
        $chapter = cleanAlias((string) ($record['chapter'] ?? ''));
        $score = priorityScore((string) ($record['project'] ?? ''), $pattern, $family);
        $matrixFamily = matrixFamilyName($family);
        $candidates[] = [
            'priority_score' => $score,
            'prioridad' => priorityLabel($score),
            'proyecto' => (string) ($record['project'] ?? ''),
            'project_bucket' => projectBucket((string) ($record['project'] ?? '')),
            'fuente' => sourceLabel($record),
            'actividad_origen' => $activity,
            'contexto' => $chapter,
            'paquete_pdc' => cleanAlias((string) ($record['package'] ?? '')),
            'patron_detectado' => $pattern,
            'familia_sugerida' => $matrixFamily,
            'decision_humana' => $proposal['decision_humana'],
            'familia_correcta' => matrixFamilyName($proposal['familia_correcta']),
            'clasificacion_familia' => matrixFamilyClassification($family),
            'nombre_actividad_correcto' => $proposal['nombre_actividad_correcto'],
            'motivo' => $proposal['motivo'],
            'accion_recomendada' => $proposal['accion_recomendada'],
            'notas' => '',
        ];
    }

    return $candidates;
}

function proposedReviewFields(array $record, string $family, string $pattern): array
{
    $activity = cleanAlias((string) ($record['activity'] ?? ''));
    $chapter = cleanAlias((string) ($record['chapter'] ?? ''));
    $suggestedName = suggestedActivityName($activity, $chapter);
    $base = [
        'decision_humana' => 'Correcto',
        'familia_correcta' => $family,
        'nombre_actividad_correcto' => $activity,
        'motivo' => 'La familia sugerida coincide con el nombre de la actividad.',
        'accion_recomendada' => 'Mantener',
    ];

    return match ($pattern) {
        'Ubicación usada como actividad' => [
            'decision_humana' => 'Nombre incorrecto',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $suggestedName,
            'motivo' => 'El nombre de origen parece ubicación, zona, piso, eje o frente; debe revisarse el alcance operativo real.',
            'accion_recomendada' => 'Corregir nombre',
        ],
        'Carpintería/revestimiento expresado como eje' => [
            'decision_humana' => 'Nombre incorrecto',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $suggestedName,
            'motivo' => 'El eje es ubicación; el alcance operativo real está en el contexto de carpintería o revestimiento.',
            'accion_recomendada' => 'Corregir nombre',
        ],
        'Extinción con redes/tubería debe revisarse como RCI' => [
            'decision_humana' => 'Correcto',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $activity,
            'motivo' => 'Tubería, accesorios o redes de extinción corresponden a red contra incendio, no a red hidrosanitaria.',
            'accion_recomendada' => 'Mantener',
        ],
        'Seguridad/control confundible con incendio' => [
            'decision_humana' => 'Correcto',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $activity,
            'motivo' => 'El contexto indica seguridad, control o detección; no debe confundirse con extinción contra incendio.',
            'accion_recomendada' => 'Mantener',
        ],
        'Familia inferida solo por contexto/capítulo' => [
            'decision_humana' => 'Dudoso',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $suggestedName,
            'motivo' => 'La familia no sale claramente del nombre de la actividad sino del contexto; requiere confirmación humana.',
            'accion_recomendada' => 'Revisar manual',
        ],
        'Mezcla red con equipo/accesorio' => [
            'decision_humana' => 'Dudoso',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $activity,
            'motivo' => 'La fila mezcla una red con equipos, aparatos o accesorios; puede requerir separación operativa.',
            'accion_recomendada' => 'Revisar manual',
        ],
        'Revisar separación excavación vs cimentación/cárcamo' => [
            'decision_humana' => 'Dudoso',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $activity,
            'motivo' => 'Excavación y cimentación/cárcamo pueden ser actividades operativas distintas.',
            'accion_recomendada' => 'Revisar manual',
        ],
        'Excavación bajo capítulo de estructura' => [
            'decision_humana' => 'Correcto',
            'familia_correcta' => $family,
            'nombre_actividad_correcto' => $activity,
            'motivo' => 'Aunque el capítulo mencione estructura, el nombre de la actividad indica excavación.',
            'accion_recomendada' => 'Mantener',
        ],
        default => $base,
    };
}

function suggestedActivityName(string $activity, string $chapter): string
{
    if (!isLocationLikeActivity(normalize($activity))) {
        return $activity;
    }
    foreach (explode(',', $chapter) as $part) {
        $candidate = trimText($part);
        if ($candidate !== '' && !isLocationLikeActivity(normalize($candidate))) {
            return cleanAlias($candidate);
        }
    }

    return $activity;
}

function sourceLabel(array $record): string
{
    $source = (string) ($record['source'] ?? '');
    $parts = [$source !== '' ? $source : 'fuente'];
    if (!empty($record['source_id'])) {
        $parts[] = '#' . $record['source_id'];
    }
    if (!empty($record['week'])) {
        $parts[] = 'semana ' . $record['week'];
    }
    if (!empty($record['start_date'])) {
        $parts[] = (string) $record['start_date'];
    }
    if (!empty($record['file'])) {
        $parts[] = (string) $record['file'];
    }
    if (!empty($record['sheet'])) {
        $parts[] = 'hoja ' . $record['sheet'];
    }
    if (!empty($record['source_row'])) {
        $parts[] = 'fila ' . $record['source_row'];
    }

    return implode(' | ', $parts);
}

function priorityScore(string $project, string $pattern, string $family): int
{
    $projectScore = match (projectBucket($project)) {
        'jmc' => 70,
        'da_porto' => 65,
        'metrolinea' => 35,
        'milan' => 30,
        default => 10,
    };
    $patternScore = match ($pattern) {
        'Familia inferida solo por contexto/capítulo' => 55,
        'Ubicación usada como actividad' => 50,
        'Carpintería/revestimiento expresado como eje' => 48,
        'Extinción con redes/tubería debe revisarse como RCI' => 45,
        'Seguridad/control confundible con incendio' => 42,
        'Revisar separación excavación vs cimentación/cárcamo' => 40,
        'Mezcla red con equipo/accesorio' => 38,
        'Excavación bajo capítulo de estructura' => 34,
        default => 12,
    };
    $familyScore = in_array($family, ['Red contra incendio (RCI)', 'Seguridad y control', 'Excavaciones y movimiento de tierra', 'Cimentaciones y cárcamos', 'Carpintería y revestimientos'], true) ? 12 : 0;

    return $projectScore + $patternScore + $familyScore;
}

function priorityLabel(int $score): string
{
    if ($score >= 110) {
        return 'Alta';
    }
    if ($score >= 70) {
        return 'Media';
    }

    return 'Baja';
}

function projectBucket(string $project): string
{
    $normalized = normalize($project);
    if (str_contains($normalized, 'JMC') || str_contains($normalized, 'AEROPUERTO')) {
        return 'jmc';
    }
    if (str_contains($normalized, 'DA PORTO') || str_contains($normalized, 'DAPORTO')) {
        return 'da_porto';
    }
    if (str_contains($normalized, 'MILAN')) {
        return 'milan';
    }
    if (str_contains($normalized, 'METROLINEA')) {
        return 'metrolinea';
    }

    return 'otro';
}

function addSample(array &$samples, array $record, string $family): void
{
    $project = normalize((string) ($record['project'] ?? ''));
    $bucket = null;
    if (str_contains($project, 'JMC') || str_contains($project, 'AEROPUERTO')) {
        $bucket = 'jmc';
    } elseif (str_contains($project, 'DA PORTO')) {
        $bucket = 'da_porto';
    } elseif (str_contains($project, 'MILAN')) {
        $bucket = 'milan';
    } elseif (str_contains($project, 'METROLINEA')) {
        $bucket = 'metrolinea';
    }
    if ($bucket !== null) {
        pushLimited($samples[$bucket], $family . ': ' . sourceExample($record), 12);
    }
}

function buildHumanValidationMatrix(array $corpus, int $targetSize): array
{
    $rawPool = uniqueMatrixCandidates($corpus['matrix_candidates']);
    $classificationCounts = matrixClassificationCounts($rawPool);
    $pool = cleanMatrixCandidates($rawPool);
    sortMatrixCandidates($pool);
    $selected = [];
    $seen = [];

    addMinimumBy($selected, $seen, $pool, 'patron_detectado', 12, $targetSize);
    foreach (['jmc' => 90, 'da_porto' => 55, 'metrolinea' => 55, 'milan' => 30] as $bucket => $quota) {
        addProjectQuota($selected, $seen, $pool, $bucket, $quota, $targetSize);
    }
    addMinimumBy($selected, $seen, $pool, 'familia_sugerida', 3, $targetSize);
    fillMatrixSelection($selected, $seen, $pool, $targetSize);

    $rows = array_slice($selected, 0, $targetSize);
    sortMatrixCandidates($rows);
    foreach ($rows as $index => &$row) {
        $row = matrixOutputRow($row, $index + 1);
    }
    unset($row);

    $lists = matrixLists($corpus);
    $summary = buildMatrixSummary($rows);
    $summary['classification_counts'] = $classificationCounts;

    return [
        'generated_at' => date('c'),
        'rows' => $rows,
        'lists' => $lists,
        'summary' => $summary,
    ];
}

function uniqueMatrixCandidates(array $candidates): array
{
    $unique = [];
    foreach ($candidates as $candidate) {
        $signature = matrixCandidateSignature($candidate);
        if (!isset($unique[$signature]) || (int) $candidate['priority_score'] > (int) $unique[$signature]['priority_score']) {
            $unique[$signature] = $candidate;
        }
    }

    return array_values($unique);
}

function cleanMatrixCandidates(array $candidates): array
{
    $clean = [];
    foreach ($candidates as $candidate) {
        $candidate['familia_sugerida'] = matrixFamilyName((string) ($candidate['familia_sugerida'] ?? ''));
        $candidate['familia_correcta'] = matrixFamilyName((string) ($candidate['familia_correcta'] ?? ''));
        $candidate['clasificacion_familia'] = matrixFamilyClassification((string) ($candidate['familia_correcta'] ?? $candidate['familia_sugerida'] ?? ''));
        if (!matrixFamilyAllowed((string) $candidate['familia_sugerida'])) {
            continue;
        }
        if (!matrixFamilyAllowed((string) $candidate['familia_correcta'])) {
            continue;
        }
        if (!in_array((string) ($candidate['decision_humana'] ?? ''), matrixDecisionValues(), true)) {
            continue;
        }
        $clean[] = $candidate;
    }

    return $clean;
}

function matrixClassificationCounts(array $candidates): array
{
    $counts = [];
    foreach ($candidates as $candidate) {
        $classification = (string) ($candidate['clasificacion_familia'] ?? matrixFamilyClassification((string) ($candidate['familia_correcta'] ?? $candidate['familia_sugerida'] ?? '')));
        $counts[$classification !== '' ? $classification : 'dudoso'] = ($counts[$classification !== '' ? $classification : 'dudoso'] ?? 0) + 1;
    }
    arsort($counts);

    return $counts;
}

function sortMatrixCandidates(array &$candidates): void
{
    usort($candidates, static function (array $a, array $b): int {
        return ((int) $b['priority_score'] <=> (int) $a['priority_score'])
            ?: strcmp((string) $a['proyecto'], (string) $b['proyecto'])
            ?: strcmp((string) $a['patron_detectado'], (string) $b['patron_detectado'])
            ?: strcmp((string) $a['actividad_origen'], (string) $b['actividad_origen']);
    });
}

function addMinimumBy(array &$selected, array &$seen, array $pool, string $field, int $minimum, int $targetSize): void
{
    $values = array_values(array_unique(array_map(static fn(array $row): string => (string) $row[$field], $pool)));
    sort($values);
    foreach ($values as $value) {
        $current = countByValue($selected, $field, $value);
        foreach ($pool as $candidate) {
            if ($current >= $minimum || count($selected) >= $targetSize) {
                break;
            }
            if ((string) $candidate[$field] !== $value) {
                continue;
            }
            if (addMatrixCandidate($selected, $seen, $candidate)) {
                $current++;
            }
        }
    }
}

function addProjectQuota(array &$selected, array &$seen, array $pool, string $bucket, int $quota, int $targetSize): void
{
    $current = countByValue($selected, 'project_bucket', $bucket);
    foreach ($pool as $candidate) {
        if ($current >= $quota || count($selected) >= $targetSize) {
            break;
        }
        if (($candidate['project_bucket'] ?? '') !== $bucket) {
            continue;
        }
        if (addMatrixCandidate($selected, $seen, $candidate)) {
            $current++;
        }
    }
}

function fillMatrixSelection(array &$selected, array &$seen, array $pool, int $targetSize): void
{
    foreach ($pool as $candidate) {
        if (count($selected) >= $targetSize) {
            break;
        }
        addMatrixCandidate($selected, $seen, $candidate);
    }
}

function addMatrixCandidate(array &$selected, array &$seen, array $candidate): bool
{
    $signature = matrixCandidateSignature($candidate);
    if (isset($seen[$signature])) {
        return false;
    }
    $seen[$signature] = true;
    $selected[] = $candidate;

    return true;
}

function countByValue(array $rows, string $field, string $value): int
{
    $count = 0;
    foreach ($rows as $row) {
        if ((string) ($row[$field] ?? '') === $value) {
            $count++;
        }
    }

    return $count;
}

function matrixCandidateSignature(array $candidate): string
{
    return implode('|', [
        normalize((string) ($candidate['proyecto'] ?? '')),
        normalize((string) ($candidate['actividad_origen'] ?? '')),
        normalize((string) ($candidate['contexto'] ?? '')),
        normalize((string) ($candidate['patron_detectado'] ?? '')),
        normalize((string) ($candidate['familia_sugerida'] ?? '')),
    ]);
}

function matrixOutputRow(array $candidate, int $index): array
{
    return [
        'id_caso' => 'VAL-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
        'prioridad' => (string) $candidate['prioridad'],
        'proyecto' => (string) $candidate['proyecto'],
        'fuente' => (string) $candidate['fuente'],
        'actividad_origen' => (string) $candidate['actividad_origen'],
        'contexto' => (string) $candidate['contexto'],
        'paquete_pdc' => (string) $candidate['paquete_pdc'],
        'patron_detectado' => (string) $candidate['patron_detectado'],
        'familia_sugerida' => (string) $candidate['familia_sugerida'],
        'decision_humana' => (string) $candidate['decision_humana'],
        'familia_correcta' => (string) $candidate['familia_correcta'],
        'nombre_actividad_correcto' => (string) $candidate['nombre_actividad_correcto'],
        'motivo' => (string) $candidate['motivo'],
        'accion_recomendada' => (string) $candidate['accion_recomendada'],
        'clasificacion_familia' => (string) ($candidate['clasificacion_familia'] ?? matrixFamilyClassification((string) $candidate['familia_correcta'])),
        'notas' => '',
    ];
}

function matrixHeaders(): array
{
    return [
        'id_caso',
        'prioridad',
        'proyecto',
        'fuente',
        'actividad_origen',
        'contexto',
        'paquete_pdc',
        'patron_detectado',
        'familia_sugerida',
        'decision_humana',
        'familia_correcta',
        'nombre_actividad_correcto',
        'motivo',
        'accion_recomendada',
        'clasificacion_familia',
        'notas',
    ];
}

function matrixLists(array $corpus): array
{
    $catalogFamilies = array_map(
        static fn(array $family): string => (string) ($family['nombre'] ?? ''),
        $corpus['catalog']['families'] ?? [],
    );
    $detectedFamilies = array_map(static fn(array $family): string => (string) $family['name'], $corpus['families']);
    $families = [];
    foreach (array_merge($catalogFamilies, $detectedFamilies) as $family) {
        $family = matrixFamilyName((string) $family);
        if ($family !== '' && matrixFamilyAllowed($family)) {
            $families[] = $family;
        }
    }
    sort($families);
    $families[] = 'Nueva familia';

    return [
        'decision_humana' => matrixDecisionValues(),
        'familia_correcta' => array_values(array_unique($families)),
        'accion_recomendada' => ['Mantener', 'Corregir familia', 'Corregir nombre', 'Mover a contratos', 'Excluir', 'Crear nueva familia', 'Revisar manual'],
    ];
}

function matrixDecisionValues(): array
{
    return ['Correcto', 'Familia incorrecta', 'Nombre incorrecto', 'Va en contratos', 'Nueva familia', 'Dudoso'];
}

function matrixPolicy(): OperationalFamilyPolicy
{
    static $policy = null;
    if ($policy === null) {
        $policy = new OperationalFamilyPolicy();
    }

    return $policy;
}

function matrixFamilyName(string $family): string
{
    return matrixPolicy()->normalizeOperationalFamily(trimText($family));
}

function matrixFamilyClassification(string $family): string
{
    return matrixPolicy()->familyClassification(trimText($family));
}

function matrixFamilyAllowed(string $family): bool
{
    return matrixPolicy()->isOperationalFamilyAllowedForListado($family);
}

function writeHumanValidationMatrix(array $matrix, string $xlsxPath, string $jsonPath, string $mdPath): void
{
    $spreadsheet = new Spreadsheet();
    $validationSheet = $spreadsheet->getActiveSheet();
    $validationSheet->setTitle('Validacion');
    $listsSheet = $spreadsheet->createSheet();
    $listsSheet->setTitle('Listas');
    $summarySheet = $spreadsheet->createSheet();
    $summarySheet->setTitle('Resumen');

    writeValidationSheet($validationSheet, $matrix['rows'], $matrix['lists']);
    writeListsSheet($listsSheet, $matrix['lists']);
    writeSummarySheet($summarySheet, $matrix['summary']);
    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);
    $writer->save($xlsxPath);
    $spreadsheet->disconnectWorksheets();

    file_put_contents($jsonPath, json_encode($matrix['summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    file_put_contents($mdPath, renderMatrixSummaryMarkdown($matrix['summary']));
}

function writeValidationSheet(Worksheet $sheet, array $rows, array $lists): void
{
    $headers = matrixHeaders();
    foreach ($headers as $index => $header) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
    }
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 2;
        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $excelRow, $row[$header] ?? '');
        }
    }

    $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
    $lastRow = count($rows) + 1;
    $sheet->freezePane('A2');
    $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
    $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F6B3A');
    $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
    $widths = [14, 12, 32, 34, 42, 48, 32, 36, 30, 24, 30, 42, 52, 24, 36];
    foreach ($widths as $index => $width) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
    }

    applyListValidation($sheet, 'J', 2, $lastRow, "'Listas'!\$A\$2:\$A\$" . (count($lists['decision_humana']) + 1));
    applyListValidation($sheet, 'K', 2, $lastRow, "'Listas'!\$B\$2:\$B\$" . (count($lists['familia_correcta']) + 1));
    applyListValidation($sheet, 'N', 2, $lastRow, "'Listas'!\$C\$2:\$C\$" . (count($lists['accion_recomendada']) + 1));
}

function writeListsSheet(Worksheet $sheet, array $lists): void
{
    $columns = [
        'A' => ['decision_humana', $lists['decision_humana']],
        'B' => ['familia_correcta', $lists['familia_correcta']],
        'C' => ['accion_recomendada', $lists['accion_recomendada']],
    ];
    foreach ($columns as $column => [$title, $values]) {
        $sheet->setCellValue($column . '1', $title);
        foreach ($values as $index => $value) {
            $sheet->setCellValue($column . ($index + 2), $value);
        }
        $sheet->getColumnDimension($column)->setWidth(34);
    }
    $sheet->getStyle('A1:C1')->getFont()->setBold(true);
}

function writeSummarySheet(Worksheet $sheet, array $summary): void
{
    $sheet->setCellValue('A1', 'Resumen de matriz');
    $sheet->setCellValue('A2', 'Total de casos');
    $sheet->setCellValue('B2', $summary['total_cases']);
    $row = 4;
    foreach ([
        'project_counts' => 'Casos por proyecto',
        'pattern_counts' => 'Casos por patrón',
        'family_counts' => 'Casos por familia sugerida',
        'decision_counts' => 'Casos por decisión propuesta',
        'classification_counts' => 'Casos por clasificación de familia',
    ] as $key => $title) {
        if (empty($summary[$key]) || !is_array($summary[$key])) {
            continue;
        }
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($summary[$key] as $label => $count) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $count);
            $row++;
        }
        $row++;
    }
    $sheet->getColumnDimension('A')->setWidth(56);
    $sheet->getColumnDimension('B')->setWidth(16);
}

function applyListValidation(Worksheet $sheet, string $column, int $startRow, int $endRow, string $formula): void
{
    for ($row = $startRow; $row <= $endRow; $row++) {
        $validation = $sheet->getCell($column . $row)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowDropDown(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Valor no permitido');
        $validation->setError('Selecciona una opción de la lista.');
        $validation->setFormula1($formula);
    }
}

function buildMatrixSummary(array $rows): array
{
    return [
        'generated_at' => date('c'),
        'total_cases' => count($rows),
        'project_counts' => countRowsBy($rows, 'proyecto'),
        'pattern_counts' => countRowsBy($rows, 'patron_detectado'),
        'family_counts' => countRowsBy($rows, 'familia_sugerida'),
        'decision_counts' => countRowsBy($rows, 'decision_humana'),
        'top_priority_cases' => array_slice($rows, 0, 15),
    ];
}

function countRowsBy(array $rows, string $field): array
{
    $counts = [];
    foreach ($rows as $row) {
        $key = (string) ($row[$field] ?? '');
        $counts[$key !== '' ? $key : 'Sin dato'] = ($counts[$key !== '' ? $key : 'Sin dato'] ?? 0) + 1;
    }
    arsort($counts);

    return $counts;
}

function renderMatrixSummaryMarkdown(array $summary): string
{
    $lines = [];
    $lines[] = '# Resumen accionable de matriz de validación humana';
    $lines[] = '';
    $lines[] = 'Generado: `' . $summary['generated_at'] . '`.';
    $lines[] = '';
    $lines[] = '- Total de casos: ' . $summary['total_cases'] . '.';
    foreach ([
        'project_counts' => 'Casos por proyecto',
        'pattern_counts' => 'Casos por patrón',
        'family_counts' => 'Casos por familia sugerida',
        'decision_counts' => 'Casos por decisión propuesta',
        'classification_counts' => 'Casos por clasificación de familia',
    ] as $key => $title) {
        if (empty($summary[$key]) || !is_array($summary[$key])) {
            continue;
        }
        $lines[] = '';
        $lines[] = '## ' . $title;
        $lines[] = '';
        foreach (array_slice($summary[$key], 0, 20, true) as $label => $count) {
            $lines[] = '- ' . $label . ': ' . $count;
        }
    }
    $lines[] = '';
    $lines[] = '## Casos de mayor prioridad';
    $lines[] = '';
    foreach ($summary['top_priority_cases'] as $case) {
        $lines[] = '- `' . $case['id_caso'] . '` ' . $case['proyecto'] . ' · ' . $case['patron_detectado'] . ' · ' . $case['actividad_origen'];
    }
    $lines[] = '';

    return implode("\n", $lines);
}

function verifyMatrix(string $xlsxPath, string $jsonPath, string $mdPath): int
{
    $errors = [];
    if (!is_file($xlsxPath)) {
        $errors[] = "No existe el XLSX esperado: {$xlsxPath}";
    }
    if (!is_file($jsonPath)) {
        $errors[] = "No existe el resumen JSON esperado: {$jsonPath}";
    }
    if (!is_file($mdPath)) {
        $errors[] = "No existe el resumen Markdown esperado: {$mdPath}";
    }
    if (!empty($errors)) {
        foreach ($errors as $error) {
            fwrite(STDERR, "[ERROR] {$error}\n");
        }

        return 1;
    }

    $spreadsheet = IOFactory::load($xlsxPath);
    foreach (['Validacion', 'Listas', 'Resumen'] as $sheetName) {
        if ($spreadsheet->getSheetByName($sheetName) === null) {
            $errors[] = "No existe la hoja {$sheetName}.";
        }
    }
    $sheet = $spreadsheet->getSheetByName('Validacion');
    if ($sheet !== null) {
        $headers = [];
        foreach (range(1, count(matrixHeaders())) as $column) {
            $headers[] = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . '1')->getValue();
        }
        if ($headers !== matrixHeaders()) {
            $errors[] = 'Los encabezados de Validacion no coinciden con lo esperado.';
        }
        $caseRows = max(0, (int) $sheet->getHighestDataRow() - 1);
        if ($caseRows !== MATRIX_SAMPLE_SIZE) {
            $errors[] = 'La hoja Validacion debe tener ' . MATRIX_SAMPLE_SIZE . " casos y tiene {$caseRows}.";
        }
        foreach (['J2' => 'decision_humana', 'K2' => 'familia_correcta', 'N2' => 'accion_recomendada'] as $cell => $name) {
            $validation = $sheet->getCell($cell)->getDataValidation();
            if ($validation->getType() !== DataValidation::TYPE_LIST || $validation->getFormula1() === '') {
                $errors[] = "La columna {$name} no tiene lista desplegable válida.";
            }
        }
        foreach (['J' => 'decision_humana', 'K' => 'familia_correcta', 'L' => 'nombre_actividad_correcto', 'M' => 'motivo', 'N' => 'accion_recomendada'] as $column => $name) {
            for ($row = 2; $row <= $caseRows + 1; $row++) {
                if (trim((string) $sheet->getCell($column . $row)->getValue()) === '') {
                    $errors[] = "La columna {$name} tiene una celda sin prellenar en la fila {$row}.";
                    break;
                }
            }
        }
        foreach (['I' => 'familia_sugerida', 'J' => 'decision_humana', 'K' => 'familia_correcta'] as $column => $name) {
            for ($row = 2; $row <= $caseRows + 1; $row++) {
                $value = trim((string) $sheet->getCell($column . $row)->getValue());
                if ($name === 'decision_humana' && $value === 'No es actividad') {
                    $errors[] = "La matriz todavía contiene No es actividad en la fila {$row}.";
                    break;
                }
                if (($name === 'familia_sugerida' || $name === 'familia_correcta') && !matrixFamilyAllowed($value)) {
                    $errors[] = "La columna {$name} contiene una familia excluida en la fila {$row}: {$value}.";
                    break;
                }
                if (($name === 'familia_sugerida' || $name === 'familia_correcta') && matrixFamilyName($value) !== $value) {
                    $errors[] = "La columna {$name} contiene una familia RCI sin unificar en la fila {$row}: {$value}.";
                    break;
                }
            }
        }
    }
    $listsSheet = $spreadsheet->getSheetByName('Listas');
    if ($listsSheet !== null) {
        $decisionOptions = [];
        for ($row = 2; $row <= $listsSheet->getHighestDataRow('A'); $row++) {
            $value = trim((string) $listsSheet->getCell('A' . $row)->getValue());
            if ($value !== '') {
                $decisionOptions[] = $value;
            }
        }
        if (in_array('No es actividad', $decisionOptions, true)) {
            $errors[] = 'La lista decision_humana todavía incluye No es actividad.';
        }

        $familyOptions = [];
        for ($row = 2; $row <= $listsSheet->getHighestDataRow('B'); $row++) {
            $value = trim((string) $listsSheet->getCell('B' . $row)->getValue());
            if ($value !== '') {
                $familyOptions[] = $value;
            }
        }
        foreach (MATRIX_EXCLUDED_FAMILIES as $excluded) {
            if (in_array($excluded, $familyOptions, true)) {
                $errors[] = "La lista familia_correcta todavía incluye {$excluded}.";
            }
        }
        foreach (array_keys(MATRIX_FAMILY_ALIASES) as $alias) {
            if (in_array($alias, $familyOptions, true)) {
                $errors[] = "La lista familia_correcta todavía incluye {$alias} como opción separada.";
            }
        }
        if (!in_array(MATRIX_RCI_FAMILY, $familyOptions, true)) {
            $errors[] = 'La lista familia_correcta no incluye Red de Extinción.';
        }
    }
    $spreadsheet->disconnectWorksheets();

    try {
        $summary = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        if (($summary['total_cases'] ?? null) !== MATRIX_SAMPLE_SIZE) {
            $errors[] = 'El resumen JSON no reporta ' . MATRIX_SAMPLE_SIZE . ' casos.';
        }
        foreach (['project_counts', 'pattern_counts', 'family_counts', 'decision_counts', 'classification_counts'] as $key) {
            if (empty($summary[$key]) || !is_array($summary[$key])) {
                $errors[] = "El resumen JSON no tiene {$key}.";
            }
        }
    } catch (JsonException $exception) {
        $errors[] = 'El resumen JSON no es válido: ' . $exception->getMessage();
    }

    foreach ($errors as $error) {
        fwrite(STDERR, "[ERROR] {$error}\n");
    }
    if (!empty($errors)) {
        return 1;
    }

    echo "Matriz XLSX válida: {$xlsxPath}\n";
    echo "Casos revisables: " . MATRIX_SAMPLE_SIZE . "\n";
    echo "Listas desplegables: decision_humana, familia_correcta, accion_recomendada\n";
    echo "Resumen JSON válido: {$jsonPath}\n";
    echo "Resumen Markdown generado: {$mdPath}\n";

    return 0;
}

function renderMarkdown(array $corpus): string
{
    $lines = [];
    $lines[] = '# Corpus maestro de familias, asociaciones y patrones';
    $lines[] = '';
    $lines[] = 'Generado: `' . $corpus['generated_at'] . '`.';
    $lines[] = '';
    $lines[] = 'Este documento es evidencia para revisar familias y asociaciones. No debe usarse como verdad automática para agrupar actividades en `/listado-actividades/`.';
    $lines[] = '';
    $lines[] = 'Criterio de lectura: la familia candidata se toma primero del nombre real de la actividad. El capítulo/contexto solo se usa como respaldo y esos casos quedan marcados como duda, porque pueden ser ubicación, frente o paquete contractual.';
    $lines[] = '';
    $lines[] = '## Fuentes revisadas';
    $lines[] = '';
    $lines[] = '- `programa_consolidado`: ' . count($corpus['program_projects']) . ' proyectos con cronograma.';
    $lines[] = '- `actividades`: ' . count($corpus['activity_rows']) . ' actividades existentes.';
    $lines[] = '- `pdc`: ' . count($corpus['pdc_rows']) . ' filas útiles.';
    $lines[] = '- `general_informe_pdc`: ' . count($corpus['report_pdc_rows']) . ' filas útiles.';
    $lines[] = '- `docs/pdc/*.xlsx`: ' . count($corpus['excel_files']) . ' archivos.';
    $lines[] = '';
    $lines[] = '### Cronogramas en DB';
    $lines[] = '';
    $lines[] = '| Proyecto | Filas | Actividades | Semanas | Fechas |';
    $lines[] = '|---|---:|---:|---|---|';
    foreach (array_slice($corpus['program_projects'], 0, 20) as $project) {
        $lines[] = '| ' . md($project['Proyecto_Proceso']) . ' | ' . $project['filas'] . ' | ' . $project['actividades'] . ' | ' . $project['semana_min'] . '-' . $project['semana_max'] . ' | ' . $project['fecha_min'] . ' a ' . $project['fecha_max'] . ' |';
    }
    $lines[] = '';
    $lines[] = '### Planes Excel';
    $lines[] = '';
    $lines[] = '| Archivo | Hojas útiles | Filas extraídas |';
    $lines[] = '|---|---|---:|';
    foreach ($corpus['excel_files'] as $file) {
        $lines[] = '| ' . md($file['file']) . ' | ' . md(implode(', ', $file['candidate_sheets'])) . ' | ' . $file['rows_extracted'] . ' |';
    }
    $lines[] = '';
    $lines[] = '## Separación familia vs contrato';
    $lines[] = '';
    $lines[] = '| Clasificación | Casos detectados | Uso esperado |';
    $lines[] = '|---|---:|---|';
    foreach (corpusClassificationCounts($corpus) as $classification => $count) {
        $lines[] = '| ' . md(classificationLabel($classification)) . ' | ' . $count . ' | ' . md(classificationUsage($classification)) . ' |';
    }
    $lines[] = '';
    $lines[] = '## Familias operativas candidatas';
    $lines[] = '';
    $lines[] = '| Familia | Evidencia | Modalidades | Aliases/paquetes frecuentes | Ejemplos |';
    $lines[] = '|---|---:|---|---|---|';
    foreach (array_slice(familiesByClassification($corpus, ['familia_operativa', 'alias_de_familia_operativa']), 0, 45) as $family) {
        $lines[] = '| ' . md($family['name']) . ' | ' . array_sum($family['counts']) . ' | ' . md(joinShort($family['modalities'])) . ' | ' . md(joinShort(array_merge($family['aliases'], $family['packages']), 4)) . ' | ' . md(joinShort($family['examples'], 2)) . ' |';
    }
    $lines[] = '';
    $lines[] = '## Elementos contractuales detectados';
    $lines[] = '';
    $lines[] = '| Elemento | Evidencia | Paquetes frecuentes | Uso esperado |';
    $lines[] = '|---|---:|---|---|';
    foreach (array_slice(familiesByClassification($corpus, ['elemento_contractual']), 0, 45) as $family) {
        $lines[] = '| ' . md($family['name']) . ' | ' . array_sum($family['counts']) . ' | ' . md(joinShort(array_merge($family['aliases'], $family['packages']), 4)) . ' | Alimenta `/contratos/`, no `/listado-actividades/`. |';
    }
    $lines[] = '';
    $lines[] = '## Asociaciones familia → paquete/modalidad';
    $lines[] = '';
    $lines[] = '| Familia | Paquete / contrato | Modalidades | Fuentes | Proyectos | Evidencia |';
    $lines[] = '|---|---|---|---|---|---:|';
    foreach (array_slice($corpus['packages'], 0, 60) as $package) {
        $lines[] = '| ' . md($package['family']) . ' | ' . md($package['package']) . ' | ' . md(joinShort($package['modalities'])) . ' | ' . md(joinShort($package['sources'])) . ' | ' . md(joinShort($package['projects'], 3)) . ' | ' . $package['count'] . ' |';
    }
    $lines[] = '';
    $lines[] = '## Patrones de confusión detectados';
    $lines[] = '';
    $grouped = groupByPattern($corpus['confusions']);
    foreach ($grouped as $pattern => $items) {
        $lines[] = '### ' . $pattern;
        $lines[] = '';
        $lines[] = '- Casos detectados: ' . count($items) . '.';
        foreach (array_slice($items, 0, 5) as $item) {
            $lines[] = '- ' . md($item['project']) . ': `' . md($item['activity']) . '` → familia candidata `' . md($item['family']) . '`; contexto `' . md($item['chapter']) . '`.';
        }
        $lines[] = '';
    }
    $lines[] = '## Muestras para validación manual';
    $lines[] = '';
    foreach ($corpus['samples'] as $bucket => $items) {
        $lines[] = '### ' . strtoupper(str_replace('_', ' ', $bucket));
        $lines[] = '';
        foreach ($items as $item) {
            $lines[] = '- ' . md($item);
        }
        if (empty($items)) {
            $lines[] = '- Sin muestra suficiente.';
        }
        $lines[] = '';
    }
    $lines[] = '## Reglas de uso del corpus';
    $lines[] = '';
    $lines[] = '- Usar estos datos para proponer familias, aliases y asociaciones contractuales.';
    $lines[] = '- No convertir paquetes de compra en actividades operativas sin validar el nombre real de programa.';
    $lines[] = '- Si una familia aparece solo por capítulo, debe quedar por revisar.';
    $lines[] = '- Si la hoja del cronograma es solo ubicación, usar ancestro operativo claro o pedir revisión.';
    $lines[] = '';
    $lines[] = '## Comando de regeneración';
    $lines[] = '';
    $lines[] = '```bash';
    $lines[] = 'docker compose exec app php docs/qa/pdc_family_corpus_extractor.php';
    $lines[] = 'docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify';
    $lines[] = '```';
    $lines[] = '';

    return implode("\n", $lines);
}

function corpusClassificationCounts(array $corpus): array
{
    return matrixClassificationCounts(uniqueMatrixCandidates($corpus['matrix_candidates'] ?? []));
}

function classificationLabel(string $classification): string
{
    return match ($classification) {
        'familia_operativa' => 'Familia operativa',
        'elemento_contractual' => 'Elemento contractual',
        'alias_de_familia_operativa' => 'Alias de familia operativa',
        default => 'Dudoso',
    };
}

function classificationUsage(string $classification): string
{
    return match ($classification) {
        'familia_operativa' => 'Puede alimentar Listado de Actividades si la evidencia es suficiente.',
        'elemento_contractual' => 'Debe alimentar Contratos; no debe quedar listo como actividad.',
        'alias_de_familia_operativa' => 'Debe normalizarse a la familia canónica antes de proponer.',
        default => 'Debe revisarse manualmente antes de automatizar.',
    };
}

function familiesByClassification(array $corpus, array $classifications): array
{
    return array_values(array_filter(
        $corpus['families'] ?? [],
        static fn(array $family): bool => in_array((string) ($family['classification'] ?? 'familia_operativa'), $classifications, true),
    ));
}

function summaryPayload(array $corpus): array
{
    $operationalFamilies = familiesByClassification($corpus, ['familia_operativa', 'alias_de_familia_operativa']);
    $contractualFamilies = familiesByClassification($corpus, ['elemento_contractual']);

    return [
        'generated_at' => $corpus['generated_at'],
        'source_counts' => [
            'program_projects' => count($corpus['program_projects']),
            'activity_rows' => count($corpus['activity_rows']),
            'pdc_rows' => count($corpus['pdc_rows']),
            'general_informe_pdc_rows' => count($corpus['report_pdc_rows']),
            'excel_files' => count($corpus['excel_files']),
            'families' => count($operationalFamilies),
            'contractual_elements' => count($contractualFamilies),
            'packages' => count($corpus['packages']),
            'confusions' => count($corpus['confusions']),
        ],
        'excel_files' => $corpus['excel_files'],
        'classification_counts' => corpusClassificationCounts($corpus),
        'top_families' => array_map(static function (array $family): array {
            return [
                'family' => $family['name'],
                'evidence' => array_sum($family['counts']),
                'sources' => $family['counts'],
                'modalities' => $family['modalities'],
                'aliases' => array_slice($family['aliases'], 0, 8),
            ];
        }, array_slice($operationalFamilies, 0, 30)),
        'top_contractual_elements' => array_map(static function (array $family): array {
            return [
                'element' => $family['name'],
                'evidence' => array_sum($family['counts']),
                'sources' => $family['counts'],
                'packages' => array_slice($family['packages'], 0, 8),
                'aliases' => array_slice($family['aliases'], 0, 8),
            ];
        }, array_slice($contractualFamilies, 0, 30)),
        'confusion_patterns' => array_map('count', groupByPattern($corpus['confusions'])),
    ];
}

function validateCorpus(array $corpus): array
{
    $errors = [];
    if (count($corpus['excel_files']) !== EXPECTED_EXCEL_FILES) {
        $errors[] = 'Se esperaban ' . EXPECTED_EXCEL_FILES . ' Excel en docs/pdc y se leyeron ' . count($corpus['excel_files']) . '.';
    }
    if (count($corpus['program_projects']) !== EXPECTED_PROGRAM_PROJECTS) {
        $errors[] = 'Se esperaban ' . EXPECTED_PROGRAM_PROJECTS . ' proyectos con programa_consolidado y se encontraron ' . count($corpus['program_projects']) . '.';
    }
    foreach (['jmc', 'da_porto', 'milan', 'metrolinea'] as $sample) {
        if (empty($corpus['samples'][$sample])) {
            $errors[] = "No se encontró muestra de validación para {$sample}.";
        }
    }
    if (empty($corpus['families']) || empty($corpus['confusions'])) {
        $errors[] = 'El corpus no produjo familias o patrones dudosos.';
    }
    $errors = array_merge($errors, validateCorpusFamilySeparation($corpus));

    return $errors;
}

function validateCorpusFamilySeparation(array $corpus): array
{
    $errors = [];
    $policy = matrixPolicy();
    $families = array_values($corpus['families'] ?? []);
    $operationalFamilies = familiesByClassification($corpus, ['familia_operativa', 'alias_de_familia_operativa']);

    foreach ($policy->familyAliases() as $alias => $canonical) {
        if (corpusHasFamilyNamed($families, $alias)) {
            $errors[] = "El alias {$alias} todavía aparece como familia separada; debe quedar bajo {$canonical}.";
        }
        if (!corpusHasFamilyNamed($families, $canonical)) {
            $errors[] = "La familia canónica {$canonical} no aparece para absorber el alias {$alias}.";
        }
    }

    foreach ($policy->contractualOnlyFamilies() as $contractual) {
        if (corpusHasFamilyNamed($operationalFamilies, $contractual)) {
            $errors[] = "El elemento contractual {$contractual} todavía aparece como familia operativa.";
        }
        $family = corpusFindFamily($families, $contractual);
        if ($family !== null && ($family['classification'] ?? '') !== 'elemento_contractual') {
            $errors[] = "El elemento {$contractual} aparece sin clasificación contractual.";
        }
    }

    return $errors;
}

function corpusHasFamilyNamed(array $families, string $name): bool
{
    return corpusFindFamily($families, $name) !== null;
}

function corpusFindFamily(array $families, string $name): ?array
{
    $needle = normalize($name);
    foreach ($families as $family) {
        if (normalize((string) ($family['name'] ?? '')) === $needle) {
            return $family;
        }
    }

    return null;
}

function inferModality(string $text): string
{
    $n = normalize($text);
    if (str_contains($n, 'TODO COSTO')) {
        return 'Todo costo';
    }
    if (str_contains($n, 'SUMINISTRO E INSTALACION') || str_contains($n, 'SUMINISTRO E INSTALACIÓN')) {
        return 'Suministro e instalación';
    }
    if (str_contains($n, 'MANO DE OBRA')) {
        return 'Mano de obra';
    }
    if (str_contains($n, 'SUMINISTRO') || str_contains($n, 'ORDEN DE COMPRA') || str_contains($n, 'COMPRA')) {
        return 'Suministro';
    }
    if (str_contains($n, 'ALQUILER') || str_contains($n, 'TORRE GRUA') || str_contains($n, 'MALACATE')) {
        return 'Equipos';
    }

    return 'Por definir';
}

function normalizeModality(string $text): string
{
    $text = trimText($text);
    return $text !== '' ? $text : 'Por definir';
}

function chapterFromActivity(string $activity): string
{
    if (preg_match('/\[Cap[ií]tulo:\s*([^\]]+)\]/iu', $activity, $matches) === 1) {
        return trimText($matches[1]);
    }

    return '';
}

function isUsefulActivity(string $activity): bool
{
    $n = normalize($activity);
    if ($n === '' || $n === 'ACTIVIDAD' || $n === 'OK' || str_contains($n, '#ERROR')) {
        return false;
    }
    if (preg_match('/^FECHA\b|^T$|^\d+$/u', $n) === 1) {
        return false;
    }

    return mb_strlen($activity, 'UTF-8') >= 4;
}

function sourceExample(array $record): string
{
    $parts = [
        (string) ($record['project'] ?? ''),
        cleanAlias((string) ($record['activity'] ?? '')),
    ];
    if (!empty($record['chapter'])) {
        $parts[] = 'Contexto: ' . cleanAlias((string) $record['chapter']);
    }
    if (!empty($record['file'])) {
        $parts[] = (string) $record['file'];
    }

    return implode(' · ', array_values(array_filter($parts)));
}

function cleanAlias(string $text): string
{
    $text = preg_replace('/\[Cap[ií]tulo:\s*[^\]]+\]/iu', ' ', trimText($text)) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim(mb_substr($text, 0, 120, 'UTF-8'));
}

function stripChapterFromActivity(string $text): string
{
    return trimText(preg_replace('/\[Cap[ií]tulo:\s*[^\]]+\]/iu', ' ', $text) ?? $text);
}

function trimText(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function normalize(string $text): string
{
    $text = mb_strtoupper(trimText($text), 'UTF-8');
    if (class_exists(Transliterator::class)) {
        $transliterator = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        if ($transliterator !== null) {
            $converted = $transliterator->transliterate($text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
    }
    $text = strtr($text, ['Ñ' => 'N']);
    $text = preg_replace('/[^A-Z0-9]+/u', ' ', $text) ?? $text;
    return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
}

function slug(string $text): string
{
    return strtolower(str_replace(' ', '_', normalize($text)));
}

function increment(array &$counts, string $key): void
{
    $key = $key !== '' ? $key : 'desconocido';
    $counts[$key] = ($counts[$key] ?? 0) + 1;
}

function pushLimited(array &$items, string|array $value, int $limit): void
{
    if ($value === '' || $value === []) {
        return;
    }
    foreach ($items as $existing) {
        if ($existing === $value) {
            return;
        }
    }
    if (count($items) < $limit) {
        $items[] = $value;
    }
}

function groupByPattern(array $confusions): array
{
    $grouped = [];
    foreach ($confusions as $item) {
        $grouped[$item['pattern']][] = $item;
    }
    ksort($grouped);
    return $grouped;
}

function joinShort(array $items, int $limit = 5): string
{
    $items = array_values(array_filter(array_map(static fn($item): string => is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : (string) $item, $items)));
    return implode('; ', array_slice($items, 0, $limit));
}

function md(string $text): string
{
    $text = str_replace(["\n", "\r"], ' ', $text);
    return str_replace('|', '\\|', $text);
}

function columnIndex(string $column): int
{
    $column = strtoupper($column);
    $number = 0;
    for ($i = 0, $len = strlen($column); $i < $len; $i++) {
        $number = $number * 26 + (ord($column[$i]) - 64);
    }

    return $number;
}
