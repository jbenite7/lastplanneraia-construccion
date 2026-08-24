<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Verifica que scripts/higiene/reparar-mojibake-causas.php excluya filas huérfanas
 * (unique_id sin fila correspondiente en `programa`) tanto en el UPDATE como en el
 * SELECT COUNT de diagnóstico. Sin el EXISTS, el UPDATE revienta con una violación de
 * llave foránea en cuanto toca una fila huérfana (336 conocidas en desarrollo).
 *
 * Hallazgo bloqueante de la revisión final de la Fase 0 de higiene de datos (Control Tower).
 */

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "PASA: {$descripcion}\n";
    } else {
        $fallos++;
        echo "FALLA: {$descripcion}\n";
    }
}

$ruta = __DIR__ . '/../scripts/higiene/reparar-mojibake-causas.php';
$contenido = file_get_contents($ruta);

if ($contenido === false) {
    echo "FAIL: no se pudo leer {$ruta}\n";
    exit(1);
}

preg_match_all(
    '/"(SELECT COUNT\(\*\)[^"]*|UPDATE programacion_semanal[^"]*)"\s*\.\s*EXISTE_EN_PROGRAMA/',
    $contenido,
    $consultas
);
comprobar('se encontraron el SELECT COUNT y el UPDATE, ambos concatenados con EXISTE_EN_PROGRAMA', count($consultas[1]) >= 2);

comprobar(
    'la constante EXISTE_EN_PROGRAMA compara project_id y unique_id contra programa',
    (bool) preg_match('/p\.project_id\s*=\s*programacion_semanal\.project_id/', $contenido)
        && (bool) preg_match('/p\.unique_id\s*=\s*programacion_semanal\.unique_id/', $contenido)
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
