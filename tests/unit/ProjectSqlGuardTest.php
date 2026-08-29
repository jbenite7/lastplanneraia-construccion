<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\MissingProjectScope;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\ProjectScopeViolation;
use App\Security\DataScope\ProjectSqlGuard;
use App\Security\DataScope\SystemScope;
use App\Security\DataScope\TableScopeCatalog;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('db')]
final class ProjectSqlGuardTest extends TestCase
{
    private ProjectSqlGuard $guard;
    private TableScopeCatalog $catalog;

    protected function setUp(): void
    {
        $this->guard = new ProjectSqlGuard(\Database::getInstance());
        $this->catalog = TableScopeCatalog::fromRows([
            ['TABLE_NAME' => 'programa', 'has_project_id' => 1],
            ['TABLE_NAME' => 'auto_program_log', 'has_project_id' => 1],
            ['TABLE_NAME' => 'project_members', 'has_project_id' => 1],
            ['TABLE_NAME' => 'general_flags', 'has_project_id' => 0],
            ['TABLE_NAME' => 'backup_fuera_de_runtime', 'has_project_id' => 0],
        ]);
    }

    /** @return iterable<string, array{string, array<mixed>, int|null, string|null, array<mixed>}> */
    public static function singleTableQueries(): iterable
    {
        yield 'simple select is scoped' => [
            'SELECT * FROM programa WHERE Semana = ?',
            [8],
            73,
            'programa.project_id = ?',
            [73, 8],
        ];
        yield 'explicit matching id is validated without a second filter' => [
            'SELECT * FROM programa WHERE project_id = ? AND Semana = ?',
            [73, 8],
            73,
            'programa.project_id = ?',
            [73, 8],
        ];
        yield 'identity query needs no project' => [
            'SELECT * FROM project_members WHERE user_id = ?',
            [9],
            null,
            null,
            [9],
        ];
    }

    /** @param array<mixed> $params @param array<mixed> $expectedParams */
    #[DataProvider('singleTableQueries')]
    public function testGuardsSingleTableQueries(
        string $sql,
        array $params,
        ?int $projectId,
        ?string $expectedFragment,
        array $expectedParams,
    ): void {
        $scope = $projectId === null ? null : new ProjectScope($projectId, 'test.A', 'A');

        $guarded = $this->guard->guard($sql, $params, $scope, $this->catalog);

        if ($expectedFragment === null) {
            self::assertSame($sql, $guarded->sql);
        } else {
            self::assertStringContainsString($expectedFragment, $guarded->sql);
        }
        self::assertSame($expectedParams, $guarded->params);
    }

    public function testRejectsProjectTableWithoutScope(): void
    {
        $this->expectException(MissingProjectScope::class);

        $this->guard->guard('SELECT * FROM programa', [], null, $this->catalog);
    }

