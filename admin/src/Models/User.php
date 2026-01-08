<?php

namespace Admin\Models;

use Database;

class User
{
    private $db;
    private $table = 'general_usuarios';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Check if a user has a specific permission or role.
     * 
     * @param string $permission The permission or role name
     * @param array $user The user data (optional, defaults to checking the current instance if it were an object)
     * @return bool
     */
    public static function can($user, $permission)
    {
        if (!$user || !isset($user['permiso'])) {
            return false;
        }

        // Admin has all permissions
        if ($user['permiso'] === 'Admin') {
            return true;
        }

        // Exact match
        if ($user['permiso'] === $permission) {
            return true;
        }

        // Logic for multiple roles could be added here
        return false;
    }

    /**
     * Find a user by their ID.
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return $stmt->fetch();
    }

    /**
     * Find a user by their username.
     *
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE usuario = ? LIMIT 1", [$username]);
        return $stmt->fetch();
    }

    /**
     * Get all users.
     *
     * @return array
     */
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return bool
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (nombre, email, cargo, proyecto, permiso, usuario, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $this->db->query($sql, [
            $data['nombre'],
            $data['email'],
            $data['cargo'],
            $data['proyecto'],
            $data['permiso'],
            $data['usuario'],
            $password
        ]);
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $fields = [
            'nombre = ?',
            'email = ?',
            'cargo = ?',
            'proyecto = ?',
            'permiso = ?',
            'usuario = ?'
        ];
        
        $params = [
            $data['nombre'],
            $data['email'],
            $data['cargo'],
            $data['proyecto'],
            $data['permiso'],
            $data['usuario']
        ];

        // If password is provided, update it too
        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
