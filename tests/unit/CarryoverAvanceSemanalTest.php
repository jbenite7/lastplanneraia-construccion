<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\DataScope\ProjectScope;
use App\Services\WeeklyRealProgressCarryoverService;
use Database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TableResolver;

/**
 * Cubre el arrastre de avance de Programacion Semanal hacia Programa General
 * (`WeeklyRealProgressCarryoverService::syncWeek`), que hasta hoy no tenia ninguna prueba
 * pese a decidir el `Ejecutado` de todo el cronograma.
 *
 * El caso que fija esta clase es el defecto medido en produccion el 2026-08-25: un avance
 * reportado en la semanal DESPUES de que el Programa General de la semana siguiente ya se
 * abrio una vez no se suma nunca. En obra es el caso normal — la semana se crea el lunes y el
 * avance de la anterior se cierra el martes.
 *
 * La causa es el criterio de preservacion introducido por dd7fc2d3 (2026-07-06): para decidir
 * si el residente edito la celda a mano, compara contra el acumulado de la semana origen. Pero
 * la celda destino ya trae sumado el avance, asi que SIEMPRE difiere del origen en cuanto hay
 * algo reportado. Desde la segunda corrida el servicio confunde su propia escritura con una
 * edicion ajena y congela la fila.
 *
 * Nivel `db`: `syncWeek` resuelve nombres de tabla y project_id contra la base.
 */
#[Group('db')]
final class CarryoverAvanceSemanalTest extends TestCase
{
    private const PROJECT_ID = 990073;
    private const PREFIX = 'test_carryover_tmp';
    private const UID = 1;

    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->abrirAlcance();
        $this->limpiar();

        $this->db->query(
            "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo)
             VALUES (?, 'Carryover Test', ?, 'QA', 1)",
            [self::PROJECT_ID, self::PREFIX],
        );

        // `programa_consolidado` tiene FK contra `semanas_activas` por (project_id, Semana).
        foreach ([1 => ['2026-08-10', '2026-08-16'], 2 => ['2026-08-17', '2026-08-23']] as $semana => $fechas) {
            $this->db->query(
                "INSERT INTO semanas_activas (Id, project_id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
                 VALUES (?, ?, ?, ?, ?)",
                [990000 + $semana, self::PROJECT_ID, $semana, $fechas[0], $fechas[1]],
            );
        }

        // `programa_consolidado.Consecutivo_en_Programa` referencia `programa.Consecutivo`.
        $this->db->query(
            "INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad)
             VALUES (?, ?, ?, 'Campamentos')",
            [self::PROJECT_ID, self::UID, self::UID],
        );

        // Programa General: la actividad va en 70% al cerrar la semana 1. La semana 2 nace
        // como copia exacta de la 1, que es lo que hace `nueva_semana.php`.
        foreach ([1 => 1001, 2 => 1002] as $semana => $consecutivo) {
            $this->db->query(
                "INSERT INTO programa_consolidado
                    (project_id, Consecutivo, row_id, Semana, unique_id, Consecutivo_en_Programa,
                     Actividad, Titulo, Ejecutado, unidad)
                 VALUES (?, ?, ?, ?, ?, ?, 'Campamentos', 0, 0.7, '%')",
                [self::PROJECT_ID, $consecutivo, $consecutivo, $semana, self::UID, self::UID],
            );
        }

