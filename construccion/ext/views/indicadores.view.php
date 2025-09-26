<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Last Planner AIA</title>
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=0.8,maximum-scale=1,minimum-scale=0.8">

    <!-- Fuentes de Google-->
    <link href="https://fonts.googleapis.com/css?family=Roboto|Bree+Serif&display=swap" rel="stylesheet">

    <!-- Font Awesome (Íconos)-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">


    <!--Iniciar estilos de Bootstrap-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">

    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../css/styles.css">

	<!-- Estilos Buttons DataTables -->
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css">

    <!-- Checkboxes DataTables -->
    <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">

    <!--Estilos Any Chart-->
    <link href="https://cdn.anychart.com/releases/v8/css/anychart-ui.min.css?hcode=c11e6e3cfefb406e8ce8d99fa8368d33" type="text/css" rel="stylesheet">
    <link href="https://cdn.anychart.com/releases/v8/fonts/css/anychart-font.min.css?hcode=c11e6e3cfefb406e8ce8d99fa8368d33" type="text/css" rel="stylesheet">


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
                $Fecha_Fin_Sem=date("Y-m-d",strtotime($Fecha_Inicio_Sem ."+6 days"));
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
            <li><img src="../imagenes/logoHorizontal.png" width="40%"></li>

            <?php
            $query2="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
            $resultado2= mysqli_query($conexion, $query2);
            $data=mysqli_fetch_assoc($resultado2);
            $Fecha_Inicio_SemYMD=$data["Fecha_Inicio_Sem"];
            $Fecha_Fin_SemYMD=$data["Fecha_Fin_Sem"];
            ?>
            <li><h1 class="titulo">Last Planner AIA - <?php echo "$proyecto, Indicadores Semana $semana " ?><h2 class="titulo_pequeño"><?php echo"(del $Fecha_Inicio_SemYMD al $Fecha_Fin_SemYMD)"?></h2></h1></li>
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
            <!--<nav class="navbar navbar-expand-m navbar-dark ">
              <a class="navbar-brand" href="#">
                <ul>
                    <li><img src="../imagenes/florAIA.png" width="" class="d-inline-block align-top" alt=""></li>
                    <li class="lps">LPS</li>
                    <li class="pagina"> - Indicadores / Semana <?php echo $semana ?></li>
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
                        <button type= 'button' class='nueva_sem btn btn-primary' onclick=nueva_sem() data-toggle='modal' data-target='#modal_nueva_sem'><i class="fa fa-plus fa-lg"></i> Nueva Semana</button>
                    </a>
                  </li>

                    <?php
                        /*require("../conexion.php");
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";
                                    }else{
                                        echo "
                                        <li class='nav-item dropdown active' style='padding: 2px 16px'>
                                            <a class='nav-link dropdown-toggle' style='padding: 0px' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i' id='navbarDropdown' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Semana $i (del $ini al $fin)       <button type= 'button' class='eliminar_sem btn btn-danger btn-sm' title='Eliminar la Semana $i' onclick=eliminar_sem($i) data-toggle='modal' data-target='#modal_eliminar_sem'><i class='fa fa-trash fa-m'></i></button>
                                            </a>
                                            <div class='dropdown-menu show' aria-labelledby='navbarDropdown'>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programa_general&semana=$i'>Programa General</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item active' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
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
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_intermedia&semana=$i'>Programación Intermedia</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=programacion_semanal&semana=$i'>Programación Semanal</a>
                                                <a class='dropdown-item' href='../cambiar_pagina.php?seccion=indicadores&semana=$i'>Indicadores</a>
                                            </div>
                                        </li>";
                                    }
                                }
                            };
                        };*/

                    ?>

                  <li class="nav-item">
                    <a class="nav-link" href="../cerrar.php" tabindex="-1" aria-disabled="true">Cerrar Sesión</a>
                  </li>
                </ul>
              </div>
            </nav>-->
        </div>
    </div>


</header>

