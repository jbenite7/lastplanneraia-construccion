<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">Editar Datos del Usuario</h3>
  </div>
  <!-- /.card-header -->
  <!-- form start -->
  <form id="editUserForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" name="nombre" class="form-control" id="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>">
          </div>
          <div class="form-group">
            <label for="cargo">Cargo</label>
            <input type="text" name="cargo" class="form-control" id="cargo" value="<?php echo htmlspecialchars($user['cargo']); ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" class="form-control" id="usuario" value="<?php echo htmlspecialchars($user['usuario']); ?>" required>
          </div>
          <div class="form-group">
            <label for="password">Contraseña (dejar en blanco para no cambiar)</label>
            <input type="password" name="password" class="form-control" id="password" placeholder="Nueva contraseña">
          </div>
          <div class="form-group">
            <label for="proyecto">Proyecto</label>
            <select name="proyecto" class="form-control" id="proyecto">
              <option value="todos" <?php echo $user['proyecto'] == 'todos' ? 'selected' : ''; ?>>Todos</option>
              <!-- Aquí se podrían cargar los proyectos dinámicamente -->
            </select>
          </div>
          <div class="form-group">
            <label for="permiso">Permiso</label>
            <select name="permiso" class="form-control" id="permiso">
              <option value="U" <?php echo $user['permiso'] == 'U' ? 'selected' : ''; ?>>Usuario</option>
              <option value="P" <?php echo $user['permiso'] == 'P' ? 'selected' : ''; ?>>Administrador</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit" class="btn btn-info">Actualizar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  $('#editUserForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');

    $btn.prop('disabled', true).text('Actualizando...');

    $.ajax({
      url: '/admin/usuarios/actualizar',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: response.message
          }).then(() => {
            window.location.href = '/admin/usuarios';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message
          });
          $btn.prop('disabled', false).text('Actualizar Usuario');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error de comunicación con el servidor'
        });
        $btn.prop('disabled', false).text('Actualizar Usuario');
      }
    });
  });
});
</script>
