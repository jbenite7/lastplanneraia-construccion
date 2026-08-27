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
 * La Torre lee: lista de restricciones de `pi_shared_constraints` con sus columnas de gestión
 * (Task 4) y la urgencia calculada (Task 7 paso 2, `ordenarPorUrgencia()` en
 * `ct-app/src/lib/urgencia.ts`) para pintar el lienzo CT-8.3 de Intermedia sin salir de la hoja.
 *
 * `GET /api/bi/control-tower/restricciones` — hermano RESTful del
 * `POST /api/bi/control-tower/restricciones/{id}/gestion` de Task 5
 * (`BiConstraintWriteController`), mismo prefijo de ruta, controlador separado. Contrato completo
 * y fuente de cada campo: `.superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-paso3a-report.md`.
 *
 * A diferencia del POST de Task 5, esta lectura SÍ reutiliza `BiPreviewAccessPolicy::canOpen()`
 * (el gate del constructor de `BiControlTowerApiController`): es la misma semántica que los otros
 * 8 GET del módulo BI ("esta página no existe para tu rol") — 404, no 403, porque no hay una
 * acción explícita del usuario que justifique diferir de esa convención.
 *
 * Fix ronda 1 (Important 1): `canOpen()` resuelve el rol vía `RbacService::resolveRoleForUser()`
 * SIN project scoping — el rol MÁS PRIVILEGIADO que el usuario tenga en CUALQUIER proyecto, no su
 * rol en el proyecto de la sesión activa. Es el mismo bug que Task 5 tuvo que corregir
 * (`BiConstraintWriteController`, Critical 1 de su propia ronda 1). No se toca `canOpen()` — es
 * compartido por los otros 8 GET del módulo BI y su resolución global responde una pregunta
 * distinta ("¿puede este usuario abrir el módulo BI oculto?"), ruling del revisor. En su lugar,
 * un SEGUNDO gate, acotado al proyecto de sesión, con el mismo patrón que ya usa
 * `BiConstraintWriteController::resolveRole()`: `RbacService::resolveCurrentRole()`.
 */
