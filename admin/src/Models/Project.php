<?php

namespace Admin\Models;

use Database;

class Project
{
    private $db;
    private $table = 'general_proyectos_procesos';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get the total number of projects.
     *
     * @return int
     */
    public function count()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Checks if all required tables exist for each project.
     *
     * @return array List of projects with missing tables
     */
    public function getIntegrityReport()
    {
        $missing = [];
        foreach (Database::globalTableNames() as $table) {
            if (!$this->tableExists($table)) {
                $missing[] = "{$table} (tabla global)";
                continue;
            }

            if (!$this->tableHasColumn($table, 'project_id')) {
                $missing[] = "{$table}.project_id";
            }
        }

        if (empty($missing)) {
            return [];
        }

        return [[
            'id' => 0,
            'nombre' => 'Esquema global',
            'missing' => $missing,
        ]];
    }

    /**
     * Detects tables in the DB that follow the project pattern but don't belong to any project.
     * PROTECTS tables starting with 'general_'.
     *
     * @return array List of orphan tables
     */
    public function getOrphanTables()
    {
        $projects = $this->getAll();
        $validPrefixes = array_filter(array_column($projects, 'Base_de_Datos'));

        $stmt = $this->db->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $suffixes = [
            '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',
            '_programa', '_programacion_semanal', '_programa_consolidado',
            '_semanas_activas', '_subcontratistas',
        ];

        $orphans = [];
        foreach ($allTables as $table) {
            // SEGURIDAD: Ignorar tablas globales del sistema
            if (str_starts_with($table, 'general_')) {
                continue;
            }

            $isProjectTable = false;
            $currentPrefix = '';

            foreach ($suffixes as $suffix) {
                if (str_ends_with($table, $suffix)) {
                    $isProjectTable = true;
                    $currentPrefix = str_replace($suffix, '', $table);
                    break;
                }
            }

            if ($isProjectTable && !in_array($currentPrefix, $validPrefixes)) {
                $orphans[] = $table;
            }
        }

        return $orphans;
    }

