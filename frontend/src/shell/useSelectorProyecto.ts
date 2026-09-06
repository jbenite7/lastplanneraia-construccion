import { useEffect, useState } from 'react';
import { z } from 'zod';
import { pedir } from '../lib/api/cliente';

const EsquemaListaProyectos = z.object({
  projects: z.array(z.object({
    id: z.number().int(),
    name: z.string(),
    role: z.string(),
  })),
});

const EsquemaSeleccionProyecto = z.object({
  success: z.boolean(),
  message: z.string().nullable(),
});

export type ProyectoDisponible = z.infer<typeof EsquemaListaProyectos>['projects'][number];

/**
 * Lógica de fetch/CSRF/selección de proyecto, extraída de `SelectorProyecto` en la ronda de
 * arreglos 1 de la Tarea 4 (hallazgo del revisor de código): el panel de cuenta (`MenuCuenta`)
 * necesita la MISMA lista y el MISMO POST — nunca un fetch propio — pero con su propio envoltorio
 * visual (sin `<h1>` de página completa ni `.aia-card`). Aislar el estado aquí es lo que permite
 * que `SelectorProyecto` (pantalla completa) y `PanelCambiarProyecto` (panel del menú de cuenta)
 * compartan una sola implementación sin que ninguno imponga su marcado al otro.
 */
export function useSelectorProyecto(alElegir: () => Promise<void>, csrfToken: string) {
  const [proyectos, setProyectos] = useState<ProyectoDisponible[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [seleccionandoId, setSeleccionandoId] = useState<number | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const respuesta = await pedir('/api/proyectos', EsquemaListaProyectos);
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
      const respuesta = await pedir('/api/proyectos/seleccionar', EsquemaSeleccionProyecto, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ name: proyecto.name }),
      });

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
