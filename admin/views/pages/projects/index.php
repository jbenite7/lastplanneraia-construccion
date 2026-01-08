<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin/">Inicio</a></li>
                    <li class="breadcrumb-item active"><?php echo $breadcrumb; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Listado de Proyectos de Construcción</h3>
            </div>
            <div class="card-body">
                <table id="projectsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Proyecto / Proceso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project['Id']; ?></td>
                                <td><?php echo htmlspecialchars($project['Codigo'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($project['Proyecto_Proceso']); ?></td>
                                <td class="text-center">
                                    <?php if ($project['Activo'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="/admin/proyectos/editar?id=<?php echo $project['Id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- DataTables Scripts -->
<script>
$(function () {
    $("#projectsTable").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#projectsTable_wrapper .col-md-6:eq(0)');
});
</script>
