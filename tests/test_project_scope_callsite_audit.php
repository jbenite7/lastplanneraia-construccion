<?php
// @requiere: db

declare(strict_types=1);

use App\Security\DataScope\ProjectSqlGuard;

require_once __DIR__ . '/../vendor/autoload.php';

$root = realpath((string) (getenv('LPS_CODE_ROOT') ?: dirname(__DIR__)));
if ($root === false) {
    fwrite(STDERR, "No se pudo resolver LPS_CODE_ROOT.\n");
    exit(1);
}

$db = Database::getInstance();
$guard = new ProjectSqlGuard($db);
$catalog = $db->tableScopeCatalog();
$findings = [];

/** @return list<string> */
function auditPhpFiles(string $root): array
{
    $files = [];
    foreach (['src', 'admin/src'] as $directory) {
        $path = $root . '/' . $directory;
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);

    return $files;
}

/** @param list<PhpToken> $tokens */
function auditNextMeaningful(array $tokens, int $start): ?int
{
    $ignored = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        if (!in_array($tokens[$index]->id, $ignored, true)) {
            return $index;
        }
    }

    return null;
}

/** @param list<PhpToken> $tokens */
function auditPreviousMeaningful(array $tokens, int $start): ?int
{
    $ignored = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
    for ($index = $start; $index >= 0; $index--) {
        if (!in_array($tokens[$index]->id, $ignored, true)) {
            return $index;
        }
    }

    return null;
}

/**
 * @param list<PhpToken> $tokens
 * @return list<list<PhpToken>>
 */
function auditCallArguments(array $tokens, int $open): array
{
    $arguments = [[]];
    $depth = 0;
    for ($index = $open + 1, $count = count($tokens); $index < $count; $index++) {
        $text = $tokens[$index]->text;
        if (in_array($text, ['(', '[', '{'], true)) {
            $depth++;
        } elseif (in_array($text, [')', ']', '}'], true)) {
            if ($text === ')' && $depth === 0) {
                return $arguments;
            }
            $depth--;
        }

        if ($text === ',' && $depth === 0) {
            $arguments[] = [];
            continue;
        }
        $arguments[array_key_last($arguments)][] = $tokens[$index];
    }

    return [];
}

/** @param list<PhpToken> $tokens */
function auditUnquote(array $tokens): ?string
{
    $meaningful = array_values(array_filter(
        $tokens,
        static fn(PhpToken $token): bool => !in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
    ));
    if (count($meaningful) !== 1 || $meaningful[0]->id !== T_CONSTANT_ENCAPSED_STRING) {
        return null;
    }

    $literal = $meaningful[0]->text;
    $quote = $literal[0] ?? '';
    $body = substr($literal, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
    }
    if ($quote === '"') {
        return stripcslashes($body);
    }

    return null;
}

/**
 * @param list<PhpToken> $tokens
 * @param array<string, string> $knownStrings
 */
function auditResolveString(array $tokens, array $knownStrings): ?string
{
    $meaningful = array_values(array_filter(
        $tokens,
        static fn(PhpToken $token): bool => !in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
    ));
    if (count($meaningful) === 1 && $meaningful[0]->id === T_VARIABLE) {
        return $knownStrings[$meaningful[0]->text] ?? null;
    }

    $parts = [];
    $current = [];
    foreach ($meaningful as $token) {
        if ($token->text === '.') {
            $value = auditUnquote($current);
            if ($value === null) {
                return null;
            }
            $parts[] = $value;
            $current = [];
            continue;
        }
        $current[] = $token;
    }
    $value = auditUnquote($current);
    if ($value === null) {
        return null;
    }
    $parts[] = $value;

    return implode('', $parts);
}

/** @param list<PhpToken> $tokens */
function auditContainsDirectProjectRequest(array $tokens): bool
{
    foreach ($tokens as $index => $token) {
        if ($token->id !== T_VARIABLE || !in_array($token->text, ['$_GET', '$_POST'], true)) {
            continue;
        }
        $open = auditNextMeaningful($tokens, $index + 1);
        $key = $open === null ? null : auditNextMeaningful($tokens, $open + 1);
        $close = $key === null ? null : auditNextMeaningful($tokens, $key + 1);
        if ($open === null || $key === null || $close === null) {
            continue;
        }
        if ($tokens[$open]->text !== '[' || $tokens[$close]->text !== ']') {
            continue;
        }
        if (auditUnquote([$tokens[$key]]) === 'project_id') {
            return true;
        }
    }

    return false;
}

