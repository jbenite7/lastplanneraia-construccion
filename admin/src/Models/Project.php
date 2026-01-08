<?php

namespace Admin\Models;

use Database;

class Project
{
    private $db;
    private $table = 'general_proyectos_procesos';

    public function __construct($db)
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

    /**
     * Update an existing project.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                Proyecto_Proceso = ?, 
                Base_de_Datos = ?, 
                Area = ?,
                Activo = ?,
                Acceso = ?,
                pdcActivo = ?,
                fechaInicioLineaBase = ?,
                fechaFinLineaBase = ?,
                costoDiaRetraso = ?,
                urlCambios = ?
                WHERE Id = ?";
        
        return $this->db->query($sql, [
            $data['nombre'],
            $data['base_datos'],
            $data['area'],
            $data['activo'],
            $data['acceso'],
            $data['pdc_activo'],
            $data['fecha_inicio_lb'] ?: null,
            $data['fecha_fin_lb'] ?: null,
            $data['costo_retraso'],
            $data['url_cambios'],
            $id
        ]);
    }

    /**
     * Create a new project.
     *
     * @param array $data
     * @return bool
     */
    public function create($data)
    {
        // Auto-generate Base_de_Datos if not provided
        $base_datos = $this->generateDatabaseName($data['nombre'], $data['area']);

        $sql = "INSERT INTO {$this->table} (
                    Proyecto_Proceso, 
                    Base_de_Datos, 
                    Area, 
                    Activo, 
                    Acceso, 
                    pdcActivo, 
                    fechaInicioLineaBase, 
                    fechaFinLineaBase, 
                    costoDiaRetraso, 
                    urlCambios
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->query($sql, [
            $data['nombre'],
            $base_datos,
            $data['area'],
            $data['activo'] ?? 1,
            $data['acceso'] ?? 1,
            $data['pdc_activo'] ?? 0,
            $data['fecha_inicio_lb'] ?: null,
            $data['fecha_fin_lb'] ?: null,
            $data['costo_retraso'] ?? 5000000,
            $data['url_cambios'] ?? null
        ]);
    }

    /**
     * Genera el nombre de la base de datos siguiendo el patrón del proyecto.
     */
    private function generateDatabaseName($name, $area)
    {
        // Slugify name: lowercase, remove accents, replace spaces/special chars with underscores
        $slug = $this->slugify($name);
        
        // Pattern from general_proyectos_procesos.sql:
        // PI projects usually have _pi suffix
        if (strtoupper($area) === 'PI') {
            if (!str_ends_with($slug, '_pi')) {
                $slug .= '_pi';
            }
        }
        
        return $slug;
    }

    private function slugify($text)
    {
        // Stop words en español
        $stop_words = ['el', 'la', 'los', 'las', 'de', 'del', 'y', 'en', 'para', 'con', 'un', 'una', 'unos', 'unas', 'a', 'al', 'o', 'e', 'u'];
        
        // Mapa para convertir números a romanos (comúnmente usados en nombres de proyectos)
        $number_map = [
            '1' => 'i', '2' => 'ii', '3' => 'iii', '4' => 'iv', '5' => 'v',
            '6' => 'vi', '7' => 'vii', '8' => 'viii', '9' => 'ix', '10' => 'x'
        ];

        // Transliterar para quitar acentos
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Convertir a minúsculas
        $text = strtolower(trim($text));

        // Reemplazar números por su representación romana si están en el mapa
        $words = preg_split('~[^\pL\d]+~u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $processed_words = [];
        foreach ($words as $word) {
            // Si es un número en el mapa, reemplazar
            if (isset($number_map[$word])) {
                $processed_words[] = $number_map[$word];
                continue;
            }
            
            // Si es un número mayor no mapeado, lo dejamos (o podríamos ignorarlo si la orden es "remover")
            // Pero usualmente se prefiere mantener la distinción. Aquí removemos números no mapeados
            // para cumplir estrictamente con "Remover números".
            if (is_numeric($word)) {
                continue; 
            }

            // Filtrar stop words
            if (!in_array($word, $stop_words)) {
                $processed_words[] = $word;
            }
        }

        // Unir con underscore
        $text = implode('_', $processed_words);

        if (empty($text)) {
            return 'n_a';
        }

        return $text;
    }

    /**
     * Delete a project.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE Id = ?", [$id]);
    }
}