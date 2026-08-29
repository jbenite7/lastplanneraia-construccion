<?php

declare(strict_types=1);

$phpParserAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!class_exists(PhpParser\ParserFactory::class) && is_file($phpParserAutoload)) {
    require_once $phpParserAutoload;
}

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_ as ArrayExpr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_ as ListExpr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Análisis intrafichero y conservador de los tests PHP.
 *
 * El SQL solo se clasifica cuando alcanza una frontera que puede ejecutarlo. El AST conserva la
 * diferencia entre un literal esperado y una llamada, y el resumen de wrappers se propaga hasta
 * un punto fijo para cubrir helpers locales anidados sin depender de sus nombres.
 *
 * @phpstan-type SqlValue array{
 *   template: string,
 *   complete: bool,
 *   deps: list<int>,
 *   external: bool
 * }
 * @phpstan-type FunctionSummary array{params: list<int>, always: bool}
 */
final class PhpTestDdlInventoryAnalyzer
{
    public function __construct(private readonly ?string $sourcePath = null)
    {
    }

    /** @var array<string, Function_> */
    private array $functions = [];

    /** @var array<string, FunctionSummary> */
    private array $summaries = [];

    /** @var list<array{call: string, line: int}> */
    private array $findings = [];

    /** @return list<array{call: string, line: int}> */
    public function analyze(string $source): array
    {
        if (!class_exists(ParserFactory::class)) {
            return [['call' => 'analysis-unavailable', 'line' => 1]];
        }

        try {
            $statements = (new ParserFactory())->createForHostVersion()->parse($source);
        } catch (Throwable $error) {
            $line = method_exists($error, 'getStartLine') ? (int) $error->getStartLine() : 1;
            return [['call' => 'parse-error', 'line' => max(1, $line)]];
        }
        if ($statements === null) {
            return [];
        }

        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($statements, Function_::class) as $function) {
            $this->functions[strtolower($function->name->toString())] = $function;
        }
        $this->buildFunctionSummaries();

        [$assignments, $calls, $foreaches] = $this->scopeNodes($statements);
        foreach ($calls as $call) {
            $environment = $this->environmentBefore(
                $assignments,
                [],
                $call->getStartFilePos(),
                $foreaches,
            );
            $this->inspectTopLevelCall($call, $environment);
        }

        usort(
            $this->findings,
            static fn(array $left, array $right): int => [$left['line'], $left['call']] <=> [$right['line'], $right['call']],
        );

