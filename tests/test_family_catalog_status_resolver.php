<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Support\FamilyCatalogStatusResolver;

$failed = 0;

function catalogStatusPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function catalogStatusFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function catalogStatusAssert(bool $condition, string $message): void
{
    $condition ? catalogStatusPass($message) : catalogStatusFail($message);
}

echo "=== Family catalog status resolver ===\n";

try {
    $db = Database::getInstance();
    $resolver = new FamilyCatalogStatusResolver($db);

    $pisos = $db->query(
        "SELECT id, codigo, nombre, categoria, siempre_revision, COALESCE(activa, 1) AS activa
         FROM general_pdc_familias
         WHERE codigo = 'PISOS_ENCHAPES'
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    catalogStatusAssert(is_array($pisos), 'existe caso Pisos y Enchapes');
    if (is_array($pisos)) {
        $status = $resolver->statusForFamily($pisos);
        catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::CREATES_ACTIVITIES, 'Pisos y Enchapes crea actividades');
        catalogStatusAssert(str_contains($status['reason'], 'paquetes') || $status['has_contract_options'], 'Pisos y Enchapes explica paquetes');
        catalogStatusAssert($status['next_action'] !== '', 'Pisos y Enchapes tiene siguiente acción');
    }

    $campamento = $db->query(
        "SELECT id, codigo, nombre, categoria, siempre_revision, COALESCE(activa, 1) AS activa
         FROM general_pdc_familias
         WHERE codigo = 'CAMPAMENTO'
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    catalogStatusAssert(is_array($campamento), 'existe caso Campamento de Obra');
    if (is_array($campamento)) {
        $status = $resolver->statusForFamily($campamento);
        catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::MANAGED_IN_CONTRACTS, 'Campamento se gestiona en Contratos');
        catalogStatusAssert($status['package_hint'] !== '', 'Campamento muestra paquete sugerido');
        catalogStatusAssert($status['next_action'] !== '', 'Campamento tiene siguiente acción');
    }

    $normal = $db->query(
        "SELECT f.id, f.codigo, f.nombre, f.categoria, f.siempre_revision, COALESCE(f.activa, 1) AS activa
         FROM general_pdc_familias f
         JOIN general_pdc_activity_rules r ON r.familia_id = f.id AND COALESCE(r.activa, 1) = 1
         JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND COALESCE(o.activa, 1) = 1
         JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
         WHERE COALESCE(f.activa, 1) = 1
           AND COALESCE(f.siempre_revision, 0) = 0
         GROUP BY f.id, f.codigo, f.nombre, f.categoria, f.siempre_revision, f.activa
         ORDER BY f.nombre ASC
         LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    catalogStatusAssert(is_array($normal), 'existe familia operativa normal con paquetes');
    if (is_array($normal)) {
        $status = $resolver->statusForFamily($normal);
        catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::CREATES_ACTIVITIES, 'familia normal crea actividades');
        catalogStatusAssert($status['has_contract_options'] === true, 'familia normal reporta paquetes configurados');
    }

    $alias = $db->query(
        'SELECT a.*, f.nombre AS familia_nombre
         FROM general_pdc_family_aliases a
         JOIN general_pdc_familias f ON f.id = a.familia_id
         WHERE COALESCE(a.activa, 1) = 1
         LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC);
    catalogStatusAssert(is_array($alias), 'existe alias activo');
    if (is_array($alias)) {
        $status = $resolver->statusForAlias($alias);
        catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::ALIAS_OF, 'alias muestra familia canónica');
        catalogStatusAssert($status['canonical_family'] !== '', 'alias incluye familia canónica');
    }

    $contractual = $db->query(
        'SELECT e.*, f.nombre AS familia_nombre
         FROM general_pdc_contractual_elements e
         LEFT JOIN general_pdc_familias f ON f.id = e.familia_id
         WHERE COALESCE(e.activa, 1) = 1
         LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC);
    catalogStatusAssert(is_array($contractual), 'existe elemento contractual activo');
    if (is_array($contractual)) {
        $status = $resolver->statusForContractualElement($contractual);
        catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::MANAGED_IN_CONTRACTS, 'elemento contractual se gestiona en Contratos');
        catalogStatusAssert($status['package_hint'] !== '', 'elemento contractual muestra paquete');
    }

    $code = 'QA_NO_USAR_' . random_int(100000, 999999);
    $name = 'QA No Usar ' . random_int(100000, 999999);
    $db->query(
        'INSERT INTO general_pdc_familias (codigo, nombre, categoria, orden, siempre_revision, activa)
         VALUES (?, ?, ?, 9999, 0, 0)',
        [$code, $name, 'QA'],
    );
    $familyId = (int) $db->query('SELECT id FROM general_pdc_familias WHERE codigo = ?', [$code])->fetchColumn();
    $inactive = $db->query(
        'SELECT id, codigo, nombre, categoria, siempre_revision, COALESCE(activa, 1) AS activa
         FROM general_pdc_familias
         WHERE id = ?',
        [$familyId],
    )->fetch(PDO::FETCH_ASSOC);
    $status = $resolver->statusForFamily($inactive);
    catalogStatusAssert($status['status_key'] === FamilyCatalogStatusResolver::DO_NOT_USE, 'familia inactiva sin alias ni contrato se marca No usar');
    catalogStatusAssert($status['reason'] !== '' && $status['next_action'] !== '', 'No usar incluye motivo y siguiente acción');
    $db->query('DELETE FROM general_pdc_familias WHERE id = ?', [$familyId]);
} catch (Throwable $e) {
    catalogStatusFail('resolver falló: ' . $e->getMessage());
}

echo "=== Family catalog status resolver: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
