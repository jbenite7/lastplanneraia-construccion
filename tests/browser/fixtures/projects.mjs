import { BASE_URL, puertoDelStackLocal } from './base-url.mjs';

// Re-exportadas: media docena de specs y helpers ya las importan desde aquí.
export { BASE_URL, puertoDelStackLocal };

const appUsername = process.env.E2E_APP_USERNAME;
const appPassword = process.env.E2E_APP_PASSWORD;

if ((appUsername && !appPassword) || (!appUsername && appPassword)) {
  throw new Error(
    'App E2E credential override is incomplete: set both E2E_APP_USERNAME and E2E_APP_PASSWORD.',
  );
}

export const CREDENTIALS = {
  username: appUsername || 'test.A',
  password: appPassword || 'aia2026',
};


export const GLOBAL_TABLES = [
  'auto_program_log',
  'cambios',
  'cic',
  'cip',
  'indicadores_generales',
  'lps_drawer_comentarios',
  'lps_escalamientos',
  'pg_tracking',
  'pi_shared_constraint_links',
  'pi_shared_constraints',
  'profesionales',
  'programa',
  'programa_consolidado',
  'programacion_semanal',
  'semanas_activas',
  'subcontratistas',
];

const ALL_PROJECTS = [
  {
    key: 'construction',
    name: 'Da Porto',
    projectId: 73,
    dbPrefix: 'da_porto',
    area: 'Construccion',
    maxWeek: 1,
    operationalWeek: 1,
    purchasingWeek: 1,
    assistantProgramUniqueId: 101,
    purchasingCapabilities: ['pdc'],
    enabledModules: [
      'programaGeneral',
      'programacionIntermedia',
      'programacionSemanal',
      'profesionales',
      'subcontratistas',
      'indicadores',
      'controlCambios',
      'cnp',
      'cnc',
      'cic',
      'reportesConstruccion',
    ],
    expectedVisibleNav: [
      'planCompras',
      'programacion_semanal',
    ],
    expectedHiddenNav: [],
    expectedSubcontractorTitle: 'Subcontratistas',
    subcontractorHeaders: ['Subcontratista', 'NIT', 'Alcance', 'Tipo Proveedor'],
    providerType: 'Mano de Obra',
    professionalCargo: 'Residente Oficina Técnica',
    hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
    softRestrictions: ['Pdto_Cons', 'Modelo'],
    constructionOnly: true,
  },
  {
    key: 'jmc',
    name: 'Optimización Aeropuerto JMC',
    projectId: 68,
    dbPrefix: 'optimizacionJMC',
    area: 'Construccion',
    maxWeek: 6,
    operationalWeek: 5,
    purchasingWeek: 5,
    assistantProgramUniqueId: 11058,
    purchasingCapabilities: ['pdc'],
    enabledModules: [
      'programaGeneral',
      'programacionIntermedia',
      'programacionSemanal',
      'cnp',
      'cnc',
      'cic',
    ],
    expectedVisibleNav: [
      'planCompras',
      'programacion_semanal',
    ],
    expectedHiddenNav: [],
    expectedSubcontractorTitle: 'Subcontratistas',
    subcontractorHeaders: ['Subcontratista', 'NIT', 'Alcance', 'Tipo Proveedor'],
    providerType: 'Mano de Obra',
    professionalCargo: 'Residente Oficina Técnica',
    hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
    softRestrictions: ['Pdto_Cons', 'Modelo'],
    constructionOnly: true,
  },
  {
    key: 'preconstruction-da-porto',
    name: 'Preconstrucción Da Porto',
    projectId: 76,
    dbPrefix: 'preconstruccion_da_porto_pc',
    area: 'Pre-Construccion',
    maxWeek: 1,
    operationalWeek: 1,
    purchasingWeek: null,
    assistantProgramUniqueId: null,
    purchasingCapabilities: [],
    enabledModules: [
      'programaGeneral',
      'programacionIntermedia',
      'programacionSemanal',
      'cnp',
      'cnc',
    ],
    expectedVisibleNav: [],
    expectedHiddenNav: ['planCompras'],
    expectedSubcontractorTitle: 'Interesados Externos',
    subcontractorHeaders: ['Interesado', 'Identificación', 'Rol/Interés', 'Tipo de Interesado'],
    providerType: 'Consultor',
    professionalCargo: 'Gerente de Proyecto',
    hardRestrictions: ['restriccion_pc_1'],
    softRestrictions: [],
    constructionOnly: false,
  },
  {
    key: 'pc',
    name: 'Aeropuerto Regional PC',
    projectId: 75,
    dbPrefix: 'da_aeropuerto_pc',
    area: 'Pre-Construccion',
    maxWeek: 3,
    operationalWeek: 3,
    purchasingWeek: null,
    purchasingCapabilities: [],
    enabledModules: [
      'programaGeneral',
      'programacionIntermedia',
      'programacionSemanal',
      'profesionales',
      'subcontratistas',
      'indicadores',
      'controlCambios',
    ],
    expectedVisibleNav: ['programa_general', 'programacion_intermedia'],
    expectedHiddenNav: ['planCompras'],
    expectedSubcontractorTitle: 'Interesados Externos',
    subcontractorHeaders: ['Interesado', 'Identificación', 'Rol/Interés', 'Tipo de Interesado'],
    providerType: 'Consultor',
    professionalCargo: 'Gerente de Proyecto',
    hardRestrictions: ['restriccion_pc_1'],
    softRestrictions: ['restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4'],
    constructionOnly: false,
  },
];

