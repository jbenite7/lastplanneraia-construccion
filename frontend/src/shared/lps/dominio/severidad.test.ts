import { describe, expect, test } from 'vitest';
import {
  severidadSemanal,
  severidadPlan,
  severidadCajon,
  debeMostrarEscalamiento,
  describirSeveridad,
  type EntradaSeveridadPlan,
  type EntradaSeveridadSemanal,
  type EntradaSeveridadCajon,
} from './severidad';

const planBase: EntradaSeveridadPlan = {
  stateKey: '',
  semanasInicio: null,
  isCritical: false,
  isLiberada: false,
  isStartedByProgress: false,
  isDueOrOverdue: false,
  progressRatio: 0,
  deepGap: false,
  isActionableState: false,
};

const semanalBase: EntradaSeveridadSemanal = {
  stateKey: '',
  stateView: null,
  isCritical: false,
};

/**
 * Matriz PG/PI completa (T02-AC "every PG/PI horizon case in the authoritative matrix"): un caso
 * por rama de `getPlanSeverity` (lps_drawer.js:611-642), en el mismo orden en que el original las
 * evalúa.
 */
describe('severidadPlan — matriz PG/PI por horizonte', () => {
  test.each<[string, Partial<EntradaSeveridadPlan>, string]>([
    ['header', { stateKey: 'header' }, 'neutral'],
    ['neutral', { stateKey: 'neutral' }, 'neutral'],
    ['terminada', { stateKey: 'terminada' }, 'normal'],
    ['atrasada', { stateKey: 'atrasada' }, 'attention'],
    ['blocked-overdue', { stateKey: 'blocked-overdue' }, 'attention'],
    ['blocked-overdue-critical', { stateKey: 'blocked-overdue-critical' }, 'critical'],
    ['execution-blocked + P1', { stateKey: 'execution-blocked', isCritical: true }, 'critical'],
    ['execution-blocked sin P1', { stateKey: 'execution-blocked', isCritical: false }, 'attention'],
    ['iniciada por avance, no liberada, P1 y vencida', { isStartedByProgress: true, isLiberada: false, isCritical: true, isDueOrOverdue: true }, 'critical'],
    ['iniciada por avance, no liberada, sin P1', { isStartedByProgress: true, isLiberada: false, isCritical: false }, 'attention'],
    ['debe iniciar (semanas<=0), no liberada, avance<0.999, P1', { semanasInicio: 0, isLiberada: false, progressRatio: 0, isCritical: true }, 'critical'],
    ['debe iniciar (semanas<=0), no liberada, avance<0.999, sin P1', { semanasInicio: -1, isLiberada: false, progressRatio: 0, isCritical: false }, 'attention'],
    ['vencida (semanas<=0), liberada, sin avance iniciado', { semanasInicio: -3, isLiberada: true, isStartedByProgress: false }, 'attention'],
    ['una semana para iniciar, no liberada', { semanasInicio: 1, isLiberada: false }, 'attention'],
    ['horizonte 2-3 semanas, no liberada, con brecha profunda', { semanasInicio: 2, isLiberada: false, deepGap: true }, 'attention'],
    ['horizonte 2-3 semanas, no liberada, sin brecha profunda', { semanasInicio: 3, isLiberada: false, deepGap: false }, 'normal'],
    ['horizonte 4-6 semanas, no liberada', { semanasInicio: 5, isLiberada: false }, 'info'],
    ['no liberada, estado accionable (fuera de rutina), sin horizonte', { isLiberada: false, isActionableState: true }, 'attention'],
    ['liberada, sin más condiciones', { isLiberada: true }, 'normal'],
    ['en-curso', { stateKey: 'en-curso' }, 'normal'],
    ['liberated-control', { stateKey: 'liberated-control' }, 'normal'],
    ['ninguna condición aplica -> neutral por defecto', {}, 'neutral'],
  ])('%s', (_titulo, parcial, esperado) => {
    expect(severidadPlan({ ...planBase, ...parcial })).toBe(esperado);
  });
});

/**
 * Estados de Programación Semanal (T02-AC "PS program/qualification states"): `prog-*` es la fase
 * de programación, `cal-*` es la de calificación posterior.
 */
