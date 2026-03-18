<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Iniciar Sesión | Last Planner AIA</title>

    <!-- Google Fonts: Montserrat & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE / Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.css">
    <link rel="stylesheet" href="/css/login-brand-unified.css?v=1.2">
</head>
<body class="hold-transition login-page login-brand-page">

    <div class="login-box">
        <div class="card card-login">
            <div class="card-header">
                <img src="/img/aiaConstruccionMasCerteza.png" alt="AIA Logo" class="brand-logo">
                <h1 class="login-title">Bienvenido a Last Planner AIA</h1>
                <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>
            
            <div class="card-body">
                
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-custom mb-4">
                        <ul class="mb-0 pl-3">
                            <?php echo $errores; ?>
                        </ul>
                    </div>
                <?php endif ?>

                <form action="/login" method="post" id="loginForm">
                    <div class="input-group mb-3">
                        <label for="usuario" class="sr-only">Usuario</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Usuario" autocomplete="username" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group mb-4">
                        <label for="password" class="sr-only">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" autocomplete="current-password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-aia btn-block">
                                INICIAR SESIÓN <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="footer-text">
                    &copy; 2026 Arquitectos e Ingenieros Asociados<br>
                    Construyendo con <strong>+CERTEZA</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.all.min.js"></script>
    <!-- AIA Alert Interceptor (Glassmorphism 2026) -->
    <script src="/js/core/AiaAlertInterceptor.js?v=2026.2"></script>
    
    <script>
        // Prevent context menu
        document.oncontextmenu = function(){return false};
    </script>

</body>
</html>
