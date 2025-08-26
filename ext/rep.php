<?php session_start();
    $_SESSION['usuario'] = "ok";
// Establecer tiempo de vida de la sesión en segundos
    $inactividad = 3600
        ;
    // Comprobar si $_SESSION["timeout"] está establecida
    if(isset($_SESSION["timeout"])){
        // Calcular el tiempo de vida de la sesión (TTL = Time To Live)
        $sessionTTL = time() - $_SESSION["timeout"];
        if($sessionTTL > $inactividad){
            echo "<script> alert('Se cerrará la sesión por un tiempo de inactividad mayor a 1 hora.');
                            window.location.href='../cerrar.php';</script>";
        }
    }

if (isset($_SESSION['usuario'])) {
    $db=$_GET['b']; 
    $_SESSION['db']=$db;
    
    require ("../conexion.php");
    $query="SELECT * FROM general_proyectos_procesos WHERE Base_de_Datos='$db'";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $proyecto=$data["Proyecto_Proceso"];
    
    $_SESSION['proyecto']=$proyecto;
    $_SESSION['semana']=$_GET['w'];
    $_SESSION['permiso']="V";
    
    //echo $_SESSION['db'] .", ". $_SESSION['proyecto'] .", ". $_SESSION['semana'];
    require 'views/indicadores.view.php';
    
    // El siguiente key se crea cuando se inicia sesión
    $_SESSION["timeout"] = time();
    
} else {
    header('Location: ../login/login.php');
}

session_destroy();

?>