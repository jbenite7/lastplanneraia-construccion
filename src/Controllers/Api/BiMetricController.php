<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\BiPreviewAccessPolicy;
use App\Security\RbacCatalog;
use App\Security\RbacManager;
use App\Security\RbacService;
use App\Services\Bi\MetricDictionaryService;
use App\Services\Bi\MetricExecutor;
use App\Services\Bi\MetricResult;
use App\Services\Bi\MetricScope;
use RuntimeException;

/**
 * La Torre lee: ejecuta CUALQUIER métrica `ejecutable` del catálogo
 * (`MetricDictionaryService`) vía `MetricExecutor::execute()`, aislada por `project_id` de la
 * sesión activa. Task 7 paso 5 (Ola 1, Torre de Control piloto) — el gap que cierra: ningún
 * controlador PHP invocaba `MetricExecutor::execute()` todavía (confirmado por Task 7 paso 4 y
 * por grep sobre `src/Controllers/`).
 *
 * `GET /api/bi/control-tower/metricas/{metricKey}` — hermano RESTful de
 * `BiConstraintListController`/`BiConstraintWriteController` (mismo prefijo de ruta, controlador
 * propio). Contrato completo, oráculo y valores medidos en dev:
 * `tests/test_bi_metric_endpoint.php` y
 * `.superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-report.md`, sección "Task 7 paso 5".
 *
 * RBAC: mismo gate de DOS niveles que `BiConstraintListController` — `BiPreviewAccessPolicy::
 * canOpen()` (global, A/D/R en cualquier proyecto) + un segundo gate acotado con
 * `RbacService::resolveCurrentRole()` + `RbacManager::hasCapability(...,
 * PERM_INTERNAL_BI_PREVIEW)` (A/D/R en el proyecto de sesión). Denegado -> 404, no 403: lectura
 * sin acción explícita del usuario, mismo criterio que el listado.
 *
 * Aislamiento: el `MetricScope` se construye con `[(int) $_SESSION['project_id']]` — UN solo
 * proyecto, NUNCA `BiProjectScope::resolve()`/`resolveProjectIds()` (multi-proyecto, pensado para
 * los reportes de portafolio existentes). Mismo tipo de bug que ya se corrigió dos veces en esta
 * etapa (Task 5 Critical 1, Task 7 paso 3a Important 1).
 *
 * `metricKey` inexistente en el catálogo: se valida con `getDefinition() === []` ANTES de llamar
 * a `execute()` -> 404 NOT_FOUND con mensaje legible, nunca se deja escapar la `RuntimeException`
 * del executor como error crudo. Una métrica que SÍ existe pero no es `ejecutable` (o cualquier
 * otra `RuntimeException` de `execute()`) queda cubierta por el `try/catch` de abajo -> 422, por
 * el mismo motivo: nunca un 500 con traza cruda rompiendo el envelope `{ok:false,...}` que
 * `ct-app/src/lib/api.ts` exige.
 *
 * Fix ronda 1 (Important 2, revisión de spec+calidad): `estado_ejecucion !== 'ejecutable'` se
 * verifica ANTES de llamar a `execute()` -> 422, explícito, no delegado al accidente de que
 * `MetricExecutor::buildSelectExpression()` falle por no reconocer la forma SQL de una métrica
 * `descriptiva`. D59 exige que lo predictivo (ej. `ps_pac_expected`, `descriptiva`) nunca se
 * sirva como cifra dura por esta ruta — esa invariante no puede depender de un parser.
 *
 * Fix ronda 1 (Important 1): `missing` se normaliza a un array PLANO de strings antes de
 * responder — ver `normalizarMissing()` — porque `MetricExecutor::buildMissing()` puede devolver
 * un array mixto (lista + clave `obras_sin_datos`) que `json_encode()` serializaría como objeto,
 * rompiendo el contrato `missing: string[]` de `ct-app/src/lib/api.ts`.
 */
