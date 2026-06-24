#!/usr/bin/env php
<?php
/**
 * Fix double-encoded UTF-8 mojibake in general_pdc_family_contract_option_items.
 *
 * Fixes both `paquete_nombre` and `tipo_paquete` columns simultaneously.
 * Handles deduplication: removes rows that would become duplicates after fixing.
 *
 * Usage: docker compose exec app php database/patches/fix_mojibake.php
 */

require_once __DIR__ . '/../../src/Core/Database.php';

// Bootstrap .env
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line) === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            } elseif (($value[0] ?? '') === "'" && substr($value, -1) === "'") {
                $value = substr($value, 1, -1);
            }
            $_ENV[trim($key)] = $value;
        }
    }
}

$db = Database::getInstance();

echo "=== Mojibake Repair Script (v3) ===\n";
echo "Table: general_pdc_family_contract_option_items\n";
echo "Columns: paquete_nombre, tipo_paquete\n\n";

// 1. Count rows
$stmt = $db->query("SELECT COUNT(*) as cnt FROM general_pdc_family_contract_option_items");
$totalBefore = $stmt->fetch()['cnt'];
echo "Total rows: {$totalBefore}\n";

$stmt = $db->query("SELECT COUNT(*) as cnt FROM general_pdc_family_contract_option_items WHERE HEX(paquete_nombre) LIKE '%C383%' OR HEX(tipo_paquete) LIKE '%C383%'");
$corruptedCount = $stmt->fetch()['cnt'];
echo "Corrupted rows: {$corruptedCount}\n\n";

if ($corruptedCount === 0) {
    echo "Nothing to fix. Exiting.\n";
    exit(0);
}

/**
 * Build REPLACE chain for a column using UNHEX byte-level replacements.
 */
function buildReplaceExpr(string $column): string {
    $replacements = [
        // Pattern B: Win-1252 special chars → UTF-8 (10 hex chars → 6 hex chars)
        'C383E2809C' => 'C393',  // Ó
        'C383E28098' => 'C391',  // Ñ
        'C383E28099' => 'C399',  // Ù
        'C383E2809A' => 'C39A',  // Ú
        // Pattern A: Standard double-encoding (8 hex chars → 4 hex chars)
        'C383C280' => 'C380',  // À
        'C383C281' => 'C381',  // Á
        'C383C282' => 'C382',  // Â
        'C383C284' => 'C384',  // Ä
        'C383C285' => 'C385',  // Å
        'C383C286' => 'C386',  // Æ
        'C383C287' => 'C387',  // Ç
        'C383C288' => 'C388',  // È
        'C383C289' => 'C389',  // É
        'C383C28A' => 'C38A',  // Ê
        'C383C28B' => 'C38B',  // Ë
        'C383C28C' => 'C38C',  // Ì
        'C383C28D' => 'C38D',  // Í
        'C383C28E' => 'C38E',  // Î
        'C383C28F' => 'C38F',  // Ï
        'C383C290' => 'C390',  // Ð
        'C383C291' => 'C391',  // Ñ (Pattern A variant)
        'C383C292' => 'C392',  // Ò
        'C383C293' => 'C393',  // Ó (Pattern A variant)
        'C383C294' => 'C394',  // Ô
        'C383C295' => 'C395',  // Õ
        'C383C296' => 'C396',  // Ö
        'C383C298' => 'C398',  // Ø
        'C383C299' => 'C399',  // Ù (Pattern A variant)
        'C383C29A' => 'C39A',  // Ú (Pattern A variant)
        'C383C29B' => 'C39B',  // Û
        'C383C29C' => 'C39C',  // Ü
        'C383C29D' => 'C39D',  // Ý
        'C383C29E' => 'C39E',  // Þ
        'C383C2A0' => 'C3A0',  // à
        'C383C2A1' => 'C3A1',  // á
        'C383C2A2' => 'C3A2',  // â
        'C383C2A3' => 'C3A3',  // ã
        'C383C2A4' => 'C3A4',  // ä
        'C383C2A5' => 'C3A5',  // å
        'C383C2A6' => 'C3A6',  // æ
        'C383C2A7' => 'C3A7',  // ç
        'C383C2A8' => 'C3A8',  // è
        'C383C2A9' => 'C3A9',  // é
        'C383C2AA' => 'C3AA',  // ê
        'C383C2AB' => 'C3AB',  // ë
        'C383C2AC' => 'C3AC',  // ì
        'C383C2AD' => 'C3AD',  // í
        'C383C2AE' => 'C3AE',  // î
        'C383C2AF' => 'C3AF',  // ï
        'C383C2B1' => 'C3B1',  // ñ
        'C383C2B3' => 'C3B3',  // ó
        'C383C2B4' => 'C3B4',  // ô
        'C383C2B5' => 'C3B5',  // õ
        'C383C2B6' => 'C3B6',  // ö
        'C383C2B8' => 'C3B8',  // ø
        'C383C2B9' => 'C3B9',  // ù
        'C383C2BA' => 'C3BA',  // ú
        'C383C2BB' => 'C3BB',  // û
        'C383C2BC' => 'C3BC',  // ü
        'C383C2BD' => 'C3BD',  // ý
        'C383C2BF' => 'C3BF',  // ÿ
    ];

    // Sort by length descending (longer patterns first to avoid partial matches)
    $sortedKeys = array_keys($replacements);
    usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));

    $expr = $column;
    foreach ($sortedKeys as $corruptedHex) {
        $correctHex = $replacements[$corruptedHex];
        $expr = "REPLACE($expr, UNHEX('{$corruptedHex}'), UNHEX('{$correctHex}'))";
    }
    return $expr;
}

