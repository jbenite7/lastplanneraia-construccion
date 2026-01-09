<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

try {
    $sql = "SELECT 
                (MAX(P_Completado) - MIN(P_Completado)) AS rango, 
                (1 + 3.332 * LOG10(COUNT(P_Completado))) AS numeroClases, 
                COUNT(P_Completado) AS numeroDatos 
            FROM general_informe_consolidado 
            WHERE P_Completado IS NOT NULL 
              AND (Activa = 1 OR Activa = 'NA')";

    $stmt = $db->query($sql);
    $data = $stmt->fetch();

    $rango = $data["rango"] ?? 0;
    $numeroClases = $data["numeroClases"] ?? 0;
    $numeroDatos = $data["numeroDatos"] ?? 0;

    echo "$rango, $numeroClases, $numeroDatos";

} catch (Exception $e) {
    error_log("Error en generarTablaHTMLProgramacionSemanal.php: " . $e->getMessage());
}

// Mantenemos session_destroy() según lógica original
session_destroy();
?>