    public function testScopesSelectBeforeTerminalForUpdateLock(): void
    {
        $guarded = $this->guard->guard(
            'SELECT * FROM programa WHERE Semana = ? FOR UPDATE',
            [8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id = ? AND Semana = ? FOR UPDATE',
            $guarded->sql,
        );
        self::assertSame([73, 8], $guarded->params);
    }

    public function testRejectsForUpdateProjectQueryWithoutScope(): void
    {
        $this->expectException(MissingProjectScope::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE Semana = ? FOR UPDATE',
            [8],
            null,
            $this->catalog,
        );
    }

    public function testRejectsContradictoryProjectPredicateBeforeForUpdateLock(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE project_id = ? AND Semana = ? FOR UPDATE',
            [27, 8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsKnownButUnclassifiedTableEvenWithSystemScope(): void
    {
        $this->expectException(DomainException::class);

        $this->guard->guard(
            'SELECT * FROM backup_fuera_de_runtime',
            [],
            SystemScope::forMaintenance('test'),
            $this->catalog,
        );
    }

    public function testRejectsExplicitProjectDifferentFromScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE project_id = ?',
            [27],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsPrefixThatResolvesOutsideScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM da_porto_programa',
            [],
            new ProjectScope(27, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testInjectsProjectIntoInsertValues(): void
    {
        $guarded = $this->guard->guard(
            'INSERT INTO auto_program_log (semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?)',
            [8, 10, 'crear', 'detalle'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)',
            $guarded->sql,
        );
        self::assertSame([73, 8, 10, 'crear', 'detalle'], $guarded->params);
    }

    public function testRejectsInsertProjectDifferentFromScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'INSERT INTO auto_program_log (project_id, semana, detalle) VALUES (?, ?, ?)',
            [27, 8, 'detalle'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsTwoProjectTablesWithoutProjectRelation(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT a.Semana FROM programa a JOIN auto_program_log b ON b.semana = a.Semana WHERE a.project_id = ?',
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsCommaJoinThatTokenizerCannotRelate(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT a.Semana FROM programa a, auto_program_log b WHERE a.project_id = ?',
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsScopePredicateThatOnlyProtectsOneOrBranch(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE project_id = ? OR Semana = ?',
            [73, 8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testGroupsExistingOrWhenInjectingScope(): void
    {
        $guarded = $this->guard->guard(
            'SELECT * FROM programa WHERE Semana = ? OR Semana = ?',
            [8, 9],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id = ? AND (Semana = ? OR Semana = ?)',
            $guarded->sql,
        );
        self::assertSame([73, 8, 9], $guarded->params);
    }

    public function testGroupsExistingXorWhenInjectingScope(): void
    {
        $guarded = $this->guard->guard(
            'SELECT * FROM programa WHERE Semana = ? XOR TRUE',
            [8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id = ? AND (Semana = ? XOR TRUE)',
            $guarded->sql,
        );
        self::assertSame([73, 8], $guarded->params);
    }

    public function testDoesNotTreatRootScopeInsideLeftJoinOnAsRowFilter(): void
    {
        $guarded = $this->guard->guard(
            'SELECT a.Semana FROM programa a LEFT JOIN project_members pm ON a.project_id = ? AND pm.project_id = a.project_id',
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertStringContainsString('WHERE a.project_id = ?', $guarded->sql);
        self::assertSame([73, 73], $guarded->params);
    }

    public function testRejectsScopePredicateUnderXor(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE project_id = ? XOR TRUE',
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsInsertSelectProjectIdTakenOnlyFromIdentitySource(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) SELECT pm.project_id, ?, ?, ?, ? FROM project_members pm WHERE pm.user_id = ?',
            [8, 10, 'comprometer', 'detalle', 9],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testAcceptsScopedDerivedInsertSelectWithNestedProjectSources(): void
    {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
                SELECT src.project_id,
                       src.Semana,
                       COALESCE((SELECT MAX(consecutivo) FROM auto_program_log WHERE project_id = src.project_id), 0) + 1,
                       ?,
                       ?
                FROM (
                    SELECT DISTINCT project_id, Semana
                    FROM programa
                    WHERE project_id = ?
                ) src
                LEFT JOIN auto_program_log existing
                  ON existing.project_id = src.project_id AND existing.semana = src.Semana
                WHERE existing.detalle IS NULL";

        $guarded = $this->guard->guard(
            $sql,
            ['crear', 'detalle', 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame(['crear', 'detalle', 73], $guarded->params);
    }

    public function testRejectsDerivedProjectRootWithoutCanonicalScopePredicate(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT src.project_id, src.Semana, 1, 'crear', 'detalle'
             FROM (SELECT project_id, Semana FROM programa) src",
            [],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsDerivedProjectRootThatContradictsActiveScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT src.project_id, src.Semana, 1, 'crear', 'detalle'
             FROM (SELECT project_id, Semana FROM programa WHERE project_id = ?) src",
            [27],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testScopesRepeatedPhysicalTableIndependentlyAcrossNestedDerivedSelects(): void
    {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
                SELECT ?, tabla.Semana, 1, 'crear', 'detalle'
                FROM (
                    SELECT programa.Semana,
                           (SELECT MAX(Id) FROM programa WHERE project_id = ? AND Semana = ?) AS max_id
                    FROM programa
                    WHERE project_id = ? AND Semana = ?
                ) AS tabla";

        $guarded = $this->guard->guard(
            $sql,
            [73, 73, 8, 73, 8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame(['auto_program_log', 'programa'], $guarded->tables);
    }

    public function testRejectsRepeatedPhysicalTableWhenNestedRootLacksScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT ?, tabla.Semana, 1, 'crear', 'detalle'
             FROM (
                 SELECT programa.Semana,
                        (SELECT MAX(Id) FROM programa WHERE Semana = ?) AS max_id
                 FROM programa
                 WHERE project_id = ? AND Semana = ?
             ) AS tabla",
            [73, 8, 73, 8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testSystemScopeClassifiesProjectRootsInsideDerivedSelect(): void
    {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
                SELECT ?, tabla.Semana, 1, 'crear', 'detalle'
                FROM (
                    SELECT Semana
                    FROM programa
                    WHERE project_id = ?
                ) AS tabla";

        $guarded = $this->guard->guard(
            $sql,
            [73, 73],
            SystemScope::forMaintenance('test:derived'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame(['auto_program_log', 'programa'], $guarded->tables);
    }

    public function testRejectsUnsupportedReplaceIntoProjectTable(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'REPLACE INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?, ?)',
            [27, 8, 10, 'comprometer', 'detalle'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testAcceptsRelatedJoinWithCanonicalRootScope(): void
    {
        $sql = 'SELECT a.Semana FROM programa a JOIN auto_program_log b ON b.project_id = a.project_id AND b.semana = a.Semana WHERE a.project_id = ?';

        $guarded = $this->guard->guard(
            $sql,
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame([73], $guarded->params);
        self::assertSame(['programa', 'auto_program_log'], $guarded->tables);
    }

    public function testChoosesOuterTableAsRootWhenSelectListContainsCorrelatedSubquery(): void
    {
        $sql = 'SELECT a.Semana, (SELECT b.detalle FROM auto_program_log b WHERE b.project_id = a.project_id LIMIT 1) AS detalle FROM programa a WHERE a.project_id = ?';

        $guarded = $this->guard->guard(
            $sql,
            [73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame([73], $guarded->params);
    }

    public function testDoesNotTreatStringsOrCommentsAsProjectPredicates(): void
    {
        $guarded = $this->guard->guard(
            "SELECT 'project_id = 27' AS note FROM programa /* project_id = 27 */ WHERE Semana = ?",
            [8],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertStringContainsString('WHERE programa.project_id = ? AND Semana = ?', $guarded->sql);
        self::assertSame([73, 8], $guarded->params);
    }

    public function testRejectsMultiProjectScopeAtSingleProjectBoundary(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa',
            [],
            new MultiProjectScope([27, 73], 'test.A', 'A', 'test'),
            $this->catalog,
        );
    }
}
