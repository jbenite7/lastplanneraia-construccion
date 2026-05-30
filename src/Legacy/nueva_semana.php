<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

/** @var Database $dbInstance */
if (!isset($dbInstance)) {
    require_once __DIR__ . "/conexion.php";
    $dbInstance = Database::getInstance();
}

require_once __DIR__ . '/_pdc_functions.php';

$db = $_GET['db'] ?? $_POST['db'] ?? '';
$opcion = $_POST["opcion"] ?? "nueva_sem";
$f_inicio_sem_raw = $_POST["f_inicio_sem"] ?? '';
$f_inicio_sem = date("Y-m-d", strtotime($f_inicio_sem_raw));
$pdcActivo = $_SESSION['pdcActivo'] ?? 0;
$rolCanon = $_SESSION['permiso_canonico'] ?? '';
$esAdmin = ($rolCanon === 'A');

require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
rbac_guard_require_permission('lps.semana.crear');

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

try {
    $stmt = $dbInstance->query("SELECT COUNT(*) AS conteo FROM {$db}_semanas_activas");
    $data = $stmt->fetch();
    $conteo = (int) ($data["conteo"] ?? 0);

    $semanalConfirmada = 0;
    if ($conteo > 0) {
        $stmtVerif = $dbInstance->query("SELECT Semanal_Confirmada FROM {$db}_semanas_activas WHERE Semana = ?", [$conteo]);
        $dataVerif = $stmtVerif->fetch();
        $semanalConfirmada = (int) ($dataVerif["Semanal_Confirmada"] ?? 0);
    }

    if ($conteo > 0 && $semanalConfirmada == 0 && !$esAdmin) {
        $respuesta = [$conteo, 0, 0, $semanalConfirmada];
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    } else {
        $semana_crear = $conteo + 1;
        $f_fin_sem = date("Y-m-d", strtotime($f_inicio_sem . "+ 6 days"));
        $fCreacionSemana = date("Y-m-d");

        $sqlInsertSemana = "INSERT INTO {$db}_semanas_activas (Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (NULL, ?, ?, ?, ?)";
        $dbInstance->query($sqlInsertSemana, [$semana_crear, $f_inicio_sem, $f_fin_sem, $fCreacionSemana]);

        if ($conteo == 0) {
            // Validar que el Programa Maestro tenga actividades antes de copiar
            $stmtPrograma = $dbInstance->query("SELECT COUNT(*) AS c FROM {$db}_programa");
            if ((int) $stmtPrograma->fetch()['c'] === 0) {
                // Rollback: eliminar la semana recién creada
                $dbInstance->query("DELETE FROM {$db}_semanas_activas WHERE Semana = ?", [$semana_crear]);
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "No hay actividades en el Programa Maestro. Cargue el programa antes de crear la primera semana."]);
                return;
            }

            $sqlCopy = "INSERT INTO {$db}_programa_consolidado(Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana) 
                        SELECT NULL, ?, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 0, IFNULL(Ejecutado, 0), Estado, IFNULL(Semanas_Inicio, 0), IFNULL(Estado_Restricciones, '0'), IFNULL(D_y_E, '0'), IFNULL(Materiales, '0'), IFNULL(MdeO, '0'), IFNULL(Equipos, '0'), IFNULL(Predecesora, '0'), IFNULL(Pdto_Cons, '0'), IFNULL(Modelo, '0'), Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, IFNULL(Ejecutado, 0) FROM {$db}_programa";
            $dbInstance->query($sqlCopy, [$semana_crear]);
            $normalizationService = new \App\Services\ProgramaConsolidadoNormalizationService($dbInstance);
            $normalizationService->normalizeChapters($db, $semana_crear);
            $sqlReset = "UPDATE {$db}_programa_consolidado SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, D_y_E='0', Materiales='0', MdeO='0', Equipos='0', Predecesora='0', Pdto_Cons='0', Modelo='0', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE Semana = ? AND Titulo=1";
            $dbInstance->query($sqlReset, [$semana_crear]);

        } else {
            $stmtMax = $dbInstance->query("SELECT MAX(Semana) AS max_semana FROM {$db}_programa_consolidado");
            $dataMax = $stmtMax->fetch();
            $maxSemanaProgramaConsolidado = (int) ($dataMax["max_semana"] ?? $conteo);

            // Limpiar datos huérfanos si hay semanas superiores a la esperada
            if ($maxSemanaProgramaConsolidado > $semana_crear) {
                $dbInstance->query("DELETE FROM {$db}_programa_consolidado WHERE Semana > ?", [$semana_crear]);
                error_log("[nueva_semana] Limpieza de datos huérfanos: eliminadas semanas > $semana_crear en programa_consolidado");
            }

            if ($maxSemanaProgramaConsolidado == $semana_crear) {
                $campos = "Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Activa, Ejecutado_Siguiente_Semana, codigo_actividad, medir_productividad, cantidad_ppto, unidad";
                $setClause = "";
                foreach (explode(',', $campos) as $campoRaw) {
                    $campo = trim($campoRaw);
                    if (in_array($campo, ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'])) {
                        $setClause .= "dest.$campo = IFNULL(src.$campo, 0), ";
                    } else {
                        $setClause .= "dest.$campo = src.$campo, ";
                    }
                }
                $setClause = rtrim($setClause, ', ');

                $sqlBigUpdate = "UPDATE {$db}_programa_consolidado AS dest
                                 INNER JOIN (SELECT * FROM {$db}_programa_consolidado WHERE Semana = ? AND Titulo = 0) AS src
                                 ON REPLACE(REPLACE(dest.programaAnteriorAsociar, '<b>', ''), '</b>', '') = REPLACE(REPLACE(src.Actividad, '<b>', ''), '</b>', '')
                                 SET $setClause
                                 WHERE dest.Semana = ?";
                $dbInstance->query($sqlBigUpdate, [$conteo, $semana_crear]);

                $sqlReprog = "UPDATE {$db}_semanas_activas SET reprogramacion=1, diferenciaEstructuraCron=(SELECT COUNT(*) FROM {$db}_programa_consolidado WHERE Semana=? AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar IS NOT NULL)) WHERE Semana=?";
                $dbInstance->query($sqlReprog, [$semana_crear, $semana_crear]);

            } else {
                $cols = "Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana";

                // Generate safe select columns dealing with potential NULLs for NOT NULL columns
                $arrCols = explode(', ', $cols);
                $arrSelect = [];
                foreach ($arrCols as $c) {
                    $c = trim($c);
                    if (in_array($c, ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'])) {
                        $arrSelect[] = "IFNULL($c, 0)";
                    } else {
                        $arrSelect[] = $c;
                    }
                }
                $selectCols = implode(', ', $arrSelect);

                $sqlInsertCopy = "INSERT INTO {$db}_programa_consolidado(Consecutivo, Semana, $cols)
                                   SELECT NULL, ?, $selectCols FROM {$db}_programa_consolidado WHERE Semana = ?";
                $dbInstance->query($sqlInsertCopy, [$semana_crear, $conteo]);
            }

            $normalizationService = new \App\Services\ProgramaConsolidadoNormalizationService($dbInstance);
            $normalizationService->normalizeChapters($db, $semana_crear);
            $dbInstance->query("UPDATE {$db}_programa_consolidado SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, D_y_E='0', Materiales='0', MdeO='0', Equipos='0', Predecesora='0', Pdto_Cons='0', Modelo='0', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE Semana = ? AND Titulo=1", [$semana_crear]);
            $dbInstance->query("UPDATE {$db}_programa_consolidado SET Ejecutado = 0, Estado_Restricciones = '0', D_y_E = '0', Materiales = '0', MdeO = '0', Equipos = '0', Predecesora = '0', Pdto_Cons = '0', Modelo = '0' WHERE Ejecutado IS NULL AND Semana = ? AND Titulo=0", [$semana_crear]);



            $carryoverService = new \App\Services\WeeklyRealProgressCarryoverService($dbInstance);
            $carryoverService->syncWeek($db, $conteo, $semana_crear);

            if ($pdcActivo == 1) {
                $sqlCopyPDC = "INSERT INTO `{$db}_pdc` (semana, titulo, tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, fechaIngresoLicify, diasIngresoLicify, fechaRealIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaRealEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaRealReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaRealCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaRealLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaRealFabricacion, fechaInsumosObra, diasInsumosObra, fechaRealInsumosObra, fechaInicio, fechaInicioProyectada, fechaRealInicio, idProveedorAdjudicado, numeroContrato, fechaVencimientoPolizas, valorPresupuesto, valorPrimeraNegociacion, valorAdjudicado, valorAnticipo, valorReclamado, valorDevoluciones, observacionesContrato)
                                SELECT ?, titulo, tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, fechaIngresoLicify, diasIngresoLicify, fechaRealIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaRealEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaRealReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaRealCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaRealLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaRealFabricacion, fechaInsumosObra, diasInsumosObra, fechaRealInsumosObra, fechaInicio, fechaInicioProyectada, fechaRealInicio, idProveedorAdjudicado, numeroContrato, fechaVencimientoPolizas, valorPresupuesto, valorPrimeraNegociacion, valorAdjudicado, valorAnticipo, valorReclamado, valorDevoluciones, observacionesContrato 
                                FROM `{$db}_pdc` WHERE `semana` = ?";
                $dbInstance->query($sqlCopyPDC, [$semana_crear, $conteo]);

                pdc_insertarPaquetes($dbInstance, $db, $semana_crear, '', '', '');
                pdc_crearSubcontratosDuplicados($dbInstance, $db, $semana_crear);
                pdc_generarEstadoProceso($dbInstance, $db, $semana_crear);
            }
        }

        $conteoPDC = $pdcActivo;
        $semana = $semana_crear;
        $ejecucionActualizada = 1;
        $dbName = $db;
        include __DIR__ . "/modificar_sem_estado.php";
    }
} catch (Exception $e) {
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}