function auditMaintenanceCallerAllowed(string $relative): bool
{
    return in_array($relative, [
        'src/Controllers/Gestion/ReportController.php',
        'src/Security/DataScope/SystemScopeRunner.php',
        'admin/src/Controllers/DashboardController.php',
        'admin/async/consolidate.php',
    ], true)
        || str_starts_with($relative, 'scripts/')
        || str_starts_with($relative, 'database/migrations/')
        || str_starts_with($relative, 'tests/');
}

foreach (auditPhpFiles($root) as $path) {
    $relative = substr($path, strlen($root) + 1);
    $source = file_get_contents($path);
    if ($source === false) {
        $findings[] = "{$relative}:1:no-se-pudo-leer";
        continue;
    }

    $tokens = PhpToken::tokenize($source);
    $knownStrings = [];

    foreach ($tokens as $index => $token) {
        if ($token->id === T_FUNCTION) {
            $knownStrings = [];
        }
        if ($token->id === T_VARIABLE) {
            $equals = auditNextMeaningful($tokens, $index + 1);
            if ($equals !== null && $tokens[$equals]->text === '=') {
                $expression = [];
                $depth = 0;
                for ($cursor = $equals + 1, $count = count($tokens); $cursor < $count; $cursor++) {
                    $text = $tokens[$cursor]->text;
                    if (in_array($text, ['(', '[', '{'], true)) {
                        $depth++;
                    } elseif (in_array($text, [')', ']', '}'], true)) {
                        $depth--;
                    }
                    if ($text === ';' && $depth === 0) {
                        break;
                    }
                    $expression[] = $tokens[$cursor];
                }
                $value = auditResolveString($expression, $knownStrings);
                if ($value === null) {
                    unset($knownStrings[$token->text]);
                } else {
                    $knownStrings[$token->text] = $value;
                }
            }
        }

        if ($token->id !== T_STRING) {
            continue;
        }

        $previous = auditPreviousMeaningful($tokens, $index - 1);
        $next = auditNextMeaningful($tokens, $index + 1);
        $isMethodCall = $previous !== null
            && in_array($tokens[$previous]->id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)
            && $next !== null
            && $tokens[$next]->text === '(';

        if ($token->text === 'queryWithProject' && $isMethodCall) {
            $arguments = auditCallArguments($tokens, $next);
            $sql = isset($arguments[0]) ? auditResolveString($arguments[0], $knownStrings) : null;
            if ($sql !== null) {
                try {
                    if ($guard->isIdentityOnly($sql, $catalog)) {
                        $findings[] = "{$relative}:{$token->line}:identity-usa-queryWithProject";
                    }
                } catch (Throwable) {
                    // SQL dinámico o fuera del catálogo: este gate solo decide cuando el tokenizer
                    // autoritativo puede demostrar que todas las tablas son Identity.
                }
            }
            if (isset($arguments[2]) && auditContainsDirectProjectRequest($arguments[2])) {
                $findings[] = "{$relative}:{$token->line}:request-project-id-crea-autoridad";
            }
            continue;
        }

        if ($token->text === 'setProjectContext' && $isMethodCall) {
            if (!in_array($relative, [
                'src/Core/SessionMiddleware.php',
                'src/Services/ProjectAccessService.php',
            ], true)) {
                $findings[] = "{$relative}:{$token->line}:setProjectContext-no-autorizado";
            }
            continue;
        }

        if ($token->text === 'forMaintenance' && $isMethodCall) {
            if (!auditMaintenanceCallerAllowed($relative)) {
                $findings[] = "{$relative}:{$token->line}:system-scope-no-autorizado";
            }
            continue;
        }

        if ($token->text === 'SystemScopeRunner' && !auditMaintenanceCallerAllowed($relative)) {
            $findings[] = "{$relative}:{$token->line}:system-runner-no-autorizado";
        }
    }

    $offset = 0;
    while (($offset = strpos($source, 'executing without injection', $offset)) !== false) {
        $line = substr_count(substr($source, 0, $offset), "\n") + 1;
        $findings[] = "{$relative}:{$line}:fallback-sin-inyeccion";
        $offset += strlen('executing without injection');
    }
}

sort($findings);
if ($findings !== []) {
    echo "=== Project Scope Callsite Audit: FAIL ===\n";
    foreach ($findings as $finding) {
        echo $finding . "\n";
    }
    exit(1);
}

echo "=== Project Scope Callsite Audit: OK (0 hallazgos) ===\n";
