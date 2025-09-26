<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Last Planner PI AIA</title>
    <link rel="icon" href="../imagenes/logo2.png">
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    
    <!-- Fuentes de Google-->
    <link href="https://fonts.googleapis.com/css?family=Roboto|Bree+Serif&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (Íconos)-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
    
    
    <!--Iniciar estilos de Bootstrap-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">
	
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../css/estilos4.css">
    
	<!-- Estilos Buttons DataTables -->
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css">
    
    <!-- Checkboxes DataTables -->
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css">




</head>
    
<!--Etiqueta superior-->
<body>
    
<header class="header">
    <div class="encabezado">
        <ul>
            <?php 
            $proyecto=$_SESSION['proyecto']; 
            $db=$_SESSION['db'];
            $semana=$_SESSION['semana'];
            $permiso=$_SESSION['permiso'];
            
            require ("../conexion.php");
            $query="SELECT COUNT(*) FROM $db"."_semanas_activas";
            $resultado= mysqli_query($conexion, $query);
            $data=mysqli_fetch_assoc($resultado);
            $conteo=$data["COUNT(*)"];
            
            if($conteo==0){
                $Fecha_Inicio_Sem=date("Y-m-d");
                $Fecha_Inicio_SemYMD=$Fecha_Inicio_Sem;
                $Fecha_Fin_Sem=date("Y-m-d",strtotime($Fecha_Inicio_Sem ."+7 days"));
                $Fecha_Fin_SemYMD=$Fecha_Fin_Sem;
                
                $Fecha_Inicio_Sem=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Inicio_Sem));
                $Fecha_Fin_Sem=date("Y, n - 1, d, H, i, s", strtotime($Fecha_Fin_Sem));
                
                $Fecha_datepicker=$Fecha_Inicio_Sem;
                //echo "$Fecha_Inicio_Sem <br> $Fecha_Fin_Sem";
 
            }else{
                $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado1= mysqli_query($conexion, $query1);
                $data1=mysqli_fetch_assoc($resultado1);
                $Fecha_Inicio_Sem=$data1["Fecha_Inicio_Sem"];
                $Fecha_Inicio_SemYMD=$Fecha_Inicio_Sem;
                $Fecha_Inicio_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Inicio_Sem"));
                
                $Fecha_Fin_Sem=$data1["Fecha_Fin_Sem"];
                $Fecha_Fin_SemYMD=$Fecha_Fin_Sem;
                $Fecha_Fin_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Fin_Sem"));
                
                require ("../conexion.php");
                $query2="SELECT Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado2= mysqli_query($conexion, $query2);
                $data2=mysqli_fetch_assoc($resultado2);
                $Fecha_datepicker=$data2["Fecha_Fin_Sem"];
                $Fecha_datepicker=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_datepicker"));
                
                $query3="SELECT Semana FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas)";
                $resultado3= mysqli_query($conexion, $query3);
                $data3=mysqli_fetch_assoc($resultado3);
                $Max_Semana=$data3["Semana"];
                //echo "<script>console.log($Max_Semana)</script>";
            }
            ?>
            <li><img src="../imagenes/logo.png" width="30%"></li>
            
            <?php
            $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
            $resultado2= mysqli_query($conexion, $query2);
            $data=mysqli_fetch_assoc($resultado2);
            $Fecha_Inicio_SemYMD=$data["Fecha_Inicio_Sem"];
            $Fecha_Fin_SemYMD=$data["Fecha_Fin_Sem"];
            ?>
            <li><h1 class="titulo">LPS Proyectos Inmobiliarios AIA - <?php echo "$proyecto, Semana $semana " ?><h2 class="titulo_pequeño"><?php echo"(del $Fecha_Inicio_SemYMD al $Fecha_Fin_SemYMD)"?></h2></h1></li>
        </ul>
    </div>
    
        <div class="contenedor_contenido">
         
        <hr class="border">
        <div class="contenido">
<!--            <div class="row_fondo">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <h1 class="text-center  text-uppercase">Programa General</h1>
                </div>
            </div>-->
            <!--Barra de navegación-->
            <nav class="navbar navbar-expand-m navbar-dark ">
              <a class="navbar-brand" href="#">
                <ul>
                    <li><img src="../imagenes/logo2.png" width="" class="d-inline-block align-top" alt=""></li>
                    <li class="lps">LPS</li>
                    <li class="pagina"> - Evaluación Semanal / Calificación Integral de Terceros / Semana <?php echo $semana ?></li>
                </ul>
              </a>
              <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                  <li class="nav-item contenido_link">
                    <a class="nav-link" href="<?php echo"../cambiar_pagina.php?seccion=contenido&semana=$semana"?>">Contenido</a>
                  </li>
                  <li class="nav-item dropdown informacion_general">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Información General</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_profesionales&semana=$semana"?>">Profesionales AIA</a>
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_subcontratistas&semana=$semana"?>">Sub-Contratistas</a>
                    </div>
                  </li>
                  <li class="nav-item active"> 
                    <a class='dropdown-item' style='padding: 16px 0px 8px 16px'>      
                        <button type= 'button' class='nueva_sem btn btn-primary' title='Crear nueva semana de la programación semanal'  onclick=nueva_sem() data-toggle='modal' data-target='#modal_nueva_sem'><i class="fa fa-plus fa-lg"></i> Nueva Semana</button>
                    </a>
                  </li>
                    
                    <?php
                        require("../conexion.php");
                        $query="SELECT  COUNT(*) FROM $db"."_semanas_activas";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        mysqli_close($conexion);
                        if ($conteo==0){  
                        } else if($conteo>0){
                            for($i=$conteo; $i>=1; $i--){
                                require("../conexion.php");
                                $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$i";
                                $resultado2= mysqli_query($conexion, $query2);
                                $data=mysqli_fetch_assoc($resultado2);
                                $ini=$data["Fecha_Inicio_Sem"];
                                $fin=$data["Fecha_Fin_Sem"];
                                mysqli_close($conexion);
                                if($i==$semana){
                                    if(($Max_Semana-2)>=$i){
                                        echo "
                                        <li class='nav-item dropdown active' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>       
                                            </a>
                                            <div class='dropdown-menu show' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown active' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu show' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }
                                       
                                } else{
                                    if(($Max_Semana-2)>=$i){
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)</button>       
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";     
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tramites&semana=$i'>Trámites</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=consultores&semana=$i'>Consultores</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>"; 
                                    }
                                }
                            };
                        };

                    ?>
                    
                  <li class="nav-item">
                    <a class="nav-link" href="../cerrar.php" tabindex="-1" aria-disabled="true">Cerrar Sesión</a>
                  </li>
                </ul>
              </div>
            </nav>
        </div>
    </div>




    <!--Se crea un div con nombre de clase "row". Acá se agregara un nuevo div que contiene la clase "cuadro2", la cual permanecerá oculta hasta que se presione el botón editar en alguna fila de la datatable -->
	<div class="row">
		<div id="cuadro2" class="cuadro2" style="visibility: hidden">
			<form class="form cic_mdo form-horizontal" action="" method="POST">
				<div class="form-group">
					<h2 class="col-sm-offset-2 col-sm-12 text-center" id="actualizacion"></h2>
				</div>
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id" name="Id" value="0">
                <input type="hidden" id="semana" name="semana" value="" readonly>
				<input type="hidden" id="opcion" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
