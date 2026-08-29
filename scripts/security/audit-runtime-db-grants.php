<?php

declare(strict_types=1);

/** @return array{grants_file: ?string} */
function runtimeGrantAuditParseArguments(array $arguments): array
{
    $grantsFile = null;

    for ($index = 0, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];
        if (str_starts_with($argument, '--grants-file=')) {
            $grantsFile = substr($argument, strlen('--grants-file='));
            continue;
        }
        if ($argument === '--grants-file' && isset($arguments[$index + 1])) {
            $grantsFile = $arguments[++$index];
            continue;
        }

        throw new InvalidArgumentException('Opción no reconocida.');
    }

    if ($grantsFile === '') {
        throw new InvalidArgumentException('--grants-file requiere una ruta.');
    }

    return ['grants_file' => $grantsFile];
}

/** @return list<string> */
function runtimeGrantAuditLines(string $input): array
{
    $grants = [];
    foreach (preg_split('/\R/', $input) ?: [] as $line) {
        $line = trim($line);
        if (str_starts_with($line, '|')) {
            $line = trim($line, "| \t");
        }
        if (preg_match('/^GRANT\s+/i', $line) === 1) {
            $grants[] = $line;
        }
    }

    return $grants;
}

/** @return array{ok: bool, grants_checked: int, reason: string} */
function runtimeGrantAudit(string $input, string $database): array
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
        return ['ok' => false, 'grants_checked' => 0, 'reason' => 'invalid-database'];
    }

    $grants = runtimeGrantAuditLines($input);
    if ($grants === []) {
        return ['ok' => false, 'grants_checked' => 0, 'reason' => 'no-grants'];
    }

    $allowedPrivileges = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
    $expectedTargets = [strtolower($database . '.*'), strtolower('`' . $database . '`.*')];

    foreach ($grants as $grant) {
        if (preg_match('/\bWITH\s+GRANT\s+OPTION\b/i', $grant) === 1) {
            return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'grant-option'];
        }

        if (preg_match(
            "/\bTO\s+(?:'([^']+)'|`([^`]+)`|([A-Za-z0-9_]+))@/i",
            $grant,
            $grantee,
        ) !== 1) {
            return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'unparsed-grantee'];
        }
        $username = strtolower((string) ($grantee[1] ?: ($grantee[2] ?: $grantee[3])));
        if ($username === 'root') {
            return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'root-account'];
        }

        if (preg_match('/^GRANT\s+(.+?)\s+ON\s+(.+?)\s+TO\s+/i', $grant, $parts) !== 1) {
            return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'unparsed-grant'];
        }

        $privileges = array_map(
            static fn(string $privilege): string => strtoupper(trim($privilege)),
            explode(',', $parts[1]),
        );

        $target = strtolower(preg_replace('/\s+/', '', trim($parts[2])) ?? '');
        if ($privileges === ['USAGE'] && $target === '*.*') {
            continue;
        }
        if (!in_array($target, $expectedTargets, true)) {
            return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'invalid-target'];
        }

        foreach ($privileges as $privilege) {
            if (!in_array($privilege, $allowedPrivileges, true)) {
                return ['ok' => false, 'grants_checked' => count($grants), 'reason' => 'forbidden-privilege'];
            }
        }
    }

    return ['ok' => true, 'grants_checked' => count($grants), 'reason' => 'ok'];
}

function runtimeGrantAuditMain(array $arguments): int
{
    try {
        $options = runtimeGrantAuditParseArguments($arguments);
    } catch (InvalidArgumentException) {
        fwrite(STDERR, "Uso: php scripts/security/audit-runtime-db-grants.php [--grants-file=RUTA]\n");
        return 2;
    }

    $database = getenv('DB_NAME');
    if ($database === false || $database === '') {
        echo "runtime_db_grants=fail reason=missing-database grants_checked=0\n";
        return 1;
    }

    if ($options['grants_file'] !== null) {
        $input = @file_get_contents($options['grants_file']);
        if ($input === false) {
            fwrite(STDERR, "runtime_db_grants=fail reason=unreadable-input grants_checked=0\n");
            return 2;
        }
    } else {
        $input = stream_get_contents(STDIN);
        if ($input === false) {
            fwrite(STDERR, "runtime_db_grants=fail reason=unreadable-input grants_checked=0\n");
            return 2;
        }
    }

    $result = runtimeGrantAudit($input, $database);
    echo sprintf(
        "runtime_db_grants=%s reason=%s grants_checked=%d\n",
        $result['ok'] ? 'ok' : 'fail',
        $result['reason'],
        $result['grants_checked'],
    );

    return $result['ok'] ? 0 : 1;
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    exit(runtimeGrantAuditMain(array_slice($argv ?? [], 1)));
}
