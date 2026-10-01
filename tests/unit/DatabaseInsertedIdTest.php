<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\ProjectScope;
use Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * `Database::insertedId()` devuelve el id que la capa de datos asigna por proyecto en las
 * tablas de PROJECT_SCOPED_IDS (sin AUTO_INCREMENT, donde `lastInsertId()` devuelve 0).
 * Todo corre dentro de una transacción que se revierte: la prueba no deja filas.
 */
#[Group('db')]
final class DatabaseInsertedIdTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->dataScope()->clear();
        $this->db->dataScope()->bind(new ProjectScope(73, 'test.A', 'A'));
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->db->dataScope()->clear();
    }

    private function insertEscalamiento(): void
    {
        $consecutivo = (int) $this->db->query('SELECT Consecutivo FROM programa LIMIT 1')->fetchColumn();
        $semana = (int) $this->db->query('SELECT Semana FROM semanas_activas LIMIT 1')->fetchColumn();
        self::assertGreaterThan(0, $consecutivo, 'El proyecto 73 no tiene programa sembrado.');

        $this->db->query(
            'INSERT INTO lps_escalamientos (proyecto_id, semana, consecutivo_en_programa, modulo, trigger_origen) VALUES (?, ?, ?, ?, ?)',
            [73, $semana, $consecutivo, 'PG', 'test'],
        );
    }

    private function maxEscalamientoId(): int
    {
        return (int) $this->db->query('SELECT MAX(id) FROM lps_escalamientos')->fetchColumn();
    }

    public function testDevuelveElIdQueAsignoRewriteInsert(): void
    {
        $this->insertEscalamiento();

        $id = $this->db->insertedId();

        self::assertGreaterThan(0, $id);
        self::assertSame($this->maxEscalamientoId(), $id);
    }

    public function testUnSelectIntermedioNoBorraElId(): void
    {
        $this->insertEscalamiento();
        $antes = $this->db->insertedId();

        $this->db->query('SELECT COUNT(*) FROM lps_escalamientos')->fetchColumn();

        self::assertGreaterThan(0, $antes);
        self::assertSame($antes, $this->db->insertedId());
    }

    public function testInsertConIdPropioCaeALastInsertId(): void
    {
        $consecutivo = (int) $this->db->query('SELECT Consecutivo FROM programa LIMIT 1')->fetchColumn();
        $semana = (int) $this->db->query('SELECT Semana FROM semanas_activas LIMIT 1')->fetchColumn();

        $this->db->query(
            'INSERT INTO lps_escalamientos (id, proyecto_id, semana, consecutivo_en_programa, modulo, trigger_origen) VALUES (?, ?, ?, ?, ?, ?)',
            [987654, 73, $semana, $consecutivo, 'PG', 'test'],
        );

        self::assertSame((int) $this->db->lastInsertId(), $this->db->insertedId());
    }

    public function testInsertPorPrepareBorraElIdAnterior(): void
    {
        $this->insertEscalamiento();
        $reescrito = $this->db->insertedId();
        self::assertGreaterThan(0, $reescrito);

        $stmt = $this->db->prepare(
            'INSERT INTO pdc_subpaquete (project_id, paquete_id, nombre, creado_por, updated_at) VALUES (?, ?, ?, ?, NOW())',
        );
        $stmt->execute([73, $this->paqueteId(), 'zz-test-insertedid-prepare', 'test']);

        self::assertNotSame($reescrito, $this->db->insertedId());
        self::assertSame((int) $this->db->lastInsertId(), $this->db->insertedId());
    }

    public function testCaeALastInsertIdEnTablaConAutoIncrement(): void
    {
        $this->db->query(
            'INSERT INTO pdc_subpaquete (project_id, paquete_id, nombre, creado_por, updated_at) VALUES (?, ?, ?, ?, NOW())',
            [73, $this->paqueteId(), 'zz-test-insertedid-auto', 'test'],
        );

        self::assertGreaterThan(0, $this->db->insertedId());
        self::assertSame((int) $this->db->lastInsertId(), $this->db->insertedId());
    }

    public function testInsertIgnoreSinFilasNoDejaId(): void
    {
        $this->insertEscalamiento();
        $reescrito = $this->db->insertedId();
        self::assertGreaterThan(0, $reescrito);

        // INSERT IGNORE con una consecutiva inexistente: la FK se degrada a aviso y no entra fila.
        $this->db->query(
            'INSERT IGNORE INTO lps_escalamientos (proyecto_id, semana, consecutivo_en_programa, modulo, trigger_origen) VALUES (?, ?, ?, ?, ?)',
            [73, 1, 999999999, 'PG', 'test'],
        );

        self::assertNotSame($reescrito, $this->db->insertedId());
        self::assertSame((int) $this->db->lastInsertId(), $this->db->insertedId());
    }

    public function testInsertQueFallaNoDejaElIdDelAnterior(): void
    {
        $this->insertEscalamiento();
        $anterior = $this->db->insertedId();
        self::assertGreaterThan(0, $anterior);

        try {
            // Viola la FK a programa: execute() lanza PDOException.
            $this->db->query(
                'INSERT INTO lps_escalamientos (proyecto_id, semana, consecutivo_en_programa, modulo, trigger_origen) VALUES (?, ?, ?, ?, ?)',
                [73, 1, 999999999, 'PG', 'test'],
            );
            self::fail('Se esperaba una PDOException por la FK.');
        } catch (\PDOException) {
            // esperado
        }

        self::assertNotSame($anterior, $this->db->insertedId());
    }

    /** Un paquete existente o, si la base no tiene ninguno (CI), uno creado dentro de la transacción. */
    private function paqueteId(): int
    {
        $id = (int) $this->db->query('SELECT id FROM general_paquetes_contratacion LIMIT 1')->fetchColumn();
        if ($id === 0) {
            $this->db->query(
                'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
                 VALUES (?, ?, ?, ?, 1, ?, NOW())',
                ['zz-test-paquete', 'zz-test-paquete', 'a_todo_costo', 'contrato', 'test'],
            );
            $id = (int) $this->db->lastInsertId();
        }
        self::assertGreaterThan(0, $id, 'No se pudo obtener ni crear un paquete de contratación.');

        return $id;
    }
}