<main class="main">

        <!--Línea donde se inserta el botón para editar los registros -->
	<div class="row">
		<div id="cuadro3" class="cuadro3  col-lg-12">
			<form class="form-botones" action="" method="POST">


                <!--Se crea el botón editar-->
				<!--<div class="form-group">
					<div class="col-sm-offset-1 ">
                        <div class="grupo_botones btn-group" role="group" aria-label="Basic example" style="padding:10">
                            <button id="btn_Actividades" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=programacion_semanal&semana=$semana"?>'">Actividades <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNP" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNP&semana=$semana"?>'">Causas No Programación <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_CNC" type="button" class="btn btn-success btn-sm" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CNC&semana=$semana"?>'">Causas No Cumplimiento <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Proveedores" type="button" class="btn btn-success btn-sm " onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=CIC&semana=$semana"?>'">Calificación de Proveedores <i class="fas fa-arrow-right fa-m"></i></button>
                            <button id="btn_Cal_Profesionales" type="button" class="btn btn-success btn-sm active" onclick="window.location.href='<?php echo "../cambiar_pagina.php?seccion=indicadores&semana=$semana"?>'">Indicadores</button>
                        </div>
					</div>
				</div>-->
			</form>

            <!--Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no-->
			<div class=" col-sm-8">
				<p class="mensaje"></p>
			</div>

		</div>
	</div>
    <br>

    <!--Se crea la estructura de la tabla, y Se crea el mensaje emergente que dice si los comandos fueron ejecutados correctamente o no (se repite el mismo de la línea anterior) -->
	<div class="row">
        <div id="div_grafico_PAC" style="width: 95%; max-width: 1300px; margin:auto; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_grafico_PAC">Seguimiento Porcentaje de Actividades Completadas (PAC)</h3>
                </div>

                    <div class="grupo_clase_PAC" style="width:600px; max-width:33%; display: inline-block;">
                        <label for="clase_PAC" class="col-sm-8 control-label">General / Profesional / Sub-Contratista</label>
                        <div class="col-sm-9"><select id="clase_PAC" name="clase_PAC" class="form-control" onchange="cambio_clase_PAC()">
                            <option value="general">General</option>
                            <option value="profesional">Profesional AIA</option>
                            <option value="subcontratista">Sub-Contratista</option>
                        </select>
                        </div>
                    </div>

                    <div class="grupo_nombre_PAC" style="width:600px; max-width:33%; display: inline-block;">
                        <label for="nombre_PAC" class="col-sm-8 control-label">Nombre</label>
                        <div class="col-sm-9"><select id="nombre_PAC" name="nombre_PAC" class="form-control" onchange="grafico_PAC('')" disabled>
                            <option value=""></option>
                        </select>
                        </div>
                    </div>

                    <div class="grupo_ver_ultimas_semanas_PAC" style="width:600px; max-width:33%; display: inline-block;">
                        <label for="ver_ultimas_semanas_PAC" class="col-sm-8 control-label">Ver Número de Semanas</label>
                        <div class="col-sm-9"><select id="ver_ultimas_semanas_PAC" name="ver_ultimas_semanas_PAC" class="form-control" onchange="grafico_PAC('')" >
                            <option value="Todas">Todas</option>
                            <option value=12>Últimas 12 Semanas</option>
                            <option value=6>Últimas 6 Semanas</option>
                            <option value=3>Últimas 3 Semanas</option>
                        </select>
                        </div>
                    </div>

            </form>
            <div id="grafico_PAC" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px" >
            </div>
        </div>


        <div id="div_Pareto_CNC" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_Pareto_CNC">Pareto de Causas de No Cumplimiento</h3>
                </div>

            </form>
            <div id="Pareto_CNC" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px" >
            </div>
        </div>


        <div id="div_Semana_CNC" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_Semana_CNC">Tendencia Semanal de Causas de No Cumplimiento</h3>
                </div>

            </form>
            <div id="Semana_CNC" style="width: 100%; height: 400px; text-align: center; margin:auto; padding:-10px 0px" >
            </div>
        </div>

        <div id="div_ind_compromisos" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_ind_compromisos">Indicadores de Compromisos</h3>
                </div>

            </form>
            <div id="compromisos_semana" style="width: 50%; height: 600px; text-align: right; margin:auto; padding:-10px 0px; display: inline-block; float:left" >
            </div>
            <div id="compromisos_acumulado" style="width: 50%; height: 600px; text-align: right; margin:auto; padding:-10px 0px; display: inline-block" >
            </div>
            <br>
            <div id="compromisos_historial" style="width: 100%; height: 400px; margin-top: 60px; text-align: center" >
            </div>
        </div>

        <div id="div_restricciones" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_restricciones">Liberación de Restricciones (Programación Intermedia)</h3>
                </div>

            </form>
            <div id="contenedor_graficos_restricciones" class="contenedor_graficos_restricciones">
                <div id="titulos_restricciones" style="width: 100%; height: 63px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_1" style="width: 13%; height: 60px; margin: auto; text-align: center; vertical-align: bottom; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                    <p>Semanas</p>
                    </div>
                    <div id="restricciones_semana_6" style="width: 13%; height: 60px; margin: auto; text-align: center; vertical-align: bottom; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>6 Semanas Para Iniciar</p>
                    </div>
                </div>


                <div id="combo_restricciones_1" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_1" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-5)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-5)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones1' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_1" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_semana_5" style="width: 13%; height: 60px; text-align: center; margin: 70px 0px 0px 0px; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>5 Semanas Para Iniciar</p>
                    </div>
                </div>
                <div id="combo_restricciones_2" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_2" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-4)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-4)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones2' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_2" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#325955; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_3" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_semana_4" style="width: 13%; height: 60px; text-align: center; margin: 70px 0px 0px 0px; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>4 Semanas Para Iniciar</p>
                    </div>
                </div>
                <div id="combo_restricciones_3" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_3" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-3)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-3)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones3' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_4" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#478079; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_5" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#325955; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_6" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_semana_3" style="width: 13%; height: 60px; text-align: center; margin: 70px 0px 0px 0px; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>3 Semanas Para Iniciar</p>
                    </div>
                </div>
                <div id="combo_restricciones_4" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_4" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-2)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-2)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones4' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_7" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#5DA69D; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_8" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#478079; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_9" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#325955; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_10" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_semana_2" style="width: 13%; height: 60px; text-align: center; margin: 70px 0px 0px 0px; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>2 Semanas Para Iniciar</p>
                    </div>
                </div>
                <div id="combo_restricciones_5" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_5" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-1)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-1)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones5' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_11" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#72CCC2; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_12" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#5DA69D; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_13" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#478079; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_14" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#325955; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_15" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_semana_1" style="width: 13%; height: 60px; text-align: center; margin: 70px 0px 0px 0px; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%" >
                        <p>1 Semana Para Iniciar</p>
                    </div>
                </div>
                <div id="combo_restricciones_6" style="width: 100%; height: 143px; margin-left:3%; margin-right:2%; text-align: center">
                    <div id="semana_combo_restricciones_6" style="width: 13%; height: 140px; text-align: center; vertical-align: middle; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:white; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    <?php
                        require("../conexion.php");
                        $query="SELECT COUNT(*) FROM $db"."_semanas_activas WHERE Semana=($semana-0)";
                        $resultado= mysqli_query($conexion, $query);
                        $data=mysqli_fetch_assoc($resultado);
                        $conteo=$data["COUNT(*)"];
                        if($conteo==0){
                            echo "<p style='height:150px; padding:0; vertical-align:middle'>No Aplica</p>";
                        }else{
                            $query1="SELECT * FROM $db"."_semanas_activas WHERE Semana=($semana-0)";
                            $resultado1= mysqli_query($conexion, $query1);
                            $data1=mysqli_fetch_assoc($resultado1);
                            $Semana_etiqueta=$data1["Semana"];
                            $Fecha_Inicio_Sem_etiqueta=$data1["Fecha_Inicio_Sem"];
                            $Fecha_Fin_Sem_etiqueta=$data1["Fecha_Fin_Sem"];
                            echo "<p id='semana_restricciones6' style='height:50px; padding: 0'>Semana $Semana_etiqueta</p>
                                    <p style='height:50px; padding: 0'>De: $Fecha_Inicio_Sem_etiqueta</p>
                                    <p style='height:50px; padding: 0'>A: $Fecha_Fin_Sem_etiqueta</p>";
                        }

                    ?>
                    </div>
                    <div id="restricciones_16" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#88F2E6; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_17" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#72CCC2; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_18" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#5DA69D; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_19" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#478079; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_20" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#325955; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                    <div id="restricciones_21" style="width: 13%; height: 140px; text-align: center; display: inline-block; float:left; margin-left: 0.1%; margin-right:0.1%; background:#1D3330; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px;" >
                    </div>
                </div>

            </div>

        </div>

        <div id="div_calificaciones_integrales" style="width: 95%; margin:auto; max-width: 1300px; border-style: solid; border-width: 2px; border-color: rgba(55,68,81,1); border-radius: 5px; padding: 2px 2px 10px 2px">
            <form class="form form-horizontal" action="" method="POST">
                <div class="form_eval form-group">
                    <h3 id="form_adm_calificaciones_integrales">Calificaciones Integrales</h3>
                </div>

            </form>
            <div id="calificacion_contratistas" style="width: 100%; height: 500px; margin-top: 60px; text-align: center" >
            </div>
            <div id="calificacion_profesionales" style="width: 100%; height: 500px; margin-top: 0px; text-align: center" >
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
            <div class="modal" id="modal_PAC" tabindex="-1" role="dialog" aria-labelledby="modal_PAC_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_PAC_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_PAC" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Descripción</th>
                                            <th>Ubicación</th>
                                            <th>Sub-Contratista</th>
                                            <th>Profesional AIA</th>
                                            <th>Unidad de Medida</th>
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
                </div>
            </div>
			<!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_Pareto_CNC" tabindex="-1" role="dialog" aria-labelledby="modal_Pareto_CNC_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_Pareto_CNC_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_Pareto_CNC" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Descripción</th>
                                            <th>Ubicación</th>
                                            <th>Sub-Contratista</th>
                                            <th>Profesional AIA</th>
                                            <th>Unidad de Medida</th>
                                            <th>Cantidad Comprometida</th>
                                            <th>Cantidad Real</th>
                                            <th>Causa de No Cumplimiento</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_CNC_semanales" tabindex="-1" role="dialog" aria-labelledby="modal_CNC_semanales_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_CNC_semanales_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_CNC_semanales" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Descripción</th>
                                            <th>Ubicación</th>
                                            <th>Sub-Contratista</th>
                                            <th>Profesional AIA</th>
                                            <th>Unidad de Medida</th>
                                            <th>Cantidad Comprometida</th>
                                            <th>Cantidad Real</th>
                                            <th>Causa de No Cumplimiento</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_compromisos" tabindex="-1" role="dialog" aria-labelledby="modal_compromisos_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_compromisos_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_compromisos" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Descripción</th>
                                            <th>Ubicación</th>
                                            <th>¿Liberada?</th>
                                            <th>Profesional AIA</th>
                                            <th>Categoría No Programación</th>
                                            <th>Causa de No Programación</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_compromisos_sin_rest" tabindex="-1" role="dialog" aria-labelledby="modal_compromisos_sin_rest_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_compromisos_sin_rest_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_compromisos_sin_rest" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Diseños y Especificaciones</th>
                                            <th>Materiales</th>
                                            <th>Mano de Obra</th>
                                            <th>Equipos</th>
                                            <th>Predecesora</th>
                                            <th>Procedimiento Constructivo</th>
                                            <th>Modelación BIM</th>
                                            <th>Estado de Liberación</th>
                                            <th>Responsable AIA</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_restricciones" tabindex="-1" role="dialog" aria-labelledby="modal_restricciones_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_restricciones_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_restricciones" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Id</th>
                                            <th>Actividad</th>
                                            <th>Diseños y Especificaciones</th>
                                            <th>Materiales</th>
                                            <th>Mano de Obra</th>
                                            <th>Equipos</th>
                                            <th>Predecesora</th>
                                            <th>Procedimiento Constructivo</th>
                                            <th>Modelación BIM</th>
                                            <th>Estado de Liberación</th>
                                            <th>Responsable AIA</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_calificacion_contratistas" tabindex="-1" role="dialog" aria-labelledby="modal_calificacion_contratistas_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_calificacion_contratistas_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_calificacion_contratistas" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
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
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <!-- Se crea el Modal que solicita la confirmación de eliminar un registro o no -->
            <div class="modal" id="modal_calificacion_profesionales" tabindex="-1" role="dialog" aria-labelledby="modal_calificacion_profesionales_Label" style="">
                <div class="modal-fluid modal-dialog-scrollable" role="document">
                    <div class="modal-content-indicadores">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modal_calificacion_profesionales_Label"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive col-sm-12">
                                <table id="dt_calificacion_profesionales" class="dt_general table table-bordered table-hover" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Profesional</th>
                                            <th>PAC</th>
                                            <th>% Completado</th>
                                            <th>% Actividades Críticas Cumplidas</th>
                                            <th>% Actividades No Críticas Cumplidas</th>
                                            <th>% Actividades Atrasadas Cumplidas</th>
                                            <th>Calificación Integral</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
        </form>

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
		</form>
	</div>
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

    <!--Google Charts-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>

    <!--Any Chart-->
    <script src="https://cdn.anychart.com/releases/v8/js/anychart-base.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>
    <!--  <script src="https://cdn.anychart.com/releases/v8/js/anychart-ui.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>-->
    <!--  <script src="https://cdn.anychart.com/releases/v8/js/anychart-exports.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>-->
    <script src="https://cdn.anychart.com/releases/v8/js/anychart-circular-gauge.min.js?hcode=c11e6e3cfefb406e8ce8d99fa8368d33"></script>


    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

	<script>

    /* Ejecuta las funciones listar, guardar y eliminar, solo cuando la página esta lista */
    $(document).on("ready", function(){
        ocultos();
        listar(nombre="general");

        //guardar();

    });

    var listar=function(nombre){
        if (nombre=="general"){
            grafico_PAC(nombre);
        }
    }

    /* Ejecuta la funcione listar, solo cuando se presiona el botón Listar */
        $("#btn_cancelar, #btn_cancelar1").on("click", function(){
            location.reload(true);
            //listar();

            $(document).on("ready", function(){
                $("#navbarNav").addClass("show");
            });
        });

    var grafico_PAC=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);

        if(nombre=="general"){
            var opcion = "PAC",
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);

        }else if(nombre==""){
            var opcion = "PAC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                if(nombre==""){
                    nombre="general";
                }
                console.log(nombre);
        }else{
            var opcion = "PAC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre, "ultimas_semanas": ultimas_semanas}
                    }).responseText;

          function drawVisualization() {
            // Some raw data (not necessarily accurate)

            var data = new google.visualization.DataTable(jsonData);

            var array = JSON.parse(jsonData);

            var Posicion_ultima_semana = (array['rows'].length)-1;
            //console.log(Posicion_ultima_semana);
            var ultima_semana = array['rows'][Posicion_ultima_semana]['c'][0]['v'];
            ultima_semana = ultima_semana.substring(7,)*1;
            //console.log(ultima_semana);
            var Semana_comparar =<?php echo $semana; ?>;
            console.log(nombre);
            //var PAC_ultima_semana = array['rows'][Posicion_ultima_semana]['c'][1]['v'];

            if(Semana_comparar > ultima_semana && nombre == "general"){
                alert("No se genera el informe hasta que las actividades comprometidas durante la semana se hayan calificado. Se redirigirá hacia la programación semanal");

                $('#div_grafico_PAC, #div_Pareto_CNC, #div_Semana_CNC, #div_ind_compromisos, #div_restricciones, #div_calificaciones_integrales').css('display', 'none');

                window.location.href='../programacion_semanal/programacion_semanal.php';
            }else{
                if(!array['rows']){
                }else{
                    var maximo= (Math.max(array['rows'][0]['c'][1]['v'], array['rows'][0]['c'][3]['v'], array['rows'][0]['c'][4]['v'], array['rows'][0]['c'][6]['v']));
                }


                var options = {
                  /*title :"",*/

                  vAxis: {title: 'Calificación Semanal' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}},
                  hAxis: {title: 'Semanas', titleTextStyle : {fontSize: 16, bold:true}},
                  seriesType: 'bars',
                  series: {0: {color: "rgb(55,86,54)"}, 1: {type: 'line', color: "blue", lineDashStyle: [10, 2]}, 2: {color: "rgb(191,215,48)"}, 3: {type: 'line', color: "red", lineDashStyle: [10, 2]}},
                  legend:{position: 'right', textStyle: {fontSize: 12}},
                  // isStacked: true

                };

                var chart = new google.visualization.ComboChart(document.getElementById('grafico_PAC'));
                chart.draw(data, options);

                  google.visualization.events.addListener(chart, 'select', selectHandler);
                  function selectHandler() {

                    var selection = chart.getSelection()[0];
                    var array = JSON.parse(jsonData);

                    var indicador = array['cols'][selection.column]['label'];

                    var str =  data.getValue(selection.row, 0)
                    var semana = str.substring(7,)*1;
                    //var semana = selection.row + 1;

                    var clase_PAC = $('#clase_PAC').val();
                      if (clase_PAC=="general"){

                          clase_PAC="Consolidado General";
                          var nombre_PAC="";
                          $('#modal_PAC_Label').text("Detalle Porcentaje de Actividades Completadas (PAC) Semana " + semana + ", " + clase_PAC);
                          $("#modal_PAC").fadeIn(1000).modal();
                          listar_detalle_PAC_General(semana);

                      }else if(clase_PAC=="subcontratista"){
                          clase_PAC="Sub-Contratista: ";
                          var nombre_PAC = $('#nombre_PAC').val();
                          $('#modal_PAC_Label').text("Detalle Porcentaje de Actividades Completadas (PAC) Semana " + semana + ", " + clase_PAC + nombre_PAC);
                          $("#modal_PAC").fadeIn(1000).modal();
                          listar_detalle_PAC_Subcontratista(semana, nombre_PAC);

                      }else if(clase_PAC=="profesional"){
                          clase_PAC="Profesional: ";
                          var nombre_PAC = $('#nombre_PAC').val();
                          $('#modal_PAC_Label').text("Detalle Porcentaje de Actividades Completadas (PAC) Semana " + semana + ", " + clase_PAC + nombre_PAC);
                          $("#modal_PAC").fadeIn(1000).modal();
                          listar_detalle_PAC_Profesional(semana, nombre_PAC);
                      }

                    chart.draw(data, options);
                    //window.alert(indicador + " de la semana " + semana + " para la clase " + clase_PAC + " con nombre " + nombre_PAC);
                  }
                  pareto_CNC(nombre);
            }
        }
    }

    var pareto_CNC=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);

        if(nombre=="general"){
            var opcion = "pareto_CNC";
        }else{
            var opcion = "pareto_CNC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val();
        }

        if(nombre=="general"){
            var opcion = "pareto_CNC",
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);

        }else if(nombre==""){
            var opcion = "pareto_CNC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }else{
            var opcion = "pareto_CNC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre}
                    }).responseText;

          function drawVisualization() {
            // Some raw data (not necessarily accurate)

            var data = new google.visualization.DataTable(jsonData);

            var options = {
              /*title :"",*/
              vAxes: {0:{title: 'Frecuencia' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}, 1:{title: 'Frecuencia Acumulada' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}},
              hAxis: {title: 'Causas de No Cumplimiento', titleTextStyle : {fontSize: 16, bold:true}},
              seriesType: 'bars',
              series: {0: {targetAxisIndex:0}, 1: {type: 'line', color: "red", lineDashStyle: [10, 2], targetAxisIndex:1}},
              legend: 'none',
              isStacked: true,
              annotations:false
            };

            var chart = new google.visualization.ComboChart(document.getElementById('Pareto_CNC'));
            chart.draw(data, options);

              google.visualization.events.addListener(chart, 'select', selectHandler);
              function selectHandler() {

                var selection = chart.getSelection()[0];
                var array = JSON.parse(jsonData);



                var indicador = array['cols'][selection.column]['label'];


                var tipo_CNC =  data.getValue(selection.row, 0)



                var clase_PAC = $('#clase_PAC').val();
                  if (clase_PAC=="general"){
                      clase_PAC1="Consolidado General";

                  }else if(clase_PAC=="subcontratista"){
                      clase_PAC1="Sub-Contratista: ";

                  }else if(clase_PAC=="profesional"){
                      clase_PAC1="Profesional: ";
                  }


                  var nombre_PAC = $('#nombre_PAC').val();
                  $('#modal_Pareto_CNC_Label').text("Detalle Causas de no Cumplimieto Categoría: " + tipo_CNC + ", " + clase_PAC1 + nombre_PAC);
                  $("#modal_Pareto_CNC").fadeIn(1000).modal();
                  listar_detalle_Pareto_CNC(nombre_PAC, clase_PAC, tipo_CNC);


                //window.alert(indicador + " de la semana " + semana + " para la clase " + clase_PAC + " con nombre " + nombre_PAC);

                  chart.draw(data, options);
              }

              semana_CNC(nombre);

        }
    }

    var semana_CNC=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);

        if(nombre=="general"){
            var opcion = "Semana_CNC",
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);

        }else if(nombre==""){
            var opcion = "Semana_CNC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }else{
            var opcion = "Semana_CNC",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre, "ultimas_semanas": ultimas_semanas}
                    }).responseText;

          function drawVisualization() {
            // Some raw data (not necessarily accurate)


            var data = new google.visualization.DataTable(jsonData);

            var options = {
              /*title :"<?php echo "Seguimiento Porcentaje de Actividades Completadas (PAC)"?>",*/
              vAxis: {title: 'Causas de No Cumplimiento' ,format:'#', titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}},
              hAxis: {title: 'Semana', titleTextStyle : {fontSize: 16, bold:true}},
              seriesType: 'bars',
              series: {0: {color: "rgb(55,86,54)"}, 1: {color: "rgb(191,215,48)"}, 2: {color: "rgb(118,68,138)"}, 3: {color: "rgb(245,176,65)"}, 4: {color: "rgb(36,113,163)"}, 5: {color: "rgb(211,84,0)"}, 6: {color: "rgb(52,73,94)"}},
              isStacked: true,
              legend:{position: 'right', textStyle: {fontSize: 12}}
            };

            var chart = new google.visualization.ComboChart(document.getElementById('Semana_CNC'));
            chart.draw(data, options);

              google.visualization.events.addListener(chart, 'select', selectHandler);
              function selectHandler() {

                var selection = chart.getSelection()[0];
                var array = JSON.parse(jsonData);



                var tipo_CNC = array['cols'][selection.column]['label'];

                var str =  data.getValue(selection.row, 0)
                var semana = str.substring(7,)*1;




                var clase_PAC = $('#clase_PAC').val();
                  if (clase_PAC=="general"){
                      clase_PAC1="Consolidado General";

                  }else if(clase_PAC=="subcontratista"){
                      clase_PAC1="Sub-Contratista: ";

                  }else if(clase_PAC=="profesional"){
                      clase_PAC1="Profesional: ";
                  }

                  var nombre_PAC = $('#nombre_PAC').val();
                  $('#modal_CNC_semanales_Label').text("Detalle Causas de no Cumplimieto Categoría: " + tipo_CNC + ", " + clase_PAC1 + nombre_PAC + ", " + str);
                  $("#modal_CNC_semanales").fadeIn(1000).modal();
                  listar_detalle_CNC_semanales(nombre_PAC, clase_PAC, tipo_CNC, semana);


                //window.alert(indicador + " de la semana " + semana + " para la clase " + clase_PAC + " con nombre " + nombre_PAC);

                  chart.draw(data, options);
              }

              ind_compromisos_semana(nombre);

        }
    }

    var ind_compromisos_semana=function(nombre){
        $( "#compromisos_semana" ).empty();
        var names = ['Tareas Críticas Comprometidas', 'Tareas No Críticas Comprometidas', 'Tareas Atrasadas Críticas Comprometidas', 'Tareas Atrasadas No Críticas Comprometidas', 'Tareas Comprometidas Sin Liberar Restricciones'];

        if(nombre=="general"){
            var opcion = "ind_compromisos",
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);

        }else if(nombre==""){
            var opcion = "ind_compromisos",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }else{
            var opcion = "ind_compromisos",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }

        if($('#clase_PAC').val()=="subcontratista"){
            $('#div_ind_compromisos').css('display', 'none');
        }else{
            $('#div_ind_compromisos').css('display', 'block');
        }

        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre, "ultimas_semanas": ultimas_semanas}
                    }).responseText;


        var data = JSON.parse(jsonData);

        var dataSet = anychart.data.set(data[0]);

        //var palette = anychart.palettes.distinctColors().items(['rgb(191,215,48)', 'rgb(211,84,0)', 'rgb(52,73,94)', 'rgb(55,86,54)', '#455a64', '#96a6a6', '#dd2c00', '#00838f', '#00bfa5', '#ffa000']);


        var makeBarWithBar = function (gauge, radius, i, width, without_stroke) {
            var stroke = '1 #e5e4e4';
            if(data[2][i]=="NA"){
                data[2][i]=0;
                label="NA";
            }else{
                data[2][i]=data[2][i]*100;
                label=data[2][i].toFixed(0) + "%";
            }

            if (without_stroke) {
                stroke = null;
                gauge.label(i)
                        .text('<b><span style="color: black; font-size: 10px;" >' + names[i] + ", " + label + '</span></b>')// color: #7c868e
                        .useHtml(true);
                gauge.label(i)
                        .hAlign('center')
                        .vAlign('middle')
                        .anchor('right-center')
                        .padding(0, 10)
                        .height(width / 2 + '%')
                        .offsetY(radius + '%')
                        .offsetX(0);

                gauge.tooltip().format("{%value}%");
            }



            if(data[2][i]<70){
                color_abc="rgb(231, 76, 60)";
            }else if(data[2][i]>=70 && data[2][i]<95){
                color_abc="rgb(241, 196, 15)";
            }else if(data[2][i]>=95){
                color_abc="rgb(29, 131, 72)";
            }

            if(data[2][4]>30){
                color_d="rgb(231, 76, 60)";
            }else if(data[2][4]<=30 && data[2][4]>5){
                color_d="rgb(241, 196, 15)";
            }else if(data[2][4]<=5){
                color_d="rgb(29, 131, 72)";
            }

            gauge.bar(i).dataIndex(i)
                    .radius(radius)
                    .width(width*1.03)
                    .fill(color_abc)
                    .stroke(stroke)
                    .zIndex(4);
            gauge.bar(4).dataIndex(4)
                    .radius(radius)
                    .width(width*1.03)
                    .fill(color_d)
                    .stroke(stroke)
                    .zIndex(4);
            gauge.bar(i + 100).dataIndex(5)
                    .radius(radius)
                    .width(width)
                    .fill('RGB(202, 207, 210)')
                    .stroke(stroke)
                    .zIndex(3);

            return gauge.bar(i);


        };

        anychart.onDocumentReady(function () {
            var gauge = anychart.gauges.circular();
            gauge.data(dataSet);
            gauge.fill('#fff')
                    .stroke(null)
                    .padding(0)
                    .margin(50)
                    .startAngle(0)
                    .sweepAngle(270);

            var axis = gauge.axis().radius(135).width(1).fill(null);
            axis.scale()
                    .minimum(0)
                    .maximum(100)
                    .ticks({interval: 5})
                    .minorTicks({interval: 1});
            axis.labels().enabled(true).fontColor('black').format("{%value}%");
            axis.ticks().enabled(false);
            axis.minorTicks().enabled(false);
            makeBarWithBar(gauge, 100, 0, 15, true);
            makeBarWithBar(gauge, 80, 1, 15, true);
            makeBarWithBar(gauge, 60, 2, 15, true);
            makeBarWithBar(gauge, 40, 3, 15, true);
            makeBarWithBar(gauge, 20, 4, 15, true);
            //makeBarWithBar(gauge, 20, 4, 17, true);

            //gauge.margin(50);
            gauge.title().text('<span style="color:black; font-size: 16px; font-weight: bold">Indicadores Actividades Comprometidas</span>' +
                    '<br/><span style="color:black; font-size: 14px;">(Presente Semana)</span><br/><br/>').useHtml(true);
            gauge.title()
                    .enabled(true)
                    .hAlign('center')
                    .padding(0)
                    .margin([0, 0, 20, 0]);

            gauge.container('compromisos_semana');
            gauge.draw();


            // remap data
              var view = dataSet.mapAs();


              // set listener on chart
              gauge.listen(

                // listener type
                "pointclick",

                // function, if listener triggers
                function(e) {
                    view.set(
                        e.pointIndex,                       // get index of clicked column
                        "x",                            // get parameter to update
                        //view.get(e.pointIndex, "x") // parameter updating
                    );

                }
              );

        });



       ind_compromisos_acumulado(nombre);
    }

    var ind_compromisos_acumulado=function(nombre){
        $( "#compromisos_acumulado" ).empty();
        var names = ['Tareas Críticas Comprometidas', 'Tareas No Críticas Comprometidas', 'Tareas Atrasadas Críticas Comprometidas', 'Tareas Atrasadas No Críticas Comprometidas', 'Tareas Comprometidas Sin Liberar Restricciones'];

        if(nombre=="general"){
            var opcion = "ind_compromisos",
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);

        }else if(nombre==""){
            var opcion = "ind_compromisos",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }else{
            var opcion = "ind_compromisos",
                nombre=$("#div_grafico_PAC #nombre_PAC").val(),
                ultimas_semanas=$("#div_grafico_PAC #ver_ultimas_semanas_PAC").val();
                //console.log(ultimas_semanas);
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre, "ultimas_semanas": ultimas_semanas}
                    }).responseText;

        var data = JSON.parse(jsonData);

        //console.log(jsonData);

        var dataSet = anychart.data.set(data[1]);
        //var palette = anychart.palettes.distinctColors().items(['rgb(191,215,48)', 'rgb(211,84,0)', 'rgb(52,73,94)', 'rgb(55,86,54)', '#455a64', '#96a6a6', '#dd2c00', '#00838f', '#00bfa5', '#ffa000']);


        var makeBarWithBar = function (gauge, radius, i, width, without_stroke) {
            var stroke = '1 #e5e4e4';
            if(data[3][i]=="NA"){
                data[3][i]=0;
                label="NA";
            }else{
                data[3][i]=data[3][i]*100;
                label=data[3][i].toFixed(0) + "%";
            }

            if (without_stroke) {
                stroke = null;
                gauge.label(i)
                        .text('<b><span style="color: black; font-size: 10px;" >' + names[i] + ", " + label + '</span></b>')// color: #7c868e
                        .useHtml(true);
                gauge.label(i)
                        .hAlign('center')
                        .vAlign('middle')
                        .anchor('right-center')
                        .padding(0, 10)
                        .height(width / 2 + '%')
                        .offsetY(radius + '%')
                        .offsetX(0);

                gauge.tooltip().format("{%value}%");
            }

            if(data[3][i]<70){
                color_abc="rgb(231, 76, 60)";
            }else if(data[3][i]>=70 && data[3][i]<95){
                color_abc="rgb(241, 196, 15)";
            }else if(data[3][i]>=95){
                color_abc="rgb(29, 131, 72)";
            }

            if(data[3][4]>30){
                color_d="rgb(231, 76, 60)";
            }else if(data[3][4]<=30 && data[3][4]>5){
                color_d="rgb(241, 196, 15)";
            }else if(data[3][4]<=5){
                color_d="rgb(29, 131, 72)";
            }

            gauge.bar(i).dataIndex(i)
                    .radius(radius)
                    .width(width*1.03)
                    .fill(color_abc)
                    .stroke(stroke)
                    .zIndex(4);
            gauge.bar(4).dataIndex(4)
                    .radius(radius)
                    .width(width*1.03)
                    .fill(color_d)
                    .stroke(stroke)
                    .zIndex(4);
            gauge.bar(i + 100).dataIndex(5)
                    .radius(radius)
                    .width(width)
                    .fill('RGB(202, 207, 210)')
                    .stroke(stroke)
                    .zIndex(3);

            return gauge.bar(i);


        };

        anychart.onDocumentReady(function () {
            var gauge = anychart.gauges.circular();
            gauge.data(dataSet);
            gauge.fill('#fff')
                    .stroke(null)
                    .padding(0)
                    .margin(50)
                    .startAngle(0)
                    .sweepAngle(270);

            var axis = gauge.axis().radius(135).width(1).fill(null);
            axis.scale()
                    .minimum(0)
                    .maximum(100)
                    .ticks({interval: 5})
                    .minorTicks({interval: 1});
            axis.labels().enabled(true).fontColor('black').format("{%value}%");
            axis.ticks().enabled(false);
            axis.minorTicks().enabled(false);
            makeBarWithBar(gauge, 100, 0, 15, true);
            makeBarWithBar(gauge, 80, 1, 15, true);
            makeBarWithBar(gauge, 60, 2, 15, true);
            makeBarWithBar(gauge, 40, 3, 15, true);
            makeBarWithBar(gauge, 20, 4, 15, true);
            //makeBarWithBar(gauge, 20, 4, 17, true);

            //gauge.margin(50);
            gauge.title().text('<span style="color:black; font-size: 16px; font-weight: bold">Indicadores Actividades Comprometidas</span>' +
                    '<br/><span style="color:black; font-size: 14px;">(Tendencia)</span><br/><br/>').useHtml(true);
            gauge.title()
                    .enabled(true)
                    .hAlign('center')
                    .padding(0)
                    .margin([0, 0, 20, 0]);

            gauge.container('compromisos_acumulado');
            gauge.draw();


            // remap data
              var view = dataSet.mapAs();


              // set listener on chart
              gauge.listen(

                // listener type
                "pointclick",

                // function, if listener triggers
                function(e) {
                    view.set(
                        e.pointIndex,                       // get index of clicked column
                        "x",                            // get parameter to update
                        //view.get(e.pointIndex, "x") // parameter updating
                    );

                }
              );


        });

        ind_compromisos_historial(nombre, JSON.stringify(data[4][0]));
    }

    var ind_compromisos_historial=function(nombre, jsonData){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);


          function drawVisualization() {
            // Some raw data (not necessarily accurate)


            var data = new google.visualization.DataTable(jsonData);

            var options = {

              legend:{position: 'right', textStyle: {fontSize: 10}},
              annotations: {
                  textStyle: {
                  //fontName: 'Times-Roman',
                  fontSize: 9,
                  //bold: true,
                  //italic: true,
                  color: 'black',     // The color of the text.
                  //auraColor: '#d799ae', // The color of the text outline.
                  opacity: 0.8          // The transparency of the text.
                }
              },
              title :"Consolidado Semanal de Indicadores de Actividades Comprometidas",
              titleTextStyle: {fontSize: 16,},
              titlePosition: 'right',
              vAxis: {title: '(%)' ,format:'#%', titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}},
              hAxis: {title: 'Semanas', titleTextStyle : {fontSize: 16, bold:true}, textStyle:{fontSize:11, bold:true}},
              seriesType: 'bars',
              series: {0: {color: "rgb(191,215,48)"}, 1: {color: "rgb(55,86,54)"}, 2: {color: "rgb(237,64,159)"}, 3: {color: "rgb(237,185,76)"}, 4: {color: "rgb(47,110,214)"}},
              chartArea: {top: 100, height: '40%', width: '70%'}
              // isStacked: true

            };

            var chart = new google.visualization.ComboChart(document.getElementById('compromisos_historial'));
            chart.draw(data, options);

              google.visualization.events.addListener(chart, 'select', selectHandler);
              function selectHandler() {

                var selection = chart.getSelection()[0];

                var array = JSON.parse(jsonData);
                var tipo_columna = selection.column;
                var trigger= array['rows'][selection.row]['c'][selection.column]['v'];

                var tipo_compromiso = array['cols'][selection.column]['label'];
                if(tipo_compromiso=="% Actividades Críticas Comprometidas"){
                    tipo_compromiso1 ="criticas"
                }else if(tipo_compromiso=="% Actividades No Críticas Comprometidas"){
                    tipo_compromiso1 ="no_criticas"
                }else if(tipo_compromiso=="% Actividades Atrasadas Críticas Comprometidas"){
                    tipo_compromiso1 ="atrasadas_criticas"
                }else if(tipo_compromiso=="% Actividades Atrasadas No Críticas Comprometidas"){
                    tipo_compromiso1 ="atrasadas_no_criticas"
                }else if(tipo_compromiso=="% Actividades Comprometidas Sin Liberar Restricciones"){
                    tipo_compromiso1 ="comp_sin_restr"
                }

                var str =  data.getValue(selection.row, 0);

                var semana = str.substring(7,)*1;



                var clase_PAC = $('#clase_PAC').val();
                  if (clase_PAC=="general"){
                      clase_PAC1="Consolidado General";

                  }else if(clase_PAC=="subcontratista"){
                      clase_PAC1="Sub-Contratista: ";

                  }else if(clase_PAC=="profesional"){
                      clase_PAC1="Profesional: ";
                  }

                  if(trigger==1 || trigger=='100%' || trigger=='NA' || (tipo_columna==9 && trigger==0) || (tipo_columna==10 && trigger=='0%')){

                  }else{
                      var nombre_PAC = $('#nombre_PAC').val();
                      if(tipo_compromiso1=="comp_sin_restr"){
                          $('#modal_compromisos_sin_rest_Label').text("Detalle Compromisos Categoría: " + tipo_compromiso + ", " + clase_PAC1 + nombre_PAC + ", " + str);
                          $("#modal_compromisos_sin_rest").fadeIn(1000).modal();
                          listar_detalle_compromisos_sin_rest(tipo_compromiso1, clase_PAC, nombre_PAC, semana);
                      }else{
                          $('#modal_compromisos_Label').text("Detalle Compromisos Categoría: " + tipo_compromiso + ", " + clase_PAC1 + nombre_PAC + ", " + str);
                          $("#modal_compromisos").fadeIn(1000).modal();
                          listar_detalle_compromisos(tipo_compromiso1, clase_PAC, nombre_PAC, semana);
                      }
                  }






                //window.alert(indicador + " de la semana " + semana + " para la clase " + clase_PAC + " con nombre " + nombre_PAC);

                  chart.draw(data, options);
              }

        }

        restricciones1(nombre);
    }


    var restricciones1=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        if(nombre=="general"){
            var opcion = "restricciones";
        }else{
            var opcion = "restricciones",
                nombre=$("#div_grafico_PAC #nombre_PAC").val();
        }

        if($('#clase_PAC').val()=="subcontratista"){
            $('#div_restricciones').css('display', 'none');
        }else{
            $('#div_restricciones').css('display', 'block');
        }

        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre}
                    }).responseText;

        var arreglo = JSON.parse(jsonData);

        function drawChart() {

            var data_6_6 = google.visualization.arrayToDataTable(arreglo['sem_6_6']);

            var data_6_5 = google.visualization.arrayToDataTable(arreglo['sem_6_5']);

            var data_6_4 = google.visualization.arrayToDataTable(arreglo['sem_6_4']);

            var data_6_3 = google.visualization.arrayToDataTable(arreglo['sem_6_3']);

            var data_6_2 = google.visualization.arrayToDataTable(arreglo['sem_6_2']);

            var data_6_1 = google.visualization.arrayToDataTable(arreglo['sem_6_1']);

            var data_5_6 = google.visualization.arrayToDataTable(arreglo['sem_5_6']);

            var data_5_5 = google.visualization.arrayToDataTable(arreglo['sem_5_5']);

            var data_5_4 = google.visualization.arrayToDataTable(arreglo['sem_5_4']);

            var data_5_3 = google.visualization.arrayToDataTable(arreglo['sem_5_3']);

            var data_5_2 = google.visualization.arrayToDataTable(arreglo['sem_5_2']);

            var data_4_6 = google.visualization.arrayToDataTable(arreglo['sem_4_6']);

            var data_4_5 = google.visualization.arrayToDataTable(arreglo['sem_4_5']);

            var data_4_4 = google.visualization.arrayToDataTable(arreglo['sem_4_4']);

            var data_4_3 = google.visualization.arrayToDataTable(arreglo['sem_4_3']);

            var data_3_6 = google.visualization.arrayToDataTable(arreglo['sem_3_6']);

            var data_3_5 = google.visualization.arrayToDataTable(arreglo['sem_3_5']);

            var data_3_4 = google.visualization.arrayToDataTable(arreglo['sem_3_4']);

            var data_2_6 = google.visualization.arrayToDataTable(arreglo['sem_2_6']);

            var data_2_5 = google.visualization.arrayToDataTable(arreglo['sem_2_5']);

            var data_1_6 = google.visualization.arrayToDataTable(arreglo['sem_1_6']);

            var options = {

                  legend:{position: 'none', textStyle: {fontSize: 9}},
                  chartArea: {
                    left: "0%",
                    top: "3%",
                    bottom:"3%",
                    width: "100%"
                  },
                  titleTextStyle: {fontSize: 16},
                  titlePosition: 'none',
                  pieSliceTextStyle:{color: 'black', fontSize: 'auto'},
                  slices: {
                    0: { color: '#B80012' },
                    1: { color: '#F0E36C' },
                    2: { color: '#308037' }
                  },
                  tooltip:{text:'percentage'},
                  backgroundColor: 'transparent'

                  // isStacked: true

                }

            var chart1 = new google.visualization.PieChart(document.getElementById('restricciones_1'));
            chart1.draw(data_1_6, options);

            var chart2 = new google.visualization.PieChart(document.getElementById('restricciones_2'));
            chart2.draw(data_2_6, options);

            var chart3 = new google.visualization.PieChart(document.getElementById('restricciones_3'));
            chart3.draw(data_2_5, options);

            var chart4 = new google.visualization.PieChart(document.getElementById('restricciones_4'));
            chart4.draw(data_3_6, options);

            var chart5 = new google.visualization.PieChart(document.getElementById('restricciones_5'));
            chart5.draw(data_3_5, options);

            var chart6 = new google.visualization.PieChart(document.getElementById('restricciones_6'));
            chart6.draw(data_3_4, options);

            var chart7 = new google.visualization.PieChart(document.getElementById('restricciones_7'));
            chart7.draw(data_4_6, options);

            var chart8 = new google.visualization.PieChart(document.getElementById('restricciones_8'));
            chart8.draw(data_4_5, options);

            var chart9 = new google.visualization.PieChart(document.getElementById('restricciones_9'));
            chart9.draw(data_4_4, options);

            var chart10 = new google.visualization.PieChart(document.getElementById('restricciones_10'));
            chart10.draw(data_4_3, options);

            var chart11 = new google.visualization.PieChart(document.getElementById('restricciones_11'));
            chart11.draw(data_5_6, options);

            var chart12 = new google.visualization.PieChart(document.getElementById('restricciones_12'));
            chart12.draw(data_5_5, options);

            var chart13 = new google.visualization.PieChart(document.getElementById('restricciones_13'));
            chart13.draw(data_5_4, options);

            var chart14 = new google.visualization.PieChart(document.getElementById('restricciones_14'));
            chart14.draw(data_5_3, options);

            var chart15 = new google.visualization.PieChart(document.getElementById('restricciones_15'));
            chart15.draw(data_5_2, options);

            var chart16 = new google.visualization.PieChart(document.getElementById('restricciones_16'));
            chart16.draw(data_6_6, options);
                google.visualization.events.addListener(chart16, 'select', selectHandler16);
                  function selectHandler16() {
                    var selection = chart16.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=6;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart16.draw(data_6_6, options);
                  }

            var chart17 = new google.visualization.PieChart(document.getElementById('restricciones_17'));
            chart17.draw(data_6_5, options);
                google.visualization.events.addListener(chart17, 'select', selectHandler17);
                  function selectHandler17() {
                    var selection = chart17.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=5;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart17.draw(data_6_5, options);
                  }

            var chart18 = new google.visualization.PieChart(document.getElementById('restricciones_18'));
            chart18.draw(data_6_4, options);
                google.visualization.events.addListener(chart18, 'select', selectHandler18);
                  function selectHandler18() {
                    var selection = chart18.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=4;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart18.draw(data_6_4, options);
                  }

            var chart19 = new google.visualization.PieChart(document.getElementById('restricciones_19'));
            chart19.draw(data_6_3, options);
                google.visualization.events.addListener(chart19, 'select', selectHandler19);
                  function selectHandler19() {
                    var selection = chart19.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=3;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart19.draw(data_6_3, options);
                  }

            var chart20 = new google.visualization.PieChart(document.getElementById('restricciones_20'));
            chart20.draw(data_6_2, options);
                google.visualization.events.addListener(chart20, 'select', selectHandler20);
                  function selectHandler20() {
                    var selection = chart20.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=2;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart20.draw(data_6_2, options);
                  }

            var chart21 = new google.visualization.PieChart(document.getElementById('restricciones_21'));
            chart21.draw(data_6_1, options);
                google.visualization.events.addListener(chart21, 'select', selectHandler21);
                  function selectHandler21() {
                    var selection = chart21.getSelection()[0];
                    selection= selection.row;
                    var semana = $("#semana_restricciones6").text();
                    semana = semana.substring(7,)*1;
                    var comienzan_en=1;

                    detalle_restricciones(selection, semana, comienzan_en);

                    chart21.draw(data_6_1, options);
                  }

            var detalle_restricciones=function(selection, semana, comienzan_en){
                if(comienzan_en==1){
                    var comienzan_en1=comienzan_en + " Semana";
                }else{
                    var comienzan_en1=comienzan_en + " Semanas";
                }
                if(selection==0){
                    $('#modal_restricciones_Label').text("Detalle de Actividades con Restricciones 0% Liberadas que Comienzan en " + comienzan_en1);
                }else if(selection==1){
                    $('#modal_restricciones_Label').text("Detalle de Actividades con Restricciones Parcialmente Liberadas que Comienzan en " + comienzan_en1);
                }else if(selection==2){
                    $('#modal_restricciones_Label').text("Detalle de Actividades con Restricciones 100% Liberadas que Comienzan en " + comienzan_en1);
                }
                $("#modal_restricciones").fadeIn(1000).modal();
                listar_detalle_restricciones(selection, semana, comienzan_en);
            }
        }
        ind_calificacion_contratistas(nombre);
    }

    var ind_calificacion_contratistas=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);

        if(nombre=="general"){
            var opcion = "cal_contratistas";
            var nombre1="general";
        }else{
            var opcion = "cal_contratistas",
                nombre1=$("#div_grafico_PAC #nombre_PAC").val();
        }

        if($('#clase_PAC').val()=="profesional"){
            var nombre1="general"
            //$('#calificacion_contratistas').css('display', 'none');
        }else{
            //$('#calificacion_contratistas').css('display', 'block');
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre1}
                    }).responseText;


          function drawVisualization() {
            // Some raw data (not necessarily accurate)


            var data = new google.visualization.DataTable(jsonData);

            var options = {

              legend:{position: 'right', textStyle: {fontSize: 14}},
              title :"Calificación Integral de Sub-Contratistas",
              titleTextStyle: {fontSize: 16,},
              titlePosition: 'right',
              vAxes: {0:{title: 'Calificación Integral' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}, 1:{title: 'Frecuencia Acumulada' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}},
              hAxis: {/*title: 'Profesionales', titleTextStyle : {fontSize: 16, bold:true},*/ textStyle:{fontSize:11}},
              seriesType: 'bars',
              series: {0: {color: "rgb(55,86,54)"}, 1: {color: "rgb(191,215,48)"}, 2: {color: "rgb(118,68,138)"}, 3: {color: "rgb(245,176,65)"}},
              chartArea: {top: 100, height: '40%'}
              // isStacked: true

            };

            var chart = new google.visualization.ComboChart(document.getElementById('calificacion_contratistas'));
            chart.draw(data, options);

              google.visualization.events.addListener(chart, 'select', selectHandler);
              function selectHandler() {

                var selection = chart.getSelection()[0];


                var array = JSON.parse(jsonData);

                var tipo_columna = selection.column;

                var tipo_calificacion = array['cols'][selection.column]['label'];



                if(tipo_calificacion=="Calificación Integral Tendencia"){
                    tipo_calificacion1 ="integral_acumulada"
                }else if(tipo_calificacion=="Calificación Integral Última Semana"){
                    tipo_calificacion1 ="integral_ultima_sem"
                }

                var subcontratista =  data.getValue(selection.row, 0);

                var calificacion =  data.getValue(selection.row, selection.column);

                var semana = <?php echo $semana?>;

                var clase_PAC =$('#clase_PAC').val();
                var nombre_PAC = $('#nombre_PAC').val();
                if(calificacion=="NA"){

                }else{
                    if(tipo_calificacion1=="integral_acumulada"){
                        $('#modal_calificacion_contratistas_Label').text("Detalle Calificación Integral Tendencia, Sub-Contratista: " + subcontratista);
                        $("#modal_calificacion_contratistas").fadeIn(1000).modal();
                        listar_detalle_cal_contratistas(tipo_calificacion1, subcontratista, semana);
                    }else if (tipo_calificacion1=="integral_ultima_sem"){
                        $('#modal_calificacion_contratistas_Label').text("Detalle Calificación Integral Última Semana, Sub-Contratista: " + subcontratista);
                        $("#modal_calificacion_contratistas").fadeIn(1000).modal();
                        listar_detalle_cal_contratistas(tipo_calificacion1, subcontratista, semana);
                    }
                }

                chart.draw(data, options);
              }


        }
        ind_calificacion_profesionales(nombre);
    }

    var ind_calificacion_profesionales=function(nombre){
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);

        if(nombre=="general"){
            var opcion = "cal_profesionales";
            var nombre1="general";
        }else{
            var opcion = "cal_profesionales",
                nombre1=$("#div_grafico_PAC #nombre_PAC").val();
        }

        if($('#clase_PAC').val()=="subcontratista"){
            var nombre1="general"
            $('#calificacion_profesionales').css('display', 'none');
        }else{
            $('#calificacion_profesionales').css('display', 'block');
        }


        var jsonData = $.ajax({
                        method:"POST",
                        url: "<?php echo "listar_indicadores.php?db=$db&semana=$semana" ?>",
                        dataType: "json",
                        async: false,
                        data: {"opcion": opcion, "nombre": nombre1}
                    }).responseText;


          function drawVisualization() {
            // Some raw data (not necessarily accurate)


            var data = new google.visualization.DataTable(jsonData);

            var options = {

              legend:{position: 'right', textStyle: {fontSize: 14}},
              title :"Calificación Integral de Profesionales",
              titleTextStyle: {fontSize: 16},
              titlePosition: 'right',
              vAxes: {0:{title: 'Calificación Integral' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}, 1:{title: 'Frecuencia Acumulada' ,format:'#%', minValue:0.0, maxValue:1.0, titleTextStyle : {fontSize: 16, bold:true}, gridlines:{count:10}}},
              hAxis: {/*title: 'Profesionales', titleTextStyle : {fontSize: 16, bold:true},*/ textStyle:{fontSize:11}},
              seriesType: 'bars',
              series: {0: {color: "rgb(118,68,138)"}, 1: {color: "rgb(245,176,65)"}},
              chartArea: {top: 30, height: '40%'}
              // isStacked: true

            };

            var chart = new google.visualization.ComboChart(document.getElementById('calificacion_profesionales'));
            chart.draw(data, options);

              google.visualization.events.addListener(chart, 'select', selectHandler);
              function selectHandler() {

                var selection = chart.getSelection()[0];

                var array = JSON.parse(jsonData);

                var tipo_columna = selection.column;

                var tipo_calificacion = array['cols'][selection.column]['label'];

                if(tipo_calificacion=="Calificación Integral Tendencia"){
                    tipo_calificacion1 ="integral_acumulada"
                }else if(tipo_calificacion=="Calificación Integral Última Semana"){
                    tipo_calificacion1 ="integral_ultima_sem"
                }

                var profesional =  data.getValue(selection.row, 0);

                var calificacion =  data.getValue(selection.row, selection.column);

                var semana = <?php echo $semana?>;

                var clase_PAC =$('#clase_PAC').val();
                var nombre_PAC = $('#nombre_PAC').val();
                if(calificacion=="NA"){

                }else{
                    if(tipo_calificacion1=="integral_acumulada"){
                        $('#modal_calificacion_profesionales_Label').text("Detalle Calificación Integral Tendencia, Profesional: " + profesional);
                        $("#modal_calificacion_profesionales").fadeIn(1000).modal();
                        listar_detalle_cal_profesionales(tipo_calificacion1, profesional, semana);
                    }else if (tipo_calificacion1=="integral_ultima_sem"){
                        $('#modal_calificacion_profesionales_Label').text("Detalle Calificación Integral Última Semana, Profesional: " + profesional);
                        $("#modal_calificacion_profesionales").fadeIn(1000).modal();
                        listar_detalle_cal_profesionales(tipo_calificacion1, profesional, semana);
                    }
                }

                chart.draw(data, options);
              }


        }

    }

    var listar_detalle_PAC_General=function(semana){

            var opcion="detalle_PAC_General";
            var url="listar_detalles_indicadores.php<?php echo "?db=$db"?>";
            var table = $("#dt_PAC").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,
//
                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"semana": semana, "opcion":opcion},
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9,10],
                        'width': "1%",
                     },

                    {
                        'targets': [9],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                    {
                        'targets': [10],
                        'render': function ( data, type, full, meta ) {
                            data=data*100;
                            data=data.toFixed(0);

                            carita="";
                            if (data == 100){
                                carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                            }else if(data < 100){
                                carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
                            }
                            return data+'%  '+carita;
                        },
                    },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Sub_Contratista",},
                    {"data":"Responsable_AIA"},
                    {"data":"Unidad"},
                    {"data":"Compromiso"},
                    {"data":"Ejecutado_Real"},
                    {"data":"P_Completado"},
                    {"data":"PAC"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_PAC_Subcontratista=function(semana, nombre_PAC){

            var opcion="detalle_PAC_Subcontratista";
            var url="listar_detalles_indicadores.php<?php echo "?db=$db"?>";
            var table = $("#dt_PAC").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,

//
                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"semana": semana, "opcion":opcion, "nombre_PAC":nombre_PAC},
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9,10],
                        'width': "1%",
                     },

                    {
                        'targets': [9],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                    {
                        'targets': [10],
                        'render': function ( data, type, full, meta ) {
                            data=data*100;
                            data=data.toFixed(0);

                            carita="";
                            if (data == 100){
                                carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                            }else if(data < 100){
                                carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
                            }
                            return data+'%  '+carita;
                        },
                    },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Sub_Contratista", "visible":false},
                    {"data":"Responsable_AIA"},
                    {"data":"Unidad"},
                    {"data":"Compromiso"},
                    {"data":"Ejecutado_Real"},
                    {"data":"P_Completado"},
                    {"data":"PAC"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_PAC_Profesional=function(semana, nombre_PAC){

            var opcion="detalle_PAC_Profesional";
            var url="listar_detalles_indicadores.php<?php echo "?db=$db"?>";
            var table = $("#dt_PAC").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"semana": semana, "opcion":opcion, "nombre_PAC":nombre_PAC},
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9,10],
                        'width': "1%",
                     },

                    {
                        'targets': [9],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                    {
                        'targets': [10],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);

                                carita="";
                                if (data == 100){
                                    carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                                }else if(data < 100){
                                    carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
                                }
                                return data+'%  '+carita;
                            }

                        },
                    },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Sub_Contratista",},
                    {"data":"Responsable_AIA", "visible":false},
                    {"data":"Unidad"},
                    {"data":"Compromiso"},
                    {"data":"Ejecutado_Real"},
                    {"data":"P_Completado"},
                    {"data":"PAC"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_Pareto_CNC=function(nombre_PAC, clase_PAC, tipo_CNC){

            var opcion="detalle_Pareto_CNC";
            console.log(tipo_CNC);

            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_Pareto_CNC").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "nombre_PAC":nombre_PAC, "clase_PAC":clase_PAC, "tipo_CNC":tipo_CNC}
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9,10,11],
                        'width': "1%",
                     },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Semana"},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Sub_Contratista",},
                    {"data":"Responsable_AIA"},
                    {"data":"Unidad"},
                    {"data":"Compromiso"},
                    {"data":"Ejecutado_Real"},
                    {"data":"CNC"},
                    {"data":"Observaciones_CNC"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_CNC_semanales=function(nombre_PAC, clase_PAC, tipo_CNC, semana){
        /*Identificamos la altura de la hoja para determinar la altura de la tabla*/

            //var alturahoja= $(window).height();
            //var alturatabla= ((1.28*alturahoja)-513.21);
            var opcion="detalle_CNC_semanales";

            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_CNC_semanales").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "nombre_PAC":nombre_PAC, "clase_PAC":clase_PAC, "tipo_CNC":tipo_CNC, "semana":semana}
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9,10,11],
                        'width': "1%",
                     },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Semana"},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Sub_Contratista",},
                    {"data":"Responsable_AIA"},
                    {"data":"Unidad"},
                    {"data":"Compromiso"},
                    {"data":"Ejecutado_Real"},
                    {"data":"CNC"},
                    {"data":"Observaciones_CNC"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_compromisos=function(tipo_compromiso1, clase_PAC, nombre_PAC, semana){
        /*Identificamos la altura de la hoja para determinar la altura de la tabla*/

            //var alturahoja= $(window).height();
            //var alturatabla= ((1.28*alturahoja)-513.21);
            var opcion="detalle_compromisos";


            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_compromisos").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "tipo_compromiso":tipo_compromiso1, "clase_PAC":clase_PAC, "nombre_PAC":nombre_PAC, "semana":semana}
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5,6,7,8,9],
                        'width': "1%",
                     },

                    {
                        'targets': [5],
                        'render': function ( data, type, full, meta ) {
                            if(data===""){
                                data="";
                                return data;
                            }else if(data==0){
                                data="Sí";
                                return data;
                            }else if (data==1){
                                data="No";
                                return data;
                            }

                        },
                    },
                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Semana", "visible": false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"Descripcion"},
                    {"data":"Ubicacion"},
                    {"data":"Prog_Sin_Restricciones_100", "visible": false},
                    {"data":"Responsable_AIA"},
                    {"data":"Categoria_CNP"},
                    {"data":"CNP"},
                    {"data":"Observaciones_CNP"},
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_compromisos_sin_rest=function(tipo_compromiso1, clase_PAC, nombre_PAC, semana){
        /*Identificamos la altura de la hoja para determinar la altura de la tabla*/

            //var alturahoja= $(window).height();
            //var alturatabla= ((1.28*alturahoja)-513.21);
            var opcion="detalle_compromisos";


            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_compromisos_sin_rest").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "tipo_compromiso":tipo_compromiso1, "clase_PAC":clase_PAC, "nombre_PAC":nombre_PAC, "semana":semana}
              },


                'columnDefs': [


                     {
                        'targets': [0,1,2,3,4,5],
                        'width': "1%",
                     },

                    {
                        'targets': [10],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                    {
                        'targets': [3,4,5,6,7,8,9],
                        'render': function ( data, type, full, meta ) {
                            if(data=="N/A"){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Semana", "visible": false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"D_y_E"},
                    {"data":"Materiales"},
                    {"data":"MdeO"},
                    {"data":"Equipos"},
                    {"data":"Predecesora"},
                    {"data":"Pdto_Cons"},
                    {"data":"Modelo"},
                    {"data":"Estado_Restricciones"},
                    {"data":"Responsable_AIA"},
                    {"data":"Observaciones"}
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_restricciones=function(selection, semana, comienzan_en){
        /*Identificamos la altura de la hoja para determinar la altura de la tabla*/

            //var alturahoja= $(window).height();
            //var alturatabla= ((1.28*alturahoja)-513.21);
            var opcion="detalle_restricciones";


            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_restricciones").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "selection":selection, "semana":semana, "comienzan_en":comienzan_en}
              },


                'columnDefs': [


                     {
                        'targets': [0,1,3,4,5,6,7,8,9,10,11,12],
                        'width': "1%",
                     },

                    {
                        'targets': [2],
                        'width': "3%",
                     },

                    {
                        'targets': [3,4,5,6,7,8,10],
                        'render': function ( data, type, full, meta ) {
                            if(data==""){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                    {
                        'targets': [9],
                        'render': function ( data, type, full, meta ) {
                            if(data=="N/A"){
                                return data;
                            }else{
                                data=data*100;
                                data=data.toFixed(0);
                                return +data+'%';
                            }

                        },
                    },

                  ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                    {"data":"Semana", "visible": false},
                    {"data":"Id"},
                    {"data":"Actividad"},
                    {"data":"D_y_E"},
                    {"data":"Materiales"},
                    {"data":"MdeO"},
                    {"data":"Equipos"},
                    {"data":"Predecesora"},
                    {"data":"Pdto_Cons"},
                    {"data":"Modelo"},
                    {"data":"Estado_Restricciones"},
                    {"data":"Responsable_AIA"},
                    {"data":"Observaciones"}
                ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_cal_contratistas=function(tipo_calificacion1, subcontratista, semana){

            var opcion="calificacion_contratistas";


            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_calificacion_contratistas").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "tipo_calificacion":tipo_calificacion1, "subcontratista":subcontratista, "semana":semana}
              },


                'columnDefs': [


                         {
                            'targets': [0,1,2,3,4,5,6,7,8,9,10,11,12],
                            'width': "1%",
                         },

                        {
                            'targets': [5,6,7,8,9,10,11],
                            'render': function ( data, type, full, meta ) {
                                if(data=='NA'){
                                    data="No Aplica";
                                    return data;
                                }else{
                                    data=data*100;
                                    data=data.toFixed(0);

                                    carita="";
                                    if (data >= 95){
                                        carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                                    } else if(data < 95 && data >= 70){
                                        carita = "<i style='color:RGB(210,203,59)' class='fas fa-meh fa-2x'></i>";
                                    } else if(data < 70){
                                        carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
                                    }
                                    return data+'%  '+carita;
                                }
                            },
                        },
                      ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                        {"data":"Semana"},
                        {"data":"Id", "visible":false},
                        {"data":"subcontratista"},
                        {"data":"alcance"},
                        {"data":"tipo_proveedor",},
                        {"data":"PAC"},
                        {"data":"P_Completado"},
                        {"data":"Calidad"},
                        {"data":"GSA"},
                        {"data":"SST"},
                        {"data":"ADM"},
                        {"data":"Cal_Integral"},
                        {"data":"Observaciones"}
                    ],

                "language": idioma_espanol
            });
    }

    var listar_detalle_cal_profesionales=function(tipo_calificacion1, profesional, semana){

            var opcion="calificacion_profesionales";


            var url="listar_detalles_indicadores.php<?php echo "?db=$db&semana=$semana"?>";
            var table = $("#dt_calificacion_profesionales").DataTable({
                "dom": '<"top"f<"clear">>rt<"bottom"pi<"clear">>',
                "destroy":true,
//                "order":false,
/*                "autoWidth": true,*/
/*                "fixedHeader": false,*/
                "scrollX": true,
                "scrollY": 350,


                //"scrollY": alturatabla,
/*                "scrollCollapse": false,*/
//                "responsive":true,
                "paging":false,


                "ajax":{
                  "method":"POST",
                  "url":url,
                  contenttype:"charset=utf-8",
                  data: {"opcion":opcion, "tipo_calificacion":tipo_calificacion1, "profesional":profesional, "semana":semana}
              },


                'columnDefs': [


                         {
                            'targets': [0,1,2,3,4,5,6,7],
                            'width': "1%",
                         },

                        {
                            'targets': [2,3,4,5,6,7],
                            'render': function ( data, type, full, meta ) {
                                if(data=='NA'){
                                    data="No Aplica";
                                    return data;
                                }else{
                                    data=data*100;
                                    data=data.toFixed(0);

                                    carita="";
                                    if (data >= 95){
                                        carita = "<i style='color:green' class='fas fa-grin-stars fa-2x'></i>";
                                    } else if(data < 95 && data >= 70){
                                        carita = "<i style='color:RGB(210,203,59)' class='fas fa-meh fa-2x'></i>";
                                    } else if(data < 70){
                                        carita = "<i style='color:red' class='fas fa-sad-cry fa-2x'></i>";
                                    }
                                    return data+'%  '+carita;
                                }
                            },
                        },
                      ],



                'select': {
                 'style': 'false',
                },

                "lengthMenu": [100],

                "columns":[
                        {"data":"Semana"},
                        {"data":"profesional"},
                        {"data":"PAC"},
                        {"data":"P_Completado"},
                        {"data":"Act_Criticas_Cumplidas"},
                        {"data":"Act_No_Criticas_Cumplidas"},
                        {"data":"Act_Atrasadas_Cumplidas"},
                        {"data":"PAC_Consolidado"}
                    ],

                "language": idioma_espanol
            });
    }

    var nueva_sem=function(){
        $("#btn_guardar_nueva_sem").on("click", function(){
            f_inicio_sem=$("#inicio_sem").val(),
            opcion="nueva_sem";

            $.ajax({
                method:"POST",
                url: "../programacion_semanal/guardar_CIC.php?db=<?php echo $db?>",
                contenttype:"charset=utf-8",
                data: {"f_inicio_sem": f_inicio_sem, "opcion": opcion}
            }).done( function( info ){
                var json_info = JSON.parse( info );
                //mostrar_mensaje( json_info );
                limpiar_datos_nueva_sem();
                location.reload(true);
                //listar();
            });
        });
    }

    var limpiar_datos_nueva_sem = function(){
			$("#opcion").val("registrar");
            $("#inicio_sem").val("");
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

    var cambio_clase_PAC=function(){
            var tipo_PAC=$("#clase_PAC").val(),
                opcion="nombre_PAC";

                if(tipo_PAC===""){
                    $('#nombre_PAC').attr('disabled', true);
                } else{
                    $('#nombre_PAC').attr('disabled', false);
                    $.ajax({
                        method:"POST",
                        url: "<?php echo "../indicadores/listar_indicadores.php?db=$db&semana=$semana" ?>",
                        contenttype:"charset=utf-8",
                        data: {"tipo_PAC": tipo_PAC, "opcion":opcion},
                        success:function(a){
                            $('#nombre_PAC').html(a);

                            grafico_PAC($('#nombre_PAC').val());
                        }
                    });
                }
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

        var ocultos=function(){
            var permiso="<?php echo $permiso?>";
            if(permiso=="R"){
                //$('#mdo_gsa, #mdo_sst, #si_gsa, #si_sst').css('display', 'none');
            }else if(permiso=="G"){
                //$('#mdo_cal, #mdo_adm, #mdo_sst, #si_cal, #si_adm, #si_sst').css('display', 'none');
            }else if(permiso=="S"){
                //$('#mdo_cal, #mdo_adm, #mdo_gsa, #si_cal, #si_adm, #si_gsa').css('display', 'none');
            }else if(permiso=="V"){
                $('.nueva_sem, .eliminar_sem').css('display', 'none');
                //$('#clase_PAC').val("subcontratista").change();
                //$('.grupo_clase_PAC, .grupo_nombre_PAC').css('display', 'none');
            }else if(permiso=="C"){
                $('.nueva_sem, .eliminar_sem, #btn_autoprogramar, #btn_agregar_actividad, .contenido_link, .informacion_general, .programa_general, .programacion_intermedia, #btn_CNP').css('display', 'none');
                //$('#clase_PAC').val("subcontratista").change();
                //$('.grupo_clase_PAC, .grupo_nombre_PAC').css('display', 'none');
            }
        }

        /*Configura la DataTable en idioma español*/
       var idioma_espanol={
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla =(",
            "sInfo":           "Mostrando  _TOTAL_ registros",
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
