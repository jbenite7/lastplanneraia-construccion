<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PasosContratacionService;
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

    /**
     * GET /plan-compras/api/plan/anclas
     *
     * Todos los nodos a los que se puede amarrar: los encabezados y también las actividades. Va
     * aparte de `frentes` para no cambiarle la forma a lo que ya consume la pestaña «Plan».
     */
    public function anclas(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['anclas' => $this->service->anclasDisponibles($projectId)]);
    }

    /** GET /plan-compras/api/plan/sugerencias */
    public function sugerencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        // `motivos` explica, para cada paquete sin propuesta, qué rama falta por resolver. Sin eso la
        // fila queda muda y quien la mira no sabe qué hacer con ella.
        $r = $this->service->sugerenciasYMotivos($projectId);
        $this->ok(['sugerencias' => $r['sugerencias'], 'motivos' => $r['motivos']]);
    }

    /** GET /plan-compras/api/plan/correspondencias */
    public function correspondencias(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok($this->service->correspondencias($projectId));
    }

    /**
     * POST /plan-compras/api/plan/correspondencias  {rama, ancla, alcance?}
     *
     * Tocar el catálogo GLOBAL es gobernanza: cambia lo que el motor propondrá en todas las obras de
     * AIA, así que exige `lps.paquetes_contratacion.reglas`, el permiso que A3.3 creó para aprobar
     * reglas y overrides globales. La excepción de una obra concreta se conforma con el permiso de
     * editar el plan, porque su efecto no sale de ese proyecto.
     */
    public function guardarCorrespondencia(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $alcance = ($body['alcance'] ?? 'global') === 'proyecto' ? 'proyecto' : 'global';
        if ($alcance === 'global' && !(new RbacService($this->db))->can('lps.paquetes_contratacion.reglas')) {
            $this->fail(
                'FORBIDDEN',
                'Cambiar una correspondencia para todas las obras necesita el permiso de reglas. Puedes guardarla solo para este proyecto.',
                403,
            );
            return;
        }
        $r = $this->service->guardarCorrespondencia(
            $projectId,
            (string) ($body['rama'] ?? ''),
            (string) ($body['ancla'] ?? ''),
            $alcance,
            $this->usuario(),
        );
        if (!$r['ok']) {
            $this->fail(
                $r['code'],
                $r['code'] === 'ANCLA_INVALIDA'
                    ? 'Ese nodo no existe en el cronograma de esta obra.'
                    : 'La rama y el nodo del cronograma son obligatorios.',
                422,
            );
            return;
        }
        $this->ok(['ok' => true]);
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

    /**
     * POST /plan-compras/api/plan/desamarrar  {paqueteId}
     *
     * Mismo guard que amarrar (`lps.paquetes_contratacion.editar`): quien puede tomar la decisión
     * puede deshacerla. Un permiso aparte para desamarrar dejaría a gente capaz de crear un amarre
     * equivocado sin poder corregirlo.
     */
    public function desamarrar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $paqueteId = filter_var($this->body()['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }
        $this->ok($this->service->desamarrar($projectId, (int) $paqueteId));
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

    /**
     * GET /plan-compras/api/plan/reprogramacion/simular — el antes/después, sin escribir nada.
     *
     * Exige el permiso de EDITAR aunque solo lea: simular es el primer paso de aplicar, y enseñarle
     * el delta completo a quien no puede aplicarlo solo produce una pantalla que promete un botón
     * que va a responderle 403.
     *
     * Lo que NO exige es CSRF, y por eso no reutiliza `guardEscritura()`: CSRF protege mutaciones,
     * y esta no lo es. Pedirlo en un GET además rompe el cliente, que solo adjunta el token en
     * POST (ver `apiPost()` en pdc-app/src/lib/api.ts).
     */
    public function simularReprogramacion(): void
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.editar')) {
            $this->fail('FORBIDDEN', 'No autorizado para reprogramar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $this->ok($this->service->simularReprogramacion($projectId));
    }

    /** POST /plan-compras/api/plan/reprogramacion/aplicar  {paqueteIds:[int]} */
    public function aplicarReprogramacion(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $ids = $this->body()['paqueteIds'] ?? null;
        if (!is_array($ids)) {
            $this->fail('PAQUETES_INVALIDOS', 'Falta la lista de paquetes a reprogramar.', 422);
            return;
        }
        $limpios = [];
        foreach ($ids as $id) {
            $n = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($n === false) {
                $this->fail('PAQUETES_INVALIDOS', 'Hay un paquete inválido en la lista.', 422);
                return;
            }
            $limpios[] = $n;
        }
        $this->ok($this->service->aplicarReprogramacion($projectId, $limpios, $this->usuario()));
    }

    /** POST /plan-compras/api/plan/responsable  {paqueteId|paqueteIds, responsableUserId} — null lo deja sin responsable */
    public function responsable(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();

        // Acepta `paqueteIds` (lista, asignación en masa) o `paqueteId` (singular). El singular se
        // conserva porque el e2e ya commiteado lo usa; los dos caminos acaban en la misma llamada.
        $entrada = $body['paqueteIds'] ?? $body['paqueteId'] ?? null;
        $paqueteIds = is_array($entrada) ? $entrada : ($entrada === null ? [] : [$entrada]);
        if ($paqueteIds === []) {
            $this->fail('PAQUETE_INVALIDO', 'No se recibió ningún paquete.', 422);
            return;
        }

        // Ausente y null significan lo mismo —dejar el paquete sin responsable— y hay que
        // distinguirlos de un id con basura dentro: `filter_var(null, FILTER_VALIDATE_INT)` también
        // devuelve false, así que sin este orden «vaciar» se respondería como error de formato.
        $crudo = $body['responsableUserId'] ?? null;
        $responsableUserId = null;
        if ($crudo !== null) {
            $validado = filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($validado === false) {
                $this->fail('RESPONSABLE_INVALIDO', 'responsableUserId inválido.', 422);
                return;
            }
            $responsableUserId = (int) $validado;
        }

        $r = $this->service->asignarResponsable($projectId, $paqueteIds, $responsableUserId, $this->usuario());
        if (!$r['ok']) {
            // `asignarResponsable()` ya devuelve `code` siempre que `ok` es false (tipo discriminado
            // en su docblock): a diferencia del resto del archivo, aquí el acceso directo es seguro y
            // PHPStan lo exige (con `?? ''` marca el offset como redundante, nivel 6).
            $mensaje = $r['code'] === 'RESPONSABLE_NO_ELEGIBLE'
                ? 'Esa persona no pertenece al equipo activo de este proyecto.'
                : 'Este paquete todavía no tiene plan de compras calculado. Calcula el plan antes de asignar responsable.';
            $this->fail($r['code'], $mensaje, 422);
            return;
        }

        $this->ok(['ok' => true]);
    }

    /** GET /plan-compras/api/plan/responsables — quién puede ser responsable en este proyecto */
    public function responsables(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['responsables' => $this->service->responsablesElegibles($projectId)]);
    }

    /** GET /plan-compras/api/plan/pasos — el catálogo de la empresa y el proceso de esta obra. */
    public function pasos(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $svc = new PasosContratacionService($this->db);
        $this->ok([
            'catalogo' => $svc->catalogo(),
            'proyecto' => $svc->deProyecto($projectId),
            'configurado' => $svc->configurado($projectId),
            // Para que el aviso de quitar un paso pueda decir un número y no un «se borrarán filas»
            // genérico: quitar un paso borra exactamente una fila por paquete con plan.
            'paquetesConPlan' => (int) $this->db->query(
                'SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id = ? AND fecha_arranque IS NOT NULL',
                [$projectId],
            )->fetchColumn(),
        ]);
    }

    /**
     * POST /plan-compras/api/plan/pasos  {pasos:[{clave, alias?, diasFijos?}]}
     *
     * Guarda y recalcula en la misma llamada: cambiar los pasos mueve las fechas de todos los
     * paquetes de la obra, y dejar la configuración nueva conviviendo con el plan viejo pondría en
     * pantalla unas fechas que ya no son las que produce esa configuración.
     */
    public function guardarPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $pasos = is_array($body['pasos'] ?? null) ? array_values($body['pasos']) : null;
        if ($pasos === null) {
            $this->fail('PASOS_INVALIDOS', 'Falta la lista de pasos.', 422);
            return;
        }
        $r = (new PasosContratacionService($this->db))->guardar($projectId, $pasos, $this->usuario());
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'PASOS_INVALIDOS', $r['mensaje'] ?? 'Configuración de pasos inválida.', 422);
            return;
        }
        $this->ok(array_merge($r, $this->service->calcular($projectId, $this->usuario())));
    }

    /** POST /plan-compras/api/plan/pasos/restablecer — la obra vuelve al proceso por defecto. */
    public function restablecerPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        (new PasosContratacionService($this->db))->restablecer($projectId);
        $this->ok($this->service->calcular($projectId, $this->usuario()));
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
     * Cambiar los pasos mueve las fechas de TODOS los paquetes de la obra a la vez, así que no basta
     * con poder asignar insumos: exige el mismo permiso con el que A3.3 aprueba reglas globales del
     * motor (Oficina Técnica / Compras y Director de Obra).
     */
    private function guardReglas(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.reglas')) {
            $this->fail('FORBIDDEN', 'No autorizado para cambiar los pasos del proceso de contratación.', 403);
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
