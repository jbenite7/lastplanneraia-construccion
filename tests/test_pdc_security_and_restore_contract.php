<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, "PASS: {$message}\n");
        return;
    }

    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$controller = file_get_contents(__DIR__ . '/../src/Controllers/Api/PdcApiController.php') ?: '';
$autoController = file_get_contents(__DIR__ . '/../src/Controllers/Api/PdcAutoGenerateController.php') ?: '';
$semiAutoController = file_get_contents(__DIR__ . '/../src/Controllers/Api/SemiAutoController.php') ?: '';
$pageController = file_get_contents(__DIR__ . '/../src/Controllers/Gestion/PdcController.php') ?: '';
$view = file_get_contents(__DIR__ . '/../views/pdc/pdc.view.php') ?: '';
$viewerPermissions = RbacCatalog::fallbackPermissionsByRole()['V'] ?? [];

$assert(in_array('lps.pdc.ver', $viewerPermissions, true), 'V conserva consulta de PDC.');
$assert(!in_array('lps.pdc.editar', $viewerPermissions, true), 'V no recibe edición de PDC.');
$assert(!in_array('lps.pdc.auto_generar', $viewerPermissions, true), 'V no recibe auto-generación de PDC.');

$assert(
    str_contains($pageController, "CsrfTokenManager::generate('pdc_save')"),
    'La vista PDC recibe un token CSRF específico.'
);
$assert(
    str_contains($controller, "CsrfTokenManager::validate")
        && str_contains($controller, "'pdc_save'"),
    'Las mutaciones PDC validan el token CSRF.'
);
$assert(
    str_contains($autoController, "CsrfTokenManager::validate")
        && str_contains($autoController, "'pdc_save'"),
    'La aplicación automática desde Contratos valida el token CSRF de PDC.'
);
$assert(
    str_contains($semiAutoController, 'requirePdcCsrf()')
        && substr_count($semiAutoController, '$this->requirePdcCsrf();') >= 8,
    'Todas las mutaciones semi-automáticas de PDC validan CSRF.'
);
$assert(
    str_contains($view, 'meta name="csrf-token"')
        && str_contains($view, 'X-CSRF-Token'),
    'Las solicitudes AJAX de PDC envían el token CSRF.'
);

$assert(
    !str_contains($controller, 'UPDATE pdc SET $columna'),
    'PDC no construye SQL desde un nombre de columna enviado por el cliente.'
);
$assert(
    str_contains($controller, 'La ruta de actualización dinámica ya no está disponible.'),
    'La ruta dinámica heredada se rechaza explícitamente.'
);

$syncStart = strpos($controller, 'private function syncPdcQuantity');
$syncEnd = $syncStart === false ? false : strpos($controller, 'private function adjudicarContrato', $syncStart);
$syncBody = ($syncStart === false || $syncEnd === false)
    ? ''
    : substr($controller, $syncStart, $syncEnd - $syncStart);
$assert(
    str_contains($syncBody, 'papelera_pdc')
        && str_contains($syncBody, 'INSERT INTO papelera_pdc'),
    'Reducir cantidades respalda los subcontratos excedentes en papelera PDC.'
);
$assert(
    str_contains($syncBody, 'DELETE FROM pdc'),
    'La reducción elimina de PDC solamente después de preparar el respaldo restaurable.'
);
$assert(
    str_contains($controller, 'DELETE FROM papelera_pdc WHERE project_id = ? AND consecutivo = ? AND semana = ?'),
    'El reemplazo del respaldo PDC queda aislado por proyecto, registro y semana.'
);
$assert(
    str_contains($controller, "case \"restaurar_actividad_pdc\"")
        && str_contains($controller, 'private function restaurarActividad'),
    'PDC ofrece una operación autenticada para restaurar desde la papelera.'
);
$assert(
    str_contains($controller, "case \"nueva_sem\":\n                    \$this->requirePermission('lps.semana.crear'")
        && str_contains($controller, "case \"eliminar_sem\":\n                    \$this->requirePermission('lps.semana.eliminar'"),
    'Las operaciones de semanas exigen sus capacidades específicas además de edición PDC.'
);
$assert(
    str_contains($controller, 'No se puede restaurar un subcontrato mientras falten posiciones anteriores.'),
    'La restauración rechaza secuencias de subcontratos con huecos.'
);
$assert(
    str_contains($controller, 'DELETE FROM papelera_pdc')
        && str_contains($controller, 'INSERT INTO pdc'),
    'La restauración mueve el registro a PDC y solo después lo retira de la papelera.'
);

exit($failures === [] ? 0 : 1);
