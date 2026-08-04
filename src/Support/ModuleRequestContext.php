<?php

namespace App\Support;

use RuntimeException;

final class ModuleRequestContext
{
    public static function resolve(array $options = []): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $allowZeroWeek = (bool) ($options['allow_zero_week'] ?? true);
        $syncSession = (bool) ($options['sync_session'] ?? true);

        $requestDb = trim((string) ($_GET['db'] ?? ($_POST['db'] ?? '')));
        $sessionDb = trim((string) ($_SESSION['db'] ?? ''));

        if ($requestDb !== '' && !self::isValidDbPrefix($requestDb)) {
            self::logWarning('Base de datos solicitada inválida; se ignorará el valor recibido por request.', [
                'request_db' => $requestDb,
                'session_db' => $sessionDb,
            ]);
            $requestDb = '';
        }

        if ($sessionDb !== '' && !self::isValidDbPrefix($sessionDb)) {
            throw new RuntimeException('Base de datos inválida o sesión expirada.');
        }

        if ($requestDb !== '' && $sessionDb !== '' && $requestDb !== $sessionDb) {
            self::logWarning('Conflicto de contexto detectado en la base de datos; se usará la sesión activa.', [
                'request_db' => $requestDb,
                'session_db' => $sessionDb,
            ]);
        }

        $dbPrefix = $sessionDb !== '' ? $sessionDb : $requestDb;
        if ($dbPrefix === '' || !self::isValidDbPrefix($dbPrefix)) {
            throw new RuntimeException('Base de datos inválida o sesión expirada.');
        }

        if ($syncSession && $sessionDb === '' && $requestDb !== '' && $dbPrefix === $requestDb) {
            $_SESSION['db'] = $dbPrefix;
        }

        $rawRequestWeek = $_GET['semana'] ?? ($_POST['semana'] ?? null);
        $requestWeek = self::parseWeek($rawRequestWeek, $allowZeroWeek);
        $sessionWeek = self::parseWeek($_SESSION['semana'] ?? null, $allowZeroWeek);

        if ($rawRequestWeek !== null && $rawRequestWeek !== '' && $requestWeek === null) {
            self::logWarning('Semana solicitada inválida; se ignorará el valor recibido por request.', [
                'request_week' => $rawRequestWeek,
                'session_week' => $_SESSION['semana'] ?? null,
            ]);
        }

        if ($requestWeek !== null && $sessionWeek !== null && $requestWeek !== $sessionWeek) {
            self::logWarning('Conflicto de contexto detectado en la semana; se usará la sesión activa.', [
                'request_week' => $requestWeek,
                'session_week' => $sessionWeek,
            ]);
        }

        $semana = $sessionWeek ?? $requestWeek ?? ($allowZeroWeek ? 0 : null);

        // Esta escritura sí puede quedarse: solo corre cuando la sesión NO trae semana (arranque de
        // contexto para las APIs), nunca cuando ya hay una. Como más arriba `$semana` prioriza siempre
        // `$sessionWeek` sobre `$requestWeek`, esta asignación no puede pisar una selección posterior
        // del usuario — que es justo el fallo que esta rama corrigió al sacar la escritura incondicional
        // de SessionMiddleware.
        if ($syncSession && $sessionWeek === null && $requestWeek !== null) {
            $_SESSION['semana'] = $requestWeek;
            $semana = $requestWeek;
        }

        if (!$allowZeroWeek && ($semana === null || $semana <= 0)) {
            throw new RuntimeException('Semana inválida o sesión expirada.');
        }

        $projectId = self::resolveProjectId($dbPrefix);

        return [
            'dbPrefix' => $dbPrefix,
            'projectId' => $projectId,
            'semana' => (int) ($semana ?? 0),
            'sessionDb' => $sessionDb,
            'sessionWeek' => $sessionWeek,
            'requestDb' => $requestDb,
            'requestWeek' => $requestWeek,
        ];
    }

    private static function parseWeek($value, bool $allowZeroWeek): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $options = ['options' => ['min_range' => $allowZeroWeek ? 0 : 1]];
        $parsed = filter_var($value, FILTER_VALIDATE_INT, $options);

        if ($parsed === false) {
            return null;
        }

        return (int) $parsed;
    }

    private static function isValidDbPrefix(string $value): bool
    {
        return $value !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $value) === 1;
    }

    private static function resolveProjectId(string $dbPrefix): int
    {
        $sessionProjectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($sessionProjectId > 0) {
            return $sessionProjectId;
        }

        $db = \Database::getInstance();
        $stmt = $db->query(
            "SELECT Id FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1",
            [$dbPrefix],
        );
        $projectId = (int) ($stmt->fetchColumn() ?: 0);
        if ($projectId <= 0) {
            throw new RuntimeException('Proyecto inválido o sesión expirada.');
        }

        $_SESSION['project_id'] = $projectId;

        return $projectId;
    }

    private static function logWarning(string $message, array $context = []): void
    {
        $payload = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        error_log('[ModuleRequestContext] ' . $message . $payload);
    }
}
