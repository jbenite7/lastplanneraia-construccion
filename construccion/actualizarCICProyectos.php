<?php
session_start();
require_once("conexion.php");

// Obtener la semana de la sesión o de la petición
$semana = filter_var($_SESSION['semana'] ?? ($_REQUEST['semana'] ?? 0), FILTER_VALIDATE_INT);

if (!$semana) {
    echo "Error: Semana no definida.";
    exit;
}

// Consultar proyectos activos de construcción
$queryProyectos = "SELECT Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE Area = 'Construccion' AND Activo = 1";
$proyectos = $db->fetchAll($queryProyectos);

echo "<li>Calificación Integral:";

foreach ($proyectos as $dataProyectos) {
    $proyecto = $dataProyectos["Proyecto_Proceso"];
    $dbName = $dataProyectos["Base_de_Datos"];

    // Validar nombre de base de datos para prevenir inyecciones en nombres de tablas
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        echo "<li>$proyecto - Error: Nombre de base de datos inválido.";
        continue;
    }

    // Verificar si existen registros para la semana en la tabla de subcontratistas (_cic)
    $queryCic = "SELECT COUNT(*) as conteo FROM {$dbName}_cic WHERE Semana = :semana";
    $dataCic = $db->fetch($queryCic, ['semana' => $semana]);
    $conteo = $dataCic['conteo'] ?? 0;

    if ($conteo > 0) {
        actualizar_PAC_subcontratistas($semana, $dbName, $db, $semana);
    }

    // Verificar si existen registros para la semana en la tabla de profesionales (_cip)
    $queryCip = "SELECT COUNT(*) as conteo FROM {$dbName}_cip WHERE Semana = :semana";
    $dataCip = $db->fetch($queryCip, ['semana' => $semana]);
    $conteo1 = $dataCip['conteo'] ?? 0;

    if ($conteo1 > 0) {
        actualizar_PAC_profesionales($semana, $dbName, $db, $semana);
    }

    // Obtener subcontratistas ya registrados para excluirlos
    $queryExistingCic = "SELECT subcontratista FROM {$dbName}_cic WHERE Semana = :semana";
    $existingCic = $db->fetchAll($queryExistingCic, ['semana' => $semana]);
    $excludeSubcontratistas = array_column($existingCic, 'subcontratista');

    // Obtener profesionales ya registrados para excluirlos
    $queryExistingCip = "SELECT profesional FROM {$dbName}_cip WHERE Semana = :semana";
    $existingCip = $db->fetchAll($queryExistingCip, ['semana' => $semana]);
    $excludeProfesionales = array_column($existingCip, 'profesional');

    generar_subcontratistas($semana, $dbName, $db, $excludeSubcontratistas);
    generar_profesionales($semana, $dbName, $db, $excludeProfesionales);

    echo "<li>$proyecto - OK";
}

function generar_subcontratistas($semana, $dbName, $db, $excludeSubcontratistas) {
    $params = ['semana' => $semana];
    $sqlExclude = "";
    
    if (!empty($excludeSubcontratistas)) {
        $placeholders = [];
        foreach ($excludeSubcontratistas as $i => $sub) {
            $key = "sub_$i";
            $placeholders[] = ":$key";
            $params[$key] = $sub;
        }
        $sqlExclude = " AND Sub_Contratista NOT IN (" . implode(',', $placeholders) . ")";
    }

    $query = "SELECT DISTINCT Sub_Contratista FROM {$dbName}_programacion_semanal 
              WHERE Semana = :semana $sqlExclude 
              AND Sub_Contratista != '' 
              AND (Activa = '1' OR Activa = 'NA') 
              AND (PAC = '1' OR PAC = '0')";
    
    $results = $db->fetchAll($query, $params);

    foreach ($results as $row) {
        $subcontratista = $row["Sub_Contratista"];
        $db->execute("INSERT INTO {$dbName}_cic (Semana, subcontratista) VALUES (0, :sub)", ['sub' => $subcontratista]);
    }

    actualizar_PAC_subcontratistas($semana, $dbName, $db, 0);
    actualizar_integral_subcontratistas($semana, $dbName, $db);
}

