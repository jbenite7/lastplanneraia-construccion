<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\SeguimientoService;

/**
 * Seguimiento al plan de compras (PDC v2 / Fase B1).
 *
 * Mismo RBAC que el plan —lectura `lps.paquetes_contratacion.ver`, escritura
 * `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`—: quien puede ver y editar el plan de
 * compras es exactamente quien opera su seguimiento, y un permiso propio solo añadiria una matriz
 * mas que alguien tendria que mantener alineada.
 *
 * Sesion garantizada por SessionMiddleware global.
 */
class PlanComprasSeguimientoController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private SeguimientoService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new SeguimientoService($this->db);
    }

    /** GET /plan-compras/api/seguimiento */
    public function resumen(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok([
            'resumen' => $this->service->resumen($projectId),
            // El tablero necesita poder decir «esto se calculó contra un cronograma viejo». Va como
            // lista aparte y no como columna del resumen: es una propiedad del amarre, no del avance.
            'desactualizados' => $this->service->paquetesDesactualizados($projectId),
        ]);
    }

    /** GET /plan-compras/api/seguimiento/paquete?paqueteId=N */
    public function paquete(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $paqueteId = filter_var($_GET['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $this->ok(['pasos' => $this->service->pasosDePaquete($projectId, $paqueteId)]);
    }

    /** POST /plan-compras/api/seguimiento/paso  {paqueteId, pasoId, fechaReal} — null deshace el registro */
    public function paso(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();

        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $pasoId = filter_var($body['pasoId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($pasoId === false) {
            $this->fail('PASO_INVALIDO', 'pasoId inválido.', 422);
            return;
        }

        // `null` es un valor legitimo —deshacer un registro equivocado—, asi que se distingue de
        // «vino cualquier otra cosa». Una cadena vacia cuenta como null: es lo que manda un campo de
        // fecha que el usuario borro.
        $crudo = $body['fechaReal'] ?? null;
        if ($crudo === null || $crudo === '') {
            $fechaReal = null;
        } elseif (is_string($crudo)) {
            $fechaReal = $crudo;
        } else {
            $this->fail('FECHA_INVALIDA', 'fechaReal debe ser una fecha AAAA-MM-DD o null.', 422);
            return;
        }

        $r = $this->service->registrarPaso($projectId, $paqueteId, $pasoId, $fechaReal, $this->usuario());
        if ($r['ok'] !== true) {
            $this->fail($r['code'] ?? 'ERROR', $r['mensaje'] ?? 'No se pudo registrar el avance.', 422);
            return;
        }

        $this->ok(['ok' => true]);
    }

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para ver el seguimiento del plan de compras.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        return $projectId;
    }

    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.editar')) {
            $this->fail('FORBIDDEN', 'No autorizado para registrar avance del plan de compras.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return null;
        }
        return $projectId;
    }

    /** @return array<string, mixed> */
    private function body(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }
}
