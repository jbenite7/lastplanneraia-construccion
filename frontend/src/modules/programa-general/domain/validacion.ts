export interface DesviacionFisica {
  porcentajeDelta: number;
  magnitudDelta: number | null;
  textoFormateado: string;
  esNegativo: boolean;
}

export function calcularDesviacionFisica(
  realRatio: number,
  teoricoRatio: number,
  cantidadPpto?: number | null,
  unidad?: string | null
): DesviacionFisica {
  const realPct = realRatio * 100;
  const teorPct = teoricoRatio * 100;
  const deltaPct = Math.round((realPct - teorPct) * 10) / 10;
  const esNegativo = deltaPct < 0;

  if (unidad && unidad !== '%' && cantidadPpto !== null && cantidadPpto !== undefined && cantidadPpto > 0) {
    const magnitudDelta = Math.round((cantidadPpto * (realRatio - teoricoRatio)) * 10) / 10;
    const signo = magnitudDelta > 0 ? '+' : '';
    const textoFormateado = `${deltaPct > 0 ? '+' : ''}${deltaPct.toFixed(1)}% (${signo}${magnitudDelta} ${unidad})`;
    return {
      porcentajeDelta: deltaPct,
      magnitudDelta,
      textoFormateado,
      esNegativo,
    };
  }

  const textoFormateado = `${deltaPct > 0 ? '+' : ''}${deltaPct.toFixed(1)}%`;
  return {
    porcentajeDelta: deltaPct,
    magnitudDelta: null,
    textoFormateado,
    esNegativo,
  };
}

export interface DatosBorradorActividad {
  Fecha_Inicio?: string | null;
  Fecha_Fin?: string | null;
  unidad?: string | null;
  cantidad_ppto?: number | null;
  ejecutadoVisible?: number | null;
}

export function validarBorradorActividad(datos: DatosBorradorActividad): string | null {
  if (datos.Fecha_Inicio && datos.Fecha_Fin) {
    if (new Date(datos.Fecha_Fin) < new Date(datos.Fecha_Inicio)) {
      return 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    }
  }

  if (datos.cantidad_ppto !== null && datos.cantidad_ppto !== undefined && datos.cantidad_ppto < 0) {
    return 'La cantidad de presupuesto no puede ser negativa.';
  }

  if (datos.ejecutadoVisible !== null && datos.ejecutadoVisible !== undefined && datos.ejecutadoVisible < 0) {
    return 'El avance no puede ser un valor negativo.';
  }

  return null;
}

export function normalizarBorradorActividad<T extends { unidad?: string | null; cantidad_ppto?: number | null }>(draft: T): T {
  if (draft.unidad === '%') {
    return { ...draft, cantidad_ppto: null };
  }
  return draft;
}
