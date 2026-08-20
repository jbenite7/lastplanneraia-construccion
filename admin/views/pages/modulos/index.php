<!-- Flash Messages -->
<?php if (!empty($flash_success)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($flash_success); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($flash_error); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-6 col-md-8">
    <div class="card aia-panel aia-panel--elevated card-outline card-success">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-toggle-on mr-2"></i>Interruptores de módulos
        </h3>
      </div>
      <div class="card-body">
        <?php foreach ($conocidos as $clave => $texto):
            $fila = $flags[$clave] ?? null;
            $encendido = $fila !== null && $fila['valor'] === '1';
        ?>
          <form method="POST" action="/admin/modulos" style="margin-bottom: 1rem;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="clave" value="<?php echo htmlspecialchars($clave); ?>">
            <input type="hidden" name="valor" value="<?php echo $encendido ? '0' : '1'; ?>">
            <p>
              <strong><?php echo htmlspecialchars($texto); ?></strong><br>
              <code><?php echo htmlspecialchars($clave); ?></code> —
              estado: <?php echo $encendido ? 'ENCENDIDO' : 'APAGADO'; ?>
              <?php if ($fila !== null): ?>
                · último cambio: <?php echo htmlspecialchars($fila['actualizado_por']); ?>
                el <?php echo htmlspecialchars($fila['actualizado_en']); ?>
              <?php endif; ?>
            </p>
            <button type="submit" class="btn <?php echo $encendido ? 'btn-warning' : 'btn-success'; ?>">
              <?php echo $encendido ? 'Apagar' : 'Encender'; ?>
            </button>
          </form>
        <?php endforeach; ?>
        <p class="text-muted">El Admin siempre puede entrar al módulo, esté como esté el interruptor.</p>
      </div>
    </div>
  </div>
</div>
