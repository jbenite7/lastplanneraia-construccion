<div class="row">
    <div class="col-md-12">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Nuevo Proyecto</h3>
            </div>
            <!-- /.card-header -->
            <!-- form start -->
            <form action="/admin/proyectos/guardar" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="card-body">
                    <div class="form-group">
                        <label for="nombre">Nombre del Proyecto / Proceso</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre del proyecto" required>
                    </div>

                    <div class="form-group">
                        <label for="area">Área</label>
                        <select class="form-control" id="area" name="area">
                            <option value="Construccion">Construcción</option>
                            <option value="PI">PI (Planeación e Ingeniería)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_inicio_lb">Fecha Inicio Línea Base</label>
                                <input type="date" class="form-control" id="fecha_inicio_lb" name="fecha_inicio_lb">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_fin_lb">Fecha Fin Línea Base</label>
                                <input type="date" class="form-control" id="fecha_fin_lb" name="fecha_fin_lb">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="costo_retraso">Costo Día Retraso (COP)</label>
                        <input type="number" class="form-control" id="costo_retraso" name="costo_retraso" value="5000000">
                    </div>

                    <div class="form-group">
                        <label for="url_cambios">URL Control de Cambios</label>
                        <input type="url" class="form-control" id="url_cambios" name="url_cambios" placeholder="https://ejemplo.com/doc">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" checked>
                                    <label class="custom-control-label" for="activo">Proyecto Activo</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="acceso" name="acceso" value="1" checked>
                                    <label class="custom-control-label" for="acceso">Acceso Permitido</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="pdc_activo" name="pdc_activo" value="1">
                                    <label class="custom-control-label" for="pdc_activo">PDC Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-success">Crear Proyecto</button>
                    <a href="/admin/proyectos" class="btn btn-default float-right">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
