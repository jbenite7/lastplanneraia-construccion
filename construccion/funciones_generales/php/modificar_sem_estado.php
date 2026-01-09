<?php
/** @var Database $db */
// $db ya está disponible si es llamado desde actualizarEjecucion.php
if (!isset($db)) {
    require_once __DIR__ . "/../../conexion.php";
    $db = Database::getInstance();
}

sleep(5);

try {
    $sqlSelect = "SELECT * FROM {$dbName}_programa_consolidado WHERE Semana = ?";
    $stmt = $db->query($sqlSelect, [$semana]);
    $actividades = $stmt->fetchAll();

    if (count($actividades) > 0) {
        foreach ($actividades as $data) {
            $Id = $data["Consecutivo_en_Programa"];
            $Titulo = (int)($data["Titulo"] ?? 0);
            $hoy = $f_inicio_sem;
            $manana = date("Y-m-d", strtotime($data["Fecha_Inicio"] ?? 'now'));
            
            $dias = floor((strtotime($manana) - strtotime($hoy)) / 86400);
            $semanas = floor($dias / 7);

            $Estado_Restricciones = null;

            if ($Titulo === 0) {
                $campos = ["D_y_E", "Materiales", "MdeO", "Equipos", "Predecesora", "Pdto_Cons", "Modelo"];
                $conteo = 0;
                $suma = 0;

                foreach ($campos as $campo) {
                    $valor = $data[$campo];
                    if ($valor !== "N/A" && $valor !== null) {
                        $conteo++;
                        $suma += round((float)$valor, 5);
                    }
                }

                if ($conteo === 0) {
                    $Estado_Restricciones = 1;
                } else {
                    $Estado_Restricciones = round(($suma / $conteo), 5);
                }
            }

            if ($data["Fecha_Inicio"] === null && $data["Fecha_Fin"] === null) {
                $semanas = null;
            } else {
                if ($semanas < 0) {
                    $semanas = 0;
                }
            }

            $sqlUpdateActividad = "UPDATE {$dbName}_programa_consolidado 
                                  SET Semanas_Inicio = ?, Estado_Restricciones = ? 
                                  WHERE Consecutivo_en_Programa = ? AND Semana = ?";
            
            $valEstadoRestricciones = ($Titulo === 1) ? null : $Estado_Restricciones;

            $db->query($sqlUpdateActividad, [
                $semanas,
                $valEstadoRestricciones,
                $Id,
                $semana
            ]);
        }
    }

    sleep(0.5);

    $db->query("UPDATE {$dbName}_programa_consolidado SET Ruta_Critica = NULL WHERE Titulo = 1 AND Semana = ?", [$semana]);

    $db->query("UPDATE {$dbName}_programa_consolidado SET Ejecutado = NULL, Semanas_Inicio = NULL WHERE Fecha_Inicio IS NULL AND Fecha_Fin IS NULL AND Titulo = 1 AND Semana = ?", [$semana]);

    $sqlEstado = "UPDATE {$dbName}_programa_consolidado SET
       Estado = CASE
          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) < 0 THEN 'Terminada Antes'

          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) = 0 THEN 'Terminada'

          WHEN Ejecutado < 1 AND Ejecutado >= 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) > 0 THEN 'Atrasada'

          WHEN Ejecutado < 1 AND Ejecutado > 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) <= 0 THEN 'A Tiempo'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END), 3) = 0 AND Ejecutado = 0 THEN 'Debe Iniciar esta Semana'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END) - Ejecutado, 3) > 0 AND Ejecutado = 0 THEN 'Ya Debió Iniciar y Restricciones Pendientes'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF(?, Fecha_Inicio) AND DATEDIFF(?, Fecha_Inicio) >= 1 THEN (DATEDIFF(?, Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF(?, Fecha_Inicio) THEN 1 WHEN DATEDIFF(?, Fecha_Inicio) < 1 THEN 0 END), 3) = 0 AND Ejecutado = 0 THEN 'Debe Iniciar esta Semana y Restricciones Pendientes'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado = 0 THEN 'En Liberación de Restricciones'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado > 0 THEN 'A Tiempo'

          ELSE 'No Requerida'
       END
      WHERE Titulo = 0 AND Semana = ?";
    
    $params = array_fill(0, 21, $f_inicio_sem);
    $params[] = $semana;

    $db->query($sqlEstado, $params);

    $respuesta = [$semana, $ejecucionActualizada, $semanalConfirmada];
    echo json_encode($respuesta);

} catch (Exception $e) {
    error_log("Error en modificar_sem_estado.php: " . $e->getMessage());
    die("Error al modificar el estado de la semana.");
}