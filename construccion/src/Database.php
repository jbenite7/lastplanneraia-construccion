<?php

class Database
{
    private $pdo;

    public function __construct($dsn, $user, $password, $options)
    {
        try {
            $this->pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            // En un entorno de producción, esto debería registrarse en un archivo de log.
            // Por ahora, terminamos la ejecución para evitar exponer información sensible.
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            die('Error: No se pudo conectar a la base de datos. Por favor, intente más tarde.');
        }
    }

    /**
     * Ejecuta una consulta preparada de forma segura.
     *
     * @param string $sql La consulta SQL con placeholders (ej. SELECT * FROM users WHERE id = ?).
     * @param array $params Un array de parámetros para vincular a la consulta.
     * @return PDOStatement El objeto PDOStatement resultante.
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Manejo de errores en consultas
            error_log('Error en la consulta SQL: ' . $e->getMessage());
            // En desarrollo, podría ser útil mostrar el error. En producción, un mensaje genérico.
            die('Error al ejecutar la consulta.');
        }
    }

    // Podríamos añadir más métodos de ayuda si es necesario, como para transacciones.
}
