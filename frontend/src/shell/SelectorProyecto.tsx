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

type ProyectoDisponible = z.infer<typeof EsquemaListaProyectos>['projects'][number];

type PropiedadesSelectorProyecto = {
  alElegir: () => Promise<void>;
  csrfToken: string;
};

export function SelectorProyecto({ alElegir, csrfToken }: PropiedadesSelectorProyecto) {
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

  return (
    <section className="aia-card">
      <h1>Elige un proyecto</h1>

      {error && <p role="alert" className="aia-alert aia-alert--error">{error}</p>}

      {proyectos === null ? (
        <p role="status">Cargando proyectos…</p>
      ) : proyectos.length === 0 ? (
        <p>No tienes proyectos asignados. Pídele acceso a un administrador.</p>
      ) : (
        <ul>
          {proyectos.map((proyecto) => (
            <li key={proyecto.id}>
              <button
                type="button"
                className="aia-btn aia-btn--secondary"
                disabled={seleccionandoId !== null}
                onClick={() => void elegir(proyecto)}
              >
                {seleccionandoId === proyecto.id ? 'Abriendo…' : proyecto.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
