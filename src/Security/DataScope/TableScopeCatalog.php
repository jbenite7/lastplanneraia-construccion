<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use DomainException;
use PDO;

final class TableScopeCatalog
{
    /** @param array<string, TableScopeKind> $kinds */
    private function __construct(
        private readonly array $kinds,
        private readonly array $schemaRows,
    ) {
    }

    public static function fromPdo(PDO $pdo): self
    {
        $rows = $pdo->query(
            "SELECT c.TABLE_NAME,
                    MAX(c.COLUMN_NAME = 'project_id') AS has_project_id,
                    MAX(c.COLUMN_NAME = 'project_id' AND c.IS_NULLABLE = 'YES') AS project_id_nullable,
                    EXISTS (
                        SELECT 1 FROM information_schema.STATISTICS s
                        WHERE s.TABLE_SCHEMA = c.TABLE_SCHEMA
                          AND s.TABLE_NAME = c.TABLE_NAME
                          AND s.SEQ_IN_INDEX = 1
                          AND s.COLUMN_NAME = 'project_id'
                    ) AS has_leading_index
             FROM information_schema.COLUMNS c
             WHERE c.TABLE_SCHEMA = DATABASE()
             GROUP BY c.TABLE_NAME
             ORDER BY c.TABLE_NAME"
        )->fetchAll(PDO::FETCH_ASSOC);

        return self::fromRows($rows ?: []);
    }

    /** @param list<array<string, mixed>> $rows */
    public static function fromRows(array $rows): self
    {
        $kinds = [];
        $normalized = [];
        foreach ($rows as $row) {
            $table = strtolower((string) ($row['TABLE_NAME'] ?? ''));
            if ($table === '') {
                continue;
            }

            $normalized[$table] = $row;
            $kinds[$table] = in_array($table, TableScopeDefinitions::IDENTITY, true)
                ? TableScopeKind::Identity
                : ((int) ($row['has_project_id'] ?? 0) === 1
                    ? TableScopeKind::Project
                    : (in_array($table, TableScopeDefinitions::SYSTEM, true)
                        ? TableScopeKind::System
                        : TableScopeKind::Unclassified));
        }

        return new self($kinds, $normalized);
    }

    public function kind(string $table): TableScopeKind
    {
        $key = strtolower(trim($table, " `\t\n\r\0\x0B"));

        return $this->kinds[$key]
            ?? throw new DomainException("Tabla no clasificada en el schema: {$key}");
    }

    public function hasTable(string $table): bool
    {
        $key = strtolower(trim($table, " `\t\n\r\0\x0B"));

        return isset($this->kinds[$key]);
    }

    /** @param list<string> $tables */
    public function hasOnlyProjectTables(array $tables): bool
    {
        if ($tables === []) {
            return false;
        }

        foreach ($tables as $table) {
            $key = strtolower(trim($table, " `\t\n\r\0\x0B"));
            if (($this->kinds[$key] ?? null) !== TableScopeKind::Project) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function projectScopedTables(): array
    {
        return array_keys(array_filter(
            $this->kinds,
            static fn(TableScopeKind $kind): bool => $kind === TableScopeKind::Project,
        ));
    }

    /** @return list<string> */
    public function unclassifiedTables(): array
    {
        return array_keys(array_filter(
            $this->kinds,
            static fn(TableScopeKind $kind): bool => $kind === TableScopeKind::Unclassified,
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function schemaRows(): array
    {
        return $this->schemaRows;
    }
}
