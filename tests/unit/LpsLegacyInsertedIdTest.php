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
    private int $actividad;
    private int $semana;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->dataScope()->clear();
        $this->db->dataScope()->bind(new ProjectScope(self::PROJECT_ID, 'test.A', 'A'));
        $this->db->beginTransaction();

        // Los escritores ponen este mismo numero en `unique_id` y en `consecutivo_en_programa`, y ambas
        // columnas tienen FK a `programa`: hace falta una actividad cuyo unique_id exista tambien como
        // Consecutivo. Sin ORDER BY el LIMIT 1 elegia una fila distinta segun el motor y fallaba a ratos.
        $this->actividad = (int) $this->db->query(
            'SELECT a.unique_id FROM programa a JOIN programa b ON b.project_id = a.project_id AND b.Consecutivo = a.unique_id '
            . 'WHERE a.project_id = ? ORDER BY a.unique_id LIMIT 1',
            [self::PROJECT_ID],
        )->fetchColumn();
        $this->semana = (int) $this->db->query(
            'SELECT Semana FROM semanas_activas WHERE project_id = ? ORDER BY Semana LIMIT 1',
            [self::PROJECT_ID],
        )->fetchColumn();
        self::assertGreaterThan(0, $this->actividad, 'Falta en programa del proyecto 73 una fila cuyo unique_id exista tambien como Consecutivo.');
        self::assertGreaterThan(0, $this->semana, 'Falta una semana en semanas_activas del proyecto 73.');
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

        $id = $repo->insertAlert(self::PROJECT_ID, $this->actividad, 'PG', $this->semana, 'test');

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_escalamientos', $id));
    }

    public function testInsertComentarioDevuelveElIdDeLaFila(): void
    {
        $repo = new LpsLegacyThreadRepository($this->db, self::PREFIX);

        $id = $repo->insert(self::PROJECT_ID, $this->actividad, $this->semana, $this->profesionalId(), 'zz-test-insertedid', null, null, null);

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_drawer_comentarios', $id));
    }

    public function testAddActivityCommentDevuelveElIdDeLaFila(): void
    {
        $service = new LpsService();

        $id = $service->addActivityComment(self::PREFIX, self::PROJECT_ID, $this->actividad, $this->semana, $this->profesionalId(), 'zz-test-insertedid');

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $this->filas('lps_drawer_comentarios', $id));
    }

    /** `lps_drawer_comentarios.usuario_id` referencia `profesionales(project_id, id)`, no `general_usuarios`. */
    private function profesionalId(): int
    {
        $id = (int) $this->db->query('SELECT id FROM profesionales WHERE project_id = ? LIMIT 1', [self::PROJECT_ID])->fetchColumn();
        if ($id === 0) {
            $this->db->query(
                'INSERT INTO profesionales (project_id, nombre, email, cargo, activo) VALUES (?, ?, ?, ?, 1)',
                [self::PROJECT_ID, 'zz-test-profesional', 'zz-test-profesional@example.invalid', 'zz-test'],
            );
            $id = $this->db->insertedId();
        }
        self::assertGreaterThan(0, $id, 'No se pudo obtener ni crear un profesional del proyecto.');

        return $id;
    }
}
