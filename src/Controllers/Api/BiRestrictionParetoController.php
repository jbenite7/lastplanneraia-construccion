<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\BiPreviewAccessPolicy;
use App\Security\RbacCatalog;
use App\Security\RbacManager;
use App\Security\RbacService;
use PDO;

/**
 * `GET /api/bi/control-tower/restricciones/pareto` — endpoint DEDICADO para el pareto de
 * restricciones no liberadas (`pi_restriction_pareto` del catálogo,
 * `src/Services/Bi/MetricDictionaryService.php:509-553`).
 *
 * Existe aparte de `BiMetricController::ejecutar()` porque esta métrica es una DISTRIBUCIÓN (N
 * filas por `restriction_type`), no un escalar `float|null` — `MetricExecutor::execute()` está
 * atado arquitectónicamente a un solo valor y bloquearía esta métrica con 422 por no ser
 * `ejecutable`. El propio catálogo documenta esta ruta como la solución correcta en su
 * `known_limitations`. Hermano RESTful de `BiConstraintListController`, mismo prefijo de ruta,
 * mismo estilo de envelope.
 *
 * RBAC de dos niveles, idéntico a `BiConstraintListController`:
 * 1. `BiPreviewAccessPolicy::canOpen()` (global) -> 404 si falla.
 * 2. `RbacService::resolveCurrentRole()` + `RbacManager::hasCapability(...,
 *    PERM_INTERNAL_BI_PREVIEW)` (acotado al proyecto de sesión) -> 404 si falla.
 * Lectura sin acción explícita del usuario: mismo criterio de 404 (no 403) que el resto del
 * módulo.
 *
 * Aislamiento: SOLO `[(int) $_SESSION['project_id']]` en el WHERE — nunca `BiProjectScope`
 * (multi-proyecto, para portafolio; este endpoint es de un solo proyecto).
 *
 * Fuente y filtro exactos del catálogo: `bi_pi_restricciones`, `is_ready=0`, `is_hard=1`,
 * agrupado por `restriction_type`, contado, ordenado descendente. `Titulo=0` ya está garantizado
 * por la propia vista (cada rama de la UNION filtra `pc.Titulo = 0` y la vista no expone la
 * columna `Titulo`), así que no hace falta repetirlo aquí.
 *
 * `tipo` sale como el valor CRUDO de `restriction_type` ('D_y_E', 'Materiales', 'MdeO', 'Equipos',
 * 'Predecesora') — no existe ningún diccionario de traducción de estos valores en el repo hoy.
 */
class BiRestrictionParetoController extends BaseController
{
    public function pareto(): void
    {
        $this->requireAuth();

        if (!BiPreviewAccessPolicy::canOpen($_SESSION)) {
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $role = (new RbacService($this->db))->resolveCurrentRole();
        if (!RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)) {
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        $semanaRaw = $_GET['semana'] ?? $_SESSION['semana'] ?? null;

        // Mismo molde que `BaseController::syncRequestedWeekContext()`: entero positivo o 422, nunca
        // el string crudo de `$_GET`. Corregido tras revisión de code-reviewer (Medium 1, 2026-08-26):
        // `?semana[]=1` reflejaba "Semana Array, ..." en el cuerpo y `?semana=1' OR '1'='1` daba 200
        // (MySQL coacciona el string contra la columna `Semana` que es `int`; no es inyección SQL --
        // va por sentencia preparada -- pero sí es entrada arbitraria reflejada sin validar).
        //
        // Semántica explícita para `semana` ausente (ni `$_GET` ni `$_SESSION`): a diferencia de
        // `MetricExecutor::buildWhereClause()` -- que omite el filtro entero y trae TODAS las
        // semanas cuando la semana es null --, este endpoint responde 422. Razón: en producción
        // `ProjectSelectorController::enterProject()` siempre siembra `$_SESSION['semana']` al
        // entrar a un proyecto, así que un `semana` ausente aquí no es "quiero el total histórico"
        // sino un estado que no debería alcanzarse; sin este 422 el `WHERE Semana = NULL` no empata
        // nada y el endpoint respondía 200 con una lista vacía sin que nadie lo pidiera.
        $semana = filter_var($semanaRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($semana === false) {
            $this->fallar(422, 'SEMANA_INVALIDA', 'El parámetro "semana" debe ser un entero positivo.');
        }

        $filas = $this->db->query(
            'SELECT restriction_type, COUNT(*) AS conteo
             FROM bi_pi_restricciones
             WHERE project_id = ? AND Semana = ? AND is_ready = 0 AND is_hard = 1
             GROUP BY restriction_type
             ORDER BY conteo DESC',
            [$projectId, $semana]
        )->fetchAll(PDO::FETCH_ASSOC);

        $distribucion = array_map(
            static fn(array $fila) => [
                'tipo' => (string) $fila['restriction_type'],
                'conteo' => (int) $fila['conteo'],
            ],
            $filas
        );

        $filasUsadas = array_sum(array_column($distribucion, 'conteo'));

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            [
                'ok' => true,
                'distribucion' => $distribucion,
                'basis' => [
                    'filas_usadas' => $filasUsadas,
                    'corte' => 'Semana ' . $semana . ', restricciones duras no liberadas',
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    /**
     * Envelope de error `{ok:false, error:{code, message}}` — igual que
     * `BiConstraintListController::fallar()`.
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
