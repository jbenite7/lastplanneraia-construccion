/**
 * Matriz de severidad del cajón contextual LPS: decide `critical`/`attention`/`info`/`neutral`/
 * `normal` a partir del estado operativo ya resuelto por el módulo llamante (PG/PI: `stateAdapter`
 * en `hot.js`; PS: `getStateView`) y de los indicadores de la fila. Puerto exacto de
 * `getWeeklySeverity`/`getPlanSeverity`/`getDrawerSeverity`/`shouldShowEscalation`
 * (lps_drawer.js:599-654).
 *
 * Este módulo NO calcula el `stateKey` en sí (esa matriz — "actividad-futura" vs "en-curso" vs
 * "prog-bloqueo-critico-sin-compromiso", etc. — vive en cada `hot.js` de módulo, fuera del
 * alcance de esta tarea): recibe el estado ya resuelto como dato, tal como lo hacía el drawer
 * legado ("The caller supplies state and restriction config", brief Tarea 3).
 */

export type Severidad = 'critical' | 'attention' | 'info' | 'neutral' | 'normal';

export interface ItemEstadoOperativo {
  text?: string | null;
  status?: string | null;
}

/** Forma mínima de lo que devuelve el `stateAdapter`/`getStateView` de cada módulo. */
export interface EstadoOperativoLps {
  state?: string | null;
  key?: string | null;
  label?: string | null;
  phase?: string | null;
  actions?: (string | null | undefined)[] | null;
  actionItems?: ItemEstadoOperativo[] | null;
  compactItems?: ItemEstadoOperativo[] | null;
}

/** Subconjunto del contexto del cajón que necesita la matriz de severidad semanal (PS). */
export interface EntradaSeveridadSemanal {
  stateKey: string;
  stateView: EstadoOperativoLps | null;
  isCritical: boolean;
}

/** Subconjunto del contexto del cajón que necesita la matriz de severidad de plan (PG/PI). */
export interface EntradaSeveridadPlan {
  stateKey: string;
  semanasInicio: number | null;
  isCritical: boolean;
  isLiberada: boolean;
  isStartedByProgress: boolean;
  isDueOrOverdue: boolean;
  progressRatio: number;
  deepGap: boolean;
  isActionableState: boolean;
}

/** Subconjunto del contexto que necesita `severidadCajon` para elegir entre las dos matrices. */
export interface EntradaSeveridadCajon extends EntradaSeveridadSemanal, EntradaSeveridadPlan {
  isHeader: boolean;
  isSOS: boolean;
  moduleKey: string | null;
}

function itemsEstado(estado: EstadoOperativoLps | null): ItemEstadoOperativo[] {
  const items: ItemEstadoOperativo[] = [];
  if (Array.isArray(estado?.actionItems)) items.push(...estado.actionItems);
  if (Array.isArray(estado?.compactItems)) items.push(...estado.compactItems);
  return items.filter(Boolean);
}

function tieneItemConEstatus(estado: EstadoOperativoLps | null, estatusPermitidos: readonly string[]): boolean {
  return itemsEstado(estado).some((item) => estatusPermitidos.includes(String(item.status ?? '').trim()));
}

/**
 * Matriz de severidad de Programación Semanal (PS): estados de programación (`prog-*`) y de
 * calificación (`cal-*`) — T02-AC "PS program/qualification states". Puerto exacto de
 * `getWeeklySeverity` (lps_drawer.js:599-609).
 */
export function severidadSemanal(entrada: EntradaSeveridadSemanal): Severidad {
  const { stateKey } = entrada;
  if (stateKey === 'prog-bloqueo-critico-sin-compromiso' || stateKey === 'cal-incumplida-critica') return 'critical';
  if (stateKey === 'prog-ejecucion-con-restricciones') return entrada.isCritical ? 'critical' : 'attention';
  if (
    stateKey === 'cal-incumplida'
    || stateKey === 'cal-sin-calificar'
    || stateKey === 'prog-condiciones-pendientes'
    || stateKey === 'prog-sin-compromiso'
  ) {
    return 'attention';
  }
  if (tieneItemConEstatus(entrada.stateView, ['critical'])) return 'critical';
  if (tieneItemConEstatus(entrada.stateView, ['pending', 'partial', 'conflict'])) return 'attention';
  if (stateKey === 'prog-lista-para-confirmar' || stateKey === 'cal-cumplida-control') return 'normal';
  if (stateKey === 'ps-no-activa') return 'neutral';
  return 'normal';
}

