<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
    <link rel="stylesheet" href="../css/stylesLogin.css">
    <title>Last Planner AIA</title>
    <link rel="shortcut icon" href="../imagenes/florAIA.png">

</head>
<body>
    <div class="encabezadoLogin">
        <ul>
            <li><img src="../imagenes/aiaConstruccionMasCerteza.png" width="100%"><h1 class="titulo">  Iniciar Sesión</h1></li>
        </ul>
    </div>
    <br>
    <div class="contenedor">

        <hr class="border">

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'])?>" method="POST" class="formulario" name="login" id="login">

            <div class="form-group">
                <i class="icono izquierda fa fa-building"></i><select id="proyecto_login" name="proyecto_login" class="proyecto_login custom-select">
                        <option value="">Proyecto:</option>
                        <?php
                            require("../conexion.php");
                            $query="SELECT * FROM general_proyectos_procesos WHERE Activo=1 AND Area='Construccion'";
                            $resultado= mysqli_query($conexion, $query);
                            while ($valores = mysqli_fetch_array($resultado)){
                                echo '<option value="'.$valores["Proyecto_Proceso"].'">'.$valores["Proyecto_Proceso"].'</option>';
                            };
                            mysqli_close($conexion);
                        ?>
                        </select>
            </div>

            <div class="form-group">
                <i class="icono izquierda fa fa-user"></i><input type="text" name="usuario" class="usuario" id="usuario" placeholder="Usuario:" autocomplete="username">
            </div>

            <div class="form-group">
                <i class="icono izquierda fa fa-lock"></i><input type="password" name="password" class="password_btn" id="password_btn" placeholder="Contraseña:" autocomplete="current-password">
                <i class="submit-btn fa fa-arrow-right" id="login_submit" onclick="login.submit()"></i>
            </div>

            <?php if(!empty($errores)): ?>
                <div class="error">
                    <ul>
                        <?php echo $errores; ?>
                    </ul>
                </div>
            <?php endif ?>

        </form>
        <!--<p class="texto-registrate">
        ¿ No tienes Cuenta ?
        <a href="../registrate/registrate.php">Registrate</a>
        </p>-->
    </div>

    <!-- Iniciar Jquery-->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.12.4.js"></script>

    <!-- Bloquear el click derecho-->
    <script type='text/javascript'>document.oncontextmenu = function(){return false}</script>

    <script>

        $(document).ready(function(){
            $("#password_btn, #usuario, #proyecto_login").keyup(function(e){
                if(e.keyCode==13)
                    $("#login").submit();
            });
        });

    </script>

</body>
</html>
