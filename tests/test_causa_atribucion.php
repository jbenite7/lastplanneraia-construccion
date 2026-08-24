<?php
// @requiere: puro
// Prueba: las funciones que arman las donas y el detalle de causas (CNP/CNC) no
// recortan el texto de la causa. El sufijo entre paréntesis --(obra)/(subcontratista)--
// es lo único que distingue tres causas que de otro modo comparten el mismo texto.
$spa = file_get_contents(__DIR__ . '/../public/js/modules/bi-spa.js');

// Las funciones responsables de las leyendas/etiquetas de las donas de causas y de
// su detalle (drilldown). Extraemos solo estos bloques: un recorte fuera de ellos
// (p. ej. "top 6 KPI" o "top 10 responsables") no borra la atribución de la causa
// y no debe hacer fallar esta prueba.
$funciones = [
    'renderCnpInsight',
    'renderCncInsight',
    'renderDoughnutChart',
    'renderCausalDrilldownTable',
    'renderCausalDrilldownCards',
    'renderCausalField',
    'renderCncExecution',
];

$bloques = [];
foreach ($funciones as $nombre) {
    if (!preg_match('/function ' . preg_quote($nombre, '/') . '\s*\([^)]*\)\s*\{/', $spa, $match, PREG_OFFSET_CAPTURE)) {
        echo "FALLA: no se encontró la función $nombre en bi-spa.js (¿se renombró?)\n";
        exit(1);
    }
    $start = $match[0][1];
    $braceStart = strpos($spa, '{', $start);
    $depth = 0;
    $end = $braceStart;
    for ($i = $braceStart, $len = strlen($spa); $i < $len; $i++) {
        if ($spa[$i] === '{') {
            $depth++;
        } elseif ($spa[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
    }
    $bloques[$nombre] = substr($spa, $start, $end - $start + 1);
}

$sospechas = [];
foreach ($bloques as $nombre => $bloque) {
    foreach (explode("\n", $bloque) as $n => $linea) {
        if (preg_match('/(substring|substr|slice)\s*\(\s*0\s*,\s*\d{1,3}\s*\)/', $linea)) {
            $sospechas[] = "$nombre línea " . ($n + 1) . ': ' . trim($linea);
        }
    }
}

if ($sospechas !== []) {
    echo "FALLA: hay recortes de texto que pueden borrar la atribución de la causa:\n  " . implode("\n  ", $sospechas) . "\n";
    exit(1);
}
echo "PASA: las funciones de donas y detalle de causas no recortan el texto\n";
exit(0);
