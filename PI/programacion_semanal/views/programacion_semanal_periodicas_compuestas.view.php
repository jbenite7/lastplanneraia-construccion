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
                    <li class="pagina"> - Programacion Semanal / Tareas Periódicas Compuestas / Semana <?php echo $semana ?></li>
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
                        <a class="dropdown-item" href="<?php echo"../cambiar_pagina.php?seccion=info_subcontratistas&semana=$semana"?>">Terceros</a>
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
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
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
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$i'>Evaluación Semanal</a>
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
		<div id="cuadro2" class="cuadro2 col-sm-12 col-md-12 col-lg-12 ">
			<form class="form form-horizontal" action="" method="POST">
				<div class="form-group">
					<h3 class="col-sm-offset-2 col-sm-8 text-center" id="actualizacion"></h3>
				</div>
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id0" name="Id" value="0">
                <input type="hidden" id="semana0" name="semana" value="" readonly>
				<input type="hidden" id="opcion0" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
<!--                <div style="width:40%; float:left;">-->
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Id1" class="col-sm-8 control-label">Id</label>
                        <div class="col-sm-8"><input id="Id1" name="Id1" class="form-control" value="" type="text" >
                        </div>				
                    </div>     
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Actividad" class="col-sm-8 control-label">Actividad</label>
                        <div class="col-sm-8"><input id="Actividad" name="Actividad" class="form-control" value="" type="text" >
                        </div>		
                    </div>
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Descripcion" class="col-sm-8 control-label">Descripción</label>
                        <div class="col-sm-8"><input id="Descripcion" name="Descripcion" class="form-control" value="" type="" >
                        </div>		
                    </div>
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Clase" class="col-sm-8 control-label">Clase</label>
                        <div class="col-sm-8"><input id="Clase" name="Clase" class="form-control" value="" type="" readonly>
                        </div>		
                    </div>
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Sub_Contratista" class="col-sm-8 control-label">Sub-Contratista</label>
                        <div class="col-sm-8"><select id="Sub_Contratista" name="Sub_Contratista" class="form-control" >
                        <option value=""></option>
                        <?php
                            require("../conexion.php");
                            $query="SELECT * FROM $db"."_subcontratistas";
                            $resultado= mysqli_query($conexion, $query);
                            while ($valores = mysqli_fetch_array($resultado)){
                                echo '<option value="'.$valores["subcontratista"].'">'.$valores["subcontratista"].'</option>';
                            };
                        ?>
                        </select>
                        </div>	
                    </div>
                
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="Responsable_AIA" class="col-sm-8 control-label">Profesional AIA</label>
                        <div class="col-sm-8"><select id="Responsable_AIA" name="Responsable_AIA" class="form-control" >
                        <option value=""></option>
                        <?php
                            require("../conexion.php");
                            $query="SELECT * FROM $db"."_profesionales";
                            $resultado= mysqli_query($conexion, $query);
                            while ($valores = mysqli_fetch_array($resultado)){
                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                            };
                        ?>
                        </select>
                        </div>	
                    </div>
                    <div class="form-group" style="width:600px; max-width:45%; float:left">
                        <label for="Unidad" class="col-sm-8 control-label">Unidad de Medida</label>
                        <div class="col-sm-4"><input id='Unidad' name='Unidad' class='form-control' type='text' value='%' readonly>
                        </div>	
                    </div>
                    <div class="form-group" style="width:600px; max-width:45%; float:left">
                        <label for="Compromiso" class="col-sm-8 control-label">Cantidad Comprometida</label>
                        <div class="col-sm-4"><input id="Compromiso" name="Compromiso" class="form-control" value="" type="text">
                        </div>	
                    </div>
                
                    <div class="form-group" style="width:600px; max-width:45%; display:inline-block">
                        <label for="Real" class="col-sm-8 control-label">Cantidad Real Ejecutada</label>
                        <div class="col-sm-4"><input id="Real" name="Real" class="form-control" value="" type="text" >
                        </div>	
                    </div>
                
                <br>
                <!--Se crean los botones Guardar y Listar-->
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-8">
						<input id="" type="submit" class="btn btn-primary" value="Guardar">
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
</header>
    
