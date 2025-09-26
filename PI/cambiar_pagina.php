<?php session_start();
ob_start();
if (isset($_SESSION['usuario'])) {
    $seccion=$_GET['seccion'];
    $semana=$_GET['semana'];
    $_SESSION['semana']=$semana;
    $_SESSION['seccion']=$seccion;
    
    if($seccion=='contenido'){
        header("Location: contenido/contenido.php");
    }else if($seccion=='info_profesionales'){
        header("Location: informacion_general/profesionales/profesionales.php");
    }else if($seccion=='info_subcontratistas'){
        header("Location: informacion_general/subcontratistas/subcontratistas.php");
    }else if($seccion=='programa_general'){
        header("Location: programa_general/programa_general.php");
    }else if($seccion=='tramites'){
        header("Location: tramites/tramites.php");
    }else if($seccion=='consultores'){
        header("Location: consultores/consultores.php");
    }else if($seccion=='tareas_periodicas_simples'){
        header("Location: tareas_periodicas_simples/tareas_periodicas_simples.php");
    }else if($seccion=='tareas_periodicas_compuestas'){
        header("Location: tareas_periodicas_compuestas/tareas_periodicas_compuestas.php");
    }else if($seccion=='tareas_propias'){
        header("Location: tareas_propias/tareas_propias.php");
    }else if($seccion=='programacion_semanal'){
        header("Location: programacion_semanal/programacion_semanal_tramites.php");
    }else if($seccion=='programacion_semanal_tramites'){
        header("Location: programacion_semanal/programacion_semanal_tramites.php");
    }else if($seccion=='programacion_semanal_consultores'){
        header("Location: programacion_semanal/programacion_semanal_consultores.php");
    }else if($seccion=='programacion_semanal_periodicas_simples'){
        header("Location: programacion_semanal/programacion_semanal_periodicas_simples.php");
    }else if($seccion=='programacion_semanal_periodicas_compuestas'){
        header("Location: programacion_semanal/programacion_semanal_periodicas_compuestas.php");
    }else if($seccion=='programacion_semanal_propias'){
        header("Location: programacion_semanal/programacion_semanal_propias.php");
    }else if($seccion=='evaluacion_semanal' || $seccion=='CNP'){
        header("Location: programacion_semanal/CNP.php");
    }else if($seccion=='CNC'){
        header("Location: programacion_semanal/CNC.php");
    }else if($seccion=='CIC'){
        header("Location: programacion_semanal/CIC.php");
    }else if($seccion=='indicadores'){
        header("Location: indicadores/indicadores.php");
    }
    
    
} else {
    header('Location: login/login.php');
}
?>