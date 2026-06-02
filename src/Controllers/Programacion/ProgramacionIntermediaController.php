<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;

class ProgramacionIntermediaController extends BaseController
{
    public function index()
    {
        // Validar autenticación y gestionar timeout (centralizado en BaseController)
        $this->requireAuth();

        // Obtener variables de sesión comunes
        $vars = $this->getSessionVars();
        $dbName = $vars['dbName'];
        if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            $dbName = '';
        }

        // 1. Subcontratistas
        $subcontratistas = [];
        if ($dbName) {
            $stmt = $this->db->query("SELECT * FROM {$dbName}_subcontratistas WHERE activo=1");
            $subcontratistas = $stmt->fetchAll();
        }

        // 2. Profesionales
        $profesionales = [];
        if ($dbName) {
            // Nota: 'Activo' con mayúscula según convención original
            $stmt = $this->db->query("SELECT * FROM {$dbName}_profesionales WHERE Activo=1");
            $profesionales = $stmt->fetchAll();
        }

        $viewAll = isset($_SESSION['pi_view_all']) && (int) $_SESSION['pi_view_all'] === 1;

        $data = array_merge($vars, [
            'subcontratistas' => $subcontratistas,
            'profesionales' => $profesionales,
            'viewAll' => $viewAll,
        ]);

