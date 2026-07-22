<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;

/**
 * Shell de la isla React del Plan de Compras v2.
 * El bundle se compila en el repo hermano `plan-de-compras` (npm run sync)
 * y se sirve desde public/pdc-app/ (ver docs/superpowers/specs/ de ese repo).
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
            'rol' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
            'csrfToken' => CsrfTokenManager::generate('plan_compras_v2'),
        ];

        $bootstrapJson = json_encode(
            $bootstrap,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $bundlePath = PROJECT_ROOT . '/public/pdc-app/assets/pdc.js';
        $assetVersion = is_file($bundlePath) ? (int) filemtime($bundlePath) : 0;

        require PROJECT_ROOT . '/views/plan-compras/app.view.php';
    }
}
