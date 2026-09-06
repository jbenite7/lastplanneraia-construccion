/**
 * Configuración de restricciones (los "7 recursos de liberación" del glosario LPS, ver
 * `GLOSARIO.md` §33) y cálculo del ITR (Termómetro Habilitador de Restricciones). Puerto de
 * `public/js/modules/lps_drawer.js:47-161` (config) y `:488-527,758-851` (ITR y resumen).
 *
 * `ConfiguracionRestricciones` refleja la forma que ya devuelve
 * `GET /api/general/restriction-config` (`src/Controllers/Api/GeneralApiController.php:1650-1673`
 * para el área "Construccion", `:1613-1667` para "Pre-Construccion") — el cajón legado la
 * consume tal cual (`fetchRestrictionConfig`, lps_drawer.js:68-95). Aquí no hay `fetch`: quien
 * llama entrega la configuración ya resuelta (T02-AC-036/037, "both area configurations").
 */

import {
  type FilaLps,
  esNoAplica,
  esValorEnBlanco,
  analizarRatio,
  formatearPorcentajeDesdeRatio,
  primerValor,
  primerValorExistente,
} from './campos';

export type TipoRestriccion = 'hard' | 'soft';

export interface DefinicionRestriccion {
  key: string;
  label: string;
  type: TipoRestriccion;
  /** Umbral 0-100 (porcentaje), como lo entrega la API. */
  threshold: number;
}

export interface ConfiguracionRestricciones {
  area: string;
  restrictions: DefinicionRestriccion[];
  hardRestrictions: string[];
  softRestrictions: string[];
}

/** Restricción ya resuelta contra la configuración: alias de columna y umbral en ratio 0-1. */
export interface RestriccionResuelta {
  key: string;
  aliases: string[];
  label: string;
  /** Umbral en ratio 0-1 (la config lo trae en 0-100). */
  threshold: number;
  tipo: TipoRestriccion;
}

export interface InfoRestriccion {
  key: string;
  label: string;
  raw: unknown;
  ratio: number | null;
  threshold: number;
  applicable: boolean;
  met: boolean;
  progress: number;
}

export interface ResultadoItr {
  /** 0-100, redondeado. */
  porcentaje: number;
  /** 0-1, sin redondear. */
  ratio: number;
  liberadas: number;
  /**
   * Total de restricciones configuradas (no "restricciones aplicables a esta fila": el original
   * usa este mismo nombre, `aplicables`, para el conteo total configurado — ver divergencia en el
   * reporte de la tarea, no se corrige aquí).
   */
  aplicables: number;
  isComplete: boolean;
  items: InfoRestriccion[];
}

/**
 * Configuración de respaldo cuando la API de restricciones no responde. Puerto de
 * `buildDefaultRestrictionConfig` (lps_drawer.js:51-66) — área "Construccion", 5 restricciones
 * duras + 2 blandas.
 */
export function configuracionPorDefecto(): ConfiguracionRestricciones {
  return {
    area: 'Construccion',
    restrictions: [
      { key: 'D_y_E', label: 'Diseños y Especif.', type: 'hard', threshold: 100 },
      { key: 'Materiales', label: 'Materiales', type: 'hard', threshold: 100 },
      { key: 'MdeO', label: 'Mano de Obra', type: 'hard', threshold: 100 },
      { key: 'Equipos', label: 'Equipos', type: 'hard', threshold: 100 },
      { key: 'Predecesora', label: 'Predecesora', type: 'hard', threshold: 50 },
      { key: 'Pdto_Cons', label: 'Procedimiento Constructivo', type: 'soft', threshold: 100 },
      { key: 'Seguimiento', label: 'Seguimiento', type: 'soft', threshold: 100 },
    ],
    hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
    softRestrictions: ['Pdto_Cons', 'Seguimiento'],
  };
}

