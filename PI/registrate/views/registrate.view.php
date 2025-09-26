<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
    <link rel="stylesheet" href="../css/estilos4.css">
    <title>Last Planner PI AIA</title> 

</head>
<body>
    <div class="encabezado">
        <ul>
            <li><img src="../imagenes/logo.png" width="30%"></li>
            <li><h1 class="titulo">Registrate</h1></li>
        </ul>
    </div>
    
    <div class="contenedor">
        
        <hr class="border">
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'])?>" method="POST" class="formulario" name="login">   
            
            <div class="form-group">           
                <i class="icono izquierda fa fa-user"></i><input type="text" name="nombre" class="nombre" placeholder="Nombre:">
            </div>
            
            <div class="form-group">           
                <i class="icono izquierda fa fa-envelope"></i><input type="text" name="email" class="email" placeholder="Email:">
            </div>

            <div class="form-group">           
                <i class="icono izquierda fa fa-graduation-cap"></i><input type="text" name="cargo" class="cargo" placeholder="Cargo:">
            </div>

            <div class="form-group">           
                <i class="icono izquierda fa fa-building"></i><select id="proyecto" name="proyecto" class="proyecto custom-select">
                        <option value="">Proyecto:</option>
                        <?php
                            require("../conexion.php");
                            $query="SELECT * FROM general_proyectos_procesos WHERE Area='PI'";
                            $resultado= mysqli_query($conexion, $query);
                            while ($valores = mysqli_fetch_array($resultado)){
                                echo '<option value="'.$valores["Proyecto_Proceso"].'">'.$valores["Proyecto_Proceso"].'</option>';
                            };
                        ?>
                        <option value="Todos">Todos</option>
                        </select>
            </div>
            
            <!--<div class="form-group">           
                <i class="icono izquierda fa fa-building"></i><select id="permisos" name="permisos" class="permisos custom-select">
                        <option value="">Permisos:</option>
                        <option value="A">Admin</option>
                        <option value="V">Visualización</option>
                        <option value="R">Residente (Actividades y calificación de calidad y administración de contratos)</option>
                        <option value="S">Residente SST (Solo calificación de proveedores en SST)</option>
                        <option value="G">Residente Socio-Ambiental (Solo calificación de proveedores en Gestión Socio-Ambiental)</option>
                        <option value="C">Sub-Contratista</option>
                        </select>
            </div>-->
            
            <div class="form-group">           
                <i class="icono izquierda fa fa-user"></i><input type="text" name="usuario" class="usuario" placeholder="Usuario:">
            </div>
            
            <div class="form-group">           
                <i class="icono izquierda fa fa-lock"></i><input type="password" name="password" class="password" placeholder="Contraseña:">
            </div>
            
            <div class="form-group">           
                <i class="icono izquierda fa fa-lock"></i><input type="password" name="password2" class="password_btn" placeholder="Confirmar Contraseña:">
                <i class="submit-btn fa fa-arrow-right" onclick="login.submit()"></i>
            </div>
            
            <?php if(!empty($errores)): ?>
                <div class="error">
                    <ul>
                        <?php echo $errores; ?>
                    </ul>
                </div>
            <?php endif ?>
        
        </form>
        <p class="texto-registrate">
        ¿ Ya tienes Cuenta ?
        <a href="../login/login.php">Iniciar Sesión</a>
        </p>
    </div>
    
    <!-- Bloquear el click derecho-->
<!--    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>-->

</body>
</html>