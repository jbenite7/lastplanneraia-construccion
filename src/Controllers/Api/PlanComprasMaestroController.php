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
        $incluirInactivos = ($_GET['incluirInactivos'] ?? '') === '1';
        $this->ok(['insumos' => $this->service->catalogo($busqueda, $incluirInactivos)]);
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
        if ($vinculoId === false) {
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

    /** POST /plan-compras/api/maestro/desactivar {maestroId} */
    public function desactivar(): void
    {
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->desactivar((int) ($body['maestroId'] ?? 0), $this->usuario());
        if (!$r['ok']) {
            $this->fail('MAESTRO_INVALIDO', 'El insumo no existe o ya está retirado.', 422);
            return;
        }
        $this->ok(['revertidos' => $r['revertidos']]);
    }

    /** POST /plan-compras/api/maestro/reactivar {maestroId} */
    public function reactivar(): void
    {
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = $this->body();
        $r = $this->service->reactivar((int) ($body['maestroId'] ?? 0), $this->usuario());
        if (!$r['ok']) {
            $this->fail('MAESTRO_INVALIDO', 'El insumo no existe o ya está activo.', 422);
            return;
        }
        // `reenganchados` viaja porque es lo que el usuario no puede deducir: devolver un insumo al
        // catálogo puede resolver vínculos pendientes de cualquier obra, y sin este número la cola
        // baja sola sin que nadie sepa por qué.
        $this->ok(['reactivado' => 1, 'reenganchados' => $r['reenganchados']]);
    }

    /** GET /plan-compras/api/maestro/equipos[?q=] — la cola de equipos sin clasificar (Ola 2). */
    public function equipos(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $q = isset($_GET['q']) ? (string) $_GET['q'] : null;
        $this->ok($this->service->equiposSinClasificar($q));
    }

    /**
     * POST /plan-compras/api/maestro/equipos/clasificar {ids:[...], destino:"..."}
     *
     * Escritura sobre el maestro GLOBAL: pasa por `guardEscritura()`, que exige `lps.pdc.maestro`
     * —capacidad de administración— y no una capacidad de obra. Clasificar aquí cambia el dato para
     * todos los proyectos de AIA.
     */
    public function clasificarEquipos(): void
    {
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = $this->body();
        $ids = is_array($body['ids'] ?? null) ? $body['ids'] : [];
        $ids = array_values(array_filter(
            $ids,
            static fn ($v): bool => (is_int($v) && $v > 0) || (is_string($v) && ctype_digit($v)),
        ));
        $destino = is_string($body['destino'] ?? null) ? $body['destino'] : '';

        $r = $this->service->clasificarEquipos($ids, $destino, $this->usuario());
        if ($r['ok'] !== true) {
            $mensaje = $r['code'] === 'SIN_IDS'
                ? 'No se seleccionó ningún equipo.'
                : 'El destino debe ser equipo alquilado o equipo comprado.';
            $this->fail($r['code'], $mensaje, 422);
            return;
        }
        $this->ok([
            'clasificados' => $r['clasificados'],
            'cola' => $this->service->equiposSinClasificar(),
        ]);
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
        return $versionId === false ? null : $versionId;
    }

    /**
     * El cuerpo llega del cliente, así que las claves no están garantizadas: un JSON que no sea
     * un objeto se decodifica como lista y los accesos `$body[...] ?? null` devuelven null.
     *
     * @return array<mixed>
     */
    private function body(): array
    {
        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private function usuario(): string
    {
        return (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? ''));
    }
}
