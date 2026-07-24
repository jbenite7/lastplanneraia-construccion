<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;
use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

/**
 * Endpoints del importador de presupuesto (PDC v2 / Fase A1).
 * Flujo: preview (multipart, valida todo, no persiste) → confirmar (transaccional).
 * Sesión garantizada por SessionMiddleware global.
 */
class PlanComprasImportController
{
    use PlanComprasJsonRespuestas;

    public const MAX_BYTES = 10_485_760; // 10MB

    private \Database $db;
    private PresupuestoImportService $service;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->service = new PresupuestoImportService($this->db, new PresupuestoImportStore(), new PresupuestoExcelParser());
    }

    /** POST /plan-compras/api/presupuesto/preview */
    public function preview(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }

        $archivo = $_FILES['archivo'] ?? null;
        $errorSubida = is_array($archivo) ? (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if (in_array($errorSubida, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            // PHP rechazó el archivo antes de llegar al chequeo de bytes propio:
            // el código correcto sigue siendo "demasiado grande", no "inválido".
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
        $nombre = mb_substr((string) ($archivo['name'] ?? 'presupuesto.xlsx'), 0, 255);
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
            $r = $this->service->previewDesdeArchivo(
                $archivo['tmp_name'],
                $nombre,
                $projectId,
                (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            );
        } catch (\PhpOffice\PhpSpreadsheet\Exception) {
            // Mensajes del vendor pueden filtrar rutas internas del servidor: genericar.
            $this->fail('INVALID_FILE', 'El archivo no es un Excel .xlsx válido.', 422);
            return;
        } catch (\RuntimeException $e) {
            // Mensajes propios del parser (hoja faltante, columnas requeridas): curados y accionables.
            $this->fail('INVALID_FILE', $e->getMessage(), 422);
            return;
        }

        if (!$r['ok']) {
            $this->fail('VALIDATION_FAILED', 'El archivo tiene errores; no se importó nada.', 422, ['errores' => $r['errores']]);
            return;
        }
        $this->ok([
            'importToken' => $r['importToken'],
            'versionLabel' => $r['versionLabel'],
            'resumen' => $r['resumen'],
            'advertencias' => $r['advertencias'],
            'sinCambios' => $r['sinCambios'],
            'versionActiva' => $r['versionActiva'],
        ]);
    }

    /** POST /plan-compras/api/presupuesto/confirmar */
    public function confirmar(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $token = (string) ($body['importToken'] ?? '');

        $r = $this->service->confirmar($token, $projectId);
        if (!$r['ok']) {
            if ($r['code'] === 'TOKEN_EXPIRED') {
                $this->fail('TOKEN_EXPIRED', 'La previsualización expiró o ya fue usada. Sube el archivo de nuevo.', 410);
            } else {
                $this->fail('INVALID_FILE', 'El archivo temporal ya no es válido. Sube el archivo de nuevo.', 422);
            }
            return;
        }
        $this->ok([
            'versionId' => $r['versionId'],
            'versionNumero' => $r['versionNumero'],
            'versionLabel' => $r['versionLabel'],
            'versionIdAnterior' => $r['versionIdAnterior'],
            'sinCambios' => $r['sinCambios'],
            'resumen' => $r['resumen'],
        ]);
    }

    /** GET /plan-compras/api/presupuesto/versiones */
    public function versiones(): void
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $this->ok(['versiones' => $this->service->versiones($projectId)]);
    }

    /** GET /plan-compras/api/presupuesto/arbol[?versionId=N] — solo lectura. */
    public function arbol(): void
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $versionId = filter_var($_GET['versionId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $arbol = $this->service->arbol($projectId, $versionId === false ? null : $versionId);
        if ($arbol === null) {
            $this->fail('NO_VERSION', 'El proyecto no tiene un presupuesto importado (o la versión no existe).', 404);
            return;
        }
        $this->ok($arbol);
    }

    /** GET /plan-compras/api/presupuesto/comparar?versionA=N&versionB=M — solo lectura. */
    public function comparar(): void
    {
        if (!(new RbacService($this->db))->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }
        $va = filter_var($_GET['versionA'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $vb = filter_var($_GET['versionB'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($va === false || $va === null || $vb === false || $vb === null || $va === $vb) {
            $this->fail('PARAMS_INVALIDOS', 'Debes elegir dos versiones distintas para comparar.', 422);
            return;
        }
        $r = $this->service->comparar($projectId, (int) $va, (int) $vb);
        if ($r === null) {
            $this->fail('NO_VERSION', 'Alguna de las versiones no existe en este proyecto.', 404);
            return;
        }
        $this->ok($r);
    }

    /** RBAC importar + proyecto + CSRF para los POST. Retorna projectId o null (ya respondió). */
    private function guardEscritura(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.pdc.importar')) {
            $this->fail('FORBIDDEN', 'No autorizado para importar presupuestos.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return null;
        }
        return $projectId;
    }
}