class BiConstraintListController extends BaseController
{
    public function listar(): void
    {
        $this->requireAuth();

        if (!BiPreviewAccessPolicy::canOpen($_SESSION)) {
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $role = (new RbacService($this->db))->resolveCurrentRole();
        if (!RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)) {
            // Mismo criterio que canOpen(), pero acotado al proyecto de la sesión activa: un
            // usuario A/D/R en OTRO proyecto y V/C/... en este no puede ver las restricciones de
            // este proyecto solo porque canOpen() lo dejó pasar con su rol más privilegiado global.
            $this->fallar(404, 'NOT_FOUND', 'Esta página no existe.');
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);

        $filas = $this->db->query(
            'SELECT Id, Restriccion, Semana, EstadoLiberacion, ResponsableAsignado,
                    FechaCompromiso, AsignadoPor, AsignadoEn
             FROM pi_shared_constraints
             WHERE project_id = ?
             ORDER BY Id',
            [$projectId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $agregados = $this->cargarAgregados($projectId);
        $bloqueantes = $this->cargarActividadBloqueada($projectId);

        $restricciones = array_map(
            fn(array $fila) => $this->formatearFila($fila, $agregados, $bloqueantes),
            $filas
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'restricciones' => $restricciones], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envelope de error `{ok:false, error:{code, message}}` — igual que
     * `BiConstraintWriteController::fallar()`: `ct-app/src/lib/api.ts` exige `body.ok` booleano y
     * ambos endpoints comparten cliente, así que comparten el mismo formato.
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

    /**
     * Agrega, por `SharedConstraintId`, el conteo de actividades encadenadas, la semana mínima de
     * arranque entre ellas (puede ser negativa — medido `-3` en dev, ver el reporte de rol A) y si
     * alguna toca ruta crítica. Join idéntico al de la rama de shared constraints en
     * `database/bi/002_bi_pi_restricciones.sql:120-128` (`ConsecutivoEnPrograma`+`Semana`+
     * `project_id`, `Titulo=0`). Consulta separada de la base (no un JOIN sobre
     * `pi_shared_constraints`) a propósito: así una restricción con varias actividades encadenadas
     * nunca duplica su fila base — el `GROUP BY` vive en esta agregación aparte, no en un producto
     * cartesiano con la lista principal.
     *
     * Fix ronda 1 (Important 2): `COUNT(DISTINCT pcl.Id)`, no `COUNT(*)`. La clave del join
     * (`ConsecutivoEnPrograma`+`Semana`+`project_id`) NO es única en `programa_consolidado` (62
     * grupos duplicados medidos en dev, dato preexistente de la tabla, no de este endpoint) — un
     * `COUNT(*)` infla `actividadesEncadenadas` si algún link llega a apuntar a uno de esos grupos.
     * `pcl.Id` sí es única dentro de un `project_id` (PK compuesta `(project_id, Id)`, y la consulta
     * ya filtra por un único `project_id`), así que cuenta filas de LINK reales, no productos del
     * join.
     *
     * @return array<int, array{n:int, semanaMin:?int, rutaCritica:bool}>
     */
    private function cargarAgregados(int $projectId): array
    {
        $filas = $this->db->query(
            'SELECT pcl.SharedConstraintId AS id,
                    COUNT(DISTINCT pcl.Id) AS n,
                    MIN(pc.Semanas_Inicio) AS semana_min,
                    MAX(pc.Ruta_Critica) AS ruta_critica
             FROM pi_shared_constraint_links pcl
             JOIN programa_consolidado pc
               ON pcl.ConsecutivoEnPrograma = pc.Consecutivo_en_Programa
              AND pcl.Semana = pc.Semana
              AND pcl.project_id = pc.project_id
             WHERE pcl.project_id = ? AND pc.Titulo = 0
             GROUP BY pcl.SharedConstraintId',
            [$projectId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $agregados = [];
        foreach ($filas as $fila) {
            $agregados[(int) $fila['id']] = [
                'n' => (int) $fila['n'],
                'semanaMin' => $fila['semana_min'] !== null ? (int) $fila['semana_min'] : null,
                'rutaCritica' => (int) $fila['ruta_critica'] === 1,
            ];
        }

        return $agregados;
    }

    /**
     * Nombre de una actividad representativa por `SharedConstraintId`, entre las que empatan en
     * la semana mínima de `cargarAgregados()` (recalculada aquí vía subconsulta, no reutilizada en
     * PHP, para mantener la comparación de semana dentro de SQL). `MIN(pc.Actividad)` desempata
     * de forma determinista pero arbitraria — el contrato no exige una actividad concreta, solo
     * que pertenezca al conjunto de candidatas de esa semana (ver el test, caso 3).
     *
     * @return array<int, string>
     */
    private function cargarActividadBloqueada(int $projectId): array
    {
        $filas = $this->db->query(
            'SELECT t.SharedConstraintId AS id, MIN(pc.Actividad) AS actividad
             FROM pi_shared_constraint_links t
             JOIN programa_consolidado pc
               ON t.ConsecutivoEnPrograma = pc.Consecutivo_en_Programa
              AND t.Semana = pc.Semana
              AND t.project_id = pc.project_id
             JOIN (
                 SELECT pcl2.SharedConstraintId AS scid, MIN(pc2.Semanas_Inicio) AS semana_min
                 FROM pi_shared_constraint_links pcl2
                 JOIN programa_consolidado pc2
                   ON pcl2.ConsecutivoEnPrograma = pc2.Consecutivo_en_Programa
                  AND pcl2.Semana = pc2.Semana
                  AND pcl2.project_id = pc2.project_id
                 WHERE pcl2.project_id = ? AND pc2.Titulo = 0
                 GROUP BY pcl2.SharedConstraintId
             ) semmin ON semmin.scid = t.SharedConstraintId AND semmin.semana_min = pc.Semanas_Inicio
             WHERE t.project_id = ? AND pc.Titulo = 0
             GROUP BY t.SharedConstraintId',
            [$projectId, $projectId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $bloqueantes = [];
        foreach ($filas as $fila) {
            $bloqueantes[(int) $fila['id']] = (string) $fila['actividad'];
        }

        return $bloqueantes;
    }

    /**
     * @param array<string,mixed> $fila
     * @param array<int, array{n:int, semanaMin:?int, rutaCritica:bool}> $agregados
     * @param array<int, string> $bloqueantes
     * @return array<string,mixed>
     */
    private function formatearFila(array $fila, array $agregados, array $bloqueantes): array
    {
        $id = (int) $fila['Id'];
        $agregado = $agregados[$id] ?? ['n' => 0, 'semanaMin' => null, 'rutaCritica' => false];
        $fechaCompromiso = $this->nullSiVacio($fila['FechaCompromiso']);

        return [
            'id' => $id,
            'restriccion' => $fila['Restriccion'],
            'semana' => (int) $fila['Semana'],
            'actividadBloqueada' => $agregado['n'] > 0 ? ($bloqueantes[$id] ?? null) : null,
            'responsableAsignado' => $this->nullSiVacio($fila['ResponsableAsignado']),
            'fechaCompromiso' => $fechaCompromiso,
            'estadoLiberacion' => $fila['EstadoLiberacion'],
            'asignadoPor' => $this->nullSiVacio($fila['AsignadoPor']),
            'asignadoEn' => $this->nullSiVacio($fila['AsignadoEn']),
            'diasVencida' => $this->diasVencida($fechaCompromiso),
            'semanaInicioActividadBloqueada' => $agregado['semanaMin'],
            'actividadesEncadenadas' => $agregado['n'],
            'tocaRutaCritica' => $agregado['rutaCritica'],
        ];
    }

    private function nullSiVacio(?string $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : $valor;
    }

    /**
     * Misma forma que `SeguimientoService::clasificarVencimiento()`
     * (`src/Services/Pdc/SeguimientoService.php:57-79`): sin fecha o fecha futura/hoy -> sin días
     * de vencimiento (`null`); fecha pasada -> entero positivo de días transcurridos.
     */
    private function diasVencida(?string $fechaCompromiso): ?int
    {
        if ($fechaCompromiso === null) {
            return null;
        }

        $hoy = new \DateTimeImmutable('today');
        $fecha = new \DateTimeImmutable($fechaCompromiso);
        if ($fecha >= $hoy) {
            return null;
        }

        return (int) $hoy->diff($fecha)->days;
    }
}
