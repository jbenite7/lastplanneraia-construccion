<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Core\Notifications\NotificationType;
use App\Services\NotificationService;
use App\Services\RestrictionConfigResolver;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use TableResolver;
class ReportController extends BaseController
{
    /**
     * Paleta de estado del exportador — la MISMA rampa clara calibrada de
     * pantalla (spec temas 2026-08-28, D17: unificar; retira la divergencia
     * documentada el 2026-08-03). Espejo ARGB de --ds-state-tint-*-light.
     *
     * Fuente de matiz por estado: docs/design-system/state-semantics.json
     * (moduleMappings), módulo `programacion-intermedia` para las filas
     * prefijadas `pi-*` (mapeo 1:1 exacto por clave).
     */
    public const STATE_FILLS = [
        'red' => 'FFF6C3C3',
        'orange' => 'FFF8C9A5',
        'amber' => 'FFFFECB2',
        'violet' => 'FFDAD4F5',
        'green' => 'FFC2E2D3',
        'blue' => 'FFC1D5EC',
        'teal' => 'FFC8EFEC',
        'neutral' => 'FFE4E4E7',
    ];

    private $reportProcessor;

    public function __construct()
    {
        parent::__construct();
        $this->reportProcessor = new \App\Services\ReportProcessor();
    }