function generar_profesionales($semana, $dbName, $db, $excludeProfesionales) {
    $params = ['semana' => $semana];
    $sqlExclude = "";
    
    if (!empty($excludeProfesionales)) {
        $placeholders = [];
        foreach ($excludeProfesionales as $i => $prof) {
            $key = "prof_$i";
            $placeholders[] = ":$key";
            $params[$key] = $prof;
        }
        $sqlExclude = " AND Responsable_AIA NOT IN (" . implode(',', $placeholders) . ")";
    }

    $query = "SELECT DISTINCT Responsable_AIA FROM {$dbName}_programacion_semanal 
              WHERE Semana = :semana $sqlExclude 
              AND Responsable_AIA != '' 
              AND (Activa = '1' OR Activa = 'NA') 
              AND (PAC = '1' OR PAC = '0')";
    
    $results = $db->fetchAll($query, $params);

    foreach ($results as $row) {
        $profesional = $row["Responsable_AIA"];
        $db->execute("INSERT INTO {$dbName}_cip (Semana, profesional) VALUES (0, :prof)", ['prof' => $profesional]);
    }

    actualizar_PAC_profesionales($semana, $dbName, $db, 0);
    actualizar_integral_profesionales($semana, $dbName, $db);
}

function actualizar_PAC_subcontratistas($semana, $dbName, $db, $semanaFiltro) {
    $query = "SELECT DISTINCT Sub_Contratista FROM {$dbName}_programacion_semanal 
              WHERE Semana = :semana AND Sub_Contratista != '' 
              AND (Activa = '1' OR Activa = 'NA') AND (PAC = '1' OR PAC = '0')";
    
    $subcontratistas = $db->fetchAll($query, ['semana' => $semana]);
    $processedSubs = [];

    foreach ($subcontratistas as $row) {
        $subcontratista = $row['Sub_Contratista'];
        $processedSubs[] = $subcontratista;

        $queryStats = "SELECT 
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN P_Completado ELSE 0 END) / 
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS P_Completado,
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN PAC ELSE 0 END) / 
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS PAC
                       FROM {$dbName}_programacion_semanal 
                       WHERE Semana = :semana AND Sub_Contratista = :sub";
        
        $stats = $db->fetch($queryStats, ['semana' => $semana, 'sub' => $subcontratista]);
        
        $pac = $stats['PAC'] ?? 0;
        $pCompletado = $stats['P_Completado'] ?? 0;

        $updateQuery = "UPDATE {$dbName}_cic cic
                        INNER JOIN {$dbName}_subcontratistas sub ON cic.subcontratista = sub.subcontratista 
                        SET cic.P_Completado = :p_comp,
                            cic.PAC = :pac,
                            cic.Semana = :semana,
                            cic.correo_contacto = sub.correo_contacto,
                            cic.NIT = sub.NIT,
                            cic.alcance = sub.alcance,
                            cic.tipo_proveedor = sub.tipo_proveedor 
                        WHERE cic.subcontratista = :sub AND cic.Semana = :semana_filtro";
        
        $db->execute($updateQuery, [
            'p_comp' => $pCompletado,
            'pac' => $pac,
            'semana' => $semana,
            'sub' => $subcontratista,
            'semana_filtro' => $semanaFiltro
        ]);
    }

    // Eliminar registros que ya no están en la programación semanal para esa semana
    $sqlDelete = "DELETE FROM {$dbName}_cic WHERE Semana = :semana";
    $deleteParams = ['semana' => $semana];
    if (!empty($processedSubs)) {
        $placeholders = [];
        foreach ($processedSubs as $i => $sub) {
            $key = "sub_$i";
            $placeholders[] = ":$key";
            $deleteParams[$key] = $sub;
        }
        $sqlDelete .= " AND subcontratista NOT IN (" . implode(',', $placeholders) . ")";
    }
    $db->execute($sqlDelete, $deleteParams);
}

