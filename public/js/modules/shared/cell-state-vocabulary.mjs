/**
 * Shared cell state vocabulary and mapping for Last Planner AIA Design System
 */

export const CELL_STATE = Object.freeze({
  NEUTRAL:   'ds-cell-neutral',
  OK:        'ds-cell-ok',
  ATENCION:  'ds-cell-atencion',
  RIESGO:    'ds-cell-riesgo',
  CRITICO:   'ds-cell-critico',
  BLOQUEADO: 'ds-cell-bloqueado',
  SIN_DATOS: 'ds-cell-sin-datos',
});

/** Map domain-specific state names to the canonical scale */
export const STATE_MAP = Object.freeze({
  // Programa General
  'pg-state-en-curso':           CELL_STATE.OK,
  'pg-state-a-tiempo-en-curso':  CELL_STATE.OK,
  'pg-state-terminada':          CELL_STATE.NEUTRAL,
  'pg-state-debe-iniciar':       CELL_STATE.RIESGO,
  'pg-state-actividad-futura':   CELL_STATE.OK,
  'pg-state-atrasada':           CELL_STATE.CRITICO,
  'pg-state-atrasado':           CELL_STATE.CRITICO,
  'pg-state-sin-datos':          CELL_STATE.SIN_DATOS,
  'pg-state-con-alerta-restricciones': CELL_STATE.ATENCION,
  'pg-state-r1':                 CELL_STATE.ATENCION,
  'pg-state-r2-3':               CELL_STATE.ATENCION,
  'pg-state-r4-6':               CELL_STATE.ATENCION,
  'pg-state-restr-0':            CELL_STATE.BLOQUEADO,
  'pg-state-r0':                 CELL_STATE.BLOQUEADO,

  // Programación Intermedia
  'pi-state-liberated-control':  CELL_STATE.OK,
  'pi-state-alert-1-week':       CELL_STATE.ATENCION,
  'pi-state-alert-2-3-weeks':     CELL_STATE.ATENCION,
  'pi-state-alert-4-6-weeks':     CELL_STATE.NEUTRAL,
  'pi-state-blocked-due':        CELL_STATE.ATENCION,
  'pi-state-execution-blocked':  CELL_STATE.OK,
  'pi-state-blocked-overdue':    CELL_STATE.RIESGO,
  'pi-state-blocked-overdue-critical': CELL_STATE.CRITICO,
  'pi-state-neutral':            CELL_STATE.NEUTRAL,

  // Programación Semanal
  'ps-alert-control':            CELL_STATE.OK,
  'ps-alert-info':               CELL_STATE.NEUTRAL,
  'ps-alert-medium':             CELL_STATE.ATENCION,
  'ps-alert-high':               CELL_STATE.RIESGO,
  'ps-alert-critical':           CELL_STATE.CRITICO,
  'ps-alert-critical-route':     CELL_STATE.CRITICO,
  'ps-alert-neutral':            CELL_STATE.NEUTRAL,

  // PDC
  'pdc-status-info':             CELL_STATE.SIN_DATOS,
  'pdc-critical-delay':          CELL_STATE.CRITICO,
  'pdc-delayed':                 CELL_STATE.RIESGO,
  'pdc-completed-late':          CELL_STATE.ATENCION,
  'pdc-completed-ontime':        CELL_STATE.OK,
  'pdc-active':                  CELL_STATE.OK,
  'pdc-missing':                 CELL_STATE.SIN_DATOS,
  'pdc-not-started':             CELL_STATE.NEUTRAL,
});

/**
 * Returns canonical cell state class for a given module class name or alias
 * @param {string} className 
 * @returns {string}
 */
export function getCanonicalCellState(className) {
  if (!className) return CELL_STATE.NEUTRAL;
  const trimmed = className.trim();
  return STATE_MAP[trimmed] || CELL_STATE.NEUTRAL;
}