        // Programacion Semanal: el residente reporta 20 puntos en la semana 1.
        $this->db->query(
            "INSERT INTO programacion_semanal
                (project_id, Consecutivo, row_id, Semana, unique_id, Consecutivo_En_Programa,
                 Actividad, Unidad, Ejecutado_Real, Activa)
             VALUES (?, 2001, 2001, 1, ?, ?, 'Campamentos', '%', 20, '1')",
            [self::PROJECT_ID, self::UID, self::UID],
        );
    }

    protected function tearDown(): void
    {
        $this->limpiar();
        $this->db->dataScope()->clear();
    }

    /**
     * Estas pruebas hablan con la base directamente sobre una obra sintetica, y desde
     * ProjectSqlGuard toda consulta a tablas de proyecto exige un alcance activo. Se declara el de
     * esa obra —el mismo project_id que ya llevaban todas las consultas—, asi que el gate reescribe
     * el WHERE al valor que la prueba ya pedia y no cambia lo que mide.
     */
    private function abrirAlcance(): void
    {
        $contexto = $this->db->dataScope();
        $contexto->clear();
        $contexto->bind(new ProjectScope(self::PROJECT_ID, 'test-phpunit', 'A'));
    }


    private function limpiar(): void
    {
        foreach (['programa_consolidado', 'programacion_semanal', 'programa', 'semanas_activas'] as $tabla) {
            $this->db->query("DELETE FROM {$tabla} WHERE project_id = ?", [self::PROJECT_ID]);
        }
        $this->db->query("DELETE FROM general_proyectos_procesos WHERE Id = ?", [self::PROJECT_ID]);
    }

    private function ejecutadoEnSemana(int $semana): float
    {
        $valor = $this->db->query(
            "SELECT Ejecutado FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND unique_id = ?",
            [self::PROJECT_ID, $semana, self::UID],
        )->fetchColumn();

        return (float) $valor;
    }

    private function arrastrar(): void
    {
        (new WeeklyRealProgressCarryoverService($this->db))->syncWeek(self::PREFIX, 1, 2);
    }

    public function testSumaElAvanceReportadoEnLaSemanaOrigen(): void
    {
        $this->arrastrar();

        $this->assertEqualsWithDelta(
            0.90,
            $this->ejecutadoEnSemana(2),
            0.001,
            '70% acumulado mas 20 puntos reportados debe dar 90% en la semana siguiente',
        );
    }

    public function testElArrastreEsIdempotente(): void
    {
        $this->arrastrar();
        $this->arrastrar();

        $this->assertEqualsWithDelta(
            0.90,
            $this->ejecutadoEnSemana(2),
            0.001,
            'correr el arrastre dos veces no puede cambiar el resultado',
        );
    }

    /**
     * El defecto medido en produccion. El residente corrige la semanal despues de que el
     * Programa General ya se abrio una vez, que es lo que pasa cuando la semana se crea antes
     * de cerrar el avance de la anterior.
     */
    public function testSumaUnAvanceReportadoDespuesDeLaPrimeraCorrida(): void
    {
        $this->arrastrar();
        $this->assertEqualsWithDelta(0.90, $this->ejecutadoEnSemana(2), 0.001, 'precondicion');

        $this->db->query(
            "UPDATE programacion_semanal SET Ejecutado_Real = 30 WHERE project_id = ? AND Semana = 1 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        );

        $this->arrastrar();

        $this->assertEqualsWithDelta(
            1.00,
            $this->ejecutadoEnSemana(2),
            0.001,
            'el avance corregido en la semanal debe llegar al Programa General',
        );
    }

    /**
     * El caso del despliegue: filas que ya venian congeladas por el defecto, sin testigo.
     * El arrastre tiene que reconocer su propia escritura tambien ahi, o el arreglo solo
     * serviria para las semanas creadas despues del deploy y no destrabaria nada de lo que
     * hoy esta mal en produccion.
     */
    public function testDestrabaUnaFilaCongeladaSinTestigo(): void
    {
        // Estado heredado: el arrastre ya escribio 90% pero la columna testigo no existia.
        $this->arrastrar();
        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado_Carryover = NULL WHERE project_id = ? AND Semana = 2",
            [self::PROJECT_ID],
        );

        // Primera corrida con el codigo nuevo: reconoce el 90% como propio y fija el testigo.
        $this->arrastrar();

        $testigo = $this->db->query(
            "SELECT Ejecutado_Carryover FROM programa_consolidado WHERE project_id = ? AND Semana = 2 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        )->fetchColumn();
        $this->assertNotNull($testigo, 'la fila heredada debe quedar con testigo tras la primera corrida');

        // Y desde ahi el avance corregido vuelve a entrar.
        $this->db->query(
            "UPDATE programacion_semanal SET Ejecutado_Real = 30 WHERE project_id = ? AND Semana = 1 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        );
        $this->arrastrar();

        $this->assertEqualsWithDelta(
            1.00,
            $this->ejecutadoEnSemana(2),
            0.001,
            'una fila heredada debe destrabarse sola en la primera corrida',
        );
    }

    /**
     * La contraparte que no se puede romper al arreglar lo anterior: lo que el residente
     * escribe a mano manda, y el arrastre no puede pisarlo. Es el defecto que corrigio
     * dd7fc2d3 y que sigue vigente como requisito de producto.
     */
    public function testNoPisaLaEdicionManualDelResidente(): void
    {
        $this->arrastrar();

        $this->db->query(
            "UPDATE programa_consolidado SET Ejecutado = 0.55 WHERE project_id = ? AND Semana = 2 AND unique_id = ?",
            [self::PROJECT_ID, self::UID],
        );

        $this->arrastrar();

        $this->assertEqualsWithDelta(
            0.55,
            $this->ejecutadoEnSemana(2),
            0.001,
            'el valor que el residente escribio a mano no se puede perder',
        );
    }
}
