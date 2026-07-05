<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\SemiAutoService;
use App\Services\SemiAutoAssistantService;
use App\Support\ModuleRequestContext;

$db = Database::getInstance();
$service = new SemiAutoService($db);
$assistant = new SemiAutoAssistantService($db);
$failed = 0;
$skipped = 0;
$runId = null;
$runIds = [];
$cleanupProjectId = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function passSemi(string $message): void
{
    echo "  PASS: {$message}\n";
}

function failSemi(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function skipSemi(string $message): void
{
    global $skipped;
    echo "  SKIP: {$message}\n";
    $skipped++;
}

function tableCount(Database $db, string $table, int $projectId, int $week): int
{
    $weekColumn = $table === 'actividades' ? 'semanaActualizacion' : 'semana';
    return (int) $db->query(
        "SELECT COUNT(*) FROM {$table} WHERE project_id = ? AND {$weekColumn} = ?",
        [$projectId, $week],
    )->fetchColumn();
}

echo "=== Semi-auto service ===\n";

$_SESSION['usuario'] = 'jbenitez';
$_SESSION['db'] = 'prueba';
$_SESSION['semana'] = 7;
$_SESSION['permiso'] = 'D';
$_SESSION['permiso_canonico'] = 'D';
unset($_SESSION['project_id']);

try {
    $context = ModuleRequestContext::resolve(['allow_zero_week' => false]);
    if (($context['dbPrefix'] ?? '') !== 'prueba' || (int) ($context['projectId'] ?? 0) <= 0) {
        failSemi('ModuleRequestContext did not resolve prueba/project_id');
    } else {
        passSemi('ModuleRequestContext resolves global project_id');
    }

    $projectId = (int) $context['projectId'];
    $cleanupProjectId = $projectId;
    $week = (int) $context['semana'];
    $activitiesBeforePreview = tableCount($db, 'actividades', $projectId, $week);

    $preview = $service->preview(SemiAutoService::MODULE_LISTADO, $context);
    $runId = (string) ($preview['run_id'] ?? '');
    $runIds[] = $runId;
    $runId !== '' ? passSemi('preview returns run_id') : failSemi('preview did not return run_id');

    $analysis = $preview['analysis'] ?? [];
    !empty($analysis['steps']) && (int) ($analysis['progress'] ?? 0) === 100
        ? passSemi('preview returns analysis steps')
        : failSemi('preview did not return completed analysis');

    !empty($preview['assistant_summary']) && isset($preview['assistant_recommendations'])
        ? passSemi('preview returns assistant summary and recommendations')
        : failSemi('preview missing assistant summary or recommendations');

    $status = $service->status(SemiAutoService::MODULE_LISTADO, $context, $runId);
    ($status['respuesta'] ?? '') === 'BIEN' && (int) ($status['progress'] ?? 0) === 100
        ? passSemi('status returns completed analysis')
        : failSemi('status did not return completed analysis');

    $pendingStatus = $service->status(SemiAutoService::MODULE_LISTADO, $context, 'run_pending_poll_test');
    ($pendingStatus['respuesta'] ?? '') === 'BIEN' && ($pendingStatus['status'] ?? '') === 'pending'
        ? passSemi('status tolerates early polling before run exists')
        : failSemi('status returned an error for early polling');

    $activitiesAfterPreview = tableCount($db, 'actividades', $projectId, $week);
    $activitiesAfterPreview === $activitiesBeforePreview
        ? passSemi('preview does not write actividades')
        : failSemi('preview changed actividades count');

    $badConfidence = [];
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        $confidence = (float) ($suggestion['confidence'] ?? -1);
        if ($confidence < 0 || $confidence > 100) {
            $badConfidence[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
    }
    $badConfidence === []
        ? passSemi('confidence values are normalized to 0-100')
        : failSemi('confidence outside 0-100: ' . implode(', ', $badConfidence));

    $missingUserAnalysis = [];
    $missingAssistantReasoning = [];
    $missingQualityGate = [];
    $preselectedWithoutReadyGate = [];
    $unexpectedTechnical = [];
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        if (empty($suggestion['analysis']['user'])) {
            $missingUserAnalysis[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
        $gate = $suggestion['analysis']['quality_gate'] ?? null;
        if (empty($gate) || empty($gate['status'])) {
            $missingQualityGate[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
        if (!empty($suggestion['preselected']) && (($gate['status'] ?? '') !== 'ready')) {
            $preselectedWithoutReadyGate[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
        if (empty($suggestion['assistant_reasoning']['next_step'])) {
            $missingAssistantReasoning[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
        if (isset($suggestion['analysis']['technical'])) {
            $unexpectedTechnical[] = $suggestion['suggestion_id'] ?? '(unknown)';
        }
    }
    $missingUserAnalysis === []
        ? passSemi('suggestions include user analysis')
        : failSemi('suggestions missing user analysis: ' . implode(', ', $missingUserAnalysis));
    $missingQualityGate === []
        ? passSemi('suggestions include quality gate')
        : failSemi('suggestions missing quality gate: ' . implode(', ', $missingQualityGate));
    $preselectedWithoutReadyGate === []
        ? passSemi('preselected suggestions passed quality gate')
        : failSemi('preselected suggestion without ready quality gate: ' . implode(', ', $preselectedWithoutReadyGate));
    $unexpectedTechnical === []
        ? passSemi('non-admin preview hides technical analysis')
        : failSemi('non-admin preview exposed technical analysis');
    $missingAssistantReasoning === []
        ? passSemi('suggestions include assistant reasoning')
        : failSemi('suggestions missing assistant reasoning: ' . implode(', ', $missingAssistantReasoning));

    $rowCount = (int) $db->query(
        "SELECT COUNT(*) FROM semi_auto_suggestions WHERE run_id = ? AND project_id = ?",
        [$runId, $projectId],
    )->fetchColumn();
    $rowCount === (int) ($preview['total'] ?? 0)
        ? passSemi('preview persists stored suggestions only')
        : failSemi("stored suggestion count {$rowCount} differs from response");

    $inbox = $assistant->inbox(SemiAutoService::MODULE_LISTADO, $context);
    ($inbox['respuesta'] ?? '') === 'BIEN' && isset($inbox['diagnostics'])
        ? passSemi('assistant inbox returns diagnostics')
        : failSemi('assistant inbox did not return diagnostics');

    $assistantFeedback = $assistant->assistantFeedback(SemiAutoService::MODULE_LISTADO, $context, [
        'run_id' => $runId,
        'feedback_type' => 'usefulness',
        'rating' => 'helpful',
    ]);
    ($assistantFeedback['respuesta'] ?? '') === 'BIEN'
        ? passSemi('assistant feedback is recorded')
        : failSemi('assistant feedback failed');

    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';
    $adminPreview = $service->preview(SemiAutoService::MODULE_LISTADO, $context);
    $adminRunId = (string) ($adminPreview['run_id'] ?? '');
    if ($adminRunId !== '') {
        $runIds[] = $adminRunId;
    }
    $technicalSeen = false;
    foreach (($adminPreview['suggestions'] ?? []) as $suggestion) {
        if (isset($suggestion['analysis']['technical'])) {
            $technicalSeen = true;
            break;
        }
    }
    empty($adminPreview['suggestions']) || $technicalSeen
        ? passSemi('admin preview includes technical analysis')
        : failSemi('admin preview did not include technical analysis');

    $applyCandidate = null;
    foreach (($preview['suggestions'] ?? []) as $suggestion) {
        if (($suggestion['action'] ?? '') === 'create_activity' && !empty($suggestion['preselected']) && count($suggestion['analysis']['sources'] ?? []) > 1) {
            $applyCandidate = $suggestion;
            break;
        }
    }
    if ($applyCandidate === null) {
        foreach (($preview['suggestions'] ?? []) as $suggestion) {
            if (($suggestion['action'] ?? '') === 'create_activity' && !empty($suggestion['preselected'])) {
                $applyCandidate = $suggestion;
                break;
            }
        }
    }

    if ($applyCandidate === null) {
        skipSemi('No create_activity suggestion available for apply/undo');
    } else {
        $applyCandidateId = (string) ($applyCandidate['suggestion_id'] ?? '');
        $sources = $applyCandidate['analysis']['sources'] ?? [];
        $selectedSource = null;
        if (count($sources) > 1) {
            $selectedSource = $sources[count($sources) - 1];
            $sourceFeedback = $service->feedback(SemiAutoService::MODULE_LISTADO, $context, [
                'run_id' => $runId,
                'suggestion_id' => $applyCandidateId,
                'feedback_type' => 'inline_correction',
                'corrected' => ['selectedSourceId' => (string) ($selectedSource['unique_id'] ?? '')],
            ]);
            !empty($sourceFeedback['updated_suggestion'])
                ? passSemi('feedback updates selected activity source')
                : failSemi('feedback did not update selected activity source');
        }

        $modalityFeedback = $service->feedback(SemiAutoService::MODULE_LISTADO, $context, [
            'run_id' => $runId,
            'suggestion_id' => $applyCandidateId,
            'feedback_type' => 'inline_correction',
            'corrected' => ['tipoContrato' => 'MO,S'],
        ]);
        !empty($modalityFeedback['updated_suggestion'])
            ? passSemi('feedback accepts multiple contract modalities')
            : failSemi('feedback did not accept multiple contract modalities');

        $invalidModalityFailed = false;
        try {
            $service->feedback(SemiAutoService::MODULE_LISTADO, $context, [
                'run_id' => $runId,
                'suggestion_id' => $applyCandidateId,
                'feedback_type' => 'inline_correction',
                'corrected' => ['tipoContrato' => 'SI,MO'],
            ]);
        } catch (Throwable $e) {
            $invalidModalityFailed = true;
        }
        $invalidModalityFailed
            ? passSemi('feedback rejects SI mixed with other modalities')
            : failSemi('feedback accepted invalid SI modality mix');
        $correctedName = 'E2E semi-auto corrected ' . date('His');
        $feedback = $service->feedback(SemiAutoService::MODULE_LISTADO, $context, [
            'run_id' => $runId,
            'suggestion_id' => $applyCandidateId,
            'feedback_type' => 'inline_correction',
            'corrected' => ['actividad' => $correctedName],
        ]);
        !empty($feedback['updated_suggestion'])
            ? passSemi('feedback updates stored suggestion')
            : failSemi('feedback did not update stored suggestion');

        $candidates = $assistant->learningCandidates(SemiAutoService::MODULE_LISTADO, $context);
        $candidate = $candidates['candidates'][0] ?? null;
        $candidate !== null
            ? passSemi('feedback creates learning candidate')
            : failSemi('feedback did not create learning candidate');

        if ($candidate !== null) {
            $_SESSION['permiso'] = 'A';
            $_SESSION['permiso_canonico'] = 'A';
            $approved = $assistant->approveLearning(SemiAutoService::MODULE_LISTADO, $context, [
                'candidate_id' => $candidate['candidate_id'],
            ]);
            ($approved['respuesta'] ?? '') === 'BIEN' && !empty($approved['rule_id'])
                ? passSemi('admin approves learning candidate')
                : failSemi('admin could not approve learning candidate');
            $_SESSION['permiso'] = 'D';
            $_SESSION['permiso_canonico'] = 'D';
        }

        $apply = $service->apply(SemiAutoService::MODULE_LISTADO, $context, $runId, [$applyCandidateId]);
        ((int) ($apply['aplicadas'] ?? 0) === 1 && (int) ($apply['errores'] ?? 0) === 0)
            ? passSemi('apply accepts only stored suggestion_id')
            : failSemi('apply did not apply exactly one stored suggestion');

        $activitiesAfterApply = tableCount($db, 'actividades', $projectId, $week);
        $activitiesAfterApply === $activitiesBeforePreview + 1
            ? passSemi('apply writes one actividad transactionally')
            : failSemi('apply did not create exactly one actividad');

        $created = $db->query(
            "SELECT Id, actividad, actividadInicio, fechaInicio, tipoContrato FROM actividades
             WHERE project_id = ? AND semanaActualizacion = ?
             ORDER BY Id DESC LIMIT 1",
            [$projectId, $week],
        )->fetch(PDO::FETCH_ASSOC) ?: [];
        $createdName = (string) ($created['actividad'] ?? '');
        $createdId = (int) ($created['Id'] ?? 0);
        $createdName === $correctedName
            ? passSemi('apply uses corrected stored suggestion')
            : failSemi('apply did not use corrected stored suggestion');
        (string) ($created['tipoContrato'] ?? '') === 'MO,S'
            ? passSemi('apply persists multiple contract modalities')
            : failSemi('apply did not persist multiple contract modalities');
        if ($selectedSource !== null) {
            ((int) ($created['actividadInicio'] ?? 0) === (int) ($selectedSource['unique_id'] ?? 0)
                && (string) ($created['fechaInicio'] ?? '') === (string) ($selectedSource['start_date'] ?? ''))
                ? passSemi('apply persists selected source and derived start date')
                : failSemi('apply did not persist selected source and derived start date');
        }

        $sourceRows = (int) $db->query(
            "SELECT COUNT(*) FROM actividad_programa_fuentes WHERE project_id = ? AND actividad_id = ? AND semana = ?",
            [$projectId, $createdId, $week],
        )->fetchColumn();
        $sourceRows > 0
            ? passSemi('apply stores multi-source traceability for contracts')
            : failSemi('apply did not store activity program sources');

        $undo = $service->undo(SemiAutoService::MODULE_LISTADO, $context, $runId);
        ((int) ($undo['revertidas'] ?? 0) === 1 && (int) ($undo['errores'] ?? 0) === 0)
            ? passSemi('undo reverts applied suggestion')
            : failSemi('undo did not revert exactly one suggestion');

        $activitiesAfterUndo = tableCount($db, 'actividades', $projectId, $week);
        $activitiesAfterUndo === $activitiesBeforePreview
            ? passSemi('undo restores actividades count')
            : failSemi('undo left actividades changed');
    }
} catch (Throwable $e) {
    failSemi($e->getMessage());
} finally {
    foreach (array_unique(array_filter($runIds)) as $cleanupRunId) {
        $db->query("DELETE FROM semi_auto_decisions WHERE run_id = ?", [$cleanupRunId]);
        $db->query("DELETE FROM semi_auto_suggestions WHERE run_id = ?", [$cleanupRunId]);
        $db->query("DELETE FROM semi_auto_runs WHERE run_id = ?", [$cleanupRunId]);
    }
    if ($cleanupProjectId !== null) {
        $db->query("DELETE FROM actividad_programa_fuentes WHERE project_id = ?", [$cleanupProjectId]);
        $db->query("DELETE FROM semi_auto_assistant_feedback WHERE project_id = ?", [$cleanupProjectId]);
        $db->query("DELETE FROM semi_auto_learning_rules WHERE project_id = ?", [$cleanupProjectId]);
        $db->query("DELETE FROM semi_auto_learning_candidates WHERE project_id = ?", [$cleanupProjectId]);
        $db->query("DELETE FROM semi_auto_proactive_queue WHERE project_id = ?", [$cleanupProjectId]);
    }
}

echo "=== Semi-auto service: {$failed} failed, {$skipped} skipped ===\n";
exit($failed === 0 ? 0 : 1);
