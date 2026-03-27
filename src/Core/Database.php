<?php

class Database
{
    private static $instance = null;
    private $pdo;

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
    private function __clone()
    {
    }

    // Evitar deserialización del objeto
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}
