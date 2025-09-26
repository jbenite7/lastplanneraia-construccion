<?php session_start();

ob_start();
if(isset($_GET['proyecto'])){
    if (isset($_SESSION['usuario'])) {
        $proyecto=$_GET['proyecto'];
        $db=$_GET['db'];
        $semana=$_GET['semana'];
        $permiso=$_GET['p'];
        $_SESSION['proyecto']=$proyecto;
        $_SESSION['db']=$db;
        $_SESSION['semana']=$semana;
        $_SESSION['permiso']=$permiso;

        if($permiso=='V'){
            header("Location: programacion_semanal/programacion_semanal.php");
        }else if($permiso=='A'){
            header("Location: informacion_general/profesionales/profesionales.php");
        }else if($permiso=='P'){
            header("Location: informacion_general/profesionales/profesionales.php");
        }else if($permiso=='R'){
            header("Location: informacion_general/profesionales/profesionales.php");
        }else if($permiso=='G'){
            header("Location: programacion_semanal/CIC.php");
        }else if($permiso=='S'){
            header("Location: programacion_semanal/CIC.php");
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
