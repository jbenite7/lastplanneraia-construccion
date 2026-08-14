const ALIAS_PERMISO = { P: 'D', U: 'V' };
const EDITABLE_PROPS_SEMANAL = {
  Descripcion: true, Ubicacion: true, Sub_Contratista: true, Responsable_AIA: true,
  Compromiso: true, Ejecutado_Real: true, Categoria_CNC: true, CNC: true, Observaciones_CNC: true,
};

function normalizarPermiso(valor) {
  const permiso = String(valor || '').trim().toUpperCase();
  return ALIAS_PERMISO[permiso] || permiso;
}

const esDirector = (p) => p === 'A' || p === 'D';
const esEditorSemanal = (p) => ['A', 'D', 'R', 'DCV'].includes(p);

export function crearReglasSemanal(contexto) {
  const permiso = normalizarPermiso(contexto.permiso);
  const semana = parseInt(contexto.semana, 10);
  const maxSemana = parseInt(contexto.maxSemana, 10);
  const confirmada = parseInt(contexto.semanalConfirmada, 10) || 0;

  function isUserAllowedToEdit() {
    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return esDirector(permiso);
    }
    return esEditorSemanal(permiso);
  }

  function isPropReadOnly(prop) {
    if (!EDITABLE_PROPS_SEMANAL[prop]) return true;
    // El orden de estas cuatro cláusulas es contrato, no estilo: Ejecutado_Real
    // se resuelve ANTES que la semana histórica a propósito, porque calificar
    // una semana ya cerrada está permitido (el servidor hace lo mismo en
    // LpsWeekEditPolicy::allows con $qualification = true).
    if (prop === 'Ejecutado_Real') {
      return confirmada !== 1 || !esEditorSemanal(permiso);
    }
    if (!isUserAllowedToEdit()) return true;
    if (['Compromiso', 'Sub_Contratista', 'Responsable_AIA'].includes(prop) && confirmada === 1) {
      return true;
    }
    return false;
  }

  return {
    isUserAllowedToEdit,
    isPropReadOnly,
    canManageToolbarActions: isUserAllowedToEdit,
    editableProps: EDITABLE_PROPS_SEMANAL,
  };
}

const esEditorIntermedia = (p) => ['A', 'D', 'R', 'DCV'].includes(p);

export function crearReglasIntermedia(contexto) {
  const permiso = normalizarPermiso(contexto.permiso);
  const semana = parseInt(contexto.semana, 10);
  const maxSemana = parseInt(contexto.maxSemana, 10);
  const confirmada = parseInt(contexto.semanalConfirmada, 10) || 0;
  const editableProps = contexto.editableProps || {};

  function isUserAllowedToEdit() {
    if (confirmada === 1) return false;
    if (Number.isFinite(semana) && Number.isFinite(maxSemana) && (maxSemana - 2) >= semana) {
      return esDirector(permiso);
    }
    return esEditorIntermedia(permiso);
  }

  function puedeEditarCelda({ prop, esHeader, tieneResponsable, esRestriccion }) {
    if (prop === '__shared_selected') return !esHeader;
    const bloqueadaPorResponsable = Boolean(esRestriccion) && !esHeader && tieneResponsable === false;
    return Boolean(editableProps[prop]) && !esHeader && isUserAllowedToEdit() && !bloqueadaPorResponsable;
  }

  return { isUserAllowedToEdit, puedeEditarCelda };
}

if (typeof window !== 'undefined') {
  window.AIAEnablementRules = Object.assign(window.AIAEnablementRules || {}, {
    crearReglasSemanal,
    crearReglasIntermedia,
  });
}
