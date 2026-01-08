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
  <div class="card-body">
    <table id="usersTable" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Email</th>
          <th>Cargo</th>
          <th>Proyecto</th>
          <th>Permiso</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td><?php echo $user['id']; ?></td>
          <td><?php echo htmlspecialchars($user['nombre']); ?></td>
          <td><?php echo htmlspecialchars($user['usuario']); ?></td>
          <td><?php echo htmlspecialchars($user['email']); ?></td>
          <td><?php echo htmlspecialchars($user['cargo']); ?></td>
          <td><?php echo htmlspecialchars($user['proyecto']); ?></td>
          <td><?php echo htmlspecialchars($user['permiso']); ?></td>
          <td>
            <a href="/admin/usuarios/editar?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">
              <i class="fas fa-edit"></i>
            </a>
            <button class="btn btn-danger btn-sm delete-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['nombre']); ?>">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- /.card-body -->
</div>

<style>
    /* Prevenir que el footer de AdminLTE solape la última fila de la tabla */
    .card-body {
        padding-bottom: 60px;
        min-height: 200px;
    }
</style>

<script>
$(function () {
  $("#usersTable").DataTable({
    "responsive": true, 
    "lengthChange": false, 
    "autoWidth": false,
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
    }
  });

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
