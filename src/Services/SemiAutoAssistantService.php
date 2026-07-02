<?php

namespace App\Services;

use Admin\Core\RoleManager;
use PDO;
use RuntimeException;

class SemiAutoAssistantService
{
    private \Database $db;
    private bool $schemaReady = false;

    public function __construct(?\Database $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    public function recordPreview(
        string $module,
        int $projectId,
        int $semana,
        string $runId,
        array $suggestions
    ): array {
        $this->ensureTables();
        $summary = $this->buildSummary($suggestions);

        if (($summary['conflicts'] ?? 0) > 0) {
            $this->upsertQueueItem($projectId, $module, $semana, 'diagnostic', 'warning', 'Conflictos por revisar', 'Hay propuestas que requieren decisión manual antes de aplicar.', 65, 'preview', $runId, $summary);
        }
        if (($summary['ready'] ?? 0) > 0) {
            $this->upsertQueueItem($projectId, $module, $semana, 'recommendation', 'info', 'Propuestas listas para aplicar', 'Hay cambios de alta seguridad que puedes aplicar después de revisar.', 85, 'preview', $runId, $summary);
        }

        return $this->previewContext($module, $projectId, $semana, $runId, $suggestions);
    }

    public function previewContext(string $module, int $projectId, int $semana, string $runId, array $suggestions): array
    {
        $this->ensureTables();
        $summary = $this->buildSummary($suggestions);
        $learning = $this->activeLearningRules($projectId, $module);

        if (!empty($learning)) {
            $summary['message'] .= ' Se usaron aprendizajes aprobados como señales de apoyo.';
        }

        return [
            'assistant_summary' => $summary,
            'assistant_recommendations' => $this->buildRecommendations($summary, $learning),
            'assistant_alerts' => $this->pendingQueueItems($projectId, $module, $semana, 5),
            'learning_used' => $learning,
            'assistant_provider' => $this->providerInfo(),
        ];
    }

    public function inbox(string $module, array $context): array
    {
        $this->ensureTables();
        $projectId = (int) ($context['projectId'] ?? 0);
        $semana = $this->resolveWeek($context, $projectId);
        $diagnostics = $this->moduleDiagnostics($module, $projectId, $semana);

        return [
            'respuesta' => 'BIEN',
            'module' => $module,
            'project_id' => $projectId,
            'semana' => $semana,
            'items' => $this->pendingQueueItems($projectId, $module, $semana, 20),
            'diagnostics' => $diagnostics,
            'recommendations' => $this->diagnosticRecommendations($diagnostics),
        ];
    }

    public function ack(string $module, array $context, array $payload): array
    {
        $this->ensureTables();
        $projectId = (int) ($context['projectId'] ?? 0);
        $itemId = (string) ($payload['item_id'] ?? '');
        if ($itemId === '') {
            throw new RuntimeException('Alerta inválida.');
        }

        $this->db->query(
            "UPDATE semi_auto_proactive_queue
             SET status = 'acknowledged', resolved_at = NOW()
             WHERE project_id = ? AND module = ? AND item_id = ?",
            [$projectId, $module, $itemId],
        );

        return ['respuesta' => 'BIEN', 'item_id' => $itemId];
    }

    public function assistantFeedback(string $module, array $context, array $payload): array
    {
        $this->ensureTables();
        $projectId = (int) ($context['projectId'] ?? 0);
        $semana = $this->resolveWeek($context, $projectId);
        $feedbackId = $this->newId('af');

        $this->db->query(
            "INSERT INTO semi_auto_assistant_feedback
             (feedback_id, project_id, module, semana, run_id, suggestion_id, item_id,
              feedback_type, rating, notes, metadata, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $feedbackId,
                $projectId,
                $module,
                $semana,
                $payload['run_id'] ?? null,
                $payload['suggestion_id'] ?? null,
                $payload['item_id'] ?? null,
                $payload['feedback_type'] ?? 'usefulness',
                $payload['rating'] ?? null,
                $payload['notes'] ?? null,
                $this->jsonEncode($payload['metadata'] ?? null),
                $this->currentUser(),
            ],
        );

        return ['respuesta' => 'BIEN', 'feedback_id' => $feedbackId];
    }

    public function recordLearningSignal(string $module, int $projectId, array $payload, string $createdBy): void
    {
        $this->ensureTables();
        $corrected = (array) ($payload['corrected'] ?? []);
        if (empty($corrected)) {
            return;
        }

        $suggestion = $this->loadSuggestion($projectId, $module, (string) ($payload['suggestion_id'] ?? ''));
        $action = (string) ($suggestion['action'] ?? 'feedback');
        foreach ($corrected as $field => $value) {
            $observations = $this->feedbackObservationsForField($projectId, $module, (string) $field);
            if ($observations < 1) {
                continue;
            }
            $candidateId = $this->stableId('learn', [$projectId, $module, $action, $field]);
            $metadata = [
                'field' => $field,
                'action' => $action,
                'observations' => $observations,
                'latest_value' => $value,
                'source_run_id' => $payload['run_id'] ?? null,
                'source_suggestion_id' => $payload['suggestion_id'] ?? null,
            ];
            $this->upsertLearningCandidate($candidateId, $projectId, $module, null, 'field_correction', 'Corrección frecuente: ' . $this->humanize((string) $field), 'El usuario ajustó este campo durante la revisión. Un Admin puede convertirlo en aprendizaje aprobado.', min(95, 55 + ($observations * 10)), $metadata, $createdBy);
        }
    }

    public function learningCandidates(string $module, array $context): array
    {
        $this->ensureTables();
        $projectId = (int) ($context['projectId'] ?? 0);
        $stmt = $this->db->query(
            "SELECT *
             FROM semi_auto_learning_candidates
             WHERE project_id = ? AND module = ?
             ORDER BY FIELD(status, 'pending', 'approved', 'rejected'), created_at DESC
             LIMIT 50",
            [$projectId, $module],
        );

        return [
            'respuesta' => 'BIEN',
            'module' => $module,
            'candidates' => array_map(fn(array $row): array => $this->formatCandidate($row), $stmt->fetchAll(PDO::FETCH_ASSOC)),
        ];
    }

    public function approveLearning(string $module, array $context, array $payload): array
    {
        $this->ensureTables();
        $this->assertAdmin();
        $projectId = (int) ($context['projectId'] ?? 0);
        $candidateId = (string) ($payload['candidate_id'] ?? '');
        $candidate = $this->loadCandidate($projectId, $module, $candidateId);
        $ruleId = $this->stableId('rule', [$projectId, $module, $candidateId]);

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO semi_auto_learning_rules
                 (rule_id, project_id, module, candidate_id, rule_type, title, description,
                  status, confidence, metadata, created_by, approved_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = 'active', confidence = VALUES(confidence),
                    metadata = VALUES(metadata), approved_by = VALUES(approved_by), updated_at = NOW()",
                [
                    $ruleId,
                    $projectId,
                    $module,
                    $candidateId,
                    $candidate['candidate_type'],
                    $candidate['title'],
                    $candidate['description'],
                    $candidate['confidence'],
                    $candidate['metadata'],
                    $candidate['created_by'],
                    $this->currentUser(),
                ],
            );
            $this->db->query(
                "UPDATE semi_auto_learning_candidates
                 SET status = 'approved', reviewed_by = ?, resolved_at = NOW()
                 WHERE project_id = ? AND module = ? AND candidate_id = ?",
                [$this->currentUser(), $projectId, $module, $candidateId],
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return ['respuesta' => 'BIEN', 'candidate_id' => $candidateId, 'rule_id' => $ruleId];
    }

    public function rejectLearning(string $module, array $context, array $payload): array
    {
        $this->ensureTables();
        $this->assertAdmin();
        $projectId = (int) ($context['projectId'] ?? 0);
        $candidateId = (string) ($payload['candidate_id'] ?? '');
        if ($candidateId === '') {
            throw new RuntimeException('Candidato inválido.');
        }

        $this->db->query(
            "UPDATE semi_auto_learning_candidates
             SET status = 'rejected', reviewed_by = ?, resolved_at = NOW()
             WHERE project_id = ? AND module = ? AND candidate_id = ?",
            [$this->currentUser(), $projectId, $module, $candidateId],
        );

        return ['respuesta' => 'BIEN', 'candidate_id' => $candidateId];
    }

    private function ensureTables(): void
    {
        if ($this->schemaReady) {
            return;
        }

        foreach ($this->schemaSql() as $sql) {
            $this->db->query($sql);
        }
        $this->schemaReady = true;
    }

    private function schemaSql(): array
    {
        return [
            "CREATE TABLE IF NOT EXISTS semi_auto_learning_candidates (
                id bigint NOT NULL AUTO_INCREMENT,
                candidate_id varchar(64) NOT NULL,
                project_id int NOT NULL,
                module varchar(40) NOT NULL,
                semana int DEFAULT NULL,
                candidate_type varchar(40) NOT NULL,
                title varchar(255) NOT NULL,
                description text DEFAULT NULL,
                status varchar(30) NOT NULL DEFAULT 'pending',
                confidence decimal(5,2) NOT NULL DEFAULT 0,
                metadata json DEFAULT NULL,
                created_by varchar(100) DEFAULT NULL,
                reviewed_by varchar(100) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_semi_auto_learning_candidate (candidate_id),
                KEY idx_semi_auto_learning_candidates_project (project_id, module, status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS semi_auto_learning_rules (
                id bigint NOT NULL AUTO_INCREMENT,
                rule_id varchar(64) NOT NULL,
                project_id int NOT NULL,
                module varchar(40) NOT NULL,
                candidate_id varchar(64) DEFAULT NULL,
                rule_type varchar(40) NOT NULL,
                title varchar(255) NOT NULL,
                description text DEFAULT NULL,
                status varchar(30) NOT NULL DEFAULT 'active',
                confidence decimal(5,2) NOT NULL DEFAULT 0,
                metadata json DEFAULT NULL,
                created_by varchar(100) DEFAULT NULL,
                approved_by varchar(100) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_semi_auto_learning_rule (rule_id),
                KEY idx_semi_auto_learning_rules_project (project_id, module, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS semi_auto_proactive_queue (
                id bigint NOT NULL AUTO_INCREMENT,
                item_id varchar(64) NOT NULL,
                project_id int NOT NULL,
                module varchar(40) NOT NULL,
                semana int DEFAULT NULL,
                item_type varchar(40) NOT NULL,
                severity varchar(20) NOT NULL DEFAULT 'info',
                title varchar(255) NOT NULL,
                message text DEFAULT NULL,
                status varchar(30) NOT NULL DEFAULT 'pending',
                confidence decimal(5,2) NOT NULL DEFAULT 0,
                source_module varchar(40) DEFAULT NULL,
                source_ref varchar(100) DEFAULT NULL,
                metadata json DEFAULT NULL,
                created_by varchar(100) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_semi_auto_queue_item (item_id),
                KEY idx_semi_auto_queue_project (project_id, module, semana, status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS semi_auto_assistant_feedback (
                id bigint NOT NULL AUTO_INCREMENT,
                feedback_id varchar(64) NOT NULL,
                project_id int NOT NULL,
                module varchar(40) NOT NULL,
                semana int DEFAULT NULL,
                run_id varchar(64) DEFAULT NULL,
                suggestion_id varchar(64) DEFAULT NULL,
                item_id varchar(64) DEFAULT NULL,
                feedback_type varchar(40) NOT NULL,
                rating varchar(30) DEFAULT NULL,
                notes text DEFAULT NULL,
                metadata json DEFAULT NULL,
                created_by varchar(100) DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_semi_auto_assistant_feedback (feedback_id),
                KEY idx_semi_auto_assistant_feedback_project (project_id, module, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    private function buildSummary(array $suggestions): array
    {
        $ready = 0;
        $review = 0;
        $conflicts = 0;
        foreach ($suggestions as $suggestion) {
            $qualityStatus = $this->qualityStatus($suggestion);
            if ($qualityStatus === 'conflict') {
                $conflicts++;
                continue;
            }
            if ($qualityStatus === 'review') {
                $review++;
                continue;
            }
            $band = (string) ($suggestion['confidence_band'] ?? '');
            $action = (string) ($suggestion['action'] ?? '');
            $diff = $suggestion['diff'] ?? $suggestion['diff_payload'] ?? [];
            if (is_string($diff)) {
                $diff = $this->decodeJson($diff) ?: [];
            }
            if (str_starts_with($action, 'review_no_match') || empty($diff) || $band === 'low') {
                $conflicts++;
                continue;
            }
            if (!empty($suggestion['preselected']) && ($band === 'high' || $band === '')) {
                $ready++;
                continue;
            }
            $review++;
        }

        $total = count($suggestions);
        return [
            'total' => $total,
            'ready' => $ready,
            'review' => $review,
            'conflicts' => $conflicts,
            'risk' => $conflicts > 0 ? 'medium' : 'low',
            'message' => $total > 0
                ? "Encontramos {$total} propuestas: {$ready} listas, {$review} por revisar y {$conflicts} conflictos."
                : 'No encontramos cambios nuevos con los datos actuales.',
        ];
    }

    private function qualityStatus(array $suggestion): ?string
    {
        $analysis = $suggestion['analysis_payload'] ?? $suggestion['analysis'] ?? null;
        if (is_string($analysis)) {
            $analysis = $this->decodeJson($analysis) ?: null;
        }
        if (!is_array($analysis)) {
            $apply = $suggestion['apply_payload'] ?? null;
            if (is_string($apply)) {
                $apply = $this->decodeJson($apply) ?: null;
            }
            $analysis = is_array($apply) ? ($apply['_analysis'] ?? null) : null;
        }
        $status = is_array($analysis) ? ($analysis['quality_gate']['status'] ?? null) : null;

        return is_string($status) && $status !== '' ? $status : null;
    }

    private function buildRecommendations(array $summary, array $learning): array
    {
        $items = [];
        if (($summary['ready'] ?? 0) > 0) {
            $items[] = ['type' => 'apply_ready', 'title' => 'Aplicar cambios de alta seguridad', 'message' => 'Revisa las tarjetas marcadas como listas y aplícalas si coinciden con tu criterio.', 'priority' => 80];
        }
        if (($summary['conflicts'] ?? 0) > 0) {
            $items[] = ['type' => 'review_conflicts', 'title' => 'Resolver conflictos primero', 'message' => 'Hay propuestas sin suficiente seguridad; no se aplicarán automáticamente.', 'priority' => 90];
        }
        if (($summary['review'] ?? 0) > 0) {
            $items[] = ['type' => 'review_changes', 'title' => 'Revisar propuestas intermedias', 'message' => 'Estas propuestas pueden servir, pero necesitan confirmación humana.', 'priority' => 60];
        }
        if (!empty($learning)) {
            $items[] = ['type' => 'learning_used', 'title' => 'Aprendizajes aprobados activos', 'message' => 'El asistente encontró aprendizajes aprobados para este módulo.', 'priority' => 50];
        }

        return $items;
    }

    private function pendingQueueItems(int $projectId, string $module, int $semana, int $limit): array
    {
        $stmt = $this->db->query(
            "SELECT *
             FROM semi_auto_proactive_queue
             WHERE project_id = ? AND module = ? AND (semana = ? OR semana IS NULL)
               AND status = 'pending'
             ORDER BY FIELD(severity, 'critical', 'warning', 'info'), created_at DESC
             LIMIT {$limit}",
            [$projectId, $module, $semana],
        );

        return array_map(fn(array $row): array => $this->formatQueueItem($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function upsertQueueItem(
        int $projectId,
        string $module,
        int $semana,
        string $type,
        string $severity,
        string $title,
        string $message,
        float $confidence,
        string $sourceModule,
        string $sourceRef,
        array $metadata
    ): void {
        $itemId = $this->stableId('assist', [$projectId, $module, $semana, $type, $title]);
        $this->db->query(
            "INSERT INTO semi_auto_proactive_queue
             (item_id, project_id, module, semana, item_type, severity, title, message,
              status, confidence, source_module, source_ref, metadata, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE message = VALUES(message), confidence = VALUES(confidence),
                source_ref = VALUES(source_ref), metadata = VALUES(metadata), status = 'pending',
                resolved_at = NULL",
            [
                $itemId,
                $projectId,
                $module,
                $semana,
                $type,
                $severity,
                $title,
                $message,
                $confidence,
                $sourceModule,
                $sourceRef,
                $this->jsonEncode($metadata),
                $this->currentUser(),
            ],
        );
    }

    private function moduleDiagnostics(string $module, int $projectId, int $semana): array
    {
        if ($module === SemiAutoService::MODULE_LISTADO) {
            $pg = (int) $this->db->query("SELECT COUNT(*) FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND COALESCE(Titulo, 0) = 0", [$projectId, $semana])->fetchColumn();
            $list = (int) $this->db->query("SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?", [$projectId, $semana])->fetchColumn();
            return ['source_rows' => $pg, 'current_rows' => $list, 'message' => 'Comparación entre Programa General y Listado.'];
        }

        if ($module === SemiAutoService::MODULE_CONTRATOS) {
            $total = (int) $this->db->query("SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ?", [$projectId, $semana])->fetchColumn();
            $missing = (int) $this->db->query("SELECT COUNT(*) FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND (tipoContrato IS NULL OR tipoContrato = '')", [$projectId, $semana])->fetchColumn();
            return ['source_rows' => $total, 'missing_contracts' => $missing, 'message' => 'Revisión de actividades sin modalidad o paquetes de contratación.'];
        }

        $packages = (int) $this->db->query("SELECT COUNT(*) FROM pdc WHERE project_id = ? AND semana = ? AND titulo = 0", [$projectId, $semana])->fetchColumn();
        $missingDates = (int) $this->db->query("SELECT COUNT(*) FROM pdc WHERE project_id = ? AND semana = ? AND titulo = 0 AND fechaInicioProyectada IS NULL", [$projectId, $semana])->fetchColumn();
        return ['source_rows' => $packages, 'missing_dates' => $missingDates, 'message' => 'Revisión de paquetes existentes en PDC.'];
    }

    private function diagnosticRecommendations(array $diagnostics): array
    {
        $items = [];
        if (($diagnostics['missing_contracts'] ?? 0) > 0) {
            $items[] = ['type' => 'run_preview', 'title' => 'Analizar contratos', 'message' => 'Hay actividades sin definición de contrato.'];
        }
        if (($diagnostics['missing_dates'] ?? 0) > 0) {
            $items[] = ['type' => 'review_pdc_dates', 'title' => 'Revisar fechas de PDC', 'message' => 'Hay paquetes sin fecha proyectada.'];
        }
        if (empty($items)) {
            $items[] = ['type' => 'run_preview', 'title' => 'Analizar propuestas', 'message' => 'Ejecuta el análisis para confirmar si hay cambios nuevos.'];
        }

        return $items;
    }

    private function activeLearningRules(int $projectId, string $module): array
    {
        $stmt = $this->db->query(
            "SELECT rule_id, rule_type, title, description, confidence, metadata
             FROM semi_auto_learning_rules
             WHERE project_id = ? AND module = ? AND status = 'active'
             ORDER BY updated_at DESC
             LIMIT 10",
            [$projectId, $module],
        );

        return array_map(function (array $row): array {
            return [
                'rule_id' => $row['rule_id'],
                'type' => $row['rule_type'],
                'title' => $row['title'],
                'description' => $row['description'],
                'confidence' => (float) $row['confidence'],
                'metadata' => $this->decodeJson($row['metadata']) ?: [],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function upsertLearningCandidate(
        string $candidateId,
        int $projectId,
        string $module,
        ?int $semana,
        string $type,
        string $title,
        string $description,
        float $confidence,
        array $metadata,
        string $createdBy
    ): void {
        $this->db->query(
            "INSERT INTO semi_auto_learning_candidates
             (candidate_id, project_id, module, semana, candidate_type, title, description,
              status, confidence, metadata, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
             ON DUPLICATE KEY UPDATE confidence = GREATEST(confidence, VALUES(confidence)),
                metadata = VALUES(metadata), status = IF(status = 'rejected', status, 'pending')",
            [$candidateId, $projectId, $module, $semana, $type, $title, $description, $confidence, $this->jsonEncode($metadata), $createdBy],
        );
    }

    private function feedbackObservationsForField(int $projectId, string $module, string $field): int
    {
        $stmt = $this->db->query(
            "SELECT corrected_payload
             FROM semi_auto_feedback
             WHERE project_id = ? AND module = ?
             ORDER BY created_at DESC
             LIMIT 50",
            [$projectId, $module],
        );

        $count = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $payload = $this->decodeJson($row['corrected_payload']) ?: [];
            if (array_key_exists($field, $payload)) {
                $count++;
            }
        }

        return $count;
    }

    private function loadSuggestion(int $projectId, string $module, string $suggestionId): ?array
    {
        if ($suggestionId === '') {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT * FROM semi_auto_suggestions
             WHERE project_id = ? AND module = ? AND suggestion_id = ?
             LIMIT 1",
            [$projectId, $module, $suggestionId],
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function loadCandidate(int $projectId, string $module, string $candidateId): array
    {
        if ($candidateId === '') {
            throw new RuntimeException('Candidato inválido.');
        }

        $stmt = $this->db->query(
            "SELECT *
             FROM semi_auto_learning_candidates
             WHERE project_id = ? AND module = ? AND candidate_id = ?
             LIMIT 1",
            [$projectId, $module, $candidateId],
        );
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$candidate) {
            throw new RuntimeException('Candidato de aprendizaje no encontrado.');
        }

        return $candidate;
    }

    private function formatCandidate(array $row): array
    {
        return [
            'candidate_id' => $row['candidate_id'],
            'type' => $row['candidate_type'],
            'title' => $row['title'],
            'description' => $row['description'],
            'status' => $row['status'],
            'confidence' => (float) $row['confidence'],
            'metadata' => $this->isAdmin() ? ($this->decodeJson($row['metadata']) ?: []) : [],
            'created_at' => $row['created_at'],
            'resolved_at' => $row['resolved_at'],
        ];
    }

    private function formatQueueItem(array $row): array
    {
        return [
            'item_id' => $row['item_id'],
            'type' => $row['item_type'],
            'severity' => $row['severity'],
            'title' => $row['title'],
            'message' => $row['message'],
            'confidence' => (float) $row['confidence'],
            'source_module' => $row['source_module'],
            'metadata' => $this->isAdmin() ? ($this->decodeJson($row['metadata']) ?: []) : [],
            'created_at' => $row['created_at'],
        ];
    }

    private function resolveWeek(array $context, int $projectId): int
    {
        $semana = (int) ($context['semana'] ?? 0);
        if ($semana > 0) {
            return $semana;
        }

        return max(1, (int) $this->db->query("SELECT COALESCE(MAX(Semana), 0) FROM semanas_activas WHERE project_id = ?", [$projectId])->fetchColumn());
    }

    private function providerInfo(): array
    {
        $provider = getenv('SEMI_AUTO_ASSISTANT_PROVIDER') ?: 'none';
        return [
            'provider' => in_array($provider, ['none', 'opencode_zen', 'openai'], true) ? $provider : 'none',
            'external_ai_enabled' => $provider !== 'none',
        ];
    }

    private function assertAdmin(): void
    {
        if (!$this->isAdmin()) {
            throw new RuntimeException('Solo un administrador puede aprobar aprendizajes.');
        }
    }

    private function isAdmin(): bool
    {
        $role = $_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '');
        if (class_exists(RoleManager::class)) {
            $role = RoleManager::cleanCargo($role);
        }

        return strtoupper((string) $role) === 'A';
    }

    private function currentUser(): string
    {
        return (string) ($_SESSION['usuario'] ?? $_SESSION['nombreUsuario'] ?? 'sistema');
    }

    private function newId(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(8));
    }

    private function stableId(string $prefix, array $parts): string
    {
        return $prefix . '_' . substr(sha1(implode('|', array_map('strval', $parts))), 0, 24);
    }

    private function jsonEncode($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function decodeJson($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function humanize(string $field): string
    {
        return ucfirst(trim((string) preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace('_', ' ', $field))));
    }
}