<!--                <div style="width:40%; float:left;">-->
                    <div class="parametro form-group" id="mdo_cal">                
                        <div class="form_eval form-group">
                            <h3 id="form_calidad">Calidad</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>La calidad del producto suministrado e instalado:</p>
                              <input type="radio" name="mdo_cal_1" id="mdo_cal_1" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_cal_1" id="mdo_cal_1" value=0.5> 50%<br>
                              <input type="radio" name="mdo_cal_1" id="mdo_cal_1" value=1> 100%<br>
                              <input type="radio" name="mdo_cal_1" id="mdo_cal_1" value='NA'> N/A<br> 
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Las condiciones de almacenamiento de los materiales, insumos, maquinaria y equipos:</p>
                              <input type="radio" name="mdo_cal_2" id="mdo_cal_2" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_cal_2" id="mdo_cal_2" value=0.5> 50%<br>
                              <input type="radio" name="mdo_cal_2" id="mdo_cal_2" value=1> 100%<br>
                              <input type="radio" name="mdo_cal_2" id="mdo_cal_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Entrega de certificaciones / procedimientos asociadas a la actividad desarrollada:</p>
                              <input type="radio" name="mdo_cal_3" id="mdo_cal_3" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_cal_3" id="mdo_cal_3" value=0.5> 50%<br>
                              <input type="radio" name="mdo_cal_3" id="mdo_cal_3" value=1> 100%<br>
                              <input type="radio" name="mdo_cal_3" id="mdo_cal_3" value='NA'> N/A<br>  
                        </div>

                    </div>
                
                    <div class="parametro form-group" id="mdo_adm">                
                        <div class="form_eval form-group">
                            <h3 id="form_adm">Administración del Contrato</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>Los procedimientos administrativos y legales de AIA:</p>
                              <input type="radio" name="mdo_adm_1" id="mdo_adm_1" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_adm_1" id="mdo_adm_1" value=0.5> 50%<br>
                              <input type="radio" name="mdo_adm_1" id="mdo_adm_1" value=1> 100%<br>
                              <input type="radio" name="mdo_adm_1" id="mdo_adm_1" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La competencia y disponibilidad oportuna del personal en la obra:</p>
                              <input type="radio" name="mdo_adm_2" id="mdo_adm_2" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_adm_2" id="mdo_adm_2" value=0.5> 50%<br>
                              <input type="radio" name="mdo_adm_2" id="mdo_adm_2" value=1> 100%<br>
                              <input type="radio" name="mdo_adm_2" id="mdo_adm_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La disponibilidad oportuna y suficiente de los recursos: maquinaria, equipo y herramienta:</p>
                              <input type="radio" name="mdo_adm_3" id="mdo_adm_3" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_adm_3" id="mdo_adm_3" value=0.5> 50%<br>
                              <input type="radio" name="mdo_adm_3" id="mdo_adm_3" value=1> 100%<br>
                              <input type="radio" name="mdo_adm_3" id="mdo_adm_3" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La atención a solicitudes, quejas y reclamos:</p>
                              <input type="radio" name="mdo_adm_4" id="mdo_adm_4" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_adm_4" id="mdo_adm_4" value=0.5> 50%<br>
                              <input type="radio" name="mdo_adm_4" id="mdo_adm_4" value=1> 100%<br>
                              <input type="radio" name="mdo_adm_4" id="mdo_adm_4" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Los requisitos legales de calidad, ambiental y seguridad y salud en el trabajo:</p>
                              <input type="radio" name="mdo_adm_5" id="mdo_adm_5" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_adm_5" id="mdo_adm_5" value=0.5> 50%<br>
                              <input type="radio" name="mdo_adm_5" id="mdo_adm_5" value=1> 100%<br>
                              <input type="radio" name="mdo_adm_5" id="mdo_adm_5" value='NA'> N/A<br>  
                        </div>

                    </div>
                
                    <div class="parametro form-group" id="mdo_gsa">                
                        <div class="form_eval form-group">
                            <h3 id="form_GSA">Gestión Socio-Ambiental</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>Mantener la rotulación, clasificación y almacenamiento de  los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:</p>
                              <input type="radio" name="mdo_gsa_1" id="mdo_gsa_1" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_1" id="mdo_gsa_1" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_1" id="mdo_gsa_1" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_1" id="mdo_gsa_1" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar la adecuada separación, almacenamiento y  disposición interna y externa (cuando aplique) de los residuos generados en obra:</p>
                              <input type="radio" name="mdo_gsa_2" id="mdo_gsa_2" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_2" id="mdo_gsa_2" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_2" id="mdo_gsa_2" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_2" id="mdo_gsa_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):</p>
                              <input type="radio" name="mdo_gsa_3" id="mdo_gsa_3" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_3" id="mdo_gsa_3" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_3" id="mdo_gsa_3" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_3" id="mdo_gsa_3" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:</p>
                              <input type="radio" name="mdo_gsa_4" id="mdo_gsa_4" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_4" id="mdo_gsa_4" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_4" id="mdo_gsa_4" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_4" id="mdo_gsa_4" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:</p>
                              <input type="radio" name="mdo_gsa_5" id="mdo_gsa_5" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_5" id="mdo_gsa_5" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_5" id="mdo_gsa_5" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_5" id="mdo_gsa_5" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):</p>
                              <input type="radio" name="mdo_gsa_6" id="mdo_gsa_6" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_6" id="mdo_gsa_6" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_6" id="mdo_gsa_6" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_6" id="mdo_gsa_6" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:</p>
                              <input type="radio" name="mdo_gsa_7" id="mdo_gsa_7" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_7" id="mdo_gsa_7" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_7" id="mdo_gsa_7" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_7" id="mdo_gsa_7" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Acatar las acciones recomendadas durante recorridos de obra:</p>
                              <input type="radio" name="mdo_gsa_8" id="mdo_gsa_8" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_gsa_8" id="mdo_gsa_8" value=0.5> 50%<br>
                              <input type="radio" name="mdo_gsa_8" id="mdo_gsa_8" value=1> 100%<br>
                              <input type="radio" name="mdo_gsa_8" id="mdo_gsa_8" value='NA'> N/A<br>  
                        </div>
    
                    </div>
                
                    <div class="parametro form-group" id="mdo_sst">                
                        <div class="form_eval form-group">
                            <h3 id="form_sst">Seguridad y Salud en el Trabajo</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:</p>
                              <input type="radio" name="mdo_sst_1" id="mdo_sst_1" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_1" id="mdo_sst_1" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_1" id="mdo_sst_1" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_1" id="mdo_sst_1" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:</p>
                              <input type="radio" name="mdo_sst_2" id="mdo_sst_2" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_2" id="mdo_sst_2" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_2" id="mdo_sst_2" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_2" id="mdo_sst_2" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:</p>
                              <input type="radio" name="mdo_sst_3" id="mdo_sst_3" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_3" id="mdo_sst_3" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_3" id="mdo_sst_3" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_3" id="mdo_sst_3" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:</p>
                              <input type="radio" name="mdo_sst_4" id="mdo_sst_4" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_4" id="mdo_sst_4" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_4" id="mdo_sst_4" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_4" id="mdo_sst_4" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:</p>
                              <input type="radio" name="mdo_sst_5" id="mdo_sst_5" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_5" id="mdo_sst_5" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_5" id="mdo_sst_5" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_5" id="mdo_sst_5" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:</p>
                              <input type="radio" name="mdo_sst_6" id="mdo_sst_6" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_6" id="mdo_sst_6" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_6" id="mdo_sst_6" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_6" id="mdo_sst_6" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con la asistencia a las  capacitaciones y charlas de seguridad y salud en el trabajo:</p>
                              <input type="radio" name="mdo_sst_7" id="mdo_sst_7" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_7" id="mdo_sst_7" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_7" id="mdo_sst_7" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_7" id="mdo_sst_7" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:</p>
                              <input type="radio" name="mdo_sst_8" id="mdo_sst_8" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_8" id="mdo_sst_8" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_8" id="mdo_sst_8" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_8" id="mdo_sst_8" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cuenta con una persona de seguridad y salud en el trabajo:</p>
                              <input type="radio" name="mdo_sst_9" id="mdo_sst_9" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_9" id="mdo_sst_9" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_9" id="mdo_sst_9" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_9" id="mdo_sst_9" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:</p>
                              <input type="radio" name="mdo_sst_10" id="mdo_sst_10" value=0 checked> 0%<br>
                              <input type="radio" name="mdo_sst_10" id="mdo_sst_10" value=0.5> 50%<br>
                              <input type="radio" name="mdo_sst_10" id="mdo_sst_10" value=1> 100%<br>
                              <input type="radio" name="mdo_sst_10" id="mdo_sst_10" value='NA'> N/A<br>   
                        </div>
                        
                    </div>
                    
                    <div class="parametro form-group">                
                        <div class="form_eval form-group">
                            <h3 id="form_obs">Observaciones</h3>
                        </div>

                        <div class="pregunta form-group">
                            <div class="col-sm-12"><textarea id="mdo_Observaciones" name="mdo_Observaciones" class="form-control" ></textarea></div>
                        </div>
                     </div>
                
                <!--Se crean los botones Guardar y Listar-->
                    <div class="form-group">
                        <div class="botones">
                            <input id="btn_guardar" type="submit" class="btn btn-primary" value="Guardar">
                            <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar">
                        </div>
                    </div>
			</form>
            
            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class="col-sm-offset-2 col-sm-8">
				<p class="mensaje"></p>
			</div>
			
		</div>
	</div>
    
    
    
    <div class="row">
		<div id="cuadro4" class="cuadro4" style="visibility: hidden">
			<form class="form cic_si form-horizontal" action="" method="POST">
				<div class="form-group">
					<h2 class="col-sm-offset-2 col-sm-12 text-center" id="actualizacion"></h2>
				</div>
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id" name="Id" value="0">
                <input type="hidden" id="semana" name="semana" value="" readonly>
				<input type="hidden" id="opcion" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
