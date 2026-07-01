<?php

/**
 * TableResolver — Punto único de resolución de nombres de tabla.
 *
 * Cuando USE_GLOBAL_TABLES=false (default): devuelve {prefix}_{tableType}
 * Cuando USE_GLOBAL_TABLES=true: devuelve {tableType} (tabla global)
 *
 * @package Core
 */
class TableResolver
{
    /**
     * Tipos de tabla válidos del sistema LPS.
     */
    private static array $validTables = [
        'actividades',
        'auto_contrato_log',
        'auto_program_log',
        'cambios',
        'cic',
        'cip',
        'indicadores_generales',
        'lps_drawer_comentarios',
        'lps_escalamientos',
        'pdc',
        'papelera_pdc',
        'pg_tracking',
        'pi_shared_constraints',
        'pi_shared_constraint_links',
        'profesionales',
        'programa',
        'programa_consolidado',
        'programacion_semanal',
        'semanas_activas',
        'semi_auto_assistant_feedback',
        'semi_auto_decisions',
        'semi_auto_feedback',
        'semi_auto_learning_candidates',
        'semi_auto_learning_rules',
        'semi_auto_project_config',
        'semi_auto_proactive_queue',
        'semi_auto_runs',
        'semi_auto_suggestions',
        'subcontratistas',
    ];

    /**
     * Cache del flag. Null = no leído aún.
     */
    private static ?bool $useGlobalTables = null;

    /**
     * Cache de prefijos (Base_de_Datos) por projectId.
     * Evita 50+ queries por page load re-consultando la misma info.
     */
    private static array $prefixCache = [];

    /**
     * Determina si se deben usar tablas globales (sin prefijo de proyecto).
     */
    public static function useGlobalTables(): bool
    {
        if (self::$useGlobalTables === null) {
            $flag = $_ENV['USE_GLOBAL_TABLES'] ?? getenv('USE_GLOBAL_TABLES') ?? 'false';
            self::$useGlobalTables = filter_var($flag, FILTER_VALIDATE_BOOLEAN);
        }

        return self::$useGlobalTables;
    }

    /**
     * Resuelve el nombre de tabla para un proyecto y tipo de tabla dados.
     *
     * @param int    $projectId  ID del proyecto (de general_proyectos_procesos.Id)
     * @param string $tableType  Tipo de tabla (ej: 'programa', 'actividades')
     *
     * @return string Nombre de tabla resuelto
     *
     * @throws InvalidArgumentException Si el proyecto no existe/inactivo o el tipo es inválido
     */
    public static function resolve(int $projectId, string $tableType): string
    {
        if (!in_array($tableType, self::$validTables, true)) {
            throw new InvalidArgumentException("Invalid table type: {$tableType}");
        }

        if (self::useGlobalTables()) {
            return $tableType;
        }

        // Check cache first — evita consultas repetidas a la BD
        if (!isset(self::$prefixCache[$projectId])) {
            $db = Database::getInstance();
            $stmt = $db->query(
                'SELECT Base_de_Datos FROM general_proyectos_procesos WHERE Id = ? AND Activo = 1',
                [$projectId]
            );
            $row = $stmt->fetch();

            if (!$row) {
                throw new InvalidArgumentException("Project not found or inactive: {$projectId}");
            }
            self::$prefixCache[$projectId] = $row['Base_de_Datos'];
        }

        return self::$prefixCache[$projectId] . '_' . $tableType;
    }

    /**
     * Resuelve el nombre de tabla usando el prefijo del proyecto directamente
     * (sin consultar la BD). Útil para código legacy que solo tiene $dbName.
     *
     * @param string $prefix    Prefijo del proyecto (Base_de_Datos, ej: 'prueba')
     * @param string $tableType Tipo de tabla (ej: 'programa', 'actividades')
     *
     * @return string Nombre de tabla resuelto
     *
     * @throws InvalidArgumentException Si el tipo de tabla es inválido
     */
    public static function resolveByPrefix(string $prefix, string $tableType): string
    {
        if (!in_array($tableType, self::$validTables, true)) {
            throw new InvalidArgumentException("Invalid table type: {$tableType}");
        }

        if (self::useGlobalTables()) {
            return $tableType;
        }

        return $prefix . '_' . $tableType;
    }

    /**
     * Limpia el cache de prefijos. Útil en tests y cuando se modifica
     * la tabla general_proyectos_procesos en caliente.
     */
    public static function clearCache(): void
    {
        self::$prefixCache = [];
    }

    /**
     * Obtiene el ID de proyecto a partir de su prefijo de base de datos.
     *
     * @param string $prefix Prefijo (Base_de_Datos)
     *
     * @return int|null ID del proyecto o null si no se encuentra
     */
    public static function getProjectIdByPrefix(string $prefix): ?int
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            'SELECT Id FROM general_proyectos_procesos WHERE Base_de_Datos = ? AND Activo = 1',
            [$prefix]
        );
        $row = $stmt->fetch();

        return $row ? (int) $row['Id'] : null;
    }

    /**
     * Devuelve la lista de tipos de tabla válidos.
     *
     * @return string[]
     */
    public static function getValidTables(): array
    {
        return self::$validTables;
    }

    /**
     * Override para testing — permite forzar el valor del flag.
     */
    public static function setUseGlobalTablesForTest(bool $value): void
    {
        self::$useGlobalTables = $value;
    }
}
