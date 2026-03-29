<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="/runtime/frontend-config.js?v=20260325a"></script>
  <script src="/public/js/tablet-viewport-scale.js?v=1.2"></script>
  <title><?php echo $title; ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="/public/css/login-brand-unified.css?v=1.2">
</head>
<body class="hold-transition login-page login-brand-page">
<div class="login-box">
  <div class="card card-login">
    <div class="card-header">
      <img src="/public/img/aiaConstruccionMasCerteza.png" alt="AIA Logo" class="brand-logo">
      <h1 class="login-title">Restablecer contraseña</h1>
      <p class="login-subtitle">Ingresa tu correo y te enviaremos un enlace para acceder nuevamente al panel.</p>
    </div>
    <div class="card-body login-card-body">
      <?php if ($message !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType !== '' ? $messageType : 'info'); ?> mb-4">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form action="/admin/password/forgot" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrf_token); ?>">

        <div class="input-group mb-4">
          <input type="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email" value="<?php echo htmlspecialchars((string) $emailValue); ?>" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-aia btn-block">Enviar enlace</button>
      </form>

      <div class="text-center mt-3 mb-3">
        <a href="/admin/login">Volver al inicio de sesión</a>
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
