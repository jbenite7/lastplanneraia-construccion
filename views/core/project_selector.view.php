<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/runtime/frontend-config.js?v=20260325a"></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2"></script>
    <title>Seleccionar Proyecto - Last Planner AIA</title>
    <!-- AdminLTE & Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Roboto', sans-serif;
        }
        .project-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
        }
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .card-header-project {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px;
        }
        .project-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .badge-status {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .card-body-project {
            padding: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        .meta-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .meta-row i {
            width: 20px;
            color: #adb5bd;
            text-align: center;
            margin-right: 8px;
        }
        .progress-xs {
            height: 6px;
            border-radius: 3px;
        }
        .btn-enter {
            background-color: #19692c; /* WCAG AA Contrast Improvement */
            color: white;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-enter:hover {
            background-color: #124d20;
            color: white;
            box-shadow: 0 4px 6px rgba(25, 105, 44, 0.3);
        }
        .navbar-brand-aia {
            font-weight: 700;
            color: #333;
        }
        .navbar-light {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <!-- Navbar -->
  <?php echo \App\View\Components\NavbarComponent::render('proyectos'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container">
        <div class="row mb-2 align-items-center">
          <div class="col-12 col-md-5 mb-2 mb-md-0">
            <h1 class="m-0"> Tus Proyectos</h1>
          </div>
          <div class="col-12 col-md-7">
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
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row" id="projectGrid">
            <?php foreach($proyectos as $proyecto): ?>
            <div class="col-lg-4 col-md-6 mb-4 project-item" data-name="<?php echo strtolower($proyecto['Proyecto_Proceso']); ?>">
                <div class="card project-card shadow-sm">
                    <div class="card-header-project d-flex justify-content-between align-items-center">
                        <h5 class="project-title" title="<?php echo htmlspecialchars($proyecto['Proyecto_Proceso']); ?>">
                            <?php echo htmlspecialchars($proyecto['Proyecto_Proceso']); ?>
                        </h5>
                        <span class="badge badge-success badge-status"><?php echo $proyecto['Activo'] == 1 ? 'Active' : 'Inactive'; ?></span>
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
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $proyecto['progreso']; ?>%" aria-valuenow="<?php echo $proyecto['progreso']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
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
            
            <?php if(empty($proyectos)): ?>
                <div class="col-12 text-center py-5">
                    <img src="/img/empty_state.svg" alt="No projects" style="max-height: 150px; opacity: 0.5; margin-bottom: 20px;">
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

<script>
    $(document).ready(function(){
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
