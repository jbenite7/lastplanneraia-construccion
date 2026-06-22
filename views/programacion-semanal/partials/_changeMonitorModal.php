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
                    <button type="button" id="cm-btn-refresh" class="btn cm-refresh-btn">
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
                <button type="button" class="btn cm-close-btn" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.cm-modal .modal-dialog {
    max-width: min(94vw, 1180px);
}

.cm-modal .modal-content {
    border: 0;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}

.cm-modal-header {
    background: linear-gradient(135deg, #1a3c2a 0%, #1a5633 100%);
    color: #fff;
    padding: 1.5rem 1.75rem 1rem;
    border-bottom: 0;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.cm-header-copy {
    flex: 1;
    min-width: 0;
}

.cm-eyebrow {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.75;
    margin-bottom: 0.15rem;
}

.cm-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.cm-modal-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: #fff;
}

.cm-total-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 28px;
    height: 28px;
    border-radius: 99px;
    padding: 0 10px;
}

.cm-modal-header p {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    opacity: 0.8;
    line-height: 1.4;
}

.cm-close {
    color: #fff;
    opacity: 0.7;
    font-size: 1.75rem;
    font-weight: 300;
    line-height: 1;
    padding: 0;
    margin: -0.25rem 0 0 auto;
    background: none;
    border: 0;
    cursor: pointer;
    margin-inline-end: 0.65rem;
}

.cm-close:hover {
    opacity: 1;
}

.cm-modal-body {
    background: #F4F1EA;
    padding: 1.25rem 1.75rem;
}

.cm-toolbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.cm-refresh-btn {
    background: #fff;
    border: 1px solid #d1d5db;
    color: #374151;
    font-size: 0.78rem;
    padding: 0.3rem 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}

.cm-refresh-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.cm-guidance {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #5f6b64;
    background: #e8ede9;
    padding: 0.6rem 0.85rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.cm-guidance i {
    margin-top: 0.1rem;
    flex-shrink: 0;
}

.cm-table-shell {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.cm-table {
    margin: 0;
    width: 100%;
}

.cm-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.55rem 0.75rem;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.cm-table tbody td {
    font-size: 0.82rem;
    color: #1f2937;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.cm-table tbody tr:last-child td {
    border-bottom: 0;
}

.cm-table tbody tr:hover {
    background: #f8fafc;
}

.cm-row-comprometer {
    border-left: 3px solid #16a34a;
}

.cm-row-descomprometer {
    border-left: 3px solid #dc2626;
}

.cm-row-insert_cnp {
    border-left: 3px solid #f59e0b;
}

.cm-detail-cell {
    max-width: 380px;
    white-space: normal;
    word-break: break-word;
}

.cm-empty-state {
    text-align: center;
    color: #94a3b8;
    font-style: italic;
    padding: 2rem !important;
}

.cm-modal-footer {
    background: #FAFAFA;
    border-top: 1px solid #e5e7eb;
    padding: 0.75rem 1.75rem;
    display: flex;
    justify-content: flex-end;
}

.cm-close-btn {
    background: #e5e7eb;
    border: 0;
    color: #374151;
    font-size: 0.82rem;
    padding: 0.4rem 1.25rem;
    border-radius: 6px;
    cursor: pointer;
}

.cm-close-btn:hover {
    background: #d1d5db;
}

.cm-badge-btn {
    position: relative;
}

.cm-badge-count {
    position: absolute;
    top: -6px;
    right: -6px;
    font-size: 0.65rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 99px;
    padding: 0 5px;
}

@media (max-width: 576px) {
    .cm-modal .modal-dialog {
        max-width: 100vw;
        margin: 0;
    }
    .cm-modal .modal-content {
        border-radius: 0;
        min-height: 100vh;
    }
    .cm-table thead { display: none; }
    .cm-table tbody td {
        display: block;
        padding: 0.35rem 0.75rem;
        border: 0;
    }
    .cm-table tbody td::before {
        content: attr(data-label);
        display: block;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #94a3b8;
        margin-bottom: 0.1rem;
    }
    .cm-table tbody tr {
        display: block;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
}
</style>
