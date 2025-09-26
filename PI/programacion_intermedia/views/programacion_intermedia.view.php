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
                    <li class="pagina"> - Programación Intermedia / Semana <?php echo $semana ?></li>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_simples&semana=$i'>Tareas Periódicas Simples</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_periodicas_compuestas&semana=$i'>Tareas Periódicas Compuestas</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=tareas_propias&semana=$i'>Tareas Propias</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?
                                                seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
			<form class="form_modificar form-horizontal" action="" method="POST">
				<div class="form-group">
					<h3 class="col-sm-offset-2 col-sm-8 text-center" id="actualizacion"></h3>
				</div>
                
                <!--Se crean 2 inputs que contienen el id del registro que se va a modificar, y el switch que dice si la acción es modificar-->
				<input type="hidden" id="Id" name="Id" value="0">
				<input type="hidden" id="opcion" name="opcion" value="registrar">
                
                <!-- Se crean los inputs del formulario de registro de usuario (Nombre, Apellidos y DNI) -->
<!--                <div style="width:40%; float:left;">-->
                    <!--<div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="D_y_E" class="col-sm-8 control-label">Diseños y Especificaciones</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="D_y_E" name="D_y_E" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.33>33%</option>
                        <option value=0.66>66%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>	
                        <div class="pregunta_D_y_E" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_D_y_E'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>     
                    <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="Materiales" class="col-sm-8 control-label">Materiales</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="Materiales" name="Materiales" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.33>33%</option>
                        <option value=0.66>66%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>
                        <div class="pregunta_Materiales" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_Materiales'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>
                    <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="MdeO" class="col-sm-8 control-label">Mano de Obra</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="MdeO" name="MdeO" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.33>33%</option>
                        <option value=0.66>66%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>
                        <div class="pregunta_MdeO" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_MdeO'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>
                    <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="Equipos" class="col-sm-8 control-label">Equipos</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="Equipos" name="Equipos" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.33>33%</option>
                        <option value=0.66>66%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>
                        <div class="pregunta_Equipos" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_Equipos'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>
                    <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="Predecesora" class="col-sm-8 control-label">Predecesoras</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="Predecesora" name="Predecesora" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.5>50%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>
                        <div class="pregunta_Predecesora" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_Predecesora'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>-->
<!--                </div>-->
                
                
                
<!--                <div style="width:40%; float:left;">-->
                   <!-- <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="Pdto_Cons" class="col-sm-8 control-label">Procedimiento Constructivo</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="Pdto_Cons" name="Pdto_Cons" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.5>50%</option>
                        <option value=1>100%</option>
                        </select>
                        </div>
                        <div class="pregunta_Pdto_Cons" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_Pdto_Cons'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>
                    <div class="form-group" style="width:400px; max-width:45%; float:left">
                        <label for="Modelo" class="col-sm-8 control-label">Modelación BIM</label>
                        <div class="col-sm-4" style="display:inline-block"><select id="Modelo" name="Modelo" class="form-control" >
                        <option value=0>0%</option>
                        <option value=0.5>50%</option>
                        <option value=1>100%</option>
                        <option value="N/A">No Aplica</option>
                        </select>
                        </div>
                        <div class="pregunta_Modelo" ; style="display:inline-block; margin:2px">
                            <button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-lg' data-toggle='modal' data-target='#modal_pregunta_Modelo'><i class='fas fa-question-circle fa-sm'></i></button>
                        </div>	
                    </div>
                    <div class="form-group" style="width:600px; ; max-width:55%; float:left">
                        <label for="Responsable_AIA" class="col-sm-8 control-label">Responsable AIA</label>
                        <div class="col-sm-6"><select id="Responsable_AIA" name="Responsable_AIA" class="form-control" >
                        <option value=""></option>
                        <?php
                            /*require("../conexion.php");
                            $query="SELECT * FROM $db"."_profesionales";
                            $resultado= mysqli_query($conexion, $query);
                            while ($valores = mysqli_fetch_array($resultado)){
                                echo '<option value="'.$valores["nombre"].'">'.$valores["nombre"].'</option>';
                            };*/
                        ?>
                        </select>
                        </div>	
                    </div>
                    <div class="form-group" style="width:100%;">
                        <label for="Observaciones" class="col-sm-8 control-label">Observaciones</label>
                        <div class="col-sm-8"><textarea id="Observaciones" name="Observaciones" class="form-control" ></textarea></div>
                    </div>
