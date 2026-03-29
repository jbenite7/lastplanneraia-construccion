<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Restablecer Contraseña | Last Planner AIA</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/css/login-brand-unified.css?v=1.2">
</head>
<body class="hold-transition login-page login-brand-page">

    <div class="login-box">
        <div class="card card-login">
            <div class="card-header">
                <img src="/img/aiaConstruccionMasCerteza.png" alt="AIA Logo" class="brand-logo">
                <h1 class="login-title">Restablecer contraseña</h1>
                <p class="login-subtitle">Ingresa tu correo y te enviaremos un enlace seguro para crear una nueva contraseña.</p>
            </div>

            <div class="card-body">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType !== '' ? $messageType : 'info'); ?> alert-custom mb-4">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="/password/forgot" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="input-group mb-4">
                        <label for="email" class="sr-only">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email" value="<?php echo htmlspecialchars($emailValue); ?>" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-aia btn-block">
                        ENVIAR ENLACE <i class="fas fa-paper-plane ml-2" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="text-center mt-3 mb-3">
                    <a href="/login">Volver al inicio de sesión</a>
                </div>

                <div class="footer-text">
                    &copy; 2026 Arquitectos e Ingenieros Asociados<br>
                    Construyendo con <strong>+CERTEZA</strong>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
