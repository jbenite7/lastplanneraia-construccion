<?php session_start();

require("../conexion.php");

if (isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
}

if ($_SERVER['REQUEST_METHOD']== 'POST'){
    $nombre=$_POST['nombre']/*filter_var(strtolower($_POST['nombre']), FILTER_SANITIZE_STRING)*/;
    $email=filter_var(strtolower($_POST['email']), FILTER_SANITIZE_EMAIL);
    $cargo=$_POST['cargo']/*filter_var(strtolower($_POST['cargo']), FILTER_SANITIZE_STRING)*/;
    $proyecto=$_POST['proyecto'];
    $permisos="R"/*$_POST['permisos']*/;
    $usuario=filter_var(strtolower($_POST['usuario']), FILTER_SANITIZE_STRING);
    $password=$_POST['password'];
    $password2=$_POST['password2'];
    //echo $proyecto;

    
    
    $errores='';
    
    if(empty($nombre) or empty($email) or empty($cargo) or empty($proyecto) or empty($permisos) or empty($usuario) or empty($password) or empty($password2)){
        $errores .= '<li>Debe rellenar todos los campos.</li>';
    } else {
        
        $query="SELECT * FROM general_usuarios WHERE usuario ='$usuario' LIMIT 1";

        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $usuario1=$data["usuario"];
        
        if($usuario1 !=""){
            $errores .= '<li>El nombre de usuario ya existe.</li>';
        }
        
        $password = hash('sha512', $password);
        $password2 = hash('sha512', $password2);
        if($password <> $password2){
            $errores .= '<li>Las contraseñas no son iguales.</li>';
        }
    }
    
    if ($errores==''){
        $query1="INSERT INTO general_usuarios (id, nombre, email, cargo, proyecto, permiso, usuario, password) VALUES (null, '$nombre', '$email', '$cargo', '$proyecto', '$permisos', '$usuario', '$password2')";
        //print_r($query1);
        $resultado1= mysqli_query($conexion, $query1);
        header('Location: ../index.php');
    }
}

require 'views/registrate.view.php';


?>