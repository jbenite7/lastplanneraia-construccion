<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\TableScopeCatalog;
use App\Security\DataScope\TableScopeKind;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class TableScopeCatalogTest extends TestCase
{
    public function testClasificaPorContratoSinConfundirMembresia(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            ['TABLE_NAME' => 'programa', 'has_project_id' => 1, 'project_id_nullable' => 0, 'has_leading_index' => 1],
            ['TABLE_NAME' => 'project_members', 'has_project_id' => 1, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'general_usuarios', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'general_flags', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
            ['TABLE_NAME' => 'backup_fuera_de_runtime', 'has_project_id' => 0, 'project_id_nullable' => 0, 'has_leading_index' => 0],
        ]);

        self::assertSame(TableScopeKind::Project, $catalog->kind('programa'));
        self::assertSame(TableScopeKind::Identity, $catalog->kind('project_members'));
        self::assertSame(TableScopeKind::Identity, $catalog->kind('general_usuarios'));
        self::assertSame(TableScopeKind::System, $catalog->kind('general_flags'));
        self::assertSame(TableScopeKind::Unclassified, $catalog->kind('backup_fuera_de_runtime'));
        self::assertSame(['programa'], $catalog->projectScopedTables());
        self::assertSame(['backup_fuera_de_runtime'], $catalog->unclassifiedTables());
    }

    public function testUnaTablaAjenaAlSchemaNoSeVuelveGlobalPorDefecto(): void
    {
        $catalog = TableScopeCatalog::fromRows([]);

        $this->expectException(\DomainException::class);
        $catalog->kind('tabla_inventada');
    }
}
