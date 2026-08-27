<?php

declare(strict_types=1);

namespace App\Services\Bi;

use Database;
use RuntimeException;

/**
 * Ejecuta una definicion del catalogo (`MetricDictionaryService::getDefinition()`)
 * como una consulta real, aislada por `project_id`, con sentencias preparadas.
 *
 * CT-6 (regla dura): un solo camino de construccion de SQL. No hay SQL libre: toda
 * consulta sale de `SELECT {select} FROM {source} WHERE project_id IN (?) AND
 * {filters}`, con `{select}` y `{filters}` derivados del catalogo por los mismos
 * dos parsers (`buildSelectExpression()`, `parseFilters()`), nunca interpolados a
 * mano en otro punto de la clase.
 *
 * Alcance de Task 2 (Ola 1, Torre de Control piloto): el catalogo real
 * (`MetricDictionaryService::catalog()`) trae `execution_source`, `filters` y
 * `aggregation_policy` con la forma que pide el brief, pero:
 *  - `filters` son fragmentos de prosa SQL-like (`'Titulo=0'`,
 *    `'Semanas_Inicio BETWEEN 0 AND 6'`), no siempre pares columna/operador/valor
 *    parseables por `parseFilter()` (que solo reconoce `=`, `>=`, `<=`, `!=`, `>`,
 *    `<`).
 *  - `aggregation_policy` es casi siempre texto descriptivo para humanos, no una
 *    directiva ejecutable; salvo el caso `formula` con forma `SUM(expr) / COUNT(*)`
 *    (ej. `ps_weekly_fulfillment`), que si se reconoce.
 * Cuando una metrica no encaja en ninguna de las dos formas reconocidas,
 * `buildSelectExpression()` lanza `RuntimeException` en vez de forzar una lectura:
 * esa metrica queda como limitacion conocida, a resolver metrica por metrica en
 * Task 3 (migracion incremental), no en este ejecutor.
 */
