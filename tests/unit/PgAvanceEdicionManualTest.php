<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PgAvanceEdicionManualService;
use Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre la bitacora de ediciones manuales del avance en Programa General.
 *
 * Existe porque `WeeklyRealProgressCarryoverService` no puede distinguir, en su caso ambiguo,
 * una edicion real del residente de un residuo del defecto corregido el 2026-08-25. La bitacora
 * le da esa evidencia. Spec:
 * docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md
 */
#[Group('db')]
final class PgAvanceEdicionManualTest extends TestCase
{
    private const PROJECT_ID = 990074;
    private const UID = 1;
    private const SEMANA = 2;

    private Database $db;
    private PgAvanceEdicionManualService $servicio;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->servicio = new PgAvanceEdicionManualService($this->db);
        $this->limpiar();

        $this->db->query(
            "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo)
             VALUES (?, 'Bitacora Test', 'test_bitacora_tmp', 'QA', 1)",
            [self::PROJECT_ID],
        );
        $this->db->query(
            "INSERT INTO semanas_activas (Id, project_id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
             VALUES (?, ?, ?, '2026-08-17', '2026-08-23')",
            [990100, self::PROJECT_ID, self::SEMANA],
        );
        $this->db->query(
            "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad)
             VALUES (?, ?, ?, 'Campamentos')",
            [self::PROJECT_ID, self::UID, self::UID],
        );
        $this->db->query(
            "INSERT INTO programa_consolidado
                (project_id, Consecutivo, row_id, Semana, unique_id, Consecutivo_en_Programa,
                 Actividad, Titulo, Ejecutado, unidad, programaAnteriorAsociar)
             VALUES (?, 3001, 3001, ?, ?, ?, 'Campamentos', 0, 0.7, '%', '*No Asociada*')",
            [self::PROJECT_ID, self::SEMANA, self::UID, self::UID],
        );
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    private function limpiar(): void
    {
        foreach (['pg_avance_edicion_manual', 'programa_consolidado', 'programa', 'semanas_activas'] as $t) {
            $this->db->query("DELETE FROM {$t} WHERE project_id = ?", [self::PROJECT_ID]);
        }
        $this->db->query("DELETE FROM general_proyectos_procesos WHERE Id = ?", [self::PROJECT_ID]);
    }

    private function filasEnBitacora(): array
    {
        return $this->db->query(
            "SELECT valor_anterior, valor_nuevo, usuario FROM pg_avance_edicion_manual
             WHERE project_id = ? AND Semana = ? AND unique_id = ? ORDER BY id",
            [self::PROJECT_ID, self::SEMANA, self::UID],
        )->fetchAll();
    }

    private function ponerAvance(float $valor): void
    {
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = ? WHERE project_id = ? AND Semana = ? AND unique_id = ?",
            [$valor, self::PROJECT_ID, self::SEMANA, self::UID],
        );
    }

    public function testRegistraUnaEdicionDirecta(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.85);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', false);

        $this->assertTrue($inserto);
        $filas = $this->filasEnBitacora();
        $this->assertCount(1, $filas, 'una edicion deja exactamente una fila');
        $this->assertEqualsWithDelta(0.7, (float) $filas[0]['valor_anterior'], 0.001);
        $this->assertEqualsWithDelta(0.85, (float) $filas[0]['valor_nuevo'], 0.001);
        $this->assertSame('test.A', $filas[0]['usuario']);
    }

    public function testNoRegistraSiElValorNoCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.7);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', false);

        $this->assertFalse($inserto);
        $this->assertCount(0, $this->filasEnBitacora(), 'guardar sin cambiar no es una edicion');
    }

    /**
     * El residente vino a corregir otra cosa; la herencia le reemplazo el avance sin que lo
     * pidiera. No es una decision suya sobre ese numero, asi que no se firma.
     */
    public function testNoRegistraLaHerenciaSiLaAsociacionNoCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.55);
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', true);

        $this->assertFalse($inserto);
        $this->assertCount(0, $this->filasEnBitacora());
    }

    /**
     * El residente asocio la actividad a una anterior: decidio traer ese avance. Si se firma.
     */
    public function testRegistraLaHerenciaSiLaAsociacionCambio(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = 0.55, programaAnteriorAsociar = 'Campamentos semana 1'
             WHERE project_id = ? AND Semana = ? AND unique_id = ?",
            [self::PROJECT_ID, self::SEMANA, self::UID],
        );
        $inserto = $this->servicio->registrarSiCambio(self::PROJECT_ID, self::SEMANA, self::UID, $previo, 'test.A', true);

        $this->assertTrue($inserto);
        $filas = $this->filasEnBitacora();
        $this->assertCount(1, $filas);
        $this->assertEqualsWithDelta(0.55, (float) $filas[0]['valor_nuevo'], 0.001);
    }

    /**
     * El spec exige explicitamente que un fallo al escribir en la bitacora no se trague en
     * silencio: `registrarSiCambio()` debe devolver `false` y dejar rastro en el log de errores,
     * nunca dejar escapar la excepcion. Se fuerza un fallo real de INSERT tumbando la tabla (sin
     * mocks, como el resto de esta clase) y se recrea siempre al final, pase lo que pase, para no
     * dejar el entorno compartido roto para el resto de la suite.
     */
    public function testDevuelveFalseSiElInsertFalla(): void
    {
        $previo = $this->servicio->capturarAvancePrevio(self::PROJECT_ID, self::SEMANA, self::UID);
        $this->ponerAvance(0.9);

        $this->db->query('DROP TABLE pg_avance_edicion_manual');
        try {
            $inserto = $this->servicio->registrarSiCambio(
                self::PROJECT_ID,
                self::SEMANA,
                self::UID,
                $previo,
                'test.A',
                false,
            );

            $this->assertFalse($inserto, 'un INSERT que falla no debe propagar la excepcion');
        } finally {
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS `pg_avance_edicion_manual` (
                  `id` bigint NOT NULL AUTO_INCREMENT,
                  `project_id` int NOT NULL,
                  `Semana` int NOT NULL,
                  `unique_id` int NOT NULL,
                  `valor_anterior` decimal(12,6) DEFAULT NULL,
                  `valor_nuevo` decimal(12,6) DEFAULT NULL,
                  `usuario` varchar(100) DEFAULT NULL,
                  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_lookup` (`project_id`, `Semana`, `unique_id`, `fecha`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                  COMMENT='Ediciones manuales de Ejecutado en programa_consolidado; la consulta el arrastre'",
            );
        }
    }
}
