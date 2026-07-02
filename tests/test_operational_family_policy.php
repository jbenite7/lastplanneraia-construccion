<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Support\OperationalFamilyPolicy;

$failed = 0;
$policy = new OperationalFamilyPolicy();
$db = Database::getInstance();

function ofpPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function ofpFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function ofpAssert(bool $condition, string $message): void
{
    $condition ? ofpPass($message) : ofpFail($message);
}

echo "=== Operational family policy ===\n";

$source = file_get_contents(__DIR__ . '/../src/Support/OperationalFamilyPolicy.php') ?: '';
ofpAssert(!str_contains($source, 'FALLBACK_FAMILY_ALIASES'), 'aliases se leen desde BD, no desde arreglo hardcodeado');
ofpAssert(!str_contains($source, 'FALLBACK_CONTRACTUAL_ONLY'), 'contractuales se leen desde BD, no desde arreglo hardcodeado');

$aliasCount = (int) $db->query(
    'SELECT COUNT(*) FROM general_pdc_family_aliases WHERE activa = 1',
)->fetchColumn();
$contractualFamilyCount = (int) $db->query(
    'SELECT COUNT(DISTINCT nombre_normalizado) FROM general_pdc_contractual_elements WHERE activa = 1',
)->fetchColumn();
ofpAssert(count($policy->familyAliases()) === $aliasCount, 'policy carga todos los aliases activos desde BD');
ofpAssert(count($policy->contractualOnlyFamilies()) === $contractualFamilyCount, 'policy carga todos los contractuales activos desde BD');

ofpAssert($policy->normalizeOperationalFamily('Enchapes Ceramicos en Muros') === 'Pisos y Enchapes', 'absorbe enchapes en Pisos y Enchapes');
ofpAssert($policy->normalizeOperationalFamily('Red RCI') === OperationalFamilyPolicy::RCI_FAMILY, 'normaliza Red RCI');
ofpAssert($policy->normalizeOperationalFamily('Red Contra Incendio - Piping') === OperationalFamilyPolicy::RCI_FAMILY, 'normaliza Red Contra Incendio - Piping');

foreach ($policy->contractualOnlyFamilies() as $family) {
    ofpAssert($policy->isContractualOnlyFamily($family), "{$family} es contractual");
    ofpAssert(!$policy->isOperationalFamilyAllowedForListado($family), "{$family} no va en listado");
    ofpAssert(!empty($policy->contractualPackageHints($family)), "{$family} tiene paquete contractual sugerido");
}

$hints = $policy->contractualPackageHintsForText('Compra de acero de refuerzo para estructura');
ofpAssert(!empty($hints) && ($hints[0]['paqueteNombre'] ?? '') === 'ACERO DE REFUERZO', 'detecta paquete contractual desde texto');
$structureHints = $policy->contractualPackageHintsForText('Estructura en Concreto');
ofpAssert(empty(array_filter($structureHints, static fn(array $hint): bool => ($hint['sourceFamily'] ?? '') === 'Mano de Obra - Estructura')), 'estructura sola no activa mano de obra contractual');
$laborHints = $policy->contractualPackageHintsForText('Mano de obra estructura en concreto');
ofpAssert(!empty(array_filter($laborHints, static fn(array $hint): bool => ($hint['sourceFamily'] ?? '') === 'Mano de Obra - Estructura')), 'mano de obra estructura sí activa contrato de mano de obra');
ofpAssert($policy->isOperationalFamilyAllowedForListado('Pisos y Enchapes'), 'Pisos y Enchapes sí va en listado');

echo "=== Operational family policy: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