    /**
     * Safely drop multiple tables.
     *
     * @param array $tables List of table names to drop
     * @return bool
     */
    public function dropTables($tables)
    {
        if (empty($tables)) {
            return true;
        }

        foreach ($tables as $table) {
            // Basic SQL injection protection for table names
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            if ($table === '' || str_starts_with($table, 'general_') || in_array($table, Database::globalTableNames(), true)) {
                continue;
            }

            try {
                $this->db->query("DROP TABLE IF EXISTS `{$table}`");
            } catch (\Exception $e) {
                error_log("Error dropping orphan table {$table}: " . $e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * Get the list of names of all active projects.
     *
     * @return array
     */
    public function getActiveNames()
    {
        $stmt = $this->db->query("SELECT Proyecto_Proceso FROM {$this->table} WHERE Activo = 1");

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Generates a full SQL dump of the entire database writing directly to a stream.
     * This prevents memory exhaustion for large databases.
     *
     * @param string $filePath Path where the SQL file will be saved
     * @return bool Success or failure
     */
    public function exportFullDatabaseToPath($filePath)
    {
        $handle = fopen($filePath, 'w');
        if (!$handle) {
            return false;
        }

        $stmt = $this->db->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        fwrite($handle, "-- Respaldo Completo de la Base de Datos\n");
        fwrite($handle, "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($allTables as $table) {
            // 1. Estructura
            $res = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $res->fetch();
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createRow['Create Table'] . ";\n\n");

            // Full database backup: keep unfiltered. Project exports use exportToSql(), scoped by project_id.
            $res = $this->db->query("SELECT * FROM `{$table}`");
            while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
                $values = array_map(function ($val) {
                    if (is_null($val)) {
                        return "NULL";
                    }

                    return $this->db->quote($val);
                }, $row);
                fwrite($handle, "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n");
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return true;
    }

    /**
     * Find a project by its ID.
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE Id = ? LIMIT 1", [$id]);

        return $stmt->fetch();
    }

    /**
     * Get all active projects.
     *
     * @return array
     */
    public function getAllActive()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE Activo = 1");

        return $stmt->fetchAll();
    }

    /**
     * Get all projects (including inactive).
     *
     * @return array
     */
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");

        return $stmt->fetchAll();
    }

    /**
     * Update an existing project and sync tables if prefix changes.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $oldProject = $this->find($id);
        if (!$oldProject) {
            error_log("Project update failed: ID {$id} not found.");

            return false;
        }

        $newPrefix = $data['base_datos'] ?? '';

        $fields = $this->projectPayloadFields($data, $newPrefix);
        $assignments = array_map(fn ($column) => "{$column} = ?", array_keys($fields));
        $params = array_values($fields);
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $assignments) . " WHERE Id = ?";
        $result = $this->db->query($sql, $params);

        if ($result && class_exists('\TableResolver')) {
            \TableResolver::clearCache();
        }

        return $result;
    }

    /**
     * Renombra las tablas del proyecto cuando cambia el prefijo.
     * Garantiza que no se pierdan datos al usar RENAME TABLE (operación de metadatos).
     *
     * @param string $oldPrefix
     * @param string $newPrefix
     * @param string $area
     * @return void
     */
    private function renameProjectTables($oldPrefix, $newPrefix, $area = 'Construccion')
    {
        $suffixes = [
            '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',
            '_programa', '_programacion_semanal', '_programa_consolidado',
            '_semanas_activas', '_subcontratistas',
        ];

        $renamePairs = [];
        foreach ($suffixes as $suffix) {
            $oldTable = "{$oldPrefix}{$suffix}";
            $newTable = "{$newPrefix}{$suffix}";

            try {
                // Verificar si la tabla antigua existe
                $stmt = $this->db->query("SHOW TABLES LIKE '{$oldTable}'");
                if ($stmt->fetch()) {
                    // Verificar si la tabla nueva ya existe para evitar colisiones y pérdida de datos
                    $checkNew = $this->db->query("SHOW TABLES LIKE '{$newTable}'");
                    if (!$checkNew->fetch()) {
                        $renamePairs[] = "`{$oldTable}` TO `{$newTable}`";
                    } else {
                        error_log("Data Safety: Se omitió el renombrado de {$oldTable} a {$newTable} porque la tabla destino ya existe.");
                    }
                }
            } catch (\Exception $e) {
                error_log("Error al verificar tabla {$oldTable}: " . $e->getMessage());
            }
        }

        // Ejecutar todos los renombres en una sola sentencia atómica si es posible
        if (!empty($renamePairs)) {
            $sql = "RENAME TABLE " . implode(', ', $renamePairs);
            try {
                $this->db->query($sql);
            } catch (\Exception $e) {
                error_log("Fallo en RENAME TABLE atómico: " . $e->getMessage() . ". Reintentando de forma individual.");
                foreach ($renamePairs as $pair) {
                    try {
                        $this->db->query("RENAME TABLE " . $pair);
                    } catch (\Exception $e2) {
                        error_log("Fallo en renombrado individual {$pair}: " . $e2->getMessage());
                    }
                }
            }
        }

        // Asegurar que todas las tablas existan para el nuevo prefijo (crear las faltantes si no existían en el viejo)
        $this->createProjectTables($newPrefix, $area);
    }


    /**
     * Create a new project and its associated tables.
     *
     * @param array $data
     * @return bool
     */
    public function create($data)
    {
        // Auto-generate Base_de_Datos if not provided
        $base_datos = $this->generateDatabaseName($data['nombre'], $data['area']);

        // Validate uniqueness of Base_de_Datos across ALL projects
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM {$this->table} WHERE Base_de_Datos = ?",
            [$base_datos]
        );
        $row = $stmt->fetch();
        if (($row['cnt'] ?? 0) > 0) {
            error_log("Project create failed: Base_de_Datos '{$base_datos}' already exists.");
            return false;
        }

        $fields = $this->projectPayloadFields($data, $base_datos);
        $columns = array_keys($fields);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $result = $this->db->query($sql, array_values($fields));

        if ($result && $base_datos) {
            $projectId = (int) $this->db->lastInsertId();
            if ($projectId <= 0) {
                $projectId = (int) $this->db->query(
                    "SELECT Id FROM {$this->table} WHERE Base_de_Datos = ? LIMIT 1",
                    [$base_datos]
                )->fetchColumn();
            }

            if ($projectId > 0) {
                $this->initializeProjectDefaults($projectId, $data);
            }
        }

        return $result;
    }

    /**
     * Crea las tablas específicas para el proyecto basadas en la plantilla.
     *
     * @param string $prefix El prefijo (Base_de_Datos) para las tablas.
     * @param string $area   Área del proyecto ('Construccion', 'Pre-Construccion').
     * @return void
     */
    private function createProjectTables($prefix, $area = 'Construccion')
    {
        // Pre-Construccion: tablas completas + columnas PC
        if (strtoupper($area) === 'PRE-CONSTRUCCION') {
            $this->createPreConstructionTables($prefix);
            return;
        }

        if (\TableResolver::useGlobalTables()) {
            // Modo global: crear solo tablas globales (sin prefijo, con project_id)
            $this->createGlobalTables();
        } else {
            // Legacy: crear tablas por-proyecto (compatibilidad hacia atrás)
            foreach ($this->getProjectTableQueries($prefix) as $sql) {
                $this->db->query($sql);
            }
        }
    }

    /**
     * Crea las 10 tablas globales (sin prefijo de proyecto) con project_id.
     * Solo se ejecuta cuando USE_GLOBAL_TABLES=true.
     *
     * @return void
     */
    private function createGlobalTables()
    {
        $suffixes = $this->getTableSuffixes();
        foreach ($suffixes as $suffix) {
            // Generar CREATE TABLE sin prefijo y agregar project_id como primera columna
            $tableType = ltrim($suffix, '_');
            $queries = $this->getProjectTableQueries('__placeholder__');
            $idx = array_search($suffix, $suffixes);
            if ($idx !== false && isset($queries[$idx])) {
                $sql = $queries[$idx];
                // Reemplazar el prefijo place holder por el nombre de tabla global
                $sql = str_replace('__placeholder__' . $suffix, $tableType, $sql);
                // Insertar project_id después de AUTO_INCREMENT PRIMARY KEY
                $sql = preg_replace(
                    '/(AUTO_INCREMENT PRIMARY KEY)/',
                    '$1,`project_id` int NOT NULL',
                    $sql
                );
                // Añadir índice en project_id
                $sql = rtrim($sql, ')');
                $sql .= ', KEY `idx_project_id` (`project_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci';
                $this->db->query($sql);
            }
        }
    }

    /**
     * Retorna los sufijos de tabla estándar del proyecto.
     *
     * @return string[]
     */
    private function getTableSuffixes()
    {
        return [
            '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',
            '_programa', '_programacion_semanal', '_programa_consolidado',
            '_semanas_activas', '_subcontratistas',
        ];
    }

    /**
     * Retorna las 10 consultas CREATE TABLE estándar para proyectos.
     * Las tablas se crean con IF NOT EXISTS para idempotencia.
     *
     * @param string $prefix Prefijo de base de datos del proyecto.
     * @return array
     */
    private function getProjectTableQueries($prefix)
    {
        return [
            // milanCampestre_actividades
            "CREATE TABLE IF NOT EXISTS `{$prefix}_actividades` (
              `Id` int(4) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `codigo` int(4) NOT NULL,
              `actividad` varchar(300) NOT NULL,
              `descripcionActividad` mediumtext DEFAULT NULL,
              `actividadInicio` varchar(500) DEFAULT NULL,
              `nombreActividadInicio` varchar(500) DEFAULT NULL,
              `fechaInicio` date DEFAULT NULL,
              `tipoContrato` varchar(10) DEFAULT NULL,
              `semanaActualizacion` int(11) DEFAULT NULL,
              `SI1` varchar(200) DEFAULT NULL,
              `paqueteSI1` varchar(200) DEFAULT NULL,
              `SI2` varchar(200) DEFAULT NULL,
              `paqueteSI2` varchar(200) DEFAULT NULL,
              `SI3` varchar(200) DEFAULT NULL,
              `paqueteSI3` varchar(200) DEFAULT NULL,
              `SI4` varchar(200) DEFAULT NULL,
              `paqueteSI4` varchar(200) DEFAULT NULL,
              `SI5` varchar(200) DEFAULT NULL,
              `paqueteSI5` varchar(200) DEFAULT NULL,
              `S1` varchar(200) DEFAULT NULL,
              `paqueteS1` varchar(200) DEFAULT NULL,
              `S2` varchar(200) DEFAULT NULL,
              `paqueteS2` varchar(200) DEFAULT NULL,
              `S3` varchar(200) DEFAULT NULL,
              `paqueteS3` varchar(200) DEFAULT NULL,
              `S4` varchar(200) DEFAULT NULL,
              `paqueteS4` varchar(200) DEFAULT NULL,
              `S5` varchar(200) DEFAULT NULL,
              `paqueteS5` varchar(200) DEFAULT NULL,
              `MO1` varchar(200) DEFAULT NULL,
              `paqueteMO1` varchar(200) DEFAULT NULL,
              `MO2` varchar(200) DEFAULT NULL,
              `paqueteMO2` varchar(200) DEFAULT NULL,
              `MO3` varchar(200) DEFAULT NULL,
              `paqueteMO3` varchar(200) DEFAULT NULL,
              `MO4` varchar(200) DEFAULT NULL,
              `paqueteMO4` varchar(200) DEFAULT NULL,
              `MO5` varchar(200) DEFAULT NULL,
              `paqueteMO5` varchar(200) DEFAULT NULL,
              `OC1` varchar(200) DEFAULT NULL,
              `paqueteOC1` varchar(200) DEFAULT NULL,
              `OC2` varchar(200) DEFAULT NULL,
              `paqueteOC2` varchar(200) DEFAULT NULL,
              `OC3` varchar(200) DEFAULT NULL,
              `paqueteOC3` varchar(200) DEFAULT NULL,
              `OC4` varchar(200) DEFAULT NULL,
              `paqueteOC4` varchar(200) DEFAULT NULL,
              `OC5` varchar(200) DEFAULT NULL,
              `paqueteOC5` varchar(200) DEFAULT NULL,
              `numeroSubcontratos` tinyint NOT NULL DEFAULT 1,
              `confianza_deteccion` decimal(5,2) DEFAULT NULL,
              `ultimo_auto_definir` datetime DEFAULT NULL,
              `fechaInicioProyectada` date DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_cambios
            "CREATE TABLE IF NOT EXISTS `{$prefix}_cambios` (
              `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `solicitanteCambio` int(11) DEFAULT NULL,
              `detalleSolicitanteOtro` longtext DEFAULT NULL,
              `fechaSolicitud` date DEFAULT NULL,
              `prioridad` int(11) DEFAULT NULL,
              `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
              `responsableSolucion` int(11) DEFAULT NULL,
              `detalleResponsableSolucion` longtext DEFAULT NULL,
              `justificacion` longtext DEFAULT NULL,
              `descripcion` longtext DEFAULT NULL,
              `incidenciaAlcance` longtext DEFAULT NULL,
              `tiempoCronograma` float DEFAULT NULL,
              `tiempoCronogramaAfectado` float DEFAULT NULL,
              `incidenciaCronograma` longtext DEFAULT NULL,
              `valorPresupuesto` float DEFAULT NULL,
              `costoDirecto` float DEFAULT NULL,
              `costoDirectoAIU` float DEFAULT NULL,
              `costoDirectoAIUIVA` float DEFAULT NULL,
              `valorAprobado` float DEFAULT NULL,
              `incidenciaPresupuesto` longtext DEFAULT NULL,
              `incidenciaCalidad` longtext DEFAULT NULL,
              `incidenciaRiesgo` longtext DEFAULT NULL,
              `incidenciaRecurso` longtext DEFAULT NULL,
              `fechaTentativaDefinicion` date DEFAULT NULL,
              `fechaEntregaInterventoria` date DEFAULT NULL,
              `Observaciones` longtext DEFAULT NULL,
              `fechaDefinicion` date DEFAULT NULL,
              `aprobacion` int(11) DEFAULT NULL,
              `soportes` longtext DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_cic
            "CREATE TABLE IF NOT EXISTS `{$prefix}_cic` (
              `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Semana` int(3) DEFAULT NULL,
              `subcontratista` varchar(200) DEFAULT NULL,
              `correo_contacto` varchar(200) DEFAULT NULL,
              `NIT` varchar(10) DEFAULT NULL,
              `alcance` varchar(200) DEFAULT NULL,
              `tipo_proveedor` varchar(200) DEFAULT NULL,
              `PAC` varchar(11) DEFAULT 'NA',
              `PAC_Acum` varchar(11) DEFAULT 'NA',
              `P_Completado` varchar(11) DEFAULT 'NA',
              `P_Completado_Acum` varchar(11) DEFAULT 'NA',
              `Calidad` varchar(11) NOT NULL DEFAULT 'NR',
              `Calidad_Acum` varchar(11) NOT NULL DEFAULT 'NR',
              `GSA` varchar(11) NOT NULL DEFAULT 'NR',
              `GSA_Acum` varchar(11) NOT NULL DEFAULT 'NR',
              `SST` varchar(11) NOT NULL DEFAULT 'NR',
              `SST_Acum` varchar(11) NOT NULL DEFAULT 'NR',
              `ADM` varchar(11) NOT NULL DEFAULT 'NR',
              `ADM_Acum` varchar(11) NOT NULL DEFAULT 'NR',
              `Cal_Integral` float DEFAULT NULL,
              `Cal_Integral_Acum` float DEFAULT NULL,
              `Observaciones` mediumtext DEFAULT NULL,
              `mdo_cal_1` varchar(5) DEFAULT 'NR',
              `mdo_cal_2` varchar(5) DEFAULT 'NR',
              `mdo_cal_3` varchar(5) DEFAULT 'NR',
              `mdo_adm_1` varchar(5) DEFAULT 'NR',
              `mdo_adm_2` varchar(5) DEFAULT 'NR',
              `mdo_adm_3` varchar(5) DEFAULT 'NR',
              `mdo_adm_4` varchar(5) DEFAULT 'NR',
              `mdo_adm_5` varchar(5) DEFAULT 'NR',
              `mdo_gsa_1` varchar(5) DEFAULT 'NR',
              `mdo_gsa_2` varchar(5) DEFAULT 'NR',
              `mdo_gsa_3` varchar(5) DEFAULT 'NR',
              `mdo_gsa_4` varchar(5) DEFAULT 'NR',
              `mdo_gsa_5` varchar(5) DEFAULT 'NR',
              `mdo_gsa_6` varchar(5) DEFAULT 'NR',
              `mdo_gsa_7` varchar(5) DEFAULT 'NR',
              `mdo_gsa_8` varchar(5) DEFAULT 'NR',
              `mdo_sst_1` varchar(5) DEFAULT 'NR',
              `mdo_sst_2` varchar(5) DEFAULT 'NR',
              `mdo_sst_3` varchar(5) DEFAULT 'NR',
              `mdo_sst_4` varchar(5) DEFAULT 'NR',
              `mdo_sst_5` varchar(5) DEFAULT 'NR',
              `mdo_sst_6` varchar(5) DEFAULT 'NR',
              `mdo_sst_7` varchar(5) DEFAULT 'NR',
              `mdo_sst_8` varchar(5) DEFAULT 'NR',
              `mdo_sst_9` varchar(5) DEFAULT 'NR',
              `mdo_sst_10` varchar(5) DEFAULT 'NR',
              `si_cal_1` varchar(5) DEFAULT 'NR',
              `si_cal_2` varchar(5) DEFAULT 'NR',
              `si_cal_3` varchar(5) DEFAULT 'NR',
              `si_adm_1` varchar(5) DEFAULT 'NR',
              `si_adm_2` varchar(5) DEFAULT 'NR',
              `si_adm_3` varchar(5) DEFAULT 'NR',
              `si_adm_4` varchar(5) DEFAULT 'NR',
              `si_adm_5` varchar(5) DEFAULT 'NR',
              `si_adm_6` varchar(5) DEFAULT 'NR',
              `si_gsa_1` varchar(5) DEFAULT 'NR',
              `si_gsa_2` varchar(5) DEFAULT 'NR',
              `si_gsa_3` varchar(5) DEFAULT 'NR',
              `si_gsa_4` varchar(5) DEFAULT 'NR',
              `si_gsa_5` varchar(5) DEFAULT 'NR',
              `si_gsa_6` varchar(5) DEFAULT 'NR',
              `si_gsa_7` varchar(5) DEFAULT 'NR',
              `si_gsa_8` varchar(5) DEFAULT 'NR',
              `si_gsa_9` varchar(5) DEFAULT 'NR',
              `si_gsa_10` varchar(5) DEFAULT 'NR',
              `si_gsa_11` varchar(5) DEFAULT 'NR',
              `si_gsa_12` varchar(5) DEFAULT 'NR',
              `si_gsa_13` varchar(5) DEFAULT 'NR',
              `si_gsa_14` varchar(5) DEFAULT 'NR',
              `si_sst_1` varchar(5) DEFAULT 'NR',
              `si_sst_2` varchar(5) DEFAULT 'NR',
              `si_sst_3` varchar(5) DEFAULT 'NR',
              `si_sst_4` varchar(5) DEFAULT 'NR',
              `si_sst_5` varchar(5) DEFAULT 'NR',
              `si_sst_6` varchar(5) DEFAULT 'NR',
              `si_sst_7` varchar(5) DEFAULT 'NR',
              `si_sst_8` varchar(5) DEFAULT 'NR',
              `si_sst_9` varchar(5) DEFAULT 'NR',
              `si_sst_10` varchar(5) DEFAULT 'NR'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_pdc
            "CREATE TABLE IF NOT EXISTS `{$prefix}_pdc` (
              `consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `semana` int(11) NOT NULL,
              `titulo` int(1) NOT NULL,
              `tipoPaquete` varchar(200) NOT NULL,
              `paqueteContratacion` varchar(200) DEFAULT NULL,
              `contratos` varchar(200) DEFAULT NULL,
              `numeroSubcontratos` int(11) DEFAULT 1,
              `subcontratoPaquete` int(11) NOT NULL DEFAULT 1,
              `estado` varchar(200) DEFAULT NULL,
              `fechaElaboracionPliegos` date DEFAULT NULL,
              `diasElaboracionPliegos` int(11) DEFAULT NULL,
              `fechaRealElaboracionPliegos` date DEFAULT NULL,
              `fechaEntregaPliegos` date DEFAULT NULL,
              `fechaEntregaPliegos` date DEFAULT NULL,
              `diasEntregaPliegos` int(11) DEFAULT NULL,
              `fechaRealEntregaPliegos` date DEFAULT NULL,
              `fechaReciboPropuestas` date DEFAULT NULL,
              `diasReciboPropuestas` int(11) DEFAULT NULL,
              `fechaRealReciboPropuestas` date DEFAULT NULL,
              `fechaCuadrosComparativos` date DEFAULT NULL,
              `diasCuadrosComparativos` int(11) DEFAULT NULL,
              `fechaRealCuadrosComparativos` date DEFAULT NULL,
              `fechaLegalizacionContrato` date DEFAULT NULL,
              `diasLegalizacionContrato` int(11) DEFAULT NULL,
              `fechaRealLegalizacionContrato` date DEFAULT NULL,
              `fechaFabricacion` date DEFAULT NULL,
              `diasFabricacion` int(11) DEFAULT NULL,
              `fechaRealFabricacion` date DEFAULT NULL,
              `fechaInsumosObra` date DEFAULT NULL,
              `diasInsumosObra` int(11) DEFAULT NULL,
              `fechaRealInsumosObra` date DEFAULT NULL,
              `fechaInicio` date DEFAULT NULL,
              `fechaInicioProyectada` date DEFAULT NULL,
              `fechaRealInicio` date DEFAULT NULL,
              `idProveedorAdjudicado` int(11) DEFAULT NULL,
              `numeroContrato` varchar(50) DEFAULT NULL,
              `aplicaPolizas` int(1) NOT NULL DEFAULT 1,
              `fechaVencimientoPolizas` date DEFAULT NULL,
              `valorPresupuesto` float DEFAULT NULL,
              `valorPrimeraNegociacion` float DEFAULT NULL,
              `valorAdjudicado` float DEFAULT NULL,
              `valorAnticipo` float DEFAULT NULL,
              `valorReclamado` float DEFAULT NULL,
              `valorDevoluciones` float DEFAULT NULL,
              `observacionesContrato` mediumtext DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_profesionales
            "CREATE TABLE IF NOT EXISTS `{$prefix}_profesionales` (
              `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `nombre` varchar(100) NOT NULL,
              `email` varchar(100) NOT NULL,
              `cargo` varchar(100) NOT NULL,
              `activo` int(11) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_programa
            "CREATE TABLE IF NOT EXISTS `{$prefix}_programa` (
              `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Id` varchar(500) DEFAULT NULL,
              `Actividad` varchar(500) DEFAULT NULL,
              `Titulo` int(11) DEFAULT NULL,
              `Fecha_Inicio` date DEFAULT NULL,
              `Fecha_Fin` date DEFAULT NULL,
              `Ruta_Critica` int(11) DEFAULT NULL,
              `Ejecutado` float DEFAULT 0,
              `Estado` varchar(50) DEFAULT NULL,
              `Semanas_Inicio` int(1) DEFAULT 0,
              `Estado_Restricciones` float DEFAULT 0,
              `D_y_E` float DEFAULT 0,
              `Materiales` float DEFAULT 0,
              `MdeO` float DEFAULT 0,
              `Equipos` float DEFAULT 0,
              `Predecesora` float DEFAULT 0,
              `Pdto_Cons` float DEFAULT 0,
              `Modelo` varchar(9) DEFAULT '0',
              `Responsable_AIA` varchar(100) DEFAULT NULL,
              `Observaciones` mediumtext DEFAULT NULL,
              `Ult_Act_Est` date DEFAULT NULL,
              `Ult_Act_Restr` date DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_programacion_semanal
            "CREATE TABLE IF NOT EXISTS `{$prefix}_programacion_semanal` (
              `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Semana` int(3) DEFAULT NULL,
              `Consecutivo_En_Programa` int(11) NOT NULL,
              `Id` varchar(500) DEFAULT NULL,
              `Actividad` varchar(500) DEFAULT NULL,
              `Descripcion` mediumtext DEFAULT NULL,
              `Ubicacion` mediumtext DEFAULT NULL,
              `Fecha_Inicio` date DEFAULT NULL,
              `Fecha_Fin` date DEFAULT NULL,
              `Sub_Contratista` varchar(200) DEFAULT NULL,
              `Responsable_AIA` varchar(200) DEFAULT NULL,
              `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
              `Ejecutado` float DEFAULT NULL,
              `medir_productividad` int(11) DEFAULT 0,
              `Unidad` varchar(10) DEFAULT NULL,
              `cantidad_ppto` int(11) DEFAULT NULL,
              `Cantidad_Sugerida` float DEFAULT NULL,
              `Compromiso` float DEFAULT NULL,
              `Ejecutado_Real` float DEFAULT NULL,
              `P_Completado` float DEFAULT NULL,
              `PAC` int(1) DEFAULT NULL,
              `Critica` int(1) DEFAULT NULL,
              `Atrasada` int(1) DEFAULT NULL,
              `Activa` varchar(3) DEFAULT NULL,
              `Prog_Sin_Restricciones_100` int(1) DEFAULT NULL,
              `Categoria_CNP` varchar(100) DEFAULT NULL,
              `CNP` varchar(100) DEFAULT NULL,
              `Observaciones_CNP` mediumtext DEFAULT NULL,
              `Categoria_CNC` varchar(100) DEFAULT NULL,
              `CNC` varchar(100) DEFAULT NULL,
              `Observaciones_CNC` mediumtext DEFAULT NULL,
              `Rendimientos` varchar(500) DEFAULT NULL,
              `codigo_actividad` varchar(11) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_programa_consolidado
            "CREATE TABLE IF NOT EXISTS `{$prefix}_programa_consolidado` (
              `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Semana` int(3) NOT NULL,
              `Consecutivo_en_Programa` int(11) NOT NULL,
              `Id` varchar(500) DEFAULT NULL,
              `Actividad` varchar(500) DEFAULT NULL,
              `Titulo` int(11) DEFAULT NULL,
              `Fecha_Inicio` date DEFAULT NULL,
              `Fecha_Fin` date DEFAULT NULL,
              `Ruta_Critica` int(11) DEFAULT NULL,
              `Ejecutado` float DEFAULT 0,
              `Estado` varchar(100) DEFAULT NULL,
              `Semanas_Inicio` int(10) DEFAULT 0,
              `Estado_Restricciones` float NOT NULL DEFAULT 0,
              `D_y_E` varchar(9) NOT NULL DEFAULT '0',
              `Materiales` varchar(9) NOT NULL DEFAULT '0',
              `MdeO` varchar(9) NOT NULL DEFAULT '0',
              `Equipos` varchar(9) NOT NULL DEFAULT '0',
              `Predecesora` varchar(9) NOT NULL DEFAULT '0',
              `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
              `Modelo` varchar(9) NOT NULL DEFAULT '0',
              `Sub_Contratista` varchar(100) DEFAULT NULL,
              `Responsable_AIA` varchar(100) DEFAULT NULL,
              `Observaciones` mediumtext DEFAULT NULL,
              `Ult_Act_Est` date DEFAULT NULL,
              `Ult_Act_Restr` date DEFAULT NULL,
              `Activa` int(1) NOT NULL DEFAULT 0,
              `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
              `codigo_actividad` varchar(11) DEFAULT NULL,
              `medir_productividad` int(11) DEFAULT 0,
              `cantidad_ppto` int(11) DEFAULT NULL,
              `unidad` varchar(20) DEFAULT NULL,
              `programaAnteriorAsociar` varchar(500) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_semanas_activas
            "CREATE TABLE IF NOT EXISTS `{$prefix}_semanas_activas` (
              `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `Semana` int(11) NOT NULL,
              `Fecha_Inicio_Sem` date NOT NULL,
              `Fecha_Fin_Sem` date NOT NULL,
              `Semanal_Confirmada` int(1) DEFAULT 0,
              `fechaCierreCompromisos` date DEFAULT NULL,
              `fechaCreacionSemana` date DEFAULT NULL,
              `reprogramacion` int(11) NOT NULL DEFAULT 0,
              `diferenciaEstructuraCron` int(11) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            // milanCampestre_subcontratistas
            "CREATE TABLE IF NOT EXISTS `{$prefix}_subcontratistas` (
              `Id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `subcontratista` varchar(200) NOT NULL,
              `correo_contacto` varchar(200) NOT NULL,
              `NIT` bigint(10) NOT NULL,
              `alcance` varchar(200) NOT NULL,
              `tipo_proveedor` varchar(200) NOT NULL,
              `activo` int(11) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",
        ];
    }


    /**
     * Crea las 16 tablas estándar para proyectos de Pre-Construccion
     * (misma estructura que Construccion) más las columnas específicas de PC.
     *
     * 1. Crea las 10 tablas estándar via getProjectTableQueries()
     * 2. Crea las 6 tablas adicionales (_auto_program_log, _lps_*, _pg_tracking, _pi_shared_*)
     * 3. Aplica columnas PC específicas via applyPcColumnModifications()
     *
     * @param string $prefix El prefijo (Base_de_Datos) para las tablas.
     * @return void
     */
    private function createPreConstructionTables($prefix)
    {
        // Paso 1: 10 tablas estándar
        if (\TableResolver::useGlobalTables()) {
            $this->createGlobalTables();
        } else {
            // Legacy: crear tablas por-proyecto
            foreach ($this->getProjectTableQueries($prefix) as $sql) {
                $this->db->query($sql);
            }
        }

        // Paso 2: 6 tablas adicionales estándar (creadas por patches en Construccion)
        $extraQueries = [
            // _auto_program_log
            "CREATE TABLE IF NOT EXISTS `{$prefix}_auto_program_log` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `semana` INT NOT NULL,
                `consecutivo` INT NOT NULL,
                `accion` ENUM('comprometer','descomprometer','insert_cnp') NOT NULL,
                `detalle` TEXT,
                `categoria_cnp` VARCHAR(100) DEFAULT NULL,
                `cnp` VARCHAR(100) DEFAULT NULL,
                `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_semana` (`semana`),
                KEY `idx_consecutivo` (`consecutivo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // _lps_escalamientos
            "CREATE TABLE IF NOT EXISTS `{$prefix}_lps_escalamientos` (
                `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `proyecto_id` int NOT NULL,
                `semana` int NOT NULL,
                `consecutivo_en_programa` int NOT NULL,
                `modulo` ENUM('PG','PI','PS') NOT NULL,
                `trigger_origen` varchar(50) NOT NULL,
                `nivel_actual` tinyint NOT NULL DEFAULT 1,
                `estado` ENUM('Activo','Mitigado','Cerrado') NOT NULL DEFAULT 'Activo',
                `fecha_detonacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `fecha_ultimo_escalamiento` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                `fecha_cierre` timestamp NULL DEFAULT NULL,
                `usuario_cierre_id` int DEFAULT NULL,
                `justificacion_cierre` mediumtext,
                KEY `idx_semana_consecutivo` (`semana`,`consecutivo_en_programa`),
                KEY `idx_estado_nivel` (`estado`,`nivel_actual`),
                KEY `idx_proyecto` (`proyecto_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // _lps_drawer_comentarios (sin FK para idempotencia)
            "CREATE TABLE IF NOT EXISTS `{$prefix}_lps_drawer_comentarios` (
                `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `proyecto_id` int NOT NULL,
                `consecutivo_en_programa` int NOT NULL,
                `semana` int NOT NULL,
                `usuario_id` int NOT NULL,
                `comentario` mediumtext NOT NULL,
                `escalamiento_id` int DEFAULT NULL,
                `parent_id` int DEFAULT NULL,
                `menciones` json DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_comentario_actividad` (`consecutivo_en_programa`,`semana`),
                KEY `idx_parent` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // _pg_tracking
            "CREATE TABLE IF NOT EXISTS `{$prefix}_pg_tracking` (
                `consecutivo_en_programa` INT NOT NULL,
                `semana` INT NOT NULL,
                `fecha_inicio` DATE DEFAULT NULL,
                `fecha_fin` DATE DEFAULT NULL,
                `estado` VARCHAR(100) DEFAULT NULL,
                `restricciones_hash` CHAR(32) DEFAULT NULL,
                `fechas_hash` CHAR(32) DEFAULT NULL,
                `estado_hash` CHAR(32) DEFAULT NULL,
                `titulo` TINYINT(1) NOT NULL DEFAULT 0,
                `ultimo_detectado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`consecutivo_en_programa`, `semana`),
                KEY `idx_semana` (`semana`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // _pi_shared_constraints
            "CREATE TABLE IF NOT EXISTS `{$prefix}_pi_shared_constraints` (
                `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `Semana` int NOT NULL,
                `Restriccion` varchar(40) NOT NULL,
                `ValorObjetivo` varchar(20) NOT NULL,
                `Nota` text,
                `CreadoPor` varchar(120) DEFAULT NULL,
                `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_semana` (`Semana`),
                KEY `idx_restriccion` (`Restriccion`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // _pi_shared_constraint_links
            "CREATE TABLE IF NOT EXISTS `{$prefix}_pi_shared_constraint_links` (
                `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `SharedConstraintId` bigint unsigned NOT NULL,
                `Semana` int NOT NULL,
                `ConsecutivoEnPrograma` varchar(64) NOT NULL,
                `ValorAplicado` varchar(20) NOT NULL,
                `OverrideLocal` tinyint(1) NOT NULL DEFAULT 0,
                `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_shared` (`SharedConstraintId`),
                KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($extraQueries as $sql) {
            $this->db->query($sql);
        }

        // Paso 3: Columnas PC específicas (idempotente via information_schema)
        $this->applyPcColumnModifications($prefix);
    }

    /**
     * Aplica columnas e índices específicos de Pre-Construccion
     * a las tablas estándar. Usa information_schema para ser idempotente.
     *
     * @param string $prefix Prefijo de base de datos del proyecto.
     * @return void
     */
    private function applyPcColumnModifications($prefix)
    {
        // Columnas PC por tabla
        $tableColumns = [
            "{$prefix}_programa" => [
                ['name' => 'restriccion_pc_1', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_2', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_3', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_4', 'def' => "VARCHAR(10) DEFAULT '0%'"],
            ],
            "{$prefix}_programa_consolidado" => [
                ['name' => 'restriccion_pc_1', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_2', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_3', 'def' => "VARCHAR(10) DEFAULT '0%'"],
                ['name' => 'restriccion_pc_4', 'def' => "VARCHAR(10) DEFAULT '0%'"],
            ],
            "{$prefix}_programacion_semanal" => [
                ['name' => 'Reprogramada_Por_Usuario', 'def' => "TINYINT(1) NOT NULL DEFAULT 0"],
                ['name' => 'Es_TNP', 'def' => "TINYINT(1) NOT NULL DEFAULT 0"],
                ['name' => 'Categoria_CP', 'def' => "VARCHAR(100) DEFAULT NULL"],
                ['name' => 'CP', 'def' => "VARCHAR(255) DEFAULT NULL"],
                ['name' => 'Observaciones_CP', 'def' => "TEXT DEFAULT NULL"],
                ['name' => 'alerta_crisis', 'def' => "TINYINT(1) NOT NULL DEFAULT 0"],
                ['name' => 'reprogramaciones_semanales', 'def' => "INT NOT NULL DEFAULT 0"],
            ],
            "{$prefix}_semanas_activas" => [
                ['name' => 'fecha_ultimo_saneo', 'def' => "DATETIME NULL DEFAULT NULL"],
            ],
        ];

        foreach ($tableColumns as $tableName => $columns) {
            foreach ($columns as $col) {
                $stmt = $this->db->query(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = '{$tableName}'
                     AND COLUMN_NAME = '{$col['name']}'"
                );
                if ($stmt->fetchColumn() == 0) {
                    $this->db->query(
                        "ALTER TABLE `{$tableName}` ADD COLUMN `{$col['name']}` {$col['def']}"
                    );
                }
            }
        }

        // Índices PC en _programacion_semanal
        $tableIndexes = [
            "{$prefix}_programacion_semanal" => [
                ['name' => 'idx_crisis_semanal', 'def' => "(`Semana`, `alerta_crisis`)"],
                ['name' => 'idx_consecutivo_semanal', 'def' => "(`Consecutivo_En_Programa`, `Semana`)"],
            ],
        ];

        foreach ($tableIndexes as $tableName => $indexes) {
            foreach ($indexes as $idx) {
                $stmt = $this->db->query(
                    "SELECT COUNT(*) FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = '{$tableName}'
                     AND INDEX_NAME = '{$idx['name']}'"
                );
                if ($stmt->fetchColumn() == 0) {
                    $this->db->query(
                        "ALTER TABLE `{$tableName}` ADD INDEX `{$idx['name']}` {$idx['def']}"
                    );
                }
            }
        }
    }

    /**
     * Genera el nombre de la base de datos siguiendo el patrón del proyecto.
     */
    private function generateDatabaseName($name, $area)
    {
        // Slugify name: lowercase, remove accents, replace spaces/special chars with underscores
        $slug = $this->slugify($name);

        // Pattern from general_proyectos_procesos.sql:
        // Pre-Construccion projects have _pc suffix
        $areaUpper = strtoupper($area);
        if ($areaUpper === 'PRE-CONSTRUCCION') {
            if (!str_ends_with($slug, '_pc')) {
                $slug .= '_pc';
            }
        }

        return $slug;
    }

    private function slugify($text)
    {
        // Stop words en español
        $stop_words = ['el', 'la', 'los', 'las', 'de', 'del', 'y', 'en', 'para', 'con', 'un', 'una', 'unos', 'unas', 'a', 'al', 'o', 'e', 'u'];

        // Mapa para convertir números a romanos (comúnmente usados en nombres de proyectos)
        $number_map = [
            '1' => 'i', '2' => 'ii', '3' => 'iii', '4' => 'iv', '5' => 'v',
            '6' => 'vi', '7' => 'vii', '8' => 'viii', '9' => 'ix', '10' => 'x',
        ];

        // Transliterar para quitar acentos
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Convertir a minúsculas
        $text = strtolower(trim($text));

        // Reemplazar números por su representación romana si están en el mapa
        $words = preg_split('~[^\pL\d]+~u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $processed_words = [];
        foreach ($words as $word) {
            // Si es un número en el mapa, reemplazar
            if (isset($number_map[$word])) {
                $processed_words[] = $number_map[$word];
                continue;
            }

            // Si es un número mayor no mapeado, lo dejamos (o podríamos ignorarlo si la orden es "remover")
            // Pero usualmente se prefiere mantener la distinción. Aquí removemos números no mapeados
            // para cumplir estrictamente con "Remover números".
            if (is_numeric($word)) {
                continue;
            }

            // Filtrar stop words
            if (!in_array($word, $stop_words)) {
                $processed_words[] = $word;
            }
        }

        // Unir con underscore
        $text = implode('_', $processed_words);

        if (empty($text)) {
            return 'n_a';
        }

        return $text;
    }

    /**
     * Update a single field of a project.
     *
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function updateField($id, $field, $value)
    {
        // Lista blanca de campos permitidos para actualización directa
        $allowedFields = [
            'Activo', 'Acceso', 'pdcActivo',
            'pc_restr_2_nombre', 'pc_restr_3_nombre', 'pc_restr_4_nombre',
        ];
        if (!in_array($field, $allowedFields)) {
            return false;
        }

        return $this->db->query("UPDATE {$this->table} SET {$field} = ? WHERE Id = ?", [$value, $id]);
    }

    /**
     * Delete a project and its associated tables.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $project = $this->find($id);
        if (!$project) {
            return false;
        }

        foreach ($this->orderedGlobalProjectTables() as $tableName) {
            try {
                $this->db->query("DELETE FROM `{$tableName}` WHERE project_id = ?", [$id]);
            } catch (\Exception $e) {
                error_log("Error al limpiar tabla global {$tableName}: " . $e->getMessage());
            }
        }

        $this->db->query("DELETE FROM project_members WHERE project_id = ?", [$id]);

        // Eliminar el registro del proyecto
        return $this->db->query("DELETE FROM {$this->table} WHERE Id = ?", [$id]);
    }

    /**
     * Genera un volcado SQL (Estructura + Datos) de todas las tablas del proyecto.

     *

     * @param int $projectId

     * @return string|false Contenido SQL o false si falla.
     */

    public function exportToSql($projectId)
    {
        $project = $this->find($projectId);
        if (!$project) {
            return false;
        }

        $output = "-- Backup SQL para el Proyecto: {$project['Proyecto_Proceso']}\n";
        $output .= "-- project_id: {$projectId}\n";
        $output .= "-- Prefijo compatibilidad: {$project['Base_de_Datos']}\n";
        $output .= "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $output .= $this->buildInsertDump(
            $this->table,
            $this->db->query("SELECT * FROM {$this->table} WHERE Id = ?", [$projectId])->fetchAll(\PDO::FETCH_ASSOC)
        );

        $output .= $this->buildInsertDump(
            'project_members',
            $this->db->query("SELECT * FROM project_members WHERE project_id = ?", [$projectId])->fetchAll(\PDO::FETCH_ASSOC)
        );

        foreach ($this->orderedGlobalProjectTables() as $table) {
            $rows = $this->db->query(
                "SELECT * FROM `{$table}` WHERE project_id = ?",
                [$projectId]
            )->fetchAll(\PDO::FETCH_ASSOC);
            $output .= $this->buildInsertDump($table, $rows);
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    private function tableExists(string $table): bool
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return false;
        }

        return (int) $this->db->query(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        )->fetchColumn() > 0;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        if (!$this->tableExists($table) || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1) {
            return false;
        }

        return (int) $this->db->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        )->fetchColumn() > 0;
    }

    private function orderedGlobalProjectTables(): array
    {
        $tables = array_values(array_filter(
            Database::globalTableNames(),
            fn ($table) => $this->tableHasColumn($table, 'project_id')
        ));

        $preferred = [
            'auto_program_log', 'lps_drawer_comentarios',
            'lps_escalamientos', 'pi_shared_constraint_links', 'pi_shared_constraints',
            'pg_tracking', 'cic', 'cip',
            'indicadores_generales', 'programacion_semanal', 'programa_consolidado',
            'cambios', 'subcontratistas', 'profesionales',
            'programa', 'semanas_activas',
        ];

        usort($tables, function ($a, $b) use ($preferred) {
            $posA = array_search($a, $preferred, true);
            $posB = array_search($b, $preferred, true);
            return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
        });
        return $tables;
    }

    private function initializeProjectDefaults(int $projectId, array $data): void
    {
        if (!$this->tableExists('semanas_activas')) {
            return;
        }

        $exists = (int) $this->db->query(
            "SELECT COUNT(*) FROM semanas_activas WHERE project_id = ?",
            [$projectId]
        )->fetchColumn();

        if ($exists > 0) {
            return;
        }

        $start = new \DateTimeImmutable((string) ($data['fecha_inicio_lb'] ?: date('Y-m-d')));
        $end = $start->modify('+6 days');
        $nextId = (int) $this->db->query(
            "SELECT COALESCE(MAX(Id), 0) + 1 FROM semanas_activas WHERE project_id = ?",
            [$projectId]
        )->fetchColumn();
        $this->db->query(
            "INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCreacionSemana, reprogramacion, diferenciaEstructuraCron) VALUES (?, ?, 1, ?, ?, 0, CURDATE(), 0, 0)",
            [$projectId, $nextId, $start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    private function projectPayloadFields(array $data, string $baseDatos): array
    {
        $fields = [
            'Proyecto_Proceso' => $data['nombre'],
            'Base_de_Datos' => $baseDatos,
            'Area' => $data['area'],
            'Activo' => $data['activo'] ?? 1,
            'Acceso' => $data['acceso'] ?? 1,
            'pdcActivo' => $data['pdc_activo'] ?? 0,
            'fechaInicioLineaBase' => $data['fecha_inicio_lb'] ?: null,
            'fechaFinLineaBase' => $data['fecha_fin_lb'] ?: null,
            'costoDiaRetraso' => $data['costo_retraso'] ?? 5000000,
            'urlCambios' => $data['url_cambios'] ?? null,
        ];

        foreach (['pc_restr_2_nombre', 'pc_restr_3_nombre', 'pc_restr_4_nombre'] as $column) {
            if ($this->tableHasColumn($this->table, $column)) {
                $fields[$column] = $data[$column] ?? null;
            }
        }

        return $fields;
    }

    private function buildInsertDump(string $table, array $rows): string
    {
        if (empty($rows)) {
            return "-- {$table}: sin filas para este proyecto.\n\n";
        }

        $columns = array_keys($rows[0]);
        $quotedColumns = array_map(fn ($column) => "`{$column}`", $columns);
        $output = "-- Datos de `{$table}`\n";

        foreach ($rows as $row) {
            $values = array_map(fn ($column) => $this->sqlValue($row[$column] ?? null), $columns);
            $output .= "INSERT INTO `{$table}` (" . implode(', ', $quotedColumns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }

        return $output . "\n";
    }

    private function sqlValue($value): string
    {
        return $value === null ? 'NULL' : $this->db->quote((string) $value);
    }





}
