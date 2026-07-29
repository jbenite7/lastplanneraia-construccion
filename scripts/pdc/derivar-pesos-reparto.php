<?php
/**
 * Genera la constante `PlanFechasService::PESOS_REPARTO` desde el catálogo `general_dias_procesos_contratacion`.
 *
 * Cuando un paquete no tiene su fila de duración en el catálogo, el plan de compras reparte la
 * mediana de plazo de su tipo de negociación entre los siete pasos del proceso. Ese reparto necesita
 * un peso por paso; este script lo mide en vez de inventarlo.
 *
 * Método: la media de las proporciones fila a fila (cada fila del catálogo pesa igual, para que los
 * procesos largos no dominen la mezcla), sobre las filas con las siete columnas `dias*` no nulas y
 * total mayor que cero.
 *
 * No escribe nada: imprime el bloque PHP listo para pegar en `src/Services/Pdc/PlanFechasService.php`
 * y el diff contra la constante vigente. La constante se congela a propósito —el catálogo es legacy
 * y se edita fuera de este módulo, así que un valor derivado en caliente movería fechas ya
 * comunicadas sin dejar rastro— y `tests/test_pdc_v2_plan_fechas.php` vigila que no se quede vieja.
 *
 *   docker compose exec -T app php scripts/pdc/derivar-pesos-reparto.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PlanFechasService;

$db = Database::getInstance();
$svc = new PlanFechasService($db);

$completo = implode(' AND ', array_map(
    static fn (array $p): string => $p['col'] . ' IS NOT NULL',
    PlanFechasService::PASOS,
));
$suma = implode(' + ', array_map(static fn (array $p): string => $p['col'], PlanFechasService::PASOS));
$n = (int) $db->query(
    "SELECT COUNT(*) FROM (SELECT ({$suma}) t FROM general_dias_procesos_contratacion WHERE {$completo}) x WHERE t > 0",
)->fetchColumn();

if ($n === 0) {
    fwrite(STDERR, "No hay ninguna fila del catálogo con desglose completo: no se puede derivar nada.\n");
    exit(1);
}

$pesos = $svc->pesosDelCatalogo();
$vigentes = PlanFechasService::PESOS_REPARTO;
$hoy = (new DateTimeImmutable('today'))->format('Y-m-d');

printf("Catálogo: %d filas con desglose completo · %s\n\n", $n, $hoy);
printf("%-26s %10s %10s %10s\n", 'Paso', 'derivado', 'vigente', 'desvío');
foreach (PlanFechasService::PASOS as $i => $p) {
    printf(
        "%-26s %10.6f %10.6f %+9.1f%%\n",
        $p['paso'],
        $pesos[$i],
        $vigentes[$i],
        $pesos[$i] > 0 ? 100 * ($vigentes[$i] - $pesos[$i]) / $pesos[$i] : 0,
    );
}

$maxDesvio = 0.0;
foreach ($pesos as $i => $w) {
    $maxDesvio = max($maxDesvio, abs($w - $vigentes[$i]));
}
printf("\nDesvío absoluto máximo contra la constante vigente: %.6f\n", $maxDesvio);

echo "\n--- Bloque para PlanFechasService (actualiza también la fecha del docblock) ---\n";
printf("     * Última generación: %s, sobre las %d filas de `general_dias_procesos_contratacion`\n", $hoy, $n);
printf(
    "    public const PESOS_REPARTO = [%s];\n",
    implode(', ', array_map(static fn (float $w): string => number_format($w, 6, '.', ''), $pesos)),
);
exit(0);
