import { construirContexto, type ContextoLps } from '../dominio/contexto';
import { configuracionPorDefecto } from '../dominio/restricciones';
import type { FilaLps } from '../dominio/campos';
import type { EstadoOperativoLps } from '../dominio/severidad';
import type { ContactosLps, LpsActivityContext } from '../estado/LpsDrawerProvider';
import type { TargetHiloParams } from '../api/hilo';

/**
 * Fixtures de contrato de los cuatro consumidores del cajón LPS (Tarea 10, T02-AC-182..186):
 * Programa General (PG), Programación Intermedia (PI), Programación Semanal (PS) y Escalamientos
 * (S25/ESC) — el censo de la Tarea 1 (`tests/test_t02_lps_caller_census.mjs`) fija que son
 * exactamente estos cuatro.
 *
 * `adaptarContextoSintetico` imita, en miniatura, lo que hará el adapter real de cada módulo
 * cuando se integre (fuera de alcance de esta tarea, ver brief §Paso 1: "Do not integrate product
 * modules yet"): toma una fila cruda sintética (`FilaLps`, el mismo tipo dinámico que entrega
 * Handsontable/la API) y un `moduleKey` de dominio, la pasa por `construirContexto()` (Tarea 3) —
 * el único traductor de fila cruda a contexto de dominio — y de ahí arma el `LpsActivityContext`
 * tipado que el provider realmente acepta. La fila cruda NUNCA sobrevive más allá de esta función:
 * no aparece en ningún campo del `LpsActivityContext` devuelto (compilador) y sus claves no viajan
 * en el `target` que llega a `fetch` (verificado en tiempo de ejecución por
 * `fixturesLps.test.tsx`, que compara los parámetros reales contra `queryDeTarget(target)` sin
 * ningún extra).
 */

interface ParametrosAdaptador {
  fila: FilaLps;
  moduleKey: string;
  moduloProvider: LpsActivityContext['module'];
  estado: EstadoOperativoLps | null;
  activityId: number;
  target: TargetHiloParams;
  contacts?: ContactosLps;
  crisis?: LpsActivityContext['crisis'];
}

function adaptarContextoSintetico(params: ParametrosAdaptador): LpsActivityContext {
  const config = configuracionPorDefecto();
  const contexto: ContextoLps = construirContexto(params.fila, params.moduleKey, params.estado, config);

  return {
    target: params.target,
    module: params.moduloProvider,
    activity: {
      id: params.activityId,
      label: contexto.actividadTexto,
      state: {
        key: contexto.stateKey,
        label: contexto.stateLabel,
        phase: contexto.phase,
        actions: contexto.stateActions,
      },
      progress: { ratio: contexto.progressRatio, display: contexto.progressDisplay },
      critical: contexto.isCritical,
      isHeader: contexto.isHeader,
    },
    restrictions: { config, values: {} },
    crisis: params.crisis,
    contacts: params.contacts,
    subcontratista: typeof contexto.subcontratista === 'string' ? contexto.subcontratista : undefined,
    simulado: false,
  };
}

export interface EscenarioLps {
  consumidor: 'PG' | 'PI' | 'PS' | 'S25';
  nombre: string;
  contexto: LpsActivityContext;
}

// --- PG (Programa General): Construcción y Pre-Construcción --------------------------------

const filaPgConstruccion: FilaLps = {
  unique_id: 5001,
  Actividad: 'Fundida de placa piso 3 — Torre B',
  estado_operativo: 'En curso',
  Semanas_Inicio: '2',
  prioridad: 'P2',
  subcontratista: 'Concretos XYZ S.A.S.',
};

const filaPgPreConstruccion: FilaLps = {
  unique_id: 5002,
  Actividad: 'Aprobación de diseño estructural',
  estado_operativo: 'Debe iniciar esta semana',
  Semanas_Inicio: '0',
  subcontratista: 'Estudio de Ingeniería ABC',
};

const escenariosPg: EscenarioLps[] = [
  {
    consumidor: 'PG',
    nombre: 'PG — Construcción',
    contexto: adaptarContextoSintetico({
      fila: filaPgConstruccion,
      moduleKey: 'programa-general',
      moduloProvider: 'PG',
      estado: { state: 'en-curso', label: 'En curso', phase: null, actions: ['Registrar avance'] },
      activityId: 5001,
      target: { consecutivo: 5001, modulo: 'PG' },
    }),
  },
  {
    consumidor: 'PG',
    nombre: 'PG — Pre-Construcción',
    contexto: adaptarContextoSintetico({
      fila: filaPgPreConstruccion,
      moduleKey: 'programa-general',
      moduloProvider: 'PG',
      estado: { state: 'debe-iniciar', label: 'Debe iniciar esta semana', phase: null, actions: [] },
      activityId: 5002,
      target: { consecutivo: 5002, modulo: 'PG' },
    }),
  },
];

// --- PI (Programación Intermedia): futura, brecha profunda, crítica en curso ---------------

const filaPiFutura: FilaLps = {
  unique_id: 5101,
  Actividad: 'Mampostería nivel 6',
  estado_operativo: 'Actividad futura',
  Semanas_Inicio: '6',
};

const filaPiBrechaProfunda: FilaLps = {
  unique_id: 5102,
  Actividad: 'Instalación tubería hidrosanitaria nivel 4',
  estado_operativo: 'En curso',
  Semanas_Inicio: '1',
  Predecesora: '20',
};

const filaPiCriticaEnCurso: FilaLps = {
  unique_id: 5103,
  Actividad: 'Estructura metálica cubierta',
  estado_operativo: 'En curso',
  Semanas_Inicio: '-1',
  prioridad: 'P1',
};

