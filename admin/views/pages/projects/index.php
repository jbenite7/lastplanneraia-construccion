<div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Listado de Proyectos de Construcción</h3>
                <div class="card-tools">
                    <a href="/admin/proyectos/crear" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </a>
                </div>
            </div>
            <div class="card-body p-0 dashboard-widget__body h-auto" style="max-height: 700px;">
                <table id="projectsTable" class="table table-bordered table-striped table-sm table-sticky-header w-full text-sm" style="border-collapse: separate; border-spacing: 0;">
                                <thead>
                                    <tr>
                                        <th class="z-sticky bg-white border-top">ID</th>
                                        <th class="z-sticky bg-white border-top">Proyecto / Proceso</th>
                                        <th class="z-sticky bg-white border-top">Área</th>
                                        <th class="z-sticky bg-white border-top text-center">Estado</th>
                                        <th class="z-sticky bg-white border-top text-center">Activo</th>
                                        <th class="z-sticky bg-white border-top text-center">Acceso</th>
                                        <th class="z-sticky bg-white border-top text-center">Plan de Compras</th>
                                        <th class="z-sticky bg-white border-top text-center">Acciones</th>
                                    </tr>
                                </thead>                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $project['Id']; ?></td>
                                        <td class="text-break">
                                            <strong><?php echo htmlspecialchars($project['Proyecto_Proceso']); ?></strong><br>
                                            <small class="text-muted">Prefijo compatibilidad: <code><?php echo htmlspecialchars($project['Base_de_Datos'] ?? 'N/A'); ?></code></small>
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
<form id="deleteForm" action="/admin/proyectos/eliminar" method="POST" class="d-none">
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
        font-size: 0.8rem;
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
            { "targets": 0, "width": "5%" },
            { "targets": 1, "width": "29%" },
            { "targets": 2, "width": "13%" },
            { "targets": 3, "width": "10%" },
            { "targets": 4, "width": "8%", "orderable": false },
            { "targets": 5, "width": "8%", "orderable": false },
            { "targets": 6, "width": "11%", "orderable": false },
            { "targets": 7, "width": "16%", "orderable": false }
        ],
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    });
    
    table.buttons().container().appendTo('#projectsTable_wrapper .col-md-6:eq(0)');

    // Confirmación de eliminación con SweetAlert2 (usando delegación de eventos)
    $(document).on('click', '.delete-project', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        AIA.Notice.dialog({
            title: '¿Estás seguro?',
            text: "Se descargará un respaldo automático y luego se eliminará el proyecto '" + name + "' permanentemente. ¡Esta acción no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, respaldar y eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/admin/proyectos/respaldar?id=' + id;
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
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.success(response.message);
                    
                    // Si el campo es 'activo', actualizar el badge de la columna Estado
                    if (field === 'activo') {
                        var badgeHtml = value ? 
                            '<span class="badge badge-success">Activo</span>' : 
                            '<span class="badge badge-danger">Inactivo</span>';
                        $('#status-badge-' + projectId).html(badgeHtml);
                    }
                } else {
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.errorToast(response.message);
                    // Revertir el cambio si hubo error
                    checkbox.prop('checked', !value);
                }
            },
            error: function() {
                if (window.AIA && window.AIA.Notice) window.AIA.Notice.errorToast('Error de comunicación con el servidor');
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
        if (action === 'created' && window.AIA && window.AIA.Notice) window.AIA.Notice.success('Proyecto creado con éxito');
        if (action === 'updated' && window.AIA && window.AIA.Notice) window.AIA.Notice.success('Proyecto actualizado con éxito');
        if (action === 'deleted' && window.AIA && window.AIA.Notice) window.AIA.Notice.success('El proyecto ha sido eliminado correctamente.');
        
        window.history.replaceState({}, document.title, "/admin/proyectos");
    }

    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        if (error === 'delete_failed' && window.AIA && window.AIA.Notice) window.AIA.Notice.errorToast('No se pudo eliminar el proyecto');
        
        window.history.replaceState({}, document.title, "/admin/proyectos");
    }
});
</script>
