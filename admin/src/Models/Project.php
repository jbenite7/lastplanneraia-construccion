<?php

namespace Admin\Models;

use Database;

class Project
{
    private $db;
    private $table = 'general_proyectos_procesos';

    public function __construct(Database $db)
    {
        $this->db = $db;
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
}
