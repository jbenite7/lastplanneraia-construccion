<?php

declare(strict_types=1);

// Repara textos de causa con codificación rota en programacion_semanal. Dry-run por defecto.
//
// Importante: las comparaciones usan LIKE BINARY. Con LIKE normal, la colación
// utf8mb4_general_ci de estas columnas trata "ń" (U+0144) y "ñ" (U+00F1) como
// equivalentes, así que un LIKE '%Diseńos%' sin BINARY también matchea filas que
// ya tienen el texto correcto "Diseños" (comprobado en Categoria_CNC/Categoria_CNP:
// 34 y 82 falsos positivos con LIKE normal, 0 con LIKE BINARY). Aplicar sin BINARY
// arriesgaría reescribir texto que ya estaba bien.
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$aplicar = in_array('--aplicar', $argv, true);

if ($aplicar && getenv('AUTORIZADO_POR_FELIPE') !== '1') {
    fwrite(STDERR, "Falta autorización: exporta AUTORIZADO_POR_FELIPE=1 antes de --aplicar.\n");
    exit(1);
}

$db = Database::getInstance();

$reemplazos = [
    'Diseńos' => 'Diseños',
    'Diseńo'  => 'Diseño',
];

foreach (['CNC', 'CNP', 'Categoria_CNC', 'Categoria_CNP'] as $col) {
    foreach ($reemplazos as $malo => $bueno) {
        $n = (int) $db->query(
            "SELECT COUNT(*) FROM programacion_semanal WHERE `$col` LIKE BINARY ?",
            ["%$malo%"]
        )->fetchColumn();
        if ($n === 0) {
            continue;
        }
        printf("%s: %d filas con «%s» → «%s»%s\n", $col, $n, $malo, $bueno, $aplicar ? '' : ' (dry-run)');
        if ($aplicar) {
            $db->query(
                "UPDATE programacion_semanal SET `$col` = REPLACE(`$col`, ?, ?) WHERE `$col` LIKE BINARY ?",
                [$malo, $bueno, "%$malo%"]
            );
        }
    }
}
echo $aplicar ? "Aplicado.\n" : "Dry-run. Repetir con --aplicar (y AUTORIZADO_POR_FELIPE=1) para escribir.\n";
