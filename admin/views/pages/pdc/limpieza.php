<?php
/**
 * Limpieza selectiva del Plan de Compras.
 *
 * @var array $projects lista de general_proyectos_procesos
 * @var array $etapas   PdcResetService::ETAPAS, en orden de aguas abajo hacia arriba
 * @var string $csrf_token
 */
$etapaClaves = array_keys($etapas);
?>

<div class="row">
  <div class="col-lg-5">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Qué borrar</h3>
      </div>
      <div class="card-body">

        <div class="form-group">
          <label for="pdcProyecto">Proyecto</label>
          <select id="pdcProyecto" class="form-control">
            <option value="">— Elige un proyecto —</option>
            <?php foreach ($projects as $project): ?>
              <option value="<?php echo (int) $project['Id']; ?>"
                      data-nombre="<?php echo htmlspecialchars($project['Proyecto_Proceso'], ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($project['Proyecto_Proceso']); ?>
                (<?php echo (int) $project['Id']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <label>Etapas</label>
        <p class="text-muted small">
          Marcar una etapa arrastra automáticamente las de aguas abajo: si borras el presupuesto,
          lo que se construyó encima deja de tener sentido.
        </p>

        <?php foreach (array_reverse($etapaClaves) as $clave): ?>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input pdc-etapa" id="etapa-<?php echo $clave; ?>"
                   value="<?php echo $clave; ?>" data-orden="<?php echo array_search($clave, $etapaClaves, true); ?>">
            <label class="custom-control-label" for="etapa-<?php echo $clave; ?>">
              <?php echo htmlspecialchars($etapas[$clave]['label']); ?>
              <span class="badge badge-secondary ml-1" data-etapa-total="<?php echo $clave; ?>">—</span>
              <br><small class="text-muted"><?php echo implode(', ', $etapas[$clave]['tablas']); ?></small>
            </label>
          </div>
        <?php endforeach; ?>

        <div class="form-group">
          <label for="pdcConfirmacion">
            Para desbloquear, escribe el nombre exacto del proyecto
          </label>
          <input type="text" class="form-control" id="pdcConfirmacion" autocomplete="off" disabled
                 placeholder="Nombre del proyecto">
        </div>

        <button type="button" class="btn btn-danger btn-block" id="pdcEjecutar" disabled>
          <i class="fas fa-broom"></i> Respaldar y limpiar
        </button>
        <p class="small text-muted mt-2 mb-0">
          Antes de borrar se genera siempre un respaldo <code>.sql</code> en
          <code>storage/backups/</code>. Si el respaldo falla, no se borra nada.
        </p>

      </div>
    </div>

    <!-- Se arma como `card` y no como `alert`/`callout`: en dark, los tokens de estado
         pintan esas dos con fondo claro y el texto interior queda ilegible. -->
    <div class="card card-outline card-success">
      <div class="card-header">
        <h3 class="card-title">Esto nunca se toca</h3>
      </div>
      <div class="card-body">
        <ul class="list-unstyled small mb-2" id="pdcCatalogos">
          <li>Elige un proyecto para ver los conteos.</li>
        </ul>
        <p class="small mb-0">
          Los catálogos de arriba los comparten todos los proyectos. Tampoco se toca el
          cronograma de obra (programa, programación semanal), que no es del PDC.
        </p>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card card-outline card-secondary">
      <div class="card-header">
        <h3 class="card-title">Estado actual del proyecto</h3>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" id="pdcConteos">
          <thead>
            <tr>
              <th>Tabla</th>
              <th class="text-right">Filas</th>
              <th>Etapa</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-muted text-center py-4">Elige un proyecto.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  const CSRF = '<?php echo $csrf_token; ?>';
  const ORDEN = <?php echo json_encode($etapaClaves); ?>;

  const $proyecto = $('#pdcProyecto');
  const $confirmacion = $('#pdcConfirmacion');
  const $ejecutar = $('#pdcEjecutar');
  const $etapasInputs = $('.pdc-etapa');

  let nombreProyecto = '';

  function etapasMarcadas() {
    return $etapasInputs.filter(':checked').map((_, el) => el.value).get();
  }

  /** Marcar una etapa implica las de aguas abajo; se reflejan marcadas y bloqueadas. */
  function propagarSeleccion() {
    const ordenes = $etapasInputs.filter(':checked').map((_, el) => Number(el.dataset.orden)).get();
    const tope = ordenes.length ? Math.max(...ordenes) : -1;

    $etapasInputs.each(function () {
      const orden = Number(this.dataset.orden);
      const arrastrada = orden < tope;
      this.disabled = arrastrada;
      if (arrastrada) { this.checked = true; }
    });

    actualizarBoton();
  }

  function actualizarBoton() {
    const listo = $proyecto.val() && etapasMarcadas().length > 0;
    $confirmacion.prop('disabled', !listo);
    $ejecutar.prop('disabled', !(listo && $confirmacion.val().trim() === nombreProyecto));
  }

  function pintarConteos(conteos) {
    const filas = [];
    ORDEN.forEach((clave) => {
      const etapa = conteos.etapas[clave];
      $(`[data-etapa-total="${clave}"]`).text(etapa.total);
      Object.entries(etapa.tablas).forEach(([tabla, n]) => {
        filas.push(`<tr><td><code>${tabla}</code></td><td class="text-right">${n}</td><td class="text-muted small">${etapa.label}</td></tr>`);
      });
    });
    Object.entries(conteos.cascada).forEach(([tabla, n]) => {
      filas.push(`<tr class="text-muted"><td><code>${tabla}</code></td><td class="text-right">${n}</td><td class="small">cae en cascada</td></tr>`);
    });
    $('#pdcConteos tbody').html(filas.join(''));

    $('#pdcCatalogos').html(
      Object.entries(conteos.catalogos)
        .map(([tabla, n]) => `<li><code>${tabla}</code> — <strong>${n}</strong> filas</li>`)
        .join('')
    );
  }

  function cargarConteos() {
    const id = $proyecto.val();
    if (!id) { return; }
    fetch(`/admin/pdc/limpieza/conteos?project_id=${encodeURIComponent(id)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) { AIA.Notice.error(data.message); return; }
        pintarConteos(data.conteos);
      })
      .catch(() => AIA.Notice.error('No se pudieron leer los conteos.'));
  }

  $proyecto.on('change', function () {
    nombreProyecto = $(this).find('option:selected').data('nombre') || '';
    $confirmacion.val('').attr('placeholder', nombreProyecto || 'Nombre del proyecto');
    actualizarBoton();
    cargarConteos();
  });

  $etapasInputs.on('change', propagarSeleccion);
  $confirmacion.on('input', actualizarBoton);

  $ejecutar.on('click', function () {
    const etapas = etapasMarcadas();
    AIA.Notice.dialog({
      title: '¿Limpiar el Plan de Compras?',
      html: `Se borrarán las etapas <strong>${etapas.join(', ')}</strong> del proyecto `
          + `<strong>${nombreProyecto}</strong>. Primero se genera un respaldo.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Respaldar y limpiar',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        const formData = new FormData();
        formData.append('csrf_token', CSRF);
        formData.append('project_id', $proyecto.val());
        formData.append('confirmacion', $confirmacion.val().trim());
        etapas.forEach((etapa) => formData.append('etapas[]', etapa));

        return fetch('/admin/pdc/limpieza/ejecutar', {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then((r) => r.json())
          .catch((error) => Swal.showValidationMessage(`Falló la petición: ${error}`));
      }
    }).then((result) => {
      if (!result.isConfirmed || !result.value) { return; }
      if (result.value.success) {
        pintarConteos(result.value.conteos);
        $etapasInputs.prop('checked', false).prop('disabled', false);
        $confirmacion.val('');
        actualizarBoton();
        AIA.Notice.success(`${result.value.message} Respaldo: ${result.value.respaldo}`);
      } else {
        AIA.Notice.error(result.value.message);
      }
    });
  });
});
</script>
