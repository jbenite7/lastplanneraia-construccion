<?php

declare(strict_types=1);

$phpParserAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!class_exists(PhpParser\ParserFactory::class) && is_file($phpParserAutoload)) {
    require_once $phpParserAutoload;
}

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_ as ArrayExpr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Closure;
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
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
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
 *   alternatives: non-empty-list<string>,
 *   complete: bool,
 *   deps: list<int>,
 *   external: bool
 * }
 * @phpstan-type FunctionSummary array{params: list<int>, always: bool}
 */
final class PhpTestDdlInventoryAnalyzer
{
    private const MAX_SQL_ALTERNATIVES = 4096;

    public function __construct(private readonly ?string $sourcePath = null)
    {
    }

    /** @var array<string, FunctionLike> */
    private array $callables = [];

    /** @var array<string, string> normalized function name => callable key */
    private array $functionKeys = [];

    /** @var array<string, array<string, string>> class key => method name => callable key */
    private array $methodKeys = [];

    /** @var array<string, string> normalized class name => class key */
    private array $classNameKeys = [];

    /** @var array<string, string> class key => parent class key/name */
    private array $classParents = [];

    /** @var array<string, true> callable keys referenced by PHPUnit DataProvider attributes */
    private array $phpUnitProviderKeys = [];

    /** @var array<string, SqlValue> normalized class::constant => literal value */
    private array $classConstants = [];

    /** @var array<string, string|null> callable key => owning class key */
    private array $callableClasses = [];

    /** @var array<string, string> callable key => lexical parent scope key */
    private array $callableParents = [];

    /** @var array<int, string> callable node object id => callable key */
    private array $callableNodeKeys = [];

    /** @var array<string, array<string, list<string>>> scope key => variable => closure keys */
    private array $closureBindings = [];

    /** @var array<string, array<string, true>> scope key => variable => unresolved callable */
    private array $unresolvedCallableBindings = [];

    /** @var list<Node\Stmt> */
    private array $topLevelStatements = [];

    /** @var array<string, FunctionSummary> */
    private array $summaries = [];

    /** @var array<string, list<SqlValue>> callable key => abstract return components */
    private array $returnSummaries = [];

    private string $evaluationScopeKey = 'top';

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

        $this->topLevelStatements = $statements;
        $this->registerCallables($statements);
        $this->buildFunctionSummaries();

        $this->inspectRootScope($statements, [], 'top');
        foreach ($this->callables as $key => $callable) {
            if (!$callable instanceof ClassMethod
                || (!$this->isPhpUnitEntrypoint($callable) && !isset($this->phpUnitProviderKeys[$key]))) {
                continue;
            }
            $this->inspectRootScope(
                $callable->getStmts() ?? [],
                $this->parameterEnvironment($callable, $key),
                $key,
            );
        }

        usort(
            $this->findings,
            static fn(array $left, array $right): int => [$left['line'], $left['call']] <=> [$right['line'], $right['call']],
        );

