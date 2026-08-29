<?php
// @requiere: db

declare(strict_types=1);

use App\Security\DataScope\ProjectSqlGuard;

require_once __DIR__ . '/../vendor/autoload.php';

$root = realpath((string) (getenv('LPS_AUDIT_ROOT') ?: getenv('LPS_CODE_ROOT') ?: dirname(__DIR__)));
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
    foreach (glob($root . '/*.php') ?: [] as $path) {
        $canonical = realpath($path);
        if ($canonical !== false) {
            $files[$canonical] = true;
        }
    }

    $excludedDirectories = ['vendor', 'node_modules', 'tests', 'coverage', 'dist', 'build', '.cache'];
    foreach (['src', 'public', 'views', 'admin', 'scripts', 'database'] as $directory) {
        $path = $root . '/' . $directory;
        if (!is_dir($path)) {
            continue;
        }
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file) use ($excludedDirectories): bool {
                return !$file->isDir() || !in_array($file->getFilename(), $excludedDirectories, true);
            },
        );
        $iterator = new RecursiveIteratorIterator($filter);
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $canonical = $file->getRealPath();
                if ($canonical !== false) {
                    $files[$canonical] = true;
                }
            }
        }
    }
    $files = array_keys($files);
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
 * @param array<string, string> $knownConstants
 * @param array<string, true> $trustedTableHelpers
 */
function auditResolveString(
    array $tokens,
    array $knownStrings,
    array $knownConstants = [],
    array $trustedTableHelpers = [],
): ?string
{
    $meaningful = array_values(array_filter(
        $tokens,
        static fn(PhpToken $token): bool => !in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
    ));
    while (count($meaningful) >= 2 && $meaningful[0]->text === '(' && $meaningful[array_key_last($meaningful)]->text === ')') {
        array_shift($meaningful);
        array_pop($meaningful);
    }
    if (count($meaningful) === 1 && $meaningful[0]->id === T_VARIABLE) {
        return $knownStrings[$meaningful[0]->text] ?? null;
    }
    if (count($meaningful) === 1 && $meaningful[0]->id === T_STRING) {
        return $knownConstants[$meaningful[0]->text] ?? null;
    }
    if (count($meaningful) === 3
        && in_array($meaningful[0]->text, ['self', 'static'], true)
        && $meaningful[1]->id === T_DOUBLE_COLON
        && $meaningful[2]->id === T_STRING) {
        return $knownConstants[$meaningful[2]->text] ?? null;
    }

    $depth = 0;
    $parts = [[]];
    foreach ($meaningful as $token) {
        if (in_array($token->text, ['(', '[', '{'], true)) {
            $depth++;
        } elseif (in_array($token->text, [')', ']', '}'], true)) {
            $depth--;
        }
        if ($token->text === '.' && $depth === 0) {
            $parts[] = [];
            continue;
        }
        $parts[array_key_last($parts)][] = $token;
    }
    if (count($parts) > 1) {
        $resolved = '';
        foreach ($parts as $part) {
            $value = auditResolveString($part, $knownStrings, $knownConstants, $trustedTableHelpers);
            if ($value === null) {
                return null;
            }
            $resolved .= $value;
        }

        return $resolved;
    }

    $literal = auditUnquote($meaningful);
    if ($literal !== null) {
        return $literal;
    }

    $callName = null;
    $open = null;
    foreach ($meaningful as $index => $token) {
        if ($token->text === '(') {
            $nameIndex = auditPreviousMeaningful($meaningful, $index - 1);
            if ($nameIndex !== null && $meaningful[$nameIndex]->id === T_STRING) {
                $callName = $meaningful[$nameIndex]->text;
                $open = $index;
            }
            break;
        }
    }
    $callNameIndex = $open === null ? null : auditPreviousMeaningful($meaningful, $open - 1);
    $operatorIndex = $callNameIndex === null ? null : auditPreviousMeaningful($meaningful, $callNameIndex - 1);
    $receiverIndex = $operatorIndex === null ? null : auditPreviousMeaningful($meaningful, $operatorIndex - 1);
    $isTableResolver = $callName === 'resolveByPrefix'
        && $operatorIndex !== null
        && $receiverIndex !== null
        && $meaningful[$operatorIndex]->id === T_DOUBLE_COLON
        && ltrim($meaningful[$receiverIndex]->text, '\\') === 'TableResolver';
    $isTrustedHelper = $callName !== null
        && isset($trustedTableHelpers[$callName])
        && $operatorIndex !== null
        && $receiverIndex !== null
        && $meaningful[$operatorIndex]->id === T_OBJECT_OPERATOR
        && $meaningful[$receiverIndex]->id === T_VARIABLE
        && $meaningful[$receiverIndex]->text === '$this';
    if ($open !== null && ($isTableResolver || $isTrustedHelper)) {
        $arguments = auditCallArguments($meaningful, $open);
        if (isset($arguments[1])) {
            return auditResolveString($arguments[1], $knownStrings, $knownConstants, $trustedTableHelpers);
        }
    }

    $isEncapsed = false;
    $resolved = '';
    foreach ($meaningful as $token) {
        if (in_array($token->id, [T_START_HEREDOC, T_END_HEREDOC], true) || $token->text === '"') {
            $isEncapsed = true;
            continue;
        }
        if ($token->id === T_ENCAPSED_AND_WHITESPACE) {
            $isEncapsed = true;
            $resolved .= $token->text;
            continue;
        }
        if ($token->id === T_VARIABLE) {
            $isEncapsed = true;
            $value = $knownStrings[$token->text] ?? null;
            if ($value === null) {
                return null;
            }
            $resolved .= $value;
            continue;
        }
        if (in_array($token->id, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)
            || in_array($token->text, ['{', '}'], true)) {
            continue;
        }
        if ($isEncapsed) {
            return null;
        }
    }
    if ($isEncapsed) {
        return $resolved;
    }

    return null;
}