<!--                <div style="width:40%; float:left;">-->
                    <div class="parametro form-group" id="si_cal">                
                        <div class="form_eval form-group">
                            <h3 id="form_calidad">Calidad</h3>
                        </div>

                        <div class="pregunta form-group" >
                            <p>La calidad del producto suministrado e instalado:</p>
                              <input type="radio" name="si_cal_1" id="si_cal_1" value=0 checked> 0%<br>
                              <input type="radio" name="si_cal_1" id="si_cal_1" value=0.5> 50%<br>
                              <input type="radio" name="si_cal_1" id="si_cal_1" value=1> 100%<br>
                              <input type="radio" name="si_cal_1" id="si_cal_1" value='NA'> N/A<br>   
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La entrega de procedimientos y/o protocolos para asegurar el cumplimiento de requisitos:</p>
                              <input type="radio" name="si_cal_2" id="si_cal_2" value=0 checked> 0%<br>
                              <input type="radio" name="si_cal_2" id="si_cal_2" value=0.5> 50%<br>
                              <input type="radio" name="si_cal_2" id="si_cal_2" value=1> 100%<br>
                              <input type="radio" name="si_cal_2" id="si_cal_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La entrega oportuna de los certificados de calibración de los equipos de medición, certificaciones y permisos ambientales:</p>
                              <input type="radio" name="si_cal_3" id="si_cal_3" value=0 checked> 0%<br>
                              <input type="radio" name="si_cal_3" id="si_cal_3" value=0.5> 50%<br>
                              <input type="radio" name="si_cal_3" id="si_cal_3" value=1> 100%<br>
                              <input type="radio" name="si_cal_3" id="si_cal_3" value='NA'> N/A<br>  
                        </div>

                    </div>
                
                    <div class="parametro form-group" id="si_adm">                
                        <div class="form_eval form-group">
                            <h3 id="form_adm">Administración del Contrato</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>El cumplimiento de las necesidades y oportunidad de personal en la obra:</p>
                              <input type="radio" name="si_adm_1" id="si_adm_1" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_1" id="si_adm_1" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_1" id="si_adm_1" value=1> 100%<br>
                              <input type="radio" name="si_adm_1" id="si_adm_1" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La disponibilidad, oportunidad y estado de la maquinaria, equipo y herramienta de trabajo:</p>
                              <input type="radio" name="si_adm_2" id="si_adm_2" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_2" id="si_adm_2" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_2" id="si_adm_2" value=1> 100%<br>
                              <input type="radio" name="si_adm_2" id="si_adm_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>La atención de solicitudes, quejas y reclamos:</p>
                              <input type="radio" name="si_adm_3" id="si_adm_3" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_3" id="si_adm_3" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_3" id="si_adm_3" value=1> 100%<br>
                              <input type="radio" name="si_adm_3" id="si_adm_3" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Los procedimientos administrativos y legales de la obra:</p>
                              <input type="radio" name="si_adm_4" id="si_adm_4" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_4" id="si_adm_4" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_4" id="si_adm_4" value=1> 100%<br>
                              <input type="radio" name="si_adm_4" id="si_adm_4" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>El cumplimiento del procedimiento de facturación:</p>
                              <input type="radio" name="si_adm_5" id="si_adm_5" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_5" id="si_adm_5" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_5" id="si_adm_5" value=1> 100%<br>
                              <input type="radio" name="si_adm_5" id="si_adm_5" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>El tiempo establecido para la liquidación del contrato:</p>
                              <input type="radio" name="si_adm_6" id="si_adm_6" value=0 checked> 0%<br>
                              <input type="radio" name="si_adm_6" id="si_adm_6" value=0.5> 50%<br>
                              <input type="radio" name="si_adm_6" id="si_adm_6" value=1> 100%<br>
                              <input type="radio" name="si_adm_6" id="si_adm_6" value='NA'> N/A<br>  
                        </div>

                    </div>
                
                    <div class="parametro form-group" id="si_gsa">                
                        <div class="form_eval form-group">
                            <h3 id="form_GSA">Gestión Socio-Ambiental</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>Presentar certificados durante los 15 primeros días del mes en donde se relacionen las volquetas  con PIN (Para Bogotá y cartagena) y Medellín los primeros 5 días del generador y sitio de disposición final con cantidades.  Las volquetas deben contar con número de PIN en Bogotá y Cartagena. Suministrar el control de los residuos que se han salido de la obra, con placa, fecha, cantidad, sitio de disposición mensual. Presentar volqueta con modelos superiores al año 2012. Contar con auxiliares de tránsito certificados para facilitar el movimiento interno y externo de los vehículos. El sitio de disposición final debe estar inscrito ante autoridad ambiental de acuerdo a la clasificación de la resolución 472 de 2017:</p>
                              <input type="radio" name="si_gsa_1" id="si_gsa_1" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_1" id="si_gsa_1" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_1" id="si_gsa_1" value=1> 100%<br>
                              <input type="radio" name="si_gsa_1" id="si_gsa_1" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>En caso de contar con la posibilidad de realizar el aprovechamiento de este material, notificar antes de realizar la actividad en la obra para verificar la legalidad de la situación:</p>
                              <input type="radio" name="si_gsa_2" id="si_gsa_2" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_2" id="si_gsa_2" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_2" id="si_gsa_2" value=1> 100%<br>
                              <input type="radio" name="si_gsa_2" id="si_gsa_2" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Las volquetas deben estar cubiertas con material completamente hermetico, y con carpado automatico. En caso de no llegar en estas condiciones no se permitirá el ingreso a obra, entrar y salir con las volcos cubiertas, compuertas y puertas cerradas y demás:</p>
                              <input type="radio" name="si_gsa_3" id="si_gsa_3" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_3" id="si_gsa_3" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_3" id="si_gsa_3" value=1> 100%<br>
                              <input type="radio" name="si_gsa_3" id="si_gsa_3" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Presentar los permisos ambientales correspondientes a la actividad (Licencias, títulos mineros, Plan de manejo ambiental, rucom, y demás permisos para operación, ) y los certificados mensuales de la entrega en obra:</p>
                              <input type="radio" name="si_gsa_4" id="si_gsa_4" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_4" id="si_gsa_4" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_4" id="si_gsa_4" value=1> 100%<br>
                              <input type="radio" name="si_gsa_4" id="si_gsa_4" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Presentar el certificado en donde se evidencie que el material de suministro presenta algún porcentaje (%) de material reciclable, cuando aplique:</p>
                              <input type="radio" name="si_gsa_5" id="si_gsa_5" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_5" id="si_gsa_5" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_5" id="si_gsa_5" value=1> 100%<br>
                              <input type="radio" name="si_gsa_5" id="si_gsa_5" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Suministrar en caso de ingreso de maquinaria: SOAT; revisión tecnicomecanica, hoja de vida (donde se incluya el mantenimiento preventido), programación de mantenimientos y matricula, poliza de terceros:</p>
                              <input type="radio" name="si_gsa_6" id="si_gsa_6" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_6" id="si_gsa_6" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_6" id="si_gsa_6" value=1> 100%<br>
                              <input type="radio" name="si_gsa_6" id="si_gsa_6" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Mantener la rotulación, clasificación y almacenamiento de  los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:</p>
                              <input type="radio" name="si_gsa_7" id="si_gsa_7" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_7" id="si_gsa_7" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_7" id="si_gsa_7" value=1> 100%<br>
                              <input type="radio" name="si_gsa_7" id="si_gsa_7" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar la adecuada separación, almacenamiento y  disposición interna y externa (cuando aplique) de los residuos generados en obra:</p>
                              <input type="radio" name="si_gsa_8" id="si_gsa_8" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_8" id="si_gsa_8" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_8" id="si_gsa_8" value=1> 100%<br>
                              <input type="radio" name="si_gsa_8" id="si_gsa_8" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):</p>
                              <input type="radio" name="si_gsa_9" id="si_gsa_9" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_9" id="si_gsa_9" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_9" id="si_gsa_9" value=1> 100%<br>
                              <input type="radio" name="si_gsa_9" id="si_gsa_9" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:</p>
                              <input type="radio" name="si_gsa_10" id="si_gsa_10" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_10" id="si_gsa_10" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_10" id="si_gsa_10" value=1> 100%<br>
                              <input type="radio" name="si_gsa_10" id="si_gsa_10" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:</p>
                              <input type="radio" name="si_gsa_11" id="si_gsa_11" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_11" id="si_gsa_11" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_11" id="si_gsa_11" value=1> 100%<br>
                              <input type="radio" name="si_gsa_11" id="si_gsa_11" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):</p>
                              <input type="radio" name="si_gsa_12" id="si_gsa_12" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_12" id="si_gsa_12" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_12" id="si_gsa_12" value=1> 100%<br>
                              <input type="radio" name="si_gsa_12" id="si_gsa_12" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:</p>
                              <input type="radio" name="si_gsa_13" id="si_gsa_13" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_13" id="si_gsa_13" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_13" id="si_gsa_13" value=1> 100%<br>
                              <input type="radio" name="si_gsa_13" id="si_gsa_13" value='NA'> N/A<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Acatar las acciones recomendadas durante recorridos de obra:</p>
                              <input type="radio" name="si_gsa_14" id="si_gsa_14" value=0 checked> 0%<br>
                              <input type="radio" name="si_gsa_14" id="si_gsa_14" value=0.5> 50%<br>
                              <input type="radio" name="si_gsa_14" id="si_gsa_14" value=1> 100%<br>
                              <input type="radio" name="si_gsa_14" id="si_gsa_14" value='NA'> N/A<br>  
                        </div>

                    </div>
                
                    <div class="parametro form-group" id="si_sst">                
                        <div class="form_eval form-group">
                            <h3 id="form_sst">Seguridad y Salud en el Trabajo</h3>
                        </div>

                        <div class="pregunta form-group">
                            <p>Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:</p>
                              <input type="radio" name="si_sst_1" id="si_sst_1" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_1" id="si_sst_1" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_1" id="si_sst_1" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:</p>
                              <input type="radio" name="si_sst_2" id="si_sst_2" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_2" id="si_sst_2" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_2" id="si_sst_2" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:</p>
                              <input type="radio" name="si_sst_3" id="si_sst_3" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_3" id="si_sst_3" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_3" id="si_sst_3" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:</p>
                              <input type="radio" name="si_sst_4" id="si_sst_4" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_4" id="si_sst_4" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_4" id="si_sst_4" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:</p>
                              <input type="radio" name="si_sst_5" id="si_sst_5" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_5" id="si_sst_5" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_5" id="si_sst_5" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:</p>
                              <input type="radio" name="si_sst_6" id="si_sst_6" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_6" id="si_sst_6" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_6" id="si_sst_6" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con la asistencia a las  capacitaciones y charlas de seguridad y salud en el trabajo:</p>
                              <input type="radio" name="si_sst_7" id="si_sst_7" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_7" id="si_sst_7" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_7" id="si_sst_7" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:</p>
                              <input type="radio" name="si_sst_8" id="si_sst_8" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_8" id="si_sst_8" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_8" id="si_sst_8" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cuenta con una persona de seguridad y salud en el trabajo:</p>
                              <input type="radio" name="si_sst_9" id="si_sst_9" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_9" id="si_sst_9" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_9" id="si_sst_9" value=1> 100%<br>  
                        </div>
                        
                        <div class="pregunta form-group">
                            <p>Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:</p>
                              <input type="radio" name="si_sst_10" id="si_sst_10" value=0 checked> 0%<br>
                              <input type="radio" name="si_sst_10" id="si_sst_10" value=0.5> 50%<br>
                              <input type="radio" name="si_sst_10" id="si_sst_10" value=1> 100%<br>  
                        </div>

                    </div>
                    
                    <div class="parametro form-group">                
                        <div class="form_eval form-group">
                            <h3 id="form_obs">Observaciones</h3>
                        </div>

                        <div class="pregunta form-group">
                            <div class="col-sm-12"><textarea id="si_Observaciones" name="si_Observaciones" class="form-control" ></textarea></div>
                        </div>
                     </div>
                
                
                <!--Se crean los botones Guardar y Listar-->
                    <div class="form-group">
                        <div class="botones">
                            <input id="btn_guardar1" type="submit" class="btn btn-primary" value="Guardar">
                            <input id="btn_listar1" type="button" class="btn btn-danger" value="Cancelar">
                        </div>
                    </div>
			</form>
            
            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class="col-sm-offset-2 col-sm-8">
				<p class="mensaje"></p>
			</div>
			
		</div>
	</div>
