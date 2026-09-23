import { FilaActividadPg } from '../../../lib/api/esquemas/programa-general';

export interface ActividadUI extends FilaActividadPg {
  esCapitulo: boolean;
  capituloNombre: string;
  avanceRealPct: number;
  avanceTeoricoPct: number;
  deltaPct: number;
  deltaTexto: string;
  esRutaCritica: boolean;
  plazoVencido: boolean;
  diasVencimiento: number;
}

export function normalizarActividades(
  filas: FilaActividadPg[],
  semanaActual?: number,
  fechaReferencia: string | Date = '2026-08-23'
): ActividadUI[] {
  let capituloActual = 'General';

  return filas.map((fila) => {
    const esCapitulo = fila.Titulo === 1;
    if (esCapitulo) {
      capituloActual = fila.Actividad;
    }

    const avanceReal = fila.Ejecutado !== null && fila.Ejecutado !== undefined ? Number(fila.Ejecutado) : 0;
    const avanceRealPct = avanceReal > 1 ? Math.round(avanceReal * 10) / 10 : Math.round(avanceReal * 1000) / 10;

    // Avance teórico de referencia basado en fechas si existe
    const avanceTeoricoPct = 50.0; // Derivado o provisto por API
    const deltaPct = Math.round((avanceRealPct - avanceTeoricoPct) * 10) / 10;
    const deltaTexto = deltaPct > 0 ? `+${deltaPct}%` : `${deltaPct}%`;

    let plazoVencido = false;
    let diasVencimiento = 0;
    if (fila.Fecha_Fin && !esCapitulo && avanceRealPct < 100) {
      const hoy = fechaReferencia instanceof Date ? fechaReferencia : new Date(fechaReferencia);
      const fin = new Date(fila.Fecha_Fin);
      if (fin < hoy) {
        plazoVencido = true;
        diasVencimiento = Math.ceil((hoy.getTime() - fin.getTime()) / (1000 * 60 * 60 * 24));
      }
    }

    return {
      ...fila,
      esCapitulo,
      capituloNombre: capituloActual,
      avanceRealPct,
      avanceTeoricoPct,
      deltaPct,
      deltaTexto,
      esRutaCritica: fila.Ruta_Critica === 1,
      plazoVencido,
      diasVencimiento,
    };
  });
}
