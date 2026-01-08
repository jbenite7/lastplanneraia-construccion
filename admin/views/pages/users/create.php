<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Datos del Usuario</h3>
  </div>
  <!-- /.card-header -->
  <!-- form start -->
  <form id="createUserForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre completo" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" placeholder="Email">
          </div>
          <div class="form-group">
            <label for="cargo">Cargo</label>
            <input type="text" name="cargo" class="form-control" id="cargo" placeholder="Cargo">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" class="form-control" id="usuario" placeholder="Nombre de usuario" required>
          </div>
          <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" name="password" class="form-control" id="password" placeholder="Contraseña" required>
          </div>
          <div class="form-group">
            <label for="proyecto">Proyecto</label>
            <select name="proyecto" class="form-control" id="proyecto">
              <option value="todos">Todos</option>
              <!-- Aquí se podrían cargar los proyectos dinámicamente -->
            </select>
          </div>
          <div class="form-group">
            <label for="permiso">Permiso</label>
            <select name="permiso" class="form-control" id="permiso">
              <option value="U">Usuario</option>
              <option value="P">Administrador</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Guardar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  $('#createUserForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');

    $btn.prop('disabled', true).text('Guardando...');

    $.ajax({
      url: '/admin/usuarios/guardar',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
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
          $btn.prop('disabled', false).text('Guardar Usuario');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error de comunicación con el servidor'
        });
        $btn.prop('disabled', false).text('Guardar Usuario');
      }
    });
  });
});
</script>
