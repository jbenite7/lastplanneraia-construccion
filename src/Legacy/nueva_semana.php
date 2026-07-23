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

use App\Services\RestrictionConfigResolver;

$db = $_GET['db'] ?? $_POST['db'] ?? '';
$opcion = $_POST["opcion"] ?? "nueva_sem";
$f_inicio_sem_raw = $_POST["f_inicio_sem"] ?? '';
$f_inicio_sem = date("Y-m-d", strtotime($f_inicio_sem_raw));
$pdcActivo = $_SESSION['pdcActivo'] ?? 0;
$rolCanon = $_SESSION['permiso_canonico'] ?? '';
$esAdmin = ($rolCanon === 'A');

require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
rbac_guard_require_permission('lps.semana.crear');
legacy_require_csrf('lps_week_admin');

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

// Resolve table names via TableResolver
$tPrograma = TableResolver::resolveByPrefix($db, 'programa');
$tProgConsolidado = TableResolver::resolveByPrefix($db, 'programa_consolidado');
$tSemanasActivas = TableResolver::resolveByPrefix($db, 'semanas_activas');
$tPdc = TableResolver::resolveByPrefix($db, 'pdc');

// Set project context for queryWithProject auto-injection
$projectId = TableResolver::getProjectIdByPrefix($db);
if ($projectId) {
    $dbInstance->setProjectContext($projectId);
} else {
    throw new RuntimeException('Proyecto no encontrado.');
}

$restrictionConfig = RestrictionConfigResolver::resolve($db);
$isPreConstruccion = $restrictionConfig['isPreConstruccion'];

