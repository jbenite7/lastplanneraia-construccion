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
        header("Location: profesionales/profesionales.php");
    }else if($seccion=='info_subcontratistas'){
        header("Location: subcontratistas/subcontratistas.php");
    }else if($seccion=='programa_general'){
        header("Location: programa_general/programa_general.php");
    }else if($seccion=='programacion_intermedia'){
        header("Location: programacion_intermedia/programacion_intermedia.php");
    } else if($seccion=='programacion_semanal'){
        header("Location: programacion_semanal/programacion_semanal.php");
    }else if($seccion=='CNP'){
        header("Location: programacion_semanal/CNP.php");
    }else if($seccion=='CNC'){
        header("Location: programacion_semanal/CNC.php");
    }else if($seccion=='CIC'){
        header("Location: programacion_semanal/CIC.php");
    }else if($seccion=='indicadores'){
        header("Location: indicadores/indicadores.php");
    }else if($seccion=='informe_productividad'){
        header("Location: informe_productividad/informe_productividad.php");
    }else if($seccion=='info_listadoActividades'){
        header("Location: listadoActividades/listadoActividades.php");
    }else if($seccion=='info_contratos'){
        header("Location: contratos/posicion_contratos.php?posicion_contratos=0");
    }else if($seccion=='info_paquetesContratacion'){
        header("Location: paquetesContratacion/paquetesContratacion.php");
    }else if($seccion=='planCompras'){
        header("Location: pdc/pdc.php");
    }else if($seccion=='actualizarCronograma'){
        header("Location: programaGeneralActualizar/programaGeneralActualizar.php");
    }else if($seccion=='controlCambios'){
        header("Location: controlCambios/controlCambios.php");
    }


} else {
    header('Location: login/login.php');
}
?>
