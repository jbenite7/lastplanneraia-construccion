<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Iniciar Sesión | Last Planner AIA</title>

    <!-- Google Fonts: Montserrat & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE / Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
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
                <h1 class="login-title">Bienvenido a Last Planner AIA</h1>
                <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>
            
            <div class="card-body">
                
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-custom mb-4" role="alert" aria-live="assertive">
                        <ul class="mb-0 pl-3">
                            <?php echo $errores; ?>
                        </ul>
                    </div>
                <?php endif ?>

                <?php if (!empty($resetNotice)): ?>
                    <div class="alert alert-success alert-custom mb-4" role="status" aria-live="polite">
                        Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($formAction ?? '/login', ENT_QUOTES, 'UTF-8'); ?>" method="post" id="loginForm" data-auth-form>
                    <div class="auth-field mb-3">
                        <label for="usuario" class="auth-field-label">Usuario</label>
                        <div class="input-group">
                            <input type="text" id="usuario" name="usuario" class="aia-input" placeholder="Ingresa tu usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user" aria-hidden="true"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="auth-field mb-4">
                        <label for="password" class="auth-field-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="aia-input" placeholder="Contraseña" autocomplete="current-password" required>
                            <div class="input-group-append">
                                <button type="button" class="input-group-text auth-password-toggle" data-password-toggle="password" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="aia-btn aia-btn-primary aia-btn--lg aia-btn--block" data-loading-text="Ingresando…">
                                INICIAR SESIÓN <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mb-3">
                    <a class="auth-link" href="/password/forgot">¿Olvidaste tu contraseña?</a>
                </div>

                <div class="footer-text">
                    &copy; 2026 Arquitectos e Ingenieros Asociados<br>
                    Construyendo con <strong>+CERTEZA</strong>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/vendor/sweetalert2.all.min.js?v=11.4.24"></script>
    <script src="/public/js/core/AiaAlertInterceptor.js?v=20260324a"></script>
    <script src="/public/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>
    <script src="/public/js/modules/aia_ui/auth_forms.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/auth_forms.js') ?>"></script>
    
    <script>
        <?php if (!empty($timeoutNotice)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.AIA && AIA.Notice) {
                    AIA.Notice.warning('Su sesión ha expirado por inactividad. Por favor ingrese de nuevo.', 'Sesión Finalizada');
                }
                
                // Limpiar URL para evitar re-disparos al recargar
                const url = new URL(window.location);
                url.searchParams.delete('timeout');
                window.history.replaceState({}, document.title, url.pathname);
            });
        <?php endif; ?>

        <?php if (!empty($inactiveNotice)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.AIA && AIA.Notice) {
                    AIA.Notice.warning('Tu cuenta está inactiva. Contacta al administrador.', 'Acceso Bloqueado');
                }

                const url = new URL(window.location);
                url.searchParams.delete('inactive');
                window.history.replaceState({}, document.title, url.pathname);
            });
        <?php endif; ?>
    </script>
<script>
$(document).ready(function() {
    <?php if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']): ?>
    AIA.Notice.dialog({
        title: '🔑 Cambio de Contraseña Obligatorio',
        html: '<div class="brand-modal-content"><p>Para fortalecer la seguridad de <b>Last Planner AIA</b>, establece una contraseña robusta y privada.</p><div class="brand-modal-form-group"><label for="new_password">Nueva contraseña <small>(1 mayúscula y 1 carácter especial)</small></label><div class="auth-modal-password-row"><input type="password" id="new_password" class="aia-input" autocomplete="new-password" placeholder="Mín. 6 caracteres"><button type="button" class="auth-password-toggle" data-password-toggle="new_password" aria-label="Mostrar contraseña" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button></div></div><div class="brand-modal-form-group"><label for="confirm_password">Confirmar contraseña</label><div class="auth-modal-password-row"><input type="password" id="confirm_password" class="aia-input" autocomplete="new-password" placeholder="Repite tu nueva contraseña"><button type="button" class="auth-password-toggle" data-password-toggle="confirm_password" aria-label="Mostrar contraseña" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button></div></div></div>',
        icon: 'warning',
        iconColor: 'var(--ds-color-state-success-text)',
        customClass: {
            popup: 'aia-glass-popup aia-password-modal',
            title: 'swal2-title',
            htmlContainer: 'swal2-html-container',
            confirmButton: 'aia-glass-confirm-btn',
            cancelButton: 'aia-glass-cancel-btn'
        },
        buttonsStyling: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: 'Actualizar y Acceder',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (!password || password.length < 6) {
                Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            if (!/[A-Z]/.test(password)) {
                Swal.showValidationMessage('Debe contener al menos una letra mayúscula');
                return false;
            }
            if (!/[^a-zA-Z0-9]/.test(password)) {
                Swal.showValidationMessage('Debe contener al menos un carácter especial (!@#$%...)');
                return false;
            }
            if (password !== confirm) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }

            const formData = new FormData();
            formData.append('password', password);
            formData.append('confirm_password', confirm);

            return fetch('/password/update', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error(response.statusText);
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Error en el servidor: ${error}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            AIA.Notice.success(result.value.message).then(() => {
                window.location.href = '/proyectos';
            });
        } else if (result.isConfirmed) {
            AIA.Notice.error(result.value.message).then(() => location.reload());
        }
    });
    <?php endif; ?>
});
</script>
</body>
</html>
