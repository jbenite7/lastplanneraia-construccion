<?php
// tests/medicion_rematching_reprogramacion.php — MEDICIÓN, no test.
// Primer entregable del spec 2026-07-29-rematching-reprogramacion-design.md: qué hace HOY el
// sistema cuando se mueve un frente del cronograma. Deja evidencia en la salida.
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\PlanFechasService;
use App\Services\Pdc\SeguimientoService;

$db = Database::getInstance();
$P = 999950;

$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ?', [$P]);
    $db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'medicion-b2'");
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'MEDICION B2%'");
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$P]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$P]);
};
$limpiar();

// --- Fixture: una semana activa, dos frentes (encabezados). ---
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, 1, 1, ?, ?)',
    [$P, '2026-07-27', '2026-08-02'],
);
$frentes = [
    [1, 8801, '<b>ESTRUCTURA, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-09-01'],
    [2, 8802, '<b>ACABADOS, </b> <small>[Capítulo: TORRE 1]</small>', 1, '2026-10-01'],
];
foreach ($frentes as [$cons, $uid, $act, $tit, $ini]) {
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, unique_id, Actividad, Titulo, Fecha_Inicio)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$P, $cons, $uid, $act, $tit, $ini],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Actividad, Titulo, Fecha_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos,
             Predecesora, Pdto_Cons, Modelo, Activa, alerta_crisis, reprogramaciones_acumuladas)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, 0, "", "", "", "", "", "", "", 1, 0, 0)',
        [$P, 100 + $cons, $uid, $cons, $act, $tit, $ini],
    );
}

// --- Fixture: un paquete con desglose completo de duración. ---
$db->query(
    "INSERT INTO general_dias_procesos_contratacion
        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
         diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('MEDICION B2 CONCRETO', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)",
);
$durRef = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('MEDICION B2 CONCRETO', 'MEDICION B2 CONCRETO', 'a_todo_costo', 'contrato', ?, 1, 'medicion-b2', NOW())",
    [$durRef],
);
$paq = (int) $db->lastInsertId();

$svc = new PlanFechasService($db);
$seg = new SeguimientoService($db);

$dump = static function (string $titulo) use ($db, $P, $paq): array {
    $cab = $db->query(
        'SELECT unique_id, fecha_ancla, fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
        [$P, $paq],
    )->fetch(\PDO::FETCH_ASSOC) ?: [];
    $pasos = $db->query(
        'SELECT orden, paso, dias, fecha_inicio, fecha_fin, fecha_real FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? ORDER BY orden',
        [$P, $paq],
    )->fetchAll(\PDO::FETCH_ASSOC);
    echo "\n-- {$titulo}\n";
    echo '   cabecera: ' . json_encode($cab, JSON_UNESCAPED_UNICODE) . "\n";
    foreach ($pasos as $p) {
        printf("   %d %-28s dias=%-3d prog=%s→%s real=%s\n", $p['orden'], mb_substr((string) $p['paso'], 0, 28),
            (int) $p['dias'], (string) $p['fecha_inicio'], (string) $p['fecha_fin'], $p['fecha_real'] ?? '—');
    }
    return ['cab' => $cab, 'pasos' => $pasos];
};

echo "=== MEDICIÓN: qué hace hoy el sistema al mover un frente ===\n";

// 1. Amarrar + calcular contra el frente en su fecha original.
$svc->amarrar($P, $paq, 8801, 'medicion');
$r = $svc->calcular($P, 'medicion');
echo "\n[1] Amarrado a ESTRUCTURA (2026-09-01) y calculado: " . json_encode($r) . "\n";
$antes = $dump('Plan calculado contra el cronograma original');

// 2. Registrar una fecha REAL en el paso 1 (lo ocurrido).
$db->query(
    "UPDATE pdc_plan_paso SET fecha_real = '2026-08-05', registrado_por = 'medicion', registrado_at = NOW()
      WHERE project_id = ? AND paquete_id = ? AND orden = 1",
    [$P, $paq],
);
echo "\n[2] Se registra fecha_real = 2026-08-05 en el paso de orden 1.\n";

// 3. Mover el frente en el cronograma: +21 días.
$db->query("UPDATE programa SET Fecha_Inicio = '2026-09-22' WHERE project_id = ? AND unique_id = 8801", [$P]);
$db->query("UPDATE programa_consolidado SET Fecha_Inicio = '2026-09-22' WHERE project_id = ? AND unique_id = 8801", [$P]);
echo "\n[3] El frente ESTRUCTURA se reprograma de 2026-09-01 a 2026-09-22 (+21 días).\n";

