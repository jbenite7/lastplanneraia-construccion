<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Support\SemiAutoQualityGate;

$failed = 0;

function qgPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function qgFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function qgAssert(bool $condition, string $message): void
{
    $condition ? qgPass($message) : qgFail($message);
}

function qgStatus(array $result): string
{
    return (string) ($result['quality_gate']['status'] ?? '');
}

echo "=== Semi-auto quality gate ===\n";

$gate = new SemiAutoQualityGate();
$match = [
    'id' => 10,
    'familia_id' => 20,
    'familia_codigo' => 'CABINAS_BANO',
    'familia_nombre' => 'Cabinas de Bano',
    'matchedBy' => 'nombre',
    'confidence' => 95,
    'reviewRequired' => false,
    'siempre_revision' => 0,
];

$mixed = $gate->listado([
    ['unique_id' => 101, 'Actividad' => '[Capitulo: Banos] Retiro de cabinas de bano existentes', 'Fecha_Inicio' => '2030-01-01'],
    ['unique_id' => 102, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano nuevas', 'Fecha_Inicio' => '2030-01-02'],
], $match, 95);
qgAssert(qgStatus($mixed) === 'conflict', 'retiro + instalacion bloquea estado listo');
qgAssert(!empty($mixed['quality_gate']['ready_blockers']), 'conflicto deja bloqueadores de listo');

$single = $gate->listado([
    ['unique_id' => 103, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano nuevas', 'Fecha_Inicio' => '2030-01-03'],
], $match, 95);
qgAssert(qgStatus($single) === 'ready', 'fuente homogenea con evidencia queda lista');
qgAssert(($single['quality_gate']['start_activity_label'] ?? '') === '[Capitulo: Banos] Instalacion de cabinas de bano nuevas | 2030-01-03', 'actividad de inicio usa Actividad | Fecha Inicio');
$specificName = $gate->activityName([
    ['unique_id' => 103, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano nuevas', 'Fecha_Inicio' => '2030-01-03'],
], $match, $single);
qgAssert($specificName === 'Cabinas de Bano', 'nombre base no usa ubicacion ni texto fuente como sufijo');

$contractualMatch = $match;
$contractualMatch['familia_codigo'] = 'ACERO_REFUERZO';
$contractualMatch['familia_nombre'] = 'Acero de Refuerzo y Estructural';
$contractualMatch['contractual_only'] = true;
$contractual = $gate->listado([
    ['unique_id' => 106, 'Actividad' => '[Capitulo: Estructura] Acero de refuerzo', 'Fecha_Inicio' => '2030-01-06'],
], $contractualMatch, 95);
qgAssert(qgStatus($contractual) === 'conflict', 'elemento contractual no queda listo en listado');
qgAssert(in_array('Un elemento contractual no puede quedar listo como familia de actividades.', $contractual['quality_gate']['ready_blockers'] ?? [], true), 'bloqueo explica que va en contratos');

$sameOperationA = ['unique_id' => 111, 'Actividad' => '[Capitulo: Estructura 5A] Instalacion concreto Eje 48', 'Fecha_Inicio' => '2030-01-11'];
$sameOperationB = ['unique_id' => 112, 'Actividad' => '[Capitulo: Estructura 5B] Instalacion concreto Eje 49', 'Fecha_Inicio' => '2030-01-12'];
qgAssert(
    $gate->operationalGroupingKey($sameOperationA, $match) === $gate->operationalGroupingKey($sameOperationB, $match),
    'misma familia y operacion agrupa igual aunque cambie contexto o eje'
);

$differentTextSameFamilyA = ['unique_id' => 113, 'Actividad' => '[Capitulo: Torre 3] Piso 11', 'Fecha_Inicio' => '2030-01-13'];
$differentTextSameFamilyB = ['unique_id' => 114, 'Actividad' => '[Capitulo: Torre 3] Piso 12', 'Fecha_Inicio' => '2030-01-14'];
qgAssert(
    $gate->operationalGroupingKey($differentTextSameFamilyA, $match) === $gate->operationalGroupingKey($differentTextSameFamilyB, $match),
    'una familia no se duplica por piso, zona, etapa o texto fuente'
);
$familyOnly = $gate->activityName([$differentTextSameFamilyA, $differentTextSameFamilyB], $match, $single, true);
qgAssert($familyOnly === 'Cabinas de Bano', 'nombre de actividad queda en familia canonica sin sufijos de contexto');

$familyDescription = $gate->description([$differentTextSameFamilyA, $differentTextSameFamilyB], $match, $single);
qgAssert(
    !str_contains($familyDescription, 'piso') && !str_contains($familyDescription, 'Piso'),
    'descripcion no usa piso, staff ni texto fuente como alcance'
);

$genericName = $gate->activityName([
    ['unique_id' => 110, 'Actividad' => '[Capitulo: Banos] Cabinas de Bano', 'Fecha_Inicio' => '2030-01-10'],
], $match, $single);
$reviewedGeneric = $gate->isSpecificName($genericName, $match)
    ? $single
    : $gate->withReviewReason($single, 'La familia aparece en varias propuestas y este subgrupo no tiene un nombre suficientemente específico.');
qgAssert(qgStatus($reviewedGeneric) === 'review', 'familia repetida sin nombre especifico baja a revision');

$homogeneousReview = $gate->listado([
    ['unique_id' => 104, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano piso 1', 'Fecha_Inicio' => '2030-01-04'],
    ['unique_id' => 105, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano piso 2', 'Fecha_Inicio' => '2030-01-05'],
], $match, 95);
$description = $gate->description([
    ['unique_id' => 104, 'Actividad' => 'Instalacion de cabinas de bano piso 1', 'Fecha_Inicio' => '2030-01-04'],
    ['unique_id' => 105, 'Actividad' => 'Instalacion de cabinas de bano piso 2', 'Fecha_Inicio' => '2030-01-05'],
], $match, $homogeneousReview);
qgAssert(!str_contains($description, ','), 'descripcion no concatena fuentes con comas');

$missingDate = $gate->listado([
    ['unique_id' => 120, 'Actividad' => '[Capitulo: Banos] Instalacion de cabinas de bano sin fecha', 'Fecha_Inicio' => null],
], $match, 95);
qgAssert(qgStatus($missingDate) === 'conflict', 'fuente sin fecha no queda lista');

$noMatch = $gate->noMatch(['unique_id' => 106, 'Actividad' => 'Actividad sin clasificar', 'Fecha_Inicio' => '2030-01-06'], 'Sin familia detectada.');
qgAssert(qgStatus($noMatch) === 'conflict', 'sin familia confiable no es aplicable');

$contract = $gate->contratos(
    ['Id' => 1, 'actividad' => 'Griferias e incrustaciones', 'actividadInicio' => 107, 'fechaInicio' => '2030-01-07'],
    ['unique_id' => 107, 'Actividad' => '[Capitulo: Acabados] Compra de griferias e incrustaciones', 'Fecha_Inicio' => '2030-01-07'],
    array_merge($match, ['familia_codigo' => 'GRIFERIAS_INCRUSTACIONES', 'familia_nombre' => 'Griferias e incrustaciones']),
    [['tipoPaquete' => 'Orden de Compra', 'paqueteNombre' => 'GRIFERIAS E INCRUSTACIONES']],
    90,
);
qgAssert(qgStatus($contract) === 'ready', 'contrato con paquete claro queda listo');

$contractReview = $gate->contratos(
    ['Id' => 2, 'actividad' => 'Red electrica pendiente diseno', 'actividadInicio' => 108, 'fechaInicio' => '2030-01-08'],
    ['unique_id' => 108, 'Actividad' => '[Capitulo: Redes] Red electrica pendiente diseno', 'Fecha_Inicio' => '2030-01-08'],
    array_merge($match, ['familia_codigo' => 'RED_ELECTRICA', 'familia_nombre' => 'Red Electrica']),
    [],
    85,
);
qgAssert(qgStatus($contractReview) === 'review', 'contrato sin paquetes o con diseno pendiente queda por revisar');

$pdcReview = $gate->pdc(
    ['actividad' => 'Actividad PDC', 'actividadInicio' => 109, 'fechaInicio' => '2030-01-09'],
    ['tipoPaquete' => 'Suministro', 'paqueteNombre' => 'APARATOS SANITARIOS'],
    ['estado' => 'Estado existente', 'contratos' => 'Contrato previo'],
    [['field' => 'estado', 'from' => 'Estado existente', 'to' => 'Nuevo estado']],
    90,
);
qgAssert(qgStatus($pdcReview) === 'review', 'PDC update sobre campo existente queda por revisar');

echo "=== Semi-auto quality gate: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
