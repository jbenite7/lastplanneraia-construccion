<?php

declare(strict_types=1);

final class BiContractFixture
{
    private static bool $rollbackRegistered = false;

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

    public static function seedCausalRows(Database $db): void
    {
        self::begin($db);
        $statement = $db->prepare(
            "INSERT INTO programacion_semanal (
                project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_En_Programa,
                Id, Actividad, Ubicacion, Fecha_Inicio, Fecha_Fin, Sub_Contratista,
                Responsable_AIA, Empresa, Ejecutado, Unidad, cantidad_ppto, Compromiso,
                Ejecutado_Real, P_Completado, PAC, Critica, Atrasada, Activa,
                Prog_Sin_Restricciones_100, Categoria_CNP, CNP, Categoria_CNC, CNC
            ) VALUES
                (73, 9301, 9301, 1, 101, 1, 'CI.CNP.73', 'Coordinacion sintetica CI', 'Nivel CNP',
                 '2026-07-06', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0, 'm2', 10, 0, 0, 0, 0, 1, 0, '0', 0,
                 'Programacion', 'Coordinacion pendiente', NULL, NULL),
                (75, 9501, 9501, 3, 201, 1, 'CI.CNP.75', 'Revision sintetica CI', 'Mesa CNP',
                 '2026-07-20', '2026-07-26', 'Consultor CI Preconstruccion', 'Profesional CI Preconstruccion',
                 'AIA', 0, 'und', 10, 0, 0, 0, 0, 0, 0, '0', 0,
                 'Diseno', 'Revision pendiente', NULL, NULL),
                (73, 9303, 9303, 1, 102, 2, 'CI.CNP.73.2', 'Material sintetico CI', 'Nivel CNP',
                 '2026-07-07', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0, 'ml', 10, 0, 0, 0, 0, 0, 0, '0', 0,
                 'Materiales', 'Material pendiente', NULL, NULL),
                (73, 9302, 9302, 1, 102, 2, 'CI.CNC.73', 'Entrega sintetica CI', 'Nivel CNC',
                 '2026-07-06', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion',
                 'AIA', 0.3, 'ml', 10, 10, 3, 0.3, 0, 1, 1, 'NA', 1,
                 NULL, NULL, 'Materiales', 'Entrega incompleta')",
        );
        $statement->execute();
    }

    public static function seedProgramSnapshots(Database $db): void
    {
        self::begin($db);
        $db->prepare(
            "INSERT INTO semanas_activas
                (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCreacionSemana)
             VALUES
                (73, 3, 3, '2026-07-20', '2026-07-27', 0, '2026-07-01')",
        )->execute();

        $db->prepare(
            "INSERT INTO programa_consolidado (
                project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
                Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado,
                Estado, Estado_Restricciones, Sub_Contratista, Responsable_AIA, Activa,
                medir_productividad, cantidad_ppto, unidad
            ) VALUES
                (73, 9101, 9101, 3, 101, 1, 'CI.PG.73.1', 'Pintura reprogramada CI', 0,
                 '2026-07-06', '2026-07-26', 1, 0.40, 'En Curso', 0.70,
                 'Proveedor CI Nuevo', 'Responsable CI Compartido', 1, 1, 100, 'm2'),
                (73, 9102, 9102, 3, 102, 2, 'CI.PG.73.2', 'Red compartida CI', 0,
                 '2026-07-13', '2026-08-09', 1, 0.00, 'En Curso', 0.60,
                 'Cohorte CI Compartida', 'Responsable CI Compartido', 1, 1, 200, 'ml'),
                (73, 9103, 9103, 3, 103, 101, 'CI.PG.73.3', 'Ancla compartida CI', 0,
                 '2026-07-13', '2026-07-20', 1, 0.00, 'En Curso', 1.00,
                 'Cohorte CI Compartida', 'Responsable CI Compartido', 1, 1, 1, 'und')",
        )->execute();
        $db->prepare(
            "UPDATE programa_consolidado
             SET Sub_Contratista = 'Cohorte CI Compartida',
                 Responsable_AIA = 'Responsable CI Compartido',
                 Ejecutado = 0
             WHERE project_id = 75 AND Semana = 3 AND COALESCE(Titulo, 0) = 0",
        )->execute();
    }
}