// 2. Build the fix expressions
$pnExpr = buildReplaceExpr('paquete_nombre');
$tpExpr = buildReplaceExpr('tipo_paquete');

// 3. First: identify and remove rows that would become duplicates after fixing
echo "Step 1: Checking for rows that would become duplicates...\n";

// Strategy: Find rows where after fixing, the (option_id, tipo_paquete, paquete_nombre) triple
// would match another row. We keep the row with the LOWEST id and delete the rest.
$dedupSql = "
DELETE FROM general_pdc_family_contract_option_items 
WHERE id IN (
    SELECT id FROM (
        SELECT a.id
        FROM general_pdc_family_contract_option_items a
        JOIN general_pdc_family_contract_option_items b
          ON a.option_id = b.option_id
          AND {$pnExpr} = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
              b.paquete_nombre,
              UNHEX('C383E2809C'), UNHEX('C393')),
              UNHEX('C383E28098'), UNHEX('C391')),
              UNHEX('C383C28D'), UNHEX('C38D')),
              UNHEX('C383C281'), UNHEX('C381')),
              UNHEX('C383C289'), UNHEX('C389')),
              UNHEX('C383C29A'), UNHEX('C39A')),
              UNHEX('C383C2A1'), UNHEX('C3A1')),
              UNHEX('C383C2A9'), UNHEX('C3A9')),
              UNHEX('C383C2AD'), UNHEX('C3AD')),
              UNHEX('C383C2B3'), UNHEX('C3B3'))
          AND {$tpExpr} = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
              b.tipo_paquete,
              UNHEX('C383E2809C'), UNHEX('C393')),
              UNHEX('C383E28098'), UNHEX('C391')),
              UNHEX('C383C28D'), UNHEX('C38D')),
              UNHEX('C383C281'), UNHEX('C381')),
              UNHEX('C383C289'), UNHEX('C389')),
              UNHEX('C383C29A'), UNHEX('C39A')),
              UNHEX('C383C2A1'), UNHEX('C3A1')),
              UNHEX('C383C2A9'), UNHEX('C3A9')),
              UNHEX('C383C2AD'), UNHEX('C3AD')),
              UNHEX('C383C2B3'), UNHEX('C3B3'))
          AND a.id > b.id
          AND (HEX(a.tipo_paquete) LIKE '%C383%' OR HEX(a.paquete_nombre) LIKE '%C383%')
        ) AS dup_ids
    )
)";

echo "  (Dedup query too complex for MySQL subquery — using PHP approach instead)\n";

// PHP approach: fix rows one at a time, catch duplicate errors
echo "\nStep 2: Fixing rows individually (handles dedup)...\n";

$stmt = $db->query(
    "SELECT id, paquete_nombre, tipo_paquete 
     FROM general_pdc_family_contract_option_items 
     WHERE HEX(paquete_nombre) LIKE '%C383%' OR HEX(tipo_paquete) LIKE '%C383%'"
);
$rows = $stmt->fetchAll();

$fixedCount = 0;
$skippedCount = 0;
$failedCount = 0;

