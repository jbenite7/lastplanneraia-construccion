import { useCallback, useRef, type MouseEvent, type ReactNode } from 'react';
import { ConmutadorTema } from '../ConmutadorTema';

const ID_CONTENIDO_ACCESO = 'contenido-acceso';

type PropiedadesMarcoAcceso = {
  titulo: string;
  /**
   * Id opcional para el `h1`. Lo usa `CambioClaveObligatorio` (Tarea 9) para que el
   * `<dialog>` que envuelve enlace `aria-labelledby` al mismo título de la página en
   * vez de duplicar un segundo `h1` — la regla de "un único `h1` por pantalla" (spec
   * T01 §14) sigue intacta porque el título del diálogo y el de la página son el
   * mismo nodo.
   */
  idTitulo?: string;
  /**
   * Subtítulo opcional bajo el `h1`, paridad visual con el legado (2026-09-16). Solo se
   * renderiza cuando viene: `CambioClaveObligatorio` no lo pasa y no lleva subtítulo.
   */
  subtitulo?: string;
  children: ReactNode;
};

/**
 * Envoltorio compartido de las pantallas públicas de acceso (Tarea 8, S01): login y,
 * más adelante, el cambio de clave obligatorio. Aporta el único `h1` de la página,
 * el skip link (mismo patrón de foco explícito que `AppShell`, ver su comentario),
 * el `ConmutadorTema` y el pie — nunca más de un `<main>` ni de un `<h1>` por
 * pantalla. La marca vive dentro de la tarjeta (paridad visual, 2026-09-16), no
 * suelta en la cabecera.
 *
 * `aia-auth`/`aia-auth__layout` (Tarea 11) son clases **añadidas**, nunca sustitutas,
 * de las primitivas `aia-shell`/`aia-page`: acotan la hoja `public/css/auth-react.css`
 * a esta pantalla, porque `frontend/index.html` es el documento de toda la SPA y sin
 * ese scope la disposición de dos paneles se filtraría a pantallas no relacionadas.
 */
export function MarcoAcceso({ titulo, idTitulo, subtitulo, children }: PropiedadesMarcoAcceso) {
  const contenidoRef = useRef<HTMLElement>(null);

  const alSaltarAlContenido = useCallback((evento: MouseEvent<HTMLAnchorElement>) => {
    evento.preventDefault();
    contenidoRef.current?.focus();
  }, []);

  return (
    <div className="aia-shell aia-auth">
      <a className="aia-skip-link" href={`#${ID_CONTENIDO_ACCESO}`} onClick={alSaltarAlContenido}>
        Saltar al contenido
      </a>

      {/* Paridad visual (2026-09-16, opción A de Felipe): la marca ya no va suelta aquí, sino en
          la tarjeta. La cabecera se queda con la utilidad de tema. */}
      <header className="aia-page aia-auth__cabecera">
        <ConmutadorTema />
      </header>

      <main id={ID_CONTENIDO_ACCESO} ref={contenidoRef} className="aia-page aia-auth__layout" tabIndex={-1}>
        <section className="aia-card">
          <div className="aia-auth__marca">
            <span className="aia-auth__marca-glifo" aria-hidden="true" />
            <span className="aia-auth__marca-nombre">Last Planner AIA</span>
          </div>
          <h1 id={idTitulo}>{titulo}</h1>
          {subtitulo && <p className="aia-auth__subtitulo">{subtitulo}</p>}
          {children}
        </section>
      </main>

      <footer className="aia-page aia-auth__pie">
        <p className="aia-copy">
          <span>© 2026 Arquitectos e Ingenieros Asociados</span>
          <span>
            Construyendo con <strong>+CERTEZA</strong>
          </span>
        </p>
      </footer>
    </div>
  );
}
