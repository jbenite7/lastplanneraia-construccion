/**
 * Construcción del contexto completo del cajón para una fila: combina el estado operativo (dato
 * de entrada, resuelto por el módulo que llama), el ITR, la severidad y los indicadores de crisis.
 * Puerto exacto de `buildDrawerContext` y sus auxiliares directos
 * (`isHeaderRow`, `getStateKey`, `getStateLabel`, `getStateActions`, `isRoutineState`,
 * lps_drawer.js:163-186,529-756).
 */

import { type FilaLps, analizarNumero, primerValor } from './campos';
import { calcularItr, tieneBrechaProfunda, type ConfiguracionRestricciones, type ResultadoItr } from './restricciones';
import { consecutivoCanonico, tituloActividad, textoPlano, subcontratista, responsable, esRutaCritica, ratioAvance, avanceVisible, esFilaCabecera } from './filaLps';
import { severidadCajon, type EstadoOperativoLps, type Severidad } from './severidad';

/** Etiquetas de respaldo de Programa General cuando el estado operativo no trae su propio `label`. */
const ETIQUETAS_ESTADO_PG: Record<string, string> = {
  'debe-iniciar': 'Debe iniciar esta semana',
  'actividad-futura': 'Actividad futura',
  'en-curso': 'En curso',
  atrasada: 'Atrasada',
  terminada: 'Terminada',
  header: 'Capítulo',
};

/**
 * Estados "de rutina": no cuentan como "hay algo que atender" para `isActionableState`. Puerto
 * exacto de `ROUTINE_STATE_KEYS` (lps_drawer.js:172-180).
 */
const CLAVES_ESTADO_RUTINA: readonly string[] = [
  'liberated-control',
  'neutral',
  'terminada',
  'prog-lista-para-confirmar',
  'cal-cumplida-control',
  'ps-no-activa',
  'header',
];

/** Puerto exacto de `getStateKey` (lps_drawer.js:539-542). */
export function claveEstado(estado: EstadoOperativoLps | null | undefined): string {
  if (!estado) return '';
  return String(estado.state || estado.key || '').trim();
}

/**
 * Etiqueta visible del estado: prioriza `stateView.label`, luego el mapa de PG, luego el primer
 * segmento (antes de salto de línea) de `estado_operativo`/`Estado` en la fila, y por defecto
 * `"Control"`. Puerto exacto de `getStateLabel` (lps_drawer.js:544-551).
 */
export function etiquetaEstado(fila: FilaLps, estado: EstadoOperativoLps | null | undefined): string {
  if (estado && estado.label) return estado.label;
  const clave = claveEstado(estado);
  if (ETIQUETAS_ESTADO_PG[clave]) return ETIQUETAS_ESTADO_PG[clave];
  const bruto = primerValor(fila, ['estado_operativo', 'Estado']);
  if (bruto) return String(bruto).split('\n')[0].trim();
  return 'Control';
}

/**
 * Lista de textos de acción sugerida: prioriza `stateView.actions`, luego mapea
 * `actionItems`/`compactItems` a su `.text`. Puerto exacto de `getStateActions`
 * (lps_drawer.js:553-563).
 */
export function accionesEstado(estado: EstadoOperativoLps | null | undefined): string[] {
  if (!estado) return [];
  if (Array.isArray(estado.actions)) return estado.actions.filter((a): a is string => Boolean(a));
  if (Array.isArray(estado.actionItems)) {
    return estado.actionItems.map((item) => item?.text).filter((t): t is string => Boolean(t));
  }
  if (Array.isArray(estado.compactItems)) {
    return estado.compactItems.map((item) => item?.text).filter((t): t is string => Boolean(t));
  }
  return [];
}

/** Puerto exacto de `isRoutineState` (lps_drawer.js:565-567). */
export function esEstadoRutina(clave: string): boolean {
  return !clave || CLAVES_ESTADO_RUTINA.includes(clave);
}

export interface ContextoLps {
  moduleKey: string | null;
  rowData: FilaLps;
  stateView: EstadoOperativoLps | null;
  stateKey: string;
  stateLabel: string;
  stateActions: string[];
  itr: ResultadoItr;
  consecutivo: unknown;
  actividad: unknown;
  actividadTexto: string;
  subcontratista: unknown;
  responsable: unknown;
  semanasInicio: number | null;
  progressRatio: number;
  progressDisplay: string;
  isHeader: boolean;
  isCritical: boolean;
  isLiberada: boolean;
  deepGap: boolean;
  isStartedByProgress: boolean;
  isDueOrOverdue: boolean;
  isStartOverdue: boolean;
  isSOS: boolean;
  isPredictiveCrisis: boolean;
  isReactiveCrisis: boolean;
  isCrisis: boolean;
  isActionableState: boolean;
  phase: string | null;
  severity: Severidad;
}

