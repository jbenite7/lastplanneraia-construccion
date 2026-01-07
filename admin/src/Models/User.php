<?php

namespace Admin\Models;

use Database;

class User
{
    private $db;
    private $table = 'general_usuarios';

    public function __construct(Database $db)
    {
        $this->db = $db;
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
}
