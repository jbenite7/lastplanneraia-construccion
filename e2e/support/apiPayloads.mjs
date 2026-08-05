/**
 * API payloads and DB queries per module.
 *
 * Documents the exact POST payloads and SQL queries used in E2E verification.
 * All queries use project_id scoping (global tables architecture).
 *
 * Usage:
 *   import { PG_API, PG_DB, PS_API } from '../../support/apiPayloads.mjs';
 */

// ─── Programa General ─────────────────────────────────────────────────────────
export const PG_API = {
  /** GET or POST: returns { data: [...] } */
  list: { method: 'GET', url: '/api/general/list?db=%DB%&semana=%SEMANA%' },
  /**
   * Update a cell. NOTE (F-001): uses unique_id and Semana (case-sensitive).
   * Payload: { unique_id, campo, valor, semana, db }
   */
  update: {
    method: 'POST',
    url: '/api/general/update',
    payload: { unique_id: '%ID%', campo: '%CAMPO%', valor: '%VALOR%', semana: '%SEMANA%', db: '%DB%' },
  },
};
export const PG_DB = {
  list: 'SELECT * FROM programa_consolidado WHERE project_id = %PID% AND Semana = %S% ORDER BY id_programa',
  byId: 'SELECT * FROM programa_consolidado WHERE project_id = %PID% AND Semana = %S% AND id_programa = %IP%',
  ejecutado:
    'SELECT Ejecutado_Real FROM programa_consolidado WHERE project_id = %PID% AND Semana = %S% AND id_programa = %IP%',
};

// ─── Programación Semanal ─────────────────────────────────────────────────────
export const PS_API = {
  list: { method: 'GET', url: '/api/semanal/list?db=%DB%&semana=%SEMANA%' },
  tnp: { method: 'GET', url: '/api/semanal/tnp-actividades?semana=%SEMANA%' },
  autoprogramar: {
    method: 'POST',
    url: '/api/semanal/save',
    payload: { semana: '%SEMANA%', db: '%DB%', opcion: 'autoprogramar' },
  },
  confirmar: {
    method: 'POST',
    url: '/api/semanal/save',
    payload: { semana: '%SEMANA%', db: '%DB%', opcion: 'confirmar' },
  },
  eliminar: {
    method: 'POST',
    url: '/api/semanal/save',
    payload: { id: '%ID%', semana: '%SEMANA%', db: '%DB%', opcion: 'eliminar', cnc: '%CNC%' },
  },
};
export const PS_DB = {
  list: 'SELECT * FROM programacion_semanal WHERE project_id = %PID% AND Semana = %S% ORDER BY id',
  compromisos: 'SELECT COUNT(*) FROM programacion_semanal WHERE project_id = %PID% AND Semana = %S% AND Compromiso > 0',
};

// ─── CNC (Calificación de No Cumplimiento) ────────────────────────────────────
export const CNC_API = {
  list: { method: 'POST', url: '/api/cnc/list', payload: { semana: '%SEMANA%', db: '%DB%' } },
  reasons: { method: 'POST', url: '/api/cnc/reasons', payload: { categoria: 'Programación' } },
};

// ─── Listado de Actividades · Contratos · PDC v1 ──────────────────────────────
// Podados el 2026-08-04 con el retiro del PDC v1: LISTADO_API/DB, CONTRATOS_API/DB
// y PDC_API/DB apuntaban a rutas y tablas que ya no existen (`/api/listado-actividades/*`,
// `/api/contratos/*`, `/api/pdc/*`, y las tablas `actividades`, `contratos` y `pdc`).
// Cero consumidores al retirarlos: sus dos unicos specs —workflows/pdc-full.spec.mjs y
// workflows/procurement-flow.spec.mjs— cayeron en el mismo commit.
// El sucesor es Plan de Compras v2, con su propia cobertura en tests/browser/pdc-v2-*.spec.mjs.

// ─── Subcontratistas ──────────────────────────────────────────────────────────
export const SUBCONTRATISTAS_API = {
  list: { method: 'POST', url: '/api/subcontratistas/list?db=%DB%', payload: { opcion: 'listar' } },
  create: {
    method: 'POST',
    url: '/api/subcontratistas/save?db=%DB%',
    payload: {
      opcion: 'crear',
      subcontratista: '%NAME%',
      correo_contacto: '%EMAIL%',
      NIT: '%NIT%',
      alcance: '%SCOPE%',
      tipo_proveedor: '%TYPE%',
    },
  },
  update: {
    method: 'POST',
    url: '/api/subcontratistas/save?db=%DB%',
    payload: { opcion: 'guardar_cambios', id: '%ID%', column: 'subcontratista', value: '%VALUE%' },
  },
  delete: {
    method: 'POST',
    url: '/api/subcontratistas/save?db=%DB%',
    payload: { opcion: 'eliminar', Id: '%ID%' },
  },
};

// ─── Dashboard ────────────────────────────────────────────────────────────────
export const DASHBOARD_API = {
  notifications: { method: 'GET', url: '/api/notifications/unread' },
};

// ─── Semanas / Weeks ──────────────────────────────────────────────────────────
export const SEMANA_API = {
  list: { method: 'GET', url: '/api/semanas/list' },
  /**
   * Create new week via legacy endpoint.
   * Payload: { opcion: 'nueva_sem', f_inicio_sem: 'YYYY-MM-DD', _csrf_token: '<token>' }
   * URL: /legacy/funciones_generales/php/nueva_semana.php?db=%DB%
   * `_csrf_token` is required (legacy_require_csrf, formKey `lps_week_admin`);
   * see tests/browser/support/session.mjs#postFormJson, which resolves it
   * automatically for this endpoint.
   */
  create: {
    method: 'POST',
    url: '/legacy/funciones_generales/php/nueva_semana.php?db=%DB%',
    payload: { opcion: 'nueva_sem', f_inicio_sem: '%FECHA%', _csrf_token: '%CSRF%' },
  },
};
export const SEMANA_DB = {
  count: 'SELECT COUNT(*) FROM semanas_activas WHERE project_id = %PID%',
  maxFechaFin: 'SELECT MAX(Fecha_Fin_Sem) FROM semanas_activas WHERE project_id = %PID%',
  semanaExists: 'SELECT COUNT(*) FROM semanas_activas WHERE project_id = %PID% AND Semana = %S%',
};

// ─── Helper: fill URL/payload templates ───────────────────────────────────────
/**
 * Fill template placeholders in a URL or payload object.
 * @param {string|object} template - URL string or payload object with %PLACEHOLDERS%
 * @param {object} vars - Key-value replacements (e.g., { DB: 'da_porto', SEMANA: '2' })
 * @returns {string|object}
 */
export function fillTemplate(template, vars) {
  if (typeof template === 'string') {
    let result = template;
    for (const [key, value] of Object.entries(vars)) {
      result = result.replace(new RegExp(`%${key}%`, 'g'), String(value));
    }
    return result;
  }
  if (typeof template === 'object' && template !== null) {
    const result = {};
    for (const [key, value] of Object.entries(template)) {
      result[key] = fillTemplate(value, vars);
    }
    return result;
  }
  return template;
}