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

$initialAssignments = [];
foreach (($assignments ?? []) as $assignment) {
    $initialAssignments[] = [
        'project_id' => (int) ($assignment['project_id'] ?? 0),
        'role' => strtoupper((string) ($assignment['role'] ?? 'V')),
    ];
}

$isUserActive = (int) ($user['activo'] ?? 1) === 1;
$forcePasswordChangeEnabled = (int) ($user['force_password_change'] ?? 0) === 1;
?>

<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">Editar Datos del Usuario</h3>
  </div>

  <form id="editUserForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">

    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" name="nombre" class="form-control" id="nombre" value="<?php echo htmlspecialchars((string) $user['nombre']); ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" value="<?php echo htmlspecialchars((string) $user['email']); ?>">
          </div>

          <div class="form-group">
            <label for="cargo">Cargo</label>
            <input type="text" name="cargo" class="form-control" id="cargo" value="<?php echo htmlspecialchars((string) $user['cargo']); ?>">
          </div>

          <div class="form-group mb-0">
            <label class="d-block">Estado del usuario</label>
            <input type="hidden" name="activo" value="0">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" <?php echo $isUserActive ? 'checked' : ''; ?>>
              <label class="custom-control-label" for="activo">Usuario activo</label>
            </div>
            <small class="text-muted">Si se desactiva, el usuario no podrá iniciar sesión pero conservará su trazabilidad.</small>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" class="form-control" id="usuario" value="<?php echo htmlspecialchars((string) $user['usuario']); ?>" required>
          </div>

          <div class="form-group">
            <label for="password">Contrasena (dejar en blanco para no cambiar)</label>
            <input type="password" name="password" class="form-control" id="password" placeholder="Nueva contrasena">
          </div>

          <div class="form-group mb-0">
            <label class="d-block">Política de contraseña</label>
            <input type="hidden" name="force_password_change" value="0">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="force_password_change" name="force_password_change" value="1" <?php echo $forcePasswordChangeEnabled ? 'checked' : ''; ?>>
              <label class="custom-control-label" for="force_password_change">Forzar cambio de contrasena</label>
            </div>
            <small class="text-muted">La bandera seguirá activa hasta que el usuario cambie su contrasena o un admin la desactive.</small>
          </div>
        </div>
      </div>

      <?php if (!$isUserActive): ?>
        <div class="alert alert-warning mt-3 mb-0">
          Este usuario está inactivo. Permanecerá oculto por defecto en el listado y no podrá iniciar sesión hasta ser reactivado.
        </div>
      <?php endif; ?>

      <hr>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Asignaciones por Proyecto</h5>
        <div class="btn-group">
          <button type="button" class="btn btn-sm btn-outline-danger" id="revokeAllProjectsBtn">
            <i class="fas fa-user-slash"></i> Revocar todos los permisos
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addAssignmentRow">
            <i class="fas fa-plus"></i> Agregar proyecto
          </button>
        </div>
      </div>

      <p class="text-muted mb-2">
        Este usuario puede tener roles diferentes segun el proyecto.
      </p>

      <div class="alert alert-light border mb-3">
        Revocar todos los permisos retira el acceso actual a los proyectos, pero no borra el historial del usuario en los proyectos.
      </div>

      <div id="assignmentsContainer"></div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-info" id="updateBtn">Actualizar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<script>
