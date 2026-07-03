<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
use Exception;
use PDO;
use Throwable;
use SplFileInfo;

class ListadoActividadesApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance(); // En legacy está mapeado globalmente o requiere su propio namespace
    }

    public function downloadTemplate(): void
    {
        $path = PROJECT_ROOT . '/public/archivosBase/listadoActividades.csv';

        if (!is_file($path) || !is_readable($path)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Plantilla no disponible.';
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="listadoActividades.csv"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($path);
        exit;
    }

    public function list(): void
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = $this->resolveMaxSemana($dbPrefix, $projectId);

            $this->requirePermission('lps.listado_actividades.ver', 'No autorizado para consultar el listado de actividades.');

            $query = "SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?";
            $stmt = $this->db->query($query, [$projectId, $semana]);
            $conteo = $stmt->fetchColumn();

            $response = ["data" => []];

            if ($conteo == 0) {
                // Return default empty structure
                $response["data"][] = [
                    "Id" => "",
                    "codigo" => "",
                    "actividad" => "",
                    "descripcionActividad" => "",
                    "actividadInicio" => "",
                    "nombreActividadInicio" => "",
                    "fechaInicio" => "",
                    "tipoContrato" => "",
                    "semanaActualizacion" => "",
                ];
            } else {
                $query1 = "SELECT
                            a.Id,
                            a.codigo,
                            a.actividad,
                            a.descripcionActividad,
                            COALESCE(
                                (
                                    SELECT pc.unique_id
                                    FROM programa_consolidado pc
                                    WHERE pc.project_id = a.project_id
                                      AND pc.Semana = a.semanaActualizacion
                                      AND (pc.unique_id = a.actividadInicio OR pc.Actividad = a.actividadInicio)
                                    ORDER BY pc.Fecha_Inicio ASC
                                    LIMIT 1
                                ),
                                a.actividadInicio
                            ) AS actividadInicio,
                            COALESCE(
                                (
                                    SELECT CONCAT(pc.Id, '. ', pc.Actividad, ' (Inicia en: ', pc.Fecha_Inicio, ')')
                                    FROM programa_consolidado pc
                                    WHERE pc.project_id = a.project_id
                                      AND pc.Semana = a.semanaActualizacion
                                      AND (pc.unique_id = a.actividadInicio OR pc.Actividad = a.actividadInicio)
                                    ORDER BY pc.Fecha_Inicio ASC
                                    LIMIT 1
                                ),
                                a.nombreActividadInicio
                            ) AS nombreActividadInicio,
                            a.fechaInicio,
                            a.tipoContrato,
                            a.semanaActualizacion
                           FROM actividades AS a
                           WHERE a.project_id = ? AND a.semanaActualizacion = ?
                           ORDER BY a.Id";

                $stmt1 = $this->db->query($query1, [$projectId, $semana]);
                $response["data"] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->jsonResponse($response);

        } catch (Throwable $e) {
            error_log("Error en ListadoActividadesApiController@list: " . $e->getMessage());
            $this->jsonError('No se pudo cargar el listado de actividades.', 500, ["data" => []]);
        }
    }

    public function save(): void
    {
        $opcion = $_POST["opcion"] ?? '';

        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = $this->resolveMaxSemana($dbPrefix, $projectId);

            $this->requirePermission('lps.listado_actividades.ver', 'No autorizado para consultar el listado de actividades.');

            if (in_array($opcion, ['registrar', 'modificar', 'eliminar', 'cargarExcel'], true)) {
                $this->requirePermission('lps.listado_actividades.editar', 'No autorizado para modificar el listado de actividades.');
            }

            if ($opcion == "registrar") {
                $this->registrar($dbPrefix, $projectId, $semana);
            } elseif ($opcion == "modificar") {
                $this->modificar($dbPrefix, $projectId, $semana);
            } elseif ($opcion == "eliminar") {
                $this->eliminar($dbPrefix, $projectId, $semana);
            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($dbPrefix, $projectId, $semana);
            } elseif ($opcion == "cargarExcel") {
                $this->cargarExcel($dbPrefix, $projectId, $semana);
            } else {
                $this->jsonError('Opción no válida.');
            }
        } catch (Throwable $e) {
            error_log("Error in ListadoActividadesApiController@save: " . $e->getMessage());
            $this->jsonError('No se pudo procesar la solicitud del listado de actividades.', 500);
        }
    }

    public function autoGenerate(): void
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = $this->resolveMaxSemana($dbPrefix, $projectId);

            $this->requirePermission('lps.listado_actividades.editar', 'No autorizado para auto-generar el listado de actividades.');

            $preview = (isset($_GET['preview']) && $_GET['preview'] === '1')
                    || (isset($_POST['preview']) && $_POST['preview'] === '1');

            $activities = $this->loadProjectActivities($dbPrefix, $projectId, $semana);
            $matcher = new \App\Support\ActivityMatcher();
            $rules = $matcher->loadRules();

            // Cargar estrategia de agrupación del proyecto
            $estrategia = $this->loadProjectAgrupacionStrategy($projectId, $dbPrefix, $semana);

            $grupos = [];
            $sinMatch = 0;
            $sugerencias = [];
            $totalProcesadas = 0;

            // PASO 1-3: Match each PG activity y agrupar por familia
            foreach ($activities as $activity) {
                $totalProcesadas++;
                $consecutivoPrograma = (int) ($activity['unique_id'] ?? $activity['Consecutivo_en_Programa'] ?? 0);
                if ($consecutivoPrograma <= 0) {
                    $sinMatch++;
                    continue;
                }

                $match = $matcher->matchActivity($activity, $rules);
                if ($match === null) {
                    $sinMatch++;
                    $sugerencias[] = [
                        'consecutivoPrograma' => $consecutivoPrograma,
                        'actividad' => $activity['Actividad'] ?? '',
                        'fechaInicio' => $activity['Fecha_Inicio'] ?? '',
                        'match' => false,
                        'motivo' => 'Sin familia detectada',
                    ];
                    continue;
                }

                // Agrupar según estrategia
                $groupKey = $this->getAgrupacionKey($activity, $match, $estrategia);

                if (!isset($grupos[$groupKey])) {
                    $grupos[$groupKey] = [
                        'familiaId' => (int) $match['familia_id'],
                        'familiaNombre' => (string) ($match['familia_nombre'] ?? 'Sin nombre'),
                        'familiaCodigo' => (string) ($match['familia_codigo'] ?? ''),
                        'categoria' => (string) ($match['categoria'] ?? ''),
                        'confianzaMin' => (int) ($match['confianza'] ?? 0),
                        'actividades' => [],
                    ];
                }

                $grupos[$groupKey]['actividades'][] = [
                    'pg' => $activity,
                    'match' => $match,
                ];
                $grupos[$groupKey]['confianzaMin'] = min(
                    $grupos[$groupKey]['confianzaMin'],
                    (int) ($match['confianza'] ?? 0)
                );
            }

            // PASO 4-5: Para cada grupo, calcular ancla y crear 1 actividad consolidada
            $creadas = 0;
            $existentes = 0;
            $gruposCreadas = [];
            $gruposPreview = [];

            foreach ($grupos as $groupKey => $grupo) {
                if (empty($grupo['actividades'])) {
                    continue;
                }

                // Ordenar por Fecha_Inicio ASC para encontrar el ancla
                usort($grupo['actividades'], function ($a, $b) {
                    return strcmp(
                        $a['pg']['Fecha_Inicio'] ?? '9999-12-31',
                        $b['pg']['Fecha_Inicio'] ?? '9999-12-31'
                    );
                });

                $anchor = $grupo['actividades'][0]['pg'];
                $anchorConsecutivo = (int) ($anchor['unique_id'] ?? $anchor['Consecutivo_en_Programa'] ?? 0);
                $anchorFecha = $this->normalizeDate($anchor['Fecha_Inicio'] ?? null);

                // Verificar si ya existe una actividad para este grupo
                if ($this->activityExists($dbPrefix, $projectId, $semana, $anchorConsecutivo)) {
                    $existentes++;
                    $sugerencias[] = [
                        'grupoKey' => $groupKey,
                        'familia' => $grupo['familiaNombre'],
                        'familiaCodigo' => $grupo['familiaCodigo'],
                        'totalActividades' => count($grupo['actividades']),
                        'actividadInicio' => $anchorConsecutivo,
                        'yaExistia' => true,
                    ];
                    continue;
                }

                // Concatenar descripciones únicas de las PG activities del grupo
                $descripciones = [];
                foreach ($grupo['actividades'] as $item) {
                    $actText = strip_tags($item['pg']['Actividad'] ?? '');
                    $actText = trim(preg_replace('/\s*\[Capítulo:.*\]$/', '', $actText));
                    $actText = trim(rtrim($actText, ','));
                    if ($actText !== '' && !in_array($actText, $descripciones, true)) {
                        $descripciones[] = $actText;
                    }
                }
                $descripcionConsolidada = implode(', ', $descripciones);

                // === PREVIEW MODE: don't create, just collect for preview ===
                if ($preview) {
                    $gruposPreview[] = [
                        'grupoKey' => $groupKey,
                        'familia' => $grupo['familiaNombre'],
                        'familiaCodigo' => $grupo['familiaCodigo'],
                        'categoria' => $grupo['categoria'],
                        'totalActividades' => count($grupo['actividades']),
                        'actividadInicio' => $anchorConsecutivo,
                        'fechaInicio' => $anchorFecha,
                        'descripcion' => $descripcionConsolidada,
                        'confianzaMin' => $grupo['confianzaMin'],
                    ];
                    $creadas++;
                    $sugerencias[] = [
                        'grupoKey' => $groupKey,
                        'familia' => $grupo['familiaNombre'],
                        'familiaCodigo' => $grupo['familiaCodigo'],
                        'totalActividades' => count($grupo['actividades']),
                        'actividadInicio' => $anchorConsecutivo,
                        'fechaInicio' => $anchorFecha,
                        'confianzaMin' => $grupo['confianzaMin'],
                        'creada' => true,
                    ];
                    continue;
                }

                // === APPLY MODE: create in DB ===
                // Resolve intelligent tipoContrato from family contract options
                $familiaId = (int) ($grupo['familiaId'] ?? 0);
                $tipoContratoAuto = $this->resolveTipoContratoForFamily($familiaId);

                $created = $this->createGroupedActivityFromPg(
                    $dbPrefix,
                    $projectId,
                    $semana,
                    $grupo['familiaNombre'],
                    $descripcionConsolidada,
                    $anchorConsecutivo,
                    $anchorFecha,
                    $grupo,
                    $tipoContratoAuto
                );

                if ($created) {
                    $creadas++;
                    $gruposCreadas[] = [
                        'grupoKey' => $groupKey,
                        'familia' => $grupo['familiaNombre'],
                        'familiaCodigo' => $grupo['familiaCodigo'],
                        'totalActividades' => count($grupo['actividades']),
                        'actividadInicio' => $anchorConsecutivo,
                        'fechaInicio' => $anchorFecha,
                        'descripcion' => $descripcionConsolidada,
                        'confianzaMin' => $grupo['confianzaMin'],
                    ];
                    $sugerencias[] = [
                        'grupoKey' => $groupKey,
                        'familia' => $grupo['familiaNombre'],
                        'familiaCodigo' => $grupo['familiaCodigo'],
                        'totalActividades' => count($grupo['actividades']),
                        'actividadInicio' => $anchorConsecutivo,
                        'fechaInicio' => $anchorFecha,
                        'confianzaMin' => $grupo['confianzaMin'],
                        'creada' => true,
                    ];
                }
            }

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'preview' => $preview,
                'creadas' => $creadas,
                'existentes' => $existentes,
                'sinMatch' => $sinMatch,
                'totalProcesadas' => $totalProcesadas,
                'totalGrupos' => count($grupos),
                'estrategia' => $estrategia,
                'gruposCreadas' => $preview ? [] : $gruposCreadas,
                'gruposPreview' => $preview ? $gruposPreview : [],
                'sugerencias' => $sugerencias,
            ]);

        } catch (Throwable $e) {
            error_log("Error en ListadoActividadesApiController@autoGenerate: " . $e->getMessage());
            $this->jsonError('No se pudo auto-generar el listado de actividades.', 500);
        }
    }

    private function getAgrupacionKey(array $activity, array $match, string $estrategia): string
    {
        switch ($estrategia) {
            case 'categoria':
                return 'cat:' . ($match['categoria'] ?? 'OTROS');
            case 'capitulo':
                return 'cap:' . ($activity['__capitulo'] ?? 'OTROS');
            case 'familia':
            default:
                return 'fam:' . $match['familia_id'];
        }
    }

    private function loadProjectAgrupacionStrategy(int $projectId, string $dbPrefix, int $semana): string
    {
        try {
            $stmt = $this->db->query(
                "SELECT estrategia_agrupacion
                 FROM general_pdc_project_family_strategy
                 WHERE project_id = ? AND semana = ? AND estrategia_agrupacion IS NOT NULL
                 ORDER BY id ASC
                 LIMIT 1",
                [$projectId, $semana]
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['estrategia_agrupacion'])) {
                return $row['estrategia_agrupacion'];
            }
        } catch (Throwable $e) {
            error_log("Error cargando estrategia: " . $e->getMessage());
        }

        try {
            $stmt = $this->db->query(
                "SELECT estrategia_agrupacion
                 FROM general_pdc_project_family_strategy
                 WHERE db_prefix = ? AND semana = ? AND estrategia_agrupacion IS NOT NULL
                 ORDER BY id ASC
                 LIMIT 1",
                [$dbPrefix, $semana]
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['estrategia_agrupacion'])) {
                return $row['estrategia_agrupacion'];
            }
        } catch (Throwable $e) {
            error_log("Error cargando estrategia legacy: " . $e->getMessage());
        }

        return 'familia';
    }

    private function createGroupedActivityFromPg(
        string $dbPrefix,
        int $projectId,
        int $semana,
        string $actividadNombre,
        string $descripcion,
        int $actividadInicio,
        ?string $fechaInicio,
        array $grupo,
        string $tipoContrato = 'S'
    ): bool {
        try {
            if ($actividadInicio <= 0 || $fechaInicio === null) {
                return false;
            }

            $maxCode = $this->getNextCodigo($dbPrefix, $projectId);
            $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, $actividadInicio);

            $queryInsert = "INSERT INTO actividades
                            (project_id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$projectId, $maxCode, $actividadNombre, $descripcion, $actividadInicio, $nombreActividadInicio, $fechaInicio, $tipoContrato, $semana];
            $this->db->query($queryInsert, $params);

            $familiaNombre = $grupo['familiaNombre'] ?? 'N/A';
            $totalActividades = count($grupo['actividades'] ?? []);
            $this->db->logActivity(
                'ListadoActividades',
                'AUTO_CREAR_GRUPO',
                "Auto-creó actividad consolidada: $actividadNombre (familia: $familiaNombre, $totalActividades PG activities consolidadas)",
                $dbPrefix
            );
            return true;
        } catch (Throwable $e) {
            error_log("Error creando actividad consolidada: " . $e->getMessage());
            return false;
        }
    }

    private function resolveTipoContratoForFamily(int $familiaId): string
    {
        try {
            $query = "SELECT tipo_paquete FROM general_pdc_family_contract_options WHERE familia_id = ?";
            $stmt = $this->db->query($query, [$familiaId]);
            $options = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($options)) {
                return 'S';
            }

            $codes = [];
            foreach ($options as $tipoPaquete) {
                $paquete = mb_strtolower(trim($tipoPaquete));
                if (strpos($paquete, 'suministro e instalación') !== false || strpos($paquete, 'suministro e instalacion') !== false) {
                    $codes[] = 'SI';
                } elseif (strpos($paquete, 'mano de obra y suministro') !== false) {
                    $codes[] = 'MO';
                    $codes[] = 'S';
                } elseif (strpos($paquete, 'mano de obra') !== false) {
                    $codes[] = 'MO';
                } elseif (strpos($paquete, 'suministro') !== false) {
                    $codes[] = 'S';
                }
            }

            $codes = array_unique($codes);
            if (empty($codes)) {
                return 'S';
            }

            // SI is exclusive — if SI is present, don't combine with MO or S
            if (in_array('SI', $codes)) {
                return 'SI';
            }

            return implode(',', $codes);
        } catch (Throwable $e) {
            error_log("Error resolving tipoContrato for family $familiaId: " . $e->getMessage());
            return 'S';
        }
    }

    private function resolveMaxSemana(string $dbPrefix, int $projectId): int
    {
        $query = "SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?";
        $maxSemana = (int) $this->db->query($query, [$projectId])->fetchColumn();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['Max_Semana'] = $maxSemana;
            $_SESSION['semana'] = $maxSemana;
        }

        return $maxSemana;
    }

    private function registrar(string $dbPrefix, int $projectId, int $semana): void
    {
        $Actividad = !empty($_POST['actividad']) ? trim($_POST['actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
        $actividadInicio = !empty($_POST['actividadInicio']) ? $_POST['actividadInicio'] : null;

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($tipoContrato) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $queryCheck = "SELECT COUNT(*) FROM actividades WHERE project_id = ? AND actividad = ? LIMIT 1";
            $stmtCheck = $this->db->query($queryCheck, [$projectId, $Actividad]);
            if ($stmtCheck->fetchColumn() > 0) {
                $errores = 'La actividad que estás intentando registrar ya existe';
            }

            if (empty($errores)) {
                $codigo = $this->getNextCodigo($dbPrefix, $projectId);
                $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, (int) $actividadInicio);

                $queryInsert = "INSERT INTO actividades (project_id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [$projectId, $codigo, $Actividad, $descripcionActividad, $actividadInicio, $nombreActividadInicio, $fechaInicio, $tipoContrato, $semana];

                $stmtInsert = $this->db->query($queryInsert, $params);
                $this->db->logActivity('ListadoActividades', 'CREAR', "Creó actividad: $Actividad", $dbPrefix);

                $this->verificar_resultado(true, '');
                return;
            }
        }
        $this->verificar_resultado(false, $errores);
    }

    private function modificar(string $dbPrefix, int $projectId, int $semana): void
    {
        $Id = $_POST['Id'] ?? 0;
        $Actividad = !empty($_POST['Actividad']) ? trim($_POST['Actividad']) : (!empty($_POST['select_Actividad']) ? trim($_POST['select_Actividad']) : '');
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : (!empty($_POST['select_descripcionActividad']) ? trim($_POST['select_descripcionActividad']) : '');
        $fechaInicio = !empty($_POST['fechaInicio']) ? date("Y-m-d", strtotime($_POST["fechaInicio"])) : null;
        $tipoContrato = $_POST['tipoContrato'] ?? '';
        $actividadInicio = !empty($_POST['actividadInicio']) ? $_POST['actividadInicio'] : null;

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $beforeTrace = $this->loadContratosTraceAnchor($projectId, (int) $Id, $semana);
            $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, (int) $actividadInicio);
            $queryUpdate = "UPDATE actividades SET actividad=?, descripcionActividad=?, actividadInicio=?,
                             nombreActividadInicio=?,
                             fechaInicio=?, tipoContrato=COALESCE(NULLIF(?, ''), tipoContrato), semanaActualizacion=? WHERE project_id=? AND Id=? AND semanaActualizacion=?";
            $params = [$Actividad, $descripcionActividad, $actividadInicio, $nombreActividadInicio, $fechaInicio, $tipoContrato, $semana, $projectId, $Id, $semana];
            $stmtUpdate = $this->db->query($queryUpdate, $params);
            $this->db->logActivity('ListadoActividades', 'MODIFICAR', "Modificó actividad ID $Id", $dbPrefix);
            $afterTrace = $this->loadContratosTraceAnchor($projectId, (int) $Id, $semana);
            $this->recordListadoContratosTrace($projectId, (int) $Id, $semana, $beforeTrace, $afterTrace);

            $this->verificar_resultado(true, '');
            return;
        }
        $this->verificar_resultado(false, $errores);
    }

    private function eliminar(string $dbPrefix, int $projectId, int $semana): void
    {
        $Id = $_POST["Id"] ?? 0;
        $query = "DELETE FROM actividades WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?";
        $stmt = $this->db->query($query, [$projectId, $Id, $semana]);
        $this->db->logActivity('ListadoActividades', 'ELIMINAR', "Eliminó actividad ID $Id", $dbPrefix);
        $this->verificar_resultado(true, '');
    }

    private function actualizarFechaInicio(string $dbPrefix, int $projectId, int $semana): void
    {
        $Id = $_POST["idActividad"] ?? '';

        try {
            $query = "SELECT Fecha_Inicio FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND unique_id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$projectId, $semana, $Id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $this->jsonResponse(["data" => $data]);
            } else {
                $this->jsonResponse(["data" => ["Fecha_Inicio" => ""]]);
            }
        } catch (Throwable $t) {
            error_log("Error en ListadoActividadesApiController@actualizarFechaInicio: " . $t->getMessage());
            $this->jsonResponse(["data" => ["Fecha_Inicio" => ""]]);
        }
    }

    private function cargarExcel(string $dbPrefix, int $projectId, int $semanaActualizacion): void
    {
        $archivoExcel = $_FILES["archivoExcel"] ?? null;
        if (!$archivoExcel) {
            $this->verificar_resultado(false, "No se recibió archivo");
            return;
        }

        $info = new SplFileInfo($archivoExcel["name"]);
        $extension = $info->getExtension();

        if (strtolower($extension) == "csv") {
            $filename = $archivoExcel['tmp_name'];
            if (($handle = fopen($filename, "r")) !== false) {
                $error = false;
                try {
                    $this->db->beginTransaction();

                    $this->db->query(
                        "DELETE FROM actividades WHERE project_id = ? AND semanaActualizacion = ?",
                        [$projectId, $semanaActualizacion]
                    );

                    $numeroFila = 0;
                    while (($data = fgetcsv($handle, 10000, ";")) !== false) {
                        if ($numeroFila != 0) {
                            $actividad = $data[0] ?? '';
                            $descripcion = $data[1] ?? '';
                            $this->db->query(
                                "INSERT INTO actividades (project_id, codigo, actividad, descripcionActividad, semanaActualizacion) VALUES (?, ?, ?, ?, ?)",
                                [$projectId, $numeroFila, $actividad, $descripcion, $semanaActualizacion]
                            );
                        }
                        $numeroFila++;
                    }
                    $this->db->commit();
                    $this->db->logActivity('ListadoActividades', 'IMPORTAR', "Importó actividades desde Excel", $dbPrefix);
                    fclose($handle);
                } catch (Exception $e) {
                    $this->db->rollBack();
                    $error = true;
                    error_log("Excel Import Error: " . $e->getMessage());
                }

                if ($error) {
                    $this->verificar_resultado(false, "No carga desde excel");
                } else {
                    $this->verificar_resultado(true, "");
                }

            } else {
                $this->verificar_resultado(false, "Error al abrir archivo");
            }
        } else {
            $this->verificar_resultado(false, "Formato invalido (debe ser .csv)");
        }
    }

    private function activityExists(string $dbPrefix, int $projectId, int $semana, int $consecutivoPrograma): bool
    {
        $stmt = $this->db->query(
            "SELECT Id FROM actividades WHERE project_id = ? AND actividadInicio = ? AND semanaActualizacion = ? LIMIT 1",
            [$projectId, $consecutivoPrograma, $semana]
        );
        return $stmt->fetchColumn() !== false;
    }

    private function createActivityFromPg(string $dbPrefix, int $projectId, int $semana, array $pgActivity, array $match): bool
    {
        $consecutivoPrograma = (int) ($pgActivity['unique_id'] ?? $pgActivity['Consecutivo_en_Programa'] ?? 0);
        $actividad = trim((string) ($pgActivity['Actividad'] ?? ''));
        $fechaInicio = $this->normalizeDate($pgActivity['Fecha_Inicio'] ?? null);

        if ($consecutivoPrograma <= 0 || $actividad === '' || $fechaInicio === null) {
            return false;
        }

        try {
            $maxCode = $this->getNextCodigo($dbPrefix, $projectId);
            $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, $consecutivoPrograma);

            $queryInsert = "INSERT INTO actividades
                            (project_id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion)
                            VALUES (?, ?, ?, '', ?, ?, ?, NULL, ?)";
            $params = [$projectId, $maxCode, $actividad, $consecutivoPrograma, $nombreActividadInicio, $fechaInicio, $semana];
            $this->db->query($queryInsert, $params);

            $familiaNombre = $match['familia_nombre'] ?? 'N/A';
            $this->db->logActivity('ListadoActividades', 'AUTO_CREAR', "Auto-creó actividad desde PG: $actividad (familia: $familiaNombre)", $dbPrefix);
            return true;
        } catch (Throwable $e) {
            error_log("Error creando actividad desde PG: " . $e->getMessage());
            return false;
        }
    }

    private function loadProjectActivities(string $dbPrefix, int $projectId, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT row_id AS Consecutivo,
                    unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND COALESCE(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(Actividad, '<[^>]+>', ''), '&nbsp;', ' ')), '') <> ''
             ORDER BY unique_id ASC, row_id ASC",
            [$projectId, $semana]
        );

        $leaves = [];
        $currentChapter = '';
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((int) $row['Titulo'] !== 0) {
                $chapterName = $this->extractLeafName($this->normalizeActivityText((string) $row['Actividad']));
                if ($chapterName !== '') {
                    $currentChapter = $chapterName;
                }
                continue;
            }
            $row['__capitulo'] = $currentChapter;
            $leaves[] = $row;
        }
        return $leaves;
    }

    private function getNextCodigo(string $dbPrefix, int $projectId): int
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(MAX(codigo), 0) + 1 FROM actividades WHERE project_id = ?",
            [$projectId]
        );
        return (int) $stmt->fetchColumn();
    }

    private function programDisplayName(string $dbPrefix, int $projectId, int $semana, int $actividadInicio): ?string
    {
        if ($actividadInicio <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT CONCAT(Id, '. ', Actividad, ' (Inicia en: ', Fecha_Inicio, ')')
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id = ?
             LIMIT 1",
            [$projectId, $semana, $actividadInicio]
        );

        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    private function normalizeDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d', $timestamp);
    }

    private function normalizeActivityText(string $raw): string
    {
        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = mb_strtoupper($text, 'UTF-8');
        $text = $this->removeAccents($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function removeAccents(string $text): string
    {
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator !== null) {
                $result = $transliterator->transliterate($text);
                if ($result !== false) {
                    return $result;
                }
            }
        }
        return strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        ]);
    }

    private function extractLeafName(string $normalized): string
    {
        $pos = mb_strpos($normalized, '[CAPITULO:');
        $leaf = $pos === false ? $normalized : mb_substr($normalized, 0, $pos);
        return trim(rtrim(trim($leaf), ','));
    }

    private function loadContratosTraceAnchor(int $projectId, int $activityId, int $week): array
    {
        try {
            $row = $this->db->query(
                "SELECT actividadInicio, fechaInicio, tipoContrato
                 FROM actividades
                 WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?
                 LIMIT 1",
                [$projectId, $activityId, $week]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: [];
        } catch (Throwable $e) {
            error_log('No se pudo cargar trazabilidad de Contratos desde Listado: ' . $e->getMessage());
            return [];
        }
    }

    private function recordListadoContratosTrace(int $projectId, int $activityId, int $week, array $before, array $after): void
    {
        if ($before === [] || $after === []) {
            return;
        }
        if (trim((string) ($before['tipoContrato'] ?? $after['tipoContrato'] ?? '')) === '') {
            return;
        }

        $changed = [];
        foreach (['actividadInicio', 'fechaInicio', 'tipoContrato'] as $field) {
            if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
                $changed[] = $field;
            }
        }
        if ($changed === []) {
            return;
        }

        try {
            $user = $_SESSION['usuario'] ?? $_SESSION['nombreUsuario'] ?? $_SESSION['user'] ?? null;
            $this->db->query(
                "INSERT INTO contratos_trazabilidad
                    (project_id, actividad_id, semana, usuario, origen, campos_cambiados, antes, despues)
                 VALUES (?, ?, ?, ?, 'listado', ?, ?, ?)",
                [
                    $projectId,
                    $activityId,
                    $week,
                    $user,
                    json_encode($changed, JSON_UNESCAPED_UNICODE),
                    json_encode($before, JSON_UNESCAPED_UNICODE),
                    json_encode($after, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (Throwable $e) {
            error_log('No se pudo registrar trazabilidad de Contratos desde Listado: ' . $e->getMessage());
        }
    }

    private function verificar_resultado(bool $success, string $errores): void
    {
        $informacion = [];
        $informacion["respuesta"] = $success ? "BIEN" : "ERROR";

        if ($errores == 'Debe rellenar todos los campos') {
            $informacion["respuesta"] = "VACIO";
        } elseif ($errores == 'La actividad que estás intentando registrar ya existe') {
            $informacion["respuesta"] = "EXISTE";
        } elseif (!empty($errores) && $informacion["respuesta"] == "ERROR") {
            $informacion["mensaje"] = $errores;
        }

        $this->jsonResponse($informacion);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400, array $extra = []): void
    {
        http_response_code($httpCode);
        $this->jsonResponse(array_merge([
            'respuesta' => 'ERROR',
            'mensaje' => $message,
        ], $extra));
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }
}
