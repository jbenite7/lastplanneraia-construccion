<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

$fuente = file_get_contents(__DIR__ . '/../src/Legacy/nueva_semana.php');
$fallos = [];

if (!str_contains($fuente, 'LineaBaseContractualService')) {
    $fallos[] = 'nueva_semana.php no invoca el sembrado de la linea base';
}
if (!str_contains($fuente, 'sembrarSiFalta')) {
    $fallos[] = 'nueva_semana.php no llama a sembrarSiFalta';
}
// El legado se toca lo minimo: una sola invocacion, no logica.
if (substr_count($fuente, 'sembrarSiFalta') > 1) {
    $fallos[] = 'el sembrado aparece mas de una vez: la logica debe vivir en el servicio';
}

if ($fallos) { foreach ($fallos as $f) { echo "FAIL: $f\n"; } exit(1); }
echo "OK: la consolidacion de semana siembra la linea base\n";