/**
 * Resuelve un subconjunto de claves (duras o blandas) contra `config.restrictions`: agrega los
 * alias de columna (`key` y `restr_<key>`) y convierte el umbral a ratio 0-1. Si una clave listada
 * en `hardRestrictions`/`softRestrictions` no aparece en `restrictions`, cae a `label = key` y
 * `threshold = 1` (100%) — puerto de `_resolveRestrictionArray` (lps_drawer.js:97-111).
 */
function resolverPorClaves(config: ConfiguracionRestricciones, claves: readonly string[]): RestriccionResuelta[] {
  return claves.map((key) => {
    const definicion = config.restrictions.find((r) => r.key === key) ?? null;
    return {
      key,
      aliases: [key, `restr_${key}`],
      label: definicion ? definicion.label : key,
      threshold: definicion ? definicion.threshold / 100 : 1,
      tipo: definicion ? definicion.type : 'hard',
    };
  });
}

/** Puerto de `getHardRestrictions` (lps_drawer.js:113-116). */
export function restriccionesDuras(config: ConfiguracionRestricciones): RestriccionResuelta[] {
  return resolverPorClaves(config, config.hardRestrictions);
}

/** Puerto de `getSoftRestrictions` (lps_drawer.js:118-121). */
export function restriccionesBlandas(config: ConfiguracionRestricciones): RestriccionResuelta[] {
  return resolverPorClaves(config, config.softRestrictions);
}

/** Puerto de `getAllRestrictions` (lps_drawer.js:123-133): recorre `config.restrictions` directamente. */
export function todasLasRestricciones(config: ConfiguracionRestricciones): RestriccionResuelta[] {
  return config.restrictions.map((r) => ({
    key: r.key,
    aliases: [r.key, `restr_${r.key}`],
    label: r.label,
    threshold: r.threshold / 100,
    tipo: r.type,
  }));
}

/**
 * Estado de una restricción para una fila: sin dato/`N/A` → no aplicable y `met = true` (no
 * bloquea el ITR); con dato → ratio normalizado contra el umbral. Puerto exacto de
 * `getRestrictionInfo` (lps_drawer.js:498-527), incluido el margen de redondeo `+ 0.0001` al
 * comparar contra el umbral.
 */
export function resolverInfoRestriccion(fila: FilaLps, resuelta: RestriccionResuelta): InfoRestriccion {
  const candidato = primerValorExistente(fila, resuelta.aliases);
  const crudo = candidato.valor;

  if (!candidato.encontrado || esNoAplica(crudo)) {
    return {
      key: resuelta.key,
      label: resuelta.label,
      raw: crudo,
      ratio: null,
      threshold: resuelta.threshold,
      applicable: false,
      met: true,
      progress: 1,
    };
  }

  const ratio = esValorEnBlanco(crudo) ? 0 : analizarRatio(crudo);
  const ratioNumerico = ratio === null ? 0 : ratio;
  const progreso = Math.max(0, Math.min(ratioNumerico / resuelta.threshold, 1));
  return {
    key: resuelta.key,
    label: resuelta.label,
    raw: crudo,
    ratio: ratioNumerico,
    threshold: resuelta.threshold,
    applicable: true,
    met: ratioNumerico + 0.0001 >= resuelta.threshold,
    progress: progreso,
  };
}

/**
 * ITR (Indicador/Termómetro de restricciones habilitantes): promedio de progreso ponderado sobre
 * el total configurado (no solo las aplicables) y bandera `isComplete` (todas las restricciones
 * liberadas). Puerto exacto de `calculateITR` (lps_drawer.js:758-774).
 *
 * Caso borde preservado: con 0 restricciones configuradas, `isComplete` se decide por
 * `porcentaje >= 0.999` en vez de por conteo — es la rama "sin restricciones duras aplicables,
 * resultado neutral" (T02-AC pertinente a "no-applicable-hard neutral result").
 */
