/**
 * Centralized DOM selectors for each module (DOM-validated 2026-07-05).
 *
 * Usage:
 *   import { PG_SELECTORS, PS_SELECTORS } from '../../support/moduleSelectors.mjs';
 */

// ─── Programa General ─────────────────────────────────────────────────────────
export const PG_SELECTORS = {
  buttons: {
    leyenda: 'button:has-text("Leyenda")',
    actualizarEjecucion: 'button:has-text("Actualizar Ejecución")',
    descargarCorte: 'button:has-text("Descargar Corte")',
    exportCSV: 'button:has-text("Exportar CSV")',
    recargar: 'button:has-text("Recargar")',
  },
  /** Chips vary by project type. Da Porto (construcción) vs Aeropuerto PC (pre-construcción). */
  chips: {
    construccion: [
      'Con Alerta Restricciones',
      'Debe Iniciar',
      'Actividad Futura',
      'En Curso',
      'Atrasada',
      'Terminada',
      'Sin Datos',
    ],
    preconstruccion: [
      'Con Restricción Pendiente',
      'Por Iniciar',
      'Actividad Futura',
      'En Ejecución',
      'Atrasada',
      'Completada',
      'Sin Datos',
    ],
  },
  table: '[role="treegrid"]',
  columnHeaders: [
    'Id', 'Código Actividad', 'Actividad', 'Semanas Inicio',
    'Fecha Inicio', 'Fecha Fin', 'Crítica', 'Unidad', 'Cantidad PPTO',
    'Ejecutado Teórico', 'Ejecutado Real', 'Estado', 'Liberación Restricciones',
  ],
};

// ─── Programación Semanal ─────────────────────────────────────────────────────
export const PS_SELECTORS = {
  phase: 'text=Fase: Programación de Compromisos',
  buttons: {
    leyenda: 'button:has-text("Ver leyenda de colores")',
    autoprogramar: 'button:has-text("Autoprogramar")',
    agregarActividad: 'button:has-text("Agregar Actividad")',
    confirmarCompromisos: 'button:has-text("Confirmar Compromisos")',
    exportCSV: 'button:has-text("Exportar datos a CSV")',
    recargar: 'button:has-text("Recargar")',
  },
  chips: [
    'RC con restricciones',
    'Ejecución con restricciones',
    'Condiciones Pendientes',
    'Por Comprometer',
    'Lista para Confirmar',
  ],
  rowActions: {
    verDetalle: 'button:has-text("Ver detalle operativo")',
    editar: 'button[aria-label=""]',
    eliminar: 'button[aria-label=""]',
  },
  columns: [
    'Id', 'Actividad', 'Sub-Contratista', 'Responsable AIA',
    'Unidad', 'Cant. PPTO', 'Ejecutado Actual', 'Ejecutado Fin Semana',
    'Cant. Sugerida', 'Compromiso', 'PAC', 'Estado Operativo', 'Acciones', 'X',
  ],
};

// ─── Programación Semanal — Calificación (CNC) ────────────────────────────────
export const CNC_SELECTORS = {
  tableSelector: '#dt_cliente',
  buttons: {
    leyenda: 'button:has-text("Ver leyenda de colores")',
  },
};

// ─── PDC (Plan de Compras) ───────────────────────────────────────────────────
export const PDC_SELECTORS = {
  tabs: {
    familias: 'button:has-text("Familias de obra")',
    paquetes: 'button:has-text("Paquetes de contratacion")',
    planCompras: 'button:has-text("Plan de Compras")',
  },
  buttons: {
    actualizar: 'button:has-text("Actualizar")',
    desglosar: 'button:has-text("Desglosar")',
    verAlertas: 'button:has-text("Ver alertas")',
  },
  rowActions: {
    editar: 'button[aria-label="Editar actividad"]',
    ver: 'button[aria-label="Ver actividad"]',
    eliminar: 'button[aria-label="Eliminar"]',
  },
  chips: [
    'Informacion pendiente',
    'Inicio de contratacion vencido',
    'Contratacion atrasada',
    'Contratacion cerrada tarde',
    'Contratacion cerrada a tiempo',
    'Contratacion en curso',
    'Contratacion pendiente de inicio',
  ],
  columns: [
    'MODALIDAD DE CONTRATACION', 'PAQUETE DE CONTRATACION', 'FAMILIAS ASOCIADAS',
    'ESTADO DEL PROCESO', 'INICIO DEL PROCESO DE CONTRATACIÓN',
    'INICIO EN OBRA SEGUN CRONOGRAMA', 'INICIO EN OBRA PROYECTADO',
    'INICIO EN OBRA REAL', 'OBSERVACIONES',
  ],
};

