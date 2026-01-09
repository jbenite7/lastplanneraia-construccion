<?php

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $db   = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
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
                session_start();
            }
            
            $usuario = $_SESSION['usuario'] ?? $_SESSION['admin_user']['usuario'] ?? 'Sistema';
            $id_sesion = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            $sql = "INSERT INTO general_auditoria_acciones 
                    (usuario, id_sesion, modulo, accion, descripcion, ip_address, proyecto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
            return $this->query($sql, [
                $usuario, 
                $id_sesion, 
                $modulo, 
                $accion, 
                $descripcion, 
                $ip, 
                $proyecto
            ]);
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

    // Evitar clonación del objeto
    private function __clone() {}

    // Evitar deserialización del objeto
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}