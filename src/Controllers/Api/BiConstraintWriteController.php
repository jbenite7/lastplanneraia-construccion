<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\ErrorPage;
use App\Security\CsrfTokenManager;
use App\Security\RbacCatalog;
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
            ErrorPage::render(
                403,
                'Acceso denegado',
                'Tu rol no tiene permiso para gestionar restricciones.'
            );
            exit;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY)) {
            ErrorPage::render(
                403,
                'Token de seguridad inválido',
                'El token de seguridad expiró o no es válido. Recarga la página e intenta de nuevo.'
            );
            exit;
        }

        $constraintId = filter_var($id, FILTER_VALIDATE_INT);
        $projectId = (int) ($_SESSION['project_id'] ?? 0);

        if ($constraintId === false || $projectId <= 0) {
            $this->notFound();
        }

        $existe = $this->db->query(
            'SELECT Id FROM pi_shared_constraints WHERE project_id = ? AND Id = ? LIMIT 1',
            [$projectId, $constraintId]
        )->fetch(PDO::FETCH_ASSOC);

        if ($existe === false) {
            // El aislamiento entre proyectos nunca revela si el id existe en otro project_id:
            // misma respuesta que un id que no existe en absoluto.
            $this->notFound();
        }

        $body = json_decode((string) file_get_contents('php://input'), true);
        [$responsable, $fechaCompromiso, $estado, $errorValidacion] = $this->validarBody(
            is_array($body) ? $body : []
        );

        if ($errorValidacion !== null) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['ok' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => $errorValidacion]],
                JSON_UNESCAPED_UNICODE
            );
            exit;
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

    private function notFound(): never
    {
        ErrorPage::render(
            404,
            'Esta página no existe',
            'La dirección que abriste no corresponde a ninguna pantalla del producto.'
        );
        exit;
    }

    /**
     * Mismo patrón que `BiPreviewAccessPolicy::resolveRole()` (privado allá, así que se
     * replica aquí): usuario de sesión resuelto vía `RbacService::resolveRoleForUser()`
     * cuando hay `$_SESSION['usuario']`, o `permiso` de sesión normalizado si no.
     */
    private function resolveRole(): string
    {
        $username = trim((string) (
            $_SESSION['usuario'] ?? ($_SESSION['admin_user']['usuario'] ?? '')
        ));

        if ($username !== '') {
            try {
                return (new RbacService())->resolveRoleForUser($username);
            } catch (\Throwable) {
                return RbacCatalog::DEFAULT_ROLE;
            }
        }

        return (new RbacService())->normalizeRole(
            (string) ($_SESSION['permiso'] ?? ($_SESSION['admin_user']['permiso'] ?? RbacCatalog::DEFAULT_ROLE))
        );
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
