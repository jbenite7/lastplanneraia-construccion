#!/usr/bin/env php
<?php
/**
 * Fix mojibake (double-encoded UTF-8) across all PDC and semi-auto tables.
 * Extends the existing fix_mojibake.php to cover additional tables/columns.
 *
 * Usage: docker compose exec app php database/patches/fix_mojibake_full.php
 */

require_once __DIR__ . '/../../src/Core/Database.php';

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

/**
 * Build REPLACE chain for a column using UNHEX byte-level replacements.
 */
function buildReplaceExpr(string $column): string {
    $replacements = [
        // Pattern B: Win-1252 special chars
        'C383E2809C' => 'C393',  // Ó
        'C383E28098' => 'C391',  // Ñ
        'C383E28099' => 'C399',  // Ù
        'C383E2809A' => 'C39A',  // Ú
        // Pattern A: Standard double-encoding
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
        'C383C291' => 'C391',  // Ñ
        'C383C292' => 'C392',  // Ò
        'C383C293' => 'C393',  // Ó
        'C383C294' => 'C394',  // Ô
        'C383C295' => 'C395',  // Õ
        'C383C296' => 'C396',  // Ö
        'C383C298' => 'C398',  // Ø
        'C383C299' => 'C399',  // Ù
        'C383C29A' => 'C39A',  // Ú
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

    $sortedKeys = array_keys($replacements);
    usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));

    $expr = $column;
    foreach ($sortedKeys as $corruptedHex) {
        $correctHex = $replacements[$corruptedHex];
        $expr = "REPLACE($expr, UNHEX('{$corruptedHex}'), UNHEX('{$correctHex}'))";
    }
    return $expr;
}

// Tables and columns to fix
$fixTargets = [
    'general_pdc_familias'            => ['nombre'],
    'general_pdc_activity_rules'      => ['patron_regex', 'modalidad_sugerida', 'descripcion'],
    'general_pdc_family_contract_options' => ['tipo_paquete', 'notas'],
    'semi_auto_suggestions'           => ['reason'],
    'general_pdc_family_contract_option_items' => ['paquete_nombre', 'tipo_paquete'],
];

$totalFixed = 0;
$totalErrs = 0;

foreach ($fixTargets as $table => $columns) {
    echo "\n=== {$table} ===\n";

    // Check which columns have corrupted data
    $corruptedCols = [];
    foreach ($columns as $col) {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM `{$table}` WHERE HEX(`{$col}`) LIKE '%C383%'");
        $cnt = $stmt->fetch()['cnt'];
        if ($cnt > 0) {
            $corruptedCols[$col] = $cnt;
        }
    }

    if (empty($corruptedCols)) {
        echo "  clean\n";
        continue;
    }

    foreach ($corruptedCols as $col => $cnt) {
        echo "  {$col}: {$cnt} rows corrupted\n";
    }

    // Build SET expressions
    $setParts = [];
    foreach ($columns as $col) {
        if (isset($corruptedCols[$col])) {
            $expr = buildReplaceExpr("`{$col}`");
            $setParts[] = "`{$col}` = {$expr}";
        }
    }

    if (empty($setParts)) {
        echo "  nothing to fix\n";
        continue;
    }

    $setSql = implode(', ', $setParts);

    // Check for potential duplicate issues before UPDATE
    // First, just update rows - if they fail, handle individually
    $updateSql = "UPDATE `{$table}` SET {$setSql} WHERE ";
    $whereCols = [];
    foreach ($columns as $col) {
        if (isset($corruptedCols[$col])) {
            $whereCols[] = "HEX(`{$col}`) LIKE '%C383%'";
        }
    }
    $updateSql .= implode(' OR ', $whereCols);

    try {
        $stmt = $db->query($updateSql);
        $affected = $stmt->rowCount();
        echo "  fixed: {$affected} rows via mass UPDATE\n";
        $totalFixed += $affected;
    } catch (Exception $e) {
        echo "  mass UPDATE failed ({$e->getMessage()}), trying row-by-row...\n";

        // Row-by-row approach for tables with potential duplicate constraints
        $whereClause = implode(' OR ', $whereCols);
        $rows = $db->query("SELECT id FROM `{$table}` WHERE {$whereClause}")->fetchAll();

        foreach ($rows as $row) {
            try {
                // Build row-level UPDATE with REPLACE chain
                $fixedParts = [];
                foreach ($columns as $col) {
                    if (isset($corruptedCols[$col])) {
                        $expr = buildReplaceExpr("`{$col}`");
                        $fixedParts[] = "`{$col}` = {$expr}";
                    }
                }
                $fixedSet = implode(', ', $fixedParts);
                $db->query("UPDATE `{$table}` SET {$fixedSet} WHERE id = {$row['id']}");
                $totalFixed++;
            } catch (Exception $e2) {
                if (str_contains($e2->getMessage(), 'Duplicate')) {
                    echo "    duplicate id={$row['id']}, deleting...\n";
                    $db->query("DELETE FROM `{$table}` WHERE id = {$row['id']}");
                } else {
                    echo "    ERROR id={$row['id']}: {$e2->getMessage()}\n";
                    $totalErrs++;
                }
            }
        }
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: {$totalFixed}\n";
echo "Errors: {$totalErrs}\n";

// Verification
echo "\n=== Verification ===\n";
$allTables = array_keys($fixTargets);
foreach ($allTables as $table) {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM `{$table}` WHERE 1=0");
    // Check all columns
    $cols = $db->query("SHOW COLUMNS FROM `{$table}` WHERE Type LIKE '%varchar%' OR Type LIKE '%text%' OR Type LIKE '%char%'")->fetchAll();
    if (empty($cols)) continue;

    $whereParts = [];
    foreach ($cols as $col) {
        $whereParts[] = "HEX(`{$col['Field']}`) LIKE '%C383%'";
    }
    $where = implode(' OR ', $whereParts);
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM `{$table}` WHERE {$where}");
    $remaining = $stmt->fetch()['cnt'];
    if ($remaining > 0) {
        echo "  WARNING: {$table} still has {$remaining} corrupted rows\n";
    } else {
        echo "  {$table}: OK\n";
    }
}