/**
 * Proyecto sacrificable para los e2e del Plan de Compras v2.
 *
 * Deliberadamente FUERA de `ALL_PROJECTS`/`PROJECTS`: media docena de specs usan `PROJECTS[0]` o
 * iteran la lista completa, y el sandbox no es un proyecto que valga la pena recorrer — es un
 * destino de escritura. Lo siembra `database/seeds/pdc_e2e_sandbox_project.php`, que además lo
 * resetea en cada corrida (ver `tests/browser/support/pdc-sandbox.mjs`).
 */
export const PDC_SANDBOX_PROJECT = {
  key: 'pdc-sandbox',
  name: 'PDC Sandbox E2E',
  projectId: 990100,
  dbPrefix: 'pdc_sandbox_e2e',
  area: 'Construccion',
  maxWeek: 1,
  operationalWeek: 1,
  purchasingWeek: 1,
  purchasingCapabilities: ['pdc'],
  enabledModules: ['pdc'],
  constructionOnly: true,
};

const requestedProjectKeys = (process.env.E2E_PROJECT_KEYS || '')
  .split(',')
  .map((key) => key.trim())
  .filter(Boolean);

export const PROJECTS = requestedProjectKeys.length > 0
  ? ALL_PROJECTS.filter((project) => requestedProjectKeys.includes(project.key))
  : ALL_PROJECTS.filter((project) => (
    project.key === 'construction'
    || (project.key === 'pc' && process.env.E2E_INCLUDE_PRECONSTRUCTION === '1')
  ));

export const OPERATIONAL_PROJECTS = ALL_PROJECTS.filter((project) => (
  ['construction', 'jmc', 'preconstruction-da-porto'].includes(project.key)
));

export const REPORTS = {
  constructionDownloads: [
    {
      type: 'corte-programacion',
      expectedSheets: ['Corte Programación'],
      expectedHeaders: ['Semana', 'Actividad', 'Fecha Inicio'],
    },
    {
      type: 'restricciones',
      expectedHeaders: ['Actividad', 'Responsable'],
    },
    {
      type: 'compromisos',
      expectedHeaders: ['Compromiso'],
    },
    {
      type: 'consolidado-odc',
      expectedHeaders: ['Orden'],
    },
  ],
  jsonJobs: ['curva-s', 'general', 'restricciones-general', 'pdc', 'subcontratistas', 'run-all'],
};
