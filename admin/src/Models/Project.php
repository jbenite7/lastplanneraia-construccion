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
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table} WHERE Area = 'Construccion'");
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
        if ($this->db->isUsingGlobalTables()) {
            $missing = [];
            foreach (Database::globalTableNames() as $table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
                if (!$stmt->fetch()) {
                    $missing[] = $table;
                }
            }

            return empty($missing) ? [] : [[
                'id' => 0,
                'nombre' => 'Tablas globales',
                'missing' => $missing,
            ]];
        }

        $projects = $this->getAll();
        $suffixes = [
            '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',
            '_programa', '_programacion_semanal', '_programa_consolidado',
            '_semanas_activas', '_subcontratistas',
        ];

        $report = [];
        foreach ($projects as $project) {
            $prefix = $project['Base_de_Datos'];
            if (empty($prefix)) {
                continue;
            }

            $missing = [];
            foreach ($suffixes as $suffix) {
                $table = "{$prefix}{$suffix}";
                $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
                if (!$stmt->fetch()) {
                    $missing[] = $table;
                }
            }

            if (!empty($missing)) {
                $report[] = [
                    'id' => $project['Id'],
                    'nombre' => $project['Proyecto_Proceso'],
                    'missing' => $missing,
                ];
            }
        }

        return $report;
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

            if ($this->db->isUsingGlobalTables() && in_array($table, Database::globalTableNames(), true)) {
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
        $stmt = $this->db->query("SELECT Proyecto_Proceso FROM {$this->table} WHERE Area = 'Construccion' AND Activo = 1");

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

            // 2. Datos (Procesados por lotes si fuera necesario, pero por ahora tabla a tabla)
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
     * Get all active construction projects.
     *
     * @return array
     */
    public function getAllActive()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE Area = 'Construccion' AND Activo = 1");

        return $stmt->fetchAll();
    }

    /**
     * Get all projects (including inactive).
     *
     * @return array
     */
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE Area = 'Construccion'");

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

        $oldPrefix = $oldProject['Base_de_Datos'] ?? '';
        $newPrefix = $data['base_datos'] ?? '';

        $sql = "UPDATE {$this->table} SET 
                Proyecto_Proceso = ?, 
                Base_de_Datos = ?, 
                Area = ?,
                Activo = ?,
                Acceso = ?,
                pdcActivo = ?,
                fechaInicioLineaBase = ?,
                fechaFinLineaBase = ?,
                costoDiaRetraso = ?,
                urlCambios = ?
                WHERE Id = ?";

        $result = $this->db->query($sql, [
            $data['nombre'],
            $newPrefix,
            $data['area'],
            $data['activo'],
            $data['acceso'],
            $data['pdc_activo'],
            $data['fecha_inicio_lb'] ?: null,
            $data['fecha_fin_lb'] ?: null,
            $data['costo_retraso'],
            $data['url_cambios'],
            $id,
        ]);

        if ($result) {
            // Si el prefijo cambió, renombrar tablas existentes
            if (!empty($oldPrefix) && !empty($newPrefix) && $oldPrefix !== $newPrefix) {
                $this->renameProjectTables($oldPrefix, $newPrefix);
            }
            // Si antes no tenía prefijo pero ahora sí, o simplemente para asegurar integridad
            elseif (!empty($newPrefix)) {
                $this->createProjectTables($newPrefix);
            }
        }

        return $result;
    }

    /**
     * Renombra las tablas del proyecto cuando cambia el prefijo.
     * Garantiza que no se pierdan datos al usar RENAME TABLE (operación de metadatos).
     *
     * @param string $oldPrefix
     * @param string $newPrefix
     * @return void
     */
    private function renameProjectTables($oldPrefix, $newPrefix)
    {
        if ($this->db->isUsingGlobalTables()) {
            return;
        }

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
        $this->createProjectTables($newPrefix);
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

        $sql = "INSERT INTO {$this->table} (
                    Proyecto_Proceso, 
                    Base_de_Datos, 
                    Area, 
                    Activo, 
                    Acceso, 
                    pdcActivo, 
                    fechaInicioLineaBase, 
                    fechaFinLineaBase, 
                    costoDiaRetraso, 
                    urlCambios
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $result = $this->db->query($sql, [
            $data['nombre'],
            $base_datos,
            $data['area'],
            $data['activo'] ?? 1,
            $data['acceso'] ?? 1,
            $data['pdc_activo'] ?? 0,
            $data['fecha_inicio_lb'] ?: null,
            $data['fecha_fin_lb'] ?: null,
            $data['costo_retraso'] ?? 5000000,
            $data['url_cambios'] ?? null,
        ]);

        if ($result && $base_datos) {
            $this->createProjectTables($base_datos);
        }

        return $result;
    }

    /**
     * Crea las tablas específicas para el proyecto basadas en la plantilla.
     *
     * @param string $prefix El prefijo (Base_de_Datos) para las tablas.
     * @return void
     */
    private function createProjectTables($prefix)
    {
        if ($this->db->isUsingGlobalTables()) {
            return;
        }

        $queries = [
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

        foreach ($queries as $sql) {
            $this->db->query($sql);
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
        // PI projects usually have _pi suffix
        if (strtoupper($area) === 'PI') {
            if (!str_ends_with($slug, '_pi')) {
                $slug .= '_pi';
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
        $allowedFields = ['Activo', 'Acceso', 'pdcActivo'];
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

        $prefix = $project['Base_de_Datos'] ?? '';

        if ($this->db->isUsingGlobalTables()) {
            foreach (Database::globalTableNames() as $tableName) {
                try {
                    $this->db->query("DELETE FROM `{$tableName}` WHERE project_id = ?", [(int) $id]);
                } catch (\Exception $e) {
                    error_log("Error al limpiar tabla global {$tableName} para proyecto {$id}: " . $e->getMessage());
                }
            }

            return $this->db->query("DELETE FROM {$this->table} WHERE Id = ?", [$id]);
        }

        // Si tiene prefijo, eliminar sus tablas asociadas
        if (!empty($prefix)) {
            $suffixes = [
                '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',
                '_programa', '_programacion_semanal', '_programa_consolidado',
                '_semanas_activas', '_subcontratistas',
            ];

            foreach ($suffixes as $suffix) {
                $tableName = "{$prefix}{$suffix}";
                try {
                    $this->db->query("DROP TABLE IF EXISTS `{$tableName}`");
                } catch (\Exception $e) {
                    error_log("Error al eliminar tabla {$tableName}: " . $e->getMessage());
                }
            }
        }

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



        $prefix = $project['Base_de_Datos'];

        if (empty($prefix)) {
            return false;
        }



        $suffixes = [

            '_actividades', '_cambios', '_cic', '_pdc', '_profesionales',

            '_programa', '_programacion_semanal', '_programa_consolidado',

            '_semanas_activas', '_subcontratistas',

        ];



        $output = "-- Backup SQL para el Proyecto: {$project['Proyecto_Proceso']}\n";

        $output .= "-- Prefijo Base de Datos: {$prefix}\n";

        $output .= "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n\n";

        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";



        foreach ($suffixes as $suffix) {

            $table = "{$prefix}{$suffix}";



            // 1. Verificar si la tabla existe

            $check = $this->db->query("SHOW TABLES LIKE '{$table}'");

            if (!$check->fetch()) {
                continue;
            }



            $output .= "-- --------------------------------------------------------\n";

            $output .= "-- Estructura de tabla para `{$table}`\n";

            $output .= "-- --------------------------------------------------------\n\n";



            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";



            // 2. Obtener CREATE TABLE

            $res = $this->db->query("SHOW CREATE TABLE `{$table}`");

            $createRow = $res->fetch();

            $output .= $createRow['Create Table'] . ";\n\n";



            // 3. Obtener Datos

            $output .= "-- Volcado de datos para la tabla `{$table}`\n\n";

            $res = $this->db->query("SELECT * FROM `{$table}`");

            $rows = $res->fetchAll();



            if (!empty($rows)) {

                foreach ($rows as $row) {

                    $output .= "INSERT INTO `{$table}` VALUES (";

                    $values = [];

                    foreach ($row as $val) {

                        if (is_null($val)) {

                            $values[] = "NULL";

                        } else {

                            $values[] = "'" . addslashes($val) . "'";

                        }

                    }

                    $output .= implode(', ', $values) . ");\n";

                }

                $output .= "\n";

            }

        }



        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";



        return $output;

    }





}
