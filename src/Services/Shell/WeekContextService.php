<?php

declare(strict_types=1);

namespace App\Services\Shell;

use App\Contracts\Shell\WeekAdministrationRepository;
use App\Security\RbacService;
use Database;
use TableResolver;

/**
 * Selección/limpieza de semana y el manifiesto canónico que consumen tanto `SessionApiController`
 * (bootstrap) como `ContextController` (select/clear) y `WeekContextApiController`
 * (create/delete-last). Un único sitio compone `week` para que el bootstrap y la respuesta de
 * cada mutación nunca diverjan (Tarea 5, T01 — mismo espíritu que `BaseController::
 * getWeekStatusVars()` documenta para `maxSemana`/`semanalConfirmada`).
 */
final class WeekContextService
{
    public function __construct(
        private readonly Database $db,
        private readonly WeekAdministrationRepository $repositorio,
    ) {
    }

    /**
     * `null` sin proyecto activo o sin semanas activas — la semana seleccionada (`current`)
     * puede ser 0/ausente incluso habiendo semanas (estado "sin elegir").
     *
     * @return array{current:int,options:list<array{number:int,startsOn:string,endsOn:string}>,actions:array{select:bool,create:bool,deleteLast:bool}}|null
     */
    public function contextoActual(int $projectId, int $semanaEnSesion, string $rol): ?array
    {
        // Igual que `SessionApiController::activeWeek()` hasta hoy: sin semana elegida en sesión
        // no hay `week` que mostrar, aunque el proyecto ya tenga semanas activas para elegir.
        if ($semanaEnSesion <= 0) {
            return null;
        }

        $options = $this->repositorio->semanasActivas($projectId);

        // Fix ronda 1 (hallazgo 1 de la revisión): antes usaba la capacidad gruesa
        // `canManageWeeks` para las dos acciones. `RbacCatalog` da al rol `DCV` el permiso fino
        // `lps.semana.crear` SIN `lps.semana.eliminar` — con la capacidad gruesa, `deleteLast`
        // se emitía en `true` para DCV y el servidor lo rechazaba con 403 al intentarlo
        // (`WeekContextApiController::eliminarUltima()` valida ese mismo permiso fino). Ahora
        // ambas acciones usan exactamente los permisos que los dos adaptadores validan, para que
        // React nunca reciba una acción que el servidor luego niegue.
        $rbac = new RbacService($this->db);
        $puedeCrear = $rbac->can('lps.semana.crear', $rol);
        $puedeEliminar = $rbac->can('lps.semana.eliminar', $rol);

        return [
            'current' => $semanaEnSesion,
            'options' => $options,
            'actions' => [
                'select' => count($options) > 1,
                'create' => $puedeCrear,
                'deleteLast' => $puedeEliminar && $options !== [],
            ],
        ];
    }

    /** true si la semana pertenece a `semanas_activas` para ese proyecto. */
    public function semanaPerteneceAlProyecto(int $projectId, int $semana): bool
    {
        $t = TableResolver::resolve($projectId, 'semanas_activas');
        $stmt = $this->db->queryWithProject(
            "SELECT COUNT(*) AS c FROM {$t} WHERE project_id = ? AND Semana = ?",
            [$projectId, $semana],
            $projectId,
        );

        return (int) ($stmt->fetch()['c'] ?? 0) > 0;
    }
}
