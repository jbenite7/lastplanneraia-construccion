<?php session_start();

// Establecer tiempo de vida de la sesión en segundos
    $inactividad = 1800;
    // Comprobar si $_SESSION["timeout"] está establecida
    if(isset($_SESSION["timeout"])){
        // Calcular el tiempo de vida de la sesión (TTL = Time To Live)
        $sessionTTL = time() - $_SESSION["timeout"];
        if($sessionTTL > $inactividad){
            echo "<script> alert('Se cerrará la sesión por un tiempo de inactividad mayor a 30 minutos.');
                            window.location.href='../cerrar.php';</script>";
        }
    }

if (isset($_SESSION['usuario'])) {
    require 'views/tareas_periodicas_compuestas.view.php'; 
    
    // El siguiente key se crea cuando se inicia sesión
    $_SESSION["timeout"] = time();
    
} else {
    header('Location: ../login/login.php');

}

?>