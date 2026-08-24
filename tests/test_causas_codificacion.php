<?php
// @requiere: db
// Prueba: no hay textos de causa con codificación rota (mojibake) en programacion_semanal.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$db = Database::getInstance();
$malas = [];
foreach (['CNC', 'CNP', 'Categoria_CNC', 'Categoria_CNP'] as $col) {
    $filas = $db->query(
        "SELECT DISTINCT `$col` AS t FROM programacion_semanal WHERE `$col` <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($filas as $t) {
        if (preg_match('/[\x{FFFD}]|ń|Ã|Â/u', $t)) {
            $malas[] = "$col: $t";
        }
    }
}
if ($malas !== []) {
    echo "FALLA: " . count($malas) . " textos con codificación rota:\n  " . implode("\n  ", array_slice($malas, 0, 10)) . "\n";
    exit(1);
}
echo "PASA: catálogo de causas sin textos rotos\n";
exit(0);
