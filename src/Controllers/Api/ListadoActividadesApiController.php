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
            $semana = (int) $context['semana'];

            $this->requirePermission('lps.listado_actividades.ver', 'No autorizado para consultar el listado de actividades.');

            $query = "SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?";
            $stmt = $this->db->query($query, [$projectId, $semana]);
            $conteo = $stmt->fetchColumn();

            $response = ["data" => []];

            if ($conteo > 0) {
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
                                    SELECT CONCAT(pc.Id, '. ', pc.Actividad, ' (Inicia el: ', pc.Fecha_Inicio, ')')
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
                foreach ($response["data"] as &$row) {
                    $row['nombreActividadInicio'] = $this->safePlainText($row['nombreActividadInicio'] ?? '');
                }
                unset($row);
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
            $semana = (int) $context['semana'];

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

    public function updateCell(): void
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = (int) $context['semana'];

            $this->requirePermission('lps.listado_actividades.editar', 'No autorizado para modificar el listado de actividades.');

            $id = (int) ($_POST['id'] ?? 0);
            $prop = trim($_POST['prop'] ?? '');
            $value = $_POST['value'] ?? '';

            if ($id <= 0 || $prop === '') {
                $this->jsonError('Parámetros inválidos: id y prop son requeridos.');
                return;
            }

            // Whitelist de columnas editables
            $editableProps = ['codigo', 'descripcionActividad', 'actividadInicio', 'fechaInicio', 'tipoContrato'];
            if (!in_array($prop, $editableProps, true)) {
                $this->jsonError("La columna '{$prop}' no es editable.");
                return;
            }

            // Sanitizar según tipo de columna
            if ($prop === 'fechaInicio') {
                $value = !empty($value) ? date('Y-m-d', strtotime((string) $value)) : null;
            } elseif ($prop === 'codigo') {
                $value = filter_var(trim((string) $value), FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($value === false) {
                    $this->jsonError('El código debe ser un número entero positivo.', 422);
                    return;
                }
            } elseif ($prop === 'descripcionActividad') {
                $value = trim((string) $value);
            } elseif ($prop === 'tipoContrato') {
                $value = $this->normalizeTipoContrato((string) $value);
                if ($value === '') {
                    $this->jsonError('Debe seleccionar una modalidad de contratación válida.');
                    return;
                }
            } elseif ($prop === 'actividadInicio') {
                $value = (int) $value;
                if ($value <= 0) {
                    $this->jsonError('Debe seleccionar una actividad válida del cronograma.');
                    return;
                }
            }

            $beforeTrace = $this->loadContratosTraceAnchor($projectId, $id, $semana);

            if ($prop === 'actividadInicio') {
                $fechaInicio = $this->loadProgramStartDate($projectId, $semana, (int) $value);
                $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, (int) $value);
                if ($fechaInicio === null || $nombreActividadInicio === null) {
                    $this->jsonError('No se encontró la actividad seleccionada en el cronograma.');
                    return;
                }

                $query = "UPDATE actividades
                          SET actividadInicio = ?, nombreActividadInicio = ?, fechaInicio = ?, semanaActualizacion = ?
                          WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?";
                $stmt = $this->db->query($query, [$value, $nombreActividadInicio, $fechaInicio, $semana, $projectId, $id, $semana]);
            } else {
                $query = "UPDATE actividades SET {$prop} = ?, semanaActualizacion = ? WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?";
                $stmt = $this->db->query($query, [$value, $semana, $projectId, $id, $semana]);
            }

            if ($stmt->rowCount() === 0 && !$this->listedActivityExists($projectId, $id, $semana)) {
                $this->jsonError('El registro cambió o ya no existe. Recarga la tabla e intenta nuevamente.', 409);
                return;
            }

            $this->db->logActivity('ListadoActividades', 'MODIFICAR_CELDA', "Modificó celda {$prop} de actividad ID {$id}", $dbPrefix);

            // Trazabilidad hacia contratos si aplica
            $afterTrace = $this->loadContratosTraceAnchor($projectId, $id, $semana);
            $this->recordListadoContratosTrace($projectId, $id, $semana, $beforeTrace, $afterTrace);

            $response = ['respuesta' => 'BIEN', 'campo' => $prop, 'valor' => $value];
            if ($prop === 'actividadInicio') {
                $response['nombreActividadInicio'] = $nombreActividadInicio ?? '';
                $response['fechaInicio'] = $fechaInicio ?? '';
            }
            $this->jsonResponse($response);

        } catch (Throwable $e) {
            error_log("Error en ListadoActividadesApiController@updateCell: " . $e->getMessage());
            $this->jsonError('No se pudo actualizar la celda.', 500);
        }
    }

    public function autoGenerate(): void
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = (int) $context['semana'];

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

    public function updateCard(): void
    {
        $ownsTransaction = false;
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $projectId = (int) $context['projectId'];
            $semana = (int) $context['semana'];
            $this->requirePermission('lps.listado_actividades.editar', 'No autorizado para modificar el listado de actividades.');

            $id = (int) ($_POST['id'] ?? 0);
            $actividadInicio = (int) ($_POST['actividadInicio'] ?? 0);
            $tipoContrato = $this->normalizeTipoContrato((string) ($_POST['tipoContrato'] ?? ''));

            if ($id <= 0 || $actividadInicio <= 0 || $tipoContrato === '') {
                $this->jsonError('Debe seleccionar una actividad y una modalidad válidas.');
                return;
            }

            $fechaInicio = $this->loadProgramStartDate($projectId, $semana, $actividadInicio);
            $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, $actividadInicio);
            if ($fechaInicio === null || $nombreActividadInicio === null) {
                $this->jsonError('No se encontró la actividad seleccionada en el cronograma.');
                return;
            }

            $beforeTrace = $this->loadContratosTraceAnchor($projectId, $id, $semana);
            $ownsTransaction = !$this->db->inTransaction();
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }
            $stmt = $this->db->query(
                "UPDATE actividades
                 SET actividadInicio = ?, nombreActividadInicio = ?, fechaInicio = ?, tipoContrato = ?
                 WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?",
                [$actividadInicio, $nombreActividadInicio, $fechaInicio, $tipoContrato, $projectId, $id, $semana]
            );
            if ($stmt->rowCount() === 0 && !$this->listedActivityExists($projectId, $id, $semana)) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                $this->jsonError('El registro cambió o ya no existe. Recarga la tabla e intenta nuevamente.', 409);
                return;
            }

            $afterTrace = $this->loadContratosTraceAnchor($projectId, $id, $semana);
            $this->recordListadoContratosTrace($projectId, $id, $semana, $beforeTrace, $afterTrace);
            $this->db->logActivity('ListadoActividades', 'MODIFICAR_TARJETA', "Modificó tarjeta de actividad ID {$id}", $dbPrefix);
            if ($ownsTransaction) {
                $this->db->commit();
            }
            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'actividadInicio' => $actividadInicio,
                'nombreActividadInicio' => $nombreActividadInicio,
                'fechaInicio' => $fechaInicio,
                'tipoContrato' => $tipoContrato,
            ]);
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en ListadoActividadesApiController@updateCard: " . $e->getMessage());
            $this->jsonError('No se pudo guardar la tarjeta.', 500);
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

    private function registrar(string $dbPrefix, int $projectId, int $semana): void
    {
        $Actividad = !empty($_POST['actividad']) ? trim($_POST['actividad']) : '';
        $descripcionActividad = !empty($_POST['descripcionActividad']) ? trim($_POST['descripcionActividad']) : '';
        $tipoContrato = $this->normalizeTipoContrato((string) ($_POST['tipoContrato'] ?? ''));
        $actividadInicio = !empty($_POST['actividadInicio']) ? (int) $_POST['actividadInicio'] : null;
        $fechaInicio = $actividadInicio === null
            ? null
            : $this->loadProgramStartDate($projectId, $semana, $actividadInicio);

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
        $actividadInicio = !empty($_POST['actividadInicio']) ? (int) $_POST['actividadInicio'] : 0;
        $fechaInicio = $this->loadProgramStartDate($projectId, $semana, $actividadInicio);
        $tipoContrato = $this->normalizeTipoContrato((string) ($_POST['tipoContrato'] ?? ''));

        $errores = '';
        if (empty($Actividad) || empty($descripcionActividad) || empty($fechaInicio) || empty($tipoContrato) || empty($semana)) {
            $errores = 'Debe rellenar todos los campos';
        } else {
            $beforeTrace = $this->loadContratosTraceAnchor($projectId, (int) $Id, $semana);
            $nombreActividadInicio = $this->programDisplayName($dbPrefix, $projectId, $semana, (int) $actividadInicio);
            if ($nombreActividadInicio === null) {
                $this->verificar_resultado(false, 'No se encontró la actividad seleccionada en el cronograma.');
                return;
            }
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
        if ($stmt->rowCount() === 0) {
            $this->verificar_resultado(false, 'El registro cambió o ya no existe. Recarga la tabla e intenta nuevamente.');
            return;
        }
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

    private function loadProgramStartDate(int $projectId, int $semana, int $actividadInicio): ?string
    {
        if ($actividadInicio <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT Fecha_Inicio
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id = ?
             LIMIT 1",
            [$projectId, $semana, $actividadInicio]
        );

        $value = $stmt->fetchColumn();
        return $value === false ? null : $this->normalizeDate($value);
    }

    private function cargarExcel(string $dbPrefix, int $projectId, int $semanaActualizacion): void
    {
        $archivoExcel = $_FILES["archivoExcel"] ?? null;
        try {
            $rows = $this->readValidatedCsvRows($archivoExcel);
            $this->db->beginTransaction();
            $this->db->query(
                "DELETE FROM actividades WHERE project_id = ? AND semanaActualizacion = ?",
                [$projectId, $semanaActualizacion]
            );
            foreach ($rows as $index => $row) {
                $this->db->query(
                    "INSERT INTO actividades (project_id, codigo, actividad, descripcionActividad, semanaActualizacion) VALUES (?, ?, ?, ?, ?)",
                    [$projectId, $index + 1, $row[0], $row[1], $semanaActualizacion]
                );
            }
            $this->db->commit();
            $this->db->logActivity('ListadoActividades', 'IMPORTAR', "Importó actividades desde Excel", $dbPrefix);
            $this->verificar_resultado(true, "");
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->verificar_resultado(false, $e->getMessage());
        }
    }

    private function readValidatedCsvRows(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('No se recibió un archivo válido.');
        }
        $extension = strtolower((new SplFileInfo((string) ($file['name'] ?? '')))->getExtension());
        $size = (int) ($file['size'] ?? 0);
        if ($extension !== 'csv' || $size <= 0 || $size > 5 * 1024 * 1024) {
            throw new Exception('El archivo debe ser CSV, contener datos y no superar 5 MB.');
        }
        $handle = fopen((string) ($file['tmp_name'] ?? ''), 'r');
        if ($handle === false) throw new Exception('No fue posible abrir el archivo CSV.');

        $header = fgetcsv($handle, 10000, ';');
        $header = array_map(static fn($value) => trim((string) $value, "\xEF\xBB\xBF \t\r\n"), $header ?: []);
        if ($header !== ['actividad', 'descripcionActividad']) {
            fclose($handle);
            throw new Exception('El CSV debe usar las columnas actividad y descripcionActividad.');
        }
        $rows = [];
        while (($data = fgetcsv($handle, 10000, ';')) !== false) {
            $activity = trim((string) ($data[0] ?? ''));
            $description = trim((string) ($data[1] ?? ''));
            if ($activity === '' && $description === '') continue;
            if (count($data) !== 2 || $activity === '' || $description === '') {
                fclose($handle);
                throw new Exception('Cada fila debe tener actividad y descripción.');
            }
            $rows[] = [$activity, $description];
        }
        fclose($handle);
        if ($rows === []) throw new Exception('El CSV no contiene familias para importar.');
        return $rows;
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

    private function normalizeTipoContrato(string $value): string
    {
        $selected = [];
        foreach (explode(',', strtoupper($value)) as $code) {
            $code = trim($code);
            if ($code === '') {
                continue;
            }
            if (!in_array($code, ['MO', 'S', 'SI', 'OC'], true)) {
                return '';
            }
            $selected[$code] = true;
        }
        if (isset($selected['SI'])) {
            return 'SI';
        }
        return implode(',', array_values(array_filter(
            ['MO', 'S', 'OC'],
            static fn(string $code): bool => isset($selected[$code]),
        )));
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

    private function listedActivityExists(int $projectId, int $activityId, int $week): bool
    {
        return (bool) $this->db->query(
            'SELECT 1 FROM actividades WHERE project_id = ? AND Id = ? AND semanaActualizacion = ? LIMIT 1',
            [$projectId, $activityId, $week]
        )->fetchColumn();
    }

    private function safePlainText($value): string
    {
        $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = strip_tags($decoded);
        return trim((string) preg_replace('/\s+/u', ' ', $plain));
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
        // @phpstan-ignore function.notFound (definida por el guard legacy cargado arriba)
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }
}
