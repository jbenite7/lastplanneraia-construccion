<?php
session_start();
require_once __DIR__ . "/../../conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$dbName = $_POST['db'] ?? '';
$semana = (int)($_POST['semana'] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

try {
    // 1. Obtener fechas de la semana activa
    $sqlSemana = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbName}_semanas_activas WHERE Semana = ?";
    $stmtSemana = $db->query($sqlSemana, [$semana]);
    $dataSemana = $stmtSemana->fetch();

    if (!$dataSemana) {
        throw new Exception("Semana activa no encontrada.");
    }

    $fechaFinSem = date("Y-m-d", strtotime($dataSemana["Fecha_Fin_Sem"]));

    // 2. Identificar actividades ya programadas para evitar duplicados
    $stmtExistentes = $db->query("SELECT DISTINCT(Consecutivo_En_Programa) FROM {$dbName}_programacion_semanal WHERE Semana = ?", [$semana]);
    $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);
    
    $whereExistentes = "";
    $paramsInsert = [$semana, $semana];
    if (!empty($existentes)) {
        $placeholders = implode(',', array_fill(0, count($existentes), '?'));
        $whereExistentes = "AND Consecutivo_en_Programa NOT IN ($placeholders)";
        $paramsInsert = array_merge($paramsInsert, $existentes);
    }

    // 3. Insertar nuevas actividades desde el consolidado
    $sqlInsert = "INSERT INTO {$dbName}_programacion_semanal (
        Semana, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin, 
        Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad, 
        Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
    ) 
    SELECT 
        ?, Consecutivo_en_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin, 
        Sub_Contratista, Responsable_AIA, 'AIA', Ejecutado, medir_productividad, 
        Ruta_Critica, 
        CASE WHEN (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END, 
        '1', unidad, cantidad_ppto, codigo_actividad
    FROM {$dbName}_programa_consolidado 
    WHERE Semana = ? AND Titulo = 0 
      AND (Estado='A Tiempo' OR Estado='Atrasada' OR Estado='Debe Iniciar Esta Semana' OR Estado='Ya Debió Iniciar y Restricciones Pendientes' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')
      $whereExistentes";

    $db->query($sqlInsert, $paramsInsert);

    // 4. Actualizar detalles y compromisos de las actividades programadas
    $stmtSemanal = $db->query("SELECT Consecutivo_En_Programa, Ejecutado, Compromiso, Activa FROM {$dbName}_programacion_semanal WHERE Semana = ? AND Activa != 'NA'", [$semana]);
    $actividadesSemanales = $stmtSemanal->fetchAll();

    foreach ($actividadesSemanales as $item) {
        $consecutivo = $item["Consecutivo_En_Programa"];
        
        $stmtCons = $db->query("SELECT * FROM {$dbName}_programa_consolidado WHERE Semana = ? AND Consecutivo_en_programa = ?", [$semana, $consecutivo]);
        $dataCons = $stmtCons->fetch();

        if (!$dataCons) continue;

        $fInicioAct = strtotime($dataCons['Fecha_Inicio']);
        $fFinAct    = strtotime($dataCons['Fecha_Fin']);
        $fFinSemTS  = strtotime($fechaFinSem);

        if ($fFinSemTS >= $fInicioAct && $fFinAct >= $fFinSemTS) {
            $totalDiasAct = floor(($fFinAct - $fInicioAct) / 86400) + 1;
            $diasHastaFinSem = floor(($fFinSemTS - $fInicioAct) / 86400) + 1;
            $ejecutadoTeorico = $diasHastaFinSem / $totalDiasAct;
        } else if ($fFinAct < $fFinSemTS) {
            $ejecutadoTeorico = 1;
        } else {
            $ejecutadoTeorico = 0;
        }

        $ejecutadoActual = (float)$dataCons["Ejecutado"];
        $cantidadPpto = (float)($dataCons["cantidad_ppto"] ?? 0);
        
        if ($cantidadPpto <= 0) {
            $compromisoCalculado = round(($ejecutadoTeorico - $ejecutadoActual) * 100, 1);
        } else {
            $compromisoCalculado = round(($ejecutadoTeorico - $ejecutadoActual) * $cantidadPpto, 1);
        }

        $compromisoFinal = ($compromisoCalculado <= 0) ? null : $compromisoCalculado;
        if (!empty($item["Compromiso"]) && (float)$item["Ejecutado"] == $ejecutadoActual) {
            $compromisoFinal = $item["Compromiso"];
        }

        $stmtAnterior = $db->query("SELECT Sub_Contratista, Responsable_AIA, Empresa, Descripcion, Ubicacion FROM {$dbName}_programacion_semanal WHERE Semana = ? AND Consecutivo_En_programa = ?", [$semana - 1, $consecutivo]);
        $dataAnt = $stmtAnterior->fetch();

        $sub = $dataCons["Sub_Contratista"] ?: ($dataAnt["Sub_Contratista"] ?? null);
        $resp = $dataCons["Responsable_AIA"] ?: ($dataAnt["Responsable_AIA"] ?? null);
        $empresa = $dataAnt["Empresa"] ?? 'AIA';
        $desc = $dataAnt["Descripcion"] ?? null;
        $ubica = $dataAnt["Ubicacion"] ?? null;

        $sqlActSemana = "UPDATE {$dbName}_programacion_semanal SET 
            Fecha_Inicio = ?, Fecha_Fin = ?, Sub_Contratista = ?, Responsable_AIA = ?, 
            Ejecutado = ?, medir_productividad = ?, Critica = ?, 
            Atrasada = (CASE WHEN ? = 'Atrasada' THEN 1 ELSE 0 END), 
            Descripcion = ?, Ubicacion = ?, Empresa = ?, Unidad = ?, 
            cantidad_ppto = ?, codigo_actividad = ?, Compromiso = ?
            WHERE Semana = ? AND Consecutivo_En_Programa = ?";
        
        $db->query($sqlActSemana, [
            $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
            $ejecutadoActual, (int)($dataCons["medir_productividad"] ?? 0), (int)($dataCons["Ruta_Critica"] ?? 0),
            $dataCons["Estado"], $desc, $ubica, $empresa, $dataCons["unidad"],
            ($cantidadPpto > 0 ? $cantidadPpto : null), $dataCons["codigo_actividad"], $compromisoFinal,
            $semana, $consecutivo
        ]);
    }

    // 5. Limpieza
    $stmtConsLimpieza = $db->query("SELECT Consecutivo_en_Programa FROM {$dbName}_programa_consolidado WHERE Semana = ? AND Ejecutado = 0 AND Semanas_Inicio > 0 AND Activa != 'NA'", [$semana]);
    $noIniciadas = $stmtConsLimpieza->fetchAll(PDO::FETCH_COLUMN);
    
    $whereLimpieza = "";
    $paramsDelete = [$semana];
    if (!empty($noIniciadas)) {
        $placeholders = implode(',', array_fill(0, count($noIniciadas), '?'));
        $whereLimpieza = "OR Consecutivo_En_Programa IN ($placeholders)";
        $paramsDelete = array_merge($paramsDelete, $noIniciadas);
    }

    $sqlDeleteLimpieza = "DELETE FROM {$dbName}_programacion_semanal WHERE Semana = ? AND ((Ejecutado = 1 AND Activa != 'NA') $whereLimpieza)";
    $db->query($sqlDeleteLimpieza, $paramsDelete);

    // 6. Actualización final
    $db->query("UPDATE {$dbName}_programacion_semanal ps
                JOIN {$dbName}_programa_consolidado pc ON ps.Consecutivo_En_Programa = pc.Consecutivo_en_Programa AND ps.Semana = pc.Semana
                SET ps.Prog_Sin_Restricciones_100 = (CASE WHEN pc.Estado_Restricciones < 1 THEN 1 ELSE 0 END),
                    ps.Ejecutado = pc.Ejecutado
                WHERE ps.Semana = ? AND ps.Activa != 'NA'", [$semana]);

    $db->query("UPDATE {$dbName}_programacion_semanal SET Prog_Sin_Restricciones_100 = 0 WHERE Semana = ? AND Activa = 'NA'", [$semana]);

    $db->logActivity('Sistema', 'AUTOPROGRAMAR', "Actividades autoprogramadas para semana $semana");
    echo json_encode("OK", JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en autoprogramar_actividades.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}