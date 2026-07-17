<?php

$source = file_get_contents(__DIR__ . '/../src/Controllers/Api/ContratosApiController.php');
if ($source === false) {
    fwrite(STDERR, "No se pudo leer ContratosApiController.php\n");
    exit(1);
}

if (str_contains($source, '$emptyRow') || str_contains($source, '$arreglo["data"][] = $emptyRow')) {
    fwrite(STDERR, "La API de Contratos todavía fabrica un registro vacío cuando no hay datos.\n");
    exit(1);
}

fwrite(STDOUT, "OK: la lista vacía de Contratos no contiene registros sintéticos.\n");
