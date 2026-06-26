<?php

class Database
{
    private static $instance = null;
    private $pdo;

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

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
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
        try {
            $stmt = $this->pdo->query("SELECT 1 FROM " . $this->quote($tableName) . " LIMIT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Evitar clonación del objeto
    private function __clone() {}

    // Evitar deserialización del objeto
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}