<!--                </div>-->
                
                <!--Se crean los botones Guardar y Listar-->
				<!--<div class="form-group">
					<div class="col-sm-offset-2 col-sm-8">
						<input id="" type="submit" class="btn btn-primary" value="Guardar">
						<input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar">
					</div>
				</div>-->
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
		<div id="cuadro3" class="cuadro3" style="height:40px">
			<form class="form-botones" action="" method="POST">
                
                
                <!--Se crea el botón editar-->
				<div class="form-group">
					<div class="col-sm-offset-1 col-sm-12">
						<button type= 'button' class='leyenda_colores btn btn-secondary btn-m' data-toggle='modal' data-target='#modal_leyenda_colores'>Leyenda <i class='fas fa-question-circle fa-lg'></i></button>
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
				<table id="dt_intermedia" class="dt_intermedia table table-bordered table-hover" cellspacing="0" width="100%">
					<thead>
						<tr>
                            <th></th>
                            <th>Consecutivo</th>
							<th>Id</th>
							<th>Actividad</th>
							<th>Título</th>
                            <th>Semanas al Inicio</th>
                            <th>Diseños y Especificaciones</th>
                            <th>Materiales</th>
                            <th>Mano de Obra</th>
                            <th>Equipos</th>
                            <th>Predecesoras</th>
                            <th>Procedimiento Constructivo</th>
                            <th>Modelación BIM</th>
                            <th>% Liberación</th>
                            <th>Responsable AIA</th>
                            <th>Observaciones</th>											
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
            <div class="modal fade" id="modal_pregunta_D_y_E" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_D_y_E_Label">Restricciones de Diseños y Especificaciones</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> Si en el proyecto no están los diseños para la construcción de algún elemento.</li><br>
                        <li align="justify"><b style="font-size:125%">33%:</b> Si los diseños (Con sello para construcción) ya fueron entregados al equipo de construcción, pero no han sido revisados en profundidad ni por el director ni por los residentes.</li><br>
                        <li align="justify"><b style="font-size:125%">66%:</b> Una vez los diseños están en el proyecto y cuentan con el visto bueno del director y de los residentes.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> Una vez que los diseños que están aprobados por dirección de obra fueron entregados a los contratistas y/o maestros de obra.</li><br>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_Materiales" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_Materiales_Label">Restricciones de Materiales</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de aprovisionamiento de los materiales que se necesitan para ejecutar la actividad.</li><br>
                        <li align="justify"><b style="font-size:125%">33%:</b> La actividad esta al día de acuerdo al plan de compras.</li><br>
                        <li align="justify"><b style="font-size:125%">66%:</b> La actividad esta al día de acuerdo al plan de aprovisionamiento.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> Los materiales que se necesitan ya están en el proyecto disponibles para su uso.</li><br>
                        <p align="justify"><b style="font-size:125%">Nota 1:</b> se debe aclarar que una actividad no se puede liberar completamente, si <b><u>mínimo uno</u></b> de los materiales que necesita para ser ejecutada no ha llegado al proyecto.</p>
                        <p align="justify"><b style="font-size:125%">Nota 2:</b> Ver formatos de plan de compras y plan de aprovisionamiento.</p>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_MdeO" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_MdeO_Label">Restricciones de Mano de Obra</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de mano de obra para la actividad.</li><br>
                        <li align="justify"><b style="font-size:125%">33%:</b> Existen los contratos de mano de obra, pero el recurso de personal todavía no esta ubicado en el proyecto.</li><br>
                        <li align="justify"><b style="font-size:125%">66%:</b> Existe en el proyecto documentación y cumplimiento de requisitos legales para ingresar al proyecto, además de toda la adecuación de campamentos necesaria.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> El recurso de personal de el o los contratistas seleccionados para la actividad ya están en el proyecto.</li><br>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_Equipos" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_Equipos_Label">Restricciones de Equipos</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> No existen contratos de aprovisionamiento de los equipos que se necesitan para ejecutar la actividad.</li><br>
                        <li align="justify"><b style="font-size:125%">33%:</b> La actividad esta al día de acuerdo al plan de compras.</li><br>
                        <li align="justify"><b style="font-size:125%">66%:</b> La actividad esta al día de acuerdo al plan de aprovisionamiento.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> Los equipos que se necesitan ya están en el proyecto disponibles para su uso.</li><br>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_Predecesora" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_Predecesora_Label">Restricciones de Actividades Predecesoras</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> las actividades predecesoras que restringen el inicio de la actividad no han iniciado o están atrasadas de acuerdo al programa.</li><br>
                        <li align="justify"><b style="font-size:125%">50%:</b> las actividades predecesoras que restringen el inicio de la actividad van con un rendimiento igual o superior al que demanda el programa.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> las actividades predecesoras que restringen el inicio de la actividad ya están acabadas.</li><br>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_Pdto_Cons" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_Pdto_Cons_Label">Restricciones de Procedimiento Constructivo</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> No existe procedimiento constructivo para la actividad.</li><br>
                        <li align="justify"><b style="font-size:125%">50%:</b> Existe procedimiento constructivo pero no se ha divulgado con el grupo profesional de la obra.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> El procedimiento fue divulgado en la obra y aprobado por el director.</li><br>
                        <p align="justify"><b style="font-size:125%">Nota:</b> Ver formato para elaborar procedimiento constructivo.</p>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
              </div>

            </div>
          </div>
            <!-- Modal -->
            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->            
            <div class="modal fade" id="modal_pregunta_Modelo" role="dialog">
            <div class="modal-dialog modal-lg">

              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title" id="modal_pregunta_Modelo_Label">Restricciones de Modelación BIM</h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul style="padding:0% 5%; margin:0">
                        <li align="justify"><b style="font-size:125%">0%:</b> No hay modelos en el proyecto.</li><br>
                        <li align="justify"><b style="font-size:125%">50%:</b> Existen los modelos pero no están coordinados.</li><br>
                        <li align="justify"><b style="font-size:125%">100%:</b> Existen modelos coordinados para todas las disciplinas.</li><br>
                        <li align="justify"><b style="font-size:125%">No Aplica:</b> La tarea no aplica para ser modelada.</li><br>
                    </ul>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
                </div>
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
            eliminar();
		});
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        /*$("#btn_listar").on("click", function(){
            var posicion=$('.dataTables_scrollBody').scrollTop();
            listar();
            cambiar_posicion(posicion);
            
            $("#cuadro2").slideUp("slow");
            $("#cuadro1").slideDown("slow");
            $("#cuadro3").slideDown("slow");
        });*/
        
        /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            location.reload(true);

            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });
    
        
        /* Ejecuta la funcion guardar, solo cuando se presiona el botón guardar. La función guardar busca la informacion registrada en el formulario de registro de usuarios y lo envia por medio de AJAX para que se ejecute la funcion modificar en guardar.php */
		var guardar = function(){
			$("#btn_guardar").on("click", function(e){
				e.preventDefault();
				var frm = $(".form_modificar").serialize();
                
                
                var D_y_E=$("#select_D_y_E").serialize();
                var Materiales=$("#select_Materiales").serialize();
                var MdeO=$("#select_MdeO").serialize();
                var Equipos=$("#select_Equipos").serialize();
                var Predecesora=$("#select_Predecesora").serialize();
                var Pdto_Cons=$("#select_Pdto_Cons").serialize();
                var Modelo=$("#select_Modelo").serialize();
                var Responsable_AIA=$("#select_Responsable_AIA").serialize();
                var Observaciones=$("#select_Observaciones").serialize();
                frm=frm+"&"+D_y_E+"&"+Materiales+"&"+MdeO+"&"+Equipos+"&"+Predecesora+"&"+Pdto_Cons+"&"+Modelo+"&"+Responsable_AIA+"&"+Observaciones;
                //console.log(frm);
				$.ajax({
					method: "POST",
					url: "../programacion_intermedia/guardar_programacion_intermedia.php<?php echo "?db=$db&semana=$semana"?>",
                    contenttype:"charset=utf-8",
					data: frm,
				}).done( function( info ){
					//var json_info = JSON.parse( info );
                    //console.log(json_info);
                    //mostrar_mensaje( json_info );
					//limpiar_datos();
                    var posicion=$('.dataTables_scrollBody').scrollTop();
                    location.assign("posicion_intermedia.php?posicion_intermedia="+posicion)
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
					url: "../programacion_intermedia/guardar_programacion_intermedia.php?db=<?php echo $db?>",
                    contenttype:"charset=utf-8",
					data: {"Id": Id, "opcion": opcion}
				}).done( function( info ){
					//var json_info = JSON.parse( info );
					//mostrar_mensaje( json_info );
					limpiar_datos();
					var posicion=$('.dataTables_scrollBody').scrollTop();
                    listar();
                    cambiar_posicion(posicion);
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
            $("#Id").val("");
            $("#D_y_E").val(0).focus();
            $("#Materiales").val(0);
            $("#MdeO").val(0);
            $("#Equipos").val(0);
            $("#Predecesora").val(0);
            $("#Pdto_Cons").val(0);
            $("#Modelo").val(0);
            $("#Estado_Restricciones").val(0);
            $("#Responsable_AIA").val(0);
            $("#Observaciones").val(0);
		}
        
        var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");
		}
        
        /*Acá se inicia la datatable y se crean sus valores por defecto como el ordenamiento, las celdas que se muestran, los datos, las opciones de longitud de los registros, y el color de las filas dependiendo del estado de las actividades*/

            
        var listar=function(){
            var posicion= <?php 
            if(isset($_SESSION["posicion_intermedia"])){
                echo $_SESSION["posicion_intermedia"];
            }else{
                echo 0;
            }
            ?>;
            /*Identificamos la altura de la hoja para determinar la altura de la tabla*/            
            var alturahoja= $(window).height();
            var alturatabla= ((1.38*alturahoja)-513.21);
            
            $("#cuadro2").slideUp("slow");
            $("#cuadro3").slideDown("slow");
//            $("#cuadro1").slideDown("slow");
            var table = $("#dt_intermedia").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"i<"clear">>',
                "destroy":true,
                //"order": [[ 1, "asc" ]],
                //"orderCellsTop": true,
                "autoWidth": true,
/*                "fixedHeader": false,*/
                "scrollX": true,
                
//                console.log($(document).height());
                "scrollY": alturatabla,
                "scrollCollapse": false,
                //"paging":false,
                


                "ajax":{
                  "method":"POST",
                  "url":"../programacion_intermedia/listar_programacion_intermedia.php<?php echo "?db=$db&semana=$semana"?>"
              },
                
                
                'columnDefs': [

                    {
                        'targets': [2],
                        'width':'10%',
                    }, 
                    {
                        'targets': [4,5,6,7,8,10,11,12,13,14],
                        'width':'12%',
                    },
                    
                    {
                        'targets': [9],
                        'width':'12%',
                    },
                    
                    {
                        'targets': [3,15],
                        'width':'30%',
                    },

                    {
                        'targets': [6,7,8,9,10,11,12],
                        'render': function ( data, type, full, meta ) {
                            if(data=="N/A"){
                                return data;
                            }else if(data==null || data==""){
                                data="";
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(2);
                                return +data+'%';
                            };

                        },
                    },
                    
                    {
                        'targets': [0],
                        'render': function ( data, type, full, meta ) {
                            if(data=="Boton"){
                                boton=""/*<button type= 'button' class='editar btn btn-primary btn-sm' title='Editar el estado de las restricciones de la actividad seleccionada'><i class='fa fa-edit' aria-hidden='true' ></i></button>*/;
                            }else{
                                boton="";
                            }
                            return boton;
                            
                        },
                    },
                    
                    {
                        'targets': [13],
                        'render': function ( data, type, full, meta ) {
                            if(data==0){
                                icono="<i style='color:red' class='fa fa-exclamation-triangle fa-lg' aria-hidden='true' ></i></button>"/*<button type= 'button' class='editar btn btn-primary btn-sm' title='Editar el estado de las restricciones de la actividad seleccionada'><i class='fa fa-edit' aria-hidden='true' ></i></button>*/;
                            }else if(data>0 && data<1){
                                icono="<i style='color:RGB(210,203,59)' class='fa fa-minus-circle fa-lg' aria-hidden='true' ></i></button>";
                            }else if(data==1){
                                icono="<i style='color:green' class='fa fa-check-square fa-lg' aria-hidden='true' ></i></button>";
                            }
                            if(data==null || data==""){
                                data="";
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(2);
                                return +data+'% '+icono;
                            }
                            
                        },
                    },
                    
                    {
                        'targets': [0],
                        'className': 'Botones'
                    },                    
                    {
                        'targets': [6],
                        'className': 'input_D_y_E'
                    },
                    {
                        'targets': [7],
                        'className': 'input_Materiales'
                    },
                    {
                        'targets': [8],
                        'className': 'input_MdeO'
                    },
                    {
                        'targets': [9],
                        'className': 'input_Equipos'
                    },
                    {
                        'targets': [10],
                        'className': 'input_Predecesora'
                    },
                    {
                        'targets': [11],
                        'className': 'input_Pdto_Cons'
                    },
                    {
                        'targets': [12],
                        'className': 'input_Modelo'
                    },
                    {
                        'targets': [14],
                        'className': 'input_Responsable_AIA'
                    },
                    {
                        'targets': [15],
                        'className': 'input_Observaciones'
                    },
                  ],
                

                
                'select': {
                 'style': 'false',
                },  
                
                "lengthMenu": [100000],
                
                "columns":[
                    {"data":"boton"},
                    {"data":"Consecutivo_en_Programa", "visible":false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Titulo", "visible":false},
                    {"data":"Dias_Inicio"},
                    {"data":"D_y_E"},
                    {"data":"Materiales"},
                    {"data":"MdeO"},
                    {"data":"Equipos"},
                    {"data":"Predecesora"},
                    {"data":"Pdto_Cons"},
                    {"data":"Modelo"},
                    {"data":"Estado_Restricciones"},
                    {"data":"Responsable_AIA"},
                    {"data":"Observaciones"},
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
                    else if((fin_act>=inicio_sem && inicio_act<fin_sem) && data.Ruta_Critica==="1" && data.Estado!="Terminada Antes" && data.Ejecutado<1){
                        $(row).addClass('Semanal_Critica');

                    } 

                    else if((fin_act>=inicio_sem && inicio_act<fin_sem) && data.Ruta_Critica==="0" && data.Estado!="Terminada Antes" && data.Ejecutado<1){
                        $(row).addClass('Semanal_No_Critica');
                    } 

                    else if(data.Estado==="Atrasada"){
                        $(row).addClass('Semanal_Atrasada');
                    }

                    else if(inicio_act<=fecha_intermedia && inicio_act>=inicio_sem && data.Estado!="Terminada Antes" && data.Ejecutado<1){
                        $(row).addClass('Intermedia');
                    }
                    
                    else{
                        $(row).addClass('No_Activa');
                    }

                },

                "language": idioma_espanol
            });
            
            ocultos(table);
            cambiar_posicion(posicion);
            <?php $_SESSION["posicion_intermedia"]=0 ?>;
            obtener_data_editar("#dt_intermedia tbody", table);
            obtener_id_eliminar("#dt_intermedia tbody", table);
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
                var data= table.row($(this).parents("tr")).data();
                var Id=$("#Id").val(data.Consecutivo_en_Programa),
				    opcion = $("#opcion").val("modificar");
                if(only_once==true && data.Titulo==0){
                    var codigo_html_D_y_E = "<select id='select_D_y_E' name='D_y_E' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_D_y_E btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_D_y_E'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_D_y_E').html(codigo_html_D_y_E);
                    
                    var codigo_html_Materiales = "<select id='select_Materiales' name='Materiales' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Materiales btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Materiales'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_Materiales').html(codigo_html_Materiales); 
                    
                    var codigo_html_MdeO = "<select id='select_MdeO' name='MdeO' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_MdeO btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_MdeO'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_MdeO').html(codigo_html_MdeO); 
                    
                    var codigo_html_Equipos = "<select id='select_Equipos' name='Equipos' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.33>33%</option><option value=0.66>66%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Equipos btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Equipos'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_Equipos').html(codigo_html_Equipos);
                    
                    var codigo_html_Predecesora = "<select id='select_Predecesora' name='Predecesora' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Predecesora btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Predecesora'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_Predecesora').html(codigo_html_Predecesora); 
                    
                    var codigo_html_Pdto_Cons = "<select id='select_Pdto_Cons' name='Pdto_Cons' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Pdto_Cons btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Pdto_Cons'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_Pdto_Cons').html(codigo_html_Pdto_Cons); 
                    
                    var codigo_html_Modelo = "<select id='select_Modelo' name='Modelo' class='form-control' style='display:inline-block'><option value=0>0%</option><option value=0.5>50%</option><option value=1>100%</option><option value='N/A'>No Aplica</option></select><button type= 'button' class='pregunta_Modelo btn btn-primary-outiline btn-sm' data-toggle='modal' data-target='#modal_pregunta_Modelo'><i class='fas fa-question-circle fa-sm'></i></button>";
                    $( this ).parent().find('.input_Modelo').html(codigo_html_Modelo);
                    
                    var Responsables_AIA = <?php
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
                    var codigo_html_Responsable_AIA = "<select id='select_Responsable_AIA' name='Responsable_AIA' class='form-control' ><option value=''></option>"+Responsables_AIA+"</select>";
                    $( this ).parent().find('.input_Responsable_AIA').html(codigo_html_Responsable_AIA); 
                    
                    var codigo_html_Observaciones = "<textarea id='select_Observaciones' name='Observaciones' class='form-control'></textarea>";
                    $( this ).parent().find('.input_Observaciones').html(codigo_html_Observaciones); 
                    
                    
                    var codigo_html_botones = "<button type= 'button' id='btn_guardar' class='guardar btn btn-success btn-xs' style='padding:5px; margin:1px' title='Guardar el porcentaje de ejecución asignado'><i class='fa fa-save fa-xs' aria-hidden='true' ></i></button><button type= 'button' id='btn_cancelar2' class='cancelar btn btn-danger btn-xs' style='padding:5px; margin:1px' title='Cancelar la edición'><i class='fa fa-undo fa-xs' aria-hidden='true' ></i></button>";
                    $( this ).parent().find('.Botones').html(codigo_html_botones);
                    $("#select_D_y_E").val(data.D_y_E).change();
                    $("#select_D_y_E").focus();
                    $("#select_Materiales").val(data.Materiales).change();
                    $("#select_MdeO").val(data.MdeO).change();
                    $("#select_Equipos").val(data.Equipos).change();
                    $("#select_Predecesora").val(data.Predecesora).change();
                    $("#select_Pdto_Cons").val(data.Pdto_Cons).change();
                    $("#select_Modelo").val(data.Modelo).change();
                    $("#select_Responsable_AIA").val(data.Responsable_AIA).change();
                    $("#select_Observaciones").val(data.Observaciones).change();
                    //$(".input_Ejecutado_Editar").select();
                    $(".input_D_y_E, .input_Materiales, .input_MdeO, .input_Equipos, .input_Predecesora, .input_Pdto_Cons, .input_Modelo, .input_Responsable_AIA, .input_Observaciones").keypress(function(e){
                        if(e.keyCode==13){
                            $("#btn_guardar").click();
                        }
                    });
                    $(".input_D_y_E, .input_Materiales, .input_MdeO, .input_Equipos, .input_Predecesora, .input_Pdto_Cons, .input_Modelo, .input_Responsable_AIA, .input_Observaciones").keyup(function(e){
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
                
                    //$("#cuadro2").slideDown("slow");
                    //$("#cuadro3").slideUp("slow");
                    //$("#cuadro1").slideUp("slow");
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
            
        var nueva_sem=function(){
            $("#btn_guardar_nueva_sem").on("click", function(){            
                f_inicio_sem=$("#inicio_sem").val(),
                opcion="nueva_sem";
                console.log(f_inicio_sem);
				$.ajax({
					method:"POST",
					url: "../programacion_intermedia/guardar_programacion_intermedia.php?db=<?php echo $db?>",
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
					url: "../programacion_intermedia/guardar_programacion_intermedia.php?db=<?php echo $db?>",
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
            $('#dt_intermedia').on( 'draw.dt', function () {
            $('.dataTables_scrollBody').scrollTop(p);
            } );
        }
        
        
        var ocultos=function(table){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="G"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="S"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                table.column( 0 ).visible( false );
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia').css('display', 'none');
                window.location.href='../programacion_semanal/programacion_semanal.php';
            }
            
            var max_semana=<?php echo $Max_Semana?>;
            var semana=<?php echo $semana?>;
            
            if((max_semana-2)>=semana){
                //$('.nueva_sem, .eliminar_sem, .button.editar, #btn_editar').css('display', 'none');
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