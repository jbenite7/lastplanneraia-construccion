<?php

namespace Admin\Core;

use Database;

class RoleManager
{
    private static $roleAliases = [
        'P' => 'D',
        'U' => 'V',
    ];

    private static $roles = [
        'A'   => ['name' => 'Administrador / Director', 'color' => 'primary', 'description' => 'Control total del proyecto.'],
        'D'   => ['name' => 'Director Funcional', 'color' => 'indigo', 'description' => 'Control total funcional sin administracion del sistema.'],
        'R'   => ['name' => 'Residente de Obra', 'color' => 'secondary', 'description' => 'Gestión operativa de obra.'],
        'S'   => ['name' => 'Seguridad (SST)', 'color' => 'success', 'description' => 'Módulo de Seguridad y Salud.'],
        'G'   => ['name' => 'Ambiental', 'color' => 'teal', 'description' => 'Módulo de gestión ambiental.'],
        'SG'  => ['name' => 'SST + Ambiental', 'color' => 'info', 'description' => 'Rol híbrido SST y Ambiental.'],
        'OT'  => ['name' => 'Oficina Técnica / Compras', 'color' => 'warning', 'description' => 'Control de costos y compras.'],
        'DCV' => ['name' => 'Profesional DCV', 'color' => 'purple', 'description' => 'Módulo especializado DCV.'],
        'V'   => ['name' => 'Visualizador', 'color' => 'dark', 'description' => 'Acceso de solo lectura (compatibilidad).'],
        'C'   => ['name' => 'Subcontratista', 'color' => 'orange', 'description' => 'Acceso limitado para terceros.'],
    ];

    private static function normalizeRoleCode($code)
    {
        $code = strtoupper(trim((string)$code));
        if (isset(self::$roleAliases[$code])) {
            $code = self::$roleAliases[$code];
        }

        if (!isset(self::$roles[$code])) {
            return 'C';
        }

        return $code;
    }

    public static function normalizeRole($code)
    {
        return self::normalizeRoleCode($code);
    }

    public static function getAll()
    {
        return self::$roles;
    }
    public static function getRoleName($code)
    {
        $code = self::normalizeRoleCode($code);

        return self::$roles[$code]['name'] ?? 'Desconocido';
    }
    public static function getRoleColor($code)
    {
        $code = self::normalizeRoleCode($code);

        return self::$roles[$code]['color'] ?? 'light';
    }

    /**
     * Normaliza un cargo para eliminar ruido (acentos, géneros, artículos, etc.)
     * Ejemplo: "Directora de Obra" -> "director obra"
     */
    public static function cleanCargo($cargo)
    {
        if (empty($cargo)) {
            return '';
        }

        // 1. A minúsculas y quitar acentos
        $cargo = mb_strtolower($cargo);
        $cargo = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $cargo);

        // 2. Normalizar géneros comunes (a -> o)
        $cargo = preg_replace('/\b(directora|coordinadora|residenta|analista|auxiliara)\b/', '$1', $cargo);
        $cargo = str_replace(['directora', 'coordinadora'], ['director', 'coordinador'], $cargo);

        // 3. Quitar artículos y conectores (de, del, la, el, y)
        $cargo = preg_replace('/\b(de|del|la|el|y|en|con)\b/', '', $cargo);

        // 4. Limpiar espacios y caracteres especiales
        $cargo = preg_replace('/[^a-z0-9 ]/', '', $cargo);
        $cargo = preg_replace('/\s+/', ' ', trim($cargo));

        return $cargo;
    }

    /**
     * Sugiere un rol basado en el conocimiento aprendido o heurística.
     * Incluye lógica de Fuzzy Matching para errores de ortografía.
     */
    public static function suggestRoleByCargo($cargo)
    {
        if (empty($cargo)) {
            return 'V';
        }

        $cleanCargo = self::cleanCargo($cargo);
        $db = Database::getInstance();

        // 1. INTELIGENCIA: Buscar coincidencia exacta
        $learned = $db->query("SELECT suggested_role FROM role_intelligence WHERE cargo_title = ? LIMIT 1", [$cleanCargo])->fetch();
        if ($learned) {
            return self::normalizeRoleCode($learned['suggested_role']);
        }

        // 2. FUZZY MATCHING: Buscar coincidencias por aproximación (errores de ortografía)
        $allLearned = $db->query("SELECT cargo_title, suggested_role FROM role_intelligence")->fetchAll();
        $bestMatch = null;
        $shortestDistance = -1;

        foreach ($allLearned as $row) {
            $lev = levenshtein($cleanCargo, $row['cargo_title']);

            // Si la distancia es pequeña (ej: max 2 errores en palabras largas)
            if ($lev <= 2 || ($lev <= 3 && strlen($cleanCargo) > 10)) {
                if ($lev < $shortestDistance || $shortestDistance == -1) {
                    $shortestDistance = $lev;
                    $bestMatch = self::normalizeRoleCode($row['suggested_role']);
                }
            }
        }

        if ($bestMatch) {
            return $bestMatch;
        }

        // 3. HEURÍSTICA: Si no hay aprendizaje previo, usar reglas de patrones
        $c = $cleanCargo;
        if (str_contains($c, 'sst') && str_contains($c, 'ambiental')) {
            return 'SG';
        }
        if (str_contains($c, 'sst')) {
            return 'S';
        }
        if (str_contains($c, 'ambiental')) {
            return 'G';
        }
        if (str_contains($c, 'costo') || str_contains($c, 'compra') || str_contains($c, 'licitaci') || str_contains($c, 'aprovisionamiento')) {
            return 'OT';
        }
        if (str_contains($c, 'oficina tecnica')) {
            return 'OT';
        }
        if (str_contains($c, 'dcv')) {
            return 'DCV';
        }
        if (str_contains($c, 'diseno') && str_contains($c, 'construccion')) {
            return 'DCV';
        }
        if (str_contains($c, 'planeaci') || str_contains($c, 'programaci') || str_contains($c, 'control')) {
            return 'D';
        }
        if (str_contains($c, 'administrador')) {
            return 'A';
        }
        if (str_contains($c, 'director') || str_contains($c, 'gerente') || str_contains($c, 'jefe')) {
            return 'A';
        }
        if (str_contains($c, 'residente')) {
            return 'R';
        }
        if (str_contains($c, 'interventor') || str_contains($c, 'coordinador') || str_contains($c, 'invitado') || str_contains($c, 'vp')) {
            return 'V';
        }

        return 'V';
    }

    public static function learn($cargo, $role)
    {
        $cleanCargo = self::cleanCargo($cargo);
        $normalizedRole = self::normalizeRoleCode($role);
        if (empty($cleanCargo) || empty($normalizedRole)) {
            return false;
        }

        $db = Database::getInstance();

        return $db->query(
            "INSERT INTO role_intelligence (cargo_title, suggested_role) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE suggested_role = ?",
            [$cleanCargo, $normalizedRole, $normalizedRole]
        );
    }
}