// 4. ¿El plan cambió solo?
$despuesDelMovimiento = $dump('¿Cambió el plan por sí solo tras mover el frente?');
// Se comparan SOLO las columnas programadas: `fecha_real` la escribió el paso [2] de esta misma
// medición, así que meterla en la comparación haría pasar por «recálculo» lo que fue una escritura
// nuestra.
$soloProgramado = static fn (array $d): string => json_encode([
    $d['cab'],
    array_map(static fn (array $p): array => [$p['orden'], $p['dias'], $p['fecha_inicio'], $p['fecha_fin']], $d['pasos']),
]);
$cambioSolo = $soloProgramado($antes) !== $soloProgramado($despuesDelMovimiento);
echo '   => ¿recalculó solo?: ' . ($cambioSolo ? 'SÍ' : 'NO — el plan sigue con las fechas viejas') . "\n";

// 5. ¿Alguien avisa? -> desfases()
$d = $svc->desfases($P);
echo "\n[5] PlanFechasService::desfases() devuelve:\n   " . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n";
echo '   => ¿detecta el desfase?: ' . ($d !== [] ? 'SÍ' : 'NO') . "\n";
echo "   => ¿dice cuántos días se movió el PAQUETE (sus pasos)?: solo dice cuánto se movió el FRENTE.\n";

// 6. ¿Existe alguna simulación / delta previo?
$tieneSimulacion = method_exists($svc, 'simular') || method_exists($svc, 'previsualizar') || method_exists($svc, 'proyectar');
echo "\n[6] ¿Existe un método de simulación en PlanFechasService (simular/previsualizar/proyectar)?: "
    . ($tieneSimulacion ? 'SÍ' : 'NO — calcular() computa y escribe en el mismo bucle') . "\n";

// 7. ¿El tablero de vencimientos avisa de que el cronograma cambió?
$resumen = $seg->resumen($P);
$claves = $resumen === [] ? [] : array_keys(is_array($resumen[0] ?? null) ? $resumen[0] : $resumen);
echo "\n[7] SeguimientoService::resumen() — claves de su payload:\n   " . json_encode($claves, JSON_UNESCAPED_UNICODE) . "\n";
$avisa = false;
foreach ($claves as $k) {
    if (stripos((string) $k, 'desfas') !== false || stripos((string) $k, 'cronograma') !== false || stripos((string) $k, 'desactual') !== false) {
        $avisa = true;
    }
}
echo '   => ¿avisa de cronograma cambiado?: ' . ($avisa ? 'SÍ' : 'NO — no hay ninguna señal de «esto se calculó contra un cronograma viejo»') . "\n";

// 8. Recalcular: ¿sobrevive lo ocurrido?
$r2 = $svc->calcular($P, 'medicion');
echo "\n[8] Recalcular: " . json_encode($r2) . "\n";
$despues = $dump('Plan tras recalcular con el frente movido');
$real = $db->query(
    'SELECT fecha_real FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden = 1',
    [$P, $paq],
)->fetchColumn();
echo '   => ¿sobrevive fecha_real?: ' . ($real === '2026-08-05' ? 'SÍ (2026-08-05)' : 'NO (' . var_export($real, true) . ')') . "\n";
$corrimiento = (new \DateTimeImmutable((string) $antes['cab']['fecha_arranque']))
    ->diff(new \DateTimeImmutable((string) $despues['cab']['fecha_arranque']))->days;
echo "   => el arranque del paquete se movió {$corrimiento} día(s).\n";

// 9. ¿Queda desfase después de recalcular?
echo "\n[9] desfases() tras recalcular: " . json_encode($svc->desfases($P), JSON_UNESCAPED_UNICODE) . "\n";

// 10. Frente borrado del cronograma → huérfano.
$db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND unique_id = 8801', [$P]);
$db->query('DELETE FROM programa WHERE project_id = ? AND unique_id = 8801', [$P]);
echo "\n[10] Se borra el frente ESTRUCTURA del cronograma.\n";
echo '   desfases(): ' . json_encode($svc->desfases($P), JSON_UNESCAPED_UNICODE) . "\n";
$amarreVivo = $db->query('SELECT unique_id FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?', [$P, $paq])->fetchColumn();
echo '   => ¿se reamarró solo a otro frente?: ' . ($amarreVivo === false ? 'la fila desapareció' : ((int) $amarreVivo === 8801 ? 'NO — sigue apuntando al frente 8801 inexistente' : 'SÍ, a ' . $amarreVivo)) . "\n";

$limpiar();
echo "\n=== FIN DE LA MEDICIÓN (fixture limpiado) ===\n";
