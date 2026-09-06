import { useId, useState } from 'react';
import { PanelCambiarProyecto } from './PanelCambiarProyecto';

type PropiedadesMenuCuenta = {
  nombre: string;
  csrfToken: string;
  /** Se invoca tras un cambio de proyecto exitoso — recarga el bootstrap (spec T01 §6). */
  alCambiarProyecto: () => Promise<void>;
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
 * Menú de cuenta del rail (Tarea 4): las tres acciones que el brief exige que salgan de datos
 * ya autorizados por el servidor. El tema vive en `ConmutadorTema` (footer del rail, sin
 * duplicarlo aquí). "Cambiar proyecto" muestra `PanelCambiarProyecto`, que comparte el fetch/POST/
 * CSRF de `useSelectorProyecto` con la pantalla completa `SelectorProyecto` sin duplicar esa
 * lógica ni heredar su envoltorio de página (ronda de arreglos 1: un `<h1>` y `.aia-card` de
 * pantalla completa no pertenecen a un panel de menú angosto). "Cerrar sesión" (Tarea 6) delega en
 * el `cerrarSesion` del `SesionProvider` — el único POST con CSRF contra `/api/auth/logout` de
 * todo el árbol, nunca el `GET /logout` legado: spec T01 §"no destructive GET".
 */
export function MenuCuenta({ nombre, csrfToken, alCambiarProyecto, cerrarSesion }: PropiedadesMenuCuenta) {
  const idPanel = useId();
  const [abierto, setAbierto] = useState(false);
  const [vista, setVista] = useState<'menu' | 'proyectos'>('menu');
  const [cerrandoSesion, setCerrandoSesion] = useState(false);

  function alternar() {
    setAbierto((valor) => !valor);
  }

  function volverAlMenu() {
    setVista('menu');
  }

  async function alElegirProyecto() {
    setAbierto(false);
    setVista('menu');
    await alCambiarProyecto();
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
        {vista === 'menu' ? (
          <>
            <span className="aia-sidebar__account-head" role="presentation">
              {nombre}
            </span>
            <button
              type="button"
              role="menuitem"
              className="aia-sidebar__account-item"
              onClick={() => setVista('proyectos')}
            >
              Cambiar proyecto
            </button>
            <button
              type="button"
              role="menuitem"
              className="aia-sidebar__account-item"
              disabled={cerrandoSesion}
              onClick={() => void alCerrarSesion()}
            >
              {cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar sesión'}
            </button>
          </>
        ) : (
          <>
            <button type="button" role="menuitem" className="aia-sidebar__account-item" onClick={volverAlMenu}>
              ← Volver
            </button>
            <PanelCambiarProyecto csrfToken={csrfToken} alElegir={alElegirProyecto} />
          </>
        )}
      </div>
    </div>
  );
}
