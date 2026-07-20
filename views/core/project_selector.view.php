<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/runtime/frontend-config.js?v=20260325a" defer></script>
    <script src="/public/js/core/SessionTimeoutManager.js?v=20260328a" defer></script>
    <script src="/js/tablet-viewport-scale.js?v=1.2" defer></script>
    <title>Seleccionar Proyecto - Last Planner AIA</title>
    <!-- Local vendor adapters; canonical AIA entrypoint owns tokens and fonts. -->
    <link rel="stylesheet" href="/vendor/font-awesome/css/all.css">
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/css/tokens.css?v=<?= filemtime(__DIR__ . '/../../public/css/tokens.css') ?>">
    <link rel="stylesheet" href="/css/aia-design-system.css?v=20260719search1">
    <link rel="stylesheet" href="/css/project-selector.css?v=20260719surface1">
</head>
<body class="hold-transition layout-top-nav project-selector-page aia-shell">
<div class="wrapper">

  <!-- Navbar -->
  <?php echo \App\View\Components\NavbarComponent::render('proyectos'); ?>

  <!-- Content Wrapper -->
  <main id="main-content" class="content-wrapper project-selector-shell">
    <div class="content-header">
      <div class="container">
        <div class="row mb-2 align-items-center">
          <div class="col-12 col-md-5 mb-2 mb-md-0">
            <h1 class="m-0 aia-title">Tus proyectos</h1>
          </div>
          <div class="col-12 col-md-7">
              <?php if (\App\View\Components\BiAccessComponent::canAccessAny()): ?>
              <div class="mb-2 text-md-right">
                  <a href="<?php echo htmlspecialchars(\App\View\Components\BiAccessComponent::globalUrl(), ENT_QUOTES, 'UTF-8'); ?>" class="aia-btn aia-btn--secondary" data-bi-access-link="control-tower">
                      <i class="fas fa-chart-line mr-1" aria-hidden="true"></i> Control Tower
                  </a>
              </div>
              <?php endif; ?>
              <div class="input-group" role="search">
                  <label for="projectSearch" class="sr-only">Buscar proyecto</label>
                  <input type="search" id="projectSearch" class="form-control aia-input" placeholder="Buscar proyecto..." autocomplete="off" aria-controls="projectGrid" aria-describedby="projectSearchStatus">
                  <div class="input-group-append">
                      <span class="input-group-text" aria-hidden="true"><i class="fas fa-search"></i></span>
                  </div>
              </div>
              <p id="projectSearchStatus" class="aia-visually-hidden" role="status" aria-live="polite"></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show aia-alert" role="alert">
                <?php echo htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row" id="projectGrid" role="list" aria-describedby="projectSearchStatus">
            <?php foreach ($proyectos as $proyecto): ?>
            <?php $projectTitle = (string) $proyecto['Proyecto_Proceso']; ?>
            <?php $projectId = (int) ($proyecto['ID'] ?? $proyecto['id'] ?? 0); ?>
            <div class="col-lg-4 col-md-6 mb-4 project-item" role="listitem" data-name="<?php echo htmlspecialchars(strtolower($projectTitle), ENT_QUOTES, 'UTF-8'); ?>">
                <article class="card project-card aia-card" aria-labelledby="project-title-<?php echo $projectId; ?>">
                    <div class="card-header-project d-flex justify-content-between align-items-start">
                        <h2 id="project-title-<?php echo $projectId; ?>" class="project-title" title="<?php echo htmlspecialchars($projectTitle, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($projectTitle, ENT_QUOTES, 'UTF-8'); ?>
                        </h2>
                        <div class="d-flex align-items-center">
                            <?php if (($proyecto['Area'] ?? 'Construccion') === 'Pre-Construccion'): ?>
                            <span class="aia-chip badge-status project-badge-domain project-badge-domain--preconstruction mr-2">
                                <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
                            </span>
                            <?php elseif (($proyecto['Area'] ?? 'Construccion') === 'Construccion'): ?>
                            <span class="aia-chip badge-status project-badge-domain project-badge-domain--construction mr-2">
                                <i class="fas fa-hard-hat mr-1"></i>Construcción
                            </span>
                            <?php endif; ?>
                            <span class="aia-chip badge-status aia-chip--success"><?php echo $proyecto['Activo'] == 1 ? 'Activo' : 'Inactivo'; ?></span>
                        </div>
                    </div>

                    <div class="card-body-project">
                        <div class="meta-row">
                            <i class="fas fa-hard-hat"></i>
                            <span>Rol: <b><?php echo htmlspecialchars($proyecto['rol_nombre'] ?? $proyecto['permiso']); ?></b></span>
                        </div>

                        <form action="/proyecto/seleccionar" method="POST">
                            <input type="hidden" name="proyecto" value="<?php echo htmlspecialchars($projectTitle, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-enter aia-btn btn-block">
                                Ingresar al proyecto <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>

            <?php if (empty($proyectos)): ?>
                <div class="col-12 text-center py-5 aia-empty">
                    <img src="/img/empty_state.svg" alt="" class="project-empty-image" aria-hidden="true">
                    <h2 class="text-muted">No tienes proyectos asignados</h2>
                    <p class="text-muted">Contacta al administrador para solicitar acceso.</p>
                </div>
            <?php endif; ?>

        </div>
        <div id="projectNoResults" class="aia-empty" hidden>
            <h2 class="aia-title">No encontramos proyectos</h2>
            <p>Prueba con otro término de búsqueda.</p>
        </div>
      </div>
    </div>
  </main>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline">
      Last Planner System
    </div>
    <strong>Copyright &copy; 2026 AIA.</strong> Todos los derechos reservados.
  </footer>
</div>

<!-- Local vendor adapters for the migrated surface. -->
<script src="/vendor/jquery.min.js" defer></script>
<script src="/vendor/bootstrap/bootstrap.min.js" defer></script>
<script src="/public/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>" defer></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const search = document.getElementById('projectSearch');
        const items = Array.from(document.querySelectorAll('#projectGrid .project-item'));
        const status = document.getElementById('projectSearchStatus');
        const noResults = document.getElementById('projectNoResults');

        let scheduledFrame = null;
        const updateResults = () => {
            const value = search.value.trim().toLocaleLowerCase();
            let visible = 0;

            items.forEach((item) => {
                const matches = item.dataset.name.includes(value);
                item.hidden = !matches;
                if (matches) visible += 1;
            });

            noResults.hidden = visible !== 0 || value === '';
            status.textContent = value === ''
                ? `${items.length} ${items.length === 1 ? 'proyecto disponible' : 'proyectos disponibles'}`
                : `${visible} ${visible === 1 ? 'proyecto encontrado' : 'proyectos encontrados'}`;
        };

        const scheduleResults = () => {
            if (scheduledFrame !== null) cancelAnimationFrame(scheduledFrame);
            scheduledFrame = requestAnimationFrame(() => {
                scheduledFrame = null;
                updateResults();
            });
        };

        search?.addEventListener('input', scheduleResults);
        updateResults();

        document.querySelectorAll('form[action="/proyecto/seleccionar"]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            });
        });
    });
</script>
</body>
</html>
