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

    /**
     * Frontera de lectura BI multiproyecto. No comparte autoridad con guard():
     * clasifica con el mismo catálogo/tokenizer, exige al menos una raíz Project
     * y convierte el conjunto solicitado en una intersección server-side.
     *
     * @param array<mixed> $params
     */
    public function guardForProjects(
        string $sql,
        array $params,
        MultiProjectScope $scope,
        TableScopeCatalog $catalog,
    ): ScopedQuery {
        if (trim($sql) === '') {
            throw new DomainException('No se puede clasificar una consulta SQL vacía.');
        }

        $analysis = $this->analyzeAndNormalize($sql, $catalog);
        $sql = $analysis['sql'];
        $tables = [];
        $projectReferences = [];

        foreach ($analysis['references'] as $reference) {
            $tables[] = $reference['table'];
            if ($reference['kind'] === TableScopeKind::Unclassified) {
                throw new DomainException("Tabla sin clasificación de alcance: {$reference['table']}");
            }
            if ($reference['kind'] === TableScopeKind::System) {
                throw new ProjectScopeViolation(
                    "La frontera multiproyecto BI no autoriza tablas System: {$reference['table']}",
                );
            }
            if ($reference['kind'] === TableScopeKind::Project) {
                $projectReferences[] = $reference;
            }
        }
        $tables = array_values(array_unique($tables));

        if ($projectReferences === []) {
            throw new ProjectScopeViolation(
                'La frontera multiproyecto BI exige al menos una raíz Project; Identity-only no está permitida.',
            );
        }
        if ($this->operation($sql) !== 'SELECT') {
            throw new ProjectScopeViolation('La frontera multiproyecto BI solo admite SELECT.');
        }

        foreach ($projectReferences as $reference) {
            $prefixProjectId = $reference['prefixProjectId'];
            if ($prefixProjectId === null) {
                continue;
            }
            if (!$scope->allows($prefixProjectId)) {
                throw new ProjectScopeViolation(
                    "El prefijo de {$reference['originalTable']} resuelve project_id {$prefixProjectId}, fuera del alcance BI.",
                );
            }
            if ($scope->projectIds() !== [$prefixProjectId]) {
                throw new ProjectScopeViolation(
                    'Una raíz legacy-prefixed no puede representar más de un proyecto en la frontera BI.',
                );
            }
        }

        $tokens = $this->tokenize($sql);
        $physicalAliases = [];
        foreach ($projectReferences as $reference) {
            $physicalAliases[$this->projectReferenceKey($reference)] = $reference;
        }
        $derivedSources = $this->derivedProjectAliasSources($tokens, $projectReferences);
        $projectAliases = array_values(array_unique(array_merge(
            array_keys($physicalAliases),
            array_keys($derivedSources),
        )));
        $this->assertSupportedDerivedJoinTypes($tokens);
        $comparisons = $this->projectComparisons($tokens, $projectAliases, $projectReferences, true);

        $anchored = [];
        $relations = [];
        foreach ($comparisons as $comparison) {
            if ($comparison['kind'] === 'list') {
                if ($comparison['alias'] === null) {
                    throw new ProjectScopeViolation('project_id no puede asociarse a una raíz multiproyecto única.');
                }
                $anchored[$comparison['alias']] = true;
                continue;
            }
            if ($comparison['kind'] === 'relation') {
                $relations[] = $comparison;
            }
        }

        if ($anchored === []) {
            $roots = array_values($physicalAliases);
            usort($roots, static function (array $left, array $right): int {
                return ($left['depth'] <=> $right['depth']) ?: ($left['start'] <=> $right['start']);
            });
            [$sql, $params] = $this->injectMultiProjectRoot(
                $sql,
                $params,
                $scope->projectIds(),
                $roots[0],
                $tokens,
            );

            return $this->guardForProjects($sql, $params, $scope, $catalog);
        }

        $propagationEdges = [];
        foreach ($relations as $relation) {
            array_push(
                $propagationEdges,
                ...$this->relationPropagationEdges($relation, $tokens, $projectReferences, $derivedSources),
            );
        }
        do {
            $changed = false;
            foreach ($derivedSources as $derivedAlias => $sourceAlias) {
                if (isset($anchored[$sourceAlias]) && !isset($anchored[$derivedAlias])) {
                    $anchored[$derivedAlias] = true;
                    $changed = true;
                }
            }
            foreach ($propagationEdges as [$source, $destination]) {
                if (isset($anchored[$source]) && !isset($anchored[$destination])) {
                    $anchored[$destination] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        foreach ($physicalAliases as $alias => $reference) {
            if (!isset($anchored[$alias])) {
                throw new ProjectScopeViolation(
                    "La raíz multiproyecto {$reference['alias']} no tiene un project_id autorizado demostrable.",
                );
            }
        }

        [$sql, $params] = $this->rewriteMultiProjectPredicates(
            $sql,
            $params,
            $tokens,
            $comparisons,
            $scope->projectIds(),
            $physicalAliases,
        );

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

    public function isIdentityOnly(string $sql, TableScopeCatalog $catalog): bool
    {
        $references = $this->analyzeAndNormalize($sql, $catalog)['references'];
        if ($references === []) {
            return false;
        }

        foreach ($references as $reference) {
            if ($reference['kind'] !== TableScopeKind::Identity) {
                return false;
            }
        }

        return true;
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
                array_slice($analysis['references'], 1),
            );
            $reanalyzed = $this->analyzeAndNormalize($sql, $catalog);
            $sources = array_values(array_filter(
                array_slice($reanalyzed['references'], 1),
                static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project,
            ));
            if ($sources !== []) {
                $sourceTokens = $this->tokenize($sql);
                if ($this->hasDerivedTable($sourceTokens)) {
                    [$sql, $params] = $this->guardDerivedProjectReferences(
                        $sql,
                        $params,
                        $scope,
                        $sources,
                        $sourceTokens,
                    );
                } else {
                    [$sql, $params] = $this->guardProjectReferences($sql, $params, $scope, $sources);
                }
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
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $sourceReferences
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
        array $sourceReferences,
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
        if ($this->isProjectScopedSourceColumnExpression($tokens, $expression, $sourceReferences)) {
            return [$sql, $params];
        }

        throw new ProjectScopeViolation('INSERT SELECT exige project_id desde placeholder o columna acotada.');
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     */
    private function hasDerivedTable(array $tokens): bool
    {
        foreach ($tokens as $index => $token) {
            if ($token['type'] !== 'word'
                || !in_array(strtoupper($token['value']), ['FROM', 'JOIN'], true)) {
                continue;
            }
            $next = $this->nextSignificantIndex($tokens, $index + 1);
            if ($next !== null && $tokens[$next]['raw'] === '(') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{0: string, 1: int}
     */
    private function derivedAliasAfterClose(array $tokens, int $close): array
    {
        $alias = $this->nextSignificantIndex($tokens, $close + 1);
        if ($alias !== null && $tokens[$alias]['type'] === 'word'
            && strtoupper($tokens[$alias]['value']) === 'AS') {
            $alias = $this->nextSignificantIndex($tokens, $alias + 1);
        }
        if ($alias === null || !$this->isIdentifier($tokens[$alias])) {
            throw new DomainException('La tabla derivada exige un alias demostrable.');
        }

        return [strtolower($tokens[$alias]['value']), $alias];
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $sourceReferences
     * @return array<string, string> alias derivado => alias Project que aporta project_id
     */
    private function derivedProjectAliasSources(array $tokens, array $sourceReferences): array
    {
        $derived = [];
        foreach ($tokens as $index => $token) {
            if ($token['type'] !== 'word'
                || !in_array(strtoupper($token['value']), ['FROM', 'JOIN'], true)) {
                continue;
            }
            $open = $this->nextSignificantIndex($tokens, $index + 1);
            if ($open === null || $tokens[$open]['raw'] !== '(') {
                continue;
            }
            $select = $this->nextSignificantIndex($tokens, $open + 1);
            if ($select === null || $tokens[$select]['type'] !== 'word'
                || strtoupper($tokens[$select]['value']) !== 'SELECT') {
                continue;
            }
            $close = $this->matchingClose($tokens, $open);
            [$derivedAlias] = $this->derivedAliasAfterClose($tokens, $close);
            if (isset($derived[$derivedAlias])) {
                throw new ProjectScopeViolation("Alias de tabla derivada ambiguo: {$derivedAlias}");
            }

            $subqueryDepth = $tokens[$select]['depth'];
            $from = $this->findKeywordAtDepth($tokens, 'FROM', $subqueryDepth, $select + 1);
            if ($from === null || $from >= $close) {
                throw new DomainException("La tabla derivada {$derivedAlias} exige un FROM demostrable.");
            }
            $directProjectReferences = array_values(array_filter(
                $sourceReferences,
                static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project
                    && $reference['depth'] === $subqueryDepth
                    && $reference['start'] > $tokens[$open]['start']
                    && $reference['end'] < $tokens[$close]['end'],
            ));
            if ($directProjectReferences === []) {
                continue;
            }

            $expressions = $this->splitRangeByComma(
                $tokens,
                $select + 1,
                $from - 1,
                $subqueryDepth,
            );
            foreach ($expressions as $expressionRange) {
                $expression = $this->significantTokensInRange($tokens, $expressionRange);
                if ($expression !== [] && $tokens[$expression[0]]['type'] === 'word'
                    && strtoupper($tokens[$expression[0]]['value']) === 'DISTINCT') {
                    array_shift($expression);
                }
                if (count($expression) === 1
                    && $this->isIdentifier($tokens[$expression[0]])
                    && strtolower($tokens[$expression[0]]['value']) === 'project_id'
                    && count($directProjectReferences) === 1) {
                    $derived[$derivedAlias] = $this->projectReferenceKey($directProjectReferences[0]);
                    break;
                }
                if (count($expression) === 3
                    && $this->isIdentifier($tokens[$expression[0]])
                    && $tokens[$expression[1]]['raw'] === '.'
                    && $this->isIdentifier($tokens[$expression[2]])
                    && strtolower($tokens[$expression[2]]['value']) === 'project_id') {
                    $sourceAlias = strtolower($tokens[$expression[0]]['value']);
                    foreach ($directProjectReferences as $reference) {
                        if (strtolower($reference['alias']) === $sourceAlias) {
                            $derived[$derivedAlias] = $this->projectReferenceKey($reference);
                            break 2;
                        }
                    }
                }
            }
        }

        return $derived;
    }

    /**
     * Las formas derivadas admitidas son deliberadamente más estrictas que una tabla simple:
     * no se inyecta scope dentro de subqueries. Cada raíz Project debe demostrar un placeholder
     * canónico o una relación project_id con una raíz ya acotada.
     *
     * @param array<mixed> $params
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function guardDerivedProjectReferences(
        string $sql,
        array $params,
        ProjectScope $scope,
        array $references,
        array $tokens,
    ): array {
        $physicalAliases = [];
        foreach ($references as $reference) {
            $physicalAliases[$this->projectReferenceKey($reference)] = true;
        }

        $derivedSources = $this->derivedProjectAliasSources($tokens, $references);
        $projectAliases = array_values(array_unique(array_merge(
            array_keys($physicalAliases),
            array_keys($derivedSources),
        )));
        $this->assertSupportedDerivedJoinTypes($tokens);
        $comparisons = $this->projectComparisons($tokens, $projectAliases, $references);
        $anchored = [];
        $propagationEdges = [];
        foreach ($comparisons as $comparison) {
            if ($comparison['kind'] === 'placeholder') {
                if ($comparison['alias'] === null) {
                    throw new ProjectScopeViolation('project_id no puede asociarse a una raíz derivada única.');
                }
                $this->assertScopePlaceholder(
                    $comparison['placeholder'],
                    $tokens,
                    $params,
                    $scope->projectId(),
                );
                $anchored[$comparison['alias']] = true;
                continue;
            }
            array_push(
                $propagationEdges,
                ...$this->relationPropagationEdges($comparison, $tokens, $references, $derivedSources),
            );
        }

        do {
            $changed = false;
            foreach ($derivedSources as $derivedAlias => $sourceAlias) {
                if (isset($anchored[$sourceAlias]) && !isset($anchored[$derivedAlias])) {
                    $anchored[$derivedAlias] = true;
                    $changed = true;
                }
            }
            foreach ($propagationEdges as [$source, $destination]) {
                if (isset($anchored[$source]) && !isset($anchored[$destination])) {
                    $anchored[$destination] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        foreach (array_keys($physicalAliases) as $alias) {
            if (!isset($anchored[$alias])) {
                throw new ProjectScopeViolation("La raíz derivada {$alias} no tiene un project_id canónico demostrable.");
            }
        }

        return [$sql, $params];
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     */
    private function assertSupportedDerivedJoinTypes(array $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if ($token['type'] === 'word' && strtoupper($token['value']) === 'JOIN'
                && $this->joinType($tokens, $index) === 'FULL') {
                throw new ProjectScopeViolation('FULL OUTER JOIN no está soportado por el gate de proyecto.');
            }
        }
    }

    /**
     * @param array{kind: string, alias: string|null, rightAlias?: string, placeholder?: int, predicate: string, lhsStart: int, lhsEnd: int} $comparison
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @param array<string, string> $derivedSources
     * @return list<array{0: string, 1: string}> aristas dirigidas origen => destino
     */
    private function relationPropagationEdges(
        array $comparison,
        array $tokens,
        array $references,
        array $derivedSources,
    ): array {
        $left = $comparison['alias'];
        $right = $comparison['rightAlias'];
        if ($left === null) {
            throw new ProjectScopeViolation('Relación project_id sin raíz izquierda demostrable.');
        }
        if ($comparison['predicate'] !== 'ON') {
            return [[$left, $right], [$right, $left]];
        }

        $join = $this->owningJoinForComparison($tokens, $comparison['lhsStart']);
        if ($join === null) {
            throw new ProjectScopeViolation('Relación project_id en ON sin JOIN demostrable.');
        }
        $joinType = $this->joinType($tokens, $join);
        if ($joinType === 'FULL') {
            throw new ProjectScopeViolation('FULL OUTER JOIN no está soportado por el gate de proyecto.');
        }

        $joined = $this->joinedProjectNode($tokens, $join, $references, $derivedSources);
        if ($joined === null) {
            throw new ProjectScopeViolation('No se pudo demostrar la raíz Project del JOIN.');
        }
        if ($joined !== $left && $joined !== $right) {
            return [];
        }

        $other = $joined === $left ? $right : $left;

        return match ($joinType) {
            'LEFT' => [[$other, $joined]],
            'RIGHT' => [[$joined, $other]],
            default => [[$left, $right], [$right, $left]],
        };
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     */
    private function owningJoinForComparison(array $tokens, int $comparisonStart): ?int
    {
        $depth = $tokens[$comparisonStart]['depth'];
        $on = null;
        for ($index = $comparisonStart - 1; $index >= 0; $index--) {
            if ($tokens[$index]['depth'] > $depth) {
                continue;
            }
            if ($tokens[$index]['depth'] < $depth) {
                break;
            }
            if ($tokens[$index]['type'] !== 'word') {
                continue;
            }
            $keyword = strtoupper($tokens[$index]['value']);
            if ($keyword === 'ON') {
                $on = $index;
                break;
            }
            if (in_array($keyword, ['WHERE', 'JOIN', 'FROM', 'SELECT'], true)) {
                return null;
            }
        }
        if ($on === null) {
            return null;
        }

        for ($index = $on - 1; $index >= 0; $index--) {
            if ($tokens[$index]['depth'] > $depth) {
                continue;
            }
            if ($tokens[$index]['depth'] < $depth) {
                break;
            }
            if ($tokens[$index]['type'] === 'word' && strtoupper($tokens[$index]['value']) === 'JOIN') {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     */
    private function joinType(array $tokens, int $join): string
    {
        $type = $this->previousSignificantIndex($tokens, $join - 1);
        if ($type !== null && $tokens[$type]['type'] === 'word'
            && strtoupper($tokens[$type]['value']) === 'OUTER') {
            $type = $this->previousSignificantIndex($tokens, $type - 1);
        }
        if ($type === null || $tokens[$type]['depth'] !== $tokens[$join]['depth']
            || $tokens[$type]['type'] !== 'word') {
            return 'INNER';
        }

        $keyword = strtoupper($tokens[$type]['value']);

        return in_array($keyword, ['LEFT', 'RIGHT', 'FULL'], true) ? $keyword : 'INNER';
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @param array<string, string> $derivedSources
     */
    private function joinedProjectNode(
        array $tokens,
        int $join,
        array $references,
        array $derivedSources,
    ): ?string {
        $source = $this->nextSignificantIndex($tokens, $join + 1);
        if ($source === null) {
            return null;
        }
        if ($tokens[$source]['raw'] === '(') {
            $close = $this->matchingClose($tokens, $source);
            [$alias] = $this->derivedAliasAfterClose($tokens, $close);

            return isset($derivedSources[$alias]) ? $alias : null;
        }

        foreach ($references as $reference) {
            if ($reference['start'] === $tokens[$source]['start']) {
                return $this->projectReferenceKey($reference);
            }
        }

        return null;
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
                    && ($comparison['alias'] === null || $comparison['alias'] === $rootAlias)
                    && $comparison['predicate'] === 'WHERE',
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
            if ($comparison['kind'] !== 'placeholder' || $comparison['alias'] !== $rootAlias
                || $comparison['predicate'] !== 'WHERE') {
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
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @return list<array{kind: string, alias: string|null, rightAlias?: string, placeholder?: int, predicate: string, lhsStart: int, lhsEnd: int}>
     */
    private function projectComparisons(
        array $tokens,
        array $projectAliases,
        array $references = [],
        bool $allowProjectLists = false,
    ): array
    {
        $comparisons = [];
        foreach ($tokens as $index => $token) {
            if (!$this->isIdentifier($token) || strtolower($token['value']) !== 'project_id') {
                continue;
            }

            [$alias, $lhsStart] = $this->columnQualifier($tokens, $index);
            $qualifiedAlias = $alias;
            if ($references !== []) {
                $alias = $this->resolveProjectComparisonAlias(
                    $tokens,
                    $index,
                    $alias,
                    $projectAliases,
                    $references,
                );
                if ($qualifiedAlias !== null && $alias === null) {
                    // Una tabla Identity puede tener project_id para enlazarse con la raíz
                    // operativa. No crea autoridad y no se analiza como raíz Project.
                    continue;
                }
            }
            if ($alias !== null && !in_array($alias, $projectAliases, true)) {
                continue;
            }
            $operator = $this->nextSignificantIndex($tokens, $index + 1);
            if ($operator === null || !$this->isPredicatePosition($tokens, $operator)) {
                continue;
            }
            if ($tokens[$operator]['raw'] !== '=') {
                $operatorWord = strtoupper($tokens[$operator]['value']);
                if ($allowProjectLists && $operatorWord === 'IN') {
                    $open = $this->nextSignificantIndex($tokens, $operator + 1);
                    if ($open === null || $tokens[$open]['raw'] !== '(') {
                        throw new ProjectScopeViolation('project_id IN exige una lista explícita de placeholders.');
                    }
                    $close = $this->matchingClose($tokens, $open);
                    $placeholders = [];
                    foreach ($this->splitListTokenRanges($tokens, $open, $close) as $range) {
                        $placeholder = $this->singlePlaceholder($tokens, $range);
                        $placeholders[] = $placeholder;
                    }
                    if ($placeholders === []) {
                        throw new ProjectScopeViolation('project_id IN no puede estar vacío.');
                    }
                    $predicate = $this->assertConjunctiveProjectComparison($tokens, $lhsStart, $close);
                    $comparisons[] = [
                        'kind' => 'list',
                        'alias' => $alias,
                        'placeholders' => $placeholders,
                        'predicate' => $predicate,
                        'lhsStart' => $lhsStart,
                        'lhsEnd' => $index,
                        'rangeEnd' => $close,
                    ];
                    continue;
                }
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
                $predicate = $this->assertConjunctiveProjectComparison($tokens, $lhsStart, $right);
                if ($allowProjectLists) {
                    $comparisons[] = [
                        'kind' => 'list',
                        'alias' => $alias,
                        'placeholders' => [$right],
                        'predicate' => $predicate,
                        'lhsStart' => $lhsStart,
                        'lhsEnd' => $index,
                        'rangeEnd' => $right,
                    ];
                    continue;
                }
                $comparisons[] = [
                    'kind' => 'placeholder',
                    'alias' => $alias,
                    'placeholder' => $right,
                    'predicate' => $predicate,
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
                    if ($references !== []) {
                        $rightAlias = $this->resolveProjectComparisonAlias(
                            $tokens,
                            $rightColumn,
                            $rightAlias,
                            $projectAliases,
                            $references,
                        );
                    }
                    if ($alias === null || !in_array($rightAlias, $projectAliases, true)) {
                        throw new ProjectScopeViolation('Relación project_id ambigua o fuera de las tablas de proyecto.');
                    }
                    $predicate = $this->assertConjunctiveProjectComparison($tokens, $lhsStart, $rightColumn);
                    $comparisons[] = [
                        'kind' => 'relation',
                        'alias' => $alias,
                        'rightAlias' => $rightAlias,
                        'predicate' => $predicate,
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
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<int> $placeholderIndices
     * @param array<mixed> $params
     * @return list<int>
     */
    private function multiProjectPlaceholderValues(array $tokens, array $placeholderIndices, array $params): array
    {
        $values = [];
        foreach ($placeholderIndices as $placeholder) {
            $raw = $tokens[$placeholder]['raw'];
            if ($raw === '?') {
                if (!$this->isSequentialArray($params)) {
                    throw new ProjectScopeViolation('Placeholders posicionales exigen parámetros secuenciales.');
                }
                $index = $this->positionalPlaceholderCountBefore($tokens, $tokens[$placeholder]['start']);
                if (!array_key_exists($index, $params)) {
                    throw new ProjectScopeViolation('Falta un parámetro project_id posicional.');
                }
                $value = $params[$index];
            } else {
                $name = ltrim($raw, ':');
                $exists = array_key_exists($name, $params) || array_key_exists($raw, $params);
                if (!$exists) {
                    throw new ProjectScopeViolation("Falta el parámetro project_id {$raw}.");
                }
                $value = $params[$name] ?? $params[$raw];
            }
            if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value <= 0) {
                throw new ProjectScopeViolation('Los filtros project_id solo aceptan enteros positivos.');
            }
            $values[(int) $value] = (int) $value;
        }

        return array_values($values);
    }

    /**
     * @param list<int> $scopeIds
     * @param array<string, array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $physicalAliases
     * @param array<mixed> $params
     * @param list<array<string, mixed>> $comparisons
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function rewriteMultiProjectPredicates(
        string $sql,
        array $params,
        array $tokens,
        array $comparisons,
        array $scopeIds,
        array $physicalAliases,
    ): array {
        $lists = array_values(array_filter(
            $comparisons,
            static fn(array $comparison): bool => $comparison['kind'] === 'list',
        ));
        if ($lists === []) {
            return [$sql, $params];
        }

        $allPlaceholders = array_values(array_filter(
            $tokens,
            static fn(array $token): bool => $token['type'] === 'placeholder',
        ));
        $hasPositional = array_filter($allPlaceholders, static fn(array $token): bool => $token['raw'] === '?') !== [];
        $hasNamed = array_filter($allPlaceholders, static fn(array $token): bool => $token['raw'] !== '?') !== [];
        if ($hasPositional && $hasNamed) {
            throw new ProjectScopeViolation('No se pueden mezclar placeholders posicionales y nombrados.');
        }

        $replacements = [];
        $positionalChanges = [];
        $namedScopeParams = [];
        $namedCounter = 0;
        foreach ($lists as $comparison) {
            $aliasKey = $comparison['alias'];
            if (!is_string($aliasKey) || !isset($physicalAliases[$aliasKey])) {
                throw new ProjectScopeViolation('No se pudo resolver la raíz física del filtro project_id.');
            }
            $requestedIds = $this->multiProjectPlaceholderValues(
                $tokens,
                $comparison['placeholders'],
                $params,
            );
            $allowedIds = array_values(array_intersect($scopeIds, $requestedIds));
            $sqlAlias = $physicalAliases[$aliasKey]['alias'];

            if ($hasNamed) {
                $placeholderSql = [];
                foreach ($allowedIds as $projectId) {
                    do {
                        $name = '__scope_project_' . $namedCounter++;
                    } while (array_key_exists($name, $params) || array_key_exists(':' . $name, $params));
                    $placeholderSql[] = ':' . $name;
                    $namedScopeParams[$name] = $projectId;
                }
            } else {
                $placeholderSql = array_fill(0, count($allowedIds), '?');
                $positions = array_map(
                    fn(int $placeholder): int => $this->positionalPlaceholderCountBefore(
                        $tokens,
                        $tokens[$placeholder]['start'],
                    ),
                    $comparison['placeholders'],
                );
                $positionalChanges[] = [
                    'position' => min($positions),
                    'count' => count($positions),
                    'values' => $allowedIds,
                ];
            }

            $condition = $allowedIds === []
                ? $sqlAlias . '.project_id IN (NULL)'
                : $sqlAlias . '.project_id IN (' . implode(', ', $placeholderSql) . ')';
            $replacements[] = [
                'start' => $tokens[$comparison['lhsStart']]['start'],
                'end' => $tokens[$comparison['rangeEnd']]['end'],
                'text' => $condition,
            ];
        }

        $sql = $this->replaceRanges($sql, $replacements);
        if (!$hasNamed) {
            usort($positionalChanges, static fn(array $left, array $right): int => $right['position'] <=> $left['position']);
            foreach ($positionalChanges as $change) {
                array_splice($params, $change['position'], $change['count'], $change['values']);
            }
            return [$sql, $params];
        }

        $originalNamedParams = [];
        foreach ($params as $name => $value) {
            $originalNamedParams[ltrim((string) $name, ':')] = $value;
        }
        $rewrittenParams = [];
        foreach ($this->tokenize($sql) as $token) {
            if ($token['type'] !== 'placeholder' || $token['raw'] === '?') {
                continue;
            }
            $name = ltrim($token['raw'], ':');
            if (array_key_exists($name, $rewrittenParams)) {
                continue;
            }
            if (array_key_exists($name, $namedScopeParams)) {
                $rewrittenParams[$name] = $namedScopeParams[$name];
                continue;
            }
            if (!array_key_exists($name, $originalNamedParams)) {
                throw new ProjectScopeViolation("Falta el parámetro nombrado :{$name} tras reescribir el scope.");
            }
            $rewrittenParams[$name] = $originalNamedParams[$name];
        }

        return [$sql, $rewrittenParams];
    }

    /**
     * @param list<int> $projectIds
     * @param array<mixed> $params
     * @param array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null} $root
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{string, array<mixed>}
     */
    private function injectMultiProjectRoot(
        string $sql,
        array $params,
        array $projectIds,
        array $root,
        array $tokens,
    ): array {
        $rootToken = $this->findTokenAt($tokens, $root['start']);
        $where = $this->findKeywordAtDepth($tokens, 'WHERE', $root['depth'], $rootToken + 1);
        $hasNamed = array_filter(
            $tokens,
            static fn(array $token): bool => $token['type'] === 'placeholder' && $token['raw'] !== '?',
        ) !== [];
        $hasPositional = array_filter(
            $tokens,
            static fn(array $token): bool => $token['type'] === 'placeholder' && $token['raw'] === '?',
        ) !== [];
        if ($hasNamed && $hasPositional) {
            throw new ProjectScopeViolation('No se pueden mezclar placeholders posicionales y nombrados.');
        }

        if ($hasNamed || !$this->isSequentialArray($params)) {
            $scopeParams = [];
            $placeholders = [];
            $counter = 0;
            foreach ($projectIds as $projectId) {
                do {
                    $name = '__scope_project_' . $counter++;
                } while (array_key_exists($name, $params) || array_key_exists(':' . $name, $params));
                $scopeParams[$name] = $projectId;
                $placeholders[] = ':' . $name;
            }
            $condition = $root['alias'] . '.project_id IN (' . implode(', ', $placeholders) . ')';
            $params = $scopeParams + $params;
        } else {
            $condition = $root['alias'] . '.project_id IN ('
                . implode(', ', array_fill(0, count($projectIds), '?')) . ')';
        }

        if ($where !== null) {
            $offset = $tokens[$where]['end'];
            $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $offset);
            $boundary = $this->findStatementBoundary($tokens, $where + 1, $root['depth']);
            if ($this->hasDisjunctionAtDepthBetween($tokens, $root['depth'], $where + 1, $boundary)) {
                $bodyStart = $offset;
                while ($bodyStart < strlen($sql) && ctype_space($sql[$bodyStart])) {
                    $bodyStart++;
                }
                $bodyEnd = $boundary === null ? strlen($sql) : $tokens[$boundary]['start'];
                $body = rtrim(substr($sql, $bodyStart, $bodyEnd - $bodyStart));
                $tail = substr($sql, $bodyEnd);
                $tailPrefix = $tail !== '' && !ctype_space($tail[0]) ? ' ' : '';
                $sql = substr($sql, 0, $offset) . ' ' . $condition . ' AND (' . $body . ')' . $tailPrefix . $tail;
            } else {
                $sql = substr($sql, 0, $offset) . ' ' . $condition . ' AND' . substr($sql, $offset);
            }
            if (!$hasNamed && $this->isSequentialArray($params)) {
                array_splice($params, $paramIndex, 0, $projectIds);
            }
            return [$sql, $params];
        }

        $boundary = $this->findStatementBoundary($tokens, $rootToken + 1, $root['depth']);
        $offset = $boundary === null ? strlen($sql) : $tokens[$boundary]['start'];
        $paramIndex = $this->positionalPlaceholderCountBefore($tokens, $offset);
        $prefix = $offset > 0 && !ctype_space($sql[$offset - 1]) ? ' ' : '';
        $suffix = $offset < strlen($sql) && !ctype_space($sql[$offset]) ? ' ' : '';
        $sql = substr($sql, 0, $offset) . $prefix . 'WHERE ' . $condition . $suffix . substr($sql, $offset);
        if (!$hasNamed && $this->isSequentialArray($params)) {
            array_splice($params, $paramIndex, 0, $projectIds);
        }
        return [$sql, $params];
    }

    /**
     * @param array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null} $reference
     */
    private function projectReferenceKey(array $reference): string
    {
        return strtolower($reference['alias']) . '@' . $reference['start'];
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<string> $projectAliases
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     */
    private function resolveProjectComparisonAlias(
        array $tokens,
        int $column,
        ?string $sqlAlias,
        array $projectAliases,
        array $references,
    ): ?string {
        if ($sqlAlias === null) {
            return $this->inferUnqualifiedProjectAlias($tokens, $column, $references);
        }

        $candidates = $this->projectReferencesInSelect($tokens, $column, $references, $sqlAlias);
        if (count($candidates) === 1) {
            return $this->projectReferenceKey($candidates[0]);
        }
        if ($candidates !== []) {
            throw new ProjectScopeViolation("Alias project_id ambiguo dentro del SELECT: {$sqlAlias}");
        }

        // Una subconsulta correlacionada puede enlazar su raíz Project con una raíz visible del
        // SELECT padre (b.project_id = a.project_id). projectReferencesInSelect() excluye al padre
        // deliberadamente para resolver columnas no calificadas; para un alias explícito sí es
        // seguro buscar hacia afuera y elegir el nivel envolvente más cercano.
        $columnDepth = $tokens[$column]['depth'];
        $columnStart = $tokens[$column]['start'];
        $outerCandidates = array_values(array_filter(
            $references,
            static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project
                && strtolower($reference['alias']) === $sqlAlias
                && $reference['depth'] < $columnDepth
                && $reference['start'] < $columnStart,
        ));
        if ($outerCandidates !== []) {
            $nearestDepth = max(array_column($outerCandidates, 'depth'));
            $nearest = array_values(array_filter(
                $outerCandidates,
                static fn(array $reference): bool => $reference['depth'] === $nearestDepth,
            ));
            if (count($nearest) === 1) {
                return $this->projectReferenceKey($nearest[0]);
            }
            throw new ProjectScopeViolation("Alias project_id ambiguo en SELECT correlacionado: {$sqlAlias}");
        }

        return in_array($sqlAlias, $projectAliases, true) ? $sqlAlias : null;
    }

    /**
     * Resuelve un project_id no calificado solo dentro del SELECT más cercano y únicamente
     * cuando allí existe una sola raíz física Project. Los hermanos y padres no cuentan.
     *
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     */
    private function inferUnqualifiedProjectAlias(array $tokens, int $column, array $references): ?string
    {
        $references = $this->projectReferencesInSelect($tokens, $column, $references);

        return count($references) === 1 ? $this->projectReferenceKey($references[0]) : null;
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $references
     * @return list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}>
     */
    private function projectReferencesInSelect(
        array $tokens,
        int $column,
        array $references,
        ?string $sqlAlias = null,
    ): array {
        $rangeStart = 0;
        $rangeEnd = PHP_INT_MAX;

        for ($index = $column - 1; $index >= 0; $index--) {
            if ($tokens[$index]['raw'] !== '(' || $tokens[$index]['depth'] >= $tokens[$column]['depth']) {
                continue;
            }
            $select = $this->nextSignificantIndex($tokens, $index + 1);
            if ($select === null || $tokens[$select]['type'] !== 'word'
                || strtoupper($tokens[$select]['value']) !== 'SELECT') {
                continue;
            }
            $close = $this->matchingClose($tokens, $index);
            if ($close <= $column) {
                continue;
            }
            $rangeStart = $tokens[$index]['start'];
            $rangeEnd = $tokens[$close]['end'];
            break;
        }

        return array_values(array_filter(
            $references,
            static fn(array $reference): bool => $reference['kind'] === TableScopeKind::Project
                && $reference['depth'] === $tokens[$column]['depth']
                && $reference['start'] > $rangeStart
                && $reference['end'] < $rangeEnd
                && ($sqlAlias === null || strtolower($reference['alias']) === $sqlAlias),
        ));
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
            if ($this->hasDisjunctionAtDepthBetween($tokens, $root['depth'], $where + 1, $boundary)) {
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
        $commonTableExpressions = $this->commonTableExpressions($tokens)['aliases'];

        foreach ($tokens as $index => $token) {
            if ($token['type'] !== 'word') {
                continue;
            }
            $keyword = strtoupper($token['value']);
            if (!in_array($keyword, ['FROM', 'JOIN', 'UPDATE', 'INTO'], true)) {
                continue;
            }
            if ($keyword === 'UPDATE' && $operation !== 'UPDATE') {
                continue;
            }
            if ($keyword === 'INTO' && !in_array($operation, ['INSERT', 'REPLACE'], true)) {
                continue;
            }

            $tableIndex = $this->nextSignificantIndex($tokens, $index + 1);
            if ($tableIndex === null) {
                throw new DomainException("Forma de tabla derivada no soportada después de {$keyword}.");
            }
            if ($tokens[$tableIndex]['raw'] === '(') {
                if (!in_array($keyword, ['FROM', 'JOIN'], true)) {
                    throw new DomainException("Forma de tabla derivada no soportada después de {$keyword}.");
                }
                $select = $this->nextSignificantIndex($tokens, $tableIndex + 1);
                if ($select === null || $tokens[$select]['type'] !== 'word'
                    || strtoupper($tokens[$select]['value']) !== 'SELECT') {
                    throw new DomainException("La tabla derivada después de {$keyword} exige un SELECT demostrable.");
                }
                $close = $this->matchingClose($tokens, $tableIndex);
                $this->derivedAliasAfterClose($tokens, $close);
                continue;
            }
            if (!$this->isIdentifier($tokens[$tableIndex])) {
                throw new DomainException("Identificador de tabla no demostrable después de {$keyword}.");
            }
            $tableName = strtolower($tokens[$tableIndex]['value']);
            foreach ($commonTableExpressions as $cte) {
                if ($tableName === $cte['alias'] && $tokens[$tableIndex]['start'] > $cte['availableAfter']) {
                    continue 2;
                }
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
        $statement = $this->commonTableExpressions($tokens)['statement'];
        if ($statement !== null && $tokens[$statement]['type'] === 'word') {
            return strtoupper($tokens[$statement]['value']);
        }
        throw new DomainException('Operación SQL no demostrable.');
    }

    /**
     * @param list<array{type: string, raw: string, value: string, start: int, end: int, depth: int}> $tokens
     * @return array{aliases: list<array{alias: string, availableAfter: int}>, statement: int|null}
     */
    private function commonTableExpressions(array $tokens): array
    {
        $first = $this->nextSignificantIndex($tokens, 0);
        if ($first === null || $tokens[$first]['type'] !== 'word'
            || strtoupper($tokens[$first]['value']) !== 'WITH') {
            return ['aliases' => [], 'statement' => $first];
        }

        $cursor = $this->nextSignificantIndex($tokens, $first + 1);
        if ($cursor !== null && $tokens[$cursor]['type'] === 'word'
            && strtoupper($tokens[$cursor]['value']) === 'RECURSIVE') {
            throw new DomainException('WITH RECURSIVE no está soportado por el gate de proyecto.');
        }

        $aliases = [];
        while ($cursor !== null) {
            if (!$this->isIdentifier($tokens[$cursor]) || $tokens[$cursor]['depth'] !== 0) {
                throw new DomainException('Alias CTE no demostrable.');
            }
            $alias = strtolower($tokens[$cursor]['value']);
            if (isset($aliases[$alias])) {
                throw new ProjectScopeViolation("Alias CTE ambiguo: {$alias}");
            }

            $as = $this->nextSignificantIndex($tokens, $cursor + 1);
            if ($as === null || $tokens[$as]['type'] !== 'word'
                || strtoupper($tokens[$as]['value']) !== 'AS') {
                throw new DomainException("El CTE {$alias} exige AS seguido de un SELECT demostrable.");
            }
            $open = $this->nextSignificantIndex($tokens, $as + 1);
            if ($open === null || $tokens[$open]['raw'] !== '(') {
                throw new DomainException("El CTE {$alias} exige una subconsulta entre paréntesis.");
            }
            $select = $this->nextSignificantIndex($tokens, $open + 1);
            if ($select === null || $tokens[$select]['type'] !== 'word'
                || strtoupper($tokens[$select]['value']) !== 'SELECT') {
                throw new DomainException("El CTE {$alias} solo admite SELECT.");
            }
            $close = $this->matchingClose($tokens, $open);
            $aliases[$alias] = [
                'alias' => $alias,
                'availableAfter' => $tokens[$close]['end'],
            ];

            $next = $this->nextSignificantIndex($tokens, $close + 1);
            if ($next === null || $tokens[$next]['raw'] !== ',') {
                return ['aliases' => array_values($aliases), 'statement' => $next];
            }
            $cursor = $this->nextSignificantIndex($tokens, $next + 1);
        }

        throw new DomainException('WITH sin sentencia principal demostrable.');
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

    /**
     * @param list<int> $indices
     * @param list<array{table: string, originalTable: string, alias: string, depth: int, start: int, end: int, kind: TableScopeKind, prefixProjectId: int|null}> $sourceReferences
     */
    private function isProjectScopedSourceColumnExpression(array $tokens, array $indices, array $sourceReferences): bool
    {
        if (count($indices) === 1) {
            return count($sourceReferences) === 1
                && $sourceReferences[0]['kind'] === TableScopeKind::Project
                && $this->isIdentifier($tokens[$indices[0]])
                && strtolower($tokens[$indices[0]]['value']) === 'project_id';
        }
        if (count($indices) !== 3
            || !$this->isIdentifier($tokens[$indices[0]])
            || $tokens[$indices[1]]['raw'] !== '.'
            || !$this->isIdentifier($tokens[$indices[2]])
            || strtolower($tokens[$indices[2]]['value']) !== 'project_id') {
            return false;
        }

        $qualifier = strtolower($tokens[$indices[0]]['value']);
        foreach ($sourceReferences as $reference) {
            if ($reference['kind'] === TableScopeKind::Project && strtolower($reference['alias']) === $qualifier) {
                return true;
            }
        }

        return isset($this->derivedProjectAliasSources($tokens, $sourceReferences)[$qualifier]);
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

    private function assertConjunctiveProjectComparison(array $tokens, int $start, int $end): string
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
            if ($tokens[$index]['depth'] > $comparisonDepth) {
                continue;
            }
            if ($tokens[$index]['raw'] === '||') {
                throw new ProjectScopeViolation('project_id bajo OR no demuestra un alcance conjuntivo.');
            }
            if ($tokens[$index]['type'] !== 'word') {
                continue;
            }
            $keyword = strtoupper($tokens[$index]['value']);
            if (in_array($keyword, ['OR', 'XOR'], true)) {
                throw new ProjectScopeViolation('project_id bajo OR/XOR no demuestra un alcance conjuntivo.');
            }
            if ($keyword === 'NOT' && $index < $start) {
                throw new ProjectScopeViolation('project_id negado no demuestra el alcance activo.');
            }
        }

        return strtoupper($tokens[$predicateStart]['value']);
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

    private function hasDisjunctionAtDepthBetween(
        array $tokens,
        int $depth,
        int $start,
        ?int $end,
    ): bool {
        $end ??= count($tokens);
        for ($index = $start; $index < $end; $index++) {
            if ($tokens[$index]['depth'] !== $depth) {
                continue;
            }
            if ($tokens[$index]['raw'] === '||'
                || ($tokens[$index]['type'] === 'word'
                    && in_array(strtoupper($tokens[$index]['value']), ['OR', 'XOR'], true))) {
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
