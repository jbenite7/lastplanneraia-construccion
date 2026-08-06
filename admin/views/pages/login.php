<!DOCTYPE html>
<html lang="es" data-aia-theme="dark" class="aia-theme-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="/public/img/brand/icon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/public/img/brand/icon-32.png">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" sizes="192x192" href="/public/img/brand/icon-192.png">
  <script src="/runtime/frontend-config.js?v=20260325a"></script>
  <script src="/public/js/tablet-viewport-scale.js?v=1.2"></script>
  <title><?php echo $title; ?></title>

  <!-- Tema dark del design system, antes de pintar (sin flash) -->
  <script src="/public/js/modules/aia_ui/theme-bootstrap.js?v=1.0.0"></script>
  <!-- Entrypoint de auth: AdminLTE, Font Awesome, icheck y tokens, todo local y capado -->
  <link rel="stylesheet" href="/admin/public/css/admin-auth-entrypoint.css?v=2.0.0">
  <link rel="stylesheet" href="/public/css/login-brand-unified.css?v=1.2">
</head>
<body class="hold-transition login-page login-brand-page">
<div class="login-box aia-panel aia-panel--elevated">
  <div class="card aia-panel aia-panel--elevated card-login">
    <div class="card-header">
      <img src="/public/img/aiaConstruccionMasCerteza.png" alt="AIA Logo" class="brand-logo">
      <h1 class="login-title">Last Planner AIA</h1>
      <p class="login-subtitle">Inicia sesión en Last Planner AIA para acceder al panel</p>
    </div>
    <div class="card-body login-card-body">
      <?php if (!empty($inactive_notice)): ?>
        <div class="alert alert-warning">Tu cuenta está inactiva. Contacta al administrador.</div>
      <?php endif; ?>

      <?php if (!empty($reset_notice)): ?>
        <div class="alert alert-success">Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.</div>
      <?php endif; ?>

      <form id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <label for="admin-usuario" class="auth-field-label">Usuario</label>
        <div class="input-group mb-3">
          <input type="text" id="admin-usuario" name="usuario" class="form-control" placeholder="Usuario" autocomplete="username" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <label for="admin-password" class="auth-field-label">Contraseña</label>
        <div class="input-group mb-3">
          <input type="password" id="admin-password" name="password" class="form-control" placeholder="Contraseña" autocomplete="current-password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Recuérdame
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-aia btn-block">Ingresar</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <div id="loginMessage" class="mt-3"></div>
      <div class="text-center mb-3">
        <a href="/admin/password/forgot">¿Olvidaste tu contraseña?</a>
      </div>
      <div class="footer-text">
        &copy; 2026 Arquitectos e Ingenieros Asociados<br>
        Construyendo con <strong>+CERTEZA</strong>
      </div>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="/public/vendor/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/public/vendor/admin-lte/plugins/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="/public/vendor/admin-lte/js/adminlte.min.js"></script>

<script>
$(function() {
  $('#loginForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    const $msg = $('#loginMessage');

    $btn.prop('disabled', true).text('Procesando...');
    $msg.empty();

    $.ajax({
      url: '/admin/login',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          window.location.href = response.redirect;
        } else {
          $msg.html('<div class="alert alert-danger">' + response.message + '</div>');
          $btn.prop('disabled', false).text('Ingresar');
        }
      },
      error: function() {
        $msg.html('<div class="alert alert-danger">Error de comunicación con el servidor</div>');
        $btn.prop('disabled', false).text('Ingresar');
      }
    });
  });
});
</script>
</body>
</html>
