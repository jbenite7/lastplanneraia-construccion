<?php

require_once __DIR__ . '/TableResolver.php';

class Database
{
    private static $instance = null;
    private $pdo;
    private ?\App\Security\DataScope\TableScopeCatalog $tableScopeCatalog = null;
    private \App\Security\DataScope\DataScopeContext $dataScopeContext;
    private array $projectIdByPrefix = [];
    private array $tableExistsCache = [];

    private const GLOBAL_TABLES = [
        'auto_program_log',
        'cambios',
        'cic',
        'cip',
        'indicadores_generales',
        'lps_drawer_comentarios',
        'lps_escalamientos',
        'pg_tracking',
        'pi_shared_constraint_links',
        'pi_shared_constraints',
        'profesionales',
        'programa',
        'programa_consolidado',
        'programacion_semanal',
        'semanas_activas',
        'subcontratistas',
    ];

    private const PROJECT_SCOPED_IDS = [
        'cambios' => 'id',
        'cic' => 'Id',
        'lps_drawer_comentarios' => 'id',
        'lps_escalamientos' => 'id',
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
            $this->dataScopeContext = new \App\Security\DataScope\DataScopeContext();
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

    public function tableScopeCatalog(): \App\Security\DataScope\TableScopeCatalog
    {
        return $this->tableScopeCatalog ??= \App\Security\DataScope\TableScopeCatalog::fromPdo($this->pdo);
    }

    public function dataScope(): \App\Security\DataScope\DataScopeContext
    {
        return $this->dataScopeContext;
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
        try {
            $scope = $this->dataScope()->current();
            $guarded = (new \App\Security\DataScope\ProjectSqlGuard($this))->guard(
                (string) $sql,
                (array) $params,
                $scope,
                $this->tableScopeCatalog(),
            );
            $projectId = $scope instanceof \App\Security\DataScope\ProjectScope
                ? $scope->projectId()
                : null;
            [$guardedSql, $guardedParams] = $this->rewriteGlobalTableQuery(
                $guarded->sql,
                $guarded->params,
                $projectId,
                $guarded->tables,
            );
            $stmt = $this->pdo->prepare($guardedSql);
            $stmt->execute($guardedParams);

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
        $this->dataScopeContext->clear();

        $user = trim((string) ($_SESSION['usuario'] ?? ''));
        $role = trim((string) ($_SESSION['permiso_canonico'] ?? $_SESSION['permiso'] ?? ''));
        if ($projectId === null || $projectId <= 0 || $user === '' || $role === '') {
            return;
        }

        $this->dataScopeContext->bind(
            new \App\Security\DataScope\ProjectScope($projectId, $user, $role),
        );
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
     * Adaptador legado que delega al único preflight. Sin ProjectScope solo
     * permite SQL compuesto exclusivamente por tablas Identity y sin override.
     */
    public function queryWithProject(string $sql, array $params = [], ?int $projectId = null): PDOStatement
    {
        $scope = $this->dataScope()->current();
        if (!$scope instanceof \App\Security\DataScope\ProjectScope) {
            if ($scope === null) {
                $guard = new \App\Security\DataScope\ProjectSqlGuard($this);
                if ($projectId === null && $guard->isIdentityOnly($sql, $this->tableScopeCatalog())) {
                    return $this->query($sql, $params);
                }
                throw new \App\Security\DataScope\MissingProjectScope(
                    'queryWithProject exige un ProjectScope activo.',
                );
            }
            throw new \App\Security\DataScope\ProjectScopeViolation(
                'queryWithProject solo acepta un ProjectScope de un proyecto.',
            );
        }
        if ($projectId !== null && $projectId !== $scope->projectId()) {
            throw new \App\Security\DataScope\ProjectScopeViolation(
                "El override {$projectId} contradice el ProjectScope {$scope->projectId()}.",
            );
        }

        return $this->query($sql, $params);
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
        $guard = new \App\Security\DataScope\ProjectSqlGuard($this);
        if ($guard->requiresDeferredExecution((string) $sql, $this->tableScopeCatalog())) {
            return new DatabasePreparedStatement($this, (string) $sql);
        }

        if (!$this->usesGlobalTables()) {
            $sql = $this->rewriteLegacyArchiveTables($sql);
        }
        return $this->pdo->prepare($sql);
    }

    private function rewriteGlobalTableQuery(string $sql, array $params, ?int $projectId, array $tables): array
    {
        if (!$this->usesGlobalTables()) {
            return [$this->rewriteLegacyArchiveTables($sql), $params];
        }

        $operation = strtoupper(strtok(ltrim($sql), " \t\r\n"));
        if (!in_array($operation, ['INSERT', 'SELECT', 'UPDATE', 'DELETE'], true)) {
            return [$sql, $params];
        }

        $matchedTables = array_values(array_intersect($tables, self::GLOBAL_TABLES));
        if ($operation !== 'INSERT' || $projectId === null || $matchedTables === []) {
            return [$sql, $params];
        }

        $insertSelect = $this->rewriteInsertSelect($sql, $params, $matchedTables[0], $projectId);
        if ($insertSelect !== null) {
            return $insertSelect;
        }

        return $this->rewriteInsert($sql, $params, $matchedTables[0], $projectId);
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

    /**
     * Compatibilidad de prefijos para el preflight SQL. La autoridad sigue siendo
     * el ProjectScope; este lookup solo traduce Base_de_Datos a su ID canónico.
     */
    public function resolveProjectIdForPrefix(string $prefix): ?int
    {
        return $this->resolveProjectIdByPrefix($prefix);
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
            // El `inTransaction()` NO sobra, aunque lo parezca: entre el
            // `beginTransaction()` de arriba y este `catch` puede haber ocurrido un
            // DDL implicito (CREATE/ALTER/DROP), que en MySQL confirma la
            // transaccion en curso y la cierra sin avisar. Llamar a `rollBack()`
            // sobre una conexion sin transaccion activa lanza una excepcion, y aqui
            // taparia la que de verdad importa: la que estamos manejando.
            // PHPStan cree que esta condicion es siempre falsa —recuerda que
            // `inTransaction()` valia false al fijar `$ownsTransaction` y no modela
            // que `beginTransaction()` lo cambia—, y su aviso esta suprimido en
            // `phpstan-baseline.neon` con esa explicacion. No lo simplifiques.
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
     * Prepara una consulta que se validará con sus parámetros completos en execute().
     * El override solo conserva compatibilidad de firma y no concede autoridad.
     *
     * @return DatabasePreparedStatement|PDOStatement
     */
    public function prepareWithProject(string $sql, ?int $projectId = null)
    {
        $scope = $this->dataScope()->current();
        if (!$scope instanceof \App\Security\DataScope\ProjectScope) {
            if ($scope === null) {
                throw new \App\Security\DataScope\MissingProjectScope(
                    'prepareWithProject exige un ProjectScope activo.',
                );
            }
            throw new \App\Security\DataScope\ProjectScopeViolation(
                'prepareWithProject solo acepta un ProjectScope de un proyecto.',
            );
        }
        if ($projectId !== null && $projectId !== $scope->projectId()) {
            throw new \App\Security\DataScope\ProjectScopeViolation(
                "El override {$projectId} contradice el ProjectScope {$scope->projectId()}.",
            );
        }

        return $this->prepare($sql);
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
