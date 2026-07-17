<!-- Modal: Reabrir Semana (solo Admin) -->
<div class="modal fade" id="modal_reabrir_semana" tabindex="-1" role="dialog" aria-labelledby="modalReabrirTitle" aria-hidden="true">
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
                    <textarea id="reabrir_motivo" class="form-control" rows="4" minlength="20" maxlength="500" placeholder="Describa el motivo por el cual necesita reabrir esta semana (mínimo 20 caracteres)..." required></textarea>
                    <small class="form-text text-muted"><span id="reabrir_motivo_count">0</span>/20 caracteres mínimos</small>
                </div>
                <div id="reabrir_feedback" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_confirmar_reabrir" class="btn btn-warning" disabled>
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

    $motivo.on('input', updateReopenState);

    $('#modal_reabrir_semana').on('show.bs.modal', function () {
        $motivo.val('');
        $feedback.addClass('d-none').empty();
        updateReopenState();
    });

    $btnConfirm.on('click', function () {
        var motivo = ($motivo.val() || '').trim();
        if (motivo.length < 20) { return; }

        var semana = '';
        if (typeof getSemana === 'function') {
            semana = getSemana();
        } else {
            semana = $('#semana_PHP').val() || $('#semana').val() || '';
        }

        var db = '';
        if (typeof getDb === 'function') {
            db = getDb();
        } else {
            db = $('#seccion').val() || '';
        }

        $btnConfirm.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        $feedback.addClass('d-none').empty();

        $.ajax({
            url: '/api/semanal/reabrir',
            method: 'POST',
            data: {
                db: db,
                semana: semana,
                motivo: motivo,
                _csrf_token: $('meta[name="csrf-token"]').attr('content') || ''
            },
            dataType: 'json'
        }).done(function (resp) {
            if (resp && (resp.respuesta === 'OK' || resp.respuesta === 'BIEN')) {
                $feedback.removeClass('d-none alert-danger').addClass('alert alert-success')
                    .html('<i class="fas fa-check-circle"></i> Semana reabierta correctamente. Recargando...');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                var msg = (resp && (resp.mensaje || resp.message || resp.respuesta)) || 'Error desconocido.';
                $feedback.removeClass('d-none alert-success').addClass('alert alert-danger')
                    .html('<i class="fas fa-times-circle"></i> ' + msg);
                $btnConfirm.prop('disabled', false).html('<i class="fas fa-unlock"></i> Confirmar Reapertura');
            }
        }).fail(function (xhr) {
            var msg = 'Error de conexión. Intente de nuevo.';
            try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch (e) {}
            $feedback.removeClass('d-none alert-success').addClass('alert alert-danger')
                .html('<i class="fas fa-times-circle"></i> ' + msg);
            $btnConfirm.prop('disabled', false).html('<i class="fas fa-unlock"></i> Confirmar Reapertura');
        });
    });
});
</script>
