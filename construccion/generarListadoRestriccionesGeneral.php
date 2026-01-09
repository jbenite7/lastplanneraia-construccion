<?php
session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$db->logActivity('Sistema', 'GENERAR_RESTRICCIONES_GRAL', "Iniciando consolidación de listado de restricciones general");

try {
    // 1. Limpiar tabla consolidada
    $db->query("TRUNCATE TABLE general_informe_restricciones_consolidado");

    // 2. Obtener proyectos activos
    $stmtProyectos = $db->query("SELECT * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1");
    $proyectos = $stmtProyectos->fetchAll();

    echo "<li>Liberación de Restricciones";

    // Definición de tipos de restricciones y sus columnas correspondientes
    $restricciones = [
        'D_y_E' => 'D_y_E',
        'Materiales' => 'Materiales',
        'MdeO' => 'MdeO',
        'Equipos' => 'Equipos',
        'Predecesora' => 'Predecesora',
        'Pdto_Cons' => 'Pdto_Cons',
        'Modelo' => 'Modelo'
    ];

    foreach ($proyectos as $data1) {
        $proyecto = $data1["Proyecto_Proceso"];
        $dbPrefix = $data1["Base_de_Datos"];

        foreach ($restricciones as $nombreLabel => $columna) {
            $sqlInsert = "INSERT INTO general_informe_restricciones_consolidado (
                Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Actividad, 
                Fecha_Inicio, Fecha_Fin, Semanas_Inicio, Restriccion, valorRestriccion, estadoActividad
            )
            SELECT 
                ? AS Proyecto, 
                prog.Semana, 
                sem.Fecha_Inicio_Sem, 
                sem.Fecha_Fin_Sem, 
                prog.Actividad, 
                prog.Fecha_Inicio, 
                prog.Fecha_Fin, 
                prog.Semanas_Inicio, 
                ? AS Restriccion,
                (CASE 
                    WHEN prog.{$columna} IS NULL THEN '0%' 
                    WHEN prog.{$columna} = 'N/A' THEN 'N/A' 
                    ELSE CONCAT(FLOOR(prog.{$columna} * 100), '%') 
                END) AS valorRestriccion,
                prog.Ejecutado
            FROM {$dbPrefix}_programa_consolidado AS prog
            LEFT JOIN {$dbPrefix}_semanas_activas AS sem ON sem.Semana = prog.Semana
            WHERE prog.{$columna} != 'N/A' 
              AND prog.Titulo = 0 
              AND prog.Semanas_Inicio < 7 
              AND prog.Ejecutado < 1 
              AND prog.Semana >= ((SELECT MAX(Semana) FROM {$dbPrefix}_programa_consolidado) - 3)";

            $db->query($sqlInsert, [$proyecto, $nombreLabel]);
        }

        // Limpiar registros inconsistentes (NULLs)
        $db->query("DELETE FROM general_informe_restricciones_consolidado WHERE Fecha_Inicio_Sem IS NULL OR Fecha_Fin_Sem IS NULL");

        echo "<li>$proyecto - OK";
    }

    $db->logActivity('Sistema', 'GENERAR_RESTRICCIONES_GRAL', "Consolidación de restricciones completada con éxito");

} catch (Exception $e) {
    error_log("Error en generarListadoRestriccionesGeneral.php: " . $e->getMessage());
    echo "<li>Error: " . $e->getMessage();
}
?>