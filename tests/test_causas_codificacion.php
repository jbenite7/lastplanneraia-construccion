<?php
// @requiere: db
// Prueba: no hay textos de causa con codificación rota (mojibake) en programacion_semanal.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/ScopeFixture.php';

$db = Database::getInstance();
$malas = [];
foreach (['CNC', 'CNP', 'Categoria_CNC', 'Categoria_CNP'] as $col) {
    // El barrido es de higiene del catálogo de causas en TODA la base, no de una obra: un texto
    // roto lo escribe cualquier import y hay que verlo esté donde esté. Acotarlo a un proyecto
    // convertiría la prueba en una muestra y dejaría el resto sin mirar, así que va como
    // mantenimiento con su razón escrita.
    $filas = ScopeFixture::comoSistema(
        $db,
        'test:causas-codificacion:barrido-de-mojibake',
        static fn () => $db->query(
            "SELECT DISTINCT `$col` AS t FROM programacion_semanal WHERE `$col` <> ''"
        )->fetchAll(PDO::FETCH_COLUMN),
    );
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
