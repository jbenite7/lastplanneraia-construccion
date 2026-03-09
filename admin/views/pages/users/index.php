<?php
use Admin\Core\RoleManager;
?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Listado de Usuarios</h3>
    <div class="card-tools">
      <a href="/admin/usuarios/crear" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Nuevo Usuario
      </a>
    </div>
  </div>
  <!-- /.card-header -->
  <div class="card-body p-0" style="max-height: 700px; overflow-y: auto;">
    <table id="usersTable" class="table table-bordered table-striped table-sm" style="width: 100%; font-size: 0.8rem; border-collapse: separate; border-spacing: 0;">
      <thead>
        <tr>
          <th style="width: 40px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">ID</th>
          <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Nombre</th>
          <th style="width: 100px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Usuario</th>
          <th style="width: 150px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Email</th>
          <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Cargo</th>
          <th style="width: 160px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Rol Principal</th>
          <th style="width: 90px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Proyectos</th>
          <th style="width: 90px; position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td class="text-center"><?php echo $user['id']; ?></td>
          <td><?php echo htmlspecialchars($user['nombre']); ?></td>
          <td class="text-break"><?php echo htmlspecialchars($user['usuario']); ?></td>
          <td class="text-break"><?php echo htmlspecialchars($user['email']); ?></td>
          <td><?php echo htmlspecialchars($user['cargo']); ?></td>
          <td class="text-center">
            <?php
              $roleCode = strtoupper((string)($user['permiso'] ?? 'V'));
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
          <td class="text-center">
            <span class="badge badge-secondary"><?php echo (int)($user['projects_count'] ?? 0); ?></span>
          </td>
          <td class="text-center">
            <div class="btn-group">
              <a href="/admin/usuarios/editar?id=<?php echo $user['id']; ?>" class="btn btn-info btn-xs" title="Editar">
                <i class="fas fa-edit"></i>
              </a>
              <button class="btn btn-danger btn-xs delete-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['nombre']); ?>" title="Eliminar">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- /.card-body -->
</div>

<style>
    /* Ajustes para maximizar el espacio horizontal y permitir sticky header */
    .text-break {
        word-break: break-all !important;
    }
    #usersTable td, #usersTable th {
        vertical-align: middle;
        padding: 4px 6px;
        font-size: 0.8rem;
    }
    /* Sombra suave para el header sticky */
    thead th {
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
</style>

<script>
$(function () {
  $("#usersTable").DataTable({
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
      { "targets": 6, "width": "6%" },
      { "targets": 7, "width": "10%" }
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

  $(document).on('click', '.delete-user', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    
    Swal.fire({
      title: '¿Estás seguro?',
      text: "Vas a eliminar al usuario " + name,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/admin/usuarios/eliminar',
          method: 'POST',
          data: {
            id: id,
            csrf_token: '<?php echo \Admin\Core\Security::generateCsrfToken(); ?>'
          },
          success: function(response) {
            if (response.success) {
              Swal.fire(
                '¡Eliminado!',
                response.message,
                'success'
              ).then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', response.message, 'error');
            }
          }
        });
      }
    });
  });
});
</script>
