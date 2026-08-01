<!-- Modal: Log de Auto-Programación (solo lectura) -->
<div class="modal fade aia-modal cm-modal" id="modal_change_monitor" role="dialog" data-backdrop="static" aria-labelledby="cm-modal-title" aria-describedby="cm-modal-desc">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header cm-modal-header">
                <div class="cm-header-copy">
                    <span class="cm-eyebrow">Programación Semanal</span>
                    <div class="cm-title-row">
                        <h4 class="modal-title" id="cm-modal-title">Actividades Auto-gestionadas</h4>
                        <span class="cm-total-badge" id="cm-count-total-header">0</span>
                    </div>
                    <p id="cm-modal-desc">Registro de actividades que el sistema gestionó automáticamente al cargar la Programación Semanal.</p>
                </div>
                <button type="button" class="close cm-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body cm-modal-body">
                <div class="cm-toolbar">
                    <button type="button" id="cm-btn-refresh" class="aia-btn aia-btn--secondary cm-refresh-btn">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>

                <div class="cm-guidance" role="note">
                    <i class="fas fa-info-circle"></i>
                    <span>Estas actividades fueron procesadas automáticamente. No requieren acción.</span>
                </div>

                <div class="cm-table-shell">
                    <table class="table cm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Actividad</th>
                                <th>Acción</th>
                                <th>Detalle</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="cm-table-body">
                            <tr><td colspan="5" class="cm-empty-state">Cargando registro...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer cm-modal-footer">
                <button type="button" class="aia-btn aia-btn--secondary cm-close-btn" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
