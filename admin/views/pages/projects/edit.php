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
                            <option value="Pre-Construccion" <?php echo ($project['Area'] === 'Pre-Construccion') ? 'selected' : ''; ?>>Pre-Construcción</option>
                        </select>
                    </div>

                    <div class="form-group pc-restriction-fields<?php echo ($project['Area'] === 'Pre-Construccion') ? ' is-visible' : ''; ?>">
                        <label for="pc_restr_2_nombre">Nombre Restricción #2</label>
                        <input type="text" class="form-control" id="pc_restr_2_nombre" name="pc_restr_2_nombre" value="<?php echo htmlspecialchars($project['pc_restr_2_nombre'] ?? ''); ?>" placeholder="Ej: Permisos Ambientales">
                    </div>
                    <div class="form-group pc-restriction-fields<?php echo ($project['Area'] === 'Pre-Construccion') ? ' is-visible' : ''; ?>">
                        <label for="pc_restr_3_nombre">Nombre Restricción #3</label>
                        <input type="text" class="form-control" id="pc_restr_3_nombre" name="pc_restr_3_nombre" value="<?php echo htmlspecialchars($project['pc_restr_3_nombre'] ?? ''); ?>" placeholder="Ej: Diseños">
                    </div>
                    <div class="form-group pc-restriction-fields<?php echo ($project['Area'] === 'Pre-Construccion') ? ' is-visible' : ''; ?>">
                        <label for="pc_restr_4_nombre">Nombre Restricción #4</label>
                        <input type="text" class="form-control" id="pc_restr_4_nombre" name="pc_restr_4_nombre" value="<?php echo htmlspecialchars($project['pc_restr_4_nombre'] ?? ''); ?>" placeholder="Ej: Apropiación Presupuestal">
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
<script>
document.getElementById('area').addEventListener('change', function() {
    document.querySelectorAll('.pc-restriction-fields').forEach(function(el) {
        el.classList.toggle('is-visible', this.value === 'Pre-Construccion');
    }.bind(this));
});
</script>
