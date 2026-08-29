<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use DomainException;

final class ProjectSqlGuard
{
    private const TABLE_ALIAS_STOP_WORDS = [
        'AS', 'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'CROSS', 'OUTER', 'FULL',
        'STRAIGHT_JOIN', 'ON', 'SET', 'ORDER', 'GROUP', 'LIMIT', 'HAVING', 'VALUES',
        'USING', 'NATURAL', 'UNION', 'RETURNING', 'FOR', 'LOCK', 'PARTITION', 'USE',
        'FORCE', 'IGNORE', 'ON', 'DUPLICATE',
    ];

    public function __construct(private readonly \Database $database)
    {
    }

    /** @param array<mixed> $params */
    public function guard(
        string $sql,
        array $params,
        ProjectScope|MultiProjectScope|SystemScope|null $scope,
        TableScopeCatalog $catalog,
    ): ScopedQuery {
        if (trim($sql) === '') {
            throw new DomainException('No se puede clasificar una consulta SQL vacía.');
        }

        $analysis = $this->analyzeAndNormalize($sql, $catalog);
        $sql = $analysis['sql'];
        $references = $analysis['references'];
        $tables = [];
        $projectReferences = [];

        foreach ($references as $reference) {
            $tables[] = $reference['table'];
            if ($reference['kind'] === TableScopeKind::Unclassified) {
                throw new DomainException("Tabla sin clasificación de alcance: {$reference['table']}");
            }
            if ($reference['kind'] === TableScopeKind::Project) {
                $projectReferences[] = $reference;
            }
        }
        $tables = array_values(array_unique($tables));

        if ($projectReferences === []) {
            return new ScopedQuery($sql, $params, $tables);
        }
        if ($scope instanceof SystemScope) {
            return new ScopedQuery($sql, $params, $tables);
        }
        if ($scope === null) {
            throw new MissingProjectScope('La consulta a tablas de proyecto exige un ProjectScope activo.');
        }
        if ($scope instanceof MultiProjectScope) {
            throw new ProjectScopeViolation('El gate SQL de un proyecto no acepta MultiProjectScope.');
        }

        foreach ($projectReferences as $reference) {
            $prefixProjectId = $reference['prefixProjectId'];
            if ($prefixProjectId !== null && $prefixProjectId !== $scope->projectId()) {
                throw new ProjectScopeViolation(
                    "El prefijo de {$reference['originalTable']} resuelve project_id {$prefixProjectId}, fuera del alcance {$scope->projectId()}.",
                );
            }
        }

        $operation = $this->operation($sql);
        if ($operation === 'INSERT') {
            [$sql, $params] = $this->guardInsert($sql, $params, $scope, $catalog);
        } elseif (in_array($operation, ['SELECT', 'UPDATE', 'DELETE'], true)) {
            [$sql, $params] = $this->guardProjectReferences($sql, $params, $scope, $projectReferences);
        } else {
            throw new ProjectScopeViolation("Forma SQL no soportada para tablas de proyecto: {$operation}");
        }

        return new ScopedQuery($sql, $params, $tables);
    }

