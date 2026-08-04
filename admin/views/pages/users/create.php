<?php
$projectOptionsHtml = '';
foreach (($projects ?? []) as $project) {
    $projectId = (int) ($project['Id'] ?? 0);
    if ($projectId <= 0) {
        continue;
    }

    $projectName = htmlspecialchars((string) ($project['Proyecto_Proceso'] ?? 'Proyecto'));
    $inactiveLabel = ((int) ($project['Activo'] ?? 0) === 1) ? '' : ' (Inactivo)';
    $projectOptionsHtml .= '<option value="' . $projectId . '">' . $projectName . $inactiveLabel . '</option>';
}

$roleOptionsHtml = '';
foreach (($roles ?? []) as $code => $role) {
    $label = htmlspecialchars($code . ' - ' . ($role['name'] ?? $code));
    $roleOptionsHtml .= '<option value="' . htmlspecialchars($code) . '">' . $label . '</option>';
}
?>

<script src="/public/vendor/select2/select2.min.js"></script>

<div class="card aia-panel aia-panel--elevated card-primary">
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
            <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre completo" aria-describedby="nombre-error" required>
            <span id="nombre-error" class="text-danger admin-field-error" role="alert" aria-live="polite">Este nombre ya está registrado.</span>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" placeholder="Email" aria-describedby="email-error">
            <span id="email-error" class="text-danger admin-field-error" role="alert" aria-live="polite">Este email ya está registrado.</span>
          </div>

          <div class="form-group">
            <label for="cargo">Cargo</label>
            <select name="cargo" class="form-control select2 w-full" id="cargo">
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
        </div>
      </div>

      <hr>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Asignaciones por Proyecto</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addAssignmentRow">
          <i class="fas fa-plus"></i> Agregar proyecto
        </button>
      </div>

      <p class="text-muted mb-2">
        Un usuario puede pertenecer a multiples proyectos y tener un rol diferente en cada uno.
      </p>

      <div id="assignmentsContainer"></div>
    </div>

    <div class="card-footer">
      <button type="submit" id="submitBtn" class="btn btn-primary">Guardar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<script>
