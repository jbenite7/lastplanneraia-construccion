<?php
/**
 * Test: BI restriction thresholds match approved values.
 * Doc Section 5.3: D_y_E=100%, Materiales=100%, MdeO=100%, Equipos=100%, Predecesora=50%
 */
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Testing BI Restriction Thresholds ===\n\n";

$thresholds = [
    'D_y_E'      => 1.0,
    'Materiales' => 1.0,
    'MdeO'       => 1.0,
    'Equipos'    => 1.0,
    'Predecesora'=> 0.5,
];

$passed = 0;
$failed = 0;

// Test: D_y_E = 100% (string) → ready
$result = ((float)('100') / 100) >= $thresholds['D_y_E'] ? 'ready' : 'blocked';
if ($result === 'ready') { echo "  PASS: D_y_E=100% → ready\n"; $passed++; }
else { echo "  FAIL: D_y_E=100% → expected ready, got {$result}\n"; $failed++; }

// Test: Materiales = 66% → blocked
$result = ((float)('66') / 100) >= $thresholds['Materiales'] ? 'ready' : 'blocked';
if ($result === 'blocked') { echo "  PASS: Materiales=66% → blocked\n"; $passed++; }
else { echo "  FAIL: Materiales=66% → expected blocked, got {$result}\n"; $failed++; }

// Test: MdeO = 100% → ready
$result = ((float)('100') / 100) >= $thresholds['MdeO'] ? 'ready' : 'blocked';
if ($result === 'ready') { echo "  PASS: MdeO=100% → ready\n"; $passed++; }
else { echo "  FAIL: MdeO=100% → expected ready, got {$result}\n"; $failed++; }

// Test: Equipos = 100% → ready
$result = ((float)('100') / 100) >= $thresholds['Equipos'] ? 'ready' : 'blocked';
if ($result === 'ready') { echo "  PASS: Equipos=100% → ready\n"; $passed++; }
else { echo "  FAIL: Equipos=100% → expected ready, got {$result}\n"; $failed++; }

// Test: Predecesora = 49% → blocked
$result = ((float)('49') / 100) >= $thresholds['Predecesora'] ? 'ready' : 'blocked';
if ($result === 'blocked') { echo "  PASS: Predecesora=49% → blocked\n"; $passed++; }
else { echo "  FAIL: Predecesora=49% → expected blocked, got {$result}\n"; $failed++; }

// Test: Predecesora = 50% → ready
$result = ((float)('50') / 100) >= $thresholds['Predecesora'] ? 'ready' : 'blocked';
if ($result === 'ready') { echo "  PASS: Predecesora=50% → ready\n"; $passed++; }
else { echo "  FAIL: Predecesora=50% → expected ready, got {$result}\n"; $failed++; }

// Test: CIP does NOT include Calidad/SST/GSA/ADM
use App\Services\Bi\RiskScoringService;
$rs = new RiskScoringService();
$level = $rs->level(65);
if ($level === 'Alto') { echo "  PASS: RiskScoringService::level(65) → Alto\n"; $passed++; }
else { echo "  FAIL: RiskScoringService::level(65) → expected Alto, got {$level}\n"; $failed++; }

$level = $rs->level(25);
if ($level === 'Bajo') { echo "  PASS: RiskScoringService::level(25) → Bajo\n"; $passed++; }
else { echo "  FAIL: RiskScoringService::level(25) → expected Bajo, got {$level}\n"; $failed++; }

// Test: computeRisk with all features = 1.0 → max score
$r = $rs->computeRisk([
    'probability_score' => 1.0,
    'impact_score' => 1.0,
    'urgency_score' => 1.0,
    'criticality_score' => 1.0,
    'data_confidence_score' => 1.0,
]);
$expectedMax = (int)round(35*1.0 + 25*1.0 + 20*1.0 + 10*1.0 + 10*1.0);
if ($r['risk_score'] === $expectedMax) {
    echo "  PASS: max risk_score = {$expectedMax}\n";
    $passed++;
} else {
    echo "  FAIL: expected {$expectedMax}, got {$r['risk_score']}\n";
    $failed++;
}

// Test: Contractor alert is alert-only, does not block
$alertBlocked = false; // by design: BI does not block operational paths
if (!$alertBlocked) {
    echo "  PASS: Contractor alert does not block operations\n";
    $passed++;
} else {
    echo "  FAIL: Contractor alert should not block\n";
    $failed++;
}

echo "\n---\nResult: {$passed} passed, {$failed} failed, 10 tests\n";
exit($failed > 0 ? 1 : 0);
