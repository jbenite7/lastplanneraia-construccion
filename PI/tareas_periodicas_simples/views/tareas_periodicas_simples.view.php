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
    
    <!--Selector de fechas -->
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
                $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas WHERE Semana<=$semana)";
                $resultado1= mysqli_query($conexion, $query1);
                $data1=mysqli_fetch_assoc($resultado1);
                $Fecha_Inicio_Sem=$data1["Fecha_Inicio_Sem"];
                $Fecha_Inicio_SemYMD=$Fecha_Inicio_Sem;
                $Fecha_Inicio_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Inicio_Sem"));
                
                $Fecha_Fin_Sem=$data1["Fecha_Fin_Sem"];
                $Fecha_Fin_SemYMD=$Fecha_Fin_Sem;
                $Fecha_Fin_Sem=date("Y, n - 1, d, H, i, s",strtotime("$Fecha_Fin_Sem"));
                
                require ("../conexion.php");
                $query2="SELECT Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=(SELECT MAX(Semana) FROM $db"."_semanas_activas WHERE Semana<=$semana)";
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
                    <li class="pagina"> - Tareas Periódicas Simples / Semana <?php echo $semana ?></li>
                </ul>
              </a>
              <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                  <li class="nav-item">
                    <a class="nav-link" href="<?php echo"../cambiar_pagina.php?seccion=contenido&semana=$semana"?>">Contenido</a>
                  </li>
                  <li class="nav-item dropdown">
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
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
		<div id="cuadro2" class="col-sm-12 col-md-12 col-lg-12 ">
			<form class="form_modificar form-horizontal" action="" method="POST">
<!--
				<div class="form-group">
					<h3 class="col-sm-offset-2 col-sm-8 text-center">					
					Formulario de Registro de Usuarios</h3>
				</div>
-->
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id" name="Id" value="0">
                <input type="hidden" id="Id1" name="Id1" value="0">
				<input type="hidden" id="opcion" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
<!--				<div class="form-group">
					<label for="Id1" class="col-sm-2 control-label">Id</label>
					<div class="col-sm-8"><input id="Id1" name="Id1" type="text" class="form-control"  autofocus></div>				
				</div>     
				<div class="form-group">
					<label for="Actividad" class="col-sm-2 control-label">Actividad</label>
					<div class="col-sm-8"><input id="Actividad" name="Actividad" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Fecha_Inicio" class="col-sm-2 control-label">Fecha Inicio</label>
					<div class="col-sm-8"><input id="Fecha_Inicio" name="Fecha_Inicio" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Fecha_Fin" class="col-sm-2 control-label">Fecha Fin</label>
					<div class="col-sm-8"><input id="Fecha_Fin" name="Fecha_Fin" type="text" class="form-control" ></div>
				</div>
				<div class="form-group">
					<label for="Critica" class="col-sm-2 control-label">Crítica</label>
					<div class="col-sm-8"><input id="Critica" name="Critica" type="text" class="form-control" maxlength="1"></div>
				</div>-->
				<!--<div class="form-group">
                    <br>
					<label for="Ejecutado" class="col-sm-2 control-label">Ejecutado</label>
					<div class="col-sm-2"><input class="Ejecutado slider form-control"id="Ejecutado" name="Ejecutado" type="range" min="0.0" max="1.0" step="0.01" value="0.00">
                        <span id="valor_Ejecutado"></span></div>
				</div>-->
