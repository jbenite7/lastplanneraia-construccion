<?php

/**
 * Async consolidation runner.
 * Called via exec() from DashboardController::runReportes().
 *
 * CLI args: 1=token, 2=username (for audit log)
 * Progress is written to /tmp/lps-consolidation-{token}.json
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Access denied\n");
}

// --- Bootstrap ---
define('ADMIN_PROJECT_ROOT', __DIR__ . '/..');

require_once ADMIN_PROJECT_ROOT . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ADMIN_PROJECT_ROOT . '/..');
$dotenv->load();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ADMIN_PROJECT_ROOT . '/logs/php_error.log');
date_default_timezone_set('America/Bogota');

require_once ADMIN_PROJECT_ROOT . '/../src/Core/Database.php';
try {
    $db = Database::getInstance();
} catch (\Exception $e) {
    error_log('[ASYNC_CONSOLIDATE] DB init error: ' . $e->getMessage());
    exit(1);
}

// --- Parse CLI args ---
$token = $argv[1] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
    error_log('[ASYNC_CONSOLIDATE] Invalid token: ' . $token);
    exit(1);
}
$usuario = $argv[2] ?? 'admin';

// --- Set up progress tracker ---
require_once ADMIN_PROJECT_ROOT . '/../src/Core/ProgressTracker.php';
use App\Core\ProgressTracker;

$tracker = new ProgressTracker($token);

// Count steps
$allCount = (int) $db->query(
    "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1",
)->fetchColumn();
$pdcCount = (int) $db->query(
    "SELECT COUNT(*) FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1",
)->fetchColumn();
$totalSteps = $allCount * 5 + $pdcCount * 2;

$tracker->init($totalSteps);

ignore_user_abort(true);
set_time_limit(0);

// --- Run consolidation ---
require_once ADMIN_PROJECT_ROOT . '/../src/Services/ReportProcessor.php';
use App\Services\ReportProcessor;

$processor = new ReportProcessor();
$parts = [];
$hasErrors = false;

$processor->setProgressCallback(function (string $reportLabel, string $project, int $index, int $total, string $status, ?string $message = null) use ($tracker) {
    $tracker->update($reportLabel, $project, $index, $total, $status, $message);
});

$processor->setSubprocessCallback(function (string $step, string $project, string $subprocess, string $status, ?string $message = null) use ($tracker) {
    $tracker->updateSubprocess($step, $project, $subprocess, $status, $message);
});

register_shutdown_function(function () use ($tracker) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $msg = sprintf('FATAL: %s in %s:%d', $error['message'], $error['file'], $error['line']);
        error_log('[ASYNC_CONSOLIDATE] ' . $msg);
        $tracker->update('', '', 0, 0, 'error', $msg);
    }
});

$steps = [
    'Curva S'         => 'generateCurvaS',
    'General'         => 'generateReporteGeneral',
    'Restricciones'   => 'generateRestriccionesGeneral',
    'PDC'             => 'generateReportePDC',
    'Subcontratistas' => 'generateReporteSubcontratistas',
];

foreach ($steps as $label => $method) {
    try {
        error_log('[ASYNC_CONSOLIDATE] Starting step: ' . $label);
        $processor->{$method}();
        $parts[] = "\xE2\x9C\x93 {$label}";
        error_log('[ASYNC_CONSOLIDATE] Completed step: ' . $label);
    } catch (\Exception $e) {
        $hasErrors = true;
        $parts[] = "\xE2\x9C\x97 {$label} (" . $e->getMessage() . ")";
        error_log('[ASYNC_CONSOLIDATE] Exception in step ' . $label . ': ' . $e->getMessage());
    }
}

try {
    $processor->updateCICProyectos(null);
    $parts[] = "\xE2\x9C\x93 CIC";
} catch (\Exception $e) {
    $hasErrors = true;
    $parts[] = "\xE2\x9C\x97 CIC (" . $e->getMessage() . ")";
}

foreach (($tracker->toArray()['history'] ?? []) as $entry) {
    if (($entry['status'] ?? '') === 'error') {
        $hasErrors = true;
        break;
    }
}

// --- Audit log ---
$prefix = $hasErrors ? 'Reportes consolidados con errores' : 'Reportes consolidados';
$message = $prefix . ': ' . implode(', ', $parts);
$db->logActivity('Admin', 'CONSOLIDAR_REPORTES', "{$usuario} ejecutó consolidación de reportes. {$message}", null);

// --- Mark complete ---
$tracker->complete(!$hasErrors, $message);

error_log('[ASYNC_CONSOLIDATE] Finished. ' . $message);
exit(0);
