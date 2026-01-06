<?php
// Sanitizar nombre de archivo: eliminar espacios y caracteres no permitidos (solo letras, números, guiones y puntos)
$nombre = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $_POST["nombreArchivo"]);
$nombre = basename($nombre); // Evitar navegación de directorios (Path Traversal)

$direccion = __DIR__ . "/$nombre"; 

if (file_exists($direccion)) {
    $status  = unlink($direccion) ? 'The file '.$nombre.' has been deleted' : 'Error deleting '.$nombre;
    //echo $status;
}else{
    //echo 'The file '.$direccion.' does not exist';
}

sleep(0.5);

move_uploaded_file(
    $_FILES['pdf']['tmp_name'], 
    $direccion
);

if (file_exists($direccion)) {
    $status  = 'The file '.$nombre.' has been created';
    $array["status"] = $status;
    $array["nombre"] = $nombre;
    $json_codificado = json_encode($array, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
}else{
    echo 'The file '.$direccion.' does not exist';
}
?>