<main class="main">
    
        <!--Línea donde se inserta el botón para editar los registros -->
	<div class="row">
		<div id="cuadro3" class="cuadro3 col-lg-12 ">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 ">
						<button type= 'button' class='leyenda_colores btn btn-secondary btn-sm' data-toggle='modal' data-target='#modal_leyenda_colores'>Leyenda <i class='fas fa-question-circle fa-lg'></i>
						</button>
                        <input id="btn_autoprogramar" type="button" class="btn btn-primary btn-sm" value="Autoprogramar Actividades">
                        <input id="btn_agregar_actividad" type="button" class="btn btn-primary btn-sm" value="Agregar Actividad">
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:10">
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal_tramites&semana=$semana"?>'">Trámites <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal_consultores&semana=$semana"?>'">Consultores <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal_periodicas_simples&semana=$semana"?>'">Tareas Periódicas Simples <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm  active" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal_periodicas_compuestas&semana=$semana"?>'">Tareas Periódicas Compuestas <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal_propias&semana=$semana"?>'">Tareas Propias <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=evaluacion_semanal&semana=$semana"?>'">Evaluación Semanal</button>
                            <!--<button id="btn_CNC" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNC&semana=$semana"?>'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CIC&semana=$semana"?>'">Calificación de Sub-Contratistas <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=indicadores&semana=$semana"?>'">Indicadores</button>-->
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
                            <th>Consecutivo</th>
							<th>Id</th>
							<th>Actividad</th>
                            <th>Descripción</th>
                            <th>Clase</th>
                            <th>¿Liberada?</th>
							<th>Tercero Responsable</th>
                            <th>Profesional AIA</th>
                            <th>Unidad</th>
                            <th>Cantidad Comprometida</th>
                            <th>Cantidad Real</th>
                            <th>% Completado</th>
                            <th>PAC</th>											
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
            <input type="hidden" id="Responsable_AIA" name="Responsable_AIA" value="" readonly>
            <input type="hidden" id="Categoria_CNP_base" name="Categoria_CNP_base" value="" readonly>
            <input type="hidden" id="CNP_base" name="CNP_base" value="" readonly>
            <input type="hidden" id="Observacioones_CNP_base" name="Observacioones_CNP_base" value="" readonly>
            <input type="hidden" id="Activa" name="Activa" value="" readonly>
            
            
            
            
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
			<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEliminarLabel">Eliminar Actividad</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">
                            <p class="modal-body-texto-eliminar" id="modal-body-texto-eliminar"></p>
						</div>
						<div class="modal-footer">
							<button type="button" id="continuar_modal_CNP" class="btn btn-primary" data-dismiss="modal" data-toggle="modal" data-target="#modal_CNP" onClick='asignar_CNP()' >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());">Cancelar</button>
						</div>
					</div>
				</div>
			</div>
			<!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de duplicar un registro o no -->
			<div class="modal fade" id="modalDuplicar" tabindex="-1" role="dialog" aria-labelledby="modalDuplicarLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalDuplicarLabel">Duplicar Actividad</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">
                            <p class="modal-body-texto-duplicar" id="modal-body-texto-duplicar"></p>
						</div>
						<div class="modal-footer">
							<button type="button" id="duplicar-usuario" class="btn btn-primary" data-dismiss="modal" >Aceptar</button>
							<button type="button" class="btn btn-default" data-dismiss="modal" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());">Cancelar</button>
						</div>
					</div>
				</div>
			</div>
			<!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de duplicar un registro o no -->
			<!--<div class="modal fade" id="modalEstadoEjecucion" tabindex="-1" role="dialog" aria-labelledby="modalEstadoEjecucionLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="modalEstadoEjecucionLabel">Estado de Ejecución de la Actividad</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body">
                            <p class="modal-body-texto-EstadoEjecucion" id="modal-body-texto-EstadoEjecucion"></p>
						</div>
						<div class="modal-footer">
							<button type="button" id="EstadoEjecucion-usuario" class="btn btn-primary" data-dismiss="modal" >Sí</button>
							<button type="button" class="btn btn-danger" data-dismiss="modal" onClick="location.assign('posicion_semanal_periodicas_compuestas.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());">No</button>
						</div>
					</div>
				</div>
			</div>-->
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
            
            <div class="modal_CNP modal fade" id="modal_CNP" tabindex="-1" role="dialog" aria-labelledby="modal_CNPLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="modalEliminarLabel"><p class="modal-body-texto-CNP" id="modal-body-texto-CNP"></p></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
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
                                            <input type="hidden" id="opcion" name="opcion" value="CNP">
                                                                                            
                                                <div class="form-group" style="width:100%;">
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="Responsable_AIA_CNP" class="col-sm-12 control-label">Profesional de AIA Encargado de la Actividad</label>
                                                        <div class="col-sm-8"><select id="Responsable_AIA_CNP" name="Responsable_AIA_CNP" class="form-control" >
                                                        <option value=""></option>
                                                        <?php
                                                            require("../conexion.php");
                                                            $query="SELECT * FROM $db"."_profesionales";
                                                            $resultado= mysqli_query($conexion, $query);
                                                            while ($valores = mysqli_fetch_array($resultado)){
                                                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                                                            };
                                                            mysqli_close($conexion);
                                                        ?>
                                                        </select>
                                                        </div>	
                                                    </div>
                                                    <!--<div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="Categoria_CNP" class="col-sm-8 control-label">Categoría</label>
                                                        <div class="col-sm-8"><select id="Categoria_CNP" name="Categoria_CNP" class="form-control">
                                                        <option value=""></option>
                                                        <option value="Rendimiento">Rendimiento</option>
                                                        <option value="Programación">Programación</option>
                                                        <option value="Mano de Obra">Mano de Obra</option>
                                                        <option value="Materiales">Materiales</option>
                                                        <option value="Equipos">Equipos</option>
                                                        <option value="Diseños">Diseños</option>
                                                        <option value="Administrativas">Administrativas</option>
                                                        </select>
                                                        </div>	
                                                    </div>-->
                                                    
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="CNP" class="col-sm-8 control-label">Causa de No Programación</label>

                                                        <div class="col-sm-12"><select id="CNP" name="CNP" class="form-control" onchange="activar_otra_CNP(value);">
                                                        <option value=""></option>
                                                        <option value="Retraso Por Entidad">Retraso Por Entidad</option>
                                                        <option value="Retraso Por Actividad Predecesora">Retraso Por Actividad Predecesora</option>
                                                        <option value="Falta de Coordinación">Falta de Coordinación</option>
                                                        <option value="Lookahead">Lookahead</option>
                                                        <option value="Otra">Otra</option>
                                                        </select>
                                                        </div>	

                                                    </div>
                                                    
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="Otra_CNP" class="col-sm-12 control-label" id="Otra_CNP_label" hidden>¿Cual es la Causa de No Programación?</label>
                                                        
                                                        <div class="col-sm-12"><input id="Otra_CNP" name="Otra_CNP" class="form-control" type="hidden" value="" readonly>
                                                        </div>	

                                                    </div>
                                                    
                                                    
                                                    
                                                    <div class="form-group" style="width:100%;">
                                                        <label for="Observaciones_CNP" class="col-sm-12 control-label">Observaciones</label>
                                                        <div class="col-sm-12"><textarea id="Observaciones_CNP" name="Observaciones_CNP" class="form-control" ></textarea></div>
                                                    </div>
                                                </div>


                                            <!--Se crean los botones Guardar y Listar-->
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-8">
                                                    <input id="eliminar-usuario" type="button" class="btn btn-primary" data-dismiss="modal" value="Guardar">
                                                    <input id="btn_cancelar" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());" >
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
            
            <div class="modal_CNC modal fade" id="modal_CNC" tabindex="-1" role="dialog" aria-labelledby="modal_CNCLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="modalEliminarLabel"><p class="modal-body-texto-CNC" id="modal-body-texto-CNC"></p></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());"><span aria-hidden="true">&times;</span></button>
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
                                            <input type="hidden" id="opcion" name="opcion" value="CNP">
                                                                                            
                                                <div class="form-group" style="width:100%;">
                                                    <!--<div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="Responsable_AIA_CNC" class="col-sm-12 control-label">Profesional de AIA Encargado de la Actividad</label>
                                                        <div class="col-sm-8"><select id="Responsable_AIA_CNC" name="Responsable_AIA_CNC" class="form-control" >
                                                        <option value=""></option>
                                                        <?php
                                                            require("../conexion.php");
                                                            $query="SELECT * FROM $db"."_profesionales";
                                                            $resultado= mysqli_query($conexion, $query);
                                                            while ($valores = mysqli_fetch_array($resultado)){
                                                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                                                            };
                                                            mysqli_close($conexion);
                                                        ?>
                                                        </select>
                                                        </div>	
                                                    </div>-->
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="CNC" class="col-sm-8 control-label">Causa de No Cumplimiento</label>

                                                        <div class="col-sm-12"><select id="CNC" name="CNC" class="form-control" onchange="activar_otra_CNC(value);">
                                                        <option value=""></option>
                                                        <option value="Retraso Por Entidad">Retraso Por Entidad</option>
                                                        <option value="Retraso Por Actividad Predecesora">Retraso Por Actividad Predecesora</option>
                                                        <option value="Falta de Coordinación">Falta de Coordinación</option>
                                                        <option value="Lookahead">Lookahead</option>
                                                        <option value="Otra">Otra</option>
                                                        </select>
                                                        </div>	

                                                    </div>
                                                    
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <label for="Otra_CNC" class="col-sm-12 control-label" id="Otra_CNC_label" hidden>¿Cual es la Causa de No Cumplimiento?</label>
                                                        
                                                        <div class="col-sm-12"><input id="Otra_CNC" name="Otra_CNC" class="form-control" type="hidden" value="" readonly>
                                                        </div>	

                                                    </div>
                                                    
                                                    <div class="form-group" style="width:600px; max-width:100%">
                                                        <p class="error-CNC" id="error-CNC"></p>
                                                    </div>
                                                    
                                                    <div class="form-group" style="width:100%;">
                                                        <label for="Observaciones_CNC" class="col-sm-12 control-label">Observaciones</label>
                                                        <div class="col-sm-12"><textarea id="Observaciones_CNC" name="Observaciones_CNC" class="form-control" ></textarea></div>
                                                    </div>
                                                </div>


                                            <!--Se crean los botones Guardar y Listar-->
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-8">
                                                    <input id="btn_guardar_CNC" type="button" class="btn btn-primary"  value="Guardar">
                                                    <input id="btn_cancelar" type="button" class="btn btn-danger" data-dismiss="modal" value="Cancelar" onClick="location.assign('posicion_semanal.php?posicion_semanal='+ $('.dataTables_scrollBody').scrollTop());" >
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
                    
                    <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
                    <div class="modal fade" id="modal_leyenda_colores" role="dialog">
                    <div class="modal-dialog modal-lg">
        
                      <!-- Modal content-->
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title" id="modal_leyenda_colores_Label">Leyenda de Colores de Las Actividades</h4>
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body" style="margin:auto">
                            <img src="../imagenes/Leyenda_LPS.png" class="d-inline-block align-top" style="margin:auto; width:100%; max-width:800px" alt="">
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                        </div>
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
            guardar_formulario();
            eliminar_duplicar();
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_listar").on("click", function(){
            listar();
            $("#cuadro2").slideUp("slow");
            $("#cuadro1").slideDown("slow");
            $("#cuadro3").slideDown("slow");
        });
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            location.reload(true);
            //listar();
            
            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
    
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar_formulario = function(){
			$("form").on("submit", function(e){
				e.preventDefault();
				var frm = $(this).serialize();
                console.log(frm);
				$.ajax({
					method: "POST",
					url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					var json_info = JSON.parse( info );
                    mostrar_mensaje( json_info );
					limpiar_datos();
					listar();
                    location.reload(true);
				});
			});
		}
        
        var guardar = function(){
			$("#btn_guardar1").on("click", function(e){
				e.preventDefault();
				var Id = $("form #Id0").serialize();
                var semana = $("form #semana0").serialize();
                var opcion = $("form #opcion0").serialize();
                
                var Descripcion=$("#select_Descripcion").serialize();
                var Clase=$("#select_Clase").serialize();
                var Sub_Contratista=$("#select_Sub_Contratista").serialize();
                var Responsable_AIA=$("#select_Responsable_AIA").serialize();
                var Unidad=$("#select_Unidad").serialize();
                var Compromiso=$("#select_Compromiso").serialize();
                var Ejecutado_Real=$("#select_Ejecutado_Real").serialize();
                var Compromiso1=$("#select_Compromiso").val();
                var Ejecutado_Real1=$("#select_Ejecutado_Real").val();
                //var Categoria_CNC=$("#Categoria_CNC").serialize();
                if($("#CNC").val() == 'Otra'){
                    var CNC=$("#Otra_CNC").serialize();
                }else{
                    var CNC=$("#CNC").serialize();
                }
                var Observaciones_CNC=$("#Observaciones_CNC").serialize();
                
                if((Ejecutado_Real1 - Compromiso1)<0 && Ejecutado_Real1 !='' && Compromiso1 !=''){
                    $("#btn_generar_CNC").click(); 
                    //$('#CNC').attr('readonly', true);
                    //cnc();
                    guardar_CNC();
                }else{
                    frm=Id+"&"+semana+"&"+opcion+"&"+Descripcion+"&"+Clase+"&"+Sub_Contratista+"&"+Responsable_AIA+"&"+Unidad+"&"+Compromiso+"&"+Ejecutado_Real+"&CNC=&Observaciones_CNC=";
                    console.log(frm);
                    $.ajax({
                        method: "POST",
                        url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
                        contenttype:"charset=utf-8",
                        data: frm,
                    }).done( function( info ){
                        var posicion=$('.dataTables_scrollBody').scrollTop();
                        location.assign("posicion_semanal_periodicas_compuestas.php?posicion_semanal="+posicion);
                    });    
                }
			});
		}
        
        
        var  guardar_CNC= function(){
			$("#btn_guardar_CNC").on("click", function(){
				var Id = $("form #Id0").serialize();
                var semana = $("form #semana0").serialize();
                var opcion = $("form #opcion0").serialize();
                
                var Descripcion=$("#select_Descripcion").serialize();
                var Clase=$("#select_Clase").serialize();
                var Sub_Contratista=$("#select_Sub_Contratista").serialize();
                var Responsable_AIA=$("#select_Responsable_AIA").serialize();
                var Unidad=$("#select_Unidad").serialize();
                var Compromiso=$("#select_Compromiso").serialize();
                var Ejecutado_Real=$("#select_Ejecutado_Real").serialize();
                //var Categoria_CNC=$("#Categoria_CNC").serialize();
                if($("#CNC").val() == 'Otra'){
                    var CNC=$("#Otra_CNC").val();
                }else{
                    var CNC=$("#CNC").val();
                }
                
                var Observaciones_CNC=$("#Observaciones_CNC").serialize();
                
                if($("#CNC").val()!=''){
                   if(($("#CNC").val()=='Otra' && $("#Otra_CNC").val()!='') || $("#CNC").val()!='Otra') {
                        frm=Id+"&"+semana+"&"+opcion+"&"+Descripcion+"&"+Clase+"&"+Sub_Contratista+"&"+Responsable_AIA+"&"+Unidad+"&"+Compromiso+"&"+Ejecutado_Real+"&CNC="+CNC+"&"+Observaciones_CNC;
                         console.log(frm);
                        $.ajax({
                            method:"POST",
                            url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
                            contenttype:"charset=utf-8",
                            data: frm,
                        }).done( function( info ){
                            var posicion=$('.dataTables_scrollBody').scrollTop();
                            location.assign("posicion_semanal_periodicas_compuestas.php?posicion_semanal="+posicion);
                        });   
                   }else{
                       var texto=$("#error-CNC").text("Error: Debe definir la Causa de No Cumplimiento");
                   }     
                }else{
                    var texto=$("#error-CNC").text("Error: Debe definir la Causa de No Cumplimiento");
                }
                 
			});
		}
        
        
        /* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
        var eliminar_duplicar = function(){
			$("#eliminar-usuario, #duplicar-usuario").on("click", function(){
				var Id = $("#frmEliminarUsuario #Id").val(),
					opcion = $("#frmEliminarUsuario #opcion").val(),
                    semana = $("#frmEliminarUsuario #semana").val(),
                    Actividad = $("#frmEliminarUsuario #Actividad").val(),
                    Responsable_AIA = $("#Responsable_AIA_CNP").val();
                    //Categoria_CNP = $("#Categoria_CNP").val(),
                    if($("#CNP").val() == 'Otra'){
                        var CNP=$("#Otra_CNP").val();
                    }else{
                        var CNP=$("#CNP").val();
                    }
                var Observaciones_CNP = $("#Observaciones_CNP").val();
                    //console.log(opcion, semana, Id, Actividad, CNP);
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"Id": Id, "semana": semana, "Actividad": Actividad, "opcion": opcion, "Responsable_AIA": Responsable_AIA, "CNP": CNP, "Observaciones_CNP": Observaciones_CNP}
				}).done( function( info ){
					//var json_info = JSON.parse( info );
					//mostrar_mensaje( json_info );
					//limpiar_datos();
					var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("posicion_semanal_periodicas_compuestas.php?posicion_semanal="+posicion);
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
            $("#Clase").val("");
            $("#Sub_Contratista").val("").focus();
            $("#Responsable_AIA").val("");
            $("#Unidad").val("");
            $("#Compromiso").val("");
            $("#Real").val("");
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");

		}
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/

            
        var listar=function(){
            var posicion= <?php 
            if(isset($_SESSION["posicion_semanal"])){
                echo $_SESSION["posicion_semanal"];
            }else{
                echo 0;
            }
            ?>;
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/            

                var alturahoja= $(window).height();
                var alturatabla= ((1.28*alturahoja)-513.21);
                
                $("#cuadro2").hide();
                //$("#cuadro2").slideUp("slow");
                //$("#cuadro3").slideDown("slow");
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
                      "url":"../programacion_semanal/listar_programacion_semanal_periodicas_compuestas.php<?php echo "?db=$db&semana=$semana"?>"
                  },


                    'columnDefs': [


                         {
                            'targets': [0,1,2,5,6,7,8,9,10,11,12,13],
                            'width': "5%",
                         },
                        
                        {
                            'targets': [3,4],
                            'width': "15%",
                         },

                        {
                            'targets': [3,7,8,9,10,11],
                            'render': function ( data, type, full, meta ) {
                                    return "<h6>" + data + "</h6>";
                                }
                        },
                        
                        {
                            'targets': [6],
                            'render': function ( data, type, full, meta ) {
                                if(data===""){
                                    data="";
                                    return "<h6>" + data + "</h6>";
                                }else if(data==0){
                                    data="Sí";
                                    return "<h6>" + data + "</h6>";
                                }else if (data==1){
                                    data="No";
                                    return "<h6>" + data + "</h6>";
                                }

                            },
                        },
                        
                        {
                            'targets': [12],
                            'render': function ( data, type, full, meta ) {
                                if(data==""){
                                    return '<h6>' + data + '</h6>';
                                }else{
                                    data=data*100;
                                    data=data.toFixed(0);
                                    return '<h6>' + data + '%</h6>';
                                }

                            },
                        },
                        
                        {
                            'targets': [13],
                            'render': function ( data, type, full, meta ) {
                                if(data=='' || data==null){
                                    data="";
                                    return '<h6>' + data + '</h6>';
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
                                    return '<h6>' + data + '%</h6>' +carita;
                                }                                
                            },
                        },
                        
                        {
                            'targets': [0],
                            'className': 'Botones'
                        },                    
                        {
                            'targets': [4],
                            'className': 'input_Descripcion'
                        },
                        {
                            'targets': [5],
                            'className': 'input_Clase'
                        },
                        {
                            'targets': [7],
                            'className': 'input_Sub_Contratista'
                        },
                        {
                            'targets': [8],
                            'className': 'input_Responsable_AIA'
                        },
                        {
                            'targets': [9],
                            'className': 'input_Unidad'
                        },
                        {
                            'targets': [10],
                            'className': 'input_Compromiso'
                        },
                        {
                            'targets': [11],
                            'className': 'input_Ejecutado_Real'
                        },
                      ],



                    'select': {
                     'style': 'false',
                    },  

                    "lengthMenu": [100],

                    "columns":[
                        {"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'  title='Editar Actividad' style='margin:1px'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='duplicar btn btn-success btn-sm'  title='Duplicar Actividad' style='margin:1px' data-toggle='modal' data-target='#modalDuplicar' ><i class='fa fa-clone fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm'  title='Eliminar Actividad' style='margin:1px' data-toggle='modal' data-target='#modalEliminar' ><i class='fa fa-trash-alt fa-xs'></i></button>"},
                        {"data":"Consecutivo", "visible":false},
                        {"data":"Id"},
                        {"data":"Actividad"},
                        {"data":"Descripcion"},
                        {"data":"Clase"},
                        {"data":"Prog_Sin_Restricciones_100"},
                        {"data":"Sub_Contratista",},
                        {"data":"Responsable_AIA"},
                        {"data":"Unidad"},
                        {"data":"Compromiso"},
                        {"data":"Ejecutado_Real"},
                        {"data":"P_Completado"},
                        {"data":"PAC"},
                    ],
                    
                    "createdRow": function( row, data, dataIndex){
    
                        
                        if(data.Atrasada==1){
                            $(row).addClass('Semanal_Atrasada');
                        } else if(data.Critica==1){
                            $(row).addClass('Semanal_Critica');
                        } else if(data.Critica==0){
                            $(row).addClass('Semanal_No_Critica');
                        }
                        
    
                    },
                    

                    "language": idioma_espanol
                });
                
                ocultos(table);
                nueva_actividad();
                autoprogramar();
                cambiar_posicion(posicion);
                <?php $_SESSION["posicion_semanal"]=0 ?>;
                obtener_data_editar("#dt_general tbody", table);
                obtener_id_eliminar("#dt_general tbody", table);
                obtener_id_duplicar("#dt_general tbody", table);
                obtener_id_generar_CNC("#dt_general tbody", table)
        }
        
//        var contar_cajas_checkeadas=function()
        
        
        /*Para agregar un nuevo usuario en la base de datos*/
        var agregar_nuevo_usuario = function(){
            limpiar_datos();
            $("#cuadro2").slideDown("slow");
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
            
            $(tbody).on("click", "td", function(){
                
                if(only_once==true){
                    var data= table.row($(this).parents("tr")).data();
                    var Id=$("#Id0").val(data.Consecutivo),
                        semana = $("#semana0").val(data.Semana),
                        opcion = $("#opcion0").val("modificar");
                    var codigo_html_Descripcion = "<textarea id='select_Descripcion' name='Descripcion' class='form-control'>'"+data.Descripcion+"'</textarea>";
                    $( this ).parent().find('.input_Descripcion').html(codigo_html_Descripcion);
                    
                   var codigo_html_Clase = "<input id='select_Clase' name='Clase' class='form-control' type='text' value='"+data.Clase+"' readonly></input>";
                    $( this ).parent().find('.input_Clase').html(codigo_html_Clase);
                    
                    var Sub_Contratista = <?php
                                            require("../conexion.php");
                                            $query="SELECT * FROM $db"."_subcontratistas";
                                            $resultado= mysqli_query($conexion, $query);
                                            $Sub_Contratista="";
                                            while ($valores = mysqli_fetch_array($resultado)){
                                                $valor=$valores["subcontratista"];
                                                $Sub_Contratista .="<option value='$valor'>$valor</option>";   
                                            };
                                            echo '"'.$Sub_Contratista.'"';
                                            mysqli_close($conexion);
                                        ?>;
                    var codigo_html_Sub_Contratista = "<select id='select_Sub_Contratista' name='Sub_Contratista' class='form-control' ><option value=''></option>"+Sub_Contratista+"</select>";
                    $( this ).parent().find('.input_Sub_Contratista').html(codigo_html_Sub_Contratista);
                    
                    var Responsable_AIA = <?php
                                            require("../conexion.php");
                                            $query="SELECT * FROM $db"."_profesionales";
                                            $resultado= mysqli_query($conexion, $query);
                                            $Responsable_AIA="";
                                            while ($valores = mysqli_fetch_array($resultado)){
                                                $valor=$valores["nombre"];
                                                $Responsable_AIA .="<option value='$valor'>$valor</option>";   
                                            };
                                            echo '"'.$Responsable_AIA.'"';
                                            mysqli_close($conexion);
                                        ?>;
                    var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control' ><option value=''></option>"+Responsable_AIA+"</select>";
                    $( this ).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA);
                    
                    var codigo_html_Unidad = "<input id='select_Unidad' name='Unidad' class='form-control' type='text' value='%' readonly></input>";
                    $( this ).parent().find('.input_Unidad').html(codigo_html_Unidad);
                    
                    var codigo_html_Compromiso = "<input id='select_Compromiso' name='Compromiso' class='form-control' type='text' value='"+data.Compromiso+"'></input>";
                    $( this ).parent().find('.input_Compromiso').html(codigo_html_Compromiso);
                    
                    var codigo_html_Ejecutado_Real = "<input id='select_Ejecutado_Real' name='Real' class='form-control' type='text' value='"+data.Ejecutado_Real+"'></input>";
                    $( this ).parent().find('.input_Ejecutado_Real').html(codigo_html_Ejecutado_Real);
                    
                    
                    var codigo_html_botones = "<button type= 'button' id='btn_guardar1' class='guardar btn btn-success btn-xs' style='padding:5px; margin:1px' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar1' class='cancelar btn btn-danger btn-xs' style='padding:5px; margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_generar_CNC' class='generar_CNC btn btn-warning btn-sm'  title='Estado de ejecución de la Actividad' style='margin:1px' hidden='hidden'><i class='fa fa-edit fa-xs'></i></button>";
                    $( this ).parent().find('.Botones').html(codigo_html_botones);
                    $("#select_Descripcion").val(data.Descripcion).change();
                    $("#select_Descripcion").focus();
                    $("#select_Clase").val(data.Clase).change();
                    $("#select_Sub_Contratista").val(data.Sub_Contratista).change();
                    $("#select_Responsable_AIA").val(data.Responsable_AIA).change();
                    $("#select_Unidad").val('%').change();
                    $("#select_Compromiso").val(data.Compromiso).change();
                    $("#select_Ejecutado_Real").val(data.Ejecutado_Real).change();
                    //$(".input_Ejecutado_Editar").select();
                    $(".input_Descripcion, .input_Clase, .input_Sub_Contratista, .input_Responsable_AIA, .input_Unidad, .input_Compromiso, .input_Ejecutado_Real").keypress(function(e){
                        if(e.keyCode==13){
                            $("#btn_guardar1").click();
                        }
                    });
                    $(".input_Descripcion, .input_Clase, .input_Sub_Contratista, .input_Responsable_AIA, .input_Unidad, .input_Compromiso, .input_Ejecutado_Real").keyup(function(e){
                        if(e.keyCode==27){
                            $("#btn_cancelar1").click();
                        }
                    });
                    only_once = false;

                    $("#btn_cancelar1").on("click", function(){
                        var posicion=$('.dataTables_scrollBody').scrollTop();
                        location.assign("posicion_semanal_periodicas_compuestas.php?posicion_semanal="+posicion);
                        //cambiar_posicion(posicion);
                        only_once = true;
                        $(document).on("ready", function(){
                            $("#navbarNav").addClass("show");
                        });
                    });
                        
                        
                }
                
                    //$("#cuadro2").slideDown("slow");
                    //$("#cuadro3").slideUp("slow");
                    //$("#cuadro1").slideUp("slow");
            guardar();        
            });
        }
        
        
        /*Toma los datos de la fila en la que se presionó el botón editar*/
        var nueva_actividad=function(tbody, table){
            $("#btn_agregar_actividad").on("click", function(){
                var opcion = $("#opcion0").val("nuevo");
                
                $("#Id1").val("").change();
                
                $("#semana0").val(<?php echo $semana?>).change();
                
                $("#Actividad").val("").change();
                
                $("#Clase").val("periodicas_compuestas").change();
                
                $("#Sub_Contratista").val("").change();
                
                $("#Responsable_AIA").val("").change();
                
                $("#Unidad").val("%").change();
                
                $("#Compromiso").val("").change();
                
                $("#Real").val("").change();
                  
                $("#actualizacion").text("Nueva Actividad");
                
                $('#Real').attr('readonly', true);
                
                $('#Actividad').attr('readonly', false);
                
                $('#Id1').attr('readonly', false);
                    
                    $("#cuadro2").slideDown("slow");
                    $("#cuadro3").slideUp("slow");
//                    $("#cuadro1").slideUp("slow");      
            });
        }

        /*Toma los datos de la fila en la que se presionó el botón eliminar*/
        var obtener_id_eliminar=function(tbody, table){
            $(tbody).on("click", "button.eliminar", function(){
                var data= table.row($(this).parents("tr")).data();
                var idusuario=$("#frmEliminarUsuario #Id").val(data.Consecutivo);
                var semana=$("#frmEliminarUsuario #semana").val(data.Semana);
                var Actividad=$("#frmEliminarUsuario #Actividad").val(data.Actividad);
                var Responsable_AIA=$("#frmEliminarUsuario #Responsable_AIA").val(data.Responsable_AIA);
                var Categoria_CNP=$("#frmEliminarUsuario #Categoria_CNP").val(data.Categoria_CNP);
                var CNP=$("#frmEliminarUsuario #CNP").val(data.CNP);
                var Observaciones_CNP=$("#frmEliminarUsuario #Observaciones_CNP").val(data.Observaciones_CNP);
                var Activa=$("#frmEliminarUsuario #Activa").val(data.Activa);
                var texto=$("#modal-body-texto-eliminar").html("¿Desea elminar de la programación semanal la actividad: "+data.Actividad+"?");
            });
        }
        
        /*Toma los datos de la fila en la que se presionó el botón duplicar*/
        var obtener_id_duplicar=function(tbody, table){
            $(tbody).on("click", "button.duplicar", function(){
                var data= table.row($(this).parents("tr")).data();
                var idusuario=$("#frmEliminarUsuario #Id").val(data.Consecutivo);
                var semana=$("#frmEliminarUsuario #semana").val(data.Semana);
                var Actividad=$("#frmEliminarUsuario #Actividad").val(data.Actividad);
                var opcion=$("#frmEliminarUsuario #opcion").val("duplicar");
                var texto=$("#modal-body-texto-duplicar").html("¿Desea duplicar la actividad: "+data.Actividad+"?");
            });
        }
        
        /*Toma los datos de la fila en la que se presionó el botón duplicar*/
        var obtener_id_generar_CNC=function(tbody, table){
            $(tbody).on("click", "button.generar_CNC", function(){
                var data= table.row($(this).parents("tr")).data();
                var idusuario=$("#frmEliminarUsuario #Id").val(data.Consecutivo_En_Programa);
                var semana=$("#frmEliminarUsuario #semana").val(data.Semana);
                var Actividad=$("#frmEliminarUsuario #Actividad").val(data.Actividad);
                var opcion=$("#frmEliminarUsuario #opcion").val("CNC");
                var Compromiso=$("#select_Compromiso").val();
                var Ejecutado_Real=$("#select_Ejecutado_Real").val();
                if((Ejecutado_Real-Compromiso)>=0){
                    var PAC=1;
                }else{
                    var PAC=0;
                };
                var texto=$("#modal-body-texto-CNC").html("Indique la Causa de No Cumplimiento de la actividad: "+data.Actividad);
                //console.log(Compromiso, Ejecutado_Real);
                if(PAC==0){
                    $("#modal_CNC").modal("show");    
                }else{
                    var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("posicion_semanal_periodicas_compuestas.php?posicion_semanal="+posicion);
                }
                
            });
        }
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
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
        var autoprogramar=function(){
            $("#btn_autoprogramar").on("click", function(){            
                opcion="autoprogramar";
                semana=<?php echo $semana?>;
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
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
					url: "../programacion_semanal/guardar_programacion_semanal_periodicas_compuestas.php?db=<?php echo $db?>",
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
        
        var activar_otra_CNP=function(value){
            if(value=='Otra'){
                $('#Otra_CNP').attr('readonly', false);
                $('#Otra_CNP').attr('type', 'text');
                $('#Otra_CNP_label').attr('hidden', false);
            }else{
                $('#Otra_CNP').attr('readonly', true); 
                $('#Otra_CNP').attr('type', 'hidden');
                $('#Otra_CNP_label').attr('hidden', true);
            }
        }
        
        var activar_otra_CNC=function(value){
            if(value=='Otra'){
                $('#Otra_CNC').attr('readonly', false);
                $('#Otra_CNC').attr('type', 'text');
                $('#Otra_CNC_label').attr('hidden', false);
            }else{
                $('#Otra_CNC').attr('readonly', true); 
                $('#Otra_CNC').attr('type', 'hidden');
                $('#Otra_CNC_label').attr('hidden', true);
            }
        }
        
/*        var cnc=function(){
            $('#Categoria_CNC').on('change', function() {
                var categoria=$("#Categoria_CNC").val(),
                    opcion="CNC";
                    //console.log(categoria);
                    if(categoria===""){
                        $('#CNC').attr('readonly', true); 
                        $('#CNC').html("<option value=''></option>"); 
                    } else{
                        $('#CNC').attr('readonly', false); 
                        $.ajax({
                            method:"POST",
                            url: "../programacion_semanal/guardar_CNC.php?db=login",
                            contenttype:"charset=utf-8",
                            data: {"categoria": categoria, "opcion":opcion},
                            success:function(a){
                                $('#CNC').html(a);
                                //$("#CNC option[value='"+CNC+"']").attr('selected', true); 
                            }
                        }); 
                    } 
                });
            }
        
            var cnp=function(CNP){
            $('#Categoria_CNP').on('change', function() {
                var categoria=$("#Categoria_CNP").val(),
                    opcion="CNC";
                    //console.log(categoria);
                    if(categoria===""){
                        $('#CNP').attr('readonly', true); 
                        $('#CNP').html("<option value=''></option>"); 
                    } else{
                        $('#CNP').attr('readonly', false); 
                        $.ajax({
                            method:"POST",
                            url: "../programacion_semanal/guardar_CNC.php?db=login",
                            contenttype:"charset=utf-8",
                            data: {"categoria": categoria, "opcion":opcion},
                            success:function(a){
                                $('#CNP').html(a);
                                $("#CNP option[value='"+CNP+"']").attr('selected', true); 
                            }
                        }); 
                    } 
                });
            }*/
            
            var asignar_CNP=function(){
                var Activa=$("#frmEliminarUsuario #Activa").val();
                if(Activa=='NA'){
                    $("#eliminar-usuario").click();
                }else{
                    var Actividad=$("#frmEliminarUsuario #Actividad").val();
                    var texto=$("#modal-body-texto-CNP").html("Indique la Categoría y Causa de No programación de la actividad: "+Actividad);
                    var Responsable_AIA=$("#frmEliminarUsuario #Responsable_AIA").val();
                    $('#Responsable_AIA_CNP').val(Responsable_AIA).change(); 
                    var CNP=$("#frmEliminarUsuario #CNP_base").val();
                    //cnp(CNP);  
                    //var Categoria_CNP=$("#frmEliminarUsuario #Categoria_CNP_base").val();
                    //$('#Categoria_CNP').val(Categoria_CNP).change(); 
                    var Observaciones_CNP=$("#frmEliminarUsuario #Observaciones_CNP_base").val();
                    $('#Observaciones_CNP').val(Observaciones_CNP).change();
                } 
            }
            
        var cambiar_posicion=function(p){
            $('#dt_general').on( 'draw.dt', function () {
            $('.dataTables_scrollBody').scrollTop(p);
            } );
        }        
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="G"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="S"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
                table.column( 0 ).visible( false );
                //window.location.href='CIC.php';
            }
            
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            
            if((max_semana-2)>=semana){
                $('#btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
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