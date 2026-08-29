<?php

declare(strict_types=1);

/** @return array{grants_file: ?string, live: bool} */
function runtimeGrantAuditParseArguments(array $arguments): array
{
    $grantsFile = null;
    $live = false;

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
        if ($argument === '--live') {
            $live = true;
            continue;
        }

        throw new InvalidArgumentException('Opción no reconocida.');
    }

    if ($grantsFile === '') {
        throw new InvalidArgumentException('--grants-file requiere una ruta.');
    }
    if ($live && $grantsFile !== null) {
        throw new InvalidArgumentException('--live no se puede combinar con --grants-file.');
    }

    return ['grants_file' => $grantsFile, 'live' => $live];
}

/** @return list<string> */
function runtimeGrantAuditLines(string $input): array
{
    $lines = [];
    foreach (preg_split('/\R/', $input) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (str_starts_with($line, '|')) {
            $line = trim($line, "| \t");
        }
        $lines[] = $line;
    }

    return $lines;
}

/**
 * @return array{privileges: list<string>, target: string, username: string, host: string}|null
 */
function runtimeGrantAuditParseLine(string $line): ?array
{
    $principal = "(?:'([^']+)'|`([^`]+)`|([A-Za-z0-9_]+))"
        . "@(?:'([^']+)'|`([^`]+)`|([A-Za-z0-9_.:%-]+))";
    if (preg_match(
        '/\AGRANT\s+(.+?)\s+ON\s+(.+?)\s+TO\s+' . $principal . '\z/i',
        $line,
        $parts,
    ) !== 1) {
        return null;
    }

    $privileges = array_map(
        static fn(string $privilege): string => strtoupper(trim($privilege)),
        explode(',', $parts[1]),
    );
    if (in_array('', $privileges, true)) {
        return null;
    }

    return [
        'privileges' => $privileges,
        'target' => strtolower(preg_replace('/[\s`]+/', '', trim($parts[2])) ?? ''),
        'username' => strtolower((string) ($parts[3] ?: ($parts[4] ?: $parts[5]))),
        'host' => strtolower((string) ($parts[6] ?: ($parts[7] ?: $parts[8]))),
    ];
}

/** @return array{ok: bool, grants_checked: int, reason: string} */
function runtimeGrantAudit(string $input, string $database): array
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
        return ['ok' => false, 'grants_checked' => 0, 'reason' => 'invalid-database'];
    }

    $lines = runtimeGrantAuditLines($input);
    if ($lines === []) {
        return ['ok' => false, 'grants_checked' => 0, 'reason' => 'no-grants'];
    }

    $grantsChecked = count($lines);
    if ($grantsChecked > 2) {
        return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'unexpected-grant-count'];
    }

    $requiredPrivileges = ['DELETE', 'INSERT', 'SELECT', 'UPDATE'];
    $expectedTarget = strtolower($database . '.*');
    $expectedPrincipal = null;
    $hasDml = false;
    $hasUsage = false;

    foreach ($lines as $line) {
        $grant = runtimeGrantAuditParseLine($line);
        if ($grant === null) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'invalid-line'];
        }
        if ($grant['username'] === 'root') {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'root-account'];
        }
        if (preg_match('/^[a-z0-9_]+$/', $grant['username']) !== 1) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'invalid-grantee'];
        }

        $principal = $grant['username'] . '@' . $grant['host'];
        if ($expectedPrincipal !== null && !hash_equals($expectedPrincipal, $principal)) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'mixed-grantees'];
        }
        $expectedPrincipal = $principal;

        if ($grant['privileges'] === ['USAGE'] && $grant['target'] === '*.*') {
            if ($hasUsage) {
                return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'duplicate-usage'];
            }
            $hasUsage = true;
            continue;
        }
        if ($grant['target'] !== $expectedTarget) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'invalid-target'];
        }
        if ($hasDml) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'duplicate-dml'];
        }

        $privileges = $grant['privileges'];
        sort($privileges);
        if ($privileges !== $requiredPrivileges) {
            return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'non-exact-dml'];
        }
        $hasDml = true;
    }

    if (!$hasDml) {
        return ['ok' => false, 'grants_checked' => $grantsChecked, 'reason' => 'missing-dml'];
    }

    return ['ok' => true, 'grants_checked' => $grantsChecked, 'reason' => 'ok'];
}

/** @return array{ok: bool, grants_checked: int, reason: string} */
function runtimeGrantAuditLive(PDO $pdo): array
{
    try {
        $statement = $pdo->query('SHOW GRANTS FOR CURRENT_USER');
        if (!$statement instanceof PDOStatement) {
            return ['ok' => false, 'grants_checked' => 0, 'reason' => 'unavailable'];
        }

        $lines = [];
        while (($row = $statement->fetch(PDO::FETCH_NUM)) !== false) {
            if (is_array($row) && isset($row[0]) && is_string($row[0])) {
                $lines[] = $row[0];
            }
        }

        return runtimeGrantAudit(implode("\n", $lines), (string) (getenv('DB_NAME') ?: ''));
    } catch (Throwable) {
        return ['ok' => false, 'grants_checked' => 0, 'reason' => 'unavailable'];
    }
}

function runtimeGrantAuditMain(array $arguments): int
{
    try {
        $options = runtimeGrantAuditParseArguments($arguments);
    } catch (InvalidArgumentException) {
        fwrite(STDERR, "Uso: php scripts/security/audit-runtime-db-grants.php [--grants-file=RUTA|--live]\n");
        return 2;
    }

    $database = getenv('DB_NAME');
    if ($database === false || $database === '') {
        echo "runtime_db_grants=fail reason=missing-database grants_checked=0\n";
        return 1;
    }

    if ($options['live']) {
        try {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '3306';
            $user = getenv('DB_USER');
            $pass = getenv('DB_PASS');
            if ($user === false || $user === '') {
                throw new RuntimeException('runtime database user is unavailable');
            }

            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
                $user,
                $pass === false ? '' : $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2,
                ],
            );
            $result = runtimeGrantAuditLive($pdo);
        } catch (Throwable) {
            $result = ['ok' => false, 'grants_checked' => 0, 'reason' => 'unavailable'];
        }

        echo sprintf(
            "runtime_db_grants=%s reason=%s grants_checked=%d\n",
            $result['ok'] ? 'ok' : 'fail',
            $result['reason'],
            $result['grants_checked'],
        );

        return $result['ok'] ? 0 : 1;
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
