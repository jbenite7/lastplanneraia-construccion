<?php

/**
 * Guard centralizado que valida que una semana no tenga compromisos confirmados
 * antes de permitir operaciones de escritura en PG, PI y PS.
 *
 * Uso:
 *   CommitmentLockGuard::guard($dbPrefix, $semana, 'autoprogramar');
 *   // Si Semanal_Confirmada=1, responde 409 y hace exit.
 */
class CommitmentLockGuard
{
    /**
     * Valida que la semana NO esté bloqueada por compromisos confirmados.
     *
     * @param string $dbPrefix Prefijo de base de datos del proyecto.
     * @param int    $semana   Número de semana a validar.
     * @param string $operacion Nombre corto de la operación (ej: 'autoprogramar', 'modificar').
     * @param bool   $allowIfConfirmed Si true, permite el pase incluso si está confirmada
     *                                 (usado para operaciones que siempre deben permitirse,
     *                                 como registrar avance Real).
     *
     * @return void Si la semana está bloqueada, emite HTTP 409 JSON y termina la ejecución.
     */
    public static function guard(string $dbPrefix, int $semana, string $operacion, bool $allowIfConfirmed = false): void
    {
        if ($allowIfConfirmed) {
            return;
        }

        $db = \Database::getInstance();
        $projectId = \TableResolver::getProjectIdByPrefix($dbPrefix);

        if (!$projectId) {
            return; // Sin proyecto, no podemos validar — se deja pasar por seguridad
        }

        $tSemanas = \TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
        $stmt = $db->queryWithProject(
            "SELECT Semanal_Confirmada FROM {$tSemanas} WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId
        );
        $confirmed = (int) ($stmt->fetchColumn() ?: 0);

        if ($confirmed === 1) {
            $usuario = $_SESSION['usuario'] ?? $_SESSION['admin_user']['usuario'] ?? 'Sistema';
            $db->logActivity(
                'ProgramacionSemanal',
                'BLOQUEO_' . strtoupper($operacion),
                "Intento de {$operacion} en semana {$semana} bloqueada (Semanal_Confirmada=1). Usuario: {$usuario}",
                $projectId
            );

            http_response_code(409);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                "respuesta" => "ERROR",
                "mensaje" => "No se puede realizar '{$operacion}': los compromisos de la semana {$semana} ya fueron confirmados.",
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}