describe('severidadSemanal — estados de programación y calificación (PS)', () => {
  test.each<[string, Partial<EntradaSeveridadSemanal>, string]>([
    ['prog-bloqueo-critico-sin-compromiso', { stateKey: 'prog-bloqueo-critico-sin-compromiso' }, 'critical'],
    ['cal-incumplida-critica', { stateKey: 'cal-incumplida-critica' }, 'critical'],
    ['prog-ejecucion-con-restricciones + P1', { stateKey: 'prog-ejecucion-con-restricciones', isCritical: true }, 'critical'],
    ['prog-ejecucion-con-restricciones sin P1', { stateKey: 'prog-ejecucion-con-restricciones', isCritical: false }, 'attention'],
    ['cal-incumplida', { stateKey: 'cal-incumplida' }, 'attention'],
    ['cal-sin-calificar', { stateKey: 'cal-sin-calificar' }, 'attention'],
    ['prog-condiciones-pendientes', { stateKey: 'prog-condiciones-pendientes' }, 'attention'],
    ['prog-sin-compromiso', { stateKey: 'prog-sin-compromiso' }, 'attention'],
    ['prog-lista-para-confirmar', { stateKey: 'prog-lista-para-confirmar' }, 'normal'],
    ['cal-cumplida-control', { stateKey: 'cal-cumplida-control' }, 'normal'],
    ['ps-no-activa', { stateKey: 'ps-no-activa' }, 'neutral'],
    ['estado desconocido sin items -> normal por defecto', { stateKey: 'algo-nuevo' }, 'normal'],
  ])('%s', (_titulo, parcial, esperado) => {
    expect(severidadSemanal({ ...semanalBase, ...parcial })).toBe(esperado);
  });

  test('estado desconocido con un item de estatus "critical" en actionItems -> critical', () => {
    expect(severidadSemanal({
      ...semanalBase,
      stateKey: 'algo-nuevo',
      stateView: { actionItems: [{ status: 'critical', text: 'x' }] },
    })).toBe('critical');
  });

  test('estado desconocido con un item "pending"/"partial"/"conflict" en compactItems -> attention', () => {
    expect(severidadSemanal({
      ...semanalBase,
      stateKey: 'algo-nuevo',
      stateView: { compactItems: [{ status: 'pending', text: 'x' }] },
    })).toBe('attention');
  });
});

/**
 * Precedencia SOS/cabecera (T02-AC "SOS/header precedence"): una fila de capítulo nunca escala
 * aunque venga marcada como crisis; una crisis SOS activa siempre es crítica, sin importar el
 * estado operativo real de la fila.
 */
describe('severidadCajon — precedencia SOS/cabecera', () => {
  const cajonBase: EntradaSeveridadCajon = {
    ...planBase,
    ...semanalBase,
    isHeader: false,
    isSOS: false,
    moduleKey: 'programa-general',
  };

  test('cabecera siempre neutral, incluso con isSOS true', () => {
    expect(severidadCajon({ ...cajonBase, isHeader: true, isSOS: true, stateKey: 'atrasada' })).toBe('neutral');
  });

  test('SOS activo es crítico aunque el estado operativo diga "terminada"', () => {
    expect(severidadCajon({ ...cajonBase, isSOS: true, stateKey: 'terminada' })).toBe('critical');
  });

  test('sin cabecera ni SOS, PS delega en la matriz semanal', () => {
    expect(severidadCajon({ ...cajonBase, moduleKey: 'programacion-semanal', stateKey: 'prog-sin-compromiso' })).toBe('attention');
  });

  test('sin cabecera ni SOS, PG/PI delega en la matriz de plan', () => {
    expect(severidadCajon({ ...cajonBase, moduleKey: 'programa-general', stateKey: 'atrasada' })).toBe('attention');
  });
});

describe('debeMostrarEscalamiento', () => {
  test('solo con severidad crítica y sin ser cabecera', () => {
    expect(debeMostrarEscalamiento({ isHeader: false, severity: 'critical' })).toBe(true);
    expect(debeMostrarEscalamiento({ isHeader: false, severity: 'attention' })).toBe(false);
    expect(debeMostrarEscalamiento({ isHeader: true, severity: 'critical' })).toBe(false);
  });
});

describe('describirSeveridad — etiqueta y tono (severity label/tone copy)', () => {
  test.each<[string, string]>([
    ['critical', 'Crítico'],
    ['attention', 'Atención'],
    ['info', 'Info'],
    ['neutral', 'Neutral'],
    ['normal', 'Control'],
  ])('%s -> %s', (severidad, etiqueta) => {
    const descripcion = describirSeveridad(severidad as Parameters<typeof describirSeveridad>[0]);
    expect(descripcion).toEqual({ tono: severidad, etiqueta });
  });
});
