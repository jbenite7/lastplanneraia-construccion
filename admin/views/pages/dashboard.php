

<div class="row">
  <div class="col-lg-3 col-6">
    <!-- small box: Proyectos -->
    <div class="small-box bg-info">
      <div class="inner">
        <h3>
          <?php echo $stats['active_projects_count']; ?> / <?php echo $stats['total_projects']; ?>
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" data-html="true" 
             title="<div class='text-left'><b>Proyectos Activos Actualmente:</b><ul class='mb-0 pl-3'><?php foreach ($stats['active_projects_list'] as $proj) {
                 echo '<li>'.htmlspecialchars($proj).'</li>';
             } ?></ul><hr class='border-light my-1'><b>¿Cómo se mide?</b> Filtra proyectos con estado 'Activo' en la tabla maestra.</div>"></i>
        </h3>
        <p>Proyectos Activos</p>
      </div>
      <div class="icon">
        <i class="fas fa-project-diagram"></i>
      </div>
      <a href="proyectos" class="small-box-footer">Gestionar <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box: DB Size -->
    <div class="small-box bg-success">
      <div class="inner">
        <h3>
          <?php echo $stats['db_size']; ?><sup class="text-sm">MB</sup>
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" data-html="true" 
             title="<b>¿Qué es?</b> Espacio físico de la base de datos.<br><b>¿Cómo se mide?</b> Suma el tamaño de datos e índices de todas las tablas en MySQL."></i>
        </h3>
        <p>Tamaño de Base de Datos</p>
      </div>
      <div class="icon">
        <i class="fas fa-database"></i>
      </div>
      <div class="small-box-footer"><?php echo $stats['total_tables']; ?> tablas activas</div>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box: Usuarios -->
    <div class="small-box bg-warning">
      <div class="inner">
        <h3>
          <?php echo $stats['total_users']; ?>
          <i class="fas fa-info-circle info-icon opacity-50" data-toggle="tooltip" data-html="true" 
             title="<b>¿Qué es?</b> Cuentas creadas.<br><b>¿Cómo se mide?</b> Conteo directo en 'general_usuarios'."></i>
        </h3>
        <p>Usuarios en el Sistema</p>
      </div>
      <div class="icon">
        <i class="fas fa-users"></i>
      </div>
      <a href="usuarios" class="small-box-footer">Ver todos <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box: Errores -->
    <div class="small-box bg-danger">
      <div class="inner">
        <h3>
          <?php echo $stats['log_errors']; ?>
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" data-html="true" 
             title="<b>¿Qué es?</b> Fallos críticos hoy.<br><b>¿Cómo se mide?</b> Escanea el log de errores de PHP para la fecha actual."></i>
        </h3>
        <p>Errores hoy (Log)</p>
      </div>
      <div class="icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <a href="#logs" class="small-box-footer">Ver detalles <i class="fas fa-arrow-circle-bottom"></i></a>
    </div>
  </div>
  <!-- ./col -->
</div>