$(function () {
  const $assignmentsContainer = $('#assignmentsContainer');
  const projectOptionsHtml = <?php echo json_encode($projectOptionsHtml); ?>;
  const roleOptionsHtml = <?php echo json_encode($roleOptionsHtml); ?>;
  const initialAssignments = <?php echo json_encode($initialAssignments, JSON_UNESCAPED_UNICODE); ?>;
  const userId = <?php echo (int) $user['id']; ?>;
  const csrfToken = <?php echo json_encode($csrf_token); ?>;
  let assignmentIndex = 0;

  function addAssignmentRow(initialData) {
    const data = initialData || {};
    const idx = assignmentIndex++;

    const row = $(
      '<div class="assignment-row">' +
        '<div class="row align-items-end">' +
          '<div class="col-md-7">' +
            '<label>Proyecto</label>' +
            '<select class="form-control assignment-project" name="assignments[' + idx + '][project_id]" required>' +
              '<option value="">Seleccione un proyecto</option>' +
              projectOptionsHtml +
            '</select>' +
          '</div>' +
          '<div class="col-md-4">' +
            '<label>Rol</label>' +
            '<select class="form-control assignment-role" name="assignments[' + idx + '][role]" required>' +
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
      row.data('saved-project-id', data.project_id);
    }
    row.find('.assignment-role').val(String(data.role || 'V'));
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
      return { ok: false, message: 'Todas las filas deben tener un proyecto seleccionado.' };
    }

    const unique = new Set(selected);
    if (unique.size !== selected.length) {
      return { ok: false, message: 'No puedes repetir el mismo proyecto en varias filas.' };
    }

    return { ok: true };
  }

  if (initialAssignments.length > 0) {
    initialAssignments.forEach(function (assignment) {
      addAssignmentRow(assignment);
    });
  }

  $('#addAssignmentRow').on('click', function () {
    addAssignmentRow({ role: 'V' });
  });

  $('#revokeAllProjectsBtn').on('click', function () {
    const $btn = $(this);

    AIA.Notice.dialog({
      title: 'Revocar todos los permisos',
      text: 'Se retirará el acceso del usuario a todos sus proyectos actuales. Esta acción no borrará su historial en los proyectos.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, revocar todo',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      $btn.prop('disabled', true);

      $.ajax({
        url: '/admin/usuarios/revocar-todos-proyectos',
        method: 'POST',
        dataType: 'json',
        data: {
          id: userId,
          csrf_token: csrfToken
        },
        success: function (response) {
          if (!response.success) {
            AIA.Notice.error(response.message, 'No se pudo revocar');
            return;
          }

          $assignmentsContainer.empty();
          AIA.Notice.success(response.message, 'Permisos revocados');
        },
        error: function () {
          AIA.Notice.error('Error de comunicación con el servidor', 'Error');
        },
        complete: function () {
          $btn.prop('disabled', false);
        }
      });
    });
  });

  $(document).on('click', '.remove-assignment-row', function () {
    const $row = $(this).closest('.assignment-row');
    const savedProjectId = $row.data('saved-project-id');

    // New row (not saved yet) — just remove from DOM
    if (!savedProjectId) {

      $row.remove();
      return;
    }

    // Existing row — validate via server
    const projectName = $row.find('.assignment-project option:selected').text();
    AIA.Notice.dialog({
      title: 'Quitar proyecto',
      text: '¿Retirar a este usuario del proyecto "' + projectName + '"?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, retirar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) return;

      const $btn = $row.find('.remove-assignment-row');
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.ajax({
        url: '/admin/usuarios/quitar-proyecto',
        method: 'POST',
        data: { csrf_token: csrfToken, user_id: userId, project_id: savedProjectId },
        dataType: 'json',
        success: function (res) {
          if (res.success) {
            $row.fadeOut(300, function () { $(this).remove(); });
            AIA.Notice.success(res.message, 'Listo');
          } else {
            AIA.Notice.error(res.message, 'No se puede retirar');
            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
          }
        },
        error: function () {
          AIA.Notice.error('Error de comunicación con el servidor', 'Error');
          $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
        }
      });
    });
  });

  $('#editUserForm').on('submit', function(e) {
    e.preventDefault();

    const assignmentValidation = validateAssignmentsClient();
    if (!assignmentValidation.ok) {
      AIA.Notice.warning(assignmentValidation.message, 'Asignaciones incompletas');
      return;
    }

    const $form = $(this);
    const $btn = $('#updateBtn');

    $btn.prop('disabled', true).text('Actualizando...');

    $.ajax({
      url: '/admin/usuarios/actualizar',
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          AIA.Notice.dialog({
            title: 'Actualizado',
            text: response.message,
            icon: 'success',
            showCancelButton: false,
            confirmButtonText: 'Continuar'
          }).then(function () {
            window.location.href = '/admin/usuarios';
          });
        } else {
          AIA.Notice.error(response.message || 'No se pudo actualizar el usuario.', 'Error');
          $btn.prop('disabled', false).text('Actualizar Usuario');
        }
      },
      error: function() {
        AIA.Notice.error('Error de comunicacion con el servidor', 'Error');
        $btn.prop('disabled', false).text('Actualizar Usuario');
      }
    });
  });
});
</script>
