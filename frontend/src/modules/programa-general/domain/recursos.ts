import type { ConfigRestriccionesPg } from '../../../lib/api/esquemas/programa-general';
import {
  analizarRatio,
  esNoAplica,
  esValorEnBlanco,
  formatearPorcentajeDesdeRatio,
} from '../../../shared/lps/dominio/campos';

export type { ConfigRestriccionesPg };

export type EstadoRecurso = 'liberada' | 'pendiente' | 'no-aplica';

export interface RecursoLiberacion {
  key: string;
  label: string;
  /** Valor tal como lo registra la fila, en el vocabulario de opciones del catálogo cuando coincide. */
  valor: string;
  estado: EstadoRecurso;
  dura: boolean;
}

/**
 * Recursos de liberación con dato real en la fila, en el orden y con las etiquetas del catálogo
 * del contexto (`RestrictionConfigResolver::presentationConfig`). Una columna sin valor no se
 * muestra: el cajón no afirma que un recurso está liberado si la fila no lo dice. El umbral de
 * liberación es el `thresholdPercent` del catálogo, nunca uno propio.
 */
export function recursosConDato(
  fila: Record<string, unknown>,
  config: ConfigRestriccionesPg | null | undefined,
): RecursoLiberacion[] {
  if (!config) return [];

  const recursos: RecursoLiberacion[] = [];
  for (const def of config.restrictions) {
    const crudo = fila[def.key];
    if (esValorEnBlanco(crudo)) continue;

    if (esNoAplica(crudo)) {
      recursos.push({ key: def.key, label: def.label, valor: 'N/A', estado: 'no-aplica', dura: def.hard });
      continue;
    }

    const ratio = analizarRatio(crudo);
    const valorPct = ratio === null ? String(crudo).trim() : formatearPorcentajeDesdeRatio(ratio);
    const valor = def.options.find((opcion) => opcion === valorPct) ?? valorPct;
    const umbral = def.thresholdPercent / 100;
    const liberada = ratio !== null && ratio + 0.0001 >= umbral;
    recursos.push({ key: def.key, label: def.label, valor, estado: liberada ? 'liberada' : 'pendiente', dura: def.hard });
  }
  return recursos;
}
