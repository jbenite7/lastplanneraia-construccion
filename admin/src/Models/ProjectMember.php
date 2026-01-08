<?php

namespace Admin\Models;

use Database;

class ProjectMember
{
    private $db;
    private $table = 'project_members';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get all members of a specific project.
     *
     * @param int $projectId
     * @return array
     */
    public function getByProject($projectId)
    {
        $sql = "SELECT pm.*, u.nombre, u.email, u.usuario, u.cargo 
                FROM {$this->table} pm
                JOIN general_usuarios u ON pm.user_id = u.id
                WHERE pm.project_id = ?
                ORDER BY u.nombre ASC";
        
        $stmt = $this->db->query($sql, [$projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Add a member to a project.
     *
     * @param int $projectId
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function add($projectId, $userId, $role = 'U')
    {
        $sql = "INSERT IGNORE INTO {$this->table} (project_id, user_id, role) VALUES (?, ?, ?)";
        return $this->db->query($sql, [$projectId, $userId, $role]);
    }

    /**
     * Remove a member from a project.
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public function remove($projectId, $userId)
    {
        $sql = "DELETE FROM {$this->table} WHERE project_id = ? AND user_id = ?";
        return $this->db->query($sql, [$projectId, $userId]);
    }

    /**
     * Get users that are NOT members of a specific project.
     * Useful for the "Add Member" dropdown/search.
     *
     * @param int $projectId
     * @return array
     */
    public function getNonMembers($projectId)
    {
        $sql = "SELECT id, nombre, usuario, email, cargo 
                FROM general_usuarios 
                WHERE id NOT IN (
                    SELECT user_id FROM {$this->table} WHERE project_id = ?
                )
                ORDER BY nombre ASC";
        
        $stmt = $this->db->query($sql, [$projectId]);
        return $stmt->fetchAll();
    }
}