class BiMetricController extends BaseController
{
    public function ejecutar(string $metricKey): void
    {
        $this->requireAuth();

        if (!BiPreviewAccessPolicy::canOpen($_SESSION)) {
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $role = (new RbacService($this->db))->resolveCurrentRole();
        if (!RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)) {
            // Mismo criterio que canOpen(), pero acotado al proyecto de la sesión activa: ver
            // BiConstraintListController::listar().
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $dictionary = new MetricDictionaryService();
        $definition = $dictionary->getDefinition($metricKey);
        if ($definition === []) {
            $this->fallar(404, 'NOT_FOUND', "Métrica desconocida en el catálogo: '{$metricKey}'.");
        }

        $estadoEjecucion = (string) ($definition['estado_ejecucion'] ?? '');
        if ($estadoEjecucion !== 'ejecutable') {
            // Fix ronda 1 (Important 2): ver docblock de la clase. Nunca depender de que
            // MetricExecutor falle por accidente para bloquear una métrica descriptiva/predictiva.
            $this->fallar(
                422,
                'METRIC_NOT_EXECUTABLE',
                "La métrica '{$metricKey}' no es ejecutable (estado_ejecucion='{$estadoEjecucion}')."
            );
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        $semanaRaw = $_GET['semana'] ?? $_SESSION['semana'] ?? null;
        $semana = $semanaRaw !== null ? (string) $semanaRaw : null;

        $scope = new MetricScope([$projectId], null, null, $semana);
        $executor = new MetricExecutor($this->db, $dictionary);

        try {
            $result = $executor->execute($metricKey, $scope);
        } catch (RuntimeException $e) {
            // Cualquier otra falla del ejecutor no cubierta por el chequeo de estado_ejecucion de
            // arriba (ej. una métrica marcada 'ejecutable' que igual no encaja en la forma SQL que
            // reconoce MetricExecutor): nunca un 500 crudo, mismo envelope de error que el resto
            // del módulo.
            $this->fallar(422, 'METRIC_NOT_EXECUTABLE', "La métrica '{$metricKey}' no se puede ejecutar: " . $e->getMessage());
        }

        [$missing, $basis] = $this->normalizarMissing($result);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'value' => $result->value(),
            'basis' => $basis,
            'completeness' => $result->completeness(),
            'missing' => $missing,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Fix ronda 1 (Important 1, revisión de spec+calidad): `MetricExecutor::buildMissing()` puede
     * devolver un array MIXTO — elementos de lista (strings sueltos, ej.
     * 'sin_filas_que_cumplan_los_filtros') junto con una clave string 'obras_sin_datos' (lista de
     * project_id sin datos), en el caso real completeness='insuficiente'/'parcial' con scope de un
     * solo proyecto. `json_encode()` de ese array mixto serializa como OBJETO en cuanto trae una
     * clave string, no como array — rompe el contrato `missing: string[]` de
     * `ct-app/src/lib/api.ts`, la primera exposición HTTP de `MetricResult`. Aquí se separa
     * 'obras_sin_datos' hacia `basis` (información de cobertura, mismo lugar que
     * obras_incluidas/obras_esperadas) y el resto se reindexa con `array_values()` para quedar
     * plano.
     *
     * @return array{0:list<string>,1:array<string,mixed>}
     */
    private function normalizarMissing(MetricResult $result): array
    {
        $missing = $result->missing();
        $basis = $result->basis();

        if (array_key_exists('obras_sin_datos', $missing)) {
            $basis['obras_sin_datos'] = $missing['obras_sin_datos'];
            unset($missing['obras_sin_datos']);
        }

        return [array_values(array_map('strval', $missing)), $basis];
    }

    /**
     * Envelope de error `{ok:false, error:{code, message}}` — igual que
     * `BiConstraintListController::fallar()`/`BiConstraintWriteController::fallar()`.
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
}
