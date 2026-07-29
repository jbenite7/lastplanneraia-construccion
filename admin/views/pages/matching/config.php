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
    <div class="card card-outline card-success">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-sliders-h mr-2"></i>Umbrales de Matching Semántico
        </h3>
      </div>
      <form method="POST" action="/admin/matching/config">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="card-body">
          <p class="text-muted mb-4">
            Configure los umbrales de confianza para el sistema de matching de actividades.
            Los valores deben estar entre 0.00 y 1.00, en incrementos de 0.05.
          </p>

          <!-- High Threshold -->
          <div class="form-group">
            <label for="high_threshold">
              <strong>Umbral Alto (High)</strong>
              <span class="text-muted ml-1">— Coincidencia fuerte</span>
            </label>
            <div class="input-group">
              <input type="number"
                     class="form-control"
                     id="high_threshold"
                     name="high_threshold"
                     value="<?php echo htmlspecialchars(number_format($thresholds['high_threshold'], 2, '.', '')); ?>"
                     min="0.00"
                     max="1.00"
                     step="0.05"
                     required>
              <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
              </div>
            </div>
            <small class="form-text text-muted">
              Actividades con confianza &ge; este valor se clasifican como "alta coincidencia". Default: 0.90
            </small>
          </div>

          <!-- Medium Threshold -->
          <div class="form-group">
            <label for="medium_threshold">
              <strong>Umbral Medio (Medium)</strong>
              <span class="text-muted ml-1">— Coincidencia moderada</span>
            </label>
            <div class="input-group">
              <input type="number"
                     class="form-control"
                     id="medium_threshold"
                     name="medium_threshold"
                     value="<?php echo htmlspecialchars(number_format($thresholds['medium_threshold'], 2, '.', '')); ?>"
                     min="0.00"
                     max="1.00"
                     step="0.05"
                     required>
              <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
              </div>
            </div>
            <small class="form-text text-muted">
              Actividades con confianza &ge; este valor pero &lt; umbral alto se clasifican como "media coincidencia". Default: 0.70
            </small>
          </div>

          <!-- Chapter Threshold -->
          <div class="form-group">
            <label for="chapter_threshold">
              <strong>Umbral de Capítulo</strong>
              <span class="text-muted ml-1">— Similitud entre capítulos</span>
            </label>
            <div class="input-group">
              <input type="number"
                     class="form-control"
                     id="chapter_threshold"
                     name="chapter_threshold"
                     value="<?php echo htmlspecialchars(number_format($thresholds['chapter_threshold'], 2, '.', '')); ?>"
                     min="0.00"
                     max="1.00"
                     step="0.05"
                     required>
              <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
              </div>
            </div>
            <small class="form-text text-muted">
              Similitud mínima entre capítulos para considerar que coinciden (filtro duro). Default: 0.70
            </small>
          </div>
        </div>

        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Guardar Configuración
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
