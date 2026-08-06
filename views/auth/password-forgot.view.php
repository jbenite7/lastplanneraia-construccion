<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Restablecer Contraseña | Last Planner AIA</title>

    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('auth') ?>
    <link rel="stylesheet" href="/css/login-brand-unified.css?v=<?= filemtime(__DIR__ . '/../../public/css/login-brand-unified.css') ?>">
</head>
<body class="hold-transition login-page login-brand-page aia-shell">
    <main class="login-box">
        <div class="card card-login">
            <div class="card-header">
                <div class="login-brand-lockup" aria-label="Last Planner AIA">
                    <span class="login-brand-mark" aria-hidden="true"></span>
                    <span class="login-brand-wordmark">Last Planner AIA</span>
                </div>
                <h1 class="login-title">Restablecer contraseña</h1>
                <p class="login-subtitle">Ingresa tu correo y te enviaremos un enlace seguro para crear una nueva contraseña.</p>
            </div>

            <div class="card-body">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType !== '' ? $messageType : 'info'); ?> alert-custom mb-4" role="alert" aria-live="assertive">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="/password/forgot" method="post" data-auth-form>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="auth-field mb-4">
                        <label for="email" class="auth-field-label">Correo electrónico</label>
                        <div class="input-group">
                            <input type="email" id="email" name="email" class="aia-input" placeholder="nombre@empresa.com" autocomplete="email" value="<?php echo htmlspecialchars($emailValue); ?>" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope" aria-hidden="true"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="aia-btn aia-btn-primary aia-btn--lg aia-btn--block" data-loading-text="Enviando…">
                        ENVIAR ENLACE <i class="fas fa-paper-plane ml-2" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="text-center mt-3 mb-3">
                    <a class="auth-link" href="/login">Volver al inicio de sesión</a>
                </div>

                <div class="footer-text">
                    &copy; 2026 Arquitectos e Ingenieros Asociados<br>
                    Construyendo con <strong>+CERTEZA</strong>
                </div>
            </div>
        </div>
    </main>

    <script src="/public/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>
    <script src="/public/js/modules/aia_ui/auth_forms.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/auth_forms.js') ?>"></script>

</body>
</html>