/**
 * Matriz de severidad de Programa General / Programación Intermedia (PG/PI): combina el estado
 * operativo con el horizonte en semanas (`semanasInicio`) — T02-AC "every PG/PI horizon case in
 * the authoritative matrix". Puerto exacto de `getPlanSeverity` (lps_drawer.js:611-642), orden de
 * las condiciones preservado tal cual (el orden decide: una fila puede cumplir varias).
 */
export function severidadPlan(entrada: EntradaSeveridadPlan): Severidad {
  const semanas = entrada.semanasInicio;
  const { stateKey } = entrada;

  if (stateKey === 'header') return 'neutral';
  if (stateKey === 'neutral') return 'neutral';
  if (stateKey === 'terminada') return 'normal';
  if (stateKey === 'atrasada' || stateKey === 'blocked-overdue') return 'attention';
  if (stateKey === 'blocked-overdue-critical') return 'critical';
  if (stateKey === 'execution-blocked') return entrada.isCritical ? 'critical' : 'attention';

  if (entrada.isStartedByProgress && !entrada.isLiberada) {
    return entrada.isCritical && entrada.isDueOrOverdue ? 'critical' : 'attention';
  }

  if (semanas !== null && semanas <= 0 && !entrada.isLiberada && entrada.progressRatio < 0.999) {
    return entrada.isCritical ? 'critical' : 'attention';
  }

  if (semanas !== null && semanas <= 0 && entrada.isLiberada && !entrada.isStartedByProgress) return 'attention';
  if (semanas === 1 && !entrada.isLiberada) return 'attention';

  if (semanas !== null && semanas >= 2 && semanas <= 3 && !entrada.isLiberada) {
    return entrada.deepGap ? 'attention' : 'normal';
  }

  if (semanas !== null && semanas >= 4 && semanas <= 6 && !entrada.isLiberada) return 'info';
  if (!entrada.isLiberada && entrada.isActionableState) return 'attention';
  if (entrada.isLiberada || stateKey === 'en-curso' || stateKey === 'liberated-control') return 'normal';

  return 'neutral';
}

/**
 * Severidad del cajón para una fila: capítulo → `neutral` (nunca escalable); crisis SOS activa →
 * `critical` sin importar el estado operativo; si no, delega en la matriz semanal o de plan según
 * el módulo — T02-AC "SOS/header precedence". Puerto exacto de `getDrawerSeverity`
 * (lps_drawer.js:644-649).
 */
export function severidadCajon(entrada: EntradaSeveridadCajon): Severidad {
  if (entrada.isHeader) return 'neutral';
  if (entrada.isSOS) return 'critical';
  if (entrada.moduleKey === 'programacion-semanal') return severidadSemanal(entrada);
  return severidadPlan(entrada);
}

/**
 * `true` si corresponde ofrecer el botón de escalamiento jerárquico (SOS): solo con severidad
 * `critical` y nunca en una fila de capítulo. Puerto exacto de `shouldShowEscalation`
 * (lps_drawer.js:651-654).
 */
export function debeMostrarEscalamiento(entrada: { isHeader: boolean; severity: Severidad }): boolean {
  if (entrada.isHeader) return false;
  return entrada.severity === 'critical';
}

export interface DescripcionSeveridad {
  tono: Severidad;
  etiqueta: string;
}

const ETIQUETAS_SEVERIDAD: Record<Severidad, string> = {
  critical: 'Crítico',
  attention: 'Atención',
  info: 'Info',
  neutral: 'Neutral',
  normal: 'Control',
};

/**
 * Etiqueta textual de una severidad (T02-AC "severity label/... copy"). Puerto del campo `label`
 * de `SEVERITY_VISUALS` (lps_drawer.js:187-223) — deliberadamente **sin** `badgeClass`/
 * `cardClass`/`sidebarClass`/`badgeText`: son clases CSS y un emoji de UI, fuera del dominio puro
 * por instrucción del brief ("Return semantic tones, not CSS classes/colors"); la resolución
 * visual queda para el componente que consuma este resultado.
 */
export function describirSeveridad(severidad: Severidad): DescripcionSeveridad {
  return { tono: severidad, etiqueta: ETIQUETAS_SEVERIDAD[severidad] };
}
