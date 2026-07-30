<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\FlujoCajaService;
use App\Services\Pdc\SubpaquetesService;

/**
 * Subpaquetes de obra y flujo de caja (PDC v2 / Ola 3).
 *
 * **RBAC:** lectura `lps.paquetes_contratacion.ver`; escritura `lps.paquetes_contratacion.editar`
 * + CSRF `plan_compras_v2`. Partir un paquete NO exige `...reglas` a propósito: los subpaquetes son
 * casuística de una obra, no tocan el maestro global y no afectan a ningún otro proyecto — que es
 * exactamente lo que el comité del 2026-07-29 pidió. Quien ya reparte insumos entre paquetes en su
 * obra es quien sabe que el porcelanato va con otro proveedor, y obligarle a pedírselo a alguien más
 * frenaría el uso que motivó la función.
 *
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasSubpaquetesController
{
    use PlanComprasJsonRespuestas;

    private \Database $db;
    private SubpaquetesService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new SubpaquetesService($this->db);
    }

    /** GET /plan-compras/api/subpaquetes?paqueteId=N */
    public function listar(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $paqueteId = $this->paqueteId($_GET['paqueteId'] ?? null);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $this->ok([
            'subpaquetes' => $this->service->listar($projectId, $paqueteId),
            'resumen' => $this->service->resumenSombrilla($projectId, $paqueteId),
        ]);
    }

    /**
     * GET /plan-compras/api/subpaquetes/destinos
     *
     * La lista de unidades contratables de la obra, que es la que fija la unidad de toda cifra que
     * antes contaba paquetes. Se expone tal cual para que la pantalla no vuelva a decidirla.
     */
    public function destinos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $destinos = $this->service->destinos($projectId);
        $this->ok([
            'destinos' => $destinos,
            // La etiqueta viaja del servidor y no se escribe en la vista: es la frase que resuelve la
            // ambigüedad de «11 de 96 paquetes o 11 de 130 lotes», y tiene que ser la misma en todas
            // las pantallas que la muestren.
            'unidad' => 'procesos de contratación',
            'total' => count($destinos),
        ]);
    }

    /** POST /plan-compras/api/subpaquetes/partir  {paqueteId, nombres: [...]} */
    public function partir(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteId = $this->paqueteId($body['paqueteId'] ?? null);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $nombres = $body['nombres'] ?? null;
        if (!is_array($nombres)) {
            $this->fail('SIN_NOMBRES', 'nombres debe ser una lista.', 422);
            return;
        }
        $this->responder($this->service->partir(
            $projectId,
            $paqueteId,
            array_map(static fn (mixed $n): string => is_string($n) ? $n : '', $nombres),
            $this->usuario(),
        ));
    }

    /** POST /plan-compras/api/subpaquetes/agregar  {paqueteId, nombre, modalidad} */
    public function agregar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteId = $this->paqueteId($body['paqueteId'] ?? null);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $this->responder($this->service->agregar(
            $projectId,
            $paqueteId,
            is_string($body['nombre'] ?? null) ? $body['nombre'] : '',
            is_string($body['modalidad'] ?? null) ? $body['modalidad'] : 'contrato',
            $this->usuario(),
        ));
    }

    /** POST /plan-compras/api/subpaquetes/actualizar  {subpaqueteId, nombre?, modalidad?, responsableUserId?} */
    public function actualizar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $subpaqueteId = $this->subpaqueteId($body['subpaqueteId'] ?? null);
        if ($subpaqueteId === false) {
            $this->fail('SUBPAQUETE_INVALIDO', 'subpaqueteId inválido.', 422);
            return;
        }
        // Solo se pasan las claves que VINIERON: `actualizar()` distingue «no lo mandes» de «ponlo en
        // null», y el responsable en null es una orden legítima («este lote se queda sin dueño»).
        $campos = [];
        foreach (['nombre', 'modalidad'] as $k) {
            if (array_key_exists($k, $body) && is_string($body[$k])) {
                $campos[$k] = $body[$k];
            }
        }
        if (array_key_exists('responsableUserId', $body)) {
            $r = $body['responsableUserId'];
            if ($r === null || $r === '') {
                $campos['responsableUserId'] = null;
            } else {
                $id = filter_var($r, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    $this->fail('RESPONSABLE_INVALIDO', 'responsableUserId inválido.', 422);
                    return;
                }
                $campos['responsableUserId'] = $id;
            }
        }
        $this->responder($this->service->actualizar($projectId, $subpaqueteId, $campos, $this->usuario()));
    }

    /** POST /plan-compras/api/subpaquetes/eliminar  {subpaqueteId} */
    public function eliminar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $subpaqueteId = $this->subpaqueteId($this->body()['subpaqueteId'] ?? null);
        if ($subpaqueteId === false) {
            $this->fail('SUBPAQUETE_INVALIDO', 'subpaqueteId inválido.', 422);
            return;
        }
        $this->responder($this->service->eliminar($projectId, $subpaqueteId));
    }

    /** POST /plan-compras/api/subpaquetes/mover  {subpaqueteId, insumos: [{descripcionNorm, unidad}]} */
    public function mover(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $subpaqueteId = $this->subpaqueteId($body['subpaqueteId'] ?? null);
        if ($subpaqueteId === false) {
            $this->fail('SUBPAQUETE_INVALIDO', 'subpaqueteId inválido.', 422);
            return;
        }
        $crudos = $body['insumos'] ?? null;
        if (!is_array($crudos) || $crudos === []) {
            $this->fail('SIN_INSUMOS', 'insumos debe ser una lista no vacía.', 422);
            return;
        }
        $insumos = [];
        foreach ($crudos as $i) {
            if (!is_array($i) || !is_string($i['descripcionNorm'] ?? null) || !is_string($i['unidad'] ?? null)) {
                $this->fail('INSUMO_INVALIDO', 'Cada insumo necesita descripcionNorm y unidad.', 422);
                return;
            }
            $insumos[] = ['descripcionNorm' => $i['descripcionNorm'], 'unidad' => $i['unidad']];
        }
        $this->responder($this->service->moverInsumos($projectId, $subpaqueteId, $insumos));
    }

    /** GET /plan-compras/api/seguimiento/flujo-caja */
    public function flujoCaja(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok((new FlujoCajaService($this->db, $this->service))->curva($projectId));
    }

    /**
     * GET /plan-compras/api/seguimiento/flujo-caja.csv
     *
     * Descarga directa y no JSON: el archivo va a viajar a un comité que no entra a la aplicación.
     * No pasa por `ok()` porque no es una respuesta de la API sino un adjunto.
     */
    public function flujoCajaCsv(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $csv = (new FlujoCajaService($this->db, $this->service))->csv($projectId);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="flujo-caja-contratacion.csv"');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
    }

    // ---------------------------------------------------------------------------------------------

    /**
     * Traduce la respuesta de un método del servicio a la envolvente JSON de la API.
     *
     * El tipo se declara laxo (`array<string, mixed>`) a propósito: los métodos del servicio
     * devuelven formas distintas según lo que hicieron —`subpaquetes`, `subpaqueteId`, `movidos`,
     * `desparte`— y tipar aquí la unión de todas obligaría a tocar este método cada vez que uno de
     * ellos añade una clave, sin ganar ninguna comprobación útil.
     *
     * @param array<string, mixed> $r
     */
    private function responder(array $r): void
    {
        if (($r['ok'] ?? false) !== true) {
            $code = $r['code'] ?? 'ERROR';
            $mensaje = $r['mensaje'] ?? 'No se pudo completar la operación.';
            $this->fail(is_string($code) ? $code : 'ERROR', is_string($mensaje) ? $mensaje : '', 422);
            return;
        }
        $this->ok($r);
    }

    /** @return int|false */
    private function paqueteId(mixed $crudo): int|false
    {
        return filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    }

    /** @return int|false */
    private function subpaqueteId(mixed $crudo): int|false
    {
        return filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    }

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
            $this->fail('FORBIDDEN', 'No autorizado para partir paquetes del plan de compras.', 403);
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
