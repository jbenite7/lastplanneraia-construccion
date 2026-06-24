<?php

namespace App\Controllers\Api;

use App\Support\ModuleRequestContext;
use PDO;
use Throwable;

class PdcAutoGenerateController
{
    private const AUTO_CONFIDENCE_THRESHOLD = 70;

    private const PACKAGE_TYPE_LABELS = [
        'SI' => 'Suministro e Instalación',
        'S' => 'Suministro',
        'MO' => 'Mano de Obra',
        'MO+S' => 'Mano de Obra y Suministro por separado',
    ];

    private $db;

    private ?\App\Support\ActivityMatcher $activityMatcher = null;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    private function getActivityMatcher(): \App\Support\ActivityMatcher
    {
        if ($this->activityMatcher === null) {
            $this->activityMatcher = new \App\Support\ActivityMatcher();
        }
        return $this->activityMatcher;
    }

    public function suggest(): void
    {
        try {
            $this->requirePermission('lps.pdc.auto_generar', 'No autorizado para auto-generar el plan de compras.');

            $context = $this->resolveContext();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            if (!$this->projectTableExists("{$dbPrefix}_programa_consolidado")) {
                $this->jsonError('No existe el programa general del proyecto.', 404);
                return;
            }

            $activities = $this->loadProjectActivities($dbPrefix, $semana);
            $rules = $this->loadRules();
            $optionsByFamily = $this->loadOptionsByFamily();
            $strategies = $this->loadProjectStrategies($dbPrefix, $semana);
            $groups = [];
            $manualReview = [];

            foreach ($activities as $activity) {
                $match = $this->getActivityMatcher()->matchActivity($activity, $rules);
                if ($match === null) {
                    $manualReview[] = $this->manualActivity($activity, 'Sin regla de mapeo confiable.');
                    continue;
                }

                $familyId = (int) $match['familia_id'];
                if (!isset($groups[$familyId])) {
                    $groups[$familyId] = $this->newSuggestionGroup($match, $optionsByFamily[$familyId] ?? []);
                }

                $groups[$familyId]['actividades'][] = $this->formatActivity($activity, $match);
                $groups[$familyId]['fechaInicio'] = $this->minDate($groups[$familyId]['fechaInicio'], $activity['Fecha_Inicio'] ?? null);
                $groups[$familyId]['confianza'] = min($groups[$familyId]['confianza'], (int) $match['confianza']);

                if ($match['reviewRequired']) {
                    $groups[$familyId]['requiereRevision'] = true;
                    $groups[$familyId]['motivosRevision'][] = $match['reviewReason'];
                }
            }

            $suggestions = [];
            foreach ($groups as $familyId => $group) {
                $strategyOptionId = $strategies[$familyId] ?? null;
                $selectedOptionId = $this->selectOptionId($group['opciones'], $group['modalidadSugerida'], $strategyOptionId);
                $group['optionId'] = $selectedOptionId;
                $group['selected'] = !$group['requiereRevision'] && $selectedOptionId !== null;
                $group['motivosRevision'] = array_values(array_unique(array_filter($group['motivosRevision'])));

                if ($selectedOptionId === null) {
                    $group['requiereRevision'] = true;
                    $group['selected'] = false;
                    $group['motivosRevision'][] = 'La familia no tiene una opción de contrato configurada.';
                }

                $suggestions[] = $group;
            }

            usort($suggestions, static fn($a, $b) => strcmp($a['familiaCodigo'], $b['familiaCodigo']));

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'data' => [
                    'dbPrefix' => $dbPrefix,
                    'semana' => $semana,
                    'totalActividades' => count($activities),
                    'familiasSugeridas' => count($suggestions),
                    'autoSeleccionadas' => count(array_filter($suggestions, static fn($item) => !empty($item['selected']))),
                    'requierenRevision' => count(array_filter($suggestions, static fn($item) => !empty($item['requiereRevision']))),
                    'sinMapeo' => count($manualReview),
                    'suggestions' => $suggestions,
                    'manualReview' => $manualReview,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('Error en PdcAutoGenerateController@suggest: ' . $e->getMessage());
            $this->jsonError('No se pudieron generar sugerencias para el plan de compras.', 500);
        }
    }

    public function inventory(): void
    {
        try {
            $this->requirePermission('lps.pdc.ver', 'No autorizado para consultar el inventario PDC.');

            $families = $this->loadOptionsByFamily();

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'data' => array_values($families),
            ]);
        } catch (Throwable $e) {
            error_log('Error en PdcAutoGenerateController@inventory: ' . $e->getMessage());
            $this->jsonError('No se pudo cargar el inventario de mapeo PDC.', 500);
        }
    }

    public function apply(): void
    {
        try {
            $this->requirePermission('lps.pdc.auto_generar', 'No autorizado para auto-generar el plan de compras.');
            $this->requirePermission('lps.pdc.editar', 'No autorizado para modificar el plan de compras.');

            $context = $this->resolveContext();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            if (!$this->projectTableExists("{$dbPrefix}_pdc")) {
                $this->jsonError('No existe la tabla PDC del proyecto.', 404);
                return;
            }

            $payload = $this->requestPayload();
            $items = $payload['suggestions'] ?? $payload['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                $this->jsonError('No se recibieron sugerencias para aplicar.');
                return;
            }

            $this->db->beginTransaction();

            $inserted = 0;
            $skipped = 0;
            $created = [];
            $writeBacks = 0;
            $allConflicts = [];

            foreach ($items as $suggestion) {
                if (isset($suggestion['selected']) && !$suggestion['selected']) {
                    $skipped++;
                    continue;
                }

                $optionId = (int) ($suggestion['optionId'] ?? 0);
                $familyId = (int) ($suggestion['familiaId'] ?? $suggestion['familyId'] ?? 0);
                if ($optionId <= 0 || $familyId <= 0) {
                    $skipped++;
                    continue;
                }

                $option = $this->loadOption($optionId);
                if ($option === null) {
                    $skipped++;
                    continue;
                }

                $fechaInicio = $this->normalizeDate($suggestion['fechaInicio'] ?? null);
                $contratos = trim((string) ($suggestion['familiaNombre'] ?? $option['familia_nombre'] ?? ''));
                if ($contratos === '') {
                    $contratos = $option['familia_codigo'];
                }

                foreach ($option['items'] as $optionItem) {
                    $tipoPaquete = $this->tipoPaqueteLabel($optionItem['tipo_paquete'] ?? $option['tipo_paquete']);
                    $paquete = trim((string) $optionItem['paquete_nombre']);
                    if ($tipoPaquete === '' || $paquete === '') {
                        $skipped++;
                        continue;
                    }

                    if ($this->pdcPackageExists($dbPrefix, $semana, $tipoPaquete, $paquete)) {
                        $skipped++;
                        continue;
                    }

                    $this->ensureTitleRow($dbPrefix, $semana, $tipoPaquete);
                    $durations = $this->resolveDurations($option, $optionItem);
                    $dates = $this->calculateProcessDates($fechaInicio, $durations);
                    $subcontractIndex = $this->nextSubcontractIndex($dbPrefix, $semana, $tipoPaquete);

                    $this->db->query(
                        "INSERT INTO {$dbPrefix}_pdc (
                            semana, titulo, tipoPaquete, paqueteContratacion, contratos,
                            numeroSubcontratos, subcontratoPaquete, estado,
                            fechaElaboracionPliegos, diasElaboracionPliegos,
                            fechaEntregaPliegos, diasEntregaPliegos,
                            fechaReciboPropuestas, diasReciboPropuestas,
                            fechaCuadrosComparativos, diasCuadrosComparativos,
                            fechaLegalizacionContrato, diasLegalizacionContrato,
                            fechaFabricacion, diasFabricacion,
                            fechaInsumosObra, diasInsumosObra,
                            fechaInicio, fechaInicioProyectada
                        ) VALUES (?, 0, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $semana,
                            $tipoPaquete,
                            $paquete,
                            $contratos,
                            $subcontractIndex,
                            'En Curso; Proceso de contratación no iniciado',
                            $dates['fechaElaboracionPliegos'],
                            $durations['dias_elaboracion'],
                            $dates['fechaEntregaPliegos'],
                            $durations['dias_entrega'],
                            $dates['fechaReciboPropuestas'],
                            $durations['dias_recibo'],
                            $dates['fechaCuadrosComparativos'],
                            $durations['dias_cuadros'],
                            $dates['fechaLegalizacionContrato'],
                            $durations['dias_legalizacion'],
                            $dates['fechaFabricacion'],
                            $durations['dias_fabricacion'],
                            $dates['fechaInsumosObra'],
                            $durations['dias_insumos'],
                            $fechaInicio,
                            $fechaInicio,
                        ],
                    );

                    $inserted++;
                    $created[] = [
                        'tipoPaquete' => $tipoPaquete,
                        'paqueteContratacion' => $paquete,
                        'consecutivo' => (int) $this->db->lastInsertId(),
                    ];
                }

                $this->saveProjectStrategy($dbPrefix, $semana, $familyId, $optionId);

                $wbResult = $this->writeBackToActividades($dbPrefix, $semana, $suggestion, $option);
                $writeBacks += $wbResult['escritas'];
                if (!empty($wbResult['conflictos'])) {
                    $allConflicts = array_merge($allConflicts, $wbResult['conflictos']);
                }
            }

            $this->db->commit();
            $this->db->logActivity('PDC', 'AUTO_GENERAR', "Auto-generó $inserted paquetes PDC, write-back $writeBacks actividades" . ($allConflicts ? ', ' . count($allConflicts) . ' conflictos' : ''), $dbPrefix);

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'insertados' => $inserted,
                'omitidos' => $skipped,
                'created' => $created,
                'writeBacks' => $writeBacks,
                'conflictos' => $allConflicts,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error en PdcAutoGenerateController@apply: ' . $e->getMessage());
            $this->jsonError('No se pudo aplicar la auto-generación del plan de compras.', 500);
        }
    }

    public function applyFromActividades(): void
    {
        try {
            $this->requirePermission('lps.pdc.auto_generar', 'No autorizado para auto-generar el plan de compras.');
            $this->requirePermission('lps.pdc.editar', 'No autorizado para modificar el plan de compras.');

            $context = $this->resolveContext();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            if (!$this->projectTableExists("{$dbPrefix}_pdc")) {
                $this->jsonError('No existe la tabla PDC del proyecto.', 404);
                return;
            }

            $activities = $this->loadActividadesWithPackages($dbPrefix, $semana);
            if (empty($activities)) {
                $this->jsonResponse([
                    'respuesta' => 'BIEN',
                    'mensaje' => 'No hay actividades con paquetes asignados para generar PDC.',
                    'paquetesCreados' => 0,
                    'paquetesActualizados' => 0,
                    'paquetesExistentes' => 0,
                ]);
                return;
            }

            $existingPdc = $this->loadExistingPdcPackages($dbPrefix, $semana);
            $prefixMap = array_flip(self::PACKAGE_TYPE_LABELS);

            $this->db->beginTransaction();

            $paquetesCreados = 0;
            $paquetesActualizados = 0;
            $paquetesExistentes = 0;
            $paquetesOmitidos = 0;

            foreach ($activities as $activity) {
                $fechaInicio = $this->normalizeDate($activity['fechaInicio'] ?? null);
                $actividadNombre = trim((string) ($activity['actividad'] ?? ''));

                foreach (['SI', 'S', 'MO'] as $prefix) {
                    for ($i = 1; $i <= 5; $i++) {
                        $paqueteNombre = trim((string) ($activity["paquete{$prefix}{$i}"] ?? ''));
                        if ($paqueteNombre === '') {
                            continue;
                        }

                        $tipoPaquete = self::PACKAGE_TYPE_LABELS[$prefix] ?? null;
                        if ($tipoPaquete === null) {
                            continue;
                        }

                        $existingKey = mb_strtolower($tipoPaquete . '|' . $paqueteNombre);
                        if (isset($existingPdc[$existingKey])) {
                            $pdcRow = $existingPdc[$existingKey];
                            $this->updateExistingPdcDates($dbPrefix, (int) $pdcRow['consecutivo'], $fechaInicio, $semana);
                            $paquetesActualizados++;
                            continue;
                        }

                        $familyMatch = $this->matchActivityForPdc($activity, $actividadNombre);
                        $contratos = $familyMatch ? ($familyMatch['familia_nombre'] ?? $actividadNombre) : $actividadNombre;

                        $option = $familyMatch ? $this->findOptionForTipoPaquete($familyMatch['familia_id'], $tipoPaquete) : null;
                        $durations = $option ? $this->resolveDurations($option, ['tipo_paquete' => $tipoPaquete, 'paquete_nombre' => $paqueteNombre, 'dias_reales' => null]) : $this->defaultDurations();

                        $dates = $this->calculateProcessDates($fechaInicio, $durations);
                        $this->ensureTitleRow($dbPrefix, $semana, $tipoPaquete);
                        $subcontractIndex = $this->nextSubcontractIndex($dbPrefix, $semana, $tipoPaquete);

                        $this->db->query(
                            "INSERT INTO {$dbPrefix}_pdc (
                                semana, titulo, tipoPaquete, paqueteContratacion, contratos,
                                numeroSubcontratos, subcontratoPaquete, estado,
                                fechaElaboracionPliegos, diasElaboracionPliegos,
                                fechaEntregaPliegos, diasEntregaPliegos,
                                fechaReciboPropuestas, diasReciboPropuestas,
                                fechaCuadrosComparativos, diasCuadrosComparativos,
                                fechaLegalizacionContrato, diasLegalizacionContrato,
                                fechaFabricacion, diasFabricacion,
                                fechaInsumosObra, diasInsumosObra,
                                fechaInicio, fechaInicioProyectada
                            ) VALUES (?, 0, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $semana,
                                $tipoPaquete,
                                $paqueteNombre,
                                $contratos,
                                $subcontractIndex,
                                'En Curso; Proceso de contratación no iniciado',
                                $dates['fechaElaboracionPliegos'],
                                $durations['dias_elaboracion'],
                                $dates['fechaEntregaPliegos'],
                                $durations['dias_entrega'],
                                $dates['fechaReciboPropuestas'],
                                $durations['dias_recibo'],
                                $dates['fechaCuadrosComparativos'],
                                $durations['dias_cuadros'],
                                $dates['fechaLegalizacionContrato'],
                                $durations['dias_legalizacion'],
                                $dates['fechaFabricacion'],
                                $durations['dias_fabricacion'],
                                $dates['fechaInsumosObra'],
                                $durations['dias_insumos'],
                                $fechaInicio,
                                $fechaInicio,
                            ]
                        );
                        $paquetesCreados++;
                    }
                }
            }

            $this->db->commit();
            $this->db->logActivity('PDC', 'AUTO_GENERAR_FROM_ACTIVIDADES', "Desde _actividades: {$paquetesCreados} creados, {$paquetesActualizados} actualizados", $dbPrefix);

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'paquetesCreados' => $paquetesCreados,
                'paquetesActualizados' => $paquetesActualizados,
                'paquetesExistentes' => $paquetesExistentes,
                'paquetesOmitidos' => $paquetesOmitidos,
            ]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error en PdcAutoGenerateController@applyFromActividades: ' . $e->getMessage());
            $this->jsonError('No se pudo generar el plan de compras desde actividades.', 500);
        }
    }

    private function resolveContext(): array
    {
        $context = ModuleRequestContext::resolve();
        $dbPrefix = $context['dbPrefix'];
        $semana = (int) $context['semana'];

        if ($semana <= 0) {
            $semana = $this->resolveMaxSemana($dbPrefix);
        }

        return [
            'dbPrefix' => $dbPrefix,
            'semana' => $semana,
        ];
    }

    private function projectTableExists(string $tableName): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$tableName],
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    private function resolveMaxSemana(string $dbPrefix): int
    {
        $stmt = $this->db->query("SELECT MAX(Semana) FROM {$dbPrefix}_semanas_activas");
        return max(1, (int) $stmt->fetchColumn());
    }

    private function loadProjectActivities(string $dbPrefix, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT Consecutivo, Consecutivo_en_Programa, Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM {$dbPrefix}_programa_consolidado
             WHERE Semana = ? AND COALESCE(Actividad, '') <> ''
             ORDER BY Consecutivo_en_Programa ASC, Consecutivo ASC",
            [$semana],
        );

        // Sweep posicional: la última fila de capítulo (Titulo != 0) precede a sus hojas
        // en el orden del programa; no se puede confiar en el outline de la columna Id.
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

    private function loadRules(): array
    {
        $stmt = $this->db->query(
            "SELECT r.id, r.familia_id, r.patron_regex, r.modalidad_sugerida, r.confianza, r.prioridad,
                    f.codigo AS familia_codigo, f.nombre AS familia_nombre, f.categoria, f.siempre_revision
             FROM general_pdc_activity_rules r
             INNER JOIN general_pdc_familias f ON f.id = r.familia_id
             WHERE r.activa = 1
             ORDER BY r.prioridad DESC, r.confianza DESC, r.id ASC",
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadOptionsByFamily(): array
    {
        $stmt = $this->db->query(
            "SELECT f.id AS familia_id, f.codigo AS familia_codigo, f.nombre AS familia_nombre, f.categoria, f.orden AS familia_orden,
                    f.siempre_revision,
                    o.id AS option_id, o.tipo_contrato AS option_tipo_contrato, o.tipo_paquete AS option_tipo_paquete,
                    o.dias_elaboracion, o.dias_entrega, o.dias_recibo, o.dias_cuadros,
                    o.dias_legalizacion, o.dias_fabricacion, o.dias_insumos, o.notas,
                    i.id AS item_id, COALESCE(i.tipo_contrato, o.tipo_contrato) AS item_tipo_contrato,
                    COALESCE(i.tipo_paquete, o.tipo_paquete) AS item_tipo_paquete, i.paquete_nombre,
                    i.dias_proceso_id, i.orden AS item_orden,
                    dpc.diasElaboracionPliegos AS real_elaboracion,
                    dpc.diasEntregaPliegos AS real_entrega,
                    dpc.diasReciboPropuestas AS real_recibo,
                    dpc.diasCuadrosComparativos AS real_cuadros,
                    dpc.diasLegalizacionContrato AS real_legalizacion,
                    dpc.diasFabricacion AS real_fabricacion,
                    dpc.diasInsumosObra AS real_insumos
             FROM general_pdc_familias f
             LEFT JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
             LEFT JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
             LEFT JOIN general_dias_procesos_contratacion dpc ON dpc.id = i.dias_proceso_id
             ORDER BY f.orden ASC, o.tipo_paquete ASC, i.orden ASC",
        );

        $families = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $familyId = (int) $row['familia_id'];
            if (!isset($families[$familyId])) {
                $families[$familyId] = [
                    'familiaId' => $familyId,
                    'familiaCodigo' => $row['familia_codigo'],
                    'familiaNombre' => $row['familia_nombre'],
                    'categoria' => $row['categoria'],
                    'siempreRevision' => (int) ($row['siempre_revision'] ?? 0) === 1,
                    'opciones' => [],
                ];
            }

            if (empty($row['option_id'])) {
                continue;
            }

            $optionId = (int) $row['option_id'];
            if (!isset($families[$familyId]['opciones'][$optionId])) {
                $families[$familyId]['opciones'][$optionId] = [
                    'optionId' => $optionId,
                    'tipoContrato' => (int) $row['option_tipo_contrato'],
                    'tipoContratoNombre' => $this->tipoContratoLabel((int) $row['option_tipo_contrato']),
                    'tipoPaquete' => $this->tipoPaqueteLabel($row['option_tipo_paquete']),
                    'dias' => [
                        'dias_elaboracion' => (int) $row['dias_elaboracion'],
                        'dias_entrega' => (int) $row['dias_entrega'],
                        'dias_recibo' => (int) $row['dias_recibo'],
                        'dias_cuadros' => (int) $row['dias_cuadros'],
                        'dias_legalizacion' => (int) $row['dias_legalizacion'],
                        'dias_fabricacion' => (int) $row['dias_fabricacion'],
                        'dias_insumos' => (int) $row['dias_insumos'],
                    ],
                    'notas' => $row['notas'],
                    'items' => [],
                ];
            }

            if (!empty($row['item_id'])) {
                $families[$familyId]['opciones'][$optionId]['items'][] = [
                    'itemId' => (int) $row['item_id'],
                    'tipoContrato' => (int) $row['item_tipo_contrato'],
                    'tipoContratoNombre' => $this->tipoContratoLabel((int) $row['item_tipo_contrato']),
                    'tipoPaquete' => $this->tipoPaqueteLabel($row['item_tipo_paquete']),
                    'paqueteNombre' => $row['paquete_nombre'],
                    'diasProcesoId' => $row['dias_proceso_id'] !== null ? (int) $row['dias_proceso_id'] : null,
                    'diasReales' => [
                        'dias_elaboracion' => $row['real_elaboracion'] !== null ? (int) $row['real_elaboracion'] : null,
                        'dias_entrega' => $row['real_entrega'] !== null ? (int) $row['real_entrega'] : null,
                        'dias_recibo' => $row['real_recibo'] !== null ? (int) $row['real_recibo'] : null,
                        'dias_cuadros' => $row['real_cuadros'] !== null ? (int) $row['real_cuadros'] : null,
                        'dias_legalizacion' => $row['real_legalizacion'] !== null ? (int) $row['real_legalizacion'] : null,
                        'dias_fabricacion' => $row['real_fabricacion'] !== null ? (int) $row['real_fabricacion'] : null,
                        'dias_insumos' => $row['real_insumos'] !== null ? (int) $row['real_insumos'] : null,
                    ],
                ];
            }
        }

        foreach ($families as &$family) {
            $family['opciones'] = array_values($family['opciones']);
        }
        unset($family);

        return $families;
    }

    private function loadProjectStrategies(string $dbPrefix, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT familia_id, option_id
             FROM general_pdc_project_family_strategy
             WHERE db_prefix = ? AND semana = ?",
            [$dbPrefix, $semana],
        );

        $strategies = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $strategies[(int) $row['familia_id']] = (int) $row['option_id'];
        }

        return $strategies;
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

    private function extractChapterHierarchy(string $normalized): array
    {
        if (!preg_match('/\[CAPITULO:\s*([^\]]+)\]/u', $normalized, $matches)) {
            return [];
        }

        $levels = array_map('trim', explode(',', $matches[1]));

        return array_values(array_filter($levels, static fn($level) => $level !== ''));
    }

    private function newSuggestionGroup(array $match, array $familyInventory): array
    {
        $familyCode = (string) $match['familia_codigo'];
        $alwaysReview = (int) ($match['siempre_revision'] ?? 0) === 1;

        return [
            'familiaId' => (int) $match['familia_id'],
            'familiaCodigo' => $familyCode,
            'familiaNombre' => (string) $match['familia_nombre'],
            'categoria' => (string) $match['categoria'],
            'modalidadSugerida' => $this->tipoPaqueteLabel($match['modalidad_sugerida'] ?: null),
            'confianza' => (int) $match['confianza'],
            'requiereRevision' => $alwaysReview,
            'motivosRevision' => $alwaysReview
                ? ['Familia configurada para revisión manual obligatoria.']
                : [],
            'fechaInicio' => null,
            'optionId' => null,
            'selected' => false,
            'actividades' => [],
            'opciones' => $familyInventory['opciones'] ?? [],
        ];
    }

    private function selectOptionId(array $options, ?string $suggestedModality, ?int $strategyOptionId): ?int
    {
        if ($strategyOptionId !== null) {
            foreach ($options as $option) {
                if ((int) $option['optionId'] === $strategyOptionId) {
                    return $strategyOptionId;
                }
            }
        }

        if ($suggestedModality !== null) {
            foreach ($options as $option) {
                if ((string) $option['tipoPaquete'] === $suggestedModality) {
                    return (int) $option['optionId'];
                }
            }
        }

        return !empty($options) ? (int) $options[0]['optionId'] : null;
    }

    private function loadOption(int $optionId): ?array
    {
        $families = $this->loadOptionsByFamily();
        foreach ($families as $family) {
            foreach ($family['opciones'] as $option) {
                if ((int) $option['optionId'] === $optionId) {
                    $option['familia_id'] = $family['familiaId'];
                    $option['familia_codigo'] = $family['familiaCodigo'];
                    $option['familia_nombre'] = $family['familiaNombre'];
                    $option['tipo_contrato'] = $option['tipoContrato'];
                    $option['tipo_paquete'] = $option['tipoPaquete'];
                    $option['dias_elaboracion'] = $option['dias']['dias_elaboracion'];
                    $option['dias_entrega'] = $option['dias']['dias_entrega'];
                    $option['dias_recibo'] = $option['dias']['dias_recibo'];
                    $option['dias_cuadros'] = $option['dias']['dias_cuadros'];
                    $option['dias_legalizacion'] = $option['dias']['dias_legalizacion'];
                    $option['dias_fabricacion'] = $option['dias']['dias_fabricacion'];
                    $option['dias_insumos'] = $option['dias']['dias_insumos'];
                    $option['items'] = array_map(static function ($item) {
                        return [
                            'tipo_contrato' => $item['tipoContrato'],
                            'tipo_paquete' => $item['tipoPaquete'],
                            'paquete_nombre' => $item['paqueteNombre'],
                            'dias_reales' => $item['diasReales'],
                        ];
                    }, $option['items']);
                    return $option;
                }
            }
        }

        return null;
    }

    private function loadActividadesWithPackages(string $dbPrefix, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT Id, codigo, actividad, descripcionActividad, actividadInicio, fechaInicio, tipoContrato,
                    paqueteSI1, paqueteSI2, paqueteSI3, paqueteSI4, paqueteSI5,
                    paqueteS1, paqueteS2, paqueteS3, paqueteS4, paqueteS5,
                    paqueteMO1, paqueteMO2, paqueteMO3, paqueteMO4, paqueteMO5
             FROM {$dbPrefix}_actividades
             WHERE semanaActualizacion = ? AND tipoContrato IS NOT NULL AND tipoContrato != ''
             ORDER BY Id ASC",
            [$semana]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadExistingPdcPackages(string $dbPrefix, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT consecutivo, tipoPaquete, paqueteContratacion, fechaInicio
             FROM {$dbPrefix}_pdc
             WHERE semana = ? AND titulo = 0",
            [$semana]
        );

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = mb_strtolower(trim($row['tipoPaquete']) . '|' . trim($row['paqueteContratacion']));
            $map[$key] = $row;
        }
        return $map;
    }

    private function updateExistingPdcDates(string $dbPrefix, int $consecutivo, ?string $fechaInicio, int $newSemana): void
    {
        if ($fechaInicio === null) {
            $this->db->query(
                "UPDATE {$dbPrefix}_pdc SET semana = ? WHERE consecutivo = ?",
                [$newSemana, $consecutivo]
            );
            return;
        }

        $stmt = $this->db->query(
            "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                    diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
             FROM {$dbPrefix}_pdc WHERE consecutivo = ?",
            [$consecutivo]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return;
        }

        $durations = [
            'dias_elaboracion' => (int) ($row['diasElaboracionPliegos'] ?? 1),
            'dias_entrega' => (int) ($row['diasEntregaPliegos'] ?? 1),
            'dias_recibo' => (int) ($row['diasReciboPropuestas'] ?? 1),
            'dias_cuadros' => (int) ($row['diasCuadrosComparativos'] ?? 1),
            'dias_legalizacion' => (int) ($row['diasLegalizacionContrato'] ?? 1),
            'dias_fabricacion' => (int) ($row['diasFabricacion'] ?? 1),
            'dias_insumos' => (int) ($row['diasInsumosObra'] ?? 1),
        ];

        $dates = $this->calculateProcessDates($fechaInicio, $durations);

        $this->db->query(
            "UPDATE {$dbPrefix}_pdc SET
                semana = ?,
                fechaInicio = ?, fechaInicioProyectada = ?,
                fechaElaboracionPliegos = ?, fechaEntregaPliegos = ?,
                fechaReciboPropuestas = ?, fechaCuadrosComparativos = ?,
                fechaLegalizacionContrato = ?, fechaFabricacion = ?,
                fechaInsumosObra = ?
             WHERE consecutivo = ?",
            [
                $newSemana,
                $fechaInicio, $fechaInicio,
                $dates['fechaElaboracionPliegos'],
                $dates['fechaEntregaPliegos'],
                $dates['fechaReciboPropuestas'],
                $dates['fechaCuadrosComparativos'],
                $dates['fechaLegalizacionContrato'],
                $dates['fechaFabricacion'],
                $dates['fechaInsumosObra'],
                $consecutivo,
            ]
        );
    }

    private function matchActivityForPdc(array $activity, string $actividadNombre): ?array
    {
        $matcher = new \App\Support\ActivityMatcher();
        $rules = $matcher->loadRules();

        $pgActivity = [
            'Actividad' => $actividadNombre,
            '__capitulo' => '',
        ];

        return $matcher->matchActivity($pgActivity, $rules);
    }

    private function findOptionForTipoPaquete(int $familyId, string $tipoPaquete): ?array
    {
        $families = $this->loadOptionsByFamily();
        $family = $families[$familyId] ?? null;
        if ($family === null) {
            return null;
        }

        foreach ($family['opciones'] as $option) {
            if ($option['tipoPaquete'] === $tipoPaquete) {
                $option['familia_id'] = $familyId;
                $option['familia_codigo'] = $family['familiaCodigo'];
                $option['familia_nombre'] = $family['familiaNombre'];
                $option['tipo_contrato'] = $option['tipoContrato'];
                $option['tipo_paquete'] = $option['tipoPaquete'];
                $option['dias_elaboracion'] = $option['dias']['dias_elaboracion'];
                $option['dias_entrega'] = $option['dias']['dias_entrega'];
                $option['dias_recibo'] = $option['dias']['dias_recibo'];
                $option['dias_cuadros'] = $option['dias']['dias_cuadros'];
                $option['dias_legalizacion'] = $option['dias']['dias_legalizacion'];
                $option['dias_fabricacion'] = $option['dias']['dias_fabricacion'];
                $option['dias_insumos'] = $option['dias']['dias_insumos'];
                $option['items'] = array_map(static function ($item) {
                    return [
                        'tipo_contrato' => $item['tipoContrato'],
                        'tipo_paquete' => $item['tipoPaquete'],
                        'paquete_nombre' => $item['paqueteNombre'],
                        'dias_reales' => $item['diasReales'],
                    ];
                }, $option['items']);
                return $option;
            }
        }

        return null;
    }

    private function defaultDurations(): array
    {
        return [
            'dias_elaboracion' => 8,
            'dias_entrega' => 10,
            'dias_recibo' => 1,
            'dias_cuadros' => 10,
            'dias_legalizacion' => 10,
            'dias_fabricacion' => 0,
            'dias_insumos' => 0,
        ];
    }
    private function resolveDurations(array $option, array $item): array
    {
        $real = $item['dias_reales'] ?? [];
        $hasReal = !empty($real)
            && count(array_filter($real, static fn($value) => $value !== null && (int) $value !== 1)) > 0;

        if ($hasReal) {
            return [
                'dias_elaboracion' => (int) ($real['dias_elaboracion'] ?? 1),
                'dias_entrega' => (int) ($real['dias_entrega'] ?? 1),
                'dias_recibo' => 1,
                'dias_cuadros' => (int) ($real['dias_cuadros'] ?? 1),
                'dias_legalizacion' => (int) ($real['dias_legalizacion'] ?? 1),
                'dias_fabricacion' => (int) ($real['dias_fabricacion'] ?? 1),
                'dias_insumos' => (int) ($real['dias_insumos'] ?? 1),
            ];
        }

        return [
            'dias_elaboracion' => (int) $option['dias_elaboracion'],
            'dias_entrega' => (int) $option['dias_entrega'],
            'dias_recibo' => 1,
            'dias_cuadros' => (int) $option['dias_cuadros'],
            'dias_legalizacion' => (int) $option['dias_legalizacion'],
            'dias_fabricacion' => (int) $option['dias_fabricacion'],
            'dias_insumos' => (int) $option['dias_insumos'],
        ];
    }

    private function calculateProcessDates(?string $fechaInicio, array $durations): array
    {
        $keys = [
            'fechaElaboracionPliegos',
            'fechaEntregaPliegos',
            'fechaReciboPropuestas',
            'fechaCuadrosComparativos',
            'fechaLegalizacionContrato',
            'fechaFabricacion',
            'fechaInsumosObra',
        ];

        if ($fechaInicio === null) {
            return array_fill_keys($keys, null);
        }

        $fechaInsumos = $this->subDays($fechaInicio, $durations['dias_insumos']);
        $fechaFabricacion = $this->subDays($fechaInsumos, $durations['dias_fabricacion']);
        $fechaLegalizacion = $this->subDays($fechaFabricacion, $durations['dias_legalizacion']);
        $fechaCuadros = $this->subDays($fechaLegalizacion, $durations['dias_cuadros']);
        $fechaRecibo = $this->subDays($fechaCuadros, $durations['dias_recibo']);
        $fechaEntrega = $this->subDays($fechaRecibo, $durations['dias_entrega']);
        $fechaElaboracion = $this->subDays($fechaEntrega, $durations['dias_elaboracion']);

        return [
            'fechaElaboracionPliegos' => $fechaElaboracion,
            'fechaEntregaPliegos' => $fechaEntrega,
            'fechaReciboPropuestas' => $fechaRecibo,
            'fechaCuadrosComparativos' => $fechaCuadros,
            'fechaLegalizacionContrato' => $fechaLegalizacion,
            'fechaFabricacion' => $fechaFabricacion,
            'fechaInsumosObra' => $fechaInsumos,
        ];
    }

    private function subDays(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' - ' . max(0, $days) . ' days'));
    }

    private function pdcPackageExists(string $dbPrefix, int $semana, string $tipoPaquete, string $paquete): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM {$dbPrefix}_pdc
             WHERE semana = ? AND titulo = 0 AND tipoPaquete = ? AND paqueteContratacion = ?",
            [$semana, $tipoPaquete, $paquete],
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    private function ensureTitleRow(string $dbPrefix, int $semana, string $tipoPaquete): void
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM {$dbPrefix}_pdc WHERE semana = ? AND titulo = 1 AND tipoPaquete = ?",
            [$semana, $tipoPaquete],
        );

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $this->db->query(
            "INSERT INTO {$dbPrefix}_pdc (semana, titulo, tipoPaquete, paqueteContratacion, subcontratoPaquete)
             VALUES (?, 1, ?, ?, 1)",
            [$semana, $tipoPaquete, $tipoPaquete],
        );
    }

    private function nextSubcontractIndex(string $dbPrefix, int $semana, string $tipoPaquete): int
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(MAX(subcontratoPaquete), 0) + 1
             FROM {$dbPrefix}_pdc
             WHERE semana = ? AND titulo = 0 AND tipoPaquete = ?",
            [$semana, $tipoPaquete],
        );

        return max(1, (int) $stmt->fetchColumn());
    }

    private function saveProjectStrategy(string $dbPrefix, int $semana, int $familyId, int $optionId): void
    {
        $this->db->query(
            "INSERT INTO general_pdc_project_family_strategy (db_prefix, semana, familia_id, option_id, aplicada)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE option_id = VALUES(option_id), aplicada = 1",
            [$dbPrefix, $semana, $familyId, $optionId],
        );
    }

    /**
     * Carga actividades de {db}_actividades para la semana y las mapea
     * por actividadInicio = Consecutivo_en_Programa (puente con programa_consolidado).
     *
     * @return array<int, array> Mapa [consecutivoPrograma => fila_actividad]
     */
    private function mapActividadesByConsecutivo(string $dbPrefix, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT Id, codigo, actividad, actividadInicio, fechaInicio, tipoContrato,
                    paqueteSI1, paqueteSI2, paqueteSI3, paqueteSI4, paqueteSI5,
                    paqueteMO1, paqueteMO2, paqueteMO3, paqueteMO4, paqueteMO5,
                    paqueteS1, paqueteS2, paqueteS3, paqueteS4, paqueteS5
             FROM {$dbPrefix}_actividades
             WHERE semanaActualizacion = ?",
            [$semana],
        );

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (int) ($row['actividadInicio'] ?? 0);
            if ($key > 0) {
                $map[$key] = $row;
            }
        }

        return $map;
    }

    /**
     * Detecta conflictos: columnas paquete* ya ocupadas con un valor diferente
     * al paquete que se intenta escribir.
     *
     * @param array $mappedActivities Mapa de actividades del listado
     * @param array $optionItems      Items de la opción de contrato seleccionada
     * @param array $pgActivities     Actividades del PG que matchearon (para obtener consecutivoPrograma)
     * @return array{conflictos: array, escrituras: array} Conflictos detectados y escrituras planificadas
     */
    private function detectConflicts(array $mappedActivities, array $optionItems, array $pgActivities): array
    {
        $conflictos = [];
        $escrituras = [];
        $prefixMap = array_flip(self::PACKAGE_TYPE_LABELS);

        foreach ($pgActivities as $pgActivity) {
            $consecProg = (int) ($pgActivity['consecutivoPrograma'] ?? 0);
            if ($consecProg <= 0 || !isset($mappedActivities[$consecProg])) {
                continue;
            }

            $actRow = $mappedActivities[$consecProg];

            foreach ($optionItems as $item) {
                $tipoLabel = $item['tipo_paquete'] ?? '';
                $prefix = $prefixMap[$tipoLabel] ?? null;
                if ($prefix === null || $prefix === 'MO+S') {
                    continue;
                }

                $paqueteNombre = trim((string) ($item['paquete_nombre'] ?? ''));
                if ($paqueteNombre === '') {
                    continue;
                }

                $normNuevo = $this->normalizeActivityText($paqueteNombre);
                $foundEmpty = false;
                $alreadyAssigned = false;

                for ($i = 1; $i <= 5; $i++) {
                    $col = 'paquete' . $prefix . $i;
                    $existing = trim((string) ($actRow[$col] ?? ''));

                    if ($existing === '') {
                        if (!$foundEmpty && !$alreadyAssigned) {
                            $escrituras[] = [
                                'actividadId' => (int) $actRow['Id'],
                                'consecutivoPrograma' => $consecProg,
                                'columna' => $col,
                                'paquete' => $paqueteNombre,
                            ];
                            $foundEmpty = true;
                        }
                    } else {
                        $normExistente = $this->normalizeActivityText($existing);
                        if ($normExistente === $normNuevo) {
                            $alreadyAssigned = true;
                        }
                    }
                }

                if (!$foundEmpty && !$alreadyAssigned) {
                    $occupiedCols = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $col = 'paquete' . $prefix . $i;
                        $existing = trim((string) ($actRow[$col] ?? ''));
                        if ($existing !== '') {
                            $occupiedCols[] = $col . ' = "' . $existing . '"';
                        }
                    }
                    $conflictos[] = [
                        'actividadId' => (int) $actRow['Id'],
                        'actividad' => $actRow['actividad'] ?? '',
                        'consecutivoPrograma' => $consecProg,
                        'paqueteIntentado' => $paqueteNombre,
                        'columnasOcupadas' => $occupiedCols,
                        'motivo' => 'Todas las columnas ' . $prefix . '1-' . $prefix . '5 están ocupadas.',
                    ];
                }
            }
        }

        return ['conflictos' => $conflictos, 'escrituras' => $escrituras];
    }

    /**
     * Escribe paquetes auto-generados en las columnas paquete* de {db}_actividades.
     * Reglas: solo columna vacía, dedupe por nombre normalizado, nunca sobreescribe.
     *
     * @return array{escritas: int, conflictos: array, omitidas: int}
     */
    private function writeBackToActividades(string $dbPrefix, int $semana, array $suggestion, array $option): array
    {
        $pgActivities = $suggestion['actividades'] ?? [];
        if (empty($pgActivities) || empty($option['items'])) {
            return ['escritas' => 0, 'conflictos' => [], 'omitidas' => 0];
        }

        $mappedActivities = $this->mapActividadesByConsecutivo($dbPrefix, $semana);
        if (empty($mappedActivities)) {
            return ['escritas' => 0, 'conflictos' => [], 'omitidas' => 0];
        }

        $result = $this->detectConflicts($mappedActivities, $option['items'], $pgActivities);

        if (!empty($result['conflictos'])) {
            return ['escritas' => 0, 'conflictos' => $result['conflictos'], 'omitidas' => count($pgActivities)];
        }

        $escritas = 0;
        $omitidas = 0;
        $prefixMap = array_flip(self::PACKAGE_TYPE_LABELS);

        foreach ($result['escrituras'] as $escritura) {
            $col = $escritura['columna'];
            $paquete = $escritura['paquete'];
            $actId = $escritura['actividadId'];

            $this->db->query(
                "UPDATE {$dbPrefix}_actividades SET {$col} = ?, semanaActualizacion = ? WHERE Id = ?",
                [$paquete, $semana, $actId],
            );
            $escritas++;
        }

        $mappedConsecutivos = array_keys($mappedActivities);
        foreach ($pgActivities as $pgActivity) {
            $consecProg = (int) ($pgActivity['consecutivoPrograma'] ?? 0);
            if (!in_array($consecProg, $mappedConsecutivos, true)) {
                $omitidas++;
            }
        }

        return ['escritas' => $escritas, 'conflictos' => [], 'omitidas' => $omitidas];
    }

    private function tipoPaqueteLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (isset(self::PACKAGE_TYPE_LABELS[$value])) {
            return self::PACKAGE_TYPE_LABELS[$value];
        }

        $clean = $this->normalizeLabel($value);
        if (isset(self::PACKAGE_TYPE_LABELS[$clean])) {
            return self::PACKAGE_TYPE_LABELS[$clean];
        }

        return in_array($clean, self::PACKAGE_TYPE_LABELS, true) ? $clean : $value;
    }

    private function normalizeLabel(string $value): string
    {
        return trim($value);
    }

    private function tipoContratoLabel(int $value): string
    {
        return match ($value) {
            1 => 'Mano de Obra y Suministro por separado',
            2 => 'Suministro e Instalación',
            default => '',
        };
    }

    private function formatActivity(array $activity, ?array $match = null): array
    {
        return [
            'consecutivo' => (int) ($activity['Consecutivo'] ?? 0),
            'consecutivoPrograma' => (int) ($activity['Consecutivo_en_Programa'] ?? 0),
            'id' => $activity['Id'] ?? '',
            'actividad' => $activity['Actividad'] ?? '',
            'fechaInicio' => $activity['Fecha_Inicio'] ?? null,
            'matchedBy' => $match['matchedBy'] ?? null,
            'breadcrumbLevel' => $match['breadcrumbLevel'] ?? null,
        ];
    }

    private function manualActivity(array $activity, string $reason): array
    {
        return array_merge($this->formatActivity($activity), [
            'motivo' => $reason,
        ]);
    }

    private function minDate(?string $current, ?string $candidate): ?string
    {
        $candidate = $this->normalizeDate($candidate);
        if ($candidate === null) {
            return $current;
        }

        if ($current === null) {
            return $candidate;
        }

        return $candidate < $current ? $candidate : $current;
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

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '', true);
        if (is_array($payload)) {
            return $payload;
        }

        if (!empty($_POST['payload'])) {
            $payload = json_decode((string) $_POST['payload'], true);
            if (is_array($payload)) {
                return $payload;
            }
        }

        if (!empty($_POST['suggestions'])) {
            $payload = json_decode((string) $_POST['suggestions'], true);
            if (is_array($payload)) {
                return ['suggestions' => $payload];
            }
        }

        return $_POST;
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
