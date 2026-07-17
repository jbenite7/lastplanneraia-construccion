<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Bi\ForecastService;

function forecastAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$service = (new ReflectionClass(ForecastService::class))->newInstanceWithoutConstructor();

$missingFeature = $service->forecastPacExpected([
    'contractor_pac_4w' => 0.75,
    'responsible_pac_4w' => 0.70,
    'is_critical' => false,
    'hard_restrictions_ready' => true,
    'recent_cnc_4w' => 0,
]);
forecastAssert($missingFeature['pac_expected'] === null, 'PAC incompleto no proyecta un valor sintético');
forecastAssert($missingFeature['projection_available'] === false, 'PAC incompleto marca proyección no disponible');
forecastAssert(in_array('current_progress', $missingFeature['missing_features'], true), 'PAC incompleto identifica current_progress faltante');
forecastAssert(is_string($missingFeature['reason']) && $missingFeature['reason'] !== '', 'PAC incompleto explica la indisponibilidad');
forecastAssert($missingFeature['sample']['contractor_pac_4w'] === null, 'PAC incompleto no sustituye una muestra ausente por cero');

$insufficientEvidence = $service->forecastPacExpected([
    'contractor_pac_4w' => 0.75,
    'responsible_pac_4w' => 0.70,
    'contractor_pac_sample_size_4w' => 2,
    'responsible_pac_sample_size_4w' => 3,
    'is_critical' => false,
    'hard_restrictions_ready' => true,
    'current_progress' => 0.60,
    'recent_cnc_4w' => 0,
]);
forecastAssert($insufficientEvidence['pac_expected'] === null, 'PAC sin muestra mínima no fabrica una predicción');
forecastAssert($insufficientEvidence['projection_available'] === false, 'PAC sin muestra mínima marca proyección no disponible');
forecastAssert(in_array('contractor_pac_sample_size_4w', $insufficientEvidence['missing_features'], true), 'PAC identifica evidencia histórica insuficiente');

$availablePac = $service->forecastPacExpected([
    'contractor_pac_4w' => 0.80,
    'responsible_pac_4w' => 0.70,
    'contractor_pac_sample_size_4w' => 4,
    'responsible_pac_sample_size_4w' => 4,
    'is_critical' => false,
    'hard_restrictions_ready' => true,
    'current_progress' => 0.60,
    'recent_cnc_4w' => 0,
]);
forecastAssert($availablePac['pac_expected'] === 0.80, 'PAC completo conserva el cálculo ponderado transparente');
forecastAssert($availablePac['projection_available'] === true, 'PAC completo habilita la proyección');
forecastAssert($availablePac['confidence'] === 1.0, 'PAC completo conserva confianza explícita');
forecastAssert(($availablePac['sample']['contractor_pac_4w'] ?? null) === 4, 'PAC completo expone la muestra del contratista');
forecastAssert(isset($availablePac['metadata']['weights']), 'PAC completo expone metadata del cálculo');

$boundaryPac = $service->forecastPacExpected([
    'contractor_pac_4w' => 0.0,
    'responsible_pac_4w' => 1.0,
    'contractor_pac_sample_size_4w' => 3,
    'responsible_pac_sample_size_4w' => 3,
    'is_critical' => false,
    'hard_restrictions_ready' => true,
    'current_progress' => 1.0,
    'recent_cnc_4w' => 0,
]);
forecastAssert($boundaryPac['projection_available'] === true, 'PAC acepta los bordes válidos 0 y 1');

$invalidPac = $service->forecastPacExpected([
    'contractor_pac_4w' => 1.01,
    'responsible_pac_4w' => 0.70,
    'contractor_pac_sample_size_4w' => 4,
    'responsible_pac_sample_size_4w' => 4,
    'is_critical' => 1,
    'hard_restrictions_ready' => true,
    'current_progress' => -0.01,
    'recent_cnc_4w' => -1,
]);
forecastAssert($invalidPac['projection_available'] === false, 'PAC inválido no proyecta un valor sintético');
forecastAssert(in_array('contractor_pac_4w', $invalidPac['invalid_features'], true), 'PAC inválido identifica PAC fuera de rango');
forecastAssert(in_array('is_critical', $invalidPac['invalid_features'], true), 'PAC inválido exige booleanos reales');
forecastAssert(in_array('recent_cnc_4w', $invalidPac['invalid_features'], true), 'PAC inválido identifica CNC negativo');

$stalledAvance = $service->forecastAvance('4w', 0.40, 0.0, 6);
forecastAssert(($stalledAvance['status'] ?? null) === 'unavailable', 'Avance sin velocidad queda no disponible');
forecastAssert($stalledAvance['projected_progress'] === null, 'Avance sin velocidad no proyecta una línea sintética');

$invalidHorizon = $service->forecastAvance('9w', 0.40, 0.10, 6);
forecastAssert(($invalidHorizon['status'] ?? null) === 'unavailable', 'Avance con horizonte inválido queda no disponible');
forecastAssert($invalidHorizon['projected_progress'] === null, 'Horizonte inválido no usa un horizonte por defecto');

$availableAvance = $service->forecastAvance('2w', 0.40, 0.10, 6);
forecastAssert(($availableAvance['status'] ?? null) === 'available', 'Avance con velocidad y horizonte válidos queda disponible');
forecastAssert($availableAvance['projected_progress'] === 0.60, 'Avance disponible conserva cálculo lineal basado en velocidad observada');

$boundaryAvance = $service->forecastAvance('1w', 0.0, 1.0, 1);
forecastAssert(($boundaryAvance['status'] ?? null) === 'available', 'Avance acepta los bordes válidos 0 y 1');
forecastAssert($boundaryAvance['projected_progress'] === 1.0, 'Avance en el borde conserva el límite de completitud');

$invalidAvance = $service->forecastAvance('1w', 1.01, 0.10, 1);
forecastAssert(($invalidAvance['status'] ?? null) === 'unavailable', 'Avance fuera de rango queda no disponible');
forecastAssert(in_array('current_progress', $invalidAvance['invalid_features'], true), 'Avance fuera de rango identifica el dato inválido');

$stalledDate = $service->forecastFechaFinal('2026-07-31', 0.40, 0.0, 5, '2026-07-10');
forecastAssert($stalledDate['fecha_fin_forecast'] === null, 'Fecha final sin velocidad no inventa una fecha remota');
forecastAssert(($stalledDate['semanas_restantes'] ?? null) === null, 'Fecha final sin velocidad no usa 999 semanas');
forecastAssert(($stalledDate['status'] ?? null) === 'unavailable', 'Fecha final sin velocidad queda no disponible');

$missingCutoffDate = $service->forecastFechaFinal('2026-07-31', 0.40, 0.20, 5);
forecastAssert($missingCutoffDate['fecha_fin_forecast'] === null, 'Fecha final sin corte explícito no inventa una fecha');
forecastAssert(in_array('fecha_corte', $missingCutoffDate['missing_features'], true), 'Fecha final identifica la fecha de corte faltante');

$availableDate = $service->forecastFechaFinal('2026-07-31', 0.40, 0.20, 5, '2026-07-10');
forecastAssert($availableDate['fecha_fin_forecast'] === '2026-07-31', 'Fecha final disponible parte de la fecha de corte observada');
forecastAssert($availableDate['dias_desplazamiento'] === 21, 'Fecha final no suma el atraso acumulado dos veces');
forecastAssert($availableDate['semanas_restantes'] === 3, 'Fecha final disponible informa semanas calculadas');

echo "OK: BI forecast contract\n";
