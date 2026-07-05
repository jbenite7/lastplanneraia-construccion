<?php

namespace App\Services;

use App\Support\ActivityMatcher;
use App\Support\FamilyCatalogStatusResolver;
use App\Support\OperationalFamilyPolicy;
use App\Support\SemiAutoQualityGate;
use PDO;
use RuntimeException;
use Throwable;

class SemiAutoService
{
    public const MODULE_LISTADO = 'listado-actividades';
    public const MODULE_CONTRATOS = 'contratos';
    public const MODULE_PDC = 'pdc';

    private const HIGH_CONFIDENCE = 80.0;
    private const MEDIUM_CONFIDENCE = 50.0;

    private \Database $db;
    private ?SemiAutoAssistantService $assistantService = null;
    private ?SemiAutoQualityGate $qualityGate = null;
    private ?OperationalFamilyPolicy $familyPolicy = null;
    private ?FamilyCatalogStatusResolver $catalogStatusResolver = null;
    private array $contractPackageCache = [];
    private bool $activityProgramSourcesTableEnsured = false;
    private ?array $traceContext = null;
    private array $traceAnalysis = [];

    public function __construct(?\Database $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    private function assistant(): SemiAutoAssistantService
    {
        if ($this->assistantService === null) {
            $this->assistantService = new SemiAutoAssistantService($this->db);
        }

        return $this->assistantService;
    }

    private function qualityGate(): SemiAutoQualityGate
    {
        if ($this->qualityGate === null) {
            $this->qualityGate = new SemiAutoQualityGate();
        }

        return $this->qualityGate;
    }

    private function familyPolicy(): OperationalFamilyPolicy
    {
        if ($this->familyPolicy === null) {
            $this->familyPolicy = new OperationalFamilyPolicy($this->db);
        }

        return $this->familyPolicy;
    }

    private function catalogStatusResolver(): FamilyCatalogStatusResolver
    {
        if ($this->catalogStatusResolver === null) {
            $this->catalogStatusResolver = new FamilyCatalogStatusResolver($this->db);
        }

        return $this->catalogStatusResolver;
    }

    public function preview(string $module, array $context, ?string $requestedRunId = null): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        $semana = (int) ($context['semana'] ?? 0);
        if ($projectId <= 0) {
            throw new RuntimeException('Proyecto inválido.');
        }
        if ($semana <= 0) {
            $semana = $this->resolveMaxSemana($projectId);
        }

        $config = $this->loadProjectConfig($projectId, $module);
        $runId = $this->resolveRequestedRunId($requestedRunId);
        $this->insertRun($runId, $projectId, $module, $semana, $config);
        $this->beginTrace($runId, $projectId, $module, $semana, $config);
        $this->traceStep('context', 'done', 'Proyecto, semana y umbrales listos.', [
            'project_id' => $projectId,
            'semana' => $semana,
        ], 10);

        try {
            $suggestions = match ($module) {
                self::MODULE_LISTADO => $this->buildListadoSuggestions($projectId, $semana, $config),
                self::MODULE_CONTRATOS => $this->buildContratosSuggestions($projectId, $semana, $config),
                self::MODULE_PDC => $this->buildPdcSuggestions($projectId, $semana, $config),
            };

            $this->traceStep('stored', 'running', 'Guardando propuestas para revisión.', [
                'propuestas' => count($suggestions),
            ], 88);

            foreach ($suggestions as $suggestion) {
                $this->insertSuggestion($runId, $projectId, $module, $suggestion, $config);
            }

            $preselected = count(array_filter($suggestions, static fn(array $item): bool => !empty($item['preselected'])));
            $this->finishTrace($suggestions, $preselected);
            $this->updateRunCompletion($runId, $projectId, count($suggestions), $preselected);
            $this->assistant()->recordPreview($module, $projectId, $semana, $runId, $suggestions);

            return $this->formatRunResponse($runId, $projectId, $module);
        } catch (Throwable $e) {
            $this->failTrace($runId, $projectId, $e->getMessage());
            throw $e;
        } finally {
            $this->traceContext = null;
            $this->traceAnalysis = [];
        }
    }

    public function apply(string $module, array $context, string $runId, array $suggestionIds): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        $run = $this->loadRun($runId, $projectId, $module);
        $suggestions = $this->loadSuggestionsForApply($runId, $projectId, $module, $suggestionIds);

        if (empty($suggestions)) {
            return ['respuesta' => 'BIEN', 'aplicadas' => 0, 'errores' => 0, 'run_id' => $runId];
        }

        $applied = 0;
        $errors = 0;

        $this->ensureActivityProgramSourcesTable();
        $this->db->beginTransaction();
        try {
            foreach ($suggestions as $suggestion) {
                try {
                    $result = $this->applySuggestion($module, $projectId, (int) $run['semana'], $suggestion);
                    $this->recordDecision(
                        $projectId,
                        $module,
                        $runId,
                        $suggestion['suggestion_id'],
                        'apply',
                        $result['before'] ?? null,
                        $result['after'] ?? null,
                        $result['result'] ?? null,
                    );
                    $this->db->query(
                        "UPDATE semi_auto_suggestions SET status = 'applied' WHERE suggestion_id = ? AND project_id = ?",
                        [$suggestion['suggestion_id'], $projectId],
                    );
                    $applied++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->recordDecision(
                        $projectId,
                        $module,
                        $runId,
                        $suggestion['suggestion_id'],
                        'error',
                        $this->decodeJson($suggestion['current_payload']),
                        $this->decodeJson($suggestion['proposed_payload']),
                        ['message' => $e->getMessage()],
                    );
                    $this->db->query(
                        "UPDATE semi_auto_suggestions SET status = 'error' WHERE suggestion_id = ? AND project_id = ?",
                        [$suggestion['suggestion_id'], $projectId],
                    );
                }
            }

            $status = $errors > 0 ? 'applied_with_errors' : 'applied';
            $this->db->query(
                "UPDATE semi_auto_runs
                 SET status = ?, applied_count = applied_count + ?, error_count = error_count + ?
                 WHERE run_id = ? AND project_id = ?",
                [$status, $applied, $errors, $runId, $projectId],
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'respuesta' => 'BIEN',
            'run_id' => $runId,
            'aplicadas' => $applied,
            'errores' => $errors,
        ];
    }

    public function undo(string $module, array $context, string $runId): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        $run = $this->loadRun($runId, $projectId, $module);
        $stmt = $this->db->query(
            "SELECT * FROM semi_auto_decisions
             WHERE project_id = ? AND module = ? AND run_id = ? AND decision = 'apply'
             ORDER BY id DESC",
            [$projectId, $module, $runId],
        );
        $decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reverted = 0;
        $errors = 0;
        $this->db->beginTransaction();
        try {
            foreach ($decisions as $decision) {
                try {
                    $this->undoDecision($module, $projectId, (int) $run['semana'], $decision);
                    $this->recordDecision(
                        $projectId,
                        $module,
                        $runId,
                        $decision['suggestion_id'] ?? null,
                        'undo',
                        $this->decodeJson($decision['after_payload']),
                        $this->decodeJson($decision['before_payload']),
                        $this->decodeJson($decision['result_payload']),
                    );
                    if (!empty($decision['suggestion_id'])) {
                        $this->db->query(
                            "UPDATE semi_auto_suggestions SET status = 'undone' WHERE suggestion_id = ? AND project_id = ?",
                            [$decision['suggestion_id'], $projectId],
                        );
                    }
                    $reverted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->recordDecision(
                        $projectId,
                        $module,
                        $runId,
                        $decision['suggestion_id'] ?? null,
                        'undo_error',
                        $this->decodeJson($decision['after_payload']),
                        $this->decodeJson($decision['before_payload']),
                        ['message' => $e->getMessage()],
                    );
                }
            }
            $this->db->query(
                "UPDATE semi_auto_runs SET status = ? WHERE run_id = ? AND project_id = ?",
                [$errors > 0 ? 'undo_with_errors' : 'undone', $runId, $projectId],
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'respuesta' => 'BIEN',
            'run_id' => $runId,
            'revertidas' => $reverted,
            'errores' => $errors,
        ];
    }

    public function feedback(string $module, array $context, array $payload): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        $updatedSuggestion = false;

        if (!empty($payload['run_id']) && !empty($payload['suggestion_id']) && isset($payload['corrected'])) {
            $updatedSuggestion = $this->updateSuggestionFromFeedback(
                $projectId,
                $module,
                (string) $payload['run_id'],
                (string) $payload['suggestion_id'],
                (array) $payload['corrected'],
            );
        }

        $this->db->query(
            "INSERT INTO semi_auto_feedback
             (project_id, module, run_id, suggestion_id, feedback_type, original_payload, corrected_payload, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $projectId,
                $module,
                $payload['run_id'] ?? null,
                $payload['suggestion_id'] ?? null,
                $payload['feedback_type'] ?? 'correction',
                $this->jsonEncode($payload['original'] ?? null),
                $this->jsonEncode($payload['corrected'] ?? null),
                $payload['notes'] ?? null,
                $this->currentUser(),
            ],
        );
        $this->assistant()->recordLearningSignal($module, $projectId, $payload, $this->currentUser());