    public function requiresDeferredExecution(string $sql, TableScopeCatalog $catalog): bool
    {
        $analysis = $this->analyzeAndNormalize($sql, $catalog);
        foreach ($analysis['references'] as $reference) {
            if ($reference['kind'] === TableScopeKind::Unclassified) {
                throw new DomainException("Tabla sin clasificación de alcance: {$reference['table']}");
            }
            if ($reference['kind'] === TableScopeKind::Project) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{sql: string, references: list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}>}
     */
    private function analyzeAndNormalize(string $sql, TableScopeCatalog $catalog): array
    {
        $tokens = $this->tokenize($sql);
        $rawReferences = $this->extractTableReferences($tokens);
        $replacements = [];
        $resolved = [];

        foreach ($rawReferences as $reference) {
            $originalTable = $reference['table'];
            $table = $originalTable;
            $prefixProjectId = null;

            try {
                $kind = $catalog->kind($table);
            } catch (DomainException $unknownTable) {
                $prefix = $this->resolveLegacyPrefix($table, $catalog);
                if ($prefix === null) {
                    throw $unknownTable;
                }
                [$table, $prefixProjectId] = $prefix;
                $kind = $catalog->kind($table);
                $replacements[] = [
                    'start' => $reference['start'],
                    'end' => $reference['end'],
                    'text' => $table,
                ];
            }

            $resolved[] = [
                'table' => $table,
                'originalTable' => $originalTable,
                'alias' => $reference['alias'] ?? $table,
                'depth' => $reference['depth'],
                'start' => $reference['start'],
                'end' => $reference['end'],
                'kind' => $kind,
                'prefixProjectId' => $prefixProjectId,
            ];
        }

        if ($replacements !== []) {
            $sql = $this->replaceRanges($sql, $replacements);
            $normalizedReferences = $this->extractTableReferences($this->tokenize($sql));
            if (count($normalizedReferences) !== count($resolved)) {
                throw new ProjectScopeViolation('La normalización de prefijos produjo una forma SQL ambigua.');
            }
            foreach ($resolved as $index => &$reference) {
                $reference['start'] = $normalizedReferences[$index]['start'];
                $reference['end'] = $normalizedReferences[$index]['end'];
                if ($normalizedReferences[$index]['alias'] === null) {
                    $reference['alias'] = $reference['table'];
                }
            }
            unset($reference);
        }

        return ['sql' => $sql, 'references' => $resolved];
    }

    /** @return array{string, int}|null */
    private function resolveLegacyPrefix(string $identifier, TableScopeCatalog $catalog): ?array
    {
        $projectTables = $catalog->projectScopedTables();
        usort($projectTables, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($projectTables as $table) {
            $suffix = '_' . $table;
            if (!str_ends_with(strtolower($identifier), $suffix)) {
                continue;
            }
            $prefix = substr($identifier, 0, -strlen($suffix));
            if ($prefix === '' || preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $prefix) !== 1) {
                return null;
            }
            $projectId = $this->database->resolveProjectIdForPrefix($prefix);
            return $projectId === null ? null : [$table, $projectId];
        }

        return null;
    }

    /**
     * @param array<mixed> $params
     * @return array{string, array<mixed>}
     */
    private function guardInsert(
        string $sql,
        array $params,
        ProjectScope $scope,
        TableScopeCatalog $catalog,
    ): array {
        $analysis = $this->analyzeAndNormalize($sql, $catalog);
        $references = array_values(array_filter(
            $analysis['references'],
            static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project,
        ));
        if ($references === []) {
            return [$sql, $params];
        }

        $target = $references[0];
        $tokens = $this->tokenize($sql);
        $targetToken = $this->findTokenAt($tokens, $target['start']);
        $openColumns = $this->nextSignificantIndex($tokens, $targetToken + 1);
        if ($openColumns === null || $tokens[$openColumns]['raw'] !== '(') {
            throw new ProjectScopeViolation('INSERT de proyecto exige lista explícita de columnas.');
        }
        $closeColumns = $this->matchingClose($tokens, $openColumns);
        $columns = $this->parseIdentifierList($tokens, $openColumns, $closeColumns);
        $projectColumn = array_search('project_id', array_map('strtolower', $columns), true);

        $sourceKeyword = $this->nextSignificantIndex($tokens, $closeColumns + 1);
        if ($sourceKeyword === null || $tokens[$sourceKeyword]['type'] !== 'word') {
            throw new ProjectScopeViolation('INSERT de proyecto sin VALUES o SELECT demostrable.');
        }

        $keyword = strtoupper($tokens[$sourceKeyword]['value']);
        if ($keyword === 'VALUES') {
            return $this->guardInsertValues(
                $sql,
                $params,
                $scope,
                $tokens,
                $openColumns,
                $closeColumns,
                $sourceKeyword,
                $projectColumn,
            );
        }
        if ($keyword === 'SELECT') {
            [$sql, $params] = $this->guardInsertSelectTarget(
                $sql,
                $params,
                $scope,
                $tokens,
                $openColumns,
                $closeColumns,
                $sourceKeyword,
                $projectColumn,
            );
            $reanalyzed = $this->analyzeAndNormalize($sql, $catalog);
            $sources = array_values(array_filter(
                array_slice($reanalyzed['references'], 1),
                static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project,
            ));
            if ($sources !== []) {
                [$sql, $params] = $this->guardProjectReferences($sql, $params, $scope, $sources);
            }
            return [$sql, $params];
        }

        throw new ProjectScopeViolation("INSERT de proyecto no soporta {$keyword}.");
    }

    /**
     * @param array<mixed> $params
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function guardInsertValues(
        string $sql,
        array $params,
        ProjectScope $scope,
        array $tokens,
        int $openColumns,
        int $closeColumns,
        int $valuesKeyword,
        int|false $projectColumn,
    ): array {
        $openValues = $this->nextSignificantIndex($tokens, $valuesKeyword + 1);
        if ($openValues === null || $tokens[$openValues]['raw'] !== '(') {
            throw new ProjectScopeViolation('VALUES de proyecto exige una tupla explícita.');
        }
        $closeValues = $this->matchingClose($tokens, $openValues);
        $expressions = $this->splitListTokenRanges($tokens, $openValues, $closeValues);
        $afterTuple = $this->nextSignificantIndex($tokens, $closeValues + 1);
        if ($afterTuple !== null && $tokens[$afterTuple]['raw'] === ',') {
            throw new ProjectScopeViolation('INSERT de múltiples filas no tiene una prueba de scope soportada.');
        }

        if ($projectColumn === false) {
            $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $tokens[$openValues]['end']);
            $sql = substr($sql, 0, $tokens[$openValues]['end']) . '?, ' . substr($sql, $tokens[$openValues]['end']);
            $sql = substr($sql, 0, $tokens[$openColumns]['end']) . 'project_id, ' . substr($sql, $tokens[$openColumns]['end']);
            $params = $this->insertScopeParam($params, $paramIndex, $scope->projectId());
            return [$sql, $params];
        }

        if (!isset($expressions[$projectColumn])) {
            throw new ProjectScopeViolation('project_id no tiene valor homólogo en VALUES.');
        }
        $placeholder = $this->singlePlaceholder($tokens, $expressions[$projectColumn]);
        $this->assertScopePlaceholder($placeholder, $tokens, $params, $scope->projectId());
        return [$sql, $params];
    }

    /**
     * @param array<mixed> $params
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function guardInsertSelectTarget(
        string $sql,
        array $params,
        ProjectScope $scope,
        array $tokens,
        int $openColumns,
        int $closeColumns,
        int $selectKeyword,
        int|false $projectColumn,
    ): array {
        $fromKeyword = $this->findKeywordAtDepth($tokens, 'FROM', $tokens[$selectKeyword]['depth'], $selectKeyword + 1);
        if ($fromKeyword === null) {
            throw new ProjectScopeViolation('INSERT SELECT de proyecto exige un FROM demostrable.');
        }
        $selectExpressions = $this->splitRangeByComma($tokens, $selectKeyword + 1, $fromKeyword - 1, $tokens[$selectKeyword]['depth']);

        if ($projectColumn === false) {
            $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $tokens[$selectKeyword]['end']);
            $sql = substr($sql, 0, $tokens[$selectKeyword]['end']) . ' ?, ' . substr($sql, $tokens[$selectKeyword]['end']);
            $sql = substr($sql, 0, $tokens[$openColumns]['end']) . 'project_id, ' . substr($sql, $tokens[$openColumns]['end']);
            $params = $this->insertScopeParam($params, $paramIndex, $scope->projectId());
            return [$sql, $params];
        }

        if (!isset($selectExpressions[$projectColumn])) {
            throw new ProjectScopeViolation('project_id no tiene expresión homóloga en SELECT.');
        }
        $expression = $this->significantTokensInRange($tokens, $selectExpressions[$projectColumn]);
        if (count($expression) === 1 && $tokens[$expression[0]]['type'] === 'placeholder') {
            $this->assertScopePlaceholder($expression[0], $tokens, $params, $scope->projectId());
            return [$sql, $params];
        }
        if ($this->isProjectIdColumnExpression($tokens, $expression)) {
            return [$sql, $params];
        }

        throw new ProjectScopeViolation('INSERT SELECT exige project_id desde placeholder o columna acotada.');
    }

    /**
     * @param array<mixed> $params
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @return array{string, array<mixed>}
     */
    private function guardProjectReferences(string $sql, array $params, ProjectScope $scope, array $references): array
    {
        if ($references === []) {
            return [$sql, $params];
        }

        usort($references, static function (array $left, array $right): int {
            return ($left['depth'] <=> $right['depth']) ?: ($left['start'] <=> $right['start']);
        });

        $aliases = [];
        foreach ($references as $reference) {
            $alias = strtolower($reference['alias']);
            if (isset($aliases[$alias])) {
                throw new ProjectScopeViolation("Alias de tabla de proyecto ambiguo: {$reference['alias']}");
            }
            $aliases[$alias] = true;
        }

        $tokens = $this->tokenize($sql);
        $comparisons = $this->projectComparisons($tokens, array_keys($aliases));
        $root = $references[0];
        $rootAlias = strtolower($root['alias']);

        if (count($references) === 1) {
            $matching = array_values(array_filter(
                $comparisons,
                static fn(array $comparison): bool => $comparison['kind'] === 'placeholder'
                    && ($comparison['alias'] === null || $comparison['alias'] === $rootAlias),
            ));
            if ($matching === []) {
                return $this->injectRootScope($sql, $params, $scope->projectId(), $root, $tokens);
            }

            $replacements = [];
            foreach ($matching as $comparison) {
                $this->assertScopePlaceholder($comparison['placeholder'], $tokens, $params, $scope->projectId());
                $replacements[] = [
                    'start' => $tokens[$comparison['lhsStart']]['start'],
                    'end' => $tokens[$comparison['lhsEnd']]['end'],
                    'text' => $root['alias'] . '.project_id',
                ];
            }
            return [$this->replaceRanges($sql, $replacements), $params];
        }

        $rootScoped = false;
        foreach ($comparisons as $comparison) {
            if ($comparison['kind'] !== 'placeholder' || $comparison['alias'] !== $rootAlias) {
                continue;
            }
            $this->assertScopePlaceholder($comparison['placeholder'], $tokens, $params, $scope->projectId());
            $rootScoped = true;
        }
        if (!$rootScoped) {
            throw new ProjectScopeViolation("La raíz {$root['alias']} no tiene {$root['alias']}.project_id = ? canónico.");
        }

        foreach (array_slice($references, 1) as $reference) {
            $alias = strtolower($reference['alias']);
            $scoped = false;
            foreach ($comparisons as $comparison) {
                if ($comparison['kind'] === 'placeholder' && $comparison['alias'] === $alias) {
                    $this->assertScopePlaceholder($comparison['placeholder'], $tokens, $params, $scope->projectId());
                    $scoped = true;
                }
                if ($comparison['kind'] === 'relation') {
                    $pair = [$comparison['alias'], $comparison['rightAlias']];
                    if (in_array($alias, $pair, true) && in_array($rootAlias, $pair, true)) {
                        $scoped = true;
                    }
                }
            }
            if (!$scoped) {
                throw new ProjectScopeViolation("La tabla {$reference['alias']} no está relacionada por project_id con {$root['alias']}.");
            }
        }

        return [$sql, $params];
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<string> $projectAliases
     * @return list<array{kind: string, alias: string|null, rightAlias?: string, placeholder?: int, lhsStart: int, lhsEnd: int}>
     */
    private function projectComparisons(array $tokens, array $projectAliases): array
    {
        $comparisons = [];
        foreach ($tokens as $index => $token) {
            if (!$this->isIdentifier($token) || strtolower($token['value']) !== 'project_id') {
                continue;
            }

            [$alias, $lhsStart] = $this->columnQualifier($tokens, $index);
            if ($alias !== null && !in_array($alias, $projectAliases, true)) {
                continue;
            }
            $operator = $this->nextSignificantIndex($tokens, $index + 1);
            if ($operator === null || !$this->isPredicatePosition($tokens, $operator)) {
                continue;
            }
            if ($tokens[$operator]['raw'] !== '=') {
                $operatorWord = strtoupper($tokens[$operator]['value']);
                if (in_array($operatorWord, ['IN', 'IS', 'LIKE', 'BETWEEN'], true)
                    || in_array($tokens[$operator]['raw'], ['<=>', '<', '>', '<=', '>=', '<>'], true)) {
                    throw new ProjectScopeViolation('Solo project_id = ? o una relación entre aliases está soportado.');
                }
                continue;
            }

            $right = $this->nextSignificantIndex($tokens, $operator + 1);
            if ($right === null) {
                throw new ProjectScopeViolation('Comparación project_id incompleta.');
            }
            if ($tokens[$right]['type'] === 'placeholder') {
                $this->assertConjunctiveProjectComparison($tokens, $lhsStart, $right);
                $comparisons[] = [
                    'kind' => 'placeholder',
                    'alias' => $alias,
                    'placeholder' => $right,
                    'lhsStart' => $lhsStart,
                    'lhsEnd' => $index,
                ];
                continue;
            }

            if ($this->isIdentifier($tokens[$right])) {
                $dot = $this->nextSignificantIndex($tokens, $right + 1);
                $rightColumn = $dot === null ? null : $this->nextSignificantIndex($tokens, $dot + 1);
                if ($dot !== null && $rightColumn !== null && $tokens[$dot]['raw'] === '.'
                    && $this->isIdentifier($tokens[$rightColumn])
                    && strtolower($tokens[$rightColumn]['value']) === 'project_id') {
                    $rightAlias = strtolower($tokens[$right]['value']);
                    if ($alias === null || !in_array($rightAlias, $projectAliases, true)) {
                        throw new ProjectScopeViolation('Relación project_id ambigua o fuera de las tablas de proyecto.');
                    }
                    $this->assertConjunctiveProjectComparison($tokens, $lhsStart, $rightColumn);
                    $comparisons[] = [
                        'kind' => 'relation',
                        'alias' => $alias,
                        'rightAlias' => $rightAlias,
                        'lhsStart' => $lhsStart,
                        'lhsEnd' => $index,
                    ];
                    continue;
                }
            }

            throw new ProjectScopeViolation('Los literales o expresiones de project_id no están soportados.');
        }

        return $comparisons;
    }

    /**
     * @param array<mixed> $params
     * @param array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null} $root
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function injectRootScope(string $sql, array $params, int $projectId, array $root, array $tokens): array
    {
        $rootToken = $this->findTokenAt($tokens, $root['start']);
        $where = $this->findKeywordAtDepth($tokens, 'WHERE', $root['depth'], $rootToken + 1);
        $condition = $root['alias'] . '.project_id = ?';

        if ($where !== null) {
            $offset = $tokens[$where]['end'];
            $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $offset);
            $boundary = $this->findStatementBoundary($tokens, $where + 1, $root['depth']);
            if ($this->hasKeywordAtDepthBetween($tokens, 'OR', $root['depth'], $where + 1, $boundary)) {
                $bodyStart = $offset;
                while ($bodyStart < strlen($sql) && ctype_space($sql[$bodyStart])) {
                    $bodyStart++;
                }
                $bodyEnd = $boundary === null ? strlen($sql) : $tokens[$boundary]['start'];
                $body = rtrim(substr($sql, $bodyStart, $bodyEnd - $bodyStart));
                $tail = substr($sql, $bodyEnd);
                $tailPrefix = $tail !== '' && !ctype_space($tail[0]) ? ' ' : '';
                $sql = substr($sql, 0, $offset) . ' ' . $condition . ' AND (' . $body . ')' . $tailPrefix . $tail;
                return [$sql, $this->insertScopeParam($params, $paramIndex, $projectId)];
            }
            $sql = substr($sql, 0, $offset) . ' ' . $condition . ' AND' . substr($sql, $offset);
            return [$sql, $this->insertScopeParam($params, $paramIndex, $projectId)];
        }

        $boundary = $this->findStatementBoundary($tokens, $rootToken + 1, $root['depth']);
        $offset = $boundary === null ? strlen($sql) : $tokens[$boundary]['start'];
        $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $offset);
        $prefix = $offset > 0 && !ctype_space($sql[$offset - 1]) ? ' ' : '';
        $suffix = $offset < strlen($sql) && !ctype_space($sql[$offset]) ? ' ' : '';
        $sql = substr($sql, 0, $offset) . $prefix . 'WHERE ' . $condition . $suffix . substr($sql, $offset);
        return [$sql, $this->insertScopeParam($params, $paramIndex, $projectId)];
    }

    /** @param array<mixed> $params */
    private function assertScopePlaceholder(int $placeholder, array $tokens, array $params, int $projectId): void
    {
        $raw = $tokens[$placeholder]['raw'];
        if ($raw === '?') {
            if (!$this->isSequentialArray($params)) {
                throw new ProjectScopeViolation('Placeholders posicionales exigen parámetros secuenciales.');
            }
            $index = 0;
            foreach ($tokens as $tokenIndex => $token) {
                if ($tokenIndex >= $placeholder) {
                    break;
                }
                if ($token['type'] === 'placeholder' && $token['raw'] === '?') {
                    $index++;
                }
            }
            if (!array_key_exists($index, $params) || !$this->sameProjectId($params[$index], $projectId)) {
                throw new ProjectScopeViolation("El parámetro project_id no coincide con el alcance {$projectId}.");
            }
            return;
        }

        $name = ltrim($raw, ':');
        $exists = array_key_exists($name, $params) || array_key_exists($raw, $params);
        $value = $params[$name] ?? $params[$raw] ?? null;
        if (!$exists || !$this->sameProjectId($value, $projectId)) {
            throw new ProjectScopeViolation("El parámetro {$raw} no coincide con el alcance {$projectId}.");
        }
    }

    private function sameProjectId(mixed $value, int $projectId): bool
    {
        return (is_int($value) && $value === $projectId)
            || (is_string($value) && ctype_digit($value) && (int) $value === $projectId);
    }

    /** @param array<mixed> $params @return array<mixed> */
    private function insertScopeParam(array $params, int $position, int $projectId): array
    {
        if (!$this->isSequentialArray($params)) {
            throw new ProjectScopeViolation('La inyección automática solo soporta parámetros posicionales secuenciales.');
        }
        array_splice($params, $position, 0, [$projectId]);
        return $params;
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return list<array{table: string, alias: string|null, depth: int, start: int, end: int}>
     */
    private function extractTableReferences(array $tokens): array
    {
        $references = [];
        $operation = $this->operationFromTokens($tokens);

        foreach ($tokens as $index => $token) {
            if ($token['type'] !== 'word') {
                continue;
            }
            $keyword = strtoupper($token['value']);
            if (!in_array($keyword, ['FROM', 'JOIN', 'UPDATE', 'INTO'], true)) {
                continue;
            }
            if ($keyword === 'INTO' && $operation !== 'INSERT') {
                continue;
            }

            $tableIndex = $this->nextSignificantIndex($tokens, $index + 1);
            if ($tableIndex === null || $tokens[$tableIndex]['raw'] === '(') {
                throw new DomainException("Forma de tabla derivada no soportada después de {$keyword}.");
            }
            if (!$this->isIdentifier($tokens[$tableIndex])) {
                throw new DomainException("Identificador de tabla no demostrable después de {$keyword}.");
            }
            $dot = $this->nextSignificantIndex($tokens, $tableIndex + 1);
            if ($dot !== null && $tokens[$dot]['raw'] === '.') {
                throw new DomainException('Las tablas calificadas por schema no están soportadas por el gate.');
            }

            $alias = null;
            $candidate = $this->nextSignificantIndex($tokens, $tableIndex + 1);
            if ($candidate !== null && strtoupper($tokens[$candidate]['value']) === 'AS') {
                $candidate = $this->nextSignificantIndex($tokens, $candidate + 1);
                if ($candidate === null || !$this->isIdentifier($tokens[$candidate])) {
                    throw new DomainException('Alias de tabla incompleto.');
                }
                $alias = $tokens[$candidate]['value'];
            } elseif ($candidate !== null && $this->isIdentifier($tokens[$candidate])
                && !in_array(strtoupper($tokens[$candidate]['value']), self::TABLE_ALIAS_STOP_WORDS, true)) {
                $alias = $tokens[$candidate]['value'];
            }

            $afterReference = $candidate;
            if ($candidate !== null && strtoupper($tokens[$candidate]['value']) === 'AS') {
                $afterReference = $this->nextSignificantIndex($tokens, $candidate + 1);
            } elseif ($alias === null) {
                $afterReference = $tableIndex;
            }
            for ($scan = ($afterReference ?? $tableIndex) + 1, $count = count($tokens); $scan < $count; $scan++) {
                if ($tokens[$scan]['depth'] < $tokens[$tableIndex]['depth']) {
                    break;
                }
                if ($tokens[$scan]['depth'] !== $tokens[$tableIndex]['depth']) {
                    continue;
                }
                if ($tokens[$scan]['raw'] === ',') {
                    throw new ProjectScopeViolation('Las listas de tablas separadas por coma no están soportadas.');
                }
                if ($tokens[$scan]['type'] === 'word'
                    && in_array(strtoupper($tokens[$scan]['value']), ['SELECT', 'VALUES', 'WHERE', 'JOIN', 'ON', 'SET', 'ORDER', 'GROUP', 'HAVING', 'LIMIT', 'UNION', 'RETURNING'], true)) {
                    break;
                }
            }

            $references[] = [
                'table' => strtolower($tokens[$tableIndex]['value']),
                'alias' => $alias,
                'depth' => $tokens[$tableIndex]['depth'],
                'start' => $tokens[$tableIndex]['start'],
                'end' => $tokens[$tableIndex]['end'],
            ];
        }

        return $references;
    }

    /**
     * @return list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}>
     */
    private function tokenize(string $sql): array
    {
        $tokens = [];
        $length = strlen($sql);
        $depth = 0;

        for ($offset = 0; $offset < $length;) {
            $start = $offset;
            $char = $sql[$offset];

            if (ctype_space($char)) {
                while ($offset < $length && ctype_space($sql[$offset])) {
                    $offset++;
                }
                $tokens[] = $this->token('space', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === '#' || ($char === '-' && ($sql[$offset + 1] ?? '') === '-' && ctype_space($sql[$offset + 2] ?? ' '))) {
                while ($offset < $length && $sql[$offset] !== "\n") {
                    $offset++;
                }
                $tokens[] = $this->token('comment', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === '/' && ($sql[$offset + 1] ?? '') === '*') {
                $offset += 2;
                $closed = false;
                while ($offset < $length - 1) {
                    if ($sql[$offset] === '*' && $sql[$offset + 1] === '/') {
                        $offset += 2;
                        $closed = true;
                        break;
                    }
                    $offset++;
                }
                if (!$closed) {
                    throw new DomainException('Comentario SQL sin cerrar.');
                }
                $tokens[] = $this->token('comment', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === "'" || $char === '"') {
                $quote = $char;
                $offset++;
                $closed = false;
                while ($offset < $length) {
                    if ($sql[$offset] === '\\') {
                        $offset += 2;
                        continue;
                    }
                    if ($sql[$offset] === $quote) {
                        if (($sql[$offset + 1] ?? '') === $quote) {
                            $offset += 2;
                            continue;
                        }
                        $offset++;
                        $closed = true;
                        break;
                    }
                    $offset++;
                }
                if (!$closed) {
                    throw new DomainException('String SQL sin cerrar.');
                }
                $tokens[] = $this->token('string', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === '`') {
                $offset++;
                $value = '';
                $closed = false;
                while ($offset < $length) {
                    if ($sql[$offset] === '`') {
                        if (($sql[$offset + 1] ?? '') === '`') {
                            $value .= '`';
                            $offset += 2;
                            continue;
                        }
                        $offset++;
                        $closed = true;
                        break;
                    }
                    $value .= $sql[$offset++];
                }
                if (!$closed) {
                    throw new DomainException('Identificador SQL sin cerrar.');
                }
                $tokens[] = [
                    'type' => 'identifier',
                    'raw' => substr($sql, $start, $offset - $start),
                    'value' => $value,
                    'start' => $start,
                    'end' => $offset,
                    'depth' => $depth,
                ];
                continue;
            }
            if ($char === '?') {
                $offset++;
                $tokens[] = $this->token('placeholder', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === ':' && preg_match('/[A-Za-z_]/', $sql[$offset + 1] ?? '') === 1) {
                $offset += 2;
                while ($offset < $length && preg_match('/[A-Za-z0-9_]/', $sql[$offset]) === 1) {
                    $offset++;
                }
                $tokens[] = $this->token('placeholder', $sql, $start, $offset, $depth);
                continue;
            }
            if (preg_match('/[A-Za-z_]/', $char) === 1) {
                $offset++;
                while ($offset < $length && preg_match('/[A-Za-z0-9_$]/', $sql[$offset]) === 1) {
                    $offset++;
                }
                $tokens[] = $this->token('word', $sql, $start, $offset, $depth);
                continue;
            }
            if (ctype_digit($char)) {
                $offset++;
                while ($offset < $length && preg_match('/[0-9.eE+-]/', $sql[$offset]) === 1) {
                    $offset++;
                }
                $tokens[] = $this->token('number', $sql, $start, $offset, $depth);
                continue;
            }
            if ($char === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new DomainException('Paréntesis SQL desbalanceados.');
                }
            }

            $offset++;
            $two = substr($sql, $start, 2);
            $three = substr($sql, $start, 3);
            if (in_array($three, ['<=>'], true)) {
                $offset = $start + 3;
            } elseif (in_array($two, ['<=', '>=', '<>', '!=', '||', '&&', ':='], true)) {
                $offset = $start + 2;
            }
            $tokens[] = $this->token('symbol', $sql, $start, $offset, $depth);
            if ($char === '(') {
                $depth++;
            }
        }

        if ($depth !== 0) {
            throw new DomainException('Paréntesis SQL desbalanceados.');
        }

        return $tokens;
    }

    /** @return array{type: string, raw: string, value: string, start: int, end: int, depth: int} */
    private function token(string $type, string $sql, int $start, int $end, int $depth): array
    {
        $raw = substr($sql, $start, $end - $start);
        return compact('type', 'raw', 'start', 'end', 'depth') + ['value' => $raw];
    }

    /** @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens */
    private function operationFromTokens(array $tokens): string
    {
        foreach ($tokens as $token) {
            if ($token['type'] === 'word') {
                return strtoupper($token['value']);
            }
            if (!in_array($token['type'], ['space', 'comment'], true)) {
                break;
            }
        }
        throw new DomainException('Operación SQL no demostrable.');
    }

    private function operation(string $sql): string
    {
        return $this->operationFromTokens($this->tokenize($sql));
    }

    private function isIdentifier(array $token): bool
    {
        return in_array($token['type'], ['word', 'identifier'], true);
    }

    private function nextSignificantIndex(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if (!in_array($tokens[$index]['type'], ['space', 'comment'], true)) {
                return $index;
            }
        }
        return null;
    }

    private function previousSignificantIndex(array $tokens, int $start): ?int
    {
        for ($index = $start; $index >= 0; $index--) {
            if (!in_array($tokens[$index]['type'], ['space', 'comment'], true)) {
                return $index;
            }
        }
        return null;
    }

    private function findTokenAt(array $tokens, int $offset): int
    {
        foreach ($tokens as $index => $token) {
            if ($token['start'] === $offset) {
                return $index;
            }
        }
        throw new DomainException('No se pudo ubicar un identificador SQL normalizado.');
    }

    private function matchingClose(array $tokens, int $open): int
    {
        $depth = $tokens[$open]['depth'];
        for ($index = $open + 1, $count = count($tokens); $index < $count; $index++) {
            if ($tokens[$index]['raw'] === ')' && $tokens[$index]['depth'] === $depth) {
                return $index;
            }
        }
        throw new DomainException('Paréntesis SQL sin cierre demostrable.');
    }

    /** @return list<string> */
    private function parseIdentifierList(array $tokens, int $open, int $close): array
    {
        $ranges = $this->splitListTokenRanges($tokens, $open, $close);
        $identifiers = [];
        foreach ($ranges as $range) {
            $significant = $this->significantTokensInRange($tokens, $range);
            if (count($significant) !== 1 || !$this->isIdentifier($tokens[$significant[0]])) {
                throw new ProjectScopeViolation('Lista de columnas INSERT ambigua.');
            }
            $identifiers[] = $tokens[$significant[0]]['value'];
        }
        return $identifiers;
    }

    /** @return list<array{0: int, 1: int}> */
    private function splitListTokenRanges(array $tokens, int $open, int $close): array
    {
        return $this->splitRangeByComma($tokens, $open + 1, $close - 1, $tokens[$open]['depth'] + 1);
    }

    /** @return list<array{0: int, 1: int}> */
    private function splitRangeByComma(array $tokens, int $start, int $end, int $depth): array
    {
        if ($start > $end) {
            return [];
        }
        $ranges = [];
        $rangeStart = $start;
        for ($index = $start; $index <= $end; $index++) {
            if ($tokens[$index]['raw'] === ',' && $tokens[$index]['depth'] === $depth) {
                $ranges[] = [$rangeStart, $index - 1];
                $rangeStart = $index + 1;
            }
        }
        $ranges[] = [$rangeStart, $end];
        return $ranges;
    }

    /** @param array{0: int, 1: int} $range @return list<int> */
    private function significantTokensInRange(array $tokens, array $range): array
    {
        $indices = [];
        for ($index = $range[0]; $index <= $range[1]; $index++) {
            if (!in_array($tokens[$index]['type'], ['space', 'comment'], true)) {
                $indices[] = $index;
            }
        }
        return $indices;
    }

    /** @param array{0: int, 1: int} $range */
    private function singlePlaceholder(array $tokens, array $range): int
    {
        $significant = $this->significantTokensInRange($tokens, $range);
        if (count($significant) !== 1 || $tokens[$significant[0]]['type'] !== 'placeholder') {
            throw new ProjectScopeViolation('project_id debe usar un placeholder homólogo.');
        }
        return $significant[0];
    }

    /** @param list<int> $indices */
    private function isProjectIdColumnExpression(array $tokens, array $indices): bool
    {
        if (count($indices) === 1) {
            return $this->isIdentifier($tokens[$indices[0]])
                && strtolower($tokens[$indices[0]]['value']) === 'project_id';
        }
        return count($indices) === 3
            && $this->isIdentifier($tokens[$indices[0]])
            && $tokens[$indices[1]]['raw'] === '.'
            && $this->isIdentifier($tokens[$indices[2]])
            && strtolower($tokens[$indices[2]]['value']) === 'project_id';
    }

    /** @return array{0: string|null, 1: int} */
    private function columnQualifier(array $tokens, int $column): array
    {
        $dot = $this->previousSignificantIndex($tokens, $column - 1);
        if ($dot === null || $tokens[$dot]['raw'] !== '.') {
            return [null, $column];
        }
        $alias = $this->previousSignificantIndex($tokens, $dot - 1);
        if ($alias === null || !$this->isIdentifier($tokens[$alias])) {
            throw new ProjectScopeViolation('Calificador de project_id ambiguo.');
        }
        return [strtolower($tokens[$alias]['value']), $alias];
    }

    private function isPredicatePosition(array $tokens, int $position): bool
    {
        $depth = $tokens[$position]['depth'];
        for ($index = $position - 1; $index >= 0; $index--) {
            if ($tokens[$index]['type'] !== 'word' || $tokens[$index]['depth'] > $depth) {
                continue;
            }
            $keyword = strtoupper($tokens[$index]['value']);
            if (in_array($keyword, ['WHERE', 'ON'], true)) {
                return true;
            }
            if (in_array($keyword, ['SELECT', 'SET', 'VALUES', 'FROM', 'JOIN', 'GROUP', 'ORDER', 'LIMIT', 'UNION', 'RETURNING'], true)) {
                return false;
            }
        }
        return false;
    }

    private function assertConjunctiveProjectComparison(array $tokens, int $start, int $end): void
    {
        $comparisonDepth = $tokens[$start]['depth'];
        $predicateStart = null;
        $predicateDepth = null;

        for ($index = $start - 1; $index >= 0; $index--) {
            if ($tokens[$index]['type'] !== 'word' || $tokens[$index]['depth'] > $comparisonDepth) {
                continue;
            }
            $keyword = strtoupper($tokens[$index]['value']);
            if (in_array($keyword, ['WHERE', 'ON'], true)) {
                $predicateStart = $index;
                $predicateDepth = $tokens[$index]['depth'];
                break;
            }
            if (in_array($keyword, ['SELECT', 'SET', 'VALUES', 'FROM', 'JOIN'], true)) {
                break;
            }
        }
        if ($predicateStart === null || $predicateDepth === null) {
            throw new ProjectScopeViolation('La comparación project_id no está en WHERE u ON.');
        }

        $boundary = $this->findPredicateBoundary($tokens, $end + 1, $predicateDepth);
        for ($index = $predicateStart + 1; $index < ($boundary ?? count($tokens)); $index++) {
            if ($tokens[$index]['type'] !== 'word' || $tokens[$index]['depth'] > $comparisonDepth) {
                continue;
            }
            $keyword = strtoupper($tokens[$index]['value']);
            if ($keyword === 'OR') {
                throw new ProjectScopeViolation('project_id bajo OR no demuestra un alcance conjuntivo.');
            }
            if ($keyword === 'NOT' && $index < $start) {
                throw new ProjectScopeViolation('project_id negado no demuestra el alcance activo.');
            }
        }
    }

    private function findPredicateBoundary(array $tokens, int $start, int $depth): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if ($tokens[$index]['raw'] === ')' && $tokens[$index]['depth'] < $depth) {
                return $index;
            }
            if ($tokens[$index]['depth'] !== $depth || $tokens[$index]['type'] !== 'word') {
                continue;
            }
            if (in_array(strtoupper($tokens[$index]['value']), ['JOIN', 'WHERE', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'UNION', 'RETURNING'], true)) {
                return $index;
            }
        }
        return null;
    }

    private function hasKeywordAtDepthBetween(
        array $tokens,
        string $keyword,
        int $depth,
        int $start,
        ?int $end,
    ): bool {
        $end ??= count($tokens);
        for ($index = $start; $index < $end; $index++) {
            if ($tokens[$index]['type'] === 'word' && $tokens[$index]['depth'] === $depth
                && strtoupper($tokens[$index]['value']) === $keyword) {
                return true;
            }
        }
        return false;
    }

    private function findKeywordAtDepth(array $tokens, string $keyword, int $depth, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if ($tokens[$index]['raw'] === ')' && $tokens[$index]['depth'] < $depth) {
                return null;
            }
            if ($tokens[$index]['type'] === 'word' && $tokens[$index]['depth'] === $depth
                && strtoupper($tokens[$index]['value']) === $keyword) {
                return $index;
            }
        }
        return null;
    }

    private function findStatementBoundary(array $tokens, int $start, int $depth): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if ($tokens[$index]['raw'] === ')' && $tokens[$index]['depth'] < $depth) {
                return $index;
            }
            if ($tokens[$index]['depth'] !== $depth) {
                continue;
            }
            if ($tokens[$index]['raw'] === ';') {
                return $index;
            }
            if ($tokens[$index]['type'] === 'word'
                && in_array(strtoupper($tokens[$index]['value']), ['ORDER', 'GROUP', 'HAVING', 'LIMIT', 'RETURNING', 'FOR', 'UNION'], true)) {
                return $index;
            }
        }
        return null;
    }

    private function positionalPlaceholderCountBefore(array $tokens, int $offset): int
    {
        $count = 0;
        foreach ($tokens as $token) {
            if ($token['start'] >= $offset) {
                break;
            }
            if ($token['type'] === 'placeholder' && $token['raw'] === '?') {
                $count++;
            }
        }
        return $count;
    }

    private function isSequentialArray(array $params): bool
    {
        return $params === [] || array_keys($params) === range(0, count($params) - 1);
    }

    /** @param list<array{start: int, end: int, text: string}> $replacements */
    private function replaceRanges(string $sql, array $replacements): string
    {
        usort($replacements, static fn(array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $replacement) {
            $sql = substr($sql, 0, $replacement['start'])
                . $replacement['text']
                . substr($sql, $replacement['end']);
        }
        return $sql;
    }
}