/**
 * Construye el contexto completo del cajón para `fila` en el módulo `moduleKey`, a partir del
 * estado operativo ya resuelto por el llamante (`estado`) y la configuración de restricciones
 * vigente. Puerto exacto de `buildDrawerContext` (lps_drawer.js:703-756), incluidas las reglas de
 * crisis predictiva/reactiva (`isPredictiveCrisis`/`isReactiveCrisis`) tal como están: la reactiva
 * exige atraso >= 10 semanas o `semanasInicio <= -2` — umbrales hardcoded en el original, sin
 * cambios aquí.
 */
export function construirContexto(
  fila: FilaLps,
  moduleKey: string | null,
  estado: EstadoOperativoLps | null,
  config: ConfiguracionRestricciones,
): ContextoLps {
  const stateKey = claveEstado(estado);
  const itr = calcularItr(fila, config);
  const deepGap = tieneBrechaProfunda(itr);
  const semanasInicio = analizarNumero(primerValor(fila, ['Semanas_Inicio', 'semanas_inicio']), null);
  const progressRatio = ratioAvance(fila);
  const isSOS = parseInt(String(fila.alerta_crisis), 10) === 1 || fila.alerta_crisis === true;
  const isCritical = esRutaCritica(fila);
  const isLiberada = itr.isComplete;
  const isStartedByProgress = progressRatio > 0.001;
  const isDueOrOverdue = semanasInicio !== null && semanasInicio <= 0;
  const atraso = analizarNumero(primerValor(fila, ['atraso', 'Atraso']), 0);
  const isActionableState = !esEstadoRutina(stateKey);

  const base = {
    moduleKey,
    rowData: fila,
    stateView: estado,
    stateKey,
    stateLabel: etiquetaEstado(fila, estado),
    stateActions: accionesEstado(estado),
    itr,
    consecutivo: consecutivoCanonico(fila),
    actividad: tituloActividad(fila),
    actividadTexto: textoPlano(tituloActividad(fila)),
    subcontratista: subcontratista(fila),
    responsable: responsable(fila),
    semanasInicio,
    progressRatio,
    progressDisplay: avanceVisible(fila),
    isHeader: esFilaCabecera(fila, stateKey),
    isCritical,
    isLiberada,
    deepGap,
    isStartedByProgress,
    isDueOrOverdue,
    isStartOverdue: semanasInicio !== null && semanasInicio < 0 && !isStartedByProgress,
    isSOS,
    isPredictiveCrisis: false,
    isReactiveCrisis: false,
    isCrisis: false,
    isActionableState,
    // Puerto exacto de `stateView && stateView.phase ? stateView.phase : null`: es una comprobación
    // de verdad (truthy), no de nulidad — un `phase` vacío ('') también cae a `null`.
    phase: estado && estado.phase ? estado.phase : null,
  };

  const severity = severidadCajon({
    stateKey: base.stateKey,
    stateView: base.stateView,
    isCritical: base.isCritical,
    semanasInicio: base.semanasInicio,
    isLiberada: base.isLiberada,
    isStartedByProgress: base.isStartedByProgress,
    isDueOrOverdue: base.isDueOrOverdue,
    progressRatio: base.progressRatio,
    deepGap: base.deepGap,
    isActionableState: base.isActionableState,
    isHeader: base.isHeader,
    isSOS: base.isSOS,
    moduleKey: base.moduleKey,
  });

  const isCrisis = severity === 'critical';
  const isPredictiveCrisis = isCrisis && moduleKey !== 'programacion-semanal' && !isLiberada;
  const isReactiveCrisis = isCrisis
    && moduleKey !== 'programacion-semanal'
    && isLiberada
    && isStartedByProgress
    && isDueOrOverdue
    && (atraso >= 10 || (semanasInicio !== null && semanasInicio <= -2));

  return {
    ...base,
    severity,
    isCrisis,
    isPredictiveCrisis,
    isReactiveCrisis,
  };
}
