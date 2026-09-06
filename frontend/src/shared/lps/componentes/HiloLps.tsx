import type { ComentarioRaiz } from '../api/esquemas';

type PropiedadesHiloLps = {
  comentarios: ComentarioRaiz[];
  actualizando: boolean;
};

function textoMenciones(menciones: ComentarioRaiz['menciones']): string | null {
  if (!menciones || menciones.roles.length === 0) return null;
  return `Menciona a: ${menciones.roles.join(', ')}`;
}

/**
 * Hilo de comentarios/respuestas (T02-AC-084..092): raíces por fecha ascendente con sus
 * respuestas anidadas un solo nivel — el orden y la forma ya vienen resueltos del presenter
 * (`LpsThreadPresenter`), este componente sólo renderiza. Todo el texto es contenido de React
 * (nunca `dangerouslySetInnerHTML`, AC-176).
 */
export function HiloLps({ comentarios, actualizando }: PropiedadesHiloLps) {
  return (
    <section className="lps-hilo" aria-label="Hilo de comentarios">
      <p role="status" aria-live="polite">
        {actualizando ? 'Actualizando…' : `${comentarios.length} comentario(s)`}
      </p>
      {comentarios.length === 0 ? (
        <p>Sin comentarios todavía.</p>
      ) : (
        <ul className="lps-hilo__lista">
          {comentarios.map((raiz) => (
            <li key={raiz.id} className="lps-hilo__comentario">
              <p>
                <strong>{raiz.autor_nombre ?? 'Sistema'}</strong>
                {raiz.autor_cargo ? ` — ${raiz.autor_cargo}` : ''}
              </p>
              <p>{raiz.comentario}</p>
              <p>
                <small>{raiz.created_at}</small>
              </p>
              {textoMenciones(raiz.menciones) ? <p>{textoMenciones(raiz.menciones)}</p> : null}
              {raiz.respuestas.length > 0 ? (
                <ul className="lps-hilo__respuestas" aria-label={`Respuestas a comentario de ${raiz.autor_nombre ?? 'Sistema'}`}>
                  {raiz.respuestas.map((respuesta) => (
                    <li key={respuesta.id}>
                      <p>
                        <strong>{respuesta.autor_nombre ?? 'Sistema'}</strong>
                        {respuesta.autor_cargo ? ` — ${respuesta.autor_cargo}` : ''}
                      </p>
                      <p>{respuesta.comentario}</p>
                      <p>
                        <small>{respuesta.created_at}</small>
                      </p>
                    </li>
                  ))}
                </ul>
              ) : null}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
