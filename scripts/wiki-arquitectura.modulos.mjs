// Manifiesto de módulos de la aplicación. Lo lee scripts/wiki-arquitectura.mjs.
//
// `rutas` son PREFIJOS: una ruta casa si es igual al prefijo o empieza por
// `<prefijo>/`. Gana el prefijo más largo, así que '/api/pdc/auto' se lleva lo
// suyo aunque '/api/pdc' también case. Toda ruta de public/index.php debe casar
// con exactamente un módulo: el generador falla si alguna queda huérfana.
//
// `capacidades` son claves del mapa que devuelve App\Security\RbacManager::getCapabilities().
// `flujo`: 'lps' | 'pdc' | 'ambos' | null.

export const MODULOS = [
  {
    slug: 'autenticacion',
    titulo: 'Autenticación',
    areas: ['rbac', 'arquitectura'],
    flujo: null,
    rutas: ['/', '/login', '/logout', '/password', '/dev/entrar', '/_aia/operacion/7f3c9b',
      '/api/session', '/api/auth'],
    capacidades: [],
    nota: 'La puerta de servicio /dev/entrar solo se registra en desarrollo. '
      + '/_aia/operacion/7f3c9b es la ruta secreta de acceso en mantenimiento '
      + '(MaintenanceMode::SECRET_PATH, ver src/Core/MaintenanceMode.php); '
      + 'sirve el mismo LoginController. '
      + '/api/session y /api/auth/* son la sesión JSON del shell React (2026-08-28): '
      + 'SessionApiController y AuthApiController.',
  },
  {
    slug: 'selector-de-proyectos',
    titulo: 'Selector de proyectos',
    areas: ['rbac', 'arquitectura'],
    flujo: null,
    rutas: ['/proyectos', '/proyecto', '/api/proyectos'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'programa-general',
    titulo: 'Programa General',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programa-general', '/api/general', '/api/pg'],
    capacidades: ['canManageGeneralProgram', 'canEditPastGeneralProgram'],
    nota: '',
  },
  {
    slug: 'cronograma',
    titulo: 'Actualizar cronograma',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programa-general-actualizar'],
    capacidades: ['canManageGeneralProgram'],
    nota: '',
  },
  {
    slug: 'programacion-intermedia',
    titulo: 'Programación Intermedia',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-intermedia', '/api/pi'],
    capacidades: ['canManageMediumTermProgram', 'canEditConstraints'],
    nota: '',
  },
  {
    slug: 'programacion-semanal',
    titulo: 'Programación Semanal',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal', '/api/semanal'],
    capacidades: ['canManageWeeklyProgram', 'canManageWeeks'],
    nota: '',
  },
  {
    slug: 'submodulo-cnp',
    titulo: 'CNP — Causas de No Programación',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cnp', '/api/cnp'],
    capacidades: ['canManageWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'submodulo-cnc',
    titulo: 'CNC — Causas de No Cumplimiento',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cnc', '/api/cnc'],
    capacidades: ['canManageWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'submodulo-cic',
    titulo: 'CIC — Cumplimiento de Actividades',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/programacion-semanal/cic', '/api/cic'],
    capacidades: ['canManageWeeklyProgram'],
    nota: '',
  },
  {
    slug: 'plan-de-compras',
    titulo: 'Plan de Compras v2',
    areas: ['pdc', 'arquitectura'],
    flujo: 'pdc',
    rutas: ['/plan-compras'],
    capacidades: ['canManagePdC'],
    nota: 'SPA React en pdc-app/, bundle en public/pdc-app/. Sub-router por hash.',
  },
  // Listado de Actividades, Contratos y el PDC v1 (`/pdc`, `/api/pdc/*`) se eliminaron el
  // 2026-08-04. Su sucesor es Plan de Compras v2, arriba.
  {
    slug: 'profesionales',
    titulo: 'Profesionales',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/profesionales', '/api/profesionales'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'subcontratistas',
    titulo: 'Subcontratistas',
    areas: ['lps', 'arquitectura'],
    flujo: 'ambos',
    rutas: ['/subcontratistas', '/api/subcontratistas'],
    capacidades: ['canManagePdC'],
    nota: 'Figuraba bajo `canManageContracts`, que el 2026-08-10 se colapsó en `canManagePdC` por ser su alias exacto.',
  },
  {
    slug: 'control-de-cambios',
    titulo: 'Control de Cambios',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/control-cambios', '/api/control-cambios'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'indicadores',
    titulo: 'Indicadores LPS',
    areas: ['lps', 'bi', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/indicadores', '/api/indicadores'],
    capacidades: [],
    nota: 'No se gobierna por capacidad RBAC: el control real es `authorizePermission(\'lps.indicadores.ver\')` en `IndicadoresController:27`. Antes declaraba `canSeeReports`, que valia true para los diez roles y no restringia nada; se retiro el 2026-08-10.',
  },
  {
    slug: 'torre-de-control-bi',
    titulo: 'Torre de Control BI',
    areas: ['bi', 'arquitectura'],
    flujo: 'ambos',
    rutas: ['/bi', '/api/bi'],
    capacidades: [],
    nota: 'Sin capacidad RBAC que lo gobierne. Antes declaraba `canSeeReports`, retirada el 2026-08-10 por ser inerte. No se sustituyo por otra porque no se encontro un guard por capacidad en `src/Controllers/Bi/`: el alcance por proyecto lo aplica `BiProjectScope`. Verificar antes de apoyarse en esta linea.',
  },
  {
    slug: 'integracion',
    titulo: 'Integración de reportes',
    areas: ['datos', 'arquitectura'],
    flujo: null,
    rutas: ['/reportes'],
    capacidades: [],
    nota: 'Sin capacidad RBAC que lo gobierne. Antes declaraba `canSeeReports`, retirada el 2026-08-10 por ser inerte. No se encontro guard por capacidad en `src/Controllers/Integracion/`. Verificar antes de apoyarse en esta linea.',
  },
  {
    slug: 'escalamientos-y-crisis',
    titulo: 'Escalamientos, crisis y avisos',
    areas: ['lps', 'arquitectura'],
    flujo: 'lps',
    rutas: ['/dashboard', '/api/lps', '/api/notifications'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'nucleo-y-runtime',
    titulo: 'Núcleo, sesión y runtime',
    areas: ['arquitectura', 'design-system'],
    flujo: null,
    rutas: ['/session', '/context', '/runtime'],
    capacidades: [],
    nota: '',
  },
  {
    slug: 'legado',
    titulo: 'Carril legado',
    areas: ['arquitectura'],
    flujo: null,
    rutas: ['/legacy'],
    capacidades: [],
    nota: 'Rutas que hacen require_once de scripts procedurales: servicios y tablas '
      + 'saldrán indeterminados por diseño.',
  },
  {
    slug: 'panel-admin',
    titulo: 'Panel de administración',
    areas: ['admin', 'arquitectura'],
    flujo: null,
    rutas: [],
    capacidades: [],
    nota: 'Mini-app aislada con su propio front controller (admin/index.php) y su propio '
      + 'router. Ninguna de sus rutas pasa por public/index.php, por eso la zona generada '
      + 'de rutas queda vacía a propósito.',
  },
  {
    slug: 'laboratorio-design-system',
    titulo: 'Laboratorio del design system',
    areas: ['design-system', 'arquitectura'],
    flujo: null,
    rutas: ['/internal'],
    capacidades: ['internal.design-system.view'],
    nota: 'La capacidad real es la constante RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW; '
      + 'si el valor de la constante cambia, hay que actualizar esta clave.',
  },
];
