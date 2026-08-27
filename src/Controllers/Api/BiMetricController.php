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
        if ($dictionary->getDefinition($metricKey) === []) {
            $this->fallar(404, 'NOT_FOUND', "Métrica desconocida en el catálogo: '{$metricKey}'.");
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        $semanaRaw = $_GET['semana'] ?? $_SESSION['semana'] ?? null;
        $semana = $semanaRaw !== null ? (string) $semanaRaw : null;

        $scope = new MetricScope([$projectId], null, null, $semana);
        $executor = new MetricExecutor($this->db, $dictionary);

        try {
            $result = $executor->execute($metricKey, $scope);
        } catch (RuntimeException $e) {
            // Métrica declarada en el catálogo pero no `ejecutable` (ej. `descriptiva`, sin forma
            // SQL reconocida por MetricExecutor::buildSelectExpression()) u otra falla del
            // ejecutor: nunca un 500 crudo, mismo envelope de error que el resto del módulo.
            $this->fallar(422, 'METRIC_NOT_EXECUTABLE', "La métrica '{$metricKey}' no se puede ejecutar: " . $e->getMessage());
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'value' => $result->value(),
            'basis' => $result->basis(),
            'completeness' => $result->completeness(),
            'missing' => $result->missing(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
