<?php session_start();

require("../conexion.php");

if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$errores = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = filter_var(strtolower($_POST['usuario']), FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $proyecto = $_POST['proyecto_login'];

    $stmt = $pdo->prepare("SELECT * FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? AND Area='Construccion'");
    $stmt->execute([$proyecto]);
    $data = $stmt->fetch();

    if ($data) {
        $db = $data["Base_de_Datos"];
        $acceso = $data["Acceso"];
        $pdcActivo = $data["pdcActivo"];

        $passwordHashed = hash('sha512', $password);
        $stmt1 = $pdo->prepare("SELECT * FROM general_usuarios WHERE usuario = ? AND password = ? AND (proyecto = ? OR proyecto='todos')");
        $stmt1->execute([$usuario, $passwordHashed, $proyecto]);
        $data1 = $stmt1->fetch();

        if ($data1) {
            $proyecto1 = $data1["proyecto"];
            $permiso = $data1['permiso'];
            $nombreUsuario = $data1['nombre'];

            if ($acceso == 0 && $permiso != 'P') {
                $errores .= "<li>El proyecto $proyecto se encuentra inactivo.</li>";
            } else {
                if (strtolower($proyecto) != strtolower($proyecto1) && $proyecto1 != 'Todos') {
                    $errores .= "<li>Este usuario no se encuentra autorizado para ingresar al proyecto $proyecto.</li>";
                } else {
                    iniciar_sesion($usuario, $proyecto, $permiso, $pdcActivo, $nombreUsuario, $db, $pdo);
                }
            }
        } else {
            $errores .= "Error: <li>Usuario, contraseña o proyecto incorrectos.</li>";
        }
    } else {
        $errores .= "Error: <li>El proyecto seleccionado no existe o no está configurado.</li>";
    }
}

require 'views/login.view.php';

function iniciar_sesion($usuario, $proyecto, $permiso, $pdcActivo, $nombreUsuario, $db, $pdo){
    $_SESSION['usuario'] = $usuario;

    // La conexión $pdo ya está disponible a través del parámetro de la función.
    $query2 = "SELECT MAX(Semana) AS max_semana FROM {$db}_semanas_activas";
    $stmt2 = $pdo->prepare($query2);
    $stmt2->execute();
    $data2 = $stmt2->fetch();
    
    $semana = $data2 && $data2['max_semana'] ? $data2['max_semana'] : 0;

    header("Location: ../index.php?proyecto=".$proyecto."&db=$db&semana=$semana&p=$permiso&pdcActivo=$pdcActivo&nombreUsuario=".urlencode($nombreUsuario));
    exit();
}
?>
