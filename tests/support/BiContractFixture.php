<?php

declare(strict_types=1);

/**
 * Datos sintéticos para los contratos de BI.
 *
 * Todo lo que siembra vive dentro de una transacción que `begin()` deja registrada para revertirse
 * al terminar el proceso: nada de esto llega a persistir.
 *
 * Antes sembraba dentro de los proyectos 73 (Da Porto) y 75, y eso traía dos problemas. Uno de
 * fondo: un test no debe escribir «Coordinación sintética CI» dentro de un proyecto de producción
 * ni siquiera en una transacción que después revierte — basta con que el rollback no llegue a
 * ejecutarse para contaminarlo. Y uno inmediato: las hojas colgaban de filas que el fixture daba
 * por existentes y dejaron de estarlo. Las FK `fk_ps__programa__consecutivo` y
 * `fk_pc__programa__consecutivo` exigen una fila de `programa` con ese `(project_id, Consecutivo)`,
 * y el `Consecutivo` de Da Porto hoy va de 1469 a 1741, no empieza en 1; el proyecto 75 ni siquiera
 * existe en `general_proyectos_procesos`. Los cinco tests que dependían de esto morían con un fatal
 * de integridad referencial.
 *
 * Ahora el fixture es autosuficiente: usa dos proyectos sacrificables propios y siembra él mismo
 * los padres que las FK piden (`programa` y `semanas_activas`) antes que las hojas. Deja de
 * depender de que un proyecto real tenga una forma concreta, así que no puede volver a romperse
 * porque los datos de producción evolucionen.
 *
 * Los tests son diferenciales —comparan `ControlTowerService` contra el mismo cálculo hecho con SQL
 * directo—, así que apuntarlos a un proyecto controlado no les quita valor: se lo añade, porque el
 * escenario deja de depender de qué haya hoy en la base.
 */
final class BiContractFixture
{
    /** Proyecto sacrificable principal (ocupa el sitio que tenía Da Porto en el fixture). */
    public const PROYECTO_A = 990200;

    /** Segundo proyecto sacrificable, para los escenarios multi-proyecto. */
    public const PROYECTO_B = 990201;

    private static bool $rollbackRegistered = false;

    private static bool $padresSembrados = false;

    public static function begin(Database $db): void
    {
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }
        if (self::$rollbackRegistered) {
            return;
        }

