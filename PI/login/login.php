<?php session_start();

require("../conexion.php");

if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
}


$errores='';


if ($_SERVER['REQUEST_METHOD']== 'POST'){
    $usuario=filter_var(strtolower($_POST['usuario']), FILTER_SANITIZE_STRING);
    $password=$_POST['password'];
    $proyecto=$_POST['proyecto_login'];

    $query="SELECT * FROM general_proyectos_procesos WHERE Proyecto_Proceso ='$proyecto' AND Area='PI'";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $db=$data["Base_de_Datos"];

    require ("../conexion.php");
    $password=$_POST['password'];
    $query1="SELECT * FROM general_usuarios WHERE usuario ='$usuario' AND password ='".hash('sha512',$password)."'";

    $resultado1= mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    $proyecto1=$data1["proyecto"];
    $permiso=$data1['permiso'];

    if($proyecto1!=""){
        if((strtolower($proyecto)!=strtolower($proyecto1))){
            if($proyecto1!='Todos'){
                $errores .= "<li>Este usuario no se encuentra autorizado para ingresar al proyecto $proyecto.</li>";
            }else{
                iniciar_sesion($usuario, $proyecto, $permiso, $db);
            }
        }else{
            iniciar_sesion($usuario, $proyecto, $permiso, $db);
        }
    } else {
        $errores .= '<li>El nombre de usuario o la contraseña son incorrectos.</li>';
    }
}
require 'views/login.view.php';

function iniciar_sesion($usuario, $proyecto, $permiso, $db){
    $_SESSION['usuario'] = $usuario;

    require ("../conexion.php");
    $query2="SELECT MAX(Semana) FROM $db"."_semanas_activas";
    $resultado2= mysqli_query($conexion, $query2);
    $data2=mysqli_fetch_assoc($resultado2);
    $semana=$data2["MAX(Semana)"];

    header("Location: ../index.php?proyecto=".$proyecto."&db=$db&semana=$semana&p=$permiso");
}

?>