function actualizar_PAC_profesionales($semana, $dbName, $db, $semanaFiltro) {
    $query = "SELECT DISTINCT Responsable_AIA FROM {$dbName}_programacion_semanal 
              WHERE Semana = :semana AND Responsable_AIA != '' 
              AND (Activa = '1' OR Activa = 'NA') AND (PAC = '1' OR PAC = '0')";
    
    $profesionales = $db->fetchAll($query, ['semana' => $semana]);
    $processedProfs = [];

    foreach ($profesionales as $row) {
        $profesional = $row['Responsable_AIA'];
        $processedProfs[] = $profesional;

        $queryStats = "SELECT 
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN P_Completado ELSE 0 END) / 
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS P_Completado,
                        ROUND((SUM(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN PAC ELSE 0 END) / 
                               COUNT(CASE WHEN (Activa = 1 OR Activa = 'NA') THEN 1 END)), 3) AS PAC
                       FROM {$dbName}_programacion_semanal 
                       WHERE Semana = :semana AND Responsable_AIA = :prof";
        
        $stats = $db->fetch($queryStats, ['semana' => $semana, 'prof' => $profesional]);
        
        $pac = $stats['PAC'] ?? 0;
        $pCompletado = $stats['P_Completado'] ?? 0;

        $updateQuery = "UPDATE {$dbName}_cip cip
                        INNER JOIN {$dbName}_profesionales prof ON cip.profesional = prof.nombre 
                        SET cip.P_Completado = :p_comp,
                            cip.PAC = :pac,
                            cip.Semana = :semana,
                            cip.correo_contacto = prof.email 
                        WHERE cip.profesional = :prof AND cip.Semana = :semana_filtro";
        
        $db->execute($updateQuery, [
            'p_comp' => $pCompletado,
            'pac' => $pac,
            'semana' => $semana,
            'prof' => $profesional,
            'semana_filtro' => $semanaFiltro
        ]);
    }

    $sqlDelete = "DELETE FROM {$dbName}_cip WHERE Semana = :semana";
    $deleteParams = ['semana' => $semana];
    if (!empty($processedProfs)) {
        $placeholders = [];
        foreach ($processedProfs as $i => $prof) {
            $key = "prof_$i";
            $placeholders[] = ":$key";
            $deleteParams[$key] = $prof;
        }
        $sqlDelete .= " AND profesional NOT IN (" . implode(',', $placeholders) . ")";
    }
    $db->execute($sqlDelete, $deleteParams);
}

