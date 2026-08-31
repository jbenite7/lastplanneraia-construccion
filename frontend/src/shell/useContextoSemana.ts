import { useId, useState } from 'react';
import { pedir } from '../lib/api/cliente';
import {
  EsquemaRespuestaContextoSemana,
  EsquemaRespuestaCrearSemana,
  EsquemaRespuestaEliminarSemana,
  type SemanaActiva,
} from '../lib/api/esquemas/contexto';

/**
 * Lógica de selección/crear/eliminar semana, separada de `ContextoSemana` (spec T01 §11 y Tarea
 * 5): ninguna mutación reintenta automáticamente, y tras crear/eliminar el estado se refresca
 * completo vía `recargar()` — el `week` que devuelve cada endpoint se ignora a propósito para
 * pintar, igual que ya hace `useSelectorProyecto` con el proyecto ("no optimistic update").
 */
export function useContextoSemana(csrfToken: string, recargar: () => Promise<void>) {
  const [seleccionando, setSeleccionando] = useState(false);
  const [creando, setCreando] = useState(false);
  const [eliminando, setEliminando] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const idDialogoCrear = useId();

  async function seleccionar(semana: number) {
    setError(null);
    setSeleccionando(true);
    try {
      await pedir('/context/week', EsquemaRespuestaContextoSemana, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ semana }),
      });
      await recargar();
    } catch {
      setError('No pudimos cambiar de semana. Intenta de nuevo.');
    } finally {
      setSeleccionando(false);
    }
  }

  async function crear(startsOn: string): Promise<boolean> {
    setError(null);
    setCreando(true);
    try {
      await pedir('/api/context/weeks/create', EsquemaRespuestaCrearSemana, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ startsOn }),
      });
      await recargar();
      return true;
    } catch (causa) {
      setError(mensajeDeCreacion(causa));
      return false;
    } finally {
      setCreando(false);
    }
  }

  async function eliminarUltima(semana: number): Promise<boolean> {
    setError(null);
    setEliminando(true);
    try {
      await pedir('/api/context/weeks/delete-last', EsquemaRespuestaEliminarSemana, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ week: semana }),
      });
      await recargar();
      return true;
    } catch {
      setError('No pudimos eliminar la semana. Intenta de nuevo.');
      return false;
    } finally {
      setEliminando(false);
    }
  }

  return { seleccionando, creando, eliminando, error, idDialogoCrear, seleccionar, crear, eliminarUltima };
}

function mensajeDeCreacion(causa: unknown): string {
  const codigo = causa && typeof causa === 'object' && 'codigo' in causa ? (causa as { codigo?: string }).codigo : null;
  const mensaje = causa && typeof causa === 'object' && 'message' in causa ? (causa as { message?: string }).message : null;

  if (codigo === 'CIC_PENDIENTE' || codigo === 'SEMANA_NO_CONFIRMADA' || codigo === 'PROGRAMA_MAESTRO_VACIO') {
    return mensaje ?? 'No se puede crear la semana todavía.';
  }

  return 'No pudimos crear la semana. Intenta de nuevo.';
}

export type { SemanaActiva };