</header>
    
<main class="main">
    
        <!--Línea donde se inserta el botón para editar los registros -->
	<div class="row">
		<div id="cuadro3" class="cuadro3  col-lg-12">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 ">
                        <input id="btn_generar_cal" type="hidden" class="btn btn-primary btn-sm" value="Calificar Sub-Contratistas">
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example">
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal&semana=$semana"?>'">Actividades <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNP&semana=$semana"?>'">Causas No Programación <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNC" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNC&semana=$semana"?>'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm active" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CIC&semana=$semana"?>'">Calificación de Terceros <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=indicadores&semana=$semana"?>'">Indicadores</button>
                        </div> 
					</div>
				</div>
			</form>
            
            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class=" col-sm-8">
				<p class="mensaje"></p>
			</div>
			
		</div>
	</div>
    
    
    <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row">
		<div id="cuadro1" class="col-sm-12 col-md-12 col-lg-12">
			<div class="col-sm-offset-2 col-sm-8">
				<h3 class="text-center"> <small class="mensaje"></small></h3>
			</div>
			<div class="table-responsive col-sm-12">		
				<table id="dt_general" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
                            <th></th>
                            <th>Id</th>
							<th>Sub-Contratista</th>
							<th>Alcance</th>
							<th>Tipo de Proveedor</th>
                            <th>PAC</th>
                            <th>% Completado</th>
                            <th>Calidad</th>
                            <th>Gestión Socio-Ambiental</th>
                            <th>SST</th>
                            <th>Administración del Contrato</th>
                            <th>Calificación Integral</th>
                            <th>Observaciones</th>
                            <th>mdo_cal_1</th>
                            <th>mdo_cal_2</th>
                            <th>mdo_cal_3</th>
                            <th>mdo_adm_1</th>
                            <th>mdo_adm_2</th>
                            <th>mdo_adm_3</th>
                            <th>mdo_adm_4</th>
                            <th>mdo_adm_5</th>
                            <th>mdo_gsa_1</th>
                            <th>mdo_gsa_2</th>
                            <th>mdo_gsa_3</th>
                            <th>mdo_gsa_4</th>
                            <th>mdo_gsa_5</th>
                            <th>mdo_gsa_6</th>
                            <th>mdo_gsa_7</th>
                            <th>mdo_gsa_8</th>
                            <th>mdo_sst_1</th>
                            <th>mdo_sst_2</th>
                            <th>mdo_sst_3</th>
                            <th>mdo_sst_4</th>
                            <th>mdo_sst_5</th>
                            <th>mdo_sst_6</th>
                            <th>mdo_sst_7</th>
                            <th>mdo_sst_8</th>
                            <th>mdo_sst_9</th>
                            <th>mdo_sst_10</th>
                            <th>si_cal_1</th>
                            <th>si_cal_2</th>
                            <th>si_cal_3</th>
                            <th>si_adm_1</th>
                            <th>si_adm_2</th>
                            <th>si_adm_3</th>
                            <th>si_adm_4</th>
                            <th>si_adm_5</th>
                            <th>si_adm_6</th>
                            <th>si_gsa_1</th>
                            <th>si_gsa_2</th>
                            <th>si_gsa_3</th>
                            <th>si_gsa_4</th>
                            <th>si_gsa_5</th>
                            <th>si_gsa_6</th>
                            <th>si_gsa_7</th>
                            <th>si_gsa_8</th>
                            <th>si_gsa_9</th>
                            <th>si_gsa_10</th>
                            <th>si_gsa_11</th>
                            <th>si_gsa_12</th>
                            <th>si_gsa_13</th>
                            <th>si_gsa_14</th>
                            <th>si_sst_1</th>
                            <th>si_sst_2</th>
                            <th>si_sst_3</th>
                            <th>si_sst_4</th>
                            <th>si_sst_5</th>
                            <th>si_sst_6</th>
                            <th>si_sst_7</th>
                            <th>si_sst_8</th>
                            <th>si_sst_9</th>
                            <th>si_sst_10</th>
						</tr>
					</thead>					
				</table>
			</div>			
		</div>		
	</div>
	
    <!--Se crea un div. Acá se agregará un form llamado "frmEliminarUsuario", el cual permanecerá oculto. En este form se crean 2 inputs que contienen el id del registro que se va a eliminar, y el switch que dice si hay que eliminar  -->  
    <div>     
		<form id="frmEliminarUsuario" action="" method="POST">
			<input type="hidden" id="Id" name="Id" value="" readonly>
            <input type="hidden" id="semana" name="semana" value="" readonly>
            <input type="hidden" id="Actividad" name="Actividad" value="" readonly>
			<input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>
            
            
            
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
			<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEliminarLabel">Eliminar Usuario</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">							
							¿Está seguro de eliminar al usuario?<strong data-name=""></strong>
						</div>
						<div class="modal-footer">
							<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
						</div>
					</div>
				</div>
			</div>
			<!-- Modal -->
            <!-- Se crea el Modal que solicita la información para crear una nueva semana -->
			<div class="modal_nueva_sem modal fade" id="modal_nueva_sem" tabindex="-1" role="dialog" aria-labelledby="modal_nueva_semLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEliminarLabel">Crear Semana LPS</h4>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">							
                            <div class="row">
                                    <div id="cuadro4" class="cuadro4 col-sm-12 col-md-12 col-lg-12 ">
                                        <form class="form form-horizontal" action="" method="POST">
                                            <!--<div class="form-group">
                                                <h3 class="col-sm-offset-2 col-sm-8 text-center"></h3>
                                            </div>
