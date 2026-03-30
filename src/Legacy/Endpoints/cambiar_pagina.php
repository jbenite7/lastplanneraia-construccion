<?php

session_start();
ob_start();
if (isset($_SESSION['usuario'])) {
    $seccionAnterior = (string)($_SESSION['seccion'] ?? '');
    $seccion = $_GET['seccion'] ?? '';
    $semana = $_GET['semana'] ?? 0;
    $origen = trim((string)($_GET['origen'] ?? $seccionAnterior));

    $_SESSION['semana'] = $semana;
    $_SESSION['seccion'] = $seccion;

    if ($seccion === 'planCompras' && $origen !== '' && $origen !== 'planCompras') {
        $_SESSION['pdc_sync_on_load'] = true;
        $_SESSION['pdc_sync_origin'] = $origen;
    }

    if ($seccion == 'contenido') {
        header("Location: contenido/contenido.php");
    } elseif ($seccion == 'info_profesionales') {
        header("Location: profesionales/profesionales.php");
    } elseif ($seccion == 'info_subcontratistas') {
        header("Location: subcontratistas/subcontratistas.php");
    } elseif ($seccion == 'programa_general') {
        header("Location: /programa-general");
    } elseif ($seccion == 'programacion_intermedia') {
        header("Location: /programacion-intermedia");
    } elseif ($seccion == 'programacion_semanal') {
        header("Location: /programacion-semanal");
    } elseif ($seccion == 'CNP') {
        header("Location: /programacion-semanal/cnp");
    } elseif ($seccion == 'CNC') {
        header("Location: /programacion-semanal/cnc");
    } elseif ($seccion == 'CIC') {
        header("Location: /programacion-semanal/cic");
    } elseif ($seccion == 'indicadores') {
        header("Location: /indicadores");
    } elseif ($seccion == 'info_listadoActividades') {
        header("Location: /listado-actividades");
    } elseif ($seccion == 'info_contratos') {
        header("Location: /contratos");
    } elseif ($seccion == 'info_paquetesContratacion') {
        header("Location: /paquetes-contratacion");
    } elseif ($seccion == 'planCompras') {
        header("Location: /pdc");
    } elseif ($seccion == 'actualizarCronograma') {
        header("Location: /programa-general-actualizar");
    } elseif ($seccion == 'controlCambios') {
        header("Location: /control-cambios");
    }


} else {
    header('Location: login/login.php');
}
