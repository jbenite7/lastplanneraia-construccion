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
                            <th>Base de Datos</th>
                            <th>Proyecto / Proceso</th>
                            <th>Área</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo $project['Id']; ?></td>
                                        <td><code><?php echo htmlspecialchars($project['Base_de_Datos'] ?? 'N/A'); ?></code></td>
                                        <td><?php echo htmlspecialchars($project['Proyecto_Proceso']); ?></td>
                                        <td><?php echo htmlspecialchars($project['Area'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($project['Activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
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
