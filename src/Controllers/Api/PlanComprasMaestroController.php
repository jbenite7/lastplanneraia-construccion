<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\MaestroInsumosService;

/**
 * Endpoints del maestro global de insumos (PDC v2 / Fase A2).
 * Lectura: lps.pdc.ver. Escritura: lps.pdc.maestro + CSRF plan_compras_v2.
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasMaestroController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private MaestroInsumosService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new MaestroInsumosService($this->db);
    }

    /** GET /plan-compras/api/maestro?busqueda= */
    public function catalogo(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $busqueda = isset($_GET['busqueda']) ? (string) $_GET['busqueda'] : null;
        $this->ok(['insumos' => $this->service->catalogo($busqueda)]);
    }

    /** GET /plan-compras/api/maestro/vinculos[?versionId=N] */
    public function vinculos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $v = $this->service->vinculos($projectId, $this->versionIdParam());
        if ($v === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($v);
    }

    /** GET /plan-compras/api/maestro/sugerencias?vinculoId=N */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $vinculoId = filter_var($_GET['vinculoId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($vinculoId === false || $vinculoId === null) {
            $this->fail('VINCULO_INVALIDO', 'vinculoId inválido.', 422);
            return;
        }
        $this->ok(['sugerencias' => $this->service->sugerencias($projectId, $vinculoId)]);
    }

    /** POST /plan-compras/api/maestro/vinculos/generar {versionId?} */
    public function generar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $versionId = isset($body['versionId']) ? (int) $body['versionId'] : null;
        $r = $this->service->generarVinculos($projectId, $versionId !== null && $versionId > 0 ? $versionId : null);
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** POST /plan-compras/api/maestro/vinculos/confirmar {vinculoId, maestroId} */
    public function confirmar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->vincular($projectId, (int) ($body['vinculoId'] ?? 0), (int) ($body['maestroId'] ?? 0));
        if (!$r['ok']) {
            $this->fail('VINCULO_INVALIDO', 'El vínculo o el insumo del maestro no existen.', 422);
            return;
        }
        $this->ok(['confirmado' => 1]);
    }

    /** POST /plan-compras/api/maestro/crear-desde-pendientes {vinculoIds:[...]} */
    public function crearDesdePendientes(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ids = is_array($body['vinculoIds'] ?? null) ? $body['vinculoIds'] : [];
        $ids = array_values(array_filter(
            $ids,
            static fn ($v): bool => (is_int($v) && $v > 0) || (is_string($v) && ctype_digit($v)),
        ));
        $r = $this->service->crearDesdePendientes($projectId, $ids, $this->usuario());
        $vin = $this->service->vinculos($projectId);
        $this->ok(['creados' => $r['creados'], 'vinculados' => $r['vinculados'], 'resumen' => $vin['resumen'] ?? null]);
    }

    /** POST /plan-compras/api/maestro {descripcion,unidad,tipoInsumo} */
    public function crearManual(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->crearManual(
            $projectId,
            (string) ($body['descripcion'] ?? ''),
            (string) ($body['unidad'] ?? ''),
            (string) ($body['tipoInsumo'] ?? ''),
            $this->usuario(),
        );
        if (!$r['ok']) {
            if ($r['code'] === 'MAESTRO_DUPLICADO') {
                $this->fail('MAESTRO_DUPLICADO', 'Ya existe un insumo con esa descripción y unidad en el maestro.', 409);
            } else {
                $this->fail('VINCULO_INVALIDO', 'Descripción y unidad son obligatorias.', 422);
            }
            return;
        }
        $this->ok(['id' => $r['id']]);
    }

    // ── guards ──────────────────────────────────────────────

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
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
        if (!(new RbacService($this->db))->can('lps.pdc.maestro')) {
            $this->fail('FORBIDDEN', 'No autorizado para administrar el maestro de insumos.', 403);
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

    /** ?versionId=N validado, o null si ausente/inválido (deja que el servicio use la versión activa). */
    private function versionIdParam(): ?int
    {
        $versionId = filter_var($_GET['versionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $versionId === false || $versionId === null ? null : $versionId;
    }

    private function body(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }
}