        return ['respuesta' => 'BIEN', 'updated_suggestion' => $updatedSuggestion];
    }

    public function metrics(string $module, array $context): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        $semana = (int) ($context['semana'] ?? 0);
        if ($semana <= 0) {
            $semana = $this->resolveMaxSemana($projectId);
        }

        $runs = $this->db->query(
            "SELECT COUNT(*) AS runs,
                    COALESCE(SUM(total_suggestions), 0) AS suggestions,
                    COALESCE(SUM(applied_count), 0) AS applied,
                    COALESCE(SUM(error_count), 0) AS errors
             FROM semi_auto_runs
             WHERE project_id = ? AND module = ?",
            [$projectId, $module],
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'respuesta' => 'BIEN',
            'module' => $module,
            'project_id' => $projectId,
            'semana' => $semana,
            'runs' => (int) ($runs['runs'] ?? 0),
            'suggestions' => (int) ($runs['suggestions'] ?? 0),
            'applied' => (int) ($runs['applied'] ?? 0),
            'errors' => (int) ($runs['errors'] ?? 0),
            'coverage' => $this->moduleCoverage($module, $projectId, $semana),
        ];
    }

    public function status(string $module, array $context, string $runId): array
    {
        $module = $this->normalizeModule($module);
        $projectId = (int) ($context['projectId'] ?? 0);
        try {
            $run = $this->loadRun($runId, $projectId, $module);
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Corrida de automatización no encontrada.') {
                throw $e;
            }

            $analysis = $this->analysisSkeleton($module, (int) ($context['semana'] ?? 0));
            return [
                'respuesta' => 'BIEN',
                'run_id' => $runId,
                'module' => $module,
                'status' => 'pending',
                'progress' => (int) ($analysis['progress'] ?? 0),
                'active_step' => $analysis['active_step'] ?? '',
                'steps' => $analysis['steps'] ?? [],
                'summary' => $analysis['summary'] ?? [],
            ];
        }
        $analysis = $this->analysisFromRun($run);

        return [
            'respuesta' => 'BIEN',
            'run_id' => $runId,
            'module' => $module,
            'status' => $run['status'],
            'progress' => (int) ($analysis['progress'] ?? 0),
            'active_step' => $analysis['active_step'] ?? '',
            'steps' => $analysis['steps'] ?? [],
            'summary' => $analysis['summary'] ?? [],
        ];
    }

    private function resolveRequestedRunId(?string $requestedRunId): string
    {
        $runId = trim((string) $requestedRunId);
        if ($runId !== '' && preg_match('/^run_[A-Za-z0-9_-]{8,80}$/', $runId) === 1) {
            return $runId;
        }

        return $this->newId('run');
    }

    private function beginTrace(string $runId, int $projectId, string $module, int $semana, array $config): void
    {
        $this->traceContext = compact('runId', 'projectId', 'module', 'semana', 'config');
        $this->traceAnalysis = $this->analysisSkeleton($module, $semana);
        $this->persistTrace('previewing');
    }

    private function traceStep(string $id, string $status, string $message, array $counts = [], ?int $progress = null): void
    {
        if ($this->traceContext === null) {
            return;
        }

        foreach ($this->traceAnalysis['steps'] as &$step) {
            if (($step['id'] ?? '') !== $id) {
                continue;
            }
            $step['status'] = $status;
            $step['message'] = $message;
            $step['counts'] = $counts;
            $step['updated_at'] = date('c');
        }
        unset($step);

        $this->traceAnalysis['active_step'] = $id;
        if ($progress !== null) {
            $this->traceAnalysis['progress'] = max(0, min(100, $progress));
        }
        $this->persistTrace();
    }

    private function finishTrace(array $suggestions, int $preselected): void
    {
        $summary = $this->analysisSummary($suggestions, $preselected);
        $this->traceAnalysis['summary'] = $summary;
        $this->traceAnalysis['finished_at'] = date('c');
        $this->traceStep('stored', 'done', 'Análisis listo para revisión.', [
            'propuestas' => count($suggestions),
            'preseleccionadas' => $preselected,
        ], 100);
    }

    private function failTrace(string $runId, int $projectId, string $message): void
    {
        if ($this->traceContext !== null) {
            $active = (string) ($this->traceAnalysis['active_step'] ?? 'context');
            $this->traceStep($active, 'error', $message, [], null);
            $this->traceAnalysis['summary'] = ['error' => $message];
            $this->persistTrace('error');
            return;
        }

        $this->db->query(
            "UPDATE semi_auto_runs SET status = 'error' WHERE run_id = ? AND project_id = ?",
            [$runId, $projectId],
        );
    }

    private function persistTrace(?string $status = null): void
    {
        if ($this->traceContext === null) {
            return;
        }

        $ctx = $this->traceContext;
        $metadata = $this->runMetadata($ctx['config'], $this->traceAnalysis);
        $sql = 'UPDATE semi_auto_runs SET metadata = ?';
        $params = [$this->jsonEncode($metadata)];
        if ($status !== null) {
            $sql .= ', status = ?';
            $params[] = $status;
        }
        $sql .= ' WHERE run_id = ? AND project_id = ?';
        $params[] = $ctx['runId'];
        $params[] = $ctx['projectId'];
        $this->db->query($sql, $params);
    }

    private function updateRunCompletion(string $runId, int $projectId, int $total, int $preselected): void
    {
        $metadata = $this->runMetadata(
            $this->traceContext['config'] ?? [],
            $this->traceAnalysis,
        );
        $this->db->query(
            "UPDATE semi_auto_runs
             SET status = 'previewed', total_suggestions = ?, preselected_count = ?, metadata = ?
             WHERE run_id = ? AND project_id = ?",
            [$total, $preselected, $this->jsonEncode($metadata), $runId, $projectId],
        );
    }

    private function runMetadata(array $config, array $analysis): array
    {
        return [
            'thresholds' => [
                'high' => $config['high_threshold'] ?? self::HIGH_CONFIDENCE,
                'medium' => $config['medium_threshold'] ?? self::MEDIUM_CONFIDENCE,
            ],
            'generated_at' => date('c'),
            'analysis' => $analysis,
        ];
    }

    private function analysisSkeleton(string $module, int $semana): array
    {
        return [
            'progress' => 1,
            'active_step' => 'context',
            'module' => $module,
            'semana' => $semana,
            'started_at' => date('c'),
            'steps' => [
                $this->analysisStep('context', 'Contexto', 'Proyecto y semana'),
                $this->analysisStep('data', 'Datos', 'Filas de origen'),
                $this->analysisStep('rules', 'Reglas', 'Criterios disponibles'),
                $this->analysisStep('matches', 'Coincidencias', 'Cruces y familias'),
                $this->analysisStep('suggestions', 'Propuestas', 'Cambios sugeridos'),
                $this->analysisStep('stored', 'Preview', 'Guardado para revisión'),
            ],
            'summary' => [],
        ];
    }

    private function analysisStep(string $id, string $label, string $description): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'description' => $description,
            'status' => 'pending',
            'message' => '',
            'counts' => [],
        ];
    }

    private function analysisFromRun(array $run): array
    {
        $metadata = $this->decodeJson($run['metadata'] ?? null) ?: [];
        $analysis = $metadata['analysis'] ?? null;
        if (is_array($analysis)) {
            return $analysis;
        }

        return [
            'progress' => (string) ($run['status'] ?? '') === 'previewed' ? 100 : 0,
            'active_step' => '',
            'steps' => [],
            'summary' => [],
        ];
    }

    private function analysisSummary(array $suggestions, int $preselected): array
    {
        $actions = [];
        foreach ($suggestions as $suggestion) {
            $action = (string) ($suggestion['action'] ?? 'sin_accion');
            $actions[$action] = ($actions[$action] ?? 0) + 1;
        }

        return [
            'total_suggestions' => count($suggestions),
            'preselected' => $preselected,
            'actions' => $actions,
            'message' => count($suggestions) > 0
                ? 'Se encontraron propuestas para revisar y aplicar selectivamente.'
                : 'No se encontraron propuestas nuevas con los datos actuales.',
        ];
    }

    private function suggestionAnalysis(array $row, array $proposed, array $applyPayload): array
    {
        $stored = $applyPayload['_analysis'] ?? [];
        $analysis = [
            'user' => $stored['user'] ?? $this->defaultSuggestionUserAnalysis($row),
        ];
        if (isset($stored['quality_gate']) && is_array($stored['quality_gate'])) {
            $analysis['quality_gate'] = $stored['quality_gate'];
        }
        if (isset($stored['sources']) && is_array($stored['sources'])) {
            $analysis['sources'] = $stored['sources'];
        }
        if (isset($stored['selected_source_id'])) {
            $analysis['selected_source_id'] = (int) $stored['selected_source_id'];
        }
        if (isset($stored['selected_source']) && is_array($stored['selected_source'])) {
            $analysis['selected_source'] = $stored['selected_source'];
        }

        if ($this->isAdminUser()) {
            $analysis['technical'] = array_merge(
                $stored['technical'] ?? [],
                [
                    'target_table' => $row['target_table'] ?? null,
                    'target_pk' => $row['target_pk'] ?? null,
                    'action' => $row['action'] ?? null,
                    'match_source' => $row['match_source'] ?? null,
                    'proposed_fields' => $proposed['fields'] ?? $proposed,
                ],
            );
        }

        return $analysis;
    }

    private function defaultSuggestionUserAnalysis(array $row): array
    {
        return [
            'origin' => (string) ($row['match_source'] ?? 'Automatización'),
            'rule' => (string) ($row['title'] ?? 'Regla automática'),
            'decision' => (string) ($row['reason'] ?? 'Revisar propuesta.'),
            'confidence' => (float) ($row['confidence'] ?? 0),
        ];
    }

    private function assistantReasoning(array $row, array $proposed, array $applyPayload): array
    {
        $analysis = $this->suggestionAnalysis($row, $proposed, $applyPayload);
        $user = $analysis['user'] ?? [];
        $quality = $analysis['quality_gate'] ?? [];
        $catalogStatus = is_array($analysis['catalog_status'] ?? null) ? $analysis['catalog_status'] : null;
        $band = (string) ($row['confidence_band'] ?? 'low');
        $action = (string) ($row['action'] ?? '');
        $isPreselected = (int) ($row['preselected'] ?? 0) === 1;
        $forcedReview = str_contains((string) ($row['match_source'] ?? ''), ':revision');
        $qualityStatus = (string) ($quality['status'] ?? '');
        $nextStep = 'Revisar antes de aplicar.';
        if ($qualityStatus === 'ready' && $band === 'high' && $isPreselected && !$forcedReview && !str_starts_with($action, 'review_no_match')) {
            $nextStep = 'Puede aplicarse después de una revisión rápida.';
        } elseif ($qualityStatus === 'conflict' || $band === 'low' || !$isPreselected || $forcedReview || str_starts_with($action, 'review_no_match')) {
            $nextStep = 'No se aplicará automáticamente; requiere decisión manual.';
        }

        if ($catalogStatus !== null && !empty($catalogStatus['next_action'])) {
            $nextStep = (string) $catalogStatus['next_action'];
        }

        return [
            'headline' => (string) ($catalogStatus['label'] ?? $user['rule'] ?? $row['title'] ?? 'Propuesta detectada'),
            'why' => (string) ($catalogStatus['reason'] ?? $user['decision'] ?? $row['reason'] ?? 'El sistema encontró una posible mejora.'),
            'risk' => $qualityStatus === 'conflict' ? 'alto' : ($qualityStatus === 'review' ? 'medio' : ($band === 'high' ? 'bajo' : ($band === 'medium' ? 'medio' : 'alto'))),
            'next_step' => $nextStep,
            'package_hint' => (string) ($catalogStatus['package_hint'] ?? ''),
        ];
    }

    private function matchAnalysis(string $origin, ?array $match, string $decision, array $extra = []): array
    {
        $family = is_array($match) ? (string) ($match['familia_nombre'] ?? '') : '';
        $user = [
            'origin' => $origin,
            'rule' => $family !== '' ? 'Familia detectada: ' . $family : ($extra['rule'] ?? 'Sin regla detectada'),
            'decision' => $decision,
            'confidence' => $match !== null ? $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0) : 0,
        ];

        return [
            'user' => array_merge($user, $extra['user'] ?? []),
            'technical' => array_merge($this->matchTechnical($match), $extra['technical'] ?? []),
        ];
    }

    private function analysisWithQuality(array $analysis, array $quality): array
    {
        $analysis['quality_gate'] = $quality['quality_gate'] ?? [];
        $analysis['sources'] = $quality['sources'] ?? [];

        return $analysis;
    }

    private function matchTechnical(?array $match): array
    {
        if ($match === null) {
            return [];
        }

        return [
            'rule_id' => $match['id'] ?? null,
            'familia_id' => $match['familia_id'] ?? null,
            'familia_codigo' => $match['familia_codigo'] ?? null,
            'patron_regex' => $match['patron_regex'] ?? null,
            'matched_by' => $match['matchedBy'] ?? null,
            'breadcrumb_level' => $match['breadcrumbLevel'] ?? null,
            'chapter_filtered' => $match['chapterFiltered'] ?? null,
            'review_required' => $match['reviewRequired'] ?? null,
            'review_reason' => $match['reviewReason'] ?? null,
        ];
    }

    private function buildListadoSuggestions(int $projectId, int $semana, array $config): array
    {
        $this->traceStep('data', 'running', 'Leyendo actividades del Programa General.', [], 18);
        $programRows = $this->db->query(
            "SELECT row_id AS Consecutivo,
                    unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ?
             ORDER BY Fecha_Inicio ASC, unique_id ASC",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_ASSOC);
        $activities = $this->leafProgramActivitiesWithBreadcrumb($programRows);

        $this->ensureActivityProgramSourcesTable();
        $existing = $this->db->query(
            "SELECT actividadInicio FROM actividades WHERE project_id = ? AND semanaActualizacion = ?",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_COLUMN);
        $linkedSources = $this->db->query(
            "SELECT programa_unique_id FROM actividad_programa_fuentes WHERE project_id = ? AND semana = ?",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_COLUMN);
        $existingKeys = array_flip(array_map('strval', array_merge($existing, $linkedSources)));
        $this->traceStep('data', 'done', 'Datos de origen cargados.', [
            'programa_general' => count($activities),
            'actividades_existentes' => count($existing),
        ], 32);

        $this->traceStep('rules', 'running', 'Cargando familias y reglas de detección.', [], 38);
        $matcher = new ActivityMatcher();
        $rules = $matcher->loadRules();
        $existingFamilyIds = $this->existingListadoFamilyIds($projectId, $semana, $matcher, $rules);
        $this->traceStep('rules', 'done', 'Reglas de detección disponibles.', [
            'reglas' => count($rules),
        ], 48);

        $this->traceStep('matches', 'running', 'Agrupando actividades por alcance, familia y modalidad.', [], 55);
        $groups = [];
        $suggestions = [];
        $qualityGate = $this->qualityGate();

        foreach ($activities as $activity) {
            $consecutivo = (string) ($activity['unique_id'] ?? $activity['Consecutivo_en_Programa'] ?? '');
            if ($consecutivo === '' || isset($existingKeys[$consecutivo])) {
                continue;
            }

            $match = $matcher->matchActivity($activity, $rules);
            if ($match === null) {
                $quality = $qualityGate->noMatch($activity, 'El programa general no coincide con una familia configurada.');
                $suggestions[] = $this->baseSuggestion(
                    'programa_consolidado',
                    $consecutivo,
                    'review_no_match',
                    0,
                    'Sin familia detectada',
                    $this->plainText($activity['Actividad'] ?? ''),
                    'El programa general no coincide con una familia configurada.',
                    ['programa' => $activity],
                    [],
                    [],
                    ['source' => 'programa_general', '_analysis' => $quality],
                    false,
                    null,
                    $this->analysisWithQuality(
                        $this->matchAnalysis(
                            'Programa General',
                            null,
                            'No hay evidencia suficiente para crear una actividad automática.',
                        ),
                        $quality,
                    ),
                );
                continue;
            }

            $dimensions = $qualityGate->groupingDimensions($activity, $match);
            if (!empty($match['contractual_only'])) {
                $quality = $qualityGate->listado(
                    [$activity],
                    $match,
                    $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
                    'Debe pasar a Contratos porque es compra, insumo, suministro, equipo o subpaquete.',
                );
                $suggestions[] = $this->baseSuggestion(
                    'programa_consolidado',
                    $consecutivo,
                    'review_contractual_item',
                    $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
                    'Va en contratos',
                    $this->plainText($activity['Actividad'] ?? ''),
                    'El cronograma contiene un elemento contractual, no una familia operativa de actividades.',
                    ['programa' => $activity],
                    [
                        'familia_operativa' => (string) ($match['familia_nombre'] ?? ''),
                        'paquetes_sugeridos' => $match['contractual_package_hints'] ?? [],
                    ],
                    [],
                    [
                        'action' => 'review_contractual_item',
                        'programa' => $activity,
                        'familia_id' => (int) ($match['familia_id'] ?? 0),
                        'paquetes_sugeridos' => $match['contractual_package_hints'] ?? [],
                        '_analysis' => $quality,
                    ],
                    false,
                    $this->matchSource($match),
                    $this->analysisWithQuality(
                        $this->matchAnalysis(
                            'Programa General',
                            $match,
                            'Se detectó un elemento contractual para revisar en Contratos, no para crear actividad.',
                            ['technical' => [
                                'family_classification' => $match['family_classification'] ?? '',
                                'contractual_package_hints' => $match['contractual_package_hints'] ?? [],
                            ]],
                        ),
                        $quality,
                    ),
                );
                continue;
            }
            $familyId = (int) ($match['familia_id'] ?? 0);
            if ($familyId > 0 && isset($existingFamilyIds[$familyId])) {
                continue;
            }
            $groupKey = $qualityGate->operationalGroupingKey($activity, $match);
            if (!isset($groups[$groupKey])) {
                $requiresReview = $this->matchRequiresReview($match);
                $groups[$groupKey] = [
                    'match' => $match,
                    'items' => [],
                    'confidence' => $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
                    'review_required' => $requiresReview,
                    'review_reason' => $requiresReview ? (string) ($match['reviewReason'] ?? 'Requiere revisión manual.') : '',
                ];
            }
            $groups[$groupKey]['items'][] = $activity;
            $groups[$groupKey]['confidence'] = min(
                $groups[$groupKey]['confidence'],
                $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
            );
            if ($this->matchRequiresReview($match)) {
                $groups[$groupKey]['review_required'] = true;
                $groups[$groupKey]['review_reason'] = (string) ($match['reviewReason'] ?? 'Requiere revisión manual.');
            }
        }

        $this->traceStep('matches', 'done', 'Coincidencias revisadas y agrupadas.', [
            'familias_detectadas' => count($groups),
            'sin_match' => count($suggestions),
        ], 68);
        $this->traceStep('suggestions', 'running', 'Construyendo propuestas de actividades.', [], 74);
        $familyGroupCounts = [];
        foreach ($groups as $group) {
            $familyId = (int) ($group['match']['familia_id'] ?? 0);
            $familyGroupCounts[$familyId] = ($familyGroupCounts[$familyId] ?? 0) + 1;
        }

        foreach ($groups as $group) {
            usort($group['items'], static function (array $a, array $b): int {
                return strcmp((string) ($a['Fecha_Inicio'] ?? ''), (string) ($b['Fecha_Inicio'] ?? ''));
            });
            $anchor = $group['items'][0];
            $match = $group['match'];
            if (!empty($group['review_required'])) {
                $match['reviewRequired'] = true;
                $match['reviewReason'] = $group['review_reason'] ?: 'Requiere revisión manual.';
            }
            $descriptions = [];
            foreach ($group['items'] as $item) {
                $name = $this->plainText($item['Actividad'] ?? '');
                if ($name !== '' && !in_array($name, $descriptions, true)) {
                    $descriptions[] = $name;
                }
            }

            $quality = $qualityGate->listado(
                $group['items'],
                $match,
                $group['confidence'],
                $group['review_reason'] ?: null,
            );
            $selectedSource = $quality['sources'][0] ?? [];
            $selectedSourceId = (int) ($selectedSource['unique_id'] ?? 0);
            $tipoContrato = $this->resolveTipoContratoForFamily((int) $match['familia_id']);
            $activityName = $qualityGate->activityName($group['items'], $match, $quality, false);
            $isRepeatedFamily = ($familyGroupCounts[(int) ($match['familia_id'] ?? 0)] ?? 0) > 1;
            if ($isRepeatedFamily) {
                $activityName = $qualityGate->activityName($group['items'], $match, $quality, true);
            }
            if ($isRepeatedFamily && !$qualityGate->isSpecificName($activityName, $match)) {
                $quality = $qualityGate->withReviewReason(
                    $quality,
                    'La familia aparece en varias propuestas por diferencias operativas; validar que no sean solo frentes, zonas o intervenciones contractuales.',
                );
            }
            $proposed = [
                'actividad' => $activityName,
                'descripcionActividad' => $qualityGate->description($group['items'], $match, $quality),
                'actividadInicio' => $selectedSourceId ?: (int) ($anchor['unique_id'] ?? $anchor['Consecutivo_en_Programa'] ?? 0),
                'fechaInicio' => $this->normalizeDate($selectedSource['start_date'] ?? ($anchor['Fecha_Inicio'] ?? null)),
                'tipoContrato' => $tipoContrato,
                'semanaActualizacion' => $semana,
            ];
            $applyFields = $proposed;
            $preselected = $this->shouldPreselect($group['confidence'], $config, $match)
                && ($quality['quality_gate']['status'] ?? 'conflict') === 'ready';

            $analysis = $this->analysisWithQuality(
                $this->matchAnalysis(
                    'Programa General',
                    $match,
                    'Se agruparon ' . count($group['items']) . ' actividades bajo un alcance auditado.',
                    ['technical' => [
                        'items_agrupados' => count($group['items']),
                        'descripciones_fuente' => $descriptions,
                        'familia_repetida' => $isRepeatedFamily,
                        'nombre_especifico' => $qualityGate->isSpecificName($activityName, $match),
                        'grouping_key_version' => 'listado_operativo_v2',
                        'grouping_dimensions' => $quality['quality_gate']['dimensions'] ?? [],
                    ]],
                ),
                $quality,
            );
            $analysis['selected_source_id'] = $selectedSourceId;
            $analysis['selected_source'] = $selectedSource;

            $suggestions[] = $this->baseSuggestion(
                'actividades',
                null,
                'create_activity',
                $group['confidence'],
                'Crear actividad consolidada',
                $proposed['actividad'],
                'Familia detectada: ' . (string) ($match['familia_nombre'] ?? ''),
                ['programa_items' => $group['items']],
                $proposed,
                $this->buildDiff([], $proposed),
                [
                    'action' => 'create_activity',
                    'fields' => $applyFields,
                    'selectedSourceId' => $selectedSourceId,
                    'familia_id' => (int) $match['familia_id'],
                    '_analysis' => $quality,
                ],
                $preselected,
                $this->matchSource($match),
                $analysis,
            );
        }

        $this->traceStep('suggestions', 'done', 'Propuestas de listado generadas.', [
            'propuestas' => count($suggestions),
        ], 84);

        $suggestions = $this->reviewCoPresentListadoFamilies($suggestions);

        return $this->reviewRepeatedActivityNames($suggestions);
    }

    private function leafProgramActivitiesWithBreadcrumb(array $programRows): array
    {
        $chapterMap = [];
        foreach ($programRows as $row) {
            if ((int) ($row['Titulo'] ?? 0) !== 0) {
                $id = trim((string) ($row['Id'] ?? ''));
                $chapterName = $this->programLeafName((string) ($row['Actividad'] ?? ''));
                if ($id !== '' && $chapterName !== '') {
                    $chapterMap[$id] = $chapterName;
                }
            }
        }

        $leaves = [];
        $currentChapter = '';
        foreach ($programRows as $row) {
            if ((int) ($row['Titulo'] ?? 0) !== 0) {
                $currentChapter = $this->programLeafName((string) ($row['Actividad'] ?? '')) ?: $currentChapter;
                continue;
            }

            $existingBreadcrumb = $this->programExistingBreadcrumb((string) ($row['Actividad'] ?? ''));
            $breadcrumb = $existingBreadcrumb !== ''
                ? $existingBreadcrumb
                : $this->programBreadcrumb((string) ($row['Id'] ?? ''), $chapterMap);
            if ($breadcrumb === '') {
                $breadcrumb = $currentChapter;
            }

            $row['__capitulo'] = $breadcrumb;
            $leaves[] = $row;
        }

        return $leaves;
    }

    private function programBreadcrumb(string $id, array $chapterMap): string
    {
        $id = trim($id);
        if ($id === '' || !str_contains($id, '.')) {
            return '';
        }

        $parts = explode('.', $id);
        $levels = [];
        $current = '';
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $current = $current === '' ? $parts[$i] : $current . '.' . $parts[$i];
            if (isset($chapterMap[$current])) {
                $levels[] = $chapterMap[$current];
            }
        }

        return implode(', ', array_reverse($levels));
    }

    private function programLeafName(string $html): string
    {
        $text = $this->plainText($html);
        $pos = mb_stripos($text, '[Capítulo');
        if ($pos === false) {
            $pos = mb_stripos($text, '[Capitulo');
        }
        if ($pos !== false) {
            $text = mb_substr($text, 0, $pos);
        }

        return trim(rtrim(trim($text), ','));
    }

    private function programExistingBreadcrumb(string $html): string
    {
        if (preg_match('/\[Cap[ií]tulo[:\s]*([^\]]+)\]/ui', $html, $matches) === 1) {
            return trim($this->plainText($matches[1]));
        }

        return '';
    }

    private function buildContratosSuggestions(int $projectId, int $semana, array $config): array
    {
        $this->traceStep('data', 'running', 'Leyendo actividades pendientes de contrato.', [], 18);
        $packageFilter = $this->contractPackagePresenceSql();
        $activities = $this->db->query(
            "SELECT *
             FROM actividades
             WHERE project_id = ? AND semanaActualizacion = ?
               AND (
                 ((tipoContrato IS NULL OR tipoContrato = '') AND NOT ($packageFilter))
                 OR (tipoContrato IS NOT NULL AND tipoContrato != '' AND ultimo_auto_definir IS NULL)
               )
             ORDER BY Id ASC",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_ASSOC);
        $this->traceStep('data', 'done', 'Actividades candidatas cargadas.', [
            'actividades' => count($activities),
        ], 32);

        $this->traceStep('rules', 'running', 'Cargando reglas y opciones de contrato.', [], 38);
        $matcher = new ActivityMatcher();
        $rules = $matcher->loadRules();
        $optionsByFamily = $this->loadFamilyContractOptions();
        $this->traceStep('rules', 'done', 'Reglas y opciones listas.', [
            'reglas' => count($rules),
            'familias_con_opciones' => count($optionsByFamily),
        ], 48);

        $this->traceStep('matches', 'running', 'Cruzando actividades con Programa General y familias.', [], 55);
        $suggestions = [];
        $matchedActivities = 0;
        $qualityGate = $this->qualityGate();

        foreach ($activities as $activity) {
            $activityId = (int) $activity['Id'];
            if (!empty($activity['tipoContrato']) && !empty($activity['ultimo_auto_definir'])) {
                continue;
            }

            $pgActivity = $this->loadLinkedProgramActivity($projectId, $semana, (string) ($activity['actividadInicio'] ?? ''));
            $programSources = $this->loadActivityProgramSources($projectId, $activityId, $semana);
            $sourceProgramActivities = $this->sourcesToProgramActivities($programSources);
            $contractSourceCount = $this->contractSourceGroupCount($programSources);
            if ($pgActivity === null && !empty($sourceProgramActivities)) {
                $pgActivity = $sourceProgramActivities[0];
            }
            if ($pgActivity === null) {
                $quality = $qualityGate->noMatch(
                    ['Actividad' => $activity['actividad'] ?? '', 'unique_id' => $activity['actividadInicio'] ?? 0, 'Fecha_Inicio' => $activity['fechaInicio'] ?? null],
                    'La actividad no tiene vínculo válido al Programa General.',
                );
                $suggestions[] = $this->baseSuggestion(
                    'actividades',
                    (string) $activityId,
                    'review_no_match',
                    0,
                    'Sin actividad vinculada',
                    (string) ($activity['actividad'] ?? ''),
                    'La actividad no tiene vínculo válido al Programa General.',
                    $activity,
                    [],
                    [],
                    ['activity_id' => $activityId, '_analysis' => $quality],
                    false,
                    null,
                    $this->analysisWithQuality(
                        $this->matchAnalysis('Contratos', null, 'No hay vínculo auditable al Programa General.'),
                        $quality,
                    ),
                );
                continue;
            }

            $match = $matcher->matchActivity($pgActivity, $rules);
            if ($match === null) {
                $contractual = $this->catalogStatusResolver()->findContractualElementForText(implode(' ', [
                    (string) ($activity['actividad'] ?? ''),
                    (string) ($pgActivity['Actividad'] ?? ''),
                    (string) ($pgActivity['__capitulo'] ?? ''),
                ]));
                if ($contractual !== null) {
                    $catalogStatus = $contractual['catalog_status'] ?? $this->catalogStatusResolver()->statusForContractualElement($contractual);
                    $quality = $qualityGate->noMatch($pgActivity, (string) ($catalogStatus['reason'] ?? 'Se gestiona desde Contratos.'));
                    $analysis = $this->analysisWithQuality(
                        $this->matchAnalysis(
                            'Actividad vinculada al Programa General',
                            null,
                            (string) ($catalogStatus['reason'] ?? 'Se gestiona desde Contratos.'),
                            ['user' => [
                                'rule' => (string) ($catalogStatus['label'] ?? 'Se gestiona en Contratos'),
                                'decision' => (string) ($catalogStatus['next_action'] ?? 'Asignar paquete desde Contratos.'),
                            ]],
                        ),
                        $quality,
                    );
                    $analysis['catalog_status'] = $catalogStatus;
                    $analysis['contractual_element'] = [
                        'nombre' => (string) ($contractual['nombre'] ?? ''),
                        'tipo_paquete' => (string) ($contractual['tipo_paquete'] ?? ''),
                        'paquete_nombre' => (string) ($contractual['paquete_nombre'] ?? ''),
                    ];
                    $suggestions[] = $this->baseSuggestion(
                        'actividades',
                        (string) $activityId,
                        'review_no_match',
                        0,
                        (string) ($catalogStatus['label'] ?? 'Se gestiona en Contratos'),
                        (string) ($activity['actividad'] ?? ''),
                        (string) ($catalogStatus['reason'] ?? 'Se gestiona desde Contratos.'),
                        $activity,
                        [],
                        [],
                        ['activity_id' => $activityId, 'catalog_status' => $catalogStatus, '_analysis' => $quality],
                        false,
                        null,
                        $analysis,
                    );
                    continue;
                }
                $quality = $qualityGate->noMatch($pgActivity, 'No se encontró familia para sugerir contratos.');
                $suggestions[] = $this->baseSuggestion(
                    'actividades',
                    (string) $activityId,
                    'review_no_match',
                    0,
                    'Sin familia detectada',
                    (string) ($activity['actividad'] ?? ''),
                    'No se encontró familia para sugerir contratos.',
                    $activity,
                    [],
                    [],
                    ['activity_id' => $activityId, '_analysis' => $quality],
                    false,
                    null,
                    $this->analysisWithQuality(
                        $this->matchAnalysis('Actividad vinculada al Programa General', null, 'No hay familia contractual confiable.'),
                        $quality,
                    ),
                );
                continue;
            }

            $family = $optionsByFamily[(int) $match['familia_id']] ?? null;
            $bestOption = $family ? $this->selectBestContratoOption($family['opciones'], $match) : null;
            $policyPackages = $this->policyContractPackagesForActivity($activity, $pgActivity, $programSources);
            $configuredPackages = $bestOption !== null ? $this->formatAssignedPackages($bestOption['items']) : [];
            $packages = $this->mergeContractPackages($configuredPackages, $policyPackages);
            if ($bestOption === null && empty($packages)) {
                $catalogStatus = $this->catalogStatusResolver()->statusForFamily([
                    'id' => (int) ($match['familia_id'] ?? 0),
                    'codigo' => (string) ($match['familia_codigo'] ?? ''),
                    'nombre' => (string) ($match['familia_nombre'] ?? ''),
                    'categoria' => (string) ($match['categoria'] ?? ''),
                    'siempre_revision' => (int) ($match['siempre_revision'] ?? 0),
                    'activa' => 1,
                ]);
                $quality = $qualityGate->contratos(
                    $activity,
                    $pgActivity,
                    $match,
                    [],
                    $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
                    'La familia detectada no tiene paquetes configurados.',
                );
                $suggestions[] = $this->baseSuggestion(
                    'actividades',
                    (string) $activityId,
                    'review_no_match',
                    $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0),
                    'Familia sin opción de contrato',
                    (string) ($activity['actividad'] ?? ''),
                    'La familia detectada no tiene paquetes configurados.',
                    $activity,
                    [],
                    [],
                    ['activity_id' => $activityId, '_analysis' => $quality],
                    false,
                    $this->matchSource($match),
                    array_merge($this->analysisWithQuality(
                        $this->matchAnalysis(
                            'Actividad vinculada al Programa General',
                            $match,
                            (string) ($catalogStatus['reason'] ?? 'La familia existe, pero no tiene una opción de contrato aplicable.'),
                        ),
                        $quality,
                    ), ['catalog_status' => $catalogStatus]),
                );
                continue;
            }

            $matchedActivities++;
            $confidence = $this->normalizeConfidence($match['confidence'] ?? $match['confianza'] ?? 0);
            $tipoContrato = $bestOption !== null ? $this->intToModalityCode((int) $bestOption['tipo_contrato']) : '';
            $proposed = $this->contractProposedFields($activity, $tipoContrato, $packages, $confidence, $contractSourceCount);
            $current = $this->contractCurrentFields($activity);
            $diff = $this->buildDiff($current, $proposed);
            if (empty($diff)) {
                continue;
            }
            $quality = $qualityGate->contratos($activity, $pgActivity, $match, $packages, $confidence);
            if (!empty($programSources)) {
                $quality['sources'] = $programSources;
                $quality['quality_gate']['source_count'] = count($programSources);
            }
            $catalogStatus = $this->catalogStatusResolver()->statusForFamily([
                'id' => (int) ($match['familia_id'] ?? 0),
                'codigo' => (string) ($match['familia_codigo'] ?? ''),
                'nombre' => (string) ($match['familia_nombre'] ?? ''),
                'categoria' => (string) ($match['categoria'] ?? ''),
                'siempre_revision' => (int) ($match['siempre_revision'] ?? 0),
                'activa' => 1,
            ]);
            $preselected = $this->shouldPreselect($confidence, $config, $match)
                && ($quality['quality_gate']['status'] ?? 'conflict') === 'ready';

            $suggestions[] = $this->baseSuggestion(
                'actividades',
                (string) $activityId,
                'update_contracts',
                $confidence,
                'Definir contratos',
                (string) ($activity['actividad'] ?? ''),
                'Familia detectada: ' . (string) ($match['familia_nombre'] ?? ''),
                $current,
                $proposed,
                $diff,
                [
                    'action' => 'update_contracts',
                    'activity_id' => $activityId,
                    'fields' => $proposed,
                    'paquetes' => $packages,
                    'catalog_status' => $catalogStatus,
                    '_analysis' => $quality,
                ],
                $preselected,
                $this->matchSource($match),
                array_merge($this->analysisWithQuality(
                    $this->matchAnalysis(
                        'Actividad vinculada al Programa General',
                        $match,
                        'Se seleccionó la mejor opción de contrato configurada para la familia.',
                        ['technical' => [
                            'option_id' => $bestOption['optionId'] ?? null,
                            'paquetes' => $packages,
                            'paquetes_desde_politica' => $policyPackages,
                            'fuentes_programa' => count($programSources),
                            'grupos_contractuales_detectados' => $contractSourceCount,
                        ]],
                    ),
                    $quality,
                ), ['catalog_status' => $catalogStatus]),
            );
        }

        $this->traceStep('matches', 'done', 'Cruce de contratos terminado.', [
            'actividades_con_match' => $matchedActivities,
            'propuestas_o_conflictos' => count($suggestions),
        ], 72);
        $this->traceStep('suggestions', 'done', 'Propuestas de contratos generadas.', [
            'propuestas' => count($suggestions),
        ], 84);

        return $suggestions;
    }

    private function buildPdcSuggestions(int $projectId, int $semana, array $config): array
    {
        $this->traceStep('data', 'running', 'Leyendo contratos y paquetes existentes en PDC.', [], 18);
        $packageFilter = $this->contractPackagePresenceSql();
        $activities = $this->db->query(
            "SELECT *
             FROM actividades
             WHERE project_id = ? AND semanaActualizacion = ?
               AND ((tipoContrato IS NOT NULL AND tipoContrato != '') OR $packageFilter)
             ORDER BY fechaInicio ASC, Id ASC",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_ASSOC);

        $existing = $this->loadExistingPdcPackages($projectId, $semana);
        $this->traceStep('data', 'done', 'Datos de Contratos y PDC cargados.', [
            'contratos_disponibles' => count($activities),
            'paquetes_pdc_existentes' => count($existing),
        ], 34);
        $this->traceStep('rules', 'done', 'Reglas de fechas y paquetes listas.', [
            'duraciones' => count($this->defaultDurations()),
        ], 48);
        $this->traceStep('matches', 'running', 'Comparando paquetes de Contratos contra PDC.', [], 58);
        $suggestions = [];
        $packagesSeen = 0;
        $queuedPackageKeys = [];
        $qualityGate = $this->qualityGate();

        foreach ($activities as $activity) {
            $packages = $this->packagesFromActivity($activity);
            foreach ($packages as $package) {
                $packagesSeen++;
                $key = $this->pdcPackageKey($package['tipoPaquete'], $package['paqueteNombre']);
                if (isset($queuedPackageKeys[$key])) {
                    continue;
                }
                $queuedPackageKeys[$key] = true;
                $confidence = $this->normalizeConfidence($activity['confianza_deteccion'] ?? 80);
                $durations = $this->pdcDurationsForPackage($package);
                $dates = $this->calculateProcessDates($this->normalizeDate($activity['fechaInicio'] ?? null), $durations);
                $requiresReview = $this->pdcPackageRequiresReview($package, $activity);
                $observations = $this->pdcObservationForPackage($package, $activity);
                $baseFields = array_merge($dates, [
                    'semana' => $semana,
                    'titulo' => 0,
                    'tipoPaquete' => $package['tipoPaquete'],
                    'paqueteContratacion' => $package['paqueteNombre'],
                    'contratos' => $this->limitDbText((string) ($activity['actividad'] ?? ''), 200),
                    'numeroSubcontratos' => max(1, (int) ($package['cantidadContratos'] ?? ($activity['numeroSubcontratos'] ?? 1))),
                    'estado' => $this->pdcStatusForPackage($package, $activity),
                    'observacionesContrato' => $observations,
                    'fechaInicio' => $this->normalizeDate($activity['fechaInicio'] ?? null),
                    'fechaInicioProyectada' => $this->normalizeDate($activity['fechaInicioProyectada'] ?? ($activity['fechaInicio'] ?? null)),
                ]);

                if (!isset($existing[$key])) {
                    $quality = $qualityGate->pdc(
                        $activity,
                        $package,
                        null,
                        $this->buildDiff([], $baseFields),
                        $confidence,
                        $requiresReview ? $this->pdcReviewReasonForPackage($package, $activity) : null,
                    );
                    $suggestions[] = $this->baseSuggestion(
                        'pdc',
                        null,
                        'create_pdc_package',
                        $confidence,
                        'Crear paquete PDC',
                        $package['paqueteNombre'],
                        'Paquete asignado en Contratos y ausente en PDC.',
                        ['actividad' => $this->contractCurrentFields($activity)],
                        $baseFields,
                        $this->buildDiff([], $baseFields),
                        [
                            'action' => 'create_pdc_package',
                            'fields' => $baseFields,
                            '_analysis' => $quality,
                        ],
                        $this->isPreselected($confidence, $config)
                            && ($quality['quality_gate']['status'] ?? 'conflict') === 'ready',
                        $requiresReview ? 'contratos:revision' : 'contratos',
                        $this->analysisWithQuality(
                            $this->matchAnalysis(
                                'Contratos',
                                null,
                                'El paquete está asignado en Contratos y no existe en PDC.',
                                ['user' => ['rule' => $requiresReview ? 'Paquete por confirmar' : 'Paquete ausente en PDC', 'confidence' => $confidence]],
                            ),
                            $quality,
                        ),
                    );
                    continue;
                }

                $pdcRow = $existing[$key];
                $current = $this->pdcComparableFields($pdcRow);
                $proposed = array_intersect_key($baseFields, $current);
                $diff = $this->buildDiff($current, $proposed);
                if (empty($diff)) {
                    continue;
                }
                $quality = $qualityGate->pdc(
                    $activity,
                    $package,
                    $pdcRow,
                    $diff,
                    $confidence,
                    $requiresReview ? $this->pdcReviewReasonForPackage($package, $activity) : null,
                );

                $suggestions[] = $this->baseSuggestion(
                    'pdc',
                    (string) ($pdcRow['pdc_row_id'] ?? $pdcRow['consecutivo']),
                    'update_pdc_package',
                    $confidence,
                    'Actualizar paquete PDC',
                    $package['paqueteNombre'],
                    'El paquete ya existe; revisar diferencias antes de actualizar.',
                    $current,
                    $proposed,
                    $diff,
                    [
                        'action' => 'update_pdc_package',
                        'pdc_row_id' => (int) ($pdcRow['pdc_row_id'] ?? $pdcRow['consecutivo']),
                        'consecutivo' => (int) $pdcRow['consecutivo'],
                        'fields' => $proposed,
                        '_analysis' => $quality,
                    ],
                    $this->isPreselected($confidence, $config)
                        && ($quality['quality_gate']['status'] ?? 'conflict') === 'ready',
                    $requiresReview ? 'pdc_diff:revision' : 'pdc_diff',
                    $this->analysisWithQuality(
                        $this->matchAnalysis(
                            'Contratos contra PDC',
                            null,
                            'El paquete ya existe y se encontraron diferencias actualizables.',
                            ['user' => ['rule' => $requiresReview ? 'Diferencia por confirmar' : 'Diferencia contra PDC existente', 'confidence' => $confidence]],
                        ),
                        $quality,
                    ),
                );
            }
        }

        $this->traceStep('matches', 'done', 'Comparación de paquetes terminada.', [
            'paquetes_contratos' => $packagesSeen,
            'paquetes_existentes' => count($existing),
        ], 72);
        $this->traceStep('suggestions', 'done', 'Propuestas de PDC generadas.', [
            'propuestas' => count($suggestions),
        ], 84);

        return $suggestions;
    }

    private function applySuggestion(string $module, int $projectId, int $semana, array $suggestion): array
    {
        $payload = $this->decodeJson($suggestion['apply_payload']);
        $qualityStatus = (string) ($payload['_analysis']['quality_gate']['status'] ?? '');
        if ($qualityStatus === 'conflict') {
            throw new RuntimeException('La sugerencia tiene conflictos de calidad y requiere revisión manual.');
        }
        $action = $payload['action'] ?? $suggestion['action'];

        return match ($action) {
            'create_activity' => $this->applyCreateActivity($projectId, $semana, $payload),
            'update_contracts' => $this->applyUpdateContracts($projectId, $semana, $payload),
            'create_pdc_package' => $this->applyCreatePdcPackage($projectId, $semana, $payload),
            'update_pdc_package' => $this->applyUpdatePdcPackage($projectId, $semana, $payload),
            default => throw new RuntimeException('La sugerencia seleccionada no es aplicable.'),
        };
    }

    private function reviewRepeatedActivityNames(array $suggestions): array
    {
        $nameCounts = [];
        foreach ($suggestions as $suggestion) {
            if (($suggestion['action'] ?? '') !== 'create_activity') {
                continue;
            }
            $name = trim((string) ($suggestion['proposed_payload']['actividad'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name, 'UTF-8');
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }

        foreach ($suggestions as &$suggestion) {
            if (($suggestion['action'] ?? '') !== 'create_activity') {
                continue;
            }
            $name = trim((string) ($suggestion['proposed_payload']['actividad'] ?? ''));
            $key = mb_strtolower($name, 'UTF-8');
            if ($name === '' || ($nameCounts[$key] ?? 0) <= 1) {
                continue;
            }
            $suggestion['preselected'] = false;
            $analysis = $suggestion['analysis_payload'] ?? [];
            $currentStatus = (string) ($analysis['quality_gate']['status'] ?? '');
            $analysis['quality_gate']['status'] = $currentStatus === 'conflict' ? 'conflict' : 'review';
            $analysis['quality_gate']['definition_status'] = 'por_confirmar';
            $analysis['quality_gate']['score'] = min((int) ($analysis['quality_gate']['score'] ?? 79), 79);
            $analysis['quality_gate']['review_reasons'][] = 'El nombre propuesto se repite en este preview; validar si debe agruparse con otra propuesta o afinar el alcance.';
            $analysis['quality_gate']['review_reasons'] = array_values(array_unique($analysis['quality_gate']['review_reasons']));
            $analysis['technical']['nombre_repetido_preview'] = true;
            $analysis['technical']['repeticiones_nombre'] = $nameCounts[$key];
            $suggestion['analysis_payload'] = $analysis;
            if (is_array($suggestion['apply_payload'] ?? null)) {
                $suggestion['apply_payload']['_analysis'] = $analysis;
            }
        }
        unset($suggestion);

        return $suggestions;
    }

    private function reviewCoPresentListadoFamilies(array $suggestions): array
    {
        $familiesToReviewTogether = ['RED_TELECOMUNICACIONES', 'SEGURIDAD_CONTROL'];
        $present = [];
        foreach ($suggestions as $suggestion) {
            if (($suggestion['action'] ?? '') !== 'create_activity') {
                continue;
            }
            $familyCode = (string) ($suggestion['analysis_payload']['technical']['familia_codigo'] ?? '');
            if (in_array($familyCode, $familiesToReviewTogether, true)) {
                $present[$familyCode] = true;
            }
        }

        foreach ($familiesToReviewTogether as $familyCode) {
            if (empty($present[$familyCode])) {
                return $suggestions;
            }
        }

        $reason = 'El programa contiene Telecomunicaciones y Seguridad y Control; confirmar separación de alcance antes de aplicar.';
        foreach ($suggestions as &$suggestion) {
            if (($suggestion['action'] ?? '') !== 'create_activity') {
                continue;
            }
            $familyCode = (string) ($suggestion['analysis_payload']['technical']['familia_codigo'] ?? '');
            if (!in_array($familyCode, $familiesToReviewTogether, true)) {
                continue;
            }
            $suggestion['preselected'] = false;
            $analysis = $suggestion['analysis_payload'] ?? [];
            $currentStatus = (string) ($analysis['quality_gate']['status'] ?? '');
            $analysis['quality_gate']['status'] = $currentStatus === 'conflict' ? 'conflict' : 'review';
            $analysis['quality_gate']['definition_status'] = 'por_confirmar';
            $analysis['quality_gate']['score'] = min((int) ($analysis['quality_gate']['score'] ?? 79), 79);
            $analysis['quality_gate']['review_reasons'][] = $reason;
            $analysis['quality_gate']['review_reasons'] = array_values(array_unique($analysis['quality_gate']['review_reasons']));
            $analysis['technical']['familias_copresentes_revision'] = $familiesToReviewTogether;
            $suggestion['analysis_payload'] = $analysis;
            if (is_array($suggestion['apply_payload'] ?? null)) {
                $suggestion['apply_payload']['_analysis'] = $analysis;
            }
        }
        unset($suggestion);

        return $suggestions;
    }

    private function existingListadoFamilyIds(int $projectId, int $semana, ActivityMatcher $matcher, array $rules): array
    {
        $rows = $this->db->query(
            "SELECT actividad
             FROM actividades
             WHERE project_id = ? AND semanaActualizacion = ?",
            [$projectId, $semana],
        )->fetchAll(PDO::FETCH_ASSOC);

        $familyIds = [];
        foreach ($rows as $row) {
            $activity = trim((string) ($row['actividad'] ?? ''));
            if ($activity === '') {
                continue;
            }
            $match = $matcher->matchActivity(['Actividad' => $activity], $rules);
            $familyId = (int) ($match['familia_id'] ?? 0);
            if ($familyId > 0 && empty($match['contractual_only'])) {
                $familyIds[$familyId] = true;
            }
        }

        return $familyIds;
    }

    private function applyCreateActivity(int $projectId, int $semana, array $payload): array
    {
        $fields = $payload['fields'] ?? [];
        $sources = $payload['_analysis']['sources'] ?? [];
        $nextId = $this->nextProjectId($projectId, 'actividades', 'Id');
        $nextCodigo = (int) $this->db->query(
            "SELECT COALESCE(MAX(codigo), 0) + 1 FROM actividades WHERE project_id = ?",
            [$projectId],
        )->fetchColumn();
        $nombreInicio = $this->programDisplayName($projectId, $semana, (int) ($fields['actividadInicio'] ?? 0));

        $this->db->query(
            "INSERT INTO actividades
             (project_id, Id, codigo, actividad, descripcionActividad, actividadInicio,
              nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $projectId,
                $nextId,
                $nextCodigo,
                $fields['actividad'] ?? '',
                $fields['descripcionActividad'] ?? '',
                $fields['actividadInicio'] ?? null,
                $nombreInicio,
                $fields['fechaInicio'] ?? null,
                $fields['tipoContrato'] ?? null,
                $semana,
            ],
        );

        if (is_array($sources) && !empty($sources)) {
            $this->replaceActivityProgramSources($projectId, $nextId, $semana, $sources);
        }

        $after = $this->loadActivity($projectId, $nextId, $semana);

        return [
            'before' => null,
            'after' => $after,
            'result' => ['table' => 'actividades', 'Id' => $nextId],
        ];
    }

    private function applyUpdateContracts(int $projectId, int $semana, array $payload): array
    {
        $activityId = (int) ($payload['activity_id'] ?? 0);
        if ($activityId <= 0) {
            throw new RuntimeException('Actividad inválida.');
        }

        $before = $this->loadActivity($projectId, $activityId, $semana);
        if (!$before) {
            throw new RuntimeException('Actividad no encontrada.');
        }

        $fields = $payload['fields'] ?? [];
        $allowed = $this->contractFieldNames();
        $updates = [];
        $params = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $fields)) {
                continue;
            }
            $updates[] = "`{$field}` = ?";
            $params[] = $fields[$field] === '' ? null : $fields[$field];
        }
        $updates[] = "ultimo_auto_definir = NOW()";
        $params[] = $projectId;
        $params[] = $activityId;
        $params[] = $semana;
        $this->db->query(
            "UPDATE actividades SET " . implode(', ', $updates) . "
             WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?",
            $params,
        );

        return [
            'before' => $before,
            'after' => $this->loadActivity($projectId, $activityId, $semana),
            'result' => ['table' => 'actividades', 'Id' => $activityId],
        ];
    }

    private function applyCreatePdcPackage(int $projectId, int $semana, array $payload): array
    {
        $fields = $payload['fields'] ?? [];
        $tipoPaquete = (string) ($fields['tipoPaquete'] ?? '');
        if ($tipoPaquete === '') {
            throw new RuntimeException('Tipo de paquete PDC inválido.');
        }

        $this->ensurePdcTitleRow($projectId, $semana, $tipoPaquete);
        $rowId = $this->nextProjectId($projectId, 'pdc', 'pdc_row_id');
        $subcontractIndex = $this->nextPdcSubcontractIndex($projectId, $semana, $tipoPaquete);

        $this->db->query(
            "INSERT INTO pdc (
                project_id, pdc_row_id, consecutivo, semana, titulo, tipoPaquete, paqueteContratacion, contratos,
                numeroSubcontratos, subcontratoPaquete, estado,
                fechaElaboracionPliegos, diasElaboracionPliegos,
                fechaEntregaPliegos, diasEntregaPliegos,
                fechaReciboPropuestas, diasReciboPropuestas,
                fechaCuadrosComparativos, diasCuadrosComparativos,
                fechaLegalizacionContrato, diasLegalizacionContrato,
                fechaFabricacion, diasFabricacion,
                fechaInsumosObra, diasInsumosObra,
                fechaInicio, fechaInicioProyectada, observacionesContrato
             ) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $projectId,
                $rowId,
                $rowId,
                $semana,
                $tipoPaquete,
                $fields['paqueteContratacion'] ?? '',
                $fields['contratos'] ?? '',
                $fields['numeroSubcontratos'] ?? 1,
                $subcontractIndex,
                $fields['estado'] ?? 'En Curso; Proceso de contratación no iniciado',
                $fields['fechaElaboracionPliegos'] ?? null,
                $fields['diasElaboracionPliegos'] ?? 8,
                $fields['fechaEntregaPliegos'] ?? null,
                $fields['diasEntregaPliegos'] ?? 10,
                $fields['fechaReciboPropuestas'] ?? null,
                $fields['diasReciboPropuestas'] ?? 1,
                $fields['fechaCuadrosComparativos'] ?? null,
                $fields['diasCuadrosComparativos'] ?? 10,
                $fields['fechaLegalizacionContrato'] ?? null,
                $fields['diasLegalizacionContrato'] ?? 10,
                $fields['fechaFabricacion'] ?? null,
                $fields['diasFabricacion'] ?? 0,
                $fields['fechaInsumosObra'] ?? null,
                $fields['diasInsumosObra'] ?? 0,
                $fields['fechaInicio'] ?? null,
                $fields['fechaInicioProyectada'] ?? null,
                $fields['observacionesContrato'] ?? null,
            ],
        );

        return [
            'before' => null,
            'after' => $this->loadPdcRow($projectId, $rowId),
            'result' => ['table' => 'pdc', 'pdc_row_id' => $rowId, 'consecutivo' => $rowId],
        ];
    }

    private function applyUpdatePdcPackage(int $projectId, int $semana, array $payload): array
    {
        $rowId = (int) ($payload['pdc_row_id'] ?? ($payload['consecutivo'] ?? 0));
        if ($rowId <= 0) {
            throw new RuntimeException('Paquete PDC inválido.');
        }

        $before = $this->loadPdcRow($projectId, $rowId);
        if (!$before) {
            throw new RuntimeException('Paquete PDC no encontrado.');
        }

        $fields = $payload['fields'] ?? [];
        $allowed = $this->pdcEditableFields();
        $updates = [];
        $params = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $fields)) {
                continue;
            }
            $updates[] = "`{$field}` = ?";
            $params[] = $fields[$field] === '' ? null : $fields[$field];
        }
        if (empty($updates)) {
            throw new RuntimeException('No hay cambios aplicables para PDC.');
        }
        $params[] = $projectId;
        $params[] = $rowId;
        $params[] = $semana;
        $this->db->query(
            "UPDATE pdc SET " . implode(', ', $updates) . "
             WHERE project_id = ? AND pdc_row_id = ? AND semana = ?",
            $params,
        );

        return [
            'before' => $before,
            'after' => $this->loadPdcRow($projectId, $rowId),
            'result' => ['table' => 'pdc', 'pdc_row_id' => $rowId, 'consecutivo' => (int) ($before['consecutivo'] ?? $rowId)],
        ];
    }

    private function undoDecision(string $module, int $projectId, int $semana, array $decision): void
    {
        $result = $this->decodeJson($decision['result_payload']);
        $before = $this->decodeJson($decision['before_payload']);
        $table = $result['table'] ?? '';

        if ($table === 'actividades' && ($result['Id'] ?? 0) > 0) {
            $activityId = (int) $result['Id'];
            if ($before === null) {
                $this->ensureActivityProgramSourcesTable();
                $this->db->query(
                    "DELETE FROM actividad_programa_fuentes WHERE project_id = ? AND actividad_id = ? AND semana = ?",
                    [$projectId, $activityId, $semana],
                );
                $this->db->query(
                    "DELETE FROM actividades WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?",
                    [$projectId, $activityId, $semana],
                );
                return;
            }
            $this->restoreRow('actividades', 'Id', $activityId, $projectId, $before);
            return;
        }

        if ($table === 'pdc' && (($result['pdc_row_id'] ?? 0) > 0 || ($result['consecutivo'] ?? 0) > 0)) {
            $rowId = (int) ($result['pdc_row_id'] ?? $result['consecutivo']);
            if ($before === null) {
                $this->db->query(
                    "DELETE FROM pdc WHERE project_id = ? AND pdc_row_id = ? AND semana = ?",
                    [$projectId, $rowId, $semana],
                );
                return;
            }
            $this->restoreRow('pdc', 'pdc_row_id', $rowId, $projectId, $before);
            return;
        }

        throw new RuntimeException('No se pudo deshacer la decisión.');
    }

    private function restoreRow(string $table, string $pkName, int $pk, int $projectId, array $row): void
    {
        $skip = ['project_id', $pkName];
        $updates = [];
        $params = [];
        foreach ($row as $field => $value) {
            if (in_array($field, $skip, true)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $field)) {
                continue;
            }
            $updates[] = "`{$field}` = ?";
            $params[] = $value;
        }
        $params[] = $projectId;
        $params[] = $pk;
        $this->db->query(
            "UPDATE {$table} SET " . implode(', ', $updates) . " WHERE project_id = ? AND {$pkName} = ?",
            $params,
        );
    }

    private function baseSuggestion(
        string $targetTable,
        ?string $targetPk,
        string $action,
        float $confidence,
        string $title,
        string $subtitle,
        string $reason,
        $current,
        $proposed,
        $diff,
        array $applyPayload,
        bool $preselected,
        ?string $matchSource = null,
        array $analysis = []
    ): array {
        return [
            'suggestion_id' => $this->newId('sug'),
            'target_table' => $targetTable,
            'target_pk' => $targetPk,
            'action' => $action,
            'confidence' => $this->normalizeConfidence($confidence),
            'title' => $title,
            'subtitle' => $subtitle,
            'reason' => $reason,
            'match_source' => $matchSource,
            'preselected' => $preselected,
            'current_payload' => $current,
            'proposed_payload' => $proposed,
            'diff_payload' => $diff,
            'apply_payload' => $applyPayload,
            'analysis_payload' => $analysis,
        ];
    }

    private function insertRun(string $runId, int $projectId, string $module, int $semana, array $config): void
    {
        $this->db->query(
            "INSERT INTO semi_auto_runs
             (run_id, project_id, module, semana, status, requested_by, metadata)
             VALUES (?, ?, ?, ?, 'previewing', ?, ?)",
            [
                $runId,
                $projectId,
                $module,
                $semana,
                $this->currentUser(),
                $this->jsonEncode(['thresholds' => $config]),
            ],
        );
    }

    private function insertSuggestion(string $runId, int $projectId, string $module, array $suggestion, array $config): void
    {
        $confidence = $this->normalizeConfidence($suggestion['confidence'] ?? 0);
        $applyPayload = (array) ($suggestion['apply_payload'] ?? []);
        if (!empty($suggestion['analysis_payload'])) {
            $applyPayload['_analysis'] = $suggestion['analysis_payload'];
        }
        $this->db->query(
            "INSERT INTO semi_auto_suggestions
             (suggestion_id, run_id, project_id, module, target_table, target_pk, action, status,
              confidence, confidence_band, title, reason, match_source, preselected,
              current_payload, proposed_payload, diff_payload, apply_payload)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'previewed', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $suggestion['suggestion_id'],
                $runId,
                $projectId,
                $module,
                $suggestion['target_table'],
                $suggestion['target_pk'],
                $suggestion['action'],
                $confidence,
                $this->confidenceBand($confidence, $config),
                $suggestion['title'],
                $suggestion['reason'],
                $suggestion['match_source'],
                !empty($suggestion['preselected']) ? 1 : 0,
                $this->jsonEncode($suggestion['current_payload']),
                $this->jsonEncode([
                    'title' => $suggestion['subtitle'],
                    'fields' => $suggestion['proposed_payload'],
                ]),
                $this->jsonEncode($suggestion['diff_payload']),
                $this->jsonEncode($applyPayload),
            ],
        );
    }

    private function formatRunResponse(string $runId, int $projectId, string $module): array
    {
        $run = $this->loadRun($runId, $projectId, $module);
        $stmt = $this->db->query(
            "SELECT *
             FROM semi_auto_suggestions
             WHERE run_id = ? AND project_id = ?",
            [$runId, $projectId],
        );

        $suggestions = [];
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        usort($rows, static function (array $a, array $b): int {
            return ((int) $b['preselected'] <=> (int) $a['preselected'])
                ?: ((float) $b['confidence'] <=> (float) $a['confidence'])
                ?: ((int) $a['id'] <=> (int) $b['id']);
        });
        foreach ($rows as $row) {
            $proposed = $this->decodeJson($row['proposed_payload']) ?: [];
            $applyPayload = $this->decodeJson($row['apply_payload']) ?: [];
            $suggestions[] = [
                'suggestion_id' => $row['suggestion_id'],
                'action' => $row['action'],
                'status' => $row['status'],
                'confidence' => (float) $row['confidence'],
                'confidence_band' => $row['confidence_band'],
                'title' => $row['title'],
                'subtitle' => $proposed['title'] ?? '',
                'reason' => $row['reason'],
                'match_source' => $row['match_source'],
                'preselected' => (int) $row['preselected'] === 1,
                'target_table' => $row['target_table'],
                'target_pk' => $row['target_pk'],
                'current' => $this->decodeJson($row['current_payload']),
                'proposed' => $proposed['fields'] ?? $proposed,
                'diff' => $this->decodeJson($row['diff_payload']),
                'analysis' => $this->suggestionAnalysis($row, $proposed, $applyPayload),
                'assistant_reasoning' => $this->assistantReasoning($row, $proposed, $applyPayload),
            ];
        }
        $analysis = $this->analysisFromRun($run);
        $assistant = $this->assistant()->previewContext($module, $projectId, (int) $run['semana'], $runId, $suggestions);

        return array_merge([
            'respuesta' => 'BIEN',
            'run_id' => $runId,
            'module' => $module,
            'semana' => (int) $run['semana'],
            'status' => $run['status'],
            'total' => count($suggestions),
            'preselected' => count(array_filter($suggestions, static fn(array $item): bool => $item['preselected'])),
            'analysis' => $analysis,
            'suggestions' => $suggestions,
        ], $assistant);
    }

    private function loadRun(string $runId, int $projectId, string $module): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM semi_auto_runs WHERE run_id = ? AND project_id = ? AND module = ? LIMIT 1",
            [$runId, $projectId, $module],
        );
        $run = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$run) {
            throw new RuntimeException('Corrida de automatización no encontrada.');
        }

        return $run;
    }

    private function loadSuggestionsForApply(string $runId, int $projectId, string $module, array $suggestionIds): array
    {
        if (empty($suggestionIds)) {
            return [];
        }

        $suggestionIds = array_values(array_unique(array_map('strval', $suggestionIds)));
        $placeholders = implode(',', array_fill(0, count($suggestionIds), '?'));
        $params = array_merge([$runId, $projectId, $module], $suggestionIds);
        $stmt = $this->db->query(
            "SELECT *
             FROM semi_auto_suggestions
             WHERE run_id = ? AND project_id = ? AND module = ?
               AND status = 'previewed'
               AND suggestion_id IN ({$placeholders})
             ORDER BY id ASC",
            $params,
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateSuggestionFromFeedback(
        int $projectId,
        string $module,
        string $runId,
        string $suggestionId,
        array $corrected
    ): bool {
        $stmt = $this->db->query(
            "SELECT * FROM semi_auto_suggestions
             WHERE project_id = ? AND module = ? AND run_id = ? AND suggestion_id = ? AND status = 'previewed'
             LIMIT 1",
            [$projectId, $module, $runId, $suggestionId],
        );
        $suggestion = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$suggestion) {
            return false;
        }

        $applyPayload = $this->decodeJson($suggestion['apply_payload']) ?: [];
        $proposed = $this->decodeJson($suggestion['proposed_payload']) ?: [];
        $fields = (array) ($proposed['fields'] ?? []);
        $allowed = array_flip($this->editableFieldsForAction((string) $suggestion['action']));
        foreach ($corrected as $field => $value) {
            if (!isset($allowed[$field])) {
                continue;
            }
            if ((string) $suggestion['action'] === 'create_activity' && $field === 'selectedSourceId') {
                $this->applySelectedSourceCorrection($fields, $applyPayload, (int) $value);
                continue;
            }
            if ((string) $suggestion['action'] === 'create_activity' && $field === 'tipoContrato') {
                $value = $this->normalizeListadoModalities($value);
            }
            $fields[$field] = $value === '' ? null : $value;
            if (isset($applyPayload['fields']) && is_array($applyPayload['fields'])) {
                $applyPayload['fields'][$field] = $fields[$field];
            }
        }

        $current = $this->decodeJson($suggestion['current_payload']) ?: [];
        $proposed['fields'] = $fields;
        $diff = $this->buildDiff((array) $current, $fields);
        $this->db->query(
            "UPDATE semi_auto_suggestions
             SET proposed_payload = ?, diff_payload = ?, apply_payload = ?, match_source = ?
             WHERE project_id = ? AND suggestion_id = ?",
            [
                $this->jsonEncode($proposed),
                $this->jsonEncode($diff),
                $this->jsonEncode($applyPayload),
                'feedback_project',
                $projectId,
                $suggestionId,
            ],
        );

        return true;
    }

    private function applySelectedSourceCorrection(array &$fields, array &$applyPayload, int $selectedSourceId): void
    {
        $sources = $applyPayload['_analysis']['sources'] ?? [];
        if (!is_array($sources) || empty($sources)) {
            throw new InvalidArgumentException('Esta propuesta no tiene fuentes disponibles para seleccionar.');
        }

        $selected = null;
        foreach ($sources as $source) {
            if ((int) ($source['unique_id'] ?? 0) === $selectedSourceId) {
                $selected = $source;
                break;
            }
        }
        if ($selected === null) {
            throw new InvalidArgumentException('La fuente seleccionada no pertenece a esta propuesta.');
        }

        $fields['actividadInicio'] = (int) ($selected['unique_id'] ?? 0);
        $fields['fechaInicio'] = $this->normalizeDate($selected['start_date'] ?? null);
        $applyPayload['selectedSourceId'] = $fields['actividadInicio'];
        $applyPayload['fields']['actividadInicio'] = $fields['actividadInicio'];
        $applyPayload['fields']['fechaInicio'] = $fields['fechaInicio'];
        $applyPayload['_analysis']['selected_source_id'] = $fields['actividadInicio'];
        $applyPayload['_analysis']['selected_source'] = $selected;
        $applyPayload['_analysis']['quality_gate']['start_activity_label'] = trim(
            (string) ($selected['activity'] ?? '') . ' | ' . (string) ($selected['start_date'] ?? 'Fecha por confirmar'),
        );
    }

    private function normalizeListadoModalities(mixed $value): string
    {
        $raw = is_array($value) ? implode(',', $value) : (string) $value;
        $aliases = [
            '1' => ['MO', 'S'],
            '2' => ['SI'],
            '3' => ['S'],
            '4' => ['MO'],
            '5' => ['OC'],
            'M_O' => ['MO'],
        ];
        $parts = [];
        foreach (preg_split('/\s*,\s*/', $raw) ?: [] as $part) {
            $part = strtoupper(trim($part));
            if ($part === '') {
                continue;
            }
            foreach ($aliases[$part] ?? [$part] as $normalized) {
                if (!in_array($normalized, ['SI', 'MO', 'S', 'OC'], true)) {
                    throw new InvalidArgumentException('Modalidad de contratación inválida.');
                }
                if (!in_array($normalized, $parts, true)) {
                    $parts[] = $normalized;
                }
            }
        }
        if (in_array('SI', $parts, true) && count($parts) > 1) {
            throw new InvalidArgumentException('Suministro e instalación no se puede combinar con otras modalidades.');
        }

        $order = ['SI', 'MO', 'S', 'OC'];
        usort($parts, static fn(string $a, string $b): int => array_search($a, $order, true) <=> array_search($b, $order, true));

        return implode(',', $parts);
    }

    private function editableFieldsForAction(string $action): array
    {
        return match ($action) {
            'create_activity' => [
                'actividad', 'descripcionActividad', 'selectedSourceId', 'tipoContrato',
            ],
            'update_contracts' => $this->contractFieldNames(),
            'create_pdc_package', 'update_pdc_package' => $this->pdcEditableFields(),
            default => [],
        };
    }

    private function recordDecision(
        int $projectId,
        string $module,
        string $runId,
        ?string $suggestionId,
        string $decision,
        $before,
        $after,
        $result
    ): void {
        $this->db->query(
            "INSERT INTO semi_auto_decisions
             (decision_id, run_id, suggestion_id, project_id, module, decision,
              before_payload, after_payload, result_payload, decided_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->newId('dec'),
                $runId,
                $suggestionId,
                $projectId,
                $module,
                $decision,
                $this->jsonEncode($before),
                $this->jsonEncode($after),
                $this->jsonEncode($result),
                $this->currentUser(),
            ],
        );
    }

    private function loadProjectConfig(int $projectId, string $module): array
    {
        $stmt = $this->db->query(
            "SELECT high_threshold, medium_threshold, learning_scope
             FROM semi_auto_project_config
             WHERE project_id = ? AND module = ?
             LIMIT 1",
            [$projectId, $module],
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'high_threshold' => (float) ($row['high_threshold'] ?? self::HIGH_CONFIDENCE),
            'medium_threshold' => (float) ($row['medium_threshold'] ?? self::MEDIUM_CONFIDENCE),
            'learning_scope' => $row['learning_scope'] ?? 'project',
        ];
    }

    private function moduleCoverage(string $module, int $projectId, int $semana): array
    {
        if ($module === self::MODULE_LISTADO) {
            $pgTotal = (int) $this->db->query(
                "SELECT COUNT(*) FROM programa_consolidado
                 WHERE project_id = ? AND Semana = ? AND COALESCE(Titulo, 0) = 0",
                [$projectId, $semana],
            )->fetchColumn();
            $listTotal = (int) $this->db->query(
                "SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?",
                [$projectId, $semana],
            )->fetchColumn();
            return ['pg_total' => $pgTotal, 'listado_total' => $listTotal];
        }

        if ($module === self::MODULE_CONTRATOS) {
            $total = (int) $this->db->query(
                "SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?",
                [$projectId, $semana],
            )->fetchColumn();
            $packageFilter = $this->contractPackagePresenceSql();
            $missing = (int) $this->db->query(
                "SELECT COUNT(*) FROM actividades
                 WHERE project_id = ?
                   AND semanaActualizacion = ?
                   AND (tipoContrato IS NULL OR tipoContrato = '')
                   AND NOT ($packageFilter)",
                [$projectId, $semana],
            )->fetchColumn();
            return ['actividades_total' => $total, 'sin_contrato' => $missing];
        }

        $total = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc WHERE project_id = ? AND semana = ? AND titulo = 0",
            [$projectId, $semana],
        )->fetchColumn();
        $missing = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc
             WHERE project_id = ? AND semana = ? AND titulo = 0
               AND (fechaInicioProyectada IS NULL OR valorPresupuesto IS NULL)",
            [$projectId, $semana],
        )->fetchColumn();

        return ['pdc_total' => $total, 'datos_faltantes' => $missing];
    }

    private function loadFamilyContractOptions(): array
    {
        $quantitySelect = $this->contractOptionItemQuantitySelect();
        $stmt = $this->db->query(
            "SELECT f.id AS familia_id, f.codigo AS familia_codigo, f.nombre AS familia_nombre,
                    o.id AS option_id, o.tipo_contrato, o.tipo_paquete,
                    o.dias_elaboracion, o.dias_entrega, o.dias_recibo, o.dias_cuadros,
                    o.dias_legalizacion, o.dias_fabricacion, o.dias_insumos,
                    i.id AS item_id, COALESCE(i.tipo_contrato, o.tipo_contrato) AS item_tipo_contrato,
                    COALESCE(i.tipo_paquete, o.tipo_paquete) AS item_tipo_paquete, i.paquete_nombre,
                    {$quantitySelect} AS cantidad_default
             FROM general_pdc_familias f
             LEFT JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
             LEFT JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
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
                    'tipo_contrato' => (int) $row['tipo_contrato'],
                    'tipo_paquete' => $row['tipo_paquete'],
                    'dias_elaboracion' => (int) $row['dias_elaboracion'],
                    'dias_entrega' => (int) $row['dias_entrega'],
                    'dias_recibo' => (int) $row['dias_recibo'],
                    'dias_cuadros' => (int) $row['dias_cuadros'],
                    'dias_legalizacion' => (int) $row['dias_legalizacion'],
                    'dias_fabricacion' => (int) $row['dias_fabricacion'],
                    'dias_insumos' => (int) $row['dias_insumos'],
                    'items' => [],
                ];
            }
            if (!empty($row['item_id'])) {
                $families[$familyId]['opciones'][$optionId]['items'][] = [
                    'item_id' => (int) $row['item_id'],
                    'tipo_contrato' => (int) $row['item_tipo_contrato'],
                    'tipo_paquete' => $row['item_tipo_paquete'],
                    'paquete_nombre' => $row['paquete_nombre'],
                    'cantidad_default' => max(1, (int) ($row['cantidad_default'] ?? 1)),
                ];
            }
        }

        foreach ($families as &$family) {
            $family['opciones'] = array_values($family['opciones']);
        }
        unset($family);

        return $families;
    }

    private function contractOptionItemQuantitySelect(): string
    {
        $exists = (int) $this->db->query(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'general_pdc_family_contract_option_items'
               AND COLUMN_NAME = 'cantidad_default'"
        )->fetchColumn();

        return $exists > 0 ? 'COALESCE(i.cantidad_default, 1)' : '1';
    }

    private function selectBestContratoOption(array $options, array $match): ?array
    {
        $modalidadSugerida = $match['modalidad_sugerida'] ?? '';
        foreach ($options as $option) {
            if ($option['tipo_paquete'] === $modalidadSugerida && !empty($option['items'])) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if (!empty($option['items'])) {
                return $option;
            }
        }

        return null;
    }

    private function formatAssignedPackages(array $items): array
    {
        return array_map(static function (array $item): array {
            return [
                'tipoPaquete' => $item['tipo_paquete'] ?? '',
                'paqueteNombre' => $item['paquete_nombre'] ?? '',
                'cantidadDefault' => max(1, (int) ($item['cantidad_default'] ?? 1)),
            ];
        }, $items);
    }

    private function policyContractPackagesForActivity(array $activity, ?array $programActivity, array $programSources): array
    {
        $texts = [
            (string) ($activity['actividad'] ?? ''),
            (string) ($programActivity['Actividad'] ?? ''),
        ];
        foreach ($programSources as $source) {
            $texts[] = (string) ($source['activity'] ?? '');
            $texts[] = (string) ($source['family'] ?? '');
        }

        $packages = [];
        foreach ($texts as $text) {
            foreach ($this->familyPolicy()->contractualPackageHintsForText($text) as $hint) {
                $package = $this->contractPackageFromDias($hint);
                $packages[$this->contractPackageKey($package)] = $package;
            }
        }

        return array_values($packages);
    }

    private function contractPackageFromDias(array $hint): array
    {
        $tipo = trim((string) ($hint['tipoPaquete'] ?? ''));
        $name = trim((string) ($hint['paqueteNombre'] ?? ''));
        $key = mb_strtolower($tipo . '|' . $name, 'UTF-8');
        if (isset($this->contractPackageCache[$key])) {
            return $this->contractPackageCache[$key];
        }

        $like = '%' . str_replace(' ', '%', $name) . '%';
        $row = $this->db->query(
            "SELECT tipoPaquete, paqueteContratacion
             FROM general_dias_procesos_contratacion
             WHERE (? = '' OR tipoPaquete = ?)
               AND UPPER(paqueteContratacion) LIKE UPPER(?)
             ORDER BY CASE WHEN UPPER(paqueteContratacion) = UPPER(?) THEN 0 ELSE 1 END,
                      LENGTH(paqueteContratacion) ASC
             LIMIT 1",
            [$tipo, $tipo, $like, $name],
        )->fetch(PDO::FETCH_ASSOC);

        $package = $row
            ? ['tipoPaquete' => (string) $row['tipoPaquete'], 'paqueteNombre' => (string) $row['paqueteContratacion']]
            : ['tipoPaquete' => $tipo, 'paqueteNombre' => $name];

        $this->contractPackageCache[$key] = $package;

        return $package;
    }

    private function mergeContractPackages(array $configured, array $detected): array
    {
        $merged = [];
        foreach (array_merge($configured, $detected) as $package) {
            $tipo = trim((string) ($package['tipoPaquete'] ?? ''));
            $name = trim((string) ($package['paqueteNombre'] ?? ''));
            if ($tipo === '' || $name === '') {
                continue;
            }
            $normalized = $this->contractPackageKey(['tipoPaquete' => $tipo, 'paqueteNombre' => $name]);
            $merged[$normalized] = ['tipoPaquete' => $tipo, 'paqueteNombre' => $name];
        }

        return array_values($merged);
    }

    private function contractPackageKey(array $package): string
    {
        return mb_strtolower(trim((string) ($package['tipoPaquete'] ?? '')) . '|' . trim((string) ($package['paqueteNombre'] ?? '')), 'UTF-8');
    }

    private function modalityCodeFromPackages(string $fallback, array $packages): string
    {
        $codes = [];
        foreach ($packages as $package) {
            $prefix = $this->packageModalityCode((string) ($package['tipoPaquete'] ?? ''));
            if ($prefix !== null) {
                $codes[$prefix] = true;
            }
        }

        if (empty($codes)) {
            return $fallback;
        }

        $ordered = [];
        foreach (['SI', 'S', 'MO', 'OC', 'E'] as $code) {
            if (isset($codes[$code])) {
                $ordered[] = $code;
            }
        }

        return implode(',', $ordered);
    }

    private function contractProposedFields(array $activity, string $tipoContrato, array $packages, float $confidence, int $sourceGroupCount = 1): array
    {
        $fields = array_fill_keys($this->contractFieldNames(), null);
        $fields['tipoContrato'] = $this->modalityCodeFromPackages($tipoContrato, $packages);
        $fields['fechaInicioProyectada'] = $this->normalizeDate($activity['fechaInicioProyectada'] ?? ($activity['fechaInicio'] ?? null));
        $fields['confianza_deteccion'] = $confidence;
        $currentSubcontracts = (int) ($activity['numeroSubcontratos'] ?? 0);
        $fields['numeroSubcontratos'] = max(1, $currentSubcontracts, $sourceGroupCount);

        $counts = ['SI' => 0, 'S' => 0, 'MO' => 0, 'OC' => 0];
        foreach ($packages as $package) {
            $prefix = $this->packagePrefix((string) ($package['tipoPaquete'] ?? ''));
            if ($prefix === null) {
                continue;
            }
            $slot = $counts[$prefix] + 1;
            if ($slot > 5) {
                continue;
            }
            $fields["paquete{$prefix}{$slot}"] = $package['paqueteNombre'] ?? '';
            $fields["cantidad{$prefix}{$slot}"] = max(1, (int) ($package['cantidadDefault'] ?? 1));
            $counts[$prefix] = $slot;
        }

        return $fields;
    }

    private function contractCurrentFields(array $activity): array
    {
        $fields = [];
        foreach ($this->contractFieldNames() as $field) {
            $fields[$field] = $activity[$field] ?? null;
        }
        $fields['Id'] = $activity['Id'] ?? null;
        $fields['actividad'] = $activity['actividad'] ?? null;
        $fields['fechaInicio'] = $activity['fechaInicio'] ?? null;

        return $fields;
    }

    private function contractFieldNames(): array
    {
        $fields = ['tipoContrato', 'fechaInicioProyectada', 'confianza_deteccion', 'numeroSubcontratos'];
        foreach (['SI', 'S', 'MO', 'OC'] as $prefix) {
            for ($i = 1; $i <= 5; $i++) {
                $fields[] = "{$prefix}{$i}";
                $fields[] = "paquete{$prefix}{$i}";
                $fields[] = "cantidad{$prefix}{$i}";
            }
        }

        return $fields;
    }

    private function contractPackagePresenceSql(): string
    {
        $checks = [];
        foreach (['SI', 'S', 'MO', 'OC'] as $prefix) {
            for ($i = 1; $i <= 5; $i++) {
                $field = "paquete$prefix$i";
                $checks[] = "NULLIF(TRIM(COALESCE(`$field`, '')), '') IS NOT NULL";
            }
        }

        return implode(' OR ', $checks);
    }

    private function ensureActivityProgramSourcesTable(): void
    {
        if ($this->activityProgramSourcesTableEnsured) {
            return;
        }
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS actividad_programa_fuentes (
                id bigint NOT NULL AUTO_INCREMENT,
                project_id int NOT NULL,
                actividad_id int NOT NULL,
                semana int NOT NULL,
                programa_unique_id int NOT NULL,
                source_activity varchar(500) NOT NULL,
                source_start_date date DEFAULT NULL,
                context varchar(255) DEFAULT NULL,
                location_hint varchar(255) DEFAULT NULL,
                intervention_hint varchar(255) DEFAULT NULL,
                family_id int DEFAULT NULL,
                family_name varchar(255) DEFAULT NULL,
                match_rule varchar(120) DEFAULT NULL,
                confidence decimal(5,2) DEFAULT NULL,
                risk_flags json DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_apf_activity_source (project_id, actividad_id, semana, programa_unique_id),
                KEY idx_apf_activity (project_id, semana, actividad_id),
                KEY idx_apf_programa (project_id, semana, programa_unique_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
        $this->activityProgramSourcesTableEnsured = true;
    }

    private function replaceActivityProgramSources(int $projectId, int $activityId, int $semana, array $sources): void
    {
        $this->ensureActivityProgramSourcesTable();
        $this->db->query(
            "DELETE FROM actividad_programa_fuentes WHERE project_id = ? AND actividad_id = ? AND semana = ?",
            [$projectId, $activityId, $semana],
        );

        foreach ($sources as $source) {
            $programUniqueId = (int) ($source['unique_id'] ?? 0);
            $activity = trim((string) ($source['activity'] ?? $source['original_activity'] ?? ''));
            if ($programUniqueId <= 0 || $activity === '') {
                continue;
            }
            $this->db->query(
                "INSERT INTO actividad_programa_fuentes
                 (project_id, actividad_id, semana, programa_unique_id, source_activity,
                  source_start_date, context, location_hint, intervention_hint, family_id,
                  family_name, match_rule, confidence, risk_flags)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $projectId,
                    $activityId,
                    $semana,
                    $programUniqueId,
                    $activity,
                    $this->normalizeDate($source['start_date'] ?? null),
                    $source['context'] ?? $source['chapter'] ?? null,
                    $source['location_hint'] ?? null,
                    $source['intervention_hint'] ?? null,
                    isset($source['family_id']) ? (int) $source['family_id'] : null,
                    $source['family'] ?? null,
                    $source['matched_rule'] ?? $source['matched_by'] ?? null,
                    isset($source['confidence']) ? (float) $source['confidence'] : null,
                    $this->jsonEncode($source['risk_flags'] ?? []),
                ],
            );
        }
    }

    private function loadActivityProgramSources(int $projectId, int $activityId, int $semana): array
    {
        $this->ensureActivityProgramSourcesTable();
        $rows = $this->db->query(
            "SELECT *
             FROM actividad_programa_fuentes
             WHERE project_id = ? AND actividad_id = ? AND semana = ?
             ORDER BY source_start_date ASC, programa_unique_id ASC",
            [$projectId, $activityId, $semana],
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'unique_id' => (int) ($row['programa_unique_id'] ?? 0),
                'activity' => (string) ($row['source_activity'] ?? ''),
                'original_activity' => (string) ($row['source_activity'] ?? ''),
                'clean_activity' => (string) ($row['source_activity'] ?? ''),
                'start_date' => $this->normalizeDate($row['source_start_date'] ?? null),
                'context' => (string) ($row['context'] ?? ''),
                'chapter' => (string) ($row['context'] ?? ''),
                'location_hint' => (string) ($row['location_hint'] ?? ''),
                'intervention_hint' => (string) ($row['intervention_hint'] ?? ''),
                'family_id' => (int) ($row['family_id'] ?? 0),
                'family' => (string) ($row['family_name'] ?? ''),
                'matched_rule' => (string) ($row['match_rule'] ?? ''),
                'matched_by' => (string) ($row['match_rule'] ?? ''),
                'confidence' => (float) ($row['confidence'] ?? 0),
                'why_included' => 'Fuente conservada desde Listado de Actividades para decisiones de contratos.',
                'risk_flags' => $this->decodeJson($row['risk_flags'] ?? null) ?: [],
            ];
        }, $rows);
    }

    private function sourcesToProgramActivities(array $sources): array
    {
        return array_map(static function (array $source): array {
            return [
                'Consecutivo' => $source['unique_id'] ?? 0,
                'Consecutivo_en_Programa' => $source['unique_id'] ?? 0,
                'unique_id' => $source['unique_id'] ?? 0,
                'Actividad' => $source['activity'] ?? '',
                'Fecha_Inicio' => $source['start_date'] ?? null,
                'Titulo' => 0,
            ];
        }, $sources);
    }

    private function contractSourceGroupCount(array $sources): int
    {
        $groups = [];
        foreach ($sources as $source) {
            $key = trim((string) ($source['intervention_hint'] ?? '')) ?: trim((string) ($source['location_hint'] ?? ''));
            if ($key !== '') {
                $groups[mb_strtolower($key, 'UTF-8')] = true;
            }
        }

        return max(1, count($groups));
    }

    private function packagesFromActivity(array $activity): array
    {
        $packages = [];
        $labels = ['SI' => 'Suministro e Instalación', 'S' => 'Suministro', 'MO' => 'Mano de Obra', 'OC' => 'Orden de Compra'];
        if (str_contains((string) ($activity['tipoContrato'] ?? ''), 'E')) {
            $labels['SI'] = 'Equipos';
        }
        foreach ($labels as $prefix => $label) {
            for ($i = 1; $i <= 5; $i++) {
                $name = trim((string) ($activity["paquete{$prefix}{$i}"] ?? ''));
                if ($name === '') {
                    continue;
                }
                $packages[] = [
                    'tipoPaquete' => $label,
                    'paqueteNombre' => $name,
                    'cantidadContratos' => max(1, (int) ($activity["cantidad{$prefix}{$i}"] ?? 1)),
                ];
            }
        }

        return $packages;
    }

    private function loadExistingPdcPackages(int $projectId, int $semana): array
    {
        $stmt = $this->db->query(
            "SELECT *
             FROM pdc
             WHERE project_id = ? AND semana = ? AND titulo = 0",
            [$projectId, $semana],
        );
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$this->pdcPackageKey($row['tipoPaquete'] ?? '', $row['paqueteContratacion'] ?? '')] = $row;
        }

        return $map;
    }

    private function pdcComparableFields(array $row): array
    {
        $data = [];
        foreach ($this->pdcEditableFields() as $field) {
            $data[$field] = $row[$field] ?? null;
        }

        return $data;
    }

    private function pdcEditableFields(): array
    {
        return [
            'contratos', 'numeroSubcontratos', 'estado', 'observacionesContrato',
            'fechaElaboracionPliegos', 'diasElaboracionPliegos',
            'fechaEntregaPliegos', 'diasEntregaPliegos',
            'fechaReciboPropuestas', 'diasReciboPropuestas',
            'fechaCuadrosComparativos', 'diasCuadrosComparativos',
            'fechaLegalizacionContrato', 'diasLegalizacionContrato',
            'fechaFabricacion', 'diasFabricacion',
            'fechaInsumosObra', 'diasInsumosObra',
            'fechaInicio', 'fechaInicioProyectada',
        ];
    }

    private function buildDiff(array $current, array $proposed): array
    {
        $diff = [];
        foreach ($proposed as $field => $value) {
            $old = $current[$field] ?? null;
            $new = $value === '' ? null : $value;
            if ((string) ($old ?? '') === (string) ($new ?? '')) {
                continue;
            }
            $diff[] = ['field' => $field, 'from' => $old, 'to' => $new];
        }

        return $diff;
    }

    private function loadLinkedProgramActivity(int $projectId, int $semana, string $actividadInicio): ?array
    {
        if ($actividadInicio === '') {
            return null;
        }
        $stmt = $this->db->query(
            "SELECT row_id AS Consecutivo,
                    unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id = ?
             LIMIT 1",
            [$projectId, $semana, $actividadInicio],
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function loadActivity(int $projectId, int $activityId, int $semana): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM actividades WHERE project_id = ? AND Id = ? AND semanaActualizacion = ? LIMIT 1",
            [$projectId, $activityId, $semana],
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function loadPdcRow(int $projectId, int $rowId): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM pdc WHERE project_id = ? AND pdc_row_id = ? LIMIT 1",
            [$projectId, $rowId],
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function programDisplayName(int $projectId, int $semana, int $consecutivoPrograma): ?string
    {
        $stmt = $this->db->query(
            "SELECT CONCAT(Id, '. ', Actividad, ' (Inicia en: ', Fecha_Inicio, ')')
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id = ?
             LIMIT 1",
            [$projectId, $semana, $consecutivoPrograma],
        );
        $value = $stmt->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    private function ensurePdcTitleRow(int $projectId, int $semana, string $tipoPaquete): void
    {
        $exists = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc
             WHERE project_id = ? AND semana = ? AND titulo = 1 AND tipoPaquete = ?",
            [$projectId, $semana, $tipoPaquete],
        )->fetchColumn();
        if ($exists > 0) {
            return;
        }

        $rowId = $this->nextProjectId($projectId, 'pdc', 'pdc_row_id');
        $this->db->query(
            "INSERT INTO pdc (project_id, pdc_row_id, consecutivo, semana, titulo, tipoPaquete, paqueteContratacion, subcontratoPaquete)
             VALUES (?, ?, ?, ?, 1, ?, ?, 0)",
            [$projectId, $rowId, $rowId, $semana, $tipoPaquete, $tipoPaquete],
        );
    }

    private function nextPdcSubcontractIndex(int $projectId, int $semana, string $tipoPaquete): int
    {
        return (int) $this->db->query(
            "SELECT COALESCE(MAX(subcontratoPaquete), 0) + 1
             FROM pdc
             WHERE project_id = ? AND semana = ? AND tipoPaquete = ? AND titulo = 0",
            [$projectId, $semana, $tipoPaquete],
        )->fetchColumn();
    }

    private function nextProjectId(int $projectId, string $table, string $idColumn): int
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $idColumn)) {
            throw new RuntimeException('Identificador inválido.');
        }

        return (int) $this->db->query(
            "SELECT COALESCE(MAX({$idColumn}), 0) + 1 FROM {$table} WHERE project_id = ?",
            [$projectId],
        )->fetchColumn();
    }

    private function resolveMaxSemana(int $projectId): int
    {
        return max(1, (int) $this->db->query(
            "SELECT COALESCE(MAX(Semana), 0) FROM semanas_activas WHERE project_id = ?",
            [$projectId],
        )->fetchColumn());
    }

    private function resolveTipoContratoForFamily(int $familyId): string
    {
        $stmt = $this->db->query(
            "SELECT tipo_contrato FROM general_pdc_family_contract_options
             WHERE familia_id = ? AND activa = 1
             ORDER BY id ASC LIMIT 1",
            [$familyId],
        );

        return $this->intToModalityCode((int) ($stmt->fetchColumn() ?: 0));
    }

    private function intToModalityCode(int $tipoContrato): string
    {
        return match ($tipoContrato) {
            1 => 'MO,S',
            2 => 'SI',
            3 => 'S',
            4 => 'MO',
            5 => 'OC',
            6 => 'E',
            default => '',
        };
    }

    private function packagePrefix(string $tipoPaquete): ?string
    {
        return match ($tipoPaquete) {
            'Suministro e Instalación', 'Todo costo', 'Alquiler con operación', 'Alquiler con operacion', 'Equipos', 'Equipo' => 'SI',
            'Suministro', 'Suministro de Materiales, Herramientas o Equipos' => 'S',
            'Mano de Obra' => 'MO',
            'Orden de Compra', 'Pedido a proveedor', 'Pedido / orden a proveedor' => 'OC',
            'Administración', 'Administracion' => 'MO',
            default => null,
        };
    }

    private function packageModalityCode(string $tipoPaquete): ?string
    {
        return match ($tipoPaquete) {
            'Equipos', 'Equipo', 'Alquiler con operación', 'Alquiler con operacion' => 'E',
            default => $this->packagePrefix($tipoPaquete),
        };
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

    private function pdcDurationsForPackage(array $package): array
    {
        $tipo = trim((string) ($package['tipoPaquete'] ?? ''));
        $name = trim((string) ($package['paqueteNombre'] ?? ''));
        if ($name === '') {
            return $this->defaultDurations();
        }

        $row = $this->loadPdcDurationRow($tipo, $name);
        if ($row === null) {
            return $this->defaultDurations();
        }

        return [
            'dias_elaboracion' => max(0, (int) ($row['diasElaboracionPliegos'] ?? 0)),
            'dias_entrega' => max(0, (int) ($row['diasEntregaPliegos'] ?? 0)),
            'dias_recibo' => max(0, (int) ($row['diasReciboPropuestas'] ?? 0)),
            'dias_cuadros' => max(0, (int) ($row['diasCuadrosComparativos'] ?? 0)),
            'dias_legalizacion' => max(0, (int) ($row['diasLegalizacionContrato'] ?? 0)),
            'dias_fabricacion' => max(0, (int) ($row['diasFabricacion'] ?? 0)),
            'dias_insumos' => max(0, (int) ($row['diasInsumosObra'] ?? 0)),
        ];
    }

    private function loadPdcDurationRow(string $tipoPaquete, string $paqueteNombre): ?array
    {
        $params = [$paqueteNombre];
        $typeClause = '';
        if ($tipoPaquete !== '') {
            $typeClause = ' AND tipoPaquete = ?';
            $params[] = $tipoPaquete;
        }

        $row = $this->db->query(
            "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                    diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
             FROM general_dias_procesos_contratacion
             WHERE UPPER(paqueteContratacion) = UPPER(?) {$typeClause}
             LIMIT 1",
            $params,
        )->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        $like = '%' . str_replace(' ', '%', $paqueteNombre) . '%';
        $params = [$like];
        $typeClause = '';
        if ($tipoPaquete !== '') {
            $typeClause = ' AND tipoPaquete = ?';
            $params[] = $tipoPaquete;
        }
        $params[] = $paqueteNombre;

        $row = $this->db->query(
            "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                    diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
             FROM general_dias_procesos_contratacion
             WHERE UPPER(paqueteContratacion) LIKE UPPER(?) {$typeClause}
             ORDER BY CASE WHEN UPPER(paqueteContratacion) = UPPER(?) THEN 0 ELSE 1 END,
                      LENGTH(paqueteContratacion) ASC
             LIMIT 1",
            $params,
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function calculateProcessDates(?string $fechaInicio, array $durations): array
    {
        if ($fechaInicio === null || $fechaInicio === '') {
            return [
                'fechaElaboracionPliegos' => null,
                'fechaEntregaPliegos' => null,
                'fechaReciboPropuestas' => null,
                'fechaCuadrosComparativos' => null,
                'fechaLegalizacionContrato' => null,
                'fechaFabricacion' => null,
                'fechaInsumosObra' => null,
                'diasElaboracionPliegos' => $durations['dias_elaboracion'],
                'diasEntregaPliegos' => $durations['dias_entrega'],
                'diasReciboPropuestas' => $durations['dias_recibo'],
                'diasCuadrosComparativos' => $durations['dias_cuadros'],
                'diasLegalizacionContrato' => $durations['dias_legalizacion'],
                'diasFabricacion' => $durations['dias_fabricacion'],
                'diasInsumosObra' => $durations['dias_insumos'],
            ];
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
            'diasElaboracionPliegos' => $durations['dias_elaboracion'],
            'diasEntregaPliegos' => $durations['dias_entrega'],
            'diasReciboPropuestas' => $durations['dias_recibo'],
            'diasCuadrosComparativos' => $durations['dias_cuadros'],
            'diasLegalizacionContrato' => $durations['dias_legalizacion'],
            'diasFabricacion' => $durations['dias_fabricacion'],
            'diasInsumosObra' => $durations['dias_insumos'],
        ];
    }

    private function subDays(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' - ' . max(0, $days) . ' days'));
    }

    private function normalizeModule(string $module): string
    {
        $module = trim($module);
        if (in_array($module, [self::MODULE_LISTADO, self::MODULE_CONTRATOS, self::MODULE_PDC], true)) {
            return $module;
        }

        throw new RuntimeException('Módulo de automatización inválido.');
    }

    private function normalizeConfidence($value): float
    {
        $confidence = (float) ($value ?? 0);
        if ($confidence > 0 && $confidence <= 1) {
            $confidence *= 100;
        }

        return max(0.0, min(100.0, round($confidence, 2)));
    }

    private function confidenceBand(float $confidence, array $config): string
    {
        if ($confidence >= (float) $config['high_threshold']) {
            return 'high';
        }
        if ($confidence >= (float) $config['medium_threshold']) {
            return 'medium';
        }

        return 'low';
    }

    private function isPreselected(float $confidence, array $config): bool
    {
        return $confidence >= (float) $config['high_threshold'];
    }

    private function shouldPreselect(float $confidence, array $config, ?array $match = null): bool
    {
        return $this->isPreselected($confidence, $config) && !$this->matchRequiresReview($match);
    }

    private function matchRequiresReview(?array $match): bool
    {
        if ($match === null) {
            return false;
        }

        return !empty($match['reviewRequired']) || (int) ($match['siempre_revision'] ?? 0) === 1;
    }

    private function matchSource(array $match): string
    {
        $source = (string) ($match['matchedBy'] ?? 'regex');
        if (!empty($match['reviewRequired'])) {
            $source .= ':revision';
        }

        return $source;
    }

    private function pdcPackageRequiresReview(array $package, array $activity): bool
    {
        return $this->pdcReviewReasonForPackage($package, $activity) !== null;
    }

    private function pdcStatusForPackage(array $package, array $activity): string
    {
        $text = $this->packageSignalText($package, $activity);
        if (str_contains($text, 'CAMPAMENTO') || str_contains($text, 'PROVISIONAL')) {
            return 'Por confirmar';
        }
        if (str_contains($text, 'BOTADA') || str_contains($text, 'ESCOMBRO')) {
            return 'A necesidad';
        }
        if (str_contains($text, 'ASEO')) {
            return 'Por decidir - subcontrato o personal directo';
        }
        if (str_contains($text, 'AMENIDAD') || str_contains($text, 'JACUZZI') || str_contains($text, 'CUBIERTA')) {
            return 'Por confirmar';
        }

        return 'En Curso; Proceso de contratación no iniciado';
    }

    private function pdcObservationForPackage(array $package, array $activity): ?string
    {
        return null;
    }

    private function pdcReviewReasonForPackage(array $package, array $activity): ?string
    {
        $text = $this->packageSignalText($package, $activity);
        if (str_contains($text, 'TELECOM')) {
            return 'Revisar alcance antes de aplicar.';
        }
        if (str_contains($text, 'CAMPAMENTO') || str_contains($text, 'PROVISIONAL')) {
            return 'Confirmar alcance antes de aplicar.';
        }
        if (str_contains($text, 'BOTADA') || str_contains($text, 'ESCOMBRO')) {
            return 'Aplicar solo si corresponde a escombros, no a tierra.';
        }
        if (str_contains($text, 'ASEO')) {
            return 'Decidir si se subcontrata o se maneja con personal directo.';
        }
        if (str_contains($text, 'AMENIDAD') || str_contains($text, 'JACUZZI') || str_contains($text, 'CUBIERTA')) {
            return 'Confirmar alcance antes de aplicar.';
        }

        return null;
    }

    private function packageSignalText(array $package, array $activity): string
    {
        $text = implode(' ', [
            $package['tipoPaquete'] ?? '',
            $package['paqueteNombre'] ?? '',
            $activity['actividad'] ?? '',
        ]);
        $text = $this->plainText($text);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return mb_strtoupper($ascii !== false ? $ascii : $text);
    }

    private function pdcPackageKey(string $tipoPaquete, string $paquete): string
    {
        return mb_strtolower(trim($tipoPaquete) . '|' . trim($paquete));
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function plainText($value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s*\[Cap[ií]tulo:\s*[^\]]+\]\s*/iu', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function limitDbText(string $value, int $maxLength): string
    {
        if ($maxLength <= 0 || mb_strlen($value, 'UTF-8') <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    private function newId(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(16));
    }

    private function currentUser(): string
    {
        $session = $_SESSION ?? [];

        return (string) ($session['usuario'] ?? $session['nombreUsuario'] ?? 'sistema');
    }

    private function isAdminUser(): bool
    {
        $session = $_SESSION ?? [];

        $role = (string) ($session['permiso_canonico'] ?? $session['permiso'] ?? '');
        return strtoupper(trim($role)) === 'A';
    }

    private function jsonEncode($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decodeJson($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
