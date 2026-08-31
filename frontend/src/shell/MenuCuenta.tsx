import { useId, useState } from 'react';
import { z } from 'zod';
import { pedir } from '../lib/api/cliente';
import { PanelCambiarProyecto } from './PanelCambiarProyecto';

const EsquemaCierreSesion = z.object({ success: z.boolean() });

type PropiedadesMenuCuenta = {
  nombre: string;
  csrfToken: string;
  /** Se invoca tras un cambio de proyecto exitoso — recarga el bootstrap (spec T01 §6). */
  alCambiarProyecto: () => Promise<void>;
};

/**
 * Menú de cuenta del rail (Tarea 4): las tres acciones que el brief exige que salgan de datos
 * ya autorizados por el servidor. El tema vive en `ConmutadorTema` (footer del rail, sin
 * duplicarlo aquí). "Cambiar proyecto" muestra `PanelCambiarProyecto`, que comparte el fetch/POST/
 * CSRF de `useSelectorProyecto` con la pantalla completa `SelectorProyecto` sin duplicar esa
 * lógica ni heredar su envoltorio de página (ronda de arreglos 1: un `<h1>` y `.aia-card` de
 * pantalla completa no pertenecen a un panel de menú angosto). "Cerrar sesión" es un POST con
 * CSRF contra `/api/auth/logout` (nunca el `GET /logout` legado): spec T01 §"no destructive GET".
 */
export function MenuCuenta({ nombre, csrfToken, alCambiarProyecto }: PropiedadesMenuCuenta) {
  const idPanel = useId();
  const [abierto, setAbierto] = useState(false);
  const [vista, setVista] = useState<'menu' | 'proyectos'>('menu');
  const [cerrandoSesion, setCerrandoSesion] = useState(false);
  const [errorCierre, setErrorCierre] = useState<string | null>(null);

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

  async function cerrarSesion() {
    setErrorCierre(null);
    setCerrandoSesion(true);
    try {
      await pedir('/api/auth/logout', EsquemaCierreSesion, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
      });
      window.location.href = '/login';
    } catch {
      setErrorCierre('No pudimos cerrar la sesión. Intenta de nuevo.');
      setCerrandoSesion(false);
    }
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
              onClick={() => void cerrarSesion()}
            >
              {cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar sesión'}
            </button>
            {errorCierre && (
              <p role="alert" className="aia-alert aia-alert--error">
                {errorCierre}
              </p>
            )}
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
