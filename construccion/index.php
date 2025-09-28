<?php session_start();


ob_start();
if(isset($_GET['proyecto'])){
    if (isset($_SESSION['usuario'])) {
        $proyecto=$_GET['proyecto'];
        $db=$_GET['db'];
        $semana=$_GET['semana'];
        $permiso=$_GET['p'];
        $pdcActivo=$_GET['pdcActivo'];
        $nombreUsuario=$_GET['nombreUsuario']; 
        $_SESSION['proyecto']=$proyecto;
        $_SESSION['db']=$db;
        $_SESSION['semana']=$semana;
        $_SESSION['permiso']=$permiso;
        $_SESSION['pdcActivo']=$pdcActivo;
        $_SESSION['nombreUsuario']=$nombreUsuario;

        if($permiso=='V'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='A'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='P'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='R'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='OT'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='G'){
            header("Location: programacion_semanal/CIC.php");
        }else if($permiso=='S'){
            header("Location: programacion_semanal/CIC.php");
        }else if($permiso=='SG'){
            header("Location: programacion_semanal/CIC.php");
        }else if($permiso=='DCV'){
            header("Location: programa_general/programa_general.php");
        }else if($permiso=='C'){
            header("Location: programacion_semanal/programacion_semanal.php");
        }


    } else {
        header('Location: login/login.php');
    }
}else{
    header('Location: cerrar.php');
}
?>
