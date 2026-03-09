<?php
$projectOptionsHtml = '';
foreach (($projects ?? []) as $project) {
    $projectId = (int)($project['Id'] ?? 0);
    if ($projectId <= 0) {
        continue;
    }

    $projectName = htmlspecialchars((string)($project['Proyecto_Proceso'] ?? 'Proyecto'));
    $inactiveLabel = ((int)($project['Activo'] ?? 0) === 1) ? '' : ' (Inactivo)';
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
        'project_id' => (int)($assignment['project_id'] ?? 0),
        'role' => strtoupper((string)($assignment['role'] ?? 'V')),
    ];
}
?>

<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">Editar Datos del Usuario</h3>
  </div>

  <form id="editUserForm">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" name="nombre" class="form-control" id="nombre" value="<?php echo htmlspecialchars((string)$user['nombre']); ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" id="email" value="<?php echo htmlspecialchars((string)$user['email']); ?>">
          </div>

          <div class="form-group">
            <label for="cargo">Cargo</label>
            <input type="text" name="cargo" class="form-control" id="cargo" value="<?php echo htmlspecialchars((string)$user['cargo']); ?>">
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input type="text" name="usuario" class="form-control" id="usuario" value="<?php echo htmlspecialchars((string)$user['usuario']); ?>" required>
          </div>

          <div class="form-group">
            <label for="password">Contrasena (dejar en blanco para no cambiar)</label>
            <input type="password" name="password" class="form-control" id="password" placeholder="Nueva contrasena">
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
        Este usuario puede tener roles diferentes segun el proyecto.
      </p>

      <div id="assignmentsContainer"></div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-info" id="updateBtn">Actualizar Usuario</button>
      <a href="/admin/usuarios" class="btn btn-default">Cancelar</a>
    </div>
  </form>
</div>

<style>
  .assignment-row {
    border: 1px solid #d7dbe0;
    border-radius: .35rem;
    padding: .75rem;
    margin-bottom: .75rem;
    background: #fbfcfe;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  const $assignmentsContainer = $('#assignmentsContainer');
  const projectOptionsHtml = <?php echo json_encode($projectOptionsHtml); ?>;
  const roleOptionsHtml = <?php echo json_encode($roleOptionsHtml); ?>;
  const initialAssignments = <?php echo json_encode($initialAssignments, JSON_UNESCAPED_UNICODE); ?>;
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

    if (selected.length === 0) {
      return { ok: false, message: 'Debes asignar al menos un proyecto.' };
    }

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
  } else {
    addAssignmentRow({ role: 'V' });
  }

  $('#addAssignmentRow').on('click', function () {
    addAssignmentRow({ role: 'V' });
  });

  $(document).on('click', '.remove-assignment-row', function () {
    if ($assignmentsContainer.find('.assignment-row').length <= 1) {
      Swal.fire({
        icon: 'warning',
        title: 'Asignacion requerida',
        text: 'El usuario debe tener al menos un proyecto asignado.'
      });
      return;
    }

    $(this).closest('.assignment-row').remove();
  });

  $('#editUserForm').on('submit', function(e) {
    e.preventDefault();

    const assignmentValidation = validateAssignmentsClient();
    if (!assignmentValidation.ok) {
      Swal.fire({
        icon: 'warning',
        title: 'Asignaciones incompletas',
        text: assignmentValidation.message
      });
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
          Swal.fire({
            icon: 'success',
            title: 'Actualizado',
            text: response.message
          }).then(function () {
            window.location.href = '/admin/usuarios';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'No se pudo actualizar el usuario.'
          });
          $btn.prop('disabled', false).text('Actualizar Usuario');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error de comunicacion con el servidor'
        });
        $btn.prop('disabled', false).text('Actualizar Usuario');
      }
    });
  });
});
</script>
