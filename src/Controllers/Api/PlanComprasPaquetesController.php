<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PaquetesService;

/**
 * Paquetes de contratación (PDC v2 / Fase A3).
 * Lectura: lps.paquetes_contratacion.ver. Escritura: lps.paquetes_contratacion.editar + CSRF plan_compras_v2.
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasPaquetesController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private PaquetesService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new PaquetesService($this->db);
    }

    /** GET /plan-compras/api/paquetes?busqueda= */
    public function catalogo(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $busqueda = isset($_GET['busqueda']) && is_string($_GET['busqueda']) ? $_GET['busqueda'] : null;
        $this->ok(['paquetes' => $this->service->catalogo($busqueda)]);
    }

    /** GET /plan-compras/api/paquetes/insumos?filtro=&versionId= */
    public function insumos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $filtro = in_array($_GET['filtro'] ?? '', ['sin_asignar', 'asignados', 'omitidos', 'todos'], true)
            ? (string) $_GET['filtro'] : 'todos';
        $r = $this->service->insumosDeVersion($projectId, $filtro, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** GET /plan-compras/api/paquetes/sugerencias?versionId= */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $r = $this->service->sugerencias($projectId, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** GET /plan-compras/api/paquetes/candidatos?paqueteId=&tipoRecurso=&versionId= */
    public function candidatos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $paqueteId = filter_var($_GET['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $tipoRecurso = isset($_GET['tipoRecurso']) && is_string($_GET['tipoRecurso']) && $_GET['tipoRecurso'] !== ''
            ? $_GET['tipoRecurso'] : null;
        $r = $this->service->candidatosParaPaquete($projectId, (int) $paqueteId, $tipoRecurso, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** GET /plan-compras/api/paquetes/resumen?versionId= */
    public function resumen(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $r = $this->service->resumen($projectId, $this->versionIdParam());
        if ($r === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado.', 404);
            return;
        }
        $this->ok($r);
    }

    /** POST /plan-compras/api/paquetes  {nombre, tipoNegociacion} */
    public function crear(): void
    {
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = $this->body();
        $nombre = is_string($body['nombre'] ?? null) ? $body['nombre'] : '';
        $tipo = is_string($body['tipoNegociacion'] ?? null) ? $body['tipoNegociacion'] : '';
        $r = $this->service->crearPaquete($nombre, $tipo, $this->usuario());
        if (!$r['ok']) {
            $this->fail('PAQUETE_INVALIDO', 'Nombre vacío o tipo de negociación inválido.', 422);
            return;
        }
        $this->ok(['paquete' => $r['paquete']]);
    }

    /** POST /plan-compras/api/paquetes/asignar  {insumos:[{descripcionNorm,unidad}], paqueteId} */
    public function asignar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $insumos = is_array($body['insumos'] ?? null) ? $body['insumos'] : [];
        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $r = $this->service->asignar($projectId, $insumos, (int) $paqueteId, $this->usuario());
        if (!$r['ok']) {
            $this->fail('PAQUETE_INVALIDO', 'El paquete no existe o está inactivo.', 422);
            return;
        }
        $this->ok(['asignados' => $r['asignados']]);
    }

    /** POST /plan-compras/api/paquetes/omitir  {insumos:[{descripcionNorm,unidad}]} */
    public function omitir(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $insumos = is_array($body['insumos'] ?? null) ? $body['insumos'] : [];
        $r = $this->service->omitir($projectId, $insumos, $this->usuario());
        $this->ok(['omitidos' => $r['omitidos']]);
    }

    /** POST /plan-compras/api/paquetes/desasignar  {insumos:[...]} */
    public function desasignar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $insumos = is_array($body['insumos'] ?? null) ? $body['insumos'] : [];
        $r = $this->service->desasignar($projectId, $insumos);
        $this->ok(['desasignados' => $r['desasignados']]);
    }

    // ── guards ──────────────────────────────────────────────

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para ver paquetes de contratación.', 403);
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
            $this->fail('FORBIDDEN', 'No autorizado para editar paquetes de contratación.', 403);
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

    /** ?versionId=N validado, o null (el servicio usa la versión activa). */
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
