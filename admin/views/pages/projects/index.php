<div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Listado de Proyectos de Construcción</h3>
                <div class="card-tools">
                    <a href="/admin/proyectos/crear" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </a>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 700px; overflow-y: auto;">
                <table id="projectsTable" class="table table-bordered table-striped table-sm" style="width: 100%; font-size: 0.85rem; border-collapse: separate; border-spacing: 0;">
                                <thead>
                                    <tr>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">ID</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Proyecto / Proceso</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;">Área</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Estado</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Activo</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Acceso</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Plan de Compras</th>
                                        <th style="position: sticky; top: 0; background: white; z-index: 10; border-top: 1px solid #dee2e6;" class="text-center">Acciones</th>
                                    </tr>
                                </thead>                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $project['Id']; ?></td>
                                        <td class="text-break">
                                            <strong><?php echo htmlspecialchars($project['Proyecto_Proceso']); ?></strong><br>
                                            <small class="text-muted">BD: <code><?php echo htmlspecialchars($project['Base_de_Datos'] ?? 'N/A'); ?></code></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['Area'] ?? 'N/A'); ?></td>
                                        <td class="text-center" id="status-badge-<?php echo $project['Id']; ?>">
                                            <?php if ($project['Activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input status-toggle" 
                                                       id="activo-<?php echo $project['Id']; ?>" 
                                                       data-id="<?php echo $project['Id']; ?>"
                                                       data-field="activo"
                                                       <?php echo ($project['Activo']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="activo-<?php echo $project['Id']; ?>"></label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input status-toggle" 
                                                       id="acceso-<?php echo $project['Id']; ?>" 
                                                       data-id="<?php echo $project['Id']; ?>"
                                                       data-field="acceso"
                                                       <?php echo ($project['Acceso']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="acceso-<?php echo $project['Id']; ?>"></label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input status-toggle" 
                                                       id="pdc-<?php echo $project['Id']; ?>" 
                                                       data-id="<?php echo $project['Id']; ?>"
                                                       data-field="pdc"
                                                       <?php echo ($project['pdcActivo']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="pdc-<?php echo $project['Id']; ?>"></label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="/admin/proyectos/miembros?id=<?php echo $project['Id']; ?>" class="btn btn-xs btn-outline-primary" title="Gestionar Miembros">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <a href="/admin/proyectos/respaldar?id=<?php echo $project['Id']; ?>" class="btn btn-xs btn-warning" title="Respaldar (SQL)">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="/admin/proyectos/editar?id=<?php echo $project['Id']; ?>" class="btn btn-xs btn-info" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-xs btn-danger delete-project" 
                                                        data-id="<?php echo $project['Id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($project['Proyecto_Proceso']); ?>"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                </table>
            </div>
        </div>

<!-- Hidden Form for Deletion -->
<form id="deleteForm" action="/admin/proyectos/eliminar" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" id="deleteProjectId">
</form>

<style>
    /* Ajustes para maximizar el espacio horizontal y permitir sticky header */
    .text-break {
        word-break: break-all !important;
    }
    #projectsTable td, #projectsTable th {
        vertical-align: middle;
        padding: 4px 6px;
    }
    /* Sombra suave para el header sticky */
    thead th {
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
    
    #projectsTable_wrapper {
        margin-bottom: 20px;
    }
</style>

<!-- DataTables Scripts -->
<script>
$(function () {
    // Inicializar DataTable
    var table = $("#projectsTable").DataTable({
        "responsive": false, 
        "lengthChange": false, 
        "autoWidth": false,
        "paging": false,
        "info": false,
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
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [4, 5, 6, 7] }
        ],
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    });
    
    table.buttons().container().appendTo('#projectsTable_wrapper .col-md-6:eq(0)');

    // Confirmación de eliminación con SweetAlert2 (usando delegación de eventos)
    $(document).on('click', '.delete-project', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: '¿Estás seguro?',
            text: "Se descargará un respaldo automático y luego se eliminará el proyecto '" + name + "' permanentemente. ¡Esta acción no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, respaldar y eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // 1. Disparar la descarga del backup
                window.location.href = '/admin/proyectos/respaldar?id=' + id;
                
                // 2. Esperar un breve momento para que el navegador procese la descarga antes de enviar el POST de eliminación
                setTimeout(function() {
                    $('#deleteProjectId').val(id);
                    $('#deleteForm').submit();
                }, 2000);
            }
        });
    });

    // Manejo del cambio de estado vía AJAX (usando delegación de eventos)
    $(document).on('change', '.status-toggle', function() {
        var checkbox = $(this);
        var projectId = checkbox.data('id');
        var field = checkbox.data('field');
        var value = checkbox.is(':checked') ? 1 : 0;
        
        // Deshabilitar temporalmente para evitar múltiples clicks
        checkbox.prop('disabled', true);

        $.ajax({
            url: '/admin/proyectos/toggle-status',
            method: 'POST',
            data: {
                id: projectId,
                field: field,
                value: value,
                csrf_token: '<?php echo $csrf_token; ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Si el campo es 'activo', actualizar el badge de la columna Estado
                    if (field === 'activo') {
                        var badgeHtml = value ? 
                            '<span class="badge badge-success">Activo</span>' : 
                            '<span class="badge badge-danger">Inactivo</span>';
                        $('#status-badge-' + projectId).html(badgeHtml);
                    }
                } else {
                    toastr.error(response.message);
                    // Revertir el cambio si hubo error
                    checkbox.prop('checked', !value);
                }
            },
            error: function() {
                toastr.error('Error de comunicación con el servidor');
                checkbox.prop('checked', !value);
            },
            complete: function() {
                checkbox.prop('disabled', false);
            }
        });
    });

    // Detectar parámetros en la URL para notificaciones
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const action = urlParams.get('success');
        if (action === 'created') toastr.success('Proyecto creado con éxito');
        if (action === 'updated') toastr.success('Proyecto actualizado con éxito');
        if (action === 'deleted') Swal.fire('Eliminado', 'El proyecto ha sido eliminado correctamente.', 'success');
        
        window.history.replaceState({}, document.title, "/admin/proyectos");
    }

    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        if (error === 'delete_failed') toastr.error('No se pudo eliminar el proyecto');
        
        window.history.replaceState({}, document.title, "/admin/proyectos");
    }
});
</script>
