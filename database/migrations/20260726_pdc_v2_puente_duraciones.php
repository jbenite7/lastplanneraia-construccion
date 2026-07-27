<?php

// 20260726_pdc_v2_puente_duraciones.php
// PDC v2 / Fase A3.3 — puente entre el catálogo de paquetes y las duraciones legacy (habilita A4).
//
// El módulo de contratos en producción busca las duraciones del proceso por NOMBRE
// (`loadPdcDurationRow()` sobre `general_dias_procesos_contratacion`), donde las filas se llaman
// «CARPINTERÍA DE MADERA» o «CONCRETO». Los paquetes de A3 llevan prefijo de tipo: «Sum + Inst
// CARPINTERÍA DE MADERA», «Suministro CONCRETO». Hoy no encuentra fila, así que A4 no derivaría
// ninguna fecha.
//
// Decisión del usuario (grilleo 2026-07-26): «usar los nombres nuevos y adoptar las duraciones
// legacy». Renombrar los paquetes rompería la app de contratos que ya está en producción, y
// renombrar el catálogo legacy rompería su histórico — así que el puente es una columna.
//
// El emparejamiento quita el prefijo de tipo del nombre nuevo y compara normalizado. Las dos
// taxonomías vienen de la misma fuente (205 filas legacy: 107 Sum+Inst / 64 Suministro / 30 Mano de
// Obra / 3 Orden de Compra, contra los 188 paquetes reales de AIA), así que la mayoría casa directo.
// Lo que no casa se queda en NULL y se reporta: A4 tendrá que resolverlo con un default por
// modalidad, no inventando una duración.
//
// Uso:  php database/migrations/20260726_pdc_v2_puente_duraciones.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

/** Quita el prefijo de tipo del nombre de paquete para poder compararlo con el catálogo legacy. */
$sinPrefijo = static function (string $nombre): string {
    $n = trim($nombre);
    foreach (['Sum + Inst ', 'Sum+Inst ', 'Suministro ', 'M. de O ', 'M de O ', 'M.O. '] as $p) {
        if (mb_stripos($n, $p) === 0) {
            return trim(mb_substr($n, mb_strlen($p)));
        }
    }
    return $n;
};

$falta = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    ['general_paquetes_contratacion', 'duracion_ref'],
)->fetchColumn() === 0;

// Índice del catálogo legacy por nombre normalizado.
$legacy = [];
foreach ($db->query('SELECT id, paqueteContratacion FROM general_dias_procesos_contratacion')->fetchAll(\PDO::FETCH_ASSOC) as $r) {
    $legacy[mb_substr(MaestroInsumosService::normalizar((string) $r['paqueteContratacion']), 0, 200)] = (int) $r['id'];
}

$paquetes = $db->query('SELECT id, nombre FROM general_paquetes_contratacion WHERE activo = 1')->fetchAll(\PDO::FETCH_ASSOC);
$pares = [];
$huerfanos = [];
foreach ($paquetes as $p) {
    $clave = mb_substr(MaestroInsumosService::normalizar($sinPrefijo((string) $p['nombre'])), 0, 200);
    if (isset($legacy[$clave])) {
        $pares[(int) $p['id']] = $legacy[$clave];
    } else {
        $huerfanos[] = (string) $p['nombre'];
    }
}

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] columna duracion_ref: ' . ($falta ? 'FALTA (se añadirá)' : 'ya existe') . "\n");
    fwrite(STDOUT, sprintf("          emparejan %d de %d paquetes activos (%.0f%%)\n", count($pares), count($paquetes), 100 * count($pares) / max(1, count($paquetes))));
    fwrite(STDOUT, '          sin duración legacy: ' . count($huerfanos) . "\n");
    foreach (array_slice($huerfanos, 0, 15) as $h) {
        fwrite(STDOUT, "            · {$h}\n");
    }
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($falta) {
    $db->query(
        'ALTER TABLE general_paquetes_contratacion
         ADD COLUMN duracion_ref int NULL DEFAULT NULL AFTER admite_materiales,
         ADD KEY idx_gpc_duracion (duracion_ref)',
    );
}

foreach (array_chunk($pares, 200, true) as $lote) {
    foreach ($lote as $paqueteId => $refId) {
        $db->query('UPDATE general_paquetes_contratacion SET duracion_ref = ? WHERE id = ?', [$refId, $paqueteId]);
    }
}

fwrite(STDOUT, sprintf(
    "[APLICADO] puente listo: %d de %d paquetes activos apuntan a su fila de duraciones; %d quedan en NULL.\n",
    count($pares),
    count($paquetes),
    count($huerfanos),
));
exit(0);
