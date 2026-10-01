<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\ProjectScope;
use App\Services\Lps\LpsLegacyCrisisRepository;
use App\Services\Lps\LpsLegacyThreadRepository;
use App\Services\LpsService;
use Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Los escritores de alertas y comentarios devuelven el id que la capa de datos asigna por proyecto
 * (`lastInsertId()` daba 0 en esas tablas). Todo corre en una transacción que se revierte.
 */
#[Group('db')]
final class LpsLegacyInsertedIdTest extends TestCase
{
    private const PROJECT_ID = 73;
    private const PREFIX = 'test';

    private Database $db;
    private int $consecutivo;
    private int $semana;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->dataScope()->clear();
        $this->db->dataScope()->bind(new ProjectScope(self::PROJECT_ID, 'test.A', 'A'));
        $this->db->beginTransaction();

        $this->consecutivo = (int) $this->db->query('SELECT Consecutivo FROM programa LIMIT 1')->fetchColumn();
        $this->semana = (int) $this->db->query('SELECT Semana FROM semanas_activas LIMIT 1')->fetchColumn();
        self::assertGreaterThan(0, $this->consecutivo, 'El proyecto 73 no tiene programa sembrado.');
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->db->dataScope()->clear();
    }

    private function filas(string $tabla, int $id): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM `{$tabla}` WHERE id = ? AND proyecto_id = ?",
            [$id, self::PROJECT_ID],
        )->fetchColumn();
    }

    public function testInsertAlertDevuelveElIdDeLaFila(): void
    {
        $repo = new LpsLegacyCrisisRepository($this->db, self::PREFIX);

        $id = $repo->insertAlert(self::PROJECT_ID, $this->consecutivo, 'PG', $this->semana, 'test');

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_escalamientos', $id));
    }

    public function testInsertComentarioDevuelveElIdDeLaFila(): void
    {
        $repo = new LpsLegacyThreadRepository($this->db, self::PREFIX);

        $id = $repo->insert(self::PROJECT_ID, $this->consecutivo, $this->semana, $this->usuarioId(), 'zz-test-insertedid', null, null, null);

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_drawer_comentarios', $id));
    }

    public function testAddActivityCommentDevuelveElIdDeLaFila(): void
    {
        $service = new LpsService();

        $id = $service->addActivityComment(self::PREFIX, self::PROJECT_ID, $this->consecutivo, $this->semana, $this->usuarioId(), 'zz-test-insertedid');

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_drawer_comentarios', $id));
    }

    private function usuarioId(): int
    {
        $id = (int) $this->db->query('SELECT Id FROM general_usuarios LIMIT 1')->fetchColumn();
        self::assertGreaterThan(0, $id, 'No hay usuarios sembrados.');

        return $id;
    }
}
