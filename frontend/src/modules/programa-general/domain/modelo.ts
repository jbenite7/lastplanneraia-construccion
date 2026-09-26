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

function calcularRatioTeorico(fechaInicio?: string | null, fechaFin?: string | null, fechaReferencia: Date = new Date()): number {
  if (!fechaInicio || !fechaFin) return 0;
  const fi = new Date(fechaInicio).getTime();
  let ff = new Date(fechaFin).getTime();
  const fs = fechaReferencia.getTime();

  if (isNaN(fi) || isNaN(ff)) return 0;
  if (ff < fi) ff = fi;

  const duracionDias = Math.max(1, Math.floor((ff - fi) / 86400000) + 1);
  const diasTranscurridos = Math.floor((fs - fi) / 86400000);

  if (diasTranscurridos < 1) return 0;
  if (diasTranscurridos > duracionDias) return 1;
  return Math.min(1, Math.max(0, diasTranscurridos / duracionDias));
}

export function normalizarActividades(
  filas: FilaActividadPg[],
  semanaActual?: number,
  fechaReferencia: string | Date = '2026-08-23'
): ActividadUI[] {
  let capituloActual = 'General';
  const hoy = fechaReferencia instanceof Date ? fechaReferencia : new Date(fechaReferencia);

  return filas.map((fila) => {
    const esCapitulo = fila.Titulo === 1;
    if (esCapitulo) {
      const parsedCap = parsearTextoActividad(fila.Actividad);
      capituloActual = parsedCap.titulo || fila.Actividad;
      return {
        ...fila,
        esCapitulo: true,
        capituloNombre: capituloActual,
        avanceRealPct: 0,
        avanceTeoricoPct: 0,
        deltaPct: 0,
        deltaTexto: '-',
        esRutaCritica: false,
        plazoVencido: false,
        diasVencimiento: 0,
      };
    }

    const avanceReal = fila.Ejecutado !== null && fila.Ejecutado !== undefined ? Number(fila.Ejecutado) : 0;
    const avanceRealPct = avanceReal > 1 ? Math.round(avanceReal * 10) / 10 : Math.round(avanceReal * 1000) / 10;

    // Avance teórico provisto por API (Ejecutado_Teorico) o derivado según fórmula canónica
    const ratioTeorico = fila.Ejecutado_Teorico !== null && fila.Ejecutado_Teorico !== undefined
      ? Number(fila.Ejecutado_Teorico)
      : calcularRatioTeorico(fila.Fecha_Inicio, fila.Fecha_Fin, hoy);

    const avanceTeoricoPct = ratioTeorico > 1 ? Math.round(ratioTeorico * 10) / 10 : Math.round(ratioTeorico * 1000) / 10;
    const deltaPct = Math.round((avanceRealPct - avanceTeoricoPct) * 10) / 10;
    const deltaTexto = deltaPct > 0 ? `+${deltaPct}%` : `${deltaPct}%`;

    let plazoVencido = false;
    let diasVencimiento = 0;
    if (fila.Fecha_Fin && avanceRealPct < 100) {
      const fin = new Date(fila.Fecha_Fin);
      if (fin < hoy) {
        plazoVencido = true;
        diasVencimiento = Math.ceil((hoy.getTime() - fin.getTime()) / (1000 * 60 * 60 * 24));
      }
    }

    return {
      ...fila,
      esCapitulo: false,
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

export interface TextoActividadParseado {
  titulo: string;
  subtitulo: string | null;
}

export function parsearTextoActividad(textoCrudo: string | null | undefined): TextoActividadParseado {
  if (!textoCrudo || typeof textoCrudo !== 'string') {
    return { titulo: '', subtitulo: null };
  }

  let texto = textoCrudo.trim();
  let subtitulo: string | null = null;

  // 1. Extraer subtítulo de <small>...</small> si existe
  const smallMatch = texto.match(/<small>(.*?)<\/small>/i);
  if (smallMatch && smallMatch[1]) {
    let sub = smallMatch[1].replace(/<[^>]+>/g, '').trim();
    // Limpiar corchetes exteriores si los tiene: [Capítulo: PRELIMINARES...]
    sub = sub.replace(/^\[\s*/, '').replace(/\s*\]$/, '');
    // Limpiar prefijo 'Capítulo:' o 'Capitulo:' si existe
    sub = sub.replace(/^Cap[ií]tulo:\s*/i, '');
    subtitulo = sub.trim() || null;
    // Remover el bloque <small> del texto original
    texto = texto.replace(/<small>.*?<\/small>/gi, '').trim();
  }

  // 2. Eliminar cualquier otro tag HTML (como <b>, </b>, <br>, etc.)
  let titulo = texto.replace(/<[^>]+>/g, '').trim();

  // 3. Limpiar comas, puntos o guiones finales sobrantes
  titulo = titulo.replace(/[,;.\s]+$/, '').trim();

  return {
    titulo,
    subtitulo,
  };
}

export function formatearFechaObra(fechaIso?: string | null): string {
  if (!fechaIso || typeof fechaIso !== 'string') return '-';
  const trimmed = fechaIso.trim();
  if (!trimmed) return '-';

  // Si ya viene como DD/MM/AAAA devolverlo directo
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(trimmed)) return trimmed;

  // Validar formato YYYY-MM-DD
  const partes = trimmed.split('T')[0].split('-');
  if (partes.length === 3 && partes[0].length === 4) {
    const [yyyy, mm, dd] = partes;
    return `${dd}/${mm}/${yyyy}`;
  }

  return trimmed;
}

export function formatearCantidadPresupuesto(
  cantidad?: number | null,
  unidad?: string | null
): string {
  if (cantidad === null || cantidad === undefined || isNaN(Number(cantidad))) {
    return '-';
  }
  const valor = Number(cantidad).toFixed(1);
  return unidad ? `${valor} ${unidad.trim()}` : valor;
}

