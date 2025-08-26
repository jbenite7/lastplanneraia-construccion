<?php

ini_set('display_errors', 1);
error_reporting( E_ALL );

$from = "notificaciones@lastplanneraia.com";
$to = "jbenitez@aia.com.co";
$asunto = "Prueba Correos LPS";
$mensaje = "<html>
            <head>
            <title>Compromisos Semanales Last Planner</title>
            </head>
            <body>
            <p>Buen día,</p>
            <br>
            <p>En el siguiente enlace pueden acceder a las actividades, correspondientes a la semana del 29/07/2021 al 05/07/2021, que fueron comprometidas durante la última reunión de Last Planner en el proyecto Concejo de Bogotá:</p>
            <br>
            <h2><a href='https://lastplanneraia.com/bdproveedores/bdproveedores.php'>Compromisos Last Planner</a></h2>
            
            <br>
            <p>Saludos,</p>
            
            <img src='https://lastplanneraia.com/prueba_correos/firma_2021.png' width='30%'>
            </body>
            </html>";
            
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";            
$headers .= "From: " . $from . "\r\n";

mail($to, $asunto, $mensaje, $headers);
echo "El Correo fue enviado";


?>