        self::$rollbackRegistered = true;
        register_shutdown_function(static function () use ($db): void {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        });
    }

    /**
     * Filas de `programa` y `semanas_activas` de las que cuelgan las hojas del fixture.
     *
     * Las FK apuntan a `programa` por DOS caminos independientes —`Consecutivo_En_Programa` contra
     * `programa.Consecutivo` y `unique_id` contra `programa.unique_id`—, así que cada valor que
     * usen las hojas tiene que existir en alguna fila de `programa` del mismo proyecto, aunque no
     * sea en la misma. Las tres filas de A cubren los `Consecutivo` 1, 2 y 101 y los `unique_id`
     * 101, 102 y 103; la de B cubre el par (1, 201).
     *
     * Es idempotente dentro del proceso porque los dos `seed*` públicos pueden llamarse juntos
     * (`test_bi_programa_general_chart_values` usa ambos) y las claves se repetirían.
     */
    private static function seedPadres(Database $db): void
    {
        if (self::$padresSembrados) {
            return;
        }
        self::$padresSembrados = true;

        $db->prepare(sprintf(
            "INSERT INTO programa (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
             VALUES
                (%1\$d, 101, 1, 'CI.PRG.A.1', 'Padre sintetico CI A1', 0, '2026-07-06', '2026-07-12'),
                (%1\$d, 102, 2, 'CI.PRG.A.2', 'Padre sintetico CI A2', 0, '2026-07-06', '2026-07-12'),
                (%1\$d, 103, 101, 'CI.PRG.A.3', 'Padre sintetico CI A3', 0, '2026-07-13', '2026-07-20'),
                (%2\$d, 201, 1, 'CI.PRG.B.1', 'Padre sintetico CI B1', 0, '2026-07-20', '2026-07-26')",
            self::PROYECTO_A,
            self::PROYECTO_B,
        ))->execute();

        $db->prepare(sprintf(
            "INSERT INTO semanas_activas
                (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCreacionSemana)
             VALUES
                (%1\$d, 1, 1, '2026-07-06', '2026-07-12', 0, '2026-07-01'),
                (%1\$d, 3, 3, '2026-07-20', '2026-07-27', 0, '2026-07-01'),
                (%2\$d, 3, 3, '2026-07-20', '2026-07-26', 0, '2026-07-01')",
            self::PROYECTO_A,
            self::PROYECTO_B,
        ))->execute();
    }

    public static function seedCausalRows(Database $db): void
    {
        self::begin($db);
        self::seedPadres($db);
        $statement = $db->prepare(sprintf(
            "INSERT INTO programacion_semanal (
                project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa,
                Id, Actividad, Ubicacion, Fecha_Inicio, Fecha_Fin, Sub_Contratista,
                Responsable_AIA, Empresa, Ejecutado, Unidad, cantidad_ppto, Compromiso,
                Ejecutado_Real, P_Completado, PAC, Critica, Atrasada, Activa,
                Prog_Sin_Restricciones_100, Categoria_CNP, CNP, Categoria_CNC, CNC
            ) VALUES
                (%1\$d, 9301, 9301, 1, 101, 1, 'CI.CNP.A', 'Coordinacion sintetica CI', 'Nivel CNP',
                 '2026-07-06', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0, 'm2', 10, 0, 0, 0, 0, 1, 0, '0', 0,
                 'Programacion', 'Coordinacion pendiente', NULL, NULL),
                (%2\$d, 9501, 9501, 3, 201, 1, 'CI.CNP.B', 'Revision sintetica CI', 'Mesa CNP',
                 '2026-07-20', '2026-07-26', 'Consultor CI Preconstruccion', 'Profesional CI Preconstruccion',
                 'AIA', 0, 'und', 10, 0, 0, 0, 0, 0, 0, '0', 0,
                 'Diseno', 'Revision pendiente', NULL, NULL),
                (%1\$d, 9303, 9303, 1, 102, 2, 'CI.CNP.A.2', 'Material sintetico CI', 'Nivel CNP',
                 '2026-07-07', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0, 'ml', 10, 0, 0, 0, 0, 0, 0, '0', 0,
                 'Materiales', 'Material pendiente', NULL, NULL),
                (%1\$d, 9302, 9302, 1, 102, 2, 'CI.CNC.A', 'Entrega sintetica CI', 'Nivel CNC',
                 '2026-07-06', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0.3, 'ml', 10, 10, 3, 0.3, 0, 1, 1, 'NA', 1,
                 NULL, NULL, 'Materiales', 'Entrega incompleta')",
            self::PROYECTO_A,
            self::PROYECTO_B,
        ));
        $statement->execute();
    }

    public static function seedProgramSnapshots(Database $db): void
    {
        self::begin($db);
        self::seedPadres($db);

        // La cuarta fila vive en B y comparte cohorte con las de A: es la que da contenido al
        // escenario multi-proyecto. Antes se intentaba con un UPDATE sobre el proyecto 75, que al
        // no existir lo dejaba en un no-op silencioso.
        //
        // La quinta está en la semana 1 con 'Proveedor CI Construccion' y es la cohorte
        // SOLO-HISTÓRICA: consultada en la semana 3 no aparece como actual (`Semana = 3`) pero sí
        // en la serie histórica (`Semana <= 3`). `test_bi_programa_general_chart_values` la llama
        // «real regression fixture» y comprueba que un match histórico no fabrique un pronóstico
        // actual; hasta ahora ninguna fila la sembraba y la comprobación no podía cumplirse.
        $db->prepare(sprintf(
            "INSERT INTO programa_consolidado (
                project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
                Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado,
                Estado, Estado_Restricciones, Sub_Contratista, Responsable_AIA, Activa,
                medir_productividad, cantidad_ppto, unidad
            ) VALUES
                (%1\$d, 9101, 9101, 3, 101, 1, 'CI.PG.A.1', 'Pintura reprogramada CI', 0,
                 '2026-07-06', '2026-07-26', 1, 0.40, 'En Curso', 0.70,
                 'Proveedor CI Nuevo', 'Responsable CI Compartido', 1, 1, 100, 'm2'),
                (%1\$d, 9102, 9102, 3, 102, 2, 'CI.PG.A.2', 'Red compartida CI', 0,
                 '2026-07-13', '2026-08-09', 1, 0.00, 'En Curso', 0.60,
                 'Cohorte CI Compartida', 'Responsable CI Compartido', 1, 1, 200, 'ml'),
                (%1\$d, 9103, 9103, 3, 103, 101, 'CI.PG.A.3', 'Ancla compartida CI', 0,
                 '2026-07-13', '2026-07-20', 1, 0.00, 'En Curso', 1.00,
                 'Cohorte CI Compartida', 'Responsable CI Compartido', 1, 1, 1, 'und'),
                (%2\$d, 9501, 9501, 3, 201, 1, 'CI.PG.B.1', 'Cohorte espejo CI', 0,
                 '2026-07-20', '2026-07-26', 1, 0.00, 'En Curso', 0.50,
                 'Cohorte CI Compartida', 'Responsable CI Compartido', 1, 1, 50, 'und'),
                (%1\$d, 9104, 9104, 1, 101, 1, 'CI.PG.A.0', 'Cohorte retirada CI', 0,
                 '2026-07-06', '2026-07-12', 1, 0.00, 'En Curso', 0.50,
                 'Proveedor CI Construccion', 'Responsable CI Compartido', 1, 1, 10, 'm2')",
            self::PROYECTO_A,
            self::PROYECTO_B,
        ))->execute();
    }
}