export function calcularItr(fila: FilaLps, config: ConfiguracionRestricciones): ResultadoItr {
  const resueltas = todasLasRestricciones(config);
  const items = resueltas.map((r) => resolverInfoRestriccion(fila, r));
  const totalConfiguradas = config.restrictions.length;
  const liberadas = items.filter((item) => item.met).length;
  const sumaProgreso = items.reduce((suma, item) => suma + item.progress, 0);
  const porcentajeRatio = totalConfiguradas > 0 ? sumaProgreso / totalConfiguradas : 1;

  return {
    porcentaje: Math.round(porcentajeRatio * 100),
    ratio: porcentajeRatio,
    liberadas,
    aplicables: totalConfiguradas,
    isComplete: totalConfiguradas === 0 ? porcentajeRatio >= 0.999 : liberadas === totalConfiguradas,
    items,
  };
}

/**
 * `true` si alguna restricción aplicable y no cumplida está muy lejos del umbral: por debajo de
 * 0.5 de ratio para `Predecesora`, por debajo de 0.66 para cualquier otra. Puerto exacto de
 * `hasDeepRestrictionGap` (lps_drawer.js:577-584) — los dos umbrales (0.5/0.66) están hardcodeados
 * en el original, se conservan tal cual.
 */
export function tieneBrechaProfunda(itr: ResultadoItr): boolean {
  return itr.items.some((item) => {
    if (!item.applicable || item.met) return false;
    const ratio = item.ratio ?? 0;
    return item.key === 'Predecesora' ? ratio < 0.5 : ratio < 0.66;
  });
}

/**
 * Texto de la(s) restricción(es) pendiente(s) para mostrar en el diagnóstico/SOS/digest: si la
 * fila trae un campo explícito de causa (`Restriccion`, `causa_no_cumplimiento`, `CNC`, `CNP`), se
 * usa tal cual; si no, se listan las restricciones aplicables no cumplidas con su porcentaje.
 * Puerto exacto de `getRestrictionSummary` (lps_drawer.js:488-496).
 */
export function resumenRestricciones(fila: FilaLps, itr: ResultadoItr): string {
  const explicito = primerValor(fila, ['Restriccion', 'causa_no_cumplimiento', 'CNC', 'CNP']);
  if (explicito) return String(explicito);

  const pendientes = itr.items
    .filter((item) => item.applicable && !item.met)
    .map((item) => `${item.label} ${formatearPorcentajeDesdeRatio(item.ratio ?? 0)}`);
  return pendientes.length ? pendientes.join(', ') : 'Sin restricciones habilitantes pendientes';
}

export type TonoRestriccionBlanda = 'exito' | 'advertencia' | 'critico';

export interface VistaRestriccionBlanda {
  label: string;
  percent: number;
  tono: TonoRestriccionBlanda;
}

/**
 * Restricciones blandas (informativas, no bloquean el ITR) con dato registrado, para mostrar en la
 * tarjeta de restricciones. Puerto del bloque de restricciones blandas de `updateITRVisuals`
 * (lps_drawer.js:809-850), **sin DOM**: en vez de construir HTML, devuelve tono semántico
 * (`exito`/`advertencia`/`critico`) — la UI decide clase/color en una tarea posterior (brief:
 * "Return semantic tones, not CSS classes/colors").
 */
export function vistaRestriccionesBlandas(fila: FilaLps, config: ConfiguracionRestricciones): VistaRestriccionBlanda[] {
  const blandas = restriccionesBlandas(config);
  const vista: VistaRestriccionBlanda[] = [];

  blandas.forEach((r) => {
    const valor = primerValor(fila, r.aliases);
    if (valor === undefined || valor === null) return;
    const strVal = String(valor).trim().toUpperCase();
    if (strVal === 'N/A' || strVal === 'NA' || strVal === '') return;

    const ratio = analizarRatio(strVal);
    const percent = ratio === null ? 0 : Math.round(ratio * 100);

    let tono: TonoRestriccionBlanda = 'critico';
    if (percent >= 100) tono = 'exito';
    else if (percent > 0) tono = 'advertencia';

    vista.push({ label: r.label, percent, tono });
  });

  return vista;
}
