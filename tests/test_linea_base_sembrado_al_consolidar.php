<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

$fuente = file_get_contents(__DIR__ . '/../src/Legacy/nueva_semana.php');

// Se compara sobre el CÓDIGO, no sobre el texto: `str_contains` sobre el fuente crudo pasa
// igual con la línea comentada, y una asercion que pasa con el codigo desactivado no vigila
// nada. `token_get_all` marca los comentarios como T_COMMENT/T_DOC_COMMENT y aquí se tiran.
$codigo = '';
foreach (token_get_all($fuente) as $token) {
    if (is_array($token)) {
        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
            continue;
        }
        $codigo .= $token[1];
        continue;
    }
    $codigo .= $token;
}

$fallos = [];

if (!str_contains($codigo, 'LineaBaseContractualService')) {
    $fallos[] = 'nueva_semana.php no invoca el sembrado de la linea base';
}
if (!str_contains($codigo, 'sembrarSiFalta')) {
    $fallos[] = 'nueva_semana.php no llama a sembrarSiFalta';
}
// El legado se toca lo minimo: una sola invocacion, no logica.
if (substr_count($codigo, 'sembrarSiFalta') > 1) {
    $fallos[] = 'el sembrado aparece mas de una vez: la logica debe vivir en el servicio';
}

if ($fallos) { foreach ($fallos as $f) { echo "FAIL: $f\n"; } exit(1); }
echo "OK: la consolidacion de semana siembra la linea base\n";
