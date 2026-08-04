<?php
use Admin\Core\RoleManager;

?>

<div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
  <div class="card-header">
    <h3 class="card-title">Listado de Usuarios</h3>
    <div class="card-tools d-flex align-items-center">
      <div class="custom-control custom-switch mr-3">
        <input type="checkbox" class="custom-control-input" id="toggleInactiveUsers">
        <label class="custom-control-label" for="toggleInactiveUsers">Mostrar inactivos</label>
      </div>
      <div class="custom-control custom-switch mr-3">
        <input type="checkbox" class="custom-control-input" id="toggleUsersWithoutProjects">
        <label class="custom-control-label" for="toggleUsersWithoutProjects">Mostrar sin proyectos</label>
      </div>
      <a href="/admin/usuarios/crear" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Nuevo Usuario
      </a>
    </div>
  </div>
  <!-- /.card-header -->
  <div class="card-body p-0 admin-table-scroll aia-grid-shell">
    <table id="usersTable" class="table table-bordered table-striped table-sm admin-table-dense">
      <thead>
        <tr>
          <th class="admin-col-3xs">ID</th>
          <th>Nombre</th>
          <th class="admin-col-xs">Usuario</th>
          <th class="admin-col-sm">Email</th>
           <th>Cargo</th>
           <th class="admin-col-md">Rol Principal</th>
           <th class="admin-col-sm text-center">Estado</th>
           <th class="admin-col-2xs text-center">Proyectos</th>
           <th class="admin-col-2xs text-center">Acciones</th>
         </tr>
       </thead>
       <tbody>
         <?php foreach ($users as $user): ?>
        <?php $isActive = (int) ($user['activo'] ?? 1) === 1; ?>
        <tr data-active="<?php echo $isActive ? 1 : 0; ?>" data-has-projects="<?php echo ((int) ($user['projects_count'] ?? 0) > 0) ? 1 : 0; ?>" class="<?php echo $isActive ? '' : 'user-row-inactive'; ?>">
          <td class="text-center"><?php echo $user['id']; ?></td>
          <td><?php echo htmlspecialchars($user['nombre']); ?></td>
          <td class="text-break"><?php echo htmlspecialchars($user['usuario']); ?></td>
          <td class="text-break"><?php echo htmlspecialchars($user['email']); ?></td>
          <td><?php echo htmlspecialchars($user['cargo']); ?></td>
          <td class="text-center">
            <?php
              $roleCode = strtoupper((string) ($user['permiso'] ?? 'V'));
             if ($roleCode === 'P') {
                 $roleCode = 'D';
             } elseif ($roleCode === 'U') {
                 $roleCode = 'V';
             }
             $roleColor = RoleManager::getRoleColor($roleCode);
             $roleName = RoleManager::getRoleName($roleCode);
             ?>
            <span class="badge bg-<?php echo htmlspecialchars($roleColor); ?>" title="<?php echo htmlspecialchars($roleCode); ?>">
              <?php echo htmlspecialchars($roleCode . ' - ' . $roleName); ?>
            </span>
          </td>
          <td class="text-center user-status-cell">
            <div class="custom-control custom-switch d-inline-block">
              <input type="checkbox"
                     class="custom-control-input user-active-toggle"
                     id="user-active-<?php echo $user['id']; ?>"
                     aria-label="Usuario activo — <?php echo htmlspecialchars($user['nombre']); ?>"
                     data-id="<?php echo $user['id']; ?>"
                     <?php echo $isActive ? 'checked' : ''; ?>>
              <label class="custom-control-label" for="user-active-<?php echo $user['id']; ?>"></label>
            </div>
            <div class="mt-1">
              <span class="badge user-status-badge <?php echo $isActive ? 'badge-success' : 'badge-secondary'; ?>">
                <?php echo $isActive ? 'Activo' : 'Inactivo'; ?>
              </span>
            </div>
            <?php if ((int) ($user['force_password_change'] ?? 0) === 1): ?>
              <div class="mt-1">
                <span class="badge badge-warning">Clave pendiente</span>
              </div>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge badge-secondary"><?php echo (int) ($user['projects_count'] ?? 0); ?></span>
            <?php if ((int) ($user['projects_count'] ?? 0) === 0): ?>
              <div class="mt-1">
                <span class="badge badge-light border">Sin proyectos</span>
              </div>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <div class="btn-group">
              <a href="/admin/usuarios/editar?id=<?php echo $user['id']; ?>" class="btn btn-info btn-xs" title="Editar">
                <i class="fas fa-edit"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- /.card-body -->
