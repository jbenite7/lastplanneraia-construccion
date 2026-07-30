<?php
/**
 * Foto del plan de fechas de un proyecto, fila a fila y en texto comparable con `diff`.
 *
 * Existe para una sola cosa: defender que un cambio en el modelo NO altera el plan de las obras
 * que no usan lo nuevo. Se toma antes de tocar código, se vuelve a tomar después, y se comparan.
 * Comparar contando filas no sirve —un cambio sutil de fechas mantiene el conteo—, así que aquí
 * sale cada fila entera.
 *
 * Deliberadamente FUERA de la foto: `updated_at`, `calculado_por`, `asignado_por` y los `id`
 * autoincrementales. Cambian en cada corrida o al reinsertar sin que el plan sea distinto, y una
 * foto que cambia sola no prueba nada. Lo que se compara es lo que el usuario ve: qué paquete,
 * contra qué frente, con qué fechas y qué días por paso.
 *
 * Uso:
 *   docker compose exec app php tests/foto_plan_fechas.php [projectId] > foto.txt
 *   diff foto-antes.txt foto-despues.txt && echo "sin regresión"
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
if (class_exists(\Dotenv\Dotenv::class) && is_file($root . '/.env')) {
    \Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

$db = \Database::getInstance();
$projectId = (int) ($argv[1] ?? 73);

echo "# Foto del plan de fechas — proyecto {$projectId}\n";
echo "# Sin id, updated_at, calculado_por ni asignado_por: cambian solos y no son el plan.\n\n";

$fmt = static function (array $row): string {
    $partes = [];
    foreach ($row as $k => $v) {
        $partes[] = $k . '=' . ($v === null ? '~' : (string) $v);
    }
    return implode('  ', $partes);
};

echo "## Amarres (pdc_paquete_frente)\n";
$rows = $db->query(
    'SELECT paquete_id, unique_id, frente_nombre, fecha_ancla, semana_origen, origen, confianza,
            evidencia, confirmado_humano
       FROM pdc_paquete_frente WHERE project_id = ?
      ORDER BY paquete_id',
    [$projectId],
)->fetchAll(\PDO::FETCH_ASSOC);
echo 'filas: ' . count($rows) . "\n";
foreach ($rows as $r) {
    echo $fmt($r) . "\n";
}

echo "\n## Cabeceras del plan (pdc_plan_paquete)\n";
$rows = $db->query(
    'SELECT paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales, duracion_ref,
            duracion_provisional, responsable_user_id
       FROM pdc_plan_paquete WHERE project_id = ?
      ORDER BY paquete_id',
    [$projectId],
)->fetchAll(\PDO::FETCH_ASSOC);
echo 'filas: ' . count($rows) . "\n";
foreach ($rows as $r) {
    echo $fmt($r) . "\n";
}

echo "\n## Pasos del plan (pdc_plan_paso)\n";
$rows = $db->query(
    'SELECT paquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin, fecha_real
       FROM pdc_plan_paso WHERE project_id = ?
      ORDER BY paquete_id, orden, paso_id',
    [$projectId],
)->fetchAll(\PDO::FETCH_ASSOC);
echo 'filas: ' . count($rows) . "\n";
foreach ($rows as $r) {
    echo $fmt($r) . "\n";
}

echo "\n## Asignación de insumos (pdc_insumo_paquete) — conteo por paquete\n";
$rows = $db->query(
    'SELECT paquete_id, COUNT(*) insumos
       FROM pdc_insumo_paquete WHERE project_id = ?
      GROUP BY paquete_id ORDER BY paquete_id',
    [$projectId],
)->fetchAll(\PDO::FETCH_ASSOC);
echo 'filas: ' . count($rows) . "\n";
foreach ($rows as $r) {
    echo $fmt($r) . "\n";
}
