<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PlanFechasService;

/**
 * Plan de compras con fechas (PDC v2 / Fase A4).
 * Lectura: lps.paquetes_contratacion.ver. Escritura: lps.paquetes_contratacion.editar + CSRF plan_compras_v2.
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasPlanController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private PlanFechasService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new PlanFechasService($this->db);
    }

    /** GET /plan-compras/api/plan/frentes */
    public function frentes(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['frentes' => $this->service->frentesDisponibles($projectId)]);
    }

    /** GET /plan-compras/api/plan/sugerencias */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['sugerencias' => $this->service->sugerirFrentes($projectId)]);
    }

    /** GET /plan-compras/api/plan */
    public function plan(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok([
            'plan' => $this->service->plan($projectId),
            'amarres' => $this->service->amarres($projectId),
        ]);
    }

    /** GET /plan-compras/api/plan/desfases */
    public function desfases(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['desfases' => $this->service->desfases($projectId)]);
    }

    /** POST /plan-compras/api/plan/amarrar  {paqueteId, uniqueId, procedencia?} */
    public function amarrar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $uniqueId = filter_var($body['uniqueId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $uniqueId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId o uniqueId inválidos.', 422);
            return;
        }
        $procedencia = is_array($body['procedencia'] ?? null) ? $body['procedencia'] : [];
        $r = $this->service->amarrar($projectId, (int) $paqueteId, (int) $uniqueId, $this->usuario(), $procedencia);
        if (!$r['ok']) {
            $mensaje = $r['code'] === 'MODALIDAD_NO_CONTRATABLE'
                ? 'Este paquete no genera proceso de contratación (nómina, imprevistos o consumo directo contra almacén) y no puede amarrarse a una fecha.'
                : 'El paquete o el frente no existen.';
            $this->fail($r['code'], $mensaje, 422);
            return;
        }
        $this->ok(['ok' => true]);
    }

    /** POST /plan-compras/api/plan/calcular */
    public function calcular(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $this->ok($this->service->calcular($projectId, $this->usuario()));
    }

    /** POST /plan-compras/api/plan/responsable  {paqueteId, responsable} */
    public function responsable(): void
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
        $responsable = is_string($body['responsable'] ?? null) ? mb_substr($body['responsable'], 0, 100) : '';

        // No usar rowCount() del UPDATE para decidir si la fila existe: este repo no activa
        // PDO::MYSQL_ATTR_FOUND_ROWS (ver Database.php), así que MySQL reporta filas MODIFICADAS,
        // no coincidentes. Guardar el mismo responsable dos veces seguidas (algo normal: abrir la
        // vista y guardar sin cambiar nada) da rowCount=0 aunque la fila exista, y el controlador
        // respondía por error PAQUETE_SIN_PLAN. Se confirma la existencia con un SELECT explícito.
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, (int) $paqueteId],
        )->fetchColumn();
        if ($existe === false) {
            $this->fail(
                'PAQUETE_SIN_PLAN',
                'Este paquete todavía no tiene plan de compras calculado. Calcula el plan antes de asignar responsable.',
                422
            );
            return;
        }

        $this->db->query(
            'UPDATE pdc_plan_paquete SET responsable = ? WHERE project_id = ? AND paquete_id = ?',
            [$responsable, $projectId, (int) $paqueteId],
        );

        $this->ok(['ok' => true]);
    }

    // ── guards ──────────────────────────────────────────────

    private function guardLectura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para ver el plan de compras.', 403);
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
            $this->fail('FORBIDDEN', 'No autorizado para editar el plan de compras.', 403);
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
