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
use InvalidArgumentException;
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

    public function testInjectsProjectIntoEveryRowOfMultiRowInsert(): void
    {
        $guarded = $this->guard->guard(
            'INSERT INTO auto_program_log (semana, consecutivo, accion, detalle) VALUES (?, ?, ?, ?), (?, ?, ?, ?), (?, ?, ?, ?)',
            [8, 10, 'crear', 'uno', 8, 11, 'crear', 'dos', 8, 12, 'crear', 'tres'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) '
            . 'VALUES (?, ?, ?, ?, ?), (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)',
            $guarded->sql,
        );
        self::assertSame(
            [73, 8, 10, 'crear', 'uno', 73, 8, 11, 'crear', 'dos', 73, 8, 12, 'crear', 'tres'],
            $guarded->params,
        );
    }

    public function testValidatesExplicitProjectIdOnEveryRowOfMultiRowInsert(): void
    {
        $guarded = $this->guard->guard(
            'INSERT INTO auto_program_log (project_id, semana, detalle) VALUES (?, ?, ?), (?, ?, ?)',
            [73, 8, 'uno', 73, 9, 'dos'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            'INSERT INTO auto_program_log (project_id, semana, detalle) VALUES (?, ?, ?), (?, ?, ?)',
            $guarded->sql,
        );
        self::assertSame([73, 8, 'uno', 73, 9, 'dos'], $guarded->params);
    }

    public function testRejectsMultiRowInsertWhenAnyRowProjectIdDiffersFromScope(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        // La segunda fila trae el project_id de otra obra (27); la primera es correcta (73).
        // El rechazo tiene que alcanzar a CUALQUIER fila, no solo a la primera.
        $this->guard->guard(
            'INSERT INTO auto_program_log (project_id, semana, detalle) VALUES (?, ?, ?), (?, ?, ?)',
            [73, 8, 'uno', 27, 9, 'dos'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testValidatesRowsThatMixExplicitLiteralsWithProjectIdPlaceholder(): void
    {
        // Forma real de MaestroInsumosService::generarVinculos()
        // (src/Services/Pdc/MaestroInsumosService.php:92) — el caso concreto que TASKS.md
        // confirmó reventando en caliente: project_id explícito + un literal fijo ('pendiente')
        // en otra columna de la misma tupla, repetido por cada fila del lote.
        $guarded = $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) "
            . "VALUES (?, ?, ?, 'crear', ?), (?, ?, ?, 'crear', ?), (?, ?, ?, 'crear', ?)",
            [73, 8, 1, 'uno', 73, 8, 2, 'dos', 73, 8, 3, 'tres'],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle) "
            . "VALUES (?, ?, ?, 'crear', ?), (?, ?, ?, 'crear', ?), (?, ?, ?, 'crear', ?)",
            $guarded->sql,
        );
        self::assertSame([73, 8, 1, 'uno', 73, 8, 2, 'dos', 73, 8, 3, 'tres'], $guarded->params);
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

    public function testRejectsLeftJoinWhenOnlyNullableDerivedSideIsScoped(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT ?, a.Semana, 1, 'crear', 'detalle'
             FROM programa a
             LEFT JOIN (
                 SELECT project_id FROM auto_program_log WHERE project_id = ?
             ) d ON d.project_id = a.project_id",
            [73, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testRejectsRightJoinWhenOnlyNullableDerivedSideIsScoped(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT ?, a.Semana, 1, 'crear', 'detalle'
             FROM (
                 SELECT project_id FROM auto_program_log WHERE project_id = ?
             ) d
             RIGHT JOIN programa a ON d.project_id = a.project_id",
            [73, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    public function testAcceptsRightJoinWhenPreservedDerivedSideIsScoped(): void
    {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
                SELECT ?, d.Semana, 1, 'crear', 'detalle'
                FROM auto_program_log b
                RIGHT JOIN (
                    SELECT project_id, Semana FROM programa WHERE project_id = ?
                ) d ON b.project_id = d.project_id";

        $guarded = $this->guard->guard(
            $sql,
            [73, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame([73, 73], $guarded->params);
    }

    public function testAcceptsInnerJoinPropagationFromScopedDerivedSide(): void
    {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
                SELECT ?, a.Semana, 1, 'crear', 'detalle'
                FROM programa a
                INNER JOIN (
                    SELECT project_id FROM auto_program_log WHERE project_id = ?
                ) d ON d.project_id = a.project_id";

        $guarded = $this->guard->guard(
            $sql,
            [73, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame([73, 73], $guarded->params);
    }

    public function testRejectsUnsupportedFullOuterJoinInDerivedProjectQuery(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle)
             SELECT ?, a.Semana, 1, 'crear', 'detalle'
             FROM programa a
             FULL OUTER JOIN (
                 SELECT project_id FROM auto_program_log WHERE project_id = ?
             ) d ON d.project_id = a.project_id",
            [73, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
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

    public function testMultiProjectBoundaryInjectsAuthorizedIdsWhenProjectIdIsOmitted(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT * FROM programa WHERE Semana = ?',
            [8],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id IN (?, ?) AND Semana = ?',
            $guarded->sql,
        );
        self::assertSame([27, 73, 8], $guarded->params);
    }

    public function testMultiProjectBoundaryIntersectsHostileRequestedIds(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT * FROM programa WHERE project_id IN (?, ?, ?) AND Semana = ?',
            [27, 73, 999999, 8],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id IN (?, ?) AND Semana = ?',
            $guarded->sql,
        );
        self::assertSame([27, 73, 8], $guarded->params);
    }

    public function testMultiProjectBoundaryPreservesNamedParametersWhileIntersectingIds(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT * FROM programa WHERE project_id IN (:requested_a, :requested_c) AND Semana = :week',
            ['requested_a' => 73, 'requested_c' => 999999, 'week' => 8],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertSame(
            'SELECT * FROM programa WHERE programa.project_id IN (:__scope_project_0) AND Semana = :week',
            $guarded->sql,
        );
        self::assertSame(['__scope_project_0' => 73, 'week' => 8], $guarded->params);
    }

    public function testMultiProjectBoundaryScopesRelatedProjectJoinAndKeepsIdentityJoin(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT a.Semana, b.detalle, pm.role FROM programa a JOIN auto_program_log b ON b.project_id = a.project_id AND b.semana = a.Semana JOIN project_members pm ON pm.project_id = a.project_id WHERE a.Semana = ?',
            [8],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertStringContainsString('WHERE a.project_id IN (?, ?) AND a.Semana = ?', $guarded->sql);
        self::assertSame([27, 73, 8], $guarded->params);
        self::assertSame(['programa', 'auto_program_log', 'project_members'], $guarded->tables);
    }

    public function testMultiProjectBoundaryRejectsNotExistsWhenOnlyInnerRootIsAnchored(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT a.detalle FROM auto_program_log a WHERE NOT EXISTS (SELECT 1 FROM auto_program_log b WHERE b.project_id = a.project_id AND b.project_id IN (?, ?))',
            [27, 73],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryRejectsNegatedExistsWhenOnlyInnerRootIsAnchored(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT a.detalle FROM auto_program_log a WHERE NOT (EXISTS (SELECT 1 FROM auto_program_log b WHERE b.project_id = a.project_id AND b.project_id IN (?, ?)))',
            [27, 73],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryRejectsPositiveExistsWhenOnlyInnerRootIsAnchored(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT a.detalle FROM auto_program_log a WHERE EXISTS (SELECT 1 FROM auto_program_log b WHERE b.project_id = a.project_id AND b.project_id IN (?, ?))',
            [27, 73],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryIntersectsOuterAndInnerAnchorsIndependently(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT a.detalle FROM auto_program_log a WHERE a.project_id IN (?, ?, ?) AND NOT EXISTS (SELECT 1 FROM auto_program_log b WHERE b.project_id = a.project_id AND b.project_id IN (?, ?, ?))',
            [27, 73, 91, 27, 73, 91],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertStringContainsString('WHERE a.project_id IN (?, ?) AND NOT EXISTS', $guarded->sql);
        self::assertStringContainsString('b.project_id = a.project_id AND b.project_id IN (?, ?)', $guarded->sql);
        self::assertSame([27, 73, 27, 73], $guarded->params);
    }

    public function testMultiProjectBoundaryRejectsDerivedAliasBridgingInnerAuthorityToOuterRoot(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT a.Semana FROM programa a INNER JOIN (SELECT project_id FROM auto_program_log b WHERE b.project_id IN (?, ?)) d ON d.project_id = a.project_id',
            [27, 73],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryAcceptsDerivedJoinWhenBothSelectBlocksAreIndependentlyAnchored(): void
    {
        $guarded = $this->guard->guardForProjects(
            'SELECT a.Semana FROM programa a INNER JOIN (SELECT project_id FROM auto_program_log b WHERE b.project_id IN (?, ?, ?)) d ON d.project_id = a.project_id WHERE a.project_id IN (?, ?, ?)',
            [27, 73, 91, 27, 73, 91],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );

        self::assertStringContainsString(
            'SELECT project_id FROM auto_program_log b WHERE b.project_id IN (?, ?)',
            $guarded->sql,
        );
        self::assertStringContainsString('WHERE a.project_id IN (?, ?)', $guarded->sql);
        self::assertSame([27, 73, 27, 73], $guarded->params);
    }

    public function testMultiProjectBoundaryClassifiesPhysicalRootsInsideCtePipeline(): void
    {
        $sql = "WITH filtered AS (
                    SELECT p.project_id, p.Semana
                    FROM programa p
                    WHERE p.project_id IN (?, ?)
                ), points AS (
                    SELECT project_id, MAX(Semana) AS Semana
                    FROM filtered
                    GROUP BY project_id
                )
                SELECT * FROM points";

        $guarded = $this->guard->guardForProjects(
            $sql,
            [73, 91],
            new MultiProjectScope([73, 91], 'test.A', 'R', 'test:cte'),
            $this->catalog,
        );

        self::assertSame($sql, $guarded->sql);
        self::assertSame([73, 91], $guarded->params);
        self::assertSame(['programa'], $guarded->tables);
    }

    public function testMultiProjectBoundaryRejectsUnanchoredNestedProjectRoot(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT a.Semana, (SELECT MAX(b.semana) FROM auto_program_log b) AS max_semana FROM programa a',
            [],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryRejectsIdentityOnlyQuery(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT * FROM project_members WHERE user_id = ?',
            [9],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryRejectsSystemOnlyQuery(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guardForProjects(
            'SELECT * FROM general_flags',
            [],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectBoundaryRejectsUnclassifiedQuery(): void
    {
        $this->expectException(DomainException::class);

        $this->guard->guardForProjects(
            'SELECT * FROM backup_fuera_de_runtime',
            [],
            new MultiProjectScope([73, 27], 'test.A', 'A', 'test:bi'),
            $this->catalog,
        );
    }

    public function testMultiProjectScopeCannotBeConstructedEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MultiProjectScope([], 'test.A', 'A', 'test:bi');
    }

    public function testSingleProjectBoundaryStillRejectsInPredicate(): void
    {
        $this->expectException(ProjectScopeViolation::class);

        $this->guard->guard(
            'SELECT * FROM programa WHERE project_id IN (?, ?)',
            [27, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    /**
     * Nombrar dos veces la MISMA tabla sin alias es ambiguo y se rechaza.
     *
     * No es teoria: es la forma exacta que tenian `src/Legacy/datosGeneralesPagina.php` y
     * `ProgramaGeneralController` —la misma consulta copiada en dos sitios— y con ella
     * `/programacion-semanal` y `/programa-general` reventaban al cargar desde el 2026-08-29.
     * El guard hace bien en fallar cerrado: con dos raices homonimas no puede decidir a cual
     * pertenece cada `project_id = ?`.
     */
    public function testRejectsSameProjectTableTwiceWithoutDistinctAliases(): void
    {
        $this->expectException(ProjectScopeViolation::class);
        $this->expectExceptionMessage('Alias de tabla de proyecto ambiguo');

        $this->guard->guard(
            'SELECT Semana, (SELECT SUM(reprogramacion) FROM programa WHERE Semana <= ? AND project_id = ?) AS v'
            . ' FROM programa WHERE Semana = ? AND project_id = ?',
            [1, 73, 1, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );
    }

    /** Y con un alias por referencia, cada una con su `project_id` calificado, pasa. */
    public function testAcceptsSameProjectTableTwiceWithDistinctAliases(): void
    {
        $consulta = $this->guard->guard(
            'SELECT s.Semana, (SELECT SUM(r.reprogramacion) FROM programa r WHERE r.Semana <= ? AND r.project_id = ?) AS v'
            . ' FROM programa s WHERE s.Semana = ? AND s.project_id = ?',
            [1, 73, 1, 73],
            new ProjectScope(73, 'test.A', 'A'),
            $this->catalog,
        );

        // El guard no tiene que anadir ningun filtro: las dos referencias ya traen el suyo, y los
        // parametros salen intactos. Si inyectara, el orden de los marcadores dejaria de casar.
        self::assertSame([1, 73, 1, 73], $consulta->params);
    }
}