<div class="row">
  <div class="col-lg-3 col-6">
    <!-- small box: Password Change Status -->
    <div class="small-box bg-maroon">
      <div class="inner">
        <h3>
          <?php echo $stats['password_stats']['completed']; ?> / <?php echo $stats['password_stats']['total']; ?>
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" data-html="true" 
             title="<b>¿Qué es?</b> Usuarios que han cumplido con el cambio de clave obligatorio.<br><b>Pendientes:</b> <?php echo $stats['password_stats']['pending']; ?>"></i>
        </h3>
        <p>Cambios de Clave Realizados</p>
      </div>
      <div class="icon">
        <i class="fas fa-key"></i>
      </div>
      <div class="small-box-footer">
        <?php 
          $percent = $stats['password_stats']['total'] > 0 
            ? round(($stats['password_stats']['completed'] / $stats['password_stats']['total']) * 100) 
            : 100;
          echo "$percent% completado";
        ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- COLUMNA IZQUIERDA (Info Servidor e Integridad) -->
  <div class="col-md-4">
    <div class="card card-outline card-primary">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-server"></i> Servidor
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Límites configurados en el servidor que afectan la carga de archivos y rendimiento."></i>
        </h3>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <tbody>
            <tr>
              <td><strong>Upload Max</strong></td>
              <td><span class="badge badge-info"><?php echo $stats['php_limits']['upload_max']; ?></span></td>
            </tr>
            <tr>
              <td><strong>Post Max Size</strong></td>
              <td><span class="badge badge-info"><?php echo $stats['php_limits']['post_max']; ?></span></td>
            </tr>
            <tr>
              <td><strong>Memoria PHP</strong></td>
              <td><span class="badge badge-info"><?php echo $stats['php_limits']['memory_limit']; ?></span></td>
            </tr>
            <tr>
              <td><strong>Max Execution</strong></td>
              <td><span class="badge badge-info"><?php echo $stats['php_limits']['max_execution']; ?></span></td>
            </tr>
            <tr>
              <td><strong>Último Backup</strong></td>
              <td><span class="badge badge-success"><?php echo $stats['backup_status']['last_backup']; ?></span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="card-footer text-center">
        <button class="btn btn-xs btn-primary" id="btnFullBackup">
          <i class="fas fa-download mr-1"></i> Respaldo Completo
        </button>
      </div>
    </div>

    <div class="card card-outline card-info">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-cog"></i> Configuración y Seguridad
        </h3>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <strong>Console Logs Frontend</strong>
            <div class="text-muted small">Controla <code>console.log</code> en toda la app.</div>
          </div>
          <div class="custom-control custom-switch">
            <input type="checkbox"
                   class="custom-control-input"
                   id="consoleLogsGlobalToggle"
                   <?php echo $stats['console_logs_enabled'] ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="consoleLogsGlobalToggle"></label>
          </div>
        </div>

        <hr class="my-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <strong>Consolidar Reportes</strong>
            <div class="text-muted small">Ejecuta Curva S, General, Restricciones, PDC, Subcontratistas y CIC.</div>
          </div>
          <button class="btn btn-sm btn-primary" id="btnRunReportes">
            <i class="fas fa-chart-bar mr-1"></i> Consolidar
          </button>
        </div>

        <hr class="my-3">

        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>Seguridad de Claves</strong>
            <div class="text-muted small">Obliga a todos los usuarios a cambiar su contraseña.</div>
          </div>
          <button class="btn btn-sm btn-danger" id="btnForcePasswordChange">
            <i class="fas fa-sync-alt mr-1"></i> Forzar Cambio
          </button>
        </div>
      </div>
    </div>

    <!-- Integrity Alerts -->
    <div class="card card-outline card-warning">
      <div class="card-header">
        <h3 class="card-title text-warning">
          <i class="fas fa-tools"></i> Integridad
          <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Verifica que cada proyecto tenga sus tablas estructurales."></i>
        </h3>
      </div>
      <div class="card-body p-0 dashboard-widget__body">
        <?php if (!empty($stats['integrity_issues'])): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($stats['integrity_issues'] as $issue): ?>
            <li class="list-group-item integrity-item cursor-pointer" 
                data-project="<?php echo htmlspecialchars($issue['nombre']); ?>" 
                data-missing="<?php echo htmlspecialchars(implode(', ', $issue['missing'])); ?>">
              <small><strong><?php echo $issue['nombre']; ?></strong></small><br>
              <small class="text-danger">Tablas faltantes: <?php echo count($issue['missing']); ?></small>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="p-3 text-center text-muted">
            <i class="fas fa-check-shield text-success mb-2"></i><br>
            <small>Proyectos íntegros.</small>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Orphan Tables -->
    <div class="card card-outline card-secondary">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-trash-alt"></i> Tablas Huérfanas (<?php echo count($stats['orphan_tables']); ?>)
        </h3>
      </div>
      <div class="card-body p-0 dashboard-widget__body">
        <?php if (!empty($stats['orphan_tables'])): ?>
          <div class="p-2">
              <?php foreach ($stats['orphan_tables'] as $table): ?>
                <span class="badge badge-light border mb-1"><?php echo $table; ?></span>
              <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-3 text-center text-muted">
            <i class="fas fa-check-circle text-success mb-2"></i><br>
            <small>BD limpia.</small>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-footer p-1 text-center">
        <?php if (!empty($stats['orphan_tables'])): ?>
          <button class="btn btn-xs btn-outline-danger mb-1" id="btnCleanupOrphans">Limpiar BD</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- COLUMNA DERECHA (Logs) -->
  <div class="col-md-8">
    <!-- Auditoría -->
    <div class="card card-outline card-success" id="audit-log">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-user-shield"></i> Auditoría de Acciones
        </h3>
      </div>
      <div class="card-body p-0 dashboard-widget__body">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Módulo</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stats['audit_logs'])): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-3">Sin acciones registradas.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($stats['audit_logs'] as $audit): ?>
                  <tr>
                    <td class="small"><?php echo date('d/m/y H:i', strtotime($audit['fecha'])); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($audit['usuario']); ?></span></td>
                    <td>
                      <strong><?php echo htmlspecialchars($audit['accion']); ?></strong>
                      <div class="small text-muted"><?php echo htmlspecialchars($audit['descripcion']); ?></div>
                    </td>
                    <td><span class="badge badge-light"><?php echo htmlspecialchars($audit['modulo']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Errores -->
    <div class="card card-outline card-danger" id="system-log">
      <div class="card-header d-flex p-0">
        <h3 class="card-title p-3">
          <i class="fas fa-server"></i> Errores de Sistema
        </h3>
        <ul class="nav nav-pills ml-auto p-2">
          <li class="nav-item"><a class="nav-link active btn-xs py-1 px-2 log-filter" href="#" data-filter="all">Todos</a></li>
          <li class="nav-item"><a class="nav-link btn-xs py-1 px-2 log-filter" href="#" data-filter="error">Errores</a></li>
          <li class="nav-item"><a class="nav-link btn-xs py-1 px-2 log-filter" href="#" data-filter="route">Rutas</a></li>
        </ul>
      </div>
      <div class="card-body p-0 dashboard-widget__body">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <tbody>
              <?php if (empty($stats['recent_errors'])): ?>
                <tr>
                  <td class="text-center text-muted py-3">Sin eventos relevantes hoy.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($stats['recent_errors'] as $error):
                    $isError = (stripos($error, 'error') !== false || stripos($error, 'exception') !== false || stripos($error, 'fatal') !== false);
                    $isRoute = strpos($error, 'Router') !== false;

                    preg_match('/^\[(.*?)\]\s+(.*)$/', $error, $matches);
                    $timestamp = $matches[1] ?? '';
                    $message = $matches[2] ?? $error;

                    $rowClass = $isError ? 'log-row-error' : ($isRoute ? 'log-row-route' : 'log-row-default');
                    $filterTag = $isError ? 'error' : ($isRoute ? 'route' : 'default');
                    $icon = $isError ? 'fas fa-bug text-danger' : ($isRoute ? 'fas fa-route text-primary' : 'fas fa-info-circle text-muted');
                    ?>
                  <tr class="log-row-all <?php echo $rowClass; ?>" data-type="<?php echo $filterTag; ?>">
                    <td class="p-2">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="log-timestamp"><i class="<?php echo $icon; ?> mr-1"></i> <?php echo htmlspecialchars($timestamp); ?></span>
                      </div>
                      <div class="log-message text-break small"><?php echo htmlspecialchars($message); ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateConsoleLogsBadge(enabled) {
        const badge = document.getElementById('consoleLogsStatusBadge');
        if (!badge) {
            return;
        }

        badge.textContent = enabled ? 'Activos' : 'Ocultos';
        badge.classList.toggle('badge-success', enabled);
        badge.classList.toggle('badge-secondary', !enabled);
    }

    // Inicializar tooltips
    $(function () {
      $('[data-toggle="tooltip"]').tooltip();
    });

    const consoleLogsToggle = document.getElementById('consoleLogsGlobalToggle');
    if (consoleLogsToggle) {
        consoleLogsToggle.addEventListener('change', function() {
            const toggle = this;
            const enabled = toggle.checked ? 1 : 0;
            const previousState = !toggle.checked;
            const formData = new FormData();

            formData.append('enabled', String(enabled));
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');

            toggle.disabled = true;

            fetch('/admin/dashboard/toggle-console-logs', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'No se pudo guardar la configuración');
                }

                updateConsoleLogsBadge(!!data.enabled);

                if (window.AIA && typeof window.AIA.applyConsoleLogPolicy === 'function') {
                    window.AIA.applyConsoleLogPolicy(!!data.enabled);
                }

                AIA.Notice.success(data.message);
            })
            .catch(error => {
                toggle.checked = previousState;
                updateConsoleLogsBadge(previousState);
                AIA.Notice.error(error.message || 'No se pudo actualizar el switch global.');
            })
            .finally(() => {
                toggle.disabled = false;
            });
        });

        updateConsoleLogsBadge(consoleLogsToggle.checked);
    }

    // Filtro de Logs
    const filterLinks = document.querySelectorAll('.log-filter');
    const logRows = document.querySelectorAll('.log-row-all');

    filterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.getAttribute('data-filter');

            // Actualizar UI de botones
            filterLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Filtrar filas
            logRows.forEach(row => {
                if (filter === 'all' || row.getAttribute('data-type') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 1. Limpieza de Tablas Huérfanas
    const btnCleanup = document.getElementById('btnCleanupOrphans');
    if (btnCleanup) {
        btnCleanup.addEventListener('click', function() {
            AIA.Notice.confirm('Esta acción eliminará permanentemente las tablas huérfanas detectadas.', '¿Estás seguro?').then((confirmed) => {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo $_SESSION["csrf_token"]; ?>');

                    fetch('/admin/proyectos/limpiar-huerfanas', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            AIA.Notice.success(data.message).then(() => location.reload());
                        } else {
                            AIA.Notice.error(data.message, 'Error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        AIA.Notice.error('Ocurrió un fallo en la conexión.', 'Error');
                    });
                }
            });
        });
    }

    // 2. Detalle de Integridad (Un Clic)
    document.querySelectorAll('.integrity-item').forEach(item => {
        item.addEventListener('click', function() {
            const project = this.getAttribute('data-project');
            const missing = this.getAttribute('data-missing').split(', ').map(t => `<li>${t}</li>`).join('');
            
            AIA.Notice.dialog({
                title: `Tablas faltantes en ${project}`,
                html: `<div class="text-left"><ul class="small">${missing}</ul></div>`,
                icon: 'info',
                confirmButtonText: 'Entendido'
            });
        });
    });

    // 3. Respaldo Completo
    const btnFullBackup = document.getElementById('btnFullBackup');
    if (btnFullBackup) {
        btnFullBackup.addEventListener('click', function() {
            AIA.Notice.dialog({
                title: 'Generar Respaldo Completo',
                text: "Se creará un volcado SQL de toda la base de datos en el servidor.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Generar ahora',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo $_SESSION["csrf_token"]; ?>');
                    return fetch('/admin/proyectos/respaldo-completo', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    AIA.Notice.success(result.value.message).then(() => location.reload());
                } else if (result.isConfirmed) {
                    AIA.Notice.error(result.value.message);
                }
            });
        });
    }

    // 4. Forzar Cambio de Clave
    const btnForcePass = document.getElementById('btnForcePasswordChange');
    if (btnForcePass) {
        btnForcePass.addEventListener('click', function() {
            AIA.Notice.confirm(
                'Esta acción obligará a TODOS los usuarios a cambiar su contraseña en su próximo inicio de sesión.',
                '¿Confirmar Rutina de Seguridad?'
            ).then((confirmed) => {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo $csrf_token; ?>');

                    fetch('/admin/dashboard/forzar-cambio-clave', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            AIA.Notice.success(data.message).then(() => location.reload());
                        } else {
                            AIA.Notice.error(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        AIA.Notice.error('Fallo en la comunicación con el servidor.');
                    });
                }
            });
        }
    });

    // 5. Consolidar Reportes
    const btnRunReportes = document.getElementById('btnRunReportes');
    if (btnRunReportes) {
        btnRunReportes.addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Consolidando...';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');

            fetch('/admin/dashboard/run-reportes', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    AIA.Notice.success(data.notification);
                } else {
                    AIA.Notice.error(data.notification || 'Error al consolidar reportes');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                AIA.Notice.error('Fallo en la comunicación con el servidor.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-chart-bar mr-1"></i> Consolidar';
            });
        });
    }
});
                }
            });
        });
    }
});
</script>