-->
                                            <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
                                            <input type="hidden" id="Id" name="Id" value="0">
                                            <input type="hidden" id="opcion" name="opcion" value="registrar">

                                                <div class="form-group" style="width:100%;">
                                                    <label for="inicio_sem" class="col-sm-12 control-label">Seleccione Fecha de Inicio de la Semana</label>
                                                    <div class="col-sm-6"><input id="inicio_sem" name="inicio_sem" class="form-control" type="text" readonly></div>
                                                </div>


                                            <!--Se crean los botones Guardar y Listar-->
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-8">
                                                    <input id="btn_guardar_nueva_sem" type="button" class="btn btn-primary" data-dismiss="modal" value="Guardar">
                                                    <input id="btn_cancelar" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" >
                                                </div>
                                            </div>
                                        </form>

                                        <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
                                        <div class="col-sm-offset-2 col-sm-8">
                                            <p class="mensaje"></p>
                                        </div>

                                    </div>
                                </div>
						</div>
<!--						<div class="modal-footer">
							<button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
						</div>-->
					</div>
				</div>
			</div>
			<!-- Modal -->
            <!--Se crea un div. Acá se agregará un form llamado "frmEliminarUsuario", el cual permanecerá oculto. En este form se crean 2 inputs que contienen el id del registro que se va a eliminar, y el switch que dice si hay que eliminar  -->  
            <div>     
                <form id="frmEliminarUsuario" action="" method="POST">
                    <input type="hidden" id="Id" name="Id" value="" readonly>
                    <input type="hidden" id="opcion" name="opcion" value="eliminar" readonly>



                    <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
                    <div class="modal_eliminar_sem modal fade" id="modal_eliminar_sem" tabindex="-1" role="dialog" aria-labelledby="modal_nueva_semLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="modalEliminarLabel">Eliminar Semana LPS</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">							
                                    <div class="row">
                                            <div id="cuadro5" class="cuadro5 col-sm-12 col-md-12 col-lg-12 ">
                                                <form class="form form-horizontal" action="" method="POST">
                                                    <!--<div class="form-group">
                                                        <h3 class="col-sm-offset-2 col-sm-8 text-center"></h3>
                                                    </div>
        -->
                                                   <p class="modal-eliminar-semana-body-texto" id="modal-eliminar-semana-body-texto"></p>


                                                    <!--Se crean los botones Guardar y Listar-->
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-8">
                                                            <input id="btn_eliminar_sem" type="button" class="btn btn-primary" data-dismiss="modal" value="Eliminar">
                                                            <input id="btn_cancelar1" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" >
                                                        </div>
                                                    </div>
                                                </form>

                                                <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
                                                <div class="col-sm-offset-2 col-sm-8">
                                                    <p class="mensaje"></p>
                                                </div>

                                            </div>
                                        </div>
                                </div>
        <!--						<div class="modal-footer">
                                    <button type="button" id="eliminar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                </div>-->
                            </div>
                        </div>
                    </div>
                    <!-- Modal -->
                </form>
            </div>
		</form>
	</div>
