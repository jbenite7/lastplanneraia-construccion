<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/construccion/conexion.php';
require_once __DIR__ . '/admin/src/Core/RoleManager.php';

use Admin\Core\RoleManager;

function normalizeTextKey(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = mb_strtolower($value, 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii !== false) {
        $value = $ascii;
    }

    $value = preg_replace('/[^a-z0-9 ]/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim((string)$value);
}

function normalizeRoleCode(string $role): string
{
    $normalized = strtoupper(trim(RoleManager::normalizeRole($role)));
    if ($normalized === 'P') {
        return 'D';
    }
    if ($normalized === 'U' || $normalized === '') {
        return 'V';
    }

    return $normalized;
}

function hasColumn(Database $db, string $table, string $column): bool
{
    $tableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $columnSafe = str_replace(["\\", "'"], ["\\\\", "\\'"], $column);

    $stmt = $db->query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'");

    return (bool)$stmt->fetch();
}

$options = getopt('', ['drop-column', 'dry-run']);
$dropColumn = isset($options['drop-column']);
$dryRun = isset($options['dry-run']);

echo "Iniciando migracion de memberships de usuarios...\n";
echo $dryRun ? "Modo: DRY RUN (sin cambios persistentes)\n" : "Modo: APPLY\n";

$db = Database::getInstance();

$summary = [
    'orphan_user_links_removed' => 0,
    'orphan_project_links_removed' => 0,
    'roles_normalized_p_to_d' => 0,
    'roles_normalized_u_to_v' => 0,
    'users_backfilled' => 0,
    'memberships_inserted' => 0,
    'users_without_memberships' => 0,
    'legacy_project_column_exists' => 0,
    'legacy_project_column_dropped' => 0,
];

try {
    $hasProjectColumn = hasColumn($db, 'general_usuarios', 'proyecto');
    $summary['legacy_project_column_exists'] = $hasProjectColumn ? 1 : 0;

    $db->beginTransaction();

    $summary['orphan_user_links_removed'] = $db->query(
        "DELETE pm
         FROM project_members pm
         LEFT JOIN general_usuarios u ON u.id = pm.user_id
         WHERE u.id IS NULL"
    )->rowCount();

    $summary['orphan_project_links_removed'] = $db->query(
        "DELETE pm
         FROM project_members pm
         LEFT JOIN general_proyectos_procesos p ON p.Id = pm.project_id
         WHERE p.Id IS NULL"
    )->rowCount();

    $summary['roles_normalized_p_to_d'] = $db->query("UPDATE project_members SET role = 'D' WHERE role = 'P'")->rowCount();
    $summary['roles_normalized_u_to_v'] = $db->query("UPDATE project_members SET role = 'V' WHERE role = 'U'")->rowCount();

    $projects = $db->query(
        "SELECT Id, Proyecto_Proceso, Activo
         FROM general_proyectos_procesos
         WHERE Area = 'Construccion'"
    )->fetchAll();

    $allProjectIds = [];
    $activeProjectIds = [];
    $projectIdByName = [];

    foreach ($projects as $project) {
        $projectId = (int)($project['Id'] ?? 0);
        if ($projectId <= 0) {
            continue;
        }

        $allProjectIds[] = $projectId;
        if ((int)($project['Activo'] ?? 0) === 1) {
            $activeProjectIds[] = $projectId;
        }

        $key = normalizeTextKey((string)($project['Proyecto_Proceso'] ?? ''));
        if ($key !== '') {
            $projectIdByName[$key] = $projectId;
        }
    }

    $fallbackProjectId = 0;
    if (isset($projectIdByName['prueba'])) {
        $fallbackProjectId = (int)$projectIdByName['prueba'];
    } elseif (!empty($activeProjectIds)) {
        $fallbackProjectId = (int)$activeProjectIds[0];
    } elseif (!empty($allProjectIds)) {
        $fallbackProjectId = (int)$allProjectIds[0];
    }

    $sqlUsers = "SELECT u.id, u.usuario, u.cargo";
    if ($hasProjectColumn) {
        $sqlUsers .= ", u.proyecto";
    }
    $sqlUsers .= ", COUNT(pm.id) AS memberships
                  FROM general_usuarios u
                  LEFT JOIN project_members pm ON pm.user_id = u.id
                  GROUP BY u.id
                  ORDER BY u.id ASC";

    $users = $db->query($sqlUsers)->fetchAll();

    foreach ($users as $user) {
        $currentMemberships = (int)($user['memberships'] ?? 0);
        if ($currentMemberships > 0) {
            continue;
        }

        $targetProjectIds = [];

        if ($hasProjectColumn) {
            $legacyProject = trim((string)($user['proyecto'] ?? ''));
            $legacyKey = normalizeTextKey($legacyProject);

            if ($legacyKey !== '') {
                if ($legacyKey === 'todos') {
                    $targetProjectIds = !empty($activeProjectIds) ? $activeProjectIds : $allProjectIds;
                } elseif (isset($projectIdByName[$legacyKey])) {
                    $targetProjectIds[] = (int)$projectIdByName[$legacyKey];
                }
            }
        }

        if (empty($targetProjectIds) && $fallbackProjectId > 0) {
            $targetProjectIds[] = $fallbackProjectId;
        }

        if (empty($targetProjectIds)) {
            continue;
        }

        $roleSuggestion = RoleManager::suggestRoleByCargo((string)($user['cargo'] ?? ''));
        $roleCode = normalizeRoleCode((string)$roleSuggestion);
        $userId = (int)($user['id'] ?? 0);

        $insertedForUser = 0;
        foreach (array_unique($targetProjectIds) as $projectId) {
            $projectId = (int)$projectId;
            if ($projectId <= 0) {
                continue;
            }

            $inserted = $db->query(
                "INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)",
                [$projectId, $userId, $roleCode]
            )->rowCount();

            $insertedForUser += (int)$inserted;
            $summary['memberships_inserted'] += (int)$inserted;
        }

        if ($insertedForUser > 0) {
            $summary['users_backfilled']++;
        }
    }

    $summary['users_without_memberships'] = (int)($db->query(
        "SELECT COUNT(*) AS total
         FROM general_usuarios u
         LEFT JOIN project_members pm ON pm.user_id = u.id
         WHERE pm.id IS NULL"
    )->fetch()['total'] ?? 0);

    if ($dryRun) {
        $db->rollBack();
        echo "\nCambios revertidos (dry-run).\n";
    } else {
        $db->commit();
    }

    if ($dropColumn && !$dryRun) {
        if (!$hasProjectColumn) {
            echo "\nLa columna general_usuarios.proyecto ya no existe.\n";
        } else {
            if ($summary['users_without_memberships'] > 0) {
                throw new RuntimeException('No se puede eliminar la columna: aun existen usuarios sin memberships.');
            }

            $db->query("ALTER TABLE general_usuarios DROP COLUMN proyecto");
            $summary['legacy_project_column_dropped'] = 1;
            echo "\nColumna general_usuarios.proyecto eliminada correctamente.\n";
        }
    }

    echo "\nResumen:\n";
    foreach ($summary as $key => $value) {
        echo "- {$key}: {$value}\n";
    }

    echo "\nMigracion finalizada.\n";
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "\nERROR: " . $e->getMessage() . "\n");
    exit(1);
}
