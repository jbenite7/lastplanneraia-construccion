<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Security\RbacManager;
use App\Security\RbacService;
use PDO;

/**
 * La Torre escribe: un director/residente asigna responsable, fecha de compromiso y estado
 * de liberación a una restricción de `pi_shared_constraints` sin salir de la hoja de
 * Intermedia. Task 5 del plan `2026-08-26-ola1-torre-etapa-piloto`.
 *
 * `POST /api/bi/control-tower/restricciones/{id}/gestion`. Lo consume tal cual
 * `ct-app/src/lib/api.ts::postGestionRestriccion()` (Task 6, ya commiteado) y lo integrará
 * Task 7 — el contrato de este archivo (ruta, CSRF, forma del envelope) no se puede romper.
 *
 * A propósito NO reutiliza `BiPreviewAccessPolicy::canOpen()` (el gate del constructor de su
 * hermano `BiControlTowerApiController`): ese gate excluye el rol V devolviendo 404, y este
 * endpoint necesita **403** para un intento de escritura de un rol sin capacidad — 404 se
 * reserva únicamente para el aislamiento entre proyectos (un `id` de otro `project_id` nunca
 * debe revelar que existe en otro lado).
 */
class BiConstraintWriteController extends BaseController
{
    private const CSRF_FORM_KEY = 'ct_piloto';

    private const ESTADOS_VALIDOS = ['sin_gestionar', 'en_gestion', 'liberada', 'no_aplica'];

    public function gestion(string $id): void
    {
        $this->requireAuth();

        $role = $this->resolveRole();
        if (!RbacManager::hasCapability($role, 'canEditConstraints')) {
            $this->fallar(403, 'FORBIDDEN', 'Tu rol no tiene permiso para gestionar restricciones.');
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY)) {
            $this->fallar(
                403,
                'CSRF_INVALID',
                'El token de seguridad expiró o no es válido. Recarga la página e intenta de nuevo.'
            );
        }

        $constraintId = filter_var($id, FILTER_VALIDATE_INT);
        $projectId = (int) ($_SESSION['project_id'] ?? 0);

        if ($constraintId === false || $projectId <= 0) {
            $this->fallar(404, 'NOT_FOUND', 'La restricción solicitada no existe.');
        }

        $existe = $this->db->query(
            'SELECT Id FROM pi_shared_constraints WHERE project_id = ? AND Id = ? LIMIT 1',
            [$projectId, $constraintId]
        )->fetch(PDO::FETCH_ASSOC);

        if ($existe === false) {
            // El aislamiento entre proyectos nunca revela si el id existe en otro project_id:
            // misma respuesta que un id que no existe en absoluto.
            $this->fallar(404, 'NOT_FOUND', 'La restricción solicitada no existe.');
        }

        $body = json_decode((string) file_get_contents('php://input'), true);
        [$responsable, $fechaCompromiso, $estado, $errorValidacion] = $this->validarBody(
            is_array($body) ? $body : []
        );

        if ($errorValidacion !== null) {
            $this->fallar(422, 'VALIDATION_ERROR', $errorValidacion);
        }

        $usuario = (string) ($_SESSION['usuario'] ?? '');

        // CT-10 es taxativo: nada más que responsable, fecha y estado puede escribirse aquí.
        $this->db->query(
            'UPDATE pi_shared_constraints
             SET ResponsableAsignado = ?, FechaCompromiso = ?, EstadoLiberacion = ?,
                 AsignadoPor = ?, AsignadoEn = NOW()
             WHERE project_id = ? AND Id = ?',
            [$responsable, $fechaCompromiso, $estado, $usuario, $projectId, $constraintId]
        );

        // Lectura fresca e independiente del UPDATE — nunca se responde con el payload enviado.
        $restriccion = $this->db->query(
            'SELECT Id, Semana, Restriccion, ValorObjetivo, Nota, ResponsableAsignado,
                    FechaCompromiso, EstadoLiberacion, AsignadoPor, AsignadoEn,
                    CreadoPor, CreadoEn, ActualizadoEn
             FROM pi_shared_constraints WHERE project_id = ? AND Id = ?',
            [$projectId, $constraintId]
        )->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'restriccion' => $restriccion], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envelope de error unificado: `{ok:false, error:{code, message}}` para los 4 sitios de
     * error de este controlador (403 capacidad, 403 CSRF, 404 aislamiento, 422 validación).
     * `ct-app/src/lib/api.ts` exige `body.ok` booleano o lanza `BAD_RESPONSE` genérico — el
     * cuerpo de `ErrorPage::render()` (`{"error":{"codigo","mensaje"}}`, sin `ok`) rompía ese
     * contrato para un rol denegado o un CSRF vencido.
     */
    private function fallar(int $status, string $code, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['ok' => false, 'error' => ['code' => $code, 'message' => $message]],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    /**
     * Rol del usuario de sesión, acotado al proyecto activo (`$_SESSION['proyecto']` /
     * `$_SESSION['db']`) — no el rol más privilegiado que el usuario tenga en cualquier
     * proyecto. `RbacService::resolveCurrentRole()` es la convención real de escritura del
     * repo para esto (`CicApiController.php`, `GeneralApiController.php`,
     * `LpsWeekEditPolicy.php`, `CommitmentLockGuard.php`).
     *
     * Corregido en ronda 1 de revisión (Critical 1): la versión anterior llamaba
     * `resolveRoleForUser($username)` SIN `$projectName`/`$dbName`, y
     * `resolveRoleFromProjectMembers()` sin esos argumentos ordena por
     * `FIELD(pm.role,'A','D','R',...)` y devuelve el rol MÁS PRIVILEGIADO del usuario en
     * CUALQUIER proyecto — no su rol en el proyecto de la sesión activa. Confirmado con dato
     * real de dev: `tomas.trujillo` es `V` en `project_id=73` (el proyecto fixture de este
     * mismo test) y `A` en otros 13 proyectos; la versión vieja le habría dado paso a escribir
     * sobre el proyecto 73, donde en realidad es Visualizador.
     */
    private function resolveRole(): string
    {
        return (new RbacService($this->db))->resolveCurrentRole();
    }

    /**
     * Campos permitidos SOLO responsable/fechaCompromiso/estado (CT-10, taxativo).
     *
     * @param array<string,mixed> $body
     * @return array{0:?string,1:?string,2:?string,3:?string}
     */
    private function validarBody(array $body): array
    {
        $responsable = $body['responsable'] ?? null;
        $fechaCompromiso = $body['fechaCompromiso'] ?? null;
        $estado = $body['estado'] ?? null;

        if (!is_string($responsable) || trim($responsable) === '') {
            return [null, null, null, 'El responsable es obligatorio.'];
        }

        if (!is_string($fechaCompromiso) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCompromiso)) {
            return [null, null, null, 'La fecha de compromiso debe tener formato YYYY-MM-DD.'];
        }
        $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaCompromiso);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaCompromiso) {
            return [null, null, null, 'La fecha de compromiso no es una fecha válida.'];
        }

        if (!is_string($estado) || !in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return [null, null, null, 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_VALIDOS) . '.'];
        }

        return [trim($responsable), $fechaCompromiso, $estado, null];
    }
}
