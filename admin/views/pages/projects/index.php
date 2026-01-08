<div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Listado de Proyectos de Construcción</h3>
                <div class="card-tools">
                    <a href="/admin/proyectos/crear" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table id="projectsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proyecto / Proceso</th>
                            <th>Área</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Activo</th>
                            <th class="text-center">Acceso</th>
                            <th class="text-center">Plan de Compras</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo $project['Id']; ?></td>
                                        <td>
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
                                            <a href="/admin/proyectos/editar?id=<?php echo $project['Id']; ?>" class="btn btn-sm btn-info" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                </table>
            </div>
        </div>

<!-- DataTables Scripts -->
<script>
$(function () {
    // Inicializar DataTable
    var table = $("#projectsTable").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "paging": false,
        "info": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [4, 5, 6, 7] }
        ],
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    });
    
    table.buttons().container().appendTo('#projectsTable_wrapper .col-md-6:eq(0)');

    // Manejo del cambio de estado vía AJAX
    $('.status-toggle').on('change', function() {
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

    // Detectar parámetros de éxito en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const action = urlParams.get('success');
        if (action === 'created') toastr.success('Proyecto creado con éxito');
        if (action === 'updated') toastr.success('Proyecto actualizado con éxito');
        
        window.history.replaceState({}, document.title, "/admin/proyectos");
    }
});
</script>
