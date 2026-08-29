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

    public function testCompruebaExistenciaSinConsultarInformationSchemaDesdeElCaller(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            ['TABLE_NAME' => 'programa', 'has_project_id' => 1],
            ['TABLE_NAME' => 'general_usuarios', 'has_project_id' => 0],
        ]);

        self::assertTrue($catalog->hasTable('programa'));
        self::assertTrue($catalog->hasTable('`GENERAL_USUARIOS`'));
        self::assertFalse($catalog->hasTable('tabla_inventada'));
    }

    public function testExigeQueTodasLasTablasEsperadasExistanYSeanProject(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            ['TABLE_NAME' => 'programa', 'has_project_id' => 1],
            ['TABLE_NAME' => 'auto_program_log', 'has_project_id' => 1],
            ['TABLE_NAME' => 'general_usuarios', 'has_project_id' => 0],
            ['TABLE_NAME' => 'backup_fuera_de_runtime', 'has_project_id' => 0],
        ]);

        self::assertTrue($catalog->hasOnlyProjectTables(['programa', 'auto_program_log']));
        self::assertFalse($catalog->hasOnlyProjectTables([]));
        self::assertFalse($catalog->hasOnlyProjectTables(['programa', 'tabla_inventada']));
        self::assertFalse($catalog->hasOnlyProjectTables(['programa', 'general_usuarios']));
        self::assertFalse($catalog->hasOnlyProjectTables(['programa', 'backup_fuera_de_runtime']));
    }

    public function testSystemNotificationsEsIdentityAunqueTengaProjectIdVarcharNullable(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            [
                'TABLE_NAME' => 'system_notifications',
                'TABLE_TYPE' => 'BASE TABLE',
                'COLUMN_TYPE' => 'varchar(100)',
                'has_project_id' => 1,
                'project_id_nullable' => 1,
                'has_leading_index' => 0,
            ],
        ]);

        self::assertSame(TableScopeKind::Identity, $catalog->kind('system_notifications'));
        self::assertSame([], $catalog->projectScopedTables());
    }

    public function testMantieneNueveViewsProjectLogicasPeroNoLasExponeComoTablasFisicas(): void
    {
        $viewNames = [
            'bi_cic_contractual',
            'bi_cip_responsables',
            'bi_control_contractual',
            'bi_curva_control',
            'bi_pdc_pipeline',
            'bi_pg_avance',
            'bi_pi_hitos',
            'bi_ps_compromisos',
            'bi_riesgos',
        ];
        $rows = [[
            'TABLE_NAME' => 'programa',
            'TABLE_TYPE' => 'BASE TABLE',
            'COLUMN_TYPE' => 'int',
            'has_project_id' => 1,
        ]];
        foreach ($viewNames as $viewName) {
            $rows[] = [
                'TABLE_NAME' => $viewName,
                'TABLE_TYPE' => 'VIEW',
                'COLUMN_TYPE' => 'int',
                'has_project_id' => 1,
            ];
        }

        $catalog = TableScopeCatalog::fromRows($rows);

        self::assertSame(['programa', ...$viewNames], $catalog->projectScopedTables());
        self::assertSame(['programa'], $catalog->projectScopedBaseTables());
        foreach ($viewNames as $viewName) {
            self::assertSame(TableScopeKind::Project, $catalog->kind($viewName));
        }
    }

    public function testExponeTableTypeYColumnTypeNormalizados(): void
    {
        $catalog = TableScopeCatalog::fromRows([
            [
                'TABLE_NAME' => 'programa',
                'TABLE_TYPE' => 'base table',
                'COLUMN_TYPE' => 'int unsigned',
                'has_project_id' => 1,
            ],
        ]);

        self::assertSame('BASE TABLE', $catalog->schemaRows()['programa']['TABLE_TYPE']);
        self::assertSame('int unsigned', $catalog->schemaRows()['programa']['COLUMN_TYPE']);
    }
}
