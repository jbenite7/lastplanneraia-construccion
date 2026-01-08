<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin/">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/admin/proyectos">Proyectos</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Edición</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="/admin/proyectos/actualizar" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" value="<?php echo $project['Id']; ?>">
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="nombre">Nombre del Proyecto / Proceso</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($project['Proyecto_Proceso']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="base_datos">Base de Datos</label>
                                        <input type="text" class="form-control" id="base_datos" name="base_datos" value="<?php echo htmlspecialchars($project['Base_de_Datos'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="area">Área</label>
                                <select class="form-control" id="area" name="area">
                                    <option value="Construccion" <?php echo ($project['Area'] === 'Construccion') ? 'selected' : ''; ?>>Construcción</option>
                                    <option value="PI" <?php echo ($project['Area'] === 'PI') ? 'selected' : ''; ?>>PI (Planeación e Ingeniería)</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fecha_inicio_lb">Fecha Inicio Línea Base</label>
                                        <input type="date" class="form-control" id="fecha_inicio_lb" name="fecha_inicio_lb" value="<?php echo $project['fechaInicioLineaBase']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fecha_fin_lb">Fecha Fin Línea Base</label>
                                        <input type="date" class="form-control" id="fecha_fin_lb" name="fecha_fin_lb" value="<?php echo $project['fechaFinLineaBase']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="costo_retraso">Costo Día Retraso (COP)</label>
                                <input type="number" class="form-control" id="costo_retraso" name="costo_retraso" value="<?php echo $project['costoDiaRetraso']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="url_cambios">URL Control de Cambios</label>
                                <input type="url" class="form-control" id="url_cambios" name="url_cambios" value="<?php echo htmlspecialchars($project['urlCambios'] ?? ''); ?>" placeholder="https://ejemplo.com/doc">
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" <?php echo ($project['Activo'] == 1) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="activo">Proyecto Activo</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="acceso" name="acceso" value="1" <?php echo ($project['Acceso'] == 1) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="acceso">Acceso Permitido</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="pdc_activo" name="pdc_activo" value="1" <?php echo ($project['pdcActivo'] == 1) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="pdc_activo">PDC Activo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-info">Guardar Cambios</button>
                            <a href="/admin/proyectos" class="btn btn-default float-right">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
