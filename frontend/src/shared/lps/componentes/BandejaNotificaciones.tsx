import type { UseNotificacionesApi } from '../estado/useNotificaciones';

export interface PropiedadesBandejaNotificaciones {
  /** El resultado de `useNotificaciones(csrfToken)` — este componente es puramente presentacional. */
  api: UseNotificacionesApi;
}

/**
 * Única bandeja React para escritorio y móvil (T02-AC-146): un solo `<ul>`, sin dos listas DOM
 * sincronizadas como el legacy `notifications.js` (`#notificationList`/`#notificationListMobile`).
 * La presentación responsive es puramente CSS (`lps-contexto.css`), nunca una segunda copia de
 * marcado.
 */
export function BandejaNotificaciones({ api }: PropiedadesBandejaNotificaciones) {
  const { estado, reintentar, marcarLeida } = api;

  if (estado.status === 'cargando') {
    return (
      <div className="lps-bandeja-notificaciones" role="status" aria-live="polite">
        <span className="lps-bandeja-notificaciones__cargando">Cargando notificaciones…</span>
      </div>
    );
  }

  const lista = estado.status === 'lista' ? estado.notificaciones : estado.notificacionesPrevias ?? [];
  const hayError = estado.status === 'error';
  const actualizando = estado.status === 'lista' && estado.actualizando;

  async function alMarcarLeida(id: number): Promise<void> {
    try {
      await marcarLeida(id);
    } catch {
      // El propio hook conserva la lista intacta ante un fallo (AC-151); esta bandeja no
      // necesita un estado adicional — el ítem sigue visible, listo para reintentarse.
    }
  }

  return (
    <div className="lps-bandeja-notificaciones">
      <span
        className="lps-bandeja-notificaciones__contador"
        aria-label={`${lista.length} notificaciones sin leer`}
      >
        {lista.length}
      </span>

      {hayError && (
        <div className="lps-bandeja-notificaciones__error" role="alert">
          <span>No se pudieron actualizar las notificaciones.</span>
          <button type="button" onClick={reintentar}>Reintentar</button>
        </div>
      )}

      {actualizando && (
        <span className="lps-bandeja-notificaciones__actualizando" role="status" aria-live="polite">
          Actualizando…
        </span>
      )}

      <ul className="lps-bandeja-notificaciones__lista" aria-label="Notificaciones">
        {lista.length === 0 && !hayError && (
          <li className="lps-bandeja-notificaciones__vacio">No hay notificaciones nuevas</li>
        )}
        {lista.map((notificacion) => (
          <li key={notificacion.id} className="lps-bandeja-notificaciones__item">
            <button
              type="button"
              className="lps-bandeja-notificaciones__marcar"
              onClick={() => { void alMarcarLeida(notificacion.id); }}
            >
              <span className="lps-bandeja-notificaciones__titulo">
                {notificacion.title}
                {notificacion.itemCount > 1 && (
                  <span className="lps-bandeja-notificaciones__grupo">{notificacion.itemCount}</span>
                )}
              </span>
              <span className="lps-bandeja-notificaciones__mensaje">{notificacion.message}</span>
              <span className="lps-bandeja-notificaciones__fecha">{notificacion.createdAt}</span>
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