const escenariosPi: EscenarioLps[] = [
  {
    consumidor: 'PI',
    nombre: 'PI — futura',
    contexto: adaptarContextoSintetico({
      fila: filaPiFutura,
      moduleKey: 'programacion-intermedia',
      moduloProvider: 'PI',
      estado: { state: 'actividad-futura', label: 'Actividad futura', phase: null, actions: [] },
      activityId: 5101,
      target: { consecutivo: 5101, modulo: 'PI' },
    }),
  },
  {
    consumidor: 'PI',
    nombre: 'PI — brecha profunda',
    contexto: adaptarContextoSintetico({
      fila: filaPiBrechaProfunda,
      moduleKey: 'programacion-intermedia',
      moduloProvider: 'PI',
      estado: { state: 'en-curso', label: 'En curso', phase: null, actions: ['Resolver predecesora'] },
      activityId: 5102,
      target: { consecutivo: 5102, modulo: 'PI' },
    }),
  },
  {
    consumidor: 'PI',
    nombre: 'PI — crítica en curso',
    contexto: adaptarContextoSintetico({
      fila: filaPiCriticaEnCurso,
      moduleKey: 'programacion-intermedia',
      moduloProvider: 'PI',
      estado: { state: 'en-curso', label: 'En curso', phase: null, actions: [] },
      activityId: 5103,
      target: { consecutivo: 5103, modulo: 'PI' },
    }),
  },
];

// --- PS (Programación Semanal): programación y calificación --------------------------------

const filaPsProgramacion: FilaLps = {
  unique_id: 5201,
  Actividad: 'Vaciado de vigas nivel 2',
  estado_operativo: 'En curso',
  Semanas_Inicio: '0',
};

const filaPsCalificacion: FilaLps = {
  unique_id: 5202,
  Actividad: 'Vaciado de vigas nivel 2',
  estado_operativo: 'Pendiente de calificación',
  Semanas_Inicio: '0',
};

const escenariosPs: EscenarioLps[] = [
  {
    consumidor: 'PS',
    nombre: 'PS — programación',
    contexto: adaptarContextoSintetico({
      fila: filaPsProgramacion,
      moduleKey: 'programacion-semanal',
      moduloProvider: 'PS',
      estado: { state: 'ps-en-curso', label: 'En curso', phase: 'programacion', actions: [] },
      activityId: 5201,
      target: { consecutivo: 5201, modulo: 'PS' },
    }),
  },
  {
    consumidor: 'PS',
    nombre: 'PS — calificación',
    contexto: adaptarContextoSintetico({
      fila: filaPsCalificacion,
      moduleKey: 'programacion-semanal',
      moduloProvider: 'PS',
      estado: { state: 'ps-pendiente-calificar', label: 'Pendiente de calificación', phase: 'calificacion', actions: [] },
      activityId: 5202,
      target: { consecutivo: 5202, modulo: 'PS' },
    }),
  },
];

// --- S25 (Escalamientos): alerta objetivo, terminal, perfil requerido ----------------------
// El target de alerta (`{ alertaId }`) nunca lleva `modulo`: es el otro brazo de la unión
// discriminada `TargetHiloParams` (`api/esquemas.ts:126`), distinto del target de actividad que
// usan PG/PI/PS.

const filaS25AlertaObjetivo: FilaLps = {
  alerta_id: 9001,
  Actividad: 'Atraso reiterado — cimentación torre A',
  alerta_crisis: 1,
};

const filaS25Terminal: FilaLps = {
  alerta_id: 9002,
  Actividad: 'Atraso reiterado — fachada torre B',
  alerta_crisis: 1,
};

const filaS25PerfilRequerido: FilaLps = {
  alerta_id: 9003,
  Actividad: 'Atraso reiterado — instalaciones torre C',
  alerta_crisis: 1,
};

const escenariosS25: EscenarioLps[] = [
  {
    consumidor: 'S25',
    nombre: 'S25 — alerta objetivo',
    contexto: adaptarContextoSintetico({
      fila: filaS25AlertaObjetivo,
      moduleKey: 'escalamientos',
      moduloProvider: 'ESC',
      estado: { state: 'esc-activa', label: 'Alerta activa', phase: null, actions: ['Registrar SOS'] },
      activityId: 9001,
      target: { alertaId: 9001 },
      crisis: { alertId: 9001, active: true, level: 2 },
    }),
  },
  {
    consumidor: 'S25',
    nombre: 'S25 — nivel terminal',
    contexto: adaptarContextoSintetico({
      fila: filaS25Terminal,
      moduleKey: 'escalamientos',
      moduloProvider: 'ESC',
      estado: { state: 'esc-terminal', label: 'Nivel terminal (Gerencia General)', phase: null, actions: [] },
      activityId: 9002,
      target: { alertaId: 9002 },
      crisis: { alertId: 9002, active: true, level: 5 },
    }),
  },
  {
    consumidor: 'S25',
    nombre: 'S25 — perfil requerido',
    contexto: adaptarContextoSintetico({
      fila: filaS25PerfilRequerido,
      moduleKey: 'escalamientos',
      moduloProvider: 'ESC',
      estado: { state: 'esc-activa', label: 'Alerta activa', phase: null, actions: [] },
      activityId: 9003,
      target: { alertaId: 9003 },
      crisis: { alertId: 9003, active: true, level: 1 },
    }),
  },
];

/** Los diez escenarios canónicos de los cuatro consumidores, en el orden fijado por el censo (Tarea 1). */
export const ESCENARIOS_CUATRO_CONSUMIDORES: readonly EscenarioLps[] = [
  ...escenariosPg,
  ...escenariosPi,
  ...escenariosPs,
  ...escenariosS25,
];