final class MetricExecutor
{
    /**
     * Contenido permitido para el numerador de un ratio: un identificador solo
     * (`cumplido`) o una comparacion simple (`PAC=1`). Bloquea cualquier otra cosa
     * que la formula del catalogo pudiera traer.
     */
    private const NUMERATOR_EXPRESSION_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\s*(>=|<=|!=|=|>|<)\s*[A-Za-z0-9_.\']+)?$/';

    private const IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    private const OPERATOR_PATTERN = '/(>=|<=|!=|=|>|<)/';

    public function __construct(
        private readonly Database $db,
        private readonly MetricDictionaryService $dictionary,
    ) {
    }

    public function execute(string $metricKey, MetricScope $scope): MetricResult
    {
        $definition = $this->dictionary->getDefinition($metricKey);
        if ($definition === []) {
            throw new RuntimeException("Metrica desconocida en el catalogo: '{$metricKey}'.");
        }

        $source = $this->assertSafeIdentifier((string) ($definition['execution_source'] ?? ''));
        $filters = $this->parseFilters($definition['filters'] ?? []);
        $selectExpression = $this->buildSelectExpression($definition);

        [$whereSql, $whereParams] = $this->buildWhereClause($scope, $filters);

        $aggregateRow = $this->db
            ->query("SELECT {$selectExpression} FROM {$source} WHERE {$whereSql}", $whereParams)
            ->fetch();
        $aggregateRow = $aggregateRow === false ? [] : $aggregateRow;

        $filasUsadas = (int) ($aggregateRow['denominador'] ?? 0);
        $numerador = $aggregateRow['numerador'] ?? null;

        $presentProjectRows = $this->db
            ->query("SELECT DISTINCT project_id FROM {$source} WHERE {$whereSql}", $whereParams)
            ->fetchAll();
        $presentProjects = array_map(
            static fn (array $row): int => (int) $row['project_id'],
            $presentProjectRows,
        );

        $expectedProjects = $scope->projectIds();
        $obrasIncluidas = count($presentProjects);
        $obrasEsperadas = count($expectedProjects);
        $missingProjects = array_values(array_diff($expectedProjects, $presentProjects));

        $completeness = $this->resolveCompleteness($filasUsadas, $obrasIncluidas, $obrasEsperadas);

        $basis = [
            'obras_incluidas' => $obrasIncluidas,
            'obras_esperadas' => $obrasEsperadas,
            'corte' => $scope->cutoff(),
            'filas_usadas' => $filasUsadas,
        ];

        // Nunca division por cero: sin filas, el valor es null explicito y
        // completeness() ya quedo en 'insuficiente' arriba -- nunca 'completa' con
        // un valor inventado.
        $value = ($filasUsadas > 0 && $numerador !== null)
            ? ((float) $numerador) / $filasUsadas
            : null;

        return new MetricResult(
            $value,
            $basis,
            $completeness,
            $this->buildMissing($completeness, $missingProjects, $filasUsadas),
        );
    }

    /**
     * Deriva la expresion SELECT de `aggregation_policy` (mini-DSL `ratio:<columna>`)
     * o, si no aplica, de `formula` cuando tiene forma `SUM(expr) / COUNT(*)`. Si
     * ninguna de las dos es reconocible, la metrica no es ejecutable todavia.
     */
    private function buildSelectExpression(array $definition): string
    {
        $aggregationPolicy = trim((string) ($definition['aggregation_policy'] ?? ''));
        if (preg_match('/^ratio:([A-Za-z_][A-Za-z0-9_]*)$/', $aggregationPolicy, $match) === 1) {
            return $this->ratioSelectExpression($match[1]);
        }

        $formula = trim((string) ($definition['formula'] ?? ''));
        if (preg_match('/^SUM\(([^()]+)\)\s*\/\s*COUNT\(\*\)$/i', $formula, $match) === 1) {
            return $this->ratioSelectExpression($match[1]);
        }

        $metricKey = (string) ($definition['metric_key'] ?? '(desconocida)');
        throw new RuntimeException(sprintf(
            "Metrica '%s' no es ejecutable con este executor: ni 'aggregation_policy' ('%s') ni 'formula' " .
            "('%s') tienen una forma SQL reconocida. Limitacion conocida, se resuelve metrica por metrica " .
            'en Task 3 (migracion incremental).',
            $metricKey,
            $aggregationPolicy,
            $formula,
        ));
    }

    private function ratioSelectExpression(string $numeratorExpression): string
    {
        if (preg_match(self::NUMERATOR_EXPRESSION_PATTERN, $numeratorExpression) !== 1) {
            throw new RuntimeException("Expresion de numerador no permitida: '{$numeratorExpression}'.");
        }

        return sprintf('SUM(%s) AS numerador, COUNT(*) AS denominador', $numeratorExpression);
    }

    /**
     * @param array<int, mixed> $filters
     * @return list<array{0:string,1:string,2:int|float|string}>
     */
    private function parseFilters(array $filters): array
    {
        $parsed = [];
        foreach ($filters as $filter) {
            $parsed[] = $this->parseFilter((string) $filter);
        }

        return $parsed;
    }

    /**
     * Parte un filtro de catalogo (`'activo = 1'`) en columna/operador/valor,
     * buscando el primer operador entre `>=`, `<=`, `!=`, `=`, `>`, `<` -- los
     * multi-caracter se comprueban antes que los de un solo caracter para no
     * cortar `>=` como `>` seguido de `=` suelto.
     *
     * @return array{0:string,1:string,2:int|float|string}
     */
    private function parseFilter(string $filter): array
    {
        if (preg_match(self::OPERATOR_PATTERN, $filter, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException("Filtro no parseable, sin operador reconocido: '{$filter}'.");
        }

        $operator = $match[1][0];
        $position = (int) $match[1][1];
        $column = $this->assertSafeIdentifier(trim(substr($filter, 0, $position)));
        $rawValue = trim(substr($filter, $position + strlen($operator)));

        return [$column, $operator, $this->castFilterValue($rawValue)];
    }

    private function castFilterValue(string $rawValue): int|float|string
    {
        $unquoted = $rawValue;
        $isQuoted = strlen($unquoted) >= 2
            && (($unquoted[0] === "'" && $unquoted[-1] === "'") || ($unquoted[0] === '"' && $unquoted[-1] === '"'));
        if ($isQuoted) {
            $unquoted = substr($unquoted, 1, -1);
        }

        if (!$isQuoted && is_numeric($unquoted)) {
            return str_contains($unquoted, '.') ? (float) $unquoted : (int) $unquoted;
        }

        return $unquoted;
    }

    /**
     * @param list<array{0:string,1:string,2:int|float|string}> $filters
     * @return array{0:string,1:list<int|float|string>}
     */
    private function buildWhereClause(MetricScope $scope, array $filters): array
    {
        $projectIds = $scope->projectIds();
        $placeholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $conditions = ["project_id IN ({$placeholders})"];
        $params = $projectIds;

        if ($scope->week() !== null) {
            $conditions[] = 'Semana = ?';
            $params[] = $scope->week();
        }

        foreach ($filters as [$column, $operator, $value]) {
            $conditions[] = "{$column} {$operator} ?";
            $params[] = $value;
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function resolveCompleteness(int $filasUsadas, int $obrasIncluidas, int $obrasEsperadas): string
    {
        if ($filasUsadas === 0) {
            return MetricResult::INSUFICIENTE;
        }

        if ($obrasIncluidas < $obrasEsperadas) {
            return MetricResult::PARCIAL;
        }

        return MetricResult::COMPLETA;
    }

    /**
     * @param list<int> $missingProjects
     * @return array<mixed>
     */
    private function buildMissing(string $completeness, array $missingProjects, int $filasUsadas): array
    {
        if ($completeness === MetricResult::COMPLETA) {
            return [];
        }

        $missing = [];
        if ($filasUsadas === 0) {
            $missing[] = 'sin_filas_que_cumplan_los_filtros';
        }
        if ($missingProjects !== []) {
            $missing['obras_sin_datos'] = $missingProjects;
        }

        return $missing;
    }

    private function assertSafeIdentifier(string $identifier): string
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw new RuntimeException("Identificador SQL invalido o inseguro: '{$identifier}'.");
        }

        return $identifier;
    }
}
