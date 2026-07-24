<?php

// 20260724_pdc_v2_seed_paquetes_aia.php
// PDC v2 / Fase A3: siembra el catálogo global general_paquetes_contratacion con los 188 paquetes
// reales de AIA (extraídos del bundle de analisis-presupuestos.web.app v33). Idempotente: usa
// PaquetesService::crearPaquete (dedupe por nombre_norm) → re-aplicar no duplica ni pisa ediciones.
// Uso:  php database/migrations/20260724_pdc_v2_seed_paquetes_aia.php           (dry-run)
//       php database/migrations/20260724_pdc_v2_seed_paquetes_aia.php --apply   (aplica)

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$jsonPath = __DIR__ . '/../seeds/paquetes_contratacion_aia.json';

$raw = file_get_contents($jsonPath);
if ($raw === false) {
    fwrite(STDERR, "No se pudo leer {$jsonPath}\n");
    exit(1);
}
$data = json_decode($raw, true);
$paquetes = $data['paquetes'] ?? null;
if (!is_array($paquetes) || $paquetes === []) {
    fwrite(STDERR, "JSON de seed inválido o vacío.\n");
    exit(1);
}

$db = Database::getInstance();
$svc = new PaquetesService($db);

$creados = 0;
$existentes = 0;
$invalidos = 0;
foreach ($paquetes as $p) {
    $nombre = is_string($p['nombre'] ?? null) ? $p['nombre'] : '';
    $tipo = is_string($p['tipoNegociacion'] ?? null) ? $p['tipoNegociacion'] : '';
    if ($nombre === '' || !in_array($tipo, PaquetesService::TIPOS, true)) {
        $invalidos++;
        fwrite(STDERR, "  INVÁLIDO: " . json_encode($p, JSON_UNESCAPED_UNICODE) . "\n");
        continue;
    }
    if (!$apply) {
        $existe = (int) $db->query(
            'SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ?',
            [mb_substr(\App\Services\Pdc\MaestroInsumosService::normalizar($nombre), 0, 200)],
        )->fetchColumn() > 0;
        $existe ? $existentes++ : $creados++;
        continue;
    }
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-tomas');
    if ($r['ok'] !== true) {
        $invalidos++;
        fwrite(STDERR, "  RECHAZADO por el servicio: {$nombre}\n");
        continue;
    }
    ((int) ($r['paquete']['existente'] ?? 0) === 1) ? $existentes++ : $creados++;
}

$total = count($paquetes);
$modo = $apply ? 'APLICADO' : 'DRY-RUN';
fwrite(STDOUT, "[{$modo}] total={$total}  crear={$creados}  ya-existen={$existentes}  invalidos={$invalidos}\n");
if (!$apply) {
    fwrite(STDOUT, "Ejecuta con --apply para sembrar.\n");
}
exit($invalidos === 0 ? 0 : 1);