</main>
    
    <!-- Iniciar Jquery-->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.12.4.js"></script>
    
    <!-- Iniciar Popper-->
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    
    <!-- Iniciar Bootstrap-->
	<script type="text/javascript" charset="utf8" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    
    <!--Iniciar DataTables-->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>

    <!--Botones de Datatables-->

	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.bootstrap4.min.js"></script>
   
	<!--checkboxes DataTables-->	
    <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
    

    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    
    
    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
        
        /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function(){
            listar();
            guardar();
           
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_listar").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            location.assign("../scroll_general.php?scroll="+posicion+"&seccion=CIC")
            $("#cuadro2").hide();
            $("#cuadro1").slideDown();
            $("#cuadro3").slideDown();
        });
        
        $("#btn_listar1").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            location.assign("../scroll_general.php?scroll="+posicion+"&seccion=CIC")
            $("#cuadro4").hide();
            $("#cuadro1").slideDown();
            $("#cuadro3").slideDown();
        });
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            location.assign("../scroll_general.php?scroll="+posicion+"&seccion=CIC")
            //listar();
            
            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
    
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$("form").on("submit", function(e){
				e.preventDefault();
				var frm = $(this).serialize();
                console.log(frm);
				$.ajax({
					method: "POST",
					url: "../programacion_semanal/guardar_CIC.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					/*var json_info = JSON.parse( info );
                    mostrar_mensaje( json_info );
					limpiar_datos();
					listar();*/
                    var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("../scroll_general.php?scroll="+posicion+"&seccion=CIC")
                    //location.reload(true);
				});
			});
		}
        
        /*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function( informacion ){
			var texto = "", color = "";
			if( informacion.respuesta == "BIEN" ){
					texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
					color = "#379911";
			}
            
            if( informacion.respuesta == "ERROR"){
					texto = "<strong>Error</strong>, no se ejecutó la consulta.";
					color = "#C9302C";
			}
            
            if( informacion.respuesta == "EXISTE" ){
					texto = "<strong>Información!</strong> el usuario ya existe.";
					color = "#C9302C";
			}
            
            if( informacion.respuesta == "VACIO" ){
					texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
					color = "#C9302C";
			}
            if( informacion.respuesta == "CONFIRMAR" ){
					texto = "<strong>Advertencia!</strong> Por favor confirmar correctamente la dirección de correo.";
					color = "#C9302C";
			}
            




            if(informacion.respuesta=="BIEN"){
                $("#cuadro2").slideUp("slow");
                $("#cuadro4").slideUp("slow");
                $("#cuadro1").slideDown("slow");
                $("#cuadro3").slideDown("slow");
                $(".mensaje2").html( texto ).css({"color": color });
                $(".mensaje2").fadeOut(5000, function(){
                        $(this).html("");
                        $(this).fadeIn(3000);
			     });
            } else{
                $(".mensaje").html( texto ).css({"color": color });
                $(".mensaje").fadeOut(5000, function(){
                        $(this).html("");
                        $(this).fadeIn(3000);
			     });
            }
        }
        
        
        /*limpia los valores del formulario de registro*/
		var limpiar_datos = function(){
			$("#opcion").val("registrar");
            $("#Actividad").val("");
            $("#Categoria_CNC").val("").focus();
            $("#CNC").val("");
            $("#Observaciones_CNC").val("");
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");

		}
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/

            
        var listar=function(){
                var posicion= <?php 
                if(isset($_SESSION["scroll"])){
                    echo $_SESSION["scroll"];
                }else{
                    echo 0;
                }
                ?>;
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/            

                var alturahoja= $(window).height();
                var alturatabla= ((1.28*alturahoja)-513.21);
                
                $("#cuadro2").hide();
                $("#cuadro4").hide();
                //$("#cuadro2").slideUp("slow");
                //$("#cuadro4").slideUp("slow");
                //$("#cuadro3").slideDown();
    //            $("#cuadro1").slideDown("slow");
                var table = $("#dt_general").DataTable({
                    "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                    "destroy":true,
    //                "order":false,
    /*                "autoWidth": true,*/
    /*                "fixedHeader": false,*/
                    "scrollX": true,

    //                console.log($(document).height());
                    "scrollY": alturatabla,
    /*                "scrollCollapse": false,*/
    //                "responsive":true,
                    "paging":false,


                    "ajax":{
                      "method":"POST",
                      "url":"../programacion_semanal/listar_CIC.php<?php echo "?db=$db&semana=$semana"?>"
                  },


                    'columnDefs': [


                         {
                            'targets': [0,1,2,3,4,5,6,7,8,9,10,11,12],
                            'width': "1%",
                         },
                        
                        {
                        'targets': [2,3],
                        'render': function ( data, type, full, meta ) {
                                return "<h6>" + data + "</h6>";
                            }
                        },
                        
                        {
                            'targets': [5,6,7,8,9,10,11],
                            'render': function ( data, type, full, meta ) {
                                if(data=='NA'){
                                    data="No Aplica";
                                    return "<h6>" + data + "</h6>";
                                }else{
                                    data=data*100;
                                    data=data.toFixed(0);
                                    //console.log(data);
                                    carita="";
                                    if (data >= 95){
                                        carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                                    } else if(data < 95 && data >= 70){
                                        carita = "<i style='color:RGB(210,203,59)' class='fas fa-meh fa-2x'></i>";     
                                    } else if(data < 70){
                                        carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";     
                                    }
                                    return "<h6>" + data + "% <small> "+carita+"</small></h6>";
                                }                                
                            },
                        },
                      ],



                    'select': {
                     'style': 'false',
                    },  

                    "lengthMenu": [100],

                    "columns":[
                        {"defaultContent":""/*"<button type= 'button' class='editar btn btn-primary btn-sm'><i class='fa fa-edit'></i></button>"*/,"visible":false},
                        {"data":"Id", "visible":false},
                        {"data":"subcontratista"},
                        {"data":"alcance"},
                        {"data":"tipo_proveedor","visible":false},
                        {"data":"PAC"},
                        {"data":"P_Completado"},
                        {"data":"Calidad","visible":false},
                        {"data":"GSA","visible":false},
                        {"data":"SST","visible":false},
                        {"data":"ADM","visible":false},
                        {"data":"Cal_Integral","visible":false},
                        {"data":"Observaciones","visible":false},
                        {"data":"mdo_cal_1","visible":false},
                        {"data":"mdo_cal_2","visible":false},
                        {"data":"mdo_cal_3","visible":false},
                        {"data":"mdo_adm_1","visible":false},
                        {"data":"mdo_adm_2","visible":false},
                        {"data":"mdo_adm_3","visible":false},
                        {"data":"mdo_adm_4","visible":false},
                        {"data":"mdo_adm_5","visible":false},
                        {"data":"mdo_gsa_1","visible":false},
                        {"data":"mdo_gsa_2","visible":false},
                        {"data":"mdo_gsa_3","visible":false},
                        {"data":"mdo_gsa_4","visible":false},
                        {"data":"mdo_gsa_5","visible":false},
                        {"data":"mdo_gsa_6","visible":false},
                        {"data":"mdo_gsa_7","visible":false},
                        {"data":"mdo_gsa_8","visible":false},
                        {"data":"mdo_sst_1","visible":false},
                        {"data":"mdo_sst_2","visible":false},
                        {"data":"mdo_sst_3","visible":false},
                        {"data":"mdo_sst_4","visible":false},
                        {"data":"mdo_sst_5","visible":false},
                        {"data":"mdo_sst_6","visible":false},
                        {"data":"mdo_sst_7","visible":false},
                        {"data":"mdo_sst_8","visible":false},
                        {"data":"mdo_sst_9","visible":false},
                        {"data":"mdo_sst_10","visible":false},
                        {"data":"si_cal_1","visible":false},
                        {"data":"si_cal_2","visible":false},
                        {"data":"si_cal_3","visible":false},
                        {"data":"si_adm_1","visible":false},
                        {"data":"si_adm_2","visible":false},
                        {"data":"si_adm_3","visible":false},
                        {"data":"si_adm_4","visible":false},
                        {"data":"si_adm_5","visible":false},
                        {"data":"si_adm_6","visible":false},
                        {"data":"si_gsa_1","visible":false},
                        {"data":"si_gsa_2","visible":false},
                        {"data":"si_gsa_3","visible":false},
                        {"data":"si_gsa_4","visible":false},
                        {"data":"si_gsa_5","visible":false},
                        {"data":"si_gsa_6","visible":false},
                        {"data":"si_gsa_7","visible":false},
                        {"data":"si_gsa_8","visible":false},
                        {"data":"si_gsa_9","visible":false},
                        {"data":"si_gsa_10","visible":false},
                        {"data":"si_gsa_11","visible":false},
                        {"data":"si_gsa_12","visible":false},
                        {"data":"si_gsa_13","visible":false},
                        {"data":"si_gsa_14","visible":false},
                        {"data":"si_sst_1","visible":false},
                        {"data":"si_sst_2","visible":false},
                        {"data":"si_sst_3","visible":false},
                        {"data":"si_sst_4","visible":false},
                        {"data":"si_sst_5","visible":false},
                        {"data":"si_sst_6","visible":false},
                        {"data":"si_sst_7","visible":false},
                        {"data":"si_sst_8","visible":false},
                        {"data":"si_sst_9","visible":false},
                        {"data":"si_sst_10","visible":false}
                    ],

                    "language": idioma_espanol
                });
                ocultos(table);
                cambiar_posicion(posicion);
                <?php $_SESSION["scroll"]=0 ?>;
                //obtener_data_editar("#dt_general tbody", table);
                generar();
        }
        
