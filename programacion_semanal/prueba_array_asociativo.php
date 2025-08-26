<?php

$array = "a;b;c";


$array = explode(";", $array);
$array_final["data"]["a"]=1;

for($i=1; $i< count($array)+1; $i++){
    $array_final["data"]["R$i"] = $array[$i-1];
}

print_r($array_final);
$json_codificado = json_encode($array_final, JSON_UNESCAPED_UNICODE);
echo "<br>" . utf8_decode($json_codificado);



?>