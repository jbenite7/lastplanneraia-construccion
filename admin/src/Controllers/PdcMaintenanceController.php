<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Admin\Models\Project;
use App\Services\Pdc\PdcResetService;
use Throwable;

/**
 * Limpieza selectiva del Plan de Compras de un proyecto.
 *
 * Hasta ahora esto sólo se podía hacer por consola (`database/seeds/pdc_reset_proyecto.php`), lo
 * que obligaba a tener acceso a Docker. La lógica de borrado es la misma —vive en
 * `App\Services\Pdc\PdcResetService`— para que consola y panel no puedan divergir.
 *
 * Salvaguardas: rol A, CSRF, respaldo obligatorio previo (si falla, no se borra), y confirmación
 * escribiendo el nombre exacto del proyecto.
 */
class PdcMaintenanceController extends AdminController
{
    private Project $projectModel;
    private PdcResetService $reset;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminRole('Solo administradores pueden limpiar el Plan de Compras.');
        $db = \Database::getInstance();
        $this->projectModel = new Project($db);
        $this->reset = new PdcResetService($db);
    }

    public function index()
    {
        $this->render('pdc/limpieza', [
            'title' => 'Limpieza Plan de Compras - Admin Panel',
            'pageTitle' => 'Limpieza del Plan de Compras',
            'breadcrumb' => 'Limpieza PDC',
            'projects' => $this->projectModel->getAll(),
            'etapas' => PdcResetService::ETAPAS,
            'csrf_token' => Security::generateCsrfToken(),
        ]);
    }

    /**
     * Conteos del proyecto elegido; alimenta la tabla de la vista sin recargar la página.
     */
    public function counts()
    {
        $proyecto = $this->resolverProyecto($_GET['project_id'] ?? null);
        if ($proyecto === null) {
            http_response_code(404);
            $this->json(['success' => false, 'message' => 'Proyecto no encontrado.']);
            return;
        }

        $this->json([
            'success' => true,
            'proyecto' => [
                'id' => (int) $proyecto['Id'],
                'nombre' => $proyecto['Proyecto_Proceso'],
            ],
            'conteos' => $this->reset->contar((int) $proyecto['Id']),
        ]);
    }

    /**
     * Respalda y borra. El respaldo va FUERA de la transacción y a propósito antes: si no se puede
     * escribir el `.sql`, la operación se aborta sin tocar una sola fila.
     */
    public function run()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $proyecto = $this->resolverProyecto($_POST['project_id'] ?? null);
        if ($proyecto === null) {
            http_response_code(404);
            $this->json(['success' => false, 'message' => 'Proyecto no encontrado.']);
            return;
        }

        // La confirmación escrita es la última barrera contra el proyecto equivocado.
        $confirmacion = trim((string) ($_POST['confirmacion'] ?? ''));
        if ($confirmacion !== trim((string) $proyecto['Proyecto_Proceso'])) {
            $this->json([
                'success' => false,
                'message' => 'El nombre escrito no coincide con el del proyecto seleccionado.',
            ]);
            return;
        }

        $etapas = $_POST['etapas'] ?? [];
        if (!is_array($etapas) || $etapas === []) {
            $this->json(['success' => false, 'message' => 'Selecciona al menos una etapa.']);
            return;
        }

        $projectId = (int) $proyecto['Id'];

        try {
            $seleccion = $this->reset->expandir(array_values(array_map('strval', $etapas)));
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
            return;
        }

        try {
            $respaldo = $this->reset->respaldar($projectId, $seleccion);
        } catch (Throwable $e) {
            error_log('Limpieza PDC — respaldo fallido: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'No se pudo generar el respaldo, no se borró nada: ' . $e->getMessage(),
            ]);
            return;
        }

        try {
            $resultado = $this->reset->limpiar($projectId, $seleccion);
        } catch (Throwable $e) {
            error_log('Limpieza PDC — borrado revertido: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'El borrado falló y se revirtió. El respaldo quedó en ' . basename($respaldo) . '.',
            ]);
            return;
        }

        $totalBorrado = array_sum($resultado['borradas']);

        \Database::getInstance()->logActivity(
            'Plan de Compras',
            'LIMPIEZA',
            sprintf(
                "Se limpió el PDC del proyecto '%s' (etapas: %s). %d filas borradas. Respaldo: %s",
                $proyecto['Proyecto_Proceso'],
                implode(', ', $resultado['etapas']),
                $totalBorrado,
                basename($respaldo),
            ),
            $proyecto['Base_de_Datos'],
        );

        $this->json([
            'success' => true,
            'message' => sprintf('%d filas borradas en las etapas: %s.', $totalBorrado, implode(', ', $resultado['etapas'])),
            'respaldo' => basename($respaldo),
            'borradas' => $resultado['borradas'],
            'conteos' => $resultado['conteos'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolverProyecto(mixed $id): ?array
    {
        if (!is_scalar($id) || (int) $id <= 0) {
            return null;
        }

        $proyecto = $this->projectModel->find((int) $id);

        return is_array($proyecto) ? $proyecto : null;
    }
}
