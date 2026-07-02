<?php

$failed = 0;

function cagPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function cagFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function cagAssert(bool $condition, string $message): void
{
    $condition ? cagPass($message) : cagFail($message);
}

function cagNormalize(string $text): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $ascii = $ascii === false ? $text : $ascii;
    $ascii = strtolower($ascii);
    $ascii = preg_replace('/[^a-z0-9\/]+/', ' ', $ascii) ?? '';

    return trim(preg_replace('/\s+/', ' ', $ascii) ?? '');
}

echo "=== Completion audit goal coverage ===\n";

try {
    $goalPath = __DIR__ . '/../goals/catalogo-familias-operativas-contratos-aliases/goal.md';
    $auditPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/completion-audit.md';
    $statusPath = __DIR__ . '/../docs/qa/evidence/catalog-goal-audit-20260702/STATUS.md';

    $goal = file_get_contents($goalPath) ?: '';
    $audit = file_get_contents($auditPath) ?: '';
    $status = file_get_contents($statusPath) ?: '';
    $auditNormalized = cagNormalize($audit);

    preg_match_all('/^- /m', $goal, $goalBullets);
    cagAssert(count($goalBullets[0]) === 9, 'el goal mantiene 9 criterios Done');

    $criteriaRows = substr_count($audit, '| `') + substr_count($audit, '| Administración') + substr_count($audit, '| Legacy') + substr_count($audit, '| Antes de cualquier') + substr_count($audit, '| Evidencia final');
    cagAssert($criteriaRows >= 9, 'la auditoria contiene al menos una fila por criterio del goal');

    $needles = [
        'general_pdc_familias contiene solo familias operativas canonicas',
        'aliases y elementos contractuales viven fuera de general_pdc_familias',
        '/listado-actividades/ no genera propuestas listas con aliases contratos capitulos ubicaciones o contexto',
        '/contratos/ conserva paquetes fuentes e intervenciones sin duplicar actividades',
        '/pdc/ se genera desde contratos',
        'administracion permite mantener familias aliases elementos contractuales reglas impacto auditoria y aprobaciones',
        'legacy directo de listado contratos y pdc queda deprecado o retirado',
        'antes de cualquier borrado destructivo existe backup externo restauracion local y comparacion de datos',
        'evidencia final cubre jmc da porto un metrolinea y milan',
    ];

    foreach ($needles as $needle) {
        cagAssert(str_contains($auditNormalized, cagNormalize($needle)), "auditoria cubre criterio: {$needle}");
    }

    foreach ([
        'decisiones humanas aplicadas',
        'metrolinea',
        'limpieza legacy',
        'marcar el goal completo',
    ] as $decisionNeedle) {
        cagAssert(str_contains($auditNormalized, cagNormalize($decisionNeedle)), "auditoria conserva decision: {$decisionNeedle}");
    }

    cagAssert(str_contains($status, 'completion-audit.md'), 'STATUS enlaza completion-audit.md');
} catch (Throwable $e) {
    cagFail($e->getMessage());
}

echo "=== Completion audit goal coverage: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