<!--				<div class="form-group">
					<label for="Estado" class="col-sm-2 control-label">Estado</label>
					<div class="col-sm-8"><input id="Estado" name="Estado" type="text" class="form-control" ></div>
				</div>-->
                
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
		<div id="cuadro3" class="cuadro3 col-lg-12" style="height:40px">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 ">
						<button type= 'button' class='leyenda_colores btn btn-primary btn-sm' data-toggle='modal' data-target='#modal_leyenda_colores'>Leyenda <i class='fas fa-question-circle fa-lg'></i>
						</button>
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:10; float:right; margin-left:2%; margin-right:35%">
                            <button id="btn_total" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('total')"><h6>Totales</h6></button>
                            <button id="btn_lookahead" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('lookahead')"></button>
                            <button id="btn_no_iniciadas" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('no_iniciadas')"></button>
                            <button id="btn_en_ejecucion" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('en_ejecucion')"></button>
                            <button id="btn_terminadas" type="button" class="btn btn-secondary btn-sm" data-toggle="button"  aria-pressed="true" onclick="cambiar_clase('terminadas')"></button>
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
				<table id="dt_cliente" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
                            <th></th>
                            <th>Consecutivo</th>
							<th>Id</th>
							<th>Actividad</th>
							<th>Título</th>
                            <th>Días Para Iniciar</th>
                            <th>Lookahead</th>
                            <th>Periodicidad (días)</th>
                            <th>Relevancia</th>
                            <th>Ejecución</th>
                            <th>Estado</th>
                            <th>Observaciones</th>                            
							<th></th>											
						</tr>
					</thead>					
				</table>
			</div>			
		</div>		
	</div>
	
    <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
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
    
    <!--Selector de fechas -->
    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    
    
    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>
        
        /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
		$(document).on("ready", function(){
			listar();
            //cambiar_posicion(0);
            
            eliminar();
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_listar").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            location.assign("../scroll_general.php?scroll="+posicion+"&seccion=tareas_periodicas_simples")
        });
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            //location.reload(true);
            listar();
            
            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$("#btn_guardar").on("click", function(e){
				e.preventDefault();
				var frm = $(".form_modificar").serialize();
                
                var Ejecutado=$("#input_Ejecutado_Editar").serialize();
                var Periodicidad=$("#input_Periodicidad_Editar").serialize();
                var Relevancia=$("#input_Relevancia_Editar").serialize();
                var Observaciones=$("#input_Observaciones_Editar").serialize();
                
                frm=frm+"&"+Ejecutado+"&"+Periodicidad+"&"+Relevancia+"&"+Observaciones;
                console.log(frm);
				$.ajax({
					method: "POST",
					url: "../tareas_periodicas_simples/guardar_tareas_periodicas_simples.php<?php echo "?db=$db&semana=$semana"?>",
                    contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					//var json_info = JSON.parse( info );
                    //console.log(json_info);
                    //mostrar_mensaje( json_info );
					limpiar_datos();
                    //location.reload(true);
                    var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("../scroll_general.php?scroll="+posicion+"&seccion=tareas_periodicas_simples")
				});
			});
		}
        
        
        /* Ejecuta la funcion eliminar, solo cuando se presiona el botón eliminar en cada uno de los registros. La función eliminar busca el id de el registro en el que se presinó el botón eliminar y lo envia por medio de AJAX para que se ejecute la funcion eliminar en guardar.php */
        var eliminar = function(){
			$("#eliminar-usuario").on("click", function(){
				var Id = $("#frmEliminarUsuario #Id").val(),
					opcion = $("#frmEliminarUsuario #opcion").val();
				$.ajax({
					method:"POST",
					url: "../tareas_periodicas_simples/guardar_tareas_periodicas_simples.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"Id": Id, "opcion": opcion}
				}).done( function( info ){
					//var json_info = JSON.parse( info );
					//mostrar_mensaje( json_info );
					limpiar_datos();
					var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("../scroll_general.php?scroll="+posicion+"&seccion=tareas_periodicas_simples")
				});
			});
		}
        
        /*Sirve para mostrar el mensaje emergente dependiendo de las condiciones que se presenten */
		var mostrar_mensaje = function( informacion ){
			var texto = "", color = "";
			if( informacion.respuesta == "BIEN" ){
					texto = "<strong>Bien!</strong> Se han guardado los cambios correctamente.";
					color = "#379911";
			}else if( informacion.respuesta == "ERROR"){
					texto = "<strong>Error</strong>, no se ejecutó la consulta.";
					color = "#C9302C";
			}else if( informacion.respuesta == "EXISTE" ){
					texto = "<strong>Información!</strong> el usuario ya existe.";
					color = "#5b94c5";
			}else if( informacion.respuesta == "VACIO" ){
					texto = "<strong>Advertencia!</strong> debe llenar todos los campos solicitados.";
					color = "#ddb11d";
			}else if( informacion.respuesta == "OPCION_VACIA" ){
					texto = "<strong>Advertencia!</strong> la opción no existe o esta vacia, recargar la página.";
					color = "#ddb11d";
			}

			$(".mensaje").html( texto ).css({"color": color });
			$(".mensaje").fadeOut(5000, function(){
					$(this).html("");
					$(this).fadeIn(3000);
			});			
		}
        
        
        /*limpia los valores del formulario de registro*/
		var limpiar_datos = function(){
			$("#opcion").val("registrar");
            $("#Id").val("");
            $("#Ejecutado").val("").focus();
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");
		}
        
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/

            
        var listar=function(clase){
            var posicion= <?php 
            if(isset($_SESSION["scroll"])){
                echo $_SESSION["scroll"];
            }else{
                echo 0;
            }
            ?>;
            
            <?php
                unset($_SESSION["lookahead_tram"],
                $_SESSION["no_iniciadas_tram"],
                $_SESSION["en_ejecucion_tram"],
                $_SESSION["terminadas_tram"],
                $_SESSION["total_tram"],
                $_SESSION["lookahead_cons"],
                $_SESSION["no_iniciadas_cons"],
                $_SESSION["en_ejecucion_cons"],
                $_SESSION["terminadas_cons"],
                $_SESSION["total_cons"],
                $_SESSION["lookahead_tpc"],
                $_SESSION["no_iniciadas_tpc"],
                $_SESSION["en_ejecucion_tpc"],
                $_SESSION["terminadas_tpc"],
                $_SESSION["total_tpc"],
                $_SESSION["lookahead_tpr"],
                $_SESSION["no_iniciadas_tpr"],
                $_SESSION["en_ejecucion_trc"],
                $_SESSION["terminadas_tpr"],
                $_SESSION["total_tpr"]);
            ?>
            var activa_lookahead= <?php 
            if(isset($_SESSION['lookahead_tps'])){
                if($_SESSION['lookahead_tps']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_no_iniciadas= <?php 
            if(isset($_SESSION['no_iniciadas_tps'])){
                if($_SESSION['no_iniciadas_tps']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_en_ejecucion= <?php 
            if(isset($_SESSION['en_ejecucion_tps'])){
                if($_SESSION['en_ejecucion_tps']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            var activa_terminadas= <?php 
            if(isset($_SESSION['terminadas_tps'])){
                if($_SESSION['terminadas_tps']==1){
                    echo 1;
                }else{
                    echo 0;
                }
            }else{
                echo 0;
            }
            ?>;
            
            <?php 
                require("../conexion.php");
                $query="SELECT (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_simples' AND Dias_Inicio>7 AND Dias_Inicio<=Lookahead AND Ejecutado=0) AS 'lookahead', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_simples' AND Dias_Inicio<=7 AND Ejecutado=0) AS 'no_iniciadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_simples' AND Ejecutado>0 AND Ejecutado<1) AS 'en_ejecucion', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_simples' AND Ejecutado=1) AS 'terminadas', (SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Categoria='periodicas_simples' AND (Dias_Inicio<=Lookahead OR Ejecutado>0)) AS 'total'";
                $resultado= mysqli_query($conexion, $query);
                $data=mysqli_fetch_assoc($resultado);
                $lookahead=$data['lookahead'];
                $no_iniciadas=$data['no_iniciadas'];
                $en_ejecucion=$data['en_ejecucion'];
                $terminadas=$data['terminadas'];
                $total=$data['total'];
            ?>
            
            var lookahead=<?php echo $lookahead ?>;
            var no_iniciadas=<?php echo $no_iniciadas ?>;
            var en_ejecucion=<?php echo $en_ejecucion ?>;
            var terminadas=<?php echo $terminadas ?>;
            var total=<?php echo $total ?>;
            
            if(total!=0){
                var p_lookahead=Math.round(lookahead/total*100) +'%';
                var p_no_iniciadas=Math.round(no_iniciadas/total*100) +'%';
                var p_en_ejecucion=Math.round(en_ejecucion/total*100) +'%';
                var p_terminadas=Math.round(terminadas/total*100) +'%';
                
            }else{
                var p_lookahead='0%';
                var p_no_iniciadas='0%';
                var p_en_ejecucion='0%';
                var p_terminadas='0%';
            }
            
            $("#btn_lookahead").html("<h6 style=margin:0; min-width:100px>En Lookahead</h6><h6 style=margin:0>"+p_lookahead+"</h6>");
            $("#btn_no_iniciadas").html("<h6 style=margin:0; min-width:100px>No Iniciadas</h6><h6 style=margin:0>"+p_no_iniciadas+"</h6>");
            $("#btn_en_ejecucion").html("<h6 style=margin:0; min-width:100px>En Ejecución</h6><h6 style=margin:0>"+p_en_ejecucion+"</h6>");
            $("#btn_terminadas").html("<h6 style=margin:0; min-width:100px>Terminadas</h6><h6 style=margin:0>"+p_terminadas+"</h6>");
            
            if(activa_lookahead==1){
                $("#btn_lookahead").addClass('btn-success');
            }
            if(activa_no_iniciadas==1){
                $("#btn_no_iniciadas").addClass('btn-success');
            }
            if(activa_en_ejecucion==1){
                $("#btn_en_ejecucion").addClass('btn-success');
            }
            if(activa_terminadas==1){
                $("#btn_terminadas").addClass('btn-success');
            }
            if(activa_lookahead==0 && activa_no_iniciadas==0 && activa_en_ejecucion==0 && activa_terminadas==0){
                $("#btn_total").addClass('btn-success');
            }
            
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/ 
           
            var alturahoja= $(window).height();
            var alturatabla= ((1.28*alturahoja)-513.21);
            
            $("#cuadro2").slideUp("slow");
            $("#cuadro3").slideDown("slow");
//            $("#cuadro1").slideDown("slow");
            var table = $("#dt_cliente").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
                "autoWidth": true,
//                "fixedHeader": true,
                "scrollX": true,
                
//                console.log($(document).height());
                "scrollY": alturatabla,
                "scrollCollapse": false,
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":"../tareas_periodicas_simples/listar_tareas_periodicas_simples.php<?php echo "?db=$db&semana=$semana"?>&activa_lookahead="+activa_lookahead+"&activa_no_iniciadas="+activa_no_iniciadas+"&activa_en_ejecucion="+activa_en_ejecucion+"&activa_terminadas="+activa_terminadas
              },
                
                
                'columnDefs': [
                     {
                        'targets': 0,
                        'width': "5%",
                        'checkboxes': {
                           'selectRow': false,
                        'visible':false,
                        }
                     },
                    
                     {
                        'targets': [3],
                        'width': "25%",
                     },

                    {
                        'targets': [11],
                        'width': "12%",
                     },
                    
                     {
                        'targets': [1,2,4,5,6,7,8,9,10,12],
                        'width': "6%",
                     },
                    
                    {
                        'targets': [9],
                        'render': function ( data, type, full, meta ) {
                            if(data=="" || data==null){
                                data="";
                                return "<h6>" + data + "</h6>";
                            }else if(data==0){
                                data= "No Iniciado"
                                return "<h6>" + data + "</h6>";
                            }else if(data==0.5){
                                data= "En Ejecución"
                                return "<h6>" + data + "</h6>";
                            }else if(data==0.75){
                                data= "Periodicidad Activa"
                                return "<h6>" + data + "</h6>";
                            }else if(data==1){
                                data= "Finalizado"
                                return "<h6>" + data + "</h6>";
                            }
                            
                        },
                    },
                    
                    {
                        'targets': [7,8],
                        'render': function ( data, type, full, meta ) {
                            var permiso="<?php echo $permiso?>";
                            if(data=="NA"){
                                resultado="No Asignado";
                            }else{
                                resultado=data;
                            }
                            return "<h6>" + resultado + "</h6>";
                            
                        },
                    },
                    
                    {
                        'targets': [3,4,5,6,10],
                        'render': function ( data, type, full, meta ) {
                                return "<h6>" + data + "</h6>";
                            }
                    },
                    
                    {
                        'targets': [12],
                        'render': function ( data, type, full, meta ) {
                            var permiso="<?php echo $permiso?>";
                            if(data=="Boton"){
                                boton=""/*<button type= 'button' class='editar btn btn-primary btn-sm' title='Editar el porcentaje de ejecución de la actividad seleccionada'><i class='fa fa-edit' aria-hidden='true' ></i></button>*/;
                            }else{
                                boton="";
                            }
                            return boton;
                            
                        },
                    },
                    
                    
                    {
                        'targets': [12],
                        'className': 'Botones'
                    },
                    {
                        'targets': [7],
                        'className': 'Periodicidad'
                    },
                    {
                        'targets': [8],
                        'className': 'Relevancia'
                    },
                    {
                        'targets': [9],
                        'className': 'Ejecutado'
                    },
                    {
                        'targets': [11],
                        'className': 'Observaciones'
                    },
                  ],
                
                
                
                'select': {
                 'style': 'false',
                },  
                
                "lengthMenu": [10],
                
                "columns":[
                    {"defaultContent":"", "visible":false},
                    {"data":"Consecutivo_en_Programa", "visible":false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Titulo", "visible":false},
                    {"data":"Dias_Inicio"},
                    {"data":"Lookahead"},
                    {"data":"Periodicidad"},
                    {"data":"Relevancia"},
                    {"data":"Ejecutado"},
                    {"data":"Estado"},
                    {"data":"Observaciones"},
                    {"data":"boton"},
                ],


                

                "createdRow": function( row, data, dataIndex){
                    
                    var inicio_sem = new Date(<?php echo $Fecha_Inicio_Sem ?>);
                    
                    var fin_sem = new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+7);

                    var inicio_act = new Date(data.Fecha_Inicio );
                    var inicio_act = new Date(inicio_act.getFullYear(),inicio_act.getMonth(),inicio_act.getDate()+1);
                    
                    var fin_act = new Date(data.Fecha_Fin );
                    var fin_act = new Date(fin_act.getFullYear(),fin_act.getMonth(),fin_act.getDate()+1);

                    var fecha_intermedia= new Date(inicio_sem.getFullYear(),inicio_sem.getMonth(),inicio_sem.getDate()+49);

/*                    console.log(data.Id,inicio_sem, fin_sem, inicio_act, fin_act, fecha_intermedia);*/
                    
                    if(data.Titulo!=0){
                        $(row).addClass('Titulo');
                    }
                    else if((data.Estado==='Pendiente de Iniciar' || data.Ejecutado>0) && data.Ruta_Critica==="1" && data.Estado!="Terminada Antes" && data.Estado!='Atrasada' && data.Ejecutado<1){
                        $(row).addClass('Semanal_Critica');

                    } 

                    else if((data.Estado==='Pendiente de Iniciar' || data.Estado==='No Puede Comenzar' || data.Ejecutado>0) && data.Ruta_Critica==="0" && data.Estado!="Terminada Antes" && data.Estado!='Atrasada' && data.Ejecutado<1){
                        $(row).addClass('Semanal_No_Critica');
                    } 

                    else if(data.Estado==="Atrasada"){
                        $(row).addClass('Semanal_Atrasada');
                    }

                    else if((data.Dias_Inicio - data.Lookahead)<=0 && data.Estado!="Terminada Antes" && data.Ejecutado<1){
                        $(row).addClass('Intermedia');
                    }
                    
                    else{
                        $(row).addClass('No_Activa');
                    } 

            },

                "language": idioma_espanol
            });
            
            
            /*var Editar = function(table){
                var only_once = true;
                var fila_anterior = null;
                $('#dt_cliente tbody').on( 'dblclick', 'td', function () {
                    //console.log(Ejecutado_anterior)
                    var Ejecutado= table.row($(this)).data()['Ejecutado'];
                    var celda = table.cell( this );
                    var columna = table.column($(this))[0][0];
                    var fila = table.row($(this))[0][0];

                    if(columna==9 && only_once==true){

                        console.log($( this ).parent().find('.Consecutivo').text());
                        var codigo_html = "<input class='input_Ejecutado_Editar"+fila+"_"+columna+"' type='text' value='"+Ejecutado+"'></input>";
                        var codigo_html1 = "<input class='input_Ejecutado_Editar"+fila+"_"+(columna+1)+"' type='text' value='"+Ejecutado+"'></input>";
                        $( this ).parent().find('.Ejecutado').html(codigo_html);
                        //console.log(this);
                        //console.log($( celda_1 ).html(codigo_html));
                        only_once=false;
                        fila_anterior = fila;
                        window.Ejecutado_anterior = Ejecutado;
                    }    
                });
            }*/
            
            //Editar(table);
            //barra_slider();
            <?php $_SESSION['clase_filtro']='total';?>
            ocultos(table);
            cambiar_posicion(posicion);
            <?php $_SESSION["scroll"]=0 ?>;
            obtener_data_editar("#dt_cliente tbody", table);
            obtener_id_eliminar("#dt_cliente tbody", table);
            obtener_id_editar("#dt_cliente tbody", table);
            
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
       /* var obtener_data_editar=function(tbody, table){
            $(tbody).on("click", "button.editar", function(){
                var data= table.row($(this).parents("tr")).data();
                var Id=$("#Id").val(data.Consecutivo_en_Programa),
                    Ejecutado=$("#Ejecutado").val(data.Ejecutado),
				    opcion = $("#opcion").val("modificar");
                    barra_slider();
                    $("#cuadro2").slideDown("slow");
                    $("#cuadro3").slideUp("slow");
//                    $("#cuadro1").slideUp("slow");
                    
            });
        }*/
        
        var obtener_data_editar=function(tbody, table){
            var only_once = true;
            $(tbody).on("click", "td", function(){
                
                    if(only_once==true){
                        var data= table.row($(this).parents("tr")).data();
                        var Id=$("#Id").val(data.Consecutivo_en_Programa),
                            Ejecutado=$("#Ejecutado").val(data.Ejecutado),
                            opcion = $("#opcion").val("modificar");
                        /*var codigo_html_Ejecutado = "<input class='input_Ejecutado_Editar' id='input_Ejecutado_Editar' name='Ejecutado' type='text' value='"+data.Ejecutado+"'></input>";*/
                        var codigo_html_Ejecutado = "<select class='input_Ejecutado_Editar' id='input_Ejecutado_Editar' name='Ejecutado'><option value=0>No iniciado</option><option value=0.75>Periodicidad Activa</option><option value=1>Finalizado</option></select>";
                        $( this ).parent().find('.Ejecutado').html(codigo_html_Ejecutado); 
                        
                        var codigo_html_Periodicidad = "<input class='input_Periodicidad_Editar' id='input_Periodicidad_Editar' name='Periodicidad' type='text' value='"+data.Periodicidad+"'></input>";
                        $( this ).parent().find('.Periodicidad').html(codigo_html_Periodicidad);
                        
                        var codigo_html_Relevancia = "<select class='input_Relevancia_Editar' id='input_Relevancia_Editar' name='Relevancia'><option value='NA'>No Asignado</option><option value=1>1</option><option value=2>2</option><option value=3>3</option><option value=4>4</option><option value=5>5</option><option value=6>6</option><option value=7>7</option><option value=8>8</option><option value=9>9</option><option value=10>10</option></select>";
                        $( this ).parent().find('.Relevancia').html(codigo_html_Relevancia); 
                        
                        var codigo_html_Observaciones = "<textarea class='input_Observaciones_Editar' id='input_Observaciones_Editar' name='Observaciones'></textarea>";
                        $( this ).parent().find('.Observaciones').html(codigo_html_Observaciones); 
                       
                        var codigo_html_botones = "<button type= 'button' id='btn_guardar' class='guardar btn btn-success btn-sm' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar2' class='cancelar btn btn-danger btn-sm' title='Cancelar la edición'><i class='fa fa-undo' aria-hidden='true' ></i></button>";
                        $( this ).parent().find('.Botones').html(codigo_html_botones);
                        
                        
                        $("#input_Periodicidad_Editar").val(data.Periodicidad).change();
                        $("#input_Periodicidad_Editar").focus();
                        $("#input_Periodicidad_Editar").select();
                        $("#input_Relevancia_Editar").val(data.Relevancia).change();
                        $("#input_Ejecutado_Editar").val(data.Ejecutado).change();
                        $("#input_Observaciones_Editar").val(data.Observaciones).change();
                        $("#input_Ejecutado_Editar, #input_Periodicidad_Editar, #input_Relevancia_Editar, #input_Observaciones_Editar").keypress(function(e){
                            if(e.keyCode==13){
                                $("#btn_guardar").click();
                            }
                        });
                        $("#input_Ejecutado_Editar, #input_Periodicidad_Editar, #input_Relevancia_Editar, #input_Observaciones_Editar").keyup(function(e){
                            if(e.keyCode==27){
                            $("#btn_cancelar2").click();
                            }
                        });
                        only_once = false;
                        
                        $("#btn_cancelar2").on("click", function(){
                            var posicion=$('.dataTables_scrollBody').scrollTop();
                            listar();
                            cambiar_posicion(posicion);
                            only_once = true;
                            $(document).on("ready", function(){
                                $("#navbarNav").addClass("show");
                            });
                        });
                        
                        
                    }
                    
                    //barra_slider();
                    //$("#cuadro2").slideDown("slow");
                    //$("#cuadro3").slideUp("slow");
//                    $("#cuadro1").slideUp("slow");
            guardar();   
            });
        }

        /*Toma los datos de la fila en la que se presionó el botón eliminar*/
        var obtener_id_eliminar=function(tbody, table){
            $(tbody).on("click", "button.eliminar", function(){
                var data= table.row($(this).parents("tr")).data();
                var idusuario=$("#frmEliminarUsuario #Id").val(data.Consecutivo);
            });
        }
        
        
        
        /*crea los Id que se deben actualizar en la base de datos*/
        var obtener_id_editar=function(tbody, table){
            
            var script="";
            var script1="";
            $("#btn_editar").on("click", function(){
                var Max_Ejecutado=0;
                $("td input").each(function(){
                    
                    if($(this).is(':checked')){
                        var valor_actual;
                        var data= table.row($(this).parents("tr")).data();
                        var valor_actual=data.Consecutivo_en_Programa;
                        var Ejecutado=data.Ejecutado;
                        if(Ejecutado>Max_Ejecutado){
                            Max_Ejecutado=Ejecutado;
                        }
                        /*var Ejecutado=data.Ejecutado;
                        console.log(Ejecutado)*/;
                        if (script==="" && valor_actual!=""){
                            script ="Consecutivo='"+valor_actual+"'";
                            script1 ="Consecutivo_en_Programa='"+valor_actual+"'";
                        } else if (script!="" && valor_actual===""){
                            script=script;
                            script1=script1;
                        } else if (script!="" && valor_actual!=""){
                            script=script+" OR Consecutivo='"+valor_actual+"'";
                            script1=script1+" OR Consecutivo_en_Programa='"+valor_actual+"'";
                        }   
                        var Id=$("#Id").val(script),
                            Id1=$("#Id1").val(script1),
                            Ejecutado=$("#Ejecutado").val(""),
                            opcion = $("#opcion").val("modificargrupo");
                        console.log(Max_Ejecutado);
                        document.getElementById("valor_Ejecutado").innerHTML = Math.round(Max_Ejecutado*100,0)+"%";
                        $("#Ejecutado").val(Max_Ejecutado).change();
                    }

                });
                $("#cuadro2").slideDown("slow");
                $("#cuadro3").slideUp("slow");
//                $("#cuadro1").slideUp("slow"); 
                console.log(script1);
            });    
        }
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../tareas_periodicas_simples/guardar_tareas_periodicas_simples.php?db=<?php echo $db?>",
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
        
        var eliminar_sem=function(semana){
            $("#modal-eliminar-semana-body-texto").text("¿Desea Eliminar la Semana "+semana+"?");
            $("#btn_eliminar_sem").on("click", function(){            
                opcion="eliminar_sem";
				$.ajax({
					method:"POST",
					url: "../tareas_periodicas_simples/guardar_tareas_periodicas_simples.php?db=<?php echo $db?>",
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

        
        var cambiar_posicion=function(p){
            $('#dt_cliente').on( 'draw.dt', function () {
                $('.dataTables_scrollBody').scrollTop(p);
            } );
        }
        
        var cambiar_clase=function(p){
            console.log(p);
            if(p=='lookahead'){
                if($('#btn_lookahead').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }else if(p=='no_iniciadas'){
                if($('#btn_no_iniciadas').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }else if(p=='en_ejecucion'){
                if($('#btn_en_ejecucion').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_terminadas').hasClass('btn-success')==true){
                        p = 'total';
                    }                    
                }
            }else if(p=='terminadas'){
                if($('#btn_terminadas').hasClass('btn-success')==true){
                    var activa = 0;
                }else{
                    var activa = 1;
                    if($('#btn_lookahead').hasClass('btn-success')==true && $('#btn_no_iniciadas').hasClass('btn-success')==true && $('#btn_en_ejecucion').hasClass('btn-success')==true){
                        p = 'total';
                    }
                }
            }
            location.assign("clase_filtro.php?clase="+p+"&activa="+activa);
        }
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
                table.column( 0 ).visible( false );
                table.column( 12 ).visible( false );
            }else if(permiso=="G"){
                $('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
                table.column( 0 ).visible( false );
                table.column( 12 ).visible( false );
            }else if(permiso=="S"){
                $('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
                table.column( 0 ).visible( false );
                table.column( 12 ).visible( false );
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
                table.column( 0 ).visible( false );
                table.column( 12 ).visible( false );
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia').css('display', 'none');
                window.location.href='../programacion_semanal/programacion_semanal.php';
            }
        }
        
        var barra_slider= function(){
            document.getElementById("valor_Ejecutado").innerHTML = Math.round(document.getElementById("Ejecutado").value*100,0)+"%"; // Display the default slider value

            // Update the current slider value (each time you drag the slider handle)
            document.getElementById("Ejecutado").oninput = function() {
              document.getElementById("valor_Ejecutado").innerHTML = Math.round(this.value*100,0)+"%";
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