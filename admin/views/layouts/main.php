<!DOCTYPE html>
<html lang="es" data-aia-theme="dark" class="aia-theme-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="/public/img/brand/icon-16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/public/img/brand/icon-32.png">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" sizes="192x192" href="/public/img/brand/icon-192.png">
  <script src="/public/js/tablet-viewport-scale.js?v=1.2"></script>
  <title><?php echo $title ?? 'Panel Administrativo - AIA'; ?></title>

  <!-- Tema dark del design system, antes de pintar (sin flash) -->
  <script src="/public/js/modules/aia_ui/theme-bootstrap.js?v=1.0.0"></script>
  <!--
    Entrypoint unico del panel: importa AdminLTE, Font Awesome, DataTables,
    Toastr y SweetAlert2 desde /public/vendor con `layer(vendor)`, mas los
    tokens canonicos y el adaptador dark. Cero peticiones a dominios externos.
  -->
  <link rel="stylesheet" href="/admin/public/css/admin-entrypoint.css?v=2.0.0">

  <!-- jQuery 3.6.0 -->
  <script src="/public/vendor/jquery.min.js"></script>
  <!-- Bootstrap 4.6.1 (bundle con Popper) -->
  <script src="/public/vendor/admin-lte/plugins/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 + AIA Notice -->
  <script src="/public/vendor/sweetalert2.all.min.js?v=11.4.24"></script>
  <script src="/runtime/frontend-config.js?v=20260325a"></script>
  <script src="/public/js/core/AiaAlertInterceptor.js?v=20260324a"></script>
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Alternar menú lateral" title="Alternar menú lateral"><i class="fas fa-bars" aria-hidden="true"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="/admin/" class="nav-link">Inicio</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <?php if (isset($_SESSION['admin_user']['nombre'])): ?>
        <?php
            $adminName = $_SESSION['admin_user']['nombre'];
          $adminRole = $_SESSION['admin_user']['permiso'] ?? '';
          $adminRoleName = class_exists('\App\Security\RbacCatalog') ? \App\Security\RbacCatalog::getRoleName($adminRole) : 'Admin';
          ?>
        <li class="nav-item d-flex align-items-center mr-3">
          <span class="text-muted admin-user-chip">
            <?php echo htmlspecialchars($adminName); ?>
            <br><small class="admin-user-role"><?php echo htmlspecialchars($adminRoleName); ?></small>
          </span>
        </li>
      <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link text-danger" href="/admin/logout" role="button">
          <i class="fas fa-sign-out-alt"></i> Salir
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/admin/dashboard" class="brand-link">
      <img src="/public/img/brand/icon.svg" alt="" aria-hidden="true" class="admin-brand-mark">
      <span class="brand-text font-weight-light">AIA Panel</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="/admin/" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-header">GESTIÓN</li>
          <li class="nav-item">
            <a href="/admin/proyectos" class="nav-link">
              <i class="nav-icon fas fa-project-diagram"></i>
              <p>Proyectos</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/usuarios" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Usuarios</p>
            </a>
          </li>
          <li class="nav-header">SISTEMA</li>
          <li class="nav-item">
            <a href="/admin/matching/config" class="nav-link">
              <i class="nav-icon fas fa-sliders-h"></i>
              <p>Matching Config</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/matching/family-catalog" class="nav-link">
              <i class="nav-icon fas fa-layer-group"></i>
              <p>Catálogo Familias</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="/admin/pdc/limpieza" class="nav-link">
              <i class="nav-icon fas fa-broom"></i>
              <p>Limpieza Plan de Compras</p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <!-- <main>, no <div>: las 8 rutas de admin no declaraban NINGUN landmark principal
       (grep de "<main" y role="main" sobre admin/views/ daba cero, frente a 19 archivos
       en views/). El informe de cierre de la campana afirmaba «28/28 con <main> y h1
       real»; medido el 2026-08-07, la app iba 23/23 pero admin 0/8. Hallazgo B-4.
       Se cambia solo la etiqueta: AdminLTE engancha por la CLASE content-wrapper, y no
       existe ningun selector `div.content-wrapper` en admin/ ni en public/, asi que no
       se mueve un pixel. Al vivir en el layout compartido, cubre las 8 rutas. -->
  <main class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><?php echo $pageTitle ?? 'Panel de Control'; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
              <li class="breadcrumb-item active"><?php echo $breadcrumb ?? 'Inicio'; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <?php echo $content; ?>
      </div>
    </section>
    <!-- /.content -->
  </main>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Versión</b> 1.0.0
    </div>
    <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#">AIA</a>.</strong> Todos los derechos reservados.
  </footer>
</div>
<!-- ./wrapper -->

<!-- Scripts Finales (Plugins) — todos locales, version fija, cero CDN -->
<!-- DataTables 1.10.21 + responsive 2.2.7 + buttons 1.7.0 -->
<script src="/public/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/public/vendor/datatables/js/dataTables.bootstrap4.min.js"></script>
<script src="/public/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="/public/vendor/datatables/js/dataTables.buttons.min.js"></script>
<script src="/public/vendor/datatables/js/buttons.bootstrap4.min.js"></script>
<script src="/public/vendor/jszip/jszip.min.js"></script>
<script src="/public/vendor/pdfmake/pdfmake.min.js"></script>
<script src="/public/vendor/pdfmake/vfs_fonts.js"></script>
<script src="/public/vendor/datatables/js/buttons.html5.min.js"></script>
<script src="/public/vendor/datatables/js/buttons.print.min.js"></script>
<script src="/public/vendor/datatables/js/buttons.colVis.min.js"></script>
<!-- Toastr 2.1.3 -->
<script src="/public/vendor/toastr.min.js"></script>
<!-- AdminLTE 3.2.0 -->
<script src="/public/vendor/admin-lte/js/adminlte.min.js"></script>

<script>
$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '<?php echo \Admin\Core\Security::generateCsrfToken(); ?>'
        }
    });

    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };
});
</script>
</body>
</html>
