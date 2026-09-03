<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\MissingProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\ProjectScopeViolation;
use App\Security\DataScope\SystemScope;
use Database;
use DomainException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre el envoltorio de `Database` que hace cumplir el DataScope activo antes de que
 * cualquier SQL llegue a PDO: identidad sola, alcance de proyecto exigido, tablas de
 * decisión sin clasificar rechazadas, y overrides contradictorios rechazados.
 *
 * Migrado desde `tests/DatabaseWrapperTest.php` (script autoejecutable que el runner nunca
 * recogía porque no seguía ninguno de los dos patrones de descubrimiento). Cada aserción del
 * script original queda como un método de test propio; la lógica probada no cambió.
 *
 * Nivel `db`: ejercita `Database::getInstance()` y `dataScope()` contra la base real.
 */
#[Group('db')]
final class DatabaseWrapperTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->dataScope()->clear();
    }

    protected function tearDown(): void
    {
        $this->db->dataScope()->clear();
    }

    public function testIdentityOnlyQueryWithProjectRemainsLegalWithoutProjectScope(): void
    {
        $count = (int) $this->db->queryWithProject(
            'SELECT COUNT(*) FROM general_usuarios WHERE Usuario = ?',
            ['test.A'],
        )->fetchColumn();

        self::assertGreaterThanOrEqual(1, $count, 'No encontró la identidad sembrada test.A.');
    }

    public function testProjectQueryWithProjectFailsClosedWithoutProjectScope(): void
    {
        $this->expectException(MissingProjectScope::class);

        $this->db->queryWithProject('SELECT COUNT(*) FROM auto_program_log');
    }

    public function testQueryRejectsTheAbsentOrUnclassifiedDecisionTableBeforePdo(): void
    {
        $this->expectException(DomainException::class);

        $this->db->query(
            'INSERT INTO general_decision_log (proyecto_id) VALUES (?)',
            [73],
        );
    }

    public function testIdentityOverrideCannotCreateProjectAuthority(): void
    {
        $this->expectException(MissingProjectScope::class);

        $this->db->queryWithProject('SELECT COUNT(*) FROM general_usuarios', [], 73);
    }

    public function testActiveSystemScopeReachesTheSharedGuardForProjectSql(): void
    {
        $this->db->dataScope()->bind(SystemScope::forMaintenance('wrapper controlled system query'));

        $count = (int) $this->db->queryWithProject(
            'SELECT COUNT(*) FROM auto_program_log WHERE detalle LIKE ?',
            ['WRAPPER_TEST_NO_MATCH_%'],
            27,
        )->fetchColumn();

        self::assertSame(0, $count);
    }

    public function testMatchingScopeReachesTheSingleDatabasePreflight(): void
    {
        $this->db->dataScope()->bind(new ProjectScope(73, 'test.A', 'A'));

        $count = (int) $this->db->queryWithProject(
            'SELECT COUNT(*) FROM auto_program_log WHERE detalle LIKE ?',
            ['WRAPPER_TEST_NO_MATCH_%'],
            73,
        )->fetchColumn();

        self::assertSame(0, $count);
    }

    public function testContradictoryOverrideIsRejectedBeforePdo(): void
    {
        $this->db->dataScope()->bind(new ProjectScope(73, 'test.A', 'A'));

        $this->expectException(ProjectScopeViolation::class);

        $this->db->queryWithProject('SELECT COUNT(*) FROM auto_program_log', [], 27);
    }
}
