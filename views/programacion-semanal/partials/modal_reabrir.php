<!-- Modal: Reabrir Semana (solo Admin) -->
<div class="modal fade aia-modal" id="modal_reabrir_semana" tabindex="-1" role="dialog" aria-labelledby="modalReabrirTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header ps-reopen-header">
                <h5 class="modal-title" id="modalReabrirTitle"><i class="fas fa-unlock"></i> Reabrir Semana</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    Esta acción reabrirá la semana para edición. Todos los compromisos podrán ser modificados nuevamente.
                </div>
                <div class="form-group">
                    <label for="reabrir_motivo"><strong>Motivo de reapertura <span class="text-danger">*</span></strong></label>
                    <textarea id="reabrir_motivo" class="aia-input" rows="4" minlength="20" maxlength="500" placeholder="Describa el motivo por el cual necesita reabrir esta semana (mínimo 20 caracteres)..." required></textarea>
                    <small class="form-text text-muted"><span id="reabrir_motivo_count">0</span>/20 caracteres mínimos</small>
                </div>
                <div id="reabrir_feedback" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="aia-btn aia-btn--secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_confirmar_reabrir" class="aia-btn aia-btn--warning" disabled>
                    <i class="fas fa-unlock"></i> Confirmar Reapertura
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var $motivo = $('#reabrir_motivo');
    var $btnConfirm = $('#btn_confirmar_reabrir');
    var $feedback = $('#reabrir_feedback');
    var $count = $('#reabrir_motivo_count');

    function updateReopenState() {
        var len = ($motivo.val() || '').trim().length;
        $count.text(len);
        $btnConfirm.prop('disabled', len < 20);
    }

    function resetConfirmButton() {
        $btnConfirm.prop('disabled', false).html('<i class="fas fa-unlock"></i> Confirmar Reapertura');
    }

    function mostrarError(msg) {
        // .text() sobre el mensaje: puede venir de una excepción del servidor.
        $feedback.removeClass('d-none alert-success').addClass('alert alert-danger')
            .empty()
            .append($('<i class="fas fa-times-circle"></i>'))
            .append(document.createTextNode(' ' + msg));
        resetConfirmButton();
    }

    // Mismas fuentes de contexto que public/js/modules/programacion_semanal/hot.js:
    // el prefijo de proyecto vive en #baseDatos_PHP (o #baseDatos, inyectado por
    // cargarDatosGeneralesPagina2.js), nunca en #seccion.
    function getContextDb() {
        return String($('#baseDatos_PHP').val() || $('#baseDatos').val() || '').trim();
    }

    function getContextSemana() {
        return parseInt($('#semana_PHP').val() || $('#semana').val() || '0', 10);
    }

    $motivo.on('input', updateReopenState);

    $('#modal_reabrir_semana').on('show.bs.modal', function () {
        $motivo.val('');
        $feedback.addClass('d-none').empty();
        updateReopenState();
    });

    $btnConfirm.on('click', function () {
        var motivo = ($motivo.val() || '').trim();
        if (motivo.length < 20) { return; }

        var db = getContextDb();
        var semana = getContextSemana();
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        if (db === '' || !Number.isFinite(semana) || semana <= 0) {
            mostrarError('No se pudo determinar el proyecto o la semana activa. Recargue la página e intente de nuevo.');
            return;
        }

        $btnConfirm.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        $feedback.addClass('d-none').empty();

        $.ajax({
            url: '/api/semanal/reabrir',
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            data: {
                db: db,
                semana: semana,
                motivo: motivo,
                _csrf_token: csrfToken
            },
            dataType: 'json'
        }).done(function (resp) {
            if (resp && (resp.respuesta === 'OK' || resp.respuesta === 'BIEN')) {
                $feedback.removeClass('d-none alert-danger').addClass('alert alert-success')
                    .html('<i class="fas fa-check-circle"></i> Semana reabierta correctamente. Recargando...');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                mostrarError((resp && (resp.mensaje || resp.message || resp.respuesta)) || 'Error desconocido.');
            }
        }).fail(function (xhr) {
            var body = xhr.responseJSON;
            if (!body) {
                try { body = JSON.parse(xhr.responseText); } catch (e) { body = null; }
            }
            var msg = (body && (body.mensaje || body.message)) || ('Error de conexión (HTTP ' + xhr.status + '). Intente de nuevo.');
            mostrarError(msg);
        });
    });
});
</script>