/**
 * Solo confía en helpers locales cuya implementación tokenizada es el adaptador
 * exacto de TableResolver; un helper con el mismo nombre no obtiene confianza.
 *
 * @param list<PhpToken> $tokens
 * @return array<string, true>
 */
function auditTrustedTableHelpers(array $tokens): array
{
    $implementations = [];
    foreach ($tokens as $index => $token) {
        if ($token->id !== T_FUNCTION) {
            continue;
        }
        $name = auditNextMeaningful($tokens, $index + 1);
        if ($name === null || $tokens[$name]->id !== T_STRING || !in_array($tokens[$name]->text, ['t', 'tbl'], true)) {
            continue;
        }
        $openBody = null;
        for ($cursor = $name + 1, $count = count($tokens); $cursor < $count; $cursor++) {
            if ($tokens[$cursor]->text === '{') {
                $openBody = $cursor;
                break;
            }
            if ($tokens[$cursor]->text === ';') {
                break;
            }
        }
        if ($openBody === null) {
            continue;
        }

        $depth = 0;
        $body = '';
        for ($cursor = $openBody + 1, $count = count($tokens); $cursor < $count; $cursor++) {
            if ($tokens[$cursor]->text === '{') {
                $depth++;
            } elseif ($tokens[$cursor]->text === '}') {
                if ($depth === 0) {
                    break;
                }
                $depth--;
            }
            if (!in_array($tokens[$cursor]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $body .= $tokens[$cursor]->text;
            }
        }
        $implementations[$tokens[$name]->text][] = $body;
    }

    $trusted = [];
    foreach ($implementations as $name => $bodies) {
        if (count($bodies) === 1 && $bodies[0] === 'returnTableResolver::resolveByPrefix($dbPrefix,$tableType);') {
            $trusted[$name] = true;
        }
    }

    return $trusted;
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

function auditSystemScopeCallerAllowed(string $relative): bool
{
    return $relative === 'src/Security/DataScope/SystemScopeRunner.php';
}

function auditSystemRunnerCallerAllowed(string $relative): bool
{
    return in_array($relative, [
        'src/Controllers/Gestion/ReportController.php',
        'admin/src/Controllers/DashboardController.php',
        'admin/async/consolidate.php',
        'scripts/higiene/reparar-mojibake-causas.php',
    ], true);
}

function auditTokenNamesClass(PhpToken $token, string $class): bool
{
    if (!in_array($token->id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
        return false;
    }

    $parts = explode('\\', ltrim($token->text, '\\'));

    return end($parts) === $class;
}

/** @param list<PhpToken> $tokens */
function auditContainsRunnerConstruction(array $tokens): bool
{
    foreach ($tokens as $index => $token) {
        if (!auditTokenNamesClass($token, 'SystemScopeRunner')) {
            continue;
        }
        $previous = auditPreviousMeaningful($tokens, $index - 1);
        if ($previous !== null && $tokens[$previous]->id === T_NEW) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<PhpToken> $tokens
 * @param array<string, true> $runnerVariables
 */
function auditIsSystemRunnerInvocation(array $tokens, int $runIndex, array $runnerVariables): bool
{
    $operator = auditPreviousMeaningful($tokens, $runIndex - 1);
    if ($operator === null || !in_array($tokens[$operator]->id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
        return false;
    }
    $receiver = auditPreviousMeaningful($tokens, $operator - 1);
    if ($receiver === null) {
        return false;
    }
    if ($tokens[$operator]->id === T_DOUBLE_COLON) {
        return str_ends_with(ltrim($tokens[$receiver]->text, '\\'), 'SystemScopeRunner');
    }
    if ($tokens[$receiver]->id === T_VARIABLE) {
        return isset($runnerVariables[$tokens[$receiver]->text]);
    }
    if ($tokens[$receiver]->text !== ')') {
        return false;
    }

    $depth = 0;
    for ($index = $receiver; $index >= 0; $index--) {
        if ($tokens[$index]->text === ')') {
            $depth++;
            continue;
        }
        if ($tokens[$index]->text === '(') {
            $depth--;
            $class = auditPreviousMeaningful($tokens, $index - 1);
            if ($class !== null && auditTokenNamesClass($tokens[$class], 'SystemScopeRunner')) {
                $new = auditPreviousMeaningful($tokens, $class - 1);
                if ($new !== null && $tokens[$new]->id === T_NEW) {
                    return true;
                }
            }
            if ($depth === 0) {
                return false;
            }
        }
    }

    return false;
}

/** @param list<PhpToken> $tokens */
function auditIsSystemScopeFactoryInvocation(array $tokens, int $methodIndex): bool
{
    $operator = auditPreviousMeaningful($tokens, $methodIndex - 1);
    $receiver = $operator === null ? null : auditPreviousMeaningful($tokens, $operator - 1);

    return $operator !== null
        && $receiver !== null
        && $tokens[$operator]->id === T_DOUBLE_COLON
        && str_ends_with(ltrim($tokens[$receiver]->text, '\\'), 'SystemScope');
}

function auditSetProjectContextCallerAllowed(string $relative): bool
{
    return in_array($relative, [
        'src/Core/SessionMiddleware.php',
        'src/Services/ProjectAccessService.php',
    ], true);
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
    $knownConstants = [];
    $runnerVariables = [];
    $trustedTableHelpers = auditTrustedTableHelpers($tokens);

    if (!auditSystemRunnerCallerAllowed($relative)
        && $relative !== 'src/Security/DataScope/SystemScopeRunner.php') {
        foreach ($tokens as $token) {
            if (auditTokenNamesClass($token, 'SystemScopeRunner')) {
                $findings[] = "{$relative}:{$token->line}:system-runner-no-autorizado";
            }
        }
    }

    foreach ($tokens as $index => $token) {
        if (in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            $previous = auditPreviousMeaningful($tokens, $index - 1);
            if ($previous === null || $tokens[$previous]->id !== T_DOUBLE_COLON) {
                $knownConstants = [];
            }
        }
        if ($token->id === T_FUNCTION) {
            $knownStrings = [];
            $runnerVariables = [];
        }
        if ($token->id === T_CONST) {
            $name = auditNextMeaningful($tokens, $index + 1);
            $equals = $name === null ? null : auditNextMeaningful($tokens, $name + 1);
            if ($name !== null && $equals !== null && $tokens[$name]->id === T_STRING && $tokens[$equals]->text === '=') {
                $expression = [];
                for ($cursor = $equals + 1, $count = count($tokens); $cursor < $count && $tokens[$cursor]->text !== ';'; $cursor++) {
                    $expression[] = $tokens[$cursor];
                }
                $value = auditResolveString($expression, $knownStrings, $knownConstants, $trustedTableHelpers);
                if ($value !== null) {
                    $knownConstants[$tokens[$name]->text] = $value;
                }
            }
        }
        if ($token->id === T_VARIABLE) {
            $equals = auditNextMeaningful($tokens, $index + 1);
            if ($equals !== null && in_array($tokens[$equals]->text, ['=', '.='], true)) {
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
                $value = auditResolveString($expression, $knownStrings, $knownConstants, $trustedTableHelpers);
                if ($tokens[$equals]->text === '.=' && isset($knownStrings[$token->text]) && $value !== null) {
                    $value = $knownStrings[$token->text] . $value;
                }
                if ($value === null) {
                    unset($knownStrings[$token->text]);
                } else {
                    $knownStrings[$token->text] = $value;
                }
                if (auditContainsRunnerConstruction($expression)) {
                    $runnerVariables[$token->text] = true;
                } else {
                    unset($runnerVariables[$token->text]);
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
            $sql = isset($arguments[0])
                ? auditResolveString($arguments[0], $knownStrings, $knownConstants, $trustedTableHelpers)
                : null;
            if ($sql === null) {
                $findings[] = "{$relative}:{$token->line}:queryWithProject-sql-no-resuelto";
            } else {
                try {
                    if ($guard->isIdentityOnly($sql, $catalog)) {
                        $findings[] = "{$relative}:{$token->line}:identity-usa-queryWithProject";
                    }
                } catch (Throwable) {
                    $findings[] = "{$relative}:{$token->line}:queryWithProject-sql-no-clasificable";
                }
            }
            if (isset($arguments[2]) && auditContainsDirectProjectRequest($arguments[2])) {
                $findings[] = "{$relative}:{$token->line}:request-project-id-crea-autoridad";
            }
            continue;
        }

        if ($token->text === 'setProjectContext' && $isMethodCall) {
            if (!auditSetProjectContextCallerAllowed($relative)) {
                $findings[] = "{$relative}:{$token->line}:setProjectContext-no-autorizado";
            }
            continue;
        }

        if ($token->text === 'forMaintenance' && $isMethodCall && auditIsSystemScopeFactoryInvocation($tokens, $index)) {
            if (!auditSystemScopeCallerAllowed($relative)) {
                $findings[] = "{$relative}:{$token->line}:system-scope-no-autorizado";
            }
            continue;
        }

        if ($token->text === 'run' && $isMethodCall && auditIsSystemRunnerInvocation($tokens, $index, $runnerVariables)) {
            if (!auditSystemRunnerCallerAllowed($relative)) {
                $findings[] = "{$relative}:{$token->line}:system-runner-no-autorizado";
            }
        }
    }

    $offset = 0;
    while (($offset = strpos($source, 'executing without injection', $offset)) !== false) {
        $line = substr_count(substr($source, 0, $offset), "\n") + 1;
        $findings[] = "{$relative}:{$line}:fallback-sin-inyeccion";
        $offset += strlen('executing without injection');
    }
}

$findings = array_values(array_unique($findings));
sort($findings);
$expectedRaw = getenv('LPS_AUDIT_EXPECT');
if ($expectedRaw !== false) {
    $expected = $expectedRaw === '' ? [] : explode(',', $expectedRaw);
    sort($expected);
    if ($findings !== $expected) {
        echo "=== Project Scope Callsite Audit Fixtures: FAIL ===\n";
        foreach (array_diff($expected, $findings) as $missing) {
            echo "MISSING: {$missing}\n";
        }
        foreach (array_diff($findings, $expected) as $unexpected) {
            echo "UNEXPECTED: {$unexpected}\n";
        }
        exit(1);
    }

    echo "=== Project Scope Callsite Audit Fixtures: OK (" . count($findings) . " hallazgos esperados) ===\n";
    exit(0);
}

if ($findings !== []) {
    echo "=== Project Scope Callsite Audit: FAIL ===\n";
    foreach ($findings as $finding) {
        echo $finding . "\n";
    }
    exit(1);
}

echo "=== Project Scope Callsite Audit: OK (0 hallazgos) ===\n";
