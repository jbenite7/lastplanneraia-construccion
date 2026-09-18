import { pedir } from './cliente';
import {
  EsquemaListaProyectos,
  EsquemaResultadoSeleccionProyecto,
  EsquemaSolicitudSeleccionProyecto,
  type ListaProyectos,
  type ResultadoSeleccionProyecto,
} from './esquemas/proyectos';

/**
 * Gateway de `/api/proyectos*` (Tarea 1, S04). Igual que `lib/api/auth.ts`: cada función valida
 * su solicitud antes de enviarla y delega en `pedir()` — el único punto de `fetch` de producción —
 * para el envío y el parseo de la respuesta.
 */

export async function listarProyectos(signal?: AbortSignal): Promise<ListaProyectos> {
  return pedir('/api/proyectos', EsquemaListaProyectos, { signal });
}

export async function seleccionarProyecto(
  name: string,
  csrfToken: string,
): Promise<ResultadoSeleccionProyecto> {
  const body = EsquemaSolicitudSeleccionProyecto.parse({ name });
  return pedir('/api/proyectos/seleccionar', EsquemaResultadoSeleccionProyecto, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(body),
  });
}
