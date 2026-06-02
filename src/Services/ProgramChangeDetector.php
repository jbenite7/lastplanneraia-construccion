<?php

namespace App\Services;

use App\Core\Notifications\NotificationType;
use PDO;

class ProgramChangeDetector
{
    private $db;

    private const RESTRICTION_COLUMNS = [
        'D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora',
    ];

    private const HARD_RESTRICTIONS = ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'];

    private const DEBE_COMPROMETER_STATES = [
        'En Curso', 'Atrasada', 'Debe Iniciar',
        'A Tiempo', 'Adelantada',
        'Ya Debió Iniciar y Restricciones Pendientes',
        'Debe Iniciar esta Semana',
        'Debe Iniciar esta Semana y Restricciones Pendientes',
    ];

    private const TERMINADA_STATES = [
        'Terminada', 'Terminada Antes',
    ];

    private const ACTIVIDAD_FUTURA_STATES = [
        'Actividad Futura',
        'En Liberación de Restricciones',
        'No Requerida',
    ];

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function run(string $dbPrefix, int $semana): array
    {
        $batchTime = date('Y-m-d H:i:s');
        $log = [];

        // Saneamiento preventivo de registros duplicados CNP (Activa = 0) en base de datos
        $this->db->query("
            DELETE p1 FROM {$dbPrefix}_programacion_semanal p1
            INNER JOIN {$dbPrefix}_programacion_semanal p2
               ON p1.Semana = p2.Semana
              AND p1.Consecutivo_En_Programa = p2.Consecutivo_En_Programa
              AND COALESCE(p1.Sub_Contratista, '') = COALESCE(p2.Sub_Contratista, '')
              AND p1.Activa = '0'
              AND p2.Activa = '0'
              AND p1.Consecutivo > p2.Consecutivo
            WHERE p1.Semana = ?
        ", [$semana]);

        $pgRows = $this->loadProgramaConsolidado($dbPrefix, $semana);
        $psConsecutivos = $this->loadPsConsecutivos($dbPrefix, $semana);
        $allPsConsecutivos = $this->loadAllPsConsecutivos($dbPrefix, $semana);
        $pgIndex = [];

        foreach ($pgRows as $pg) {
            $pgIndex[(int) $pg['Consecutivo_en_Programa']] = $pg;
        }

        $this->ensureLogTable($dbPrefix);
        $newlyCommitted = [];

        // --- PASO 1: PS huérfanas (en PS, no en PG) ---
        foreach ($psConsecutivos as $consecutivo) {
            if (!isset($pgIndex[$consecutivo])) {
                $this->deleteFromPs($dbPrefix, $semana, $consecutivo);
                $this->logAction(
                    $dbPrefix,
                    $semana,
                    $consecutivo,
                    'descomprometer',
                    'Actividad eliminada de Programación Semanal porque ya no existe en el Programa General.',
                    null,
                    null,
                    $batchTime,
                );
                $log[] = [
                    'consecutivo' => $consecutivo,
                    'accion' => 'descomprometer',
                    'motivo' => 'PS huérfana',
                    'categoria_cnp' => null,
                    'cnp' => null,
                    'detalle' => 'Actividad eliminada de Programación Semanal porque ya no existe en el Programa General.',
                ];
            }
        }

        // --- PASO 2: PG nuevas (en PG, no en PS de ninguna forma, activa o inactiva) ---
        foreach ($pgRows as $pg) {
            $consecutivo = (int) $pg['Consecutivo_en_Programa'];
            if (in_array($consecutivo, $allPsConsecutivos)) {
                continue;
            }

            $estado = trim($pg['Estado'] ?? '');
            if ($this->shouldSkip($pg, $estado)) {
                continue;
            }

            $grupo = $this->classifyState($estado);

            if ($grupo === 'debe_comprometer') {
                $restriccionesOk = $this->checkRestrictionsMet($pg);
                if ($restriccionesOk) {
                    $this->doComprometer($dbPrefix, $semana, $consecutivo, $pg);
                    $newlyCommitted[] = $consecutivo;
                    $this->logAction(
                        $dbPrefix,
                        $semana,
                        $consecutivo,
                        'comprometer',
                        'Actividad auto-programada: cumple restricciones duras y estado propicio.',
                        null,
                        null,
                        $batchTime,
                    );
                    $log[] = [
                        'consecutivo' => $consecutivo,
                        'accion' => 'comprometer',
                        'motivo' => 'Nueva actividad + restricciones OK',
                        'categoria_cnp' => null,
                        'cnp' => null,
                        'detalle' => 'Actividad auto-programada: cumple restricciones duras y estado propicio.',
                    ];
                } else {
                    $this->doInsertCnp(
                        $dbPrefix,
                        $semana,
                        $consecutivo,
                        $pg,
                        'Programación',
                        'Restricciones habilitantes no cumplidas',
                    );
                    $this->logAction(
                        $dbPrefix,
                        $semana,
                        $consecutivo,
                        'insert_cnp',
                        'Actividad insertada con CNP: no cumple restricciones habilitantes.',
                        'Programación',
                        'Restricciones habilitantes no cumplidas',
                        $batchTime,
                    );
                    $log[] = [
                        'consecutivo' => $consecutivo,
                        'accion' => 'insert_cnp',
                        'motivo' => 'Nueva actividad + restricciones NO OK',
                        'categoria_cnp' => 'Programación',
                        'cnp' => 'Restricciones habilitantes no cumplidas',
                        'detalle' => 'Actividad insertada con CNP: no cumple restricciones habilitantes.',
                    ];
                }
            }
            // Terminada y Actividad Futura → Skip en PG nueva
        }

        // Recargar todos los consecutivos de PS (activos e inactivos) después de las inserciones del paso 2
        $allPsConsecutivos = $this->loadAllPsConsecutivos($dbPrefix, $semana);

        // --- PASO 3: Existe en PG + Existe en PS ---
        foreach ($allPsConsecutivos as $consecutivo) {
            if (!isset($pgIndex[$consecutivo])) {
                continue;
            }

            $pg = $pgIndex[$consecutivo];
            $estado = trim($pg['Estado'] ?? '');
            if ($this->shouldSkip($pg, $estado)) {
                continue;
            }

            $psRecord = $this->getPsRecord($dbPrefix, $semana, $consecutivo);
            if (!$psRecord) {
                continue;
            }

            // REGLA 1: Si es agregada manualmente por el usuario (Activa = 'NA'), se mantiene intacta.
            if ($psRecord['Activa'] === 'NA') {
                continue;
            }

            // REGLA 2: Si fue desprogramada voluntariamente por el usuario
            // (Activa = '0' y tiene una causa CNP que NO es la genérica de restricciones), se mantiene en ese estado.
            if ($psRecord['Activa'] === '0') {
                $cnp = trim($psRecord['CNP'] ?? '');
                if ($cnp !== '' && $cnp !== 'Restricciones habilitantes no cumplidas') {
                    continue;
                }
            }

            $compromiso = $this->getCompromisoFromRecord($psRecord);
            $grupo = $this->classifyState($estado);
            $restriccionesOk = $this->checkRestrictionsMet($pg);
            $estaActiva = $psRecord['Activa'] === '1';
            $reprogramadaPorUsuario = (int) ($psRecord['Reprogramada_Por_Usuario'] ?? 0) === 1;

            $accion = $this->resolvePaso3($grupo, $compromiso, $restriccionesOk, $estaActiva, $reprogramadaPorUsuario);

            if ($accion === 'descomprometer') {
                $this->doDecommit($dbPrefix, $semana, $consecutivo, $pg);
                $this->logAction(
                    $dbPrefix,
                    $semana,
                    $consecutivo,
                    'descomprometer',
                    "Actividad desprogramada: estado {$estado} y restricciones " . ($restriccionesOk ? 'OK' : 'NO OK') . '.',
                    null,
                    null,
                    $batchTime,
                );
                $esRestriccion = ($grupo === 'debe_comprometer' && !$restriccionesOk);
                $log[] = [
                    'consecutivo' => $consecutivo,
                    'accion' => 'descomprometer',
                    'motivo' => "Estado {$grupo} + sin compromiso + restricciones " . ($restriccionesOk ? 'OK' : 'NO OK'),
                    'categoria_cnp' => $esRestriccion ? 'Programación' : null,
                    'cnp' => $esRestriccion ? 'Restricciones habilitantes no cumplidas' : null,
                    'detalle' => "Actividad desprogramada: estado {$estado} y restricciones " . ($restriccionesOk ? 'OK' : 'NO OK') . '.',
                ];
            } elseif ($accion === 'comprometer') {
                $this->doComprometer($dbPrefix, $semana, $consecutivo, $pg);
                $this->logAction(
                    $dbPrefix,
                    $semana,
                    $consecutivo,
                    'comprometer',
                    "Actividad reactivada y auto-programada al cumplir restricciones duras y estado propicio.",
                    null,
                    null,
                    $batchTime,
                );
                $log[] = [
                    'consecutivo' => $consecutivo,
                    'accion' => 'comprometer',
                    'motivo' => "Reactivación: restricciones reparadas y OK",
                    'categoria_cnp' => null,
                    'cnp' => null,
                    'detalle' => 'Actividad reactivada y auto-programada al cumplir restricciones duras y estado propicio.',
                ];
            }
        }

        // Sincronizar flags de restricciones
        $this->syncRestrictionFlags($dbPrefix, $semana);

        // Insertar registro de control de esta corrida
        $this->logAction($dbPrefix, $semana, 0, 'comprometer', 'Corrida de control', null, null, $batchTime);

        // Notificar (campana global) si hubo autodesprogramaciones por restricciones duras
        $this->notifyRestrictionCnp($dbPrefix, $semana, $log);

        return $log;
    }

    private function notifyRestrictionCnp(string $dbPrefix, int $semana, array $log): void
    {
        $cnpList = array_values(array_filter($log, static function ($entry) {
            return isset($entry['accion'], $entry['categoria_cnp'])
                && in_array($entry['accion'], ['descomprometer', 'insert_cnp'], true)
                && $entry['categoria_cnp'] === 'Programación';
        }));

        if (empty($cnpList)) {
            return;
        }

        $count = count($cnpList);
        $titulo = NotificationType::getTitle(
            NotificationType::PS_AUTOPROGRAMMED_CNP_RESTRICTION,
            $count,
        );
        $cuerpo = sprintf(
            '%d actividad(es) en la semana %d pasaron a CNP genérica porque no cumplen las restricciones duras (D_y_E, Materiales, MdeO, Equipos, Predecesora). Revisa el módulo CNP.',
            $count,
            $semana,
        );

        try {
            $notificationService = new NotificationService();
            $usersByRole = $notificationService->getUsersByRoleForProject($dbPrefix);
            $notificationService->emitToRoles(
                NotificationType::PS_AUTOPROGRAMMED_CNP_RESTRICTION,
                $cuerpo,
                $usersByRole,
                $dbPrefix,
            );
        } catch (\Throwable $t) {
            error_log('[ProgramChangeDetector] notifyRestrictionCnp failed: ' . $t->getMessage());
        }
    }

    private function getPsRecord(string $dbPrefix, int $semana, int $consecutivo): ?array
    {
        return $this->db->query(
            "SELECT Activa, Categoria_CNP, CNP, Compromiso, Reprogramada_Por_Usuario FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND Consecutivo_En_Programa = ? LIMIT 1",
            [$semana, $consecutivo],
        )->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getCompromisoFromRecord(array $psRecord): ?float
    {
        $val = $psRecord['Compromiso'];
        if ($val === false || $val === null || $val === '') {
            return null;
        }
        $f = (float) $val;
        return $f > 0 ? $f : null;
    }

    private function resolvePaso3(string $grupo, ?float $compromiso, bool $restriccionesOk, bool $estaActiva, bool $reprogramadaPorUsuario): string
    {
        $tieneCompromiso = $compromiso !== null && $compromiso > 0;

        if ($tieneCompromiso) {
            return 'no_tocar';
        }

        // Sin compromiso (NULL o <=0)
        if ($grupo === 'debe_comprometer') {
            if ($restriccionesOk) {
                return $estaActiva ? 'no_tocar' : 'comprometer';
            }
            // Restricciones NO OK: distinguir entre autoprogramada por el sistema
            // y reactivada manualmente por el usuario desde CNP.
            if ($estaActiva && $reprogramadaPorUsuario) {
                // El usuario la reactivó voluntariamente: se respeta la soberanía.
                return 'no_tocar';
            }
            // Autoprogramada por el sistema (o sin flag) y con restricciones pendientes:
            // autodescomprometer con CNP genérica.
            return 'descomprometer';
        }

        if ($grupo === 'terminada') {
            return $estaActiva ? 'descomprometer' : 'no_tocar';
        }

        if ($grupo === 'actividad_futura') {
            if ($restriccionesOk) {
                return $estaActiva ? 'no_tocar' : 'comprometer';
            }
            // Si ya está activa en PS por reprogramación manual del usuario, se respeta su soberanía y se mantiene activa
            return 'no_tocar';
        }

        return 'no_tocar';
    }

    private function shouldSkip(array $pg, string $estado): bool
    {
        if ((int) ($pg['Titulo'] ?? 0) === 1) {
            return true;
        }
        if ($estado === '' || $estado === 'Sin Datos') {
            return true;
        }
        return false;
    }

    private function classifyState(string $estado): string
    {
        if (in_array($estado, self::DEBE_COMPROMETER_STATES, true)) {
            return 'debe_comprometer';
        }
        if (in_array($estado, self::TERMINADA_STATES, true)) {
            return 'terminada';
        }
        if (in_array($estado, self::ACTIVIDAD_FUTURA_STATES, true)) {
            return 'actividad_futura';
        }
        return 'desconocido';
    }


    public function getLog(string $dbPrefix, int $semana): array
    {
        $this->ensureLogTable($dbPrefix);

        $lastBatchTime = $this->db->query(
            "SELECT creado_en FROM {$dbPrefix}_auto_program_log WHERE semana = ? AND consecutivo = 0 ORDER BY id DESC LIMIT 1",
            [$semana],
        )->fetchColumn();

        if (!$lastBatchTime) {
            return [];
        }

        return $this->db->query(
            "SELECT l.*, pc.Id AS actividad_id, pc.Actividad AS actividad_nombre
             FROM {$dbPrefix}_auto_program_log l
             LEFT JOIN {$dbPrefix}_programa_consolidado pc
               ON l.consecutivo = pc.Consecutivo_en_Programa AND l.semana = pc.Semana
             WHERE l.semana = ? AND l.creado_en = ? AND l.consecutivo > 0
             ORDER BY l.id DESC",
            [$semana, $lastBatchTime],
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadAllPsConsecutivos(string $dbPrefix, int $semana): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT Consecutivo_En_Programa FROM {$dbPrefix}_programacion_semanal WHERE Semana = ?",
            [$semana],
        )->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    private function ensureLogTable(string $dbPrefix): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$dbPrefix}_auto_program_log` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `semana` INT NOT NULL,
                `consecutivo` INT NOT NULL,
                `accion` ENUM('comprometer','descomprometer','insert_cnp') NOT NULL,
                `detalle` TEXT,
                `categoria_cnp` VARCHAR(100) DEFAULT NULL,
                `cnp` VARCHAR(100) DEFAULT NULL,
                `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_semana` (`semana`),
                KEY `idx_consecutivo` (`consecutivo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function logAction(string $dbPrefix, int $semana, int $consecutivo, string $accion, string $detalle, ?string $catCnp = null, ?string $cnp = null, ?string $creadoEn = null): void
    {
        if ($creadoEn) {
            $this->db->query(
                "INSERT INTO {$dbPrefix}_auto_program_log (semana, consecutivo, accion, detalle, categoria_cnp, cnp, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$semana, $consecutivo, $accion, $detalle, $catCnp, $cnp, $creadoEn],
            );
        } else {
            $this->db->query(
                "INSERT INTO {$dbPrefix}_auto_program_log (semana, consecutivo, accion, detalle, categoria_cnp, cnp) VALUES (?, ?, ?, ?, ?, ?)",
                [$semana, $consecutivo, $accion, $detalle, $catCnp, $cnp],
            );
        }
    }

    private function doComprometer(string $dbPrefix, int $semana, int $consecutivo, array $pg): void
    {
        $exists = $this->db->query(
            "SELECT COUNT(*) FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND Consecutivo_En_Programa = ?",
            [$semana, $consecutivo],
        )->fetchColumn();

        if ($exists > 0) {
            $this->db->query(
                "UPDATE {$dbPrefix}_programacion_semanal
                 SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 0
                 WHERE Semana = ? AND Consecutivo_En_Programa = ? AND Activa != '1'",
                [$semana, $consecutivo],
            );
            return;
        }

        $subsRaw = $pg['Sub_Contratista'] ?? '';
        $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
        if (empty($subs)) {
            $subs = [''];
        }

        foreach ($subs as $sub) {
            $this->db->query(
                "INSERT INTO {$dbPrefix}_programacion_semanal (
                    Semana, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', ?, ?, ?)",
                [
                    $semana,
                    $consecutivo,
                    $pg['Id'],
                    $pg['Actividad'],
                    $pg['Fecha_Inicio'],
                    $pg['Fecha_Fin'],
                    $sub,
                    $pg['Responsable_AIA'] ?? '',
                    'AIA',
                    (float) ($pg['Ejecutado'] ?? 0),
                    0,
                    (int) ($pg['Ruta_Critica'] ?? 0),
                    in_array($pg['Estado'] ?? '', ['Atrasada', 'Ya Debió Iniciar y Restricciones Pendientes']) ? 1 : 0,
                    $pg['unidad'] ?? '%',
                    (float) ($pg['cantidad_ppto'] ?? 0) > 0 ? (float) $pg['cantidad_ppto'] : null,
                    $pg['codigo_actividad'] ?? null,
                ],
            );
        }
    }

    private function doDecommit(string $dbPrefix, int $semana, int $consecutivo, array $pg): void
    {
        $estado = trim($pg['Estado'] ?? '');
        $grupo = $this->classifyState($estado);

        if ($grupo === 'terminada') {
            $this->deleteFromPs($dbPrefix, $semana, $consecutivo);
            return;
        }

        $this->db->query(
            "UPDATE {$dbPrefix}_programacion_semanal
             SET Activa = '0', Categoria_CNP = ?, CNP = ?, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 0
             WHERE Semana = ? AND Consecutivo_En_Programa = ?",
            ['Programación', 'Restricciones habilitantes no cumplidas', $semana, $consecutivo],
        );
    }


    private function doInsertCnp(string $dbPrefix, int $semana, int $consecutivo, array $pg, string $catCnp, string $cnp): void
    {
        $subsRaw = $pg['Sub_Contratista'] ?? '';
        $subs = array_filter(array_map('trim', explode(',', $subsRaw)));
        if (empty($subs)) {
            $subs = [''];
        }

        foreach ($subs as $sub) {
            $this->db->query(
                "INSERT INTO {$dbPrefix}_programacion_semanal (
                    Semana, Consecutivo_En_Programa, Id, Actividad, Fecha_Inicio, Fecha_Fin,
                    Sub_Contratista, Responsable_AIA, Empresa, Ejecutado, medir_productividad,
                    Critica, Atrasada, Activa, Unidad, cantidad_ppto, codigo_actividad,
                    Categoria_CNP, CNP
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', ?, ?, ?, ?, ?)",
                [
                    $semana,
                    $consecutivo,
                    $pg['Id'],
                    $pg['Actividad'],
                    $pg['Fecha_Inicio'],
                    $pg['Fecha_Fin'],
                    $sub,
                    $pg['Responsable_AIA'] ?? '',
                    'AIA',
                    (float) ($pg['Ejecutado'] ?? 0),
                    0,
                    (int) ($pg['Ruta_Critica'] ?? 0),
                    in_array($pg['Estado'] ?? '', ['Atrasada', 'Ya Debió Iniciar y Restricciones Pendientes']) ? 1 : 0,
                    $pg['unidad'] ?? '%',
                    (float) ($pg['cantidad_ppto'] ?? 0) > 0 ? (float) $pg['cantidad_ppto'] : null,
                    $pg['codigo_actividad'] ?? null,
                    $catCnp,
                    $cnp,
                ],
            );
        }
    }

    private function deleteFromPs(string $dbPrefix, int $semana, int $consecutivo): void
    {
        $this->db->query(
            "DELETE FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND Consecutivo_En_Programa = ?",
            [$semana, $consecutivo],
        );
    }

    private function loadProgramaConsolidado(string $dbPrefix, int $semana): array
    {
        return $this->db->query(
            "SELECT * FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? ORDER BY Consecutivo_en_Programa ASC",
            [$semana],
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadPsConsecutivos(string $dbPrefix, int $semana): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT Consecutivo_En_Programa FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND (Activa = '1' OR Activa = 'NA')",
            [$semana],
        )->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    private function checkRestrictionsMet(array $pg): bool
    {
        // Si la actividad ya tiene avance ejecutado en el Programa General,
        // prima la ejecución real sobre las restricciones duras.
        $ejecutado = (float) ($pg['Ejecutado'] ?? 0);
        if ($ejecutado > 0.001) {
            return true;
        }

        foreach (self::HARD_RESTRICTIONS as $col) {
            $val = trim((string) ($pg[$col] ?? ''));
            if (empty($val)) {
                return false;
            }
            $upper = strtoupper($val);
            if ($upper === 'N/A' || $upper === 'NO APLICA') {
                continue;
            }
            $ratio = $this->parseRestrictionRatio($val);
            if ($ratio === null) {
                return false;
            }
            $threshold = ($col === 'Predecesora') ? 0.5 : 1.0;
            if (($ratio + 0.0001) < $threshold) {
                return false;
            }
        }
        return true;
    }

    private function parseRestrictionRatio($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $raw = trim((string) $value);
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
    }

    private function syncRestrictionFlags(string $dbPrefix, int $semana): void
    {
        $sql = $this->buildEligibilitySql('pc');
        $this->db->query("UPDATE {$dbPrefix}_programacion_semanal ps
            JOIN {$dbPrefix}_programa_consolidado pc
              ON ps.Consecutivo_En_Programa = pc.Consecutivo_en_Programa
             AND ps.Semana = pc.Semana
            SET ps.Prog_Sin_Restricciones_100 = (CASE WHEN {$sql} THEN 0 ELSE 1 END)
            WHERE ps.Semana = ? AND ps.Activa != 'NA'", [$semana]);

        $this->db->query("UPDATE {$dbPrefix}_programacion_semanal SET Prog_Sin_Restricciones_100 = 0 WHERE Semana = ? AND Activa = 'NA'", [$semana]);
    }

    private function buildEligibilitySql(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        return '(' . implode(' AND ', [
            $this->restrictionSql($prefix . 'D_y_E', 1.0),
            $this->restrictionSql($prefix . 'Materiales', 1.0),
            $this->restrictionSql($prefix . 'MdeO', 1.0),
            $this->restrictionSql($prefix . 'Equipos', 1.0),
            $this->restrictionSql($prefix . 'Predecesora', 0.5),
        ]) . ')';
    }

    private function restrictionSql(string $column, float $minimumRatio): string
    {
        $text = "TRIM(COALESCE({$column}, ''))";
        $compact = "REPLACE({$text}, ' ', '')";
        $numeric = "CAST(REPLACE(REPLACE({$compact}, '%', ''), ',', '.') AS DECIMAL(10,5))";
        $normalized = "(CASE WHEN LOCATE('%', {$compact}) > 0 THEN {$numeric} / 100 WHEN {$numeric} > 1 AND {$numeric} <= 10000 THEN {$numeric} / 100 ELSE {$numeric} END)";
        $threshold = number_format($minimumRatio, 5, '.', '');

        return "(UPPER({$text}) IN ('N/A', 'NO APLICA') OR {$normalized} >= {$threshold})";
    }
}
