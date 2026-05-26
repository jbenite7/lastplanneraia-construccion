<?php

namespace App\Services;

class ProgramaConsolidadoNormalizationService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: \Database::getInstance();
    }

    public function normalizeChapters(string $dbPrefix, int $semana): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            throw new \InvalidArgumentException('Base de datos inválida.');
        }

        if ($semana <= 0) {
            return;
        }

        $sql = "UPDATE {$dbPrefix}_programa_consolidado 
                SET Ejecutado = 0, Ejecutado_Siguiente_Semana = NULL, Estado = 'Capítulo'
                WHERE Titulo = 1 AND Semana = ?";

        $this->db->query($sql, [$semana]);
    }
}