foreach ($rows as $row) {
    // Build the fixed values using the same REPLACE chain
    // We execute a SELECT to compute the fixed values for this row
    $selectStmt = $db->query(
        "SELECT {$pnExpr} as fixed_pn, {$tpExpr} as fixed_tp 
         FROM general_pdc_family_contract_option_items WHERE id = {$row['id']}"
    );
    $fixed = $selectStmt->fetch();

    if ($fixed['fixed_pn'] === $row['paquete_nombre'] && $fixed['fixed_tp'] === $row['tipo_paquete']) {
        // No change needed (shouldn't happen since we filtered for corrupted)
        $skippedCount++;
        continue;
    }

    try {
        $updateStmt = $db->prepare(
            "UPDATE general_pdc_family_contract_option_items 
             SET paquete_nombre = ?, tipo_paquete = ? 
             WHERE id = ?"
        );
        $updateStmt->execute([$fixed['fixed_pn'], $fixed['fixed_tp'], $row['id']]);
        $fixedCount++;
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            // This row would create a duplicate — delete it (it's a redundant duplicate)
            echo "  Skipped duplicate id={$row['id']}: {$row['paquete_nombre']}\n";
            $deleteStmt = $db->prepare("DELETE FROM general_pdc_family_contract_option_items WHERE id = ?");
            $deleteStmt->execute([$row['id']]);
            $skippedCount++;
        } else {
            echo "  FAILED id={$row['id']}: {$e->getMessage()}\n";
            $failedCount++;
        }
    }
}

echo "\nFixed: {$fixedCount}\n";
echo "Skipped (duplicates removed): {$skippedCount}\n";
echo "Failed: {$failedCount}\n";

// 4. Verify
$stmt = $db->query("SELECT COUNT(*) as cnt FROM general_pdc_family_contract_option_items");
$totalAfter = $stmt->fetch()['cnt'];

$stmt = $db->query("SELECT COUNT(*) as cnt FROM general_pdc_family_contract_option_items WHERE HEX(paquete_nombre) LIKE '%C383%'");
$remainingPnCorrupted = $stmt->fetch()['cnt'];

$stmt = $db->query("SELECT COUNT(*) as cnt FROM general_pdc_family_contract_option_items WHERE HEX(tipo_paquete) LIKE '%C383%'");
$remainingTpCorrupted = $stmt->fetch()['cnt'];

echo "\n=== Verification ===\n";
echo "Total rows: {$totalAfter} (before: {$totalBefore})\n";
echo "paquete_nombre corrupted: {$remainingPnCorrupted} (expected 0)\n";
echo "tipo_paquete corrupted: {$remainingTpCorrupted} (expected 0)\n";

// 5. Spot-check
echo "\nSpot-checks:\n";
$samples = [
    ['label' => 'TOPOGRAFÍA', 'sql' => "SELECT paquete_nombre, HEX(paquete_nombre) as hx FROM general_pdc_family_contract_option_items WHERE paquete_nombre LIKE '%TOPOGRA%' LIMIT 2"],
    ['label' => 'INSTALACIÓN', 'sql' => "SELECT paquete_nombre, HEX(paquete_nombre) as hx FROM general_pdc_family_contract_option_items WHERE paquete_nombre LIKE '%INSTALACI%' LIMIT 2"],
    ['label' => 'ELÉCTRICA', 'sql' => "SELECT paquete_nombre, HEX(paquete_nombre) as hx FROM general_pdc_family_contract_option_items WHERE paquete_nombre LIKE '%L%E9C%' LIMIT 2"],
];
foreach ($samples as $sample) {
    $stmt = $db->query($sample['sql']);
    $rows = $stmt->fetchAll();
    echo "  {$sample['label']}:\n";
    foreach ($rows as $r) {
        echo "    \"{$r['paquete_nombre']}\" HEX={$r['hx']}\n";
    }
}

// 6. Check unique constraint integrity
$stmt = $db->query("SHOW KEYS FROM general_pdc_family_contract_option_items WHERE Key_name = 'uq_pdc_option_item'");
$hasUnique = $stmt->fetch();
if ($hasUnique) {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM (
        SELECT option_id, tipo_paquete, paquete_nombre 
        FROM general_pdc_family_contract_option_items 
        GROUP BY option_id, tipo_paquete, paquete_nombre 
        HAVING COUNT(*) > 1
    ) as dups");
    $dupCount = $stmt->fetch()['cnt'];
    echo "\nUnique constraint violations: {$dupCount} (expected 0)\n";
}

if ($remainingPnCorrupted === 0 && $remainingTpCorrupted === 0) {
    echo "\n✓ Migration successful!\n";
} else {
    echo "\n⚠ Remaining corruption detected.\n";
    exit(1);
}
