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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.css">
    <link rel="stylesheet" href="/css/login-brand-unified.css?v=1.3">
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

                <?php if (!empty($resetNotice)): ?>
                    <div class="alert alert-success alert-custom mb-4">
                        Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($formAction ?? '/login', ENT_QUOTES, 'UTF-8'); ?>" method="post" id="loginForm">
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

                <div class="text-center mb-3">
                    <a href="/password/forgot">¿Olvidaste tu contraseña?</a>
                </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.all.min.js"></script>
    <script src="/public/js/core/AiaAlertInterceptor.js?v=20260324a"></script>
    
    <script>
        // Prevent context menu
        document.oncontextmenu = function(){return false};

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
<script src="public/js/core/AiaAlertInterceptor.js"></script>

<?php if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']): ?>
<style id="pwd-modal-style">
    /* Forzar redimensionamiento y espaciado del modal de cambio de clave */
    .aia-password-modal.aia-glass-popup {
        padding: 1.5rem !important;
        border-radius: 20px !important;
        width: 25rem !important;
    }
    .aia-password-modal .swal2-icon {
        margin: 0 auto 0.5rem auto !important;
        transform: scale(0.7);
    }
    .aia-password-modal .swal2-title {
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.2 !important;
        font-size: 1.35rem !important;
    }
    .aia-password-modal .swal2-html-container {
        margin: 0.5rem 0 0 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        text-align: left !important;
    }
    .aia-password-modal .swal2-actions {
        margin-top: 1.5rem !important;
        padding: 0 !important;
        min-height: auto !important;
    }
    
    .brand-modal-content { display: flex; flex-direction: column; gap: 0.5rem; text-align: left; }
    .brand-modal-content p { margin: 0 0 0.5rem 0 !important; font-size: 0.88rem; opacity: 0.9; line-height: 1.3; color: #ecedf1; }
    .brand-modal-form-group { margin-bottom: 0 !important; }
    .brand-modal-form-group label { color: #f8f9fa !important; font-size: 0.85rem !important; margin-bottom: 2px !important; display: block; font-weight: 600; }
    .brand-modal-form-group label small { color: rgba(255,255,255,0.6); font-weight: 400; font-size: 0.75rem; display: inline-block; margin-left: 2px; }
</style>
<?php endif; ?>

<script>
$(document).ready(function() {
    <?php if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']): ?>
    AIA.Notice.dialog({
        title: '🔑 Cambio de Contraseña Obligatorio',
        html: '<div class="brand-modal-content"><p>Para fortalecer la seguridad de <b>Last Planner AIA</b>, es necesario que establezcas una nueva contraseña robusta y privada.</p><div class="brand-modal-form-group"><label for="new_password">Nueva Contraseña <small>(1 Mayús. y 1 Especial como #, $, @)</small></label><input type="password" id="new_password" class="form-control form-control-sm" placeholder="Mín. 6 caracteres"></div><div class="brand-modal-form-group"><label for="confirm_password">Confirmar Contraseña</label><input type="password" id="confirm_password" class="form-control form-control-sm" placeholder="Repite tu nueva contraseña"></div></div>',
        icon: 'warning',
        iconColor: '#34c759',
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
