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
     * @param bool   $allowIfConfirmed Si true, permite el pase aunque la semana esté confirmada
     *                                 (pensado para operaciones que siempre deben permitirse,
     *                                 como registrar avance Real) — pero sigue exigiendo que el
     *                                 proyecto se resuelva y que el rol actual esté habilitado;
     *                                 ya no es un pase libre sin comprobar nada.
     *
     * @return void Si el proyecto no se resuelve, si la semana está bloqueada, o si
     *              allowIfConfirmed=true y el rol no está habilitado, emite HTTP 409 JSON
     *              y termina la ejecución.
     */
    public static function guard(string $dbPrefix, int $semana, string $operacion, bool $allowIfConfirmed = false): void
    {
        $db = \Database::getInstance();
        $projectId = \TableResolver::getProjectIdByPrefix($dbPrefix);

        if (!$projectId) {
            // Antes se dejaba pasar la mutación "por seguridad" cuando no podíamos resolver
            // el proyecto. Cambio deliberado (2026-08-10, Task 4): un candado que no sabe,
            // cierra — misma decisión que ya aplicaba LpsWeekEditPolicy::allows() y que
            // Task 3 fijó primero en SemanalReabrirPolicy. Dejar pasar aquí era la mitad
            // abierta del mismo candado que en la otra pieza cierra.
            self::deny($operacion, $semana, $projectId, 'no se pudo resolver el proyecto');
        }

        if ($allowIfConfirmed) {
            // "allowIfConfirmed" solo bypassea el bloqueo por Semanal_Confirmada=1, no la
            // autorización de base: sigue exigiendo un rol habilitado para tocar la semana.
            // Antes retornaba aquí sin comprobar nada — pase libre para cualquier llamada
            // futura que lo activara. Ninguna de las nueve llamadas actuales lo usa (2026-08-10).
            $role = (new \App\Security\RbacService($db))->resolveCurrentRole();
            if (!\App\Security\RbacCatalog::canQualifyWeeklyCommitment($role)) {
                self::deny($operacion, $semana, $projectId, "rol '{$role}' sin autorización");
            }

            return;
        }

        $tSemanas = \TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
        $stmt = $db->queryWithProject(
            "SELECT Semanal_Confirmada FROM {$tSemanas} WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId
        );
        $confirmed = (int) ($stmt->fetchColumn() ?: 0);

        if ($confirmed === 1) {
            self::deny($operacion, $semana, $projectId, 'los compromisos de la semana ya fueron confirmados');
        }
    }

    /**
     * Registra el bloqueo y termina la ejecución con HTTP 409.
     *
     * @return never
     */
    private static function deny(string $operacion, int $semana, ?int $projectId, string $motivo): void
    {
        $db = \Database::getInstance();
        $usuario = $_SESSION['usuario'] ?? $_SESSION['admin_user']['usuario'] ?? 'Sistema';
        $db->logActivity(
            'ProgramacionSemanal',
            'BLOQUEO_' . strtoupper($operacion),
            "Intento de {$operacion} en semana {$semana} bloqueado ({$motivo}). Usuario: {$usuario}",
            $projectId
        );

        http_response_code(409);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "respuesta" => "ERROR",
            "mensaje" => "No se puede realizar '{$operacion}': {$motivo}.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}