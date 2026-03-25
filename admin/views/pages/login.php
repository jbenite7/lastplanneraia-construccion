<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="/runtime/frontend-config.js?v=20260325a"></script>
  <script src="/public/js/tablet-viewport-scale.js?v=1.2"></script>
  <title><?php echo $title; ?></title>

  <!-- Google Fonts: Montserrat & Inter -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="/public/css/login-brand-unified.css?v=1.2">
</head>
<body class="hold-transition login-page login-brand-page">
<div class="login-box">
  <div class="card card-login">
    <div class="card-header">
      <img src="/public/img/aiaConstruccionMasCerteza.png" alt="AIA Logo" class="brand-logo">
      <h1 class="login-title">Last Planner AIA</h1>
      <p class="login-subtitle">Inicia sesión en Last Planner AIA para acceder al panel</p>
    </div>
    <div class="card-body login-card-body">
      <form id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="input-group mb-3">
          <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

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
