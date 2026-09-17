import { useEffect, useState } from 'react';
import { listarProyectos, seleccionarProyecto } from '../lib/api/proyectos';
import type { ProyectoDisponible } from '../lib/api/esquemas/proyectos';

export type { ProyectoDisponible } from '../lib/api/esquemas/proyectos';

/**
 * Lógica de fetch/CSRF/selección de proyecto, extraída de `SelectorProyecto` en la ronda de
 * arreglos 1 de la Tarea 4 (hallazgo del revisor de código): el panel de cuenta (`MenuCuenta`)
 * necesita la MISMA lista y el MISMO POST — nunca un fetch propio — pero con su propio envoltorio
 * visual (sin `<h1>` de página completa ni `.aia-card`). Aislar el estado aquí es lo que permite
 * que `SelectorProyecto` (pantalla completa) y `PanelCambiarProyecto` (panel del menú de cuenta)
 * compartan una sola implementación sin que ninguno imponga su marcado al otro.
 *
 * Los esquemas y el fetch vivían aquí mismo; se movieron a `lib/api/esquemas/proyectos.ts` y
 * `lib/api/proyectos.ts` en la Tarea 1 de S04, siguiendo el mismo patrón de `lib/api/auth.ts`.
 */
export function useSelectorProyecto(alElegir: () => Promise<void>, csrfToken: string) {
  const [proyectos, setProyectos] = useState<ProyectoDisponible[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [seleccionandoId, setSeleccionandoId] = useState<number | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const respuesta = await listarProyectos();
        setProyectos(respuesta.projects);
      } catch {
        setError('No pudimos cargar tus proyectos. Intenta de nuevo.');
      }
    })();
  }, []);

  async function elegir(proyecto: ProyectoDisponible) {
    setError(null);
    setSeleccionandoId(proyecto.id);

    try {
      const respuesta = await seleccionarProyecto(proyecto.name, csrfToken);

      if (!respuesta.success) {
        setError('No pudimos abrir ese proyecto. Intenta de nuevo.');
        return;
      }

      await alElegir();
    } catch {
      setError('No pudimos abrir ese proyecto. Intenta de nuevo.');
    } finally {
      setSeleccionandoId(null);
    }
  }

  return { proyectos, error, seleccionandoId, elegir };
}