// ─── Contratos ────────────────────────────────────────────────────────────────
export const CONTRATOS_SELECTORS = {
  handsontable: '#hot-container',
  handsontableMaster: '#hot-container .ht_master.handsontable',
  mobileCards: '#ct-mobile-card-list .ct-mobile-card',
  tableStatus: '#ct-table-status',
  editModal: '#modalEditarContratos',
  buttons: {
    autoDefinir: 'button:has-text("Auto-definir paquetes")',
    cancelar: '#btn_cancelar_contratos',
    guardar: '#btn_guardar_contratos',
  },
  columns: [
    '', 'Código', 'Familia', 'Descripción', 'Fecha de Inicio',
    'Modalidad de contratación', 'Paquetes de contratación asociados',
  ],
  rowActions: {
    editar: 'button[aria-label="Editar paquetes"]',
  },
};

// ─── Listado de Actividades ───────────────────────────────────────────────────
export const LISTADO_SELECTORS = {
  table: '#hot-container .ht_master.handsontable',
  cards: '#la-mobile-card-list .la-mobile-card',
  state: '#la-table-state',
  buttons: {
    cargarExcel: '#btn_cargarActividadesExcel',
    nuevaFamilia: '#btn_nueva_actividad',
    autoGenerar: '#btn_auto_generar_listado',
  },
  columns: [
    'Código', 'Familia', 'Descripción', 'Inicio en obra (cronograma)',
    'Fecha de Inicio', 'Modalidad de contratación',
  ],
  rowActions: {
    eliminar: 'button.eliminar[title="Eliminar"]',
  },
};

// ─── Subcontratistas ──────────────────────────────────────────────────────────
export const SUBCONTRATISTAS_SELECTORS = {
  buttons: {
    nuevo: 'button:has-text("Nuevo"), button:has-text("Agregar")',
  },
  columns: ['Subcontratista', 'NIT', 'Alcance', 'Tipo Proveedor'],
  rowActions: {
    editar: 'button[aria-label=""]',
    eliminar: 'button[aria-label=""]',
  },
};

// ─── Dashboard ────────────────────────────────────────────────────────────────
export const DASHBOARD_SELECTORS = {
  sections: ['Escalamientos', 'Notificaciones', 'Indicadores'],
};

// ─── Common / Shared ──────────────────────────────────────────────────────────
export const COMMON_SELECTORS = {
  breadcrumb: '.breadcrumb',
  weekIndicator: 'text:has-text("Semana")',
  lpsDrawer: 'button:has-text("Abrir Cajón Contextual LPS")',
  lpsDrawerDialog: 'dialog:has(h3:text("Cajón Contextual LPS"))',
  lpsCompilarDigest: 'button:has-text("Compilar Digest de Obra")',
  lpsSimulationMode: 'checkbox:has-text("Modo Simulación")',
  /** Matches "Guia Operativa" in any container (dialog, modal, div) */
  leyendaModal: ':text("Guia Operativa")',
  /** Multiple close button patterns */
  leyendaClose: 'button:has-text("Cerrar"), button:has-text("×"), .modal-footer button, .close, [data-dismiss="modal"]',
  sidebarItem: (name) => `a:has-text("${name}")`,
};

// ─── Admin Panel ──────────────────────────────────────────────────────────────
export const ADMIN_SELECTORS = {
  login: {
    url: '/admin/login',
    inputUsuario: 'textbox[name="Usuario"]',
    inputPassword: 'textbox[name="Contraseña"]',
    buttonIngresar: 'button:has-text("Ingresar")',
  },
  dashboard: {
    url: '/admin/',
    heading: 'h1:has-text("Panel de Control")',
    breadcrumb: 'text=Admin / Inicio',
    sidebarItems: ['Dashboard', 'Proyectos', 'Usuarios', 'Matching Config', 'Catálogo Familias'],
    statCards: ['Proyectos Activos', 'Tamaño de Base de Datos', 'Usuarios en el Sistema'],
  },
  usuarios: {
    url: '/admin/usuarios',
    heading: 'h1:has-text("Usuarios")',
    filters: {
      mostrarInactivos: '#toggleInactiveUsers, label:has-text("Mostrar inactivos")',
      mostrarSinProyectos: '#toggleUsersWithoutProjects, label:has-text("Mostrar sin proyectos")',
    },
    buttons: {
      nuevoUsuario: 'a:has-text("Nuevo Usuario"), button:has-text("Nuevo Usuario")',
      excel: 'button:has-text("Excel")',
    },
    columns: ['ID', 'Nombre', 'Usuario', 'Email', 'Cargo', 'Rol Principal', 'Estado', 'Proyectos', 'Acciones'],
  },
  proyectos: {
    url: '/admin/proyectos',
    heading: 'h1:has-text("Proyectos de Construcción")',
    buttons: {
      nuevoProyecto: 'a:has-text("Nuevo Proyecto"), button:has-text("Nuevo Proyecto")',
      csv: 'button:has-text("CSV")',
      excel: 'button:has-text("Excel")',
      pdf: 'button:has-text("PDF")',
    },
    columns: ['ID', 'Proyecto / Proceso', 'Área', 'Estado', 'Activo', 'Acceso', 'Plan de Compras', 'Acciones'],
  },
  familyCatalog: {
    url: '/admin/matching/family-catalog',
  },
  matchingConfig: {
    url: '/admin/matching/config',
  },
};
