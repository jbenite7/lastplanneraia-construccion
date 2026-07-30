<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\MaestroSincoImportService;
use App\Services\Pdc\MaestroSincoParser;
use App\Services\Pdc\PresupuestoImportStore;

/**
 * Import del maestro SINCO (PDC v2 / Fase A2.5). Escritura del catálogo global
 * de insumos: RBAC lps.pdc.maestro + CSRF. Sesión garantizada por SessionMiddleware.
 */
class PlanComprasMaestroImportController
{
    use PlanComprasJsonRespuestas;

    public const MAX_BYTES = 10_485_760; // 10MB

    private \Database $db;
    private MaestroSincoImportService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new MaestroSincoImportService($this->db, new PresupuestoImportStore(), new MaestroSincoParser());
    }

    /** POST /plan-compras/api/maestro/importar/preview */
    public function preview(): void
    {
        if (!$this->guardEscritura()) {
            return;
        }
        $archivo = $_FILES['archivo'] ?? null;
        $errorSubida = is_array($archivo) ? (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if (in_array($errorSubida, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $this->fail('FILE_TOO_LARGE', 'El archivo supera el límite de 10MB.', 413);
            return;
        }
        if (!is_array($archivo) || $errorSubida !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            $this->fail('INVALID_FILE', 'No llegó ningún archivo válido.', 422);
            return;
        }
        if ((int) $archivo['size'] > self::MAX_BYTES) {
            $this->fail('FILE_TOO_LARGE', 'El archivo supera el límite de 10MB.', 413);
            return;
        }
        $nombre = mb_substr((string) ($archivo['name'] ?? 'maestro.xlsx'), 0, 255);
        if (!preg_match('/\.xlsx$/i', $nombre)) {
            $this->fail('INVALID_FILE', 'Solo se aceptan archivos .xlsx.', 422);
            return;
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'], true)) {
            $this->fail('INVALID_FILE', 'El archivo no es un Excel .xlsx válido.', 422);
            return;
        }

        try {
            $r = $this->service->preview($archivo['tmp_name'], $nombre, (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')));
        } catch (\PhpOffice\PhpSpreadsheet\Exception) {
            $this->fail('INVALID_FILE', 'El archivo no es un Excel .xlsx válido.', 422);
            return;
        } catch (\RuntimeException $e) {
            $this->fail('INVALID_FILE', $e->getMessage(), 422);
            return;
        }

        if (!$r['ok']) {
            $this->fail('VALIDATION_FAILED', 'El archivo tiene errores; no se importó nada.', 422, ['errores' => $r['errores']]);
            return;
        }
        $this->ok(['importToken' => $r['importToken'], 'resumen' => $r['resumen']]);
    }

    /** POST /plan-compras/api/maestro/importar/confirmar */
    public function confirmar(): void
    {
        if (!$this->guardEscritura()) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $token = (string) ($body['importToken'] ?? '');
        $r = $this->service->confirmar($token);
        if (!$r['ok']) {
            if ($r['code'] === 'TOKEN_EXPIRED') {
                $this->fail('TOKEN_EXPIRED', 'La previsualización expiró o ya fue usada. Sube el archivo de nuevo.', 410);
            } else {
                $this->fail('INVALID_FILE', 'El archivo temporal ya no es válido. Sube el archivo de nuevo.', 422);
            }
            return;
        }
        // `reenganchados` es la respuesta a «cargué el maestro, ¿y mis pendientes?»: dice cuántos
        // vínculos que esperaban un insumo lo encontraron en esta carga. Sin él, la carga informa de
        // lo que entró al catálogo y calla lo que eso resolvió, que es lo que la obra estaba mirando.
        $this->ok(['creados' => $r['creados'], 'actualizados' => $r['actualizados'], 'enriquecidos' => $r['enriquecidos'], 'conflictos' => $r['conflictos'], 'reenganchados' => $r['reenganchados']]);
    }

    /** RBAC maestro + proyecto activo + CSRF. true si pasa; false y ya respondió si no. */
    private function guardEscritura(): bool
    {
        if (!(new RbacService($this->db))->can('lps.pdc.maestro')) {
            $this->fail('FORBIDDEN', 'No autorizado para administrar el maestro de insumos.', 403);
            return false;
        }
        if ((int) ($_SESSION['project_id'] ?? 0) <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return false;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return false;
        }
        return true;
    }
}
