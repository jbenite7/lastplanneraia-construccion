<div class="row">
  <div class="col-lg-3 col-6">
    <!-- small box -->
    <div class="small-box bg-info">
      <div class="inner">
        <h3>150</h3>
        <p>Proyectos Activos</p>
      </div>
      <div class="icon">
        <i class="fas fa-project-diagram"></i>
      </div>
      <a href="proyectos.php" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box -->
    <div class="small-box bg-success">
      <div class="inner">
        <h3>53<sup style="font-size: 20px">%</sup></h3>
        <p>Cumplimiento General</p>
      </div>
      <div class="icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box -->
    <div class="small-box bg-warning">
      <div class="inner">
        <h3>44</h3>
        <p>Usuarios Registrados</p>
      </div>
      <div class="icon">
        <i class="fas fa-users"></i>
      </div>
      <a href="usuarios.php" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-6">
    <!-- small box -->
    <div class="small-box bg-danger">
      <div class="inner">
        <h3>65</h3>
        <p>Logs de Error</p>
      </div>
      <div class="icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Bienvenido al Panel de Administración</h3>
  </div>
  <div class="card-body">
    Has iniciado sesión como <strong><?php echo $user['nombre']; ?></strong> (<?php echo $user['usuario']; ?>).
    <br><br>
    Desde este panel podrás gestionar los proyectos y usuarios de la plataforma de construcción.
  </div>
</div>