        return $this->findings;
    }

    private function buildFunctionSummaries(): void
    {
        foreach ($this->functions as $name => $_function) {
            $this->summaries[$name] = ['params' => [], 'always' => false];
        }

        $changed = true;
        $iterations = 0;
        while ($changed && $iterations <= count($this->functions) + 1) {
            $changed = false;
            $iterations++;
            foreach ($this->functions as $name => $function) {
                $calculated = $this->summarizeFunction($function);
                $mergedParams = array_values(array_unique(array_merge(
                    $this->summaries[$name]['params'],
                    $calculated['params'],
                )));
                sort($mergedParams);
                $merged = [
                    'params' => $mergedParams,
                    'always' => $this->summaries[$name]['always'] || $calculated['always'],
                ];
                if ($merged !== $this->summaries[$name]) {
                    $this->summaries[$name] = $merged;
                    $changed = true;
                }
            }
        }
    }

    /** @return FunctionSummary */
    private function summarizeFunction(Function_ $function): array
    {
        $initialEnvironment = [];
        foreach ($function->params as $index => $parameter) {
            if ($parameter->var instanceof Variable && is_string($parameter->var->name)) {
                $initialEnvironment[$parameter->var->name] = $this->unknownValue([$index], false);
            }
        }

        [$assignments, $calls, $foreaches] = $this->scopeNodes($function->stmts);
        $summary = ['params' => [], 'always' => false];
        foreach ($calls as $call) {
            $environment = $this->environmentBefore(
                $assignments,
                $initialEnvironment,
                $call->getStartFilePos(),
                $foreaches,
            );
            $localName = $call instanceof FuncCall ? $this->callName($call) : null;
            if ($localName !== null && isset($this->functions[$localName])) {
                $callee = $this->summaries[$localName];
                if ($callee['always']) {
                    $summary['always'] = true;
                }
                foreach ($callee['params'] as $parameterIndex) {
                    $this->absorbSummaryValue(
                        $summary,
                        $this->argumentValue($call, $parameterIndex, $environment),
                    );
                }
                continue;
            }

            $sqlIndex = $this->sqlArgumentIndex($call);
            if ($sqlIndex !== null) {
                $this->absorbSummaryValue(
                    $summary,
                    $this->argumentValue($call, $sqlIndex, $environment),
                );
            }
        }

        sort($summary['params']);
        $summary['params'] = array_values(array_unique($summary['params']));

        return $summary;
    }

    /**
     * @param FunctionSummary $summary
     * @param SqlValue $value
     */
    private function absorbSummaryValue(array &$summary, array $value): void
    {
        $kind = $this->sqlKind($value);
        if ($kind === 'safe') {
            return;
        }
        if ($kind === 'ddl' || $value['external'] || $value['deps'] === []) {
            $summary['always'] = true;
            return;
        }
        $summary['params'] = array_merge($summary['params'], $value['deps']);
    }

    /** @param array<string, SqlValue> $environment */
    private function inspectTopLevelCall(Expr $call, array $environment): void
    {
        $callName = $this->callName($call);
        $localName = $call instanceof FuncCall ? $callName : null;
        if ($localName !== null && isset($this->functions[$localName])) {
            $summary = $this->summaries[$localName];
            if ($summary['always']) {
                $this->addFinding($callName, $call->getStartLine());
                return;
            }
            foreach ($summary['params'] as $parameterIndex) {
                if ($this->sqlKind($this->argumentValue($call, $parameterIndex, $environment)) !== 'safe') {
                    $this->addFinding($callName, $call->getStartLine());
                    return;
                }
            }
            return;
        }

        $sqlIndex = $this->sqlArgumentIndex($call);
        if ($sqlIndex === null) {
            return;
        }
        if ($this->sqlKind($this->argumentValue($call, $sqlIndex, $environment)) !== 'safe') {
            $this->addFinding($callName ?? 'dynamic-sink', $call->getStartLine());
        }
    }

    private function addFinding(string $call, int $line): void
    {
        foreach ($this->findings as $finding) {
            if ($finding['call'] === $call && $finding['line'] === $line) {
                return;
            }
        }
        $this->findings[] = ['call' => $call, 'line' => max(1, $line)];
    }

    /**
     * @param list<Assign> $assignments
     * @param array<string, SqlValue> $initial
     * @param list<Foreach_> $foreaches
     * @return array<string, SqlValue>
     */
    private function environmentBefore(
        array $assignments,
        array $initial,
        int $beforePosition,
        array $foreaches = [],
    ): array
    {
        $environment = $initial;
        usort(
            $assignments,
            static fn(Assign $left, Assign $right): int => $left->getStartFilePos() <=> $right->getStartFilePos(),
        );
        foreach ($assignments as $assignment) {
            $position = $assignment->getStartFilePos();
            if ($beforePosition >= 0 && $position >= $beforePosition) {
                continue;
            }
            if (!$assignment->var instanceof Variable || !is_string($assignment->var->name)) {
                continue;
            }
            $environment[$assignment->var->name] = $this->expressionValue($assignment->expr, $environment);
        }

        foreach ($foreaches as $foreach) {
            if ($beforePosition < $foreach->getStartFilePos() || $beforePosition > $foreach->getEndFilePos()) {
                continue;
            }
            $iterable = $this->resolveArrayExpression(
                $foreach->expr,
                $assignments,
                $foreach->getStartFilePos(),
            );
            if ($iterable === null) {
                if ($foreach->valueVar instanceof Variable && is_string($foreach->valueVar->name)) {
                    $values = $this->resolveIterableSqlValues($foreach->expr, $environment);
                    if ($values !== null) {
                        $environment[$foreach->valueVar->name] = $this->aggregateValues($values);
                    }
                }
                continue;
            }
            $this->bindForeachValues($environment, $foreach->valueVar, $iterable);
        }

        return $environment;
    }

    /** @param list<Assign> $assignments */
    private function resolveArrayExpression(Expr $expression, array $assignments, int $beforePosition): ?ArrayExpr
    {
        if ($expression instanceof ArrayExpr) {
            return $expression;
        }
        if (!$expression instanceof Variable || !is_string($expression->name)) {
            return null;
        }

        $candidate = null;
        foreach ($assignments as $assignment) {
            if ($assignment->getStartFilePos() >= $beforePosition) {
                continue;
            }
            if (!$assignment->var instanceof Variable || $assignment->var->name !== $expression->name) {
                continue;
            }
            if ($assignment->expr instanceof ArrayExpr) {
                $candidate = $assignment->expr;
            }
        }

        return $candidate;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return list<SqlValue>|null
     */
    private function resolveIterableSqlValues(Expr $expression, array $environment): ?array
    {
        if (!$expression instanceof FuncCall || $this->callName($expression) !== 'preg_split'
            || !isset($expression->args[0], $expression->args[1])) {
            return null;
        }
        $pattern = $this->expressionValue($expression->args[0]->value, $environment);
        $subject = $this->expressionValue($expression->args[1]->value, $environment);
        if (!$pattern['complete'] || !$subject['complete']) {
            return null;
        }
        $parts = @preg_split($pattern['template'], $subject['template']);
        if ($parts === false) {
            return null;
        }

        return array_map(fn(string $part): array => $this->knownValue($part), $parts);
    }

    /**
     * @param array<string, SqlValue> $environment
     */
    private function bindForeachValues(array &$environment, Expr $target, ArrayExpr $iterable): void
    {
        if ($target instanceof Variable && is_string($target->name)) {
            $expressions = [];
            foreach ($iterable->items as $item) {
                $expressions[] = $item->value;
            }
            $environment[$target->name] = $this->aggregateExpressions($expressions, $environment);
            return;
        }
        if (!$target instanceof ArrayExpr && !$target instanceof ListExpr) {
            return;
        }

        foreach ($target->items as $targetIndex => $targetItem) {
            if ($targetItem === null || !$targetItem->value instanceof Variable
                || !is_string($targetItem->value->name)) {
                continue;
            }
            $expressions = [];
            foreach ($iterable->items as $iterableItem) {
                if (!$iterableItem->value instanceof ArrayExpr) {
                    continue;
                }
                $sourceItem = $iterableItem->value->items[$targetIndex] ?? null;
                if ($sourceItem !== null) {
                    $expressions[] = $sourceItem->value;
                }
            }
            $environment[$targetItem->value->name] = $this->aggregateExpressions($expressions, $environment);
        }
    }

    /**
     * @param list<Expr> $expressions
     * @param array<string, SqlValue> $environment
     * @return SqlValue
     */
    private function aggregateExpressions(array $expressions, array $environment): array
    {
        if ($expressions === []) {
            return $this->unknownValue([], true);
        }
        return $this->aggregateValues(array_map(
            fn(Expr $expression): array => $this->expressionValue($expression, $environment),
            $expressions,
        ));
    }

    /** @param list<SqlValue> $values @return SqlValue */
    private function aggregateValues(array $values): array
    {
        if ($values === []) {
            return $this->unknownValue([], true);
        }
        $aggregate = $values[0];
        foreach (array_slice($values, 1) as $value) {
            $aggregate = $this->alternativeValues($aggregate, $value);
        }

        return $aggregate;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return SqlValue
     */
    private function argumentValue(Expr $call, int $index, array $environment): array
    {
        if (!property_exists($call, 'args') || !isset($call->args[$index])) {
            return $this->unknownValue([], true);
        }

        return $this->expressionValue($call->args[$index]->value, $environment);
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return SqlValue
     */
    private function expressionValue(Expr $expression, array $environment): array
    {
        if ($expression instanceof String_) {
            return $this->knownValue($expression->value);
        }
        if ($expression instanceof InterpolatedString) {
            $value = $this->knownValue('');
            foreach ($expression->parts as $part) {
                $partValue = $part instanceof InterpolatedStringPart
                    ? $this->knownValue($part->value)
                    : $this->expressionValue($part, $environment);
                $value = $this->concatValues($value, $partValue);
            }
            return $value;
        }
        if ($expression instanceof Variable && is_string($expression->name)) {
            return $environment[$expression->name] ?? $this->unknownValue([], true);
        }
        if ($expression instanceof Concat) {
            return $this->concatValues(
                $this->expressionValue($expression->left, $environment),
                $this->expressionValue($expression->right, $environment),
            );
        }
        if ($expression instanceof Expr\Cast\String_) {
            return $this->expressionValue($expression->expr, $environment);
        }
        if ($expression instanceof Node\Scalar\MagicConst\Dir) {
            return $this->sourcePath === null
                ? $this->unknownValue([], true)
                : $this->knownValue(dirname($this->sourcePath));
        }
        if ($expression instanceof Assign) {
            return $this->expressionValue($expression->expr, $environment);
        }
        if ($expression instanceof Ternary) {
            $whenTrue = $expression->if === null
                ? $this->expressionValue($expression->cond, $environment)
                : $this->expressionValue($expression->if, $environment);
            return $this->alternativeValues(
                $whenTrue,
                $this->expressionValue($expression->else, $environment),
            );
        }
        if ($expression instanceof Coalesce) {
            return $this->alternativeValues(
                $this->expressionValue($expression->left, $environment),
                $this->expressionValue($expression->right, $environment),
            );
        }
        if ($expression instanceof FuncCall && $this->callName($expression) === 'sprintf' && isset($expression->args[0])) {
            $format = $this->expressionValue($expression->args[0]->value, $environment);
            $format['complete'] = false;
            $format['external'] = true;
            return $format;
        }
        if ($expression instanceof FuncCall && $this->callName($expression) === 'dirname'
            && isset($expression->args[0])) {
            $path = $this->expressionValue($expression->args[0]->value, $environment);
            if ($path['complete']) {
                return $this->knownValue(dirname($path['template']));
            }
            return $this->unknownValue($path['deps'], $path['external']);
        }
        if ($expression instanceof FuncCall && $this->callName($expression) === 'file_get_contents'
            && isset($expression->args[0])) {
            return $this->sourceControlledSqlFileValue(
                $this->expressionValue($expression->args[0]->value, $environment),
            );
        }

        return $this->unknownValue([], true);
    }

    /** @param SqlValue $path @return SqlValue */
    private function sourceControlledSqlFileValue(array $path): array
    {
        if (!$path['complete']) {
            return $this->unknownValue($path['deps'], true);
        }
        $repositoryRoot = realpath(dirname(__DIR__, 2));
        $resolvedPath = realpath($path['template']);
        if ($repositoryRoot === false || $resolvedPath === false
            || !str_starts_with($resolvedPath, $repositoryRoot . DIRECTORY_SEPARATOR)
            || strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION)) !== 'sql') {
            return $this->unknownValue([], true);
        }
        $contents = @file_get_contents($resolvedPath);
        if ($contents === false) {
            return $this->unknownValue([], true);
        }

        return $this->knownValue($contents);
    }

    /** @return SqlValue */
    private function knownValue(string $text): array
    {
        return ['template' => $text, 'complete' => true, 'deps' => [], 'external' => false];
    }

    /**
     * @param list<int> $dependencies
     * @return SqlValue
     */
    private function unknownValue(array $dependencies, bool $external): array
    {
        sort($dependencies);
        return [
            'template' => '?',
            'complete' => false,
            'deps' => array_values(array_unique($dependencies)),
            'external' => $external,
        ];
    }

    /**
     * @param SqlValue $left
     * @param SqlValue $right
     * @return SqlValue
     */
    private function concatValues(array $left, array $right): array
    {
        $dependencies = array_values(array_unique(array_merge($left['deps'], $right['deps'])));
        sort($dependencies);
        return [
            'template' => $left['template'] . $right['template'],
            'complete' => $left['complete'] && $right['complete'],
            'deps' => $dependencies,
            'external' => $left['external'] || $right['external'],
        ];
    }

    /**
     * @param SqlValue $left
     * @param SqlValue $right
     * @return SqlValue
     */
    private function alternativeValues(array $left, array $right): array
    {
        $leftKind = $this->sqlKind($left);
        $rightKind = $this->sqlKind($right);
        if ($leftKind === $rightKind && $leftKind === 'safe') {
            return $this->knownValue('SELECT');
        }
        if ($leftKind === 'ddl' || $rightKind === 'ddl') {
            return $this->knownValue('CREATE');
        }
        $dependencies = array_values(array_unique(array_merge($left['deps'], $right['deps'])));
        return $this->unknownValue($dependencies, $left['external'] || $right['external']);
    }

    /** @param SqlValue $value @return 'ddl'|'safe'|'unknown' */
    private function sqlKind(array $value): string
    {
        $statements = $this->splitSqlStatements($value['template']);
        $allRecognizedSafe = true;
        foreach ($statements as $statement) {
            if (preg_match(
                '/\/\*!\d*\s*(?:CREATE|DROP|ALTER|TRUNCATE|RENAME|GRANT|REVOKE)\b/i',
                $statement,
            ) === 1) {
                return 'ddl';
            }
            $sql = $this->stripLeadingSqlComments($statement);
            if ($sql === '') {
                continue;
            }
            if (preg_match('/^(?:CREATE|DROP|ALTER|TRUNCATE|RENAME|GRANT|REVOKE)\b/i', $sql) === 1) {
                return 'ddl';
            }
            if (preg_match(
                '/^(?:SELECT|INSERT|UPDATE|DELETE|REPLACE|WITH|SET|SHOW|DESCRIBE|DESC|EXPLAIN|CALL|DO|USE|START|COMMIT|ROLLBACK|SAVEPOINT|RELEASE)\b/i',
                $sql,
            ) !== 1) {
                $allRecognizedSafe = false;
            }
        }
        if ($value['complete']) {
            return 'safe';
        }
        if ($allRecognizedSafe && $statements !== []) {
            return 'safe';
        }

        return 'unknown';
    }

    /** @return list<string> */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $lineComment = false;
        $blockComment = false;
        $length = strlen($sql);
        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';
            if ($lineComment) {
                $buffer .= $character;
                if ($character === "\n") {
                    $lineComment = false;
                }
                continue;
            }
            if ($blockComment) {
                $buffer .= $character;
                if ($character === '*' && $next === '/') {
                    $buffer .= $next;
                    $index++;
                    $blockComment = false;
                }
                continue;
            }
            if ($quote !== null) {
                $buffer .= $character;
                if ($character === '\\' && $next !== '') {
                    $buffer .= $next;
                    $index++;
                    continue;
                }
                if ($character === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $index++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }
            if (($character === '-' && $next === '-') || $character === '#') {
                $buffer .= $character;
                if ($character === '-' && $next === '-') {
                    $buffer .= $next;
                    $index++;
                }
                $lineComment = true;
                continue;
            }
            if ($character === '/' && $next === '*') {
                $buffer .= $character . $next;
                $index++;
                $blockComment = true;
                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                $buffer .= $character;
                continue;
            }
            if ($character === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }
            $buffer .= $character;
        }
        if (trim($buffer) !== '' || $statements === []) {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function stripLeadingSqlComments(string $sql): string
    {
        $previous = null;
        while ($previous !== $sql) {
            $previous = $sql;
            $sql = (string) preg_replace(
                '/\A\s*(?:\/\*.*?\*\/\s*|--[^\r\n]*(?:\r?\n|\z)\s*|#[^\r\n]*(?:\r?\n|\z)\s*)/s',
                '',
                $sql,
                1,
            );
        }

        return ltrim($sql);
    }

    private function sqlArgumentIndex(Expr $call): ?int
    {
        $name = $this->callName($call);
        if ($name === null) {
            return null;
        }

        if ($call instanceof MethodCall || $call instanceof StaticCall) {
            return match ($name) {
                'exec', 'query', 'prepare', 'querywithproject' => 0,
                'queryforprojects' => 1,
                default => preg_match('/^(?:exec|execute|run|query|prepare).*sql$/', $name) === 1
                    ? max(0, count($call->args) - 1)
                    : null,
            };
        }
        if (!$call instanceof FuncCall) {
            return null;
        }

        return match ($name) {
            'mysqli_query' => 1,
            'mysql_query' => 0,
            default => preg_match('/^(?:exec|execute|run|query|prepare).*sql$/', $name) === 1
                ? max(0, count($call->args) - 1)
                : null,
        };
    }

    private function callName(Expr $call): ?string
    {
        $name = null;
        if ($call instanceof FuncCall && $call->name instanceof Name) {
            $parts = $call->name->getParts();
            $name = end($parts);
        } elseif (($call instanceof MethodCall || $call instanceof StaticCall) && $call->name instanceof Identifier) {
            $name = $call->name->toString();
        }

        return is_string($name) ? strtolower($name) : null;
    }

    /**
     * @param array<Node>|Node $nodes
     * @return array{0: list<Assign>, 1: list<Expr>, 2: list<Foreach_>}
     */
    private function scopeNodes(array|Node $nodes): array
    {
        $assignments = [];
        $calls = [];
        $foreaches = [];
        $this->walkScope($nodes, $assignments, $calls, $foreaches);
        return [$assignments, $calls, $foreaches];
    }

    /**
     * @param array<Node>|Node|scalar|null $value
     * @param list<Assign> $assignments
     * @param list<Expr> $calls
     * @param list<Foreach_> $foreaches
     */
    private function walkScope(mixed $value, array &$assignments, array &$calls, array &$foreaches): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->walkScope($item, $assignments, $calls, $foreaches);
            }
            return;
        }
        if (!$value instanceof Node) {
            return;
        }
        if ($value instanceof Function_ || $value instanceof ClassMethod) {
            return;
        }
        if ($value instanceof Assign) {
            $assignments[] = $value;
        }
        if ($value instanceof Foreach_) {
            $foreaches[] = $value;
        }
        if ($value instanceof FuncCall || $value instanceof MethodCall || $value instanceof StaticCall) {
            $calls[] = $value;
        }
        foreach ($value->getSubNodeNames() as $subNodeName) {
            $this->walkScope($value->{$subNodeName}, $assignments, $calls, $foreaches);
        }
    }
}

