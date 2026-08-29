<?php
// @requiere: db

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\DataScope\TableScopeKind;

$mode = $argv[1] ?? '--audit';
if (!in_array($mode, ['--audit', '--enforce'], true)) {
    fwrite(STDERR, "Uso: php tests/test_project_scope_schema_contract.php [--audit|--enforce]\n");
    exit(2);
}

$catalog = Database::getInstance()->tableScopeCatalog();
$findings = [];

foreach ($catalog->schemaRows() as $table => $row) {
    $kind = $catalog->kind($table);

    if ($kind === TableScopeKind::Project) {
        $problems = [];
        if ((int) ($row['project_id_nullable'] ?? 0) === 1) {
            $problems[] = 'project_id permite NULL';
        }
        if ((int) ($row['has_leading_index'] ?? 0) === 0) {
            $problems[] = 'project_id no es índice líder';
        }
        if ($problems !== []) {
            $findings[] = "{$table}: " . implode('; ', $problems);
        }
    }

    if ($kind === TableScopeKind::Unclassified) {
        $findings[] = "{$table}: denied (sin definición explícita de alcance)";
    }
}

$label = $mode === '--audit' ? 'AUDIT' : 'ENFORCE';
echo "=== Project Scope Schema Contract: {$label} ===\n";
foreach ($findings as $finding) {
    echo " - {$finding}\n";
}

if ($findings === []) {
    echo "Sin hallazgos.\n";
} elseif ($mode === '--audit') {
    echo "Hallazgos auditados: " . count($findings) . " (audit no bloquea).\n";
} else {
    echo "Hallazgos bloqueantes: " . count($findings) . ".\n";
    exit(1);
}
