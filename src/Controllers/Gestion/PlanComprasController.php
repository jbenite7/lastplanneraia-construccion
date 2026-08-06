<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Support\SesionUsuario;

/**
 * Shell de la isla React del Plan de Compras v2.
 * El bundle se compila desde `pdc-app/` (`npm run build`) directo a public/pdc-app/,
 * de donde se sirve. Ver docs/pdc-v2.md.
 */
class PlanComprasController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->authorizePermission('lps.pdc.ver', 'No autorizado para consultar el plan de compras.');

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            // Sin proyecto activo no hay contexto: volver al selector de proyectos.
            header('Location: /');
            return;
        }

        $bootstrap = [
            'projectId' => $projectId,
            'proyectoNombre' => (string) ($_SESSION['proyecto'] ?? ''),
            'usuario' => (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            'usuarioId' => SesionUsuario::resolverId($this->db),
            'rol' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
            'csrfToken' => CsrfTokenManager::generate('plan_compras_v2'),
        ];

        $bootstrapJson = json_encode(
            $bootstrap,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );

        // Ítem del sidebar que queda marcado como activo. Si no casara con ningún id declarado en
        // shell_sidebar.php, `sidebarNavigation()` lanza InvalidArgumentException y la página
        // entera truena — no se degrada solo la marca.
        $shellActive = 'plan-compras';
        $shellModuleLabel = 'Plan de Compras';

        // Shell sidebar (DS-027): el PDC no maneja semanas (la vista apaga el chip),
        // pero los flyouts de semana de PG/PI/PS en la lateral sí las necesitan.
        $shellWeeks = [];
        $dbName = (string) ($_SESSION['db'] ?? '');
        if ($dbName !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            try {
                $tSaShell = \TableResolver::resolveByPrefix($dbName, 'semanas_activas');
                $projectIdShell = \TableResolver::getProjectIdByPrefix($dbName);
                $stmtShellWeeks = $this->db->queryWithProject(
                    "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSaShell} WHERE project_id = ? ORDER BY Semana DESC",
                    [$projectIdShell]
                );
                $shellWeeks = $stmtShellWeeks->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                error_log('Error cargando semanas para el shell PDC: ' . $e->getMessage());
            }
        }

        $bundlePath = PROJECT_ROOT . '/public/pdc-app/assets/pdc.js';
        $assetVersion = is_file($bundlePath) ? (int) filemtime($bundlePath) : 0;

        // tokens.css evoluciona con lps-aia, no con el bundle: cache-busting propio.
        $tokensPath = PROJECT_ROOT . '/public/css/tokens.css';
        $tokensVersion = is_file($tokensPath) ? (int) filemtime($tokensPath) : 0;

        require PROJECT_ROOT . '/views/plan-compras/app.view.php';
    }
}
