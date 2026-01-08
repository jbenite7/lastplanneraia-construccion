<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap4-theme/1.5.2/select2-bootstrap4.min.css">
<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<style>
  /* Refuerzo visual para que Select2 parezca una casilla estándar de AdminLTE */
  .select2-container--bootstrap4 .select2-selection--single {
    height: calc(2.25rem + 2px) !important;
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
    box-shadow: inset 0 0 0 transparent !important;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out !important;
  }
  
  .select2-container--bootstrap4.select2-container--focus .select2-selection--single {
    border-color: #80bdff !important;
    outline: 0 !important;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
  }

  .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem !important;
    padding-left: .75rem !important;
    color: #495057 !important;
  }

  .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: 2.25rem !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
  }

  .select2-container--bootstrap4 .select2-dropdown {
    border-color: #80bdff !important;
    border-radius: .25rem !important;
  }
</style>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Datos del Usuario</h3>
  </div>
  <form id="createUserForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre completo" required>
            <span id="nombre-error" class="text-danger" style="display:none; font-size: 0.8rem;">Este nombre ya está registrado.</span>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" placeholder="Email">
            <span id="email-error" class="text-danger" style="display:none; font-size: 0.8rem;">Este email ya está registrado.</span>
          </div>
          <div class="form-group">
            <label for="cargo">Cargo</label>
            <select name="cargo" class="form-control select2" id="cargo" style="width: 100%;">
              <option value=""></option>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" class="form-control" id="usuario" placeholder="Nombre de usuario" readonly required>
          </div>
          <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-group">
              <input type="password" name="password" class="form-control" id="password" placeholder="Contraseña" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="generatePass" title="Generar contraseña">
                  <i class="fas fa-magic"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="togglePass" title="Ver/Ocultar contraseña">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="permiso">Permiso</label>
            <select name="permiso" class="form-control" id="permiso">
              <option value="U">Usuario (Acceso estándar)</option>
              <option value="A">Administrador (Acceso total)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" id="submitBtn" class="btn btn-primary">Guardar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function () {
  const $cargo = $('#cargo');
  const $password = $('#password');
  const $togglePassIcon = $('#togglePass i');
  
  // Inicialización de Select2
  $cargo.select2({
    theme: 'bootstrap4',
    tags: true,
    placeholder: "Seleccione o escriba un cargo",
    allowClear: true,
    ajax: {
      url: '/admin/usuarios/cargos',
      dataType: 'json',
      delay: 250,
      processResults: function (data) {
        return {
          results: data.cargos.map(function(item) {
            return { id: item, text: item };
          })
        };
      }
    }
  });

  // Sugerir Rol basado en cargo
  $cargo.on('change', function() {
    const val = $(this).val();
    if (val) {
      $.ajax({
        url: '/admin/proyectos/sugerir-rol',
        method: 'GET',
        data: { cargo: val },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            $('#permiso').val(response.role).trigger('change');
          }
        }
      });
    }
  });

  // Generador de Contraseña
  $('#generatePass').on('click', function() {
    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    let password = "";
    for (let i = 0; i < 12; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $password.val(password).attr('type', 'text');
    $togglePassIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    toastr.success('Contraseña generada');
  });

  // Ver/Ocultar Contraseña
  $('#togglePass').on('click', function() {
    if ($password.attr('type') === 'password') {
      $password.attr('type', 'text');
      $togglePassIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      $password.attr('type', 'password');
      $togglePassIcon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  let debounceTimer;
  let hasErrors = false;

  const checkUniqueness = () => {
    const nombre = $('#nombre').val();
    const email = $('#email').val();
    
    if (nombre.length > 3 || email.length > 3) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        $.ajax({
          url: '/admin/usuarios/sugerir-usuario',
          method: 'GET',
          data: { nombre: nombre, email: email },
          dataType: 'json',
          success: function(response) {
            hasErrors = false;

            if (response.nombreExiste) {
              $('#nombre').addClass('is-invalid');
              $('#nombre-error').show();
              hasErrors = true;
            } else {
              $('#nombre').removeClass('is-invalid');
              $('#nombre-error').hide();
            }

            if (response.emailExiste) {
              $('#email').addClass('is-invalid');
              $('#email-error').show();
              hasErrors = true;
            } else {
              $('#email').removeClass('is-invalid');
              $('#email-error').hide();
            }

            if (response.usuario && !hasErrors) {
              $('#usuario').val(response.usuario);
            }

            $('#submitBtn').prop('disabled', hasErrors);
          }
        });
      }, 500);
    }
  };

  $('#nombre, #email').on('input', checkUniqueness);

  $('#createUserForm').on('submit', function(e) {
    e.preventDefault();
    if (hasErrors) return;

    const $form = $(this);
    const $btn = $('#submitBtn');

    $btn.prop('disabled', true).text('Guardando...');

    $.ajax({
      url: '/admin/usuarios/guardar',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const nombre = $('#nombre').val();
          const usuario = $('#usuario').val();
          const password = $password.val();
          
          const mensajeTexto = "*Credenciales Last Planner AIA*\n" +
                               "Hola " + nombre + ",\n" +
                               "Se ha creado tu cuenta de acceso a la plataforma de Last Planner:\n" +
                               "Usuario: " + usuario + "\n" +
                               "Contraseña: " + password + "\n" +
                               "Puedes ingresar aquí: https://lastplanneraia.com";

          Swal.fire({
            icon: 'success',
            title: '¡Usuario Creado!',
            html: `
              <div class="text-left mt-3">
                <p>El usuario ha sido guardado correctamente. Comparte estos datos de forma segura:</p>
                <div class="p-3 bg-light border rounded mb-3">
                  <strong>Usuario:</strong> <code>` + usuario + `</code><br>
                  <strong>Contraseña:</strong> <code>` + password + `</code>
                </div>
                <button type="button" class="btn btn-success btn-block" id="btnCopyCreds">
                  <i class="fab fa-whatsapp"></i> Copiar para enviar
                </button>
              </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Ir a la lista',
            allowOutsideClick: false,
            didOpen: () => {
              $('#btnCopyCreds').off('click').on('click', function() {
                navigator.clipboard.writeText(mensajeTexto).then(() => {
                  toastr.success('Copiado al portapapeles');
                  $(this).html('<i class="fas fa-check"></i> ¡Copiado!');
                });
              });
            }
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