function actualizar_integral_subcontratistas($semana, $dbName, $db) {
    $cicRows = $db->fetchAll("SELECT Id, subcontratista, PAC, Calidad, ADM, GSA, SST FROM {$dbName}_cic WHERE Semana = :semana", ['semana' => $semana]);

    foreach ($cicRows as $cic) {
        $id = $cic['Id'];
        $subcontratista = $cic['subcontratista'];

        $queryAcum = "SELECT 
            (SELECT ROUND(AVG(PAC), 3) FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND PAC != 'NA') AS PAC_Acum,
            (SELECT ROUND(AVG(P_Completado), 3) FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND P_Completado != 'NA') AS P_Completado_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Calidad), 3) END FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND Calidad NOT IN ('NA', 'NR')) AS Calidad_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(GSA), 3) END FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND GSA NOT IN ('NA', 'NR')) AS GSA_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(SST), 3) END FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND SST NOT IN ('NA', 'NR')) AS SST_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(ADM), 3) END FROM {$dbName}_cic WHERE Semana <= :semana AND subcontratista = :sub AND ADM NOT IN ('NA', 'NR')) AS ADM_Acum
            FROM DUAL";

        $acum = $db->fetch($queryAcum, ['semana' => $semana, 'sub' => $subcontratista]);

        $db->execute("UPDATE {$dbName}_cic SET 
                        PAC_Acum = :pac, P_Completado_Acum = :pcomp, Calidad_Acum = :cal, 
                        GSA_Acum = :gsa, SST_Acum = :sst, ADM_Acum = :adm 
                      WHERE Id = :id", [
            'pac' => $acum['PAC_Acum'], 'pcomp' => $acum['P_Completado_Acum'], 'cal' => $acum['Calidad_Acum'],
            'gsa' => $acum['GSA_Acum'], 'sst' => $acum['SST_Acum'], 'adm' => $acum['ADM_Acum'], 'id' => $id
        ]);

        // Cálculo de Calificación Integral (Lógica original conservada)
        $pac = $cic['PAC'];
        $calidad = $cic['Calidad'];
        $adm = $cic['ADM'];
        $gsa = $cic['GSA'];
        $sst = $cic['SST'];

        $cal_integral = calcular_logica_integral($pac, $calidad, $sst, $gsa, $adm);
        
        $pac_acum = $acum['PAC_Acum'];
        $calidad_acum = $acum['Calidad_Acum'];
        $adm_acum = $acum['ADM_Acum'];
        $gsa_acum = $acum['GSA_Acum'];
        $sst_acum = $acum['SST_Acum'];
        
        $cal_integral_acum = calcular_logica_integral($pac_acum, $calidad_acum, $sst_acum, $gsa_acum, $adm_acum);

        $db->execute("UPDATE {$dbName}_cic SET Cal_Integral = ROUND(:cal, 3), Cal_Integral_Acum = ROUND(:cal_acum, 3) WHERE Id = :id", [
            'cal' => $cal_integral, 'cal_acum' => $cal_integral_acum, 'id' => $id
        ]);
    }
}

function calcular_logica_integral($pac, $calidad, $sst, $gsa, $adm) {
    if ($calidad == 'NA' || $calidad == 'NR') {
        if ($sst == 'NA' || $sst == 'NR') {
            if ($gsa == 'NA' || $gsa == 'NR') {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.7 / 7) * 7);
                } else {
                    return $pac * (0.3 + (0.6 / 4) * 3) + $adm * (0.1 + (0.6 / 4) * 1);
                }
            } else {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.5 / 5) * 3) + $gsa * (0.2 + (0.5 / 5) * 2);
                } else {
                    return $pac * (0.3 + (0.4 / 6) * 3) + $gsa * (0.2 + (0.4 / 6) * 2) + $adm * (0.1 + (0.4 / 6) * 1);
                }
            }
        } else {
            if ($gsa == 'NA' || $gsa == 'NR') {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.5 / 5) * 3) + $sst * (0.2 + (0.5 / 5) * 2);
                } else {
                    return $pac * (0.3 + (0.4 / 6) * 3) + $sst * (0.2 + (0.4 / 6) * 2) + $adm * (0.1 + (0.4 / 6) * 1);
                }
            } else {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.3 / 7) * 3) + $sst * (0.2 + (0.3 / 7) * 2) + $gsa * (0.2 + (0.3 / 7) * 2);
                } else {
                    return $pac * (0.3 + (0.2 / 8) * 3) + $sst * (0.2 + (0.2 / 8) * 2) + $gsa * (0.2 + (0.2 / 8) * 2) + $adm * (0.1 + (0.2 / 8) * 1);
                }
            }
        }
    } else {
        if ($sst == 'NA' || $sst == 'NR') {
            if ($gsa == 'NA' || $gsa == 'NR') {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.5 / 5) * 3) + $calidad * (0.2 + (0.5 / 5) * 2);
                } else {
                    return $pac * (0.3 + (0.4 / 6) * 3) + $calidad * (0.2 + (0.4 / 6) * 2) + $adm * (0.1 + (0.4 / 6) * 1);
                }
            } else {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.3 / 7) * 3) + $calidad * (0.2 + (0.3 / 7) * 2) + $gsa * (0.2 + (0.3 / 7) * 2);
                } else {
                    return $pac * (0.3 + (0.2 / 8) * 3) + $calidad * (0.2 + (0.2 / 8) * 2) + $gsa * (0.2 + (0.2 / 8) * 2) + $adm * (0.1 + (0.2 / 8) * 1);
                }
            }
        } else {
            if ($gsa == 'NA' || $gsa == 'NR') {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.3 / 7) * 3) + $calidad * (0.2 + (0.3 / 7) * 2) + $sst * (0.2 + (0.3 / 7) * 2);
                } else {
                    return $pac * (0.3 + (0.2 / 8) * 3) + $calidad * (0.2 + (0.2 / 8) * 2) + $sst * (0.2 + (0.2 / 8) * 2) + $adm * (0.1 + (0.2 / 8) * 1);
                }
            } else {
                if ($adm == 'NA' || $adm == 'NR') {
                    return $pac * (0.3 + (0.1 / 9) * 3) + $calidad * (0.2 + (0.1 / 9) * 2) + $sst * (0.2 + (0.1 / 9) * 2) + $gsa * (0.2 + (0.1 / 9) * 2);
                } else {
                    return $pac * (0.3 + (0.0 / 10) * 3) + $calidad * (0.2 + (0.0 / 10) * 2) + $sst * (0.2 + (0.0 / 10) * 2) + $gsa * (0.2 + (0.0 / 10) * 2) + $adm * (0.1 + (0.0 / 10) * 1);
                }
            }
        }
    }
}