$(document).ready(function () {
  const $cargo = $('#cargo');
  const $password = $('#password');
  const $togglePassIcon = $('#togglePass i');
  const $assignmentsContainer = $('#assignmentsContainer');
  const projectOptionsHtml = <?php echo json_encode($projectOptionsHtml); ?>;
  const roleOptionsHtml = <?php echo json_encode($roleOptionsHtml); ?>;
  let suggestedRole = 'V';
  let assignmentIndex = 0;

  function addAssignmentRow(initialData) {
    const data = initialData || {};
    const idx = assignmentIndex++;
    const row = $(
      '<div class="assignment-row">' +
        '<div class="row align-items-end">' +
          '<div class="col-md-7">' +
            '<label for="assignment-project-' + idx + '">Proyecto</label>' +
            '<select id="assignment-project-' + idx + '" class="form-control assignment-project" name="assignments[' + idx + '][project_id]" required>' +
              '<option value="">Seleccione un proyecto</option>' +
              projectOptionsHtml +
            '</select>' +
          '</div>' +
          '<div class="col-md-4">' +
            '<label for="assignment-role-' + idx + '">Rol</label>' +
            '<select id="assignment-role-' + idx + '" class="form-control assignment-role" name="assignments[' + idx + '][role]" required>' +
              roleOptionsHtml +
            '</select>' +
          '</div>' +
          '<div class="col-md-1 text-right">' +
            '<button type="button" class="btn btn-outline-danger remove-assignment-row" title="Quitar">' +
              '<i class="fas fa-trash"></i>' +
            '</button>' +
          '</div>' +
        '</div>' +
      '</div>'
    );

    $assignmentsContainer.append(row);

    if (data.project_id) {
      row.find('.assignment-project').val(String(data.project_id));
    }

    row.find('.assignment-role').val(String(data.role || suggestedRole || 'V'));
  }

  function validateAssignmentsClient() {
    const selected = [];
    let hasEmpty = false;

    $assignmentsContainer.find('.assignment-row').each(function () {
      const projectId = $(this).find('.assignment-project').val();
      if (!projectId) {
        hasEmpty = true;
        return;
      }
      selected.push(projectId);
    });

    if (hasEmpty) {
      return { ok: false, message: 'Todas las asignaciones deben tener un proyecto seleccionado.' };
    }

    const unique = new Set(selected);
    if (unique.size !== selected.length) {
      return { ok: false, message: 'No puedes repetir el mismo proyecto en varias filas.' };
    }

    return { ok: true };
  }

  addAssignmentRow({ role: suggestedRole });

  $('#addAssignmentRow').on('click', function () {
    addAssignmentRow({ role: suggestedRole });
  });

  $(document).on('click', '.remove-assignment-row', function () {
    $(this).closest('.assignment-row').remove();
  });

  $cargo.select2({
    theme: 'bootstrap4',
    tags: true,
    placeholder: 'Seleccione o escriba un cargo',
    allowClear: true,
    ajax: {
      url: '/admin/usuarios/cargos',
      dataType: 'json',
      delay: 250,
      processResults: function (data) {
        return {
          results: (data.cargos || []).map(function (item) {
            return { id: item, text: item };
          })
        };
      }
    }
  });

  $cargo.on('change', function () {
    const val = $(this).val();
    if (!val) {
      return;
    }

    $.ajax({
      url: '/admin/proyectos/sugerir-rol',
      method: 'GET',
      data: { cargo: val },
      dataType: 'json',
      success: function (response) {
        if (!response.role) {
          return;
        }

        suggestedRole = response.role;
        $assignmentsContainer.find('.assignment-role').each(function () {
          const $select = $(this);
          if ($select.val() === 'V' || $select.val() === '' || !$select.data('manual-role')) {
            $select.val(suggestedRole);
          }
        });
      }
    });
  });

  $(document).on('change', '.assignment-role', function () {
    $(this).data('manual-role', true);
  });

  $('#generatePass').on('click', function () {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    let password = '';
    for (let i = 0; i < 12; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $password.val(password).attr('type', 'text');
    $togglePassIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    toastr.success('Contraseña generada');
  });

  $('#togglePass').on('click', function () {
    if ($password.attr('type') === 'password') {
      $password.attr('type', 'text');
      $togglePassIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      $password.attr('type', 'password');
      $togglePassIcon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  let debounceTimer;
  let hasIdentityErrors = false;

  function checkUniqueness() {
    const nombre = $('#nombre').val();
    const email = $('#email').val();

    if (nombre.length <= 3 && email.length <= 3) {
      return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      $.ajax({
        url: '/admin/usuarios/sugerir-usuario',
        method: 'GET',
        data: { nombre: nombre, email: email },
        dataType: 'json',
        success: function (response) {
          hasIdentityErrors = false;

          if (response.nombreExiste) {
            $('#nombre').addClass('is-invalid');
            $('#nombre-error').show();
            hasIdentityErrors = true;
          } else {
            $('#nombre').removeClass('is-invalid');
            $('#nombre-error').hide();
          }

          if (response.emailExiste) {
            $('#email').addClass('is-invalid');
            $('#email-error').show();
            hasIdentityErrors = true;
          } else {
            $('#email').removeClass('is-invalid');
            $('#email-error').hide();
          }

          if (response.usuario && !hasIdentityErrors) {
            $('#usuario').val(response.usuario);
          }

          $('#submitBtn').prop('disabled', hasIdentityErrors);
        }
      });
    }, 500);
  }

  $('#nombre, #email').on('input', checkUniqueness);

  $('#createUserForm').on('submit', function (e) {
    e.preventDefault();

    if (hasIdentityErrors) {
      return;
    }

    const assignmentValidation = validateAssignmentsClient();
    if (!assignmentValidation.ok) {
      AIA.Notice.warning(assignmentValidation.message, 'Asignaciones incompletas');
      return;
    }

    const $form = $(this);
    const $btn = $('#submitBtn');

    $btn.prop('disabled', true).text('Guardando...');

    $.ajax({
      url: '/admin/usuarios/guardar',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          const nombre = $('#nombre').val();
          const usuario = $('#usuario').val();
          const password = $password.val();

          const mensajeTexto =
            '*Credenciales Last Planner AIA*\n' +
            'Hola ' + nombre + ',\n' +
            'Se ha creado tu cuenta de acceso a la plataforma de Last Planner:\n' +
            'Usuario: ' + usuario + '\n' +
            'Contraseña: ' + password + '\n' +
            'Puedes ingresar aqui: https://lastplanneraia.com';

          AIA.Notice.dialog({
            icon: 'success',
            title: 'Usuario creado',
            html:
              '<div class="text-left mt-3">' +
                '<p>Comparte estos datos de forma segura:</p>' +
                '<div class="admin-credentials-block mb-3">' +
                  '<strong>Usuario:</strong> <code>' + usuario + '</code><br>' +
                  '<strong>Contraseña:</strong> <code>' + password + '</code>' +
                '</div>' +
                '<button type="button" class="btn btn-success btn-block" id="btnCopyCreds">' +
                  '<i class="fab fa-whatsapp"></i> Copiar para enviar' +
                '</button>' +
              '</div>',
            showConfirmButton: true,
            confirmButtonText: 'Ir a la lista',
            allowOutsideClick: false,
            didOpen: function () {
              $('#btnCopyCreds').off('click').on('click', function () {
                navigator.clipboard.writeText(mensajeTexto).then(function () {
                  toastr.success('Copiado al portapapeles');
                  $('#btnCopyCreds').html('<i class="fas fa-check"></i> Copiado');
                });
              });
            }
          }).then(function () {
            window.location.href = '/admin/usuarios';
          });
        } else {
          AIA.Notice.error(response.message || 'No se pudo crear el usuario.', 'Error');
          $btn.prop('disabled', false).text('Guardar Usuario');
        }
      },
      error: function () {
        AIA.Notice.error('Error de comunicacion con el servidor.', 'Error');
        $btn.prop('disabled', false).text('Guardar Usuario');
      }
    });
  });
});
</script>