    /**
     * Entry point for report generation (Downloads)
     * Route: /reportes/{tipo}/{id}
     */
    public function generate($tipo, $id = null)
    {
        $this->requireAuth();
        $this->authorizePermission('lps.reportes.generar', 'No tienes permisos para generar reportes.');

        try {
            switch ($tipo) {
                // Download Reports (Excel)
                case 'corte-programacion':
                    return $this->downloadCorteProgramacion();
                case 'restricciones':
                    return $this->downloadRestricciones();
                case 'compromisos':
                    return $this->downloadCompromisos();
                case 'consolidado-odc':
                    return $this->downloadConsolidadoODC();

                    // Processing Triggers (JSON Response)
                case 'curva-s':
                    $result = $this->reportProcessor->generateCurvaS();
                    echo json_encode($result);
                    exit;
                case 'general':
                    $result = $this->reportProcessor->generateReporteGeneral();
                    echo json_encode($result);
                    exit;
                case 'restricciones-general':
                    $result = $this->reportProcessor->generateRestriccionesGeneral();
                    echo json_encode($result);
                    exit;
                case 'pdc':
                    $result = $this->reportProcessor->generateReportePDC();
                    echo json_encode($result);
                    exit;
                case 'subcontratistas':
                    $result = $this->reportProcessor->generateReporteSubcontratistas();
                    echo json_encode($result);
                    exit;

                    // Run All (Cron Job Equivalent)
                case 'run-all':
                    $results = [];
                    $parts = [];
                    $hasErrors = false;

                    // 1. Curva S
                    try {
                        $results['curva_s'] = $this->reportProcessor->generateCurvaS();
                        $parts[] = "\xE2\x9C\x93 Curva S";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['curva_s'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 Curva S (" . $e->getMessage() . ")";
                    }

                    // 2. Reporte General
                    try {
                        $results['general'] = $this->reportProcessor->generateReporteGeneral();
                        $parts[] = "\xE2\x9C\x93 General";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['general'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 General (" . $e->getMessage() . ")";
                    }

                    // 3. Restricciones General
                    try {
                        $results['restricciones'] = $this->reportProcessor->generateRestriccionesGeneral();
                        $parts[] = "\xE2\x9C\x93 Restricciones";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['restricciones'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 Restricciones (" . $e->getMessage() . ")";
                    }

                    // 4. Reporte PDC
                    try {
                        $results['pdc'] = $this->reportProcessor->generateReportePDC();
                        $parts[] = "\xE2\x9C\x93 PDC";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['pdc'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 PDC (" . $e->getMessage() . ")";
                    }

                    // 5. Reporte Subcontratistas
                    try {
                        $results['subcontratistas'] = $this->reportProcessor->generateReporteSubcontratistas();
                        $parts[] = "\xE2\x9C\x93 Subcontratistas";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['subcontratistas'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 Subcontratistas (" . $e->getMessage() . ")";
                    }

                    // 6. CIC Update
                    try {
                        $semana = $_GET['semana'] ?? null;
                        if ($semana && is_numeric($semana)) {
                            $results['cic'] = $this->reportProcessor->updateCICProyectos((int) $semana);
                        } else {
                            $results['cic'] = $this->reportProcessor->updateCICProyectos(null);
                        }
                        $parts[] = "\xE2\x9C\x93 CIC";
                    } catch (Exception $e) {
                        $hasErrors = true;
                        $results['cic'] = ['success' => false, 'error' => $e->getMessage()];
                        $parts[] = "\xE2\x9C\x97 CIC (" . $e->getMessage() . ")";
                    }

                    // Build notification message
                    $prefix = $hasErrors ? 'Reportes consolidados con errores' : 'Reportes consolidados';
                    $message = $prefix . ': ' . implode(', ', $parts);

                    // Emit notification to current user
                    try {
                        $usuario = $_SESSION['usuario'] ?? null;
                        if ($usuario) {
                            $svc = new NotificationService();
                            $svc->emit($usuario, NotificationType::REPORT_RUN_ALL, $message);
                        }
                    } catch (\Throwable $e) {
                        error_log("REPORT_RUN_ALL_NOTIF_ERROR: " . $e->getMessage());
                    }

                    echo json_encode([
                        'success' => !$hasErrors,
                        'results' => $results,
                        'notification' => $message,
                    ]);
                    exit;

                default:
                    throw new Exception("Tipo de reporte no válido: " . $tipo);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Migrated Logic: descargarCorteProgramacion.php
     */
    private function downloadCorteProgramacion()
    {
        $db = $this->db;
        $dbName = $_POST['db'] ?? $_GET['db'] ?? ($_SESSION['db'] ?? '');
        $semanaInput = $_POST['semana'] ?? $_GET['semana'] ?? ($_SESSION['semana'] ?? 0);
        $semana = filter_var($semanaInput, FILTER_VALIDATE_INT);
        $proyecto = $_SESSION['proyecto'] ?? '';

        if (!$dbName || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            echo json_encode(['error' => 'Base de datos inválida o no especificada.']);
            exit;
        }

        if ($semana === false || $semana <= 0) {
            echo json_encode(['error' => 'Datos de sesión incompletos (Proyecto/Semana)']);
            exit;
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId === null) {
            echo json_encode(['error' => 'Proyecto no encontrado para la base de datos seleccionada.']);
            exit;
        }

        if ($proyecto === '') {
            $stmtProyecto = $db->query(
                "SELECT Proyecto_Proceso FROM general_proyectos_procesos WHERE Base_de_Datos = :db LIMIT 1",
                [':db' => $dbName],
            );
            $proyecto = $stmtProyecto->fetchColumn() ?: '';
        }

        // 1. Fetch Data
        try {
            $query = "SELECT Semana, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica,
                             Ejecutado AS EjecutadoRaw, ROUND(Ejecutado*100,1) AS Ejecutado,
                             Semanas_Inicio, Estado_Restricciones, cantidad_ppto, unidad
                      FROM " . TableResolver::resolveByPrefix($dbName, 'programa_consolidado') . "
                      WHERE project_id = :project_id AND Semana = :semana";

            $stmt = $db->query($query, [':project_id' => $projectId, ':semana' => $semana]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            echo json_encode(['error' => 'Error SQL: ' . $e->getMessage()]);
            exit;
        }

        // 2. Prepare Data for Excel
        $tabla = [];
        $tabla[] = ["Semana", "Id", "Actividad", "Titulo", "Fecha Inicio", "Fecha Fin", "Ruta Crítica", "Cantidad PPTO", "Ejecutado", "Cantidad Ejecutada", "Unidad"];

        // We also build "Assemble" data as per legacy script
        $tablaAssemble = [];
        $tablaAssemble[] = ["Semana", "Id", "Actividad", "Titulo", "Fecha Inicio", "Fecha Fin", "Ruta Crítica", "Cantidad PPTO", "Ejecutado", "Cantidad Ejecutada", "Unidad"];
        $corteRowStyles = [];

        $resolvePercentValue = static function ($value) {
            if (!is_numeric($value)) {
                return 0.0;
            }

            $numeric = (float) $value;

            return $numeric <= 1 ? $numeric * 100 : $numeric;
        };

        foreach ($rows as $data) {
            // ... Logic copied and adapted from legacy ...
            $Actividad = str_replace(["<small>", "</small>", "<b>", "</b>"], "", $data["Actividad"]);

            $Fecha_Inicio = $data["Fecha_Inicio"] ? date("Y-m-d", strtotime($data["Fecha_Inicio"])) : "";
            $Fecha_Fin = $data["Fecha_Fin"] ? date("Y-m-d", strtotime($data["Fecha_Fin"])) : "";
            $Fecha_Inicio_Assemble = $data["Fecha_Inicio"] ? date("m/d/Y", strtotime($data["Fecha_Inicio"])) : "";
            $Fecha_Fin_Assemble = $data["Fecha_Fin"] ? date("m/d/Y", strtotime($data["Fecha_Fin"])) : "";

            $Ejecutado = is_numeric($data["Ejecutado"] ?? null) ? round((float) $data["Ejecutado"], 1) : null;
            $cantidad_ppto = is_numeric($data["cantidad_ppto"] ?? null) ? round((float) $data["cantidad_ppto"], 1) : null;
            if ($cantidad_ppto !== null && $cantidad_ppto <= 0) {
                $cantidad_ppto = null;
            }

            $unidad = trim((string) ($data["unidad"] ?? ''));

            $cantidadEjecutada = null;
            if ($Ejecutado !== null) {
                if ($cantidad_ppto === null) {
                    $cantidadEjecutada = round($Ejecutado, 1);
                } else {
                    $cantidadEjecutada = ($Ejecutado == 0.0) ? 0.0 : round(($Ejecutado * $cantidad_ppto / 100), 1);
                }
                if ($unidad === '') {
                    $unidad = "%";
                }
            } else {
                $unidad = "";
            }

            $Ruta_Critica = ($data["Ruta_Critica"] == 1) ? "Ruta critica" : "Actividades NO criticas";

            $semanasInicio = is_numeric($data["Semanas_Inicio"] ?? null) ? (int) $data["Semanas_Inicio"] : 999;
            $estadoRestricciones = $resolvePercentValue($data["Estado_Restricciones"] ?? 0);
            $ejecutadoActual = $resolvePercentValue($data["EjecutadoRaw"] ?? 0);

            $isLiberated = $estadoRestricciones >= 100;
            $isStarted = $ejecutadoActual > 0;
            $shouldHaveStarted = $semanasInicio <= 0;

            $rowStyle = 'pdc-neutral';
            if (!empty($data["Titulo"])) {
                $rowStyle = 'pdc-header';
            } elseif ($isStarted) {
                $rowStyle = $isLiberated ? 'pdc-ok' : 'pdc-delayed';
            } elseif ($shouldHaveStarted) {
                $rowStyle = $isLiberated ? 'pdc-delayed' : 'pdc-critical-delay';
            } elseif ($semanasInicio === 1) {
                $rowStyle = 'pdc-warning';
            } elseif ($semanasInicio === 2) {
                $rowStyle = 'pdc-attention';
            } elseif ($semanasInicio > 2) {
                $rowStyle = 'pdc-ok';
            }

            $corteRowStyles[] = $rowStyle;

            $tabla[] = [$data["Semana"], $data["Id"], $Actividad, $data["Titulo"], $Fecha_Inicio, $Fecha_Fin, $Ruta_Critica, $cantidad_ppto, $Ejecutado, $cantidadEjecutada, $unidad];
            $tablaAssemble[] = [$data["Semana"], $data["Id"], $Actividad, $data["Titulo"], $Fecha_Inicio_Assemble, $Fecha_Fin_Assemble, $Ruta_Critica, $cantidad_ppto, $Ejecutado, $cantidadEjecutada, $unidad];
        }

        // 3. Generate Excel
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Last Planner AIA")->setTitle("Corte de Programación Last Planner AIA");

        $spreadsheet->setActiveSheetIndex(0);
        $hojaActiva = $spreadsheet->getActiveSheet();
        $hojaActiva->setTitle("Corte Programacion");

        $spreadsheet->createSheet();
        $hojaAssemble = $spreadsheet->getSheet(1);
        $hojaAssemble->setTitle("ASSEMBLE");

        // Estilos Generales
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);

        $maximaFila = count($tabla); // 1-based mostly because header is row 1 and data starts at 2, but array count includes header if we kept it.
        // Array $tabla includes header at index 0? Yes, we pushed header first.
        // So count is correct for number of rows.

        // Populate Data FIRST to ensure cells exist
        $hojaActiva->fromArray($tabla, null, 'A1');
        $hojaAssemble->fromArray($tablaAssemble, null, 'A1');

        // Apply Styles
        $hojaActiva->getStyle('A1:K1')->getFont()->setBold(true)->setSize(14);
        $hojaAssemble->getStyle('A1:K1')->getFont()->setBold(true)->setSize(14);

        $hojaActiva->getStyle('A1:K' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $hojaActiva->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        $hojaAssemble->getStyle('A1:K' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $hojaAssemble->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        // Ajuste automático de altura para respetar textos envueltos
        for ($row = 1; $row <= $maximaFila; $row++) {
            $hojaActiva->getRowDimension($row)->setRowHeight(-1);
            $hojaAssemble->getRowDimension($row)->setRowHeight(-1);
        }

        $bordesPrimeraFila = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        $hojaActiva->getStyle('A1:K1')->applyFromArray($bordesPrimeraFila);
        $hojaAssemble->getStyle('A1:K1')->applyFromArray($bordesPrimeraFila);

        $bordesActividades = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        // Apply borders from A2 to end
        if ($maximaFila >= 2) {
            $hojaActiva->getStyle('A2:K' . $maximaFila)->applyFromArray($bordesActividades);
            $hojaAssemble->getStyle('A2:K' . $maximaFila)->applyFromArray($bordesActividades);

            $rowPalette = [
                'pdc-header' => ['fill' => 'FF035766', 'font' => 'FFFFFFFF', 'bold' => true],
                'pdc-critical-delay' => ['fill' => self::STATE_FILLS['red'], 'font' => 'FF6B21A8', 'bold' => true],
                // pdc-delayed: sin matiz limpio — prog-sin-compromiso (violet) y
                // cal-incumplida (amber) comparten este bucket con matices distintos
                // en state-semantics.json. Sin decision, se conserva el ARGB previo.
                'pdc-delayed' => ['fill' => 'FFFEE2E2', 'font' => 'FF991B1B', 'bold' => false],
                'pdc-warning' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF854D0E', 'bold' => false],
                // pdc-attention: sin estado de state-semantics.json que lo referencie
                // (solo lo usa la logica interna "faltan 2 semanas" de este reporte).
                // Sin decision, se conserva el ARGB previo.
                'pdc-attention' => ['fill' => 'FFDBEAFE', 'font' => 'FF1E40AF', 'bold' => false],
                'pdc-ok' => ['fill' => self::STATE_FILLS['green'], 'font' => 'FF166534', 'bold' => false],
                'pdc-neutral' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF475569', 'bold' => false],
            ];

            foreach ($corteRowStyles as $index => $styleKey) {
                $excelRow = 2 + $index;
                $palette = $rowPalette[$styleKey] ?? $rowPalette['pdc-neutral'];

                foreach ([$hojaActiva, $hojaAssemble] as $sheetTarget) {
                    $rowRange = 'A' . $excelRow . ':K' . $excelRow;
                    $rowStyle = $sheetTarget->getStyle($rowRange);

                    $rowStyle->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($palette['fill']);

                    $rowStyle->getFont()->getColor()->setARGB($palette['font']);
                    $rowStyle->getFont()->setBold($palette['bold']);
                }
            }
        }

        $hojaActiva->freezePane('A2');
        $hojaAssemble->freezePane('A2');

        // Columns Dimensions and Formats
        $hojaActiva->getColumnDimension('A')->setWidth(10);
        $hojaActiva->getColumnDimension('B')->setWidth(15);
        $hojaActiva->getColumnDimension('C')->setWidth(45);
        $hojaActiva->getColumnDimension('D')->setWidth(10);
        $hojaActiva->getColumnDimension('E')->setWidth(12);
        $hojaActiva->getColumnDimension('F')->setWidth(12);
        $hojaActiva->getStyle('E')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getStyle('F')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getColumnDimension('G')->setWidth(20);
        $hojaActiva->getColumnDimension('H')->setWidth(12);
        $hojaActiva->getColumnDimension('I')->setWidth(12);
        $hojaActiva->getColumnDimension('J')->setWidth(12);
        $hojaActiva->getColumnDimension('K')->setWidth(12);
        $hojaActiva->getStyle('H')->getNumberFormat()->setFormatCode('0.0');
        $hojaActiva->getStyle('I')->getNumberFormat()->setFormatCode('0.0"%"');
        $hojaActiva->getStyle('J')->getNumberFormat()->setFormatCode('0.0');

        $hojaAssemble->getColumnDimension('A')->setWidth(10);
        $hojaAssemble->getColumnDimension('B')->setWidth(15);
        $hojaAssemble->getColumnDimension('C')->setWidth(45);
        $hojaAssemble->getColumnDimension('D')->setWidth(10);
        $hojaAssemble->getColumnDimension('E')->setWidth(12);
        $hojaAssemble->getColumnDimension('F')->setWidth(12);
        $hojaAssemble->getStyle('E')->getNumberFormat()->setFormatCode('MM/DD/YYYY');
        $hojaAssemble->getStyle('F')->getNumberFormat()->setFormatCode('MM/DD/YYYY');
        $hojaAssemble->getColumnDimension('G')->setWidth(20);
        $hojaAssemble->getColumnDimension('H')->setWidth(12);
        $hojaAssemble->getColumnDimension('I')->setWidth(12);
        $hojaAssemble->getColumnDimension('J')->setWidth(12);
        $hojaAssemble->getColumnDimension('K')->setWidth(12);
        $hojaAssemble->getStyle('H')->getNumberFormat()->setFormatCode('0.0');
        $hojaAssemble->getStyle('I')->getNumberFormat()->setFormatCode('0.0"%"');
        $hojaAssemble->getStyle('J')->getNumberFormat()->setFormatCode('0.0');

        // 4. Save File
        $filename = date("YmdBis") . "_{$dbName}_semana_{$semana}.xlsx";
        $savePath = PROJECT_ROOT . "/public/storage/cortesProgramacion";

        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $fullPath = $savePath . "/" . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // 5. Response
        if (file_exists($fullPath)) {
            echo json_encode(["url" => "/public/storage/cortesProgramacion/" . $filename]);
        } else {
            echo json_encode(["error" => "No se pudo crear el archivo"]);
        }
        exit;
    }

    private function downloadRestricciones()
    {
        $dbName = $_POST['db'] ?? $_GET['db'] ?? $_SESSION['db'] ?? '';
        $semanaInput = $_POST['semana'] ?? $_GET['semana'] ?? $_SESSION['semana'] ?? '';
        $semana = filter_var($semanaInput, FILTER_VALIDATE_INT);

        if (!$dbName || $semana === false || $semana <= 0) {
            echo json_encode(['error' => 'Base de datos o semana no especificados.']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            echo json_encode(['error' => 'Base de datos inválida.']);
            exit;
        }

        $db = $this->db;
        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId === null) {
            echo json_encode(['error' => 'Proyecto no encontrado para la base de datos seleccionada.']);
            exit;
        }

        // 1. Fetch Data
        // Project Name
        $stmt = $db->query("SELECT Proyecto_Proceso FROM general_proyectos_procesos WHERE Base_de_Datos = :db", [':db' => $dbName]);
        $proyecto = $stmt->fetchColumn() ?: '';

        // Dates
        $stmt = $db->query("SELECT * FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . " WHERE project_id = :project_id AND Semana = :semana", [':project_id' => $projectId, ':semana' => $semana]);
        $fechas = $stmt->fetch(\PDO::FETCH_ASSOC);
        $fechaInicio = isset($fechas["Fecha_Inicio_Sem"]) ? date("Y-m-d", strtotime($fechas["Fecha_Inicio_Sem"])) : '';
        $fechaFin = isset($fechas["Fecha_Fin_Sem"]) ? date("Y-m-d", strtotime($fechas["Fecha_Fin_Sem"])) : '';

        // Program Data
        $query = "SELECT * FROM " . TableResolver::resolveByPrefix($dbName, 'programa_consolidado') . "
                  WHERE project_id = :project_id
                  AND Semana = :semana
                  AND Fecha_Inicio IS NOT NULL
                  AND Fecha_Fin IS NOT NULL
                  AND Semanas_Inicio <= 6
                  AND Ejecutado < 1
                  AND Titulo = 0
                  ORDER BY Semanas_Inicio ASC, Estado_Restricciones DESC";
        $stmt = $db->query($query, [':project_id' => $projectId, ':semana' => $semana]);

        require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_intermedia.php';

        // Resolve restriction config based on project Area
        try {
            $restrConfig = RestrictionConfigResolver::resolve($dbName);
            $area = $restrConfig['area'];
        } catch (\Throwable $e) {
            error_log("ReportController: RestrictionConfigResolver failed for {$dbName}: " . $e->getMessage());
            $area = 'Construccion';
        }

        // Build dynamic restriction labels and column names
        $restrictionColumns = [];
        $restrictionLabels = [];

        if ($area === 'Pre-Construccion') {
            // PC labels: "Predecesora" for restriccion_pc_1 + dynamic names from DB
            $pcLabel2 = 'Restricción 2';
            $pcLabel3 = 'Restricción 3';
            $pcLabel4 = 'Restricción 4';

            try {
                $stmtPc = $db->query(
                    "SELECT pc_restr_2_nombre, pc_restr_3_nombre, pc_restr_4_nombre
                     FROM general_proyectos_procesos
                     WHERE Base_de_Datos = :db LIMIT 1",
                    [':db' => $dbName]
                );
                $proyectoPc = $stmtPc->fetch(\PDO::FETCH_ASSOC);
                if ($proyectoPc) {
                    if (!empty($proyectoPc['pc_restr_2_nombre'])) {
                        $pcLabel2 = $proyectoPc['pc_restr_2_nombre'];
                    }
                    if (!empty($proyectoPc['pc_restr_3_nombre'])) {
                        $pcLabel3 = $proyectoPc['pc_restr_3_nombre'];
                    }
                    if (!empty($proyectoPc['pc_restr_4_nombre'])) {
                        $pcLabel4 = $proyectoPc['pc_restr_4_nombre'];
                    }
                }
            } catch (\Throwable $e) {
                error_log("ReportController: Error loading PC restriction labels: " . $e->getMessage());
            }

            $restrictionColumns = ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4'];
            $restrictionLabels = ['Predecesora', $pcLabel2, $pcLabel3, $pcLabel4];
        } else {
            // Construccion: standard columns
            $restrictionColumns = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
            $restrictionLabels = ['Diseños y Especif.', 'Materiales', 'Mano de Obra', 'Equipos', 'Predecesoras', 'Proced. Constructivo', 'Modelación BIM'];
        }

        // Build header: fixed columns + dynamic restriction columns + % Liberación
        $headerRow = array_merge(
            ["Semana", "Consecutivo", "Id", "Actividad", "Semanas al Inicio", "Ejecutado"],
            $restrictionLabels,
            ["% Liberación"]
        );
        $tabla = [$headerRow];
        $tabla2 = [["Sub-Contratista", "Responsable AIA", "Observaciones"]];
        $restrictionRowStyles = [];

        $stateToExcelStyle = [
            'blocked-overdue-critical' => 'pi-blocked-overdue-critical',
            'blocked-overdue' => 'pi-blocked-overdue',
            'blocked-due' => 'pi-blocked-due',
            'alert-1-week' => 'pi-alert-1-week',
            'alert-2-3-weeks' => 'pi-alert-2-3-weeks',
            'alert-4-6-weeks' => 'pi-alert-4-6-weeks',
            'execution-blocked' => 'pi-execution-blocked',
            'liberated-control' => 'pi-liberated-control',
            'neutral' => 'pi-neutral',
        ];

        $formatDecimalComma = static function ($value, int $decimals = 1) {
            if (!is_numeric($value)) {
                return '';
            }

            return number_format((float) $value, $decimals, ',', '');
        };

        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $Actividad = str_replace(["<small>", "</small>", "<b>", "</b>"], "", $data["Actividad"]);

            $cantidad_ppto = $data["cantidad_ppto"];
            if ($cantidad_ppto == 0 || $cantidad_ppto == null || $cantidad_ppto == '') {
                // Fallback: actividades tipo % usan 100 como base de cálculo
                $cantidad_ppto = 100;
            }

            if ($data["unidad"] == "" || !$data["unidad"] || $data["unidad"] == null || $data["unidad"] == '%') {
                $unidad = "%";
                $Ejecutado = $formatDecimalComma(((float) $data["Ejecutado"] * $cantidad_ppto), 1) . "$unidad";
            } else {
                $unidad = $data["unidad"];
                $Ejecutado = $formatDecimalComma(((float) $data["Ejecutado"] * $cantidad_ppto), 1) . " $unidad (" . $formatDecimalComma(((float) $data["Ejecutado"] * 100), 1) . "%)";
            }

            $formatPercent = function ($val) use ($formatDecimalComma) {
                if ($val === "N/A" || $val === null || $val === '') {
                    return $val;
                }

                return $formatDecimalComma(((float) $val * 100), 1) . "%";
            };

            $Estado_Restricciones = $formatPercent($data["Estado_Restricciones"] ?? null);

            $stateKey = \pi_classify_state($data);
            $restrictionRowStyles[] = $stateToExcelStyle[$stateKey] ?? 'pi-neutral';

            $tabla[] = array_merge(
                [$data["Semana"], $data["Consecutivo"], $data["Id"], $Actividad,
                 $data["Semanas_Inicio"], $Ejecutado],
                array_map(fn($col) => $formatPercent($data[$col] ?? null), $restrictionColumns),
                [$Estado_Restricciones],
            );

            $tabla2[] = [$data["Sub_Contratista"], $data["Responsable_AIA"], $data["Observaciones"]];
        }

        // Professionals
        $stmt = $db->query(
            "SELECT nombre FROM " . TableResolver::resolveByPrefix($dbName, 'profesionales') . " WHERE project_id = :project_id AND activo = 1",
            [':project_id' => $projectId]
        );
        $tablaProfesionales = [["nombre"]];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tablaProfesionales[] = [$row["nombre"]];
        }

        // Subcontractors
        $stmt = $db->query(
            "SELECT subcontratista FROM " . TableResolver::resolveByPrefix($dbName, 'subcontratistas') . " WHERE project_id = :project_id AND activo = 1",
            [':project_id' => $projectId]
        );
        $tablaSubcontratistas = [["subcontratista"]];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tablaSubcontratistas[] = [$row["subcontratista"]];
        }

        // 2. Load Template
        // Path relative to index.php or absolute
        $templatePath = PROJECT_ROOT . "/public/storage/cortesRestricciones/plantillaLiberacionRestricciones.xlsx";

        if (!file_exists($templatePath)) {
            echo json_encode(["error" => "Plantilla no encontrada en: $templatePath"]);
            exit;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $spreadsheet->getProperties()->setCreator("Last Planner AIA")->setTitle("Liberación de Restricciones");

        $spreadsheet->setActiveSheetIndexByName("Restricciones");
        $hojaActiva = $spreadsheet->getActiveSheet();

        $hojaListas = $spreadsheet->getSheetByName("Listas");
        if (!$hojaListas) {
            // Should exist in template, but safety check
            $spreadsheet->createSheet();
            $hojaListas = $spreadsheet->getSheet($spreadsheet->getSheetCount() - 1);
            $hojaListas->setTitle("Listas");
        }

        $hojaLeyenda = $spreadsheet->getSheetByName("Leyenda y Acciones");
        if (!$hojaLeyenda) {
            $hojaLeyenda = $spreadsheet->createSheet();
            $hojaLeyenda->setTitle("Leyenda y Acciones");
        }

        // 3. Styles & Data
        $maximaFila = count($tabla); // Header is row 1 of array.
        // Legacy: $hojaActiva->fromArray($tabla, null, 'A3');
        // Legacy: $maximaFila = count($tabla); -> number of rows in array (header + data)
        // Legacy border range: 'A4:Q' . ($maximaFila + 2)
        // If count=2 (header + 1 row), maxFila=2. Range A4:Q4. Correct (starts at 3, header is 3).

        // Estilos Generales
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);
        $hojaActiva->getStyle('A3:Q3')->getFont()->setBold(true)->setSize(12);

        // Header Info
        $hojaActiva->getCell('D2')->setValue("Liberación de Restricciones Proyecto $proyecto \nSemana del $fechaInicio al $fechaFin");

        // Insert Data
        $hojaActiva->fromArray($tabla, null, 'A3');
        $hojaActiva->fromArray($tabla2, null, 'O3');

        $hojaListas->fromArray($tablaProfesionales, null, 'A1');
        $hojaListas->fromArray($tablaSubcontratistas, null, 'B1');

        $lastRow = $maximaFila + 2;

        // Asegurar ajuste y centrado en todas las celdas de salida
        $hojaActiva->getStyle('A2:Q' . $lastRow)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $hojaActiva->getStyle('D2:Q2')->getFont()->setBold(true)->setSize(14);

        // Auto-ajuste de filas
        for ($row = 2; $row <= $lastRow; $row++) {
            $hojaActiva->getRowDimension($row)->setRowHeight(-1);
        }

        // Auto-ajuste de columnas visibles
        foreach (range('A', 'Q') as $column) {
            if ($column === 'B') {
                continue;
            }
            $hojaActiva->getColumnDimension($column)->setAutoSize(true);
        }

        // Columna Actividad con ancho fijo cómodo
        $hojaActiva->getColumnDimension('D')->setAutoSize(false);
        $hojaActiva->getColumnDimension('D')->setWidth(45);

        // Listas auxiliares también centradas y ajustadas
        $lastListRow = max(count($tablaProfesionales), count($tablaSubcontratistas));
        if ($lastListRow > 0) {
            $hojaListas->getStyle('A1:B' . $lastListRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $hojaListas->getColumnDimension('A')->setAutoSize(true);
            $hojaListas->getColumnDimension('B')->setAutoSize(true);
        }

        // Borders
        $bordesActividades = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        // pi-* mapea 1:1 por clave a programacion-intermedia en
        // docs/design-system/state-semantics.json (moduleMappings): mismo
        // sufijo, mismo matiz, sin ambiguedad.
        $rowPalette = [
            'pi-blocked-overdue-critical' => ['fill' => self::STATE_FILLS['red'], 'font' => 'FF991B1B', 'bold' => true],
            'pi-blocked-overdue' => ['fill' => self::STATE_FILLS['orange'], 'font' => 'FF9A3412', 'bold' => false],
            'pi-blocked-due' => ['fill' => self::STATE_FILLS['violet'], 'font' => 'FF92400E', 'bold' => false],
            'pi-alert-1-week' => ['fill' => self::STATE_FILLS['amber'], 'font' => 'FF854D0E', 'bold' => false],
            'pi-alert-2-3-weeks' => ['fill' => self::STATE_FILLS['teal'], 'font' => 'FF3F6212', 'bold' => false],
            'pi-alert-4-6-weeks' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF166534', 'bold' => false],
            'pi-execution-blocked' => ['fill' => self::STATE_FILLS['blue'], 'font' => 'FF9A3412', 'bold' => false],
            'pi-liberated-control' => ['fill' => self::STATE_FILLS['green'], 'font' => 'FF0C4A6E', 'bold' => false],
            'pi-neutral' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF475569', 'bold' => false],
        ];

        // Apply borders if we have data rows (starting at A4)
        if ($maximaFila > 1) {
            // array row 1 goes to Excel 3. row N goes to 3 + N - 1.
            $hojaActiva->getStyle('A4:Q' . $lastRow)->applyFromArray($bordesActividades);

            foreach ($restrictionRowStyles as $index => $styleKey) {
                $excelRow = 4 + $index;
                $palette = $rowPalette[$styleKey] ?? $rowPalette['pi-neutral'];

                $rowRange = 'A' . $excelRow . ':Q' . $excelRow;
                $rowStyle = $hojaActiva->getStyle($rowRange);

                $rowStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($palette['fill']);

                $rowStyle->getFont()->getColor()->setARGB($palette['font']);
                $rowStyle->getFont()->setBold($palette['bold']);
            }
        }

        $hojaLeyenda->setCellValue('A1', 'Guia Operativa - Programación Intermedia (Last Planner 6 semanas)');
        $hojaLeyenda->mergeCells('A1:E1');
        $hojaLeyenda->setCellValue('A2', "Proyecto {$proyecto} | Semana del {$fechaInicio} al {$fechaFin}");
        $hojaLeyenda->mergeCells('A2:E2');

        $hojaLeyenda->fromArray([
            ['Enfoque', 'Estado', 'Regla Operativa', 'Accion Recomendada', 'SLA'],
        ], null, 'A3');

        $legendRows = [
            [
                'style' => 'pi-blocked-overdue-critical',
                'enfoque' => 'Resolver hoy',
                'estado' => 'Bloqueada Vencida (Critica)',
                'regla' => 'SI <= 0, EJ = 0, ER < 0.999, CR = si.',
                'accion' => 'Escalar bloqueo y activar recuperacion del frente critico.',
                'sla' => 'Hoy',
            ],
            [
                'style' => 'pi-blocked-overdue',
                'enfoque' => 'Resolver hoy',
                'estado' => 'Bloqueada Vencida',
                'regla' => 'SI <= 0, EJ = 0, ER < 0.999.',
                'accion' => 'Definir responsable y fecha de destrabe en la reunion diaria.',
                'sla' => '24-48h',
            ],
            [
                'style' => 'pi-blocked-due',
                'enfoque' => 'Resolver hoy',
                'estado' => 'Debe Iniciar (Con Restricciones)',
                'regla' => 'SI = 0, EJ = 0, ER < 0.999.',
                'accion' => 'Cerrar liberacion y asegurar recursos antes del inicio.',
                'sla' => 'Hoy',
            ],
            [
                'style' => 'pi-execution-blocked',
                'enfoque' => 'Resolver hoy',
                'estado' => 'En Ejecucion (Con Restricciones)',
                'regla' => '0 < EJ < 0.999, ER < 0.999.',
                'accion' => 'Eliminar restricciones activas para evitar retrabajos y paradas.',
                'sla' => 'Hoy',
            ],
            [
                'style' => 'pi-alert-1-week',
                'enfoque' => 'Gestion semanal',
                'estado' => 'Alerta 1 Semana',
                'regla' => 'SI = 1, EJ = 0, ER < 0.999.',
                'accion' => 'Cerrar compras, permisos y frentes antes del siguiente corte.',
                'sla' => 'Esta semana',
            ],
            [
                'style' => 'pi-alert-2-3-weeks',
                'enfoque' => 'Gestion semanal',
                'estado' => 'Alerta 2-3 Semanas',
                'regla' => 'SI entre 2 y 3, EJ = 0, ER < 0.999.',
                'accion' => 'Implementar plan preventivo con abastecimiento y mano de obra.',
                'sla' => 'Semanal',
            ],
            [
                'style' => 'pi-alert-4-6-weeks',
                'enfoque' => 'Seguimiento',
                'estado' => 'Alerta 4-6 Semanas',
                'regla' => 'SI entre 4 y 6, EJ = 0, ER < 0.999.',
                'accion' => 'Monitorear lookahead y anticipar restricciones emergentes.',
                'sla' => 'Semanal',
            ],
            [
                'style' => 'pi-liberated-control',
                'enfoque' => 'Seguimiento',
                'estado' => 'Liberada / Control',
                'regla' => 'ER >= 0.999 (iniciada o no iniciada).',
                'accion' => 'Mantener control de compromisos y trazabilidad de cierre.',
                'sla' => 'Monitoreo',
            ],
        ];

        $legendRowNumber = 4;
        foreach ($legendRows as $legendRow) {
            $hojaLeyenda->fromArray([
                [
                    $legendRow['enfoque'],
                    $legendRow['estado'],
                    $legendRow['regla'],
                    $legendRow['accion'],
                    $legendRow['sla'],
                ],
            ], null, 'A' . $legendRowNumber);

            $palette = $rowPalette[$legendRow['style']] ?? $rowPalette['pi-neutral'];
            $legendRange = 'A' . $legendRowNumber . ':E' . $legendRowNumber;
            $legendStyle = $hojaLeyenda->getStyle($legendRange);

            $legendStyle->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($palette['fill']);

            $legendStyle->getFont()->getColor()->setARGB($palette['font']);
            $legendStyle->getFont()->setBold($palette['bold']);

            $legendRowNumber++;
        }

        $legendLastRow = $legendRowNumber - 1;

        $hojaLeyenda->getStyle('A1:E1')->getFont()->setBold(true)->setSize(14);
        $hojaLeyenda->getStyle('A2:E2')->getFont()->setSize(11);
        $hojaLeyenda->getStyle('A3:E3')->getFont()->setBold(true);
        $hojaLeyenda->getStyle('A3:E3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF035766');
        $hojaLeyenda->getStyle('A3:E3')->getFont()->getColor()->setARGB('FFFFFFFF');

        $hojaLeyenda->getStyle('A1:E' . $legendLastRow)->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $hojaLeyenda->getStyle('A1:E2')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $hojaLeyenda->getStyle('A3:A' . $legendLastRow)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $hojaLeyenda->getStyle('E3:E' . $legendLastRow)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $hojaLeyenda->getStyle('A3:E' . $legendLastRow)->applyFromArray($bordesActividades);

        $hojaLeyenda->getColumnDimension('A')->setWidth(18);
        $hojaLeyenda->getColumnDimension('B')->setWidth(36);
        $hojaLeyenda->getColumnDimension('C')->setWidth(42);
        $hojaLeyenda->getColumnDimension('D')->setWidth(56);
        $hojaLeyenda->getColumnDimension('E')->setWidth(14);

        for ($legendRowHeight = 1; $legendRowHeight <= $legendLastRow; $legendRowHeight++) {
            $hojaLeyenda->getRowDimension($legendRowHeight)->setRowHeight(-1);
        }

        $hojaLeyenda->freezePane('A4');

        $hojaActiva->getColumnDimension('B')->setVisible(false);
        $hojaListas->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // Logo
        $logoPath = PROJECT_ROOT . "/public/img/logoHorizontal.png";
        if (file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('logoAIA');
            $drawing->setDescription('logoAIA');
            $drawing->setPath($logoPath);
            $drawing->setCoordinates('A2');
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(4);
            $drawing->setWidth(170);
            $drawing->setWorksheet($hojaActiva);
        }

        $spreadsheet->setActiveSheetIndexByName('Restricciones');

        // 4. Save
        $filename = date("YmdBis") . "{$dbName}_Liberacion_Restricciones_S{$semana}.xlsx";
        $savePath = PROJECT_ROOT . "/public/storage/cortesRestricciones"; // Legacy exact path

        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $fullPath = $savePath . "/" . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // 5. Response
        if (file_exists($fullPath)) {
            echo json_encode(["url" => "/public/storage/cortesRestricciones/" . $filename]);
        } else {
            echo json_encode(["error" => "No se pudo crear el archivo"]);
        }
        exit;
    }

    private function downloadCompromisos()
    {
        $dbName = $_POST['db'] ?? $_GET['db'] ?? $_SESSION['db'] ?? '';
        $semanaInput = $_POST['semana'] ?? $_GET['semana'] ?? $_SESSION['semana'] ?? '';
        $semana = filter_var($semanaInput, FILTER_VALIDATE_INT);

        if (!$dbName || $semana === false || $semana <= 0) {
            echo json_encode(['error' => 'Base de datos o semana no especificados.']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            echo json_encode(['error' => 'Base de datos inválida.']);
            exit;
        }

        $db = $this->db;
        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId === null) {
            echo json_encode(['error' => 'Proyecto no encontrado para la base de datos seleccionada.']);
            exit;
        }

        try {
            $stmtProyecto = $db->query(
                "SELECT Proyecto_Proceso FROM general_proyectos_procesos WHERE Base_de_Datos = :db",
                [':db' => $dbName],
            );
            $proyecto = $stmtProyecto->fetchColumn();

            if (!$proyecto) {
                echo json_encode(['error' => 'Proyecto no encontrado para la base de datos seleccionada.']);
                exit;
            }

            $stmtFechas = $db->query(
                "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . " WHERE project_id = :project_id AND Semana = :semana",
                [':project_id' => $projectId, ':semana' => $semana],
            );
            $fechas = $stmtFechas->fetch(\PDO::FETCH_ASSOC);

            if (!$fechas) {
                echo json_encode(['error' => 'No se encontraron fechas para la semana seleccionada.']);
                exit;
            }

            $fechaInicioSemana = date('Y-m-d', strtotime($fechas['Fecha_Inicio_Sem']));
            $fechaFinSemana = date('Y-m-d', strtotime($fechas['Fecha_Fin_Sem']));
            require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_semanal.php';
            $phaseKey = \ps_weekly_phase_key($fechas['Semanal_Confirmada'] ?? 0);

            $stmtData = $db->query(
                "SELECT * FROM " . TableResolver::resolveByPrefix($dbName, 'programacion_semanal') . " WHERE project_id = :project_id AND Semana = :semana AND (Activa = '1' OR Activa = 'NA')",
                [':project_id' => $projectId, ':semana' => $semana],
            );

            $tabla = [[
                "Semana", "Id", "Actividad", "Descripcion", "Subcontratista", "Responsable AIA",
                "Ejecución Actual", "Compromiso Para la Semana", "Ejecutado Real en la Semana", "PAC", "% Completado",
            ]];
            $compromisosRowStyles = [];

            $weeklyStateToExcelStyle = [
                'prog-bloqueo-critico-sin-compromiso' => 'pdc-critical-delay',
                'prog-sin-compromiso' => 'pdc-delayed',
                'prog-lista-para-confirmar' => 'pdc-ok',
                'cal-sin-calificar' => 'pdc-warning',
                'cal-incumplida-critica' => 'pdc-critical-delay',
                'cal-incumplida' => 'pdc-delayed',
                'cal-cumplida-control' => 'pdc-ok',
                'ps-no-activa' => 'pdc-neutral',
            ];

            $formatDecimalComma = static function ($value, int $decimals = 1) {
                if (!is_numeric($value)) {
                    return '';
                }

                return number_format((float) $value, $decimals, ',', '');
            };

            while ($data = $stmtData->fetch(\PDO::FETCH_ASSOC)) {
                $actividad = str_replace(["<small>", "</small>", "<b>", "</b>"], "", (string) ($data['Actividad'] ?? ''));
                $unidad = (string) ($data['Unidad'] ?? '');

                $cantidadPpto = (float) ($data['cantidad_ppto'] ?? 100);
                if ($cantidadPpto <= 0) {
                    $cantidadPpto = 100;
                }

                $ejecutado = $formatDecimalComma((((float) ($data['Ejecutado'] ?? 0)) * $cantidadPpto), 1) . $unidad;
                $compromiso = $formatDecimalComma(((float) ($data['Compromiso'] ?? 0)), 1) . $unidad;
                $ejecutadoReal = '';
                if (isset($data['Ejecutado_Real']) && $data['Ejecutado_Real'] !== '') {
                    $ejecutadoReal = $formatDecimalComma(((float) $data['Ejecutado_Real']), 1) . $unidad;
                }

                $pac = empty($data['PAC']) ? '0,0%' : $formatDecimalComma((floatval($data['PAC']) * 100), 1) . '%';
                $pCompletado = empty($data['P_Completado']) ? '0,0%' : $formatDecimalComma((floatval($data['P_Completado']) * 100), 1) . '%';

                $weeklyStateKey = \ps_classify_state($data, $phaseKey);
                $compromisosRowStyles[] = $weeklyStateToExcelStyle[$weeklyStateKey] ?? 'pdc-neutral';

                $tabla[] = [
                    $data['Semana'] ?? '',
                    $data['Id'] ?? '',
                    $actividad,
                    $data['Descripcion'] ?? '',
                    $data['Sub_Contratista'] ?? '',
                    $data['Responsable_AIA'] ?? '',
                    $ejecutado,
                    $compromiso,
                    $ejecutadoReal,
                    $pac,
                    $pCompletado,
                ];
            }

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()->setCreator('Last Planner AIA')->setTitle('Compromisos Last Planner');
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(12);

            $sheet->mergeCells('A2:B2');
            $sheet->mergeCells('C2:I2');
            $sheet->mergeCells('J2:K2');
            $sheet->getRowDimension('2')->setRowHeight(75);

            $sheet->getStyle('A2:K3')->getFont()->setBold(true);
            $sheet->getStyle('A3:K3')->getFont()->setSize(13);
            $sheet->getStyle('C2')->getFont()->setSize(18);
            $sheet->getStyle('C2')->getAlignment()->setWrapText(true);
            $sheet->setCellValue('C2', "Compromisos Semanales Proyecto {$proyecto} \nSemana del {$fechaInicioSemana} al {$fechaFinSemana}");

            $logoPath = PROJECT_ROOT . '/public/img/logoHorizontal.png';
            if (file_exists($logoPath)) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('logoAIA');
                $drawing->setPath($logoPath);
                $drawing->setCoordinates('A2');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(15);
                $drawing->setWidth(180);
                $drawing->setWorksheet($sheet);
            }

            $sheet->fromArray($tabla, null, 'A3');
            $maxFila = count($tabla) + 2;

            $rangeTotal = 'A2:K' . $maxFila;
            $sheet->getStyle($rangeTotal)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            for ($row = 3; $row <= $maxFila; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(-1);
            }

            $sheet->getStyle('C2:I2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
            $sheet->getStyle('A3:K3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

            $borderThin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
            $sheet->getStyle('A2:B2')->applyFromArray($borderThin);
            $sheet->getStyle('C2:I2')->applyFromArray($borderThin);
            $sheet->getStyle('J2:K2')->applyFromArray($borderThin);
            $sheet->getStyle('A3:K' . $maxFila)->applyFromArray($borderThin);

            $rowPalette = [
                'pdc-header' => ['fill' => 'FF035766', 'font' => 'FFFFFFFF', 'bold' => true],
                'pdc-critical-delay' => ['fill' => self::STATE_FILLS['red'], 'font' => 'FF6B21A8', 'bold' => true],
                // pdc-delayed: sin matiz limpio — prog-sin-compromiso (violet) y
                // cal-incumplida (amber) comparten este bucket con matices distintos
                // en state-semantics.json. Sin decision, se conserva el ARGB previo.
                'pdc-delayed' => ['fill' => 'FFFEE2E2', 'font' => 'FF991B1B', 'bold' => false],
                'pdc-warning' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF854D0E', 'bold' => false],
                // pdc-attention: sin estado de state-semantics.json que lo referencie
                // (solo lo usa la logica interna "faltan 2 semanas" de este reporte).
                // Sin decision, se conserva el ARGB previo.
                'pdc-attention' => ['fill' => 'FFDBEAFE', 'font' => 'FF1E40AF', 'bold' => false],
                'pdc-ok' => ['fill' => self::STATE_FILLS['green'], 'font' => 'FF166534', 'bold' => false],
                'pdc-neutral' => ['fill' => self::STATE_FILLS['neutral'], 'font' => 'FF475569', 'bold' => false],
            ];

            foreach ($compromisosRowStyles as $index => $styleKey) {
                $excelRow = 4 + $index;
                $palette = $rowPalette[$styleKey] ?? $rowPalette['pdc-neutral'];

                $rowRange = 'A' . $excelRow . ':K' . $excelRow;
                $rowStyle = $sheet->getStyle($rowRange);

                $rowStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($palette['fill']);

                $rowStyle->getFont()->getColor()->setARGB($palette['font']);
                $rowStyle->getFont()->setBold($palette['bold']);
            }

            $sheet->freezePane('A4');

            $widths = ['A' => 8, 'B' => 10, 'C' => 45, 'D' => 35, 'E' => 20, 'F' => 20, 'G' => 15, 'H' => 15, 'I' => 15, 'J' => 10, 'K' => 10];
            foreach ($widths as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            $filename = date('YmdHis') . $dbName . '_Compromisos_S' . $semana . '.xlsx';
            $savePath = PROJECT_ROOT . '/public/storage/compromisosSemana';

            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }

            $fullPath = $savePath . '/' . $filename;
            $writer = new Xlsx($spreadsheet);
            $writer->save($fullPath);

            if (file_exists($fullPath)) {
                echo json_encode(['url' => '/public/storage/compromisosSemana/' . $filename]);
            } else {
                echo json_encode(['error' => 'No se pudo crear el archivo']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al generar compromisos: ' . $e->getMessage()]);
        }

        exit;
    }

    private function downloadConsolidadoODC()
    {
        $dbName = $_POST['db'] ?? $_GET['db'] ?? $_SESSION['db'] ?? '';

        if (!$dbName) {
            echo json_encode(['error' => 'Base de datos no especificada.']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            echo json_encode(['error' => 'Base de datos inválida.']);
            exit;
        }

        $db = $this->db;
        $projectId = TableResolver::getProjectIdByPrefix($dbName);
        if ($projectId === null) {
            echo json_encode(['error' => 'Proyecto no encontrado para la base de datos seleccionada.']);
            exit;
        }

        // 1. Fetch Data
        try {
            $query = "SELECT * FROM cambios WHERE project_id = :project_id ORDER BY id ASC";
            $stmt = $db->query($query, [':project_id' => $projectId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error SQL: ' . $e->getMessage()]);
            exit;
        }

        $tabla = [];
        $tabla[] = ["Id", "Solicitante", "Fecha Solicitud", "Prioridad", "Tipo de Cambio", "Responsable", "Descripcion", "Días Afectación Cronograma", "Costo Directo + AIU + IVA", "Valor Aprobado", "Fecha Tentativa de Definición", "Fecha de Entrega a Interventoría", "Fecha de Definición", "Aprobación"];

        foreach ($results as $data) {
            $solicitanteCambio = $data['solicitanteCambio'];
            switch ($solicitanteCambio) {
                case 1: $solicitanteCambio = "Obra";
                    break;
                case 2: $solicitanteCambio = "Cliente";
                    break;
                case 3: $solicitanteCambio = "Interventoría";
                    break;
                case 4: $solicitanteCambio = "Otro [{$data['detalleSolicitanteOtro']}]";
                    break;
            }

            $responsableSolucion = $data['responsableSolucion'];
            switch ($responsableSolucion) {
                case 1: $responsableSolucion = "Obra";
                    break;
                case 2: $responsableSolucion = "Cliente";
                    break;
                case 3: $responsableSolucion = "Interventoría";
                    break;
                case 4: $responsableSolucion = "Otro [{$data['detalleResponsableSolucion']}]";
                    break;
            }

            $aprobacion = $data['aprobacion'];
            switch ($aprobacion) {
                case 1: $aprobacion = "Aprobado";
                    break;
                case 2: $aprobacion = "Aprobado con Restricciones";
                    break;
                case 3: $aprobacion = "No Aprobado";
                    break;
                case 4: $aprobacion = "En Estudio";
                    break;
                case 5: $aprobacion = "Desistido";
                    break;
            }

            $tipoCambioData = json_decode($data['tipoCambio'], true);
            $listaCambios = "";
            if (isset($tipoCambioData["tiposCambio"])) {
                foreach ($tipoCambioData["tiposCambio"] as $key => $value) {
                    if ($value !== '0') {
                        $listaCambios .= $key . ", ";
                    }
                }
            }
            $listaCambios = rtrim($listaCambios, ", ");

            $tabla[] = [
                $data['id'],
                $solicitanteCambio,
                $data['fechaSolicitud'],
                $data['prioridad'],
                $listaCambios,
                $responsableSolucion,
                $data['descripcion'],
                $data['inputTiempoCronogramaAfectado'],
                $data['costoDirectoAIUIVA'],
                $data['valorAprobado'],
                $data['fechaTentativaDefinicion'],
                $data['fechaEntregaInterventoria'],
                $data['fechaDefinicion'],
                $aprobacion,
            ];
        }

        // 2. Excel Generation
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Last Planner AIA")->setTitle("Consolidado Ordenes de Cambio");

        $spreadsheet->setActiveSheetIndex(0);
        $hojaActiva = $spreadsheet->getActiveSheet();
        $hojaActiva->setTitle("Consolidado ODC");

        // General Styles
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);

        $hojaActiva->getStyle('A1:N1')->getFont()->setBold(true)->setSize(14);

        $maximaFila = count($tabla); // Includes header

        // Fill Data
        $hojaActiva->fromArray($tabla, null, 'A1');

        $hojaActiva->getStyle('A1:N' . $maximaFila)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $hojaActiva->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        $bordesPrimeraFila = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        $hojaActiva->getStyle('A1:N1')->applyFromArray($bordesPrimeraFila);

        $bordesActividades = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['argb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        // Apply borders from A2 to end
        if ($maximaFila >= 2) {
            $hojaActiva->getStyle('A2:N' . $maximaFila)->applyFromArray($bordesActividades);
        }

        $hojaActiva->freezePane('A2');

        // Conditional Formatting
        // Los 5 estados de control-cambios (En Estudio/Aprobado/Aprobado con
        // Restricciones/No Aprobado/Desistido) solo declaran `level` en
        // docs/design-system/state-semantics.json — no tienen matiz asignado
        // ahi. Sin decision, se conserva el ARGB previo tal cual.
        $formatoCondicionalEstudio = new Style(false, true);
        $formatoCondicionalEstudio->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB('FFC5D9F1');
        $formatoCondicionalEstudio->getFont()->setBold(true);

        $formatoCondicionalNoAprobado = new Style(false, true);
        $formatoCondicionalNoAprobado->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB('FFDA9694');
        $formatoCondicionalNoAprobado->getFont()->setBold(true);

        $formatoCondicionalAprobadoRestricciones = new Style(false, true);
        $formatoCondicionalAprobadoRestricciones->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB('FFFFFFBD');
        $formatoCondicionalAprobadoRestricciones->getFont()->setBold(true);

        $formatoCondicionalAprobado = new Style(false, true);
        $formatoCondicionalAprobado->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB('FFC0E399');
        $formatoCondicionalAprobado->getFont()->setBold(true);

        $formatoCondicionalDesistido = new Style(false, true);
        $formatoCondicionalDesistido->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setARGB('FF808080');
        $formatoCondicionalDesistido->getFont()->setBold(true);

        $cellRange = 'A2:N' . $maximaFila;
        $conditionalStyles = [];
        $wizardFactory = new Wizard($cellRange);

        $rules = [
            ['"En Estudio"', $formatoCondicionalEstudio],
            ['"No Aprobado"', $formatoCondicionalNoAprobado],
            ['"Aprobado con Restricciones"', $formatoCondicionalAprobadoRestricciones],
            ['"Aprobado"', $formatoCondicionalAprobado],
            ['"Desistido"', $formatoCondicionalDesistido],
        ];

        foreach ($rules as $rule) {
            $expressionWizard = $wizardFactory->newRule(Wizard::EXPRESSION);
            /** @var \PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard\Expression $expressionWizard */
            $expressionWizard->expression('($N1)=' . $rule[0])->setStyle($rule[1]);
            $conditionalStyles[] = $expressionWizard->getConditional();
        }

        $hojaActiva->getStyle($expressionWizard->getCellRange())->setConditionalStyles($conditionalStyles);

        // Column Dimensions and Formats
        $hojaActiva->getColumnDimension('A')->setWidth(10);
        $hojaActiva->getColumnDimension('B')->setWidth(15);
        $hojaActiva->getColumnDimension('C')->setWidth(15);
        $hojaActiva->getStyle('C')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getColumnDimension('D')->setWidth(10);
        $hojaActiva->getColumnDimension('E')->setWidth(20);
        $hojaActiva->getColumnDimension('F')->setWidth(15);
        $hojaActiva->getColumnDimension('G')->setWidth(30);
        $hojaActiva->getColumnDimension('H')->setWidth(15);
        $hojaActiva->getColumnDimension('I')->setWidth(15);
        $hojaActiva->getColumnDimension('J')->setWidth(15);
        $hojaActiva->getColumnDimension('K')->setWidth(15);
        $hojaActiva->getStyle('K')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getColumnDimension('L')->setWidth(15);
        $hojaActiva->getStyle('L')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getColumnDimension('M')->setWidth(15);
        $hojaActiva->getStyle('M')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        $hojaActiva->getColumnDimension('N')->setWidth(15);
        $hojaActiva->getStyle('I:J')->getNumberFormat()->setFormatCode('"$"#,##0');

        $hojaActiva->getSheetView()->setZoomScale(80);

        // 3. Save File
        $filename = date("YmdBis") . "_{$dbName}_ConsolidadoODC.xlsx";
        $savePath = PROJECT_ROOT . "/public/storage/ordenes";

        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $fullPath = $savePath . "/" . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // 4. Response
        if (file_exists($fullPath)) {
            // Note: Legacy returns absolute path or relative?
            // Legacy returned just $archivoDescargar which was "ordenes/..." relative to execution.
            // But AJAX client window.location.href utilized it relative to current page URL.
            // If current page is /legacy/controlCambios/controlCambios.php, then "ordenes/..." is valid.
            // Since we are moving to global API, we should probably return absolute URL path.
            echo json_encode(["url" => "/public/storage/ordenes/" . $filename]);
        } else {
            echo json_encode(["error" => "No se pudo crear el archivo"]);
        }
        exit;
    }
}
