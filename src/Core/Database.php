<?php

require_once __DIR__ . '/TableResolver.php';

class Database
{
    private static $instance = null;
    private $pdo;
    private array $projectIdByPrefix = [];
    private array $tableExistsCache = [];

    private const GLOBAL_TABLES = [
        'actividades',
        'actividad_programa_fuentes',
        'auto_contrato_log',
        'auto_program_log',
        'cambios',
        'cic',
        'cip',
        'indicadores_generales',
        'lps_drawer_comentarios',
        'lps_escalamientos',
        'papelera_pdc',
        'pdc',
        'pg_tracking',
        'pi_shared_constraint_links',
        'pi_shared_constraints',
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

    private const PROJECT_SCOPED_IDS = [
        'actividades' => 'Id',
        'cambios' => 'id',
        'cic' => 'Id',
        'lps_drawer_comentarios' => 'id',
        'lps_escalamientos' => 'id',
        'papelera_pdc' => 'pdc_row_id',
        'pdc' => 'pdc_row_id',
        'pi_shared_constraint_links' => 'id',
        'pi_shared_constraints' => 'id',
        'profesionales' => 'id',
        'programa' => 'unique_id',
        'programa_consolidado' => 'row_id',
        'programacion_semanal' => 'row_id',
        'semanas_activas' => 'Id',
        'subcontratistas' => 'Id',
    ];

    private const LEGACY_ID_COMPANIONS = [
        'papelera_pdc' => ['pdc_row_id' => 'consecutivo'],
        'pdc' => ['pdc_row_id' => 'consecutivo'],
        'programa' => ['unique_id' => 'Consecutivo'],
        'programa_consolidado' => ['row_id' => 'Consecutivo'],
        'programacion_semanal' => ['row_id' => 'Consecutivo'],
    ];

    /**
     * ID del proyecto actualmente en contexto (para inyección automática de project_id).
     */
    private ?int $currentProjectId = null;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?? '3306';
        $db = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?? '';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            // Configurar la zona horaria de la sesión de base de datos a Bogotá (UTC-5)
            $this->pdo->exec("SET time_zone = '-05:00'");
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            die('Error: No se pudo conectar a la base de datos. Por favor, intente más tarde.');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Ejecuta una consulta preparada de forma segura.
     *
     * @param string $sql La consulta SQL con placeholders.
     * @param array $params Un array de parámetros.
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        // Auto-inyectar project_id para queries raw que bypass queryWithProject()
        // Solo cuando hay contexto de proyecto y USE_GLOBAL_TABLES=true
        if ($this->currentProjectId !== null && \TableResolver::useGlobalTables()) {
            $injected = $this->injectProjectId($sql, $this->currentProjectId);
            if ($injected !== null) {
                // injectProjectId agregó placeholder ? al final → agregar project_id al final de params
                $params[] = $this->currentProjectId;
                $sql = $injected;
            }
        }

        try {
            [$sql, $params] = $this->rewriteGlobalTableQuery($sql, $params);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            error_log('Error en la consulta SQL: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Establece el contexto de proyecto para inyección automática de project_id.
     * Pasar null para limpiar el contexto.
     */
    public function setProjectContext(?int $projectId): void
    {
        $this->currentProjectId = $projectId;
    }

    /**
     * Obtiene el ID del proyecto actualmente en contexto.
     *
     * @return int|null
     */
    public function getCurrentProjectId(): ?int
    {
        return $this->currentProjectId;
    }

    /**
     * Inyecta project_id en un INSERT y su array de parámetros.
     *
     * - Solo modifica INSERTs sobre tablas globales válidas.
     * - Si la tabla no es global o ya tiene project_id en la columna → retorna sin cambios.
     * - INSERT … SELECT se salta (el SELECT ya recibe project_id via queryWithProject).
     *
     * @param string $sql       La consulta INSERT SQL
     * @param int    $projectId El project_id a inyectar
     * @param array  $params    Parámetros originales de la consulta
     *
     * @return array{0: string, 1: array} [SQL modificado, parámetros modificados]
     */
    public function insertProjectId(string $sql, int $projectId, array $params = []): array
    {
        // 1. Solo procesar INSERTs
        if (!preg_match('/^\s*INSERT\s+INTO\s+(\w+)\s*/i', $sql, $matches)) {
            return [$sql, $params];
        }

        $tableName = $matches[1];

        // 2. Solo inyectar en tablas globales válidas
        $validTables = TableResolver::getValidTables();
        if (!in_array(strtolower($tableName), $validTables, true)) {
            return [$sql, $params];
        }

        // 3. INSERT … SELECT — el SELECT ya maneja project_id via queryWithProject
        if (preg_match('/^\s*INSERT\s+INTO\s+\w+\s*\([^)]*\)\s*SELECT\b/i', $sql)) {
            return [$sql, $params];
        }

        // 4. Si tiene lista de columnas, verificar si project_id ya está presente
        if (preg_match('/^\s*INSERT\s+INTO\s+\w+\s*\(([^)]+)\)/i', $sql, $colMatch)) {
            $columns = array_map('trim', explode(',', $colMatch[1]));
            foreach ($columns as $col) {
                $colName = strtolower(str_replace('`', '', $col));
                if ($colName === 'project_id') {
                    return [$sql, $params]; // Ya tiene project_id
                }
            }

            // 5. Inyectar project_id como primera columna + placeholder en VALUES
            $modifiedSql = preg_replace(
                '/^(\s*INSERT\s+INTO\s+\w+\s*\()/i',
                '$1`project_id`, ',
                $sql
            );
            $modifiedSql = preg_replace(
                '/\bVALUES\s*\(/i',
                'VALUES(?, ',
                $modifiedSql
            );
            array_unshift($params, $projectId);
            return [$modifiedSql, $params];
        }

        // Sin lista de columnas explícita → retornar sin cambios
        return [$sql, $params];
    }

    /**
     * Ejecuta una consulta con inyección automática de project_id.
     *
     * - Si la query toca una tabla global y no tiene project_id en WHERE → inyecta AND project_id = ?
     * - Si la query YA tiene project_id en WHERE → NO duplica
     * - Si no hay project_id disponible → ejecuta sin modificar (con warning)
     *
     * @param string     $sql       La consulta SQL con placeholders.
     * @param array      $params    Parámetros preparados.
     * @param int|null   $projectId Override del contexto de proyecto (null = usar currentProjectId).
     * @return PDOStatement
     */
    public function queryWithProject(string $sql, array $params = [], ?int $projectId = null): PDOStatement
    {
        $pid = $projectId ?? $this->currentProjectId;

        // Sin project_id → ejecutar sin modificar
        if ($pid === null) {
            error_log('queryWithProject: No project_id available, executing without injection. SQL: ' . substr($sql, 0, 120));
            return $this->query($sql, $params);
        }

        $injected = $this->injectProjectId($sql, $pid);

        if ($injected !== null) {
            // Se inyectó project_id = ? al final de la query → agregar valor al final de params
            $params[] = $pid;
            return $this->query($injected, $params);
        }

        // No se inyectó (query no toca tabla global o ya tiene project_id)
        return $this->query($sql, $params);
    }

    /**
     * Inyecta project_id en el WHERE de una query si toca una tabla global y no lo tiene ya.
     *
     * @return string|null SQL modificado, o null si no se necesita inyección.
     */
    /**
     * Extract first table alias from FROM or UPDATE clause.
     * Returns null if the query has no alias (simple single-table queries).
     */
    private function extractFirstTableAlias(string $sql): ?string
    {
        // SQL keywords that can follow a table name (not aliases)
        $sqlKeywords = '\b(?:WHERE|JOIN|INNER|LEFT|RIGHT|CROSS|OUTER|FULL|STRAIGHT_JOIN|ON|AND|OR|SET|ORDER|GROUP|LIMIT|HAVING|INTO|VALUES|USING|NATURAL|AS)\b';

        // Match: FROM table [AS] alias
        if (preg_match('/\bFROM\s+(\w+)\s+(?:AS\s+)?(\w+)\b/i', $sql, $m)) {
            if (!preg_match('/' . $sqlKeywords . '/i', $m[2])) {
                return $m[2];
            }
        }

        // Match: UPDATE table [AS] alias
        if (preg_match('/\bUPDATE\s+(\w+)\s+(?:AS\s+)?(\w+)\b/i', $sql, $m)) {
            if (!preg_match('/' . $sqlKeywords . '/i', $m[2])) {
                return $m[2];
            }
        }

        return null;
    }

    private function injectProjectId(string $sql, int $projectId): ?string
    {
        // Skip INSERT queries — rewriteGlobalTableQuery handles INSERT...SELECT via
        // rewriteInsertSelect (injects project_id into both INSERT columns and SELECT's
        // WHERE) and plain INSERT...VALUES via rewriteInsert. Injecting project_id as a
        // WHERE append in INSERT...SELECT with JOIN causes MySQL "Unknown column
        // 'project_id' in 'on clause'" when the JOIN involves a non-global table.
        if (preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?/i', $sql)) {
            return null;
        }

        $sqlLower = strtolower($sql);

        // 1. Verificar si ya tiene project_id en el WHERE
        if (preg_match('/\bwhere\b.*\bproject_id\b/s', $sqlLower)) {
            return null;
        }

        // 2. Detectar si la query toca una tabla global
        $globalTableTypes = TableResolver::getValidTables();
        $touchesGlobal = false;

        // Buscar FROM/JOIN/UPDATE + nombre de tabla global
        foreach ($globalTableTypes as $type) {
            if (preg_match('/\b(?:from|join|update)\s+' . preg_quote($type, '/') . '\b/i', $sql)) {
                $touchesGlobal = true;
                break;
            }
        }

        if (!$touchesGlobal) {
            return null;
        }

        // 3. Determinar si necesita calificación con alias (tiene JOIN)
        $hasJoin = (bool) preg_match('/\bJOIN\b/i', $sql);
        $qualifier = $hasJoin ? $this->extractFirstTableAlias($sql) : null;
        $projectIdExpr = $qualifier ? "{$qualifier}.project_id = ?" : "project_id = ?";

        // 4. Inyectar AND/WHERE project_id = ? al FINAL del SQL
        //    para que el parámetro pueda ir al final del array de params.
        $pidExpr = "{$projectIdExpr}";

        if (preg_match('/\bwhere\b/i', $sql)) {
            // Ya tiene WHERE → añadir AND project_id = ? al final (o antes de ORDER BY, etc.)
            $patterns = [
                '/\s+(ORDER\s+BY)\b/i',
                '/\s+(GROUP\s+BY)\b/i',
                '/\s+(LIMIT)\b/i',
                '/\s+(HAVING)\b/i',
                '/\s+(ON\s+DUPLICATE\s+KEY)\b/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $sql, $matches, PREG_OFFSET_CAPTURE)) {
                    $pos = $matches[0][1];
                    return substr($sql, 0, $pos) . " AND {$pidExpr}" . substr($sql, $pos);
                }
            }

            // No hay cláusulas posteriores → añadir AND al final
            return rtrim($sql) . " AND {$pidExpr}";
        }

        // No hay WHERE → añadir al final (o antes de ORDER BY, LIMIT, etc.)
        $patterns = [
            '/\s+(ORDER\s+BY)\b/i',
            '/\s+(GROUP\s+BY)\b/i',
            '/\s+(LIMIT)\b/i',
            '/\s+(HAVING)\b/i',
            '/\s+(ON\s+DUPLICATE\s+KEY)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                return substr($sql, 0, $pos) . " WHERE {$pidExpr}" . substr($sql, $pos);
            }
        }

        return rtrim($sql) . " WHERE {$pidExpr}";
    }

    /**
     * Registra una acción en la bitácora de auditoría.
     *
     * @param string $modulo Nombre del módulo (ej: 'PDC', 'Usuarios')
     * @param string $accion Tipo de acción (ej: 'CREAR', 'MODIFICAR')
     * @param string $descripcion Detalle de la acción
     * @param string $proyecto Proyecto asociado (opcional)
     * @return bool
     */
    public function logActivity($modulo, $accion, $descripcion = '', $proyecto = null)
    {
        try {
            // Detectar usuario de cualquier sesión
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $usuario = 'Sistema';
            if (isset($_SESSION['usuario'])) {
                $usuario = $_SESSION['usuario'];
            } elseif (isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user']) && isset($_SESSION['admin_user']['usuario'])) {
                $usuario = $_SESSION['admin_user']['usuario'];
            }
            $id_sesion = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $sql = "INSERT INTO general_auditoria_acciones
                    (usuario, id_sesion, modulo, accion, descripcion, ip_address, proyecto)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->query($sql, [
                $usuario,
                $id_sesion,
                $modulo,
                $accion,
                $descripcion,
                $ip,
                $proyecto,
            ]);

            return true;
        } catch (Exception $e) {
            error_log('Error registrando auditoría: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Escapa una cadena para su uso en una consulta SQL.
     *
     * @param string $string
     * @return string
     */
    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    /**
     * Obtiene el ID del último registro insertado.
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    public function isUsingGlobalTables(): bool
    {
        return $this->usesGlobalTables();
    }

    public static function globalTableNames(): array
    {
        return self::GLOBAL_TABLES;
    }

    public function prepare($sql)
    {
        if ($this->usesGlobalTables()) {
            [, $matchedTables] = $this->rewritePrefixedTables($sql);
            if (!empty($matchedTables)) {
                return new DatabasePreparedStatement($this, $sql);
            }
        } else {
            $sql = $this->rewriteLegacyArchiveTables($sql);
        }
        return $this->pdo->prepare($sql);
    }

    private function rewriteGlobalTableQuery(string $sql, array $params): array
    {
        if (!$this->usesGlobalTables()) {
            return [$this->rewriteLegacyArchiveTables($sql), $params];
        }

        $operation = strtoupper(strtok(ltrim($sql), " \t\r\n"));
        if (!in_array($operation, ['INSERT', 'SELECT', 'UPDATE', 'DELETE'], true)) {
            return [$sql, $params];
        }

        $projectId = $this->resolveProjectIdFromSession();
        [$rewrittenSql, $matchedTables, $prefixProjectId, $prefixedReferenceCount] = $this->rewritePrefixedTables($sql);

        if ($prefixProjectId !== null) {
            $projectId = $prefixProjectId;
        }

        if ($projectId === null || empty($matchedTables)) {
            return [$rewrittenSql, $params];
        }

        if ($operation === 'INSERT') {
            $insertSelect = $this->rewriteInsertSelect($rewrittenSql, $params, $matchedTables[0], $projectId);
            if ($insertSelect !== null) {
                return $insertSelect;
            }

            return $this->rewriteInsert($rewrittenSql, $params, $matchedTables[0], $projectId);
        }

        if (in_array($operation, ['SELECT', 'UPDATE', 'DELETE'], true)) {
            [$scopedSql, $scopedParams] = $this->injectProjectFilter($rewrittenSql, $params, $matchedTables[0], $projectId, $operation);
            $this->assertScopedPrefixedGlobalQuery($scopedSql, $matchedTables, $prefixedReferenceCount);
            return [$scopedSql, $scopedParams];
        }

        return [$rewrittenSql, $params];
    }

    private function usesGlobalTables(): bool
    {
        $env = $_ENV['USE_GLOBAL_TABLES'] ?? $_SERVER['USE_GLOBAL_TABLES'] ?? getenv('USE_GLOBAL_TABLES');
        if ($env !== false && $env !== null) {
            return in_array(strtolower((string) $env), ['1', 'true', 'yes', 'on'], true);
        }

        static $available = null;
        if ($available !== null) {
            return $available;
        }

        try {
            $available = (bool) $this->pdo->query("SHOW TABLES LIKE 'semanas_activas'")->fetchColumn();
        } catch (PDOException $e) {
            $available = false;
        }

        return $available;
    }

    private function resolveProjectIdFromSession(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!empty($_SESSION['project_id'])) {
            return (int) $_SESSION['project_id'];
        }

        $prefix = (string) ($_SESSION['db'] ?? '');
        return $prefix !== '' ? $this->resolveProjectIdByPrefix($prefix) : null;
    }

    private function resolveProjectIdByPrefix(string $prefix): ?int
    {
        if ($prefix === '' || preg_match('/^[A-Za-z0-9_]+$/', $prefix) !== 1) {
            return null;
        }

        if (array_key_exists($prefix, $this->projectIdByPrefix)) {
            return $this->projectIdByPrefix[$prefix];
        }

        $stmt = $this->pdo->prepare('SELECT ID FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1');
        $stmt->execute([$prefix]);
        $id = $stmt->fetchColumn();
        $this->projectIdByPrefix[$prefix] = $id ? (int) $id : null;

        return $this->projectIdByPrefix[$prefix];
    }

    private function rewritePrefixedTables(string $sql): array
    {
        $matchedTables = [];
        $projectId = null;
        $prefixedReferenceCount = 0;
        $tables = implode('|', array_map('preg_quote', self::GLOBAL_TABLES));
        $pattern = '/`?([A-Za-z][A-Za-z0-9_]*)_(' . $tables . ')`?\b/';

        $rewritten = preg_replace_callback($pattern, function (array $match) use (&$matchedTables, &$projectId, &$prefixedReferenceCount) {
            $prefix = $match[1];
            $table = $match[2];
            $resolvedId = $this->resolveProjectIdByPrefix($prefix);

            if ($resolvedId === null) {
                return $match[0];
            }

            $projectId = $resolvedId;
            $matchedTables[] = $table;
            $prefixedReferenceCount++;
            return $table;
        }, $sql);

        foreach (self::GLOBAL_TABLES as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/', $rewritten)) {
                $matchedTables[] = $table;
            }
        }

        return [$rewritten, array_values(array_unique($matchedTables)), $projectId, $prefixedReferenceCount];
    }

    private function assertScopedPrefixedGlobalQuery(string $sql, array $matchedTables, int $prefixedReferenceCount): void
    {
        if ($prefixedReferenceCount <= 1) {
            return;
        }

        $tableReferenceCount = 0;
        foreach ($matchedTables as $table) {
            $pattern = '/\b(?:FROM|JOIN|UPDATE|DELETE\s+FROM)\s+`?' . preg_quote($table, '/') . '`?\b/i';
            $tableReferenceCount += preg_match_all($pattern, $sql);
        }

        if ($tableReferenceCount <= 1) {
            return;
        }

        $scopeCount = preg_match_all(
            '/(?:\b[A-Za-z_][A-Za-z0-9_]*\s*\.\s*)?`?project_id`?\b\s*(?:=|<=>|IN\b)/i',
            $sql
        );

        if ($scopeCount < $tableReferenceCount) {
            throw new RuntimeException('Consulta a tablas globales sin alcance completo por project_id.');
        }
    }

    private function rewriteLegacyArchiveTables(string $sql): string
    {
        $tables = implode('|', array_map('preg_quote', self::GLOBAL_TABLES));
        $pattern = '/`?([A-Za-z][A-Za-z0-9_]*)_(' . $tables . ')`?\b/';

        return preg_replace_callback($pattern, function (array $match) {
            $legacyTable = $match[1] . '_' . $match[2];
            if ($this->rawTableExists($legacyTable)) {
                return $match[0];
            }

            $archiveTable = 'zleg_' . $legacyTable;
            if ($this->rawTableExists($archiveTable)) {
                return '`' . $archiveTable . '`';
            }

            return $match[0];
        }, $sql);
    }

    private function rewriteInsert(string $sql, array $params, string $table, int $projectId): array
    {
        if (!preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+`?' . preg_quote($table, '/') . '`?\s*\(([^)]*)\)\s*VALUES\s*\(([^)]*)\)/i', $sql, $match)) {
            return [$sql, $params];
        }

        $columns = array_map(static fn($column) => trim($column, " `\t\n\r\0\x0B"), explode(',', $match[1]));
        $values = array_map('trim', explode(',', $match[2]));
        $prependColumns = [];
        $prependValues = [];

        if (!in_array('project_id', $columns, true)) {
            $prependColumns[] = 'project_id';
            $prependValues[] = $projectId;
        }

        $idColumn = $this->projectScopedIdColumn($table);
        if ($idColumn !== null && !in_array($idColumn, $columns, true)) {
            $legacyColumn = $this->legacyIdCompanion($table, $idColumn);
            if ($legacyColumn === null || !in_array($legacyColumn, $columns, true)) {
                $nextId = $this->nextProjectScopedId($table, $idColumn, $projectId);
                $prependColumns[] = $idColumn;
                $prependValues[] = $nextId;
            }
            if ($legacyColumn !== null && !in_array($legacyColumn, $columns, true)) {
                $prependColumns[] = $legacyColumn;
                $prependValues[] = $nextId;
            }
        }

        if (empty($prependColumns)) {
            return [$sql, $params];
        }

        $newColumns = array_merge($prependColumns, $columns);
        $newValues = array_merge(array_fill(0, count($prependColumns), '?'), $values);
        $isIgnore = (bool) preg_match('/^\s*INSERT\s+IGNORE\s+/i', $sql);
        $prefix = $isIgnore ? 'INSERT IGNORE INTO ' : 'INSERT INTO ';
        $replacement = $prefix . $table . ' (' . implode(', ', $newColumns) . ') VALUES (' . implode(', ', $newValues) . ')';
        $rewritten = preg_replace('/INSERT\s+(?:IGNORE\s+)?INTO\s+`?' . preg_quote($table, '/') . '`?\s*\([^)]+\)\s*VALUES\s*\([^)]+\)/i', $replacement, $sql, 1);

        if ($this->isSequentialArray($params)) {
            $params = array_merge($prependValues, $params);
        }

        return [$rewritten, $params];
    }

    private function rewriteInsertSelect(string $sql, array $params, string $table, int $projectId): ?array
    {
        if (!preg_match('/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?\s*(?:\(([^)]*)\))?\s+SELECT\s+/i', $sql, $insertMatch)) {
            return null;
        }

        $sourceTable = $this->resolveInsertSelectSourceTable($sql);
        if ($sourceTable !== null) {
            [$sql, $params] = $this->injectProjectFilter($sql, $params, $sourceTable, $projectId, 'SELECT');
        }

        if (!isset($insertMatch[1]) || trim($insertMatch[1]) === '') {
            return [$sql, $params];
        }

        if (!preg_match('/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?\s*\(([^)]*)\)\s*SELECT\s+(.*?)\s+FROM\s/is', $sql, $match)) {
            return [$sql, $params];
        }

        $columns = array_map(static fn($column) => trim($column, " `\t\n\r\0\x0B"), $this->splitSqlList($match[1]));
        $selectValues = $this->splitSqlList($match[2]);
        $prependColumns = [];
        $prependValues = [];
        $prependParams = [];

        if (!in_array('project_id', $columns, true)) {
            $prependColumns[] = 'project_id';
            $prependValues[] = '?';
            $prependParams[] = $projectId;
        }

        $idColumn = $this->projectScopedIdColumn($table);
        if ($idColumn !== null) {
            $idIndex = array_search($idColumn, $columns, true);
            $legacyColumn = $this->legacyIdCompanion($table, $idColumn);
            if ($idIndex === false) {
                if ($legacyColumn === null || !in_array($legacyColumn, $columns, true)) {
                    $idExpression = $this->projectScopedIdSelectExpression($table, $idColumn);
                    $prependColumns[] = $idColumn;
                    $prependValues[] = $idExpression;
                    $prependParams[] = $projectId;
                }
                if ($legacyColumn !== null && !in_array($legacyColumn, $columns, true)) {
                    $prependColumns[] = $legacyColumn;
                    $prependValues[] = $idExpression;
                    $prependParams[] = $projectId;
                }
            } elseif (isset($selectValues[$idIndex]) && strtoupper(trim($selectValues[$idIndex])) === 'NULL') {
                array_splice($columns, $idIndex, 1);
                array_splice($selectValues, $idIndex, 1);
                $idExpression = $this->projectScopedIdSelectExpression($table, $idColumn);
                $prependColumns[] = $idColumn;
                $prependValues[] = $idExpression;
                $prependParams[] = $projectId;
            }
        }

        if (empty($prependColumns)) {
            return [$sql, $params];
        }

        $newColumns = array_merge($prependColumns, $columns);
        $newSelectValues = array_merge($prependValues, $selectValues);
        $replacement = 'INSERT INTO ' . $table . ' (' . implode(', ', $newColumns) . ') SELECT ' . implode(', ', $newSelectValues) . ' FROM ';
        $rewritten = preg_replace(
            '/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?\s*\([^)]+\)\s*SELECT\s+.*?\s+FROM\s/is',
            $replacement,
            $sql,
            1,
        );

        if ($this->isSequentialArray($params)) {
            $params = array_merge($prependParams, $params);
        }

        return [$rewritten, $params];
    }

    private function injectProjectFilter(string $sql, array $params, string $table, int $projectId, string $operation): array
    {
        if (preg_match('/\bproject_id\b/i', $sql)) {
            return [$sql, $params];
        }

        $alias = $this->resolveAlias($sql, $table, $operation);
        $usesSequentialParams = $this->isSequentialArray($params);
        $projectPlaceholder = $usesSequentialParams ? '?' : ':__global_project_id';
        $condition = ($alias !== null ? $alias : $table) . '.project_id = ' . $projectPlaceholder;

        $insertOffset = strlen($sql);
        if (preg_match('/\bWHERE\b/i', $sql, $whereMatch, PREG_OFFSET_CAPTURE)) {
            $insertOffset = $whereMatch[0][1];
            $rewritten = preg_replace('/\bWHERE\b/i', 'WHERE ' . $condition . ' AND ', $sql, 1);
        } else {
            $splitPattern = '/\s+(ORDER\s+BY|GROUP\s+BY|LIMIT)\s+/i';
            if (preg_match($splitPattern, $sql, $match, PREG_OFFSET_CAPTURE)) {
                $pos = $match[0][1];
                $insertOffset = $pos;
                $rewritten = substr($sql, 0, $pos) . ' WHERE ' . $condition . substr($sql, $pos);
            } else {
                $rewritten = $sql . ' WHERE ' . $condition;
            }
        }

        if ($usesSequentialParams) {
            $projectParamIndex = substr_count(substr($sql, 0, $insertOffset), '?');
            array_splice($params, $projectParamIndex, 0, [$projectId]);
        } else {
            $params['__global_project_id'] = $projectId;
        }

        return [$rewritten, $params];
    }

    private function resolveAlias(string $sql, string $table, string $operation): ?string
    {
        $keywordPattern = '(WHERE|SET|JOIN|LEFT|RIGHT|INNER|OUTER|ORDER|GROUP|LIMIT|ON)';

        if ($operation === 'UPDATE' && preg_match('/UPDATE\s+`?' . preg_quote($table, '/') . '`?(?:\s+(?:AS\s+)?([A-Za-z_][A-Za-z0-9_]*))?/i', $sql, $match)) {
            return isset($match[1]) && !preg_match('/^' . $keywordPattern . '$/i', $match[1]) ? $match[1] : null;
        }

        if (preg_match('/FROM\s+`?' . preg_quote($table, '/') . '`?(?:\s+(?:AS\s+)?([A-Za-z_][A-Za-z0-9_]*))?/i', $sql, $match)) {
            return isset($match[1]) && !preg_match('/^' . $keywordPattern . '$/i', $match[1]) ? $match[1] : null;
        }

        return null;
    }

    private function resolveInsertSelectSourceTable(string $sql): ?string
    {
        if (!preg_match('/\bFROM\s+`?([A-Za-z_][A-Za-z0-9_]*)`?\b/i', $sql, $match)) {
            return null;
        }

        return in_array($match[1], self::GLOBAL_TABLES, true) ? $match[1] : null;
    }

    private function projectScopedIdSelectExpression(string $table, string $column): string
    {
        return "(SELECT COALESCE(MAX(`{$column}`), 0) FROM (SELECT `{$column}` FROM `{$table}` WHERE project_id = ?) AS __global_ids) + ROW_NUMBER() OVER ()";
    }

    private function splitSqlList(string $list): array
    {
        $items = [];
        $buffer = '';
        $depth = 0;
        $quote = null;
        $length = strlen($list);

        for ($i = 0; $i < $length; $i++) {
            $char = $list[$i];
            $previous = $i > 0 ? $list[$i - 1] : '';

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === $quote && $previous !== '\\') {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
                $buffer .= $char;
                continue;
            }

            if ($char === ')') {
                $depth = max(0, $depth - 1);
                $buffer .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $items[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $items[] = trim($buffer);
        }

        return $items;
    }

    private function projectScopedIdColumn(string $table): ?string
    {
        $column = self::PROJECT_SCOPED_IDS[$table] ?? null;
        if ($column === null) {
            return null;
        }

        if ($this->rawColumnExists($table, $column)) {
            return $column;
        }

        foreach (self::LEGACY_ID_COMPANIONS[$table] ?? [] as $newColumn => $legacyColumn) {
            if ($newColumn === $column && $this->rawColumnExists($table, $legacyColumn)) {
                return $legacyColumn;
            }
        }

        return $column;
    }

    private function legacyIdCompanion(string $table, string $column): ?string
    {
        $legacyColumn = self::LEGACY_ID_COMPANIONS[$table][$column] ?? null;
        if ($legacyColumn === null) {
            return null;
        }

        return $this->rawColumnExists($table, $legacyColumn) ? $legacyColumn : null;
    }

    private function nextProjectScopedId(string $table, string $column, int $projectId): int
    {
        if ($table === 'programa' && $column === 'unique_id') {
            return $this->reserveProgramUniqueId($projectId);
        }

        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(`$column`), 0) + 1 FROM `$table` WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return (int) $stmt->fetchColumn();
    }

    private function reserveProgramUniqueId(int $projectId): int
    {
        if (!$this->rawTableExists('program_unique_id_sequences') || !$this->rawColumnExists('programa', 'unique_id')) {
            $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(`Consecutivo`), 0) + 1 FROM `programa` WHERE project_id = ?');
            $stmt->execute([$projectId]);
            return (int) $stmt->fetchColumn();
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->pdo->prepare(
                'INSERT INTO `program_unique_id_sequences` (`project_id`, `next_unique_id`)
                 SELECT ?, COALESCE(MAX(`unique_id`), 0) + 1 FROM `programa` WHERE project_id = ?
                 ON DUPLICATE KEY UPDATE `next_unique_id` = GREATEST(`next_unique_id`, VALUES(`next_unique_id`))'
            )->execute([$projectId, $projectId]);

            $stmt = $this->pdo->prepare('SELECT `next_unique_id` FROM `program_unique_id_sequences` WHERE `project_id` = ? FOR UPDATE');
            $stmt->execute([$projectId]);
            $next = (int) $stmt->fetchColumn();

            $this->pdo->prepare('UPDATE `program_unique_id_sequences` SET `next_unique_id` = ? WHERE `project_id` = ?')
                ->execute([$next + 1, $projectId]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $next;
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function isSequentialArray(array $params): bool
    {
        if ($params === []) {
            return true;
        }

        return array_keys($params) === range(0, count($params) - 1);
    }

    private function rawTableExists(string $tableName): bool
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $tableName) !== 1) {
            return false;
        }

        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$tableName]);
        $this->tableExistsCache[$tableName] = ((int) $stmt->fetchColumn()) > 0;

        return $this->tableExistsCache[$tableName];
    }

    private function rawColumnExists(string $tableName, string $columnName): bool
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $tableName) !== 1 || preg_match('/^[A-Za-z0-9_]+$/', $columnName) !== 1) {
            return false;
        }

        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, $this->tableExistsCache)) {
            return $this->tableExistsCache[$cacheKey];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$tableName, $columnName]);
        $this->tableExistsCache[$cacheKey] = ((int) $stmt->fetchColumn()) > 0;

        return $this->tableExistsCache[$cacheKey];
    }

    /**
     * Injects project_id into SQL (like queryWithProject) but returns a prepared
     * statement WITHOUT executing it. Use when you need to execute the same query
     * multiple times in a loop with different params.
     *
     * When injectProjectId() modifies the SQL (appends AND project_id = ? at the
     * end), the caller MUST append the project_id value at the END of the params
     * array passed to execute().
     *
     * Example:
     *   $pid = $db->getCurrentProjectId();
     *   $stmt = $db->prepareWithProject("UPDATE t SET x = ? WHERE id = ?");
     *   foreach ($items as $item) {
     *       $stmt->execute([$item['x'], $item['id'], $pid]);  // $pid LAST
     *   }
     *
     * @param string $sql The SQL query with placeholders
     * @param int|null $projectId Project ID (uses context if null)
     * @return PDOStatement
     */
    public function prepareWithProject(string $sql, ?int $projectId = null): PDOStatement
    {
        $pid = $projectId ?? $this->currentProjectId;

        if ($pid === null) {
            return $this->pdo->prepare($sql);
        }

        $injected = $this->injectProjectId($sql, $pid);

        if ($injected !== null) {
            return $this->pdo->prepare($injected);
        }

        return $this->pdo->prepare($sql);
    }

    /**
     * Verifica de forma segura si una tabla existe en la base de datos actual.
     *
     * @param string $tableName Nombre de la tabla a verificar.
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        return $this->rawTableExists($tableName);
    }

    // Evitar clonación del objeto
    private function __clone() {}

    // Evitar deserialización del objeto
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}

class DatabasePreparedStatement
{
    private Database $db;
    private string $sql;
    private ?PDOStatement $stmt = null;
    private array $boundParams = [];

    public function __construct(Database $db, string $sql)
    {
        $this->db = $db;
        $this->sql = $sql;
    }

    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = null, $driverOptions = null): bool
    {
        $this->boundParams[$param] = &$var;
        return true;
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value;
        return true;
    }

    public function execute($params = null): bool
    {
        $this->stmt = $this->db->query($this->sql, $params ?? $this->boundParams);
        return true;
    }

    public function fetch($mode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $this->ensureExecuted();
        return $mode === null
            ? $this->stmt->fetch()
            : $this->stmt->fetch($mode, $cursorOrientation, $cursorOffset);
    }

    public function fetchAll($mode = null, ...$args): array
    {
        $this->ensureExecuted();
        return $mode === null ? $this->stmt->fetchAll() : $this->stmt->fetchAll($mode, ...$args);
    }

    public function fetchColumn($column = 0)
    {
        $this->ensureExecuted();
        return $this->stmt->fetchColumn($column);
    }

    public function rowCount(): int
    {
        $this->ensureExecuted();
        return $this->stmt->rowCount();
    }

    public function __call(string $name, array $arguments)
    {
        $this->ensureExecuted();
        return $this->stmt->{$name}(...$arguments);
    }

    private function ensureExecuted(): void
    {
        if ($this->stmt === null) {
            throw new RuntimeException('La consulta preparada aún no se ha ejecutado.');
        }
    }
}
