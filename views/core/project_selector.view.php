<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/public/js/core/SessionTimeoutManager.js?v=20260328a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Seleccionar Proyecto - Last Planner AIA</title>
    <!-- AdminLTE & Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/tokens.css?v=<?= filemtime(__DIR__ . '/../../public/css/tokens.css') ?>">
    <link rel="stylesheet" href="/css/aia-design-system.css?v=20260708radius1">
    <link rel="stylesheet" href="/css/project-selector.css?v=20260708c">
</head>
<body class="hold-transition layout-top-nav project-selector-page aia-shell">
<div class="wrapper">

  <!-- Navbar -->
  <?php echo \App\View\Components\NavbarComponent::render('proyectos'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper project-selector-shell">
    <div class="content-header">
      <div class="container">
        <div class="row mb-2 align-items-center">
          <div class="col-12 col-md-5 mb-2 mb-md-0">
            <h1 class="m-0"> Tus Proyectos</h1>
          </div>
          <div class="col-12 col-md-7">
              <?php if (\App\View\Components\BiAccessComponent::canAccessAny()): ?>
              <div class="mb-2 text-md-right">
                  <a href="<?php echo htmlspecialchars(\App\View\Components\BiAccessComponent::globalUrl(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-success" data-bi-access-link="control-tower">
                      <i class="fas fa-chart-line mr-1" aria-hidden="true"></i> Control Tower
                  </a>
              </div>
              <?php endif; ?>
              <div class="input-group">
                  <label for="projectSearch" class="sr-only">Buscar proyecto</label>
                  <input type="text" id="projectSearch" class="form-control" placeholder="Buscar proyecto...">
                  <div class="input-group-append">
                      <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                  </div>
              </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row" id="projectGrid">
            <?php foreach ($proyectos as $proyecto): ?>
            <div class="col-lg-4 col-md-6 mb-4 project-item" data-name="<?php echo strtolower($proyecto['Proyecto_Proceso']); ?>">
                <div class="card project-card shadow-sm">
                    <div class="card-header-project d-flex justify-content-between align-items-start">
                        <h5 class="project-title" title="<?php echo htmlspecialchars($proyecto['Proyecto_Proceso']); ?>">
                            <?php echo htmlspecialchars($proyecto['Proyecto_Proceso']); ?>
                        </h5>
                        <div class="d-flex align-items-center">
                            <?php if (($proyecto['Area'] ?? 'Construccion') === 'Pre-Construccion'): ?>
                            <span class="badge badge-warning badge-status project-badge-domain mr-2">
                                <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
                            </span>
                            <?php elseif (($proyecto['Area'] ?? 'Construccion') === 'Construccion'): ?>
                            <span class="badge badge-info badge-status project-badge-domain mr-2">
                                <i class="fas fa-hard-hat mr-1"></i>Construcción
                            </span>
                            <?php endif; ?>
                            <span class="badge badge-success badge-status"><?php echo $proyecto['Activo'] == 1 ? 'Active' : 'Inactive'; ?></span>
                        </div>
                    </div>

                    <div class="card-body-project">
                        <div class="meta-row">
                            <i class="fas fa-hard-hat"></i>
                            <span>Rol: <b><?php echo htmlspecialchars($proyecto['rol_nombre'] ?? $proyecto['permiso']); ?></b></span>
                        </div>

                        <div class="mt-3 d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-xs text-muted">Progreso Estimado</span>
                                <span class="text-xs font-weight-bold"><?php echo $proyecto['progreso']; ?>%</span>
                            </div>
                            <div class="progress progress-xs">
                                <div class="progress-bar bg-success" role="progressbar" data-progress="<?php echo $proyecto['progreso']; ?>" aria-valuenow="<?php echo $proyecto['progreso']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <form action="/proyecto/seleccionar" method="POST" class="mt-4">
                            <input type="hidden" name="proyecto" value="<?php echo htmlspecialchars($proyecto['Proyecto_Proceso']); ?>">
                            <button type="submit" class="btn btn-enter btn-block">
                                Ingresar al Proyecto <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($proyectos)): ?>
                <div class="col-12 text-center py-5">
                    <img src="/img/empty_state.svg" alt="No projects" class="project-empty-image">
                    <h4 class="text-muted">No tienes proyectos asignados</h4>
                    <p class="text-muted">Contacta al administrador para solicitar acceso.</p>
                </div>
            <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Last Planner System
    </div>
    <strong>Copyright &copy; 2026 <a href="#">AIA</a>.</strong> All rights reserved.
  </footer>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="/public/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>

<script>
    $(document).ready(function(){
        $(".progress-bar[data-progress]").each(function() {
            this.style.width = ($(this).data("progress") || 0) + "%";
        });

        // Simple client-side search
        $("#projectSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#projectGrid .project-item").filter(function() {
                $(this).toggle($(this).data("name").indexOf(value) > -1)
            });
        });
    });
</script>
</body>
</html>