</div>


<script>
$(function () {
  var table;

  table = $("#usersTable").DataTable({
    "responsive": false, 
    "paging": false,
    "lengthChange": false, 
    "autoWidth": false,
    "ordering": true,
    "info": false,
    "columnDefs": [
      { "type": "num", "targets": 0, "width": "6%" },
      { "targets": 1, "width": "18%" },
      { "targets": 2, "width": "14%" },
      { "targets": 3, "width": "20%" },
      { "targets": 4, "width": "16%" },
      { "targets": 5, "width": "16%" },
      { "targets": 6, "width": "12%", "orderable": false },
      { "targets": 7, "width": "6%" },
      { "targets": 8, "width": "6%", "orderable": false }
    ],
    "language": {
        "processing": "Procesando...",
        "search": "Buscar:",
        "lengthMenu": "Mostrar _MENU_ registros",
        "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
        "infoFiltered": "(filtrado de un total de _MAX_ registros)",
        "infoPostFix": "",
        "loadingRecords": "Cargando...",
        "zeroRecords": "No se encontraron resultados",
        "emptyTable": "Ningún dato disponible en esta tabla",
        "paginate": {
            "first": "Primero",
            "previous": "Anterior",
            "next": "Siguiente",
            "last": "Último"
        }
    },
    "buttons": ["excel"]
  });

  table.buttons().container().appendTo('#usersTable_wrapper .col-md-6:eq(0)');

  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (!table || settings.nTable !== table.table().node()) {
      return true;
    }

    var rowNode = table.row(dataIndex).node();
    if (!rowNode) {
      return true;
    }

    if ($('#toggleInactiveUsers').is(':checked')) {
      if ($('#toggleUsersWithoutProjects').is(':checked')) {
        return true;
      }
    } else if (Number($(rowNode).attr('data-active') || 0) !== 1) {
      return false;
    }

    if (!$('#toggleUsersWithoutProjects').is(':checked') && Number($(rowNode).attr('data-has-projects') || 0) !== 1) {
      return false;
    }

    return true;
  });

  function updateUserStatusRow($row, active) {
    var $badge = $row.find('.user-status-badge');
    $row.attr('data-active', active ? '1' : '0');
    $row.toggleClass('user-row-inactive', !active);
    $badge
      .text(active ? 'Activo' : 'Inactivo')
      .toggleClass('badge-success', active)
      .toggleClass('badge-secondary', !active);
  }

  $('#toggleInactiveUsers').on('change', function() {
    table.draw(false);
  });

  $('#toggleUsersWithoutProjects').on('change', function() {
    table.draw(false);
  });

  table.draw(false);

  $(document).on('change', '.user-active-toggle', function() {
    var checkbox = $(this);
    var row = checkbox.closest('tr');
    var userId = checkbox.data('id');
    var active = checkbox.is(':checked') ? 1 : 0;
    var previousChecked = !checkbox.is(':checked');

    var performToggle = function() {
      checkbox.prop('disabled', true);

      $.ajax({
        url: '/admin/usuarios/toggle-active',
        method: 'POST',
        dataType: 'json',
        data: {
          id: userId,
          value: active,
          csrf_token: '<?php echo \Admin\Core\Security::generateCsrfToken(); ?>'
        },
        success: function(response) {
          if (!response.success) {
            checkbox.prop('checked', previousChecked);
            AIA.Notice.error(response.message, 'Error');
            return;
          }

          updateUserStatusRow(row, !!response.active);
          AIA.Notice.success(response.message, 'Estado actualizado');
          table.draw(false);
        },
        error: function() {
          checkbox.prop('checked', previousChecked);
          AIA.Notice.error('Error de comunicación con el servidor', 'Error');
        },
        complete: function() {
          checkbox.prop('disabled', false);
        }
      });
    };

    if (active === 0) {
      AIA.Notice.dialog({
        title: 'Inactivar usuario',
        text: 'El usuario dejará de poder iniciar sesión hasta que vuelva a activarse.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, inactivar',
        cancelButtonText: 'Cancelar'
      }).then(function(result) {
        if (result.isConfirmed) {
          performToggle();
          return;
        }

        checkbox.prop('checked', previousChecked);
      });

      return;
    }

    performToggle();
  });
});
</script>