/** @return list<array{call: string, line: int}> */
function phpTestExecutableDdlCalls(string $source, ?string $sourcePath = null): array
{
    return (new PhpTestDdlInventoryAnalyzer($sourcePath))->analyze($source);
}

function phpTestDeclaredLevel(string $source): ?string
{
    if (preg_match('/^\s*\/\/\s*@requiere:\s*([a-z-]+)\s*$/m', $source, $match) === 1) {
        return $match[1];
    }
    if (preg_match('/#\[Group\(\s*[\'\"]([a-z-]+)[\'\"]\s*\)\]/', $source, $match) === 1) {
        return $match[1];
    }

    return null;
}

/**
 * @param array<string, string> $testLevels path => declared level
 * @return list<array{file: string, level: string, calls: list<array{call: string, line: int}>}>
 */
function phpTestDdlLevelViolations(array $testLevels): array
{
    $violations = [];
    foreach ($testLevels as $path => $level) {
        $source = @file_get_contents($path);
        if ($source === false) {
            $violations[] = [
                'file' => $path,
                'level' => $level,
                'calls' => [['call' => 'unreadable', 'line' => 0]],
            ];
            continue;
        }
        $calls = phpTestExecutableDdlCalls($source, $path);
        if ($calls !== [] && $level !== 'admin-db') {
            $violations[] = ['file' => $path, 'level' => $level, 'calls' => $calls];
        }
    }

    return $violations;
}
