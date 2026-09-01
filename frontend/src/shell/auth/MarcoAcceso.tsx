import { useCallback, useRef, type MouseEvent, type ReactNode } from 'react';
import { ConmutadorTema } from '../ConmutadorTema';

const ID_CONTENIDO_ACCESO = 'contenido-acceso';

type PropiedadesMarcoAcceso = {
  titulo: string;
  children: ReactNode;
};

/**
 * Envoltorio compartido de las pantallas públicas de acceso (Tarea 8, S01): login y,
 * más adelante, el cambio de clave obligatorio. Aporta el único `h1` de la página,
 * el skip link (mismo patrón de foco explícito que `AppShell`, ver su comentario),
 * la marca, el `ConmutadorTema` y el pie — nunca más de un `<main>` ni de un `<h1>`
 * por pantalla.
 */
export function MarcoAcceso({ titulo, children }: PropiedadesMarcoAcceso) {
  const contenidoRef = useRef<HTMLElement>(null);

  const alSaltarAlContenido = useCallback((evento: MouseEvent<HTMLAnchorElement>) => {
    evento.preventDefault();
    contenidoRef.current?.focus();
  }, []);

  return (
    <div className="aia-shell">
      <a className="aia-skip-link" href={`#${ID_CONTENIDO_ACCESO}`} onClick={alSaltarAlContenido}>
        Saltar al contenido
      </a>

      <header className="aia-page">
        <span className="aia-title">Last Planner AIA</span>
        <ConmutadorTema />
      </header>

      <main id={ID_CONTENIDO_ACCESO} ref={contenidoRef} className="aia-page" tabIndex={-1}>
        <section className="aia-card">
          <h1>{titulo}</h1>
          {children}
        </section>
      </main>

      <footer className="aia-page">
        <p className="aia-copy">© Last Planner AIA</p>
      </footer>
    </div>
  );
}