        return $this->findings;
    }

    /** @param list<Node\Stmt> $statements */
    private function registerCallables(array $statements): void
    {
        $finder = new NodeFinder();
        $this->registerClassConstants($statements);
        $this->registerIncludedClassConstants($statements);
        foreach ($finder->findInstanceOf($statements, Function_::class) as $function) {
            $name = strtolower($function->name->toString());
            $key = 'function:' . $name;
            $this->registerCallable($key, $function, null, 'top');
            $this->functionKeys[$name] = $key;
        }

        /** @var array<string, ClassLike> $classes */
        $classes = [];
        foreach ($finder->findInstanceOf($statements, ClassLike::class) as $class) {
            $declaredName = $class->name?->toString();
            $classKey = $declaredName === null
                ? 'anonymous@' . max(0, $class->getStartFilePos())
                : strtolower($declaredName);
            $classes[$classKey] = $class;
            if ($declaredName !== null) {
                $this->classNameKeys[strtolower($declaredName)] = $classKey;
            }
            foreach ($class->getMethods() as $method) {
                $methodName = strtolower($method->name->toString());
                $key = 'method:' . $classKey . ':' . $methodName;
                $this->registerCallable($key, $method, $classKey, 'top');
                $this->methodKeys[$classKey][$methodName] = $key;
            }
        }
        foreach ($classes as $classKey => $class) {
            if (!$class instanceof Class_ || $class->extends === null) {
                continue;
            }
            $parts = $class->extends->getParts();
            $parentName = strtolower((string) end($parts));
            $this->classParents[$classKey] = $this->classNameKeys[$parentName] ?? $parentName;
        }
        $this->registerPhpUnitProviders();

        $this->registerClosuresInScope($statements, 'top');
        $processed = [];
        do {
            $pending = array_diff(array_keys($this->callables), $processed);
            foreach ($pending as $key) {
                $processed[] = $key;
                $this->registerClosuresInScope($this->callables[$key]->getStmts() ?? [], $key);
            }
        } while ($pending !== []);

        $changed = true;
        $iterations = 0;
        while ($changed && $iterations <= count($this->callables) + 1) {
            $changed = false;
            $iterations++;
            $bindingScopes = array_values(array_unique(array_merge(
                array_keys($this->closureBindings),
                array_keys($this->unresolvedCallableBindings),
            )));
            foreach ($bindingScopes as $scopeKey) {
                [$aliases] = $this->scopeNodes($this->scopeStatements($scopeKey, $statements));
                foreach ($aliases as $alias) {
                    if (!$alias->var instanceof Variable || !is_string($alias->var->name)
                        || !$alias->expr instanceof Variable || !is_string($alias->expr->name)) {
                        continue;
                    }
                    $targets = $this->closureBindings[$scopeKey][$alias->expr->name] ?? [];
                    if ($targets === []) {
                        continue;
                    }
                    $current = $this->closureBindings[$scopeKey][$alias->var->name] ?? [];
                    $merged = array_values(array_unique(array_merge($current, $targets)));
                    sort($merged);
                    if ($merged !== $current) {
                        $this->closureBindings[$scopeKey][$alias->var->name] = $merged;
                        $changed = true;
                    }
                    if (($this->unresolvedCallableBindings[$scopeKey][$alias->expr->name] ?? false)
                        && !isset($this->unresolvedCallableBindings[$scopeKey][$alias->var->name])) {
                        $this->unresolvedCallableBindings[$scopeKey][$alias->var->name] = true;
                        $changed = true;
                    }
                }
            }
        }
    }

    private function registerPhpUnitProviders(): void
    {
        foreach ($this->callables as $key => $callable) {
            if (!$callable instanceof ClassMethod) {
                continue;
            }
            $classKey = $this->callableClasses[$key] ?? null;
            if ($classKey === null) {
                continue;
            }
            foreach ($callable->getAttrGroups() as $group) {
                foreach ($group->attrs as $attribute) {
                    $parts = $attribute->name->getParts();
                    $attributeName = strtolower((string) end($parts));
                    if (!in_array($attributeName, ['dataprovider', 'dataproviderexternal'], true)) {
                        continue;
                    }
                    if ($attributeName === 'dataproviderexternal') {
                        $providerKey = $this->externalProviderKey($attribute, $classKey);
                    } else {
                        $provider = $attribute->args[0]->value ?? null;
                        $providerKey = $provider instanceof String_
                            ? $this->methodKeyForClass($classKey, strtolower($provider->value))
                            : null;
                    }
                    if ($providerKey === null) {
                        $this->addFinding('unresolved-data-provider', $attribute->getStartLine());
                        continue;
                    }
                    $this->phpUnitProviderKeys[$providerKey] = true;
                }
            }
        }
    }

    private function externalProviderKey(Node\Attribute $attribute, string $scopeClassKey): ?string
    {
        $class = $attribute->args[0]->value ?? null;
        $method = $attribute->args[1]->value ?? null;
        if (!$class instanceof Expr\ClassConstFetch || !$class->class instanceof Name
            || !$class->name instanceof Identifier || strtolower($class->name->toString()) !== 'class'
            || !$method instanceof String_) {
            return null;
        }
        $parts = $class->class->getParts();
        $className = strtolower((string) end($parts));
        $classKey = match ($className) {
            'self', 'static' => $scopeClassKey,
            'parent' => $this->classParents[$scopeClassKey] ?? null,
            default => $this->classNameKeys[$className] ?? null,
        };

        return $classKey === null
            ? null
            : $this->methodKeyForClass($classKey, strtolower($method->value));
    }

    /** @param list<Node\Stmt> $statements */
    private function registerClassConstants(array $statements): void
    {
        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($statements, ClassLike::class) as $class) {
            if ($class->name === null) {
                continue;
            }
            $className = strtolower($class->name->toString());
            foreach ($class->stmts as $statement) {
                if (!$statement instanceof Node\Stmt\ClassConst) {
                    continue;
                }
                foreach ($statement->consts as $constant) {
                    $value = $this->expressionValue($constant->value, []);
                    if ($value['complete']) {
                        $this->classConstants[$className . '::' . strtolower($constant->name->toString())] = $value;
                    }
                }
            }
        }
    }

    /** @param list<Node\Stmt> $statements */
    private function registerIncludedClassConstants(array $statements): void
    {
        if ($this->sourcePath === null) {
            return;
        }
        $repositoryRoot = realpath(dirname(__DIR__, 2));
        if ($repositoryRoot === false) {
            return;
        }
        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($statements, Expr\Include_::class) as $include) {
            $path = $this->expressionValue($include->expr, []);
            $pathText = $this->singleCompleteText($path);
            if ($pathText === null) {
                continue;
            }
            $resolved = realpath($pathText);
            if ($resolved === false || !str_starts_with($resolved, $repositoryRoot . DIRECTORY_SEPARATOR)
                || strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }
            $source = @file_get_contents($resolved);
            if ($source === false) {
                continue;
            }
            try {
                $includedStatements = (new ParserFactory())->createForHostVersion()->parse($source);
            } catch (Throwable) {
                continue;
            }
            if ($includedStatements !== null) {
                $this->registerClassConstants($includedStatements);
            }
        }
    }

    private function registerCallable(
        string $key,
        FunctionLike $callable,
        ?string $classKey,
        string $parentScope,
    ): void
    {
        $this->callables[$key] = $callable;
        $this->callableClasses[$key] = $classKey;
        $this->callableParents[$key] = $parentScope;
        $this->callableNodeKeys[spl_object_id($callable)] = $key;
    }

    /** @param array<Node>|Node $nodes */
    private function registerClosuresInScope(array|Node $nodes, string $scopeKey): void
    {
        $walk = function (mixed $value) use (&$walk, $scopeKey): void {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }
                return;
            }
            if (!$value instanceof Node) {
                return;
            }
            if ($value instanceof Closure || $value instanceof ArrowFunction) {
                if (!isset($this->callableNodeKeys[spl_object_id($value)])) {
                    $key = 'closure@' . max(0, $value->getStartFilePos()) . ':' . spl_object_id($value);
                    $this->registerCallable(
                        $key,
                        $value,
                        $this->callableClasses[$scopeKey] ?? null,
                        $scopeKey,
                    );
                }
                return;
            }
            if ($value instanceof FunctionLike) {
                return;
            }
            if ($value instanceof Assign && $value->var instanceof Variable
                && is_string($value->var->name) && $this->isUnresolvedCallableFactory($value->expr)) {
                $this->unresolvedCallableBindings[$scopeKey][$value->var->name] = true;
            }
            if ($value instanceof Assign && $value->var instanceof Variable
                && is_string($value->var->name)
                && ($value->expr instanceof Closure || $value->expr instanceof ArrowFunction)) {
                $closure = $value->expr;
                $key = $this->callableNodeKeys[spl_object_id($closure)] ?? null;
                if ($key === null) {
                    $key = 'closure@' . max(0, $closure->getStartFilePos()) . ':' . spl_object_id($closure);
                    $this->registerCallable(
                        $key,
                        $closure,
                        $this->callableClasses[$scopeKey] ?? null,
                        $scopeKey,
                    );
                }
                $bindings = $this->closureBindings[$scopeKey][$value->var->name] ?? [];
                $bindings[] = $key;
                $bindings = array_values(array_unique($bindings));
                sort($bindings);
                $this->closureBindings[$scopeKey][$value->var->name] = $bindings;
                return;
            }
            foreach ($value->getSubNodeNames() as $subNodeName) {
                $walk($value->{$subNodeName});
            }
        };
        $walk($nodes);
    }

    private function isUnresolvedCallableFactory(Expr $expression): bool
    {
        if (($expression instanceof FuncCall || $expression instanceof MethodCall || $expression instanceof StaticCall)
            && $expression->isFirstClassCallable()) {
            return true;
        }
        if (!$expression instanceof StaticCall || $this->callName($expression) !== 'fromcallable'
            || !$expression->class instanceof Name) {
            return false;
        }
        $parts = $expression->class->getParts();

        return strtolower((string) end($parts)) === 'closure';
    }

    /** @param list<Node\Stmt> $topLevel @return list<Node\Stmt> */
    private function scopeStatements(string $scopeKey, array $topLevel): array
    {
        if ($scopeKey === 'top') {
            return $topLevel;
        }

        return $this->callables[$scopeKey]->getStmts() ?? [];
    }

    /**
     * @param array<Node>|Node $nodes
     * @param array<string, SqlValue> $initialEnvironment
     */
    private function inspectRootScope(array|Node $nodes, array $initialEnvironment, string $scopeKey): void
    {
        $previousScope = $this->evaluationScopeKey;
        $this->evaluationScopeKey = $scopeKey;
        [, $calls] = $this->scopeNodes($nodes);
        foreach ($calls as $call) {
            $environment = $this->environmentBefore(
                $nodes,
                $initialEnvironment,
                $call->getStartFilePos(),
            );
            $this->inspectTopLevelCall($call, $environment, $scopeKey);
        }
        $this->evaluationScopeKey = $previousScope;
    }

    /** @return array<string, SqlValue> */
    private function parameterEnvironment(FunctionLike $function, string $scopeKey): array
    {
        $environment = [];
        foreach ($function->getParams() as $index => $parameter) {
            if ($parameter->var instanceof Variable && is_string($parameter->var->name)) {
                $environment[$parameter->var->name] = $this->unknownValue([$index], false);
            }
        }

        if ($function instanceof Closure) {
            $parentScope = $this->callableParents[$scopeKey] ?? 'top';
            $parentStatements = $this->scopeStatements($parentScope, $this->topLevelStatements);
            $outerEnvironment = $this->environmentBefore(
                $parentStatements,
                [],
                $function->getStartFilePos(),
            );
            foreach ($function->uses as $use) {
                if (is_string($use->var->name)) {
                    $environment[$use->var->name] = $outerEnvironment[$use->var->name]
                        ?? $this->unknownValue([], true);
                }
            }
        }
        if ($function instanceof Function_) {
            $topLevelEnvironment = $this->environmentBefore(
                $this->topLevelStatements,
                [],
                PHP_INT_MAX,
            );
            $finder = new NodeFinder();
            foreach ($finder->findInstanceOf($function->getStmts(), Node\Stmt\Global_::class) as $global) {
                foreach ($global->vars as $variable) {
                    if ($variable instanceof Variable && is_string($variable->name)) {
                        $environment[$variable->name] = $topLevelEnvironment[$variable->name]
                            ?? $this->unknownValue([], true);
                    }
                }
            }
        }

        return $environment;
    }

    private function isPhpUnitEntrypoint(ClassMethod $method): bool
    {
        $name = strtolower($method->name->toString());
        if (str_starts_with($name, 'test') || in_array(
            $name,
            ['setup', 'teardown', 'setupbeforeclass', 'teardownafterclass'],
            true,
        )) {
            return true;
        }
        foreach ($method->getAttrGroups() as $group) {
            foreach ($group->attrs as $attribute) {
                $parts = $attribute->name->getParts();
                $attributeName = strtolower((string) end($parts));
                if (in_array($attributeName, ['test', 'before', 'after', 'beforeclass', 'afterclass'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    private function localCallableKeys(Expr $call, string $scopeKey): array
    {
        if ($call instanceof FuncCall) {
            if ($call->name instanceof Name) {
                $parts = $call->name->getParts();
                $name = strtolower((string) end($parts));
                if ($name === 'call_user_func') {
                    $callback = $call->args[0]->value ?? null;
                    return $callback instanceof Expr
                        ? $this->callableExpressionKeys($callback, $scopeKey)
                        : [];
                }
                return isset($this->functionKeys[$name]) ? [$this->functionKeys[$name]] : [];
            }
            if ($call->name instanceof Variable && is_string($call->name->name)) {
                return $this->closureBindings[$scopeKey][$call->name->name] ?? [];
            }
            if ($call->name instanceof Closure || $call->name instanceof ArrowFunction) {
                $key = $this->callableNodeKeys[spl_object_id($call->name)] ?? null;
                return $key === null ? [] : [$key];
            }
            return $this->callableExpressionKeys($call->name, $scopeKey);
        }

        $name = $this->callName($call);
        if ($name === null) {
            return [];
        }
        $classKey = $this->callableClasses[$scopeKey] ?? null;
        if ($call instanceof MethodCall && $call->var instanceof Variable
            && $call->var->name === 'this' && $classKey !== null) {
            $key = $this->methodKeyForClass($classKey, $name);
            return $key === null ? [] : [$key];
        }
        if ($call instanceof StaticCall && $call->class instanceof Name) {
            $parts = $call->class->getParts();
            $calledClass = strtolower((string) end($parts));
            if (in_array($calledClass, ['self', 'static'], true)) {
                $calledClass = $classKey;
            } elseif ($calledClass === 'parent') {
                $calledClass = $classKey === null ? null : ($this->classParents[$classKey] ?? null);
            } else {
                $calledClass = $this->classNameKeys[$calledClass] ?? null;
            }
            if ($calledClass !== null) {
                $key = $this->methodKeyForClass($calledClass, $name);
                return $key === null ? [] : [$key];
            }
        }

        return [];
    }

    private function methodKeyForClass(string $classKey, string $methodName): ?string
    {
        $visited = [];
        while (!isset($visited[$classKey])) {
            $visited[$classKey] = true;
            $key = $this->methodKeys[$classKey][$methodName] ?? null;
            if ($key !== null) {
                return $key;
            }
            $parent = $this->classParents[$classKey] ?? null;
            if ($parent === null) {
                return null;
            }
            $classKey = $this->classNameKeys[$parent] ?? $parent;
        }

        return null;
    }

    /** @return list<string> */
    private function callableExpressionKeys(Expr $callback, string $scopeKey): array
    {
        if ($callback instanceof String_) {
            $key = $this->functionKeys[strtolower($callback->value)] ?? null;
            return $key === null ? [] : [$key];
        }
        if ($callback instanceof Variable && is_string($callback->name)) {
            return $this->closureBindings[$scopeKey][$callback->name] ?? [];
        }
        if ($callback instanceof Closure || $callback instanceof ArrowFunction) {
            $key = $this->callableNodeKeys[spl_object_id($callback)] ?? null;
            return $key === null ? [] : [$key];
        }
        if (!$callback instanceof ArrayExpr || count($callback->items) !== 2) {
            return [];
        }
        $target = $callback->items[0]->value;
        $method = $callback->items[1]->value;
        if (!$method instanceof String_) {
            return [];
        }
        $classKey = null;
        if ($target instanceof Variable && $target->name === 'this') {
            $classKey = $this->callableClasses[$scopeKey] ?? null;
        } elseif ($target instanceof Expr\ClassConstFetch && $target->class instanceof Name
            && $target->name instanceof Identifier && strtolower($target->name->toString()) === 'class') {
            $parts = $target->class->getParts();
            $className = strtolower((string) end($parts));
            $currentClass = $this->callableClasses[$scopeKey] ?? null;
            if (in_array($className, ['self', 'static'], true)) {
                $classKey = $currentClass;
            } elseif ($className === 'parent') {
                $classKey = $currentClass === null ? null : ($this->classParents[$currentClass] ?? null);
            } else {
                $classKey = $this->classNameKeys[$className] ?? null;
            }
        } elseif ($target instanceof String_) {
            $classKey = $this->classNameKeys[strtolower($target->value)] ?? null;
        }
        if ($classKey === null) {
            return [];
        }
        $key = $this->methodKeyForClass($classKey, strtolower($method->value));

        return $key === null ? [] : [$key];
    }

    private function unresolvedIndirectCallIsUnsafe(Expr $call, string $scopeKey): bool
    {
        if (!$call instanceof FuncCall) {
            return false;
        }
        if ($call->name instanceof Name) {
            return in_array($this->callName($call), ['call_user_func', 'call_user_func_array'], true);
        }
        if ($call->name instanceof Variable && is_string($call->name->name)) {
            return $this->unresolvedCallableBindings[$scopeKey][$call->name->name] ?? false;
        }

        return true;
    }

    /** @param array<string, SqlValue> $environment */
    private function unresolvedScopedCallIsUnsafe(
        Expr $call,
        array $environment,
        string $scopeKey,
    ): bool {
        if (!$call instanceof MethodCall && !$call instanceof StaticCall) {
            return false;
        }
        $name = $this->callName($call);
        if ($name === null || preg_match('/^(?:assert|expect|createMock|createStub)/i', $name) === 1) {
            return false;
        }
        $isScoped = $call instanceof MethodCall
            && $call->var instanceof Variable && $call->var->name === 'this';
        if ($call instanceof StaticCall && $call->class instanceof Name) {
            $parts = $call->class->getParts();
            $isScoped = in_array(strtolower((string) end($parts)), ['self', 'static', 'parent'], true);
        }
        if (!$isScoped || ($this->callableClasses[$scopeKey] ?? null) === null) {
            return false;
        }
        foreach ($call->args as $argument) {
            if ($this->sqlKind($this->expressionValue($argument->value, $environment)) !== 'safe') {
                return true;
            }
        }

        return false;
    }

    private function buildFunctionSummaries(): void
    {
        foreach ($this->callables as $key => $_callable) {
            $this->summaries[$key] = ['params' => [], 'always' => false];
            $this->returnSummaries[$key] = [];
        }

        $changed = true;
        $iterations = 0;
        while ($changed && $iterations <= count($this->callables) + 1) {
            $changed = false;
            $iterations++;
            foreach ($this->callables as $key => $callable) {
                $calculated = $this->summarizeFunction($callable, $key);
                $calculatedReturns = $this->summarizeReturnValues($callable, $key);
                $mergedParams = array_values(array_unique(array_merge(
                    $this->summaries[$key]['params'],
                    $calculated['params'],
                )));
                sort($mergedParams);
                $merged = [
                    'params' => $mergedParams,
                    'always' => $this->summaries[$key]['always'] || $calculated['always'],
                ];
                if ($merged !== $this->summaries[$key]) {
                    $this->summaries[$key] = $merged;
                    $changed = true;
                }
                if ($calculatedReturns !== $this->returnSummaries[$key]) {
                    $this->returnSummaries[$key] = $calculatedReturns;
                    $changed = true;
                }
            }
        }
    }

    /** @return FunctionSummary */
    private function summarizeFunction(FunctionLike $function, string $scopeKey): array
    {
        $previousScope = $this->evaluationScopeKey;
        $this->evaluationScopeKey = $scopeKey;
        $initialEnvironment = $this->parameterEnvironment($function, $scopeKey);
        $statements = $function->getStmts() ?? [];

        [, $calls] = $this->scopeNodes($statements);
        $summary = ['params' => [], 'always' => false];
        foreach ($calls as $call) {
            $environment = $this->environmentBefore(
                $statements,
                $initialEnvironment,
                $call->getStartFilePos(),
            );
            $calleeKeys = $this->localCallableKeys($call, $scopeKey);
            if ($calleeKeys !== []) {
                foreach ($calleeKeys as $calleeKey) {
                    $callee = $this->summaries[$calleeKey] ?? ['params' => [], 'always' => false];
                    if ($callee['always']) {
                        $summary['always'] = true;
                    }
                    foreach ($callee['params'] as $parameterIndex) {
                        $this->absorbSummaryValue(
                            $summary,
                            $this->argumentValueForCallable(
                                $call,
                                $parameterIndex,
                                $environment,
                                $calleeKey,
                            ),
                        );
                    }
                }
                continue;
            }
            if ($this->unresolvedIndirectCallIsUnsafe($call, $scopeKey)
                || $this->unresolvedScopedCallIsUnsafe($call, $environment, $scopeKey)) {
                $summary['always'] = true;
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
        $this->evaluationScopeKey = $previousScope;

        return $summary;
    }

    /** @return list<SqlValue> */
    private function summarizeReturnValues(FunctionLike $function, string $scopeKey): array
    {
        $previousScope = $this->evaluationScopeKey;
        $this->evaluationScopeKey = $scopeKey;
        $statements = $function->getStmts() ?? [];
        $initialEnvironment = $this->parameterEnvironment($function, $scopeKey);
        $returns = [];
        $walk = function (mixed $value, bool $root = false) use (&$walk, &$returns): void {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }
                return;
            }
            if (!$value instanceof Node || (!$root && $value instanceof FunctionLike)) {
                return;
            }
            if ($value instanceof Node\Stmt\Return_) {
                $returns[] = $value;
                return;
            }
            foreach ($value->getSubNodeNames() as $subNodeName) {
                $walk($value->{$subNodeName});
            }
        };
        $walk($statements, true);

        $summary = [];
        foreach ($returns as $return) {
            $environment = $this->environmentBefore(
                $statements,
                $initialEnvironment,
                $return->getStartFilePos(),
            );
            $components = [];
            if ($return->expr instanceof ArrayExpr) {
                foreach ($return->expr->items as $item) {
                    $components[] = $this->expressionValue($item->value, $environment);
                }
            } elseif ($return->expr instanceof Expr) {
                $components[] = $this->expressionValue($return->expr, $environment);
            } else {
                $components[] = $this->knownValue('');
            }
            foreach ($components as $index => $component) {
                $summary[$index] = isset($summary[$index])
                    ? $this->alternativeValues($summary[$index], $component)
                    : $component;
            }
        }
        ksort($summary);
        $this->evaluationScopeKey = $previousScope;

        return array_values($summary);
    }

    /**
     * Instancia el resumen abstracto de retorno de un callable local con los argumentos del callsite.
     *
     * @param array<string, SqlValue> $environment
     * @return list<SqlValue>|null
     */
    private function localCallableReturnValues(Expr $call, array $environment): ?array
    {
        $keys = $this->localCallableKeys($call, $this->evaluationScopeKey);
        if ($keys === []) {
            return null;
        }

        $components = [];
        foreach ($keys as $key) {
            foreach ($this->returnSummaries[$key] ?? [] as $index => $value) {
                $instantiated = $this->instantiateReturnValue($value, $call, $environment, $key);
                $components[$index] = isset($components[$index])
                    ? $this->alternativeValues($components[$index], $instantiated)
                    : $instantiated;
            }
        }
        ksort($components);

        return array_values($components);
    }

    /**
     * @param SqlValue $value
     * @param array<string, SqlValue> $environment
     * @return SqlValue
     */
    private function instantiateReturnValue(
        array $value,
        Expr $call,
        array $environment,
        string $calleeKey,
    ): array
    {
        if ($this->sqlKind($value) === 'ddl') {
            return $this->knownValue('CREATE');
        }
        if ($value['complete']) {
            return $value;
        }

        $dependencies = [];
        $external = $value['external'];
        $resolvedArguments = [];
        $allResolved = !$external && $value['deps'] !== [];
        foreach ($value['deps'] as $parameterIndex) {
            $argument = $this->argumentValueForCallable(
                $call,
                $parameterIndex,
                $environment,
                $calleeKey,
            );
            if ($this->sqlKind($argument) === 'ddl') {
                return $this->knownValue('CREATE');
            }
            $resolvedArguments[$parameterIndex] = $argument;
            if (!$argument['complete']) {
                $allResolved = false;
                $dependencies = array_merge($dependencies, $argument['deps']);
                $external = $external || $argument['external'];
            }
        }

        if (!$external && count($value['deps']) === 1 && $value['alternatives'] === ['?']) {
            $parameterIndex = $value['deps'][0];
            return $resolvedArguments[$parameterIndex] ?? $this->unknownValue([], true);
        }
        if ($allResolved && $value['alternatives'] === ['?']
            && $this->identifierArgumentsAreSafe($resolvedArguments)) {
            return $this->knownValue('safe_fragment');
        }

        return $this->unknownValue($dependencies, $external);
    }

    /** @param array<int, SqlValue> $arguments */
    private function identifierArgumentsAreSafe(array $arguments): bool
    {
        $ddlVerbs = ['create', 'drop', 'alter', 'truncate', 'rename', 'grant', 'revoke'];
        foreach ($arguments as $argument) {
            if (!$argument['complete']) {
                return false;
            }
            foreach ($argument['alternatives'] as $alternative) {
                if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alternative) !== 1) {
                    return false;
                }
                $normalized = strtolower($alternative);
                foreach ($ddlVerbs as $verb) {
                    if (str_starts_with($verb, $normalized) || str_starts_with($normalized, $verb)) {
                        return false;
                    }
                }
            }
        }

        return $arguments !== [];
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
    private function inspectTopLevelCall(Expr $call, array $environment, string $scopeKey): void
    {
        $callName = $this->callName($call);
        $calleeKeys = $this->localCallableKeys($call, $scopeKey);
        if ($calleeKeys !== []) {
            foreach ($calleeKeys as $calleeKey) {
                $summary = $this->summaries[$calleeKey] ?? ['params' => [], 'always' => false];
                if ($summary['always']) {
                    $this->addFinding($callName ?? 'local-callable', $call->getStartLine());
                    return;
                }
                foreach ($summary['params'] as $parameterIndex) {
                    $argument = $this->argumentValueForCallable(
                        $call,
                        $parameterIndex,
                        $environment,
                        $calleeKey,
                    );
                    if ($this->sqlKind($argument) !== 'safe') {
                        $this->addFinding($callName ?? 'local-callable', $call->getStartLine());
                        return;
                    }
                }
            }
            return;
        }
        if ($this->unresolvedIndirectCallIsUnsafe($call, $scopeKey)
            || $this->unresolvedScopedCallIsUnsafe($call, $environment, $scopeKey)) {
            $this->addFinding($callName ?? 'indirect-callable', $call->getStartLine());
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
     * Reconstruye el entorno posible en el punto de la llamada. Las ramas se unen por may-analysis:
     * una alternativa DDL o desconocida nunca queda ocultada por la última asignación textual.
     *
     * @param array<Node>|Node $nodes
     * @param array<string, SqlValue> $initial
     * @return array<string, SqlValue>
     */
    private function environmentBefore(array|Node $nodes, array $initial, int $beforePosition): array
    {
        $statements = is_array($nodes) ? $nodes : [$nodes];
        [$assignments] = $this->scopeNodes($nodes);

        return $this->flowNodesBefore($statements, $initial, $beforePosition, $assignments);
    }

    /**
     * @param array<Node> $nodes
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function flowNodesBefore(
        array $nodes,
        array $environment,
        int $beforePosition,
        array $assignments,
    ): array {
        foreach ($nodes as $node) {
            $start = $node->getStartFilePos();
            $end = $node->getEndFilePos();
            if ($beforePosition >= 0 && $start >= 0 && $start >= $beforePosition) {
                break;
            }
            if ($beforePosition >= 0 && $start >= 0 && $end >= $beforePosition) {
                return $this->flowInsideNode($node, $environment, $beforePosition, $assignments);
            }
            $environment = $this->applyFlowNode($node, $environment, $assignments);
        }

        return $environment;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function flowInsideNode(
        Node $node,
        array $environment,
        int $beforePosition,
        array $assignments,
    ): array {
        if ($node instanceof Node\Stmt\If_) {
            foreach ($node->elseifs as $elseif) {
                if ($this->containsPosition($elseif, $beforePosition)) {
                    return $this->flowNodesBefore($elseif->stmts, $environment, $beforePosition, $assignments);
                }
            }
            if ($node->else !== null && $this->containsPosition($node->else, $beforePosition)) {
                return $this->flowNodesBefore($node->else->stmts, $environment, $beforePosition, $assignments);
            }
            foreach ($node->stmts as $statement) {
                if ($this->containsPosition($statement, $beforePosition)) {
                    return $this->flowNodesBefore($node->stmts, $environment, $beforePosition, $assignments);
                }
            }

            return $environment;
        }
        if ($node instanceof Node\Stmt\Switch_) {
            $entryAlternatives = [$environment];
            $fallthrough = $environment;
            foreach ($node->cases as $case) {
                if ($this->containsPosition($case, $beforePosition)) {
                    return $this->flowNodesBefore(
                        $case->stmts,
                        $this->joinEnvironments($entryAlternatives),
                        $beforePosition,
                        $assignments,
                    );
                }
                $fallthrough = $this->applyFlowNodes($case->stmts, $fallthrough, $assignments);
                $entryAlternatives[] = $fallthrough;
            }

            return $environment;
        }
        if ($this->isLoop($node)) {
            $head = $this->loopHeadEnvironment($node, $environment, $assignments);
            $bodyEnvironment = $this->bindLoopValues($node, $head, $assignments);
            /** @var list<Node\Stmt> $body */
            $body = $node->stmts;
            foreach ($body as $statement) {
                if ($this->containsPosition($statement, $beforePosition)) {
                    return $this->flowNodesBefore($body, $bodyEnvironment, $beforePosition, $assignments);
                }
            }

            return $head;
        }
        if ($node instanceof Node\Stmt\TryCatch) {
            foreach ($node->stmts as $statement) {
                if ($this->containsPosition($statement, $beforePosition)) {
                    return $this->flowNodesBefore($node->stmts, $environment, $beforePosition, $assignments);
                }
            }
            $tryEnvironment = $this->applyFlowNodes($node->stmts, $environment, $assignments);
            foreach ($node->catches as $catch) {
                if ($this->containsPosition($catch, $beforePosition)) {
                    return $this->flowNodesBefore(
                        $catch->stmts,
                        $this->joinEnvironments([$environment, $tryEnvironment]),
                        $beforePosition,
                        $assignments,
                    );
                }
            }
            if ($node->finally !== null && $this->containsPosition($node->finally, $beforePosition)) {
                $alternatives = [$tryEnvironment];
                foreach ($node->catches as $catch) {
                    $alternatives[] = $this->applyFlowNodes($catch->stmts, $environment, $assignments);
                }
                return $this->flowNodesBefore(
                    $node->finally->stmts,
                    $this->joinEnvironments($alternatives),
                    $beforePosition,
                    $assignments,
                );
            }

            return $environment;
        }

        return $this->applyStraightLineAssignments($node, $environment, $beforePosition);
    }

    /**
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function applyFlowNode(Node $node, array $environment, array $assignments): array
    {
        if ($node instanceof Node\Stmt\If_) {
            $alternatives = [$this->applyFlowNodes($node->stmts, $environment, $assignments)];
            foreach ($node->elseifs as $elseif) {
                $alternatives[] = $this->applyFlowNodes($elseif->stmts, $environment, $assignments);
            }
            $alternatives[] = $node->else === null
                ? $environment
                : $this->applyFlowNodes($node->else->stmts, $environment, $assignments);
            return $this->joinEnvironments($alternatives);
        }
        if ($node instanceof Node\Stmt\Switch_) {
            $alternatives = [];
            $fallthrough = $environment;
            $hasDefault = false;
            foreach ($node->cases as $case) {
                $hasDefault = $hasDefault || $case->cond === null;
                $alternatives[] = $this->applyFlowNodes($case->stmts, $environment, $assignments);
                $fallthrough = $this->applyFlowNodes($case->stmts, $fallthrough, $assignments);
                $alternatives[] = $fallthrough;
            }
            if (!$hasDefault) {
                $alternatives[] = $environment;
            }
            return $this->joinEnvironments($alternatives === [] ? [$environment] : $alternatives);
        }
        if ($this->isLoop($node)) {
            return $this->loopHeadEnvironment($node, $environment, $assignments);
        }
        if ($node instanceof Node\Stmt\TryCatch) {
            $alternatives = [$this->applyFlowNodes($node->stmts, $environment, $assignments)];
            foreach ($node->catches as $catch) {
                $alternatives[] = $this->applyFlowNodes($catch->stmts, $environment, $assignments);
            }
            $joined = $this->joinEnvironments($alternatives);
            return $node->finally === null
                ? $joined
                : $this->applyFlowNodes($node->finally->stmts, $joined, $assignments);
        }

        return $this->applyStraightLineAssignments($node, $environment, PHP_INT_MAX);
    }

    /**
     * @param array<Node> $nodes
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function applyFlowNodes(array $nodes, array $environment, array $assignments): array
    {
        foreach ($nodes as $node) {
            $environment = $this->applyFlowNode($node, $environment, $assignments);
        }

        return $environment;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return array<string, SqlValue>
     */
    private function applyStraightLineAssignments(
        Node $node,
        array $environment,
        int $beforePosition,
    ): array {
        $effects = [];
        $walk = function (mixed $value, bool $root = false) use (&$walk, &$effects): void {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }
                return;
            }
            if (!$value instanceof Node || $value instanceof FunctionLike) {
                return;
            }
            if (!$root && ($value instanceof Node\Stmt\If_ || $value instanceof Node\Stmt\Switch_
                || $this->isLoop($value) || $value instanceof Node\Stmt\TryCatch)) {
                return;
            }
            if ($value instanceof Assign) {
                $effects[] = $value;
            }
            if ($value instanceof FuncCall || $value instanceof MethodCall || $value instanceof StaticCall) {
                $effects[] = $value;
            }
            foreach ($value->getSubNodeNames() as $subNodeName) {
                $walk($value->{$subNodeName});
            }
        };
        $walk($node, true);
        usort(
            $effects,
            static fn(Expr $left, Expr $right): int => $left->getEndFilePos() <=> $right->getEndFilePos(),
        );
        foreach ($effects as $effect) {
            $end = $effect->getEndFilePos();
            if ($beforePosition >= 0 && $end >= $beforePosition) {
                continue;
            }
            if (!$effect instanceof Assign) {
                $this->applyByReferenceCallEffects($effect, $environment);
                continue;
            }
            $assignment = $effect;
            if ($assignment->var instanceof ArrayExpr || $assignment->var instanceof ListExpr) {
                $components = $this->destructuredExpressionValues($assignment->expr, $environment);
                foreach ($assignment->var->items as $index => $item) {
                    if ($item === null || !$item->value instanceof Variable
                        || !is_string($item->value->name)) {
                        continue;
                    }
                    $environment[$item->value->name] = $components[$index]
                        ?? $this->unknownValue([], true);
                }
                continue;
            }
            if ($assignment->var instanceof Expr\ArrayDimFetch
                && $assignment->var->var instanceof Variable
                && is_string($assignment->var->var->name)) {
                $name = $assignment->var->var->name;
                $current = $environment[$name] ?? $this->knownValue('');
                $environment[$name] = $this->alternativeValues(
                    $current,
                    $this->expressionValue($assignment->expr, $environment),
                );
                continue;
            }
            if (!$assignment->var instanceof Variable || !is_string($assignment->var->name)) {
                continue;
            }
            $environment[$assignment->var->name] = $this->expressionValue($assignment->expr, $environment);
            if ($assignment->expr instanceof Closure) {
                foreach ($assignment->expr->uses as $use) {
                    if ($use->byRef && is_string($use->var->name)) {
                        $environment[$use->var->name] = $this->unknownValue([], true);
                    }
                }
            }
        }

        return $environment;
    }

    /** @param array<string, SqlValue> $environment */
    private function applyByReferenceCallEffects(Expr $call, array &$environment): void
    {
        foreach ($this->localCallableKeys($call, $this->evaluationScopeKey) as $calleeKey) {
            $callable = $this->callables[$calleeKey] ?? null;
            if ($callable === null) {
                continue;
            }
            foreach ($callable->getParams() as $formalIndex => $parameter) {
                if (!$parameter->byRef) {
                    continue;
                }
                $argument = $this->argumentNodeForCallable($call, $formalIndex, $calleeKey);
                if ($argument?->value instanceof Variable && is_string($argument->value->name)) {
                    $environment[$argument->value->name] = $this->unknownValue([], true);
                }
            }
            $finder = new NodeFinder();
            $globalNames = [];
            foreach ($finder->findInstanceOf($callable->getStmts() ?? [], Node\Stmt\Global_::class) as $global) {
                foreach ($global->vars as $variable) {
                    if ($variable instanceof Variable && is_string($variable->name)) {
                        $globalNames[$variable->name] = true;
                    }
                }
            }
            foreach ($finder->findInstanceOf($callable->getStmts() ?? [], Assign::class) as $assignment) {
                if ($assignment->var instanceof Variable && is_string($assignment->var->name)
                    && isset($globalNames[$assignment->var->name])) {
                    $environment[$assignment->var->name] = $this->unknownValue([], true);
                }
            }
        }
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return list<SqlValue>
     */
    private function destructuredExpressionValues(Expr $expression, array $environment): array
    {
        $localReturns = $this->localCallableReturnValues($expression, $environment);
        if ($localReturns !== null) {
            return $localReturns;
        }
        // Database::insertProjectId conserva el SQL recibido y devuelve [SQL, params].
        // Propagar el primer argumento mantiene DML constante como seguro sin sanear un valor dinámico.
        if (($expression instanceof MethodCall || $expression instanceof StaticCall)
            && $this->callName($expression) === 'insertprojectid') {
            return [
                $this->argumentValue($expression, 0, $environment),
                isset($expression->args[2])
                    ? $this->expressionValue($expression->args[2]->value, $environment)
                    : $this->knownValue(''),
            ];
        }
        if (!$expression instanceof ArrayExpr) {
            return [];
        }

        $values = [];
        foreach ($expression->items as $item) {
            $values[] = $this->expressionValue($item->value, $environment);
        }

        return $values;
    }

    private function containsPosition(Node $node, int $position): bool
    {
        return $position >= 0 && $node->getStartFilePos() <= $position
            && $node->getEndFilePos() >= $position;
    }

    /**
     * @phpstan-assert-if-true Foreach_|Node\Stmt\For_|Node\Stmt\While_|Node\Stmt\Do_ $node
     */
    private function isLoop(Node $node): bool
    {
        return $node instanceof Foreach_ || $node instanceof Node\Stmt\For_
            || $node instanceof Node\Stmt\While_ || $node instanceof Node\Stmt\Do_;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function loopHeadEnvironment(
        Foreach_|Node\Stmt\For_|Node\Stmt\While_|Node\Stmt\Do_ $loop,
        array $environment,
        array $assignments,
    ): array
    {
        $base = $environment;
        if ($loop instanceof Node\Stmt\For_) {
            foreach ($loop->init as $initialization) {
                $base = $this->applyStraightLineAssignments($initialization, $base, PHP_INT_MAX);
            }
        }
        $head = $base;
        $limit = min(64, max(2, count($assignments) + 2));
        for ($iteration = 0; $iteration < $limit; $iteration++) {
            $bodyEnvironment = $this->bindLoopValues($loop, $head, $assignments);
            /** @var list<Node\Stmt> $body */
            $body = $loop->stmts;
            $afterBody = $this->applyFlowNodes($body, $bodyEnvironment, $assignments);
            if ($loop instanceof Node\Stmt\For_) {
                foreach ($loop->loop as $update) {
                    $afterBody = $this->applyStraightLineAssignments($update, $afterBody, PHP_INT_MAX);
                }
            }
            $next = $this->joinEnvironments([$base, $afterBody]);
            if ($next === $head) {
                break;
            }
            $head = $next;
        }

        return $head;
    }

    /**
     * @param array<string, SqlValue> $environment
     * @param list<Assign> $assignments
     * @return array<string, SqlValue>
     */
    private function bindLoopValues(
        Foreach_|Node\Stmt\For_|Node\Stmt\While_|Node\Stmt\Do_ $loop,
        array $environment,
        array $assignments,
    ): array
    {
        if (!$loop instanceof Foreach_) {
            return $environment;
        }
        $iterable = $this->resolveArrayExpression($loop->expr, $assignments, $loop->getStartFilePos());
        if ($iterable !== null) {
            $this->bindForeachValues($environment, $loop->valueVar, $iterable);
            if ($loop->keyVar !== null) {
                $this->bindForeachKeys($environment, $loop->keyVar, $iterable);
            }
            return $environment;
        }
        $values = $this->resolveIterableSqlValues($loop->expr, $environment);
        if ($loop->valueVar instanceof Variable && is_string($loop->valueVar->name)) {
            $environment[$loop->valueVar->name] = $values === null
                ? $this->unknownValue([], true)
                : $this->aggregateValues($values);
        } elseif ($loop->valueVar instanceof ArrayExpr || $loop->valueVar instanceof ListExpr) {
            foreach ($loop->valueVar->items as $item) {
                if ($item !== null && $item->value instanceof Variable && is_string($item->value->name)) {
                    $environment[$item->value->name] = $this->unknownValue([], true);
                }
            }
        }

        return $environment;
    }

    /**
     * @param list<array<string, SqlValue>> $environments
     * @return array<string, SqlValue>
     */
    private function joinEnvironments(array $environments): array
    {
        $names = [];
        foreach ($environments as $environment) {
            $names = array_merge($names, array_keys($environment));
        }
        $names = array_values(array_unique($names));
        sort($names);
        $joined = [];
        foreach ($names as $name) {
            $values = [];
            foreach ($environments as $environment) {
                $values[] = $environment[$name] ?? $this->unknownValue([], true);
            }
            $joined[$name] = $this->aggregateValues($values);
        }

        return $joined;
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
        if ($expression instanceof FuncCall && $this->callName($expression) === 'preg_split'
            && isset($expression->args[0], $expression->args[1])) {
            $pattern = $this->expressionValue($expression->args[0]->value, $environment);
            $subject = $this->expressionValue($expression->args[1]->value, $environment);
            $patternText = $this->singleCompleteText($pattern);
            $subjectText = $this->singleCompleteText($subject);
            if ($patternText === null || $subjectText === null) {
                return null;
            }
            $parts = @preg_split($patternText, $subjectText);
            if ($parts === false) {
                return null;
            }

            return array_map(fn(string $part): array => $this->knownValue($part), $parts);
        }

        if ($expression instanceof FuncCall && $this->callName($expression) === 'array_chunk'
            && isset($expression->args[0])) {
            $value = $this->expressionValue($expression->args[0]->value, $environment);
            return $value['complete'] ? [$value] : null;
        }

        $value = $this->expressionValue($expression, $environment);
        return $value['complete'] ? [$value] : null;
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

    /** @param array<string, SqlValue> $environment */
    private function bindForeachKeys(array &$environment, Expr $target, ArrayExpr $iterable): void
    {
        if (!$target instanceof Variable || !is_string($target->name)) {
            return;
        }
        $keys = [];
        foreach ($iterable->items as $index => $item) {
            if ($item->key instanceof Expr) {
                $keys[] = $item->key;
            } else {
                $keys[] = new Node\Scalar\Int_($index);
            }
        }
        $environment[$target->name] = $this->aggregateExpressions($keys, $environment);
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
     * Enlaza un argumento a la posición formal del callable, respetando nombres declarados.
     * Un unpack o un nombre que no pueda probarse se mantiene UNKNOWN.
     *
     * @param array<string, SqlValue> $environment
     * @return SqlValue
     */
    private function argumentValueForCallable(
        Expr $call,
        int $formalIndex,
        array $environment,
        string $calleeKey,
    ): array {
        $argument = $this->argumentNodeForCallable($call, $formalIndex, $calleeKey);
        return $argument === null
            ? $this->unknownValue([], true)
            : $this->expressionValue($argument->value, $environment);
    }

    private function argumentNodeForCallable(
        Expr $call,
        int $formalIndex,
        string $calleeKey,
    ): ?Node\Arg {
        $callable = $this->callables[$calleeKey] ?? null;
        $parameter = $callable?->getParams()[$formalIndex] ?? null;
        if ($parameter === null || !$parameter->var instanceof Variable
            || !is_string($parameter->var->name) || !property_exists($call, 'args')) {
            return null;
        }

        /** @var list<Node\Arg> $arguments */
        $arguments = $call->args;
        if ($this->callName($call) === 'call_user_func') {
            $arguments = array_slice($arguments, 1);
        }

        $positional = [];
        foreach ($arguments as $argument) {
            if ($argument->unpack) {
                return null;
            }
            if ($argument->name !== null) {
                if (strtolower($argument->name->toString()) === strtolower($parameter->var->name)) {
                    return $argument;
                }
                continue;
            }
            $positional[] = $argument;
        }

        return $positional[$formalIndex] ?? null;
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
        if ($expression instanceof Node\Scalar\Int_ || $expression instanceof Node\Scalar\Float_) {
            return $this->knownValue((string) $expression->value);
        }
        if ($expression instanceof Expr\ConstFetch) {
            return $this->knownValue(strtolower($expression->name->toString()));
        }
        if ($expression instanceof Expr\ClassConstFetch && $expression->class instanceof Name
            && $expression->name instanceof Identifier) {
            $classParts = $expression->class->getParts();
            $className = strtolower((string) end($classParts));
            $key = $className . '::' . strtolower($expression->name->toString());
            return $this->classConstants[$key] ?? $this->unknownValue([], true);
        }
        if ($expression instanceof ArrayExpr) {
            $values = [];
            foreach ($expression->items as $item) {
                $values[] = $this->expressionValue($item->value, $environment);
            }
            return $values === [] ? $this->knownValue('') : $this->aggregateValues($values);
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
        if ($expression instanceof Expr\ArrayDimFetch && $expression->var instanceof Variable
            && is_string($expression->var->name)) {
            return $environment[$expression->var->name] ?? $this->unknownValue([], true);
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
        if ($expression instanceof Expr\Cast\Int_ || $expression instanceof Expr\Cast\Double
            || $expression instanceof Expr\Cast\Bool_) {
            return $this->knownValue('0');
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
            if ($this->isExistenceGuardedIdentifierChoice($expression)) {
                return $this->knownValue('fixture_table');
            }
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
        if ($expression instanceof Expr\BinaryOp) {
            return $this->knownValue('0');
        }
        if ($expression instanceof FuncCall || $expression instanceof MethodCall
            || $expression instanceof StaticCall) {
            $localReturns = $this->localCallableReturnValues($expression, $environment);
            if ($localReturns !== null) {
                return $localReturns[0] ?? $this->unknownValue([], true);
            }
        }
        if ($expression instanceof StaticCall && $expression->class instanceof Name) {
            $classParts = $expression->class->getParts();
            $className = strtolower((string) end($classParts));
            $methodName = $this->callName($expression);
            if ($className === 'tableresolver' && in_array($methodName, ['resolve', 'resolvebyprefix'], true)) {
                $logicalArgument = $expression->args[count($expression->args) - 1]->value ?? null;
                if ($logicalArgument instanceof Expr) {
                    $logical = $this->expressionValue($logicalArgument, $environment);
                    $logicalText = $this->singleCompleteText($logical);
                    if ($logicalText !== null && preg_match('/^[A-Za-z0-9_]+$/', $logicalText) === 1) {
                        return $this->knownValue('fixture_table');
                    }
                }
            }
        }
        if ($expression instanceof FuncCall) {
            $functionValue = $this->functionExpressionValue($expression, $environment);
            if ($functionValue !== null) {
                return $functionValue;
            }
        }

        return $this->unknownValue([], true);
    }

    private function isExistenceGuardedIdentifierChoice(Ternary $ternary): bool
    {
        if (!$ternary->cond instanceof FuncCall || $this->callName($ternary->cond) !== 'tableexists'
            || !isset($ternary->cond->args[1])) {
            return false;
        }
        $candidate = $ternary->cond->args[1]->value;
        $whenTrue = $ternary->if ?? $ternary->cond;
        if (!$candidate instanceof Variable || !$whenTrue instanceof Variable
            || $candidate->name !== $whenTrue->name) {
            return false;
        }
        if ($ternary->else instanceof Ternary) {
            return $this->isExistenceGuardedIdentifierChoice($ternary->else);
        }

        return $ternary->else instanceof Expr\ConstFetch
            && strtolower($ternary->else->name->toString()) === 'null';
    }

    /**
     * @param array<string, SqlValue> $environment
     * @return SqlValue|null
     */
    private function functionExpressionValue(FuncCall $call, array $environment): ?array
    {
        $name = $this->callName($call);
        if ($name === null) {
            return null;
        }
        if ($name === 'file_get_contents' && isset($call->args[0])) {
            return $this->sourceControlledSqlFileValue(
                $this->expressionValue($call->args[0]->value, $environment),
            );
        }
        if ($name === 'dirname' && isset($call->args[0])) {
            $path = $this->expressionValue($call->args[0]->value, $environment);
            $pathText = $this->singleCompleteText($path);
            return $pathText !== null
                ? $this->knownValue(dirname($pathText))
                : $this->unknownValue($path['deps'], $path['external']);
        }
        if ($name === 'sprintf' && isset($call->args[0])) {
            $values = array_map(
                fn(Node\Arg $argument): array => $this->expressionValue($argument->value, $environment),
                $call->args,
            );
            $texts = array_map(fn(array $value): ?string => $this->singleCompleteText($value), $values);
            if (in_array(null, $texts, true)) {
                return $this->unknownValue(
                    array_merge(...array_map(static fn(array $value): array => $value['deps'], $values)),
                    true,
                );
            }
            /** @var string $format */
            $format = array_shift($texts);
            $rendered = @sprintf($format, ...$texts);
            return $this->knownValue($rendered);
        }
        if (in_array($name, ['implode', 'join'], true)) {
            if (count($call->args) === 1) {
                $delimiter = $this->knownValue('');
                $collectionExpression = $call->args[0]->value;
            } elseif (isset($call->args[0], $call->args[1])) {
                $delimiter = $this->expressionValue($call->args[0]->value, $environment);
                $collectionExpression = $call->args[1]->value;
            } else {
                return $this->unknownValue([], true);
            }
            $delimiterText = $this->singleCompleteText($delimiter);
            if ($delimiterText === null) {
                return $this->unknownValue($delimiter['deps'], $delimiter['external']);
            }
            if ($collectionExpression instanceof ArrayExpr) {
                $parts = [];
                foreach ($collectionExpression->items as $item) {
                    $part = $this->expressionValue($item->value, $environment);
                    $partText = $this->singleCompleteText($part);
                    if ($partText === null) {
                        return $this->unknownValue($part['deps'], $part['external']);
                    }
                    $parts[] = $partText;
                }
                return $this->knownValue(implode($delimiterText, $parts));
            }
            $collection = $this->expressionValue($collectionExpression, $environment);
            if (!$collection['complete']) {
                return $this->unknownValue($collection['deps'], $collection['external']);
            }
            return $this->sqlKind($collection) === 'ddl'
                ? $this->knownValue('CREATE')
                : $this->knownValue('safe_fragment');
        }
        if ($name === 'array_fill' && isset($call->args[2])) {
            return $this->expressionValue($call->args[2]->value, $environment);
        }
        if ($name === 'array_map' && isset($call->args[0], $call->args[1])) {
            $callback = $call->args[0]->value;
            $source = $this->expressionValue($call->args[1]->value, $environment);
            if ($callback instanceof String_) {
                $callbackName = strtolower($callback->value);
                if (in_array($callbackName, ['intval', 'floatval'], true)) {
                    return $this->knownValue('1');
                }
                if (in_array($callbackName, ['trim', 'ltrim', 'rtrim', 'strtolower', 'strtoupper', 'strval'], true)) {
                    return $source;
                }
            }
            if (($callback instanceof Closure || $callback instanceof ArrowFunction) && $source['complete']) {
                $callbackEnvironment = [];
                foreach ($callback->getParams() as $index => $parameter) {
                    if ($parameter->var instanceof Variable && is_string($parameter->var->name)) {
                        $callbackEnvironment[$parameter->var->name] = $index === 0
                            ? $source
                            : $this->unknownValue([], true);
                    }
                }
                $returns = [];
                foreach ($callback->getStmts() as $statement) {
                    if ($statement instanceof Node\Stmt\Return_ && $statement->expr instanceof Expr) {
                        $returns[] = $this->expressionValue($statement->expr, $callbackEnvironment);
                    }
                }
                if ($returns !== []) {
                    return $this->aggregateValues($returns);
                }
            }
            return $this->unknownValue([], true);
        }
        if (in_array($name, ['array_filter', 'array_values', 'array_unique', 'array_chunk'], true)
            && isset($call->args[0])) {
            return $this->expressionValue($call->args[0]->value, $environment);
        }
        if (in_array($name, ['explode', 'preg_split'], true)) {
            $subjectIndex = $name === 'explode' ? 1 : 1;
            return isset($call->args[$subjectIndex])
                ? $this->expressionValue($call->args[$subjectIndex]->value, $environment)
                : $this->unknownValue([], true);
        }
        if (in_array($name, ['trim', 'ltrim', 'rtrim', 'strtolower', 'strtoupper'], true)
            && isset($call->args[0])) {
            $value = $this->expressionValue($call->args[0]->value, $environment);
            if (!$value['complete']) {
                return $value;
            }
            $transform = match ($name) {
                'trim' => trim(...),
                'ltrim' => ltrim(...),
                'rtrim' => rtrim(...),
                'strtolower' => strtolower(...),
                'strtoupper' => strtoupper(...),
            };
            return $this->aggregateValues(array_map(
                fn(string $alternative): array => $this->knownValue($transform($alternative)),
                $value['alternatives'],
            ));
        }
        if (in_array($name, ['intval', 'floatval', 'count', 'sizeof', 'round', 'floor', 'ceil', 'min', 'max'], true)) {
            return $this->knownValue('1');
        }

        return null;
    }

    /** @param SqlValue $path @return SqlValue */
    private function sourceControlledSqlFileValue(array $path): array
    {
        $pathText = $this->singleCompleteText($path);
        if ($pathText === null) {
            return $this->unknownValue($path['deps'], true);
        }
        $repositoryRoot = realpath(dirname(__DIR__, 2));
        $resolvedPath = realpath($pathText);
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
        return [
            'template' => $text,
            'alternatives' => [$text],
            'complete' => true,
            'deps' => [],
            'external' => false,
        ];
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
            'alternatives' => ['?'],
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
        $alternatives = [];
        foreach ($left['alternatives'] as $leftAlternative) {
            foreach ($right['alternatives'] as $rightAlternative) {
                $alternatives[] = $leftAlternative . $rightAlternative;
                if (count($alternatives) > self::MAX_SQL_ALTERNATIVES) {
                    return $this->unknownValue($dependencies, true);
                }
            }
        }
        $alternatives = array_values(array_unique($alternatives));
        return [
            'template' => $alternatives[0],
            'alternatives' => $alternatives,
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
        $dependencies = array_values(array_unique(array_merge($left['deps'], $right['deps'])));
        sort($dependencies);
        $alternatives = array_values(array_unique(array_merge(
            $left['alternatives'],
            $right['alternatives'],
        )));
        if (count($alternatives) > self::MAX_SQL_ALTERNATIVES) {
            return $this->unknownValue($dependencies, true);
        }

        return [
            'template' => $alternatives[0],
            'alternatives' => $alternatives,
            'complete' => $left['complete'] && $right['complete'],
            'deps' => $dependencies,
            'external' => $left['external'] || $right['external'],
        ];
    }

    /** @param SqlValue $value @return 'ddl'|'safe'|'unknown' */
    private function sqlKind(array $value): string
    {
        foreach ($value['alternatives'] as $alternative) {
            $versionedBodies = $this->versionedCommentBodies($alternative);
            if ($versionedBodies === null) {
                return 'ddl';
            }
            foreach ($versionedBodies as $body) {
                $body = (string) preg_replace('/\A\s*\d{5,6}\s*/', '', $body, 1);
                foreach ($this->splitSqlStatements($body) as $payloadStatement) {
                    $payloadSql = $this->stripLeadingSqlComments($payloadStatement);
                    if ($payloadSql === '') {
                        continue;
                    }
                    if (preg_match('/^(?:CREATE|DROP|ALTER|TRUNCATE|RENAME|GRANT|REVOKE)\b/i', $payloadSql) === 1) {
                        return 'ddl';
                    }
                    if (preg_match(
                        '/^(?:SELECT|INSERT|UPDATE|DELETE|REPLACE|WITH|SET|SHOW|DESCRIBE|DESC|EXPLAIN|CALL|DO|USE|START|COMMIT|ROLLBACK|SAVEPOINT|RELEASE)\b/i',
                        $payloadSql,
                    ) !== 1) {
                        return 'ddl';
                    }
                }
            }
            $statements = $this->splitSqlStatements($alternative);
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
                if (preg_match('/^DELIMITER(?:\s|\z)/i', $sql) === 1) {
                    return 'ddl';
                }
                if (preg_match('/^(?:CREATE|DROP|ALTER|TRUNCATE|RENAME|GRANT|REVOKE)\b/i', $sql) === 1) {
                    return 'ddl';
                }
            }
        }

        return $value['complete'] ? 'safe' : 'unknown';
    }

    /**
     * Extrae comentarios ejecutables MySQL fuera de literales y comentarios ordinarios.
     * Un marcador sin cierre es inválido y se rechaza fail-closed.
     *
     * @return list<string>|null
     */
    private function versionedCommentBodies(string $sql): ?array
    {
        $bodies = [];
        $quote = null;
        $lineComment = false;
        $blockComment = false;
        $length = strlen($sql);
        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';
            $afterNext = $index + 2 < $length ? $sql[$index + 2] : '';
            if ($lineComment) {
                if ($character === "\n") {
                    $lineComment = false;
                }
                continue;
            }
            if ($blockComment) {
                if ($character === '*' && $next === '/') {
                    $index++;
                    $blockComment = false;
                }
                continue;
            }
            if ($quote !== null) {
                if ($character === '\\' && $next !== '') {
                    $index++;
                    continue;
                }
                if ($character === $quote) {
                    if ($next === $quote) {
                        $index++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                continue;
            }
            $startsDashComment = $character === '-' && $next === '-'
                && $afterNext !== '' && ord($afterNext) <= 32;
            if ($startsDashComment || $character === '#') {
                $lineComment = true;
                if ($startsDashComment) {
                    $index++;
                }
                continue;
            }
            if ($character !== '/' || $next !== '*') {
                continue;
            }
            if ($afterNext !== '!') {
                $blockComment = true;
                $index++;
                continue;
            }
            $end = strpos($sql, '*/', $index + 3);
            if ($end === false) {
                return null;
            }
            $bodies[] = substr($sql, $index + 3, $end - ($index + 3));
            $index = $end + 1;
        }

        return $bodies;
    }

    /** @param SqlValue $value */
    private function singleCompleteText(array $value): ?string
    {
        return $value['complete'] && count($value['alternatives']) === 1
            ? $value['alternatives'][0]
            : null;
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
            $afterNext = $index + 2 < $length ? $sql[$index + 2] : '';
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
            $startsDashComment = $character === '-' && $next === '-'
                && $afterNext !== '' && ord($afterNext) <= 32;
            if ($startsDashComment || $character === '#') {
                $buffer .= $character;
                if ($startsDashComment) {
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
                '/\A\s*(?:\/\*.*?\*\/\s*|--(?=[\x00-\x20])[^\r\n]*(?:\r?\n|\z)\s*|#[^\r\n]*(?:\r?\n|\z)\s*)/s',
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
                default => $this->looksLikeSqlHelperName($name)
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
            default => $this->looksLikeSqlHelperName($name)
                ? max(0, count($call->args) - 1)
                : null,
        };
    }

    private function looksLikeSqlHelperName(string $name): bool
    {
        return preg_match('/^(?:exec|execute|run|query|prepare).*sql$/', $name) === 1;
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
     * @return array{0: list<Assign>, 1: list<Expr>}
     */
    private function scopeNodes(array|Node $nodes): array
    {
        $assignments = [];
        $calls = [];
        $this->walkScope($nodes, $assignments, $calls);
        return [$assignments, $calls];
    }

    /**
     * @param array<Node>|Node|scalar|null $value
     * @param list<Assign> $assignments
     * @param list<Expr> $calls
     */
    private function walkScope(mixed $value, array &$assignments, array &$calls): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->walkScope($item, $assignments, $calls);
            }
            return;
        }
        if (!$value instanceof Node) {
            return;
        }
        if ($value instanceof FunctionLike) {
            return;
        }
        if ($value instanceof Assign) {
            $assignments[] = $value;
        }
        if ($value instanceof FuncCall || $value instanceof MethodCall || $value instanceof StaticCall) {
            $calls[] = $value;
        }
        foreach ($value->getSubNodeNames() as $subNodeName) {
            $this->walkScope($value->{$subNodeName}, $assignments, $calls);
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
