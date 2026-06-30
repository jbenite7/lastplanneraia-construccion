<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(PROJECT_ROOT . "/src/Legacy/conexion.php");
require_once __DIR__ . "/productividad_temporal.php";
use App\Services\RestrictionConfigResolver;

/** @var Database $db */
$db = Database::getInstance();

$dbName = $dbPrefix ?? $_POST['db'] ?? $_GET['db'] ?? '';
$semana = (int) ($semana ?? $_POST['semana'] ?? $_GET['semana'] ?? 0);

disable_productivity_measurement_temporarily($db);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

// Resolve table names via TableResolver
$tSemanasActivas = TableResolver::resolveByPrefix($dbName, 'semanas_activas');
$tProgSemanal = TableResolver::resolveByPrefix($dbName, 'programacion_semanal');
$tProgConsolidado = TableResolver::resolveByPrefix($dbName, 'programa_consolidado');

// Set project context for queryWithProject auto-injection
$projectId = TableResolver::getProjectIdByPrefix($dbName);
if ($projectId) {
    $db->setProjectContext($projectId);
}

try {
    // Resolve restriction config once based on project Area
    $restrictionConfig = RestrictionConfigResolver::resolve($dbName);
    $isPreConstruccion = $restrictionConfig['isPreConstruccion'];

    // 1. Obtener fechas de la semana activa
    $sqlSemana = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSemanasActivas} WHERE Semana = ?";
    $stmtSemana = $db->queryWithProject($sqlSemana, [$semana]);
    $dataSemana = $stmtSemana->fetch();

    if (!$dataSemana) {
        throw new Exception("Semana activa no encontrada.");
    }

    $restrictionRatioSql = function (string $column): string {
        $text = "TRIM(COALESCE({$column}, ''))";
        $compact = "REPLACE({$text}, ' ', '')";
        $numeric = "CAST(REPLACE(REPLACE({$compact}, '%', ''), ',', '.') AS DECIMAL(10,5))";

        return "(CASE WHEN LOCATE('%', {$compact}) > 0 THEN {$numeric} / 100 WHEN {$numeric} > 1 AND {$numeric} <= 10000 THEN {$numeric} / 100 ELSE {$numeric} END)";
    };
    $restrictionAtLeastSql = function (string $column, float $minimumRatio) use ($restrictionRatioSql): string {
        $text = "TRIM(COALESCE({$column}, ''))";
        $normalized = $restrictionRatioSql($column);
        $threshold = number_format($minimumRatio, 5, '.', '');

        return "(UPPER({$text}) IN ('N/A', 'NO APLICA') OR {$normalized} >= {$threshold})";
    };
    $buildHardEligibilitySql = function (string $prefix = '') use ($restrictionAtLeastSql, $restrictionConfig): string {
        $conditions = [];
        foreach ($restrictionConfig['hardRestrictions'] as $col) {
            $threshold = $restrictionConfig['thresholds'][$col] ?? 1.0;
            $conditions[] = $restrictionAtLeastSql($prefix . $col, $threshold);
        }

        return '(' . implode(' AND ', $conditions) . ')';
    };
    $parseRestrictionRatio = function ($value): ?float {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || strtolower($raw) === 'null') {
            return null;
        }

        $hasPercent = strpos($raw, '%') !== false;
        $normalized = str_replace('%', '', preg_replace('/\s+/', '', $raw));
        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            $normalized = $commaPos > $dotPos
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $ratio = (float) $normalized;
        if ($hasPercent) {
            $ratio /= 100;
        }
        while ($ratio > 1 && $ratio <= 10000) {
            $ratio /= 100;
        }

        return max(0.0, min(1.0, $ratio));
    };
    $buildRestrictionAlertParts = function (array $row, array $rules) use ($parseRestrictionRatio): array {
        $parts = [];
        foreach ($rules as $col => $rule) {
            $raw = trim((string) ($row[$col] ?? ''));
            $upper = strtoupper($raw);
            if ($upper === 'N/A' || $upper === 'NO APLICA') {
                continue;
            }

            $ratio = $parseRestrictionRatio($row[$col] ?? null);
            if ($ratio !== null && ($ratio + 0.0001) >= (float) $rule['threshold']) {
                continue;
            }

            $parts[] = $rule['label'] . ' (' . round(($ratio ?? 0.0) * 100) . '%)';
        }

        return $parts;
    };
    $hardEligibilitySql = $buildHardEligibilitySql();
    $hardEligibilitySqlPc = $buildHardEligibilitySql('pc.');

    // 2. Identificar actividades ya programadas para evitar duplicados
    $stmtExistentes = $db->queryWithProject("SELECT DISTINCT(Consecutivo_En_Programa) FROM {$tProgSemanal} WHERE Semana = ?", [$semana]);
    $existentes = $stmtExistentes->fetchAll(PDO::FETCH_COLUMN);

    $whereExistentes = "";
    $paramsInsert = [$semana, $semana];
    if (!empty($existentes)) {
        $placeholders = implode(',', array_fill(0, count($existentes), '?'));
        $whereExistentes = "AND Consecutivo_en_Programa NOT IN ($placeholders)";
        $paramsInsert = array_merge($paramsInsert, $existentes);
    }

    // 3. Insertar nuevas actividades desde el consolidado (Con Split)
    $sqlSelectNuevas = "SELECT
        {$semana}, Consecutivo_en_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
        Sub_Contratista, Responsable_AIA, 'AIA', Ejecutado, 0,
        Ruta_Critica,
        CASE WHEN (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END,
        '1', COALESCE(NULLIF(TRIM(unidad), ''), '%'), cantidad_ppto, codigo_actividad
    FROM {$tProgConsolidado}
    WHERE Semana = ? AND Titulo = 0
      AND (COALESCE(Ejecutado, 0) > 0.001 OR {$hardEligibilitySql})
      AND (
Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
		OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
      )
      $whereExistentes";

    // Remover el primer param de los inserts porque ahora lo inyectamos directamente arriba
    array_shift($paramsInsert);

    $stmtNuevas = $db->queryWithProject($sqlSelectNuevas, $paramsInsert);
    $nuevasFilas = $stmtNuevas->fetchAll(PDO::FETCH_NUM);

    if (!empty($nuevasFilas)) {
        $queryInsertSingle = "INSERT INTO {$tProgSemanal} (
            Semana, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
            Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
            Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        foreach ($nuevasFilas as $f) {
            $subsRaw = $f[6] ?? '';
            $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
            if (empty($subs)) {
                $subs = [''];
            }
            foreach ($subs as $sub) {
                $f[6] = $sub;
                list($insSql, $insParams) = Database::getInstance()->insertProjectId($queryInsertSingle, $projectId ?? 0, $f);
                $db->queryWithProject($insSql, $insParams);
            }
        }
    }

    // 4. Actualizar detalles y compromisos de las actividades programadas
    $stmtSemanal = $db->queryWithProject("SELECT Consecutivo, Consecutivo_En_Programa, Ejecutado, Compromiso, Activa, Sub_Contratista FROM {$tProgSemanal} WHERE Semana = ? AND Activa != 'NA'", [$semana]);
    $actividadesSemanales = $stmtSemanal->fetchAll();

    foreach ($actividadesSemanales as $item) {
        $consecutivo_pk = $item["Consecutivo"];
        $consecutivo_pg = $item["Consecutivo_En_Programa"];
        $subcontratista_split = $item["Sub_Contratista"];

        $stmtCons = $db->queryWithProject("SELECT * FROM {$tProgConsolidado} WHERE Semana = ? AND Consecutivo_en_programa = ?", [$semana, $consecutivo_pg]);
        $dataCons = $stmtCons->fetch();

        if (!$dataCons) {
            continue;
        }

        $ejecutadoActual = (float) $dataCons["Ejecutado"];
        $cantidadPpto = (float) ($dataCons["cantidad_ppto"] ?? 0);
        $compromisoFinal = null;
        if ($item["Compromiso"] !== null && $item["Compromiso"] !== '') {
            $compromisoFinal = (float) $item["Compromiso"];
            if ($compromisoFinal <= 0) {
                $compromisoFinal = null;
            }
        }

        // Buscar en la semana anterior priorizando el subcontratista dividido
        $stmtAnterior = $db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM {$tProgSemanal} WHERE Semana = ? AND Consecutivo_En_programa = ? AND Sub_Contratista = ?", [$semana - 1, $consecutivo_pg, $subcontratista_split]);
        $dataAnt = $stmtAnterior->fetch();

        // Si no lo encuentra, buscar solo por Consecutivo_En_Programa
        if (!$dataAnt) {
            $stmtAnteriorFallBack = $db->queryWithProject("SELECT Responsable_AIA, Empresa, Descripcion, Ubicacion FROM {$tProgSemanal} WHERE Semana = ? AND Consecutivo_En_programa = ?", [$semana - 1, $consecutivo_pg]);
            $dataAnt = $stmtAnteriorFallBack->fetch();
        }

        // Preservamos el subcontratista que ya viene dividido en $item (insertado en el Paso 3)
        $sub = $subcontratista_split ?: ($dataCons["Sub_Contratista"] ?? null);
        $resp = $dataCons["Responsable_AIA"] ?: ($dataAnt["Responsable_AIA"] ?? null);
        $empresa = $dataAnt["Empresa"] ?? 'AIA';
        $desc = $dataAnt["Descripcion"] ?? null;
        $ubica = $dataAnt["Ubicacion"] ?? null;

        $sqlActSemana = "UPDATE {$tProgSemanal} SET
            Fecha_Inicio = ?, Fecha_Fin = ?, Sub_Contratista = ?, Responsable_AIA = ?,
            Ejecutado = ?, medir_productividad = ?, Critica = ?,
            Atrasada = (CASE WHEN ? IN ('Atrasada', 'Ya Debió Iniciar y Restricciones Pendientes') THEN 1 ELSE 0 END),
            Descripcion = ?, Ubicacion = ?, Empresa = ?, Unidad = COALESCE(NULLIF(TRIM(?), ''), '%'),
            cantidad_ppto = ?, codigo_actividad = ?, Compromiso = ?
            WHERE Semana = ? AND Consecutivo = ?";

        $db->queryWithProject($sqlActSemana, [
            $dataCons['Fecha_Inicio'], $dataCons['Fecha_Fin'], $sub, $resp,
            $ejecutadoActual, 0, (int) ($dataCons["Ruta_Critica"] ?? 0),
            $dataCons["Estado"], $desc, $ubica, $empresa, $dataCons["unidad"],
            ($cantidadPpto > 0 ? $cantidadPpto : null), $dataCons["codigo_actividad"], $compromisoFinal,
            $semana, $consecutivo_pk,
        ]);
    }

    // 5. Limpieza
    $stmtConsLimpieza = $db->queryWithProject("SELECT Consecutivo_en_Programa FROM {$tProgConsolidado} WHERE Semana = ? AND Ejecutado = 0 AND Semanas_Inicio > 0 AND Activa != 'NA'", [$semana]);
    $noIniciadas = $stmtConsLimpieza->fetchAll(PDO::FETCH_COLUMN);

    $whereLimpieza = "";
    $paramsDelete = [$semana];
    if (!empty($noIniciadas)) {
        $placeholders = implode(',', array_fill(0, count($noIniciadas), '?'));
        $whereLimpieza = "OR Consecutivo_En_Programa IN ($placeholders)";
        $paramsDelete = array_merge($paramsDelete, $noIniciadas);
    }

    $sqlDeleteLimpieza = "DELETE FROM {$tProgSemanal} WHERE Semana = ? AND ((Ejecutado = 1 AND Activa != 'NA') $whereLimpieza)";
    $db->queryWithProject($sqlDeleteLimpieza, $paramsDelete);

    // 6. Actualización final
    $db->queryWithProject("UPDATE {$tProgSemanal} ps
                JOIN {$tProgConsolidado} pc ON ps.Consecutivo_En_Programa = pc.Consecutivo_en_Programa AND ps.Semana = pc.Semana
                SET ps.Prog_Sin_Restricciones_100 = (CASE WHEN {$hardEligibilitySqlPc} THEN 0 ELSE 1 END),
                    ps.Ejecutado = pc.Ejecutado
                WHERE ps.Semana = ? AND ps.Activa != 'NA'", [$semana]);

    $db->queryWithProject("UPDATE {$tProgSemanal} SET Prog_Sin_Restricciones_100 = 0 WHERE Semana = ? AND Activa = 'NA'", [$semana]);

    // 7. Identificar actividades que no se autoprogramaron por restricciones pendientes
    $restrictionColsSql = implode(', ', $restrictionConfig['allRestrictions']);
    $sqlRestricciones = "SELECT
        Id, Actividad, {$restrictionColsSql}
    FROM {$tProgConsolidado}
    WHERE Semana = ? AND Titulo = 0
      AND NOT {$hardEligibilitySql}
      AND (
Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
		OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes'
      )
      $whereExistentes";

    $stmtRest = $db->queryWithProject($sqlRestricciones, $paramsInsert);
    $fallidas = $stmtRest->fetchAll(PDO::FETCH_ASSOC);

    $alertasRestricciones = [];
    // Build display labels from column names; explicit map for well-known columns
    $restrictionDisplayLabels = [
        'D_y_E' => 'D. y Especificaciones',
        'Materiales' => 'Materiales',
        'MdeO' => 'Mano de Obra',
        'Equipos' => 'Equipos',
        'Predecesora' => 'Predecesora',
        'Pdto_Cons' => 'Pdto. Constructivo',
        'Modelo' => 'Modelo BIM',
        'restriccion_pc_1' => 'Restricción PC 1',
        'restriccion_pc_2' => 'Restricción PC 2',
        'restriccion_pc_3' => 'Restricción PC 3',
        'restriccion_pc_4' => 'Restricción PC 4',
    ];

    $hardRestrictionLabels = [];
    foreach ($restrictionConfig['hardRestrictions'] as $col) {
        $hardRestrictionLabels[$col] = [
            'label' => $restrictionDisplayLabels[$col] ?? $col,
            'threshold' => $restrictionConfig['thresholds'][$col] ?? 1.0,
        ];
    }
    $softRestrictionLabels = [];
    foreach ($restrictionConfig['softRestrictions'] as $col) {
        $softRestrictionLabels[$col] = [
            'label' => $restrictionDisplayLabels[$col] ?? $col,
            'threshold' => $restrictionConfig['thresholds'][$col] ?? 1.0,
        ];
    }

    foreach ($fallidas as $row) {
        $pendientes = $buildRestrictionAlertParts($row, $hardRestrictionLabels);
        if (empty($pendientes)) {
            continue;
        }
        $blandas = $buildRestrictionAlertParts($row, $softRestrictionLabels);

        $actLabel = trim(preg_replace('/\s+/', ' ', preg_replace('/<[^>]*>/', ' ', (string) ($row['Actividad'] ?? ''))));

        $alertasRestricciones[] = [
            'Id' => $row['Id'],
            'Actividad' => $actLabel,
            'RestriccionesPendientes' => implode(', ', $pendientes),
            'RestriccionesBlandas' => implode(', ', $blandas),
        ];
    }

    $db->logActivity('Sistema', 'AUTOPROGRAMAR', "Actividades autoprogramadas para semana $semana");
    echo json_encode([
        "respuesta" => "OK",
        "alertasRestricciones" => $alertasRestricciones,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en autoprogramar_actividades.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}