try {
    $stmt = $dbInstance->queryWithProject("SELECT COUNT(*) AS conteo FROM {$tSemanasActivas} WHERE project_id = ?", [$projectId], $projectId);
    $data = $stmt->fetch();
    $conteo = (int) ($data["conteo"] ?? 0);

    $semanalConfirmada = 0;
    if ($conteo > 0) {
        $stmtVerif = $dbInstance->queryWithProject("SELECT Semanal_Confirmada FROM {$tSemanasActivas} WHERE project_id = ? AND Semana = ?", [$projectId, $conteo], $projectId);
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
        $nextSemanaId = (int) $dbInstance
            ->queryWithProject("SELECT COALESCE(MAX(Id), 0) + 1 FROM {$tSemanasActivas} WHERE project_id = ?", [$projectId], $projectId)
            ->fetchColumn();

        $sqlInsertSemana = "INSERT INTO {$tSemanasActivas} (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (?, ?, ?, ?, ?, ?)";
        $dbInstance->query($sqlInsertSemana, [$projectId, $nextSemanaId, $semana_crear, $f_inicio_sem, $f_fin_sem, $fCreacionSemana]);

        if ($conteo == 0) {
            // Validar que el Programa Maestro tenga actividades antes de copiar
            $stmtPrograma = $dbInstance->queryWithProject("SELECT COUNT(*) AS c FROM {$tPrograma} WHERE project_id = ?", [$projectId], $projectId);
            if ((int) $stmtPrograma->fetch()['c'] === 0) {
                // Rollback: eliminar la semana recién creada
                $dbInstance->queryWithProject("DELETE FROM {$tSemanasActivas} WHERE project_id = ? AND Semana = ?", [$projectId, $semana_crear], $projectId);
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "No hay actividades en el Programa Maestro. Cargue el programa antes de crear la primera semana."]);
                return;
            }

            $baseConsolidadoId = (int) $dbInstance
                ->queryWithProject("SELECT COALESCE(MAX(row_id), MAX(Consecutivo), 0) FROM {$tProgConsolidado} WHERE project_id = ?", [$projectId], $projectId)
                ->fetchColumn();
            if ($isPreConstruccion) {
                $sqlCopy = "INSERT INTO {$tProgConsolidado}(project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)
                            SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ?, unique_id, unique_id, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 0, IFNULL(Ejecutado, 0), Estado, IFNULL(Semanas_Inicio, 0), IFNULL(Estado_Restricciones, '0'), IFNULL(restriccion_pc_1, '0%'), IFNULL(restriccion_pc_2, '0%'), IFNULL(restriccion_pc_3, '0%'), IFNULL(restriccion_pc_4, '0%'), Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, IFNULL(Ejecutado, 0) FROM {$tPrograma} WHERE project_id = ?";
            } else {
                $sqlCopy = "INSERT INTO {$tProgConsolidado}(project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)
                            SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY unique_id, Id), ?, unique_id, unique_id, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, 0, IFNULL(Ejecutado, 0), Estado, IFNULL(Semanas_Inicio, 0), IFNULL(Estado_Restricciones, '0'), IFNULL(D_y_E, '0'), IFNULL(Materiales, '0'), IFNULL(MdeO, '0'), IFNULL(Equipos, '0'), IFNULL(Predecesora, '0'), IFNULL(Pdto_Cons, '0'), IFNULL(Modelo, '0'), Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, IFNULL(Ejecutado, 0) FROM {$tPrograma} WHERE project_id = ?";
            }
            $dbInstance->query($sqlCopy, [$projectId, $baseConsolidadoId, $baseConsolidadoId, $semana_crear, $projectId]);
            $normalizationService = new \App\Services\ProgramaConsolidadoNormalizationService($dbInstance);
            $normalizationService->normalizeChapters($db, $semana_crear);
            if ($isPreConstruccion) {
                $sqlReset = "UPDATE {$tProgConsolidado} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, restriccion_pc_1='0%', restriccion_pc_2='0%', restriccion_pc_3='0%', restriccion_pc_4='0%', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1";
            } else {
                $sqlReset = "UPDATE {$tProgConsolidado} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, D_y_E='0', Materiales='0', MdeO='0', Equipos='0', Predecesora='0', Pdto_Cons='0', Modelo='0', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1";
            }
            $dbInstance->queryWithProject($sqlReset, [$projectId, $semana_crear], $projectId);

        } else {
            $stmtMax = $dbInstance->queryWithProject("SELECT MAX(Semana) AS max_semana FROM {$tProgConsolidado} WHERE project_id = ?", [$projectId], $projectId);
            $dataMax = $stmtMax->fetch();
            $maxSemanaProgramaConsolidado = (int) ($dataMax["max_semana"] ?? $conteo);

            // Limpiar datos huérfanos si hay semanas superiores a la esperada
            if ($maxSemanaProgramaConsolidado > $semana_crear) {
                $dbInstance->queryWithProject("DELETE FROM {$tProgConsolidado} WHERE project_id = ? AND Semana > ?", [$projectId, $semana_crear], $projectId);
                error_log("[nueva_semana] Limpieza de datos huérfanos: eliminadas semanas > $semana_crear en programa_consolidado");
            }

            if ($maxSemanaProgramaConsolidado == $semana_crear) {
                if ($isPreConstruccion) {
                    $campos = "Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Activa, Ejecutado_Siguiente_Semana, codigo_actividad, medir_productividad, cantidad_ppto, unidad";
                } else {
                    $campos = "Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Activa, Ejecutado_Siguiente_Semana, codigo_actividad, medir_productividad, cantidad_ppto, unidad";
                }
                $ifnullCheck = $isPreConstruccion
                    ? ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4', 'Ejecutado', 'Estado_Restricciones']
                    : ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'];
                $setClause = "";
                foreach (explode(',', $campos) as $campoRaw) {
                    $campo = trim($campoRaw);
                    if (in_array($campo, $ifnullCheck)) {
                        $setClause .= "dest.$campo = IFNULL(src.$campo, 0), ";
                    } else {
                        $setClause .= "dest.$campo = src.$campo, ";
                    }
                }
                $setClause = rtrim($setClause, ', ');

                $sqlBigUpdate = "UPDATE {$tProgConsolidado} AS dest
                                 INNER JOIN (SELECT * FROM {$tProgConsolidado} WHERE project_id = ? AND Semana = ? AND Titulo = 0) AS src
                                 ON src.project_id = dest.project_id
                                 AND REPLACE(REPLACE(dest.programaAnteriorAsociar, '<b>', ''), '</b>', '') = REPLACE(REPLACE(src.Actividad, '<b>', ''), '</b>', '')
                                 SET $setClause
                                 WHERE dest.project_id = ? AND dest.Semana = ?";
                $dbInstance->queryWithProject($sqlBigUpdate, [$projectId, $conteo, $projectId, $semana_crear], $projectId);

                $sqlReprog = "UPDATE {$tSemanasActivas} SET reprogramacion=1, diferenciaEstructuraCron=(SELECT COUNT(*) FROM {$tProgConsolidado} WHERE project_id = ? AND Semana=? AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar IS NOT NULL)) WHERE project_id = ? AND Semana=?";
                $dbInstance->queryWithProject($sqlReprog, [$projectId, $semana_crear, $projectId, $semana_crear], $projectId);

            } else {
                if ($isPreConstruccion) {
                    $cols = "unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana, programaAnteriorAsociar";
                } else {
                    $cols = "unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana, programaAnteriorAsociar";
                }

                // Generate safe select columns dealing with potential NULLs for NOT NULL columns
                $arrCols = explode(', ', $cols);
                $arrSelect = [];
                $ifnullCheckCols = $isPreConstruccion
                    ? ['restriccion_pc_1', 'restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4', 'Ejecutado', 'Estado_Restricciones']
                    : ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo', 'Ejecutado', 'Estado_Restricciones'];
                foreach ($arrCols as $c) {
                    $c = trim($c);
                    if (in_array($c, $ifnullCheckCols)) {
                        $arrSelect[] = "IFNULL($c, 0)";
                    } else {
                        $arrSelect[] = $c;
                    }
                }
                $selectCols = implode(', ', $arrSelect);

                $baseConsolidadoId = (int) $dbInstance
                    ->queryWithProject("SELECT COALESCE(MAX(row_id), MAX(Consecutivo), 0) FROM {$tProgConsolidado} WHERE project_id = ?", [$projectId], $projectId)
                    ->fetchColumn();
                $sqlInsertCopy = "INSERT INTO {$tProgConsolidado}(project_id, row_id, Consecutivo, Semana, $cols)
                                   SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY COALESCE(row_id, Consecutivo), unique_id, Id), ? + ROW_NUMBER() OVER (ORDER BY COALESCE(row_id, Consecutivo), unique_id, Id), ?, $selectCols FROM {$tProgConsolidado} WHERE project_id = ? AND Semana = ?";
                $dbInstance->query($sqlInsertCopy, [$projectId, $baseConsolidadoId, $baseConsolidadoId, $semana_crear, $projectId, $conteo]);
            }

            $normalizationService = new \App\Services\ProgramaConsolidadoNormalizationService($dbInstance);
            $normalizationService->normalizeChapters($db, $semana_crear);
            if ($isPreConstruccion) {
                $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, restriccion_pc_1='0%', restriccion_pc_2='0%', restriccion_pc_3='0%', restriccion_pc_4='0%', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1", [$projectId, $semana_crear], $projectId);
                $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Ejecutado = 0, Estado_Restricciones = '0', restriccion_pc_1 = '0%', restriccion_pc_2 = '0%', restriccion_pc_3 = '0%', restriccion_pc_4 = '0%' WHERE project_id = ? AND Ejecutado IS NULL AND Semana = ? AND Titulo=0", [$projectId, $semana_crear], $projectId);
            } else {
                $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, D_y_E='0', Materiales='0', MdeO='0', Equipos='0', Predecesora='0', Pdto_Cons='0', Modelo='0', Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0 WHERE project_id = ? AND Semana = ? AND Titulo=1", [$projectId, $semana_crear], $projectId);
                $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Ejecutado = 0, Estado_Restricciones = '0', D_y_E = '0', Materiales = '0', MdeO = '0', Equipos = '0', Predecesora = '0', Pdto_Cons = '0', Modelo = '0' WHERE project_id = ? AND Ejecutado IS NULL AND Semana = ? AND Titulo=0", [$projectId, $semana_crear], $projectId);
            }



            $carryoverService = new \App\Services\WeeklyRealProgressCarryoverService($dbInstance);
            $carryoverService->syncWeek($db, $conteo, $semana_crear);

            if ($pdcActivo == 1) {
                $basePdcId = (int) $dbInstance
                    ->queryWithProject("SELECT COALESCE(MAX(pdc_row_id), MAX(consecutivo), 0) FROM {$tPdc} WHERE project_id = ?", [$projectId], $projectId)
                    ->fetchColumn();
                $sqlCopyPDC = "INSERT INTO {$tPdc} (project_id, pdc_row_id, consecutivo, semana, titulo, tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos, fechaRealEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaRealReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaRealCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaRealLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaRealFabricacion, fechaInsumosObra, diasInsumosObra, fechaRealInsumosObra, fechaInicio, fechaInicioProyectada, fechaRealInicio, idProveedorAdjudicado, numeroContrato, fechaVencimientoPolizas, valorPresupuesto, valorPrimeraNegociacion, valorAdjudicado, valorAnticipo, valorReclamado, valorDevoluciones, observacionesContrato)
                                SELECT ?, ? + ROW_NUMBER() OVER (ORDER BY COALESCE(pdc_row_id, consecutivo)), ? + ROW_NUMBER() OVER (ORDER BY COALESCE(pdc_row_id, consecutivo)), ?, titulo, tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos, subcontratoPaquete, estado, fechaElaboracionPliegos, diasElaboracionPliegos, fechaRealElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos, fechaRealEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaRealReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaRealCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaRealLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaRealFabricacion, fechaInsumosObra, diasInsumosObra, fechaRealInsumosObra, fechaInicio, fechaInicioProyectada, fechaRealInicio, idProveedorAdjudicado, numeroContrato, fechaVencimientoPolizas, valorPresupuesto, valorPrimeraNegociacion, valorAdjudicado, valorAnticipo, valorReclamado, valorDevoluciones, observacionesContrato
                                FROM {$tPdc} WHERE project_id = ? AND semana = ?";
                $dbInstance->query($sqlCopyPDC, [$projectId, $basePdcId, $basePdcId, $semana_crear, $projectId, $conteo]);

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