function actualizar_integral_profesionales($semana, $dbName, $db) {
    $cipRows = $db->fetchAll("SELECT profesional, PAC FROM {$dbName}_cip WHERE Semana = :semana", ['semana' => $semana]);

    foreach ($cipRows as $cip) {
        $profesional = $cip['profesional'];

        $queryStats = "SELECT 
            (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END 
             FROM {$dbName}_programacion_semanal WHERE Semana = :semana AND Responsable_AIA = :prof AND Activa = 1 AND Critica = 1 AND Atrasada = 0) AS Act_Criticas_Cumplidas,
            (SELECT CASE WHEN COUNT(Critica) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Critica), 3) ELSE 'NA' END 
             FROM {$dbName}_programacion_semanal WHERE Semana = :semana AND Responsable_AIA = :prof AND (Activa = 1 OR Activa = 'NA') AND Critica = 0 AND Atrasada = 0) AS Act_No_Criticas_Cumplidas,
            (SELECT CASE WHEN COUNT(Atrasada) > 0 THEN ROUND(SUM(CASE WHEN PAC=1 THEN 1 ELSE 0 END)/COUNT(Atrasada), 3) ELSE 'NA' END 
             FROM {$dbName}_programacion_semanal WHERE Semana = :semana AND Responsable_AIA = :prof AND Activa = 1 AND Atrasada = 1) AS Act_Atrasadas_Cumplidas
            FROM DUAL";

        $stats = $db->fetch($queryStats, ['semana' => $semana, 'prof' => $profesional]);

        $db->execute("UPDATE {$dbName}_cip SET 
                        Act_Criticas_Cumplidas = :crit, Act_No_Criticas_Cumplidas = :nocrit, Act_Atrasadas_Cumplidas = :atr 
                      WHERE profesional = :prof AND Semana = :semana", [
            'crit' => $stats['Act_Criticas_Cumplidas'], 'nocrit' => $stats['Act_No_Criticas_Cumplidas'],
            'atr' => $stats['Act_Atrasadas_Cumplidas'], 'prof' => $profesional, 'semana' => $semana
        ]);

        $queryAcum = "SELECT 
            (SELECT ROUND(AVG(PAC), 3) FROM {$dbName}_cip WHERE Semana <= :semana AND profesional = :prof AND PAC != 'NA') AS PAC_Acum,
            (SELECT ROUND(AVG(P_Completado), 3) FROM {$dbName}_cip WHERE Semana <= :semana AND profesional = :prof AND P_Completado != 'NA') AS P_Completado_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Criticas_Cumplidas), 3) END FROM {$dbName}_cip WHERE Semana <= :semana AND profesional = :prof AND Act_Criticas_Cumplidas != 'NA') AS Act_Criticas_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_No_Criticas_Cumplidas), 3) END FROM {$dbName}_cip WHERE Semana <= :semana AND profesional = :prof AND Act_No_Criticas_Cumplidas != 'NA') AS Act_No_Criticas_Acum,
            (SELECT CASE WHEN COUNT(*) = 0 THEN 'NA' ELSE ROUND(AVG(Act_Atrasadas_Cumplidas), 3) END FROM {$dbName}_cip WHERE Semana <= :semana AND profesional = :prof AND Act_Atrasadas_Cumplidas != 'NA') AS Act_Atrasadas_Acum
            FROM DUAL";

        $acum = $db->fetch($queryAcum, ['semana' => $semana, 'prof' => $profesional]);

        $db->execute("UPDATE {$dbName}_cip SET 
                        PAC_Acum = :pac, P_Completado_Acum = :pcomp, 
                        Act_Criticas_Cumplidas_Acum = :crit, Act_No_Criticas_Cumplidas_Acum = :nocrit, Act_Atrasadas_Cumplidas_Acum = :atr 
                      WHERE profesional = :prof AND Semana = :semana", [
            'pac' => $acum['PAC_Acum'], 'pcomp' => $acum['P_Completado_Acum'], 'crit' => $acum['Act_Criticas_Acum'],
            'nocrit' => $acum['Act_No_Criticas_Acum'], 'atr' => $acum['Act_Atrasadas_Acum'], 'prof' => $profesional, 'semana' => $semana
        ]);

        // Cálculo de PAC Consolidado
        $pac = $cip['PAC'];
        $crit = $stats['Act_Criticas_Cumplidas'];
        $nocrit = $stats['Act_No_Criticas_Cumplidas'];
        $atr = $stats['Act_Atrasadas_Cumplidas'];
        
        $pac_cons = calcular_pac_consolidado($pac, $crit, $nocrit, $atr);

        $pac_acum = $acum['PAC_Acum'];
        $crit_acum = $acum['Act_Criticas_Acum'];
        $nocrit_acum = $acum['Act_No_Criticas_Acum'];
        $atr_acum = $acum['Act_Atrasadas_Acum'];

        $pac_cons_acum = calcular_pac_consolidado($pac_acum, $crit_acum, $nocrit_acum, $atr_acum);

        $db->execute("UPDATE {$dbName}_cip SET PAC_Consolidado = :cons, PAC_Consolidado_Acum = :cons_acum 
                      WHERE profesional = :prof AND Semana = :semana", [
            'cons' => $pac_cons, 'cons_acum' => $pac_cons_acum, 'prof' => $profesional, 'semana' => $semana
        ]);
    }
}

function calcular_pac_consolidado($pac, $crit, $nocrit, $atr) {
    if ($crit != 'NA' && $nocrit != 'NA' && $atr != 'NA') return round($pac * ($crit * 0.4 + $nocrit * 0.2 + $atr * 0.4), 3);
    if ($crit != 'NA' && $nocrit != 'NA' && $atr == 'NA') return round($pac * ($crit * 0.6667 + $nocrit * 0.3333), 3);
    if ($crit != 'NA' && $nocrit == 'NA' && $atr != 'NA') return round($pac * ($crit * 0.5 + $atr * 0.5), 3);
    if ($crit == 'NA' && $nocrit != 'NA' && $atr != 'NA') return round($pac * ($nocrit * 0.3333 + $atr * 0.6667), 3);
    if ($crit != 'NA' && $nocrit == 'NA' && $atr == 'NA') return round($pac * $crit, 3);
    if ($crit == 'NA' && $nocrit != 'NA' && $atr == 'NA') return round($pac * $nocrit, 3);
    if ($crit == 'NA' && $nocrit == 'NA' && $atr != 'NA') return round($pac * $atr, 3);
    return 0;
}