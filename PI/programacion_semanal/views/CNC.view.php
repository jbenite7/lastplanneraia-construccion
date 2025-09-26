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
                    <li class="pagina"> - Evaluación Semanal / Causas de No Cumplimiento / Semana <?php echo $semana ?></li>
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

                    <div class="form-group" style="width:600px; max-width:45%; float:left">
                        <label for="Categoria_CNC" class="col-sm-8 control-label">Categoría</label>
                        <div class="col-sm-8"><select id="Categoria_CNC" name="Categoria_CNC" class="form-control" onchange="cnc('')">
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
                    </div>
                
                    <div class="form-group" style="width:600px; ; max-width:45%; float:left">
                        <label for="CNC" class="col-sm-8 control-label">Causa de No Cumplimiento</label>
                        
                        <div class="col-sm-8"><select id="CNC" name="CNC" class="form-control">
                        <option value=''></option>
                        </select>
                        </div>	
                        
                    </div>
                    <div class="form-group" style="width:100%;">
                        <label for="Observaciones_CNC" class="col-sm-8 control-label">Observaciones</label>
                        <div class="col-sm-8"><textarea id="Observaciones_CNC" name="Observaciones_CNC" class="form-control" ></textarea></div>
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
		<div id="cuadro3" class="cuadro3  col-lg-12">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 ">
                        <button type= 'button' class='leyenda_colores btn btn-secondary btn-sm' data-toggle='modal' data-target='#modal_leyenda_colores'>Leyenda <i class='fas fa-question-circle fa-lg'></i>
						</button>
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:10">
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal&semana=$semana"?>'">Actividades <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNP&semana=$semana"?>'">Causas No Programación <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNC" type="button" class="btn btn-success btn-sm active" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNC&semana=$semana"?>'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CIC&semana=$semana"?>'">Calificación de Terceros <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=indicadores&semana=$semana"?>'">Indicadores</button>
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
							<th>Categoría CNC</th>
                            <th>Causa de No Cumplimiento</th>
                            <th>Observaciones</th>										
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
            guardar();
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
		var guardar = function(){
			$("#btn_guardar1").on("click", function(e){
				e.preventDefault();
                var Id = $("form #Id0").serialize();
                var semana = $("form #semana0").serialize();
                var opcion = $("form #opcion0").serialize();
                var Categoria_CNC=$("#select_Categoria_CNC").serialize();
                var CNC=$("#select_CNC").serialize();
                var Observaciones_CNC=$("#select_Observaciones_CNC").serialize();
				//frm=Id+"&"+semana+"&"+opcion+"&"+Categoria_CNC+"&"+CNC+"&"+Observaciones_CNC;
                frm=Id+"&"+semana+"&"+opcion+"&"+CNC+"&"+Observaciones_CNC;
                console.log(frm);
				$.ajax({
					method: "POST",
					url: "../programacion_semanal/guardar_CNC.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					/*var json_info = JSON.parse( info );
                    mostrar_mensaje( json_info );
					limpiar_datos();
					//listar();*/
                    var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("../scroll_general.php?scroll="+posicion+"&seccion=programa_general")
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
                      "url":"../programacion_semanal/listar_CNC.php<?php echo "?db=$db&semana=$semana"?>"
                  },


                    'columnDefs': [


                         {
                            'targets': [0],
                            'width': "0.4%",
                         },
                        
                         {
                            'targets': [2],
                            'width': "5%",
                         },
                        
                         {
                            'targets': [3],
                            'width': "15%",
                         },
                        
                         {
                            'targets': [1,2,4,5,6,7,8],
                            'width': "10%",
                         },
                        
                        {
                        'targets': [2,3,4,5,6,7,8],
                        'render': function ( data, type, full, meta ) {
                                return "<h6>" + data + "</h6>";
                            }
                        },
                        
                        {
                            'targets': [0],
                            'className': 'Botones'
                        },                    
                        {
                            'targets': [6],
                            'className': 'input_Categoria_CNC'
                        },
                        {
                            'targets': [7],
                            'className': 'input_CNC'
                        },
                        {
                            'targets': [8],
                            'className': 'input_Observaciones_CNC'
                        }
                      ],



                    'select': {
                     'style': 'false',
                    },  

                    "lengthMenu": [100],

                    "columns":[
                        {"defaultContent":"<button type= 'button' class='editar btn btn-primary btn-sm'><i class='fa fa-edit'></i></button>"},
                        {"data":"Consecutivo", "visible":false},
                        {"data":"Id"},
                        {"data":"Actividad"},
                        {"data":"Descripcion", "visible":false},
                        {"data":"Clase"},
                        {"data":"Categoria_CNC", "visible":false},
                        {"data":"CNC"},
                        {"data":"Observaciones_CNC"},
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
                cambiar_posicion(posicion);
                <?php $_SESSION["scroll"]=0 ?>;
                obtener_data_editar("#dt_general tbody", table);
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
        /*var obtener_data_editar=function(tbody, table){
            $(tbody).on("click", "button.editar", function(){
                var data= table.row($(this).parents("tr")).data();
                var Id=$("#Id").val(data.Consecutivo),
				    opcion = $("#opcion").val("modificar");
                
                $("#Id1").val(data.Id).change();
                
                $("#semana").val(<?php echo $semana?>).change();
                
                $("#Actividad").val(data.Actividad).change();
                
                $("#CNC").val(data.CNC).change();
                
                $("#Categoria_CNC").val(data.Categoria_CNC);
                
                cnc(data.CNC);
                
//                $('#CNC').html("<option value='"+data.CNP+"'>"+data.CNP+"</option>");
                
                $("#Observaciones_CNC").val(data.Observaciones_CNC).change();
                  
                $("#actualizacion").text("Causa de No Cumplimiento Actividad: "+data.Actividad);                   
                $('#Actividad').attr('readonly', true);
                
                $('#Id1').attr('readonly', true);
                
                
                
                    
                    $("#cuadro2").slideDown("slow");
                    $("#cuadro3").slideUp("slow");
//                    $("#cuadro1").slideUp("slow");      
            });
        }*/
        
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
                
                if(only_once==true)try {
                    {
                        var data= table.row($(this).parents("tr")).data();
                        var Id=$("#Id0").val(data.Consecutivo),
                            semana = $("#semana0").val(data.Semana),
                            opcion = $("#opcion0").val("modificar");
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
                            ?>;
                        var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control' ><option value=''></option>"+Responsable_AIA+"</select>";
                        $( this ).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA);

                        var codigo_html_Categoria_CNC = "<select id='select_Categoria_CNC' name='Categoria_CNC' class='form-control'><option value='' selected></option><option value='Rendimiento'>Rendimiento</option><option value='Programación'>Programación</option><option value='Mano de Obra'>Mano de Obra</option><option value='Materiales'>Materiales</option><option value='Equipos'>Equipos</option><option value='Diseños'>Diseños</option><option value='Administrativas'>Administrativas</option></select>";
                        $( this ).parent().find('.input_Categoria_CNC').html(codigo_html_Categoria_CNC);
                        
                        /*var codigo_html_CNC = "<select id='select_CNC' name='CNC' class='form-control'><option value='' selected></option></select>";*/
                        var codigo_html_CNC = "<input id='select_CNC' name='CNC' class='form-control' type='text' value='"+data.CNC+"'></input>";
                        $( this ).parent().find('.input_CNC').html(codigo_html_CNC);

                        var codigo_html_Observaciones_CNC = "<textarea id='select_Observaciones_CNC' name='Observaciones_CNC' class='form-control'>'"+data.Observaciones_CNC+"'</textarea>";
                        $( this ).parent().find('.input_Observaciones_CNC').html(codigo_html_Observaciones_CNC);


                        var codigo_html_botones = "<button type= 'button' id='btn_guardar1' class='guardar btn btn-success btn-xs' style='padding:5px; margin:1px' title='Guardar Causa de No Programación'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar2' class='cancelar btn btn-danger btn-xs' style='padding:5px; margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
                        $( this ).parent().find('.Botones').html(codigo_html_botones);
                        //$("#select_CNC").val(data.CNC).change();
                        $("#select_Categoria_CNC").val(data.Categoria_CNC).change();
                        $("#select_Categoria_CNC").focus();
                        cnc(data.CNC);
                        console.log("<option value='"+data.CNC+"'>"+data.CNC+"</option>");
                        $('#select_CNC').html("<option value='"+data.CNC+"'>"+data.CNC+"</option>");
                        
                        $("#select_Observaciones_CNC").val(data.Observaciones_CNC).change();

                        //$(".input_Ejecutado_Editar").select();
                        $(".input_Categoria_CNC, .input_CNC, .input_Observaciones_CNC").keypress(function(e){
                            if(e.keyCode==13){
                                $("#btn_guardar1").click();
                            }
                        });
                        $(".input_Categoria_CNC, .input_CNC, .input_Observaciones_CNC").keyup(function(e){
                            if(e.keyCode==27){
                                $("#btn_cancelar2").click();
                            }
                        });
                        only_once = false;

                        $("#btn_cancelar2").on("click", function(){
                            var posicion=$('.dataTables_scrollBody').scrollTop();
                            listar();
                            //cambiar_posicion(posicion);
                            only_once = true;
                            $(document).on("ready", function(){
                                $("#navbarNav").addClass("show");
                            });
                        });


                    }
                } catch (e) {
                    //Catch Statement
                }
                
                    //$("#cuadro2").slideDown("slow");
                    //$("#cuadro3").slideUp("slow");
                    //$("#cuadro1").slideUp("slow");
            guardar(); 
            //cnc1();
            });
        }
        
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../programacion_semanal/guardar_CNC.php?db=<?php echo $db?>",
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
					url: "../programacion_semanal/guardar_CNC.php?db=<?php echo $db?>",
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
            var categoria=$("#select_Categoria_CNC").val(),
                opcion="CNC",
                CNC=causa;
                //console.log(CNC);
                if(categoria===""){
                    $('#select_CNC').attr('readonly', true); 
                    $('#select_CNC').html("<option value=''></option>"); ; 
                } else{
                    $('#Cselect_NC').attr('readonly', false); 
                    $.ajax({
                        method:"POST",
                        url: "../programacion_semanal/guardar_CNC.php?db=login",
                        contenttype:"charset=utf-8",
                        data: {"categoria": categoria, "opcion":opcion},
                        success:function(a){
                            //console.log(a);
                            $('#select_CNC').html(a);
                            $("#select_CNC option[value='"+CNC+"']").attr('selected', true);
                        }
                    }); 
                }  
            }
        
        var cnc1=function(){
            $('#select_Categoria_CNC').on('change', function() {
                var categoria=$("#select_Categoria_CNC").val(),
                    opcion="CNC";
                    //console.log(CNC);
                    if(categoria===""){
                        $('#select_CNC').attr('readonly', true); 
                        $('#select_CNC').html("<option value=''></option>"); 
                    } else{
                        $('#select_CNC').attr('readonly', false); 
                        $.ajax({
                            method:"POST",
                            url: "../programacion_semanal/guardar_CNC.php?db=login",
                            contenttype:"charset=utf-8",
                            data: {"categoria": categoria, "opcion":opcion},
                            success:function(a){
                                console.log(a);
                                $('#select_CNC').html(a);
                                //$("#select_CNC option[value='"+CNC+"']").attr('selected', true);
                            }
                        }); 
                    } 
                });
            }
        
        var cambiar_posicion=function(p){
            $('#dt_general').on( 'draw.dt', function () {
            $('.dataTables_scrollBody').scrollTop(p);
            } );
        } 
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                //$('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
            }else if(permiso=="G"){
                //$('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
            }else if(permiso=="S"){
                //$('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
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