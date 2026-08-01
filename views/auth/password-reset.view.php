<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Nueva Contraseña | Last Planner AIA</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('auth') ?>
    <link rel="stylesheet" href="/css/login-brand-unified.css?v=<?= filemtime(__DIR__ . '/../../public/css/login-brand-unified.css') ?>">
</head>
<body class="hold-transition login-page login-brand-page aia-shell">
    <div class="login-box">
        <div class="card card-login">
            <div class="card-header">
                <div class="login-brand-lockup" aria-label="Last Planner AIA">
                    <span class="login-brand-mark" aria-hidden="true"></span>
                    <span class="login-brand-wordmark">Last Planner AIA</span>
                </div>
                <h1 class="login-title">Define tu nueva contraseña</h1>
                <p class="login-subtitle">Usa al menos 6 caracteres, una mayúscula y un carácter especial.</p>
            </div>

            <div class="card-body">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType !== '' ? $messageType : 'info'); ?> alert-custom mb-4" role="alert" aria-live="assertive">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($isTokenValid): ?>
                    <form action="/password/reset" method="post" data-auth-form>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="input-group mb-3">
                            <label for="password" class="auth-field-label">Nueva contraseña</label>
                            <input type="password" id="password" name="password" class="aia-input" placeholder="Nueva contraseña" autocomplete="new-password" minlength="6" pattern="(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}" aria-describedby="password-policy" required>
                            <div class="input-group-append">
                                <button type="button" class="input-group-text auth-password-toggle" data-password-toggle="password" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <p id="password-policy" class="auth-field-help">Mínimo 6 caracteres, una mayúscula y un carácter especial.</p>

                        <div class="input-group mb-4">
                            <label for="confirm_password" class="auth-field-label">Confirmar contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="aia-input" placeholder="Confirma tu contraseña" autocomplete="new-password" required>
                            <div class="input-group-append">
                                <button type="button" class="input-group-text auth-password-toggle" data-password-toggle="confirm_password" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="aia-btn aia-btn-primary aia-btn--lg aia-btn--block" data-loading-text="Actualizando…">
                            ACTUALIZAR CONTRASEÑA <i class="fas fa-key ml-2" aria-hidden="true"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center mb-3">
                        <a class="auth-link" href="/password/forgot">Solicitar un nuevo enlace</a>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-3 mb-3">
                    <a class="auth-link" href="/login">Volver al inicio de sesión</a>
                </div>

                <div class="footer-text">
                    &copy; 2026 Arquitectos e Ingenieros Asociados<br>
                    Construyendo con <strong>+CERTEZA</strong>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>
    <script src="/public/js/modules/aia_ui/auth_forms.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/auth_forms.js') ?>"></script>

</body>
</html>
