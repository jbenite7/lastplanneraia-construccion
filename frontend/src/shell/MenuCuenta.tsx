import { useId, useState } from 'react';

type PropiedadesMenuCuenta = {
  nombre: string;
  /**
   * Cierra sesión vía el único `ControlActividad` del shell (Tarea 6, T01) — nunca un fetch propio.
   * Es CSRF-idempotente y siempre invalida el estado local, así que este componente no necesita
   * un camino de error propio: `SesionProvider.cerrarSesion` (ver `SesionProvider.tsx`) siempre
   * resuelve. El valor resuelto (confirmado/red) no le importa a este componente presentacional —
   * `logoutSinConfirmar` en el contexto de sesión es lo que decide el mensaje, no este botón.
   */
  cerrarSesion: () => Promise<unknown>;
};

/**
 * Menú de cuenta del rail (Tarea 4): las acciones que el brief exige que salgan de datos ya
 * autorizados por el servidor. El tema vive en `ConmutadorTema` (footer del rail, sin
 * duplicarlo aquí).
 *
 * **T7-1 (Tarea 7, S04):** "Cambiar proyecto" dejó de abrir `PanelCambiarProyecto` en sitio y
 * pasó a un enlace de navegación completa a `/proyectos` — spec S04 §421 lo pide literal, y
 * §474 exige descartar las cachés del proyecto anterior antes de cualquier render operativo,
 * cosa que una recarga completa garantiza y un panel en sitio no. `PanelCambiarProyecto` y
 * `useSelectorProyecto` se retiraron con este cambio (sin consumidores restantes). "Cerrar
 * sesión" (Tarea 6) delega en el `cerrarSesion` del `SesionProvider` — el único POST con CSRF
 * contra `/api/auth/logout` de todo el árbol, nunca el `GET /logout` legado: spec T01 §"no
 * destructive GET".
 */
export function MenuCuenta({ nombre, cerrarSesion }: PropiedadesMenuCuenta) {
  const idPanel = useId();
  const [abierto, setAbierto] = useState(false);
  const [cerrandoSesion, setCerrandoSesion] = useState(false);

  function alternar() {
    setAbierto((valor) => !valor);
  }

  async function alCerrarSesion() {
    setCerrandoSesion(true);
    await cerrarSesion();
    // Sin `finally`: si `cerrarSesion()` resuelve, la sesión ya se invalidó y `SesionProvider`
    // desmontará este árbol al recargar — no queda un `setCerrandoSesion(false)` que pisar.
  }

  return (
    <div className="aia-menu aia-sidebar__account" data-aia-component="menu">
      <button
        type="button"
        className="aia-sidebar__utility"
        data-aia-menu-trigger
        aria-haspopup="menu"
        aria-controls={idPanel}
        aria-expanded={abierto}
        aria-label={`Cuenta · ${nombre}`}
        onClick={alternar}
      >
        <span className="aia-sidebar__label">Cuenta · {nombre}</span>
      </button>

      <div id={idPanel} data-aia-menu-panel role="menu" hidden={!abierto}>
        <span className="aia-sidebar__account-head" role="presentation">
          {nombre}
        </span>
        <a href="/proyectos" role="menuitem" className="aia-sidebar__account-item">
          Cambiar proyecto
        </a>
        <button
          type="button"
          role="menuitem"
          className="aia-sidebar__account-item"
          disabled={cerrandoSesion}
          onClick={() => void alCerrarSesion()}
        >
          {cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar sesión'}
        </button>
      </div>
    </div>
  );
}