        $this->render('/views/programacion-intermedia/programacion_intermedia.view.php', $data);
    }

    /**
     * API: Lista actividades de PI con filtros de estado operativo.
     */
    public function list()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.ver');
        header('Content-Type: application/json; charset=utf-8');

        require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_intermedia.php';

        $vars = $this->getSessionVars();
        $dbPrefix = $vars['dbName'] ?? '';
        $semana = $vars['semana'] ?? 0;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            echo json_encode(["data" => []]);
            return;
        }

        $stateKeys = pi_filter_state_keys();
        $activeStates = [];

        foreach ($stateKeys as $stateKey) {
            $suffix = str_replace('-', '_', $stateKey);
            $isActive = isset($_GET['activa_' . $suffix]) ? (int) $_GET['activa_' . $suffix] : 0;
            if ($isActive === 1) {
                $activeStates[] = $stateKey;
            }
        }

        if (empty($activeStates)) {
            $legacyFlags = [
                'lookahead' => isset($_GET['activa_lookahead']) ? (int) $_GET['activa_lookahead'] : 0,
                'no_iniciadas' => isset($_GET['activa_no_iniciadas']) ? (int) $_GET['activa_no_iniciadas'] : 0,
                'en_ejecucion_pendientes' => isset($_GET['activa_en_ejecucion_pendientes']) ? (int) $_GET['activa_en_ejecucion_pendientes'] : 0,
                'en_ejecucion_terminadas' => isset($_GET['activa_en_ejecucion_terminadas']) ? (int) $_GET['activa_en_ejecucion_terminadas'] : 0,
            ];
            foreach ($legacyFlags as $legacyClass => $legacyActive) {
                if ($legacyActive === 1) {
                    $activeStates = array_values(array_unique(array_merge($activeStates, pi_resolve_filter_targets($legacyClass))));
                }
            }
        }

        try {
            $viewAll = isset($_SESSION['pi_view_all']) && (int) $_SESSION['pi_view_all'] === 1;

            $where = "Semana = ?
                      AND Fecha_Inicio IS NOT NULL
                      AND Fecha_Fin IS NOT NULL
                      AND Ejecutado < 1
                      AND Titulo = 0";
            if (!$viewAll) {
                $where .= " AND Semanas_Inicio <= 6";
            }

            $query = "SELECT *
                      FROM {$dbPrefix}_programa_consolidado
                      WHERE {$where}
                      ORDER BY Semanas_Inicio ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$semana]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $filteredRows = [];
            foreach ($rows as $row) {
                $row['Estado_Restricciones'] = $this->calculateRestrictionState($row);
                $state = pi_classify_state($row);
                if (!empty($activeStates) && !in_array($state, $activeStates, true)) {
                    continue;
                }
                $row['boton'] = 'Boton';
                $filteredRows[] = $row;
            }

            if (count($filteredRows) === 0) {
                echo json_encode(["data" => [["Consecutivo" => "","Semana" => "","Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Semanas_Inicio" => "","Fecha_Inicio" => "","Fecha_Fin" => "","Ruta_Critica" => "","Ejecutado" => "","Estado" => "","Estado_Restricciones" => "","Responsable_AIA" => "","Observaciones" => "","boton" => ""]]]);
            } else {
                echo json_encode(["data" => $filteredRows], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            error_log("PI list error: " . $e->getMessage());
            echo json_encode(["data" => []]);
        }
    }

    /**
     * API: Guarda modificaciones de restricciones en PI.
     * Delega al script legacy que contiene lógica de alertas.
     */
    public function save()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.editar');
        header('Content-Type: application/json; charset=utf-8');

        require_once PROJECT_ROOT . '/src/Legacy/estado_programa_general.php';
        require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_intermedia.php';

        $vars = $this->getSessionVars();
        $dbPrefix = $_POST['db'] ?? $_GET['db'] ?? ($vars['dbName'] ?? '');
        $semanaReq = $_POST['semana'] ?? $_GET['semana'] ?? null;

        if ($semanaReq !== null && $semanaReq !== '') {
            $_SESSION['semana'] = (int) $semanaReq;
        }

        if ($dbPrefix !== '' && (!isset($_SESSION['db']) || $_SESSION['db'] === '')) {
            $_SESSION['db'] = $dbPrefix;
        }

        // Delegate to legacy script (contains modificar, alerts, notifications)
        require PROJECT_ROOT . '/src/Legacy/guardar_programacion_intermedia.php';
    }

    /**
     * AJAX endpoint: devuelve contadores y filtros activos
     * para el semáforo operativo de Programación Intermedia.
     */
    public function getFilters()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.ver');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Method Not Allowed"]);

            return;
        }

        $dbPrefix = $_POST['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix) || $semana === false) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Parámetros inválidos."]);

            return;
        }

        require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_intermedia.php';

        $viewAll = isset($_SESSION['pi_view_all']) && (int) $_SESSION['pi_view_all'] === 1;

        $query = "SELECT Titulo, Semanas_Inicio, Estado_Restricciones, Ejecutado, Estado, Ruta_Critica,
                         D_y_E, Materiales, MdeO, Equipos, Predecesora
                  FROM {$dbPrefix}_programa_consolidado
                  WHERE Semana = ?
                    AND Fecha_Inicio IS NOT NULL
                    AND Fecha_Fin IS NOT NULL
                    AND Semanas_Inicio <= 6
                    AND Ejecutado < 1
                    AND Titulo = 0";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$semana]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $payload = [];
            $counters = [];

            foreach (\pi_filter_state_keys() as $stateKey) {
                $suffix = str_replace('-', '_', $stateKey);
                $counters[$stateKey] = 0;
                $payload['count_' . $suffix] = 0;
                $payload['activa_' . $suffix] = (isset($_SESSION[\pi_state_session_key($stateKey)]) && (int) $_SESSION[\pi_state_session_key($stateKey)] === 1) ? 1 : 0;
            }

            $payload['view_all'] = $viewAll ? 1 : 0;
            $payload['window_label'] = $viewAll ? 'Ventana 6 sem.' : null;

            foreach ($rows as $row) {
                $state = \pi_classify_state($row);
                if (isset($counters[$state])) {
                    $counters[$state]++;
                }
            }

            foreach ($counters as $state => $count) {
                $suffix = str_replace('-', '_', $state);
                $payload['count_' . $suffix] = $count;
            }

            $payload['total'] = array_sum($counters);

            header('Content-Type: application/json');
            echo json_encode(['data' => $payload], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log("Error en ProgramacionIntermediaController::getFilters: " . $e->getMessage());
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudieron obtener los filtros."]);
        }
    }

    /**
     * Actualiza estado de filtro en sesión.
     */
    public function setFilter()
    {
        $this->requireAuth();

        require_once PROJECT_ROOT . '/src/Legacy/estado_programacion_intermedia.php';

        $clase = $_GET['clase'] ?? '';
        $activa = isset($_GET['activa']) ? (int) $_GET['activa'] : 0;

        $states = \pi_filter_state_keys();

        if ($clase === 'total') {
            foreach ($states as $stateKey) {
                $_SESSION[\pi_state_session_key($stateKey)] = 0;
            }
            $_SESSION['total_intermedia'] = 1;
        } else {
            $_SESSION['total_intermedia'] = 0;
            $targets = \pi_resolve_filter_targets($clase);
            foreach ($targets as $stateKey) {
                $_SESSION[\pi_state_session_key($stateKey)] = ($activa === 1) ? 1 : 0;
            }
        }

        header('Location: /programacion-intermedia');
        exit;
    }

    /**
     * Alterna el modo "Ver Todas las Actividades" en PI.
     * Cuando esta activo, el listado se calcula sin la barrera
     * Semanas_Inicio <= 6, mostrando tambien actividades que aun
     * no entran en la ventana de liberacion de restricciones.
     * Por defecto: desactivado.
     *
     * Soporta dos modos:
     *  - AJAX (X-Requested-With: XMLHttpRequest o ?ajax=1): devuelve JSON.
     *  - Navegacion normal: redirige a /programacion-intermedia.
     */
    public function setViewAll()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.editar');

        $activa = isset($_GET['activa']) ? (int) $_GET['activa'] : 0;
        $_SESSION['pi_view_all'] = ($activa === 1) ? 1 : 0;

        $isAjax = (isset($_GET['ajax']) && (int) $_GET['ajax'] === 1)
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'respuesta' => 'BIEN',
                'view_all' => $_SESSION['pi_view_all'],
                'redirect' => '/programacion-intermedia',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: /programacion-intermedia');
        exit;
    }

    /**
     * Preview de impacto para aplicar una restricción compartida.
     */
    public function previewSharedConstraints()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.ver');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Method Not Allowed"]);

            return;
        }

        $payload = $this->resolveSharedConstraintPayload(false);
        if (!$payload['ok']) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $payload['mensaje']]);

            return;
        }

        try {
            $rows = $this->fetchSharedConstraintRows($payload['dbPrefix'], $payload['semana'], $payload['activityIds']);
            $foundIds = [];
            $preview = [];

            foreach ($rows as $row) {
                $id = (string) ($row['Consecutivo_en_Programa'] ?? '');
                if ($id !== '') {
                    $foundIds[] = $id;
                }

                $preview[] = [
                    'consecutivo' => $id,
                    'id_actividad' => $row['Id'] ?? '',
                    'actividad' => $row['Actividad'] ?? '',
                    'valor_actual' => $row[$payload['restrictionType']] ?? '',
                    'restricciones_actuales' => [
                        'D_y_E' => $row['D_y_E'] ?? '',
                        'Materiales' => $row['Materiales'] ?? '',
                        'MdeO' => $row['MdeO'] ?? '',
                        'Equipos' => $row['Equipos'] ?? '',
                        'Predecesora' => $row['Predecesora'] ?? '',
                        'Pdto_Cons' => $row['Pdto_Cons'] ?? '',
                        'Modelo' => $row['Modelo'] ?? '',
                    ],
                    'sub_contratista_actual' => $row['Sub_Contratista'] ?? '',
                    'responsable_aia_actual' => $row['Responsable_AIA'] ?? '',
                ];
            }

            $missingIds = array_values(array_diff($payload['activityIds'], $foundIds));

            header('Content-Type: application/json');
            echo json_encode([
                'respuesta' => 'BIEN',
                'data' => [
                    'restriction_type' => $payload['restrictionType'],
                    'target_value' => $payload['targetValue'],
                    'restrictions' => $payload['restrictions'],
                    'apply_restriction' => $payload['applyRestriction'] ? 1 : 0,
                    'apply_assignments' => $payload['applyAssignments'] ? 1 : 0,
                    'sub_contratista' => $payload['subContratista'],
                    'responsable_aia' => $payload['responsableAia'],
                    'count_total' => count($payload['activityIds']),
                    'count_found' => count($foundIds),
                    'count_missing' => count($missingIds),
                    'found_ids' => $foundIds,
                    'missing_ids' => $missingIds,
                    'preview' => $preview,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('Error previewSharedConstraints: ' . $e->getMessage());
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudo calcular el preview."]);
        }
    }

    /**
     * Aplica una restricción compartida en lote y recalcula Estado_Restricciones.
     */
    public function applySharedConstraints()
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programacion_intermedia.editar');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Method Not Allowed"]);

            return;
        }

        $payload = $this->resolveSharedConstraintPayload(true);
        if (!$payload['ok']) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $payload['mensaje']]);

            return;
        }

        try {
            $rows = $this->fetchSharedConstraintRows($payload['dbPrefix'], $payload['semana'], $payload['activityIds']);
            if (empty($rows)) {
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se encontraron actividades para aplicar la restricción."]);

                return;
            }

            $dbPrefix = $payload['dbPrefix'];
            $semana = $payload['semana'];
            $restrictionColumn = $payload['restrictionType'];
            $note = $payload['note'];
            $applyRestriction = $payload['applyRestriction'];
            $restrictions = $payload['restrictions'];
            $applyAssignments = $payload['applyAssignments'];
            $subContratista = $payload['subContratista'];
            $responsableAia = $payload['responsableAia'];
            $normalizedRestrictions = [];

            if ($applyRestriction) {
                foreach ($restrictions as $restriction) {
                    $type = $restriction['type'];
                    $normalizedValue = $this->normalizeSharedRestrictionInput($type, $restriction['value']);
                    if ($normalizedValue === null) {
                        echo json_encode(["respuesta" => "ERROR", "mensaje" => "Valor de restricción inválido para {$type}."]);

                        return;
                    }

                    $normalizedRestrictions[] = [
                        'type' => $type,
                        'value' => $normalizedValue,
                    ];
                }
            }

            $setClauses = [];
            if ($applyRestriction) {
                foreach ($normalizedRestrictions as $restriction) {
                    $setClauses[] = "{$restriction['type']} = ?";
                }
                $setClauses[] = "Estado_Restricciones = ?";
            }
            if ($applyAssignments && $subContratista !== '') {
                $setClauses[] = "Sub_Contratista = ?";
            }
            if ($responsableAia !== '') {
                $setClauses[] = "Responsable_AIA = ?";
            }
            $setClauses[] = "Activa = 1";

            $sharedTablesReady = $applyRestriction ? $this->ensureSharedConstraintTables($dbPrefix) : false;
            $this->db->beginTransaction();

            $sharedIdsByType = [];
            $trackSharedLinks = false;
            $insertLinkStmt = null;

            if ($sharedTablesReady) {
                try {
                    $createdBy = (string) ($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? $_SESSION['nombre_usuario'] ?? 'system');
                    $insertSharedSql = "INSERT INTO {$dbPrefix}_pi_shared_constraints (Semana, Restriccion, ValorObjetivo, Nota, CreadoPor) VALUES (?, ?, ?, ?, ?)";
                    $insertSharedStmt = $this->db->prepare($insertSharedSql);

                    foreach ($normalizedRestrictions as $restriction) {
                        $insertSharedStmt->execute([
                            $semana,
                            $restriction['type'],
                            (string) $restriction['value'],
                            $note,
                            $createdBy,
                        ]);

                        $sharedId = (int) $this->db->lastInsertId();
                        if ($sharedId > 0) {
                            $sharedIdsByType[$restriction['type']] = $sharedId;
                        }
                    }

                    $insertLinkSql = "INSERT INTO {$dbPrefix}_pi_shared_constraint_links (SharedConstraintId, Semana, ConsecutivoEnPrograma, ValorAplicado) VALUES (?, ?, ?, ?)";
                    $insertLinkStmt = $this->db->prepare($insertLinkSql);
                    $trackSharedLinks = (!empty($sharedIdsByType) && $insertLinkStmt !== false);
                } catch (\Throwable $trackingError) {
                    $trackSharedLinks = false;
                    $sharedIdsByType = [];
                    $insertLinkStmt = null;
                    error_log('Shared constraint tracking disabled: ' . $trackingError->getMessage());
                }
            }

            $updateSql = "UPDATE {$dbPrefix}_programa_consolidado SET " . implode(', ', $setClauses) . " WHERE Consecutivo_en_Programa = ? AND Semana = ? AND Titulo = 0";
            $updateStmt = $this->db->prepare($updateSql);

            $updated = 0;
            $updatedIds = [];

            foreach ($rows as $row) {
                $rowId = (string) ($row['Consecutivo_en_Programa'] ?? '');
                if ($rowId === '') {
                    continue;
                }

                $updateParams = [];
                if ($applyRestriction) {
                    foreach ($normalizedRestrictions as $restriction) {
                        $row[$restriction['type']] = $restriction['value'];
                        $updateParams[] = $restriction['value'];
                    }
                    $updateParams[] = $this->calculateRestrictionState($row);
                }
                if ($applyAssignments && $subContratista !== '') {
                    $updateParams[] = $subContratista;
                }
                if ($applyAssignments && $responsableAia !== '') {
                    $updateParams[] = $responsableAia;
                }
                $updateParams[] = $rowId;
                $updateParams[] = $semana;

                $updateStmt->execute($updateParams);

                if ($applyRestriction && $trackSharedLinks && $insertLinkStmt) {
                    foreach ($normalizedRestrictions as $restriction) {
                        $sharedId = $sharedIdsByType[$restriction['type']] ?? 0;
                        if ($sharedId <= 0) {
                            continue;
                        }

                        try {
                            $insertLinkStmt->execute([
                                $sharedId,
                                $semana,
                                $rowId,
                                (string) $restriction['value'],
                            ]);
                        } catch (\Throwable $linkError) {
                            $trackSharedLinks = false;
                            error_log('Shared constraint link tracking disabled: ' . $linkError->getMessage());
                            break;
                        }
                    }
                }

                $updated++;
                $updatedIds[] = $rowId;
            }

            $this->db->commit();

            $camposNames = [
                'D_y_E' => 'Diseños',
                'Materiales' => 'Materiales',
                'MdeO' => 'Mano de Obra',
                'Equipos' => 'Equipos',
                'Predecesora' => 'Predecesora',
                'Pdto_Cons' => 'Pdto. Constructivo',
                'Modelo' => 'Modelo BIM',
            ];
            $operationParts = [];
            if ($applyRestriction) {
                $restrictionParts = [];
                foreach ($normalizedRestrictions as $restriction) {
                    $valDisplay = $restriction['value'] === 'N/A' || $restriction['value'] === 'n/a' ? 'N/A' : (round((float) $restriction['value'] * 100) . '%');
                    $cName = $camposNames[$restriction['type']] ?? $restriction['type'];
                    $restrictionParts[] = "{$cName} a {$valDisplay}";
                }
                $operationParts[] = 'Restricciones: ' . implode(', ', $restrictionParts);
            }
            if ($applyAssignments && $subContratista !== '') {
                $operationParts[] = "Sub-Contratista: {$subContratista}";
            }
            if ($applyAssignments && $responsableAia !== '') {
                $operationParts[] = "Responsable AIA: {$responsableAia}";
            }
            $operationSummary = implode('; ', $operationParts);

            $this->db->logActivity(
                'ProgramacionIntermedia',
                'SHARED_RESTRICTION_APPLY',
                "Aplicó lote PI ({$operationSummary}) en {$updated} actividades (semana {$semana})",
                $dbPrefix,
            );

            // Emitir Notificación de Restricción Compartida (Fase 5)
            try {
                if ($updated > 0) {
                    $svc = new \App\Services\NotificationService();
                    $usersByRole = $svc->getUsersByRoleForProject($dbPrefix);
                    $msg = "Lote PI: {$operationSummary} en {$updated} actividades - Semana {$semana}";

                    // Notificar a D, R, A
                    $notifiedUids = [];
                    $roles = \App\Core\Notifications\NotificationType::getRoles(\App\Core\Notifications\NotificationType::PI_SHARED_APPLIED);

                    foreach ($roles as $role) {
                        $users = $usersByRole[$role] ?? [];
                        foreach ($users as $uid) {
                            if (!isset($notifiedUids[$uid])) {
                                $svc->emit($uid, \App\Core\Notifications\NotificationType::PI_SHARED_APPLIED, $msg, $dbPrefix);
                                $notifiedUids[$uid] = true;
                            }
                        }
                    }

                    // Notificar a los Responsables AIA afectados (extrayendo únicos de $rows)
                    $affectedResponsibles = [];
                    if ($applyAssignments && $responsableAia !== '') {
                        $rUsername = $svc->findUsernameByName($responsableAia);
                        if ($rUsername) {
                            $affectedResponsibles[$responsableAia] = $rUsername;
                        }
                    } else {
                        foreach ($rows as $r) {
                            $rName = trim($r['Responsable_AIA'] ?? '');
                            if ($rName !== '' && !isset($affectedResponsibles[$rName])) {
                                $rUsername = $svc->findUsernameByName($rName);
                                if ($rUsername) {
                                    $affectedResponsibles[$rName] = $rUsername;
                                }
                            }
                        }
                    }

                    foreach ($affectedResponsibles as $rUsername) {
                        if (!isset($notifiedUids[$rUsername])) {
                            $svc->emit($rUsername, \App\Core\Notifications\NotificationType::PI_SHARED_APPLIED, $msg, $dbPrefix);
                            $notifiedUids[$rUsername] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log("PI_SHARED_NOTIF_ERROR: " . $e->getMessage());
            }

            header('Content-Type: application/json');
            echo json_encode([
                'respuesta' => 'BIEN',
                'data' => [
                    'shared_constraint_id' => reset($sharedIdsByType) ?: null,
                    'shared_constraint_ids' => $sharedIdsByType,
                    'tracking_enabled' => $trackSharedLinks,
                    'updated_count' => $updated,
                    'updated_ids' => $updatedIds,
                    'apply_restriction' => $applyRestriction ? 1 : 0,
                    'apply_assignments' => $applyAssignments ? 1 : 0,
                    'restriction_type' => $restrictionColumn,
                    'target_value' => $normalizedRestrictions[0]['value'] ?? '',
                    'restrictions' => $normalizedRestrictions,
                    'sub_contratista' => $subContratista,
                    'responsable_aia' => $responsableAia,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Error applySharedConstraints: ' . $e->getMessage());
            $detail = trim((string) $e->getMessage());
            $detail = preg_replace('/\s+/', ' ', $detail);
            $publicMessage = 'No se pudo aplicar el lote de Programación Intermedia. Revise datos y permisos de escritura.';
            if ($detail !== '') {
                $publicMessage .= ' Detalle: ' . substr($detail, 0, 180);
            }

            echo json_encode(["respuesta" => "ERROR", "mensaje" => $publicMessage], JSON_UNESCAPED_UNICODE);
        }
    }

    private function resolveSharedConstraintPayload(bool $requireValue): array
    {
        $vars = $this->getSessionVars();
        $dbPrefix = $_POST['db'] ?? ($vars['dbName'] ?? '');
        $semana = isset($_POST['semana']) ? filter_var($_POST['semana'], FILTER_VALIDATE_INT) : ($vars['semana'] ?? null);
        $applyRestriction = $this->parseSharedBool($_POST['apply_restriction'] ?? null, true);
        $applyAssignments = $this->parseSharedBool($_POST['apply_assignments'] ?? null, false);
        $restrictionType = trim((string) ($_POST['restriction_type'] ?? ''));
        $activityIds = $this->parseActivityIds($_POST['activity_ids'] ?? []);
        $note = trim((string) ($_POST['note'] ?? ''));
        $targetValue = $_POST['target_value'] ?? null;
        $subContratista = $applyAssignments ? trim((string) ($_POST['sub_contratista'] ?? '')) : '';
        $responsableAia = trim((string) ($_POST['responsable_aia'] ?? ''));

        $allowedRestrictions = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora', 'Pdto_Cons', 'Modelo'];
        $restrictions = $applyRestriction
            ? $this->parseSharedRestrictionsInput($_POST['restrictions'] ?? null, $restrictionType, $targetValue, $allowedRestrictions)
            : [];

        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $dbPrefix)) {
            return ['ok' => false, 'mensaje' => 'Base de datos inválida.'];
        }

        if ($semana === false || $semana === null || (int) $semana <= 0) {
            return ['ok' => false, 'mensaje' => 'Semana inválida.'];
        }

        if (!$applyRestriction && !$applyAssignments) {
            return ['ok' => false, 'mensaje' => 'Debe activar al menos una operación de lote.'];
        }

        if ($applyRestriction && empty($restrictions)) {
            return ['ok' => false, 'mensaje' => 'Debe seleccionar al menos una restricción válida.'];
        }

        if (!$applyRestriction) {
            $restrictionType = '';
            $targetValue = '';
        } else {
            $restrictionType = $restrictions[0]['type'];
            $targetValue = $restrictions[0]['value'];
        }

        if (empty($activityIds)) {
            return ['ok' => false, 'mensaje' => 'Debe seleccionar al menos una actividad.'];
        }

        if ($requireValue && $applyRestriction) {
            foreach ($restrictions as $restriction) {
                if ($restriction['value'] === '') {
                    return ['ok' => false, 'mensaje' => 'Debe definir valor objetivo para todas las restricciones seleccionadas.'];
                }
            }
        }

        if ($applyRestriction && $responsableAia === '') {
            return ['ok' => false, 'mensaje' => 'Responsable AIA es obligatorio para aplicar restricciones en lote.'];
        }

        if ($applyAssignments && !$applyRestriction && $subContratista === '' && $responsableAia === '') {
            return ['ok' => false, 'mensaje' => 'Debe seleccionar Sub-Contratista o Responsable para aplicar asignaciones.'];
        }

        return [
            'ok' => true,
            'dbPrefix' => $dbPrefix,
            'semana' => (int) $semana,
            'applyRestriction' => $applyRestriction,
            'applyAssignments' => $applyAssignments,
            'restrictionType' => $restrictionType,
            'restrictions' => $restrictions,
            'activityIds' => $activityIds,
            'note' => $note,
            'targetValue' => $targetValue,
            'subContratista' => $subContratista,
            'responsableAia' => $responsableAia,
        ];
    }

    private function parseSharedBool($value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return $default;
        }

        return in_array($text, ['1', 'true', 'yes', 'on', 'si'], true);
    }

    private function parseSharedRestrictionsInput($rawRestrictions, string $fallbackType, $fallbackValue, array $allowedRestrictions): array
    {
        $items = [];
        $raw = $rawRestrictions;

        if (is_string($raw)) {
            $text = trim($raw);
            if ($text !== '') {
                $decoded = json_decode($text, true);
                $raw = is_array($decoded) ? $decoded : [];
            } else {
                $raw = [];
            }
        }

        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $type = trim((string) ($item['type'] ?? $item['restriction_type'] ?? ''));
                if (!in_array($type, $allowedRestrictions, true)) {
                    continue;
                }

                $value = $item['value'] ?? $item['target_value'] ?? '';
                $items[$type] = [
                    'type' => $type,
                    'value' => trim((string) $value),
                ];
            }
        }

        if (empty($items) && in_array($fallbackType, $allowedRestrictions, true)) {
            $items[$fallbackType] = [
                'type' => $fallbackType,
                'value' => trim((string) $fallbackValue),
            ];
        }

        return array_values($items);
    }

    private function parseActivityIds($rawIds): array
    {
        $result = [];

        if (is_array($rawIds)) {
            $tokens = $rawIds;
        } else {
            $rawText = trim((string) $rawIds);
            if ($rawText === '') {
                return [];
            }
            $tokens = preg_split('/[\s,;\n\r]+/', $rawText);
        }

        foreach ($tokens as $token) {
            $value = trim((string) $token);
            if ($value === '' || !preg_match('/^[0-9]+$/', $value)) {
                continue;
            }

            if (!in_array($value, $result, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    private function fetchSharedConstraintRows(string $dbPrefix, int $semana, array $activityIds): array
    {
        if (empty($activityIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
        $sql = "SELECT Consecutivo_en_Programa, Id, Actividad, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA
                FROM {$dbPrefix}_programa_consolidado
                WHERE Semana = ?
                  AND Titulo = 0
                  AND Consecutivo_en_Programa IN ({$placeholders})";

        $params = array_merge([$semana], $activityIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function normalizeSharedRestrictionInput(string $restrictionType, $value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (strcasecmp($text, 'N/A') === 0 || strcasecmp($text, 'NO APLICA') === 0) {
            return 'N/A';
        }

        $ratio = $this->parseRatio($text);
        if ($ratio === null) {
            return null;
        }

        $allowed = ($restrictionType === 'Predecesora' || $restrictionType === 'Pdto_Cons' || $restrictionType === 'Modelo')
            ? [0.0, 0.5, 1.0]
            : [0.0, 0.33, 0.66, 1.0];

        $nearest = $allowed[0];
        $minDiff = abs($allowed[0] - $ratio);

        foreach ($allowed as $candidate) {
            $diff = abs($candidate - $ratio);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $candidate;
            }
        }

        if ($nearest <= 0.0) {
            return 0;
        }

        if ($nearest >= 1.0) {
            return 1;
        }

        return round($nearest, 5);
    }

    private function parseRatio($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $hasPercent = (strpos($raw, '%') !== false) || (strpos($raw, '％') !== false);

        $normalized = preg_replace('/\s+/', '', $raw);
        $normalized = str_replace(['%', '％'], '', $normalized);
        if ($normalized === '') {
            return null;
        }

        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $ratio = (float) $normalized;
        if ($hasPercent) {
            $ratio = $ratio / 100;
        }

        while ($ratio > 1.0 && $ratio <= 10000.0) {
            $ratio = $ratio / 100;
        }

        if ($ratio < 0.0) {
            $ratio = 0.0;
        }

        if ($ratio > 1.0) {
            $ratio = 1.0;
        }

        return $ratio;
    }

    private function calculateRestrictionState(array $row): float
    {
        $fields = [
            'D_y_E' => 1.0,
            'Materiales' => 1.0,
            'MdeO' => 1.0,
            'Equipos' => 1.0,
            'Predecesora' => 0.5,
            'Pdto_Cons' => 1.0,
            'Modelo' => 1.0,
        ];
        $sum = 0.0;
        $count = 0;

        foreach ($fields as $field => $threshold) {
            $value = $row[$field] ?? null;
            if ($value === null) {
                continue;
            }

            $text = trim((string) $value);
            if ($text === '' || strcasecmp($text, 'N/A') === 0) {
                continue;
            }

            $ratio = $this->parseRatio($text);
            if ($ratio === null) {
                continue;
            }

            $sum += min($ratio / $threshold, 1.0);
            $count++;
        }

        if ($count === 0) {
            return 1.0;
        }

        $result = $sum / $count;
        if ($result < 0.0) {
            $result = 0.0;
        }

        if ($result > 1.0) {
            $result = 1.0;
        }

        return round($result, 5);
    }

    private function ensureSharedConstraintTables(string $dbPrefix): bool
    {
        try {
            $sqlShared = "CREATE TABLE IF NOT EXISTS {$dbPrefix}_pi_shared_constraints (
                Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                Semana INT NOT NULL,
                Restriccion VARCHAR(40) NOT NULL,
                ValorObjetivo VARCHAR(20) NOT NULL,
                Nota TEXT NULL,
                CreadoPor VARCHAR(120) NULL,
                CreadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ActualizadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_semana (Semana),
                INDEX idx_restriccion (Restriccion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $sqlLinks = "CREATE TABLE IF NOT EXISTS {$dbPrefix}_pi_shared_constraint_links (
                Id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                SharedConstraintId BIGINT UNSIGNED NOT NULL,
                Semana INT NOT NULL,
                ConsecutivoEnPrograma VARCHAR(64) NOT NULL,
                ValorAplicado VARCHAR(20) NOT NULL,
                OverrideLocal TINYINT(1) NOT NULL DEFAULT 0,
                AplicadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_shared (SharedConstraintId),
                INDEX idx_semana_consecutivo (Semana, ConsecutivoEnPrograma)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->db->query($sqlShared);
            $this->db->query($sqlLinks);

            return true;
        } catch (\Throwable $e) {
            error_log('ensureSharedConstraintTables warning: ' . $e->getMessage());

            return false;
        }
    }
}