//        var contar_cajas_checkeadas=function()
        
        
        /*Para agregar un nuevo usuario en la base de datos*/
        var agregar_nuevo_usuario = function(){
            limpiar_datos();
            $("#cuadro2").slideDown("slow");
            $("#cuadro4").slideDown("slow");
            $("#cuadro3").slideUp("slow");
//            $("#cuadro1").slideUp("slow");   
        }
        
        /*Toma los datos de la fila en la que se presionó el botón editar*/
        var obtener_data_editar=function(tbody, table){
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            var permiso="<?php echo $permiso?>";
            
            if((max_semana-2)>=semana){
                var only_once = false;
            }else{
                if(permiso=="G" || permiso=="S" || permiso=="V" || permiso=="C"){
                    var only_once = false;
                }else{
                    var only_once = true;
                }
            }
            if(only_once==true){
                $(tbody).on("click", "td", function(){
                    var data= table.row($(this).parents("tr")).data();
                    console.log(data.tipo_proveedor);
                    if(data.tipo_proveedor=="Mano de Obra"){
                        var Id=$("#cic_mdo, #Id").val(data.Id);
                        var opcion = $("#cic_mdo, #opcion").val("modificar_mdo");
                        $("#cic_mdo, #semana").val(<?php echo $semana?>).change();
                        $("input[name=mdo_cal_1][value='"+data.mdo_cal_1+"']").prop("checked",true);
                        $("input[name=mdo_cal_2][value='"+data.mdo_cal_2+"']").prop("checked",true);
                        $("input[name=mdo_cal_3][value='"+data.mdo_cal_3+"']").prop("checked",true);
                        $("input[name=mdo_adm_1][value='"+data.mdo_adm_1+"']").prop("checked",true);
                        $("input[name=mdo_adm_2][value='"+data.mdo_adm_2+"']").prop("checked",true);
                        $("input[name=mdo_adm_3][value='"+data.mdo_adm_3+"']").prop("checked",true);
                        $("input[name=mdo_adm_4][value='"+data.mdo_adm_4+"']").prop("checked",true);
                        $("input[name=mdo_adm_5][value='"+data.mdo_adm_5+"']").prop("checked",true);
                        $("input[name=mdo_gsa_1][value='"+data.mdo_gsa_1+"']").prop("checked",true);
                        $("input[name=mdo_gsa_2][value='"+data.mdo_gsa_2+"']").prop("checked",true);
                        $("input[name=mdo_gsa_3][value='"+data.mdo_gsa_3+"']").prop("checked",true);
                        $("input[name=mdo_gsa_4][value='"+data.mdo_gsa_4+"']").prop("checked",true);
                        $("input[name=mdo_gsa_5][value='"+data.mdo_gsa_5+"']").prop("checked",true);
                        $("input[name=mdo_gsa_6][value='"+data.mdo_gsa_6+"']").prop("checked",true);
                        $("input[name=mdo_gsa_7][value='"+data.mdo_gsa_7+"']").prop("checked",true);
                        $("input[name=mdo_gsa_8][value='"+data.mdo_gsa_8+"']").prop("checked",true);
                        $("input[name=mdo_sst_1][value='"+data.mdo_sst_1+"']").prop("checked",true);
                        $("input[name=mdo_sst_2][value='"+data.mdo_sst_2+"']").prop("checked",true);
                        $("input[name=mdo_sst_3][value='"+data.mdo_sst_3+"']").prop("checked",true);
                        $("input[name=mdo_sst_4][value='"+data.mdo_sst_4+"']").prop("checked",true);
                        $("input[name=mdo_sst_5][value='"+data.mdo_sst_5+"']").prop("checked",true);
                        $("input[name=mdo_sst_6][value='"+data.mdo_sst_6+"']").prop("checked",true);
                        $("input[name=mdo_sst_7][value='"+data.mdo_sst_7+"']").prop("checked",true);
                        $("input[name=mdo_sst_8][value='"+data.mdo_sst_8+"']").prop("checked",true);
                        $("input[name=mdo_sst_9][value='"+data.mdo_sst_9+"']").prop("checked",true);
                        $("input[name=mdo_sst_10][value='"+data.mdo_sst_10+"']").prop("checked",true);
                        $("#mdo_Observaciones").val(data.Observaciones);

                    } else if (data.tipo_proveedor=="Suministro e Instalación"){
                        var Id=$("#cic_si, #Id").val(data.Id);
                        var opcion = $("#cic_si, #opcion").val("modificar_si");
                        $("#cic_si, #semana").val(<?php echo $semana?>).change();
                        $("#cic_mdo, #semana").val(<?php echo $semana?>).change();
                        $("input[name=si_cal_1][value='"+data.si_cal_1+"']").prop("checked",true);
                        $("input[name=si_cal_2][value='"+data.si_cal_2+"']").prop("checked",true);
                        $("input[name=si_cal_3][value='"+data.si_cal_3+"']").prop("checked",true);
                        $("input[name=si_adm_1][value='"+data.si_adm_1+"']").prop("checked",true);
                        $("input[name=si_adm_2][value='"+data.si_adm_2+"']").prop("checked",true);
                        $("input[name=si_adm_3][value='"+data.si_adm_3+"']").prop("checked",true);
                        $("input[name=si_adm_4][value='"+data.si_adm_4+"']").prop("checked",true);
                        $("input[name=si_adm_5][value='"+data.si_adm_5+"']").prop("checked",true);
                        $("input[name=si_adm_6][value='"+data.si_adm_6+"']").prop("checked",true);
                        $("input[name=si_gsa_1][value='"+data.si_gsa_1+"']").prop("checked",true);
                        $("input[name=si_gsa_2][value='"+data.si_gsa_2+"']").prop("checked",true);
                        $("input[name=si_gsa_3][value='"+data.si_gsa_3+"']").prop("checked",true);
                        $("input[name=si_gsa_4][value='"+data.si_gsa_4+"']").prop("checked",true);
                        $("input[name=si_gsa_5][value='"+data.si_gsa_5+"']").prop("checked",true);
                        $("input[name=si_gsa_6][value='"+data.si_gsa_6+"']").prop("checked",true);
                        $("input[name=si_gsa_7][value='"+data.si_gsa_7+"']").prop("checked",true);
                        $("input[name=si_gsa_8][value='"+data.si_gsa_8+"']").prop("checked",true);
                        $("input[name=si_gsa_9][value='"+data.si_gsa_9+"']").prop("checked",true);
                        $("input[name=si_gsa_10][value='"+data.si_gsa_10+"']").prop("checked",true);
                        $("input[name=si_gsa_11][value='"+data.si_gsa_11+"']").prop("checked",true);
                        $("input[name=si_gsa_12][value='"+data.si_gsa_12+"']").prop("checked",true);
                        $("input[name=si_gsa_13][value='"+data.si_gsa_13+"']").prop("checked",true);
                        $("input[name=si_gsa_14][value='"+data.si_gsa_14+"']").prop("checked",true);
                        $("input[name=si_sst_1][value='"+data.si_sst_1+"']").prop("checked",true);
                        $("input[name=si_sst_2][value='"+data.si_sst_2+"']").prop("checked",true);
                        $("input[name=si_sst_3][value='"+data.si_sst_3+"']").prop("checked",true);
                        $("input[name=si_sst_4][value='"+data.si_sst_4+"']").prop("checked",true);
                        $("input[name=si_sst_5][value='"+data.si_sst_5+"']").prop("checked",true);
                        $("input[name=si_sst_6][value='"+data.si_sst_6+"']").prop("checked",true);
                        $("input[name=si_sst_7][value='"+data.si_sst_7+"']").prop("checked",true);
                        $("input[name=si_sst_8][value='"+data.si_sst_8+"']").prop("checked",true);
                        $("input[name=si_sst_9][value='"+data.si_sst_9+"']").prop("checked",true);
                        $("input[name=si_sst_10][value='"+data.si_sst_10+"']").prop("checked",true);
                        $("#si_Observaciones").val(data.Observaciones);
                    }                

                    if(data.tipo_proveedor=="Mano de Obra"){
                        $("#cuadro2").slideDown("slow");
                        $("#cuadro4").slideUp("slow");
                    } else if (data.tipo_proveedor=="Suministro e Instalación"){
                        $("#cuadro4").slideDown("slow");
                        $("#cuadro2").slideUp("slow");
                    };
                        $("#cuadro3").slideUp("slow");
    //                    $("#cuadro1").slideUp("slow");      
                });    
            }
            
        }
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_CIC.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"f_inicio_sem": f_inicio_sem, "opcion": opcion}
				}).done( function( info ){
					var json_info = JSON.parse( info );
					mostrar_mensaje( json_info );
					limpiar_datos_nueva_sem();
                    location.reload(true);
					listar();
				});
            });
        }
        var generar=function(){
            $("#btn_generar_cal").on("click", function(){            
                opcion="generar";
                semana=<?php echo $semana?>;
                console.log(opcion);
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_CIC.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"semana": semana, "opcion": opcion}
				}).done( function( info ){
					var json_info = JSON.parse( info );
					console.log(json_info);
					mostrar_mensaje( json_info );
					limpiar_datos_nueva_sem();
                    location.reload(true);
					listar();
				});
            });
        }
        
        var eliminar_sem=function(semana){
            $("#modal-eliminar-semana-body-texto").text("¿Desea Eliminar la Semana "+semana+"?");
            $("#btn_eliminar_sem").on("click", function(){            
                opcion="eliminar_sem";
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_CIC.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"semana": semana, "opcion": opcion}
				}).done( function( info ){
                    location.reload(true);
					listar();
				});
            });
        }
        
        $(function() {
            var dia=new Date(<?php echo $Fecha_datepicker; ?>);
            //dia = new Date(dia.getFullYear(),dia.getMonth(),dia.getDate());
            
            $( "#inicio_sem" ).val(dia.getFullYear()+"-"+(dia.getMonth()+1)+"-"+dia.getDate());
            
            $( "#inicio_sem" )
                .datepicker({dateFormat: 'yy-mm-dd',
                                           changeMonth: true,
                                           changeYear: true,
                                           showOtherMonths: true,
                                           selectOtherMonths: true,
                                           defaultDate: dia,
                                          });
        });
        
        
        var cnc=function(causa){
            var categoria=$("#Categoria_CNC").val(),
                opcion="CNC",
                CNC=causa;
                console.log(CNC);
                if(categoria===""){
                    $('#CNC').attr('disabled', true); 
                } else{
                    $('#CNC').attr('disabled', false); 
                    $.ajax({
                        method:"POST",
                        url: "../programacion_semanal/guardar_CIC.php?db=login",
                        contenttype:"charset=utf-8",
                        data: {"categoria": categoria, "opcion":opcion},
                        success:function(a){
                            $('#CNC').html(a);
                            $("#CNC option[value='"+CNC+"']").attr('selected', true);
                        }
                    }); 
                }  
            }
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
            }else if(permiso=="G"){
                $('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
            }else if(permiso=="S"){
                $('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
            }else if(permiso=="V"){
                $('#btn_guardar, #btn_guardar1').css('display', 'none');
                $('#btn_listar, #btn_listar1').removeClass('btn btn-danger').addClass('btn btn-primary');
                $('#btn_listar, #btn_listar1').val('Cerrar').change();
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="C"){
                $('#btn_guardar, #btn_guardar1').css('display', 'none');
                $('#btn_listar, #btn_listar1').removeClass('btn btn-danger').addClass('btn btn-primary');
                $('#btn_listar, #btn_listar1').val('Cerrar').change();
                $('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
                table.column( 0 ).visible( false );
            }
            
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            
            if((max_semana-2)>=semana){
                //$('#btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }
        }
        

        /*Configura la DataTable en idioma español*/
       var idioma_espanol={
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla =(",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad"
            }
        }

	</script>
</body>
</html>